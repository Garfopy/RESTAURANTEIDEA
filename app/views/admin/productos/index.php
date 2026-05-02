<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <h1 style="font-size:1.25rem;font-weight:700;margin:0">Productos</h1>
  <a href="<?= BASE_URL ?>producto/crear" class="btn btn-primary">
    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nuevo producto
  </a>
</div>

<?php if (!empty($flash)): ?>
<div class="toast <?=$flash['type']==='success'?'success':'error'?>" style="margin-bottom:16px;position:relative;max-width:100%">
  <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card" style="padding:12px 16px;margin-bottom:16px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
    <input type="text" name="q" class="form-control" style="flex:1;min-width:180px" placeholder="Buscar producto..." value="<?= htmlspecialchars($filtros['busqueda']) ?>">
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <a href="<?= BASE_URL ?>producto/index" class="btn btn-sm <?= !$filtros['categoria_id'] ? 'btn-primary':'btn-secondary' ?>">Todos</a>
      <?php foreach ($categorias as $cat): ?>
      <a href="?cat=<?= $cat['id'] ?>" class="btn btn-sm <?= $filtros['categoria_id']==$cat['id'] ? 'btn-primary':'btn-secondary' ?>"><?= $cat['nombre'] ?></a>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
  </form>
</div>

<div class="card" style="padding:0">
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th style="width:50px"></th>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Presentación</th>
          <th>Precio base</th>
          <th>Stock</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($productos['data'] as $p): ?>
        <tr>
          <td>
            <div style="width:40px;height:40px;border-radius:8px;overflow:hidden;background:#F3F4F6;display:flex;align-items:center;justify-content:center">
              <?php if ($p['imagen']): ?>
              <img src="<?= UPLOAD_URL ?>productos/<?= htmlspecialchars($p['imagen']) ?>" style="width:100%;height:100%;object-fit:cover">
              <?php else: ?>
              <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#9CA3AF"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <?php endif; ?>
            </div>
          </td>
          <td style="font-weight:600"><?= htmlspecialchars($p['nombre']) ?></td>
          <td><span class="badge badge-gray"><?= $p['categoria_nombre'] ?></span></td>
          <td style="font-size:.875rem">Por <?= $p['presentacion'] ?></td>
          <td style="font-weight:600">$<?= number_format($p['precio_base'],2) ?></td>
          <td>
            <?php
            $stock = $p['stock_disponible'] ?? 0;
            $badge = $stock <= 50 ? 'badge-danger' : ($stock <= 100 ? 'badge-warning' : 'badge-success');
            ?>
            <span class="badge <?= $badge ?>"><?= number_format($stock,0) ?> kg</span>
          </td>
          <td><span class="badge <?= $p['activo']?'badge-success':'badge-gray' ?>"><?= $p['activo']?'Activo':'Inactivo' ?></span></td>
          <td>
            <div style="display:flex;gap:4px">
              <a href="<?= BASE_URL ?>producto/precios/<?= $p['id'] ?>" class="btn btn-sm btn-secondary" title="Precios">$</a>
              <a href="<?= BASE_URL ?>producto/editar/<?= $p['id'] ?>" class="btn btn-sm btn-secondary" title="Editar">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($productos['last_page'] > 1): ?>
  <div style="padding:12px 16px">
    <div class="pagination">
      <?php for ($i=1; $i<=$productos['last_page']; $i++): ?>
      <a href="?page=<?=$i?>" class="<?= $i===$productos['current_page']?'active':'' ?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
