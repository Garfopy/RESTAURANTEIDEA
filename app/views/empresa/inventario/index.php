<?php
// Vista: Inventario (admin_empresa)
$baseUrl = BASE_URL;
?>

<!-- Toolbar -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <form method="GET" action="<?= $baseUrl ?>empresa-inventario/index" style="display:flex;gap:8px">
    <input type="text" name="buscar" value="<?= htmlspecialchars($filtros['buscar']) ?>"
           placeholder="Buscar producto..."
           style="padding:8px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem;width:220px">
    <label style="display:flex;align-items:center;gap:4px;font-size:.875rem;color:#374151">
      <input type="checkbox" name="stock_bajo" value="1" <?= $filtros['stock_bajo'] ? 'checked' : '' ?>> Solo stock bajo
    </label>
    <button type="submit" style="padding:8px 16px;background:var(--color-primary);color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.875rem">Filtrar</button>
  </form>
  <a href="<?= $baseUrl ?>empresa-producto/index"
     style="padding:8px 14px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem;color:#374151;text-decoration:none">
    Ir al catálogo
  </a>
</div>

<!-- Tabla de inventario -->
<div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden">
  <?php if (empty($items)): ?>
    <div style="padding:48px;text-align:center;color:#9CA3AF">Sin productos en inventario.</div>
  <?php else: ?>
  <table style="width:100%;border-collapse:collapse">
    <thead>
      <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
        <th style="padding:12px 16px;text-align:left;font-size:.75rem;color:#6B7280;font-weight:600;text-transform:uppercase">Producto</th>
        <th style="padding:12px 16px;text-align:right;font-size:.75rem;color:#6B7280;font-weight:600;text-transform:uppercase">Stock actual</th>
        <th style="padding:12px 16px;text-align:right;font-size:.75rem;color:#6B7280;font-weight:600;text-transform:uppercase">Umbral mínimo</th>
        <th style="padding:12px 16px;text-align:center;font-size:.75rem;color:#6B7280;font-weight:600;text-transform:uppercase">Estado</th>
        <th style="padding:12px 16px;text-align:center;font-size:.75rem;color:#6B7280;font-weight:600;text-transform:uppercase">Ajustar</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <?php
        $stock   = (float)($item['stock_actual'] ?? 0);
        $umbral  = (float)($item['umbral_minimo'] ?? 10);
        $color   = $stock <= 0 ? '#DC2626' : ($stock <= $umbral ? '#D97706' : '#059669');
        $label   = $stock <= 0 ? 'Sin stock' : ($stock <= $umbral ? 'Stock bajo' : 'OK');
        $bgLabel = $stock <= 0 ? '#FEE2E2' : ($stock <= $umbral ? '#FEF3C7' : '#D1FAE5');
        $txLabel = $stock <= 0 ? '#991B1B' : ($stock <= $umbral ? '#92400E' : '#065F46');
      ?>
      <tr style="border-bottom:1px solid #F3F4F6">
        <td style="padding:12px 16px">
          <div style="font-weight:600;color:#111827;font-size:.875rem"><?= htmlspecialchars($item['nombre']) ?></div>
          <div style="font-size:.75rem;color:#6B7280"><?= htmlspecialchars($item['presentacion']) ?> · <?= htmlspecialchars($item['categoria_nombre'] ?? '') ?></div>
        </td>
        <td style="padding:12px 16px;text-align:right">
          <span style="font-size:1rem;font-weight:700;color:<?= $color ?>"><?= number_format($stock, 1) ?></span>
          <span style="font-size:.75rem;color:#9CA3AF"> <?= $item['presentacion'] ?></span>
        </td>
        <td style="padding:12px 16px;text-align:right;font-size:.875rem;color:#6B7280">
          <?= number_format($umbral, 1) ?>
        </td>
        <td style="padding:12px 16px;text-align:center">
          <span style="padding:3px 10px;border-radius:999px;background:<?= $bgLabel ?>;color:<?= $txLabel ?>;font-size:.7rem;font-weight:600">
            <?= $label ?>
          </span>
        </td>
        <td style="padding:12px 16px;text-align:center">
          <button type="button"
                  onclick="abrirAjuste(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['nombre'])) ?>', <?= $stock ?>)"
                  style="padding:6px 14px;background:var(--color-primary);color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.8rem;font-weight:600">
            Ajustar
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Paginación -->
  <?php if (($paginacion['total_pages'] ?? 1) > 1): ?>
  <div style="padding:16px;display:flex;justify-content:center;gap:4px;border-top:1px solid #E5E7EB">
    <?php for ($i = 1; $i <= $paginacion['total_pages']; $i++): ?>
      <a href="?<?= http_build_query(array_merge($filtros, ['page' => $i])) ?>"
         style="padding:6px 12px;border-radius:6px;font-size:.8rem;text-decoration:none;<?= $i === ($paginacion['current_page'] ?? 1) ? 'background:var(--color-primary);color:#fff' : 'background:#F3F4F6;color:#374151' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Modal ajuste -->
<div id="modalAjuste" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:28px;width:420px;max-width:95vw">
    <h3 id="modalTitle" style="font-size:1rem;font-weight:700;color:#111827;margin-bottom:4px"></h3>
    <p id="modalStock" style="font-size:.8rem;color:#6B7280;margin-bottom:20px"></p>
    <form method="POST" action="<?= $baseUrl ?>empresa-inventario/ajustar">
      <input type="hidden" name="producto_id" id="modalProductoId">
      <div style="margin-bottom:14px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px">Tipo de ajuste</label>
        <select name="tipo" style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem">
          <option value="entrada">Entrada (suma al stock)</option>
          <option value="salida">Salida (resta al stock)</option>
          <option value="ajuste">Ajuste directo (establece el valor)</option>
        </select>
      </div>
      <div style="margin-bottom:14px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px">Cantidad</label>
        <input type="number" name="cantidad" min="0.1" step="0.1" required
               style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem;box-sizing:border-box">
      </div>
      <div style="margin-bottom:20px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px">Notas (opcional)</label>
        <input type="text" name="notas" placeholder="Motivo del ajuste..."
               style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem;box-sizing:border-box">
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" style="flex:1;padding:10px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer">
          Confirmar ajuste
        </button>
        <button type="button" onclick="cerrarAjuste()"
                style="padding:10px 20px;border:1px solid #D1D5DB;border-radius:8px;background:#fff;cursor:pointer;font-size:.875rem">
          Cancelar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirAjuste(id, nombre, stock) {
  document.getElementById('modalProductoId').value = id;
  document.getElementById('modalTitle').textContent = nombre;
  document.getElementById('modalStock').textContent = 'Stock actual: ' + stock;
  document.getElementById('modalAjuste').style.display = 'flex';
}
function cerrarAjuste() {
  document.getElementById('modalAjuste').style.display = 'none';
}
document.getElementById('modalAjuste').addEventListener('click', function(e) {
  if (e.target === this) cerrarAjuste();
});
</script>
