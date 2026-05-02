<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
$query = trim($_GET['q'] ?? '');
if (mb_strlen($query, 'UTF-8') < 2) {
    echo json_encode([]);
    exit;
}

$lowerQuery = mb_strtolower($query, 'UTF-8');
$stmt = db()->prepare("
    SELECT ad, etken_madde, barkod 
    FROM ilaclar 
    WHERE aktif = 1 AND (LOWER(ad) LIKE ? OR LOWER(etken_madde) LIKE ? OR barkod LIKE ?) 
    ORDER BY 
        CASE 
            WHEN LOWER(ad) = ? THEN 1
            WHEN LOWER(ad) LIKE ? THEN 2
            ELSE 3
        END,
        ad ASC
    LIMIT 15
");
$searchParam = "%$lowerQuery%";
$startParam = "$lowerQuery%";
$stmt->execute([$searchParam, $searchParam, $searchParam, $lowerQuery, $startParam]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$suggestions = [];
foreach ($results as $row) {
    if (!in_array($row['ad'], $suggestions)) {
        $suggestions[] = $row['ad'];
    }
}
echo json_encode($suggestions, JSON_UNESCAPED_UNICODE);
exit;
