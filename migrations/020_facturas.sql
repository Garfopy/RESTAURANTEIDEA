-- ============================================================
-- Migración 020 — Módulo de Facturas (CFDI)
-- ============================================================

-- Tabla de facturas emitidas por la plataforma a cada empresa
CREATE TABLE IF NOT EXISTS `facturas` (
  `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `pedido_id`     INT UNSIGNED   NOT NULL,
  `empresa_id`    INT UNSIGNED   NOT NULL,
  `uuid_cfdi`     VARCHAR(36)    NOT NULL,
  `xml_url`       TEXT           NULL,
  `pdf_url`       TEXT           NULL,
  `total`         DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `fecha_emision` DATETIME       NOT NULL,
  `estado`        ENUM('timbrada','cancelada') NOT NULL DEFAULT 'timbrada',
  `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_uuid_cfdi` (`uuid_cfdi`),
  KEY `idx_empresa_id` (`empresa_id`),
  KEY `idx_pedido_id` (`pedido_id`),
  CONSTRAINT `fk_facturas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_facturas_pedido`  FOREIGN KEY (`pedido_id`)  REFERENCES `pedidos`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Credenciales de Factura-lo en global_settings
INSERT INTO `global_settings` (`clave`, `valor`, `tipo`, `grupo`, `etiqueta`) VALUES
('facturalo_token', '', 'string', 'facturacion', 'Token API de factura-lo.mx'),
('facturalo_rfc',   '', 'string', 'facturacion', 'RFC del emisor (CarniHub)')
ON DUPLICATE KEY UPDATE `etiqueta` = VALUES(`etiqueta`);
