<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
$frecuencias = ['diario'=>'Diario','semanal'=>'Semanal','quincenal'=>'Quincenal'];
?>
<div style="margin-bottom:16px">
  <a href="<?= BASE_URL ?>recurrente/index" style="font-size:.875rem;color:#6B7280;text-decoration:none">← Recurrentes</a>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
  <h1 style="font-size:1.1rem;font-weight:700;margin:0"><?= htmlspecialchars($recurrente['nombre']) ?></h1>
  <?php if ($recurrente['pausado']): ?>
  <span style="background:#F3F4F6;color:#6B7280;font-size:.75rem;font-weight:600;padding:3px 12px;border-radius:20px">⏸ Pausado</span>
  <?php else: ?>
  <span style="background:#D1FAE5;color:#065F46;font-size:.75rem;font-weight:600;padding:3px 12px;border-radius:20px">● Activo</span>
  <?php endif; ?>
</div>

<!-- Info -->
<div class="card" style="margin-bottom:12px">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:.875rem">
    <div>
      <div style="color:#6B7280;font-size:.75rem">Frecuencia</div>
      <div style="font-weight:600"><?= $frecuencias[$recurrente['frecuencia']] ?? $recurrente['frecuencia'] ?></div>
    </div>
    <div>
      <div style="color:#6B7280;font-size:.75rem">Próximo pedido</div>
      <div style="font-weight:600"><?= $recurrente['proximo_pedido'] ? date('d/m/Y', strtotime($recurrente['proximo_pedido'])) : '—' ?></div>
    </div>
    <div>
      <div style="color:#6B7280;font-size:.75rem">Último pedido</div>
      <div style="font-weight:600"><?= $recurrente['ultimo_pedido'] ? date('d/m/Y', strtotime($recurrente['ultimo_pedido'])) : 'Nunca' ?></div>
    </div>
  </div>
</div>

<!-- Detalle de productos por sucursal -->
<?php foreach ($porSucursal as $sucId => $group): ?>
<div class="card" style="margin-bottom:12px;padding:14px">
  <div style="font-weight:700;margin-bottom:10px;color:#374151;font-size:.875rem">
    📍 <?= htmlspecialchars($group['sucursal']['nombre'] ?? 'Sucursal') ?>
  </div>
  <table style="width:100%;font-size:.8rem">
    <thead>
      <tr style="color:#6B7280;font-size:.7rem">
        <th style="text-align:left;padding-bottom:6px">Producto</th>
        <th style="text-align:center">Cantidad</th>
        <th style="text-align:right">Precio ref.</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($group['items'] as $item): ?>
      <tr style="border-top:1px solid #F9FAFB">
        <td style="padding:6px 0"><?= htmlspecialchars($item['producto_nombre']) ?></td>
        <td style="text-align:center"><?= number_format($item['cantidad'],0) ?> kg</td>
        <td style="text-align:right;color:#C8102E;font-weight:600">$<?= number_format($item['precio'],2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endforeach; ?>

<!-- Acciones -->
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
  <button onclick="confirmarAhora()" class="btn btn-primary" style="flex:1;justify-content:center">🛒 Pedir ahora</button>
  <?php if ($recurrente['pausado']): ?>
  <button onclick="togglePausa(0)" class="btn btn-secondary" style="flex:1;justify-content:center">▶ Reactivar</button>
  <?php else: ?>
  <button onclick="togglePausa(1)" class="btn btn-secondary" style="flex:1;justify-content:center">⏸ Pausar</button>
  <?php endif; ?>
  <a href="<?= BASE_URL ?>recurrente/index" class="btn btn-secondary" style="flex:1;justify-content:center">← Volver</a>
</div>

<script>
const REC_ID = <?= $recurrente['id'] ?>;

function confirmarAhora() {
  if (!confirm('¿Generar pedido con esta plantilla ahora?')) return;
  fetch('<?= BASE_URL ?>recurrente/confirmarAhora/' + REC_ID, { method: 'POST' })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        showToast('Pedido creado', 'success');
        setTimeout(() => window.location = '<?= BASE_URL ?>pedido/detalle/' + d.pedido_id, 1000);
      } else {
        showToast(d.error || 'Error', 'error');
      }
    });
}

function togglePausa(pausar) {
  const url = pausar ? '<?= BASE_URL ?>recurrente/pausar/' : '<?= BASE_URL ?>recurrente/activar/';
  fetch(url + REC_ID, { method: 'POST' })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
