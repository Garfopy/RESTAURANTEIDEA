<?php
// Vista: Paso 2 — Distribución por sucursal
$distGuardada = $distribucion ?? [];
?>
<!-- Pasos -->
<div style="display:flex;align-items:center;gap:0;margin-bottom:24px;font-size:.8rem">
  <?php
  $pasos = ['1'=>'Productos','2'=>'Sucursales','3'=>'Resumen','4'=>'Confirmado'];
  foreach ($pasos as $num => $label):
    $activo = $num === '2';
    $hecho  = $num === '1';
  ?>
  <div style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:<?= $activo ? 'var(--color-primary)' : ($hecho ? '#D1FAE5' : '#E5E7EB') ?>;color:<?= $activo ? '#fff' : ($hecho ? '#065F46' : '#9CA3AF') ?>;<?= $num === '1' ? 'border-radius:8px 0 0 8px' : ($num === '4' ? 'border-radius:0 8px 8px 0' : '') ?>">
    <span style="font-weight:700"><?= $hecho ? '✓' : $num ?></span>
    <?= $label ?>
  </div>
  <?php if ($num < '4'): ?>
  <div style="width:0;height:0;border-top:18px solid transparent;border-bottom:18px solid transparent;border-left:10px solid <?= $activo ? 'var(--color-primary)' : ($hecho ? '#D1FAE5' : '#E5E7EB') ?>"></div>
  <?php endif; ?>
  <?php endforeach; ?>
</div>

<p style="font-size:.875rem;color:#6B7280;margin-bottom:16px">
  Indica qué cantidad de cada producto debe entregarse en cada sucursal. Los totales deben coincidir exactamente.
</p>

<form method="POST" action="<?= BASE_URL ?>carrito/guardarSucursales" id="distForm">
  <?php foreach ($items as $prodId => $item): ?>
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:16px;margin-bottom:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <div>
        <span style="font-weight:700;font-size:.95rem;color:#111827"><?= htmlspecialchars($item['nombre']) ?></span>
        <span style="font-size:.8rem;color:#6B7280;margin-left:8px"><?= $item['presentacion'] ?></span>
      </div>
      <span style="font-size:.85rem;color:#374151">Total: <strong><?= number_format($item['cantidad'], 2) ?> <?= $item['presentacion'] ?></strong></span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">
      <?php foreach ($sucursales as $suc): ?>
      <?php $prevQty = $distGuardada[$prodId][$suc['id']] ?? ''; ?>
      <div>
        <label style="font-size:.75rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">
          <?= htmlspecialchars($suc['nombre']) ?>
          <span style="font-weight:400;color:#9CA3AF">— <?= htmlspecialchars(mb_strimwidth($suc['direccion'] ?? '', 0, 30, '…')) ?></span>
        </label>
        <input type="number"
               name="dist[<?= $prodId ?>][<?= $suc['id'] ?>]"
               class="qty-input prod-<?= $prodId ?>"
               data-total="<?= $item['cantidad'] ?>"
               data-prod="<?= $prodId ?>"
               value="<?= $prevQty ?>"
               min="0" step="0.5" placeholder="0"
               style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem"
               oninput="validarTotal(<?= $prodId ?>)">
      </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:10px;font-size:.8rem" id="total-status-<?= $prodId ?>">
      <span style="color:#6B7280">Asignado: </span>
      <span id="total-asignado-<?= $prodId ?>" style="font-weight:700;color:#374151">0</span>
      <span style="color:#6B7280"> de <?= number_format($item['cantidad'], 2) ?> <?= $item['presentacion'] ?></span>
    </div>
  </div>
  <?php endforeach; ?>

  <div style="display:flex;justify-content:space-between;gap:10px">
    <a href="<?= BASE_URL ?>carrito/index" style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
      ← Volver
    </a>
    <button type="submit" id="btnContinuar" style="padding:10px 24px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
      Ver resumen →
    </button>
  </div>
</form>

<script>
function validarTotal(prodId) {
  const inputs = document.querySelectorAll('.prod-' + prodId);
  let sum = 0;
  inputs.forEach(el => sum += parseFloat(el.value) || 0);
  const total = parseFloat(inputs[0]?.dataset.total || 0);
  const el    = document.getElementById('total-asignado-' + prodId);
  el.textContent = sum.toFixed(2);
  el.style.color  = Math.abs(sum - total) < 0.01 ? '#059669' : '#DC2626';
}

// Init from saved values
document.addEventListener('DOMContentLoaded', () => {
  <?php foreach (array_keys($items) as $prodId): ?>
  validarTotal(<?= $prodId ?>);
  <?php endforeach; ?>
});
</script>
