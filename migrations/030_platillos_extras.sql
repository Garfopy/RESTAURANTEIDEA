-- Migration 030: Información extra de platillos para clientes + control de stock inteligente
-- alergenos, contiene, es_informativo (no descuenta stock), exclusiones por pedido

ALTER TABLE `rest_platillos`
  ADD COLUMN `alergenos` VARCHAR(500) NULL DEFAULT NULL AFTER `descripcion`,
  ADD COLUMN `contiene`  TEXT         NULL DEFAULT NULL AFTER `alergenos`;

ALTER TABLE `rest_receta_ingredientes`
  ADD COLUMN `es_informativo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `notas`;

ALTER TABLE `rest_pedido_items`
  ADD COLUMN `exclusiones` TEXT NULL DEFAULT NULL AFTER `notas`;
