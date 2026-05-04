<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial de entregas — CarniHub</title>
  <style>
    * { box-sizing: border-box; }
    body { background: #111827; color: #F9FAFB; font-family: 'Inter', sans-serif; margin: 0; }
    .header { background: #1F2937; padding: 14px 16px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #374151; }
    .row { background: #1F2937; border-radius: 10px; padding: 14px 16px; margin-bottom: 10px; }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<div class="header">
  <a href="<?= BASE_URL ?>repartidor/inicio" style="color:#9CA3AF;text-decoration:none;font-size:1.4rem">&larr;</a>
  <div style="font-weight:800">Historial de entregas</div>
</div>

<div style="padding:16px">
  <?php if (empty($historial)): ?>
    <div style="text-align:center;padding:40px;color:#6B7280">
      <div style="font-size:2rem;margin-bottom:10px">📋</div>
      <p>No hay entregas registradas aún.</p>
    </div>
  <?php else: ?>
    <?php foreach ($historial as $h): ?>
    <div class="row">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
          <div style="font-weight:700;font-size:.9rem"><?= htmlspecialchars($h['sucursal_nombre']) ?></div>
          <div style="font-size:.75rem;color:#9CA3AF"><?= htmlspecialchars($h['empresa_nombre']) ?></div>
          <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px">Pedido: <?= htmlspecialchars($h['folio']) ?></div>
        </div>
        <div style="text-align:right">
          <span style="background:#064E3B;color:#6EE7B7;padding:2px 8px;border-radius:999px;font-size:.7rem;font-weight:600">Entregado</span>
          <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px">
            <?= $h['hora_entrega'] ? date('d/m/Y H:i', strtotime($h['hora_entrega'])) : $h['fecha'] ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</body>
</html>
