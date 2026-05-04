<?php
// Vista: Detalle de un pedido
$estadoConfig = [
    'pendiente'      => ['label'=>'Pendiente',      'bg'=>'#FEF3C7','color'=>'#92400E'],
    'confirmado'     => ['label'=>'Confirmado',      'bg'=>'#DBEAFE','color'=>'#1E40AF'],
    'en_preparacion' => ['label'=>'En preparación',  'bg'=>'#EDE9FE','color'=>'#5B21B6'],
    'en_ruta'        => ['label'=>'En ruta',          'bg'=>'#FEF3C7','color'=>'#B45309'],
    'entregado'      => ['label'=>'Entregado',        'bg'=>'#D1FAE5','color'=>'#065F46'],
    'cancelado'      => ['label'=>'Cancelado',        'bg'=>'#FEE2E2','color'=>'#991B1B'],
];
$est = $estadoConfig[$pedido['estado']] ?? ['label'=>$pedido['estado'],'bg'=>'#F3F4F6','color'=>'#374151'];
$rol = $_SESSION['usuario']['rol_slug'] ?? '';
$esComprador = in_array($rol, ['admin_empresa','comprador'], true);
?>
<!-- Barra de estado -->
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:16px 20px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:.8rem;color:#9CA3AF;margin-bottom:2px">Folio</div>
    <div style="font-size:1.3rem;font-weight:800;color:#111827;font-family:monospace"><?= htmlspecialchars($pedido['folio']) ?></div>
  </div>
  <div style="text-align:center">
    <div style="font-size:.8rem;color:#9CA3AF;margin-bottom:2px">Estado</div>
    <span style="background:<?= $est['bg'] ?>;color:<?= $est['color'] ?>;padding:5px 16px;border-radius:999px;font-size:.875rem;font-weight:700">
      <?= $est['label'] ?>
    </span>
  </div>
  <div style="text-align:center">
    <div style="font-size:.8rem;color:#9CA3AF;margin-bottom:2px">Total</div>
    <div style="font-size:1.3rem;font-weight:800;color:var(--color-primary)">$<?= number_format($pedido['total'], 2) ?></div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if (in_array($pedido['estado'], ['en_ruta','en_preparacion'], true)): ?>
    <a href="<?= BASE_URL ?>pedido/tracking/<?= $pedido['id'] ?>"
       style="padding:9px 18px;background:var(--color-primary);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
      Rastrear entrega
    </a>
    <?php endif; ?>
    <?php if ($esComprador && $pedido['estado'] === 'pendiente' && !$pedido['requiere_aprobacion']): ?>
    <a href="<?= BASE_URL ?>pedido/cancelar/<?= $pedido['id'] ?>"
       onclick="return confirm('¿Cancelar este pedido?')"
       style="padding:9px 18px;background:#FEE2E2;color:#991B1B;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
      Cancelar
    </a>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">
  <!-- Productos del pedido -->
  <div>
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:16px">
      <div style="padding:14px 16px;border-bottom:1px solid #F3F4F6;font-weight:700;font-size:.9rem;color:#111827">
        Productos (<?= count($pedido['items']) ?>)
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:.875rem">
        <thead>
          <tr style="background:#F9FAFB">
            <th style="padding:10px 16px;text-align:left;color:#6B7280;font-weight:600">Producto</th>
            <th style="padding:10px;text-align:center;color:#6B7280;font-weight:600">Cantidad</th>
            <th style="padding:10px;text-align:right;color:#6B7280;font-weight:600">Precio unit.</th>
            <th style="padding:10px 16px;text-align:right;color:#6B7280;font-weight:600">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pedido['items'] as $item): ?>
          <tr style="border-top:1px solid #F3F4F6">
            <td style="padding:10px 16px;font-weight:600;color:#111827">
              <?= htmlspecialchars($item['producto_nombre']) ?>
              <div style="font-size:.75rem;color:#9CA3AF;font-weight:400"><?= $item['presentacion'] ?></div>
            </td>
            <td style="padding:10px;text-align:center;color:#374151"><?= number_format($item['cantidad'], 2) ?></td>
            <td style="padding:10px;text-align:right;color:#374151">$<?= number_format($item['precio_unit'], 2) ?></td>
            <td style="padding:10px 16px;text-align:right;font-weight:700;color:#111827">$<?= number_format($item['subtotal'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="border-top:2px solid #E5E7EB;background:#F9FAFB">
            <td colspan="3" style="padding:12px 16px;text-align:right;font-weight:700;color:#374151">TOTAL</td>
            <td style="padding:12px 16px;text-align:right;font-weight:800;color:var(--color-primary);font-size:1.05rem">
              $<?= number_format($pedido['total'], 2) ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Sucursales de entrega -->
    <?php if (!empty($pedido['sucursales'])): ?>
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:14px 16px">
      <div style="font-weight:700;font-size:.9rem;color:#111827;margin-bottom:10px">Sucursales de entrega</div>
      <?php foreach ($pedido['sucursales'] as $suc): ?>
      <?php
      $sucEst = ['pendiente'=>'Pendiente','entregado'=>'Entregado','parcial'=>'Parcial','rechazado'=>'Rechazado'][$suc['estado']] ?? $suc['estado'];
      $sucBg  = ['entregado'=>'#D1FAE5','rechazado'=>'#FEE2E2'][$suc['estado']] ?? '#F9FAFB';
      ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #F3F4F6">
        <div>
          <div style="font-weight:600;color:#111827;font-size:.875rem"><?= htmlspecialchars($suc['sucursal_nombre']) ?></div>
          <div style="font-size:.75rem;color:#6B7280"><?= htmlspecialchars($suc['direccion'] ?? '') ?></div>
        </div>
        <span style="background:<?= $sucBg ?>;padding:3px 10px;border-radius:999px;font-size:.75rem;font-weight:600;color:#374151"><?= $sucEst ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Panel lateral -->
  <div style="display:flex;flex-direction:column;gap:14px">
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:16px">
      <div style="font-weight:700;font-size:.85rem;color:#111827;margin-bottom:12px">Información</div>
      <?php $rows = [
        'Comprador'      => htmlspecialchars(($pedido['comprador_nombre']??'') . ' ' . ($pedido['comprador_apellido']??'')),
        'Fecha pedido'   => date('d/m/Y H:i', strtotime($pedido['created_at'])),
        'Fecha entrega'  => $pedido['fecha_entrega'] ? date('d/m/Y', strtotime($pedido['fecha_entrega'])) : '—',
        'Método de pago' => ucfirst($pedido['metodo_pago'] ?? '—'),
      ];
      if ($pedido['aprobador_nombre']): $rows['Aprobado por'] = htmlspecialchars($pedido['aprobador_nombre']); endif;
      foreach ($rows as $k => $v): ?>
      <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #F9FAFB;font-size:.85rem">
        <span style="color:#6B7280"><?= $k ?></span>
        <span style="font-weight:600;color:#374151"><?= $v ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pedido['notas']): ?>
    <div style="background:#FFFBEB;border:1px solid #FCD34D;border-radius:12px;padding:14px">
      <div style="font-weight:600;font-size:.8rem;color:#92400E;margin-bottom:6px">Notas</div>
      <p style="font-size:.85rem;color:#78350F;margin:0;white-space:pre-line"><?= htmlspecialchars($pedido['notas']) ?></p>
    </div>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>pedido/index" style="display:block;text-align:center;padding:10px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
      ← Volver a pedidos
    </a>
  </div>
</div>
