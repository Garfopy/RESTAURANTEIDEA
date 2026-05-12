<?php
/**
 * Vista: Estadísticas de pedidos recurrentes
 * Variables: $resumen, $frecuencias, $topProductos, $listado, $rol
 */

$freqLabels  = json_encode(array_map('ucfirst', array_column($frecuencias, 'frecuencia')));
$freqTotales = json_encode(array_map('intval',  array_column($frecuencias, 'total')));

$prodLabels  = json_encode(array_map(fn($p) => $p['nombre'] . ' (' . $p['presentacion'] . ')', $topProductos));
$prodCant    = json_encode(array_map(fn($p) => (float)$p['cantidad_acumulada'], $topProductos));
$prodPlant   = json_encode(array_map(fn($p) => (int)$p['en_plantillas'], $topProductos));
?>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px">
  <div style="background:#F5F3FF;border-radius:12px;padding:18px">
    <div style="font-size:.75rem;color:#5B21B6;font-weight:600;margin-bottom:6px">Plantillas activas</div>
    <div style="font-size:2rem;font-weight:800;color:#5B21B6"><?= $resumen['activos'] ?></div>
  </div>
  <div style="background:#F3F4F6;border-radius:12px;padding:18px">
    <div style="font-size:.75rem;color:#374151;font-weight:600;margin-bottom:6px">Plantillas pausadas</div>
    <div style="font-size:2rem;font-weight:800;color:#374151"><?= $resumen['inactivos'] ?></div>
  </div>
  <div style="background:#EFF6FF;border-radius:12px;padding:18px">
    <div style="font-size:.75rem;color:#1E40AF;font-weight:600;margin-bottom:6px">Próxima ejecución</div>
    <div style="font-size:1.4rem;font-weight:800;color:#1E40AF">
      <?= $resumen['proxima_fecha'] ? date('d/m/Y', strtotime($resumen['proxima_fecha'])) : '—' ?>
    </div>
    <?php if ($resumen['proxima_fecha']): ?>
      <?php $dias = (int)floor((strtotime($resumen['proxima_fecha']) - time()) / 86400); ?>
      <div style="font-size:.72rem;color:#3B82F6;margin-top:2px">
        <?= $dias === 0 ? 'Hoy' : ($dias === 1 ? 'Mañana' : "en {$dias} días") ?>
      </div>
    <?php endif; ?>
  </div>
  <div style="background:#F0FDF4;border-radius:12px;padding:18px">
    <div style="font-size:.75rem;color:#166534;font-weight:600;margin-bottom:6px">Total plantillas</div>
    <div style="font-size:2rem;font-weight:800;color:#166534"><?= $resumen['activos'] + $resumen['inactivos'] ?></div>
  </div>
</div>

<?php if (($resumen['activos'] + $resumen['inactivos']) === 0): ?>
<!-- Estado vacío -->
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:48px;text-align:center">
  <div style="font-size:3rem;margin-bottom:16px">🔄</div>
  <h3 style="font-size:1.1rem;font-weight:700;color:#111827;margin-bottom:8px">Sin plantillas recurrentes aún</h3>
  <p style="color:#6B7280;font-size:.9rem;max-width:400px;margin:0 auto">
    Los compradores pueden crear plantillas de pedido recurrente desde su portal.
    Aquí verás estadísticas de frecuencia, productos más pedidos y próximas ejecuciones.
  </p>
</div>

<?php else: ?>

<!-- Gráficas -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:24px">

  <!-- Gráfica: Distribución por frecuencia -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:18px">
    <h3 style="font-size:.9rem;font-weight:700;color:#111827;margin-bottom:14px">Distribución por frecuencia</h3>
    <?php if (empty($frecuencias)): ?>
      <p style="text-align:center;color:#9CA3AF;font-size:.85rem;padding:40px 0">Sin datos</p>
    <?php else: ?>
      <canvas id="chartFrecuencias" style="max-height:260px"></canvas>
    <?php endif; ?>
  </div>

  <!-- Gráfica: Top productos solicitados -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:18px">
    <h3 style="font-size:.9rem;font-weight:700;color:#111827;margin-bottom:14px">Productos más solicitados</h3>
    <?php if (empty($topProductos)): ?>
      <p style="text-align:center;color:#9CA3AF;font-size:.85rem;padding:40px 0">Sin productos en plantillas</p>
    <?php else: ?>
      <canvas id="chartTopProductos" style="max-height:260px"></canvas>
    <?php endif; ?>
  </div>
</div>

<!-- Tabla: Top productos con detalle -->
<?php if (!empty($topProductos)): ?>
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:24px">
  <div style="padding:14px 18px;border-bottom:1px solid #F3F4F6">
    <h2 style="font-size:.9rem;font-weight:700;color:#111827">Productos más pedidos en plantillas activas</h2>
  </div>
  <table style="width:100%;border-collapse:collapse;font-size:.85rem">
    <thead>
      <tr style="background:#F9FAFB">
        <th style="padding:10px 16px;text-align:left;color:#6B7280;font-weight:600">#</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Producto</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Presentación</th>
        <th style="padding:10px;text-align:right;color:#6B7280;font-weight:600">Cant. acumulada</th>
        <th style="padding:10px;text-align:center;color:#6B7280;font-weight:600">En plantillas</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($topProductos as $i => $prod): ?>
      <tr style="border-top:1px solid #F3F4F6">
        <td style="padding:10px 16px;color:#9CA3AF;font-weight:600"><?= $i + 1 ?></td>
        <td style="padding:10px;font-weight:600;color:#111827"><?= htmlspecialchars($prod['nombre']) ?></td>
        <td style="padding:10px;color:#6B7280"><?= htmlspecialchars($prod['presentacion'] ?? '—') ?></td>
        <td style="padding:10px;text-align:right;font-weight:700;color:#111827">
          <?= number_format((float)$prod['cantidad_acumulada'], 2) ?>
        </td>
        <td style="padding:10px;text-align:center">
          <span style="background:#F5F3FF;color:#5B21B6;padding:2px 10px;border-radius:999px;font-size:.75rem;font-weight:700">
            <?= (int)$prod['en_plantillas'] ?>
          </span>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Tabla: Todas las plantillas -->
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <div style="padding:14px 18px;border-bottom:1px solid #F3F4F6;display:flex;justify-content:space-between;align-items:center">
    <h2 style="font-size:.9rem;font-weight:700;color:#111827">Todas las plantillas</h2>
    <span style="font-size:.8rem;color:#9CA3AF"><?= count($listado) ?> plantilla<?= count($listado) !== 1 ? 's' : '' ?></span>
  </div>
  <?php if (empty($listado)): ?>
    <p style="padding:24px;text-align:center;color:#6B7280;font-size:.875rem">Sin plantillas registradas.</p>
  <?php else: ?>
  <table style="width:100%;border-collapse:collapse;font-size:.85rem">
    <thead>
      <tr style="background:#F9FAFB">
        <th style="padding:10px 16px;text-align:left;color:#6B7280;font-weight:600">Nombre</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Frecuencia</th>
        <th style="padding:10px;text-align:center;color:#6B7280;font-weight:600">Productos</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Próxima ejecución</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Estado</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Creado por</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Alta</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $freqColors = ['diario'=>['#FEF3C7','#92400E'],'semanal'=>['#DBEAFE','#1E40AF'],'quincenal'=>['#F5F3FF','#5B21B6']];
      foreach ($listado as $rec):
        $fc = $freqColors[$rec['frecuencia']] ?? ['#F3F4F6','#374151'];
        $diasRestantes = $rec['proximo_pedido']
          ? (int)floor((strtotime($rec['proximo_pedido']) - time()) / 86400)
          : null;
      ?>
      <tr style="border-top:1px solid #F3F4F6">
        <td style="padding:10px 16px;font-weight:600;color:#111827"><?= htmlspecialchars($rec['nombre']) ?></td>
        <td style="padding:10px">
          <span style="background:<?= $fc[0] ?>;color:<?= $fc[1] ?>;padding:2px 8px;border-radius:999px;font-size:.72rem;font-weight:600">
            <?= ucfirst(htmlspecialchars($rec['frecuencia'])) ?>
          </span>
        </td>
        <td style="padding:10px;text-align:center;color:#374151;font-weight:600"><?= (int)$rec['total_productos'] ?></td>
        <td style="padding:10px;font-size:.8rem">
          <?php if ($rec['proximo_pedido']): ?>
            <span style="color:#374151"><?= date('d/m/Y', strtotime($rec['proximo_pedido'])) ?></span>
            <?php if ($diasRestantes !== null && $rec['activo']): ?>
              <span style="color:<?= $diasRestantes <= 1 ? '#DC2626' : ($diasRestantes <= 3 ? '#D97706' : '#6B7280') ?>;font-size:.7rem;font-weight:600;margin-left:4px">
                <?= $diasRestantes === 0 ? 'Hoy' : ($diasRestantes === 1 ? 'Mañana' : "en {$diasRestantes}d") ?>
              </span>
            <?php endif; ?>
          <?php else: ?>
            <span style="color:#9CA3AF">—</span>
          <?php endif; ?>
        </td>
        <td style="padding:10px">
          <?php if ($rec['activo']): ?>
            <span style="background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:999px;font-size:.72rem;font-weight:600">Activa</span>
          <?php else: ?>
            <span style="background:#F3F4F6;color:#6B7280;padding:2px 8px;border-radius:999px;font-size:.72rem;font-weight:600">Pausada</span>
          <?php endif; ?>
        </td>
        <td style="padding:10px;color:#6B7280;font-size:.8rem"><?= htmlspecialchars($rec['creado_por'] ?? '—') ?></td>
        <td style="padding:10px;color:#6B7280;font-size:.8rem"><?= date('d/m/Y', strtotime($rec['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- Scripts Chart.js -->
<script>
Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#6B7280';

<?php if (!empty($frecuencias)): ?>
// Gráfica: Distribución por frecuencia
new Chart(document.getElementById('chartFrecuencias'), {
  type: 'doughnut',
  data: {
    labels: <?= $freqLabels ?>,
    datasets: [{
      data: <?= $freqTotales ?>,
      backgroundColor: ['#92400E', '#1E40AF', '#5B21B6'],
      borderWidth: 0,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: { position: 'right' },
      tooltip: {
        callbacks: {
          label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' plantilla' + (ctx.parsed !== 1 ? 's' : '')
        }
      }
    }
  }
});
<?php endif; ?>

<?php if (!empty($topProductos)): ?>
// Gráfica: Top productos
new Chart(document.getElementById('chartTopProductos'), {
  type: 'bar',
  data: {
    labels: <?= $prodLabels ?>,
    datasets: [
      {
        label: 'Cantidad acumulada',
        data: <?= $prodCant ?>,
        backgroundColor: '#5B21B6',
        yAxisID: 'y',
      },
      {
        label: 'En # plantillas',
        data: <?= $prodPlant ?>,
        backgroundColor: '#A78BFA',
        yAxisID: 'y1',
      }
    ]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: true,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { position: 'top' },
    },
    scales: {
      y:  { beginAtZero: true },
      y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { stepSize: 1 } }
    }
  }
});
<?php endif; ?>
</script>

<?php endif; ?>
