-- 079 - Canal de generacion de reservaciones
-- Permite distinguir reservas creadas desde web/QR contra reservas creadas desde la app movil.

SET @exists := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'rest_reservaciones'
    AND column_name = 'canal_reserva'
);

SET @sql := IF(
  @exists = 0,
  "ALTER TABLE rest_reservaciones
     ADD COLUMN canal_reserva ENUM('web','movil') NOT NULL DEFAULT 'web'
     AFTER origen",
  "SELECT 1"
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE rest_reservaciones
SET canal_reserva = 'web'
WHERE canal_reserva IS NULL OR canal_reserva = '';

SET @idx_exists := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'rest_reservaciones'
    AND index_name = 'idx_rest_reservaciones_canal'
);

SET @idx_sql := IF(
  @idx_exists = 0,
  "CREATE INDEX idx_rest_reservaciones_canal
     ON rest_reservaciones (restaurante_id, canal_reserva)",
  "SELECT 1"
);

PREPARE stmt FROM @idx_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
