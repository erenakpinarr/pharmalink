<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../includes/helpers.php';
$baslik = 'Hakkımızda — ' . APP_NAME;
include_once __DIR__ . '/../includes/header.php';
?>
<header class="corp-page-header">
    <div class="corp-container">
        <div class="corp-grid-2">
            <div class="hero-text-side">
                <div class="corp-page-breadcrumb">
                    <?= svgIkon('building') ?> Kurumsal / Vizyonumuz
                </div>
                <h1 class="corp-page-title text-gradient">Geleceğin Sağlık Bağlantılarını Bugünden Kuruyoruz.</h1>
                <p style="color:var(--metin-ikincil); font-size:1.25rem; line-height:1.7; margin-bottom:2rem;">
                    PharmaLink, eczane ve hastalar arasındaki mesafeyi teknoloji ile kapatan, Türkiye'nin en inovatif ilaç stok takip platformudur. 
                </p>
                <div style="display:flex; gap:1rem;">
                    <a href="#biz-kimiz" class="btn btn-birincil">Hikayemiz</a>
                    <a href="<?= sayf('pages/contact') ?>" class="btn btn-gri">İş Birliği</a>
                </div>
            </div>
            <div class="corp-header-visual">
                <img src="<?= sayf('assets/img/about_visual.png') ?>" alt="About PharmaLink">
            </div>
        </div>
    </div>
</header>

<main class="corp-section" id="biz-kimiz">
    <div class="corp-container">
        <div class="corp-grid-2">
            <div>
                <h2 style="font-size:2.8rem; margin-bottom:2rem; letter-spacing:-0.03em;" class="text-gradient">Dijital Sağlıkta<br>Yeni Bir Çağ</h2>
                <p style="font-size:1.1rem; color:var(--metin-ikincil); line-height:1.8; margin-bottom:2rem;">
                    2024 yılında kurulan <strong><?= APP_NAME ?></strong>, sağlık sektöründeki dijitalleşme ihtiyacını karşılamak üzere hayata geçirilmiştir. 
                    Amacımız, kritik ilaçlara erişimde yaşanan zaman kayıplarını minimize etmek ve eczanelerimizin dijital dönüşümüne tam destek vermektir.
                </p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                    <div style="border-left:3px solid var(--renk-birincil); padding-left:1.5rem;">
                        <h4 style="font-size:2rem; font-weight:900; color:#0f172a;">1000+</h4>
                        <p style="font-size:0.85rem; color:var(--metin-uc); font-weight:700; text-transform:uppercase;">Aktif Eczane</p>
                    </div>
                    <div style="border-left:3px solid #0ea5e9; padding-left:1.5rem;">
                        <h4 style="font-size:2rem; font-weight:900; color:#0f172a;">50K+</h4>
                        <p style="font-size:0.85rem; color:var(--metin-uc); font-weight:700; text-transform:uppercase;">Mutlu Kullanıcı</p>
                    </div>
                </div>
            </div>
            <div style="position:relative;">
                <div style="position:absolute; inset: -20px; background:var(--renk-birincil-acik); border-radius:30px; transform:rotate(-2deg); z-index:-1;"></div>
                <img src="<?= sayf('assets/img/about_team.png') ?>" alt="Hakkımızda" style="border-radius:24px; box-shadow:0 20px 50px rgba(0,0,0,0.1); width:100%;">
            </div>
        </div>

        <div style="margin-top:8rem; display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:2.5rem;">
            <div class="corp-card">
                <div style="width:50px; height:50px; background:var(--renk-birincil-acik); color:var(--renk-birincil); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:1.5rem;">
                    <?= svgIkon('shield-check') ?>
                </div>
                <h3>Misyonumuz</h3>
                <p>Herkesin, her an, her yerde ihtiyacı olan ilaca en hızlı ve şeffaf şekilde ulaşabilmesini sağlayacak dijital altyapıyı sunmak.</p>
            </div>
            <div class="corp-card">
                <div style="width:50px; height:50px; background:#e0f2fe; color:#0ea5e9; border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:1.5rem;">
                    <?= svgIkon('eye') ?>
                </div>
                <h3>Vizyonumuz</h3>
                <p>Türkiye'de sağlık teknolojileri denilince akla gelen ilk ve en güvenilir çözüm ortağı olarak, global ölçekte model olmak.</p>
            </div>
            <div class="corp-card">
                <div style="width:50px; height:50px; background:#fef3c7; color:#d97706; border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:1.5rem;">
                    <?= svgIkon('activity') ?>
                </div>
                <h3>Değerlerimiz</h3>
                <p>Güvenilirlik, şeffaflık ve sürekli inovasyon ilkeleriyle sağlık ekosistemine değer katıyor, kullanıcı odaklı düşünüyoruz.</p>
            </div>
        </div>
    </div>
</main>

<section class="corp-section" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color:#fff; position:relative; overflow:hidden;">
    <div style="position:absolute; top:-100px; right:-100px; width:300px; height:300px; background:var(--renk-birincil); filter:blur(150px); opacity:0.1;"></div>
    <div class="corp-container" style="text-align:center; position:relative; z-index:1;">
        <h2 style="margin-bottom:1.5rem; color:#fff; font-size:2.5rem; font-weight:900;">Siz de Bu Ekosistemin Bir Parçası Olun</h2>
        <p style="margin-bottom:3.5rem; color:rgba(255,255,255,0.7); font-size:1.1rem; max-width:700px; margin-left:auto; margin-right:auto;">
            Eczanenizi dijitale taşımak, stok yönetimini optimize etmek veya ilaç arayan binlerce hastaya ulaşmak için hemen aramıza katılın.
        </p>
        <div style="display:flex; justify-content:center; gap:1.5rem; flex-wrap:wrap;">
            <a href="<?= sayf('auth/register') ?>" class="btn btn-birincil btn-lg" style="padding:1rem 3rem; border-radius:100px; font-weight:700;">Hemen Kayıt Ol</a>
            <a href="<?= sayf('pages/contact') ?>" class="btn btn-gri btn-lg" style="padding:1rem 3rem; border-radius:100px; font-weight:700; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:#fff;">Bize Ulaşın</a>
        </div>
    </div>
</section>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
