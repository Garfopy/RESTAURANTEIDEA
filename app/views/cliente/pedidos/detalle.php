<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
$estadoColors = [
  'pendiente'       => ['#FEF3C7','#92400E'],
  'confirmado'      => ['#DBEAFE','#1E40AF'],
  'en_preparacion'  => ['#FDE68A','#92400E'],
  'en_ruta'         => ['#D1FAE5','#065F46'],
  'entregado'       => ['#F0FDF4','#166534'],
  'cancelado'       => ['#FEE2E2','#991B1B'],
];
[$bgColor, $textColor] = $estadoColors[$pedido['estado']] ?? ['#F3F4F6','#374151'];
$estadoLabel = ucfirst(str_replace('_',' ',$pedido['estado']));
?>
<div style="margin-bottom:16px">
  <a href="<?= BASE_URL ?>pedido/index" style="font-size:.875rem;color:#6B7280;text-decoration:none">← Mis pedidos</a>
</div>

<!-- Header -->
<div class="card" style="margin-bottom:12px">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div>
      <div style="font-size:.75rem;color:#6B7280">Pedido</div>
      <div style="font-size:1.25rem;font-weight:800">#<?= $pedido['folio'] ?></div>
      <div style="font-size:.8rem;color:#6B7280"><?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></div>
    </div>
    <span style="background:<?= $bgColor ?>;color:<?= $textColor ?>;font-size:.75rem;font-weight:700;padding:4px 14px;border-radius:20px"><?= $estadoLabel ?></span>
  </div>
  <?php if ($pedido['fecha_entrega']): ?>
  <div style="margin-top:12px;padding-top:12px;border-top:1px solid #F3F4F6;font-size:.875rem;color:#374151">
    📅 Fecha de entrega: <strong><?= date('d/m/Y', strtotime($pedido['fecha_entrega'])) ?></strong>
    <?php if ($pedido['ventana_entrega']): ?> · <?= $pedido['ventana_entrega'] ?><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if ($pedido['notas']): ?>
  <div style="margin-top:8px;font-size:.8rem;color:#6B7280;background:#F9FAFB;padding:8px;border-radius:6px">
    📝 <?= htmlspecialchars($pedido['notas']) ?>
  </div>
  <?php endif; ?>
</div>

<!-- Productos por sucursal -->
<?php foreach ($porSucursal as $sucId => $group): ?>
<div class="card" style="margin-bottom:12px;padding:14px">
  <div style="font-weight:700;margin-bottom:10px;color:#374151;font-size:.875rem">
    📍 <?= htmlspecialchars($group['sucursal']['nombre'] ?? 'Sucursal') ?>
    <span style="font-size:.75rem;font-weight:400;color:#6B7280;display:block"><?= htmlspecialchars($group['sucursal']['direccion'] ?? '') ?></span>
  </div>
  <table style="width:100%;font-size:.8rem">
    <thead>
      <tr style="color:#6B7280;font-size:.7rem">
        <th style="text-align:left;padding-bottom:6px">Producto</th>
        <th style="text-align:center">Cantidad</th>
        <th style="text-align:right">Precio</th>
        <th style="text-align:right">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($group['items'] as $item): ?>
      <tr style="border-top:1px solid #F9FAFB">
        <td style="padding:6px 0"><?= htmlspecialchars($item['producto_nombre']) ?></td>
        <td style="text-align:center"><?= number_format($item['cantidad'],0) ?> kg</td>
        <td style="text-align:right;color:#6B7280">$<?= number_format($item['precio_unitario'],2) ?></td>
        <td style="text-align:right;font-weight:600">$<?= number_format($item['subtotal'],0,'.', ',') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3" style="text-align:right;padding-top:8px;font-weight:600;font-size:.8rem">Subtotal:</td>
        <td style="text-align:right;font-weight:700;color:#C8102E;padding-top:8px">$<?= number_format($group['subtotal'],0,'.', ',') ?></td>
      </tr>
    </tfoot>
  </table>
</div>
<?php endforeach; ?>

<!-- Total -->
<div class="card" style="margin-bottom:20px">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <span style="font-weight:600">Total del pedido</span>
    <span style="font-size:1.5rem;font-weight:800;color:#111827">$<?= number_format($pedido['total'],0,'.', ',') ?></span>
  </div>
  <?php if ($pedido['metodo_pago']): ?>
  <div style="font-size:.75rem;color:#6B7280;margin-top:4px">Método de pago: <?= ucfirst($pedido['metodo_pago']) ?></div>
  <?php endif; ?>
</div>

<!-- Acciones -->
<div style="display:flex;gap:10px;flex-wrap:wrap">
  <a href="<?= BASE_URL ?>pedido/index" class="btn btn-secondary" style="flex:1;justify-content:center">← Volver</a>
  <?php if (in_array($pedido['estado'], ['entregado','cancelado'])): ?>
  <button onclick="reordenar(<?= $pedido['id'] ?>)" class="btn btn-primary" style="flex:1;justify-content:center">🔄 Reordenar</button>
  <?php endif; ?>
  <?php if ($pedido['estado'] === 'entregado'): ?>
  <a href="<?= BASE_URL ?>facturacion/solicitar/<?= $pedido['id'] ?>" class="btn btn-secondary" style="flex:1;justify-content:center">🧾 Factura</a>
  <?php endif; ?>
</div>

<script>
function reordenar(pedidoId) {
  if (!confirm('¿Agregar este pedido al carrito?')) return;
  fetch('<?= BASE_URL ?>pedido/reordenar/' + pedidoId, { method: 'POST' })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        showToast('Pedido copiado al carrito', 'success');
        setTimeout(() => window.location = '<?= BASE_URL ?>carrito/index', 900);
      } else {
        showToast(d.error || 'Error al reordenar', 'error');
      }
    });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
