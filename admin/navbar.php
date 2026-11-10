<?php
// Cek otentikasi admin
check_auth('admin');
$current_page = basename($_SERVER['PHP_SELF']);

// Hitung total peminjaman pending untuk badge notifikasi di navbar
$stmtPendingCount = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'Pending'");
$navPendingCount = (int)$stmtPendingCount->fetchColumn();

// Hitung unread messages untuk admin
$stmtUnreadChat = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmtUnreadChat->execute([$_SESSION['user_id']]);
$navUnreadChat = (int)$stmtUnreadChat->fetchColumn();
?>
<nav class="navbar navbar-expand-lg navbar-laborrow sticky-top">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand" href="index.php">
            <i class="fa-solid fa-flask-vial"></i>
            <span>LABORROW <span class="badge bg-danger text-uppercase fw-normal fs-6 ms-1">Admin</span></span>
        </a>
        <button class="navbar-toggler text-white border-white" type="button" data-bs-toggle="collapse" data-bs-target="#navAdminContent">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navAdminContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'index.php') ? 'active' : '' ?>" href="index.php">
                        <i class="fa-solid fa-gauge me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'kategori.php') ? 'active' : '' ?>" href="kategori.php">
                        <i class="fa-solid fa-tags me-1"></i> Kategori
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'alat.php') ? 'active' : '' ?>" href="alat.php">
                        <i class="fa-solid fa-microscope me-1"></i> Inventaris Alat
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative <?= ($current_page === 'peminjaman.php') ? 'active' : '' ?>" href="peminjaman.php">
                        <i class="fa-solid fa-hand-holding-hand me-1"></i> Peminjaman
                        <?php if ($navPendingCount > 0): ?>
                            <span class="badge rounded-pill bg-danger ms-1"><?= $navPendingCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'daftar_siswa.php') ? 'active' : '' ?>" href="daftar_siswa.php">
                        <i class="fa-solid fa-users me-1"></i> Data Siswa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative <?= ($current_page === 'chat.php') ? 'active' : '' ?>" href="chat.php">
                        <i class="fa-solid fa-comments me-1"></i> Live Chat
                        <?php if ($navUnreadChat > 0): ?>
                            <span class="badge rounded-pill bg-warning text-dark ms-1"><?= $navUnreadChat ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle rounded-pill px-3 py-1" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-user-shield me-1"></i> <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li>
                            <div class="px-3 py-2 border-bottom">
                                <div class="fw-bold small"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
                                <span class="badge bg-danger-subtle text-danger border mt-1">Administrator Lab</span>
                            </div>
                        </li>
                        <li><a class="dropdown-item py-2" href="peminjaman.php"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Kelola Pinjaman</a></li>
                        <li><a class="dropdown-item py-2" href="chat.php"><i class="fa-solid fa-headset me-2 text-success"></i>Pesan Masuk</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="../auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
