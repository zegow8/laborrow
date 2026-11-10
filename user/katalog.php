<?php
require_once __DIR__ . '/../config/koneksi.php';
check_auth('user');

// Ambil list kategori untuk dropdown filter
$stmtCat = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC");
$categories = $stmtCat->fetchAll();

// Parameter Pencarian & Filter
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['kategori'] ?? '');
$ketersediaan = trim($_GET['ketersediaan'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 8;
$offset   = ($page - 1) * $limit;

// Bangun query dinamis
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "t.nama_alat LIKE ?";
    $params[] = "%{$search}%";
}

if (!empty($category)) {
    $where[] = "t.category_id = ?";
    $params[] = $category;
}

if ($ketersediaan === 'tersedia') {
    $where[] = "t.stok > 0";
} elseif ($ketersediaan === 'habis') {
    $where[] = "t.stok = 0";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Hitung total data
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM tools t {$whereClause}");
$stmtCount->execute($params);
$totalRows = (int)$stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Query data paginasi
$sql = "SELECT t.*, c.nama_kategori 
        FROM tools t 
        LEFT JOIN categories c ON t.category_id = c.id 
        {$whereClause} 
        ORDER BY t.id DESC 
        LIMIT {$limit} OFFSET {$offset}";

$stmtTools = $pdo->prepare($sql);
$stmtTools->execute($params);
$tools = $stmtTools->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Alat Laboratorium - Laborrow</title>
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
    <!-- Header Page -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="fw-bold mb-1" style="color: var(--primary-maroon);">Katalog Alat Laboratorium</h2>
            <p class="text-muted mb-0 small">Temukan peralatan laboratorium untuk praktikum Anda. Cek kuantitas unit kondisi baik yang siap dipinjam.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="fa-solid fa-boxes-stacked me-1 text-danger"></i> Total: <strong><?= $totalRows ?></strong> Alat
            </span>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card card-laborrow p-3 mb-4">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Cari nama alat lab..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-filter"></i></span>
                    <select name="kategori" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($category == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="input-group">
                    <select name="ketersediaan" class="form-select">
                        <option value="">Semua Ketersediaan</option>
                        <option value="tersedia" <?= ($ketersediaan === 'tersedia') ? 'selected' : '' ?>>Siap Pinjam (&gt; 0)</option>
                        <option value="habis" <?= ($ketersediaan === 'habis') ? 'selected' : '' ?>>Stok Habis (0)</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-maroon flex-fill">
                    <i class="fa-solid fa-sliders me-1"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($category) || !empty($ketersediaan)): ?>
                    <a href="katalog.php" class="btn btn-outline-secondary" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Grid Alat -->
    <?php if (empty($tools)): ?>
        <div class="empty-state">
            <div class="empty-state-icon-wrapper">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">
                <?= (!empty($search) || !empty($category) || !empty($ketersediaan)) ? 'Alat Tidak Ditemukan' : 'Belum Ada Alat yang Ditambahkan' ?>
            </h4>
            <p class="text-muted col-md-6 mx-auto mb-4">
                <?= (!empty($search) || !empty($category) || !empty($ketersediaan)) 
                    ? 'Tidak ada alat laboratorium yang cocok dengan filter pencarian yang dipilih. Silakan coba kata kunci lain.' 
                    : 'Inventaris laboratorium saat ini masih kosong. Data alat akan muncul setelah Administrator menambahkan alat baru.' ?>
            </p>
            <?php if (!empty($search) || !empty($category) || !empty($ketersediaan)): ?>
                <a href="katalog.php" class="btn btn-outline-maroon">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset Pencarian
                </a>
            <?php else: ?>
                <a href="chat.php" class="btn btn-maroon">
                    <i class="fa-solid fa-comments me-2"></i> Tanyakan ke Admin Lab
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <?php foreach ($tools as $tool): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-laborrow h-100 d-flex flex-column">
                        <div class="position-relative">
                            <?php if (!empty($tool['foto']) && file_exists(__DIR__ . '/../assets/img/' . $tool['foto'])): ?>
                                <img src="../assets/img/<?= htmlspecialchars($tool['foto']) ?>" class="card-img-top" alt="<?= htmlspecialchars($tool['nama_alat']) ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 200px;">
                                    <i class="fa-solid fa-flask fa-3x" style="color: var(--soft-pink);"></i>
                                </div>
                            <?php endif; ?>
                            
                            <span class="position-absolute top-0 end-0 m-2 badge bg-dark bg-opacity-75">
                                <?= htmlspecialchars($tool['nama_kategori'] ?? 'Umum') ?>
                            </span>
                        </div>

                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="card-title fw-bold text-truncate mb-2" title="<?= htmlspecialchars($tool['nama_alat']) ?>">
                                    <?= htmlspecialchars($tool['nama_alat']) ?>
                                </h5>

                                <p class="card-text text-muted small" style="min-height: 40px;">
                                    <?= htmlspecialchars(mb_strimwidth($tool['deskripsi'] ?? 'Tidak ada deskripsi rinci untuk alat ini.', 0, 70, '...')) ?>
                                </p>
                            </div>

                            <div class="mt-3 pt-3 border-top">
                                <!-- STATUS KETERSEDIAAN UNIT BAIK VS RUSAK -->
                                <div class="mb-3">
                                    <?php if ($tool['stok'] > 0): ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small text-muted">Ketersediaan:</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">
                                                <i class="fa-solid fa-circle-check me-1"></i> Tersedia <?= $tool['stok'] ?> Unit
                                            </span>
                                        </div>
                                        <?php if ($tool['stok_rusak'] > 0): ?>
                                            <div class="text-end mt-1">
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;" title="<?= htmlspecialchars($tool['catatan_kerusakan'] ?? '') ?>">
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $tool['stok_rusak'] ?> Unit Dalam Servis
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small text-muted">Ketersediaan:</span>
                                            <?php if ($tool['stok_rusak'] > 0): ?>
                                                <span class="badge bg-danger">
                                                    Semua Unit Rusak (<?= $tool['stok_rusak'] ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    Stok Habis (0)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2">
                                    <!-- LOGIKA ADIL: SELAMA ADA UNIT BAIK (> 0), SISWA BISA PINJAM -->
                                    <?php if ($tool['stok'] > 0): ?>
                                        <a href="ajukan_pinjam.php?id=<?= $tool['id'] ?>" class="btn btn-maroon btn-sm">
                                            <i class="fa-solid fa-hand-holding-hand me-1"></i> Pinjam Alat
                                        </a>
                                    <?php elseif ($tool['stok_rusak'] > 0): ?>
                                        <button class="btn btn-secondary btn-sm" disabled title="Semua unit alat ini sedang dalam masa perbaikan">
                                            <i class="fa-solid fa-wrench me-1"></i> Dalam Perbaikan
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled>
                                            <i class="fa-solid fa-ban me-1"></i> Stok Habis
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($category) ?>&ketersediaan=<?= urlencode($ketersediaan) ?>">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link <?= ($page == $i) ? 'bg-maroon border-maroon' : '' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($category) ?>&ketersediaan=<?= urlencode($ketersediaan) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($category) ?>&ketersediaan=<?= urlencode($ketersediaan) ?>">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
