-- ============================================================
-- CarniHub v2.7.2 — Limpieza de campos FTP (OPCIONAL)
-- Migración 014: Eliminar campos FTP ya que no se usan
-- ============================================================
-- IMPORTANTE: Esta migración es OPCIONAL.
-- Solo ejecútala si estás seguro de que NO necesitas FTP en el futuro.
-- ============================================================

-- ── 1. Eliminar columnas FTP de tabla usuarios ──

-- Eliminar columna ftp_creado
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND COLUMN_NAME = 'ftp_creado');
SET @sqlstmt := IF(@exist > 0,
    'ALTER TABLE `usuarios` DROP COLUMN `ftp_creado`',
    'SELECT ''ftp_creado no existe'' AS Info');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Eliminar columna ftp_username
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND COLUMN_NAME = 'ftp_username');
SET @sqlstmt := IF(@exist > 0,
    'ALTER TABLE `usuarios` DROP COLUMN `ftp_username`',
    'SELECT ''ftp_username no existe'' AS Info');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Eliminar índice uq_ftp_username si existe
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND INDEX_NAME = 'uq_ftp_username');
SET @sqlstmt := IF(@exist > 0,
    'ALTER TABLE `usuarios` DROP INDEX `uq_ftp_username`',
    'SELECT ''índice uq_ftp_username no existe'' AS Info');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- ── 2. Eliminar configuración de cPanel de global_settings ──
DELETE FROM `global_settings`
WHERE `grupo` = 'cpanel'
  AND `clave` IN ('cpanel_host', 'cpanel_username', 'cpanel_token', 'cpanel_domain', 'cpanel_ftp_dir', 'cpanel_ftp_quota');

SELECT '✅ Migración 014 completada: Campos FTP eliminados (opcional)' AS Resultado;
