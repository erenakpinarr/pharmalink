<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/db.php';

try {
    $kid = 2; // Semih Sevimler (kullanici_id)
    $eczaneId = 1; // Eczane id (varsayılan)
    $konu = "Test Konu";
    $mesaj = "Test Mesaj";

    echo "Başlatılıyor...\n";
    db()->beginTransaction();
    
    echo "Talepler tablosuna ekleniyor...\n";
    $stmt = db()->prepare("INSERT INTO talepler (kullanici_id, eczane_id, konu, mesaj, durum) VALUES (?, ?, ?, ?, 'bekliyor')");
    $stmt->execute([$kid, $eczaneId, $konu, $mesaj]);
    
    $talepId = db()->lastInsertId();
    echo "Talep eklendi, ID: $talepId\n";
    
    echo "talep_mesajlari tablosuna ekleniyor...\n";
    $msgStmt = db()->prepare("INSERT INTO talep_mesajlari (talep_id, gonderen_tipi, mesaj) VALUES (?, 'kullanici', ?)");
    $msgStmt->execute([$talepId, $mesaj]);
    echo "Mesaj eklendi.\n";
    
    echo "Eczane kullanıcısı aranıyor...\n";
    $eczaneSorgu = db()->prepare("SELECT kullanici_id FROM eczaneler WHERE id = ?");
    $eczaneSorgu->execute([$eczaneId]);
    $eczaneKullaniciId = $eczaneSorgu->fetchColumn();
    
    echo "Eczane Kullanıcı ID: $eczaneKullaniciId\n";
    
    require_once __DIR__ . '/includes/helpers.php';
    if ($eczaneKullaniciId) {
        echo "Bildirim gönderiliyor...\n";
        $bildirimSonuc = bildirimGonder($eczaneKullaniciId, 'Yeni Stok Talebi', 'Kullanıcı bir stok talebinde bulundu: ' . $konu, 'info', 'talep');
        if ($bildirimSonuc === false) {
            echo "bildirimGonder false döndürdü!\n";
        } else {
            echo "Bildirim eklendi.\n";
        }
    }
    
    db()->commit();
    echo "İşlem başarıyla tamamlandı.\n";

} catch (Exception $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    echo "HATA OLUŞTU: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
