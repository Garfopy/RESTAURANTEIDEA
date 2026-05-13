-- Migration 031: Link rest_restaurantes to CarniHub sucursales
-- Each restaurant portal "local" maps to a CarniHub sucursal (delivery destination)
-- This enables per-sucursal inventory import when an order is delivered

ALTER TABLE rest_restaurantes
  ADD COLUMN sucursal_id INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'FK to sucursales.id — links this local to a CarniHub delivery sucursal',
  ADD CONSTRAINT fk_rest_restaurante_sucursal
    FOREIGN KEY (sucursal_id) REFERENCES sucursales(id) ON DELETE SET NULL;

CREATE INDEX idx_rest_restaurantes_sucursal ON rest_restaurantes(sucursal_id);
