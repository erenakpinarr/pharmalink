<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('eczane');
$baslik = 'Eczane Dashboard — ' . APP_NAME;
$kid = mevcutKullaniciId();
$eczane = db()->prepare("SELECT * FROM eczaneler WHERE kullanici_id = ? LIMIT 1");
$eczane->execute([$kid]);
$eczane = $eczane->fetch();
if (!$eczane) {
    flashMesajAyarla('tehlike', 'Eczane bilgileriniz bulunamadı.');
    yonlendir(sayf('auth/logout.php'));
}

// ERP ve CRM modülleri kaldırıldı.


// Removed vardiyalar query

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
include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('grid') ?> <?= e($eczane['eczane_adi']) ?></h1>
        <div style="display:flex;gap:.75rem;align-items:center;">
            <a href="profile.php" class="btn btn-gri btn-sm" style="border-radius:4px;"><?= svgIkon('settings') ?> Profil</a>
        </div>
    </div>
    

    
    <?php
    $nobetStateClass = $eczane['nobetci'] ? 'renk-basari' : 'metin-ikincil';
    $nobetStateBg = $eczane['nobetci'] ? 'renk-basari-bg' : 'arkaplan-hover';
    ?>
    <div class="kart mb-2" id="nobetYonetimKarti" style="padding:1.5rem; display:flex; justify-content:space-between; align-items:center; border: 1px solid var(--kenar-rengi); background: var(--arkaplan-kart);">
        <div>
            <h2 style="margin:0 0 0.25rem 0; font-size:1.15rem; display:flex; align-items:center; gap:0.5rem; color:var(--metin-birincil);">
                <?= svgIkon('activity') ?> Nöbetçi Eczane Durumu
            </h2>
            <p style="margin:0; font-size:0.85rem; color:var(--metin-ikincil);">
                Şu anki nöbet durumu: 
                <strong id="nobetciGuncelDurum" style="color:var(--<?= $nobetStateClass ?>); padding:0.2rem 0.5rem; border-radius:4px; background:var(--<?= $nobetStateBg ?>);">
                    <?= $eczane['nobetci'] ? 'NÖBETÇİ (AKTİF)' : 'NÖBETÇİ DEĞİL (PASİF)' ?>
                </strong>
            </p>
        </div>
        <div style="display:flex; gap:1rem;">
            <button id="btnNobetBaslat" class="btn btn-birincil" style="border-radius:4px; <?= $eczane['nobetci'] ? 'display:none;' : '' ?>" onclick="setNobetDurumu(1)">
                <?= svgIkon('check') ?> Nöbeti Başlat
            </button>
            <button id="btnNobetBitir" class="btn btn-tehlike" style="border-radius:4px; <?= !$eczane['nobetci'] ? 'display:none;' : '' ?>" onclick="setNobetDurumu(0)">
                <?= svgIkon('x-circle') ?> Nöbeti Bitir
            </button>
        </div>
    </div>
    
    <div class="kart mb-2" style="padding:1.5rem; display:flex; gap:1rem; align-items:center; background:var(--arkaplan-kart); border-left:4px solid var(--renk-birincil);">
        <h3 style="margin:0; font-size:1.1rem; color:var(--metin-birincil); display:flex; align-items:center; gap:0.5rem; white-space:nowrap;">
            <?= svgIkon('activity') ?> Hızlı İşlemler:
        </h3>
        <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            <a href="vitrin_ayarlari.php" class="btn btn-ikincil btn-sm"><?= svgIkon('eye') ?> Vitrin</a>
            <a href="profile.php" class="btn btn-ikincil btn-sm"><?= svgIkon('settings') ?> Profil</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;margin-top:1.5rem;">

        <div class="kart" style="margin:0;">
            <div class="kart-baslik">
                <h2><?= svgIkon('map-pin') ?> Eczane Bilgileri</h2>
                <a href="profile.php" class="btn btn-gri btn-sm"><?= svgIkon('edit') ?> Düzenle</a>
            </div>
            <div class="kart-govde">
                <div class="form-grid">
                    <div>
                        <div style="font-size:.75rem;color:var(--metin-uc);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">Adres</div>
                        <div><?= e($eczane['adres']) ?></div>
                        <div style="color:var(--metin-ikincil);font-size:.875rem;"><?= e($eczane['sehir']) ?> / <?= e($eczane['ilce']) ?></div>
                    </div>
                    <div>
                        <div style="font-size:.75rem;color:var(--metin-uc);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">Çalışma Saatleri</div>
                        <div><?= e($eczane['calisma_saatleri'] ?? '—') ?></div>
                    </div>
                    <div>
                        <div style="font-size:.75rem;color:var(--metin-uc);text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem;">Telefon</div>
                        <div><?= e($eczane['telefon'] ?? '—') ?></div>
                    </div>
                </div>
            </div>
        </div>




    </div>
</main>
<script>
function setNobetDurumu(hedefDurum) {
    const btnBaslat = document.getElementById('btnNobetBaslat');
    const btnBitir = document.getElementById('btnNobetBitir');
    
    // Yükleniyor durumu ekle
    if (hedefDurum === 1) {
        btnBaslat.innerHTML = '<span class="spinner" style="width:14px;height:14px;margin-right:8px;"></span> İşleniyor...';
        btnBaslat.disabled = true;
    } else {
        btnBitir.innerHTML = '<span class="spinner" style="width:14px;height:14px;margin-right:8px;"></span> İşleniyor...';
        btnBitir.disabled = true;
    }

    const formData = new FormData();
    formData.append('durum', hedefDurum);
    formData.append('csrf_token', '<?= csrf_token() ?>');

    fetch('api/nobet_guncelle.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        // Buton durumlarını düzelt
        btnBaslat.innerHTML = '<?= svgIkon('check') ?> Nöbeti Başlat';
        btnBaslat.disabled = false;
        btnBitir.innerHTML = '<?= svgIkon('x-circle') ?> Nöbeti Bitir';
        btnBitir.disabled = false;

        if (data.success) {
            const durumText = document.getElementById('nobetciGuncelDurum');
            if (data.nobetci) {
                durumText.textContent = 'NÖBETÇİ (AKTİF)';
                durumText.style.color = 'var(--renk-basari)';
                durumText.style.background = 'var(--renk-basari-bg)';
                btnBaslat.style.display = 'none';
                btnBitir.style.display = 'inline-flex';
            } else {
                durumText.textContent = 'NÖBETÇİ DEĞİL (PASİF)';
                durumText.style.color = 'var(--metin-ikincil)';
                durumText.style.background = 'var(--arkaplan-hover)';
                btnBaslat.style.display = 'inline-flex';
                btnBitir.style.display = 'none';
            }
            if (typeof showNotification === 'function') {
                showNotification(data.message, 'basari');
            }
        } else {
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Bir hata oluştu.', 'tehlike');
            } else {
                alert(data.message || 'Hata oluştu');
            }
        }
    })
    .catch(err => {
        btnBaslat.innerHTML = '<?= svgIkon('check') ?> Nöbeti Başlat';
        btnBaslat.disabled = false;
        btnBitir.innerHTML = '<?= svgIkon('x-circle') ?> Nöbeti Bitir';
        btnBitir.disabled = false;
        if (typeof showNotification === 'function') {
            showNotification('Bağlantı hatası.', 'tehlike');
        } else {
            alert('Bağlantı hatası.');
        }
    });
}

</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
