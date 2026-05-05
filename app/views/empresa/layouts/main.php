<?php
/**
 * Layout del Portal Empresa (admin_empresa + supervisor + comprador).
 * Sidebar muestra ítems según rol.
 */
$configModel  = new ConfigModel();
$colorPrimary = $configModel->get('color_primary', '#C8102E');
$colorSecond  = $configModel->get('color_secondary', '#1f2937');
$appName      = $configModel->get('app_name', APP_NAME);
$appLogo      = $configModel->get('app_logo', '');
$usuario      = $_SESSION['usuario'] ?? [];
$empresa      = $_SESSION['empresa'] ?? [];
$rol          = $usuario['rol_slug'] ?? '';
$esAdmin      = $rol === 'admin_empresa';
$esComprador  = $rol === 'comprador';
$esSupervisor = in_array($rol, ['admin_empresa', 'supervisor'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Mi Empresa') ?> — <?= htmlspecialchars($appName) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/carnihub.css">
  <style>
    :root {
      --color-primary: <?= htmlspecialchars($colorPrimary) ?>;
      --color-secondary: <?= htmlspecialchars($colorSecond) ?>;
    }
    .sidebar { width:240px;min-height:100vh;background:#fff;border-right:1px solid #E5E7EB;flex-shrink:0; }
    .sidebar a { display:flex;align-items:center;gap:10px;padding:9px 16px;font-size:.875rem;color:#4B5563;text-decoration:none;border-radius:6px;margin:2px 8px;transition:background .15s; }
    .sidebar a:hover, .sidebar a.active { background:#FEF2F2;color:var(--color-primary);font-weight:600; }
    .sidebar-section { font-size:.7rem;font-weight:700;letter-spacing:.08em;color:#9CA3AF;padding:14px 16px 4px;text-transform:uppercase; }
    .main-content { flex:1;overflow-y:auto;background:#F9FAFB;min-height:100vh; }
    .topbar { background:#fff;border-bottom:1px solid #E5E7EB;padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between; }
    .badge-rol { font-size:.7rem;padding:2px 8px;border-radius:999px;background:var(--color-primary);color:#fff;font-weight:600; }
  </style>
</head>
<body style="display:flex;font-family:'Inter',sans-serif">

<!-- Sidebar -->
<aside class="sidebar">
  <div style="padding:16px;border-bottom:1px solid #E5E7EB">
    <?php if ($appLogo): ?>
      <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo" style="height:44px;max-width:180px;object-fit:contain">
    <?php else: ?>
      <img src="<?= BASE_URL ?>public/img/logo.svg" alt="<?= htmlspecialchars($appName) ?>" style="height:44px;max-width:180px;object-fit:contain">
    <?php endif; ?>
    <?php if (!empty($empresa)): ?>
    <p style="font-size:.75rem;color:#6B7280;margin-top:6px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($empresa['razon_social']) ?>">
      <?= htmlspecialchars($empresa['razon_social']) ?>
    </p>
    <?php endif; ?>
  </div>

  <nav style="padding:8px 0">
    <?php if ($esAdmin): ?>
    <div class="sidebar-section">General</div>
    <a href="<?= BASE_URL ?>empresa/dashboard" class="<?= ($activeMenu??'')==='dashboard'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke-width="2"/></svg>
      Dashboard
    </a>
    <?php endif; ?>

    <?php if ($rol === 'supervisor'): ?>
    <div class="sidebar-section">Mi panel</div>
    <a href="<?= BASE_URL ?>supervisor/dashboard" class="<?= ($activeMenu??'')==='supervisor_dashboard'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke-width="2"/></svg>
      Mi panel
    </a>
    <?php endif; ?>

    <?php if ($rol === 'comprador'): ?>
    <div class="sidebar-section">Inicio</div>
    <a href="<?= BASE_URL ?>comprador/inicio" class="<?= ($activeMenu??'')==='comprador_inicio'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3M9 21h6"/></svg>
      Inicio
    </a>
    <?php endif; ?>

    <?php if ($esSupervisor): ?>
    <div class="sidebar-section">Supervisión</div>
    <a href="<?= BASE_URL ?>empresa-inventario" class="<?= ($activeMenu??'')==='inventario'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      Control de Stock
    </a>
    <a href="<?= BASE_URL ?>empresa-pedido" class="<?= ($activeMenu??'')==='pedidos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Pedidos
    </a>
    <a href="<?= BASE_URL ?>pedido/aprobacion" class="<?= ($activeMenu??'')==='aprobacion'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Aprobaciones
    </a>
    <a href="<?= BASE_URL ?>limite/index" class="<?= ($activeMenu??'')==='limites'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      Límites de compra
    </a>
    <?php endif; ?>

    <?php if ($esComprador): ?>
    <div class="sidebar-section">Pedidos</div>
    <a href="<?= BASE_URL ?>catalogo/index" class="<?= ($activeMenu??'')==='catalogo'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
      Catálogo
    </a>
    <a href="<?= BASE_URL ?>carrito/index" class="<?= ($activeMenu??'')==='carrito'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m5-9v9m4-9v9m5-9l2 9"/></svg>
      Hacer pedido
    </a>
    <a href="<?= BASE_URL ?>pedido/index" class="<?= ($activeMenu??'')==='pedidos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Mis pedidos
    </a>
    <a href="<?= BASE_URL ?>recurrente/index" class="<?= ($activeMenu??'')==='recurrentes'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      Pedidos recurrentes
    </a>
    <?php endif; ?>

    <?php if ($esAdmin): ?>
    <div class="sidebar-section">Catálogo y Stock</div>
    <a href="<?= BASE_URL ?>empresa-producto/index" class="<?= ($activeMenu??'')==='productos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
      Productos
    </a>
    <a href="<?= BASE_URL ?>empresa-inventario" class="<?= ($activeMenu??'')==='inventario'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      Control de Stock
    </a>

    <div class="sidebar-section">Operación</div>
    <a href="<?= BASE_URL ?>empresa-pedido" class="<?= ($activeMenu??'')==='pedidos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Pedidos
    </a>
    <a href="<?= BASE_URL ?>empresa-pedido/personalizado" class="<?= ($activeMenu??'')==='pedido_personalizado'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
      Pedido personalizado
    </a>
    <a href="<?= BASE_URL ?>empresa-logistica/index" class="<?= ($activeMenu??'')==='logistica'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
      Logística y rutas
    </a>

    <div class="sidebar-section">Mi empresa</div>
    <a href="<?= BASE_URL ?>empresa-usuario/index" class="<?= ($activeMenu??'')==='usuarios'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 110-8 4 4 0 010 8z"/></svg>
      Mi equipo
    </a>
    <a href="<?= BASE_URL ?>empresa-sucursal/index" class="<?= ($activeMenu??'')==='sucursales'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3"/></svg>
      Sucursales
    </a>
    <a href="<?= BASE_URL ?>empresa-vehiculo/index" class="<?= ($activeMenu??'')==='vehiculos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
      Vehículos
    </a>
    <a href="<?= BASE_URL ?>empresa-reporte/index" class="<?= ($activeMenu??'')==='reportes'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Reportes
    </a>
    <?php endif; ?>

    <div class="sidebar-section">Cuenta</div>
    <a href="<?= BASE_URL ?>cuenta/perfil" class="<?= ($activeMenu??'')==='cuenta'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A8.966 8.966 0 0112 15c2.485 0 4.745.99 6.379 2.596M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Mi perfil
    </a>
  </nav>

  <div style="position:sticky;bottom:0;padding:12px 16px;border-top:1px solid #E5E7EB;background:#fff">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
      <?php if (!empty($usuario['avatar'])): ?>
        <img src="<?= htmlspecialchars($usuario['avatar']) ?>" alt="Avatar"
             style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1px solid #E5E7EB">
      <?php else: ?>
        <div style="width:32px;height:32px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:var(--color-primary);flex-shrink:0">
          <?= strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1)) ?>
        </div>
      <?php endif; ?>
      <div style="overflow:hidden">
        <div style="font-size:.8rem;font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($usuario['nombre'] ?? '') ?></div>
        <span class="badge-rol"><?= htmlspecialchars($usuario['rol_nombre'] ?? '') ?></span>
      </div>
    </div>
    <a href="<?= BASE_URL ?>auth/logout" style="display:block;text-align:center;padding:6px;border-radius:6px;font-size:.8rem;color:#6B7280;text-decoration:none;border:1px solid #E5E7EB" onmouseover="this.style.color='#C8102E'" onmouseout="this.style.color='#6B7280'">
      Cerrar sesión
    </a>
  </div>
</aside>

<!-- Contenido principal -->
<div class="main-content">
  <div class="topbar">
    <h1 style="font-size:1rem;font-weight:700;color:#111827"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
    <?php if (!empty($flash)): ?>
    <div style="padding:8px 14px;border-radius:8px;font-size:.8rem;<?= $flash['type']==='error' ? 'background:#FEE2E2;color:#991B1B' : 'background:#D1FAE5;color:#065F46' ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>
  </div>

  <div style="padding:24px">
    <?= $content ?? '' ?>
  </div>
</div>

</body>
</html>
