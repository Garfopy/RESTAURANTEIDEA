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
<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

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
              <th style="padding:12px;text-align:center;color:#6B7280;font-weight:600;min-width:120px">Cantidad</th>
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
                <div style="font-size:.7rem;color:#059669;font-weight:600;margin-top:2px">Precio por volumen disponible</div>
                <?php endif; ?>
              </td>
              <td style="padding:12px;text-align:center;color:var(--color-primary);font-weight:700">
                $<?= number_format($prod['precio_base'], 2) ?>
                <div style="font-size:.7rem;color:#9CA3AF;font-weight:400">por <?= $prod['presentacion'] ?></div>
              </td>
              <td style="padding:12px;text-align:center">
                <input type="number" name="cantidad[<?= $prod['id'] ?>]"
                       id="qty-<?= $prod['id'] ?>"
                       value="<?= $prev > 0 ? $prev : '' ?>"
                       min="0" step="0.5"
                       placeholder="0"
                       onchange="actualizarFila(<?= $prod['id'] ?>, <?= $prod['precio_base'] ?>, '<?= htmlspecialchars($prod['nombre']) ?>', '<?= $prod['presentacion'] ?>')"
                       style="width:90px;padding:6px 10px;border:1px solid #D1D5DB;border-radius:6px;text-align:center;font-size:.875rem">
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

      <!-- Línea punteada decorativa -->
      <div style="border-bottom:2px dashed #E5E7EB;margin:0"></div>

      <!-- Items del ticket -->
      <div id="ticket-body" style="padding:12px 16px;min-height:120px">
        <!-- Estado vacío -->
        <div id="ticket-empty" style="text-align:center;padding:20px 0;color:#9CA3AF;font-size:.82rem">
          <div style="font-size:1.5rem;margin-bottom:6px">🛒</div>
          <div>Agrega productos para<br>ver tu pedido aquí</div>
        </div>
        <!-- Ítems (generados por JS) -->
        <div id="ticket-items" style="display:none"></div>
      </div>

      <!-- Línea punteada -->
      <div style="border-bottom:2px dashed #E5E7EB;margin:0"></div>

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
  </div>

</div><!-- /grid -->

<script>
// Datos del carrito inicial (desde sesión PHP)
const carritoInicial = <?= json_encode(array_values($carritoItems)) ?>;
const preciosProductos = {};

// Inicializar desde el carrito existente
carritoInicial.forEach(item => {
  preciosProductos[item.producto_id] = {
    precio: item.precio,
    nombre: item.nombre,
    presentacion: item.presentacion,
    cantidad: item.cantidad,
    subtotal: item.subtotal
  };
});

function actualizarFila(id, precioBase, nombre, presentacion) {
  const qty = parseFloat(document.getElementById('qty-'+id).value) || 0;
  const row = document.getElementById('row-'+id);
  const sub = document.getElementById('sub-'+id);

  if (qty > 0) {
    row.style.background = '#FFF7F7';
    fetch('<?= BASE_URL ?>api/precios/'+id+'?cantidad='+qty)
      .then(r => r.json())
      .then(d => {
        const precio = d.precio || precioBase;
        const subtotal = precio * qty;
        sub.textContent = '$' + subtotal.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
        preciosProductos[id] = { precio, nombre, presentacion, cantidad: qty, subtotal };
        renderTicket();
      })
      .catch(() => {
        const subtotal = precioBase * qty;
        sub.textContent = '$' + subtotal.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
        preciosProductos[id] = { precio: precioBase, nombre, presentacion, cantidad: qty, subtotal };
        renderTicket();
      });
  } else {
    row.style.background = '';
    sub.textContent = '—';
    delete preciosProductos[id];
    renderTicket();
  }
}

function renderTicket() {
  const items = Object.entries(preciosProductos).filter(([,v]) => v.cantidad > 0);
  const ticketItems = document.getElementById('ticket-items');
  const ticketEmpty = document.getElementById('ticket-empty');
  const ticketTotal = document.getElementById('ticket-total');

  if (items.length === 0) {
    ticketEmpty.style.display = 'block';
    ticketItems.style.display = 'none';
    ticketTotal.textContent = '$0.00';
    return;
  }

  ticketEmpty.style.display = 'none';
  ticketItems.style.display = 'block';

  let total = 0;
  let html = '';
  items.forEach(([id, item]) => {
    total += item.subtotal;
    html += `
      <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:6px 0;border-bottom:1px solid #F9FAFB;font-size:.8rem">
        <div style="flex:1;padding-right:8px">
          <div style="font-weight:600;color:#111827;line-height:1.3">${item.nombre}</div>
          <div style="color:#9CA3AF;font-size:.7rem">${item.cantidad} ${item.presentacion}</div>
        </div>
        <div style="font-weight:700;color:#374151;white-space:nowrap">
          $${item.subtotal.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2})}
        </div>
      </div>`;
  });

  ticketItems.innerHTML = html;
  ticketTotal.textContent = '$' + total.toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
}

// Renderizar ticket con datos iniciales de sesión
renderTicket();
</script>
