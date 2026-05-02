<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/includes/helpers.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header("Location: " . APP_URL);
    exit;
}

// Eczane verilerini çek
$stmt = db()->prepare("
    SELECT e.*, k.ad as yetkili_ad, k.soyad as yetkili_soyad, k.profil_resmi
    FROM eczaneler e
    JOIN kullanicilar k ON k.id = e.kullanici_id
    WHERE e.slug = ? AND e.durum = 'onaylandi' AND e.vitrin_aktif = 1
    LIMIT 1
");
$stmt->execute([$slug]);
$eczane = $stmt->fetch();

if (!$eczane) {
    die("<h1>404 - Eczane Bulunamadı</h1><p>Aradığınız eczane sayfası yayında değil veya mevcut değil.</p>");
}

// Eczaneye özel aramayı işle
$arananIlac = trim($_GET['ara'] ?? '');
$params = [$eczane['id']];
$query = "
    SELECT i.ad, i.etken_madde, k.ad as kategori, s.durum, s.adet, i.id as ilac_id
    FROM stok s
    JOIN ilaclar i ON i.id = s.ilac_id
    JOIN kategoriler k ON k.id = i.kategori_id
    WHERE s.eczane_id = ? AND i.aktif = 1
";

if($arananIlac) {
    $query .= " AND (i.ad LIKE ? OR i.etken_madde LIKE ?)";
    $params[] = "%$arananIlac%";
    $params[] = "%$arananIlac%";
}

$query .= " ORDER BY s.durum ASC, i.ad ASC";
$stokStmt = db()->prepare($query);
$stokStmt->execute($params);
$stoklar = $stokStmt->fetchAll();

$temaRengi = $eczane['tema_rengi'] ?: '#6366f1';
$baslik = e($eczane['eczane_adi']) . ' — Eczane Vitrini';

include __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --vitrin-tema: <?= $temaRengi ?>;
        --vitrin-tema-50: <?= $temaRengi ?>10;
        --vitrin-tema-20: <?= $temaRengi ?>33;
    }

    .vitrin-hero {
        position: relative;
        height: 450px;
        background: <?= $eczane['banner_resmi'] ? "url('".sayf('uploads/banners/'.$eczane['banner_resmi'])."')" : 'linear-gradient(135deg, var(--vitrin-tema), #4338ca)' ?>;
        background-size: cover;
        background-position: center;
        display: flex;
        align-items: flex-end;
        padding-bottom: 4rem;
        margin-top: -80px; /* Header transparan değilse ayarlanmalı */
    }

    .vitrin-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(0deg, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0) 100%);
    }

    .vitrin-header-content {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 2rem;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }

    .vitrin-logo {
        width: 120px;
        height: 120px;
        border-radius: 24px;
        background: white;
        padding: 5px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        border: 4px solid var(--vitrin-tema);
    }

    .vitrin-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 18px;
    }

    .vitrin-info h1 {
        color: white;
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }

    .vitrin-info .location {
        color: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.1rem;
    }

    .main-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 3rem;
        max-width: 1200px;
        margin: -3rem auto 4rem;
        padding: 0 2rem;
        position: relative;
        z-index: 10;
    }

    .vitrin-card {
        background: var(--arkaplan-kart);
        border-radius: 24px;
        border: 1px solid var(--kenar-rengi);
        padding: 2.5rem;
        box-shadow: var(--golge-sm);
    }

    .hakkimizda-text {
        font-size: 1.1rem;
        line-height: 1.8;
        color: var(--metin-ikincil);
        white-space: pre-wrap;
    }

    .search-box-pill {
        background: var(--arkaplan-kart);
        border: 1px solid var(--kenar-rengi);
        border-radius: 100px;
        padding: 0.5rem 0.5rem 0.5rem 1.5rem;
        display: flex;
        align-items: center;
        box-shadow: var(--golge-sm);
        margin-bottom: 2rem;
    }

    .stok-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .drug-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem;
        border-radius: 16px;
        border: 1px solid var(--kenar-rengi);
        transition: all 0.2s ease;
    }

    .drug-row:hover {
        border-color: var(--vitrin-tema);
        background: var(--vitrin-tema-50);
    }

    .contact-sidebar {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .contact-pill {
        display: flex;
        align-items: center;
        gap: 1rem;
        color: var(--metin-birincil);
        font-weight: 600;
        text-decoration: none;
        padding: 1rem;
        border-radius: 12px;
        background: var(--arkaplan-hover);
        transition: transform 0.2s;
    }

    .contact-pill:hover { transform: translateX(5px); }

    .btn-vitrin {
        background: var(--vitrin-tema);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: opacity 0.2s;
    }

    .btn-vitrin:hover { opacity: 0.9; }

    @media (max-width: 1024px) {
        .main-grid { grid-template-columns: 1fr; margin-top: 1rem; }
        .vitrin-hero { height: 350px; }
        .vitrin-header-content { flex-direction: column; text-align: center; }
        .vitrin-logo { margin-top: -60px; }
    }
</style>

<div class="vitrin-hero">
    <div class="vitrin-header-content">
        <div class="vitrin-logo">
            <?php if (!empty($eczane['profil_resmi'])): ?>
                <img src="<?= sayf('uploads/avatars/' . e($eczane['profil_resmi'])) ?>" alt="Logo">
            <?php else: ?>
                <div style="width:100%; height:100%; background:var(--vitrin-tema-50); color:var(--vitrin-tema); display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:800; border-radius:18px;">
                    <?= mb_substr($eczane['eczane_adi'], 0, 1) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="vitrin-info">
            <h1><?= e($eczane['eczane_adi']) ?></h1>
            <div class="location">
                <?= svgIkon('map-pin') ?>
                <span><?= e($eczane['sehir']) ?> / <?= e($eczane['ilce']) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="main-grid">
    <div class="vitrin-left">
        <!-- Hakkımızda Bölümü -->
        <?php if ($eczane['hakkimizda']): ?>
        <section class="vitrin-card" style="margin-bottom: 2rem;">
            <h2 style="margin-bottom: 1.5rem; font-weight: 800; color:var(--vitrin-tema);">Hakkımızda</h2>
            <div class="hakkimizda-text"><?= $eczane['hakkimizda'] ?></div>
        </section>
        <?php endif; ?>

        <!-- Stok ve Arama Bölümü -->
        <section class="vitrin-card">
            <h2 style="margin-bottom: 2rem; font-weight: 800;">İlaç Stoklarımı Sorgula</h2>
            
            <form action="" method="GET" class="search-box-pill">
                <input type="hidden" name="slug" value="<?= e($slug) ?>">
                <?= svgIkon('search') ?>
                <input type="text" name="ara" value="<?= e($arananIlac) ?>" placeholder="Aramak istediğiniz ilacı yazın..." style="flex:1; border:none; padding:1rem; outline:none; background:transparent;">
                <button type="submit" class="btn-vitrin">Sorgula</button>
            </form>

            <div class="stok-list">
                <?php if (empty($stoklar)): ?>
                    <p style="text-align:center; padding: 2rem; color:var(--metin-uc);">Şu anda stok bilgisi bulunmamaktadır.</p>
                <?php else: ?>
                    <?php foreach ($stoklar as $s): ?>
                        <div class="drug-row">
                            <div>
                                <strong style="font-size:1.1rem;"><?= e($s['ad']) ?></strong>
                                <div style="font-size:0.85rem; color:var(--metin-ikincil);"><?= e($s['kategori']) ?> · <?= e($s['etken_madde']) ?></div>
                            </div>
                            <div style="display:flex; align-items:center; gap:1.5rem;">
                                <?php if ($s['durum'] === 'mevcut'): ?>
                                    <span style="color:#10b981; font-weight:700; font-size:0.9rem;">● STOKTA VAR</span>
                                    <button class="btn-vitrin" style="padding: 0.5rem 1rem; font-size:0.85rem;" onclick="openRezervasyonModal('<?= $eczane['id'] ?>', '<?= e($s['ad']) ?>', '<?= $s['ilac_id'] ?>')">Ayırt</button>
                                <?php else: ?>
                                    <span style="color:#ef4444; font-weight:700; font-size:0.9rem;">TÜKENDİ</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="contact-sidebar">
        <div class="vitrin-card">
            <h3 style="margin-bottom: 1.5rem; font-weight: 800;">İletişim & Konum</h3>
            
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <a href="tel:<?= e($eczane['telefon']) ?>" class="contact-pill">
                    <div style="color:var(--vitrin-tema)"><?= svgIkon('phone') ?></div>
                    <span><?= e($eczane['telefon']) ?></span>
                </a>
                <div class="contact-pill" style="cursor:default;">
                    <div style="color:var(--vitrin-tema)"><?= svgIkon('map-pin') ?></div>
                    <span style="font-size:0.85rem; line-height:1.4;"><?= e($eczane['adres']) ?></span>
                </div>
                <div class="contact-pill" style="cursor:default;">
                    <div style="color:var(--vitrin-tema)"><?= svgIkon('clock') ?></div>
                    <span style="font-size:0.85rem;">Hafta İçi: 09:00 - 19:00</span>
                </div>
            </div>

            <div style="margin-top:2rem; border-radius:16px; overflow:hidden; border:1px solid var(--kenar-rengi);">
                <iframe 
                    width="100%" 
                    height="200" 
                    frameborder="0" 
                    style="border:0" 
                    src="https://www.google.com/maps/embed/v1/place?key=<?= GOOGLE_MAPS_API_KEY ?>&q=<?= $eczane['enlem'] ?>,<?= $eczane['boylam'] ?>" 
                    allowfullscreen>
                </iframe>
            </div>

            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $eczane['enlem'] ?>,<?= $eczane['boylam'] ?>" target="_blank" class="btn-vitrin" style="width:100%; display:flex; align-items:center; justify-content:center; gap:0.5rem; margin-top:1.5rem; text-decoration:none;">
                <?= svgIkon('navigation') ?> Yol Tarifi Al
            </a>
        </div>

        <div class="vitrin-card" style="background:var(--vitrin-tema); border:none;">
            <h3 style="color:white; margin-bottom:1rem; font-weight:800;">Bize Mesaj Gönderin</h3>
            <p style="color:rgba(255,255,255,0.8); font-size:0.9rem; margin-bottom:1.5rem;">İlaç sormak veya bilgi almak için formu doldurun.</p>
            <form action="<?= sayf('user/requests.php') ?>" method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="eczane_id" value="<?= $eczane['id'] ?>">
                <input type="text" name="konu" placeholder="Konu" required style="width:100%; padding:0.8rem; border-radius:8px; border:none; margin-bottom:0.75rem;">
                <textarea name="mesaj" placeholder="Mesajınız..." required style="width:100%; padding:0.8rem; border-radius:8px; border:none; margin-bottom:1rem; min-height:100px;"></textarea>
                <button type="submit" style="width:100%; padding:0.8rem; border-radius:8px; border:none; background:white; color:var(--vitrin-tema); font-weight:800; cursor:pointer;">Gönder</button>
            </form>
        </div>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
