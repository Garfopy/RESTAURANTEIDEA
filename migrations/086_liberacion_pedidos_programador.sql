-- Liberaciones forzadas de pedidos realizadas por PROGRAMADOR.
-- Cambia exclusivamente el estado operativo del pedido y sus articulos.

CREATE TABLE IF NOT EXISTS `rest_liberaciones_pedido_programador` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED NOT NULL,
  `pedido_id` INT UNSIGNED NOT NULL,
  `folio` VARCHAR(80) NULL,
  `cliente_referencia` VARCHAR(200) NULL,
  `estado_anterior` VARCHAR(50) NOT NULL,
  `estado_nuevo` VARCHAR(50) NOT NULL DEFAULT 'entregado',
  `estados_items_anteriores` TEXT NULL,
  `motivo` VARCHAR(500) NOT NULL,
  `usuario_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rlpp_rest_fecha` (`restaurante_id`, `created_at`),
  KEY `idx_rlpp_pedido` (`pedido_id`),
  KEY `idx_rlpp_usuario` (`usuario_id`),
  CONSTRAINT `fk_rlpp_restaurante`
    FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rlpp_pedido`
    FOREIGN KEY (`pedido_id`) REFERENCES `rest_pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rlpp_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
