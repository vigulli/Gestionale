-- ============================================================
--  GESTIONALE MULTI-TENANT — DATABASE CENTRALE
--  Importa questo file nel database: gestionale_central
--  Via phpMyAdmin: seleziona il DB → Importa → scegli questo file
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ─── Users (admin per ogni tenant) ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Cache ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Jobs / Queue ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Password reset tokens ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Sessions ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tenants ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tenants` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `favicon_path` varchar(255) DEFAULT NULL,
  `primary_color` varchar(255) NOT NULL DEFAULT '#6366f1',
  `secondary_color` varchar(255) NOT NULL DEFAULT '#818cf8',
  `invoice_background_path` varchar(255) DEFAULT NULL,
  `invoice_layout` json DEFAULT NULL,
  `invoice_prefix` varchar(255) DEFAULT NULL,
  `invoice_next_number` int NOT NULL DEFAULT '1',
  `company_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `zip` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'CH',
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `uid_number` varchar(255) DEFAULT NULL,
  `iban` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` varchar(255) DEFAULT NULL,
  `smtp_user` varchar(255) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `smtp_encryption` varchar(255) NOT NULL DEFAULT 'tls',
  `smtp_from_name` varchar(255) DEFAULT NULL,
  `smtp_from_email` varchar(255) DEFAULT NULL,
  `bulkgate_app_id` varchar(255) DEFAULT NULL,
  `bulkgate_app_token` varchar(255) DEFAULT NULL,
  `bulkgate_sender_id` varchar(255) DEFAULT NULL,
  `bulkgate_whatsapp_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `module_repairs` tinyint(1) NOT NULL DEFAULT '1',
  `module_print_orders` tinyint(1) NOT NULL DEFAULT '0',
  `module_pos` tinyint(1) NOT NULL DEFAULT '1',
  `module_inventory` tinyint(1) NOT NULL DEFAULT '1',
  `module_loyalty` tinyint(1) NOT NULL DEFAULT '0',
  `sumup_api_key` varchar(255) DEFAULT NULL,
  `sumup_merchant_code` varchar(255) DEFAULT NULL,
  `vat_rate_standard` decimal(5,2) NOT NULL DEFAULT '8.10',
  `vat_rate_reduced` decimal(5,2) NOT NULL DEFAULT '2.60',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `data` json DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Domains ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `domains` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL,
  `tenant_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domains_domain_unique` (`domain`),
  KEY `domains_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `domains_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Migration tracking ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Segna le migration come già eseguite (per evitare che artisan le riesegua)
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
  ('0001_01_01_000000_create_users_table', 1),
  ('0001_01_01_000001_create_cache_table', 1),
  ('0001_01_01_000002_create_jobs_table', 1),
  ('2019_09_15_000010_create_tenants_table', 1),
  ('2019_09_15_000020_create_domains_table', 1);

-- ─── Dati iniziali: 3 Tenant ─────────────────────────────────────────────────
-- IMPORTANTE: sostituisci i domini con i tuoi sottodomini reali
INSERT IGNORE INTO `tenants` (`id`, `name`, `primary_color`, `secondary_color`, `invoice_prefix`,
  `invoice_next_number`, `country`, `vat_rate_standard`, `vat_rate_reduced`,
  `module_repairs`, `module_print_orders`, `module_pos`, `module_inventory`, `module_loyalty`,
  `created_at`, `updated_at`) VALUES
('i-lab',       'i-Lab',      '#6366f1', '#818cf8', 'IL-',  1, 'CH', 8.10, 2.60, 1, 0, 1, 1, 0, NOW(), NOW()),
('nipotetech',  'NipoteTech', '#10b981', '#34d399', 'NT-',  1, 'CH', 8.10, 2.60, 1, 0, 1, 1, 0, NOW(), NOW()),
('dtflab',      'DTF Lab',    '#f59e0b', '#fbbf24', 'DTF-', 1, 'CH', 8.10, 2.60, 1, 1, 1, 1, 0, NOW(), NOW());

-- IMPORTANTE: sostituisci i domini qui sotto con i tuoi sottodomini reali Infomaniak
-- Esempio: se i-lab gira su "ilab.miodominio.ch", metti 'ilab.miodominio.ch'
INSERT IGNORE INTO `domains` (`domain`, `tenant_id`, `created_at`, `updated_at`) VALUES
('ilab.miodominio.ch',      'i-lab',      NOW(), NOW()),
('nipotetech.miodominio.ch','nipotetech',  NOW(), NOW()),
('dtflab.miodominio.ch',    'dtflab',     NOW(), NOW());

SET foreign_key_checks = 1;
