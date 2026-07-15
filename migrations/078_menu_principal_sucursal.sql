-- Permite marcar una sucursal/restaurante como plantilla principal de menu
-- para copiar su catalogo a otras sucursales de la misma cadena.

SET @exists := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'rest_restaurantes'
    AND column_name = 'menu_principal'
);

SET @sql := IF(
  @exists = 0,
  'ALTER TABLE rest_restaurantes ADD COLUMN menu_principal TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'rest_restaurantes'
    AND index_name = 'idx_rest_menu_principal'
);

SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE rest_restaurantes ADD KEY idx_rest_menu_principal (empresa_id, menu_principal)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
