-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 03, 2026 at 02:00 AM
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
-- Database: `db_pengajuan_dana_v2`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_pembelian`
--

CREATE TABLE `detail_pembelian` (
  `id` int(11) NOT NULL,
  `laporan_id` int(11) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `divisi`
--

CREATE TABLE `divisi` (
  `id` int(11) NOT NULL,
  `nama_divisi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `divisi`
--

INSERT INTO `divisi` (`id`, `nama_divisi`) VALUES
(1, 'Finance'),
(2, 'PPIC'),
(3, 'RND'),
(4, 'QC'),
(5, 'Tehnik'),
(6, 'Produksi'),
(7, 'HRD');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_pembelian`
--

CREATE TABLE `laporan_pembelian` (
  `id` int(11) NOT NULL,
  `pengajuan_id` int(11) NOT NULL,
  `total_pemakaian` decimal(15,2) NOT NULL,
  `bukti_nota` varchar(255) NOT NULL,
  `tanggal_laporan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan`
--

CREATE TABLE `pengajuan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `jenis_pengajuan` enum('Non-PPN','PPN') NOT NULL,
  `nama_vendor` varchar(150) NOT NULL,
  `biaya_diajukan` decimal(15,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `rekening_tujuan` varchar(255) NOT NULL,
  `file_penawaran` varchar(255) DEFAULT NULL,
  `status_manager` enum('Pending','Disetujui','Ditolak','Revisi') DEFAULT 'Pending',
  `status_finance` varchar(50) DEFAULT 'Pending',
  `status_dirut` enum('Pending','Disetujui','Ditolak') DEFAULT 'Pending',
  `status_dirkeu` enum('Pending','Disetujui','Ditolak') DEFAULT 'Pending',
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `tanggal_transfer` datetime DEFAULT NULL,
  `rincian_pembelian` text DEFAULT NULL,
  `bukti_nota` text DEFAULT NULL,
  `status_laporan` enum('Belum Lapor','Menunggu Validasi','Valid','Revisi') DEFAULT 'Belum Lapor',
  `catatan_revisi` text DEFAULT NULL,
  `realisasi_dana` decimal(15,2) DEFAULT NULL,
  `status_keseluruhan` varchar(50) DEFAULT 'Pending',
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_persetujuan`
--

CREATE TABLE `riwayat_persetujuan` (
  `id` int(11) NOT NULL,
  `pengajuan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tahap_persetujuan` enum('Finance','Dirut','Dirkeu') NOT NULL,
  `status_persetujuan` enum('Disetujui','Ditolak') NOT NULL,
  `catatan` text DEFAULT NULL,
  `tanggal_proses` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nama_role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `nama_role`) VALUES
(1, 'admin'),
(2, 'spv'),
(3, 'finance'),
(4, 'dirut'),
(5, 'dirkeu'),
(6, 'manager');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `divisi_id` int(11) DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role_id`, `divisi_id`, `otp_code`, `otp_expiry`) VALUES
(6, 'Super Administrator', 'admin@TLP.co.id', '$2y$10$dU27Pq96ii1nvXfidTbwmuIOKtxyjcuclp3yMXKpeCUkMLs14O83u', 1, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_id` (`laporan_id`);

--
-- Indexes for table `divisi`
--
ALTER TABLE `divisi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `laporan_pembelian`
--
ALTER TABLE `laporan_pembelian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengajuan_id` (`pengajuan_id`);

--
-- Indexes for table `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `riwayat_persetujuan`
--
ALTER TABLE `riwayat_persetujuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengajuan_id` (`pengajuan_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `divisi_id` (`divisi_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `divisi`
--
ALTER TABLE `divisi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `laporan_pembelian`
--
ALTER TABLE `laporan_pembelian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengajuan`
--
ALTER TABLE `pengajuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `riwayat_persetujuan`
--
ALTER TABLE `riwayat_persetujuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD CONSTRAINT `detail_pembelian_ibfk_1` FOREIGN KEY (`laporan_id`) REFERENCES `laporan_pembelian` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `laporan_pembelian`
--
ALTER TABLE `laporan_pembelian`
  ADD CONSTRAINT `laporan_pembelian_ibfk_1` FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD CONSTRAINT `pengajuan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `riwayat_persetujuan`
--
ALTER TABLE `riwayat_persetujuan`
  ADD CONSTRAINT `riwayat_persetujuan_ibfk_1` FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `riwayat_persetujuan_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`divisi_id`) REFERENCES `divisi` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
