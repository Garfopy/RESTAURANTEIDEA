-- Regularizaciones manuales de pagos pendientes realizadas por PROGRAMADOR.
-- No elimina tickets, pedidos, visitas ni historial: solo sincroniza el pago
-- y conserva una bitacora permanente de la correccion.

CREATE TABLE IF NOT EXISTS `rest_regularizaciones_adeudo` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED NOT NULL,
  `tipo_registro` ENUM('ticket','pedido_app') NOT NULL,
  `registro_id` INT UNSIGNED NOT NULL,
  `folio` VARCHAR(80) NULL,
  `cliente_referencia` VARCHAR(200) NULL,
  `monto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `estado_anterior` VARCHAR(50) NULL,
  `metodo_pago` ENUM('paypal','tarjeta','transferencia','efectivo') NOT NULL,
  `motivo` VARCHAR(500) NOT NULL,
  `usuario_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rra_rest_fecha` (`restaurante_id`, `created_at`),
  KEY `idx_rra_registro` (`tipo_registro`, `registro_id`),
  KEY `idx_rra_usuario` (`usuario_id`),
  CONSTRAINT `fk_rra_restaurante`
    FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rra_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
