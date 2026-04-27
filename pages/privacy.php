<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';

$girisYapmisMi = !empty($_SESSION['kullanici_id']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gizlilik Politikası — <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= sayf('assets/css/style.css') ?>?v=<?= time() ?>">
    <script>
        const theme = localStorage.getItem('theme');
        if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
    <style>
        body { 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
            margin: 0; 
            background: var(--arkaplan);
            font-family: var(--font-govde);
            color: var(--metin-birincil);
        }
        
        .hero-section {
            background: linear-gradient(135deg, rgba(88, 19, 39, 0.9), rgba(159, 18, 57, 0.4)), url('https://images.unsplash.com/photo-1587854692152-cbe660dbde88?q=80&w=2669&auto=format&fit=crop') center/cover no-repeat;
            position: relative;
            color: white;
            padding: 160px 5% 6rem;
            text-align: center;
        }
        .hero-title { 
            background: none; 
            -webkit-text-fill-color: white; 
            color: white; 
            text-shadow: 0 4px 10px rgba(0,0,0,0.5); 
            font-size: 3.5rem; 
            font-weight: 800; 
            margin-bottom: 1rem; 
            font-family: var(--font-baslik);
        }
        .hero-subtitle { 
            color: #f1f5f9; 
            text-shadow: 0 2px 4px rgba(0,0,0,0.5); 
            font-size: 1.25rem; 
            font-weight: 500; 
            max-width: 700px; 
            margin: 0 auto; 
        }

        .content-section {
            padding: 5rem 5%;
            flex: 1;
        }
        
        .content-grid {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .feature-card {
            background: var(--arkaplan-kart);
            padding: 2.5rem;
            border-radius: var(--yaricap-lg);
            border: 1px solid var(--kenar-rengi);
            box-shadow: var(--golge-hafif);
            transition: var(--gecis);
        }
        .feature-card:hover {
            box-shadow: var(--golge-orta); 
            border-color: var(--renk-ikincil-acik);
            transform: translateY(-5px);
        }
        .feature-card h2 {
            color: var(--renk-birincil);
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 1rem;
            font-family: var(--font-baslik);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .feature-card p {
            color: var(--metin-ikincil);
            font-size: 1.1rem;
            line-height: 1.8;
            margin: 0;
        }

        .landing-footer {
            margin-top: auto;
            border-top: 1px solid var(--kenar-rengi);
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0) translateX(-50%); }
            40% { transform: translateY(-15px) translateX(-50%); }
            60% { transform: translateY(-7px) translateX(-50%); }
        }
        
        .scroll-down {
            position: absolute;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            animation: bounce 2s infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            text-decoration: none;
            z-index: 10;
        }
        .scroll-down:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--renk-ikincil-acik);
        }
    </style>
</head>
<body class="public-body">

<nav class="landing-navbar" id="navbar">
    <div style="display:flex; align-items:center; gap:0.5rem; font-family:var(--font-baslik); font-weight:800; font-size:1.5rem; color:#fff; text-shadow: 0 2px 4px rgba(0,0,0,0.5); z-index:10;" class="nav-logo-text">
        <div style="width:40px; height:40px; background:linear-gradient(135deg, var(--renk-ikincil), #fb7185); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff;" class="nav-logo-icon">
            <?= svgIkon('plus') ?>
        </div>
        <span class="nav-brand"><?= APP_NAME ?></span>
    </div>
    
    <div class="nav-links" style="z-index:10;">
        <a href="<?= sayf('index.php') ?>" style="color:white;text-shadow:0 1px 2px rgba(0,0,0,0.8);">Ana Sayfa</a>
        <a href="<?= sayf('pages/about.php') ?>" style="color:white;text-shadow:0 1px 2px rgba(0,0,0,0.8);">Hakkımızda</a>
        <a href="<?= sayf('pages/contact.php') ?>" style="color:white;text-shadow:0 1px 2px rgba(0,0,0,0.8);">İletişim</a>
    </div>

    <div class="nav-buttons" style="z-index:10;">
        <?php if ($girisYapmisMi): ?>
            <a href="<?= sayf('user/index.php') ?>" class="btn btn-birincil">Kullanıcı Paneli</a>
        <?php else: ?>
            <a href="<?= sayf('auth/login.php') ?>" class="btn" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid #fff;">Giriş Yap</a>
            <a href="<?= sayf('auth/register.php') ?>" class="btn btn-birincil">Kayıt Ol</a>
        <?php endif; ?>
    </div>
</nav>

<header class="hero-section">
    <div class="hero-content" style="max-width:900px; margin:0 auto; text-align:center;">
        <h1 class="hero-title">Gizlilik Politikası</h1>
        <p class="hero-subtitle">Verilerinizin güvenliği ve gizliliği bizim için en büyük önceliktir.</p>
    </div>

    <a href="#icerik-alani" class="scroll-down" aria-label="Aşağı Kaydır">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
    </a>
</header>

<main class="content-section" id="icerik-alani">
    <div class="content-grid">
        <div class="feature-card">
            <h2>1. Veri Toplama</h2>
            <p>
                Sizlere daha iyi hizmet sunabilmek adına; isim, soyisim, e-posta adresi, telefon numarası ve konum bilgilerini topluyoruz. 
                Toplanan bu veriler yalnızca ilaç arama ve talep süreçlerinin yönetilmesi amacıyla kullanılmaktadır.
            </p>
        </div>

        <div class="feature-card">
            <h2>2. Çerezler (Cookies)</h2>
            <p>
                Web sitemizdeki deneyiminizi kişiselleştirmek ve oturum yönetimi sağlamak amacıyla çerezleri kullanıyoruz. 
                Bu çerezler, tercihlerinizi saklayarak web sitemize döndüğünüzde size daha hızlı hizmet vermemize yardımcı olur.
            </p>
        </div>

        <div class="feature-card">
            <h2>3. Veri Paylaşımı</h2>
            <p>
                Verileriniz asla üçüncü şahıslara reklam veya satış amacıyla aktarılmaz. 
                Sadece yasal yükümlülüklerin gerektirdiği durumlarda veya hizmetin gerçekleştirilmesi için zorunlu olan taraflar (ilgili eczane gibi) ile paylaşılabilir.
            </p>
        </div>

        <div class="feature-card">
            <h2>4. Güvenlik</h2>
            <p>
                Platformumuz, bilgilerinizi korumak için endüstri standardı olan SSL/TLS protokollerini kullanır. 
                Ancak bilişim dünyasının doğası gereği hiçbir yöntemin %100 güvenli olmadığının bilinciyle hareket ediyor ve sistemimizi sürekli güncelliyoruz.
            </p>
        </div>
    </div>
</main>

<footer class="landing-footer">
    <div style="max-width:1200px; margin:0 auto; display:flex; flex-direction:column; align-items:center;">
        <div class="footer-logo">
            <div class="footer-logo-icon">
                <?= svgIkon('plus') ?>
            </div>
            <?= APP_NAME ?>
        </div>
        <p style="margin-bottom:2rem; font-size:1rem; color: var(--metin-birincil); opacity: 0.85;">&copy; <?= date('Y') ?> <?= APP_NAME ?>. Tüm hakları saklıdır.</p>
        <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:2rem;">
            <a href="<?= sayf('index.php') ?>">Ana Sayfa</a>
            <a href="<?= sayf('pages/about.php') ?>">Hakkımızda</a>
            <a href="<?= sayf('pages/contact.php') ?>">İletişim</a>
            <a href="<?= sayf('pages/privacy.php') ?>">Gizlilik Politikası</a>
        </div>
    </div>
</footer>

<script>
    // Navbar scroll efekti
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('navbar');
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
            nav.style.color = "var(--metin-birincil)";
            Array.from(nav.querySelectorAll('.nav-links a')).forEach(el => { el.style.color="var(--metin-birincil)"; el.style.textShadow="none"; });
            nav.querySelector('.nav-logo-text').style.color="var(--renk-birincil)";
            nav.querySelector('.nav-logo-text').style.textShadow="none";
            nav.querySelectorAll('.btn').forEach(btn => {
                if(btn.style.background.includes('rgba')) {
                    btn.style.background = 'var(--arkaplan-hover)';
                    btn.style.color = 'var(--metin-birincil)';
                    btn.style.borderColor = 'var(--kenar-rengi)';
                }
            });
            // Aktif linki koru
            const activeLink = nav.querySelector('.active-link');
            if(activeLink) activeLink.style.color = 'var(--renk-ikincil)';
        } else {
            nav.classList.remove('scrolled');
            Array.from(nav.querySelectorAll('.nav-links a')).forEach(el => { el.style.color="#fff"; el.style.textShadow="0 1px 2px rgba(0,0,0,0.8)"; });
            const activeLink = nav.querySelector('.active-link');
            if(activeLink) activeLink.style.color = 'var(--renk-ikincil)';
            
            nav.querySelector('.nav-logo-text').style.color="#fff";
            nav.querySelector('.nav-logo-text').style.textShadow="0 2px 4px rgba(0,0,0,0.5)";
            nav.querySelectorAll('.btn').forEach(btn => {
                if(btn.style.background.includes('arkaplan-hover')) {
                    btn.style.background = 'rgba(255,255,255,0.2)';
                    btn.style.color = '#fff';
                    btn.style.borderColor = '#fff';
                }
            });
        }
    });

    // Smooth Scroll JS Yedeği (Tarayıcıların CSS scroll-behavior yoksaymasını engeller)
    document.addEventListener('DOMContentLoaded', function() {
        const btnScroll = document.querySelector('.scroll-down');
        if (btnScroll) {
            btnScroll.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        }
    });
</script>
</body>
</html>
