# Plan & Checklist — Módulo Restaurantes CarniHub v3.2

**Actualizado:** 2026-05-13 | **Branch:** `sprint-restaurantes`

---

## CHECKLIST COMPLETO — ESTADO ACTUAL

### ✅ BASE DE DATOS (Migrations)
- [x] `022_restaurantes_core.sql` — 15 tablas core (rest_restaurantes, mesas, menú, pedidos, inventario, comensales, visitas, tickets, staff…)
- [x] `023_restaurantes_finanzas.sql` — gastos, retiros, cortes de caja
- [x] `024_restaurantes_reservaciones.sql` — reservaciones
- [x] `025_roles_restaurante.sql` — roles mesero/chef/portero (IDs 7/8/9), columnas `restaurante_id` y `restaurante_activo` en usuarios

### ✅ MODELOS
- [x] `RestauranteModel.php` — CRUD, getBySlug, getByComprador, generarSlugUnico
- [x] `RestMesaModel.php` — CRUD mesas, getByQr, estado
- [x] `RestMenuModel.php` — platillos, categorías, recetas con ingredientes
- [x] `RestInventarioModel.php` — stock, descontarPorOrden, movimientos
- [x] `RestPedidoModel.php` — crear pedido con transacción, getKitchenQueue, estados
- [x] `RestFinanzasModel.php` — kpisDashboard, gastos, retiros, cortes
- [x] `RestClienteModel.php` — comensales, top por consumo/visitas
- [x] `RestVisitaModel.php` — crear visita, actualizarTotales, estados
- [x] `RestReservaModel.php` — CRUD reservaciones
- [x] `RestTicketModel.php` — consolidar, getByVisita, confirmarPago

### ✅ CONTROLLERS
- [x] `RestauranteController.php` — dashboard, crear, editar, seleccionar, activar
- [x] `RestConfigController.php` — configuración branding, logo upload
- [x] `RestMesaController.php` — CRUD mesas, QR display
- [x] `RestMenuController.php` — CRUD platillos, categorías, recetas
- [x] `RestInventarioController.php` — stock, movimientos (entrada/salida/merma)
- [x] `RestPedidoController.php` — crear pedido, estados, nuevo por mesa
- [x] `RestFinanzasController.php` — dashboard financiero, gastos, retiros, cortes
- [x] `RestClienteController.php` — comensales, top consumo/visitas, detalle
- [x] `RestReservaController.php` — CRUD reservaciones
- [x] `RestTicketController.php` — listado, detalle, confirmarPago
- [x] `RestChefController.php` — KDS dashboard, queue AJAX, marcarPreparacion, marcarListo
- [x] `RestMeseroController.php` — dashboard mesero, marcarEntregado
- [x] `RestPorteroController.php` — scanner QR, registrarEntrada, registrarSalida
- [x] `RestPublicoController.php` — menú público, ordenar, confirmación, pagar
- [x] `StaffAccesoController.php` — login staff por slug de restaurante ← NUEVO

### ✅ VISTAS — PORTAL ADMIN RESTAURANTE
- [x] `restaurante/layouts/main.php` — sidebar con CSS separado, transiciones, responsive
- [x] `restaurante/dashboard.php` — KPIs: ingresos, mesas activas, pedidos, alertas inventario
- [x] `restaurante/seleccionar.php` — selección de local activo
- [x] `restaurante/form.php` — crear/editar restaurante
- [x] `restaurante/config/index.php` — configuración + Google Maps embed + QR generado ← MEJORADO
- [x] `restaurante/mesas/index.php` — lista de mesas con estado y QR
- [x] `restaurante/menu/index.php` — catálogo de platillos
- [x] `restaurante/menu/form.php` — crear/editar platillo
- [x] `restaurante/inventario/index.php` — stock con semáforos
- [x] `restaurante/inventario/movimientos.php` — historial de movimientos
- [x] `restaurante/pedidos/index.php` — todos los pedidos
- [x] `restaurante/pedidos/nuevo.php` — nuevo pedido por mesa
- [x] `restaurante/pedidos/detalle.php` — detalle pedido
- [x] `restaurante/finanzas/dashboard.php` — KPIs financieros + Chart.js
- [x] `restaurante/finanzas/gastos.php` — gestión de gastos
- [x] `restaurante/finanzas/retiros.php` — gestión de retiros
- [x] `restaurante/finanzas/cortes.php` — corte de caja
- [x] `restaurante/tickets/index.php` — gestión de tickets
- [x] `restaurante/tickets/detalle.php` — detalle ticket + confirmar pago
- [x] `restaurante/clientes/index.php` — comensales
- [x] `restaurante/clientes/detalle.php` — detalle comensal
- [x] `restaurante/clientes/top.php` — top por consumo / visitas
- [x] `restaurante/reservas/index.php` — reservaciones

### ✅ VISTAS — PORTALES STAFF
- [x] `chef/dashboard.php` — KDS dark mode, AJAX polling 5s, Web Audio alert, marcar estados
- [x] `mesero/dashboard.php` — mesas asignadas, órdenes activas, nueva orden
- [x] `portero/dashboard.php` — scanner QR (jsQR + cámara), verificación, rojo/verde
- [x] `staff/login.php` — portal de acceso staff branded por restaurante ← NUEVO

### ✅ VISTAS — MENÚ PÚBLICO (SIN LOGIN)
- [x] `publico/menu/index.php` — menú mobile-first, filtros categoría, carrito flotante, CSS separado ← MEJORADO
- [x] `publico/menu/confirmacion.php` — confirmación de pedido recibido
- [x] `publico/menu/pagar.php` — pantalla de pago con selector propina y método ← NUEVO (era faltante)

### ✅ CSS & ASSETS
- [x] `public/css/restaurant.css` — CSS completo separado: admin portal, menú público, KDS, portero, staff login, transiciones fluidas ← NUEVO
- [x] `public/css/carnihub.css` — CSS base del sistema (existente)
- [x] `public/js/chart.umd.min.js` — Chart.js local

### ✅ INFRAESTRUCTURA
- [x] `index.php` — 14 rutas restaurante + 1 ruta acceso staff
- [x] `index.php` — auth guard exento: `$ctrlSlug !== 'menu' && $ctrlSlug !== 'acceso'`
- [x] `BaseController.php` — requireRestaurante(), requireMesero(), requireChef(), requirePortero()
- [x] `BaseController.php` — redirectSegunRol() extendido con mesero/chef/portero
- [x] Sidebar comprador — "Mi Restaurante" → un solo link "Ver mis locales" ← SIMPLIFICADO
- [x] QR de menú — visita persistida en cookie 4h por restaurante ← MEJORADO

---

## ⏳ PENDIENTE — SPRINT 2

### 🔴 Alta prioridad (bloqueante)
- [ ] **CORRER MIGRATIONS 022–025 EN DB** — sin esto nada funciona (acción manual del usuario)
- [ ] **Activar `restaurante_activo = 1`** en la BD para el comprador de prueba
- [ ] **PayPal — confirmarPago** — `RestTicketController::confirmarPago` integrar `PayPalPagoService`
- [ ] **Layout visual de mesas** — drag & drop (canvas/JS) para posicionar mesas visualmente
- [ ] **Editor de recetas** — `restaurante/menu/receta.php` asignar ingredientes + gramajes a platillos
- [ ] **Gestión de staff** — vista para que el admin cree cuentas mesero/chef/portero con código

### 🔴 Layouts staff faltantes
- [ ] `app/views/chef/layouts/main.php` — layout para portal chef (actualmente sin sidebar)
- [ ] `app/views/mesero/layouts/main.php` — layout para portal mesero
- [ ] `app/views/portero/layouts/main.php` — layout para portal portero

### 🟡 Media prioridad
- [ ] **Multi-sucursal selector** — dropdown en topbar para cambiar entre locales
- [ ] **Reservaciones form** — `restaurante/reservas/form.php` crear/editar reserva
- [ ] **Inventario form** — vista para registrar ingrediente nuevo
- [ ] **Reportes CSV/PDF** — exportar ventas, inventario, comensales
- [ ] **Firebase notifications** — nuevos pedidos en tiempo real (chef)
- [ ] **Print QR de mesa** — botón imprimir QR individual por mesa
- [ ] **Integración distribuidor → inventario** — entrada automática al recibir pedido

### 🟢 Baja prioridad / Sprint 3
- [ ] PWA (manifiest.json + service worker) para instalar como app
- [ ] Dark mode toggle en admin portal
- [ ] Analíticas avanzadas (platillos populares, horarios pico, ticket promedio)
- [ ] QR de mesa con nombre de mesa impreso (pdf imprimible)

---

## FLUJOS IMPLEMENTADOS

```
QR Entry del comensal
  → Escanea QR del restaurante o mesa
  → /menu/{slug}?mesa={qr_codigo}
  → Cookie visita_{restId} se lee (si existe, misma sesión)
  → Ve menú con branding del restaurante
  → Carrito flotante aparece al agregar items
  → Ordenar → pedido.estado = 'pendiente' → confirmación

Chef (KDS)
  → /rest-chef/dashboard → polling AJAX 5s
  → Nuevo pedido → Web Audio alert + animación fadeIn
  → Marca 'en_preparacion' → 'listo'

Mesero
  → Ve pedidos listos → marca 'entregado'
  → Genera ticket para la mesa

Pago público
  → /menu/{slug}/pagar/{visitaId}
  → Selector propina visual (0/10/15/20%)
  → Selector método (Efectivo/Tarjeta/Transferencia/PayPal)
  → Confirma → ticket.estado = 'pagado'

Portero
  → Escanea QR de visita (cámara jsQR o manual)
  → Verde = PAGADO ✅  |  Rojo = PENDIENTE ❌

Staff login
  → /acceso/{slug} — pantalla branded del restaurante
  → Solo usuarios con rol mesero/chef/portero del restaurante
  → Redirige al portal según rol
```

---

## PARA ACTIVAR EL MÓDULO (pasos manuales únicos)

```sql
-- 1. Ejecutar en MySQL/phpMyAdmin en orden:
SOURCE migrations/022_restaurantes_core.sql;
SOURCE migrations/023_restaurantes_finanzas.sql;
SOURCE migrations/024_restaurantes_reservaciones.sql;
SOURCE migrations/025_roles_restaurante.sql;

-- 2. Activar para tu comprador:
UPDATE usuarios SET restaurante_activo = 1 WHERE email = 'tu@correo.com';

-- 3. Cerrar sesión y volver a entrar
-- 4. Click en "Ver mis locales" en el sidebar
-- 5. Crear primer restaurante
-- 6. Navegar a /rest-config/index para configurar branding y ver QR
```
