<?php
$usuario    = $_SESSION['usuario'] ?? [];
$activeMenu = $activeMenu ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Superadmin') ?> — Plataforma</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/restaurant.css?v=<?= @filemtime(ROOT_PATH . '/public/css/restaurant.css') ?: time() ?>">
  <style>
    :root {
      --cp: #1E293B;
      --cs: #0F172A;
      --color-primary:   #1E293B;
      --color-secondary: #0F172A;
    }
    .rst-sidebar { background: var(--cp); border: none; }
    .rst-sidebar-logo { border-bottom-color: rgba(255,255,255,.15); }
    .rst-nav-section { color: rgba(255,255,255,.55); }
    .rst-nav-link { color: rgba(255,255,255,.82); }
    .rst-nav-link:hover { background: rgba(255,255,255,.15); color: #fff; }
    .rst-nav-link.active {
      background: rgba(255,255,255,.22);
      color: #fff;
      border-left-color: rgba(255,255,255,.9);
      font-weight: 700;
    }
    .rst-sidebar-footer { border-top-color: rgba(255,255,255,.15); color: rgba(255,255,255,.5); }
    .rst-sidebar-footer strong { color: rgba(255,255,255,.7); }
  </style>
</head>
<body>

<aside class="rst-sidebar" id="rstSidebar">
  <div class="rst-sidebar-logo">
    <div style="font-weight:700;font-size:.95rem;color:#fff;line-height:1.2">Panel de Plataforma</div>
    <div style="font-size:.7rem;color:rgba(255,255,255,.65);margin-top:3px">Superadmin</div>
  </div>

  <nav>
    <a class="rst-nav-link <?= $activeMenu === 'sa_dashboard' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>superadmin/dashboard">
      <svg style="width:16px;height:16px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Dashboard
    </a>

    <div class="rst-nav-section">Marketplace</div>
    <a class="rst-nav-link <?= $activeMenu === 'sa_negocios' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>superadmin/negocios">
      <svg style="width:16px;height:16px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      Negocios
    </a>
    <a class="rst-nav-link <?= $activeMenu === 'sa_puntos_referencia' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>superadmin/puntosReferencia">
      <svg style="width:16px;height:16px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Puntos de referencia
    </a>

    <div class="rst-nav-section">Accesos</div>
    <a class="rst-nav-link <?= $activeMenu === 'sa_usuarios' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>superadmin/usuarios">
      <svg style="width:16px;height:16px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Usuarios
    </a>
    <a class="rst-nav-link <?= $activeMenu === 'sa_bitacora' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>superadmin/bitacora">
      <svg style="width:16px;height:16px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Bitácora
    </a>

    <div class="rst-nav-section">Plataforma</div>
    <a class="rst-nav-link <?= $activeMenu === 'sa_config' ? 'active' : '' ?>"
       href="<?= BASE_URL ?>superadmin/config">
      <svg style="width:16px;height:16px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Configuración global
    </a>
  </nav>

  <div class="rst-sidebar-footer">
    <a href="<?= BASE_URL ?>restaurante/seleccionar"
       style="display:flex;align-items:center;justify-content:center;gap:6px;
              padding:8px 12px;margin-bottom:8px;border-radius:8px;
              background:rgba(255,255,255,.08);color:rgba(255,255,255,.85);text-decoration:none;
              font-size:.78rem;font-weight:600;transition:background .15s"
       onmouseover="this.style.background='rgba(255,255,255,.18)'"
       onmouseout="this.style.background='rgba(255,255,255,.08)'">
      Entrar como Admin de un negocio →
    </a>
    <a href="<?= BASE_URL ?>auth/logout"
       style="display:flex;align-items:center;justify-content:center;gap:6px;
              padding:8px 12px;margin-bottom:10px;border-radius:8px;
              background:rgba(255,255,255,.15);color:#fff;text-decoration:none;
              font-size:.82rem;font-weight:600;transition:background .15s"
       onmouseover="this.style.background='rgba(255,255,255,.25)'"
       onmouseout="this.style.background='rgba(255,255,255,.15)'">
      <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Cerrar sesión
    </a>
    <div style="text-align:center;font-size:.7rem;color:#9CA3AF">
      <?= htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido_paterno'] ?? '')) ?>
    </div>
  </div>
</aside>

<div class="rst-main">
  <div class="rst-page page-content">
    <div id="menuToggleWrap" style="display:none;margin-bottom:12px">
      <button onclick="document.getElementById('rstSidebar').classList.toggle('open')"
              style="background:#fff;border:1px solid #E5E7EB;border-radius:10px;cursor:pointer;padding:8px 10px;display:flex;align-items:center;gap:8px;color:#111827"
              id="menuToggle">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <span style="font-size:.82rem;font-weight:600">Menu</span>
      </button>
    </div>
    <?php if (!empty($flash)): ?>
    <div class="flash flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"
         data-flash="<?= htmlspecialchars(md5(($flash['type'] ?? '') . '|' . ($flash['message'] ?? ''))) ?>"
         onclick="this.remove()">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>
    <?= $content ?? '' ?>
  </div>
</div>

<script>
if (window.innerWidth <= 768) {
  document.getElementById('menuToggleWrap').style.display = 'block';
}
document.addEventListener('click', e => {
  const sb = document.getElementById('rstSidebar');
  if (window.innerWidth <= 768 && sb.classList.contains('open') && !sb.contains(e.target)) {
    sb.classList.remove('open');
  }
});
(function(){
  const seen = new Set();
  document.querySelectorAll('.flash[data-flash]').forEach(el => {
    const k = el.dataset.flash;
    if (seen.has(k)) { el.remove(); return; }
    seen.add(k);
    setTimeout(() => el.remove(), 5000);
  });
})();
</script>
</body>
</html>
