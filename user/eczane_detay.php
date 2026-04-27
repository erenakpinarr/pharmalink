<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
girisKontrol();
$eczaneId = (int)($_GET['id'] ?? 0);
if (!$eczaneId) yonlendir(sayf('user/index.php'));
$stmt = db()->prepare(
    "SELECT e.*, k.ad AS yetkili_ad, k.soyad AS yetkili_soyad
     FROM eczaneler e
     JOIN kullanicilar k ON k.id = e.kullanici_id
     WHERE e.id = ? AND e.durum = 'onaylandi'
     LIMIT 1"
);
$stmt->execute([$eczaneId]);
$eczane = $stmt->fetch();
if (!$eczane) {
    flashMesajAyarla('tehlike', 'Eczane bulunamadı veya onaylanmamış.');
    yonlendir(sayf('user/index.php'));
}

$stokStmt = db()->prepare(
    "SELECT s.durum, i.ad AS ilac_adi, i.etken_madde, k.ad AS kategori_adi
     FROM stok s
     JOIN ilaclar i ON i.id = s.ilac_id
     JOIN kategoriler k ON k.id = i.kategori_id
     WHERE s.eczane_id = ? AND i.aktif = 1
     ORDER BY s.durum ASC, k.ad, i.ad"
);
$stokStmt->execute([$eczaneId]);
$stoklar = $stokStmt->fetchAll();

$favIlaclar = [];
if (mevcutRol() === 'kullanici') {
    $favStmt = db()->prepare(
        "SELECT ilac_id FROM favoriler WHERE kullanici_id=?"
    );
    $favStmt->execute([mevcutKullaniciId()]);
    $favIlaclar = $favStmt->fetchAll(PDO::FETCH_COLUMN);
}
$baslik = e($eczane['eczane_adi']) . ' — ' . APP_NAME;
?>

<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=geometry&language=tr&v=weekly&callback=initMap" defer></script>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="ana-icerik">
    <div style="margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between;">
        <a href="javascript:history.back()" class="btn btn-gri" style="border-radius: var(--yaricap-sm); padding: 0.5rem 1rem;">
            <?= svgIkon('arrow-left') ?> Geri Dön
        </a>
        <div style="display: flex; gap: 0.75rem;">
            <?php if($eczane['telefon']): ?>
                <a href="tel:<?= e($eczane['telefon']) ?>" class="btn btn-birincil" style="border-radius: var(--yaricap-sm);">
                    <?= svgIkon('phone') ?> Hemen Ara
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($eczane['nobetci']): ?>
    <div style="margin-bottom: 2rem; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: var(--yaricap); padding: 1.25rem; display: flex; align-items: center; gap: 1rem; font-weight: 700; box-shadow: var(--golge-hafif);">
        <div style="background: #22c55e; color: #fff; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <?= svgIkon('bell') ?>
        </div>
        <div style="font-size: 1.1rem; font-family: 'Outfit', sans-serif;">
            Bu Eczane Şu An Nöbetçidir ve Açıktır
        </div>
    </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
        <div class="kart" style="border-radius: var(--yaricap-lg); border: 1px solid var(--kenar-rengi); overflow: hidden; background: white; box-shadow: var(--golge-orta);">
            <div style="background: var(--arkaplan-hover); padding: 1.5rem 2rem; border-bottom: 1px solid var(--kenar-rengi);">
                <h2 style="margin: 0; font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--metin-birincil); display: flex; align-items: center; gap: 0.75rem;">
                    <?= svgIkon('building', ['style' => 'color: var(--renk-birincil);']) ?> 
                    Eczane Bilgileri
                </h2>
            </div>
            <div class="kart-govde" style="padding: 2.5rem;">
                <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; margin-bottom: 1.5rem; color: var(--metin-birincil); font-weight: 800;"><?= e($eczane['eczane_adi']) ?></h1>
                
                <?php if($eczane['vitrin_aktif'] && $eczane['slug']): ?>
                    <a href="<?= sayf('p/' . $eczane['slug']) ?>" target="_blank" class="btn" style="width: 100%; margin-bottom: 2rem; justify-content: center; gap: 0.75rem; background: linear-gradient(135deg, #0d9488, #0f766e); border: none; color: white; font-weight: 700; padding: 1rem; border-radius: var(--yaricap);">
                        <?= svgIkon('eye') ?> Premium Vitrini Ziyaret Et
                    </a>
                <?php endif; ?>

                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 40px; height: 40px; background: var(--arkaplan-hover); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--metin-uc); flex-shrink: 0;">
                            <?= svgIkon('map-pin') ?>
                        </div>
                        <div style="font-size: 1rem; line-height: 1.6;">
                            <strong style="color: var(--metin-birincil); font-family: 'Outfit', sans-serif;"><?= e($eczane['sehir']) ?> / <?= e($eczane['ilce']) ?></strong><br>
                            <span style="color: var(--metin-ikincil);"><?= e($eczane['adres']) ?></span>
                        </div>
                    </div>

                    <?php if ($eczane['telefon']): ?>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div style="width: 40px; height: 40px; background: var(--arkaplan-hover); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--metin-uc); flex-shrink: 0;">
                            <?= svgIkon('phone') ?>
                        </div>
                        <a href="tel:<?= e($eczane['telefon']) ?>" style="font-size: 1.25rem; font-weight: 800; color: var(--renk-birincil); text-decoration: none; font-family: 'Outfit', sans-serif;">
                            <?= e($eczane['telefon']) ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div style="width: 40px; height: 40px; background: var(--arkaplan-hover); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--metin-uc); flex-shrink: 0;">
                            <?= svgIkon('clock') ?>
                        </div>
                        <span style="color: var(--metin-ikincil); font-size: 1rem;">
                            Çalışma Saatleri: <strong style="color: var(--metin-birincil);"><?= e($eczane['calisma_saatleri'] ?? 'Belirtilmemiş') ?></strong>
                        </span>
                    </div>
                </div>

                <?php if ($eczane['harita_linki'] || ($eczane['enlem'] && $eczane['boylam'])): ?>
                <?php $mapUrl = ($eczane['enlem'] && $eczane['boylam'])
                    ? "https://maps.google.com/?q={$eczane['enlem']},{$eczane['boylam']}"
                    : $eczane['harita_linki']; ?>
                <a href="<?= e($mapUrl) ?>" target="_blank" class="btn btn-ikincil" style="width: 100%; justify-content: center; padding: 1rem; margin-top: 2rem; border-radius: var(--yaricap);">
                    <?= svgIkon('navigation') ?> Google Maps'te Yol Tarifi Al
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="kart" style="border-radius: var(--yaricap-lg); border: 1px solid var(--kenar-rengi); overflow: hidden; background: white; box-shadow: var(--golge-orta); display: flex; flex-direction: column;">
            <div style="background: var(--arkaplan-hover); padding: 1.5rem 2rem; border-bottom: 1px solid var(--kenar-rengi);">
                <h2 style="margin: 0; font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--metin-birincil); display: flex; align-items: center; gap: 0.75rem;">
                    <?= svgIkon('map', ['style' => 'color: var(--renk-birincil);']) ?> 
                    Konum Görünümü
                </h2>
            </div>
            <div style="flex: 1; min-height: 400px; position: relative;">
                <?php if ($eczane['enlem'] && $eczane['boylam']): ?>
                <div id="detayMap" style="position: absolute; inset: 0; width: 100%; height: 100%;"></div>
                <?php else: ?>
                <div style="height: 100%; display: flex; align-items: center; justify-content: center; background: var(--arkaplan-hover);">
                    <div style="text-align: center; color: var(--metin-uc); padding: 3rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.2;"><?= svgIkon('map-pin') ?></div>
                        <p style="font-weight: 700; font-family: 'Outfit', sans-serif;">Koordinat bilgisi eksik</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function initMap() {
    const lat = <?= (float)$eczane['enlem'] ?>;
    const lng = <?= (float)$eczane['boylam'] ?>;
    if (!lat || !lng) return;
    const loc = { lat, lng };
    const map = new google.maps.Map(document.getElementById("detayMap"), {
        center: loc,
        zoom: 16,
        styles: [
            { "featureType": "poi", "stylers": [{ "visibility": "off" }] },
            { "featureType": "transit", "stylers": [{ "visibility": "off" }] }
        ],
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
    });
    new google.maps.Marker({
        position: loc,
        map: map,
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 12,
            fillColor: '#0d9488',
            fillOpacity: 1,
            strokeColor: '#fff',
            strokeWeight: 4
        },
        title: '<?= e($eczane['eczane_adi']) ?>'
    });
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
