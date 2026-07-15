<?php
$money = static fn($value): string => '$' . number_format((float)$value, 2);
$number = static fn($value): string => number_format((float)$value, 0);

$ingresos = (float)($kpis['ingresos'] ?? 0);
$gastos = (float)($kpis['gastos'] ?? 0);
$retiros = (float)($kpis['retiros'] ?? 0);
$utilidad = (float)($kpis['utilidad'] ?? 0);
$ingresosTickets = (float)($kpis['ingresosTickets'] ?? 0);
$ingresosPedidosApp = (float)($kpis['ingresosPedidosApp'] ?? 0);
$ingresosRecargasAmare = (float)($kpis['ingresosRecargasAmare'] ?? 0);

$metodoPagoLabel = static function (?string $metodo): string {
  $key = strtolower(trim((string)$metodo));
  $labels = [
    'amare_wallet' => 'Saldo Amare',
    'saldo_amare' => 'Saldo Amare',
    'wallet' => 'Saldo Amare',
    'app movil' => 'App movil',
    'app_movil' => 'App movil',
    'social_cover' => 'Social Cover',
  ];

  return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key ?: 'efectivo'));
};

$metodosVista = array_map(static function (array $m) use ($metodoPagoLabel): array {
  $m['metodo_pago_label'] = $metodoPagoLabel($m['metodo_pago'] ?? 'efectivo');
  return $m;
}, $metodos);

$ingresoBreakdown = [
  ['label' => 'Tickets de mesa', 'value' => $ingresosTickets],
  ['label' => 'Pedidos app', 'value' => $ingresosPedidosApp],
  ['label' => 'Recargas Saldo Amare', 'value' => $ingresosRecargasAmare],
];

ob_start();
?>
<style>
.finance-page {
  display: grid;
  gap: 22px;
}
.finance-hero {
  border-radius: 30px;
  padding: 28px;
  color: #fff;
  background:
    radial-gradient(circle at 12% 18%, rgba(215,180,106,.22), transparent 28%),
    radial-gradient(circle at 86% 14%, rgba(16,185,129,.16), transparent 26%),
    linear-gradient(135deg, #0B1220 0%, #141E31 100%);
  box-shadow: 0 28px 80px rgba(15,23,42,.20);
}
.finance-hero-top,
.finance-filter {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.finance-title {
  margin: 0 0 7px;
  font-size: 2rem;
  line-height: 1.05;
  font-weight: 900;
}
.finance-subtitle {
  margin: 0;
  color: rgba(255,255,255,.66);
  max-width: 760px;
}
.finance-filter {
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.16);
  border-radius: 18px;
  padding: 10px;
}
.finance-filter input {
  background: rgba(255,255,255,.96);
  border: 0;
  border-radius: 12px;
  padding: 10px 12px;
  color: #111827;
  font-weight: 700;
}
.finance-filter button,
.finance-action {
  border: 0;
  border-radius: 12px;
  padding: 10px 14px;
  background: #D7B46A;
  color: #111827;
  font-weight: 900;
  cursor: pointer;
  text-decoration: none;
}
.finance-kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
  margin-top: 24px;
}
.finance-kpi {
  border: 1px solid rgba(255,255,255,.16);
  background: rgba(255,255,255,.10);
  border-radius: 20px;
  padding: 18px;
}
.finance-kpi span {
  display: block;
  color: rgba(255,255,255,.66);
  font-size: .82rem;
}
.finance-kpi strong {
  display: block;
  margin-top: 7px;
  color: #fff;
  font-size: 1.55rem;
}
.finance-grid-2 {
  display: grid;
  grid-template-columns: minmax(0, 1.25fr) minmax(360px, .75fr);
  gap: 20px;
}
.finance-grid-2 > *,
.finance-grid-3 > * {
  min-width: 0;
}
.finance-grid-3 {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}
.finance-card {
  background: rgba(255,255,255,.97);
  border: 1px solid #E5E7EB;
  border-radius: 22px;
  padding: 20px;
  box-shadow: 0 18px 50px rgba(15,23,42,.06);
}
.finance-card h2,
.finance-card h3 {
  margin: 0 0 14px;
  color: #111827;
}
.finance-card h2 {
  font-size: 1.15rem;
}
.finance-card h3 {
  font-size: 1rem;
}
.finance-breakdown {
  display: grid;
  gap: 12px;
}
.finance-line {
  display: grid;
  gap: 7px;
}
.finance-line-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  color: #334155;
  font-size: .92rem;
}
.finance-bar {
  height: 10px;
  border-radius: 999px;
  overflow: hidden;
  background: #E2E8F0;
}
.finance-bar span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #0F172A, #D7B46A);
}
.finance-mini {
  display: grid;
  gap: 10px;
}
.finance-mini-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid #F1F5F9;
  color: #334155;
}
.finance-mini-row:last-child {
  border-bottom: 0;
}
.finance-mini-row strong {
  color: #111827;
}
.finance-chart {
  min-height: 0;
  overflow: hidden;
}
.finance-chart-body {
  --finance-chart-height: 300px;
  contain: layout size;
  position: relative;
  height: var(--finance-chart-height);
  min-height: 0;
  max-height: var(--finance-chart-height);
  overflow: hidden;
}
.finance-chart-body--sm {
  --finance-chart-height: 260px;
}
.finance-chart-body canvas {
  display: block;
  width: 100% !important;
  height: var(--finance-chart-height) !important;
  max-height: var(--finance-chart-height) !important;
}
.finance-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 4px 10px;
  background: #F1F5F9;
  color: #475569;
  font-size: .78rem;
  font-weight: 800;
}
.finance-activity {
  display: grid;
  gap: 10px;
}
.finance-activity-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px;
  border: 1px solid #EEF2F7;
  border-radius: 16px;
  background: #F8FAFC;
}
.finance-activity-row p {
  margin: 4px 0 0;
  color: #64748B;
  font-size: .84rem;
}
.finance-empty {
  color: #94A3B8;
  font-size: .9rem;
  text-align: center;
  padding: 18px;
  border-radius: 16px;
  background: #F8FAFC;
}
@media (max-width: 1100px) {
  .finance-kpis,
  .finance-grid-3 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .finance-grid-2 {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 680px) {
  .finance-kpis,
  .finance-grid-3 {
    grid-template-columns: 1fr;
  }
  .finance-hero {
    padding: 20px;
  }
  .finance-chart-body {
    --finance-chart-height: 260px;
  }
  .finance-chart-body--sm {
    --finance-chart-height: 240px;
  }
}
</style>

<div class="finance-page">
  <section class="finance-hero">
    <div class="finance-hero-top">
      <div>
        <h1 class="finance-title">Dashboard financiero</h1>
        <p class="finance-subtitle">Vista ejecutiva del periodo: ingresos reales, egresos, utilidad, pagos, ventas app y recargas de Saldo Amare.</p>
      </div>
      <form class="finance-filter" method="GET" action="<?= BASE_URL ?>rest-finanzas/dashboard">
        <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
        <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
        <button type="submit">Filtrar</button>
      </form>
    </div>

    <div class="finance-kpis">
      <div class="finance-kpi"><span>Ingresos contables</span><strong><?= $money($ingresos) ?></strong></div>
      <div class="finance-kpi"><span>Utilidad estimada</span><strong><?= $money($utilidad) ?></strong></div>
      <div class="finance-kpi"><span>Gastos</span><strong><?= $money($gastos) ?></strong></div>
      <div class="finance-kpi"><span>Margen</span><strong><?= number_format((float)($kpis['margen'] ?? 0), 1) ?>%</strong></div>
    </div>
  </section>

  <section class="finance-grid-2">
    <div class="finance-card finance-chart">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px">
        <h2 style="margin:0">Ingresos vs egresos</h2>
        <a class="finance-action" href="<?= BASE_URL ?>rest-finanzas/ventas?desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>">Ver ventas</a>
      </div>
      <div class="finance-chart-body">
        <canvas id="chartIngEgr"></canvas>
      </div>
    </div>

    <div class="finance-card">
      <h2>Ingresos por fuente</h2>
      <div class="finance-breakdown">
        <?php foreach ($ingresoBreakdown as $row): ?>
        <?php $pct = $ingresos > 0 ? min(100, ((float)$row['value'] / $ingresos) * 100) : 0; ?>
        <div class="finance-line">
          <div class="finance-line-top">
            <span><?= htmlspecialchars($row['label']) ?></span>
            <strong><?= $money($row['value']) ?></strong>
          </div>
          <div class="finance-bar"><span style="width:<?= number_format($pct, 2) ?>%"></span></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="finance-grid-3">
    <div class="finance-card">
      <h3>Operacion</h3>
      <div class="finance-mini">
        <div class="finance-mini-row"><span>Tickets pagados</span><strong><?= $number($kpis['totalTickets'] ?? 0) ?></strong></div>
        <div class="finance-mini-row"><span>Pedidos app</span><strong><?= $number($kpis['totalPedidosApp'] ?? 0) ?></strong></div>
        <div class="finance-mini-row"><span>Ticket promedio mesa</span><strong><?= $money($kpis['ticketPromedio'] ?? 0) ?></strong></div>
        <div class="finance-mini-row"><span>Pendiente por cobrar</span><strong><?= $money($kpis['pendiente'] ?? 0) ?></strong></div>
      </div>
    </div>

    <div class="finance-card">
      <h3>Egresos</h3>
      <div class="finance-mini">
        <div class="finance-mini-row"><span>Gastos</span><strong><?= $money($gastos) ?></strong></div>
        <div class="finance-mini-row"><span>Retiros</span><strong><?= $money($retiros) ?></strong></div>
        <div class="finance-mini-row"><span>Propinas</span><strong><?= $money($kpis['propinas'] ?? 0) ?></strong></div>
        <div class="finance-mini-row"><span>Utilidad despues de egresos</span><strong><?= $money($utilidad) ?></strong></div>
      </div>
    </div>

    <div class="finance-card finance-chart">
      <h3>Metodos de pago</h3>
      <?php if (!empty($metodosVista)): ?>
      <div class="finance-chart-body finance-chart-body--sm">
        <canvas id="chartMetodosPago"></canvas>
      </div>
      <?php else: ?>
      <div class="finance-empty">Sin pagos registrados en el periodo.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="finance-grid-2">
    <div class="finance-card">
      <h2>Gastos por categoria</h2>
      <?php if (!empty($catGastos)): ?>
      <div class="finance-mini">
        <?php foreach ($catGastos as $cg): ?>
        <div class="finance-mini-row">
          <span><?= htmlspecialchars((string)$cg['categoria']) ?></span>
          <strong><?= $money($cg['total'] ?? 0) ?></strong>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="finance-empty">Sin gastos en el periodo.</div>
      <?php endif; ?>
    </div>

    <div class="finance-card">
      <h2>Actividad reciente</h2>
      <?php if (!empty($reciente)): ?>
      <div class="finance-activity">
        <?php foreach ($reciente as $act): ?>
        <div class="finance-activity-row">
          <div>
            <span class="finance-pill"><?= htmlspecialchars((string)($act['tipo'] ?? 'movimiento')) ?></span>
            <p><?= htmlspecialchars((string)($act['descripcion'] ?? '')) ?></p>
          </div>
          <strong><?= $money($act['monto'] ?? 0) ?></strong>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="finance-empty">Sin actividad reciente.</div>
      <?php endif; ?>
    </div>
  </section>
</div>

<script>
(function() {
  const money = value => '$' + Number(value || 0).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const ingData = <?= json_encode(array_map(fn($r) => ['x' => $r['dia'], 'y' => (float)$r['total']], $grafica['ingresos'])) ?>;
  const egrData = <?= json_encode(array_map(fn($r) => ['x' => $r['dia'], 'y' => (float)$r['total']], $grafica['egresos'])) ?>;
  const allDates = [...new Set([...ingData.map(d => d.x), ...egrData.map(d => d.x)])].sort();
  const toMap = arr => Object.fromEntries(arr.map(d => [d.x, d.y]));
  const imap = toMap(ingData);
  const emap = toMap(egrData);

  const chartIng = document.getElementById('chartIngEgr');
  if (chartIng) {
    new Chart(chartIng, {
      type: 'bar',
      data: {
        labels: allDates,
        datasets: [
          {label: 'Ingresos', data: allDates.map(d => imap[d] || 0), backgroundColor: '#0F172A', borderRadius: 8},
          {label: 'Egresos', data: allDates.map(d => emap[d] || 0), backgroundColor: '#D7B46A', borderRadius: 8},
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${money(ctx.parsed.y)}` } } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  const mpCanvas = document.getElementById('chartMetodosPago');
  const mpData = <?= json_encode(array_map(fn($m) => [
    'label' => $m['metodo_pago_label'] ?? 'Efectivo',
    'total' => (float)$m['total'],
  ], $metodosVista)) ?>;

  if (mpCanvas && mpData.length) {
    const palette = ['#0F172A', '#D7B46A', '#10B981', '#64748B', '#F97316', '#2563EB'];
    new Chart(mpCanvas, {
      type: 'doughnut',
      data: {
        labels: mpData.map(m => m.label),
        datasets: [{
          data: mpData.map(m => m.total),
          backgroundColor: mpData.map((_, i) => palette[i % palette.length]),
          borderColor: '#fff',
          borderWidth: 3,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
          tooltip: { callbacks: { label: ctx => `${ctx.label}: ${money(ctx.parsed)}` } }
        }
      }
    });
  }
})();
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
