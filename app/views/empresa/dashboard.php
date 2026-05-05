<?php
// Vista: Dashboard de empresa (admin_empresa / supervisor / comprador)
// Variables: $rol, $totalPedidos, $gastomMes, $pedidosRecientes, $pendientesAprobacion
?>

<?php if ($rol === 'comprador'): ?>
<!-- Vista comprador: ir directo al catálogo -->
<div style="background:#fff;border-radius:12px;padding:32px;text-align:center;border:1px solid #E5E7EB">
  <h2 style="font-size:1.25rem;font-weight:800;color:#111827;margin-bottom:8px">Bienvenido, <?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? '') ?>!</h2>
  <p style="color:#6B7280;margin-bottom:24px">¿Qué necesitas hoy?</p>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>catalogo/index" style="display:flex;align-items:center;gap:8px;padding:12px 24px;background:var(--color-primary);color:#fff;border-radius:8px;text-decoration:none;font-weight:600">
      Ver catálogo y hacer pedido
    </a>
    <a href="<?= BASE_URL ?>pedido/index" style="display:flex;align-items:center;gap:8px;padding:12px 24px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600">
      Mis pedidos
    </a>
  </div>
</div>

<?php else: ?>
<!-- Vista admin_empresa y supervisor -->

<!-- KPIs -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px">
  <div style="background:#EFF6FF;border-radius:12px;padding:18px">
    <div style="font-size:.75rem;color:#1E40AF;font-weight:600;margin-bottom:6px">Pedidos totales</div>
    <div style="font-size:1.75rem;font-weight:800;color:#1E40AF"><?= $totalPedidos ?></div>
  </div>
  <div style="background:#F0FDF4;border-radius:12px;padding:18px">
    <div style="font-size:.75rem;color:#166534;font-weight:600;margin-bottom:6px">Gasto este mes</div>
    <div style="font-size:1.75rem;font-weight:800;color:#166534">$<?= number_format($gastomMes,2) ?></div>
  </div>
  <?php if ($pendientesAprobacion > 0): ?>
  <div style="background:#FFF7ED;border-radius:12px;padding:18px">
    <div style="font-size:.75rem;color:#9A3412;font-weight:600;margin-bottom:6px">Pendientes aprobación</div>
    <div style="font-size:1.75rem;font-weight:800;color:#9A3412"><?= $pendientesAprobacion ?></div>
    <a href="<?= BASE_URL ?>pedido/aprobacion" style="font-size:.75rem;color:#9A3412;font-weight:600;text-decoration:underline">Revisar ahora</a>
  </div>
  <?php endif; ?>
</div>

<!-- Acciones rápidas (solo admin_empresa) -->
<?php if ($rol === 'admin_empresa'): ?>
<div style="display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap">
  <a href="<?= BASE_URL ?>empresa-usuario/nuevo" style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
    + Agregar usuario
  </a>
  <a href="<?= BASE_URL ?>empresa-sucursal/nuevo" style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
    + Nueva sucursal
  </a>
</div>
<?php endif; ?>

<!-- Pedidos recientes -->
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <div style="padding:14px 18px;border-bottom:1px solid #F3F4F6;display:flex;justify-content:space-between;align-items:center">
    <h2 style="font-size:.9rem;font-weight:700;color:#111827">Pedidos recientes</h2>
    <a href="<?= BASE_URL ?>pedido/index" style="font-size:.8rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todos</a>
  </div>
  <?php if (empty($pedidosRecientes)): ?>
    <p style="padding:24px;text-align:center;color:#6B7280;font-size:.875rem">No hay pedidos aún.</p>
  <?php else: ?>
  <table style="width:100%;border-collapse:collapse;font-size:.85rem">
    <thead>
      <tr style="background:#F9FAFB">
        <th style="padding:10px 16px;text-align:left;color:#6B7280;font-weight:600">Folio</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Estado</th>
        <th style="padding:10px;text-align:right;color:#6B7280;font-weight:600">Total</th>
        <th style="padding:10px;text-align:left;color:#6B7280;font-weight:600">Fecha</th>
        <th style="padding:10px"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pedidosRecientes as $ped): ?>
      <tr style="border-top:1px solid #F3F4F6">
        <td style="padding:10px 16px;font-weight:600;color:#111827"><?= htmlspecialchars($ped['folio']) ?></td>
        <td style="padding:10px">
          <?php
          $colors = ['pendiente'=>['#FEF3C7','#92400E'],'confirmado'=>['#DBEAFE','#1E40AF'],'en_preparacion'=>['#EDE9FE','#5B21B6'],'en_ruta'=>['#D1FAE5','#065F46'],'entregado'=>['#D1FAE5','#065F46'],'cancelado'=>['#FEE2E2','#991B1B']];
          $c = $colors[$ped['estado']] ?? ['#F3F4F6','#374151'];
          echo "<span style='background:{$c[0]};color:{$c[1]};padding:2px 8px;border-radius:999px;font-size:.75rem;font-weight:600'>" . htmlspecialchars($ped['estado']) . "</span>";
          ?>
        </td>
        <td style="padding:10px;text-align:right;font-weight:600">$<?= number_format($ped['total'],2) ?></td>
        <td style="padding:10px;color:#6B7280;font-size:.8rem"><?= date('d/m/Y', strtotime($ped['created_at'])) ?></td>
        <td style="padding:10px">
          <?php if (in_array($ped['estado'], ['en_ruta','en_preparacion'], true)): ?>
          <a href="<?= BASE_URL ?>pedido/tracking/<?= $ped['id'] ?? '' ?>" style="font-size:.75rem;color:var(--color-primary);font-weight:600;text-decoration:none">Rastrear</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>
