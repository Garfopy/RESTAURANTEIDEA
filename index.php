<?php
/**
 * CarniHub — Front Controller / Router
 *
 * URL pattern: /{controller}/{action}/{param}
 * Example:    /dashboard/index   → DashboardController::index()
 *             /producto/detalle/5 → ProductoController::detalle(5)
 */

define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';

// ── Session ──────────────────────────────────────────────────────────────────
session_name(SESSION_NAME);
session_start();

// ── Parse URL ─────────────────────────────────────────────────────────────────
$path     = trim($_GET['url'] ?? '', '/');
$segments = array_values(array_filter(explode('/', $path)));

$ctrlSlug = strtolower($segments[0] ?? 'dashboard');
$action   = $segments[1] ?? 'index';
$param    = $segments[2] ?? null;

// Map URL slug → Controller class
$routes = [
    'auth'        => 'AuthController',
    'dashboard'   => 'DashboardController',
    'cliente'     => 'ClienteController',
    'sucursal'    => 'SucursalController',
    'producto'    => 'ProductoController',
    'carrito'     => 'CarritoController',
    'pedido'      => 'PedidoController',
    'recurrente'  => 'RecurrenteController',
    'logistica'   => 'LogisticaController',
    'inventario'  => 'InventarioController',
    'reporte'     => 'ReporteController',
    'usuario'     => 'UsuarioController',
    'config'      => 'ConfigController',
    'repartidor'  => 'RepartidorController',
    'api'         => 'ApiController',
    'cuenta'      => 'CuentaController',
    'registro'    => 'RegistroController',
];

// ── Auth guard ────────────────────────────────────────────────────────────────
$publicPaths = [
    'auth/login',
    'auth/dologin',
    'registro/index',
    'registro/comprador',
    'registro/repartidor',
    'registro/guardar',
    'registro/verificar',
    'registro/pendiente',
];

$currentPath = strtolower($ctrlSlug . '/' . $action);

if (!isset($_SESSION['usuario']) && !in_array($currentPath, $publicPaths, true)) {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
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
    http_response_code(500);
    echo '<h1>Controller not found: ' . htmlspecialchars($controllerClass) . '</h1>';
    exit;
}

// Load all models (simple autoload)
foreach (glob(ROOT_PATH . '/app/models/*.php') as $model) {
    require_once $model;
}

require_once $controllerFile;

$controller = new $controllerClass();

if (!method_exists($controller, $action)) {
    http_response_code(404);
    echo '<h1>Acción no encontrada: ' . htmlspecialchars($action) . '</h1>';
    exit;
}

$controller->$action($param);
