<?php
/**
 * Layout principal del portal de administración del restaurante
 * $restaurante debe estar disponible en la vista que incluye este layout
 */
$usuario    = $_SESSION['usuario'] ?? [];
$restaurante = $restaurante ?? (new RestauranteModel())->find($_SESSION['restaurante_activo_id'] ?? 0);
$colorPri   = $restaurante['color_primario']   ?? '#C8102E';
$colorSec   = $restaurante['color_secundario'] ?? '#1f2937';
$restNombre = $restaurante['nombre'] ?? 'Mi Restaurante';
$restLogo   = $restaurante['logo']   ?? '';
$activeMenu = $activeMenu ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Restaurante') ?> — <?= htmlspecialchars($restNombre) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/carnihub.css">
  <script src="<?= BASE_URL ?>public/js/chart.umd.min.js"></script>
  <style>
    :root {
      --color-primary:   <?= htmlspecialchars($colorPri) ?>;
      --color-secondary: <?= htmlspecialchars($colorSec) ?>;
    }
    body { font-family: 'Inter', sans-serif; background: #F9FAFB; }
    .sidebar {
      width: 256px; height: 100vh; background: #fff;
      flex-shrink: 0; display: flex; flex-direction: column;
      position: fixed; top: 0; left: 0; z-index: 100;
      border-right: 1px solid #E5E7EB; overflow: hidden;
    }
    .sidebar-logo-area { padding: 18px 20px 14px; border-bottom: 1px solid #F3F4F6; }
    .sidebar nav { flex: 1; min-height: 0; padding: 8px 0; overflow-y: auto; }
    .sidebar a {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 14px; font-size: .845rem; color: #6B7280;
      text-decoration: none; border-radius: 8px; margin: 1px 10px;
      transition: background .15s, color .15s;
      border-left: 3px solid transparent; font-weight: 500;
    }
    .sidebar a:hover { background: #F3F4F6; color: #111827; }
    .sidebar a.active { background: color-mix(in srgb, var(--color-primary) 10%, white);
      color: var(--color-primary); border-left-color: var(--color-primary); font-weight: 600; }
    .sidebar-section { font-size: .68rem; font-weight: 700; color: #9CA3AF;
      text-transform: uppercase; letter-spacing: .08em; padding: 14px 24px 4px; }
    .main-content { margin-left: 256px; min-height: 100vh; }
    .topbar { background: #fff; border-bottom: 1px solid #E5E7EB;
      padding: 0 24px; height: 56px; display: flex; align-items: center; justify-content: space-between; }
  </style>
</head>
<body>
<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo-area">
    <?php if ($restLogo): ?>
    <img src="<?= BASE_URL . htmlspecialchars($restLogo) ?>" alt="Logo" style="height:36px;object-fit:contain;margin-bottom:6px">
    <?php endif; ?>
    <div style="font-weight:700;font-size:.95rem;color:#111827;line-height:1.2"><?= htmlspecialchars($restNombre) ?></div>
    <div style="font-size:.7rem;color:#9CA3AF;margin-top:3px">Portal Restaurante</div>
  </div>
  <nav>
    <a href="<?= BASE_URL ?>restaurante/dashboard" class="<?= $activeMenu === 'rest_dashboard' ? 'active' : '' ?>">
      <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Dashboard
    </a>

    <div class="sidebar-section">Operación</div>
    <a href="<?= BASE_URL ?>rest-mesa/index" class="<?= $activeMenu === 'rest_mesas' ? 'active' : '' ?>">
      <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      Mesas
    </a>
    <a href="<?= BASE_URL ?>rest-pedido/index" class="<?= $activeMenu === 'rest_pedidos' ? 'active' : '' ?>">
      <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Pedidos
    </a>
    <a href="<?= BASE_URL ?>rest-reserva/index" class="<?= $activeMenu === 'rest_reservas' ? 'active' : '' ?>">
      <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      Reservaciones
    </a>
    <a href="<?= BASE_URL ?>rest-ticket/index" class="<?= $activeMenu === 'rest_tickets' ? 'active' : '' ?>">
      <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
      Tickets
    </a>

    <div class="sidebar-section">Financiero</div>
    <a href="<?= BASE_URL ?>rest-finanzas/dashboard" class="<?= $activeMenu === 'rest_finanzas' ? 'active' : '' ?>">
      <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      Dashboard Financiero
    </a>
    <a href="<?= BASE_URL ?>rest-finanzas/gastos" class="<?= $activeMenu === 'rest_gastos' ? 'active' : '' ?>">Gastos</a>
    <a href="<?= BASE_URL ?>rest-finanzas/retiros" class="<?= $activeMenu === 'rest_retiros' ? 'active' : '' ?>">Retiros</a>
    <a href="<?= BASE_URL ?>rest-finanzas/cortes" class="<?= $activeMenu === 'rest_cortes' ? 'active' : '' ?>">Corte de Caja</a>

    <div class="sidebar-section">Cocina & Menú</div>
    <a href="<?= BASE_URL ?>rest-menu/index" class="<?= $activeMenu === 'rest_menu' ? 'active' : '' ?>">
      <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
      Menú
    </a>
    <a href="<?= BASE_URL ?>rest-inventario/index" class="<?= $activeMenu === 'rest_inventario' ? 'active' : '' ?>">
      <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      Inventario
    </a>

    <div class="sidebar-section">Clientes</div>
    <a href="<?= BASE_URL ?>rest-cliente/index" class="<?= $activeMenu === 'rest_clientes' ? 'active' : '' ?>">Comensales</a>
    <a href="<?= BASE_URL ?>rest-cliente/topConsumo">Top por Consumo</a>
    <a href="<?= BASE_URL ?>rest-cliente/topVisitas">Top por Visitas</a>

    <div class="sidebar-section">Ajustes</div>
    <a href="<?= BASE_URL ?>rest-config/index" class="<?= $activeMenu === 'rest_config' ? 'active' : '' ?>">
      <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Configuración
    </a>
    <a href="<?= BASE_URL ?>comprador/inicio" style="margin-top:4px">
      <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      Volver al portal
    </a>
  </nav>
  <div style="padding:12px 16px;border-top:1px solid #F3F4F6;font-size:.68rem;color:#9CA3AF;text-align:center">
    Potenciado por <strong>CarniHub</strong>
  </div>
</aside>

<!-- Main content -->
<div class="main-content">
  <header class="topbar">
    <div style="font-weight:600;font-size:.95rem;color:#111827"><?= htmlspecialchars($pageTitle ?? '') ?></div>
    <div style="display:flex;align-items:center;gap:12px">
      <a href="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug'] ?? '') ?>" target="_blank"
         style="font-size:.8rem;color:var(--color-primary);font-weight:500">
        Ver menú público ↗
      </a>
      <span style="font-size:.82rem;color:#6B7280"><?= htmlspecialchars($usuario['nombre'] ?? '') ?></span>
    </div>
  </header>
  <div style="padding:24px">
    <?php if (!empty($flash)): ?>
    <div style="margin-bottom:16px;padding:12px 16px;border-radius:8px;font-size:.875rem;font-weight:500;
         background:<?= $flash['type'] === 'success' ? '#DCFCE7' : '#FEE2E2' ?>;
         color:<?= $flash['type'] === 'success' ? '#166534' : '#991B1B' ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>
    <?= $content ?? '' ?>
  </div>
</div>
</body>
</html>
