-- MIGRACIÓN 021 REVERT — Restaurar precio real del plan Básico
-- ⚠️  Ejecutar DESPUÉS de terminar la prueba PayPal Live
-- ⚠️  Luego ir a Panel > Suscripciones > Configurar PayPal > Sincronizar Planes
-- --------------------------------------------------------------------
-- Restaura el precio real del plan Básico (según migración 019)
-- y limpia el paypal_plan_id para que se cree un nuevo plan en PayPal
-- con el precio correcto al sincronizar.

UPDATE `planes_saas`
SET `precio_mensual`       = 5200.00,
    `precio_anual`         = 52000.00,
    `paypal_plan_id`       = NULL,
    `paypal_plan_id_anual` = NULL
WHERE `slug` = 'basico';

-- Verificar resultado
SELECT id, slug, nombre, precio_mensual, precio_anual,
       paypal_plan_id, paypal_plan_id_anual
FROM planes_saas
WHERE slug = 'basico';
