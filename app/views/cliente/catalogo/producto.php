<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
?>
<div style="margin-bottom:16px">
  <a href="<?= BASE_URL ?>producto/catalogo" style="font-size:.875rem;color:#6B7280;text-decoration:none">← Catálogo</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
  <!-- Product image -->
  <div style="border-radius:16px;overflow:hidden;background:#F3F4F6;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center">
    <?php if ($producto['imagen']): ?>
    <img src="<?= UPLOAD_URL ?>productos/<?= htmlspecialchars($producto['imagen']) ?>" style="width:100%;height:100%;object-fit:cover">
    <?php else: ?>
    <svg width="80" height="80" fill="none" viewBox="0 0 24 24" stroke="#D1D5DB"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    <?php endif; ?>
  </div>

  <!-- Product info -->
  <div>
    <div style="font-size:.8rem;color:#6B7280;margin-bottom:4px"><?= $producto['categoria_nombre'] ?></div>
    <h1 style="font-size:1.5rem;font-weight:800;margin:0 0 8px"><?= htmlspecialchars($producto['nombre']) ?></h1>
    <div style="font-size:.875rem;color:#6B7280;margin-bottom:16px;line-height:1.6">
      <?= htmlspecialchars($producto['descripcion'] ?? '') ?>
    </div>
    <div style="font-size:.75rem;font-weight:700;color:#374151;margin-bottom:8px;text-transform:uppercase">Presentación: Por <?= $producto['presentacion'] ?></div>

    <!-- Precios escalonados -->
    <div style="background:#F9FAFB;border-radius:10px;padding:14px;margin-bottom:16px">
      <div style="font-weight:700;font-size:.875rem;margin-bottom:10px">Precios escalonados</div>
      <table style="width:100%;font-size:.875rem">
        <thead>
          <tr style="color:#6B7280;font-size:.75rem">
            <th style="text-align:left;padding:4px 8px 4px 0">Rango (kg)</th>
            <th style="text-align:right;padding:4px 0">Precio por kg</th>
          </tr>
        </thead>
        <tbody id="preciosBody">
          <?php foreach ($producto['precios'] as $pe): ?>
          <tr id="pe-<?= $pe['id'] ?>" class="precio-row">
            <td style="padding:4px 8px 4px 0">
              <?= number_format($pe['rango_min'],0) ?> –
              <?= $pe['rango_max'] ? number_format($pe['rango_max'],0) . ' kg' : 'más' ?>
            </td>
            <td style="text-align:right;font-weight:600" data-precio="<?= $pe['precio_por_unidad'] ?>">
              $<?= number_format($pe['precio_por_unidad'],2) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Quantity + add to cart -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
      <div style="display:flex;align-items:center;border:1.5px solid #E5E7EB;border-radius:8px;overflow:hidden">
        <button onclick="cambiarCantidad(-1)" style="padding:8px 14px;background:none;border:none;cursor:pointer;font-size:1rem;font-weight:700">−</button>
        <input type="number" id="cantidadInput" value="20" min="1" step="1"
               style="width:60px;border:none;text-align:center;font-weight:700;font-size:1rem;outline:none"
               oninput="actualizarPrecio()">
        <button onclick="cambiarCantidad(1)" style="padding:8px 14px;background:none;border:none;cursor:pointer;font-size:1rem;font-weight:700">+</button>
      </div>
      <span style="font-size:.875rem;color:#6B7280">kg</span>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <div>
        <div style="font-size:.75rem;color:#6B7280">Precio actual</div>
        <div style="font-size:1.5rem;font-weight:800;color:#C8102E" id="precioActual">$<?= number_format($producto['precio_base'],2) ?></div>
      </div>
      <div style="text-align:right">
        <div style="font-size:.75rem;color:#6B7280">Subtotal</div>
        <div style="font-size:1.25rem;font-weight:700" id="subtotalActual">$<?= number_format($producto['precio_base'] * 20,2) ?></div>
      </div>
    </div>

    <button onclick="agregarAlCarrito()" class="btn btn-primary btn-block btn-lg">
      <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      Agregar al carrito
    </button>
  </div>
</div>

<script>
const PRODUCTO_ID = <?= $producto['id'] ?>;
const precios = <?= json_encode($producto['precios']) ?>;

function getPrecioParaCantidad(cant) {
  let precio = <?= $producto['precio_base'] ?>;
  for (const pe of precios) {
    if (cant >= pe.rango_min && (pe.rango_max === null || cant <= pe.rango_max)) {
      precio = parseFloat(pe.precio_por_unidad);
    }
  }
  return precio;
}

function actualizarPrecio() {
  const cant    = parseFloat(document.getElementById('cantidadInput').value) || 0;
  const precio  = getPrecioParaCantidad(cant);
  document.getElementById('precioActual').textContent  = '$' + precio.toLocaleString('es-MX', {minimumFractionDigits:2});
  document.getElementById('subtotalActual').textContent = '$' + (precio * cant).toLocaleString('es-MX', {minimumFractionDigits:2});

  // Highlight active row
  document.querySelectorAll('.precio-row').forEach(tr => tr.style.background = '');
  precios.forEach((pe, i) => {
    if (cant >= pe.rango_min && (pe.rango_max === null || cant <= pe.rango_max)) {
      const row = document.querySelectorAll('.precio-row')[i];
      if (row) row.style.background = '#FEF2F2';
    }
  });
}

function cambiarCantidad(delta) {
  const inp = document.getElementById('cantidadInput');
  inp.value = Math.max(1, (parseFloat(inp.value)||0) + delta);
  actualizarPrecio();
}

function agregarAlCarrito() {
  const cant = parseFloat(document.getElementById('cantidadInput').value) || 0;
  if (cant <= 0) { showToast('Ingresa una cantidad válida','error'); return; }

  // For simplicity, add to default sucursal (first)
  fetch('<?= BASE_URL ?>carrito/agregar', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      producto_id: PRODUCTO_ID,
      sucursales: { 0: cant }
    })
  }).then(r=>r.json()).then(d=>{
    if (d.ok) {
      showToast('Producto agregado al carrito', 'success');
      setTimeout(() => window.location = '<?= BASE_URL ?>carrito/index', 800);
    }
  });
}

actualizarPrecio();
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
