<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
?>
<div class="checkout-steps">
  <div class="step done"><div class="step-number">✓</div><span class="hide-mobile">Carrito</span></div>
  <div class="step-line"></div>
  <div class="step done"><div class="step-number">✓</div><span class="hide-mobile">Entrega</span></div>
  <div class="step-line"></div>
  <div class="step active"><div class="step-number">3</div><span class="hide-mobile">Resumen</span></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-number">4</div><span class="hide-mobile">Confirmación</span></div>
</div>

<h1 style="font-size:1.1rem;font-weight:700;margin-bottom:20px">Resumen del pedido</h1>

<?php foreach ($porSucursal as $sucId => $group): ?>
<div class="card" style="margin-bottom:12px;padding:14px">
  <div style="font-weight:700;margin-bottom:10px;color:#374151">
    📍 <?= htmlspecialchars($group['sucursal']['nombre'] ?? 'Sucursal') ?>
  </div>
  <table style="width:100%;font-size:.875rem">
    <thead><tr style="color:#6B7280;font-size:.75rem">
      <th style="text-align:left;padding-bottom:6px">Producto</th>
      <th style="text-align:center">Cantidad</th>
      <th style="text-align:right">Precio</th>
      <th style="text-align:right">Subtotal</th>
    </tr></thead>
    <tbody>
      <?php foreach ($group['items'] as $item): ?>
      <tr>
        <td style="padding:4px 0"><?= htmlspecialchars($item['producto']['nombre']) ?></td>
        <td style="text-align:center"><?= number_format($item['cantidad'],0) ?> kg</td>
        <td style="text-align:right">$<?= number_format($item['precio'],2) ?></td>
        <td style="text-align:right;font-weight:600">$<?= number_format($item['subtotal'],0,'.', ',') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="border-top:1px solid #F3F4F6">
        <td colspan="3" style="text-align:right;padding-top:8px;font-weight:600">Subtotal <?= htmlspecialchars($group['sucursal']['nombre'] ?? '') ?>:</td>
        <td style="text-align:right;font-weight:700;color:#C8102E;padding-top:8px">$<?= number_format($group['subtotal'],0,'.', ',') ?></td>
      </tr>
    </tfoot>
  </table>
</div>
<?php endforeach; ?>

<!-- Total + delivery info -->
<div class="card" style="margin-bottom:20px">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <span style="font-size:1rem;font-weight:600">Total del pedido</span>
    <span style="font-size:1.5rem;font-weight:800;color:#111827">$<?= number_format($total,0,'.', ',') ?></span>
  </div>
  <div style="font-size:.75rem;color:#6B7280;margin-top:4px">Precios incluyen IVA</div>
  <?php if ($fechaEntrega): ?>
  <div style="margin-top:12px;padding-top:12px;border-top:1px solid #F3F4F6;font-size:.875rem;color:#374151">
    📅 Entrega: <strong><?= $fechaEntrega ?></strong> · <?= $ventana ?>
  </div>
  <?php endif; ?>
</div>

<div style="display:flex;gap:10px">
  <a href="<?= BASE_URL ?>carrito/entrega" class="btn btn-secondary" style="flex:1;justify-content:center">← Volver</a>
  <form method="POST" action="<?= BASE_URL ?>carrito/procesarPedido" style="flex:2">
    <button type="submit" class="btn btn-primary btn-block">Confirmar pedido →</button>
  </form>
</div>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
