<?php
/**
 * cron/recordatorio_reservas.php
 *
 * Envía un recordatorio por correo a los comensales que tienen
 * reservación MAÑANA (24h antes) y aún no han recibido el aviso.
 *
 * Ejecutar diariamente (sugerido 09:00):
 *   0 9 * * *  php /ruta/al/proyecto/cron/recordatorio_reservas.php
 *
 * Es idempotente: solo manda a quienes tengan recordatorio_enviado = 0.
 */

define('ROOT_PATH', dirname(__DIR__));

$appUrl = getenv('APP_URL') ?: getenv('BASE_URL') ?: getenv('CARNIHUB_BASE_URL') ?: '';
if (PHP_SAPI === 'cli' && $appUrl !== '') {
    $parts = parse_url($appUrl);
    if (!empty($parts['host'])) {
        $_SERVER['HTTPS'] = (($parts['scheme'] ?? 'https') === 'https') ? 'on' : 'off';
        $_SERVER['HTTP_HOST'] = $parts['host'] . (!empty($parts['port']) ? ':' . $parts['port'] : '');
        $_SERVER['SCRIPT_NAME'] = rtrim($parts['path'] ?? '/', '/') . '/index.php';
    }
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

require_once ROOT_PATH . '/app/models/BaseModel.php';
foreach (glob(ROOT_PATH . '/app/models/*.php') as $f) {
    if (basename($f) !== 'BaseModel.php') require_once $f;
}
foreach (glob(ROOT_PATH . '/app/services/*.php') as $f) require_once $f;

$logDir = ROOT_PATH . '/cron/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
$logFile = $logDir . '/recordatorio_reservas.log';
$log = function (string $m): void {
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . "] $m\n";
    echo $line;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
};

$dryRun = in_array('--dry-run', $argv ?? [], true);

$columnExists = function (PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare(
        "SELECT COUNT(*)
           FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
};

try {
    $db = Database::getInstance();
    foreach (['confirmacion_enviada', 'recordatorio_enviado'] as $column) {
        if (!$columnExists($db, 'rest_reservaciones', $column)) {
            $log("Falta la columna rest_reservaciones.$column. Ejecuta migrations/053_reservaciones_email_flags.sql en la BD de cPanel.");
            exit(1);
        }
    }

    $timeRow = $db->query("SELECT NOW() AS now_db, CURDATE() AS hoy_db, DATE_ADD(CURDATE(), INTERVAL 1 DAY) AS manana_db")->fetch(PDO::FETCH_ASSOC);
    $log('Inicio cron recordatorio_reservas' . ($dryRun ? ' [dry-run]' : ''));
    $log('BASE_URL=' . BASE_URL);
    $log('Fecha BD: now=' . ($timeRow['now_db'] ?? '?') . ' hoy=' . ($timeRow['hoy_db'] ?? '?') . ' manana=' . ($timeRow['manana_db'] ?? '?'));

    $reservaModel = new RestReservaModel();
    $email        = new EmailService();
    $emailStatus  = $email->getConfigStatus();
    $log('SMTP configured=' . ($emailStatus['configured'] ? 'si' : 'no')
        . ' host=' . ($emailStatus['smtp_host'] ? 'si' : 'no')
        . ' user=' . ($emailStatus['smtp_username'] ? 'si' : 'no')
        . ' from=' . ($emailStatus['smtp_from_email'] ? 'si' : 'no')
        . ' PHPMailer=' . (class_exists(\PHPMailer\PHPMailer\PHPMailer::class) ? 'si' : 'no')
        . ' mail()=' . (function_exists('mail') ? 'si' : 'no'));

    if (!$email->isConfigured()) {
        $log('SMTP no configurado; se intentara fallback con mail() si el servidor lo permite.');
    }

    $pendientes = $reservaModel->getParaRecordatorio();
} catch (Throwable $e) {
    $log('ERROR preflight: ' . $e->getMessage());
    exit(1);
}

$log('Recordatorios pendientes: ' . count($pendientes));

$enviados = 0;
$fallos   = 0;

foreach ($pendientes as $r) {
    $restaurante = [
        'nombre'         => $r['rest_nombre']    ?? '',
        'slug'           => $r['rest_slug']      ?? '',
        'telefono'       => $r['rest_telefono']  ?? '',
        'direccion'      => $r['rest_direccion'] ?? '',
        'color_primario' => $r['color_primario'] ?? '#C8102E',
    ];

    $cancelUrl = BASE_URL . 'menu/' . $restaurante['slug'] . '/cancelarReserva/' . (int)$r['id'];

    if ($dryRun) {
        $log("DRY -> reserva #{$r['id']} {$r['email']} {$restaurante['nombre']} {$r['hora']} $cancelUrl");
        continue;
    }

    try {
        $ok = $email->enviarRecordatorioReserva(
            $r['email'],
            $restaurante,
            [
                'nombre'      => $r['nombre'],
                'fecha'       => $r['fecha'] ?? '',
                'hora'        => $r['hora'],
                'personas'    => $r['personas'],
                'mesa_nombre' => $r['mesa_nombre'] ?? null,
            ],
            $cancelUrl
        );
    } catch (Throwable $e) {
        $ok = false;
        $log("ERROR -> reserva #{$r['id']} {$r['email']}: " . $e->getMessage());
    }

    if ($ok) {
        $reservaModel->marcarRecordatorioEnviado((int)$r['id']);
        $enviados++;
        $log("OK  → reserva #{$r['id']}  {$r['email']}");
    } else {
        $fallos++;
        $log("FAIL → reserva #{$r['id']}  {$r['email']}");
    }
}

$log("Terminado. Enviados: $enviados — Fallos: $fallos");
