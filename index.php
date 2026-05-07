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

// ── Ruta raíz → landing pública ──────────────────────────────────────────────
if ($path === '') {
    $ctrlSlug = 'landing';
    $action   = 'landing';
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
    // Portal supervisor — solo supervisor
    'supervisor'          => 'SupervisorController',
    // Portal comprador — solo comprador
    'comprador'           => 'CompradorController',
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
];

// ── Auth guard ────────────────────────────────────────────────────────────────
$publicPaths = [
    'auth/login',
    'auth/dologin',
    'auth/index',
    'auth/verificar',
    'planes/index',
    'planes/registro',
    'planes/checkout',
    'planes/retorno',
    'planes/cancelado',
    'planes/simularpago',
    'planes/aprobarpagotest',
    'suscripcion/webhook',
    'landing/landing'
];

$currentPath = strtolower($ctrlSlug . '/' . $action);

if (!isset($_SESSION['usuario']) && !in_array($currentPath, $publicPaths, true)) {
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
