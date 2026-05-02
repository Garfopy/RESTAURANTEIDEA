<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h1 style="font-size:1.25rem;font-weight:800;margin:0">Usuarios del sistema</h1>
  <a href="<?= BASE_URL ?>usuario/crear" class="btn btn-primary">+ Nuevo usuario</a>
</div>

<?php if (!empty($flash)): ?>
<div class="toast <?= $flash['type'] ?>" style="margin-bottom:12px"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<!-- Filtros -->
<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="text" name="q" class="form-control" placeholder="Buscar por nombre o email..."
           value="<?= htmlspecialchars($busqueda ?? '') ?>" style="max-width:260px">
    <select name="rol" onchange="this.form.submit()" class="form-control form-select" style="max-width:180px">
      <option value="">Todos los roles</option>
      <?php foreach ($roles as $rol): ?>
      <option value="<?= $rol['id'] ?>" <?= ($_GET['rol'] ?? '') == $rol['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($rol['nombre']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Buscar</button>
  </form>
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
      <?php foreach (($usuarios['data'] ?? []) as $u): ?>
      <?php
        $nombreCompleto = trim(
            $u['nombre'] . ' ' . $u['apellido_paterno'] .
            (!empty($u['apellido_materno']) ? ' ' . $u['apellido_materno'] : '')
        );
        $inicial = strtoupper(mb_substr($u['nombre'], 0, 1, 'UTF-8') ?: '?');
      ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:36px;height:36px;border-radius:50%;background:#C8102E;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.875rem;flex-shrink:0;overflow:hidden">
              <?php if (!empty($u['avatar'])): ?>
              <img src="<?= BASE_URL . htmlspecialchars($u['avatar']) ?>" style="width:100%;height:100%;object-fit:cover" alt="">
              <?php else: ?>
              <?= $inicial ?>
              <?php endif; ?>
            </div>
            <div>
              <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($nombreCompleto) ?></div>
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
          <?php if ($u['id'] != ($_SESSION['usuario']['id'] ?? 0)): ?>
          <button onclick="toggleActivo(<?= $u['id'] ?>, <?= $u['activo'] ?>)"
                  class="btn btn-sm <?= $u['activo'] ? 'btn-secondary' : 'btn-primary' ?>">
            <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
          </button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($usuarios['data'])): ?>
      <tr><td colspan="6" style="text-align:center;color:#9CA3AF;padding:32px">No se encontraron usuarios.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Paginación -->
<?php if (($usuarios['last_page'] ?? 1) > 1): ?>
<div style="display:flex;gap:6px;justify-content:center;margin-top:16px;flex-wrap:wrap">
  <?php for ($p = 1; $p <= $usuarios['last_page']; $p++): ?>
  <a href="?page=<?= $p ?><?= $busqueda ? '&q=' . urlencode($busqueda) : '' ?>"
     style="padding:6px 12px;border-radius:6px;border:1px solid <?= $p == $usuarios['current_page'] ? '#C8102E' : '#E5E7EB' ?>;
            background:<?= $p == $usuarios['current_page'] ? '#C8102E' : '#fff' ?>;
            color:<?= $p == $usuarios['current_page'] ? '#fff' : '#374151' ?>;
            text-decoration:none;font-size:.8rem">
    <?= $p ?>
  </a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<script>
function toggleActivo(id, activo) {
  const msg = activo ? '¿Desactivar este usuario?' : '¿Activar este usuario?';
  if (!confirm(msg)) return;
  postJSON('<?= BASE_URL ?>usuario/toggleActivo', { id })
    .then(d => { if (d.ok) location.reload(); else alert('Error al cambiar estado.'); });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
