<?php
/**
 * CapiRest — Front Controller / Router v1.0
 *
 * URL pattern: /{controller}/{action}/{param}
 * Portales:
 *   /restaurante/  → Admin local del restaurante
 *   /rest-{mod}/   → Módulos del restaurante (menú, pedidos, inventario, etc.)
 *   /rest-mesero/  → Portal mesero
 *   /rest-chef/    → Portal chef
 *   /rest-portero/ → Portal portero
 *   /menu/         → Menú público (sin login)
 *   /acceso/       → Login de staff por slug
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
                     'scanPortero','registrarSalidaPublica','checkSalida','gracias','stripeIntent',
                     'reservar','guardarReserva','cancelarReserva'];
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

// ── Ruta raíz → login ────────────────────────────────────────────────────────
if ($path === '') {
    $ctrlSlug = 'auth';
    $action   = 'login';
}
// ── Route map: URL slug → Controller class ────────────────────────────────────
$routes = [
    // Auth (público)
    'auth'          => 'AuthController',
    // Portal admin del restaurante
    'restaurante'   => 'RestauranteController',
    'rest-config'   => 'RestConfigController',
    'rest-mesa'     => 'RestMesaController',
    'rest-menu'     => 'RestMenuController',
    'rest-inventario' => 'RestInventarioController',
    'rest-pedido'   => 'RestPedidoController',
    'rest-finanzas' => 'RestFinanzasController',
    'rest-cliente'  => 'RestClienteController',
    'rest-reserva'  => 'RestReservaController',
    'rest-ticket'   => 'RestTicketController',
    // Portales staff
    'rest-mesero'   => 'RestMeseroController',
    'rest-chef'     => 'RestChefController',
    'rest-portero'  => 'RestPorteroController',
    'rest-staff'    => 'RestStaffController',
    'rest-propinas' => 'RestPropinaController',
    // Menú público (sin login)
    'menu'          => 'RestPublicoController',
    // Login de staff por slug de restaurante
    'acceso'        => 'StaffAccesoController',
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
    if (in_array($rol, ['admin_local', 'superadmin'], true)) {
        header('Location: ' . BASE_URL . 'restaurante/seleccionar'); exit;
    }
    if ($rol === 'mesero') {
        header('Location: ' . BASE_URL . 'rest-mesero/inicio'); exit;
    }
    if ($rol === 'chef') {
        header('Location: ' . BASE_URL . 'rest-chef/inicio'); exit;
    }
    if ($rol === 'portero') {
        header('Location: ' . BASE_URL . 'rest-portero/inicio'); exit;
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
