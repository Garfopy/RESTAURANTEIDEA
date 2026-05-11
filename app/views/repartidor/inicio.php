<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Mis entregas — CarniHub</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body { background: #111827; color: #F9FAFB; font-family: 'Inter', sans-serif; min-height: 100vh; margin: 0; }
    .header { background: #1F2937; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #374151; }
    .card { background: #1F2937; border-radius: 12px; margin-bottom: 12px; overflow: hidden; }
    .card-parada { padding: 16px; border-left: 4px solid #374151; }
    .card-parada.pendiente { border-left-color: #F59E0B; }
    .card-parada.entregado { border-left-color: #10B981; }
    .card-parada.fallido   { border-left-color: #EF4444; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: .75rem; font-weight: 600; }
    .badge-p { background: #78350F; color: #FCD34D; }
    .badge-e { background: #064E3B; color: #6EE7B7; }
    .badge-f { background: #7F1D1D; color: #FCA5A5; }
    .btn-primary { background: #C8102E; color: #fff; padding: 14px; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; width: 100%; text-decoration: none; display: block; text-align: center; }
    .btn-secondary { background: #374151; color: #F9FAFB; padding: 12px; border: none; border-radius: 10px; font-size: .9rem; font-weight: 600; cursor: pointer; width: 100%; text-decoration: none; display: block; text-align: center; }
  </style>
</head>
<body>

<div class="header">
  <div>
    <div style="font-weight:800;font-size:1rem">CarniHub Repartidor</div>
    <div style="font-size:.75rem;color:#9CA3AF"><?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? '') ?></div>
  </div>
  <a href="<?= BASE_URL ?>auth/logout" style="font-size:.8rem;color:#9CA3AF;text-decoration:none">Salir</a>
</div>

<div style="padding:16px">
  <?php if (!empty($flash)): ?>
  <div style="padding:12px;border-radius:8px;margin-bottom:12px;<?= $flash['type']==='error' ? 'background:#7F1D1D;color:#FCA5A5' : 'background:#064E3B;color:#6EE7B7' ?>">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <!-- Banner de cambio de contraseña (primer login) -->
  <?php if (!empty($flash) && $flash['type'] === 'first_login'): ?>
  <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px 20px; border-radius: 12px; margin-bottom: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
    <div style="display: flex; flex-direction: column; gap: 12px;">
      <div>
        <div style="font-weight: 700; font-size: 1rem; margin-bottom: 6px;">
          🔐 Actualiza tu contraseña
        </div>
        <div style="opacity: 0.95; font-size: 0.85rem;">
          <?= htmlspecialchars($flash['message']) ?>
        </div>
      </div>
      <div style="display: flex; gap: 10px;">
        <a href="<?= BASE_URL ?>cuenta/perfil"
           style="background: white; color: #667eea; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; flex: 1; text-align: center;">
          Cambiar contraseña
        </a>
        <button onclick="dismissFirstLoginBanner(<?= $_SESSION['usuario']['id'] ?>)"
                style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; flex: 1;">
          Después
        </button>
      </div>
    </div>
  </div>
  <script>
  function dismissFirstLoginBanner(userId) {
      fetch('<?= BASE_URL ?>cuenta/dismissFirstLogin', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ user_id: userId })
      }).then(() => {
          location.reload();
      });
  }
  </script>
  <?php endif; ?>

  <h1 style="font-size:1.1rem;font-weight:800;margin-bottom:4px">Entregas de hoy</h1>
  <p style="font-size:.8rem;color:#9CA3AF;margin-bottom:16px"><?= date('d \d\e F \d\e Y') ?></p>

  <?php
    // ── Datos de KPIs (defensivos) ─────────────────────────────────────────
    $resumenHoy     = $resumenHoy     ?? [];
    $proximaParada  = $proximaParada  ?? null;
    $evidencia      = $evidencia      ?? ['entregadas' => 0, 'completas' => 0, 'pct' => 0.0];
    $incidencias    = $incidencias    ?? 0;
    $tiempoProm     = isset($tiempoProm) ? (float)$tiempoProm : 0.0;
    $kilosPendientes = isset($kilosPendientes) ? (float)$kilosPendientes : 0.0;
    $prodSemanal    = is_array($prodSemanal ?? null) ? $prodSemanal : [];
    $slaMinutosParada = $slaMinutosParada ?? 30;

    $totalHoy      = (int)($resumenHoy['total'] ?? 0);
    $entregadasHoy = (int)($resumenHoy['entregadas'] ?? 0);
    $pendientesHoy = (int)($resumenHoy['pendientes'] ?? 0);
    $pctRuta       = $totalHoy > 0 ? round(($entregadasHoy / $totalHoy) * 100) : 0;

    // Entregas exitosas % = entregadas / (entregadas + fallidas + parciales)
    $intentosHoy = $entregadasHoy + (int)($resumenHoy['fallidas'] ?? 0) + (int)($resumenHoy['parciales'] ?? 0);
    $pctExitosas = $intentosHoy > 0 ? round(($entregadasHoy / $intentosHoy) * 100) : 0;

    // Próxima entrega
    $proxFolio = $proximaParada ? ($proximaParada['folio'] ?? '') : '';
    $proxHora  = '—';
    if ($proximaParada) {
        if (!empty($proximaParada['fecha_entrega'])) {
            $proxHora = date('H:i', strtotime((string)$proximaParada['fecha_entrega']));
        } elseif (!empty($proximaParada['eta_minutos'])) {
            $proxHora = 'ETA ' . (int)$proximaParada['eta_minutos'] . ' min';
        }
    }

    // Estado SLA por tiempo promedio
    $slaOk = $tiempoProm > 0 && $tiempoProm <= $slaMinutosParada;
    $slaColor = $tiempoProm <= 0 ? '#9CA3AF' : ($slaOk ? '#10B981' : '#F59E0B');
    $slaLabel = $tiempoProm <= 0 ? 'Sin datos' : ($slaOk ? 'Dentro de SLA' : 'Por encima del SLA');
  ?>

  <!-- ── Botón generar reporte semanal ─────────────────────────────────── -->
  <a href="<?= BASE_URL ?>empresa-reporte/index?periodo=7d"
     style="display:flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,#7C3AED,#5B21B6);color:#fff;padding:12px;border-radius:10px;text-decoration:none;font-weight:700;font-size:.88rem;margin-bottom:14px">
    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6h6v6m-3-13v9m-7 4h14a2 2 0 002-2V8.414a1 1 0 00-.293-.707l-4.414-4.414A1 1 0 0014.586 3H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
    Generar reporte semanal (PDF)
  </a>

  <!-- ── KPIs estratégicos del Repartidor ──────────────────────────────── -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">

    <!-- Roja: Paradas Completadas -->
    <div style="background:linear-gradient(135deg,#C8102E,#9B0A22);border-radius:12px;padding:14px;color:#fff">
      <div style="font-size:.65rem;font-weight:700;opacity:.85;text-transform:uppercase;letter-spacing:.06em">Paradas completadas</div>
      <div style="font-size:1.7rem;font-weight:900;line-height:1;margin-top:6px"><?= $entregadasHoy ?>/<?= $totalHoy ?></div>
      <div style="height:5px;background:rgba(255,255,255,.25);border-radius:999px;margin-top:8px;overflow:hidden">
        <div style="height:100%;width:<?= $pctRuta ?>%;background:#fff;border-radius:999px"></div>
      </div>
      <div style="font-size:.68rem;opacity:.85;margin-top:5px"><?= $pctRuta ?>% de la ruta del día</div>
    </div>

    <!-- Azul: Kilos Pendientes -->
    <div style="background:linear-gradient(135deg,#1D4ED8,#1E40AF);border-radius:12px;padding:14px;color:#fff">
      <div style="font-size:.65rem;font-weight:700;opacity:.85;text-transform:uppercase;letter-spacing:.06em">Kilos pendientes</div>
      <div style="font-size:1.7rem;font-weight:900;line-height:1;margin-top:6px"><?= number_format($kilosPendientes, 1) ?> <span style="font-size:.85rem;opacity:.85">kg</span></div>
      <div style="font-size:.68rem;opacity:.85;margin-top:8px">en el vehículo por descargar</div>
    </div>

    <!-- Verde: Entregas Exitosas % -->
    <div style="background:linear-gradient(135deg,#059669,#047857);border-radius:12px;padding:14px;color:#fff">
      <div style="font-size:.65rem;font-weight:700;opacity:.85;text-transform:uppercase;letter-spacing:.06em">Entregas exitosas</div>
      <div style="font-size:1.7rem;font-weight:900;line-height:1;margin-top:6px"><?= $pctExitosas ?>%</div>
      <div style="font-size:.68rem;opacity:.85;margin-top:8px"><?= $entregadasHoy ?> de <?= $intentosHoy ?> intento(s)</div>
    </div>

    <!-- Naranja: Próxima Entrega -->
    <div style="background:linear-gradient(135deg,#D97706,#B45309);border-radius:12px;padding:14px;color:#fff">
      <div style="font-size:.65rem;font-weight:700;opacity:.85;text-transform:uppercase;letter-spacing:.06em">Próxima entrega</div>
      <?php if ($proximaParada): ?>
        <div style="font-size:1rem;font-weight:800;line-height:1.1;margin-top:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($proximaParada['sucursal_nombre'] ?? '') ?>">
          <?= htmlspecialchars($proxFolio) ?>
        </div>
        <div style="font-size:.78rem;opacity:.95;font-weight:700;margin-top:3px"><?= htmlspecialchars($proxHora) ?></div>
        <div style="font-size:.65rem;opacity:.8;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($proximaParada['sucursal_nombre'] ?? '') ?></div>
      <?php else: ?>
        <div style="font-size:1.3rem;font-weight:900;margin-top:6px">—</div>
        <div style="font-size:.68rem;opacity:.85;margin-top:8px">Sin paradas pendientes</div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Tarjeta púrpura full-width: Evidencias listas -->
  <div style="background:linear-gradient(135deg,#7C3AED,#5B21B6);border-radius:12px;padding:14px;color:#fff;margin-bottom:14px;display:flex;align-items:center;gap:14px">
    <div style="flex:1">
      <div style="font-size:.65rem;font-weight:700;opacity:.85;text-transform:uppercase;letter-spacing:.06em">Evidencias listas</div>
      <div style="display:flex;align-items:baseline;gap:10px;margin-top:6px">
        <div style="font-size:1.7rem;font-weight:900;line-height:1"><?= number_format($evidencia['pct'], 0) ?>%</div>
        <div style="font-size:.78rem;opacity:.85"><?= $evidencia['completas'] ?> de <?= $evidencia['entregadas'] ?> entregas con foto + firma</div>
      </div>
      <?php if ($evidencia['pct'] < 100 && $evidencia['entregadas'] > 0): ?>
      <div style="font-size:.7rem;opacity:.9;margin-top:6px;background:rgba(255,255,255,.18);padding:5px 8px;border-radius:6px;display:inline-block">
        ⚠ Subir evidencias asegura tu pago
      </div>
      <?php endif; ?>
    </div>
    <div style="width:54px;height:54px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
  </div>

  <!-- ── Indicador SLA + Incidencias ──────────────────────────────────── -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
    <div style="background:#1F2937;border-radius:12px;padding:14px;border-left:4px solid <?= $slaColor ?>">
      <div style="font-size:.65rem;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em">Tiempo por parada</div>
      <div style="display:flex;align-items:baseline;gap:6px;margin-top:6px">
        <span style="font-size:1.5rem;font-weight:900;color:#F9FAFB"><?= $tiempoProm > 0 ? number_format($tiempoProm, 0) : '—' ?></span>
        <span style="font-size:.75rem;color:#9CA3AF">min</span>
      </div>
      <div style="font-size:.68rem;color:<?= $slaColor ?>;font-weight:700;margin-top:4px"><?= $slaLabel ?> (<?= $slaMinutosParada ?> min)</div>
    </div>

    <div style="background:#1F2937;border-radius:12px;padding:14px;border-left:4px solid <?= $incidencias > 0 ? '#EF4444' : '#10B981' ?>">
      <div style="font-size:.65rem;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em">Incidencias (30d)</div>
      <div style="font-size:1.5rem;font-weight:900;color:#F9FAFB;margin-top:6px"><?= number_format($incidencias) ?></div>
      <div style="font-size:.68rem;color:#9CA3AF;margin-top:4px">Fallidas o parciales</div>
    </div>
  </div>

  <!-- ── Mini-chart: Productividad semanal ────────────────────────────── -->
  <?php if (!empty($prodSemanal)): ?>
  <div style="background:#1F2937;border-radius:12px;padding:14px;margin-bottom:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
      <div>
        <div style="font-size:.85rem;font-weight:700;color:#F9FAFB">Productividad semanal</div>
        <div style="font-size:.68rem;color:#9CA3AF">Entregadas vs intentos · últimas 6 semanas</div>
      </div>
    </div>
    <div style="height:140px"><canvas id="chart-prod-rep"></canvas></div>
  </div>
  <?php endif; ?>

  <?php if (!$rutaHoy): ?>
    <div style="text-align:center;padding:40px 20px;color:#6B7280">
      <div style="font-size:2.5rem;margin-bottom:12px">📦</div>
      <p style="font-weight:600">No tienes entregas asignadas para hoy.</p>
      <p style="font-size:.85rem;margin-top:4px">Contacta a tu empresa para más información.</p>
      <a href="<?= BASE_URL ?>repartidor/historial" class="btn-secondary" style="margin-top:20px;display:inline-block;width:auto;padding:10px 24px">Ver historial</a>
    </div>

  <?php else: ?>
    <!-- Resumen de ruta -->
    <div class="card" style="padding:14px 16px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center">
      <div>
        <div style="font-size:.75rem;color:#9CA3AF;margin-bottom:2px">Progreso de ruta</div>
        <div style="font-weight:800;font-size:1rem"><?= (int)$rutaHoy['entregadas'] ?> / <?= (int)$rutaHoy['total_paradas'] ?> entregas</div>
      </div>
      <div style="text-align:right">
        <div style="font-size:.75rem;color:#9CA3AF;margin-bottom:2px">Estado</div>
        <?php
        $eBg = $rutaHoy['estado'] === 'en_curso' ? '#064E3B' : '#1E3A5F';
        $eC  = $rutaHoy['estado'] === 'en_curso' ? '#6EE7B7' : '#93C5FD';
        ?>
        <span class="badge" style="background:<?= $eBg ?>;color:<?= $eC ?>"><?= htmlspecialchars($rutaHoy['estado']) ?></span>
      </div>
    </div>

    <!-- Lista de paradas -->
    <?php foreach ($paradas as $i => $parada): ?>
    <div class="card">
      <div class="card-parada <?= $parada['estado'] ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
          <div>
            <div style="font-weight:700;font-size:.95rem"><?= $i+1 ?>. <?= htmlspecialchars($parada['sucursal_nombre']) ?></div>
            <div style="font-size:.8rem;color:#9CA3AF;margin-top:2px"><?= htmlspecialchars($parada['empresa_nombre']) ?></div>
          </div>
          <?php
          $bClass = match($parada['estado']) {
            'entregado' => 'badge-e',
            'fallido'   => 'badge-f',
            default     => 'badge-p',
          };
          $bLabel = match($parada['estado']) {
            'entregado' => 'Entregado',
            'fallido'   => 'Fallido',
            default     => 'Pendiente',
          };
          ?>
          <span class="badge <?= $bClass ?>"><?= $bLabel ?></span>
        </div>
        <div style="font-size:.8rem;color:#D1D5DB;margin-bottom:10px">
          <span>📍 </span><?= htmlspecialchars($parada['direccion']) ?>
        </div>
        <div style="font-size:.8rem;color:#9CA3AF;margin-bottom:10px">
          Pedido: <span style="color:#F9FAFB;font-weight:600"><?= htmlspecialchars($parada['pedido_folio']) ?></span>
        </div>
        <?php if ($parada['estado'] === 'pendiente'): ?>
        <a href="<?= BASE_URL ?>repartidor/entrega/<?= $parada['id'] ?>" class="btn-primary">
          Registrar entrega
        </a>
        <?php elseif ($parada['hora_entrega']): ?>
        <div style="font-size:.75rem;color:#6EE7B7;text-align:center;margin-top:4px">
          Entregado a las <?= date('H:i', strtotime($parada['hora_entrega'])) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <a href="<?= BASE_URL ?>repartidor/historial" class="btn-secondary" style="margin-top:8px">
      Ver historial de entregas
    </a>
  <?php endif; ?>
</div>

<script src="<?= BASE_URL ?>public/js/chart.min.js"></script>
<script>
(function(){
  if (typeof Chart === 'undefined') return;
  const c = document.getElementById('chart-prod-rep');
  if (!c) return;
  const labels    = <?= json_encode(array_map(fn($x) => 'S' . substr((string)$x['yw'], 4), $prodSemanal)) ?>;
  const entreg    = <?= json_encode(array_map(fn($x) => (int)$x['entregadas'], $prodSemanal)) ?>;
  const intentos  = <?= json_encode(array_map(fn($x) => (int)$x['intentos'],   $prodSemanal)) ?>;
  new Chart(c, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        { label: 'Entregadas', data: entreg,   backgroundColor: 'rgba(16,185,129,.85)', borderRadius: 4, maxBarThickness: 22 },
        { label: 'Intentos',   data: intentos, backgroundColor: 'rgba(96,165,250,.55)', borderRadius: 4, maxBarThickness: 22 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { color: '#D1D5DB', font: { size: 10 }, boxWidth: 10 } },
        tooltip: { backgroundColor: '#111827' }
      },
      scales: {
        y: { beginAtZero: true, ticks: { color: '#9CA3AF', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,.08)' } },
        x: { ticks: { color: '#9CA3AF', font: { size: 10 } }, grid: { display: false } }
      }
    }
  });
})();
</script>

</body>
</html>
