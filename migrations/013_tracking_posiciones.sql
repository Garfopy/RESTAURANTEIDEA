-- Sprint 4C-3: historial de posiciones GPS (cada ~60 s, por repartidor)
CREATE TABLE IF NOT EXISTS `tracking_posiciones` (
  `id`        INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `pedido_id` INT UNSIGNED   NOT NULL,
  `lat`       DECIMAL(10,7)  NOT NULL,
  `lng`       DECIMAL(10,7)  NOT NULL,
  `ts`        TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tp_pedido_ts` (`pedido_id`, `ts`),
  CONSTRAINT `fk_tp_pedido`
    FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
