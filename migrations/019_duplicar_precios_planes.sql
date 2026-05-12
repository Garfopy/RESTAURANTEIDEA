-- ══════════════════════════════════════════════════════════════════════════════
-- CarniHub — Migration 019: Actualización de precios de planes SaaS (x2)
-- Ejecutar después de 018_paypal_plan_id_anual.sql
-- ══════════════════════════════════════════════════════════════════════════════

-- Básico: $2,600/mes → $5,200/mes | $26,000/año → $52,000/año
UPDATE `planes_saas`
SET `precio_mensual` = 5200.00,
    `precio_anual`   = 52000.00
WHERE `slug` = 'basico';

-- Pro: $3,200/mes → $6,400/mes | $32,000/año → $64,000/año
UPDATE `planes_saas`
SET `precio_mensual` = 6400.00,
    `precio_anual`   = 64000.00
WHERE `slug` = 'pro';

-- Empresa: $4,000/mes → $8,000/mes | $40,000/año → $80,000/año
UPDATE `planes_saas`
SET `precio_mensual` = 8000.00,
    `precio_anual`   = 80000.00
WHERE `slug` = 'empresa';

-- VERIFICACIÓN
SELECT id, slug, nombre, precio_mensual, precio_anual FROM planes_saas;
