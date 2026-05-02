<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('eczane');

$baslik = 'Müşteri Talepleri — ' . APP_NAME;
$eczaneId = $_SESSION['eczane_id'] ?? 0;

// Talepleri getir
$stmt = db()->prepare("
    SELECT t.*, k.ad, k.soyad, k.telefon
    FROM talepler t
    JOIN kullanicilar k ON k.id = t.kullanici_id
    WHERE t.eczane_id = ?
    ORDER BY t.guncelleme_tarihi DESC
");
$stmt->execute([$eczaneId]);
$talepler = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('message-square') ?> Müşteri Talepleri</h1>
    </div>

    <?php if (empty($talepler)): ?>
        <div class="kart" style="padding: 5rem 2rem; text-align: center; background: white; border-radius: var(--yaricap-lg); border: 1px solid var(--kenar-rengi);">
            <div style="font-size: 4rem; color: var(--metin-uc); margin-bottom: 1.5rem;"><?= svgIkon('message-square') ?></div>
            <h2 style="color: var(--metin-birincil); margin-bottom: 0.5rem;">Henüz bir talep almadınız</h2>
            <p style="color: var(--metin-ikincil); max-width: 500px; margin: 0 auto 2rem;">
                Müşteriler stokta olmayan ilaçlar için buradan size talep gönderebilir.
            </p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
            <div class="kart" style="max-height: 800px; overflow-y: auto;">
                <h3 style="margin-bottom: 1rem; border-bottom: 1px solid var(--kenar-rengi); padding-bottom: 1rem;">Talepler</h3>
                <div style="display:flex; flex-direction:column; gap:0.5rem;" id="talepListesi">
                    <?php foreach ($talepler as $t): ?>
                        <div class="talep-item" data-id="<?= $t['id'] ?>" style="padding: 1rem; border: 1px solid var(--kenar-rengi); border-radius: 4px; cursor: pointer; transition: all 0.2s;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                                <strong><?= e($t['ad'] . ' ' . $t['soyad']) ?></strong>
                                <?php
                                $renk = match($t['durum']) {
                                    'bekliyor' => 'rozet-sari',
                                    'yanitlandi' => 'rozet-gri',
                                    'onaylandi' => 'rozet-mavi',
                                    'teslim_edildi' => 'rozet-yesil',
                                    'iptal' => 'rozet-kirmizi',
                                    default => 'rozet-gri'
                                };
                                ?>
                                <span class="rozet <?= $renk ?> durum-rozet-<?= $t['id'] ?>"><?= e(ucfirst($t['durum'])) ?></span>
                            </div>
                            <div style="font-size:0.85rem; color:var(--metin-ikincil);"><?= e($t['konu']) ?></div>
                            <div style="font-size:0.75rem; color:var(--metin-uc); text-align:right; margin-top:0.5rem;"><?= date('d.m.Y H:i', strtotime($t['guncelleme_tarihi'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="kart" style="display:flex; flex-direction:column; height: 600px;">
                <div id="talepDetayBaslik" style="border-bottom: 1px solid var(--kenar-rengi); padding-bottom: 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="color:var(--metin-uc);">Bir talep seçin</h3>
                </div>
                
                <div id="mesajlarAlani" style="flex:1; overflow-y:auto; padding-right:1rem; display:flex; flex-direction:column; gap:1rem; margin-bottom:1rem;">
                    <!-- Mesajlar buraya gelecek -->
                </div>
                
                <div id="mesajGondermeAlani" style="display:none; border-top: 1px solid var(--kenar-rengi); padding-top: 1rem;">
                    <form id="mesajForm">
                        <input type="hidden" id="aktifTalepId" name="talep_id">
                        <input type="hidden" name="islem" value="mesaj_gonder">
                        <div style="display:flex; gap:0.5rem;">
                            <input type="text" name="mesaj" class="form-alani" style="flex:1;" placeholder="Müşteriye yanıt yazın..." required autocomplete="off">
                            <button type="submit" class="btn btn-birincil"><?= svgIkon('send') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<script>
document.querySelectorAll('.talep-item').forEach(item => {
    item.addEventListener('click', async function() {
        document.querySelectorAll('.talep-item').forEach(i => i.style.borderColor = 'var(--kenar-rengi)');
        this.style.borderColor = 'var(--renk-birincil)';
        
        const talepId = this.dataset.id;
        document.getElementById('aktifTalepId').value = talepId;
        document.getElementById('mesajGondermeAlani').style.display = 'block';
        
        // Üst başlık ve durum butonları
        document.getElementById('talepDetayBaslik').innerHTML = `
            <h3>Talep #${talepId}</h3>
            <div style="display:flex; gap:0.5rem;">
                <select id="durumSelect" class="form-alani" style="padding: 0.35rem 0.5rem; font-size: 0.85rem;" onchange="durumDegistir(${talepId}, this.value)">
                    <option value="">Durum Değiştir</option>
                    <option value="onaylandi">Onayla / Tedarik Edildi</option>
                    <option value="teslim_edildi">Teslim Edildi</option>
                    <option value="iptal">İptal Et</option>
                </select>
            </div>
        `;
        
        mesajlariYukle(talepId);
    });
});

async function durumDegistir(talepId, durum) {
    if(!durum) return;
    try {
        const formData = new FormData();
        formData.append('islem', 'durum_guncelle');
        formData.append('talep_id', talepId);
        formData.append('durum', durum);
        
        const data = await ApiService.post('/api/talep.php', Object.fromEntries(formData));
        if (data.basari) {
            gostermeBildirim('Durum başarıyla güncellendi.', 'basari');
            const rozet = document.querySelector(`.durum-rozet-${talepId}`);
            if (rozet) {
                rozet.textContent = durum.toUpperCase();
                rozet.className = 'rozet durum-rozet-' + talepId + ' ' + (durum === 'onaylandi' ? 'rozet-mavi' : (durum === 'teslim_edildi' ? 'rozet-yesil' : 'rozet-kirmizi'));
            }
        } else {
            gostermeBildirim(data.mesaj, 'tehlike');
        }
    } catch(err) {
        gostermeBildirim('Hata oluştu.', 'tehlike');
    }
}

async function mesajlariYukle(talepId) {
    const mesajlarAlani = document.getElementById('mesajlarAlani');
    mesajlarAlani.innerHTML = '<div style="text-align:center; padding:2rem;"><div class="spinner" style="width:30px;height:30px;"></div></div>';
    
    try {
        const res = await fetch(`<?= APP_URL ?>/api/talep_mesajlari.php?talep_id=${talepId}`);
        const data = await res.json();
        
        if (data.basari) {
            mesajlarAlani.innerHTML = '';
            data.mesajlar.forEach(m => {
                const isBen = m.gonderen_tipi === 'eczane';
                const div = document.createElement('div');
                div.style.maxWidth = '80%';
                div.style.padding = '0.75rem 1rem';
                div.style.borderRadius = '8px';
                div.style.marginBottom = '0.5rem';
                
                if (isBen) {
                    div.style.alignSelf = 'flex-end';
                    div.style.background = 'var(--renk-ikincil)';
                    div.style.color = '#fff';
                    div.style.borderBottomRightRadius = '0';
                } else {
                    div.style.alignSelf = 'flex-start';
                    div.style.background = '#f1f5f9';
                    div.style.color = '#0f172a';
                    div.style.borderBottomLeftRadius = '0';
                }
                
                div.innerHTML = `
                    <div style="font-size:0.95rem;">${m.mesaj}</div>
                    <div style="font-size:0.7rem; margin-top:0.25rem; text-align:right; opacity:0.8;">${m.tarih}</div>
                `;
                mesajlarAlani.appendChild(div);
            });
            mesajlarAlani.scrollTop = mesajlarAlani.scrollHeight;
        } else {
            mesajlarAlani.innerHTML = `<div style="text-align:center; color:red;">${data.mesaj}</div>`;
        }
    } catch (e) {
        mesajlarAlani.innerHTML = '<div style="text-align:center; color:red;">Bağlantı hatası</div>';
    }
}

document.getElementById('mesajForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const input = this.querySelector('input[name="mesaj"]');
    const talepId = document.getElementById('aktifTalepId').value;
    if(!input.value.trim() || !talepId) return;
    
    try {
        const formData = new FormData(this);
        const data = await ApiService.post('/api/talep.php', Object.fromEntries(formData));
        if (data.basari) {
            input.value = '';
            mesajlariYukle(talepId);
            
            // Rozeti yanitlandi yap
            const rozet = document.querySelector(`.durum-rozet-${talepId}`);
            if (rozet && rozet.textContent !== 'ONAYLANDI' && rozet.textContent !== 'TESLİM_EDİLDİ' && rozet.textContent !== 'İPTAL') {
                rozet.textContent = 'Yanıtlandı';
                rozet.className = `rozet rozet-gri durum-rozet-${talepId}`;
            }
        } else {
            gostermeBildirim(data.mesaj, 'tehlike');
        }
    } catch(err) {
        gostermeBildirim('Hata oluştu', 'tehlike');
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
