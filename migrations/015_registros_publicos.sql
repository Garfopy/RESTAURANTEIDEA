-- ══════════════════════════════════════════════════════════════════════════════
-- CarniHub — Migration 015: Registros Públicos desde Landing Page
-- Ejecutar DESPUÉS de 014_first_login_tracking.sql
-- ══════════════════════════════════════════════════════════════════════════════

-- ── Tabla de registros pendientes ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `registros_pendientes` (
  `id`                     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `email`                  VARCHAR(150)     NOT NULL,
  `plan_id`                TINYINT UNSIGNED NOT NULL,
  `ciclo`                  ENUM('mensual','anual') NOT NULL DEFAULT 'mensual',
  `paypal_subscription_id` VARCHAR(50)      NULL,
  `paypal_status`          VARCHAR(30)      NULL,
  `token_verificacion`     VARCHAR(64)      NULL,
  `token_expira`           DATETIME         NULL,
  `password_hash`          VARCHAR(255)     NULL     COMMENT 'Hash de contraseña temporal',
  `datos_empresa`          JSON             NOT NULL COMMENT 'razon_social, rfc, telefono, etc.',
  `estado`                 ENUM('pendiente_pago','pendiente_verificacion','completado','expirado')
                             NOT NULL DEFAULT 'pendiente_pago',
  `created_at`             TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at`           TIMESTAMP        NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email_activo` (`email`, `estado`),
  KEY `idx_token_verificacion` (`token_verificacion`),
  KEY `idx_paypal_sub` (`paypal_subscription_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_expira` (`token_expira`),
  CONSTRAINT `fk_reg_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes_saas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registros pendientes de verificación desde landing page';
