-- ============================================================
-- 042_codigos_postres_bebidas.sql
-- Crea la materia prima (DP1-DP18, B1-B34) correspondiente a los
-- platillos de Dulces y Postres y Bebidas insertados en 036, asegura
-- una receta por platillo y vincula la MP para que descuente stock
-- al entregar el pedido.
--
-- Requisito previo: migración 036 aplicada (con codigo embebido en
-- los INSERT de rest_platillos para DP1-DP18 y B1-B34).
-- Seguro de re-ejecutar: usa INSERT IGNORE.
-- ============================================================

SET @rest_id = 1; -- Cambiar si el restaurante_id es distinto de 1

-- ── 1. Insertar Materia Prima DP1-DP18 (postres) y B1-B34 (bebidas) ───────
-- Mismo codigo que el platillo. Tabla separada, no hay conflicto.

INSERT IGNORE INTO `rest_ingredientes`
  (restaurante_id, codigo, tipo, nombre, unidad_principal, costo_unitario, stock, stock_minimo, activo)
VALUES
  -- Postres
  (@rest_id, 'DP1',  'materia_prima', 'Ate de Guayaba con Queso 6 Pzs.',       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP2',  'materia_prima', 'Ate de Membrillo con Queso 6 Pzs.',     'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP3',  'materia_prima', 'Glorias de Leche Quemada 3 Pzs.',       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP4',  'materia_prima', 'Obleas de Cajeta 3 Pzs.',               'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP5',  'materia_prima', 'Camote de Puebla 3 Pzs.',               'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP6',  'materia_prima', 'Borrachitos de Fresa 5 Pzs.',           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP7',  'materia_prima', 'Fruta Cristalizada Higo 150 Gr.',       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP8',  'materia_prima', 'Fruta Cristalizada Pera 150 Gr.',       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP9',  'materia_prima', 'Fruta Cristalizada Calabazete 150 Gr.', 'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP10', 'materia_prima', 'Orejonas 150 Gr.',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP11', 'materia_prima', 'Cocadas 3 Pzs.',                        'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP12', 'materia_prima', 'Palanquetas 1 Pza.',                    'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP13', 'materia_prima', 'Merengues 3 Pzs.',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP14', 'materia_prima', 'Natillas Qro. 150 Gr.',                 'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP15', 'materia_prima', 'Natillas Bernal 4 Pzs.',                'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP16', 'materia_prima', 'Ollitas de Tamarindo 3 Pzs.',           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP17', 'materia_prima', 'Tamal de Maíz Dulce de Piña 2 Pzs.',    'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'DP18', 'materia_prima', 'Tamal de Dulce de Fresa 2 Pzs.',        'porcion', 0.00, 999.000, 0.000, 1),
  -- Bebidas
  (@rest_id, 'B1',  'bebida', 'Mezcal Sol 2 Oz.',                       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B2',  'bebida', 'Mezcal Luna 2 Oz.',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B3',  'bebida', 'Mezcal Orgullo 2 Oz.',                   'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B4',  'bebida', 'Mezcal Noche 2 Oz.',                     'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B5',  'bebida', 'Mezcal Amor 2 Oz.',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B6',  'bebida', 'Tequila Blanco 2 Oz.',                   'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B7',  'bebida', 'Tequila Reposado 2 Oz.',                 'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B8',  'bebida', 'Tequila Añejo 2 Oz.',                    'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B9',  'bebida', 'Cerveza Artesanal Clara',                'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B10', 'bebida', 'Cerveza Artesanal Morena',               'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B11', 'bebida', 'Cerveza Artesanal Oscura',               'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B12', 'bebida', 'Cocktail de Mezcal con Tamarindo',       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B13', 'bebida', 'Cocktail de Mezcal con Jamaica',         'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B14', 'bebida', 'Cocktail de Mezcal Margarita',           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B15', 'bebida', 'Cocktail de Tequila con Tamarindo',      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B16', 'bebida', 'Coctel de Tequila con Jamaica',          'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B17', 'bebida', 'Cocktail de Tequila Margarita',          'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B18', 'bebida', 'Café de Olla',                           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B19', 'bebida', 'Carajillo sin Cafeína Amanecer',         'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B20', 'bebida', 'Carajillo sin Cafeína Anochecer',        'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B21', 'bebida', 'Agua de Horchata',                       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B22', 'bebida', 'Agua de Jamaica',                        'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B23', 'bebida', 'Agua de Tamarindo',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B24', 'bebida', 'Chocolate con Agua',                     'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B25', 'bebida', 'Chocolate con Leche',                    'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B26', 'bebida', 'Atole de Fresa',                         'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B27', 'bebida', 'Atole de Vainilla',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B28', 'bebida', 'Agua Mineral',                           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B29', 'bebida', 'Agua Sola',                              'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B30', 'bebida', 'Percherón Mezcal Sol 65 Ml.',            'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B31', 'bebida', 'Percherón Mezcal Luna 65 Ml.',           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B32', 'bebida', 'Percherón Mezcal Orgullo 65 Ml.',        'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B33', 'bebida', 'Percherón Mezcal Amor 65 Ml.',           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'B34', 'bebida', 'Percherón Mezcal Noche 65 Ml.',          'porcion', 0.00, 999.000, 0.000, 1);

-- ── 2. Asegurar fila en rest_recetas para cada platillo DP* / B* ──────────

INSERT IGNORE INTO rest_recetas (platillo_id, porciones_base, notas)
SELECT p.id, 1,
  CONCAT('Servir ', p.nombre, '. Descuenta 1 porción del ingrediente ', p.codigo, '.')
FROM rest_platillos p
WHERE p.restaurante_id = @rest_id
  AND (p.codigo REGEXP '^DP[0-9]+$' OR p.codigo REGEXP '^B[0-9]+$');

-- ── 3. Vincular MP (componente principal) a cada receta DP* / B* ──────────
-- es_informativo=0: descuenta stock al pasar el pedido a 'entregado'.
-- Join por codigo: mp.codigo = p.codigo (mismo identificador).

INSERT IGNORE INTO rest_receta_ingredientes
  (receta_id, ingrediente_id, cantidad, unidad, es_informativo, precio_extra)
SELECT r.id,
       mp.id,
       1,
       'porcion',
       0,
       0.00
FROM rest_recetas r
JOIN rest_platillos p   ON p.id = r.platillo_id
JOIN rest_ingredientes mp
       ON mp.restaurante_id = p.restaurante_id
      AND mp.codigo = p.codigo
      AND mp.tipo IN ('materia_prima','bebida')
WHERE p.restaurante_id = @rest_id
  AND (p.codigo REGEXP '^DP[0-9]+$' OR p.codigo REGEXP '^B[0-9]+$');

-- ── 4. Reasignar categoría correcta a platillos DP* / B* de La Comalada ───
-- Corrige casos en los que un LIMIT 1 sin ORDER BY apuntó a una categoría
-- duplicada/incorrecta. Elige la categoría con id más alto (la que insertó
-- 036, normalmente la que tiene descripción poblada).

SET @cat_postres_id = (
  SELECT id FROM rest_categorias_menu
  WHERE restaurante_id = @rest_id AND nombre = 'Dulces y Postres'
  ORDER BY id DESC LIMIT 1
);

SET @cat_bebidas_id = (
  SELECT id FROM rest_categorias_menu
  WHERE restaurante_id = @rest_id AND nombre = 'Bebidas'
  ORDER BY id DESC LIMIT 1
);

UPDATE rest_platillos
   SET categoria_id = @cat_postres_id
 WHERE restaurante_id = @rest_id
   AND codigo REGEXP '^DP[0-9]+$'
   AND @cat_postres_id IS NOT NULL;

UPDATE rest_platillos
   SET categoria_id = @cat_bebidas_id
 WHERE restaurante_id = @rest_id
   AND codigo REGEXP '^B[0-9]+$'
   AND @cat_bebidas_id IS NOT NULL;

-- ── Fin ─────────────────────────────────────────────────────────────────────
