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
$esAdmin       = $rol === 'admin_empresa';
$esSupervisor  = $rol === 'supervisor';
$esComprador   = $rol === 'comprador';
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
  <script src="<?= BASE_URL ?>public/js/chart.umd.min.js"></script>
  <style>
    :root {
      --color-primary:   <?= htmlspecialchars($colorPrimary) ?>;
      --color-secondary: <?= htmlspecialchars($colorSecond) ?>;
    }

    /* ── Sidebar dark premium ── */
    .sidebar {
      width: 256px;
      height: 100vh;
      background: #fff;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; left: 0;
      z-index: 100;
      overflow: hidden;
      transition: transform .3s ease;
      border-right: 1px solid #E5E7EB;
    }
    .sidebar-logo-area {
      padding: 18px 20px 16px;
      border-bottom: 1px solid #F3F4F6;
    }
    .sidebar-company {
      font-size: .72rem;
      color: #9CA3AF;
      font-weight: 500;
      margin-top: 6px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .sidebar nav {
      flex: 1;
      min-height: 0;
      padding: 8px 0;
      overflow-y: auto;
    }
    .sidebar a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 14px;
      font-size: .845rem;
      color: #6B7280;
      text-decoration: none;
      border-radius: 8px;
      margin: 1px 10px;
      transition: background .15s, color .15s;
      border-left: 3px solid transparent;
      font-weight: 500;
    }
    .sidebar a:hover {
      background: #F9FAFB;
      color: #111827;
    }
    .sidebar a.active {
      background: #FEF2F2;
      color: var(--color-primary);
      font-weight: 700;
      border-left-color: var(--color-primary);
    }
    .sidebar a svg { flex-shrink: 0; opacity: .75; }
    .sidebar a:hover svg, .sidebar a.active svg { opacity: 1; }
    .sidebar-section {
      font-size: .63rem;
      font-weight: 700;
      letter-spacing: .1em;
      color: #D1D5DB;
      padding: 14px 18px 4px;
      text-transform: uppercase;
    }
    .sidebar-user {
      padding: 14px 16px;
      border-top: 1px solid #F3F4F6;
      background: #FAFAFA;
      flex-shrink: 0;
    }
    .sidebar-user-info {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
    }
    .sidebar-avatar {
      width: 34px; height: 34px;
      border-radius: 50%;
      background: #FEF2F2;
      border: 1.5px solid #FECACA;
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: .85rem;
      color: var(--color-primary); flex-shrink: 0;
    }
    .sidebar-user-name {
      font-size: .8rem; font-weight: 600;
      color: #111827;
      overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .badge-rol {
      font-size: .62rem; padding: 2px 8px;
      border-radius: 999px;
      background: #FEF2F2;
      color: var(--color-primary); font-weight: 700;
      letter-spacing: .03em;
    }
    .sidebar-logout {
      display: flex; align-items: center; justify-content: center; gap: 6px;
      width: 100%; padding: 7px 10px;
      border-radius: 7px;
      font-size: .78rem; color: #9CA3AF;
      text-decoration: none;
      border: 1px solid #E5E7EB;
      transition: color .15s, border-color .15s, background .15s;
    }
    .sidebar-logout:hover {
      color: var(--color-primary);
      border-color: #FECACA;
      background: #FEF2F2;
    }

    /* ── Main content ── */
    .main-content {
      margin-left: 256px;
      min-height: 100vh;
      width: calc(100% - 256px);
      background: #F4F6FA;
      display: flex; flex-direction: column;
    }

    /* ── Topbar ── */
    .topbar {
      background: #fff;
      border-bottom: 1px solid #E5E7EB;
      padding: 0 28px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
      box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .topbar-left {
      display: flex; align-items: center; gap: 12px;
    }
    .topbar-title {
      font-size: 1rem; font-weight: 700; color: #111827;
    }
    .topbar-sep {
      width: 1px; height: 20px; background: #E5E7EB;
    }
    .topbar-right {
      display: flex; align-items: center; gap: 12px;
    }
    .topbar-company-chip {
      display: flex; align-items: center; gap: 7px;
      padding: 5px 12px 5px 8px;
      border-radius: 999px;
      background: #F3F4F6;
      border: 1px solid #E5E7EB;
      font-size: .78rem; font-weight: 600; color: #374151;
      text-decoration: none;
      transition: background .15s;
    }
    .topbar-company-chip:hover { background: #E9EBF0; }
    .topbar-avatar {
      width: 32px; height: 32px; border-radius: 50%;
      background: rgba(200,16,46,.1);
      border: 2px solid rgba(200,16,46,.25);
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: .8rem; color: var(--color-primary);
      flex-shrink: 0;
    }

    /* ── Page content wrapper ── */
    .page-body { padding: 24px 28px; flex: 1; }

    /* ── Flash in topbar ── */
    .topbar-flash {
      padding: 6px 14px;
      border-radius: 8px;
      font-size: .8rem;
      font-weight: 500;
      max-width: 400px;
    }
    .topbar-flash.is-error   { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
    .topbar-flash.is-success { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .sidebar       { transform: translateX(-100%); }
      .sidebar.open  { transform: translateX(0); }
      .main-content  { margin-left: 0; }
      .hide-mobile   { display: none !important; }
    }
    @media (min-width: 769px) {
      .bottom-nav   { display: none; }
      .hide-desktop { display: none !important; }
    }
  </style>
</head>
<body style="display:flex;font-family:'Inter',sans-serif;margin:0">

<!-- ── Sidebar ────────────────────────────────────────────────────────────── -->
<aside class="sidebar">

  <div class="sidebar-logo-area">
    <a href="<?= BASE_URL ?>" style="display:inline-flex;align-items:center;text-decoration:none">
    <?php if ($appLogo): ?>
      <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo"
           style="height:38px;max-width:180px;object-fit:contain">
    <?php else: ?>
      <img src="<?= BASE_URL ?>public/img/logo.svg" alt="<?= htmlspecialchars($appName) ?>"
           style="height:38px;max-width:180px;object-fit:contain">
    <?php endif; ?>
    </a>
    <?php if (!empty($empresa)): ?>
      <div class="sidebar-company" title="<?= htmlspecialchars($empresa['razon_social']) ?>">
        <?= htmlspecialchars($empresa['razon_social']) ?>
      </div>
    <?php endif; ?>
  </div>

  <nav>

    <?php /* ── ADMIN EMPRESA ─────────────────────────────────────── */ ?>
    <?php if ($esAdmin): ?>
    <div class="sidebar-section">General</div>
    <a href="<?= BASE_URL ?>empresa/dashboard" class="<?= ($activeMenu??'')==='dashboard'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>

    <div class="sidebar-section">Catálogo y Stock</div>
    <a href="<?= BASE_URL ?>empresa-producto/index" class="<?= ($activeMenu??'')==='productos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
      Productos
    </a>
    <a href="<?= BASE_URL ?>empresa-inventario" class="<?= ($activeMenu??'')==='inventario'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      Control de Stock
    </a>

    <div class="sidebar-section">Operación</div>
    <a href="<?= BASE_URL ?>empresa-pedido" class="<?= ($activeMenu??'')==='pedidos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Pedidos
    </a>
    <a href="<?= BASE_URL ?>empresa-combo" class="<?= ($activeMenu??'')==='combos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      Combos por comprador
    </a>
    <a href="<?= BASE_URL ?>empresa-logistica/index" class="<?= ($activeMenu??'')==='logistica'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
      Logística y rutas
    </a>
    <a href="<?= BASE_URL ?>empresa-evidencia/index" class="<?= ($activeMenu??'')==='evidencias'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      Evidencias de entrega
    </a>
    <a href="<?= BASE_URL ?>limite/index" class="<?= ($activeMenu??'')==='limites'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      Límites de compra
    </a>

    <div class="sidebar-section">Mi empresa</div>
    <a href="<?= BASE_URL ?>empresa-usuario/index" class="<?= ($activeMenu??'')==='usuarios'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 110-8 4 4 0 010 8z"/></svg>
      Mi equipo
    </a>
    <a href="<?= BASE_URL ?>empresa-sucursal/index" class="<?= ($activeMenu??'')==='sucursales'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3"/></svg>
      Sucursales
    </a>
    <a href="<?= BASE_URL ?>empresa-vehiculo/index" class="<?= ($activeMenu??'')==='vehiculos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
      Vehículos
    </a>

    <div class="sidebar-section">Análisis</div>
    <a href="<?= BASE_URL ?>empresa-reporte/index" class="<?= ($activeMenu??'')==='reportes'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Reportes y analítica
    </a>
    <?php endif; ?>

    <?php /* ── SUPERVISOR ──────────────────────────────────────────── */ ?>
    <?php if ($esSupervisor): ?>
    <div class="sidebar-section">Mi panel</div>
    <a href="<?= BASE_URL ?>supervisor/dashboard" class="<?= ($activeMenu??'')==='supervisor_dashboard'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Mi panel
    </a>

    <div class="sidebar-section">Pedidos</div>
    <a href="<?= BASE_URL ?>empresa-pedido" class="<?= ($activeMenu??'')==='pedidos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Pedidos
      <?php if (!empty($countPendientesSidebar) && $countPendientesSidebar > 0): ?>
        <span style="margin-left:auto;background:#DC2626;color:#fff;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:999px;line-height:1.4"><?= (int)$countPendientesSidebar ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>empresa-evidencia/index" class="<?= ($activeMenu??'')==='evidencias'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      Evidencias de entrega
    </a>

    <div class="sidebar-section">Stock</div>
    <a href="<?= BASE_URL ?>empresa-inventario" class="<?= ($activeMenu??'')==='inventario'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      Control de Stock
    </a>
    <a href="<?= BASE_URL ?>empresa-inventario/movimiento/entrada" class="<?= ($activeMenu??'')==='inventario_entrada'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      Registrar movimiento
    </a>
    <?php endif; ?>

    <?php /* ── COMPRADOR ───────────────────────────────────────────── */ ?>
    <?php if ($esComprador): ?>
    <div class="sidebar-section">Inicio</div>
    <a href="<?= BASE_URL ?>comprador/inicio" class="<?= ($activeMenu??'')==='comprador_inicio'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3m10-11v10a1 1 0 01-1 1h-3M9 21h6"/></svg>
      Inicio
    </a>

    <div class="sidebar-section">Comprar</div>
    <a href="<?= BASE_URL ?>catalogo/index" class="<?= ($activeMenu??'')==='catalogo'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
      Catálogo
    </a>
    <a href="<?= BASE_URL ?>carrito/index" class="<?= ($activeMenu??'')==='carrito'?'active':'' ?>" style="position:relative">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m5-9v9m4-9v9m5-9l2 9"/></svg>
      Carrito
      <?php
      $carritoCount = count($_SESSION['carrito']['items'] ?? []);
      if ($carritoCount > 0):
      ?>
      <span style="margin-left:auto;background:var(--color-primary);color:#fff;font-size:.6rem;font-weight:700;padding:1px 6px;border-radius:999px;line-height:1.5"><?= $carritoCount ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>pedido/index" class="<?= ($activeMenu??'')==='pedidos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Mis pedidos
    </a>
    <a href="<?= BASE_URL ?>empresa-evidencia/index" class="<?= ($activeMenu??'')==='evidencias'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      Mis evidencias
    </a>
    <a href="<?= BASE_URL ?>recurrente/index" class="<?= ($activeMenu??'')==='recurrentes'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      Pedidos recurrentes
    </a>
    <?php
    // Conteo de favoritos para mostrar en el sidebar
    $favCount = 0;
    try {
      $favSidebarModel = new FavoritoModel();
      $favCount = $favSidebarModel->contarPorUsuario($_SESSION['usuario']['id'] ?? 0);
    } catch (\Throwable $e) { $favCount = 0; }
    ?>
    <a href="<?= BASE_URL ?>favorito/index" class="<?= ($activeMenu??'')==='favoritos'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/></svg>
      Mis favoritos
      <?php if ($favCount > 0): ?>
        <span style="margin-left:auto;background:#FEF2F2;color:var(--color-primary);font-size:.62rem;font-weight:700;padding:1px 7px;border-radius:999px;line-height:1.5"><?= $favCount ?></span>
      <?php endif; ?>
    </a>

    <div class="sidebar-section">Mis ubicaciones</div>
    <?php
    // Mostrar conteo de sucursales en sidebar
    try {
      $susModel  = new SucursalModel();
      $susCount  = $susModel->contarPorComprador($_SESSION['usuario']['id'] ?? 0);
      $susScript = new SuscripcionModel();
      $susSub    = $susScript->getByEmpresa($_SESSION['usuario']['empresa_id'] ?? 0);
      $susMax    = (int)($susSub['max_sucursales'] ?? 3);
    } catch (\Throwable $e) { $susCount = 0; $susMax = 3; }
    ?>
    <a href="<?= BASE_URL ?>comprador-sucursal/index" class="<?= ($activeMenu??'')==='mis_sucursales'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Mis sucursales
      <span style="margin-left:auto;font-size:.68rem;color:#9CA3AF"><?= $susCount ?>/<?= $susMax > 0 ? $susMax : '∞' ?></span>
    </a>
    <?php endif; ?>

    <div class="sidebar-section">Cuenta</div>
    <a href="<?= BASE_URL ?>cuenta/perfil" class="<?= ($activeMenu??'')==='cuenta'?'active':'' ?>">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A8.966 8.966 0 0112 15c2.485 0 4.745.99 6.379 2.596M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Mi perfil
    </a>

  </nav>

  <!-- User card -->
  <div class="sidebar-user">
    <div class="sidebar-user-info">
      <?php if (!empty($usuario['avatar'])): ?>
        <img src="<?= htmlspecialchars($usuario['avatar']) ?>" alt="Avatar"
             style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1.5px solid rgba(200,16,46,.4)">
      <?php else: ?>
        <div class="sidebar-avatar">
          <?= strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1)) ?>
        </div>
      <?php endif; ?>
      <div style="overflow:hidden;min-width:0">
        <div class="sidebar-user-name"><?= htmlspecialchars($usuario['nombre'] ?? '') ?></div>
        <span class="badge-rol"><?= htmlspecialchars($usuario['rol_nombre'] ?? '') ?></span>
      </div>
    </div>
    <a href="<?= BASE_URL ?>auth/logout" class="sidebar-logout">
      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Cerrar sesión
    </a>
  </div>

</aside>

<!-- ── Main content ───────────────────────────────────────────────────────── -->
<div class="main-content">

  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-left">
      <h1 class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
      <?php if (!empty($flash)): ?>
        <div class="topbar-sep"></div>
        <div class="topbar-flash <?= $flash['type'] === 'error' ? 'is-error' : 'is-success' ?>">
          <?= htmlspecialchars($flash['message']) ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="topbar-right">
      <?php if (!empty($empresa)): ?>
      <span class="topbar-company-chip">
        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:#9CA3AF"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3"/></svg>
        <?= htmlspecialchars(mb_strimwidth($empresa['razon_social'] ?? '', 0, 28, '…')) ?>
      </span>
      <?php endif; ?>
      <?php if (!empty($usuario['avatar'])): ?>
        <img src="<?= htmlspecialchars($usuario['avatar']) ?>" alt="Avatar"
             style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid rgba(200,16,46,.25)">
      <?php else: ?>
        <div class="topbar-avatar">
          <?= strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1)) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Page body -->
  <div class="page-body">
    <?= $content ?? '' ?>
  </div>

</div>

</body>
</html>
