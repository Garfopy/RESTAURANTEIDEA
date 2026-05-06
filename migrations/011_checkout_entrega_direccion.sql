-- Migration 011: Snapshot de dirección en pedidos + coordenadas en empresas
-- Ejecutar DESPUÉS de migration 010

-- Añadir columnas de dirección de entrega al pedido (snapshot al momento de creación)
ALTER TABLE `pedidos`
  ADD COLUMN `direccion_entrega`  TEXT          NULL AFTER `foto_entrega_path`,
  ADD COLUMN `referencia_entrega` VARCHAR(200)  NULL AFTER `direccion_entrega`,
  ADD COLUMN `lat_entrega`        DECIMAL(10,8) NULL AFTER `referencia_entrega`,
  ADD COLUMN `lng_entrega`        DECIMAL(11,8) NULL AFTER `lat_entrega`;

-- Coordenadas de la empresa (punto de retiro/pickup)
ALTER TABLE `empresas`
  ADD COLUMN `lat` DECIMAL(10,8) NULL AFTER `direccion_fiscal`,
  ADD COLUMN `lng` DECIMAL(11,8) NULL AFTER `lat`;
