<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('admin');
$baslik = 'Profil & Ayarlar — ' . APP_NAME;
$kid = mevcutKullaniciId();
$kulStmt = db()->prepare("SELECT * FROM kullanicilar WHERE id=?");
$kulStmt->execute([$kid]);
$kullanici = $kulStmt->fetch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $islem = $_POST['islem'] ?? '';
    if ($islem === 'kisisel_bilgiler') {
        $ad      = trim($_POST['ad'] ?? '');
        $soyad   = trim($_POST['soyad'] ?? '');
        $telefon = trim($_POST['telefon'] ?? '') ?: null;
        if (!$ad || !$soyad) {
            flashMesajAyarla('tehlike', 'Ad ve soyad zorunludur.');
        } else {
            db()->prepare("UPDATE kullanicilar SET ad=?, soyad=?, telefon=? WHERE id=?")->execute([$ad, $soyad, $telefon, $kid]);
            $_SESSION['kullanici_ad'] = $ad . ' ' . $soyad;
            flashMesajAyarla('basari', 'Kişisel bilgiler güncellendi.');
        }
        yonlendir(sayf('admin/profile.php'));
    }
    if ($islem === 'profil_resmi_yukle' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $maxBoyut = 2 * 1024 * 1024;
        $izinliTurler = ['image/jpeg', 'image/png', 'image/webp'];
        $tmpDosya = $_FILES['avatar']['tmp_name'];
        $dosyaTuru = $_FILES['avatar']['type'];
        $dosyaBoyutu = $_FILES['avatar']['size'];
        if ($dosyaBoyutu > $maxBoyut) {
            flashMesajAyarla('tehlike', 'Dosya boyutu 2MB sınırını aşıyor.');
        } elseif (!in_array($dosyaTuru, $izinliTurler)) {
            flashMesajAyarla('tehlike', 'Sadece JPG, PNG veya WEBP yükleyebilirsiniz.');
        } else {
            $uzanti = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $yeniAd = 'avatar_' . $kid . '_' . time() . '.' . $uzanti;
            $hedef_dizin = __DIR__ . '/../uploads/avatars/';
            if (move_uploaded_file($tmpDosya, $hedef_dizin . $yeniAd)) {
                if ($kullanici['profil_resmi'] && file_exists($hedef_dizin . $kullanici['profil_resmi'])) {
                    @unlink($hedef_dizin . $kullanici['profil_resmi']);
                }
                db()->prepare("UPDATE kullanicilar SET profil_resmi=? WHERE id=?")->execute([$yeniAd, $kid]);
                $_SESSION['profil_resmi'] = $yeniAd;
                flashMesajAyarla('basari', 'Profil fotoğrafı güncellendi.');
            } else {
                flashMesajAyarla('tehlike', 'Dosya yüklenirken bir sorun oluştu.');
            }
        }
        yonlendir(sayf('admin/profile.php'));
    }
    if ($islem === 'sifre_degistir') {
        $eskiSifre = $_POST['eski_sifre'] ?? '';
        $yeniSifre = $_POST['yeni_sifre'] ?? '';
        $tekrar    = $_POST['sifre_tekrar'] ?? '';
        if (!password_verify($eskiSifre, $kullanici['sifre'])) {
            flashMesajAyarla('tehlike', 'Mevcut şifre hatalı.');
        } elseif (strlen($yeniSifre) < 6) {
            flashMesajAyarla('tehlike', 'Yeni şifre en az 6 karakter olmalıdır.');
        } elseif ($yeniSifre !== $tekrar) {
            flashMesajAyarla('tehlike', 'Şifreler eşleşmiyor.');
        } else {
            $hash = password_hash($yeniSifre, PASSWORD_DEFAULT);
            db()->prepare("UPDATE kullanicilar SET sifre=? WHERE id=?")->execute([$hash, $kid]);
            flashMesajAyarla('basari', 'Şifre başarıyla değiştirildi.');
        }
        yonlendir(sayf('admin/profile.php'));
    }
}
include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('settings') ?> Profil & Ayarlar</h1>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:1.5rem;align-items:start;">
        <div style="display:flex;flex-direction:column;gap:1.5rem;">
            <div class="kart" style="text-align:center;padding:2rem;">
                <div style="margin:0 auto 1rem; width:120px; height:120px; border-radius:50%; background:var(--arkaplan);display:flex;align-items:center;justify-content:center;overflow:hidden;border:4px solid var(--kenar-rengi);">
                    <?php if ($kullanici['profil_resmi']): ?>
                        <img src="<?= sayf('uploads/avatars/' . e($kullanici['profil_resmi'])) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <span style="font-size:3rem;font-weight:700;color:var(--renk-ikincil);"><?= initialsAvatar($kullanici['ad'] . ' ' . $kullanici['soyad']) ?></span>
                    <?php endif; ?>
                </div>
                <h3 style="margin-bottom:.25rem;"><?= e($kullanici['ad'] . ' ' . $kullanici['soyad']) ?></h3>
                <div style="color:var(--metin-ikincil);font-size:.875rem;margin-bottom:1.5rem;"><?= e($kullanici['email']) ?></div>
                <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:.5rem;">
                    <input type="hidden" name="islem" value="profil_resmi_yukle">
                    <label class="btn btn-gri btn-sm" style="cursor:pointer;justify-content:center;">
                        <?= svgIkon('upload') ?> Fotoğraf Seç
                        <input type="file" name="avatar" accept="image/*" style="display:none;" onchange="this.form.submit()">
                    </label>
                </form>
            </div>
            <div class="kart">
                <div class="kart-baslik">
                    <h2><?= svgIkon('key') ?> Şifre Değiştir</h2>
                </div>
                <div class="kart-govde">
                    <form method="post">
                        <input type="hidden" name="islem" value="sifre_degistir">
                        <div class="form-grid">
                            <div class="form-grup tam-satir">
                                <label class="form-etiket" for="eski_sifre">Mevcut Şifre</label>
                                <input class="form-giris" type="password" name="eski_sifre" id="eski_sifre" required>
                            </div>
                            <div class="form-grup">
                                <label class="form-etiket" for="yeni_sifre">Yeni Şifre</label>
                                <input class="form-giris" type="password" name="yeni_sifre" id="yeni_sifre" minlength="6" required>
                            </div>
                            <div class="form-grup">
                                <label class="form-etiket" for="sifre_tekrar">Yeni Şifre Tekrar</label>
                                <input class="form-giris" type="password" name="sifre_tekrar" id="sifre_tekrar" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-uyari mt-1 w-full justify-center">
                            <?= svgIkon('key') ?> Şifreyi Güncelle
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="kart">
            <div class="kart-baslik">
                <h2><?= svgIkon('user') ?> Kişisel Bilgiler (Yönetici)</h2>
            </div>
            <div class="kart-govde">
                <form method="post">
                    <input type="hidden" name="islem" value="kisisel_bilgiler">
                    <div class="form-grid">
                        <div class="form-grup">
                            <label class="form-etiket" for="admin_ad">Ad *</label>
                            <input class="form-giris" type="text" name="ad" id="admin_ad" value="<?= e($kullanici['ad']) ?>" required>
                        </div>
                        <div class="form-grup">
                            <label class="form-etiket" for="admin_soyad">Soyad *</label>
                            <input class="form-giris" type="text" name="soyad" id="admin_soyad" value="<?= e($kullanici['soyad']) ?>" required>
                        </div>
                        <div class="form-grup tam-satir">
                            <label class="form-etiket">E-posta (Değiştirilemez)</label>
                            <input class="form-giris" type="email" value="<?= e($kullanici['email']) ?>" disabled style="background:var(--arkaplan-hover);opacity:.7;">
                        </div>
                        <div class="form-grup tam-satir">
                            <label class="form-etiket" for="admin_tel">Telefon Numarası</label>
                            <input class="form-giris" type="tel" name="telefon" id="admin_tel" value="<?= e($kullanici['telefon'] ?? '') ?>" placeholder="05XX XXX XX XX">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-birincil mt-2">
                        <?= svgIkon('check') ?> Bilgileri Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
