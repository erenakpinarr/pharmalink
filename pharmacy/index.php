<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('eczane');
$baslik = 'Eczane Yönetim Paneli — ' . APP_NAME;
$kid = mevcutKullaniciId();
$db = db();

$eczane = $db->prepare("SELECT * FROM eczaneler WHERE kullanici_id = ? LIMIT 1");
$eczane->execute([$kid]);
$eczane = $eczane->fetch();

if (!$eczane) {
    flashMesajAyarla('tehlike', 'Eczane bilgileriniz bulunamadı.');
    yonlendir(sayf('auth/logout.php'));
}

if ($eczane['durum'] !== 'onaylandi') {
    $baslik = 'Hesap Durumu — ' . APP_NAME;
    include __DIR__ . '/../includes/header.php';
    ?>
    <main class="ana-icerik">
        <div class="bos-durum kart mt-2" style="max-width:540px;margin-left:auto;margin-right:auto;">
            <?php if ($eczane['durum'] === 'beklemede'): ?>
                <div class="stat-ikon camgob mb-1" style="width:64px;height:64px;font-size:2rem;"><?= svgIkon('clock') ?></div>
                <h2>Hesabınız İnceleniyor</h2>
                <p>Eczane başvurunuz admin tarafından incelenmektedir. Onaylanınca e-posta ile bildirim alacaksınız.</p>
            <?php else: ?>
                <div class="stat-ikon kirmizi mb-1" style="width:64px;height:64px;font-size:2rem;"><?= svgIkon('x') ?></div>
                <h2>Başvurunuz Reddedildi</h2>
                <p><?= e($eczane['red_nedeni'] ?? 'Belgeleriniz uygun bulunmadı.') ?></p>
            <?php endif; ?>
            <a href="<?= sayf('auth/logout.php') ?>" class="btn btn-gri mt-1">Çıkış Yap</a>
        </div>
    </main>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// İstatistikleri Çek
$stokSayisi = $db->prepare("SELECT COUNT(*) FROM stok WHERE eczane_id = ?");
$stokSayisi->execute([$eczane['id']]);
$stokSayisi = $stokSayisi->fetchColumn();

$bekleyenRez = $db->prepare("SELECT COUNT(*) FROM ayirtmalar WHERE eczane_id = ? AND durum = 'beklemede'");
$bekleyenRez->execute([$eczane['id']]);
$bekleyenRez = $bekleyenRez->fetchColumn();

$bekleyenTalep = $db->prepare("SELECT COUNT(*) FROM talepler WHERE eczane_id = ? AND durum = 'bekliyor'");
$bekleyenTalep->execute([$eczane['id']]);
$bekleyenTalep = $bekleyenTalep->fetchColumn();

// Son Rezervasyonlar
$sonRez = $db->prepare("
    SELECT a.*, i.ad as ilac_adi, k.ad, k.soyad 
    FROM ayirtmalar a 
    JOIN ilaclar i ON a.ilac_id = i.id 
    JOIN kullanicilar k ON a.kullanici_id = k.id 
    WHERE a.eczane_id = ? 
    ORDER BY a.olusturma_tarihi DESC LIMIT 5
");
$sonRez->execute([$eczane['id']]);
$sonRez = $sonRez->fetchAll();

// Son Talepler
$sonTalep = $db->prepare("
    SELECT t.*, k.ad, k.soyad 
    FROM talepler t 
    JOIN kullanicilar k ON t.kullanici_id = k.id 
    WHERE t.eczane_id = ? 
    ORDER BY t.olusturma_tarihi DESC LIMIT 5
");
$sonTalep->execute([$eczane['id']]);
$sonTalep = $sonTalep->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('grid') ?> <?= e($eczane['eczane_adi']) ?> Paneli</h1>
        <div style="display:flex;gap:.75rem;align-items:center;">
            <a href="profile.php" class="btn btn-gri btn-sm" style="border-radius:4px;"><?= svgIkon('settings') ?> Ayarlar</a>
        </div>
    </div>
    
    <!-- İstatistik Kartları -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
        <div class="kart" style="padding:1.5rem; display:flex; align-items:center; gap:1.25rem; border-bottom:4px solid var(--renk-birincil);">
            <div class="stat-ikon camgob" style="width:50px; height:50px; font-size:1.5rem; flex-shrink:0;">
                <?= svgIkon('shopping-bag') ?>
            </div>
            <div>
                <div style="font-size:0.85rem; color:var(--metin-uc); font-weight:600; text-transform:uppercase;">Stoktaki İlaçlar</div>
                <div style="font-size:1.75rem; font-weight:800; color:var(--metin-birincil);"><?= number_format($stokSayisi) ?></div>
            </div>
        </div>
        <div class="kart" style="padding:1.5rem; display:flex; align-items:center; gap:1.25rem; border-bottom:4px solid var(--renk-uyari);">
            <div class="stat-ikon sari" style="width:50px; height:50px; font-size:1.5rem; flex-shrink:0;">
                <?= svgIkon('clock') ?>
            </div>
            <div>
                <div style="font-size:0.85rem; color:var(--metin-uc); font-weight:600; text-transform:uppercase;">Bekleyen Rezervasyon</div>
                <div style="font-size:1.75rem; font-weight:800; color:var(--metin-birincil);"><?= number_format($bekleyenRez) ?></div>
            </div>
        </div>
        <div class="kart" style="padding:1.5rem; display:flex; align-items:center; gap:1.25rem; border-bottom:4px solid var(--renk-ikincil);">
            <div class="stat-ikon mavi" style="width:50px; height:50px; font-size:1.5rem; flex-shrink:0;">
                <?= svgIkon('message-square') ?>
            </div>
            <div>
                <div style="font-size:0.85rem; color:var(--metin-uc); font-weight:600; text-transform:uppercase;">Yeni Talepler</div>
                <div style="font-size:1.75rem; font-weight:800; color:var(--metin-birincil);"><?= number_format($bekleyenTalep) ?></div>
            </div>
        </div>
    </div>

    <!-- Nöbetçi Durumu -->
    <?php
    $nobetStateClass = $eczane['nobetci'] ? 'renk-basari' : 'metin-ikincil';
    $nobetStateBg = $eczane['nobetci'] ? 'renk-basari-bg' : 'arkaplan-hover';
    ?>
    <div class="kart mb-2" id="nobetYonetimKarti" style="padding:1.5rem; display:flex; justify-content:space-between; align-items:center; background: var(--arkaplan-kart); border-left: 6px solid <?= $eczane['nobetci'] ? 'var(--renk-basari)' : 'var(--kenar-rengi)' ?>;">
        <div>
            <h2 style="margin:0 0 0.5rem 0; font-size:1.25rem; display:flex; align-items:center; gap:0.5rem;">
                <?= svgIkon('activity') ?> Nöbet Modu
            </h2>
            <p style="margin:0; font-size:0.9rem; color:var(--metin-ikincil);">
                Durum: 
                <strong id="nobetciGuncelDurum" style="color:var(--<?= $nobetStateClass ?>); padding:0.25rem 0.75rem; border-radius:6px; background:var(--<?= $nobetStateBg ?>); margin-left:0.5rem;">
                    <?= $eczane['nobetci'] ? 'NÖBETÇİ (AKTİF)' : 'PASİF' ?>
                </strong>
            </p>
        </div>
        <div style="display:flex; gap:1rem;">
            <button id="btnNobetBaslat" class="btn btn-birincil" style="height:44px; padding:0 1.5rem; <?= $eczane['nobetci'] ? 'display:none;' : '' ?>" onclick="setNobetDurumu(1)">
                <?= svgIkon('check') ?> Nöbeti Başlat
            </button>
            <button id="btnNobetBitir" class="btn btn-tehlike" style="height:44px; padding:0 1.5rem; <?= !$eczane['nobetci'] ? 'display:none;' : '' ?>" onclick="setNobetDurumu(0)">
                <?= svgIkon('x-circle') ?> Nöbeti Bitir
            </button>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 2fr; gap:1.5rem; margin-top:2rem;">
        
        <!-- Sol Kolon: Eczane Bilgileri -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <div class="kart">
                <div class="kart-baslik" style="display:flex; justify-content:space-between; align-items:center;">
                    <h2 style="margin:0; font-size:1.1rem;"><?= svgIkon('map-pin') ?> Eczane Bilgileri</h2>
                    <a href="profile.php" class="btn btn-gri btn-sm" title="Bilgileri Düzenle"><?= svgIkon('edit') ?></a>
                </div>
                <div class="kart-govde">
                    <div style="display:flex; flex-direction:column; gap:1.25rem;">
                        <div>
                            <label style="display:block; font-size:0.7rem; color:var(--metin-uc); text-transform:uppercase; font-weight:700; margin-bottom:0.4rem;">Adres</label>
                            <div style="font-size:0.95rem; line-height:1.5; color:var(--metin-birincil);"><?= e($eczane['adres']) ?></div>
                            <div style="color:var(--metin-ikincil); font-size:0.85rem; margin-top:0.25rem; font-weight:600;"><?= e($eczane['sehir']) ?> / <?= e($eczane['ilce']) ?></div>
                        </div>
                        <div>
                            <label style="display:block; font-size:0.7rem; color:var(--metin-uc); text-transform:uppercase; font-weight:700; margin-bottom:0.4rem;">İletişim</label>
                            <div style="font-size:0.95rem; color:var(--metin-birincil); display:flex; align-items:center; gap:0.5rem;">
                                <?= svgIkon('phone') ?> <?= e($eczane['telefon'] ?: 'Belirtilmedi') ?>
                            </div>
                        </div>
                        <div style="padding-top:1rem; border-top:1px solid var(--kenar-rengi);">
                            <a href="profile.php" class="btn btn-ikincil w-full justify-center" style="font-weight:600;">
                                Profil Ayarlarına Git
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kart" style="background: linear-gradient(135deg, var(--renk-birincil), #0f766e); color:white; border:none;">
                <div class="kart-govde" style="padding:1.5rem; text-align:center;">
                    <div style="font-size:2.5rem; margin-bottom:0.5rem;">📱</div>
                    <h3 style="margin-bottom:0.5rem; color:white;">Mobil Uygulama</h3>
                    <p style="font-size:0.85rem; opacity:0.9; line-height:1.4;">Talepleri ve rezervasyonları mobil üzerinden de yönetebilirsiniz.</p>
                    <div style="margin-top:1rem; font-size:0.75rem; background:rgba(255,255,255,0.2); padding:0.5rem; border-radius:6px;">Yakında Google Play & App Store'da!</div>
                </div>
            </div>
        </div>

        <!-- Sağ Kolon: Aktiviteler -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            
            <!-- Son Rezervasyonlar -->
            <div class="kart">
                <div class="kart-baslik" style="display:flex; justify-content:space-between; align-items:center;">
                    <h2 style="margin:0; font-size:1.1rem;"><?= svgIkon('calendar') ?> Son Rezervasyonlar</h2>
                    <a href="reservations.php" style="font-size:0.85rem; color:var(--renk-birincil); font-weight:600;">Tümünü Gör</a>
                </div>
                <div class="kart-govde" style="padding:0;">
                    <?php if (empty($sonRez)): ?>
                        <div style="padding:2rem; text-align:center; color:var(--metin-uc);">Henüz rezervasyon bulunmuyor.</div>
                    <?php else: ?>
                        <div class="liste-tablo">
                            <table style="width:100%; border-collapse:collapse;">
                                <thead style="background:var(--arkaplan-alt); border-bottom:1px solid var(--kenar-rengi);">
                                    <tr>
                                        <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; color:var(--metin-uc);">Kullanıcı</th>
                                        <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; color:var(--metin-uc);">İlaç</th>
                                        <th style="padding:0.75rem 1rem; text-align:left; font-size:0.75rem; color:var(--metin-uc);">Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sonRez as $rez): ?>
                                        <tr style="border-bottom:1px solid var(--kenar-rengi);">
                                            <td style="padding:1rem;">
                                                <div style="font-weight:600;"><?= e($rez['ad'] . ' ' . $rez['soyad']) ?></div>
                                            </td>
                                            <td style="padding:1rem; font-size:0.9rem;"><?= e($rez['ilac_adi']) ?></td>
                                            <td style="padding:1rem;">
                                                <?php
                                                $durumRenk = 'gri';
                                                if ($rez['durum'] === 'onaylandi') $durumRenk = 'basari';
                                                if ($rez['durum'] === 'beklemede') $durumRenk = 'uyari';
                                                ?>
                                                <span class="etiket etiket-<?= $durumRenk ?>"><?= ucfirst($rez['durum']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Son Talepler -->
            <div class="kart">
                <div class="kart-baslik" style="display:flex; justify-content:space-between; align-items:center;">
                    <h2 style="margin:0; font-size:1.1rem;"><?= svgIkon('message-circle') ?> Son İlaç Talepleri</h2>
                    <a href="requests.php" style="font-size:0.85rem; color:var(--renk-birincil); font-weight:600;">Tümünü Gör</a>
                </div>
                <div class="kart-govde" style="padding:0;">
                    <?php if (empty($sonTalep)): ?>
                        <div style="padding:2rem; text-align:center; color:var(--metin-uc);">Yeni talep bulunmuyor.</div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column;">
                            <?php foreach ($sonTalep as $talep): ?>
                                <div style="padding:1.25rem; border-bottom:1px solid var(--kenar-rengi); display:flex; justify-content:space-between; align-items:center; transition:background 0.2s;" onmouseover="this.style.background='var(--arkaplan-hover)'" onmouseout="this.style.background='transparent'">
                                    <div>
                                        <div style="font-weight:700; margin-bottom:0.25rem;"><?= e($talep['konu']) ?></div>
                                        <div style="font-size:0.85rem; color:var(--metin-ikincil);"><?= e($talep['ad'] . ' ' . $talep['soyad']) ?> tarafından gönderildi</div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-size:0.75rem; color:var(--metin-uc); margin-bottom:0.5rem;"><?= date('d.m.Y H:i', strtotime($talep['olusturma_tarihi'])) ?></div>
                                        <a href="requests.php?id=<?= $talep['id'] ?>" class="btn btn-ikincil btn-sm">Yanıtla</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</main>

<script>
function setNobetDurumu(hedefDurum) {
    const btnBaslat = document.getElementById('btnNobetBaslat');
    const btnBitir = document.getElementById('btnNobetBitir');
    
    if (hedefDurum === 1) {
        btnBaslat.innerHTML = '<span class="spinner" style="width:14px;height:14px;margin-right:8px;"></span>...';
        btnBaslat.disabled = true;
    } else {
        btnBitir.innerHTML = '<span class="spinner" style="width:14px;height:14px;margin-right:8px;"></span>...';
        btnBitir.disabled = true;
    }

    const formData = new FormData();
    formData.append('islem', 'nobetci_guncelle');
    formData.append('durum', hedefDurum);
    formData.append('csrf_token', '<?= csrf_token() ?>');

    fetch('profile_islem.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => {
        if (!res.ok) throw new Error('Sunucu hatası: ' + res.status);
        return res.json();
    })
    .then(data => {
        btnBaslat.innerHTML = '<?= svgIkon('check') ?> Nöbeti Başlat';
        btnBaslat.disabled = false;
        btnBitir.innerHTML = '<?= svgIkon('x-circle') ?> Nöbeti Bitir';
        btnBitir.disabled = false;

        if (data.success) {
            location.reload();
        } else {
            alert('Hata: ' + (data.message || 'Bilinmeyen bir hata oluştu.'));
        }
    })
    .catch(err => {
        btnBaslat.innerHTML = '<?= svgIkon('check') ?> Nöbeti Başlat';
        btnBaslat.disabled = false;
        btnBitir.innerHTML = '<?= svgIkon('x-circle') ?> Nöbeti Bitir';
        btnBitir.disabled = false;
        alert('Bağlantı hatası: ' + err.message);
    });
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
