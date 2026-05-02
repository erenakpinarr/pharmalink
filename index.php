<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/includes/auth.php';
$girisYapmisMi = !empty($_SESSION['kullanici_id']);
$panelUrl = '';
if ($girisYapmisMi) {
    $panelUrl = match ($_SESSION['rol'] ?? '') {
        'admin' => 'admin/index.php',
        'eczane' => 'pharmacy/index.php',
        'kullanici' => 'user/index.php',
        default => 'auth/login.php',
    };
}
$arananIlac = trim($_GET['ara'] ?? '');
$aramaYapildi = !empty($arananIlac);
$haritaEczaneler = [];
$userLat = isset($_GET['lat']) && $_GET['lat'] !== '' ? (float) $_GET['lat'] : null;
$userLng = isset($_GET['lng']) && $_GET['lng'] !== '' ? (float) $_GET['lng'] : null;
$params = ["%$arananIlac%"];
$query = "
        SELECT e.id, e.eczane_adi, e.adres, e.sehir, e.ilce, e.telefon, e.enlem, e.boylam, e.nobetci,
               MAX(CASE WHEN i.ad LIKE ? AND s.durum = 'mevcut' THEN 1 ELSE 0 END) AS has_stock
    ";
if ($userLat && $userLng) {
    $query .= ", (6371 * acos(cos(radians(?)) * cos(radians(e.enlem)) * cos(radians(e.boylam) - radians(?)) + sin(radians(?)) * sin(radians(e.enlem)))) AS mesafe ";
    $params[] = $userLat;
    $params[] = $userLng;
    $params[] = $userLat;
} else {
    $query .= ", NULL AS mesafe ";
}
$query .= "
        FROM eczaneler e
        LEFT JOIN stok s ON s.eczane_id = e.id
        LEFT JOIN ilaclar i ON s.ilac_id = i.id
        WHERE e.durum = 'onaylandi'
        GROUP BY e.id
        ORDER BY has_stock DESC " . ($userLat ? ", mesafe ASC" : ", e.nobetci DESC, e.eczane_adi ASC");
$stmt = db()->prepare($query);
$stmt->execute($params);
$haritaEczaneler = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = db()->prepare("SELECT id, ad FROM ilaclar WHERE ad LIKE ? LIMIT 1");
$stmt->execute(["%$arananIlac%"]);
$bulunanIlac = $stmt->fetch();
$bulunanIlacId = $bulunanIlac['id'] ?? null;
$bulunanIlacAd = $bulunanIlac['ad'] ?? $arananIlac;
?>
<?php include_once __DIR__ . '/includes/header.php'; ?>
</head>
<body class="public-body home-page">
    <div class="loc-loading" id="locLoading">
        <div class="spinner"
            style="width:50px; height:50px; border-width:4px; margin-bottom:1rem; border-top-color:var(--renk-ikincil);">
        </div>
        <h2>Konumunuz Alınıyor...</h2>
        <p>Harika bir sonuç serisi için çevrenizi analiz ediyoruz.</p>
    </div>
    
    <header class="landing-hero" id="ana-ekran">
        <div class="hero-visual-container">
            <div class="hero-text-side">
                <div class="hero-tag">TÜRKİYE'NİN DİJİTAL SAĞLIK REHBERİ</div>
                <h1 class="hero-main-title text-gradient">Aradığınız İlaç,<br>Size En Yakın Eczanede.</h1>
                <p class="hero-desc">
                    Eczane eczane gezmeye son! İlacınızın adını yazın, hangi eczanenin elinde olduğunu ve size ne kadar
                    uzaklıkta olduğunu saniyeler içinde öğrenin.
                </p>
                <div class="search-container-wrap">
                    <form id="publicSearchForm" action="" method="get" class="public-search-box"
                        style="border: 1px solid #cbd5e1; padding: 0.5rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background:#fff;">
                        <input type="text" name="ara" id="drugSearchInput"
                            placeholder="İlacınızın adını buraya yazın... (Örn: Parol)" autocomplete="off"
                            value="<?= htmlspecialchars($arananIlac, ENT_QUOTES, 'UTF-8') ?>" required
                            style="padding-left: 1rem; font-weight: 500; border:none; height:50px;">
                        <input type="hidden" name="lat" id="latInput" value="">
                        <input type="hidden" name="lng" id="lngInput" value="">
                        <button type="submit" class="btn btn-birincil"
                            style="border-radius: 8px; padding: 0 2.5rem; height: 50px; text-transform: uppercase; font-weight:700; letter-spacing: 0.05em;">
                            <?= svgIkon('search') ?> Sorgula
                        </button>
                    </form>
                    <div class="autocomplete-list" id="autocompleteList"></div>
                </div>
                <?php if (!$aramaYapildi): ?>
                    <div style="display:flex; justify-content:flex-start; gap:1.5rem; flex-wrap:wrap; margin-top: 2.5rem;">
                        <a href="#hizmet-amaclarimiz" class="btn btn-gri" style="font-weight:700; border-radius:8px;">Hizmet Politikamız</a>
                        <a href="#kullanim-rehberi" class="btn btn-cizgili" style="font-weight:700; border-radius:8px;">Nasıl Çalışır?</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="hero-image-side">
                <img src="<?= sayf('assets/img/hero_visual.png') ?>" alt="PharmaLink Hero" class="hero-main-img">
            </div>
        </div>
    </header>
    <div id="externalMap"></div> 
    
    <div class="route-modal-overlay" id="routeModal" role="dialog" aria-modal="true">
        <div class="route-modal">
            <div class="route-modal-header">
                <h3>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="3 11 22 2 13 21 11 13 3 11" />
                    </svg>
                    Rota Planlayıcı
                </h3>
                <div style="display:flex;align-items:center;gap:1rem;">
                    <button class="route-modal-close" id="closeRouteModal" aria-label="Kapat">✕</button>
                </div>
            </div>
            <div class="route-modal-body">
                <div id="pubRouteMap"></div>
                <div class="route-sidebar">
                    <div class="travel-mode-tabs" id="travelModeTabs">
                        <button class="travel-mode-btn active" data-mode="DRIVING">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="3" width="15" height="13" />
                                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
                                <circle cx="5.5" cy="18.5" r="2.5" />
                                <circle cx="18.5" cy="18.5" r="2.5" />
                            </svg>
                            Araç
                        </button>
                        <button class="travel-mode-btn" data-mode="WALKING">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="5" r="1" />
                                <path d="m9 20 3-6 3 3 1-8" />
                                <path d="m6.5 17.5 1.5-3" />
                                <path d="m14 14 1.5 3.5" />
                            </svg>
                            Yürüyüş
                        </button>
                        <button class="travel-mode-btn" data-mode="TRANSIT">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="3" width="16" height="16" rx="2" />
                                <path d="M9 19v2" />
                                <path d="M15 19v2" />
                                <path d="M4 11h16" />
                                <path d="M9 7h6" />
                            </svg>
                            Taşıma
                        </button>
                    </div>
                    <div class="route-modal-loading" id="routeCalcLoading">
                        <div class="spinner"
                            style="width:44px;height:44px;border-width:4px;border-top-color:var(--renk-ikincil);"></div>
                        <span style="color:var(--metin-ikincil);font-weight:600;">Rota hesaplanıyor...</span>
                    </div>
                    <div id="routeSummarySection" style="display:none;flex:1;flex-direction:column;overflow:hidden;">
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
                    <span id="routeDestAddress"></span>
                </div>
                <div class="route-footer-btns">
                    <button class="btn btn-gri btn-sm" id="clearRouteBtn">✕ Rotayı Sil</button>
                    <a href="#" class="btn btn-birincil btn-sm" id="openGoogleMapsBtn" target="_blank" rel="noopener">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php if ($aramaYapildi): ?>
        <section id="sonuclar" style="padding: 4rem 5%; background: var(--arkaplan);">
            <div style="max-width:1200px; margin:0 auto;">
                <div
                    style="display:flex; justify-content:space-between; align-items:flex-end; gap:2rem; margin-bottom: 2rem; border-bottom: 2px solid var(--kenar-rengi); padding-bottom: 1.5rem;">
                    <div>
                        <h2 style="font-size:2rem; margin:0; color:#0f172a;">Arama Sonuçları</h2>
                        <p style="color:#475569; font-size:1.1rem; margin:0.5rem 0 0 0;">
                            "<strong><?= htmlspecialchars($arananIlac) ?></strong>" ilacı için sistemdeki kayıtlı ve
                            çevredeki eczaneler listelenmiştir.
                        </p>
                    </div>
                    <div id="searchCounter" style="color:var(--metin-uc); font-weight:600; font-size:0.95rem;"></div>
                </div>
                <div class="search-results-list" id="mainResultsGrid">
                    <?php if (empty($haritaEczaneler)): ?>
                        <div id="noInternalResults"
                            style="background: #fff; padding: 4rem 2rem; border-radius: 4px; border: 1px dashed var(--kenar-rengi); text-align: center; width: 100%;">
                            <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"><?= svgIkon('pill') ?></div>
                            <h3 style="color: #0f172a; font-size: 1.5rem; margin-bottom: 0.5rem;">Kayıtlı Eczane Bulunamadı</h3>
                            <p style="color: #475569; max-width: 500px; margin: 0 auto; line-height:1.6;">
                                "<strong><?= htmlspecialchars($arananIlac) ?></strong>" ilacı için kayıtlı eczanelerimizde şu an
                                aktif stok bilgisi bulunmamaktadır.
                                Diğer eczaneler aşağıda taranmaktadır.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($haritaEczaneler as $he): ?>
                            <div class="pub-drug-card internal-result" data-id="<?= $he['id'] ?>"
                                data-lat="<?= (float) $he['enlem'] ?>" data-lng="<?= (float) $he['boylam'] ?>">
                                <div class="card-left">
                                    <h3 style="font-size:1.2rem; color:#0f172a; margin:0;">
                                        <?= htmlspecialchars($he['eczane_adi']) ?></h3>
                                    <div style="display:flex;gap:0.5rem; flex-wrap:wrap; margin-top:0.25rem;">
                                        <span class="sys-badge"><?= svgIkon('check-circle') ?> Tescilli Eczane</span>
                                        <?php if ($he['nobetci']): ?>
                                            <span class="rozet rozet-yesil" style="border-radius:2px;">Nöbetçi</span>
                                        <?php endif; ?>
                                    </div>
                                    <p style="color:#475569; font-size:0.85rem; margin:0.5rem 0 0 0; display:flex; gap:0.5rem;">
                                        <span><?= svgIkon('map-pin') ?></span>
                                        <span><?= htmlspecialchars($he['sehir'] . '/' . $he['ilce']) ?> -
                                            <?= htmlspecialchars($he['adres']) ?></span>
                                    </p>
                                </div>
                                <div class="card-mid">
                                    <?php if ($he['has_stock'] == 1): ?>
                                        <div
                                            style="padding:0.6rem 1rem; background:rgba(16, 185, 129, 0.05); border-radius:4px; border:1px solid #10b981; color:#10b981; text-align:center; font-weight:700;">
                                            STOKTA MEVCUT
                                        </div>
                                    <?php else: ?>
                                        <div
                                            style="padding:0.6rem 1rem; background:rgba(239, 68, 68, 0.05); border-radius:4px; border:1px solid #ef4444; color:#ef4444; text-align:center; font-weight:700;">
                                            STOK BİLGİSİ YOK
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-right">
                                    <?php if ($he['mesafe'] !== null): ?>
                                        <div style="font-size:1rem; font-weight:900; color:#0f172a;">
                                            <?= number_format($he['mesafe'], 2) ?> km</div>
                                    <?php endif; ?>
                                    <div style="display:flex; gap:0.5rem;">
                                        <?php if ($he['has_stock'] != 1 && $girisYapmisMi && ($_SESSION['rol'] ?? '') === 'kullanici'): ?>
                                            <button type="button" onclick="talepModalAc(<?= $he['id'] ?>, '<?= e($he['eczane_adi']) ?>')" class="btn btn-tehlike btn-sm" style="border-radius:2px;">Talep Aç</button>
                                        <?php endif; ?>
                                        <a href="<?= sayf('user/eczane_detay.php?id=' . $he['id']) ?>"
                                            class="btn btn-birincil btn-sm" style="border-radius:2px;">Bilgi Al</a>
                                        <button type="button"
                                            onclick='openRouteModal(<?= (float) $he['enlem'] ?>, <?= (float) $he['boylam'] ?>, <?= json_encode($he['eczane_adi']) ?>, <?= json_encode($he['sehir'] . '/' . $he['ilce'] . ' - ' . $he['adres']) ?>)'
                                            class="btn btn-cizgili btn-sm" style="border-radius:2px;">Rota</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div id="externalResultsContainer" style="display: contents;"></div>
                </div>
                <div
                    style="margin-top: 4rem; text-align:center; border-top:1px solid var(--kenar-rengi); padding-top:3rem;">
                    <p style="font-size:1rem; color:#475569; margin-bottom:1.5rem;">Aradığınız ilacı bulamadınız mı? Yeni
                        bir arama yapabilir veya sistemden çıkış yapabilirsiniz.</p>
                    <div style="display:flex; justify-content:center; gap:1rem;">
                        <a href="<?= sayf('index.php') ?>" class="btn btn-gri">Yeni Arama Yap</a>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        
        <section class="section-padding" id="hizmet-amaclarimiz" style="background: linear-gradient(to bottom, #fff, #f8fafc);">
            <div class="section-header">
                <h2 class="text-gradient">Dijital Sağlık ve İlaç Erişim Hizmeti</h2>
                <p>Vatandaşlarımızın doğru ilaca, doğru zamanda ve en yakın noktadan ulaşmasını sağlamak temel
                    önceliğimizdir.</p>
            </div>
            <div style="display:flex; align-items:center; gap:4rem; max-width:1200px; margin:0 auto; flex-wrap:wrap;">
                <div style="flex:1; min-width:300px;">
                    <img src="<?= sayf('assets/img/trust_illustration.png') ?>" alt="Trust" style="width:100%; border-radius:24px; box-shadow:0 20px 40px rgba(0,0,0,0.05);">
                </div>
                <div style="flex:1.5; min-width:300px;">
                    <div class="onemli-ozellikler" style="grid-template-columns: 1fr; gap: 1.5rem;">
                        <div class="ozellik-kart glass-card" style="display:flex; text-align:left; gap:1.5rem; padding:2rem; align-items:center;">
                            <div class="ozellik-ikon" style="margin:0; flex-shrink:0; background:var(--renk-birincil-acik); color:var(--renk-birincil);"><?= svgIkon('shield-check') ?></div>
                            <div>
                                <h3 style="margin-bottom:0.5rem;">Kamu Yararı ve Şeffaflık</h3>
                                <p style="margin:0;">Tüm veriler tescilli eczanelerden doğrudan alınarak en doğru bilginin ulaştırılması sağlanır.</p>
                            </div>
                        </div>
                        <div class="ozellik-kart glass-card" style="display:flex; text-align:left; gap:1.5rem; padding:2rem; align-items:center;">
                            <div class="ozellik-ikon" style="margin:0; flex-shrink:0; background:#e0f2fe; color:#0ea5e9;"><?= svgIkon('map-pin') ?></div>
                            <div>
                                <h3 style="margin-bottom:0.5rem;">Erişilebilirlik</h3>
                                <p style="margin:0;">Türkiye genelindeki binlerce eczane arasından size en yakın olanı saniyeler içinde tespit edilir.</p>
                            </div>
                        </div>
                        <div class="ozellik-kart glass-card" style="display:flex; text-align:left; gap:1.5rem; padding:2rem; align-items:center;">
                            <div class="ozellik-ikon" style="margin:0; flex-shrink:0; background:#fef3c7; color:#d97706;"><?= svgIkon('clock') ?></div>
                            <div>
                                <h3 style="margin-bottom:0.5rem;">Nöbetçi Eczane Desteği</h3>
                                <p style="margin:0;">Gece ve tatil günlerinde hizmet veren nöbetçi eczaneler sistem tarafından otomatik olarak vurgulanır.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="resmi-bildiri" style="margin-top: 5rem; border-radius:20px; background:rgba(255,255,255,0.8); backdrop-filter:blur(10px); border:1px solid #e2e8f0;">
                <?= svgIkon('info') ?>
                <div>
                    <strong>Önemli Bilgilendirme</strong>
                    <p>Bu platform bir ilaç satış sitesi değildir. PharmaLink, sadece ilaçların hangi eczanelerde
                        bulunduğuna dair bilgi sağlayan bir rehberlik servisidir.</p>
                </div>
            </div>
        </section>
        
        <section class="section-padding" id="kullanim-rehberi" style="background: #f8fafc; border-top: 1px solid #f1f5f9;">
            <div class="section-header">
                <h2 class="text-gradient">Hizmet Kullanım Rehberi</h2>
                <p>Sistemi kullanarak ilacınıza ulaşmak için aşağıdaki 3 adımı takip ediniz.</p>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:4rem; max-width:1100px; margin:0 auto;">
                <div class="adimlar-kapsayici">
                    <div class="adim-kart">
                        <div class="adim-no">1</div>
                        <h4 style="font-weight:700; margin-bottom:0.75rem;">Sorgulama Yapın</h4>
                        <p style="font-size:0.95rem; color:#475569;">Arama kutusuna ilacın adını yazarak stokları anında tarayın.</p>
                    </div>
                    <div class="adim-kart">
                        <div class="adim-no">2</div>
                        <h4 style="font-weight:700; margin-bottom:0.75rem;">Eczaneyi Belirleyin</h4>
                        <p style="font-size:0.95rem; color:#475569;">Sonuçlardan size en yakın veya nöbetçi eczaneyi seçin.</p>
                    </div>
                    <div class="adim-kart">
                        <div class="adim-no">3</div>
                        <h4 style="font-weight:700; margin-bottom:0.75rem;">Rotanızı Oluşturun</h4>
                        <p style="font-size:0.95rem; color:#475569;">Tek tıkla yol tarifi alarak eczaneye en hızlı şekilde ulaşın.</p>
                    </div>
                </div>

            </div>
        </section>
    <?php endif; ?>
    
    <section class="section-padding" id="faq" style="background: #fff;">
        <div class="section-header">
            <h2 style="color:#0f172a;">Sıkça Sorulan Sorular</h2>
        </div>
        <div class="sss-kapsayici">
            <div class="sss-item">
                <div class="sss-soru">
                    Bu sistem resmi bir hizmet mi?
                    <span class="soru-ikon"><?= svgIkon('chevron-down') ?></span>
                </div>
                <div class="sss-cevap">
                    PharmaLink, eczaneler ve vatandaşlar arasındaki ilaç erişimini dijitalleştiren profesyonel bir
                    altyapıdır. Veriler tescilli işletmelerden sağlanır.
                </div>
            </div>
            <div class="sss-item">
                <div class="sss-soru">
                    Eczane bilgilerine nasıl ulaşabilirim?
                    <span class="soru-ikon"><?= svgIkon('chevron-down') ?></span>
                </div>
                <div class="sss-cevap">
                    Arama sonuçlarındaki "Bilgi Al" butonuna tıklayarak eczanenin telefon, adres ve konum bilgilerine ulaşabilirsiniz.
                </div>
            </div>
            <div class="sss-item">
                <div class="sss-soru">
                    Sistem kullanımı ücretli mi?
                    <span class="soru-ikon"><?= svgIkon('chevron-down') ?></span>
                </div>
                <div class="sss-cevap">
                    Hayır, PharmaLink vatandaşlar için tamamen ücretsiz bir bilgilendirme ve rehberlik platformudur.
                </div>
            </div>
            <div class="sss-item">
                <div class="sss-soru">
                    Nöbetçi eczaneler güncel mi?
                    <span class="soru-ikon"><?= svgIkon('chevron-down') ?></span>
                </div>
                <div class="sss-cevap">
                    Evet, sistemimiz nöbetçi eczane verilerini düzenli olarak güncellemekte ve arama sonuçlarında öncelikli olarak göstermektedir.
                </div>
            </div>
            <div class="sss-item">
                <div class="sss-soru">
                    Eczanemizi sisteme nasıl kaydedebiliriz?
                    <span class="soru-ikon"><?= svgIkon('chevron-down') ?></span>
                </div>
                <div class="sss-cevap">
                    Giriş sayfasındaki "Eczane Kayıt" seçeneği ile başvurunuzu yapabilirsiniz. Onay sürecinden sonra profilinizi yönetmeye başlayabilirsiniz.
                </div>
            </div>
            <div class="sss-item">
                <div class="sss-soru">
                    Hangi şehirlerde hizmet veriyorsunuz?
                    <span class="soru-ikon"><?= svgIkon('chevron-down') ?></span>
                </div>
                <div class="sss-cevap">
                    PharmaLink, Türkiye genelindeki tüm il ve ilçelerde hizmet verecek şekilde tasarlanmıştır ve her geçen gün ağını genişletmektedir.
                </div>
            </div>
        </div>
    </section>



    <?php include_once __DIR__ . '/includes/footer.php'; ?>

    <!-- Talep Modal -->
    <div class="modal-arkaplan" id="talepModal" role="dialog" aria-hidden="true">
        <div class="modal-kutu" style="max-width: 500px;">
            <div class="modal-baslik">
                <h3><?= svgIkon('message-circle') ?> Talep Oluştur</h3>
                <button class="modal-kapat" onclick="modalKapat('talepModal')">✕</button>
            </div>
            <form id="talepForm">
                <div class="modal-govde">
                    <input type="hidden" name="eczane_id" id="talepEczaneId">
                    <p style="font-size: 0.95rem; color: var(--metin-ikincil); margin-bottom: 1rem;">
                        <strong><span id="talepEczaneAdi"></span></strong> adlı eczaneden 
                        <strong><?= htmlspecialchars($arananIlac) ?></strong> ilacı için stok talebi veya özel sipariş isteğinde bulunuyorsunuz.
                    </p>
                    <div class="form-grup">
                        <label class="form-etiket">Mesajınız *</label>
                        <textarea class="form-alani" name="mesaj" rows="4" required placeholder="Örn: Bu ilacı ne zamana temin edebilirsiniz? Bana geri dönüş yapabilir misiniz?"></textarea>
                    </div>
                </div>
                <div class="modal-ayak">
                    <button type="button" class="btn btn-gri" onclick="modalKapat('talepModal')">İptal</button>
                    <button type="submit" class="btn btn-birincil">Talebi Gönder</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function talepModalAc(eczaneId, eczaneAdi) {
        document.getElementById('talepEczaneId').value = eczaneId;
        document.getElementById('talepEczaneAdi').textContent = eczaneAdi;
        modalAc('talepModal');
    }
    document.getElementById('talepForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        try {
            const formData = new FormData(this);
            formData.append('islem', 'talep_olustur');
            formData.append('konu', 'Stok Talebi: <?= e($arananIlac) ?>');
            const data = await ApiService.post('/api/talep.php', Object.fromEntries(formData));
            if (data.basari) {
                gostermeBildirim('Talebiniz başarıyla eczaneye iletildi.', 'basari');
                modalKapat('talepModal');
                this.reset();
            } else {
                gostermeBildirim(data.mesaj || 'Talep oluşturulurken bir hata oluştu.', 'tehlike');
            }
        } catch(err) {
            gostermeBildirim('Bir bağlantı hatası oluştu.', 'tehlike');
        } finally {
            btn.disabled = false;
        }
    });
    </script>
</body>
</html>
