# CarniHub — Plan de Implementación y Estado del Sistema
**Versión:** 1.3.0 | **Fecha:** 2026-05-04 | **Stack:** PHP 8.3 · MySQL 5.7 · Tailwind CDN · MVC sin framework

---

## ESTADO ACTUAL (resumen ejecutivo)

| Capa | Archivos | Estado |
|------|----------|--------|
| Infraestructura (.htaccess, index.php, config) | 4 | ✅ Funcional |
| Controladores | 18 | ✅ Auth, Registro, Config, Cuenta completados |
| Modelos | 13 | ⚠️ Revisar queries vs BD real |
| Vistas | 50+ | ⚠️ Admin revisado parcialmente |
| Servicios API | 5 | 🔴 Esqueletos, no conectados a tokens reales |
| BD (SQL) | 27 tablas + migraciones | ✅ Schema completo + datos dummy |
| CSS / JS | 9 archivos | ⚠️ Posibles rutas rotas |

**Completado en esta sesión:**
- Registro público comprador/repartidor con verificación de correo
- Seguridad login: brute force + cuentas de prueba eliminadas
- Separación nombre en campos (nombre, apellido_paterno, apellido_materno)
- Avatares de usuario con upload al servidor
- Logo del sistema: upload PNG/JPG/WebP/SVG desde config admin, dinámico en sidebars
- Google Maps interactivo en formulario de registro (marcador draggable para comprador)
- APIs e integraciones: campo Google Maps key en config
- Módulo Cuenta cliente (perfil, cambiar contraseña)
- Migraciones 001 y 002 para nuevas tablas y campos
- **v1.3 (2026-05-04):** Mapa comprador visible desde inicio (sin esperar autocomplete); zona repartidor cambiada de "colonia/círculo" a dropdown de municipios de México (QRO, GTO, HGO, MEX, CDMX, JAL, NL)

---

## ARQUITECTURA DEL SISTEMA

```
Navegador
   │
   └─► Apache (.htaccess) ──► index.php?url={ctrl}/{accion}/{id}
                                    │
                              parse $_GET['url']
                                    │
                    ┌───────────────┼───────────────┐
                    ▼               ▼               ▼
             AuthGuard        Router Map       404 handler
                    │               │
                    └──► Controller::action($param)
                                    │
                    ┌───────────────┼──────────────┐
                    ▼               ▼              ▼
                Model           Service         render(view)
                (PDO)        (API externa)    (require view.php)
```

### Roles del sistema
| Rol slug | Nombre | Acceso |
|----------|--------|--------|
| `superadmin` | SuperAdmin | Todo |
| `admin` | Admin Empresa | Panel admin excepto config global |
| `comprador` | Comprador | Portal cliente completo |
| `supervisor` | Supervisor | Portal cliente (solo lectura en algunos) |
| `repartidor` | Repartidor | App repartidor oscura |

### Flujo de autenticación
1. Cualquier URL sin sesión → redirect `auth/login`
2. `POST auth/doLogin` → valida email+password con `password_verify()`
3. Según `rol_slug` redirige a: `dashboard/index` / `carrito/inicio` / `repartidor/inicio`
4. Sesión guarda: `$_SESSION['usuario']` (array completo) + `$_SESSION['empresa']`

---

## MÓDULO 0 — INFRAESTRUCTURA

### 0.1 `.htaccess`
```apache
Options -Indexes
RewriteEngine On
RewriteBase /carnihub/          ← DEBE coincidir con carpeta en servidor
RewriteRule ^(config|app)/.*$ - [F,L]
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```
**Importante:** `RewriteBase` debe coincidir EXACTAMENTE con el nombre de carpeta en `public_html/`. Actualmente: `/carnihub/` (minúsculas).

### 0.2 `config/config.php` — constantes globales
| Constante | Valor | Uso |
|-----------|-------|-----|
| `BASE_URL` | `https://idactivos.digital/carnihub/` | Prefijo de todos los `redirect()` y enlaces |
| `BASE_URL_PATH` | `/carnihub/` | Para strips en router |
| `BASE_PATH` | Ruta filesystem | Para `require_once` de vistas/modelos |
| `UPLOAD_PATH` | `BASE_PATH/public/uploads/` | Guardar imágenes |
| `UPLOAD_URL` | `BASE_URL/public/uploads/` | Mostrar imágenes en HTML |
| `SESSION_NAME` | `carnihub_session` | Nombre cookie |
| `PER_PAGE` | 20 | Paginación |

### 0.3 `config/database.php` — PDO singleton
- Credenciales reales en producción: host=`localhost`, db=`idactivo_carnihubdb`, user=`idactivo_carnihubdb_admin`
- Retorna la misma instancia PDO en cada llamada
- `PDO::ATTR_ERRMODE = EXCEPTION` para capturar errores SQL

### 0.4 `index.php` — Front Controller
**Flujo exacto:**
```
$_GET['url'] = "producto/editar/5"
  → $segments = ['producto', 'editar', '5']
  → $ctrlSlug = 'producto'
  → $action   = 'editar'
  → $param    = '5'
  → AuthGuard: ¿isset($_SESSION['usuario'])? Si no → redirect auth/login
  → $routes['producto'] = 'ProductoController'
  → require app/controllers/ProductoController.php
  → (new ProductoController())->editar('5')
```
**Public paths** (sin sesión permitidos): `auth/login`, `auth/dologin`

---

## MÓDULO 1 — AUTENTICACIÓN (`auth/`)

### Páginas
| URL | Método | Descripción |
|-----|--------|-------------|
| `auth/login` | GET | Formulario de login |
| `auth/doLogin` | POST | Procesar credenciales |
| `auth/logout` | GET | Cerrar sesión |

### Lógica `auth/login` (GET)
1. Si `$_SESSION['usuario']` existe → redirect al dashboard correspondiente
2. Leer `$flash` de sesión (mensaje de error previo)
3. Renderizar `views/auth/login.php` con variables: `$pageTitle`, `$flash`

### Lógica `auth/doLogin` (POST)
```
1. Validar campos: email y password no vacíos → flash error + redirect login
2. UsuarioModel::getByEmail($email) → busca en tabla usuarios JOIN roles
3. Si no existe usuario → flash "Credenciales incorrectas" + redirect
4. password_verify($password, $usuario['password']) → si falla → flash error
5. Guardar en sesión:
   $_SESSION['usuario'] = $row_completo   (incluye rol_slug, empresa_id, nombre, avatar)
   $_SESSION['empresa'] = EmpresaModel::find($usuario['empresa_id'])  (si aplica)
6. Redirigir según rol_slug:
   - 'repartidor'              → repartidor/inicio
   - 'comprador','supervisor'  → carrito/inicio
   - 'superadmin','admin'      → dashboard/index
```

### Vista `views/auth/login.php`
- Diseño: centrado, fondo oscuro o imagen de carne, logo CarniHub
- Campos: email, password (con toggle ver/ocultar)
- Botón: "Iniciar sesión" (rojo `#C8102E`)
- Alerta de error si `$flash['type'] === 'error'`
- Sin sidebar/header global (página standalone)

---

## MÓDULO 2 — DASHBOARD ADMIN (`dashboard/`)

### URL: `dashboard/index`

### Lógica
1. `requireRole(['superadmin','admin','comprador','supervisor'])`
2. Según `rol_slug` del usuario:
   - `superadmin`/`admin` → cargar métricas de admin
   - `comprador`/`supervisor` → cargar métricas de cliente

### Datos que carga (admin)
```php
$stats = PedidoModel::getEstadisticasDashboard();
// Retorna:
//   total_pedidos_hoy, total_ventas_mes, pedidos_pendientes,
//   clientes_activos, entregas_en_ruta, alertas_inventario
$graficaVentas = PedidoModel::getVentasPorDia(30);  // últimos 30 días
$graficaProductos = ProductoModel::getTopVendidos(5);
$pedidosRecientes = PedidoModel::getRecientes(10);
$alertasInventario = InventarioModel::getAlertas();
```

### Datos que carga (cliente/comprador)
```php
$empresa = $_SESSION['empresa'];
$estadoPedidos = PedidoModel::getResumenByEmpresa($empresa['id']);
$ultimosPedidos = PedidoModel::getByEmpresa($empresa['id'], ['limit'=>5]);
$recurrentes = RecurrenteModel::getByEmpresa($empresa['id']);
```

### Vista `views/admin/dashboard.php`
- KPIs en tarjetas: Pedidos hoy | Ventas mes | Pendientes | En ruta
- Gráfica línea: ventas por día (Chart.js, `dashboard_admin.js`)
- Gráfica pie: productos más vendidos
- Tabla: últimos 10 pedidos con estado y acciones
- Sidebar: `sidebar_admin.php`
- Alertas de inventario bajo (badge rojo)

---

## MÓDULO 3 — CLIENTES / EMPRESAS (`cliente/` admin)

> Nota: Este módulo es para el ADMIN gestionando empresas clientes B2B.
> El portal del comprador está en Módulo 7.

### URLs admin
| URL | Método | Vista |
|-----|--------|-------|
| `cliente/index` | GET | Lista empresas |
| `cliente/detalle/{id}` | GET | Detalle + tabs |
| `cliente/crear` | GET | Form nueva empresa |
| `cliente/editar/{id}` | GET | Form editar |
| `cliente/guardar` | POST | Guardar (crear o editar) |
| `cliente/eliminar/{id}` | POST | Eliminar |
| `cliente/activarCredito/{id}` | POST | Toggle crédito |

### Lógica `cliente/index`
```php
requireAdmin();
$filtros = [
    'busqueda' => $_GET['q'] ?? '',
    'activo'   => $_GET['activo'] ?? 1,
    'page'     => $_GET['page'] ?? 1,
];
$empresas = EmpresaModel::listar($filtros);
$total    = EmpresaModel::total($filtros);
render('admin/clientes/index', compact('empresas','total','filtros'));
```

### Lógica `cliente/detalle/{id}`
```php
requireAdmin();
$empresa    = EmpresaModel::find($id);        // datos empresa
$sucursales = SucursalModel::getByEmpresa($id);
$contactos  = UsuarioModel::getByEmpresa($id);
$historial  = PedidoModel::getByEmpresa($id, ['limit'=>20]);
$credito    = ['activo' => $empresa['credito_activo'],
               'limite' => $empresa['limite_credito'],
               'usado'  => $empresa['saldo_credito']];
render('admin/clientes/detalle', compact(...));
```

### Vista `views/admin/clientes/detalle.php`
- Tabs: **Info general** | **Sucursales** | **Usuarios** | **Crédito** | **Historial**
- Tab Info: RFC, razón social, régimen, vendedor asignado, método pago
- Tab Sucursales: tabla con dirección, ciudad, contacto; botón agregar
- Tab Crédito: toggle activar, límite editable, saldo actual, días crédito
- Tab Historial: tabla pedidos con folio, fecha, total, estado

### Lógica `cliente/guardar` (POST)
```php
// Validar: razon_social, rfc (único), email
// Si $POST['id'] existe → UPDATE, sino → INSERT
// Si hay archivo avatar/logo → subir a UPLOAD_PATH
// Registrar en action_logs
// redirect cliente/detalle/{id}
```

---

## MÓDULO 4 — PRODUCTOS (`producto/`)

### URLs
| URL | Método | Vista |
|-----|--------|-------|
| `producto/index` | GET | Lista productos (admin) |
| `producto/catalogo` | GET | Catálogo (cliente) |
| `producto/detalle/{id}` | GET | Detalle producto (cliente) |
| `producto/crear` | GET | Form nuevo producto (admin) |
| `producto/editar/{id}` | GET | Form editar (admin) |
| `producto/guardar` | POST | Guardar producto (admin) |
| `producto/precios/{id}` | GET | Gestionar precios escalonados |
| `producto/guardarPrecio` | POST | Guardar rango de precio |
| `producto/eliminarPrecio/{id}` | POST | Eliminar rango |

### Lógica precios escalonados
Los precios cambian según cantidad comprada. Ejemplo:
```
0 – 50 kg   → $180/kg
51 – 200 kg → $165/kg
201+  kg    → $150/kg
```
- `ProductoModel::getPrecioParaCantidad($productoId, $cantidad)`:
  ```sql
  SELECT precio_por_unidad
  FROM precios_escalonados
  WHERE producto_id = ? AND activo = 1
    AND rango_min <= ? AND (rango_max IS NULL OR rango_max >= ?)
  LIMIT 1
  ```
- El precio se recalcula en tiempo real vía `ApiController::precioEscalonado()` (AJAX)

### Vista `views/admin/productos/precios.php`
- Tabla de rangos actuales: Desde | Hasta | Precio/kg | Acciones
- Form inline para agregar rango: campos `rango_min`, `rango_max`, `precio`
- Botón eliminar por rango

### Vista `views/admin/productos/form.php`
- Campos: nombre, categoría (select), descripción, presentación (kg/caja/pieza), precio_base
- Upload de imagen (acepta jpg/png, máx 2MB)
- Preview de imagen antes de guardar

---

## MÓDULO 5 — INVENTARIO (`inventario/`)

### URLs
| URL | Método | Vista |
|-----|--------|-------|
| `inventario/index` | GET | Lista stock por producto |
| `inventario/actualizar` | POST | Ajustar stock manualmente |

### Lógica `inventario/index`
```php
requireAdmin();
$stock = InventarioModel::getConProducto();
// SELECT i.*, p.nombre, p.presentacion, c.nombre as categoria
// FROM inventario i JOIN productos p ON i.producto_id = p.id
// JOIN categorias c ON p.categoria_id = c.id
// ORDER BY i.disponible ASC
$alertas = InventarioModel::getAlertas();
// WHERE disponible <= minimo_alerta
```

### Vista `views/admin/inventario/index.php`
- Tabla: Producto | Categoría | Disponible | En tránsito | Reservado | Mínimo | Estado
- Estado visual: 🟢 OK / 🟡 Bajo / 🔴 Crítico (vs `minimo_alerta`)
- Botón "Ajustar" abre modal con campo cantidad y motivo
- Filtros: categoría, estado de alerta

---

## MÓDULO 6 — PEDIDOS ADMIN (`pedido/`)

### URLs
| URL | Método | Vista |
|-----|--------|-------|
| `pedido/index` | GET | Lista todos los pedidos |
| `pedido/detalle/{id}` | GET | Detalle completo |
| `pedido/cambiarEstado/{id}` | POST | Cambiar estado |
| `pedido/reordenar/{id}` | POST | Copiar al carrito |

### Estados del pedido (flujo)
```
pendiente → confirmado → en_preparacion → en_ruta → entregado
                                                  ↘ cancelado (cualquier estado)
```

### Lógica `pedido/detalle/{id}`
```php
$pedido    = PedidoModel::find($id);
$detalle   = PedidoDetalleModel::getByPedido($id);
// SELECT pd.*, p.nombre, p.presentacion FROM pedido_detalle pd JOIN productos p ON pd.producto_id = p.id
$porSucursal = PedidoModel::getDistribucionSucursales($id);
// SELECT ps.*, s.nombre as sucursal FROM pedido_sucursal ps JOIN sucursales s ON ps.sucursal_id = s.id
$ruta      = RutaModel::getByPedido($id);   // si ya tiene ruta asignada
$pagos     = PagoModel::getByPedido($id);
```

### Vista `views/admin/pedidos/detalle.php`
- Header: folio (CHB-YYYY-NNNN), empresa, fecha, estado con badge color
- Tabla productos: nombre | cantidad | precio unit | subtotal
- Distribución por sucursal: tabla agrupada
- Timeline de estados (CSS timeline vertical)
- Sección pago: método, referencia, estado
- Botones: Cambiar estado | Asignar ruta | Descargar factura

---

## MÓDULO 7 — PORTAL CLIENTE (comprador/supervisor)

### 7.1 Inicio (`carrito/inicio`)
```php
requireRole(['comprador','supervisor']);
$empresa  = $_SESSION['empresa'];
$ultimosPedidos = PedidoModel::getByEmpresa($empresa['id'], ['limit'=>5]);
$recurrentes    = RecurrenteModel::getActivos($empresa['id']);
$catalogo       = ProductoModel::getDestacados(6);
render('cliente/inicio', compact(...));
```

### Vista `views/cliente/inicio.php`
- Saludo personalizado con nombre + empresa
- Acceso rápido: botones grandes → Hacer Pedido | Mis Pedidos | Pedidos Recurrentes
- Últimos 5 pedidos: folio | fecha | total | estado
- Plantillas recurrentes activas con próxima fecha
- Catálogo destacado (6 productos)

### 7.2 Catálogo (`producto/catalogo`)
```php
requireRole(['comprador','supervisor']);
$empresa    = $_SESSION['empresa'];
$sucursales = SucursalModel::getByEmpresa($empresa['id']);
$categorias = CategoriaModel::all();
$productos  = ProductoModel::getCatalogo([
    'categoria' => $_GET['cat'] ?? '',
    'busqueda'  => $_GET['q'] ?? '',
    'activo'    => 1
]);
// Para cada producto cargar precios_escalonados
render('cliente/catalogo/index', compact(...));
```

### Vista `views/cliente/catalogo/index.php`
- Filtros: barra búsqueda + tabs de categoría (Res, Cerdo, Pollo, Otros)
- Grid de productos: imagen | nombre | precio base | botón "Agregar al carrito"
- Al hacer clic en precio → muestra tabla de precios escalonados (modal/tooltip)
- Selector de cantidad con `input[type=number]` → JS llama `api/precioEscalonado` → actualiza precio

### 7.3 Carrito — 4 pasos

#### Paso 1: Carrito (`carrito/index`)
```
Vista: views/cliente/carrito/paso1_carrito.php
JS: carrito.js (gestiona carrito en localStorage + sincroniza con sesión)
```
**Estructura del carrito en sesión:**
```php
$_SESSION['carrito'] = [
    $productoId => [
        'nombre'      => string,
        'precio_unit' => decimal,   // recalculado según cantidad total
        'cantidad_total' => decimal,
        'subtotal'    => decimal,
        'por_sucursal' => [
            $sucursalId => $cantidad,
            ...
        ]
    ],
    ...
]
```
**Lógica JS (`carrito/agregar` endpoint):**
1. Usuario selecciona producto + cantidad por sucursal (si tiene varias)
2. `POST api/agregar → CarritoController::agregar()`
3. Recalcular precio escalonado con cantidad total
4. Actualizar `$_SESSION['carrito']`
5. Devolver JSON `{ok: true, total: X, items: Y}`
6. JS actualiza badge del carrito en sidebar

#### Paso 2: Entrega (`carrito/entrega`)
```
Vista: views/cliente/carrito/paso2_entrega.php
```
- Mostrar sucursales del cliente con sus cantidades del carrito
- Para cada sucursal: campo fecha de entrega + ventana horaria (08-12, 12-16, 16-20)
- Validar que todas tengan fecha asignada antes de continuar
- Guardar en `$_SESSION['entrega']`

#### Paso 3: Resumen (`carrito/resumen`)
```
Vista: views/cliente/carrito/paso3_resumen.php
```
- Mostrar RESUMEN COMPLETO antes de confirmar:
  - Tabla agrupada por sucursal con productos y cantidades
  - Subtotal por sucursal
  - Total global
  - Fecha y ventana de entrega por sucursal
  - Método de pago (seleccionar: transferencia / crédito / efectivo)
- Botón "Confirmar Pedido" → POST a `carrito/confirmar`

#### Paso 4: Confirmación (`carrito/confirmar` POST)
```php
// Transacción MySQL:
1. Generar folio: CHB-2026-NNNN (autoincremental por año)
2. INSERT pedidos (datos globales)
3. INSERT pedido_detalle (por producto, cantidades totales)
4. INSERT pedido_sucursal (por producto+sucursal)
5. UPDATE inventario (descontar reservado)
6. INSERT action_logs
7. Limpiar $_SESSION['carrito'] y $_SESSION['entrega']
8. Enviar WhatsApp de confirmación (WhatsAppService)
9. redirect pedido/detalle/{id} → mostrar confirmación
```
```
Vista confirmación: views/cliente/carrito/paso4_confirmacion.php
```
- Folio grande: CHB-2026-XXXX
- Mensaje "Tu pedido fue confirmado"
- Resumen breve
- Botones: Ver Pedido | Hacer otro pedido

---

## MÓDULO 8 — PEDIDOS RECURRENTES (`recurrente/`)

### URLs
| URL | Método | Vista |
|-----|--------|-------|
| `recurrente/index` | GET | Lista plantillas |
| `recurrente/detalle/{id}` | GET | Ver/editar plantilla |
| `recurrente/guardar` | POST | Crear/editar plantilla |
| `recurrente/pausar/{id}` | POST | Pausar |
| `recurrente/activar/{id}` | POST | Reactivar |
| `recurrente/confirmarAhora/{id}` | POST | Generar pedido inmediato |

### Lógica
- Las plantillas guardan qué productos y cantidades se piden en qué sucursal
- `frecuencia`: diario / semanal / quincenal
- `proximo_pedido`: fecha próxima generación
- `confirmarAhora()` → llama `PedidoModel::crearConDetalle()` con datos de la plantilla
- En el futuro: CRON job para generación automática

### Vista `views/cliente/recurrentes/detalle.php`
- Nombre de la plantilla
- Tabla de productos por sucursal con cantidades editables
- Frecuencia + próxima fecha
- Historial de pedidos generados desde esta plantilla
- Botones: Guardar cambios | Pausar | Pedir ahora

---

## MÓDULO 9 — LOGÍSTICA (`logistica/`)

### URLs
| URL | Método | Vista |
|-----|--------|-------|
| `logistica/rutas` | GET | Lista rutas del día |
| `logistica/detalle/{id}` | GET | Detalle ruta + stops |
| `logistica/crearRuta` | POST | Crear ruta y asignar pedidos |
| `logistica/choferes` | GET | Lista choferes |
| `logistica/cambiarEstado/{id}` | POST | Cambiar estado ruta |

### Lógica `logistica/rutas`
```php
requireAdmin();
$fecha  = $_GET['fecha'] ?? date('Y-m-d');
$rutas  = RutaModel::getDelDia($fecha);
// Cada ruta incluye: chofer, vehículo, n° entregas, estado, progreso
$pedidosSinRuta = PedidoModel::getSinRuta($fecha);
// Pedidos confirmados sin ruta asignada para ese día
$choferes = ChoferModel::getDisponibles($fecha);
render('admin/logistica/rutas', compact(...));
```

### Vista `views/admin/logistica/rutas.php`
- Header: selector de fecha + botón "Nueva ruta"
- Columna izquierda: lista de rutas del día (card por ruta: chofer, vehículo, # paradas, estado)
- Columna derecha: pedidos sin ruta (drag & drop para asignar a una ruta) ← si se implementa drag/drop
- Botón en cada ruta: Ver detalle | Ver en mapa

### Vista `views/admin/logistica/mapa.php`
- Mapa Leaflet.js con marcadores por sucursal
- Línea de ruta entre paradas (polyline en orden `orden_entrega`)
- Datos de Traccar (TraccarService) para posición en tiempo real del vehículo
- Panel lateral: lista de paradas con estado actual

---

## MÓDULO 10 — REPORTES (`reporte/`)

### URLs
| URL | Vista |
|-----|-------|
| `reporte/index` | Resumen general |
| `reporte/ventas` | Reporte de ventas detallado |
| `reporte/cliente` | Reporte por empresa (para el comprador) |

### Datos reportes admin
```php
// reporte/ventas
$filtros = [
    'desde'   => $_GET['desde'] ?? date('Y-m-01'),
    'hasta'   => $_GET['hasta'] ?? date('Y-m-d'),
    'empresa' => $_GET['empresa'] ?? null,
    'producto'=> $_GET['producto'] ?? null,
];
$ventas       = PedidoModel::getVentasFiltradas($filtros);
$porEmpresa   = PedidoModel::agruparPorEmpresa($filtros);
$porProducto  = PedidoModel::agruparPorProducto($filtros);
$porCategoria = PedidoModel::agruparPorCategoria($filtros);
```

### Vista `views/admin/reportes/ventas.php`
- Filtros: rango fechas (datepicker), empresa, producto
- KPIs: Total facturado | # Pedidos | Ticket promedio | Producto top
- Gráfica barras: ventas por día/semana
- Gráfica pie: distribución por categoría de carne
- Tabla detallada: empresa | pedidos | kg total | monto total

---

## MÓDULO 11 — USUARIOS (`usuario/`)

### URLs
| URL | Vista |
|-----|-------|
| `usuario/index` | Lista usuarios |
| `usuario/crear` | Form nuevo usuario |
| `usuario/editar/{id}` | Form editar |
| `usuario/guardar` | POST guardar |
| `usuario/eliminar/{id}` | POST eliminar |
| `usuario/toggleActivo/{id}` | POST activar/desactivar |

### Lógica `usuario/guardar`
```php
requireAdmin();
// Validar: email único, rol válido
// Si es nuevo: password_hash($password, PASSWORD_DEFAULT)
// Si edita: solo hash si viene password no vacío
// Subir avatar si se incluye archivo
// Asociar empresa_id según rol
// INSERT o UPDATE
// redirect usuario/index
```

---

## MÓDULO 12 — CONFIGURACIÓN GLOBAL (`config/`)

### URLs
| URL | Grupo | Descripción |
|-----|-------|-------------|
| `config/general` | general | Nombre app, logo, colores, timezone |
| `config/apis` | apis | Tokens: WhatsApp, Traccar, Facturalo, PayPal |
| `config/dispositivos` | — | CRUD HikVision + Shelly |
| `config/bitacora` | — | Visor de action_logs |
| `config/guardar` | POST | Guardar settings |

### Lógica `config/general`
```php
requireRole(['superadmin']);
$settings = ConfigModel::getGrupo('general');
// global_settings WHERE grupo = 'general'
// Claves: app_nombre, app_logo, brand_color, timezone, moneda
render('admin/configuracion/general', ['settings' => $settings]);
```

### Lógica `config/dispositivos`
```php
requireRole(['superadmin']);
$camaras = DispositivoModel::getHikvision();   // tabla dispositivos_hikvision
$shellys = DispositivoModel::getShelly();       // tabla dispositivos_shelly
render('admin/configuracion/dispositivos', compact('camaras','shellys'));
```

### Vista `views/admin/configuracion/dispositivos.php`
- **Sección HikVision:**
  - Lista de cámaras con: nombre, IP, tipo, estado (ping), ubicación
  - Botón "Agregar cámara" → form con: nombre, IP, puerto, usuario, password, canal, tipo, ubicación
  - Botón "Ver snapshot" → llama `HikvisionService::getSnapshot()` → muestra imagen
  - Botón "Ver stream" → abre RTSP/MJPEG en iframe o modal
- **Sección Shelly:**
  - Lista de dispositivos con: nombre, device_id, tipo, estado (on/off)
  - Toggle ON/OFF en tiempo real → `ApiController::shellyToggle()`
  - Botón "Agregar Shelly" → form con device_id, auth_key, tipo, ubicación

### Lógica `config/guardar` (POST) — IMPLEMENTADO
```php
requireRole(['superadmin']);

// 1. Subida de logo (viene en $_FILES, no en $_POST)
if (!empty($_FILES['app_logo']['tmp_name']) && $_FILES['app_logo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['app_logo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['png','jpg','jpeg','webp','svg'], true) && size <= 2MB) {
        move_uploaded_file(..., ROOT_PATH . '/public/uploads/logos/logo.' . $ext);
        $model->set('app_logo', 'uploads/logos/logo.' . $ext);
    }
}
unset($campos['app_logo']);  // nunca guardar el campo file vacío como texto

// 2. Borrar logo si se solicitó (campo oculto app_logo_borrar=1)
if ($campos['app_logo_borrar'] === '1') {
    $ruta = $model->get('app_logo', '');
    if ($ruta) { @unlink(ROOT_PATH . '/public/' . $ruta); }
    $model->set('app_logo', '');
}
unset($campos['app_logo_borrar']);

// 3. Guardar el resto de campos de texto
foreach ($campos as $k => $v) { $model->set($k, $v); }
flash('success', 'Configuración guardada correctamente.');
redirect("config/$grupo");
```

**Por qué el logo no funcionaba antes:**
- `$_FILES` y `$_POST` son arrays separados en PHP. El `<input type="file">` llega vacío en `$_POST`.
- El controlador solo iteraba `$_POST`, por eso el logo nunca se guardaba.
- Solución: leer `$_FILES['app_logo']` explícitamente antes de procesar `$_POST`.

### Sidebars — Logo dinámico
Ambos sidebars (`sidebar_admin.php`, `sidebar_cliente.php`) consultaban `global_settings` en tiempo de render:
```php
$_lr = Database::getInstance()->query(
    "SELECT clave,valor FROM global_settings WHERE clave IN ('app_logo','app_nombre')"
)->fetchAll(PDO::FETCH_KEY_PAIR);
// Si app_logo tiene valor → <img src="...">
// Si no → <span>Nombre del sistema</span>
```

### Vista `views/admin/configuracion/general.php` — Widget logo
- Preview 80×50px mostrando logo actual o "Sin logo"
- Botón "Seleccionar logo" → abre file picker oculto
- `previewLogo(input)` → FileReader API → actualiza preview al instante sin enviar form
- Botón "Quitar logo" (solo si hay logo) → `borrarLogo()` → pone `app_logo_borrar=1`
- Campo oculto `app_logo_borrar` que el controlador lee para eliminar físicamente el archivo

### Checklist Módulo 12
- [x] `ConfigModel::get()` y `ConfigModel::set()` funcionando
- [x] Logo upload: validación extensión + tamaño + move_uploaded_file
- [x] Logo borrar: unlink() del archivo + limpiar BD
- [x] Sidebars con logo dinámico desde global_settings
- [x] Widget UI con preview, seleccionar y quitar logo
- [x] Campo API Key Google Maps en `apis.php`
- [x] Migration 002: insertar claves `api_google_maps_key` y `app_logo` en global_settings
- [ ] Ejecutar `migrations/002_google_maps_logo.sql` en cPanel phpMyAdmin
- [ ] Subir logo real del cliente
- [ ] Configurar API Key de Google Maps en config/APIs

---

## MÓDULO 13 — REPARTIDOR (app móvil oscura)

### URLs
| URL | Vista |
|-----|-------|
| `repartidor/inicio` | Dashboard oscuro con resumen del día |
| `repartidor/entregas` | Lista de entregas asignadas hoy |
| `repartidor/detalle/{id}` | Detalle de una entrega |
| `repartidor/iniciarEntrega/{id}` | Marcar como en ruta |
| `repartidor/completarEntrega` | POST: subir firma + foto |
| `repartidor/mapa` | Mapa de ruta |
| `repartidor/historial` | Entregas anteriores |
| `repartidor/perfil` | Datos del repartidor |

### Lógica `repartidor/inicio`
```php
requireRole(['repartidor']);
$usuario  = $_SESSION['usuario'];
$chofer   = ChoferModel::getByUsuario($usuario['id']);
$ruta     = RutaModel::getHoyByChofer($chofer['id']);
$entregas = $ruta ? RutaDetalleModel::getByRuta($ruta['id']) : [];
$stats    = [
    'total'       => count($entregas),
    'completadas' => count(array_filter($entregas, fn($e) => $e['estado'] === 'entregado')),
    'pendientes'  => count(array_filter($entregas, fn($e) => $e['estado'] === 'pendiente')),
];
render('repartidor/inicio', compact('ruta','entregas','stats','chofer'));
```

### Vista `views/repartidor/inicio.php`
- Fondo oscuro `#111827`
- Texto en blanco/gris claro
- Tarjeta grande: Ruta del día (nombre, total paradas, km estimados)
- Progress bar: X de Y entregas completadas
- Botón grande rojo: "Ver mis entregas"
- Bottom nav: Inicio | Entregas | Mapa | Perfil

### Lógica `repartidor/completarEntrega` (POST)
```php
requireRole(['repartidor']);
$rutaDetalleId = (int)$_POST['ruta_detalle_id'];
$receptorNombre = $_POST['receptor'];

// Subir foto si viene $_FILES['foto']
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
    $fotoPath = UPLOAD_PATH . 'evidencias/' . uniqid() . '.jpg';
    move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath);
}

// Guardar firma (viene como base64 del canvas)
if (!empty($_POST['firma_base64'])) {
    $firmaPath = UPLOAD_PATH . 'evidencias/' . uniqid() . '_firma.png';
    $data = base64_decode(str_replace('data:image/png;base64,', '', $_POST['firma_base64']));
    file_put_contents($firmaPath, $data);
}

// INSERT evidencias_entrega
// UPDATE ruta_detalle SET estado = 'entregado'
// UPDATE pedidos SET estado = 'entregado' (si todas las paradas del pedido están entregadas)
// LogModel::registrar(...)
// redirect repartidor/entregas
```

### Vista `views/repartidor/en_progreso.php`
- Nombre de la empresa y sucursal
- Dirección con botón "Abrir en Google Maps"
- Lista de productos a entregar
- Campo: nombre del receptor
- **Área de firma digital:** `<canvas id="firma">` + JS con eventos touch
  - Botón limpiar firma
- **Foto:** `<input type="file" accept="image/*" capture="environment">` (abre cámara)
- Botón rojo: "Confirmar entrega"

---

## MÓDULO 14 — CUENTA CLIENTE (`cuenta/`)

### URLs
| URL | Vista |
|-----|-------|
| `cuenta/perfil` | Ver/editar perfil propio |
| `cuenta/guardarPerfil` | POST actualizar datos |
| `cuenta/cambiarPassword` | POST cambiar contraseña |

### Vista `views/cliente/cuenta/perfil.php`
- Avatar actual + botón cambiar (upload)
- Campos: nombre_completo (dividido en nombre+apellido), email, teléfono
- Sección contraseña: password actual + nueva + confirmar
- Info empresa: nombre empresa, RFC (solo lectura para comprador)

---

## MÓDULO 15 — API JSON (`api/`)

> Endpoints consumidos por JavaScript del frontend. Responden JSON.

| URL | Params | Respuesta |
|-----|--------|-----------|
| `api/precioEscalonado` | GET `producto_id`, `cantidad` | `{precio, subtotal, rango}` |
| `api/stockProducto` | GET `producto_id` | `{disponible, reservado, unidad}` |
| `api/sucursalesEmpresa` | GET (usa sesión) | `[{id, nombre, direccion}]` |
| `api/estadoPedido` | GET `folio` | `{estado, updated_at}` |
| `api/shellyStatus` | GET `dispositivo_id` | `{estado, voltaje}` |
| `api/shellyToggle` | POST `dispositivo_id`, `accion` | `{ok, nuevo_estado}` |

### Lógica `api/precioEscalonado`
```php
// IMPORTANTE: debe devolver JSON siempre
header('Content-Type: application/json');
$productoId = (int)($_GET['producto_id'] ?? 0);
$cantidad   = (float)($_GET['cantidad'] ?? 0);
$precio = ProductoModel::getPrecioParaCantidad($productoId, $cantidad);
echo json_encode([
    'ok'       => true,
    'precio'   => $precio,
    'subtotal' => round($precio * $cantidad, 2),
]);
exit;
```

---

## MÓDULO 16 — SERVICIOS EXTERNOS

### 16.1 WhatsAppService
**Proveedor:** WhatsApp Business API (Meta) o proveedor alternativo
**Cuándo se usa:**
- Pedido confirmado → notificar al comprador
- Pedido en ruta → notificar a sucursal
- Entrega completada → notificar a comprador
**Config:** token en `global_settings` clave `whatsapp_token`

### 16.2 HikvisionService
**Acceso:** HTTP Digest Auth a IP de cámara en red local o VPN
**Cuándo se usa:**
- Vista de dispositivos en `config/dispositivos`
- Snapshots en tiempo real
**Importante:** Las cámaras deben estar en red accesible desde el servidor

### 16.3 ShellyService
**API:** Shelly Cloud REST API
**Cuándo se usa:**
- Toggle ON/OFF desde `api/shellyToggle`
- Estado en dashboard IoT
**Config:** `shelly_auth_key` en `global_settings`

### 16.4 TraccarService
**API:** Traccar REST API
**Cuándo se usa:**
- Mapa logística: posición en tiempo real de vehículos
- Historial de ruta por vehículo
**Config:** URL servidor Traccar + token en `global_settings`

### 16.5 FacturaloService
**API:** factura-lo.mx CFDI
**Cuándo se usa:**
- Módulo facturación admin
- Generar CFDI al confirmar pedido (si cliente lo solicita)
**Config:** API key en `global_settings`

---

## MÓDULO 17 — COMPONENTES VISUALES

### `views/components/header.php`
```html
<!-- Debe incluir: -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $pageTitle ?? APP_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/carnihub.css">
<!-- CDNs opcionales según página: -->
<!-- Chart.js, Leaflet.js, FullCalendar -->
```

### `views/components/sidebar_admin.php`
- Items de menú según rol:
  - SuperAdmin: Dashboard | Clientes | Productos | Pedidos | Logística | Inventario | Reportes | Usuarios | Configuración
  - Admin: mismos excepto Configuración global
- Item activo resaltado con borde rojo izquierdo
- Badge con número en Pedidos (pendientes) e Inventario (alertas)
- Collapsable en móvil (hamburger)

### `views/components/sidebar_cliente.php`
- Items: Inicio | Catálogo | Mis Pedidos | Pedidos Recurrentes | Sucursales | Reportes | Mi Cuenta
- Badge en catálogo con items en carrito
- Bottom nav en móvil (5 ítems principales)

---

## PLAN DE CORRECCIÓN POR PRIORIDAD

### 🔴 PRIORIDAD 1 — Lo que impide usar el sistema (bugs bloqueantes)

| # | Síntoma | Causa probable | Archivo a revisar |
|---|---------|----------------|-------------------|
| 1 | Página en blanco al entrar a dashboard | Error PHP sin mostrar (variables undefined) | `DashboardController.php`, `dashboard.php` |
| 2 | JSON en vez de HTML en alguna vista | `header('Content-Type: application/json')` no removido | Controlador del módulo afectado |
| 3 | 404 en rutas existentes | Método en controller con nombre diferente al esperado | `index.php` routes array vs método real |
| 4 | Error al subir imagen | Carpeta `public/uploads/` no existe en servidor | Crear manualmente con permisos 755 |
| 5 | Error al crear pedido | Transacción SQL falla por FK o campo faltante | `PedidoModel::crearConDetalle()` |

**Acción inmediata — crear carpetas uploads:**
En cPanel → File Manager → `public_html/carnihub/public/` → crear:
```
uploads/          (755)
uploads/productos/ (755)
uploads/evidencias/(755)
uploads/avatars/  (755)
```

### 🟡 PRIORIDAD 2 — Funcionalidad incompleta

| # | Módulo | Qué falta |
|---|--------|-----------|
| 6 | Carrito | Validar que precio escalonado se recalcula correctamente |
| 7 | Pedidos | Generar folio CHB-YYYY-NNNN único |
| 8 | Repartidor | Firma canvas + upload foto en `completarEntrega` |
| 9 | Config | Verificar que `ConfigModel::set()` persiste correctamente |
| 10 | Reportes | Queries de agregación con fechas |

### 🟢 PRIORIDAD 3 — Mejoras y servicios externos

| # | Módulo | Qué implementar |
|---|--------|-----------------|
| 11 | WhatsApp | Conectar token real y plantillas |
| 12 | Traccar | URL y token, mostrar mapa real |
| 13 | HikVision | Snapshot desde IP de cámara |
| 14 | Shelly | Toggle con auth key real |
| 15 | Facturación | CFDI con Factura-lo |

---

## ORDEN DE REVISIÓN/CORRECCIÓN SUGERIDO

```
Semana 1 — Que el sistema sea navegable
  [x] Fix .htaccess (DONE)
  [x] Seguridad login: brute force + cuentas de prueba eliminadas
  [x] Módulo registro público (comprador y repartidor)
  [x] Google Maps interactivo en registro (círculo / marcador draggable)
  [x] Logo del sistema: upload desde config admin, dinámico en sidebars
  [x] Nombre separado en 3 campos, avatares de usuario
  [x] Módulo Cuenta cliente (perfil + cambiar contraseña)
  [ ] Ejecutar migrations 001 y 002 en cPanel phpMyAdmin
  [ ] Configurar Google Maps API key en Configuración → APIs
  [ ] Verificar mail() funcionando en servidor
  [ ] Revisar y corregir cada página de admin una a una
  [ ] Crear carpetas uploads en servidor (si faltan)
  [ ] Probar login con cada rol

Semana 2 — Flujo de pedido completo
  [ ] Catálogo → precio escalonado en tiempo real
  [ ] Carrito → paso 1 al 4
  [ ] Confirmar pedido → registro en BD
  [ ] Ver pedido desde admin y desde cliente

Semana 3 — Logística y repartidor
  [ ] Crear ruta desde admin
  [ ] App repartidor: ver entregas del día
  [ ] Completar entrega con firma + foto
  [ ] Evidencia guardada en BD

Semana 4 — Configuración y servicios
  [ ] Guardar settings en global_settings
  [ ] CRUD dispositivos HikVision
  [ ] CRUD dispositivos Shelly
  [ ] Bitácora de logs

Semana 5 — Reportes y pulido
  [ ] Reportes con gráficas Chart.js
  [ ] Pedidos recurrentes
  [ ] Facturación CFDI
  [ ] Notificaciones WhatsApp
```

---

## CREDENCIALES DE PRUEBA (datos dummy Querétaro)

| Rol | Email | Password | Empresa |
|-----|-------|----------|---------|
| SuperAdmin | admin@carnihub.mx | admin123 | — |
| Comprador | juan.perez@carnihub.mx | admin123 | Taquería El Buen Sabor |
| Repartidor | luis.martinez@carnihub.mx | admin123 | — |

---

## NOTAS TÉCNICAS IMPORTANTES

1. **Todos los redirect usan `BASE_URL`:** `header('Location: ' . BASE_URL . 'modulo/accion')`
2. **Todas las imágenes usan `BASE_URL`:** `<img src="<?= BASE_URL ?>public/uploads/...">`
3. **Todos los `require` de vistas usan `ROOT_PATH`:** definido en `index.php` como `__DIR__`
4. **JSON en ApiController:** siempre `header('Content-Type: application/json')` + `exit` al final
5. **Seguridad básica:**
   - Inputs: `htmlspecialchars()` en vistas, no confiar en datos de usuario en SQL
   - Archivos: validar extensión + tamaño antes de `move_uploaded_file()`
   - Roles: cada controlador llama `requireRole()` o `requireAdmin()` al inicio

---

---

## CHECKLIST GENERAL DE AVANCE — v1.3 (2026-05-04)

### ✅ COMPLETADO

#### Infraestructura y autenticación
- [x] `.htaccess` con RewriteBase correcto (`/carnihub/`)
- [x] `config.php` con constantes globales (BASE_URL, UPLOAD_PATH, etc.)
- [x] `Database.php` — PDO singleton
- [x] `index.php` — Front Controller con rutas públicas/protegidas
- [x] `auth/login` con protección brute force (5 intentos / 2 min)
- [x] `auth/doLogin` con detección cuenta inactiva y mensaje específico
- [x] `auth/logout`

#### Registro público
- [x] Página de selección comprador/repartidor (`registro/index`)
- [x] Formulario comprador: datos personales + negocio + ubicación con mapa Google Maps visible inmediatamente
- [x] Formulario repartidor: datos personales + dropdown de municipios de México (sin mapa impreciso)
- [x] Validación email único, rate limit IP, hash contraseña
- [x] Creación de empresa automática para compradores
- [x] Verificación de correo con token de 24h
- [x] Página "revisa tu correo" post-registro
- [x] Email HTML con enlace de verificación

#### Módulo Cuenta cliente
- [x] `cuenta/perfil` — ver y editar datos personales
- [x] `cuenta/guardarPerfil` — actualizar nombre, email, teléfono, avatar
- [x] `cuenta/cambiarPassword` — con validación password actual

#### Configuración admin
- [x] `config/general` — nombre app, logo, zona horaria
- [x] Logo upload (PNG/JPG/WebP/SVG, máx 2MB) con preview y borrar
- [x] Logo dinámico en sidebars y formulario de registro
- [x] `config/apis` — campo Google Maps API key
- [x] `ConfigModel::get()` y `ConfigModel::set()` funcionando

#### Base de datos
- [x] Schema completo 27 tablas + datos dummy Querétaro
- [x] Migration 001: `login_intentos`, `verificacion_tokens`, `registro_intentos`, campos `telefono`/`ubicacion_*` en usuarios, `tipo_negocio` en empresas
- [x] Migration 002: claves `api_google_maps_key` y `app_logo` en `global_settings`

---

### 🔴 NECESARIO PARA QUE EL SISTEMA FUNCIONE EN SERVIDOR

| # | Tarea | Dónde hacerlo |
|---|-------|---------------|
| 1 | **Ejecutar migration 001** en BD de producción | cPanel → phpMyAdmin → `idactivo_carnihubdb` |
| 2 | **Ejecutar migration 002** en BD de producción | cPanel → phpMyAdmin |
| 3 | **Crear carpetas uploads** con permisos 755 | cPanel → File Manager → `public_html/carnihub/public/uploads/{productos,evidencias,avatars,logos}` |
| 4 | **Verificar `mail()`** con correo de prueba | Crear usuario en registro y ver si llega el email |
| 5 | **Google Maps API key** — obtener y configurar | Google Cloud Console → habilitar Maps JS API + Places API → pegar en Config → APIs |
| 6 | **Probar login con cada rol** en producción | Usar credenciales dummy del plan |

---

### 🟡 PRÓXIMOS MÓDULOS A DESARROLLAR

#### Semana 2 — Flujo de pedido completo (PRIORIDAD ALTA)
- [ ] Catálogo de productos con filtros por categoría
- [ ] Precios escalonados en tiempo real (AJAX `api/precioEscalonado`)
- [ ] Carrito: agregar productos por sucursal
- [ ] Paso 2: selección de fecha y ventana de entrega por sucursal
- [ ] Paso 3: resumen y selección método de pago
- [ ] Paso 4: confirmar pedido → INSERT en BD con folio CHB-YYYY-NNNN
- [ ] Vista de pedido desde admin y desde cliente

#### Semana 3 — Logística y repartidor
- [ ] Crear ruta desde admin con asignación de chofer
- [ ] App repartidor: ver entregas del día (UI oscura)
- [ ] Completar entrega: firma en canvas + foto con cámara
- [ ] Evidencia guardada en BD + actualizar estado pedido

#### Semana 4 — Módulos admin pendientes
- [ ] `cliente/index` y `cliente/detalle` (gestión empresas B2B)
- [ ] `producto/crear` y `producto/editar` con upload de imagen
- [ ] `inventario/index` con alertas de stock bajo
- [ ] `config/dispositivos` — CRUD HikVision y Shelly
- [ ] `config/bitacora` — visor de logs

#### Semana 5 — Reportes y servicios externos
- [ ] `reporte/ventas` con gráficas Chart.js y filtros por fecha/empresa
- [ ] Pedidos recurrentes (plantillas + generación)
- [ ] WhatsApp: notificación al confirmar pedido y al entregar
- [ ] Facturación CFDI con Factura-lo (opcional)
- [ ] Traccar: posición en tiempo real de vehículos en mapa logística

---

### ⚙️ LO QUE SE NECESITA (EXTERNOS) PARA AVANZAR

| Servicio | Para qué | Dónde obtener |
|----------|----------|---------------|
| **Google Maps API Key** | Mapa en registro comprador y autocompletado de direcciones | console.cloud.google.com → Maps JS API + Places API |
| **Cuenta de email SMTP** (opcional) | Mayor entregabilidad que `mail()` nativo | cPanel → Email Accounts o usar SendGrid/Mailgun |
| **WhatsApp Business API token** | Notificaciones de pedido al comprador | Meta for Developers o proveedor (Twilio, MessageBird) |
| **Traccar server** | Rastreo GPS de repartidores en mapa | traccar.org (autohosting) o servidor propio |
| **Shelly Cloud auth key** | Control de enchufes/dispositivos IoT | app.shelly.cloud → Account → API |
| **Factura-lo API key** | Generación de CFDI | factura.com o factura-lo.mx |

---

*Última actualización: 2026-05-04 v1.3 | Servidor: idactivos.digital/carnihub/*

---

## MÓDULO 1.5 — REGISTRO PÚBLICO (`registro/`)

> Flujo de auto-registro para compradores y repartidores, con verificación de correo.

### URLs (todas son públicas — no requieren sesión)
| URL | Método | Descripción |
|-----|--------|-------------|
| `registro/index` | GET | Elegir tipo: Comprador o Repartidor |
| `registro/comprador` | GET | Formulario de registro tipo comprador |
| `registro/repartidor` | GET | Formulario de registro tipo repartidor |
| `registro/guardar` | POST | Procesar el formulario y crear usuario |
| `registro/verificar/{token}` | GET | Activar cuenta con token de correo |
| `registro/pendiente` | GET | Página "revisa tu correo" post-registro |

### Flujo completo
```
1. Login page → botones "Soy Comprador" / "Soy Repartidor"
2. registro/index → cards visuales para elegir tipo
3. registro/{tipo} → formulario con campos personales + negocio/zona
4. POST registro/guardar →
   a. Validar campos (nombres, email, password ≥8, teléfono, ubicación)
   b. Verificar email único en usuarios
   c. Rate limit: máx 5 registros/IP por hora
   d. INSERT usuarios con activo=0
   e. Si comprador: INSERT empresas + UPDATE usuarios.empresa_id
   f. Generar token 64 chars, INSERT verificacion_tokens (expira 24h)
   g. Enviar correo con mail() via cPanel sendmail
   h. redirect registro/pendiente
5. Usuario clic en enlace del correo → registro/verificar/{token}
   a. Verificar token válido, no usado, no expirado
   b. UPDATE usuarios SET activo=1
   c. Marcar token como usado
   d. redirect auth/login con flash éxito
```

### Campos del formulario
**Ambos tipos:**
- nombre, apellido_paterno, apellido_materno (opt), email, teléfono
- password (min 8 chars), confirmar_password
- ubicacion (Google Maps Places autocomplete → guarda lat/lng)

**Solo comprador:**
- nombre_empresa (requerido), tipo_negocio (select)

### Configuración Google Maps — IMPLEMENTADO
- API Key almacenada en `global_settings.api_google_maps_key` (grupo `apis`)
- `RegistroController::getMapsKey()` lee la key desde `ConfigModel`, fallback a constante `GOOGLE_MAPS_KEY`
- Si no hay key configurada: el campo de ubicación funciona como texto libre (degradación elegante)
- Para activar: ir a **Configuración → APIs** y pegar la key de Google Cloud Console
  - Habilitar: **Maps JavaScript API** + **Places API**

### Mapa interactivo en registro_form.php — ACTUALIZADO v1.3
- **Comprador:** mapa visible desde el inicio (sin esperar selección de autocomplete); marcador draggable en Querétaro por defecto; buscar dirección centra y mueve el marcador; zoom 17 al seleccionar lugar
- **Repartidor:** se eliminó el mapa/círculo; ahora usa un `<select>` con municipios agrupados por estado (Querétaro, Guanajuato, Hidalgo, Estado de México, CDMX, Jalisco, Nuevo León + "Otro"); campo `ubicacion` guarda el valor "Municipio, ESTADO"
- Autocomplete con `google.maps.places.Autocomplete` restringido a México (solo comprador)
- `ubicacion_lat` y `ubicacion_lng` se guardan en campos hidden (solo comprador)
- Si no hay Maps key: campo texto libre para comprador, dropdown municipios para repartidor

### Tipo de negocio libre — IMPLEMENTADO
- Select con opciones fijas + "Otro…"
- Al seleccionar "Otro": aparece input libre (`tipo_negocio_otro`)
- Controller: si `tipo_negocio === 'otro'` usa el valor del campo libre

### Correo de verificación (cPanel)
- Usa `mail()` nativo de PHP (cPanel configura sendmail automáticamente)
- Asunto: *"Verifica tu cuenta en CarniHub"*
- Remitente: `noreply@{dominio}`
- Mensaje post-verificación: "¡Correo verificado exitosamente! Tu cuenta está activa. Ya puedes iniciar sesión."
- Mejora futura: PHPMailer con SMTP de cuenta cPanel para mayor entregabilidad


- [x] Migración SQL: tablas `login_intentos`, `verificacion_tokens`, `registro_intentos`
- [x] Migración SQL: columnas `telefono`, `ubicacion_*` en `usuarios`
- [x] Migración SQL: columna `tipo_negocio` en `empresas`
- [x] `RegistroController.php` completo (index / comprador / repartidor / guardar / verificar / pendiente)
- [x] Vista `registro.php` (selección de tipo)
- [x] Vista `registro_form.php` (formulario adaptable comprador/repartidor)
- [x] Google Maps interactivo en registro (mapa visible inmediato para comprador)
- [x] Repartidor: dropdown de municipios de México en lugar de mapa/círculo
- [x] Tipo de negocio con campo libre "Otro"
- [x] Logo dinámico en registro_form.php (query directa a global_settings)
- [x] Vista `verificar_pendiente.php` (página post-registro)
- [x] Mensaje de verificación mejorado: "Tu cuenta está activa. Ya puedes iniciar sesión."
- [x] `login.php` — cuentas de prueba eliminadas + botones de registro
- [x] `index.php` — rutas `registro/*` agregadas como públicas
- [x] `config.php` — constante `GOOGLE_MAPS_KEY`
- [ ] **Ejecutar migration 001 en cPanel phpMyAdmin**
- [ ] **Ejecutar migration 002 en cPanel phpMyAdmin** (`api_google_maps_key` + `app_logo`)
- [ ] Configurar Google Maps API key en **Configuración → APIs**
- [ ] Verificar que `mail()` funciona en el servidor (enviar prueba)
- [ ] Probar flujo completo: registro → correo → verificar → login

---

## MÓDULO 1 — AUTENTICACIÓN (`auth/`) — ACTUALIZADO

### Páginas
| URL | Método | Descripción |
|-----|--------|-------------|
| `auth/login` | GET | Formulario de login |
| `auth/doLogin` | POST | Procesar credenciales |
| `auth/logout` | GET | Cerrar sesión |

### Seguridad — Protección brute force
- Tabla `login_intentos`: registra IP + email + timestamp de cada fallo
- Antes de verificar credenciales: contar intentos de esa IP en los últimos **2 minutos**
- Si ≥ 5 intentos: bloquear con mensaje, **no procesar credenciales**
- Login exitoso: eliminar todos los intentos de esa IP
- Cuenta inactiva (`activo=0`): mensaje específico "Verifica tu correo"
- Mensaje progresivo: indica cuántos intentos restan antes del bloqueo

### Checklist Módulo 1
- [x] Protección brute force (5 intentos → bloqueo 2 min)
- [x] Detección de cuenta inactiva / sin verificar
- [x] Cuentas de prueba eliminadas de la vista login
- [x] Botones "Soy Comprador" / "Soy Repartidor" en login
- [ ] Probar login con cada rol en servidor real
- [ ] Verificar que el bloqueo funciona correctamente

---
