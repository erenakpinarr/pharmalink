<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('eczane');
$baslik = 'Profil & Ayarlar — ' . APP_NAME;
$kid = mevcutKullaniciId();
$eczStmt = db()->prepare("SELECT * FROM eczaneler WHERE kullanici_id=? AND durum='onaylandi' LIMIT 1");
$eczStmt->execute([$kid]);
$eczane = $eczStmt->fetch();
if (!$eczane) yonlendir(sayf('pharmacy/index.php'));
$kulStmt = db()->prepare("SELECT * FROM kullanicilar WHERE id=?");
$kulStmt->execute([$kid]);
$kullanici = $kulStmt->fetch();

$saatlerStmt = db()->prepare("SELECT * FROM eczane_calisma_saatleri WHERE eczane_id=? ORDER BY gun ASC");
$saatlerStmt->execute([$eczane['id']]);
$dbSaatler = $saatlerStmt->fetchAll(PDO::FETCH_ASSOC);
$saatler = [];
foreach ($dbSaatler as $s) {
    // 1: Pzt, 2: Sal, 3: Çar, 4: Per, 5: Cum, 6: Cmt, 7: Paz
    $saatler[$s['gun']] = $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $islem = $_POST['islem'] ?? '';
    if ($islem === 'profil_guncelle') {
        $eczaneAdi      = trim($_POST['eczane_adi'] ?? '');
        $adres          = trim($_POST['adres'] ?? '');
        $sehir          = trim($_POST['sehir'] ?? '');
        $ilce           = trim($_POST['ilce'] ?? '');
        $telefon        = trim($_POST['telefon'] ?? '');
        $calismaSaatleri = trim($_POST['calisma_saatleri'] ?? '');
        $haritaLinki    = trim($_POST['harita_linki'] ?? '');
        $enlem          = trim($_POST['enlem'] ?? '') ?: null;
        $boylam         = trim($_POST['boylam'] ?? '') ?: null;
        $nobetci        = isset($_POST['nobetci']) ? 1 : 0;
        if (!$eczaneAdi || !$adres || !$sehir || !$ilce) {
            flashMesajAyarla('tehlike', 'Zorunlu alanları doldurunuz.');
        } else {
            if ($enlem !== null && ($enlem < -90 || $enlem > 90)) $enlem = null;
            if ($boylam !== null && ($boylam < -180 || $boylam > 180)) $boylam = null;
            $enlem = ($enlem !== null && is_numeric($enlem)) ? (float)$enlem : null;
            $boylam = ($boylam !== null && is_numeric($boylam)) ? (float)$boylam : null;
            $stmt = db()->prepare(
                "UPDATE eczaneler SET eczane_adi=?, adres=?, sehir=?, ilce=?, telefon=?,
                 calisma_saatleri=?, harita_linki=?, enlem=?, boylam=?, nobetci=?
                 WHERE id=?"
            );
            $stmt->execute([$eczaneAdi, $adres, $sehir, $ilce, $telefon,
                            $calismaSaatleri, $haritaLinki, $enlem, $boylam, $nobetci, $eczane['id']]);
            
            // Çalışma Saatleri Grid Logics
            $haftaninGunleri = [1, 2, 3, 4, 5, 6, 7];
            foreach ($haftaninGunleri as $gun) {
                $acilis = !empty($_POST["acilis_$gun"]) ? $_POST["acilis_$gun"] : null;
                $kapanis = !empty($_POST["kapanis_$gun"]) ? $_POST["kapanis_$gun"] : null;
                $kapali = isset($_POST["kapali_$gun"]) ? 1 : 0;
                
                $check = db()->prepare("SELECT id FROM eczane_calisma_saatleri WHERE eczane_id=? AND gun=?");
                $check->execute([$eczane['id'], $gun]);
                $varB = $check->fetchColumn();
                
                if ($varB) {
                    db()->prepare("UPDATE eczane_calisma_saatleri SET acilis=?, kapanis=?, kapali=? WHERE id=?")->execute([$acilis, $kapanis, $kapali, $varB]);
                } else {
                    db()->prepare("INSERT INTO eczane_calisma_saatleri (eczane_id, gun, acilis, kapanis, kapali) VALUES (?, ?, ?, ?, ?)")->execute([$eczane['id'], $gun, $acilis, $kapanis, $kapali]);
                }
            }

            flashMesajAyarla('basari', 'Profil bilgileri güncellendi.');
        }
        yonlendir(sayf('pharmacy/profile.php'));
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
        yonlendir(sayf('pharmacy/profile.php'));
    }
    if ($islem === 'kisisel_bilgiler') {
        $ad      = trim($_POST['ad'] ?? '');
        $soyad   = trim($_POST['soyad'] ?? '');
        $telefon = trim($_POST['yetkili_telefon'] ?? '') ?: null;
        if (!$ad || !$soyad) {
            flashMesajAyarla('tehlike', 'Yetkili ad ve soyad zorunludur.');
        } else {
            db()->prepare("UPDATE kullanicilar SET ad=?, soyad=?, telefon=? WHERE id=?")->execute([$ad, $soyad, $telefon, $kid]);
            $_SESSION['kullanici_ad'] = $ad . ' ' . $soyad;
            flashMesajAyarla('basari', 'Yetkili bilgileri güncellendi.');
        }
        yonlendir(sayf('pharmacy/profile.php'));
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
        yonlendir(sayf('pharmacy/profile.php'));
    }
}
?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places&language=tr&v=weekly&callback=initMap" defer></script>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('settings') ?> Profil & Ayarlar</h1>
    </div>
    <div style="max-width: 1000px; margin: 0 auto;">
        <div class="layout-stacked">
            
            <div style="display:flex;flex-direction:column;gap:1.5rem;">
                
                <div class="kart" style="text-align:center;padding:2rem;">
                    <div style="width:120px;height:120px;margin:0 auto 1.5rem;position:relative;">
                        <?php if ($kullanici['profil_resmi']): ?>
                            <img src="<?= sayf('uploads/avatars/' . $kullanici['profil_resmi']) ?>" 
                                 style="width:100%;height:100%;border-radius:50%;object-fit:cover;border:3px solid var(--renk-ikincil);">
                        <?php else: ?>
                            <div style="width:100%;height:100%;border-radius:50%;background:var(--arkaplan-hover);display:flex;align-items:center;justify-content:center;font-size:3rem;color:var(--renk-ikincil);border:2px dashed var(--kenar-rengi);">
                                <?= initialsAvatar($_SESSION['kullanici_ad'] ?? 'U') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="islem" value="profil_resmi_yukle">
                        <label class="btn btn-gri btn-sm" style="cursor:pointer;justify-content:center;width:100%;">
                            <?= svgIkon('upload') ?> Fotoğraf Değiştir
                            <input type="file" name="avatar" accept="image/*" style="display:none;" onchange="this.form.submit()">
                        </label>
                    </form>
                </div>
                
                <div class="kart">
                    <div class="kart-baslik">
                        <h2><?= svgIkon('user') ?> Yetkili Bilgileri</h2>
                    </div>
                    <div class="kart-govde">
                        <form method="post">
                            <input type="hidden" name="islem" value="kisisel_bilgiler">
                            <div class="form-grid">
                                <div class="form-grup">
                                    <label class="form-etiket">Ad *</label>
                                    <input class="form-giris" type="text" name="ad" value="<?= e($kullanici['ad']) ?>" required>
                                </div>
                                <div class="form-grup">
                                    <label class="form-etiket">Soyad *</label>
                                    <input class="form-giris" type="text" name="soyad" value="<?= e($kullanici['soyad']) ?>" required>
                                </div>
                                <div class="form-grup tam-satir">
                                    <label class="form-etiket">Kişisel Telefon</label>
                                    <input class="form-giris" type="tel" name="yetkili_telefon" value="<?= e($kullanici['telefon'] ?? '') ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-birincil mt-1 w-full justify-center">
                                <?= svgIkon('check') ?> Güncelle
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="kart">
                    <div class="kart-baslik">
                        <h2><?= svgIkon('lock') ?> Güvenlik</h2>
                    </div>
                    <div class="kart-govde">
                        <form method="post">
                            <input type="hidden" name="islem" value="sifre_degistir">
                            <div class="form-grid">
                                <div class="form-grup tam-satir">
                                    <label class="form-etiket">Mevcut Şifre</label>
                                    <input class="form-giris" type="password" name="eski_sifre" required>
                                </div>
                                <div class="form-grup">
                                    <label class="form-etiket">Yeni Şifre</label>
                                    <input class="form-giris" type="password" name="yeni_sifre" minlength="6" required>
                                </div>
                                <div class="form-grup">
                                    <label class="form-etiket">Tekrar</label>
                                    <input class="form-giris" type="password" name="sifre_tekrar" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-uyari mt-1 w-full justify-center">
                                <?= svgIkon('key') ?> Şifreyi Güncelle
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div style="display:flex;flex-direction:column;gap:1.5rem;">
                <div class="kart">
                    <div class="kart-baslik">
                        <h2><?= svgIkon('building') ?> Kurumsal Eczane Profili</h2>
                    </div>
                    <div class="kart-govde">
                        <form method="post">
                            <input type="hidden" name="islem" value="profil_guncelle">
                            <div class="form-grid">
                                <div class="form-grup tam-satir">
                                    <label class="form-etiket">Eczane Adı *</label>
                                    <input class="form-giris" type="text" name="eczane_adi" value="<?= e($eczane['eczane_adi']) ?>" required>
                                </div>
                                <div class="form-grup">
                                    <label class="form-etiket">Şehir *</label>
                                    <input class="form-giris" type="text" name="sehir" value="<?= e($eczane['sehir']) ?>" required>
                                </div>
                                <div class="form-grup">
                                    <label class="form-etiket">İlçe *</label>
                                    <input class="form-giris" type="text" name="ilce" value="<?= e($eczane['ilce']) ?>" required>
                                </div>
                                <div class="form-grup tam-satir">
                                    <label class="form-etiket">Tam Adres *</label>
                                    <textarea class="form-alani" name="adres" rows="2" required><?= e($eczane['adres']) ?></textarea>
                                </div>
                                <div class="form-grup">
                                    <label class="form-etiket">Telefon</label>
                                    <input class="form-giris" type="tel" name="telefon" value="<?= e($eczane['telefon'] ?? '') ?>">
                                </div>
                                <div class="form-grup">
                                    <label class="form-etiket">Genel Çalışma Saatleri Metni</label>
                                    <input class="form-giris" type="text" name="calisma_saatleri" value="<?= e($eczane['calisma_saatleri'] ?? '') ?>" placeholder="Örn: Hafta içi 09:00 - 19:00">
                                </div>
                                
                                <div class="form-grup tam-satir">
                                    <label class="form-etiket" style="font-size:1.1rem;">Haftalık Çalışma Saatleri (Grid)</label>
                                    <div style="display:flex; flex-direction:column; gap:0.5rem; background:#f8fafc; padding:1rem; border-radius:8px; border:1px solid var(--kenar-rengi);">
                                        <?php 
                                        $gunAdlari = [1=>'Pazartesi', 2=>'Salı', 3=>'Çarşamba', 4=>'Perşembe', 5=>'Cuma', 6=>'Cumartesi', 7=>'Pazar'];
                                        foreach($gunAdlari as $k => $ad): 
                                            $s = $saatler[$k] ?? ['acilis'=>'', 'kapanis'=>'', 'kapali'=>0];
                                            $kapaliCheck = $s['kapali'] ? 'checked' : '';
                                        ?>
                                        <div style="display:flex; align-items:center; gap:1rem; padding:0.5rem 0; border-bottom:1px solid #e2e8f0;">
                                            <div style="width:100px; font-weight:600;"><?= $ad ?></div>
                                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                                <input type="time" name="acilis_<?= $k ?>" value="<?= e(substr($s['acilis']??'', 0, 5)) ?>" class="form-giris" style="width:110px;" <?= $s['kapali'] ? 'disabled' : '' ?> id="acilis_<?= $k ?>">
                                                <span>-</span>
                                                <input type="time" name="kapanis_<?= $k ?>" value="<?= e(substr($s['kapanis']??'', 0, 5)) ?>" class="form-giris" style="width:110px;" <?= $s['kapali'] ? 'disabled' : '' ?> id="kapanis_<?= $k ?>">
                                            </div>
                                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                                <input type="checkbox" name="kapali_<?= $k ?>" id="kapali_<?= $k ?>" value="1" <?= $kapaliCheck ?> onchange="document.getElementById('acilis_<?= $k ?>').disabled = this.checked; document.getElementById('kapanis_<?= $k ?>').disabled = this.checked;">
                                                <label for="kapali_<?= $k ?>" style="color:var(--renk-uyari); font-weight:600;">Kapalı</label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="form-grup" style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                                    <div>
                                        <label class="form-etiket">Enlem</label>
                                        <input class="form-giris" type="text" name="enlem" id="enlem" value="<?= e($eczane['enlem'] ?? '') ?>" readonly style="text-align:center;font-family:monospace;">
                                    </div>
                                    <div>
                                        <label class="form-etiket">Boylam</label>
                                        <input class="form-giris" type="text" name="boylam" id="boylam" value="<?= e($eczane['boylam'] ?? '') ?>" readonly style="text-align:center;font-family:monospace;">
                                    </div>
                                </div>
                                
                                <div class="form-grup tam-satir">
                                    <label class="form-etiket">Konum Seçici (Haritaya Tıklayın)</label>
                                    <div id="mapPicker" style="height:350px;width:100%;border-radius:12px;border:1px solid var(--kenar-rengi);background:#f8fafc;"></div>
                                    <p style="font-size:.75rem;color:var(--metin-uc);margin-top:0.75rem;display:flex;align-items:center;gap:0.5rem;">
                                        <?= svgIkon('info') ?> Eczanenizin tam konumunu harita üzerinde işaretleyin.
                                    </p>
                                </div>
                                
                                <div class="form-grup tam-satir" style="margin-top:0.5rem;">
                                    <div style="background:var(--arkaplan-hover);padding:1.25rem;border-radius:12px;border:1px solid var(--kenar-rengi);">
                                        <label class="nobetci-toggle" style="cursor:pointer;">
                                            <span class="toggle-switch">
                                                <input type="checkbox" name="nobetci" value="1" <?= $eczane['nobetci'] ? 'checked' : '' ?>>
                                                <span class="toggle-kaydir"></span>
                                            </span>
                                            <span style="font-weight:600;color:var(--metin-birincil);margin-left:0.5rem;">Şu an Nöbetçiyim</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-birincil mt-2 w-full justify-center" style="height:50px;font-size:1.1rem;">
                                <?= svgIkon('check') ?> Değişiklikleri Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
let pickerMap, pickerMarker;
function initMap() {
    const initialLat = parseFloat("<?= $eczane['enlem'] ?: 39.9334 ?>");
    const initialLng = parseFloat("<?= $eczane['boylam'] ?: 32.8597 ?>");
    const hasLocation = <?= ($eczane['enlem'] && $eczane['boylam']) ? 'true' : 'false' ?>;
    const mapOptions = {
        center: { lat: initialLat, lng: initialLng },
        zoom: hasLocation ? 16 : 6,
        styles: [
            { "featureType": "poi", "stylers": [{ "visibility": "off" }] },
            { "featureType": "transit", "stylers": [{ "visibility": "off" }] }
        ],
        mapTypeControl: false,
        streetViewControl: false
    };
    pickerMap = new google.maps.Map(document.getElementById("mapPicker"), mapOptions);
    if (hasLocation) {
        pickerMarker = new google.maps.Marker({
            position: { lat: initialLat, lng: initialLng },
            map: pickerMap,
            draggable: true
        });
        pickerMarker.addListener('dragend', function() {
            updateInputs(pickerMarker.getPosition());
        });
    }
    pickerMap.addListener("click", (e) => {
        const coords = e.latLng;
        placeMarker(coords);
    });
}
function placeMarker(latLng) {
    if (pickerMarker) {
        pickerMarker.setPosition(latLng);
    } else {
        pickerMarker = new google.maps.Marker({
            position: latLng,
            map: pickerMap,
            draggable: true
        });
        pickerMarker.addListener('dragend', function() {
            updateInputs(pickerMarker.getPosition());
        });
    }
    updateInputs(latLng);
}
function updateInputs(latLng) {
    document.getElementById('enlem').value = latLng.lat().toFixed(7);
    document.getElementById('boylam').value = latLng.lng().toFixed(7);
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
