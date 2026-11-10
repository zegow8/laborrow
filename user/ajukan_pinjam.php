<?php
require_once __DIR__ . '/../config/koneksi.php';
check_auth('user');

$tool_id = (int)($_GET['id'] ?? 0);

if ($tool_id <= 0) {
    set_flash('error', 'Alat Tidak Ditemukan', 'Parameter ID alat tidak valid.');
    header("Location: " . base_url('user/katalog.php'));
    exit;
}

// Ambil data alat
$stmt = $pdo->prepare("SELECT t.*, c.nama_kategori 
                       FROM tools t 
                       LEFT JOIN categories c ON t.category_id = c.id 
                       WHERE t.id = ? LIMIT 1");
$stmt->execute([$tool_id]);
$tool = $stmt->fetch();

if (!$tool) {
    set_flash('error', 'Alat Tidak Ditemukan', 'Data alat laboratorium tidak ada dalam database.');
    header("Location: " . base_url('user/katalog.php'));
    exit;
}

$today = date('Y-m-d');

// Proses Form Pengajuan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Cek Ketersediaan Unit Baik Secara Real-time Langsung dari Database
    $stmtCek = $pdo->prepare("SELECT stok, stok_rusak FROM tools WHERE id = ?");
    $stmtCek->execute([$tool_id]);
    $currentTool = $stmtCek->fetch();

    $unitBaik = (int)($currentTool['stok'] ?? 0);
    $unitRusak = (int)($currentTool['stok_rusak'] ?? 0);

    if ($unitBaik <= 0) {
        if ($unitRusak > 0) {
            set_flash('error', 'Unit Sedang Rusak', 'Maaf, semua unit alat ini sedang dalam perbaikan/servis dan tidak tersedia untuk dipinjam.');
        } else {
            set_flash('error', 'Stok Habis', 'Maaf, unit alat ini sedang habis dipinjam oleh praktikan lain.');
        }
        header("Location: " . base_url('user/katalog.php'));
        exit;
    }

    $tgl_pinjam  = trim($_POST['tanggal_pinjam'] ?? '');
    $tgl_kembali = trim($_POST['tanggal_kembali'] ?? '');

    // 2. Validasi Tanggal
    if (empty($tgl_pinjam) || empty($tgl_kembali)) {
        set_flash('warning', 'Data Belum Lengkap', 'Silakan pilih tanggal pinjam dan tanggal pengembalian.');
    } elseif ($tgl_pinjam < $today) {
        set_flash('error', 'Tanggal Tidak Valid', 'Tanggal pinjam tidak boleh di masa lalu. Minimal hari ini (' . date('d-m-Y') . ').');
    } elseif ($tgl_kembali < $tgl_pinjam) {
        set_flash('error', 'Tanggal Tidak Valid', 'Tanggal kembali tidak boleh lebih awal dari tanggal pinjam.');
    } else {
        // Cek apakah siswa ini sudah memiliki permohonan pending untuk alat yang sama
        $stmtPending = $pdo->prepare("SELECT id FROM loans WHERE user_id = ? AND tool_id = ? AND status = 'Pending'");
        $stmtPending->execute([$_SESSION['user_id'], $tool_id]);
        if ($stmtPending->fetch()) {
            set_flash('warning', 'Pengajuan Terduplikasi', 'Anda sudah memiliki permohonan peminjaman yang berstatus Pending untuk alat ini. Harap tunggu konfirmasi admin.');
        } else {
            // Simpan peminjaman dengan status 'Pending'
            $stmtInsert = $pdo->prepare("INSERT INTO loans (user_id, tool_id, tanggal_pinjam, tanggal_kembali, status) VALUES (?, ?, ?, ?, 'Pending')");
            if ($stmtInsert->execute([$_SESSION['user_id'], $tool_id, $tgl_pinjam, $tgl_kembali])) {
                set_flash('success', 'Pengajuan Terkirim!', 'Permohonan peminjaman alat berhasil diajukan. Silakan tunggu persetujuan dari Administrator Lab.');
                header("Location: " . base_url('user/riwayat.php'));
                exit;
            } else {
                set_flash('error', 'Gagal Mengajukan', 'Terjadi kesalahan sistem saat menyimpan data peminjaman.');
            }
        }
    }
}

$isUnavailable = ((int)$tool['stok'] <= 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Peminjaman - <?= htmlspecialchars($tool['nama_alat']) ?></title>
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
    <!-- Breadcrumb & Back -->
    <div class="mb-3">
        <a href="katalog.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Katalog
        </a>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Informasi Alat -->
        <div class="col-lg-5">
            <div class="card card-laborrow h-100">
                <div class="card-header-maroon">
                    <i class="fa-solid fa-circle-info me-2"></i> Detail Alat Laboratorium
                </div>
                <div class="p-3 text-center bg-light border-bottom">
                    <?php if (!empty($tool['foto']) && file_exists(__DIR__ . '/../assets/img/' . $tool['foto'])): ?>
                        <img src="../assets/img/<?= htmlspecialchars($tool['foto']) ?>" class="img-fluid rounded-3 shadow-sm" alt="<?= htmlspecialchars($tool['nama_alat']) ?>" style="max-height: 250px; object-fit: cover;">
                    <?php else: ?>
                        <div class="py-5 text-muted">
                            <i class="fa-solid fa-flask fa-4x text-muted opacity-50"></i>
                            <div class="small mt-2">Tidak ada foto alat</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <span class="badge bg-secondary mb-2"><?= htmlspecialchars($tool['nama_kategori'] ?? 'Umum') ?></span>
                    <h4 class="fw-bold mb-3" style="color: var(--primary-maroon);"><?= htmlspecialchars($tool['nama_alat']) ?></h4>
                    
                    <!-- Indikator Rincian Unit Baik vs Rusak -->
                    <div class="p-3 rounded-3 bg-light border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-muted">Unit Baik (Siap Pakai):</span>
                            <?php if ($tool['stok'] > 0): ?>
                                <span class="badge bg-success text-white px-2 py-1">
                                    <i class="fa-solid fa-circle-check me-1"></i> <?= $tool['stok'] ?> Unit Tersedia
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">Habis (0 Unit)</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($tool['stok_rusak'] > 0): ?>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="small fw-semibold text-danger">Unit Rusak/Servis:</span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                    <?= $tool['stok_rusak'] ?> Unit
                                </span>
                            </div>
                            <?php if (!empty($tool['catatan_kerusakan'])): ?>
                                <small class="text-danger d-block mt-1 fst-italic" style="font-size: 0.72rem;">
                                    Catatan: <?= htmlspecialchars($tool['catatan_kerusakan']) ?>
                                </small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="fw-semibold small text-muted d-block mb-1">Deskripsi & Spesifikasi:</label>
                        <p class="text-secondary small" style="white-space: pre-line;">
                            <?= !empty($tool['deskripsi']) ? htmlspecialchars($tool['deskripsi']) : 'Tidak ada catatan tambahan untuk alat ini.' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulir Pengajuan -->
        <div class="col-lg-7">
            <div class="card card-laborrow">
                <div class="card-header-maroon">
                    <i class="fa-solid fa-file-pen me-2"></i> Formulir Pengajuan Peminjaman
                </div>
                <div class="card-body p-4">
                    
                    <?php if ($tool['stok'] <= 0): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                            <i class="fa-solid fa-triangle-exclamation fa-2x me-3"></i>
                            <div>
                                <strong>Unit Siap Pakai Sedang Kosong!</strong><br>
                                <?= ($tool['stok_rusak'] > 0) ? 'Seluruh unit alat ini sedang dalam masa perbaikan/servis.' : 'Semua unit alat ini sedang habis dipinjam oleh praktikan lain.' ?> Formulir dinonaktifkan sementara.
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" id="formPeminjaman">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Nama Peminjam</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($_SESSION['nama']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Alat yang Dipinjam</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($tool['nama_alat']) ?>" readonly>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="tanggal_pinjam" class="form-label fw-semibold small">
                                    Tanggal Mulai Pinjam <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="tanggal_pinjam" name="tanggal_pinjam" 
                                       min="<?= $today ?>" value="<?= htmlspecialchars($_POST['tanggal_pinjam'] ?? $today) ?>" 
                                       required <?= $isUnavailable ? 'disabled' : '' ?>>
                                <small class="text-muted">Minimal hari ini (<?= date('d/m/Y') ?>)</small>
                            </div>

                            <div class="col-md-6">
                                <label for="tanggal_kembali" class="form-label fw-semibold small">
                                    Tanggal Rencana Kembali <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="tanggal_kembali" name="tanggal_kembali" 
                                       min="<?= $today ?>" value="<?= htmlspecialchars($_POST['tanggal_kembali'] ?? '') ?>" 
                                       required <?= $isUnavailable ? 'disabled' : '' ?>>
                                <small class="text-muted">Tidak boleh sebelum tanggal pinjam</small>
                            </div>
                        </div>

                        <div class="p-3 mb-4 rounded-3" style="background-color: #FFF5F5; border-left: 4px solid var(--primary-maroon);">
                            <h6 class="fw-bold mb-1" style="color: var(--primary-maroon);">Ketentuan Peminjaman Laboratorium:</h6>
                            <ul class="mb-0 small text-muted ps-3">
                                <li>Peminjaman hanya dapat disetujui jika kuota unit baik masih tersedia di ruang lab.</li>
                                <li>Setiap peminjaman wajib dikonfirmasi saat serah terima barang di laboratorium.</li>
                                <li>Peminjam bertanggung jawab mengembalikan alat tepat waktu dan dalam kondisi bersih.</li>
                            </ul>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="katalog.php" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-maroon" <?= $isUnavailable ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-paper-plane me-1"></i> Ajukan Permohonan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<script>
    const inputPinjam = document.getElementById('tanggal_pinjam');
    const inputKembali = document.getElementById('tanggal_kembali');

    if (inputPinjam && inputKembali) {
        inputPinjam.addEventListener('change', function() {
            inputKembali.min = this.value;
            if (inputKembali.value && inputKembali.value < this.value) {
                inputKembali.value = this.value;
            }
        });

        document.getElementById('formPeminjaman').addEventListener('submit', function(e) {
            if (inputKembali.value < inputPinjam.value) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Tanggal Tidak Sesuai',
                    text: 'Tanggal rencana kembali tidak boleh sebelum tanggal pinjam!',
                    confirmButtonColor: '#800020'
                });
            }
        });
    }
</script>
