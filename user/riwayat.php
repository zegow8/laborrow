<?php
require_once __DIR__ . '/../config/koneksi.php';
check_auth('user');

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Proses konfirmasi siswa sudah mengambil alat fisik di laboratorium
if (isset($_GET['action']) && $_GET['action'] === 'ambil') {
    $loan_id = (int)($_GET['id'] ?? 0);
    if ($loan_id > 0) {
        $stmtFind = $pdo->prepare("SELECT l.*, t.nama_alat FROM loans l JOIN tools t ON l.tool_id = t.id WHERE l.id = ? AND l.user_id = ?");
        $stmtFind->execute([$loan_id, $user_id]);
        $loan = $stmtFind->fetch();

        if ($loan && $loan['status'] === 'Disetujui') {
            $stmtUp = $pdo->prepare("UPDATE loans SET status = 'Dipinjam', tanggal_diambil = NOW() WHERE id = ?");
            if ($stmtUp->execute([$loan_id])) {
                set_flash('success', 'Barang Sudah di Tangan Anda!', "Konfirmasi serah terima alat '{$loan['nama_alat']}' berhasil. Masa peminjaman aktif terhitung mulai sekarang.");
            } else {
                set_flash('error', 'Gagal', 'Terjadi kesalahan saat memproses konfirmasi.');
            }
        }
    }
    header("Location: " . base_url('user/riwayat.php'));
    exit;
}

// Parameter Filter Status & Search
$filterStatus = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');

$where = ["l.user_id = ?"];
$params = [$user_id];

if (!empty($filterStatus)) {
    $where[] = "l.status = ?";
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $where[] = "t.nama_alat LIKE ?";
    $params[] = "%{$search}%";
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Ambil riwayat peminjaman milik user ini
$sql = "SELECT l.*, t.nama_alat, t.foto, c.nama_kategori 
        FROM loans l 
        JOIN tools t ON l.tool_id = t.id 
        LEFT JOIN categories c ON t.category_id = c.id 
        {$whereClause} 
        ORDER BY l.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$loans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Laborrow</title>
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
            <h2 class="fw-bold mb-1" style="color: var(--primary-maroon);">Riwayat Peminjaman Anda</h2>
            <p class="text-muted mb-0 small">Pantau status persetujuan, konfirmasi serah terima alat, dan batas pengembalian praktikum.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="katalog.php" class="btn btn-maroon btn-sm">
                <i class="fa-solid fa-plus me-1"></i> Pinjam Alat Baru
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar Siswa (Search by Status & Nama Alat) -->
    <div class="card card-laborrow p-3 mb-4">
        <form method="GET" action="" class="row g-2 align-items-center">
            <!-- Search Nama Alat -->
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Cari nama alat lab..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <!-- Dropdown / Tab Filter Status -->
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-filter"></i></span>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status Peminjaman</option>
                        <option value="Pending" <?= ($filterStatus === 'Pending') ? 'selected' : '' ?>>Pending (Menunggu Persetujuan)</option>
                        <option value="Disetujui" <?= ($filterStatus === 'Disetujui') ? 'selected' : '' ?>>Disetujui (Siap Diambil)</option>
                        <option value="Dipinjam" <?= ($filterStatus === 'Dipinjam') ? 'selected' : '' ?>>Dipinjam (Di Tangan Anda)</option>
                        <option value="Dikembalikan" <?= ($filterStatus === 'Dikembalikan') ? 'selected' : '' ?>>Dikembalikan (Selesai)</option>
                        <option value="Ditolak" <?= ($filterStatus === 'Ditolak') ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-maroon flex-fill">
                    <i class="fa-solid fa-sliders me-1"></i> Terapkan Filter
                </button>
                <?php if (!empty($filterStatus) || !empty($search)): ?>
                    <a href="riwayat.php" class="btn btn-outline-secondary" title="Reset Pencarian & Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Quick Filter Pills -->
        <div class="d-flex flex-wrap gap-2 pt-3 mt-2 border-top align-items-center">
            <span class="small text-muted fw-semibold me-1">Filter Cepat:</span>
            <a href="riwayat.php" class="btn btn-sm <?= empty($filterStatus) ? 'btn-maroon' : 'btn-outline-secondary' ?> py-1 px-3 rounded-pill" style="font-size: 0.78rem;">Semua</a>
            <a href="riwayat.php?status=Pending" class="btn btn-sm <?= ($filterStatus === 'Pending') ? 'btn-warning text-dark fw-bold' : 'btn-outline-warning text-dark' ?> py-1 px-3 rounded-pill" style="font-size: 0.78rem;">Pending</a>
            <a href="riwayat.php?status=Disetujui" class="btn btn-sm <?= ($filterStatus === 'Disetujui') ? 'btn-success fw-bold' : 'btn-outline-success' ?> py-1 px-3 rounded-pill" style="font-size: 0.78rem;">Siap Diambil</a>
            <a href="riwayat.php?status=Dipinjam" class="btn btn-sm <?= ($filterStatus === 'Dipinjam') ? 'btn-primary fw-bold' : 'btn-outline-primary' ?> py-1 px-3 rounded-pill" style="font-size: 0.78rem; <?= ($filterStatus === 'Dipinjam') ? 'background-color: #6d28d9; border-color: #6d28d9;' : 'color: #6d28d9; border-color: #6d28d9;' ?>">Di Tangan Anda</a>
            <a href="riwayat.php?status=Dikembalikan" class="btn btn-sm <?= ($filterStatus === 'Dikembalikan') ? 'btn-info text-white fw-bold' : 'btn-outline-info text-dark' ?> py-1 px-3 rounded-pill" style="font-size: 0.78rem;">Selesai Dikembalikan</a>
            <a href="riwayat.php?status=Ditolak" class="btn btn-sm <?= ($filterStatus === 'Ditolak') ? 'btn-danger fw-bold' : 'btn-outline-danger' ?> py-1 px-3 rounded-pill" style="font-size: 0.78rem;">Ditolak</a>
            <span class="ms-auto badge bg-light text-muted border p-2" style="font-size: 0.75rem;">Total: <strong><?= count($loans) ?></strong> Riwayat</span>
        </div>
    </div>

    <?php if (empty($loans)): ?>
        <div class="empty-state">
            <div class="empty-state-icon-wrapper">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">
                <?= (!empty($filterStatus) || !empty($search)) ? 'Data Peminjaman Tidak Ditemukan' : 'Belum Ada Riwayat Peminjaman' ?>
            </h4>
            <p class="text-muted col-md-6 mx-auto mb-4">
                <?= (!empty($filterStatus) || !empty($search)) 
                    ? 'Tidak ditemukan riwayat peminjaman dengan filter status atau kata kunci yang dipilih.' 
                    : 'Anda belum pernah mengajukan peminjaman alat laboratorium di Laborrow. Buka katalog untuk melihat daftar peralatan yang tersedia.' ?>
            </p>
            <?php if (!empty($filterStatus) || !empty($search)): ?>
                <a href="riwayat.php" class="btn btn-outline-maroon">
                    <i class="fa-solid fa-rotate-left me-1"></i> Tampilkan Semua Riwayat
                </a>
            <?php else: ?>
                <a href="katalog.php" class="btn btn-maroon">
                    <i class="fa-solid fa-microscope me-2"></i> Buka Katalog Alat
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card card-laborrow">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small" style="letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>Alat Laboratorium</th>
                            <th>Tgl Rencana Pinjam</th>
                            <th>Tgl Batas Kembali</th>
                            <th>Status Alat</th>
                            <th>Keterangan / Serah Terima</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($loans as $loan): ?>
                            <tr>
                                <td class="ps-4 fw-semibold text-muted"><?= $no++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($loan['foto']) && file_exists(__DIR__ . '/../assets/img/' . $loan['foto'])): ?>
                                            <img src="../assets/img/<?= htmlspecialchars($loan['foto']) ?>" alt="" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded bg-light d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px;">
                                                <i class="fa-solid fa-flask"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($loan['nama_alat']) ?></div>
                                            <span class="badge bg-light text-muted border"><?= htmlspecialchars($loan['nama_kategori'] ?? 'Umum') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= date('d-m-Y', strtotime($loan['tanggal_pinjam'])) ?></div>
                                    <small class="text-muted"><?= format_tanggal_indo($loan['tanggal_pinjam']) ?></small>
                                </td>

                                <!-- KOLOM TGL BATAS KEMBALI (KONTEKSTUAL SESUAI STATUS MASING-MASING) -->
                                <td>
                                    <?php if ($loan['status'] === 'Dikembalikan'): ?>
                                        <!-- KASUS 1: TELAH DIKEMBALIKAN (TIDAK MERAH, ADA KETERANGAN TELAH DIKEMBALIKAN) -->
                                        <div class="fw-semibold text-dark"><?= date('d-m-Y', strtotime($loan['tanggal_kembali'])) ?></div>
                                        <small class="text-muted d-block"><?= format_tanggal_indo($loan['tanggal_kembali']) ?></small>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle mt-1" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-circle-check me-1"></i>Telah Dikembalikan
                                        </span>

                                    <?php elseif ($loan['status'] === 'Dipinjam'): ?>
                                        <!-- KASUS 2: DI TANGAN ANDA (DEADLINE AKTIF) -->
                                        <?php if ($loan['tanggal_kembali'] < $today): ?>
                                            <div class="fw-bold text-danger"><?= date('d-m-Y', strtotime($loan['tanggal_kembali'])) ?></div>
                                            <small class="text-danger d-block"><?= format_tanggal_indo($loan['tanggal_kembali']) ?></small>
                                            <span class="badge bg-danger text-white mt-1" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i>Terlambat Dikembalikan
                                            </span>
                                        <?php elseif ($loan['tanggal_kembali'] === $today): ?>
                                            <div class="fw-bold text-danger"><?= date('d-m-Y', strtotime($loan['tanggal_kembali'])) ?></div>
                                            <small class="text-muted d-block"><?= format_tanggal_indo($loan['tanggal_kembali']) ?></small>
                                            <span class="badge bg-warning text-dark border border-warning mt-1" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-clock me-1"></i>Batas Hari Ini!
                                            </span>
                                        <?php else: ?>
                                            <div class="fw-bold text-danger"><?= date('d-m-Y', strtotime($loan['tanggal_kembali'])) ?></div>
                                            <small class="text-muted d-block"><?= format_tanggal_indo($loan['tanggal_kembali']) ?></small>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-hourglass-end me-1"></i>Wajib Kembali
                                            </span>
                                        <?php endif; ?>

                                    <?php elseif ($loan['status'] === 'Disetujui'): ?>
                                        <!-- KASUS 3: DISETUJUI TAPI BELUM DIAMBIL -->
                                        <div class="fw-semibold text-secondary"><?= date('d-m-Y', strtotime($loan['tanggal_kembali'])) ?></div>
                                        <small class="text-muted d-block"><?= format_tanggal_indo($loan['tanggal_kembali']) ?></small>
                                        <span class="badge bg-light text-muted border mt-1" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-calendar me-1"></i>Target Pengembalian
                                        </span>

                                    <?php elseif ($loan['status'] === 'Pending'): ?>
                                        <!-- KASUS 4: PENDING MENUNGGU PERSETUJUAN -->
                                        <div class="fw-semibold text-muted"><?= date('d-m-Y', strtotime($loan['tanggal_kembali'])) ?></div>
                                        <small class="text-muted d-block"><?= format_tanggal_indo($loan['tanggal_kembali']) ?></small>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle mt-1" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-clock me-1"></i>Rencana Kembali
                                        </span>

                                    <?php elseif ($loan['status'] === 'Ditolak'): ?>
                                        <!-- KASUS 5: DITOLAK (TANGGAL TIDAK BERLAKU) -->
                                        <div class="fw-semibold text-muted text-decoration-line-through"><?= date('d-m-Y', strtotime($loan['tanggal_kembali'])) ?></div>
                                        <small class="text-muted text-decoration-line-through d-block"><?= format_tanggal_indo($loan['tanggal_kembali']) ?></small>
                                        <span class="badge bg-secondary-subtle text-secondary border mt-1" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-ban me-1"></i>Batal / Ditolak
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- KOLOM STATUS ALAT -->
                                <td>
                                    <?php if ($loan['status'] === 'Pending'): ?>
                                        <span class="badge-status badge-pending">
                                            <i class="fa-solid fa-hourglass-half"></i> Pending
                                        </span>
                                    <?php elseif ($loan['status'] === 'Disetujui'): ?>
                                        <span class="badge-status badge-disetujui">
                                            <i class="fa-solid fa-check"></i> Siap Diambil di Lab
                                        </span>
                                    <?php elseif ($loan['status'] === 'Dipinjam'): ?>
                                        <span class="badge-status badge-dipinjam">
                                            <i class="fa-solid fa-hand-holding"></i> Di Tangan Anda
                                        </span>
                                    <?php elseif ($loan['status'] === 'Ditolak'): ?>
                                        <span class="badge-status badge-ditolak">
                                            <i class="fa-solid fa-circle-xmark"></i> Ditolak
                                        </span>
                                    <?php elseif ($loan['status'] === 'Dikembalikan'): ?>
                                        <span class="badge-status badge-dikembalikan">
                                            <i class="fa-solid fa-box-archive"></i> Selesai Dikembalikan
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- KOLOM KETERANGAN / AKSI -->
                                <td>
                                    <?php if ($loan['status'] === 'Disetujui'): ?>
                                        <div>
                                            <button class="btn btn-sm btn-success shadow-sm fw-bold mb-1" onclick="konfirmasiAmbil(<?= $loan['id'] ?>, '<?= htmlspecialchars(addslashes($loan['nama_alat'])) ?>')">
                                                <i class="fa-solid fa-box-check me-1"></i> Saya Sudah Ambil Alat
                                            </button>
                                            <div class="small text-muted" style="font-size: 0.74rem;">
                                                <i class="fa-solid fa-circle-info text-primary me-1"></i> Klik jika sudah menerima barang fisik di ruang lab
                                            </div>
                                        </div>

                                    <?php elseif ($loan['status'] === 'Dipinjam'): ?>
                                        <div class="p-2 rounded bg-light border">
                                            <div class="fw-bold text-primary small d-flex align-items-center gap-1">
                                                <i class="fa-solid fa-circle-check text-success"></i> Barang Aktif Digunakan
                                            </div>
                                            <div class="text-muted small" style="font-size: 0.74rem;">
                                                Diambil: <?= !empty($loan['tanggal_diambil']) ? date('d/m/Y H:i', strtotime($loan['tanggal_diambil'])) : '-' ?> WIB
                                            </div>
                                        </div>

                                    <?php elseif ($loan['status'] === 'Ditolak' && !empty($loan['alasan_penolakan'])): ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="lihatAlasan('<?= htmlspecialchars(addslashes($loan['alasan_penolakan'])) ?>')">
                                            <i class="fa-solid fa-circle-info me-1"></i> Alasan Ditolak
                                        </button>

                                    <?php elseif ($loan['status'] === 'Pending'): ?>
                                        <span class="text-muted small">
                                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Menunggu persetujuan admin lab
                                        </span>

                                    <?php elseif ($loan['status'] === 'Dikembalikan'): ?>
                                        <span class="text-primary small fw-semibold">
                                            <i class="fa-solid fa-check-double text-success me-1"></i> Alat sudah dikembalikan ke lab
                                        </span>

                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<script>
    function konfirmasiAmbil(id, namaAlat) {
        Swal.fire({
            title: 'Konfirmasi Penerimaan Alat?',
            html: `Apakah Anda menyatakan bahwa alat <strong>${namaAlat}</strong> sudah benar-benar Anda terima secara fisik di ruang laboratorium?<br><br><small class="text-muted">Masa berlaku peminjaman aktif akan dimulai sekarang.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Alat Sudah Saya Ambil!',
            cancelButtonText: 'Belum'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `riwayat.php?action=ambil&id=${id}`;
            }
        });
    }

    function lihatAlasan(alasan) {
        Swal.fire({
            icon: 'info',
            title: 'Alasan Penolakan',
            text: alasan,
            confirmButtonColor: '#800020'
        });
    }
</script>
