-- Migración 020: Precio fijo opcional por combo
-- Permite que el admin de empresa asigne un precio total al combo (con o sin descuento)

ALTER TABLE combos
    ADD COLUMN precio DECIMAL(10,2) NULL DEFAULT NULL AFTER descripcion;
