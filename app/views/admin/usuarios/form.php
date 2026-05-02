<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
$esEdicion = !empty($usuario);
?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
  <a href="<?= BASE_URL ?>usuario/index" style="color:#6B7280;text-decoration:none;font-size:.875rem">← Usuarios</a>
  <h1 style="font-size:1.25rem;font-weight:800;margin:0"><?= $esEdicion ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
</div>

<?php if (!empty($error)): ?>
<div class="toast error" style="margin-bottom:12px"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:560px">
  <form method="POST" action="<?= BASE_URL ?>usuario/guardar">
    <?php if ($esEdicion): ?><input type="hidden" name="id" value="<?= $usuario['id'] ?>"><?php endif; ?>

    <div style="display:flex;flex-direction:column;gap:14px">
      <div>
        <label class="form-label">Nombre completo *</label>
        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>
      </div>
      <div>
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
      </div>
      <div>
        <label class="form-label">Contraseña <?= $esEdicion ? '<small style="color:#9CA3AF">(vacío = sin cambio)</small>' : '*' ?></label>
        <input type="password" name="password" class="form-control" minlength="6" <?= $esEdicion ? '' : 'required' ?>>
      </div>
      <div>
        <label class="form-label">Rol *</label>
        <select name="rol_id" class="form-control form-select" required onchange="mostrarEmpresa(this.value)">
          <option value="">Seleccionar rol</option>
          <?php foreach ($roles as $rol): ?>
          <option value="<?= $rol['id'] ?>" data-slug="<?= $rol['slug'] ?>" <?= ($usuario['rol_id'] ?? '') == $rol['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($rol['nombre']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="campoEmpresa">
        <label class="form-label">Empresa asociada</label>
        <select name="empresa_id" class="form-control form-select">
          <option value="">Sin empresa</option>
          <?php foreach ($empresas as $e): ?>
          <option value="<?= $e['id'] ?>" <?= ($usuario['empresa_id'] ?? '') == $e['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($e['razon_social']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Estado</label>
        <select name="activo" class="form-control form-select">
          <option value="1" <?= ($usuario['activo'] ?? 1) ? 'selected' : '' ?>>Activo</option>
          <option value="0" <?= !($usuario['activo'] ?? 1) ? 'selected' : '' ?>>Inactivo</option>
        </select>
      </div>
    </div>

    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
      <a href="<?= BASE_URL ?>usuario/index" class="btn btn-secondary">Cancelar</a>
      <button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Guardar cambios' : 'Crear usuario' ?></button>
    </div>
  </form>
</div>

<script>
function mostrarEmpresa(rolId) {
  const opt = document.querySelector(`select[name=rol_id] option[value="${rolId}"]`);
  const slug = opt ? opt.getAttribute('data-slug') : '';
  const campo = document.getElementById('campoEmpresa');
  const adminRoles = ['comprador','adminempresa','supervisor'];
  campo.style.display = adminRoles.includes(slug) ? 'block' : 'none';
}
// Init on load
mostrarEmpresa(document.querySelector('select[name=rol_id]').value);
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
