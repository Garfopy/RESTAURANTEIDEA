<?php
// Vista: Dashboard de empresa (admin_empresa / supervisor / comprador)
// Variables: $rol, $totalPedidos, $gastomMes, $pedidosRecientes, $pendientesAprobacion
?>

<?php if ($rol === 'comprador'): ?>
<!-- Vista comprador: ir directo al catálogo -->
<div style="background:#fff;border-radius:12px;padding:32px;text-align:center;border:1px solid #E5E7EB">
  <h2 style="font-size:1.25rem;font-weight:800;color:#111827;margin-bottom:8px">Bienvenido, <?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? '') ?>!</h2>
  <p style="color:#6B7280;margin-bottom:24px">¿Qué necesitas hoy?</p>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>catalogo/index" style="display:flex;align-items:center;gap:8px;padding:12px 24px;background:var(--color-primary);color:#fff;border-radius:8px;text-decoration:none;font-weight:600">
      Ver catálogo y hacer pedido
    </a>
    <a href="<?= BASE_URL ?>pedido/index" style="display:flex;align-items:center;gap:8px;padding:12px 24px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600">
      Mis pedidos
    </a>
  </div>
</div>

<?php else: ?>
<!-- Vista admin_empresa y supervisor -->

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px">
  <div style="background:#EFF6FF;border-radius:12px;padding:18px">
    <div style="font-size:.75rem;color:#1E40AF;font-weight:600;margin-bottom:6px">Pedidos totales</div>
    <div style="font-size:1.75rem;font-weight:800;color:#1E40AF"><?= $totalPedidos ?></div>
  </div>
  <div style="background:#F0FDF4;border-radius:12px;padding:18px">
    <div style="font-size:.75rem;color:#166534;font-weight:600;margin-bottom:6px">Gasto este mes</div>
    <div style="font-size:1.75rem;font-weight:800;color:#166534">$<?= number_format($gastomMes,2) ?></div>
  </div>
  <?php if ($pendientesAprobacion > 0): ?>
  <div style="background:#FFF7ED;border-radius:12px;padding:18px">
    <div style="font-size:.75rem;color:#9A3412;font-weight:600;margin-bottom:6px">Pendientes aprobación</div>
    <div style="font-size:1.75rem;font-weight:800;color:#9A3412"><?= $pendientesAprobacion ?></div>
    <a href="<?= BASE_URL ?>pedido/aprobacion" style="font-size:.75rem;color:#9A3412;font-weight:600;text-decoration:underline">Revisar ahora</a>
  </div>
  <?php endif; ?>
</div>

<!-- Acciones rápidas (solo admin_empresa) -->
<?php if ($rol === 'admin_empresa'): ?>
<div style="display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap">
  <a href="<?= BASE_URL ?>empresa-usuario/nuevo" style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
    + Agregar usuario
  </a>
  <a href="<?= BASE_URL ?>empresa-sucursal/nuevo" style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
    + Nueva sucursal
  </a>
  <button onclick="document.getElementById('modalDireccion').style.display='flex'"
          style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;font-weight:600;font-size:.875rem;border:1px solid #E5E7EB;cursor:pointer;font-family:inherit">
    📍 Dirección de la empresa
  </button>
</div>
<?php endif; ?>

<!-- Pedidos recientes -->
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <div style="padding:14px 18px;border-bottom:1px solid #F3F4F6;display:flex;justify-content:space-between;align-items:center">
    <h2 style="font-size:.9rem;font-weight:700;color:#111827">Pedidos recientes</h2>
    <a href="<?= BASE_URL ?>pedido/index" style="font-size:.8rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todos</a>
  </div>
  <?php if (empty($pedidosRecientes)): ?>
    <p style="padding:24px;text-align:center;color:#6B7280;font-size:.875rem">No hay pedidos aún.</p>
  <?php else: ?>
  <table style="width:100%;border-collapse:collapse;font-size:.85rem">
    <thead>
      <tr style="background:#F9FAFB">
        <th style="padding:10px 16px;text-align:left;color:#6B7280;font-weight:600">Folio</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Estado</th>
        <th style="padding:10px;text-align:right;color:#6B7280;font-weight:600">Total</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Fecha</th>
        <th style="padding:10px"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pedidosRecientes as $ped): ?>
      <tr style="border-top:1px solid #F3F4F6">
        <td style="padding:10px 16px;font-weight:600;color:#111827"><?= htmlspecialchars($ped['folio']) ?></td>
        <td style="padding:10px">
          <?php
          $colors = ['pendiente'=>['#FEF3C7','#92400E'],'confirmado'=>['#DBEAFE','#1E40AF'],'en_preparacion'=>['#EDE9FE','#5B21B6'],'en_ruta'=>['#D1FAE5','#065F46'],'entregado'=>['#D1FAE5','#065F46'],'cancelado'=>['#FEE2E2','#991B1B']];
          $c = $colors[$ped['estado']] ?? ['#F3F4F6','#374151'];
          echo "<span style='background:{$c[0]};color:{$c[1]};padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600'>" . htmlspecialchars($ped['estado']) . "</span>";
          ?>
        </td>
        <td style="padding:10px;text-align:right;font-weight:600">$<?= number_format($ped['total'],2) ?></td>
        <td style="padding:10px;color:#6B7280;font-size:.8rem"><?= date('d/m/Y', strtotime($ped['created_at'])) ?></td>
        <td style="padding:10px">
          <?php if (in_array($ped['estado'], ['en_ruta','en_preparacion'], true)): ?>
          <a href="<?= BASE_URL ?>pedido/tracking/<?= $ped['id'] ?? '' ?>" style="font-size:.75rem;color:var(--color-primary);font-weight:600;text-decoration:none">Rastrear</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php if (!empty($datosGraficas) && $rol !== 'comprador'): ?>
<!-- ════════════════════════════════════════════════════════════════ -->
<!-- SECCIÓN DE GRÁFICAS DE MÉTRICAS -->
<!-- ════════════════════════════════════════════════════════════════ -->
<div style="margin-top:32px">
  <h2 style="font-size:1.1rem;font-weight:700;color:#111827;margin-bottom:18px">📊 Métricas y Análisis</h2>

  <!-- Fila 1: Ventas mensuales y diarias -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:16px">

    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:18px">
      <h3 style="font-size:.9rem;font-weight:600;color:#374151;margin-bottom:12px">Ventas por Mes</h3>
      <canvas id="chartVentasMes" style="max-height:280px"></canvas>
    </div>

    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:18px">
      <h3 style="font-size:.9rem;font-weight:600;color:#374151;margin-bottom:12px">Ventas Últimos 30 Días</h3>
      <canvas id="chartVentasDia" style="max-height:280px"></canvas>
    </div>
  </div>

  <!-- Fila 2: Estados y usuarios -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:16px">

    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:18px">
      <h3 style="font-size:.9rem;font-weight:600;color:#374151;margin-bottom:12px">Estados de Pedidos</h3>
      <canvas id="chartEstadosPedidos" style="max-height:280px"></canvas>
    </div>

    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:18px">
      <h3 style="font-size:.9rem;font-weight:600;color:#374151;margin-bottom:12px">Mi Equipo</h3>
      <canvas id="chartUsuariosRol" style="max-height:280px"></canvas>
    </div>
  </div>

  <!-- Fila 3: Productos y métodos de pago -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px">

    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:18px">
      <h3 style="font-size:.9rem;font-weight:600;color:#374151;margin-bottom:12px">Top 5 Productos</h3>
      <canvas id="chartTopProductos" style="max-height:280px"></canvas>
    </div>

    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:18px">
      <h3 style="font-size:.9rem;font-weight:600;color:#374151;margin-bottom:12px">Métodos de Pago</h3>
      <canvas id="chartMetodosPago" style="max-height:280px"></canvas>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════════ -->
<!-- SCRIPTS DE INICIALIZACIÓN DE CHART.JS -->
<!-- ════════════════════════════════════════════════════════════════ -->
<script>
// Configuración global de Chart.js
Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6B7280';

// Obtener color primario del sistema
const colorPrimario = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#C8102E';

// Función para agregar transparencia a color hex
function hexToRgba(hex, alpha) {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

// ═══════════════════════════════════════════════════════════════════
// 1. GRÁFICA: Ventas por Mes (línea + barras)
// ═══════════════════════════════════════════════════════════════════
<?php
$mesesLabels = !empty($datosGraficas['ventasMes']) ? json_encode(array_column($datosGraficas['ventasMes'], 'mes_corto')) : '[]';
$mesesVentas = !empty($datosGraficas['ventasMes']) ? json_encode(array_map('floatval', array_column($datosGraficas['ventasMes'], 'ventas'))) : '[]';
$mesesPedidos = !empty($datosGraficas['ventasMes']) ? json_encode(array_map('intval', array_column($datosGraficas['ventasMes'], 'total_pedidos'))) : '[]';
?>
new Chart(document.getElementById('chartVentasMes'), {
  type: 'bar',
  data: {
    labels: <?= $mesesLabels ?>,
    datasets: [
      {
        type: 'line',
        label: 'Ventas ($)',
        data: <?= $mesesVentas ?>,
        borderColor: colorPrimario,
        backgroundColor: hexToRgba(colorPrimario, 0.2),
        borderWidth: 2,
        tension: 0.3,
        yAxisID: 'y',
      },
      {
        type: 'bar',
        label: 'Pedidos',
        data: <?= $mesesPedidos ?>,
        backgroundColor: '#3B82F6',
        yAxisID: 'y1',
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { display: true, position: 'top' },
      tooltip: {
        callbacks: {
          label: function(context) {
            let label = context.dataset.label || '';
            if (label) label += ': ';
            if (context.dataset.yAxisID === 'y') {
              label += '$' + context.parsed.y.toLocaleString('es-MX', {minimumFractionDigits: 2});
            } else {
              label += context.parsed.y;
            }
            return label;
          }
        }
      }
    },
    scales: {
      y: {
        type: 'linear',
        position: 'left',
        ticks: { callback: v => '$' + v.toLocaleString('es-MX') },
        title: { display: true, text: 'Ventas' }
      },
      y1: {
        type: 'linear',
        position: 'right',
        grid: { drawOnChartArea: false },
        title: { display: true, text: 'Pedidos' }
      }
    }
  }
});

// ═══════════════════════════════════════════════════════════════════
// 2. GRÁFICA: Ventas por Día (área)
// ═══════════════════════════════════════════════════════════════════
<?php
$diasLabels = !empty($datosGraficas['ventasDia']) ? json_encode(array_map(function($d) {
  return date('d/m', strtotime($d['fecha']));
}, $datosGraficas['ventasDia'])) : '[]';
$diasVentas = !empty($datosGraficas['ventasDia']) ? json_encode(array_map('floatval', array_column($datosGraficas['ventasDia'], 'ventas'))) : '[]';
?>
new Chart(document.getElementById('chartVentasDia'), {
  type: 'line',
  data: {
    labels: <?= $diasLabels ?>,
    datasets: [{
      label: 'Ventas',
      data: <?= $diasVentas ?>,
      fill: true,
      backgroundColor: hexToRgba(colorPrimario, 0.3),
      borderColor: colorPrimario,
      borderWidth: 2,
      tension: 0.4,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => '$' + ctx.parsed.y.toLocaleString('es-MX', {minimumFractionDigits: 2})
        }
      }
    },
    scales: {
      y: {
        ticks: { callback: v => '$' + v.toLocaleString('es-MX') },
        beginAtZero: true
      }
    }
  }
});

// ═══════════════════════════════════════════════════════════════════
// 3. GRÁFICA: Estados de Pedidos (dona)
// ═══════════════════════════════════════════════════════════════════
<?php
$estadosLabels = !empty($datosGraficas['estadosPedidos']) ? json_encode(array_map('ucfirst', array_map(function($e) {
  return str_replace('_', ' ', $e);
}, array_column($datosGraficas['estadosPedidos'], 'estado')))) : '[]';
$estadosTotales = !empty($datosGraficas['estadosPedidos']) ? json_encode(array_map('intval', array_column($datosGraficas['estadosPedidos'], 'total'))) : '[]';
$estadosColores = !empty($datosGraficas['estadosPedidos']) ? json_encode(array_map(function($e) {
  $colores = [
    'pendiente' => '#92400E',
    'confirmado' => '#1E40AF',
    'en_preparacion' => '#5B21B6',
    'en_ruta' => '#065F46',
    'entregado' => '#059669',
    'cancelado' => '#991B1B'
  ];
  return $colores[$e['estado']] ?? '#6B7280';
}, $datosGraficas['estadosPedidos'])) : '[]';
?>
new Chart(document.getElementById('chartEstadosPedidos'), {
  type: 'doughnut',
  data: {
    labels: <?= $estadosLabels ?>,
    datasets: [{
      data: <?= $estadosTotales ?>,
      backgroundColor: <?= $estadosColores ?>,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: { position: 'right' },
      tooltip: {
        callbacks: {
          label: ctx => ctx.label + ': ' + ctx.parsed + ' pedidos'
        }
      }
    }
  }
});

// ═══════════════════════════════════════════════════════════════════
// 4. GRÁFICA: Usuarios por Rol (barra horizontal)
// ═══════════════════════════════════════════════════════════════════
<?php
$rolesLabels = !empty($datosGraficas['usuariosPorRol']) ? json_encode(array_column($datosGraficas['usuariosPorRol'], 'rol')) : '[]';
$rolesTotales = !empty($datosGraficas['usuariosPorRol']) ? json_encode(array_map('intval', array_column($datosGraficas['usuariosPorRol'], 'total'))) : '[]';
?>
new Chart(document.getElementById('chartUsuariosRol'), {
  type: 'bar',
  data: {
    labels: <?= $rolesLabels ?>,
    datasets: [{
      label: 'Usuarios activos',
      data: <?= $rolesTotales ?>,
      backgroundColor: [colorPrimario, '#3B82F6', '#059669'],
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: { display: false },
    },
    scales: {
      x: {
        ticks: { stepSize: 1 },
        beginAtZero: true
      }
    }
  }
});

// ═══════════════════════════════════════════════════════════════════
// 5. GRÁFICA: Top Productos (barra horizontal)
// ═══════════════════════════════════════════════════════════════════
<?php
$productosLabels = !empty($datosGraficas['topProductos']) ? json_encode(array_map(function($p) {
  return $p['nombre'] . ' (' . $p['presentacion'] . ')';
}, $datosGraficas['topProductos'])) : '[]';
$productosTotales = !empty($datosGraficas['topProductos']) ? json_encode(array_map('floatval', array_column($datosGraficas['topProductos'], 'total_vendido'))) : '[]';
?>
new Chart(document.getElementById('chartTopProductos'), {
  type: 'bar',
  data: {
    labels: <?= $productosLabels ?>,
    datasets: [{
      label: 'Cantidad vendida',
      data: <?= $productosTotales ?>,
      backgroundColor: '#10B981',
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: { display: false },
    },
    scales: {
      x: {
        ticks: { callback: v => v.toLocaleString('es-MX', {minimumFractionDigits: 1}) },
        beginAtZero: true
      }
    }
  }
});

// ═══════════════════════════════════════════════════════════════════
// 6. GRÁFICA: Métodos de Pago (barra vertical)
// ═══════════════════════════════════════════════════════════════════
<?php
$metodosLabels = !empty($datosGraficas['metodosPago']) ? json_encode(array_map('ucfirst', array_column($datosGraficas['metodosPago'], 'metodo_pago'))) : '[]';
$metodosTotales = !empty($datosGraficas['metodosPago']) ? json_encode(array_map('intval', array_column($datosGraficas['metodosPago'], 'total'))) : '[]';
?>
new Chart(document.getElementById('chartMetodosPago'), {
  type: 'bar',
  data: {
    labels: <?= $metodosLabels ?>,
    datasets: [{
      label: 'Pedidos',
      data: <?= $metodosTotales ?>,
      backgroundColor: ['#3B82F6', '#8B5CF6', '#F59E0B'],
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: { display: false },
    },
    scales: {
      y: {
        ticks: { stepSize: 1 },
        beginAtZero: true
      }
    }
  }
});
</script>
<?php endif; ?>

<?php endif; ?>

<?php if ($rol === 'admin_empresa'): ?>
<!-- Modal: Dirección de la empresa (origen para rutas Maps) -->
<div id="modalDireccion" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center"
     onclick="if(event.target===this)this.style.display='none'">
  <div style="background:#fff;border-radius:14px;padding:28px;width:520px;max-width:95vw">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
      <h3 style="font-size:1rem;font-weight:700;color:#111827;margin:0">📍 Dirección de la empresa</h3>
      <button onclick="document.getElementById('modalDireccion').style.display='none'"
              style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:#9CA3AF">✕</button>
    </div>
    <p style="font-size:.82rem;color:#6B7280;margin-bottom:16px">
      Esta dirección se usa como <strong>punto de origen</strong> en las rutas de Maps al ver los pedidos. Asegúrate de que sea tu bodega o domicilio fiscal correcto.
    </p>
    <form method="POST" action="<?= BASE_URL ?>empresa/guardarDireccion">
      <div style="margin-bottom:14px">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:5px">Dirección</label>
        <input type="text" name="direccion_fiscal"
               value="<?= htmlspecialchars($_SESSION['empresa']['direccion_fiscal'] ?? '') ?>"
               placeholder="Ej: Av. Industrial 234, Col. Centro, Guadalajara, Jalisco"
               style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px">
        <div>
          <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:5px">Latitud (opcional)</label>
          <input type="number" name="lat" step="0.0000001"
                 value="<?= htmlspecialchars($_SESSION['empresa']['lat'] ?? '') ?>"
                 placeholder="20.6597"
                 style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box">
        </div>
        <div>
          <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:5px">Longitud (opcional)</label>
          <input type="number" name="lng" step="0.0000001"
                 value="<?= htmlspecialchars($_SESSION['empresa']['lng'] ?? '') ?>"
                 placeholder="-103.3496"
                 style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box">
        </div>
      </div>
      <div style="font-size:.75rem;color:#9CA3AF;margin-bottom:16px">
        💡 Obtén coordenadas exactas: Google Maps → clic derecho en tu dirección → copia los números.
      </div>
      <button type="submit"
              style="width:100%;padding:11px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.9rem">
        Guardar dirección
      </button>
    </form>
  </div>
</div>
<?php endif; ?>
