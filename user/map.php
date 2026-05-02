<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('kullanici');
$baslik = 'Eczane Bul & Harita — ' . APP_NAME;
$arananIlac  = trim($_GET['ara'] ?? '');
$aramaYapildi = !empty($arananIlac);
$userLat = isset($_GET['lat']) && $_GET['lat'] !== '' ? (float)$_GET['lat'] : null;
$userLng = isset($_GET['lng']) && $_GET['lng'] !== '' ? (float)$_GET['lng'] : null;
$kid = mevcutKullaniciId();
$kul = db()->prepare("SELECT sehir, ilce FROM kullanicilar WHERE id = ?");
$kul->execute([$kid]);
$kul = $kul->fetch();
$userProfileCity = $kul['sehir'] ?? '';
$userProfileDistrict = $kul['ilce'] ?? '';

if ($userLat && $userLng) {
    $mesafeSql = "(6371 * acos(cos(radians(?)) * cos(radians(e.enlem)) * cos(radians(e.boylam) - radians(?)) + sin(radians(?)) * sin(radians(e.enlem))))";
    if ($aramaYapildi) {
        $stmt = db()->prepare("
            SELECT e.id, e.eczane_adi, e.adres, e.sehir, e.ilce, e.telefon, e.enlem, e.boylam, e.nobetci,
                   MAX(CASE WHEN i.ad LIKE ? AND s.durum = 'mevcut' THEN 1 ELSE 0 END) AS has_stock,
                   $mesafeSql AS mesafe
            FROM eczaneler e
            LEFT JOIN stok s ON s.eczane_id = e.id
            LEFT JOIN ilaclar i ON s.ilac_id = i.id
            WHERE e.durum = 'onaylandi' AND e.enlem IS NOT NULL AND e.boylam IS NOT NULL
            GROUP BY e.id, e.eczane_adi, e.adres, e.sehir, e.ilce, e.telefon, e.enlem, e.boylam, e.nobetci
            ORDER BY has_stock DESC, mesafe ASC
        ");
        $stmt->execute(["%$arananIlac%", $userLat, $userLng, $userLat]);
    } else {
        $stmt = db()->prepare("
            SELECT e.id, e.eczane_adi, e.adres, e.sehir, e.ilce, e.telefon, e.enlem, e.boylam, e.nobetci,
                   0 AS has_stock,
                   $mesafeSql AS mesafe
            FROM eczaneler e
            WHERE e.durum = 'onaylandi' AND e.enlem IS NOT NULL AND e.boylam IS NOT NULL
        ");
        $stmt->execute([$userLat, $userLng, $userLat]);
    }
} else {
    if ($aramaYapildi) {
        $stmt = db()->prepare("
            SELECT e.id, e.eczane_adi, e.adres, e.sehir, e.ilce, e.telefon, e.enlem, e.boylam, e.nobetci,
                   MAX(CASE WHEN i.ad LIKE ? AND s.durum = 'mevcut' THEN 1 ELSE 0 END) AS has_stock,
                   NULL AS mesafe
            FROM eczaneler e
            LEFT JOIN stok s ON s.eczane_id = e.id
            LEFT JOIN ilaclar i ON s.ilac_id = i.id
            WHERE e.durum = 'onaylandi' AND e.enlem IS NOT NULL AND e.boylam IS NOT NULL
            GROUP BY e.id, e.eczane_adi, e.adres, e.sehir, e.ilce, e.telefon, e.enlem, e.boylam, e.nobetci
            ORDER BY has_stock DESC, e.nobetci DESC
        ");
        $stmt->execute(["%$arananIlac%"]);
    } else {
        $stmt = db()->prepare("
            SELECT id, eczane_adi, adres, sehir, ilce, telefon, enlem, boylam, nobetci,
                   0 AS has_stock, NULL AS mesafe
            FROM eczaneler
            WHERE durum='onaylandi' AND enlem IS NOT NULL AND boylam IS NOT NULL
        ");
        $stmt->execute();
    }
}
$eczanelerDB = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($eczanelerDB as &$ecz) {
    if (!$aramaYapildi) {
        $stokStmt = db()->prepare("SELECT COUNT(*) FROM stok WHERE eczane_id = ? AND durum='mevcut'");
        $stokStmt->execute([$ecz['id']]);
        $ecz['stok_sayisi'] = $stokStmt->fetchColumn();
    } else {
        $ecz['stok_sayisi'] = 0;
    }
}
unset($ecz);
$haritaJson = json_encode($eczanelerDB, JSON_UNESCAPED_UNICODE);
include __DIR__ . '/../includes/header.php';
?>
<style>
.map-split-container {
    display: flex;
    flex-direction: row;
    height: calc(100vh - var(--navbar-yukseklik) - 2rem);
    gap: 1.5rem;
    margin: -1rem 0;
}
.map-section {
    flex: 7;
    position: relative;
    border-radius: var(--yaricap-lg);
    overflow: hidden;
    box-shadow: var(--golge-orta);
    border: 1px solid var(--kenar-rengi);
}
#eczaneMap { width: 100%; height: 100%; }
.list-section {
    flex: 5;
    background: var(--arkaplan-kart);
    border-radius: var(--yaricap-lg);
    box-shadow: var(--golge-hafif);
    border: 1px solid var(--kenar-rengi);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.list-header {
    padding: 2rem;
    border-bottom: 1px solid var(--kenar-rengi);
    background: white;
    flex-shrink: 0;
}
.list-items {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    background: var(--arkaplan-ikincil);
}
.pharmacy-list-item {
    padding: 1.5rem;
    border-radius: var(--yaricap);
    background: white;
    border: 1px solid var(--kenar-rengi);
    transition: var(--gecis);
    display: flex; flex-direction: column; gap: 1rem;
    cursor: pointer;
}
.pharmacy-list-item:hover {
    transform: translateY(-3px);
    box-shadow: var(--golge-orta);
    border-color: var(--renk-birincil);
}
.sys-label {
    font-size: 0.75rem; font-weight: 800;
    background: var(--renk-birincil-acik);
    color: var(--renk-birincil); padding: 0.3rem 0.75rem; border-radius: 6px;
    display: inline-flex; align-items: center; gap: 0.3rem;
    text-transform: uppercase; letter-spacing: 0.02em;
}
.ext-label {
    font-size: 0.75rem; font-weight: 800;
    background: #f1f5f9; color: #64748b;
    padding: 0.3rem 0.75rem; border-radius: 6px;
    display: inline-flex; align-items: center; gap: 0.3rem;
    text-transform: uppercase; letter-spacing: 0.02em;
}
.loc-loading {
    position: absolute; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.75); z-index: 9999;
    display: none; flex-direction: column;
    align-items: center; justify-content: center;
    color: white; border-radius: var(--yaricap-lg);
    gap: 1rem;
}
.route-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(0,0,0,0.65);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    align-items: stretch;
    justify-content: center;
    padding: 1rem;
    animation: fadeOverlay 0.3s ease;
}
.route-modal-overlay.open { display: flex; }
@keyframes fadeOverlay {
    from { opacity: 0; }
    to { opacity: 1; }
}
.route-modal {
    background: var(--arkaplan-kart);
    border-radius: 24px;
    border: 1px solid var(--kenar-rengi);
    box-shadow: 0 40px 100px -20px rgba(0,0,0,0.5);
    width: 100%;
    max-width: 1350px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUpModal 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes slideUpModal {
    from { opacity: 0; transform: translateY(60px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.route-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--kenar-rengi);
    flex-shrink: 0;
    background: var(--arkaplan-hover);
}
.route-modal-header h3 {
    font-size: 1.25rem;
    font-weight: 800;
    display: flex; align-items: center; gap: 0.75rem;
    color: var(--metin-birincil);
    margin: 0;
}
.route-modal-close {
    background: none; border: none;
    width: 40px; height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--metin-ikincil);
    transition: all 0.2s ease;
    font-size: 1.5rem;
}
.route-modal-close:hover {
    background: var(--arkaplan);
    color: var(--metin-birincil);
}
.route-modal-body {
    display: flex;
    flex: 1;
    overflow: hidden;
    min-height: 0;
}
#routeMap {
    flex: 1.6;
    min-height: 450px;
    border-right: 1px solid var(--kenar-rengi);
}
.route-sidebar {
    flex: 1;
    min-width: 320px;
    max-width: 400px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.travel-mode-tabs {
    display: flex;
    border-bottom: 1px solid var(--kenar-rengi);
    background: var(--arkaplan-hover);
    flex-shrink: 0;
}
.travel-mode-btn {
    flex: 1;
    padding: 1rem 0.5rem;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex; flex-direction: column; align-items: center; gap: 0.35rem;
    font-size: 0.75rem; font-weight: 700;
    color: var(--metin-uc);
    border-bottom: 3px solid transparent;
    transition: all 0.25s ease;
}
.travel-mode-btn.active {
    color: var(--renk-ikincil);
    border-bottom-color: var(--renk-ikincil);
    background: var(--arkaplan-kart);
}
.travel-mode-btn svg { width: 22px; height: 22px; }
.route-summary {
    padding: 1.5rem;
    border-bottom: 1px solid var(--kenar-rengi);
    flex-shrink: 0;
}
.route-summary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.route-stat {
    background: var(--arkaplan);
    border: 1px solid var(--kenar-rengi);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
}
.route-stat .val {
    font-size: 1.75rem; font-weight: 900; line-height: 1;
    color: var(--renk-ikincil);
}
.route-stat .lbl {
    font-size: 0.75rem; font-weight: 700; color: var(--metin-uc);
    text-transform: uppercase; margin-top: 0.35rem;
}
.route-steps-container {
    flex: 1;
    overflow-y: auto;
    padding: 0;
}
.route-steps-header {
    padding: 1rem 1.5rem 0.75rem;
    font-size: 0.8rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.08em;
    color: var(--metin-uc);
    border-bottom: 1px solid var(--kenar-rengi);
    background: var(--arkaplan-hover);
    position: sticky; top: 0; z-index: 2;
}
.route-step {
    display: flex; align-items: flex-start; gap: 1rem;
    padding: 0.9rem 1.5rem;
    border-bottom: 1px solid var(--kenar-rengi);
    cursor: pointer;
    transition: background 0.2s ease;
}
.route-step:hover { background: var(--arkaplan-hover); }
.route-step:last-child { border-bottom: none; }
.step-num {
    width: 28px; height: 28px; min-width: 28px;
    border-radius: 2px;
    background: var(--renk-ikincil);
    color: #fff; font-size: 0.7rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    margin-top: 2px;
}
.step-text {
    font-size: 0.875rem; line-height: 1.5;
    color: var(--metin-birincil);
    flex: 1;
}
.step-dist {
    font-size: 0.75rem; font-weight: 700;
    color: var(--metin-uc); white-space: nowrap;
    margin-top: 4px;
}
.route-modal-footer {
    padding: 1.25rem 2rem;
    border-top: 1px solid var(--kenar-rengi);
    display: flex; align-items: center; justify-content: space-between;
    background: var(--arkaplan-hover);
    flex-shrink: 0;
}
.route-dest-info { 
    display: flex; flex-direction: column; gap: 0.2rem;
}
.route-dest-info strong {
    font-size: 1rem; font-weight: 800;
    color: var(--metin-birincil);
}
.route-dest-info span {
    font-size: 0.8rem; color: var(--metin-uc);
}
.route-footer-btns { display: flex; gap: 0.75rem; }
.route-modal-loading {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 1.5rem; padding: 4rem;
    flex: 1;
}
@media (max-width: 1024px) {
    .route-modal-body { flex-direction: column; }
    #routeMap { flex: none; min-height: 300px; border-right: none; border-bottom: 1px solid var(--kenar-rengi); }
    .route-sidebar { flex: none; max-width: 100%; }
}
@media (max-width: 992px) {
    .map-split-container { flex-direction: column; height: auto; }
    .map-section { height: 400px; flex: none; }
    .list-section { height: 600px; flex: none; border-radius: 12px; }
}
</style>
<main class="ana-icerik" style="padding:1rem 2rem;">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places,geometry&language=tr&v=weekly&loading=async" async defer></script>
    <div class="sayfa-baslik" style="margin-bottom:1.5rem;">
        <h1 style="font-family: 'Outfit', sans-serif; font-weight: 800;"><?= svgIkon('map') ?> Eczane Keşfet</h1>
        <div style="display:flex;gap:1rem;">
            <a href="favorites.php" class="btn btn-cizgili btn-sm"><?= svgIkon('heart') ?> Favoriler</a>
        </div>
    </div>
    <div class="map-split-container">
        
        <div class="map-section">
            <div class="loc-loading" id="locLoading">
                <div class="spinner" style="width:50px;height:50px;border-width:4px;border-top-color:var(--renk-ikincil);"></div>
                <h3 id="locLoadingText">Konum Alınıyor...</h3>
            </div>
            <div id="eczaneMap"></div>
        </div>
        
        <div class="list-section">
            <div class="list-header">
                <h3 style="margin-bottom:0.5rem;font-size:1.15rem;display:flex;align-items:center;gap:0.5rem;">
                    <span id="listTitle"><?= $aramaYapildi ? '"'.htmlspecialchars($arananIlac).'" Sonuçları' : 'Kayıtlı Eczaneler' ?></span>
                    <span class="rozet rozet-yesil" id="resultCount" style="font-size:0.8rem;">0</span>
                </h3>
                <form id="publicSearchForm" action="" method="get" class="search-wrapper">
                    <div style="position:relative;display:flex;gap:0.5rem;">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--metin-uc);"><?= svgIkon('search') ?></span>
                        <input type="text" name="ara" id="drugSearchInput" class="form-giris"
                            style="padding-left:2.5rem;border-radius:var(--yaricap-pill);height:45px;"
                            placeholder="İlaç ara (Örn: Parol)" autocomplete="off"
                            value="<?= htmlspecialchars($arananIlac) ?>">
                        <input type="hidden" name="lat" id="latInput" value="<?= e($userLat ?? '') ?>">
                        <input type="hidden" name="lng" id="lngInput" value="<?= e($userLng ?? '') ?>">
                        <button type="submit" class="btn btn-ikincil" style="border-radius:var(--yaricap-pill);">Bul</button>
                    </div>
                    <div class="autocomplete-list" id="autocompleteList"></div>
                </form>
            </div>
            <div class="list-items" id="pharmacyList"></div>
        </div>
    </div>
    <div class="route-modal-overlay" id="routeModal" role="dialog" aria-modal="true" aria-labelledby="routeModalTitle">
        <div class="route-modal">
            
            <div class="route-modal-header">
                <h3 id="routeModalTitle">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                    Rota Planlayıcı
                </h3>
                <div style="display:flex;align-items:center;gap:1rem;">
                    <button class="btn btn-ikincil btn-sm" id="shareRouteBtn" style="display:none;" title="Google Maps'te Aç">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        Maps'te Aç
                    </button>
                    <button class="route-modal-close" id="closeRouteModal" aria-label="Kapat">✕</button>
                </div>
            </div>
            
            <div class="route-modal-body">
                
                <div id="routeMap"></div>
                
                <div class="route-sidebar">
                    
                    <div class="travel-mode-tabs" id="travelModeTabs">
                        <button class="travel-mode-btn active" data-mode="DRIVING" title="Araçla">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                            Araç
                        </button>
                        <button class="travel-mode-btn" data-mode="WALKING" title="Yürüyerek">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="1"/><path d="m9 20 3-6 3 3 1-8"/><path d="m6.5 17.5 1.5-3"/><path d="m14 14 1.5 3.5"/></svg>
                            Yürüyüş
                        </button>
                        <button class="travel-mode-btn" data-mode="TRANSIT" title="Toplu Taşıma">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="16" rx="2"/><path d="M9 19v2"/><path d="M15 19v2"/><path d="M4 11h16"/><path d="M9 7h6"/></svg>
                            Taşıma
                        </button>
                    </div>
                    
                    <div class="route-modal-loading" id="routeCalcLoading">
                        <div class="spinner" style="width:44px;height:44px;border-width:4px;border-top-color:var(--renk-ikincil);"></div>
                        <span style="color:var(--metin-ikincil);font-weight:600;">Rota hesaplanıyor...</span>
                    </div>
                    
                    <div id="routeSummarySection" style="display:none;">
                        <div class="route-summary">
                            <div class="route-summary-grid">
                                <div class="route-stat">
                                    <div class="val" id="routeDuration">—</div>
                                    <div class="lbl">Süre</div>
                                </div>
                                <div class="route-stat">
                                    <div class="val" id="routeDistance">—</div>
                                    <div class="lbl">Mesafe</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="route-steps-container">
                            <div class="route-steps-header">📍 Adım Adım Yol Tarifi</div>
                            <div id="routeStepsList"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="route-modal-footer">
                <div class="route-dest-info">
                    <strong id="routeDestName">Hedef Eczane</strong>
                    <span id="routeDestAddress" style="max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                </div>
                <div class="route-footer-btns">
                    <button class="btn btn-gri btn-sm" id="clearRouteBtn">
                        <?= svgIkon('x') ?> Rotayı Sil
                    </button>
                    <a href="#" class="btn btn-birincil btn-sm" id="openGoogleMapsBtn" target="_blank" rel="noopener">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        Google Maps'te Aç
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>
<script>

let map, infoWindow;
let routeMap, routeDirectionsService, routeDirectionsRenderer;
let eczanelerDB = <?= $haritaJson ?>;
let userProfileCity = "<?= e($userProfileCity) ?>";
let userProfileDistrict = "<?= e($userProfileDistrict) ?>";
let aramaYapildi = <?= $aramaYapildi ? 'true' : 'false' ?>;
let arananIlac = "<?= htmlspecialchars($arananIlac) ?>";
let tumEczanelerListesi = [];
let markers = [];
let userLocation = <?= ($userLat && $userLng) ? "{lat: $userLat, lng: $userLng}" : "null" ?>;
let currentRouteTarget = null;
let currentTravelMode = 'DRIVING';

eczanelerDB.forEach(e => {
    tumEczanelerListesi.push({
        source: 'internal',
        id: e.id,
        name: e.eczane_adi,
        address: e.adres + ' - ' + e.ilce + '/' + e.sehir,
        lat: parseFloat(e.enlem),
        lng: parseFloat(e.boylam),
        phone: e.telefon,
        isOnduty: e.nobetci == "1",
        hasStock: parseInt(e.has_stock) === 1,
        stockCount: parseInt(e.stok_sayisi || 0),
        distance: e.mesafe !== null ? parseFloat(e.mesafe) : null
    });
});

async function initMap() {
    let centerLoc = userLocation || { lat: 39.0, lng: 35.0 };
    let zoomLevel = userLocation ? 13 : 6;

    if (!userLocation && userProfileCity) {
        const geocoder = new google.maps.Geocoder();
        const address = `${userProfileDistrict} ${userProfileCity}, Türkiye`;
        geocoder.geocode({ address }, (results, status) => {
            if (status === 'OK' && results[0]) {
                const cityLoc = results[0].geometry.location;
                map.setCenter(cityLoc);
                map.setZoom(13);
                searchGooglePlaces(cityLoc.lat(), cityLoc.lng());
            }
        });
    }
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const mapStyles = isDark ? [
        { elementType: 'geometry', stylers: [{ color: '#1d2235' }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#8ec3b9' }] },
        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#171d33' }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#38414e' }] },
        { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#212a37' }] },
        { featureType: 'poi', stylers: [{ visibility: 'off' }] },
        { featureType: 'transit', stylers: [{ visibility: 'off' }] }
    ] : [
        { featureType: 'poi', stylers: [{ visibility: 'off' }] },
        { featureType: 'transit', stylers: [{ visibility: 'off' }] },
        { featureType: 'administrative', stylers: [{ visibility: 'simplified' }] }
    ];
    map = new google.maps.Map(document.getElementById('eczaneMap'), {
        center: centerLoc,
        zoom: zoomLevel,
        styles: mapStyles,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
    });
    infoWindow = new google.maps.InfoWindow();
    if (userLocation) {

        new google.maps.Marker({
            position: userLocation, map: map,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 10,
                fillColor: '#3b82f6', fillOpacity: 1,
                strokeColor: '#fff', strokeWeight: 3
            },
            title: 'Sizin Konumunuz',
            zIndex: 999
        });
        searchGooglePlaces(userLocation.lat, userLocation.lng);
    } else {
        renderMarkers();
        renderList();
    }
}

async function searchGooglePlaces(lat, lng) {
    const { Place } = await google.maps.importLibrary('places');
    const request = {
        fields: ['displayName', 'location', 'formattedAddress', 'id'],
        locationRestriction: { center: { lat, lng }, radius: 4000 },
        includedPrimaryTypes: ['pharmacy']
    };
    try {
        const { places } = await Place.searchNearby(request);
        if (places && places.length > 0) {
            places.forEach(place => {
                if (!place.location) return;
                const isDuplicate = tumEczanelerListesi.some(listed =>
                    listed.source === 'internal' &&
                    google.maps.geometry.spherical.computeDistanceBetween(
                        place.location,
                        new google.maps.LatLng(listed.lat, listed.lng)
                    ) < 150
                );
                if (!isDuplicate) {
                    const dist = google.maps.geometry.spherical.computeDistanceBetween(
                        new google.maps.LatLng(lat, lng), place.location
                    );
                    tumEczanelerListesi.push({
                        source: 'external',
                        id: place.id,
                        name: place.displayName,
                        address: place.formattedAddress || 'Adres bilgisi yok',
                        lat: place.location.lat(),
                        lng: place.location.lng(),
                        phone: '', isOnduty: false, hasStock: false,
                        stockCount: 0, distance: dist / 1000
                    });
                }
            });
        }
    } catch (err) {
        console.error('Places API Hatası:', err);
    } finally {
        renderMarkers();
        renderList();
    }
}

function renderMarkers() {
    clearMarkers();
    tumEczanelerListesi.forEach(item => addSingleMarker(item));
}
function addSingleMarker(item) {
    if (isNaN(item.lat) || isNaN(item.lng)) return;
    let fillColor = '#ef4444';
    if (item.source === 'internal') {
        if (aramaYapildi && item.hasStock) fillColor = '#10b981';
        else if (!aramaYapildi && item.isOnduty) fillColor = '#f59e0b';
        else fillColor = '#6366f1';
    } else {
        fillColor = '#3b82f6';
    }
    const marker = new google.maps.Marker({
        position: { lat: item.lat, lng: item.lng },
        map: map,
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 9,
            fillColor,
            fillOpacity: 0.92,
            strokeColor: '#ffffff',
            strokeWeight: 2
        },
        title: item.name
    });
    marker.addListener('click', () => showInfoWindow(item, marker));
    markers.push(marker);
}
function showInfoWindow(item, marker) {
    const internalBadge = item.source === 'internal'
        ? `<span style="background:#6366f1;color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;">Sistemde Kayıtlı</span>`
        : `<span style="background:#64748b;color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;">Harici Eczane</span>`;
    let stockBadge = '';
    if (aramaYapildi) {
        stockBadge = item.hasStock
            ? `<div style="color:#10b981;font-weight:700;font-size:12px;margin:4px 0;">✓ Stokta Var</div>`
            : `<div style="color:#ef4444;font-weight:700;font-size:12px;margin:4px 0;">✗ Stok Durumu Bilinmiyor</div>`;
    }
    const detayBtn = item.source === 'internal'
        ? `<a href="eczane_detay.php?id=${item.id}" style="flex:1;text-align:center;padding:6px 12px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:700;color:#0f172a;text-decoration:none;">Detay</a>`
        : '';
    const content = `
        <div style="font-family:'Inter',sans-serif;min-width:220px;padding:4px 2px;">
            <div style="font-size:1rem;font-weight:800;color:#0f172a;margin-bottom:6px;">${item.name}</div>
            <div style="margin-bottom:6px;">${internalBadge}</div>
            ${stockBadge}
            <div style="font-size:12px;color:#6b7280;margin-bottom:12px;line-height:1.4;">${item.address}</div>
            <div style="display:flex;gap:8px;">
                ${detayBtn}
                <button onclick="openRouteModal(${item.lat}, ${item.lng}, '${item.name.replace(/'/g, "\\'")}', '${item.address.replace(/'/g, "\\'")}')"
                    style="flex:1;padding:6px 12px;background:var(--renk-birincil);color:#fff;border:none;border-radius:2px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                    <?= svgIkon('navigation') ?>
                    Rota Çiz
                </button>
            </div>
        </div>`;
    infoWindow.setContent(content);
    infoWindow.open(map, marker);
}
function clearMarkers() {
    markers.forEach(m => m.setMap(null));
    markers = [];
}

function renderList() {
    const listContainer = document.getElementById('pharmacyList');
    listContainer.innerHTML = '';
    let filtered = [...tumEczanelerListesi].sort((a, b) => {
        if (aramaYapildi) {
            if (a.hasStock && !b.hasStock) return -1;
            if (!a.hasStock && b.hasStock) return 1;
        }
        if (a.distance !== null && b.distance !== null) return a.distance - b.distance;
        if (a.distance !== null) return -1;
        if (b.distance !== null) return 1;
        if (a.source === 'internal' && b.source === 'external') return -1;
        if (a.source === 'external' && b.source === 'internal') return 1;
        return 0;
    });
    document.getElementById('resultCount').innerText = filtered.length;
    if (filtered.length === 0) {
        listContainer.innerHTML = `<div style="text-align:center;padding:3rem;color:var(--metin-uc);">Eczane bulunamadı.</div>`;
        return;
    }
    filtered.forEach(item => {
        const internalPill = item.source === 'internal'
            ? `<span class="sys-label">✓ Kayıtlı</span>`
            : `<span class="ext-label">Harici</span>`;
        const nobetci = (item.isOnduty && item.source === 'internal')
            ? `<span class="rozet rozet-yesil" style="margin-left:0.4rem;">Nöbetçi</span>` : '';
        const distHtml = item.distance !== null
            ? `<span style="font-size:0.75rem;color:var(--renk-birincil);font-weight:800;background:var(--arkaplan);padding:0.2rem 0.5rem;border-radius:4px;border:1px solid rgba(0,0,0,0.08);">${item.distance.toFixed(2)} km</span>` : '';
        let stockHtml = '';
        if (aramaYapildi) {
            stockHtml = item.hasStock
                ? `<div style="color:#10b981;font-weight:600;font-size:0.85rem;padding:0.4rem;background:rgba(16,185,129,0.08);border-radius:6px;">✓ Stokta Mevcut</div>`
                : `<div style="color:#ef4444;font-weight:600;font-size:0.85rem;padding:0.4rem;background:rgba(239,68,68,0.08);border-radius:6px;">✗ Net Stok Bilinmiyor</div>`;
        } else if (item.source === 'internal') {
            stockHtml = `<div style="color:var(--metin-ikincil);font-size:0.8rem;"><strong>${item.stockCount}</strong> İlaç Stokta</div>`;
        }
        const safeAddr = item.address.replace(/'/g, "\\'");
        const safeName = item.name.replace(/'/g, "\\'");
        const html = `
        <div class="pharmacy-list-item" onclick="panToPharmacy(${item.lat}, ${item.lng})">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <h4 style="margin:0 0 0.35rem;color:var(--metin-birincil);font-size:1.05rem;font-weight:700;">${item.name}</h4>
                    <div>${internalPill}${nobetci}</div>
                </div>
                ${distHtml}
            </div>
            ${stockHtml}
            <div style="font-size:0.82rem;color:var(--metin-uc);line-height:1.4;">${item.address}</div>
            <div style="display:flex;gap:0.5rem;margin-top:0.25rem;">
                ${item.source === 'internal' ? `<a href="eczane_detay.php?id=${item.id}" class="btn btn-ikincil btn-sm" style="flex:1;justify-content:center;" onclick="event.stopPropagation()">Detay</a>` : ''}
                <button
                    class="btn btn-birincil btn-sm"
                    style="flex:1;justify-content:center;"
                    onclick="event.stopPropagation(); openRouteModal(${item.lat}, ${item.lng}, '${safeName}', '${safeAddr}')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                    Rota Çiz
                </button>
            </div>
        </div>`;
        listContainer.insertAdjacentHTML('beforeend', html);
    });
}
function panToPharmacy(lat, lng) {
    if (!map) return;
    map.panTo({ lat, lng });
    map.setZoom(15);
}

let routeMapInitialized = false;
function openRouteModal(lat, lng, name, address) {
    if (!userLocation) {
        if (!confirm('Konum bilginiz bulunamadı. Rota çizmek için konum izni vermeniz gerekmektedir.\n\nModalı yine de açmak istiyor musunuz?')) return;
    }
    currentRouteTarget = { lat, lng, name, address };

    document.getElementById('routeDestName').textContent = name;
    document.getElementById('routeDestAddress').textContent = address;

    const gmUrl = userLocation
        ? `https://www.google.com/maps/dir/?api=1&origin=${userLocation.lat},${userLocation.lng}&destination=${lat},${lng}&travelmode=${currentTravelMode.toLowerCase()}`
        : `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
    document.getElementById('openGoogleMapsBtn').href = gmUrl;

    const modal = document.getElementById('routeModal');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';

    if (!routeMapInitialized) {
        initRouteMap().then(() => calculateRoute());
    } else {
        calculateRoute();
    }
}
async function initRouteMap() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const darkStyles = [
        { elementType: 'geometry', stylers: [{ color: '#1d2235' }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#8ec3b9' }] },
        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#171d33' }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#38414e' }] },
        { featureType: 'poi', stylers: [{ visibility: 'off' }] },
        { featureType: 'transit', stylers: [{ visibility: 'off' }] }
    ];
    const center = currentRouteTarget || userLocation || { lat: 39.0, lng: 35.0 };
    routeMap = new google.maps.Map(document.getElementById('routeMap'), {
        center: { lat: center.lat, lng: center.lng },
        zoom: 13,
        styles: isDark ? darkStyles : [
            { featureType: 'poi', stylers: [{ visibility: 'off' }] },
            { featureType: 'transit', stylers: [{ visibility: 'off' }] }
        ],
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true
    });
    routeDirectionsService = new google.maps.DirectionsService();
    routeDirectionsRenderer = new google.maps.DirectionsRenderer({
        map: routeMap,
        suppressMarkers: false,
        polylineOptions: {
            strokeColor: '#6366f1',
            strokeWeight: 6,
            strokeOpacity: 0.85
        }
    });
    routeMapInitialized = true;
}
function calculateRoute() {
    if (!userLocation || !currentRouteTarget) {
        document.getElementById('routeCalcLoading').style.display = 'none';
        document.getElementById('routeSummarySection').style.display = 'none';
        document.getElementById('routeDestName').textContent = currentRouteTarget?.name || 'Hedef';
        return;
    }

    document.getElementById('routeCalcLoading').style.display = 'flex';
    document.getElementById('routeSummarySection').style.display = 'none';
    const travelModeMap = {
        'DRIVING': google.maps.TravelMode.DRIVING,
        'WALKING': google.maps.TravelMode.WALKING,
        'TRANSIT': google.maps.TravelMode.TRANSIT
    };
    routeDirectionsService.route({
        origin: new google.maps.LatLng(userLocation.lat, userLocation.lng),
        destination: new google.maps.LatLng(currentRouteTarget.lat, currentRouteTarget.lng),
        travelMode: travelModeMap[currentTravelMode],
        provideRouteAlternatives: false,
        unitSystem: google.maps.UnitSystem.METRIC,
        region: 'tr',
        language: 'tr'
    }, (response, status) => {
        document.getElementById('routeCalcLoading').style.display = 'none';
        if (status === 'OK') {
            routeDirectionsRenderer.setDirections(response);
            const leg = response.routes[0].legs[0];

            document.getElementById('routeDuration').textContent = leg.duration.text;
            document.getElementById('routeDistance').textContent = leg.distance.text;

            const stepsList = document.getElementById('routeStepsList');
            stepsList.innerHTML = '';
            leg.steps.forEach((step, i) => {
                const cleanInstruction = step.instructions.replace(/<[^>]*>/g, '');
                const div = document.createElement('div');
                div.className = 'route-step';
                div.innerHTML = `
                    <div class="step-num">${i + 1}</div>
                    <div class="step-text">
                        ${cleanInstruction}
                        <div class="step-dist">${step.distance.text} · ${step.duration.text}</div>
                    </div>`;
                div.addEventListener('click', () => {
                    routeMap.panTo(step.start_location);
                    routeMap.setZoom(17);
                });
                stepsList.appendChild(div);
            });
            document.getElementById('routeSummarySection').style.display = 'block';

            const mode = currentTravelMode.toLowerCase();
            document.getElementById('openGoogleMapsBtn').href =
                `https://www.google.com/maps/dir/?api=1&origin=${userLocation.lat},${userLocation.lng}&destination=${currentRouteTarget.lat},${currentRouteTarget.lng}&travelmode=${mode}`;
        } else {
            document.getElementById('routeStepsList').innerHTML =
                `<div style="padding:2rem;text-align:center;color:var(--metin-uc);">Bu rota için yol tarifi alınamadı.<br><small>(${status})</small></div>`;
            document.getElementById('routeSummarySection').style.display = 'block';
            document.getElementById('routeDuration').textContent = '—';
            document.getElementById('routeDistance').textContent = '—';
        }
    });
}

document.getElementById('travelModeTabs').addEventListener('click', (e) => {
    const btn = e.target.closest('[data-mode]');
    if (!btn) return;
    document.querySelectorAll('.travel-mode-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentTravelMode = btn.dataset.mode;
    if (currentRouteTarget) calculateRoute();
});

function closeRouteModal() {
    document.getElementById('routeModal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('closeRouteModal').addEventListener('click', closeRouteModal);
document.getElementById('routeModal').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) closeRouteModal();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeRouteModal();
});

document.getElementById('clearRouteBtn').addEventListener('click', () => {
    if (routeDirectionsRenderer) routeDirectionsRenderer.setMap(null);
    routeMapInitialized = false;
    closeRouteModal();
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
