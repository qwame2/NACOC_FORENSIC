/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `associate_lab_examinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `associate_lab_examinations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) unsigned NOT NULL,
  `suspect_name` varchar(255) NOT NULL,
  `associate_id` varchar(255) DEFAULT NULL,
  `exhibit_number` varchar(255) DEFAULT NULL,
  `laboratory_number` varchar(255) DEFAULT NULL,
  `exam_reference` varchar(255) DEFAULT NULL,
  `lab_unit` varchar(255) DEFAULT NULL,
  `analyst` varchar(255) DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `date_started` date DEFAULT NULL,
  `date_completed` date DEFAULT NULL,
  `presumptive_result` text DEFAULT NULL,
  `confirmatory_result` varchar(255) DEFAULT NULL,
  `sample_weight_description` text DEFAULT NULL,
  `sample_weight_units` varchar(255) DEFAULT NULL,
  `sample_weight_gross` varchar(255) DEFAULT NULL,
  `sample_weight_taken` varchar(255) DEFAULT NULL,
  `sample_weight_reserved` varchar(255) DEFAULT NULL,
  `substance_identification` varchar(255) DEFAULT NULL,
  `detected_substances` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detected_substances`)),
  `test_methods` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `has_saved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assoc_lab_exam_reference_unique` (`exam_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_unit` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `auditable_type` varchar(255) DEFAULT NULL,
  `auditable_id` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_case_number_index` (`case_number`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `case_handovers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `case_handovers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_number` varchar(255) NOT NULL,
  `single_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `multiple_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `from_user_id` bigint(20) unsigned NOT NULL,
  `from_unit` varchar(255) NOT NULL,
  `to_user_id` bigint(20) unsigned NOT NULL,
  `to_unit` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Pending Acknowledgement',
  `request_notes` text DEFAULT NULL,
  `response_notes` text DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `case_handovers_single_suspect_case_id_foreign` (`single_suspect_case_id`),
  KEY `case_handovers_multiple_suspect_case_id_foreign` (`multiple_suspect_case_id`),
  KEY `case_handovers_from_user_id_foreign` (`from_user_id`),
  KEY `case_handovers_to_user_id_foreign` (`to_user_id`),
  KEY `case_handovers_case_number_index` (`case_number`),
  CONSTRAINT `case_handovers_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `case_handovers_multiple_suspect_case_id_foreign` FOREIGN KEY (`multiple_suspect_case_id`) REFERENCES `multiple_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `case_handovers_single_suspect_case_id_foreign` FOREIGN KEY (`single_suspect_case_id`) REFERENCES `single_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `case_handovers_to_user_id_foreign` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_handovers_case_exclusivity` CHECK (`single_suspect_case_id` is null or `multiple_suspect_case_id` is null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `case_suspects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `case_suspects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `single_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `multiple_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `suspect_number` int(11) NOT NULL DEFAULT 1,
  `role` varchar(255) NOT NULL DEFAULT 'Primary Suspect',
  `associate_id` varchar(255) DEFAULT NULL,
  `case_status` varchar(255) NOT NULL DEFAULT 'Under Investigation',
  `suspect_disposition` varchar(255) NOT NULL DEFAULT 'Active — Under Investigation',
  `disposition_reason` text DEFAULT NULL,
  `disposition_updated_at` timestamp NULL DEFAULT NULL,
  `disposition_updated_by` varchar(255) DEFAULT NULL,
  `suspect_name` varchar(255) NOT NULL,
  `suspect_alias` varchar(255) DEFAULT NULL,
  `ghana_card` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT 'Male',
  `dob` varchar(255) DEFAULT NULL,
  `age` varchar(255) DEFAULT NULL,
  `height` varchar(255) DEFAULT NULL,
  `height_unit` varchar(255) DEFAULT 'CM',
  `eye_color` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) DEFAULT 'Ghanaian',
  `pob` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `phones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`phones`)),
  `address` text DEFAULT NULL,
  `marital_status` varchar(255) DEFAULT NULL,
  `has_children` tinyint(1) NOT NULL DEFAULT 0,
  `children_count` varchar(255) DEFAULT '0',
  `spouse_info` text DEFAULT NULL,
  `spouse_name` varchar(255) DEFAULT NULL,
  `spouse_phone` varchar(255) DEFAULT NULL,
  `partner_name` varchar(255) DEFAULT NULL,
  `partner_phone` varchar(255) DEFAULT NULL,
  `partner_info` text DEFAULT NULL,
  `dependents` text DEFAULT NULL,
  `holding_facility` varchar(255) DEFAULT NULL,
  `raid_location` text DEFAULT NULL,
  `offense_description` text DEFAULT NULL,
  `arrest_date` varchar(255) DEFAULT NULL,
  `arrest_time` varchar(255) DEFAULT NULL,
  `mugshot_front` longtext DEFAULT NULL,
  `mugshot_left` longtext DEFAULT NULL,
  `mugshot_right` longtext DEFAULT NULL,
  `extra_mugshots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_mugshots`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `case_suspects_multiple_suspect_case_id_foreign` (`multiple_suspect_case_id`),
  KEY `idx_case_suspects_suspect_name` (`suspect_name`),
  KEY `case_suspects_single_suspect_case_id_foreign` (`single_suspect_case_id`),
  CONSTRAINT `case_suspects_multiple_suspect_case_id_foreign` FOREIGN KEY (`multiple_suspect_case_id`) REFERENCES `multiple_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `case_suspects_single_suspect_case_id_foreign` FOREIGN KEY (`single_suspect_case_id`) REFERENCES `single_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_suspects_age` CHECK (`age` is null or `age` >= 0 and `age` <= 150),
  CONSTRAINT `chk_suspects_number` CHECK (`suspect_number` is null or `suspect_number` > 0),
  CONSTRAINT `chk_suspects_children` CHECK (`children_count` is null or `children_count` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crime_scene_report_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crime_scene_report_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `officer_name` varchar(255) DEFAULT NULL,
  `status` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `conclusion` text DEFAULT NULL,
  `batch_id` varchar(255) DEFAULT NULL,
  `report_type` varchar(255) NOT NULL DEFAULT 'crime_scene_support',
  `period` varchar(255) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `search` varchar(255) DEFAULT NULL,
  `priority` varchar(255) DEFAULT NULL,
  `status_filter` varchar(255) DEFAULT NULL,
  `total_cases_count` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crime_scene_report_notes_user_id_foreign` (`user_id`),
  KEY `crime_scene_report_notes_batch_id_index` (`batch_id`),
  KEY `crime_scene_report_notes_report_type_index` (`report_type`),
  CONSTRAINT `crime_scene_report_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dpf_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dpf_report` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dpf_code` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL,
  `laboratory_number` varchar(255) DEFAULT NULL,
  `single_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `multiple_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `suspect_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `location_post` varchar(255) DEFAULT NULL,
  `investigation_date` date DEFAULT NULL,
  `devices_retrieved` text DEFAULT NULL,
  `quantity` varchar(255) DEFAULT NULL,
  `brief_case_description` longtext DEFAULT NULL,
  `court_order_requested` varchar(255) NOT NULL DEFAULT 'YES',
  `court_order_granted` varchar(255) NOT NULL DEFAULT 'YES',
  `court_order_file` text DEFAULT NULL,
  `extraction_analysis_conducted` text DEFAULT NULL,
  `extraction_analysis_message` text DEFAULT NULL,
  `report_witness_statement_sent` text DEFAULT NULL,
  `report_witness_statement_message` text DEFAULT NULL,
  `report_witness_statement_file` text DEFAULT NULL,
  `intel_shared` varchar(255) NOT NULL DEFAULT 'YES',
  `write_blocker_used` varchar(255) DEFAULT NULL,
  `hash_checksum` varchar(255) DEFAULT NULL,
  `extraction_tools_used` varchar(255) DEFAULT NULL,
  `findings_summary` longtext DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`report_data`)),
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dpf_report_single_suspect_case_id_foreign` (`single_suspect_case_id`),
  KEY `dpf_report_multiple_suspect_case_id_foreign` (`multiple_suspect_case_id`),
  KEY `dpf_report_suspect_id_foreign` (`suspect_id`),
  KEY `dpf_report_user_id_foreign` (`user_id`),
  KEY `dpf_report_dpf_code_index` (`dpf_code`),
  KEY `dpf_report_case_number_index` (`case_number`),
  KEY `dpf_report_laboratory_number_index` (`laboratory_number`),
  KEY `dpf_report_status_index` (`status`),
  CONSTRAINT `dpf_report_multiple_suspect_case_id_foreign` FOREIGN KEY (`multiple_suspect_case_id`) REFERENCES `multiple_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dpf_report_single_suspect_case_id_foreign` FOREIGN KEY (`single_suspect_case_id`) REFERENCES `single_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dpf_report_suspect_id_foreign` FOREIGN KEY (`suspect_id`) REFERENCES `case_suspects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dpf_report_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_dpf_case_exclusivity` CHECK (`single_suspect_case_id` is null or `multiple_suspect_case_id` is null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dpf_reports`;
/*!50001 DROP VIEW IF EXISTS `dpf_reports`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `dpf_reports` AS SELECT
 1 AS `id`,
  1 AS `dpf_code`,
  1 AS `case_number`,
  1 AS `laboratory_number`,
  1 AS `single_suspect_case_id`,
  1 AS `multiple_suspect_case_id`,
  1 AS `suspect_id`,
  1 AS `user_id`,
  1 AS `location_post`,
  1 AS `investigation_date`,
  1 AS `devices_retrieved`,
  1 AS `quantity`,
  1 AS `brief_case_description`,
  1 AS `court_order_requested`,
  1 AS `court_order_granted`,
  1 AS `court_order_file`,
  1 AS `extraction_analysis_conducted`,
  1 AS `extraction_analysis_message`,
  1 AS `report_witness_statement_sent`,
  1 AS `report_witness_statement_message`,
  1 AS `report_witness_statement_file`,
  1 AS `intel_shared`,
  1 AS `write_blocker_used`,
  1 AS `hash_checksum`,
  1 AS `extraction_tools_used`,
  1 AS `findings_summary`,
  1 AS `remark`,
  1 AS `report_data`,
  1 AS `status`,
  1 AS `created_at`,
  1 AS `updated_at` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `drug_screening`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drug_screening` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `suspect_id` bigint(20) unsigned DEFAULT NULL,
  `civilian_name` varchar(255) DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `screening_date` date NOT NULL,
  `result` varchar(255) NOT NULL DEFAULT 'Pending',
  `substances_found` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `on_medication` tinyint(1) NOT NULL DEFAULT 0,
  `medication_summary` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `drug_screening_suspect_id_foreign` (`suspect_id`),
  CONSTRAINT `drug_screening_suspect_id_foreign` FOREIGN KEY (`suspect_id`) REFERENCES `case_suspects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drug_screening_medications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drug_screening_medications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `drug_screening_id` bigint(20) unsigned NOT NULL,
  `medication_name` varchar(255) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `form` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `route` varchar(100) DEFAULT NULL,
  `prescription_source` enum('doctor','otc','self_administered') DEFAULT NULL,
  `date_started` date DEFAULT NULL,
  `duration_ongoing` varchar(100) DEFAULT NULL,
  `last_dose_taken` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `drug_screening_medications_drug_screening_id_foreign` (`drug_screening_id`),
  CONSTRAINT `drug_screening_medications_drug_screening_id_foreign` FOREIGN KEY (`drug_screening_id`) REFERENCES `drug_screening` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evidence_report_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evidence_report_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `officer_name` varchar(255) DEFAULT NULL,
  `table_key` varchar(255) NOT NULL,
  `table_title` varchar(255) NOT NULL,
  `note` text NOT NULL,
  `batch_id` varchar(255) DEFAULT NULL,
  `report_type` varchar(255) NOT NULL DEFAULT 'evidence_management',
  `period` varchar(255) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evidence_report_notes_user_id_foreign` (`user_id`),
  KEY `evidence_report_notes_table_key_index` (`table_key`),
  KEY `evidence_report_notes_batch_id_index` (`batch_id`),
  KEY `evidence_report_notes_report_type_index` (`report_type`),
  CONSTRAINT `evidence_report_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `exhibits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exhibits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_type` varchar(255) NOT NULL DEFAULT 'single',
  `single_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `multiple_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `suspect_id` bigint(20) unsigned DEFAULT NULL,
  `associated_person` varchar(255) DEFAULT NULL,
  `exhibit_number` varchar(255) DEFAULT NULL,
  `lab_exhibit_no` varchar(255) DEFAULT NULL,
  `property_id` varchar(50) DEFAULT NULL,
  `custodial_status` varchar(50) NOT NULL DEFAULT 'Seized',
  `exhibit_category` varchar(255) NOT NULL DEFAULT 'Drugs',
  `exhibit_type` varchar(255) DEFAULT NULL,
  `drug_classification` varchar(255) DEFAULT NULL,
  `commodity_name` text DEFAULT NULL,
  `contents` text DEFAULT NULL,
  `description_of_exhibits` text DEFAULT NULL,
  `seizure_quantity` text DEFAULT NULL,
  `amount` varchar(255) DEFAULT NULL,
  `seizure_packaging` text DEFAULT NULL,
  `seizure_weight` text DEFAULT NULL,
  `seizure_unit` text DEFAULT NULL,
  `seizure_value` text DEFAULT NULL,
  `seizure_concealment` text DEFAULT NULL,
  `seizure_date` varchar(255) DEFAULT NULL,
  `seizure_time` varchar(255) DEFAULT NULL,
  `seizure_location` text DEFAULT NULL,
  `seizure_officer` text DEFAULT NULL,
  `investigating_officer` varchar(255) DEFAULT NULL,
  `source_requesting_unit` varchar(255) DEFAULT NULL,
  `seizure_reason` text DEFAULT NULL,
  `source_of_exhibits` text DEFAULT NULL,
  `exhibit_remark` text DEFAULT NULL,
  `lab_remark` text DEFAULT NULL,
  `veh_make` text DEFAULT NULL,
  `veh_model` text DEFAULT NULL,
  `veh_type` text DEFAULT NULL,
  `veh_year` text DEFAULT NULL,
  `veh_colour` text DEFAULT NULL,
  `veh_reg_no` text DEFAULT NULL,
  `veh_vin` text DEFAULT NULL,
  `veh_engine_no` text DEFAULT NULL,
  `veh_country_reg` text DEFAULT NULL,
  `veh_owner` text DEFAULT NULL,
  `veh_driver` text DEFAULT NULL,
  `veh_owner_contact` varchar(255) DEFAULT NULL,
  `veh_docs_available` varchar(255) DEFAULT NULL,
  `veh_doc_ref` varchar(255) DEFAULT NULL,
  `veh_condition` varchar(255) DEFAULT NULL,
  `veh_fuel` varchar(255) DEFAULT NULL,
  `veh_keys_recv` varchar(255) DEFAULT NULL,
  `veh_key_count` varchar(255) DEFAULT NULL,
  `veh_damage` text DEFAULT NULL,
  `veh_mods` text DEFAULT NULL,
  `veh_features` text DEFAULT NULL,
  `wpn_model` text DEFAULT NULL,
  `wpn_serial_no` text DEFAULT NULL,
  `wpn_calibre` text DEFAULT NULL,
  `wpn_colour` text DEFAULT NULL,
  `wpn_size` text DEFAULT NULL,
  `wpn_marks` text DEFAULT NULL,
  `wpn_location_found` varchar(255) DEFAULT NULL,
  `wpn_loaded_status` varchar(255) DEFAULT NULL,
  `wpn_condition` varchar(255) DEFAULT NULL,
  `dev_model` text DEFAULT NULL,
  `dev_serial_no` text DEFAULT NULL,
  `dev_imei1` text DEFAULT NULL,
  `dev_imei2` text DEFAULT NULL,
  `dev_mac_address` text DEFAULT NULL,
  `ammunition_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ammunition_details`)),
  `dev_storage` text DEFAULT NULL,
  `dev_colour` text DEFAULT NULL,
  `dev_marks` text DEFAULT NULL,
  `evidence_photos` longtext DEFAULT NULL,
  `tag_url` varchar(500) DEFAULT NULL,
  `qr_token` varchar(64) DEFAULT NULL,
  `other_exhibit_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exhibits_property_id_unique` (`property_id`),
  UNIQUE KEY `exhibits_qr_token_unique` (`qr_token`),
  UNIQUE KEY `exhibits_exhibit_number_unique` (`exhibit_number`),
  KEY `exhibits_single_suspect_case_id_foreign` (`single_suspect_case_id`),
  KEY `exhibits_multiple_suspect_case_id_foreign` (`multiple_suspect_case_id`),
  KEY `exhibits_suspect_id_foreign` (`suspect_id`),
  KEY `idx_exhibits_created_at` (`created_at`),
  CONSTRAINT `exhibits_multiple_suspect_case_id_foreign` FOREIGN KEY (`multiple_suspect_case_id`) REFERENCES `multiple_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exhibits_single_suspect_case_id_foreign` FOREIGN KEY (`single_suspect_case_id`) REFERENCES `single_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exhibits_suspect_id_foreign` FOREIGN KEY (`suspect_id`) REFERENCES `case_suspects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_exhibits_case_exclusivity` CHECK (`single_suspect_case_id` is null or `multiple_suspect_case_id` is null),
  CONSTRAINT `chk_exhibits_case_type_alignment` CHECK (`case_type` is null or `case_type` = 'SINGLE' and `multiple_suspect_case_id` is null or `case_type` = 'MULTIPLE' and `single_suspect_case_id` is null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `laboratory_examination_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `laboratory_examination_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_type` varchar(255) NOT NULL DEFAULT 'single',
  `single_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `multiple_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `suspect_id` bigint(20) unsigned DEFAULT NULL,
  `exhibit_id` bigint(20) unsigned DEFAULT NULL,
  `laboratory_number` varchar(255) DEFAULT NULL,
  `gsa_lab_number` varchar(255) DEFAULT NULL,
  `exam_reference` varchar(255) DEFAULT NULL,
  `purpose_of_examination` text DEFAULT NULL,
  `preliminary_exam_procedure` text DEFAULT NULL,
  `screening_method` text DEFAULT NULL,
  `analyst` text DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `date_started` date DEFAULT NULL,
  `date_completed` date DEFAULT NULL,
  `presumptive_result` text DEFAULT NULL,
  `presumptive_presence` text DEFAULT NULL,
  `presumptive_percentage` varchar(255) DEFAULT NULL,
  `confirmatory_result` varchar(255) NOT NULL DEFAULT 'YES',
  `sample_weight_description` text DEFAULT NULL,
  `sample_weight_units` text DEFAULT NULL,
  `sample_weight_gross` text DEFAULT NULL,
  `sample_weight_taken` text DEFAULT NULL,
  `sample_weight_reserved` text DEFAULT NULL,
  `substance_identification` text DEFAULT NULL,
  `detected_substances` longtext DEFAULT NULL,
  `test_methods` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `disposition_of_exhibit` text DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `has_saved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_exam_results_exam_reference_unique` (`exam_reference`),
  KEY `laboratory_examination_results_single_suspect_case_id_foreign` (`single_suspect_case_id`),
  KEY `laboratory_examination_results_multiple_suspect_case_id_foreign` (`multiple_suspect_case_id`),
  KEY `laboratory_examination_results_suspect_id_foreign` (`suspect_id`),
  KEY `laboratory_examination_results_exhibit_id_foreign` (`exhibit_id`),
  CONSTRAINT `laboratory_examination_results_exhibit_id_foreign` FOREIGN KEY (`exhibit_id`) REFERENCES `exhibits` (`id`) ON DELETE SET NULL,
  CONSTRAINT `laboratory_examination_results_multiple_suspect_case_id_foreign` FOREIGN KEY (`multiple_suspect_case_id`) REFERENCES `multiple_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `laboratory_examination_results_single_suspect_case_id_foreign` FOREIGN KEY (`single_suspect_case_id`) REFERENCES `single_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `laboratory_examination_results_suspect_id_foreign` FOREIGN KEY (`suspect_id`) REFERENCES `case_suspects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_lab_case_exclusivity` CHECK (`single_suspect_case_id` is null or `multiple_suspect_case_id` is null),
  CONSTRAINT `chk_lab_case_type_alignment` CHECK (`case_type` is null or `case_type` = 'SINGLE' and `multiple_suspect_case_id` is null or `case_type` = 'MULTIPLE' and `single_suspect_case_id` is null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `multiple_suspect_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `multiple_suspect_cases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_number` varchar(255) NOT NULL,
  `laboratory_number` varchar(255) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `unit_name` varchar(255) DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `controller_user_id` bigint(20) unsigned DEFAULT NULL,
  `arresting_officer` varchar(255) DEFAULT NULL,
  `operation_code` varchar(255) DEFAULT NULL,
  `operation_code_name` varchar(255) DEFAULT NULL,
  `search_warrant_document` varchar(255) DEFAULT NULL,
  `raid_location` text DEFAULT NULL,
  `offense_description` text DEFAULT NULL,
  `description_of_exhibits` text DEFAULT NULL,
  `incident_date` varchar(255) DEFAULT NULL,
  `docket_summary` text DEFAULT NULL,
  `total_suspects_count` int(11) NOT NULL DEFAULT 0,
  `total_exhibits_count` int(11) NOT NULL DEFAULT 0,
  `priority` varchar(255) NOT NULL DEFAULT 'NORMAL',
  `status` varchar(255) NOT NULL DEFAULT 'Approved',
  `case_status` varchar(255) NOT NULL DEFAULT 'Under Investigation',
  `suspect_disposition` varchar(255) NOT NULL DEFAULT 'Active — Under Investigation',
  `disposition_reason` text DEFAULT NULL,
  `disposition_updated_at` timestamp NULL DEFAULT NULL,
  `disposition_updated_by` varchar(255) DEFAULT NULL,
  `hash_log` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `confirmatory_remark` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `multiple_suspect_cases_case_number_unique` (`case_number`),
  KEY `multiple_suspect_cases_unit_id_foreign` (`unit_id`),
  KEY `multiple_suspect_cases_user_id_foreign` (`user_id`),
  KEY `multiple_suspect_cases_controller_user_id_foreign` (`controller_user_id`),
  KEY `idx_multi_cases_created_at` (`created_at`),
  KEY `idx_multi_cases_status` (`status`),
  CONSTRAINT `multiple_suspect_cases_controller_user_id_foreign` FOREIGN KEY (`controller_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `multiple_suspect_cases_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `multiple_suspect_cases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_multi_cases_counts` CHECK ((`total_suspects_count` is null or `total_suspects_count` >= 0) and (`total_exhibits_count` is null or `total_exhibits_count` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reference_registry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reference_registry` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(128) NOT NULL,
  `category` varchar(64) NOT NULL,
  `prefix` varchar(32) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_registry_reference_unique` (`reference`),
  KEY `reference_registry_category_index` (`category`),
  KEY `reference_registry_prefix_index` (`prefix`),
  KEY `reference_registry_year_index` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reference_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reference_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(64) NOT NULL,
  `prefix` varchar(32) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `current_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_sequences_name_unique` (`name`),
  UNIQUE KEY `ref_seq_cat_pfx_year_unique` (`category`,`prefix`,`year`),
  KEY `reference_sequences_category_index` (`category`),
  KEY `reference_sequences_prefix_index` (`prefix`),
  KEY `reference_sequences_year_index` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `single_suspect_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `single_suspect_cases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_number` varchar(255) NOT NULL,
  `laboratory_number` varchar(255) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `unit_name` varchar(255) DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `controller_user_id` bigint(20) unsigned DEFAULT NULL,
  `arresting_officer` varchar(255) DEFAULT NULL,
  `suspect_name` varchar(255) DEFAULT NULL,
  `suspect_alias` varchar(255) DEFAULT NULL,
  `ghana_card` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT 'Male',
  `dob` varchar(255) DEFAULT NULL,
  `age` varchar(255) DEFAULT NULL,
  `height` varchar(255) DEFAULT NULL,
  `height_unit` varchar(255) DEFAULT 'CM',
  `eye_color` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) DEFAULT 'Ghanaian',
  `pob` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `phones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`phones`)),
  `address` text DEFAULT NULL,
  `marital_status` varchar(255) DEFAULT NULL,
  `has_children` tinyint(1) NOT NULL DEFAULT 0,
  `children_count` varchar(255) DEFAULT '0',
  `spouse_info` text DEFAULT NULL,
  `spouse_name` varchar(255) DEFAULT NULL,
  `spouse_phone` varchar(255) DEFAULT NULL,
  `partner_name` varchar(255) DEFAULT NULL,
  `partner_phone` varchar(255) DEFAULT NULL,
  `partner_info` text DEFAULT NULL,
  `dependents` text DEFAULT NULL,
  `holding_facility` varchar(255) DEFAULT NULL,
  `operation_code` varchar(255) DEFAULT NULL,
  `operation_code_name` varchar(255) DEFAULT NULL,
  `search_warrant_document` varchar(255) DEFAULT NULL,
  `raid_location` text DEFAULT NULL,
  `offense_description` text DEFAULT NULL,
  `description_of_exhibits` text DEFAULT NULL,
  `arrest_date` varchar(255) DEFAULT NULL,
  `arrest_time` varchar(255) DEFAULT NULL,
  `incident_date` varchar(255) DEFAULT NULL,
  `mugshot_front` longtext DEFAULT NULL,
  `mugshot_left` longtext DEFAULT NULL,
  `mugshot_right` longtext DEFAULT NULL,
  `extra_mugshots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_mugshots`)),
  `priority` varchar(255) NOT NULL DEFAULT 'NORMAL',
  `status` varchar(255) NOT NULL DEFAULT 'Approved',
  `case_status` varchar(255) NOT NULL DEFAULT 'Under Investigation',
  `suspect_disposition` varchar(255) NOT NULL DEFAULT 'Active — Under Investigation',
  `disposition_reason` text DEFAULT NULL,
  `disposition_updated_at` timestamp NULL DEFAULT NULL,
  `disposition_updated_by` varchar(255) DEFAULT NULL,
  `hash_log` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `confirmatory_remark` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `single_suspect_cases_case_number_unique` (`case_number`),
  KEY `single_suspect_cases_unit_id_foreign` (`unit_id`),
  KEY `single_suspect_cases_user_id_foreign` (`user_id`),
  KEY `single_suspect_cases_controller_user_id_foreign` (`controller_user_id`),
  KEY `idx_single_cases_created_at` (`created_at`),
  KEY `idx_single_cases_status` (`status`),
  KEY `idx_single_cases_suspect_name` (`suspect_name`),
  CONSTRAINT `single_suspect_cases_controller_user_id_foreign` FOREIGN KEY (`controller_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `single_suspect_cases_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `single_suspect_cases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_single_cases_age` CHECK (`age` is null or `age` >= 0 and `age` <= 150),
  CONSTRAINT `chk_single_cases_children` CHECK (`children_count` is null or `children_count` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `fullname` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `service_id` varchar(255) NOT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `unit` varchar(255) NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `avatar` varchar(255) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_accounts_username_unique` (`username`),
  UNIQUE KEY `staff_accounts_service_id_unique` (`service_id`),
  UNIQUE KEY `staff_accounts_user_id_unique` (`user_id`),
  UNIQUE KEY `staff_accounts_email_unique` (`email`),
  KEY `staff_accounts_unit_index` (`unit`),
  KEY `staff_accounts_is_approved_index` (`is_approved`),
  CONSTRAINT `staff_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_case_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_case_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_by_name` varchar(255) DEFAULT NULL,
  `unit_name` varchar(255) NOT NULL,
  `case_number_preview` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `suspect_name` varchar(255) DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'MEDIUM',
  `status` varchar(255) NOT NULL DEFAULT 'Pending Approval',
  `payload` longtext NOT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_by_name` varchar(255) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `rollback_corrections` longtext DEFAULT NULL,
  `rolled_back_at` timestamp NULL DEFAULT NULL,
  `approved_case_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_case_submissions_submitted_by_index` (`submitted_by`),
  KEY `staff_case_submissions_unit_name_index` (`unit_name`),
  KEY `staff_case_submissions_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suspect_gsa_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suspect_gsa_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_suspect_id` bigint(20) unsigned NOT NULL,
  `single_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `multiple_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `original_filename` varchar(255) NOT NULL COMMENT 'Original filename from the user',
  `stored_filename` varchar(255) NOT NULL COMMENT 'Hashed unique filename on disk',
  `file_path` varchar(255) NOT NULL COMMENT 'Relative storage path',
  `file_type` varchar(255) DEFAULT NULL COMMENT 'MIME type',
  `file_extension` varchar(10) DEFAULT NULL COMMENT 'jpg|png|webp|pdf|docx|csv',
  `file_size` bigint(20) unsigned DEFAULT NULL COMMENT 'Size in bytes',
  `lab_number` varchar(255) DEFAULT NULL,
  `gsa_lab_number` varchar(255) DEFAULT NULL,
  `gsa_case_reference` text DEFAULT NULL,
  `gsa_status` text DEFAULT NULL,
  `exhibit_type` text DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active' COMMENT 'active|archived|deleted',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suspect_gsa_reports_uploaded_by_foreign` (`uploaded_by`),
  KEY `suspect_gsa_reports_case_suspect_id_index` (`case_suspect_id`),
  KEY `suspect_gsa_reports_multiple_suspect_case_id_index` (`multiple_suspect_case_id`),
  KEY `suspect_gsa_reports_status_index` (`status`),
  KEY `suspect_gsa_reports_single_suspect_case_id_foreign` (`single_suspect_case_id`),
  CONSTRAINT `suspect_gsa_reports_case_suspect_id_foreign` FOREIGN KEY (`case_suspect_id`) REFERENCES `case_suspects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suspect_gsa_reports_multiple_suspect_case_id_foreign` FOREIGN KEY (`multiple_suspect_case_id`) REFERENCES `multiple_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suspect_gsa_reports_single_suspect_case_id_foreign` FOREIGN KEY (`single_suspect_case_id`) REFERENCES `single_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suspect_gsa_reports_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_gsa_file_size` CHECK (`file_size` is null or `file_size` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suspect_nlr_reports`;
/*!50001 DROP VIEW IF EXISTS `suspect_nlr_reports`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `suspect_nlr_reports` AS SELECT
 1 AS `id`,
  1 AS `case_suspect_id`,
  1 AS `multiple_suspect_case_id`,
  1 AS `single_suspect_case_id`,
  1 AS `uploaded_by`,
  1 AS `original_filename`,
  1 AS `stored_filename`,
  1 AS `file_path`,
  1 AS `file_type`,
  1 AS `file_extension`,
  1 AS `file_size`,
  1 AS `lab_number`,
  1 AS `case_number`,
  1 AS `notes`,
  1 AS `status`,
  1 AS `created_at`,
  1 AS `updated_at` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `suspect_preliminary_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suspect_preliminary_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_suspect_id` bigint(20) unsigned DEFAULT NULL,
  `multiple_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `single_suspect_case_id` bigint(20) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `file_extension` varchar(10) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `lab_number` varchar(255) DEFAULT NULL,
  `case_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suspect_nlr_reports_single_suspect_case_id_foreign` (`single_suspect_case_id`),
  KEY `suspect_nlr_reports_uploaded_by_foreign` (`uploaded_by`),
  KEY `suspect_nlr_reports_case_suspect_id_index` (`case_suspect_id`),
  KEY `suspect_nlr_reports_multiple_suspect_case_id_index` (`multiple_suspect_case_id`),
  KEY `suspect_nlr_reports_status_index` (`status`),
  CONSTRAINT `suspect_nlr_reports_case_suspect_id_foreign` FOREIGN KEY (`case_suspect_id`) REFERENCES `case_suspects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suspect_nlr_reports_multiple_suspect_case_id_foreign` FOREIGN KEY (`multiple_suspect_case_id`) REFERENCES `multiple_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suspect_nlr_reports_single_suspect_case_id_foreign` FOREIGN KEY (`single_suspect_case_id`) REFERENCES `single_suspect_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suspect_nlr_reports_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_suspect_nlr_case_exclusivity` CHECK (`single_suspect_case_id` is null or `multiple_suspect_case_id` is null),
  CONSTRAINT `chk_suspect_nlr_file_size` CHECK (`file_size` is null or `file_size` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unit_heads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unit_heads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` bigint(20) unsigned NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `rank_title` varchar(255) NOT NULL,
  `service_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unit_heads_service_id_unique` (`service_id`),
  KEY `unit_heads_unit_id_foreign` (`unit_id`),
  CONSTRAINT `unit_heads_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'GENERAL',
  `head_name` varchar(255) DEFAULT NULL,
  `head_title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `units_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fullname` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `service_id` varchar(255) NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `is_staff` tinyint(1) NOT NULL DEFAULT 1,
  `is_head` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `unit` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `signature` longtext DEFAULT NULL,
  `avatar` longtext DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_service_id_unique` (`service_id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_is_approved_index` (`is_approved`),
  KEY `users_is_head_index` (`is_head`),
  KEY `users_approved_head_index` (`is_approved`,`is_head`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50001 DROP VIEW IF EXISTS `dpf_reports`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `dpf_reports` AS select `dpf_report`.`id` AS `id`,`dpf_report`.`dpf_code` AS `dpf_code`,`dpf_report`.`case_number` AS `case_number`,`dpf_report`.`laboratory_number` AS `laboratory_number`,`dpf_report`.`single_suspect_case_id` AS `single_suspect_case_id`,`dpf_report`.`multiple_suspect_case_id` AS `multiple_suspect_case_id`,`dpf_report`.`suspect_id` AS `suspect_id`,`dpf_report`.`user_id` AS `user_id`,`dpf_report`.`location_post` AS `location_post`,`dpf_report`.`investigation_date` AS `investigation_date`,`dpf_report`.`devices_retrieved` AS `devices_retrieved`,`dpf_report`.`quantity` AS `quantity`,`dpf_report`.`brief_case_description` AS `brief_case_description`,`dpf_report`.`court_order_requested` AS `court_order_requested`,`dpf_report`.`court_order_granted` AS `court_order_granted`,`dpf_report`.`court_order_file` AS `court_order_file`,`dpf_report`.`extraction_analysis_conducted` AS `extraction_analysis_conducted`,`dpf_report`.`extraction_analysis_message` AS `extraction_analysis_message`,`dpf_report`.`report_witness_statement_sent` AS `report_witness_statement_sent`,`dpf_report`.`report_witness_statement_message` AS `report_witness_statement_message`,`dpf_report`.`report_witness_statement_file` AS `report_witness_statement_file`,`dpf_report`.`intel_shared` AS `intel_shared`,`dpf_report`.`write_blocker_used` AS `write_blocker_used`,`dpf_report`.`hash_checksum` AS `hash_checksum`,`dpf_report`.`extraction_tools_used` AS `extraction_tools_used`,`dpf_report`.`findings_summary` AS `findings_summary`,`dpf_report`.`remark` AS `remark`,`dpf_report`.`report_data` AS `report_data`,`dpf_report`.`status` AS `status`,`dpf_report`.`created_at` AS `created_at`,`dpf_report`.`updated_at` AS `updated_at` from `dpf_report` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `suspect_nlr_reports`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `suspect_nlr_reports` AS select `suspect_preliminary_reports`.`id` AS `id`,`suspect_preliminary_reports`.`case_suspect_id` AS `case_suspect_id`,`suspect_preliminary_reports`.`multiple_suspect_case_id` AS `multiple_suspect_case_id`,`suspect_preliminary_reports`.`single_suspect_case_id` AS `single_suspect_case_id`,`suspect_preliminary_reports`.`uploaded_by` AS `uploaded_by`,`suspect_preliminary_reports`.`original_filename` AS `original_filename`,`suspect_preliminary_reports`.`stored_filename` AS `stored_filename`,`suspect_preliminary_reports`.`file_path` AS `file_path`,`suspect_preliminary_reports`.`file_type` AS `file_type`,`suspect_preliminary_reports`.`file_extension` AS `file_extension`,`suspect_preliminary_reports`.`file_size` AS `file_size`,`suspect_preliminary_reports`.`lab_number` AS `lab_number`,`suspect_preliminary_reports`.`case_number` AS `case_number`,`suspect_preliminary_reports`.`notes` AS `notes`,`suspect_preliminary_reports`.`status` AS `status`,`suspect_preliminary_reports`.`created_at` AS `created_at`,`suspect_preliminary_reports`.`updated_at` AS `updated_at` from `suspect_preliminary_reports` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_09_05_000001_create_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_09_08_000001_add_is_approved_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_09_08_000002_create_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_09_08_000003_add_docket_fields_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_09_08_000004_add_all_docket_and_vehicle_columns_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_09_10_000001_add_email_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_09_11_000001_add_seizure_weight_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_09_11_000002_add_weapon_columns_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_09_11_000003_drop_wpn_make_from_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_09_11_000004_drop_seizure_vault_from_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_09_11_000005_change_seizure_officer_to_text_in_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_09_11_000006_add_seizure_packaging_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_09_11_000007_add_height_and_eye_color_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_09_11_000008_add_seizure_name_ammo_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_09_11_000009_add_seizure_name_device_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_09_11_000010_add_device_specifications_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_09_13_000001_add_presumptive_test_result_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_09_13_000002_add_confirmatory_test_result_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_09_14_000001_update_existing_cases_exam_reference_format',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_09_14_000002_add_laboratory_number_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_09_14_000003_add_confirmatory_test_columns_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_09_14_000004_add_laboratory_analytical_findings_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_09_14_000005_add_indexes_to_cases_and_users_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_09_15_000001_add_parent_case_and_suspect_number_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_09_18_000001_create_associate_lab_examinations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_09_19_000001_add_associated_person_to_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_09_20_000001_create_single_suspect_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_09_20_000002_create_multiple_suspect_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_09_20_000003_create_case_suspects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_09_20_000004_create_exhibits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_09_20_000005_create_laboratory_examination_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_09_20_000006_migrate_existing_cases_to_relational_schema',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_09_21_000001_remove_lab_unit_from_laboratory_examination_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_09_22_000001_drop_cases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_09_22_000002_add_ammunition_details_to_exhibits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_09_22_000003_backfill_laboratory_number_format',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_09_22_000004_backfill_examination_reference_format',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_09_22_000005_add_remark_to_laboratory_examination_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_09_22_000006_add_case_status_and_controller_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_09_22_000007_create_case_handovers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_09_22_000008_create_audit_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_09_22_000009_add_suspect_disposition_columns',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_09_24_000001_change_mugshots_and_photos_to_longtext',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_09_25_000001_add_drug_classification_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_09_26_000001_add_other_exhibit_description_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_10_01_000001_add_phones_to_suspect_cases_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_10_02_141605_create_suspect_gsa_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_10_03_000001_add_device_extra_columns_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_10_05_000002_create_nlr_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_10_06_000001_create_suspect_nlr_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_10_06_000002_add_nlr_code_to_nlr_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_10_06_000003_create_dpf_report_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_10_09_000001_add_file_uploads_to_dpf_report_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_10_09_000002_change_file_columns_to_text_in_dpf_report_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_10_09_000003_add_extraction_analysis_message_to_dpf_report_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_10_09_000004_add_report_witness_statement_message_to_dpf_report_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_10_14_000001_add_avatar_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_10_14_000002_add_signature_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_10_15_000001_add_associated_person_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_10_17_000001_add_performance_indexes_to_cases_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_10_21_152811_add_gsa_lab_number_to_laboratory_examination_results_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_10_21_152916_add_gsa_lab_number_to_laboratory_examination_results_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_10_21_153427_add_gsa_lab_number_to_laboratory_examination_results_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_10_21_153803_add_gsa_lab_number_to_laboratory_examination_results_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_10_21_160206_add_gsa_fields_to_suspect_gsa_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_10_21_163502_add_database_integrity_and_case_exclusivity_constraints',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_10_21_170000_add_source_of_exhibits_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_10_21_180000_create_drug_screening_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_10_21_183000_add_substances_found_to_drug_screening_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_10_27_000001_add_exhibit_remark_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_10_27_000002_add_single_suspect_case_id_to_case_suspects_and_gsa_reports',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_10_27_000003_add_lab_remark_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_10_27_000004_add_unique_case_number_indexes',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_10_28_000001_add_vehicle_and_weapon_details_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_10_28_000002_drop_commanding_officer_from_cases_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_10_29_000001_rename_submitting_officer_to_arresting_officer',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_10_30_000001_create_reference_sequences_and_indexes',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_10_30_000002_create_reference_registry_and_enforce_uniqueness',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_11_01_000001_add_tag_url_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_11_01_000002_add_property_id_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_11_02_000001_add_custodial_status_and_qr_token_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_11_02_000002_add_exhibit_type_to_suspect_gsa_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_11_04_000001_add_civilian_name_to_drug_screening_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_11_04_000002_add_on_medication_to_drug_screening_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_11_04_000003_create_drug_screening_medications_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_11_05_223324_drop_detailed_exhibit_description_and_condition_from_nlr_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_11_06_000001_add_contents_and_description_to_exhibits_and_cases_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_11_06_000002_add_purpose_of_examination_to_laboratory_examination_results_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_11_06_000003_add_preliminary_exam_procedure_to_laboratory_examination_results_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_11_06_000004_add_screening_method_and_disposition_of_exhibit_to_laboratory_examination_results_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_11_07_000001_add_amount_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_11_07_000002_add_investigating_officer_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_11_07_000003_add_source_requesting_unit_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_11_07_000004_add_lab_exhibit_no_to_exhibits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_11_07_000005_rename_suspect_nlr_reports_to_suspect_preliminary_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_11_09_000001_create_evidence_report_notes_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_11_10_000001_create_crime_scene_report_notes_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_11_11_000001_add_presumptive_presence_and_percentage_to_laboratory_examination_results_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_11_11_000001_add_search_warrant_document_to_cases_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_11_11_000002_add_permissions_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_11_12_000001_create_staff_case_submissions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_11_12_000002_create_staff_accounts_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_11_12_000003_remove_staff_accounts_from_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_11_12_000004_add_last_seen_at_to_staff_accounts_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_11_12_000004_change_sessions_user_id_to_string',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_11_13_231726_add_rollback_columns_to_staff_case_submissions_table',2);
