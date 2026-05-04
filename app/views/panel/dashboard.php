<?php
// Vista: Panel Dashboard (KPIs globales)
// Variables disponibles: $totalEmpresas, $totalUsuarios, $pedidosMes, $ventasMes,
//                        $ultimosPedidos, $stockBajo, $esSuperAdmin

function estadoBadge(string $estado): string {
    return match($estado) {
        'pendiente'       => '<span style="background:#FEF3C7;color:#92400E;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">Pendiente</span>',
        'confirmado'      => '<span style="background:#DBEAFE;color:#1E40AF;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">Confirmado</span>',
        'en_preparacion'  => '<span style="background:#EDE9FE;color:#5B21B6;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">En prep.</span>',
        'en_ruta'         => '<span style="background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">En ruta</span>',
        'entregado'       => '<span style="background:#D1FAE5;color:#065F46;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">Entregado</span>',
        'cancelado'       => '<span style="background:#FEE2E2;color:#991B1B;padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600">Cancelado</span>',
        default           => '<span style="background:#F3F4F6;color:#374151;padding:2px 8px;border-radius:999px;font-size:.75rem">' . htmlspecialchars($estado) . '</span>',
    };
}
?>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px">
  <?php
  $kpis = [
    ['label'=>'Empresas activas','valor'=>$totalEmpresas,'color'=>'#EFF6FF','text'=>'#1E40AF','icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16'],
    ['label'=>'Usuarios activos','valor'=>$totalUsuarios,'color'=>'#F0FDF4','text'=>'#166534','icon'=>'M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 110-8 4 4 0 010 8z'],
    ['label'=>'Pedidos este mes','valor'=>$pedidosMes,'color'=>'#FFF7ED','text'=>'#9A3412','icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2'],
    ['label'=>'Ventas este mes','valor'=>'$'.number_format($ventasMes,2),'color'=>'#FFF1F2','text'=>'#9F1239','icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1'],
  ];
  foreach ($kpis as $kpi): ?>
  <div style="background:<?= $kpi['color'] ?>;border-radius:12px;padding:20px">
    <div style="font-size:.8rem;color:<?= $kpi['text'] ?>;font-weight:600;margin-bottom:8px"><?= $kpi['label'] ?></div>
    <div style="font-size:1.75rem;font-weight:800;color:<?= $kpi['text'] ?>"><?= $kpi['valor'] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Alerta stock bajo -->
<?php if (!empty($stockBajo)): ?>
<div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:14px 18px;margin-bottom:20px">
  <p style="font-weight:700;color:#9A3412;margin-bottom:8px;font-size:.875rem">Stock bajo en <?= count($stockBajo) ?> producto(s)</p>
  <div style="display:flex;flex-wrap:wrap;gap:8px">
    <?php foreach ($stockBajo as $s): ?>
    <span style="background:#FEF3C7;color:#92400E;padding:3px 10px;border-radius:999px;font-size:.75rem;font-weight:600">
      <?= htmlspecialchars($s['nombre']) ?> (<?= $s['stock'] ?> / mín <?= $s['umbral_minimo'] ?>)
    </span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Últimos pedidos -->
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <div style="padding:16px 20px;border-bottom:1px solid #F3F4F6;display:flex;justify-content:space-between;align-items:center">
    <h2 style="font-size:.95rem;font-weight:700;color:#111827">Últimos pedidos</h2>
    <a href="<?= BASE_URL ?>panel-pedido/index" style="font-size:.8rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todos</a>
  </div>
  <?php if (empty($ultimosPedidos)): ?>
    <p style="padding:24px;text-align:center;color:#6B7280;font-size:.875rem">Sin pedidos aún.</p>
  <?php else: ?>
  <table style="width:100%;border-collapse:collapse;font-size:.85rem">
    <thead>
      <tr style="background:#F9FAFB">
        <th style="padding:10px 16px;text-align:left;color:#6B7280;font-weight:600">Folio</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Empresa</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Comprador</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Estado</th>
        <th style="padding:10px;text-align:right;color:#6B7280;font-weight:600">Total</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Fecha</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($ultimosPedidos as $ped): ?>
      <tr style="border-top:1px solid #F3F4F6">
        <td style="padding:10px 16px;font-weight:600;color:#111827"><?= htmlspecialchars($ped['folio']) ?></td>
        <td style="padding:10px;color:#374151"><?= htmlspecialchars($ped['empresa']) ?></td>
        <td style="padding:10px;color:#374151"><?= htmlspecialchars($ped['comprador']) ?></td>
        <td style="padding:10px"><?= estadoBadge($ped['estado']) ?></td>
        <td style="padding:10px;text-align:right;font-weight:600">$<?= number_format($ped['total'],2) ?></td>
        <td style="padding:10px;color:#6B7280;font-size:.8rem"><?= date('d/m/Y', strtotime($ped['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
