<?php ob_start(); ?>
<?php
function facturaEstadoBadge(string $estado): string {
    $map = [
        'pendiente' => ['badge-amber', 'Pendiente'],
        'en_proceso' => ['badge-blue', 'En proceso'],
        'facturada' => ['badge-green', 'Facturada'],
        'cancelada' => ['badge-gray', 'Cancelada'],
    ];
    [$class, $label] = $map[$estado] ?? ['badge-gray', ucfirst($estado)];
    return '<span class="badge ' . $class . '">' . htmlspecialchars($label) . '</span>';
}
$rows = $solicitudes['data'] ?? [];
$currentPage = (int)($solicitudes['current_page'] ?? 1);
$lastPage = max(1, (int)($solicitudes['last_page'] ?? 1));
?>

<div class="rst-card">
  <form method="GET" action="<?= BASE_URL ?>rest-factura/index" style="display:grid;grid-template-columns:1fr 1fr 1fr auto auto;gap:12px;align-items:end">
    <div>
      <label class="form-label">Estado</label>
      <select name="estado" class="form-input">
        <?php foreach (['' => 'Todos', 'pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'facturada' => 'Facturada', 'cancelada' => 'Cancelada'] as $value => $label): ?>
        <option value="<?= $value ?>" <?= ($filtros['estado'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="form-label">Desde</label>
      <input type="date" name="from" class="form-input" value="<?= htmlspecialchars($filtros['from'] ?? '') ?>">
    </div>
    <div>
      <label class="form-label">Hasta</label>
      <input type="date" name="to" class="form-input" value="<?= htmlspecialchars($filtros['to'] ?? '') ?>">
    </div>
    <button class="btn btn-primary" type="submit">Filtrar</button>
    <a class="btn btn-outline" href="<?= BASE_URL ?>rest-factura/index?estado=pendiente">Pendientes</a>
  </form>
</div>

<div class="rst-table-wrap">
  <table class="rst-table">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Origen</th>
        <th>Tipo</th>
        <th>Referencia</th>
        <th>RFC receptor</th>
        <th>Nombre fiscal</th>
        <th>Monto</th>
        <th>Pago</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
      <tr>
        <td colspan="10">
          <div class="empty-state" style="padding:34px 20px">No hay solicitudes de factura con estos filtros.</div>
        </td>
      </tr>
      <?php endif; ?>
      <?php foreach ($rows as $s): ?>
      <?php
        $ref = $s['pedido_folio'] ? 'Pedido ' . $s['pedido_folio'] : '';
        if ($s['ticket_folio']) $ref .= ($ref ? ' / ' : '') . 'Ticket ' . $s['ticket_folio'];
        if ($s['mesa_nombre']) $ref .= ($ref ? ' / ' : '') . 'Mesa ' . $s['mesa_nombre'];
        $ref = $ref ?: ('Solicitud #' . (int)$s['id']);
      ?>
      <tr>
        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($s['created_at']))) ?></td>
        <td><?= htmlspecialchars($s['origen']) ?></td>
        <td><?= htmlspecialchars(str_replace('_', ' ', $s['scope'])) ?></td>
        <td><?= htmlspecialchars($ref) ?></td>
        <td style="font-family:monospace"><?= htmlspecialchars($s['receptor_rfc']) ?></td>
        <td><?= htmlspecialchars($s['receptor_nombre_fiscal']) ?></td>
        <td>$<?= number_format((float)$s['monto'], 2) ?></td>
        <td><?= htmlspecialchars($s['metodo_pago'] ?? '-') ?></td>
        <td><?= facturaEstadoBadge($s['estado']) ?></td>
        <td><a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>rest-factura/detalle/<?= (int)$s['id'] ?>">Ver</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($lastPage > 1): ?>
<div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px">
  <?php
    $query = $_GET;
    $query['page'] = max(1, $currentPage - 1);
  ?>
  <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>rest-factura/index?<?= htmlspecialchars(http_build_query($query)) ?>" <?= $currentPage <= 1 ? 'style="pointer-events:none;opacity:.5"' : '' ?>>Anterior</a>
  <span style="font-size:.82rem;color:#6B7280;align-self:center">Pagina <?= $currentPage ?> de <?= $lastPage ?></span>
  <?php $query['page'] = min($lastPage, $currentPage + 1); ?>
  <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>rest-factura/index?<?= htmlspecialchars(http_build_query($query)) ?>" <?= $currentPage >= $lastPage ? 'style="pointer-events:none;opacity:.5"' : '' ?>>Siguiente</a>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
