<?php
$usuario   = $usuario ?? $_SESSION['usuario'];
$rol       = $_SESSION['usuario']['rol_slug'] ?? '';
$iniciales = strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1) . mb_substr($usuario['apellido_paterno'] ?? '', 0, 1));
$rolLabels = [
    'admin_empresa' => ['Admin de empresa', '#C8102E', '#FEE2E2'],
    'supervisor'    => ['Supervisor',        '#2563EB', '#DBEAFE'],
    'comprador'     => ['Comprador',         '#059669', '#D1FAE5'],
];
[$rolLabel, $rolColor, $rolBg] = $rolLabels[$rol] ?? ['Usuario', '#6B7280', '#F3F4F6'];
?>
<style>
.perfil-card {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #E5E7EB;
  padding: 26px;
  box-shadow: 0 1px 4px rgba(0,0,0,.03);
  margin-bottom: 18px;
}
.perfil-card-title {
  font-size: .9rem;
  font-weight: 800;
  color: #111827;
  margin-bottom: 20px;
  padding-bottom: 14px;
  border-bottom: 1px solid #F3F4F6;
  display: flex;
  align-items: center;
  gap: 9px;
}
.perfil-card-title-icon {
  width: 30px; height: 30px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.perfil-input {
  width: 100%;
  padding: 10px 13px;
  border: 1.5px solid #E5E7EB;
  border-radius: 9px;
  font-size: .875rem;
  font-family: 'Inter', sans-serif;
  color: #111827;
  background: #fff;
  outline: none;
  transition: border-color .2s, box-shadow .2s;
  box-sizing: border-box;
}
.perfil-input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(200,16,46,.09); }
.perfil-input::placeholder { color: #BFC4CE; }
.perfil-input[readonly] { background: #F9FAFB; color: #6B7280; cursor: not-allowed; }
.perfil-label { display: block; font-size: .8rem; font-weight: 700; color: #374151; margin-bottom: 6px; }
.perfil-save-btn {
  padding: 10px 22px;
  background: linear-gradient(135deg, var(--color-primary), #A00D24);
  color: #fff;
  border: none;
  border-radius: 9px;
  font-weight: 700;
  font-size: .875rem;
  font-family: 'Inter', sans-serif;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: transform .15s, box-shadow .15s;
}
.perfil-save-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(200,16,46,.3); }
.perfil-save-btn:active { transform: translateY(0); }
.perfil-sec-btn {
  padding: 10px 22px;
  background: #111827;
  color: #fff;
  border: none;
  border-radius: 9px;
  font-weight: 700;
  font-size: .875rem;
  font-family: 'Inter', sans-serif;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: background .15s;
}
.perfil-sec-btn:hover { background: #1F2937; }
</style>

<div style="display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:start">

  <!-- ── Columna izquierda: avatar + datos personales ── -->
  <div>

    <!-- Avatar card -->
    <div class="perfil-card">
      <div class="perfil-card-title">
        <div class="perfil-card-title-icon" style="background:#FEF2F2">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#C8102E" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A8.966 8.966 0 0112 15c2.485 0 4.745.99 6.379 2.596M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        Foto de perfil
      </div>

      <div style="display:flex;flex-direction:column;align-items:center;margin-bottom:20px">
        <div id="avatar-preview" style="margin-bottom:14px">
          <?php if (!empty($usuario['avatar'])): ?>
            <img src="<?= htmlspecialchars($usuario['avatar']) ?>" alt="Avatar"
                 style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid #FECACA;box-shadow:0 4px 16px rgba(200,16,46,.18)">
          <?php else: ?>
            <div style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,#FEE2E2,#FEF2F2);border:3px solid #FECACA;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.8rem;color:var(--color-primary);box-shadow:0 4px 16px rgba(200,16,46,.15)">
              <?= htmlspecialchars($iniciales) ?>
            </div>
          <?php endif; ?>
        </div>

        <div style="text-align:center">
          <div style="font-weight:800;color:#111827;font-size:1rem"><?= htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido_paterno'] ?? '')) ?></div>
          <span style="display:inline-block;margin-top:5px;padding:3px 10px;border-radius:999px;background:<?= $rolBg ?>;color:<?= $rolColor ?>;font-size:.72rem;font-weight:700"><?= $rolLabel ?></span>
          <?php if (!empty($usuario['email'])): ?>
          <div style="margin-top:7px;font-size:.78rem;color:#9CA3AF"><?= htmlspecialchars($usuario['email']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <form id="form-avatar" method="POST" action="<?= BASE_URL ?>cuenta/subirAvatar" enctype="multipart/form-data">
        <input type="file" id="avatar_input" name="avatar" accept=".jpg,.jpeg,.png,.webp" style="display:none">
        <div style="display:flex;flex-direction:column;gap:8px">
          <button type="button" onclick="document.getElementById('avatar_input').click()"
                  style="width:100%;padding:9px;border:1.5px dashed #E5E7EB;border-radius:9px;background:#F9FAFB;font-size:.84rem;font-weight:600;color:#374151;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:background .15s" onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#F9FAFB'">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Seleccionar foto
          </button>
          <button type="submit" id="btn-subir" disabled
                  style="width:100%;padding:9px;border:none;border-radius:9px;background:var(--color-primary);color:#fff;font-size:.84rem;font-weight:700;cursor:pointer;opacity:.5;font-family:'Inter',sans-serif;transition:opacity .15s">
            Subir foto
          </button>
        </div>
        <p id="nombre-archivo" style="font-size:.75rem;color:#6B7280;margin-top:6px;min-height:1em;text-align:center"></p>
        <p style="font-size:.7rem;color:#9CA3AF;text-align:center">JPG, PNG o WebP · Máx 2 MB</p>
      </form>

      <?php if (!empty($usuario['avatar'])): ?>
      <form method="POST" action="<?= BASE_URL ?>cuenta/quitarAvatar" style="margin-top:6px"
            onsubmit="return confirm('¿Quitar tu foto de perfil?')">
        <button type="submit"
                style="width:100%;padding:8px;border:1px solid #FECACA;border-radius:8px;background:#FEF2F2;color:#DC2626;font-size:.8rem;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif">
          Quitar foto actual
        </button>
      </form>
      <?php endif; ?>

      <script>
        document.getElementById('avatar_input').addEventListener('change', function() {
          const btn = document.getElementById('btn-subir');
          const lbl = document.getElementById('nombre-archivo');
          if (this.files[0]) {
            lbl.textContent = this.files[0].name;
            btn.disabled = false;
            btn.style.opacity = '1';
          }
        });
      </script>
    </div>

  </div>

  <!-- ── Columna derecha: formularios ── -->
  <div>

    <!-- Información personal -->
    <div class="perfil-card">
      <div class="perfil-card-title">
        <div class="perfil-card-title-icon" style="background:#EFF6FF">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#2563EB" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        Información personal
      </div>

      <form method="POST" action="<?= BASE_URL ?>cuenta/guardar">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div>
            <label class="perfil-label">Nombre</label>
            <input type="text" name="nombre" class="perfil-input" value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>
          </div>
          <div>
            <label class="perfil-label">Apellido paterno</label>
            <input type="text" name="apellido_paterno" class="perfil-input" value="<?= htmlspecialchars($usuario['apellido_paterno'] ?? '') ?>" required>
          </div>
        </div>
        <div style="margin-bottom:14px">
          <label class="perfil-label">
            Correo electrónico
            <span style="font-size:.72rem;color:#9CA3AF;font-weight:400">(no editable)</span>
          </label>
          <input type="email" class="perfil-input" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" readonly>
        </div>
        <div style="margin-bottom:20px">
          <label class="perfil-label">Teléfono</label>
          <input type="text" name="telefono" class="perfil-input" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" placeholder="10 dígitos">
        </div>
        <button type="submit" class="perfil-save-btn">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          Guardar cambios
        </button>
      </form>
    </div>

    <!-- Cambiar contraseña -->
    <div class="perfil-card">
      <div class="perfil-card-title">
        <div class="perfil-card-title-icon" style="background:#F3F4F6">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#374151" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 018 0v4"/></svg>
        </div>
        Cambiar contraseña
      </div>

      <form method="POST" action="<?= BASE_URL ?>cuenta/cambiarPassword">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div style="grid-column:1/-1">
            <label class="perfil-label">Contraseña actual</label>
            <input type="password" name="password_actual" class="perfil-input" required>
          </div>
          <div>
            <label class="perfil-label">Nueva contraseña</label>
            <input type="password" name="password_nuevo" class="perfil-input" minlength="8" required placeholder="Mínimo 8 caracteres">
          </div>
          <div>
            <label class="perfil-label">Confirmar contraseña</label>
            <input type="password" name="password_confirm" class="perfil-input" minlength="8" required placeholder="Repite la nueva contraseña">
          </div>
        </div>
        <button type="submit" class="perfil-sec-btn">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
          Cambiar contraseña
        </button>
      </form>
    </div>

    <?php if ($rol === 'comprador'): ?>
    <!-- Dirección de entrega -->
    <div class="perfil-card">
      <div class="perfil-card-title">
        <div class="perfil-card-title-icon" style="background:#ECFDF5">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        Dirección de entrega
      </div>

      <p style="font-size:.82rem;color:#6B7280;margin-bottom:16px;line-height:1.5">
        Se usará como dirección predeterminada al hacer pedidos con envío a domicilio.
      </p>
      <form method="POST" action="<?= BASE_URL ?>cuenta/guardarDireccion">
        <div style="margin-bottom:14px">
          <label class="perfil-label">Dirección completa</label>
          <textarea name="direccion_entrega" class="perfil-input" rows="2" style="height:auto;resize:vertical"
                    placeholder="Calle, número exterior, colonia, municipio, estado..."><?= htmlspecialchars($usuario['direccion_entrega'] ?? '') ?></textarea>
        </div>
        <div style="margin-bottom:20px">
          <label class="perfil-label">Referencia / número interior</label>
          <input type="text" name="referencia_entrega" class="perfil-input"
                 placeholder="Ej: Depto 3B, edificio azul, portón negro..."
                 value="<?= htmlspecialchars($usuario['referencia_entrega'] ?? '') ?>">
        </div>
        <?php if (!empty($usuario['lat_entrega']) && !empty($usuario['lng_entrega'])): ?>
        <div style="margin-bottom:14px;padding:9px 13px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;font-size:.78rem;color:#065F46;display:flex;align-items:center;gap:7px">
          <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Coordenadas guardadas: <?= number_format((float)$usuario['lat_entrega'],6) ?>, <?= number_format((float)$usuario['lng_entrega'],6) ?>
        </div>
        <?php endif; ?>
        <input type="hidden" name="lat_entrega" value="<?= htmlspecialchars($usuario['lat_entrega'] ?? '') ?>">
        <input type="hidden" name="lng_entrega" value="<?= htmlspecialchars($usuario['lng_entrega'] ?? '') ?>">
        <button type="submit" class="perfil-save-btn">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          Guardar dirección
        </button>
      </form>
    </div>
    <?php endif; ?>

  </div>

</div>
