<?php ob_start(); ?>
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

<?php
$ingCmp = (float)($comparativaFinanzas['ingresos'] ?? 0);
$perCmp = (float)($comparativaFinanzas['perdidas'] ?? 0);
$netCmp = (float)($comparativaFinanzas['neto'] ?? 0);
$maxCmp = max($ingCmp, $perCmp, 1);
$ingPct = min(100, round(($ingCmp / $maxCmp) * 100));
$perPct = min(100, round(($perCmp / $maxCmp) * 100));
$ingresosTickets = (float)($kpis['ingresosTickets'] ?? $ingCmp);
$ingresosPedidosApp = (float)($kpis['ingresosPedidosApp'] ?? 0);
$ingresosRecargasAmare = (float)($kpis['ingresosRecargasAmare'] ?? 0);
$ingresosGenerales = $ingresosTickets + $ingresosPedidosApp;
$perdidasGenerales = (float)($kpis['gastos'] ?? 0) + (float)($kpis['retiros'] ?? 0);
$saldoAmareUsado = (float)($amareKpis['walletUsado'] ?? 0);
$perdidasAmare = (float)($amareKpis['perdidaAmare'] ?? 0);
$reservasWeb = (int)($reservasCanal['web'] ?? 0);
$reservasMovil = (int)($reservasCanal['movil'] ?? 0);
$reservasTotal = (int)($reservasCanal['total'] ?? ($reservasWeb + $reservasMovil));
$reservasMovilPct = (float)($reservasCanal['chart_pct'] ?? 0);
$reservasWebPctLabel = (float)($reservasCanal['web_pct'] ?? 0);
$reservasMovilPctLabel = (float)($reservasCanal['movil_pct'] ?? 0);
$maxGeneral = max($ingresosGenerales, $perdidasGenerales, 1);
$maxAmare = max($ingresosRecargasAmare, $saldoAmareUsado, $perdidasAmare, 1);
$ingGeneralPct = min(100, round(($ingresosGenerales / $maxGeneral) * 100));
$perGeneralPct = min(100, round(($perdidasGenerales / $maxGeneral) * 100));
$recargasAmarePct = min(100, round(($ingresosRecargasAmare / $maxAmare) * 100));
$saldoAmarePct = min(100, round(($saldoAmareUsado / $maxAmare) * 100));
$perdidasAmarePct = min(100, round(($perdidasAmare / $maxAmare) * 100));
$amareCards = [
  [
    'label'=>'Saldo Jungle',
    'val'=>'$'.number_format((float)($amareKpis['saldo'] ?? 0), 2),
    'color'=>'#111827',
    'sub'=>'Disponible en wallets',
    'info'=>'Saldo total que los clientes todavia tienen disponible para gastar en la app.',
  ],
  [
    'label'=>'Recargas del mes',
    'val'=>'$'.number_format((float)($amareKpis['recargas'] ?? 0), 2),
    'color'=>'#111827',
    'sub'=>'Dinero recargado',
    'info'=>'Dinero real que entro al restaurante cuando los clientes cargaron Saldo Jungle durante este mes.',
  ],
  [
    'label'=>'Saldo usado',
    'val'=>'$'.number_format((float)($amareKpis['walletUsado'] ?? 0), 2),
    'color'=>'#111827',
    'sub'=>'Pagado con wallet',
    'info'=>'Saldo Jungle que los clientes gastaron en pedidos. No se suma otra vez como ingreso porque ya conto al recargarse.',
  ],
  [
    'label'=>'Puntos dados',
    'val'=>number_format((int)($amareKpis['puntosDados'] ?? 0)),
    'color'=>'#111827',
    'sub'=>'Fidelizacion generada',
    'info'=>'Puntos entregados a clientes por compras o beneficios de la app.',
  ],
  [
    'label'=>'Descuentos / puntos',
    'val'=>'$'.number_format((float)($amareKpis['perdidaAmare'] ?? 0), 2),
    'color'=>'#111827',
    'sub'=>'Costo promocional',
    'info'=>'Monto absorbido por descuentos, promociones o puntos aplicados durante el mes.',
  ],
];
$generalCards = [
  [
    'label'=>'Ingresos del mes',
    'val'=>'$'.number_format($ingCmp, 2),
    'sub'=>'Total contable',
  ],
  [
    'label'=>'Tickets',
    'val'=>'$'.number_format($ingresosTickets, 2),
    'sub'=>'Ventas en mesas',
  ],
  [
    'label'=>'Pedido app',
    'val'=>'$'.number_format($ingresosPedidosApp, 2),
    'sub'=>'Ventas desde app',
  ],
];
$infoBtn = static function (string $text, string $theme = 'light'): string {
  $classes = $theme === 'dark' ? 'kpi-info kpi-info-dark' : 'kpi-info';
  $safeText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  return '<button type="button" class="'.$classes.'" title="'.$safeText.'" data-tooltip="'.$safeText.'" aria-label="'.$safeText.'">i</button>';
};
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin-bottom:24px">
  <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:18px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px">
      <div>
        <div style="font-weight:800;color:#111827">Métricas Jungle</div>
      </div>
      <span style="font-size:.72rem;color:#6B7280;background:#F3F4F6;border-radius:99px;padding:4px 10px;font-weight:700"><?= date('M Y') ?></span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px">
      <?php foreach ($amareCards as $c): ?>
      <div style="border:1px solid #EEF2F7;background:#F9FAFB;border-radius:10px;padding:14px;min-height:100px">
        <div style="font-size:.74rem;color:#6B7280;margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;gap:8px">
          <span><?= htmlspecialchars($c['label']) ?></span>
          <?= $infoBtn($c['info']) ?>
        </div>
        <div style="font-size:1.25rem;font-weight:800;color:<?= $c['color'] ?>;line-height:1.15"><?= htmlspecialchars($c['val']) ?></div>
        <div style="font-size:.68rem;color:#9CA3AF;margin-top:8px"><?= htmlspecialchars($c['sub']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="border-top:1px solid #EEF2F7;margin-top:18px;padding-top:16px">
      <div style="font-weight:800;color:#111827;margin-bottom:12px">Ingresos generales</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px">
        <?php foreach ($generalCards as $c): ?>
        <div style="border:1px solid #EEF2F7;background:#F9FAFB;border-radius:10px;padding:14px;min-height:94px">
          <div style="font-size:.74rem;color:#6B7280;margin-bottom:8px"><?= htmlspecialchars($c['label']) ?></div>
          <div style="font-size:1.25rem;font-weight:800;color:#111827;line-height:1.15"><?= htmlspecialchars($c['val']) ?></div>
          <div style="font-size:.68rem;color:#9CA3AF;margin-top:8px"><?= htmlspecialchars($c['sub']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div style="background:#111827;color:#F9FAFB;border-radius:12px;padding:18px;border:1px solid #111827">
    <div style="font-weight:800;margin-bottom:18px">Resumen por area</div>

    <div style="display:grid;gap:18px">
      <div style="border:1px solid #253044;background:#151F2E;border-radius:12px;padding:14px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
          <span style="font-weight:800;color:#F9FAFB">Generales</span>
          <span style="font-size:.72rem;color:#CBD5E1">Ventas del restaurante</span>
        </div>

        <div style="margin-bottom:12px">
          <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:6px">
            <span style="color:#A7F3D0;font-weight:700">Ingresos</span>
            <strong>$<?= number_format($ingresosGenerales, 2) ?></strong>
          </div>
          <div style="height:9px;background:#374151;border-radius:99px;overflow:hidden">
            <div style="height:100%;width:<?= $ingGeneralPct ?>%;background:#10B981;border-radius:99px"></div>
          </div>
          <div style="display:grid;gap:4px;margin-top:8px;font-size:.72rem;color:#D1D5DB">
            <span>Tickets: $<?= number_format($ingresosTickets, 2) ?></span>
            <span>Pedido app: $<?= number_format($ingresosPedidosApp, 2) ?></span>
          </div>
        </div>

        <div>
          <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:6px">
            <span style="color:#FCA5A5;font-weight:700">Perdidas</span>
            <strong>$<?= number_format($perdidasGenerales, 2) ?></strong>
          </div>
          <div style="height:9px;background:#374151;border-radius:99px;overflow:hidden">
            <div style="height:100%;width:<?= $perGeneralPct ?>%;background:#EF4444;border-radius:99px"></div>
          </div>
          <div style="display:grid;gap:4px;margin-top:8px;font-size:.72rem;color:#D1D5DB">
            <span>Gastos: $<?= number_format((float)($kpis['gastos'] ?? 0), 2) ?></span>
            <span>Retiros: $<?= number_format((float)($kpis['retiros'] ?? 0), 2) ?></span>
          </div>
        </div>
      </div>

      <div style="border:1px solid #253044;background:#151F2E;border-radius:12px;padding:14px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
          <span style="font-weight:800;color:#F9FAFB">Jungle</span>
          <span style="font-size:.72rem;color:#CBD5E1">Wallet, puntos y descuentos</span>
        </div>

        <div style="display:grid;gap:12px">
          <div>
            <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:6px">
              <span style="color:#A7F3D0;font-weight:700">Recargas</span>
              <strong>$<?= number_format($ingresosRecargasAmare, 2) ?></strong>
            </div>
            <div style="height:9px;background:#374151;border-radius:99px;overflow:hidden">
              <div style="height:100%;width:<?= $recargasAmarePct ?>%;background:#10B981;border-radius:99px"></div>
            </div>
          </div>

          <div>
            <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:6px">
              <span style="color:#CBD5E1;font-weight:700">Saldo usado</span>
              <strong>$<?= number_format($saldoAmareUsado, 2) ?></strong>
            </div>
            <div style="height:9px;background:#374151;border-radius:99px;overflow:hidden">
              <div style="height:100%;width:<?= $saldoAmarePct ?>%;background:#94A3B8;border-radius:99px"></div>
            </div>
          </div>

          <div>
            <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:6px">
              <span style="color:#FCA5A5;font-weight:700">Descuentos / puntos</span>
              <strong>$<?= number_format($perdidasAmare, 2) ?></strong>
            </div>
            <div style="height:9px;background:#374151;border-radius:99px;overflow:hidden">
              <div style="height:100%;width:<?= $perdidasAmarePct ?>%;background:#EF4444;border-radius:99px"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin-bottom:24px">
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB;min-height:104px;display:flex;flex-direction:column;justify-content:center">
    <div style="font-size:.8rem;color:#6B7280">Mesas en uso</div>
    <div style="font-size:1.4rem;font-weight:700;color:#111827"><?= (int)($restaurante['mesas_ocupadas'] ?? 0) ?> / <?= (int)($restaurante['total_mesas'] ?? 0) ?></div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB;min-height:104px;display:flex;flex-direction:column;justify-content:center">
    <div style="font-size:.8rem;color:#6B7280">Pedidos activos</div>
    <div style="font-size:1.4rem;font-weight:700;color:#111827"><?= count($activos) ?></div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB;min-height:104px;display:flex;flex-direction:column;justify-content:center">
    <div style="font-size:.8rem;color:#6B7280">Alertas inventario</div>
    <div style="font-size:1.4rem;font-weight:700;color:#111827"><?= count($alertas) ?></div>
  </div>
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB;min-height:104px;display:flex;flex-direction:column;justify-content:center">
    <div style="font-size:.8rem;color:#6B7280">Pendiente por cobrar</div>
    <div style="font-size:1.4rem;font-weight:700;color:#111827">$<?= number_format($kpis['pendiente'],2) ?></div>
  </div>
</div>

<?php
$moderacionSocial = $moderacionSocial ?? [
  'available' => false,
  'kpis' => [],
  'reportes' => [],
  'bloqueos' => [],
  'usuarios_observados' => [],
];
$modKpis = $moderacionSocial['kpis'] ?? [];
$reportesApp = $moderacionSocial['reportes'] ?? [];
$bloqueosApp = $moderacionSocial['bloqueos'] ?? [];
$usuariosObservados = $moderacionSocial['usuarios_observados'] ?? [];
$fmtModDate = static function (?string $date): string {
  if (!$date) return '';
  $ts = strtotime($date);
  return $ts ? date('d/m H:i', $ts) : $date;
};
$reasonLabel = static function (?string $value): string {
  $value = trim((string)$value);
  if ($value === '') return 'Sin motivo';
  $labels = [
    'user_report' => 'Reporte de usuario',
    'user_request' => 'Solicitud del usuario',
    'harassment' => 'Acoso',
    'spam' => 'Spam',
    'inappropriate' => 'Contenido inapropiado',
  ];
  return $labels[$value] ?? ucfirst(str_replace('_', ' ', $value));
};
$shortText = static function (?string $value, int $max = 130): string {
  $value = trim((string)$value);
  if ($value === '') return '';
  return strlen($value) > $max ? substr($value, 0, $max - 3) . '...' : $value;
};
?>
<div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:20px;margin-bottom:24px">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:16px;flex-wrap:wrap">
    <div>
      <div style="font-weight:800;color:#111827">Moderacion app</div>
      <div style="font-size:.78rem;color:#6B7280;margin-top:3px">Reportes, bloqueos y usuarios que requieren revision</div>
    </div>
    <span style="font-size:.72rem;color:#374151;background:#F3F4F6;border-radius:99px;padding:5px 10px;font-weight:700">App social</span>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px">
    <div style="border:1px solid #FEE2E2;background:#FEF2F2;border-radius:10px;padding:14px">
      <div style="font-size:.72rem;color:#991B1B;font-weight:700">Reportes abiertos</div>
      <div style="font-size:1.45rem;font-weight:800;color:#7F1D1D;margin-top:6px"><?= (int)($modKpis['reportes_abiertos'] ?? 0) ?></div>
    </div>
    <div style="border:1px solid #E5E7EB;background:#F9FAFB;border-radius:10px;padding:14px">
      <div style="font-size:.72rem;color:#374151;font-weight:700">Reportes del mes</div>
      <div style="font-size:1.45rem;font-weight:800;color:#111827;margin-top:6px"><?= (int)($modKpis['reportes_mes'] ?? 0) ?></div>
    </div>
    <div style="border:1px solid #FEF3C7;background:#FFFBEB;border-radius:10px;padding:14px">
      <div style="font-size:.72rem;color:#92400E;font-weight:700">Bloqueos del mes</div>
      <div style="font-size:1.45rem;font-weight:800;color:#78350F;margin-top:6px"><?= (int)($modKpis['bloqueos_mes'] ?? 0) ?></div>
    </div>
    <div style="border:1px solid #DBEAFE;background:#EFF6FF;border-radius:10px;padding:14px">
      <div style="font-size:.72rem;color:#1E40AF;font-weight:700">Usuarios bloqueados</div>
      <div style="font-size:1.45rem;font-weight:800;color:#1E3A8A;margin-top:6px"><?= (int)($modKpis['usuarios_bloqueados'] ?? 0) ?></div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">
    <div style="border:1px solid #EEF2F7;border-radius:10px;padding:14px;min-height:210px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <div style="font-weight:800;color:#111827;font-size:.92rem">Reportes recientes</div>
        <span style="font-size:.68rem;color:#991B1B;background:#FEF2F2;border-radius:99px;padding:3px 8px;font-weight:700">prioridad</span>
      </div>
      <?php if (empty($reportesApp)): ?>
      <p style="color:#9CA3AF;font-size:.84rem;margin:0">Sin reportes recientes desde la app.</p>
      <?php else: ?>
      <?php foreach ($reportesApp as $r): ?>
      <div style="padding:10px 0;border-bottom:1px solid #F3F4F6">
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
          <div style="min-width:0">
            <div style="font-weight:700;color:#111827;font-size:.86rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= htmlspecialchars($r['reported_nombre'] ?? 'Usuario reportado') ?>
            </div>
            <div style="font-size:.74rem;color:#6B7280;margin-top:2px">
              Reporta: <?= htmlspecialchars($r['reporter_nombre'] ?? 'Usuario') ?>
              <?php if (!empty($r['reporter_meta'])): ?> - <?= htmlspecialchars($r['reporter_meta']) ?><?php endif; ?>
            </div>
          </div>
          <span style="font-size:.68rem;color:#374151;background:#F3F4F6;border-radius:99px;padding:2px 7px;white-space:nowrap">
            <?= htmlspecialchars($r['status'] ?? 'open') ?>
          </span>
        </div>
        <div style="font-size:.75rem;color:#374151;margin-top:6px">
          <?= htmlspecialchars($reasonLabel($r['reason'] ?? null)) ?>
          <?php if (!empty($r['created_at'])): ?> - <?= htmlspecialchars($fmtModDate($r['created_at'])) ?><?php endif; ?>
        </div>
        <?php if (!empty($r['details'])): ?>
        <div style="font-size:.74rem;color:#6B7280;margin-top:4px;line-height:1.35">
          <?= htmlspecialchars($shortText($r['details'])) ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div style="border:1px solid #EEF2F7;border-radius:10px;padding:14px;min-height:210px">
      <div style="font-weight:800;color:#111827;font-size:.92rem;margin-bottom:10px">Bloqueos recientes</div>
      <?php if (empty($bloqueosApp)): ?>
      <p style="color:#9CA3AF;font-size:.84rem;margin:0">Sin bloqueos recientes desde la app.</p>
      <?php else: ?>
      <?php foreach ($bloqueosApp as $b): ?>
      <div style="padding:10px 0;border-bottom:1px solid #F3F4F6">
        <div style="font-weight:700;color:#111827;font-size:.86rem">
          <?= htmlspecialchars($b['blocker_nombre'] ?? 'Usuario') ?> bloqueo a <?= htmlspecialchars($b['blocked_nombre'] ?? 'Usuario') ?>
        </div>
        <div style="font-size:.74rem;color:#6B7280;margin-top:2px">
          <?php if (!empty($b['blocker_meta'])): ?><?= htmlspecialchars($b['blocker_meta']) ?> - <?php endif; ?>
          <?= htmlspecialchars($reasonLabel($b['reason'] ?? null)) ?>
          <?php if (!empty($b['created_at'])): ?> - <?= htmlspecialchars($fmtModDate($b['created_at'])) ?><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div style="border:1px solid #EEF2F7;border-radius:10px;padding:14px;min-height:210px">
      <div style="font-weight:800;color:#111827;font-size:.92rem;margin-bottom:10px">Usuarios observados</div>
      <?php if (empty($usuariosObservados)): ?>
      <p style="color:#9CA3AF;font-size:.84rem;margin:0">Sin usuarios con acumulacion de bloqueos.</p>
      <?php else: ?>
      <?php foreach ($usuariosObservados as $u): ?>
      <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid #F3F4F6">
        <div style="min-width:0">
          <div style="font-weight:700;color:#111827;font-size:.86rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <?= htmlspecialchars($u['nombre'] ?? 'Usuario') ?>
          </div>
          <div style="font-size:.74rem;color:#6B7280;margin-top:2px">
            <?= htmlspecialchars($u['meta'] ?? 'Sin mesa activa') ?>
          </div>
        </div>
        <span style="font-size:.72rem;color:#92400E;background:#FEF3C7;border:1px solid #FCD34D;border-radius:99px;padding:3px 8px;font-weight:800;white-space:nowrap">
          <?= (int)($u['total_bloqueos'] ?? 0) ?> bloq.
        </span>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:20px">
  <!-- Próximas reservas -->
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB">
    <div style="font-weight:700;color:#111827;margin-bottom:14px">Próximas reservaciones</div>
    <?php if (empty($proximas)): ?>
    <p style="color:#9CA3AF;font-size:.875rem;margin:0">Sin reservaciones próximas.</p>
    <?php else: ?>
    <?php foreach ($proximas as $r): ?>
    <div style="padding:8px 0;border-bottom:1px solid #F3F4F6;font-size:.85rem">
      <div style="font-weight:500"><?= htmlspecialchars($r['nombre']) ?> — <?= $r['personas'] ?> personas</div>
      <div style="color:#6B7280"><?= date('d/m H:i', strtotime($r['fecha'].' '.$r['hora'])) ?> <?= $r['mesa_nombre'] ? '· '.$r['mesa_nombre'] : '' ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div style="background:#fff;border-radius:12px;padding:20px;border:1px solid #E5E7EB;display:flex;flex-direction:column;gap:16px">
    <div>
      <div style="font-weight:700;color:#111827">Reservas por canal</div>
      <div style="font-size:.76rem;color:#9CA3AF;margin-top:3px">Origen historico de reservaciones</div>
    </div>
    <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
      <div style="width:128px;height:128px;border-radius:50%;background:conic-gradient(#2563EB 0 <?= $reservasMovilPct ?>%, #10B981 <?= $reservasMovilPct ?>% 100%);display:flex;align-items:center;justify-content:center;flex:0 0 auto">
        <div style="width:78px;height:78px;border-radius:50%;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;border:1px solid #EEF2F7">
          <strong style="font-size:1.35rem;color:#111827;line-height:1"><?= $reservasTotal ?></strong>
          <span style="font-size:.68rem;color:#9CA3AF">total</span>
        </div>
      </div>
      <div style="display:grid;gap:10px;min-width:180px;flex:1">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
          <span style="display:flex;align-items:center;gap:8px;color:#374151;font-size:.86rem"><i style="width:10px;height:10px;border-radius:50%;background:#10B981;display:inline-block"></i>Web</span>
          <strong style="color:#111827"><?= $reservasWeb ?> <span style="color:#9CA3AF;font-size:.72rem;font-weight:600"><?= number_format($reservasWebPctLabel, 1) ?>%</span></strong>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
          <span style="display:flex;align-items:center;gap:8px;color:#374151;font-size:.86rem"><i style="width:10px;height:10px;border-radius:50%;background:#2563EB;display:inline-block"></i>Movil</span>
          <strong style="color:#111827"><?= $reservasMovil ?> <span style="color:#9CA3AF;font-size:.72rem;font-weight:600"><?= number_format($reservasMovilPctLabel, 1) ?>%</span></strong>
        </div>
        <?php if ($reservasTotal === 0): ?>
        <div style="font-size:.75rem;color:#9CA3AF">Aun no hay datos para graficar.</div>
        <?php endif; ?>
      </div>
    </div>
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
                     background:<?= $i===0?'#FEF3C7':'#F3F4F6' ?>;
                     color:<?= $i===0?'#92400E':'#6B7280' ?>;
                     font-weight:800;font-size:.72rem"><?= $i+1 ?></span>
        <span style="color:#111827;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['nombre']) ?></span>
      </div>
      <div style="display:flex;align-items:center;gap:10px;white-space:nowrap">
        <span style="color:#10B981;font-weight:700">$<?= number_format((float)$p['precio'],2) ?></span>
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
        <span style="color:#EF4444;font-weight:700">$<?= number_format((float)$p['precio'],2) ?></span>
        <span style="font-size:.72rem;color:#6B7280;background:#F3F4F6;border-radius:99px;padding:2px 8px;font-weight:600">
          <?= (int)$p['unidades_vendidas'] ?> vend.
        </span>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
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
