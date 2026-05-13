# Plan & Checklist — Módulo Restaurantes CarniHub v3.4

**Actualizado:** 2026-05-13 | **Branch:** `sprint-restaurantes`

> Manual operativo + checklist + historial del módulo. Cualquier funcionalidad nueva se diseña primero aquí.

---

## ÍNDICE

1. [Actores del sistema](#1-actores-del-sistema)
2. [Modos de operación por sucursal](#2-modos-de-operación-por-sucursal)
3. [Flujos completos (HIPER detallado)](#3-flujos-completos-hiper-detallado)
4. [Casos extra y desviaciones](#4-casos-extra-y-desviaciones)
5. [Cómo funciona cada sección](#5-cómo-funciona-cada-sección)
6. [Estado actual: ✅/⏳/❌](#6-estado-actual)
7. [Por dónde empezar a corregir](#7-por-dónde-empezar-a-corregir)
8. [Historial de cambios](#8-historial-de-cambios)

---

## 1. Actores del sistema

| Actor | Rol DB | Portal | Cómo entra |
|-------|--------|--------|-----------|
| **Admin Restaurante** | `comprador` con `restaurante_activo=1` | `/restaurante/*` `/rest-*/` | Login normal CarniHub → "Ver mis locales" |
| **Mesero** | `mesero` (rol id 7) | `/rest-mesero/*` | Login en portal staff branded `/acceso/{slug}` |
| **Chef** | `chef` (rol id 8) | `/rest-chef/*` | Login en portal staff branded `/acceso/{slug}` |
| **Portero** | `portero` (rol id 9) | `/rest-portero/*` | Login en portal staff branded `/acceso/{slug}` |
| **Comensal** | NO USUARIO | `/menu/{slug}` | Escanea QR físico — sin login |

**Regla de oro**: el comprador (admin) NO se mete en el flujo operativo diario; sólo configura, ve KPIs y crea staff. Mesero/Chef/Portero mueven mesas/platillos día a día.

---

## 2. Modos de operación por sucursal

Cada sucursal puede activar/desactivar módulos según su tipo de negocio:

| Toggle (`rest_restaurantes`) | Para qué tipo de negocio |
|------------------------------|--------------------------|
| `mesas_habilitadas` (1/0) | 0 = taquería de banqueta o take-away. Sólo menú QR del local, pago al instante, sin asignar mesa |
| `reservas_habilitadas` (1/0) | 1 = restaurante con reservas. 0 = lugar de paso |
| `portero_habilitado` (1/0) | 1 = checador verifica pago al salir. 0 = self-service, paga y se va |
| `propinas_sugeridas` (csv) | Default `0,10,15,20`. Editable por admin |
| `requiere_login_comensal` (1/0) | 1 = pide Google/nombre+tel antes de ordenar. 0 = ordenar libre |

> 📌 **Pendiente**: agregar campos en `migrations/026_rest_modos.sql` y exponer toggles en `restaurante/config/index.php`.

---

## 3. Flujos completos (HIPER detallado)

### 3.1 Crear restaurante (admin)

```
Comprador con plan Empresa o restaurante_activo=1
  ├─ Login normal CarniHub
  ├─ Sidebar comprador → "Ver mis locales" → /restaurante/index
  ├─ Si NO tiene restaurante → vista "Crear primer local"
  │     └─ Form: nombre, dirección, tipo de negocio (selector pre-marca toggles §2)
  ├─ Si SÍ tiene → lista de sucursales con selector
  └─ Selecciona sucursal → $_SESSION['restaurante_activo_id'] = X
        └─ Redirect a /restaurante/dashboard
```

### 3.2 Crear staff (admin)

```
Admin → /rest-staff/index → Click "+ Nuevo staff"
  ├─ Modal: nombre, email, password, rol (radio: mesero/chef/portero)
  ├─ POST /rest-staff/crear
  │     ├─ Crea usuarios (rol 7/8/9, restaurante_id=X, empresa_id=Y)
  │     ├─ Crea rest_staff con código auto (ME001/CH002/PT003)
  │     └─ Flash success
  └─ Tabla muestra staff con badge por rol y botón Desactivar
```

### 3.3 Login staff por slug (separado de CarniHub)

```
Staff abre URL branded: /acceso/{slug-restaurante}
  ├─ StaffAccesoController::index → vista login con logo+colores del restaurante
  ├─ POST /acceso/login con email+password
  ├─ Verifica usuario.activo=1 AND usuario.restaurante_id = restaurante.id
  ├─ Setea $_SESSION['usuario'] con rol_slug
  └─ redirectSegunRol():
        mesero  → /rest-mesero/dashboard
        chef    → /rest-chef/dashboard
        portero → /rest-portero/dashboard
```

### 3.4 Operación COMPLETA del comensal (con mesa)

```
[1] Llegada
    Comensal entra → portero (opcional según toggle):
      A. portero_habilitado=1:
         ├─ Portero abre /rest-portero/dashboard
         ├─ Click "Registrar entrada" → opcional nombre+teléfono
         ├─ POST /rest-portero/registrarEntrada
         │     ├─ Crea rest_visitas con qr_code = bin2hex(random_bytes(16))
         │     └─ Devuelve qr_code
         └─ Portero imprime/muestra QR al comensal
      B. portero_habilitado=0:
         └─ Comensal va directo a su mesa, no se crea visita aún

[2] Sentarse y ordenar
    Comensal escanea QR de mesa pegado en la mesa
      ├─ URL pública: /menu/{slug}?mesa={qr_codigo_mesa}
      ├─ RestPublicoController::index
      │     ├─ Busca rest_restaurantes WHERE slug=...
      │     ├─ Busca rest_mesas WHERE qr_codigo=... (si hay ?mesa=)
      │     ├─ Lee cookie 'visita_{restId}' (4h TTL)
      │     │     ├─ Si existe y visita 'activa': se reutiliza (más órdenes)
      │     │     └─ Si no: visitaId=0 (se creará al ordenar)
      │     └─ Render publico/menu/index.php con catálogo
      ├─ Comensal arma carrito en su celular
      ├─ POST /menu/{slug}/ordenar con platillo_id[], cantidad[], mesa_qr, visita_id
      │     ├─ Si visita_id NULL → RestVisitaModel::crear() + cookie 4h
      │     ├─ Crea rest_pedidos (estado='pendiente', mesa_id, visita_id)
      │     ├─ Crea rest_pedido_items por cada platillo
      │     └─ RestVisitaModel::actualizarTotales()
      └─ Redirect a /menu/{slug}/confirmacion/{visitaId}
            └─ "✅ Pedido recibido", lista items, link "Pedir más" o "Pagar"

[3] Cocina
    Chef entra a /rest-chef/dashboard (KDS dark mode)
      ├─ JS hace fetch a /rest-chef/queue cada 5s
      │     └─ JSON con pedidos+items WHERE estado IN (pendiente, en_preparacion)
      ├─ Si hay items NUEVOS (set diff) → Web Audio API beep
      ├─ Click "Prep ▶" → POST /rest-chef/marcarPreparacion/{itemId}
      │     └─ rest_pedido_items.estado = 'en_preparacion'
      └─ Click "Listo ✓" → POST /rest-chef/marcarListo/{itemId}
            ├─ rest_pedido_items.estado = 'listo'
            ├─ Si TODOS los items del pedido están 'listo':
            │     ├─ rest_pedidos.estado = 'listo'
            │     └─ ⚠️ NUEVO: descuenta inventario por receta
            │           └─ RestInventarioModel::descontarPorOrden(pedidoId)
            └─ JSON ok=true

[4] Entrega
    Mesero entra a /rest-mesero/dashboard
      ├─ Banner "✅ Órdenes listas para entregar"
      ├─ Grid de mesas con estado (verde=disponible, amber=ocupada, rojo=pagando)
      ├─ Click "Entregado ✓" → POST /rest-mesero/marcarEntregado/{pedidoId}
      │     ├─ rest_pedidos.estado = 'entregado'
      │     └─ rest_pedido_items.estado = 'entregado'
      └─ Página recarga

[5] Pago
    Comensal en su celular abre /menu/{slug}/pagar/{visitaId}
      ├─ RestPublicoController::pagar
      │     ├─ Si NO existe ticket pendiente: RestTicketModel::consolidar(visitaId, propina=0)
      │     │     ├─ Suma rest_pedidos.subtotal de la visita
      │     │     ├─ Crea rest_tickets (estado=pendiente)
      │     │     └─ rest_visitas.estado = 'pagando'
      │     └─ Render publico/menu/pagar.php
      ├─ Vista: subtotal, selector propina, método pago
      ├─ POST /menu/{slug}/confirmarPago/{ticketId} (PÚBLICO sin login)
      │     ├─ Valida ticket pertenece al slug
      │     ├─ ticket.estado = 'pagado', metodo_pago, propina, total
      │     ├─ visita.estado = 'pagada', pagada_at = NOW()
      │     └─ Si paypal: redirect a PayPal Checkout
      └─ Pantalla "✅ ¡Cuenta pagada!" con QR para mostrar al portero

[6] Salida
    portero_habilitado=1:
      Comensal va a la salida → portero escanea QR con cámara o teclado
      ├─ POST /rest-portero/verificar con qr_code
      ├─ JSON: { pagado: bool, mensaje: '✅ PUEDE SALIR' | '❌ PAGO PENDIENTE $X' }
      └─ Si pagado: opcional registrar salida → /rest-portero/registrarSalida
            └─ visita.salida_at = NOW()
    portero_habilitado=0:
      Comensal sale solo, no hay verificación.
```

### 3.5 Flujo TAQUERÍA (sin mesas)

```
Sucursal con mesas_habilitadas=0
  ├─ Menú principal /menu/{slug} (sin parámetro mesa)
  ├─ Comensal escanea QR del local en la pared
  ├─ Arma orden, opcionalmente da nombre
  ├─ Pago INMEDIATO → ticket por orden (no acumula visita)
  ├─ Chef recibe orden con folio
  └─ Cocinero/dueño grita el nombre cuando está listo
```

Diferencias: mesa_id NULL, 1 orden = 1 ticket, mesero/portero no participan.

### 3.6 Inventario integrado CarniHub

```
[A] Compra al distribuidor (CarniHub)
    Admin restaurante → CarniHub como comprador → Carrito → Pedido
      ├─ Pedido se procesa normal
      └─ Al marcar 'entregado':
            ⚠️ PENDIENTE: hook → propone entrada automática al inventario
                  ├─ Por cada item: busca rest_ingredientes WHERE carnihub_producto_id = X
                  │     ├─ Si existe: pre-rellena form (admin confirma)
                  │     └─ Si no: prompt "¿Crear ingrediente nuevo?"
                  └─ Al confirmar: ajustarStock(+cantidad, 'entrada')

[B] Compra externa
    Admin → /rest-inventario/index → tab "Externo"
      ├─ Click "+ Ingrediente" o "+ Movimiento"
      └─ POST /rest-inventario/guardar → rest_movimientos_inventario tipo='entrada'

[C] Consumo (descuento automático)
    Chef marca pedido como 'listo' → descontarPorOrden() recorre receta y descuenta

[D] Merma manual
    Admin → click "Merma" en una fila → tipo='merma' descuenta stock

[E] Alertas stock bajo
    Dashboard → KPI "Ingredientes bajo mínimo" → alertasStockBajo()
```

### 3.7 Finanzas (corte de caja)

```
Mesero/Admin al cierre → /rest-finanzas/cortes → "Nuevo corte"
  ├─ Form: turno (Matutino/Vespertino/Nocturno), notas
  ├─ Sistema calcula automáticamente:
  │     ├─ ingresos = SUM(tickets.total WHERE pagado_at desde último corte)
  │     ├─ propinas = SUM(tickets.propina mismas condiciones)
  │     ├─ gastos   = SUM(gastos.monto desde último corte)
  │     ├─ retiros  = SUM(retiros.monto desde último corte)
  │     └─ utilidad = ingresos - gastos - retiros
  └─ Genera fila rest_cortes y muestra resumen para imprimir
```

---

## 4. Casos extra y desviaciones

| Caso | Cómo se maneja |
|------|----------------|
| Comensal cambia de mesa a media visita | qr_code persiste; mesero edita rest_visitas.mesa_id |
| Pide más después de pago parcial | Nuevo ticket que se suma a la misma visita |
| Pedido se cancela antes de cocinar | POST /rest-pedido/cancelar/{id}. NO descuenta inventario |
| Item individual cancelado | rest_pedido_items.estado='cancelado'. Si todos: pedido cancelado |
| Cocinero se equivoca de plato | Mesero registra "merma" + pedido nuevo |
| Falla conexión del comensal | Cookie visita_{restId} (4h) recupera al recargar |
| Cierra navegador antes de pagar | Mesero usa /rest-ticket/index → genera ticket manual con visitaId |
| Pago efectivo sin portero | Mesero cobra al final, POST a confirmarPago metodo='efectivo' |
| Restaurante deja de tener Empresa | restaurante_activo=0 → sidebar oculta "Mi local", datos persisten |
| Comprador con 2 sucursales | Sidebar dropdown "Sucursal activa" → cambia $_SESSION['restaurante_activo_id'] |
| Staff intenta entrar al login principal | AuthController redirige meseros/chefs/porteros a /acceso/{slug} |
| QR de mesa se daña | Admin → click "Regenerar QR" → bin2hex nuevo |
| Cliente rechaza datos | nombre/telefono opcionales — funciona como invitado |
| 2 chefs trabajando | KDS muestra todos los items; lock optimista por click |
| Mesero pide por el comensal | /rest-pedido/nuevo/{mesaId}, sólo cambia mesero_id |
| PayPal falla | Comensal regresa a /pagar, elige otro método |
| Platillo sin receta | descontarPorOrden con JOIN inner; no descuenta nada |
| QR de mesa OCUPADA escaneado | Permitido — múltiples comensales comparten visita |
| Sin Google Maps API key | Dirección como texto plano sin embed |

---

## 5. Cómo funciona cada sección

### 5.1 Admin Restaurante — `/restaurante/*`

| Sección | URL | Qué hace |
|---------|-----|----------|
| Dashboard | `/restaurante/dashboard` | KPIs: ventas hoy, pedidos pendientes, mesas ocupadas, top platillos |
| Mesas | `/rest-mesa/index` | CRUD mesas con QR. Toggle activo/inactivo |
| Pedidos | `/rest-pedido/index` | Lista todos los pedidos con filtro por estado |
| Reservas | `/rest-reserva/index` | Calendario y lista de reservas |
| Tickets | `/rest-ticket/index` | Tickets emitidos, estado de pago |
| Finanzas | `/rest-finanzas/dashboard` | Ingresos vs egresos, gráficas Chart.js |
| Gastos | `/rest-finanzas/gastos` | CRUD con categoría y comprobante |
| Retiros | `/rest-finanzas/retiros` | Retiros de caja |
| Corte de caja | `/rest-finanzas/cortes` | Cierre de turno automatizado |
| Menú | `/rest-menu/index` | CRUD platillos por categoría |
| Receta | `/rest-menu/form/{id}` | Editor receta con ingredientes y gramajes |
| Inventario | `/rest-inventario/index` | Stock con tabs CarniHub/Externo |
| Movimientos | `/rest-inventario/movimientos` | Historial entradas/salidas/mermas |
| Comensales | `/rest-cliente/index` | Lista de comensales registrados |
| Top consumo/visitas | `/rest-cliente/topConsumo`, `/topVisitas` | Rankings |
| Staff | `/rest-staff/index` | CRUD meseros/chefs/porteros con tarjetas por rol |
| QR del local | `/rest-config/qr` | Genera QR público descargable |
| Configuración | `/rest-config/index` | Branding, horarios, toggles modos §2 |

### 5.2 Mesero — `/rest-mesero/*`

| Sección | Qué hace |
|---------|----------|
| Dashboard | Banner órdenes listas + grid de mesas + botón "+ Pedido" |
| Marcar entregado | AJAX → pedido='entregado' |
| Nuevo pedido por mesa | `/rest-pedido/nuevo/{mesaId}` reutilizado |

### 5.3 Chef — `/rest-chef/*`

| Sección | Qué hace |
|---------|----------|
| Dashboard KDS | Cards dark mode, polling 5s, beep nuevos pedidos |
| Marcar prep | AJAX item → en_preparacion |
| Marcar listo | AJAX item → listo. Si pedido completo: descuenta inventario |

### 5.4 Portero — `/rest-portero/*`

| Sección | Qué hace |
|---------|----------|
| Dashboard | Scanner cámara (jsQR) + input manual + form registrar entrada |
| Verificar | AJAX qr_code → JSON pagado/no pagado con mensaje grande |
| Registrar entrada | Crea visita con QR único |
| Registrar salida | Marca salida_at en visita |

### 5.5 Comensal — `/menu/{slug}`

| Sección | Qué hace |
|---------|----------|
| Menú | Mobile-first, branded, carrito JS local |
| Ordenar | POST crea visita+pedido, cookie 4h |
| Confirmación | Lista items, link "Pedir más" / "Ver cuenta" |
| Pagar | Selector propina + método. PayPal Checkout o pago directo |

---

## 6. Estado actual

### ✅ HECHO

#### Base de datos
- [x] `022_restaurantes_core.sql` — 15 tablas core
- [x] `023_restaurantes_finanzas.sql` — gastos, retiros, cortes
- [x] `024_restaurantes_reservaciones.sql`
- [x] `025_roles_restaurante.sql` — roles 7/8/9, columnas restaurante_id

#### Modelos
- [x] RestauranteModel, RestMesaModel, RestMenuModel, RestInventarioModel
- [x] RestPedidoModel, RestFinanzasModel, RestClienteModel, RestVisitaModel
- [x] RestReservaModel, RestTicketModel

#### Controllers
- [x] RestauranteController, RestConfigController (+ qr), RestMesaController
- [x] RestMenuController (con recetas), RestInventarioController (CarniHub + ext)
- [x] RestPedidoController, RestFinanzasController, RestClienteController
- [x] RestReservaController, RestTicketController
- [x] RestChefController (KDS + AJAX), RestMeseroController, RestPorteroController
- [x] RestPublicoController (menú + ordenar + pagar + confirmación)
- [x] StaffAccesoController — login staff por slug
- [x] RestStaffController — CRUD staff con código autoincremental

#### Vistas admin
- [x] layouts/main.php con CSS separado y modal-display fallback inline
- [x] dashboard, mesas (modal), pedidos (index/detalle/nuevo), reservas
- [x] menu (index + form con receta), inventario (tabs CarniHub/Externo + movimientos)
- [x] finanzas (dashboard, gastos, retiros, cortes), tickets (index/detalle)
- [x] clientes (index/detalle/top), staff (index con role cards)
- [x] config (branding + Maps), config/qr (descargable + URL staff)

#### Portales staff
- [x] chef/dashboard.php — KDS dark mode, AJAX 5s, Web Audio beep
- [x] mesero/dashboard.php — grid mesas + listos
- [x] portero/dashboard.php — jsQR scanner + entrada/salida
- [x] staff/login.php — login branded por restaurante

#### Menú público
- [x] publico/menu/index.php — mobile-first, carrito, cookie visita 4h
- [x] publico/menu/confirmacion.php
- [x] publico/menu/pagar.php — propina + método

#### Infraestructura
- [x] public/css/restaurant.css con modal animado y cache-busting
- [x] index.php rutas + auth guard exento /menu/* y /acceso/*
- [x] BaseController guards (requireRestaurante/Mesero/Chef/Portero)
- [x] Sidebar comprador "Ver mis locales"
- [x] Cookie visita 4h por restaurante
- [x] Visita actualiza totales al ordenar

### 🔴 PENDIENTE BLOQUEANTE para flujo end-to-end

- [ ] **Pago público sin login** — pagar.php POSTea a `rest-ticket/confirmarPago` que requiere login → rompe el flujo. Falta endpoint público en RestPublicoController.
- [ ] **Descuento de inventario** — `descontarPorOrden()` existe pero NUNCA se llama. Hook en chef::marcarListo cuando todo el pedido pasa a 'listo'.
- [ ] **Migración 026** — campos `mesas_habilitadas`, `reservas_habilitadas`, `portero_habilitado`, `propinas_sugeridas`, `requiere_login_comensal`.

### 🟡 PENDIENTE alta prioridad

- [ ] Toggle modos §2 en `restaurante/config/index.php`
- [ ] Vista `/restaurante/dashboard` con KPIs reales (actualmente básica)
- [ ] Vista `/restaurante/index` (lista sucursales) y `/seleccionar` (cambiar activa)
- [ ] Auto-import compras CarniHub al inventario (hook en pedido entregado)
- [ ] Integración real PayPal en confirmarPago

### 🟢 PENDIENTE media

- [ ] Google Auth comensales en menú público
- [ ] Registro comensal nombre+teléfono+SMS
- [ ] Layout visual mesas (drag & drop)
- [ ] Multi-sucursal selector dropdown topbar
- [ ] Reservaciones form (`reservas/form.php`)
- [ ] Reportes CSV/PDF
- [ ] Print QR mesa (PDF por mesa)
- [ ] Sistema notificaciones in-app (campanita)
- [ ] Guías contextuales en cada página

### 🔵 PENDIENTE Sprint 3

- [ ] PWA manifest + service worker
- [ ] Dark mode toggle admin
- [ ] Analíticas avanzadas (platillos populares, horarios pico)
- [ ] Google Auth API key en panel superadmin
- [ ] Webhook PayPal para pagos pendientes

---

## 7. Por dónde empezar a corregir

> Estado **post-sprint v3.4**. Ya se completaron los bloqueantes; abajo solo lo pendiente.

### ✅ FUNCIONANDO HOY (qué probar)

- **Onboarding de comprador**: crea restaurante → redirige a `/restaurante/bienvenida` con link `/acceso/{slug}` shareable + QR + guía.
- **Dashboard restaurante**: KPIs reales + **banner onboarding** con checklist (info, mesas, menú, staff) y barra de progreso.
- **Configuración**: 4 toggles de modos con badge **Activo/Apagado** visible + propinas CSV. Mapa via **Leaflet/OpenStreetMap** (sin API key).
- **Staff**: portal `/rest-staff/index` (ya sin error 500) — crea cuentas mesero/chef/portero, código auto, login en `/acceso/{slug}`.
- **Usuarios de prueba** (LA COMALADA, migration 027): `mesero1@la-comalada.test` · `chef1@la-comalada.test` · `portero1@la-comalada.test` — pass `Test1234!`.
- **Wizard de platillo**: 3 pasos (info → receta → revisar) con validación, badge de pasos y resumen final.
- **Menú público**: empty state bonito si aún no hay platillos.
- **Modales**: centrados de verdad (flex centrado + scroll interno) con animación elástica `cubic-bezier`.
- **KDS chef → inventario**: cuando el chef marca el último ítem como listo, ejecuta `descontarPorOrden()`.
- **Pago público sin login**: `/menu/{slug}/pagar/{visita}` permite confirmar pago como invitado.

### 🟡 EN PROGRESO / PARCIAL

- **Categorías de menú**: el modal funciona pero no hay UX para reordenar drag&drop.
- **Inventario CarniHub**: tabla y CRUD listos; falta el hook automático cuando un pedido al distribuidor pasa a `entregado`.
- **PayPal real**: `confirmarPago` marca pagado pero aún no llama a `PayPalPagoService` — modos efectivo/tarjeta/transferencia funcionan localmente.

### 🔴 PENDIENTE (orden sugerido)

1. **Hook auto-import compras CarniHub → inventario del restaurante**
   - En `EmpresaPedidoController::cambiarEstado` cuando pasa a `entregado` y comprador tiene `restaurante_activo`, proponer entrada de inventario.
2. **PayPal real en pago público**
   - Reemplazar la confirmación inmediata por `PayPalPagoService::crearOrden` y handler de retorno/webhook.
3. **Layout drag & drop de mesas**
   - Canvas con posiciones X/Y persistidas en `rest_mesas`.
4. **Selector multi-sucursal en topbar**
   - Dropdown con `getByComprador()` para cambiar `$_SESSION['restaurante_activo_id']`.
5. **Reservaciones — formulario completo + recordatorio email**
6. **Notificaciones in-app** (tabla + helper + badge en topbar)
7. **Reportes CSV/PDF** finanzas, inventario, mejores comensales
8. **Google Auth para comensales** (módulo público de menú)

---

## 8. Historial de cambios

| Fecha | Sprint | Cambio |
|-------|--------|--------|
| 2026-05-13 | v3.4 | **Bugfix sprint**: arreglado 500 en `/rest-staff/index` (PDO methods correctos), toggles con label-based switch + badge Activo/Apagado, modales realmente centrados con animación elástica, mapa migrado a Leaflet/OSM (sin API key), banner onboarding con checklist en dashboard, wizard de 3 pasos para crear platillo, empty state en menú público |
| 2026-05-13 | v3.3 | Plan reescrito HIPER completo: actores, modos sucursal, flujos detallados, casos extra, estado, prioridades, historial. Implementado pago público sin login + descuento inventario al marcar listo. Bienvenida post-creación + migración 026 modos + 027 staff prueba |
| 2026-05-13 | v3.2 | Modal CSS cache-bust + fallback inline |
| 2026-05-13 | v3.2 | UX: modales animados, staff CRUD, inventario tabs CarniHub/Externo, QR del local descargable |
| 2026-05-13 | v3.2 | CSS separado, flujo QR, portal staff /acceso/{slug}, checklist |
| 2026-05-12 | v3.1 | Módulo restaurantes v3.2 base — 15 tablas, modelos, controllers, vistas admin |
| 2026-05-11 | v3.0 | Conflicto RecurrenteModel resuelto — analytics de pedidos |

---

## 9. Activar el módulo (pasos manuales)

```sql
SOURCE migrations/022_restaurantes_core.sql;
SOURCE migrations/023_restaurantes_finanzas.sql;
SOURCE migrations/024_restaurantes_reservaciones.sql;
SOURCE migrations/025_roles_restaurante.sql;
SOURCE migrations/026_rest_modos.sql;
SOURCE migrations/027_test_staff_la_comalada.sql;   -- usuarios de prueba

UPDATE usuarios SET restaurante_activo = 1 WHERE email = 'tu@correo.com';
```

Luego: cerrar sesión → "Ver mis locales" → crear restaurante.

**Logins de prueba (LA COMALADA, slug `la-comalada`)**

| Rol     | Email                          | Pass        |
|---------|--------------------------------|-------------|
| Mesero  | mesero1@la-comalada.test       | Test1234!   |
| Chef    | chef1@la-comalada.test         | Test1234!   |
| Portero | portero1@la-comalada.test      | Test1234!   |

URL del staff: `BASE_URL/acceso/la-comalada`
