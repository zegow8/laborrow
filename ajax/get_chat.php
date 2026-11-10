<?php
require_once __DIR__ . '/../config/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$my_id = (int)$_SESSION['user_id'];
$partner_id = (int)($_GET['partner_id'] ?? 0);

if ($partner_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid partner ID']);
    exit;
}

try {
    // Ambil histori chat antara kedua user
    $sql = "SELECT id, sender_id, receiver_id, isi_pesan, tanggal 
            FROM messages 
            WHERE (sender_id = :my1 AND receiver_id = :partner1) 
               OR (sender_id = :partner2 AND receiver_id = :my2) 
            ORDER BY id ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'my1' => $my_id,
        'partner1' => $partner_id,
        'partner2' => $partner_id,
        'my2' => $my_id
    ]);
    $messages = $stmt->fetchAll();

    // Tandai pesan dari lawan bicara ke saya sebagai sudah dibaca
    $stmtUpdate = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $stmtUpdate->execute([$partner_id, $my_id]);

    $data = [];
    foreach ($messages as $msg) {
        $timestamp = strtotime($msg['tanggal']);
        $data[] = [
            'id'             => (int)$msg['id'],
            'sender_id'      => (int)$msg['sender_id'],
            'receiver_id'    => (int)$msg['receiver_id'],
            'isi_pesan'      => htmlspecialchars($msg['isi_pesan']),
            'is_me'          => ((int)$msg['sender_id'] === $my_id),
            'time_formatted' => date('H:i', $timestamp),
            'date_formatted' => date('d/m/Y', $timestamp)
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
