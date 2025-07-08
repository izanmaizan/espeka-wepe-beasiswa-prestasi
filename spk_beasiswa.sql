-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 07, 2025 at 04:27 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spk_beasiswa`
--

-- --------------------------------------------------------

--
-- Table structure for table `hasil_perhitungan`
--

CREATE TABLE `hasil_perhitungan` (
  `id` int NOT NULL,
  `siswa_id` int NOT NULL,
  `skor_s` decimal(15,8) NOT NULL,
  `skor_v` decimal(15,8) NOT NULL,
  `ranking` int NOT NULL,
  `tahun_ajaran` varchar(10) NOT NULL,
  `tanggal_hitung` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kriteria`
--

CREATE TABLE `kriteria` (
  `id` int NOT NULL,
  `kode` varchar(10) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `bobot` decimal(5,4) NOT NULL,
  `jenis` enum('benefit','cost') DEFAULT 'benefit',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kriteria`
--

INSERT INTO `kriteria` (`id`, `kode`, `nama`, `bobot`, `jenis`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 'C1', 'Nilai Rata-rata Rapor', 0.2500, 'benefit', 'Nilai rata-rata rapor semester terakhir', '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(2, 'C2', 'Prestasi Akademik', 0.2000, 'benefit', 'Jumlah prestasi akademik yang diraih', '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(3, 'C3', 'Prestasi Non-Akademik', 0.1500, 'benefit', 'Jumlah prestasi non-akademik yang diraih', '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(4, 'C4', 'Kedisiplinan', 0.2000, 'benefit', 'Tingkat kedisiplinan siswa (1-100)', '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(5, 'C5', 'Kondisi Ekonomi Keluarga', 0.2000, 'cost', 'Penghasilan orang tua per bulan (dalam jutaan)', '2025-07-07 10:04:53', '2025-07-07 10:04:53');

-- --------------------------------------------------------

--
-- Table structure for table `penilaian`
--

CREATE TABLE `penilaian` (
  `id` int NOT NULL,
  `siswa_id` int NOT NULL,
  `kriteria_id` int NOT NULL,
  `nilai` decimal(8,2) NOT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penilaian`
--

INSERT INTO `penilaian` (`id`, `siswa_id`, `kriteria_id`, `nilai`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 85.50, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(2, 1, 2, 3.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(3, 1, 3, 2.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(4, 1, 4, 90.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(5, 1, 5, 2.50, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(6, 2, 1, 88.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(7, 2, 2, 5.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(8, 2, 3, 4.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(9, 2, 4, 95.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(10, 2, 5, 1.80, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(11, 3, 1, 82.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(12, 3, 2, 2.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(13, 3, 3, 1.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(14, 3, 4, 85.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(15, 3, 5, 3.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(16, 4, 1, 90.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(17, 4, 2, 4.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(18, 4, 3, 3.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(19, 4, 4, 88.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(20, 4, 5, 2.20, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(21, 5, 1, 86.50, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(22, 5, 2, 3.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(23, 5, 3, 2.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(24, 5, 4, 92.00, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(25, 5, 5, 2.80, NULL, '2025-07-07 10:04:53', '2025-07-07 10:04:53');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id` int NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(10) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `alamat` text,
  `no_hp` varchar(15) DEFAULT NULL,
  `tahun_ajaran` varchar(10) NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `nis`, `nama`, `kelas`, `jenis_kelamin`, `alamat`, `no_hp`, `tahun_ajaran`, `status`, `created_at`, `updated_at`) VALUES
(1, '2023001', 'Ahmad Rizky Pratama', '8A', 'L', 'Jl. Merdeka No. 15, Ampek Angkek', '081234567890', '2023/2024', 'aktif', '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(2, '2023002', 'Siti Nurhaliza', '8B', 'P', 'Jl. Sudirman No. 22, Ampek Angkek', '081234567891', '2023/2024', 'aktif', '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(3, '2023003', 'Budi Santoso', '8A', 'L', 'Jl. Ahmad Yani No. 8, Ampek Angkek', '081234567892', '2023/2024', 'aktif', '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(4, '2023004', 'Dewi Sartika', '8C', 'P', 'Jl. Diponegoro No. 12, Ampek Angkek', '081234567893', '2023/2024', 'aktif', '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(5, '2023005', 'Randi Firmansyah', '8B', 'L', 'Jl. Gatot Subroto No. 5, Ampek Angkek', '081234567894', '2023/2024', 'aktif', '2025-07-07 10:04:53', '2025-07-07 10:04:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `role` enum('admin','kepala_sekolah') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', '2025-07-07 10:04:53', '2025-07-07 10:04:53'),
(2, 'kepala_sekolah', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kepala Sekolah', 'kepala_sekolah', '2025-07-07 10:04:53', '2025-07-07 10:04:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hasil_perhitungan`
--
ALTER TABLE `hasil_perhitungan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `kriteria`
--
ALTER TABLE `kriteria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_penilaian` (`siswa_id`,`kriteria_id`),
  ADD KEY `kriteria_id` (`kriteria_id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nis` (`nis`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hasil_perhitungan`
--
ALTER TABLE `hasil_perhitungan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kriteria`
--
ALTER TABLE `kriteria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `penilaian`
--
ALTER TABLE `penilaian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `hasil_perhitungan`
--
ALTER TABLE `hasil_perhitungan`
  ADD CONSTRAINT `hasil_perhitungan_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD CONSTRAINT `penilaian_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penilaian_ibfk_2` FOREIGN KEY (`kriteria_id`) REFERENCES `kriteria` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
