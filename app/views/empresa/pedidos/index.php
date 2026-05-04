<?php
// Vista: Historial de pedidos de la empresa
$estados = [
    'pendiente'       => ['label'=>'Pendiente',       'bg'=>'#FEF3C7','color'=>'#92400E'],
    'confirmado'      => ['label'=>'Confirmado',       'bg'=>'#DBEAFE','color'=>'#1E40AF'],
    'en_preparacion'  => ['label'=>'En preparación',   'bg'=>'#EDE9FE','color'=>'#5B21B6'],
    'en_ruta'         => ['label'=>'En ruta',           'bg'=>'#FEF3C7','color'=>'#B45309'],
    'entregado'       => ['label'=>'Entregado',         'bg'=>'#D1FAE5','color'=>'#065F46'],
    'cancelado'       => ['label'=>'Cancelado',         'bg'=>'#FEE2E2','color'=>'#991B1B'],
];
$rol = $_SESSION['usuario']['rol_slug'] ?? '';
$puedeComprar = in_array($rol, ['admin_empresa','comprador'], true);
?>
<!-- Filtros -->
<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:flex-end">
  <input type="text" name="buscar" placeholder="Folio o comprador..." value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>"
         style="flex:1;min-width:160px;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
  <select name="estado" style="padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
    <option value="">Todos los estados</option>
    <?php foreach ($estados as $k => $v): ?>
    <option value="<?= $k ?>" <?= ($filtros['estado'] ?? '') === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" style="padding:8px 16px;background:#374151;color:#fff;border:none;border-radius:8px;font-size:.875rem;cursor:pointer">Filtrar</button>
  <?php if ($puedeComprar): ?>
  <a href="<?= BASE_URL ?>carrito/index"
     style="padding:9px 18px;background:var(--color-primary);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem;margin-left:auto">
    + Nuevo pedido
  </a>
  <?php endif; ?>
</form>

<?php if (empty($pedidos)): ?>
<div style="background:#fff;border-radius:12px;padding:40px;text-align:center;border:1px solid #E5E7EB;color:#6B7280">
  No hay pedidos registrados.
  <?php if ($puedeComprar): ?>
  <a href="<?= BASE_URL ?>carrito/index" style="color:var(--color-primary);font-weight:600"> Hacer el primer pedido</a>
  <?php endif; ?>
</div>
<?php else: ?>
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <table style="width:100%;border-collapse:collapse;font-size:.875rem">
    <thead>
      <tr style="background:#F9FAFB">
        <th style="padding:12px 16px;text-align:left;color:#6B7280;font-weight:600">Folio</th>
        <th style="padding:12px;text-align:left;color:#6B7280;font-weight:600">Comprador</th>
        <th style="padding:12px;text-align:left;color:#6B7280;font-weight:600">Fecha entrega</th>
        <th style="padding:12px;text-align:right;color:#6B7280;font-weight:600">Total</th>
        <th style="padding:12px;text-align:center;color:#6B7280;font-weight:600">Estado</th>
        <th style="padding:12px 16px;text-align:left;color:#6B7280;font-weight:600">Fecha</th>
        <th style="padding:12px;text-align:center;color:#6B7280;font-weight:600">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pedidos as $p): ?>
      <?php $est = $estados[$p['estado']] ?? ['label'=>$p['estado'],'bg'=>'#F3F4F6','color'=>'#374151']; ?>
      <tr style="border-top:1px solid #F3F4F6">
        <td style="padding:12px 16px">
          <a href="<?= BASE_URL ?>pedido/detalle/<?= $p['id'] ?>" style="font-weight:700;color:var(--color-primary);text-decoration:none;font-family:monospace">
            <?= htmlspecialchars($p['folio']) ?>
          </a>
          <?php if ($p['requiere_aprobacion'] && $p['estado'] === 'pendiente'): ?>
          <span style="display:block;font-size:.7rem;color:#B45309;font-weight:600;margin-top:2px">⏳ Pendiente aprobación</span>
          <?php endif; ?>
        </td>
        <td style="padding:12px;color:#374151"><?= htmlspecialchars($p['comprador_nombre'] . ' ' . $p['comprador_apellido']) ?></td>
        <td style="padding:12px;color:#374151"><?= $p['fecha_entrega'] ? date('d/m/Y', strtotime($p['fecha_entrega'])) : '—' ?></td>
        <td style="padding:12px;text-align:right;font-weight:700;color:#111827">$<?= number_format($p['total'], 2) ?></td>
        <td style="padding:12px;text-align:center">
          <span style="background:<?= $est['bg'] ?>;color:<?= $est['color'] ?>;padding:3px 10px;border-radius:999px;font-size:.75rem;font-weight:600">
            <?= $est['label'] ?>
          </span>
        </td>
        <td style="padding:12px 16px;color:#6B7280;font-size:.8rem"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
        <td style="padding:12px;text-align:center">
          <a href="<?= BASE_URL ?>pedido/detalle/<?= $p['id'] ?>" style="font-size:.8rem;color:#6B7280;text-decoration:none;margin-right:8px">Ver</a>
          <?php if (in_array($p['estado'], ['en_ruta','en_preparacion'], true)): ?>
          <a href="<?= BASE_URL ?>pedido/tracking/<?= $p['id'] ?>" style="font-size:.8rem;color:var(--color-primary);font-weight:600;text-decoration:none">Rastrear</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Paginación -->
<?php if (($paginacion['last_page'] ?? 1) > 1): ?>
<div style="display:flex;justify-content:center;gap:6px;margin-top:16px">
  <?php for ($i = 1; $i <= $paginacion['last_page']; $i++): ?>
  <a href="?page=<?= $i ?>&buscar=<?= urlencode($filtros['buscar'] ?? '') ?>&estado=<?= urlencode($filtros['estado'] ?? '') ?>"
     style="padding:6px 12px;border-radius:6px;font-size:.85rem;text-decoration:none;<?= $i === $paginacion['current_page'] ? 'background:var(--color-primary);color:#fff;font-weight:700' : 'background:#F3F4F6;color:#374151' ?>">
    <?= $i ?>
  </a>
  <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>
