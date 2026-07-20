-- 078_social_reports_blocks.sql
-- Reportes y bloqueos generados desde la app social.

CREATE TABLE IF NOT EXISTS `social_reports` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_user_id` INT(10) UNSIGNED NOT NULL,
  `reported_user_id` INT(10) UNSIGNED NOT NULL,
  `reason` VARCHAR(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` TEXT COLLATE utf8mb4_unicode_ci NULL,
  `status` VARCHAR(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` DATETIME NULL,
  `reviewed_by` INT(11) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_social_reports_status` (`status`, `created_at`),
  KEY `idx_social_reports_reported` (`reported_user_id`, `created_at`),
  KEY `idx_social_reports_reporter` (`reporter_user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `social_blocks` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `blocker_user_id` INT(10) UNSIGNED NOT NULL,
  `blocked_user_id` INT(10) UNSIGNED NOT NULL,
  `reason` VARCHAR(80) COLLATE utf8mb4_unicode_ci NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_social_blocks_pair` (`blocker_user_id`, `blocked_user_id`),
  KEY `idx_social_blocks_blocker` (`blocker_user_id`, `created_at`),
  KEY `idx_social_blocks_blocked` (`blocked_user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
