<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
?>
<!-- Checkout steps -->
<div class="checkout-steps">
  <div class="step done"><div class="step-number">✓</div><span class="hide-mobile">Carrito</span></div>
  <div class="step-line"></div>
  <div class="step active"><div class="step-number">2</div><span class="hide-mobile">Entrega</span></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-number">3</div><span class="hide-mobile">Resumen</span></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-number">4</div><span class="hide-mobile">Confirmación</span></div>
</div>

<h1 style="font-size:1.1rem;font-weight:700;margin-bottom:20px">Direcciones de entrega</h1>

<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:24px">
  <?php
  $colorMap = ['#10B981','#3B82F6','#F59E0B','#8B5CF6','#EC4899'];
  foreach ($sucursales as $i => $s): $color = $colorMap[$i % count($colorMap)];
  ?>
  <div style="background:#fff;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 3px rgba(0,0,0,.08)">
    <div style="width:36px;height:36px;border-radius:8px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:1rem;flex-shrink:0">
      <?= strtoupper(substr($s['nombre'],0,1)) ?>
    </div>
    <div style="flex:1">
      <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($s['nombre']) ?></div>
      <div style="font-size:.75rem;color:#6B7280"><?= htmlspecialchars($s['direccion']) ?></div>
      <?php if ($s['contacto_nombre']): ?>
      <div style="font-size:.75rem;color:#9CA3AF">Contacto: <?= $s['contacto_nombre'] ?> · <?= $s['contacto_telefono'] ?></div>
      <?php endif; ?>
    </div>
    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#9CA3AF"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
  </div>
  <?php endforeach; ?>
</div>

<form method="POST" action="<?= BASE_URL ?>carrito/confirmar">
  <div class="card" style="margin-bottom:16px">
    <div style="font-weight:700;margin-bottom:14px">Programación de entrega</div>
    <div style="display:grid;gap:12px">
      <div>
        <label class="form-label">Fecha de entrega</label>
        <input type="date" name="fecha_entrega" class="form-control" min="<?= date('Y-m-d') ?>" required>
      </div>
      <div>
        <label class="form-label">Horario de entrega</label>
        <select name="ventana_entrega" class="form-control form-select">
          <option value="08:00-12:00">8:00 am — 12:00 pm</option>
          <option value="12:00-16:00">12:00 pm — 4:00 pm</option>
          <option value="16:00-20:00">4:00 pm — 8:00 pm</option>
        </select>
      </div>
      <div>
        <label class="form-label">Método de pago</label>
        <select name="metodo_pago" class="form-control form-select">
          <option value="transferencia">Transferencia bancaria</option>
          <option value="tarjeta">Tarjeta</option>
          <option value="credito">Crédito CarniHub</option>
          <option value="efectivo">Efectivo</option>
        </select>
      </div>
      <div>
        <label class="form-label">Notas para el pedido (opcional)</label>
        <textarea name="notas" class="form-control" rows="3" placeholder="Indicaciones especiales, referencias, alguna nota para el repartidor..."></textarea>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:10px">
    <a href="<?= BASE_URL ?>carrito/index" class="btn btn-secondary" style="flex:1;justify-content:center">← Volver</a>
    <button type="submit" class="btn btn-primary" style="flex:2;justify-content:center">Continuar →</button>
  </div>
</form>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
