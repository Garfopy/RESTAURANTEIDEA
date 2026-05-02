<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
$esEdicion = !empty($usuario);
$nombreActual = $esEdicion ? trim($usuario['nombre']) : 'U';
$inicial = strtoupper(mb_substr($nombreActual, 0, 1, 'UTF-8') ?: 'U');
?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
  <a href="<?= BASE_URL ?>usuario/index" style="color:#6B7280;text-decoration:none;font-size:.875rem">← Usuarios</a>
  <h1 style="font-size:1.25rem;font-weight:800;margin:0"><?= $esEdicion ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
</div>

<?php if (!empty($flash) && $flash['type'] === 'error'): ?>
<div class="toast error" style="margin-bottom:12px"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>usuario/guardar" enctype="multipart/form-data">
<?php if ($esEdicion): ?><input type="hidden" name="id" value="<?= $usuario['id'] ?>"><?php endif; ?>
<input type="hidden" name="borrar_avatar" id="borrarAvatarInput" value="0">

<div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start">

  <!-- Columna izquierda: Avatar -->
  <div class="card" style="min-width:200px;flex:0 0 200px;display:flex;flex-direction:column;align-items:center;gap:12px;padding:24px 16px">
    <!-- Círculo de preview -->
    <div id="avatarPreview" style="width:88px;height:88px;border-radius:50%;background:#C8102E;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:2rem;overflow:hidden;border:3px solid #E5E7EB;flex-shrink:0">
      <?php if ($esEdicion && !empty($usuario['avatar'])): ?>
      <img id="avatarImg" src="<?= BASE_URL . htmlspecialchars($usuario['avatar']) ?>" style="width:100%;height:100%;object-fit:cover" alt="Avatar">
      <?php else: ?>
      <span id="avatarInitial"><?= $inicial ?></span>
      <?php endif; ?>
    </div>

    <p style="font-size:.75rem;color:#9CA3AF;text-align:center;margin:0">JPG, PNG o WebP · máx. 2 MB</p>

    <!-- Input oculto -->
    <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewAvatar(this)">

    <!-- 3 botones -->
    <button type="button" onclick="document.getElementById('avatarInput').click()" class="btn btn-secondary btn-sm" style="width:100%">
      Seleccionar imagen
    </button>
    <button type="submit" id="btnSubirImg" class="btn btn-primary btn-sm" style="width:100%;display:none">
      Subir imagen
    </button>
    <button type="button" id="btnBorrarImg" onclick="borrarAvatar()" class="btn btn-sm" style="width:100%;background:#EF4444;color:#fff;<?= ($esEdicion && !empty($usuario['avatar'])) ? '' : 'display:none' ?>">
      Borrar imagen
    </button>
  </div>

  <!-- Columna derecha: Datos -->
  <div class="card" style="flex:1;min-width:280px">
    <div style="display:flex;flex-direction:column;gap:14px">

      <div>
        <label class="form-label">Nombre(s) *</label>
        <input type="text" name="nombre" id="inputNombre" class="form-control"
               value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>"
               required placeholder="Ej: Juan Carlos"
               onkeyup="actualizarInicial()">
      </div>

      <div style="display:flex;gap:10px">
        <div style="flex:1">
          <label class="form-label">Apellido paterno *</label>
          <input type="text" name="apellido_paterno" class="form-control"
                 value="<?= htmlspecialchars($usuario['apellido_paterno'] ?? '') ?>"
                 required placeholder="Ej: García">
        </div>
        <div style="flex:1">
          <label class="form-label">Apellido materno</label>
          <input type="text" name="apellido_materno" class="form-control"
                 value="<?= htmlspecialchars($usuario['apellido_materno'] ?? '') ?>"
                 placeholder="Ej: López">
        </div>
      </div>

      <div>
        <label class="form-label">Correo electrónico *</label>
        <input type="email" name="email" class="form-control"
               value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
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
          <option value="<?= $rol['id'] ?>" data-slug="<?= $rol['slug'] ?>"
            <?= ($usuario['rol_id'] ?? '') == $rol['id'] ? 'selected' : '' ?>>
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

    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px">
      <a href="<?= BASE_URL ?>usuario/index" class="btn btn-secondary">Cancelar</a>
      <button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Guardar cambios' : 'Crear usuario' ?></button>
    </div>
  </div>

</div>
</form>

<script>
function previewAvatar(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    const preview = document.getElementById('avatarPreview');
    preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover" alt="Avatar">';
    document.getElementById('btnSubirImg').style.display = 'block';
    document.getElementById('borrarAvatarInput').value = '0';
  };
  reader.readAsDataURL(input.files[0]);
}

function borrarAvatar() {
  if (!confirm('¿Borrar la imagen de perfil?')) return;
  document.getElementById('borrarAvatarInput').value = '1';
  document.getElementById('avatarInput').value = '';
  const nombre = document.getElementById('inputNombre').value.trim();
  const inicial = nombre ? nombre.charAt(0).toUpperCase() : 'U';
  document.getElementById('avatarPreview').innerHTML = '<span id="avatarInitial">' + inicial + '</span>';
  document.getElementById('btnBorrarImg').style.display = 'none';
  document.getElementById('btnSubirImg').style.display = 'none';
}

function actualizarInicial() {
  const el = document.getElementById('avatarInitial');
  if (!el) return;
  const nombre = document.getElementById('inputNombre').value.trim();
  el.textContent = nombre ? nombre.charAt(0).toUpperCase() : 'U';
}

function mostrarEmpresa(rolId) {
  const opt = document.querySelector('select[name=rol_id] option[value="' + rolId + '"]');
  const slug = opt ? opt.getAttribute('data-slug') : '';
  const campo = document.getElementById('campoEmpresa');
  campo.style.display = ['comprador','adminempresa','supervisor'].includes(slug) ? 'block' : 'none';
}

mostrarEmpresa(document.querySelector('select[name=rol_id]').value);
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
