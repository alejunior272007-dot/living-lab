-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 10, 2026 at 09:56 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `living_lab`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `audit_code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_code` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_code` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `audit_date` date NOT NULL,
  `source_category` enum('Rumah Tangga','Rumah Makan','Warung','Pasar','Dapur MBG') COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditor` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `what_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `why_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `how_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `total_weight` decimal(12,3) NOT NULL DEFAULT '0.000',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `audit_code`, `project_code`, `session_code`, `audit_date`, `source_category`, `auditor`, `location`, `what_description`, `why_description`, `how_description`, `notes`, `total_weight`, `created_at`, `updated_at`) VALUES
(1, 'OB01-MBG01-20261030', 'OB01', 'MBG01', '2026-10-30', 'Dapur MBG', 'ALVIN', 'SPPG Sindanglaka', 'sisa sayur & kulit buah', 'untuk penggunaan magot', 'dipilah', '', 5.555, '2026-11-09 00:37:13', '2026-11-10 06:15:55');

-- --------------------------------------------------------

--
-- Table structure for table `audit_photos`
--

CREATE TABLE `audit_photos` (
  `id` bigint UNSIGNED NOT NULL,
  `audit_id` bigint UNSIGNED NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_photos`
--

INSERT INTO `audit_photos` (`id`, `audit_id`, `original_name`, `stored_name`, `mime_type`, `file_size`, `created_at`) VALUES
(2, 1, 'WhatsApp Image 2026-11-09 at 18.43.37.jpeg', 'OB01_FOTO_MBG01_20261030_001.jpg', 'image/jpeg', 124025, '2026-11-10 00:33:02');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` text,
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_reset_requests`
--

INSERT INTO `password_reset_requests` (`id`, `user_id`, `reason`, `status`, `admin_note`, `requested_at`, `processed_at`, `processed_by`) VALUES
(1, 6, 'saya lupa password saya', 'approved', 'jangan sampe lupa lagi', '2026-11-10 08:06:28', '2026-11-10 15:07:29', 5);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','petugas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'petugas',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `password`, `role`, `is_active`, `must_change_password`, `created_at`, `updated_at`) VALUES
(1, 'Muhammad Ale Nurjaya Jr', 'ale junior', '$2y$10$Q1fTOkk.xtm75ISsa6Mi6.Rr6LOMGDNDcHEAFil0RpjSbTBWhy2RW', 'admin', 1, 0, '2026-11-09 01:39:15', '2026-11-09 01:39:15'),
(4, 'junior', 'niors', '$2y$10$XNi7SjG140CkpWM0oQiyDediIGD01E9Be5bjpQb4yok4CXVxKnTry', 'petugas', 1, 0, '2026-11-09 03:11:59', '2026-11-09 04:51:00'),
(5, 'PENGUASA', 'penguasa', '$2y$10$ZlU6W4GPbt18Y//k0AOLMensBvyfaAUsatM6Lgal/vdasux3WnWe.', 'admin', 1, 0, '2026-11-09 23:39:20', '2026-11-09 23:46:43'),
(6, 'PENGGUNA', 'user', '$2y$10$lGu2bEeSyseSSOAKyQ6HBu25BAa8ViXnnCsoyud0K7jt09T51eRNG', 'petugas', 1, 0, '2026-11-09 23:39:47', '2026-11-10 08:48:08');

-- --------------------------------------------------------

--
-- Table structure for table `waste_details`
--

CREATE TABLE `waste_details` (
  `id` bigint UNSIGNED NOT NULL,
  `audit_id` bigint UNSIGNED NOT NULL,
  `waste_type` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight_kg` decimal(12,3) NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `waste_details`
--

INSERT INTO `waste_details` (`id`, `audit_id`, `waste_type`, `weight_kg`, `note`, `created_at`) VALUES
(7, 1, 'kulit buah', 5.555, '', '2026-11-10 06:15:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `audit_code` (`audit_code`),
  ADD KEY `idx_audit_date` (`audit_date`),
  ADD KEY `idx_source_category` (`source_category`);

--
-- Indexes for table `audit_photos`
--
ALTER TABLE `audit_photos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stored_name` (`stored_name`),
  ADD KEY `idx_photo_audit` (`audit_id`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reset_user` (`user_id`),
  ADD KEY `idx_reset_status` (`status`),
  ADD KEY `idx_reset_requested` (`requested_at`),
  ADD KEY `fk_reset_request_admin` (`processed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `waste_details`
--
ALTER TABLE `waste_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_waste_audit` (`audit_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_photos`
--
ALTER TABLE `audit_photos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `waste_details`
--
ALTER TABLE `waste_details`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_photos`
--
ALTER TABLE `audit_photos`
  ADD CONSTRAINT `fk_photo_audit` FOREIGN KEY (`audit_id`) REFERENCES `audit_logs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD CONSTRAINT `fk_reset_request_admin` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reset_request_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `waste_details`
--
ALTER TABLE `waste_details`
  ADD CONSTRAINT `fk_waste_audit` FOREIGN KEY (`audit_id`) REFERENCES `audit_logs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
