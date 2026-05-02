<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
?>
<!-- Checkout steps -->
<div class="checkout-steps" style="margin-bottom:24px">
  <div class="step active"><div class="step-number">1</div><span class="hide-mobile">Carrito</span></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-number">2</div><span class="hide-mobile">Entrega</span></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-number">3</div><span class="hide-mobile">Resumen</span></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-number">4</div><span class="hide-mobile">Confirmación</span></div>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
  <h1 style="font-size:1.1rem;font-weight:700;margin:0">Distribuye por sucursal</h1>
  <a href="<?= BASE_URL ?>producto/catalogo" class="btn btn-sm btn-secondary">+ Agregar producto</a>
</div>

<?php if (!empty($flash)): ?>
<div class="toast success" style="margin-bottom:12px;position:relative;max-width:100%"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<?php if (empty($items)): ?>
<div class="card" style="text-align:center;padding:48px">
  <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#D1D5DB" style="margin:0 auto 12px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
  <p style="color:#9CA3AF">Tu carrito está vacío</p>
  <a href="<?= BASE_URL ?>producto/catalogo" class="btn btn-primary" style="margin-top:12px">Ver catálogo</a>
</div>
<?php else: ?>

<!-- Multi-branch cart table -->
<div class="card" style="padding:0;overflow-x:auto;margin-bottom:16px">
  <table class="carrito-table" id="carritoTable">
    <thead>
      <tr>
        <th style="min-width:160px">Producto</th>
        <?php foreach ($sucursales as $s): ?>
        <th class="sucursal-col" style="min-width:100px"><?= htmlspecialchars($s['nombre']) ?></th>
        <?php endforeach; ?>
        <th style="min-width:80px">Total kg</th>
        <th style="min-width:100px">Precio/kg</th>
        <th style="min-width:100px">Subtotal</th>
        <th style="width:40px"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr data-producto="<?= $item['producto']['id'] ?>">
        <td>
          <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($item['producto']['nombre']) ?></div>
          <div style="font-size:.75rem;color:#6B7280"><?= $item['producto']['categoria_nombre'] ?></div>
        </td>
        <?php foreach ($sucursales as $s): ?>
        <td class="cantidad-input">
          <input type="number"
                 data-producto="<?= $item['producto']['id'] ?>"
                 data-sucursal="<?= $s['id'] ?>"
                 value="<?= $item['sucursales'][$s['id']] ?? 0 ?>"
                 min="0" step="1"
                 onchange="actualizarCantidad(<?= $item['producto']['id'] ?>, <?= $s['id'] ?>, this.value)">
        </td>
        <?php endforeach; ?>
        <td style="font-weight:700" id="total-<?= $item['producto']['id'] ?>"><?= number_format($item['cantidad_total'],0) ?> kg</td>
        <td style="color:#C8102E;font-weight:600" id="precio-<?= $item['producto']['id'] ?>">$<?= number_format($item['precio'],2) ?></td>
        <td style="font-weight:700" id="sub-<?= $item['producto']['id'] ?>">$<?= number_format($item['subtotal'],0,'.', ',') ?></td>
        <td>
          <button onclick="eliminarItem(<?= $item['producto']['id'] ?>)" style="background:none;border:none;cursor:pointer;color:#EF4444">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Totals by branch -->
<div style="display:flex;gap:10px;overflow-x:auto;margin-bottom:20px;padding-bottom:4px">
  <?php foreach ($sucursales as $s):
    $sucTotal = 0;
    foreach ($items as $item) {
      $cant = $item['sucursales'][$s['id']] ?? 0;
      $sucTotal += $cant * $item['precio'];
    }
    if ($sucTotal <= 0) continue;
  ?>
  <div style="background:#EFF6FF;border-radius:10px;padding:12px 16px;min-width:140px;flex-shrink:0">
    <div style="font-size:.75rem;color:#1E40AF;font-weight:600"><?= htmlspecialchars($s['nombre']) ?></div>
    <div style="font-size:1rem;font-weight:700;color:#1D4ED8">$<?= number_format($sucTotal,0,'.', ',') ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div>
    <div style="font-size:.875rem;color:#6B7280">Total del pedido</div>
    <div style="font-size:1.5rem;font-weight:800;color:#111827">$<?= number_format($total,0,'.', ',') ?></div>
  </div>
  <a href="<?= BASE_URL ?>carrito/entrega" class="btn btn-primary btn-lg">Continuar →</a>
</div>

<?php endif; ?>

<!-- Bottom nav mobile -->
<nav class="bottom-nav hide-desktop">
  <a href="<?= BASE_URL ?>carrito/inicio" class="bottom-nav-item">🏠 <span>Inicio</span></a>
  <a href="<?= BASE_URL ?>producto/catalogo" class="bottom-nav-item">📦 <span>Catálogo</span></a>
  <a href="<?= BASE_URL ?>pedido/index" class="bottom-nav-item">📋 <span>Pedidos</span></a>
  <a href="<?= BASE_URL ?>carrito/index" class="bottom-nav-item active">🛒 <span>Carrito</span></a>
  <a href="<?= BASE_URL ?>auth/logout" class="bottom-nav-item">👤 <span>Cuenta</span></a>
</nav>

<script>
function actualizarCantidad(productoId, sucursalId, valor) {
  fetch('<?= BASE_URL ?>carrito/actualizar', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ producto_id: productoId, sucursal_id: sucursalId, cantidad: parseFloat(valor)||0 })
  }).then(r=>r.json()).then(d=>{ if(d.ok) recalcularFila(productoId); });
}

function recalcularFila(productoId) {
  const inputs = document.querySelectorAll(`input[data-producto="${productoId}"]`);
  let total = 0;
  inputs.forEach(i => total += parseFloat(i.value)||0);

  fetch(`<?= BASE_URL ?>api/precioEscalonado?producto_id=${productoId}&cantidad=${total}`)
    .then(r=>r.json()).then(d=>{
      document.getElementById(`total-${productoId}`).textContent = total + ' kg';
      document.getElementById(`precio-${productoId}`).textContent = '$' + parseFloat(d.precio).toFixed(2);
      document.getElementById(`sub-${productoId}`).textContent = '$' + parseFloat(d.subtotal).toLocaleString('es-MX');
    });
}

function eliminarItem(productoId) {
  if (!confirm('¿Quitar este producto del carrito?')) return;
  fetch('<?= BASE_URL ?>carrito/eliminar', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ producto_id: productoId })
  }).then(r=>r.json()).then(d=>{ if(d.ok) location.reload(); });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
