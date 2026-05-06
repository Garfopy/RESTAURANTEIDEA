-- Sprint 4C-1: Historial de movimientos de inventario
-- Aplicar DESPUÉS de 005_productos_empresa_id.sql

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `movimientos_inventario` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('entrada','salida','ajuste','merma') NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `stock_antes` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_despues` decimal(10,2) NOT NULL DEFAULT '0.00',
  `motivo` varchar(200) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL COMMENT 'Ej: folio de pedido, número de factura proveedor',
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
