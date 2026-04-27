<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/auth.php';
$rol   = mevcutRol();
$flash = flashMesajAl();

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$isPanelPage = strpos($scriptPath, '/admin/') !== false || 
               strpos($scriptPath, '/pharmacy/') !== false || 
               strpos($scriptPath, '/user/') !== false;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PharmaLink - Eczane ve İlaç Stok Takip Platformu">
    <title><?= e($baslik ?? APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= sayf('assets/css/style.css') ?>?v=<?= time() ?>">
    <style>
    .topbar-nav { overflow: visible !important; }
    .nav-dropdown-kapsayici { position: relative; height: 100%; display: flex; align-items: center; }
    .nav-dropdown-menu { position: absolute; top: 100%; left: 0; min-width: 220px; background: #fff; border: 1px solid var(--kenar-rengi); border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); padding: 0.5rem; display: none; z-index: 50; flex-direction: column; }
    .nav-dropdown-menu.goster { display: flex; }
    .nav-dropdown-menu .dropdown-item { font-size: 0.85rem; padding: 0.75rem 1rem; border-radius: 6px; transition: var(--gecis-hizli); color: var(--metin-birincil); text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
    .nav-dropdown-menu .dropdown-item:hover { background: var(--arkaplan-hover); }
    .nav-dropdown-menu .dropdown-item.aktif { background-color: var(--renk-birincil-acik); color: var(--renk-birincil); font-weight: 600; }
    button.panel-nav-link svg:last-child { margin-left: 0.2rem; margin-right: 0; width: 14px; height: 14px; }
    </style>
    <script>
        window.APP_URL = '<?= rtrim(APP_URL, '/') ?>';
        window.CSRF_TOKEN = '<?= csrf_token() ?>';
        window.PHARMA_CONFIG = {
            baseUrl: '<?= rtrim(APP_URL, '/') ?>',
            csrfToken: '<?= csrf_token() ?>',
            isLoggedIn: <?= mevcutRol() ? 'true' : 'false' ?>,
            userRole: '<?= mevcutRol() ?>',
            searchActive: <?= (!empty($_GET['ara'])) ? 'true' : 'false' ?>,
            userCity: '<?= $_SESSION['user_city'] ?? 'İstanbul' ?>'
        };
    </script>
    <script src="<?= sayf('assets/js/app.js') ?>?v=<?= APP_VERSION ?>" defer></script>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
</head>
<body class="<?= ($rol && $isPanelPage) ? 'app-body' : 'public-body' ?>">
<?php
$bildirim_html = '
<div class="bildirim-kapsayici">
    <button id="bildirim-can" class="btn-bildirim-can" title="Bildirimler">
        ' . svgIkon('bell') . '
        <span id="bildirim-sayac" class="bildirim-rozet"></span>
    </button>
    <div id="bildirim-pencere" class="bildirim-dropdown">
        <div class="bildirim-header">
            <div class="bildirim-baslik-satir">
                <h4>Bildirimler</h4>
                <button id="bildirim-hepsini-oku" class="bildirim-footer-btn">Hepsini Oku</button>
            </div>
            <div class="bildirim-tabs" id="bildirim-tablari">
                <div class="bildirim-tab aktif" data-kat="hepsi">Hepsi</div>
                <div class="bildirim-tab" data-kat="sistem">Sistem</div>
                <div class="bildirim-tab" data-kat="eczane">Eczane</div>
                <div class="bildirim-tab" data-kat="ilac">İlaç</div>
            </div>
        </div>
        <div id="bildirim-liste" class="bildirim-liste">
        </div>
        <div class="bildirim-footer">
            <button id="bildirim-temizle" class="bildirim-footer-btn">Okunanları Temizle</button>
        </div>
    </div>
</div>';
?>
<?php if ($rol && $isPanelPage): ?>
<div class="app-layout">
    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-sol">
                <a href="<?= sayf('/') ?>" class="sidebar-marka" style="border:none; height:auto; padding:0; gap:0.6rem;">
                    <div style="width:32px; height:32px; background:var(--renk-birincil); border-radius:4px; display:flex; align-items:center; justify-content:center; color:#fff;">
                        <?= svgIkon('plus') ?>
                    </div>
                    <span><?= APP_NAME ?></span>
                </a>
                <div style="width:1px; height:24px; background:var(--kenar-rengi); margin:0 0.5rem;"></div>
                <div style="display:flex; flex-direction:column;">
                    <span style="color:var(--metin-ikincil);font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.02em;"><?= e(rolAdi($rol)) ?></span>
                    <strong style="font-size:0.95rem;color:var(--metin-birincil);line-height:1.2;"><?= e($_SESSION['kullanici_ad'] ?? '') ?></strong>
                </div>
            </div>
            <nav class="topbar-nav">
                <?php 
                $navItems = panelNavigasyonuGetir($rol);
                $currentScript = basename($_SERVER['SCRIPT_FILENAME']);
                foreach ($navItems as $item): 
                    if (isset($item['subItems'])):
                        $isSubActive = false;
                        foreach($item['subItems'] as $sub) {
                            if ($currentScript === basename($sub['link'])) $isSubActive = true;
                        }
                ?>
                    <div class="nav-dropdown-kapsayici">
                        <button class="panel-nav-link <?= $isSubActive ? 'aktif' : '' ?>" style="border:none; background:none; cursor:pointer;" onclick="this.nextElementSibling.classList.toggle('goster'); event.stopPropagation();">
                            <?= svgIkon($item['icon']) ?>
                            <span><?= $item['label'] ?></span>
                            <?= svgIkon('chevron-down') ?>
                        </button>
                        <div class="nav-dropdown-menu">
                            <?php foreach($item['subItems'] as $sub): 
                                $subActive = ($currentScript === basename($sub['link']));
                            ?>
                                <a href="<?= sayf($sub['link']) ?>" class="dropdown-item <?= $subActive ? 'aktif' : '' ?>">
                                    <?= svgIkon($sub['icon']) ?> <?= $sub['label'] ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: 
                    $itemScript = basename($item['link']);
                    $isActive = ($currentScript === $itemScript);
                ?>
                    <a href="<?= sayf($item['link']) ?>" class="panel-nav-link <?= $isActive ? 'aktif' : '' ?>">
                        <?= svgIkon($item['icon']) ?>
                        <span><?= $item['label'] ?></span>
                    </a>
                <?php 
                    endif;
                endforeach; 
                ?>
            </nav>
            <script>
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.nav-dropdown-kapsayici')) {
                    document.querySelectorAll('.nav-dropdown-menu').forEach(i => i.classList.remove('goster'));
                }
            });
            </script>
            <div class="topbar-sag">
                <?= $bildirim_html ?>
                <div class="avatar-dropdown-kapsayici">
                    <button id="avatar-toggle" class="kullanici-avatar" aria-haspopup="true" aria-expanded="false" title="Profil Menüsü">
                        <?php if (!empty($_SESSION['profil_resmi'])): ?>
                            <img src="<?= sayf('uploads/avatars/' . e($_SESSION['profil_resmi'])) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?= initialsAvatar($_SESSION['kullanici_ad'] ?? 'U') ?>
                        <?php endif; ?>
                    </button>
                    <div class="avatar-dropdown-menu" id="avatar-menu">
                        <div class="dropdown-header">
                            <strong><?= e($_SESSION['kullanici_ad'] ?? '') ?></strong>
                            <span class="rol-rozet <?= e($rol) ?>" style="margin-top:0.25rem;display:inline-block;"><?= e(rolAdi($rol)) ?></span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?= sayf($rol === 'admin' ? 'admin/profile.php' : ($rol === 'eczane' ? 'pharmacy/profile.php' : 'user/profile.php')) ?>" class="dropdown-item">
                            Profil ve Ayarlar
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?= sayf('auth/logout.php') ?>" class="dropdown-item cikis-item">
                            Çıkış Yap
                        </a>
                    </div>
                </div>
            </div>
        </header>

<?php else: ?>
<nav class="corp-navbar" id="navbar">
    <div class="corp-navbar-container">
        <a href="<?= sayf('/') ?>" class="corp-nav-logo">
            <div style="width:36px; height:36px; background:var(--renk-birincil); border-radius:4px; display:flex; align-items:center; justify-content:center; color:#fff;">
                <?= svgIkon('plus') ?>
            </div>
            <?= APP_NAME ?>
        </a>
        <div class="corp-nav-links">
            <a href="<?= sayf('/') ?>" class="<?= aktifMi('index.php') ?>">Ana Sayfa</a>
            <a href="<?= sayf('pages/about') ?>" class="<?= aktifMi('about.php') ?>">Hakkımızda</a>
            <a href="<?= sayf('pages/contact') ?>" class="<?= aktifMi('contact.php') ?>">İletişim</a>
        </div>
        <div class="corp-nav-buttons">
            <?= $bildirim_html ?>
            <div style="width:1px; height:24px; background:var(--kenar-rengi); margin:0 0.5rem;"></div>
            <?php if (!empty($_SESSION['kullanici_id'])): 
                $pUrl = match($_SESSION['rol'] ?? '') {
                    'admin' => 'admin/index.php',
                    'eczane' => 'pharmacy/index.php',
                    'kullanici' => 'user/index.php',
                    default => 'auth/login.php',
                };
            ?>
                <a href="<?= sayf($pUrl) ?>" class="btn btn-birincil btn-sm">Panele Git</a>
            <?php else: ?>
                <a href="<?= sayf('auth/login') ?>" class="btn btn-gri btn-sm">Giriş</a>
                <a href="<?= sayf('auth/register') ?>" class="btn btn-birincil btn-sm">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<?php endif; ?>
