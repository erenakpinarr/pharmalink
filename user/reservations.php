<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('kullanici');

$baslik = 'Rezervasyonlarım — ' . APP_NAME;
$kid = mevcutKullaniciId();

// Rezervasyonları getir
$stmt = db()->prepare("
    SELECT r.*, e.eczane_adi, e.telefon AS eczane_tel, i.ad AS ilac_adi
    FROM ayirtmalar r
    JOIN eczaneler e ON e.id = r.eczane_id
    JOIN ilaclar i ON i.id = r.ilac_id
    WHERE r.kullanici_id = ?
    ORDER BY r.olusturma_tarihi DESC
");
$stmt->execute([$kid]);
$reservations = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('activity') ?> Rezervasyonlarım</h1>
    </div>

    <?php if (empty($reservations)): ?>
        <div class="kart" style="padding: 5rem 2rem; text-align: center; background: white; border-radius: var(--yaricap-lg); border: 1px solid var(--kenar-rengi);">
            <div style="font-size: 4rem; color: var(--metin-uc); margin-bottom: 1.5rem;"><?= svgIkon('clock') ?></div>
            <h2 style="color: var(--metin-birincil); margin-bottom: 0.5rem;">Henüz bir rezervasyonunuz yok</h2>
            <p style="color: var(--metin-ikincil); max-width: 500px; margin: 0 auto 2rem;">
                İhtiyacınız olan ilaçları eczanelerden ayırtarak kendinize ayırabilirsiniz. 
            </p>
            <a href="index.php" class="btn btn-birincil">İlaç Bulmaya Başla</a>
        </div>
    <?php else: ?>
        <div class="tablo-sarici">
            <table class="tablo">
                <thead>
                    <tr>
                        <th>İlaç</th>
                        <th>Eczane</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th style="text-align: right;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td><strong><?= e($r['ilac_adi']) ?></strong></td>
                            <td>
                                <?= e($r['eczane_adi']) ?><br>
                                <small style="color: var(--metin-uc);"><?= e($r['eczane_tel']) ?></small>
                            </td>
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
                            <td><?= date('d.m.Y H:i', strtotime($r['olusturma_tarihi'])) ?></td>
                            <td style="text-align: right;">
                                <a href="eczane_detay.php?id=<?= $r['eczane_id'] ?>" class="btn btn-gri btn-sm">Eczaneye Git</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
