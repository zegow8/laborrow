<?php
require_once __DIR__ . '/../config/koneksi.php';
check_auth('user');

$my_id = (int)$_SESSION['user_id'];

// Cari ID Admin (tujuan chat)
$stmtAdmin = $pdo->query("SELECT id, nama, email FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
$admin = $stmtAdmin->fetch();
$admin_id = $admin ? (int)$admin['id'] : 1;

// Tandai pesan dari admin ke siswa ini sebagai sudah dibaca (is_read = 1)
$stmtRead = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
$stmtRead->execute([$admin_id, $my_id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Admin Laboratorium - Laborrow</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="chat-container">
                <div class="chat-area">
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div class="chat-user-avatar" style="background-color: var(--primary-maroon);">
                            <i class="fa-solid fa-headset text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($admin['nama'] ?? 'Administrator Lab') ?></h6>
                            <small class="text-success d-flex align-items-center gap-1">
                                <span class="badge rounded-pill bg-success p-1"></span> Staf Laboratorium (Online Support)
                            </small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary rounded-circle" id="btnManualRefresh" title="Refresh Pesan">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Chat Messages Box -->
                    <div class="chat-messages" id="chatMessagesBox">
                        <div class="text-center py-4 text-muted">
                            <div class="spinner-border text-danger spinner-border-sm me-2" role="status"></div>
                            Memuat percakapan...
                        </div>
                    </div>

                    <!-- Chat Input -->
                    <form id="formChat" class="chat-input-bar">
                        <input type="hidden" name="receiver_id" id="receiver_id" value="<?= $admin_id ?>">
                        <input type="text" name="isi_pesan" id="inputPesan" class="form-control rounded-pill px-3" placeholder="Ketik pesan Anda untuk admin lab..." autocomplete="off" required>
                        <button type="submit" class="btn btn-maroon rounded-circle" style="width: 44px; height: 44px;" id="btnKirim">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="text-muted small mt-2 text-center">
                <i class="fa-solid fa-bolt text-warning me-1"></i> Pesan terhubung langsung dengan sistem auto-refresh realtime.
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<script>
    const currentUserId = <?= json_encode($my_id) ?>;
    const chatPartnerId = <?= json_encode($admin_id) ?>;
</script>
<script src="../assets/js/chat.js"></script>
