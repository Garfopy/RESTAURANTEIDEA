-- ============================================================
-- Migration 001 — Registro público y seguridad de login
-- Compatible MySQL 5.7 · Ejecutar en cPanel → phpMyAdmin
-- ============================================================

DROP PROCEDURE IF EXISTS _m001;
DELIMITER $$
CREATE PROCEDURE _m001()
BEGIN

  -- usuarios: telefono
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='telefono') THEN
    ALTER TABLE `usuarios` ADD COLUMN `telefono` VARCHAR(20) NULL DEFAULT NULL;
  END IF;

  -- usuarios: ubicacion_texto
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='ubicacion_texto') THEN
    ALTER TABLE `usuarios` ADD COLUMN `ubicacion_texto` VARCHAR(255) NULL DEFAULT NULL;
  END IF;

  -- usuarios: ubicacion_lat
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='ubicacion_lat') THEN
    ALTER TABLE `usuarios` ADD COLUMN `ubicacion_lat` DECIMAL(10,7) NULL DEFAULT NULL;
  END IF;

  -- usuarios: ubicacion_lng
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='ubicacion_lng') THEN
    ALTER TABLE `usuarios` ADD COLUMN `ubicacion_lng` DECIMAL(10,7) NULL DEFAULT NULL;
  END IF;

  -- empresas: tipo_negocio
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='empresas' AND COLUMN_NAME='tipo_negocio') THEN
    ALTER TABLE `empresas` ADD COLUMN `tipo_negocio` VARCHAR(100) NULL DEFAULT NULL;
  END IF;

END$$
DELIMITER ;
CALL _m001();
DROP PROCEDURE IF EXISTS _m001;

-- Tablas nuevas (IF NOT EXISTS es seguro en CREATE TABLE para MySQL 5.7)
CREATE TABLE IF NOT EXISTS `login_intentos` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45)   NOT NULL,
  `email`      VARCHAR(150)  NOT NULL,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_fecha` (`ip`, `created_at`),
  KEY `idx_email_fecha` (`email`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE IF NOT EXISTS `registro_intentos` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45)  NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_fecha` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
