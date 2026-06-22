-- Selector unificado de guarniciones. Idempotente para MySQL 5.7+.

SET @exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='rest_modificadores' AND column_name='alcance');
SET @sql := IF(@exists=0, 'ALTER TABLE rest_modificadores ADD COLUMN alcance ENUM(\'platillo\',\'restaurante\') NOT NULL DEFAULT \'platillo\' AFTER tipo', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='rest_modificadores' AND column_name='max_seleccion_global');
SET @sql := IF(@exists=0, 'ALTER TABLE rest_modificadores ADD COLUMN max_seleccion_global SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER unidad', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='rest_modificadores' AND index_name='idx_rm_catalogo');
SET @sql := IF(@exists=0, 'ALTER TABLE rest_modificadores ADD INDEX idx_rm_catalogo (restaurante_id, alcance, tipo, activo)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
