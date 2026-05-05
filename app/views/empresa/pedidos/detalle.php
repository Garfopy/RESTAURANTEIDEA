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
$esComprador = in_array($rol, ['comprador'], true);
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
    <div style="font-size:.8rem;color:#9CA3AF;margin-bottom:2px">Subtotal</div>
    <div style="font-size:1rem;font-weight:700;color:#374151">$<?= number_format($pedido['subtotal'], 2) ?></div>
  </div>
  <?php if (($pedido['costo_envio'] ?? 0) > 0): ?>
  <div style="text-align:center">
    <div style="font-size:.8rem;color:#9CA3AF;margin-bottom:2px">Envío</div>
    <div style="font-size:1rem;font-weight:700;color:#374151">$<?= number_format($pedido['costo_envio'], 2) ?></div>
  </div>
  <?php endif; ?>
  <div style="text-align:center">
    <div style="font-size:.8rem;color:#9CA3AF;margin-bottom:2px">Total</div>
    <div style="font-size:1.3rem;font-weight:800;color:var(--color-primary)">$<?= number_format($pedido['total'], 2) ?></div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if ($esComprador && $pedido['estado'] === 'pendiente'): ?>
    <a href="<?= BASE_URL ?>pedido/cancelar/<?= $pedido['id'] ?>"
       onclick="return confirm('¿Cancelar este pedido?')"
       style="padding:9px 18px;background:#FEE2E2;color:#991B1B;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
      Cancelar
    </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($flash): ?>
<div style="margin-bottom:16px;padding:12px 16px;border-radius:8px;font-size:.875rem;font-weight:500;
  <?= $flash['type'] === 'success' ? 'background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0' : 'background:#FEE2E2;color:#991B1B;border:1px solid #FECACA' ?>">
  <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<!-- Nota de la empresa (rechazo o aprobación) -->
<?php if (!empty($pedido['nota_empresa'])): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;font-size:.875rem;color:#92400E">
  <strong>Mensaje de la empresa:</strong> <?= htmlspecialchars($pedido['nota_empresa']) ?>
</div>
<?php endif; ?>

<!-- Banner "En camino" para el comprador -->
<?php if ($esComprador && $pedido['estado'] === 'en_ruta'): ?>
<div style="margin-bottom:20px;background:linear-gradient(135deg,#FEF3C7,#FDE68A);border:2px solid #F59E0B;border-radius:14px;padding:20px 24px">
  <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <div style="font-size:2.5rem;flex-shrink:0">🚚</div>
    <div style="flex:1">
      <div style="font-size:1.05rem;font-weight:800;color:#92400E">¡Tu pedido está en camino!</div>
      <div style="font-size:.85rem;color:#B45309;margin-top:4px">
        Tu pedido <strong><?= htmlspecialchars($pedido['folio']) ?></strong> fue despachado y está siendo entregado.
      </div>
      <?php if (!empty($pedido['tipo_entrega'])): ?>
      <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:12px">
        <div style="background:rgba(255,255,255,.6);border-radius:8px;padding:8px 14px;font-size:.82rem">
          <div style="color:#92400E;font-weight:700;font-size:.7rem;margin-bottom:2px">TIPO DE ENTREGA</div>
          <div style="color:#78350F;font-weight:600">
            <?= $pedido['tipo_entrega'] === 'pickup' ? '🏭 Recoger en bodega' : '🚚 Envío a domicilio' ?>
          </div>
        </div>
        <?php
        $repartidorNombre = null;
        if (!empty($pedido['repartidor_asignado_id'])) {
            $usuarioModel = new UsuarioModel();
            $rep = $usuarioModel->find((int)$pedido['repartidor_asignado_id']);
            if ($rep) $repartidorNombre = trim(($rep['nombre'] ?? '') . ' ' . ($rep['apellido_paterno'] ?? ''));
        }
        ?>
        <?php if ($repartidorNombre): ?>
        <div style="background:rgba(255,255,255,.6);border-radius:8px;padding:8px 14px;font-size:.82rem">
          <div style="color:#92400E;font-weight:700;font-size:.7rem;margin-bottom:2px">REPARTIDOR</div>
          <div style="color:#78350F;font-weight:600"><?= htmlspecialchars($repartidorNombre) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($pedido['fecha_entrega'])): ?>
        <div style="background:rgba(255,255,255,.6);border-radius:8px;padding:8px 14px;font-size:.82rem">
          <div style="color:#92400E;font-weight:700;font-size:.7rem;margin-bottom:2px">FECHA ESTIMADA</div>
          <div style="color:#78350F;font-weight:600"><?= date('d/m/Y', strtotime($pedido['fecha_entrega'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Comprobante de pago (solo comprador, solo si está confirmado) -->
<?php if ($esComprador && $pedido['estado'] === 'confirmado'): ?>
<div style="margin-bottom:20px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:18px 20px">
  <div style="font-weight:700;color:#1E40AF;font-size:.9rem;margin-bottom:8px">Tu pedido fue aprobado — Envía tu comprobante de pago</div>
  <p style="font-size:.85rem;color:#1D4ED8;margin:0 0 12px 0">
    Tipo de entrega: <strong><?= $pedido['tipo_entrega'] === 'pickup' ? 'Recoger en bodega' : 'Envío a domicilio' ?></strong>
    <?php if (!empty($pedido['repartidor_asignado_id'])): ?>· Repartidor asignado<?php endif; ?>
  </p>
  <?php if (empty($pedido['foto_comprobante_path'])): ?>
  <form method="POST" action="<?= BASE_URL ?>pedido/subirComprobante/<?= $pedido['id'] ?>" enctype="multipart/form-data">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <input type="file" name="comprobante" accept="image/*,.pdf" required
             style="flex:1;padding:8px;border:1px solid #93C5FD;border-radius:8px;font-size:.85rem;background:#fff">
      <button type="submit" style="padding:9px 20px;background:#1D4ED8;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:.875rem;cursor:pointer;white-space:nowrap">
        Enviar comprobante
      </button>
    </div>
  </form>
  <?php else: ?>
  <div style="display:flex;align-items:center;gap:8px;color:#065F46;font-size:.875rem;font-weight:600">
    ✓ Comprobante enviado — la empresa verificará el pago.
    <a href="<?= $pedido['foto_comprobante_path'] ?>" target="_blank" style="color:#1D4ED8;margin-left:8px">Ver comprobante</a>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">
  <!-- Productos del pedido -->
  <div>
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:16px">
      <div style="padding:14px 16px;border-bottom:1px solid #F3F4F6;font-weight:700;font-size:.9rem;color:#111827">
        Productos (<?= count($pedido['items']) ?>)
      </div>
      <?php
      // Detect if any item was price-adjusted
      $hayAjuste = false;
      foreach ($pedido['items'] as $item) {
          if (!empty($item['precio_original']) && abs((float)$item['precio_original'] - (float)$item['precio_unit']) > 0.001) {
              $hayAjuste = true; break;
          }
      }
      ?>
      <?php if ($hayAjuste): ?>
      <div style="padding:10px 16px;background:#EFF6FF;border-bottom:1px solid #BFDBFE;font-size:.78rem;color:#1E40AF">
        ✎ El proveedor ajustó algunos precios al aprobar este pedido.
        <strong>Verde</strong> = precio mejorado respecto al original.
      </div>
      <?php endif; ?>
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
          <?php foreach ($pedido['items'] as $item):
            $precioOriginal = !empty($item['precio_original']) ? (float)$item['precio_original'] : null;
            $precioFinal = (float)$item['precio_unit'];
            $fueAjustado = $precioOriginal !== null && abs($precioOriginal - $precioFinal) > 0.001;
          ?>
          <tr style="border-top:1px solid #F3F4F6<?= $fueAjustado ? ';background:#F0FDF4' : '' ?>">
            <td style="padding:10px 16px;font-weight:600;color:#111827">
              <?= htmlspecialchars($item['producto_nombre']) ?>
              <div style="font-size:.75rem;color:#9CA3AF;font-weight:400"><?= $item['presentacion'] ?></div>
            </td>
            <td style="padding:10px;text-align:center;color:#374151"><?= number_format($item['cantidad'], 2) ?></td>
            <td style="padding:10px;text-align:right">
              <?php if ($fueAjustado): ?>
              <div style="font-size:.75rem;color:#9CA3AF;text-decoration:line-through">$<?= number_format($precioOriginal, 2) ?></div>
              <div style="font-weight:700;color:#059669">$<?= number_format($precioFinal, 2) ?></div>
              <div style="font-size:.68rem;color:#059669;font-weight:600">
                −$<?= number_format($precioOriginal - $precioFinal, 2) ?> (<?= number_format(100 * ($precioOriginal - $precioFinal) / $precioOriginal, 1) ?>% dto.)
              </div>
              <?php else: ?>
              <span style="color:#374151">$<?= number_format($precioFinal, 2) ?></span>
              <?php endif; ?>
            </td>
            <td style="padding:10px 16px;text-align:right;font-weight:700;color:#111827">$<?= number_format($item['subtotal'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="border-top:1px solid #E5E7EB;background:#F9FAFB">
            <td colspan="3" style="padding:10px 16px;text-align:right;color:#6B7280;font-size:.85rem">Subtotal productos</td>
            <td style="padding:10px 16px;text-align:right;font-weight:700;color:#374151">$<?= number_format($pedido['subtotal'], 2) ?></td>
          </tr>
          <?php if (($pedido['costo_envio'] ?? 0) > 0): ?>
          <tr style="background:#F9FAFB">
            <td colspan="3" style="padding:6px 16px;text-align:right;color:#6B7280;font-size:.85rem">Costo de envío</td>
            <td style="padding:6px 16px;text-align:right;font-weight:700;color:#374151">$<?= number_format($pedido['costo_envio'], 2) ?></td>
          </tr>
          <?php endif; ?>
          <tr style="border-top:2px solid #E5E7EB;background:#F9FAFB">
            <td colspan="3" style="padding:12px 16px;text-align:right;font-weight:700;color:#374151">TOTAL</td>
            <td style="padding:12px 16px;text-align:right;font-weight:800;color:var(--color-primary);font-size:1.05rem">
              $<?= number_format($pedido['total'], 2) ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Foto de entrega -->
    <?php if (!empty($pedido['foto_entrega_path'])): ?>
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:16px;margin-bottom:16px">
      <div style="font-weight:700;font-size:.9rem;color:#065F46;margin-bottom:10px">✓ Evidencia de entrega</div>
      <img src="<?= htmlspecialchars($pedido['foto_entrega_path']) ?>" alt="Evidencia de entrega"
           style="max-width:100%;border-radius:8px;border:1px solid #E5E7EB">
    </div>
    <?php endif; ?>
  </div>

  <!-- Panel lateral -->
  <div style="display:flex;flex-direction:column;gap:14px">
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:16px">
      <div style="font-weight:700;font-size:.85rem;color:#111827;margin-bottom:12px">Información</div>
      <?php
      $rows = [
        'Comprador'     => htmlspecialchars(($pedido['comprador_nombre']??'') . ' ' . ($pedido['comprador_apellido']??'')),
        'Fecha pedido'  => date('d/m/Y H:i', strtotime($pedido['created_at'])),
        'Fecha entrega' => $pedido['fecha_entrega'] ? date('d/m/Y', strtotime($pedido['fecha_entrega'])) : '—',
      ];
      if (!empty($pedido['tipo_entrega'])) {
          $rows['Tipo entrega'] = $pedido['tipo_entrega'] === 'pickup' ? 'Recoger en bodega' : 'Envío por repartidor';
      }
      if ($pedido['aprobador_nombre']) {
          $rows['Aprobado por'] = htmlspecialchars($pedido['aprobador_nombre']);
      }
      foreach ($rows as $k => $v): ?>
      <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #F9FAFB;font-size:.85rem">
        <span style="color:#6B7280"><?= $k ?></span>
        <span style="font-weight:600;color:#374151"><?= $v ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pedido['notas']): ?>
    <div style="background:#FFFBEB;border:1px solid #FCD34D;border-radius:12px;padding:14px">
      <div style="font-weight:600;font-size:.8rem;color:#92400E;margin-bottom:6px">Notas del pedido</div>
      <p style="font-size:.85rem;color:#78350F;margin:0;white-space:pre-line"><?= htmlspecialchars($pedido['notas']) ?></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($pedido['foto_comprobante_path']) && !$esComprador): ?>
    <div style="background:#F0FDF4;border:1px solid #A7F3D0;border-radius:12px;padding:14px">
      <div style="font-weight:700;font-size:.8rem;color:#065F46;margin-bottom:8px">Comprobante de pago</div>
      <a href="<?= htmlspecialchars($pedido['foto_comprobante_path']) ?>" target="_blank"
         style="font-size:.85rem;color:#059669;font-weight:600">Ver comprobante →</a>
    </div>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>pedido/index" style="display:block;text-align:center;padding:10px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
      ← Volver a pedidos
    </a>
  </div>
</div>
