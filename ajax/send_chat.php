<?php
require_once __DIR__ . '/../config/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

$sender_id   = (int)$_SESSION['user_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$isi_pesan   = trim($_POST['isi_pesan'] ?? '');

if ($receiver_id <= 0 || empty($isi_pesan)) {
    echo json_encode(['status' => 'error', 'message' => 'Pesan tidak boleh kosong']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, isi_pesan, is_read) VALUES (?, ?, ?, 0)");
    $stmt->execute([$sender_id, $receiver_id, $isi_pesan]);

    echo json_encode(['status' => 'success', 'message' => 'Pesan terkirim']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
