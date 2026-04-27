<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
if (empty($_SESSION['kullanici_id'])) apiCevap(false, 'Yetkisiz.', [], 401);
if (!isAjax()) apiCevap(false, 'Geçersiz istek türü.', [], 403);
$islem  = $_POST['islem'] ?? '';
$ilacId = (int)($_POST['ilac_id'] ?? 0);
$kid    = mevcutKullaniciId();
if ($islem === 'toggle' && $ilacId) {
    $stmt = db()->prepare("SELECT id FROM favoriler WHERE kullanici_id=? AND ilac_id=?");
    $stmt->execute([$kid, $ilacId]);
    $var = $stmt->fetch();
    if ($var) {
        db()->prepare("DELETE FROM favoriler WHERE kullanici_id=? AND ilac_id=?")->execute([$kid, $ilacId]);
        apiCevap(true, 'Favorilerden kaldırıldı.', ['favori_mi' => false]);
    } else {
        db()->prepare("INSERT IGNORE INTO favoriler (kullanici_id, ilac_id) VALUES (?,?)")->execute([$kid, $ilacId]);
        apiCevap(true, 'Favorilere eklendi.', ['favori_mi' => true]);
    }
}
apiCevap(false, 'Geçersiz istek.');
