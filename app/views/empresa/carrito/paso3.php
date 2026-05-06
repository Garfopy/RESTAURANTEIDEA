<?php
// Vista: Paso 3 — Resumen y confirmación del pedido
$metaSaved  = $meta ?? [];
$comprador  = $comprador ?? [];
$empresa    = $empresa ?? [];
$metaSaved['tipo_entrega'] = $metaSaved['tipo_entrega'] ?? 'pickup';
?>
<!-- Pasos -->
<div style="display:flex;align-items:center;gap:0;margin-bottom:24px;font-size:.8rem">
  <?php
  $pasos = ['1'=>'Productos','2'=>'Resumen','3'=>'Confirmado'];
  foreach ($pasos as $num => $label):
    $activo = $num === '2';
    $hecho  = $num < '2';
  ?>
  <div style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:<?= $activo ? 'var(--color-primary)' : ($hecho ? '#D1FAE5' : '#E5E7EB') ?>;color:<?= $activo ? '#fff' : ($hecho ? '#065F46' : '#9CA3AF') ?>;<?= $num === '1' ? 'border-radius:8px 0 0 8px' : ($num === '3' ? 'border-radius:0 8px 8px 0' : '') ?>">
    <span style="font-weight:700"><?= $hecho ? '✓' : $num ?></span>
    <?= $label ?>
  </div>
  <?php if ($num < '3'): ?>
  <div style="width:0;height:0;border-top:18px solid transparent;border-bottom:18px solid transparent;border-left:10px solid <?= $activo ? 'var(--color-primary)' : ($hecho ? '#D1FAE5' : '#E5E7EB') ?>"></div>
  <?php endif; ?>
  <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">
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
  </div>

  <!-- Panel de confirmación -->
  <form method="POST" action="<?= BASE_URL ?>carrito/confirmar" id="form-pedido">
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:20px">
      <h3 style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:16px">Datos del pedido</h3>

      <!-- Fecha entrega -->
      <div style="margin-bottom:14px">
        <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">Fecha de entrega *</label>
        <input type="date" name="fecha_entrega"
               value="<?= htmlspecialchars($metaSaved['fecha_entrega'] ?? '') ?>"
               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
               required
               style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
      </div>

      <!-- Método de pago -->
      <div style="margin-bottom:14px">
        <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">Método de pago *</label>
        <select name="metodo_pago" required style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
          <?php foreach (['transferencia'=>'Transferencia bancaria','tarjeta'=>'Tarjeta de crédito','credito'=>'Crédito empresarial'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= ($metaSaved['metodo_pago'] ?? 'transferencia') === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Tipo de entrega -->
      <div style="margin-bottom:14px">
        <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:6px">Tipo de entrega *</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <?php $teActual = $metaSaved['tipo_entrega'] ?? 'pickup'; ?>
          <label id="card-pickup" style="cursor:pointer;border:2px solid <?= $teActual==='pickup' ? 'var(--color-primary)' : '#E5E7EB' ?>;border-radius:10px;padding:12px 10px;text-align:center;background:<?= $teActual==='pickup' ? '#FFF5F5' : '#fff' ?>;transition:all .15s">
            <input type="radio" name="tipo_entrega" id="te_pickup" value="pickup" <?= $teActual==='pickup' ? 'checked' : '' ?> style="display:none">
            <div style="font-size:1.4rem;margin-bottom:4px">🏭</div>
            <div style="font-size:.8rem;font-weight:700;color:#111827">Recoger en bodega</div>
            <div style="font-size:.72rem;color:#6B7280;margin-top:2px">Sin costo de envío</div>
          </label>
          <label id="card-repartidor" style="cursor:pointer;border:2px solid <?= $teActual==='repartidor' ? 'var(--color-primary)' : '#E5E7EB' ?>;border-radius:10px;padding:12px 10px;text-align:center;background:<?= $teActual==='repartidor' ? '#FFF5F5' : '#fff' ?>;transition:all .15s">
            <input type="radio" name="tipo_entrega" id="te_repartidor" value="repartidor" <?= $teActual==='repartidor' ? 'checked' : '' ?> style="display:none">
            <div style="font-size:1.4rem;margin-bottom:4px">🚚</div>
            <div style="font-size:.8rem;font-weight:700;color:#111827">Envío a domicilio</div>
            <div style="font-size:.72rem;color:#6B7280;margin-top:2px">La empresa asigna costo</div>
          </label>
        </div>
      </div>

      <!-- Bloque: Pickup — dirección de la empresa -->
      <div id="bloque-pickup" style="margin-bottom:14px;<?= $teActual!=='pickup' ? 'display:none' : '' ?>">
        <div style="background:#F0FDF4;border:1px solid #A7F3D0;border-radius:8px;padding:12px">
          <div style="font-size:.75rem;font-weight:700;color:#065F46;margin-bottom:4px">PUNTO DE RETIRO</div>
          <?php if (!empty($empresa['direccion_fiscal'])): ?>
          <div style="font-size:.85rem;color:#064E3B"><?= htmlspecialchars($empresa['direccion_fiscal']) ?></div>
          <?php else: ?>
          <div style="font-size:.85rem;color:#6B7280">La empresa confirmará el punto de retiro al aprobar el pedido.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Bloque: Repartidor — dirección del comprador -->
      <div id="bloque-direccion" style="margin-bottom:14px;<?= $teActual!=='repartidor' ? 'display:none' : '' ?>">
        <?php $tieneDireccion = !empty($comprador['direccion_entrega']); ?>
        <?php if ($tieneDireccion): ?>
        <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:10px 12px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
          <div>
            <div style="font-size:.75rem;font-weight:700;color:#1E40AF;margin-bottom:2px">DIRECCIÓN GUARDADA EN TU PERFIL</div>
            <div style="font-size:.85rem;color:#1D4ED8"><?= htmlspecialchars($comprador['direccion_entrega']) ?></div>
            <?php if (!empty($comprador['referencia_entrega'])): ?>
            <div style="font-size:.78rem;color:#3B82F6;margin-top:2px"><?= htmlspecialchars($comprador['referencia_entrega']) ?></div>
            <?php endif; ?>
          </div>
          <button type="button" onclick="toggleEditDireccion()" style="font-size:.75rem;color:#1D4ED8;background:none;border:1px solid #93C5FD;border-radius:6px;padding:4px 8px;cursor:pointer;white-space:nowrap">Cambiar</button>
        </div>
        <!-- Campos ocultos con dirección del perfil (se envían por defecto) -->
        <input type="hidden" name="direccion_entrega" id="hidden-dir" value="<?= htmlspecialchars($comprador['direccion_entrega'] ?? '') ?>">
        <input type="hidden" name="referencia_entrega" id="hidden-ref" value="<?= htmlspecialchars($comprador['referencia_entrega'] ?? '') ?>">
        <!-- Formulario de edición (oculto) -->
        <div id="edit-direccion" style="display:none">
        <?php endif; ?>
          <div style="margin-bottom:8px">
            <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:3px">Dirección de entrega *</label>
            <textarea name="<?= $tieneDireccion ? 'direccion_entrega_edit' : 'direccion_entrega' ?>" id="input-dir"
                      rows="2" placeholder="Calle, colonia, municipio..."
                      style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;resize:none"
                      <?= (!$tieneDireccion && $teActual==='repartidor') ? 'required' : '' ?>><?= htmlspecialchars($comprador['direccion_entrega'] ?? '') ?></textarea>
          </div>
          <div>
            <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:3px">Referencia / número interior</label>
            <input type="text" name="<?= $tieneDireccion ? 'referencia_entrega_edit' : 'referencia_entrega' ?>" id="input-ref"
                   placeholder="Ej: Interior 3B, edificio azul..."
                   value="<?= htmlspecialchars($comprador['referencia_entrega'] ?? '') ?>"
                   style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem">
          </div>
        <?php if ($tieneDireccion): ?>
        </div>
        <?php endif; ?>
        <?php if (!$tieneDireccion): ?>
        <div style="font-size:.75rem;color:#6B7280;margin-top:6px">
          Puedes guardar esta dirección en tu <a href="<?= BASE_URL ?>cuenta/perfil" target="_blank" style="color:var(--color-primary)">perfil</a> para futuros pedidos.
        </div>
        <?php endif; ?>
      </div>

      <!-- Notas -->
      <div style="margin-bottom:18px">
        <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">
          Notas adicionales
          <span style="font-size:.72rem;color:#9CA3AF;font-weight:400"> — instrucciones especiales, cortes específicos, etc.</span>
        </label>
        <textarea name="notas" rows="3" placeholder="Ej: Entregar antes del mediodía, pedir al guardia que avise..."
                  style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;resize:vertical"><?= htmlspecialchars($metaSaved['notas'] ?? '') ?></textarea>
      </div>

      <!-- Total -->
      <div style="background:#F9FAFB;border-radius:8px;padding:14px;margin-bottom:16px;text-align:center">
        <div style="font-size:.8rem;color:#6B7280;margin-bottom:4px">Total del pedido</div>
        <div style="font-size:1.8rem;font-weight:800;color:var(--color-primary)">$<?= number_format($total, 2) ?></div>
        <div style="font-size:.72rem;color:#9CA3AF;margin-top:2px">El proveedor puede ajustar precios al aprobar tu pedido</div>
      </div>

      <div style="display:flex;flex-direction:column;gap:8px">
        <button type="submit" style="padding:12px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:700;font-size:.9rem;cursor:pointer;width:100%">
          Confirmar pedido
        </button>
        <a href="<?= BASE_URL ?>carrito/index" style="text-align:center;padding:10px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
          ← Volver al carrito
        </a>
      </div>
    </div>
  </form>
</div>

<script>
(function () {
  var primary = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#DC2626';

  function actualizarCards() {
    var val = document.querySelector('[name="tipo_entrega"]:checked')?.value;
    ['pickup','repartidor'].forEach(function(v) {
      var card = document.getElementById('card-' + v);
      if (!card) return;
      var sel = (val === v);
      card.style.borderColor  = sel ? primary : '#E5E7EB';
      card.style.background   = sel ? '#FFF5F5' : '#fff';
    });
    document.getElementById('bloque-pickup').style.display     = (val === 'pickup')     ? '' : 'none';
    document.getElementById('bloque-direccion').style.display  = (val === 'repartidor') ? '' : 'none';
    // Gestión del required
    var inputDir = document.getElementById('input-dir');
    if (inputDir && inputDir.closest('[name="direccion_entrega"]')) {
      inputDir.required = (val === 'repartidor');
    }
  }

  document.querySelectorAll('[name="tipo_entrega"]').forEach(function(r) {
    r.addEventListener('change', actualizarCards);
    r.closest('label').addEventListener('click', function() {
      r.checked = true;
      actualizarCards();
    });
  });

  actualizarCards();

  window.toggleEditDireccion = function() {
    var ed  = document.getElementById('edit-direccion');
    var hd  = document.getElementById('hidden-dir');
    var hr  = document.getElementById('hidden-ref');
    var inp = document.getElementById('input-dir');
    var inr = document.getElementById('input-ref');
    if (!ed) return;
    var visible = ed.style.display !== 'none';
    ed.style.display = visible ? 'none' : '';
    if (!visible) {
      // Al mostrar el formulario, desactivar los hidden para evitar doble envío
      hd.disabled = true;
      hr.disabled = true;
      inp.name = 'direccion_entrega';
      inr.name = 'referencia_entrega';
    } else {
      hd.disabled = false;
      hr.disabled = false;
      inp.name = 'direccion_entrega_edit';
      inr.name = 'referencia_entrega_edit';
    }
  };
})();
</script>
