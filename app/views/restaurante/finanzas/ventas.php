<?php
$kpis = $ventas['kpis'] ?? [];
$topProductos = $ventas['topProductos'] ?? [];
$categorias = $ventas['categorias'] ?? [];
$porMes = $ventas['porMes'] ?? [];
$porEstacion = $ventas['porEstacion'] ?? [];
$insights = $ventas['insights'] ?? [];
$periodo = $periodo ?? 'mes';
$ordenProductos = $ordenProductos ?? 'desc';
$limiteProductos = (int)($limiteProductos ?? 20);
$estacionProductos = $estacionProductos ?? 'todas';
$estacionesFiltro = [
  'todas' => 'Todas las estaciones',
  'primavera' => 'Primavera',
  'verano' => 'Verano',
  'otono' => 'Otono',
  'invierno' => 'Invierno',
];

$money = static fn($value): string => '$' . number_format((float)$value, 2);
$number = static fn($value): string => number_format((float)$value, 0);
$periodUrl = static fn(string $p): string => BASE_URL
  . 'rest-finanzas/ventas?periodo=' . urlencode($p)
  . '&orden=' . urlencode((string)$ordenProductos)
  . '&limite=' . urlencode((string)$limiteProductos)
  . '&estacion=' . urlencode((string)$estacionProductos);
$productosTitulo = $ordenProductos === 'asc' ? 'Productos menos vendidos' : 'Productos mas vendidos';

ob_start();
?>
<style>
.sales-page {
  display: grid;
  gap: 22px;
}
.sales-hero {
  background:
    radial-gradient(circle at 10% 20%, rgba(16,185,129,.16), transparent 30%),
    radial-gradient(circle at 90% 10%, rgba(15,23,42,.14), transparent 24%),
    linear-gradient(135deg, #0F172A 0%, #172033 100%);
  color: #fff;
  border-radius: 28px;
  padding: 28px;
  box-shadow: 0 24px 70px rgba(15,23,42,.18);
}
.sales-hero-top,
.sales-filter {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.sales-title {
  font-size: 1.9rem;
  line-height: 1.05;
  font-weight: 900;
  margin: 0;
}
.sales-filter {
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 18px;
  padding: 10px;
  justify-content: flex-end;
}
.sales-filter input {
  background: rgba(255,255,255,.96);
  border: 0;
  border-radius: 12px;
  padding: 10px 12px;
  color: #111827;
  font-weight: 700;
}
.sales-filter button,
.sales-link {
  border: 0;
  border-radius: 12px;
  padding: 10px 14px;
  background: #D7B46A;
  color: #111827;
  font-weight: 900;
  cursor: pointer;
  text-decoration: none;
}
.sales-kpis {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 14px;
  margin-top: 24px;
}
.sales-hero-insights {
  margin-top: 24px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}
.sales-kpi,
.sales-card {
  background: rgba(255,255,255,.96);
  border: 1px solid #E5E7EB;
  border-radius: 20px;
  padding: 18px;
  box-shadow: 0 18px 50px rgba(15,23,42,.06);
}
.sales-kpi {
  background: rgba(255,255,255,.11);
  border-color: rgba(255,255,255,.16);
  box-shadow: none;
}
.sales-kpi span {
  color: rgba(255,255,255,.68);
  font-size: .82rem;
  display: block;
}
.sales-kpi strong {
  color: #fff;
  font-size: 1.45rem;
  display: block;
  margin-top: 7px;
}
.sales-grid-charts {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
}
.sales-card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}
.sales-card-head h2 {
  margin: 0;
}
.sales-periods {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  flex-wrap: wrap;
}
.sales-period {
  border: 1px solid #E5E7EB;
  border-radius: 999px;
  padding: 8px 12px;
  background: #fff;
  color: #475569;
  text-decoration: none;
  font-size: .78rem;
  font-weight: 900;
}
.sales-period.active {
  background: #0F172A;
  border-color: #0F172A;
  color: #fff;
}
.sales-filter .sales-period {
  background: rgba(255,255,255,.10);
  border-color: rgba(255,255,255,.22);
  color: rgba(255,255,255,.88);
}
.sales-filter .sales-period.active {
  background: #D7B46A;
  border-color: #D7B46A;
  color: #111827;
}
.sales-range {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}
.sales-range input {
  border: 1px solid #E5E7EB;
  border-radius: 12px;
  padding: 8px 10px;
  color: #111827;
  font-weight: 700;
}
.sales-range button {
  border: 0;
  border-radius: 12px;
  background: #D7B46A;
  color: #111827;
  cursor: pointer;
  font-weight: 900;
  padding: 9px 12px;
}
.sales-product-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.sales-product-controls select {
  border: 1px solid #E5E7EB;
  border-radius: 12px;
  padding: 9px 12px;
  background: #fff;
  color: #111827;
  font-weight: 800;
}
.sales-product-controls button {
  border: 0;
  border-radius: 12px;
  background: #0F172A;
  color: #fff;
  cursor: pointer;
  font-weight: 900;
  padding: 10px 13px;
}
.sales-card h2,
.sales-card h3 {
  margin: 0 0 14px;
  color: #111827;
}
.sales-card h2 {
  font-size: 1.2rem;
}
.sales-card h3 {
  font-size: 1rem;
}
.sales-table {
  width: 100%;
  border-collapse: collapse;
}
.sales-table th {
  font-size: .72rem;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #64748B;
  padding: 10px;
  border-bottom: 1px solid #E5E7EB;
}
.sales-table td {
  padding: 12px 10px;
  border-bottom: 1px solid #F1F5F9;
  color: #111827;
}
.sales-product {
  text-align: left !important;
}
.sales-product strong {
  display: block;
  color: #111827;
}
.sales-product span {
  display: block;
  color: #94A3B8;
  font-size: .78rem;
  margin-top: 2px;
}
.sales-insights {
  display: grid;
  gap: 12px;
}
.sales-insight {
  border-radius: 16px;
  border: 1px solid #E5E7EB;
  padding: 14px;
  background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);
}
.sales-insight strong {
  display: block;
  color: #0F172A;
  margin-bottom: 4px;
}
.sales-insight p {
  margin: 0;
  color: #64748B;
  font-size: .9rem;
}
.sales-hero .sales-insight {
  background: rgba(255,255,255,.10);
  border-color: rgba(255,255,255,.16);
}
.sales-hero .sales-insight strong {
  color: #fff;
}
.sales-hero .sales-insight p {
  color: rgba(255,255,255,.72);
}
.sales-empty {
  color: #94A3B8;
  font-size: .9rem;
  padding: 18px;
  text-align: center;
  background: #F8FAFC;
  border-radius: 16px;
}
.sales-chart {
  height: 340px;
  min-height: 0;
  overflow: hidden;
}
.sales-chart canvas {
  display: block;
  width: 100% !important;
  height: 260px !important;
  max-height: 260px !important;
}
@media (max-width: 1100px) {
  .sales-kpis,
  .sales-hero-insights,
  .sales-grid-charts {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 680px) {
  .sales-kpis,
  .sales-hero-insights,
  .sales-grid-charts {
    grid-template-columns: 1fr;
  }
  .sales-hero {
    padding: 20px;
  }
}
</style>

<div class="sales-page">
  <section class="sales-hero">
    <div class="sales-hero-top">
      <div>
        <h1 class="sales-title">Ventas</h1>
      </div>
      <div class="sales-filter">
        <?php foreach (['hoy' => 'Hoy', 'semana' => 'Semana', 'mes' => 'Mes', 'trimestre' => 'Trimestre'] as $key => $label): ?>
        <a class="sales-period <?= $periodo === $key ? 'active' : '' ?>" href="<?= $periodUrl($key) ?>"><?= $label ?></a>
        <?php endforeach; ?>
        <form class="sales-range" method="GET" action="<?= BASE_URL ?>rest-finanzas/ventas">
          <input type="hidden" name="periodo" value="rango">
          <input type="hidden" name="orden" value="<?= htmlspecialchars((string)$ordenProductos) ?>">
          <input type="hidden" name="limite" value="<?= htmlspecialchars((string)$limiteProductos) ?>">
          <input type="hidden" name="estacion" value="<?= htmlspecialchars((string)$estacionProductos) ?>">
          <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
          <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
          <button type="submit">Filtrar</button>
        </form>
      </div>
    </div>

    <div class="sales-kpis">
      <div class="sales-kpi"><span>Venta de productos</span><strong><?= $money($kpis['ventas'] ?? 0) ?></strong></div>
      <div class="sales-kpi"><span>Unidades vendidas</span><strong><?= $number($kpis['unidades'] ?? 0) ?></strong></div>
      <div class="sales-kpi"><span>Pedidos con productos</span><strong><?= $number($kpis['pedidos'] ?? 0) ?></strong></div>
      <div class="sales-kpi"><span>Productos vendidos</span><strong><?= $number($kpis['productos'] ?? 0) ?></strong></div>
      <div class="sales-kpi"><span>Ticket promedio</span><strong><?= $money($kpis['ticketPromedio'] ?? 0) ?></strong></div>
    </div>

    <?php if (!empty($insights)): ?>
    <div class="sales-hero-insights">
      <?php foreach ($insights as $insight): ?>
      <div class="sales-insight">
        <strong><?= htmlspecialchars((string)$insight['titulo']) ?></strong>
        <p><?= htmlspecialchars((string)$insight['texto']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <section class="sales-card">
      <div class="sales-card-head">
        <h2><?= htmlspecialchars($productosTitulo) ?></h2>
        <form class="sales-product-controls" method="GET" action="<?= BASE_URL ?>rest-finanzas/ventas">
          <input type="hidden" name="periodo" value="<?= htmlspecialchars((string)$periodo) ?>">
          <input type="hidden" name="desde" value="<?= htmlspecialchars($desde) ?>">
          <input type="hidden" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
          <select name="estacion" aria-label="Filtrar por estacion">
            <?php foreach ($estacionesFiltro as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= $estacionProductos === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="orden" aria-label="Ordenar productos">
            <option value="desc" <?= $ordenProductos === 'desc' ? 'selected' : '' ?>>Mas vendidos</option>
            <option value="asc" <?= $ordenProductos === 'asc' ? 'selected' : '' ?>>Menos vendidos</option>
          </select>
          <select name="limite" aria-label="Numero de productos">
            <?php foreach (range(5, 100, 5) as $limitOption): ?>
            <option value="<?= $limitOption ?>" <?= $limiteProductos === $limitOption ? 'selected' : '' ?>><?= $limitOption ?> productos</option>
            <?php endforeach; ?>
          </select>
          <button type="submit">Actualizar</button>
        </form>
      </div>
      <?php if (!empty($topProductos)): ?>
      <table class="sales-table">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Unidades</th>
            <th>Pedidos</th>
            <th>Venta</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($topProductos as $row): ?>
          <tr>
            <td class="sales-product">
              <strong><?= htmlspecialchars((string)$row['nombre']) ?></strong>
              <?php if (!empty($row['categoria'])): ?>
              <span><?= htmlspecialchars((string)$row['categoria']) ?></span>
              <?php endif; ?>
            </td>
            <td><?= $number($row['unidades'] ?? 0) ?></td>
            <td><?= $number($row['pedidos'] ?? 0) ?></td>
            <td><strong><?= $money($row['ventas'] ?? 0) ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="sales-empty">Sin ventas registradas en este periodo.</div>
      <?php endif; ?>
  </section>

  <section class="sales-grid-charts">
    <div class="sales-card sales-chart">
      <h3>Categorias mas vendidas</h3>
      <canvas id="chartCategoriasVentas"></canvas>
    </div>
    <div class="sales-card sales-chart">
      <h3>Ventas por mes</h3>
      <canvas id="chartVentasMes"></canvas>
    </div>
  </section>

  <section class="sales-grid-charts">
    <div class="sales-card sales-chart">
      <h3>Ventas por estacion</h3>
      <canvas id="chartVentasEstacion"></canvas>
    </div>
  </section>

  <section class="sales-card">
    <h2>Detalle por categoria</h2>
    <?php if (!empty($categorias)): ?>
    <table class="sales-table">
      <thead>
        <tr>
          <th>Categoria</th>
          <th>Unidades</th>
          <th>Venta</th>
          <th>Participacion</th>
        </tr>
      </thead>
      <tbody>
        <?php $totalCategorias = array_sum(array_map(fn($r) => (float)($r['ventas'] ?? 0), $categorias)); ?>
        <?php foreach ($categorias as $row): ?>
        <?php $share = $totalCategorias > 0 ? ((float)$row['ventas'] / $totalCategorias) * 100 : 0; ?>
        <tr>
          <td class="sales-product"><strong><?= htmlspecialchars((string)$row['categoria']) ?></strong></td>
          <td><?= $number($row['unidades'] ?? 0) ?></td>
          <td><strong><?= $money($row['ventas'] ?? 0) ?></strong></td>
          <td><?= number_format($share, 1) ?>%</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="sales-empty">Sin categorias vendidas en este periodo.</div>
    <?php endif; ?>
  </section>
</div>

<script>
(function() {
  const money = value => '$' + Number(value || 0).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const palette = ['#0F172A', '#D7B46A', '#10B981', '#64748B', '#F97316', '#2563EB', '#EF4444', '#14B8A6'];
  const makeChart = (id, type, labels, values, label) => {
    const el = document.getElementById(id);
    if (!el || !labels.length) return;
    new Chart(el, {
      type,
      data: {
        labels,
        datasets: [{
          label,
          data: values,
          backgroundColor: type === 'line' ? 'rgba(215,180,106,.22)' : labels.map((_, i) => palette[i % palette.length]),
          borderColor: '#D7B46A',
          borderWidth: type === 'line' ? 3 : 1,
          tension: .35,
          fill: type === 'line',
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: type === 'doughnut' },
          tooltip: { callbacks: { label: ctx => `${ctx.label}: ${money(ctx.parsed.y ?? ctx.parsed)}` } }
        },
        scales: type === 'doughnut' ? {} : { y: { beginAtZero: true } }
      }
    });
  };

  const categorias = <?= json_encode(array_map(fn($r) => ['label' => (string)$r['categoria'], 'value' => (float)$r['ventas']], $categorias)) ?>;
  const meses = <?= json_encode(array_map(fn($r) => ['label' => (string)$r['periodo'], 'value' => (float)$r['ventas']], $porMes)) ?>;
  const estaciones = <?= json_encode(array_map(fn($r) => ['label' => (string)$r['estacion'], 'value' => (float)$r['ventas']], $porEstacion)) ?>;

  makeChart('chartCategoriasVentas', 'doughnut', categorias.map(r => r.label), categorias.map(r => r.value), 'Ventas');
  makeChart('chartVentasMes', 'line', meses.map(r => r.label), meses.map(r => r.value), 'Ventas');
  makeChart('chartVentasEstacion', 'bar', estaciones.map(r => r.label), estaciones.map(r => r.value), 'Ventas');
})();
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
