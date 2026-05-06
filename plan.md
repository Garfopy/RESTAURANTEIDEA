# CarniHub — Plan v2.6.4
**Versión:** 2.6.4 | **Fecha:** 2026-05-05 | **Stack:** PHP 8.3 · MySQL · Tailwind CDN · MVC sin framework

---

## MODELO DE NEGOCIO

CarniHub es un **SaaS B2B** vendido a **productores de carne**. Cada productor que compra el sistema es un "Admin Empresa" que opera su propio portal privado. El flujo de negocio es:

```
Productor de carne (Admin Empresa)
    │   Carga su catálogo de productos y precios
    │   Registra a sus clientes compradores en el sistema
    │   Asigna supervisores y repartidores de su propia operación
    │
    ├── Comprador (cliente del productor)
    │     Inicia sesión → ve el catálogo de su proveedor → hace pedidos
    │
    ├── Supervisor (empleado del productor)
    │     Revisa y aprueba pedidos, configura límites de compra
    │
    └── Repartidor (empleado del productor)
          Recibe la ruta del día y registra entregas con GPS
```

**No existe registro público.** Todos los usuarios son creados por un rol superior. El Super Admin de la plataforma solo crea cuentas de empresa (Admin Empresa); nunca toca productos, pedidos ni operación interna de ninguna empresa.

---

## BASE DE DATOS — QUÉ HACER PARA RESETEARLA

> Haz esto ANTES de subir el código al servidor.

### Paso 1 — Entrar a phpMyAdmin (cPanel)
1. Abre cPanel → sección **Bases de Datos** → **phpMyAdmin**
2. Selecciona la base de datos: **`idactivo_carnihubdb`** en el panel izquierdo

### Paso 2 — Borrar todo lo que existe
1. Haz clic en la pestaña **SQL**
2. Pega y ejecuta:
```sql
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS plantilla_recurrente_detalle;
DROP TABLE IF EXISTS pedidos_recurrentes;
DROP TABLE IF EXISTS evidencias_entrega;
DROP TABLE IF EXISTS ruta_detalle;
DROP TABLE IF EXISTS rutas;
DROP TABLE IF EXISTS repartidor_vehiculo;
DROP TABLE IF EXISTS vehiculos;
DROP TABLE IF EXISTS pedido_sucursal;
DROP TABLE IF EXISTS pedido_detalle;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS limites_compra;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS facturas;
DROP TABLE IF EXISTS precios_escalonados;
DROP TABLE IF EXISTS inventario;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS sucursales;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS empresas;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS global_settings;
DROP TABLE IF EXISTS action_logs;
DROP TABLE IF EXISTS error_logs;
DROP TABLE IF EXISTS login_intentos;
DROP TABLE IF EXISTS verificacion_tokens;
DROP TABLE IF EXISTS registro_intentos;
DROP TABLE IF EXISTS email_verificaciones;
DROP TABLE IF EXISTS dispositivos_hikvision;
DROP TABLE IF EXISTS dispositivos_shelly;

SET FOREIGN_KEY_CHECKS = 1;
```

### Paso 3 — Importar el nuevo schema
1. Ve a la pestaña **Importar** en phpMyAdmin
2. Haz clic en **Seleccionar archivo**
3. Busca en tu computadora: `CarniHub/migrations/001_schema_completo.sql`
4. Haz clic en **Continuar / Importar**
5. Debe decir: **"Importación ejecutada correctamente"**

### Paso 4 — Verificar que se crearon las tablas
En el panel izquierdo debes ver estas tablas:
```
roles · empresas · usuarios · sucursales · categorias
productos · precios_escalonados · inventario
pedidos · pedido_detalle · pedido_sucursal
vehiculos · repartidor_vehiculo · rutas · ruta_detalle
evidencias_entrega · pedidos_recurrentes · plantilla_recurrente_detalle
limites_compra · pagos · facturas
global_settings · action_logs · error_logs · login_intentos
```

### Credenciales de prueba (incluidas en el seed)
| Rol | Email | Contraseña |
|-----|-------|-----------|
| Super Admin | admin@carnihub.mx | Admin2024! |
| Admin Empresa | juan@buensabor.mx | Admin2024! |
| Supervisor | supervisor@buensabor.mx | Admin2024! |
| Comprador | comprador@buensabor.mx | Admin2024! |
| Repartidor | repartidor@buensabor.mx | Admin2024! |

> ⚠️ Para los 3 usuarios de rol empresa, importa también `migrations/002_seed_usuarios_prueba.sql` después del schema base.

---

## ARQUITECTURA DE ROLES (definitiva — sin registro público)

No existe registro público. **Todos los usuarios son creados por un rol superior.**

```
SuperAdmin (plataforma — 1 usuario inicial, seed en BD)
    │  Solo monitorea la plataforma, no toca ninguna empresa
    │
    └── Crea: Admin CarniHub (soporte interno de plataforma)
    └── Crea: Admin Empresa  (el productor de carne que compra el sistema)
                │
                ├── Crea: Comprador  (sus clientes compradores)
                ├── Crea: Supervisor (empleados de su empresa)
                └── Crea: Repartidor (choferes de su empresa)
```

### Los 6 roles

| Rol | slug | Portal | Quién lo crea | Función |
|-----|------|--------|---------------|---------|
| Super Admin | `superadmin` | `/panel/` | Seed inicial en BD | Monitoreo de plataforma, métricas globales, crear cuentas empresa |
| Admin CarniHub | `admin` | `/panel/` | El superadmin | Soporte operativo de plataforma |
| Admin Empresa | `admin_empresa` | `/empresa/` | Admin o superadmin | Control total de su empresa: catálogo, equipo, pedidos |
| Supervisor | `supervisor` | `/supervisor/` | Admin Empresa | Aprueba pedidos, configura límites de compra |
| Comprador | `comprador` | `/comprador/` | Admin Empresa | Navega el catálogo y hace pedidos |
| Repartidor | `repartidor` | `/repartidor/` | Admin Empresa | App oscura GPS para registrar entregas |

---

## PERMISOS POR ROL

### Super Admin — monitoreo + configuración de plataforma

El superadmin **nunca** toca productos, inventario, pedidos ni ninguna operación interna de las empresas. Su función es garantizar que la plataforma funcione correctamente y configurar los parámetros globales del SaaS.

| Capacidad | Descripción |
|-----------|-------------|
| Dashboard de plataforma | KPIs globales: empresas activas, pedidos del sistema, volumen de ventas (solo lectura) |
| Estado de APIs y servicios | Google Maps, WhatsApp, Traccar, PayPal, Factura-lo, Shelly, HikVision |
| Métricas y salud del sistema | Logs de error, intentos de login, actividad del sistema |
| Configuración global | Logo, colores, claves de API, configuración SMTP |
| Gestionar empresas | Crear/activar/desactivar cuentas de Admin Empresa |
| Crear Admin CarniHub | Para soporte interno del sistema |
| **Editar precios de planes** | Modificar precio, límites de usuarios/productos/pedidos/sucursales por plan desde `/suscripcion/configurar` |
| **Incidencias lógicas** | Panel de errores del sistema: excepciones PHP, intentos de login fallidos, accesos denegados, errores de pago (fuente: `error_logs` + `action_logs`) |
| **Reportes de funcionamiento** | Uptime de APIs, pedidos procesados por mes, tasa de errores, transacciones PayPal con gráficas Chart.js |
| **Reportes de ventas SaaS** | Ingresos por suscripciones, churn, planes más contratados, empresas nuevas vs. canceladas |

### Admin Empresa — control total de su empresa

El Admin Empresa es el productor de carne. Tiene control completo sobre su propia operación. El dashboard es un **panel de estado general**, no un punto de acción de pedidos.

| Capacidad | Descripción |
|-----------|-------------|
| **Dashboard — panel de estado general** | Ventas del día/mes, cobros pendientes de cobrar, últimos movimientos financieros, stock crítico (alertas en rojo), equipo activo, pedidos en curso — todo en vista rápida sin botón "Nuevo pedido" |
| Catálogo de productos | CRUD de productos, precios escalonados, imágenes, categorías |
| Inventario | Stock, alertas de mínimo, ajustes manuales |
| Pedidos de su empresa | Ver todos los pedidos de sus compradores, cambiar estado, aprobar, cancelar |
| **Cobros y movimientos** | Ver qué compradores tienen pedidos pendientes de pago, historial de pagos recibidos, saldos por cobrar |
| **Configurar métodos de cobro** | Definir cómo recibe pagos de sus clientes: transferencia, efectivo, PayPal (mock en Sprint 5P, real posterior) |
| Crear compradores | Agrega a sus clientes/compradores para que hagan pedidos |
| Crear supervisores | Empleados que aprueban pedidos y configuran límites |
| Crear repartidores | Choferes de su flota con app GPS |
| Sucursales | CRUD de puntos de entrega/recepción |
| Vehículos | Alta de vehículos y asignación a repartidores |
| Rutas y logística | Crear rutas del día, asignar paradas por pedido |
| Reportes de empresa | Consumo mensual, gasto por sucursal, top productos |
| Límites de compra | Configurar máximos por comprador/sucursal/producto |
| Pedidos recurrentes | Plantillas de pedido automático |

### Supervisor — aprobaciones y control

| Capacidad | Descripción |
|-----------|-------------|
| Ver catálogo (solo lectura) | Consultar productos y precios |
| Aprobar / rechazar pedidos | Cola de aprobación con modal de motivo |
| Configurar límites de compra | Máximos por comprador, sucursal o producto |
| Rastrear entregas en mapa | GPS en tiempo real de sus pedidos |
| Reportes operativos | Ver actividad de su empresa |

### Comprador — tienda y pedidos

| Capacidad | Descripción |
|-----------|-------------|
| Ver catálogo de su proveedor | Productos con precios escalonados según su volumen |
| Carrito y pedidos | Flujo 4 pasos: productos → sucursal → resumen → confirmar |
| Historial de pedidos | Consultar pedidos pasados con filtros |
| Rastrear entrega | Mapa GPS en tiempo real cuando el pedido está en ruta |
| Pedidos recurrentes | Activar plantillas de pedido automático |

### Repartidor — app de entregas

| Capacidad | Descripción |
|-----------|-------------|
| Ruta del día | Ver las paradas asignadas |
| Registrar entrega | GPS automático + firma digital + foto del receptor |
| Historial de entregas | Evidencias y estados de días anteriores |

---

## PORTALES Y RUTAS

```
/auth/login              → Login único para TODOS los roles

Login exitoso redirige según rol:
  superadmin / admin     → /panel/dashboard
  admin_empresa          → /empresa/dashboard
  supervisor             → /supervisor/dashboard
  comprador              → /comprador/inicio
  repartidor             → /repartidor/inicio
```

### Router (`index.php`) — mapeo de URLs

| URL | Controller | Acceso |
|-----|-----------|--------|
| `auth/*` | AuthController | Todos |
| `api/*` | ApiController | Autenticados |
| `panel/*` | PanelController | superadmin, admin |
| `panel-empresa/*` | EmpresaController | superadmin, admin |
| `panel-usuario/*` | PanelUsuarioController | superadmin, admin |
| `panel-reporte/*` | PanelReporteController | superadmin, admin |
| `config/*` | ConfigController | superadmin |
| `empresa/*` | EmpresaDashboardController | admin_empresa |
| `empresa-producto/*` | EmpresaProductoController | admin_empresa |
| `empresa-inventario/*` | EmpresaInventarioController | admin_empresa |
| `empresa-pedido/*` | EmpresaPedidoController | admin_empresa |
| `empresa-usuario/*` | EmpresaUsuarioController | admin_empresa |
| `empresa-sucursal/*` | EmpresaSucursalController | admin_empresa |
| `empresa-vehiculo/*` | EmpresaVehiculoController | admin_empresa |
| `empresa-logistica/*` | EmpresaLogisticaController | admin_empresa |
| `empresa-reporte/*` | EmpresaReporteController | admin_empresa |
| `supervisor/*` | SupervisorController | supervisor |
| `comprador/*` | CompradorController | comprador |
| `catalogo/*` | CatalogoController | comprador (solo lectura) |
| `carrito/*` | CarritoController | comprador |
| `pedido/*` | PedidoController | comprador, supervisor, admin_empresa |
| `recurrente/*` | RecurrenteController | comprador, admin_empresa |
| `limite/*` | LimiteController | supervisor, admin_empresa |
| `pago/*` | PagoController | comprador, admin_empresa |
| `cuenta/*` | CuentaController | Todos |
| `repartidor/*` | RepartidorController | repartidor |

---

## ESTADO ACTUAL DE ARCHIVOS

### Controllers (en `app/controllers/`)

| Controller | Estado | Acceso | Funcionalidad |
|-----------|--------|--------|---------------|
| BaseController | ✅ Completo | — | requireAdmin, requireEmpresa, requireComprador, requireSupervisor, requireAdminEmpresa, requireRepartidor, redirectSegunRol |
| AuthController | ✅ Completo | Todos | Login con brute-force, logout, redirect por rol |
| PanelController | ✅ Funcional | superadmin, admin | Dashboard con KPIs globales de plataforma |
| RepartidorController | ✅ Funcional | repartidor | Ruta del día, confirmar entrega, firma/foto POD, historial |
| ApiController | ✅ Funcional | Autenticados | Precios escalonados AJAX, GPS tracking |
| EmpresaController | ✅ Funcional | superadmin, admin | Listado y alta de empresas cliente |
| EmpresaUsuarioController | ✅ Funcional | admin_empresa | Crea supervisor/comprador/repartidor para su empresa |
| EmpresaDashboardController | ✅ Funcional | admin_empresa | Dashboard del productor (ventas, stock, equipo) |
| CatalogoController | ✅ Funcional | comprador | Catálogo con filtros, detalle de producto |
| CuentaController | ✅ Funcional | Todos | Perfil, guardar datos, cambiar contraseña, avatar, guardarDireccion (compradores) |
| CarritoController | ✅ Funcional | comprador | 4 pasos: productos → sucursales → resumen → confirmar |
| PedidoController | ✅ Funcional | comprador, supervisor, admin_empresa | Historial, detalle, aprobación, tracking GPS, cancelar |
| ConfigController | ✅ Completo | superadmin | general (logo+colores), apis (claves), correo (SMTP) |
| PanelUsuarioController | ✅ Completo | superadmin, admin | CRUD usuarios de plataforma (admin, admin_empresa) + toggle |
| PanelLogisticaController | ✅ Funcional | superadmin, admin | Mapa global de rutas (solo monitoreo) |
| EmpresaProductoController | ✅ Funcional | admin_empresa | CRUD catálogo de la empresa |
| EmpresaInventarioController | ✅ Funcional | admin_empresa | Stock de la empresa — fix p.unidad aplicado |
| EmpresaLogisticaController | ✅ Funcional | admin_empresa | Rutas de la empresa — fix query() protected aplicado |
| SupervisorController | ⚠️ Esqueleto | supervisor | Panel dedicado — vistas pendientes |
| CompradorController | ⚠️ Esqueleto | comprador | Portal de compras — vistas pendientes |
| EmpresaPedidoController | ✅ Funcional | admin_empresa | Pedidos de su empresa: index, aprobar, rechazar, asignarEntrega, cambiarEstado, subirFotoEntrega, personalizado, guardarPersonalizado |
| PanelReporteController | ❌ Pendiente | superadmin, admin | Reportes globales de plataforma (solo lectura) |
| RecurrenteController | ❌ Pendiente | comprador, admin_empresa | Plantillas de pedido automático |
| LimiteController | ❌ Pendiente | supervisor, admin_empresa | Límites de compra |
| EmpresaReporteController | ❌ Pendiente | admin_empresa | Reportes de su empresa |
| SuscripcionController | ✅ Completo | superadmin, admin | Gestión suscripciones + webhook PayPal |
| EmpresaSuscripcionController | ✅ Completo | admin_empresa | Portal pago con PayPal |
| PublicController | ✅ Completo | Público | Página de precios pública |
| PagoController | ❌ Pendiente | comprador, admin_empresa | Comprobantes, PayPal, crédito |
| EmpresaSucursalController | ❌ Pendiente | admin_empresa | CRUD sucursales con mapa |
| EmpresaVehiculoController | ❌ Pendiente | admin_empresa | Vehículos + asignación repartidores |

### Models (7 — en `app/models/`)

| Model | Estado |
|-------|--------|
| BaseModel | ✅ CRUD + paginate (query/queryOne/execute son protected — llamar solo desde models) |
| UsuarioModel | ✅ getByEmail, getByEmpresa, rolesPermitidos, crear, getConRol, getRolPorSlug, getRolPorId, getRepartidoresGlobal, getRepartidoresPorEmpresa |
| EmpresaModel | ✅ listado con filtros, estadísticas, listadoSimple |
| ProductoModel | ✅ listadoConPrecio, getPrecioParaCantidad, getEscalonados, getCategorias, listadoAdmin, listadoInventario (fix: usa presentacion no unidad), ajustarStock, actualizarEscalonados, inicializarInventario, actualizarInventario |
| PedidoModel | ✅ generarFolio, crear (transacción), listadoEmpresa, pendientesAprobacion, conDetalle, aprobar, rechazar, tracking, listadoGlobal, cambiarEstado, crearRuta, listadoConfirmadosPorEmpresa, getRutasActivas, getPosicionesActivas |
| ConfigModel | ✅ get, set, getGrupo, getAll, guardarGrupo |
| SuscripcionModel | ✅ getPlanesActivos, getPlanPorSlug, getByEmpresa, getByPaypalId, listado, crear, cambiarPlan, cambiarEstado, guardarPaypalId, activarDesdePaypal, renovar, verificarLimite |
| LogModel | ✅ registrar, registrarError, getBitacora |

### Vistas (en `app/views/`)

| Vista | Estado | Rol |
|-------|--------|-----|
| auth/login.php | ✅ Sin links de registro | — |
| panel/layouts/main.php | ✅ Sidebar oscuro dinámico | superadmin, admin |
| panel/dashboard.php | ✅ KPIs + alertas + tabla pedidos | superadmin, admin |
| panel/empresas/index.php | ✅ Listado con filtros | superadmin, admin |
| panel/empresas/form.php | ✅ Alta de empresa | superadmin, admin |
| panel/usuarios/index.php | ✅ Listado usuarios plataforma | superadmin, admin |
| panel/usuarios/form.php | ✅ Alta/edición de usuario plataforma | superadmin, admin |
| panel/productos/index.php | ⚠️ Construida, debe migrar a empresa | admin_empresa |
| panel/productos/form.php | ⚠️ Construida, debe migrar a empresa | admin_empresa |
| panel/inventario/index.php | ⚠️ Construida, debe migrar a empresa | admin_empresa |
| panel/pedidos/index.php | ✅ Vista global de todos los pedidos | superadmin, admin |
| panel/pedidos/detalle.php | ✅ Detalle del pedido | superadmin, admin |
| panel/logistica/index.php | ⚠️ Construida, debe migrar a empresa | admin_empresa |
| panel/logistica/form_ruta.php | ⚠️ Construida, debe migrar a empresa | admin_empresa |
| empresa/layouts/main.php | ✅ Sidebar blanco dinámico | admin_empresa |
| empresa/dashboard.php | ✅ Dashboard del productor | admin_empresa |
| empresa/catalogo/index.php | ✅ Grid de productos con filtros | comprador |
| empresa/catalogo/detalle.php | ✅ Detalle + precios escalonados | comprador |
| empresa/carrito/paso1.php | ✅ Selección productos + AJAX | comprador |
| empresa/carrito/paso2.php | ✅ Distribución por sucursal | comprador |
| empresa/carrito/paso3.php | ✅ Resumen + fecha + pago | comprador |
| empresa/carrito/paso4.php | ✅ Folio confirmado | comprador |
| empresa/pedidos/index.php | ✅ Historial con filtros | comprador, supervisor |
| empresa/pedidos/detalle.php | ✅ Detalle completo + acciones | comprador, supervisor |
| empresa/pedidos/aprobacion.php | ✅ Lista pendientes + modal rechazo | supervisor |
| empresa/pedidos/tracking.php | ✅ Mapa Leaflet + polling GPS | comprador, supervisor |
| empresa/usuarios/index.php | ✅ Listado equipo de empresa | admin_empresa |
| empresa/usuarios/form.php | ✅ Alta/edición de usuario de empresa | admin_empresa |
| empresa/cuenta/perfil.php | ✅ Editar perfil + avatar | Todos |
| repartidor/inicio.php | ✅ Ruta del día (dark mode) | repartidor |
| repartidor/entrega.php | ✅ Firma digital + GPS + foto | repartidor |
| repartidor/historial.php | ✅ Historial de entregas | repartidor |
| supervisor/dashboard.php | ❌ Pendiente | supervisor |
| comprador/inicio.php | ❌ Pendiente | comprador |

### Migración
| Archivo | Estado |
|---------|--------|
| `migrations/001_schema_completo.sql` | ✅ Schema completo + seed demo (superadmin + admin_empresa) |
| `migrations/002_seed_usuarios_prueba.sql` | ✅ Supervisor, comprador, repartidor para empresa 1 |
| `migrations/003_saas_suscripciones.sql` | ✅ Tablas `planes_saas` y `suscripciones` + ALTER empresas + seed 3 planes |

---

## FLUJOS IMPLEMENTADOS

### Flujo de login
```
1. /auth/login → formulario (sin links de registro)
2. POST /auth/doLogin → valida email+password
3. Brute-force: máx 5 intentos por IP en 2 minutos
4. Login exitoso → sesión guarda usuario + empresa
5. Redirect según rol:
   - superadmin/admin → /panel/dashboard
   - admin_empresa    → /empresa/dashboard
   - supervisor       → /supervisor/dashboard
   - comprador        → /comprador/inicio
   - repartidor       → /repartidor/inicio
```

### Flujo del comprador (cliente del productor)
```
1. Admin empresa crea cuenta de comprador (email + contraseña temporal)
2. Comprador inicia sesión → /comprador/inicio
3. Ve el catálogo de SU proveedor (filtrado por empresa_id)
4. Agrega productos al carrito con precios escalonados por volumen
5. Flujo 4 pasos: productos → sucursal de entrega → resumen → confirmar
6. Si el supervisor tiene límites activos, el pedido queda pendiente de aprobación
7. Si no, pasa directamente a "confirmado"
8. Puede rastrear la entrega en mapa GPS cuando el pedido está en ruta
```

### Flujo del supervisor
```
1. Admin empresa crea cuenta de supervisor
2. Supervisor inicia sesión → /supervisor/dashboard
3. Ve cola de pedidos pendientes de aprobación
4. Aprueba o rechaza con modal de motivo
5. Puede configurar límites por comprador/sucursal/producto
6. Ve reportes operativos de su empresa
7. Puede rastrear entregas en mapa GPS
```

### Flujo del Admin Empresa (productor)
```
1. Superadmin crea la cuenta de admin_empresa y asigna empresa
2. Admin empresa inicia sesión → /empresa/dashboard
3. Carga su catálogo: productos, categorías, precios escalonados
4. Crea sus compradores (clientes), supervisores y repartidores
5. Gestiona sucursales y vehículos de su flota
6. Supervisa todos los pedidos de su empresa
7. Crea rutas del día asignando paradas a repartidores
8. Ve reportes financieros y operativos de su empresa
```

### Flujo de creación de usuarios (admin_empresa)
```
1. Admin empresa entra a /empresa-usuario/nuevo
2. Selecciona rol: Comprador, Supervisor o Repartidor
3. Llena nombre, email, teléfono
4. Sistema genera contraseña temporal (ej: Ch4821!)
5. Flash muestra la contraseña → admin la comunica al usuario
6. Usuario queda vinculado a la misma empresa_id del admin
```

### Flujo de GPS tracking (repartidor)
```
1. Admin empresa crea ruta → asigna repartidor
2. Repartidor en /repartidor/inicio → ve su ruta del día
3. Repartidor entra a parada → clic "Registrar entrega"
4. JavaScript activa navigator.geolocation.watchPosition()
5. Cada 30 segundos → POST /api/tracking/actualizar (lat, lng, paradaId)
6. Backend calcula ETA con fórmula Haversine (~30 km/h urbano)
7. Comprador/supervisor en /pedido/tracking/{pedido_id}:
   - Polling AJAX cada 5 segundos → GET /api/tracking/{pedido_id}
   - Mapa Leaflet.js actualiza marcador en tiempo real
   - Barra de estado: En preparación → En ruta → Próximo → Entregado
8. Repartidor confirma entrega: firma canvas + foto + nombre receptor
9. POST /repartidor/confirmarEntrega → guarda evidencia + actualiza estado
10. Si todas las paradas del pedido = entregado → pedido pasa a "entregado"
```

---

## PENDIENTE — SPRINTS SIGUIENTES

### Sprint 3 — Carrito y Pedidos ✅ COMPLETADO

### Sprint 4A — Configuración Global + Foto de Perfil ✅ COMPLETADO
- [x] `ConfigController` completo — general, apis, correo, subirLogo (solo superadmin)
- [x] Vista `config/general.php` — nombre app, colores, upload de logo
- [x] Vista `config/apis.php` — todas las claves de API con toggle show/hide
- [x] Vista `config/correo.php` — configuración SMTP
- [x] Logo dinámico ya funcional en ambos layouts (panel y empresa)
- [x] `CuentaController::subirAvatar()` — foto de perfil para todos los roles

### Sprint 4B — Portales por rol + Corrección de arquitectura ✅ COMPLETADO
> Arquitectura de roles corregida: admin_empresa es VENDEDOR (no comprador).

**Correcciones aplicadas:**
- [x] Sidebar empresa: `$esComprador` restringido a solo rol `comprador` (admin_empresa ya no ve menú de compras)
- [x] Sidebar panel (superadmin): eliminado "Pedidos global" — superadmin solo ve métricas
- [x] Form alta usuario empresa: selector de rol en cards + campos específicos por rol
  - Comprador → datos del negocio y dirección de entrega
  - Repartidor → tipo vehículo, placas, modelo, licencia
- [x] `EmpresaUsuarioController::guardar()` — auto-crea sucursal al crear comprador
- [x] `EmpresaProductoController`, `EmpresaInventarioController`, `EmpresaLogisticaController` — controllers del admin_empresa
- [x] `SupervisorController`, `CompradorController` — portales dedicados por rol
- [x] `UsuarioModel` — consolidado: getConRol, getRolPorSlug, getRolPorId, getRepartidoresGlobal, getRepartidoresPorEmpresa

**Fixes de errores (producción):**
- [x] `ProductoModel::listadoInventario()` — eliminado `p.unidad` (columna inexistente; usar `p.presentacion`)
- [x] `BaseModel::query()` es protected — movidas queries de logística a métodos públicos en `PedidoModel`
  - `PedidoModel::getRutasActivas(int $empresaId = 0)` — global o filtrada por empresa
  - `PedidoModel::getPosicionesActivas(int $empresaId = 0)` — GPS activos global o por empresa
  - `PedidoModel::listadoConfirmadosPorEmpresa(int $empresaId)` — pedidos disponibles para asignar ruta
- [x] `rutas.estado` — corregidos valores a `planificada/en_curso` (schema DB usa estos, no `pendiente/en_ruta`)
- [x] `EmpresaLogisticaController::nuevaRuta()` — corregido a `getRepartidoresPorEmpresa()` (un solo argumento)

**Migración de datos de prueba:**
- [x] `migrations/002_seed_usuarios_prueba.sql` — supervisor, comprador, repartidor para empresa 1

### Sprint 4S — SaaS: Planes, Suscripciones y PayPal ✅ COMPLETADO

**Planes definitivos:**
| Plan | Precio/mes | Usuarios | Productos | Pedidos/mes | Sucursales |
|------|-----------|---------|---------|------------|----------|
| Básico | $2,600 MXN | 5 | 100 | 200 | 3 |
| Pro | $3,200 MXN | 20 | Ilimitado | Ilimitado | 10 |
| Empresa | $4,000 MXN | Ilimitado | Ilimitado | Ilimitado | Ilimitado |

- [x] `migrations/003_saas_suscripciones.sql` — tablas `planes_saas` y `suscripciones`, ALTER `empresas`
- [x] `SuscripcionModel` — CRUD + límites + sync estado empresa
- [x] `PayPalSuscripcionService` — wrapper PayPal Subscriptions API (sandbox/live)
- [x] `BaseController` — `requireSuscripcionActiva()`, `getPlanActual()`
- [x] `SuscripcionController` — panel admin: listado, cambiar plan, suspender/activar, webhook PayPal
- [x] `EmpresaSuscripcionController` — portal empresa: mi plan, ver planes, checkout PayPal, retorno, suspendida
- [x] `EmpresaController` — asigna plan al crear empresa
- [x] `EmpresaModel` — listado ahora incluye plan_slug y plan_nombre via LEFT JOIN
- [x] `PublicController` — página pública de planes sin login
- [x] Vistas panel: `suscripciones/index.php`, `suscripciones/configurar.php`
- [x] Vistas panel: `empresas/index.php` (columna Plan), `empresas/form.php` (cards de plan)
- [x] Sidebar panel: enlace "Suscripciones" bajo sección Clientes
- [x] Vistas empresa: `suscripcion/planes.php`, `confirmacion.php`, `suspendida.php`, `mi_plan.php`
- [x] `public/planes.php` — pricing público standalone
- [x] `index.php` — rutas `suscripcion/*`, `empresa-suscripcion/*`, `planes/*`; autoload services

### Sprint 4C-0 — Hotfixes críticos (pre-4C) ✅ COMPLETADO
> Bugs activos encontrados en revisión 2026-05-05. Todos corregidos en rama `sprint-4C-0`.

**Bug 1 — Inventario: stock siempre mostraba 0**
- [x] `ProductoModel::listadoInventario()` — alias corregido: `AS stock` → `AS stock_actual`

**Bug 2 — Inventario: imágenes no se ven + sin filtro por empresa (bug de seguridad)**
- [x] `ProductoModel::listadoInventario()` — añadido `p.imagen` al SELECT
- [x] `ProductoModel::listadoInventario()` — filtro `AND p.empresa_id = ?` (requiere migration 005)
- [x] `ProductoModel::listadoAdmin()` — filtro por `empresa_id` añadido
- [x] `EmpresaInventarioController::index()` — pasa `empresa_id` al modelo
- [x] `EmpresaProductoController::index()` — pasa `empresa_id` al modelo
- [x] `EmpresaProductoController::guardar()` — guarda `empresa_id` al crear producto
- [x] Vista `empresa/inventario/index.php` — miniatura 40×40 con fallback gris + URL de imagen correcta (campo ya guarda URL completa)
- [x] `migrations/005_productos_empresa_id.sql` — ALTER TABLE agrega columna `empresa_id` a `productos` (default 1 para datos existentes)

**Bug 3 — Suscripciones vacías en panel superadmin**
- [x] `migrations/003_saas_suscripciones.sql` — agrega INSERT suscripción demo empresa 1 al final
- [x] `migrations/004_hotfix_4c0.sql` — parche para BD existente (ya aplicado en producción)

**Bug 4 — Dashboard admin_empresa: botón "+ Nuevo pedido" incorrecto**
- [x] Vista `empresa/dashboard.php` — eliminado botón "+ Nuevo pedido" (los pedidos los hace el comprador desde el catálogo)

**Migraciones para aplicar en phpMyAdmin (si no se ha hecho reset completo):**
```
004_hotfix_4c0.sql   → suscripción demo empresa 1 (si ya corriste 003)
005_productos_empresa_id.sql → ALTER TABLE productos ADD empresa_id
```

---

### Sprint 4S-3 — Landing pública + Super Admin: precios, incidencias y reportes SaaS
> Prioridad menor que 4C-1/4C-2/4C-3. Hacer después de completar los portales de empresa, supervisor y comprador.

**A — Landing page pública** (`/` o `/inicio`)
- [ ] `LandingController` — ruta pública `/` → vista `public/landing.php`
- [ ] Secciones de la landing:
  - Hero: qué es CarniHub (titular + subtítulo + CTA "Ver planes")
  - Características: 6 cards con ventajas clave (pedidos, GPS, inventario, aprobaciones, reportes, suscripción SaaS)
  - Cómo funciona: 3 pasos ilustrados (te registras → configuras tu empresa → tus clientes piden)
  - Planes y precios: embed o link a `/planes` (ya existe)
  - Footer con contacto
- [ ] Diseño con Tailwind CDN, sin login requerido
- [ ] Navbar: logo + "Iniciar sesión" + "Ver planes"
- [ ] Rutas: `GET /` → landing, `GET /planes` → ya existe

**B — Super Admin: editar precios de planes**
- [ ] `SuscripcionController::editarPlan()` y `guardarPlan()` — formulario editar precio + límites por plan
- [ ] Vista `panel/suscripciones/editar_plan.php` — campos: precio, max_usuarios, max_productos, max_pedidos_mes, max_sucursales
- [ ] Guard: solo superadmin puede editar (admin no)

**C — Super Admin: incidencias lógicas del sistema**
- [ ] `PanelIncidenciaController` — fuente: tablas `error_logs`, `action_logs`, `login_intentos`
- [ ] Vista `panel/incidencias/index.php` — tabla con filtros: tipo (error PHP / login fallido / acceso denegado / pago fallido), empresa, fecha
- [ ] Resumen cards: errores hoy, intentos de login fallidos hoy, transacciones PayPal fallidas hoy
- [ ] Detalle de incidencia: mensaje completo, stack trace si existe, IP, usuario_id
- [ ] Ruta: `panel-incidencia/*` (solo superadmin)

**D — Super Admin: reportes de funcionamiento y ventas SaaS**
- [ ] `PanelReporteController` — KPIs de la plataforma con Chart.js
- [ ] Vista `panel/reportes/index.php`:
  - Ingresos SaaS por mes (suma suscripciones activas × precio plan) — gráfica de línea
  - Empresas activas vs. suspendidas vs. canceladas — gráfica de dona
  - Distribución de planes (cuántas empresas en cada plan) — gráfica de barras
  - Pedidos totales procesados por el sistema por mes — gráfica de área
  - Top 5 empresas por volumen de pedidos
  - Tasa de errores del sistema (errores/día últimos 30 días)
- [ ] Ruta: `panel-reporte/*` (superadmin, admin — solo lectura)

### Sprint 4C-1 — Stock Inteligente + Productos + Precios Especiales + Pedido Personalizado ✅ COMPLETADO
> Email service movido a Sprint 4C-Email (no prioritario). El foco ahora es hacer que el catálogo, inventario y precios sean sólidos y usables antes de seguir con pedidos.

#### Modelo de Sucursales (definitivo — aclarado 2026-05-05)
> Las sucursales NO son almacenes del productor. Son puntos de entrega de los compradores.

```
Empresa (productor de carne) — UN solo inventario global
    │
    ├── Comprador A (Taquería Centro) → Sucursal Centro, Sucursal Norte
    ├── Comprador B (Restaurante Las Flores) → Sucursal Única
    └── Comprador C (Carnicería del Valle) → Sucursal 1, Sucursal 2, Sucursal 3
```

- El productor envía desde su bodega → a los puntos del comprador
- El repartidor sigue ruta con múltiples paradas (ya implementado)
- **No hay envíos entre sucursales** en el MVP
- **Futuro Sprint 4D+**: `almacenes_empresa` con stock por almacén y transferencias internas

**A — Movimientos de Inventario (prioridad máxima)**
> El sistema actual solo tiene un número de stock. El supervisor y admin necesitan registrar entradas/salidas con historial.

- [x] `migrations/006_movimientos_inventario.sql` — nueva tabla:
- [x] `EmpresaInventarioController` — reescrito completo:
  - [x] `index()` — dashboard de stock: tarjetas con semáforo (verde/amarillo/rojo), resumen rápido
  - [x] `movimiento()` — formulario rápido entrada/salida/merma con cálculo de stock resultante
  - [x] `guardarMovimiento()` — registra movimiento + actualiza inventario
  - [x] `historial($productoId)` — historial de movimientos por producto con paginación
  - [x] `log_movimientos()` — log global con filtros por tipo, producto, fecha
  - [x] `ajuste($productoId)` — corrección manual de stock (solo admin_empresa)
- [x] `ProductoModel::ajustarStock()` — ahora retorna {stock_antes, stock_despues}
- [x] `MovimientoInventarioModel` — registrar, historialProducto, historialEmpresa, resumenStock, ultimosMovimientos, stockActual
- [x] **Delegación al supervisor**: supervisor puede hacer entradas/salidas; ajustes solo admin_empresa
- [x] Sidebar empresa: "Control de Stock" visible para admin_empresa Y supervisor
- [x] Vista `empresa/inventario/index.php` — nueva UI con cards semáforo + botones rápidos + últimos movimientos
- [x] Vista `empresa/inventario/movimiento_form.php` — formulario unificado entrada/salida/merma con preview de stock resultante
- [x] Vista `empresa/inventario/historial.php` — log de movimientos de un producto
- [x] Vista `empresa/inventario/log.php` — log global con filtros
- [x] Vista `empresa/inventario/ajuste_form.php` — ajuste directo de stock

**B — Precios Especiales por Comprador**
> Cada comprador puede tener un precio acordado diferente al catálogo general. Esto es común en ventas B2B de carne.

- [x] `migrations/007_precios_especiales.sql` — tabla `precios_especiales` + columnas `tipo` y `creado_por_id` en `pedidos`
- [x] `ProductoModel::getPrecioEspecial()` — retorna precio especial si existe
- [x] `ProductoModel::getPrecioFinal()` — aplica: primero precio especial, sino precio escalonado por volumen
- [x] `ProductoModel::guardarPrecioEspecial()`, `eliminarPrecioEspecial()`, `listadoParaPreciosEspeciales()`
- [x] `EmpresaUsuarioController::precios()` — GET muestra tabla de productos + precios, POST guarda
- [x] Vista `empresa/usuarios/precios_comprador.php` — tabla inline con toggle y cálculo de diferencia
- [x] Lista de usuarios: link "Precios especiales" visible solo para compradores

**C — Pedido Personalizado**
> El admin_empresa o supervisor puede crear un pedido especial para un comprador con precios negociados ad-hoc, fuera del flujo estándar del carrito.

- [x] `PedidoModel::crearPersonalizado()` — transacción: crea pedido + detalle con tipo='personalizado'
- [x] `PedidoModel::listadoEmpresa()` — añadidos filtros: tipo, fecha_desde, fecha_hasta
- [x] `EmpresaPedidoController` — creado completo:
  - [x] `index()` — lista pedidos empresa con filtros + badge "Personalizado"
  - [x] `cambiarEstado()` — modal de cambio de estado
  - [x] `personalizado()` — formulario dinámico con líneas add/remove
  - [x] `guardarPersonalizado()` — valida y crea pedido personalizado
- [x] Vista `empresa/pedidos/empresa_index.php` — tabla con modal de estado
- [x] Vista `empresa/pedidos/personalizado.php` — formulario dinámico con JS + cálculo de total
- [x] Sidebar empresa: "Pedidos" → empresa-pedido, "Pedido personalizado" como ítem separado

**D — EmpresaPedidoController — Vista general pedidos** ✅ (incluido en C)

**E — Flujo de Pedido Completo (revisión 2026-05-05)**
> Corrección de lógica de negocio: sucursal=comprador, sin stock visible al comprador, flujo con revisión empresa.

- [x] `migrations/008_flujo_pedido_entrega.sql` — pedidos: tipo_entrega, repartidor_asignado_id, costo_envio, nota_empresa, foto_comprobante_path, foto_entrega_path · usuarios: direccion_entrega, referencia_entrega, lat_entrega, lng_entrega
- [x] `PedidoModel::asignarEntrega()` — empresa asigna tipo y repartidor
- [x] `PedidoModel::aprobarPedido()` — confirmar + recalcular total con envío
- [x] `PedidoModel::rechazarPedido()` — cancelar con nota
- [x] `PedidoModel::subirComprobante()` — comprador sube comprobante → en_preparacion
- [x] `PedidoModel::subirFotoEntrega()` — empresa/repartidor sube foto → entregado
- [x] `EmpresaPedidoController::asignarEntrega()` — modal de asignación
- [x] `EmpresaPedidoController::aprobar()` / `rechazar()` / `subirFotoEntrega()` — acciones inline
- [x] `PedidoController::subirComprobante()` — comprador sube comprobante desde detalle
- [x] `CarritoController` simplificado — 3 pasos (sin paso2 sucursales), usa `getPrecioFinal()`
- [x] Catálogo y carrito sin stock visible para compradores
- [x] Vista `empresa_index.php` — modal revisar (asignar+aprobar+rechazar), badge pendientes, badge comprobante
- [x] Vista `detalle.php` — comprobante upload para comprador en estado confirmado, foto entrega, costo envío desglosado
- [x] `EmpresaUsuarioController::guardar()` — guarda `direccion_entrega` en usuarios al crear comprador

**Modelo Sucursal = Comprador (definitivo 2026-05-05)**
```
Empresa (productor) — UN solo inventario
├── Comprador "Taquería El Buen Sabor — Norte"  ← usuario comprador, 1 punto de entrega
├── Comprador "Taquería El Buen Sabor — Sur"    ← usuario comprador, 1 punto de entrega
└── Comprador "Restaurante Las Flores"           ← usuario comprador, 1 punto de entrega
```
- La empresa crea un comprador por cada punto de entrega de su cliente
- Tabla `sucursales` se mantiene para Sprint 4D (rutas con múltiples paradas)
- No hay distribución multi-sucursal en el carrito del MVP

**Flujo de pedido completo (post-revisión)**
```
Comprador → solicita (pendiente)
Empresa → asigna tipo_entrega + costo_envio → aprueba (confirmado) o rechaza (cancelado)
Comprador → ve total final → sube comprobante de pago → en_preparacion
Empresa/Repartidor → "En camino" → en_ruta → sube foto entrega → entregado
```

**F — GPS — Sprint 4D (plan, no implementar ahora)**
- Google Maps JS API: mapa con marcador origen (empresa) → destino (lat_entrega del comprador)
- Repartidor móvil: `navigator.geolocation.watchPosition()` → POST `/api/posicion` cada 30s
- `ruta_detalle.lat_actual` + `lng_actual` ya existen en schema
- Comprador: iframe con posición del repartidor en tiempo real

**G — UI Productos mejorada ✅ COMPLETADO (2026-05-05)**
- [x] Formulario producto rediseñado con 3 secciones guiadas: Información básica · Precios y rangos · Stock inicial
- [x] Preview en tiempo real del precio al configurar
- [x] Precios escalonados con filas add/remove dinámicas y leyenda explicativa
- [x] Helper text en cada campo explicando su propósito
- [x] Nota clara: "Los compradores NO ven el stock"

**Bugs corregidos en producción (2026-05-05):**
- [x] `PedidoModel::listadoEmpresa()` — eliminado `COALESCE(p.tipo,"normal") AS tipo` (duplicaba columna de `p.*` → MySQL 5.7 error en paginate COUNT subquery)
- [x] `EmpresaPedidoController` — reemplazados 3 usos de métodos `protected` (query/queryOne) por llamadas públicas correctas: `getByRolEmpresa()` y nuevo `countPendientes()`
- [x] `CarritoController.php` — eliminado código duplicado fuera de la clase (causaba error de sintaxis PHP)
- [x] `paso1.php` — eliminada versión antigua con columna Stock visible al comprador; sin stock en vista del comprador
- [x] `EmpresaInventarioController` — corregidos 3 bugs: `userId()` → `usuarioId()`, llamadas a métodos `protected` (`queryOne`, `execute`) reemplazadas por métodos públicos nuevos en `ProductoModel` (`perteneceAEmpresa`, `conStockDetalleEmpresa`, `ajustarInventarioDirecto`)
- [x] `MovimientoInventarioModel::historialProducto()` — eliminada columna `u.rol_slug` que no existe en `usuarios` sin JOIN de roles
- [x] `detalle.php` — eliminado bloque duplicado (líneas 197+); añadido display de precio original vs ajustado con diff visual en verde
- [x] `paso3.php` — actualizado indicador de pasos a 3 pasos; eliminada sección "Distribución por sucursal" obsoleta

**H — Combos por Comprador ✅ COMPLETADO (2026-05-05)**
- [x] `migrations/009_combos_comprador.sql` — tablas `combos`, `combo_items`, `combo_compradores`
- [x] `ComboModel` — CRUD, `getItems()`, `getCompradores()`, `getCombosParaComprador()`, `guardarItems()`, `guardarCompradores()`
- [x] `EmpresaComboController` — index, nuevo, guardar, editar, actualizar, eliminar, activar
- [x] Vistas `empresa/combos/index.php`, `empresa/combos/form.php`
- [x] Route `empresa-combo` → `EmpresaComboController` en `index.php`
- [x] Sidebar admin: "Combos por comprador" bajo Operación
- [x] `CarritoController::cargarCombo()` — carga combo en sesión de carrito (merge de cantidades)
- [x] `paso1.php` — sección de combos del comprador al inicio con botón "Cargar"

**I — Ajuste de precios en aprobación ✅ COMPLETADO (2026-05-05)**
- [x] `migrations/010_precio_ajuste_pedido.sql` — ADD `precio_original DECIMAL(10,2) NULL` en `pedido_detalle`
- [x] `PedidoModel::aprobarPedido(ajustes[])` — acepta array de precio ajustado por `detalle_id`; solo permite bajar; guarda `precio_original`; recalcula subtotal y total
- [x] `PedidoModel::getItemsPedido()` — retorna items para el modal AJAX
- [x] `EmpresaPedidoController::itemsJson()` — endpoint JSON de items de un pedido (verificación de pertenencia)
- [x] `EmpresaPedidoController::aprobar()` — pasa `$_POST['ajustes']` al modelo
- [x] `empresa_index.php` — modal Revisar: carga items via AJAX al abrir, muestra precios editables con límite máximo = precio original; inputs fluyen al formAprobar
- [x] `detalle.php` — column "Precio unit." muestra precio tachado + nuevo precio en verde + descuento % cuando fue ajustado

**J — UX Catálogo + Carrito: Modal AJAX + Tiempo Real ✅ COMPLETADO (2026-05-05)**
- [x] `catalogo/index.php` — reescrito completo:
  - [x] Imágenes corregidas: eliminado doble-prefijo `UPLOAD_URL`, ahora usa `$prod['imagen']` directamente
  - [x] Pre-carga de `escalonados` por producto en PHP (`$productoModelCat->getEscalonados()`)
  - [x] "Ver precios" → modal en modo lectura (sin botón Agregar para roles sin carrito)
  - [x] "+ Agregar" → modal AJAX: cantidad, tabla de precios por volumen, estimación de precio/subtotal
  - [x] Precio se actualiza en tiempo real vía `fetch('/api/precios/{id}')` con 280ms debounce
  - [x] Alertas dinámicas: verde "Ahorrando X%" cuando aplica descuento; amarillo "Agrega N más → precio Y"
  - [x] Fila activa de tramos resaltada en verde según cantidad ingresada
  - [x] AJAX POST a `carrito/agregarProducto` — sin salir del catálogo; badge del carrito actualiza sin reload
- [x] `CarritoController::agregarProducto()` — nuevo endpoint AJAX:
  - Valida producto activo y pertenece a empresa del comprador
  - Merge de cantidad si producto ya estaba en carrito (recalcula precio con nuevo total)
  - Retorna `{ok, msg, total_items}` para actualizar badge
- [x] `paso1.php` — precios actualizados en tiempo real con `oninput` + debounce 350ms por producto; muestra "..." mientras carga
- [x] `empresa_index.php` — fixes UX pedidos admin:
  - [x] Eliminado botón "+ Pedido Personalizado" del listado
  - [x] "Costo de envío" se oculta automáticamente cuando se selecciona "Pickup"
  - [x] Botón "Aprobar" sincroniza campos de entrega (tipo, repartidor, costo, nota) al formAprobar antes de submit — ya no es necesario "Guardar asignación" antes
- [x] `EmpresaPedidoController::aprobar()` — también guarda asignación de entrega si `tipo_entrega` viene en POST
- [x] `detalle.php` — banner "En camino 🚚" para comprador cuando `estado = 'en_ruta'`:
  - Tipo de entrega, repartidor asignado (nombre), fecha estimada

**K — Flujo de Pago Completo + Tipo de Entrega + Comprobante + Dirección ✅ COMPLETADO (2026-05-05)**
> Bug crítico corregido: `CarritoController::confirmar()` no guardaba `metodo_pago` ni `tipo_entrega` en BD.
> Todas las pantallas ahora guían al usuario paso a paso y mantienen el estado visible en todo momento.

- [x] **Bug fix**: `CarritoController::confirmar()` — ahora guarda `metodo_pago`, `tipo_entrega`, `direccion_entrega`, `referencia_entrega`, `lat_entrega`, `lng_entrega` al crear el pedido
- [x] `migrations/011_checkout_entrega_direccion.sql` — ADD columnas snapshot de dirección en `pedidos` (`direccion_entrega`, `referencia_entrega`, `lat_entrega`, `lng_entrega`) · ADD `lat` y `lng` en `empresas` para comparativa de ubicación
- [x] `CarritoController::resumen()` — carga y pasa `$comprador` (usuario con dirección guardada) y `$empresa` al view paso3
- [x] `CarritoController::confirmado()` — carga el pedido desde BD para pasarlo a paso4 (necesario para mostrar `tipo_entrega` en timeline)
- [x] **`paso3.php` (resumen)** — reescrito completo:
  - Selector visual de tipo de entrega con tarjetas interactivas: 🏭 "Recoger en bodega" / 🚚 "Envío a domicilio"
  - Bloque **pickup**: muestra `empresas.direccion_fiscal` como punto de retiro
  - Bloque **repartidor**: muestra dirección guardada del perfil con botón "Cambiar"; si no tiene, muestra campos editables
  - JS: toggle visual de tarjetas + mostrar/ocultar bloques según selección
  - `direccion_entrega` y `referencia_entrega` se envían al `confirmar()`
- [x] **`paso4.php` (confirmado)** — reescrito con timeline "¿Qué sigue?":
  - 4 pasos visuales: ✓ Pedido registrado · ⏳ Revisión del equipo · ○ Sube comprobante · ○ Entrega/Recoger
  - Paso 4 se adapta al `tipo_entrega` del pedido (pickup vs. repartidor)
- [x] **`perfil.php`** — nueva sección "Dirección de entrega" visible **solo para compradores** (`$rol === 'comprador'`):
  - Campos: `direccion_entrega` (textarea), `referencia_entrega`, `lat_entrega`/`lng_entrega` (ocultos, para futuro mapa)
- [x] **`CuentaController::guardarDireccion()`** — nuevo método; guarda `direccion_entrega`, `referencia_entrega`, `lat_entrega`, `lng_entrega` en `usuarios`; ruta `cuenta/guardarDireccion`
- [x] **`detalle.php`** — reescrito con bloques contextuales por estado y rol:
  - **Barra de progreso/timeline** con 5 estados: pendiente → aprobado → en preparación → en camino → entregado
  - Estado **`pendiente`**: bloque azul "Tu pedido está en revisión"
  - Estado **`confirmado`**: bloque upload comprobante (ya existía) + muestra tipo de entrega y método de pago
  - Estado **`en_preparacion`**: bloque violeta; si pickup → muestra dirección fiscal de empresa; si repartidor → muestra dirección de entrega y aviso de repartidor próximo
  - Estado **`en_ruta`**: banner naranja con repartidor + fecha estimada (ya existía, mantenido)
  - Estado **`entregado`**: banner verde confirmación
  - **Admin**: sección "Comprobante de pago" siempre visible — muestra preview de imagen o "Sin comprobante aún"
  - Panel lateral: muestra `tipo_entrega`, `metodo_pago`, y bloque de dirección de entrega si aplica
- [x] **`empresa_index.php`** — mejoras admin:
  - Nuevo alert banner verde cuando hay pedidos con `foto_comprobante_path IS NOT NULL AND estado = 'en_preparacion'` (comprobantes pendientes de revisar)
  - Badge `💳 Comprobante` visible y prominente en filas que tienen comprobante adjunto
  - Botón **"✓ Recogido"** para pedidos `tipo_entrega='pickup'` en estado `en_preparacion`/`en_ruta` — confirma entrega sin requerir foto
  - Botón **"📷 Entrega"** ahora solo aparece para pedidos con repartidor
- [x] `PedidoModel::countConComprobantePendiente(int $empresaId)` — nuevo método para el badge admin
- [x] `EmpresaPedidoController::index()` — agrega `$countConComprobante` para pasar al view

**Flujo de pedido completo (post K)**
```
Comprador:
  1. Carrito paso 3 → elige metodo_pago + tipo_entrega (pickup/repartidor) + dirección
  2. Confirmar → pedido en BD con todos los campos guardados
  3. paso4 → timeline "¿Qué sigue?" con 4 pasos claros

Empresa (admin/supervisor):
  4. Revisar pedido → asignar entrega + aprobar/rechazar
  5. Si pickup: botón "✓ Recogido" cuando el cliente pasa a recoger (sin foto)
  6. Si repartidor: asignar repartidor → repartidor sube foto de entrega → entregado

Comprador:
  3b. Si aprobado → detalle muestra bloque "Sube tu comprobante"
  3c. Sube imagen de pago → estado en_preparacion
  3d. Detalle muestra estado actual con instrucciones para cada paso

Repartidor (ya existía desde 4C-1):
  - confirmarEntrega() con firma digital + foto → pedido entregado
```

**F — Email Service (movido de prioridad — hacer cuando SMTP esté configurado en cPanel)**
> El admin puede seguir viendo la contraseña generada como fallback mientras no haya SMTP activo.
- [ ] `app/services/EmailService.php` con PHPMailer
- [ ] Template HTML: bienvenida con credenciales
- [ ] Prerrequisito: superadmin configura SMTP en `/config/correo`

---

### Sprint 4C-IA — Reconocimiento de Facturas para Stock (🔮 FUTURO)
> El objetivo es que el admin/supervisor pueda fotografiar una factura de compra y el sistema registre automáticamente los movimientos de entrada sin captura manual.

**Casos de uso:**
1. Admin toma foto de factura con la cámara del celular → el sistema extrae: proveedor, lista de productos, cantidades, montos → propone movimiento de entrada → admin confirma
2. Admin dicta por voz: "Entraron 50 kilos de arrachera y 30 kilos de lomo" → el sistema parsea y propone el movimiento

**Stack propuesto:**
- **API vision**: Claude Vision (Anthropic API) o GPT-4V para OCR + extracción estructurada de facturas
- **API voz**: Web Speech API del navegador (sin backend, sin costo) para transcripción de voz a texto
- Endpoint PHP: `POST /api/ia/analizarFactura` — recibe imagen base64 → llama a API externa → devuelve JSON con productos detectados
- Endpoint PHP: `POST /api/ia/analizarVoz` — recibe texto transcrito → parsea con regex o LLM → devuelve JSON

**Flujo UI:**
```
Modal "Entrada rápida IA" en /empresa-inventario
    ├── [📷 Subir/Tomar foto] → OCR factura → tabla propuesta (editable)
    └── [🎤 Dictado de voz]   → transcripción → parseo → tabla propuesta (editable)
         ↓
    [Confirmar] → POST /empresa-inventario/guardarMovimientosLote
```

**Migraciones necesarias:**
- Ninguna — usa la tabla existente `movimientos_inventario` con `tipo='entrada'`
- Opcional: campo `fuente_ia TINYINT(1)` para auditoría de movimientos generados por IA

**Prerrequisitos:**
- Clave API de Anthropic o OpenAI configurada en `global_settings` (superadmin)
- HTTPS activo en producción (Web Speech API requiere HTTPS)
- Servidor con soporte de `cURL` para llamadas a API externa

**Estimación:** Sprint completo (3-4 días de desarrollo + testing)

### Sprint 4C-2 — Portal Supervisor funcional 🔄 SIGUIENTE
- [ ] `SupervisorController::dashboard()` — cola de pedidos + KPIs del día
  - [ ] Pedidos pendientes de aprobación (lista + modal aprobar/rechazar)
  - [ ] Resumen operativo: pedidos hoy, entregados, en ruta
  - [ ] Acceso a movimientos de inventario (entradas/salidas delegadas desde admin)
  - [ ] Vista `supervisor/dashboard.php`
- [ ] Sidebar supervisor: verificar rutas de aprobación, límites e inventario

### Sprint 4C-3 — Portal Comprador funcional 🔄 SIGUIENTE
- [ ] `CompradorController::inicio()` — bienvenida + últimos pedidos + acceso rápido al catálogo
  - [ ] Vista `comprador/inicio.php`
- [ ] Verificar flujo completo: inicio → catálogo (con precios especiales si aplica) → carrito → confirmar pedido
- [ ] Verificar que límites de compra bloquean correctamente cuando están activos

### Sprint 4C-4 — Detalle de producto + Descuentos en pedidos (pendiente)
- [ ] **Página de detalle de producto** (`/catalogo/detalle/{id}` o modal expandido):
  - [ ] Foto ampliada, descripción completa, tabla de precios escalonados, precio especial del comprador si aplica
  - [ ] Botón "+ Agregar" desde el detalle (igual que en el catálogo)
  - [ ] El admin puede agregar esta info desde `/empresa-producto/editar/{id}` (campo descripción larga)
- [ ] **Descuentos visibles en detalle de pedido**:
  - [ ] Si el pedido tiene precio escalonado, mostrar ahorro por volumen vs. precio base en cada línea
  - [ ] Resumen al pie: "Ahorro total por volumen: $X"
- [ ] **Límites de compra en el carrito**:
  - [ ] `LimiteController` — supervisor configura máximos por comprador/producto/mes
  - [ ] `CarritoController::actualizar()` — validar límites antes de crear pedido; mostrar error descriptivo

### Sprint 4D — Sucursales del Comprador (CRUD) + Vehículos
> Las sucursales son los PUNTOS DE ENTREGA del comprador (no almacenes del productor).

- [ ] `EmpresaSucursalController` — gestión de sucursales (puntos de entrega de compradores)
  - [ ] index: listado de sucursales de todos los compradores de la empresa
  - [ ] form: crear/editar con picker de coordenadas en mapa Leaflet
  - [ ] toggle activo/inactivo
  - [ ] Ruta: `empresa-sucursal/*`
- [ ] `EmpresaVehiculoController` — vehículos y asignación a repartidores
  - [ ] index: listado de vehículos con estado y repartidor asignado
  - [ ] form: alta de vehículo (placa, modelo, capacidad)
  - [ ] Asignación en tabla `repartidor_vehiculo`
  - [ ] Ruta: `empresa-vehiculo/*`
- [ ] Al crear repartidor: guardar datos de vehículo en `repartidor_vehiculo` + `vehiculos`

### Sprint 4D+ — Almacenes del Productor (futuro, no MVP)
> Si el productor tiene múltiples bodegas/centros de distribución propios con stock independiente.
- [ ] Nueva tabla `almacenes_empresa` (diferente a `sucursales` que son del comprador)
- [ ] Stock por almacén en `inventario` + columna `almacen_id`
- [ ] Transferencias internas entre almacenes (`transferencias_almacen`)
- [ ] Interfaz para mover stock entre almacenes

### Sprint 5P — Pagos empresa→comprador (simulados / mock)
> El Admin Empresa necesita recibir pagos de sus compradores. Se simula la pasarela para que el flujo se vea completo; la integración real con PayPal/Stripe va después.

- [ ] **Flujo simulado de pago al confirmar pedido:**
  - Al confirmar pedido (paso 4 del carrito), comprador ve pantalla "Selecciona método de pago"
  - Opciones: Transferencia bancaria · Efectivo al repartidor · Tarjeta (simulado)
  - Modal "Tarjeta simulada": formulario con número ficticio → botón "Pagar $X" → siempre aprueba
  - Estado pedido pasa a `pagado_simulado` (diferente a `pagado_real` en sprint posterior)
- [ ] **`PagoController`** — acciones:
  - `seleccionarMetodo($pedidoId)` — pantalla elección de método
  - `procesarSimulado($pedidoId)` — mock: inserta registro en tabla `pagos` con `metodo=mock`, `estado=aprobado`
  - `comprobante($pagoId)` — PDF/vista del comprobante
  - `registrarTransferencia($pedidoId)` — comprador sube foto del comprobante bancario
- [ ] **`PagoModel`** — tabla `pagos` ya existe en schema, métodos: `crear`, `getByPedido`, `getByEmpresa`, `pendientes`
- [ ] **Dashboard Admin Empresa — sección cobros:**
  - `EmpresaCobroController` — lista de pedidos pendientes de cobro agrupados por comprador
  - Vista `empresa/cobros/index.php` — tabla: comprador · pedido · monto · método elegido · estado pago · acción (confirmar/rechazar)
  - Admin puede marcar "Cobro confirmado" en transferencias/efectivo
- [ ] **Configuración de métodos de cobro del Admin Empresa:**
  - Vista `empresa/cobros/configurar.php` — toggles: habilitar/deshabilitar cada método
  - Datos bancarios (para transferencia): CLABE, banco, nombre titular
- [ ] Rutas: `pago/*` (comprador), `empresa-cobro/*` (admin_empresa)
- [ ] **Sin integración PayPal real en este sprint** — se deja preparado para reemplazar mock

### Sprint 5 — Pagos reales y Facturación
- [ ] Reemplazar mock de tarjeta con `PayPalPagoService` (checkout pedidos individuales, diferente a suscripciones)
- [ ] `FacturaloService` — CFDI automático al confirmar pago
- [ ] Vista de facturas descargables para el cliente

### Sprint 6 — Notificaciones y Pedidos avanzados
- [ ] `NotificacionService` — Eventos WhatsApp Cloud API (EmailService ya existe desde 4C-1)
- [ ] Eventos: pedido confirmado, en ruta, próximo (<1km), entregado
- [ ] `RecurrenteController` — plantillas + generación automática
- [ ] `LimiteController` — supervisor configura límites por sucursal/producto

### Sprint 7 — Reportes y Analítica
- [ ] `EmpresaReporteController` — consumo mensual, gasto por sucursal, top productos
- [ ] `PanelReporteController` — ventas globales con gráficas Chart.js (solo lectura para superadmin)
- [ ] Exportar Excel (PhpSpreadsheet) y PDF (Dompdf)

---

## PAYPAL SUBSCRIPTIONS — CONFIGURACIÓN PASO A PASO

> El botón "Configurar PayPal" en `/suscripcion/configurar` ya existe. Solo falta poner los Plan IDs reales.

### Paso 1 — Crear cuenta de desarrollador y app
1. Ve a [developer.paypal.com](https://developer.paypal.com) → inicia sesión con tu cuenta PayPal
2. En **Dashboard → My Apps & Credentials** → clic "Create App"
3. Ponle nombre (ej: "CarniHub"), tipo: **Merchant**
4. Copia el **Client ID** y **Secret** → guárdalos en CarniHub en `/config/apis` (campos `paypal_client_id` y `paypal_secret`)
5. En `/config/apis` también pon `paypal_mode = sandbox` para pruebas (cambiar a `live` en producción)

### Paso 2 — Crear productos en PayPal
1. En el panel de developer → **Sandbox → Subscriptions → Products** → "Create Product"
2. Crea **un producto por plan**:
   - Nombre: "CarniHub Básico", tipo: SERVICE, categoría: SOFTWARE
   - Nombre: "CarniHub Pro", tipo: SERVICE
   - Nombre: "CarniHub Empresa", tipo: SERVICE
3. Guarda el **Product ID** de cada uno (empieza con `PROD-`)

### Paso 3 — Crear Billing Plans
1. En **Sandbox → Subscriptions → Plans** → "Create Plan"
2. Por cada producto creado:
   - Selecciona el producto correspondiente
   - Nombre del plan (ej: "Plan Básico Mensual")
   - Ciclo de facturación: **Monthly**, precio en MXN
   - `$2,600 MXN` para Básico · `$3,200 MXN` para Pro · `$4,000 MXN` para Empresa
3. Activa el plan (status: ACTIVE)
4. Copia el **Plan ID** de cada uno (empieza con `P-`)

### Paso 4 — Configurar en CarniHub
1. Entra como superadmin → `/suscripcion/configurar`
2. Pega cada Plan ID en el campo correspondiente → "Guardar IDs"
3. Configura también el Webhook ID:
   - En developer.paypal.com → **Webhooks → Add Webhook**
   - URL: `https://tudominio.com/carnihub/suscripcion/webhook`
   - Eventos a suscribir: `BILLING.SUBSCRIPTION.ACTIVATED`, `BILLING.SUBSCRIPTION.SUSPENDED`, `BILLING.SUBSCRIPTION.CANCELLED`, `PAYMENT.SALE.COMPLETED`
   - Copia el **Webhook ID** → guárdalo en `/config/apis` campo `paypal_webhook_id`

### Paso 5 — Por qué no se ven suscripciones ahora
El panel muestra vacío porque la empresa demo del seed no tiene un registro en la tabla `suscripciones`.
Esto se corrige con el fix del **Sprint 4C-0 Bug 3** (INSERT en migration).
Cuando el superadmin crea una empresa nueva via `/panel-empresa/nueva`, la suscripción se crea automáticamente en ese momento.

### Prueba en Sandbox
- Usa la cuenta de comprador sandbox de PayPal (en developer.paypal.com → Sandbox Accounts)
- Flujo empresa: `/empresa-suscripcion/planes` → elige plan → PayPal checkout → regresa → estado activo
- El webhook dispara automáticamente cuando el pago se procesa

---

## TECNOLOGÍAS OPEN SOURCE

| Capa | Librería | Versión | Para qué |
|------|---------|---------|----------|
| Backend | PHP 8.3 | — | Servidor |
| BD | MySQL / MariaDB | 5.7+ | Datos |
| CSS | Tailwind CSS | CDN | Estilos |
| Mapas | Leaflet.js | CDN | Mapas y tracking |
| Mapas base | OpenStreetMap | Gratuito | Tiles sin costo |
| Gráficas | Chart.js | CDN | KPIs y reportes |
| GPS servidor | Traccar | Self-hosted | Rastreo real-time |
| Email | PHPMailer | Composer | SMTP — **adelantado a Sprint 4C-1** (credenciales a usuarios) |
| Excel | PhpSpreadsheet | Composer | Exportar reportes |
| PDF | Dompdf | Composer | Facturas/reportes |
| WhatsApp | Meta Cloud API | Gratuito (hasta límite) | Notificaciones |
| Facturación | Factura-lo API | De pago | CFDI México |

---

## INFRAESTRUCTURA — NOTAS IMPORTANTES

### `.htaccess`
```apache
RewriteBase /carnihub/   ← DEBE coincidir con carpeta en servidor
```

### Credenciales BD (`config/database.php`)
```
Host: localhost
BD: idactivo_carnihubdb
Usuario: carnihubdb_admin
```

### Carpetas que deben existir en servidor (permisos 755)
```
public/uploads/
public/uploads/productos/
public/uploads/firmas/
public/uploads/entregas/
public/uploads/logos/
public/uploads/comprobantes/
public/uploads/avatars/
```

### Constantes en `config/config.php`
| Constante | Valor |
|-----------|-------|
| `BASE_URL` | Auto-detectado (https://dominio.com/carnihub/) |
| `SESSION_NAME` | `carnihub_session` |
| `UPLOAD_PATH` | Ruta filesystem a `public/uploads/` |
| `UPLOAD_URL` | URL pública a `public/uploads/` |
| `PER_PAGE` | 20 (paginación) |

---

## SERVICIOS EXTERNOS NECESARIOS

| Servicio | Clave en global_settings | Para qué |
|----------|--------------------------|---------|
| Google Maps | `google_maps_key` | Mapas en sucursales y logística |
| WhatsApp | `whatsapp_api_token`, `whatsapp_phone_id` | Notificaciones |
| Traccar | `traccar_url`, `traccar_user`, `traccar_pass` | GPS real-time |
| Factura-lo | `facturalo_api_key` | CFDI automático |
| PayPal | `paypal_client_id`, `paypal_secret`, `paypal_mode` | Pagos con tarjeta |
| SMTP | `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass` | Email sistema |
| Shelly Cloud | `shelly_api_url`, `shelly_auth_key` | IoT enchufes |
| HikVision | `hikvision_host`, `hikvision_user`, `hikvision_pass` | Cámaras |

Todos se configuran desde `/config/apis` y `/config/correo` (solo visible para superadmin).

---

*Última actualización: 2026-05-05 — v2.6.4 (Sprint 4C-1 K: flujo de pago completo — tipo_entrega + comprobante + dirección de entrega · Fix crítico: confirmar() ahora guarda metodo_pago y tipo_entrega · paso3 con tarjetas pickup/repartidor y dirección del comprador · paso4 con timeline "¿Qué sigue?" · perfil comprador con sección dirección de entrega · detalle con barra de progreso por 5 estados + bloques contextuales · empresa_index con badge comprobante + botón "Recogido" para pickup · migration 011 snapshot dirección en pedidos · EmpresaPedidoController ahora ✅ Funcional)*
