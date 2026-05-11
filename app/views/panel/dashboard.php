<?php
// Vista: Panel Dashboard (KPIs globales)
// Variables: $totalEmpresas, $totalUsuarios, $pedidosMes, $ventasMes,
//            $empresasActivas, $empresasSuspendidas, $ingresosSaas,
//            $distPlanes, $pedidosPorMes, $empresasNuevas,
//            $ultimosPedidos, $stockBajo, $actividadReciente

function estadoBadge(string $estado): string {
    return match($estado) {
        'pendiente'      => '<span style="background:#FEF3C7;color:#92400E;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">Pendiente</span>',
        'confirmado'     => '<span style="background:#DBEAFE;color:#1E40AF;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">Confirmado</span>',
        'en_preparacion' => '<span style="background:#EDE9FE;color:#5B21B6;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">En prep.</span>',
        'en_ruta'        => '<span style="background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">En ruta</span>',
        'entregado'      => '<span style="background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">Entregado</span>',
        'cancelado'      => '<span style="background:#FEE2E2;color:#991B1B;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">Cancelado</span>',
        default          => '<span style="background:#F3F4F6;color:#374151;padding:2px 8px;border-radius:999px;font-size:.75rem">' . htmlspecialchars($estado) . '</span>',
    };
}
?>
<script src="<?= BASE_URL ?>public/js/chart.min.js"></script>

<!-- Banner primer login -->
<?php if (!empty($flash) && $flash['type'] === 'first_login'): ?>
<div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:20px 24px;border-radius:12px;margin-bottom:24px">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px">
    <div>
      <div style="font-weight:600;font-size:1.05rem;margin-bottom:6px">Actualiza tu contraseña</div>
      <div style="opacity:.9;font-size:.875rem"><?= htmlspecialchars($flash['message']) ?></div>
    </div>
    <a href="<?= BASE_URL ?>cuenta/perfil" style="background:#fff;color:#667eea;padding:9px 18px;border-radius:8px;text-decoration:none;font-weight:600;white-space:nowrap">Cambiar</a>
  </div>
</div>
<?php endif; ?>

<!-- ── Fila 1: 6 KPI cards ── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:20px">
  <?php
  $kpis = [
    ['label'=>'Empresas activas',  'valor'=>$totalEmpresas,                              'bg'=>'#EFF6FF','text'=>'#1E40AF'],
    ['label'=>'Usuarios activos',  'valor'=>$totalUsuarios,                              'bg'=>'#F0FDF4','text'=>'#166534'],
    ['label'=>'Pedidos este mes',  'valor'=>number_format($pedidosMes),                  'bg'=>'#FFF7ED','text'=>'#9A3412'],
    ['label'=>'Ventas este mes',   'valor'=>'$'.number_format($ventasMes,0,'.',','),     'bg'=>'#FFF1F2','text'=>'#9F1239'],
    ['label'=>'Ingresos SaaS/mes', 'valor'=>'$'.number_format($ingresosSaas,0,'.',','), 'bg'=>'#F0FDF4','text'=>'#065F46'],
    ['label'=>'Suscrip. activas',  'valor'=>$empresasActivas . ' / ' . $totalEmpresas,  'bg'=>'#EFF6FF','text'=>'#1D4ED8'],
  ];
  foreach ($kpis as $k): ?>
  <div style="background:<?= $k['bg'] ?>;border-radius:12px;padding:16px 18px">
    <div style="font-size:.75rem;font-weight:600;color:<?= $k['text'] ?>;margin-bottom:6px"><?= $k['label'] ?></div>
    <div style="font-size:1.55rem;font-weight:800;color:<?= $k['text'] ?>"><?= $k['valor'] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── Fila 2: Gráficas ── -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:20px">

  <!-- Pedidos + monto por mes -->
  <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:20px">
    <h3 style="font-size:.875rem;font-weight:700;color:#111827;margin:0 0 16px">Pedidos por mes (últimos 6 meses)</h3>
    <canvas id="chartPedidos" height="90"></canvas>
  </div>

  <!-- Distribución de planes (dona) -->
  <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:20px">
    <h3 style="font-size:.875rem;font-weight:700;color:#111827;margin:0 0 16px">Distribución de planes</h3>
    <canvas id="chartPlanes" height="160"></canvas>
    <div style="margin-top:12px">
      <?php foreach ($distPlanes as $dp): ?>
      <div style="display:flex;justify-content:space-between;font-size:.78rem;color:#374151;padding:3px 0;border-bottom:1px solid #F3F4F6">
        <span><?= htmlspecialchars($dp['nombre']) ?></span>
        <span style="font-weight:700"><?= (int)$dp['total'] ?> empresa<?= (int)$dp['total'] !== 1 ? 's' : '' ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ── Fila 3: Tabla pedidos + actividad ── -->
<div style="display:grid;grid-template-columns:3fr 1fr;gap:16px;margin-bottom:20px">

  <!-- Últimos pedidos -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
    <div style="padding:14px 18px;border-bottom:1px solid #F3F4F6;display:flex;justify-content:space-between;align-items:center">
      <h2 style="font-size:.875rem;font-weight:700;color:#111827;margin:0">Últimos pedidos</h2>
      <a href="<?= BASE_URL ?>panel-pedido/index" style="font-size:.78rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todos →</a>
    </div>
    <?php if (empty($ultimosPedidos)): ?>
      <p style="padding:20px;text-align:center;color:#6B7280;font-size:.875rem">Sin pedidos aún.</p>
    <?php else: ?>
    <table style="width:100%;border-collapse:collapse;font-size:.82rem">
      <thead>
        <tr style="background:#F9FAFB">
          <th style="padding:8px 14px;text-align:left;color:#6B7280;font-weight:600">Folio</th>
          <th style="padding:8px;text-align:left;color:#6B7280;font-weight:600">Empresa</th>
          <th style="padding:8px;text-align:left;color:#6B7280;font-weight:600">Estado</th>
          <th style="padding:8px;text-align:right;color:#6B7280;font-weight:600">Total</th>
          <th style="padding:8px;text-align:left;color:#6B7280;font-weight:600">Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ultimosPedidos as $ped): ?>
        <tr style="border-top:1px solid #F3F4F6">
          <td style="padding:8px 14px;font-weight:600;color:#111827"><?= htmlspecialchars($ped['folio']) ?></td>
          <td style="padding:8px;color:#374151;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($ped['empresa']) ?></td>
          <td style="padding:8px"><?= estadoBadge($ped['estado']) ?></td>
          <td style="padding:8px;text-align:right;font-weight:600">$<?= number_format($ped['total'],2) ?></td>
          <td style="padding:8px;color:#6B7280;font-size:.78rem"><?= date('d/m/Y', strtotime($ped['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Actividad reciente -->
  <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:18px">
    <h3 style="font-size:.875rem;font-weight:700;color:#111827;margin:0 0 14px">Actividad reciente</h3>
    <?php if (empty($actividadReciente)): ?>
      <p style="color:#9CA3AF;font-size:.8rem">Sin actividad.</p>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach ($actividadReciente as $ac): ?>
      <div style="border-left:3px solid var(--color-primary);padding-left:10px">
        <div style="font-size:.8rem;font-weight:600;color:#111827"><?= htmlspecialchars($ac['accion']) ?></div>
        <div style="font-size:.72rem;color:#6B7280"><?= htmlspecialchars($ac['nombre']) ?> · <?= htmlspecialchars($ac['modulo'] ?? '') ?></div>
        <div style="font-size:.7rem;color:#9CA3AF"><?= date('d/m H:i', strtotime($ac['created_at'])) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Alerta stock bajo -->
<?php if (!empty($stockBajo)): ?>
<div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:14px 18px;margin-bottom:20px">
  <p style="font-weight:700;color:#9A3412;margin:0 0 8px;font-size:.875rem">Stock bajo en <?= count($stockBajo) ?> producto(s)</p>
  <div style="display:flex;flex-wrap:wrap;gap:8px">
    <?php foreach ($stockBajo as $s): ?>
    <span style="background:#FEF3C7;color:#92400E;padding:3px 10px;border-radius:999px;font-size:.75rem;font-weight:600">
      <?= htmlspecialchars($s['nombre']) ?> (<?= $s['stock'] ?> / mín <?= $s['umbral_minimo'] ?>) — <?= htmlspecialchars($s['empresa']) ?>
    </span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
(function() {
  // ── Gráfica pedidos por mes ──
  const pedMeses  = <?= json_encode(array_column($pedidosPorMes, 'mes')) ?>;
  const pedTotals = <?= json_encode(array_map('intval', array_column($pedidosPorMes, 'total'))) ?>;
  const pedMontos = <?= json_encode(array_map('floatval', array_column($pedidosPorMes, 'monto'))) ?>;

  new Chart(document.getElementById('chartPedidos'), {
    type: 'bar',
    data: {
      labels: pedMeses,
      datasets: [
        { label: 'Pedidos', data: pedTotals, backgroundColor: 'rgba(200,16,46,0.15)', borderColor: '#C8102E', borderWidth: 2, yAxisID: 'y', type: 'bar' },
        { label: 'Monto ($)', data: pedMontos, borderColor: '#1D4ED8', backgroundColor: 'transparent', borderWidth: 2, tension: 0.4, yAxisID: 'y1', type: 'line', pointRadius: 4 }
      ]
    },
    options: {
      responsive: true, interaction: { mode: 'index', intersect: false },
      scales: {
        y:  { position: 'left',  beginAtZero: true, ticks: { font: { size: 11 } } },
        y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { font: { size: 11 }, callback: v => '$' + v.toLocaleString() } }
      },
      plugins: { legend: { labels: { font: { size: 11 } } } }
    }
  });

  // ── Gráfica planes (dona) ──
  const planNombres = <?= json_encode(array_column($distPlanes, 'nombre')) ?>;
  const planTotals  = <?= json_encode(array_map('intval', array_column($distPlanes, 'total'))) ?>;

  new Chart(document.getElementById('chartPlanes'), {
    type: 'doughnut',
    data: {
      labels: planNombres,
      datasets: [{ data: planTotals, backgroundColor: ['#DBEAFE','#D1FAE5','#EDE9FE'], borderWidth: 2 }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } }
    }
  });
})();
</script>
