-- Siembra la cuenta superadmin del panel de plataforma.
-- La tabla `usuarios` no traia ninguna cuenta con rol_id=1 (los usuarios existentes son
-- admin_restaurante/cocina). La migracion vieja que hacia esto (088_superadmin_rol_1.sql)
-- se elimino en la limpieza del 2026-08-25.
--
-- ⚠️ ESTE REPO ES PUBLICO: no pongas hashes ni passwords reales en este archivo.
--
-- Para correr esta migracion, genera primero tu propio hash y sustituyelo abajo:
--
--   php -r "echo password_hash('TU_PASSWORD_AQUI', PASSWORD_DEFAULT), PHP_EOL;"
--
-- Luego reemplaza el correo y el placeholder REEMPLAZAR_CON_TU_HASH, y ejecuta.
-- El hash real de la cuenta que ya corre en produccion se comparte por canal privado,
-- nunca por aqui.

START TRANSACTION;

INSERT INTO `usuarios` (
  `nombre`, `apellido_paterno`, `apellido_materno`, `email`, `email_verificado`,
  `primer_login_completado`, `password`, `rol_id`, `empresa_id`, `restaurante_id`,
  `restaurante_activo`, `activo`
) VALUES (
  'Super', 'Admin', NULL, 'superadmin@ejemplo.com', 1,
  0, 'REEMPLAZAR_CON_TU_HASH', 1, NULL, NULL,
  0, 1
)
ON DUPLICATE KEY UPDATE
  `rol_id` = 1,
  `password` = VALUES(`password`),
  `email_verificado` = 1,
  `activo` = 1;

COMMIT;
