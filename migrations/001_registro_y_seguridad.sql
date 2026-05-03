-- ============================================================
-- Migration 001 — Registro público y seguridad de login
-- Ejecutar en cPanel → phpMyAdmin sobre idactivo_carnihubdb
-- ============================================================

-- 1. Columnas extra en usuarios (perfil de registro)
ALTER TABLE `usuarios`
  ADD COLUMN `telefono`        VARCHAR(20)    NULL DEFAULT NULL AFTER `activo`,
  ADD COLUMN `ubicacion_texto` VARCHAR(255)   NULL DEFAULT NULL AFTER `telefono`,
  ADD COLUMN `ubicacion_lat`   DECIMAL(10,7)  NULL DEFAULT NULL AFTER `ubicacion_texto`,
  ADD COLUMN `ubicacion_lng`   DECIMAL(10,7)  NULL DEFAULT NULL AFTER `ubicacion_lat`;

-- 2. Tipo de negocio en empresas
ALTER TABLE `empresas`
  ADD COLUMN `tipo_negocio` VARCHAR(100) NULL DEFAULT NULL AFTER `razon_social`;

-- 3. Tabla para bloqueo por intentos fallidos de login
CREATE TABLE IF NOT EXISTS `login_intentos` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45)   NOT NULL,
  `email`      VARCHAR(150)  NOT NULL,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_fecha` (`ip`, `created_at`),
  KEY `idx_email_fecha` (`email`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabla para tokens de verificación de correo
CREATE TABLE IF NOT EXISTS `verificacion_tokens` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED  NOT NULL,
  `token`       VARCHAR(64)   NOT NULL,
  `tipo`        ENUM('email_verificacion','reset_password') NOT NULL DEFAULT 'email_verificacion',
  `usado`       TINYINT(1)    NOT NULL DEFAULT 0,
  `expires_at`  DATETIME      NOT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`),
  KEY `fk_vtoken_usuario` (`usuario_id`),
  CONSTRAINT `fk_vtoken_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabla para registrar intentos de auto-registro (rate limiting)
CREATE TABLE IF NOT EXISTS `registro_intentos` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45)  NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_fecha` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
