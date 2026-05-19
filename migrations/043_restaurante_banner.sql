-- ============================================================
-- 043 — Banner foto para menú público del restaurante
-- Dependencias: rest_restaurantes
-- ============================================================

ALTER TABLE `rest_restaurantes`
  ADD COLUMN `imagen_banner` VARCHAR(255) NULL DEFAULT NULL
    COMMENT 'Foto de portada del restaurante — se muestra como fondo del hero en el menú público'
    AFTER `logo`;
