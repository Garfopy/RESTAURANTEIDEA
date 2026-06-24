-- ============================================================
-- 073_rest_ingredientes_guardar_compat.sql
-- Columnas usadas por el formulario de inventario al guardar.
-- Idempotente para bases que no tienen todo el schema standalone.
-- ============================================================

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rest_ingredientes'
    AND COLUMN_NAME = 'codigo'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE rest_ingredientes ADD COLUMN codigo VARCHAR(20) NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rest_ingredientes'
    AND COLUMN_NAME = 'tipo'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE rest_ingredientes ADD COLUMN tipo VARCHAR(30) NULL AFTER codigo',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rest_ingredientes'
    AND COLUMN_NAME = 'categoria'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE rest_ingredientes ADD COLUMN categoria VARCHAR(100) NULL AFTER stock_minimo',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rest_ingredientes'
    AND COLUMN_NAME = 'carnihub_producto_id'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE rest_ingredientes ADD COLUMN carnihub_producto_id INT UNSIGNED NULL AFTER categoria',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rest_ingredientes'
    AND COLUMN_NAME = 'proveedor_carnihub'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE rest_ingredientes ADD COLUMN proveedor_carnihub TINYINT(1) NOT NULL DEFAULT 0 AFTER carnihub_producto_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rest_ingredientes'
    AND COLUMN_NAME = 'proveedor_nombre'
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE rest_ingredientes ADD COLUMN proveedor_nombre VARCHAR(100) NULL AFTER proveedor_carnihub',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rest_ingredientes'
    AND INDEX_NAME = 'idx_ring_carnihub_producto'
);
SET @sql := IF(@idx_exists = 0,
  'ALTER TABLE rest_ingredientes ADD INDEX idx_ring_carnihub_producto (carnihub_producto_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

