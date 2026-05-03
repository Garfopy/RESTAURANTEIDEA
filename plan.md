# CarniHub — Plan de Implementación y Estado del Sistema
**Versión:** 1.0.0 | **Fecha:** 2026-05-03 | **Stack:** PHP 8.3 · MySQL 5.7 · Tailwind CDN · MVC sin framework

---

## ESTADO ACTUAL (resumen ejecutivo)

| Capa | Archivos | Estado |
|------|----------|--------|
| Infraestructura (.htaccess, index.php, config) | 4 | ✅ Funcional tras fix RewriteBase |
| Controladores | 17 | ⚠️ Existen, revisar uno a uno |
| Modelos | 13 | ⚠️ Existen, validar queries vs BD real |
| Vistas | 46 | ⚠️ Existen, reportes de páginas en blanco o JSON |
| Servicios API | 5 | 🔴 Esqueletos, no conectados a tokens reales |
| BD (SQL) | 27 tablas | ✅ Schema completo + datos dummy |
| CSS / JS | 9 archivos | ⚠️ Existen, posibles rutas rotas |

**Problemas reportados:**
- Páginas que devuelven JSON en vez de HTML
- Páginas que dan 404 (ruta no registrada o método inexistente)
- Páginas en blanco (error PHP silenciado)

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

### Lógica `config/guardar` (POST)
```php
requireRole(['superadmin']);
foreach ($_POST as $clave => $valor) {
    if (str_starts_with($clave, 'setting_')) {
        $key = substr($clave, 8);  // quitar prefijo 'setting_'
        ConfigModel::set($key, $valor);
    }
}
// Si hay logo nuevo → subir imagen
LogModel::registrar($_SESSION['usuario']['id'], 'Configuración actualizada', 'config');
flash('success', 'Configuración guardada');
redirect('config/general');
```

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
  [ ] Revisar y corregir cada página de admin una a una
  [ ] Crear carpetas uploads en servidor
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

*Última actualización: 2026-05-03 | Servidor: idactivos.digital/carnihub/*
