<?php



require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('admin');
$baslik = 'İlaç Kataloğu — ' . APP_NAME;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $islem = $_POST['islem'] ?? '';
    if ($islem === 'ekle' || $islem === 'duzenle') {
        $ad        = trim($_POST['ad'] ?? '');
        $katId     = (int)($_POST['kategori_id'] ?? 0);
        $barkod    = trim($_POST['barkod'] ?? '') ?: null;
        $etken     = trim($_POST['etken_madde'] ?? '') ?: null;
        $aciklama  = trim($_POST['aciklama'] ?? '') ?: null;
        if (!$ad || !$katId) {
            flashMesajAyarla('tehlike', 'İlaç adı ve kategori zorunludur.');
        } elseif ($islem === 'ekle') {
            $stmt = db()->prepare(
                "INSERT INTO ilaclar (kategori_id, ad, barkod, etken_madde, aciklama) VALUES (?,?,?,?,?)"
            );
            $stmt->execute([$katId, $ad, $barkod, $etken, $aciklama]);
            flashMesajAyarla('basari', 'İlaç eklendi.');
        } else {
            $id = (int)($_POST['ilac_id'] ?? 0);
            $stmt = db()->prepare(
                "UPDATE ilaclar SET kategori_id=?, ad=?, barkod=?, etken_madde=?, aciklama=? WHERE id=?"
            );
            $stmt->execute([$katId, $ad, $barkod, $etken, $aciklama, $id]);
            flashMesajAyarla('basari', 'İlaç güncellendi.');
        }
    } elseif ($islem === 'sil') {
        $id = (int)($_POST['ilac_id'] ?? 0);
        db()->prepare("UPDATE ilaclar SET aktif=0 WHERE id=?")->execute([$id]);
        flashMesajAyarla('basari', 'İlaç pasif yapıldı.');
    } elseif ($islem === 'aktifles') {
        $id = (int)($_POST['ilac_id'] ?? 0);
        db()->prepare("UPDATE ilaclar SET aktif=1 WHERE id=?")->execute([$id]);
        flashMesajAyarla('basari', 'İlaç aktifleştirildi.');
    }
    yonlendir(sayf('admin/medicines.php'));
}
$ilaclar    = db()->query("SELECT i.*, k.ad AS kategori_adi FROM ilaclar i LEFT JOIN kategoriler k ON k.id=i.kategori_id ORDER BY i.aktif DESC, k.ad, i.ad")->fetchAll();
$kategoriler = db()->query("SELECT * FROM kategoriler ORDER BY ad")->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('pill') ?> İlaç Kataloğu</h1>
        <button class="btn btn-birincil" onclick="modalAc('ilacModal')">
            <?= svgIkon('plus') ?> Yeni İlaç Ekle
        </button>
    </div>
    <div class="kart">
        <div class="filtre-alan">
            <div class="arama-alani">
                <?= svgIkon('search') ?>
                <input type="text" id="aramaInput" placeholder="İlaç adı, barkod, etken madde...">
            </div>
            <span style="font-size:.8rem;color:var(--metin-uc);"><?= count($ilaclar) ?> ilaç</span>
        </div>
        <div class="tablo-sarici" style="border:none;box-shadow:none;border-radius:0;">
            <table class="tablo" id="anaTablosu">
                <thead>
                    <tr>
                        <th>İlaç Adı</th>
                        <th>Kategori</th>
                        <th>Barkod</th>
                        <th>Etken Madde</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ilaclar as $i): ?>
                    <tr style="<?= $i['aktif'] ? '' : 'opacity:.55;' ?>">
                        <td><strong><?= e($i['ad']) ?></strong></td>
                        <td><span class="rozet rozet-mavi" style="font-size:0.75rem;"><?= e($i['kategori_adi'] ?? 'Kategorisiz') ?></span></td>
                        <td style="font-size:.8rem;font-family:monospace;color:var(--metin-ikincil);"><?= e($i['barkod'] ?? '—') ?></td>
                        <td style="font-size:0.875rem;"><?= e($i['etken_madde'] ?? '—') ?></td>
                        <td><span class="rozet <?= $i['aktif'] ? 'rozet-yesil' : 'rozet-gri' ?>"><?= $i['aktif'] ? 'Aktif' : 'Pasif' ?></span></td>
                        <td>
                            <div style="display:flex;gap:.4rem;">
                                <button class="btn btn-gri btn-sm" onclick="ilacDuzenle(<?= htmlspecialchars(json_encode($i), ENT_QUOTES, 'UTF-8') ?>)">
                                    <?= svgIkon('edit') ?>
                                </button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="islem" value="<?= $i['aktif'] ? 'sil' : 'aktifles' ?>">
                                    <input type="hidden" name="ilac_id" value="<?= $i['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $i['aktif'] ? 'btn-tehlike' : 'btn-ikincil' ?>"
                                            data-onay="<?= $i['aktif'] ? 'Bu ilacı pasif yapmak istiyor musunuz?' : 'Bu ilacı aktifleştirmek istiyor musunuz?' ?>">
                                        <?= $i['aktif'] ? svgIkon('trash') : svgIkon('check') ?>
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
<div class="modal-arkaplan" id="ilacModal" role="dialog" aria-labelledby="ilacModalBaslik" aria-hidden="true">
    <div class="modal-kutu">
        <div class="modal-baslik">
            <h3 id="ilacModalBaslik"><?= svgIkon('pill') ?> Yeni İlaç Ekle</h3>
            <button class="modal-kapat" onclick="modalKapat('ilacModal')" aria-label="Kapat">✕</button>
        </div>
        <form method="post" id="ilacForm">
            <div class="modal-govde">
                <input type="hidden" name="islem" id="ilacIslem" value="ekle">
                <input type="hidden" name="ilac_id" id="ilacId" value="">
                <div class="form-grid">
                    <div class="form-grup tam-satir">
                        <label class="form-etiket" for="ilacAd">İlaç Adı *</label>
                        <input class="form-giris" type="text" name="ad" id="ilacAd" placeholder="Örn: Parol 500 mg" required>
                    </div>
                    <div class="form-grup">
                        <label class="form-etiket" for="ilacKategori">Kategori *</label>
                        <select class="form-secim" name="kategori_id" id="ilacKategori" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($kategoriler as $k): ?>
                                <option value="<?= $k['id'] ?>"><?= e($k['ad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-grup">
                        <label class="form-etiket" for="ilacBarkod">Barkod</label>
                        <input class="form-giris" type="text" name="barkod" id="ilacBarkod" placeholder="8699...">
                    </div>
                    <div class="form-grup tam-satir">
                        <label class="form-etiket" for="ilacEtken">Etken Madde</label>
                        <input class="form-giris" type="text" name="etken_madde" id="ilacEtken" placeholder="Parasetamol">
                    </div>
                    <div class="form-grup tam-satir">
                        <label class="form-etiket" for="ilacAciklama">Açıklama</label>
                        <textarea class="form-alani" name="aciklama" id="ilacAciklama" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-ayak">
                <button type="button" class="btn btn-gri" onclick="modalKapat('ilacModal')">İptal</button>
                <button type="submit" class="btn btn-birincil">
                    <?= svgIkon('check') ?> Kaydet
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function ilacDuzenle(ilac) {
    document.getElementById('ilacModalBaslik').innerHTML = '<?= addslashes(svgIkon('edit')) ?> İlaç Düzenle';
    document.getElementById('ilacIslem').value   = 'duzenle';
    document.getElementById('ilacId').value      = ilac.id;
    document.getElementById('ilacAd').value      = ilac.ad;
    document.getElementById('ilacKategori').value = ilac.kategori_id;
    document.getElementById('ilacBarkod').value  = ilac.barkod ?? '';
    document.getElementById('ilacEtken').value   = ilac.etken_madde ?? '';
    document.getElementById('ilacAciklama').value = ilac.aciklama ?? '';
    modalAc('ilacModal');
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
