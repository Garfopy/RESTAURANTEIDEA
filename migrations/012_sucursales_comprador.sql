-- CarniHub — Migration 012: Sucursales del Comprador + Pedido Multi-Destino
-- Sprint 4C-3 — 2026-05-06
-- Las sucursales son puntos de entrega del COMPRADOR, no del productor.
-- Un comprador puede distribuir un pedido entre sus sucursales para alcanzar mejor precio por volumen.

-- -----------------------------------------------------------------------
-- 1. Asociar sucursales al comprador (usuario) que las administra
-- -----------------------------------------------------------------------
ALTER TABLE `sucursales`
  ADD COLUMN `comprador_id` INT(10) UNSIGNED NULL DEFAULT NULL
    COMMENT 'Usuario comprador dueño de esta sucursal (NULL = legacy sin comprador asignado)'
    AFTER `empresa_id`,
  ADD CONSTRAINT `fk_sucursal_comprador`
    FOREIGN KEY (`comprador_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- -----------------------------------------------------------------------
-- 2. Enriquecer pedido_sucursal con notas y costo de envío por parada
-- -----------------------------------------------------------------------
ALTER TABLE `pedido_sucursal`
  ADD COLUMN `notas` TEXT NULL DEFAULT NULL
    COMMENT 'Instrucciones especiales para esta entrega (ej: "tocar en puerta trasera")'
    AFTER `hora_entrega`,
  ADD COLUMN `costo_envio_sucursal` DECIMAL(10,2) NOT NULL DEFAULT 0.00
    COMMENT 'Costo de envío asignado por la empresa a esta parada específica'
    AFTER `notas`;

-- -----------------------------------------------------------------------
-- 3. Detalle de distribución: qué productos/cantidades van a cada sucursal
-- -----------------------------------------------------------------------
CREATE TABLE `pedido_sucursal_detalle` (
  `id`                INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pedido_sucursal_id` INT(10) UNSIGNED NOT NULL,
  `pedido_detalle_id`  INT(10) UNSIGNED NOT NULL COMMENT 'Línea del pedido original de donde se toman las unidades',
  `producto_id`        INT(10) UNSIGNED NOT NULL,
  `cantidad`           DECIMAL(10,3) NOT NULL COMMENT 'Cantidad/kg destinados a esta sucursal',
  `precio_unit`        DECIMAL(10,2) NOT NULL,
  `subtotal`           DECIMAL(12,2) NOT NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_psd_pedido_sucursal`
    FOREIGN KEY (`pedido_sucursal_id`) REFERENCES `pedido_sucursal`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_psd_pedido_detalle`
    FOREIGN KEY (`pedido_detalle_id`) REFERENCES `pedido_detalle`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_psd_producto`
    FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Distribución de productos por sucursal dentro de un pedido multi-destino';

-- -----------------------------------------------------------------------
-- 4. Datos de prueba: sucursales para el comprador demo (empresa 1, usuario 13)
-- -----------------------------------------------------------------------
INSERT INTO `sucursales`
  (`empresa_id`, `comprador_id`, `nombre`, `direccion`, `lat`, `lng`, `responsable`, `telefono`, `activo`)
VALUES
  (1, 13, 'Sucursal Norte',   'Av. Insurgentes Norte 123, CDMX', 19.44260, -99.13320, 'Carlos López',   '5512345678', 1),
  (1, 13, 'Sucursal Centro',  'Calle Madero 45, CDMX',           19.43260, -99.13900, 'Ana Martínez',   '5598765432', 1),
  (1, 13, 'Cocina Central',   'Blvd. Reforma 200, CDMX',         19.42680, -99.16800, 'Pedro Sánchez',  '5567891234', 1);
