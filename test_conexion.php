<?php
// test_conexion.php — CarniHub connection and environment tester
error_reporting(E_ALL);
ini_set('display_errors', 1);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$basePath  = rtrim($scriptDir, '/') . '/';
$baseUrl   = $protocol . $host . $basePath;

function ok(string $msg): string { return "<span style='color:#10B981'>✅ $msg</span>"; }
function fail(string $msg): string { return "<span style='color:#EF4444'>❌ $msg</span>"; }
function warn(string $msg): string { return "<span style='color:#F59E0B'>⚠️ $msg</span>"; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CarniHub — Test de Conexión</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #111; color: #F1F5F9; margin: 0; padding: 24px; }
    h1 { color: #C8102E; margin-bottom: 8px; }
    .card { background: #1E2130; border-radius: 12px; padding: 20px; margin-bottom: 16px; max-width: 640px; }
    .card h2 { font-size: 1rem; color: #94A3B8; margin-top: 0; }
    table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    td { padding: 6px 0; border-bottom: 1px solid #2D3348; vertical-align: top; }
    td:first-child { color: #94A3B8; width: 200px; }
    pre { background: #0F1117; padding: 10px; border-radius: 6px; font-size: .75rem; overflow-x: auto; color: #64748B; }
    a { color: #C8102E; }
  </style>
</head>
<body>
<h1>🥩 CarniHub — Diagnóstico del sistema</h1>
<p style="color:#64748B;margin-top:0">Versión 1.0.0 · <?= date('Y-m-d H:i:s') ?></p>

<!-- URL Base -->
<div class="card">
  <h2>🌐 URL Base</h2>
  <table>
    <tr><td>URL detectada</td><td><?= htmlspecialchars($baseUrl) ?></td></tr>
    <tr><td>HTTP_HOST</td><td><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '—') ?></td></tr>
    <tr><td>SCRIPT_NAME</td><td><?= htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? '—') ?></td></tr>
    <tr><td>HTTPS</td><td><?= !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? ok('Activo') : warn('No activo') ?></td></tr>
  </table>
</div>

<!-- PHP -->
<div class="card">
  <h2>🐘 PHP</h2>
  <table>
    <tr><td>Versión PHP</td><td><?= phpversion() >= '7.4' ? ok(phpversion()) : fail(phpversion() . ' (mínimo 7.4)') ?></td></tr>
    <tr><td>PDO</td><td><?= extension_loaded('pdo') ? ok('Disponible') : fail('No disponible') ?></td></tr>
    <tr><td>PDO MySQL</td><td><?= extension_loaded('pdo_mysql') ? ok('Disponible') : fail('No disponible') ?></td></tr>
    <tr><td>Session</td><td><?= session_start() ? ok('OK') : fail('Error') ?></td></tr>
    <tr><td>GD (imágenes)</td><td><?= extension_loaded('gd') ? ok('Disponible') : warn('No disponible') ?></td></tr>
    <tr><td>file_get_contents URL</td><td><?= ini_get('allow_url_fopen') ? ok('Habilitado') : warn('Deshabilitado (servicios API no funcionarán)') ?></td></tr>
    <tr><td>memory_limit</td><td><?= ini_get('memory_limit') ?></td></tr>
    <tr><td>upload_max_filesize</td><td><?= ini_get('upload_max_filesize') ?></td></tr>
  </table>
</div>

<!-- Base de datos -->
<div class="card">
  <h2>🗄️ Base de datos MySQL</h2>
  <?php
  $dbConfig = ['host' => 'localhost', 'db' => 'carnihub', 'user' => 'root', 'pass' => ''];
  try {
    $pdo = new PDO(
      "mysql:host={$dbConfig['host']};dbname={$dbConfig['db']};charset=utf8mb4",
      $dbConfig['user'], $dbConfig['pass'],
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo '<table>';
    echo '<tr><td>Conexión</td><td>' . ok('Exitosa') . '</td></tr>';
    echo '<tr><td>Versión MySQL</td><td>' . htmlspecialchars($version) . '</td></tr>';

    // Check tables
    $tables = ['roles','usuarios','empresas','sucursales','categorias','productos','precios_escalonados',
               'inventario','pedidos','pedido_detalle','pedido_sucursal','pedidos_recurrentes',
               'rutas','ruta_detalle','evidencias_entrega','pagos','facturas','global_settings',
               'action_logs','error_logs','dispositivos_hikvision','dispositivos_shelly'];

    $stmt = $pdo->query("SHOW TABLES FROM `{$dbConfig['db']}`");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $missing = array_diff($tables, $existingTables);
    echo '<tr><td>Tablas</td><td>';
    echo count($missing) === 0
      ? ok(count($existingTables) . ' tablas encontradas')
      : fail(count($missing) . ' tablas faltantes: ' . implode(', ', $missing));
    echo '</td></tr>';

    // Check dummy data
    $userCount = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    $prodCount = $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
    echo "<tr><td>Usuarios</td><td>$userCount registros</td></tr>";
    echo "<tr><td>Productos</td><td>$prodCount registros</td></tr>";
    echo '</table>';

  } catch (PDOException $e) {
    echo fail('Error de conexión: ' . htmlspecialchars($e->getMessage()));
    echo '<br><br>' . warn('Edita config/database.php con las credenciales correctas.');
  }
  ?>
</div>

<!-- Archivos clave -->
<div class="card">
  <h2>📁 Archivos del sistema</h2>
  <table>
    <?php
    $checkFiles = [
      '.htaccess'                 => 'Rewrite rules',
      'index.php'                 => 'Front Controller',
      'config/config.php'         => 'Configuración URL',
      'config/database.php'       => 'Conexión BD',
      'app/models/BaseModel.php'  => 'Modelo base',
      'app/controllers/AuthController.php' => 'Auth',
      'app/views/auth/login.php'  => 'Vista login',
      'app/views/components/header.php' => 'Header component',
      'public/css/carnihub.css'   => 'Estilos CSS',
      'public/js/app.js'          => 'JS global',
    ];
    foreach ($checkFiles as $path => $label):
      $exists = file_exists(dirname(__FILE__) . '/' . $path);
    ?>
    <tr><td><?= $label ?></td><td><?= $exists ? ok($path) : fail($path . ' — no encontrado') ?></td></tr>
    <?php endforeach; ?>
  </table>
</div>

<!-- Upload dirs -->
<div class="card">
  <h2>📂 Directorios de uploads</h2>
  <table>
    <?php
    $uploadDirs = ['public/uploads','public/uploads/productos','public/uploads/evidencias','public/uploads/avatars'];
    foreach ($uploadDirs as $dir):
      $fullPath = dirname(__FILE__) . '/' . $dir;
      $exists   = is_dir($fullPath);
      $writable = $exists && is_writable($fullPath);
    ?>
    <tr>
      <td><?= $dir ?></td>
      <td>
        <?= $exists   ? ok('Existe') : warn('No existe — crear con 755') ?>
        <?php if ($exists): ?>
          · <?= $writable ? ok('Escritura OK') : fail('Sin permiso de escritura') ?>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<!-- Login de prueba -->
<div class="card">
  <h2>🔐 Credenciales de prueba</h2>
  <table>
    <tr><td>SuperAdmin</td><td><code>admin@carnihub.mx</code> / <code>admin123</code></td></tr>
    <tr><td>Comprador</td><td><code>juan.perez@carnihub.mx</code> / <code>admin123</code></td></tr>
    <tr><td>Repartidor</td><td><code>luis.martinez@carnihub.mx</code> / <code>admin123</code></td></tr>
  </table>
  <br>
  <a href="<?= htmlspecialchars($baseUrl) ?>" style="display:inline-block;background:#C8102E;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:700">
    → Ir a CarniHub
  </a>
</div>

</body>
</html>
