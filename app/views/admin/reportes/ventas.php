<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div>
    <a href="<?= BASE_URL ?>reporte/index" style="font-size:.875rem;color:#6B7280;text-decoration:none">← Reportes</a>
    <h1 style="font-size:1.25rem;font-weight:800;margin:4px 0 0">Reporte de ventas</h1>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <form method="GET" style="display:flex;gap:8px">
      <input type="date" name="desde" value="<?= htmlspecialchars($_GET['desde'] ?? date('Y-m-01')) ?>" class="form-control" style="max-width:150px">
      <input type="date" name="hasta" value="<?= htmlspecialchars($_GET['hasta'] ?? date('Y-m-d')) ?>" class="form-control" style="max-width:150px">
      <button type="submit" class="btn btn-primary">Filtrar</button>
    </form>
    <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary">⬇️ CSV</a>
  </div>
</div>

<div class="card" style="padding:0;overflow-x:auto">
  <table class="table">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Folio</th>
        <th>Cliente</th>
        <th>Productos</th>
        <th>Método pago</th>
        <th>Estado</th>
        <th style="text-align:right">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($ventas as $v):
        $statusBg = ['entregado'=>'#D1FAE5','pendiente'=>'#FEF3C7','cancelado'=>'#FEE2E2'][$v['estado']] ?? '#F3F4F6';
        $statusTxt = ['entregado'=>'#065F46','pendiente'=>'#92400E','cancelado'=>'#991B1B'][$v['estado']] ?? '#374151';
      ?>
      <tr>
        <td><?= date('d/m/Y', strtotime($v['fecha_pedido'])) ?></td>
        <td><a href="<?= BASE_URL ?>pedido/detalle/<?= $v['id'] ?>" style="color:#C8102E;text-decoration:none"><?= $v['folio'] ?></a></td>
        <td><?= htmlspecialchars($v['razon_social']) ?></td>
        <td style="color:#6B7280;font-size:.8rem"><?= $v['total_items'] ?? '—' ?> items</td>
        <td style="color:#6B7280;font-size:.8rem"><?= ucfirst($v['metodo_pago'] ?? '—') ?></td>
        <td><span class="badge" style="background:<?= $statusBg ?>;color:<?= $statusTxt ?>"><?= ucfirst(str_replace('_',' ',$v['estado'])) ?></span></td>
        <td style="text-align:right;font-weight:700">$<?= number_format($v['total'],0,'.', ',') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($ventas)): ?>
      <tr><td colspan="7" style="text-align:center;color:#9CA3AF;padding:32px">No hay ventas en el rango seleccionado</td></tr>
      <?php endif; ?>
    </tbody>
    <?php if (!empty($ventas)): ?>
    <tfoot>
      <tr style="border-top:2px solid #E5E7EB;background:#F9FAFB">
        <td colspan="6" style="text-align:right;font-weight:700;padding:10px">Total del período:</td>
        <td style="text-align:right;font-weight:800;font-size:1rem;color:#111827">$<?= number_format(array_sum(array_column($ventas,'total')),0,'.', ',') ?></td>
      </tr>
    </tfoot>
    <?php endif; ?>
  </table>
</div>

<!-- Paginación -->
<?php if (($paginacion['last_page'] ?? 1) > 1): ?>
<div style="display:flex;justify-content:center;gap:6px;margin-top:20px">
  <?php for ($i = 1; $i <= $paginacion['last_page']; $i++): ?>
  <a href="?<?= http_build_query(array_merge($_GET, ['pagina'=>$i])) ?>"
     class="btn btn-sm <?= $paginacion['current_page'] == $i ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
