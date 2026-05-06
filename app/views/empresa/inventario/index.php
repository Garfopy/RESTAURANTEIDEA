<?php
$baseUrl = BASE_URL;
$rol     = $_SESSION['usuario']['rol_slug'] ?? '';
?>

<!-- Flash -->
<?php if ($flash): ?>
<div style="margin-bottom:16px;padding:12px 16px;border-radius:8px;font-size:.875rem;font-weight:500;
  <?= $flash['type'] === 'success' ? 'background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0' : 'background:#FEE2E2;color:#991B1B;border:1px solid #FECACA' ?>">
  <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<!-- Acciones rápidas -->
<div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap">
  <a href="<?= $baseUrl ?>empresa-inventario/movimiento/entrada"
     style="display:flex;align-items:center;gap:8px;padding:12px 20px;background:#059669;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:.9rem">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Registrar Entrada
  </a>
  <a href="<?= $baseUrl ?>empresa-inventario/movimiento/salida"
     style="display:flex;align-items:center;gap:8px;padding:12px 20px;background:#DC2626;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:.9rem">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
    Registrar Salida
  </a>
  <a href="<?= $baseUrl ?>empresa-inventario/movimiento/merma"
     style="display:flex;align-items:center;gap:8px;padding:12px 20px;background:#F59E0B;color:#fff;border-radius:10px;text-decoration:none;font-weight:700;font-size:.9rem">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    Registrar Merma
  </a>
  <a href="<?= $baseUrl ?>empresa-inventario/log_movimientos"
     style="display:flex;align-items:center;gap:8px;padding:12px 20px;background:#fff;color:#374151;border:1px solid #D1D5DB;border-radius:10px;text-decoration:none;font-weight:600;font-size:.9rem">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
    Ver Log Completo
  </a>
</div>

<!-- Cards de resumen -->
<?php
$nAgotado = count(array_filter($resumen, fn($r) => $r['estado_stock'] === 'agotado'));
$nCritico = count(array_filter($resumen, fn($r) => $r['estado_stock'] === 'critico'));
$nBajo    = count(array_filter($resumen, fn($r) => $r['estado_stock'] === 'bajo'));
$nOk      = count(array_filter($resumen, fn($r) => $r['estado_stock'] === 'ok'));
?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:28px">
  <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:16px;text-align:center">
    <div style="font-size:2rem;font-weight:800;color:#DC2626"><?= $nAgotado ?></div>
    <div style="font-size:.8rem;color:#991B1B;font-weight:600;margin-top:2px">Sin Stock</div>
  </div>
  <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:16px;text-align:center">
    <div style="font-size:2rem;font-weight:800;color:#D97706"><?= $nCritico ?></div>
    <div style="font-size:.8rem;color:#92400E;font-weight:600;margin-top:2px">Crítico</div>
  </div>
  <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:16px;text-align:center">
    <div style="font-size:2rem;font-weight:800;color:#EA580C"><?= $nBajo ?></div>
    <div style="font-size:.8rem;color:#7C2D12;font-weight:600;margin-top:2px">Stock Bajo</div>
  </div>
  <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:16px;text-align:center">
    <div style="font-size:2rem;font-weight:800;color:#16A34A"><?= $nOk ?></div>
    <div style="font-size:.8rem;color:#14532D;font-weight:600;margin-top:2px">OK</div>
  </div>
</div>

<!-- Grid de productos con estado -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

  <!-- Productos con alerta -->
  <?php if ($nAgotado + $nCritico + $nBajo > 0): ?>
  <div style="grid-column:1/-1">
    <h3 style="font-size:.875rem;font-weight:700;color:#DC2626;margin-bottom:12px;display:flex;align-items:center;gap:6px">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 100 20A10 10 0 0012 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
      Productos que necesitan atención
    </h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px">
      <?php foreach (array_merge(array_values($criticos), array_values($bajos)) as $p): ?>
      <?php
        $isAgotado = $p['estado_stock'] === 'agotado';
        $isCritico = $p['estado_stock'] === 'critico';
        $bgCard = $isAgotado ? '#FEF2F2' : ($isCritico ? '#FFFBEB' : '#FFF7ED');
        $borderCard = $isAgotado ? '#FECACA' : ($isCritico ? '#FDE68A' : '#FED7AA');
        $stockColor = $isAgotado ? '#DC2626' : ($isCritico ? '#D97706' : '#EA580C');
        $badgeBg = $isAgotado ? '#FEE2E2' : ($isCritico ? '#FEF3C7' : '#FFEDD5');
        $badgeTx = $isAgotado ? '#991B1B' : ($isCritico ? '#92400E' : '#7C2D12');
        $badgeLabel = $isAgotado ? 'Agotado' : ($isCritico ? 'Crítico' : 'Bajo');
      ?>
      <div style="background:<?= $bgCard ?>;border:1px solid <?= $borderCard ?>;border-radius:10px;padding:14px;display:flex;gap:12px;align-items:center">
        <?php if (!empty($p['imagen'])): ?>
          <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:8px;flex-shrink:0">
        <?php else: ?>
          <div style="width:44px;height:44px;background:#E5E7EB;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#9CA3AF;font-size:1.2rem">🥩</div>
        <?php endif; ?>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;color:#111827;font-size:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($p['nombre']) ?></div>
          <div style="font-size:.75rem;color:#6B7280;margin-bottom:4px"><?= htmlspecialchars($p['categoria_nombre']) ?></div>
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:1.1rem;font-weight:800;color:<?= $stockColor ?>"><?= number_format((float)$p['stock_actual'], 1) ?> <small style="font-size:.7rem;font-weight:500"><?= $p['presentacion'] ?></small></span>
            <span style="font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:999px;background:<?= $badgeBg ?>;color:<?= $badgeTx ?>"><?= $badgeLabel ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php foreach ($criticos as $p): if ($p['estado_stock'] !== 'agotado') continue; ?>
      <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:14px;display:flex;gap:12px;align-items:center">
        <?php if (!empty($p['imagen'])): ?>
          <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:8px;flex-shrink:0">
        <?php else: ?>
          <div style="width:44px;height:44px;background:#E5E7EB;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#9CA3AF;font-size:1.2rem">🥩</div>
        <?php endif; ?>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;color:#111827;font-size:.85rem"><?= htmlspecialchars($p['nombre']) ?></div>
          <div style="font-size:.75rem;color:#6B7280;margin-bottom:4px"><?= htmlspecialchars($p['categoria_nombre']) ?></div>
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:1.1rem;font-weight:800;color:#DC2626"><?= number_format((float)$p['stock_actual'], 1) ?> <small style="font-size:.7rem;font-weight:500"><?= $p['presentacion'] ?></small></span>
            <span style="font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:999px;background:#FEE2E2;color:#991B1B">Agotado</span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Tabla completa de stock -->
  <div style="grid-column:1/-1">
    <h3 style="font-size:.875rem;font-weight:700;color:#374151;margin-bottom:12px">Todos los productos</h3>
    <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden">
      <?php if (empty($resumen)): ?>
        <div style="padding:48px;text-align:center;color:#9CA3AF">Sin productos registrados.</div>
      <?php else: ?>
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
            <th style="padding:10px 16px;text-align:left;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Producto</th>
            <th style="padding:10px 16px;text-align:center;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Estado</th>
            <th style="padding:10px 16px;text-align:right;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Stock</th>
            <th style="padding:10px 16px;text-align:right;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Mínimo</th>
            <th style="padding:10px 16px;text-align:center;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($resumen as $p): ?>
          <?php
            $s = (float)$p['stock_actual'];
            $u = (float)$p['umbral_minimo'];
            [$badgeBg, $badgeTx, $badgeLabel, $dot] = match ($p['estado_stock']) {
              'agotado' => ['#FEE2E2','#991B1B','Agotado','#DC2626'],
              'critico' => ['#FEF3C7','#92400E','Crítico','#D97706'],
              'bajo'    => ['#FFEDD5','#7C2D12','Bajo','#EA580C'],
              default   => ['#D1FAE5','#065F46','OK','#059669'],
            };
          ?>
          <tr style="border-bottom:1px solid #F3F4F6">
            <td style="padding:10px 16px">
              <div style="display:flex;align-items:center;gap:8px">
                <?php if (!empty($p['imagen'])): ?>
                  <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:6px;flex-shrink:0">
                <?php else: ?>
                  <div style="width:32px;height:32px;background:#F3F4F6;border-radius:6px;flex-shrink:0"></div>
                <?php endif; ?>
                <div>
                  <div style="font-weight:600;font-size:.85rem;color:#111827"><?= htmlspecialchars($p['nombre']) ?></div>
                  <div style="font-size:.7rem;color:#9CA3AF"><?= htmlspecialchars($p['categoria_nombre']) ?></div>
                </div>
              </div>
            </td>
            <td style="padding:10px 16px;text-align:center">
              <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;background:<?= $badgeBg ?>;color:<?= $badgeTx ?>;font-size:.7rem;font-weight:700">
                <span style="width:6px;height:6px;border-radius:50%;background:<?= $dot ?>"></span>
                <?= $badgeLabel ?>
              </span>
            </td>
            <td style="padding:10px 16px;text-align:right;font-size:.9rem;font-weight:700;color:#111827">
              <?= number_format($s, 1) ?> <span style="font-size:.7rem;color:#9CA3AF;font-weight:400"><?= $p['presentacion'] ?></span>
            </td>
            <td style="padding:10px 16px;text-align:right;font-size:.8rem;color:#6B7280">
              <?= number_format($u, 1) ?>
            </td>
            <td style="padding:10px 16px;text-align:center">
              <div style="display:flex;justify-content:center;gap:6px">
                <a href="<?= $baseUrl ?>empresa-inventario/historial/<?= $p['id'] ?>"
                   title="Ver historial" style="padding:5px 10px;border:1px solid #D1D5DB;border-radius:6px;color:#374151;text-decoration:none;font-size:.75rem">
                  Historial
                </a>
                <?php if ($rol === 'admin_empresa'): ?>
                <a href="<?= $baseUrl ?>empresa-inventario/ajuste/<?= $p['id'] ?>"
                   title="Ajuste directo" style="padding:5px 10px;border:1px solid #D1D5DB;border-radius:6px;color:#374151;text-decoration:none;font-size:.75rem">
                  Ajuste
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Últimos movimientos -->
  <?php if (!empty($ultimos)): ?>
  <div style="grid-column:1/-1">
    <h3 style="font-size:.875rem;font-weight:700;color:#374151;margin-bottom:12px">Últimos movimientos</h3>
    <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
            <th style="padding:8px 16px;text-align:left;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Tipo</th>
            <th style="padding:8px 16px;text-align:left;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Producto</th>
            <th style="padding:8px 16px;text-align:right;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Cantidad</th>
            <th style="padding:8px 16px;text-align:left;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Motivo</th>
            <th style="padding:8px 16px;text-align:left;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Usuario</th>
            <th style="padding:8px 16px;text-align:left;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Fecha</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ultimos as $m): ?>
          <?php
            [$tipoBg, $tipoTx, $tipoLabel] = match ($m['tipo']) {
              'entrada' => ['#D1FAE5','#065F46','Entrada'],
              'salida'  => ['#FEE2E2','#991B1B','Salida'],
              'merma'   => ['#FEF3C7','#92400E','Merma'],
              default   => ['#E0E7FF','#3730A3','Ajuste'],
            };
          ?>
          <tr style="border-bottom:1px solid #F3F4F6">
            <td style="padding:8px 16px">
              <span style="padding:2px 8px;border-radius:999px;background:<?= $tipoBg ?>;color:<?= $tipoTx ?>;font-size:.7rem;font-weight:700"><?= $tipoLabel ?></span>
            </td>
            <td style="padding:8px 16px;font-size:.8rem;color:#111827"><?= htmlspecialchars($m['producto_nombre']) ?></td>
            <td style="padding:8px 16px;text-align:right;font-size:.85rem;font-weight:700;color:#111827"><?= number_format((float)$m['cantidad'], 1) ?> <span style="font-size:.7rem;font-weight:400;color:#9CA3AF"><?= $m['presentacion'] ?></span></td>
            <td style="padding:8px 16px;font-size:.75rem;color:#6B7280"><?= htmlspecialchars($m['motivo'] ?? '—') ?></td>
            <td style="padding:8px 16px;font-size:.75rem;color:#6B7280"><?= htmlspecialchars($m['usuario_nombre']) ?></td>
            <td style="padding:8px 16px;font-size:.75rem;color:#9CA3AF"><?= date('d/m H:i', strtotime($m['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div style="padding:10px 16px;text-align:right;border-top:1px solid #F3F4F6">
        <a href="<?= $baseUrl ?>empresa-inventario/log_movimientos" style="font-size:.8rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todos los movimientos →</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>
