-- ============================================================
-- 039 — Empresa proveedora por defecto para restaurantes
-- Dependencias: rest_restaurantes, empresas
-- ============================================================
-- Permite configurar qué empresa CarniHub surtirá al restaurante.
-- El sistema de forecast auto-genera pedidos hacia esta empresa.
-- ============================================================

ALTER TABLE `rest_restaurantes`
  ADD COLUMN `empresa_proveedor_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Empresa CarniHub proveedora por defecto para pedidos automáticos de forecast'
    AFTER `activo`,
  ADD KEY `idx_rr_empresa_proveedor` (`empresa_proveedor_id`),
  ADD CONSTRAINT `fk_rr_empresa_proveedor`
    FOREIGN KEY (`empresa_proveedor_id`) REFERENCES `empresas` (`id`)
    ON DELETE SET NULL;
