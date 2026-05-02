<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="margin-bottom:16px"><a href="<?= BASE_URL ?>producto/index" style="font-size:.875rem;color:#6B7280;text-decoration:none">← Productos</a></div>

<div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:20px">
  <div>
    <h1 style="font-size:1.25rem;font-weight:700;margin:0"><?= htmlspecialchars($producto['nombre']) ?></h1>
    <div style="font-size:.875rem;color:#6B7280">Unidad: <?= $producto['presentacion'] ?> | Precio base: $<?= number_format($producto['precio_base'],2) ?></div>
  </div>
  <a href="<?= BASE_URL ?>producto/editar/<?= $producto['id'] ?>" class="btn btn-secondary btn-sm" style="margin-left:auto">Editar producto</a>
</div>

<?php if (!empty($flash)): ?>
<div class="toast success" style="margin-bottom:16px;position:relative;max-width:100%"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <!-- Tabla de rangos existentes -->
  <div class="card">
    <div class="card-title" style="margin-bottom:14px">Rangos de precio</div>
    <div class="table-container" style="margin-bottom:16px">
      <table class="precio-table">
        <thead><tr><th>Rango (kg)</th><th>Precio por kg</th><th>Total mínimo</th><th></th></tr></thead>
        <tbody id="preciosBody">
          <?php foreach ($producto['precios'] as $pe): ?>
          <tr id="row-<?= $pe['id'] ?>">
            <td>
              <?= number_format($pe['rango_min'],0) ?> –
              <?= $pe['rango_max'] ? number_format($pe['rango_max'],0) . ' kg' : 'más' ?>
            </td>
            <td style="font-weight:700">$<?= number_format($pe['precio_por_unidad'],2) ?></td>
            <td style="color:#6B7280">$<?= number_format($pe['rango_min'] * $pe['precio_por_unidad'],2) ?></td>
            <td>
              <button onclick="eliminarPrecio(<?= $pe['id'] ?>)" style="background:none;border:none;cursor:pointer;color:#EF4444">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p style="font-size:.75rem;color:#6B7280">Los precios se aplican automáticamente en el carrito según la cantidad total por producto.</p>
  </div>

  <!-- Formulario agregar rango -->
  <div class="card">
    <div class="card-title" style="margin-bottom:14px">+ Agregar rango</div>
    <div style="display:grid;gap:12px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label class="form-label">Desde (kg)</label>
          <input type="number" id="rangoMin" class="form-control" min="1" step="0.5" placeholder="1">
        </div>
        <div>
          <label class="form-label">Hasta (kg) — vacío = sin límite</label>
          <input type="number" id="rangoMax" class="form-control" min="1" step="0.5" placeholder="∞">
        </div>
      </div>
      <div>
        <label class="form-label">Precio por kg ($)</label>
        <input type="number" id="precioPorUnidad" class="form-control" step="0.01" placeholder="185.00">
      </div>
      <button onclick="guardarPrecio()" class="btn btn-primary btn-block">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Agregar rango
      </button>
    </div>
  </div>
</div>

<script>
const PRODUCTO_ID = <?= $producto['id'] ?>;

function guardarPrecio() {
  const data = {
    producto_id: PRODUCTO_ID,
    rango_min: document.getElementById('rangoMin').value,
    rango_max: document.getElementById('rangoMax').value,
    precio_por_unidad: document.getElementById('precioPorUnidad').value
  };
  if (!data.rango_min || !data.precio_por_unidad) { alert('Completa Desde y Precio por kg'); return; }

  fetch('<?= BASE_URL ?>producto/guardarPrecio', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams(data)
  }).then(r=>r.json()).then(d=>{ if(d.ok) location.reload(); });
}

function eliminarPrecio(id) {
  if (!confirm('¿Eliminar este rango?')) return;
  fetch('<?= BASE_URL ?>producto/eliminarPrecio/'+id, {method:'POST'})
    .then(r=>r.json()).then(d=>{ if(d.ok) document.getElementById('row-'+id)?.remove(); });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
