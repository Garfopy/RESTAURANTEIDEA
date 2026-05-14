-- =============================================================
-- 035_propinas_mesero.sql
-- Agrega mesero_id y propina_entregada a rest_tickets
-- para la tabla de propinas por mesero en el admin
-- =============================================================

ALTER TABLE rest_tickets
  ADD COLUMN mesero_id        INT          NULL    COMMENT 'Mesero principal de la visita (del primer pedido)',
  ADD COLUMN propina_entregada TINYINT(1)  NOT NULL DEFAULT 0 COMMENT '1 = propina ya entregada al mesero';

ALTER TABLE rest_tickets
  ADD INDEX idx_tickets_mesero (mesero_id),
  ADD INDEX idx_tickets_propina_entregada (propina_entregada);
