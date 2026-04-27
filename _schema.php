<?php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/db.php';

$tables = db()->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "=== TABLES ===\n";
foreach ($tables as $t) {
    echo "\n[$t]\n";
    $cols = db()->query("DESCRIBE `$t`")->fetchAll();
    foreach ($cols as $c) {
        echo "  {$c['Field']}  {$c['Type']}" . ($c['Key'] ? " [{$c['Key']}]" : "") . "\n";
    }
}
