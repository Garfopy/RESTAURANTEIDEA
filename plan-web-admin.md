# Plan Detallado — Rol Admin (dueño del negocio)

**Creado:** 2026-08-25 | **Depende de:** `plan-web-marketplace.md` (visión general)

> Este documento baja a nivel de campo de base de datos y pantalla el panel de Admin — quien
> administra un negocio individual (cafetería/restaurante) dentro del marketplace.

---

## 1. Objetivo del rol

El Admin es dueño/operador de **un negocio** (o varios, si tiene multi-sucursal). Administra
menú, inventario, pedidos, personal (cajeros), finanzas, promociones y su relación con la
plataforma (comisión/plan). No cocina ni cobra directamente — para eso están la app móvil
(cocina) y el rol Cajero.

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

## 7. Checklist de implementación

- [ ] Migración SQL con columnas/tablas de §3
- [ ] Controladores: reusar/adaptar `RestMenuController`, `RestInventarioController`, `RestFinanzasController`, `RestPedidoController`, `RestPromocionController`, `RestClienteController`, `RestFacturaController`, `RestConfigController` — quitando todo lo de mesas/reservas/mesero (ver tabla de recorte en `plan-web-marketplace.md` §1)
- [ ] `RestStaffController` adaptado para solo gestionar cajeros (ya no mesero/chef/portero)
- [ ] Vistas de §4
- [ ] Dashboard con las queries de §5
- [ ] Pruebas: crear negocio (vía Superadmin) → Admin configura menú/inventario → recibe pedido → cajero cobra → Admin ve reflejado en finanzas y comisión
