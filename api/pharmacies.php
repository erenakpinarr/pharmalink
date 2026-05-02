<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
if (empty($_SESSION['kullanici_id'])) apiCevap(false, 'Yetkisiz.', [], 401);
if (!isAjax()) apiCevap(false, 'Geçersiz istek türü.', [], 403);
$islem = $_POST['islem'] ?? '';
$kid   = mevcutKullaniciId();
if ($islem === 'nobetci_guncelle' && mevcutRol() === 'eczane') {
    $durum = (int)($_POST['durum'] ?? 0);
    db()->prepare("UPDATE eczaneler SET nobetci=? WHERE kullanici_id=?")->execute([$durum, $kid]);
    apiCevap(true, 'Nöbet durumu güncellendi.', ['nobetci' => $durum]);
}
apiCevap(false, 'Bilinmeyen işlem.');
