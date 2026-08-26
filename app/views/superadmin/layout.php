<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Superadmin') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body { margin: 0; font-family: 'Inter', sans-serif; background: #F3F4F6; color: #111827; }
  .sa-shell { display: flex; min-height: 100vh; }
  .sa-sidebar { width: 220px; flex-shrink: 0; background: #111827; color: #fff; padding: 20px 0; }
  .sa-brand { padding: 0 20px 20px; font-weight: 800; font-size: 1.05rem; border-bottom: 1px solid #1F2937; margin-bottom: 14px; }
  .sa-brand span { display: block; font-size: .7rem; color: #9CA3AF; font-weight: 500; margin-top: 2px; }
  .sa-nav a { display: block; padding: 10px 20px; color: #D1D5DB; text-decoration: none; font-size: .88rem; font-weight: 500; border-left: 3px solid transparent; }
  .sa-nav a:hover { background: #1F2937; color: #fff; }
  .sa-nav a.active { background: #1F2937; color: #fff; border-left-color: #A97C3F; font-weight: 700; }
  .sa-nav a.exit { margin-top: 20px; border-top: 1px solid #1F2937; padding-top: 16px; color: #F87171; }
  .sa-main { flex: 1; padding: 28px 32px; max-width: 1400px; }
  .sa-flash { padding: 10px 16px; border-radius: 8px; font-size: .85rem; margin-bottom: 16px; }
  .sa-flash.success { background: #D1FAE5; color: #065F46; }
  .sa-flash.error { background: #FEE2E2; color: #991B1B; }
</style>
</head>
<body>
<div class="sa-shell">
  <aside class="sa-sidebar">
    <div class="sa-brand">Panel Superadmin<span>Plataforma</span></div>
    <nav class="sa-nav">
      <a href="<?= BASE_URL ?>superadmin/dashboard" class="<?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
      <a href="<?= BASE_URL ?>superadmin/negocios" class="<?= ($activeMenu ?? '') === 'negocios' ? 'active' : '' ?>">Negocios</a>
      <a href="<?= BASE_URL ?>superadmin/nuevoNegocio" class="<?= ($activeMenu ?? '') === 'nuevo' ? 'active' : '' ?>">+ Nuevo negocio</a>
      <a href="<?= BASE_URL ?>restaurante/seleccionar">Entrar a un negocio</a>
      <a href="<?= BASE_URL ?>auth/logout" class="exit">Cerrar sesión</a>
    </nav>
  </aside>
  <main class="sa-main">
    <?php if (!empty($flash)): ?>
    <div class="sa-flash <?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <?= $content ?? '' ?>
  </main>
</div>
</body>
</html>
