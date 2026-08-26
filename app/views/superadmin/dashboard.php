<?php ob_start(); ?>
<?php $fmt = static fn($v) => '$' . number_format((float)$v, 2); ?>

<h1 style="font-size:1.3rem;font-weight:800;margin-bottom:20px">Dashboard de la plataforma</h1>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px">
  <div style="background:#fff;border-radius:12px;padding:18px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Negocios totales</div>
    <div style="font-size:1.5rem;font-weight:800"><?= (int)($negocios['total'] ?? 0) ?></div>
    <div style="font-size:.74rem;color:#9CA3AF;margin-top:4px"><?= (int)($negocios['activos'] ?? 0) ?> activos</div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:18px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Ventas de hoy (plataforma)</div>
    <div style="font-size:1.5rem;font-weight:800"><?= $fmt($kpisHoy['v'] ?? 0) ?></div>
    <div style="font-size:.74rem;color:#9CA3AF;margin-top:4px"><?= (int)($kpisHoy['c'] ?? 0) ?> pedidos</div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:18px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Ventas del mes (plataforma)</div>
    <div style="font-size:1.5rem;font-weight:800"><?= $fmt($kpisMes['v'] ?? 0) ?></div>
    <div style="font-size:.74rem;color:#9CA3AF;margin-top:4px"><?= (int)($kpisMes['c'] ?? 0) ?> pedidos</div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:18px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Usuarios app móvil</div>
    <div style="font-size:1.5rem;font-weight:800"><?= (int)$usuariosApp ?></div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:18px;border:<?= $pendientes > 0 ? '1px solid #FCA5A5' : '1px solid #E5E7EB' ?>">
    <div style="font-size:.8rem;color:#6B7280">Negocios suspendidos</div>
    <div style="font-size:1.5rem;font-weight:800;color:<?= $pendientes > 0 ? '#DC2626' : '#111827' ?>"><?= (int)$pendientes ?></div>
  </div>
</div>

<div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
  <div style="font-weight:700;margin-bottom:14px">Ranking de negocios — este mes</div>
  <?php if (empty($ranking)): ?>
  <p style="color:#9CA3AF;font-size:.875rem;margin:0">Aún no hay ventas registradas en la plataforma.</p>
  <?php else: ?>
  <table style="width:100%;border-collapse:collapse;font-size:.88rem">
    <thead>
      <tr style="text-align:left;color:#6B7280;font-size:.75rem;text-transform:uppercase">
        <th style="padding:8px 0">Negocio</th>
        <th style="padding:8px 0;text-align:right">Pedidos</th>
        <th style="padding:8px 0;text-align:right">Ventas</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($ranking as $r): ?>
      <tr style="border-top:1px solid #F3F4F6">
        <td style="padding:9px 0;font-weight:600"><?= htmlspecialchars($r['nombre']) ?></td>
        <td style="padding:9px 0;text-align:right"><?= (int)$r['pedidos'] ?></td>
        <td style="padding:9px 0;text-align:right;font-weight:700;color:#10B981"><?= $fmt($r['ventas']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$activeMenu = 'dashboard';
require ROOT_PATH . '/app/views/superadmin/layout.php';
