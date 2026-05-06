-- Sprint 4C-1: Precios especiales por comprador + tipo de pedido personalizado
-- Aplicar DESPUÉS de 006_movimientos_inventario.sql

SET FOREIGN_KEY_CHECKS = 0;

-- Precios acordados individualmente con cada comprador
CREATE TABLE IF NOT EXISTS `precios_especiales` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `comprador_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `notas` varchar(200) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comprador_producto` (`comprador_id`,`producto_id`),
  KEY `idx_empresa` (`empresa_id`),
  KEY `idx_comprador` (`comprador_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar columnas a pedidos (sin IF NOT EXISTS — compatible con MySQL 5.7)
ALTER TABLE `pedidos`
  ADD COLUMN `tipo`          enum('normal','personalizado') NOT NULL DEFAULT 'normal' AFTER `notas`,
  ADD COLUMN `creado_por_id` int(10) UNSIGNED DEFAULT NULL AFTER `tipo`;

SET FOREIGN_KEY_CHECKS = 1;
