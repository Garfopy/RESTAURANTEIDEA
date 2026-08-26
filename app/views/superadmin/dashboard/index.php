<?php ob_start(); ?>

<style>
.sa-header { margin-bottom: 18px; }
.sa-title { color: #0F172A; font-size: 1.45rem; font-weight: 800; margin: 0; }
.sa-copy { color: #64748B; font-size: .9rem; margin: 4px 0 0; }
.sa-kpi-grid { display: grid; gap: 14px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 22px; }
.sa-kpi {
  background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px 18px;
}
.sa-kpi-label { color: #64748B; font-size: .78rem; font-weight: 600; text-transform: uppercase; }
.sa-kpi-value { color: #0F172A; font-size: 1.6rem; font-weight: 800; margin-top: 4px; }
.sa-kpi-sub { color: #94A3B8; font-size: .78rem; margin-top: 2px; }
.sa-estado-row { display: flex; gap: 10px; margin-top: 10px; }
.sa-estado-chip { border-radius: 8px; flex: 1; padding: 8px; text-align: center; }
.sa-estado-chip .n { font-size: 1.1rem; font-weight: 800; }
.sa-estado-chip .l { font-size: .68rem; text-transform: uppercase; }
.sa-section-title { color: #0F172A; font-size: 1.05rem; font-weight: 700; margin: 0 0 12px; }
</style>

<div class="sa-header">
  <h1 class="sa-title">Dashboard</h1>
  <p class="sa-copy">Vista global de la plataforma.</p>
</div>

<div class="sa-kpi-grid">
  <div class="sa-kpi">
    <div class="sa-kpi-label">Negocios</div>
    <div class="sa-kpi-value"><?= (int)$resumen['negocios_total'] ?></div>
    <div class="sa-estado-row">
      <div class="sa-estado-chip" style="background:#D1FAE5"><div class="n" style="color:#065F46"><?= (int)$resumen['negocios_por_estado']['activo'] ?></div><div class="l" style="color:#065F46">Activos</div></div>
      <div class="sa-estado-chip" style="background:#FEF3C7"><div class="n" style="color:#92400E"><?= (int)$resumen['negocios_por_estado']['pendiente'] ?></div><div class="l" style="color:#92400E">Pendientes</div></div>
      <div class="sa-estado-chip" style="background:#FEE2E2"><div class="n" style="color:#991B1B"><?= (int)$resumen['negocios_por_estado']['suspendido'] ?></div><div class="l" style="color:#991B1B">Suspendidos</div></div>
    </div>
  </div>
  <div class="sa-kpi">
    <div class="sa-kpi-label">Ventas del mes</div>
    <div class="sa-kpi-value">$<?= number_format($resumen['ventas_mes'], 2) ?></div>
    <div class="sa-kpi-sub">Total histórico: $<?= number_format($resumen['ventas_totales'], 2) ?></div>
  </div>
  <div class="sa-kpi">
    <div class="sa-kpi-label">Pedidos totales</div>
    <div class="sa-kpi-value"><?= (int)$resumen['pedidos_totales'] ?></div>
  </div>
  <div class="sa-kpi">
    <div class="sa-kpi-label">Usuarios</div>
    <div class="sa-kpi-value"><?= (int)$resumen['usuarios_negocio'] ?></div>
    <div class="sa-kpi-sub"><?= (int)$resumen['usuarios_app'] ?> en la app móvil</div>
  </div>
</div>

<div class="rst-card">
  <h2 class="sa-section-title" style="padding:16px 16px 0">Ranking de negocios por ventas</h2>
  <div class="rst-table-wrap">
    <table class="rst-table">
      <thead><tr><th>Negocio</th><th>Ventas</th></tr></thead>
      <tbody>
        <?php foreach ($resumen['ranking'] as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['nombre']) ?></td>
          <td>$<?= number_format((float)$r['ventas'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($resumen['ranking'])): ?>
        <tr><td colspan="2" style="text-align:center;color:#94A3B8;padding:24px">Sin datos todavía.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/superadmin/layouts/main.php';
