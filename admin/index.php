<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('admin');
$baslik = 'Admin Dashboard — ' . APP_NAME;
$istatistikler = [
    'kullanicilar'     => db()->query("SELECT COUNT(*) FROM kullanicilar WHERE rol='kullanici'")->fetchColumn(),
    'eczaneler_toplam' => db()->query("SELECT COUNT(*) FROM eczaneler")->fetchColumn(),
    'bekleyen'         => db()->query("SELECT COUNT(*) FROM eczaneler WHERE durum='beklemede'")->fetchColumn(),
    'onaylandi'        => db()->query("SELECT COUNT(*) FROM eczaneler WHERE durum='onaylandi'")->fetchColumn(),
    'ilaclar'          => db()->query("SELECT COUNT(*) FROM ilaclar WHERE aktif=1")->fetchColumn(),
    'kategoriler'      => db()->query("SELECT COUNT(*) FROM kategoriler")->fetchColumn(),
];
$bekleyenEczaneler = db()->query(
    "SELECT e.*, k.ad, k.soyad, k.email
     FROM eczaneler e
     JOIN kullanicilar k ON k.id = e.kullanici_id
     WHERE e.durum='beklemede'
     ORDER BY e.olusturma DESC LIMIT 5"
)->fetchAll();
$sonKullanicilar = db()->query(
    "SELECT id, ad, soyad, email, olusturma 
     FROM kullanicilar 
     WHERE rol='kullanici' 
     ORDER BY olusturma DESC LIMIT 5"
)->fetchAll();
$katGrafik = db()->query(
    "SELECT k.ad, COUNT(i.id) as sayi 
     FROM kategoriler k 
     LEFT JOIN ilaclar i ON i.kategori_id = k.id AND i.aktif=1 
     GROUP BY k.id"
)->fetchAll();
$katIsimleri = json_encode(array_column($katGrafik, 'ad') ?: ['Kategori Yok']);
$katSayilari = json_encode(array_column($katGrafik, 'sayi') ?: [0]);
$eczGrafik = [
    'Onaylı' => $istatistikler['onaylandi'],
    'Bekleyen' => $istatistikler['bekleyen']
];
$eczIsimleri = json_encode(array_keys($eczGrafik));
$eczSayilari = json_encode(array_values($eczGrafik));
include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('grid') ?> Admin Dashboard</h1>
        <div style="font-size:.8rem;color:var(--metin-uc);"><?= date('d.m.Y H:i') ?></div>
    </div>
    <div class="stat-grid">
        <div class="stat-kart">
            <div class="stat-ikon"><?= svgIkon('users') ?></div>
            <div class="stat-icerik">
                <div class="stat-sayi"><?= number_format($istatistikler['kullanicilar']) ?></div>
                <div class="stat-etiket">Kayıtlı Kullanıcı</div>
            </div>
        </div>
        <div class="stat-kart">
            <div class="stat-ikon"><?= svgIkon('building') ?></div>
            <div class="stat-icerik">
                <div class="stat-sayi"><?= number_format($istatistikler['onaylandi']) ?></div>
                <div class="stat-etiket">Onaylı Eczane</div>
            </div>
        </div>
        <div class="stat-kart">
            <div class="stat-ikon"><?= svgIkon('clock') ?></div>
            <div class="stat-icerik">
                <div class="stat-sayi"><?= number_format($istatistikler['bekleyen']) ?></div>
                <div class="stat-etiket">Bekleyen Onay</div>
            </div>
        </div>
        <div class="stat-kart">
            <div class="stat-ikon"><?= svgIkon('pill') ?></div>
            <div class="stat-icerik">
                <div class="stat-sayi"><?= number_format($istatistikler['ilaclar']) ?></div>
                <div class="stat-etiket">Aktif İlaç</div>
            </div>
        </div>
    </div>
    <div class="kart mb-2" style="padding:1.5rem; display:flex; gap:1rem; align-items:center; background:var(--arkaplan-kart); border-left:4px solid var(--renk-birincil);">
        <h3 style="margin:0; font-size:1.1rem; color:var(--metin-birincil); display:flex; align-items:center; gap:0.5rem; white-space:nowrap;">
            <?= svgIkon('activity') ?> Hızlı İşlemler:
        </h3>
        <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            <a href="categories.php" class="btn btn-ikincil btn-sm"><?= svgIkon('plus') ?> Kategori Ekle</a>
            <a href="medicines.php" class="btn btn-ikincil btn-sm"><?= svgIkon('pill') ?> İlaç Yönetimi</a>
            <a href="users.php" class="btn btn-ikincil btn-sm"><?= svgIkon('users') ?> Kullanıcıları Yönet</a>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:1.5rem;margin-bottom:1.5rem;">
        <div class="kart" style="padding:1.5rem;">
            <div style="margin-bottom:1rem;">
                <h3 style="font-size:1.1rem;margin:0;color:var(--metin-birincil);"><?= svgIkon('tag') ?> Kategorilere Göre İlaçlar</h3>
            </div>
            <div style="position:relative;height:250px;">
                <canvas id="kategoriGrafik"></canvas>
            </div>
        </div>
        <div class="kart" style="padding:1.5rem;">
            <div style="margin-bottom:1rem;">
                <h3 style="font-size:1.1rem;margin:0;color:var(--metin-birincil);"><?= svgIkon('building') ?> Eczane Kayıt Durumu</h3>
            </div>
            <div style="position:relative;height:250px;">
                <canvas id="eczaneGrafik"></canvas>
            </div>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;margin-bottom:1.5rem;">
        <div class="kart">
            <div class="kart-baslik">
                <h2><?= svgIkon('clock') ?> Bekleyen Eczane Onayları</h2>
                <a href="pharmacies.php" class="btn btn-gri btn-sm">Tüm Yönetim</a>
            </div>
            <?php if (empty($bekleyenEczaneler)): ?>
                <div class="bos-durum" style="padding:3rem 1.5rem;">
                    <div class="stat-ikon yesil mb-1" style="width:50px;height:50px;font-size:1.5rem;"><?= svgIkon('check-circle') ?></div>
                    <h3>Bekleyen Onay Yok</h3>
                    <p style="font-size:0.9rem;">Tüm eczane başvuruları güncel.</p>
                </div>
            <?php else: ?>
            <div class="tablo-sarici">
                <table class="tablo">
                    <thead>
                        <tr>
                            <th>Eczane Adı</th>
                            <th>Bölge</th>
                            <th style="text-align:right;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bekleyenEczaneler as $e): ?>
                        <tr>
                            <td>
                                <div style="display:flex;flex-direction:column;">
                                    <strong><?= e($e['eczane_adi']) ?></strong>
                                    <small style="color:var(--metin-uc)"><?= e($e['ad'] . ' ' . $e['soyad']) ?></small>
                                </div>
                            </td>
                            <td><span class="rozet rozet-gri"><?= e($e['sehir']) ?></span></td>
                            <td style="text-align:right;">
                                <a href="pharmacies.php?id=<?= $e['id'] ?>" class="btn btn-birincil btn-sm">İncele</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="kart">
            <div class="kart-baslik">
                <h2><?= svgIkon('users') ?> Son Kayıt Olan Kullanıcılar</h2>
                <a href="users.php" class="btn btn-gri btn-sm">Tümünü Gör</a>
            </div>
            <?php if (empty($sonKullanicilar)): ?>
                <div class="bos-durum" style="padding:3rem 1.5rem;">
                    <div class="stat-ikon gri mb-1" style="width:50px;height:50px;font-size:1.5rem;"><?= svgIkon('user') ?></div>
                    <h3>Kullanıcı Yok</h3>
                </div>
            <?php else: ?>
            <div class="tablo-sarici">
                <table class="tablo">
                    <thead>
                        <tr>
                            <th>Ad Soyad</th>
                            <th>Kayıt Tarihi</th>
                            <th style="text-align:right;">Detay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sonKullanicilar as $k): ?>
                        <tr>
                            <td>
                                <div style="display:flex;flex-direction:column;">
                                    <strong><?= e($k['ad'] . ' ' . $k['soyad']) ?></strong>
                                    <small style="color:var(--metin-uc)"><?= e($k['email']) ?></small>
                                </div>
                            </td>
                            <td style="color:var(--metin-ikincil);font-size:0.875rem;"><?= date('d.m.Y', strtotime($k['olusturma'])) ?></td>
                            <td style="text-align:right;">
                                <a href="users.php" class="btn btn-gri btn-sm"><?= svgIkon('eye') ?></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#f9fafb' : '#1f2937';
    const gridColor = isDark ? '#374151' : '#e5e7eb';
    const ctxKategori = document.getElementById('kategoriGrafik');
    if(ctxKategori) {
        new Chart(ctxKategori, {
            type: 'bar',
            data: {
                labels: <?= $katIsimleri ?>,
                datasets: [{
                    label: 'İlaç Sayısı',
                    data: <?= $katSayilari ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: textColor, font: { family: 'Inter', size: 11 } },
                        grid: { color: gridColor, drawBorder: false }
                    },
                    x: {
                        ticks: { color: textColor, font: { family: 'Inter', size: 11, weight: '500' } },
                        grid: { display: false }
                    }
                }
            }
        });
    }
    const ctxEczane = document.getElementById('eczaneGrafik');
    if(ctxEczane) {
        new Chart(ctxEczane, {
            type: 'doughnut',
            data: {
                labels: <?= $eczIsimleri ?>,
                datasets: [{
                    data: <?= $eczSayilari ?>,
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)'
                    ],
                    borderColor: isDark ? '#1f2937' : '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { 
                            color: textColor, 
                            padding: 24,
                            usePointStyle: true,
                            font: { family: 'Inter', size: 12, weight: '500' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#374151' : '#ffffff',
                        titleColor: isDark ? '#ffffff' : '#111827',
                        bodyColor: isDark ? '#ffffff' : '#111827',
                        borderColor: gridColor,
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                cutout: '75%'
            }
        });
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
