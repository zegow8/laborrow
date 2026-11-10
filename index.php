<?php
require_once __DIR__ . '/config/koneksi.php';

// Cek status sesi login (jika ada, sesuaikan tombol navigasi)
$isLoggedIn = isset($_SESSION['user_id']);
$userRole   = $_SESSION['role'] ?? null;
$userName   = $_SESSION['nama'] ?? '';

// Ambil beberapa alat terbaru dari database
$stmtTools = $pdo->query("SELECT t.*, c.nama_kategori 
                          FROM tools t 
                          LEFT JOIN categories c ON t.category_id = c.id 
                          ORDER BY t.id DESC LIMIT 4");
$popularTools = $stmtTools->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laborrow - Sistem Peminjaman Alat Laboratorium Modern</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom Modern Design System -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <!-- NAVBAR PUBLIK -->
    <nav class="navbar navbar-expand-lg public-nav sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fa-solid fa-flask-vial"></i>
                <span>LABORROW</span>
            </a>
            <button class="navbar-toggler text-white border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navPublicContent">
                <i class="fa-solid fa-bars text-white fs-4"></i>
            </button>
            <div class="collapse navbar-collapse" id="navPublicContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link active" href="#beranda">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#fitur">Keunggulan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#alur">Alur Pinjam</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#katalog">Koleksi Alat</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($isLoggedIn): ?>
                        <!-- Jika sudah login -->
                        <?php if ($userRole === 'admin'): ?>
                            <a href="admin/index.php" class="btn btn-sm btn-nav-action">
                                <i class="fa-solid fa-gauge me-1"></i> Dashboard Admin
                            </a>
                        <?php else: ?>
                            <a href="user/index.php" class="btn btn-sm btn-nav-action">
                                <i class="fa-solid fa-user-graduate me-1"></i> Area Siswa
                            </a>
                        <?php endif; ?>
                        <a href="auth/logout.php" class="btn btn-sm btn-outline-light rounded-pill px-3">
                            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Keluar
                        </a>
                    <?php else: ?>
                        <!-- Jika belum login -->
                        <a href="auth/login.php" class="btn btn-sm btn-outline-light rounded-pill px-3 py-2 fw-semibold">
                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Masuk
                        </a>
                        <a href="auth/register.php" class="btn btn-sm btn-nav-action">
                            <i class="fa-solid fa-user-plus me-1"></i> Registrasi Siswa
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <!-- HERO SECTION -->
        <section class="hero-section" id="beranda">
            <div class="container position-relative" style="z-index: 2;">
                <div class="row align-items-center g-5">
                    
                    <!-- Left Hero Content -->
                    <div class="col-lg-6 text-center text-lg-start">
                        <div class="hero-badge mb-3">
                            <span class="pulsing-dot"></span>
                            <span>Laboratorium Digital &bull; Bebas Antre & Bebas Kertas</span>
                        </div>
                        <h1 class="hero-title mb-3">
                            Peminjaman Alat Lab Jadi <span class="text-gradient-soft">Terstruktur & Cepat.</span>
                        </h1>
                        <p class="lead text-white-50 mb-4" style="font-size: 1.1rem; line-height: 1.7;">
                            Kelola jadwal praktikum dengan presisi. Cek stok inventaris alat secara real-time, ajukan permohonan dengan validasi otomatis, dan berinteraksi langsung dengan laboran.
                        </p>
                        
                        <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start mb-4">
                            <?php if ($isLoggedIn): ?>
                                <a href="<?= ($userRole === 'admin') ? 'admin/index.php' : 'user/katalog.php' ?>" class="btn btn-light text-maroon px-4 py-3 fw-bold rounded-pill shadow-lg">
                                    <i class="fa-solid fa-arrow-right me-2"></i> Lanjutkan ke Aplikasi
                                </a>
                            <?php else: ?>
                                <a href="auth/login.php" class="btn btn-light text-maroon px-4 py-3 fw-bold rounded-pill shadow-lg">
                                    <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk ke Akun
                                </a>
                                <a href="auth/register.php" class="btn btn-outline-light px-4 py-3 fw-bold rounded-pill">
                                    <i class="fa-solid fa-user-plus me-2"></i> Daftar Akun Siswa
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Micro trust badges -->
                        <div class="d-flex flex-wrap align-items-center gap-3 justify-content-center justify-content-lg-start text-white-50 small">
                            <span><i class="fa-solid fa-circle-check text-success me-1"></i> Validasi Real-time</span>
                            <span>&bull;</span>
                            <span><i class="fa-solid fa-circle-check text-success me-1"></i> Chat Staf Terpadu</span>
                            <span>&bull;</span>
                            <span><i class="fa-solid fa-circle-check text-success me-1"></i> Tanpa Birokrasi Rumit</span>
                        </div>
                    </div>

                    <!-- Right Hero Content: Interactive Showcase Card (Bukan kotak kaku AI Slop) -->
                    <div class="col-lg-6">
                        <div class="position-relative mx-auto" style="max-width: 480px;">
                            
                            <!-- Floating Chip 1 -->
                            <div class="hero-floating-chip chip-top-left">
                                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1"><i class="fa-solid fa-bolt"></i></span>
                                <span>Approval Cepat & Otomatis</span>
                            </div>

                            <!-- Showcase Card -->
                            <div class="hero-showcase-card">
                                <!-- Mockup Top Header -->
                                <div class="mockup-app-header">
                                    <div class="mockup-dots">
                                        <span class="dot-red"></span>
                                        <span class="dot-yellow"></span>
                                        <span class="dot-green"></span>
                                    </div>
                                    <div class="small text-white-50 font-monospace" style="font-size: 0.78rem;">
                                        <i class="fa-solid fa-lock me-1"></i> laborrow.local/app
                                    </div>
                                    <div class="d-flex align-items-center gap-1 small text-success fw-bold" style="font-size: 0.78rem;">
                                        <span class="pulsing-dot"></span> Live
                                    </div>
                                </div>

                                <!-- Mockup Body Ticket -->
                                <div class="mockup-preview-inner">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="badge bg-light text-muted border small mb-1">Tiket Peminjaman #LB-2026</span>
                                            <h6 class="fw-bold mb-0 text-dark">Mikroskop Binokuler CX23</h6>
                                        </div>
                                        <span class="badge-status badge-disetujui">
                                            <i class="fa-solid fa-circle-check"></i> Disetujui
                                        </span>
                                    </div>

                                    <div class="p-3 rounded-3 bg-light mb-3">
                                        <div class="row g-2 small">
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.72rem;">PEMINJAM</span>
                                                <span class="fw-bold text-dark">Siswa Demo (Praktikan)</span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.72rem;">STATUS STOK</span>
                                                <span class="fw-bold text-success"><i class="fa-solid fa-check"></i> Unit Terverifikasi</span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.72rem;">TANGGAL PINJAM</span>
                                                <span class="fw-semibold text-dark"><?= date('d M Y') ?></span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.72rem;">LOKASI PENGAMBILAN</span>
                                                <span class="fw-semibold text-dark">Lab IPA &ndash; Rak B-04</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="chat-user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                <i class="fa-solid fa-user-shield"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.78rem;">Laboran Lab</div>
                                                <div class="text-muted" style="font-size: 0.7rem;">Diverifikasi Petugas</div>
                                            </div>
                                        </div>
                                        <span class="badge bg-maroon text-white px-3 py-1 fw-semibold small">Siap Diambil</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Floating Chip 2 -->
                            <div class="hero-floating-chip chip-bottom-right">
                                <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1"><i class="fa-solid fa-comment-dots"></i></span>
                                <span>Live Chat Staf Lab</span>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- SECTION BENTO GRID (KEUNGGULAN SISTEM) -->
        <section class="container py-5 my-3" id="fitur">
            <div class="text-center mb-5">
                <span class="badge px-3 py-2 rounded-pill fw-bold mb-2" style="background-color: var(--soft-pink); color: var(--primary-maroon);">
                    KEUNGGULAN UTAMA
                </span>
                <h2 class="fw-bold section-title">Dirancang untuk Efisiensi Laboratorium</h2>
                <p class="text-muted col-lg-6 mx-auto">
                    Kombinasi fitur cerdas untuk memastikan inventaris lab terpantau rapi dan jadwal praktikum berjalan lancar.
                </p>
            </div>

            <div class="row g-4">
                <!-- Bento 1: Stok Real-time -->
                <div class="col-md-6 col-lg-4">
                    <div class="bento-card">
                        <div class="bento-icon">
                            <i class="fa-solid fa-cubes-stacked"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Sinkronisasi Stok Real-Time</h5>
                        <p class="text-muted small mb-0">
                            Stok fisik alat langsung terpotong saat permohonan disetujui, dan otomatis bertambah saat alat dikembalikan. Mencegah bentrok jadwal antar siswa.
                        </p>
                    </div>
                </div>

                <!-- Bento 2: Validasi Ketat -->
                <div class="col-md-6 col-lg-4">
                    <div class="bento-card">
                        <div class="bento-icon">
                            <i class="fa-solid fa-calendar-shield"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Validasi Tanggal Ketat</h5>
                        <p class="text-muted small mb-0">
                            Sistem secara cerdas menolak tanggal di masa lalu dan memastikan tanggal kembali tidak mendahului tanggal pinjam, baik di sisi peramban maupun server.
                        </p>
                    </div>
                </div>

                <!-- Bento 3: Live Chat Terpadu -->
                <div class="col-md-6 col-lg-4">
                    <div class="bento-card">
                        <div class="bento-icon">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Live Chat Siswa & Laboran</h5>
                        <p class="text-muted small mb-0">
                            Konsultasi ketersediaan dan konfirmasi praktikum lebih cepat lewat antarmuka chat WhatsApp-style dengan pembaruan otomatis setiap 3 detik.
                        </p>
                    </div>
                </div>

                <!-- Bento 4: 4 Status Warna -->
                <div class="col-md-6 col-lg-6">
                    <div class="bento-card">
                        <div class="bento-icon">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Tracking Status Berwarna Jelas</h5>
                        <p class="text-muted small mb-3">
                            Transparansi penuh status peminjaman dengan indikator warna: Kuning (Pending), Hijau (Disetujui), Merah (Ditolak dengan alasan), dan Biru (Dikembalikan).
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-auto">
                            <span class="badge-status badge-pending"><i class="fa-solid fa-hourglass-half"></i> Pending</span>
                            <span class="badge-status badge-disetujui"><i class="fa-solid fa-check"></i> Disetujui</span>
                            <span class="badge-status badge-ditolak"><i class="fa-solid fa-xmark"></i> Ditolak</span>
                            <span class="badge-status badge-dikembalikan"><i class="fa-solid fa-box-archive"></i> Dikembalikan</span>
                        </div>
                    </div>
                </div>

                <!-- Bento 5: Keamanan & Database Transaksi -->
                <div class="col-md-6 col-lg-6">
                    <div class="bento-card">
                        <div class="bento-icon">
                            <i class="fa-solid fa-database"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Database Transaksi Atomik & Aman</h5>
                        <p class="text-muted small mb-0">
                            Dibangun menggunakan PHP PDO Prepared Statements dan Database Transaction. Menjamin tidak ada data stok yang hilang atau inkonsisten saat dipinjam bersamaan.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION ALUR PEMINJAMAN (TIMELINE) -->
        <section class="py-5" style="background-color: #FFF2F2;" id="alur">
            <div class="container py-3">
                <div class="text-center mb-5">
                    <span class="badge px-3 py-2 rounded-pill fw-bold mb-2" style="background-color: #ffffff; color: var(--primary-maroon); border: 1px solid var(--soft-pink-border);">
                        CARA KERJA
                    </span>
                    <h2 class="fw-bold section-title">Alur Peminjaman Praktis</h2>
                    <p class="text-muted col-lg-6 mx-auto">Tiga langkah mudah untuk menyelesaikan permohonan peminjaman alat laboratorium Anda.</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="step-track-card text-center">
                            <div class="step-number-badge">1</div>
                            <h5 class="fw-bold mb-2">Pilih Alat & Cek Stok</h5>
                            <p class="text-muted small mb-0">
                                Telusuri katalog alat lab, lihat deskripsi spesifikasi dan pastikan stok unit berstatus <strong>Tersedia</strong> sebelum meminjam.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="step-track-card text-center">
                            <div class="step-number-badge">2</div>
                            <h5 class="fw-bold mb-2">Isi Jadwal Peminjaman</h5>
                            <p class="text-muted small mb-0">
                                Masukkan tanggal mulai dan batas waktu pengembalian. Formulir dilengkapi validasi otomatis bebas bentrok.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="step-track-card text-center">
                            <div class="step-number-badge">3</div>
                            <h5 class="fw-bold mb-2">Verifikasi & Ambil di Lab</h5>
                            <p class="text-muted small mb-0">
                                Laboran memverifikasi pengajuan. Begitu status berubah <strong>Disetujui</strong>, alat siap diambil di ruang laboratorium.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION KOLEKSI ALAT TERBARU (DENGAN EMPTY STATE ESTETIK) -->
        <section class="container py-5 my-4" id="katalog">
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
                <div>
                    <span class="badge px-3 py-2 rounded-pill fw-bold mb-2" style="background-color: var(--soft-pink); color: var(--primary-maroon);">
                        INVENTARIS LABORATORIUM
                    </span>
                    <h2 class="fw-bold mb-0">Koleksi Peralatan Laboratorium</h2>
                </div>
                <div class="mt-2 mt-md-0">
                    <?php if ($isLoggedIn && $userRole === 'user'): ?>
                        <a href="user/katalog.php" class="btn btn-outline-maroon btn-sm">
                            Buka Seluruh Katalog <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    <?php else: ?>
                        <a href="auth/login.php" class="btn btn-outline-maroon btn-sm">
                            Masuk untuk Meminjam <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($popularTools)): ?>
                <!-- EMPTY STATE VISUAL MODERN (Bukan AI Slop) -->
                <div class="empty-state">
                    <div class="empty-state-icon-wrapper">
                        <i class="fa-solid fa-boxes-packing"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Inventaris Belum Memiliki Alat</h4>
                    <p class="text-muted col-md-6 mx-auto mb-4 small" style="line-height: 1.6;">
                        Database alat saat ini masih bersih (tanpa data tiruan). Administrator Lab dapat masuk sekarang untuk menambahkan kategori dan inventaris alat pertama.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="auth/login.php" class="btn btn-maroon px-4 py-2">
                            <i class="fa-solid fa-user-shield me-1"></i> Masuk Sebagai Admin Lab
                        </a>
                        <a href="auth/register.php" class="btn btn-outline-maroon px-4 py-2">
                            <i class="fa-solid fa-user-plus me-1"></i> Daftar Sebagai Siswa
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($popularTools as $tool): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-laborrow h-100">
                                <?php if (!empty($tool['foto']) && file_exists(__DIR__ . '/assets/img/' . $tool['foto'])): ?>
                                    <img src="assets/img/<?= htmlspecialchars($tool['foto']) ?>" class="card-img-top" alt="<?= htmlspecialchars($tool['nama_alat']) ?>" style="height: 190px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 190px;">
                                        <i class="fa-solid fa-flask fa-3x" style="color: var(--soft-pink);"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <span class="badge bg-light text-dark border mb-2"><?= htmlspecialchars($tool['nama_kategori'] ?? 'Umum') ?></span>
                                        <h5 class="card-title fw-bold text-truncate" title="<?= htmlspecialchars($tool['nama_alat']) ?>"><?= htmlspecialchars($tool['nama_alat']) ?></h5>
                                        <p class="card-text text-muted small line-clamp-2"><?= htmlspecialchars(substr($tool['deskripsi'] ?? 'Tidak ada deskripsi', 0, 75)) ?>...</p>
                                    </div>
                                    <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                                        <?php if ($tool['stok'] > 0): ?>
                                            <span class="badge bg-success-subtle text-success fw-bold">Tersedia: <?= $tool['stok'] ?></span>
                                            <a href="user/ajukan_pinjam.php?id=<?= $tool['id'] ?>" class="btn btn-sm btn-maroon">Pinjam</a>
                                        <?php elseif ($tool['stok_rusak'] > 0): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Dalam Servis</span>
                                            <button class="btn btn-sm btn-secondary" disabled>Servis</button>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Stok Habis</span>
                                            <button class="btn btn-sm btn-secondary" disabled>Habis</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- CTA SECTION BAWAH (MODERN RADIAL BANNER) -->
        <section class="container my-5">
            <div class="cta-banner-modern text-center position-relative">
                <div class="position-relative" style="z-index: 2;">
                    <span class="hero-badge mb-3">
                        <i class="fa-solid fa-sparkles me-1"></i> Mulai Praktikum Sekarang
                    </span>
                    <h2 class="display-6 fw-bold mb-3 text-white">Kelola Praktikum Lebih Cepat & Bebas Kendala</h2>
                    <p class="col-lg-6 mx-auto text-white-50 mb-4" style="font-size: 1.05rem;">
                        Gabung bersama ratusan praktikan yang sudah beralih ke sistem peminjaman laboratorium digital modern.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <?php if ($isLoggedIn): ?>
                            <a href="<?= ($userRole === 'admin') ? 'admin/index.php' : 'user/katalog.php' ?>" class="btn btn-light text-maroon px-4 py-3 fw-bold rounded-pill shadow-lg">
                                <i class="fa-solid fa-arrow-right me-2"></i> Buka Panel Sistem
                            </a>
                        <?php else: ?>
                            <a href="auth/register.php" class="btn btn-light text-maroon px-4 py-3 fw-bold rounded-pill shadow-lg">
                                <i class="fa-solid fa-user-plus me-2"></i> Registrasi Siswa Baru
                            </a>
                            <a href="auth/login.php" class="btn btn-outline-light px-4 py-3 fw-bold rounded-pill">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk Sekarang
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER PUBLIK -->
    <footer class="footer-laborrow">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <h5 class="text-white fw-bold mb-1 d-flex align-items-center gap-2 justify-content-center justify-content-md-start">
                        <i class="fa-solid fa-flask-vial text-danger"></i> LABORROW
                    </h5>
                    <p class="text-white-50 mb-0 small">Sistem Peminjaman Alat Laboratorium Praktis, Terintegrasi, dan Transparan.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <div class="small text-white-50">
                        &copy; <?= date('Y') ?> <strong>Laborrow</strong>. Didesain dengan standar UI/UX profesional.
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?= render_flash() ?>
</body>
</html>
