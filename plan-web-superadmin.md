# Plan Detallado — Rol Superadmin (plataforma)

**Creado:** 2026-08-25 | **Depende de:** `plan-web-marketplace.md` (visión general)

> Este documento baja a nivel de campo de base de datos y pantalla el panel de Superadmin —
> el dueño de la plataforma (nosotros). Controla negocios, universidades/zonas, monetización,
> moderación y analítica global.

---

## 1. Objetivo del rol

Superadmin no opera un negocio, **opera el marketplace**: aprueba/suspende negocios, define
cómo se cobra la comisión, gestiona la cobertura geográfica (universidades/zonas), modera
contenido y da soporte. Es el único rol con visión de **todos** los negocios a la vez.

---

## 2. Tablas existentes que se reusan tal cual

| Tabla | Para qué |
|---|---|
| `empresas` | Entidad legal dueña de uno o más `rest_restaurantes` (ya soporta multi-sucursal) |
| `rest_restaurantes` | Listado global de negocios (todos, sin filtrar por dueño) |
| `usuarios`, `roles` | Gestión de cuentas Admin/Cajero de cada negocio |
| `action_logs` | Auditoría global de acciones (usuario_id, rol, empresa_id, accion, modulo, descripcion, ip) — sirve tal cual para loguear impersonación de Superadmin |
| `login_intentos`, `api_rate_limits` | Seguridad de la plataforma |
| `global_settings` | Configuración global clave-valor (ya existe: colores de app, métodos de pago habilitados, costo de envío por defecto — reusar y extender) |

> **Modo social: eliminado por completo (2026-08-26).** `RestModeracionController` y sus vistas
> se borraron en el Sprint 1; el 2026-08-26 se remató el resto: `RestSocialModeracionModel`
> (990 líneas de código muerto que consultaba tablas ya inexistentes), las rutas API
> `/admin/social/*` en `ApiController`, su routing en `index.php`, el método de pago
> `social_cover` del dashboard financiero, y la tabla huérfana `moderation_actions`
> (`migrations/005_eliminar_modo_social.sql`). **No queda nada de social que reusar.** Si
> Superadmin llega a necesitar moderación de fotos/reseñas de producto (§5.4 de
> `plan-web-marketplace.md`), es un módulo nuevo con su propia tabla de cola.

---

## 3. Tablas y columnas NUEVAS necesarias

### 3.1 `puntos_referencia` (tabla nueva — antes "universidades")

> Nombre generalizado a propósito (corrección 2026-08-26): el primer caso de uso es UTEQ
> (universidad), pero la tabla no debe quedar amarrada a ese concepto — mañana puede ser un
> hospital, una plaza, un fraccionamiento. Configurable **solo por Superadmin**, igual que antes.
> El texto visible en pantalla puede seguir diciendo "Universidad" si el negocio lo pide; el
> esquema/código usan el nombre genérico.

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `int(10) UNSIGNED AUTO_INCREMENT PK` | |
| `nombre` | `varchar(200) NOT NULL` | |
| `ciudad` | `varchar(100) DEFAULT NULL` | |
| `lat` | `decimal(10,8) NOT NULL` | |
| `lng` | `decimal(11,8) NOT NULL` | |
| `radio_km` | `decimal(5,2) NOT NULL DEFAULT 2.00` | Radio de cobertura considerado "cerca" |
| `activo` | `tinyint(1) NOT NULL DEFAULT 1` | |
| `created_at` | `timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP` | |

### 3.2 `rest_puntos_referencia` (tabla puente — negocio ↔ punto de referencia)

| Columna | Tipo | Notas |
|---|---|---|
| `restaurante_id` | `int(10) UNSIGNED NOT NULL` | |
| `punto_referencia_id` | `int(10) UNSIGNED NOT NULL` | |
| `distancia_km` | `decimal(6,2) DEFAULT NULL` | Calculada automáticamente (Haversine) al guardar/editar ubicación del negocio |
| `destacado_manual` | `tinyint(1) NOT NULL DEFAULT 0` | Override manual de Superadmin si quiere forzar la asociación |

- PK compuesta `(restaurante_id, punto_referencia_id)`
- [ ] Job/trigger que recalcula esta tabla cuando cambia `lat`/`lng` de un negocio o se agrega un punto de referencia nuevo

### 3.3 `planes_negocio` (tabla nueva)

> ⚠️ El modelo de comisión (% / cuota fija / híbrido) sigue **pendiente de decisión del equipo**
> (`plan-web-marketplace.md` §11). La tabla trae ambas columnas de precio para no bloquear el
> resto de Sprint 2 (negocios/universidades/usuarios no dependen de esto), pero el plan "Básico"
> se siembra en 0.00/0.00 — no usar estos valores como si el modelo ya estuviera decidido.

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `int(10) UNSIGNED AUTO_INCREMENT PK` | |
| `nombre` | `varchar(80) NOT NULL` | Ej. "Básico", "Premium" |
| `comision_pct` | `decimal(5,2) NOT NULL DEFAULT 0.00` | % por venta |
| `cuota_mensual` | `decimal(10,2) NOT NULL DEFAULT 0.00` | Si el modelo es híbrido/fijo |
| `beneficios_json` | `text` | Lista de beneficios (aparece en búsqueda destacada, soporte prioritario, etc.) |
| `activo` | `tinyint(1) NOT NULL DEFAULT 1` | |

- [ ] `rest_restaurantes.plan_id` (definida en `plan-web-admin.md` §3.1) referencia esta tabla

### 3.4 `rest_negocio_comisiones` (tabla nueva)
*(ya especificada en `plan-web-admin.md` §3.4 — Superadmin es quien la escribe/calcula, Admin solo la lee)*

- [ ] Job mensual (cron cPanel) que genera el registro del periodo a partir de `SUM(rest_pedidos.total)` por negocio × `comision_pct` del plan vigente

### 3.5 `promos_destacadas` (tabla nueva)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `int(10) UNSIGNED AUTO_INCREMENT PK` | |
| `restaurante_id` | `int(10) UNSIGNED NOT NULL` | |
| `punto_referencia_id` | `int(10) UNSIGNED DEFAULT NULL` | FK a `puntos_referencia` — NULL = destacado general, o limitado a un punto/zona |
| `fecha_inicio` | `date NOT NULL` | |
| `fecha_fin` | `date NOT NULL` | |
| `monto_pagado` | `decimal(10,2) NOT NULL DEFAULT 0.00` | |
| `estado` | `enum('activo','vencido','cancelado') NOT NULL DEFAULT 'activo'` | |

### 3.6 `soporte_tickets` (tabla nueva)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `bigint(20) UNSIGNED AUTO_INCREMENT PK` | |
| `origen` | `enum('negocio','cliente_app') NOT NULL` | |
| `restaurante_id` | `int(10) UNSIGNED DEFAULT NULL` | Si aplica |
| `mobile_usuario_id` | `int(10) UNSIGNED DEFAULT NULL` | Si aplica |
| `asunto` | `varchar(200) NOT NULL` | |
| `descripcion` | `text` | |
| `estado` | `enum('abierto','en_proceso','resuelto','cerrado') NOT NULL DEFAULT 'abierto'` | |
| `atendido_por_id` | `int(10) UNSIGNED DEFAULT NULL` | Usuario superadmin que lo atiende |
| `created_at` | `datetime NOT NULL DEFAULT CURRENT_TIMESTAMP` | |
| `resuelto_at` | `datetime DEFAULT NULL` | |

### 3.7 `rest_categorias_negocio` (tabla nueva — catálogo de tipos de negocio)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `tinyint(3) UNSIGNED AUTO_INCREMENT PK` | |
| `nombre` | `varchar(80) NOT NULL` | Ej. "Cafetería", "Taquería", "Comida rápida" |
| `icono` | `varchar(100) DEFAULT NULL` | |
| `activo` | `tinyint(1) NOT NULL DEFAULT 1` | |

- [ ] `rest_restaurantes` necesita columna `categoria_negocio_id` (FK a esta tabla) — hoy `empresas.tipo_negocio` es un `enum` fijo (`taqueria/carniceria/restaurante/comedor/otro`), migrar a esta tabla catálogo para poder agregar categorías sin tocar el esquema

---

## 4. Pantallas (vistas) a construir

- [ ] `superadmin/dashboard.php` — KPIs globales
- [ ] `superadmin/negocios/index.php`, `superadmin/negocios/solicitudes.php`, `superadmin/negocios/detalle.php`
- [ ] `superadmin/puntos-referencia/index.php`, `superadmin/puntos-referencia/form.php`, `superadmin/puntos-referencia/mapa.php` — la etiqueta visible en pantalla puede decir "Universidades" mientras el único caso real sea UTEQ
- [ ] `superadmin/planes/index.php`, `superadmin/planes/form.php`
- [ ] `superadmin/comisiones/index.php` — estado de cuenta por negocio, marcar pagado
- [ ] `superadmin/destacados/index.php`, `superadmin/destacados/form.php`
- [ ] `superadmin/moderacion/index.php` — módulo nuevo con tabla de cola propia (ver nota de §2: no hay nada de social que reusar)
- [ ] `superadmin/soporte/index.php`, `superadmin/soporte/detalle.php`
- [ ] `superadmin/usuarios/index.php` — cuentas Admin/Cajero de todos los negocios
- [ ] `superadmin/config/categorias.php`, `superadmin/config/global.php` (edita `global_settings`)
- [ ] `superadmin/reportes/index.php` — analítica global, exportables
- [ ] `superadmin/marketing/notificaciones.php` — push masivo segmentado

---

## 5. Reportes / analítica — de dónde sale cada dato

| Reporte | Fuente de datos |
|---|---|
| Ventas totales de la plataforma | `SUM(rest_pedidos.total)` sin filtrar por negocio |
| Ingresos de la plataforma (comisiones) | `SUM(rest_negocio_comisiones.monto_comision)` |
| Negocios activos | `COUNT(rest_restaurantes) WHERE estado_plataforma='activo'` |
| Ranking de negocios | `SUM(rest_pedidos.total) GROUP BY restaurante_id ORDER BY DESC` |
| Cobertura por punto de referencia | `COUNT(rest_puntos_referencia) GROUP BY punto_referencia_id` |
| Zonas más activas | `rest_pedidos` cruzado con `rest_puntos_referencia` vía `restaurante_id` |
| Usuarios app móvil activos | `mobile_usuarios` con `activo=1` y actividad reciente en `rest_pedidos.mobile_usuario_id` |

---

## 6. Reglas de negocio clave

- [ ] Solo Superadmin puede cambiar `rest_restaurantes.estado_plataforma`
- [ ] Al aprobar un negocio nuevo (`pendiente → activo`), el sistema:
  - [ ] Crea la cuenta Admin inicial (o envía invitación)
  - [ ] Asigna `plan_id` por defecto
  - [ ] Recalcula `rest_puntos_referencia` según su `lat`/`lng`
- [ ] Al suspender un negocio, deja de aparecer en la búsqueda de la app móvil pero **no se borran sus datos**
- [ ] Cálculo de comisión corre por cron mensual, no en tiempo real (evita inconsistencias si hay reembolsos a mitad de mes)
- [ ] Superadmin puede impersonar a un Admin para soporte, pero esa acción **debe quedar registrada** en `action_logs`

---

## 7. Checklist de implementación (Sprint 2 — núcleo)

- [x] Migración SQL de `puntos_referencia`, `rest_puntos_referencia` (nombre genérico, antes "universidades" — corrección 2026-08-26), `planes_negocio` y columnas nuevas en `rest_restaurantes` (`migrations/003_superadmin_universidades_planes.sql`, el archivo conserva el nombre viejo, el contenido ya usa el nuevo) — incluye backfill para no dejar sin `estado_plataforma` al negocio ya activo en producción (UTEQ Cafetería). Se difiere `rest_categorias_negocio` (§3.7): con 1 solo negocio real hoy y `empresas.tipo_negocio` cubriendo el caso, no hay urgencia — revisar cuando haga falta categorización real
- [x] Cuenta Superadmin sembrada (`migrations/004_superadmin_seed.sql`) — no existía ninguna en el esquema real
- [x] `SuperadminController` (negocios: listado global, aprobar/suspender, auditado vía `LogModel`) + layout de plataforma nuevo y separado (`app/views/superadmin/layouts/main.php`, no depende de `restaurante_activo_id`)
- [x] Slug `superadmin` registrado en el `$routes` array de `index.php`; redirect post-login de Superadmin apunta a `superadmin/dashboard` (antes iba directo a `restaurante/seleccionar` — ese flujo se conserva como "Entrar como Admin de un negocio")
- [x] `PuntoReferenciaModel` + acciones de puntos de referencia en `SuperadminController` (listado con conteo de negocios asociados, alta/edición, activar/desactivar) — se sumaron al mismo controlador en vez de crear uno nuevo, siguiendo el patrón consolidado de `RestStaffController`
- [x] Función Haversine reusable (`PuntoReferenciaModel::haversineKm()`, PHP puro) + recálculo automático de `rest_puntos_referencia`:
  - Al guardar/editar un punto desde Superadmin → recalcula contra todos los negocios con lat/lng
  - Al guardar la ubicación de un negocio en `rest-config` (Admin) → recalcula ese negocio contra todos los puntos activos (hook agregado en `RestConfigController::guardar()`, degrada sin tronar si la migración 003 no ha corrido)
  - Respeta `destacado_manual=1` (override de Superadmin): esas filas nunca se autoborran ni se recalculan por radio
- [x] Vista `superadmin/puntos-referencia/index.php` (listado + alta inline)
- [x] Dashboard global (`superadmin/dashboard/index.php` + `RestauranteModel::getResumenPlataforma()`): negocios por estado, ventas del mes/históricas, pedidos, usuarios web vs app móvil, ranking top-5 por ventas
- [x] Usuarios/accesos (`superadmin/usuarios/index.php` + `UsuarioModel::listadoParaSuperadmin()`): listado global de todas las cuentas de todos los negocios con filtros (rol, búsqueda) y paginación, alta de usuario con password temporal, activar/desactivar, resetear password — todo auditado en `action_logs`
- [x] Detalle de negocio (`superadmin/negocios/detalle.php` + `RestauranteModel::getDetalleParaSuperadmin()`): datos generales, KPIs de ventas del negocio (mes/histórico/pedidos/ticket promedio), staff con su rol y estado, puntos de referencia cercanos (distancia y si es automático o manual), asignación de plan, y aprobar/suspender desde la misma ficha. Se llega dando clic al nombre en el listado
- [x] Configuración global (`superadmin/config/index.php`, reusa `ConfigModel`): edita `global_settings` agrupado por `grupo`, con el input correcto según `tipo` (selector de color para los hex, texto para el resto). **Validación al guardar** para no romper la app móvil: solo acepta claves que ya existen, exige hex `#RRGGBB` en los colores, exige JSON válido si el valor actual ya era JSON, y numérico donde aplica — un ajuste inválido se omite y los demás sí se guardan
- [x] Bitácora (`superadmin/bitacora/index.php`, reusa `LogModel::getBitacora()`): visor de `action_logs` con filtro por módulo y fecha, y paginación — cierra el ciclo de todo lo que el panel audita (§5.5)
- [ ] Pruebas: alta de punto de referencia → alta de negocio cerca → verificar asociación automática → aprobar negocio → verificar que aparece en "cerca de mi universidad" en la app móvil (la app puede seguir hablando de "universidad" en su copy — es un tema de texto visible, no de esquema)
- [ ] ⚠️ Pendiente real: el único negocio en producción (UTEQ Cafetería) tiene `lat`/`lng` en `NULL` hoy — nada se asociará hasta que el Admin las capture en `rest-config`

### Diferido a sprints posteriores (no bloquea el núcleo)

- [ ] Moderación (§5.4) — módulo nuevo desde cero con su propia tabla de cola (ver §2: social ya está 100% eliminado, no hay nada que reusar)
- [ ] Cron mensual de cálculo de comisiones — bloqueado por la decisión de modelo de comisión (§3.3)
- [ ] `soporte_tickets`, `promos_destacadas`, `rest_negocio_comisiones`
