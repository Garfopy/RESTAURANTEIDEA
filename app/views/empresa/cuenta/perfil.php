<?php
// Vista: Perfil de usuario (todos los roles)
$usuario = $usuario ?? $_SESSION['usuario'];
?>
<div style="max-width:560px">
  <!-- Datos del perfil -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px;margin-bottom:16px">
    <h2 style="font-size:.95rem;font-weight:700;margin-bottom:16px;color:#111827">Información personal</h2>

    <form method="POST" action="<?= BASE_URL ?>cuenta/guardar">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div>
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>
        </div>
        <div>
          <label class="form-label">Apellido paterno</label>
          <input type="text" name="apellido_paterno" class="form-control" value="<?= htmlspecialchars($usuario['apellido_paterno'] ?? '') ?>" required>
        </div>
      </div>
      <div style="margin-top:14px">
        <label class="form-label">Correo electrónico</label>
        <input type="email" class="form-control" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" readonly style="background:#F9FAFB;color:#6B7280">
      </div>
      <div style="margin-top:14px">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
      </div>
      <div style="margin-top:16px">
        <button type="submit" style="padding:9px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
          Guardar cambios
        </button>
      </div>
    </form>
  </div>

  <!-- Cambiar contraseña -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px">
    <h2 style="font-size:.95rem;font-weight:700;margin-bottom:16px;color:#111827">Cambiar contraseña</h2>

    <form method="POST" action="<?= BASE_URL ?>cuenta/cambiarPassword">
      <div style="margin-bottom:12px">
        <label class="form-label">Contraseña actual</label>
        <input type="password" name="password_actual" class="form-control" required>
      </div>
      <div style="margin-bottom:12px">
        <label class="form-label">Nueva contraseña (mínimo 8 caracteres)</label>
        <input type="password" name="password_nuevo" class="form-control" minlength="8" required>
      </div>
      <div style="margin-bottom:16px">
        <label class="form-label">Confirmar nueva contraseña</label>
        <input type="password" name="password_confirm" class="form-control" minlength="8" required>
      </div>
      <button type="submit" style="padding:9px 20px;background:#374151;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
        Cambiar contraseña
      </button>
    </form>
  </div>
</div>
