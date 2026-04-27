<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('admin');
$baslik = 'Kategoriler — ' . APP_NAME;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $islem = $_POST['islem'] ?? '';
    $ad = trim($_POST['ad'] ?? '');
    $aciklama = trim($_POST['aciklama'] ?? '') ?: null;
    if ($islem === 'ekle' && $ad) {
        try {
            db()->prepare("INSERT INTO kategoriler (ad, aciklama) VALUES (?,?)")->execute([$ad, $aciklama]);
            flashMesajAyarla('basari', 'Kategori eklendi.');
        } catch (PDOException $e) {
            flashMesajAyarla('tehlike', 'Bu kategori adı zaten mevcut.');
        }
    } elseif ($islem === 'duzenle' && $ad) {
        $id = (int)($_POST['kat_id'] ?? 0);
        db()->prepare("UPDATE kategoriler SET ad=?, aciklama=? WHERE id=?")->execute([$ad, $aciklama, $id]);
        flashMesajAyarla('basari', 'Kategori güncellendi.');
    } elseif ($islem === 'sil') {
        $id = (int)($_POST['kat_id'] ?? 0);
        $sayi = db()->prepare("SELECT COUNT(*) FROM ilaclar WHERE kategori_id=?");
        $sayi->execute([$id]);
        if ((int)$sayi->fetchColumn() > 0) {
            flashMesajAyarla('uyari', 'Bu kategoride ilaç bulunduğu için silinemez.');
        } else {
            db()->prepare("DELETE FROM kategoriler WHERE id=?")->execute([$id]);
            flashMesajAyarla('basari', 'Kategori silindi.');
        }
    }
    yonlendir(sayf('admin/categories.php'));
}
$kategoriler = db()->query(
    "SELECT k.*, COUNT(i.id) AS ilac_sayisi
     FROM kategoriler k
     LEFT JOIN ilaclar i ON i.kategori_id = k.id AND i.aktif=1
     GROUP BY k.id ORDER BY k.ad"
)->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('tag') ?> Kategori Yönetimi</h1>
        <button class="btn btn-birincil" onclick="modalAc('katModal')">
            <?= svgIkon('plus') ?> Yeni Kategori
        </button>
    </div>
    <div class="kart">
        <div class="tablo-sarici">
            <table class="tablo">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Kategori Adı</th>
                        <th>Açıklama</th>
                        <th>İstatistik</th>
                        <th>Kayıt Tarihi</th>
                        <th style="text-align:right;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kategoriler)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--metin-uc);">Kategori bulunamadı.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($kategoriler as $k): ?>
                    <tr>
                        <td><span style="color:var(--metin-uc);font-size:.8rem;"><?= $k['id'] ?></span></td>
                        <td><strong><?= e($k['ad']) ?></strong></td>
                        <td><span style="color:var(--metin-ikincil);font-size:.875rem;"><?= e($k['aciklama'] ?? 'Açıklama yok') ?></span></td>
                        <td>
                            <span class="rozet rozet-mavi"><?= $k['ilac_sayisi'] ?> Aktif İlaç</span>
                        </td>
                        <td style="font-size:.8rem;color:var(--metin-uc);"><?= date('d.m.Y', strtotime($k['olusturma'])) ?></td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:.4rem;justify-content:flex-end;">
                                <button class="btn btn-gri btn-sm" onclick="katDuzenle(<?= htmlspecialchars(json_encode($k), ENT_QUOTES, 'UTF-8') ?>)" title="Düzenle">
                                    <?= svgIkon('edit') ?>
                                </button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="islem" value="sil">
                                    <input type="hidden" name="kat_id" value="<?= $k['id'] ?>">
                                    <button type="submit" class="btn btn-tehlike btn-sm" title="Sil"
                                            data-onay="'<?= e($k['ad']) ?>' kategorisini silmek istiyor musunuz?">
                                        <?= svgIkon('trash') ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<div class="modal-arkaplan" id="katModal" role="dialog" aria-labelledby="katModalBaslik" aria-hidden="true">
    <div class="modal-kutu">
        <div class="modal-baslik">
            <h3 id="katModalBaslik"><?= svgIkon('tag') ?> Yeni Kategori Ekle</h3>
            <button class="modal-kapat" onclick="modalKapat('katModal')" aria-label="Kapat">✕</button>
        </div>
        <form method="post">
            <div class="modal-govde">
                <input type="hidden" name="islem" id="katIslem" value="ekle">
                <input type="hidden" name="kat_id" id="katId" value="">
                <div class="form-grid">
                    <div class="form-grup tam-satir">
                        <label class="form-etiket" for="katAd">Kategori Adı *</label>
                        <input class="form-giris" type="text" name="ad" id="katAd" placeholder="Örn: Antibiyotikler" required>
                    </div>
                    <div class="form-grup tam-satir">
                        <label class="form-etiket" for="katAciklama">Açıklama</label>
                        <textarea class="form-alani" name="aciklama" id="katAciklama" rows="3" placeholder="Bu kategoriye ait kısa bir açıklama girin..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-ayak">
                <button type="button" class="btn btn-gri" onclick="modalKapat('katModal')">İptal</button>
                <button type="submit" class="btn btn-birincil">
                    <?= svgIkon('check') ?> Kaydet
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function katDuzenle(kat) {
    document.getElementById('katModalBaslik').textContent = 'Kategori Düzenle';
    document.getElementById('katIslem').value   = 'duzenle';
    document.getElementById('katId').value      = kat.id;
    document.getElementById('katAd').value      = kat.ad;
    document.getElementById('katAciklama').value = kat.aciklama ?? '';
    modalAc('katModal');
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
