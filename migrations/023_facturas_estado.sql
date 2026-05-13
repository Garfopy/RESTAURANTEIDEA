-- ============================================================
-- Migración 023 — Columna estado en facturas + campos API
-- ============================================================

-- 1. Agrega estado a la tabla facturas (ignorar error 1060 si ya existe)
ALTER TABLE `facturas`
  ADD COLUMN `estado` ENUM('timbrada','cancelada') NOT NULL DEFAULT 'timbrada';

-- 2. Nuevas claves de configuración para FacturaLO Plus
INSERT INTO `global_settings` (`clave`, `valor`, `tipo`, `grupo`, `etiqueta`) VALUES
('facturalo_apikey',    '', 'password', 'facturacion', 'API Key de FacturaLO Plus (32 chars)'),
('facturalo_ambiente',  'dev', 'string', 'facturacion', 'Ambiente: dev o app'),
('facturalo_rfc',       '', 'string',   'facturacion', 'RFC del emisor'),
('facturalo_nombre',    '', 'string',   'facturacion', 'Nombre/Razón social del emisor'),
('facturalo_regimen',   '601', 'string','facturacion', 'Régimen fiscal del emisor'),
('facturalo_cp',        '', 'string',   'facturacion', 'CP del lugar de expedición'),
('facturalo_key_pem',   '', 'text',     'facturacion', 'Llave privada CSD en formato PEM'),
('facturalo_cer_pem',   '', 'text',     'facturacion', 'Certificado CSD en formato PEM'),
('facturalo_csd_pass',  '', 'password', 'facturacion', 'Contraseña de la llave privada CSD'),
('facturalo_plantilla', '1', 'string',  'facturacion', 'Número de plantilla PDF')
ON DUPLICATE KEY UPDATE `etiqueta` = VALUES(`etiqueta`);
