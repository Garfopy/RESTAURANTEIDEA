-- ============================================================
-- CarniHub v2.7.2 — Sistema de verificación de email
-- Migración 013: Agregar campos para verificación de email
-- ============================================================

-- ── 1. Agregar campos de verificación a tabla usuarios ──

-- Agregar columna email_verificado
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND COLUMN_NAME = 'email_verificado');
SET @sqlstmt := IF(@exist = 0,
    'ALTER TABLE `usuarios` ADD COLUMN `email_verificado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `email`',
    'SELECT ''email_verificado ya existe'' AS Info');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Agregar columna token_verificacion
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND COLUMN_NAME = 'token_verificacion');
SET @sqlstmt := IF(@exist = 0,
    'ALTER TABLE `usuarios` ADD COLUMN `token_verificacion` VARCHAR(64) NULL DEFAULT NULL AFTER `email_verificado`',
    'SELECT ''token_verificacion ya existe'' AS Info');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Agregar columna token_expira
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND COLUMN_NAME = 'token_expira');
SET @sqlstmt := IF(@exist = 0,
    'ALTER TABLE `usuarios` ADD COLUMN `token_expira` DATETIME NULL DEFAULT NULL AFTER `token_verificacion`',
    'SELECT ''token_expira ya existe'' AS Info');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Agregar índice para búsqueda rápida de tokens
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND INDEX_NAME = 'idx_token_verificacion');
SET @sqlstmt := IF(@exist = 0,
    'ALTER TABLE `usuarios` ADD INDEX `idx_token_verificacion` (`token_verificacion`)',
    'SELECT ''índice idx_token_verificacion ya existe'' AS Info');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- ── 2. Marcar usuarios existentes como verificados ──
-- Los usuarios que ya existen se consideran verificados automáticamente
UPDATE `usuarios`
SET `email_verificado` = 1
WHERE `email_verificado` = 0
  AND `created_at` < NOW();

SELECT '✅ Migración 013 completada: Verificación de email implementada' AS Resultado;
