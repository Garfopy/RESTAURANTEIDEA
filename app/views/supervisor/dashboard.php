<?php
// Vista: Dashboard del Supervisor
$baseUrl = BASE_URL;
?>

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:20px">
    <div style="font-size:.75rem;color:#6B7280;font-weight:600;text-transform:uppercase;margin-bottom:6px">Pendientes de aprobar</div>
    <div style="font-size:2rem;font-weight:800;color:<?= count($pendientes) > 0 ? '#D97706' : '#059669' ?>">
      <?= count($pendientes) ?>
    </div>
    <?php if (count($pendientes) > 0): ?>
      <a href="<?= $baseUrl ?>pedido/aprobacion" style="font-size:.8rem;color:var(--color-primary);text-decoration:none;font-weight:600">Revisar →</a>
    <?php else: ?>
      <span style="font-size:.8rem;color:#059669">Todo al día ✓</span>
    <?php endif; ?>
  </div>
  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:20px">
    <div style="font-size:.75rem;color:#6B7280;font-weight:600;text-transform:uppercase;margin-bottom:6px">En ruta ahora</div>
    <div style="font-size:2rem;font-weight:800;color:#3B82F6"><?= count($enRuta) ?></div>
    <a href="<?= $baseUrl ?>pedido/index" style="font-size:.8rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver pedidos →</a>
  </div>
  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:20px">
    <div style="font-size:.75rem;color:#6B7280;font-weight:600;text-transform:uppercase;margin-bottom:6px">Entregados hoy</div>
    <div style="font-size:2rem;font-weight:800;color:#059669"><?= (int)$entregadosHoy ?></div>
    <span style="font-size:.8rem;color:#6B7280">pedidos completados</span>
  </div>
</div>

<!-- Cola de aprobación -->
<div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:20px">
  <div style="padding:14px 16px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between">
    <span style="font-weight:700;color:#111827">Cola de aprobación</span>
    <a href="<?= $baseUrl ?>pedido/aprobacion" style="font-size:.8rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todos →</a>
  </div>
  <?php if (empty($pendientes)): ?>
    <div style="padding:32px;text-align:center;color:#059669">
      <div style="font-size:2rem;margin-bottom:8px">✓</div>
      <div style="font-weight:600">No hay pedidos pendientes de aprobación</div>
      <div style="font-size:.875rem;color:#6B7280;margin-top:4px">Todos los pedidos han sido revisados.</div>
    </div>
  <?php else: ?>
  <table style="width:100%;border-collapse:collapse">
    <thead>
      <tr style="background:#FEF3C7;border-bottom:1px solid #E5E7EB">
        <th style="padding:10px 16px;text-align:left;font-size:.75rem;color:#92400E;font-weight:600;text-transform:uppercase">Folio</th>
        <th style="padding:10px 16px;text-align:left;font-size:.75rem;color:#92400E;font-weight:600;text-transform:uppercase">Comprador</th>
        <th style="padding:10px 16px;text-align:right;font-size:.75rem;color:#92400E;font-weight:600;text-transform:uppercase">Total</th>
        <th style="padding:10px 16px;text-align:left;font-size:.75rem;color:#92400E;font-weight:600;text-transform:uppercase">Fecha</th>
        <th style="padding:10px 16px;text-align:center;font-size:.75rem;color:#92400E;font-weight:600;text-transform:uppercase">Acción</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (array_slice($pendientes, 0, 8) as $ped): ?>
      <tr style="border-bottom:1px solid #F3F4F6">
        <td style="padding:10px 16px;font-size:.875rem;font-weight:600;color:#111827"><?= htmlspecialchars($ped['folio']) ?></td>
        <td style="padding:10px 16px;font-size:.875rem;color:#374151"><?= htmlspecialchars($ped['comprador_nombre'] ?? '') ?></td>
        <td style="padding:10px 16px;text-align:right;font-size:.875rem;font-weight:600;color:#111827">$<?= number_format($ped['total'], 2) ?></td>
        <td style="padding:10px 16px;font-size:.8rem;color:#6B7280"><?= date('d/m/Y', strtotime($ped['created_at'])) ?></td>
        <td style="padding:10px 16px;text-align:center">
          <a href="<?= $baseUrl ?>pedido/detalle/<?= $ped['id'] ?>"
             style="padding:5px 12px;border-radius:6px;background:var(--color-primary);color:#fff;text-decoration:none;font-size:.78rem;font-weight:600">
            Revisar
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (count($pendientes) > 8): ?>
    <div style="padding:12px 16px;border-top:1px solid #E5E7EB;text-align:center">
      <a href="<?= $baseUrl ?>pedido/aprobacion" style="font-size:.875rem;color:var(--color-primary);text-decoration:none;font-weight:600">
        Ver <?= count($pendientes) - 8 ?> más pendientes →
      </a>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Pedidos en ruta -->
<?php if (!empty($enRuta)): ?>
<div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden">
  <div style="padding:14px 16px;border-bottom:1px solid #E5E7EB">
    <span style="font-weight:700;color:#111827">Pedidos en ruta ahora</span>
  </div>
  <div style="padding:16px;display:flex;flex-direction:column;gap:8px">
    <?php foreach ($enRuta as $pr): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;background:#EFF6FF;border-radius:8px;border:1px solid #BFDBFE">
      <div>
        <div style="font-size:.875rem;font-weight:600;color:#1E3A8A"><?= htmlspecialchars($pr['folio']) ?></div>
        <div style="font-size:.75rem;color:#3B82F6"><?= htmlspecialchars($pr['comprador_nombre'] ?? '') ?></div>
      </div>
      <a href="<?= $baseUrl ?>pedido/tracking/<?= $pr['id'] ?>"
         style="padding:6px 14px;background:#3B82F6;color:#fff;border-radius:6px;text-decoration:none;font-size:.78rem;font-weight:600">
        Ver mapa
      </a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
