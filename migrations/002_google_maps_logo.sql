-- ============================================================
-- Migration 002 — Google Maps key + Logo en global_settings
-- Ejecutar en cPanel → phpMyAdmin sobre idactivo_carnihubdb
-- ============================================================

-- 1. Agregar API Key de Google Maps a configuración
INSERT INTO `global_settings` (`clave`, `valor`, `grupo`, `descripcion`)
VALUES ('api_google_maps_key', '', 'apis', 'API Key de Google Maps (habilita Maps JS API + Places API en Google Cloud Console)')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

-- 2. Asegurarse de que app_logo exista en general settings
INSERT INTO `global_settings` (`clave`, `valor`, `grupo`, `descripcion`)
VALUES ('app_logo', '', 'general', 'Ruta relativa al logotipo (ej: uploads/logos/logo.png)')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);
