-- ============================================================
-- Migración 021 — Hotfix: agrega empresa_id a facturas
-- Ejecutar si da error "Unknown column 'empresa_id' in facturas"
-- La tabla facturas existía antes sin esta columna.
-- ============================================================

-- 1. Agrega la columna (fallará silenciosamente en MySQL si ya existe;
--    en phpMyAdmin simplemente ignora el error 1060 Duplicate column)
ALTER TABLE `facturas`
  ADD COLUMN `empresa_id` INT UNSIGNED NULL AFTER `pedido_id`;

-- 2. Índice de búsqueda por empresa
ALTER TABLE `facturas`
  ADD KEY `idx_empresa_id` (`empresa_id`);

-- 3. Rellena empresa_id desde el pedido para filas existentes
UPDATE `facturas` f
  JOIN `pedidos` p ON p.id = f.pedido_id
  SET f.empresa_id = p.empresa_id
WHERE f.empresa_id IS NULL;

-- 4. Hace la columna NOT NULL ahora que está poblada
ALTER TABLE `facturas`
  MODIFY COLUMN `empresa_id` INT UNSIGNED NOT NULL;

-- 5. Foreign key (solo si aún no existe)
ALTER TABLE `facturas`
  ADD CONSTRAINT `fk_facturas_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

-- 6. Credenciales de Factura-lo en global_settings (si no se corrió migración 020)
INSERT INTO `global_settings` (`clave`, `valor`, `tipo`, `grupo`, `etiqueta`) VALUES
('facturalo_token', '', 'string', 'facturacion', 'Token API de factura-lo.mx'),
('facturalo_rfc',   '', 'string', 'facturacion', 'RFC del emisor')
ON DUPLICATE KEY UPDATE `etiqueta` = VALUES(`etiqueta`);
