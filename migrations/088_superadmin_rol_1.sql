-- Jungle Pizza
-- Transfiere la administracion superior al rol 1 (superadmin).
-- El rol 12/programador permanece solo por compatibilidad historica,
-- pero ya no recibe privilegios superiores desde la aplicacion.

START TRANSACTION;

INSERT INTO `roles` (`id`, `nombre`, `slug`)
VALUES (1, 'Superadministrador', 'superadmin')
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `slug` = VALUES(`slug`);

-- Crea la cuenta superior. Si el correo ya existe, lo convierte en
-- superadministrador y restablece su contraseña a Danjohn007.
INSERT INTO `usuarios` (
  `nombre`,
  `apellido_paterno`,
  `apellido_materno`,
  `email`,
  `email_verificado`,
  `primer_login_completado`,
  `password`,
  `rol_id`,
  `empresa_id`,
  `restaurante_id`,
  `restaurante_activo`,
  `activo`
) VALUES (
  'Super',
  'Administrador',
  NULL,
  'admin@junglezihua.com',
  1,
  1,
  '$2y$10$4pdAEBTVeAfHLSwh9f.sHejSPChcGD4nXmE5kiTeDJFV0sXqhKbjy',
  1,
  NULL,
  NULL,
  0,
  1
)
ON DUPLICATE KEY UPDATE
  `rol_id` = 1,
  `password` = VALUES(`password`),
  `email_verificado` = 1,
  `primer_login_completado` = 1,
  `activo` = 1;

COMMIT;
