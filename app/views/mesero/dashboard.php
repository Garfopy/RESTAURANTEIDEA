<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mesero — <?= htmlspecialchars($restaurante['nombre'] ?? '') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { background: #F9FAFB; font-family: system-ui, sans-serif; }
    .topbar { background: #fff; border-bottom: 1px solid #E5E7EB; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; }
    .mesa-card { background: #fff; border: 2px solid #E5E7EB; border-radius: 12px; padding: 16px; text-align: center; cursor: pointer; transition: border-color .2s; }
    .mesa-card.disponible { border-color: #10B981; }
    .mesa-card.ocupada    { border-color: #F59E0B; }
    .mesa-card.pagando    { border-color: #EF4444; }
  </style>
</head>
<body>
<div class="topbar">
  <div style="font-weight:700;font-size:1rem">🍽 Mesero — <?= htmlspecialchars($restaurante['nombre'] ?? '') ?></div>
  <div style="display:flex;gap:12px;align-items:center">
    <a href="<?= BASE_URL ?>rest-pedido/nuevo" style="padding:8px 14px;background:#C8102E;color:#fff;border-radius:8px;font-size:.85rem;text-decoration:none;font-weight:500">+ Pedido</a>
    <a href="<?= BASE_URL ?>auth/logout" style="color:#6B7280;font-size:.8rem">Salir</a>
  </div>
</div>

<div style="padding:20px">
  <!-- Órdenes listas para entregar -->
  <?php if (!empty($listos)): ?>
  <div style="background:#DCFCE7;border:1px solid #86EFAC;border-radius:12px;padding:16px;margin-bottom:20px">
    <div style="font-weight:600;color:#166534;margin-bottom:10px">✅ Órdenes listas para entregar (<?= count($listos) ?>)</div>
    <?php foreach ($listos as $p): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #BBF7D0">
      <span style="font-size:.875rem;font-weight:500"><?= htmlspecialchars($p['folio']) ?> — <?= htmlspecialchars($p['mesa_nombre'] ?? '—') ?></span>
      <button onclick="fetch('<?= BASE_URL ?>rest-mesero/marcarEntregado/<?= $p['id'] ?>',{method:'POST'}).then(()=>location.reload())"
        style="padding:5px 14px;background:#10B981;color:#fff;border:none;border-radius:8px;font-size:.8rem;font-weight:500;cursor:pointer">
        Entregado ✓
      </button>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Layout de mesas -->
  <div style="font-weight:600;font-size:1rem;margin-bottom:14px">Mesas</div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px">
    <?php foreach ($mesas as $m): ?>
    <div class="mesa-card <?= $m['estado'] ?>" onclick="location.href='<?= BASE_URL ?>rest-pedido/nuevo/<?= $m['id'] ?>'">
      <div style="font-size:1.4rem;margin-bottom:4px">🪑</div>
      <div style="font-weight:700"><?= htmlspecialchars($m['nombre']) ?></div>
      <div style="font-size:.75rem;color:#6B7280"><?= (int)$m['capacidad'] ?> personas</div>
      <div style="font-size:.72rem;font-weight:600;margin-top:6px;
        color:<?= ['disponible'=>'#10B981','ocupada'=>'#F59E0B','reservada'=>'#3B82F6','pagando'=>'#EF4444'][$m['estado']] ?? '#6B7280' ?>">
        <?= strtoupper($m['estado']) ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
