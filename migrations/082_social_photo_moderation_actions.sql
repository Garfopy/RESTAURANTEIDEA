-- 082_social_photo_moderation_actions.sql
-- Cola de fotografias sociales y auditoria de decisiones de moderacion.

CREATE TABLE IF NOT EXISTS `social_photo_moderation` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `photo_url` VARCHAR(600) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` ENUM('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `review_notes` VARCHAR(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewed_by` BIGINT(20) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_social_photo_url` (`photo_url`),
  KEY `idx_social_photo_queue` (`status`,`created_at`),
  KEY `idx_social_photo_user` (`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `moderation_actions` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `action` VARCHAR(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` VARCHAR(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `user_id` INT(10) UNSIGNED DEFAULT NULL,
  `photo_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `photo_url` VARCHAR(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decision` VARCHAR(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` VARCHAR(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moderator_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_moderation_actions_target` (`target_type`,`target_id`,`created_at`),
  KEY `idx_moderation_actions_user` (`user_id`,`created_at`),
  KEY `idx_moderation_actions_photo` (`photo_id`,`created_at`),
  KEY `idx_moderation_actions_moderator` (`moderator_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
