<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/db.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS talep_mesajlari (
        id INT AUTO_INCREMENT PRIMARY KEY,
        talep_id INT NOT NULL,
        gonderen_tipi ENUM('kullanici', 'eczane') NOT NULL,
        mesaj TEXT NOT NULL,
        okundu TINYINT(1) DEFAULT 0,
        olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_talep_mesaj FOREIGN KEY (talep_id) REFERENCES talepler(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
    ";
    
    db()->exec($sql);
    echo "Tablo başarıyla oluşturuldu.";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
