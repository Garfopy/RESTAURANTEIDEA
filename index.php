<?php
/**
 * CarniHub — Front Controller / Router v2.0
 *
 * URL pattern: /{controller}/{action}/{param}
 * Portales:
 *   /panel/     → SuperAdmin + Admin (plataforma)
 *   /empresa/   → Admin Empresa + Supervisor + Comprador
 *   /repartidor/→ Repartidor
 */

define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';

// ── Session ───────────────────────────────────────────────────────────────────
session_name(SESSION_NAME);
session_start();

// ── Parse URL ─────────────────────────────────────────────────────────────────
$path     = trim($_GET['url'] ?? '', '/');
$segments = array_values(array_filter(explode('/', $path)));

$ctrlSlug = strtolower($segments[0] ?? 'auth');
$action   = $segments[1] ?? 'index';
$param    = $segments[2] ?? null;

// ── Route map: URL slug → Controller class ────────────────────────────────────
$routes = [
    // Auth (público)
    'auth'              => 'AuthController',
    // API (AJAX)
    'api'               => 'ApiController',
    // Panel de plataforma (superadmin + admin)
    'panel'             => 'PanelController',
    'panel-empresa'     => 'EmpresaController',
    'panel-usuario'     => 'PanelUsuarioController',
    'panel-producto'    => 'PanelProductoController',
    'panel-inventario'  => 'PanelInventarioController',
    'panel-pedido'      => 'PanelPedidoController',
    'panel-logistica'   => 'PanelLogisticaController',
    'panel-reporte'     => 'PanelReporteController',
    'config'            => 'ConfigController',
    // Portal empresa (admin_empresa + supervisor + comprador)
    'empresa'           => 'EmpresaDashboardController',
    'empresa-usuario'   => 'EmpresaUsuarioController',
    'empresa-sucursal'  => 'EmpresaSucursalController',
    'empresa-vehiculo'  => 'EmpresaVehiculoController',
    'catalogo'          => 'CatalogoController',
    'carrito'           => 'CarritoController',
    'pedido'            => 'PedidoController',
    'recurrente'        => 'RecurrenteController',
    'limite'            => 'LimiteController',
    'empresa-reporte'   => 'EmpresaReporteController',
    'pago'              => 'PagoController',
    'cuenta'            => 'CuentaController',
    // App repartidor
    'repartidor'        => 'RepartidorController',
];

// ── Auth guard ────────────────────────────────────────────────────────────────
$publicPaths = ['auth/login', 'auth/dologin', 'auth/index'];

$currentPath = strtolower($ctrlSlug . '/' . $action);

if (!isset($_SESSION['usuario']) && !in_array($currentPath, $publicPaths, true)) {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

// ── Redirect root to correct portal ──────────────────────────────────────────
if ($ctrlSlug === 'auth' && $action === 'index' && isset($_SESSION['usuario'])) {
    $rol = $_SESSION['usuario']['rol_slug'] ?? '';
    if (in_array($rol, ['superadmin', 'admin'], true)) {
        header('Location: ' . BASE_URL . 'panel/dashboard'); exit;
    }
    if ($rol === 'repartidor') {
        header('Location: ' . BASE_URL . 'repartidor/inicio'); exit;
    }
    header('Location: ' . BASE_URL . 'empresa/dashboard'); exit;
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

require_once ROOT_PATH . '/app/controllers/BaseController.php';
require_once $controllerFile;

$controller = new $controllerClass();

if (!method_exists($controller, $action)) {
    http_response_code(404);
    echo '<h1>Acción no encontrada: ' . htmlspecialchars($action) . '</h1>';
    exit;
}

$controller->$action($param);
