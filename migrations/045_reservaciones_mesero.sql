-- ============================================================
-- 045 — Reservaciones: mesero_id + updated_at
-- Dependencias: rest_reservaciones (024), usuarios (001)
-- ============================================================
-- Conecta cada reservación con el mesero asignado según zona
-- (via rest_mesero_turno). Se auto-asigna al guardar si la
-- mesa pertenece a una zona con mesero de turno activo.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `rest_reservaciones`
  ADD COLUMN IF NOT EXISTS `mesero_id`  INT UNSIGNED NULL DEFAULT NULL AFTER `comensal_id`,
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

ALTER TABLE `rest_reservaciones`
  ADD CONSTRAINT `fk_rres_mesero`
    FOREIGN KEY (`mesero_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
