-- Modificadores configurables para Amare-App. Idempotente para MySQL 5.7+.

SET @exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'rest_restaurantes' AND column_name = 'exclusiones_app_habilitadas');
SET @sql := IF(@exists = 0, 'ALTER TABLE rest_restaurantes ADD COLUMN exclusiones_app_habilitadas TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_login_comensal', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'sucursales' AND column_name = 'modificadores_config');
SET @sql := IF(@exists = 0, 'ALTER TABLE sucursales ADD COLUMN modificadores_config TEXT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'rest_restaurantes' AND column_name = 'extras_app_habilitados');
SET @sql := IF(@exists = 0, 'ALTER TABLE rest_restaurantes ADD COLUMN extras_app_habilitados TINYINT(1) NOT NULL DEFAULT 0 AFTER exclusiones_app_habilitadas', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'rest_modificadores' AND column_name = 'ingrediente_id');
SET @sql := IF(@exists = 0, 'ALTER TABLE rest_modificadores ADD COLUMN ingrediente_id INT UNSIGNED NULL AFTER restaurante_id, ADD INDEX idx_rm_ingrediente (ingrediente_id), ADD CONSTRAINT fk_rm_ingrediente FOREIGN KEY (ingrediente_id) REFERENCES rest_ingredientes(id) ON DELETE RESTRICT', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'rest_modificadores' AND column_name = 'cantidad_unidad');
SET @sql := IF(@exists = 0, 'ALTER TABLE rest_modificadores ADD COLUMN cantidad_unidad DECIMAL(12,3) NOT NULL DEFAULT 1.000 AFTER precio_extra', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'rest_modificadores' AND column_name = 'unidad');
SET @sql := IF(@exists = 0, 'ALTER TABLE rest_modificadores ADD COLUMN unidad VARCHAR(20) NOT NULL DEFAULT \'pza\' AFTER cantidad_unidad', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
