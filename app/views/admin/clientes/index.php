<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
$s = $stats ?? [];
?>
<!-- Flash -->
<?php if (!empty($flash)): ?>
<div class="toast <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" style="margin-bottom:16px;max-width:100%;position:relative">
  <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<!-- Header + stats chips -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <h1 style="font-size:1.25rem;font-weight:700;margin:0">Clientes</h1>
    <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap">
      <span class="badge badge-gray">Todos <?= $s['total'] ?? 0 ?></span>
      <span class="badge badge-success">Activos <?= $s['activos'] ?? 0 ?></span>
      <span class="badge badge-danger">Inactivos <?= $s['inactivos'] ?? 0 ?></span>
    </div>
  </div>
  <a href="<?= BASE_URL ?>cliente/crear" class="btn btn-primary">
    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nuevo cliente
  </a>
</div>

<!-- Filters -->
<div class="card" style="padding:14px;margin-bottom:16px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
    <div style="flex:1;min-width:200px">
      <input type="text" name="q" value="<?= htmlspecialchars($filtros['busqueda']) ?>" class="form-control" placeholder="Buscar cliente...">
    </div>
    <select name="activo" class="form-control form-select" style="width:140px">
      <option value="">Todos</option>
      <option value="1" <?= $filtros['activo']==='1'?'selected':'' ?>>Activos</option>
      <option value="0" <?= $filtros['activo']==='0'?'selected':'' ?>>Inactivos</option>
    </select>
    <button type="submit" class="btn btn-secondary">Filtrar</button>
    <a href="<?= BASE_URL ?>cliente/index" class="btn btn-secondary">Limpiar</a>
  </form>
</div>

<!-- Table -->
<div class="card" style="padding:0">
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th style="width:40px"></th>
          <th>Razón Social / RFC</th>
          <th class="hide-mobile">Email</th>
          <th class="hide-mobile">Crédito</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clientes['data'] as $c): ?>
        <tr>
          <td>
            <div style="width:36px;height:36px;border-radius:50%;background:#FEE2E2;color:#C8102E;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.875rem">
              <?= strtoupper(substr($c['razon_social'],0,1)) ?>
            </div>
          </td>
          <td>
            <div style="font-weight:600"><?= htmlspecialchars($c['razon_social']) ?></div>
            <div style="font-size:.75rem;color:#6B7280"><?= $c['rfc'] ?></div>
          </td>
          <td class="hide-mobile" style="font-size:.875rem;color:#6B7280"><?= htmlspecialchars($c['email'] ?? '') ?></td>
          <td class="hide-mobile">
            <?php if ($c['credito_activo']): ?>
              <span class="badge badge-success">Activo — $<?= number_format($c['limite_credito'],0,'.', ',') ?></span>
            <?php else: ?>
              <span class="badge badge-gray">Desactivado</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge <?= $c['activo'] ? 'badge-success' : 'badge-danger' ?>">
              <?= $c['activo'] ? 'Activo' : 'Inactivo' ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="<?= BASE_URL ?>cliente/detalle/<?= $c['id'] ?>" class="btn btn-sm btn-secondary" title="Ver">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </a>
              <a href="<?= BASE_URL ?>cliente/editar/<?= $c['id'] ?>" class="btn btn-sm btn-secondary" title="Editar">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($clientes['data'])): ?>
        <tr><td colspan="6" style="text-align:center;padding:32px;color:#9CA3AF">No se encontraron clientes</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($clientes['last_page'] > 1): ?>
  <div style="padding:12px 16px">
    <div class="pagination">
      <?php for ($i=1; $i<=$clientes['last_page']; $i++): ?>
      <a href="?page=<?=$i?>&q=<?= urlencode($filtros['busqueda']) ?>&activo=<?= $filtros['activo'] ?>"
         class="<?= $i === $clientes['current_page'] ? 'active' : '' ?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
