<?php
/**
 * Layout principal del Panel de Plataforma (superadmin + admin).
 * Se usa con: $pageTitle, $activeMenu (opcional), $esSuperAdmin
 */
$configModel   = new ConfigModel();
$colorPrimary  = $configModel->get('color_primary', '#C8102E');
$colorSecond   = $configModel->get('color_secondary', '#1f2937');
$appName       = $configModel->get('app_name', APP_NAME);
$appLogo       = $configModel->get('app_logo', '');
$usuario       = $_SESSION['usuario'] ?? [];
$esSuperAdmin  = ($usuario['rol_slug'] ?? '') === 'superadmin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Panel') ?> — <?= htmlspecialchars($appName) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/carnihub.css">
  <style>
    :root {
      --color-primary: <?= htmlspecialchars($colorPrimary) ?>;
      --color-secondary: <?= htmlspecialchars($colorSecond) ?>;
    }
    .sidebar { width:260px;min-height:100vh;background:var(--color-secondary);color:#fff;flex-shrink:0; }
    .sidebar a { display:flex;align-items:center;gap:10px;padding:10px 20px;font-size:.875rem;color:#D1D5DB;text-decoration:none;border-radius:6px;margin:2px 10px;transition:background .15s; }
    .sidebar a:hover, .sidebar a.active { background:rgba(255,255,255,.1);color:#fff; }
    .sidebar-section { font-size:.7rem;font-weight:700;letter-spacing:.08em;color:#6B7280;padding:16px 20px 4px;text-transform:uppercase; }
    .main-content { flex:1;overflow-y:auto;background:#F9FAFB;min-height:100vh; }
    .topbar { background:#fff;border-bottom:1px solid #E5E7EB;padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between; }
    .badge-rol { font-size:.7rem;padding:2px 8px;border-radius:999px;background:var(--color-primary);color:#fff;font-weight:600; }
  </style>
</head>
<body style="display:flex;font-family:'Inter',sans-serif">

<!-- Sidebar -->
<aside class="sidebar">
  <div style="padding:20px;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:10px">
    <?php if ($appLogo): ?>
      <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo" style="height:32px;filter:brightness(0) invert(1)">
    <?php else: ?>
      <img src="<?= BASE_URL ?>public/img/logo.svg" alt="<?= htmlspecialchars($appName) ?>" style="height:32px;filter:brightness(0) invert(1)">
    <?php endif; ?>
    <span style="font-weight:800;font-size:.95rem"><?= htmlspecialchars($appName) ?></span>
  </div>

  <nav style="padding:10px 0">
    <div class="sidebar-section">Principal</div>
    <a href="<?= BASE_URL ?>panel/dashboard" class="<?= ($activeMenu??'')==='dashboard'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
      Dashboard
    </a>

    <div class="sidebar-section">Clientes</div>
    <a href="<?= BASE_URL ?>panel-empresa/index" class="<?= ($activeMenu??'')==='empresas'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3"/></svg>
      Empresas
    </a>
    <a href="<?= BASE_URL ?>panel-usuario/index" class="<?= ($activeMenu??'')==='usuarios'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 110-8 4 4 0 010 8z"/></svg>
      Usuarios
    </a>

    <div class="sidebar-section">Catálogo</div>
    <a href="<?= BASE_URL ?>panel-producto/index" class="<?= ($activeMenu??'')==='productos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
      Productos
    </a>
    <a href="<?= BASE_URL ?>panel-inventario/index" class="<?= ($activeMenu??'')==='inventario'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      Inventario
    </a>

    <div class="sidebar-section">Operación</div>
    <a href="<?= BASE_URL ?>panel-pedido/index" class="<?= ($activeMenu??'')==='pedidos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Pedidos
    </a>
    <a href="<?= BASE_URL ?>panel-logistica/index" class="<?= ($activeMenu??'')==='logistica'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
      Logística
    </a>
    <a href="<?= BASE_URL ?>panel-reporte/index" class="<?= ($activeMenu??'')==='reportes'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Reportes
    </a>

    <?php if ($esSuperAdmin): ?>
    <div class="sidebar-section">Sistema</div>
    <a href="<?= BASE_URL ?>config/general" class="<?= ($activeMenu??'')==='config'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Configuración
    </a>
    <?php endif; ?>
  </nav>

  <!-- Pie del sidebar -->
  <div style="position:sticky;bottom:0;padding:12px 20px;border-top:1px solid rgba(255,255,255,.1);background:var(--color-secondary)">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
      <?php if (!empty($usuario['avatar'])): ?>
        <img src="<?= htmlspecialchars($usuario['avatar']) ?>" alt="Avatar"
             style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1px solid rgba(255,255,255,.2)">
      <?php else: ?>
        <div style="width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0">
          <?= strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1)) ?>
        </div>
      <?php endif; ?>
      <div>
        <div style="font-size:.8rem;font-weight:600"><?= htmlspecialchars($usuario['nombre'] ?? '') ?></div>
        <span class="badge-rol"><?= htmlspecialchars($usuario['rol_nombre'] ?? '') ?></span>
      </div>
    </div>
    <a href="<?= BASE_URL ?>auth/logout" style="display:block;text-align:center;padding:6px;border-radius:6px;font-size:.8rem;color:#9CA3AF;text-decoration:none;border:1px solid rgba(255,255,255,.1)" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#9CA3AF'">
      Cerrar sesión
    </a>
  </div>
</aside>

<!-- Contenido principal -->
<div class="main-content">
  <!-- Topbar -->
  <div class="topbar">
    <h1 style="font-size:1rem;font-weight:700;color:#111827"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
    <?php if (!empty($flash)): ?>
    <div style="padding:8px 14px;border-radius:8px;font-size:.8rem;<?= $flash['type']==='error' ? 'background:#FEE2E2;color:#991B1B' : 'background:#D1FAE5;color:#065F46' ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Contenido de la vista inyectado aquí -->
  <div style="padding:24px">
    <?= $content ?? '' ?>
  </div>
</div>

</body>
</html>
