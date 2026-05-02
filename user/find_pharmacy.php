<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('kullanici');

$ilacId = (int)($_GET['ilac_id'] ?? 0);
if (!$ilacId) {
    flashMesajAyarla('uyari', 'Lütfen bir ilaç seçin.');
    yonlendir(sayf('user/index.php'));
}

$ilac = db()->prepare("
    SELECT i.*, k.ad AS kategori_adi
    FROM ilaclar i
    JOIN kategoriler k ON k.id = i.kategori_id
    WHERE i.id = ?
");
$ilac->execute([$ilacId]);
$ilac = $ilac->fetch();

if (!$ilac) {
    flashMesajAyarla('tehlike', 'İlaç bulunamadı.');
    yonlendir(sayf('user/index.php'));
}

$baslik = e($ilac['ad']) . ' — Eczane Bul — ' . APP_NAME;

$stmt = db()->prepare("
    SELECT e.*, s.durum AS stok_durumu, s.adet
    FROM eczaneler e
    JOIN stok s ON s.eczane_id = e.id
    WHERE s.ilac_id = ?
      AND e.durum = 'onaylandi'
      AND s.durum != 'tukendi'
    ORDER BY e.nobetci DESC, e.eczane_adi ASC
");
$stmt->execute([$ilacId]);
$eczaneler = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div style="display:flex; align-items:center; gap:1.5rem; margin-bottom:2rem;">
        <a href="javascript:history.back()" class="btn btn-gri"
           style="border-radius:50%; width:40px; height:40px; padding:0; display:flex; align-items:center; justify-content:center;">
            <?= svgIkon('arrow-left') ?>
        </a>
        <div>
            <h1 style="margin:0; font-size:1.75rem;"><?= e($ilac['ad']) ?></h1>
            <span class="rozet rozet-mavi"><?= e($ilac['kategori_adi']) ?></span>
        </div>
    </div>

    <?php if (empty($eczaneler)): ?>
        <div class="kart" style="padding:5rem 2rem; text-align:center;">
            <div style="font-size:3rem; color:var(--metin-uc); margin-bottom:1rem;"><?= svgIkon('pill') ?></div>
            <h2>Stokta Bulunamadı</h2>
            <p style="color:var(--metin-ikincil); max-width:480px; margin:0 auto 2rem;">
                Bu ilaç şu an hiçbir eczanenin stoğunda mevcut görünmüyor.
            </p>
            <a href="index.php" class="btn btn-birincil">Panele Dön</a>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px,1fr)); gap:1.5rem;">
            <?php foreach ($eczaneler as $e): ?>
                <div class="kart" style="margin:0; position:relative; transition:var(--gecis);">
                    <?php if ($e['nobetci']): ?>
                        <span style="position:absolute; top:1rem; right:1rem; background:#22c55e; color:#fff;
                                     padding:.2rem .65rem; border-radius:var(--yaricap-pill); font-size:.72rem; font-weight:700;">
                            NÖBETÇİ
                        </span>
                    <?php endif; ?>

                    <div style="padding:1.5rem;">
                        <h3 style="margin:0 0 .4rem; font-size:1.15rem;"><?= e($e['eczane_adi']) ?></h3>
                        <p style="margin:0 0 1rem; font-size:.875rem; color:var(--metin-ikincil); display:flex; gap:.4rem; align-items:flex-start;">
                            <?= svgIkon('map-pin') ?>
                            <?= e($e['ilce']) ?> / <?= e($e['sehir']) ?>
                        </p>

                        <div style="display:flex; justify-content:space-between; align-items:center; padding-top:1rem; border-top:1px solid var(--kenar-rengi);">
                            <div>
                                <span style="font-size:.7rem; color:var(--metin-uc); font-weight:700; text-transform:uppercase; display:block;">Stok</span>
                                <strong style="color:<?= $e['stok_durumu'] === 'mevcut' ? '#10b981' : '#f59e0b' ?>">
                                    <?= $e['stok_durumu'] === 'mevcut' ? '✓ Mevcut' : '⚠ Az Stok' ?>
                                    (<?= (int)$e['adet'] ?> adet)
                                </strong>
                            </div>
                            <div style="display:flex; gap:.5rem;">
                                <?php if (!empty($e['telefon'])): ?>
                                    <a href="tel:<?= e($e['telefon']) ?>" class="btn btn-gri btn-sm"
                                       style="width:36px; height:36px; padding:0; display:flex; align-items:center; justify-content:center;"
                                       title="Ara">
                                        <?= svgIkon('phone') ?>
                                    </a>
                                <?php endif; ?>
                                <button onclick="ayirt(<?= $e['id'] ?>, <?= $ilacId ?>)"
                                        class="btn btn-birincil btn-sm">
                                    Ayırt
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
function ayirt(eczaneId, ilacId) {
    if (!confirm('Bu ilacı bu eczaneden ayırtmak istediğinize emin misiniz?')) return;

    const fd = new FormData();
    fd.append('eczane_id', eczaneId);
    fd.append('ilac_id', ilacId);

    fetch('<?= APP_URL ?>/api/reserve.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) window.location.href = 'reservations.php';
        })
        .catch(() => alert('Bağlantı hatası. Lütfen tekrar deneyin.'));
}
</script>

<style>
.kart:hover { transform:translateY(-3px); box-shadow:var(--golge-orta); }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
