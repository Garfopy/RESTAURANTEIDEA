-- Migración 014: polyline de ruta guardada + paradas de entrega por sucursal
-- Ejecutar en phpMyAdmin

ALTER TABLE `pedido_sucursal`
  ADD COLUMN `foto_entrega_path` VARCHAR(255) NULL AFTER `hora_entrega`,
  ADD COLUMN `fecha_llegada`     DATETIME     NULL AFTER `foto_entrega_path`;

ALTER TABLE `pedidos`
  ADD COLUMN `ruta_polyline`      TEXT     NULL AFTER `foto_entrega_path`,
  ADD COLUMN `ruta_iniciada_at`   DATETIME NULL AFTER `ruta_polyline`,
  ADD COLUMN `ruta_finalizada_at` DATETIME NULL AFTER `ruta_iniciada_at`;
