<?php
require_once __DIR__ . '/../config/koneksi.php';
check_auth('user');

$user_id = (int)$_SESSION['user_id'];
$today = date('Y-m-d');

// 1. STATISTIK PERSONAL SISWA
$stmtStat = $pdo->prepare("SELECT 
    COUNT(*) AS total_pinjam,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS count_pending,
    SUM(CASE WHEN status = 'Disetujui' THEN 1 ELSE 0 END) AS count_siap_ambil,
    SUM(CASE WHEN status = 'Dipinjam' THEN 1 ELSE 0 END) AS count_aktif_dipinjam,
    SUM(CASE WHEN status = 'Dikembalikan' THEN 1 ELSE 0 END) AS count_dikembalikan,
    SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) AS count_ditolak
    FROM loans WHERE user_id = ?");
$stmtStat->execute([$user_id]);
$stats = $stmtStat->fetch();

$totalPinjam   = (int)($stats['total_pinjam'] ?? 0);
$countPending  = (int)($stats['count_pending'] ?? 0);
$countSiap     = (int)($stats['count_siap_ambil'] ?? 0);
$countDipinjam = (int)($stats['count_aktif_dipinjam'] ?? 0);
$countSelesai  = (int)($stats['count_dikembalikan'] ?? 0);
$countDitolak  = (int)($stats['count_ditolak'] ?? 0);

// 2. CEK ALERT KRITIS (ALAT HARUS SEGERA DIAMBIL ATAU HAMPIR/LEWAT DEADLINE)
// a. Alat Siap Diambil
$stmtReady = $pdo->prepare("SELECT l.*, t.nama_alat 
                            FROM loans l 
                            JOIN tools t ON l.tool_id = t.id 
                            WHERE l.user_id = ? AND l.status = 'Disetujui' 
                            ORDER BY l.id DESC");
$stmtReady->execute([$user_id]);
$readyLoans = $stmtReady->fetchAll();

// b. Alat yang Sedang di Tangan Siswa (Dipinjam)
$stmtActive = $pdo->prepare("SELECT l.*, t.nama_alat, t.foto, c.nama_kategori 
                             FROM loans l 
                             JOIN tools t ON l.tool_id = t.id 
                             LEFT JOIN categories c ON t.category_id = c.id 
                             WHERE l.user_id = ? AND l.status = 'Dipinjam' 
                             ORDER BY l.tanggal_kembali ASC");
$stmtActive->execute([$user_id]);
$activeLoans = $stmtActive->fetchAll();

// c. Alat yang Masih Pending
$stmtPending = $pdo->prepare("SELECT l.*, t.nama_alat, t.foto, c.nama_kategori 
                              FROM loans l 
                              JOIN tools t ON l.tool_id = t.id 
                              LEFT JOIN categories c ON t.category_id = c.id 
                              WHERE l.user_id = ? AND l.status = 'Pending' 
                              ORDER BY l.id DESC");
$stmtPending->execute([$user_id]);
$pendingLoans = $stmtPending->fetchAll();

// 3. AKTIVITAS TERBARU SISWA (SEMUA STATUS, LIMIT 5)
$stmtRecent = $pdo->prepare("SELECT l.*, t.nama_alat, t.foto, c.nama_kategori 
                             FROM loans l 
                             JOIN tools t ON l.tool_id = t.id 
                             LEFT JOIN categories c ON t.category_id = c.id 
                             WHERE l.user_id = ? 
                             ORDER BY l.id DESC LIMIT 5");
$stmtRecent->execute([$user_id]);
$recentActivities = $stmtRecent->fetchAll();

// 4. REKOMENDASI ALAT LAB YANG TERSEDIA (MEMILIKI UNIT BAIK)
$stmtAvailableTools = $pdo->query("SELECT t.*, c.nama_kategori 
                                   FROM tools t 
                                   LEFT JOIN categories c ON t.category_id = c.id 
                                   WHERE t.stok > 0 
                                   ORDER BY t.id DESC LIMIT 4");
$availableTools = $stmtAvailableTools->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - Laborrow</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .welcome-card-user {
            background: radial-gradient(circle at 90% 10%, rgba(255, 212, 212, 0.22) 0%, transparent 50%),
                        linear-gradient(135deg, #800020 0%, #4d0013 100%);
            border-radius: 20px;
            color: #ffffff;
            padding: 2.2rem 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 14px 35px rgba(128, 0, 32, 0.2);
            margin-bottom: 2rem;
        }
        .user-stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 1.25rem 1.4rem;
            border: 1px solid var(--soft-pink-border);
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
        }
        .user-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
            border-color: rgba(128, 0, 32, 0.25);
        }
        .user-stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<main class="container py-4">

    <!-- WELCOME BANNER USER -->
    <div class="welcome-card-user">
        <div class="row align-items-center g-4">
            <div class="col-lg-8 text-center text-lg-start">
                <span class="badge px-3 py-1 rounded-pill mb-2" style="background: rgba(255, 212, 212, 0.2); color: var(--soft-pink); font-size: 0.82rem;">
                    <i class="fa-solid fa-graduation-cap me-1"></i> Area Praktikan Laboratorium
                </span>
                <h2 class="fw-bold mb-2 text-white">
                    Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'Siswa') ?>! 👋
                </h2>
                <p class="text-white-50 mb-4 mb-lg-0" style="max-width: 600px; font-size: 0.98rem; line-height: 1.6;">
                    Selamat datang di beranda personal Anda. Pantau status peminjaman alat praktikum, konfirmasi pengambilan barang di lab, dan pastikan pengembalian tepat waktu.
                </p>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-flex flex-column flex-sm-row flex-lg-column gap-2 justify-content-lg-end">
                    <a href="katalog.php" class="btn btn-light text-maroon fw-bold rounded-pill shadow px-4 py-2">
                        <i class="fa-solid fa-plus-circle me-1"></i> Pinjam Alat Baru
                    </a>
                    <a href="riwayat.php" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> Cek Semua Riwayat
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ACTIONABLE ALERTS UNTUK SISWA -->
    <?php if (!empty($readyLoans)): ?>
        <!-- Peringatan: Ada alat yang disetujui dan siap diambil -->
        <div class="alert alert-success d-flex align-items-center justify-content-between p-3 rounded-4 shadow-sm mb-4 border-success-subtle" role="alert">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fa-solid fa-box-open fa-lg"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-success">
                        Ada <?= count($readyLoans) ?> Permohonan Disetujui & Siap Diambil di Lab!
                    </h6>
                    <div class="small text-muted">
                        Silakan ambil alat fisik di ruang lab dan lakukan konfirmasi pengambilan di halaman riwayat.
                    </div>
                </div>
            </div>
            <a href="riwayat.php?status=Disetujui" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                Ambil Sekarang <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    <?php endif; ?>

    <!-- STATISTIK RINGKAS SISWA (5 KARTU INDIKATOR) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Seluruh Peminjaman -->
        <div class="col-sm-6 col-lg-4 col-xl-2-4" style="flex: 1 0 180px;">
            <div class="user-stat-card">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Total Pengajuan</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $totalPinjam ?></h3>
                </div>
                <div class="user-stat-icon" style="background-color: #FFF0F0; color: var(--primary-maroon);">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
            </div>
        </div>

        <!-- 2. Sedang Dipinjam (Di Tangan Anda) -->
        <div class="col-sm-6 col-lg-4 col-xl-2-4" style="flex: 1 0 180px;">
            <div class="user-stat-card" style="border-left: 4px solid #6d28d9;">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Di Tangan Anda</span>
                    <h3 class="fw-bold mb-0" style="color: #6d28d9;"><?= $countDipinjam ?></h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Aktif Digunakan</small>
                </div>
                <div class="user-stat-icon" style="background-color: #f5f3ff; color: #6d28d9;">
                    <i class="fa-solid fa-hand-holding"></i>
                </div>
            </div>
        </div>

        <!-- 3. Siap Diambil di Lab -->
        <div class="col-sm-6 col-lg-4 col-xl-2-4" style="flex: 1 0 180px;">
            <div class="user-stat-card" style="border-left: 4px solid #16a34a;">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Siap Diambil</span>
                    <h3 class="fw-bold mb-0 text-success"><?= $countSiap ?></h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Di-acc Laboran</small>
                </div>
                <div class="user-stat-icon" style="background-color: #f0fdf4; color: #16a34a;">
                    <i class="fa-solid fa-check-to-slot"></i>
                </div>
            </div>
        </div>

        <!-- 4. Menunggu Konfirmasi (Pending) -->
        <div class="col-sm-6 col-lg-4 col-xl-2-4" style="flex: 1 0 180px;">
            <div class="user-stat-card" style="border-left: 4px solid #eab308;">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Pending Approval</span>
                    <h3 class="fw-bold mb-0 text-warning-emphasis"><?= $countPending ?></h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Antrean Review</small>
                </div>
                <div class="user-stat-icon" style="background-color: #fefce8; color: #ca8a04;">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>
        </div>

        <!-- 5. Tuntas Dikembalikan -->
        <div class="col-sm-6 col-lg-4 col-xl-2-4" style="flex: 1 0 180px;">
            <div class="user-stat-card" style="border-left: 4px solid #2563eb;">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Dikembalikan</span>
                    <h3 class="fw-bold mb-0 text-primary"><?= $countSelesai ?></h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Tuntas Selesai</small>
                </div>
                <div class="user-stat-icon" style="background-color: #eff6ff; color: #2563eb;">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- KOLOM KIRI: ALAT AKTIF DI TANGAN & PENDING -->
        <div class="col-lg-7">
            <!-- Peminjaman Aktif (Di Tangan Anda) -->
            <div class="card card-laborrow mb-4">
                <div class="card-header-maroon d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="fa-solid fa-hand-holding me-2"></i> Alat Sedang di Tangan Anda (Aktif)</span>
                    <span class="badge bg-light text-dark"><?= count($activeLoans) ?> Alat</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($activeLoans)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fa-solid fa-clipboard-check fa-2x mb-2 text-muted opacity-25"></i>
                            <div class="small fw-semibold">Tidak ada alat laboratorium yang sedang Anda bawa saat ini.</div>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($activeLoans as $al): ?>
                                <div class="list-group-item p-3 d-flex align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($al['foto']) && file_exists(__DIR__ . '/../assets/img/' . $al['foto'])): ?>
                                            <img src="../assets/img/<?= htmlspecialchars($al['foto']) ?>" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded bg-light d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px;">
                                                <i class="fa-solid fa-flask"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($al['nama_alat']) ?></h6>
                                            <small class="text-muted d-block" style="font-size: 0.76rem;">
                                                Diambil: <?= !empty($al['tanggal_diambil']) ? date('d/m/Y H:i', strtotime($al['tanggal_diambil'])) : '-' ?> WIB
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <?php if ($al['tanggal_kembali'] < $today): ?>
                                            <span class="badge bg-danger mb-1 d-block">Terlambat!</span>
                                            <small class="text-danger fw-bold" style="font-size: 0.75rem;"><?= date('d-m-Y', strtotime($al['tanggal_kembali'])) ?></small>
                                        <?php elseif ($al['tanggal_kembali'] === $today): ?>
                                            <span class="badge bg-warning text-dark mb-1 d-block">Batas Hari Ini!</span>
                                            <small class="text-danger fw-bold" style="font-size: 0.75rem;"><?= date('d-m-Y', strtotime($al['tanggal_kembali'])) ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger mb-1 d-block">Batas Pengembalian</span>
                                            <small class="text-muted fw-semibold" style="font-size: 0.75rem;"><?= date('d-m-Y', strtotime($al['tanggal_kembali'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Permohonan Menunggu Konfirmasi (Pending) -->
            <div class="card card-laborrow">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-3 border-bottom">
                    <span class="fw-bold text-dark"><i class="fa-solid fa-hourglass-start me-2 text-warning"></i> Permohonan Menunggu Konfirmasi</span>
                    <span class="badge bg-warning text-dark"><?= count($pendingLoans) ?> Pengajuan</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($pendingLoans)): ?>
                        <div class="p-3 text-center text-muted small">
                            Tidak ada permohonan peminjaman yang sedang berstatus Pending.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pendingLoans as $pl): ?>
                                <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($pl['nama_alat']) ?></div>
                                        <small class="text-muted">Rencana: <?= date('d/m/Y', strtotime($pl['tanggal_pinjam'])) ?> &rarr; <?= date('d/m/Y', strtotime($pl['tanggal_kembali'])) ?></small>
                                    </div>
                                    <span class="badge-status badge-pending">Menunggu Review</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: AKTIVITAS TERBARU & QUICK ACTION -->
        <div class="col-lg-5">
            <!-- Box Aktivitas Riwayat Terkini -->
            <div class="card card-laborrow mb-4">
                <div class="card-header-soft fw-bold d-flex justify-content-between align-items-center" style="color: var(--primary-maroon);">
                    <span><i class="fa-solid fa-clock-rotate-left me-2"></i> Aktivitas Peminjaman Terkini</span>
                    <a href="riwayat.php" class="btn btn-sm btn-outline-maroon py-0 px-2 fw-semibold" style="font-size: 0.76rem;">Lihat Semua</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentActivities)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fa-solid fa-inbox fa-2x mb-2 text-muted opacity-25"></i>
                            <div class="small">Belum ada riwayat aktivitas peminjaman.</div>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentActivities as $ra): ?>
                                <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                    <div class="overflow-hidden me-2">
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($ra['nama_alat']) ?>">
                                            <?= htmlspecialchars($ra['nama_alat']) ?>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.74rem;">Diajukan: <?= date('d/m/Y', strtotime($ra['created_at'])) ?></small>
                                    </div>
                                    <div>
                                        <?php if ($ra['status'] === 'Pending'): ?>
                                            <span class="badge-status badge-pending" style="font-size: 0.72rem;">Pending</span>
                                        <?php elseif ($ra['status'] === 'Disetujui'): ?>
                                            <span class="badge-status badge-disetujui" style="font-size: 0.72rem;">Disetujui</span>
                                        <?php elseif ($ra['status'] === 'Dipinjam'): ?>
                                            <span class="badge-status badge-dipinjam" style="font-size: 0.72rem;">Di Tangan Anda</span>
                                        <?php elseif ($ra['status'] === 'Ditolak'): ?>
                                            <span class="badge-status badge-ditolak" style="font-size: 0.72rem;">Ditolak</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-dikembalikan" style="font-size: 0.72rem;">Dikembalikan</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bantuan Cepat Siswa -->
            <div class="card card-laborrow">
                <div class="card-header bg-light fw-bold text-dark py-3">
                    <i class="fa-solid fa-circle-question me-2 text-danger"></i> Butuh Konsultasi Praktikum?
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Ada pertanyaan mengenai ketersediaan alat, petunjuk keselamatan laboratorium, atau penyesuaian jadwal praktikum?
                    </p>
                    <a href="chat.php" class="btn btn-maroon w-100 rounded-pill py-2">
                        <i class="fa-solid fa-headset me-2"></i> Hubungi Staf Laboran (Chat)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION REKOMENDASI ALAT LAB YANG BISA LANGSUNG DIPINJAM -->
    <div class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-microscope text-danger me-2"></i>Alat Laboratorium Siap Pinjam</h5>
                <small class="text-muted">Pilih peralatan yang tersedia untuk praktikum Anda selanjutnya.</small>
            </div>
            <a href="katalog.php" class="btn btn-outline-maroon btn-sm">
                Buka Katalog Lengkap <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if (empty($availableTools)): ?>
            <div class="empty-state py-4">
                <i class="fa-solid fa-box-open empty-state-icon" style="font-size: 2.2rem;"></i>
                <h6 class="fw-bold mt-2">Belum ada alat laboratorium yang ditambahkan</h6>
                <p class="text-muted small mb-0">Inventaris laboratorium saat ini masih kosong.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($availableTools as $at): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-laborrow h-100 p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-light text-dark border small"><?= htmlspecialchars($at['nama_kategori'] ?? 'Umum') ?></span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Stok: <?= $at['stok'] ?></span>
                                </div>
                                <h6 class="fw-bold text-dark text-truncate mb-1" title="<?= htmlspecialchars($at['nama_alat']) ?>">
                                    <?= htmlspecialchars($at['nama_alat']) ?>
                                </h6>
                                <p class="text-muted small mb-3 line-clamp-2" style="font-size: 0.8rem;">
                                    <?= htmlspecialchars(mb_strimwidth($at['deskripsi'] ?? 'Tidak ada deskripsi rinci', 0, 60, '...')) ?>
                                </p>
                            </div>
                            <a href="ajukan_pinjam.php?id=<?= $at['id'] ?>" class="btn btn-sm btn-maroon w-100 rounded-pill">
                                <i class="fa-solid fa-hand-holding-hand me-1"></i> Pinjam Alat Ini
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</main>

<?php include __DIR__ . '/footer.php'; ?>
