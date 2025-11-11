-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 07 Nov 2025 pada 06.41
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project_rpl`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `area_parkir`
--

CREATE TABLE `area_parkir` (
  `id` int(11) NOT NULL,
  `kode_area` varchar(20) NOT NULL,
  `status` enum('kosong','terisi') DEFAULT 'kosong',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kendaraan`
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
-- Dumping data untuk tabel `kendaraan`
--

INSERT INTO `kendaraan` (`id`, `user_id`, `plat_nomor`, `no_stnk`, `jenis`, `warna`, `created_at`) VALUES
(1, 8, '1', '123456', '', '', '2025-10-30 15:48:27'),
(2, 9, '2024', '2024', '', '', '2025-11-01 12:09:55'),
(7, 13, '1', '1', '', '', '2025-11-05 13:30:59'),
(8, 14, '1', '1', '', '', '2025-11-05 13:34:08'),
(9, 15, 'U 1212 T', '120013001400', '', '', '2025-11-06 17:02:19'),
(10, 16, 'B 123 B', '123000123000', '', '', '2025-11-07 05:33:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_parkir`
--

CREATE TABLE `log_parkir` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plat_nomor` varchar(20) NOT NULL,
  `waktu_masuk` datetime NOT NULL DEFAULT current_timestamp(),
  `waktu_keluar` datetime DEFAULT NULL,
  `status` enum('masuk','keluar') NOT NULL DEFAULT 'masuk'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(5, 'BotBotBot456654@gmail.com', '2057010a95515cc7d36b406b0483d2ee9d50fa1a13805d0c5ae40b8b02310933', '2025-11-07 00:34:02', '2025-11-07 05:34:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('mahasiswa','dosen','tamu','admin','petugas') NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nim` varchar(20) DEFAULT NULL,
  `nidn` varchar(20) DEFAULT NULL,
  `keperluan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `role`, `nama`, `email`, `password`, `nim`, `nidn`, `keperluan`, `created_at`) VALUES
(8, 'mahasiswa', 'xo', 'xo@gmail.com', '$2y$10$dh.SlxbojQC7mfwEmFzRPuMruMYG/uFwuoSOfmxgSZo14Ue00yQU2', '111', '', NULL, '2025-10-30 15:48:27'),
(9, 'mahasiswa', 'Ardi', 'ardi@gmail.com', '$2y$10$RvUlUV9wTOwyAboQWxKgkuvni3EXnb4F52wlQ7reAsaDNS79z3FUu', '1', '', NULL, '2025-11-01 12:09:55'),
(12, 'mahasiswa', 'Mhs', 'mhs@gmail.com', '$2y$10$tvjU2s5HCM3AMSIyFYmr/.Ouw/Sqmal8O392zpH99pPJaTEHiKANa', '1', '', NULL, '2025-11-05 12:26:59'),
(13, 'dosen', 'Dsn', 'Dsn@gmail.com', '$2y$10$RPI4iwwt0a82Tw8.3dWpReJA7iUNdASn7z4D5FVY1AX3fmUEjDV5y', '', '1', NULL, '2025-11-05 13:30:59'),
(14, 'tamu', 'Tm', 'Tm@gmail.com', '$2y$10$jgYKdihEySdTxg4BcbzX1uVDj7JsJEaytKioNOnDoLS9CV2z1IyQO', '', '', NULL, '2025-11-05 13:34:08'),
(15, 'mahasiswa', 'User Testing', 'usertesting.bydev@gmail.com', '$2y$10$/qm4suYaR2Ixw0lgcmW.N.aZsOigjVN18wAM5Kdf8Uu5chk4GG0Ia', '202451100', '', NULL, '2025-11-06 17:02:19'),
(16, 'mahasiswa', 'BotBot ', 'BotBotBot456654@gmail.com', '$2y$10$iKmo1L3JIP1COD.lHK430e0dFjXKKusMEACnWKKEMNn0Vo9HaQQLC', '202551100', '', NULL, '2025-11-07 05:33:54');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `area_parkir`
--
ALTER TABLE `area_parkir`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `log_parkir`
--
ALTER TABLE `log_parkir`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_index` (`email`),
  ADD KEY `token_index` (`token`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `area_parkir`
--
ALTER TABLE `area_parkir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `log_parkir`
--
ALTER TABLE `log_parkir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD CONSTRAINT `kendaraan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `log_parkir`
--
ALTER TABLE `log_parkir`
  ADD CONSTRAINT `log_parkir_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
