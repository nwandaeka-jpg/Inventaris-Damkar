-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 15 Okt 2025 pada 16.17
-- Versi server: 10.1.38-MariaDB
-- Versi PHP: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `damkar_inv`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang`
--

CREATE TABLE `barang` (
  `id` int(10) UNSIGNED NOT NULL,
  `kode_barang` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_id` int(10) UNSIGNED DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT '0',
  `lokasi` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kondisi` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT 'baik',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `barang`
--

INSERT INTO `barang` (`id`, `kode_barang`, `nama`, `kategori_id`, `qty`, `lokasi`, `foto`, `kondisi`, `keterangan`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'A01', 'HT (Handy Talky)', 5, 24, 'Ruang Komunikasi ', './uploads/barang_1760508379.jpeg', '0', '0', 3, '2025-10-15 06:06:19', '2025-10-15 13:23:07'),
(2, 'A02', 'Drone Pengintai', 5, 2, 'Ruang Komunikasi', './uploads/barang_1760509565.jpeg', '0', '0', 1, '2025-10-15 06:26:05', '2025-10-15 14:16:11'),
(3, 'K01', 'Mobil Pemadam', 1, 6, 'Garasi Mobil', './uploads/barang_1760536237.jpeg', 'Baik', 'Siap Digunakan', 3, '2025-10-15 13:50:37', '2025-10-15 13:50:37'),
(4, 'L01', 'Lampu Sorot', 6, 2, 'Ruang APD lantai 2', './uploads/barang_1760536417.jpeg', 'Baik', 'AMAN SIAP DIGUNAKAN', 3, '2025-10-15 13:53:37', '2025-10-15 14:13:41'),
(5, 'L02', 'Genset', 6, 6, 'Ruang APD lantai 2', './uploads/barang_1760536502.jpeg', 'Baik', 'Siap digunakan', 3, '2025-10-15 13:55:02', '2025-10-15 13:55:02'),
(6, 'P01', 'Baju Tahan Api', 4, 24, 'Ruang APD lantai 2', './uploads/barang_1760536689.jpeg', 'Baik', 'baju pemadam ', 3, '2025-10-15 13:58:09', '2025-10-15 13:58:09'),
(7, 'P02', 'Helm', 4, 24, 'Ruang APD lantai 2', './uploads/barang_1760536764.jpeg', 'Baik', 'Helm pemadam kebakaran', 3, '2025-10-15 13:59:24', '2025-10-15 13:59:24'),
(8, 'P03', 'Sepatu', 4, 24, 'Ruang APD lantai 2', './uploads/barang_1760536876.jpeg', 'Baik', 'Sepatu Pemadam Kebakaran', 3, '2025-10-15 14:01:16', '2025-10-15 14:01:16'),
(9, 'P04', 'Sarung Tangan', 4, 24, 'Ruang APD lantai 2', './uploads/barang_1760536932.jpeg', 'Baik', 'Sarung tangan pemadam kebakaran', 3, '2025-10-15 14:02:12', '2025-10-15 14:02:12'),
(10, 'P05', 'Masker SCBA', 4, 24, 'Ruang APD lantai 2', './uploads/barang_1760537001.jpeg', 'Baik', 'Masker Siap digunakan', 3, '2025-10-15 14:03:21', '2025-10-15 14:03:21'),
(11, 'PP01', 'Selang', 2, 5, '	Gudang Peralatan Utama (Rak B-1)', './uploads/barang_1760537175.jpeg', 'Baik', 'Selang', 1, '2025-10-15 14:06:15', '2025-10-15 14:14:29'),
(12, 'PP02', 'Nozzle Multi Fungsi', 2, 6, 'Mobil Pompa (Engine) PS-01', './uploads/barang_1760537257.jpeg', 'Perlu Perbaikan', 'Perlu perawatan', 3, '2025-10-15 14:07:37', '2025-10-15 14:07:37'),
(13, 'S01', 'Hydraulic Spreader (Pembuka)', 3, 5, 'Rescue Truck (Kompartemen Samping)', './uploads/barang_1760537376.jpeg', 'Rusak', 'Digunakan untuk operasi penyelamatan laka lantas.', 1, '2025-10-15 14:09:36', '2025-10-15 14:15:17'),
(14, 'S02', 'Tali Kernmantel Statis (50m)', 3, 6, 'Pos Sektor Utara (Lemari Besi No. 3)', './uploads/barang_1760537459.jpeg', 'Baik', 'Tahan beban 1000 kg, dicatat per meter.', 3, '2025-10-15 14:10:59', '2025-10-15 14:10:59');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama`) VALUES
(5, 'Alat Komunikasi'),
(1, 'Kendaraan Operasional'),
(6, 'Logistik dan Perlengkapan'),
(4, 'Peralatan Pelindung Diri (APD)'),
(2, 'Peralatan Pemadaman'),
(3, 'Peralatan Penyelamatan (SAR)');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(10) UNSIGNED NOT NULL,
  `barang_id` int(10) UNSIGNED NOT NULL,
  `tipe` enum('masuk','keluar') COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty` int(11) NOT NULL,
  `tanggal` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id`, `barang_id`, `tipe`, `qty`, `tanggal`, `user_id`, `keterangan`) VALUES
(1, 1, 'masuk', 24, '2025-10-15 13:06:19', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(2, 1, 'keluar', 4, '2025-10-15 13:21:26', 3, 'Digunakan dalam operasi SAR'),
(3, 2, 'masuk', 5, '2025-10-15 13:26:05', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(4, 1, 'masuk', 4, '2025-10-15 19:35:13', 3, 'Selesai digunakan dalam operasi SAR'),
(5, 3, 'masuk', 6, '2025-10-15 20:50:37', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(6, 4, 'masuk', 24, '2025-10-15 20:53:37', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(7, 5, 'masuk', 6, '2025-10-15 20:55:02', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(8, 6, 'masuk', 24, '2025-10-15 20:58:09', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(9, 7, 'masuk', 24, '2025-10-15 20:59:24', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(10, 8, 'masuk', 24, '2025-10-15 21:01:16', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(11, 9, 'masuk', 24, '2025-10-15 21:02:12', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(12, 10, 'masuk', 24, '2025-10-15 21:03:21', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(13, 11, 'masuk', 6, '2025-10-15 21:06:15', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(14, 12, 'masuk', 6, '2025-10-15 21:07:37', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(15, 13, 'masuk', 3, '2025-10-15 21:09:36', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(16, 14, 'masuk', 6, '2025-10-15 21:10:59', 3, 'Stok awal ditambahkan saat registrasi barang.'),
(17, 11, 'keluar', 5, '2025-10-15 21:12:44', 3, 'BUTUH PERBAIKAN!!!'),
(18, 4, 'keluar', 22, '2025-10-15 21:13:41', 3, 'Rusak '),
(19, 11, 'masuk', 4, '2025-10-15 21:14:29', 1, 'Sudah Bagus'),
(20, 13, 'masuk', 2, '2025-10-15 21:15:17', 1, 'Sudah Mendapatkan Produk Baru'),
(21, 2, 'keluar', 3, '2025-10-15 21:16:11', 1, 'Kecelakaan Dalam Uji Coba');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','petugas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'petugas',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama`, `role`, `created_at`) VALUES
(1, 'ipanro123', '$2y$10$MKf4cZLVhKPECUGePT8qU.uhlqLkFidXeU72ztCN1YkWjLAg.p1li', 'Ipanro Gilbert Silaban', 'petugas', '2025-10-14 07:43:10'),
(3, 'admin12345', '$2y$10$glL9WVLKdRV1D1HtcqcFgefE5schIbw4do8PwulAC0.MZ5aY75vp6', 'AdminFireTrack', 'admin', '2025-10-15 04:23:28'),
(4, 'Budi01', '$2y$10$1XpU5fJ2.Y9EbE0/fCA5YuZYrFElzYSyQobaIolT7fpavV2c5.M92', 'Budi Saptadji', 'petugas', '2025-10-15 06:01:32'),
(5, 'Kaptenagus', '$2y$10$4wacVcz4Oy..o/FNk29up.73NRjA9NmtzQ.BrCH/avUtIZk92kWa6', 'Agus Purnomo Sari', 'petugas', '2025-10-15 06:01:59'),
(6, 'Ferdinan09', '$2y$10$K9siTOt3JLFeBaS/zVLl0umzOUXoyQUzdBpYgE7Vd115nE9W0jkfm', 'Ferdinan Saragih', 'petugas', '2025-10-15 13:47:12');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_barang` (`kode_barang`),
  ADD KEY `fk_barang_kategori` (`kategori_id`),
  ADD KEY `fk_barang_updatedby` (`updated_by`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama` (`nama`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transaksi_user` (`user_id`),
  ADD KEY `idx_transaksi_barang` (`barang_id`),
  ADD KEY `idx_transaksi_tanggal` (`tanggal`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `fk_barang_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_barang_updatedby` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_transaksi_barang` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
