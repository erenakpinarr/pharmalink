<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (mevcutRol() !== 'eczane') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

if (!hash_equals(csrf_token(), $_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız.']);
    exit;
}

$kullaniciId = mevcutKullaniciId();
$durum = isset($_POST['durum']) ? (int)$_POST['durum'] : null;

if ($durum !== 0 && $durum !== 1) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz nöbet durumu.']);
    exit;
}

try {
    $update = db()->prepare("UPDATE eczaneler SET nobetci = ? WHERE kullanici_id = ?");
    $update->execute([$durum, $kullaniciId]);

    echo json_encode([
        'success' => true, 
        'nobetci' => (bool)$durum,
        'message' => $durum ? 'Nöbet başladı, haritada görünürsünüz.' : 'Nöbet bitirildi, kapalı duruma geçtiniz.'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Sistem hatası lütfen tekrar deneyin.']);
}
