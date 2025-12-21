-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 21, 2025 at 03:47 PM
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
-- Database: `student_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` enum('info','warning','success','error','reminder','general') NOT NULL DEFAULT 'info',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `target_audience` enum('all','students','staff','admins','specific') NOT NULL DEFAULT 'all',
  `target_user_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_user_ids`)),
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `type`, `priority`, `created_by`, `target_audience`, `target_user_ids`, `published_at`, `expires_at`, `is_active`, `is_pinned`, `views_count`, `created_at`, `updated_at`) VALUES
(1, 'Information Announcement 1', 'This is the first information announcement. It contains important information for all users.', 'info', 'medium', 1, 'all', NULL, '2025-12-18 15:19:39', '2026-01-17 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(2, 'Information Announcement 2', 'This is the second information announcement. Please read carefully.', 'info', 'low', 1, 'all', NULL, '2025-12-18 15:19:39', '2026-01-17 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(3, 'Information Announcement 3', 'This is the third information announcement. Stay informed!', 'info', 'high', 1, 'students', NULL, '2025-12-18 15:19:39', '2026-01-17 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(4, 'Warning Announcement 1', 'This is a warning announcement. Please pay attention to this important notice.', 'warning', 'high', 1, 'all', NULL, '2025-12-18 15:19:39', '2026-01-02 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(5, 'Warning Announcement 2', 'This is another warning announcement. Action may be required.', 'warning', 'urgent', 1, 'all', NULL, '2025-12-18 15:19:39', '2026-01-02 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(6, 'Warning Announcement 3', 'This is the third warning announcement. Please take necessary precautions.', 'warning', 'medium', 1, 'staff', NULL, '2025-12-18 15:19:39', '2026-01-02 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(7, 'Success Announcement 1', 'Congratulations! This is a success announcement celebrating achievements.', 'success', 'low', 1, 'all', NULL, '2025-12-18 15:19:39', '2026-02-16 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(8, 'Success Announcement 2', 'Great news! Another success announcement to share with everyone.', 'success', 'medium', 1, 'all', NULL, '2025-12-18 15:19:39', '2026-02-16 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(9, 'Success Announcement 3', 'Well done! This success announcement highlights recent accomplishments.', 'success', 'low', 1, 'students', NULL, '2025-12-18 15:19:39', '2026-02-16 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(10, 'Error Announcement 1', 'This is an error announcement. There was an issue that needs attention.', 'error', 'high', 1, 'all', NULL, '2025-12-18 15:19:39', '2025-12-25 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(11, 'Error Announcement 2', 'Another error announcement. Please be aware of this problem.', 'error', 'urgent', 1, 'all', NULL, '2025-12-18 15:19:39', '2025-12-25 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(12, 'Error Announcement 3', 'Error notification. Immediate action may be required.', 'error', 'high', 1, 'staff', NULL, '2025-12-18 15:19:39', '2025-12-25 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(13, 'Reminder Announcement 1', 'This is a reminder announcement. Please remember this important information.', 'reminder', 'medium', 1, 'all', NULL, '2025-12-18 15:19:39', '2026-01-07 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(14, 'Reminder Announcement 2', 'Friendly reminder: Please complete your tasks before the deadline.', 'reminder', 'low', 1, 'all', NULL, '2025-12-18 15:19:39', '2026-01-07 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(15, 'Reminder Announcement 3', 'Important reminder announcement. Don\'t forget about this!', 'reminder', 'medium', 1, 'students', NULL, '2025-12-18 15:19:39', '2026-01-07 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(16, 'General Announcement 1', 'This is a general announcement for all users. It contains general information.', 'general', 'low', 1, 'all', NULL, '2025-12-18 15:19:39', '2026-02-01 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(17, 'General Announcement 2', 'Another general announcement with updates and information.', 'general', 'medium', 1, 'all', NULL, '2025-12-18 15:19:39', '2026-02-01 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(18, 'General Announcement 3', 'General information announcement. Please review when you have time.', 'general', 'low', 1, 'all', NULL, '2025-12-18 15:19:39', '2026-02-01 15:19:39', 1, 0, 0, '2025-12-18 15:19:39', '2025-12-18 15:19:39');

-- --------------------------------------------------------

--
-- Table structure for table `attendees`
--

CREATE TABLE `attendees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `student_passport` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `facility_id` bigint(20) UNSIGNED NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_hours` int(11) NOT NULL,
  `purpose` text NOT NULL,
  `expected_attendees` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `facility_id`, `booking_date`, `start_time`, `end_time`, `duration_hours`, `purpose`, `expected_attendees`, `status`, `approved_by`, `rejection_reason`, `approved_at`, `cancelled_at`, `cancellation_reason`, `created_at`, `updated_at`) VALUES
(1, 2, 125, '2025-12-26', '09:00:00', '10:00:00', 1, 'Study session', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:00:00', '2025-12-21 02:00:00'),
(2, 3, 130, '2025-12-26', '09:00:00', '10:00:00', 1, 'Badminton practice', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:01:00', '2025-12-21 02:01:00'),
(3, 4, 126, '2025-12-26', '09:00:00', '10:00:00', 1, 'Group discussion', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:02:00', '2025-12-21 02:02:00'),
(4, 5, 133, '2025-12-26', '09:00:00', '10:00:00', 1, 'Squash training', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:03:00', '2025-12-21 02:03:00'),
(5, 6, 127, '2025-12-26', '09:00:00', '10:00:00', 1, 'Research reading', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:04:00', '2025-12-21 02:04:00'),
(6, 7, 135, '2025-12-26', '09:00:00', '10:00:00', 1, 'Basketball practice', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:05:00', '2025-12-21 02:05:00'),
(7, 8, 152, '2025-12-26', '09:00:00', '10:00:00', 1, 'Table tennis', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:06:00', '2025-12-21 02:06:00'),
(8, 9, 158, '2025-12-26', '09:00:00', '10:00:00', 1, 'Swimming training', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:07:00', '2025-12-21 02:07:00'),
(9, 10, 130, '2025-12-26', '09:00:00', '10:00:00', 1, 'Sports practice', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:08:00', '2025-12-21 02:08:00'),
(10, 11, 113, '2025-12-26', '09:00:00', '10:00:00', 1, 'Event setup', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:09:00', '2025-12-21 02:09:00'),
(11, 12, 115, '2025-12-26', '09:00:00', '10:00:00', 1, 'Lecture session', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:10:00', '2025-12-21 02:10:00'),
(12, 13, 120, '2025-12-26', '09:00:00', '10:00:00', 1, 'Computer lab usage', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:11:00', '2025-12-21 02:11:00'),
(13, 14, 122, '2025-12-26', '09:00:00', '10:00:00', 1, 'Training class', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:12:00', '2025-12-21 02:12:00'),
(14, 15, 147, '2025-12-26', '09:00:00', '10:00:00', 1, 'Gym training', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:13:00', '2025-12-21 02:13:00'),
(15, 16, 150, '2025-12-26', '09:00:00', '10:00:00', 1, 'Snooker practice', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:14:00', '2025-12-21 02:14:00'),
(16, 17, 114, '2025-12-26', '09:00:00', '10:00:00', 1, 'Hall reservation', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:15:00', '2025-12-21 02:15:00'),
(17, 18, 160, '2025-12-26', '09:00:00', '10:00:00', 1, 'Futsal session', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:16:00', '2025-12-21 02:16:00'),
(18, 19, 119, '2025-12-26', '09:00:00', '10:00:00', 1, 'Workshop', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:17:00', '2025-12-21 02:17:00'),
(19, 20, 138, '2025-12-26', '09:00:00', '10:00:00', 1, 'Volleyball training', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:18:00', '2025-12-21 02:18:00'),
(20, 11, 125, '2025-12-26', '09:00:00', '10:00:00', 1, 'Meeting discussion', 1, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-12-21 02:19:00', '2025-12-21 02:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `booking_slots`
--

CREATE TABLE `booking_slots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `slot_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_hours` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_slots`
--

INSERT INTO `booking_slots` (`id`, `booking_id`, `slot_date`, `start_time`, `end_time`, `duration_hours`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:00:00', '2025-12-21 06:00:00'),
(2, 2, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:01:00', '2025-12-21 06:01:00'),
(3, 3, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:02:00', '2025-12-21 06:02:00'),
(4, 4, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:03:00', '2025-12-21 06:03:00'),
(5, 5, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:04:00', '2025-12-21 06:04:00'),
(6, 6, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:05:00', '2025-12-21 06:05:00'),
(7, 7, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:06:00', '2025-12-21 06:06:00'),
(8, 8, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:07:00', '2025-12-21 06:07:00'),
(9, 9, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:08:00', '2025-12-21 06:08:00'),
(10, 10, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:09:00', '2025-12-21 06:09:00'),
(11, 11, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:10:00', '2025-12-21 06:10:00'),
(12, 12, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:11:00', '2025-12-21 06:11:00'),
(13, 13, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:12:00', '2025-12-21 06:12:00'),
(14, 14, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:13:00', '2025-12-21 06:13:00'),
(15, 15, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:14:00', '2025-12-21 06:14:00'),
(16, 16, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:15:00', '2025-12-21 06:15:00'),
(17, 17, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:16:00', '2025-12-21 06:16:00'),
(18, 18, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:17:00', '2025-12-21 06:17:00'),
(19, 19, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:18:00', '2025-12-21 06:18:00'),
(20, 20, '2025-12-26', '09:00:00', '10:00:00', 1, '2025-12-21 06:19:00', '2025-12-21 06:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `reward_id` bigint(20) UNSIGNED DEFAULT NULL,
  `certificate_number` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `issued_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `issued_by` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','issued','revoked') NOT NULL DEFAULT 'pending',
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `user_id`, `reward_id`, `certificate_number`, `title`, `description`, `issued_date`, `expiry_date`, `issued_by`, `status`, `file_path`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'CERT-6944F456A8EA2', 'Most Active Player', 'Award for most activate in the system.', '2025-12-19', NULL, '1', 'approved', NULL, '2025-12-18 22:44:38', '2025-12-18 22:44:38');

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('classroom','laboratory','sports','auditorium','library','cafeteria','other') NOT NULL DEFAULT 'other',
  `location` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL,
  `enable_multi_attendees` tinyint(1) NOT NULL DEFAULT 0,
  `max_attendees` int(11) DEFAULT NULL,
  `available_day` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_day`)),
  `available_time` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_time`)),
  `equipment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`equipment`)),
  `rules` text DEFAULT NULL,
  `status` enum('available','maintenance','unavailable','reserved') NOT NULL DEFAULT 'available',
  `image_url` varchar(255) DEFAULT NULL,
  `max_booking_hours` int(11) NOT NULL DEFAULT 4,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`id`, `name`, `code`, `description`, `type`, `location`, `capacity`, `enable_multi_attendees`, `max_attendees`, `available_day`, `available_time`, `equipment`, `rules`, `status`, `image_url`, `max_booking_hours`, `is_deleted`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(113, 'Dewan Utama (Main Hall)', 'AUD-DU1', 'The university’s premier multi-purpose hall, featuring a professional stage, theatrical lighting, and high-fidelity sound systems. Designed for convocations, major examinations, and large-scale cultural events.', 'auditorium', 'Main Campus, Block M', 1500, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\",\"sunday\"]', '{\"start\":\"08:00\",\"end\":\"23:00\"}', '[\"Stage\", \"PA System\", \"Projectors\", \"Lighting Rig\"]', '1. Booking is strictly for approved major events only.\n2. No food or drinks allowed inside the hall.\n3. Technical staff must be present for stage equipment use.\n4. All decorations and trash must be removed immediately after the event.', 'available', '/images/facilities/1765902491_6941889b93c49.jpg', 12, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:57:12'),
(114, 'College Hall', 'AUD-CH1', 'A modern secondary hall suitable for student assemblies, guest lectures, and medium-sized events. Features a flexible layout and standard AV equipment.', 'auditorium', 'Block A, Level 1', 500, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"08:00\",\"end\":\"22:00\"}', '[\"Sound System\", \"Stage\", \"Projector\"]', '1. Booking requires at least 1 week notice.\n2. Audio equipment usage must be registered in advance.\n3. Do not move fixed seating without permission.\n4. Please switch off lights and AC before leaving.', 'available', '/images/facilities/1765902542_694188ceb185f.jpg', 8, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:29:02'),
(115, 'Lecture Hall DK1', 'CLS-DK1', 'Large tiered lecture theatre designed for optimal viewing. Equipped with HD projectors and PA systems to ensure clear audio-visual delivery for mass lectures.', 'classroom', 'Block D, Ground Floor', 250, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"08:00\",\"end\":\"18:00\"}', '[\"Projector\", \"Microphone\", \"Whiteboard\"]', '1. No strong-smelling food allowed.\n2. Clean the whiteboard and remove personal trash after class.\n3. Ensure projectors and power outlets are switched off.\n4. Do not graffiti or scratch the desks.', 'available', '/images/facilities/1765902600_69418908eb12f.jpg', 4, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:30:00'),
(116, 'Lecture Hall DK2', 'CLS-DK2', 'Large tiered lecture theatre designed for optimal viewing. Equipped with HD projectors and PA systems to ensure clear audio-visual delivery for mass lectures.', 'classroom', 'Block D, Ground Floor', 150, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"08:00\",\"end\":\"18:00\"}', '[\"Projector\", \"Sound System\"]', '1. No strong-smelling food allowed.\n2. Clean the whiteboard and remove personal trash after class.\n3. Ensure projectors and power outlets are switched off.\n4. Do not graffiti or scratch the desks.', 'available', '/images/facilities/1765902612_69418914c9046.jpg', 4, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:30:12'),
(117, 'Tutorial Room H204', 'CLS-H204', 'Standard tutorial space with flexible seating arrangements. Ideal for group discussions, workshops, and postgraduate seminars. Equipped with interactive teaching aids.', 'classroom', 'Block H, Level 2', 30, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"18:00\"}', '[\"Whiteboard\", \"Projector\"]', '1. Return chairs and tables to their original layout after use.\n2. Turn off air-conditioning and lights to save energy.\n3. Keep mobile phones on silent mode.\n4. If using AV equipment, ensure cables are disconnected properly.', 'available', '/images/facilities/1765902652_6941893ce5fc2.jpg', 3, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:31:00'),
(118, 'Seminar Room S1', 'CLS-SEM', 'Standard tutorial space with flexible seating arrangements. Ideal for group discussions, workshops, and postgraduate seminars. Equipped with interactive teaching aids.', 'classroom', 'Block SB, Level 3', 40, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"09:00\",\"end\":\"18:00\"}', '[\"Smart Board\", \"Video Conference Kit\"]', '1. Return chairs and tables to their original layout after use.\n2. Turn off air-conditioning and lights to save energy.\n3. Keep mobile phones on silent mode.\n4. If using AV equipment, ensure cables are disconnected properly.', 'available', '/images/facilities/1765902750_6941899e54297.jpg', 4, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:32:30'),
(119, 'Hotel Training Suite', 'CLS-HOT', 'A fully simulated hotel suite for housekeeping training. Features industry-standard bedding, an ensuite bathroom, and housekeeping carts for an immersive practical experience.', 'classroom', 'Block R, Level 2', 10, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"09:00\",\"end\":\"16:00\"}', '[\"Bed\", \"Ensuite Bathroom\", \"Housekeeping Cart\"]', '1. Shoes must be removed or indoor slippers worn.\n2. For educational purposes only; not to be used as a rest area.\n3. Bedding and props must be reset to hotel standards after use.\n4. No eating or drinking allowed inside.', 'available', '/images/facilities/1765902838_694189f64623a.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:33:58'),
(120, 'Cyber Centre Lab 1', 'LAB-CYB1', 'General-purpose computer lab featuring high-performance desktops and high-speed internet. suitable for programming classes, e-learning, and digital assignments.', 'laboratory', 'Block M, Level 1', 30, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"08:00\",\"end\":\"21:00\"}', '[\"Desktops\", \"High-speed Internet\", \"Printer\"]', '1. Installation of games or unauthorized software is strictly prohibited.\n2. No food or open cups near the computers.\n3. Log off your account and turn off the monitor before leaving.\n4. Keep noise levels to a minimum.', 'available', '/images/facilities/1765902964_69418a740080d.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:36:04'),
(121, 'Engineering Workshop', 'LAB-ENG', 'Heavy engineering workshop equipped with industrial lathes, drill presses, and welding stations. Designed to build practical skills in mechanical fabrication.', 'laboratory', 'Block J, Ground Floor', 25, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"09:00\",\"end\":\"17:00\"}', '[\"Lathe Machines\", \"Drill Press\", \"Welding Station\"]', '1. Safety boots and goggles (PPE) are mandatory at all times.\n2. Long hair must be tied back; no loose clothing or jewelry.\n3. Machinery must only be operated under supervision.\n4. Press the Emergency Stop button immediately in case of accidents.', 'available', '/images/facilities/1765903040_69418ac08ad18.jpg', 4, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:37:20'),
(122, 'Science Lab B1', 'LAB-SCI', 'Comprehensive Chemistry and Biology laboratory equipped with microscopes, Bunsen burners, and fume hoods. Provides a safe environment for scientific experimentation.', 'laboratory', 'Block B, Level 1', 30, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"08:00\",\"end\":\"17:00\"}', '[\"Bunsen Burners\", \"Microscopes\", \"Chemical Storage\"]', '1. Lab coats must be worn upon entry.\n2. Never taste chemicals or eat in the lab.\n3. Keep flammable materials away from open flames.\n4. Chemical waste must be disposed of in designated bins, not the sink.', 'available', '/images/facilities/1765903233_69418b81e51fb.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:40:33'),
(123, 'Cisco Networking Lab', 'LAB-NET', 'Specialized networking lab housing enterprise-grade routers, switches, and server racks. Supports CCNA/CCNP certification courses and network topology simulations.', 'laboratory', 'Block M, Level 2', 25, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"09:00\",\"end\":\"18:00\"}', '[\"Routers\", \"Switches\", \"Server Racks\"]', '1. Do not unplug core cabling from the racks.\n2. Reset device configurations to default after your session.\n3. Discharge static electricity before touching hardware components.\n4. Do not remove any equipment or patch cords.', 'available', '/images/facilities/1765903378_69418c129cf57.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:42:58'),
(124, 'Hospitality Kitchen', 'LAB-KIT', 'Professional training kitchen with industrial ovens, stainless steel prep tables, and blast chillers. Adheres to strict food safety standards for culinary arts students.', 'laboratory', 'Block R, Level 1', 20, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"08:00\",\"end\":\"16:00\"}', '[\"Ovens\", \"Prep Tables\", \"Industrial Fridges\"]', '1. Food handling certification is required for entry.\n2. Full chef uniform, apron, and non-slip shoes are mandatory.\n3. Strictly observe raw vs. cooked food separation.\n4. Deep clean all workstations and utensils after use.', 'maintenance', '/images/facilities/1765903694_69418d4e71d3a.jpg', 4, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:48:14'),
(125, 'Library Discussion Room 1', 'LIB-DR1', 'Private, sound-insulated discussion room within the library. Furnished with a round table and whiteboard, perfect for group projects and brainstorming sessions.', 'library', 'Tsan Tan Siew Sin Library, Level 3', 8, 1, 8, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:30\",\"end\":\"20:00\"}', '[\"Whiteboard\", \"Round Table\", \"Power Sockets\"]', '1. Keep voices low to avoid disturbing others outside.\n2. No food allowed (lidded water bottles only).\n3. Erase the whiteboard before vacating the room.\n4. Strictly adhere to your booked time slot.', 'available', '/images/facilities/1765903761_69418d91395e7.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:49:21'),
(126, 'Library Discussion Room 2', 'LIB-DR2', 'Private, sound-insulated discussion room within the library. Furnished with a round table and whiteboard, perfect for group projects and brainstorming sessions.', 'library', 'Tsan Tan Siew Sin Library, Level 3', 8, 1, 8, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:30\",\"end\":\"20:00\"}', '[\"Whiteboard\", \"Round Table\", \"Power Sockets\"]', '1. Keep voices low to avoid disturbing others outside.\n2. No food allowed (lidded water bottles only).\n3. Erase the whiteboard before vacating the room.\n4. Strictly adhere to your booked time slot.', 'available', '/images/facilities/1765903777_69418da194a9e.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:49:37'),
(127, 'Library Multimedia Zone', 'LIB-MMZ', 'Individual multimedia carrels equipped with PCs and headphones. Designed for viewing educational materials, language learning, and digital research.', 'library', 'Tsan Tan Siew Sin Library, Level 2', 10, 1, 10, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:30\",\"end\":\"20:00\"}', '[\"PC\", \"Headphones\", \"Internet\"]', '1. Silent study zone; headphones are mandatory.\n2. PCs are for academic research purposes only.\n3. Do not save personal files on the desktop (files are wiped daily).\n4. Keep the station clean.', 'available', '/images/facilities/1765903870_69418dfe9e2e8.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:51:10'),
(128, 'Badminton Court 1', 'SPT-BD1', 'Professional indoor badminton court featuring rubberized mats for shock absorption and anti-slip safety. illuminated by glare-free lighting.', 'sports', 'Sports Complex, Main Hall', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"09:00\",\"end\":\"22:00\"}', '[\"Net\", \"Court Lighting\"]', '1. Non-marking court shoes are mandatory to prevent floor damage.\n2. Bookings are limited to 2 hour slots.\n3. Do not hang heavy items on the nets or posts.\n4. No food or colored drinks on the court.', 'available', '/images/facilities/1765899266_69417c027dd7d.png', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 07:34:26'),
(129, 'Badminton Court 2', 'SPT-BD2', 'Professional indoor badminton court featuring rubberized mats for shock absorption and anti-slip safety. illuminated by glare-free lighting.', 'sports', 'Sports Complex, Main Hall', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"09:00\",\"end\":\"22:00\"}', '[\"Net\", \"Court Lighting\"]', '1. Non-marking court shoes are mandatory to prevent floor damage.\n2. Bookings are limited to 2 hour slots.\n3. Do not hang heavy items on the nets or posts.\n4. No food or colored drinks on the court.', 'available', '/images/facilities/1765899266_69417c027dd7d.png', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(130, 'Badminton Court 3', 'SPT-BD3', 'Professional indoor badminton court featuring rubberized mats for shock absorption and anti-slip safety. illuminated by glare-free lighting.', 'sports', 'Sports Complex, Main Hall', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"09:00\",\"end\":\"22:00\"}', '[\"Net\", \"Court Lighting\"]', '1. Non-marking court shoes are mandatory to prevent floor damage.\n2. Bookings are limited to 2 hour slots.\n3. Do not hang heavy items on the nets or posts.\n4. No food or colored drinks on the court.', 'available', '/images/facilities/1765899266_69417c027dd7d.png', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(131, 'Badminton Court 4', 'SPT-BD4', 'Professional indoor badminton court featuring rubberized mats for shock absorption and anti-slip safety. illuminated by glare-free lighting.', 'sports', 'Sports Complex, Main Hall', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"09:00\",\"end\":\"22:00\"}', '[\"Net\", \"Court Lighting\"]', '1. Non-marking court shoes are mandatory to prevent floor damage.\n2. Bookings are limited to 2 hour slots.\n3. Do not hang heavy items on the nets or posts.\n4. No food or colored drinks on the court.', 'available', '/images/facilities/1765899266_69417c027dd7d.png', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(132, 'Badminton Court 5', 'SPT-BD5', 'Professional indoor badminton court featuring rubberized mats for shock absorption and anti-slip safety. illuminated by glare-free lighting.', 'sports', 'Sports Complex, Main Hall', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"09:00\",\"end\":\"22:00\"}', '[\"Net\", \"Court Lighting\"]', '1. Non-marking court shoes are mandatory to prevent floor damage.\n2. Bookings are limited to 2 hour slots.\n3. Do not hang heavy items on the nets or posts.\n4. No food or colored drinks on the court.', 'available', '/images/facilities/1765899266_69417c027dd7d.png', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(133, 'Squash Court A', 'SPT-SQ-A', 'Standard indoor squash court with a glass back wall and professional sprung wood flooring. Ideal for high-intensity cardio and agility training.', 'sports', 'Sports Complex, Ground Floor', 2, 1, 2, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"09:00\",\"end\":\"21:00\"}', '[\"Glass Wall\", \"Wooden Floor\"]', '1. Protective eyewear is highly recommended.\n2. Non-marking indoor shoes only.\n3. Ensure your opponent is clear of your swing radius.\n4. The door must be fully closed before play begins.', 'available', '/images/facilities/1765904099_69418ee303472.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:54:59'),
(134, 'Squash Court B', 'SPT-SQ-B', 'Standard indoor squash court with a glass back wall and professional sprung wood flooring. Ideal for high-intensity cardio and agility training.', 'sports', 'Sports Complex, Ground Floor', 2, 1, 2, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"09:00\",\"end\":\"21:00\"}', '[\"Glass Wall\", \"Wooden Floor\"]', '1. Protective eyewear is highly recommended.\n2. Non-marking indoor shoes only.\n3. Ensure your opponent is clear of your swing radius.\n4. The door must be fully closed before play begins.', 'available', '/images/facilities/1765904099_69418ee303472.jpg', 1, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(135, 'Basketball Court 1', 'SPT-BB1', 'Full-sized indoor basketball court with electronic scoreboards and suspended hoops. Suitable for competitive matches and tactical training.', 'sports', 'Sports Complex, Zone A', 30, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"22:00\"}', '[\"Scoreboard\", \"Hoops\"]', '1. Proper basketball or court shoes required.\n2. No hanging on the rims (dunking allowed only if supervised).\n3. Respect referees and opponents during matches.\n4. Keep bags off the playing surface.', 'available', '/images/facilities/1765904231_69418f672c001.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:57:11'),
(136, 'Basketball Court 2', 'SPT-BB2', 'Full-sized indoor basketball court with electronic scoreboards and suspended hoops. Suitable for competitive matches and tactical training.', 'sports', 'Sports Complex, Zone A', 30, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"22:00\"}', '[\"Scoreboard\", \"Hoops\"]', '1. Proper basketball or court shoes required.\n2. No hanging on the rims (dunking allowed only if supervised).\n3. Respect referees and opponents during matches.\n4. Keep bags off the playing surface.', 'available', '/images/facilities/1765904231_69418f672c001.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(137, 'Basketball Court 3', 'SPT-BB3', 'Outdoor concrete basketball court. Features durable surfaces and good airflow, perfect for streetball style games and casual practice.', 'sports', 'Sports Complex, Outdoor Area', 30, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\",\"sunday\"]', '{\"start\":\"07:00\",\"end\":\"19:00\"}', '[\"Hoops\", \"Concrete Surface\"]', '1. No booking required; first-come, first-served basis.\n2. Use restricted to daylight hours.\n3. Do not use if the surface is wet or slippery.\n4. Please bin your trash.', 'available', '/images/facilities/1765904231_69418f672c001.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(138, 'Volleyball Court 1', 'SPT-VB1', 'Standard indoor volleyball court with adjustable net heights. Features a flat, safe surface for serving, setting, and spiking drills.', 'sports', 'Sports Complex, Zone B', 20, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"09:00\",\"end\":\"21:00\"}', '[\"Net\", \"Poles\"]', '1. Do not hang or pull on the net.\n2. Proper sports attire is required.\n3. No kicking of volleyballs (use feet only when necessary).\n4. Assist in dismantling the net if you are the last group.', 'available', '/images/facilities/1765904339_69418fd3dc178.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 08:58:59'),
(139, 'Volleyball Court 2', 'SPT-VB2', 'Standard indoor volleyball court with adjustable net heights. Features a flat, safe surface for serving, setting, and spiking drills.', 'sports', 'Sports Complex, Zone B', 20, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"09:00\",\"end\":\"21:00\"}', '[\"Net\", \"Poles\"]', '1. Do not hang or pull on the net.\n2. Proper sports attire is required.\n3. No kicking of volleyballs (use feet only when necessary).\n4. Assist in dismantling the net if you are the last group.', 'available', '/images/facilities/1765904339_69418fd3dc178.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(140, 'Volleyball Court 3', 'SPT-VB3', 'Outdoor sand volleyball court offering a beach-style experience. excellent for resistance training and recreational matches.', 'sports', 'Sports Complex, Outdoor Area', 20, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"19:00\"}', '[\"Sand Court\", \"Net\"]', '1. Rinse sand off at the washing bay before entering buildings.\n2. Inspect sand for sharp objects before playing.\n3. Barefoot play is permitted at your own risk.\n4. Keep the sand inside the court area.', 'available', '/images/facilities/1765904339_69418fd3dc178.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(141, 'Volleyball Court 4', 'SPT-VB4', 'Outdoor sand volleyball court offering a beach-style experience. excellent for resistance training and recreational matches.', 'sports', 'Sports Complex, Outdoor Area', 20, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"19:00\"}', '[\"Sand Court\", \"Net\"]', '1. Rinse sand off at the washing bay before entering buildings.\n2. Inspect sand for sharp objects before playing.\n3. Barefoot play is permitted at your own risk.\n4. Keep the sand inside the court area.', 'maintenance', '/images/facilities/1765904339_69418fd3dc178.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(142, 'Pickleball Court 1', 'SPT-PB1', 'Rooftop Pickleball courts combining elements of tennis, badminton, and ping-pong. Features a hard-court surface with excellent views.', 'sports', 'Rooftop Courts', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"22:00\"}', '[\"Net\", \"Hard Court\"]', '1. Bring your own paddles and balls (rentals available).\n2. Keep noise levels reasonable to respect offices below.\n3. Do not lean on the perimeter fencing.\n4. Sports shoes are mandatory.', 'available', '/images/facilities/1765904442_6941903a53131.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:00:42'),
(143, 'Pickleball Court 2', 'SPT-PB2', 'Rooftop Pickleball courts combining elements of tennis, badminton, and ping-pong. Features a hard-court surface with excellent views.', 'sports', 'Rooftop Courts', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"22:00\"}', '[\"Net\", \"Hard Court\"]', '1. Bring your own paddles and balls (rentals available).\n2. Keep noise levels reasonable to respect offices below.\n3. Do not lean on the perimeter fencing.\n4. Sports shoes are mandatory.', 'available', '/images/facilities/1765904442_6941903a53131.jpg', 1, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(144, 'Pickleball Court 3', 'SPT-PB3', 'Rooftop Pickleball courts combining elements of tennis, badminton, and ping-pong. Features a hard-court surface with excellent views.', 'sports', 'Rooftop Courts', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"22:00\"}', '[\"Net\", \"Hard Court\"]', '1. Bring your own paddles and balls (rentals available).\n2. Keep noise levels reasonable to respect offices below.\n3. Do not lean on the perimeter fencing.\n4. Sports shoes are mandatory.', 'available', '/images/facilities/1765904442_6941903a53131.jpg', 1, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(145, 'Pickleball Court 4', 'SPT-PB4', 'Rooftop Pickleball courts combining elements of tennis, badminton, and ping-pong. Features a hard-court surface with excellent views.', 'sports', 'Rooftop Courts', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"22:00\"}', '[\"Net\", \"Hard Court\"]', '1. Bring your own paddles and balls (rentals available).\n2. Keep noise levels reasonable to respect offices below.\n3. Do not lean on the perimeter fencing.\n4. Sports shoes are mandatory.', 'available', '/images/facilities/1765904442_6941903a53131.jpg', 1, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(146, 'Pickleball Court 5', 'SPT-PB5', 'Rooftop Pickleball courts combining elements of tennis, badminton, and ping-pong. Features a hard-court surface with excellent views.', 'sports', 'Rooftop Courts', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"22:00\"}', '[\"Net\", \"Hard Court\"]', '1. Bring your own paddles and balls (rentals available).\n2. Keep noise levels reasonable to respect offices below.\n3. Do not lean on the perimeter fencing.\n4. Sports shoes are mandatory.', 'available', '/images/facilities/1765904442_6941903a53131.jpg', 1, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(147, 'Gym Room 1 (General)', 'SPT-GYM1', 'General cardio and fitness zone equipped with treadmills, ellipticals, and light free weights. Great for endurance and toning.', 'sports', 'Sports Complex, Level 2', 30, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"22:00\"}', '[\"Treadmills\", \"Dumbbells\", \"Yoga Mats\"]', '1. A personal towel is mandatory; wipe down machines after use.\n2. Re-rack all weights and dumbbells to their designated spots.\n3. No open-toed shoes or jeans allowed.\n4. Do not drop weights (except on deadlift platforms).', 'available', '/images/facilities/1765905035_6941928b1d1f7.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:10:35'),
(148, 'Gym Room 2 (Strength)', 'SPT-GYM2', 'Dedicated strength training room featuring power racks, bench presses, and deadlift platforms. Designed for heavy lifting and bodybuilding.', 'sports', 'Sports Complex, Level 2', 20, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"08:00\",\"end\":\"22:00\"}', '[\"Squat Racks\", \"Bench Press\", \"Deadlift Platform\"]', '1. A personal towel is mandatory; wipe down machines after use.\n2. Re-rack all weights and dumbbells to their designated spots.\n3. No open-toed shoes or jeans allowed.\n4. Do not drop weights (except on deadlift platforms).', 'available', '/images/facilities/1765905093_694192c59f1ba.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:11:33'),
(149, 'Gym Room 3 (Studio)', 'SPT-GYM3', 'Group exercise studio with mirrored walls and sound system. Open space suitable for yoga, Zumba, and aerobics.', 'sports', 'Sports Complex, Level 3', 20, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"09:00\",\"end\":\"20:00\"}', '[\"Mirrors\", \"Sound System\", \"Step Platforms\"]', '1. A personal towel is mandatory; wipe down machines after use.\n2. Re-rack all weights and dumbbells to their designated spots.\n3. No open-toed shoes or jeans allowed.\n4. Do not drop weights (except on deadlift platforms).', 'available', '/images/facilities/1765905109_694192d590c54.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:11:49'),
(150, 'Snooker Table 1', 'SPT-SNK1', 'Standard size snooker table located in the clubhouse game room. Maintained with high-quality cloth and cues for a premium leisure experience.', 'sports', 'Clubhouse, Game Room', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"10:00\",\"end\":\"22:00\"}', '[\"Cues\", \"Balls\", \"Triangle\"]', '1. No food or drinks allowed on the table edge.\n2. No jump shots or massé shots that may tear the cloth.\n3. Any damage to the cloth must be paid for.\n4. Maintain a quiet environment.', 'available', '/images/facilities/1765904812_694191ac73253.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:06:52'),
(151, 'Snooker Table 2', 'SPT-SNK2', 'Standard size snooker table located in the clubhouse game room. Maintained with high-quality cloth and cues for a premium leisure experience.', 'sports', 'Clubhouse, Game Room', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"10:00\",\"end\":\"17:00\"}', '[\"Cues\", \"Balls\", \"Triangle\"]', '1. No food or drinks allowed on the table edge.\n2. No jump shots or massé shots that may tear the cloth.\n3. Any damage to the cloth must be paid for.\n4. Maintain a quiet environment.', 'available', '/images/facilities/1765904812_694191ac73253.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:08:01'),
(152, 'Table Tennis 1', 'SPT-TT1', 'Professional ITTF standard table tennis setup. Located in the mezzanine area with ample lighting for fast-paced rallies.', 'sports', 'Sports Complex, Mezzanine', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"09:00\",\"end\":\"17:00\"}', '[\"Table\", \"Net\"]', '1. Bring your own bats and balls.\n2. Do not sit or lean on the tables.\n3. Keep the floor dry to prevent slipping.\n4. Avoid excessive shouting.', 'available', '/images/facilities/1765904945_69419231e292f.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:09:05'),
(153, 'Table Tennis 2', 'SPT-TT2', 'Professional ITTF standard table tennis setup. Located in the mezzanine area with ample lighting for fast-paced rallies.', 'sports', 'Sports Complex, Mezzanine', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"09:00\",\"end\":\"21:00\"}', '[\"Table\", \"Net\"]', '1. Bring your own bats and balls.\n2. Do not sit or lean on the tables.\n3. Keep the floor dry to prevent slipping.\n4. Avoid excessive shouting.', 'available', '/images/facilities/1765904945_69419231e292f.jpg', 1, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(154, 'Table Tennis 3', 'SPT-TT3', 'Professional ITTF standard table tennis setup. Located in the mezzanine area with ample lighting for fast-paced rallies.', 'sports', 'Sports Complex, Mezzanine', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"09:00\",\"end\":\"21:00\"}', '[\"Table\", \"Net\"]', '1. Bring your own bats and balls.\n2. Do not sit or lean on the tables.\n3. Keep the floor dry to prevent slipping.\n4. Avoid excessive shouting.', 'available', '/images/facilities/1765904945_69419231e292f.jpg', 1, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(155, 'Table Tennis 4', 'SPT-TT4', 'Professional ITTF standard table tennis setup. Located in the mezzanine area with ample lighting for fast-paced rallies.', 'sports', 'Sports Complex, Mezzanine', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\"]', '{\"start\":\"09:00\",\"end\":\"21:00\"}', '[\"Table\", \"Net\"]', '1. Bring your own bats and balls.\n2. Do not sit or lean on the tables.\n3. Keep the floor dry to prevent slipping.\n4. Avoid excessive shouting.', 'available', '/images/facilities/1765904945_69419231e292f.jpg', 1, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(156, 'Tennis Court 1', 'SPT-TN1', 'Outdoor hard-court tennis facility with standard nets and umpire chairs. Provides consistent bounce and good traction for all skill levels.', 'sports', 'Sports Complex, Outdoor Zone C', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\",\"sunday\"]', '{\"start\":\"07:00\",\"end\":\"19:00\"}', '[\"Net\", \"Umpire Chair\"]', '1. Proper non-marking tennis shoes are required.\n2. Wait for a break in play before crossing behind other courts.\n3. Collect all balls and trash before your time expires.\n4. Tennis activities only (no football or cycling).', 'available', '/images/facilities/1765904540_6941909c132cd.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:02:20'),
(157, 'Tennis Court 2', 'SPT-TN2', 'Outdoor hard-court tennis facility with standard nets and umpire chairs. Provides consistent bounce and good traction for all skill levels.', 'sports', 'Sports Complex, Outdoor Zone C', 4, 1, 4, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\",\"sunday\"]', '{\"start\":\"07:00\",\"end\":\"19:00\"}', '[\"Net\", \"Umpire Chair\"]', '1. Proper non-marking tennis shoes are required.\n2. Wait for a break in play before crossing behind other courts.\n3. Collect all balls and trash before your time expires.\n4. Tennis activities only (no football or cycling).', 'available', '/images/facilities/1765904540_6941909c132cd.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 15:33:00'),
(158, 'Olympic Swimming Pool', 'SPT-POOL', 'Olympic-sized outdoor swimming pool with marked lanes and diving blocks. Maintains high water quality standards for lap swimming and competitions.', 'sports', 'Sports Complex, Outdoor Area', 30, 0, NULL, '[\"tuesday\",\"wednesday\",\"thursday\",\"friday\",\"saturday\",\"sunday\"]', '{\"start\":\"08:00\",\"end\":\"19:00\"}', '[\"Lanes\", \"Diving Blocks\"]', '1. Shower before entering the pool.\n2. Proper swimwear and swimming caps are mandatory (no cotton).\n3. No diving in the shallow end.\n4. Persons with open wounds or skin infections are not permitted.', 'available', '/images/facilities/1765904626_694190f27834c.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:03:46'),
(159, 'Rooftop Futsal Court', 'SPT-FUT1', 'Rooftop open-air futsal pitch with floodlights and high fencing. Fresh air environment suitable for evening team games.', 'sports', 'Sports Complex, Rooftop Level', 60, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"17:00\",\"end\":\"22:00\"}', '[\"Goal Posts\", \"Fencing\", \"Floodlights\"]', '1. Flat-soled indoor shoes or turf shoes only (No studded boots).\n2. No sliding tackles to prevent injury.\n3. Team bookings only.\n4. No smoking or eating in the court area.', 'available', '/images/facilities/1765904689_69419131bc803.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:04:49'),
(160, 'Indoor Futsal Court', 'SPT-FUT2', 'Indoor futsal court with polished wooden flooring. Weather-proof venue ideal for fast, technical 5-a-side matches.', 'sports', 'Sports Complex, Main Hall B', 30, 0, NULL, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\",\"friday\"]', '{\"start\":\"18:00\",\"end\":\"22:00\"}', '[\"Goal Posts\", \"Benches\"]', '1. Flat-soled indoor shoes or turf shoes only (No studded boots).\n2. No sliding tackles to prevent injury.\n3. Team bookings only.\n4. No smoking or eating in the court area.', 'available', '/images/facilities/1765904731_6941915b35ecc.jpg', 2, 0, NULL, NULL, '2025-12-16 15:33:00', '2025-12-16 09:05:31'),
(161, 'Bry', 'SPT-GYM-01', '1111', 'laboratory', 'Main Campus, Block M', 1, 1, 1, '[\"monday\",\"tuesday\",\"wednesday\",\"thursday\"]', '{\"start\":\"08:00\",\"end\":\"18:00\"}', NULL, NULL, 'maintenance', '/images/facilities/1765905444_69419424e5b25.jpg', 2, 1, NULL, NULL, '2025-12-16 09:16:38', '2025-12-16 09:17:48'),
(162, 'test222', 'SPT-GYM-011', '1111', 'laboratory', 'Block A, Level 1', 11, 1, 1, '[\"monday\"]', '{\"start\":\"08:00\",\"end\":\"18:00\"}', NULL, NULL, 'available', NULL, 4, 1, NULL, NULL, '2025-12-16 10:06:08', '2025-12-16 10:06:59');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `facility_id` bigint(20) UNSIGNED DEFAULT NULL,
  `booking_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('complaint','suggestion','compliment','general') NOT NULL DEFAULT 'general',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `image` longtext DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `status` enum('pending','under_review','resolved','rejected','blocked') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_response` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `block_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `user_id`, `facility_id`, `booking_id`, `type`, `subject`, `message`, `image`, `rating`, `status`, `reviewed_by`, `admin_response`, `reviewed_at`, `is_blocked`, `block_reason`, `created_at`, `updated_at`) VALUES
(1, 2, 125, NULL, 'compliment', 'Quiet discussion room', 'The room was quiet and suitable for study.', NULL, 5, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:00:00', '2025-12-21 02:00:00'),
(2, 3, 128, NULL, 'general', 'Court condition', 'Badminton court surface was acceptable.', NULL, 4, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:01:00', '2025-12-21 02:01:00'),
(3, 4, 126, NULL, 'complaint', 'Air conditioning issue', 'The room was too cold.', NULL, 3, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:02:00', '2025-12-21 02:02:00'),
(4, 5, 130, NULL, 'compliment', 'Lighting quality', 'Lighting was good during gameplay.', NULL, 4, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:03:00', '2025-12-21 02:03:00'),
(5, 6, 127, NULL, 'compliment', 'Internet speed', 'WiFi connection was fast and stable.', NULL, 5, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:04:00', '2025-12-21 02:04:00'),
(6, 7, 133, NULL, 'general', 'Court cleanliness', 'Squash court was clean.', NULL, 4, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:05:00', '2025-12-21 02:05:00'),
(7, 8, 135, NULL, 'suggestion', 'Basketball hoops', 'Suggest checking hoop alignment regularly.', NULL, 4, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:06:00', '2025-12-21 02:06:00'),
(8, 9, 152, NULL, 'complaint', 'Table tennis net', 'Net tension was slightly loose.', NULL, 3, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:07:00', '2025-12-21 02:07:00'),
(9, 10, 158, NULL, 'compliment', 'Swimming pool condition', 'Pool water was clean and well maintained.', NULL, 5, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:08:00', '2025-12-21 02:08:00'),
(10, 11, 113, NULL, 'compliment', 'Main hall facilities', 'PA system and lighting worked well.', NULL, 5, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:09:00', '2025-12-21 02:09:00'),
(11, 12, 115, NULL, 'general', 'Lecture hall seating', 'Seats were comfortable for long sessions.', NULL, 4, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:10:00', '2025-12-21 02:10:00'),
(12, 13, 120, NULL, 'complaint', 'Computer performance', 'Some PCs were slow to start.', NULL, 3, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:11:00', '2025-12-21 02:11:00'),
(13, 14, 122, NULL, 'compliment', 'Lab safety', 'Safety rules were clearly displayed.', NULL, 5, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:12:00', '2025-12-21 02:12:00'),
(14, 15, 125, NULL, 'general', 'Discussion room use', 'Room was suitable for small meetings.', NULL, 4, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:13:00', '2025-12-21 02:13:00'),
(15, 16, 138, NULL, 'suggestion', 'Volleyball court net', 'Recommend checking net tension before sessions.', NULL, 4, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:14:00', '2025-12-21 02:14:00'),
(16, 17, 147, NULL, 'compliment', 'Gym equipment', 'Equipment was well maintained.', NULL, 5, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:15:00', '2025-12-21 02:15:00'),
(17, 18, 150, NULL, 'general', 'Snooker table', 'Table surface was smooth.', NULL, 4, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:16:00', '2025-12-21 02:16:00'),
(18, 19, 119, NULL, 'suggestion', 'Training room layout', 'Consider adding more flexible seating.', NULL, 4, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:17:00', '2025-12-21 02:17:00'),
(19, 20, 114, NULL, 'compliment', 'Event space', 'Hall size was suitable for large events.', NULL, 5, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:18:00', '2025-12-21 02:18:00'),
(20, 11, 160, NULL, 'general', 'Futsal court condition', 'Court floor grip was acceptable.', NULL, 4, 'pending', NULL, NULL, NULL, 0, NULL, '2025-12-21 02:19:00', '2025-12-21 02:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_points`
--

CREATE TABLE `loyalty_points` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `action_type` varchar(255) NOT NULL,
  `related_id` bigint(20) UNSIGNED DEFAULT NULL,
  `related_type` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_rules`
--

CREATE TABLE `loyalty_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `action_type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loyalty_rules`
--

INSERT INTO `loyalty_rules` (`id`, `action_type`, `name`, `description`, `points`, `is_active`, `conditions`, `created_at`, `updated_at`) VALUES
(1, 'feedback_resolved', 'Feedback Resolved', 'Points awarded when feedback is marked resolved by admin.', 20, 1, NULL, '2025-12-18 09:12:32', '2025-12-18 09:12:32'),
(2, 'facility_booking_long_duration', 'Long Duration Booking', 'Extra points for bookings lasting 4 hours or more', 30, 1, NULL, '2025-12-18 09:13:05', '2025-12-18 09:13:05'),
(3, 'facility_booking_first', 'First Facility Booking', 'Bonus points for completing your first facility booking.', 30, 1, NULL, '2025-12-18 09:13:45', '2025-12-18 09:13:45'),
(4, 'facility_booking_complete', 'Facility Booking Complete', 'Reward points when completes a facility booking.', 20, 1, NULL, '2025-12-18 09:14:19', '2025-12-18 09:14:19'),
(5, 'booking_gym', 'Facility Booking Gym', NULL, 10, 1, NULL, '2025-12-21 05:05:58', '2025-12-21 05:05:58');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2019_08_19_000000_create_failed_jobs_table', 1),
(3, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(4, '2025_12_08_123005_create_users_table', 1),
(5, '2025_12_08_184655_create_user_activity_logs_table', 1),
(6, '2025_12_08_184703_create_notifications_table', 1),
(7, '2025_12_08_184711_create_loyalty_points_table', 1),
(8, '2025_12_08_184716_create_rewards_table', 1),
(9, '2025_12_08_184720_create_certificates_table', 1),
(10, '2025_12_08_184728_create_facilities_table', 1),
(11, '2025_12_08_184732_create_bookings_table', 1),
(12, '2025_12_08_184733_create_booking_status_history_table', 1),
(13, '2025_12_08_184734_create_feedbacks_table', 1),
(14, '2025_12_08_184754_create_user_notification_table', 1),
(15, '2025_12_08_184758_create_user_reward_table', 1),
(16, '2025_12_11_143441_add_settings_to_users_table', 1),
(17, '2025_12_11_151431_add_remember_token_to_users_table', 1),
(18, '2025_12_12_090116_remove_booking_advance_days_from_facilities_table', 1),
(19, '2025_12_12_091829_add_otp_to_users_table', 1),
(20, '2025_12_12_093249_add_available_day_and_available_time_to_facilities_table', 1),
(21, '2025_12_12_093827_remove_available_times_from_facilities_table', 1),
(22, '2025_12_12_141811_remove_settings_and_user_notification_table', 1),
(23, '2025_12_12_142421_remove_settings_from_users_table', 1),
(24, '2025_12_12_192526_create_loyalty_rules_table', 1),
(25, '2025_12_13_061343_create_announcements_table', 1),
(26, '2025_12_13_061433_create_user_announcement_table', 1),
(27, '2025_12_13_155339_add_is_starred_to_user_announcement_and_user_notification_tables', 1),
(28, '2025_12_13_174317_add_multi_attendees_fields_to_facilities_table', 1),
(29, '2025_12_13_192217_create_attendees_table', 1),
(30, '2025_12_14_042723_add_reschedule_request_to_bookings_table', 1),
(31, '2025_12_14_052956_modify_reschedule_time_fields_to_string', 1),
(32, '2025_12_14_054451_remove_reschedule_fields_from_bookings_table', 1),
(33, '2025_12_14_111640_create_booking_slots_table', 1),
(34, '2025_12_14_142355_add_studentid_to_users_table', 1),
(35, '2025_12_14_151336_add_image_to_feedbacks_table', 1),
(36, '2025_12_14_160551_modify_rewards_image_url_to_longtext', 1),
(37, '2025_12_14_161647_remove_booking_number_from_bookings_table', 1),
(38, '2025_12_14_162529_remove_special_requirements_from_bookings_table', 1),
(39, '2025_12_16_102416_rename_studentid_to_personal_id_in_users_table', 1),
(40, '2025_12_16_114048_add_is_deleted_to_facilities_table', 1),
(41, '2025_12_18_000000_drop_booking_id_from_feedbacks_table', 1),
(42, '2025_12_18_153958_change_feedbacks_facility_id_to_type', 2),
(44, '2025_12_18_153958_add_booking_id_to_feedbacks_table', 3),
(45, '2025_12_19_042504_create_feedbacks_table_if_not_exists', 4),
(46, '2025_12_19_152035_add_created_by_and_updated_by_to_facilities_table', 5),
(47, '2025_12_20_010559_remove_requires_approval_from_facilities_table', 5),
(48, '2025_12_20_010920_remove_requires_approval_from_facilities_table', 5);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error','reminder') NOT NULL DEFAULT 'info',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `target_audience` enum('all','students','staff','admins','specific') NOT NULL DEFAULT 'all',
  `target_user_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_user_ids`)),
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `title`, `message`, `type`, `priority`, `created_by`, `target_audience`, `target_user_ids`, `scheduled_at`, `expires_at`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'New Feedback Submitted - Complaint', 'User 2 has submitted a new feedback:\n\nSubject: tetetet\nType: Complaint\nFacility Type: Library\nRating: 1/5\nFeedback ID: #28', 'info', 'medium', 2, 'specific', '[1]', '2025-12-18 07:47:53', NULL, 1, '2025-12-18 07:47:53', '2025-12-18 07:47:53'),
(2, 'New Feedback Submitted - Suggestion', 'User 2 has submitted a new feedback:\n\nSubject: sasasasa\nType: Suggestion\nFacility Type: Sports\nRating: 3/5\nFeedback ID: #29', 'info', 'medium', 2, 'specific', '[1]', '2025-12-18 07:49:52', NULL, 1, '2025-12-18 07:49:52', '2025-12-18 07:49:52'),
(3, 'Feedback Resolved - More Study Tables', 'Your feedback has been resolved by Admin.\n\nSubject: More Study Tables\nFeedback ID: #2\n\nAdmin Response:\nNoted with thanks\n\nYou can view the full details in your feedback history.', 'success', 'medium', 1, 'specific', '[3]', '2025-12-18 08:04:47', NULL, 1, '2025-12-18 08:04:47', '2025-12-18 08:04:47'),
(4, 'New Booking Request', 'Staff 5 has submitted a new booking request:\n\nFacility: Lecture Hall DK1\nDate: 2025-12-22\nTime: 08:00 - 09:00\nBooking ID: #1', 'info', 'medium', 15, 'specific', '[1]', '2025-12-18 10:24:14', NULL, 1, '2025-12-18 10:24:14', '2025-12-18 10:24:14'),
(5, 'New Booking Request', 'Boo Kai Jie has submitted a new booking request:\n\nFacility: Badminton Court 1\nDate: 2025-12-20\nTime: 09:00 - 10:00\nBooking ID: #2', 'info', 'medium', 12, 'specific', '[1]', '2025-12-18 20:12:29', NULL, 1, '2025-12-18 20:12:29', '2025-12-18 20:12:29'),
(6, 'New Feedback Submitted - Complaint', 'Boo Kai Jie has submitted a new feedback:\n\nSubject: Swimming pool\nType: Complaint\nRating: 1/5\nFeedback ID: #1', 'info', 'medium', 12, 'specific', '[1]', '2025-12-18 20:27:02', NULL, 1, '2025-12-18 20:27:02', '2025-12-18 20:27:02'),
(7, 'Feedback Resolved - Swimming pool', 'Your feedback has been resolved by Admin.\n\nSubject: Swimming pool\nFeedback ID: #1\n\nAdmin Response:\nOk\n\nYou can view the full details in your feedback history.', 'success', 'medium', 1, 'specific', '[12]', '2025-12-18 20:27:59', NULL, 1, '2025-12-18 20:27:59', '2025-12-18 20:27:59'),
(8, 'New Feedback Submitted - Complaint', 'Boo Kai Jie has submitted a new feedback:\n\nSubject: Swimming pool\nType: Complaint\nRating: 1/5\nFeedback ID: #2', 'info', 'medium', 12, 'specific', '[1]', '2025-12-18 21:27:08', NULL, 1, '2025-12-18 21:27:08', '2025-12-18 21:27:08'),
(9, 'New Booking Request', 'Boo Kai Jie has submitted a new booking request:\n\nFacility: Badminton Court 1\nDate: 2025-12-20\nTime: 10:00 - 11:00\nBooking ID: #3', 'info', 'medium', 12, 'specific', '[1]', '2025-12-18 22:17:49', NULL, 1, '2025-12-18 22:17:49', '2025-12-18 22:17:49'),
(10, 'New Feedback Submitted - Complaint', 'Boo Kai Jie has submitted a new feedback:\n\nSubject: ssasasa\nType: Complaint\nRating: 1/5\nFeedback ID: #3', 'info', 'medium', 12, 'specific', '[1]', '2025-12-18 22:19:59', NULL, 1, '2025-12-18 22:19:59', '2025-12-18 22:19:59'),
(11, 'Feedback Resolved - ssasasa', 'Your feedback has been resolved by Admin.\n\nSubject: ssasasa\nFeedback ID: #3\n\nAdmin Response:\nok\n\nYou can view the full details in your feedback history.', 'success', 'medium', 1, 'specific', '[12]', '2025-12-18 22:23:45', NULL, 1, '2025-12-18 22:23:45', '2025-12-18 22:23:45'),
(12, 'New Feedback Submitted - Complaint', 'User 2 has submitted a new feedback:\n\nSubject: gggg\nType: Complaint\nRating: 1/5\nFeedback ID: #4', 'info', 'medium', 2, 'specific', '[1]', '2025-12-18 22:37:11', NULL, 1, '2025-12-18 22:37:11', '2025-12-18 22:37:11'),
(13, 'New Booking Request', 'User 2 has submitted a new booking request:\n\nFacility: Volleyball Court 3\nDate: 2025-12-20\nTime: 08:00 - 09:00\nBooking ID: #4', 'info', 'medium', 2, 'specific', '[1]', '2025-12-18 22:47:15', NULL, 1, '2025-12-18 22:47:15', '2025-12-18 22:47:15'),
(14, 'Booking Approved', 'Your booking has been approved!\n\nFacility: Volleyball Court 3\nDate: 2025-12-20\nTime: 08:00 - 09:00\nBooking ID: 4', 'success', 'medium', 1, 'specific', '[2]', '2025-12-18 22:47:33', NULL, 1, '2025-12-18 22:47:33', '2025-12-18 22:47:33'),
(15, 'Booking Completed', 'Your booking has been marked as completed!\n\nFacility: Volleyball Court 3\nDate: 2025-12-20\nTime: 08:00 - 09:00\nBooking ID: 4', 'info', 'medium', 1, 'specific', '[2]', '2025-12-18 22:47:43', NULL, 1, '2025-12-18 22:47:43', '2025-12-18 22:47:43'),
(16, 'New Feedback Submitted - Complaint', 'User 2 has submitted a new feedback:\n\nSubject: sasa\nType: Complaint\nRating: 1/5\nFeedback ID: #5', 'info', 'medium', 2, 'specific', '[1]', '2025-12-20 07:31:10', NULL, 1, '2025-12-20 07:31:10', '2025-12-20 07:31:10'),
(17, 'New Feedback Submitted - Complaint', 'User 2 has submitted a new feedback:\n\nSubject: sasasasa\nType: Complaint\nRating: 1/5\nFeedback ID: #6', 'info', 'medium', 2, 'specific', '[1]', '2025-12-20 07:34:47', NULL, 1, '2025-12-20 07:34:47', '2025-12-20 07:34:47'),
(18, 'New Feedback Submitted - General Feedback', 'User 3 has submitted a new feedback:\n\nSubject: sasassa\nType: General Feedback\nRating: 5/5\nFeedback ID: #7', 'info', 'medium', 3, 'specific', '[1]', '2025-12-20 07:36:38', NULL, 1, '2025-12-20 07:36:38', '2025-12-20 07:36:38'),
(19, 'New Feedback Submitted - Complaint', 'User 2 has submitted a new feedback:\n\nSubject: sasasa\nType: Complaint\nRating: 2/5\nFeedback ID: #8', 'info', 'medium', 2, 'specific', '[1]', '2025-12-20 07:52:41', NULL, 1, '2025-12-20 07:52:41', '2025-12-20 07:52:41'),
(20, 'New Booking Request', 'User 3 has submitted a new booking request:\n\nFacility: Library Discussion Room 1\nDate: 2025-12-22\nTime: 08:30 - 12:30\nBooking ID: #5', 'info', 'medium', 3, 'specific', '[1]', '2025-12-20 08:03:43', NULL, 1, '2025-12-20 08:03:43', '2025-12-20 08:03:43'),
(21, 'New Booking Request', 'User 2 has submitted a new booking request:\n\nFacility: Library Discussion Room 2\nDate: 2025-12-22\nTime: 13:30 - 14:30\nBooking ID: #6', 'info', 'medium', 2, 'specific', '[1]', '2025-12-20 12:44:10', NULL, 1, '2025-12-20 12:44:10', '2025-12-20 12:44:10'),
(22, 'Booking Approved', 'Your booking has been approved!\n\nFacility: Library Discussion Room 2\nDate: 2025-12-22\nTime: 13:30 - 14:30\nBooking ID: 6', 'success', 'medium', 1, 'specific', '[2]', '2025-12-20 12:44:56', NULL, 1, '2025-12-20 12:44:56', '2025-12-20 12:44:56'),
(23, 'New Feedback Submitted - Complaint', 'User 2 has submitted a new feedback:\n\nSubject: sasasasa\nType: Complaint\nRating: 1/5\nFeedback ID: #9', 'info', 'medium', 2, 'specific', '[1]', '2025-12-20 13:22:37', NULL, 1, '2025-12-20 13:22:37', '2025-12-20 13:22:37'),
(24, 'New Feedback Submitted - Complaint', 'User 2 has submitted a new feedback:\n\nSubject: ssssssssssss\nType: Complaint\nRating: 5/5\nFeedback ID: #10', 'info', 'medium', 2, 'specific', '[1]', '2025-12-20 13:22:52', NULL, 1, '2025-12-20 13:22:52', '2025-12-20 13:22:52'),
(25, 'New Feedback Submitted - Suggestion', 'User 2 has submitted a new feedback:\n\nSubject: aaaaaaaaaaaaaaaaaaaaaaaaaaa\nType: Suggestion\nRating: 3/5\nFeedback ID: #11', 'info', 'medium', 2, 'specific', '[1]', '2025-12-20 13:28:05', NULL, 1, '2025-12-20 13:28:05', '2025-12-20 13:28:05'),
(26, 'Feedback Resolved - aaaaaaaaaaaaaaaaaaaaaaaaaaa', 'Your feedback has been resolved by Admin.\n\nSubject: aaaaaaaaaaaaaaaaaaaaaaaaaaa\nFeedback ID: #11\n\nAdmin Response:\nok\n\nYou can view the full details in your feedback history.', 'success', 'medium', 1, 'specific', '[2]', '2025-12-20 13:37:46', NULL, 1, '2025-12-20 13:37:46', '2025-12-20 13:37:46'),
(27, 'Feedback Resolved - ssssssssssss', 'Your feedback has been resolved by Admin.\n\nSubject: ssssssssssss\nFeedback ID: #10\n\nAdmin Response:\nok\n\nYou can view the full details in your feedback history.', 'success', 'medium', 1, 'specific', '[2]', '2025-12-20 13:38:00', NULL, 1, '2025-12-20 13:38:00', '2025-12-20 13:38:00'),
(28, 'Feedback Resolved - sasasa', 'Your feedback has been resolved by Admin.\n\nSubject: sasasa\nFeedback ID: #8\n\nAdmin Response:\nsasasa\n\nYou can view the full details in your feedback history.', 'success', 'medium', 1, 'specific', '[2]', '2025-12-20 13:48:00', NULL, 1, '2025-12-20 13:48:00', '2025-12-20 13:48:00'),
(29, 'New Booking Request', 'User 2 has submitted a new booking request:\n\nFacility: Gym Room 1 (General)\nDate: 2025-12-22\nTime: 12:00 - 13:00\nBooking ID: #7', 'info', 'medium', 2, 'specific', '[1]', '2025-12-21 05:04:26', NULL, 1, '2025-12-21 05:04:26', '2025-12-21 05:04:26'),
(30, 'Booking Approved', 'Your booking has been approved!\n\nFacility: Gym Room 1 (General)\nDate: 2025-12-22\nTime: 12:00 - 13:00\nBooking ID: 7', 'success', 'medium', 1, 'specific', '[2]', '2025-12-21 05:04:49', NULL, 1, '2025-12-21 05:04:49', '2025-12-21 05:04:49'),
(31, 'Booking Completed', 'Your booking has been marked as completed!\n\nFacility: Gym Room 1 (General)\nDate: 2025-12-22\nTime: 12:00 - 13:00\nBooking ID: 7', 'info', 'medium', 1, 'specific', '[2]', '2025-12-21 05:05:24', NULL, 1, '2025-12-21 05:05:24', '2025-12-21 05:05:24'),
(32, 'New Booking Request', 'User 2 has submitted a new booking request:\n\nFacility: Gym Room 1 (General)\nDate: 2025-12-22\nTime: 10:00 - 11:00\nBooking ID: #8', 'info', 'medium', 2, 'specific', '[1]', '2025-12-21 05:06:13', NULL, 1, '2025-12-21 05:06:13', '2025-12-21 05:06:13'),
(33, 'Booking Approved', 'Your booking has been approved!\n\nFacility: Gym Room 1 (General)\nDate: 2025-12-22\nTime: 10:00 - 11:00\nBooking ID: 8', 'success', 'medium', 1, 'specific', '[2]', '2025-12-21 05:06:23', NULL, 1, '2025-12-21 05:06:23', '2025-12-21 05:06:23'),
(34, 'Booking Completed', 'Your booking has been marked as completed!\n\nFacility: Gym Room 1 (General)\nDate: 2025-12-22\nTime: 10:00 - 11:00\nBooking ID: 8', 'info', 'medium', 1, 'specific', '[2]', '2025-12-21 05:06:32', NULL, 1, '2025-12-21 05:06:32', '2025-12-21 05:06:32'),
(35, 'New Feedback Submitted - Complaint', 'User 2 has submitted a new feedback:\n\nSubject: sasasa\nType: Complaint\nRating: 2/5\nFeedback ID: #12', 'info', 'medium', 2, 'specific', '[1]', '2025-12-21 05:18:49', NULL, 1, '2025-12-21 05:18:49', '2025-12-21 05:18:49'),
(36, 'New Booking Request', 'User 2 has submitted a new booking request:\n\nFacility: Volleyball Court 3\nDate: 2025-12-22\nTime: 09:00 - 10:00\nBooking ID: #9', 'info', 'medium', 2, 'specific', '[1]', '2025-12-21 05:19:19', NULL, 1, '2025-12-21 05:19:19', '2025-12-21 05:19:19'),
(37, 'New Booking Request', 'User 2 has submitted a new booking request:\n\nFacility: Library Discussion Room 2\nDate: 2025-12-22\nTime: 10:30 - 11:30\nBooking ID: #10', 'info', 'medium', 2, 'specific', '[1]', '2025-12-21 05:21:00', NULL, 1, '2025-12-21 05:21:00', '2025-12-21 05:21:00'),
(38, 'Booking Approved', 'Your booking has been approved!\n\nFacility: Library Discussion Room 2\nDate: 2025-12-22\nTime: 10:30 - 11:30\nBooking ID: 10', 'success', 'medium', 1, 'specific', '[2]', '2025-12-21 05:24:01', NULL, 1, '2025-12-21 05:24:01', '2025-12-21 05:24:01'),
(39, 'Booking Approved', 'Your booking has been approved!\n\nFacility: Volleyball Court 3\nDate: 2025-12-22\nTime: 09:00 - 10:00\nBooking ID: 9', 'success', 'medium', 1, 'specific', '[2]', '2025-12-21 05:25:21', NULL, 1, '2025-12-21 05:25:21', '2025-12-21 05:25:21'),
(40, 'New Booking Request', 'User 2 has submitted a new booking request:\n\nFacility: Library Multimedia Zone\nDate: 2025-12-22\nTime: 08:30 - 09:30\nBooking ID: #11', 'info', 'medium', 2, 'specific', '[1]', '2025-12-21 05:26:17', NULL, 1, '2025-12-21 05:26:17', '2025-12-21 05:26:17'),
(41, 'Booking Approved', 'Your booking has been approved!\n\nFacility: Library Multimedia Zone\nDate: 2025-12-22\nTime: 08:30 - 09:30\nBooking ID: 11', 'success', 'medium', 1, 'specific', '[2]', '2025-12-21 05:28:01', NULL, 1, '2025-12-21 05:28:01', '2025-12-21 05:28:01'),
(42, 'Booking Completed', 'Your booking has been marked as completed!\n\nFacility: Library Multimedia Zone\nDate: 2025-12-22\nTime: 08:30 - 09:30\nBooking ID: 11', 'info', 'medium', 1, 'specific', '[2]', '2025-12-21 05:28:08', NULL, 1, '2025-12-21 05:28:08', '2025-12-21 05:28:08'),
(43, 'New Booking Request', 'User 2 has submitted a new booking request:\n\nFacility: Library Discussion Room 1\nDate: 2025-12-22\nTime: 09:30 - 10:30\nBooking ID: #12', 'info', 'medium', 2, 'specific', '[1]', '2025-12-21 05:29:13', NULL, 1, '2025-12-21 05:29:13', '2025-12-21 05:29:13'),
(44, 'New Booking Request', 'User 4 has submitted a new booking request:\n\nFacility: Library Discussion Room 1\nDate: 2025-12-22\nTime: 09:30 - 10:30\nBooking ID: #13', 'info', 'medium', 4, 'specific', '[1]', '2025-12-21 05:29:14', NULL, 1, '2025-12-21 05:29:14', '2025-12-21 05:29:14'),
(45, 'New Booking Request', 'User 2 has submitted a new booking request:\n\nFacility: Labubu\nDate: 2025-12-22\nTime: 08:00 - 09:00\nBooking ID: #14', 'info', 'medium', 2, 'specific', '[1]', '2025-12-21 05:31:20', NULL, 1, '2025-12-21 05:31:20', '2025-12-21 05:31:20'),
(46, 'New Booking Request', 'User 5 has submitted a new booking request:\n\nFacility: Labubu\nDate: 2025-12-22\nTime: 08:00 - 09:00\nBooking ID: #15', 'info', 'medium', 5, 'specific', '[1]', '2025-12-21 05:31:21', NULL, 1, '2025-12-21 05:31:21', '2025-12-21 05:31:21'),
(47, 'New Feedback Submitted - Suggestion', 'User 8 has submitted a new feedback:\n\nSubject: Swimming pool is clean\nType: Suggestion\nRating: 1/5\nFeedback ID: #13', 'info', 'medium', 8, 'specific', '[1]', '2025-12-21 05:34:35', NULL, 1, '2025-12-21 05:34:35', '2025-12-21 05:34:35'),
(48, 'Booking Rejected', 'Your booking has been rejected. Reason: Capacity Exceeded\n\nFacility: Labubu\nDate: 2025-12-22\nTime: 08:00 - 09:00\nBooking ID: 15', 'error', 'medium', 1, 'specific', '[5]', '2025-12-21 05:35:58', NULL, 1, '2025-12-21 05:35:58', '2025-12-21 05:35:58'),
(49, 'Booking Rejected', 'Your booking has been rejected. Reason: Capacity Exceeded\n\nFacility: Labubu\nDate: 2025-12-22\nTime: 08:00 - 09:00\nBooking ID: 14', 'error', 'medium', 1, 'specific', '[2]', '2025-12-21 05:36:03', NULL, 1, '2025-12-21 05:36:03', '2025-12-21 05:36:03'),
(50, 'New Booking Request', 'User 8 has submitted a new booking request:\n\nFacility: Library Discussion Room 1\nDate: 2025-12-22\nTime: 12:30 - 13:30\nBooking ID: #16', 'info', 'medium', 8, 'specific', '[1]', '2025-12-21 05:36:55', NULL, 1, '2025-12-21 05:36:55', '2025-12-21 05:36:55'),
(51, 'Booking Approved', 'Your booking has been approved!\n\nFacility: Library Discussion Room 1\nDate: 2025-12-22\nTime: 12:30 - 13:30\nBooking ID: 16', 'success', 'medium', 1, 'specific', '[8]', '2025-12-21 05:37:29', NULL, 1, '2025-12-21 05:37:29', '2025-12-21 05:37:29'),
(52, 'New Feedback Submitted - General Feedback', 'User 8 has submitted a new feedback:\n\nSubject: sasasas\nType: General Feedback\nRating: 1/5\nFeedback ID: #14', 'info', 'medium', 8, 'specific', '[1]', '2025-12-21 05:42:41', NULL, 1, '2025-12-21 05:42:41', '2025-12-21 05:42:41'),
(53, 'New Feedback Submitted - Compliment', 'User 8 has submitted a new feedback:\n\nSubject: sasasas\nType: Compliment\nRating: 4/5\nFeedback ID: #15', 'info', 'medium', 8, 'specific', '[1]', '2025-12-21 05:43:00', NULL, 1, '2025-12-21 05:43:00', '2025-12-21 05:43:00'),
(54, 'New Booking Request', 'User 2 has submitted a new booking request:\n\nFacility: Badminton Court 2\nDate: 2025-12-24\nTime: 21:00 - 22:00\nBooking ID: #21', 'info', 'medium', 2, 'specific', '[1]', '2025-12-21 06:34:21', NULL, 1, '2025-12-21 06:34:21', '2025-12-21 06:34:21');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(5, 'App\\Models\\User', 15, 'web_session', '434e8281dc548c58e12dcd7536cbcd42e9abc0e4bf20537a17dfb5fcdc5c3933', '[\"*\"]', '2025-12-18 11:08:46', NULL, '2025-12-18 10:05:36', '2025-12-18 11:08:46'),
(52, 'App\\Models\\User', 1, 'web_session', '3b3f51991c441220bb7b878aa4666e584f5e127de6bd42b8daefbb2dd1c21c76', '[\"*\"]', '2025-12-21 06:46:47', NULL, '2025-12-21 05:40:06', '2025-12-21 06:46:47'),
(53, 'App\\Models\\User', 2, 'web_session', '721bb318fc904029f18b87751c516e9b422a2753955d27736c271770f9cb5a13', '[\"*\"]', '2025-12-21 06:46:47', NULL, '2025-12-21 06:19:26', '2025-12-21 06:46:47');

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `points_required` int(11) NOT NULL,
  `reward_type` enum('certificate','badge','privilege','physical') NOT NULL DEFAULT 'certificate',
  `image_url` longtext DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`id`, `name`, `description`, `points_required`, `reward_type`, `image_url`, `stock_quantity`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Water Bottle', 'Reusable water bottle for daily use', 200, 'physical', NULL, 50, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(2, 'Sports Towel', 'Microfiber towel suitable for gym and sports activities', 180, 'physical', NULL, 40, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(3, 'Facility Locker Padlock', 'Personal padlock for facility locker usage', 150, 'physical', NULL, 28, 1, '2025-12-21 06:23:14', '2025-12-21 05:23:31'),
(4, 'Gym Bag', 'Lightweight gym bag for sports equipment', 350, 'physical', NULL, 20, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(5, 'Yoga Mat', 'Non-slip yoga mat for fitness and wellness activities', 400, 'physical', NULL, 15, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(6, 'Sports Wristband', 'Comfortable wristband for sports activities', 120, 'physical', NULL, 60, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(7, 'Shuttlecock Set', 'Set of shuttlecocks for badminton facility users', 250, 'physical', NULL, 25, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(8, 'Resistance Band', 'Fitness resistance band for training sessions', 300, 'physical', NULL, 20, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(9, 'Gym Gloves', 'Protective gloves for gym equipment usage', 280, 'physical', NULL, 25, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(10, 'Sports Socks', 'Breathable sports socks for active users', 160, 'physical', NULL, 45, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(11, 'Most Active Facility User', 'Certificate awarded to the user with the highest facility bookings', 500, 'certificate', NULL, NULL, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(12, 'Top Sports Facility Booker', 'Certificate for frequent sports facility reservations', 450, 'certificate', NULL, NULL, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(13, 'Gym Commitment Award', 'Certificate for consistent gym facility usage', 400, 'certificate', NULL, NULL, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(14, 'Early Bird Booker', 'Certificate for users who frequently book facilities early', 350, 'certificate', NULL, NULL, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(15, 'Facility Ambassador', 'Certificate recognizing responsible facility usage', 600, 'certificate', NULL, NULL, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(16, 'Most Active Player', 'Certificate awarded to the most active sports facility player', 550, 'certificate', NULL, NULL, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(17, 'Consistent Facility User', 'Certificate for long-term consistent facility bookings', 420, 'certificate', NULL, NULL, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(18, 'Peak Hour Contributor', 'Certificate for frequent usage during peak facility hours', 380, 'certificate', NULL, NULL, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(19, 'Facility Supporter', 'Certificate for positive participation in facility activities', 300, 'certificate', NULL, NULL, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14'),
(20, 'Outstanding Facility User', 'Certificate recognizing excellent facility usage behavior', 650, 'certificate', NULL, NULL, 1, '2025-12-21 06:23:14', '2025-12-21 06:23:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `personal_id` varchar(10) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'student',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `otp_code` varchar(255) DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `personal_id`, `password`, `remember_token`, `phone_number`, `address`, `role`, `status`, `otp_code`, `otp_expires_at`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'admin', 'active', NULL, NULL, '2025-12-21 05:40:06', '2025-12-18 15:19:39', '2025-12-21 05:40:06'),
(2, 'User 2', 'user1@gmail.com', '25WMR00002', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'student', 'active', NULL, NULL, '2025-12-21 06:19:26', '2025-12-18 15:19:39', '2025-12-21 06:19:26'),
(3, 'User 3', 'user2@gmail.com', '25WMR00003', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'student', 'active', NULL, NULL, '2025-12-21 03:02:05', '2025-12-18 15:19:39', '2025-12-21 03:02:05'),
(4, 'User 4', 'user3@gmail.com', '25WMR00004', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'student', 'active', NULL, NULL, '2025-12-21 05:28:29', '2025-12-18 15:19:39', '2025-12-21 05:28:29'),
(5, 'User 5', 'user4@gmail.com', '25WMR00005', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'student', 'active', NULL, NULL, '2025-12-21 05:36:14', '2025-12-18 15:19:39', '2025-12-21 05:36:14'),
(6, 'User 6', 'user5@gmail.com', '25WMR00006', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'student', 'active', NULL, NULL, NULL, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(7, 'User 7', 'user6@gmail.com', '25WMR00007', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'student', 'active', NULL, NULL, NULL, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(8, 'User 8', 'user7@gmail.com', '25WMR00008', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'student', 'active', NULL, NULL, '2025-12-21 05:31:38', '2025-12-18 15:19:39', '2025-12-21 05:31:38'),
(9, 'User 9', 'user8@gmail.com', '25WMR00009', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'student', 'active', NULL, NULL, NULL, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(10, 'User 10', 'user9@gmail.com', '25WMR00010', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'student', 'active', NULL, NULL, NULL, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(11, 'Liew Zi Li', 'liewzl-wm22@student.tarc.edu.my', 'p0001', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'staff', 'active', NULL, NULL, '2025-12-18 07:26:23', '2025-12-18 15:19:39', '2025-12-18 07:26:23'),
(12, 'Boo Kai Jie', 'bookj-wm22@student.tarc.edu.my', 'p0002', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'staff', 'active', NULL, NULL, '2025-12-18 22:17:08', '2025-12-18 15:19:39', '2025-12-18 22:17:08'),
(13, 'Low Kim Hong', 'lowkh-wm22@student.tarc.edu.my', 'p0003', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'staff', 'active', NULL, NULL, NULL, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(14, 'Ng Jhun Hou', 'ngjh-wm22@student.tarc.edu.my', 'p0004', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'staff', 'active', NULL, NULL, NULL, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(15, 'Staff 5', 'staff5@gmail.com', 'p0005', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'staff', 'active', NULL, NULL, '2025-12-18 10:05:36', '2025-12-18 15:19:39', '2025-12-18 10:05:36'),
(16, 'Staff 6', 'staff6@gmail.com', 'p0006', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'staff', 'active', NULL, NULL, NULL, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(17, 'Staff 7', 'staff7@gmail.com', 'p0007', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'staff', 'active', NULL, NULL, NULL, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(18, 'Staff 8', 'staff8@gmail.com', 'p0008', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'staff', 'active', NULL, NULL, NULL, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(19, 'Staff 9', 'staff9@gmail.com', 'p0009', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'staff', 'active', NULL, NULL, NULL, '2025-12-18 15:19:39', '2025-12-18 15:19:39'),
(20, 'Staff 10', 'staff10@gmail.com', 'p0010', '$2y$12$DXdT96meidlDdOW7Bv1wLuyuheZ6kL1Taj0wslN2jOSpGDX5pa45u', NULL, NULL, NULL, 'staff', 'active', NULL, NULL, NULL, '2025-12-18 15:19:39', '2025-12-18 15:19:39');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_logs`
--

CREATE TABLE `user_activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_activity_logs`
--

INSERT INTO `user_activity_logs` (`id`, `user_id`, `action`, `ip_address`, `user_agent`, `description`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 07:25:51', '2025-12-18 07:25:51'),
(2, 11, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 07:26:23', '2025-12-18 07:26:23'),
(3, 11, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 07:27:02', '2025-12-18 07:27:02'),
(4, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 07:27:12', '2025-12-18 07:27:12'),
(5, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 08:01:21', '2025-12-18 08:01:21'),
(6, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 08:01:30', '2025-12-18 08:01:30'),
(7, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 10:05:20', '2025-12-18 10:05:20'),
(8, 15, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 10:05:36', '2025-12-18 10:05:36'),
(9, 15, 'create_booking', NULL, NULL, 'Created booking for facility: Lecture Hall DK1 on 2025-12-22', NULL, '2025-12-18 10:24:14', '2025-12-18 10:24:14'),
(10, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 20:11:47', '2025-12-18 20:11:47'),
(11, 12, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 20:12:06', '2025-12-18 20:12:06'),
(12, 12, 'create_booking', NULL, NULL, 'Created booking for facility: Badminton Court 1 on 2025-12-20', NULL, '2025-12-18 20:12:29', '2025-12-18 20:12:29'),
(13, 12, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 21:25:15', '2025-12-18 21:25:15'),
(14, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 22:16:51', '2025-12-18 22:16:51'),
(15, 12, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 22:17:08', '2025-12-18 22:17:08'),
(16, 12, 'create_booking', NULL, NULL, 'Created booking for facility: Badminton Court 1 on 2025-12-20', NULL, '2025-12-18 22:17:49', '2025-12-18 22:17:49'),
(17, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 22:20:18', '2025-12-18 22:20:18'),
(18, 12, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 22:34:45', '2025-12-18 22:34:45'),
(19, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-18 22:35:01', '2025-12-18 22:35:01'),
(20, 2, 'create_booking', NULL, NULL, 'Created booking for facility: Volleyball Court 3 on 2025-12-20', NULL, '2025-12-18 22:47:15', '2025-12-18 22:47:15'),
(21, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-19 01:44:45', '2025-12-19 01:44:45'),
(22, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-19 01:46:08', '2025-12-19 01:46:08'),
(23, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-19 01:47:44', '2025-12-19 01:47:44'),
(24, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-19 01:47:52', '2025-12-19 01:47:52'),
(25, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-19 01:48:01', '2025-12-19 01:48:01'),
(26, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-19 01:48:08', '2025-12-19 01:48:08'),
(27, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-19 01:48:29', '2025-12-19 01:48:29'),
(28, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-19 01:48:38', '2025-12-19 01:48:38'),
(29, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-19 01:48:50', '2025-12-19 01:48:50'),
(30, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-19 01:48:57', '2025-12-19 01:48:57'),
(31, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-19 07:12:22', '2025-12-19 07:12:22'),
(32, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 07:10:52', '2025-12-20 07:10:52'),
(33, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 07:23:10', '2025-12-20 07:23:10'),
(34, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 07:35:11', '2025-12-20 07:35:11'),
(35, 3, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 07:35:20', '2025-12-20 07:35:20'),
(36, 3, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 07:36:39', '2025-12-20 07:36:39'),
(37, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 07:36:46', '2025-12-20 07:36:46'),
(38, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 08:01:41', '2025-12-20 08:01:41'),
(39, 3, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 08:01:52', '2025-12-20 08:01:52'),
(40, 3, 'create_booking', NULL, NULL, 'Created booking for facility: Library Discussion Room 1 on 2025-12-22', NULL, '2025-12-20 08:03:43', '2025-12-20 08:03:43'),
(41, 3, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 08:03:58', '2025-12-20 08:03:58'),
(42, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 08:04:05', '2025-12-20 08:04:05'),
(43, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 08:05:38', '2025-12-20 08:05:38'),
(44, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 08:05:46', '2025-12-20 08:05:46'),
(45, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 08:15:45', '2025-12-20 08:15:45'),
(46, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 08:15:50', '2025-12-20 08:15:50'),
(47, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 08:18:41', '2025-12-20 08:18:41'),
(48, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 08:18:46', '2025-12-20 08:18:46'),
(49, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 12:42:28', '2025-12-20 12:42:28'),
(50, 2, 'create_booking', NULL, NULL, 'Created booking for facility: Library Discussion Room 2 on 2025-12-22', NULL, '2025-12-20 12:44:10', '2025-12-20 12:44:10'),
(51, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 12:44:23', '2025-12-20 12:44:23'),
(52, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 12:44:32', '2025-12-20 12:44:32'),
(53, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 12:45:13', '2025-12-20 12:45:13'),
(54, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 13:37:30', '2025-12-20 13:37:30'),
(55, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 13:37:37', '2025-12-20 13:37:37'),
(56, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 13:39:06', '2025-12-20 13:39:06'),
(57, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 13:39:16', '2025-12-20 13:39:16'),
(58, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 13:39:47', '2025-12-20 13:39:47'),
(59, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 13:39:53', '2025-12-20 13:39:53'),
(60, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 13:47:33', '2025-12-20 13:47:33'),
(61, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 22:19:35', '2025-12-20 22:19:35'),
(62, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 22:29:21', '2025-12-20 22:29:21'),
(63, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 22:32:18', '2025-12-20 22:32:18'),
(64, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-20 22:32:27', '2025-12-20 22:32:27'),
(65, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 02:27:44', '2025-12-21 02:27:44'),
(66, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 02:28:38', '2025-12-21 02:28:38'),
(67, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 02:28:44', '2025-12-21 02:28:44'),
(68, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 02:36:40', '2025-12-21 02:36:40'),
(69, 3, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 02:36:46', '2025-12-21 02:36:46'),
(70, 3, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 02:43:40', '2025-12-21 02:43:40'),
(71, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 02:43:49', '2025-12-21 02:43:49'),
(72, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 03:01:59', '2025-12-21 03:01:59'),
(73, 3, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 03:02:05', '2025-12-21 03:02:05'),
(74, 3, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:00:50', '2025-12-21 05:00:50'),
(75, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:00:56', '2025-12-21 05:00:56'),
(76, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:01:08', '2025-12-21 05:01:08'),
(77, 2, 'create_booking', NULL, NULL, 'Created booking for facility: Gym Room 1 (General) on 2025-12-22', NULL, '2025-12-21 05:04:26', '2025-12-21 05:04:26'),
(78, 2, 'create_booking', NULL, NULL, 'Created booking for facility: Gym Room 1 (General) on 2025-12-22', NULL, '2025-12-21 05:06:13', '2025-12-21 05:06:13'),
(79, 2, 'create_booking', NULL, NULL, 'Created booking for facility: Volleyball Court 3 on 2025-12-22', NULL, '2025-12-21 05:19:19', '2025-12-21 05:19:19'),
(80, 2, 'create_booking', NULL, NULL, 'Created booking for facility: Library Discussion Room 2 on 2025-12-22', NULL, '2025-12-21 05:21:00', '2025-12-21 05:21:00'),
(81, 2, 'create_booking', NULL, NULL, 'Created booking for facility: Library Multimedia Zone on 2025-12-22', NULL, '2025-12-21 05:26:17', '2025-12-21 05:26:17'),
(82, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:28:22', '2025-12-21 05:28:22'),
(83, 4, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:28:29', '2025-12-21 05:28:29'),
(84, 2, 'create_booking', NULL, NULL, 'Created booking for facility: Library Discussion Room 1 on 2025-12-22', NULL, '2025-12-21 05:29:13', '2025-12-21 05:29:13'),
(85, 4, 'create_booking', NULL, NULL, 'Created booking for facility: Library Discussion Room 1 on 2025-12-22', NULL, '2025-12-21 05:29:14', '2025-12-21 05:29:14'),
(86, 4, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:29:44', '2025-12-21 05:29:44'),
(87, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:29:51', '2025-12-21 05:29:51'),
(88, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:30:40', '2025-12-21 05:30:40'),
(89, 5, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:30:48', '2025-12-21 05:30:48'),
(90, 2, 'create_booking', NULL, NULL, 'Created booking for facility: Labubu on 2025-12-22', NULL, '2025-12-21 05:31:20', '2025-12-21 05:31:20'),
(91, 5, 'create_booking', NULL, NULL, 'Created booking for facility: Labubu on 2025-12-22', NULL, '2025-12-21 05:31:21', '2025-12-21 05:31:21'),
(92, 2, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:31:30', '2025-12-21 05:31:30'),
(93, 8, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:31:38', '2025-12-21 05:31:38'),
(94, 5, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:33:48', '2025-12-21 05:33:48'),
(95, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:33:57', '2025-12-21 05:33:57'),
(96, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:35:40', '2025-12-21 05:35:40'),
(97, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:35:45', '2025-12-21 05:35:45'),
(98, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:36:07', '2025-12-21 05:36:07'),
(99, 5, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:36:14', '2025-12-21 05:36:14'),
(100, 5, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:36:31', '2025-12-21 05:36:31'),
(101, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:36:40', '2025-12-21 05:36:40'),
(102, 8, 'create_booking', NULL, NULL, 'Created booking for facility: Library Discussion Room 1 on 2025-12-22', NULL, '2025-12-21 05:36:55', '2025-12-21 05:36:55'),
(103, 1, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:40:00', '2025-12-21 05:40:00'),
(104, 1, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 05:40:06', '2025-12-21 05:40:06'),
(105, 8, 'logout', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 06:19:21', '2025-12-21 06:19:21'),
(106, 2, 'login', '127.0.0.1', NULL, NULL, NULL, '2025-12-21 06:19:26', '2025-12-21 06:19:26'),
(107, 2, 'create_booking', NULL, NULL, 'Created booking for facility: Badminton Court 2 on 2025-12-24', NULL, '2025-12-21 06:34:21', '2025-12-21 06:34:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_announcement`
--

CREATE TABLE `user_announcement` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `announcement_id` bigint(20) UNSIGNED NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_starred` tinyint(1) NOT NULL DEFAULT 0,
  `starred_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_notification`
--

CREATE TABLE `user_notification` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `notification_id` bigint(20) UNSIGNED NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `is_starred` tinyint(1) NOT NULL DEFAULT 0,
  `starred_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_notification`
--

INSERT INTO `user_notification` (`id`, `user_id`, `notification_id`, `is_read`, `read_at`, `is_acknowledged`, `acknowledged_at`, `is_starred`, `starred_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '2025-12-18 10:25:06', 0, NULL, 0, NULL, '2025-12-18 07:47:53', '2025-12-18 10:25:06'),
(2, 1, 2, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 07:49:52', '2025-12-18 07:49:52'),
(3, 3, 3, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 08:04:47', '2025-12-18 08:04:47'),
(4, 1, 4, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 10:24:14', '2025-12-18 10:24:14'),
(5, 1, 5, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 20:12:29', '2025-12-18 20:12:29'),
(6, 1, 6, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 20:27:02', '2025-12-18 20:27:02'),
(7, 12, 7, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 20:27:59', '2025-12-18 20:27:59'),
(8, 1, 8, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 21:27:08', '2025-12-18 21:27:08'),
(9, 1, 9, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 22:17:49', '2025-12-18 22:17:49'),
(10, 1, 10, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 22:19:59', '2025-12-18 22:19:59'),
(11, 12, 11, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 22:23:45', '2025-12-18 22:23:45'),
(12, 1, 12, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 22:37:11', '2025-12-18 22:37:11'),
(13, 1, 13, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 22:47:15', '2025-12-18 22:47:15'),
(14, 2, 14, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 22:47:33', '2025-12-18 22:47:33'),
(15, 2, 15, 0, NULL, 0, NULL, 0, NULL, '2025-12-18 22:47:43', '2025-12-18 22:47:43'),
(16, 1, 16, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 07:31:10', '2025-12-20 07:31:10'),
(17, 1, 17, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 07:34:47', '2025-12-20 07:34:47'),
(18, 1, 18, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 07:36:38', '2025-12-20 07:36:38'),
(19, 1, 19, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 07:52:41', '2025-12-20 07:52:41'),
(20, 1, 20, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 08:03:43', '2025-12-20 08:03:43'),
(21, 1, 21, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 12:44:10', '2025-12-20 12:44:10'),
(22, 2, 22, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 12:44:56', '2025-12-20 12:44:56'),
(23, 1, 23, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 13:22:37', '2025-12-20 13:22:37'),
(24, 1, 24, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 13:22:52', '2025-12-20 13:22:52'),
(25, 1, 25, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 13:28:05', '2025-12-20 13:28:05'),
(26, 2, 26, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 13:37:46', '2025-12-20 13:37:46'),
(27, 2, 27, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 13:38:00', '2025-12-20 13:38:00'),
(28, 2, 28, 0, NULL, 0, NULL, 0, NULL, '2025-12-20 13:48:00', '2025-12-20 13:48:00'),
(29, 1, 29, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:04:26', '2025-12-21 05:04:26'),
(30, 2, 30, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:04:49', '2025-12-21 05:04:49'),
(31, 2, 31, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:05:24', '2025-12-21 05:05:24'),
(32, 1, 32, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:06:13', '2025-12-21 05:06:13'),
(33, 2, 33, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:06:23', '2025-12-21 05:06:23'),
(34, 2, 34, 1, '2025-12-21 05:21:10', 0, NULL, 0, NULL, '2025-12-21 05:06:32', '2025-12-21 05:21:10'),
(35, 1, 35, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:18:49', '2025-12-21 05:18:49'),
(36, 1, 36, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:19:19', '2025-12-21 05:19:19'),
(37, 1, 37, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:21:00', '2025-12-21 05:21:00'),
(38, 2, 38, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:24:01', '2025-12-21 05:24:01'),
(39, 2, 39, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:25:21', '2025-12-21 05:25:21'),
(40, 1, 40, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:26:17', '2025-12-21 05:26:17'),
(41, 2, 41, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:28:01', '2025-12-21 05:28:01'),
(42, 2, 42, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:28:08', '2025-12-21 05:28:08'),
(43, 1, 43, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:29:13', '2025-12-21 05:29:13'),
(44, 1, 44, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:29:14', '2025-12-21 05:29:14'),
(45, 1, 45, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:31:20', '2025-12-21 05:31:20'),
(46, 1, 46, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:31:21', '2025-12-21 05:31:21'),
(47, 1, 47, 1, '2025-12-21 05:41:35', 0, NULL, 0, NULL, '2025-12-21 05:34:35', '2025-12-21 05:41:35'),
(48, 5, 48, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:35:58', '2025-12-21 05:35:58'),
(49, 2, 49, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:36:03', '2025-12-21 05:36:03'),
(50, 1, 50, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:36:55', '2025-12-21 05:36:55'),
(51, 8, 51, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:37:29', '2025-12-21 05:37:29'),
(52, 1, 52, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:42:41', '2025-12-21 05:42:41'),
(53, 1, 53, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 05:43:00', '2025-12-21 05:43:00'),
(54, 1, 54, 0, NULL, 0, NULL, 0, NULL, '2025-12-21 06:34:21', '2025-12-21 06:34:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_reward`
--

CREATE TABLE `user_reward` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `reward_id` bigint(20) UNSIGNED NOT NULL,
  `points_used` int(11) NOT NULL,
  `status` enum('pending','approved','redeemed','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `redeemed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcements_created_by_foreign` (`created_by`),
  ADD KEY `announcements_type_index` (`type`),
  ADD KEY `announcements_target_audience_index` (`target_audience`),
  ADD KEY `announcements_published_at_index` (`published_at`),
  ADD KEY `announcements_expires_at_index` (`expires_at`),
  ADD KEY `announcements_is_active_index` (`is_active`),
  ADD KEY `announcements_is_pinned_index` (`is_pinned`);

--
-- Indexes for table `attendees`
--
ALTER TABLE `attendees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendees_booking_id_index` (`booking_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bookings_approved_by_foreign` (`approved_by`),
  ADD KEY `bookings_user_id_index` (`user_id`),
  ADD KEY `bookings_facility_id_index` (`facility_id`),
  ADD KEY `bookings_booking_date_index` (`booking_date`),
  ADD KEY `bookings_status_index` (`status`);

--
-- Indexes for table `booking_slots`
--
ALTER TABLE `booking_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_slots_booking_id_index` (`booking_id`),
  ADD KEY `booking_slots_slot_date_start_time_end_time_index` (`slot_date`,`start_time`,`end_time`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificates_certificate_number_unique` (`certificate_number`),
  ADD KEY `certificates_reward_id_foreign` (`reward_id`),
  ADD KEY `certificates_user_id_index` (`user_id`),
  ADD KEY `certificates_certificate_number_index` (`certificate_number`),
  ADD KEY `certificates_status_index` (`status`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `facilities_code_unique` (`code`),
  ADD KEY `facilities_type_index` (`type`),
  ADD KEY `facilities_status_index` (`status`),
  ADD KEY `facilities_code_index` (`code`),
  ADD KEY `facilities_is_deleted_index` (`is_deleted`),
  ADD KEY `facilities_created_by_foreign` (`created_by`),
  ADD KEY `facilities_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `feedbacks_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `feedbacks_user_id_index` (`user_id`),
  ADD KEY `feedbacks_facility_id_index` (`facility_id`),
  ADD KEY `feedbacks_booking_id_index` (`booking_id`),
  ADD KEY `feedbacks_status_index` (`status`),
  ADD KEY `feedbacks_type_index` (`type`);

--
-- Indexes for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loyalty_points_user_id_index` (`user_id`),
  ADD KEY `loyalty_points_action_type_index` (`action_type`),
  ADD KEY `loyalty_points_related_id_related_type_index` (`related_id`,`related_type`);

--
-- Indexes for table `loyalty_rules`
--
ALTER TABLE `loyalty_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loyalty_rules_action_type_unique` (`action_type`),
  ADD KEY `loyalty_rules_action_type_index` (`action_type`),
  ADD KEY `loyalty_rules_is_active_index` (`is_active`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_created_by_foreign` (`created_by`),
  ADD KEY `notifications_type_index` (`type`),
  ADD KEY `notifications_target_audience_index` (`target_audience`),
  ADD KEY `notifications_scheduled_at_index` (`scheduled_at`),
  ADD KEY `notifications_is_active_index` (`is_active`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_studentid_unique` (`personal_id`);

--
-- Indexes for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_activity_logs_user_id_index` (`user_id`),
  ADD KEY `user_activity_logs_action_index` (`action`),
  ADD KEY `user_activity_logs_created_at_index` (`created_at`);

--
-- Indexes for table `user_announcement`
--
ALTER TABLE `user_announcement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_announcement_user_id_announcement_id_unique` (`user_id`,`announcement_id`),
  ADD KEY `user_announcement_announcement_id_foreign` (`announcement_id`),
  ADD KEY `user_announcement_is_read_index` (`is_read`),
  ADD KEY `user_announcement_read_at_index` (`read_at`),
  ADD KEY `user_announcement_is_starred_index` (`is_starred`);

--
-- Indexes for table `user_notification`
--
ALTER TABLE `user_notification`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_notification_user_id_notification_id_unique` (`user_id`,`notification_id`),
  ADD KEY `user_notification_notification_id_foreign` (`notification_id`),
  ADD KEY `user_notification_is_read_index` (`is_read`),
  ADD KEY `user_notification_is_starred_index` (`is_starred`);

--
-- Indexes for table `user_reward`
--
ALTER TABLE `user_reward`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_reward_reward_id_foreign` (`reward_id`),
  ADD KEY `user_reward_approved_by_foreign` (`approved_by`),
  ADD KEY `user_reward_user_id_index` (`user_id`),
  ADD KEY `user_reward_status_index` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `attendees`
--
ALTER TABLE `attendees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `booking_slots`
--
ALTER TABLE `booking_slots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `loyalty_rules`
--
ALTER TABLE `loyalty_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `user_announcement`
--
ALTER TABLE `user_announcement`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `user_notification`
--
ALTER TABLE `user_notification`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `user_reward`
--
ALTER TABLE `user_reward`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendees`
--
ALTER TABLE `attendees`
  ADD CONSTRAINT `attendees_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_slots`
--
ALTER TABLE `booking_slots`
  ADD CONSTRAINT `booking_slots_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_reward_id_foreign` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `certificates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `facilities`
--
ALTER TABLE `facilities`
  ADD CONSTRAINT `facilities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `facilities_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `feedbacks_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `feedbacks_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `feedbacks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_points`
--
ALTER TABLE `loyalty_points`
  ADD CONSTRAINT `loyalty_points_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD CONSTRAINT `user_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_announcement`
--
ALTER TABLE `user_announcement`
  ADD CONSTRAINT `user_announcement_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_announcement_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notification`
--
ALTER TABLE `user_notification`
  ADD CONSTRAINT `user_notification_notification_id_foreign` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_notification_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_reward`
--
ALTER TABLE `user_reward`
  ADD CONSTRAINT `user_reward_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_reward_reward_id_foreign` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_reward_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
