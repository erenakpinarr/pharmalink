<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/helpers.php';
if (!function_exists('girisKontrol')) {
    function girisKontrol(): void {
        if (empty($_SESSION['kullanici_id'])) {
            yonlendir(sayf('auth/login.php?mesaj=giris_gerekli'));
        }
    }
}
if (!function_exists('rolKontrol')) {
    function rolKontrol(string ...$roller): void {
        girisKontrol();
        if (!in_array($_SESSION['rol'] ?? '', $roller, true)) {
            yonlendir(sayf('pages/yetkisiz.php'));
        }
    }
}
if (!function_exists('mevcutRol')) {
    function mevcutRol(): string {
        return $_SESSION['rol'] ?? '';
    }
}
if (!function_exists('mevcutKullaniciId')) {
    function mevcutKullaniciId(): int {
        return (int)($_SESSION['kullanici_id'] ?? 0);
    }
}
if (!function_exists('panelUrl')) {
    function panelUrl(string $rol): string {
        return match ($rol) {
            'admin'     => 'admin/index.php',
            'eczane'    => 'pharmacy/index.php',
            'kullanici' => 'user/reservations.php',
            default     => 'auth/login.php',
        };
    }
}
if (!function_exists('yonlendir')) {
    function yonlendir(string $url): never {
        header('Location: ' . $url);
        exit;
    }
}
if (!function_exists('flashMesajAyarla')) {
    function flashMesajAyarla(string $tip, string $mesaj): void {
        $_SESSION['flash'] = ['tip' => $tip, 'mesaj' => $mesaj];
    }
}
if (!function_exists('flashMesajAl')) {
    function flashMesajAl(): ?array {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
