-- Soporte de pedidos Pickup/Recoger desde app movil.
-- Ajustado a la estructura actual de rest_pedidos.
-- Compatible con MySQL 5.6 / phpMyAdmin.
--
-- La tabla actual ya tiene:
-- tipo_pedido enum(...,'pickup',...), tipo_origen, direccion_entrega,
-- mobile_usuario_id, metodo_pago, pagado_at y cliente_nombre.
--
-- Ejecutar una sola vez. MySQL 5.6 no soporta ADD COLUMN IF NOT EXISTS,
-- asi que si una columna o indice ya existe, omite manualmente ese bloque.

ALTER TABLE `rest_pedidos`
  ADD COLUMN `tipo_entrega` varchar(30) NULL DEFAULT NULL AFTER `tipo_pedido`,
  ADD COLUMN `comprador_telefono` varchar(30) NULL DEFAULT NULL AFTER `cliente_nombre`,
  ADD COLUMN `pickup_at` datetime NULL DEFAULT NULL AFTER `direccion_entrega`,
  ADD COLUMN `app_order_id` varchar(80) NULL DEFAULT NULL AFTER `pagado_at`;

ALTER TABLE `rest_pedidos`
  ADD INDEX `idx_rest_pedidos_mobile` (`restaurante_id`, `mobile_usuario_id`, `created_at`);

ALTER TABLE `rest_pedidos`
  ADD INDEX `idx_rest_pedidos_tipo_app` (`restaurante_id`, `tipo_origen`, `tipo_pedido`, `estado`);

ALTER TABLE `rest_pedidos`
  ADD UNIQUE KEY `uniq_rest_pedidos_app_order` (`restaurante_id`, `app_order_id`);
