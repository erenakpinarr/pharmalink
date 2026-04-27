<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('eczane');

$kid = mevcutKullaniciId();
$eczane = db()->prepare("SELECT id FROM eczaneler WHERE kullanici_id = ? LIMIT 1");
$eczane->execute([$kid]);
$eczane = $eczane->fetch();
$eczaneId = $eczane['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $islem = $_POST['islem'] ?? '';
    
    if ($islem === 'guncelle') {
        $ilacId = (int)$_POST['ilac_id'];
        $adet = (int)$_POST['adet'];
        $durum = $_POST['durum'] ?? 'mevcut';
        
        $stmt = db()->prepare("
            INSERT INTO stok (eczane_id, ilac_id, adet, durum) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE adet = ?, durum = ?
        ");
        $stmt->execute([$eczaneId, $ilacId, $adet, $durum, $adet, $durum]);
        flashMesajAyarla('basari', 'Stok güncellendi.');
    } elseif ($islem === 'sil') {
        $ilacId = (int)$_POST['ilac_id'];
        db()->prepare("DELETE FROM stok WHERE eczane_id = ? AND ilac_id = ?")->execute([$eczaneId, $ilacId]);
        flashMesajAyarla('basari', 'İlaç stoktan kaldırıldı.');
    }
    yonlendir(sayf('pharmacy/inventory.php'));
}

$baslik = 'Stok Yönetimi — ' . APP_NAME;

// Mevcut stoku getir
$stok = db()->prepare("
    SELECT s.*, i.ad AS ilac_adi, k.ad AS kategori_adi
    FROM stok s
    JOIN ilaclar i ON i.id = s.ilac_id
    JOIN kategoriler k ON k.id = i.kategori_id
    WHERE s.eczane_id = ?
    ORDER BY i.ad ASC
");
$stok->execute([$eczaneId]);
$stoklar = $stok->fetchAll();

// Tüm ilaçları getir (Ekleme için)
$tumIlaclar = db()->query("SELECT i.*, k.ad AS kategori FROM ilaclar i JOIN kategoriler k ON k.id = i.kategori_id ORDER BY i.ad")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('package') ?> Stok Yönetimi</h1>
        <button class="btn btn-birincil" onclick="modalAc('stokEkleModal')">
            <?= svgIkon('plus') ?> Yeni İlaç Ekle
        </button>
    </div>

    <div class="tablo-sarici">
        <table class="tablo">
            <thead>
                <tr>
                    <th>İlaç Adı</th>
                    <th>Kategori</th>
                    <th>Adet</th>
                    <th>Durum</th>
                    <th style="text-align: right;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stoklar as $s): ?>
                    <tr>
                        <td><strong><?= e($s['ilac_adi']) ?></strong></td>
                        <td><?= e($s['kategori_adi']) ?></td>
                        <td><?= (int)$s['adet'] ?></td>
                        <td>
                            <?php
                            $renk = match($s['durum']) {
                                'mevcut' => 'rozet-yesil',
                                'az_stok' => 'rozet-mavi',
                                'tukendi' => 'rozet-kirmizi',
                                default => 'rozet-gri'
                            };
                            ?>
                            <span class="rozet <?= $renk ?>"><?= e(ucfirst(str_replace('_', ' ', $s['durum']))) ?></span>
                        </td>
                        <td style="text-align: right;">
                            <button class="btn btn-gri btn-sm" onclick='duzenle(<?= json_encode($s) ?>)'><?= svgIkon('edit') ?></button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Bu ilacı stoktan kaldırmak istediğinize emin misiniz?')">
                                <input type="hidden" name="islem" value="sil">
                                <input type="hidden" name="ilac_id" value="<?= $s['ilac_id'] ?>">
                                <button type="submit" class="btn btn-gri btn-sm" style="color: var(--renk-tehlike);"><?= svgIkon('trash') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<div class="modal-arkaplan" id="stokEkleModal">
    <div class="modal-kutu">
        <div class="modal-baslik">
            <h3 id="modalBaslik">Yeni Stok Ekle</h3>
            <button class="modal-kapat" onclick="modalKapat('stokEkleModal')">✕</button>
        </div>
        <form method="post">
            <input type="hidden" name="islem" value="guncelle">
            <div class="modal-govde">
                <div class="form-grup">
                    <label>İlaç Seçin</label>
                    <select name="ilac_id" id="ilacSelect" class="form-giris" required>
                        <?php foreach ($tumIlaclar as $il): ?>
                            <option value="<?= $il['id'] ?>"><?= e($il['ad']) ?> (<?= e($il['kategori']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-grup">
                    <label>Adet</label>
                    <input type="number" name="adet" id="adetInput" class="form-giris" min="0" required>
                </div>
                <div class="form-grup">
                    <label>Durum</label>
                    <select name="durum" id="durumSelect" class="form-giris">
                        <option value="mevcut">Mevcut</option>
                        <option value="az_stok">Az Stok</option>
                        <option value="tukendi">Tükendi</option>
                    </select>
                </div>
            </div>
            <div class="modal-ayak">
                <button type="button" class="btn btn-gri" onclick="modalKapat('stokEkleModal')">Vazgeç</button>
                <button type="submit" class="btn btn-birincil">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function duzenle(stok) {
    document.getElementById('modalBaslik').innerText = 'Stok Güncelle: ' + stok.ilac_adi;
    document.getElementById('ilacSelect').value = stok.ilac_id;
    document.getElementById('ilacSelect').disabled = true; // Düzenlemede ilacı değiştirtme
    document.getElementById('adetInput').value = stok.adet;
    document.getElementById('durumSelect').value = stok.durum;
    
    // Hidden field ekle çünkü disabled select post edilmez
    if (!document.getElementById('hiddenIlacId')) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'ilac_id';
        hidden.id = 'hiddenIlacId';
        document.querySelector('#stokEkleModal form').appendChild(hidden);
    }
    document.getElementById('hiddenIlacId').value = stok.ilac_id;
    
    modalAc('stokEkleModal');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
