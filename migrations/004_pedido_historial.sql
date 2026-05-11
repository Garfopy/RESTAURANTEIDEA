-- ── Migración 004: Historial de estados de pedidos ──────────────
-- Ejecutar después de 001, 002, 003

CREATE TABLE IF NOT EXISTS `pedido_historial` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pedido_id`   INT UNSIGNED NOT NULL,
  `estado`      VARCHAR(30)  NOT NULL,
  `usuario_id`  INT UNSIGNED NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ph_pedido` (`pedido_id`),
  CONSTRAINT `fk_ph_pedido`  FOREIGN KEY (`pedido_id`)  REFERENCES `pedidos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ph_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
