<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
if (!empty($_SESSION['kullanici_id'])) {
    yonlendir(sayf(panelUrl(mevcutRol())));
}
$mesaj = '';
$hata = '';
$islem = $_GET['islem'] ?? 'istek';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($islem === 'istek') {
        $email = trim($_POST['email'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = db()->prepare("SELECT id FROM kullanicilar WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $exp = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $update = db()->prepare("UPDATE kullanicilar SET sifre_token = ?, sifre_token_exp = ? WHERE id = ?");
                $update->execute([$token, $exp, $user['id']]);
                $mesaj = "Şifre sıfırlama talebiniz alındı. (Simülasyon: <a href='forgot_password.php?islem=sifirla&token=$token' style='color:var(--auth-accent); text-decoration:underline;'>Buraya Tıklayın</a>)";
            } else {
                $mesaj = "E-posta adresiniz kayıtlıysa, size bir bağlantı gönderdik.";
            }
        } else {
            $hata = 'Geçerli bir e-posta adresi giriniz.';
        }
    } elseif ($islem === 'sifirla') {
        $token = $_POST['token'] ?? '';
        $sifre = $_POST['sifre'] ?? '';
        $sifreT = $_POST['sifre_tekrar'] ?? '';
        if (!$token || !$sifre || !$sifreT) {
            $hata = 'Tüm alanları doldurunuz.';
        } elseif ($sifre !== $sifreT) {
            $hata = 'Şifreler eşleşmiyor.';
        } elseif (strlen($sifre) < 8) {
            $hata = 'Şifre en az 8 karakter olmalıdır.';
        } else {
            $stmt = db()->prepare("SELECT id FROM kullanicilar WHERE sifre_token = ? AND sifre_token_exp > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            if ($user) {
                $hash = password_hash($sifre, PASSWORD_DEFAULT);
                $update = db()->prepare("UPDATE kullanicilar SET sifre = ?, sifre_token = NULL, sifre_token_exp = NULL WHERE id = ?");
                $update->execute([$hash, $user['id']]);
                $mesaj = "Şifreniz başarıyla değiştirildi! Artık yeni şifrenizle giriş yapabilirsiniz.";
                $islem = 'tamamlandi';
            } else {
                $hata = 'Geçersiz veya süresi dolmuş bağlantı.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Kurtarma — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= sayf('assets/css/style.css') ?>?v=<?= time() ?>">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <div class="auth-brand-side">
            <div class="auth-brand-content">
                <div style="background:var(--auth-accent); width:72px; height:72px; border-radius:22px; display:flex; align-items:center; justify-content:center; margin-bottom:3rem; box-shadow: 0 15px 35px -10px var(--auth-accent-glow); color:white;">
                    <?= svgIkon('key') ?>
                </div>
                <h1>Hızlı Şifre<br>Kurtarma Portalı.</h1>
                <p>Erişiminizi kaybettiyseniz endişelenmeyin. Kayıtlı e-posta adresinizi kullanarak güvenli bir şekilde şifrenizi yenileyebilirsiniz.</p>
            </div>
        </div>
        <div class="auth-form-side">
            <div class="auth-header">
                <h2><?php 
                    if ($islem === 'istek') echo 'Şifremi Unuttum';
                    elseif ($islem === 'sifirla') echo 'Yeni Şifre';
                    else echo 'İşlem Başarılı';
                ?></h2>
                <p><?php 
                    if ($islem === 'istek') echo 'E-posta adresini girerek başla.';
                    elseif ($islem === 'sifirla') echo 'Yeni, güvenli bir şifre belirle.';
                    else echo 'Şifreniz güncellendi.';
                ?></p>
            </div>
            <?php if ($hata): ?>
                <div class="auth-notif auth-notif-danger">
                    <?= svgIkon('alert-circle') ?>
                    <span><?= htmlspecialchars($hata, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>
            <?php if ($mesaj): ?>
                <div class="auth-notif auth-notif-success" <?php if($islem === 'tamamlandi') echo 'style="flex-direction:column; align-items:flex-start; gap:1.5rem;"'; ?>>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <?= svgIkon('check-circle') ?>
                        <span><?= $mesaj ?></span>
                    </div>
                    <?php if($islem === 'tamamlandi'): ?>
                        <a href="<?= sayf('auth/login.php') ?>" class="auth-btn-primary" style="margin-top:0; width:auto; padding:0.85rem 2.5rem;">Giriş Yap</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($islem === 'istek'): ?>
                <form action="" method="post">
                    <div class="auth-form-group">
                        <label class="auth-label">E-posta Adresi</label>
                        <div class="auth-input-box">
                            <?= svgIkon('mail') ?>
                            <input class="auth-input" type="email" name="email" required placeholder="ornek@mail.com">
                        </div>
                    </div>
                    <button type="submit" class="auth-btn-primary">
                        Kurtarma Bağlantısı Gönder <?= svgIkon('arrow-right') ?>
                    </button>
                </form>
            <?php elseif ($islem === 'sifirla'): ?>
                <form action="" method="post">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
                    <div class="auth-form-group">
                        <label class="auth-label">Yeni Şifre</label>
                        <div class="auth-input-box">
                            <?= svgIkon('lock') ?>
                            <input class="auth-input" type="password" name="sifre" required placeholder="Min. 8 karakter" id="reg_sifre">
                        </div>
                    </div>
                    <div class="auth-form-group">
                        <label class="auth-label">Şifre Tekrar</label>
                        <div class="auth-input-box">
                            <?= svgIkon('check') ?>
                            <input class="auth-input" type="password" name="sifre_tekrar" required placeholder="••••••••">
                        </div>
                    </div>
                    <div id="passwordRulesHint" style="margin-bottom:2rem; display:none;"></div>
                    <button type="submit" class="auth-btn-primary">
                        Şifremi Sıfırla <?= svgIkon('arrow-right') ?>
                    </button>
                </form>
            <?php endif; ?>
            <?php if ($islem !== 'tamamlandi'): ?>
            <div class="auth-footer">
                <a href="<?= sayf('auth/login.php') ?>" class="auth-link" style="color: var(--auth-text-muted); display:inline-flex; align-items:center; gap:0.5rem;">
                    <?= svgIkon('arrow-left') ?> Giriş Ekranına Dön
                </a>
            </div>
            <?php endif; ?>
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
