<?php
require_once __DIR__ . '/../config/koneksi.php';
check_auth('admin');

// 1. Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    if (empty($nama_kategori)) {
        set_flash('warning', 'Data Kosong', 'Nama kategori tidak boleh kosong.');
    } else {
        $stmtInsert = $pdo->prepare("INSERT INTO categories (nama_kategori) VALUES (?)");
        if ($stmtInsert->execute([$nama_kategori])) {
            set_flash('success', 'Berhasil', 'Kategori baru berhasil ditambahkan.');
        } else {
            set_flash('error', 'Gagal', 'Terjadi kesalahan saat menambahkan kategori.');
        }
    }
    header("Location: " . base_url('admin/kategori.php'));
    exit;
}

// 2. Edit Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    if ($id <= 0 || empty($nama_kategori)) {
        set_flash('warning', 'Data Tidak Valid', 'Parameter edit kategori tidak lengkap.');
    } else {
        $stmtUpdate = $pdo->prepare("UPDATE categories SET nama_kategori = ? WHERE id = ?");
        if ($stmtUpdate->execute([$nama_kategori, $id])) {
            set_flash('success', 'Berhasil', 'Kategori berhasil diperbarui.');
        } else {
            set_flash('error', 'Gagal', 'Gagal memperbarui kategori.');
        }
    }
    header("Location: " . base_url('admin/kategori.php'));
    exit;
}

// 3. Hapus Kategori
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id > 0) {
        // Cek apakah ada alat terkait
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tools WHERE category_id = ?");
        $stmtCheck->execute([$id]);
        $toolsCount = (int)$stmtCheck->fetchColumn();

        $stmtDel = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmtDel->execute([$id])) {
            if ($toolsCount > 0) {
                set_flash('info', 'Kategori Dihapus', "Kategori dan {$toolsCount} alat di dalamnya telah dihapus.");
            } else {
                set_flash('success', 'Berhasil', 'Kategori berhasil dihapus.');
            }
        } else {
            set_flash('error', 'Gagal', 'Gagal menghapus kategori.');
        }
    }
    header("Location: " . base_url('admin/kategori.php'));
    exit;
}

// Ambil semua kategori beserta hitungan alat di dalamnya
$stmt = $pdo->query("SELECT c.*, COUNT(t.id) AS total_alat 
                     FROM categories c 
                     LEFT JOIN tools t ON c.id = t.category_id 
                     GROUP BY c.id 
                     ORDER BY c.nama_kategori ASC");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Laborrow</title>
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
                <i class="fa-solid fa-tags me-2"></i>Kategori Alat Laboratorium
            </h2>
            <p class="text-muted mb-0 small">Kelola klasifikasi alat praktikum (contoh: Kimia, Fisika, Biologi, Instrumen Gelas, dll).</p>
        </div>
        <div>
            <button class="btn btn-maroon btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="fa-solid fa-plus me-1"></i> Tambah Kategori Baru
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-laborrow">
                <div class="card-header-maroon d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-list me-2"></i> Daftar Kategori</span>
                    <span class="badge bg-light text-dark"><?= count($categories) ?> Kategori</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($categories)): ?>
                        <div class="empty-state border-0 my-0 py-5">
                            <i class="fa-solid fa-folder-open empty-state-icon"></i>
                            <h5 class="fw-bold">Belum Ada Kategori</h5>
                            <p class="text-muted small mb-3">Tambahkan kategori alat untuk memudahkan pengelompokan inventaris lab.</p>
                            <button class="btn btn-sm btn-maroon" data-bs-toggle="modal" data-bs-target="#modalTambah">
                                Tambah Sekarang
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th class="ps-4" style="width: 70px;">No</th>
                                        <th>Nama Kategori</th>
                                        <th>Total Alat Terkait</th>
                                        <th class="text-end pe-4" style="width: 150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($categories as $cat): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold text-muted"><?= $no++ ?></td>
                                            <td>
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($cat['nama_kategori']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fa-solid fa-flask me-1 text-danger"></i> <?= $cat['total_alat'] ?> Alat
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editKategori(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['nama_kategori'])) ?>')" 
                                                        title="Ubah Kategori">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="hapusKategori(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['nama_kategori'])) ?>')" 
                                                        title="Hapus Kategori">
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
        </div>

        <div class="col-lg-4">
            <div class="card card-laborrow">
                <div class="card-header-soft fw-bold" style="color: var(--primary-maroon);">
                    <i class="fa-solid fa-circle-question me-2"></i> Informasi Kategori
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        Kategori digunakan oleh siswa saat memfilter katalog alat.
                    </p>
                    <div class="alert alert-warning small mb-0" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        <strong>Perhatian:</strong> Menghapus kategori yang memiliki alat akan menghapus alat-alat terkait (cascade) di dalamnya.
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header card-header-maroon text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-folder-plus me-2"></i> Tambah Kategori Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Alat Gelas, Mikroskopik, Elektronik..." required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-maroon">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header card-header-maroon text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i> Ubah Kategori</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kategori" id="edit_nama_kategori" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-maroon">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<script>
    function editKategori(id, nama) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama_kategori').value = nama;
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }

    function hapusKategori(id, nama) {
        Swal.fire({
            title: 'Hapus Kategori?',
            html: `Apakah Anda yakin ingin menghapus kategori <strong>${nama}</strong>?<br><small class='text-danger'>Alat di dalam kategori ini juga akan ikut terhapus!</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#800020',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `kategori.php?hapus=${id}`;
            }
        });
    }
</script>
