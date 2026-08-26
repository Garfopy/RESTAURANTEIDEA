-- Datos de prueba para UTEQ Cafetería (restaurante_id = 1).
-- Seguro correr una sola vez sobre una base de prueba. NO correr en producción con datos reales.
-- Crea: 3 categorías, 6 ingredientes (uno con stock bajo a propósito), 6 platillos,
-- y 3 pedidos en distintos estados (para ver algo en Dashboard, Cocina e Inventario).

-- ── Categorías ───────────────────────────────────────────────────────────────
INSERT INTO rest_categorias_menu (restaurante_id, nombre, descripcion, orden, activo) VALUES
  (1, 'Comida', 'Platillos principales', 1, 1),
  (1, 'Bebidas', 'Café, té y refrescos', 2, 1),
  (1, 'Postres', 'Dulces y repostería', 3, 1);

SET @cat_comida  = (SELECT id FROM rest_categorias_menu WHERE restaurante_id=1 AND nombre='Comida'  LIMIT 1);
SET @cat_bebidas = (SELECT id FROM rest_categorias_menu WHERE restaurante_id=1 AND nombre='Bebidas' LIMIT 1);
SET @cat_postres = (SELECT id FROM rest_categorias_menu WHERE restaurante_id=1 AND nombre='Postres' LIMIT 1);

-- ── Ingredientes (uno queda con stock bajo a propósito: café molido) ──────────
INSERT INTO rest_ingredientes (codigo, tipo, restaurante_id, nombre, unidad_principal, costo_unitario, stock, stock_minimo, categoria, activo) VALUES
  ('ING-001', 'otro', 1, 'Café molido',    'kg',  180.00,  1.500, 3.000, 'Insumos', 1), -- stock bajo a propósito
  ('ING-002', 'otro', 1, 'Leche entera',   'lt',   22.00, 15.000, 5.000, 'Insumos', 1),
  ('ING-003', 'otro', 1, 'Pan para sandwich','pza', 4.50, 40.000,10.000, 'Insumos', 1),
  ('ING-004', 'otro', 1, 'Jamón',          'kg',  120.00,  3.000, 1.000, 'Insumos', 1),
  ('ING-005', 'otro', 1, 'Queso amarillo', 'kg',  140.00,  2.500, 1.000, 'Insumos', 1),
  ('ING-006', 'otro', 1, 'Harina para hot cakes','kg', 28.00, 8.000, 2.000, 'Insumos', 1);

-- ── Platillos ──────────────────────────────────────────────────────────────
INSERT INTO rest_platillos (codigo, restaurante_id, categoria_id, nombre, descripcion, precio, tiempo_preparacion_min, disponible, activo) VALUES
  ('C-001', 1, @cat_comida,  'Sandwich de jamón y queso', 'Pan blanco, jamón y queso amarillo', 45.00, 8,  1, 1),
  ('C-002', 1, @cat_comida,  'Hot cakes (3 pzas)',        'Con miel y mantequilla',              55.00, 10, 1, 1),
  ('B-001', 1, @cat_bebidas, 'Café americano',             'Café de grano recién molido',          28.00, 3,  1, 1),
  ('B-002', 1, @cat_bebidas, 'Café con leche',              'Espresso con leche vaporizada',        35.00, 4,  1, 1),
  ('P-001', 1, @cat_postres, 'Concha',                      'Pan dulce tradicional',                18.00, 1,  1, 1),
  ('P-002', 1, @cat_postres, 'Brownie',                     'Con chispas de chocolate',              32.00, 1,  1, 1);

SET @plat_sandwich   = (SELECT id FROM rest_platillos WHERE restaurante_id=1 AND codigo='C-001' LIMIT 1);
SET @plat_hotcakes   = (SELECT id FROM rest_platillos WHERE restaurante_id=1 AND codigo='C-002' LIMIT 1);
SET @plat_americano  = (SELECT id FROM rest_platillos WHERE restaurante_id=1 AND codigo='B-001' LIMIT 1);
SET @plat_conleche   = (SELECT id FROM rest_platillos WHERE restaurante_id=1 AND codigo='B-002' LIMIT 1);
SET @plat_concha     = (SELECT id FROM rest_platillos WHERE restaurante_id=1 AND codigo='P-001' LIMIT 1);
SET @plat_brownie    = (SELECT id FROM rest_platillos WHERE restaurante_id=1 AND codigo='P-002' LIMIT 1);

-- ── Pedido 1: pendiente (para ver en la cola de Cocina) ───────────────────────
INSERT INTO rest_pedidos (restaurante_id, folio, estado, subtotal, total, tipo_pedido, pedido_origen, cliente_nombre, comprador_telefono, created_at) VALUES
  (1, 'UTEQ-0001', 'pendiente', 80.00, 80.00, 'pickup', 'cliente', 'Ana López', '4421110001', NOW());
SET @ped1 = LAST_INSERT_ID();
INSERT INTO rest_pedido_items (pedido_id, platillo_id, cantidad, precio_unit, subtotal, estado) VALUES
  (@ped1, @plat_sandwich,  1, 45.00, 45.00, 'pendiente'),
  (@ped1, @plat_americano, 1, 28.00, 28.00, 'pendiente');

-- ── Pedido 2: en preparación (para ver el flujo de avance en Cocina) ─────────
INSERT INTO rest_pedidos (restaurante_id, folio, estado, subtotal, total, tipo_pedido, pedido_origen, cliente_nombre, comprador_telefono, created_at) VALUES
  (1, 'UTEQ-0002', 'en_preparacion', 90.00, 90.00, 'delivery', 'cliente', 'Carlos Ruiz', '4421110002', DATE_SUB(NOW(), INTERVAL 12 MINUTE));
SET @ped2 = LAST_INSERT_ID();
INSERT INTO rest_pedido_items (pedido_id, platillo_id, cantidad, precio_unit, subtotal, estado) VALUES
  (@ped2, @plat_hotcakes, 1, 55.00, 55.00, 'en_preparacion'),
  (@ped2, @plat_conleche, 1, 35.00, 35.00, 'listo');

-- ── Pedido 3: entregado hoy (para ver KPIs de ventas en el Dashboard) ────────
INSERT INTO rest_pedidos (restaurante_id, folio, estado, subtotal, total, tipo_pedido, pedido_origen, cliente_nombre, comprador_telefono, metodo_pago, pagado_at, created_at) VALUES
  (1, 'UTEQ-0003', 'entregado', 50.00, 50.00, 'pickup', 'cliente', 'María Torres', '4421110003', 'efectivo', NOW(), DATE_SUB(NOW(), INTERVAL 2 HOUR));
SET @ped3 = LAST_INSERT_ID();
INSERT INTO rest_pedido_items (pedido_id, platillo_id, cantidad, precio_unit, subtotal, estado) VALUES
  (@ped3, @plat_concha,  1, 18.00, 18.00, 'entregado'),
  (@ped3, @plat_brownie, 1, 32.00, 32.00, 'entregado');
