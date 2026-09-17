-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: 76.13.22.242    Database: koperasi
-- ------------------------------------------------------
-- Server version	8.4.10-10

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `causer_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auto_interest_run_logs`
--

DROP TABLE IF EXISTS `auto_interest_run_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auto_interest_run_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `triggered_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','success','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `triggered_by` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
  `savings_result` json DEFAULT NULL,
  `deposit_result` json DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `auto_interest_run_logs_period_unique` (`period`),
  KEY `auto_interest_run_logs_period_index` (`period`),
  KEY `auto_interest_run_logs_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auto_interest_run_logs`
--

LOCK TABLES `auto_interest_run_logs` WRITE;
/*!40000 ALTER TABLE `auto_interest_run_logs` DISABLE KEYS */;
INSERT INTO `auto_interest_run_logs` VALUES (1,'2026-08','2026-09-16 13:18:33','failed','auto',NULL,NULL,'Savings interest processing failed: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'is_default\' in \'where clause\' (SQL: select * from `interest_rates` where `type` = simpanan and `is_active` = 1 and `is_default` = 1 limit 1)','2026-09-12 11:10:48','2026-09-16 13:18:33');
/*!40000 ALTER TABLE `auto_interest_run_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collaterals`
--

DROP TABLE IF EXISTS `collaterals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collaterals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` bigint unsigned NOT NULL DEFAULT '0',
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collaterals_customer_id_foreign` (`customer_id`),
  CONSTRAINT `collaterals_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaterals`
--

LOCK TABLES `collaterals` WRITE;
/*!40000 ALTER TABLE `collaterals` DISABLE KEYS */;
/*!40000 ALTER TABLE `collaterals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
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
  `interest_rate_id` bigint unsigned DEFAULT NULL,
  `photo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `joined_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_nik_unique` (`nik`),
  UNIQUE KEY `customers_number_unique` (`number`),
  UNIQUE KEY `customers_phone_unique` (`phone`),
  KEY `customers_created_by_foreign` (`created_by`),
  KEY `customers_updated_by_foreign` (`updated_by`),
  KEY `customers_interest_rate_id_foreign` (`interest_rate_id`),
  CONSTRAINT `customers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_interest_rate_id_foreign` FOREIGN KEY (`interest_rate_id`) REFERENCES `interest_rates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'3589474664631111','Hery','210000001','L','2024-02-22','Gadungan','085706773676','SMA','Petani','active',12,NULL,'2026-08-06 19:45:48',NULL,NULL,'2026-08-06 19:45:48','2026-08-06 20:11:39',NULL),(2,'3568163636363737','Hery','1','L','2024-02-22','Gadungan','085701553739','SMA','Petani','active',NULL,NULL,'2026-08-06 19:46:49',NULL,NULL,'2026-08-06 19:46:49','2026-08-07 10:18:34','2026-08-07 10:18:34'),(3,'3506084131219040','DIDIK SUWARDIANTO','210000002','L','1994-08-12','GADUNGAN','085322655897','SMA','SWASTA','active',12,NULL,'2026-08-07 10:51:05',NULL,NULL,'2026-08-07 10:51:05','2026-08-27 11:42:34',NULL),(4,'3506172508670001','GATUT ENDRATNO','210000867','L','1967-08-25','DARUNGAN 2/6','08125964304','S-1','KARYAWAN','active',12,NULL,'2026-08-27 12:05:24',NULL,NULL,'2026-08-27 12:05:24','2026-09-07 12:30:42',NULL),(5,'3506546742587425','BAYU','210000003','L','2000-01-01','GADUNGAN','085322655800','SMA','SWASTA','active',12,NULL,'2026-09-02 12:33:25',NULL,NULL,'2026-09-02 12:33:25','2026-09-08 12:39:01',NULL),(6,'3506010585001105','SYAHRINI','210000004','P','1985-05-01','JAKARTA','03112355556','S-1','SWASTA','active',12,NULL,'2026-09-03 12:02:55',NULL,NULL,'2026-09-03 12:02:55','2026-09-09 10:35:08',NULL),(7,'3506172503170001','TAMARA','21000007','P','2026-09-05','GADUNGAN','031123756','S-1','SWASTA','active',16,NULL,'2026-09-05 11:34:41',NULL,NULL,'2026-09-05 11:34:41','2026-09-14 10:35:20',NULL),(8,'3506080102456004','agus','210000005','L','2001-03-01','GADUNGAN','03540391225','S-1','SWASTA','active',12,NULL,'2026-09-07 10:17:17',NULL,NULL,'2026-09-07 10:17:17','2026-09-07 10:18:39',NULL),(9,'3506172508670701','SUSI','210000008','P','1985-08-17','GADUNGAN','0351391869','S-1','KARYAWAN','active',12,NULL,'2026-09-09 10:36:57',NULL,NULL,'2026-09-09 10:36:57','2026-09-09 10:37:50',NULL),(10,'3506081112620001','SUNARTO','210000877','L','1962-12-11','SATAK','085792739103','SMA','WIRASWASTA','active',12,NULL,'2026-09-09 12:35:52',NULL,NULL,'2026-09-09 12:35:52','2026-09-09 12:37:11',NULL),(11,'3506085108910004','DIAN TRI AGUSTIN','210000212','P','1991-08-11','GADUNGAN','085334492666','S-1','KARYAWAN','active',12,NULL,'2026-09-09 13:17:57',NULL,NULL,'2026-09-09 13:17:57','2026-09-09 13:19:27',NULL);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_interest_accumulations`
--

DROP TABLE IF EXISTS `daily_interest_accumulations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_interest_accumulations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `savings_type` enum('simpanan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'simpanan',
  `calculation_date` date NOT NULL,
  `base_balance` bigint unsigned NOT NULL,
  `rate_percent` decimal(5,2) NOT NULL,
  `interest_amount` bigint unsigned NOT NULL,
  `is_posted` tinyint(1) NOT NULL DEFAULT '0',
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customer_date_type` (`customer_id`,`calculation_date`,`savings_type`),
  KEY `daily_interest_accumulations_calculation_date_index` (`calculation_date`),
  KEY `daily_interest_accumulations_is_posted_index` (`is_posted`),
  CONSTRAINT `daily_interest_accumulations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_interest_accumulations`
--

LOCK TABLES `daily_interest_accumulations` WRITE;
/*!40000 ALTER TABLE `daily_interest_accumulations` DISABLE KEYS */;
INSERT INTO `daily_interest_accumulations` VALUES (1,3,'simpanan','2026-08-07',200000,1.00,7,1,'2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(2,3,'simpanan','2026-08-10',200000,1.00,7,1,'2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(3,3,'simpanan','2026-08-11',200000,1.00,7,1,'2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(4,3,'simpanan','2026-08-12',200000,1.00,7,1,'2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(5,3,'simpanan','2026-08-13',200000,1.00,7,1,'2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(6,3,'simpanan','2026-08-14',200000,1.00,7,1,'2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(7,3,'simpanan','2026-08-17',200000,1.00,7,1,'2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(8,3,'simpanan','2026-08-18',200000,1.00,7,1,'2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(9,3,'simpanan','2026-08-19',410000,1.00,15,1,'2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(10,3,'simpanan','2026-08-20',410000,1.00,15,1,'2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(11,3,'simpanan','2026-08-21',410000,1.00,15,1,'2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(12,3,'simpanan','2026-08-24',1113434,1.00,42,1,'2026-09-02 15:03:40','2026-09-02 15:03:40','2026-09-02 15:03:40'),(13,3,'simpanan','2026-08-25',1113434,1.00,42,1,'2026-09-02 15:03:40','2026-09-02 15:03:40','2026-09-02 15:03:40'),(14,3,'simpanan','2026-08-26',1113434,1.00,42,1,'2026-09-02 15:03:40','2026-09-02 15:03:40','2026-09-02 15:03:40'),(15,3,'simpanan','2026-08-27',2113434,1.00,80,1,'2026-09-02 15:03:40','2026-09-02 15:03:40','2026-09-02 15:03:40'),(16,3,'simpanan','2026-08-28',2113434,1.00,80,1,'2026-09-02 15:03:40','2026-09-02 15:03:40','2026-09-02 15:03:40'),(17,3,'simpanan','2026-08-31',2113434,1.00,80,1,'2026-09-02 15:03:40','2026-09-02 15:03:40','2026-09-02 15:03:40'),(18,3,'simpanan','2026-09-01',2113434,1.00,80,1,'2026-09-02 15:03:40','2026-09-02 15:03:40','2026-09-02 15:03:40'),(19,3,'simpanan','2026-09-02',2100000,1.00,80,1,'2026-09-02 15:03:40','2026-09-02 15:03:40','2026-09-02 15:03:40'),(20,4,'simpanan','2026-09-02',500000,1.00,19,1,'2026-09-02 15:03:40','2026-09-02 15:03:40','2026-09-02 15:03:40'),(21,5,'simpanan','2026-09-02',10000000,1.00,383,1,'2026-09-02 15:03:40','2026-09-02 15:03:40','2026-09-02 15:03:40');
/*!40000 ALTER TABLE `daily_interest_accumulations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deposit_interest_payments`
--

DROP TABLE IF EXISTS `deposit_interest_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deposit_interest_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fixed_deposit_id` bigint unsigned NOT NULL,
  `period` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interest_amount` bigint unsigned NOT NULL,
  `savings_txn_id` bigint unsigned DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_deposit_period` (`fixed_deposit_id`,`period`),
  KEY `deposit_interest_payments_savings_txn_id_foreign` (`savings_txn_id`),
  CONSTRAINT `deposit_interest_payments_fixed_deposit_id_foreign` FOREIGN KEY (`fixed_deposit_id`) REFERENCES `fixed_deposits` (`id`),
  CONSTRAINT `deposit_interest_payments_savings_txn_id_foreign` FOREIGN KEY (`savings_txn_id`) REFERENCES `deposits` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deposit_interest_payments`
--

LOCK TABLES `deposit_interest_payments` WRITE;
/*!40000 ALTER TABLE `deposit_interest_payments` DISABLE KEYS */;
INSERT INTO `deposit_interest_payments` VALUES (1,2,'2026-08',703333,7,'2026-08-22 11:26:18','2026-08-22 11:26:18','2026-08-22 11:26:18'),(2,2,'2026-09',703333,29,'2026-09-07 11:39:33','2026-09-07 11:39:33','2026-09-07 11:39:33'),(3,4,'2026-09',750000,30,'2026-09-07 11:39:33','2026-09-07 11:39:33','2026-09-07 11:39:33'),(4,5,'2026-09',69375,37,'2026-09-12 11:10:48','2026-09-12 11:10:48','2026-09-12 11:10:48'),(5,6,'2026-09',55500,38,'2026-09-12 11:10:48','2026-09-12 11:10:48','2026-09-12 11:10:48'),(6,7,'2026-09',91666,39,'2026-09-12 11:10:48','2026-09-12 11:10:48','2026-09-12 11:10:48'),(7,8,'2026-09',16500,40,'2026-09-12 11:10:48','2026-09-12 11:10:48','2026-09-12 11:10:48');
/*!40000 ALTER TABLE `deposit_interest_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deposits`
--

DROP TABLE IF EXISTS `deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deposits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('simpanan','penarikan','bunga') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'simpanan',
  `amount` bigint unsigned NOT NULL,
  `previous_balance` bigint unsigned NOT NULL DEFAULT '0',
  `current_balance` bigint unsigned NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `validated_at` timestamp NULL DEFAULT NULL,
  `validated_by` bigint unsigned DEFAULT NULL,
  `is_system_generated` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'True if generated by InterestSyncEngine',
  `period` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Period for bunga (YYYY-MM)',
  `customer_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deposits_customer_id_foreign` (`customer_id`),
  KEY `deposits_created_by_foreign` (`created_by`),
  KEY `deposits_updated_by_foreign` (`updated_by`),
  KEY `deposits_validated_by_foreign` (`validated_by`),
  CONSTRAINT `deposits_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deposits_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `deposits_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deposits_validated_by_foreign` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deposits`
--

LOCK TABLES `deposits` WRITE;
/*!40000 ALTER TABLE `deposits` DISABLE KEYS */;
INSERT INTO `deposits` VALUES (1,'simpanan',10000,0,10000,NULL,NULL,NULL,0,NULL,1,1,NULL,'2026-08-06 20:11:39','2026-08-19 10:45:45',NULL),(2,'penarikan',5000,10000,5000,NULL,'2026-08-23 23:28:08',1,0,NULL,1,1,NULL,'2026-08-07 10:30:41','2026-08-23 23:28:08',NULL),(3,'simpanan',200000,0,200000,NULL,'2026-08-29 11:15:32',2,0,NULL,3,1,NULL,'2026-08-07 10:51:40','2026-08-29 11:15:32',NULL),(4,'penarikan',50000,200000,150000,NULL,NULL,NULL,0,NULL,3,1,NULL,'2026-08-07 10:52:09','2026-08-19 22:31:09','2026-08-19 22:31:09'),(5,'simpanan',210000,200000,410000,NULL,NULL,NULL,0,NULL,3,1,NULL,'2026-08-19 22:15:10','2026-08-19 22:31:09',NULL),(6,'bunga',101,1113333,1113434,'Bunga Simpanan s/d 22 Agustus 2026',NULL,NULL,0,NULL,3,1,NULL,'2026-08-22 23:59:59','2026-08-22 11:26:18',NULL),(7,'bunga',703333,410000,1113333,'Bunga Deposito DEP-202608-00002 Periode 2026-08',NULL,NULL,0,NULL,3,1,NULL,'2026-08-22 11:26:18','2026-08-22 11:26:18',NULL),(8,'simpanan',1000000,1113434,2113434,NULL,NULL,NULL,0,NULL,3,2,NULL,'2026-08-27 11:42:34','2026-08-27 11:42:34',NULL),(9,'simpanan',5000000,0,5000000,NULL,NULL,NULL,0,NULL,5,2,NULL,'2026-09-02 12:34:03','2026-09-02 12:34:03',NULL),(10,'simpanan',5000000,5000000,10000000,NULL,NULL,NULL,0,NULL,5,2,NULL,'2026-09-02 12:34:03','2026-09-02 12:34:03',NULL),(11,'simpanan',500000,0,500000,NULL,NULL,NULL,0,NULL,4,2,NULL,'2026-09-02 13:18:39','2026-09-02 13:18:39',NULL),(12,'penarikan',13434,2113800,2100366,NULL,NULL,NULL,0,NULL,3,2,NULL,'2026-09-02 13:19:58','2026-09-02 15:03:40',NULL),(13,'bunga',366,2113434,2113800,'Bunga Simpanan Periode Agustus 2026',NULL,NULL,0,NULL,3,1,NULL,'2026-08-31 23:59:59','2026-09-02 15:03:40',NULL),(14,'bunga',160,2100366,2100526,'Bunga Simpanan Periode September 2026','2026-09-03 11:58:23',2,0,NULL,3,1,NULL,'2026-09-02 23:59:59','2026-09-03 11:58:23',NULL),(15,'bunga',19,500000,500019,'Bunga Simpanan Periode September 2026',NULL,NULL,0,NULL,4,1,NULL,'2026-09-02 23:59:59','2026-09-02 15:03:40',NULL),(16,'bunga',383,10000000,10000383,'Bunga Simpanan Periode September 2026','2026-09-03 12:25:11',1,0,NULL,5,1,NULL,'2026-09-02 23:59:59','2026-09-03 12:25:11',NULL),(17,'simpanan',75000,10000383,10075383,NULL,'2026-09-03 12:26:35',2,0,NULL,5,2,NULL,'2026-09-03 11:37:20','2026-09-03 12:26:35',NULL),(18,'simpanan',25000000,0,25000000,NULL,'2026-09-03 12:11:33',1,0,NULL,6,2,NULL,'2026-09-03 12:03:33','2026-09-03 12:11:33',NULL),(19,'penarikan',20000000,25000000,5000000,NULL,NULL,NULL,0,NULL,6,2,NULL,'2026-09-05 10:24:32','2026-09-05 10:24:32',NULL),(28,'simpanan',10000000,0,10000000,NULL,NULL,NULL,0,NULL,8,2,1,'2026-09-07 10:18:39','2026-09-07 11:36:23',NULL),(29,'bunga',703333,2100526,2803859,'Bunga Deposito DEP-202608-00002 Periode 2026-09','2026-09-07 12:08:47',2,0,NULL,3,1,NULL,'2026-09-07 11:39:33','2026-09-07 12:08:47',NULL),(30,'bunga',750000,10075383,10825383,'Bunga Deposito DEP-202609-00001 Periode 2026-09','2026-09-07 12:55:30',2,0,NULL,5,1,NULL,'2026-09-07 11:39:33','2026-09-07 12:55:30',NULL),(31,'simpanan',25000000,500019,25500019,NULL,'2026-09-07 12:56:14',2,0,NULL,4,2,NULL,'2026-09-07 12:30:42','2026-09-07 12:56:14',NULL),(32,'simpanan',100000000,10825383,110825383,NULL,NULL,NULL,0,NULL,5,2,NULL,'2026-09-08 12:39:01','2026-09-08 12:39:01',NULL),(33,'simpanan',10000000,5000000,15000000,NULL,'2026-09-10 17:11:26',1,0,NULL,6,2,NULL,'2026-09-09 10:35:08','2026-09-10 17:11:26',NULL),(34,'simpanan',200000,0,200000,NULL,NULL,NULL,0,NULL,9,2,NULL,'2026-09-09 10:37:50','2026-09-09 10:37:50',NULL),(35,'simpanan',3744680,0,3744680,NULL,NULL,NULL,0,NULL,10,2,NULL,'2004-06-22 12:37:11','2026-09-09 12:37:11',NULL),(36,'simpanan',21420255,0,21420255,NULL,NULL,NULL,0,NULL,11,1,NULL,'2026-09-09 13:19:27','2026-09-09 13:19:27',NULL),(37,'bunga',69375,15000000,15069375,'Bunga Deposito DEP-202609-00002 Periode 2026-09',NULL,NULL,0,NULL,6,NULL,NULL,'2026-09-12 11:10:48','2026-09-12 11:10:48',NULL),(38,'bunga',55500,200000,255500,'Bunga Deposito DEP-202609-00003 Periode 2026-09',NULL,NULL,0,NULL,9,NULL,NULL,'2026-09-12 11:10:48','2026-09-12 11:10:48',NULL),(39,'bunga',91666,3744680,3836346,'Bunga Deposito DEP-202609-00004 Periode 2026-09',NULL,NULL,0,NULL,10,NULL,NULL,'2026-09-12 11:10:48','2026-09-12 11:10:48',NULL),(40,'bunga',16500,21420255,21436755,'Bunga Deposito DEP-202609-00005 Periode 2026-09',NULL,NULL,0,NULL,11,NULL,NULL,'2026-09-12 11:10:48','2026-09-12 11:10:48',NULL),(41,'simpanan',100000000,0,100000000,NULL,'2026-09-14 11:41:44',1,0,NULL,7,2,NULL,'2026-09-14 10:35:20','2026-09-14 11:41:44',NULL),(42,'penarikan',436755,21436755,21000000,NULL,NULL,NULL,0,NULL,11,1,NULL,'2026-09-14 11:23:22','2026-09-14 11:23:22',NULL),(43,'penarikan',803859,2803859,2000000,NULL,NULL,NULL,0,NULL,3,1,NULL,'2026-09-14 11:29:23','2026-09-14 11:29:23',NULL);
/*!40000 ALTER TABLE `deposits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fixed_deposits`
--

DROP TABLE IF EXISTS `fixed_deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fixed_deposits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `amount` bigint unsigned NOT NULL,
  `tenor_months` tinyint unsigned NOT NULL,
  `rate_percent` decimal(5,2) NOT NULL,
  `start_date` date NOT NULL,
  `maturity_date` date NOT NULL,
  `status` enum('active','matured','extended','liquidated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `extended_from_id` bigint unsigned DEFAULT NULL,
  `liquidated_at` timestamp NULL DEFAULT NULL,
  `matured_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `validated_at` timestamp NULL DEFAULT NULL,
  `validated_by` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fixed_deposits_number_unique` (`number`),
  UNIQUE KEY `unique_customer_account_number` (`customer_id`,`account_number`),
  KEY `fixed_deposits_customer_id_index` (`customer_id`),
  KEY `fixed_deposits_status_index` (`status`),
  KEY `fixed_deposits_maturity_date_index` (`maturity_date`),
  KEY `fixed_deposits_extended_from_id_foreign` (`extended_from_id`),
  KEY `fixed_deposits_created_by_foreign` (`created_by`),
  KEY `fixed_deposits_updated_by_foreign` (`updated_by`),
  KEY `fixed_deposits_validated_by_foreign` (`validated_by`),
  CONSTRAINT `fixed_deposits_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fixed_deposits_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fixed_deposits_extended_from_id_foreign` FOREIGN KEY (`extended_from_id`) REFERENCES `fixed_deposits` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fixed_deposits_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fixed_deposits_validated_by_foreign` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fixed_deposits`
--

LOCK TABLES `fixed_deposits` WRITE;
/*!40000 ALTER TABLE `fixed_deposits` DISABLE KEYS */;
INSERT INTO `fixed_deposits` VALUES (1,'DEP-202608-00001','11111111',3,211000000,4,4.00,'2026-08-07','2026-12-07','extended',NULL,NULL,NULL,NULL,NULL,NULL,1,1,'2026-08-07 10:59:33','2026-08-07 11:40:08',NULL),(2,'DEP-202608-00002','DEP-3-0001',3,211000000,4,4.00,'2026-08-07','2026-12-07','active',1,NULL,NULL,'Perpanjangan dari DEP-202608-00001',NULL,NULL,1,NULL,'2026-08-07 11:40:08','2026-09-07 17:46:34',NULL),(3,'DEP-202608-00003','DEP-4-0001',4,10000000,6,6.00,'2026-08-27','2027-02-27','active',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-08-27 12:07:53','2026-09-07 17:46:34',NULL),(4,'DEP-202609-00001','DEP-5-0001',5,150000000,3,6.00,'2026-09-02','2026-12-02','active',NULL,NULL,NULL,NULL,'2026-09-03 12:16:30',1,2,NULL,'2026-09-02 12:45:59','2026-09-07 17:46:34',NULL),(5,'DEP-202609-00002','211000123',6,25000000,3,3.33,'2026-09-08','2026-12-08','active',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-09-08 10:43:57','2026-09-08 10:43:57',NULL),(6,'DEP-202609-00003','211000124',9,20000000,3,3.33,'2026-09-09','2026-12-09','active',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,'2026-09-09 10:38:58','2026-09-09 10:38:58',NULL),(7,'DEP-202609-00004','211000263',10,10000000,3,11.00,'2026-09-09','2026-12-09','active',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'2026-09-09 13:08:41','2026-09-09 13:08:41',NULL),(8,'DEP-202609-00005','211000954',11,60000000,3,0.33,'2026-09-09','2026-12-09','active',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'2026-09-09 13:27:36','2026-09-09 13:27:36',NULL);
/*!40000 ALTER TABLE `fixed_deposits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `foreclosures`
--

DROP TABLE IF EXISTS `foreclosures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `foreclosures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `collateral_amount` bigint unsigned NOT NULL DEFAULT '0',
  `remaining_amount` bigint unsigned NOT NULL DEFAULT '0',
  `return_amount` bigint unsigned NOT NULL DEFAULT '0',
  `customer_id` bigint unsigned NOT NULL,
  `visit_id` bigint unsigned NOT NULL,
  `collateral_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `foreclosures_customer_id_foreign` (`customer_id`),
  KEY `foreclosures_visit_id_foreign` (`visit_id`),
  KEY `foreclosures_collateral_id_foreign` (`collateral_id`),
  CONSTRAINT `foreclosures_collateral_id_foreign` FOREIGN KEY (`collateral_id`) REFERENCES `collaterals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `foreclosures_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `foreclosures_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `foreclosures`
--

LOCK TABLES `foreclosures` WRITE;
/*!40000 ALTER TABLE `foreclosures` DISABLE KEYS */;
/*!40000 ALTER TABLE `foreclosures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('holiday','workday') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'holiday',
  `year` smallint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `holidays_date_unique` (`date`),
  KEY `holidays_year_index` (`year`),
  KEY `holidays_type_index` (`type`),
  KEY `holidays_created_by_foreign` (`created_by`),
  KEY `holidays_updated_by_foreign` (`updated_by`),
  CONSTRAINT `holidays_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `holidays_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` VALUES (1,'2026-08-05','1','holiday',2026,NULL,1,NULL,'2026-08-05 22:11:19','2026-08-06 10:44:50','2026-08-06 10:44:50'),(2,'2026-08-17','minggu','holiday',2026,NULL,1,1,'2026-08-06 11:46:05','2026-09-05 11:03:42',NULL);
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interest_engine_logs`
--

DROP TABLE IF EXISTS `interest_engine_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interest_engine_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `run_date` date NOT NULL,
  `status` enum('success','skipped','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_customers` int unsigned NOT NULL DEFAULT '0',
  `total_interest` bigint unsigned NOT NULL DEFAULT '0',
  `duration_seconds` int unsigned DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interest_engine_logs_run_date_unique` (`run_date`),
  KEY `interest_engine_logs_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interest_engine_logs`
--

LOCK TABLES `interest_engine_logs` WRITE;
/*!40000 ALTER TABLE `interest_engine_logs` DISABLE KEYS */;
INSERT INTO `interest_engine_logs` VALUES (1,'2026-08-06','success','Diproses manual',0,0,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(2,'2026-08-07','success','Diproses manual',1,7,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(3,'2026-08-08','skipped','Hari Libur / Weekend',0,0,NULL,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(4,'2026-08-09','skipped','Hari Libur / Weekend',0,0,NULL,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(5,'2026-08-10','success','Diproses manual',1,7,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(6,'2026-08-11','success','Diproses manual',1,7,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(7,'2026-08-12','success','Diproses manual',1,7,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(8,'2026-08-13','success','Diproses manual',1,7,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(9,'2026-08-14','success','Diproses manual',1,7,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(10,'2026-08-15','skipped','Hari Libur / Weekend',0,0,NULL,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(11,'2026-08-16','skipped','Hari Libur / Weekend',0,0,NULL,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(12,'2026-08-17','success','Diproses manual',1,7,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(13,'2026-08-18','success','Diproses manual',1,7,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(14,'2026-08-19','success','Diproses manual',1,15,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(15,'2026-08-20','success','Diproses manual',1,15,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(16,'2026-08-21','success','Diproses manual',1,15,1,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(17,'2026-08-22','skipped','Hari Libur / Weekend',0,0,NULL,NULL,'2026-08-22 11:25:21','2026-08-22 11:25:21'),(18,'2026-08-23','skipped','Hari Libur / Weekend',0,0,NULL,NULL,'2026-09-02 15:03:40','2026-09-02 15:03:40'),(19,'2026-08-24','success','Diproses manual',1,42,1,NULL,'2026-09-02 15:03:40','2026-09-02 15:03:40'),(20,'2026-08-25','success','Diproses manual',1,42,1,NULL,'2026-09-02 15:03:40','2026-09-02 15:03:40'),(21,'2026-08-26','success','Diproses manual',1,42,1,NULL,'2026-09-02 15:03:40','2026-09-02 15:03:40'),(22,'2026-08-27','success','Diproses manual',1,80,1,NULL,'2026-09-02 15:03:40','2026-09-02 15:03:40'),(23,'2026-08-28','success','Diproses manual',1,80,1,NULL,'2026-09-02 15:03:40','2026-09-02 15:03:40'),(24,'2026-08-29','skipped','Hari Libur / Weekend',0,0,NULL,NULL,'2026-09-02 15:03:40','2026-09-02 15:03:40'),(25,'2026-08-30','skipped','Hari Libur / Weekend',0,0,NULL,NULL,'2026-09-02 15:03:40','2026-09-02 15:03:40'),(26,'2026-08-31','success','Diproses manual',1,80,1,NULL,'2026-09-02 15:03:40','2026-09-02 15:03:40'),(27,'2026-09-01','success','Diproses manual',1,80,1,NULL,'2026-09-02 15:03:40','2026-09-02 15:03:40'),(28,'2026-09-02','success','Diproses manual',3,482,1,NULL,'2026-09-02 15:03:40','2026-09-02 15:03:40');
/*!40000 ALTER TABLE `interest_engine_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interest_posting_logs`
--

DROP TABLE IF EXISTS `interest_posting_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interest_posting_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('success','failed','manual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_customers` int unsigned NOT NULL DEFAULT '0',
  `total_interest` bigint unsigned NOT NULL DEFAULT '0',
  `posted_by` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interest_posting_logs_period_unique` (`period`),
  KEY `interest_posting_logs_posted_by_foreign` (`posted_by`),
  CONSTRAINT `interest_posting_logs_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interest_posting_logs`
--

LOCK TABLES `interest_posting_logs` WRITE;
/*!40000 ALTER TABLE `interest_posting_logs` DISABLE KEYS */;
INSERT INTO `interest_posting_logs` VALUES (1,'2026-08','manual',1,101,1,'Update bunga manual pada 2026-08-22 11:25:21','2026-08-22 11:25:21','2026-08-22 11:25:21'),(2,'2026-09','manual',4,928,1,'Update bunga simpanan bulanan pada 2026-09-02 15:03:40','2026-09-02 15:03:40','2026-09-02 15:03:40');
/*!40000 ALTER TABLE `interest_posting_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interest_rates`
--

DROP TABLE IF EXISTS `interest_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interest_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rate_percent` decimal(5,2) NOT NULL,
  `effective_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interest_rates_type_is_active_index` (`type`,`is_active`),
  KEY `interest_rates_effective_date_index` (`effective_date`),
  KEY `interest_rates_created_by_foreign` (`created_by`),
  CONSTRAINT `interest_rates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interest_rates`
--

LOCK TABLES `interest_rates` WRITE;
/*!40000 ALTER TABLE `interest_rates` DISABLE KEYS */;
INSERT INTO `interest_rates` VALUES (2,'simpanan',3.50,'2026-01-01',0,NULL,NULL,'2026-08-05 20:57:24','2026-08-05 20:57:24'),(4,'deposito',5.50,'2026-01-01',0,NULL,NULL,'2026-08-05 20:57:24','2026-08-05 20:57:24'),(5,'deposito',3.33,'2026-01-01',0,NULL,NULL,'2026-08-05 20:57:24','2026-09-09 13:04:46'),(7,'simpanan',3.50,'2026-01-01',0,NULL,NULL,'2026-08-05 21:39:04','2026-08-05 21:39:04'),(9,'deposito',5.50,'2026-01-01',0,NULL,NULL,'2026-08-05 21:39:04','2026-08-05 21:39:04'),(11,'deposito',4.00,'2026-08-05',1,NULL,1,'2026-08-05 21:45:36','2026-09-14 10:43:13'),(12,'simpanan',1.00,'2026-08-05',0,'1% perhari',1,'2026-08-05 21:55:39','2026-09-09 13:24:37'),(13,'deposito',11.00,'2026-09-09',0,NULL,1,'2026-09-09 13:04:46','2026-09-09 13:21:16'),(14,'deposito',0.33,'2026-09-09',0,NULL,1,'2026-09-09 13:21:16','2026-09-11 17:17:33'),(15,'simpanan',0.33,'2026-09-09',0,NULL,1,'2026-09-09 13:24:37','2026-09-09 13:25:12'),(16,'simpanan',1.00,'2026-09-09',1,NULL,1,'2026-09-09 13:25:12','2026-09-09 13:25:12'),(17,'deposito',10.00,'2026-09-11',1,NULL,1,'2026-09-11 17:17:34','2026-09-11 17:17:34'),(18,'simpanan',5.00,'2026-09-11',1,NULL,1,'2026-09-11 18:00:45','2026-09-11 18:00:45');
/*!40000 ALTER TABLE `interest_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interest_sync_runs`
--

DROP TABLE IF EXISTS `interest_sync_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interest_sync_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL COMMENT 'User who triggered the sync',
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
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interest_sync_runs_user_id_foreign` (`user_id`),
  KEY `interest_sync_runs_status_index` (`status`),
  KEY `interest_sync_runs_created_at_index` (`created_at`),
  CONSTRAINT `interest_sync_runs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interest_sync_runs`
--

LOCK TABLES `interest_sync_runs` WRITE;
/*!40000 ALTER TABLE `interest_sync_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `interest_sync_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_resets_table',1),(3,'2019_08_19_000000_create_failed_jobs_table',1),(4,'2019_12_14_000001_create_personal_access_tokens_table',1),(5,'2022_06_15_114258_add_gender_columns_to_users_table',1),(6,'2022_06_18_000725_create_customers_table',1),(7,'2022_06_18_160457_create_collaterals_table',1),(8,'2022_06_19_094908_create_deposits_table',1),(9,'2022_06_25_112144_create_visits_table',1),(10,'2022_06_25_140614_create_foreclosures_table',1),(11,'2026_08_05_180355_create_activity_log_table',1),(12,'2026_08_05_180356_add_event_column_to_activity_log_table',1),(13,'2026_08_05_180357_add_batch_uuid_column_to_activity_log_table',1),(14,'2026_08_05_000001_create_holidays_table',2),(15,'2026_08_05_000002_create_workday_year_counts_table',2),(16,'2026_08_05_000003_create_interest_rates_table',2),(17,'2026_08_05_000004_create_daily_interest_accumulations_table',2),(18,'2026_08_05_000005_create_interest_engine_logs_table',2),(19,'2026_08_05_000006_create_interest_posting_logs_table',2),(20,'2026_08_05_000007_create_fixed_deposits_table',3),(21,'2026_08_05_000008_create_deposit_interest_payments_table',3),(22,'2026_08_05_000008_add_interest_rate_id_to_customers_table',4),(23,'2026_08_06_000001_update_interest_rates_type_enum',4),(24,'2026_08_15_000001_alter_deposits_table_for_interest_sync',5),(25,'2026_08_15_000002_create_interest_sync_runs_table',5),(26,'2026_08_21_000001_simplify_savings_and_interest_types',6),(27,'2026_08_23_000001_add_validation_columns_to_deposits_and_fixed_deposits_table',7),(28,'2026_09_06_000001_create_auto_interest_run_logs_table',8),(29,'2026_09_07_000001_add_account_number_to_fixed_deposits_table',9),(30,'2026_09_08_000001_fix_account_number_constraint_in_fixed_deposits_table',10),(31,'2026_09_08_000002_cleanup_fixed_deposits_account_numbers',10);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
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
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_phone_unique` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Drs Gatut Endratno','manajer',NULL,'$2y$10$YcSsW5gyv87Ke5easWTCGuMnK2kY.JoyryqHrwehHIe.mmx53RJfC',NULL,'2026-08-05 18:04:05','2026-08-07 11:02:16','L','1978-08-30','S-1','darungan','085322655897','2026-08-04 18:04:05','manager',1,NULL,NULL),(2,'DYAH','admin',NULL,'$2a$12$APlZu9/jIZAQoy4K0g3v3OqbT6RyaWQMQJt2AU.hwDFyP7mAPVtzu',NULL,'2026-08-05 18:04:05','2026-09-09 13:02:04','P','1990-12-05','S-1','GADUNGAN','085322655800','2026-08-05 18:04:05','teller',1,NULL,NULL),(3,'Collector','kolektor',NULL,'$2y$10$0uidg7j/Tlgk57Ww.sQ20.p68BhXVE7zPw5CSdvqR.UoQ9crAa3sS',NULL,'2026-08-05 18:04:05','2026-08-05 18:04:05','L',NULL,NULL,NULL,'0823','2026-08-05 18:04:05','collector',1,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visits`
--

DROP TABLE IF EXISTS `visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `remaining_amount` bigint unsigned NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visits_customer_id_foreign` (`customer_id`),
  KEY `visits_user_id_foreign` (`user_id`),
  CONSTRAINT `visits_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `visits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visits`
--

LOCK TABLES `visits` WRITE;
/*!40000 ALTER TABLE `visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workday_year_counts`
--

DROP TABLE IF EXISTS `workday_year_counts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workday_year_counts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint unsigned NOT NULL,
  `workday_count` smallint unsigned NOT NULL,
  `calculated_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workday_year_counts_year_unique` (`year`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workday_year_counts`
--

LOCK TABLES `workday_year_counts` WRITE;
/*!40000 ALTER TABLE `workday_year_counts` DISABLE KEYS */;
INSERT INTO `workday_year_counts` VALUES (1,2026,260,'2026-09-05 11:03:42','2026-08-05 22:11:19','2026-09-05 11:03:42');
/*!40000 ALTER TABLE `workday_year_counts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'koperasi'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-17 15:38:14
