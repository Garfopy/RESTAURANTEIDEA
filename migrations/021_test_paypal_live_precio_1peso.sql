-- MIGRACIÓN 021 — Precio temporal $1 MXN para prueba PayPal Live
-- ⚠️  SOLO PARA PRUEBA — ejecutar ANTES de sincronizar planes en live
-- ⚠️  Revertir con 021_revert_precio_1peso.sql después de la prueba
-- --------------------------------------------------------------------
-- Baja el plan Básico a $1 MXN mensual y limpia el paypal_plan_id
-- para que "Sincronizar Planes" cree un plan nuevo en Live con ese precio.

UPDATE `planes_saas`
SET `precio_mensual`     = 1.00,
    `precio_anual`       = 1.00,
    `paypal_plan_id`     = NULL,
    `paypal_plan_id_anual` = NULL
WHERE `slug` = 'basico';

-- Verificar resultado
SELECT id, slug, nombre, precio_mensual, precio_anual,
       paypal_plan_id, paypal_plan_id_anual
FROM planes_saas
WHERE slug = 'basico';
