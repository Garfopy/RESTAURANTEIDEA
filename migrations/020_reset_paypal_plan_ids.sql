-- ══════════════════════════════════════════════════════════════════════════════
-- CarniHub — Migration 020: Limpiar PayPal Plan IDs para forzar re-sincronización
-- Ejecutar DESPUÉS de 019_duplicar_precios_planes.sql
--
-- MOTIVO: Los PayPal Plans son inmutables — el precio queda fijo al crearlos.
-- Al duplicar los precios en planes_saas, los paypal_plan_id viejos siguen
-- cobrando los precios anteriores. Se deben crear NUEVOS planes en PayPal.
--
-- PASO 1 (esta migración): vaciar paypal_plan_id y paypal_plan_id_anual.
-- PASO 2 (manual): Panel → Suscripciones → Configurar PayPal → Sincronizar Planes.
-- ══════════════════════════════════════════════════════════════════════════════

UPDATE `planes_saas`
SET `paypal_plan_id`       = NULL,
    `paypal_plan_id_anual` = NULL
WHERE `slug` IN ('basico', 'pro', 'empresa');

-- VERIFICACIÓN: ambas columnas deben salir NULL
SELECT id, slug, nombre, precio_mensual, precio_anual,
       paypal_plan_id, paypal_plan_id_anual
FROM planes_saas;
