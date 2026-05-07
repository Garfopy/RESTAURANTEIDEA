<?php
/**
 * Script de debug para verificar tokens de verificación
 * Ejecutar desde el navegador: /carnihub/debug_token.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();

// Obtener usuarios sin verificar con sus tokens
$stmt = $db->query("
    SELECT id, email, nombre, apellido_paterno,
           email_verificado,
           token_verificacion,
           token_expira,
           created_at
    FROM usuarios
    WHERE email_verificado = 0
    ORDER BY created_at DESC
    LIMIT 10
");

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug - Tokens de Verificación</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .null { color: red; font-weight: bold; }
        .ok { color: green; }
    </style>
</head>
<body>
    <h1>Usuarios sin verificar - Tokens de Verificación</h1>

    <?php if (empty($usuarios)): ?>
        <p>No hay usuarios sin verificar.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Nombre</th>
                    <th>Token</th>
                    <th>Expira</th>
                    <th>Link Verificación</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['id']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido_paterno']) ?></td>
                        <td>
                            <?php if ($u['token_verificacion']): ?>
                                <span class="ok">✓ Presente (<?= strlen($u['token_verificacion']) ?> chars)</span>
                            <?php else: ?>
                                <span class="null">✗ NULL</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['token_expira'] ?? '<span class="null">NULL</span>' ?></td>
                        <td>
                            <?php if ($u['token_verificacion']): ?>
                                <a href="<?= BASE_URL ?>auth/verificar?token=<?= urlencode($u['token_verificacion']) ?>" target="_blank">
                                    Verificar
                                </a>
                            <?php else: ?>
                                <span class="null">No disponible</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Instrucciones:</h2>
        <ul>
            <li>Si ves "✗ NULL" en la columna Token, significa que el token NO se guardó en la base de datos</li>
            <li>Si ves "✓ Presente", haz clic en el link "Verificar" para probar el proceso de verificación</li>
            <li>Los tokens expiran en 24 horas desde su creación</li>
        </ul>
    <?php endif; ?>

    <hr>
    <p><strong>IMPORTANTE:</strong> Elimina este archivo después de usarlo por seguridad.</p>
</body>
</html>
