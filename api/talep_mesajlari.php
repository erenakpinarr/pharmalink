<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (empty($_SESSION['kullanici_id'])) {
    echo json_encode(['basari' => false, 'mesaj' => 'Oturum açmalısınız.']);
    exit;
}

$talepId = (int)($_GET['talep_id'] ?? 0);
$rol = $_SESSION['rol'] ?? '';

if (!$talepId) {
    echo json_encode(['basari' => false, 'mesaj' => 'Talep ID eksik.']);
    exit;
}

// Talebi ve yetkiyi kontrol et
$stmt = db()->prepare("SELECT * FROM talepler WHERE id = ?");
$stmt->execute([$talepId]);
$talep = $stmt->fetch();

if (!$talep) {
    echo json_encode(['basari' => false, 'mesaj' => 'Talep bulunamadı.']);
    exit;
}

if ($rol === 'kullanici' && $talep['kullanici_id'] != $_SESSION['kullanici_id']) {
    echo json_encode(['basari' => false, 'mesaj' => 'Yetkisiz erişim.']);
    exit;
}

if ($rol === 'eczane' && $talep['eczane_id'] != (int)($_SESSION['eczane_id'] ?? 0)) {
    echo json_encode(['basari' => false, 'mesaj' => 'Yetkisiz erişim.']);
    exit;
}

// Mesajları getir
$msgStmt = db()->prepare("SELECT * FROM talep_mesajlari WHERE talep_id = ? ORDER BY olusturma_tarihi ASC");
$msgStmt->execute([$talepId]);
$mesajlar = $msgStmt->fetchAll();

$formatted = [];
foreach ($mesajlar as $m) {
    $formatted[] = [
        'id' => $m['id'],
        'gonderen_tipi' => $m['gonderen_tipi'],
        'mesaj' => htmlspecialchars($m['mesaj'], ENT_QUOTES, 'UTF-8'),
        'tarih' => date('d.m.Y H:i', strtotime($m['olusturma_tarihi']))
    ];
}

echo json_encode(['basari' => true, 'mesajlar' => $formatted]);
