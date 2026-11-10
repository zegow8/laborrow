-- ========================================================
-- Database: laborrow
-- Sistem Peminjaman Alat Laboratorium (Laborrow)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `laborrow` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `laborrow`;

-- --------------------------------------------------------
-- Struktur Tabel `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `loans`;
DROP TABLE IF EXISTS `tools`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur Tabel `categories`
-- DILARANG ISI DUMMY DATA
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kategori` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur Tabel `tools`
-- DILARANG ISI DUMMY DATA
-- --------------------------------------------------------
CREATE TABLE `tools` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `nama_alat` VARCHAR(150) NOT NULL,
  `stok` INT NOT NULL DEFAULT 0,
  `stok_rusak` INT NOT NULL DEFAULT 0,
  `catatan_kerusakan` VARCHAR(255) DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tools_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur Tabel `loans`
-- DILARANG ISI DUMMY DATA
-- --------------------------------------------------------
CREATE TABLE `loans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `tool_id` INT NOT NULL,
  `tanggal_pinjam` DATE NOT NULL,
  `tanggal_kembali` DATE NOT NULL,
  `tanggal_diambil` DATETIME DEFAULT NULL,
  `status` ENUM('Pending', 'Disetujui', 'Dipinjam', 'Ditolak', 'Dikembalikan') NOT NULL DEFAULT 'Pending',
  `alasan_penolakan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_loans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_loans_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Struktur Tabel `messages`
-- --------------------------------------------------------
CREATE TABLE `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `isi_pesan` TEXT NOT NULL,
  `tanggal` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Data Awal: HANYA 2 Akun Demo (admin & user/siswa)
-- --------------------------------------------------------
INSERT INTO `users` (`nama`, `email`, `password`, `role`) VALUES
('Administrator Lab', 'admin@laborrow.com', '$2y$12$bHnaDafVoc4PgSHgvBSZ8ODdnBREyoiQWXk2uDbH3cRSePrOrnK9O', 'admin'),
('Siswa Demo', 'siswa@laborrow.com', '$2y$12$1AW.8VP3i1bIQk8arhsyWe99J0EFnzPEw0NIKCjfcU/Oui0M1XQaO', 'user');
