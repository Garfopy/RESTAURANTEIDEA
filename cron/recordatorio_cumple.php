<?php
/**
 * cron/recordatorio_cumple.php
 *
 * Crea una promocion personal de cumpleanos y envia push a los clientes
 * activos que cumplen anos hoy.
 *
 * Ejecucion sugerida diaria:
 *   0 9 * * * php /ruta/al/proyecto/cron/recordatorio_cumple.php
 *
 * Prueba sin escribir ni enviar push:
 *   php cron/recordatorio_cumple.php --dry-run
 *
 * Reenviar push aun si ya existe log de hoy:
 *   php cron/recordatorio_cumple.php --resend
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('CLI_MODE', true);

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

$logDir = ROOT_PATH . '/cron/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
$logFile = $logDir . '/recordatorio_cumple.log';

$log = static function (string $message) use ($logFile): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    echo $line;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
};

$args = $argv ?? [];
$dryRun = in_array('--dry-run', $args, true);
$resend = in_array('--resend', $args, true);
$requireMarketingOptIn = filter_var(getenv('BIRTHDAY_PROMO_REQUIRE_MARKETING_OPT_IN') ?: false, FILTER_VALIDATE_BOOLEAN);

function birthday_table_exists(PDO $db, string $table): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*)
           FROM INFORMATION_SCHEMA.TABLES
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function birthday_column_exists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*)
           FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function birthday_db_setting(PDO $db, array $keys): string
{
    if (!birthday_table_exists($db, 'global_settings')) {
        return '';
    }

    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $db->prepare(
        "SELECT valor
           FROM global_settings
          WHERE clave IN ($placeholders)
            AND COALESCE(valor, '') <> ''
          LIMIT 1"
    );
    $stmt->execute($keys);
    return trim((string)($stmt->fetchColumn() ?: ''));
}

function birthday_insert_existing_columns(PDO $db, string $table, array $values): int
{
    $columns = [];
    $params = [];

    foreach ($values as $column => $value) {
        if (!birthday_column_exists($db, $table, (string)$column)) {
            continue;
        }
        $columns[] = (string)$column;
        $params[':' . $column] = $value;
    }

    if (empty($columns)) {
        throw new RuntimeException("No hay columnas compatibles para insertar en {$table}.");
    }

    $quotedColumns = array_map(static fn(string $column): string => "`{$column}`", $columns);
    $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
    $stmt = $db->prepare(
        'INSERT INTO `' . $table . '` (' . implode(', ', $quotedColumns) . ')
         VALUES (' . implode(', ', $placeholders) . ')'
    );
    $stmt->execute($params);

    return (int)$db->lastInsertId();
}

function birthday_ensure_notification_logs(PDO $db): void
{
    $db->exec(
        "CREATE TABLE IF NOT EXISTS `mobile_notification_logs` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `promotion_id` int(10) UNSIGNED DEFAULT NULL,
            `usuario_id` int(10) UNSIGNED NOT NULL,
            `fcm_token_id` int(10) UNSIGNED DEFAULT NULL,
            `fcm_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `provider` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fcm',
            `status` enum('pending','sent','failed','skipped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
            `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `body` text COLLATE utf8mb4_unicode_ci,
            `response` text COLLATE utf8mb4_unicode_ci,
            `error` text COLLATE utf8mb4_unicode_ci,
            `sent_at` datetime DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_mobile_notification_promotion` (`promotion_id`),
            KEY `idx_mobile_notification_usuario` (`usuario_id`),
            KEY `idx_mobile_notification_status` (`status`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function birthday_log_notification(
    PDO $db,
    int $promotionId,
    int $usuarioId,
    ?int $tokenId,
    ?string $token,
    string $status,
    string $title,
    string $body,
    ?string $response,
    ?string $error
): void {
    birthday_ensure_notification_logs($db);
    birthday_insert_existing_columns($db, 'mobile_notification_logs', [
        'promotion_id' => $promotionId > 0 ? $promotionId : null,
        'usuario_id' => $usuarioId,
        'fcm_token_id' => $tokenId,
        'fcm_token' => $token,
        'provider' => 'fcm',
        'status' => $status,
        'title' => $title,
        'body' => $body,
        'response' => $response,
        'error' => $error,
        'sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

function birthday_base64_url(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function birthday_http_post(string $url, array $headers, string $body): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 12,
        ]);
        if (defined('CURL_HTTP_VERSION_1_1')) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        }
        $responseBody = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseBody === false) {
            throw new RuntimeException($error ?: 'No se pudo conectar con FCM.');
        }

        return ['http_code' => $httpCode, 'body' => (string)$responseBody];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => 12,
            'ignore_errors' => true,
        ],
    ]);
    $responseBody = @file_get_contents($url, false, $context);
    if ($responseBody === false) {
        throw new RuntimeException('No se pudo conectar con FCM.');
    }

    $httpCode = 0;
    if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $httpCode = (int)$matches[1];
    }

    return ['http_code' => $httpCode, 'body' => (string)$responseBody];
}

function birthday_firebase_service_account(PDO $db): ?array
{
    $json = trim((string)(getenv('FIREBASE_SERVICE_ACCOUNT_JSON') ?: ($_ENV['FIREBASE_SERVICE_ACCOUNT_JSON'] ?? $_SERVER['FIREBASE_SERVICE_ACCOUNT_JSON'] ?? '')));

    if ($json === '') {
        $path = trim((string)(getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: ($_ENV['GOOGLE_APPLICATION_CREDENTIALS'] ?? $_SERVER['GOOGLE_APPLICATION_CREDENTIALS'] ?? '')));
        if ($path !== '' && is_readable($path)) {
            $json = (string)file_get_contents($path);
        }
    }

    if ($json === '') {
        foreach ([
            ROOT_PATH . '/amare-service-account.json',
            ROOT_PATH . '/firebase/amare-service-account.json',
            dirname(ROOT_PATH) . '/amare-service-account.json',
            dirname(ROOT_PATH) . '/firebase/amare-service-account.json',
        ] as $path) {
            if (is_readable($path)) {
                $json = (string)file_get_contents($path);
                break;
            }
        }
    }

    if ($json === '') {
        $json = birthday_db_setting($db, ['firebase_service_account_json', 'FIREBASE_SERVICE_ACCOUNT_JSON']);
    }

    if ($json === '') {
        return null;
    }

    $json = preg_replace('/^\xEF\xBB\xBF/', '', $json) ?? $json;
    $data = json_decode($json, true);
    if (!is_array($data) || ($data['type'] ?? '') !== 'service_account') {
        return null;
    }

    return $data;
}

function birthday_firebase_project_id(PDO $db, ?array $serviceAccount): string
{
    foreach (['FIREBASE_PROJECT_ID', 'GOOGLE_CLOUD_PROJECT', 'GCLOUD_PROJECT'] as $key) {
        $value = getenv($key) ?: ($_ENV[$key] ?? $_SERVER[$key] ?? '');
        if (trim((string)$value) !== '') {
            return trim((string)$value);
        }
    }

    $fromDb = birthday_db_setting($db, ['firebase_project_id', 'FIREBASE_PROJECT_ID']);
    if ($fromDb !== '') {
        return $fromDb;
    }

    return trim((string)($serviceAccount['project_id'] ?? ''));
}

function birthday_fcm_server_key(PDO $db): string
{
    foreach (['FCM_SERVER_KEY', 'FIREBASE_SERVER_KEY'] as $key) {
        $value = getenv($key) ?: ($_ENV[$key] ?? $_SERVER[$key] ?? '');
        if (trim((string)$value) !== '') {
            return trim((string)$value);
        }
    }

    return birthday_db_setting($db, ['fcm_server_key', 'firebase_server_key', 'FCM_SERVER_KEY', 'FIREBASE_SERVER_KEY']);
}

function birthday_fcm_access_token(array $config): string
{
    if (!function_exists('openssl_sign')) {
        throw new RuntimeException('La extension openssl de PHP es requerida para Firebase HTTP v1.');
    }

    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claims = [
        'iss' => (string)$config['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ];
    $unsigned = birthday_base64_url((string)json_encode($header)) . '.' . birthday_base64_url((string)json_encode($claims));
    $signature = '';
    if (!openssl_sign($unsigned, $signature, (string)$config['private_key'], OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('No se pudo firmar el JWT de Firebase.');
    }

    $result = birthday_http_post(
        'https://oauth2.googleapis.com/token',
        ['Content-Type: application/x-www-form-urlencoded'],
        http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $unsigned . '.' . birthday_base64_url($signature),
        ])
    );
    $decoded = json_decode($result['body'] ?? '', true);
    if ($result['http_code'] < 200 || $result['http_code'] >= 300 || empty($decoded['access_token'])) {
        throw new RuntimeException('No se pudo obtener access token de Firebase: ' . ($result['body'] ?? ''));
    }

    return trim((string)$decoded['access_token']);
}

function birthday_post_fcm(PDO $db, array $payload): array
{
    $serviceAccount = birthday_firebase_service_account($db);
    $projectId = birthday_firebase_project_id($db, $serviceAccount);

    if (
        is_array($serviceAccount)
        && $projectId !== ''
        && !empty($serviceAccount['client_email'])
        && !empty($serviceAccount['private_key'])
    ) {
        $accessToken = birthday_fcm_access_token([
            'client_email' => (string)$serviceAccount['client_email'],
            'private_key' => (string)$serviceAccount['private_key'],
        ]);
        $message = [
            'message' => [
                'token' => (string)($payload['to'] ?? ''),
                'notification' => [
                    'title' => (string)($payload['notification']['title'] ?? ''),
                    'body' => (string)($payload['notification']['body'] ?? ''),
                ],
                'data' => array_map('strval', $payload['data'] ?? []),
                'android' => ['priority' => 'HIGH'],
            ],
        ];
        if (!empty($payload['apns']) && is_array($payload['apns'])) {
            $message['message']['apns'] = $payload['apns'];
        }

        return birthday_http_post(
            'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send',
            [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'Content-Type: application/json; charset=UTF-8',
            ],
            (string)json_encode($message, JSON_UNESCAPED_UNICODE)
        );
    }

    $serverKey = birthday_fcm_server_key($db);
    if ($serverKey !== '') {
        return birthday_http_post(
            'https://fcm.googleapis.com/fcm/send',
            [
                'Authorization: key=' . $serverKey,
                'Content-Type: application/json',
            ],
            (string)json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }

    throw new RuntimeException('missing_fcm_config');
}

function birthday_has_fcm_config(PDO $db): bool
{
    $serviceAccount = birthday_firebase_service_account($db);
    $projectId = birthday_firebase_project_id($db, $serviceAccount);
    if (
        is_array($serviceAccount)
        && $projectId !== ''
        && !empty($serviceAccount['client_email'])
        && !empty($serviceAccount['private_key'])
    ) {
        return true;
    }

    return birthday_fcm_server_key($db) !== '';
}

function birthday_fcm_error_message(array|string|null $error): string
{
    if (is_string($error)) {
        return $error;
    }
    if (!is_array($error)) {
        return 'fcm_error';
    }

    $message = trim((string)($error['message'] ?? ''));
    if (!empty($error['status'])) {
        return (string)$error['status'] . ($message !== '' ? ': ' . $message : '');
    }

    return $message !== '' ? $message : 'fcm_error';
}

function birthday_token_is_invalid(?string $error): bool
{
    $error = strtoupper((string)$error);
    return str_contains($error, 'UNREGISTERED')
        || str_contains($error, 'REGISTRATION TOKEN')
        || str_contains($error, 'REQUESTED ENTITY WAS NOT FOUND');
}

function birthday_push_tokens(PDO $db, int $usuarioId): array
{
    if (!birthday_table_exists($db, 'mobile_push_tokens')) {
        return [];
    }

    $enabledCol = birthday_column_exists($db, 'mobile_push_tokens', 'enabled') ? 'enabled' : null;
    $platformCol = birthday_column_exists($db, 'mobile_push_tokens', 'platform') ? 'platform' : null;
    $where = ['usuario_id = ?'];
    if ($enabledCol !== null) {
        $where[] = "COALESCE(`{$enabledCol}`, 1) = 1";
    }

    $stmt = $db->prepare(
        'SELECT id, '
        . ($platformCol ? "`{$platformCol}` AS platform, " : 'NULL AS platform, ')
        . 'fcm_token AS token
           FROM mobile_push_tokens
          WHERE ' . implode(' AND ', $where) . "
            AND fcm_token <> ''
          ORDER BY id DESC"
    );
    $stmt->execute([$usuarioId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function birthday_disable_token(PDO $db, int $tokenId): void
{
    if ($tokenId <= 0 || !birthday_column_exists($db, 'mobile_push_tokens', 'enabled')) {
        return;
    }

    $stmt = $db->prepare('UPDATE mobile_push_tokens SET enabled = 0 WHERE id = ?');
    $stmt->execute([$tokenId]);
}

function birthday_notification_already_logged(PDO $db, int $promotionId): bool
{
    if (!birthday_table_exists($db, 'mobile_notification_logs')) {
        return false;
    }

    $stmt = $db->prepare(
        'SELECT COUNT(*)
           FROM mobile_notification_logs
          WHERE promotion_id = ?
            AND DATE(created_at) = CURDATE()
            AND status IN ("sent", "failed", "skipped")'
    );
    $stmt->execute([$promotionId]);
    return (int)$stmt->fetchColumn() > 0;
}

function birthday_existing_promotion(PDO $db, int $usuarioId, string $code): ?array
{
    $stmt = $db->prepare(
        'SELECT *
           FROM mobile_promociones
          WHERE usuario_id = ?
            AND UPPER(code) = UPPER(?)
          ORDER BY id DESC
          LIMIT 1'
    );
    $stmt->execute([$usuarioId, $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function birthday_create_promotion(PDO $db, array $user, string $code, string $expiresAt, string $createdAt): int
{
    $title = 'Feliz cumpleanos: 10% OFF';
    $description = 'Hoy celebramos contigo. Usa este codigo y recibe 10% de descuento en todo el menu.';

    return birthday_insert_existing_columns($db, 'mobile_promociones', [
        'usuario_id' => (int)$user['id'],
        'producto_id' => null,
        'platillo_id' => null,
        'titulo' => $title,
        'descripcion' => $description,
        'imagen' => null,
        'deep_link' => 'amare://promociones?code=' . rawurlencode($code),
        'code' => $code,
        'discount_type' => 'percent',
        'discount_value' => 10.00,
        'tipo_descuento' => 'porcentaje',
        'valor_descuento' => 10.00,
        'scope_tipo' => 'all',
        'scope_ids' => null,
        'buy_qty' => null,
        'pay_qty' => null,
        'min_subtotal' => 0.00,
        'max_uses' => 1,
        'combinable' => 0,
        'activo' => 1,
        'expires_at' => $expiresAt,
        'created_at' => $createdAt,
        'created_by' => null,
    ]);
}

try {
    $db = Database::getInstance();
    foreach (['mobile_usuarios', 'mobile_promociones'] as $table) {
        if (!birthday_table_exists($db, $table)) {
            throw new RuntimeException("Falta la tabla {$table}.");
        }
    }
    if (!birthday_column_exists($db, 'mobile_usuarios', 'fecha_nacimiento')) {
        throw new RuntimeException('Falta la columna mobile_usuarios.fecha_nacimiento.');
    }

    $timeRow = $db->query('SELECT CURDATE() AS today, YEAR(CURDATE()) AS current_year, NOW() AS now_db')->fetch(PDO::FETCH_ASSOC) ?: [];
    $today = (string)($timeRow['today'] ?? date('Y-m-d'));
    $currentYear = (int)($timeRow['current_year'] ?? date('Y'));
    $nowDb = (string)($timeRow['now_db'] ?? date('Y-m-d H:i:s'));

    $where = [
        'mu.activo = 1',
        "mu.rol = 'user'",
        'mu.fecha_nacimiento IS NOT NULL',
        "DATE_FORMAT(mu.fecha_nacimiento, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')",
    ];
    if ($requireMarketingOptIn && birthday_column_exists($db, 'mobile_usuarios', 'marketing_opt_in')) {
        $where[] = 'COALESCE(mu.marketing_opt_in, 0) = 1';
    }

    $appJoin = '';
    if (
        birthday_column_exists($db, 'mobile_usuarios', 'current_restaurante_id')
        && birthday_column_exists($db, 'rest_restaurantes', 'app_movil_habilitada')
    ) {
        $appJoin = ' INNER JOIN rest_restaurantes rr
                       ON rr.id = mu.current_restaurante_id
                      AND rr.app_movil_habilitada = 1';
    }

    $stmt = $db->query(
        'SELECT mu.id, mu.nombre, mu.email, mu.fecha_nacimiento
           FROM mobile_usuarios mu
           ' . $appJoin . '
          WHERE ' . implode(' AND ', $where) . '
          ORDER BY mu.id ASC'
    );
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $log('Inicio recordatorio_cumple'
        . ($dryRun ? ' [dry-run]' : '')
        . ($resend ? ' [resend]' : '')
        . ' now_db=' . (string)($timeRow['now_db'] ?? '?')
        . ' users=' . count($users));

    $created = 0;
    $existing = 0;
    $sent = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($users as $user) {
        $usuarioId = (int)$user['id'];
        $name = trim((string)($user['nombre'] ?? ''));
        $code = 'CUMPLE-' . $currentYear . '-' . $usuarioId;

        $promotion = birthday_existing_promotion($db, $usuarioId, $code);
        if ($promotion) {
            $promotionId = (int)$promotion['id'];
            $existing++;
            $log("EXISTS usuario={$usuarioId} promo={$promotionId} code={$code}");
        } elseif ($dryRun) {
            $promotionId = 0;
            $created++;
            $log("DRY CREATE usuario={$usuarioId} nombre=\"{$name}\" code={$code} expires={$today} 23:59:59");
        } else {
            $promotionId = birthday_create_promotion($db, $user, $code, $today . ' 23:59:59', $nowDb);
            $created++;
            $log("CREATED usuario={$usuarioId} promo={$promotionId} code={$code}");
        }

        if ($dryRun) {
            $tokens = birthday_push_tokens($db, $usuarioId);
            $log("DRY PUSH usuario={$usuarioId} tokens=" . count($tokens));
            continue;
        }

        if ($promotionId <= 0) {
            $skipped++;
            continue;
        }

        if (!$resend && birthday_notification_already_logged($db, $promotionId)) {
            $skipped++;
            $log("SKIP usuario={$usuarioId} promo={$promotionId} reason=already_logged_today");
            continue;
        }

        $tokens = birthday_push_tokens($db, $usuarioId);
        $title = 'Feliz cumpleanos de parte de Amare';
        $body = 'Hoy tienes 10% OFF en todo el menu. Usa tu codigo ' . $code . '.';
        $deepLink = 'amare://promociones?code=' . rawurlencode($code);

        if (empty($tokens)) {
            birthday_log_notification($db, $promotionId, $usuarioId, null, null, 'skipped', $title, $body, null, 'no_push_token');
            $skipped++;
            $log("SKIP usuario={$usuarioId} promo={$promotionId} reason=no_push_token");
            continue;
        }

        if (!birthday_has_fcm_config($db)) {
            foreach ($tokens as $tokenRow) {
                birthday_log_notification(
                    $db,
                    $promotionId,
                    $usuarioId,
                    (int)($tokenRow['id'] ?? 0) ?: null,
                    trim((string)($tokenRow['token'] ?? '')) ?: null,
                    'skipped',
                    $title,
                    $body,
                    null,
                    'missing_fcm_config'
                );
                $skipped++;
            }
            $log("SKIP usuario={$usuarioId} promo={$promotionId} reason=missing_fcm_config");
            continue;
        }

        foreach ($tokens as $tokenRow) {
            $token = trim((string)($tokenRow['token'] ?? ''));
            if ($token === '') {
                continue;
            }

            $platform = strtolower(trim((string)($tokenRow['platform'] ?? '')));
            $payload = [
                'to' => $token,
                'priority' => 'high',
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'type' => 'promotion',
                    'promotion_id' => (string)$promotionId,
                    'usuario_id' => (string)$usuarioId,
                    'code' => $code,
                    'promo_code' => $code,
                    'route' => '/promociones',
                    'screen' => 'promociones',
                    'deep_link' => $deepLink,
                ],
            ];
            if ($platform === 'ios') {
                $payload['apns'] = [
                    'headers' => ['apns-priority' => '10'],
                    'payload' => ['aps' => ['sound' => 'default']],
                ];
            }

            try {
                $result = birthday_post_fcm($db, $payload);
                $decoded = json_decode($result['body'] ?? '', true);
                $legacySuccess = is_array($decoded) && array_key_exists('success', $decoded) ? (int)($decoded['success'] ?? 0) : null;
                $v1Success = is_array($decoded) && !empty($decoded['name']);
                $ok = $result['http_code'] >= 200
                    && $result['http_code'] < 300
                    && ($v1Success || $legacySuccess === null || $legacySuccess > 0);
                $status = $ok ? 'sent' : 'failed';
                $error = $ok ? null : ('fcm_http_' . $result['http_code']);
                if (!$ok && is_array($decoded) && !empty($decoded['results'][0]['error'])) {
                    $error = (string)$decoded['results'][0]['error'];
                } elseif (!$ok && is_array($decoded) && !empty($decoded['error'])) {
                    $error = birthday_fcm_error_message($decoded['error']);
                }

                birthday_log_notification(
                    $db,
                    $promotionId,
                    $usuarioId,
                    (int)($tokenRow['id'] ?? 0) ?: null,
                    $token,
                    $status,
                    $title,
                    $body,
                    $result['body'] ?? '',
                    $error
                );

                if ($ok) {
                    $sent++;
                    $log("SENT usuario={$usuarioId} promo={$promotionId} token_id=" . ((int)($tokenRow['id'] ?? 0)));
                } else {
                    $failed++;
                    $log("FAIL usuario={$usuarioId} promo={$promotionId} error=" . ($error ?? 'unknown'));
                    if (birthday_token_is_invalid($error)) {
                        birthday_disable_token($db, (int)($tokenRow['id'] ?? 0));
                    }
                }
            } catch (Throwable $e) {
                birthday_log_notification(
                    $db,
                    $promotionId,
                    $usuarioId,
                    (int)($tokenRow['id'] ?? 0) ?: null,
                    $token,
                    'failed',
                    $title,
                    $body,
                    null,
                    $e->getMessage()
                );
                $failed++;
                $log("FAIL usuario={$usuarioId} promo={$promotionId} error=" . $e->getMessage());
            }
        }
    }

    $log("Terminado. creadas={$created} existentes={$existing} enviadas={$sent} omitidas={$skipped} fallidas={$failed}");
    exit($failed > 0 ? 1 : 0);
} catch (Throwable $e) {
    $log('ERROR: ' . $e->getMessage());
    exit(1);
}
