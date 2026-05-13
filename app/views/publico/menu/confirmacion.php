<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>¡Pedido recibido! — <?= htmlspecialchars($restaurante['nombre'] ?? '') ?></title>
  <style>
    :root { --cp: <?= htmlspecialchars($restaurante['color_primario'] ?? '#C8102E') ?>; }
    body { font-family: system-ui, sans-serif; background: #F9FAFB; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .card { background: #fff; border-radius: 16px; border: 1px solid #E5E7EB; padding: 32px; max-width: 420px; width: 100%; text-align: center; }
  </style>
</head>
<body>
<div class="card">
  <div style="font-size:3rem;margin-bottom:12px">✅</div>
  <h1 style="font-size:1.3rem;font-weight:700;color:#111827;margin-bottom:8px">¡Pedido recibido!</h1>
  <p style="color:#6B7280;font-size:.9rem;margin-bottom:20px">Tu orden está siendo preparada. Te avisamos cuando esté lista.</p>

  <div style="background:#F9FAFB;border-radius:10px;padding:16px;margin-bottom:20px;text-align:left">
    <?php foreach ($pedidos as $p): ?>
    <div style="font-size:.85rem;padding:4px 0;border-bottom:1px solid #F3F4F6">
      <strong><?= htmlspecialchars($p['folio']) ?></strong>
      <span style="font-size:.75rem;color:#6B7280;margin-left:6px"><?= htmlspecialchars($p['mesa_nombre'] ?? '') ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <a href="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug'] ?? '') ?>/pagar/<?= (int)($visita['id'] ?? 0) ?>"
     style="display:block;padding:12px;background:var(--cp);color:#fff;border-radius:10px;font-weight:700;text-decoration:none;margin-bottom:10px">
    Pagar mi cuenta
  </a>
  <a href="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug'] ?? '') ?>"
     style="display:block;font-size:.875rem;color:#6B7280">← Agregar más items</a>

  <div style="margin-top:24px;font-size:.7rem;color:#9CA3AF">Potenciado por <strong>CarniHub</strong></div>
</div>
</body>
</html>
