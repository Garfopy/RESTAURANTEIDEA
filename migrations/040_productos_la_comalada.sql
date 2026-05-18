-- ============================================================
-- MIGRACIÓN 040 — Productos La Comalada para empresa_id = 1
-- Categorías: Guarniciones, Postres, Bebidas, Platillos
-- Todos los productos se manejan por paquetes (presentacion = pieza)
-- precio_base = 0.00 (configurar desde el panel de empresa)
--
-- IDEMPOTENTE: seguro de ejecutar más de una vez.
--   - INSERT IGNORE en categorias (slug único)
--   - INSERT...SELECT...WHERE NOT EXISTS en productos (por nombre+descripcion+empresa)
--   - INSERT...SELECT...WHERE NOT EXISTS en inventario (ya existente)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Nuevas categorías ─────────────────────────────────────────
INSERT IGNORE INTO `categorias` (`nombre`, `slug`) VALUES
  ('Guarniciones', 'guarniciones'),
  ('Postres',      'postres'),
  ('Bebidas',      'bebidas'),
  ('Platillos',    'platillos');

-- ── Productos: Guarniciones (G1–G21) ────────────────────────
INSERT INTO `productos` (`empresa_id`, `categoria_id`, `nombre`, `descripcion`, `presentacion`, `precio_base`, `activo`)
SELECT 1, c.id, t.nombre, t.descripcion, 'pieza', 0.00, 1
FROM (SELECT id FROM categorias WHERE slug = 'guarniciones') AS c
CROSS JOIN (
  SELECT 'Tortillas de Maíz'        AS nombre, 'G1 — 4 piezas'  AS descripcion UNION ALL
  SELECT 'Salsa Verde',                         'G2 — 50 ml'                    UNION ALL
  SELECT 'Salsa Roja',                          'G3 — 50 ml'                    UNION ALL
  SELECT 'Crema',                               'G4 — 50 ml'                    UNION ALL
  SELECT 'Queso Rayado',                        'G5 — 50 gr'                    UNION ALL
  SELECT 'Limones',                             'G6 — 2 piezas'                 UNION ALL
  SELECT 'Frijoles Refritos',                   'G7 — 150 gr'                   UNION ALL
  SELECT 'Sal de Mar',                          'G8'                            UNION ALL
  SELECT 'Guacamole',                           'G9 — 200 gr'                   UNION ALL
  SELECT 'Rajas',                               'G10 — 50 gr'                   UNION ALL
  SELECT 'Salsa Chipotle',                      'G11 — 50 gr'                   UNION ALL
  SELECT 'Chorizo',                             'G12 — 150 gr'                  UNION ALL
  SELECT 'Arroz Blanco',                        'G13 — 200 gr'                  UNION ALL
  SELECT 'Arroz Rojo',                          'G14 — 200 gr'                  UNION ALL
  SELECT 'Azúcar',                              'G15 — 5 gr'                    UNION ALL
  SELECT 'Orégano',                             'G16 — 5 gr'                    UNION ALL
  SELECT 'Cebolla Picada',                      'G17 — 25 gr'                   UNION ALL
  SELECT 'Cilantro Picado',                     'G18 — 5 gr'                    UNION ALL
  SELECT 'Pollo Deshebrado',                    'G19 — 250 gr'                  UNION ALL
  SELECT 'Carne Picada',                        'G20 — 250 gr'                  UNION ALL
  SELECT 'Cebolla Morada Desflmada',            'G21 — 25 gr'
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` p WHERE p.empresa_id = 1 AND p.nombre = t.nombre AND p.descripcion = t.descripcion
);

-- ── Productos: Postres y Dulces (DP1–DP18) ─────────────────
INSERT INTO `productos` (`empresa_id`, `categoria_id`, `nombre`, `descripcion`, `presentacion`, `precio_base`, `activo`)
SELECT 1, c.id, t.nombre, t.descripcion, 'pieza', 0.00, 1
FROM (SELECT id FROM categorias WHERE slug = 'postres') AS c
CROSS JOIN (
  SELECT 'Ate de Guayaba con Queso'      AS nombre, 'DP1 — 6 piezas'  AS descripcion UNION ALL
  SELECT 'Ate de Membrillo con Queso',              'DP2 — 6 piezas'                 UNION ALL
  SELECT 'Glorias de Leche Quemada',                'DP3 — 3 piezas'                 UNION ALL
  SELECT 'Obleas de Cajeta',                        'DP4 — 3 piezas'                 UNION ALL
  SELECT 'Camote de Puebla',                        'DP5 — 3 piezas'                 UNION ALL
  SELECT 'Borrachitos de Fresa',                    'DP6 — 5 piezas'                 UNION ALL
  SELECT 'Fruta Cristalizada Higo',                 'DP7 — 150 gr'                   UNION ALL
  SELECT 'Fruta Cristalizada Pera',                 'DP8 — 150 gr'                   UNION ALL
  SELECT 'Fruta Cristalizada Calabazete',           'DP9 — 150 gr'                   UNION ALL
  SELECT 'Orejones',                                'DP10 — 150 gr'                  UNION ALL
  SELECT 'Cocadas',                                 'DP11 — 3 piezas'                UNION ALL
  SELECT 'Palanquetas',                             'DP12 — 1 pieza'                 UNION ALL
  SELECT 'Merengues',                               'DP13 — 3 piezas'                UNION ALL
  SELECT 'Natillas Oro',                            'DP14 — 150 gr'                  UNION ALL
  SELECT 'Natillas Bernal',                         'DP15 — 4 piezas'                UNION ALL
  SELECT 'Olitas de Tamarindo',                     'DP16 — 3 piezas'                UNION ALL
  SELECT 'Tamal de Maíz Dulce de Piña',             'DP17 — 2 piezas'                UNION ALL
  SELECT 'Tamal de Dulce de Fresa',                 'DP18 — 2 piezas'
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` p WHERE p.empresa_id = 1 AND p.nombre = t.nombre AND p.descripcion = t.descripcion
);

-- ── Productos: Bebidas (B1–B34) ─────────────────────────────
INSERT INTO `productos` (`empresa_id`, `categoria_id`, `nombre`, `descripcion`, `presentacion`, `precio_base`, `activo`)
SELECT 1, c.id, t.nombre, t.descripcion, 'pieza', 0.00, 1
FROM (SELECT id FROM categorias WHERE slug = 'bebidas') AS c
CROSS JOIN (
  SELECT 'Mezcal Sol'                        AS nombre, 'B1 — 2 oz.'   AS descripcion UNION ALL
  SELECT 'Mezcal Luna',                                  'B2 — 2 oz.'                 UNION ALL
  SELECT 'Mezcal Orgullo',                               'B3 — 2 oz.'                 UNION ALL
  SELECT 'Mezcal Noche',                                 'B4 — 2 oz.'                 UNION ALL
  SELECT 'Mezcal Amor',                                  'B5 — 2 oz.'                 UNION ALL
  SELECT 'Tequila Blanco',                               'B6 — 2 oz.'                 UNION ALL
  SELECT 'Tequila Reposado',                             'B7 — 2 oz.'                 UNION ALL
  SELECT 'Tequila Añejo',                                'B8 — 2 oz.'                 UNION ALL
  SELECT 'Cerveza Artesanal Clara',                      'B9'                          UNION ALL
  SELECT 'Cerveza Artesanal Morena',                     'B10'                         UNION ALL
  SELECT 'Cerveza Artesanal Oscura',                     'B11'                         UNION ALL
  SELECT 'Cocktail de Mezcal con Tamarindo',             'B12'                         UNION ALL
  SELECT 'Cocktail de Mezcal con Jamaica',               'B13'                         UNION ALL
  SELECT 'Cocktail de Mezcal Margarita',                 'B14'                         UNION ALL
  SELECT 'Cocktail de Tequila con Tamarindo',            'B15'                         UNION ALL
  SELECT 'Coctel de Tequila con Jamaica',                'B16'                         UNION ALL
  SELECT 'Cocktail de Tequila Margarita',                'B17'                         UNION ALL
  SELECT 'Café de Olla',                                 'B18'                         UNION ALL
  SELECT 'Carajillo Sin Cafeína Amanecer',               'B19'                         UNION ALL
  SELECT 'Carajillo Sin Cafeína Anochecer',              'B20'                         UNION ALL
  SELECT 'Agua de Horchata',                             'B21'                         UNION ALL
  SELECT 'Agua de Jamaica',                              'B22'                         UNION ALL
  SELECT 'Agua de Tamarindo',                            'B23'                         UNION ALL
  SELECT 'Chocolate con Agua',                           'B24'                         UNION ALL
  SELECT 'Chocolate con Leche',                          'B25'                         UNION ALL
  SELECT 'Atole de Fresa',                               'B26'                         UNION ALL
  SELECT 'Atole de Vainilla',                            'B27'                         UNION ALL
  SELECT 'Agua Mineral',                                 'B28'                         UNION ALL
  SELECT 'Agua Sola',                                    'B29'                         UNION ALL
  SELECT 'Percheron Mezcal Sol',                         'B30 — 65 ml'                 UNION ALL
  SELECT 'Percheron Mezcal Luna',                        'B31 — 65 ml'                 UNION ALL
  SELECT 'Percheron Mezcal Orgullo',                     'B32 — 65 ml'                 UNION ALL
  SELECT 'Percheron Mezcal Amor',                        'B33 — 65 ml'                 UNION ALL
  SELECT 'Percheron Mezcal Noche',                       'B34 — 65 ml'
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` p WHERE p.empresa_id = 1 AND p.nombre = t.nombre AND p.descripcion = t.descripcion
);

-- ── Productos: Platillos (MP1–MP35) ─────────────────────────
INSERT INTO `productos` (`empresa_id`, `categoria_id`, `nombre`, `descripcion`, `presentacion`, `precio_base`, `activo`)
SELECT 1, c.id, t.nombre, t.descripcion, 'pieza', 0.00, 1
FROM (SELECT id FROM categorias WHERE slug = 'platillos') AS c
CROSS JOIN (
  SELECT 'Enchiladas Potosinas'              AS nombre, 'MP1 — 3 piezas'  AS descripcion UNION ALL
  SELECT 'Carnitas',                                     'MP2 — 150 gr'                   UNION ALL
  SELECT 'Barbacoa',                                     'MP3 — 150 gr'                   UNION ALL
  SELECT 'Tamal de Maíz Rojo de Pollo',                  'MP4 — 2 piezas'                 UNION ALL
  SELECT 'Tamal de Maíz Rojo de Carne',                  'MP5 — 2 piezas'                 UNION ALL
  SELECT 'Tamal de Maíz Verde de Pollo',                 'MP6 — 2 piezas'                 UNION ALL
  SELECT 'Tamal de Maíz Verde de Carne',                 'MP7 — 2 piezas'                 UNION ALL
  SELECT 'Tamal Oaxaqueño',                              'MP8 — 1 pieza'                  UNION ALL
  SELECT 'Mole Poblano con Carne de Puerco',             'MP9 — 250 gr'                   UNION ALL
  SELECT 'Mole Poblano con Carne de Pollo',              'MP10 — 250 gr'                  UNION ALL
  SELECT 'Mole Verde con Pollo',                         'MP11 — 250 gr'                  UNION ALL
  SELECT 'Mole Verde con Carne',                         'MP12 — 250 gr'                  UNION ALL
  SELECT 'Mole Oaxaqueño Rojo con Carne',                'MP13 — 250 gr'                  UNION ALL
  SELECT 'Mole Oaxaqueño Rojo con Pollo',                'MP14 — 250 gr'                  UNION ALL
  SELECT 'Mole Oaxaqueño Negro con Carne',               'MP15 — 250 gr'                  UNION ALL
  SELECT 'Mole Oaxaqueño Negro con Pollo',               'MP16 — 250 gr'                  UNION ALL
  SELECT 'Mole Oaxaqueño Amarillo con Carne',            'MP17 — 250 gr'                  UNION ALL
  SELECT 'Mole Oaxaqueño Amarillo con Pollo',            'MP18 — 250 gr'                  UNION ALL
  SELECT 'Sope de Chorizo',                              'MP19 — 3 piezas'                UNION ALL
  SELECT 'Sope de Pollo',                                'MP20 — 3 piezas'                UNION ALL
  SELECT 'Huarache de Pollo',                            'MP21 — 1 pieza'                 UNION ALL
  SELECT 'Huarache de Carne',                            'MP22 — 1 pieza'                 UNION ALL
  SELECT 'Chilorio',                                     'MP23 — 250 gr'                  UNION ALL
  SELECT 'Tacos de Canasta Deshebrada',                  'MP24 — 3 piezas'                UNION ALL
  SELECT 'Tacos de Canasta de Papa',                     'MP25 — 3 piezas'                UNION ALL
  SELECT 'Tacos de Canasta de Chicharrón',               'MP26 — 3 piezas'                UNION ALL
  SELECT 'Tacos de Canasta de Frijol',                   'MP27 — 3 piezas'                UNION ALL
  SELECT 'Chile Relleno de Queso',                       'MP28 — 1 pieza'                 UNION ALL
  SELECT 'Chile Relleno de Carne',                       'MP29 — 1 pieza'                 UNION ALL
  SELECT 'Asadura',                                      'MP30 — 250 gr'                  UNION ALL
  SELECT 'Cochinita Pibil',                              'MP31 — 250 gr'                  UNION ALL
  SELECT 'Gorditas de Queso',                            'MP32 — 3 piezas'                UNION ALL
  SELECT 'Gorditas de Migajas',                          'MP33 — 3 piezas'                UNION ALL
  SELECT 'Pozole Blanco',                                'MP34 — 300 gr'                  UNION ALL
  SELECT 'Pozole Rojo',                                  'MP35 — 300 gr'
) AS t
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` p WHERE p.empresa_id = 1 AND p.nombre = t.nombre AND p.descripcion = t.descripcion
);

-- ── Inventario: registrar stock inicial (0) para cada producto nuevo ──
INSERT INTO `inventario` (`producto_id`, `stock`, `umbral_minimo`)
SELECT p.id, 0, 0
FROM `productos` p
WHERE p.empresa_id = 1
  AND p.activo = 1
  AND NOT EXISTS (
    SELECT 1 FROM `inventario` i WHERE i.producto_id = p.id
  );

SET FOREIGN_KEY_CHECKS = 1;
