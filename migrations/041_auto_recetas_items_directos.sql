-- ============================================================
-- MIGRACIÓN 041 — Auto-recetas para platillos de consumo directo
-- (bebidas, postres y cualquier ítem donde 1 platillo = 1 unidad de inventario)
--
-- Para cada rest_platillos que:
--   1. NO tiene receta en rest_recetas
--   2. Tiene un rest_ingredientes con el MISMO nombre (case-insensitive)
--      en el mismo restaurante y activo = 1
-- → Crea la receta + el ingrediente 1:1
--
-- IDEMPOTENTE: INSERT IGNORE en recetas (UNIQUE KEY platillo_id),
--              WHERE NOT EXISTS en ingredientes de receta.
-- ============================================================

-- Paso 1: Crear rest_recetas para platillos directos sin receta previa
INSERT IGNORE INTO `rest_recetas` (`platillo_id`, `porciones_base`, `notas`)
SELECT p.id, 1, 'Auto: ítem directo de inventario'
FROM `rest_platillos` p
JOIN `rest_ingredientes` i
  ON  i.restaurante_id = p.restaurante_id
  AND LOWER(i.nombre)  = LOWER(p.nombre)
  AND i.activo = 1
WHERE p.activo = 1
  AND NOT EXISTS (
    SELECT 1 FROM `rest_recetas` r WHERE r.platillo_id = p.id
  );

-- Paso 2: Crear rest_receta_ingredientes (cantidad = 1) para esas recetas
INSERT INTO `rest_receta_ingredientes` (`receta_id`, `ingrediente_id`, `cantidad`, `unidad`, `es_informativo`)
SELECT r.id, i.id, 1, i.unidad_principal, 0
FROM `rest_recetas` r
JOIN `rest_platillos`    p ON p.id = r.platillo_id
JOIN `rest_ingredientes` i
  ON  i.restaurante_id = p.restaurante_id
  AND LOWER(i.nombre)  = LOWER(p.nombre)
  AND i.activo = 1
WHERE NOT EXISTS (
  SELECT 1 FROM `rest_receta_ingredientes` ri WHERE ri.receta_id = r.id
);
