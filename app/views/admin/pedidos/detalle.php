<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
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
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div>
    <a href="<?= BASE_URL ?>pedido/index" style="font-size:.875rem;color:#6B7280;text-decoration:none">← Pedidos</a>
    <h1 style="font-size:1.25rem;font-weight:800;margin:4px 0 0">#<?= $pedido['folio'] ?></h1>
  </div>
  <span style="background:<?= $bgColor ?>;color:<?= $textColor ?>;font-size:.8rem;font-weight:700;padding:5px 14px;border-radius:20px"><?= $estadoLabel ?></span>
</div>

<!-- Header info -->
<div class="grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
  <div class="card">
    <div style="font-size:.75rem;color:#6B7280;margin-bottom:4px">Cliente</div>
    <div style="font-weight:700"><?= htmlspecialchars($pedido['empresa_nombre']) ?></div>
    <div style="font-size:.8rem;color:#6B7280"><?= htmlspecialchars($pedido['comprador_nombre'] ?? '') ?></div>
  </div>
  <div class="card">
    <div style="font-size:.75rem;color:#6B7280;margin-bottom:4px">Fecha pedido</div>
    <div style="font-weight:700"><?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></div>
    <?php if ($pedido['fecha_entrega']): ?>
    <div style="font-size:.8rem;color:#6B7280">Entrega: <?= date('d/m/Y', strtotime($pedido['fecha_entrega'])) ?></div>
    <?php endif; ?>
  </div>
</div>

<!-- Cambiar estado -->
<div class="card" style="margin-bottom:16px">
  <div style="font-weight:700;font-size:.875rem;margin-bottom:10px">Cambiar estado</div>
  <form method="POST" action="<?= BASE_URL ?>pedido/cambiarEstado/<?= $pedido['id'] ?>" style="display:flex;gap:8px;flex-wrap:wrap">
    <?php foreach (['pendiente','confirmado','en_preparacion','en_ruta','entregado','cancelado'] as $st): ?>
    <button type="submit" name="estado" value="<?= $st ?>"
            class="btn btn-sm <?= $pedido['estado'] === $st ? 'btn-primary' : 'btn-secondary' ?>"
            style="white-space:nowrap">
      <?= ucfirst(str_replace('_',' ',$st)) ?>
    </button>
    <?php endforeach; ?>
  </form>
</div>

<!-- Productos por sucursal -->
<?php foreach ($porSucursal as $sucId => $group): ?>
<div class="card" style="margin-bottom:12px">
  <div style="font-weight:700;margin-bottom:10px;color:#374151">
    📍 <?= htmlspecialchars($group['sucursal']['nombre'] ?? 'Sucursal') ?>
    <span style="font-size:.75rem;font-weight:400;color:#6B7280"> — <?= htmlspecialchars($group['sucursal']['direccion'] ?? '') ?></span>
  </div>
  <table style="width:100%;font-size:.875rem">
    <thead>
      <tr style="color:#6B7280;font-size:.75rem">
        <th style="text-align:left;padding-bottom:6px">Producto</th>
        <th style="text-align:center">Cantidad</th>
        <th style="text-align:right">Precio</th>
        <th style="text-align:right">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($group['items'] as $item): ?>
      <tr style="border-top:1px solid #F3F4F6">
        <td style="padding:6px 0"><?= htmlspecialchars($item['producto_nombre']) ?></td>
        <td style="text-align:center"><?= number_format($item['cantidad'],0) ?> kg</td>
        <td style="text-align:right;color:#6B7280">$<?= number_format($item['precio_unitario'],2) ?></td>
        <td style="text-align:right;font-weight:600">$<?= number_format($item['subtotal'],0,'.', ',') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endforeach; ?>

<!-- Total -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <span style="font-weight:600">Total del pedido</span>
    <span style="font-size:1.5rem;font-weight:800">$<?= number_format($pedido['total'],0,'.', ',') ?></span>
  </div>
  <?php if ($pedido['metodo_pago']): ?>
  <div style="font-size:.75rem;color:#6B7280;margin-top:4px">Método: <?= ucfirst($pedido['metodo_pago']) ?></div>
  <?php endif; ?>
  <?php if ($pedido['notas']): ?>
  <div style="margin-top:8px;font-size:.8rem;color:#6B7280;background:#F9FAFB;padding:8px;border-radius:6px">
    📝 <?= htmlspecialchars($pedido['notas']) ?>
  </div>
  <?php endif; ?>
</div>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
