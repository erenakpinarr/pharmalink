<?php
require_once __DIR__ . '/config.php';
class Veritabani {
    private static ?PDO $baglanti = null;
    public static function baglan(): PDO {
        if (self::$baglanti === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $secenekler = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_general_ci",
            ];
            try {
                self::$baglanti = new PDO($dsn, DB_USER, DB_PASS, $secenekler);
            } catch (PDOException $e) {
                error_log("Veritabanı Hatası: " . $e->getMessage());
                die('<div style="font-family:sans-serif; text-align:center; padding:5rem; color:#64748b;">
                    <h1 style="color:#0f172a;">⚠ Veritabanı Bağlantı Hatası</h1>
                    <p>Sistem şu anda geçici bir teknik sorun nedeniyle erişilemez durumda.</p>
                </div>');
            }
        }
        return self::$baglanti;
    }
    private function __construct() {}
    private function __clone() {}
}
function db(): PDO {
    return Veritabani::baglan();
}
