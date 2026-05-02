<?php 
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$isPanelPage = strpos($scriptPath, '/admin/') !== false || 
               strpos($scriptPath, '/pharmacy/') !== false || 
               strpos($scriptPath, '/user/') !== false;

if (mevcutRol() && $isPanelPage): 
?>
    </div> 
</div> 
<?php else: ?>
    <footer class="corp-footer">
        <div class="corp-footer-grid">
            <div class="footer-col brand-col">
                <div class="corp-nav-logo" style="color:var(--renk-birincil); margin-bottom:0.5rem; display:flex; align-items:center; gap:0;">
                    <img src="<?= sayf('assets/img/logo_header.png') ?>" alt="PharmaLink Logo" style="height:110px; width:auto; object-fit:contain; margin-right:-15px; position:relative; top:-2px;">
                    <span style="font-weight:800; font-size:1.6rem; letter-spacing:-0.02em; color:var(--renk-birincil);"><?= APP_NAME ?></span>
                </div>
                <p style="color:var(--metin-ikincil); line-height:1.7; font-size:0.95rem;">
                    Türkiye'nin en hızlı ve güvenilir ilaç stok takip platformu. Sağlığınız için teknolojiyi eczanenize getiriyoruz.
                </p>
            </div>
            <div class="footer-col">
                <h4>Hızlı Erişim</h4>
                <div class="footer-links">
                    <a href="<?= sayf('/') ?>">Ana Sayfa</a>
                    <a href="<?= sayf('pages/about') ?>">Hakkımızda</a>
                    <a href="<?= sayf('pages/contact') ?>">İletişim</a>
                    <a href="<?= sayf('auth/login') ?>">Eczane Girişi</a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Kurumsal</h4>
                <div class="footer-links">
                    <a href="<?= sayf('pages/legal.php?tab=privacy') ?>">Gizlilik Politikası</a>
                    <a href="<?= sayf('pages/legal.php?tab=kvkk') ?>">KVKK Aydınlatma</a>
                    <a href="<?= sayf('pages/legal.php?tab=terms') ?>">Kullanım Şartları</a>
                    <a href="<?= sayf('pages/legal.php?tab=cookies') ?>">Çerez Politikası</a>
                </div>
            </div>
            <div class="footer-col">
                <h4>İletişim</h4>
                <div class="footer-links">
                    <div class="footer-contact-item">
                        <?= svgIkon('map-pin') ?>
                        <span>Maslak Veri Merkezi, No:42 <br>Sarıyer, İstanbul</span>
                    </div>
                    <div class="footer-contact-item">
                        <?= svgIkon('phone') ?>
                        <span>+90 (212) 444 0 555</span>
                    </div>
                    <div class="footer-contact-item">
                        <?= svgIkon('mail') ?>
                        <span>destek@pharmalink.com.tr</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Tüm hakları saklıdır.</p>
            <div style="display:flex; gap:1.5rem;">
                <span>Digital Health Solutions</span>
                <span style="opacity:0.3;">|</span>
                <span>v<?= APP_VERSION ?></span>
            </div>
        </div>
    </footer>
<?php endif; ?>

<!-- AI Chat Widget -->
<div class="ai-chat-widget" id="aiChatWidget">
    <div class="ai-chat-window">
        <div class="ai-chat-header">
            <h4><?= svgIkon('message-circle') ?> PharmaGPT Asistan</h4>
            <button class="ai-chat-close" id="aiChatClose" type="button" aria-label="Kapat"><?= svgIkon('x') ?></button>
        </div>
        <div class="ai-chat-messages" id="aiChatMessages">
            <div class="ai-msg bot">
                Merhaba! Ben PharmaLink Sağlık Asistanı. Size nasıl yardımcı olabilirim?
                <div class="ai-options">
                    <button type="button" class="ai-option-btn">Nöbetçi eczaneler</button>
                    <button type="button" class="ai-option-btn">Stok sorgula</button>
                    <button type="button" class="ai-option-btn">Sağlık tavsiyeleri</button>
                </div>
            </div>
        </div>
    </div>
    <button class="ai-chat-toggle" id="aiChatToggle" aria-label="Asistanı Aç">
        <?= svgIkon('message-circle') ?>
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php if (defined('GOOGLE_MAPS_API_KEY')): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places,geometry&language=tr&region=TR"></script>
<?php endif; ?>
</body>
</html>
