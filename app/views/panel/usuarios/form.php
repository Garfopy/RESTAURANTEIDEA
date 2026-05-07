<?php
// Variables: $usuario (null=nuevo, array=editar), $roles[], $empresas[]
$esEdicion = $usuario !== null;
$accion    = $esEdicion
    ? BASE_URL . 'panel-usuario/actualizar/' . $usuario['id']
    : BASE_URL . 'panel-usuario/guardar';
?>
<div style="max-width:580px">
  <a href="<?= BASE_URL ?>panel-usuario/index"
     style="display:inline-flex;align-items:center;gap:6px;color:#6B7280;font-size:.875rem;text-decoration:none;margin-bottom:20px">
    ← Volver a usuarios
  </a>

  <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:24px">
    <h3 style="margin:0 0 20px;font-size:1rem;font-weight:700;color:#111827">
      <?= $esEdicion ? 'Editar usuario' : 'Nuevo usuario de plataforma' ?>
    </h3>

    <form method="POST" action="<?= $accion ?>">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
        <div>
          <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px">Nombre *</label>
          <input type="text" name="nombre" required value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>"
                 style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box;outline:none">
        </div>
        <div>
          <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px">Apellido paterno</label>
          <input type="text" name="apellido_paterno" value="<?= htmlspecialchars($usuario['apellido_paterno'] ?? '') ?>"
                 style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box;outline:none">
        </div>
      </div>

      <?php if (!$esEdicion): ?>
      <div style="margin-bottom:14px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px">Email *</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($usuario['email'] ?? '') ?>"
               style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box;outline:none">
      </div>
      <?php else: ?>
      <div style="margin-bottom:14px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#6B7280;margin-bottom:5px">Email (no editable)</label>
        <div style="padding:8px 12px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;font-size:.875rem;color:#374151">
          <?= htmlspecialchars($usuario['email']) ?>
        </div>
      </div>
      <?php endif; ?>

      <div style="margin-bottom:14px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px">Teléfono</label>
        <input type="tel" name="telefono" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>"
               style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box;outline:none">
      </div>

      <?php if (!$esEdicion): ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
        <div>
          <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px">Rol *</label>
          <select name="rol_slug" required id="sel-rol" onchange="toggleEmpresa(this.value)"
                  style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box">
            <option value="">Seleccionar...</option>
            <?php foreach ($roles as $r): ?>
            <option value="<?= $r['slug'] ?>" <?= ($usuario['rol_slug'] ?? '') === $r['slug'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($r['nombre']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="campo-empresa">
          <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px">Empresa</label>
          <select name="empresa_id"
                  style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box">
            <option value="">Sin empresa (admin plataforma)</option>
            <?php foreach ($empresas as $emp): ?>
            <option value="<?= $emp['id'] ?>" <?= ($usuario['empresa_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($emp['razon_social']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php endif; ?>

      <div style="margin-bottom:20px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px">
          Contraseña <?= $esEdicion ? '(dejar vacío para no cambiar)' : '*' ?>
        </label>
        <input type="password" name="password" <?= $esEdicion ? '' : 'required' ?> autocomplete="new-password"
               minlength="6" placeholder="Mínimo 6 caracteres"
               style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box;outline:none">
      </div>

      <div style="display:flex;gap:10px">
        <button type="submit"
                style="padding:10px 24px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer">
          <?= $esEdicion ? 'Guardar cambios' : 'Crear usuario' ?>
        </button>
        <a href="<?= BASE_URL ?>panel-usuario/index"
           style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;font-size:.875rem;text-decoration:none;font-weight:600">
          Cancelar
        </a>
      </div>

    </form>
  </div>
</div>

<script>
function toggleEmpresa(rol) {
  const campo = document.getElementById('campo-empresa');
  if (campo) {
    // Ya no hay rol 'admin', solo 'admin_empresa' requiere empresa_id
    campo.style.opacity = (rol === 'admin_empresa') ? '1' : '0.5';
  }
}
</script>
