<?php ob_start(); ?>

<style>
.sa-header { margin-bottom: 18px; }
.sa-title { color: #0F172A; font-size: 1.45rem; font-weight: 800; margin: 0; }
.sa-copy { color: #64748B; font-size: .9rem; margin: 4px 0 0; }
.sa-form-card {
  background: #fff; border: 1px solid #E2E8F0; border-radius: 12px;
  margin-bottom: 18px; padding: 18px;
}
.sa-form-grid { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
.sa-form-grid label { color: #475569; display: block; font-size: .78rem; font-weight: 600; margin-bottom: 4px; }
.sa-form-grid input, .sa-form-grid select {
  border: 1px solid #E2E8F0; border-radius: 8px; font-size: .85rem; padding: 8px 10px; width: 100%;
}
.sa-filters { display: flex; gap: 10px; margin-bottom: 14px; }
.sa-filters select, .sa-filters input {
  border: 1px solid #E2E8F0; border-radius: 8px; font-size: .82rem; padding: 6px 10px;
}
.sa-btn {
  border: 1px solid #E2E8F0; border-radius: 8px; cursor: pointer; font-size: .78rem;
  font-weight: 600; padding: 6px 12px;
}
.sa-btn-primary { background: #1E293B; border-color: #1E293B; color: #fff; }
.sa-btn-toggle { background: #fff; color: #334155; }
.sa-badge {
  border-radius: 999px; display: inline-block; font-size: .72rem; font-weight: 700;
  padding: 3px 10px; text-transform: uppercase;
}
.sa-badge-on  { background: #D1FAE5; color: #065F46; }
.sa-badge-off { background: #E5E7EB; color: #374151; }
.sa-rol-chip { background: #EEF2FF; border-radius: 6px; color: #3730A3; font-size: .72rem; font-weight: 700; padding: 2px 8px; }
</style>

<div class="sa-header">
  <h1 class="sa-title">Usuarios</h1>
  <p class="sa-copy">Cuentas Admin, Cajero y Cocina de todos los negocios — <?= (int)$resultado['total'] ?> en total.</p>
</div>

<div class="sa-form-card">
  <form method="post" action="<?= BASE_URL ?>superadmin/usuarioCrear">
    <div class="sa-form-grid">
      <div>
        <label>Nombre completo</label>
        <input type="text" name="nombre" placeholder="Nombre Apellido" required>
      </div>
      <div>
        <label>Correo</label>
        <input type="email" name="email" required>
      </div>
      <div>
        <label>Rol</label>
        <select name="rol_slug" required>
          <option value="">Selecciona...</option>
          <?php foreach ($roles as $rol): ?>
          <option value="<?= htmlspecialchars($rol['slug']) ?>"><?= htmlspecialchars($rol['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Negocio (si aplica)</label>
        <select name="restaurante_id">
          <option value="">— Ninguno (solo superadmin) —</option>
          <?php foreach ($negocios as $n): ?>
          <option value="<?= (int)$n['id'] ?>"><?= htmlspecialchars($n['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div style="margin-top:12px">
      <button type="submit" class="sa-btn sa-btn-primary">Crear usuario</button>
    </div>
  </form>
</div>

<form method="get" action="<?= BASE_URL ?>superadmin/usuarios" class="sa-filters">
  <input type="text" name="buscar" placeholder="Buscar por nombre o correo" value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>">
  <select name="rol_slug" onchange="this.form.submit()">
    <option value="">Todos los roles</option>
    <?php foreach ($roles as $rol): ?>
    <option value="<?= htmlspecialchars($rol['slug']) ?>" <?= ($filtros['rol_slug'] ?? '') === $rol['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($rol['nombre']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="sa-btn sa-btn-toggle">Filtrar</button>
</form>

<div class="rst-card">
  <div class="rst-table-wrap">
    <table class="rst-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Correo</th>
          <th>Rol</th>
          <th>Negocio</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($resultado['data'] as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido_paterno']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="sa-rol-chip"><?= htmlspecialchars($u['rol_nombre']) ?></span></td>
          <td><?= htmlspecialchars($u['restaurante_nombre'] ?? '—') ?></td>
          <td>
            <span class="sa-badge <?= $u['activo'] ? 'sa-badge-on' : 'sa-badge-off' ?>">
              <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:8px">
              <form method="post" action="<?= BASE_URL ?>superadmin/usuarioToggle/<?= (int)$u['id'] ?>" style="display:inline">
                <button type="submit" class="sa-btn sa-btn-toggle"><?= $u['activo'] ? 'Desactivar' : 'Reactivar' ?></button>
              </form>
              <form method="post" action="<?= BASE_URL ?>superadmin/usuarioResetPassword/<?= (int)$u['id'] ?>" style="display:inline"
                    onsubmit="return confirm('¿Restablecer la contraseña de <?= htmlspecialchars($u['email'], ENT_QUOTES) ?>?');">
                <button type="submit" class="sa-btn sa-btn-toggle">Resetear password</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($resultado['data'])): ?>
        <tr><td colspan="6" style="text-align:center;color:#94A3B8;padding:24px">Sin resultados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($resultado['last_page'] > 1): ?>
<div style="display:flex;gap:8px;justify-content:center;margin-top:14px">
  <?php for ($i = 1; $i <= $resultado['last_page']; $i++): ?>
  <a class="sa-btn <?= $i === $resultado['current_page'] ? 'sa-btn-primary' : 'sa-btn-toggle' ?>"
     style="text-decoration:none"
     href="<?= BASE_URL ?>superadmin/usuarios?page=<?= $i ?><?= !empty($filtros['rol_slug']) ? '&rol_slug=' . urlencode($filtros['rol_slug']) : '' ?><?= !empty($filtros['buscar']) ? '&buscar=' . urlencode($filtros['buscar']) : '' ?>">
    <?= $i ?>
  </a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/superadmin/layouts/main.php';
