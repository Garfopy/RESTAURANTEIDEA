# Plan & Checklist — Módulo Restaurantes CarniHub v3.2

**Actualizado:** 2026-05-13 | **Branch:** `sprint-restaurantes`

---

## CHECKLIST

### ✅ BASE DE DATOS
- [x] `022_restaurantes_core.sql` — 15 tablas core
- [x] `023_restaurantes_finanzas.sql` — gastos, retiros, cortes
- [x] `024_restaurantes_reservaciones.sql`
- [x] `025_roles_restaurante.sql` — roles 7/8/9, columnas restaurante_id y restaurante_activo

### ✅ MODELOS
- [x] RestauranteModel, RestMesaModel, RestMenuModel, RestInventarioModel
- [x] RestPedidoModel, RestFinanzasModel, RestClienteModel, RestVisitaModel
- [x] RestReservaModel, RestTicketModel

### ✅ CONTROLLERS
- [x] RestauranteController, RestConfigController (+ qr), RestMesaController
- [x] RestMenuController (con recetas), RestInventarioController (CarniHub + ext)
- [x] RestPedidoController, RestFinanzasController, RestClienteController
- [x] RestReservaController, RestTicketController
- [x] RestChefController (KDS + AJAX), RestMeseroController, RestPorteroController
- [x] RestPublicoController (menú + ordenar + pagar + confirmación)
- [x] StaffAccesoController — login staff por slug
- [x] RestStaffController — crear/desactivar mesero/chef/portero ← NUEVO

### ✅ VISTAS — ADMIN RESTAURANTE
- [x] layouts/main.php — CSS separado, transiciones, responsive, Staff + QR en sidebar
- [x] dashboard.php — KPIs
- [x] mesas/index.php — modal animado, clases CSS, cerrar backdrop/X ← MEJORADO
- [x] inventario/index.php — tabs CarniHub/Externo, tipo movimiento visual ← MEJORADO
- [x] inventario/movimientos.php — historial
- [x] menu/index.php — catálogo de platillos
- [x] menu/form.php — editor de platillo + receta con ingredientes/gramajes
- [x] finanzas/dashboard.php, gastos.php, retiros.php, cortes.php
- [x] tickets/index.php, tickets/detalle.php
- [x] clientes/index.php, clientes/detalle.php, clientes/top.php
- [x] reservas/index.php
- [x] config/index.php — branding + Google Maps + QR generado
- [x] config/qr.php — QR descargable, instrucciones, URL staff ← NUEVO
- [x] staff/index.php — crear/listar staff con tarjetas por rol ← NUEVO

### ✅ PORTALES STAFF
- [x] chef/dashboard.php — KDS dark mode, AJAX 5s, Web Audio
- [x] mesero/dashboard.php
- [x] portero/dashboard.php — jsQR scanner
- [x] staff/login.php — login branded por restaurante

### ✅ MENÚ PÚBLICO
- [x] publico/menu/index.php — mobile-first, carrito, cookie visita 4h
- [x] publico/menu/confirmacion.php
- [x] publico/menu/pagar.php — selector propina 0/10/15/20%, método pago

### ✅ CSS & INFRAESTRUCTURA
- [x] public/css/restaurant.css — modal animado, tabs, badges, btns, responsive
- [x] index.php — rutas + auth guard exento menu/acceso
- [x] BaseController — requireRestaurante/Mesero/Chef/Portero, redirectSegunRol
- [x] Sidebar comprador — solo "Ver mis locales"
- [x] QR visita → cookie 4h por restaurante

---

## ⏳ PENDIENTE

### 🔴 BLOQUEANTE
- [ ] **Correr migrations 022–025** en tu DB (manual)
- [ ] `UPDATE usuarios SET restaurante_activo=1 WHERE email='tu@correo.com'`

### 🔴 Alta prioridad
- [ ] **Layouts staff** — chef/mesero/portero sin sidebar (usar restaurant.css)
- [ ] **PayPal en ticket público** — integrar PayPalPagoService en confirmarPago
- [ ] **Google Auth comensales** — OAuth Google en /menu/{slug}
- [ ] **Layout visual mesas** — drag & drop JS para posicionar mesas

### 🟡 Media prioridad
- [ ] **Mesas opcionales** — toggle `mesas_habilitadas` en config (take-away)
- [ ] **Multi-sucursal selector** — dropdown topbar cuando hay >1 local
- [ ] **Reservaciones form** — restaurante/reservas/form.php
- [ ] **Entrada automática inventario** — al recibir pedido CarniHub confirmado
- [ ] **Reportes CSV/PDF** — ventas, inventario, comensales
- [ ] **Print QR mesa** — PDF por mesa individual

### 🟢 Sprint 3
- [ ] PWA (manifiest.json + service worker)
- [ ] Dark mode toggle
- [ ] Analíticas avanzadas (platillos populares, horarios pico)

---

## FLUJOS IMPLEMENTADOS

```
Cliente escanea QR → /menu/{slug}?mesa={qr}
  → Cookie visita_{restId} (4h)
  → Carrito → Ordenar → pedido PENDIENTE → confirmación

Chef KDS → /rest-chef/dashboard → AJAX 5s → marcar listo

Pago → /menu/{slug}/pagar/{visitaId}
  → Propina: 0/10/15/20% → Método → Confirmar

Portero → scanner QR → Verde=PAGADO | Rojo=PENDIENTE

Staff login → /acceso/{slug} → branded → redirect al portal

Admin crea staff:
  → rest-staff/index → modal → nombre/email/pass/rol
  → Sistema crea usuario + rest_staff con código autoincremental
```

---

## ACTIVAR EL MÓDULO (pasos manuales)

```sql
SOURCE migrations/022_restaurantes_core.sql;
SOURCE migrations/023_restaurantes_finanzas.sql;
SOURCE migrations/024_restaurantes_reservaciones.sql;
SOURCE migrations/025_roles_restaurante.sql;

UPDATE usuarios SET restaurante_activo = 1 WHERE email = 'tu@correo.com';
```
Luego: cerrar sesión → "Ver mis locales" → crear restaurante.
