-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 24, 2026 at 05:33 AM
-- Server version: 8.4.10-10
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `koperasi`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint UNSIGNED NOT NULL,
  `log_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint UNSIGNED DEFAULT NULL,
  `causer_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint UNSIGNED DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auto_interest_run_logs`
--

CREATE TABLE `auto_interest_run_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `period` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `triggered_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','success','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `triggered_by` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
  `savings_result` json DEFAULT NULL,
  `deposit_result` json DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `auto_interest_run_logs`
--

INSERT INTO `auto_interest_run_logs` (`id`, `period`, `triggered_at`, `status`, `triggered_by`, `savings_result`, `deposit_result`, `error_message`, `created_at`, `updated_at`) VALUES
(1, '2026-08', '2026-09-23 16:15:02', 'failed', 'auto', NULL, NULL, 'Savings interest processing failed: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'is_default\' in \'where clause\' (SQL: select * from `interest_rates` where `type` = simpanan and `is_active` = 1 and `is_default` = 1 limit 1)', '2026-09-12 11:10:48', '2026-09-23 16:15:02');

-- --------------------------------------------------------

--
-- Table structure for table `collaterals`
--

CREATE TABLE `collaterals` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` bigint UNSIGNED NOT NULL DEFAULT '0',
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint UNSIGNED NOT NULL,
  `nik` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('L','P') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L',
  `birth` date DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_education` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profession` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','blacklist') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `interest_rate_id` bigint UNSIGNED DEFAULT NULL,
  `photo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joined_at` datetime DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_interest_accumulations`
--

CREATE TABLE `daily_interest_accumulations` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `savings_type` enum('simpanan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'simpanan',
  `calculation_date` date NOT NULL,
  `base_balance` bigint UNSIGNED NOT NULL,
  `rate_percent` decimal(5,2) NOT NULL,
  `interest_amount` bigint UNSIGNED NOT NULL,
  `is_posted` tinyint(1) NOT NULL DEFAULT '0',
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` bigint UNSIGNED NOT NULL,
  `type` enum('simpanan','penarikan','bunga') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'simpanan',
  `amount` bigint UNSIGNED NOT NULL,
  `previous_balance` bigint UNSIGNED NOT NULL DEFAULT '0',
  `current_balance` bigint UNSIGNED NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `validated_at` timestamp NULL DEFAULT NULL,
  `validated_by` bigint UNSIGNED DEFAULT NULL,
  `is_system_generated` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'True if generated by InterestSyncEngine',
  `period` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Period for bunga (YYYY-MM)',
  `customer_id` bigint UNSIGNED NOT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deposit_interest_payments`
--

CREATE TABLE `deposit_interest_payments` (
  `id` bigint UNSIGNED NOT NULL,
  `fixed_deposit_id` bigint UNSIGNED NOT NULL,
  `period` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interest_amount` bigint UNSIGNED NOT NULL,
  `savings_txn_id` bigint UNSIGNED DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixed_deposits`
--

CREATE TABLE `fixed_deposits` (
  `id` bigint UNSIGNED NOT NULL,
  `number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `amount` bigint UNSIGNED NOT NULL,
  `tenor_months` tinyint UNSIGNED NOT NULL,
  `rate_percent` decimal(5,2) NOT NULL,
  `start_date` date NOT NULL,
  `maturity_date` date NOT NULL,
  `status` enum('active','matured','extended','liquidated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `extended_from_id` bigint UNSIGNED DEFAULT NULL,
  `liquidated_at` timestamp NULL DEFAULT NULL,
  `matured_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `validated_at` timestamp NULL DEFAULT NULL,
  `validated_by` bigint UNSIGNED DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `foreclosures`
--

CREATE TABLE `foreclosures` (
  `id` bigint UNSIGNED NOT NULL,
  `date` datetime NOT NULL,
  `collateral_amount` bigint UNSIGNED NOT NULL DEFAULT '0',
  `remaining_amount` bigint UNSIGNED NOT NULL DEFAULT '0',
  `return_amount` bigint UNSIGNED NOT NULL DEFAULT '0',
  `customer_id` bigint UNSIGNED NOT NULL,
  `visit_id` bigint UNSIGNED NOT NULL,
  `collateral_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('holiday','workday') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'holiday',
  `year` smallint UNSIGNED NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `date`, `name`, `type`, `year`, `description`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '2026-08-05', '1', 'holiday', 2026, NULL, 1, NULL, '2026-08-05 22:11:19', '2026-08-06 10:44:50', '2026-08-06 10:44:50'),
(2, '2026-08-17', 'minggu', 'holiday', 2026, NULL, 1, 1, '2026-08-06 11:46:05', '2026-09-05 11:03:42', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `interest_engine_logs`
--

CREATE TABLE `interest_engine_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `run_date` date NOT NULL,
  `status` enum('success','skipped','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_customers` int UNSIGNED NOT NULL DEFAULT '0',
  `total_interest` bigint UNSIGNED NOT NULL DEFAULT '0',
  `duration_seconds` int UNSIGNED DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interest_posting_logs`
--

CREATE TABLE `interest_posting_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `period` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('success','failed','manual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_customers` int UNSIGNED NOT NULL DEFAULT '0',
  `total_interest` bigint UNSIGNED NOT NULL DEFAULT '0',
  `posted_by` bigint UNSIGNED DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interest_rates`
--

CREATE TABLE `interest_rates` (
  `id` bigint UNSIGNED NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rate_percent` decimal(5,2) NOT NULL,
  `effective_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interest_sync_runs`
--

CREATE TABLE `interest_sync_runs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL COMMENT 'User who triggered the sync',
  `sync_from_date` date NOT NULL COMMENT 'Earliest date to catch up from',
  `sync_to_date` date NOT NULL COMMENT 'Latest date to sync to',
  `status` enum('pending','running','success','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `customers_processed` int NOT NULL DEFAULT '0',
  `total_interest_calculated` int NOT NULL DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `duration_seconds` int DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2022_06_15_114258_add_gender_columns_to_users_table', 1),
(6, '2022_06_18_000725_create_customers_table', 1),
(7, '2022_06_18_160457_create_collaterals_table', 1),
(8, '2022_06_19_094908_create_deposits_table', 1),
(9, '2022_06_25_112144_create_visits_table', 1),
(10, '2022_06_25_140614_create_foreclosures_table', 1),
(11, '2026_08_05_180355_create_activity_log_table', 1),
(12, '2026_08_05_180356_add_event_column_to_activity_log_table', 1),
(13, '2026_08_05_180357_add_batch_uuid_column_to_activity_log_table', 1),
(14, '2026_08_05_000001_create_holidays_table', 2),
(15, '2026_08_05_000002_create_workday_year_counts_table', 2),
(16, '2026_08_05_000003_create_interest_rates_table', 2),
(17, '2026_08_05_000004_create_daily_interest_accumulations_table', 2),
(18, '2026_08_05_000005_create_interest_engine_logs_table', 2),
(19, '2026_08_05_000006_create_interest_posting_logs_table', 2),
(20, '2026_08_05_000007_create_fixed_deposits_table', 3),
(21, '2026_08_05_000008_create_deposit_interest_payments_table', 3),
(22, '2026_08_05_000008_add_interest_rate_id_to_customers_table', 4),
(23, '2026_08_06_000001_update_interest_rates_type_enum', 4),
(24, '2026_08_15_000001_alter_deposits_table_for_interest_sync', 5),
(25, '2026_08_15_000002_create_interest_sync_runs_table', 5),
(26, '2026_08_21_000001_simplify_savings_and_interest_types', 6),
(27, '2026_08_23_000001_add_validation_columns_to_deposits_and_fixed_deposits_table', 7),
(28, '2026_09_06_000001_create_auto_interest_run_logs_table', 8),
(29, '2026_09_07_000001_add_account_number_to_fixed_deposits_table', 9),
(30, '2026_09_08_000001_fix_account_number_constraint_in_fixed_deposits_table', 10),
(31, '2026_09_08_000002_cleanup_fixed_deposits_account_numbers', 10);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `gender` enum('L','P') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L',
  `birth` date DEFAULT NULL,
  `last_education` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joined_at` datetime DEFAULT NULL,
  `role` enum('manager','teller','viewer','collector') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'teller',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `photo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `gender`, `birth`, `last_education`, `address`, `phone`, `joined_at`, `role`, `is_active`, `photo`, `deleted_at`) VALUES
(1, 'Drs Gatut Endratno', 'manajer', NULL, '$2y$10$YcSsW5gyv87Ke5easWTCGuMnK2kY.JoyryqHrwehHIe.mmx53RJfC', NULL, '2026-08-05 18:04:05', '2026-08-07 11:02:16', 'L', '1978-08-30', 'S-1', 'darungan', '085322655897', '2026-08-04 18:04:05', 'manager', 1, NULL, NULL),
(2, 'DYAH', 'admin', NULL, '$2a$12$APlZu9/jIZAQoy4K0g3v3OqbT6RyaWQMQJt2AU.hwDFyP7mAPVtzu', NULL, '2026-08-05 18:04:05', '2026-09-09 13:02:04', 'P', '1990-12-05', 'S-1', 'GADUNGAN', '085322655800', '2026-08-05 18:04:05', 'teller', 1, NULL, NULL),
(3, 'Collector', 'kolektor', NULL, '$2y$10$0uidg7j/Tlgk57Ww.sQ20.p68BhXVE7zPw5CSdvqR.UoQ9crAa3sS', NULL, '2026-08-05 18:04:05', '2026-08-05 18:04:05', 'L', NULL, NULL, NULL, '0823', '2026-08-05 18:04:05', 'collector', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` bigint UNSIGNED NOT NULL,
  `remaining_amount` bigint UNSIGNED NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workday_year_counts`
--

CREATE TABLE `workday_year_counts` (
  `id` bigint UNSIGNED NOT NULL,
  `year` smallint UNSIGNED NOT NULL,
  `workday_count` smallint UNSIGNED NOT NULL,
  `calculated_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workday_year_counts`
--

INSERT INTO `workday_year_counts` (`id`, `year`, `workday_count`, `calculated_at`, `created_at`, `updated_at`) VALUES
(1, 2026, 260, '2026-09-05 11:03:42', '2026-08-05 22:11:19', '2026-09-05 11:03:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject_type`,`subject_id`),
  ADD KEY `causer` (`causer_type`,`causer_id`),
  ADD KEY `activity_log_log_name_index` (`log_name`);

--
-- Indexes for table `auto_interest_run_logs`
--
ALTER TABLE `auto_interest_run_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `auto_interest_run_logs_period_unique` (`period`),
  ADD KEY `auto_interest_run_logs_period_index` (`period`),
  ADD KEY `auto_interest_run_logs_status_index` (`status`);

--
-- Indexes for table `collaterals`
--
ALTER TABLE `collaterals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `collaterals_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_nik_unique` (`nik`),
  ADD UNIQUE KEY `customers_number_unique` (`number`),
  ADD UNIQUE KEY `customers_phone_unique` (`phone`),
  ADD KEY `customers_created_by_foreign` (`created_by`),
  ADD KEY `customers_updated_by_foreign` (`updated_by`),
  ADD KEY `customers_interest_rate_id_foreign` (`interest_rate_id`);

--
-- Indexes for table `daily_interest_accumulations`
--
ALTER TABLE `daily_interest_accumulations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customer_date_type` (`customer_id`,`calculation_date`,`savings_type`),
  ADD KEY `daily_interest_accumulations_calculation_date_index` (`calculation_date`),
  ADD KEY `daily_interest_accumulations_is_posted_index` (`is_posted`);

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deposits_customer_id_foreign` (`customer_id`),
  ADD KEY `deposits_created_by_foreign` (`created_by`),
  ADD KEY `deposits_updated_by_foreign` (`updated_by`),
  ADD KEY `deposits_validated_by_foreign` (`validated_by`);

--
-- Indexes for table `deposit_interest_payments`
--
ALTER TABLE `deposit_interest_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_deposit_period` (`fixed_deposit_id`,`period`),
  ADD KEY `deposit_interest_payments_savings_txn_id_foreign` (`savings_txn_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `fixed_deposits`
--
ALTER TABLE `fixed_deposits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fixed_deposits_number_unique` (`number`),
  ADD UNIQUE KEY `unique_customer_account_number` (`customer_id`,`account_number`),
  ADD KEY `fixed_deposits_customer_id_index` (`customer_id`),
  ADD KEY `fixed_deposits_status_index` (`status`),
  ADD KEY `fixed_deposits_maturity_date_index` (`maturity_date`),
  ADD KEY `fixed_deposits_extended_from_id_foreign` (`extended_from_id`),
  ADD KEY `fixed_deposits_created_by_foreign` (`created_by`),
  ADD KEY `fixed_deposits_updated_by_foreign` (`updated_by`),
  ADD KEY `fixed_deposits_validated_by_foreign` (`validated_by`);

--
-- Indexes for table `foreclosures`
--
ALTER TABLE `foreclosures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `foreclosures_customer_id_foreign` (`customer_id`),
  ADD KEY `foreclosures_visit_id_foreign` (`visit_id`),
  ADD KEY `foreclosures_collateral_id_foreign` (`collateral_id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `holidays_date_unique` (`date`),
  ADD KEY `holidays_year_index` (`year`),
  ADD KEY `holidays_type_index` (`type`),
  ADD KEY `holidays_created_by_foreign` (`created_by`),
  ADD KEY `holidays_updated_by_foreign` (`updated_by`);

--
-- Indexes for table `interest_engine_logs`
--
ALTER TABLE `interest_engine_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `interest_engine_logs_run_date_unique` (`run_date`),
  ADD KEY `interest_engine_logs_status_index` (`status`);

--
-- Indexes for table `interest_posting_logs`
--
ALTER TABLE `interest_posting_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `interest_posting_logs_period_unique` (`period`),
  ADD KEY `interest_posting_logs_posted_by_foreign` (`posted_by`);

--
-- Indexes for table `interest_rates`
--
ALTER TABLE `interest_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interest_rates_type_is_active_index` (`type`,`is_active`),
  ADD KEY `interest_rates_effective_date_index` (`effective_date`),
  ADD KEY `interest_rates_created_by_foreign` (`created_by`);

--
-- Indexes for table `interest_sync_runs`
--
ALTER TABLE `interest_sync_runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interest_sync_runs_user_id_foreign` (`user_id`),
  ADD KEY `interest_sync_runs_status_index` (`status`),
  ADD KEY `interest_sync_runs_created_at_index` (`created_at`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visits_customer_id_foreign` (`customer_id`),
  ADD KEY `visits_user_id_foreign` (`user_id`);

--
-- Indexes for table `workday_year_counts`
--
ALTER TABLE `workday_year_counts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `workday_year_counts_year_unique` (`year`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auto_interest_run_logs`
--
ALTER TABLE `auto_interest_run_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `collaterals`
--
ALTER TABLE `collaterals`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `daily_interest_accumulations`
--
ALTER TABLE `daily_interest_accumulations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `deposit_interest_payments`
--
ALTER TABLE `deposit_interest_payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fixed_deposits`
--
ALTER TABLE `fixed_deposits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `foreclosures`
--
ALTER TABLE `foreclosures`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `interest_engine_logs`
--
ALTER TABLE `interest_engine_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `interest_posting_logs`
--
ALTER TABLE `interest_posting_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `interest_rates`
--
ALTER TABLE `interest_rates`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `interest_sync_runs`
--
ALTER TABLE `interest_sync_runs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workday_year_counts`
--
ALTER TABLE `workday_year_counts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `collaterals`
--
ALTER TABLE `collaterals`
  ADD CONSTRAINT `collaterals_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_interest_rate_id_foreign` FOREIGN KEY (`interest_rate_id`) REFERENCES `interest_rates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `daily_interest_accumulations`
--
ALTER TABLE `daily_interest_accumulations`
  ADD CONSTRAINT `daily_interest_accumulations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deposits`
--
ALTER TABLE `deposits`
  ADD CONSTRAINT `deposits_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `deposits_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `deposits_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `deposits_validated_by_foreign` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `deposit_interest_payments`
--
ALTER TABLE `deposit_interest_payments`
  ADD CONSTRAINT `deposit_interest_payments_fixed_deposit_id_foreign` FOREIGN KEY (`fixed_deposit_id`) REFERENCES `fixed_deposits` (`id`),
  ADD CONSTRAINT `deposit_interest_payments_savings_txn_id_foreign` FOREIGN KEY (`savings_txn_id`) REFERENCES `deposits` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fixed_deposits`
--
ALTER TABLE `fixed_deposits`
  ADD CONSTRAINT `fixed_deposits_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fixed_deposits_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fixed_deposits_extended_from_id_foreign` FOREIGN KEY (`extended_from_id`) REFERENCES `fixed_deposits` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fixed_deposits_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fixed_deposits_validated_by_foreign` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `foreclosures`
--
ALTER TABLE `foreclosures`
  ADD CONSTRAINT `foreclosures_collateral_id_foreign` FOREIGN KEY (`collateral_id`) REFERENCES `collaterals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `foreclosures_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `foreclosures_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `holidays`
--
ALTER TABLE `holidays`
  ADD CONSTRAINT `holidays_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `holidays_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `interest_posting_logs`
--
ALTER TABLE `interest_posting_logs`
  ADD CONSTRAINT `interest_posting_logs_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `interest_rates`
--
ALTER TABLE `interest_rates`
  ADD CONSTRAINT `interest_rates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `interest_sync_runs`
--
ALTER TABLE `interest_sync_runs`
  ADD CONSTRAINT `interest_sync_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `visits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
