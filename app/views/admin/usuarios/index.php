<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h1 style="font-size:1.25rem;font-weight:800;margin:0">Usuarios del sistema</h1>
  <a href="<?= BASE_URL ?>usuario/crear" class="btn btn-primary">+ Nuevo usuario</a>
</div>

<!-- Filtros -->
<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
  <select onchange="location='?rol='+this.value" class="form-control form-select" style="max-width:180px">
    <option value="">Todos los roles</option>
    <?php foreach ($roles as $rol): ?>
    <option value="<?= $rol['id'] ?>" <?= ($_GET['rol'] ?? '') == $rol['id'] ? 'selected' : '' ?>>
      <?= htmlspecialchars($rol['nombre']) ?>
    </option>
    <?php endforeach; ?>
  </select>
</div>

<div class="card" style="padding:0;overflow-x:auto">
  <table class="table">
    <thead>
      <tr>
        <th>Usuario</th>
        <th>Rol</th>
        <th>Empresa</th>
        <th>Creado</th>
        <th>Estado</th>
        <th style="text-align:right">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($usuarios as $u): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:32px;height:32px;border-radius:50%;background:#C8102E;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.75rem;flex-shrink:0">
              <?= strtoupper(substr($u['nombre'],0,1)) ?>
            </div>
            <div>
              <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($u['nombre']) ?></div>
              <div style="font-size:.75rem;color:#6B7280"><?= htmlspecialchars($u['email']) ?></div>
            </div>
          </div>
        </td>
        <td><span class="badge badge-primary"><?= htmlspecialchars($u['rol_nombre']) ?></span></td>
        <td style="color:#6B7280;font-size:.8rem"><?= htmlspecialchars($u['empresa_nombre'] ?? '—') ?></td>
        <td style="color:#6B7280;font-size:.8rem"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
        <td>
          <?php if ($u['activo']): ?>
          <span class="badge badge-success">Activo</span>
          <?php else: ?>
          <span class="badge badge-danger">Inactivo</span>
          <?php endif; ?>
        </td>
        <td style="text-align:right">
          <a href="<?= BASE_URL ?>usuario/editar/<?= $u['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
          <?php if ($u['id'] != $_SESSION['usuario']['id']): ?>
          <button onclick="toggleActivo(<?= $u['id'] ?>, <?= $u['activo'] ?>)" class="btn btn-sm <?= $u['activo'] ? 'btn-secondary' : 'btn-primary' ?>">
            <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
          </button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
function toggleActivo(id, activo) {
  const msg = activo ? '¿Desactivar este usuario?' : '¿Activar este usuario?';
  if (!confirm(msg)) return;
  postJSON('<?= BASE_URL ?>usuario/toggleActivo', { id })
    .then(d => { if (d.ok) location.reload(); });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
