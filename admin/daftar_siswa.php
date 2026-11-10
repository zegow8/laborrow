<?php
require_once __DIR__ . '/../config/koneksi.php';
check_auth('admin');

$search = trim($_GET['search'] ?? '');
$params = [];
$whereClause = "WHERE u.role = 'user'";

if (!empty($search)) {
    $whereClause .= " AND (u.nama LIKE ? OR u.email LIKE ?)";
    $params = ["%{$search}%", "%{$search}%"];
}

// Ambil data siswa beserta jumlah peminjaman mereka
$sql = "SELECT u.*, 
        COUNT(l.id) AS total_pinjam,
        SUM(CASE WHEN l.status = 'Pending' THEN 1 ELSE 0 END) AS total_pending,
        SUM(CASE WHEN l.status = 'Disetujui' OR l.status = 'Dipinjam' THEN 1 ELSE 0 END) AS total_aktif
        FROM users u 
        LEFT JOIN loans l ON u.id = l.user_id 
        {$whereClause} 
        GROUP BY u.id 
        ORDER BY u.nama ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa - Laborrow</title>
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
    <!-- Header Page -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="fw-bold mb-1" style="color: var(--primary-maroon);">
                <i class="fa-solid fa-users me-2"></i>Data Siswa Praktikan
            </h2>
            <p class="text-muted mb-0 small">Daftar siswa yang terdaftar dalam sistem Laborrow beserta statistik aktivitas peminjaman.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="fa-solid fa-graduation-cap text-primary me-1"></i> Total: <strong><?= count($students) ?></strong> Siswa
            </span>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="card card-laborrow p-3 mb-4">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Cari nama atau email siswa..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-maroon">Cari Siswa</button>
                <?php if (!empty($search)): ?>
                    <a href="daftar_siswa.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabel Siswa -->
    <div class="card card-laborrow">
        <div class="card-body p-0">
            <?php if (empty($students)): ?>
                <div class="empty-state border-0 my-0 py-5">
                    <i class="fa-solid fa-user-xmark empty-state-icon"></i>
                    <h5 class="fw-bold">Tidak Ada Data Siswa</h5>
                    <p class="text-muted small col-md-5 mx-auto mb-0">
                        <?= !empty($search) ? 'Tidak ditemukan siswa dengan kata kunci tersebut.' : 'Belum ada siswa yang mendaftar di sistem Laborrow.' ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="ps-4">No</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Bergabung Sejak</th>
                                <th>Status Peminjaman</th>
                                <th class="text-end pe-4">Aksi Komunikasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($students as $s): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-muted"><?= $no++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="chat-user-avatar" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                                <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($s['nama']) ?></div>
                                                <small class="text-muted">ID: #<?= $s['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?= htmlspecialchars($s['email']) ?></span>
                                    </td>
                                    <td>
                                        <small><?= format_tanggal_indo($s['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <span class="badge bg-light text-dark border" title="Total Peminjaman">
                                                Total: <?= $s['total_pinjam'] ?>
                                            </span>
                                            <?php if ($s['total_pending'] > 0): ?>
                                                <span class="badge bg-warning text-dark" title="Peminjaman Pending">
                                                    Pending: <?= $s['total_pending'] ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($s['total_aktif'] > 0): ?>
                                                <span class="badge bg-success" title="Sedang Dipinjam">
                                                    Aktif: <?= $s['total_aktif'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="chat.php?user_id=<?= $s['id'] ?>" class="btn btn-sm btn-maroon">
                                            <i class="fa-solid fa-comments me-1"></i> Buka Chat
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
</main>

<?php include __DIR__ . '/footer.php'; ?>
