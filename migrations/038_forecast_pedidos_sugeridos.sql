-- ============================================================
-- 038 — Forecast de Inventario y Pedidos Sugeridos
-- Dependencias: rest_restaurantes, rest_ingredientes,
--               empresas, productos, pedidos, usuarios
-- ============================================================

-- 1. Lead time por ingrediente
ALTER TABLE `rest_ingredientes`
  ADD COLUMN `dias_entrega` SMALLINT UNSIGNED NULL DEFAULT 1
    COMMENT 'Días que tarda el proveedor en entregar este ingrediente'
    AFTER `proveedor_nombre`;

-- 2. Pedidos sugeridos (órdenes de compra inteligentes hacia una empresa CarniHub)
CREATE TABLE IF NOT EXISTS `rest_pedidos_sugeridos` (
  `id`                 INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `restaurante_id`     INT UNSIGNED    NOT NULL,
  `empresa_id`         INT UNSIGNED    NOT NULL COMMENT 'Empresa CarniHub proveedora',
  `estado`             ENUM('borrador','sugerido','aprobado','rechazado','convertido')
                                       NOT NULL DEFAULT 'sugerido',
  `total_estimado`     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  `notas`              TEXT            NULL,
  `usuario_id`         INT UNSIGNED    NULL COMMENT 'Usuario que generó o aprobó',
  `pedido_carnihub_id` INT UNSIGNED    NULL COMMENT 'ID en tabla pedidos una vez convertido',
  `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aprobado_at`        DATETIME        NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rps_restaurante` (`restaurante_id`),
  KEY `idx_rps_empresa`     (`empresa_id`),
  KEY `idx_rps_estado`      (`estado`),
  CONSTRAINT `fk_rps_restaurante` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`),
  CONSTRAINT `fk_rps_empresa`     FOREIGN KEY (`empresa_id`)     REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_rps_usuario`     FOREIGN KEY (`usuario_id`)     REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Pedidos de reabastecimiento sugeridos por el sistema de forecast';

-- 3. Items de cada pedido sugerido
CREATE TABLE IF NOT EXISTS `rest_pedido_sugerido_items` (
  `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `pedido_sugerido_id`    INT UNSIGNED    NOT NULL,
  `ingrediente_id`        INT UNSIGNED    NOT NULL,
  `carnihub_producto_id`  INT UNSIGNED    NOT NULL COMMENT 'Producto en el catálogo CarniHub',
  `cantidad_sugerida`     DECIMAL(10,3)   NOT NULL,
  `cantidad_aprobada`     DECIMAL(10,3)   NULL COMMENT 'Ajustada manualmente al aprobar',
  `unidad`                VARCHAR(20)     NOT NULL DEFAULT 'kg',
  `precio_unit_estimado`  DECIMAL(10,4)   NOT NULL DEFAULT 0.0000,
  `subtotal_estimado`     DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_rpsi_pedido`      (`pedido_sugerido_id`),
  KEY `idx_rpsi_ingrediente` (`ingrediente_id`),
  CONSTRAINT `fk_rpsi_pedido`      FOREIGN KEY (`pedido_sugerido_id`) REFERENCES `rest_pedidos_sugeridos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rpsi_ingrediente` FOREIGN KEY (`ingrediente_id`)     REFERENCES `rest_ingredientes` (`id`),
  CONSTRAINT `fk_rpsi_producto`    FOREIGN KEY (`carnihub_producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Items de los pedidos sugeridos por forecast';
