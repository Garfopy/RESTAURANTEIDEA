-- ══════════════════════════════════════════════════════════════════════════════
-- CarniHub — Migration 005: Agregar empresa_id a productos
-- Ejecutar sobre BD que ya tiene 001 + 002 + 003 + 004 aplicados.
-- ══════════════════════════════════════════════════════════════════════════════

-- Agregar columna empresa_id a productos (los existentes quedan asignados a empresa 1)
ALTER TABLE `productos`
  ADD COLUMN `empresa_id` INT(10) UNSIGNED NOT NULL DEFAULT 1
  AFTER `id`;

-- Índice para búsquedas por empresa
ALTER TABLE `productos`
  ADD KEY `idx_empresa` (`empresa_id`);

-- FK a empresas
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_prod_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);
