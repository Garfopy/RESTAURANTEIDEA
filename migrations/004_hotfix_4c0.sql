-- ══════════════════════════════════════════════════════════════════════════════
-- CarniHub — Migration 004: Hotfix Sprint 4C-0
-- Ejecutar sobre una BD que ya tiene 001 + 002 + 003 aplicados.
-- Si vas a hacer un reset completo (DROP ALL + reimportar), usa solo
-- 001 → 002 → 003 (ya incluye estos cambios). Este archivo es SOLO para
-- parchear la BD de producción/staging sin resetearla.
-- ══════════════════════════════════════════════════════════════════════════════

-- ── Bug 3: suscripción demo faltante para empresa 1 (buensabor) ──────────────
INSERT IGNORE INTO `suscripciones`
  (`empresa_id`, `plan_id`, `estado`, `ciclo`, `fecha_inicio`, `created_by`)
SELECT 1, id, 'activo', 'mensual', CURDATE(), 1
FROM `planes_saas` WHERE `slug` = 'pro' LIMIT 1;

UPDATE `empresas` SET `suscripcion_estado` = 'activo' WHERE `id` = 1;
