<?php
/**
 * PASO 2: Prueba del endpoint token simplificado
 * Accede a: https://idactivos.digital/api/auth/token.php
 * Esperado: JSON con datos de sesión
 */

define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));

// Cargar config básico
require_once ROOT_PATH . '/config/config.php';

// Iniciar sesión (sin suffix en este test)
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'samesite' => 'Lax']);
session_name(SESSION_NAME);
session_start();

header('Content-Type: application/json; charset=utf-8');

// CORS headers (mismo que ApiController)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    http_response_code(204);
    exit;
}

// Respuesta de prueba
echo json_encode([
    'success' => true,
    'message' => 'Token endpoint works (simplified test)',
    'debug' => [
        'session_existe' => isset($_SESSION['usuario']) ? 'SÍ' : 'NO',
        'usuario' => $_SESSION['usuario'] ?? null,
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'NULL',
        'timestamp' => date('Y-m-d H:i:s')
    ]
]);
