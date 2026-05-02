<?php
$isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';
error_reporting($isDev ? E_ALL : E_ALL & ~E_NOTICE);
ini_set('display_errors', $isDev ? 1 : 0);
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $name = trim($parts[0]);
        $value = trim(trim($parts[1]), '"\'');
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
loadEnv(__DIR__ . '/../.env');
function loadConfig($key, $default = null) {
    return $_ENV[$key] ?? $default;
}
define('DB_HOST', loadConfig('DB_HOST', 'localhost'));
define('DB_PORT', loadConfig('DB_PORT', '3306'));
define('DB_NAME', loadConfig('DB_NAME', 'eczane_sistemi'));
define('DB_USER', loadConfig('DB_USER', 'root'));
define('DB_PASS', loadConfig('DB_PASS', ''));
define('DB_CHARSET', loadConfig('DB_CHARSET', 'utf8mb4'));
define('APP_NAME', loadConfig('APP_NAME', 'PharmaLink'));
define('APP_URL', loadConfig('APP_URL', 'http://localhost/PharmaLink'));
define('APP_VERSION', loadConfig('APP_VERSION', '1.0.0'));
define('GOOGLE_MAPS_API_KEY', loadConfig('GOOGLE_MAPS_API_KEY', ''));
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('UPLOAD_ALLOWED_TYPES', ['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
session_set_cookie_params([
    'lifetime' => (int)loadConfig('SESSION_LIFETIME', 86400),
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Strict',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Europe/Istanbul');
