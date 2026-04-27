<?php
$baslik = "404 - Sayfa Bulunamadı";
require_once __DIR__ . '/includes/header.php';
?>

<main class="hata-sayfasi">
    <div class="hata-konteyner">
        <div class="hata-gorsel">
            <div class="hata-kod-kapsayici">
                <span class="hata-kod">404</span>
                <div class="hata-daireler">
                    <div class="daire daire-1"></div>
                    <div class="daire daire-2"></div>
                </div>
            </div>
        </div>
        
        <h1 class="hata-baslik">Aradığınız İlaç (Sayfa) Bulunamadı!</h1>
        <p class="hata-aciklama">
            Görünüşe göre bu sayfa sistemimizden kaldırılmış veya hiç var olmamış. 
            Lütfen adresi kontrol edin veya ana sayfaya dönerek devam edin.
        </p>
        
        <div class="hata-eylemler">
            <a href="<?= sayf('/') ?>" class="btn btn-birincil">
                <?= svgIkon('home') ?> Ana Sayfaya Dön
            </a>
            <a href="javascript:history.back()" class="btn btn-gri">
                <?= svgIkon('arrow-left') ?> Geri Git
            </a>
        </div>
    </div>
</main>

<style>
.hata-sayfasi {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    background: radial-gradient(circle at top right, rgba(49, 46, 129, 0.03), transparent),
                radial-gradient(circle at bottom left, rgba(49, 46, 129, 0.03), transparent);
}

.hata-konteyner {
    max-width: 600px;
    width: 100%;
}

.hata-kod-kapsayici {
    position: relative;
    display: inline-block;
    margin-bottom: 2rem;
}

.hata-kod {
    font-size: 8rem;
    font-weight: 900;
    font-family: 'Outfit', sans-serif;
    background: linear-gradient(135deg, var(--renk-birincil), #6366f1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    line-height: 1;
    letter-spacing: -0.05em;
    position: relative;
    z-index: 2;
}

.hata-daireler {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 200px;
    height: 200px;
    z-index: 1;
}

.daire {
    position: absolute;
    border-radius: 50%;
    filter: blur(40px);
    opacity: 0.2;
    animation: yuzme 10s infinite alternate;
}

.daire-1 {
    width: 150px;
    height: 150px;
    background: var(--renk-birincil);
    top: -20px;
    left: -20px;
}

.daire-2 {
    width: 120px;
    height: 120px;
    background: #6366f1;
    bottom: -10px;
    right: -10px;
    animation-delay: -5s;
}

@keyframes yuzme {
    0% { transform: translate(0, 0) scale(1); }
    100% { transform: translate(20px, 20px) scale(1.1); }
}

.hata-baslik {
    font-size: 2.25rem;
    font-weight: 700;
    color: var(--metin-birincil);
    margin-bottom: 1rem;
    font-family: 'Outfit', sans-serif;
}

.hata-aciklama {
    font-size: 1.1rem;
    color: var(--metin-ikincil);
    margin-bottom: 2.5rem;
    line-height: 1.6;
}

.hata-eylemler {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.hata-eylemler .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
}

@media (max-width: 640px) {
    .hata-kod { font-size: 6rem; }
    .hata-baslik { font-size: 1.75rem; }
    .hata-eylemler { flex-direction: column; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
