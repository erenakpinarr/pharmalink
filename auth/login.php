<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!empty($_SESSION['kullanici_id'])) {
    yonlendir(sayf(panelUrl(mevcutRol())));
}
$hata = '';
$ip           = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ratelimitKey = 'login_fail_' . md5($ip);
$attemptLimit = 10;
$lockoutTime  = 15 * 60;
if (!isset($_SESSION[$ratelimitKey])) {
    $_SESSION[$ratelimitKey] = ['count' => 0, 'first_fail' => time()];
}
$rl = &$_SESSION[$ratelimitKey];
if ((time() - $rl['first_fail']) > $lockoutTime) {
    $rl = ['count' => 0, 'first_fail' => time()];
}
if ($rl['count'] >= $attemptLimit) {
    $kalanSure = ceil(($rl['first_fail'] + $lockoutTime - time()) / 60);
    $hata = "Çok fazla başarısız deneme. Lütfen {$kalanSure} dakika bekleyin.";
    goto render;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $submittedToken)) {
        $hata = 'Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.';
        goto render;
    }
    $email = trim($_POST['email'] ?? '');
    $sifre = trim($_POST['sifre'] ?? '');
    if (empty($email) || empty($sifre)) {
        $hata = 'Lütfen tüm alanları doldurunuz.';
    } else {
        $stmt = db()->prepare("SELECT * FROM kullanicilar WHERE email = ? AND aktif = 1 LIMIT 1");
        $stmt->execute([$email]);
        $kullanici = $stmt->fetch();
        if ($kullanici && password_verify($sifre, $kullanici['sifre'])) {
            if ($kullanici['rol'] === 'eczane') {
                $stmtE = db()->prepare("SELECT id, durum FROM eczaneler WHERE kullanici_id = ? LIMIT 1");
                $stmtE->execute([$kullanici['id']]);
                $eczane = $stmtE->fetch();
                if ($eczane && $eczane['durum'] !== 'onaylandi') {
                    $hata = match ($eczane['durum']) {
                        'beklemede' => 'Hesabınız henüz onaylanmamıştır. Yönetici onayını bekleyin.',
                        'reddedildi' => 'Hesap başvurunuz reddedilmiştir. Detay için bize ulaşın.',
                        default => 'Hesap durumu pasif.',
                    };
                    goto render;
                }
            }
            unset($_SESSION[$ratelimitKey]);
            session_regenerate_id(true);
            $_SESSION['kullanici_id']  = $kullanici['id'];
            $_SESSION['kullanici_ad']  = $kullanici['ad'] . ' ' . $kullanici['soyad'];
            $_SESSION['email']         = $kullanici['email'];
            $_SESSION['rol']           = $kullanici['rol'];
            $_SESSION['profil_resmi']  = $kullanici['profil_resmi'] ?? null;
            if ($kullanici['rol'] === 'eczane' && $eczane) {
                $_SESSION['eczane_id'] = $eczane['id'];
            }
            flashMesajAyarla('basari', 'Tekrar hoş geldiniz, ' . $kullanici['ad'] . '!');
            yonlendir(sayf(panelUrl($kullanici['rol'])));
        } else {
            $rl['count']++;
            if ($rl['count'] === 1) {
                $rl['first_fail'] = time();
            }
            $kalanDeneme = $attemptLimit - $rl['count'];
            $hata = 'E-posta veya şifre hatalı.' . ($kalanDeneme <= 5 ? " ({$kalanDeneme} deneme hakkınız kaldı)" : '');
        }
    }
}
render:
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap — <?= APP_NAME ?></title>
    <meta name="description" content="<?= APP_NAME ?>'e giriş yaparak eczane ve stok takibi işlemlerinizi yönetin.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= sayf('assets/css/style.css') ?>?v=<?= time() ?>">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-form-side">
            <div class="auth-header">
                <h2>Merhaba!</h2>
                <p>Hesabına güvenle giriş yapabilirsin.</p>
            </div>
            <?php if ($hata): ?>
                <div class="auth-notif auth-notif-danger">
                    <?= svgIkon('alert-circle') ?>
                    <span><?= htmlspecialchars($hata, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>
            <form method="post" action="">
                <?= csrf_field() ?>
                <div class="auth-form-group">
                    <label class="auth-label" for="email">E-posta Adresi</label>
                    <div class="auth-input-box">
                        <?= svgIkon('mail') ?>
                        <input class="auth-input" type="email" id="email" name="email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="ornek@mail.com" required autocomplete="email">
                    </div>
                </div>
                <div class="auth-form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.85rem;">
                        <label class="auth-label" style="margin-bottom:0;" for="sifre">Şifre</label>
                        <a href="forgot_password.php" class="auth-link" style="font-size:0.9rem;">Şifremi Unuttum</a>
                    </div>
                    <div class="auth-input-box">
                        <?= svgIkon('lock') ?>
                        <input class="auth-input" type="password" id="sifre" name="sifre" placeholder="••••••••"
                               required autocomplete="current-password">
                    </div>
                </div>
                <button type="submit" class="auth-btn-primary">
                    Giriş Yap <?= svgIkon('arrow-right') ?>
                </button>
            </form>
            <div class="auth-footer">
                Hesabın yok mu?
                <a href="<?= sayf('auth/register.php') ?>" class="auth-link">Hemen Kayıt Ol</a>
            </div>
        </div>
    </div>
    <script>
        window.PHARMA_CONFIG = {
            baseUrl: '<?= rtrim(APP_URL, '/') ?>',
            csrfToken: '<?= csrf_token() ?>',
            isLoggedIn: false,
            userRole: 'konuk'
        };
    </script>
    <script src="<?= sayf('assets/js/app.js') ?>?v=<?= time() ?>"></script>
</body>
</html>
