<?php
// Vista: Catálogo de productos — comprador y admin_empresa
$productoModelCat = new ProductoModel();
// Pre-cargar precios escalonados por producto
foreach ($productos as &$prod) {
    $prod['escalonados'] = $productoModelCat->getEscalonados((int)$prod['id']);
}
unset($prod);

$rol          = $_SESSION['usuario']['rol_slug'] ?? '';
$puedeComprar = in_array($rol, ['admin_empresa','comprador'], true);
$totalCarrito = count($_SESSION['carrito']['items'] ?? []);
?>

<!-- Filtros -->
<form method="GET" action="<?= BASE_URL ?>catalogo/index" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:flex-end">
  <div style="flex:1;min-width:180px">
    <label style="font-size:.75rem;font-weight:600;color:#6B7280;display:block;margin-bottom:4px">Buscar</label>
    <input type="text" name="buscar" value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>"
           placeholder="Nombre de producto..."
           style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
  </div>
  <div style="min-width:160px">
    <label style="font-size:.75rem;font-weight:600;color:#6B7280;display:block;margin-bottom:4px">Categoría</label>
    <select name="categoria_id" style="padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;width:100%">
      <option value="">Todas</option>
      <?php foreach ($categorias as $cat): ?>
      <option value="<?= $cat['id'] ?>" <?= ($filtros['categoria_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($cat['nombre']) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" style="padding:9px 20px;background:#374151;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
    Filtrar
  </button>
  <?php if ($puedeComprar): ?>
  <a href="<?= BASE_URL ?>carrito/index" id="btnVerCarrito"
     style="padding:9px 20px;background:var(--color-primary);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem;margin-left:auto;display:flex;align-items:center;gap:6px">
    🛒 Ver carrito
    <span id="cartBadge" style="<?= $totalCarrito > 0 ? '' : 'display:none;' ?>background:#fff;color:var(--color-primary);border-radius:999px;padding:0 7px;font-size:.75rem;font-weight:800"><?= $totalCarrito ?: 0 ?></span>
  </a>
  <?php endif; ?>
</form>

<!-- Grid de productos -->
<?php if (empty($productos)): ?>
<div style="text-align:center;padding:40px;color:#6B7280">Sin productos disponibles.</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
  <?php foreach ($productos as $prod): ?>
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;display:flex;flex-direction:column">
    <!-- Imagen -->
    <div style="height:140px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;overflow:hidden">
      <?php if (!empty($prod['imagen'])): ?>
        <img src="<?= htmlspecialchars($prod['imagen']) ?>" alt="<?= htmlspecialchars($prod['nombre']) ?>" style="width:100%;height:100%;object-fit:cover">
      <?php else: ?>
        <span style="font-size:3rem">🥩</span>
      <?php endif; ?>
    </div>
    <!-- Info -->
    <div style="padding:14px;flex:1">
      <div style="font-size:.75rem;color:#9CA3AF;margin-bottom:4px"><?= htmlspecialchars($prod['categoria_nombre']) ?></div>
      <div style="font-weight:700;font-size:.95rem;color:#111827;margin-bottom:4px"><?= htmlspecialchars($prod['nombre']) ?></div>
      <div style="font-size:.8rem;color:#6B7280;margin-bottom:10px"><?= htmlspecialchars($prod['presentacion']) ?></div>
      <div style="font-size:1.1rem;font-weight:800;color:var(--color-primary)">
        $<?= number_format($prod['precio_base'],2) ?> / <?= $prod['presentacion'] ?>
      </div>
      <?php if (!empty($prod['escalonados'])): ?>
      <div style="font-size:.72rem;color:#059669;margin-top:3px;font-weight:600">✦ Descuentos por volumen</div>
      <?php endif; ?>
    </div>
    <!-- Acciones -->
    <?php
    $prodData = json_encode([
        'id'          => $prod['id'],
        'nombre'      => $prod['nombre'],
        'presentacion'=> $prod['presentacion'],
        'precio_base' => (float)$prod['precio_base'],
        'imagen'      => $prod['imagen'] ?? '',
        'categoria'   => $prod['categoria_nombre'],
        'escalonados' => $prod['escalonados'],
    ]);
    ?>
    <div style="padding:12px 14px;border-top:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between">
      <button onclick='verDetalleCatalogo(<?= $prodData ?>)'
              style="font-size:.8rem;color:#6B7280;background:none;border:none;cursor:pointer;padding:0;text-decoration:underline;text-underline-offset:2px">
        Ver precios
      </button>
      <?php if ($puedeComprar): ?>
      <button onclick='abrirModalAgregar(<?= $prodData ?>)'
              style="padding:6px 14px;background:var(--color-primary);color:#fff;border:none;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer">
        + Agregar
      </button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ═══════════════════════ Modal: Agregar al carrito ═══════════════════════ -->
<div id="modalAgregar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:2000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:14px;width:460px;max-width:96vw;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2)">

    <div id="modImg" style="height:140px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:14px 14px 0 0">
      <span style="font-size:4rem">🥩</span>
    </div>

    <div style="padding:20px">
      <div style="font-size:.75rem;color:#9CA3AF" id="modCategoria"></div>
      <h3 id="modNombre" style="font-size:1.1rem;font-weight:800;color:#111827;margin:4px 0 2px"></h3>
      <div id="modPresentacion" style="font-size:.8rem;color:#6B7280;margin-bottom:14px"></div>

      <!-- Precio estimado -->
      <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span style="font-size:.8rem;color:#374151">Precio estimado:</span>
          <span id="modPrecioEst" style="font-size:1.2rem;font-weight:800;color:var(--color-primary)">$0.00</span>
        </div>
        <div id="modSubtotalEst" style="text-align:right;font-size:.75rem;color:#6B7280;margin-top:2px"></div>
      </div>

      <!-- Alerta de tramo -->
      <div id="modAlertaTramo" style="display:none;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:.82rem;font-weight:600"></div>

      <!-- Precios por volumen -->
      <div id="modTiersSection" style="display:none;margin-bottom:14px">
        <div style="font-size:.78rem;font-weight:700;color:#374151;margin-bottom:6px">📊 Precios por volumen</div>
        <div id="modTiersTable" style="font-size:.8rem;border:1px solid #E5E7EB;border-radius:8px;overflow:hidden"></div>
      </div>

      <!-- Cantidad -->
      <div style="margin-bottom:16px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px">
          Cantidad <span id="modUnidad" style="color:#9CA3AF;font-weight:400"></span>
        </label>
        <input type="number" id="modCantidad" min="0.5" step="0.5" placeholder="0"
               style="width:100%;padding:11px 14px;border:2px solid #D1D5DB;border-radius:8px;font-size:1rem;text-align:center;box-sizing:border-box;outline:none"
               oninput="actualizarModalPrecio()"
               onfocus="this.style.borderColor='var(--color-primary)'"
               onblur="this.style.borderColor='#D1D5DB'">
      </div>

      <div style="display:flex;gap:10px">
        <button type="button" onclick="cerrarModalAgregar()"
                style="flex:1;padding:11px;border:1px solid #D1D5DB;border-radius:8px;background:#fff;cursor:pointer;font-size:.875rem;color:#6B7280;font-family:inherit">
          Cancelar
        </button>
        <button type="button" id="btnAgregarConfirmar" onclick="confirmarAgregar()"
                style="flex:2;padding:11px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.875rem;font-family:inherit">
          Agregar al pedido
        </button>
      </div>

      <div id="modFeedback" style="display:none;margin-top:12px;padding:10px 14px;border-radius:8px;font-size:.85rem;text-align:center;border:1px solid transparent"></div>
    </div>
  </div>
</div>

<script>
const BASE_URL_CAT = '<?= BASE_URL ?>';
let modalProducto = null;
let debTimer = null;

function abrirModalAgregar(prod) {
  modalProducto = prod;
  document.getElementById('modNombre').textContent       = prod.nombre;
  document.getElementById('modCategoria').textContent    = prod.categoria || '';
  document.getElementById('modPresentacion').textContent = prod.presentacion;
  document.getElementById('modUnidad').textContent       = '(' + prod.presentacion + ')';
  document.getElementById('modCantidad').value           = '';
  document.getElementById('modFeedback').style.display   = 'none';
  document.getElementById('modAlertaTramo').style.display = 'none';
  document.getElementById('modSubtotalEst').textContent  = '';
  document.getElementById('modPrecioEst').textContent    = '$' + prod.precio_base.toLocaleString('es-MX',{minimumFractionDigits:2}) + ' / ' + prod.presentacion;
  document.getElementById('btnAgregarConfirmar').style.display = 'block';

  const imgDiv = document.getElementById('modImg');
  imgDiv.innerHTML = prod.imagen
    ? `<img src="${prod.imagen}" alt="${prod.nombre}" style="width:100%;height:100%;object-fit:cover">`
    : '<span style="font-size:4rem">🥩</span>';

  // Tiers
  if (prod.escalonados && prod.escalonados.length > 0) {
    document.getElementById('modTiersSection').style.display = 'block';
    let html = '';
    prod.escalonados.forEach((t, i) => {
      const desde  = parseFloat(t.cantidad_min);
      const hasta  = t.cantidad_max ? parseFloat(t.cantidad_max) : null;
      const label  = hasta ? `${desde}–${hasta} ${prod.presentacion}` : `${desde}+ ${prod.presentacion}`;
      const precio = parseFloat(t.precio);
      const ahorro = prod.precio_base - precio;
      const pct    = prod.precio_base > 0 ? ((ahorro / prod.precio_base)*100).toFixed(0) : 0;
      html += `<div style="display:flex;justify-content:space-between;align-items:center;padding:7px 12px;${i>0?'border-top:1px solid #F3F4F6':''}" id="tier-${i}">
        <span style="color:#374151">${label}</span>
        <div style="text-align:right">
          <span style="font-weight:700;color:#111827">$${precio.toFixed(2)}</span>
          ${ahorro>0.01?`<span style="display:block;font-size:.68rem;color:#059669;font-weight:600">−${pct}% dto.</span>`:''}
        </div>
      </div>`;
    });
    document.getElementById('modTiersTable').innerHTML = html;
  } else {
    document.getElementById('modTiersSection').style.display = 'none';
  }

  document.getElementById('modalAgregar').style.display = 'flex';
  setTimeout(() => document.getElementById('modCantidad').focus(), 100);
}

function verDetalleCatalogo(prod) {
  abrirModalAgregar(prod);
  <?php if (!$puedeComprar): ?>
  document.getElementById('btnAgregarConfirmar').style.display = 'none';
  <?php endif; ?>
}

function cerrarModalAgregar() {
  document.getElementById('modalAgregar').style.display = 'none';
  modalProducto = null;
}

function actualizarModalPrecio() {
  if (!modalProducto) return;
  clearTimeout(debTimer);
  const qty = parseFloat(document.getElementById('modCantidad').value) || 0;
  if (qty <= 0) {
    document.getElementById('modPrecioEst').textContent = '$' + modalProducto.precio_base.toLocaleString('es-MX',{minimumFractionDigits:2}) + ' / ' + modalProducto.presentacion;
    document.getElementById('modSubtotalEst').textContent = '';
    document.getElementById('modAlertaTramo').style.display = 'none';
    resaltarTier(null); return;
  }
  debTimer = setTimeout(() => {
    fetch(BASE_URL_CAT + 'api/precios/' + modalProducto.id + '?cantidad=' + qty)
      .then(r => r.json())
      .then(d => aplicarPrecio(qty, d.precio || modalProducto.precio_base))
      .catch(()  => aplicarPrecio(qty, precioLocal(qty)));
  }, 280);
}

function precioLocal(qty) {
  if (!modalProducto?.escalonados) return modalProducto.precio_base;
  let p = modalProducto.precio_base;
  modalProducto.escalonados.forEach(t => {
    const min = parseFloat(t.cantidad_min), max = t.cantidad_max ? parseFloat(t.cantidad_max) : Infinity;
    if (qty >= min && qty <= max) p = parseFloat(t.precio);
  });
  return p;
}

function aplicarPrecio(qty, precio) {
  document.getElementById('modPrecioEst').textContent = '$' + precio.toLocaleString('es-MX',{minimumFractionDigits:2}) + ' / ' + modalProducto.presentacion;
  document.getElementById('modSubtotalEst').textContent = 'Subtotal: $' + (precio*qty).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});

  const alerta = document.getElementById('modAlertaTramo');
  let activoIdx = -1;
  if (modalProducto.escalonados) {
    modalProducto.escalonados.forEach((t, i) => {
      const min = parseFloat(t.cantidad_min), max = t.cantidad_max ? parseFloat(t.cantidad_max) : Infinity;
      if (qty >= min && qty <= max) activoIdx = i;
    });
  }
  resaltarTier(activoIdx);

  const ahorro = modalProducto.precio_base - precio;
  if (ahorro > 0.01) {
    const pct = ((ahorro / modalProducto.precio_base)*100).toFixed(0);
    alerta.style.cssText = 'display:block;background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:.82rem;font-weight:600';
    alerta.textContent = `¡Ahorrando ${pct}% — $${ahorro.toFixed(2)} menos por ${modalProducto.presentacion}!`;
    return;
  }
  if (modalProducto.escalonados?.length) {
    const siguiente = activoIdx + 1;
    if (siguiente < modalProducto.escalonados.length) {
      const sig  = modalProducto.escalonados[siguiente];
      const falta = parseFloat(sig.cantidad_min) - qty;
      const pSig  = parseFloat(sig.precio);
      const pctSig = ((modalProducto.precio_base - pSig) / modalProducto.precio_base * 100).toFixed(0);
      alerta.style.cssText = 'display:block;background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:.82rem;font-weight:600';
      alerta.textContent = `Agrega ${falta.toFixed(1)} ${modalProducto.presentacion} más → precio ${pctSig}% dto. ($${pSig.toFixed(2)}/${modalProducto.presentacion})`;
      return;
    }
  }
  alerta.style.display = 'none';
}

function resaltarTier(idx) {
  if (!modalProducto?.escalonados) return;
  modalProducto.escalonados.forEach((_, i) => {
    const r = document.getElementById('tier-' + i);
    if (r) { r.style.background = i === idx ? '#F0FDF4' : ''; r.style.fontWeight = i === idx ? '700' : ''; }
  });
}

function confirmarAgregar() {
  if (!modalProducto) return;
  const qty = parseFloat(document.getElementById('modCantidad').value) || 0;
  if (qty <= 0) { document.getElementById('modCantidad').style.borderColor = '#DC2626'; document.getElementById('modCantidad').focus(); return; }
  document.getElementById('modCantidad').style.borderColor = '#D1D5DB';

  const btn = document.getElementById('btnAgregarConfirmar');
  btn.disabled = true; btn.textContent = 'Agregando...';

  const fd = new FormData();
  fd.append('producto_id', modalProducto.id);
  fd.append('cantidad', qty);

  fetch(BASE_URL_CAT + 'carrito/agregarProducto', { method:'POST', body:fd })
    .then(r => r.json())
    .then(d => {
      const fb = document.getElementById('modFeedback');
      fb.style.display = 'block';
      if (d.ok) {
        fb.style.cssText = 'display:block;margin-top:12px;padding:10px 14px;border-radius:8px;font-size:.85rem;text-align:center;background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0';
        fb.textContent = '✓ ' + d.msg;
        const badge = document.getElementById('cartBadge');
        if (badge) { badge.textContent = d.total_items; badge.style.display = 'inline'; }
        document.getElementById('modCantidad').value = '';
        document.getElementById('modSubtotalEst').textContent = '';
        document.getElementById('modAlertaTramo').style.display = 'none';
        document.getElementById('modPrecioEst').textContent = '$' + modalProducto.precio_base.toLocaleString('es-MX',{minimumFractionDigits:2}) + ' / ' + modalProducto.presentacion;
      } else {
        fb.style.cssText = 'display:block;margin-top:12px;padding:10px 14px;border-radius:8px;font-size:.85rem;text-align:center;background:#FEE2E2;color:#991B1B;border:1px solid #FECACA';
        fb.textContent = '✕ ' + d.msg;
      }
    })
    .catch(() => {
      const fb = document.getElementById('modFeedback');
      fb.style.cssText = 'display:block;margin-top:12px;padding:10px 14px;border-radius:8px;font-size:.85rem;text-align:center;background:#FEE2E2;color:#991B1B;border:1px solid #FECACA';
      fb.textContent = 'Error de conexión.';
    })
    .finally(() => { btn.disabled = false; btn.textContent = 'Agregar al pedido'; });
}

document.getElementById('modalAgregar').addEventListener('click', e => { if (e.target === document.getElementById('modalAgregar')) cerrarModalAgregar(); });
</script>
