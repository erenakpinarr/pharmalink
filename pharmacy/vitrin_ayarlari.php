<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
girisKontrol('eczane');

$kullaniciId = mevcutKullaniciId();
$hata = '';
$basari = '';

// Eczane verilerini çek
$stmt = db()->prepare("SELECT * FROM eczaneler WHERE kullanici_id = ? LIMIT 1");
$stmt->execute([$kullaniciId]);
$eczane = $stmt->fetch();

if (!$eczane) {
    die("Eczane bilgileri bulunamadı.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $slug = trim($_POST['slug'] ?? '');
    $hakkimizda = trim($_POST['hakkimizda'] ?? '');
    $tema_rengi = trim($_POST['tema_rengi'] ?? '#6366f1');
    $vitrin_aktif = isset($_POST['vitrin_aktif']) ? 1 : 0;
    
    // Slug boşsa eczane adından üret
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $eczane['eczane_adi'])));
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug)));
    }

    // Banner yükleme işlemi
    $banner_resmi = $eczane['banner_resmi'];
    if (!empty($_FILES['banner']['name'])) {
        $dosya = $_FILES['banner'];
        $uzanti = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));
        $izinli = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($uzanti, $izinli)) {
            $yeniAd = 'banner_' . $eczane['id'] . '_' . time() . '.' . $uzanti;
            $hedef = __DIR__ . '/../uploads/banners/' . $yeniAd;
            
            if (!is_dir(__DIR__ . '/../uploads/banners/')) {
                mkdir(__DIR__ . '/../uploads/banners/', 0755, true);
            }
            
            if (move_uploaded_file($dosya['tmp_name'], $hedef)) {
                $banner_resmi = $yeniAd;
            } else {
                $hata = 'Banner yüklenemedi.';
            }
        } else {
            $hata = 'Geçersiz dosya formatı (Sadece JPG, PNG, WebP).';
        }
    }

    if (!$hata) {
        try {
            $update = db()->prepare("
                UPDATE eczaneler 
                SET slug = ?, hakkimizda = ?, banner_resmi = ?, tema_rengi = ?, vitrin_aktif = ? 
                WHERE id = ?
            ");
            $update->execute([$slug, $hakkimizda, $banner_resmi, $tema_rengi, $vitrin_aktif, $eczane['id']]);
            $basari = 'Vitrin ayarları başarıyla güncellendi.';
            
            // Veriyi tazele
            $stmt->execute([$kullaniciId]);
            $eczane = $stmt->fetch();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $hata = 'Bu URL (slug) zaten başka bir eczane tarafından kullanılıyor.';
            } else {
                $hata = 'Güncelleme sırasında bir hata oluştu: ' . $e->getMessage();
            }
        }
    }
}

$baslik = 'Vitrin Ayarları — ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<main class="ana-icerik">
    <div class="sayfa-baslik">
        <div class="sol">
            <h1><?= svgIkon('eye') ?> Vitrin Ayarları</h1>
            <p>Eczaneniz için "site içinde site" konseptinde kişisel bir sayfa oluşturun.</p>
        </div>
        <div class="sag">
            <?php if ($eczane['vitrin_aktif'] && $eczane['slug']): ?>
                <a href="<?= sayf('p/' . $eczane['slug']) ?>" target="_blank" class="btn btn-birincil">
                    <?= svgIkon('navigation') ?> Sayfamıza Git
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($hata): ?>
        <div class="alert alert-tehlike"><?= e($hata) ?></div>
    <?php endif; ?>
    <?php if ($basari): ?>
        <div class="alert alert-basari"><?= e($basari) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="kart" style="max-width: 900px; margin: 0 auto;">
        <?= csrf_field() ?>
        
        <div class="kart-baslik">
            <h2>Görsel ve Tema Ayarları</h2>
        </div>
        <div class="kart-govde" style="padding: 2rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div class="form-grup">
                    <label class="form-etiket">Sayfa Banner Görseli</label>
                    <div style="margin-bottom: 1rem; border-radius: 12px; overflow: hidden; height: 160px; background: #f1f5f9; border: 2px dashed #cbd5e1; display:flex; align-items:center; justify-content:center;">
                        <?php if ($eczane['banner_resmi']): ?>
                            <img src="<?= sayf('uploads/banners/' . $eczane['banner_resmi']) ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <div style="text-align:center; color:#94a3b8;">
                                <?= svgIkon('image') ?>
                                <p style="font-size:0.8rem; margin-top:0.5rem;">Banner yüklenmemiş</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="banner" class="form-giris" accept="image/*">
                    <p class="form-ipucu">Önerilen boyut: 1200x400px</p>
                </div>

                <div class="form-grup">
                    <label class="form-etiket">Tema Vurgu Rengi</label>
                    <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1rem;">
                        <input type="color" name="tema_rengi" value="<?= e($eczane['tema_rengi'] ?: '#6366f1') ?>" style="width: 64px; height: 64px; border: none; border-radius: 12px; cursor: pointer;">
                        <div>
                            <p style="font-weight: 600; font-size: 0.9rem;">Marka Renginiz</p>
                            <p style="font-size: 0.8rem; color: var(--metin-ikincil);">Buton ve başlıklar bu tonda görünecektir.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="kart-baslik" style="border-top: 1px solid var(--kenar-rengi);">
            <h2>İçerik ve URL</h2>
        </div>
        <div class="kart-govde" style="padding: 2rem;">
            <div class="form-grup">
                <label class="form-etiket">Kişisel Sayfa URL (Slug)</label>
                <div style="display: flex; align-items: center; background: var(--arkaplan-hover); border-radius: 8px; border: 1px solid var(--kenar-rengi);">
                    <span style="padding: 0 1rem; color: var(--metin-uc); font-size: 0.9rem; border-right: 1px solid var(--kenar-rengi);">pharmalink.com/p/</span>
                    <input type="text" name="slug" value="<?= e($eczane['slug']) ?>" class="form-giris" style="border:none; background:transparent;" placeholder="hayat-eczanesi">
                </div>
                <p class="form-ipucu">Boş bırakırsanız otomatik üretilir. Sadece harf, rakam ve tire kullanın.</p>
            </div>

            <div class="form-grup">
                <label class="form-etiket">Eczane Hakkında</label>
                <textarea name="hakkimizda" class="form-alani" rows="6" placeholder="Eczanenizin tarihçesi, uzmanlıkları ve hizmet anlayışını anlatın..."><?= e($eczane['hakkimizda']) ?></textarea>
            </div>

            <div class="form-grup">
                <label class="nobetci-toggle">
                    <input type="checkbox" name="vitrin_aktif" <?= $eczane['vitrin_aktif'] ? 'checked' : '' ?>>
                    <span class="slider"></span>
                    <span style="font-weight: 600;">Kişisel Sayfayı Aktifleştir</span>
                </label>
            </div>
        </div>

        <div class="kart-footer" style="padding: 1.5rem 2rem; background: #f8fafc; text-align: right;">
            <button type="submit" class="btn btn-birincil">
                <?= svgIkon('check') ?> Ayarları Kaydet
            </button>
        </div>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
