-- Elimina el ultimo remanente del "modo social" en la base de datos.
-- Decision del equipo (plan-web-marketplace.md, 2026-08-25): el modo social se quita por
-- completo — controlador, vistas, rutas Y tablas.
--
-- Estado antes de esta migracion:
--   social_reports, social_blocks, social_photo_moderation → YA no existen (se fueron con el
--     recorte de esquema del 2026-08-25)
--   moderation_actions → sobrevivio huerfana: nada la escribe ni la lee desde que se borro
--     RestModeracionController, y las tablas a las que apuntaba (social_photo_moderation via
--     photo_id, mobile_usuarios via user_id) ya no tienen su contraparte social.
--
-- Si mas adelante Superadmin necesita moderar fotos/resenas de producto, sera un modulo NUEVO
-- con su propia tabla de cola (ver plan-web-superadmin.md §2) — no se reusa esta.
--
-- ⚠️ Revisa si tienes filas que quieras conservar como historico antes de correrla:
--     SELECT COUNT(*) FROM moderation_actions;
-- En la base de produccion al 2026-08-26 la tabla esta vacia.

START TRANSACTION;

DROP TABLE IF EXISTS `moderation_actions`;

-- Estas ya no existian en produccion, van por si algun entorno local quedo rezagado.
DROP TABLE IF EXISTS `social_photo_moderation`;
DROP TABLE IF EXISTS `social_reports`;
DROP TABLE IF EXISTS `social_blocks`;

COMMIT;
