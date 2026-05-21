-- ============================================================
-- 051_separar_presentacion_de_nombre.sql
--
-- Mueve el sufijo de presentación (cantidad + unidad) del
-- nombre a la columna `presentacion` (en `productos`) y a
-- `unidad_principal` (en `rest_ingredientes`).
--
-- Ejemplos:
--   "Mezcal Sol 2 Oz."          → nombre="Mezcal Sol",          presentacion="2 Oz."
--   "Merengues 3 Pzs."          → nombre="Merengues",           presentacion="3 Pzs."
--   "Fruta Cristalizada 150 Gr."→ nombre="Fruta Cristalizada",  presentacion="150 Gr."
--   "Percheron Mezcal Sol 65 Ml."→ nombre="Percheron Mezcal Sol",presentacion="65 Ml."
--   "Palanquetas 1 Pza."        → nombre="Palanquetas",         presentacion="1 Pza."
--   "Tortillas de maíz 4 pzas"  → nombre="Tortillas de maíz",   presentacion="4 pzas"
--
-- IDEMPOTENTE: detecta sólo nombres cuyo sufijo aún coincide
-- con el patrón. Re-ejecutarlo no hace cambios adicionales.
-- ============================================================

-- ── 1) PRODUCTOS (catálogo CarniHub) ─────────────────────────
-- Patrón: termina con "<digitos> <unidad>" donde unidad ∈
--   Pzs. / Pza. / Pzas. / Pzs / Pza / Pzas / pzas / Oz. / Oz / Gr. / Gr / Ml. / Ml
-- Tomamos los últimos 2 tokens separados por espacio.
UPDATE productos
   SET presentacion = TRIM(SUBSTRING_INDEX(nombre, ' ', -2)),
       nombre       = TRIM(SUBSTRING(
                            nombre,
                            1,
                            CHAR_LENGTH(nombre)
                              - CHAR_LENGTH(SUBSTRING_INDEX(nombre, ' ', -2)) - 1
                          ))
 WHERE nombre REGEXP ' [0-9]+ (Pzs|Pza|Pzas|pzas|Oz|Gr|Ml)[.]?$';

-- ── 2) REST_INGREDIENTES (inventario del restaurante) ────────
-- Misma lógica: el sufijo pasa a `unidad_principal`.
-- (La unidad anterior solía ser "pieza"/"kg"; la sustituimos
-- por la presentación específica del producto cuando exista).
UPDATE rest_ingredientes
   SET unidad_principal = TRIM(SUBSTRING_INDEX(nombre, ' ', -2)),
       nombre           = TRIM(SUBSTRING(
                                nombre,
                                1,
                                CHAR_LENGTH(nombre)
                                  - CHAR_LENGTH(SUBSTRING_INDEX(nombre, ' ', -2)) - 1
                              ))
 WHERE nombre REGEXP ' [0-9]+ (Pzs|Pza|Pzas|pzas|Oz|Gr|Ml)[.]?$';

-- ── 3) Verificación rápida (opcional, sólo SELECT) ───────────
-- Después de correr la migración puedes inspeccionar:
--   SELECT id, nombre, presentacion FROM productos
--     WHERE nombre REGEXP ' [0-9]+ (Pzs|Pza|Pzas|pzas|Oz|Gr|Ml)[.]?$';
--   (debe regresar 0 filas — ya no hay sufijos pendientes)
--
--   SELECT id, nombre, unidad_principal FROM rest_ingredientes
--     WHERE nombre REGEXP ' [0-9]+ (Pzs|Pza|Pzas|pzas|Oz|Gr|Ml)[.]?$';
--   (idem)
-- ── Fin ──────────────────────────────────────────────────────
