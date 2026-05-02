<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';

$pdo = db();

echo "Demo Veri Yüklemesi Başlıyor...\n";

// 1. Bir Test Eczane Kullanıcısı Oluştur
$email = 'test_eczane@demo.com';
$stmt = $pdo->prepare("SELECT id FROM kullanicilar WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $pdo->prepare("INSERT INTO kullanicilar (ad, soyad, email, sifre, rol) VALUES (?,?,?,?,?)")
        ->execute(['Demo', 'Eczacı', $email, password_hash('123456', PASSWORD_DEFAULT), 'eczane']);
    $userId = $pdo->lastInsertId();
} else {
    $userId = $user['id'];
}

// 2. Eczane Kaydı
$stmt = $pdo->prepare("SELECT id FROM eczaneler WHERE kullanici_id = ?");
$stmt->execute([$userId]);
$eczane = $stmt->fetch();

if (!$eczane) {
    $pdo->prepare("INSERT INTO eczaneler (kullanici_id, eczane_adi, adres, sehir, ilce, telefon, enlem, boylam, durum) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([
            $userId, 
            'Şifa Eczanesi (Demo)', 
            'Kadıköy Rıhtım No: 5', 
            'İstanbul', 
            'Kadıköy', 
            '02165551122', 
            40.9911, // İstanbul/Kadıköy coordinates
            29.0275, 
            'onaylandi'
        ]);
    $eczaneId = $pdo->lastInsertId();
    echo "Demo Eczane 'Şifa Eczanesi' oluşturuldu.\n";
} else {
    $eczaneId = $eczane['id'];
    $pdo->prepare("UPDATE eczaneler SET durum='onaylandi' WHERE id=?")->execute([$eczaneId]);
    echo "Mevcut eczane 'onaylandi' durumuna getirildi.\n";
}

// 3. Parol İlacını Bul ve Stoğa Ekle
$stmt = $pdo->prepare("SELECT id FROM ilaclar WHERE ad LIKE ? LIMIT 1");
$stmt->execute(['%Parol%']);
$ilac = $stmt->fetch();

if ($ilac) {
    $pdo->prepare("INSERT IGNORE INTO stok (eczane_id, ilac_id, durum) VALUES (?,?,?)")
        ->execute([$eczaneId, $ilac['id'], 'mevcut']);
    echo "'{$ilac['id']}' ID'li Parol ilacı eczane stoğuna eklendi.\n";
} else {
    echo "HATA: Parol ilacı veritabanında bulunamadı!\n";
}

echo "İşlem Tamam!\n";



