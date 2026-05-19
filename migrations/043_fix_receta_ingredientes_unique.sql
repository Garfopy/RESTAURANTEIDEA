-- ============================================================
-- 043 — Deduplicar rest_receta_ingredientes + UNIQUE KEY
-- Dependencias: rest_receta_ingredientes
-- ============================================================
-- Las migraciones 036 y 041 usaban INSERT IGNORE sin que
-- existiera una clave UNIQUE en (receta_id, ingrediente_id),
-- por lo que re-ejecuciones o JOINs con ingredientes duplicados
-- generaron filas repetidas.
-- Paso 1: elimina duplicados conservando el de menor id.
-- Paso 2: agrega el UNIQUE KEY para prevenir futuros duplicados.
-- ============================================================

-- Paso 1: eliminar filas duplicadas (conserva id más bajo)
DELETE ri1
FROM `rest_receta_ingredientes` ri1
INNER JOIN `rest_receta_ingredientes` ri2
  ON  ri2.receta_id      = ri1.receta_id
  AND ri2.ingrediente_id = ri1.ingrediente_id
  AND ri2.id             < ri1.id;

-- Paso 2: agregar restricción UNIQUE
ALTER TABLE `rest_receta_ingredientes`
  ADD UNIQUE KEY `uq_receta_ingrediente` (`receta_id`, `ingrediente_id`);
