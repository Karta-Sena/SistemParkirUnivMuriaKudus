-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 22, 2025 at 03:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `umkparkingsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `area_parkir`
--

CREATE TABLE `area_parkir` (
  `id` int(11) NOT NULL,
  `nama_area` varchar(50) NOT NULL,
  `kode_area` varchar(20) NOT NULL,
  `kapasitas_maks` int(11) DEFAULT 24
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `area_parkir`
--

INSERT INTO `area_parkir` (`id`, `nama_area`, `kode_area`, `kapasitas_maks`) VALUES
(1, 'Area A', 'AA', 24),
(2, 'Area B', 'AB', 24),
(3, 'Area C', 'AC', 24);

-- --------------------------------------------------------

--
-- Table structure for table `kendaraan`
--

CREATE TABLE `kendaraan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plat_nomor` varchar(20) NOT NULL,
  `no_stnk` varchar(50) DEFAULT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `warna` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kendaraan`
--

INSERT INTO `kendaraan` (`id`, `user_id`, `plat_nomor`, `no_stnk`, `jenis`, `warna`, `created_at`) VALUES
(1, 8, '1', '123456', '', '', '2025-10-30 15:48:27'),
(2, 9, '2024', '2024', '', '', '2025-11-01 12:09:55'),
(7, 13, '1', '1', '', '', '2025-11-05 13:30:59'),
(8, 14, '1', '1', '', '', '2025-11-05 13:34:08'),
(10, 16, 'B 123 B', '123000123000', '', '', '2025-11-07 05:33:54'),
(23, 19, 'K 1234 AB', '12345678', NULL, NULL, '2025-11-21 03:40:02'),
(28, 20, '3N4K', '1111111111111113', 'motor', NULL, '2025-11-21 05:22:05'),
(29, 18, '123', '123', 'mobil', NULL, '2025-11-21 05:49:48'),
(30, 18, '1234', '1234', 'motor', NULL, '2025-11-21 06:32:23'),
(31, 101, 'K 8888 TES', 'STNK-DUMMY-01', 'mobil', 'Hitam', '2025-11-21 19:07:14'),
(32, 102, 'K 7777 TES', 'STNK-DUMMY-02', 'motor', 'Putih', '2025-11-21 19:07:14'),
(33, 103, 'H 5555 TES', 'STNK-DUMMY-03', 'mobil', 'Silver', '2025-11-21 19:07:14');

-- --------------------------------------------------------

--
-- Table structure for table `log_parkir`
--

CREATE TABLE `log_parkir` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plat_nomor` varchar(20) NOT NULL,
  `area_id` int(11) DEFAULT NULL,
  `slot_id` int(11) DEFAULT NULL,
  `kode_area` varchar(20) DEFAULT NULL,
  `waktu_masuk` datetime NOT NULL DEFAULT current_timestamp(),
  `waktu_keluar` datetime DEFAULT NULL,
  `status` enum('masuk','keluar') NOT NULL DEFAULT 'masuk'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_parkir`
--

INSERT INTO `log_parkir` (`id`, `user_id`, `plat_nomor`, `area_id`, `slot_id`, `kode_area`, `waktu_masuk`, `waktu_keluar`, `status`) VALUES
(1, 18, '1234', 1, 1, 'L2', '2025-11-22 17:54:43', '2025-11-22 17:57:42', 'keluar'),
(2, 18, '1234', 1, 3, 'A1-03', '2025-11-22 17:59:38', '2025-11-22 18:46:22', 'keluar'),
(3, 18, '1234', 1, 5, 'A1-05', '2025-11-22 19:19:37', '2025-11-22 19:47:45', 'keluar'),
(4, 18, '123', 1, 4, 'A1-04', '2025-11-22 19:48:13', '2025-11-22 19:48:43', 'keluar'),
(5, 18, '123', 1, 1, 'A1-01', '2025-11-22 20:50:16', '2025-11-22 20:50:41', 'keluar'),
(6, 18, '1234', 1, 1, 'A1-01', '2025-11-22 20:51:06', NULL, 'masuk');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `type` varchar(50) DEFAULT 'info',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `type`, `created_at`) VALUES
(1, 18, 'Kendaraan 1234 berhasil parkir di Slot A1-01.', 1, 'info', '2025-11-22 17:54:43'),
(2, 18, 'Kendaraan Anda (1234) telah dipindahkan petugas dari Area A1-01 ke Area L2.', 1, 'pindah_area', '2025-11-22 17:55:26'),
(3, 18, 'Kendaraan 1234 telah keluar. Terima kasih.', 1, 'info', '2025-11-22 17:57:42'),
(4, 18, 'Kendaraan 1234 berhasil parkir di Slot A1-01.', 1, 'info', '2025-11-22 17:59:38'),
(5, 18, 'Kendaraan Anda (1234) telah dipindahkan petugas dari Slot A1-01 ke Slot A1-03.', 1, 'pindah_area', '2025-11-22 17:59:58'),
(6, 18, 'Kendaraan 1234 telah keluar. Terima kasih.', 1, 'info', '2025-11-22 18:46:22'),
(7, 18, 'Kendaraan 1234 berhasil parkir di Slot A1-01.', 1, 'info', '2025-11-22 19:19:37'),
(8, 18, 'Kendaraan Anda (1234) telah dipindahkan petugas dari Slot A1-01 ke Slot A1-05.', 1, 'pindah_area', '2025-11-22 19:23:07'),
(9, 18, 'Kendaraan 1234 telah keluar. Terima kasih.', 1, 'info', '2025-11-22 19:47:45'),
(10, 18, 'Kendaraan 123 berhasil parkir di Slot A1-04.', 1, 'info', '2025-11-22 19:48:13'),
(11, 18, 'Kendaraan 123 telah keluar. Terima kasih.', 1, 'info', '2025-11-22 19:48:43'),
(12, 18, 'Kendaraan 123 berhasil parkir di Slot A1-01.', 1, 'info', '2025-11-22 20:50:16'),
(13, 18, 'Kendaraan 123 telah keluar. Terima kasih.', 1, 'info', '2025-11-22 20:50:41'),
(14, 18, 'Kendaraan 1234 berhasil parkir di Slot A3-01.', 1, 'info', '2025-11-22 20:51:06'),
(15, 18, 'Kendaraan Anda (1234) dipindahkan petugas dari A3-01 ke A1-01.', 1, 'pindah_area', '2025-11-22 20:51:26');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(5, 'BotBotBot456654@gmail.com', '2057010a95515cc7d36b406b0483d2ee9d50fa1a13805d0c5ae40b8b02310933', '2025-11-07 00:34:02', '2025-11-07 05:34:02'),
(9, 'usertesting.bydev@gmail.com', '98dae1cefa0a0d3a854416c30b6ff1035cb7d4261d7ea3d29b8777d7662a580b', '2025-11-15 06:53:13', '2025-11-15 11:53:13');

-- --------------------------------------------------------

--
-- Table structure for table `slot_parkir`
--

CREATE TABLE `slot_parkir` (
  `id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL,
  `kode_slot` varchar(10) NOT NULL,
  `jenis_slot` enum('mobil','motor') NOT NULL DEFAULT 'mobil',
  `grid_row` int(11) NOT NULL,
  `grid_col` int(11) NOT NULL,
  `status` enum('tersedia','terisi','rusak') DEFAULT 'tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slot_parkir`
--

INSERT INTO `slot_parkir` (`id`, `area_id`, `kode_slot`, `jenis_slot`, `grid_row`, `grid_col`, `status`) VALUES
(1, 1, 'A1-01', 'mobil', 1, 1, 'terisi'),
(2, 1, 'A1-02', 'mobil', 1, 2, 'tersedia'),
(3, 1, 'A1-03', 'mobil', 1, 4, 'tersedia'),
(4, 1, 'A1-04', 'mobil', 1, 5, 'tersedia'),
(5, 1, 'A1-05', 'mobil', 2, 1, 'tersedia'),
(6, 1, 'A1-06', 'mobil', 2, 2, 'tersedia'),
(7, 1, 'A1-07', 'mobil', 2, 4, 'tersedia'),
(8, 1, 'A1-08', 'mobil', 2, 5, 'tersedia'),
(9, 1, 'A1-09', 'motor', 3, 1, 'tersedia'),
(10, 1, 'A1-10', 'motor', 3, 2, 'tersedia'),
(11, 1, 'A1-11', 'motor', 3, 4, 'tersedia'),
(12, 1, 'A1-12', 'motor', 3, 5, 'tersedia'),
(13, 1, 'A1-13', 'motor', 4, 1, 'tersedia'),
(14, 1, 'A1-14', 'motor', 4, 2, 'tersedia'),
(15, 1, 'A1-15', 'motor', 4, 4, 'tersedia'),
(16, 1, 'A1-16', 'motor', 4, 5, 'tersedia'),
(17, 1, 'A1-17', 'mobil', 5, 1, 'tersedia'),
(18, 1, 'A1-18', 'mobil', 5, 2, 'tersedia'),
(19, 1, 'A1-19', 'mobil', 5, 4, 'tersedia'),
(20, 1, 'A1-20', 'mobil', 5, 5, 'tersedia'),
(21, 1, 'A1-21', 'mobil', 6, 1, 'tersedia'),
(22, 1, 'A1-22', 'mobil', 6, 2, 'tersedia'),
(23, 1, 'A1-23', 'mobil', 6, 4, 'tersedia'),
(24, 1, 'A1-24', 'mobil', 6, 5, 'tersedia'),
(25, 2, 'A2-01', 'mobil', 1, 1, 'tersedia'),
(26, 2, 'A2-02', 'mobil', 1, 2, 'tersedia'),
(27, 2, 'A2-03', 'mobil', 1, 4, 'tersedia'),
(28, 2, 'A2-04', 'mobil', 1, 5, 'tersedia'),
(29, 2, 'A2-05', 'mobil', 2, 1, 'tersedia'),
(30, 2, 'A2-06', 'mobil', 2, 2, 'tersedia'),
(31, 2, 'A2-07', 'mobil', 2, 4, 'tersedia'),
(32, 2, 'A2-08', 'mobil', 2, 5, 'tersedia'),
(33, 2, 'A2-09', 'motor', 3, 1, 'tersedia'),
(34, 2, 'A2-10', 'motor', 3, 2, 'tersedia'),
(35, 2, 'A2-11', 'motor', 3, 4, 'tersedia'),
(36, 2, 'A2-12', 'motor', 3, 5, 'tersedia'),
(37, 2, 'A2-13', 'motor', 4, 1, 'tersedia'),
(38, 2, 'A2-14', 'motor', 4, 2, 'tersedia'),
(39, 2, 'A2-15', 'motor', 4, 4, 'tersedia'),
(40, 2, 'A2-16', 'motor', 4, 5, 'tersedia'),
(41, 2, 'A2-17', 'mobil', 5, 1, 'tersedia'),
(42, 2, 'A2-18', 'mobil', 5, 2, 'tersedia'),
(43, 2, 'A2-19', 'mobil', 5, 4, 'tersedia'),
(44, 2, 'A2-20', 'mobil', 5, 5, 'tersedia'),
(45, 2, 'A2-21', 'mobil', 6, 1, 'tersedia'),
(46, 2, 'A2-22', 'mobil', 6, 2, 'tersedia'),
(47, 2, 'A2-23', 'mobil', 6, 4, 'tersedia'),
(48, 2, 'A2-24', 'mobil', 6, 5, 'tersedia'),
(49, 3, 'A3-01', 'mobil', 1, 1, 'tersedia'),
(50, 3, 'A3-02', 'mobil', 1, 2, 'tersedia'),
(51, 3, 'A3-03', 'mobil', 1, 4, 'tersedia'),
(52, 3, 'A3-04', 'mobil', 1, 5, 'tersedia'),
(53, 3, 'A3-05', 'mobil', 2, 1, 'tersedia'),
(54, 3, 'A3-06', 'mobil', 2, 2, 'tersedia'),
(55, 3, 'A3-07', 'mobil', 2, 4, 'tersedia'),
(56, 3, 'A3-08', 'mobil', 2, 5, 'tersedia'),
(57, 3, 'A3-09', 'motor', 3, 1, 'tersedia'),
(58, 3, 'A3-10', 'motor', 3, 2, 'tersedia'),
(59, 3, 'A3-11', 'motor', 3, 4, 'tersedia'),
(60, 3, 'A3-12', 'motor', 3, 5, 'tersedia'),
(61, 3, 'A3-13', 'motor', 4, 1, 'tersedia'),
(62, 3, 'A3-14', 'motor', 4, 2, 'tersedia'),
(63, 3, 'A3-15', 'motor', 4, 4, 'tersedia'),
(64, 3, 'A3-16', 'motor', 4, 5, 'tersedia'),
(65, 3, 'A3-17', 'mobil', 5, 1, 'tersedia'),
(66, 3, 'A3-18', 'mobil', 5, 2, 'tersedia'),
(67, 3, 'A3-19', 'mobil', 5, 4, 'tersedia'),
(68, 3, 'A3-20', 'mobil', 5, 5, 'tersedia'),
(69, 3, 'A3-21', 'mobil', 6, 1, 'tersedia'),
(70, 3, 'A3-22', 'mobil', 6, 2, 'tersedia'),
(71, 3, 'A3-23', 'mobil', 6, 4, 'tersedia'),
(72, 3, 'A3-24', 'mobil', 6, 5, 'tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('mahasiswa','dosen','tamu','admin','petugas') NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nim` varchar(20) DEFAULT NULL,
  `nidn` varchar(20) DEFAULT NULL,
  `status_parkir` enum('keluar','masuk') NOT NULL DEFAULT 'keluar',
  `keperluan` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `nama`, `email`, `password`, `nim`, `nidn`, `status_parkir`, `keperluan`, `avatar`, `created_at`) VALUES
(8, 'mahasiswa', 'xo', 'xo@gmail.com', '$2y$10$dh.SlxbojQC7mfwEmFzRPuMruMYG/uFwuoSOfmxgSZo14Ue00yQU2', '111', '', 'keluar', NULL, NULL, '2025-10-30 15:48:27'),
(9, 'mahasiswa', 'Ardi', 'ardi@gmail.com', '$2y$10$RvUlUV9wTOwyAboQWxKgkuvni3EXnb4F52wlQ7reAsaDNS79z3FUu', '1', '', 'keluar', NULL, NULL, '2025-11-01 12:09:55'),
(12, 'mahasiswa', 'Mhs', 'mhs@gmail.com', '$2y$10$tvjU2s5HCM3AMSIyFYmr/.Ouw/Sqmal8O392zpH99pPJaTEHiKANa', '1', '', 'keluar', NULL, NULL, '2025-11-05 12:26:59'),
(13, 'dosen', 'Dsn', 'Dsn@gmail.com', '$2y$10$RPI4iwwt0a82Tw8.3dWpReJA7iUNdASn7z4D5FVY1AX3fmUEjDV5y', '', '1', 'keluar', NULL, NULL, '2025-11-05 13:30:59'),
(14, 'tamu', 'Tm', 'Tm@gmail.com', '$2y$10$jgYKdihEySdTxg4BcbzX1uVDj7JsJEaytKioNOnDoLS9CV2z1IyQO', '', '', 'keluar', NULL, NULL, '2025-11-05 13:34:08'),
(16, 'mahasiswa', 'BotBot ', 'BotBotBot456654@gmail.com', '$2y$10$iKmo1L3JIP1COD.lHK430e0dFjXKKusMEACnWKKEMNn0Vo9HaQQLC', '202551100', '', 'keluar', NULL, NULL, '2025-11-07 05:33:54'),
(18, 'mahasiswa', 'User Testing by Developer', 'usertesting.bydev@gmail.com', '$2y$10$ktnJT9AHaNT4ScDvsBvexuDkpfAfTf0A73jc2EH2ihsDdu.2PsqCi', '00000000', '', 'masuk', NULL, 'uploads/avatars/avatar_18_1763622185.jpg', '2025-11-19 11:39:20'),
(19, 'mahasiswa', 'Test Mahasiswa', 'testmhs@umk.ac.id', '$2y$10$P7U2hKNoOW8nbXz6gqqq5uhJyMMl8c5uwIelLsLWPB6X3yNtPEZxS', '202351001', '', 'keluar', NULL, NULL, '2025-11-21 03:40:02'),
(20, 'mahasiswa', 'USERTESTINGV.2', 'usertestingv2@gmail.com', '$2y$10$9AJYc/v01HDNdPt/551FUuFdHhDIlxt1Oj2ZoVVSkAHK/jfDP4Qd6', '20202020', '', 'keluar', NULL, NULL, '2025-11-21 05:18:38'),
(24, 'petugas', 'Karta Sena', 'petugas01', '$2y$10$zG/UdUDUeZl3ZhRQ8CfbAO.oJ6uSMBlbeYqJHCRg1ojsHr3y0oZdm', NULL, NULL, 'keluar', NULL, NULL, '2025-11-21 14:02:47'),
(101, 'mahasiswa', 'Budi Santoso (Dummy)', 'budi.dummy@test.com', '$2y$10$DummyHashPassword123456789', 'DUMMY001', NULL, 'keluar', NULL, NULL, '2025-11-21 19:07:14'),
(102, 'mahasiswa', 'Siti Aminah (Dummy)', 'siti.dummy@test.com', '$2y$10$DummyHashPassword123456789', 'DUMMY002', NULL, 'keluar', NULL, NULL, '2025-11-21 19:07:14'),
(103, 'dosen', 'Dr. Hartono (Dummy)', 'hartono.dummy@test.com', '$2y$10$DummyHashPassword123456789', 'DUMMY003', NULL, 'keluar', NULL, NULL, '2025-11-21 19:07:14'),
(104, 'admin', 'Admin UMK', 'admin@gmail.com', '$2y$10$5lrMJOtDbUqQ3fFRY.CckO.lIT/lVkm1ospArh.G9nz0zhujqkCgS', NULL, NULL, 'keluar', NULL, NULL, '2025-11-22 12:29:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `area_parkir`
--
ALTER TABLE `area_parkir`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `log_parkir`
--
ALTER TABLE `log_parkir`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_log_area` (`area_id`),
  ADD KEY `fk_log_slot` (`slot_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_index` (`email`),
  ADD KEY `token_index` (`token`);

--
-- Indexes for table `slot_parkir`
--
ALTER TABLE `slot_parkir`
  ADD PRIMARY KEY (`id`),
  ADD KEY `area_id` (`area_id`);

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
-- AUTO_INCREMENT for table `area_parkir`
--
ALTER TABLE `area_parkir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `log_parkir`
--
ALTER TABLE `log_parkir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `slot_parkir`
--
ALTER TABLE `slot_parkir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD CONSTRAINT `kendaraan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `log_parkir`
--
ALTER TABLE `log_parkir`
  ADD CONSTRAINT `fk_log_area` FOREIGN KEY (`area_id`) REFERENCES `area_parkir` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_log_slot` FOREIGN KEY (`slot_id`) REFERENCES `slot_parkir` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `log_parkir_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `slot_parkir`
--
ALTER TABLE `slot_parkir`
  ADD CONSTRAINT `slot_parkir_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `area_parkir` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
