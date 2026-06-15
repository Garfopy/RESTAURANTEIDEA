-- Compatibilidad MySQL 5.7: agrega coordenadas del restaurante si no existen
SET @lat_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rest_restaurantes'
    AND COLUMN_NAME = 'lat'
);

SET @ddl_lat := IF(
  @lat_exists = 0,
  'ALTER TABLE rest_restaurantes ADD COLUMN lat DECIMAL(10,7) NULL AFTER direccion',
  'SELECT 1'
);

PREPARE stmt FROM @ddl_lat;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @lng_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'rest_restaurantes'
    AND COLUMN_NAME = 'lng'
);

SET @ddl_lng := IF(
  @lng_exists = 0,
  'ALTER TABLE rest_restaurantes ADD COLUMN lng DECIMAL(10,7) NULL AFTER lat',
  'SELECT 1'
);

PREPARE stmt FROM @ddl_lng;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
