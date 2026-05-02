<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../includes/helpers.php';
$baslik = 'İletişim — ' . APP_NAME;
include_once __DIR__ . '/../includes/header.php';
?>
<header class="corp-page-header">
    <div class="corp-container">
        <div class="corp-grid-2">
            <div class="hero-text-side">
                <div class="corp-page-breadcrumb">
                    <?= svgIkon('mail') ?> Bize Ulaşın / İletişim Formu
                </div>
                <h1 class="corp-page-title text-gradient">Sorularınız İçin<br>Buradayız.</h1>
                <p style="color:var(--metin-ikincil); font-size:1.25rem; line-height:1.7; margin-bottom:2rem;">
                    Öneri, görüş veya eczane kayıt talepleriniz için aşağıdaki formu doldurabilir veya doğrudan iletişim kanallarımızı kullanabilirsiniz.
                </p>
            </div>
            <div class="corp-header-visual">
                <img src="<?= sayf('assets/img/contact_visual.png') ?>" alt="Contact PharmaLink">
            </div>
        </div>
    </div>
</header>

<main class="corp-section">
    <div class="corp-container">
        <div class="corp-contact-grid">
            <div class="corp-card glass-card">
                <h2 style="margin-bottom:2rem; font-size:1.75rem;">İletişim Formu</h2>
                <form action="#" id="contactPageForm">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
                        <div class="form-grup" style="margin-bottom:0;">
                            <label class="form-etiket" style="color:var(--metin-uc); font-size:0.8rem; text-transform:uppercase; font-weight:800;">Ad Soyad</label>
                            <input type="text" class="form-giris" placeholder="Adınız Soyadınız" required style="border-radius:12px; height:50px;">
                        </div>
                        <div class="form-grup" style="margin-bottom:0;">
                            <label class="form-etiket" style="color:var(--metin-uc); font-size:0.8rem; text-transform:uppercase; font-weight:800;">E-posta Adresi</label>
                            <input type="email" class="form-giris" placeholder="ornek@mail.com" required style="border-radius:12px; height:50px;">
                        </div>
                    </div>
                    <div class="form-grup">
                        <label class="form-etiket" style="color:var(--metin-uc); font-size:0.8rem; text-transform:uppercase; font-weight:800;">Konu</label>
                        <select class="form-secim" style="border-radius:12px; height:50px;">
                            <option value="">Bir konu seçin...</option>
                            <option value="genel">Genel Bilgi</option>
                            <option value="eczane">Eczane Kayıt Talebi</option>
                            <option value="teknik">Teknik Destek</option>
                            <option value="oneri">Öneri / Şikayet</option>
                        </select>
                    </div>
                    <div class="form-grup">
                        <label class="form-etiket" style="color:var(--metin-uc); font-size:0.8rem; text-transform:uppercase; font-weight:800;">Mesajınız</label>
                        <textarea class="form-alani" rows="5" placeholder="Bize nasıl yardımcı olabiliriz?" required style="border-radius:12px;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-birincil btn-lg" style="width:100%; justify-content:center; border-radius:12px; height:55px; font-weight:800; font-size:1.1rem; box-shadow:0 10px 20px rgba(220, 38, 38, 0.15);">
                        <?= svgIkon('send') ?> Mesajı Gönder
                    </button>
                </form>
            </div>
            <div style="display:flex; flex-direction:column; gap:2rem;">
                <div class="corp-card" style="padding:2.5rem; border-radius:24px;">
                    <h3 style="font-size:1.35rem; margin-bottom:2rem;">İletişim Bilgileri</h3>
                    <div class="footer-links" style="gap:2rem;">
                        <div class="footer-contact-item" style="font-size:1.05rem; color:var(--metin-birincil); align-items:flex-start;">
                            <div style="width:46px; height:46px; background:var(--renk-birincil-acik); border-radius:14px; display:flex; align-items:center; justify-content:center; color:var(--renk-birincil); flex-shrink:0;">
                                <?= svgIkon('map-pin') ?>
                            </div>
                            <div style="padding-top:0.25rem;">
                                <strong style="display:block; font-size:0.8rem; color:var(--metin-uc); text-transform:uppercase; margin-bottom:0.25rem;">Genel Merkez</strong>
                                <span>Maslak Veri Merkezi, No:42 <br>Sarıyer, İstanbul</span>
                            </div>
                        </div>
                        <div class="footer-contact-item" style="font-size:1.05rem; color:var(--metin-birincil); align-items:flex-start;">
                             <div style="width:46px; height:46px; background:#e0f2fe; border-radius:14px; display:flex; align-items:center; justify-content:center; color:#0ea5e9; flex-shrink:0;">
                                <?= svgIkon('phone') ?>
                            </div>
                            <div style="padding-top:0.25rem;">
                                <strong style="display:block; font-size:0.8rem; color:var(--metin-uc); text-transform:uppercase; margin-bottom:0.25rem;">Destek Hattı</strong>
                                <span>+90 (212) 444 0 555</span>
                            </div>
                        </div>
                        <div class="footer-contact-item" style="font-size:1.05rem; color:var(--metin-birincil); align-items:flex-start;">
                             <div style="width:46px; height:46px; background:#f0fdf4; border-radius:14px; display:flex; align-items:center; justify-content:center; color:#10b981; flex-shrink:0;">
                                <?= svgIkon('mail') ?>
                            </div>
                            <div style="padding-top:0.25rem;">
                                <strong style="display:block; font-size:0.8rem; color:var(--metin-uc); text-transform:uppercase; margin-bottom:0.25rem;">E-Posta</strong>
                                <span>destek@pharmalink.com.tr</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="corp-card" style="padding:2.5rem; background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color:#fff; border:none; border-radius:24px; position:relative; overflow:hidden;">
                    <div style="position:absolute; top:-20px; right:-20px; width:100px; height:100px; background:var(--renk-birincil); filter:blur(60px); opacity:0.2;"></div>
                    <h3 style="color:#fff; font-size:1.35rem; margin-bottom:1.5rem;">Çalışma Saatleri</h3>
                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        <div style="display:flex; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:0.5rem;">
                            <span style="color:rgba(255,255,255,0.6);">Hafta içi</span>
                            <span style="font-weight:700;">09:00 - 18:00</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:0.5rem;">
                            <span style="color:rgba(255,255,255,0.6);">Cumartesi</span>
                            <span style="font-weight:700;">10:00 - 14:00</span>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:rgba(255,255,255,0.6);">Pazar</span>
                            <span style="color:#ef4444; font-weight:700;">Kapalı</span>
                        </div>
                    </div>
                    <div style="margin-top:2.5rem; padding:1rem; background:rgba(255,255,255,0.05); border-radius:12px; font-size:0.85rem; border:1px solid rgba(255,255,255,0.1); display:flex; gap:0.75rem; align-items:center;">
                        <div style="color:var(--renk-birincil);"><?= svgIkon('bell') ?></div>
                        <p style="margin:0; opacity:0.8; line-height:1.4;">Eczaneler için 7/24 teknik destek hattımız mevcuttur.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<section style="height:450px; background:#f1f5f9; border-top:1px solid var(--kenar-rengi); position:relative; overflow:hidden;">
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d12025.275817293527!2d29.0118855!3d41.1121964!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14cab5c0a969f583%3A0x6476e3391781f8f9!2zTWFzbGFrLCBTYXLEsXllci_EsHN0YW5idWw!5e0!3m2!1str!2str!4v1700000000000!5m2!1str!2str" 
            width="100%" height="100%" style="border:0; filter: grayscale(0.5) contrast(1.1) brightness(1.1);" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    <div style="position:absolute; top:2rem; left:50%; transform:translateX(-50%); background:rgba(255,255,255,0.9); backdrop-filter:blur(10px); padding:1rem 2rem; border-radius:100px; box-shadow:0 10px 30px rgba(0,0,0,0.1); border:1px solid #fff; display:flex; align-items:center; gap:0.75rem; font-weight:700; color:#0f172a; pointer-events:none;">
        <?= svgIkon('map-pin') ?> Maslak Genel Merkezi
    </div>
</section>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
