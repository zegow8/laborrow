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
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        set_flash('error', 'Gagal Masuk', 'Harap isi semua kolom email dan password.');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            set_flash('success', 'Selamat Datang!', 'Login berhasil sebagai ' . ($user['role'] === 'admin' ? 'Administrator' : 'Siswa') . '.');

            if ($user['role'] === 'admin') {
                header("Location: " . base_url('admin/index.php'));
            } else {
                header("Location: " . base_url('user/index.php'));
            }
            exit;
        } else {
            set_flash('error', 'Login Gagal', 'Email atau kata sandi yang Anda masukkan salah.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Akun - Laborrow</title>
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
                    <i class="fa-solid fa-flask-vial fa-3x" style="color: var(--soft-pink);"></i>
                </div>
                <h3 class="fw-bold mb-1 text-white">LABORROW</h3>
                <p class="text-white-50 mb-0 small">Sistem Peminjaman Alat Laboratorium Modern</p>
            </div>

            <!-- Auth Body -->
            <div class="auth-body">
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1" style="color: var(--primary-maroon);">Selamat Datang Kembali</h5>
                    <p class="text-muted small mb-0">Silakan masukkan akun Anda untuk mengakses sistem</p>
                </div>
                
                <form action="" method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small">Alamat Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="nama@laborrow.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold small">Kata Sandi</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fa-solid fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-maroon w-100 py-2 mb-3 shadow-sm">
                        <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Masuk Sekarang
                    </button>
                </form>

                <div class="text-center mb-3">
                    <span class="text-muted small">Belum punya akun siswa? </span>
                    <a href="register.php" class="fw-bold small text-maroon">Daftar Akun Baru</a>
                </div>

                <!-- Info Demo Akun Interaktif -->
                <div class="demo-box">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-bolt text-danger"></i>
                        <span class="fw-bold small" style="color: var(--primary-maroon);">Akun Demo Siap Pakai:</span>
                    </div>
                    <div class="row g-2 small">
                        <div class="col-6">
                            <div class="demo-chip" onclick="fillDemo('admin@laborrow.com', 'admin123')">
                                <span class="badge bg-danger mb-1">Admin</span>
                                <div class="text-truncate fw-semibold" style="font-size: 0.78rem;">admin@laborrow.com</div>
                                <div class="text-muted font-monospace" style="font-size: 0.74rem;">admin123</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="demo-chip" onclick="fillDemo('siswa@laborrow.com', 'siswa123')">
                                <span class="badge bg-primary mb-1">Siswa</span>
                                <div class="text-truncate fw-semibold" style="font-size: 0.78rem;">siswa@laborrow.com</div>
                                <div class="text-muted font-monospace" style="font-size: 0.74rem;">siswa123</div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted fst-italic" style="font-size: 0.72rem;">*Klik kotak di atas untuk mengisi form otomatis</small>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?= render_flash() ?>

    <script>
        // Toggle Show/Hide Password
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function() {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });

        // Quick Fill Demo
        function fillDemo(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>
