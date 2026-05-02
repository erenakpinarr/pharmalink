<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiCevap(false, 'Geçersiz metod', [], 405);
}

$adSoyad = trim($_POST['ad_soyad'] ?? '');
$email = trim($_POST['email'] ?? '');
$mesaj = trim($_POST['mesaj'] ?? '');
if (empty($adSoyad) || empty($email) || empty($mesaj)) {
    apiCevap(false, 'Lütfen tüm alanları doldurun.', [], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiCevap(false, 'Lütfen geçerli bir e-posta adresi girin.', [], 400);
}
try {
    $stmt = db()->prepare("INSERT INTO mesajlar (ad_soyad, email, mesaj) VALUES (?, ?, ?)");
    $stmt->execute([$adSoyad, $email, $mesaj]);
    apiCevap(true, 'Mesajınız başarıyla iletildi. En kısa sürede dönüş yapacağız!');
} catch (PDOException $e) {
    error_log("İletişim Formu Hatası: " . $e->getMessage());
    apiCevap(false, 'Mesaj gönderilirken sunucu tarafında bir hata oluştu.', [], 500);
}
