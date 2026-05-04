<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar Admin -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <a href="<?= BASE_URL ?>dashboard/index">
      <?php
        try {
          $_lr = Database::getInstance()->query("SELECT clave,valor FROM global_settings WHERE clave IN ('app_logo','app_nombre')")->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch(Exception $e){ $_lr=[]; }
        $_lp = $_lr['app_logo'] ?? '';
        $_ln = $_lr['app_nombre'] ?? 'CarniHub';
      ?>
      <?php if(!empty($_lp)): ?>
      <img src="<?= BASE_URL . htmlspecialchars($_lp) ?>" alt="<?= htmlspecialchars($_ln) ?>" style="height:36px;max-width:160px;object-fit:contain">
      <?php else: ?>
      <span style="font-size:1.2rem;font-weight:800;color:#C8102E;letter-spacing:-1px"><?= htmlspecialchars($_ln) ?></span>
      <?php endif; ?>
    </a>
    <div style="font-size:.7rem;color:#6B7280;margin-top:4px">
      <?= htmlspecialchars($_SESSION['usuario']['rol_nombre'] ?? '') ?>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php $current = ($_GET['_route'] ?? ($ctrlSlug ?? '')); ?>

    <a href="<?= BASE_URL ?>dashboard/index" class="<?= $current === 'dashboard' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Dashboard
    </a>

    <div class="sidebar-section-title">Ventas</div>

    <a href="<?= BASE_URL ?>cliente/index" class="<?= $current === 'cliente' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Clientes
    </a>

    <a href="<?= BASE_URL ?>pedido/index" class="<?= $current === 'pedido' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Pedidos
    </a>

    <div class="sidebar-section-title">Catálogo</div>

    <a href="<?= BASE_URL ?>producto/index" class="<?= $current === 'producto' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      Productos
    </a>

    <a href="<?= BASE_URL ?>inventario/index" class="<?= $current === 'inventario' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      Inventario
    </a>

    <div class="sidebar-section-title">Operaciones</div>

    <a href="<?= BASE_URL ?>logistica/rutas" class="<?= $current === 'logistica' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
      Logística
    </a>

    <a href="<?= BASE_URL ?>reporte/index" class="<?= $current === 'reporte' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      Reportes
    </a>

    <a href="<?= BASE_URL ?>usuario/index" class="<?= $current === 'usuario' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      Usuarios
    </a>

    <div class="sidebar-section-title">Sistema</div>

    <a href="<?= BASE_URL ?>config/general" class="<?= $current === 'config' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Configuración
    </a>

    <a href="<?= BASE_URL ?>config/bitacora">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Bitácora
    </a>

    <a href="<?= BASE_URL ?>auth/logout" style="margin-top:auto;color:#EF4444;">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Cerrar sesión
    </a>
  </nav>
</aside>

<!-- Topbar -->
<div class="main-content">
  <div class="topbar">
    <button onclick="toggleSidebar()" class="hide-desktop p-2">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div style="display:flex;align-items:center;gap:10px">
      <?php
        $su = $_SESSION['usuario'] ?? [];
        $suNombreCompleto = trim(
            ($su['nombre'] ?? '') . ' ' . ($su['apellido_paterno'] ?? '')
        );
        if (!empty($su['apellido_materno'])) $suNombreCompleto .= ' ' . $su['apellido_materno'];
        $suInicial = strtoupper(mb_substr($su['nombre'] ?? 'A', 0, 1, 'UTF-8'));
      ?>
      <span style="font-size:.875rem;font-weight:500;color:#374151"><?= htmlspecialchars($suNombreCompleto) ?></span>
      <a href="<?= BASE_URL ?>usuario/editar/<?= $su['id'] ?? '' ?>"
         style="width:36px;height:36px;border-radius:50%;background:#C8102E;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.875rem;overflow:hidden;text-decoration:none;flex-shrink:0">
        <?php if (!empty($su['avatar'])): ?>
        <img src="<?= BASE_URL . htmlspecialchars($su['avatar']) ?>" style="width:100%;height:100%;object-fit:cover" alt="">
        <?php else: ?>
        <?= $suInicial ?>
        <?php endif; ?>
      </a>
    </div>
  </div>
  <div class="page-content">
