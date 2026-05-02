<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h1 style="font-size:1.25rem;font-weight:800;margin:0">Inventario</h1>
  <a href="<?= BASE_URL ?>inventario/ajuste" class="btn btn-secondary">+ Ajuste manual</a>
</div>

<!-- Alertas de stock bajo -->
<?php $alertas = array_filter($inventario, fn($i) => $i['disponible'] <= $i['minimo_alerta']); ?>
<?php if (!empty($alertas)): ?>
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:.875rem;color:#991B1B">
  ⚠️ <strong><?= count($alertas) ?> producto<?= count($alertas) > 1 ? 's' : '' ?></strong> con stock bajo o crítico
</div>
<?php endif; ?>

<div class="card" style="padding:0;overflow-x:auto">
  <table class="table">
    <thead>
      <tr>
        <th>Producto</th>
        <th>Categoría</th>
        <th style="text-align:right">Disponible</th>
        <th style="text-align:right">En tránsito</th>
        <th style="text-align:right">Reservado</th>
        <th style="text-align:right">Mín. alerta</th>
        <th style="text-align:center">Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($inventario as $item):
        $pct = $item['minimo_alerta'] > 0 ? ($item['disponible'] / $item['minimo_alerta']) : 2;
        $statusColor = $pct >= 2 ? '#10B981' : ($pct >= 1 ? '#F59E0B' : '#EF4444');
        $statusLabel = $pct >= 2 ? 'OK' : ($pct >= 1 ? 'Bajo' : 'Crítico');
      ?>
      <tr>
        <td style="font-weight:600"><?= htmlspecialchars($item['producto_nombre']) ?></td>
        <td style="color:#6B7280;font-size:.8rem"><?= htmlspecialchars($item['categoria']) ?></td>
        <td style="text-align:right;font-weight:700;color:<?= $statusColor ?>"><?= number_format($item['disponible'],0) ?> kg</td>
        <td style="text-align:right;color:#6B7280"><?= number_format($item['en_transito'],0) ?> kg</td>
        <td style="text-align:right;color:#6B7280"><?= number_format($item['reservado'],0) ?> kg</td>
        <td style="text-align:right;color:#9CA3AF"><?= number_format($item['minimo_alerta'],0) ?> kg</td>
        <td style="text-align:center">
          <span class="badge" style="background:<?= $statusColor ?>20;color:<?= $statusColor ?>;font-size:.7rem"><?= $statusLabel ?></span>
        </td>
        <td>
          <button onclick="ajustarStock(<?= $item['producto_id'] ?>, '<?= htmlspecialchars($item['producto_nombre']) ?>', <?= $item['disponible'] ?>)"
                  class="btn btn-sm btn-secondary">Ajustar</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal ajuste stock -->
<div id="modalAjuste" class="modal-overlay">
  <div class="modal" style="max-width:380px">
    <div class="modal-header">
      <div class="modal-title">Ajustar stock</div>
      <button class="modal-close" onclick="document.getElementById('modalAjuste').classList.remove('active')">×</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="<?= BASE_URL ?>inventario/ajustar">
        <input type="hidden" name="producto_id" id="ajusteProductoId">
        <div style="font-weight:600;margin-bottom:12px" id="ajusteProductoNombre"></div>
        <div style="display:flex;flex-direction:column;gap:12px">
          <div>
            <label class="form-label">Nuevo stock disponible (kg)</label>
            <input type="number" name="disponible" id="ajusteDisponible" class="form-control" step="0.1" min="0" required>
          </div>
          <div>
            <label class="form-label">Mínimo de alerta (kg)</label>
            <input type="number" name="minimo_alerta" id="ajusteMinimo" class="form-control" step="1" min="0">
          </div>
          <div>
            <label class="form-label">Nota del ajuste (opcional)</label>
            <textarea name="nota" class="form-control" rows="2" placeholder="Motivo del ajuste..."></textarea>
          </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAjuste').classList.remove('active')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function ajustarStock(productoId, nombre, stock) {
  document.getElementById('ajusteProductoId').value    = productoId;
  document.getElementById('ajusteProductoNombre').textContent = nombre;
  document.getElementById('ajusteDisponible').value   = stock;
  document.getElementById('modalAjuste').classList.add('active');
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
