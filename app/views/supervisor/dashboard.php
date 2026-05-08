<?php
// Vista: Dashboard Analytics del Supervisor
$baseUrl = BASE_URL;

// Calcular tasa de entrega
$totalPed   = (int)($kpis['total_pedidos'] ?? 0);
$entregados = (int)($kpis['entregados'] ?? 0);
$tasaEntrega = $totalPed > 0 ? round(($entregados / $totalPed) * 100, 1) : 0;

// Tiempo promedio de aprobación
$avgMin = (float)($kpis['avg_minutos_aprobacion'] ?? 0);
if ($avgMin > 0) {
    $avgLabel = $avgMin < 60 ? round($avgMin) . ' min' : round($avgMin / 60, 1) . ' h';
} else {
    $avgLabel = '—';
}

// Colores de estados
$estadoColores = [
    'pendiente'       => '#F59E0B',
    'confirmado'      => '#3B82F6',
    'en_preparacion'  => '#8B5CF6',
    'en_ruta'         => '#06B6D4',
    'entregado'       => '#10B981',
    'cancelado'       => '#EF4444',
];
$estadoLabels = [
    'pendiente'       => 'Pendiente',
    'confirmado'      => 'Confirmado',
    'en_preparacion'  => 'En preparación',
    'en_ruta'         => 'En ruta',
    'entregado'       => 'Entregado',
    'cancelado'       => 'Cancelado',
];

// Datos para gráfica de estados
$estadoData   = [];
$estadoColors = [];
$estadoNames  = [];
foreach ($pedidosPorEstado as $row) {
    $estadoNames[]  = $estadoLabels[$row['estado']] ?? $row['estado'];
    $estadoData[]   = (int)$row['total'];
    $estadoColors[] = $estadoColores[$row['estado']] ?? '#9CA3AF';
}

// Datos movimientos semanales
$semLabels   = [];
$semEntradas = [];
$semSalidas  = [];
foreach ($movsSemanal as $s) {
    $semLabels[]   = 'Sem ' . date('d/m', strtotime($s['inicio_semana']));
    $semEntradas[] = (float)$s['entradas'];
    $semSalidas[]  = (float)$s['salidas'];
}

// URL base para período
$baseUrlPeriodo = $baseUrl . 'supervisor/dashboard';

function periodoUrl(string $p, string $base): string {
    return $base . '?periodo=' . $p;
}
?>

<style>
/* ── Dashboard refined styles ────────────────────────────── */
.dash-section-title {
  font-size: .68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #9CA3AF;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.dash-section-title::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #E5E7EB;
}

/* KPI gradient card */
.kpi-card {
  border-radius: 14px;
  padding: 20px;
  color: #fff;
  position: relative;
  overflow: hidden;
}
.kpi-card::after {
  content: '';
  position: absolute;
  top: -30px; right: -30px;
  width: 110px; height: 110px;
  border-radius: 50%;
  background: rgba(255,255,255,.07);
  pointer-events: none;
}
.kpi-card::before {
  content: '';
  position: absolute;
  bottom: -40px; left: -20px;
  width: 130px; height: 130px;
  border-radius: 50%;
  background: rgba(255,255,255,.04);
  pointer-events: none;
}
.kpi-card-label {
  font-size: .65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .1em;
  opacity: .75;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.kpi-card-value {
  font-size: 2.1rem;
  font-weight: 800;
  line-height: 1;
  position: relative;
  z-index: 1;
}
.kpi-card-sub {
  font-size: .72rem;
  opacity: .65;
  margin-top: 8px;
  position: relative;
  z-index: 1;
}
.kpi-card-icon {
  position: absolute;
  bottom: 16px; right: 16px;
  opacity: .15;
  z-index: 0;
}

/* Secondary metric card */
.metric-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #E5E7EB;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  transition: box-shadow .15s, transform .15s;
}
.metric-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,.07);
  transform: translateY(-1px);
}
.metric-icon {
  width: 46px; height: 46px;
  border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

/* Chart card */
.chart-card {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #E5E7EB;
  padding: 22px;
  box-shadow: 0 1px 4px rgba(0,0,0,.03);
}
.chart-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 18px;
}
.chart-card-title { font-weight: 700; color: #111827; font-size: .95rem; }
.chart-card-sub   { font-size: .73rem; color: #9CA3AF; margin-top: 2px; }

/* Period selector */
.period-tabs {
  display: flex;
  align-items: center;
  background: #F3F4F6;
  border-radius: 8px;
  padding: 3px;
  gap: 2px;
}
.period-tab {
  padding: 5px 13px;
  border-radius: 6px;
  font-size: .78rem;
  font-weight: 600;
  text-decoration: none;
  color: #6B7280;
  transition: background .15s, color .15s, box-shadow .15s;
  white-space: nowrap;
}
.period-tab:hover { color: #374151; background: rgba(255,255,255,.7); }
.period-tab.active {
  background: #fff;
  color: #111827;
  box-shadow: 0 1px 3px rgba(0,0,0,.12);
}
.period-tab-custom {
  padding: 5px 13px;
  border-radius: 6px;
  font-size: .78rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  color: #6B7280;
  background: transparent;
  transition: background .15s, color .15s, box-shadow .15s;
}
.period-tab-custom:hover { color: #374151; background: rgba(255,255,255,.7); }
.period-tab-custom.active {
  background: #fff;
  color: #111827;
  box-shadow: 0 1px 3px rgba(0,0,0,.12);
}

/* Table */
.data-table { width: 100%; border-collapse: collapse; }
.data-table thead tr { background: #F9FAFB; }
.data-table th {
  padding: 10px 16px;
  text-align: left;
  font-size: .67rem;
  color: #6B7280;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  white-space: nowrap;
}
.data-table th.right, .data-table td.right { text-align: right; }
.data-table th.center, .data-table td.center { text-align: center; }
.data-table tbody tr {
  border-top: 1px solid #F3F4F6;
  transition: background .1s;
}
.data-table tbody tr:hover { background: #FAFAFA; }
.data-table td { padding: 10px 16px; }

/* Progress bar */
.progress-bar-track {
  height: 4px;
  border-radius: 2px;
  background: rgba(255,255,255,.2);
  margin-top: 10px;
  overflow: hidden;
}
.progress-bar-fill {
  height: 100%;
  border-radius: 2px;
  background: rgba(255,255,255,.7);
}
</style>

<!-- ── Encabezado de sección + selector de período ──────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px">
  <div>
    <h2 style="font-size:1.15rem;font-weight:800;color:#111827;margin:0 0 3px">Panel de análisis</h2>
    <p style="font-size:.78rem;color:#9CA3AF;margin:0"><?= htmlspecialchars($labelPeriodo) ?> &mdash; actualizado al <?= date('d/m/Y H:i') ?></p>
  </div>

  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <div class="period-tabs">
      <?php foreach (['hoy' => 'Hoy', '7d' => '7 días', '30d' => '30 días', '90d' => '90 días', 'año' => 'Este año'] as $key => $label): ?>
      <a href="<?= periodoUrl($key, $baseUrlPeriodo) ?>" class="period-tab <?= $periodo === $key ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
      <button onclick="toggleCustom()"
              class="period-tab-custom <?= $periodo === 'custom' ? 'active' : '' ?>">
        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" style="vertical-align:-1px;margin-right:3px"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Rango
      </button>
    </div>
  </div>
</div>

<!-- Picker personalizado -->
<form id="custom-range" method="GET" action="<?= $baseUrlPeriodo ?>"
      style="display:<?= $periodo === 'custom' ? 'flex' : 'none' ?>;align-items:center;gap:10px;margin-bottom:18px;background:#fff;padding:14px 18px;border-radius:12px;border:1px solid #E5E7EB;flex-wrap:wrap;box-shadow:0 1px 4px rgba(0,0,0,.04)">
  <input type="hidden" name="periodo" value="custom">
  <span style="font-size:.8rem;font-weight:600;color:#374151">Rango personalizado:</span>
  <div style="display:flex;align-items:center;gap:8px">
    <label style="font-size:.8rem;color:#6B7280">Desde</label>
    <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>"
           style="padding:6px 10px;border:1.5px solid #D1D5DB;border-radius:7px;font-size:.8rem;color:#111827;outline:none" onfocus="this.style.borderColor='#C8102E'" onblur="this.style.borderColor='#D1D5DB'">
  </div>
  <div style="display:flex;align-items:center;gap:8px">
    <label style="font-size:.8rem;color:#6B7280">Hasta</label>
    <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>"
           style="padding:6px 10px;border:1.5px solid #D1D5DB;border-radius:7px;font-size:.8rem;color:#111827;outline:none" onfocus="this.style.borderColor='#C8102E'" onblur="this.style.borderColor='#D1D5DB'">
  </div>
  <button type="submit"
          style="padding:7px 18px;background:#C8102E;color:#fff;border:none;border-radius:7px;font-size:.8rem;font-weight:700;cursor:pointer;letter-spacing:.02em">
    Aplicar
  </button>
  <button type="button" onclick="toggleCustom()"
          style="padding:7px 12px;background:#F3F4F6;color:#6B7280;border:none;border-radius:7px;font-size:.8rem;font-weight:600;cursor:pointer">
    Cancelar
  </button>
</form>

<!-- ── FILA 1: KPIs principales ─────────────────────────────────────────── -->
<div class="dash-section-title">Métricas clave del período</div>
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:22px">

  <div class="kpi-card" style="background:linear-gradient(135deg,#C8102E 0%,#9B0A22 100%)">
    <div class="kpi-card-label">
      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      Pedidos totales
    </div>
    <div class="kpi-card-value"><?= number_format($totalPed) ?></div>
    <div class="progress-bar-track"><div class="progress-bar-fill" style="width:<?= min(100, $totalPed) ?>%"></div></div>
    <div class="kpi-card-sub"><?= htmlspecialchars($labelPeriodo) ?></div>
    <svg class="kpi-card-icon" width="80" height="80" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
  </div>

  <div class="kpi-card" style="background:linear-gradient(135deg,#1D4ED8 0%,#1E3A8A 100%)">
    <div class="kpi-card-label">
      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Ingresos
    </div>
    <div class="kpi-card-value" style="font-size:1.6rem">$<?= number_format((float)($kpis['monto_total'] ?? 0), 0) ?></div>
    <div class="progress-bar-track"><div class="progress-bar-fill" style="width:72%"></div></div>
    <div class="kpi-card-sub">MXN &middot; <?= htmlspecialchars($labelPeriodo) ?></div>
    <svg class="kpi-card-icon" width="80" height="80" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
  </div>

  <div class="kpi-card" style="background:linear-gradient(135deg,#059669 0%,#065F46 100%)">
    <div class="kpi-card-label">
      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Tasa de entrega
    </div>
    <div class="kpi-card-value"><?= $tasaEntrega ?>%</div>
    <div class="progress-bar-track"><div class="progress-bar-fill" style="width:<?= $tasaEntrega ?>%"></div></div>
    <div class="kpi-card-sub"><?= $entregados ?> entregados</div>
    <svg class="kpi-card-icon" width="80" height="80" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
  </div>

  <div class="kpi-card" style="background:linear-gradient(135deg,#D97706 0%,#92400E 100%)">
    <div class="kpi-card-label">
      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Pendientes ahora
    </div>
    <div class="kpi-card-value"><?= count($pendientes) ?></div>
    <div class="progress-bar-track"><div class="progress-bar-fill" style="width:<?= min(100, count($pendientes) * 8) ?>%"></div></div>
    <div class="kpi-card-sub"><?= count($enRuta) ?> en ruta</div>
    <svg class="kpi-card-icon" width="80" height="80" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
  </div>

  <div class="kpi-card" style="background:linear-gradient(135deg,#7C3AED 0%,#4C1D95 100%)">
    <div class="kpi-card-label">
      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      Tiempo aprobación
    </div>
    <div class="kpi-card-value"><?= $avgLabel ?></div>
    <div class="progress-bar-track"><div class="progress-bar-fill" style="width:58%"></div></div>
    <div class="kpi-card-sub">promedio del período</div>
    <svg class="kpi-card-icon" width="80" height="80" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
  </div>

</div>

<!-- ── FILA 2: KPIs secundarios ──────────────────────────────────────────── -->
<div class="dash-section-title">Actividad de hoy</div>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px">

  <div class="metric-card">
    <div class="metric-icon" style="background:#FEF3C7">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#D97706" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div>
      <div style="font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.06em">Entregados hoy</div>
      <div style="font-size:1.7rem;font-weight:800;color:#D97706;line-height:1.1"><?= $entregadosHoy ?></div>
      <div style="font-size:.7rem;color:#9CA3AF;margin-top:2px">de <?= $pedidosHoy ?> recibidos</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon" style="background:#ECFDF5">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div>
      <div style="font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.06em">Confirmados</div>
      <div style="font-size:1.7rem;font-weight:800;color:#059669;line-height:1.1"><?= (int)($kpis['confirmados'] ?? 0) ?></div>
      <div style="font-size:.7rem;color:#9CA3AF;margin-top:2px">en el período</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon" style="background:#FEE2E2">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#DC2626" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div>
      <div style="font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.06em">Cancelados</div>
      <div style="font-size:1.7rem;font-weight:800;color:#DC2626;line-height:1.1"><?= (int)($kpis['cancelados'] ?? 0) ?></div>
      <div style="font-size:.7rem;color:#9CA3AF;margin-top:2px">en el período</div>
    </div>
  </div>

  <div class="metric-card" style="border-color:<?= !empty($alertasStock) ? '#FECACA' : '#E5E7EB' ?>">
    <div class="metric-icon" style="background:<?= !empty($alertasStock) ? '#FEE2E2' : '#F3F4F6' ?>">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="<?= !empty($alertasStock) ? '#DC2626' : '#9CA3AF' ?>" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
    </div>
    <div>
      <div style="font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.06em">Alertas stock</div>
      <div style="font-size:1.7rem;font-weight:800;color:<?= !empty($alertasStock) ? '#DC2626' : '#059669' ?>;line-height:1.1"><?= count($alertasStock) ?></div>
      <div style="font-size:.7rem;color:#9CA3AF;margin-top:2px"><?= $stockStats['agotado'] ?> agotados · <?= $stockStats['critico'] ?> críticos</div>
    </div>
  </div>

</div>

<!-- ── FILA 3: Gráfica principal (línea) ─────────────────────────────────── -->
<div class="dash-section-title">Evolución temporal</div>
<div class="chart-card" style="margin-bottom:22px">
  <div class="chart-card-header">
    <div>
      <div class="chart-card-title">Pedidos y ventas por día</div>
      <div class="chart-card-sub"><?= htmlspecialchars($desde) ?> al <?= htmlspecialchars($hasta) ?></div>
    </div>
    <div style="display:flex;align-items:center;gap:16px;font-size:.75rem;color:#6B7280">
      <span style="display:flex;align-items:center;gap:6px">
        <span style="width:20px;height:3px;background:#C8102E;display:inline-block;border-radius:2px"></span>Pedidos
      </span>
      <span style="display:flex;align-items:center;gap:6px">
        <span style="width:20px;height:3px;background:#3B82F6;display:inline-block;border-radius:2px;border-top:2px dashed #3B82F6"></span>Monto ($)
      </span>
    </div>
  </div>
  <div style="height:250px">
    <canvas id="chart-ventas"></canvas>
  </div>
</div>

<!-- ── FILA 4: Donut estados + Top productos ─────────────────────────────── -->
<div class="dash-section-title">Distribución y top productos</div>
<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:20px;margin-bottom:22px">

  <div class="chart-card">
    <div class="chart-card-header">
      <div>
        <div class="chart-card-title">Pedidos por estado</div>
        <div class="chart-card-sub"><?= htmlspecialchars($labelPeriodo) ?></div>
      </div>
    </div>
    <?php if (empty($estadoData)): ?>
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 0;color:#D1D5DB">
        <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="margin-bottom:8px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span style="font-size:.85rem">Sin datos en este período.</span>
      </div>
    <?php else: ?>
    <div style="height:180px;margin-bottom:16px">
      <canvas id="chart-estados"></canvas>
    </div>
    <div style="display:flex;flex-direction:column;gap:7px">
      <?php foreach ($pedidosPorEstado as $row): ?>
      <div style="display:flex;align-items:center;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:8px">
          <span style="width:9px;height:9px;border-radius:50%;background:<?= $estadoColores[$row['estado']] ?? '#9CA3AF' ?>;flex-shrink:0"></span>
          <span style="font-size:.78rem;color:#374151"><?= $estadoLabels[$row['estado']] ?? $row['estado'] ?></span>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
          <div style="width:60px;height:4px;background:#F3F4F6;border-radius:2px;overflow:hidden">
            <div style="height:100%;width:<?= $totalPed > 0 ? round($row['total']/$totalPed*100) : 0 ?>%;background:<?= $estadoColores[$row['estado']] ?? '#9CA3AF' ?>;border-radius:2px"></div>
          </div>
          <span style="font-size:.78rem;font-weight:700;color:#111827;min-width:16px;text-align:right"><?= $row['total'] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="chart-card">
    <div class="chart-card-header">
      <div>
        <div class="chart-card-title">Top productos más pedidos</div>
        <div class="chart-card-sub">Por cantidad &middot; <?= htmlspecialchars($labelPeriodo) ?></div>
      </div>
    </div>
    <?php if (empty($topProductos)): ?>
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 0;color:#D1D5DB">
        <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="margin-bottom:8px"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
        <span style="font-size:.85rem">Sin datos en este período.</span>
      </div>
    <?php else: ?>
    <div style="height:240px">
      <canvas id="chart-productos"></canvas>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── FILA 5: Donut inventario + Entradas vs Salidas ────────────────────── -->
<div class="dash-section-title">Inventario</div>
<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:20px;margin-bottom:22px">

  <div class="chart-card">
    <div class="chart-card-header">
      <div>
        <div class="chart-card-title">Estado del inventario</div>
        <div class="chart-card-sub">Todos los productos activos</div>
      </div>
    </div>
    <?php $totalStockProd = array_sum($stockStats); ?>
    <?php if ($totalStockProd === 0): ?>
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 0;color:#D1D5DB">
        <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="margin-bottom:8px"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
        <span style="font-size:.85rem">Sin productos en inventario.</span>
      </div>
    <?php else: ?>
    <div style="height:160px;margin-bottom:18px">
      <canvas id="chart-inventario"></canvas>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
      <?php foreach (['agotado' => ['#EF4444','Agotado'], 'critico' => ['#F97316','Crítico'], 'bajo' => ['#F59E0B','Bajo'], 'ok' => ['#10B981','Normal']] as $k => [$c, $l]): ?>
      <div style="display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:9px;background:#F9FAFB;border:1px solid #F3F4F6">
        <span style="width:10px;height:10px;border-radius:50%;background:<?= $c ?>;flex-shrink:0"></span>
        <div>
          <div style="font-size:.68rem;color:#9CA3AF;font-weight:600"><?= $l ?></div>
          <div style="font-size:1.05rem;font-weight:800;color:#111827"><?= $stockStats[$k] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($alertasStock)): ?>
    <a href="<?= $baseUrl ?>empresa-inventario"
       style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:14px;font-size:.78rem;color:#DC2626;font-weight:600;text-decoration:none;padding:9px;background:#FEF2F2;border-radius:8px;border:1px solid #FECACA">
      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      Ver productos con alerta
    </a>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="chart-card">
    <div class="chart-card-header">
      <div>
        <div class="chart-card-title">Entradas vs Salidas de stock</div>
        <div class="chart-card-sub">Últimas 6 semanas &middot; unidades</div>
      </div>
    </div>
    <?php if (empty($semLabels)): ?>
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 0;color:#D1D5DB">
        <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="margin-bottom:8px"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
        <span style="font-size:.85rem">Sin movimientos de inventario.</span>
      </div>
    <?php else: ?>
    <div style="height:220px">
      <canvas id="chart-movimientos"></canvas>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── FILA 6: Pedidos recientes + Historial de accesos ─────────────────── -->
<div class="dash-section-title">Actividad reciente</div>
<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:20px">

  <!-- Tabla pedidos recientes -->
  <div class="chart-card" style="padding:0;overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between">
      <div>
        <div style="font-weight:700;color:#111827;font-size:.9rem">Pedidos recientes</div>
        <div style="font-size:.72rem;color:#9CA3AF;margin-top:1px">Últimos <?= count($pedidosRecientes) ?> pedidos</div>
      </div>
      <a href="<?= $baseUrl ?>empresa-pedido"
         style="display:inline-flex;align-items:center;gap:4px;font-size:.75rem;color:#C8102E;font-weight:600;text-decoration:none;padding:5px 10px;border-radius:6px;background:#FEF2F2;transition:background .15s"
         onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
        Ver todos
        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>
    <?php if (empty($pedidosRecientes)): ?>
      <div style="padding:40px;text-align:center;color:#D1D5DB;font-size:.875rem">Sin pedidos registrados.</div>
    <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Folio</th>
          <th>Comprador</th>
          <th class="center">Estado</th>
          <th class="right">Total</th>
          <th class="right">Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pedidosRecientes as $ped): ?>
        <tr>
          <td>
            <a href="<?= $baseUrl ?>empresa-pedido/detalle/<?= $ped['id'] ?>"
               style="font-size:.82rem;font-weight:700;color:#C8102E;text-decoration:none"><?= htmlspecialchars($ped['folio']) ?></a>
          </td>
          <td style="font-size:.82rem;color:#374151">
            <?= htmlspecialchars(($ped['comprador_nombre'] ?? '') . ' ' . ($ped['comprador_apellido'] ?? '')) ?>
          </td>
          <td class="center">
            <?php
              $sc = $estadoColores[$ped['estado']] ?? '#9CA3AF';
              $sl = $estadoLabels[$ped['estado']] ?? $ped['estado'];
            ?>
            <span style="font-size:.66rem;font-weight:700;padding:3px 9px;border-radius:999px;background:<?= $sc ?>1A;color:<?= $sc ?>"><?= $sl ?></span>
          </td>
          <td class="right" style="font-size:.82rem;font-weight:600;color:#111827">$<?= number_format($ped['total'], 2) ?></td>
          <td class="right" style="font-size:.74rem;color:#9CA3AF"><?= date('d/m/y', strtotime($ped['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Historial de accesos -->
  <div class="chart-card" style="padding:0;overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid #F3F4F6">
      <div style="font-weight:700;color:#111827;font-size:.9rem">Historial de accesos</div>
      <div style="font-size:.72rem;color:#9CA3AF;margin-top:1px">Últimas sesiones de tu cuenta</div>
    </div>
    <?php if (empty($historialAccesos)): ?>
      <div style="padding:40px;text-align:center;color:#D1D5DB;font-size:.875rem">Sin registros de acceso.</div>
    <?php else: ?>
    <div>
      <?php foreach ($historialAccesos as $acceso): ?>
      <?php
        $esLogin = $acceso['accion'] === 'Login exitoso';
        $color   = $esLogin ? '#059669' : '#6B7280';
        $bgColor = $esLogin ? '#ECFDF5' : '#F9FAFB';
        $ts      = strtotime($acceso['created_at']);
        $diff    = time() - $ts;
        if ($diff < 3600)      $relTime = 'hace ' . round($diff/60) . ' min';
        elseif ($diff < 86400) $relTime = 'hace ' . round($diff/3600) . ' h';
        else                   $relTime = 'hace ' . round($diff/86400) . ' días';
      ?>
      <div style="padding:11px 16px;border-bottom:1px solid #F9FAFB;display:flex;align-items:center;gap:11px;transition:background .1s" onmouseover="this.style.background='#FAFAFA'" onmouseout="this.style.background=''">
        <div style="width:32px;height:32px;border-radius:50%;background:<?= $bgColor ?>;border:1px solid <?= $esLogin ? '#BBF7D0' : '#E5E7EB' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <?php if ($esLogin): ?>
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
          <?php else: ?>
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#6B7280" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          <?php endif; ?>
        </div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:.8rem;font-weight:600;color:<?= $color ?>"><?= htmlspecialchars($acceso['accion']) ?></span>
            <span style="font-size:.68rem;color:#9CA3AF"><?= $relTime ?></span>
          </div>
          <div style="display:flex;align-items:center;gap:6px;margin-top:3px">
            <span style="font-size:.68rem;color:#6B7280;font-family:monospace;background:#F3F4F6;padding:2px 6px;border-radius:4px"><?= htmlspecialchars($acceso['ip']) ?></span>
            <span style="font-size:.66rem;color:#D1D5DB"><?= date('d/m/Y H:i', $ts) ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /fila 6 -->

<!-- ── CHART.JS ──────────────────────────────────────────────────────────── -->
<script src="<?= BASE_URL ?>public/js/chart.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#6B7280';

const primary      = '#C8102E';
const primaryLight = 'rgba(200,16,46,0.10)';

// ── 1. Gráfica de ventas (línea dual) ────────────────────────────────────
<?php if (!empty($chartDias)): ?>
(function() {
  const ctx = document.getElementById('chart-ventas');
  if (!ctx) return;
  const dias    = <?= json_encode($chartDias) ?>;
  const pedidos = <?= json_encode($chartPedidos) ?>;
  const montos  = <?= json_encode($chartMontos) ?>;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: dias,
      datasets: [
        {
          label: 'Pedidos',
          data: pedidos,
          borderColor: primary,
          backgroundColor: primaryLight,
          borderWidth: 2.5,
          pointBackgroundColor: primary,
          pointRadius: dias.length <= 14 ? 4 : 2,
          pointHoverRadius: 6,
          fill: true,
          tension: 0.4,
          yAxisID: 'yPedidos',
        },
        {
          label: 'Monto ($)',
          data: montos,
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59,130,246,0)',
          borderWidth: 2,
          borderDash: [5, 3],
          pointBackgroundColor: '#3B82F6',
          pointRadius: dias.length <= 14 ? 3 : 2,
          pointHoverRadius: 5,
          fill: false,
          tension: 0.4,
          yAxisID: 'yMonto',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1F2937',
          titleColor: '#F9FAFB',
          bodyColor: '#D1D5DB',
          borderColor: '#374151',
          borderWidth: 1,
          padding: 10,
          callbacks: {
            label: ctx => ctx.dataset.yAxisID === 'yMonto'
              ? ' $' + ctx.raw.toLocaleString('es-MX', {minimumFractionDigits: 0})
              : ' ' + ctx.raw + ' pedidos',
          },
        },
      },
      scales: {
        x: { grid: { color: '#F3F4F6' }, ticks: { maxTicksLimit: 15, font: { size: 11 } } },
        yPedidos: {
          position: 'left',
          beginAtZero: true,
          grid: { color: '#F3F4F6' },
          ticks: { stepSize: 1, font: { size: 11 } },
          title: { display: true, text: 'Pedidos', font: { size: 11 } },
        },
        yMonto: {
          position: 'right',
          beginAtZero: true,
          grid: { drawOnChartArea: false },
          ticks: {
            font: { size: 11 },
            callback: v => '$' + (v/1000).toFixed(0) + 'k',
          },
          title: { display: true, text: 'Monto', font: { size: 11 } },
        },
      },
    },
  });
})();
<?php endif; ?>

// ── 2. Donut: estados ─────────────────────────────────────────────────────
<?php if (!empty($estadoData)): ?>
(function() {
  const ctx = document.getElementById('chart-estados');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($estadoNames) ?>,
      datasets: [{ data: <?= json_encode($estadoData) ?>, backgroundColor: <?= json_encode($estadoColors) ?>, borderWidth: 3, borderColor: '#fff', hoverBorderWidth: 3 }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1F2937',
          titleColor: '#F9FAFB',
          bodyColor: '#D1D5DB',
          callbacks: { label: c => ' ' + c.label + ': ' + c.raw },
        },
      },
    },
  });
})();
<?php endif; ?>

// ── 3. Bar horizontal: top productos ─────────────────────────────────────
<?php if (!empty($topProductos)): ?>
(function() {
  const ctx = document.getElementById('chart-productos');
  if (!ctx) return;
  const labels  = <?= json_encode(array_map(fn($p) => mb_strimwidth($p['nombre'], 0, 24, '…'), $topProductos)) ?>;
  const data    = <?= json_encode(array_map(fn($p) => (float)$p['total_cantidad'], $topProductos)) ?>;
  const palette = ['#C8102E','#EF4444','#F97316','#F59E0B','#10B981','#3B82F6','#8B5CF6','#EC4899'];
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label: 'Unidades', data, backgroundColor: palette.slice(0, data.length), borderRadius: 5, borderSkipped: false }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1F2937',
          titleColor: '#F9FAFB',
          bodyColor: '#D1D5DB',
          callbacks: { label: c => ' ' + c.raw.toLocaleString() + ' uds' },
        },
      },
      scales: {
        x: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 11 } } },
        y: { grid: { display: false }, ticks: { font: { size: 11 } } },
      },
    },
  });
})();
<?php endif; ?>

// ── 4. Donut: inventario ──────────────────────────────────────────────────
<?php if (array_sum($stockStats) > 0): ?>
(function() {
  const ctx = document.getElementById('chart-inventario');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Agotado', 'Crítico', 'Bajo', 'Normal'],
      datasets: [{
        data: [<?= $stockStats['agotado'] ?>, <?= $stockStats['critico'] ?>, <?= $stockStats['bajo'] ?>, <?= $stockStats['ok'] ?>],
        backgroundColor: ['#EF4444', '#F97316', '#F59E0B', '#10B981'],
        borderWidth: 3,
        borderColor: '#fff',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1F2937',
          titleColor: '#F9FAFB',
          bodyColor: '#D1D5DB',
          callbacks: { label: c => ' ' + c.label + ': ' + c.raw + ' productos' },
        },
      },
    },
  });
})();
<?php endif; ?>

// ── 5. Bar: entradas vs salidas semanal ───────────────────────────────────
<?php if (!empty($semLabels)): ?>
(function() {
  const ctx = document.getElementById('chart-movimientos');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($semLabels) ?>,
      datasets: [
        { label: 'Entradas', data: <?= json_encode($semEntradas) ?>, backgroundColor: '#10B981', borderRadius: 5, borderSkipped: false },
        { label: 'Salidas',  data: <?= json_encode($semSalidas) ?>,  backgroundColor: '#EF4444', borderRadius: 5, borderSkipped: false },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index' },
      plugins: {
        legend: {
          position: 'top',
          align: 'end',
          labels: { usePointStyle: true, pointStyle: 'rectRounded', font: { size: 11 }, boxWidth: 10 },
        },
        tooltip: {
          backgroundColor: '#1F2937',
          titleColor: '#F9FAFB',
          bodyColor: '#D1D5DB',
        },
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 11 } } },
      },
    },
  });
})();
<?php endif; ?>

function toggleCustom() {
  const el = document.getElementById('custom-range');
  el.style.display = el.style.display === 'none' ? 'flex' : 'none';
}
</script>
