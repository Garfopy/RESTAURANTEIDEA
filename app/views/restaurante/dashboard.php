<?php ob_start(); ?>
<?php
// Onboarding banner — checklist primera vez (modelo marketplace: sin mesas/staff de piso)
$pasos = [
  ['ok' => !empty($restaurante['telefono']) && !empty($restaurante['direccion']),
   'label' => 'Completa la información del negocio', 'url' => 'rest-config/index'],
  ['ok' => (int)($restaurante['total_platillos'] ?? 0) > 0,
   'label' => 'Agrega productos a tu menú',           'url' => 'rest-menu/index'],
  ['ok' => !empty($restaurante['lat']) && !empty($restaurante['lng']),
   'label' => 'Ubica tu negocio en el mapa',           'url' => 'rest-config/index'],
];
$completados = count(array_filter($pasos, fn($p) => $p['ok']));
$totalPasos  = count($pasos);

$fmt = static fn($v) => '$' . number_format((float)$v, 2);
?>

<?php if ($completados < $totalPasos): ?>
<div style="background:linear-gradient(135deg,#FEF3C7 0%,#FFFBEB 100%);border:1px solid #FDE68A;
            border-radius:14px;padding:20px;margin-bottom:20px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px">
    <div>
      <div style="font-weight:700;color:#92400E;font-size:1rem">🚀 Configura tu negocio</div>
      <div style="font-size:.82rem;color:#78350F;margin-top:2px">
        Te faltan <strong><?= $totalPasos - $completados ?> paso<?= ($totalPasos - $completados) !== 1 ? 's' : '' ?></strong> para empezar a operar.
      </div>
    </div>
    <div style="font-size:.82rem;color:#92400E;font-weight:600"><?= $completados ?>/<?= $totalPasos ?></div>
  </div>
  <div style="background:#FDE68A;height:6px;border-radius:3px;overflow:hidden;margin-bottom:14px">
    <div style="background:#F59E0B;height:100%;width:<?= ($completados / $totalPasos) * 100 ?>%;transition:.3s"></div>
  </div>
  <div style="display:grid;gap:6px">
    <?php foreach ($pasos as $p): ?>
    <a href="<?= BASE_URL . $p['url'] ?>" style="display:flex;align-items:center;gap:10px;
            padding:8px 12px;border-radius:8px;text-decoration:none;
            background:<?= $p['ok'] ? '#D1FAE5' : '#fff' ?>;
            border:1px solid <?= $p['ok'] ? '#A7F3D0' : '#FDE68A' ?>;transition:.15s">
      <span style="font-size:1rem"><?= $p['ok'] ? '✅' : '⏳' ?></span>
      <span style="flex:1;font-size:.85rem;color:<?= $p['ok'] ? '#065F46' : '#78350F' ?>;font-weight:500">
        <?= htmlspecialchars($p['label']) ?>
      </span>
      <?php if (!$p['ok']): ?>
      <span style="font-size:.78rem;color:#92400E;font-weight:600">Configurar →</span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- KPIs principales -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin-bottom:20px">
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Ventas de hoy</div>
    <div style="font-size:1.4rem;font-weight:700;color:#111827"><?= $fmt($kpisHoy['ingresos']) ?></div>
    <div style="font-size:.74rem;color:#9CA3AF;margin-top:4px"><?= (int)$kpisHoy['totalPedidos'] ?> pedido<?= (int)$kpisHoy['totalPedidos'] === 1 ? '' : 's' ?></div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Ventas del mes</div>
    <div style="font-size:1.4rem;font-weight:700;color:#111827"><?= $fmt($kpisMes['ingresos']) ?></div>
    <div style="font-size:.74rem;color:#9CA3AF;margin-top:4px">Ticket promedio <?= $fmt($kpisMes['ticketPromedio']) ?></div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Pedidos activos</div>
    <div style="font-size:1.4rem;font-weight:700;color:#111827"><?= (int)($restaurante['pedidos_activos'] ?? 0) ?></div>
    <div style="font-size:.74rem;color:#9CA3AF;margin-top:4px">Pendientes / en preparación / en camino</div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Alertas de inventario</div>
    <div style="font-size:1.4rem;font-weight:700;color:<?= count($alertas) > 0 ? '#DC2626' : '#111827' ?>"><?= count($alertas) ?></div>
    <div style="font-size:.74rem;color:#9CA3AF;margin-top:4px">Ingredientes con stock bajo</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px" class="dash-grid-2">
  <!-- Resumen del mes -->
  <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:20px">
    <div style="font-weight:800;color:#111827;margin-bottom:16px">Resumen del mes — <?= date('F Y') ?></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px">
      <div>
        <div style="font-size:.74rem;color:#6B7280">Ingresos</div>
        <div style="font-size:1.1rem;font-weight:700;color:#10B981"><?= $fmt($kpisMes['ingresos']) ?></div>
      </div>
      <div>
        <div style="font-size:.74rem;color:#6B7280">Gastos</div>
        <div style="font-size:1.1rem;font-weight:700;color:#EF4444"><?= $fmt($kpisMes['gastos']) ?></div>
      </div>
      <div>
        <div style="font-size:.74rem;color:#6B7280">Retiros</div>
        <div style="font-size:1.1rem;font-weight:700;color:#EF4444"><?= $fmt($kpisMes['retiros']) ?></div>
      </div>
      <div>
        <div style="font-size:.74rem;color:#6B7280">Utilidad</div>
        <div style="font-size:1.1rem;font-weight:700;color:#111827"><?= $fmt($kpisMes['utilidad']) ?></div>
      </div>
      <div>
        <div style="font-size:.74rem;color:#6B7280">Margen</div>
        <div style="font-size:1.1rem;font-weight:700;color:#111827"><?= number_format($kpisMes['margen'], 1) ?>%</div>
      </div>
      <div>
        <div style="font-size:.74rem;color:#6B7280">Pendiente por cobrar</div>
        <div style="font-size:1.1rem;font-weight:700;color:#F59E0B"><?= $fmt($kpisMes['pendientePorCobrar']) ?></div>
      </div>
    </div>

    <?php if (!empty($kpisMes['porTipoEntrega'])): ?>
    <div style="border-top:1px solid #F3F4F6;margin-top:16px;padding-top:14px">
      <div style="font-size:.78rem;color:#6B7280;margin-bottom:8px;font-weight:600">Ventas por tipo de entrega</div>
      <?php
      $etiquetasEntrega = ['pickup' => 'Recoger en tienda', 'delivery' => 'A domicilio', 'take_out' => 'Para llevar'];
      $maxEntrega = max(array_column($kpisMes['porTipoEntrega'], 'total') ?: [1]);
      ?>
      <?php foreach ($kpisMes['porTipoEntrega'] as $te): ?>
      <div style="margin-bottom:8px">
        <div style="display:flex;justify-content:space-between;font-size:.8rem;margin-bottom:4px">
          <span style="color:#374151"><?= htmlspecialchars($etiquetasEntrega[$te['tipo_pedido']] ?? ($te['tipo_pedido'] ?: 'Sin especificar')) ?></span>
          <strong><?= $fmt($te['total']) ?> · <?= (int)$te['c'] ?></strong>
        </div>
        <div style="height:7px;background:#F3F4F6;border-radius:99px;overflow:hidden">
          <div style="height:100%;width:<?= min(100, round(((float)$te['total'] / max($maxEntrega, 1)) * 100)) ?>%;background:#A97C3F;border-radius:99px"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Alertas de inventario -->
  <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:20px">
    <div style="font-weight:800;color:#111827;margin-bottom:14px">⚠️ Stock bajo</div>
    <?php if (empty($alertas)): ?>
    <p style="color:#9CA3AF;font-size:.85rem;margin:0">Todo el inventario está en niveles normales.</p>
    <?php else: ?>
    <?php foreach (array_slice($alertas, 0, 6) as $a): ?>
    <div style="padding:8px 0;border-bottom:1px solid #F3F4F6;font-size:.85rem;display:flex;justify-content:space-between;gap:8px">
      <span style="color:#111827;font-weight:500"><?= htmlspecialchars($a['nombre']) ?></span>
      <span style="color:#DC2626;font-weight:700;white-space:nowrap"><?= (float)$a['stock'] ?> / <?= (float)$a['stock_minimo'] ?> <?= htmlspecialchars($a['unidad_principal']) ?></span>
    </div>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>rest-inventario/index" style="display:block;text-align:center;margin-top:12px;font-size:.82rem;color:#A97C3F;font-weight:600;text-decoration:none">Ver inventario completo →</a>
    <?php endif; ?>
  </div>
</div>

<!-- Productos: más / menos vendidos -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px">
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <div style="font-weight:700;color:#111827">🔥 Más vendidos</div>
      <span style="font-size:.7rem;color:#9CA3AF">últimos 365 días</span>
    </div>
    <?php if (empty($topVendidos)): ?>
    <p style="color:#9CA3AF;font-size:.875rem;margin:0">Aún no hay ventas registradas.</p>
    <?php else: ?>
    <?php foreach ($topVendidos as $i => $p): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;
                border-bottom:1px solid #F3F4F6;font-size:.88rem;gap:10px">
      <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1">
        <span style="display:inline-flex;align-items:center;justify-content:center;
                     width:24px;height:24px;border-radius:8px;
                     background:<?= $i === 0 ? '#FEF3C7' : '#F3F4F6' ?>;
                     color:<?= $i === 0 ? '#92400E' : '#6B7280' ?>;
                     font-weight:800;font-size:.72rem"><?= $i + 1 ?></span>
        <span style="color:#111827;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['nombre']) ?></span>
      </div>
      <div style="display:flex;align-items:center;gap:10px;white-space:nowrap">
        <span style="color:#10B981;font-weight:700"><?= $fmt($p['precio']) ?></span>
        <span style="font-size:.72rem;color:#6B7280;background:#F3F4F6;border-radius:99px;padding:2px 8px;font-weight:600">
          <?= (int)$p['unidades_vendidas'] ?> vend.
        </span>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <div style="font-weight:700;color:#111827">📉 Menos vendidos</div>
      <span style="font-size:.7rem;color:#9CA3AF">candidatos a oferta</span>
    </div>
    <?php if (empty($menosVendidos)): ?>
    <p style="color:#9CA3AF;font-size:.875rem;margin:0">Sin platillos activos.</p>
    <?php else: ?>
    <?php foreach ($menosVendidos as $p): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;
                border-bottom:1px solid #F3F4F6;font-size:.88rem;gap:10px">
      <div style="display:flex;align-items:center;gap:8px;min-width:0;flex:1">
        <span style="color:#374151;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['nombre']) ?></span>
        <?php if ((int)$p['unidades_vendidas'] === 0): ?>
        <span style="font-size:.65rem;color:#92400E;background:#FEF3C7;border:1px solid #FCD34D;border-radius:99px;padding:1px 7px;font-weight:700">sin ventas</span>
        <?php endif; ?>
      </div>
      <div style="display:flex;align-items:center;gap:10px;white-space:nowrap">
        <span style="color:#EF4444;font-weight:700"><?= $fmt($p['precio']) ?></span>
        <span style="font-size:.72rem;color:#6B7280;background:#F3F4F6;border-radius:99px;padding:2px 8px;font-weight:600">
          <?= (int)$p['unidades_vendidas'] ?> vend.
        </span>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<style>
@media (max-width: 860px) {
  .dash-grid-2 { grid-template-columns: 1fr !important; }
}
</style>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
