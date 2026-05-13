<?php ob_start(); ?>
<!-- KPI Cards -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
  <?php
  $cards = [
    ['label'=>'Ingresos del mes', 'val'=>'$'.number_format($kpis['ingresos'],2), 'color'=>'#10B981'],
    ['label'=>'Gastos del mes',   'val'=>'$'.number_format($kpis['gastos'],2),   'color'=>'#EF4444'],
    ['label'=>'Utilidad neta',    'val'=>'$'.number_format($kpis['utilidad'],2),  'color'=>'#6366F1'],
    ['label'=>'Margen',           'val'=>$kpis['margen'].'%',                    'color'=>'#F59E0B'],
  ];
  foreach ($cards as $c): ?>
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280;margin-bottom:6px"><?= $c['label'] ?></div>
    <div style="font-size:1.5rem;font-weight:700;color:<?= $c['color'] ?>"><?= htmlspecialchars($c['val']) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Mesas activas</div>
    <div style="font-size:1.4rem;font-weight:700;color:#111827"><?= (int)($restaurante['mesas_ocupadas'] ?? 0) ?> / <?= (int)($restaurante['total_mesas'] ?? 0) ?></div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Pedidos activos</div>
    <div style="font-size:1.4rem;font-weight:700;color:#F59E0B"><?= count($activos) ?></div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Alertas inventario</div>
    <div style="font-size:1.4rem;font-weight:700;color:<?= count($alertas) > 0 ? '#EF4444' : '#10B981' ?>"><?= count($alertas) ?></div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-size:.8rem;color:#6B7280">Pendiente por cobrar</div>
    <div style="font-size:1.4rem;font-weight:700;color:#EF4444">$<?= number_format($kpis['pendiente'],2) ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
  <!-- Pedidos activos -->
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-weight:600;margin-bottom:14px">Pedidos en cocina</div>
    <?php if (empty($activos)): ?>
    <p style="color:#9CA3AF;font-size:.875rem">No hay pedidos activos.</p>
    <?php else: ?>
    <?php foreach (array_slice($activos, 0, 10) as $item): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #F3F4F6;font-size:.85rem">
      <span style="color:#374151"><strong><?= htmlspecialchars($item['folio'] ?? '') ?></strong> — <?= htmlspecialchars($item['mesa_nombre'] ?? '—') ?></span>
      <span style="padding:2px 8px;border-radius:99px;font-size:.75rem;font-weight:500;
        background:<?= $item['item_estado']==='en_preparacion' ? '#FEF3C7' : '#DBEAFE' ?>;
        color:<?= $item['item_estado']==='en_preparacion' ? '#92400E' : '#1E40AF' ?>">
        <?= htmlspecialchars($item['platillo_nombre']) ?>
      </span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Próximas reservas -->
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-weight:600;margin-bottom:14px">Próximas reservaciones</div>
    <?php if (empty($proximas)): ?>
    <p style="color:#9CA3AF;font-size:.875rem">Sin reservaciones próximas.</p>
    <?php else: ?>
    <?php foreach ($proximas as $r): ?>
    <div style="padding:8px 0;border-bottom:1px solid #F3F4F6;font-size:.85rem">
      <div style="font-weight:500"><?= htmlspecialchars($r['nombre']) ?> — <?= $r['personas'] ?> personas</div>
      <div style="color:#6B7280"><?= date('d/m H:i', strtotime($r['fecha'].' '.$r['hora'])) ?> <?= $r['mesa_nombre'] ? '· '.$r['mesa_nombre'] : '' ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
