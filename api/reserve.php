<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

if (!mevcutKullaniciId()) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor.']);
    exit;
}

$kullaniciId = mevcutKullaniciId();
$eczaneId = (int)($_POST['eczane_id'] ?? 0);
$ilacId = (int)($_POST['ilac_id'] ?? 0);

if (!$eczaneId || !$ilacId) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi.']);
    exit;
}

// Stok kontrolü
$stok = db()->prepare("SELECT * FROM stok WHERE eczane_id = ? AND ilac_id = ? AND durum != 'tukendi' LIMIT 1");
$stok->execute([$eczaneId, $ilacId]);
if (!$stok->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Maalesef bu ilaç şu an stokta yok.']);
    exit;
}

// Rezervasyon ekle
try {
    $stmt = db()->prepare("
        INSERT INTO ayirtmalar (kullanici_id, eczane_id, ilac_id, durum, olusturma_tarihi) 
        VALUES (?, ?, ?, 'beklemede', NOW())
    ");
    $stmt->execute([$kullaniciId, $eczaneId, $ilacId]);
    
    // Eczaneye bildirim gönder (varsa bildirim sistemi)
    // bildirimGonder(...);
    
    echo json_encode(['success' => true, 'message' => 'Rezervasyon talebiniz alındı. Eczane onayını bekleyin.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
