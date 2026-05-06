<?php
// Vista: Dashboard del Supervisor
$baseUrl = BASE_URL;
?>

<!-- KPIs (4 tarjetas) -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">

  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:20px">
    <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Pendientes aprobar</div>
    <div style="font-size:2rem;font-weight:800;color:<?= count($pendientes) > 0 ? '#D97706' : '#059669' ?>;line-height:1">
      <?= count($pendientes) ?>
    </div>
    <?php if (count($pendientes) > 0): ?>
      <a href="<?= $baseUrl ?>empresa-pedido?estado=pendiente" style="display:inline-block;margin-top:8px;font-size:.78rem;color:var(--color-primary);text-decoration:none;font-weight:600">Revisar →</a>
    <?php else: ?>
      <span style="display:inline-block;margin-top:8px;font-size:.78rem;color:#059669;font-weight:500">Al día ✓</span>
    <?php endif; ?>
  </div>

  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:20px">
    <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">En ruta ahora</div>
    <div style="font-size:2rem;font-weight:800;color:#3B82F6;line-height:1"><?= count($enRuta) ?></div>
    <a href="<?= $baseUrl ?>empresa-pedido?estado=en_ruta" style="display:inline-block;margin-top:8px;font-size:.78rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver pedidos →</a>
  </div>

  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:20px">
    <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Entregados hoy</div>
    <div style="font-size:2rem;font-weight:800;color:#059669;line-height:1"><?= $entregadosHoy ?></div>
    <span style="display:inline-block;margin-top:8px;font-size:.78rem;color:#6B7280">de <?= $pedidosHoy ?> recibidos hoy</span>
  </div>

  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:20px">
    <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Monto este mes</div>
    <div style="font-size:1.6rem;font-weight:800;color:#111827;line-height:1">$<?= number_format($montoMes, 0) ?></div>
    <a href="<?= $baseUrl ?>empresa-pedido" style="display:inline-block;margin-top:8px;font-size:.78rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todos →</a>
  </div>
</div>

<!-- Alertas de stock -->
<?php if (!empty($alertasStock)): ?>
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:16px;margin-bottom:24px">
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#DC2626" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <span style="font-weight:700;color:#991B1B;font-size:.875rem">
      Alertas de stock — <?= count($alertasStock) ?> producto<?= count($alertasStock) !== 1 ? 's' : '' ?> requieren atención
    </span>
    <a href="<?= $baseUrl ?>empresa-inventario" style="margin-left:auto;font-size:.78rem;color:#DC2626;font-weight:600;text-decoration:none">Ir a inventario →</a>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:8px">
    <?php foreach (array_slice($alertasStock, 0, 6) as $alerta): ?>
    <a href="<?= $baseUrl ?>empresa-inventario/ajuste/<?= $alerta['id'] ?>"
       style="background:#fff;border:1px solid #FECACA;border-radius:6px;padding:8px 12px;min-width:140px;text-decoration:none;display:block">
      <div style="font-size:.8rem;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px"><?= htmlspecialchars($alerta['nombre']) ?></div>
      <div style="display:flex;align-items:center;gap:6px;margin-top:4px">
        <?php if ($alerta['estado_stock'] === 'agotado'): ?>
          <span style="font-size:.68rem;font-weight:700;color:#fff;background:#DC2626;padding:1px 6px;border-radius:4px">AGOTADO</span>
        <?php else: ?>
          <span style="font-size:.68rem;font-weight:700;color:#fff;background:#D97706;padding:1px 6px;border-radius:4px">CRÍTICO</span>
        <?php endif; ?>
        <span style="font-size:.75rem;color:#6B7280"><?= number_format($alerta['stock_actual'], 0) ?> restantes</span>
      </div>
    </a>
    <?php endforeach; ?>
    <?php if (count($alertasStock) > 6): ?>
    <a href="<?= $baseUrl ?>empresa-inventario"
       style="background:#fff;border:1px solid #FECACA;border-radius:6px;padding:8px 12px;display:flex;align-items:center;text-decoration:none">
      <span style="font-size:.8rem;color:#DC2626;font-weight:600">+<?= count($alertasStock) - 6 ?> más</span>
    </a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px">

  <!-- Pedidos en ruta -->
  <div>
    <?php if (!empty($enRuta)): ?>
    <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:20px">
      <div style="padding:14px 20px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-weight:700;color:#111827">Pedidos en ruta</span>
          <span style="background:#EFF6FF;color:#1E40AF;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:999px"><?= count($enRuta) ?> activos</span>
        </div>
        <a href="<?= $baseUrl ?>empresa-pedido?estado=en_ruta" style="font-size:.8rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todos →</a>
      </div>
      <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px">
        <?php foreach ($enRuta as $pr): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#EFF6FF;border-radius:8px;border:1px solid #BFDBFE">
          <div>
            <div style="font-size:.875rem;font-weight:700;color:#1E3A8A"><?= htmlspecialchars($pr['folio']) ?></div>
            <div style="font-size:.75rem;color:#3B82F6;margin-top:2px"><?= htmlspecialchars($pr['comprador_nombre'] ?? '') ?></div>
          </div>
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:.875rem;font-weight:600;color:#1E3A8A">$<?= number_format($pr['total'], 2) ?></span>
            <a href="<?= $baseUrl ?>pedido/tracking/<?= $pr['id'] ?>"
               style="padding:5px 12px;background:#3B82F6;color:#fff;border-radius:6px;text-decoration:none;font-size:.75rem;font-weight:600">
              Rastrear
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Accesos rápidos -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <a href="<?= $baseUrl ?>empresa-pedido?estado=pendiente"
         style="background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:20px;text-decoration:none;display:flex;align-items:center;gap:12px;transition:box-shadow .15s"
         onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow='none'">
        <div style="width:40px;height:40px;background:#FEF3C7;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#D97706" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <div style="font-size:.875rem;font-weight:700;color:#111827">Pendientes</div>
          <div style="font-size:1.4rem;font-weight:800;color:#D97706"><?= count($pendientes) ?></div>
        </div>
      </a>
      <a href="<?= $baseUrl ?>empresa-inventario/movimiento/entrada"
         style="background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:20px;text-decoration:none;display:flex;align-items:center;gap:12px;transition:box-shadow .15s"
         onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow='none'">
        <div style="width:40px;height:40px;background:#D1FAE5;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        </div>
        <div>
          <div style="font-size:.875rem;font-weight:700;color:#111827">Registrar entrada</div>
          <div style="font-size:.78rem;color:#059669;font-weight:500">Agregar stock</div>
        </div>
      </a>
    </div>
  </div>

  <!-- Panel lateral: últimos movimientos de stock -->
  <div>
    <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden">
      <div style="padding:14px 20px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between">
        <span style="font-weight:700;color:#111827">Movimientos recientes</span>
        <a href="<?= $baseUrl ?>empresa-inventario/log_movimientos" style="font-size:.78rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todo →</a>
      </div>
      <?php if (empty($ultimosMovimientos)): ?>
        <div style="padding:32px;text-align:center;color:#9CA3AF;font-size:.875rem">
          Sin movimientos registrados.
        </div>
      <?php else: ?>
      <div style="padding:8px 0">
        <?php foreach ($ultimosMovimientos as $mov): ?>
        <div style="padding:10px 16px;display:flex;align-items:flex-start;gap:10px;border-bottom:1px solid #F9FAFB">
          <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                      background:<?= $mov['tipo'] === 'entrada' ? '#D1FAE5' : '#FEE2E2' ?>">
            <?php if ($mov['tipo'] === 'entrada'): ?>
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <?php else: ?>
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#DC2626" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.8rem;font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= htmlspecialchars($mov['producto_nombre']) ?>
            </div>
            <div style="font-size:.72rem;color:#6B7280;margin-top:2px">
              <?= $mov['tipo'] === 'entrada' ? '+' : '-' ?><?= number_format($mov['cantidad'], 0) ?>
              <?php if ($mov['motivo']): ?> · <?= htmlspecialchars(mb_strimwidth($mov['motivo'], 0, 28, '…')) ?><?php endif; ?>
            </div>
            <div style="font-size:.68rem;color:#9CA3AF;margin-top:1px">
              <?= date('d/m H:i', strtotime($mov['created_at'])) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div style="padding:12px 16px;border-top:1px solid #F3F4F6;text-align:center">
        <a href="<?= $baseUrl ?>empresa-inventario"
           style="font-size:.8rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver inventario completo →</a>
      </div>
    </div>
  </div>

</div><!-- /grid -->
