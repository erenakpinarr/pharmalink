<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
$uid = mevcutKullaniciId();
$rol = mevcutRol();
$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
$action = $_GET['action'] ?? 'get';

try {
    db()->exec("CREATE TABLE IF NOT EXISTS bildirimler (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_id INT NULL,
        baslik VARCHAR(255) NOT NULL,
        mesaj TEXT NOT NULL,
        tip ENUM('basari', 'tehlike', 'uyari', 'info') DEFAULT 'info',
        kategori ENUM('sistem', 'eczane', 'ilac', 'guvenlik') DEFAULT 'sistem',
        eylemler JSON NULL,
        okundu TINYINT(1) DEFAULT 0,
        olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

if ($is_post) {
    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = db()->prepare("UPDATE bildirimler SET okundu = 1 WHERE id = ? AND (kullanici_id = ? OR kullanici_id IS NULL)");
            $stmt->execute([$id, $uid]);
        } else {
            $stmt = db()->prepare("UPDATE bildirimler SET okundu = 1 WHERE (kullanici_id = ? OR kullanici_id IS NULL)");
            $stmt->execute([$uid]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'clear') {
        $stmt = db()->prepare("DELETE FROM bildirimler WHERE (kullanici_id = ? OR kullanici_id IS NULL) AND okundu = 1");
        $stmt->execute([$uid]);
        echo json_encode(['success' => true]);
        exit;
    }
}

if ($action === 'check_new') {
    $lastId = (int)($_GET['last_id'] ?? 0);
    $sql = "SELECT * FROM bildirimler WHERE (kullanici_id = ? OR kullanici_id IS NULL) AND okundu = 0 AND id > ? ORDER BY id ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute([$uid, $lastId]);
    $newNotifs = $stmt->fetchAll();
    
    if (count($newNotifs) > 0) {
        $latest = end($newNotifs);
        echo json_encode([
            'basari' => true,
            'bildirimVar' => true,
            'sayi' => count($newNotifs),
            'yeniBildirimler' => $newNotifs,
            'tip' => $latest['tip'],
            'mesaj' => $latest['mesaj'],
            'baslik' => $latest['baslik'],
            'last_id' => $latest['id']
        ]);
    } else {
        echo json_encode(['basari' => true, 'bildirimVar' => false]);
    }
    exit;
}

$kategori = $_GET['kategori'] ?? 'hepsi';
$sql = "SELECT * FROM bildirimler WHERE (kullanici_id = ? OR kullanici_id IS NULL)";
$params = [$uid];
if ($kategori !== 'hepsi') {
    $sql .= " AND kategori = ?";
    $params[] = $kategori;
}
$sql .= " ORDER BY okundu ASC, olusturma_tarihi DESC LIMIT 50";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

foreach ($list as &$item) {
    $item['eylemler'] = json_decode($item['eylemler'], true);
    $item['zaman_insan'] = humanTime($item['olusturma_tarihi']);
}

db()->exec("DELETE FROM bildirimler WHERE olusturma_tarihi < DATE_SUB(NOW(), INTERVAL 30 DAY) AND okundu = 1");
echo json_encode($list);
function humanTime($time) {
    $ts = strtotime($time);
    $diff = time() - $ts;
    if ($diff < 60) return 'Az önce';
    if ($diff < 3600) return floor($diff / 60) . ' dk önce';
    if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
    return date('d.m.Y', $ts);
}
