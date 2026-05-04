<?php
// Vista: Formulario alta/edición de usuario de empresa
$editando = !empty($usuario);
?>
<div style="max-width:560px">
  <form method="POST" action="<?= BASE_URL ?><?= $editando ? 'empresa-usuario/actualizar/'.$usuario['id'] : 'empresa-usuario/guardar' ?>">

    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px;margin-bottom:16px">
      <h2 style="font-size:.95rem;font-weight:700;margin-bottom:16px;color:#111827">
        <?= $editando ? 'Editar usuario' : 'Nuevo usuario' ?>
      </h2>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div>
          <label class="form-label">Nombre *</label>
          <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>
        </div>
        <div>
          <label class="form-label">Apellido paterno *</label>
          <input type="text" name="apellido_paterno" class="form-control" value="<?= htmlspecialchars($usuario['apellido_paterno'] ?? '') ?>" required>
        </div>
      </div>

      <div style="margin-top:14px">
        <label class="form-label">Correo electrónico *</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" <?= $editando ? 'readonly style="background:#F9FAFB"' : 'required' ?>>
        <?php if ($editando): ?>
        <p style="font-size:.75rem;color:#6B7280;margin-top:4px">El correo no se puede cambiar.</p>
        <?php endif; ?>
      </div>

      <div style="margin-top:14px">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" placeholder="10 dígitos">
      </div>

      <?php if (!$editando): ?>
      <div style="margin-top:14px">
        <label class="form-label">Rol *</label>
        <select name="rol_id" class="form-control" required>
          <option value="">Selecciona un rol</option>
          <?php foreach ($roles as $r): ?>
          <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <p style="font-size:.75rem;color:#6B7280;margin-top:4px">
          <strong>Supervisor:</strong> Aprueba pedidos y configura límites. |
          <strong>Comprador:</strong> Hace pedidos. |
          <strong>Repartidor:</strong> Realiza entregas con GPS.
        </p>
      </div>
      <?php else: ?>
      <div style="margin-top:14px">
        <label class="form-label">Estado</label>
        <select name="activo" class="form-control">
          <option value="1" <?= $usuario['activo'] ? 'selected' : '' ?>>Activo</option>
          <option value="0" <?= !$usuario['activo'] ? 'selected' : '' ?>>Inactivo</option>
        </select>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!$editando): ?>
    <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:.85rem;color:#92400E">
      Se generará una contraseña temporal que deberás comunicar al usuario para que pueda acceder.
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:10px">
      <button type="submit" style="padding:10px 24px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
        <?= $editando ? 'Guardar cambios' : 'Crear usuario' ?>
      </button>
      <a href="<?= BASE_URL ?>empresa-usuario/index" style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
        Cancelar
      </a>
    </div>
  </form>
</div>
