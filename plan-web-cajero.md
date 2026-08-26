# Plan Detallado — Rol Cajero (POS)

**Creado:** 2026-08-25 · **Actualizado:** 2026-08-25 (decisiones cerradas + verificado contra el código real del repo)
**Depende de:** `plan-web-marketplace.md` (visión general del equipo)
**Anexos:**
- [`plan-web-cajero-bd.md`](plan-web-cajero-bd.md) — migración SQL, endpoints, contratos y modelos
- [`plan-web-cajero-ui.md`](plan-web-cajero-ui.md) — pantallas, ticket térmico, impresión y atajos

> Este documento es la **fuente de verdad del módulo Cajero**. Todo lo que dice fue contrastado
> contra el esquema y el código reales (el volcado `idactivo_cafeteq` del 25-ago y el árbol `app/`),
> no contra suposiciones. Donde el plan original no coincidía con la BD real está marcado con ⚠️.

---

## 0. Cómo leer este documento

| Marca | Significado |
|---|---|
| ✅ | Ya existe en el repo, se reusa tal cual |
| ♻️ | Existe pero hay que tocarlo (cambio pequeño y localizado) |
| 🔨 | Nuevo, hay que construirlo desde cero |
| ⚠️ | Corrección respecto al plan original |
| ❓ | Falta definir con el equipo |

---

## 1. Objetivo del rol

Un cajero atiende **una terminal física** (PC/tablet) dentro de un negocio y hace tres cosas:

1. Vende directo en mostrador (cliente que llega a pedir en persona).
2. Confirma/entrega pedidos que llegaron por la app móvil **ya pagados**.
3. Cobra pedidos de la app que el cliente eligió **pagar al recoger**.

Todo dentro de un **turno de caja** con apertura, movimientos de efectivo y cierre controlados.

El módulo es **opcional por negocio**: se activa con `rest_restaurantes.pos_habilitado` (mismo patrón
que `mesas_habilitadas` / `app_movil_habilitada`, que ya existen). Un negocio que solo recibe pedidos
de la app y los despacha desde el panel de Admin nunca ve el POS.

---

## 2. Decisiones cerradas (2026-08-25)

Estas ya están resueltas. No volver a discutirlas sin actualizar este documento.

| # | Decisión | Qué significa en la práctica |
|---|---|---|
| D1 | **Pedidos de app: prepagado + pagar al recoger** | La app cobra con Stripe/wallet y el cajero solo confirma y entrega. Pero el cliente puede elegir "pago en caja" y ahí el cajero sí cobra. El cierre de turno **separa** lo cobrado en caja (afecta efectivo esperado) de lo prepagado (solo informativo). |
| D2 | **La venta se registra en `rest_pedidos` + `rest_pedido_pagos`** | Sin generar `rest_tickets`. Implica un cambio en `RestFinanzasModel` para que las ventas del cajero cuenten como ingreso (ver §11 — contrato con Admin). |
| D3 | **`turnos_caja` es tabla nueva, con espejo a `rest_cortes`** | Al cerrar el turno se escribe además una fila en `rest_cortes` (reusando `RestFinanzasModel::insertCorte()`) para que la pantalla de cortes que ya existe lo muestre sin tocarla. El espejo es **snapshot, no fuente de ingreso** — no debe sumarse otra vez. |
| D4 | **Alcance v1 = todo el módulo** | Incluye wallet, descuentos con autorización, cupones, propinas, movimientos de efectivo y PWA. Las dos únicas excepciones son QZ Tray (D5) y modo offline (v2). |
| D5 | **Impresión: HTML térmico ahora, QZ Tray solo definido** | v1 imprime con una vista HTML de 58/80mm y `window.print()`, funciona sin instalar nada. La capa `PrintBridge` deja declarados los adaptadores `qz` y `desktop` con su contrato de payload, **sin implementarlos**, porque el POS probablemente termine siendo app de escritorio. Ver anexo UI §4. |
| D6 | **Los precios del menú YA incluyen IVA** | El total es la suma de precios. Si `iva_habilitado = 1`, el ticket desglosa base + IVA de forma **informativa** (`iva_mxn` se calcula hacia atrás). El precio que ve el cliente en la app es exactamente el que paga en caja. |
| D7 | **Cancelar venta cobrada = cancelación + devolución manual** | Marca `estado='cancelado'` con motivo obligatorio y registra un **contra-movimiento** en `rest_pedido_pagos` (`tipo='devolucion'`) para que el efectivo del turno cuadre. Si el pago fue Stripe o wallet, el pedido queda con `reembolso_pendiente=1` para que el Admin lo resuelva. El cajero nunca mueve dinero digital. |
| D8 | **Login = sesión de terminal + PIN por cajero** | La terminal se abre una vez con email+password (rol `cajero`). Después cada cajero **se selecciona de una lista y teclea su PIN**. El turno se ata al cajero del PIN, no al de la sesión de terminal. |
| D9 | **Descuento sobre el límite: PIN de Admin en pantalla** | El Admin también tiene `pin_hash`. Modal → PIN → token efímero de autorización → queda auditado en `rest_descuentos_log.autorizado_por_id`. |
| D10 | **Cierre con pedidos pendientes: permitido con advertencia** | Se listan los pendientes, el cajero confirma y cierra. Esos pedidos quedan con `turno_caja_id = NULL` y los ve el siguiente turno. El reporte de cierre anota cuántos quedaron. |
| D11 | **Propina: sugerencias + monto libre** | Botones leídos de `rest_restaurantes.propinas_sugeridas` (ya existe, default `0,10,15,20`) más campo libre. Se apaga por negocio con `propinas_pos_habilitadas`. |
| D12 | **Movimientos de efectivo dentro del turno** | Retiros e ingresos (sacar dinero a caja fuerte, pagar al proveedor, meter cambio) en `turno_caja_movimientos`. Afectan el efectivo esperado del cierre. Espejo opcional a `rest_retiros` / `rest_gastos`. |

---

## 3. Alcance — qué entra en v1 y qué no

### v1 (este entregable)
- [x] Login de terminal + selección de cajero + PIN + bloqueo de pantalla
- [x] Apertura de turno con fondo inicial
- [x] Venta de mostrador: catálogo, carrito, modificadores, notas
- [x] Descuento manual con límite y autorización por PIN de Admin
- [x] Cupón/promoción vigente
- [x] Propina (sugerida + libre)
- [x] Pago: efectivo con cálculo de cambio, tarjeta, transferencia, wallet del cliente, **mixto**
- [x] Cola de pedidos entrantes de la app (prepagados y por cobrar) con alerta sonora
- [x] Cobro/confirmación/entrega de pedidos de app
- [x] Cancelación con motivo + contra-movimiento
- [x] Movimientos de efectivo (retiro/ingreso)
- [x] Ticket HTML térmico 58/80mm + reimpresión
- [x] Historial del turno actual
- [x] Cierre de turno: conteo, diferencia, resumen por método, espejo a `rest_cortes`
- [x] Reporte de cierre imprimible
- [x] Historial de turnos propios (solo lectura)
- [x] PWA instalable (manifest + shell, sin caché de datos)

### v2 (documentado, no se programa ahora)
- [ ] Adaptador **QZ Tray**: impresión silenciosa + apertura de cajón de dinero
- [ ] Adaptador **desktop** (Electron/Tauri) consumiendo el mismo payload de ticket
- [ ] **Modo offline**: ventas en IndexedDB + cola de sincronización + indicador visual
- [ ] Reembolso automático real (Stripe refund API + devolución de saldo a wallet)
- [ ] Tiempo real por Firebase/Pusher en vez de polling
- [ ] Terminal de tarjeta integrada (Stripe Terminal) en vez de captura manual

---

## 4. Estado del terreno — qué ya existe en el repo

| Pieza | Estado | Nota |
|---|---|---|
| `rest_platillos`, `rest_categorias_menu` | ✅ | Catálogo de venta, el mismo que administra Admin |
| `rest_modificadores`, `rest_platillo_modificador` | ✅ | Extras/opciones; ya hay validación de `max_seleccion` |
| `RestPedidoModel::crear()` | ♻️ | **Ya valida modificadores, calcula precios e inserta pedido+items+modificadores en una transacción.** El POS lo reusa. Falta ampliar su whitelist de columnas ([RestPedidoModel.php:173](app/models/RestPedidoModel.php#L173)) para aceptar `turno_caja_id`, `cajero_id`, `pedido_origen`, `descuento`, `promo_code`, `propina_mxn`, `iva_mxn`, `pos_client_uuid` |
| `rest_pedidos.pedido_origen` / `tipo_origen` | ✅ | Ya existen con default `'cliente'` / `'menu'`. Se usan tal cual: `pedido_origen='cajero'` para venta de mostrador |
| `rest_pedidos.metodo_pago`, `pagado_at`, `descuento`, `promo_code` | ✅ | Ya existen, no hay que crearlas |
| `rest_pedidos.stripe_payment_intent_id` | ✅ | Por eso un pedido de app puede llegar prepagado (D1) |
| `rest_configuracion.metodos_pago` (JSON) | ✅ | El POS **filtra sus botones de pago desde aquí**, no desde una lista fija |
| `rest_restaurantes.propinas_sugeridas` | ✅ | `'0,10,15,20'` por default — alimenta los botones de propina |
| `rest_restaurantes.*_habilitada` | ✅ | Patrón a copiar para `pos_habilitado` |
| `rest_cortes` + `RestFinanzasModel::insertCorte()` | ✅ | Sirve como espejo del cierre (D3) |
| `rest_retiros`, `rest_gastos` | ✅ | Espejo opcional de los movimientos de efectivo |
| Ruta `rest-caja` en `index.php` | ✅ | Ya estaba registrada apuntando a `RestCajaController` |
| `BaseController::requireCajero()` y `redirectSegunRol()` | ✅ | Ya contemplaban el rol `cajero` |
| Cookie de sesión por rol en `index.php` | ✅ | Se agregó `'rest-caja' => '_cajero'` a `$_roleCookies` |
| `AuthController::doLogin()` | ✅ | `$esStaffLogin` ahora es `['cajero','cocina']` y resuelve el negocio por `usuarios.restaurante_id` (antes consultaba `rest_staff`, tabla que ya no existe) |
| `RestStaffController` | ✅ | Ya da de alta usuarios con rol `cajero`. Le falta el botón de reiniciar PIN (§10) |
| `login_intentos` | ✅ | Patrón que copia el bloqueo de PIN |
| `action_logs` + `BaseController::log()` | ✅ | Auditoría de acciones sensibles del POS |
| Protección CSRF | ✅ | Helper `csrfToken()` / `validarCsrf()` en `BaseController`, usado en todo POST del POS |
| POS / turno de caja / pagos múltiples | ✅ | Construido en este sprint — ver §15 |

---

## 5. ⚠️ Correcciones al plan original

Diferencias reales entre lo que decía el md original y lo que hay en la base de datos.
Si no se corrigen, el código no corre contra la BD real.

1. **El monedero se llama `amare_wallets` / `amare_wallet_transactions`** en la BD real
   (`idactivo_cafeteq`), con `user_id` apuntando a `mobile_usuarios.id`. En el esquema anterior del
   repo las mismas tablas eran `jungle_*`, y el marketplace ya decidió borrar el branding "AMARE",
   así que **se van a volver a renombrar**.
   → **Regla: el POS no toca esas tablas con SQL directo.** Todo pago con saldo pasa por
   `WalletModel`, que detecta el nombre vigente en tiempo de ejecución. Cuando se renombren, se
   cambia un archivo, no diez.
2. **`rest_propinas` no existe como tabla**, y en el esquema recortado tampoco existen ya
   `rest_tickets` ni `rest_visitas`. El POS guarda la propina en `rest_pedidos.propina_mxn`
   (columna nueva) y suma el turno desde ahí.
3. **El rol `cajero` ya existe con `id = 3`** en la BD actual (junto con `1 superadmin`,
   `2 admin_restaurante`, `4 cocina`), sembrado por `migrations/001_roles_cajero_cocina.sql`.
   Ojo: en la BD del sistema anterior el `id = 3` era `admin_empresa`.
   → **Regla que se mantiene:** el código **siempre resuelve el rol por slug**, nunca por número.
   La migración usa `INSERT IGNORE` y `roles.slug` es UNIQUE, así que es segura en las dos bases.
4. **Las vistas no van en `caja/*.php` sueltas.** La convención del repo es
   ruta `rest-{modulo}` → `RestXController` → vistas en `app/views/{modulo}/`.
   → Ruta **`rest-caja`**, controlador **`RestCajaController`**, vistas en **`app/views/caja/`**.
5. **`RestPedidoModel::generarFolio()` era inseguro para un POS.** Usaba `COUNT(*) + 1`, que con dos
   cajeros vendiendo al mismo tiempo genera **folios duplicados**. Ya corregido: el folio se arma
   después del INSERT a partir del id autoincremental (`C-00042` en caja, `P-00042` en el resto).
6. **`rest_configuracion` puede no tener fila para un restaurante.** Todo el POS lee la config con
   defaults vía `CajaConfigModel`, nunca directo de la tabla.
7. **IVA:** la migración `028_pedidos_iva.sql` del sistema anterior (precios SIN IVA,
   `total = subtotal + iva`) aplicaba a la tabla legacy `pedidos` de CarniHub, **no a `rest_pedidos`**.
   No sirve de referencia. Aplica D6: precios con IVA incluido, desglose informativo.
8. **`rest_staff` ya no existe.** El staff se liga al negocio por `usuarios.restaurante_id`.
   `AuthController::doLogin()` seguía consultando `rest_staff` para los roles viejos: se reescribió
   para `cajero` y `cocina` usando la columna del usuario.
9. **`crear()` rechazaba cualquier modificador en el esquema nuevo.** Validaba contra
   `rest_restaurantes.exclusiones_app_habilitadas` / `extras_app_habilitados`, columnas que el
   esquema recortado ya no tiene: al no existir, `empty()` daba true y tiraba
   "modificador deshabilitado" en toda venta con extras. Ahora cae a
   `rest_configuracion.exclusiones_habilitadas` / `extras_habilitados`, que sí existen.

---

## 6. Arquitectura del módulo

```
Indexphp
  └── 'rest-caja' => RestCajaController        (cookie de sesión propia: _caja)
        ├── requireCajero()                    (ya existía en BaseController)
        ├── requirePos()                       (valida rest_restaurantes.pos_habilitado)
        └── requireTurno()                     (bloquea vender sin turno)

app/models/
  ├── CajaConfigModel.php     ✅  parámetros del POS con defaults
  ├── TurnoCajaModel.php      ✅  abrir / cerrar / totales / movimientos
  ├── PedidoPagoModel.php     ✅  cobros, devoluciones, totales por método
  ├── CajeroPinModel.php      ✅  verificar PIN, intentos, bloqueo temporal
  └── WalletModel.php         ✅  fachada del monedero (ver §5.1)

app/views/caja/
  ├── parts/head.php  parts/foot.php
  ├── login.php  apertura.php  venta.php  pedidos.php
  ├── ticket.php (térmico)     historial.php   turnos.php
  └── cierre.php               reporte.php

public/js/
  ├── caja-comun.js     ✅  fetch con CSRF, modales, atajos, sonido
  ├── caja-venta.js     ✅  carrito, cobro, descuentos, propina, cliente
  ├── caja-pedidos.js   ✅  cola de pedidos de la app (polling)
  └── caja-print.js     ✅  PrintBridge (browser | qz | desktop)
```

**Patrón de pantalla:** `venta.php` es **una sola pantalla que no recarga**. El carrito vive en JS
(y en `sessionStorage`, para sobrevivir un F5) y se manda **un solo POST final** a `rest-caja/cobrar`.
El resto de acciones son endpoints JSON del mismo controlador. Sin framework, JS vanilla, igual que
el resto del repo.

**Estado de sesión del POS:**

```php
$_SESSION['usuario']               // cuenta con la que se abrió la TERMINAL (rol cajero)
$_SESSION['restaurante_activo_id'] // lo pone AuthController en el login de staff
$_SESSION['caja'] = [
    'cajero_id'     => 42,         // quién está operando AHORA (el del PIN) — puede ser otro
    'cajero_nombre' => 'Angel',
    'turno_id'      => 17,
    'bloqueada'     => false,
    'autorizacion'  => ['token' => '...', 'admin_id' => 7, 'expira' => 1740000000], // PIN admin, efímero
];
```

> **Regla de oro:** `cajero_id`, `turno_id` y `restaurante_id` **siempre** salen de la sesión del
> servidor, **nunca** de un campo del formulario. Todo endpoint filtra por el `restaurante_id` de sesión.

---

## 7. Flujos detallados

### 7.1 Entrar y abrir turno
1. La terminal entra a `auth/login` con la cuenta de cajero (email+password). `AuthController` la
   detecta como staff login → cookie `_caja`, `restaurante_activo_id` cargado.
2. `rest-caja/index` muestra la **lista de cajeros activos** del negocio (inicial/foto + nombre).
3. El cajero se toca a sí mismo → teclado numérico → PIN → `rest-caja/pinLogin`.
   - PIN correcto → `$_SESSION['caja']['cajero_id']`, se limpian los intentos.
   - PIN incorrecto → `usuarios.pin_intentos_fallidos++`. Al llegar al máximo configurable,
     `pin_bloqueado_hasta = NOW() + N minutos` y ese cajero no entra hasta que pase (o hasta que el
     Admin lo resetee).
4. ¿Hay `turnos_caja` con `estado='abierto'` para ese `cajero_id`?
   - No → `rest-caja/apertura`: captura `fondo_inicial` → crea el turno.
   - Sí → directo a `rest-caja/venta`.
5. Botón **Bloquear** en la barra superior: pone `bloqueada = true` sin cerrar sesión; para volver hay
   que teclear el PIN otra vez. **Cambiar de cajero** regresa al paso 2 (el turno del anterior sigue
   abierto, no se cierra solo).

### 7.2 Venta de mostrador
1. Carrito desde el catálogo (categorías → platillos → modificadores → cantidad → nota).
2. Opcional: vincular cliente por teléfono (`rest_comensales` / `mobile_usuarios`) — obligatorio si se
   va a pagar con wallet.
3. Opcional: cupón (`rest_promociones` por `code`, vigente y del mismo `restaurante_id`).
4. Opcional: descuento manual. Si el % supera `descuento_max_cajero_pct` → modal de PIN de Admin.
5. Propina (si `propinas_pos_habilitadas`): botones desde `propinas_sugeridas` + monto libre.
6. Pago: uno o varios métodos hasta cubrir el total. Efectivo pide **monto recibido** y muestra el cambio.
7. `POST rest-caja/cobrar` con un `client_uuid` generado en el navegador (idempotencia).
   El servidor, **en una sola transacción**:
   - recalcula todo desde cero (nunca confía en los totales que manda el navegador),
   - crea `rest_pedidos` con `pedido_origen='cajero'`, `estado='entregado'`, `turno_caja_id`,
     `cajero_id`, `pagado_at=NOW()`, `metodo_pago` (`'mixto'` si son varios),
   - crea items y modificadores (vía `RestPedidoModel::crear()`),
   - inserta las filas de `rest_pedido_pagos` (`tipo='cobro'`),
   - inserta `rest_descuentos_log` si hubo descuento manual,
   - marca el uso del cupón,
   - debita el wallet si aplica.
8. Respuesta JSON con `pedido_id` y `folio` → la pantalla imprime el ticket y limpia el carrito.

### 7.3 Pedido que viene de la app
1. `rest-caja/pedidosEntrantes` (polling cada N segundos, configurable) lista los pedidos del
   restaurante con `turno_caja_id IS NULL` y estado no final, separados en dos columnas:
   - **Ya pagados** (`pagado_at IS NOT NULL`) → solo confirmar y entregar.
   - **Por cobrar** (`pagado_at IS NULL`) → cobrar como en §7.2 pero sobre el pedido que ya existe.
2. Alerta sonora + badge cuando aparece uno nuevo (comparando contra el último id visto).
3. Al cobrar o al entregar, el pedido se **ata al turno actual** (`turno_caja_id`, `cajero_id`).
   Un pedido prepagado también se ata al turno, pero su pago se registra con `metodo='stripe_app'`
   para que **no sume al efectivo esperado**.

### 7.4 Cancelación
- **Antes de cobrar:** se descarta el carrito, no queda registro.
- **Ya cobrada, mismo turno:** motivo obligatorio → `estado='cancelado'`, `motivo_cancelacion`,
  `cancelado_por_id`, `cancelado_at`, y una fila `rest_pedido_pagos` con `tipo='devolucion'` por cada
  pago original, **atada al turno en el que se hace la devolución** (no al turno original).
  Si el pago fue `wallet` / `stripe_app` → `reembolso_pendiente=1` y el cajero ve el aviso
  "el reembolso lo procesa el Admin".
- **Turno ya cerrado:** el cajero no puede; lo hace el Admin.

### 7.5 Movimiento de efectivo
Botón "Movimiento de caja" → tipo (retiro/ingreso), monto, motivo obligatorio →
`turno_caja_movimientos`. Recalcula el efectivo esperado en vivo.

### 7.6 Cierre de turno
1. El sistema calcula desde `rest_pedido_pagos` del turno (nunca desde lo que teclea el cajero):
   - `total_efectivo`, `total_tarjeta`, `total_wallet`, `total_transferencia`, `total_prepagado_app`
   - `total_propinas`, `total_descuentos`, `total_cancelado`
   - `efectivo_esperado = fondo_inicial + efectivo_cobrado − efectivo_devuelto + ingresos − retiros`
2. Si hay pedidos de app pendientes → se listan y se pide confirmación (D10).
3. El cajero captura `efectivo_contado` (con desglose de denominaciones, opcional).
4. `diferencia = efectivo_contado − efectivo_esperado`. Si `ABS(diferencia) > diferencia_caja_alerta_mxn`
   → **nota obligatoria** y `alerta_diferencia = 1` para que el Admin la vea.
5. `estado='cerrado'`, `cerrado_at=NOW()`, se escribe el espejo en `rest_cortes` y se muestra el
   reporte imprimible.

---

## 8. Reglas de negocio (verificables)

| # | Regla | Dónde se hace cumplir |
|---|---|---|
| R1 | No se puede vender sin turno abierto | `requireTurnoAbierto()` en el controlador, **no solo en la UI** |
| R2 | Un cajero no puede tener dos turnos abiertos a la vez | Columna generada + índice UNIQUE en `turnos_caja` (ver anexo BD) |
| R3 | Varios cajeros SÍ pueden tener turnos abiertos al mismo tiempo en el mismo negocio | La restricción es por `cajero_id`, no por `restaurante_id` |
| R4 | Un cajero solo ve y opera datos de **su** `restaurante_id` | Todo query filtra por el `restaurante_id` de sesión. Se prueba explícitamente (§13, T14) |
| R5 | La suma de pagos debe ser exactamente igual al total del pedido | Validación en servidor dentro de la transacción; si no cuadra, rollback |
| R6 | En efectivo, `recibido >= monto`; el cambio lo calcula el servidor | `PedidoPagoModel` |
| R7 | Descuento manual mayor a `descuento_max_cajero_pct` exige token de autorización vigente | `aplicarDescuento()` + `$_SESSION['caja']['autorizacion']` (expira en 2 min, un solo uso) |
| R8 | Toda cancelación exige motivo no vacío (mínimo 5 caracteres) | `cancelarVenta()` |
| R9 | Los totales del turno se calculan desde `rest_pedido_pagos`; nunca se confía en el cliente | `TurnoCajaModel::totales()` |
| R10 | Diferencia fuera del rango configurable exige nota y genera alerta para el Admin | `cerrarTurno()` |
| R11 | Un cobro repetido con el mismo `client_uuid` devuelve el pedido ya creado, no crea otro | UNIQUE en `rest_pedidos.pos_client_uuid` |
| R12 | El pago prepagado de la app no entra al efectivo esperado | `metodo='stripe_app'`, excluido del cálculo de efectivo |
| R13 | Una devolución afecta al turno en el que se hace, no al turno de la venta original | `rest_pedido_pagos.turno_caja_id` propio por fila |
| R14 | El POS no existe si `pos_habilitado = 0` | `requirePos()` redirige con mensaje |

---

## 9. Qué queda configurable (y dónde)

Nada de esto se hardcodea. Todo se lee de BD con un default sensato.

| Configuración | Dónde vive | Default | Quién lo cambia |
|---|---|---|---|
| POS activo para el negocio | `rest_restaurantes.pos_habilitado` | `0` | Superadmin / Admin |
| Métodos de pago visibles en caja | `rest_configuracion.metodos_pago` (JSON, **ya existe**) | efectivo + tarjeta | Admin |
| Límite de descuento sin autorización | `rest_configuracion.descuento_max_cajero_pct` | `10.00` | Admin |
| Ancho de ticket | `rest_configuracion.impresora_ancho_ticket` | `'80mm'` | Admin |
| Desglose de IVA en el ticket | `rest_configuracion.iva_habilitado` / `iva_porcentaje` | `0` / `16.00` | Admin |
| Propinas en el POS | `rest_configuracion.propinas_pos_habilitadas` | `1` | Admin |
| Porcentajes sugeridos de propina | `rest_restaurantes.propinas_sugeridas` (**ya existe**) | `'0,10,15,20'` | Admin |
| Umbral de alerta por diferencia de caja | `rest_configuracion.diferencia_caja_alerta_mxn` | `20.00` | Admin |
| Intentos de PIN y minutos de bloqueo | `rest_configuracion.pin_intentos_max` / `pin_bloqueo_minutos` | `5` / `5` | Admin |
| Frecuencia de polling de pedidos entrantes | `rest_configuracion.pos_polling_segundos` | `15` | Admin |
| Leyenda al pie del ticket | `rest_configuracion.ticket_leyenda` | `NULL` | Admin |
| Adaptador de impresión | Detección en runtime (`desktop` > `qz` > `browser`) | `browser` | — |

---

## 10. Qué falta definir ❓

### Bloqueantes (sin esto no se puede cerrar el módulo)

- [x] ~~¿Quién da de alta a los cajeros?~~ **Resuelto:** lo hace el Admin desde `rest-staff`, que ya
      crea usuarios con rol `cajero` (`RestStaffController::ROLES_STAFF`).
- [ ] **Falta el botón "reiniciar PIN" del lado de Admin.** Mientras no exista: un cajero sin PIN
      lo crea él mismo la primera vez que entra a la terminal (ya implementado, ver §7.1), pero
      **si lo olvida, nadie puede reiniciarlo desde la interfaz** — hay que hacerlo por SQL.
      Es una acción del módulo de Admin, no del POS.
- [ ] **El Admin necesita PIN para autorizar descuentos** (decisión D9). Hoy ningún admin tiene
      `pin_hash`, así que el modal de autorización aparece pero no lo puede aprobar nadie.
      Mismo dueño: la pantalla de personal de Admin.
- [ ] **Qué valor exacto manda la app móvil en `tipo_origen`.** No bloquea la cola de pedidos
      entrantes (esa filtra por `turno_caja_id IS NULL`, sin importar el origen), pero sí importa
      para el contrato con Finanzas de §11.1.
- [ ] **Contrato con Finanzas** (§11.1) — necesita el visto bueno de quien haga Admin.

### No bloqueantes (se puede avanzar y resolver después)

- [ ] Tiempo real: v1 va con **polling configurable**. Si el equipo elige Firebase/Pusher, solo se
      reemplaza la función que rellena la cola.
- [ ] Stripe en modo live y quién ejecuta los reembolsos pendientes.
- [ ] Multi-sucursal: hoy `usuarios.restaurante_id` es una sola columna, un cajero pertenece a un
      único negocio. ⚠️ Corrección: la nota anterior mencionaba `rest_staff_restaurantes` como
      soporte ya existente para varios negocios por usuario — esa tabla es del esquema viejo
      (Jungle) y **no existe** en la BD real (`idactivo_cafeteq`). Si se necesita multi-sucursal,
      hay que diseñarla desde cero: no hay nada que reusar hoy.
- [ ] Si el POS necesita facturación CFDI en mostrador (`facturacion_solicitudes` ya existe).

---

## 11. Contratos con los otros roles

### 11.1 Con Admin — Finanzas (⚠️ importante)

Hoy `RestFinanzasModel::kpisDashboard()` suma los ingresos desde `rest_tickets`, más los pedidos de app
que cumplan `pedidoAppWhereSql()` (que exige `tipo_origen IN ('app','mobile','movil','jungle_app')`).

**Una venta de mostrador con `pedido_origen='cajero'` no cae en ninguna de las dos → sería invisible en
las finanzas del Admin.**

Lo que se necesita del lado de Admin (o lo hago yo, si se acuerda así):
- [ ] Agregar `pedido_origen = 'cajero'` como fuente de ingreso en `RestFinanzasModel`, excluyendo
      `estado='cancelado'` y restando las devoluciones de `rest_pedido_pagos`.
- [ ] **No sumar `rest_cortes` como ingreso.** El espejo del cierre (D3) es un snapshot para la pantalla
      de cortes. Ojo con [RestFinanzasModel.php:1176](app/models/RestFinanzasModel.php#L1176), donde los
      cortes ya aparecen en el feed de movimientos: hay que revisar que un cierre de turno no se lea como
      dinero extra.
- [ ] Las propinas del POS salen de `rest_pedidos.propina_mxn`, no de `rest_tickets.propina`.

### 11.2 Con Admin — Personal
- [ ] Pantalla de alta de cajeros, asignar/resetear PIN, activar/desactivar (ver §10).
- [ ] Vista de turnos de caja por cajero (lectura de `turnos_caja`), con las diferencias marcadas.
- [ ] Resolver los `reembolso_pendiente = 1` que deja el cajero.

### 11.3 Con Superadmin
Nada bloqueante. Solo que el alta de un negocio deje `pos_habilitado` configurable.

---

## 12. Checklist de implementación (en orden)

**Fase 1 — cimientos**
- [x] Migración SQL completa e idempotente → `migrations/002_cajero_pos.sql`
- [x] `roles`: `cajero` por slug (ya sembrado con id 3 por la migración 001)
- [x] `index.php`: cookie `_cajero` y `'cajero'` en la lista de `logoutStaff`
- [x] `AuthController`: `'cajero'` en `$esStaffLogin`, sin depender de `rest_staff`
- [x] `BaseController`: helper CSRF compartido
- [x] Arreglar `RestPedidoModel::generarFolio()` (§14 R1)
- [x] Ampliar la whitelist de columnas de `crear()` + `prepararItems()` + transacción anidable
- [ ] **Correr la migración en la BD real** ← lo único de esta fase que falta

**Fase 2 — turno**
- [x] `TurnoCajaModel` + `CajeroPinModel`
- [x] Pantallas: selección de cajero, PIN, apertura, bloqueo de pantalla
- [x] `rest-caja/index`, `pinLogin`, `definirPin`, `abrirTurno`

**Fase 3 — venta**
- [x] Endpoint `catalogo()` (JSON, con `config_version` para poder cachear después)
- [x] `venta.php` + `caja-venta.js`: carrito, modificadores, atajos, `sessionStorage`
- [x] `PedidoPagoModel` + `cobrar()` transaccional e idempotente
- [x] Cupón, descuento manual, autorización por PIN de Admin, propina, wallet

**Fase 4 — ticket**
- [x] Payload de ticket (JSON) + vista térmica 58/80mm
- [x] `PrintBridge` con adaptador `browser`; `qz` / `desktop` declarados sin implementar
- [x] Reimpresión desde el historial del turno

**Fase 5 — pedidos de app**
- [x] Cola con polling + alerta sonora + búsqueda por folio/nombre
- [x] Cobrar / entregar
- [x] Cancelación con contra-movimiento

**Fase 6 — cierre**
- [x] Movimientos de efectivo (retiro/ingreso) con espejo a `rest_retiros`
- [x] Cálculo de totales, conteo con denominaciones, diferencia, alerta
- [x] Espejo a `rest_cortes`, reporte imprimible, historial de turnos

**Fase 7 — pulido**
- [x] Estados vacíos / de carga / de error en cada pantalla
- [x] Manifest PWA (`public/caja-manifest.json`)
- [ ] Íconos PNG del PWA (192 y 512) — falta el arte
- [ ] Pasada de accesibilidad táctil en tablet real

---

## 13. Plan de pruebas

Cada caso con su resultado esperado. Marcar solo cuando se probó de verdad, no cuando "debería funcionar".

| # | Caso | Resultado esperado |
|---|---|---|
| T1 | Vender sin turno abierto, llamando el endpoint directo (no por la UI) | 403 / redirect a apertura. **La UI no cuenta como protección** |
| T2 | Abrir turno con fondo `500.00`, vender `120.00` en efectivo, cerrar contando `620.00` | `diferencia = 0.00`, turno `cerrado` |
| T3 | Lo mismo pero contando `600.00` | `diferencia = -20.00`, exige nota si supera el umbral, `alerta_diferencia = 1` |
| T4 | Pago mixto: total `200` = `120` efectivo + `80` tarjeta | Dos filas en `rest_pedido_pagos`, `metodo_pago='mixto'`, solo `120` suma al efectivo esperado |
| T5 | Efectivo recibido `200` sobre total `137.50` | Cambio mostrado `62.50` y guardado en la fila del pago |
| T6 | Doble clic en "Cobrar" (o reintento por red lenta) | **Un solo pedido**. El segundo request devuelve el mismo `pedido_id` |
| T7 | Descuento del 5% con el límite en 10% | Pasa sin pedir autorización |
| T8 | Descuento del 25% con el límite en 10% | Modal de PIN de Admin. Sin PIN válido no se cobra. Con PIN → fila en `rest_descuentos_log` con `autorizado_por_id` |
| T9 | Token de autorización reusado en una segunda venta | Rechazado (un solo uso) |
| T10 | PIN incorrecto N veces | Bloqueo temporal de ese cajero; los demás siguen entrando normal |
| T11 | Cancelar una venta cobrada en efectivo | Pedido `cancelado` con motivo, contra-movimiento creado, el efectivo esperado del cierre baja |
| T12 | Cancelar una venta pagada con wallet | `reembolso_pendiente = 1`, el saldo del cliente **no** se mueve desde el POS |
| T13 | Devolver en el turno B una venta del turno A | La devolución afecta el efectivo del turno B |
| T14 | Cajero del negocio A pide `rest-caja/detallePedido/{id de negocio B}` | 404/403. **Probar con un id real de otro negocio, no con uno inventado** |
| T15 | Dos cajeros vendiendo al mismo tiempo en el mismo negocio | Sin folios duplicados, cada venta a su propio turno |
| T16 | El mismo cajero intenta abrir un segundo turno | Rechazado por el índice UNIQUE, no solo por la UI |
| T17 | Pedido de app prepagado, entregado en caja | Se ata al turno, **no** suma al efectivo esperado, sí aparece en el reporte como prepagado |
| T18 | Pedido de app "pagar al recoger", cobrado en efectivo | Suma al efectivo esperado |
| T19 | Cerrar turno con pedidos de app pendientes | Advertencia + confirmación; los pendientes quedan con `turno_caja_id NULL` y los ve el siguiente turno |
| T20 | Ticket en 58mm y en 80mm | No se corta el texto, los totales quedan alineados, imprime con `window.print()` |
| T21 | Reimprimir un ticket viejo | Idéntico, marcado como **REIMPRESIÓN** |
| T22 | `iva_habilitado = 1` con precios que ya incluyen IVA | El total **no cambia**; el ticket solo desglosa base + IVA |
| T23 | Negocio con `pos_habilitado = 0` | El POS no es accesible ni escribiendo la URL a mano |
| T24 | Restaurante sin fila en `rest_configuracion` | El POS abre con los defaults, no truena |
| T25 | Cerrar el turno y revisar la pantalla de cortes del Admin | Aparece la fila espejo, sin doble conteo de ingresos |
| T26 | Perder conexión a media venta | Mensaje de error claro y el carrito **no** se pierde (sigue en `sessionStorage`) |

---

## 14. Riesgos conocidos

| # | Riesgo | Mitigación |
|---|---|---|
| R1 | **Folios duplicados.** `generarFolio()` usa `COUNT(*)+1`; con dos cajeros simultáneos genera el mismo folio | Cambiarlo por folio derivado del `AUTO_INCREMENT` (`C-{rest}-{id}`) o por una tabla de secuencias con `SELECT ... FOR UPDATE`. **Arreglar en Fase 1** |
| R2 | **Doble cobro** por doble clic o reintento de red | `client_uuid` + UNIQUE (R11) |
| R3 | **Caja que nunca cuadra** por movimientos de efectivo no registrados | D12 — retiros/ingresos obligatorios dentro del turno |
| R4 | **Ventas del POS invisibles en Finanzas** | §11.1, contrato explícito con Admin. Probar con T25 |
| R5 | **PIN de 4 dígitos en un dispositivo compartido** | Selección de cajero + PIN (no "el PIN identifica al cajero"), intentos limitados, bloqueo temporal, y todo POST sensible reverifica sesión |
| R6 | **El resto del sistema no tiene CSRF** | Helper en `BaseController` y usarlo en el POS desde el día 1 |
| R7 | **El renombrado de las tablas `jungle_*`** en el Sprint 1 del marketplace rompe el pago con wallet | Fachada `WalletModel`: un solo archivo que tocar |
| R8 | El POS se vuelve app de escritorio más adelante | El contrato de payload del ticket y los endpoints JSON ya están pensados para eso (anexo UI §4) |
| R9 | El front controller despacha por nombre de método y `method_exists()` no distingue visibilidad: una URL como `rest-caja/armarTicket` provoca un error 500 al intentar llamar un método privado | No es fuga de datos (PHP corta antes de ejecutar y `display_errors` está apagado en producción) pero sí ruido en los logs. Es del router, no del POS: afecta a todos los controladores. Vale la pena filtrar acciones públicas en `index.php` en algún sprint |

---

## 15. Estado de implementación (2026-08-25)

Todo lo de abajo está escrito y pasa `php -l` / `node --check`, pero **todavía no se ha
ejecutado contra la base de datos**: falta correr la migración y hacer la pasada de §13.

### Archivos nuevos

| Archivo | Qué hace |
|---|---|
| `migrations/002_cajero_pos.sql` | Migración idempotente: rol, PIN, `turnos_caja`, `turno_caja_movimientos`, `rest_pedido_pagos`, `rest_descuentos_log`, columnas de POS |
| `app/controllers/RestCajaController.php` | Todos los endpoints del POS |
| `app/models/CajaConfigModel.php` | Config del POS con defaults (nunca truena si falta la fila) |
| `app/models/TurnoCajaModel.php` | Apertura, totales, movimientos, cierre y espejo a `rest_cortes` |
| `app/models/PedidoPagoModel.php` | Pago mixto, validación de cambio, devoluciones |
| `app/models/CajeroPinModel.php` | PIN, intentos, bloqueo, autorización de admin |
| `app/models/WalletModel.php` | Fachada del monedero (`amare_*` o `jungle_*`, detectado en runtime) |
| `app/views/caja/*` | login/PIN, apertura, venta, pedidos, ticket, historial, cierre, reporte, turnos |
| `public/css/caja.css` | Diseño táctil del POS |
| `public/js/caja-comun.js` | fetch con CSRF, modales, atajos, sonido |
| `public/js/caja-venta.js` | Carrito, cobro, descuentos, propina, cliente |
| `public/js/caja-pedidos.js` | Cola de pedidos de la app con polling |
| `public/js/caja-print.js` | `PrintBridge` (browser implementado; qz/desktop declarados) |
| `public/caja-manifest.json` | PWA |

### Archivos tocados (cambios chicos y acotados)

| Archivo | Cambio |
|---|---|
| `index.php` | Cookie `_cajero` para `rest-caja`, `cajero`/`cocina` en `logoutStaff` |
| `app/controllers/AuthController.php` | Login de staff para `cajero`/`cocina` sin `rest_staff` |
| `app/controllers/BaseController.php` | Helpers CSRF compartidos |
| `app/models/RestPedidoModel.php` | `prepararItems()`, `crear()` anidable + folio sin carrera, columnas del POS, `tomarEnCaja()`, `cancelarDesdeCaja()`, `descontarStockEntrega()` |
| `app/models/RestPromocionModel.php` | `porCodigoVigente()`, `registrarUso()`, `registrarDescuentoManual()` |

> **Efecto colateral bueno:** el arreglo de `AuthController` también destraba el login de
> **cocina**, que apuntaba a `rest_staff` (tabla inexistente) y a los roles viejos. Eso es
> territorio del compañero que hizo el KDS: conviene avisarle en vez de que lo descubra por su lado.

### Para dejarlo andando

1. Correr `migrations/002_cajero_pos.sql` (respaldo antes; ver el encabezado del archivo).
2. Crear un cajero desde **Admin → Staff** con rol `cajero`.
3. Entrar a `/auth/login` con esa cuenta → cae en `/rest-caja/venta`.
4. La primera vez pide crear el PIN; después ya es selección + PIN.
5. Abrir turno con un fondo y hacer una venta de prueba.
6. Ir bajando la lista de pruebas de §13.
