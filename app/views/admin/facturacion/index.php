<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h1 style="font-size:1.25rem;font-weight:800;margin:0">Facturación CFDI</h1>
  <div style="font-size:.8rem;color:#6B7280">
    RFC Emisor: <strong><?= htmlspecialchars($rfcEmisor ?? '—') ?></strong>
  </div>
</div>

<!-- Filtros -->
<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="date" name="desde" value="<?= htmlspecialchars($_GET['desde'] ?? date('Y-m-01')) ?>" class="form-control" style="max-width:150px">
    <input type="date" name="hasta" value="<?= htmlspecialchars($_GET['hasta'] ?? date('Y-m-d')) ?>" class="form-control" style="max-width:150px">
    <select name="estado" class="form-control form-select" style="max-width:150px">
      <option value="">Todos</option>
      <option value="pendiente" <?= ($_GET['estado']??'') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
      <option value="timbrada" <?= ($_GET['estado']??'') === 'timbrada' ? 'selected' : '' ?>>Timbrada</option>
      <option value="cancelada" <?= ($_GET['estado']??'') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
    </select>
    <button type="submit" class="btn btn-primary">Filtrar</button>
  </form>
</div>

<div class="card" style="padding:0;overflow-x:auto">
  <table class="table">
    <thead>
      <tr>
        <th>Pedido</th>
        <th>Cliente</th>
        <th>Fecha emisión</th>
        <th>UUID CFDI</th>
        <th style="text-align:right">Total</th>
        <th>Estado</th>
        <th style="text-align:right">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($facturas as $f):
        $stBg = ['timbrada'=>'#D1FAE5','pendiente'=>'#FEF3C7','cancelada'=>'#FEE2E2'][$f['estado']] ?? '#F3F4F6';
        $stTx = ['timbrada'=>'#065F46','pendiente'=>'#92400E','cancelada'=>'#991B1B'][$f['estado']] ?? '#374151';
      ?>
      <tr>
        <td><a href="<?= BASE_URL ?>pedido/detalle/<?= $f['pedido_id'] ?>" style="color:#C8102E;text-decoration:none"><?= $f['folio'] ?></a></td>
        <td style="font-size:.875rem"><?= htmlspecialchars($f['razon_social']) ?></td>
        <td style="color:#6B7280;font-size:.8rem"><?= $f['fecha_emision'] ? date('d/m/Y', strtotime($f['fecha_emision'])) : '—' ?></td>
        <td style="font-size:.75rem;color:#6B7280;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?= htmlspecialchars($f['uuid_cfdi'] ?? '—') ?>
        </td>
        <td style="text-align:right;font-weight:600">$<?= number_format($f['total'],0,'.', ',') ?></td>
        <td><span class="badge" style="background:<?= $stBg ?>;color:<?= $stTx ?>"><?= ucfirst($f['estado']) ?></span></td>
        <td style="text-align:right">
          <?php if ($f['estado'] === 'pendiente'): ?>
          <button onclick="timbrar(<?= $f['pedido_id'] ?>)" class="btn btn-sm btn-primary">Timbrar</button>
          <?php elseif ($f['estado'] === 'timbrada'): ?>
          <?php if ($f['xml_url']): ?><a href="<?= htmlspecialchars($f['xml_url']) ?>" target="_blank" class="btn btn-sm btn-secondary">XML</a><?php endif; ?>
          <?php if ($f['pdf_url']): ?><a href="<?= htmlspecialchars($f['pdf_url']) ?>" target="_blank" class="btn btn-sm btn-secondary">PDF</a><?php endif; ?>
          <button onclick="cancelar('<?= htmlspecialchars($f['uuid_cfdi']) ?>')" class="btn btn-sm btn-secondary" style="color:#EF4444">Cancelar</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($facturas)): ?>
      <tr><td colspan="7" style="text-align:center;color:#9CA3AF;padding:32px">No hay facturas en el período seleccionado</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function timbrar(pedidoId) {
  if (!confirm('¿Generar CFDI para este pedido?')) return;
  postJSON('<?= BASE_URL ?>facturacion/timbrar', { pedido_id: pedidoId })
    .then(d => {
      if (d.ok) { showToast('CFDI timbrado exitosamente', 'success'); setTimeout(() => location.reload(), 1000); }
      else showToast(d.error || 'Error al timbrar', 'error');
    });
}

function cancelar(uuid) {
  if (!confirm('¿Cancelar esta factura ante el SAT?')) return;
  postJSON('<?= BASE_URL ?>facturacion/cancelar', { uuid })
    .then(d => {
      if (d.ok) { showToast('Factura cancelada', 'success'); setTimeout(() => location.reload(), 1000); }
      else showToast(d.error || 'Error al cancelar', 'error');
    });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
