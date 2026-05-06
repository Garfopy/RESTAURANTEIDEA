<?php
// Vista: Paso 3 — Resumen y confirmación del pedido
$metaSaved  = $meta ?? [];
$comprador  = $comprador ?? [];
$empresa    = $empresa ?? [];
$metaSaved['tipo_entrega'] = $metaSaved['tipo_entrega'] ?? 'pickup';

// Sucursales del comprador (para selector de entrega)
$sucursalModel  = new SucursalModel();
$misSucursales  = $sucursalModel->getByComprador($comprador['id'] ?? 0);

// Google Maps key
$configModel = new ConfigModel();
$gmKey = $configModel->get('google_maps_key', '');
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
          <?php foreach (['transferencia'=>'Transferencia bancaria','efectivo'=>'Efectivo en la empresa'] as $v => $l): ?>
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

      <!-- Bloque: Repartidor — dirección del comprador o sucursal -->
      <div id="bloque-direccion" style="margin-bottom:14px;<?= $teActual!=='repartidor' ? 'display:none' : '' ?>">
        <?php
        $tieneDireccion = !empty($comprador['direccion_entrega']);
        $tieneSucursales = !empty($misSucursales);
        ?>

        <?php if ($tieneSucursales): ?>
        <!-- Selector de sucursal registrada -->
        <div style="margin-bottom:10px">
          <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">Entregar en *</label>
          <div style="display:flex;flex-direction:column;gap:6px" id="opciones-destino">
            <!-- Opción: mis sucursales -->
            <?php foreach ($misSucursales as $suc): ?>
            <label style="cursor:pointer;border:1px solid #E5E7EB;border-radius:8px;padding:10px 12px;display:flex;align-items:flex-start;gap:10px;transition:border-color .15s" class="card-sucursal">
              <input type="radio" name="sucursal_id" value="<?= $suc['id'] ?>"
                     data-dir="<?= htmlspecialchars($suc['direccion']) ?>"
                     data-lat="<?= (float)($suc['lat'] ?? 0) ?>"
                     data-lng="<?= (float)($suc['lng'] ?? 0) ?>"
                     style="margin-top:2px;accent-color:var(--color-primary)">
              <div>
                <div style="font-size:.85rem;font-weight:700;color:#111827"><?= htmlspecialchars($suc['nombre']) ?></div>
                <div style="font-size:.78rem;color:#6B7280"><?= htmlspecialchars($suc['direccion']) ?></div>
                <?php if (!empty($suc['responsable'])): ?>
                <div style="font-size:.75rem;color:#9CA3AF">Resp: <?= htmlspecialchars($suc['responsable']) ?></div>
                <?php endif; ?>
              </div>
            </label>
            <?php endforeach; ?>
            <!-- Opción: otra dirección -->
            <label style="cursor:pointer;border:1px solid #E5E7EB;border-radius:8px;padding:10px 12px;display:flex;align-items:center;gap:10px" class="card-sucursal">
              <input type="radio" name="sucursal_id" value="" id="radio-otra-dir"
                     style="accent-color:var(--color-primary)" <?= !$tieneDireccion ? 'checked' : '' ?>>
              <div>
                <div style="font-size:.85rem;font-weight:700;color:#111827">Otra dirección</div>
                <div style="font-size:.78rem;color:#6B7280">Ingresar dirección manual</div>
              </div>
            </label>
          </div>
        </div>

        <!-- Mapa de la sucursal seleccionada -->
        <?php if ($gmKey): ?>
        <div id="mapa-sucursal-container" style="border-radius:8px;overflow:hidden;height:160px;margin-bottom:10px;border:1px solid #E5E7EB;display:none">
          <div id="mapa-sucursal" style="width:100%;height:100%"></div>
        </div>
        <?php endif; ?>

        <!-- Campos de dirección manual (se muestra solo si elige "Otra dirección") -->
        <div id="bloque-dir-manual" style="<?= $tieneSucursales ? 'display:none' : '' ?>">
        <?php endif; ?>

        <?php if (!$tieneSucursales): ?>
        <!-- Sin sucursales: muestra dirección del perfil o campos manuales -->
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
        <input type="hidden" name="direccion_entrega" id="hidden-dir" value="<?= htmlspecialchars($comprador['direccion_entrega'] ?? '') ?>">
        <input type="hidden" name="referencia_entrega" id="hidden-ref" value="<?= htmlspecialchars($comprador['referencia_entrega'] ?? '') ?>">
        <div id="edit-direccion" style="display:none">
        <?php endif; ?>
        <?php endif; ?>

          <!-- Campos manuales de dirección -->
          <div style="margin-bottom:8px">
            <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:3px">Dirección de entrega *</label>
            <?php if ($gmKey && !$tieneSucursales): ?>
            <input type="text" id="input-dir-checkout"
                   name="<?= ($tieneDireccion && !$tieneSucursales) ? 'direccion_entrega_edit' : 'direccion_entrega' ?>"
                   id="input-dir"
                   placeholder="Escribe para buscar con Google Maps..."
                   value="<?= htmlspecialchars($comprador['direccion_entrega'] ?? '') ?>"
                   style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem"
                   autocomplete="off"
                   <?= (!$tieneDireccion && $teActual==='repartidor') ? 'required' : '' ?>>
            <?php else: ?>
            <textarea name="<?= ($tieneDireccion && !$tieneSucursales) ? 'direccion_entrega_edit' : 'direccion_entrega' ?>" id="input-dir"
                      rows="2" placeholder="Calle, colonia, municipio..."
                      style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;resize:none"
                      <?= (!$tieneDireccion && $teActual==='repartidor') ? 'required' : '' ?>><?= htmlspecialchars($comprador['direccion_entrega'] ?? '') ?></textarea>
            <?php endif; ?>
          </div>
          <div>
            <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:3px">Referencia / número interior</label>
            <input type="text" name="<?= ($tieneDireccion && !$tieneSucursales) ? 'referencia_entrega_edit' : 'referencia_entrega' ?>" id="input-ref"
                   placeholder="Ej: Interior 3B, edificio azul..."
                   value="<?= htmlspecialchars($comprador['referencia_entrega'] ?? '') ?>"
                   style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem">
          </div>
          <input type="hidden" name="lat_entrega" id="input-lat-checkout" value="<?= htmlspecialchars($comprador['lat_entrega'] ?? '') ?>">
          <input type="hidden" name="lng_entrega" id="input-lng-checkout" value="<?= htmlspecialchars($comprador['lng_entrega'] ?? '') ?>">

        <?php if (!$tieneSucursales && $tieneDireccion): ?>
        </div><!-- /edit-direccion -->
        <?php endif; ?>

        <?php if ($tieneSucursales): ?>
        </div><!-- /bloque-dir-manual -->
        <?php endif; ?>

        <?php if (!$tieneDireccion && !$tieneSucursales): ?>
        <div style="font-size:.75rem;color:#6B7280;margin-top:6px">
          Puedes guardar tu dirección en tu <a href="<?= BASE_URL ?>cuenta/perfil" target="_blank" style="color:var(--color-primary)">perfil</a> para futuros pedidos.
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
    document.getElementById('bloque-pickup').style.display    = (val === 'pickup')     ? '' : 'none';
    document.getElementById('bloque-direccion').style.display = (val === 'repartidor') ? '' : 'none';
  }

  document.querySelectorAll('[name="tipo_entrega"]').forEach(function(r) {
    r.addEventListener('change', actualizarCards);
    r.closest('label').addEventListener('click', function() { r.checked = true; actualizarCards(); });
  });

  actualizarCards();

  // ── Selector de sucursal ──────────────────────────────────────────
  var mapaSucursal = null, markerSucursal = null;

  document.querySelectorAll('[name="sucursal_id"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
      var dir = this.dataset.dir || '';
      var lat = parseFloat(this.dataset.lat || 0);
      var lng = parseFloat(this.dataset.lng || 0);
      var esOtra = this.id === 'radio-otra-dir' || this.value === '';

      // Mostrar/ocultar dirección manual
      var bloqueManual = document.getElementById('bloque-dir-manual');
      if (bloqueManual) bloqueManual.style.display = esOtra ? '' : 'none';

      // Estilo borde tarjetas sucursal
      document.querySelectorAll('.card-sucursal').forEach(function(c) {
        c.style.borderColor = '#E5E7EB';
        c.style.background = '#fff';
      });
      this.closest('.card-sucursal').style.borderColor = primary;
      this.closest('.card-sucursal').style.background = '#FFF5F5';

      // Mapa de sucursal seleccionada
      var mapaContainer = document.getElementById('mapa-sucursal-container');
      if (mapaContainer && !esOtra && lat && lng && typeof google !== 'undefined') {
        mapaContainer.style.display = '';
        var pos = { lat: lat, lng: lng };
        if (!mapaSucursal) {
          mapaSucursal = new google.maps.Map(document.getElementById('mapa-sucursal'), {
            center: pos, zoom: 15, mapTypeControl: false, streetViewControl: false
          });
          markerSucursal = new google.maps.Marker({ position: pos, map: mapaSucursal });
        } else {
          mapaSucursal.setCenter(pos);
          markerSucursal.setPosition(pos);
        }
      } else if (mapaContainer) {
        mapaContainer.style.display = 'none';
      }

      // Llenar lat/lng del checkout con los de la sucursal
      var latEl = document.getElementById('input-lat-checkout');
      var lngEl = document.getElementById('input-lng-checkout');
      if (latEl && lngEl) {
        latEl.value = esOtra ? '' : lat;
        lngEl.value = esOtra ? '' : lng;
      }
    });
  });

  // ── Editar dirección guardada ──────────────────────────────────────
  window.toggleEditDireccion = function() {
    var ed  = document.getElementById('edit-direccion');
    var hd  = document.getElementById('hidden-dir');
    var hr  = document.getElementById('hidden-ref');
    var inp = document.getElementById('input-dir');
    var inr = document.getElementById('input-ref');
    if (!ed || !hd) return;
    var visible = ed.style.display !== 'none';
    ed.style.display = visible ? 'none' : '';
    if (!visible) {
      hd.disabled = true;
      if (hr) hr.disabled = true;
      if (inp) inp.name = 'direccion_entrega';
      if (inr) inr.name = 'referencia_entrega';
    } else {
      hd.disabled = false;
      if (hr) hr.disabled = false;
      if (inp) inp.name = 'direccion_entrega_edit';
      if (inr) inr.name = 'referencia_entrega_edit';
    }
  };

  // ── Google Maps Autocomplete (checkout — dirección manual) ─────────
  window.initGoogleMapsCheckout = function() {
    var inputDir = document.getElementById('input-dir-checkout');
    if (!inputDir) return;
    var autocomplete = new google.maps.places.Autocomplete(inputDir, {
      componentRestrictions: { country: 'mx' },
      fields: ['geometry', 'formatted_address'],
    });
    autocomplete.addListener('place_changed', function() {
      var place = autocomplete.getPlace();
      if (!place.geometry) return;
      var pos = place.geometry.location;
      var latEl = document.getElementById('input-lat-checkout');
      var lngEl = document.getElementById('input-lng-checkout');
      if (latEl) latEl.value = pos.lat().toFixed(7);
      if (lngEl) lngEl.value = pos.lng().toFixed(7);
    });
  };
})();
</script>

<?php if ($gmKey): ?>
<script async defer
  src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($gmKey) ?>&libraries=places&callback=initGoogleMapsCheckout">
</script>
<?php endif; ?>
