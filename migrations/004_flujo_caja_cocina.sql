-- ============================================================
-- 004_flujo_caja_cocina.sql
-- Separa productos que pasan por Cocina de productos inmediatos.
-- Compatible con MySQL 5.7 e idempotente.
-- ============================================================

DROP PROCEDURE IF EXISTS ops_add_column;
DROP PROCEDURE IF EXISTS ops_add_index;

DELIMITER $$

CREATE PROCEDURE ops_add_column(IN p_tabla VARCHAR(64), IN p_col VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND BINARY TABLE_NAME = BINARY p_tabla
          AND BINARY COLUMN_NAME = BINARY p_col) = 0
     AND (SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND BINARY TABLE_NAME = BINARY p_tabla) = 1 THEN
    SET @q = CONCAT('ALTER TABLE `', p_tabla, '` ADD COLUMN ', p_ddl);
    PREPARE st FROM @q;
    EXECUTE st;
    DEALLOCATE PREPARE st;
  END IF;
END$$

CREATE PROCEDURE ops_add_index(IN p_tabla VARCHAR(64), IN p_idx VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND BINARY TABLE_NAME = BINARY p_tabla
          AND BINARY INDEX_NAME = BINARY p_idx) = 0
     AND (SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND BINARY TABLE_NAME = BINARY p_tabla) = 1 THEN
    SET @q = CONCAT('ALTER TABLE `', p_tabla, '` ADD ', p_ddl);
    PREPARE st FROM @q;
    EXECUTE st;
    DEALLOCATE PREPARE st;
  END IF;
END$$

DELIMITER ;

CALL ops_add_column(
  'rest_platillos',
  'requiere_preparacion',
  '`requiere_preparacion` tinyint(1) NOT NULL DEFAULT 1 AFTER `tiempo_preparacion_min`'
);

CALL ops_add_column(
  'rest_platillos',
  'ingrediente_directo_cantidad',
  '`ingrediente_directo_cantidad` decimal(10,3) NOT NULL DEFAULT 1.000 AFTER `ingrediente_directo_id`'
);

CALL ops_add_index(
  'rest_platillos',
  'idx_platillos_preparacion',
  'KEY `idx_platillos_preparacion` (`restaurante_id`, `requiere_preparacion`, `activo`)'
);

DROP PROCEDURE IF EXISTS ops_add_index;
DROP PROCEDURE IF EXISTS ops_add_column;

-- No se hace backfill automático: los platillos actuales conservan el valor
-- seguro (requiere preparacion). El administrador puede marcar como inmediatos
-- bebidas, piezas y productos listos desde Editar platillo, y definir cuanta
-- existencia descuenta cada venta directa.
