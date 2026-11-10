<?php
require_once __DIR__ . '/../config/koneksi.php';
check_auth('admin');

// 1. STATISTIK INVENTARIS & USER
$totalTools      = (int)$pdo->query("SELECT COUNT(*) FROM tools")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalUsers      = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

// 2. STATISTIK RINCIAN PEMINJAMAN (MASING-MASING STATUS)
$stmtLoanStats = $pdo->query("SELECT 
    COUNT(*) AS total_pinjam,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS count_pending,
    SUM(CASE WHEN status = 'Disetujui' THEN 1 ELSE 0 END) AS count_siap,
    SUM(CASE WHEN status = 'Dipinjam' THEN 1 ELSE 0 END) AS count_aktif,
    SUM(CASE WHEN status = 'Dikembalikan' THEN 1 ELSE 0 END) AS count_selesai,
    SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) AS count_ditolak
    FROM loans");
$loanStats = $stmtLoanStats->fetch();

$totalLoans   = (int)($loanStats['total_pinjam'] ?? 0);
$countPending = (int)($loanStats['count_pending'] ?? 0);
$countSiap    = (int)($loanStats['count_siap'] ?? 0);
$countAktif   = (int)($loanStats['count_aktif'] ?? 0);
$countSelesai = (int)($loanStats['count_selesai'] ?? 0);
$countDitolak = (int)($loanStats['count_ditolak'] ?? 0);

// 3. STATISTIK KUANTITAS UNIT FISIK (BAIK / RUSAK / TOTAL)
$totalUnitFisik    = (int)$pdo->query("SELECT COALESCE(SUM(stok + stok_rusak), 0) FROM tools")->fetchColumn();
$totalUnitBaik     = (int)$pdo->query("SELECT COALESCE(SUM(stok), 0) FROM tools")->fetchColumn();
$totalUnitRusak    = (int)$pdo->query("SELECT COALESCE(SUM(stok_rusak), 0) FROM tools")->fetchColumn();
$totalLowStock     = (int)$pdo->query("SELECT COUNT(*) FROM tools WHERE stok < 3 AND stok > 0")->fetchColumn();
$totalOutStock     = (int)$pdo->query("SELECT COUNT(*) FROM tools WHERE stok = 0")->fetchColumn();
$stmtLowList       = $pdo->query("SELECT id, nama_alat, stok, stok_rusak, catatan_kerusakan FROM tools WHERE stok < 3 OR stok_rusak > 0 ORDER BY stok_rusak DESC, stok ASC LIMIT 5");
$lowStockList      = $stmtLowList->fetchAll();

// 4. PEMINJAMAN TERBARU (LIMIT 6)
$stmtRecent = $pdo->query("SELECT l.*, u.nama AS nama_siswa, u.email AS email_siswa, t.nama_alat 
                          FROM loans l 
                          JOIN users u ON l.user_id = u.id 
                          JOIN tools t ON l.tool_id = t.id 
                          ORDER BY l.id DESC LIMIT 6");
$recentLoans = $stmtRecent->fetchAll();

// 5. ALAT PALING SERING DIPINJAM (TOP BORROWED TOOLS)
$stmtTopTools = $pdo->query("SELECT t.id, t.nama_alat, t.stok, COUNT(l.id) AS total_dipinjam 
                             FROM tools t 
                             JOIN loans l ON t.id = l.tool_id 
                             GROUP BY t.id 
                             ORDER BY total_dipinjam DESC LIMIT 4");
$topTools = $stmtTopTools->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrator - Laborrow</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .metric-card-admin {
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
        .metric-card-admin:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-hover-shadow);
            border-color: rgba(128, 0, 32, 0.25);
        }
        .metric-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<main class="container-fluid px-lg-4 py-4">
    <!-- Header Welcome & Quick Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="fw-bold mb-1" style="color: var(--primary-maroon);">
                <i class="fa-solid fa-gauge-high me-2"></i>Dashboard Administrator
            </h2>
            <p class="text-muted mb-0 small">Ringkasan menyeluruh operasional inventaris, status permohonan pinjam, dan aktivitas laboratorium.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a href="kategori.php" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-tags me-1"></i> + Kategori (<?= $totalCategories ?>)
            </a>
            <a href="alat.php" class="btn btn-outline-maroon btn-sm">
                <i class="fa-solid fa-plus me-1"></i> + Tambah Alat
            </a>
            <a href="peminjaman.php" class="btn btn-maroon btn-sm">
                <i class="fa-solid fa-hand-holding-hand me-1"></i> Kelola Pinjaman (<?= $totalLoans ?>)
            </a>
        </div>
    </div>

    <!-- ACTIONABLE BANNER ALERTS (JIKA ADA PENDING ATAU STOK MENIPIS) -->
    <?php if ($countPending > 0): ?>
        <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 rounded-4 shadow-sm mb-4 border-warning-subtle" role="alert">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning text-dark p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                    <i class="fa-solid fa-bell fa-lg"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        Ada <?= $countPending ?> Permohonan Peminjaman Menunggu Persetujuan Anda!
                    </h6>
                    <small class="text-muted">Siswa membutuhkan konfirmasi persetujuan alat sebelum jadwal praktikum dimulai.</small>
                </div>
            </div>
            <a href="peminjaman.php?status=Pending" class="btn btn-warning btn-sm fw-bold px-3 text-dark rounded-pill">
                Periksa Sekarang <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    <?php endif; ?>

    <!-- BARIS 1: STATISTIK STATUS PEMINJAMAN (RINCIAN LENGKAP MASING-MASING STATUS) -->
    <div class="mb-2">
        <h6 class="text-uppercase fw-bold text-muted small" style="letter-spacing: 0.8px;">
            <i class="fa-solid fa-chart-pie me-1 text-danger"></i> Rekapitulasi Status Peminjaman
        </h6>
    </div>
    <div class="row g-3 mb-4">
        <!-- 1. Pending -->
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="metric-card-admin" style="border-left: 4px solid #eab308;">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Pending</span>
                    <h3 class="fw-bold mb-0 text-warning-emphasis"><?= $countPending ?></h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Perlu di-review</small>
                </div>
                <div class="metric-icon-box" style="background-color: #fefce8; color: #ca8a04;">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
        </div>

        <!-- 2. Siap Diambil di Lab (Disetujui) -->
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="metric-card-admin" style="border-left: 4px solid #16a34a;">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Siap Diambil</span>
                    <h3 class="fw-bold mb-0 text-success"><?= $countSiap ?></h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Menunggu siswa</small>
                </div>
                <div class="metric-icon-box" style="background-color: #f0fdf4; color: #16a34a;">
                    <i class="fa-solid fa-box-open"></i>
                </div>
            </div>
        </div>

        <!-- 3. Aktif di Tangan Siswa (Dipinjam) -->
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="metric-card-admin" style="border-left: 4px solid #6d28d9;">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Sedang Dipinjam</span>
                    <h3 class="fw-bold mb-0" style="color: #6d28d9;"><?= $countAktif ?></h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Di tangan siswa</small>
                </div>
                <div class="metric-icon-box" style="background-color: #f5f3ff; color: #6d28d9;">
                    <i class="fa-solid fa-hand-holding"></i>
                </div>
            </div>
        </div>

        <!-- 4. Tuntas Dikembalikan -->
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="metric-card-admin" style="border-left: 4px solid #2563eb;">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Dikembalikan</span>
                    <h3 class="fw-bold mb-0 text-primary"><?= $countSelesai ?></h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Selesai tuntas</small>
                </div>
                <div class="metric-icon-box" style="background-color: #eff6ff; color: #2563eb;">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
            </div>
        </div>

        <!-- 5. Ditolak -->
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="metric-card-admin" style="border-left: 4px solid #dc2626;">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Ditolak</span>
                    <h3 class="fw-bold mb-0 text-danger"><?= $countDitolak ?></h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Tidak disetujui</small>
                </div>
                <div class="metric-icon-box" style="background-color: #fef2f2; color: #dc2626;">
                    <i class="fa-solid fa-ban"></i>
                </div>
            </div>
        </div>

        <!-- 6. Total Semua Transaksi -->
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="metric-card-admin" style="border-left: 4px solid var(--primary-maroon);">
                <div>
                    <span class="text-muted small fw-semibold d-block mb-1">Total Peminjaman</span>
                    <h3 class="fw-bold mb-0" style="color: var(--primary-maroon);"><?= $totalLoans ?></h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Seluruh permohonan</small>
                </div>
                <div class="metric-icon-box" style="background-color: #FFF0F0; color: var(--primary-maroon);">
                    <i class="fa-solid fa-list-check"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- BARIS 2: STATISTIK FISIK INVENTARIS LAB & USER -->
    <div class="row g-3 mb-4">
        <!-- Total Inventaris Alat -->
        <div class="col-sm-6 col-xl">
            <div class="stat-card" style="background: linear-gradient(135deg, #800020 0%, #5a0016 100%);">
                <i class="fa-solid fa-microscope stat-icon"></i>
                <div class="small text-uppercase fw-semibold mb-1" style="color: var(--soft-pink);">Total Fisik Unit</div>
                <h2 class="display-6 fw-bold mb-1"><?= $totalUnitFisik ?></h2>
                <div class="small opacity-75"><?= $totalTools ?> jenis di <?= $totalCategories ?> kategori</div>
            </div>
        </div>

        <!-- Total Unit Kondisi Baik -->
        <div class="col-sm-6 col-xl">
            <div class="stat-card" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);">
                <i class="fa-solid fa-circle-check stat-icon"></i>
                <div class="small text-uppercase fw-semibold mb-1 text-light">Unit Siap Pakai</div>
                <h2 class="display-6 fw-bold mb-1"><?= $totalUnitBaik ?></h2>
                <div class="small opacity-75">Kondisi baik &amp; bisa dipinjam</div>
            </div>
        </div>

        <!-- Total Unit Rusak / Perbaikan -->
        <div class="col-sm-6 col-xl">
            <div class="stat-card" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);">
                <i class="fa-solid fa-screwdriver-wrench stat-icon"></i>
                <div class="small text-uppercase fw-semibold mb-1 text-white">Unit Rusak / Servis</div>
                <h2 class="display-6 fw-bold mb-1 text-white"><?= $totalUnitRusak ?></h2>
                <div class="small opacity-75">Sedang ditarik / perbaikan</div>
            </div>
        </div>

        <!-- Siswa Terdaftar -->
        <div class="col-sm-6 col-xl">
            <div class="stat-card" style="background: linear-gradient(135deg, #0d6efd 0%, #0a4ebd 100%);">
                <i class="fa-solid fa-user-graduate stat-icon"></i>
                <div class="small text-uppercase fw-semibold mb-1 text-light">Siswa Praktikan</div>
                <h2 class="display-6 fw-bold mb-1"><?= $totalUsers ?></h2>
                <div class="small opacity-75">Siswa terdaftar aktif</div>
            </div>
        </div>

        <!-- Stok Menipis / Kritis -->
        <div class="col-sm-6 col-xl">
            <div class="stat-card" style="background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%); color: #fff;">
                <i class="fa-solid fa-triangle-exclamation stat-icon" style="color: #fff;"></i>
                <div class="small text-uppercase fw-semibold mb-1 text-white">Stok Kritis</div>
                <h2 class="display-6 fw-bold mb-1 text-white"><?= $totalLowStock ?></h2>
                <div class="small opacity-75">&lt; 3 unit siap pakai</div>
            </div>
        </div>
    </div>

    <!-- ROW 3: TABEL AKTIVITAS TERBARU & WIDGET SAMPING -->
    <div class="row g-4">
        <!-- Kolom Kiri: Tabel Peminjaman Terbaru -->
        <div class="col-lg-8">
            <div class="card card-laborrow h-100">
                <div class="card-header-maroon d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i> Aktivitas Peminjaman Terbaru</span>
                    <a href="peminjaman.php" class="btn btn-sm btn-light py-1 px-3 fw-semibold text-maroon rounded-pill" style="font-size: 0.78rem;">
                        Kelola Semua <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentLoans)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fa-3x mb-3 opacity-25"></i>
                            <h6>Belum ada data peminjaman</h6>
                            <p class="small text-muted mb-0">Permohonan peminjaman dari siswa akan tampil otomatis di sini.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th class="ps-3">Siswa</th>
                                        <th>Alat Laboratorium</th>
                                        <th>Jadwal Pinjam</th>
                                        <th>Status</th>
                                        <th class="text-end pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLoans as $loan): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($loan['nama_siswa']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($loan['email_siswa']) ?></small>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($loan['nama_alat']) ?></span>
                                            </td>
                                            <td>
                                                <small class="d-block"><?= date('d/m/Y', strtotime($loan['tanggal_pinjam'])) ?> &rarr; <?= date('d/m/Y', strtotime($loan['tanggal_kembali'])) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($loan['status'] === 'Pending'): ?>
                                                    <span class="badge-status badge-pending" style="font-size: 0.74rem;">Pending</span>
                                                <?php elseif ($loan['status'] === 'Disetujui'): ?>
                                                    <span class="badge-status badge-disetujui" style="font-size: 0.74rem;">Siap Diambil</span>
                                                <?php elseif ($loan['status'] === 'Dipinjam'): ?>
                                                    <span class="badge-status badge-dipinjam" style="font-size: 0.74rem;">Di Tangan Siswa</span>
                                                <?php elseif ($loan['status'] === 'Ditolak'): ?>
                                                    <span class="badge-status badge-ditolak" style="font-size: 0.74rem;">Ditolak</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-dikembalikan" style="font-size: 0.74rem;">Dikembalikan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <a href="peminjaman.php" class="btn btn-sm btn-outline-maroon py-1 px-2" style="font-size: 0.78rem;">
                                                    Tinjau
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Peringatan Stok & Alat Terpopuler -->
        <div class="col-lg-4">
            <!-- Widget 1: Peringatan Stok Kritis -->
            <div class="card card-laborrow mb-4">
                <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-triangle-exclamation me-2"></i> Peringatan Stok Kritis</span>
                    <span class="badge bg-white text-danger"><?= count($lowStockList) ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($lowStockList)): ?>
                        <div class="text-center py-3 text-muted">
                            <i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i>
                            <div class="small fw-semibold">Semua stok alat dalam kondisi aman (&ge; 3 unit) atau belum ada alat.</div>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush mb-3">
                            <?php foreach ($lowStockList as $low): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-truncate fw-semibold small" style="max-width: 200px;"><?= htmlspecialchars($low['nama_alat']) ?></span>
                                    <?php if ($low['stok'] == 0): ?>
                                        <span class="badge bg-danger rounded-pill">Habis (0)</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark rounded-pill">Sisa <?= $low['stok'] ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="alat.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill">
                            <i class="fa-solid fa-boxes-stacked me-1"></i> Perbarui Stok di Inventaris
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Widget 2: Alat Paling Sering Dipinjam -->
            <div class="card card-laborrow">
                <div class="card-header-soft fw-bold" style="color: var(--primary-maroon);">
                    <i class="fa-solid fa-fire text-danger me-2"></i> Alat Paling Sering Dipinjam
                </div>
                <div class="card-body">
                    <?php if (empty($topTools)): ?>
                        <div class="text-center py-3 text-muted small">
                            Belum ada riwayat alat yang dipinjam.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($topTools as $tt): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-truncate fw-semibold small text-dark" style="max-width: 210px;">
                                        <?= htmlspecialchars($tt['nama_alat']) ?>
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        <?= $tt['total_dipinjam'] ?>x Dipinjam
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
