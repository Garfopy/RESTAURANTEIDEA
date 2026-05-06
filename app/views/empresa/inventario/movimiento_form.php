<?php
$baseUrl = BASE_URL;
$tipoColor = match ($tipo) {
    'entrada' => '#059669',
    'salida'  => '#DC2626',
    'merma'   => '#D97706',
    default   => '#374151',
};
$tipoLabel = match ($tipo) {
    'entrada' => 'Entrada de Stock',
    'salida'  => 'Salida de Stock',
    'merma'   => 'Registro de Merma',
    default   => 'Movimiento',
};
$tipoDesc = match ($tipo) {
    'entrada' => 'Registra una compra a proveedor, transferencia u otra entrada al almacén.',
    'salida'  => 'Registra salida de productos (ventas directas, préstamos, etc.).',
    'merma'   => 'Registra productos perdidos por vencimiento, daño u otra causa.',
    default   => '',
};
?>

<!-- Flash -->
<?php if ($flash): ?>
<div style="margin-bottom:16px;padding:12px 16px;border-radius:8px;font-size:.875rem;font-weight:500;
  <?= $flash['type'] === 'success' ? 'background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0' : 'background:#FEE2E2;color:#991B1B;border:1px solid #FECACA' ?>">
  <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<div style="max-width:600px">

  <!-- Selector de tipo -->
  <div style="display:flex;gap:10px;margin-bottom:24px">
    <?php foreach (['entrada' => ['#059669','#D1FAE5','Entrada'], 'salida' => ['#DC2626','#FEE2E2','Salida'], 'merma' => ['#D97706','#FEF3C7','Merma']] as $t => [$c, $bg, $label]): ?>
    <a href="<?= $baseUrl ?>empresa-inventario/movimiento/<?= $t ?>"
       style="flex:1;padding:12px;text-align:center;border-radius:10px;text-decoration:none;font-weight:700;font-size:.85rem;
         <?= $tipo === $t ? "background:$c;color:#fff;border:2px solid $c" : "background:$bg;color:$c;border:2px solid $c" ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Descripción -->
  <p style="font-size:.85rem;color:#6B7280;margin-bottom:24px;padding:12px 16px;background:#F9FAFB;border-radius:8px;border-left:4px solid <?= $tipoColor ?>">
    <?= $tipoDesc ?>
  </p>

  <!-- Formulario -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:28px">
    <form method="POST" action="<?= $baseUrl ?>empresa-inventario/guardarMovimiento">
      <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">

      <!-- Producto -->
      <div style="margin-bottom:18px">
        <label style="display:block;font-size:.85rem;font-weight:700;color:#374151;margin-bottom:8px">
          Producto <span style="color:#DC2626">*</span>
        </label>
        <select name="producto_id" id="selectProducto" required onchange="actualizarStock(this)"
                style="width:100%;padding:10px 14px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;background:#fff">
          <option value="">— Selecciona un producto —</option>
          <?php foreach ($productos as $p): ?>
          <option value="<?= $p['id'] ?>"
                  data-stock="<?= number_format((float)$p['stock_actual'], 1) ?>"
                  data-unidad="<?= htmlspecialchars($p['presentacion']) ?>"
                  data-nombre="<?= htmlspecialchars($p['nombre']) ?>">
            <?= htmlspecialchars($p['nombre']) ?> — Stock: <?= number_format((float)$p['stock_actual'], 1) ?> <?= $p['presentacion'] ?>
          </option>
          <?php endforeach; ?>
        </select>
        <!-- Stock actual del producto seleccionado -->
        <div id="stockInfo" style="margin-top:6px;font-size:.8rem;color:#6B7280;display:none">
          Stock actual: <strong id="stockValor"></strong>
        </div>
      </div>

      <!-- Cantidad -->
      <div style="margin-bottom:18px">
        <label style="display:block;font-size:.85rem;font-weight:700;color:#374151;margin-bottom:8px">
          Cantidad <span style="color:#DC2626">*</span>
        </label>
        <input type="number" name="cantidad" id="inputCantidad" min="0.01" step="0.01" required
               onchange="calcularNuevoStock()"
               style="width:100%;padding:10px 14px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box">
        <div id="nuevoStockInfo" style="margin-top:6px;font-size:.8rem;color:#6B7280;display:none">
          Stock resultante: <strong id="nuevoStockValor" style="color:<?= $tipoColor ?>"></strong>
        </div>
      </div>

      <!-- Motivo -->
      <div style="margin-bottom:18px">
        <label style="display:block;font-size:.85rem;font-weight:700;color:#374151;margin-bottom:8px">
          Motivo <?= $tipo !== 'merma' ? '' : '<span style="color:#DC2626">*</span>' ?>
        </label>
        <input type="text" name="motivo"
               placeholder="<?= $tipo === 'entrada' ? 'Ej: Compra a Proveedor ABC' : ($tipo === 'salida' ? 'Ej: Venta directa local' : 'Ej: Producto vencido') ?>"
               <?= $tipo === 'merma' ? 'required' : '' ?>
               style="width:100%;padding:10px 14px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box">
      </div>

      <!-- Referencia -->
      <div style="margin-bottom:24px">
        <label style="display:block;font-size:.85rem;font-weight:700;color:#374151;margin-bottom:8px">
          Referencia (opcional)
        </label>
        <input type="text" name="referencia"
               placeholder="<?= $tipo === 'entrada' ? 'Ej: Factura A-0234, remisión, folio...' : 'Ej: Número de pedido, folio...' ?>"
               style="width:100%;padding:10px 14px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box">
        <p style="margin-top:4px;font-size:.75rem;color:#9CA3AF">Útil para rastrear el origen o destino del movimiento</p>
      </div>

      <div style="display:flex;gap:12px">
        <button type="submit"
                style="flex:1;padding:12px;background:<?= $tipoColor ?>;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:.9rem;cursor:pointer">
          Registrar <?= $tipoLabel ?>
        </button>
        <a href="<?= $baseUrl ?>empresa-inventario"
           style="padding:12px 20px;border:1px solid #D1D5DB;border-radius:8px;color:#374151;text-decoration:none;font-size:.875rem;display:flex;align-items:center">
          Cancelar
        </a>
      </div>
    </form>
  </div>
</div>

<script>
function actualizarStock(select) {
  const opt = select.options[select.selectedIndex];
  const stock = opt.dataset.stock;
  const unidad = opt.dataset.unidad;
  if (opt.value) {
    document.getElementById('stockInfo').style.display = 'block';
    document.getElementById('stockValor').textContent = stock + ' ' + unidad;
    document.getElementById('stockValor').dataset.valor = stock;
    document.getElementById('stockValor').dataset.unidad = unidad;
  } else {
    document.getElementById('stockInfo').style.display = 'none';
    document.getElementById('nuevoStockInfo').style.display = 'none';
  }
  calcularNuevoStock();
}

function calcularNuevoStock() {
  const select   = document.getElementById('selectProducto');
  const cantidad = parseFloat(document.getElementById('inputCantidad').value) || 0;
  const opt      = select.options[select.selectedIndex];
  if (!opt || !opt.value || cantidad <= 0) {
    document.getElementById('nuevoStockInfo').style.display = 'none';
    return;
  }
  const stockActual = parseFloat(opt.dataset.stock) || 0;
  const unidad      = opt.dataset.unidad;
  const tipo        = '<?= $tipo ?>';
  let nuevo;
  if (tipo === 'entrada') nuevo = stockActual + cantidad;
  else nuevo = Math.max(0, stockActual - cantidad);
  document.getElementById('nuevoStockInfo').style.display = 'block';
  document.getElementById('nuevoStockValor').textContent = nuevo.toFixed(1) + ' ' + unidad;
}
</script>
