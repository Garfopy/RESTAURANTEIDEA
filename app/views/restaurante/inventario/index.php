<?php ob_start(); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div style="display:flex;gap:10px;align-items:center">
    <?php if (!empty($alertas)): ?>
    <span style="padding:4px 12px;background:#FEE2E2;color:#991B1B;border-radius:99px;font-size:.8rem;font-weight:600">
      ⚠ <?= count($alertas) ?> alertas de stock bajo
    </span>
    <?php endif; ?>
  </div>
  <div style="display:flex;gap:10px">
    <a href="<?= BASE_URL ?>rest-inventario/movimientos"
       style="padding:8px 14px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;text-decoration:none;color:#374151">
      Ver movimientos
    </a>
    <button onclick="document.getElementById('modalIng').style.display='flex'"
      style="padding:8px 16px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer">
      + Ingrediente
    </button>
  </div>
</div>

<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <table style="width:100%;border-collapse:collapse;font-size:.875rem">
    <thead>
      <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Ingrediente</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Categoría</th>
        <th style="padding:12px 16px;text-align:right;font-weight:600;color:#374151">Stock</th>
        <th style="padding:12px 16px;text-align:right;font-weight:600;color:#374151">Mín.</th>
        <th style="padding:12px 16px;text-align:right;font-weight:600;color:#374151">Costo/u</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Estado</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($ingredientes as $ing): ?>
      <?php $bajo = (float)$ing['stock'] <= (float)$ing['stock_minimo']; ?>
      <tr style="border-bottom:1px solid #F3F4F6;<?= $bajo ? 'background:#FFF5F5' : '' ?>">
        <td style="padding:12px 16px;font-weight:500"><?= htmlspecialchars($ing['nombre']) ?></td>
        <td style="padding:12px 16px;color:#6B7280"><?= htmlspecialchars($ing['categoria'] ?? '—') ?></td>
        <td style="padding:12px 16px;text-align:right;font-weight:600;color:<?= $bajo ? '#EF4444' : '#111827' ?>">
          <?= number_format((float)$ing['stock'], 3) ?> <?= htmlspecialchars($ing['unidad_principal']) ?>
        </td>
        <td style="padding:12px 16px;text-align:right;color:#9CA3AF"><?= number_format((float)$ing['stock_minimo'], 3) ?></td>
        <td style="padding:12px 16px;text-align:right">$<?= number_format((float)$ing['costo_unitario'], 2) ?></td>
        <td style="padding:12px 16px">
          <span style="padding:2px 8px;border-radius:99px;font-size:.72rem;font-weight:600;
            background:<?= $bajo ? '#FEE2E2' : '#DCFCE7' ?>;color:<?= $bajo ? '#991B1B' : '#166534' ?>">
            <?= $bajo ? 'Stock bajo' : 'OK' ?>
          </span>
        </td>
        <td style="padding:12px 16px">
          <button onclick="abrirMovimiento(<?= $ing['id'] ?>, '<?= htmlspecialchars($ing['nombre']) ?>')"
            style="font-size:.8rem;color:var(--color-primary);font-weight:500;background:none;border:none;cursor:pointer">Mover</button>
          <a href="#" onclick="editIngrediente(<?= htmlspecialchars(json_encode($ing)) ?>)"
             style="margin-left:10px;font-size:.8rem;color:#6B7280">Editar</a>
          <a href="<?= BASE_URL ?>rest-inventario/eliminar/<?= $ing['id'] ?>" onclick="return confirm('¿Desactivar?')"
             style="margin-left:10px;font-size:.8rem;color:#EF4444">×</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($ingredientes)): ?>
      <tr><td colspan="7" style="padding:32px;text-align:center;color:#9CA3AF">No hay ingredientes.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal nuevo ingrediente -->
<div id="modalIng" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:480px;max-width:95vw;max-height:90vh;overflow-y:auto">
    <h3 style="font-weight:700;margin-bottom:18px" id="modalIngTitle">Nuevo Ingrediente</h3>
    <form method="POST" action="<?= BASE_URL ?>rest-inventario/guardar">
      <input type="hidden" name="id" id="ingId" value="">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
        <div style="grid-column:span 2">
          <label style="font-size:.85rem;font-weight:500">Nombre *</label>
          <input type="text" name="nombre" id="ingNombre" required
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Unidad principal</label>
          <input type="text" name="unidad_principal" id="ingUnidad" value="kg"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Categoría</label>
          <input type="text" name="categoria" id="ingCategoria"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Costo unitario</label>
          <input type="number" name="costo_unitario" id="ingCosto" value="0" min="0" step="0.01"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Stock mínimo</label>
          <input type="number" name="stock_minimo" id="ingMinimo" value="0" min="0" step="0.001"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div style="grid-column:span 2">
          <label style="font-size:.85rem;font-weight:500">Proveedor (nombre libre)</label>
          <input type="text" name="proveedor_nombre" id="ingProveedor"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('modalIng').style.display='none'"
          style="padding:8px 16px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;cursor:pointer;background:#fff">Cancelar</button>
        <button type="submit"
          style="padding:8px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:500;cursor:pointer">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal movimiento -->
<div id="modalMov" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:380px;max-width:95vw">
    <h3 style="font-weight:700;margin-bottom:6px">Movimiento de Inventario</h3>
    <p id="movIngNombre" style="color:#6B7280;font-size:.875rem;margin-bottom:16px"></p>
    <form method="POST" action="<?= BASE_URL ?>rest-inventario/movimiento">
      <input type="hidden" name="ingrediente_id" id="movIngId">
      <div style="margin-bottom:14px">
        <label style="font-size:.85rem;font-weight:500">Tipo</label>
        <select name="tipo" style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
          <option value="entrada">Entrada (suma stock)</option>
          <option value="salida">Salida (resta stock)</option>
          <option value="merma">Merma</option>
          <option value="ajuste">Ajuste</option>
        </select>
      </div>
      <div style="margin-bottom:14px">
        <label style="font-size:.85rem;font-weight:500">Cantidad</label>
        <input type="number" name="cantidad" step="0.001" min="0.001" required
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="margin-bottom:18px">
        <label style="font-size:.85rem;font-weight:500">Motivo</label>
        <input type="text" name="motivo"
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('modalMov').style.display='none'"
          style="padding:8px 16px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;cursor:pointer;background:#fff">Cancelar</button>
        <button type="submit"
          style="padding:8px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:500;cursor:pointer">Registrar</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirMovimiento(id, nombre) {
  document.getElementById('movIngId').value = id;
  document.getElementById('movIngNombre').textContent = nombre;
  document.getElementById('modalMov').style.display = 'flex';
}
function editIngrediente(i) {
  document.getElementById('ingId').value = i.id;
  document.getElementById('ingNombre').value = i.nombre;
  document.getElementById('ingUnidad').value = i.unidad_principal;
  document.getElementById('ingCategoria').value = i.categoria || '';
  document.getElementById('ingCosto').value = i.costo_unitario;
  document.getElementById('ingMinimo').value = i.stock_minimo;
  document.getElementById('ingProveedor').value = i.proveedor_nombre || '';
  document.getElementById('modalIngTitle').textContent = 'Editar Ingrediente';
  document.getElementById('modalIng').style.display = 'flex';
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
