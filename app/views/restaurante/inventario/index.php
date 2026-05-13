<?php ob_start(); ?>

<!-- Alertas stock bajo -->
<?php if (!empty($alertas)): ?>
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
  <svg width="18" height="18" fill="none" stroke="#EF4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
  <span style="font-size:.85rem;font-weight:600;color:#991B1B">
    <?= count($alertas) ?> ingrediente<?= count($alertas) > 1 ? 's' : '' ?> con stock bajo:
    <?= implode(', ', array_column($alertas, 'nombre')) ?>
  </span>
</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div style="display:flex;gap:10px;align-items:center">
    <a href="<?= BASE_URL ?>rest-inventario/movimientos" class="btn btn-outline btn-sm">
      Ver historial
    </a>
  </div>
  <button onclick="rstModal('modalIng')" class="btn btn-primary btn-sm">
    + Ingrediente
  </button>
</div>

<div class="rst-table-wrap">
  <table class="rst-table">
    <thead>
      <tr>
        <th>Ingrediente</th>
        <th>Fuente</th>
        <th>Categoría</th>
        <th style="text-align:right">Stock</th>
        <th style="text-align:right">Mín.</th>
        <th style="text-align:right">Costo/u</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($ingredientes as $ing): ?>
      <?php $bajo = (float)$ing['stock'] <= (float)$ing['stock_minimo']; ?>
      <tr style="<?= $bajo ? 'background:#FFFBFB' : '' ?>">
        <td style="font-weight:600"><?= htmlspecialchars($ing['nombre']) ?></td>
        <td>
          <?php if ($ing['proveedor_carnihub']): ?>
          <span class="badge badge-purple">CarniHub</span>
          <?php elseif ($ing['proveedor_nombre']): ?>
          <span class="badge badge-gray" style="font-size:.68rem"><?= htmlspecialchars($ing['proveedor_nombre']) ?></span>
          <?php else: ?>
          <span style="color:#9CA3AF;font-size:.8rem">—</span>
          <?php endif; ?>
        </td>
        <td style="color:#6B7280"><?= htmlspecialchars($ing['categoria'] ?? '—') ?></td>
        <td style="text-align:right;font-weight:600;color:<?= $bajo ? '#EF4444' : '#111827' ?>">
          <?= number_format((float)$ing['stock'], 3) ?> <span style="font-size:.78rem;color:#9CA3AF"><?= htmlspecialchars($ing['unidad_principal']) ?></span>
        </td>
        <td style="text-align:right;color:#9CA3AF;font-size:.85rem"><?= number_format((float)$ing['stock_minimo'], 3) ?></td>
        <td style="text-align:right">$<?= number_format((float)$ing['costo_unitario'], 2) ?></td>
        <td>
          <span class="badge <?= $bajo ? 'badge-red' : 'badge-green' ?>">
            <?= $bajo ? 'Stock bajo' : 'OK' ?>
          </span>
        </td>
        <td>
          <button onclick="abrirMovimiento(<?= $ing['id'] ?>, '<?= htmlspecialchars($ing['nombre'], ENT_QUOTES) ?>')"
                  class="btn btn-outline btn-sm">Mover</button>
          <button onclick='editIngrediente(<?= htmlspecialchars(json_encode($ing), ENT_QUOTES) ?>)'
                  class="btn btn-outline btn-sm" style="margin-left:4px">Editar</button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($ingredientes)): ?>
      <tr>
        <td colspan="8">
          <div class="empty-state">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <div style="font-size:.95rem;font-weight:600;color:#374151;margin-bottom:4px">Sin ingredientes</div>
            <div style="font-size:.85rem">Agrega ingredientes de CarniHub o de proveedores externos</div>
          </div>
        </td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal nuevo/editar ingrediente -->
<div id="modalIng" class="rst-modal-backdrop">
  <div class="rst-modal">
    <div class="rst-modal-header">
      <div class="rst-modal-title" id="modalIngTitle">Nuevo Ingrediente</div>
      <button class="rst-modal-close" onclick="rstModal('modalIng')">✕</button>
    </div>

    <!-- Tabs fuente -->
    <div class="rst-tabs" id="ingTabs">
      <button class="rst-tab active" data-tab="ext" onclick="switchTab('ext')">Proveedor externo</button>
      <button class="rst-tab" data-tab="ch"  onclick="switchTab('ch')">Desde CarniHub</button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>rest-inventario/guardar" id="formIng">
      <input type="hidden" name="id" id="ingId" value="">
      <input type="hidden" name="proveedor_carnihub" id="ingEsCarniHub" value="0">
      <input type="hidden" name="carnihub_producto_id" id="ingCarniHubId" value="">

      <!-- Panel externo -->
      <div class="rst-tab-panel active" id="panelExt">
        <div class="form-group">
          <label class="form-label">Nombre del ingrediente *</label>
          <input type="text" name="nombre" id="ingNombre" class="form-input"
                 placeholder="Ej: Jitomate, Carne de res, Aceite" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label class="form-label">Unidad de medida</label>
            <select name="unidad_principal" id="ingUnidad" class="form-select">
              <option value="kg">kg (kilogramo)</option>
              <option value="g">g (gramo)</option>
              <option value="L">L (litro)</option>
              <option value="ml">ml (mililitro)</option>
              <option value="pza">pza (pieza)</option>
              <option value="caja">caja</option>
              <option value="bolsa">bolsa</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Categoría</label>
            <input type="text" name="categoria" id="ingCategoria" class="form-input"
                   placeholder="Ej: Lácteos, Carnes, Verduras">
          </div>
          <div class="form-group">
            <label class="form-label">Costo por unidad</label>
            <input type="number" name="costo_unitario" id="ingCosto" class="form-input"
                   value="0" min="0" step="0.01" placeholder="0.00">
          </div>
          <div class="form-group">
            <label class="form-label">Stock mínimo (alerta)</label>
            <input type="number" name="stock_minimo" id="ingMinimo" class="form-input"
                   value="0" min="0" step="0.001" placeholder="0.000">
          </div>
          <div class="form-group" style="grid-column:span 2">
            <label class="form-label">Stock inicial</label>
            <input type="number" name="stock_inicial" id="ingStockInicial" class="form-input"
                   value="0" min="0" step="0.001" placeholder="0.000">
          </div>
          <div class="form-group" style="grid-column:span 2">
            <label class="form-label">Proveedor <span style="color:#9CA3AF;font-weight:400">(nombre libre)</span></label>
            <input type="text" name="proveedor_nombre" id="ingProveedor" class="form-input"
                   placeholder="Ej: Mercado local, Walmart, Don José">
          </div>
        </div>
      </div>

      <!-- Panel CarniHub -->
      <div class="rst-tab-panel" id="panelCh">
        <div style="background:var(--cp-light);border-radius:10px;padding:14px;margin-bottom:16px;font-size:.85rem;color:#374151">
          <strong>Catálogo CarniHub</strong> — Los productos que compraste a tu distribuidor aparecen aquí.
          Al agregarlos, el stock se mantiene sincronizado con tus pedidos CarniHub.
        </div>
        <?php if (!empty($productosCarnihub)): ?>
        <div style="max-height:200px;overflow-y:auto;border:1px solid #E5E7EB;border-radius:8px;margin-bottom:12px">
          <?php foreach ($productosCarnihub as $pc): ?>
          <div style="padding:10px 14px;border-bottom:1px solid #F3F4F6;display:flex;justify-content:space-between;align-items:center;cursor:pointer"
               onclick="seleccionarCarniHub(<?= $pc['id'] ?>, '<?= htmlspecialchars($pc['nombre'], ENT_QUOTES) ?>')">
            <div>
              <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($pc['nombre']) ?></div>
              <div style="font-size:.75rem;color:#9CA3AF"><?= htmlspecialchars($pc['unidad'] ?? '') ?></div>
            </div>
            <span class="badge badge-purple">CarniHub</span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding:24px">
          <div style="font-size:.85rem">No tienes pedidos de CarniHub aún. Cuando compres productos a tu distribuidor, aparecerán aquí para importarlos al inventario.</div>
        </div>
        <?php endif; ?>
        <div class="form-group" id="chNombreWrap" style="display:none">
          <label class="form-label">Producto seleccionado</label>
          <input type="text" id="ingNombreCh" class="form-input" readonly
                 style="background:#F9FAFB" placeholder="Selecciona un producto de arriba">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group">
            <label class="form-label">Stock inicial a registrar</label>
            <input type="number" name="stock_inicial_ch" id="ingStockCh" class="form-input"
                   value="0" min="0" step="0.001">
          </div>
          <div class="form-group">
            <label class="form-label">Stock mínimo (alerta)</label>
            <input type="number" name="stock_minimo_ch" id="ingMinimoCh" class="form-input"
                   value="0" min="0" step="0.001">
          </div>
        </div>
      </div>

      <div class="rst-modal-footer">
        <button type="button" onclick="rstModal('modalIng')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar ingrediente</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal movimiento de stock -->
<div id="modalMov" class="rst-modal-backdrop">
  <div class="rst-modal rst-modal-sm">
    <div class="rst-modal-header">
      <div>
        <div class="rst-modal-title">Movimiento de Stock</div>
        <div id="movIngNombre" style="font-size:.82rem;color:#6B7280;margin-top:2px"></div>
      </div>
      <button class="rst-modal-close" onclick="rstModal('modalMov')">✕</button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>rest-inventario/movimiento">
      <input type="hidden" name="ingrediente_id" id="movIngId">
      <div class="form-group">
        <label class="form-label">Tipo de movimiento</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px">
          <?php
          $tipos = [
            ['val'=>'entrada', 'label'=>'Entrada',  'cls'=>'badge-green',  'desc'=>'Suma al stock'],
            ['val'=>'salida',  'label'=>'Salida',   'cls'=>'badge-red',    'desc'=>'Resta del stock'],
            ['val'=>'merma',   'label'=>'Merma',    'cls'=>'badge-amber',  'desc'=>'Pérdida/daño'],
            ['val'=>'ajuste',  'label'=>'Ajuste',   'cls'=>'badge-blue',   'desc'=>'Corrección manual'],
          ];
          foreach ($tipos as $t):
          ?>
          <label style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:2px solid #E5E7EB;
                        border-radius:8px;cursor:pointer;transition:.15s" class="tipo-lbl">
            <input type="radio" name="tipo" value="<?= $t['val'] ?>" style="display:none" class="tipo-radio">
            <span class="badge <?= $t['cls'] ?>"><?= $t['label'] ?></span>
            <span style="font-size:.78rem;color:#6B7280"><?= $t['desc'] ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Cantidad</label>
        <input type="number" name="cantidad" class="form-input" step="0.001" min="0.001" required placeholder="0.000">
      </div>
      <div class="form-group">
        <label class="form-label">Motivo <span style="color:#9CA3AF;font-weight:400">(opcional)</span></label>
        <input type="text" name="motivo" class="form-input" placeholder="Ej: Compra del día, Desperdicio, Inventario físico">
      </div>
      <div class="rst-modal-footer">
        <button type="button" onclick="rstModal('modalMov')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Registrar</button>
      </div>
    </form>
  </div>
</div>

<script>
function rstModal(id) {
  document.getElementById(id).classList.toggle('open');
}
document.querySelectorAll('.rst-modal-backdrop').forEach(bd => {
  bd.addEventListener('click', e => { if (e.target === bd) bd.classList.remove('open'); });
});

// Tabs
let tabActual = 'ext';
function switchTab(tab) {
  tabActual = tab;
  document.querySelectorAll('.rst-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
  document.querySelectorAll('.rst-tab-panel').forEach(p => p.classList.toggle('active', p.id === 'panel' + tab.charAt(0).toUpperCase() + tab.slice(1)));
  document.getElementById('ingEsCarniHub').value = tab === 'ch' ? '1' : '0';
  if (tab === 'ext') {
    document.getElementById('ingNombre').required = true;
  } else {
    document.getElementById('ingNombre').required = false;
  }
}

function seleccionarCarniHub(id, nombre) {
  document.getElementById('ingCarniHubId').value  = id;
  document.getElementById('ingNombreCh').value    = nombre;
  document.getElementById('chNombreWrap').style.display = 'block';
  // Highlight selected row
  document.querySelectorAll('#panelCh [onclick]').forEach(r => r.style.background = '');
  event.currentTarget.style.background = 'var(--cp-light)';
}

// Tipo radio highlight
document.querySelectorAll('.tipo-lbl').forEach(lbl => {
  const radio = lbl.querySelector('.tipo-radio');
  lbl.addEventListener('click', () => {
    document.querySelectorAll('.tipo-lbl').forEach(l => l.style.borderColor = '#E5E7EB');
    lbl.style.borderColor = 'var(--cp)';
    radio.checked = true;
  });
});
// Seleccionar "Entrada" por defecto
const firstLbl = document.querySelector('.tipo-lbl');
if (firstLbl) { firstLbl.click(); }

function abrirMovimiento(id, nombre) {
  document.getElementById('movIngId').value = id;
  document.getElementById('movIngNombre').textContent = nombre;
  document.getElementById('modalMov').classList.add('open');
}
function editIngrediente(i) {
  // Reset tabs
  switchTab('ext');
  document.getElementById('ingId').value         = i.id;
  document.getElementById('ingNombre').value     = i.nombre;
  // Unidad
  const uSel = document.getElementById('ingUnidad');
  let found = false;
  for (let o of uSel.options) { if (o.value === i.unidad_principal) { o.selected = true; found = true; break; } }
  if (!found) { const opt = new Option(i.unidad_principal, i.unidad_principal, true, true); uSel.add(opt); }
  document.getElementById('ingCategoria').value  = i.categoria || '';
  document.getElementById('ingCosto').value      = i.costo_unitario;
  document.getElementById('ingMinimo').value     = i.stock_minimo;
  document.getElementById('ingProveedor').value  = i.proveedor_nombre || '';
  document.getElementById('modalIngTitle').textContent = 'Editar Ingrediente';
  document.getElementById('modalIng').classList.add('open');
}
</script>
<?php
$content = ob_get_clean();
$activeMenu = 'rest_inventario';
$pageTitle  = 'Inventario';
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
