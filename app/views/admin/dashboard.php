<?php
$ctrlSlug = 'dashboard';
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
$stats = $estadisticas['stats'] ?? [];
$dias  = $estadisticas['ventas_por_dia'] ?? [];
?>

<!-- KPI Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="kpi-card">
    <div class="kpi-label">Ventas del mes</div>
    <div class="kpi-value">$<?= number_format($stats['ventas_total'] ?? 0, 0, '.', ',') ?></div>
    <div class="kpi-delta up">+18.6% vs mes anterior</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Pedidos del mes</div>
    <div class="kpi-value"><?= number_format($stats['mes_actual'] ?? 0) ?></div>
    <div class="kpi-delta up">+12.4% vs mes anterior</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Clientes activos</div>
    <div class="kpi-value"><?= $statsEmpresa['activos'] ?? 0 ?></div>
    <div class="kpi-delta up">+6.7% vs mes anterior</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-label">Kg vendidos</div>
    <div class="kpi-value"><?= number_format(array_sum(array_column($dias, 'total')) / 185, 0) ?> kg</div>
    <div class="kpi-delta up">+21.3% vs mes anterior</div>
  </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

  <!-- Ventas por día -->
  <div class="card lg:col-span-2">
    <div class="card-header">
      <span class="card-title">Ventas por día (últimos 7 días)</span>
      <select class="form-control form-select" style="width:auto;font-size:.8rem">
        <option>Ventas</option>
        <option>Pedidos</option>
      </select>
    </div>
    <canvas id="chartVentasDia" height="90"></canvas>
  </div>

  <!-- Ventas por categoría -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Ventas por categoría (kg)</span>
    </div>
    <canvas id="chartCategorias" height="160"></canvas>
    <div id="legendCategorias" style="margin-top:12px;font-size:.75rem"></div>
  </div>
</div>

<!-- Tables Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

  <!-- Estado de pedidos -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Pedidos por estado</span>
      <a href="<?= BASE_URL ?>pedido/index" class="btn btn-sm btn-secondary">Ver todos</a>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <?php
      $estadosPedido = [
        'pendiente'      => ['label'=>'Pendientes',   'color'=>'#F59E0B', 'val'=> $stats['pendientes'] ?? 0],
        'en_ruta'        => ['label'=>'En ruta',      'color'=>'#3B82F6', 'val'=> $stats['en_ruta']    ?? 0],
        'entregado'      => ['label'=>'Entregados',   'color'=>'#10B981', 'val'=> $stats['entregados'] ?? 0],
        'total'          => ['label'=>'Total',        'color'=>'#6B7280', 'val'=> $stats['total']       ?? 0],
      ];
      foreach ($estadosPedido as $est):
      ?>
      <div style="background:#F9FAFB;border-radius:10px;padding:14px;display:flex;align-items:center;gap:10px">
        <div style="width:10px;height:10px;border-radius:50%;background:<?= $est['color'] ?>;flex-shrink:0"></div>
        <div>
          <div style="font-size:1.3rem;font-weight:700;color:#111827"><?= $est['val'] ?></div>
          <div style="font-size:.75rem;color:#6B7280"><?= $est['label'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Top 5 productos -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Top 5 productos</span>
    </div>
    <div class="table-container">
      <table>
        <thead><tr><th>Producto</th><th>Kg</th><th>Ventas</th></tr></thead>
        <tbody>
          <?php foreach ($topProductos as $tp): ?>
          <tr>
            <td><?= htmlspecialchars($tp['nombre']) ?></td>
            <td><?= number_format($tp['kg_vendidos'],0) ?> kg</td>
            <td>$<?= number_format($tp['ventas_total'],0,'.', ',') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Alertas de inventario -->
<?php
$alertas = (new InventarioModel())->getAlertas();
if (!empty($alertas)):
?>
<div class="card" style="border-left:4px solid #EF4444">
  <div class="card-header">
    <span class="card-title" style="color:#EF4444">
      <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:inline;vertical-align:middle;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      Stock bajo — <?= count($alertas) ?> productos
    </span>
    <a href="<?= BASE_URL ?>inventario/index" class="btn btn-sm btn-danger">Ver inventario</a>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:8px">
    <?php foreach ($alertas as $a): ?>
    <span class="badge badge-danger"><?= htmlspecialchars($a['producto_nombre']) ?> — <?= $a['disponible'] ?> kg</span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
// Chart: Ventas por día
const diasData = <?= json_encode($dias) ?>;
const labels   = diasData.map(d => d.dia?.substr(5) ?? '');
const valores  = diasData.map(d => parseFloat(d.total) || 0);

new Chart(document.getElementById('chartVentasDia'), {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Ventas ($)',
      data: valores,
      backgroundColor: 'rgba(200,16,46,0.85)',
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { grid: { color: '#F3F4F6' }, ticks: { callback: v => '$' + (v/1000).toFixed(0) + 'k' } },
      x: { grid: { display: false } }
    }
  }
});

// Chart: Categorías
const catData = <?= json_encode($categorias) ?>;
const catLabels = catData.map(c => c.nombre);
const catKg     = catData.map(c => parseFloat(c.kg) || 0);
const catColors = ['#C8102E','#F59E0B','#10B981','#3B82F6','#8B5CF6'];
const totalKg   = catKg.reduce((a,b)=>a+b,0);

new Chart(document.getElementById('chartCategorias'), {
  type: 'doughnut',
  data: {
    labels: catLabels,
    datasets: [{ data: catKg, backgroundColor: catColors, borderWidth: 0 }]
  },
  options: {
    cutout: '65%',
    plugins: { legend: { display: false } }
  }
});

// Legend
const leg = document.getElementById('legendCategorias');
catData.forEach((c,i) => {
  const pct = totalKg > 0 ? Math.round(catKg[i]/totalKg*100) : 0;
  leg.innerHTML += `<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
    <div style="width:10px;height:10px;border-radius:2px;background:${catColors[i]};flex-shrink:0"></div>
    <span style="flex:1">${c.nombre}</span>
    <span style="font-weight:700">${pct}%</span>
  </div>`;
});
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
