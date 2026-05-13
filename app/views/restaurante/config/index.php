<?php ob_start(); ?>
<div>
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
      </div>

      <!-- Horarios por día de la semana -->
      <div style="border-top:1px solid #F3F4F6;padding-top:20px;margin-bottom:20px">
        <div style="font-weight:700;font-size:.95rem;color:#111827;margin-bottom:6px;
                    display:flex;align-items:center;gap:8px">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Horarios de atención
        </div>
        <div style="font-size:.8rem;color:#6B7280;margin-bottom:14px">
          Selecciona los días que abres y define el horario. Los comensales no podrán ordenar fuera de este horario.
        </div>

        <?php
          $diasKeys = ['lun','mar','mie','jue','vie','sab','dom'];
          $diasNom  = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
          $horariosJson = !empty($restaurante['horarios_json'])
            ? json_decode($restaurante['horarios_json'], true)
            : [];
          // Default: fill from old fields or 9:00-22:00
          $defaultAbre  = substr($restaurante['horario_apertura'] ?? '09:00', 0, 5);
          $defaultCierra = substr($restaurante['horario_cierre']  ?? '22:00', 0, 5);
          foreach ($diasKeys as $d) {
            if (!isset($horariosJson[$d])) {
              $horariosJson[$d] = ['abre' => $defaultAbre, 'cierra' => $defaultCierra, 'cerrado' => 0];
            }
          }
        ?>

        <!-- Day chips -->
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px" id="dayChips">
          <?php foreach ($diasKeys as $i => $d): ?>
          <?php $cerrado = (int)($horariosJson[$d]['cerrado'] ?? 0); ?>
          <button type="button"
                  data-dia="<?= $d ?>"
                  onclick="toggleDia('<?= $d ?>', this)"
                  style="padding:8px 16px;border-radius:99px;font-size:.85rem;font-weight:600;cursor:pointer;
                         border:2px solid <?= !$cerrado ? 'var(--cp)' : '#D1D5DB' ?>;
                         background:<?= !$cerrado ? 'var(--cp)' : '#fff' ?>;
                         color:<?= !$cerrado ? '#fff' : '#6B7280' ?>;transition:.15s">
            <?= $diasNom[$i] ?>
          </button>
          <?php endforeach; ?>
        </div>

        <!-- Time rows -->
        <div id="horarioRows" style="display:grid;gap:8px">
          <?php foreach ($diasKeys as $i => $d): ?>
          <?php
            $h = $horariosJson[$d];
            $cerrado = (int)($h['cerrado'] ?? 0);
          ?>
          <div id="row_<?= $d ?>" style="<?= $cerrado ? 'display:none' : 'display:flex' ?>;align-items:center;gap:12px;
               background:#F9FAFB;border-radius:10px;padding:10px 14px;flex-wrap:wrap">
            <span style="font-weight:600;font-size:.88rem;color:#374151;width:80px"><?= $diasNom[$i] ?></span>
            <div style="display:flex;align-items:center;gap:8px;flex:1">
              <label style="font-size:.8rem;color:#6B7280">Abre</label>
              <input type="time" id="abre_<?= $d ?>" value="<?= htmlspecialchars($h['abre']) ?>"
                     class="form-input" style="max-width:130px;padding:6px 10px"
                     onchange="actualizarHorariosJson()">
              <label style="font-size:.8rem;color:#6B7280">Cierra</label>
              <input type="time" id="cierra_<?= $d ?>" value="<?= htmlspecialchars($h['cierra']) ?>"
                     class="form-input" style="max-width:130px;padding:6px 10px"
                     onchange="actualizarHorariosJson()">
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <input type="hidden" name="horarios_json" id="horariosJson"
               value="<?= htmlspecialchars(json_encode($horariosJson)) ?>">
        <!-- Keep legacy columns for backwards compat -->
        <input type="hidden" name="horario_apertura" id="legacyAbre" value="<?= htmlspecialchars($defaultAbre) ?>">
        <input type="hidden" name="horario_cierre" id="legacyCierra" value="<?= htmlspecialchars($defaultCierra) ?>">
      </div>

      <!-- Mapa de ubicación (Leaflet/OpenStreetMap, sin API key) -->
      <?php if (!empty($restaurante['direccion'])): ?>
      <div style="margin-bottom:20px">
        <label class="form-label">Ubicación en mapa</label>
        <div id="rstMap"
             data-direccion="<?= htmlspecialchars($restaurante['direccion'], ENT_QUOTES) ?>"
             style="border-radius:10px;overflow:hidden;border:1px solid #E5E7EB;height:240px;background:#F3F4F6"></div>
        <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px">
          Mapa abierto vía OpenStreetMap. Se actualiza al guardar la dirección.
        </div>
      </div>
      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
      <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
      <script>
      (function(){
        const el = document.getElementById('rstMap'); if (!el) return;
        const dir = el.dataset.direccion;
        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(dir))
          .then(r => r.json())
          .then(data => {
            const lat = data[0] ? parseFloat(data[0].lat) : 19.4326;
            const lng = data[0] ? parseFloat(data[0].lon) : -99.1332;
            const map = L.map(el).setView([lat, lng], data[0] ? 16 : 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              attribution: '© OpenStreetMap', maxZoom: 19
            }).addTo(map);
            if (data[0]) L.marker([lat, lng]).addTo(map).bindPopup(dir).openPopup();
          })
          .catch(() => { el.innerHTML = '<div style="padding:20px;text-align:center;color:#9CA3AF">No se pudo cargar el mapa.</div>'; });
      })();
      </script>
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
        <label class="rst-toggle-row <?= $val ? 'is-on' : '' ?>">
          <span class="rst-toggle">
            <input type="checkbox" name="<?= $key ?>" value="1" <?= $val ? 'checked' : '' ?>
                   onchange="this.closest('.rst-toggle-row').classList.toggle('is-on', this.checked)">
            <span class="rst-toggle-track"></span>
          </span>
          <div style="flex:1">
            <div style="font-weight:600;color:#111827;font-size:.92rem"><?= $label ?></div>
            <div style="font-size:.78rem;color:#6B7280;margin-top:2px"><?= $desc ?></div>
          </div>
          <span class="badge rst-toggle-badge <?= $val ? 'badge-green' : 'badge-gray' ?>">
            <?= $val ? 'Activo' : 'Apagado' ?>
          </span>
        </label>
        <?php endforeach; ?>

        <script>
        document.querySelectorAll('.rst-toggle-row input[type="checkbox"]').forEach(chk => {
          chk.addEventListener('change', () => {
            const badge = chk.closest('.rst-toggle-row').querySelector('.rst-toggle-badge');
            badge.textContent = chk.checked ? 'Activo' : 'Apagado';
            badge.className = 'badge rst-toggle-badge ' + (chk.checked ? 'badge-green' : 'badge-gray');
          });
        });
        </script>

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
// ── Horarios ────────────────────────────────────────────────────────────────
const DIAS = ['lun','mar','mie','jue','vie','sab','dom'];

function toggleDia(dia, btn) {
  const row = document.getElementById('row_' + dia);
  const abierto = row.style.display !== 'none' && row.style.display !== '';
  if (abierto) {
    row.style.display = 'none';
    btn.style.background = '#fff';
    btn.style.color = '#6B7280';
    btn.style.borderColor = '#D1D5DB';
  } else {
    row.style.display = 'flex';
    btn.style.background = 'var(--cp)';
    btn.style.color = '#fff';
    btn.style.borderColor = 'var(--cp)';
  }
  actualizarHorariosJson();
}

function actualizarHorariosJson() {
  const data = {};
  DIAS.forEach(d => {
    const row = document.getElementById('row_' + d);
    const cerrado = !row || row.style.display === 'none' ? 1 : 0;
    const abre   = document.getElementById('abre_'  + d)?.value || '09:00';
    const cierra = document.getElementById('cierra_' + d)?.value || '22:00';
    data[d] = { abre, cierra, cerrado };
  });
  document.getElementById('horariosJson').value = JSON.stringify(data);
  // Update legacy fallback fields with Mon hours
  const mon = data['lun'];
  document.getElementById('legacyAbre').value  = mon?.cerrado ? '' : mon?.abre;
  document.getElementById('legacyCierra').value = mon?.cerrado ? '' : mon?.cierra;
}

// ── Color pickers ────────────────────────────────────────────────────────────
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
  script.src = 'https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js';
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

