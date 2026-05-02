<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yetkisiz Erişim — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= sayf('assets/css/style.css') ?>">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, rgba(var(--renk-birincil-rgb), 0.08), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(var(--renk-birincil-rgb), 0.05), transparent 40%),
                        var(--arkaplan);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .error-kart {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            max-width: 480px;
            width: 100%;
            border-radius: 24px;
            padding: 4rem 2rem;
            text-align: center;
        }
        .error-ikon {
            width: 80px;
            height: 80px;
            background: rgba(var(--renk-kirmizi-rgb), 0.1);
            color: var(--renk-kirmizi);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
    </style>
</head>
<body>
<div class="error-kart">
    <div class="error-ikon">
        <?= svgIkon('alert-octagon') ?>
    </div>
    <h1 style="font-size:1.75rem; color:var(--metin-birincil); margin-bottom:1rem; letter-spacing:-0.02em;">Erişim Reddedildi</h1>
    <p style="color:var(--metin-ikincil); line-height:1.6; margin-bottom:2.5rem; font-size:1.05rem;">
        Bu sayfayı görüntülemek için yetkiniz bulunmamaktadır.<br>
        Lütfen doğru hesapla giriş yaptığınızdan emin olun.
    </p>
    <?php
    $geri = match(mevcutRol()) {
        'admin'     => sayf('admin/index.php'),
        'eczane'    => sayf('pharmacy/index.php'),
        'kullanici' => sayf('user/reservations.php'),
        default     => sayf('auth/login.php'),
    };
    ?>
    <div style="display:flex; flex-direction:column; gap:1rem;">
        <a href="<?= $geri ?>" class="btn btn-birincil btn-lg" style="justify-content:center; padding:0.9rem; font-weight:700; border-radius:14px;">
            <?= svgIkon('arrow-left') ?> Panele Geri Dön
        </a>
        <a href="<?= sayf('auth/logout.php') ?>" class="btn btn-gri" style="justify-content:center; padding:0.8rem; border-radius:12px;">
            <?= svgIkon('log-out') ?> Güvenli Çıkış
        </a>
    </div>
</div>
</body>
</html>
<?php exit;
