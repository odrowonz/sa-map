-- SA Map: создание таблиц под Laravel (эквивалент php artisan migrate).
-- База: itsenior_sa_map (в phpMyAdmin выберите её, затем вкладка SQL и выполните скрипт).
-- Выполнять один раз на пустой БД (или после бэкапа). Кодировка: utf8mb4.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 0001_01_01_000000_create_users_table
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `locale` varchar(10) NOT NULL DEFAULT 'ru',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sa_users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`),
  CONSTRAINT `sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sa_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 0001_01_01_000001_create_cache_table
-- ---------------------------------------------------------------------------

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

-- ---------------------------------------------------------------------------
-- 0001_01_01_000002_create_jobs_table
-- ---------------------------------------------------------------------------

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
  `options` mediumtext,
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

-- ---------------------------------------------------------------------------
-- 2026_04_11_120000_create_sa_map_domain_tables
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sa_projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(500) NOT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sa_projects_user_id_foreign` (`user_id`),
  CONSTRAINT `sa_projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sa_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sa_elements` (
  `id` char(36) NOT NULL,
  `project_id` bigint unsigned NOT NULL,
  `level` tinyint unsigned NOT NULL,
  `artifact_key` varchar(120) NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `content` json NOT NULL,
  `include_in_export` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sa_elements_project_id_level_artifact_key_index` (`project_id`,`level`,`artifact_key`),
  CONSTRAINT `sa_elements_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `sa_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sa_element_upstream` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `element_id` char(36) NOT NULL,
  `upstream_element_id` char(36) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sa_element_upstream_element_id_upstream_element_id_unique` (`element_id`,`upstream_element_id`),
  KEY `sa_element_upstream_upstream_element_id_index` (`upstream_element_id`),
  CONSTRAINT `sa_element_upstream_element_id_foreign` FOREIGN KEY (`element_id`) REFERENCES `sa_elements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sa_element_upstream_upstream_element_id_foreign` FOREIGN KEY (`upstream_element_id`) REFERENCES `sa_elements` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sa_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `storage_key` varchar(512) NOT NULL,
  `original_name` varchar(500) NOT NULL,
  `mime_type` varchar(127) DEFAULT NULL,
  `kind` enum('scheme','png','document','other') NOT NULL,
  `size_bytes` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sa_attachments_project_id_foreign` (`project_id`),
  CONSTRAINT `sa_attachments_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `sa_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sa_attachment_element` (
  `attachment_id` bigint unsigned NOT NULL,
  `element_id` char(36) NOT NULL,
  PRIMARY KEY (`attachment_id`,`element_id`),
  KEY `sa_attachment_element_element_id_foreign` (`element_id`),
  CONSTRAINT `sa_attachment_element_attachment_id_foreign` FOREIGN KEY (`attachment_id`) REFERENCES `sa_attachments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sa_attachment_element_element_id_foreign` FOREIGN KEY (`element_id`) REFERENCES `sa_elements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sa_njk_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sa_njk_templates_user_id_foreign` (`user_id`),
  CONSTRAINT `sa_njk_templates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `sa_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Таблица migrations — чтобы позже `php artisan migrate` не пытался создать всё заново
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '0001_01_01_000000_create_users_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '0001_01_01_000000_create_users_table' LIMIT 1);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '0001_01_01_000001_create_cache_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '0001_01_01_000001_create_cache_table' LIMIT 1);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '0001_01_01_000002_create_jobs_table', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '0001_01_01_000002_create_jobs_table' LIMIT 1);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_04_11_120000_create_sa_map_domain_tables', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_04_11_120000_create_sa_map_domain_tables' LIMIT 1);
