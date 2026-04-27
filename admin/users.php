<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
rolKontrol('admin');
$baslik = 'Kullanıcılar — ' . APP_NAME;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrf_token(), $_POST['csrf_token'] ?? '')) {
        flashMesajAyarla('tehlike', 'Güvenlik doğrulaması başarısız.');
        yonlendir(sayf('admin/users.php'));
    }
    $id    = (int)($_POST['kullanici_id'] ?? 0);
    $aktif = (int)($_POST['aktif'] ?? 0);
    if ($id && $id !== mevcutKullaniciId()) {
        db()->prepare("UPDATE kullanicilar SET aktif=? WHERE id=?")->execute([$aktif, $id]);
        flashMesajAyarla('basari', $aktif ? 'Kullanıcı aktifleştirildi.' : 'Kullanıcı pasif yapıldı.');
    }
    yonlendir(sayf('admin/users.php'));
}
$kullanicilar = db()->query(
    "SELECT k.*, e.eczane_adi, e.durum AS eczane_durum
     FROM kullanicilar k
     LEFT JOIN eczaneler e ON e.kullanici_id = k.id
     ORDER BY k.olusturma DESC"
)->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<main class="ana-icerik">
    <div class="sayfa-baslik">
        <h1><?= svgIkon('users') ?> Kullanıcı Listesi</h1>
        <div class="arama-alani" style="max-width:300px;">
            <?= svgIkon('search') ?>
            <input type="text" id="aramaInput" placeholder="Ad, e-posta...">
        </div>
    </div>
    <div class="tablo-sarici">
        <table class="tablo" id="anaTablosu">
            <thead>
                <tr>
                    <th>Avatar</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Telefon</th>
                    <th>Rol</th>
                    <th>Eczane</th>
                    <th>Durum</th>
                    <th>Kayıt Tarihi</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kullanicilar as $k): ?>
                <tr>
                    <td style="width:48px;">
                        <div class="kullanici-avatar" style="width:40px;height:40px;font-size:0.9rem;">
                            <?php if ($k['profil_resmi']): ?>
                                <img src="<?= sayf('uploads/avatars/' . e($k['profil_resmi'])) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <?= initialsAvatar($k['ad'] . ' ' . $k['soyad']) ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><strong><?= e($k['ad'] . ' ' . $k['soyad']) ?></strong></td>
                    <td style="font-size:.875rem;"><?= e($k['email']) ?></td>
                    <td style="font-size:.875rem;"><?= e($k['telefon'] ?? '—') ?></td>
                    <td><span class="rol-rozet <?= e($k['rol']) ?>"><?= e(rolAdi($k['rol'])) ?></span></td>
                    <td style="font-size:.8rem;">
                        <?= $k['eczane_adi'] ? e($k['eczane_adi']) : '<span style="color:var(--metin-uc)">—</span>' ?>
                    </td>
                    <td>
                        <span class="rozet <?= $k['aktif'] ? 'rozet-yesil' : 'rozet-kirmizi' ?>">
                            <?= $k['aktif'] ? 'Aktif' : 'Pasif' ?>
                        </span>
                    </td>
                    <td style="font-size:.8rem;color:var(--metin-uc);"><?= date('d.m.Y', strtotime($k['olusturma'])) ?></td>
                    <td>
                        <?php if ($k['id'] !== mevcutKullaniciId()): ?>
                        <form method="post" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="kullanici_id" value="<?= $k['id'] ?>">
                            <input type="hidden" name="aktif" value="<?= $k['aktif'] ? '0' : '1' ?>">
                            <button type="submit" class="btn btn-sm <?= $k['aktif'] ? 'btn-gri' : 'btn-ikincil' ?>"
                                    data-onay="Bu kullanıcıyı <?= $k['aktif'] ? 'pasif' : 'aktif' ?> yapmak istiyor musunuz?"
                                    title="<?= $k['aktif'] ? 'Pasif Yap' : 'Aktifleştir' ?>">
                                <?= $k['aktif'] ? svgIkon('user-x') : svgIkon('user-check') ?>
                                <span class="mobil-gizle"><?= $k['aktif'] ? 'Durdur' : 'Başlat' ?></span>
                            </button>
                        </form>
                        <?php else: ?>
                            <span class="rozet rozet-gri" style="font-size:.7rem;">Siz</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
