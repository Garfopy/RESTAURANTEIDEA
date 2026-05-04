# CarniHub — Plan v2.1
**Versión:** 2.1.0 | **Fecha:** 2026-05-04 | **Stack:** PHP 8.3 · MySQL · Tailwind CDN · MVC sin framework

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

> ⚠️ Cambia las contraseñas antes de pasar a producción real.

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

### Super Admin — solo monitoreo de plataforma

El superadmin **nunca** toca productos, inventario, pedidos ni ninguna operación interna de las empresas. Su función es garantizar que la plataforma funcione correctamente.

| Capacidad | Descripción |
|-----------|-------------|
| Dashboard de plataforma | KPIs globales: empresas activas, pedidos del sistema, volumen de ventas (solo lectura) |
| Estado de APIs y servicios | Google Maps, WhatsApp, Traccar, PayPal, Factura-lo, Shelly, HikVision |
| Métricas y salud del sistema | Logs de error, intentos de login, actividad del sistema |
| Configuración global | Logo, colores, claves de API, configuración SMTP |
| Gestionar empresas | Crear/activar/desactivar cuentas de Admin Empresa |
| Crear Admin CarniHub | Para soporte interno del sistema |
| Ver analítica de ventas | Reportes globales entre todas las empresas (solo lectura, sin modificar nada) |

### Admin Empresa — control total de su empresa

El Admin Empresa es el productor de carne. Tiene control completo sobre su propia operación.

| Capacidad | Descripción |
|-----------|-------------|
| Dashboard de empresa | Ventas, pedidos pendientes, alertas de stock, equipo activo |
| Catálogo de productos | CRUD de productos, precios escalonados, imágenes, categorías |
| Inventario | Stock, alertas de mínimo, ajustes manuales |
| Pedidos de su empresa | Ver todos los pedidos, cambiar estado, aprobar, cancelar |
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
| CuentaController | ✅ Funcional | Todos | Perfil, guardar datos, cambiar contraseña, avatar |
| CarritoController | ✅ Funcional | comprador | 4 pasos: productos → sucursales → resumen → confirmar |
| PedidoController | ✅ Funcional | comprador, supervisor, admin_empresa | Historial, detalle, aprobación, tracking GPS, cancelar |
| ConfigController | ✅ Completo | superadmin | general (logo+colores), apis (claves), correo (SMTP) |
| PanelUsuarioController | ✅ Completo | superadmin, admin | CRUD usuarios de plataforma (admin, admin_empresa) + toggle |
| PanelLogisticaController | ⚠️ Reasignar | superadmin → admin_empresa | Actualmente en /panel/ pero debe ser de admin_empresa |
| PanelProductoController | ⚠️ Reasignar | admin_empresa | Actualmente en /panel/ → renombrar a EmpresaProductoController |
| PanelInventarioController | ⚠️ Reasignar | admin_empresa | Actualmente en /panel/ → renombrar a EmpresaInventarioController |
| PanelPedidoController | ⚠️ Ajustar | superadmin (solo lectura) | Vista global de todos los pedidos para monitoreo |
| SupervisorController | ❌ Pendiente | supervisor | Panel dedicado de supervisor |
| CompradorController | ❌ Pendiente | comprador | Portal de compras del cliente |
| EmpresaProductoController | ❌ Pendiente | admin_empresa | CRUD catálogo de la empresa (migrar desde PanelProductoController) |
| EmpresaInventarioController | ❌ Pendiente | admin_empresa | Stock de la empresa (migrar desde PanelInventarioController) |
| EmpresaLogisticaController | ❌ Pendiente | admin_empresa | Rutas y logística de su empresa |
| EmpresaPedidoController | ❌ Pendiente | admin_empresa | Pedidos de su empresa (consolidar) |
| PanelReporteController | ❌ Pendiente | superadmin, admin | Reportes globales de plataforma (solo lectura) |
| RecurrenteController | ❌ Pendiente | comprador, admin_empresa | Plantillas de pedido automático |
| LimiteController | ❌ Pendiente | supervisor, admin_empresa | Límites de compra |
| EmpresaReporteController | ❌ Pendiente | admin_empresa | Reportes de su empresa |
| PagoController | ❌ Pendiente | comprador, admin_empresa | Comprobantes, PayPal, crédito |
| EmpresaSucursalController | ❌ Pendiente | admin_empresa | CRUD sucursales con mapa |
| EmpresaVehiculoController | ❌ Pendiente | admin_empresa | Vehículos + asignación repartidores |

### Models (7 — en `app/models/`)

| Model | Estado |
|-------|--------|
| BaseModel | ✅ CRUD + paginate |
| UsuarioModel | ✅ getByEmail, getByEmpresa, rolesPermitidos, crear, getConRol, getRolPorSlug, getRepartidoresGlobal |
| EmpresaModel | ✅ listado con filtros, estadísticas, listadoSimple |
| ProductoModel | ✅ listadoConPrecio, getPrecioParaCantidad, getEscalonados, getCategorias, listadoAdmin, listadoInventario, ajustarStock, actualizarEscalonados, inicializarInventario, actualizarInventario |
| PedidoModel | ✅ generarFolio, crear (transacción), listadoEmpresa, pendientesAprobacion, conDetalle, aprobar, rechazar, tracking, listadoGlobal, cambiarEstado, crearRuta, listadoConfirmadosPorEmpresa |
| ConfigModel | ✅ get, set, getGrupo, getAll, guardarGrupo |
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
| `migrations/001_schema_completo.sql` | ✅ Schema completo + seed demo |

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

### Sprint 4B — Panel de Plataforma (superadmin/admin) ✅ PARCIALMENTE COMPLETADO
> ⚠️ Algunos controllers se construyeron correctamente, otros fueron asignados al rol incorrecto y deben migrar.

- [x] `PanelUsuarioController` — CRUD usuarios plataforma (admin, admin_empresa) + toggle ✅ CORRECTO
- [x] `PanelPedidoController` — vista global de pedidos (solo lectura para superadmin) ✅ CORRECTO
- [x] Vistas panel/usuarios/ y panel/pedidos/ ✅ CORRECTAS
- [⚠️] `PanelProductoController` — construido, pero debe ser `EmpresaProductoController` (admin_empresa)
- [⚠️] `PanelInventarioController` — construido, debe ser `EmpresaInventarioController` (admin_empresa)
- [⚠️] `PanelLogisticaController` — construido, debe ser `EmpresaLogisticaController` (admin_empresa)
- [⚠️] Vistas panel/productos/, panel/inventario/, panel/logistica/ — construidas, deben migrar a empresa/

### Sprint 4C — Portales dedicados por rol + Migración de controllers
> Corrección de la arquitectura de roles para que cada rol tenga su portal propio.

- [ ] **SupervisorController** — portal dedicado para supervisor
  - [ ] `supervisor/dashboard` — cola de aprobación + resumen operativo
  - [ ] Mueve lógica de aprobación desde PedidoController
  - [ ] Vista `supervisor/dashboard.php`
- [ ] **CompradorController** — portal de compras dedicado para comprador
  - [ ] `comprador/inicio` — bienvenida con últimos pedidos y acceso rápido al catálogo
  - [ ] Vista `comprador/inicio.php`
- [ ] **EmpresaProductoController** — migrar desde PanelProductoController
  - [ ] CRUD catálogo: crear, editar, desactivar producto
  - [ ] Precios escalonados + upload de imagen
  - [ ] Ruta: `empresa-producto/*`
- [ ] **EmpresaInventarioController** — migrar desde PanelInventarioController
  - [ ] Stock por empresa + alertas + modal ajuste
  - [ ] Ruta: `empresa-inventario/*`
- [ ] **EmpresaLogisticaController** — migrar desde PanelLogisticaController
  - [ ] Rutas de la empresa + mapa Leaflet + crear ruta + asignar repartidor
  - [ ] Ruta: `empresa-logistica/*`
- [ ] **EmpresaPedidoController** — gestión de pedidos para admin_empresa
  - [ ] Todos los pedidos de su empresa + detalle + cambiar estado AJAX
  - [ ] Ruta: `empresa-pedido/*`
- [ ] Actualizar Router en `index.php` con las nuevas rutas
- [ ] Actualizar sidebar de `/empresa/` para admin_empresa con los nuevos menús
- [ ] Quitar del sidebar de `/panel/` las secciones de productos/inventario/logística (no corresponden a superadmin)

### Sprint 5 — Sucursales y Vehículos del Admin Empresa
- [ ] `EmpresaSucursalController` — CRUD sucursales con mapa Leaflet
- [ ] `EmpresaVehiculoController` — vehículos + asignación de repartidores

### Sprint 6 — Pagos y Facturación
- [ ] `PagoController` — transferencia (subir comprobante), PayPal SDK, crédito
- [ ] `FacturaloService` — CFDI automático al confirmar pago
- [ ] Vista de facturas descargables para el cliente

### Sprint 7 — Notificaciones y Pedidos avanzados
- [ ] `NotificacionService` — PHPMailer SMTP + WhatsApp Cloud API
- [ ] Eventos: pedido confirmado, en ruta, próximo (<1km), entregado
- [ ] `RecurrenteController` — plantillas + generación automática
- [ ] `LimiteController` — supervisor configura límites por sucursal/producto

### Sprint 8 — Reportes y Analítica
- [ ] `EmpresaReporteController` — consumo mensual, gasto por sucursal, top productos
- [ ] `PanelReporteController` — ventas globales con gráficas Chart.js (solo lectura para superadmin)
- [ ] Exportar Excel (PhpSpreadsheet) y PDF (Dompdf)

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
| Email | PHPMailer | Composer | SMTP en sprints 5+ |
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

*Última actualización: 2026-05-04 — v2.1.0 (Arquitectura de roles corregida)*
