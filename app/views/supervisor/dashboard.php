<?php
// Vista: Dashboard Analytics del Supervisor
$baseUrl = BASE_URL;

// Calcular tasa de entrega
$totalPed = (int)($kpis['total_pedidos'] ?? 0);
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

<!-- Barra de selector de período -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
  <div>
    <div style="font-size:.75rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Período de análisis</div>
    <div style="font-size:1rem;font-weight:700;color:#111827"><?= htmlspecialchars($labelPeriodo) ?></div>
  </div>
  <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
    <?php foreach (['hoy' => 'Hoy', '7d' => '7 días', '30d' => '30 días', '90d' => '90 días', 'año' => 'Este año'] as $key => $label): ?>
    <a href="<?= periodoUrl($key, $baseUrlPeriodo) ?>"
       style="padding:6px 14px;border-radius:6px;font-size:.8rem;font-weight:600;text-decoration:none;
              <?= $periodo === $key ? 'background:var(--color-primary);color:#fff' : 'background:#fff;color:#374151;border:1px solid #D1D5DB' ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
    <button onclick="document.getElementById('custom-range').style.display=document.getElementById('custom-range').style.display==='none'?'flex':'none'"
            style="padding:6px 14px;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer;
                   <?= $periodo === 'custom' ? 'background:var(--color-primary);color:#fff;border:none' : 'background:#fff;color:#374151;border:1px solid #D1D5DB' ?>">
      Personalizado
    </button>
  </div>
</div>

<!-- Picker personalizado -->
<form id="custom-range" method="GET" action="<?= $baseUrlPeriodo ?>"
      style="display:<?= $periodo === 'custom' ? 'flex' : 'none' ?>;align-items:center;gap:10px;margin-bottom:16px;background:#fff;padding:12px 16px;border-radius:10px;border:1px solid #E5E7EB;flex-wrap:wrap">
  <input type="hidden" name="periodo" value="custom">
  <div style="display:flex;align-items:center;gap:8px">
    <label style="font-size:.8rem;font-weight:600;color:#374151">Desde</label>
    <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>"
           style="padding:6px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.8rem">
  </div>
  <div style="display:flex;align-items:center;gap:8px">
    <label style="font-size:.8rem;font-weight:600;color:#374151">Hasta</label>
    <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>"
           style="padding:6px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.8rem">
  </div>
  <button type="submit"
          style="padding:6px 16px;background:var(--color-primary);color:#fff;border:none;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer">
    Aplicar
  </button>
</form>

<!-- ── FILA 1: KPIs rápidos (tiempo real + período) ─────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px">

  <div style="background:linear-gradient(135deg,#C8102E,#9B0A22);border-radius:12px;padding:20px;color:#fff">
    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;opacity:.8;margin-bottom:8px">Pedidos totales</div>
    <div style="font-size:2.2rem;font-weight:800;line-height:1"><?= number_format($totalPed) ?></div>
    <div style="font-size:.75rem;opacity:.7;margin-top:6px"><?= htmlspecialchars($labelPeriodo) ?></div>
  </div>

  <div style="background:linear-gradient(135deg,#1D4ED8,#1E40AF);border-radius:12px;padding:20px;color:#fff">
    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;opacity:.8;margin-bottom:8px">Ingresos</div>
    <div style="font-size:1.7rem;font-weight:800;line-height:1">$<?= number_format((float)($kpis['monto_total'] ?? 0), 0) ?></div>
    <div style="font-size:.75rem;opacity:.7;margin-top:6px">MXN · <?= htmlspecialchars($labelPeriodo) ?></div>
  </div>

  <div style="background:linear-gradient(135deg,#059669,#047857);border-radius:12px;padding:20px;color:#fff">
    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;opacity:.8;margin-bottom:8px">Tasa de entrega</div>
    <div style="font-size:2.2rem;font-weight:800;line-height:1"><?= $tasaEntrega ?>%</div>
    <div style="font-size:.75rem;opacity:.7;margin-top:6px"><?= $entregados ?> entregados</div>
  </div>

  <div style="background:linear-gradient(135deg,#D97706,#B45309);border-radius:12px;padding:20px;color:#fff">
    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;opacity:.8;margin-bottom:8px">Pendientes ahora</div>
    <div style="font-size:2.2rem;font-weight:800;line-height:1"><?= count($pendientes) ?></div>
    <div style="font-size:.75rem;opacity:.7;margin-top:6px"><?= count($enRuta) ?> en ruta</div>
  </div>

  <div style="background:linear-gradient(135deg,#7C3AED,#5B21B6);border-radius:12px;padding:20px;color:#fff">
    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;opacity:.8;margin-bottom:8px">Tiempo aprobación</div>
    <div style="font-size:2rem;font-weight:800;line-height:1"><?= $avgLabel ?></div>
    <div style="font-size:.75rem;opacity:.7;margin-top:6px">promedio del período</div>
  </div>

</div>

<!-- ── FILA 2: KPIs secundarios ──────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:16px;display:flex;align-items:center;gap:14px">
    <div style="width:44px;height:44px;background:#FEF3C7;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#D97706" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div>
      <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase">Entregados hoy</div>
      <div style="font-size:1.6rem;font-weight:800;color:#D97706"><?= $entregadosHoy ?></div>
      <div style="font-size:.72rem;color:#9CA3AF">de <?= $pedidosHoy ?> recibidos</div>
    </div>
  </div>
  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:16px;display:flex;align-items:center;gap:14px">
    <div style="width:44px;height:44px;background:#ECFDF5;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div>
      <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase">Confirmados</div>
      <div style="font-size:1.6rem;font-weight:800;color:#059669"><?= (int)($kpis['confirmados'] ?? 0) ?></div>
      <div style="font-size:.72rem;color:#9CA3AF">en el período</div>
    </div>
  </div>
  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:16px;display:flex;align-items:center;gap:14px">
    <div style="width:44px;height:44px;background:#FEE2E2;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#DC2626" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div>
      <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase">Cancelados</div>
      <div style="font-size:1.6rem;font-weight:800;color:#DC2626"><?= (int)($kpis['cancelados'] ?? 0) ?></div>
      <div style="font-size:.72rem;color:#9CA3AF">en el período</div>
    </div>
  </div>
  <div style="background:#fff;border-radius:10px;border:1px solid <?= !empty($alertasStock) ? '#FECACA' : '#E5E7EB' ?>;padding:16px;display:flex;align-items:center;gap:14px">
    <div style="width:44px;height:44px;background:<?= !empty($alertasStock) ? '#FEE2E2' : '#F3F4F6' ?>;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="<?= !empty($alertasStock) ? '#DC2626' : '#9CA3AF' ?>" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
    </div>
    <div>
      <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase">Alertas stock</div>
      <div style="font-size:1.6rem;font-weight:800;color:<?= !empty($alertasStock) ? '#DC2626' : '#059669' ?>"><?= count($alertasStock) ?></div>
      <div style="font-size:.72rem;color:#9CA3AF"><?= $stockStats['agotado'] ?> agotados · <?= $stockStats['critico'] ?> críticos</div>
    </div>
  </div>
</div>

<!-- ── FILA 3: Gráfica principal (línea) ─────────────────────────────────── -->
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:22px;margin-bottom:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div>
      <div style="font-weight:700;color:#111827;font-size:1rem">Pedidos y ventas por día</div>
      <div style="font-size:.78rem;color:#6B7280;margin-top:2px"><?= htmlspecialchars($labelPeriodo) ?> — <?= htmlspecialchars($desde) ?> al <?= htmlspecialchars($hasta) ?></div>
    </div>
    <div style="display:flex;align-items:center;gap:16px;font-size:.78rem">
      <span style="display:flex;align-items:center;gap:5px"><span style="width:12px;height:3px;background:#C8102E;display:inline-block;border-radius:2px"></span>Pedidos</span>
      <span style="display:flex;align-items:center;gap:5px"><span style="width:12px;height:3px;background:#3B82F6;display:inline-block;border-radius:2px;border-top:2px dashed #3B82F6"></span>Monto ($)</span>
    </div>
  </div>
  <div style="height:240px">
    <canvas id="chart-ventas"></canvas>
  </div>
</div>

<!-- ── FILA 4: Donut estados + Top productos ─────────────────────────────── -->
<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:20px;margin-bottom:20px">

  <!-- Donut: pedidos por estado -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:22px">
    <div style="font-weight:700;color:#111827;font-size:.95rem;margin-bottom:4px">Pedidos por estado</div>
    <div style="font-size:.75rem;color:#6B7280;margin-bottom:16px"><?= htmlspecialchars($labelPeriodo) ?></div>
    <?php if (empty($estadoData)): ?>
      <div style="text-align:center;padding:40px 0;color:#9CA3AF;font-size:.875rem">Sin datos en este período.</div>
    <?php else: ?>
    <div style="height:180px;margin-bottom:14px">
      <canvas id="chart-estados"></canvas>
    </div>
    <div style="display:flex;flex-direction:column;gap:6px">
      <?php foreach ($pedidosPorEstado as $i => $row): ?>
      <div style="display:flex;align-items:center;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:7px">
          <span style="width:10px;height:10px;border-radius:50%;background:<?= $estadoColores[$row['estado']] ?? '#9CA3AF' ?>;flex-shrink:0"></span>
          <span style="font-size:.78rem;color:#374151"><?= $estadoLabels[$row['estado']] ?? $row['estado'] ?></span>
        </div>
        <span style="font-size:.78rem;font-weight:700;color:#111827"><?= $row['total'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Bar horizontal: top productos -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:22px">
    <div style="font-weight:700;color:#111827;font-size:.95rem;margin-bottom:4px">Top productos más pedidos</div>
    <div style="font-size:.75rem;color:#6B7280;margin-bottom:16px">Por cantidad · <?= htmlspecialchars($labelPeriodo) ?></div>
    <?php if (empty($topProductos)): ?>
      <div style="text-align:center;padding:40px 0;color:#9CA3AF;font-size:.875rem">Sin datos en este período.</div>
    <?php else: ?>
    <div style="height:240px">
      <canvas id="chart-productos"></canvas>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── FILA 5: Donut inventario + Entradas vs Salidas ────────────────────── -->
<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:20px;margin-bottom:20px">

  <!-- Donut: estado inventario -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:22px">
    <div style="font-weight:700;color:#111827;font-size:.95rem;margin-bottom:4px">Estado del inventario</div>
    <div style="font-size:.75rem;color:#6B7280;margin-bottom:16px">Todos los productos activos</div>
    <?php $totalStockProd = array_sum($stockStats); ?>
    <?php if ($totalStockProd === 0): ?>
      <div style="text-align:center;padding:40px 0;color:#9CA3AF;font-size:.875rem">Sin productos en inventario.</div>
    <?php else: ?>
    <div style="height:160px;margin-bottom:16px">
      <canvas id="chart-inventario"></canvas>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
      <?php foreach (['agotado' => ['#EF4444','Agotado'], 'critico' => ['#F97316','Crítico'], 'bajo' => ['#F59E0B','Bajo'], 'ok' => ['#10B981','Normal']] as $k => [$c, $l]): ?>
      <div style="display:flex;align-items:center;gap:7px;padding:8px 10px;border-radius:8px;background:#F9FAFB">
        <span style="width:10px;height:10px;border-radius:50%;background:<?= $c ?>;flex-shrink:0"></span>
        <div>
          <div style="font-size:.7rem;color:#6B7280"><?= $l ?></div>
          <div style="font-size:1rem;font-weight:800;color:#111827"><?= $stockStats[$k] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($alertasStock)): ?>
    <a href="<?= $baseUrl ?>empresa-inventario" style="display:block;margin-top:12px;text-align:center;font-size:.78rem;color:#DC2626;font-weight:600;text-decoration:none;padding:8px;background:#FEF2F2;border-radius:6px">
      Ver productos con alerta →
    </a>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Bar: entradas vs salidas semanal -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:22px">
    <div style="font-weight:700;color:#111827;font-size:.95rem;margin-bottom:4px">Entradas vs Salidas de stock</div>
    <div style="font-size:.75rem;color:#6B7280;margin-bottom:16px">Últimas 6 semanas · unidades</div>
    <?php if (empty($semLabels)): ?>
      <div style="text-align:center;padding:60px 0;color:#9CA3AF;font-size:.875rem">Sin movimientos de inventario.</div>
    <?php else: ?>
    <div style="height:220px">
      <canvas id="chart-movimientos"></canvas>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── FILA 6: Pedidos recientes + Historial de accesos ─────────────────── -->
<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:20px">

  <!-- Tabla pedidos recientes -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between">
      <span style="font-weight:700;color:#111827">Pedidos recientes</span>
      <a href="<?= $baseUrl ?>empresa-pedido" style="font-size:.78rem;color:var(--color-primary);font-weight:600;text-decoration:none">Ver todos →</a>
    </div>
    <?php if (empty($pedidosRecientes)): ?>
      <div style="padding:40px;text-align:center;color:#9CA3AF;font-size:.875rem">Sin pedidos registrados.</div>
    <?php else: ?>
    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr style="background:#F9FAFB">
          <th style="padding:9px 16px;text-align:left;font-size:.68rem;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:.06em">Folio</th>
          <th style="padding:9px 16px;text-align:left;font-size:.68rem;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:.06em">Comprador</th>
          <th style="padding:9px 16px;text-align:center;font-size:.68rem;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:.06em">Estado</th>
          <th style="padding:9px 16px;text-align:right;font-size:.68rem;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:.06em">Total</th>
          <th style="padding:9px 16px;text-align:right;font-size:.68rem;color:#6B7280;font-weight:700;text-transform:uppercase;letter-spacing:.06em">Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pedidosRecientes as $ped): ?>
        <tr style="border-top:1px solid #F3F4F6;transition:background .1s" onmouseover="this.style.background='#FAFAFA'" onmouseout="this.style.background=''">
          <td style="padding:9px 16px">
            <a href="<?= $baseUrl ?>empresa-pedido/detalle/<?= $ped['id'] ?>"
               style="font-size:.82rem;font-weight:700;color:var(--color-primary);text-decoration:none"><?= htmlspecialchars($ped['folio']) ?></a>
          </td>
          <td style="padding:9px 16px;font-size:.82rem;color:#374151">
            <?= htmlspecialchars(($ped['comprador_nombre'] ?? '') . ' ' . ($ped['comprador_apellido'] ?? '')) ?>
          </td>
          <td style="padding:9px 16px;text-align:center">
            <?php
              $sc = $estadoColores[$ped['estado']] ?? '#9CA3AF';
              $sl = $estadoLabels[$ped['estado']] ?? $ped['estado'];
              $bg = $sc . '1A';
            ?>
            <span style="font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:999px;background:<?= $bg ?>;color:<?= $sc ?>"><?= $sl ?></span>
          </td>
          <td style="padding:9px 16px;text-align:right;font-size:.82rem;font-weight:600;color:#111827">$<?= number_format($ped['total'], 2) ?></td>
          <td style="padding:9px 16px;text-align:right;font-size:.75rem;color:#9CA3AF"><?= date('d/m/y', strtotime($ped['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Historial de accesos -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid #E5E7EB">
      <div style="font-weight:700;color:#111827">Historial de accesos</div>
      <div style="font-size:.75rem;color:#6B7280;margin-top:2px">Últimas sesiones de tu cuenta</div>
    </div>
    <?php if (empty($historialAccesos)): ?>
      <div style="padding:40px;text-align:center;color:#9CA3AF;font-size:.875rem">Sin registros de acceso.</div>
    <?php else: ?>
    <div>
      <?php foreach ($historialAccesos as $acceso): ?>
      <?php
        $esLogin  = $acceso['accion'] === 'Login exitoso';
        $color    = $esLogin ? '#059669' : '#6B7280';
        $bgColor  = $esLogin ? '#ECFDF5' : '#F9FAFB';
        $icono    = $esLogin ? '→' : '←';
        $ts       = strtotime($acceso['created_at']);
        $diff     = time() - $ts;
        if ($diff < 3600)       $relTime = 'hace ' . round($diff/60) . ' min';
        elseif ($diff < 86400)  $relTime = 'hace ' . round($diff/3600) . ' h';
        else                    $relTime = 'hace ' . round($diff/86400) . ' días';
      ?>
      <div style="padding:10px 16px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;gap:10px">
        <div style="width:30px;height:30px;border-radius:50%;background:<?= $bgColor ?>;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:<?= $color ?>;flex-shrink:0">
          <?= $icono ?>
        </div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:.8rem;font-weight:600;color:<?= $color ?>"><?= htmlspecialchars($acceso['accion']) ?></span>
            <span style="font-size:.68rem;color:#9CA3AF"><?= $relTime ?></span>
          </div>
          <div style="display:flex;align-items:center;gap:6px;margin-top:3px">
            <span style="font-size:.7rem;color:#6B7280;font-family:monospace;background:#F3F4F6;padding:1px 5px;border-radius:4px"><?= htmlspecialchars($acceso['ip']) ?></span>
            <span style="font-size:.68rem;color:#9CA3AF"><?= date('d/m/Y H:i', $ts) ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /fila 6 -->

<!-- ── CHART.JS ──────────────────────────────────────────────────────────── -->
<script src="https://unpkg.com/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#6B7280';

const primary = '#C8102E';
const primaryLight = 'rgba(200,16,46,0.12)';

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
          tension: 0.35,
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
          tension: 0.35,
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
      datasets: [{ data: <?= json_encode($estadoData) ?>, backgroundColor: <?= json_encode($estadoColors) ?>, borderWidth: 2, borderColor: '#fff' }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + c.raw } },
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
  const labels = <?= json_encode(array_map(fn($p) => mb_strimwidth($p['nombre'], 0, 24, '…'), $topProductos)) ?>;
  const data   = <?= json_encode(array_map(fn($p) => (float)$p['total_cantidad'], $topProductos)) ?>;
  const palette = ['#C8102E','#EF4444','#F97316','#F59E0B','#10B981','#3B82F6','#8B5CF6','#EC4899'];
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label: 'Unidades', data, backgroundColor: palette.slice(0, data.length), borderRadius: 4 }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: c => ' ' + c.raw.toLocaleString() + ' uds' } },
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
        borderWidth: 2,
        borderColor: '#fff',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '60%',
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + c.raw + ' productos' } },
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
        { label: 'Entradas', data: <?= json_encode($semEntradas) ?>, backgroundColor: '#10B981', borderRadius: 4 },
        { label: 'Salidas',  data: <?= json_encode($semSalidas) ?>,  backgroundColor: '#EF4444', borderRadius: 4 },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index' },
      plugins: {
        legend: {
          position: 'top',
          labels: { usePointStyle: true, pointStyle: 'rectRounded', font: { size: 11 } },
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
</script>
