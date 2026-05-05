-- Migración 010: precio_original en pedido_detalle
-- Sprint 4C-1 — Permite al admin ajustar (bajar) precios al aprobar un pedido
-- Se guarda el precio original para mostrar la diferencia en el detalle

ALTER TABLE pedido_detalle ADD COLUMN precio_original DECIMAL(10,2) NULL DEFAULT NULL;
