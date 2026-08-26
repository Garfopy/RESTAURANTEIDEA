# Plan Detallado — Rol Admin (dueño del negocio)

**Creado:** 2026-08-25 | **Depende de:** `plan-web-marketplace.md` (visión general)

> Este documento baja a nivel de campo de base de datos y pantalla el panel de Admin — quien
> administra un negocio individual (cafetería/restaurante) dentro del marketplace.

---

## 1. Objetivo del rol

El Admin es dueño/operador de **un negocio** (o varios, si tiene multi-sucursal). Administra
menú, inventario, pedidos, personal (cajeros y cocina), finanzas, promociones y su relación con
la plataforma (comisión/plan). No cocina ni cobra directamente — para eso están los roles
Cocina y Cajero (que el propio Admin da de alta).

---

## 2. Tablas existentes que se reusan tal cual

| Tabla | Para qué |
|---|---|
| `rest_restaurantes` | Datos del negocio (ya tiene `lat`/`lng`, `color_primario/secundario`, `horarios_json`, `app_movil_habilitada`) |
| `rest_configuracion` | Métodos de pago, tipos de entrega, costo de envío, pedido mínimo, facturación |
| `rest_categorias_menu`, `rest_platillos` | Menú |
| `rest_modificadores`, `rest_platillo_modificador`, `rest_platillo_modificadores` | Extras/exclusiones del menú |
| `rest_ingredientes`, `rest_recetas`, `rest_receta_ingredientes` | Inventario y costeo |
| `rest_movimientos_inventario` | Entradas/salidas/mermas |
| `rest_pedidos`, `rest_pedido_items` | Pedidos (ver también campos nuevos que agrega `plan-web-cajero.md` §3.4) |
| `rest_gastos`, `rest_retiros` | Finanzas operativas |
| `rest_promociones`, `mobile_promocion_usos` | Cupones/promos propias |
| `rest_comensales` | CRM básico (ya trae `total_visitas`, `total_gastado`, `ultima_visita`) |
| `facturacion_solicitudes`, `mobile_datos_fiscales` | Facturación CFDI |
| `rest_zonas_delivery` | Zonas y costo de envío por zona |

---

## 3. Tablas y columnas NUEVAS necesarias

### 3.1 `rest_restaurantes` — columnas nuevas

| Columna | Tipo | Notas |
|---|---|---|
| `plan_id` | `int(10) UNSIGNED DEFAULT NULL` | FK → `planes_negocio.id` (definida en `plan-web-superadmin.md`) |
| `estado_plataforma` | `enum('pendiente','activo','suspendido','baja') NOT NULL DEFAULT 'pendiente'` | Controlado por Superadmin, visible en solo-lectura para Admin |
| `descuento_max_cajero_pct` | *(ya cubierto en `rest_configuracion`, ver doc de Cajero)* | — |

> Nota: `horario_apertura`/`horario_cierre` (columnas simples `time`) y `horarios_json` (texto)
> **ya conviven** en la tabla. Usar `horarios_json` como fuente de verdad para horario por día de
> la semana (`{"lunes": {"abre":"07:00","cierra":"19:00","cerrado":false}, ...}`) y dejar las
> columnas simples como fallback/compatibilidad con la app móvil si ya las consume así.
- [ ] Confirmar con el equipo de móvil qué formato de horario espera hoy la app antes de cambiar

### 3.2 `rest_cajeros` (tabla nueva — ligera, referencia desde Admin)

En vez de una tabla separada, se reusa `usuarios` con `rol_id = cajero` (ver `plan-web-cajero.md` §3.1).
El Admin necesita una vista de gestión, no una tabla nueva:

| Columna en `usuarios` usada por Admin | Notas |
|---|---|
| `restaurante_id` | Ya existe — liga el cajero a este negocio |
| `activo` | Ya existe — el Admin puede desactivar acceso |
| `pin_hash`, `pin_intentos_fallidos`, `pin_bloqueado_hasta` | Nuevas (ver doc de Cajero §3.2) — el Admin dispara "resetear PIN" |

- [ ] Pantalla de Admin para asignar/resetear PIN de cada cajero (no lo genera el cajero mismo)

### 3.3 `rest_producto_stats` (tabla nueva — opcional, cache de analítica)

Para no recalcular agregaciones pesadas en cada carga del dashboard:

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `int(10) UNSIGNED AUTO_INCREMENT PK` | |
| `platillo_id` | `int(10) UNSIGNED NOT NULL` | |
| `fecha` | `date NOT NULL` | Agregado diario |
| `veces_vendido` | `int(10) UNSIGNED NOT NULL DEFAULT 0` | |
| `ingreso_generado` | `decimal(12,2) NOT NULL DEFAULT 0.00` | |

- [ ] Job (cron) que recalcula esta tabla cada noche a partir de `rest_pedido_items`
- [ ] Marcar como **"nice to have"**, no bloqueante para v1 — se puede calcular al vuelo con `GROUP BY` mientras el volumen sea bajo

### 3.4 `rest_negocio_comisiones` (tabla nueva — solo lectura para Admin)

El cálculo/edición vive en Superadmin (`plan-web-superadmin.md`), pero Admin necesita **ver** su
estado de cuenta:

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `int(10) UNSIGNED AUTO_INCREMENT PK` | |
| `restaurante_id` | `int(10) UNSIGNED NOT NULL` | |
| `periodo` | `char(7) NOT NULL` | `YYYY-MM` |
| `ventas_totales` | `decimal(12,2) NOT NULL DEFAULT 0.00` | |
| `comision_pct_aplicada` | `decimal(5,2) NOT NULL` | |
| `monto_comision` | `decimal(12,2) NOT NULL DEFAULT 0.00` | |
| `estado_pago` | `enum('pendiente','pagado','vencido') NOT NULL DEFAULT 'pendiente'` | |
| `pagado_at` | `datetime DEFAULT NULL` | |

---

## 4. Pantallas (vistas) a construir

- [ ] `admin/dashboard.php` — KPIs, gráficas, alertas
- [ ] `admin/menu/index.php`, `admin/menu/categoria-form.php`, `admin/menu/producto-form.php`
- [ ] `admin/inventario/ingredientes.php`, `admin/inventario/recetas.php`, `admin/inventario/movimientos.php`
- [ ] `admin/pedidos/index.php`, `admin/pedidos/detalle.php`
- [ ] `admin/config/negocio.php`, `admin/config/horarios.php`, `admin/config/pagos.php`, `admin/config/zonas-entrega.php`
- [ ] `admin/personal/cajeros.php` — alta, reset PIN, activar/desactivar
- [ ] `admin/finanzas/ventas.php`, `admin/finanzas/gastos.php`, `admin/finanzas/cortes.php` (consolidado de todos los turnos de caja), `admin/finanzas/comision.php` (solo lectura)
- [ ] `admin/promociones/index.php`, `admin/promociones/form.php`
- [ ] `admin/clientes/index.php`, `admin/clientes/detalle.php`
- [ ] `admin/facturacion/index.php`
- [ ] `admin/resenas/index.php` (si aplica al modelo)

---

## 5. Reportes / analítica — de dónde sale cada dato

| Reporte | Fuente de datos |
|---|---|
| Ventas del día/semana/mes | `SUM(rest_pedidos.total)` agrupado por fecha, `estado NOT IN ('cancelado')` |
| Ticket promedio | `AVG(rest_pedidos.total)` |
| Producto más vendido | `rest_pedido_items` agrupado por `platillo_id` (o `rest_producto_stats` si se implementa el cache) |
| Horas pico | `rest_pedidos.created_at` agrupado por hora |
| Stock bajo | `rest_ingredientes.stock < rest_ingredientes.stock_minimo` |
| Margen por producto | `rest_platillos.precio` vs costo calculado desde `rest_receta_ingredientes` × `rest_ingredientes.costo_unitario` |
| Clientes frecuentes | `rest_comensales.total_visitas` ordenado desc |
| Cortes de caja consolidados | `SUM()` sobre `turnos_caja` (tabla nueva definida en doc de Cajero) filtrado por `restaurante_id` |
| Desempeño de promoción | `COUNT()` sobre `mobile_promocion_usos` / tabla equivalente de uso de cupón |

---

## 6. Reglas de negocio clave

- [ ] Admin solo ve/edita datos de **su propio** `restaurante_id` (o los de su `empresa_id` si maneja multi-sucursal)
- [ ] Admin no puede cambiar su propio `estado_plataforma` (activo/suspendido) — eso es exclusivo de Superadmin
- [ ] Admin no puede editar `comision_pct_aplicada` — solo consulta
- [ ] Desactivar un cajero no borra su historial de turnos/ventas (borrado lógico vía `activo=0`)
- [ ] Cambios en menú/precio no deben afectar pedidos ya creados (los items de pedido guardan `precio_unit` propio, ya es así en `rest_pedido_items` — mantener ese patrón)

---

## 6.12 Cocina (web) — nace también en Admin

> Corrección 2026-08-25: cocina **sí** tiene portal web (`/rest-cocina/`), además de la app
> móvil. El Admin es quien crea las cuentas de cocina (ver §6.6) — cocina no administra menú
> ni inventario, solo ve y avanza el estado de los pedidos activos.

- [x] `RestCocinaController` — cola de pedidos activos (`pendiente`/`en_preparacion`/`listo`), agrupados por pedido con sus items
- [x] Avanzar estado por item (`pendiente → en_preparacion → listo`), auto-avanza el pedido completo cuando todos sus items están listos
- [x] Botón "Entregar / recogido" cuando el pedido ya está listo — lo saca de la cola
- [x] Vista propia (no comparte el sidebar de Admin), pensada para pantalla de cocina, refresco automático por polling (20s)
- [ ] Alerta sonora de pedido nuevo (fase futura, no implementado aún)
- [ ] Filtro/columnas por estación (comida vs bebidas) si el volumen lo justifica más adelante

## 7. Checklist de implementación

- [ ] Migración SQL con columnas/tablas de §3
- [ ] Controladores: reusar/adaptar `RestMenuController`, `RestInventarioController`, `RestFinanzasController`, `RestPedidoController`, `RestPromocionController`, `RestClienteController`, `RestFacturaController`, `RestConfigController` — quitando todo lo de mesas/reservas/mesero (ver tabla de recorte en `plan-web-marketplace.md` §1)
- [x] `RestStaffController` reescrito — ya no depende de `rest_staff`/`rest_zonas`/`rest_mesero_turno` (no existen en el esquema), guarda todo en `usuarios`, gestiona cajero + cocina
- [ ] Vistas de §4
- [x] Dashboard con las queries de §5 (ver `RestFinanzasModel::kpisPedidosDashboard`)
- [ ] Pruebas: crear negocio (vía Superadmin) → Admin configura menú/inventario → recibe pedido → cajero cobra → Admin ve reflejado en finanzas y comisión

---

## 8. Procedimientos operativos del Admin (día a día)

Guía práctica de lo que el dueño/operador del negocio hace en el sistema, paso a paso.

### 8.1 Alta inicial (una sola vez, al empezar)

1. Entra con el usuario que te dio Superadmin (`restaurante/dashboard`)
2. **Configuración** (`rest-config/index`): completa teléfono, dirección, ubicación (lat/lng), horarios, colores de marca
3. **Menú** (`rest-menu/index`): crea tus categorías (ej. Comida, Bebidas, Postres) y agrega tus productos con precio y foto
4. **Inventario** (`rest-inventario/index`): da de alta tus ingredientes con stock actual y stock mínimo (para que te avise cuando se esté acabando)
5. **Staff** (`rest-staff/index`): crea las cuentas de tu equipo — un usuario por cada **cocina** y **cajero**, cada quien con su propio correo y contraseña
6. Comparte con tu equipo el link de acceso (`auth/login`) — cada uno entra con su correo/contraseña y el sistema los manda directo a su portal (cocina → cola de pedidos, cajero → punto de venta)

### 8.2 Rutina diaria

1. Entra al **Dashboard** al abrir — revisa ventas del día anterior, alertas de stock bajo y pedidos activos
2. Si hay ingredientes en alerta de stock bajo, ve a **Inventario → Movimientos** y registra la entrada de mercancía nueva
3. Durante el día: los pedidos entran solos (desde la app móvil o desde el cajero) — cocina los ve en su cola y los va marcando, el Admin puede supervisar desde **Pedidos** (`rest-pedido/index`)
4. Al final del día: revisa **Finanzas → Dashboard Financiero** para ver el resumen de ventas, gastos y utilidad del día

### 8.3 Cómo agregar un producto nuevo al menú

1. **Menú → Categorías**: si el producto es de una categoría que no existe, créala primero
2. **Menú → Nuevo producto**: nombre, descripción, precio, foto, tiempo de preparación estimado
3. Márcalo como "disponible" para que aparezca en la app y en el punto de venta del cajero
4. Si el producto usa ingredientes que quieres descontar automático del inventario, liga su receta (Inventario/Recetas)

### 8.4 Cómo dar de alta un cajero o cocina

1. **Staff → + Nuevo staff**
2. Elige el rol (Cocina o Cajero), nombre completo, correo y contraseña
3. Comparte el link de acceso y sus credenciales con esa persona
4. Si esa persona deja de trabajar ahí, **desactívala** desde la misma pantalla (no se borra su historial, solo pierde acceso)

### 8.5 Cómo manejar un pedido con problema

1. Ve a **Pedidos**, busca el pedido por folio
2. Si hay que cancelarlo: botón "Cancelar pedido" (requiere confirmar)
3. Si el cliente reporta un problema después de entregado, revisa el detalle del pedido para ver exactamente qué se le cobró y qué llevaba

### 8.6 Cierre de comisión con la plataforma (cuando Superadmin lo active)

1. **Finanzas → (pendiente: pestaña de comisión)**: verás cuánto debes a la plataforma del periodo actual
2. El cobro/pago se coordina fuera del sistema por ahora (transferencia manual) hasta que se decida el modelo de Stripe Connect (ver `plan-web-marketplace.md` §11)

### 8.7 Checklist de errores comunes

- [ ] "No puedo entrar" → confirma que tu cuenta esté **activa** (Superadmin puede reactivarla)
- [ ] "No aparecen mis productos en la app" → revisa que el producto esté marcado **disponible** y **activo**
- [ ] "El inventario no baja solo" → revisa que el producto tenga una receta ligada en Inventario
- [ ] "Mi cajero/cocina no puede entrar" → confirma que su cuenta esté activa y que esté usando el correo/contraseña correctos (no hay recuperación de contraseña automática todavía — el Admin puede pedir a Superadmin restablecerla)
