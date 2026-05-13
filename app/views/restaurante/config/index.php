<?php ob_start(); ?>
<div style="max-width:760px">
  <?php if (!empty($flash)): ?>
  <div class="flash flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <div class="rst-card">
    <form method="POST" action="<?= BASE_URL ?>rest-config/guardar" enctype="multipart/form-data">

      <!-- Información general -->
      <div style="font-weight:700;font-size:.95rem;color:#111827;margin-bottom:16px;
                  display:flex;align-items:center;gap:8px">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Información general
      </div>

      <div class="form-group">
        <label class="form-label">Nombre del restaurante *</label>
        <input type="text" name="nombre" class="form-input"
               value="<?= htmlspecialchars($restaurante['nombre'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-textarea" rows="3"><?= htmlspecialchars($restaurante['descripcion'] ?? '') ?></textarea>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group">
          <label class="form-label">Teléfono</label>
          <input type="text" name="telefono" class="form-input"
                 value="<?= htmlspecialchars($restaurante['telefono'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Dirección</label>
          <input type="text" name="direccion" id="inpDireccion" class="form-input"
                 value="<?= htmlspecialchars($restaurante['direccion'] ?? '') ?>"
                 placeholder="Ej: Av. Principal 123, Ciudad">
        </div>
        <div class="form-group">
          <label class="form-label">Horario apertura</label>
          <input type="time" name="horario_apertura" class="form-input"
                 value="<?= htmlspecialchars($restaurante['horario_apertura'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Horario cierre</label>
          <input type="time" name="horario_cierre" class="form-input"
                 value="<?= htmlspecialchars($restaurante['horario_cierre'] ?? '') ?>">
        </div>
      </div>

      <!-- Mapa de ubicación -->
      <?php if (!empty($restaurante['direccion'])): ?>
      <div style="margin-bottom:20px">
        <label class="form-label">Ubicación en mapa</label>
        <div style="border-radius:10px;overflow:hidden;border:1px solid #E5E7EB">
          <iframe
            src="https://maps.google.com/maps?q=<?= urlencode($restaurante['direccion']) ?>&output=embed&z=15"
            width="100%" height="220" style="border:0;display:block" allowfullscreen loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
        <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px">
          El mapa se actualiza al guardar la dirección.
        </div>
      </div>
      <?php else: ?>
      <div style="background:#F9FAFB;border-radius:8px;padding:14px;margin-bottom:20px;
                  font-size:.82rem;color:#6B7280;display:flex;align-items:center;gap:8px">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Agrega una dirección para mostrar el mapa de ubicación.
      </div>
      <?php endif; ?>

      <!-- Branding -->
      <div style="border-top:1px solid #F3F4F6;padding-top:20px;margin-top:4px;margin-bottom:20px">
        <div style="font-weight:700;font-size:.95rem;color:#111827;margin-bottom:16px;
                    display:flex;align-items:center;gap:8px">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
          Branding
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;align-items:end">
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Color primario</label>
            <div style="display:flex;gap:8px;align-items:center;margin-top:4px">
              <input type="color" name="color_primario" id="cpicker"
                     value="<?= htmlspecialchars($restaurante['color_primario'] ?? '#C8102E') ?>"
                     style="height:40px;width:48px;border:1px solid #D1D5DB;border-radius:6px;padding:2px;cursor:pointer">
              <input type="text" id="txtColorPri"
                     value="<?= htmlspecialchars($restaurante['color_primario'] ?? '#C8102E') ?>"
                     class="form-input" style="flex:1">
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Color secundario</label>
            <div style="display:flex;gap:8px;align-items:center;margin-top:4px">
              <input type="color" name="color_secundario" id="spicker"
                     value="<?= htmlspecialchars($restaurante['color_secundario'] ?? '#1f2937') ?>"
                     style="height:40px;width:48px;border:1px solid #D1D5DB;border-radius:6px;padding:2px;cursor:pointer">
              <input type="text" id="txtColorSec"
                     value="<?= htmlspecialchars($restaurante['color_secundario'] ?? '#1f2937') ?>"
                     class="form-input" style="flex:1">
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Logo</label>
            <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.svg"
                   class="form-input" style="padding:6px">
            <?php if (!empty($restaurante['logo'])): ?>
            <img src="<?= BASE_URL . htmlspecialchars($restaurante['logo']) ?>"
                 style="height:36px;margin-top:6px;border-radius:4px;object-fit:contain;display:block">
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Modos de operación -->
      <div style="border-top:1px solid #F3F4F6;padding-top:20px;margin-bottom:20px">
        <div style="font-weight:700;font-size:.95rem;color:#111827;margin-bottom:6px;
                    display:flex;align-items:center;gap:8px">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          Modos de operación
        </div>
        <div style="font-size:.8rem;color:#6B7280;margin-bottom:14px">
          Adapta CarniHub a cómo opera tu sucursal: restaurante con mesas, taquería, take-away, etc.
        </div>

        <?php
          $r = $restaurante ?? [];
          $toggles = [
            ['mesas_habilitadas',       1, '🪑 Mesas habilitadas',       'Sucursal con mesas físicas. Desactiva para take-away o banqueta sin mesas.'],
            ['reservas_habilitadas',    1, '📅 Reservaciones',           'Permite que los comensales reserven mesa con anticipación.'],
            ['portero_habilitado',      1, '🛡️ Portero (verifica pago)','Un portero escanea el QR del comensal al salir para confirmar el pago.'],
            ['requiere_login_comensal', 0, '🔐 Login obligatorio',       'Exige Google login o nombre+teléfono antes de ordenar.'],
          ];
          foreach ($toggles as [$key, $def, $label, $desc]):
            $val = (int)($r[$key] ?? $def);
        ?>
        <label style="display:flex;align-items:center;gap:14px;padding:12px 14px;border:1px solid #E5E7EB;
                      border-radius:10px;margin-bottom:8px;cursor:pointer;background:#fff">
          <input type="checkbox" name="<?= $key ?>" value="1" <?= $val ? 'checked' : '' ?>
                 style="width:42px;height:24px;appearance:none;background:#D1D5DB;border-radius:12px;
                        position:relative;cursor:pointer;transition:.2s;flex-shrink:0"
                 onchange="this.style.background = this.checked ? 'var(--cp)' : '#D1D5DB'"
                 class="toggle-switch">
          <div style="flex:1">
            <div style="font-weight:600;color:#111827;font-size:.9rem"><?= $label ?></div>
            <div style="font-size:.78rem;color:#6B7280;margin-top:2px"><?= $desc ?></div>
          </div>
        </label>
        <?php endforeach; ?>

        <div class="form-group" style="margin-top:14px">
          <label class="form-label">💰 Propinas sugeridas (CSV de %)</label>
          <input type="text" name="propinas_sugeridas" class="form-input"
                 value="<?= htmlspecialchars($r['propinas_sugeridas'] ?? '0,10,15,20') ?>"
                 placeholder="0,10,15,20">
          <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px">
            Porcentajes mostrados al comensal en la pantalla de pago, separados por comas.
          </div>
        </div>
      </div>

      <!-- Nota footer -->
      <div style="background:#F9FAFB;border-radius:8px;padding:12px;font-size:.8rem;color:#6B7280;margin-bottom:20px">
        El footer del menú siempre mostrará: <strong>Potenciado por CarniHub</strong>
      </div>

      <style>
        .toggle-switch::after{content:'';position:absolute;top:2px;left:2px;width:20px;height:20px;
                              border-radius:50%;background:#fff;transition:.2s;
                              box-shadow:0 1px 3px rgba(0,0,0,.2)}
        .toggle-switch:checked::after{transform:translateX(18px)}
      </style>

      <div style="display:flex;justify-content:flex-end">
        <button type="submit" class="btn btn-primary">
          Guardar configuración
        </button>
      </div>
    </form>
  </div>

  <!-- QR del restaurante -->
  <div class="rst-card" style="margin-top:0">
    <div style="font-weight:700;font-size:.95rem;color:#111827;margin-bottom:16px;
                display:flex;align-items:center;gap:8px">
      <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
      QR del menú público
    </div>
    <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap">
      <div>
        <div id="qrcanvas"></div>
      </div>
      <div style="flex:1;min-width:220px">
        <div style="font-size:.85rem;color:#6B7280;margin-bottom:8px;line-height:1.5">
          Imprime este QR y colócalo en cada mesa o en la entrada del restaurante.<br>
          Los clientes lo escanean para ver el menú y ordenar directamente desde su celular.
        </div>
        <div style="background:#F9FAFB;border-radius:8px;padding:10px 12px;font-size:.8rem;
                    color:#374151;word-break:break-all;font-family:monospace;margin-bottom:12px">
          <?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug'] ?? '') ?>
        </div>
        <a href="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug'] ?? '') ?>"
           target="_blank" class="btn btn-outline btn-sm">
          Ver menú público ↗
        </a>
      </div>
    </div>
  </div>
</div>

<script>
// Sync color pickers con text inputs
document.getElementById('cpicker').addEventListener('input', function() {
  document.getElementById('txtColorPri').value = this.value;
});
document.getElementById('txtColorPri').addEventListener('input', function() {
  document.getElementById('cpicker').value = this.value;
});
document.getElementById('spicker').addEventListener('input', function() {
  document.getElementById('txtColorSec').value = this.value;
});
document.getElementById('txtColorSec').addEventListener('input', function() {
  document.getElementById('spicker').value = this.value;
});

// Generar QR con qrcode.js CDN
(function() {
  const script = document.createElement('script');
  script.src = 'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js';
  script.onload = function() {
    new QRCode(document.getElementById('qrcanvas'), {
      text: '<?= addslashes(BASE_URL . 'menu/' . ($restaurante['slug'] ?? '')) ?>',
      width: 160, height: 160,
      colorDark: '<?= addslashes($restaurante['color_secundario'] ?? '#1f2937') ?>',
      colorLight: '#ffffff',
    });
  };
  document.head.appendChild(script);
})();
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';

