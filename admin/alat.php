<?php
require_once __DIR__ . '/../config/koneksi.php';
check_auth('admin');

$upload_dir = __DIR__ . '/../assets/img/';

// Ambil list kategori untuk dropdown
$stmtCat = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC");
$categories = $stmtCat->fetchAll();

// 1. TAMBAH ALAT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama_alat         = trim($_POST['nama_alat'] ?? '');
    $category_id       = (int)($_POST['category_id'] ?? 0);
    $stok_baik         = max(0, (int)($_POST['stok'] ?? 0));
    $stok_rusak        = max(0, (int)($_POST['stok_rusak'] ?? 0));
    $catatan_kerusakan = trim($_POST['catatan_kerusakan'] ?? '');
    $deskripsi         = trim($_POST['deskripsi'] ?? '');

    if (empty($nama_alat) || $category_id <= 0) {
        set_flash('warning', 'Data Kurang', 'Nama alat dan kategori wajib diisi.');
    } else {
        $foto_name = null;

        // Cek Upload Foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['foto']['tmp_name'];
            $fileSize = $_FILES['foto']['size'];
            $fileName = $_FILES['foto']['name'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($fileExt, $allowedExts)) {
                set_flash('error', 'Format Tidak Didukung', 'Format gambar harus JPG, JPEG, PNG, atau WEBP.');
                header("Location: " . base_url('admin/alat.php'));
                exit;
            } elseif ($fileSize > $maxSize) {
                set_flash('error', 'Ukuran Terlalu Besar', 'Ukuran gambar maksimal 2MB.');
                header("Location: " . base_url('admin/alat.php'));
                exit;
            } else {
                $foto_name = 'tool_' . time() . '_' . uniqid() . '.' . $fileExt;
                if (!move_uploaded_file($fileTmp, $upload_dir . $foto_name)) {
                    $foto_name = null;
                }
            }
        }

        $stmtInsert = $pdo->prepare("INSERT INTO tools (category_id, nama_alat, stok, stok_rusak, catatan_kerusakan, foto, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmtInsert->execute([$category_id, $nama_alat, $stok_baik, $stok_rusak, $catatan_kerusakan, $foto_name, $deskripsi])) {
            set_flash('success', 'Berhasil', 'Alat laboratorium baru berhasil ditambahkan.');
        } else {
            set_flash('error', 'Gagal', 'Terjadi kesalahan sistem saat menyimpan alat.');
        }
    }
    header("Location: " . base_url('admin/alat.php'));
    exit;
}

// 2. EDIT ALAT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id                = (int)($_POST['id'] ?? 0);
    $nama_alat         = trim($_POST['nama_alat'] ?? '');
    $category_id       = (int)($_POST['category_id'] ?? 0);
    $stok_baik         = max(0, (int)($_POST['stok'] ?? 0));
    $stok_rusak        = max(0, (int)($_POST['stok_rusak'] ?? 0));
    $catatan_kerusakan = trim($_POST['catatan_kerusakan'] ?? '');
    $deskripsi         = trim($_POST['deskripsi'] ?? '');

    if ($id <= 0 || empty($nama_alat) || $category_id <= 0) {
        set_flash('warning', 'Data Kurang', 'Harap lengkapi semua kolom wajib.');
    } else {
        $stmtOld = $pdo->prepare("SELECT foto FROM tools WHERE id = ?");
        $stmtOld->execute([$id]);
        $oldTool = $stmtOld->fetch();
        $foto_name = $oldTool['foto'] ?? null;

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['foto']['tmp_name'];
            $fileSize = $_FILES['foto']['size'];
            $fileName = $_FILES['foto']['name'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($fileExt, $allowedExts)) {
                set_flash('error', 'Format Tidak Didukung', 'Format gambar harus JPG, JPEG, PNG, atau WEBP.');
                header("Location: " . base_url('admin/alat.php'));
                exit;
            } elseif ($fileSize > $maxSize) {
                set_flash('error', 'Ukuran Terlalu Besar', 'Ukuran gambar maksimal 2MB.');
                header("Location: " . base_url('admin/alat.php'));
                exit;
            } else {
                if (!empty($foto_name) && file_exists($upload_dir . $foto_name)) {
                    @unlink($upload_dir . $foto_name);
                }

                $foto_name = 'tool_' . time() . '_' . uniqid() . '.' . $fileExt;
                move_uploaded_file($fileTmp, $upload_dir . $foto_name);
            }
        }

        $stmtUpdate = $pdo->prepare("UPDATE tools SET category_id = ?, nama_alat = ?, stok = ?, stok_rusak = ?, catatan_kerusakan = ?, foto = ?, deskripsi = ? WHERE id = ?");
        if ($stmtUpdate->execute([$category_id, $nama_alat, $stok_baik, $stok_rusak, $catatan_kerusakan, $foto_name, $deskripsi, $id])) {
            set_flash('success', 'Berhasil', 'Data inventaris alat berhasil diperbarui.');
        } else {
            set_flash('error', 'Gagal', 'Gagal memperbarui data alat.');
        }
    }
    header("Location: " . base_url('admin/alat.php'));
    exit;
}

// 3. AKSI CEPAT GESER UNIT (TANDAI 1 UNIT RUSAK ATAU 1 UNIT SELESAI SERVIS)
if (isset($_GET['action']) && in_array($_GET['action'], ['tandai_rusak', 'selesai_servis'])) {
    $id = (int)($_GET['id'] ?? 0);
    $action = $_GET['action'];

    if ($id > 0) {
        $stmtFind = $pdo->prepare("SELECT nama_alat, stok, stok_rusak FROM tools WHERE id = ?");
        $stmtFind->execute([$id]);
        $row = $stmtFind->fetch();

        if ($row) {
            if ($action === 'tandai_rusak') {
                if ($row['stok'] > 0) {
                    $stmtShift = $pdo->prepare("UPDATE tools SET stok = stok - 1, stok_rusak = stok_rusak + 1 WHERE id = ?");
                    $stmtShift->execute([$id]);
                    set_flash('warning', '1 Unit Ditandai Rusak', "1 unit '{$row['nama_alat']}' dialihkan ke status perbaikan. Sisa unit baik: " . ($row['stok'] - 1));
                } else {
                    set_flash('error', 'Gagal', 'Tidak ada unit berkondisi baik yang tersisa untuk ditandai rusak.');
                }
            } elseif ($action === 'selesai_servis') {
                if ($row['stok_rusak'] > 0) {
                    $stmtShift = $pdo->prepare("UPDATE tools SET stok = stok + 1, stok_rusak = stok_rusak - 1 WHERE id = ?");
                    $stmtShift->execute([$id]);
                    set_flash('success', '1 Unit Selesai Diservis', "1 unit '{$row['nama_alat']}' telah diperbaiki dan siap dipinjam kembali. Unit baik sekarang: " . ($row['stok'] + 1));
                } else {
                    set_flash('error', 'Gagal', 'Tidak ada unit rusak untuk alat ini.');
                }
            }
        }
    }
    header("Location: " . base_url('admin/alat.php'));
    exit;
}

// 4. HAPUS ALAT
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id > 0) {
        $stmtFind = $pdo->prepare("SELECT foto FROM tools WHERE id = ?");
        $stmtFind->execute([$id]);
        $row = $stmtFind->fetch();

        if ($row && !empty($row['foto']) && file_exists($upload_dir . $row['foto'])) {
            @unlink($upload_dir . $row['foto']);
        }

        $stmtDel = $pdo->prepare("DELETE FROM tools WHERE id = ?");
        if ($stmtDel->execute([$id])) {
            set_flash('success', 'Berhasil', 'Alat berhasil dihapus dari inventaris.');
        } else {
            set_flash('error', 'Gagal', 'Gagal menghapus alat.');
        }
    }
    header("Location: " . base_url('admin/alat.php'));
    exit;
}

// Query list alat dengan filter & pencarian
$search = trim($_GET['search'] ?? '');
$filterKondisi = trim($_GET['kondisi'] ?? '');

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(t.nama_alat LIKE ? OR c.nama_kategori LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($filterKondisi === 'semua_baik') {
    $where[] = "t.stok > 0";
} elseif ($filterKondisi === 'ada_rusak') {
    $where[] = "t.stok_rusak > 0";
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmtTools = $pdo->prepare("SELECT t.*, c.nama_kategori 
                           FROM tools t 
                           LEFT JOIN categories c ON t.category_id = c.id 
                           {$whereClause} 
                           ORDER BY t.id DESC");
$stmtTools->execute($params);
$tools = $stmtTools->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Inventaris Alat - Laborrow</title>
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
                <i class="fa-solid fa-microscope me-2"></i>Inventaris Alat Laboratorium
            </h2>
            <p class="text-muted mb-0 small">Kelola rincian stok unit baik (siap pinjam) dan unit rusak (dalam servis) secara proporsional.</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <button class="btn btn-maroon btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahAlat">
                <i class="fa-solid fa-plus me-1"></i> Tambah Alat Baru
            </button>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="card card-laborrow p-3 mb-4">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Cari nama alat / kategori..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-filter"></i></span>
                    <select name="kondisi" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status Inventaris</option>
                        <option value="semua_baik" <?= ($filterKondisi === 'semua_baik') ? 'selected' : '' ?>>Memiliki Unit Baik (&gt; 0)</option>
                        <option value="ada_rusak" <?= ($filterKondisi === 'ada_rusak') ? 'selected' : '' ?>>Memiliki Unit Rusak (&gt; 0)</option>
                    </select>
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-maroon">Terapkan</button>
                <?php if (!empty($search) || !empty($filterKondisi)): ?>
                    <a href="alat.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
            <div class="col-auto ms-auto">
                <span class="badge bg-light text-dark border p-2">Total: <strong><?= count($tools) ?></strong> Alat</span>
            </div>
        </form>
    </div>

    <!-- Tabel Alat -->
    <div class="card card-laborrow">
        <div class="card-body p-0">
            <?php if (empty($tools)): ?>
                <div class="empty-state border-0 my-0 py-5">
                    <i class="fa-solid fa-box-open empty-state-icon"></i>
                    <h5 class="fw-bold">
                        <?= (!empty($search) || !empty($filterKondisi)) ? 'Pencarian Tidak Ditemukan' : 'Belum Ada Data Alat' ?>
                    </h5>
                    <p class="text-muted small col-md-5 mx-auto mb-3">
                        <?= (!empty($search) || !empty($filterKondisi)) 
                            ? 'Tidak ada alat yang cocok dengan filter pencarian Anda.' 
                            : 'Inventaris laboratorium masih kosong. Tambahkan kategori dan alat pertama Anda.' ?>
                    </p>
                    <?php if (empty($categories)): ?>
                        <a href="kategori.php" class="btn btn-sm btn-outline-maroon">
                            <i class="fa-solid fa-tags me-1"></i> Buat Kategori Dahulu
                        </a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-maroon" data-bs-toggle="modal" data-bs-target="#modalTambahAlat">
                            <i class="fa-solid fa-plus me-1"></i> Tambah Alat Pertama
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="ps-4">No</th>
                                <th>Foto</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Status Kuantitas Unit (Baik vs Rusak)</th>
                                <th>Kelola Status Unit</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($tools as $t): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-muted"><?= $no++ ?></td>
                                    <td>
                                        <?php if (!empty($t['foto']) && file_exists($upload_dir . $t['foto'])): ?>
                                            <img src="../assets/img/<?= htmlspecialchars($t['foto']) ?>" alt="" class="rounded border" style="width: 55px; height: 55px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted" style="width: 55px; height: 55px;">
                                                <i class="fa-solid fa-flask"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($t['nama_alat']) ?></div>
                                        <small class="text-muted">Total Fisik: <strong><?= ($t['stok'] + $t['stok_rusak']) ?></strong> Unit</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($t['nama_kategori'] ?? 'Tanpa Kategori') ?></span>
                                    </td>

                                    <!-- KOLOM RINCIAN UNIT BAIK VS RUSAK -->
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <!-- Unit Baik -->
                                            <div>
                                                <?php if ($t['stok'] > 0): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                        <i class="fa-solid fa-circle-check me-1"></i> <strong><?= $t['stok'] ?> Unit Baik</strong> (Bisa Dipinjam)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                                        <i class="fa-solid fa-ban me-1"></i> 0 Unit Baik
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Unit Rusak -->
                                            <?php if ($t['stok_rusak'] > 0): ?>
                                                <div>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" title="<?= htmlspecialchars($t['catatan_kerusakan'] ?? '') ?>">
                                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> <strong><?= $t['stok_rusak'] ?> Unit Rusak / Servis</strong>
                                                    </span>
                                                    <?php if (!empty($t['catatan_kerusakan'])): ?>
                                                        <small class="text-danger d-block mt-1 fst-italic" style="font-size: 0.72rem;">
                                                            Ket: <?= htmlspecialchars(mb_strimwidth($t['catatan_kerusakan'], 0, 30, '...')) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- QUICK SHIFT UNIT (PINDAH STATUS BAIK <-> RUSAK) -->
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <!-- Pindahkan 1 unit ke rusak -->
                                            <button class="btn btn-outline-danger" 
                                                    <?= ($t['stok'] <= 0) ? 'disabled' : '' ?>
                                                    onclick="geserUnit(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nama_alat'])) ?>', 'tandai_rusak')"
                                                    title="Tandai 1 unit baik menjadi rusak">
                                                <i class="fa-solid fa-arrow-down me-1"></i> 1 Rusak
                                            </button>
                                            <!-- Selesai servis 1 unit -->
                                            <button class="btn btn-outline-success" 
                                                    <?= ($t['stok_rusak'] <= 0) ? 'disabled' : '' ?>
                                                    onclick="geserUnit(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nama_alat'])) ?>', 'selesai_servis')"
                                                    title="1 unit rusak selesai diservis dan kembali baik">
                                                <i class="fa-solid fa-arrow-up me-1"></i> 1 Sembuh
                                            </button>
                                        </div>
                                    </td>

                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-primary me-1" 
                                                onclick='editAlat(<?= json_encode($t) ?>)' 
                                                title="Ubah Rincian Alat">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="hapusAlat(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['nama_alat'])) ?>')" 
                                                title="Hapus Alat">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
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

<!-- Modal Tambah Alat -->
<div class="modal fade" id="modalTambahAlat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header card-header-maroon text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i> Tambah Alat Laboratorium Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Nama Alat <span class="text-danger">*</span></label>
                            <input type="text" name="nama_alat" class="form-control" placeholder="Contoh: Mikroskop Binokuler Olympus CX23" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Kategori Alat <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Kuantitas Stok Baik vs Rusak -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-success">
                                <i class="fa-solid fa-circle-check me-1"></i> Unit Kondisi Baik (Bisa Dipinjam) <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="stok" class="form-control border-success-subtle" min="0" value="1" required>
                            <small class="text-muted" style="font-size: 0.74rem;">Jumlah fisik unit yang normal dan siap dipakai praktikum.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-danger">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Unit Rusak / Maintenance
                            </label>
                            <input type="number" name="stok_rusak" class="form-control border-danger-subtle" min="0" value="0">
                            <small class="text-muted" style="font-size: 0.74rem;">Unit yang sedang rusak dan ditarik dari peredaran.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Catatan Kerusakan (Jika Ada Unit Rusak)</label>
                            <input type="text" name="catatan_kerusakan" class="form-control" placeholder="Contoh: 1 unit lensa buram, 1 unit kabel putus...">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Foto Alat (Maks. 2MB, JPG/PNG/WEBP)</label>
                            <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg, image/jpg, image/webp">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Deskripsi & Spesifikasi</label>
                            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Tuliskan spesifikasi, kelengkapan, dan petunjuk pemakaian alat..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-maroon">Simpan Alat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Alat -->
<div class="modal fade" id="modalEditAlat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header card-header-maroon text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i> Ubah Data Inventaris Alat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_alat_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Nama Alat <span class="text-danger">*</span></label>
                            <input type="text" name="nama_alat" id="edit_alat_nama" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Kategori Alat <span class="text-danger">*</span></label>
                            <select name="category_id" id="edit_alat_kategori" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Kuantitas Stok Baik vs Rusak -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-success">
                                <i class="fa-solid fa-circle-check me-1"></i> Unit Kondisi Baik (Bisa Dipinjam) <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="stok" id="edit_alat_stok" class="form-control border-success-subtle" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-danger">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Unit Rusak / Maintenance
                            </label>
                            <input type="number" name="stok_rusak" id="edit_alat_stok_rusak" class="form-control border-danger-subtle" min="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Catatan Kerusakan</label>
                            <input type="text" name="catatan_kerusakan" id="edit_alat_catatan" class="form-control" placeholder="Tuliskan kendala unit rusak...">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Ganti Foto (Kosongkan jika tetap)</label>
                            <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg, image/jpg, image/webp">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Deskripsi & Spesifikasi</label>
                            <textarea name="deskripsi" id="edit_alat_deskripsi" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-maroon">Perbarui Alat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<script>
    function geserUnit(id, namaAlat, tipe) {
        const isRusak = (tipe === 'tandai_rusak');
        const title = isRusak ? 'Tandai 1 Unit Rusak?' : '1 Unit Selesai Diservis?';
        const text = isRusak 
            ? `Pindahkan 1 unit <strong>${namaAlat}</strong> dari stok baik ke stok rusak/servis?` 
            : `Pindahkan 1 unit <strong>${namaAlat}</strong> dari stok rusak kembali ke stok baik (siap dipinjam)?`;

        Swal.fire({
            title: title,
            html: text,
            icon: isRusak ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: isRusak ? '#dc3545' : '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: isRusak ? 'Ya, Alihkan ke Rusak' : 'Ya, Siap Dipinjam',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `alat.php?action=${tipe}&id=${id}`;
            }
        });
    }

    function editAlat(tool) {
        document.getElementById('edit_alat_id').value = tool.id;
        document.getElementById('edit_alat_nama').value = tool.nama_alat;
        document.getElementById('edit_alat_stok').value = tool.stok;
        document.getElementById('edit_alat_stok_rusak').value = tool.stok_rusak || 0;
        document.getElementById('edit_alat_kategori').value = tool.category_id;
        document.getElementById('edit_alat_catatan').value = tool.catatan_kerusakan || '';
        document.getElementById('edit_alat_deskripsi').value = tool.deskripsi || '';
        new bootstrap.Modal(document.getElementById('modalEditAlat')).show();
    }

    function hapusAlat(id, nama) {
        Swal.fire({
            title: 'Hapus Alat?',
            html: `Apakah Anda yakin ingin menghapus alat <strong>${nama}</strong> dari sistem?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#800020',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `alat.php?hapus=${id}`;
            }
        });
    }
</script>
