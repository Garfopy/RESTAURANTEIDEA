<?php
/**
 * CapiRest — Global Configuration
 * Auto-detects BASE_URL regardless of subdirectory installation.
 */

// In CLI/cron there is no reliable HTTP_HOST/SCRIPT_NAME. Allow cPanel cron
// to inject the public URL with APP_URL, BASE_URL or CARNIHUB_BASE_URL.
$configuredBaseUrl = getenv('APP_URL') ?: getenv('BASE_URL') ?: getenv('CARNIHUB_BASE_URL') ?: '';

if ($configuredBaseUrl !== '') {
    $parsedPath = parse_url($configuredBaseUrl, PHP_URL_PATH);
    $basePath   = $parsedPath ? '/' . trim($parsedPath, '/') . '/' : '/';
    define('BASE_URL', rtrim($configuredBaseUrl, '/') . '/');
    define('BASE_URL_PATH', $basePath);
} else {
    // Detect protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Detect base path from actual script location
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $basePath  = ($scriptDir === '/') ? '/' : rtrim($scriptDir, '/') . '/';

    define('BASE_URL',      $protocol . $host . $basePath);
    define('BASE_URL_PATH', $basePath);
}
define('BASE_PATH',     dirname(__DIR__));   // project root

// Application
define('APP_NAME',      'CapiRest');
define('APP_VERSION',   '1.0.0');
define('APP_LOCALE',    'es_MX');

// Default brand color (overridable from global_settings)
define('BRAND_COLOR',   '#C8102E');

// Session name
define('SESSION_NAME',  'capirest_session');
define('SESSION_LIFETIME_SECONDS', 315360000); // 10 years; no automatic logout by inactivity.
define('SESSION_SAVE_PATH', BASE_PATH . '/storage/sessions');

// Despliegue standalone (solo restaurante, sin SaaS multi-empresa)
if (!defined('RESTAURANTE_STANDALONE')) define('RESTAURANTE_STANDALONE', true);

// Upload directories
define('UPLOAD_PATH',   BASE_PATH . '/public/uploads/');
define('UPLOAD_URL',    BASE_URL  . 'public/uploads/');

// Pagination
define('PER_PAGE', 20);

// Google Maps Places API key (agregar en cPanel o aquí directamente)
define('GOOGLE_MAPS_KEY', getenv('GOOGLE_MAPS_KEY') ?: '');

// Stripe — pago con tarjeta (MXN)
define('STRIPE_PUBLIC_KEY', getenv('STRIPE_PUBLIC_KEY') ?: '');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');

// FacturAPI - timbrado CFDI en modo prueba/produccion segun la llave usada.
define('FACTURAPI_SECRET_KEY', getenv('FACTURAPI_SECRET_KEY') ?: '');
define('FACTURAPI_AUTO_STAMP', filter_var(getenv('FACTURAPI_AUTO_STAMP') ?: false, FILTER_VALIDATE_BOOLEAN));
define('FACTURAPI_PRODUCT_KEY', getenv('FACTURAPI_PRODUCT_KEY') ?: '90101501');
define('FACTURAPI_UNIT_KEY', getenv('FACTURAPI_UNIT_KEY') ?: 'E48');
define('FACTURAPI_TAX_INCLUDED', filter_var(getenv('FACTURAPI_TAX_INCLUDED') ?: true, FILTER_VALIDATE_BOOLEAN));
define('FACTURAPI_TAX_RATE', (float)(getenv('FACTURAPI_TAX_RATE') ?: 0.16));
