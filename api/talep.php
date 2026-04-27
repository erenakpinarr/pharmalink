<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz istek.']);
    exit;
}

csrf_verify();

$islem = $_POST['islem'] ?? '';

if ($islem === 'talep_olustur') {
    rolKontrol('kullanici', true);
    $kid = mevcutKullaniciId();
    $eczaneId = (int)($_POST['eczane_id'] ?? 0);
    $konu = strip_tags(trim($_POST['konu'] ?? ''));
    $mesaj = strip_tags(trim($_POST['mesaj'] ?? ''));
    
    if (!$eczaneId || !$konu || !$mesaj) {
        echo json_encode(['basari' => false, 'mesaj' => 'Eksik bilgi girdiniz.']);
        exit;
    }
    
    try {
        db()->beginTransaction();
        
        $stmt = db()->prepare("INSERT INTO talepler (kullanici_id, eczane_id, konu, mesaj, durum) VALUES (?, ?, ?, ?, 'bekliyor')");
        $stmt->execute([$kid, $eczaneId, $konu, $mesaj]);
        
        $talepId = db()->lastInsertId();
        
        // İlk mesajı talep_mesajlari tablosuna ekle
        $msgStmt = db()->prepare("INSERT INTO talep_mesajlari (talep_id, gonderen_tipi, mesaj) VALUES (?, 'kullanici', ?)");
        $msgStmt->execute([$talepId, $mesaj]);
        
        // Eczaneye bildirim gönder
        $eczaneSorgu = db()->prepare("SELECT kullanici_id FROM eczaneler WHERE id = ?");
        $eczaneSorgu->execute([$eczaneId]);
        $eczaneKullaniciId = $eczaneSorgu->fetchColumn();
        if ($eczaneKullaniciId) {
            bildirimGonder($eczaneKullaniciId, 'Yeni Stok Talebi', 'Kullanıcı bir stok talebinde bulundu: ' . $konu, 'info', 'talep');
        }
        
        db()->commit();
        echo json_encode(['basari' => true]);
    } catch (Exception $e) {
        db()->rollBack();
        echo json_encode(['basari' => false, 'mesaj' => 'Veritabanı hatası.']);
    }
} elseif ($islem === 'mesaj_gonder') {
    if (empty($_SESSION['kullanici_id'])) {
        echo json_encode(['basari' => false, 'mesaj' => 'Oturum açmalısınız.']);
        exit;
    }
    $talepId = (int)($_POST['talep_id'] ?? 0);
    $mesaj = strip_tags(trim($_POST['mesaj'] ?? ''));
    $rol = $_SESSION['rol'];
    
    if (!$talepId || !$mesaj) {
        echo json_encode(['basari' => false, 'mesaj' => 'Mesaj boş olamaz.']);
        exit;
    }
    
    // Talebi doğrula ve durumunu güncelle
    $talep = db()->prepare("SELECT * FROM talepler WHERE id = ?");
    $talep->execute([$talepId]);
    $talepBilgi = $talep->fetch();
    
    if (!$talepBilgi) {
        echo json_encode(['basari' => false, 'mesaj' => 'Talep bulunamadı.']);
        exit;
    }
    
    // Yetki kontrolü
    if ($rol === 'kullanici' && $talepBilgi['kullanici_id'] != $_SESSION['kullanici_id']) {
        echo json_encode(['basari' => false, 'mesaj' => 'Yetkisiz erişim.']);
        exit;
    }
    if ($rol === 'eczane' && $talepBilgi['eczane_id'] != (int)($_SESSION['eczane_id'] ?? 0)) {
        echo json_encode(['basari' => false, 'mesaj' => 'Yetkisiz erişim.']);
        exit;
    }
    
    $gonderenTipi = ($rol === 'eczane') ? 'eczane' : 'kullanici';
    $yeniDurum = ($rol === 'eczane') ? 'yanitlandi' : 'bekliyor';
    
    try {
        db()->beginTransaction();
        $msgStmt = db()->prepare("INSERT INTO talep_mesajlari (talep_id, gonderen_tipi, mesaj) VALUES (?, ?, ?)");
        $msgStmt->execute([$talepId, $gonderenTipi, $mesaj]);
        
        $updStmt = db()->prepare("UPDATE talepler SET durum = ?, guncelleme_tarihi = NOW() WHERE id = ?");
        $updStmt->execute([$yeniDurum, $talepId]);
        
        // Bildirim
        if ($rol === 'eczane') {
            bildirimGonder($talepBilgi['kullanici_id'], 'Talebinize Yanıt Geldi', 'Eczane stok talebinize yanıt verdi.', 'info', 'talep');
        } else {
            $eczaneSorgu = db()->prepare("SELECT kullanici_id FROM eczaneler WHERE id = ?");
            $eczaneSorgu->execute([$talepBilgi['eczane_id']]);
            $eczaneKullaniciId = $eczaneSorgu->fetchColumn();
            if ($eczaneKullaniciId) {
                bildirimGonder($eczaneKullaniciId, 'Talep Mesajı', 'Kullanıcı talebe mesaj gönderdi.', 'info', 'talep');
            }
        }
        
        db()->commit();
        echo json_encode(['basari' => true]);
    } catch(Exception $e) {
        db()->rollBack();
        echo json_encode(['basari' => false, 'mesaj' => 'Kayıt hatası.']);
    }
} elseif ($islem === 'durum_guncelle') {
    rolKontrol('eczane', true);
    $talepId = (int)($_POST['talep_id'] ?? 0);
    $durum = $_POST['durum'] ?? '';
    
    if (!in_array($durum, ['onaylandi', 'iptal', 'teslim_edildi'])) {
        echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz durum.']);
        exit;
    }
    
    $stmt = db()->prepare("UPDATE talepler SET durum = ? WHERE id = ? AND eczane_id = ?");
    $stmt->execute([$durum, $talepId, $_SESSION['eczane_id']]);
    
    echo json_encode(['basari' => true, 'mesaj' => 'Durum güncellendi.']);
} else {
    echo json_encode(['basari' => false, 'mesaj' => 'Geçersiz işlem.']);
}
