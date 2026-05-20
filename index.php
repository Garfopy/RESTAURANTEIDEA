<?php
/**
 * CarniHub — Front Controller / Router v2.1
 *
 * URL pattern: /{controller}/{action}/{param}
 * Portales:
 *   /panel/      → SuperAdmin + Admin (solo métricas y gestión de plataforma)
 *   /empresa/    → Admin Empresa (control total de su empresa)
 *   /supervisor/ → Supervisor (aprobaciones y supervisión)
 *   /comprador/  → Comprador (tienda y pedidos)
 *   /repartidor/ → Repartidor (app GPS de entregas)
 */

define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';

// ── Composer autoload ─────────────────────────────────────────────────────────
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

// ── Session ───────────────────────────────────────────────────────────────────
session_name(SESSION_NAME);
session_start();

// ── Parse URL ─────────────────────────────────────────────────────────────────
$path     = trim($_GET['url'] ?? '', '/');
$segments = array_values(array_filter(explode('/', $path)));

$ctrlSlug = strtolower($segments[0] ?? 'auth');
$action   = $segments[1] ?? 'index';
$param    = $segments[2] ?? null;

// Rutas públicas con slug en URL: /menu/{slug}, /menu/{slug}/ordenar, /menu/{slug}/pagar/{visitaId}, /acceso/{slug}
// Convención esperada por los controllers: param = "slug" o "slug/visitaId" (concat de segmentos restantes)
if (in_array($ctrlSlug, ['menu', 'acceso'], true)) {
    $knownActions = ['index','ordenar','pagar','confirmarPago','confirmacion','login',
                     'llamarMesero','cancelarPedido','estadoPedido','actualizarPropina','generarTicket',
                     'paypalCrear','paypalRetorno','paypalCancelar','entrarComensal',
                     'scanPortero','registrarSalidaPublica','gracias','stripeIntent',
                     'reservar','guardarReserva'];
    if ($action !== '' && in_array($action, $knownActions, true)) {
        // Forma /menu/{accion}/{slug}/{...} — concatenar segmentos a partir del 2
        $rest  = array_slice($segments, 2);
        $param = $rest ? implode('/', $rest) : null;
    } else {
        // Forma /menu/{slug}/{accion?}/{...} — el slug viene primero
        $slug  = $action;
        $sub   = $segments[2] ?? '';
        if ($sub && in_array($sub, $knownActions, true)) {
            $action = $sub;
            $rest   = array_slice($segments, 3);
            $param  = $slug . ($rest ? '/' . implode('/', $rest) : '');
        } else {
            // /menu/{slug} → index del slug
            $action = 'index';
            $param  = $slug;
        }
    }
}

// ── Ruta raíz → landing pública ──────────────────────────────────────────────
if ($path === '') {
    $ctrlSlug = 'landing';
    $action   = 'landing';
}
// ── Redirects 301: rutas antiguas → nuevas rutas SEO ─────────────────────
$oldRouteRedirects = [
    'taqueria'    => BASE_URL . 'distribuidora-carne-cerca-de-mi',
    'restaurantes' => BASE_URL . 'carnihub/cortes-de-carne-para-restaurantes',
    'cedis'       => BASE_URL . 'carnihub',
];
if (isset($oldRouteRedirects[$path])) {
    header('Location: ' . $oldRouteRedirects[$path], true, 301);
    exit;
}
// ── Nuevas rutas SEO HUB → PublicController ─────────────────────────────
$seoRoutes = [
    'distribuidora-carne-cerca-de-mi'             => 'taqueria',
    'carnihub/cortes-de-carne-para-restaurantes'  => 'restaurantes',
    'carnihub'                                    => 'carnihub',
];
if (isset($seoRoutes[$path])) {
    $ctrlSlug = 'landing';
    $action   = $seoRoutes[$path];
}
// ── Route map: URL slug → Controller class ────────────────────────────────────
$routes = [
    // Auth (público)
    'auth'                => 'AuthController',
    // API (AJAX)
    'api'                 => 'ApiController',
    // Panel de plataforma — solo superadmin + admin (métricas, empresas, usuarios, pedidos globales)
    'panel'               => 'PanelController',
    'panel-empresa'       => 'EmpresaController',
    'panel-usuario'       => 'PanelUsuarioController',
    'panel-pedido'        => 'PanelPedidoController',
    'panel-reporte'       => 'PanelReporteController',
    'admin-storage'       => 'AdminStorageController',
    'config'              => 'ConfigController',
    // Portal empresa — solo admin_empresa (gestión de su empresa)
    'empresa'             => 'EmpresaDashboardController',
    'empresa-usuario'     => 'EmpresaUsuarioController',
    'empresa-producto'    => 'EmpresaProductoController',
    'empresa-inventario'  => 'EmpresaInventarioController',
    'empresa-pedido'      => 'EmpresaPedidoController',
    'empresa-logistica'   => 'EmpresaLogisticaController',
    'empresa-combo'       => 'EmpresaComboController',
    'empresa-sucursal'    => 'EmpresaSucursalController',
    'empresa-vehiculo'    => 'EmpresaVehiculoController',
    'empresa-reporte'     => 'EmpresaReporteController',
    'empresa-evidencia'   => 'EmpresaEvidenciaController',
    'empresa-factura'     => 'EmpresaFacturaController',
    'empresa-config'      => 'EmpresaConfigController',
    // Portal supervisor — solo supervisor
    'supervisor'          => 'SupervisorController',
    // Portal comprador — solo comprador
    'comprador'           => 'CompradorController',
    'comprador-sucursal'  => 'CompradorSucursalController',
    'favorito'            => 'FavoritoController',
    // Módulos compartidos (acceso según rol validado en cada controller)
    'catalogo'            => 'CatalogoController',
    'carrito'             => 'CarritoController',
    'pedido'              => 'PedidoController',
    'recurrente'          => 'RecurrenteController',
    'limite'              => 'LimiteController',
    'pago'                => 'PagoController',
    'cuenta'              => 'CuentaController',
    // App repartidor
    'repartidor'          => 'RepartidorController',
    // SaaS — Suscripciones
    'suscripcion'         => 'SuscripcionController',
    'empresa-suscripcion' => 'EmpresaSuscripcionController',
    // Página pública de planes
    'planes'              => 'PublicController',
    // Landing page pública
    'landing'             => 'PublicController',
    // Landings de audiencia
    'taqueria'            => 'PublicController',
    'restaurantes'        => 'PublicController',
    // Módulo restaurante — portal del comprador/admin
    'restaurante'         => 'RestauranteController',
    'rest-config'         => 'RestConfigController',
    'rest-mesa'           => 'RestMesaController',
    'rest-menu'           => 'RestMenuController',
    'rest-inventario'     => 'RestInventarioController',
    'rest-pedido'         => 'RestPedidoController',
    'rest-finanzas'       => 'RestFinanzasController',
    'rest-cliente'        => 'RestClienteController',
    'rest-reserva'        => 'RestReservaController',
    'rest-ticket'         => 'RestTicketController',
    // Portales staff restaurante
    'rest-mesero'         => 'RestMeseroController',
    'rest-chef'           => 'RestChefController',
    'rest-portero'        => 'RestPorteroController',
    'rest-staff'          => 'RestStaffController',
    'rest-propinas'       => 'RestPropinaController',
    // Menú público (sin login)
    'menu'                => 'RestPublicoController',
    // Portal acceso staff por slug de restaurante
    'acceso'              => 'StaffAccesoController',
];

// ── Auth guard ────────────────────────────────────────────────────────────────
$publicPaths = [
    'auth/login',
    'auth/dologin',
    'auth/index',
    'auth/verificar',
    'auth/forgot',
    'auth/sendreset',
    'auth/reset',
    'auth/doreset',
    'planes/index',
    'planes/registro',
    'planes/checkout',
    'planes/retorno',
    'planes/cancelado',
    'planes/simularpago',
    'planes/aprobarpagotest',
    'suscripcion/webhook',
    'landing/landing',
    'taqueria/taqueria',
    'restaurantes/restaurantes',
    // Nuevas rutas SEO HUB
    'landing/taqueria',
    'landing/restaurantes',
    'landing/carnihub',
    // Menú público del restaurante
    'menu/index',
    'menu/ordenar',
    'menu/pagar',
    'menu/confirmarPago',
    'menu/confirmacion',
    'menu/llamarMesero',
    'menu/cancelarPedido',
    'menu/estadoPedido',
    'menu/actualizarPropina',
    'menu/paypalCrear',
    'menu/paypalRetorno',
    'menu/paypalCancelar',
    // Verificación pública de salida (QR del portero)
    'menu/scanPortero',
    'menu/registrarSalidaPublica',
    'menu/gracias',
    // Acceso staff (login por slug de restaurante)
    'acceso/index',
    'acceso/login',
    'acceso/entrarComensal',
];

$currentPath = strtolower($ctrlSlug . '/' . $action);

if (!isset($_SESSION['usuario']) && !in_array($currentPath, $publicPaths, true) && $ctrlSlug !== 'menu' && $ctrlSlug !== 'acceso') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

// ── Redirect root to correct portal ──────────────────────────────────────────
if ($ctrlSlug === 'auth' && $action === 'index' && isset($_SESSION['usuario'])) {
    $rol = $_SESSION['usuario']['rol_slug'] ?? '';
    if ($rol === 'superadmin') {
        header('Location: ' . BASE_URL . 'panel/dashboard'); exit;
    }
    if ($rol === 'admin_empresa') {
        header('Location: ' . BASE_URL . 'empresa/dashboard'); exit;
    }
    if ($rol === 'supervisor') {
        header('Location: ' . BASE_URL . 'supervisor/dashboard'); exit;
    }
    if ($rol === 'comprador') {
        header('Location: ' . BASE_URL . 'comprador/inicio'); exit;
    }
    if ($rol === 'repartidor') {
        header('Location: ' . BASE_URL . 'repartidor/inicio'); exit;
    }
    header('Location: ' . BASE_URL . 'auth/login'); exit;
}

// ── Dispatch ──────────────────────────────────────────────────────────────────
if (!array_key_exists($ctrlSlug, $routes)) {
    http_response_code(404);
    echo '<h1>404 — Página no encontrada</h1>';
    exit;
}

$controllerClass = $routes[$ctrlSlug];
$controllerFile  = ROOT_PATH . '/app/controllers/' . $controllerClass . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(501);
    echo '<h1>Módulo en construcción: ' . htmlspecialchars($controllerClass) . '</h1>';
    exit;
}

// ── Autoload models ───────────────────────────────────────────────────────────
foreach (glob(ROOT_PATH . '/app/models/*.php') as $model) {
    require_once $model;
}
foreach (glob(ROOT_PATH . '/app/services/*.php') as $service) {
    require_once $service;
}
foreach (glob(ROOT_PATH . '/app/helpers/*.php') as $helper) {
    require_once $helper;
}

require_once ROOT_PATH . '/app/controllers/BaseController.php';
require_once $controllerFile;

$controller = new $controllerClass();

if (!method_exists($controller, $action)) {
    http_response_code(404);
    echo '<h1>Acción no encontrada: ' . htmlspecialchars($action) . '</h1>';
    exit;
}

$controller->$action($param);
