<?php
$carritoItems = $carrito ?? [];
?>
<!-- Indicador de pasos -->
<div style="display:flex;align-items:center;gap:0;margin-bottom:24px;font-size:.8rem">
  <?php
  $pasos = ['1'=>'Productos','2'=>'Resumen','3'=>'Confirmado'];
  foreach ($pasos as $num => $label):
    $activo = $num === '1';
  ?>
  <div style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:<?= $activo ? 'var(--color-primary)' : '#E5E7EB' ?>;color:<?= $activo ? '#fff' : '#9CA3AF' ?>;<?= $num === '1' ? 'border-radius:8px 0 0 8px' : ($num === '3' ? 'border-radius:0 8px 8px 0' : '') ?>">
    <span style="font-weight:700"><?= $num ?></span>
    <?= $label ?>
  </div>
  <?php if ($num < '3'): ?>
  <div style="width:0;height:0;border-top:18px solid transparent;border-bottom:18px solid transparent;border-left:10px solid <?= $activo ? 'var(--color-primary)' : '#E5E7EB' ?>"></div>
  <?php endif; ?>
  <?php endforeach; ?>
</div>

<?php if ($flash): ?>
<div style="margin-bottom:12px;padding:10px 14px;border-radius:8px;font-size:.875rem;font-weight:500;
  <?= $flash['type']==='success' ? 'background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0' : 'background:#FEE2E2;color:#991B1B;border:1px solid #FECACA' ?>">
  <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<!-- Combos del comprador -->
<?php if (!empty($combos)): ?>
<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:16px;margin-bottom:16px">
  <div style="font-weight:700;font-size:.85rem;color:#1E40AF;margin-bottom:10px">📦 Tus combos — carga un pedido predefinido en un clic</div>
  <div style="display:flex;flex-wrap:wrap;gap:10px">
    <?php foreach ($combos as $combo): ?>
    <form method="POST" action="<?= BASE_URL ?>carrito/cargarCombo" style="display:inline">
      <input type="hidden" name="combo_id" value="<?= $combo['id'] ?>">
      <button type="submit"
              style="padding:8px 16px;background:#fff;border:1px solid #BFDBFE;border-radius:8px;cursor:pointer;font-size:.82rem;color:#1E40AF;font-weight:600;font-family:inherit;display:flex;flex-direction:column;align-items:flex-start;gap:2px">
        <span><?= htmlspecialchars($combo['nombre']) ?></span>
        <span style="font-size:.7rem;color:#6B7280;font-weight:400"><?= $combo['total_items'] ?> producto(s)</span>
      </button>
    </form>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Filtros -->
<form method="GET" action="<?= BASE_URL ?>carrito/index" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
  <input type="text" name="buscar" placeholder="Buscar producto..." value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>"
         style="flex:1;min-width:160px;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
  <select name="categoria_id" style="padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
    <option value="">Todas las categorías</option>
    <?php foreach ($categorias as $cat): ?>
    <option value="<?= $cat['id'] ?>" <?= ($filtros['categoria_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" style="padding:8px 16px;background:#374151;color:#fff;border:none;border-radius:8px;font-size:.875rem;cursor:pointer">Filtrar</button>
</form>

<!-- Layout: productos (izquierda) + ticket (derecha) -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

  <!-- IZQUIERDA: selección de productos -->
  <div>
    <?php if (!empty($carritoItems)): ?>
    <div style="background:#FFFBEB;border:1px solid #FCD34D;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:.85rem;color:#92400E;display:flex;align-items:center;justify-content:space-between">
      <span>Tienes <?= count($carritoItems) ?> producto(s) en el pedido. Ajusta las cantidades abajo.</span>
      <a href="<?= BASE_URL ?>carrito/vaciar" style="color:#B45309;font-weight:600;text-decoration:underline">Vaciar</a>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>carrito/actualizar" id="carritoForm">
      <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:16px">
        <table style="width:100%;border-collapse:collapse;font-size:.875rem">
          <thead>
            <tr style="background:#F9FAFB">
              <th style="padding:12px 16px;text-align:left;color:#6B7280;font-weight:600">Producto</th>
              <th style="padding:12px;text-align:center;color:#6B7280;font-weight:600">Precio</th>
              <th style="padding:12px;text-align:center;color:#6B7280;font-weight:600;min-width:160px">Cantidad</th>
              <th style="padding:12px;text-align:right;color:#6B7280;font-weight:600">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($productos as $prod): ?>
            <?php $prev = $carritoItems[$prod['id']]['cantidad'] ?? 0; ?>
            <tr style="border-top:1px solid #F3F4F6;transition:background .15s" id="row-<?= $prod['id'] ?>"
                <?= $prev > 0 ? 'style="border-top:1px solid #F3F4F6;background:#FFF7F7"' : '' ?>>
              <td style="padding:12px 16px">
                <?php if (!empty($prod['imagen'])): ?>
                <img src="<?= htmlspecialchars($prod['imagen']) ?>" alt="" style="width:36px;height:36px;border-radius:6px;object-fit:cover;float:left;margin-right:10px">
                <?php endif; ?>
                <div style="font-weight:600;color:#111827"><?= htmlspecialchars($prod['nombre']) ?></div>
                <div style="font-size:.75rem;color:#9CA3AF"><?= htmlspecialchars($prod['categoria_nombre'] ?? '') ?> · <?= $prod['presentacion'] ?></div>
                <?php if (!empty($prod['tiene_escalonados'])): ?>
                <div style="font-size:.7rem;color:#059669;font-weight:600;margin-top:2px">🏷 Descuento por volumen disponible</div>
                <?php endif; ?>
                <?php if (!empty($limitePorProducto[$prod['id']])): ?>
                <?php $lim = $limitePorProducto[$prod['id']]; $perC = ['por_pedido'=>'/pedido','semanal'=>'/semana','mensual'=>'/mes'][$lim['periodo']] ?? ''; ?>
                <div style="font-size:.68rem;font-weight:700;color:#92400E;margin-top:2px">🔒 Máx. <?= number_format($lim['limite_kg'],0) ?> kg <?= $perC ?></div>
                <?php endif; ?>
                <!-- Alerta de descuento por tramo -->
                <div id="alert-<?= $prod['id'] ?>" style="display:none;margin-top:4px;font-size:.72rem;padding:3px 8px;border-radius:6px;font-weight:600"></div>
              </td>
              <td style="padding:12px;text-align:center">
                <div id="precio-display-<?= $prod['id'] ?>" style="color:var(--color-primary);font-weight:700">
                  $<?= number_format($prod['precio_base'], 2) ?>
                </div>
                <div style="font-size:.7rem;color:#9CA3AF;font-weight:400">por <?= $prod['presentacion'] ?></div>
              </td>
              <td style="padding:12px;text-align:center">
                <!-- Controles +/- -->
                <div style="display:flex;align-items:center;justify-content:center;gap:4px">
                  <button type="button" onclick="cambiarCantidad(<?= $prod['id'] ?>, -0.5, <?= $prod['precio_base'] ?>, '<?= htmlspecialchars($prod['nombre']) ?>', '<?= $prod['presentacion'] ?>')"
                          style="width:30px;height:30px;border:1px solid #D1D5DB;border-radius:6px;background:#F9FAFB;cursor:pointer;font-size:1rem;font-weight:700;color:#374151;display:flex;align-items:center;justify-content:center;font-family:inherit;flex-shrink:0">−</button>
                  <input type="number" name="cantidad[<?= $prod['id'] ?>]"
                         id="qty-<?= $prod['id'] ?>"
                         value="<?= $prev > 0 ? $prev : '' ?>"
                         min="0" step="0.5"
                         <?= !empty($limitePorProducto[$prod['id']]) && $limitePorProducto[$prod['id']]['limite_kg'] ? 'max="'.htmlspecialchars($limitePorProducto[$prod['id']]['limite_kg']).'"' : '' ?>
                         placeholder="0"
                         oninput="actualizarFila(<?= $prod['id'] ?>, <?= $prod['precio_base'] ?>, '<?= htmlspecialchars($prod['nombre']) ?>', '<?= $prod['presentacion'] ?>')"
                         style="width:70px;padding:6px 8px;border:1px solid #D1D5DB;border-radius:6px;text-align:center;font-size:.875rem">
                  <button type="button" onclick="cambiarCantidad(<?= $prod['id'] ?>, +0.5, <?= $prod['precio_base'] ?>, '<?= htmlspecialchars($prod['nombre']) ?>', '<?= $prod['presentacion'] ?>')"
                          style="width:30px;height:30px;border:1px solid #D1D5DB;border-radius:6px;background:#F9FAFB;cursor:pointer;font-size:1rem;font-weight:700;color:#374151;display:flex;align-items:center;justify-content:center;font-family:inherit;flex-shrink:0">+</button>
                </div>
              </td>
              <td style="padding:12px;text-align:right;font-weight:700;color:#111827" id="sub-<?= $prod['id'] ?>">
                <?= $prev > 0 ? '$' . number_format($carritoItems[$prod['id']]['subtotal'] ?? 0, 2) : '—' ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px">
        <a href="<?= BASE_URL ?>catalogo/index" style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
          ← Catálogo
        </a>
        <button type="submit" style="padding:10px 24px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
          Continuar →
        </button>
      </div>
    </form>
  </div>

  <!-- DERECHA: ticket del pedido -->
  <div style="position:sticky;top:20px">
    <div style="background:#fff;border-radius:12px;border:2px dashed #E5E7EB;overflow:hidden">
      <!-- Cabecera del ticket -->
      <div style="background:var(--color-primary);padding:14px 16px;text-align:center">
        <div style="color:#fff;font-weight:700;font-size:.9rem;letter-spacing:.05em">MI PEDIDO</div>
        <div style="color:rgba(255,255,255,.7);font-size:.72rem;margin-top:2px">Selecciona cantidades a la izquierda</div>
      </div>

      <div style="border-bottom:2px dashed #E5E7EB;margin:0"></div>

      <!-- Items del ticket -->
      <div id="ticket-body" style="padding:12px 16px;min-height:120px">
        <div id="ticket-empty" style="text-align:center;padding:20px 0;color:#9CA3AF;font-size:.82rem">
          <div style="font-size:1.5rem;margin-bottom:6px">🛒</div>
          <div>Agrega productos para<br>ver tu pedido aquí</div>
        </div>
        <div id="ticket-items" style="display:none"></div>
      </div>

      <div style="border-bottom:2px dashed #E5E7EB;margin:0"></div>

      <!-- Ahorro total -->
      <div id="ticket-ahorro-box" style="display:none;padding:8px 16px;background:#F0FDF4;border-bottom:1px solid #A7F3D0">
        <div style="display:flex;justify-content:space-between;font-size:.78rem;color:#059669;font-weight:700">
          <span>🏷 Ahorro por volumen</span>
          <span id="ticket-ahorro">$0.00</span>
        </div>
      </div>

      <!-- Total -->
      <div style="padding:12px 16px">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span style="font-weight:700;color:#111827;font-size:.9rem">TOTAL</span>
          <span id="ticket-total" style="font-weight:800;color:var(--color-primary);font-size:1.1rem">$0.00</span>
        </div>
        <div style="font-size:.7rem;color:#9CA3AF;margin-top:4px;text-align:right">Sin IVA · Precio estimado</div>
      </div>

      <!-- Footer ticket -->
      <div style="background:#F9FAFB;padding:10px 16px;text-align:center;border-top:1px solid #F3F4F6">
        <div style="font-size:.7rem;color:#9CA3AF">Precio final sujeto a confirmación del proveedor</div>
      </div>
    </div>

    <!-- Alertas de descuento globales -->
    <div id="ticket-alertas" style="margin-top:10px"></div>
  </div>

</div><!-- /grid -->

<script>
const carritoInicial = <?= json_encode(array_values($carritoItems)) ?>;
const preciosProductos = {};
let debTimers = {};
// Mapa de escalonados por producto (se llena con la API)
const escalonadosMap = {};
// Límites de compra por producto_id
const limitesProducto = <?= json_encode($limitePorProducto ?? []) ?>;

carritoInicial.forEach(item => {
  preciosProductos[item.producto_id] = {
    precio: item.precio,
    precioBase: item.precio,
    nombre: item.nombre,
    presentacion: item.presentacion,
    cantidad: item.cantidad,
    subtotal: item.subtotal
  };
});

function cambiarCantidad(id, delta, precioBase, nombre, presentacion) {
  const input = document.getElementById('qty-' + id);
  const val = parseFloat(input.value) || 0;
  let nuevo = Math.max(0, Math.round((val + delta) * 2) / 2);
  const lim = limitesProducto[id];
  if (lim && lim.limite_kg && nuevo > parseFloat(lim.limite_kg)) {
    nuevo = parseFloat(lim.limite_kg);
  }
  input.value = nuevo || '';
  actualizarFila(id, precioBase, nombre, presentacion);
}

function actualizarFila(id, precioBase, nombre, presentacion) {
  const qty = parseFloat(document.getElementById('qty-'+id).value) || 0;
  const row = document.getElementById('row-'+id);
  const sub = document.getElementById('sub-'+id);

  if (qty <= 0) {
    row.style.background = '';
    sub.textContent = '—';
    document.getElementById('precio-display-'+id).innerHTML =
      '$' + precioBase.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('alert-'+id).style.display = 'none';
    delete preciosProductos[id];
    renderTicket();
    return;
  }

  row.style.background = '#FFF7F7';
  sub.textContent = '...';

  clearTimeout(debTimers[id]);
  debTimers[id] = setTimeout(() => {
    fetch('<?= BASE_URL ?>api/precios/'+id+'?cantidad='+qty)
      .then(r => r.json())
      .then(d => {
        const precio = d.precio || precioBase;
        const subtotal = precio * qty;
        sub.textContent = '$' + subtotal.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});

        // Mostrar precio actualizado con descuento si aplica
        const precioEl = document.getElementById('precio-display-'+id);
        if (precio < precioBase) {
          precioEl.innerHTML =
            '<span style="text-decoration:line-through;color:#9CA3AF;font-size:.8rem;font-weight:400">$' +
            precioBase.toLocaleString('es-MX', {minimumFractionDigits:2}) + '</span> ' +
            '<span style="color:#059669">$' + precio.toLocaleString('es-MX', {minimumFractionDigits:2}) + '</span>';
        } else {
          precioEl.innerHTML = '<span style="color:var(--color-primary);font-weight:700">$' +
            precio.toLocaleString('es-MX', {minimumFractionDigits:2}) + '</span>';
        }

        preciosProductos[id] = { precio, precioBase, nombre, presentacion, cantidad: qty, subtotal };

        // Filtrar tramos por límite antes de mostrar alertas
        const limProd = limitesProducto[id];
        const maxKgProd = limProd?.limite_kg ? parseFloat(limProd.limite_kg) : Infinity;
        const escFiltrados = (d.escalonados || []).filter(t => parseFloat(t.cantidad_desde || t.cantidad_min || 0) <= maxKgProd);
        if (d.escalonados) escalonadosMap[id] = escFiltrados;
        mostrarAlertaDescuento(id, qty, precio, precioBase, escFiltrados);

        renderTicket();
      })
      .catch(() => {
        const subtotal = precioBase * qty;
        sub.textContent = '$' + subtotal.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
        preciosProductos[id] = { precio: precioBase, precioBase, nombre, presentacion, cantidad: qty, subtotal };
        renderTicket();
      });
  }, 350);
}

function mostrarAlertaDescuento(id, qty, precioActual, precioBase, escalonados) {
  const alertEl = document.getElementById('alert-'+id);
  if (!alertEl || !escalonados.length) { alertEl && (alertEl.style.display='none'); return; }

  // Ordenar por cantidad_desde
  const tiers = escalonados.slice().sort((a,b) => (a.cantidad_desde||0) - (b.cantidad_desde||0));

  // ¿Hay un tramo mejor al que se puede llegar?
  const nextTier = tiers.find(t => (t.cantidad_desde||0) > qty && t.precio < precioActual);

  if (nextTier) {
    const falta = (nextTier.cantidad_desde - qty).toFixed(1).replace('.0','');
    const ahorro = (precioActual - nextTier.precio).toFixed(2);
    alertEl.style.cssText = 'display:block;margin-top:4px;font-size:.72rem;padding:4px 8px;border-radius:6px;font-weight:600;background:#FFF7ED;color:#B45309;border:1px solid #FED7AA';
    alertEl.textContent = `🏷 Agrega ${falta} ${tiers[0]?.presentacion || 'kg'} más → $${nextTier.precio}/kg (ahorras $${ahorro}/kg)`;
  } else if (precioActual < precioBase) {
    const ahorro = (precioBase - precioActual).toFixed(2);
    alertEl.style.cssText = 'display:block;margin-top:4px;font-size:.72rem;padding:4px 8px;border-radius:6px;font-weight:600;background:#F0FDF4;color:#059669;border:1px solid #A7F3D0';
    alertEl.textContent = `✓ Precio por volumen aplicado — ahorras $${ahorro}/kg`;
  } else {
    alertEl.style.display = 'none';
  }
}

function renderTicket() {
  const items = Object.entries(preciosProductos).filter(([,v]) => v.cantidad > 0);
  const ticketItems = document.getElementById('ticket-items');
  const ticketEmpty = document.getElementById('ticket-empty');
  const ticketTotal = document.getElementById('ticket-total');
  const ahorroBox = document.getElementById('ticket-ahorro-box');
  const ahorroEl = document.getElementById('ticket-ahorro');

  if (items.length === 0) {
    ticketEmpty.style.display = 'block';
    ticketItems.style.display = 'none';
    ticketTotal.textContent = '$0.00';
    ahorroBox.style.display = 'none';
    return;
  }

  ticketEmpty.style.display = 'none';
  ticketItems.style.display = 'block';

  let total = 0, totalBase = 0, ahorroTotal = 0;
  let html = '';
  items.forEach(([id, item]) => {
    total += item.subtotal;
    totalBase += item.precioBase * item.cantidad;
    html += `
      <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:6px 0;border-bottom:1px solid #F9FAFB;font-size:.8rem">
        <div style="flex:1;padding-right:8px">
          <div style="font-weight:600;color:#111827;line-height:1.3">${item.nombre}</div>
          <div style="color:#9CA3AF;font-size:.7rem">${item.cantidad} ${item.presentacion} × $${item.precio.toLocaleString('es-MX', {minimumFractionDigits:2})}</div>
        </div>
        <div style="font-weight:700;color:#374151;white-space:nowrap">
          $${item.subtotal.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2})}
        </div>
      </div>`;
  });

  ahorroTotal = totalBase - total;
  ticketItems.innerHTML = html;
  ticketTotal.textContent = '$' + total.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});

  if (ahorroTotal > 0.01) {
    ahorroBox.style.display = 'block';
    ahorroEl.textContent = '-$' + ahorroTotal.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
  } else {
    ahorroBox.style.display = 'none';
  }

  // Alertas globales en el panel lateral
  renderAlertasGlobales();
}

function renderAlertasGlobales() {
  const cont = document.getElementById('ticket-alertas');
  let html = '';
  Object.entries(escalonadosMap).forEach(([id, tiers]) => {
    if (!preciosProductos[id]) return;
    const item = preciosProductos[id];
    const sorted = tiers.slice().sort((a,b) => (a.cantidad_desde||0) - (b.cantidad_desde||0));
    const next = sorted.find(t => (t.cantidad_desde||0) > item.cantidad && t.precio < item.precio);
    if (next) {
      const falta = (next.cantidad_desde - item.cantidad).toFixed(1).replace('.0','');
      html += `<div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:8px 12px;margin-bottom:6px;font-size:.78rem;color:#92400E">
        💡 <strong>${item.nombre}</strong>: agrega ${falta} ${item.presentacion} más → $${parseFloat(next.precio).toFixed(2)}/${item.presentacion}
      </div>`;
    }
  });
  cont.innerHTML = html;
}

renderTicket();
</script>
