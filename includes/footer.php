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
                <div class="corp-nav-logo" style="color:var(--renk-birincil); margin-bottom:1.5rem;">
                    <div style="width:36px; height:36px; background:var(--renk-birincil); border-radius:4px; display:flex; align-items:center; justify-content:center; color:#fff;">
                        <?= svgIkon('plus') ?>
                    </div>
                    <?= APP_NAME ?>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php if (defined('GOOGLE_MAPS_API_KEY')): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=places,geometry&language=tr&region=TR"></script>
<?php endif; ?>
</body>
</html>
