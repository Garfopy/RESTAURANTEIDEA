# Plan Detallado — Rol Cajero (POS)

**Creado:** 2026-08-25 | **Depende de:** `plan-web-marketplace.md` (visión general)

> Este documento baja a nivel de campo de base de datos y pantalla el módulo de Cajero.
> Es el rol con más piezas nuevas porque **hoy no existe ningún POS en el sistema**.

---

## 1. Objetivo del rol

Un cajero atiende **una terminal física** (PC/tablet) dentro de un negocio y hace dos cosas:
1. Vende directo en mostrador (cliente que llega a pedir en persona).
2. Cobra/confirma pedidos que ya llegaron por la app móvil (pickup/delivery).

Todo dentro de un **turno de caja** con apertura y cierre controlados.

---

## 2. Tablas existentes que se reusan tal cual

| Tabla | Para qué la usa el Cajero |
|---|---|
| `rest_platillos`, `rest_categorias_menu` | Catálogo de venta (mismo menú que administra Admin) |
| `rest_modificadores`, `rest_platillo_modificador` | Extras/opciones al armar una venta |
| `rest_pedidos` | Registro de la venta (ver §3 — necesita columnas nuevas) |
| `rest_pedido_items`, `rest_pedido_item_modificadores` | Detalle de productos vendidos |
| `amare_wallets`, `amare_wallet_transactions` | Cobro con saldo del cliente |
| `rest_promociones`, `mobile_promocion_usos` | Aplicar cupón en el cobro |
| `rest_comensales` | Vincular venta de mostrador a un cliente conocido (opcional, por teléfono) |
| `usuarios` | Cuenta del cajero (rol `cajero`, ligado a un `restaurante_id`) |
| `rest_configuracion` | Métodos de pago habilitados, IVA/impuestos si aplica |

---

## 3. Tablas y columnas NUEVAS necesarias

### 3.1 `roles` — nuevo registro
```sql
INSERT INTO roles (id, nombre, slug) VALUES (3, 'Cajero', 'cajero');
```
- [ ] Confirmar que `usuarios.rol_id` acepta este nuevo valor (ya es `tinyint(3) UNSIGNED`, sin problema)

### 3.2 `usuarios` — columnas nuevas para login rápido por PIN
El cajero no debería loguearse con email/password completo cada vez (es una terminal de uso
repetido durante el día). Se agrega:

| Columna | Tipo | Notas |
|---|---|---|
| `pin_hash` | `varchar(255) DEFAULT NULL` | Hash del PIN corto (4-6 dígitos), solo aplica a `rol_id = cajero` |
| `pin_intentos_fallidos` | `tinyint(3) UNSIGNED DEFAULT 0` | Bloqueo tras N intentos |
| `pin_bloqueado_hasta` | `datetime DEFAULT NULL` | Ventana de bloqueo temporal |

- [ ] Login: email/password para entrar a la terminal por primera vez en el día → después, solo PIN para cambiar de cajero o desbloquear pantalla sin cerrar la sesión de la terminal

### 3.3 `turnos_caja` (tabla nueva)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `int(10) UNSIGNED AUTO_INCREMENT PK` | |
| `restaurante_id` | `int(10) UNSIGNED NOT NULL` | FK → `rest_restaurantes.id` |
| `cajero_id` | `int(10) UNSIGNED NOT NULL` | FK → `usuarios.id` |
| `fondo_inicial` | `decimal(10,2) NOT NULL DEFAULT 0.00` | Efectivo con el que abre |
| `total_efectivo` | `decimal(10,2) NOT NULL DEFAULT 0.00` | Calculado al cerrar (suma de ventas en efectivo) |
| `total_tarjeta` | `decimal(10,2) NOT NULL DEFAULT 0.00` | |
| `total_wallet` | `decimal(10,2) NOT NULL DEFAULT 0.00` | |
| `total_propinas` | `decimal(10,2) NOT NULL DEFAULT 0.00` | |
| `efectivo_esperado` | `decimal(10,2) DEFAULT NULL` | `fondo_inicial + total_efectivo` |
| `efectivo_contado` | `decimal(10,2) DEFAULT NULL` | Lo que el cajero cuenta físicamente al cerrar |
| `diferencia` | `decimal(10,2) DEFAULT NULL` | `efectivo_contado - efectivo_esperado` |
| `estado` | `enum('abierto','cerrado') NOT NULL DEFAULT 'abierto'` | |
| `abierto_at` | `datetime NOT NULL DEFAULT CURRENT_TIMESTAMP` | |
| `cerrado_at` | `datetime DEFAULT NULL` | |
| `notas` | `text` | Justificación de diferencias, incidencias del turno |

- [ ] Índice: `idx_turno_rest_estado (restaurante_id, estado)` — para saber rápido si ya hay un turno abierto
- [ ] Regla: **no puede haber dos turnos `abierto` al mismo tiempo para el mismo `cajero_id`**, pero sí varios turnos abiertos simultáneos si el negocio tiene varias terminales/cajeros

### 3.4 `rest_pedidos` — columnas nuevas

| Columna | Tipo | Notas |
|---|---|---|
| `turno_caja_id` | `int(10) UNSIGNED DEFAULT NULL` | FK → `turnos_caja.id`. NULL si el pedido nació en la app y aún no lo toma un cajero |
| `cajero_id` | `int(10) UNSIGNED DEFAULT NULL` | FK → `usuarios.id`, quién lo cobró |
| `motivo_cancelacion` | `varchar(255) DEFAULT NULL` | Obligatorio si `estado = 'cancelado'` |
| `cancelado_por_id` | `int(10) UNSIGNED DEFAULT NULL` | FK → `usuarios.id` |
| `propina_mxn` | `decimal(10,2) NOT NULL DEFAULT 0.00` | Propina capturada en el cobro (si no se usa ya `rest_propinas`, revisar y unificar con ese módulo existente) |

> **Nota:** `rest_pedidos` **ya tiene** `pedido_origen varchar(20) DEFAULT 'cliente'` y `tipo_origen varchar(20) DEFAULT 'menu'` —
> reusar esos para distinguir venta de mostrador vs pedido de app:
> - [ ] `pedido_origen = 'cajero'` cuando la venta nace directo en el POS
> - [ ] `pedido_origen = 'cliente'` (ya es el default) cuando nace en la app móvil, y el Cajero solo la cobra/confirma

### 3.5 `rest_pedido_pagos` (tabla nueva — soporta pago mixto)

Un pedido puede pagarse con más de un método (ej. mitad efectivo, mitad tarjeta).

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `int(10) UNSIGNED AUTO_INCREMENT PK` | |
| `pedido_id` | `int(10) UNSIGNED NOT NULL` | FK → `rest_pedidos.id` |
| `metodo` | `enum('efectivo','tarjeta','wallet','transferencia') NOT NULL` | |
| `monto` | `decimal(10,2) NOT NULL` | |
| `referencia` | `varchar(120) DEFAULT NULL` | Últimos 4 dígitos de tarjeta, folio de terminal, etc. |
| `created_at` | `timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP` | |

- [ ] Índice: `idx_pedido_pagos_pedido (pedido_id)`

### 3.6 `rest_descuentos_log` (tabla nueva — auditoría de descuentos manuales)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `int(10) UNSIGNED AUTO_INCREMENT PK` | |
| `pedido_id` | `int(10) UNSIGNED NOT NULL` | |
| `cajero_id` | `int(10) UNSIGNED NOT NULL` | |
| `tipo` | `enum('porcentaje','monto_fijo') NOT NULL` | |
| `valor` | `decimal(10,2) NOT NULL` | |
| `motivo` | `varchar(255) DEFAULT NULL` | |
| `autorizado_por_id` | `int(10) UNSIGNED DEFAULT NULL` | FK admin, solo si excede el límite libre del cajero |
| `created_at` | `timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP` | |

### 3.7 `rest_configuracion` — columnas nuevas

| Columna | Tipo | Notas |
|---|---|---|
| `descuento_max_cajero_pct` | `decimal(5,2) NOT NULL DEFAULT 10.00` | Límite libre antes de requerir autorización |
| `impresora_ancho_ticket` | `enum('58mm','80mm') DEFAULT '80mm'` | |
| `iva_habilitado` | `tinyint(1) NOT NULL DEFAULT 0` | Si el negocio desglosa IVA en ticket |
| `iva_porcentaje` | `decimal(5,2) DEFAULT NULL` | |

---

## 4. Pantallas (vistas) a construir

- [ ] `caja/login.php` — login por PIN (asume que ya hay sesión de terminal vía email/password)
- [ ] `caja/apertura.php` — captura de fondo inicial, confirma apertura de turno
- [ ] `caja/venta.php` — pantalla principal: catálogo + carrito + cobro (la más importante, diseño táctil)
- [ ] `caja/pedidos-entrantes.php` — cola de pedidos de la app pendientes de cobrar/confirmar
- [ ] `caja/ticket.php` — vista de impresión (formato térmico) + botón reimprimir
- [ ] `caja/historial-turno.php` — ventas del turno actual (solo lectura)
- [ ] `caja/cierre.php` — conteo de efectivo, resumen por método de pago, confirmación de cierre
- [ ] `caja/reporte-cierre.php` — reporte final imprimible del turno cerrado

---

## 5. Flujo detallado (paso a paso)

1. Cajero abre la terminal → si no hay sesión de terminal, login email/password → si ya hay sesión, pide **PIN**
2. Sistema verifica si hay un `turno_caja` abierto para este cajero en este negocio
   - Si no hay → pantalla de apertura (`fondo_inicial`) → crea `turnos_caja` con `estado='abierto'`
   - Si ya hay uno abierto → va directo a `caja/venta.php`
3. En venta de mostrador:
   - Cajero arma el carrito desde el catálogo (`rest_platillos` + `rest_modificadores`)
   - Aplica descuento/cupón si aplica (valida contra `descuento_max_cajero_pct` de `rest_configuracion`)
   - Selecciona método(s) de pago → inserta en `rest_pedido_pagos`
   - Sistema crea `rest_pedidos` con `pedido_origen='cajero'`, `turno_caja_id`, `cajero_id`, `estado='entregado'` (venta de mostrador se entrega en el acto)
   - Imprime ticket
4. En pedido de app móvil:
   - Pedido ya existe en `rest_pedidos` con `pedido_origen='cliente'`, `turno_caja_id=NULL`
   - Cajero lo busca/selecciona de `caja/pedidos-entrantes.php`
   - Al cobrar/confirmar: actualiza `turno_caja_id`, `cajero_id`, inserta pago(s), cambia `estado`
5. Al cerrar turno:
   - Sistema calcula `total_efectivo/tarjeta/wallet/propinas` sumando `rest_pedido_pagos` de las ventas de ese `turno_caja_id`
   - Cajero captura `efectivo_contado` → sistema calcula `diferencia`
   - Se marca `estado='cerrado'`, `cerrado_at=NOW()`
   - Genera reporte de cierre imprimible

---

## 6. Acciones del controlador (`RestCajaController` — nuevo)

- [ ] `loginPin()` — valida PIN, abre sesión de cajero
- [ ] `turnoActual()` — retorna si hay turno abierto para el cajero actual
- [ ] `abrirTurno()` — crea `turnos_caja`
- [ ] `catalogo()` — retorna menú disponible (reusa lógica de `RestMenuController`)
- [ ] `crearVentaMostrador()` — crea `rest_pedidos` + `rest_pedido_items` + `rest_pedido_pagos`
- [ ] `pedidosEntrantes()` — lista pedidos con `turno_caja_id IS NULL` y `restaurante_id` actual
- [ ] `cobrarPedidoApp($pedidoId)` — vincula pedido existente al turno, registra pago
- [ ] `aplicarDescuento($pedidoId)` — valida límite, inserta en `rest_descuentos_log`
- [ ] `cancelarVenta($pedidoId)` — requiere motivo, marca `motivo_cancelacion`/`cancelado_por_id`
- [ ] `imprimirTicket($pedidoId)` — genera payload para el puente de impresión local
- [ ] `cerrarTurno()` — calcula totales, guarda `efectivo_contado`/`diferencia`, marca `cerrado`
- [ ] `reporteCierre($turnoId)` — reporte final

---

## 7. Reglas de negocio clave

- [ ] No se puede vender sin turno abierto
- [ ] No se puede cerrar turno con ventas sin cobrar/pendientes de confirmar (o se define qué pasa con ellas: se quedan para el siguiente turno)
- [ ] Descuento manual > `descuento_max_cajero_pct` → requiere PIN/confirmación de un Admin
- [ ] Cancelación de venta ya impresa → requiere motivo + queda marcada para revisión del Admin
- [ ] Un cajero solo ve/opera pedidos de **su** `restaurante_id` (nunca de otro negocio)
- [ ] Diferencias de caja fuera de un rango configurable generan alerta visible para el Admin

---

## 8. Impresión — puente local (recordatorio de la conversación)

- [ ] Evaluar **QZ Tray** (gratis, open source) instalado en la PC de la caja
- [ ] El navegador envía el payload del ticket (texto/ESC-POS) a QZ Tray vía websocket local
- [ ] QZ Tray imprime en la impresora térmica y, si aplica, abre el cajón de dinero
- [ ] Documentar instalación de QZ Tray como parte del "onboarding" de cada negocio nuevo

---

## 9. Checklist de implementación

- [ ] Migración SQL con todas las tablas/columnas de §3
- [ ] `RestCajaController` + rutas en `Indexphp`
- [ ] Guard `requireCajero()` en `BaseController` (nuevo, análogo a `requireChef()`)
- [ ] Vistas de §4
- [ ] Integración con puente de impresión (§8)
- [ ] Pruebas: apertura → venta mostrador → venta de app → descuento → cancelación → cierre con diferencia
