# Anexo A — Cajero: base de datos, endpoints y modelos

**Anexo de** [`plan-web-cajero.md`](plan-web-cajero.md) · **Creado:** 2026-08-25

> Aquí vive el SQL listo para copiar y el contrato del backend. Las decisiones que justifican cada
> tabla están en el documento maestro (§2). Si algo aquí contradice al maestro, gana el maestro.

---

## 1. Migración — `migrations/002_cajero_pos.sql`

El SQL vive en el repo, no aquí: **[`migrations/002_cajero_pos.sql`](migrations/002_cajero_pos.sql)**.
Tener el esquema en dos lugares es la forma más rápida de que uno de los dos quede mintiendo.

### 1.1 Qué crea

| Objeto | Tipo | Para qué |
|---|---|---|
| `roles` → `cajero` | `INSERT IGNORE` | El rol ya existe con id 3 en la BD actual; la migración es segura de todos modos porque `roles.slug` es UNIQUE |
| `usuarios.pin_hash`, `pin_intentos_fallidos`, `pin_bloqueado_hasta`, `pin_actualizado_at` | columnas | Acceso rápido por PIN (cajeros) y autorización de descuentos (admins) |
| `rest_restaurantes.pos_habilitado` | columna | El POS es opcional por negocio |
| `rest_restaurantes.propinas_sugeridas` | columna | El esquema recortado la perdió; el POS la usa para los botones de propina |
| `rest_configuracion` × 10 columnas | columnas | Todo lo configurable del POS (ver §9 del documento maestro) |
| `rest_pedidos` × 9 columnas | columnas | `turno_caja_id`, `cajero_id`, `propina_mxn`, `iva_mxn`, cancelación, `reembolso_pendiente`, `pos_client_uuid` |
| `turnos_caja` | tabla | Apertura, totales congelados, conteo y diferencia |
| `turno_caja_movimientos` | tabla | Retiros e ingresos de efectivo del turno |
| `rest_pedido_pagos` | tabla | Pago mixto y devoluciones |
| `rest_descuentos_log` | tabla | Auditoría de descuentos manuales |

### 1.2 Detalles que importan

- **Es idempotente.** Usa un procedimiento auxiliar (`pos_add_column`) que consulta
  `information_schema` antes de cada `ALTER`, porque MySQL 5.7 no soporta
  `ADD COLUMN IF NOT EXISTS`. Se puede correr dos veces sin romper nada.
  El archivo trae un "Plan B" por si el usuario de MySQL no puede crear procedimientos.
- **El DDL hace commit implícito en MySQL**, así que no hay transacción que valga: respaldo antes.
- **La regla "un turno abierto por cajero" es del motor, no del código.** `turnos_caja` tiene una
  columna generada `cajero_abierto_uk` que vale `cajero_id` mientras el turno está abierto y `NULL`
  al cerrarlo, con un índice UNIQUE encima. MySQL permite muchos `NULL` en un índice único, así que
  un cajero puede tener muchos turnos cerrados pero solo uno abierto.
- **`rest_pedido_pagos.turno_caja_id` es por fila**, no heredado del pedido: una devolución hecha
  en el turno B sobre una venta del turno A tiene que afectar el efectivo del turno B (regla R13).
- **`monto` siempre es positivo**; el signo lo da `tipo` (`cobro` / `devolucion`). Evita sumas con
  signo escondido que después nadie sabe interpretar.
- **Sin claves foráneas nuevas.** `rest_pedidos` en el esquema actual no tiene ninguna; agregar FKs
  solo en las tablas nuevas dejaría una mitad protegida y la otra no, y complica los borrados de
  prueba. El aislamiento se hace en la aplicación, que filtra por `restaurante_id` en cada consulta.
- El archivo trae al final las consultas de **verificación** y el **rollback** completo.

---

## 2. Cambios en archivos existentes

Todos hechos, todos chicos y acotados a lo que el POS necesita.

| Archivo | Cambio |
|---|---|
| `index.php` | `$_roleCookies['rest-caja'] = '_cajero'` y `cajero`/`cocina` en la lista de `auth/logoutStaff` (la ruta `rest-caja` ya estaba registrada) |
| `app/controllers/AuthController.php` | `$esStaffLogin = ['cajero','cocina']` y el negocio se resuelve por `usuarios.restaurante_id`, no por `rest_staff` (tabla que ya no existe) |
| `app/controllers/AuthController.php` | `logoutStaff()` acepta `cajero` y `cocina` en vez de los roles retirados |
| `app/controllers/BaseController.php` | `csrfToken()`, `validarCsrf()`, `csrfInput()` — antes el CSRF solo existía ad-hoc en Moderación |
| `app/models/RestPedidoModel.php` | `prepararItems()` (precios sin escribir), `crear()` anidable en una transacción externa, folio derivado del id, columnas del POS en la whitelist, `delRestaurante()`, `porUuidPos()`, `tomarEnCaja()`, `cancelarDesdeCaja()`, `descontarStockEntrega()` |
| `app/models/RestPromocionModel.php` | `porCodigoVigente()`, `registrarUso()`, `registrarDescuentoManual()` |

Lo que **no** hizo falta tocar: `BaseController::requireCajero()` y `redirectSegunRol()` ya
contemplaban el rol, y `RestStaffController` ya da de alta cajeros.

`requirePos()` no se subió a `BaseController`: vive en el constructor de `RestCajaController`
porque es la única pantalla que depende de `pos_habilitado`.

---

## 3. Endpoints de `RestCajaController`

Ruta base `rest-caja/`. `[JSON]` responde `application/json`; el resto renderiza vista.
Todo POST valida CSRF (`_csrf` en el cuerpo o `X-CSRF-Token` en la cabecera) y sesión de caja.
Todo endpoint filtra por el `restaurante_id` de la sesión.

### Acceso
| Ruta | Método | Qué hace |
|---|---|---|
| `index` | GET | Lista de cajeros + teclado de PIN. Si ya hay cajero y turno, redirige a `venta` |
| `bloqueo` | GET | Igual, pero fijo en el cajero actual (pantalla bloqueada) |
| `pinLogin` | POST `[JSON]` | `{cajero_id, pin}` → valida, cuenta intentos, bloquea temporalmente |
| `definirPin` | POST `[JSON]` | `{pin, pin_confirmacion}` — solo el propio cajero, solo si aún no tiene PIN |
| `bloquear` | POST `[JSON]` | Bloquea la pantalla sin cerrar sesión ni turno |
| `salirCajero` | POST | Cambia de operador; el turno del anterior sigue abierto |

### Turno
| Ruta | Método | Qué hace |
|---|---|---|
| `apertura` | GET | Pantalla de fondo inicial |
| `abrirTurno` | POST | Crea el turno. Si ya hay uno abierto, el índice UNIQUE lo impide |
| `cierre` | GET | Conteo con el resumen calculado en vivo |
| `cerrarTurno` | POST | `{efectivo_contado, denominaciones[], notas}` → cierra y espejea a `rest_cortes` |
| `reporte/{turnoId}` | GET | Reporte de corte imprimible |
| `turnos` | GET | Turnos cerrados propios (solo lectura) |
| `historial` | GET | Ventas y movimientos del turno actual, con reimpresión |

### Venta
| Ruta | Método | Qué hace |
|---|---|---|
| `venta` | GET | Pantalla principal del POS |
| `catalogo` | GET `[JSON]` | Categorías + platillos disponibles + modificadores |
| `buscarCliente?telefono=` | GET `[JSON]` | Busca en `rest_comensales` y `mobile_usuarios`; devuelve saldo si hay monedero |
| `validarCupon` | POST `[JSON]` | `{code}` → cupón vigente del negocio o error |
| `autorizarPin` | POST `[JSON]` | `{pin}` de un admin → token efímero (2 min, un solo uso) |
| `cobrar` | POST `[JSON]` | **El endpoint pesado.** Ver §4 |
| `cancelarVenta/{id}` | POST `[JSON]` | `{motivo}` → cancela y genera los contra-movimientos |
| `movimiento` | POST `[JSON]` | `{tipo, monto, motivo}` — retiro o ingreso de efectivo |

### Pedidos de la app
| Ruta | Método | Qué hace |
|---|---|---|
| `pedidos` | GET | Cola en dos columnas: prepagados y por cobrar |
| `pedidosEntrantes` | GET `[JSON]` | Lo que consume el polling |
| `pedido/{id}` | GET `[JSON]` | Detalle con items |
| `cobrarPedido/{id}` | POST `[JSON]` | Cobra en caja un pedido que venía sin pagar |
| `entregarPedido/{id}` | POST `[JSON]` | Entrega uno prepagado y lo ata al turno como `stripe_app` |

### Ticket
| Ruta | Método | Qué hace |
|---|---|---|
| `ticket/{id}` | GET | Vista térmica HTML. `?w=58\|80`, `?reimpresion=1` |
| `ticketPayload/{id}` | GET `[JSON]` | El mismo ticket como datos, para los adaptadores `qz` / `desktop` (anexo UI §4) |

---

## 4. Contrato de `cobrar()`

**Request**
```json
{
  "client_uuid": "e2a1c0f4-...",
  "items": [
    { "platillo_id": 12, "cantidad": 2, "notas": "sin cebolla",
      "modificadores": [{ "modificador_id": 4, "cantidad": 1 }] }
  ],
  "descuento":  { "tipo": "porcentaje", "valor": 15, "motivo": "cliente frecuente",
                  "autorizacion_token": "abc..." },
  "cupon_code": "BIENVENIDO10",
  "propina_mxn": 20.00,
  "cliente": { "telefono": "4421234567" },
  "pagos": [
    { "metodo": "efectivo", "monto": 120.00, "recibido": 200.00 },
    { "metodo": "tarjeta",  "monto": 80.00,  "referencia": "4242" }
  ]
}
```

**Reglas del servidor (todas dentro de una transacción)**
1. Si `pos_client_uuid` ya existe → devolver ese pedido, código `200`, **sin crear nada** (R11).
2. Recalcular precios, extras, descuento, cupón e IVA **desde la BD**. Ignorar cualquier total del cliente.
3. `SUM(pagos.monto)` debe ser exactamente `total`. Si no, rollback con error legible.
4. Efectivo: `recibido >= monto`; `cambio = recibido - monto` lo calcula el servidor.
5. Descuento > límite sin token válido → rechazo.
6. Wallet: debitar por `WalletModel` **dentro de la misma transacción**; si falla, rollback de todo.
7. Insertar pedido (`pedido_origen='cajero'`, `estado='entregado'`, `pagado_at=NOW()`), items,
   modificadores, pagos, log de descuento y uso de cupón.

**Response**
```json
{ "ok": true, "pedido_id": 8123, "folio": "C-3-8123", "total": 200.00,
  "cambio": 80.00, "ticket_url": "/rest-caja/ticket/8123" }
```

**Errores** — siempre `{ "ok": false, "error": "mensaje para el cajero", "codigo": "PAGO_INCOMPLETO" }`.
Nada de stack traces en pantalla: el cajero tiene un cliente enfrente y necesita saber qué hacer.

---

## 5. Firmas de los modelos

```php
class CajaConfigModel extends BaseModel {
    // Defaults + rest_configuracion + datos del restaurante, todo en un array.
    // Nunca truena si falta la fila o si la migración no corrió.
    public function get(int $restauranteId): array;
    public static function limpiarCache(): void;
}

class TurnoCajaModel extends BaseModel {
    public function abierto(int $cajeroId): ?array;
    public function delRestaurante(int $turnoId, int $restauranteId): ?array;
    public function abrir(int $restauranteId, int $cajeroId, float $fondoInicial,
                          ?int $terminalUsuarioId, ?string $notas = null): int;

    // Calculado en vivo desde rest_pedido_pagos + rest_pedidos + movimientos.
    public function totales(int $turnoId): array;
    public function ventas(int $turnoId): array;

    public function movimientos(int $turnoId): array;
    public function movimiento(array $turno, int $cajeroId, string $tipo,
                               float $monto, string $motivo): int;

    public function pendientesApp(int $restauranteId): array;
    public function contarPendientesApp(int $restauranteId): int;

    public function cerrar(array $turno, float $efectivoContado, ?array $denominaciones,
                           ?string $notas, float $umbralAlerta): array;
    public function espejarCorte(int $turnoId): void;
    public function historial(int $cajeroId, int $limite = 30): array;
}

class PedidoPagoModel extends BaseModel {
    // Valida sin escribir: si la suma no cuadra con el total, lanza excepción
    // con un mensaje que el cajero pueda entender ("Faltan $30.00 por cubrir").
    public function validar(array $pagos, float $total, array $metodosHabilitados): array;

    public function registrar(int $pedidoId, int $restauranteId, ?int $turnoId,
                              ?int $cajeroId, array $pagos): void;

    // Contra-movimientos de una cancelación; devuelve cuánto toca devolver en
    // efectivo y qué métodos quedan pendientes de reembolso del Admin.
    public function devolver(int $pedidoId, int $restauranteId, ?int $turnoId, ?int $cajeroId): array;

    public function porPedido(int $pedidoId): array;
    public static function etiqueta(string $metodo): string;
}

class CajeroPinModel extends BaseModel {
    public function cajerosActivos(int $restauranteId): array;
    public function cajero(int $usuarioId, int $restauranteId): ?array;
    public function segundosBloqueo(array $usuario): int;

    // {ok:bool, error?:string, espera?:int} — cuenta intentos y bloquea sola.
    public function verificar(int $usuarioId, string $pin, int $intentosMax, int $bloqueoMinutos): array;

    public function verificarAdmin(int $restauranteId, string $pin): ?int;
    public function hayAdminConPin(int $restauranteId): bool;
    public function validarFormato(string $pin): ?string;   // null = PIN aceptable
    public function asignar(int $usuarioId, string $pin): void;
}

class WalletModel extends BaseModel {   // fachada sobre amare_/jungle_wallets
    public function disponible(): bool;
    public function saldo(int $mobileUsuarioId): float;
    public function debitar(int $mobileUsuarioId, float $monto, int $pedidoId, string $descripcion): void;
    public function acreditar(int $mobileUsuarioId, float $monto, int $pedidoId, string $descripcion): void;
}
```

---

## 6. Cálculos de referencia

```
subtotal        = Σ (precio_unit_con_extras × cantidad)
descuento       = cupón + descuento manual              (nunca mayor a subtotal)
total           = subtotal − descuento + propina
iva_mxn         = ROUND((total − propina) × iva_pct / (100 + iva_pct), 2)   -- IVA INCLUIDO (D6)
base_gravable   = (total − propina) − iva_mxn

efectivo_esperado = fondo_inicial
                  + Σ pagos(efectivo, cobro)
                  − Σ pagos(efectivo, devolucion)
                  + Σ movimientos(ingreso)
                  − Σ movimientos(retiro)

diferencia      = efectivo_contado − efectivo_esperado
```

> La propina **no** causa IVA y **no** entra en la base gravable, pero **sí** entra en el efectivo
> esperado si se pagó en efectivo.
