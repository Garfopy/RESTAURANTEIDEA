<?php $esSuperAdmin = ($_SESSION['usuario']['rol_slug'] ?? '') === 'superadmin'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $esSuperAdmin ? 'Administración general' : 'Mis restaurantes' ?> — Jungle Pizza</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body style="background:#F9FAFB;font-family:system-ui,sans-serif;min-height:100vh">
  <div style="padding:48px 20px;max-width:600px;margin:0 auto;text-align:center">
    <h1 style="font-size:1.6rem;font-weight:700;color:#111827;margin-bottom:8px"><?= $esSuperAdmin ? 'Administración general' : 'Mis restaurantes' ?></h1>
    <p style="color:#6B7280;margin-bottom:32px"><?= $esSuperAdmin ? 'Selecciona cualquier restaurante para gestionar su operación.' : 'Selecciona el restaurante que deseas administrar.' ?></p>

    <?php foreach ($restaurantes as $r): ?>
    <a href="<?= BASE_URL ?>restaurante/activar/<?= $r['id'] ?>"
       style="display:block;background:#fff;border:2px solid #E5E7EB;border-radius:12px;padding:20px;margin-bottom:12px;text-decoration:none;text-align:left;transition:.15s"
       onmouseover="this.style.borderColor='<?= htmlspecialchars($r['color_primario']) ?>'"
       onmouseout="this.style.borderColor='#E5E7EB'">
      <div style="display:flex;align-items:center;gap:14px">
        <?php if ($r['logo']): ?>
        <img src="<?= BASE_URL . htmlspecialchars($r['logo']) ?>" alt="" style="width:48px;height:48px;border-radius:8px;object-fit:cover">
        <?php else: ?>
        <div style="width:48px;height:48px;border-radius:8px;background:<?= htmlspecialchars($r['color_primario']) ?>;display:flex;align-items:center;justify-content:center;color:#fff" aria-hidden="true">
          <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M6 6l1 14h10l1-14M9 3h6"/></svg>
        </div>
        <?php endif; ?>
        <div>
          <div style="font-weight:700;font-size:1rem;color:#111827"><?= htmlspecialchars($r['nombre']) ?></div>
          <?php if (!empty($r['empresa_nombre'])): ?>
          <div style="font-size:.75rem;font-weight:700;color:#006c68"><?= htmlspecialchars($r['empresa_nombre']) ?></div>
          <?php endif; ?>
          <div style="font-size:.8rem;color:#6B7280"><?= htmlspecialchars($r['direccion'] ?? '') ?></div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>

    <?php if (!$esSuperAdmin): ?>
    <a href="<?= BASE_URL ?>restaurante/crear"
       style="display:inline-block;margin-top:12px;padding:10px 24px;background:#111827;color:#fff;border-radius:10px;font-weight:600;font-size:.9rem;text-decoration:none">
      + Crear nuevo restaurante
    </a>
    <?php endif; ?>

    <div style="margin-top:24px">
      <a href="<?= BASE_URL ?>auth/logout" style="display:inline-flex;align-items:center;gap:9px;font-size:1rem;font-weight:800;color:#334155;text-decoration:none;background:#fff;border:1px solid #E2E8F0;border-radius:999px;padding:9px 14px;box-shadow:0 8px 20px rgba(15,23,42,.06)">Cerrar sesión</a>
    </div>
  </div>
</body>
</html>
