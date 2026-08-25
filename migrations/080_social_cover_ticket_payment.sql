-- Pagos sociales de cuenta.
-- Version compatible con usuarios MySQL sin permiso sobre information_schema.
--
-- Necesario:
--   Permite registrar rest_tickets.metodo_pago = 'social_cover'.
--
-- Importante:
--   Esta version asume que rest_tickets.metodo_pago conserva los valores base
--   del proyecto: paypal, tarjeta, transferencia y efectivo.

ALTER TABLE `rest_tickets`
  MODIFY COLUMN `metodo_pago`
  ENUM('paypal','tarjeta','transferencia','efectivo','social_cover') NULL DEFAULT NULL;

-- Indices recomendados para validar rapido pagos/QR por visita.
-- Ejecutalos una sola vez si esas columnas existen en tu base.
-- Si tu MySQL/phpMyAdmin marca "Duplicate key name", significa que el indice ya existe.

-- ALTER TABLE `rest_tickets`
--   ADD INDEX `idx_tickets_visita_estado` (`visita_id`, `estado`);

-- ALTER TABLE `rest_pedidos`
--   ADD INDEX `idx_pedidos_visita_usuario` (`visita_id`, `mobile_usuario_id`, `tipo_pedido`);
