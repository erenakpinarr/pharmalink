<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('eczane');
csrf_verify();

$kid = mevcutKullaniciId();
$eczaneStmt = db()->prepare("SELECT id FROM eczaneler WHERE kullanici_id = ? AND durum = 'onaylandi' LIMIT 1");
$eczaneStmt->execute([$kid]);
$eczane = $eczaneStmt->fetch();
if (!$eczane) yonlendir(sayf('pharmacy/index.php'));
$eczaneId = $eczane['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resId = (int)($_POST['res_id'] ?? 0);
    $durum = $_POST['durum'] ?? '';
    $izinliDurumlar = ['onaylandi', 'reddedildi', 'teslim_edildi', 'iptal'];
    if ($resId && in_array($durum, $izinliDurumlar)) {
        $stmt = db()->prepare("UPDATE ayirtmalar SET durum = ? WHERE id = ? AND eczane_id = ?");
        $stmt->execute([$durum, $resId, $eczaneId]);
        flashMesajAyarla('basari', 'Rezervasyon durumu güncellendi.');
    }
    yonlendir(sayf('pharmacy/reservations.php'));
}

$baslik = 'Rezervasyon Yönetimi — ' . APP_NAME;

// Durum filtresi
$durumFiltre = $_GET['durum'] ?? 'tumu';
$neresi = '';
$params = [$eczaneId];
if (in_array($durumFiltre, ['beklemede', 'onaylandi', 'reddedildi', 'teslim_edildi', 'iptal'], true)) {
    $neresi = 'AND r.durum = ?';
    $params[] = $durumFiltre;
}

$stmt = db()->prepare("
    SELECT r.id,
           r.durum,
           r.notlar,
           r.olusturma_tarihi,
           k.ad    AS musteri_ad,
           k.soyad AS musteri_soyad,
           k.telefon AS musteri_telefon,
           i.ad    AS ilac_adi
    FROM ayirtmalar r
    JOIN kullanicilar k ON k.id = r.kullanici_id
    JOIN ilaclar      i ON i.id = r.ilac_id
    WHERE r.eczane_id = ? $neresi
    ORDER BY r.olusturma_tarihi DESC
");
$stmt->execute($params);
$rezervasyonlar = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('clock') ?> Rezervasyon Yönetimi</h1>
    </div>

    <!-- Durum filtreleri -->
    <div style="display:flex; gap:.75rem; margin-bottom:1.5rem; flex-wrap:wrap;">
        <?php foreach (['tumu' => 'Tümü', 'beklemede' => 'Beklemede', 'onaylandi' => 'Onaylı', 'teslim_edildi' => 'Teslim Edildi', 'reddedildi' => 'Reddedildi'] as $k => $v): ?>
            <a href="?durum=<?= $k ?>"
               class="btn <?= ($durumFiltre === $k) ? 'btn-birincil' : 'btn-gri' ?> btn-sm"
               style="border-radius:var(--yaricap-pill);">
                <?= $v ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="kart">
        <div class="tablo-sarici" style="border:none; box-shadow:none; border-radius:0;">
            <table class="tablo" id="rezTablosu">
                <thead>
                    <tr>
                        <th>Müşteri</th>
                        <th>İlaç</th>
                        <th>Tarih</th>
                        <th>Notlar</th>
                        <th>Durum</th>
                        <th style="text-align:right;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rezervasyonlar)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:3rem; color:var(--metin-uc);">
                                <?= svgIkon('clock') ?>
                                <br><br>Henüz rezervasyon bulunmuyor.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($rezervasyonlar as $r): ?>
                        <?php
                        $rozetSinif = match($r['durum']) {
                            'beklemede'     => 'rozet-sari',
                            'onaylandi'     => 'rozet-mavi',
                            'teslim_edildi' => 'rozet-yesil',
                            'reddedildi',
                            'iptal'         => 'rozet-kirmizi',
                            default         => 'rozet-gri',
                        };
                        $durumMetin = match($r['durum']) {
                            'beklemede'     => 'Beklemede',
                            'onaylandi'     => 'Onaylandı',
                            'teslim_edildi' => 'Teslim Edildi',
                            'reddedildi'    => 'Reddedildi',
                            'iptal'         => 'İptal',
                            default         => $r['durum'],
                        };
                        ?>
                        <tr>
                            <td>
                                <strong><?= e($r['musteri_ad'] . ' ' . $r['musteri_soyad']) ?></strong>
                                <?php if ($r['musteri_telefon']): ?>
                                    <br><small style="color:var(--metin-uc);"><?= e($r['musteri_telefon']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($r['ilac_adi']) ?></strong>
                            </td>
                            <td style="font-size:.875rem; color:var(--metin-ikincil);">
                                <?= date('d.m.Y', strtotime($r['olusturma_tarihi'])) ?>
                                <br><small><?= date('H:i', strtotime($r['olusturma_tarihi'])) ?></small>
                            </td>
                            <td style="font-size:.85rem; color:var(--metin-ikincil); max-width:200px;">
                                <?= $r['notlar'] ? e($r['notlar']) : '<span style="color:var(--metin-uc);">—</span>' ?>
                            </td>
                            <td>
                                <span class="rozet <?= $rozetSinif ?>"><?= $durumMetin ?></span>
                            </td>
                            <td style="text-align:right;">
                                <form method="post" style="display:inline-flex; gap:.35rem; flex-wrap:wrap; justify-content:flex-end;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                                    <?php if ($r['durum'] === 'beklemede'): ?>
                                        <button type="submit" name="durum" value="onaylandi"
                                                class="btn btn-ikincil btn-sm">
                                            <?= svgIkon('check') ?> Onayla
                                        </button>
                                        <button type="submit" name="durum" value="reddedildi"
                                                class="btn btn-tehlike btn-sm">
                                            <?= svgIkon('x') ?> Reddet
                                        </button>
                                    <?php elseif ($r['durum'] === 'onaylandi'): ?>
                                        <button type="submit" name="durum" value="teslim_edildi"
                                                class="btn btn-sm" style="background:var(--renk-basari); color:#fff; border-color:var(--renk-basari);">
                                            <?= svgIkon('check-circle') ?> Teslim Edildi
                                        </button>
                                        <button type="submit" name="durum" value="iptal"
                                                class="btn btn-gri btn-sm">
                                            <?= svgIkon('x') ?> İptal
                                        </button>
                                    <?php else: ?>
                                        <span style="color:var(--metin-uc); font-size:.8rem;">İşlem yok</span>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
