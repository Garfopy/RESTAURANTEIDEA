-- 029_test_ingredientes_la_comalada.sql
-- Ingredientes de prueba para restaurante_id=1 (LA COMALADA)
-- Úsalo SOLO en ambientes de desarrollo/staging.

INSERT IGNORE INTO `rest_ingredientes`
  (`restaurante_id`, `nombre`, `unidad_principal`, `costo_unitario`,
   `stock`, `stock_minimo`, `categoria`, `proveedor_carnihub`, `proveedor_nombre`, `activo`)
VALUES
  (1, 'Tortilla de maíz',    'pza',  0.50, 500.000, 50.000, 'Masa y tortillas',  0, 'Tortillería local',   1),
  (1, 'Carne de res molida', 'kg',  130.00,  15.000,  2.000, 'Carnes',            0, 'CarniHub / distribuidor', 1),
  (1, 'Carne de cerdo',      'kg',  110.00,  10.000,  2.000, 'Carnes',            0, 'CarniHub / distribuidor', 1),
  (1, 'Pollo (pechuga)',     'kg',   90.00,   8.000,  2.000, 'Carnes',            0, 'CarniHub / distribuidor', 1),
  (1, 'Jitomate',            'kg',   28.00,   5.000,  1.000, 'Verduras',          0, 'Mercado local',       1),
  (1, 'Cebolla blanca',      'kg',   18.00,   4.000,  0.500, 'Verduras',          0, 'Mercado local',       1),
  (1, 'Chile serrano',       'kg',   35.00,   1.500,  0.200, 'Especias y chiles', 0, 'Mercado local',       1),
  (1, 'Cilantro',            'kg',   22.00,   0.500,  0.100, 'Verduras',          0, 'Mercado local',       1),
  (1, 'Aguacate',            'pza',  12.00,  20.000,  5.000, 'Frutas',            0, 'Mercado local',       1),
  (1, 'Limón',               'pza',   2.00, 100.000, 20.000, 'Frutas',            0, 'Mercado local',       1),
  (1, 'Aceite vegetal',      'L',    35.00,   5.000,  1.000, 'Abarrotes',         0, 'Supermercado',        1),
  (1, 'Sal',                 'kg',    8.00,   2.000,  0.500, 'Abarrotes',         0, 'Supermercado',        1),
  (1, 'Frijoles',            'kg',   45.00,   5.000,  1.000, 'Abarrotes',         0, 'Supermercado',        1),
  (1, 'Arroz',               'kg',   30.00,   5.000,  1.000, 'Abarrotes',         0, 'Supermercado',        1),
  (1, 'Queso Oaxaca',        'kg',  160.00,   3.000,  0.500, 'Lácteos',           0, 'Cremería local',      1),
  (1, 'Crema ácida',         'L',    75.00,   2.000,  0.500, 'Lácteos',           0, 'Cremería local',      1),
  (1, 'Refresco 355ml',      'pza',   9.00,  48.000, 12.000, 'Bebidas',           0, 'Distribuidora',       1),
  (1, 'Agua embotellada',    'pza',   5.00,  24.000,  6.000, 'Bebidas',           0, 'Distribuidora',       1);

-- Movimientos de entrada para que el stock quede registrado
INSERT IGNORE INTO `rest_movimientos_inventario`
  (`restaurante_id`, `ingrediente_id`, `tipo`, `cantidad`, `stock_antes`, `stock_despues`, `motivo`, `usuario_id`)
SELECT
  i.restaurante_id, i.id, 'entrada', i.stock, 0, i.stock,
  'Stock inicial — carga de prueba', 13
FROM `rest_ingredientes` i
WHERE i.restaurante_id = 1 AND i.nombre IN (
  'Tortilla de maíz','Carne de res molida','Carne de cerdo','Pollo (pechuga)',
  'Jitomate','Cebolla blanca','Chile serrano','Cilantro','Aguacate','Limón',
  'Aceite vegetal','Sal','Frijoles','Arroz','Queso Oaxaca','Crema ácida',
  'Refresco 355ml','Agua embotellada'
);
