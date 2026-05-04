-- ============================================================
-- Migration 001 — Registro público y seguridad de login
-- Compatible MySQL 5.7 · phpMyAdmin
--
-- INSTRUCCIONES:
-- 1. Abre phpMyAdmin → selecciona la base idactivo_carnihubdb
-- 2. Pestaña SQL → pega TODO este contenido
-- 3. IMPORTANTE: activa la casilla "Continuar aunque haya error"
--    (o "Continue on error") antes de ejecutar
-- 4. Ejecuta → algunos ALTER pueden fallar si la columna ya existe, es normal
-- ============================================================

-- Columnas en usuarios (si alguna ya existe, fallará pero las demás continuarán)
ALTER TABLE `usuarios` ADD COLUMN `telefono`        VARCHAR(20)   NULL DEFAULT NULL;
ALTER TABLE `usuarios` ADD COLUMN `ubicacion_texto` VARCHAR(255)  NULL DEFAULT NULL;
ALTER TABLE `usuarios` ADD COLUMN `ubicacion_lat`   DECIMAL(10,7) NULL DEFAULT NULL;
ALTER TABLE `usuarios` ADD COLUMN `ubicacion_lng`   DECIMAL(10,7) NULL DEFAULT NULL;

-- Columna en empresas
ALTER TABLE `empresas` ADD COLUMN `tipo_negocio` VARCHAR(100) NULL DEFAULT NULL;

-- Tabla intentos de login (brute force)
CREATE TABLE IF NOT EXISTS `login_intentos` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45)   NOT NULL,
  `email`      VARCHAR(150)  NOT NULL,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_fecha` (`ip`, `created_at`),
  KEY `idx_email_fecha` (`email`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla tokens de verificación de correo
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

-- Tabla rate limiting de registros
CREATE TABLE IF NOT EXISTS `registro_intentos` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45)  NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_fecha` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
