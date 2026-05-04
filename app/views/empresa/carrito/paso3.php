<?php
// Vista: Paso 3 — Resumen y confirmación del pedido
$metaSaved = $meta ?? [];
?>
<!-- Pasos -->
<div style="display:flex;align-items:center;gap:0;margin-bottom:24px;font-size:.8rem">
  <?php
  $pasos = ['1'=>'Productos','2'=>'Sucursales','3'=>'Resumen','4'=>'Confirmado'];
  foreach ($pasos as $num => $label):
    $activo = $num === '3';
    $hecho  = $num < '3';
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

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">
  <!-- Productos -->
  <div>
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:16px">
      <div style="padding:14px 16px;border-bottom:1px solid #F3F4F6;font-weight:700;font-size:.9rem;color:#111827">
        Detalle del pedido
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:.875rem">
        <thead>
          <tr style="background:#F9FAFB">
            <th style="padding:10px 16px;text-align:left;color:#6B7280;font-weight:600">Producto</th>
            <th style="padding:10px;text-align:center;color:#6B7280;font-weight:600">Cantidad</th>
            <th style="padding:10px;text-align:right;color:#6B7280;font-weight:600">Precio</th>
            <th style="padding:10px 16px;text-align:right;color:#6B7280;font-weight:600">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr style="border-top:1px solid #F3F4F6">
            <td style="padding:10px 16px;font-weight:600;color:#111827">
              <?= htmlspecialchars($item['nombre']) ?>
              <div style="font-size:.75rem;color:#9CA3AF;font-weight:400"><?= $item['presentacion'] ?></div>
            </td>
            <td style="padding:10px;text-align:center;color:#374151"><?= number_format($item['cantidad'], 2) ?></td>
            <td style="padding:10px;text-align:right;color:#374151">$<?= number_format($item['precio'], 2) ?></td>
            <td style="padding:10px 16px;text-align:right;font-weight:700;color:#111827">$<?= number_format($item['subtotal'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="border-top:2px solid #E5E7EB;background:#F9FAFB">
            <td colspan="3" style="padding:12px 16px;text-align:right;font-weight:700;color:#374151">TOTAL</td>
            <td style="padding:12px 16px;text-align:right;font-size:1.1rem;font-weight:800;color:var(--color-primary)">
              $<?= number_format($total, 2) ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Distribución por sucursal -->
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:14px 16px">
      <div style="font-weight:700;font-size:.9rem;color:#111827;margin-bottom:10px">Distribución por sucursal</div>
      <?php
      // Build readable map: sucursal_name => [producto => qty]
      foreach ($items as $prodId => $item):
        foreach ($distribucion[$prodId] ?? [] as $sucId => $qty):
          if ($qty <= 0) continue;
          $sucNombre = $sucursalesArr[$sucId] ?? "Sucursal #$sucId";
      ?>
      <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #F3F4F6;font-size:.85rem">
        <span style="color:#374151"><?= htmlspecialchars($item['nombre']) ?> → <?= htmlspecialchars($sucNombre) ?></span>
        <span style="font-weight:600;color:#111827"><?= number_format($qty, 2) ?> <?= $item['presentacion'] ?></span>
      </div>
      <?php endforeach; endforeach; ?>
    </div>
  </div>

  <!-- Panel de confirmación -->
  <form method="POST" action="<?= BASE_URL ?>carrito/confirmar">
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:20px">
      <h3 style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:16px">Datos del pedido</h3>

      <div style="margin-bottom:14px">
        <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">Fecha de entrega *</label>
        <input type="date" name="fecha_entrega"
               value="<?= htmlspecialchars($metaSaved['fecha_entrega'] ?? '') ?>"
               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
               required
               style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
      </div>

      <div style="margin-bottom:14px">
        <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">Método de pago *</label>
        <select name="metodo_pago" required style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
          <?php foreach (['transferencia'=>'Transferencia bancaria','tarjeta'=>'Tarjeta de crédito','credito'=>'Crédito empresarial'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= ($metaSaved['metodo_pago'] ?? 'transferencia') === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="margin-bottom:18px">
        <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">Notas adicionales</label>
        <textarea name="notas" rows="3" placeholder="Instrucciones especiales de entrega..."
                  style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;resize:vertical"><?= htmlspecialchars($metaSaved['notas'] ?? '') ?></textarea>
      </div>

      <div style="background:#F9FAFB;border-radius:8px;padding:14px;margin-bottom:16px;text-align:center">
        <div style="font-size:.8rem;color:#6B7280;margin-bottom:4px">Total del pedido</div>
        <div style="font-size:1.8rem;font-weight:800;color:var(--color-primary)">$<?= number_format($total, 2) ?></div>
      </div>

      <div style="display:flex;flex-direction:column;gap:8px">
        <button type="submit" style="padding:12px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:700;font-size:.9rem;cursor:pointer;width:100%">
          Confirmar pedido
        </button>
        <a href="<?= BASE_URL ?>carrito/sucursales" style="text-align:center;padding:10px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
          ← Atrás
        </a>
      </div>
    </div>
  </form>
</div>
