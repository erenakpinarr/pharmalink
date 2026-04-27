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
    $resId = (int)$_POST['res_id'];
    $durum = $_POST['durum'];
    
    $stmt = db()->prepare("UPDATE ayirtmalar SET durum = ? WHERE id = ? AND eczane_id = ?");
    $stmt->execute([$durum, $resId, $eczaneId]);
    flashMesajAyarla('basari', 'Rezervasyon durumu güncellendi.');
    yonlendir(sayf('pharmacy/reservations.php'));
}

$baslik = 'Rezervasyon Yönetimi — ' . APP_NAME;

$stmt = db()->prepare("
    SELECT r.*, k.ad, k.soyad, k.telefon, i.ad AS ilac_adi
    FROM ayirtmalar r
    JOIN kullanicilar k ON k.id = r.kullanici_id
    JOIN ilaclar i ON i.id = r.ilac_id
    WHERE r.eczane_id = ?
    ORDER BY r.olusturma_tarihi DESC
");
$stmt->execute([$eczaneId]);
$reservations = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('clock') ?> Rezervasyonlar</h1>
    </div>

    <div class="tablo-sarici">
        <table class="tablo">
            <thead>
                <tr>
                    <th>Müşteri</th>
                    <th>İlaç</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th style="text-align: right;">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td>
                            <strong><?= e($r['ad'] . ' ' . $r['soyad']) ?></strong><br>
                            <small><?= e($r['telefon']) ?></small>
                        </td>
                        <td><?= e($r['ilac_adi']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($r['olusturma_tarihi'])) ?></td>
                        <td>
                            <?php
                            $renk = match($r['durum']) {
                                'beklemede' => 'rozet-gri',
                                'onaylandi' => 'rozet-mavi',
                                'teslim_edildi' => 'rozet-yesil',
                                'reddedildi', 'iptal' => 'rozet-kirmizi',
                                default => 'rozet-gri'
                            };
                            ?>
                            <span class="rozet <?= $renk ?>"><?= e(ucfirst($r['durum'])) ?></span>
                        </td>
                        <td style="text-align: right;">
                            <form method="post" style="display:inline-flex; gap:0.25rem;">
                                <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                                <?php if ($r['durum'] === 'beklemede'): ?>
                                    <button type="submit" name="durum" value="onaylandi" class="btn btn-birincil btn-sm">Onayla</button>
                                    <button type="submit" name="durum" value="reddedildi" class="btn btn-tehlike btn-sm">Reddet</button>
                                <?php elseif ($r['durum'] === 'onaylandi'): ?>
                                    <button type="submit" name="durum" value="teslim_edildi" class="btn btn-yesil btn-sm">Teslim Edildi</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
