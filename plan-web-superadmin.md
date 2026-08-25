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
| `action_logs` | Auditoría global de acciones |
| `moderation_actions` | **Ya existe y encaja directo** con la cola de moderación (target_type, decision, moderator_id, notes) |
| `login_intentos`, `api_rate_limits` | Seguridad de la plataforma |
| `global_settings` | Configuración global clave-valor (ya existe: colores de app, métodos de pago habilitados, costo de envío por defecto — reusar y extender) |

---

## 3. Tablas y columnas NUEVAS necesarias

### 3.1 `universidades` (tabla nueva)

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

### 3.2 `rest_universidades` (tabla puente — negocio ↔ universidad)

| Columna | Tipo | Notas |
|---|---|---|
| `restaurante_id` | `int(10) UNSIGNED NOT NULL` | |
| `universidad_id` | `int(10) UNSIGNED NOT NULL` | |
| `distancia_km` | `decimal(6,2) DEFAULT NULL` | Calculada automáticamente (Haversine) al guardar/editar ubicación del negocio |
| `destacado_manual` | `tinyint(1) NOT NULL DEFAULT 0` | Override manual de Superadmin si quiere forzar la asociación |

- PK compuesta `(restaurante_id, universidad_id)`
- [ ] Job/trigger que recalcula esta tabla cuando cambia `lat`/`lng` de un negocio o se agrega una universidad nueva

### 3.3 `planes_negocio` (tabla nueva)

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
| `universidad_id` | `int(10) UNSIGNED DEFAULT NULL` | NULL = destacado general, o limitado a una universidad/zona |
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
- [ ] `superadmin/universidades/index.php`, `superadmin/universidades/form.php`, `superadmin/universidades/mapa.php`
- [ ] `superadmin/planes/index.php`, `superadmin/planes/form.php`
- [ ] `superadmin/comisiones/index.php` — estado de cuenta por negocio, marcar pagado
- [ ] `superadmin/destacados/index.php`, `superadmin/destacados/form.php`
- [ ] `superadmin/moderacion/index.php` — cola de `moderation_actions`
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
| Cobertura por universidad | `COUNT(rest_universidades) GROUP BY universidad_id` |
| Zonas más activas | `rest_pedidos` cruzado con `rest_universidades` vía `restaurante_id` |
| Usuarios app móvil activos | `mobile_usuarios` con `activo=1` y actividad reciente en `rest_pedidos.mobile_usuario_id` |

---

## 6. Reglas de negocio clave

- [ ] Solo Superadmin puede cambiar `rest_restaurantes.estado_plataforma`
- [ ] Al aprobar un negocio nuevo (`pendiente → activo`), el sistema:
  - [ ] Crea la cuenta Admin inicial (o envía invitación)
  - [ ] Asigna `plan_id` por defecto
  - [ ] Recalcula `rest_universidades` según su `lat`/`lng`
- [ ] Al suspender un negocio, deja de aparecer en la búsqueda de la app móvil pero **no se borran sus datos**
- [ ] Cálculo de comisión corre por cron mensual, no en tiempo real (evita inconsistencias si hay reembolsos a mitad de mes)
- [ ] Superadmin puede impersonar a un Admin para soporte, pero esa acción **debe quedar registrada** en `action_logs`

---

## 7. Checklist de implementación

- [ ] Migración SQL con todas las tablas de §3
- [ ] `RestauranteController` extendido con: aprobación/suspensión, asignación de plan
- [ ] Nuevo `SuperadminUniversidadController`, `SuperadminPlanController`, `SuperadminSoporteController`, `SuperadminModeracionController` (puede reusar lógica existente de `RestModeracionController` si aplica)
- [ ] Función Haversine reusable (PHP y/o query SQL) para distancia negocio↔universidad
- [ ] Cron job mensual de cálculo de comisiones
- [ ] Vistas de §4
- [ ] Pruebas: alta de universidad → alta de negocio cerca → verificar asociación automática → aprobar negocio → verificar que aparece en "cerca de mi universidad" en la app móvil
