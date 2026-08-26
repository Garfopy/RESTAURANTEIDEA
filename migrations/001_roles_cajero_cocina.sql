-- Agrega los roles nuevos del modelo marketplace: cajero (POS) y cocina (KDS web).
-- Tu tabla `roles` hoy solo trae 1=superadmin, 2=admin_restaurante (ver idactivo_cafeteq.sql).
-- Seguro correr aunque ya existan — INSERT IGNORE no duplica si el slug ya está.

INSERT IGNORE INTO roles (id, nombre, slug) VALUES
  (3, 'Cajero', 'cajero'),
  (4, 'Cocina', 'cocina');
