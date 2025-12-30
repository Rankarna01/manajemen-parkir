-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 28, 2025 at 03:32 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `parkir-testing`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pos_id` int(11) NOT NULL,
  `waktu_masuk` datetime DEFAULT current_timestamp(),
  `waktu_keluar` datetime DEFAULT NULL,
  `koordinat_masuk` varchar(100) DEFAULT NULL,
  `koordinat_keluar` varchar(100) DEFAULT NULL,
  `status` enum('hadir','pulang') DEFAULT 'hadir'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `user_id`, `pos_id`, `waktu_masuk`, `waktu_keluar`, `koordinat_masuk`, `koordinat_keluar`, `status`) VALUES
(1, 2, 2, '2025-12-26 18:24:33', '2025-12-26 18:29:24', '3.555,98.6331', '3.5848192,98.6775552', 'pulang'),
(2, 2, 3, '2025-12-28 20:59:51', '2025-12-28 21:00:02', '3.5848192,98.6775552', '3.5848192,98.6775552', 'pulang'),
(3, 4, 2, '2025-12-28 21:22:44', NULL, '3.5848192,98.6775552', NULL, 'hadir');

-- --------------------------------------------------------

--
-- Table structure for table `jenis_kendaraan`
--

CREATE TABLE `jenis_kendaraan` (
  `id` int(11) NOT NULL,
  `nama_jenis` varchar(50) NOT NULL,
  `tarif_per_jam` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jenis_kendaraan`
--

INSERT INTO `jenis_kendaraan` (`id`, `nama_jenis`, `tarif_per_jam`) VALUES
(1, 'Motor', 2000.00),
(2, 'Mobil', 5000.00);

-- --------------------------------------------------------

--
-- Table structure for table `kendaraan`
--

CREATE TABLE `kendaraan` (
  `id` int(11) NOT NULL,
  `plat_nomor` varchar(20) NOT NULL,
  `jenis` enum('mobil','motor') NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kendaraan`
--

INSERT INTO `kendaraan` (`id`, `plat_nomor`, `jenis`, `foto`, `created_at`) VALUES
(1, 'BK 4032', 'mobil', NULL, '2025-10-18 21:00:34'),
(2, 'BK 2010', 'motor', NULL, '2025-10-18 22:37:18'),
(3, 'BK 1931', 'motor', NULL, '2025-10-18 22:44:58'),
(4, 'BK 1932', 'mobil', NULL, '2025-10-18 22:47:50'),
(5, 'BK1234', 'motor', NULL, '2025-10-18 22:50:17'),
(6, 'BK2341', 'motor', NULL, '2025-10-19 19:21:48'),
(7, 'DD2010', 'mobil', NULL, '2025-10-20 20:19:19'),
(8, 'BK 4548', 'motor', NULL, '2025-11-01 23:27:05'),
(9, '1002BK', 'motor', NULL, '2025-11-01 23:27:22'),
(10, 'BK 4547', 'motor', NULL, '2025-11-01 23:42:55'),
(11, 'BK5050', 'motor', NULL, '2025-11-16 12:39:21'),
(12, 'ENTRY-2352-17', 'mobil', NULL, '2025-12-05 23:52:49'),
(13, 'ENTRY-2352-43', 'mobil', NULL, '2025-12-05 23:52:58'),
(14, 'ENTRY-0007-65', 'mobil', NULL, '2025-12-10 00:07:48'),
(15, 'MTR-1707-12', 'motor', NULL, '2025-12-11 17:07:51'),
(16, 'MTR-1719-82', 'mobil', NULL, '2025-12-11 17:19:28'),
(17, 'MTR-2344-92', 'mobil', NULL, '2025-12-13 23:44:07'),
(18, 'MTR-0606-17', 'mobil', NULL, '2025-12-16 06:06:10'),
(19, 'MTR-0606-37', 'mobil', NULL, '2025-12-16 06:06:31'),
(20, 'MTR-0616-37', 'motor', NULL, '2025-12-16 06:16:07'),
(21, 'MTR-1429-40', 'motor', NULL, '2025-12-17 14:29:53'),
(22, 'MTR-1828-26', 'motor', NULL, '2025-12-26 18:28:36');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_keuangan`
--

CREATE TABLE `laporan_keuangan` (
  `id` int(11) NOT NULL,
  `periode` varchar(50) NOT NULL,
  `total_pendapatan` decimal(10,2) NOT NULL,
  `total_pengeluaran` decimal(10,2) NOT NULL,
  `laba_bersih` decimal(10,2) NOT NULL,
  `tanggal_dibuat` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_keuangan`
--

INSERT INTO `laporan_keuangan` (`id`, `periode`, `total_pendapatan`, `total_pengeluaran`, `laba_bersih`, `tanggal_dibuat`) VALUES
(1, 'October 2025', 0.00, 0.00, 0.00, '2025-11-24 19:51:26'),
(2, 'November 2025', 0.00, 0.00, 0.00, '2025-11-24 19:51:36');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan_sistem`
--

CREATE TABLE `pengaturan_sistem` (
  `id` int(11) NOT NULL,
  `nama_pos` varchar(100) DEFAULT 'Pos Utama',
  `nama_instansi` varchar(100) DEFAULT 'RSIA ANANDA MAKASSAR',
  `alamat_instansi` varchar(255) DEFAULT 'Jl. Andi Djemma No.57',
  `footer_struk` varchar(100) DEFAULT 'Terima Kasih',
  `ip_kamera_masuk` varchar(50) DEFAULT '192.168.1.101',
  `ip_kamera_keluar` varchar(50) DEFAULT '192.168.1.102',
  `ip_palang_masuk` varchar(50) DEFAULT '192.168.1.105/open',
  `ip_palang_keluar` varchar(50) DEFAULT '192.168.1.106/open',
  `ip_printer` varchar(50) DEFAULT '192.168.1.200/print',
  `tipe_pos` enum('mobil','motor') DEFAULT 'mobil',
  `latitude` varchar(50) DEFAULT '-6.200000',
  `longitude` varchar(50) DEFAULT '106.816666'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan_sistem`
--

INSERT INTO `pengaturan_sistem` (`id`, `nama_pos`, `nama_instansi`, `alamat_instansi`, `footer_struk`, `ip_kamera_masuk`, `ip_kamera_keluar`, `ip_palang_masuk`, `ip_palang_keluar`, `ip_printer`, `tipe_pos`, `latitude`, `longitude`) VALUES
(2, 'Pos Masuk Motor', 'RSIA ANANDA MAKASSAR', 'Jl. Andi Djemma No.57', 'Terima Kasih', '192.168.1.101', '192.168.1.102', '192.168.1.105/open', '192.168.1.106/open', '192.168.1.200/print', 'motor', '-6.200000', '106.816666'),
(3, 'Pos Masuk Mobil', 'RSIA ANANDA MAKASSAR', 'Jl. Andi Djemma No.57', 'Terima Kasih', '192.168.1.102', '192.168.1.102', '192.168.1.106/open', '192.168.1.106/open', '192.168.1.200/print', 'mobil', '-6.200000', '106.816666');

-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran_operasional`
--

CREATE TABLE `pengeluaran_operasional` (
  `id` int(11) NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `tanggal` date NOT NULL,
  `dibuat_oleh` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tarif_parkir`
--

CREATE TABLE `tarif_parkir` (
  `id` int(11) NOT NULL,
  `jenis_kendaraan` enum('mobil','motor') NOT NULL,
  `tarif_per_jam` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tarif_flat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tarif_parkir`
--

INSERT INTO `tarif_parkir` (`id`, `jenis_kendaraan`, `tarif_per_jam`, `tarif_flat`, `updated_at`) VALUES
(1, 'motor', 2000.00, 0.00, '2025-10-18 17:13:43'),
(2, 'mobil', 5000.00, 0.00, '2025-12-09 23:45:20');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_parkir`
--

CREATE TABLE `transaksi_parkir` (
  `id` int(11) NOT NULL,
  `id_kendaraan` int(11) NOT NULL,
  `kode_barcode` varchar(50) NOT NULL,
  `waktu_masuk` datetime NOT NULL,
  `waktu_keluar` datetime DEFAULT NULL,
  `biaya` decimal(10,2) DEFAULT NULL,
  `status` enum('masuk','keluar') NOT NULL DEFAULT 'masuk',
  `foto_masuk` varchar(255) DEFAULT NULL,
  `foto_keluar` varchar(255) DEFAULT NULL,
  `id_petugas_masuk` int(11) NOT NULL,
  `id_petugas_keluar` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi_parkir`
--

INSERT INTO `transaksi_parkir` (`id`, `id_kendaraan`, `kode_barcode`, `waktu_masuk`, `waktu_keluar`, `biaya`, `status`, `foto_masuk`, `foto_keluar`, `id_petugas_masuk`, `id_petugas_keluar`, `created_at`) VALUES
(1, 1, 'PK-1760796034', '2025-10-18 21:00:34', '2025-10-18 22:57:42', 10000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-18 21:00:34'),
(2, 2, 'PK-1760801838', '2025-10-18 22:37:18', '2025-10-19 19:23:11', 42000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-18 22:37:18'),
(3, 3, 'PK-1760802298', '2025-10-18 22:44:58', '2025-10-19 19:22:58', 42000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-18 22:44:58'),
(4, 4, 'PK-20251018-00004', '2025-10-18 22:47:50', '2025-10-19 19:22:18', 105000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-18 22:47:50'),
(5, 5, 'PK-20251018-00005', '2025-10-18 22:50:17', '2025-10-18 22:50:33', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-18 22:50:17'),
(6, 6, 'PK-20251019-00006', '2025-10-19 19:21:48', '2025-10-19 19:23:21', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-19 19:21:48'),
(7, 7, 'PK-20251020-00007', '2025-10-20 20:19:19', '2025-10-20 20:20:30', 5000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-20 20:19:19'),
(8, 8, 'PK-20251101-00008', '2025-11-01 23:27:05', '2025-11-24 19:27:12', 1098000.00, 'keluar', NULL, NULL, 2, 2, '2025-11-01 23:27:05'),
(9, 9, 'PK-20251101-00009', '2025-11-01 23:27:22', '2025-11-01 23:28:25', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-11-01 23:27:22'),
(10, 10, 'PK-20251101-00010', '2025-11-01 23:42:55', '2025-11-24 19:27:00', 1096000.00, 'keluar', NULL, NULL, 2, 2, '2025-11-01 23:42:55'),
(11, 11, 'PK-20251116-00011', '2025-11-16 12:39:21', '2025-11-16 12:40:46', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-11-16 12:39:21'),
(12, 1, 'PK-20251118-00012', '2025-11-18 13:59:26', '2025-11-18 14:00:20', 5000.00, 'keluar', NULL, NULL, 2, 2, '2025-11-18 13:59:26'),
(13, 12, 'PK-20251205-00013', '2025-12-05 23:52:49', NULL, NULL, 'masuk', NULL, NULL, 2, NULL, '2025-12-05 23:52:49'),
(14, 13, 'PK-20251205-00014', '2025-12-05 23:52:58', NULL, NULL, 'masuk', NULL, NULL, 2, NULL, '2025-12-05 23:52:58'),
(15, 14, 'PK-20251210-00015', '2025-12-10 00:07:48', NULL, NULL, 'masuk', NULL, NULL, 2, NULL, '2025-12-10 00:07:48'),
(16, 15, 'PK-MTR-20251211-00016', '2025-12-11 17:07:51', '2025-12-11 17:08:13', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-12-11 17:07:51'),
(17, 16, 'PK-MTR-20251211-00017', '2025-12-11 17:19:28', '2025-12-11 17:19:47', 5000.00, 'keluar', NULL, NULL, 2, 2, '2025-12-11 17:19:28'),
(18, 17, 'PK-MTR-20251213-00018', '2025-12-13 23:44:07', NULL, NULL, 'masuk', NULL, NULL, 2, NULL, '2025-12-13 23:44:07'),
(19, 18, 'PK-MTR-20251216-00019', '2025-12-16 06:06:10', NULL, NULL, 'masuk', NULL, NULL, 2, NULL, '2025-12-16 06:06:10'),
(20, 19, 'PK-MTR-20251216-00020', '2025-12-16 06:06:31', NULL, NULL, 'masuk', NULL, NULL, 2, NULL, '2025-12-16 06:06:31'),
(21, 20, 'PK-MTR-20251216-00021', '2025-12-16 06:16:07', '2025-12-16 06:17:23', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-12-16 06:16:07'),
(22, 21, 'PK-MTR-20251217-00022', '2025-12-17 14:29:53', NULL, NULL, 'masuk', NULL, NULL, 2, NULL, '2025-12-17 14:29:53'),
(23, 22, 'PK-MTR-20251226-00023', '2025-12-26 18:28:36', '2025-12-26 18:29:05', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-12-26 18:28:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','pekerja') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_pos_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`, `updated_at`, `assigned_pos_id`) VALUES
(1, 'Admin Owner', 'owner@parkir.com', '$2y$10$ApD/.sHlVzNS4eNMVMlEgePhrwRFtmCN90Ek2Mrh2Yyz5MjLrvhcG', 'owner', '2025-10-18 17:13:43', '2025-10-18 17:18:33', NULL),
(2, 'Budi Pekerjaa', 'pekerja@parkir.com', '$2y$10$Bqjj4NFIEjvxsViyHGcu2Ohz5X/q2H8lMilGJERx0OTElfC0nZzWm', 'pekerja', '2025-10-18 17:13:43', '2025-12-09 23:40:56', NULL),
(3, 'jaya', 'jaya@parkir.gmail', '$2y$10$dbstPYccBKNegK2BVO8vbe4wKLXIU5lpohJTY9yWeSamRO1N9iTMO', 'pekerja', '2025-11-18 19:20:53', '2025-11-18 19:20:53', NULL),
(4, 'randy', 'randy@parkir.com', '$2y$10$0FbeQrLBYkrJB7rke.8dyemPfn8oKJWrNXZBp.TdPbnLvNxt94OoO', 'pekerja', '2025-12-22 09:01:39', '2025-12-22 09:01:39', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `pos_id` (`pos_id`);

--
-- Indexes for table `jenis_kendaraan`
--
ALTER TABLE `jenis_kendaraan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plat_nomor` (`plat_nomor`);

--
-- Indexes for table `laporan_keuangan`
--
ALTER TABLE `laporan_keuangan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengaturan_sistem`
--
ALTER TABLE `pengaturan_sistem`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengeluaran_operasional`
--
ALTER TABLE `pengeluaran_operasional`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dibuat_oleh` (`dibuat_oleh`);

--
-- Indexes for table `tarif_parkir`
--
ALTER TABLE `tarif_parkir`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jenis_kendaraan` (`jenis_kendaraan`);

--
-- Indexes for table `transaksi_parkir`
--
ALTER TABLE `transaksi_parkir`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_barcode` (`kode_barcode`),
  ADD KEY `id_kendaraan` (`id_kendaraan`),
  ADD KEY `id_petugas_masuk` (`id_petugas_masuk`),
  ADD KEY `id_petugas_keluar` (`id_petugas_keluar`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jenis_kendaraan`
--
ALTER TABLE `jenis_kendaraan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `laporan_keuangan`
--
ALTER TABLE `laporan_keuangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pengaturan_sistem`
--
ALTER TABLE `pengaturan_sistem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pengeluaran_operasional`
--
ALTER TABLE `pengeluaran_operasional`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tarif_parkir`
--
ALTER TABLE `tarif_parkir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaksi_parkir`
--
ALTER TABLE `transaksi_parkir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`pos_id`) REFERENCES `pengaturan_sistem` (`id`);

--
-- Constraints for table `pengeluaran_operasional`
--
ALTER TABLE `pengeluaran_operasional`
  ADD CONSTRAINT `pengeluaran_operasional_ibfk_1` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`);

--
-- Constraints for table `transaksi_parkir`
--
ALTER TABLE `transaksi_parkir`
  ADD CONSTRAINT `transaksi_parkir_ibfk_1` FOREIGN KEY (`id_kendaraan`) REFERENCES `kendaraan` (`id`),
  ADD CONSTRAINT `transaksi_parkir_ibfk_2` FOREIGN KEY (`id_petugas_masuk`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transaksi_parkir_ibfk_3` FOREIGN KEY (`id_petugas_keluar`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
