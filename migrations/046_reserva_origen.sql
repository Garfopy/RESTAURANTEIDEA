-- ============================================================
-- 046 — Reservaciones: columna origen
-- Dependencias: rest_reservaciones (024)
-- ============================================================
-- 'restaurante' = creada por el admin/staff desde el panel
-- 'comensal'    = solicitud pública recibida vía QR
-- ============================================================

ALTER TABLE `rest_reservaciones`
  ADD COLUMN IF NOT EXISTS `origen`
    ENUM('restaurante','comensal') NOT NULL DEFAULT 'restaurante'
    COMMENT 'restaurante = admin/staff; comensal = solicitud pública por QR'
    AFTER `estado`;
