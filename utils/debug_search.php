<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
$pdo = db();
echo "--- VERİTABANI DURUM RAPORU ---\n\n";

$eczaneler = $pdo->query("SELECT id, eczane_adi, durum FROM eczaneler")->fetchAll();
echo "Eczane Sayısı: " . count($eczaneler) . "\n";
foreach ($eczaneler as $e) {
    echo "- {$e['eczane_adi']} ({$e['durum']})\n";
}
echo "\n";

$ilaclar = $pdo->prepare("SELECT id, ad, aktif FROM ilaclar WHERE ad LIKE ?");
$ilaclar->execute(['%Parol%']);
$pIlaclar = $ilaclar->fetchAll();
echo "Parol Eşleşen İlaç Sayısı: " . count($pIlaclar) . "\n";
foreach ($pIlaclar as $i) {
    echo "- {$i['ad']} (Aktif: {$i['aktif']})\n";
}
echo "\n";

$stok = $pdo->query("SELECT COUNT(*) FROM stok")->fetchColumn();
echo "Toplam Stok Kaydı: $stok\n";
if ($stok > 0) {
    $stokDetay = $pdo->query("
        SELECT e.eczane_adi, i.ad, s.durum 
        FROM stok s 
        JOIN eczaneler e ON e.id = s.eczane_id 
        JOIN ilaclar i ON i.id = s.ilac_id 
        LIMIT 5
    ")->fetchAll();
    echo "Örnek Stoklar:\n";
    foreach ($stokDetay as $sd) {
        echo "- {$sd['eczane_adi']} -> {$sd['ad']} ({$sd['durum']})\n";
    }
}
echo "\n------------------------------\n";
