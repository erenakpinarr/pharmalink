<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('kullanici');
$baslik = 'Favori İlaçlar — ' . APP_NAME;
$kid = mevcutKullaniciId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ilacId = (int)($_POST['ilac_id'] ?? 0);
    if ($ilacId) {
        db()->prepare("DELETE FROM favoriler WHERE kullanici_id=? AND ilac_id=?")->execute([$kid, $ilacId]);
        flashMesajAyarla('basari', 'İlaç favorilerden kaldırıldı.');
    }
    yonlendir(sayf('user/favorites.php'));
}

if (isset($_GET['ekle'])) {
    $ilacId = (int)$_GET['ekle'];
    if ($ilacId) {
        try {
            db()->prepare("INSERT IGNORE INTO favoriler (kullanici_id, ilac_id) VALUES (?,?)")->execute([$kid, $ilacId]);
            flashMesajAyarla('basari', 'İlaç favorilere eklendi.');
        } catch (PDOException) {}
    }
    yonlendir(sayf('user/favorites.php'));
}

$favoriler = db()->prepare(
    "SELECT f.id AS fav_id, f.olusturma,
            i.id AS ilac_id, i.ad AS ilac_adi, i.barkod, i.etken_madde,
            k.ad AS kategori_adi
     FROM favoriler f
     JOIN ilaclar i ON i.id = f.ilac_id
     JOIN kategoriler k ON k.id = i.kategori_id
     WHERE f.kullanici_id = ? AND i.aktif = 1
     ORDER BY k.ad, i.ad"
);
$favoriler->execute([$kid]);
$favoriler = $favoriler->fetchAll();

$favIlaçIds = array_column($favoriler, 'ilac_id');
$tumIlaclarStmt = db()->query("SELECT i.id, i.ad, k.ad AS kategori FROM ilaclar i JOIN kategoriler k ON k.id=i.kategori_id WHERE i.aktif=1 ORDER BY k.ad, i.ad");
$tumIlaclar = $tumIlaclarStmt->fetchAll();
$eklenebilir = array_filter($tumIlaclar, fn($i) => !in_array($i['id'], $favIlaçIds));
include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('heart') ?> Favori İlaçlarım</h1>
        <div style="display:flex;gap:.75rem;">

            <button class="btn btn-birincil" onclick="modalAc('favEkleModal')">
                <?= svgIkon('plus') ?> <span class="mobil-gizle">Yeni İlaç Ekle</span>
            </button>
        </div>
    </div>
    <?php if (empty($favoriler)): ?>
    <div class="kart">
        <div class="bos-durum" style="padding:4.5rem 2rem;">
            <?= svgIkon('pill') ?>
            <h3 class="mt-1">Henüz favori ilacınız yok</h3>
            <p>Takip etmek istediğiniz ilaçları favorilerinize ekleyin. Stok eşleştirme özelliğiyle en yakın hangi eczanede bulunduğunu anında görün.</p>
            <button class="btn btn-birincil mt-2" onclick="modalAc('favEkleModal')">
                <?= svgIkon('plus') ?> İlk İlacınızı Ekleyin
            </button>
        </div>
    </div>
    <?php else: ?>
    <div class="uyari uyari-bilgi">
        <?= svgIkon('info') ?>
        <span>
            <strong><?= count($favoriler) ?></strong> favori ilaç takip ediyorsunuz.
        </span>
    </div>
    <div class="tablo-sarici mt-1">
        <table class="tablo">
            <thead>
                <tr>
                    <th style="padding-left:1.5rem;">İlaç Bilgisi</th>
                    <th>Kategori</th>
                    <th>Etken Madde</th>
                    <th>Barkod</th>
                    <th>Eklenme</th>
                    <th style="text-align:right; padding-right:1.5rem;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($favoriler as $f): ?>
                <tr>
                    <td style="padding-left:1.5rem;">
                        <div style="display:flex; flex-direction:column;">
                            <strong><?= e($f['ilac_adi']) ?></strong>
                        </div>
                    </td>
                    <td><span class="rozet rozet-mavi" style="font-weight:600;"><?= e($f['kategori_adi']) ?></span></td>
                    <td style="font-size:0.85rem; color:var(--metin-ikincil);"><?= e($f['etken_madde'] ?: '—') ?></td>
                    <td style="font-size:0.85rem; font-family:monospace; color:var(--metin-uc);"><?= e($f['barkod'] ?: '—') ?></td>
                    <td style="font-size:0.8rem; color:var(--metin-uc);"><?= date('d.m.Y', strtotime($f['olusturma'])) ?></td>
                    <td style="text-align:right; padding-right:1.5rem;">
                        <form method="post" style="display:inline;" onsubmit="return confirm('Bu ilacı favorilerinizden kaldırmak istediğinize emin misiniz?')">
                            <input type="hidden" name="ilac_id" value="<?= $f['ilac_id'] ?>">
                            <button type="submit" class="btn btn-gri btn-sm" title="Favorilerden Kaldır">
                                <?= svgIkon('trash') ?> <span class="mobil-gizle">Kaldır</span>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>
<div class="modal-arkaplan" id="favEkleModal">
    <div class="modal-kutu">
        <div class="modal-baslik">
            <h3><?= svgIkon('heart') ?> Favorilere İlaç Ekle</h3>
            <button class="modal-kapat" onclick="modalKapat('favEkleModal')">✕</button>
        </div>
        <div class="modal-govde" style="padding:1.5rem;">
            <div class="arama-alani" style="margin-bottom:1.5rem;">
                <?= svgIkon('search') ?>
                <input type="text" id="modalAramaInput" placeholder="İlaç adı, etken madde veya barkod ara..." oninput="modalFiltrele(this.value)">
            </div>
            <div id="ilacListesi" style="max-height:400px; overflow-y:auto; display:flex; flex-direction:column; gap:0.5rem; padding-right:0.25rem;">
                <?php foreach ($eklenebilir as $il): ?>
                <div class="ilac-satir" data-ad="<?= strtolower(e($il['ad'])) ?>"
                     style="display:flex; justify-content:space-between; align-items:center; padding:0.85rem 1rem; border:1px solid var(--kenar-rengi); border-radius:var(--yaricap-md); background:var(--arkaplan); transition:all 0.2s ease;">
                    <div style="flex:1;">
                        <strong style="font-size:0.95rem; color:var(--metin-birincil); display:block;"><?= e($il['ad']) ?></strong>
                        <span class="rozet rozet-gri" style="font-size:0.7rem; margin-top:0.25rem;"><?= e($il['kategori']) ?></span>
                    </div>
                    <a href="?ekle=<?= $il['id'] ?>" class="btn btn-birincil btn-sm" style="width:36px; height:36px; justify-content:center; padding:0; border-radius:50%;">
                        <?= svgIkon('plus') ?>
                    </a>
                </div>
                <?php endforeach; ?>
                <?php if (empty($eklenebilir)): ?>
                    <div style="text-align:center; padding:3rem 1rem; color:var(--metin-uc);">
                        <?= svgIkon('check-circle') ?>
                        <p style="margin-top:0.5rem;">Tüm ilaçlar zaten favorilerinizde!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="modal-ayak" style="justify-content:flex-end;">
            <button class="btn btn-gri" onclick="modalKapat('favEkleModal')">Kapat</button>
        </div>
    </div>
</div>
<script>
function modalFiltrele(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.ilac-satir').forEach(el => {
        el.style.display = el.dataset.ad.includes(q) ? '' : 'none';
    });
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
