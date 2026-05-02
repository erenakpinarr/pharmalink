<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

rolKontrol('eczane');
csrf_verify(); 
$kid = mevcutKullaniciId();
$islem = $_POST['islem'] ?? '';

$eczane = db()->prepare("SELECT id FROM eczaneler WHERE kullanici_id = ? LIMIT 1");
$eczane->execute([$kid]);
$ecz = $eczane->fetch();
if (!$ecz) {
    http_response_code(404);
    echo json_encode(['error' => 'Eczane bulunamadı']);
    exit;
}
if ($islem === 'nobetci_guncelle') {
    $durum = (int)($_POST['durum'] ?? 0);
    $stmt = db()->prepare("UPDATE eczaneler SET nobetci = ? WHERE id = ?");
    if ($stmt->execute([$durum, $ecz['id']])) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false]);
    }
    exit;
}
http_response_code(400);
echo json_encode(['error' => 'Geçersiz işlem']);
