# Anexo B — Cajero: pantallas, ticket e impresión

**Anexo de** [`plan-web-cajero.md`](plan-web-cajero.md) · **Creado:** 2026-08-25

---

## 1. Principios de diseño del POS

El POS no es un panel de administración. Se usa de pie, con prisa, a veces con una mano, con un
cliente enfrente esperando. Las reglas:

- [ ] **Targets de 48×48px mínimo.** Nada que dependa de `hover` para funcionar.
- [ ] **Un objetivo por pantalla.** La pantalla de venta no navega a otro lado: todo pasa en modales.
- [ ] **El total siempre visible**, grande, sin tener que hacer scroll.
- [ ] **Confirmación solo donde duele** (cobrar, cancelar, cerrar turno). Agregar un producto no se confirma.
- [ ] **Errores en lenguaje de cajero**, no de programador: "Faltan $30.00 por cubrir", no "422 Unprocessable".
- [ ] **Nunca dejar la pantalla en blanco**: estado vacío, de carga y de error en cada lista.
- [ ] **Feedback inmediato**: cada toque responde en menos de 100ms aunque el servidor tarde
      (optimista en el carrito, nunca en el cobro).
- [ ] Branding del negocio con las variables `--cp` / `--cs` que ya usa `restaurant.css`.

---

## 2. Mapa de pantallas

```
auth/login  (email+password, una vez al día)
     │
     ▼
rest-caja/index ──► seleccionar cajero ──► PIN
     │
     ├── sin turno abierto ──► rest-caja/apertura ──┐
     │                                              │
     └── con turno abierto ─────────────────────────┤
                                                    ▼
                                          rest-caja/venta  ◄── pantalla base
                                             │
              ┌──────────────┬───────────────┼───────────────┬──────────────┐
              ▼              ▼               ▼               ▼              ▼
       rest-caja/pedidos   historial     modal cobro   modal movimiento   cierre
                              │                                              │
                              ▼                                              ▼
                          ticket (reimpresión)                        reporte/{turnoId}
```

---

## 3. Wireframes

### 3.1 `rest-caja/index` — selección de cajero + PIN

```
┌────────────────────────────────────────────┐
│  [logo]   Cafetería El Roble               │
│                                            │
│  ¿Quién va a operar la caja?               │
│                                            │
│   ┌────────┐  ┌────────┐  ┌────────┐       │
│   │   AS   │  │   MG   │  │   LR   │       │
│   │ Angel  │  │ María  │  │ Luis   │       │
│   └────────┘  └────────┘  └────────┘       │
│                            🔒 bloqueado    │
│                                            │
│   PIN de Angel:   ● ● ● ○                  │
│        ┌───┬───┬───┐                       │
│        │ 1 │ 2 │ 3 │                       │
│        │ 4 │ 5 │ 6 │                       │
│        │ 7 │ 8 │ 9 │                       │
│        │ ← │ 0 │ ✓ │                       │
│        └───┴───┴───┘                       │
└────────────────────────────────────────────┘
```
- Un cajero bloqueado se ve pero no se puede seleccionar, con el tiempo restante.
- El PIN nunca viaja en la URL ni se guarda en `localStorage`.

### 3.2 `rest-caja/apertura`

```
┌────────────────────────────────────────────┐
│  Apertura de turno — Angel                 │
│  Lunes 25 ago, 08:14                       │
│                                            │
│  ¿Con cuánto efectivo abres?               │
│         ┌──────────────────┐               │
│         │  $  1,000.00     │               │
│         └──────────────────┘               │
│   [ $500 ]  [ $1,000 ]  [ $1,500 ]         │
│                                            │
│  Notas (opcional) ______________________   │
│                                            │
│            [ Abrir turno ]                 │
└────────────────────────────────────────────┘
```

### 3.3 `rest-caja/venta` — la pantalla principal

```
┌───────────────────────────────────────────────────────────────────────┐
│ Angel · Turno #17 abierto 08:14   [🔔 3 pedidos]  [🔒]  [Cierre]      │
├──────────────────────────────────────────┬────────────────────────────┤
│ [ Buscar producto o código…        ] ⌕   │  VENTA ACTUAL              │
│                                          │                            │
│ ┌Café──┐┌Panadería┐┌Comida┐┌Bebidas┐     │  2× Latte grande     $90.00│
│                                          │     + doble shot     $15.00│
│ ┌────────┐ ┌────────┐ ┌────────┐         │  1× Croissant        $38.00│
│ │ Latte  │ │Capuchino│ │Croissant│       │     "sin mantequilla"      │
│ │ $45.00 │ │ $48.00 │ │ $38.00 │         │                            │
│ └────────┘ └────────┘ └────────┘         │  ─────────────────────────│
│ ┌────────┐ ┌────────┐ ┌────────┐         │  Subtotal          $143.00 │
│ │Americano│ │ Té    │ │ Muffin │         │  Descuento 10%     −$14.30 │
│ │ $35.00 │ │ $30.00 │ │ $42.00 │         │  Propina            $15.00 │
│ └────────┘ └────────┘ └────────┘         │  ═════════════════════════ │
│                                          │  TOTAL             $143.70 │
│                                          │                            │
│                                          │ [Cliente] [Cupón] [Desc.]  │
│                                          │ [  Cobrar  (F2)         ]  │
└──────────────────────────────────────────┴────────────────────────────┘
```

- Tocar un producto con modificadores abre el modal de opciones; sin modificadores entra directo.
- Tocar una línea del carrito la edita (cantidad, nota, quitar).
- El carrito se guarda en `sessionStorage` en cada cambio: un F5 accidental no lo borra (T26).

### 3.4 Modal de cobro

```
┌──────────────────────────────────────────┐
│  Cobrar $143.70                          │
│                                          │
│  [Efectivo] [Tarjeta] [Transfer] [Wallet]│
│   (solo los habilitados en metodos_pago) │
│                                          │
│  Efectivo                                │
│  Recibí:  ┌──────────┐                   │
│           │ $200.00  │                   │
│           └──────────┘                   │
│  [$150] [$200] [$500] [Exacto]           │
│                                          │
│  ┌────────────────────────────────────┐  │
│  │  CAMBIO:            $56.30         │  │
│  └────────────────────────────────────┘  │
│                                          │
│  ¿Pago mixto?  [+ Agregar otro método]   │
│  Cubierto: $143.70 / $143.70  ✓          │
│                                          │
│         [Cancelar]   [ Cobrar ]          │
└──────────────────────────────────────────┘
```
- El botón "Cobrar" se deshabilita solo mientras el request está en vuelo (defensa contra T6).
- En pago mixto, la barra "Cubierto" es la que decide si se puede cobrar.

### 3.5 `rest-caja/pedidos`

```
┌────────────────────────────────────────────────────────────┐
│ Pedidos de la app          [ Buscar folio o nombre    ] ⌕  │
├──────────────────────────────┬─────────────────────────────┤
│ YA PAGADOS (solo entregar)   │ POR COBRAR EN CAJA          │
│                              │                             │
│ ┌──────────────────────────┐ │ ┌─────────────────────────┐ │
│ │ #A-1042  María G.        │ │ │ #A-1043  Luis R.        │ │
│ │ 2 productos      $87.00  │ │ │ 3 productos    $154.00  │ │
│ │ Pickup 10:30  ✅ pagado  │ │ │ Pickup 10:45  ⏳ por     │ │
│ │ [Ver]  [Entregar]        │ │ │ [Ver]  [Cobrar]         │ │
│ └──────────────────────────┘ │ └─────────────────────────┘ │
└──────────────────────────────┴─────────────────────────────┘
```
- Polling cada `pos_polling_segundos`. Pedido nuevo → sonido corto + badge en la barra superior.
- El sonido se puede silenciar por sesión (algunos navegadores exigen un toque previo para permitir audio:
  el primer toque de la sesión desbloquea el `AudioContext`).

### 3.6 `rest-caja/cierre`

```
┌──────────────────────────────────────────────────────┐
│  Cierre de turno #17 — Angel                         │
│  Abierto 08:14 · Cerrando 16:02 (7h 48m)             │
│                                                      │
│  Ventas del turno            42 pedidos              │
│  ────────────────────────────────────────────        │
│  Efectivo                            $3,240.00       │
│  Tarjeta                             $1,890.00       │
│  Transferencia                          $220.00      │
│  Wallet                                 $180.00      │
│  Prepagado en app (informativo)         $960.00      │
│  Propinas                               $310.00      │
│  Descuentos aplicados                   −$145.00     │
│  Cancelaciones                          −$120.00     │
│                                                      │
│  EFECTIVO                                            │
│  Fondo inicial                       $1,000.00       │
│  + Ventas en efectivo                $3,240.00       │
│  − Devoluciones                         −$120.00     │
│  + Ingresos de caja                       $0.00      │
│  − Retiros                             −$500.00      │
│  ══════════════════════════════════════════          │
│  Efectivo esperado                   $3,620.00       │
│                                                      │
│  Efectivo contado  ┌────────────┐                    │
│                    │ $3,600.00  │  [Desglosar]       │
│                    └────────────┘                    │
│  Diferencia: −$20.00  ⚠️ requiere nota               │
│  Nota: ___________________________________           │
│                                                      │
│  ⚠️ Hay 2 pedidos de app sin entregar. Al cerrar,    │
│     pasan al siguiente turno.                        │
│                                                      │
│         [Volver]        [ Cerrar turno ]             │
└──────────────────────────────────────────────────────┘
```

---

## 4. Impresión (decisión D5)

### 4.1 La capa `PrintBridge`

El POS **nunca** habla con una impresora directo. Habla con `PrintBridge`, que elige adaptador
en runtime, en este orden:

```js
// public/js/caja-print.js
const PrintBridge = {
  adaptador() {
    if (window.cajaDesktop?.imprimir) return 'desktop'; // app de escritorio futura — NO implementado
    if (window.qz?.websocket)         return 'qz';      // QZ Tray               — NO implementado
    return 'browser';                                   // v1 — implementado
  },
  async imprimirTicket(pedidoId, { reimpresion = false } = {}) { /* ... */ },
  async abrirCajon() { /* solo desktop/qz; en browser no hace nada y avisa */ },
};
```

- **`browser` (v1, se implementa):** carga `rest-caja/ticket/{id}?w=80` en un `<iframe>` oculto y
  llama `iframe.contentWindow.print()`. Funciona en cualquier PC sin instalar nada.
- **`qz` (v2, solo declarado):** se conectaría por websocket a `localhost` y mandaría ESC/POS crudo,
  con apertura de cajón de dinero.
- **`desktop` (v2, solo declarado):** la app de escritorio expone `window.cajaDesktop.imprimir(payload)`
  y resuelve impresión y cajón de forma nativa.

> **Por qué así:** el módulo probablemente termine siendo app de escritorio. Definiendo el contrato
> desde ahora, esa app solo implementa `window.cajaDesktop` y **no se toca ni una línea del POS**.

### 4.2 Contrato del payload (`rest-caja/ticketPayload/{id}`)

Este JSON es lo que consumirán `qz` y `desktop` cuando se implementen. **Congelarlo ahora.**

```json
{
  "negocio":  { "nombre": "Cafetería El Roble", "direccion": "...", "telefono": "...", "logo_url": null },
  "ticket":   { "folio": "C-3-8123", "fecha": "2026-08-25 10:42:11",
                "cajero": "Angel S.", "turno_id": 17, "reimpresion": false,
                "ancho": "80mm" },
  "cliente":  { "nombre": "María G.", "telefono": "442..." },
  "items": [
    { "cantidad": 2, "nombre": "Latte grande", "precio_unit": 45.00, "subtotal": 90.00,
      "modificadores": [{ "nombre": "Doble shot", "precio_extra": 7.50, "cantidad": 2 }],
      "nota": "sin azúcar" }
  ],
  "totales":  { "subtotal": 143.00, "descuento": 14.30, "propina": 15.00, "total": 143.70,
                "iva_habilitado": true, "iva_porcentaje": 16.00,
                "base_gravable": 110.95, "iva_mxn": 17.75 },
  "pagos":    [ { "metodo": "efectivo", "monto": 143.70, "recibido": 200.00, "cambio": 56.30 } ],
  "leyenda":  "¡Gracias por tu compra!"
}
```

### 4.3 El ticket térmico (`app/views/caja/ticket.php`)

```
        CAFETERÍA EL ROBLE
     Av. Universidad 123, Qro.
          Tel. 442 123 4567
─────────────────────────────────
Folio: C-3-8123
25/08/2026 10:42      Cajero: Angel
─────────────────────────────────
2  Latte grande            90.00
     + Doble shot          15.00
     sin azúcar
1  Croissant               38.00
─────────────────────────────────
Subtotal                  143.00
Descuento (10%)           -14.30
Propina                    15.00
TOTAL                     143.70

Base gravable             110.95
IVA (16%)                  17.75
─────────────────────────────────
Efectivo                  200.00
Cambio                     56.30
─────────────────────────────────
     ¡Gracias por tu compra!
```

CSS del ticket — lo que de verdad importa:

```css
@page { margin: 0; }
@media print {
  body { margin: 0; }
  .no-print { display: none !important; }
}
.ticket        { font-family: "Courier New", monospace; font-size: 12px; line-height: 1.35; }
.ticket--80mm  { width: 72mm; }   /* 80mm de papel ≈ 72mm imprimibles */
.ticket--58mm  { width: 48mm; font-size: 11px; }
.ticket .row   { display: flex; justify-content: space-between; gap: 4px; }
.ticket .row .nombre { overflow-wrap: anywhere; }  /* nombres largos no rompen el layout */
```

- [ ] Probar con nombres de producto largos y con 20+ items (T20).
- [ ] La reimpresión imprime **REIMPRESIÓN** en grande, para que nadie la confunda con la original (T21).
- [ ] Si `iva_habilitado = 0`, el bloque de IVA no se imprime en absoluto.

---

## 5. Atajos de teclado

Para la terminal con teclado. Se muestran en un modal con `?`.

| Tecla | Acción |
|---|---|
| `F2` | Cobrar |
| `F3` | Foco en buscar producto |
| `F4` | Pedidos entrantes |
| `F8` | Movimiento de caja |
| `Esc` | Cerrar modal / limpiar búsqueda |
| `Supr` | Quitar la línea seleccionada del carrito |
| `+` / `-` | Subir/bajar cantidad de la línea seleccionada |
| `Ctrl+L` | Bloquear pantalla |

> Los atajos se desactivan cuando el foco está en un input de texto, salvo `Esc` y `F2`.

---

## 6. Estados que no se pueden olvidar

| Pantalla | Vacío | Cargando | Error |
|---|---|---|---|
| Catálogo | "Este negocio todavía no tiene productos" + link a Admin | Skeleton de tarjetas | "No se pudo cargar el menú" + botón Reintentar |
| Pedidos entrantes | "Sin pedidos pendientes" | Spinner discreto, sin borrar la lista anterior | Badge "sin conexión", sigue reintentando |
| Historial del turno | "Todavía no hay ventas en este turno" | — | — |
| Cobro | — | Botón en "Cobrando…" y deshabilitado | Mensaje concreto + el carrito intacto |

---

## 7. PWA

- [x] `public/caja-manifest.json`: `display: "fullscreen"`, `orientation: "landscape"`,
      `start_url` apuntando a `rest-caja/index`, enlazado desde el `<head>` del POS
- [ ] **Faltan los íconos PNG 192 y 512** (`public/img/caja-192.png`, `caja-512.png`). Sin ellos el
      navegador no ofrece instalar la app: es lo único que falta para cerrar el PWA.
- [ ] Service worker que cachee **solo el shell** (CSS/JS/íconos). **No cachear datos de venta** —
      un precio viejo cobrando de más es peor que un error de red. Queda para v2 junto con el modo
      offline, que es el único motivo real para tener service worker aquí.
- [ ] Documentar el modo kiosco del navegador para las terminales (`chrome --kiosk --app=URL`).

---

## 8. Checklist de QA visual

- [ ] Tablet 10" horizontal (el caso principal)
- [ ] Laptop 1366×768 (el caso real de las cafeterías)
- [ ] Tablet vertical: el carrito pasa abajo, no se rompe
- [ ] Con el teclado en pantalla abierto, el botón Cobrar sigue alcanzable
- [ ] Contraste AA en los colores de branding de al menos 3 negocios distintos
- [ ] Toda la pantalla operable solo con dedo, sin teclado
- [ ] Toda la pantalla operable solo con teclado, sin mouse
