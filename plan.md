# CarniHub — Plan v2.0
**Versión:** 2.0.0 | **Fecha:** 2026-05-04 | **Stack:** PHP 8.3 · MySQL · Tailwind CDN · MVC sin framework

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
SuperAdmin (1 usuario inicial — seed en BD)
    │
    ├── Crea: Admin CarniHub
    └── Crea: Admin Empresa (para empresa específica)
                │
                ├── Crea: Supervisor (de su empresa)
                ├── Crea: Comprador  (de su empresa)
                └── Crea: Repartidor (de su empresa)
```

### Los 6 roles

| Rol | slug | Portal | Quién lo crea |
|-----|------|--------|---------------|
| Super Admin | `superadmin` | `/panel/` — acceso total | Seed inicial en BD |
| Admin CarniHub | `admin` | `/panel/` — sin config global | El superadmin |
| Admin Empresa | `admin_empresa` | `/empresa/` — control total de su empresa | Admin o superadmin |
| Supervisor | `supervisor` | `/empresa/` — aprobaciones y límites (sin pedidos) | Admin Empresa |
| Comprador | `comprador` | `/empresa/` — hace pedidos | Admin Empresa |
| Repartidor | `repartidor` | `/repartidor/` — app oscura GPS | Admin Empresa |

### Tabla de permisos

| Capacidad | superadmin | admin | admin_empresa | supervisor | comprador | repartidor |
|-----------|:---:|:---:|:---:|:---:|:---:|:---:|
| Panel de plataforma | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Configuración global | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Gestionar empresas clientes | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Catálogo + precios | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Hacer pedidos | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| Aprobar pedidos | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Pedidos recurrentes | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Configurar límites de compra | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Gestionar su equipo (empresa) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Gestionar sucursales | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Vehículos + repartidores | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Reportes financieros | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Reportes operativos | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Rastrear entrega (mapa GPS) | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| App repartidor | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Activar crédito empresa | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## PORTALES Y RUTAS

```
/auth/login              → Login único para TODOS los roles

Login exitoso redirige:
  superadmin / admin     → /panel/dashboard
  admin_empresa
  supervisor             → /empresa/dashboard
  comprador              → /empresa/dashboard
  repartidor             → /repartidor/inicio
```

### Router (`index.php`) — mapeo de URLs

| URL | Controller |
|-----|-----------|
| `auth/*` | AuthController |
| `api/*` | ApiController |
| `panel/*` | PanelController |
| `panel-empresa/*` | EmpresaController |
| `panel-usuario/*` | PanelUsuarioController |
| `panel-producto/*` | PanelProductoController |
| `panel-inventario/*` | PanelInventarioController |
| `panel-pedido/*` | PanelPedidoController |
| `panel-logistica/*` | PanelLogisticaController |
| `panel-reporte/*` | PanelReporteController |
| `config/*` | ConfigController |
| `empresa/*` | EmpresaDashboardController |
| `empresa-usuario/*` | EmpresaUsuarioController |
| `empresa-sucursal/*` | EmpresaSucursalController |
| `empresa-vehiculo/*` | EmpresaVehiculoController |
| `catalogo/*` | CatalogoController |
| `carrito/*` | CarritoController |
| `pedido/*` | PedidoController |
| `recurrente/*` | RecurrenteController |
| `limite/*` | LimiteController |
| `empresa-reporte/*` | EmpresaReporteController |
| `pago/*` | PagoController |
| `cuenta/*` | CuentaController |
| `repartidor/*` | RepartidorController |

---

## ESTADO ACTUAL DE ARCHIVOS

### Controllers (25 — en `app/controllers/`)

| Controller | Estado | Funcionalidad |
|-----------|--------|---------------|
| BaseController | ✅ Completo | requireAdmin, requireEmpresa, requireComprador, requireSupervisor, requireAdminEmpresa, requireRepartidor, redirectSegunRol |
| AuthController | ✅ Completo | Login con brute-force, logout, redirect por rol |
| PanelController | ✅ Funcional | Dashboard con KPIs, últimos pedidos, alertas de stock |
| EmpresaDashboardController | ✅ Funcional | Dashboard por rol (financiero/aprobaciones/comprador) |
| RepartidorController | ✅ Funcional | Ruta del día, confirmar entrega, firma/foto POD, historial |
| ApiController | ✅ Funcional | Precios escalonados AJAX, GPS tracking (actualizar/iniciar/finalizar) |
| EmpresaController | ✅ Funcional | Listado y alta de empresas cliente (admin) |
| EmpresaUsuarioController | ✅ Funcional | Admin_empresa crea supervisor/comprador/repartidor |
| CatalogoController | ✅ Funcional | Catálogo con filtros, detalle de producto |
| CuentaController | ✅ Funcional | Perfil, guardar datos, cambiar contraseña |
| CarritoController | ✅ Funcional | 4 pasos: productos → sucursales → resumen → confirmar |
| PedidoController | ✅ Funcional | Historial, detalle, aprobación supervisor, tracking GPS, cancelar |
| PanelUsuarioController | 🔧 Stub | Pendiente de implementar |
| PanelProductoController | 🔧 Stub | Pendiente de implementar |
| PanelInventarioController | 🔧 Stub | Pendiente de implementar |
| PanelPedidoController | 🔧 Stub | Pendiente de implementar |
| PanelLogisticaController | 🔧 Stub | Pendiente de implementar |
| PanelReporteController | 🔧 Stub | Pendiente de implementar |
| ConfigController | 🔧 Stub | Pendiente de implementar |
| RecurrenteController | 🔧 Stub | Pendiente |
| LimiteController | 🔧 Stub | Pendiente |
| EmpresaReporteController | 🔧 Stub | Pendiente |
| PagoController | 🔧 Stub | Pendiente |
| EmpresaSucursalController | 🔧 Stub | Pendiente |
| EmpresaVehiculoController | 🔧 Stub | Pendiente |

### Models (7 — en `app/models/`)

| Model | Estado |
|-------|--------|
| BaseModel | ✅ CRUD + paginate |
| UsuarioModel | ✅ getByEmail, getByEmpresa, rolesPermitidos, crear |
| EmpresaModel | ✅ listado con filtros y estadísticas |
| ProductoModel | ✅ listadoConPrecio, getPrecioParaCantidad, getEscalonados |
| PedidoModel | ✅ generarFolio, crear (transacción), listadoEmpresa, pendientesAprobacion, conDetalle, aprobar, rechazar, tracking |
| ConfigModel | ✅ get, set, getGrupo, getAll |
| LogModel | ✅ registrar (sin user_agent), registrarError, getBitacora |

### Vistas (22 — en `app/views/`)

| Vista | Estado |
|-------|--------|
| auth/login.php | ✅ Sin links de registro |
| panel/layouts/main.php | ✅ Sidebar oscuro dinámico por rol |
| panel/dashboard.php | ✅ KPIs + alertas + tabla pedidos |
| panel/empresas/index.php | ✅ Listado con filtros |
| panel/empresas/form.php | ✅ Alta de empresa |
| empresa/layouts/main.php | ✅ Sidebar blanco dinámico por rol |
| empresa/dashboard.php | ✅ Vista por rol (financiero/aprobaciones/comprador) |
| empresa/catalogo/index.php | ✅ Grid de productos con filtros |
| empresa/catalogo/detalle.php | ✅ Detalle + precios escalonados |
| empresa/carrito/paso1.php | ✅ Selección productos + cantidades + precio AJAX |
| empresa/carrito/paso2.php | ✅ Distribución por sucursal con validación |
| empresa/carrito/paso3.php | ✅ Resumen + fecha entrega + método pago |
| empresa/carrito/paso4.php | ✅ Folio confirmado + links de navegación |
| empresa/pedidos/index.php | ✅ Historial con filtros + paginación |
| empresa/pedidos/detalle.php | ✅ Detalle completo + sucursales + acciones |
| empresa/pedidos/aprobacion.php | ✅ Lista pendientes + aprobar + modal rechazo |
| empresa/pedidos/tracking.php | ✅ Mapa Leaflet + polling AJAX GPS |
| empresa/usuarios/index.php | ✅ Listado equipo de empresa |
| empresa/usuarios/form.php | ✅ Alta/edición de usuario |
| empresa/cuenta/perfil.php | ✅ Editar perfil + cambiar contraseña |
| repartidor/inicio.php | ✅ Ruta del día (dark mode) |
| repartidor/entrega.php | ✅ Firma digital + GPS + foto |
| repartidor/historial.php | ✅ Historial de entregas |

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
   - admin_empresa/supervisor/comprador → /empresa/dashboard
   - repartidor → /repartidor/inicio
```

### Flujo de creación de usuarios (admin_empresa)
```
1. Admin empresa entra a /empresa-usuario/nuevo
2. Selecciona rol: Supervisor, Comprador o Repartidor
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
- [x] `CarritoController` — 4 pasos: productos → sucursales → resumen → confirmar
- [x] Revalidar precios al confirmar
- [x] Generar folio CHB-YYYY-NNNN (autoincremental por año)
- [x] `PedidoController::index` — historial con filtros
- [x] `PedidoController::aprobacion` — supervisor aprueba/rechaza con modal
- [x] `PedidoController::tracking` — mapa GPS para comprador (Leaflet + polling AJAX)
- [x] Vistas carrito 4 pasos (en `/empresa/carrito/`)
- [x] Vistas pedidos (index, detalle, aprobacion, tracking)
- [x] Vista catálogo/detalle.php
- [x] `PedidoModel` — generarFolio, crear con transacción, pendientesAprobacion, aprobar, rechazar

### Sprint 4 — Panel de Plataforma (admin)
- [ ] `PanelProductoController` — CRUD productos + precios escalonados
- [ ] `PanelInventarioController` — stock + alertas + movimientos
- [ ] `PanelPedidoController` — todos los pedidos + cambiar estado
- [ ] `PanelLogisticaController` — rutas + mapa Leaflet global
- [ ] `PanelUsuarioController` — CRUD usuarios plataforma
- [ ] Vistas correspondientes en `/panel/`

### Sprint 5 — Configuración Global (solo superadmin)
- [ ] `ConfigController` completo (reemplazar stub)
- [ ] Secciones: General, Correo, Estilos, Pagos, APIs, Notificaciones, GPS, Facturación, IoT
- [ ] CSS variables dinámicas (`--color-primary`) desde `global_settings`
- [ ] Upload de logo desde panel de configuración

### Sprint 6 — Pagos y Facturación
- [ ] `PagoController` — transferencia (subir comprobante), PayPal SDK, crédito
- [ ] `FacturaloService` — CFDI automático al confirmar pago
- [ ] Vista de facturas descargables para el cliente

### Sprint 7 — Notificaciones y Logística avanzada
- [ ] `NotificacionService` — PHPMailer SMTP + WhatsApp Cloud API
- [ ] Eventos: pedido confirmado, en ruta, próximo (<1km), entregado
- [ ] `EmpresaSucursalController` — CRUD sucursales con mapa Leaflet
- [ ] `EmpresaVehiculoController` — vehículos + asignación de repartidores
- [ ] `RecurrenteController` — plantillas + generación automática

### Sprint 8 — Reportes y Analítica
- [ ] `EmpresaReporteController` — consumo mensual, gasto por sucursal, top productos
- [ ] `PanelReporteController` — ventas globales con gráficas Chart.js
- [ ] Exportar Excel (PhpSpreadsheet) y PDF (Dompdf)
- [ ] `LimiteController` — supervisor configura límites por sucursal/producto

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

Todos se configuran desde `/config/general` (solo visible para superadmin).

---

*Última actualización: 2026-05-04 — v2.1.0 (Sprint 3 completado)*
