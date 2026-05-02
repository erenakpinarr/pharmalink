<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('kullanici');
$baslik = 'Kullanıcı Paneli — ' . APP_NAME;
$kid = mevcutKullaniciId();

$rezCount = db()->prepare("SELECT COUNT(*) FROM ayirtmalar WHERE kullanici_id = ?");
$rezCount->execute([$kid]);
$toplamRezervasyon = $rezCount->fetchColumn();

$favCount = db()->prepare("SELECT COUNT(*) FROM favoriler WHERE kullanici_id = ?");
$favCount->execute([$kid]);
$toplamFavori = $favCount->fetchColumn();

$sonRezervasyonlar = db()->prepare("
    SELECT a.*, e.eczane_adi, i.ad as ilac_adi 
    FROM ayirtmalar a
    JOIN eczaneler e ON a.eczane_id = e.id
    JOIN ilaclar i ON a.ilac_id = i.id
    WHERE a.kullanici_id = ? 
    ORDER BY a.olusturma_tarihi DESC LIMIT 5
");
$sonRezervasyonlar->execute([$kid]);
$rezervasyonlar = $sonRezervasyonlar->fetchAll();

$sonFavoriler = db()->prepare("
    SELECT f.*, i.ad AS ilac_adi, k.ad AS kategori_adi
    FROM favoriler f
    JOIN ilaclar i ON f.ilac_id = i.id
    JOIN kategoriler k ON k.id = i.kategori_id
    WHERE f.kullanici_id = ?
    ORDER BY f.olusturma DESC LIMIT 4
");
$sonFavoriler->execute([$kid]);
$favoriler = $sonFavoriler->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('grid') ?> Kullanıcı Paneli</h1>
        <a href="profile.php" class="btn btn-gri btn-sm" style="border-radius:4px;"><?= svgIkon('settings') ?> Profil &
            Ayarlar</a>
    </div>

    <!-- Hızlı İşlemler Kartı (Flat design style) -->
    <div class="kart mb-2"
        style="padding:1.5rem; display:flex; gap:1rem; align-items:center; background:var(--arkaplan-kart); border-left:4px solid var(--renk-birincil);">
        <h3
            style="margin:0; font-size:1.1rem; color:var(--metin-birincil); display:flex; align-items:center; gap:0.5rem; white-space:nowrap;">
            <?= svgIkon('activity') ?> Hızlı Erişim:
        </h3>
        <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            <a href="favorites.php" class="btn btn-birincil btn-sm"><?= svgIkon('heart') ?> Favori İlaçlar</a>
            <a href="reservations.php" class="btn btn-ikincil btn-sm"><?= svgIkon('clock') ?> Rezervasyonlarım</a>
        </div>
    </div>

    <!-- İstatistikler -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;margin-bottom:1.5rem;">
        <div class="stat-kart">
            <div class="stat-ikon"><?= svgIkon('pill') ?></div>
            <div class="stat-icerik">
                <div class="stat-sayi"><?= number_format($toplamRezervasyon) ?></div>
                <div class="stat-etiket">Toplam Rezervasyon</div>
            </div>
        </div>
        <div class="stat-kart">
            <div class="stat-ikon red-bg" style="color:var(--renk-tehlike); background:var(--renk-tehlike-bg);">
                <?= svgIkon('heart') ?>
            </div>
            <div class="stat-icerik">
                <div class="stat-sayi"><?= number_format($toplamFavori) ?></div>
                <div class="stat-etiket">Favori İlaçlar</div>
            </div>
        </div>
    </div>

    <!-- Son Rezervasyonlar ve Favoriler -->
    <div style="display:grid;grid-template-columns:1fr 1.2fr;gap:1.5rem;align-items:start;">
        <div class="kart" style="margin:0;">
            <div class="kart-baslik">
                <h2 style="margin:0;"><?= svgIkon('heart') ?> Favori İlaçlarım</h2>
                <a href="favorites.php" class="btn btn-gri btn-sm">Tümü</a>
            </div>
            <?php if (empty($favoriler)): ?>
                <div class="bos-durum" style="padding:2rem;">
                    <div class="stat-ikon mb-1" style="width:40px;height:40px;"><?= svgIkon('pill') ?></div>
                    <p>Henüz favori ilacınız yok.</p>
                </div>
            <?php else: ?>
                <div style="padding:1rem;">
                    <?php foreach ($favoriler as $f): ?>
                        <div style="padding:1rem; border:1px solid var(--kenar-rengi); border-radius:4px; margin-bottom:0.75rem; background:var(--arkaplan); display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h4 style="margin:0 0 0.2rem 0; font-size:1rem;"><?= e($f['ilac_adi']) ?></h4>
                                <span class="rozet rozet-mavi" style="font-size:0.7rem;"><?= e($f['kategori_adi']) ?></span>
                            </div>
                            <a href="find_pharmacy.php?ilac_id=<?= $f['ilac_id'] ?>" class="btn btn-birincil btn-sm"><?= svgIkon('search') ?> Bul</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="kart" style="margin:0;">
            <div class="kart-baslik">
                <h2 style="margin:0;"><?= svgIkon('clock') ?> Son Rezervasyonlar</h2>
                <a href="reservations.php" class="btn btn-gri btn-sm">Tümü</a>
            </div>
            <?php if (empty($rezervasyonlar)): ?>
                <div class="bos-durum" style="padding:2.5rem 1.5rem;">
                    <div class="stat-ikon mb-1" style="width:48px;height:48px;font-size:1.5rem;"><?= svgIkon('info') ?>
                    </div>
                    <h3>Rezervasyon Bulunamadı</h3>
                </div>
            <?php else: ?>
                <div class="tablo-sarici">
                    <table class="tablo" style="margin:0;">
                        <thead>
                            <tr>
                                <th>İlaç</th>
                                <th>Eczane</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rezervasyonlar as $r): ?>
                                <?php
                                $durumEtiketi = match ($r['durum']) {
                                    'beklemede' => '<span class="rozet rozet-sari">Beklemede</span>',
                                    'onaylandi' => '<span class="rozet rozet-yesil">Onaylandı</span>',
                                    'reddedildi' => '<span class="rozet rozet-kirmizi">Reddedildi</span>',
                                    default => '<span class="rozet rozet-gri">Bilinmiyor</span>'
                                };
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= e($r['ilac_adi']) ?></strong><br>
                                        <small
                                            style="color:var(--metin-uc);"><?= date('d.m.Y', strtotime($r['olusturma_tarihi'])) ?></small>
                                    </td>
                                    <td><?= e($r['eczane_adi']) ?></td>
                                    <td><?= $durumEtiketi ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>