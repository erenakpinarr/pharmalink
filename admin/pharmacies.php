<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('admin');
csrf_verify();
$baslik = 'Eczane Yönetimi — ' . APP_NAME;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $islem   = $_POST['islem'] ?? '';
    $eczaneId = (int)($_POST['eczane_id'] ?? 0);
    if ($islem === 'onayla' && $eczaneId) {
        $stmt = db()->prepare("UPDATE eczaneler SET durum='onaylandi', red_nedeni=NULL WHERE id=?");
        $stmt->execute([$eczaneId]);
        flashMesajAyarla('basari', 'Eczane başarıyla onaylandı.');
    } elseif ($islem === 'reddet' && $eczaneId) {
        $nedenSorgu = trim($_POST['red_nedeni'] ?? '');
        $stmt = db()->prepare("UPDATE eczaneler SET durum='reddedildi', red_nedeni=? WHERE id=?");
        $stmt->execute([$nedenSorgu ?: 'Belgeler eksik veya hatalı.', $eczaneId]);
        flashMesajAyarla('uyari', 'Eczane başvurusu reddedildi.');
    }
    yonlendir(sayf('admin/pharmacies.php'));
}
$durumFiltre = $_GET['durum'] ?? 'tumu';
$sayfaNo     = max(1, (int)($_GET['sayfa'] ?? 1));
$limitSayisi = 15;
$offset      = ($sayfaNo - 1) * $limitSayisi;
$neresi = '';
$params = [];
if (in_array($durumFiltre, ['beklemede', 'onaylandi', 'reddedildi'], true)) {
    $neresi  = "WHERE e.durum = ?";
    $params[] = $durumFiltre;
}
$toplam  = db()->prepare("SELECT COUNT(*) FROM eczaneler e $neresi");
$toplam->execute($params);
$toplamSayi = (int)$toplam->fetchColumn();
$stmt = db()->prepare(
    "SELECT e.*, k.ad, k.soyad, k.email
     FROM eczaneler e
     JOIN kullanicilar k ON k.id = e.kullanici_id
     $neresi
     ORDER BY e.olusturma DESC
     LIMIT $limitSayisi OFFSET $offset"
);
$stmt->execute($params);
$eczaneler = $stmt->fetchAll();
$toplamSayfa = max(1, ceil($toplamSayi / $limitSayisi));
?>

<script src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_API_KEY ?>&libraries=geometry&language=tr&v=weekly" defer></script>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('building') ?> Eczane Yönetimi</h1>
    </div>
    <div style="display:flex;gap:.75rem;margin-bottom:2.5rem;flex-wrap:wrap;">
        <?php foreach (['tumu'=>'Tümü','beklemede'=>'Beklemede','onaylandi'=>'Onaylı','reddedildi'=>'Reddedildi'] as $k => $v): ?>
            <a href="?durum=<?= $k ?>"
               class="btn <?= ($durumFiltre === $k) ? 'btn-birincil' : 'btn-gri' ?> btn-sm" style="border-radius: var(--yaricap-pill); padding: 0.6rem 1.25rem;">
                <?= $v ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="kart">
        <div class="filtre-alan">
            <div class="arama-alani">
                <?= svgIkon('search') ?>
                <input type="text" id="aramaInput" placeholder="Eczane adı, şehir... ara">
            </div>
            <span style="font-size:.8rem;color:var(--metin-uc);">Toplam: <?= $toplamSayi ?> kayıt</span>
        </div>
        <div class="tablo-sarici" style="border:none;box-shadow:none;border-radius:0;">
            <table class="tablo" id="anaTablosu">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Eczane</th>
                        <th>Yetkili</th>
                        <th>Konum</th>
                        <th>Belge</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eczaneler)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--metin-uc);">Kayıt bulunamadı.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($eczaneler as $e): ?>
                    <tr>
                        <td style="color:var(--metin-uc);font-size:.8rem;"><?= $e['id'] ?></td>
                        <td><strong><?= e($e['eczane_adi']) ?></strong></td>
                        <td>
                            <?= e($e['ad'] . ' ' . $e['soyad']) ?><br>
                            <small style="color:var(--metin-uc)"><?= e($e['email']) ?></small>
                        </td>
                        <td>
                            <?= e($e['sehir']) ?> / <?= e($e['ilce']) ?><br>
                            <?php if ($e['enlem'] && $e['boylam']): ?>
                                <button class="btn btn-gri btn-sm" style="margin-top:.25rem;padding:.2rem .5rem;"
                                        onclick="adminHaritaAc(<?= (float)$e['enlem'] ?>, <?= (float)$e['boylam'] ?>, '<?= e($e['eczane_adi']) ?>')">
                                    <?= svgIkon('map-pin') ?> Harita
                                </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($e['belge_dosyasi']): ?>
                                <a href="<?= sayf('uploads/documents/' . $e['belge_dosyasi']) ?>" target="_blank" class="btn btn-gri btn-sm">
                                    <?= svgIkon('eye') ?> Görüntüle
                                </a>
<?php else: ?>
                                <span style="color:var(--metin-uc);font-size:.8rem;">Yok</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $rozetSinif = match($e['durum']) {
                                'onaylandi'  => 'rozet-yesil',
                                'beklemede'  => 'rozet-sari',
                                'reddedildi' => 'rozet-kirmizi',
                                default      => 'rozet-gri',
                            };
                            $rozetMetin = match($e['durum']) {
                                'onaylandi'  => 'Onaylandı',
                                'beklemede'  => 'Beklemede',
                                'reddedildi' => 'Reddedildi',
                                default      => $e['durum'],
                            };
                            ?>
                            <span class="rozet <?= $rozetSinif ?>"><?= $rozetMetin ?></span>
                        </td>
                        <td style="font-size:.8rem;"><?= date('d.m.Y', strtotime($e['olusturma'])) ?></td>
                        <td>
                            <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
                                <?php if ($e['durum'] === 'beklemede' || $e['durum'] === 'reddedildi'): ?>
                                <form method="post" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="islem" value="onayla">
                                    <input type="hidden" name="eczane_id" value="<?= $e['id'] ?>">
                                    <button type="submit" class="btn btn-ikincil btn-sm"
                                            data-onay="Bu eczaneyi onaylamak istediğinizden emin misiniz?">
                                        <?= svgIkon('check') ?> Onayla
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if ($e['durum'] !== 'reddedildi'): ?>
                                <button class="btn btn-tehlike btn-sm"
                                        onclick="redModalAc(<?= $e['id'] ?>)">
                                    <?= svgIkon('x') ?> Reddet
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($toplamSayfa > 1): ?>
        <div class="sayfalama mt-2">
            <span class="sayfalama-bilgi"><?= $toplamSayi ?> kayıttan <?= min($offset+1, $toplamSayi) ?>-<?= min($offset+$limitSayisi, $toplamSayi) ?> arası</span>
            <div class="sayfalama-linkler">
                <?php for ($i = 1; $i <= $toplamSayfa; $i++): ?>
                    <a href="?durum=<?= $durumFiltre ?>&sayfa=<?= $i ?>"
                       class="<?= ($i === $sayfaNo) ? 'aktif-sayfa' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
<div class="modal-arkaplan" id="redModal" role="dialog" aria-labelledby="redModalBaslik" aria-hidden="true">
    <div class="modal-kutu">
        <div class="modal-baslik">
            <h3 id="redModalBaslik"><?= svgIkon('alert-circle') ?> Başvuruyu Reddet</h3>
            <button class="modal-kapat" onclick="modalKapat('redModal')" aria-label="Kapat">✕</button>
        </div>
        <form method="post" id="redForm">
            <?= csrf_field() ?>
            <div class="modal-govde">
                <input type="hidden" name="islem" value="reddet">
                <input type="hidden" name="eczane_id" id="redEczaneId" value="">
                <div class="form-grid">
                    <div class="form-grup tam-satir">
                        <label class="form-etiket" for="red_nedeni">Red Nedeni *</label>
                        <textarea class="form-alani" name="red_nedeni" id="red_nedeni" rows="4" 
                                   placeholder="Başvurunun neden reddedildiğini açıklayın..." required></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-ayak">
                <button type="button" class="btn btn-gri" onclick="modalKapat('redModal')">İptal</button>
                <button type="submit" class="btn btn-tehlike">
                    <?= svgIkon('x-circle') ?> Başvuruyu Reddet
                </button>
            </div>
        </form>
    </div>
</div>
<div class="modal-arkaplan" id="adminHaritaModal" role="dialog" aria-labelledby="haritaModalBaslik" aria-hidden="true">
    <div class="modal-kutu" style="max-width:750px;">
        <div class="modal-baslik">
            <h3 id="haritaModalBaslik"><?= svgIkon('map-pin') ?> Konum Önizleme</h3>
            <button class="modal-kapat" onclick="modalKapat('adminHaritaModal')" aria-label="Kapat">✕</button>
        </div>
        <div class="modal-govde" style="padding:0; overflow:hidden; border-radius: 0 0 var(--yaricap-lg) var(--yaricap-lg);">
            <div id="adminMap" style="height:450px;width:100%;"></div>
        </div>
        <div class="modal-ayak">
            <button type="button" class="btn btn-gri" onclick="modalKapat('adminHaritaModal')">Kapat</button>
        </div>
    </div>
</div>
<script>
let adminGMap, adminMarker;
function adminHaritaAc(lat, lng, name) {
    modalAc('adminHaritaModal');
    const modalBaslik = document.getElementById('haritaModalBaslik');
    if (modalBaslik) {
        modalBaslik.innerHTML = '<?= svgIkon('map-pin') ?> ' + name;
    }
    const loc = { lat, lng };
    if (!adminGMap) {
        adminGMap = new google.maps.Map(document.getElementById("adminMap"), {
            center: loc,
            zoom: 16,
            styles: [
                { "featureType": "poi", "stylers": [{ "visibility": "off" }] },
                { "featureType": "transit", "stylers": [{ "visibility": "off" }] },
                { "featureType": "administrative", "stylers": [{ "visibility": "on" }] }
            ],
            mapTypeControl: false,
            streetViewControl: false
        });
        adminMarker = new google.maps.Marker({
            position: loc,
            map: adminGMap,
            icon: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
            title: name
        });
    } else {
        adminGMap.setCenter(loc);
        adminMarker.setPosition(loc);
        adminMarker.setTitle(name);
    }
}
function redModalAc(eczaneId) {
    document.getElementById('redEczaneId').value = eczaneId;
    modalAc('redModal');
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
