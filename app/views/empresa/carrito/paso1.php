<?php
// Vista: Paso 1 — Selección de productos y cantidades
$carritoItems = $carrito ?? [];
?>
<!-- Pasos del proceso -->
<div style="display:flex;align-items:center;gap:0;margin-bottom:24px;font-size:.8rem">
  <?php
  $pasos = ['1'=>'Productos','2'=>'Sucursales','3'=>'Resumen','4'=>'Confirmado'];
  foreach ($pasos as $num => $label):
    $activo = $num === '1';
    $hecho  = false;
  ?>
  <div style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:<?= $activo ? 'var(--color-primary)' : '#E5E7EB' ?>;color:<?= $activo ? '#fff' : '#9CA3AF' ?>;<?= $num === '1' ? 'border-radius:8px 0 0 8px' : ($num === '4' ? 'border-radius:0 8px 8px 0' : '') ?>">
    <span style="font-weight:700"><?= $num ?></span>
    <?= $label ?>
  </div>
  <?php if ($num < '4'): ?>
  <div style="width:0;height:0;border-top:18px solid transparent;border-bottom:18px solid transparent;border-left:10px solid <?= $activo ? 'var(--color-primary)' : '#E5E7EB' ?>"></div>
  <?php endif; ?>
  <?php endforeach; ?>
</div>

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

<!-- Indicador de ítems en carrito -->
<?php if (!empty($carritoItems)): ?>
<div style="background:#FFFBEB;border:1px solid #FCD34D;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:.85rem;color:#92400E;display:flex;align-items:center;justify-content:space-between">
  <span>Tienes <?= count($carritoItems) ?> producto(s) en el pedido. Puedes ajustar cantidades abajo.</span>
  <a href="<?= BASE_URL ?>carrito/vaciar" style="color:#B45309;font-weight:600;text-decoration:underline">Vaciar</a>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>carrito/actualizar" id="carritoForm">
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:16px">
    <table style="width:100%;border-collapse:collapse;font-size:.875rem">
      <thead>
        <tr style="background:#F9FAFB">
          <th style="padding:12px 16px;text-align:left;color:#6B7280;font-weight:600">Producto</th>
          <th style="padding:12px;text-align:center;color:#6B7280;font-weight:600">Precio base</th>
          <th style="padding:12px;text-align:center;color:#6B7280;font-weight:600">Stock</th>
          <th style="padding:12px;text-align:center;color:#6B7280;font-weight:600;min-width:130px">Cantidad</th>
          <th style="padding:12px;text-align:right;color:#6B7280;font-weight:600">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($productos as $prod): ?>
        <?php $prev = $carritoItems[$prod['id']]['cantidad'] ?? 0; ?>
        <tr style="border-top:1px solid #F3F4F6" id="row-<?= $prod['id'] ?>">
          <td style="padding:12px 16px">
            <div style="font-weight:600;color:#111827"><?= htmlspecialchars($prod['nombre']) ?></div>
            <div style="font-size:.75rem;color:#9CA3AF"><?= htmlspecialchars($prod['categoria_nombre']) ?> · <?= $prod['presentacion'] ?></div>
          </td>
          <td style="padding:12px;text-align:center;color:var(--color-primary);font-weight:700">
            $<?= number_format($prod['precio_base'], 2) ?>
          </td>
          <td style="padding:12px;text-align:center">
            <?php if ($prod['stock'] !== null): ?>
            <span style="color:<?= $prod['stock'] <= ($prod['umbral_minimo'] ?? 0) ? '#EF4444' : '#6B7280' ?>;font-size:.8rem">
              <?= number_format($prod['stock'], 1) ?> <?= $prod['presentacion'] ?>
            </span>
            <?php else: ?>
            <span style="color:#9CA3AF;font-size:.8rem">—</span>
            <?php endif; ?>
          </td>
          <td style="padding:12px;text-align:center">
            <input type="number" name="cantidad[<?= $prod['id'] ?>]"
                   id="qty-<?= $prod['id'] ?>"
                   value="<?= $prev > 0 ? $prev : '' ?>"
                   min="0" step="0.5"
                   placeholder="0"
                   onchange="calcularFila(<?= $prod['id'] ?>, <?= $prod['precio_base'] ?>)"
                   style="width:100px;padding:6px 10px;border:1px solid #D1D5DB;border-radius:6px;text-align:center;font-size:.875rem">
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
      Continuar con sucursales →
    </button>
  </div>
</form>

<script>
function calcularFila(id, precioBase) {
  const qty = parseFloat(document.getElementById('qty-'+id).value) || 0;
  const sub = document.getElementById('sub-'+id);
  if (qty > 0) {
    // Precio escalonado via AJAX
    fetch('<?= BASE_URL ?>api/precios/'+id+'?cantidad='+qty)
      .then(r => r.json())
      .then(d => {
        const precio = d.precio || precioBase;
        sub.textContent = '$' + (precio * qty).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
      })
      .catch(() => {
        sub.textContent = '$' + (precioBase * qty).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
      });
  } else {
    sub.textContent = '—';
  }
}
</script>
