<?php ob_start(); ?>
<!-- Links rápidos del restaurante -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
  <!-- Staff login -->
  <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;padding:16px 18px">
    <div style="font-weight:700;color:#065F46;font-size:.88rem;margin-bottom:6px">🔑 Portal del equipo</div>
    <div style="font-family:monospace;font-size:.78rem;color:#374151;word-break:break-all;margin-bottom:10px">
      <?= htmlspecialchars($linkStaff ?? '') ?>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="navegarCopiar('<?= htmlspecialchars(addslashes($linkStaff ?? ''), ENT_QUOTES) ?>',this)"
              class="btn btn-sm btn-outline" style="border-color:#10B981;color:#10B981;font-size:.75rem">Copiar</button>
      <a href="<?= htmlspecialchars($linkStaff ?? '') ?>" target="_blank"
         class="btn btn-sm" style="background:#10B981;color:#fff;font-size:.75rem">Abrir ↗</a>
    </div>
  </div>
  <!-- Menú público -->
  <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:12px;padding:16px 18px">
    <div style="font-weight:700;color:#92400E;font-size:.88rem;margin-bottom:6px">📱 Menú público (clientes)</div>
    <div style="font-family:monospace;font-size:.78rem;color:#374151;word-break:break-all;margin-bottom:10px">
      <?= htmlspecialchars($linkMenu ?? '') ?>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="navegarCopiar('<?= htmlspecialchars(addslashes($linkMenu ?? ''), ENT_QUOTES) ?>',this)"
              class="btn btn-sm btn-outline" style="border-color:#F59E0B;color:#F59E0B;font-size:.75rem">Copiar</button>
      <a href="<?= htmlspecialchars($linkMenu ?? '') ?>" target="_blank"
         class="btn btn-sm" style="background:#F59E0B;color:#fff;font-size:.75rem">Ver menú ↗</a>
    </div>
  </div>
</div>

<?php
// Onboarding banner — checklist primera vez
$pasos = [
  ['ok' => !empty($restaurante['telefono']) && !empty($restaurante['direccion']),
   'label' => 'Completa la información del restaurante', 'url' => 'rest-config/index'],
  ['ok' => (int)($restaurante['total_mesas'] ?? 0) > 0,
   'label' => 'Crea al menos una mesa o silla',           'url' => 'rest-mesa/index'],
  ['ok' => (int)($restaurante['total_platillos'] ?? 0) > 0,
   'label' => 'Agrega platillos al menú',                  'url' => 'rest-menu/index'],
  ['ok' => (int)($restaurante['total_staff'] ?? 0) > 0,
   'label' => 'Invita a tu staff (mesero, chef, portero)', 'url' => 'rest-staff/index'],
];
$completados = count(array_filter($pasos, fn($p) => $p['ok']));
$totalPasos  = count($pasos);
?>
<?php if ($completados < $totalPasos): ?>
<div style="background:linear-gradient(135deg,#FEF3C7 0%,#FFFBEB 100%);border:1px solid #FDE68A;
            border-radius:14px;padding:20px;margin-bottom:20px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px">
    <div>
      <div style="font-weight:700;color:#92400E;font-size:1rem">🚀 Configura tu restaurante</div>
      <div style="font-size:.82rem;color:#78350F;margin-top:2px">
        Te faltan <strong><?= $totalPasos - $completados ?> paso<?= ($totalPasos-$completados)!==1?'s':'' ?></strong> para empezar a operar.
      </div>
    </div>
    <div style="font-size:.82rem;color:#92400E;font-weight:600">
      <?= $completados ?>/<?= $totalPasos ?>
    </div>
  </div>
  <div style="background:#FDE68A;height:6px;border-radius:3px;overflow:hidden;margin-bottom:14px">
    <div style="background:#F59E0B;height:100%;width:<?= ($completados/$totalPasos)*100 ?>%;transition:.3s"></div>
  </div>
  <div style="display:grid;gap:6px">
    <?php foreach ($pasos as $p): ?>
    <a href="<?= BASE_URL . $p['url'] ?>" style="display:flex;align-items:center;gap:10px;
            padding:8px 12px;border-radius:8px;text-decoration:none;
            background:<?= $p['ok'] ? '#D1FAE5' : '#fff' ?>;
            border:1px solid <?= $p['ok'] ? '#A7F3D0' : '#FDE68A' ?>;transition:.15s"
            onmouseover="this.style.transform='translateX(2px)'"
            onmouseout="this.style.transform=''">
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

<!-- KPI Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px">
  <?php
  $cards = [
    ['label'=>'Ingresos del mes', 'val'=>'$'.number_format($kpis['ingresos'],2), 'color'=>'#10B981'],
    ['label'=>'Gastos del mes',   'val'=>'$'.number_format($kpis['gastos'],2),   'color'=>'#EF4444'],
    ['label'=>'Utilidad neta',    'val'=>'$'.number_format($kpis['utilidad'],2),  'color'=>'#6366F1'],
    ['label'=>'Margen',           'val'=>$kpis['margen'].'%',                    'color'=>'#F59E0B'],
    ['label'=>'Ticket promedio',  'val'=>'$'.number_format($kpis['ticketPromedio'],2), 'color'=>'#0F766E'],
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

<!-- Acceso rápido propinas -->
<div style="margin-top:20px;background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB;
            display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-weight:700;color:#111827">💰 Propinas del día</div>
    <div style="font-size:.82rem;color:#6B7280;margin-top:2px">
      Consulta y registra las propinas de cada mesero para entregar al final del turno.
    </div>
  </div>
  <a href="<?= BASE_URL ?>rest-propinas/index"
     style="padding:9px 20px;border-radius:10px;background:#10B981;color:#fff;
            font-weight:700;font-size:.87rem;text-decoration:none;white-space:nowrap">
    Ver propinas →
  </a>
</div>
<script>
function navegarCopiar(url, btn) {
  navigator.clipboard.writeText(url).then(() => {
    const orig = btn.textContent;
    btn.textContent = '✓ Copiado';
    setTimeout(() => { btn.textContent = orig; }, 1500);
  });
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
