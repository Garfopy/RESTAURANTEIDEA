<?php
/**
 * Script de reparación para usuarios bloqueados
 *
 * Problema: La empresa y usuario se crearon pero el registro quedó sin marcar como completado
 * Solución: Marcar manualmente como completado y permitir login
 *
 * INSTRUCCIONES:
 * 1. Ejecutar desde navegador: http://localhost/carnihub/reparar_registro.php?email=EMAIL
 * 2. O desde CLI: php reparar_registro.php EMAIL
 * 3. Después de ejecutar, ELIMINAR este archivo por seguridad
 */

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/database.php';

$db = Database::getInstance();

// Obtener email desde parámetro
$email = $_GET['email'] ?? ($argv[1] ?? null);

if (!$email) {
    die("Error: Proporciona el email como parámetro\n" .
        "Uso: php reparar_registro.php EMAIL\n" .
        "O: http://localhost/carnihub/reparar_registro.php?email=EMAIL\n");
}

echo "=== Reparación de Registro Bloqueado ===" . PHP_EOL . PHP_EOL;
echo "Email: " . htmlspecialchars($email) . PHP_EOL . PHP_EOL;

try {
    $db->beginTransaction();

    // 1. Verificar si el usuario ya existe en la tabla usuarios
    echo "1. Verificando usuario en tabla usuarios..." . PHP_EOL;
    $stmtUsuario = $db->prepare('SELECT id, email, rol_id, empresa_id, email_verificado FROM usuarios WHERE email = ? LIMIT 1');
    $stmtUsuario->execute([$email]);
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        echo "   ✓ Usuario encontrado (ID: {$usuario['id']})" . PHP_EOL;
        echo "   - Empresa ID: {$usuario['empresa_id']}" . PHP_EOL;
        echo "   - Rol ID: {$usuario['rol_id']}" . PHP_EOL;
        echo "   - Email verificado: " . ($usuario['email_verificado'] ? 'SÍ' : 'NO') . PHP_EOL;

        // Si el email no está verificado, marcarlo como verificado
        if (!$usuario['email_verificado']) {
            echo "   → Marcando email como verificado..." . PHP_EOL;
            $stmtUpdate = $db->prepare('UPDATE usuarios SET email_verificado = 1 WHERE id = ?');
            $stmtUpdate->execute([$usuario['id']]);
            echo "   ✓ Email verificado" . PHP_EOL;
        }
    } else {
        echo "   ✗ Usuario NO encontrado en tabla usuarios" . PHP_EOL;
        echo "   El problema es más grave, la creación del usuario falló completamente" . PHP_EOL;
        $db->rollBack();
        exit(1);
    }

    // 2. Buscar registros pendientes problemáticos
    echo PHP_EOL . "2. Buscando registros pendientes..." . PHP_EOL;
    $stmtRegistros = $db->prepare('SELECT id, estado, created_at, completed_at FROM registros_pendientes WHERE email = ? ORDER BY created_at DESC');
    $stmtRegistros->execute([$email]);
    $registros = $stmtRegistros->fetchAll(PDO::FETCH_ASSOC);

    if (count($registros) === 0) {
        echo "   ✗ No hay registros pendientes para este email" . PHP_EOL;
    } else {
        echo "   ✓ Encontrados " . count($registros) . " registros:" . PHP_EOL;
        foreach ($registros as $reg) {
            echo "   - ID {$reg['id']}: {$reg['estado']} (creado: {$reg['created_at']})" . PHP_EOL;
        }

        // 3. Buscar el registro que está en estado pendiente_verificacion
        $registroPendiente = null;
        foreach ($registros as $reg) {
            if ($reg['estado'] === 'pendiente_verificacion') {
                $registroPendiente = $reg;
                break;
            }
        }

        if ($registroPendiente) {
            echo PHP_EOL . "3. Marcando registro ID {$registroPendiente['id']} como completado..." . PHP_EOL;

            // Marcar TODOS los registros completados antiguos como "expirado" para liberar el índice
            $stmtExpira = $db->prepare('UPDATE registros_pendientes SET estado = "expirado" WHERE email = ? AND estado = "completado" AND id != ?');
            $stmtExpira->execute([$email, $registroPendiente['id']]);
            echo "   → Registros antiguos marcados como expirados" . PHP_EOL;

            // Ahora marcar el actual como completado
            $stmtCompletado = $db->prepare('UPDATE registros_pendientes SET estado = "completado", completed_at = NOW() WHERE id = ?');
            $stmtCompletado->execute([$registroPendiente['id']]);
            echo "   ✓ Registro marcado como completado" . PHP_EOL;
        } else {
            echo PHP_EOL . "3. No hay registros en estado pendiente_verificacion" . PHP_EOL;
            echo "   Posiblemente ya fue resuelto manualmente" . PHP_EOL;
        }
    }

    $db->commit();

    echo PHP_EOL . "=== REPARACIÓN COMPLETADA ===" . PHP_EOL;
    echo "El usuario puede iniciar sesión ahora con:" . PHP_EOL;
    echo "Email: " . htmlspecialchars($email) . PHP_EOL;
    echo "Contraseña: (la que se envió por correo)" . PHP_EOL;
    echo PHP_EOL;
    echo "⚠️  IMPORTANTE: Elimina este archivo (reparar_registro.php) por seguridad" . PHP_EOL;

} catch (Exception $e) {
    $db->rollBack();
    echo PHP_EOL . "✗ ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
