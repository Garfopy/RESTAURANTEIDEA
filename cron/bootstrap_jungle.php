<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$options = getopt('', ['email:', 'password:', 'nombre::']);
$email = strtolower(trim((string)($options['email'] ?? '')));
$password = (string)($options['password'] ?? '');
$nombre = trim((string)($options['nombre'] ?? 'Administrador')) ?: 'Administrador';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Uso: php cron/bootstrap_jungle.php --email=admin@dominio.com --password='CLAVE_DE_12_O_MAS' [--nombre='Nombre']\n");
    exit(1);
}

$rootPath = dirname(__DIR__);
$databaseFile = $rootPath . '/config/database.php';
if (!is_file($databaseFile)) {
    fwrite(STDERR, "Falta config/database.php. Copia y completa config/database.example.php.\n");
    exit(1);
}

require_once $databaseFile;
$db = Database::getInstance();
$db->beginTransaction();

try {
    $roles = [
        [1, 'Super Admin', 'superadmin'],
        [2, 'Admin Restaurante', 'admin_restaurante'],
        [3, 'Admin Empresa', 'admin_empresa'],
        [4, 'Supervisor', 'supervisor'],
        [5, 'Comprador', 'comprador'],
        [7, 'Mesero', 'mesero'],
        [8, 'Chef', 'chef'],
        [9, 'Portero', 'portero'],
        [10, 'Admin Local', 'admin_local'],
        [11, 'Barra', 'barra'],
        [12, 'Programador', 'programador'],
    ];
    $roleStmt = $db->prepare(
        'INSERT INTO roles (id, nombre, slug) VALUES (?, ?, ?) '
        . 'ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), slug = VALUES(slug)'
    );
    foreach ($roles as $role) {
        $roleStmt->execute($role);
    }

    $stmt = $db->prepare('SELECT id FROM empresas WHERE razon_social = ? LIMIT 1');
    $stmt->execute(['Jungle Pizza Zihuatanejo']);
    $empresaId = (int)($stmt->fetchColumn() ?: 0);
    if (!$empresaId) {
        $stmt = $db->prepare(
            "INSERT INTO empresas (razon_social, tipo_negocio, email, telefono, activo) VALUES (?, 'restaurante', ?, ?, 1)"
        );
        $stmt->execute(['Jungle Pizza Zihuatanejo', $email, '7551002309']);
        $empresaId = (int)$db->lastInsertId();
    }

    $stmt = $db->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $usuarioId = (int)($stmt->fetchColumn() ?: 0);
    if (!$usuarioId) {
        $stmt = $db->prepare(
            'INSERT INTO usuarios '
            . '(nombre, apellido_paterno, email, email_verificado, primer_login_completado, password, rol_id, empresa_id, activo) '
            . 'VALUES (?, ?, ?, 1, 0, ?, 2, ?, 1)'
        );
        $stmt->execute([$nombre, 'Jungle', $email, password_hash($password, PASSWORD_BCRYPT), $empresaId]);
        $usuarioId = (int)$db->lastInsertId();
    }

    $stmt = $db->prepare('SELECT id FROM rest_restaurantes WHERE slug = ? LIMIT 1');
    $stmt->execute(['jungle-pizza-zihuatanejo']);
    $restauranteId = (int)($stmt->fetchColumn() ?: 0);
    if (!$restauranteId) {
        $stmt = $db->prepare(
            'INSERT INTO rest_restaurantes '
            . '(empresa_id, comprador_id, nombre, slug, logo, imagen_banner, color_primario, color_secundario, descripcion, telefono, direccion, horario_apertura, horario_cierre, activo, reservas_habilitadas, menu_principal) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 1)'
        );
        $stmt->execute([
            $empresaId,
            $usuarioId,
            'Jungle Pizza',
            'jungle-pizza-zihuatanejo',
            'base/redesign-assets/jungle-pizza-logo-420.webp',
            'base/redesign-assets/hero-jungle-pizza-1600.webp',
            '#8E1730',
            '#13212B',
            'Pizza al horno de leña, margaritas y noches relajadas en Playa La Ropa, Zihuatanejo.',
            '7551002309',
            'Camino Escénico a Playa Las Gatas, La Ropa, 40895 Zihuatanejo, Guerrero',
            '16:00:00',
            '22:00:00',
        ]);
        $restauranteId = (int)$db->lastInsertId();
    }

    $stmt = $db->prepare(
        'UPDATE usuarios SET empresa_id = ?, restaurante_id = ?, restaurante_activo = 1 WHERE id = ?'
    );
    $stmt->execute([$empresaId, $restauranteId, $usuarioId]);

    $stmt = $db->prepare(
        "INSERT INTO rest_configuracion (restaurante_id, metodos_pago, tipos_entrega, activo) VALUES (?, ?, ?, 1) "
        . 'ON DUPLICATE KEY UPDATE activo = 1'
    );
    $stmt->execute([$restauranteId, '["efectivo","tarjeta"]', '["dine_in","pickup"]']);

    $db->commit();
    fwrite(STDOUT, "Configuración inicial completada.\n");
    fwrite(STDOUT, "Administrador: {$email}\n");
    fwrite(STDOUT, "Restaurante: Jungle Pizza (jungle-pizza-zihuatanejo)\n");
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "No se pudo completar la configuración inicial: {$e->getMessage()}\n");
    exit(1);
}
