-- ============================================================
-- 042_codigos_postres_bebidas.sql
-- Asigna codigos DP1-DP18 (Dulces y Postres) y B1-B34 (Bebidas)
-- a los platillos ya insertados en 036, crea sus MP correspondientes
-- (MP-DP1..MP-DP18 y MP-B1..MP-B34) y los vincula a las recetas para
-- que descuenten stock al entregar el pedido.
--
-- Requisitos: migraciones 036 y 040 aplicadas.
-- Ejecutar UNA SOLA VEZ. INSERT IGNORE / UPDATE condicional la hacen
-- segura de re-ejecutar.
-- ============================================================

SET @rest_id = 1; -- Cambiar si el restaurante_id es distinto de 1

-- ── 1. Codigos DP1-DP18 en rest_platillos (Dulces y Postres) ──────────────

UPDATE `rest_platillos` SET codigo = 'DP1', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Ate de Guayaba con Queso 6 Pzs.';
UPDATE `rest_platillos` SET codigo = 'DP2', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Ate de Membrillo con Queso 6 Pzs.';
UPDATE `rest_platillos` SET codigo = 'DP3', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Glorias de Leche Quemada 3 Pzs.';
UPDATE `rest_platillos` SET codigo = 'DP4', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Obleas de Cajeta 3 Pzs.';
UPDATE `rest_platillos` SET codigo = 'DP5', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Camote de Puebla 3 Pzs.';
UPDATE `rest_platillos` SET codigo = 'DP6', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Borrachitos de Fresa 5 Pzs.';
UPDATE `rest_platillos` SET codigo = 'DP7', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Fruta Cristalizada Higo 150 Gr.';
UPDATE `rest_platillos` SET codigo = 'DP8', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Fruta Cristalizada Pera 150 Gr.';
UPDATE `rest_platillos` SET codigo = 'DP9', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Fruta Cristalizada Calabazete 150 Gr.';
UPDATE `rest_platillos` SET codigo = 'DP10', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Orejonas 150 Gr.';
UPDATE `rest_platillos` SET codigo = 'DP11', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Cocadas 3 Pzs.';
UPDATE `rest_platillos` SET codigo = 'DP12', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Palanquetas 1 Pza.';
UPDATE `rest_platillos` SET codigo = 'DP13', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Merengues 3 Pzs.';
UPDATE `rest_platillos` SET codigo = 'DP14', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Natillas Qro. 150 Gr.';
UPDATE `rest_platillos` SET codigo = 'DP15', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Natillas Bernal 4 Pzs.';
UPDATE `rest_platillos` SET codigo = 'DP16', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Ollitas de Tamarindo 3 Pzs.';
UPDATE `rest_platillos` SET codigo = 'DP17', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Tamal de Maíz Dulce de Piña 2 Pzs.';
UPDATE `rest_platillos` SET codigo = 'DP18', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Tamal de Dulce de Fresa 2 Pzs.';

-- ── 2. Codigos B1-B34 en rest_platillos (Bebidas) ─────────────────────────

UPDATE `rest_platillos` SET codigo = 'B1', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Mezcal Sol 2 Oz.';
UPDATE `rest_platillos` SET codigo = 'B2', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Mezcal Luna 2 Oz.';
UPDATE `rest_platillos` SET codigo = 'B3', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Mezcal Orgullo 2 Oz.';
UPDATE `rest_platillos` SET codigo = 'B4', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Mezcal Noche 2 Oz.';
UPDATE `rest_platillos` SET codigo = 'B5', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Mezcal Amor 2 Oz.';
UPDATE `rest_platillos` SET codigo = 'B6', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Tequila Blanco 2 Oz.';
UPDATE `rest_platillos` SET codigo = 'B7', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Tequila Reposado 2 Oz.';
UPDATE `rest_platillos` SET codigo = 'B8', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Tequila Añejo 2 Oz.';
UPDATE `rest_platillos` SET codigo = 'B9', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Cerveza Artesanal Clara';
UPDATE `rest_platillos` SET codigo = 'B10', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Cerveza Artesanal Morena';
UPDATE `rest_platillos` SET codigo = 'B11', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Cerveza Artesanal Oscura';
UPDATE `rest_platillos` SET codigo = 'B12', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Cocktail de Mezcal con Tamarindo';
UPDATE `rest_platillos` SET codigo = 'B13', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Cocktail de Mezcal con Jamaica';
UPDATE `rest_platillos` SET codigo = 'B14', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Cocktail de Mezcal Margarita';
UPDATE `rest_platillos` SET codigo = 'B15', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Cocktail de Tequila con Tamarindo';
UPDATE `rest_platillos` SET codigo = 'B16', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Coctel de Tequila con Jamaica';
UPDATE `rest_platillos` SET codigo = 'B17', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Cocktail de Tequila Margarita';
UPDATE `rest_platillos` SET codigo = 'B18', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Café de Olla';
UPDATE `rest_platillos` SET codigo = 'B19', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Carajillo sin Cafeína Amanecer';
UPDATE `rest_platillos` SET codigo = 'B20', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Carajillo sin Cafeína Anochecer';
UPDATE `rest_platillos` SET codigo = 'B21', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Agua de Horchata';
UPDATE `rest_platillos` SET codigo = 'B22', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Agua de Jamaica';
UPDATE `rest_platillos` SET codigo = 'B23', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Agua de Tamarindo';
UPDATE `rest_platillos` SET codigo = 'B24', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Chocolate con Agua';
UPDATE `rest_platillos` SET codigo = 'B25', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Chocolate con Leche';
UPDATE `rest_platillos` SET codigo = 'B26', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Atole de Fresa';
UPDATE `rest_platillos` SET codigo = 'B27', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Atole de Vainilla';
UPDATE `rest_platillos` SET codigo = 'B28', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Agua Mineral';
UPDATE `rest_platillos` SET codigo = 'B29', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Agua Sola';
UPDATE `rest_platillos` SET codigo = 'B30', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Percherón Mezcal Sol 65 Ml.';
UPDATE `rest_platillos` SET codigo = 'B31', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Percherón Mezcal Luna 65 Ml.';
UPDATE `rest_platillos` SET codigo = 'B32', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Percherón Mezcal Orgullo 65 Ml.';
UPDATE `rest_platillos` SET codigo = 'B33', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Percherón Mezcal Amor 65 Ml.';
UPDATE `rest_platillos` SET codigo = 'B34', es_armado = 1
  WHERE restaurante_id = @rest_id AND nombre = 'Percherón Mezcal Noche 65 Ml.';

-- ── 3. Insertar Materia Prima MP-DP1..MP-DP18 y MP-B1..MP-B34 ─────────────
-- INSERT IGNORE: seguro de re-ejecutar.

INSERT IGNORE INTO `rest_ingredientes`
  (restaurante_id, codigo, tipo, nombre, unidad_principal, costo_unitario, stock, stock_minimo, activo)
VALUES
  -- Postres
  (@rest_id, 'MP-DP1',  'materia_prima', 'Ate de Guayaba con Queso 6 Pzs.',       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP2',  'materia_prima', 'Ate de Membrillo con Queso 6 Pzs.',     'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP3',  'materia_prima', 'Glorias de Leche Quemada 3 Pzs.',       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP4',  'materia_prima', 'Obleas de Cajeta 3 Pzs.',               'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP5',  'materia_prima', 'Camote de Puebla 3 Pzs.',               'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP6',  'materia_prima', 'Borrachitos de Fresa 5 Pzs.',           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP7',  'materia_prima', 'Fruta Cristalizada Higo 150 Gr.',       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP8',  'materia_prima', 'Fruta Cristalizada Pera 150 Gr.',       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP9',  'materia_prima', 'Fruta Cristalizada Calabazete 150 Gr.', 'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP10', 'materia_prima', 'Orejonas 150 Gr.',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP11', 'materia_prima', 'Cocadas 3 Pzs.',                        'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP12', 'materia_prima', 'Palanquetas 1 Pza.',                    'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP13', 'materia_prima', 'Merengues 3 Pzs.',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP14', 'materia_prima', 'Natillas Qro. 150 Gr.',                 'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP15', 'materia_prima', 'Natillas Bernal 4 Pzs.',                'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP16', 'materia_prima', 'Ollitas de Tamarindo 3 Pzs.',           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP17', 'materia_prima', 'Tamal de Maíz Dulce de Piña 2 Pzs.',    'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-DP18', 'materia_prima', 'Tamal de Dulce de Fresa 2 Pzs.',        'porcion', 0.00, 999.000, 0.000, 1),
  -- Bebidas
  (@rest_id, 'MP-B1',  'bebida', 'Mezcal Sol 2 Oz.',                       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B2',  'bebida', 'Mezcal Luna 2 Oz.',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B3',  'bebida', 'Mezcal Orgullo 2 Oz.',                   'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B4',  'bebida', 'Mezcal Noche 2 Oz.',                     'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B5',  'bebida', 'Mezcal Amor 2 Oz.',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B6',  'bebida', 'Tequila Blanco 2 Oz.',                   'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B7',  'bebida', 'Tequila Reposado 2 Oz.',                 'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B8',  'bebida', 'Tequila Añejo 2 Oz.',                    'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B9',  'bebida', 'Cerveza Artesanal Clara',                'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B10', 'bebida', 'Cerveza Artesanal Morena',               'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B11', 'bebida', 'Cerveza Artesanal Oscura',               'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B12', 'bebida', 'Cocktail de Mezcal con Tamarindo',       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B13', 'bebida', 'Cocktail de Mezcal con Jamaica',         'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B14', 'bebida', 'Cocktail de Mezcal Margarita',           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B15', 'bebida', 'Cocktail de Tequila con Tamarindo',      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B16', 'bebida', 'Coctel de Tequila con Jamaica',          'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B17', 'bebida', 'Cocktail de Tequila Margarita',          'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B18', 'bebida', 'Café de Olla',                           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B19', 'bebida', 'Carajillo sin Cafeína Amanecer',         'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B20', 'bebida', 'Carajillo sin Cafeína Anochecer',        'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B21', 'bebida', 'Agua de Horchata',                       'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B22', 'bebida', 'Agua de Jamaica',                        'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B23', 'bebida', 'Agua de Tamarindo',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B24', 'bebida', 'Chocolate con Agua',                     'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B25', 'bebida', 'Chocolate con Leche',                    'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B26', 'bebida', 'Atole de Fresa',                         'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B27', 'bebida', 'Atole de Vainilla',                      'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B28', 'bebida', 'Agua Mineral',                           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B29', 'bebida', 'Agua Sola',                              'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B30', 'bebida', 'Percherón Mezcal Sol 65 Ml.',            'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B31', 'bebida', 'Percherón Mezcal Luna 65 Ml.',           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B32', 'bebida', 'Percherón Mezcal Orgullo 65 Ml.',        'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B33', 'bebida', 'Percherón Mezcal Amor 65 Ml.',           'porcion', 0.00, 999.000, 0.000, 1),
  (@rest_id, 'MP-B34', 'bebida', 'Percherón Mezcal Noche 65 Ml.',          'porcion', 0.00, 999.000, 0.000, 1);

-- ── 4. Asegurar fila en rest_recetas para cada platillo DP* / B* ──────────

INSERT IGNORE INTO rest_recetas (platillo_id, porciones_base, notas)
SELECT p.id, 1,
  CONCAT('Servir ', p.nombre, '. Descuenta 1 porción de MP-', p.codigo, '.')
FROM rest_platillos p
WHERE p.restaurante_id = @rest_id
  AND (p.codigo REGEXP '^DP[0-9]+$' OR p.codigo REGEXP '^B[0-9]+$');

-- ── 5. Vincular MP (componente principal) a cada receta DP* / B* ──────────
-- es_informativo=0: descuenta stock al cambiar pedido a 'entregado'.
-- INSERT IGNORE: seguro de re-ejecutar.

INSERT IGNORE INTO rest_receta_ingredientes
  (receta_id, ingrediente_id, cantidad, unidad, es_informativo, precio_extra)
SELECT r.id,
       mp.id,
       1,
       'porcion',
       0,
       0.00
FROM rest_recetas r
JOIN rest_platillos p ON p.id = r.platillo_id
JOIN rest_ingredientes mp
       ON mp.restaurante_id = p.restaurante_id
      AND mp.codigo = CONCAT('MP-', p.codigo)
WHERE p.restaurante_id = @rest_id
  AND (p.codigo REGEXP '^DP[0-9]+$' OR p.codigo REGEXP '^B[0-9]+$');

-- ── Fin ─────────────────────────────────────────────────────────────────────
