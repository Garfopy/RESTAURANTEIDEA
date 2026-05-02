<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h1 style="font-size:1.25rem;font-weight:800;margin:0">Reportes</h1>
  <div style="display:flex;gap:8px">
    <input type="date" id="fechaDesde" value="<?= date('Y-m-01') ?>" class="form-control" style="max-width:150px" onchange="filtrar()">
    <input type="date" id="fechaHasta" value="<?= date('Y-m-d') ?>" class="form-control" style="max-width:150px" onchange="filtrar()">
    <a href="<?= BASE_URL ?>reporte/ventas" class="btn btn-secondary">📊 Ventas detalladas</a>
  </div>
</div>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
  <?php
  $kpis = [
    ['label'=>'Ventas del mes','value'=>'$'.number_format($stats['ventas_mes']??0,0,'.', ','),'icon'=>'💰','color'=>'#10B981'],
    ['label'=>'Pedidos del mes','value'=>$stats['pedidos_mes']??0,'icon'=>'📋','color'=>'#3B82F6'],
    ['label'=>'Clientes activos','value'=>$stats['clientes_activos']??0,'icon'=>'🏢','color'=>'#F59E0B'],
    ['label'=>'Kg vendidos','value'=>number_format($stats['kg_mes']??0,0).''.' kg','icon'=>'⚖️','color'=>'#8B5CF6'],
  ];
  foreach ($kpis as $k): ?>
  <div class="kpi-card">
    <div class="kpi-icon" style="background:<?= $k['color'] ?>20;color:<?= $k['color'] ?>"><?= $k['icon'] ?></div>
    <div class="kpi-value"><?= $k['value'] ?></div>
    <div class="kpi-label"><?= $k['label'] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Gráficas -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px">
  <div class="card">
    <div style="font-weight:700;font-size:.875rem;margin-bottom:14px">Ventas diarias</div>
    <canvas id="chartVentas" height="260"></canvas>
  </div>
  <div class="card">
    <div style="font-weight:700;font-size:.875rem;margin-bottom:14px">Por categoría</div>
    <canvas id="chartCategorias" height="260"></canvas>
  </div>
</div>

<!-- Top clientes -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
  <div class="card">
    <div style="font-weight:700;font-size:.875rem;margin-bottom:12px">Top clientes</div>
    <table style="width:100%;font-size:.875rem">
      <thead><tr style="color:#6B7280;font-size:.75rem"><th style="text-align:left">Cliente</th><th style="text-align:right">Compras</th></tr></thead>
      <tbody>
        <?php foreach ($topClientes as $tc): ?>
        <tr style="border-top:1px solid #F3F4F6">
          <td style="padding:6px 0"><?= htmlspecialchars($tc['razon_social']) ?></td>
          <td style="text-align:right;font-weight:600">$<?= number_format($tc['total'],0,'.', ',') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card">
    <div style="font-weight:700;font-size:.875rem;margin-bottom:12px">Top productos</div>
    <table style="width:100%;font-size:.875rem">
      <thead><tr style="color:#6B7280;font-size:.75rem"><th style="text-align:left">Producto</th><th style="text-align:right">Kg</th></tr></thead>
      <tbody>
        <?php foreach ($topProductos as $tp): ?>
        <tr style="border-top:1px solid #F3F4F6">
          <td style="padding:6px 0"><?= htmlspecialchars($tp['nombre']) ?></td>
          <td style="text-align:right;font-weight:600"><?= number_format($tp['total_kg'],0) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$labels = array_column($ventasDias, 'dia');
$datos  = array_column($ventasDias, 'total');
$catLabels = array_column($ventasCat, 'nombre');
$catDatos  = array_column($ventasCat, 'total');
?>
<script>
window.ventasDiasLabels = <?= json_encode($labels) ?>;
window.ventasDiasDatos  = <?= json_encode($datos) ?>;
window.categoriasLabels = <?= json_encode($catLabels) ?>;
window.categoriasDatos  = <?= json_encode($catDatos) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= BASE_URL ?>public/js/dashboard_admin.js"></script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
