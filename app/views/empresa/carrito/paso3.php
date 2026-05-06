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

      <!-- ── Bloque: Envío a domicilio ────────────────────────────── -->
      <div id="bloque-direccion" style="margin-bottom:14px;<?= $teActual!=='repartidor' ? 'display:none' : '' ?>">

        <?php if (!empty($misSucursales)): ?>
        <!-- ── MODO MULTI-PARADA (tiene sucursales registradas) ──── -->
        <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:6px">
          Paradas de entrega *
          <span style="font-size:.72rem;font-weight:400;color:#9CA3AF"> — el repartidor visita cada parada</span>
        </label>

        <!-- Lista de paradas añadidas -->
        <div id="lista-paradas" style="display:flex;flex-direction:column;gap:6px;margin-bottom:8px"></div>

        <!-- Estado vacío -->
        <div id="paradas-empty" style="border:1.5px dashed #D1D5DB;border-radius:8px;padding:14px 12px;text-align:center;color:#9CA3AF;font-size:.8rem;margin-bottom:8px">
          Añade al menos una parada de entrega
        </div>

        <!-- Botones para añadir -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
          <div style="position:relative">
            <button type="button" id="btn-toggle-dropdown"
                    style="padding:7px 14px;background:#F3F4F6;border:1px solid #D1D5DB;border-radius:8px;font-size:.82rem;font-weight:600;color:#374151;cursor:pointer">
              + Añadir sucursal ▾
            </button>
            <!-- Dropdown de sucursales disponibles -->
            <div id="dropdown-sucursales"
                 style="display:none;position:absolute;top:100%;left:0;margin-top:4px;background:#fff;border:1px solid #E5E7EB;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.12);z-index:50;min-width:260px;overflow:hidden">
              <?php foreach ($misSucursales as $suc): ?>
              <div class="suc-option" data-id="<?= $suc['id'] ?>"
                   data-nombre="<?= htmlspecialchars($suc['nombre'], ENT_QUOTES) ?>"
                   data-dir="<?= htmlspecialchars($suc['direccion'], ENT_QUOTES) ?>"
                   data-lat="<?= (float)($suc['lat'] ?? 0) ?>"
                   data-lng="<?= (float)($suc['lng'] ?? 0) ?>"
                   style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #F3F4F6;font-size:.85rem">
                <div style="font-weight:700;color:#111827"><?= htmlspecialchars($suc['nombre']) ?></div>
                <div style="font-size:.75rem;color:#6B7280"><?= htmlspecialchars($suc['direccion']) ?></div>
              </div>
              <?php endforeach; ?>
              <div id="dropdown-vacio" style="display:none;padding:10px 14px;font-size:.8rem;color:#9CA3AF;text-align:center">
                Ya añadiste todas tus sucursales
              </div>
            </div>
          </div>

          <button type="button" id="btn-add-manual"
                  style="padding:7px 14px;background:#F3F4F6;border:1px solid #D1D5DB;border-radius:8px;font-size:.82rem;font-weight:600;color:#374151;cursor:pointer">
            + Otra dirección
          </button>
        </div>

        <!-- Formulario dirección manual (oculto por defecto) -->
        <div id="panel-dir-manual" style="display:none;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:12px;margin-bottom:8px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
            <span style="font-size:.8rem;font-weight:700;color:#374151">Otra dirección de entrega</span>
            <button type="button" id="btn-remove-manual"
                    style="background:none;border:none;color:#EF4444;font-size:.8rem;font-weight:700;cursor:pointer">× Quitar</button>
          </div>
          <?php if ($gmKey): ?>
          <input type="text" id="input-dir-checkout" name="direccion_entrega"
                 placeholder="Escribe para buscar con Google Maps..."
                 value="<?= htmlspecialchars($comprador['direccion_entrega'] ?? '') ?>"
                 autocomplete="off"
                 style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;box-sizing:border-box;margin-bottom:6px">
          <?php else: ?>
          <textarea name="direccion_entrega" id="input-dir-checkout" rows="2"
                    placeholder="Calle, colonia, municipio..."
                    style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;resize:none;box-sizing:border-box;margin-bottom:6px"><?= htmlspecialchars($comprador['direccion_entrega'] ?? '') ?></textarea>
          <?php endif; ?>
          <input type="text" name="referencia_entrega" id="input-ref-checkout"
                 placeholder="Ej: Interior 3B, portón negro..."
                 value="<?= htmlspecialchars($comprador['referencia_entrega'] ?? '') ?>"
                 style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;box-sizing:border-box">
          <input type="hidden" name="lat_entrega" id="input-lat-checkout" value="<?= htmlspecialchars($comprador['lat_entrega'] ?? '') ?>">
          <input type="hidden" name="lng_entrega" id="input-lng-checkout" value="<?= htmlspecialchars($comprador['lng_entrega'] ?? '') ?>">
        </div>

        <!-- Inputs ocultos: IDs de sucursales añadidas (JS los genera) -->
        <div id="hidden-sucursales-ids"></div>

        <?php else: ?>
        <!-- ── MODO DIRECCIÓN ÚNICA (sin sucursales registradas) ──── -->
        <?php if (!empty($comprador['direccion_entrega'])): ?>
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
        <input type="hidden" name="lat_entrega" id="input-lat-checkout" value="<?= htmlspecialchars($comprador['lat_entrega'] ?? '') ?>">
        <input type="hidden" name="lng_entrega" id="input-lng-checkout" value="<?= htmlspecialchars($comprador['lng_entrega'] ?? '') ?>">
        <div id="edit-direccion" style="display:none">
        <?php endif; ?>

        <!-- Campos manuales -->
        <?php if ($gmKey): ?>
        <input type="text" id="input-dir-checkout"
               name="<?= !empty($comprador['direccion_entrega']) ? 'direccion_entrega_edit' : 'direccion_entrega' ?>"
               placeholder="Escribe para buscar con Google Maps..."
               value="<?= htmlspecialchars($comprador['direccion_entrega'] ?? '') ?>"
               autocomplete="off"
               style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;box-sizing:border-box;margin-bottom:6px"
               <?= empty($comprador['direccion_entrega']) && $teActual==='repartidor' ? 'required' : '' ?>>
        <?php else: ?>
        <textarea name="<?= !empty($comprador['direccion_entrega']) ? 'direccion_entrega_edit' : 'direccion_entrega' ?>"
                  id="input-dir-checkout" rows="2"
                  placeholder="Calle, colonia, municipio..."
                  style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;resize:none;box-sizing:border-box;margin-bottom:6px"
                  <?= empty($comprador['direccion_entrega']) && $teActual==='repartidor' ? 'required' : '' ?>><?= htmlspecialchars($comprador['direccion_entrega'] ?? '') ?></textarea>
        <?php endif; ?>
        <input type="text"
               name="<?= !empty($comprador['direccion_entrega']) ? 'referencia_entrega_edit' : 'referencia_entrega' ?>"
               id="input-ref-checkout"
               placeholder="Ej: Interior 3B, edificio azul..."
               value="<?= htmlspecialchars($comprador['referencia_entrega'] ?? '') ?>"
               style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;box-sizing:border-box">
        <?php if (!empty($comprador['lat_entrega']) && empty($comprador['direccion_entrega']) === false): ?>
        <input type="hidden" name="lat_entrega" id="input-lat-checkout" value="<?= htmlspecialchars($comprador['lat_entrega'] ?? '') ?>">
        <input type="hidden" name="lng_entrega" id="input-lng-checkout" value="<?= htmlspecialchars($comprador['lng_entrega'] ?? '') ?>">
        <?php else: ?>
        <input type="hidden" name="lat_entrega" id="input-lat-checkout" value="">
        <input type="hidden" name="lng_entrega" id="input-lng-checkout" value="">
        <?php endif; ?>

        <?php if (!empty($comprador['direccion_entrega'])): ?>
        </div><!-- /edit-direccion -->
        <?php endif; ?>

        <?php if (empty($comprador['direccion_entrega'])): ?>
        <div style="font-size:.75rem;color:#6B7280;margin-top:6px">
          Guarda tu dirección en tu <a href="<?= BASE_URL ?>cuenta/perfil" target="_blank" style="color:var(--color-primary)">perfil</a> para futuros pedidos.
        </div>
        <?php endif; ?>
        <?php endif; // fin sin sucursales ?>

      </div><!-- /bloque-direccion -->

      <!-- Notas -->
      <div style="margin-bottom:18px">
        <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">
          Notas adicionales
          <span style="font-size:.72rem;color:#9CA3AF;font-weight:400"> — instrucciones especiales, cortes específicos, etc.</span>
        </label>
        <textarea name="notas" rows="3" placeholder="Ej: Entregar antes del mediodía, pedir al guardia que avise..."
                  style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;resize:vertical"><?= htmlspecialchars($metaSaved['notas'] ?? '') ?></textarea>
      </div>

      <!-- Total + costo envío -->
      <div style="background:#F9FAFB;border-radius:8px;padding:14px;margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;font-size:.82rem;color:#6B7280;margin-bottom:6px">
          <span>Subtotal productos</span>
          <span>$<?= number_format($total, 2) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.82rem;color:#9CA3AF;margin-bottom:10px" id="fila-envio">
          <span>Costo de envío</span>
          <span id="txt-costo-envio">— La empresa lo asigna —</span>
        </div>
        <div style="border-top:1px solid #E5E7EB;padding-top:10px;text-align:center">
          <div style="font-size:.8rem;color:#6B7280;margin-bottom:2px">Total del pedido</div>
          <div style="font-size:1.8rem;font-weight:800;color:var(--color-primary)">$<?= number_format($total, 2) ?></div>
          <div style="font-size:.72rem;color:#9CA3AF;margin-top:2px">El costo de envío se confirma al aprobar</div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:8px">
        <button type="submit" id="btn-confirmar" style="padding:12px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:700;font-size:.9rem;cursor:pointer;width:100%">
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

  // ── Tipo de entrega cards ─────────────────────────────────────────────
  function actualizarCards() {
    var val = document.querySelector('[name="tipo_entrega"]:checked')?.value;
    ['pickup','repartidor'].forEach(function(v) {
      var card = document.getElementById('card-' + v);
      if (!card) return;
      var sel = (val === v);
      card.style.borderColor = sel ? primary : '#E5E7EB';
      card.style.background  = sel ? '#FFF5F5' : '#fff';
    });
    var bPickup = document.getElementById('bloque-pickup');
    var bDir    = document.getElementById('bloque-direccion');
    if (bPickup) bPickup.style.display = (val === 'pickup')     ? '' : 'none';
    if (bDir)    bDir.style.display    = (val === 'repartidor') ? '' : 'none';
  }
  document.querySelectorAll('[name="tipo_entrega"]').forEach(function(r) {
    r.addEventListener('change', actualizarCards);
    r.closest('label').addEventListener('click', function() { r.checked = true; actualizarCards(); });
  });
  actualizarCards();

  // ── Editar dirección guardada (modo sin sucursales) ───────────────────
  window.toggleEditDireccion = function() {
    var ed = document.getElementById('edit-direccion');
    var hd = document.getElementById('hidden-dir');
    var hr = document.getElementById('hidden-ref');
    if (!ed || !hd) return;
    var visible = ed.style.display !== 'none';
    ed.style.display = visible ? 'none' : '';
    hd.disabled = !visible;
    if (hr) hr.disabled = !visible;
  };

  // ── Multi-parada (solo si hay sucursales registradas) ─────────────────
  var listaParadas  = document.getElementById('lista-paradas');
  if (!listaParadas) return; // modo sin sucursales, salir

  var paradasEmpty  = document.getElementById('paradas-empty');
  var hiddenCont    = document.getElementById('hidden-sucursales-ids');
  var btnToggle     = document.getElementById('btn-toggle-dropdown');
  var dropdown      = document.getElementById('dropdown-sucursales');
  var dropdownVacio = document.getElementById('dropdown-vacio');
  var btnAddManual  = document.getElementById('btn-add-manual');
  var panelManual   = document.getElementById('panel-dir-manual');
  var btnRemManual  = document.getElementById('btn-remove-manual');

  // Estado
  var paradasIds = []; // array de sucursal_id (enteros)
  var manualActivo = false;

  // Abrir/cerrar dropdown sucursales
  btnToggle.addEventListener('click', function(e) {
    e.stopPropagation();
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
  });
  document.addEventListener('click', function() {
    if (dropdown) dropdown.style.display = 'none';
  });
  dropdown.addEventListener('click', function(e) { e.stopPropagation(); });

  // Añadir sucursal desde dropdown
  dropdown.querySelectorAll('.suc-option').forEach(function(opt) {
    opt.addEventListener('click', function() {
      var id     = parseInt(this.dataset.id);
      var nombre = this.dataset.nombre;
      var dir    = this.dataset.dir;
      var lat    = parseFloat(this.dataset.lat) || 0;
      var lng    = parseFloat(this.dataset.lng) || 0;
      if (paradasIds.indexOf(id) !== -1) return; // ya añadida
      paradasIds.push(id);
      agregarParadaUI(id, nombre, dir, lat, lng);
      actualizarDropdown();
      sincronizarHiddens();
      dropdown.style.display = 'none';
    });
  });

  // Añadir dirección manual
  btnAddManual.addEventListener('click', function() {
    if (manualActivo) return;
    manualActivo = true;
    panelManual.style.display = '';
    btnAddManual.disabled = true;
    btnAddManual.style.opacity = '.4';
    paradasEmpty.style.display = 'none';
  });

  // Quitar dirección manual
  if (btnRemManual) {
    btnRemManual.addEventListener('click', function() {
      manualActivo = false;
      panelManual.style.display = 'none';
      btnAddManual.disabled = false;
      btnAddManual.style.opacity = '1';
      // Limpiar campos manuales
      var dirInput = document.getElementById('input-dir-checkout');
      var refInput = document.getElementById('input-ref-checkout');
      if (dirInput) dirInput.value = '';
      if (refInput) refInput.value = '';
      var latEl = document.getElementById('input-lat-checkout');
      var lngEl = document.getElementById('input-lng-checkout');
      if (latEl) latEl.value = '';
      if (lngEl) lngEl.value = '';
      actualizarEmptyState();
    });
  }

  function agregarParadaUI(id, nombre, dir, lat, lng) {
    var item = document.createElement('div');
    item.className  = 'parada-item';
    item.dataset.id = id;
    item.style.cssText = 'border:1px solid #E5E7EB;border-radius:8px;padding:10px 12px;display:flex;align-items:flex-start;gap:8px;background:#fff';
    var num = paradasIds.indexOf(id) + 1;
    item.innerHTML =
      '<div style="flex-shrink:0;width:22px;height:22px;border-radius:50%;background:var(--color-primary);color:#fff;font-size:.7rem;font-weight:700;display:flex;align-items:center;justify-content:center">' + num + '</div>' +
      '<div style="flex:1;min-width:0">' +
        '<div style="font-size:.85rem;font-weight:700;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + htmlEsc(nombre) + '</div>' +
        '<div style="font-size:.75rem;color:#6B7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + htmlEsc(dir) + '</div>' +
      '</div>' +
      '<button type="button" data-rm="' + id + '" style="background:none;border:none;color:#9CA3AF;font-size:1rem;cursor:pointer;padding:0;line-height:1;flex-shrink:0">×</button>';
    item.querySelector('[data-rm]').addEventListener('click', function() {
      quitarParada(parseInt(this.dataset.rm));
    });
    listaParadas.appendChild(item);
    paradasEmpty.style.display = 'none';
  }

  function quitarParada(id) {
    var idx = paradasIds.indexOf(id);
    if (idx === -1) return;
    paradasIds.splice(idx, 1);
    // Quitar del DOM
    var item = listaParadas.querySelector('[data-id="' + id + '"]');
    if (item) item.remove();
    // Renumerar
    listaParadas.querySelectorAll('.parada-item').forEach(function(el, i) {
      var badge = el.querySelector('div[style*="border-radius:50%"]');
      if (badge) badge.textContent = i + 1;
    });
    actualizarDropdown();
    sincronizarHiddens();
    actualizarEmptyState();
  }

  function actualizarDropdown() {
    var alguno = false;
    dropdown.querySelectorAll('.suc-option').forEach(function(opt) {
      var id = parseInt(opt.dataset.id);
      var usada = paradasIds.indexOf(id) !== -1;
      opt.style.display = usada ? 'none' : '';
      if (!usada) alguno = true;
    });
    if (dropdownVacio) dropdownVacio.style.display = alguno ? 'none' : '';
    btnToggle.disabled = !alguno && !dropdownVacio;
  }

  function sincronizarHiddens() {
    hiddenCont.innerHTML = '';
    paradasIds.forEach(function(id) {
      var inp = document.createElement('input');
      inp.type  = 'hidden';
      inp.name  = 'sucursales_ids[]';
      inp.value = id;
      hiddenCont.appendChild(inp);
    });
  }

  function actualizarEmptyState() {
    var hayParadas = paradasIds.length > 0 || manualActivo;
    paradasEmpty.style.display = hayParadas ? 'none' : '';
  }

  // Validar al enviar: al menos una parada si es repartidor
  document.getElementById('form-pedido').addEventListener('submit', function(e) {
    var te = document.querySelector('[name="tipo_entrega"]:checked');
    if (!te || te.value !== 'repartidor') return;
    if (paradasIds.length === 0 && !manualActivo) {
      e.preventDefault();
      paradasEmpty.style.borderColor = '#EF4444';
      paradasEmpty.style.color = '#EF4444';
      paradasEmpty.textContent = 'Añade al menos una parada de entrega antes de confirmar';
      paradasEmpty.style.display = '';
      paradasEmpty.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });

  function htmlEsc(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
  }

  // ── Google Maps Autocomplete (campo dirección manual) ─────────────────
  window.initGoogleMapsCheckout = function() {
    var inputDir = document.getElementById('input-dir-checkout');
    if (!inputDir || typeof google === 'undefined') return;
    var ac = new google.maps.places.Autocomplete(inputDir, {
      componentRestrictions: { country: 'mx' },
      fields: ['geometry', 'formatted_address'],
    });
    ac.addListener('place_changed', function() {
      var place = ac.getPlace();
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
