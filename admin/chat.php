<?php
require_once __DIR__ . '/../config/koneksi.php';
check_auth('admin');

$admin_id = (int)$_SESSION['user_id'];

// Ambil daftar semua siswa yang terdaftar
// Ditambah info pesan terakhir & hitungan pesan belum dibaca
$sqlStudents = "SELECT u.id, u.nama, u.email,
                (SELECT isi_pesan FROM messages 
                 WHERE (sender_id = u.id AND receiver_id = :admin1) 
                    OR (sender_id = :admin2 AND receiver_id = u.id) 
                 ORDER BY id DESC LIMIT 1) AS last_message,
                (SELECT tanggal FROM messages 
                 WHERE (sender_id = u.id AND receiver_id = :admin3) 
                    OR (sender_id = :admin4 AND receiver_id = u.id) 
                 ORDER BY id DESC LIMIT 1) AS last_time,
                (SELECT COUNT(*) FROM messages 
                 WHERE sender_id = u.id AND receiver_id = :admin5 AND is_read = 0) AS unread_count
                FROM users u
                WHERE u.role = 'user'
                ORDER BY (last_time IS NOT NULL) DESC, last_time DESC, u.nama ASC";

$stmtSiswa = $pdo->prepare($sqlStudents);
$stmtSiswa->execute([
    'admin1' => $admin_id,
    'admin2' => $admin_id,
    'admin3' => $admin_id,
    'admin4' => $admin_id,
    'admin5' => $admin_id
]);
$studentList = $stmtSiswa->fetchAll();

// Tentukan siswa yang sedang aktif diajak chat
$active_student_id = (int)($_GET['user_id'] ?? 0);
if ($active_student_id <= 0 && !empty($studentList)) {
    $active_student_id = (int)$studentList[0]['id'];
}

$active_student = null;
foreach ($studentList as $s) {
    if ((int)$s['id'] === $active_student_id) {
        $active_student = $s;
        break;
    }
}

// Jika ada siswa aktif terpilih, update pesan dari siswa ini jadi sudah dibaca
if ($active_student_id > 0) {
    $stmtRead = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmtRead->execute([$active_student_id, $admin_id]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat Siswa - Laborrow Admin</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<main class="container-fluid px-lg-4 py-4">
    <div class="chat-container">
        <!-- Sidebar List Siswa (WhatsApp Style) -->
        <div class="chat-sidebar">
            <div class="chat-sidebar-header">
                <i class="fa-solid fa-comments text-danger fa-lg"></i>
                <h6 class="fw-bold mb-0 text-dark">Percakapan Siswa</h6>
            </div>
            
            <div class="chat-user-list">
                <?php if (empty($studentList)): ?>
                    <div class="p-4 text-center text-muted small">
                        Belum ada siswa terdaftar.
                    </div>
                <?php else: ?>
                    <?php foreach ($studentList as $st): ?>
                        <div class="chat-user-item <?= ($st['id'] == $active_student_id) ? 'active' : '' ?>" 
                             onclick="window.location.href='chat.php?user_id=<?= $st['id'] ?>'">
                            <div class="chat-user-avatar">
                                <?= strtoupper(substr($st['nama'], 0, 1)) ?>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark text-truncate mb-0 small" style="max-width: 150px;">
                                        <?= htmlspecialchars($st['nama']) ?>
                                    </h6>
                                    <?php if (!empty($st['last_time'])): ?>
                                        <small class="text-muted" style="font-size: 0.7rem;">
                                            <?= date('H:i', strtotime($st['last_time'])) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted text-truncate d-block" style="max-width: 170px;">
                                        <?= !empty($st['last_message']) ? htmlspecialchars($st['last_message']) : 'Belum ada obrolan' ?>
                                    </small>
                                    <?php if ($st['unread_count'] > 0): ?>
                                        <span class="badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                                            <?= $st['unread_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($active_student): ?>
                <!-- Header Chat Aktif -->
                <div class="chat-header">
                    <div class="chat-user-avatar">
                        <?= strtoupper(substr($active_student['nama'], 0, 1)) ?>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($active_student['nama']) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($active_student['email']) ?></small>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="btnManualRefresh" title="Refresh Pesan">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>
                </div>

                <!-- Messages Box -->
                <div class="chat-messages" id="chatMessagesBox">
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border text-danger spinner-border-sm me-2" role="status"></div>
                        Memuat obrolan dengan siswa...
                    </div>
                </div>

                <!-- Chat Input Bar -->
                <form id="formChat" class="chat-input-bar">
                    <input type="hidden" name="receiver_id" id="receiver_id" value="<?= $active_student_id ?>">
                    <input type="text" name="isi_pesan" id="inputPesan" class="form-control rounded-pill px-3" placeholder="Tulis balasan untuk siswa..." autocomplete="off" required>
                    <button type="submit" class="btn btn-maroon rounded-circle" style="width: 44px; height: 44px;" id="btnKirim">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                    <div class="text-center p-4">
                        <i class="fa-solid fa-comments fa-4x text-muted opacity-25 mb-3"></i>
                        <h5>Pilih Siswa untuk Memulai Percakapan</h5>
                        <p class="small text-muted">Klik salah satu nama siswa di daftar sebelah kiri untuk membalas pesan.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<?php if ($active_student): ?>
<script>
    const currentUserId = <?= json_encode($admin_id) ?>;
    const chatPartnerId = <?= json_encode($active_student_id) ?>;
</script>
<script src="../assets/js/chat.js"></script>
<?php endif; ?>
