<?php
// Vista: Perfil de usuario (todos los roles)
$usuario = $usuario ?? $_SESSION['usuario'];
$iniciales = strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1) . mb_substr($usuario['apellido_paterno'] ?? '', 0, 1));
?>
<div style="max-width:560px">

  <!-- Avatar -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px;margin-bottom:16px">
    <h2 style="font-size:.95rem;font-weight:700;margin-bottom:16px;color:#111827">Foto de perfil</h2>
    <form method="POST" action="<?= BASE_URL ?>cuenta/subirAvatar" enctype="multipart/form-data"
          style="display:flex;align-items:center;gap:20px">
      <!-- Foto o iniciales -->
      <?php if (!empty($usuario['avatar'])): ?>
        <img src="<?= htmlspecialchars($usuario['avatar']) ?>" alt="Avatar"
             style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid #E5E7EB;flex-shrink:0">
      <?php else: ?>
        <div style="width:72px;height:72px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.3rem;color:var(--color-primary);flex-shrink:0;border:2px solid #FECACA">
          <?= htmlspecialchars($iniciales) ?>
        </div>
      <?php endif; ?>

      <div>
        <label for="avatar_input" style="display:inline-block;padding:8px 16px;background:#F3F4F6;border:1px solid #E5E7EB;border-radius:8px;font-size:.85rem;font-weight:600;color:#374151;cursor:pointer">
          Cambiar foto
        </label>
        <input type="file" id="avatar_input" name="avatar" accept=".jpg,.jpeg,.png,.webp"
               style="display:none" onchange="this.form.submit()">
        <p style="font-size:.75rem;color:#9CA3AF;margin-top:6px">JPG, PNG o WebP · Máx 2 MB</p>
      </div>
    </form>
  </div>

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
