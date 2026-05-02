<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
$ec=['pendiente'=>'badge-warning','confirmado'=>'badge-blue','en_preparacion'=>'badge-orange','en_ruta'=>'badge-info','entregado'=>'badge-success','cancelado'=>'badge-danger'];
$el=['pendiente'=>'Pendiente','confirmado'=>'Confirmado','en_preparacion'=>'En preparación','en_ruta'=>'En ruta','entregado'=>'Entregado','cancelado'=>'Cancelado'];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <h1 style="font-size:1.25rem;font-weight:700;margin:0">Pedidos</h1>
  <span class="badge badge-gray"><?= $pedidos['total'] ?> pedidos</span>
</div>

<!-- Filters -->
<div class="card" style="padding:12px 16px;margin-bottom:16px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
    <input type="text" name="q" class="form-control" style="flex:1;min-width:180px" placeholder="Buscar folio o cliente..." value="<?= htmlspecialchars($filtros['busqueda']) ?>">
    <select name="estado" class="form-control form-select" style="width:160px">
      <option value="">Todos los estados</option>
      <?php foreach ($el as $v=>$l): ?>
      <option value="<?=$v?>" <?= $filtros['estado']===$v?'selected':''?>><?=$l?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filtrar</button>
  </form>
</div>

<div class="card" style="padding:0">
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Folio</th>
          <th>Cliente</th>
          <th>Fecha</th>
          <th>Entrega</th>
          <th>Total</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pedidos['data'] as $p): ?>
        <tr>
          <td style="font-weight:700;font-size:.875rem"><?= $p['folio'] ?></td>
          <td style="font-size:.875rem"><?= htmlspecialchars($p['empresa_nombre']) ?></td>
          <td style="font-size:.8rem;color:#6B7280"><?= substr($p['fecha_pedido'],0,10) ?></td>
          <td style="font-size:.8rem"><?= $p['fecha_entrega'] ?? '—' ?></td>
          <td style="font-weight:600">$<?= number_format($p['total'],0,'.', ',') ?></td>
          <td>
            <select onchange="cambiarEstado(<?= $p['id'] ?>, this.value)" class="form-control form-select" style="width:140px;padding:4px 8px;font-size:.8rem">
              <?php foreach ($el as $v=>$l): ?>
              <option value="<?=$v?>" <?= $p['estado']===$v?'selected':''?>><?=$l?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <a href="<?= BASE_URL ?>pedido/detalle/<?= $p['id'] ?>" class="btn btn-sm btn-secondary">
              <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($pedidos['data'])): ?>
        <tr><td colspan="7" style="text-align:center;padding:32px;color:#9CA3AF">No se encontraron pedidos</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pedidos['last_page'] > 1): ?>
  <div style="padding:12px 16px">
    <div class="pagination">
      <?php for ($i=1; $i<=$pedidos['last_page']; $i++): ?>
      <a href="?page=<?=$i?>&estado=<?= $filtros['estado'] ?>&q=<?= urlencode($filtros['busqueda']) ?>"
         class="<?= $i===$pedidos['current_page']?'active':'' ?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function cambiarEstado(id, estado) {
  fetch(`<?= BASE_URL ?>pedido/cambiarEstado/${id}`, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `estado=${estado}`
  }).then(r=>r.json()).then(d=>{
    if(d.ok) showToast('Estado actualizado', 'success');
  });
}
</script>
<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
