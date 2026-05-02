<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
$pedidoConfirmado = !empty($pedido);
?>
<div class="checkout-steps">
  <div class="step done"><div class="step-number">✓</div><span class="hide-mobile">Carrito</span></div>
  <div class="step-line"></div>
  <div class="step done"><div class="step-number">✓</div><span class="hide-mobile">Entrega</span></div>
  <div class="step-line"></div>
  <div class="step done"><div class="step-number">✓</div><span class="hide-mobile">Resumen</span></div>
  <div class="step-line"></div>
  <div class="step <?= $pedidoConfirmado ? 'done' : 'active' ?>">
    <div class="step-number"><?= $pedidoConfirmado ? '✓' : '4' ?></div>
    <span class="hide-mobile">Confirmación</span>
  </div>
</div>

<?php if ($pedidoConfirmado): ?>
<!-- Confirmed state -->
<div style="text-align:center;padding:40px 20px">
  <div style="width:72px;height:72px;border-radius:50%;background:#D1FAE5;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
    <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="#065F46"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
  </div>
  <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:8px">¡Pedido confirmado!</h1>
  <p style="color:#6B7280;font-size:.875rem;margin-bottom:4px">Tu pedido #<strong><?= $pedido['folio'] ?></strong> ha sido recibido.</p>
  <p style="color:#6B7280;font-size:.875rem">Te enviaremos una notificación cuando el pedido esté en camino.</p>
  <div style="margin-top:24px;padding:16px;background:#F9FAFB;border-radius:10px;display:inline-block;min-width:240px">
    <div style="font-size:.75rem;color:#6B7280">Fecha de entrega estimada</div>
    <div style="font-weight:700"><?= $pedido['fecha_entrega'] ?? '—' ?></div>
    <?php if ($pedido['ventana_entrega']): ?>
    <div style="font-size:.8rem;color:#6B7280"><?= $pedido['ventana_entrega'] ?></div>
    <?php endif; ?>
  </div>
  <div style="display:flex;gap:10px;justify-content:center;margin-top:24px;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>pedido/detalle/<?= $pedido['id'] ?>" class="btn btn-primary">Ver pedido</a>
    <a href="<?= BASE_URL ?>producto/catalogo" class="btn btn-secondary">Seguir comprando</a>
  </div>
</div>
<?php else: ?>
<!-- Confirmation form (step 4 preview) -->
<div class="card" style="max-width:480px;margin:0 auto">
  <div style="font-weight:700;font-size:1rem;margin-bottom:16px">Confirmación del pedido</div>
  <?php if (!empty($entrega['fecha'])): ?>
  <div style="padding:12px;background:#F9FAFB;border-radius:8px;margin-bottom:12px;font-size:.875rem">
    <div style="color:#6B7280;font-size:.75rem;margin-bottom:2px">Fecha de entrega</div>
    <div style="font-weight:600"><?= $entrega['fecha'] ?></div>
    <div style="font-size:.8rem;color:#6B7280"><?= $entrega['ventana'] ?? '' ?></div>
  </div>
  <?php endif; ?>
  <?php if (!empty($checkout)): ?>
  <div style="font-weight:700;font-size:1.25rem;margin:16px 0;text-align:right">
    Total a pagar: $<?= number_format($checkout['total'],2) ?>
  </div>
  <?php endif; ?>
  <form method="POST" action="<?= BASE_URL ?>carrito/procesarPedido">
    <button type="submit" class="btn btn-primary btn-block btn-lg">Confirmar pedido</button>
  </form>
  <a href="<?= BASE_URL ?>carrito/resumen" class="btn btn-secondary btn-block" style="margin-top:8px">← Volver</a>
</div>
<?php endif; ?>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
