<?php
require_once __DIR__ . '/../config/koneksi.php';
check_auth('admin');

// 1. PROSES SETUJUI PEMINJAMAN
if (isset($_GET['action']) && $_GET['action'] === 'setujui') {
    $loan_id = (int)($_GET['id'] ?? 0);
    if ($loan_id > 0) {
        $stmtFind = $pdo->prepare("SELECT l.*, t.stok, t.nama_alat FROM loans l JOIN tools t ON l.tool_id = t.id WHERE l.id = ?");
        $stmtFind->execute([$loan_id]);
        $loan = $stmtFind->fetch();

        if ($loan && $loan['status'] === 'Pending') {
            if ($loan['stok'] <= 0) {
                set_flash('error', 'Gagal Menyetujui', "Stok untuk alat '{$loan['nama_alat']}' saat ini adalah 0. Tidak dapat disetujui.");
            } else {
                try {
                    $pdo->beginTransaction();

                    // Update status peminjaman jadi 'Disetujui' (Siap diambil)
                    $stmtUp = $pdo->prepare("UPDATE loans SET status = 'Disetujui' WHERE id = ?");
                    $stmtUp->execute([$loan_id]);

                    // Potong stok fisik alat
                    $stmtStok = $pdo->prepare("UPDATE tools SET stok = stok - 1 WHERE id = ?");
                    $stmtStok->execute([$loan['tool_id']]);

                    $pdo->commit();
                    set_flash('success', 'Disetujui', 'Peminjaman telah disetujui dan 1 unit stok alat telah dipotong. Menunggu pengambilan alat oleh siswa.');
                } catch (Exception $e) {
                    $pdo->rollBack();
                    set_flash('error', 'Kesalahan', 'Gagal memproses persetujuan: ' . $e->getMessage());
                }
            }
        }
    }
    header("Location: " . base_url('admin/peminjaman.php'));
    exit;
}

// 2. PROSES SERAH TERIMA ALAT OLEH ADMIN (STATUS DISIAPKAN -> DIPINJAM)
if (isset($_GET['action']) && $_GET['action'] === 'serahkan') {
    $loan_id = (int)($_GET['id'] ?? 0);
    if ($loan_id > 0) {
        $stmtFind = $pdo->prepare("SELECT l.*, t.nama_alat, u.nama AS nama_siswa FROM loans l JOIN tools t ON l.tool_id = t.id JOIN users u ON l.user_id = u.id WHERE l.id = ?");
        $stmtFind->execute([$loan_id]);
        $loan = $stmtFind->fetch();

        if ($loan && $loan['status'] === 'Disetujui') {
            $stmtUp = $pdo->prepare("UPDATE loans SET status = 'Dipinjam', tanggal_diambil = NOW() WHERE id = ?");
            if ($stmtUp->execute([$loan_id])) {
                set_flash('success', 'Serah Terima Selesai', "Alat '{$loan['nama_alat']}' telah diserahkan kepada siswa '{$loan['nama_siswa']}'. Masa peminjaman aktif.");
            }
        }
    }
    header("Location: " . base_url('admin/peminjaman.php'));
    exit;
}

// 3. PROSES TOLAK PEMINJAMAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tolak') {
    $loan_id = (int)($_POST['loan_id'] ?? 0);
    $alasan  = trim($_POST['alasan_penolakan'] ?? 'Maaf, permohonan peminjaman belum dapat disetujui.');

    if ($loan_id > 0) {
        $stmtFind = $pdo->prepare("SELECT status FROM loans WHERE id = ?");
        $stmtFind->execute([$loan_id]);
        $loan = $stmtFind->fetch();

        if ($loan && $loan['status'] === 'Pending') {
            $stmtUp = $pdo->prepare("UPDATE loans SET status = 'Ditolak', alasan_penolakan = ? WHERE id = ?");
            if ($stmtUp->execute([$alasan, $loan_id])) {
                set_flash('info', 'Permohonan Ditolak', 'Status peminjaman diubah menjadi Ditolak dengan alasan yang dicantumkan.');
            } else {
                set_flash('error', 'Gagal', 'Terjadi kesalahan saat memperbarui status.');
            }
        }
    }
    header("Location: " . base_url('admin/peminjaman.php'));
    exit;
}

// 4. PROSES PENGEMBALIAN ALAT (KEMBALIKAN STOK FISIK)
if (isset($_GET['action']) && $_GET['action'] === 'kembalikan') {
    $loan_id = (int)($_GET['id'] ?? 0);
    if ($loan_id > 0) {
        $stmtFind = $pdo->prepare("SELECT l.*, t.nama_alat FROM loans l JOIN tools t ON l.tool_id = t.id WHERE l.id = ?");
        $stmtFind->execute([$loan_id]);
        $loan = $stmtFind->fetch();

        if ($loan && ($loan['status'] === 'Disetujui' || $loan['status'] === 'Dipinjam')) {
            try {
                $pdo->beginTransaction();

                // Ubah status ke 'Dikembalikan'
                $stmtUp = $pdo->prepare("UPDATE loans SET status = 'Dikembalikan' WHERE id = ?");
                $stmtUp->execute([$loan_id]);

                // Kembalikan stok alat (+1)
                $stmtStok = $pdo->prepare("UPDATE tools SET stok = stok + 1 WHERE id = ?");
                $stmtStok->execute([$loan['tool_id']]);

                $pdo->commit();
                set_flash('success', 'Alat Dikembalikan', "Peminjaman selesai! 1 unit alat '{$loan['nama_alat']}' telah ditambahkan kembali ke inventaris.");
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash('error', 'Kesalahan', 'Gagal memproses pengembalian: ' . $e->getMessage());
            }
        }
    }
    header("Location: " . base_url('admin/peminjaman.php'));
    exit;
}

// Filter Status
$filterStatus = trim($_GET['status'] ?? '');
$params = [];
$whereSql = "";

if (!empty($filterStatus)) {
    $whereSql = "WHERE l.status = ?";
    $params[] = $filterStatus;
}

// Ambil semua daftar peminjaman
$sql = "SELECT l.*, u.nama AS nama_siswa, u.email AS email_siswa, t.nama_alat, t.foto, t.stok, c.nama_kategori 
        FROM loans l 
        JOIN users u ON l.user_id = u.id 
        JOIN tools t ON l.tool_id = t.id 
        LEFT JOIN categories c ON t.category_id = c.id 
        {$whereSql} 
        ORDER BY CASE 
            WHEN l.status = 'Pending' THEN 1 
            WHEN l.status = 'Disetujui' THEN 2 
            WHEN l.status = 'Dipinjam' THEN 3
            ELSE 4 
        END, l.id DESC";

$stmtLoans = $pdo->prepare($sql);
$stmtLoans->execute($params);
$loans = $stmtLoans->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Peminjaman - Laborrow</title>
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
                <i class="fa-solid fa-hand-holding-hand me-2"></i>Manajemen Peminjaman & Serah Terima Alat
            </h2>
            <p class="text-muted mb-0 small">Approval permohonan, pantau alat yang sedang di tangan siswa, dan konfirmasi pengembalian alat fisik ke lab.</p>
        </div>
    </div>

    <!-- Filter Tab Bar -->
    <div class="card card-laborrow p-3 mb-4">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-semibold small text-muted me-2"><i class="fa-solid fa-filter me-1"></i> Filter Status:</span>
            <a href="peminjaman.php" class="btn btn-sm <?= empty($filterStatus) ? 'btn-maroon' : 'btn-outline-secondary' ?>">
                Semua Data
            </a>
            <a href="peminjaman.php?status=Pending" class="btn btn-sm <?= ($filterStatus === 'Pending') ? 'btn-warning text-dark fw-bold' : 'btn-outline-warning text-dark' ?>">
                <i class="fa-solid fa-clock me-1"></i> Pending
            </a>
            <a href="peminjaman.php?status=Disetujui" class="btn btn-sm <?= ($filterStatus === 'Disetujui') ? 'btn-success fw-bold' : 'btn-outline-success' ?>">
                <i class="fa-solid fa-check me-1"></i> Disetujui (Siap Diambil)
            </a>
            <a href="peminjaman.php?status=Dipinjam" class="btn btn-sm <?= ($filterStatus === 'Dipinjam') ? 'btn-primary fw-bold' : 'btn-outline-primary' ?>" style="<?= ($filterStatus === 'Dipinjam') ? 'background-color: #6d28d9; border-color: #6d28d9; color: #fff;' : 'color: #6d28d9; border-color: #6d28d9;' ?>">
                <i class="fa-solid fa-hand-holding me-1"></i> Dipinjam (Di Tangan Siswa)
            </a>
            <a href="peminjaman.php?status=Dikembalikan" class="btn btn-sm <?= ($filterStatus === 'Dikembalikan') ? 'btn-info text-white fw-bold' : 'btn-outline-info text-dark' ?>">
                <i class="fa-solid fa-box-archive me-1"></i> Dikembalikan
            </a>
            <a href="peminjaman.php?status=Ditolak" class="btn btn-sm <?= ($filterStatus === 'Ditolak') ? 'btn-danger fw-bold' : 'btn-outline-danger' ?>">
                <i class="fa-solid fa-xmark me-1"></i> Ditolak
            </a>
            <span class="ms-auto badge bg-light text-dark border p-2">Total: <strong><?= count($loans) ?></strong> Data</span>
        </div>
    </div>

    <!-- Tabel Peminjaman -->
    <div class="card card-laborrow">
        <div class="card-body p-0">
            <?php if (empty($loans)): ?>
                <div class="empty-state border-0 my-0 py-5">
                    <div class="empty-state-icon-wrapper">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <h5 class="fw-bold">Tidak Ada Data Peminjaman</h5>
                    <p class="text-muted small col-md-5 mx-auto mb-0">
                        <?= !empty($filterStatus) ? "Tidak ada permohonan pinjam dengan status '{$filterStatus}'." : "Belum ada riwayat permohonan peminjaman alat di sistem." ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="ps-4">No</th>
                                <th>Peminjam (Siswa)</th>
                                <th>Alat Laboratorium</th>
                                <th>Jadwal & Serah Terima</th>
                                <th>Status Alat</th>
                                <th class="text-end pe-4">Aksi Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($loans as $l): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-muted"><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($l['nama_siswa']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($l['email_siswa']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($l['nama_alat']) ?></div>
                                        <small class="text-muted">Kategori: <?= htmlspecialchars($l['nama_kategori'] ?? 'Umum') ?> | Sisa Stok: <strong><?= $l['stok'] ?></strong></small>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <span class="text-muted">Jadwal:</span> <?= date('d/m/Y', strtotime($l['tanggal_pinjam'])) ?> &rarr; <?= date('d/m/Y', strtotime($l['tanggal_kembali'])) ?>
                                        </div>
                                        <?php if (!empty($l['tanggal_diambil'])): ?>
                                            <div class="badge bg-purple-subtle text-purple border mt-1" style="background-color: #f5f3ff; color: #6d28d9; border-color: #ddd6fe !important;">
                                                <i class="fa-solid fa-hand-holding me-1"></i> Diambil: <?= date('d/m/Y H:i', strtotime($l['tanggal_diambil'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($l['status'] === 'Pending'): ?>
                                            <span class="badge-status badge-pending"><i class="fa-solid fa-hourglass-half"></i> Pending</span>
                                        <?php elseif ($l['status'] === 'Disetujui'): ?>
                                            <span class="badge-status badge-disetujui"><i class="fa-solid fa-check"></i> Siap Diambil</span>
                                        <?php elseif ($l['status'] === 'Dipinjam'): ?>
                                            <span class="badge-status badge-dipinjam"><i class="fa-solid fa-hand-holding"></i> Di Tangan Siswa</span>
                                        <?php elseif ($l['status'] === 'Ditolak'): ?>
                                            <span class="badge-status badge-ditolak"><i class="fa-solid fa-xmark"></i> Ditolak</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-dikembalikan"><i class="fa-solid fa-box-archive"></i> Dikembalikan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if ($l['status'] === 'Pending'): ?>
                                            <!-- Tombol Setujui -->
                                            <button class="btn btn-sm btn-success me-1" 
                                                    onclick="setujuiPinjam(<?= $l['id'] ?>, '<?= htmlspecialchars(addslashes($l['nama_siswa'])) ?>', '<?= htmlspecialchars(addslashes($l['nama_alat'])) ?>', <?= $l['stok'] ?>)" 
                                                    title="Setujui & Potong Stok">
                                                <i class="fa-solid fa-check me-1"></i> Setujui
                                            </button>
                                            <!-- Tombol Tolak -->
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="tolakPinjam(<?= $l['id'] ?>, '<?= htmlspecialchars(addslashes($l['nama_siswa'])) ?>')" 
                                                    title="Tolak Permohonan">
                                                <i class="fa-solid fa-xmark me-1"></i> Tolak
                                            </button>

                                        <?php elseif ($l['status'] === 'Disetujui'): ?>
                                            <!-- Serahkan Alat ke Siswa di Meja Lab -->
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="serahkanAlat(<?= $l['id'] ?>, '<?= htmlspecialchars(addslashes($l['nama_siswa'])) ?>', '<?= htmlspecialchars(addslashes($l['nama_alat'])) ?>')"
                                                    title="Konfirmasi Penyerahan Fisik Alat">
                                                <i class="fa-solid fa-hand-holding me-1"></i> Serahkan Alat
                                            </button>
                                            <button class="btn btn-sm btn-secondary" 
                                                    onclick="kembalikanAlat(<?= $l['id'] ?>, '<?= htmlspecialchars(addslashes($l['nama_alat'])) ?>')" 
                                                    title="Batalkan & Kembalikan ke Rak">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>

                                        <?php elseif ($l['status'] === 'Dipinjam'): ?>
                                            <!-- Tombol Kembalikan Alat dari Tangan Siswa -->
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="kembalikanAlat(<?= $l['id'] ?>, '<?= htmlspecialchars(addslashes($l['nama_alat'])) ?>')" 
                                                    title="Konfirmasi Pengembalian Alat Fisik">
                                                <i class="fa-solid fa-box-archive me-1"></i> Terima Pengembalian
                                            </button>

                                        <?php elseif ($l['status'] === 'Ditolak'): ?>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="lihatAlasanAdmin('<?= htmlspecialchars(addslashes($l['alasan_penolakan'] ?? 'Tanpa alasan')) ?>')">
                                                <i class="fa-solid fa-eye me-1"></i> Alasan
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary small">Selesai Dikembalikan</span>
                                        <?php endif; ?>
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

<!-- Modal Tolak Peminjaman -->
<div class="modal fade" id="modalTolak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-circle-xmark me-2"></i> Tolak Permohonan Pinjam</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="tolak">
                <input type="hidden" name="loan_id" id="tolak_loan_id">
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3" id="tolak_peminjam_info"></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="alasan_penolakan" class="form-control" rows="3" placeholder="Contoh: Stok alat habis, jadwal pemakaian bentrok dengan praktikum wajib kelas XII..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Permohonan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<script>
    function setujuiPinjam(id, namaSiswa, namaAlat, stok) {
        if (stok <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Stok Habis!',
                text: `Tidak dapat menyetujui peminjaman ${namaAlat} karena stok fisik bernilai 0 unit.`,
                confirmButtonColor: '#800020'
            });
            return;
        }

        Swal.fire({
            title: 'Setujui Peminjaman?',
            html: `Apakah Anda yakin ingin menyetujui peminjaman alat <strong>${namaAlat}</strong> oleh <strong>${namaSiswa}</strong>?<br><small class='text-muted'>Stok alat akan otomatis berkurang 1 unit dan siap diambil di lab.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Setujui!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `peminjaman.php?action=setujui&id=${id}`;
            }
        });
    }

    function serahkanAlat(id, namaSiswa, namaAlat) {
        Swal.fire({
            title: 'Serah Terima Alat?',
            html: `Konfirmasi bahwa alat <strong>${namaAlat}</strong> telah Anda serahkan secara fisik kepada <strong>${namaSiswa}</strong> di laboratorium.<br><small class='text-muted'>Masa peminjaman aktif dimulai.</small>`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Alat Sudah Diserahkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `peminjaman.php?action=serahkan&id=${id}`;
            }
        });
    }

    function tolakPinjam(id, namaSiswa) {
        document.getElementById('tolak_loan_id').value = id;
        document.getElementById('tolak_peminjam_info').innerHTML = `Menolak permohonan peminjaman dari siswa: <strong>${namaSiswa}</strong>.`;
        new bootstrap.Modal(document.getElementById('modalTolak')).show();
    }

    function kembalikanAlat(id, namaAlat) {
        Swal.fire({
            title: 'Konfirmasi Pengembalian Alat?',
            html: `Pastikan alat <strong>${namaAlat}</strong> sudah Anda terima kembali di laboratorium dalam kondisi baik.<br><small class='text-muted'>Stok alat akan bertambah 1 unit kembali ke inventaris.</small>`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Alat Sudah Kembali',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `peminjaman.php?action=kembalikan&id=${id}`;
            }
        });
    }

    function lihatAlasanAdmin(alasan) {
        Swal.fire({
            icon: 'info',
            title: 'Alasan Penolakan',
            text: alasan,
            confirmButtonColor: '#800020'
        });
    }
</script>
