<?php
// Vista: Perfil de usuario (todos los roles)
$usuario = $usuario ?? $_SESSION['usuario'];
$iniciales = strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1) . mb_substr($usuario['apellido_paterno'] ?? '', 0, 1));
?>
<div style="max-width:560px">

  <!-- Avatar -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px;margin-bottom:16px">
    <h2 style="font-size:.95rem;font-weight:700;margin-bottom:16px;color:#111827">Foto de perfil</h2>
    <div style="display:flex;align-items:flex-start;gap:20px">

      <!-- Foto circular o iniciales -->
      <div id="avatar-preview" style="flex-shrink:0">
        <?php if (!empty($usuario['avatar'])): ?>
          <img src="<?= htmlspecialchars($usuario['avatar']) ?>" alt="Avatar"
               style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid #E5E7EB">
        <?php else: ?>
          <div style="width:80px;height:80px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.4rem;color:var(--color-primary);border:2px solid #FECACA">
            <?= htmlspecialchars($iniciales) ?>
          </div>
        <?php endif; ?>
      </div>

      <div style="flex:1">
        <!-- Form subir -->
        <form id="form-avatar" method="POST" action="<?= BASE_URL ?>cuenta/subirAvatar" enctype="multipart/form-data">
          <input type="file" id="avatar_input" name="avatar" accept=".jpg,.jpeg,.png,.webp" style="display:none">

          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <!-- Botón 1: Seleccionar -->
            <button type="button"
                    onclick="document.getElementById('avatar_input').click()"
                    style="padding:8px 16px;border:1px solid #D1D5DB;border-radius:8px;background:#F9FAFB;font-size:.85rem;font-weight:600;color:#374151;cursor:pointer">
              Seleccionar archivo
            </button>

            <!-- Botón 2: Subir -->
            <button type="submit" id="btn-subir" disabled
                    style="padding:8px 16px;border:none;border-radius:8px;background:var(--color-primary);color:#fff;font-size:.85rem;font-weight:600;cursor:pointer;opacity:.5">
              Subir foto
            </button>
          </div>

          <script>
            document.getElementById('avatar_input').addEventListener('change', function() {
              var btn = document.getElementById('btn-subir');
              var lbl = document.getElementById('nombre-archivo');
              if (this.files[0]) {
                lbl.textContent = this.files[0].name;
                btn.disabled = false;
                btn.style.opacity = '1';
              }
            });
          </script>

          <p id="nombre-archivo" style="font-size:.78rem;color:#6B7280;margin-top:6px;min-height:1em"></p>
          <p style="font-size:.73rem;color:#9CA3AF">JPG, PNG o WebP · Máx 2 MB</p>
        </form>

        <!-- Botón 3: Quitar foto (solo si hay avatar) -->
        <?php if (!empty($usuario['avatar'])): ?>
        <form method="POST" action="<?= BASE_URL ?>cuenta/quitarAvatar" style="margin-top:8px"
              onsubmit="return confirm('¿Quitar tu foto de perfil?')">
          <button type="submit"
                  style="padding:6px 14px;border:1px solid #FCA5A5;border-radius:8px;background:#FEF2F2;color:#DC2626;font-size:.82rem;font-weight:600;cursor:pointer">
            Quitar foto
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
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

  <?php if (($rol ?? '') === 'comprador'): ?>
  <!-- Dirección de entrega (solo compradores) -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px;margin-top:16px">
    <h2 style="font-size:.95rem;font-weight:700;margin-bottom:4px;color:#111827">Dirección de entrega</h2>
    <p style="font-size:.8rem;color:#6B7280;margin-bottom:16px">
      Se usará como dirección predeterminada al hacer pedidos con envío a domicilio.
    </p>
    <form method="POST" action="<?= BASE_URL ?>cuenta/guardarDireccion">
      <div style="margin-bottom:12px">
        <label class="form-label">Dirección completa</label>
        <textarea name="direccion_entrega" class="form-control" rows="2"
                  placeholder="Calle, número exterior, colonia, municipio, estado..."><?= htmlspecialchars($usuario['direccion_entrega'] ?? '') ?></textarea>
      </div>
      <div style="margin-bottom:16px">
        <label class="form-label">Referencia / número interior</label>
        <input type="text" name="referencia_entrega" class="form-control"
               placeholder="Ej: Depto 3B, edificio azul, portón negro..."
               value="<?= htmlspecialchars($usuario['referencia_entrega'] ?? '') ?>">
      </div>
      <?php if (!empty($usuario['lat_entrega']) && !empty($usuario['lng_entrega'])): ?>
      <div style="margin-bottom:12px;font-size:.8rem;color:#6B7280">
        Coordenadas guardadas: <?= number_format((float)$usuario['lat_entrega'],6) ?>, <?= number_format((float)$usuario['lng_entrega'],6) ?>
      </div>
      <?php endif; ?>
      <input type="hidden" name="lat_entrega" value="<?= htmlspecialchars($usuario['lat_entrega'] ?? '') ?>">
      <input type="hidden" name="lng_entrega" value="<?= htmlspecialchars($usuario['lng_entrega'] ?? '') ?>">
      <button type="submit" style="padding:9px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
        Guardar dirección
      </button>
    </form>
  </div>
  <?php endif; ?>

</div>
