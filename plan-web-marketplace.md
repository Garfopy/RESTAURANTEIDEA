# Plan & Checklist — Web Marketplace Universitario (Superadmin · Admin · Cajero)

**Creado:** 2026-08-25 | **Alcance:** solo la parte **web** del sistema (PHP + HTML + CSS + JS, cPanel)

> Este documento es el checklist maestro para el equipo. Cubre 4 roles web:
> **Superadmin** (nosotros, dueños de la plataforma), **Admin** (dueño de cada cafetería/restaurante),
> **Cajero** (punto de venta / POS) y **Cocina** (KDS web — corrección 2026-08-25: cocina vive
> tanto en web como en la app móvil, no solo en móvil como se dijo al inicio). **Cliente** sigue
> siendo 100% app móvil — ver `PLAN_RECORTE_UTEQ.md` del repo de la app para esa parte.

---

## ⚠️ LEER PRIMERO — flujo de trabajo del equipo y estado de decisiones

### Flujo de trabajo (git)

- Cada quien trabaja los cambios de código en **su propia rama** (una rama por feature/módulo,
  no directo sobre `main`).
- Al terminar, se sube esa rama para que el resto del equipo la revise antes de mezclarla.
- Todo eventualmente se integra a `main`, pero **el `main` actual del repo es la referencia/base**
  sobre la que se parte — no se reescribe desde cero.
- Este archivo y sus 3 acompañantes (`plan-web-superadmin.md`, `plan-web-admin.md`,
  `plan-web-cajero.md`) sí se publican directo a `main` por ser solo documentación de planeación.

### Decisiones YA RESUELTAS por el equipo (2026-08-25)

- ✅ **Modo social: se elimina por completo.** Sin excepciones — controlador, vistas, rutas,
  tablas y cualquier referencia relacionada. Ver detalle actualizado en la sección 1 (tabla) y
  en el Sprint 1 del roadmap (sección 9).
- ✅ **Branding "AMARE": se elimina por completo.** No se conserva nada del nombre/marca del
  sistema legado. Se detectaron **23 archivos** en el repo actual con referencias a "AMARE"
  (incluye un servicio dedicado `AmareModifierSyncService.php`, vistas de dashboard/config,
  varios controladores y modelos, documentación en `docs/`, y `migrations/069_modificadores_app.sql`).
  Queda como tarea de limpieza explícita en el Sprint 1 (sección 9) — no solo es texto visual,
  hay lógica de sincronización de modificadores nombrada "Amare" que hay que revisar antes de
  borrar (puede tener funcionalidad útil detrás del nombre, no solo branding).

### Decisiones que SIGUEN pendientes (ver detalle y recomendación en la sección 11)

- [ ] Modelo de comisión (% por transacción / cuota fija / híbrido)
- [ ] Cómo fluye el dinero (Stripe Connect vs cobro manual centralizado)
- [ ] Mecanismo de tiempo real para pedidos nuevos (Firebase / Pusher / polling)
- [ ] Multi-sucursal desde el día 1 o después
- [ ] Si el módulo Cajero es obligatorio u opcional por negocio

---

## ÍNDICE

0. [Visión del producto](#0-visión-del-producto)
1. [Qué se hereda del sistema actual vs qué se elimina](#1-qué-se-hereda-del-sistema-actual-vs-qué-se-elimina)
2. [Arquitectura y stack](#2-arquitectura-y-stack)
3. [Modelo de datos (alto nivel)](#3-modelo-de-datos-alto-nivel)
4. [Matriz de roles y permisos](#4-matriz-de-roles-y-permisos)
5. [ROL 1 — Superadmin](#5-rol-1--superadmin)
6. [ROL 2 — Admin (dueño del negocio)](#6-rol-2--admin-dueño-del-negocio)
7. [ROL 3 — Cajero (POS)](#7-rol-3--cajero-pos)
8. [Requisitos transversales](#8-requisitos-transversales)
9. [Roadmap por sprints](#9-roadmap-por-sprints)
10. [Checklist de infraestructura y cuentas externas](#10-checklist-de-infraestructura-y-cuentas-externas)
11. [Decisiones pendientes — para el equipo](#11-decisiones-pendientes--para-el-equipo)

---

## 0. Visión del producto

Una plataforma tipo marketplace para **cafeterías y restaurantes pequeños ubicados cerca de
universidades**. El objetivo: que el negocio administre su menú, inventario y ventas con
facilidad, y que el cliente (desde la app móvil) descubra negocios cercanos a su universidad o a
su ubicación, ordene y pague — con retiro en tienda (pickup) o entrega (delivery).

**Analogía rápida:** nosotros somos la plataforma (tipo "Uber Eats" enfocado a zona universitaria),
cada cafetería es un "vendedor" con su propio panel de administración, y el cajero es quien opera
la caja física del negocio.

- [x] Nombre/branding: **se elimina por completo "AMARE"**, rebranding total (decisión tomada 2026-08-25)
- [ ] Confirmar el nombre comercial nuevo de la plataforma
- [ ] Confirmar ciudad(es)/universidad(es) de lanzamiento inicial

---

## 1. Qué se hereda del sistema actual vs qué se elimina

El repo actual (`RESTAURANTEIDEA`, alias interno "CarniHub"/"Jungle Pizza") es un sistema de
**restaurante único con servicio en mesa** (dine-in). El marketplace universitario es
**pickup/delivery únicamente** (igual que el plan ya definido para la app móvil), así que varios
módulos completos del sistema actual no aplican al nuevo modelo.

| Módulo actual | Decisión | Por qué |
|---|---|---|
| `RestPorteroController` + vistas + rutas `_portero` | 🗑️ **Eliminar** | No hay control de entrada/salida física en un modelo pickup/delivery |
| `RestChefController` legado (KDS atado a mesas/mesero) + vistas `chef/` | 🗑️ **Eliminar** (hecho) | Se reemplaza por `RestCocinaController` nuevo (`rest-cocina/`), sin mesas — cocina vive en web **y** en la app móvil (corrección 2026-08-25 al plan original) |
| `RestMeseroController` + vistas `mesero/` | 🗑️ **Eliminar** | No hay servicio en mesa en el marketplace |
| `RestMesaController` (mesas, layout, zonas) | 🗑️ **Eliminar** | No hay mesas físicas que gestionar |
| `RestReservaController` (reservaciones) | 🗑️ **Eliminar** | No aplica a pickup/delivery |
| `RestBarController` (stub de barra) | 🗑️ **Eliminar** | Placeholder sin uso, no aplica |
| `RestModeracionController` (modo social) | 🗑️ **Eliminar por completo** — decisión tomada | Se quita todo el modo social (perfiles, likes, regalos entre mesas). Si más adelante se quiere moderar fotos/reseñas de producto, es un módulo nuevo dentro de Superadmin, no una reutilización de este |
| Todo lo relacionado con "AMARE" (branding, `AmareModifierSyncService.php`, vistas, docs) | 🗑️ **Eliminar por completo** — decisión tomada | Rebranding total del sistema. Revisar `AmareModifierSyncService.php` antes de borrar: puede tener lógica de sincronización de modificadores útil detrás del nombre, no solo texto de marca |
| Autenticación multi-sesión por rol (cookies `_chef`, `_mesero`, etc.) | ♻️ **Simplificar** | Con solo 3 roles web ya no se necesita login simultáneo de tantos roles en el mismo navegador |
| `RestauranteController` (alta de negocios, multi-sucursal) | ✅ **Reusar como base** | Encaja casi directo con "Superadmin gestiona negocios" |
| `RestMenuController`, `RestInventarioController`, `RestFinanzasController` | ✅ **Reusar como base** | Lógica de menú/inventario/finanzas es transferible al rol Admin |
| `RestFinanzasController::cortes/guardarCorte` | ✅ **Reusar como base del corte de caja del Cajero** | Es lo más cercano que existe hoy a un cierre de turno |
| `RestPedidoController` | ✅ **Reusar y adaptar** | Base para la bandeja de pedidos de Admin y para la venta directa del Cajero |
| `RestFacturaController` (CFDI) | ✅ **Reusar** | Facturación fiscal sigue aplicando |
| `ApiController.php` (monolito de ~6700 líneas) | ♻️ **Refactorizar por dominio** | Sigue siendo la API que consume la app móvil — separar en controladores más chicos antes de que crezca más |
| Tablas `rest_visitas`, mesas, reservas en BD | 🗑️ **Eliminar/deprecar** en una migración de recorte análoga a `086_trim_schema_uteq.sql` |
| `amare_wallets` / `amare_wallet_transactions` | ✅ **Reusar** | Útil como saldo/monedero del cliente, también sirve como método de pago en el Cajero |

- [ ] Aprobar esta tabla con el equipo antes de tocar código (marcar sí/no por fila)
- [ ] Confirmar el archivo `Indexphp` (sin extensión) en la raíz — verificar si en el servidor real es `index.php` o si hay que corregirlo antes de refactorizar
- [ ] Revisar carpeta `base/redesign-assets/` — ya existe un intento de rediseño previo, revisar antes de proponer un sistema visual nuevo desde cero

---

## 2. Arquitectura y stack

- [ ] **Backend:** PHP puro (MVC ya existente: `app/controllers`, `app/models`, `app/views`), sin frameworks pesados — compatible con cPanel/shared hosting
- [ ] **Base de datos:** MySQL vía PDO (ya existente, `config/database.php`, prepared statements)
- [ ] **Frontend:** HTML + CSS + JavaScript vanilla (o librería ligera tipo Alpine.js si se necesita reactividad sin build step — evaluar, nada que requiera Node/Webpack en producción)
- [ ] **Diseño:** sistema de diseño nuevo y consistente entre los 3 roles (ver sección 8) — variables CSS reusando el patrón `--cp`/`--cs` (branding dinámico) ya presente en `restaurant.css`
- [ ] **Multi-tenant:** ya existe la jerarquía `empresas → rest_restaurantes` — el marketplace la reusa: cada negocio es un `rest_restaurantes` con dueño = usuario `admin`
- [ ] **Geolocalización:** los negocios ya tienen `lat`/`lng` en `rest_restaurantes` — falta:
  - [ ] Tabla nueva `universidades` (nombre, lat, lng, radio_km, ciudad, activo)
  - [ ] Relación negocio↔universidad(es) cercanas (calculada o manual)
  - [ ] Fórmula de distancia (Haversine en SQL o PHP) para "negocios cerca de mí" / "cerca de tu universidad" — sin depender de librerías externas de pago
- [ ] **API para app móvil:** mantener contrato de `ApiController.php`, pero refactorizado — ver sección 8
- [ ] **Tiempo real (pedidos nuevos en POS/Admin):** cPanel compartido normalmente no soporta websockets persistentes — opciones a evaluar (ver sección 11):
  - [ ] Firebase Realtime DB / Firestore (ya hay carpeta `firebase/` placeholder en el repo — probablemente para push a la app móvil, se puede extender)
  - [ ] Servicio externo tipo Pusher/Ably (capa gratuita)
  - [ ] Polling simple (fetch cada 5-10s) como solución de arranque, sin dependencias nuevas
- [ ] **Pagos:** Stripe ya integrado (modo prueba en el legado) — decidir modelo de cobro de comisión (ver sección 11: Stripe Connect vs cobro manual)
- [ ] **Despliegue:** cPanel — sin acceso shell garantizado, así que evitar dependencias que requieran `composer install` en producción sin poder correrlo (vendorizar si es necesario, como ya se hace con Chart.js/jsPDF en `public/js/lib/`)

---

## 3. Modelo de datos (alto nivel)

Reusar lo que ya existe en `idactivo_cafeteq.sql` y extender:

**Ya existe y se reusa tal cual:**
- `empresas`, `rest_restaurantes` (negocios, con `lat`/`lng`, colores de marca, horarios)
- `usuarios`, `roles` (base de autenticación — se recorta a solo `superadmin` y `admin_restaurante`, más el nuevo `cajero`)
- `rest_categorias_menu`, `rest_platillos`, `rest_modificadores`, `rest_platillo_modificadores` (menú)
- `rest_ingredientes`, `rest_recetas`, `rest_receta_ingredientes`, `rest_movimientos_inventario` (inventario)
- `rest_pedidos`, `rest_pedido_items`, `rest_pedido_item_modificadores` (pedidos)
- `rest_gastos`, `rest_retiros`, `rest_configuracion` (finanzas/config)
- `rest_promociones`, `mobile_promociones` (promociones)
- `amare_wallets`, `amare_wallet_transactions` (monedero del cliente)
- `facturacion_solicitudes`, `mobile_datos_fiscales` (CFDI)
- `action_logs`, `login_intentos`, `api_rate_limits` (auditoría/seguridad)

**Nuevo — a diseñar:**
- [ ] `universidades` (id, nombre, lat, lng, radio_km, ciudad, activo)
- [ ] `rest_universidades` (tabla puente negocio↔universidad, o calcular por distancia en query)
- [ ] `cajeros` o extender `usuarios`/`rest_staff` con rol `cajero` + PIN corto de acceso
- [ ] `turnos_caja` (apertura/cierre de turno: cajero_id, restaurante_id, fondo_inicial, efectivo_contado, diferencia, abierto_at, cerrado_at)
- [ ] `ventas_pos` o reusar `rest_pedidos` con `origen='pos'` + `tipo_pedido='mostrador'`
- [ ] `planes_negocio` (id, nombre, comisión_%, cuota_mensual, beneficios) — monetización
- [ ] `negocio_suscripciones` o `negocio_comisiones_historial` (cobros de la plataforma a cada negocio)
- [ ] `promos_destacadas` (negocio paga por aparecer primero en listados — si aplica al modelo de negocio)
- [ ] `resenas` (calificación de cliente hacia negocio/producto) si no existe ya algo equivalente
- [ ] `soporte_tickets` (tickets de soporte que Superadmin atiende)

- [ ] Diseñar migración de recorte (`0XX_trim_schema_web.sql`) análoga a `086_trim_schema_uteq.sql`, sobre una **copia** de la base, nunca en producción directo

---

## 4. Matriz de roles y permisos

| Acción | Superadmin | Admin | Cajero |
|---|:---:|:---:|:---:|
| Alta/baja de negocios | ✅ | ❌ | ❌ |
| Configurar comisiones/planes | ✅ | ❌ | ❌ |
| Ver analítica global de la plataforma | ✅ | ❌ | ❌ |
| Moderar contenido (fotos/reseñas) | ✅ | 🔶 solo su negocio | ❌ |
| Editar menú/inventario de su negocio | ❌ (solo lectura si necesita soporte) | ✅ | ❌ |
| Configurar su negocio (horario, ubicación, pagos) | ❌ | ✅ | ❌ |
| Dar de alta cajeros de su negocio | ❌ | ✅ | ❌ |
| Ver finanzas consolidadas de su negocio | ❌ | ✅ | 🔶 solo su turno |
| Abrir/cerrar turno de caja | ❌ | 🔶 puede forzar cierre | ✅ |
| Cobrar venta / usar POS | ❌ | 🔶 puede si no hay cajero | ✅ |
| Ver pedidos entrantes de app móvil | 🔶 soporte | ✅ | ✅ (cola de cobro) |
| Aplicar descuento manual en venta | ❌ | ✅ | 🔶 con límite configurable |

- [ ] Validar esta matriz con el equipo, especialmente las columnas 🔶 (permisos condicionales)
- [ ] Decidir si "Admin" puede tener sub-roles (ej. gerente vs dueño) — fuera de alcance v1 salvo que se pida

---

## 5. ROL 1 — Superadmin

### 5.1 Gestión de negocios
- [ ] Bandeja de solicitudes de alta de negocio (aprobar / rechazar / pedir más info)
- [ ] Listado de todos los negocios con filtros (activo/suspendido, ciudad, universidad cercana, plan)
- [ ] Detalle de negocio: datos generales, dueño, ventas históricas, estado de cuenta con la plataforma
- [ ] Activar / suspender / dar de baja un negocio (con motivo, notifica al admin)
- [ ] Editar datos de un negocio en caso de soporte (branding, ubicación, horarios)
- [ ] Impersonar/entrar como Admin de un negocio para dar soporte (ya existe patrón similar en `BaseController::esSuperAdmin()`)

### 5.2 Universidades y zonas
- [ ] CRUD de universidades (nombre, ubicación, radio de cobertura, ciudad)
- [ ] Asociar negocios a universidades cercanas (automático por distancia + override manual)
- [ ] Mapa visual de negocios y universidades (para revisar cobertura de zonas)

### 5.3 Monetización
- [ ] CRUD de planes (comisión % por venta, o cuota fija mensual, o híbrido)
- [ ] Asignar plan a cada negocio
- [ ] Historial de cobros/comisiones por negocio (cuánto debe/pagó cada uno)
- [ ] Gestión de "promos destacadas" pagadas (negocio paga por salir primero en resultados cercanos)
- [ ] Reporte de ingresos de la plataforma (comisiones + suscripciones + destacados)

### 5.4 Moderación y soporte
- [ ] Cola de moderación de fotos de productos (aprobar/rechazar antes de publicar, si aplica)
- [ ] Cola de reportes/reseñas de clientes (contenido inapropiado, disputas)
- [ ] Sistema de tickets de soporte (de negocios y/o de clientes vía app)
- [ ] Gestión de reembolsos/disputas de pago (ligado a Stripe)

### 5.5 Usuarios y accesos
- [ ] Crear/editar/desactivar cuentas Admin de cada negocio
- [ ] Reset de acceso (forzar cambio de contraseña, generar acceso temporal)
- [ ] Ver bitácora de acciones por usuario (`action_logs` ya existe)

### 5.6 Configuración global de la plataforma
- [ ] Categorías de negocio disponibles (cafetería, taquería, comida rápida, etc.)
- [ ] Métodos de pago habilitados a nivel plataforma
- [ ] Textos legales (términos, privacidad) — ya existe `LegalController`
- [ ] Parámetros generales (radio de búsqueda por defecto, mínimo de pedido, etc.)

### 5.7 Analítica global
- [ ] Dashboard: ventas totales, # pedidos, # negocios activos, # usuarios app móvil, ticket promedio global
- [ ] Ranking de negocios más vendedores / mejor calificados
- [ ] Mapa de calor de zonas con más actividad
- [ ] Comparativo de periodos (semana/mes/trimestre)
- [ ] Exportables (PDF/Excel — reusar `jspdf` ya vendored en `public/js/lib/`)

### 5.8 Marketing / comunicación
- [ ] Envío de notificaciones push masivas a usuarios de la app (segmentado por universidad/zona)
- [ ] Gestión de contenido de la landing pública (banners, destacados de home)

---

## 6. ROL 2 — Admin (dueño del negocio)

### 6.1 Dashboard
- [ ] KPIs del día/semana/mes: ventas, # pedidos, ticket promedio, producto más vendido
- [ ] Gráfica de ventas por periodo (reusar Chart.js ya vendored)
- [ ] Comparativo vs periodo anterior
- [ ] Alertas visibles: stock bajo, pedidos pendientes sin atender

### 6.2 Menú
- [ ] CRUD de categorías del menú
- [ ] CRUD de productos/platillos (nombre, descripción, precio, foto, tiempo de preparación)
- [ ] Disponibilidad on/off por producto (agotado del día sin borrarlo)
- [ ] Modificadores/extras (obligatorios/opcionales, precio adicional)
- [ ] Combos o productos armados (ya existe `rest_platillo_armado` en BD)
- [ ] Orden de aparición en el menú (drag & drop o campo `orden`)

### 6.3 Inventario
- [ ] CRUD de ingredientes/insumos
- [ ] Recetas: qué ingredientes y cantidades usa cada producto
- [ ] Registro de movimientos (entradas, salidas, mermas)
- [ ] Alertas de stock mínimo
- [ ] Costeo de producto (costo real vs precio de venta, margen)
- [ ] Historial de mermas con motivo

### 6.4 Pedidos
- [ ] Bandeja de pedidos entrantes (pickup/delivery) con estado en tiempo real (o polling)
- [ ] Cambiar estado del pedido (recibido → en preparación → listo → entregado/cancelado)
- [ ] Historial completo con filtros (fecha, estado, tipo de entrega, cliente)
- [ ] Detalle de pedido (productos, modificadores, notas del cliente, método de pago)
- [ ] Tiempo estimado de preparación configurable por producto/negocio

### 6.5 Configuración del negocio
- [ ] Datos generales (nombre, descripción, logo, banner, categoría de negocio)
- [ ] Colores de marca (branding dinámico — ya existe el patrón `--cp`/`--cs` en CSS)
- [ ] Ubicación (dirección + lat/lng, mapa para ajustar el pin)
- [ ] Horarios de atención por día de la semana
- [ ] Zona(s) de entrega y costo de envío (si ofrece delivery)
- [ ] Pedido mínimo
- [ ] Métodos de pago aceptados (efectivo, tarjeta, wallet)
- [ ] Datos fiscales para facturación CFDI

### 6.6 Personal
- [ ] Dar de alta cajeros (nombre, PIN de acceso, activo/inactivo)
- [ ] Ver actividad/turnos de cada cajero (auditoría de aperturas/cierres de caja)
- [ ] Desactivar acceso de un cajero (ej. termina relación laboral)

### 6.7 Finanzas
- [ ] Reporte de ventas (por día, por método de pago, por producto)
- [ ] Registro de gastos y retiros (ya existe `RestFinanzasController`)
- [ ] Consolidado de cortes de caja de todos los cajeros/turnos
- [ ] Estado de cuenta con la plataforma (comisión que debe/pagó — alimentado por Superadmin §5.3)
- [ ] Exportables (PDF/Excel)

### 6.8 Promociones
- [ ] CRUD de promociones/cupones (%, monto fijo, envío gratis)
- [ ] Vigencia (fecha inicio/fin), alcance (todo el menú o productos específicos)
- [ ] Límite de usos, mínimo de compra
- [ ] Ver desempeño de cada promoción (cuántas veces se usó)

### 6.9 Clientes (CRM ligero)
- [ ] Listado de clientes que han comprado en el negocio
- [ ] Historial de compras por cliente
- [ ] Identificar clientes frecuentes / top consumo
- [ ] Enviar promoción dirigida a un cliente o grupo

### 6.10 Facturación
- [ ] Ver solicitudes de facturación CFDI de sus pedidos
- [ ] Estado de cada factura (pendiente, timbrada, error)
- [ ] Descarga de PDF/XML (ya integrado vía FacturAPI en el legado)

### 6.11 Reseñas (si aplica al modelo)
- [ ] Ver calificaciones y comentarios de clientes
- [ ] Responder a una reseña públicamente

---

## 7. ROL 3 — Cajero (POS)

### 7.1 Acceso
- [ ] Login rápido por PIN corto (no email/password completo) — pensado para uso repetido en el día
- [ ] Selección de negocio/sucursal si el usuario tiene acceso a más de una
- [ ] Bloqueo de pantalla rápido (volver a pedir PIN sin cerrar sesión completa)

### 7.2 Turno de caja
- [ ] Apertura de turno: captura de fondo inicial en efectivo, hora, cajero
- [ ] No permitir vender sin turno abierto
- [ ] Cierre de turno: efectivo esperado (calculado) vs efectivo contado, diferencia, resumen por método de pago, resumen de propinas
- [ ] Reporte de cierre imprimible/descargable
- [ ] Historial de turnos anteriores (solo lectura)

### 7.3 Venta / cobro
- [ ] Catálogo de productos por categoría con búsqueda rápida (mismo catálogo que gestiona Admin en §6.2)
- [ ] Carrito: agregar/quitar producto, cantidad, modificadores/extras, nota
- [ ] Aplicar descuento manual (con límite configurable por Admin; más allá del límite requiere autorización)
- [ ] Aplicar cupón/promoción vigente
- [ ] Cálculo automático de subtotal, impuestos si aplica, propina, total
- [ ] Métodos de pago: efectivo (con cálculo de cambio), tarjeta (registro manual o terminal Stripe), pago mixto, saldo del wallet del cliente (`amare_wallets`)
- [ ] Confirmar venta → genera ticket

### 7.4 Pedidos de la app móvil en caja
- [ ] Cola de pedidos entrantes de pickup/delivery pendientes de cobrar/confirmar
- [ ] Alerta sonora/visual cuando llega un pedido nuevo
- [ ] Buscar pedido por folio/nombre de cliente
- [ ] Marcar como cobrado/entregado

### 7.5 Ticket e impresión
- [ ] Generar ticket en formato térmico (58mm/80mm)
- [ ] Reimprimir ticket de una venta anterior
- [ ] Puente de impresión local (ver §8 — propuesta QZ Tray) para que el navegador hable con la impresora térmica/cajón de dinero

### 7.6 Cancelaciones
- [ ] Cancelar/anular una venta antes de cerrar turno (con motivo obligatorio)
- [ ] Requiere autorización de Admin si ya se imprimió el ticket

### 7.7 Experiencia de uso
- [ ] Diseño táctil, pensado para tablet/pantalla touch además de PC con mouse/teclado
- [ ] Atajos de teclado para agilizar cobro (buscar producto, cobrar, cancelar)
- [ ] Instalable como PWA (ícono, pantalla completa, se siente "app de escritorio" — ver conversación previa)

### 7.8 Fase futura — modo offline (NO en v1, dejar la idea documentada)
- [ ] Guardar ventas localmente (IndexedDB/localStorage o app local con SQLite) cuando no hay internet
- [ ] Cola de sincronización automática al recuperar conexión
- [ ] Indicador visual de "sin conexión, ventas en espera de sincronizar"
- [ ] Evaluar en su momento si se resuelve con Service Worker (PWA) o requiere una capa nativa tipo Electron

---

## 8. Requisitos transversales

### 8.1 Seguridad
- [ ] Contraseñas con hash (ya usa bcrypt en el legado — mantener)
- [ ] PIN de cajero con intentos limitados (reusar patrón de `login_intentos`)
- [ ] Protección CSRF en formularios
- [ ] Rate limiting de login/API (ya existen tablas `login_intentos`, `api_rate_limits` — reusar)
- [ ] Sesiones seguras (cookie `httponly`, `secure` en HTTPS, expiración razonable)
- [ ] Sanitización/prepared statements en toda consulta (ya es el patrón actual con PDO — mantener)
- [ ] Auditoría de acciones sensibles por rol (`action_logs` ya existe)
- [ ] Forzar HTTPS en producción (cPanel + certificado SSL)

### 8.2 Pagos
- [ ] Definir modelo de comisión (ver §11 — Stripe Connect vs manual)
- [ ] Checkout con Stripe ya probado en modo prueba (legado) — pasar a modo live antes de lanzar
- [ ] Conciliación: cómo se concilian pagos con tarjeta cobrados en el POS físico
- [ ] Manejo de reembolsos (parcial/total) desde Superadmin o Admin según corresponda

### 8.3 Tiempo real / notificaciones
- [ ] Definir mecanismo (Firebase / Pusher / polling — ver §11)
- [ ] Notificación de pedido nuevo → Admin y Cajero
- [ ] Notificación de stock bajo → Admin
- [ ] Notificación de negocio suspendido/aprobado → Admin (desde Superadmin)

### 8.4 Diseño / UI
- [ ] Revisar `base/redesign-assets/` (ya existe intento previo) antes de proponer sistema nuevo
- [ ] Sistema de diseño único compartido por los 3 roles (tipografía, espaciados, componentes: tablas, tarjetas, modales, formularios)
- [ ] Branding dinámico por negocio para las vistas de Admin/Cajero (reusar patrón `--cp`/`--cs`), Superadmin usa marca propia de la plataforma
- [ ] Responsive: Superadmin/Admin pensados para escritorio principalmente; Cajero pensado para tablet/touch
- [ ] Estados vacíos, de carga y de error cuidados en cada pantalla (no dejar pantallas en blanco)

### 8.5 Rendimiento
- [ ] Paginación en listados grandes (pedidos, clientes, negocios)
- [ ] Índices de BD ya existen en varias tablas clave — revisar cobertura para las queries nuevas de geolocalización
- [ ] Cache simple de catálogo de menú (poco cambia durante el día)

### 8.6 API para app móvil
- [ ] Mantener compatibilidad de contrato mientras se refactoriza `ApiController.php`
- [ ] Separar el monolito en controladores por dominio (pedidos, productos, promociones, auth, negocios/branches) para facilitar mantenimiento
- [ ] Documentar endpoints usados por: descubrimiento de negocios cercanos, detalle de negocio, menú, crear pedido, estado de pedido, pago

### 8.7 Despliegue (cPanel)
- [ ] Confirmar si hay acceso SSH/Composer en el hosting o si hay que vendorizar dependencias
- [ ] `config/database.php` fuera de git (ya es la práctica actual) — documentar proceso de despliegue de config sensible
- [ ] Cron jobs vía cPanel para tareas programadas (cierre automático de turnos olvidados, limpieza de rate limits, recordatorios)
- [ ] Ambiente de staging antes de producción, si el hosting lo permite

### 8.8 QA / Testing
- [ ] Checklist manual de pruebas por rol antes de cada release (flujo completo: alta de negocio → configurar menú → recibir pedido → cobrar en POS → cerrar turno → ver reporte en Admin → ver comisión en Superadmin)
- [ ] Pruebas de permisos cruzados (que un Cajero no pueda acceder a rutas de Admin, etc.)

---

## 9. Roadmap por sprints

### Sprint 0 — Infraestructura y decisiones
- [ ] Resolver decisiones pendientes de la §11 (comisión, tiempo real, alcance de "mesero/portero")
- [ ] Confirmar `Indexphp` vs `index.php` en producción
- [ ] Diseñar y aplicar migración de recorte de BD (`0XX_trim_schema_web.sql`) sobre copia de la base
- [ ] Definir sistema de diseño (revisar `base/redesign-assets/`)

### Sprint 1 — Limpieza del sistema actual
- [ ] Eliminar módulos de portero, mesero, mesas, reservaciones, barra (según tabla §1)
- [ ] Quitar cocina/chef del web (controlador + vistas) — queda solo en móvil
- [ ] **Eliminar modo social por completo** (`RestModeracionController` + vistas + rutas + tablas relacionadas)
- [ ] **Eliminar todo lo relacionado con "AMARE"** — lista base de archivos detectados a revisar uno por uno:
  - [x] `app/services/AmareModifierSyncService.php` — **HECHO 2026-08-25**: renombrado a `app/services/ModifierSyncService.php` (clase `ModifierSyncService`). Confirmado: sí tenía lógica real (materializa modificadores para la app móvil, comparten BD) — se conservó, solo se quitó el nombre. Call sites actualizados en `ApiController.php` (2), `RestConfigController.php` (1), `RestMenuController.php` (2, incluyendo el método `syncModificadoresAmare` → `syncModificadoresApp`)
  - [ ] Resto de menciones de "AMARE" (217 en 19 archivos, sobre todo texto/branding): **se limpian incrementalmente**, archivo por archivo, conforme cada rol (Admin/Superadmin) trabaja esa sección — no en un barrido único. Concentración principal en `RestConfigController.php` (28), `app/views/restaurante/config/index.php` (43), `app/views/restaurante/dashboard.php` (20), `ApiController.php` (50, resto), `RestClienteController.php` (11), `RestFinanzasModel.php` (11), `EmailService.php` (15). Lo que vive fuera de Admin (landing `Indexphp`, `cron/recordatorio_cumple.php` con deep link `amare://` y notificaciones push, `firebase/amare-service-account.json`) se coordina con Superadmin/marketing — **no cambiar el deep link `amare://` sin avisar al equipo de la app móvil**, puede seguir en uso ahí
  - [ ] `public/js/api-client.js`
  - [ ] `migrations/__index_restore.php`, `migrations/069_modificadores_app.sql`
  - [ ] `cron/recordatorio_cumple.php`
  - [ ] `docs/amare_modificadores_api.md`, `docs/plan_api_restaurante_modificadores.md`
  - [ ] `app/views/restaurante/finanzas/dashboard.php`, `app/views/restaurante/dashboard.php`, `app/views/restaurante/config/index.php`
  - [ ] `app/models/RestauranteModel.php`, `app/models/RestClienteModel.php`, `app/models/RestFinanzasModel.php`
  - [ ] `app/services/EmailService.php`
  - [ ] `app/controllers/RestauranteController.php`, `RestPromocionController.php`, `RestMenuController.php`, `RestClienteController.php`, `RestConfigController.php`, `ApiController.php`
  - [ ] `Indexphp` (front controller)
  - [ ] Definir y aplicar el nombre/branding nuevo en todos los puntos anteriores
- [ ] Simplificar autenticación multi-sesión a 3 roles

### Sprint 2 — Base de Superadmin
- [ ] CRUD de negocios (§5.1)
- [ ] CRUD de universidades/zonas (§5.2)
- [ ] Panel de usuarios/accesos (§5.5)

### Sprint 3 — Base de Admin
- [ ] Menú (§6.2), inventario (§6.3), configuración del negocio (§6.5)
- [ ] Bandeja de pedidos (§6.4)

### Sprint 4 — Cajero / POS (módulo nuevo desde cero)
- [ ] Login por PIN + apertura/cierre de turno (§7.1, §7.2)
- [ ] Pantalla de venta (§7.3)
- [ ] Impresión de tickets + puente local (§7.5)

### Sprint 5 — Geolocalización + monetización
- [ ] "Negocios cerca de mí / cerca de mi universidad" (backend + API para móvil)
- [ ] Planes, comisiones, estado de cuenta (§5.3, §6.7)

### Sprint 6 — Analítica y reportes
- [ ] Dashboards de Superadmin (§5.7) y Admin (§6.1)
- [ ] Exportables PDF/Excel

### Sprint 7 — Integración final y QA
- [ ] Refactor de `ApiController.php` por dominio
- [ ] Pruebas integrales de los 3 roles
- [ ] Despliegue a cPanel producción

---

## 10. Checklist de infraestructura y cuentas externas

- [ ] Hosting cPanel confirmado (plan, límites de recursos, si tiene SSH/cron/SSL)
- [ ] Dominio(s) para superadmin / admin / cajero (¿subdominios distintos o rutas dentro del mismo dominio?)
- [ ] Certificado SSL activo
- [ ] Cuenta Stripe en modo live (hoy está en modo prueba)
- [ ] Decisión y cuenta de servicio de tiempo real (Firebase / Pusher / ninguno por ahora)
- [ ] Cuenta FacturAPI para timbrado CFDI (ya integrado en el legado, confirmar si sigue vigente)
- [ ] Servicio de impresión local (QZ Tray u otro) documentado para instalación en cada negocio

---

## 11. Decisiones pendientes — para el equipo

> Ver también el resumen al inicio del documento (sección "⚠️ LEER PRIMERO"). Las decisiones
> de modo social y branding "AMARE" ya están **resueltas** (ambas: eliminación total) — se dejan
> tachadas aquí como registro histórico, el resto sigue abierto.

- [ ] **Modelo de comisión:** ¿% por transacción, cuota mensual fija, o híbrido?
- [ ] **Cómo fluye el dinero:** ¿Stripe Connect (el dinero llega directo al negocio, la plataforma retiene comisión automática) o todo pasa por nuestra cuenta y se liquida manualmente a cada negocio? (Stripe Connect es más escalable pero más complejo de integrar)
- [ ] **Tiempo real para pedidos nuevos:** ¿Firebase (ya hay carpeta placeholder en el repo), Pusher/Ably, o polling simple para arrancar?
- ~~[ ] **Alcance de "modo social"/reseñas:** ¿se elimina por completo o se conserva una versión simple?~~ → **RESUELTO 2026-08-25: se elimina por completo, sin excepciones.**
- [ ] **Multi-sucursal por negocio:** ¿un negocio puede tener más de un local desde el día 1, o se lanza con un local = un negocio y se agrega multi-sucursal después? (la BD ya lo soporta vía `empresas`)
- [ ] **Cajero sin negocio con caja física:** si un negocio no quiere usar el módulo Cajero (solo recibe pedidos de la app y el Admin los marca listo), ¿el POS es obligatorio u opcional por negocio?
- ~~[ ] **Nombre comercial** de la plataforma y si se conserva algo del branding "AMARE" del legado~~ → **RESUELTO 2026-08-25: se elimina todo lo relacionado con "AMARE", rebranding total.**
