<?php
require_once __DIR__ . '/../config/koneksi.php';

// Hapus semua data sesi
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Mulai sesi baru untuk pesan flash
session_start();
set_flash('info', 'Sampai Jumpa!', 'Anda telah berhasil keluar dari sistem Laborrow.');

header("Location: " . base_url('auth/login.php'));
exit;
