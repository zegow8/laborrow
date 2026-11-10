<?php
// Pastikan sesi aktif dan otentikasi user dicek
check_auth('user');
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-laborrow sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fa-solid fa-flask-vial"></i>
            <span>LABORROW</span>
        </a>
        <button class="navbar-toggler text-white border-white" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'index.php') ? 'active' : '' ?>" href="index.php">
                        <i class="fa-solid fa-house me-1"></i> Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'katalog.php' || $current_page === 'ajukan_pinjam.php') ? 'active' : '' ?>" href="katalog.php">
                        <i class="fa-solid fa-microscope me-1"></i> Katalog Alat
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'riwayat.php') ? 'active' : '' ?>" href="riwayat.php">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> Riwayat Pinjam
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <a href="chat.php" class="btn btn-sm btn-nav-action d-flex align-items-center gap-2 <?= ($current_page === 'chat.php') ? 'shadow' : '' ?>">
                    <i class="fa-solid fa-comments"></i> Hubungi Admin
                </a>

                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle rounded-pill px-3 py-1" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-user-graduate me-1"></i> <?= htmlspecialchars($_SESSION['nama'] ?? 'Siswa') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li>
                            <div class="px-3 py-2 border-bottom">
                                <div class="fw-bold small"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></div>
                                <div class="text-muted small text-truncate" style="max-width: 180px;"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
                            </div>
                        </li>
                        <li><a class="dropdown-item py-2" href="riwayat.php"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Riwayat Pinjam</a></li>
                        <li><a class="dropdown-item py-2" href="chat.php"><i class="fa-solid fa-headset me-2 text-success"></i>Bantuan & Chat</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="../auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
