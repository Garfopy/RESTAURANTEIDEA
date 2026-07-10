CREATE TABLE IF NOT EXISTS `mobile_notification_logs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `promotion_id` int(10) UNSIGNED DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `fcm_token_id` int(10) UNSIGNED DEFAULT NULL,
  `fcm_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fcm',
  `status` enum('pending','sent','failed','skipped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `response` text COLLATE utf8mb4_unicode_ci,
  `error` text COLLATE utf8mb4_unicode_ci,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mobile_notification_promotion` (`promotion_id`),
  KEY `idx_mobile_notification_usuario` (`usuario_id`),
  KEY `idx_mobile_notification_status` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
