-- ============================================================
-- Migración 024 — Credenciales de facturación por empresa
-- ============================================================

ALTER TABLE `empresas`
  ADD COLUMN `facturalo_apikey`   VARCHAR(64)    DEFAULT NULL AFTER `activo`,
  ADD COLUMN `facturalo_ambiente` ENUM('dev','app') NOT NULL DEFAULT 'dev' AFTER `facturalo_apikey`,
  ADD COLUMN `facturalo_rfc`      VARCHAR(15)    DEFAULT NULL AFTER `facturalo_ambiente`,
  ADD COLUMN `facturalo_nombre`   VARCHAR(200)   DEFAULT NULL AFTER `facturalo_rfc`,
  ADD COLUMN `facturalo_regimen`  VARCHAR(10)    NOT NULL DEFAULT '601' AFTER `facturalo_nombre`,
  ADD COLUMN `facturalo_cp`       VARCHAR(10)    DEFAULT NULL AFTER `facturalo_regimen`,
  ADD COLUMN `facturalo_plantilla` VARCHAR(10)   NOT NULL DEFAULT '1' AFTER `facturalo_cp`,
  ADD COLUMN `facturalo_key_pem`  MEDIUMTEXT     DEFAULT NULL AFTER `facturalo_plantilla`,
  ADD COLUMN `facturalo_cer_pem`  MEDIUMTEXT     DEFAULT NULL AFTER `facturalo_key_pem`,
  ADD COLUMN `facturalo_csd_pass` VARCHAR(200)   DEFAULT NULL AFTER `facturalo_cer_pem`;
