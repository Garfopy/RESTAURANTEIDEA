<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cocina — <?= htmlspecialchars($restaurante['nombre'] ?? 'Cocina') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root { --cp: <?= htmlspecialchars($restaurante['color_primario'] ?? '#A97C3F') ?>; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: 'Inter', sans-serif; background: #111827; color: #F9FAFB; min-height: 100vh; }
  header { background: var(--cp); padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; }
  header h1 { font-size: 1.1rem; margin: 0; }
  header a { color: #fff; text-decoration: none; font-size: .82rem; font-weight: 600; background: rgba(0,0,0,.2); padding: 8px 14px; border-radius: 8px; }
  .kds-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; padding: 20px; }
  .kds-card { background: #1F2937; border: 1px solid #374151; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 10px; }
  .kds-card.urgente { border-color: #EF4444; box-shadow: 0 0 0 1px #EF4444; }
  .kds-card-head { display: flex; justify-content: space-between; align-items: center; }
  .kds-folio { font-weight: 800; font-size: 1rem; }
  .kds-tipo { font-size: .72rem; background: #374151; padding: 2px 8px; border-radius: 99px; }
  .kds-espera { font-size: .74rem; color: #9CA3AF; }
  .kds-item { background: #111827; border-radius: 8px; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
  .kds-item-info { min-width: 0; }
  .kds-item-nombre { font-weight: 700; font-size: .92rem; }
  .kds-item-detalle { font-size: .74rem; color: #9CA3AF; margin-top: 2px; }
  .kds-btn { border: none; border-radius: 8px; padding: 8px 12px; font-size: .78rem; font-weight: 700; cursor: pointer; white-space: nowrap; }
  .kds-btn-iniciar { background: #F59E0B; color: #111827; }
  .kds-btn-listo { background: #10B981; color: #fff; }
  .kds-badge-listo { background: #065F46; color: #A7F3D0; font-size: .72rem; font-weight: 700; padding: 6px 10px; border-radius: 8px; }
  .kds-listo-entrega { margin-top: 4px; background: #064E3B; color: #A7F3D0; border: 1px solid #047857; border-radius: 8px; padding: 10px; font-size: .8rem; font-weight: 700; text-align: center; }
  .kds-empty { text-align: center; padding: 80px 20px; color: #6B7280; }
  .kds-notas { font-size: .74rem; color: #FCD34D; margin-top: 4px; }
</style>
</head>
<body>
<header>
  <h1>🍳 Cocina — <?= htmlspecialchars($restaurante['nombre'] ?? '') ?></h1>
  <a href="<?= BASE_URL ?>auth/logout">Cerrar sesión</a>
</header>

<?php if (!empty($flash)): ?>
<div style="background:<?= $flash['type'] === 'success' ? '#065F46' : '#7F1D1D' ?>;color:#fff;padding:10px 20px;font-size:.85rem"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<?php if (empty($pedidos)): ?>
<div class="kds-empty">
  <div style="font-size:2.5rem;margin-bottom:10px">✅</div>
  <div>No hay pedidos pendientes por ahora.</div>
</div>
<?php else: ?>
<div class="kds-grid">
  <?php foreach ($pedidos as $ped): ?>
  <?php $urgente = (int)$ped['minutos_espera'] >= 15; ?>
  <div class="kds-card <?= $urgente ? 'urgente' : '' ?>" data-pedido="<?= (int)$ped['id'] ?>">
    <div class="kds-card-head">
      <span class="kds-folio">#<?= htmlspecialchars($ped['folio']) ?></span>
      <span class="kds-tipo"><?= htmlspecialchars(['pickup' => 'Recoger', 'delivery' => 'Domicilio', 'take_out' => 'Para llevar'][$ped['tipo_pedido']] ?? ($ped['tipo_pedido'] ?: '—')) ?></span>
    </div>
    <div class="kds-espera"><?= (int)$ped['minutos_espera'] ?> min en espera</div>
    <?php if (!empty($ped['pedido_notas'])): ?>
    <div class="kds-notas">📝 <?= htmlspecialchars($ped['pedido_notas']) ?></div>
    <?php endif; ?>

    <?php $todosListo = true; ?>
    <?php foreach ($ped['items'] as $it): ?>
      <?php if ($it['estado'] !== 'listo') $todosListo = false; ?>
      <div class="kds-item">
        <div class="kds-item-info">
          <div class="kds-item-nombre"><?= (int)$it['cantidad'] ?>× <?= htmlspecialchars($it['platillo_nombre']) ?></div>
          <?php if (!empty($it['extras'])): ?>
          <div class="kds-item-detalle"><?= htmlspecialchars(implode(', ', array_map(fn($e) => $e['nombre'] . ($e['cantidad'] > 1 ? ' x' . $e['cantidad'] : ''), $it['extras']))) ?></div>
          <?php endif; ?>
          <?php if (!empty($it['notas'])): ?>
          <div class="kds-item-detalle">📝 <?= htmlspecialchars($it['notas']) ?></div>
          <?php endif; ?>
        </div>
        <?php if ($it['estado'] === 'pendiente'): ?>
        <button class="kds-btn kds-btn-iniciar" onclick="avanzar(<?= (int)$it['id'] ?>, this)">Iniciar</button>
        <?php elseif ($it['estado'] === 'en_preparacion'): ?>
        <button class="kds-btn kds-btn-listo" onclick="avanzar(<?= (int)$it['id'] ?>, this)">Marcar listo</button>
        <?php else: ?>
        <span class="kds-badge-listo">✓ Listo</span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <?php if ($todosListo): ?>
    <div class="kds-listo-entrega">✓ Pedido listo · Caja confirma la entrega</div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const CSRF = <?= json_encode($csrf) ?>;

async function postAction(path) {
  const response = await fetch(BASE_URL + path, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'X-CSRF-Token': CSRF
    }
  });
  let data = {};
  try { data = await response.json(); } catch (_) {}
  if (!response.ok || !data.ok) {
    throw new Error(data.msg || 'No se pudo actualizar el pedido.');
  }
  return data;
}

async function avanzar(itemId, btn) {
  btn.disabled = true;
  try {
    await postAction('rest-cocina/avanzarItem/' + itemId);
    location.reload();
  } catch (error) {
    btn.disabled = false;
    alert(error.message || 'No se pudo actualizar, intenta de nuevo.');
  }
}

// Refresco automático — modelo simple de polling (v1)
setTimeout(() => location.reload(), 20000);
</script>
</body>
</html>
