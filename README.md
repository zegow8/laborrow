# 🧪 Laborrow — Sistem Peminjaman Alat Laboratorium

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x%20Native-800020?style=for-the-badge&logo=php&logoColor=white" alt="PHP Native">
  <img src="https://img.shields.io/badge/Database-MySQL%20(PDO)-00758F?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/UI-Bootstrap%205-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap 5">
  <img src="https://img.shields.io/badge/Notification-SweetAlert2-800020?style=for-the-badge" alt="SweetAlert2">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

**Laborrow** adalah aplikasi web sistem informasi peminjaman alat laboratorium sekolah/kampus yang dibangun menggunakan **PHP Native (PDO)** tanpa framework. Aplikasi dirancang dengan sentuhan antarmuka modern, responsif, dan elegan bertema **Maroon/Dark Red (`#800020`)** dipadukan dengan **Soft Pink (`#FFD4D4`)**.

---

## ✨ Fitur Utama

### 👨‍🎓 Sisi Siswa (Praktikan)
1. **Beranda & Landing Page Elegan**
   - Hero section visual informatif dengan CTA instan ke katalog alat.
   - Section statis alur peminjaman 3 langkah mudah.
   - *Empty state* informatif saat inventaris laboratorium masih kosong.
2. **Katalog & Pencarian Cerdas**
   - Pencarian alat berdasarkan nama dan filter kategori/kondisi fisik.
   - Pagination (8 unit per halaman).
   - **Indikator Kondisi Kelayakan Alat**:
     - 🟢 **Kondisi Baik**: Alat siap dipinjam praktikan.
     - 🔴 **Rusak / Maintenance**: Alat dilarang keras dipinjam (tombol dinonaktifkan otomatis).
   - Indikator ketersediaan stok fisik unit (*Tersedia / Stok Habis*).
3. **Formulir Pengajuan dengan Validasi Ketat**
   - **Proteksi Alat Rusak**: Permohonan otomatis ditolak jika unit berstatus rusak/perbaikan.
   - Validasi tanggal di sisi HTML5 `min` dan validasi server-side PHP.
   - Tanggal rencana kembali tidak boleh sebelum tanggal pinjam.
   - Tanggal pinjam minimal hari ini (tidak dapat memilih tanggal masa lalu).
   - Pengecekan ketersediaan stok real-time (otomatis dinonaktifkan jika stok 0).
4. **Riwayat Peminjaman Berwarna & Serah Terima Barang**
   - Status terperinci dengan warna kontras & alur serah terima riil:
     - 🟡 **Pending**: Permohonan diajukan, menunggu approval laboran.
     - 🟢 **Disetujui**: Permohonan di-acc laboran, unit disiapkan di ruang lab.
     - 🟣 **Dipinjam (Di Tangan Siswa)**: Siswa mengonfirmasi bahwa barang fisik telah diterima di lab ("Saya Sudah Ambil Alat"). Masa aktif berlaku dihitung mulai tanggal & jam serah terima.
     - 🔴 **Ditolak**: Dilengkapi tombol modal alasan penolakan.
     - 🔵 **Dikembalikan**: Alat diterima kembali oleh laboran dan stok fisik bertambah kembali (+1).
5. **Live Chat dengan Staf Lab (WhatsApp Style)**
   - Akses cepat tombol *"Hubungi Admin"* di navbar.
   - Obrolan dua arah dengan gelembung pesan rapi.
   - **Auto-Refresh Real-time** setiap 3 detik via Vanilla JavaScript Fetch API tanpa perlu reload halaman.

---

### 👨‍💼 Sisi Administrator Laboratorium
1. **Dashboard Monitoring Real-time**
   - Kartu statistik: Total inventaris alat, total siswa, pengajuan pending, dan peringatan stok menipis (`< 3 unit`).
   - Tabel pemantauan permohonan peminjaman terbaru.
2. **Manajemen Kategori & Alat (CRUD)**
   - Tambah, edit modal, dan hapus kategori.
   - Tambah dan ubah spesifikasi alat, kelola stok fisik, dan upload foto alat.
   - Validasi ketat upload gambar (format JPG, JPEG, PNG, WEBP, ukuran maksimal 2MB).
   - Pembersihan otomatis (*unlink*) file foto lama dari disk server saat data diperbarui atau dihapus.
3. **Approval & Manajemen Stok Presisi**
   - Tombol **Setujui**: Mengubah status peminjaman menjadi `Disetujui` dan **otomatis memotong stok alat fisik (-1 unit)** dengan proteksi Database Transaction.
   - Tombol **Tolak**: Mengubah status menjadi `Ditolak` dan mewajibkan admin mengisi alasan penolakan via modal.
   - Tombol **Konfirmasi Pengembalian**: Mengubah status menjadi `Dikembalikan` dan **otomatis mengembalikan stok alat (+1 unit)**.
4. **Data Siswa & Komunikasi Terintegrasi**
   - Daftar seluruh siswa praktikan terdaftar beserta rekap peminjaman.
   - Tombol instan untuk membuka sesi pesan langsung dengan siswa terkait.
5. **Panel Live Chat Admin**
   - Daftar percakapan siswa di sidebar dengan cuplikan pesan terakhir dan *unread badge count*.
   - Balas obrolan siswa secara instan tanpa reload halaman.

---

## 🎨 Tema & Skema Desain

| Elemen | Kode Hex | Keterangan |
| :--- | :--- | :--- |
| **Primary Maroon** | `#800020` | Warna utama navbar, tombol utama, heading, dan gelembung pesan keluar |
| **Soft Pink** | `#FFD4D4` | Aksen background lembut, hero ornament, dan highlight badge |
| **Soft Pink Light** | `#FFF8F8` | Latar belakang halaman aplikasi yang ramah di mata |
| **Dark Text** | `#2C2527` | Teks utama dengan kontras tinggi sehingga nyaman dibaca |

---

## 🗂️ Struktur Direktori Proyek

```text
laborrow/
├── config/
│   └── koneksi.php              # Koneksi PDO, session_start, flash SweetAlert & helpers
├── assets/
│   ├── css/
│   │   └── style.css            # Custom CSS tema Maroon & Soft Pink
│   ├── img/
│   │   └── .gitkeep             # Folder penyimpanan upload foto alat
│   └── js/
│       ├── chat.js              # Real-time engine live chat (Fetch API auto-refresh 3s)
│       └── script.js            # Skrip validasi file upload & SweetAlert trigger
├── ajax/
│   ├── get_chat.php             # Endpoint AJAX pengambilan pesan obrolan
│   └── send_chat.php            # Endpoint AJAX pengiriman pesan
├── auth/
│   ├── login.php                # Form login siswa & admin (dengan fitur quick-fill demo)
│   ├── register.php             # Form pendaftaran akun siswa baru
│   └── logout.php               # Hapus sesi dan redirect ke login
├── admin/
│   ├── index.php                # Dashboard statistik & monitoring lab
│   ├── kategori.php             # CRUD kategori alat
│   ├── alat.php                 # CRUD alat laboratorium & upload foto
│   ├── peminjaman.php           # Approval peminjaman & auto update stok
│   ├── daftar_siswa.php         # Data siswa praktikan
│   ├── chat.php                 # Live chat admin WhatsApp style
│   ├── navbar.php               # Navigasi panel admin
│   └── footer.php               # Footer panel admin
├── user/
│   ├── index.php                # Landing page siswa & empty state
│   ├── katalog.php              # Pencarian, filter & paginasi alat
│   ├── ajukan_pinjam.php        # Form peminjaman dengan validasi tanggal & stok
│   ├── riwayat.php              # Riwayat pinjam dengan badge status berwarna
│   ├── chat.php                 # Live chat siswa ke admin lab
│   ├── navbar.php               # Navigasi siswa
│   └── footer.php               # Footer siswa
├── laborrow.sql                 # Skema database & 2 akun demo awal
├── index.php                    # Landing Page Publik (Muncul pertama kali sebelum login)
└── README.md                    # Dokumentasi lengkap proyek
```

---

## 🚀 Panduan Instalasi (Laragon / XAMPP)

1. **Unduh atau Clone Repositori**
   Tempatkan folder `laborrow` ke dalam folder web server Anda:
   - Laragon: `C:\laragon\www\laborrow`
   - XAMPP: `C:\xampp\htdocs\laborrow`

2. **Impor Database MySQL**
   - Buka **phpMyAdmin** atau HeidiSQL / MySQL CLI.
   - Buat database dengan nama `laborrow` atau langsung impor file `laborrow.sql`:
     ```sql
     SOURCE c:/laragon/www/laborrow/laborrow.sql;
     ```
   - *Catatan:* Tabel `categories`, `tools`, dan `loans` dibiarkan kosong sesuai spesifikasi awal (hanya berisi 2 akun demo awal).

3. **Konfigurasi Database (Jika Diperlukan)**
   Periksa file [config/koneksi.php](file:///c:/laragon/www/laborrow/config/koneksi.php):
   ```php
   $db_host = '127.0.0.1';
   $db_port = '3306';
   $db_name = 'laborrow';
   $db_user = 'root';
   $db_pass = ''; // Sesuaikan jika MySQL Anda memiliki password
   ```

4. **Jalankan Aplikasi di Peramban**
   Buka URL berikut pada browser Anda:
   ```text
   http://localhost/laborrow/
   ```

---

## 🔑 Akun Demo Bawaan

| Role | Alamat Email | Kata Sandi | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@laborrow.com` | `admin123` | Akses penuh CRUD, approval peminjaman, & balas chat |
| **Siswa / Praktikan** | `siswa@laborrow.com` | `siswa123` | Menjelajahi katalog, mengajukan pinjaman, cek riwayat, & chat |

> *Tips:* Pada halaman [auth/login.php](file:///c:/laragon/www/laborrow/auth/login.php) telah disediakan kotak tombol cepat (*Quick Fill Demo*) yang dapat diklik untuk mengisi email dan kata sandi demo secara otomatis.

---

## 🛡️ Standar Keamanan & Best Practice
- **PDO Prepared Statements**: Menangkal serangan *SQL Injection*.
- **Password Hashing**: Menggunakan algoritma enkripsi standar industri `password_hash()` (BCRYPT).
- **Session Authentication & Role Guard**: Mencegah akses lintas hak akses (*horizontal/vertical privilege escalation*).
- **Database Transactions**: Menjamin akurasi pemotongan dan penambahan stok alat secara atomik.
- **XSS Protection**: Sanitasi output melalui `htmlspecialchars()`.
