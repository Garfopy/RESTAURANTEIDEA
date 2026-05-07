-- ============================================================
-- MIGRACIÓN 018 — Agregar campo paypal_plan_id_anual a planes_saas
-- Necesario para soportar 2 Plan IDs por plan (mensual + anual)
-- ============================================================

ALTER TABLE `planes_saas`
ADD COLUMN `paypal_plan_id_anual` VARCHAR(50) NULL
    COMMENT 'Plan ID de PayPal para ciclo anual'
    AFTER `paypal_plan_id`;

-- VERIFICACIÓN
SELECT id, slug, nombre, paypal_plan_id, paypal_plan_id_anual FROM planes_saas;
