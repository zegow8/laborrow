<?php
require_once __DIR__ . '/../config/koneksi.php';

// Jika sudah login, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: " . base_url('admin/index.php'));
    } else {
        header("Location: " . base_url('user/index.php'));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = trim($_POST['nama'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if (empty($nama) || empty($email) || empty($password) || empty($konfirmasi)) {
        set_flash('error', 'Gagal Mendaftar', 'Semua kolom formulir wajib diisi.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Format Email Salah', 'Harap masukkan alamat email yang valid.');
    } elseif (strlen($password) < 6) {
        set_flash('warning', 'Kata Sandi Lemah', 'Kata sandi minimal harus 6 karakter.');
    } elseif ($password !== $konfirmasi) {
        set_flash('error', 'Kata Sandi Tidak Cocok', 'Konfirmasi kata sandi tidak sama dengan kata sandi.');
    } else {
        // Cek apakah email sudah terdaftar
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            set_flash('error', 'Email Terdaftar', 'Alamat email ini sudah terdaftar. Silakan gunakan email lain atau login.');
        } else {
            // Hash password
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'user')");
            if ($stmtInsert->execute([$nama, $email, $hashed])) {
                set_flash('success', 'Pendaftaran Berhasil!', 'Akun Anda telah dibuat. Silakan masuk sekarang.');
                header("Location: " . base_url('auth/login.php'));
                exit;
            } else {
                set_flash('error', 'Terjadi Kesalahan', 'Gagal mendaftarkan akun. Silakan coba lagi.');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Siswa - Laborrow</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            
            <!-- Auth Header with Back Button -->
            <div class="auth-header">
                <a href="../index.php" class="auth-back-btn">
                    <i class="fa-solid fa-arrow-left me-1"></i> Beranda
                </a>
                <div class="mb-2">
                    <i class="fa-solid fa-user-plus fa-3x" style="color: var(--soft-pink);"></i>
                </div>
                <h3 class="fw-bold mb-1 text-white">LABORROW</h3>
                <p class="text-white-50 mb-0 small">Registrasi Akun Siswa Praktikan</p>
            </div>

            <!-- Auth Body -->
            <div class="auth-body">
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1" style="color: var(--primary-maroon);">Buat Akun Siswa Baru</h5>
                    <p class="text-muted small mb-0">Isi data di bawah untuk mulai meminjam alat lab</p>
                </div>
                
                <form action="" method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="nama" class="form-label fw-semibold small">Nama Lengkap Siswa</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                            <input type="text" class="form-control" id="nama" name="nama" placeholder="Contoh: Muhammad Rayhan" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small">Alamat Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="siswa@sekolah.sch.id" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small">Kata Sandi (Min. 6 Karakter)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" minlength="6" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="konfirmasi_password" class="form-label fw-semibold small">Ulangi Kata Sandi</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-check-double"></i></span>
                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" placeholder="••••••••" minlength="6" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-maroon w-100 py-2 mb-3 shadow-sm">
                        <i class="fa-solid fa-user-check me-1"></i> Daftar Sekarang
                    </button>
                </form>

                <div class="text-center pt-2 border-top">
                    <span class="text-muted small">Sudah memiliki akun? </span>
                    <a href="login.php" class="fw-bold small text-maroon">Masuk di sini</a>
                </div>
            </div>

        </div>
    </div>

    <?= render_flash() ?>
</body>
</html>
