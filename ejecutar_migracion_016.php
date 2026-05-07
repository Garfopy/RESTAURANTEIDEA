<?php
/**
 * Script de migración 016: Eliminar rol "Administrador" (ID 2)
 *
 * INSTRUCCIONES:
 * 1. Ejecutar desde navegador: http://localhost/carnihub/ejecutar_migracion_016.php
 * 2. O desde CLI: php ejecutar_migracion_016.php
 * 3. Después de ejecutar, ELIMINAR este archivo por seguridad
 */

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/database.php';

$db = Database::getInstance();

echo "=== MIGRACIÓN 016: Eliminar rol 'Administrador' (ID 2) ===" . PHP_EOL . PHP_EOL;

// 1. Verificar usuarios con rol_id = 2
echo "1. Verificando usuarios con rol_id = 2..." . PHP_EOL;
$stmt = $db->query('SELECT COUNT(*) AS total_usuarios_admin, GROUP_CONCAT(email SEPARATOR ", ") AS emails FROM usuarios WHERE rol_id = 2');
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "   Total usuarios con rol 'admin': " . $result['total_usuarios_admin'] . PHP_EOL;

if ($result['total_usuarios_admin'] > 0) {
    echo "   ⚠️  ADVERTENCIA: Existen usuarios con rol_id = 2" . PHP_EOL;
    echo "   Emails: " . $result['emails'] . PHP_EOL;
    echo PHP_EOL;
    echo "   ¿Desea actualizar estos usuarios a superadmin (rol_id = 1)? (s/n): ";

    // Si se ejecuta desde CLI, esperar respuesta
    if (php_sapi_name() === 'cli') {
        $respuesta = trim(fgets(STDIN));
        if (strtolower($respuesta) === 's') {
            $db->exec('UPDATE usuarios SET rol_id = 1 WHERE rol_id = 2');
            echo "   ✓ Usuarios actualizados a superadmin" . PHP_EOL;
        } else {
            echo "   ✗ Migración cancelada. Por favor, maneje los usuarios manualmente." . PHP_EOL;
            exit(1);
        }
    } else {
        // Si se ejecuta desde navegador, mostrar formulario
        echo '
        <form method="POST">
            <input type="hidden" name="actualizar_usuarios" value="1">
            <button type="submit">Actualizar usuarios a superadmin</button>
        </form>
        <p><a href="?cancelar=1">Cancelar migración</a></p>
        ';

        if (isset($_POST['actualizar_usuarios'])) {
            $db->exec('UPDATE usuarios SET rol_id = 1 WHERE rol_id = 2');
            echo "<p>✓ Usuarios actualizados a superadmin</p>";
        } elseif (isset($_GET['cancelar'])) {
            echo "<p>✗ Migración cancelada.</p>";
            exit(1);
        } else {
            exit;
        }
    }
}

echo PHP_EOL . "2. Eliminando rol 'Administrador' (ID 2)..." . PHP_EOL;

try {
    $db->beginTransaction();

    // Eliminar el rol
    $stmt = $db->prepare('DELETE FROM roles WHERE id = 2');
    $stmt->execute();

    $db->commit();
    echo "   ✓ Rol eliminado exitosamente" . PHP_EOL;

} catch (Exception $e) {
    $db->rollBack();
    echo "   ✗ Error al eliminar el rol: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "3. Verificando roles restantes..." . PHP_EOL;
$stmt = $db->query('SELECT id, nombre, slug FROM roles ORDER BY id');
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "   Roles en el sistema:" . PHP_EOL;
foreach ($roles as $rol) {
    echo "   - ID {$rol['id']}: {$rol['nombre']} ({$rol['slug']})" . PHP_EOL;
}

echo PHP_EOL . "=== MIGRACIÓN COMPLETADA EXITOSAMENTE ===" . PHP_EOL;
echo PHP_EOL . "⚠️  IMPORTANTE: Elimina este archivo (ejecutar_migracion_016.php) por seguridad" . PHP_EOL;
