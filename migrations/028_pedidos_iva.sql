-- ── Migración 028: Columna IVA en pedidos ──────────────────────
-- Los precios del catálogo incluyen IVA (16 %).
-- Esta columna almacena el IVA extraído del subtotal para reportes y facturas.
-- Fórmula: iva = ROUND(subtotal * 16 / 116, 2)
--
-- MySQL 5.7 — NO soporta ADD COLUMN IF NOT EXISTS
-- Ejecutar solo si la columna no existe en producción.

ALTER TABLE `pedidos`
  ADD COLUMN `iva` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`;
