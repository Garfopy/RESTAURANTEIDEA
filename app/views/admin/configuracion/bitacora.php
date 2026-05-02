<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
  <h1 style="font-size:1.25rem;font-weight:700;margin:0">Bitácora de Actividades</h1>
</div>
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
  <?php foreach (['general'=>'General','apis'=>'APIs','dispositivos'=>'Dispositivos IoT','bitacora'=>'Bitácora'] as $slug=>$label): ?>
  <a href="<?= BASE_URL ?>config/<?= $slug ?>" class="btn btn-sm <?= $slug==='bitacora'?'btn-primary':'btn-secondary' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<!-- Error summary -->
<?php if (!empty($errores['data'])): ?>
<div class="card" style="border-left:4px solid #EF4444;margin-bottom:16px">
  <div class="card-title" style="color:#EF4444;margin-bottom:10px">Errores recientes</div>
  <?php foreach ($errores['data'] as $e): ?>
  <div style="font-size:.8rem;padding:6px 0;border-bottom:1px solid #F3F4F6">
    <span class="badge badge-danger"><?= $e['nivel'] ?></span>
    <span style="margin-left:8px;color:#374151"><?= htmlspecialchars(substr($e['mensaje'],0,100)) ?></span>
    <span style="float:right;color:#9CA3AF"><?= $e['created_at'] ?></span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card" style="padding:12px;margin-bottom:16px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
    <input type="date" name="fecha" class="form-control" style="width:160px" value="<?= htmlspecialchars($filtros['fecha']) ?>">
    <select name="modulo" class="form-control form-select" style="width:160px">
      <option value="">Todos los módulos</option>
      <?php foreach (['auth','clientes','productos','pedidos','logistica','inventario','usuarios','configuracion','dispositivos','sistema'] as $m): ?>
      <option value="<?=$m?>" <?= $filtros['modulo']===$m?'selected':''?>><?= ucfirst($m) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filtrar</button>
  </form>
</div>

<div class="card" style="padding:0">
  <div class="table-container">
    <table>
      <thead><tr><th>Fecha</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>IP</th></tr></thead>
      <tbody>
        <?php foreach ($logs['data'] as $l): ?>
        <tr>
          <td style="font-size:.8rem;color:#6B7280;white-space:nowrap"><?= $l['created_at'] ?></td>
          <td style="font-size:.875rem"><?= htmlspecialchars($l['usuario_nombre'] ?? 'Sistema') ?></td>
          <td><span class="badge badge-gray"><?= $l['modulo'] ?></span></td>
          <td style="font-size:.875rem"><?= htmlspecialchars($l['accion']) ?></td>
          <td style="font-size:.75rem;color:#9CA3AF;font-family:monospace"><?= $l['ip'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($logs['data'])): ?>
        <tr><td colspan="5" style="text-align:center;padding:24px;color:#9CA3AF">Sin actividad registrada</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($logs['last_page'] > 1): ?>
  <div style="padding:12px 16px">
    <div class="pagination">
      <?php for ($i=1; $i<=$logs['last_page']; $i++): ?>
      <a href="?page=<?=$i?>&modulo=<?= urlencode($filtros['modulo']) ?>&fecha=<?= $filtros['fecha'] ?>"
         class="<?= $i===$logs['current_page']?'active':'' ?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
