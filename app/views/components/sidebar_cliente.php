<!-- Sidebar Cliente -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <a href="<?= BASE_URL ?>carrito/inicio">
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
      <?= htmlspecialchars($_SESSION['empresa']['razon_social'] ?? '') ?>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php $current = ($ctrlSlug ?? ''); ?>

    <a href="<?= BASE_URL ?>carrito/inicio" class="<?= $current === 'inicio' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Inicio
    </a>

    <a href="<?= BASE_URL ?>producto/catalogo" class="<?= $current === 'catalogo' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
      Catálogo
    </a>

    <a href="<?= BASE_URL ?>pedido/index" class="<?= $current === 'pedido' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Pedidos
    </a>

    <a href="<?= BASE_URL ?>recurrente/index">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      Pedidos Recurrentes
    </a>

    <a href="<?= BASE_URL ?>sucursal/index">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Mis Sucursales
    </a>

    <a href="<?= BASE_URL ?>reporte/cliente">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      Reportes
    </a>

    <a href="<?= BASE_URL ?>cuenta/perfil" class="<?= $current === 'cuenta' ? 'active' : '' ?>">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Mi Cuenta
    </a>

    <a href="<?= BASE_URL ?>auth/logout" style="margin-top:auto;color:#EF4444;">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Cerrar sesión
    </a>
  </nav>
</aside>

<div class="main-content">
  <div class="topbar">
    <button onclick="toggleSidebar()" class="hide-desktop" style="padding:8px;background:none;border:none;cursor:pointer">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div style="display:flex;align-items:center;gap:12px">
      <a href="<?= BASE_URL ?>carrito/index" style="position:relative;text-decoration:none;color:#374151">
        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <?php if (!empty($_SESSION['carrito'])): ?>
        <span style="position:absolute;top:-6px;right:-6px;background:#C8102E;color:#fff;border-radius:50%;width:16px;height:16px;font-size:10px;display:flex;align-items:center;justify-content:center;font-weight:700">
          <?= count($_SESSION['carrito']) ?>
        </span>
        <?php endif; ?>
      </a>
      <div style="display:flex;align-items:center;gap:8px">
        <?php
          $cu = $_SESSION['usuario'] ?? [];
          $cuNombre = trim(($cu['nombre'] ?? '') . ' ' . ($cu['apellido_paterno'] ?? ''));
          $cuInicial = strtoupper(mb_substr($cu['nombre'] ?? 'U', 0, 1, 'UTF-8'));
        ?>
        <div style="width:32px;height:32px;border-radius:50%;background:#C8102E;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8rem;overflow:hidden;flex-shrink:0">
          <?php if (!empty($cu['avatar'])): ?>
          <img src="<?= BASE_URL . htmlspecialchars($cu['avatar']) ?>" style="width:100%;height:100%;object-fit:cover" alt="">
          <?php else: ?>
          <?= $cuInicial ?>
          <?php endif; ?>
        </div>
        <div style="font-size:.875rem;font-weight:600;color:#374151">
          <?= htmlspecialchars($cuNombre) ?>
        </div>
      </div>
    </div>
  </div>
  <div class="page-content">
