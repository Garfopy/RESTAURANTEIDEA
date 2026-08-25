-- ============================================================
-- 072_role_barra.sql
-- Agrega el rol de staff "barra" para una pantalla KDS separada
-- que atiende solamente bebidas.
-- ============================================================

INSERT IGNORE INTO roles (id, nombre, slug)
VALUES (11, 'Barra', 'barra');

