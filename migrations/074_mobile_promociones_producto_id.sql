-- Guarda el producto/platillo al que aplica una promocion movil.
-- Permite que la app aplique el descuento solo a ese producto y no al carrito completo.

SET @col_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mobile_promociones'
    AND COLUMN_NAME = 'producto_id'
);

SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE mobile_promociones ADD COLUMN producto_id INT(10) UNSIGNED DEFAULT NULL AFTER usuario_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'mobile_promociones'
    AND INDEX_NAME = 'idx_producto_id'
);

SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE mobile_promociones ADD KEY idx_producto_id (producto_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
