-- ============================================================
-- CarniHub v3.2 — Nuevos roles para módulo restaurante
-- Ejecutar DESPUÉS de 022–024
-- ============================================================

-- Nuevos roles: mesero (7), chef (8), portero (9)
INSERT IGNORE INTO `roles` (`id`, `nombre`, `slug`) VALUES
  (7, 'Mesero',  'mesero'),
  (8, 'Chef',    'chef'),
  (9, 'Portero', 'portero');

-- Columna restaurante_id en usuarios (para staff del restaurante)
ALTER TABLE `usuarios`
  ADD COLUMN `restaurante_id` INT UNSIGNED NULL AFTER `empresa_id`;

-- Flag en usuarios para compradores con restaurante activo
ALTER TABLE `usuarios`
  ADD COLUMN `restaurante_activo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `restaurante_id`;
