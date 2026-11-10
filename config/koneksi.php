<?php
/**
 * Koneksi Database & Konfigurasi Global Laborrow
 * Menggunakan PDO (PHP Data Objects)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'laborrow';
$db_user = 'root';
$db_pass = '';

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// Base URL helper
function base_url($path = '') {
    // Deteksi protokol dan host otomatis
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Tentukan direktori root laborrow relatif terhadap DOCUMENT_ROOT jika ada
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $appDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $subDir = trim(str_replace($docRoot, '', $appDir), '/');
    
    $base = $protocol . $host . ($subDir ? '/' . $subDir : '');
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

// Flash Message Helpers (SweetAlert2)
function set_flash($type, $title, $text) {
    $_SESSION['flash_message'] = [
        'type'  => $type, // 'success', 'error', 'warning', 'info'
        'title' => $title,
        'text'  => $text
    ];
}

function render_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        $type = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($flash['title'], ENT_QUOTES, 'UTF-8');
        $text = htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8');
        return "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: '{$type}',
                    title: '{$title}',
                    text: '{$text}',
                    confirmButtonColor: '#800020'
                });
            });
        </script>";
    }
    return '';
}

// Auth Guard
function check_auth($allowed_role = null) {
    if (!isset($_SESSION['user_id'])) {
        set_flash('warning', 'Akses Ditolak', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        header("Location: " . base_url('auth/login.php'));
        exit;
    }

    if ($allowed_role !== null && $_SESSION['role'] !== $allowed_role) {
        set_flash('error', 'Akses Terlarang', 'Anda tidak memiliki hak akses ke halaman tersebut.');
        if ($_SESSION['role'] === 'admin') {
            header("Location: " . base_url('admin/index.php'));
        } else {
            header("Location: " . base_url('user/index.php'));
        }
        exit;
    }
}

// Format Tanggal Indonesia
function format_tanggal_indo($tanggal) {
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
    $timestamp = strtotime($tanggal);
    if (!$timestamp) return $tanggal;
    
    $bulan_indo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('d', $timestamp);
    $m = (int)date('m', $timestamp);
    $y = date('Y', $timestamp);
    return "{$d} {$bulan_indo[$m]} {$y}";
}

function format_datetime_indo($datetime) {
    if (empty($datetime)) return '-';
    $timestamp = strtotime($datetime);
    if (!$timestamp) return $datetime;
    return date('d/m/Y H:i', $timestamp);
}
