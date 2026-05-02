<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../includes/helpers.php';

$activeTab = $_GET['tab'] ?? 'privacy';
$baslik = 'Yasal Bilgilendirme Merkezi — ' . APP_NAME;

include_once __DIR__ . '/../includes/header.php';
?>

<style>
    .legal-container {
        max-width: 1200px;
        margin: 4rem auto;
        padding: 0 2rem;
    }

    .legal-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 3rem;
        align-items: start;
    }

    .legal-nav {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        position: sticky;
        top: 120px;
        background: var(--arkaplan-kart);
        padding: 1.5rem;
        border-radius: 16px;
        border: 1px solid var(--kenar-rengi);
        box-shadow: var(--golge-hafif);
    }

    .legal-nav-btn {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border: none;
        background: transparent;
        color: var(--metin-ikincil);
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        border-radius: 12px;
        transition: all 0.25s ease;
        text-align: left;
        width: 100%;
        text-decoration: none;
    }

    .legal-nav-btn:hover {
        background: var(--arkaplan-hover);
        color: var(--metin-birincil);
    }

    .legal-nav-btn.active {
        background: var(--renk-birincil-acik);
        color: var(--renk-birincil);
    }

    .legal-nav-btn svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    .legal-content {
        background: var(--arkaplan-kart);
        padding: 4rem;
        border-radius: 24px;
        border: 1px solid var(--kenar-rengi);
        box-shadow: var(--golge-hafif);
    }

    .legal-section {
        display: none;
        animation: fadeIn 0.4s ease;
    }

    .legal-section.active {
        display: block;
    }

    .legal-section h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 2rem;
        color: var(--metin-birincil);
    }

    .legal-section h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 2.5rem 0 1.25rem;
        color: var(--metin-birincil);
    }

    .legal-section p, .legal-section li {
        color: var(--metin-ikincil);
        line-height: 1.8;
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }

    .legal-section ul {
        padding-left: 1.5rem;
        margin-bottom: 2rem;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 1024px) {
        .legal-layout {
            grid-template-columns: 1fr;
        }
        .legal-nav {
            position: relative;
            top: 0;
            flex-direction: row;
            overflow-x: auto;
            padding: 1rem;
            white-space: nowrap;
        }
        .legal-nav-btn {
            width: auto;
        }
        .legal-content {
            padding: 2.5rem 1.5rem;
        }
    }
</style>

<header class="corp-page-header">
    <div class="corp-container">
        <div class="corp-page-breadcrumb">Kurumsal / Yasal Mevzuat</div>
        <h1 class="corp-page-title">Yasal Bilgilendirme Merkezi</h1>
        <p style="color:var(--metin-ikincil); font-size:1.2rem; max-width:800px; margin:0 auto;">
            Platformumuzun kullanım şartları, verilerinizin korunması ve gizlilik haklarınız hakkında detaylı bilgilere buradan ulaşabilirsiniz.
        </p>
    </div>
</header>

<main class="legal-container">
    <div class="legal-layout">
        <!-- Sol Navigasyon -->
        <nav class="legal-nav">
            <a href="#privacy" class="legal-nav-btn active" data-tab="privacy">
                <?= svgIkon('shield-check') ?> <span>Gizlilik Politikası</span>
            </a>
            <a href="#kvkk" class="legal-nav-btn" data-tab="kvkk">
                <?= svgIkon('file-text') ?> <span>KVKK Aydınlatma</span>
            </a>
            <a href="#terms" class="legal-nav-btn" data-tab="terms">
                <?= svgIkon('info') ?> <span>Kullanım Şartları</span>
            </a>
            <a href="#cookies" class="legal-nav-btn" data-tab="cookies">
                <?= svgIkon('circle') ?> <span>Çerez Politikası</span>
            </a>
        </nav>

        <!-- Sağ İçerik -->
        <div class="legal-content">
            <!-- 1. GİZLİLİK POLİTİKASI -->
            <section id="privacy" class="legal-section active">
                <h1>Gizlilik Politikası</h1>
                <p>PharmaLink olarak, kullanıcılarımızın kişisel verilerinin gizliliğine ve güvenliğine en üst düzeyde önem veriyoruz. Bu politika, hangi verileri topladığımızı ve bunları nasıl kullandığımızı açıklar.</p>
                
                <h2>1. Toplanan Veriler</h2>
                <p>Platformumuzu kullanabilmeniz için aşağıdaki verileri topluyoruz:</p>
                <ul>
                    <li>Kimlik Bilgileri (Ad, Soyad)</li>
                    <li>İletişim Bilgileri (E-posta, Telefon)</li>
                    <li>Konum Bilgileri (İlaç arama sonuçlarını mesafeye göre sıralamak için)</li>
                </ul>

                <h2>2. Verilerin Kullanım Amacı</h2>
                <p>Toplanan bilgiler, yalnızca size en yakın eczaneyi bulmak, ilaç ayırtma taleplerinizi ilgili eczacıya iletmek ve sistem güncellemeleri hakkında sizi bilgilendirmek amacıyla kullanılır.</p>
                
                <h2>3. Veri Güvenliği</h2>
                <p>Tüm kişisel verileriniz, endüstri standardı şifreleme yöntemleri ile sunucularımızda korunmaktadır. Üçüncü şahıslarla reklam veya pazarlama amacıyla paylaşım yapılmaz.</p>
            </section>

            <!-- 2. KVKK AYDINLATMA -->
            <section id="kvkk" class="legal-section">
                <h1>KVKK Aydınlatma Metni</h1>
                <p>Bu metin, 6698 Sayılı Kişisel Verilerin Korunması Kanunu (KVKK) uyarınca, veri sorumlusu sıfatıyla PharmaLink tarafından verilerinizin işlenme usullerini bildirmek amacıyla hazırlanmıştır.</p>
                
                <h2>1. Veri Sorumlusu</h2>
                <p>PharmaLink Dijital Sağlık Hizmetleri A.Ş., Maslak Veri Merkezi, İstanbul.</p>

                <h2>2. İşlenen Verilerin Aktarımı</h2>
                <p>Verileriniz, yalnızca talebiniz doğrultusunda bir ilaç ayırttığınızda, o işlemin tamamlanması için ilgili Eczane ile sınırlı olarak paylaşılır.</p>
                
                <h2>3. Veri Sahibi Hakları</h2>
                <p>KVKK'nın 11. maddesi uyarınca şu haklara sahipsiniz:</p>
                <ul>
                    <li>Verilerinizin işlenip işlenmediğini öğrenme</li>
                    <li>Verilerinizin düzeltilmesini veya silinmesini isteme</li>
                    <li>İşlemin durdurulmasını talep etme</li>
                </ul>
            </section>

            <!-- 3. KULLANIM ŞARTLARI -->
            <section id="terms" class="legal-section">
                <h1>Kullanım Şartları</h1>
                <p>PharmaLink platformuna erişerek ve kullanarak, aşağıdaki kullanım şartlarını kabul etmiş sayılırsınız.</p>
                
                <h2>1. Hizmet Kapsamı</h2>
                <p>PharmaLink, eczane stoklarını sorgulamanıza ve ilaç ayırtma talebinde bulunmanıza olanak tanıyan bir aracı platformdur. İlaç satışı yapmaz; sadece yönlendirme ve bilgi sağlar.</p>

                <h2>2. Kullanıcı Sorumlulukları</h2>
                <p>Kullanıcılar, platform üzerinden yaptıkları taleplerin doğruluğundan ve randevu saatlerine sadık kalmaktan sorumludur. Kötüye kullanım durumunda hesaplar askıya alınabilir.</p>
                
                <h2>3. Sorumluluk Sınırı</h2>
                <p>Eczanelerin stok bilgilerindeki hatalardan veya operasyonel aksaklıklardan PharmaLink doğrudan sorumlu tutulamaz.</p>
            </section>

            <!-- 4. ÇEREZ POLİTİKASI -->
            <section id="cookies" class="legal-section">
                <h1>Çerez Politikası</h1>
                <p>Web sitemizden en verimli şekilde faydalanabilmeniz için çerezler (cookies) kullanıyoruz.</p>
                
                <h2>1. Çerez Nedir?</h2>
                <p>Çerezler, web sitemizi ziyaret ettiğinizde cihazınıza kaydedilen küçük metin dosyalarıdır. Ayarlarınızın hatırlanmasına ve oturumunuzun aktif kalmasına yardımcı olurlar.</p>

                <h2>2. Kullanılan Çerez Türleri</h2>
                <ul>
                    <li><strong>Zorunlu Çerezler:</strong> Giriş yapma ve güvenlik gibi temel fonksiyonlar için gereklidir.</li>
                    <li><strong>Performans Çerezleri:</strong> Sitemizin nasıl kullanıldığını analiz ederek hızı artırmamıza yardımcı olur.</li>
                </ul>
                
                <h2>3. Yönetim</h2>
                <p>Tarayıcı ayarlarınız üzerinden çerezleri dilediğiniz zaman silebilir veya engelleyebilirsiniz; ancak bu durumda bazı özellikler çalışmayabilir.</p>
            </section>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btns = document.querySelectorAll('.legal-nav-btn');
    const sections = document.querySelectorAll('.legal-section');

    function switchTab(tabId) {
        btns.forEach(b => {
            b.classList.toggle('active', b.dataset.tab === tabId);
        });
        sections.forEach(s => {
            s.classList.toggle('active', s.id === tabId);
        });
        
        // Scroll to top of content in mobile
        if(window.innerWidth <= 1024) {
            document.querySelector('.legal-content').scrollIntoView({ behavior: 'smooth' });
        }
    }

    btns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const tab = btn.dataset.tab;
            history.pushState(null, '', `?tab=${tab}`);
            switchTab(tab);
        });
    });

    // Sayfa yüklendiğinde URL kontrolü
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        switchTab(tabParam);
    } else {
        const hash = window.location.hash.replace('#', '');
        if(hash) switchTab(hash);
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
