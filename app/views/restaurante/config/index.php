<?php ob_start(); ?>
<div>
  <div class="rst-card">
    <form method="POST" action="<?= BASE_URL ?>rest-config/guardar" enctype="multipart/form-data" accept-charset="UTF-8">

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
        <textarea name="descripcion" class="form-textarea" rows="3"><?= htmlspecialchars($restaurante['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-input"
               value="<?= htmlspecialchars($restaurante['telefono'] ?? '') ?>">
      </div>

      <!-- Dirección + Mapa lado a lado -->
      <div style="display:grid;grid-template-columns:280px 1fr;gap:16px;margin-bottom:20px;align-items:start">
        <div>
          <label class="form-label">Dirección</label>
          <div style="position:relative">
            <input type="text" name="direccion" id="inpDireccion" class="form-input"
                   value="<?= htmlspecialchars($restaurante['direccion'] ?? '') ?>"
                   placeholder="Ej: Av. Principal 123, Ciudad"
                   autocomplete="off"
                   style="margin-bottom:0">
            <div id="addrSugg" style="display:none;position:absolute;top:calc(100% + 2px);left:0;right:0;
                 z-index:300;background:#fff;border:1.5px solid #E5E7EB;border-radius:10px;
                 box-shadow:0 6px 24px rgba(0,0,0,.1);max-height:220px;overflow-y:auto"></div>
          </div>
          <div style="margin-top:10px"></div>
          <div id="coordsBox" style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:10px 12px;<?= empty($restaurante['direccion']) ? 'display:none' : '' ?>">
            <div style="font-size:.75rem;font-weight:700;color:#065F46;margin-bottom:6px">📍 Coordenadas</div>
            <div style="font-size:.78rem;color:#374151">Lat: <span id="coordLat"><?= $restaurante['lat'] ?? '—' ?></span></div>
            <div style="font-size:.78rem;color:#374151">Lng: <span id="coordLng"><?= $restaurante['lng'] ?? '—' ?></span></div>
            <input type="hidden" name="lat" id="inpLat" value="<?= htmlspecialchars($restaurante['lat'] ?? '') ?>">
            <input type="hidden" name="lng" id="inpLng" value="<?= htmlspecialchars($restaurante['lng'] ?? '') ?>">
          </div>
          <div id="mapNote" style="font-size:.72rem;color:#9CA3AF;margin-top:6px">
            Guarda para actualizar el mapa.
          </div>
        </div>
        <div>
          <label class="form-label">Ubicación en mapa</label>
          <div id="rstMap"
               data-direccion="<?= htmlspecialchars($restaurante['direccion'] ?? '', ENT_QUOTES) ?>"
               style="border-radius:10px;overflow:hidden;border:1px solid #E5E7EB;height:200px;background:#F3F4F6;display:flex;align-items:center;justify-content:center">
            <?php if (empty($restaurante['direccion'])): ?>
            <div style="text-align:center;color:#9CA3AF;font-size:.82rem">
              <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 6px;display:block"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              Agrega una dirección para ver el mapa
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php if (!empty($restaurante['direccion'])): ?>
      <?php if (!empty($mapsApiKey)): ?>
      <!-- Nominatim geocoding + Google Maps display -->
      <script>
      window._mapCoords = null;

      function initMap() {
        // Called when Google Maps SDK loads — render if Nominatim already resolved
        if (window._mapCoords) _renderGoogleMap(window._mapCoords.lat, window._mapCoords.lng);
      }

      function _renderGoogleMap(lat, lng) {
        var el = document.getElementById('rstMap');
        var dir = el.dataset.direccion;
        el.innerHTML = '';
        var map = new google.maps.Map(el, { center:{lat:lat,lng:lng}, zoom:16 });
        new google.maps.Marker({ position:{lat:lat,lng:lng}, map:map, title:dir });
      }

      (function(){
        var el  = document.getElementById('rstMap');
        var dir = el.dataset.direccion;
        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(dir))
          .then(function(r){ return r.json(); })
          .then(function(data) {
            if (data[0]) {
              var lat = parseFloat(data[0].lat), lng = parseFloat(data[0].lon);
              document.getElementById('coordLat').textContent = lat.toFixed(6);
              document.getElementById('coordLng').textContent = lng.toFixed(6);
              document.getElementById('inpLat').value = lat.toFixed(6);
              document.getElementById('inpLng').value = lng.toFixed(6);
              document.getElementById('coordsBox').style.display = 'block';
              window._mapCoords = {lat:lat, lng:lng};
              if (window.google && window.google.maps) _renderGoogleMap(lat, lng);
            } else {
              el.innerHTML = '<div style="padding:20px;text-align:center;color:#9CA3AF;font-size:.82rem">No se encontró la dirección en el mapa.</div>';
            }
          })
          .catch(function(){ el.innerHTML = '<div style="padding:20px;text-align:center;color:#9CA3AF;font-size:.82rem">No se pudo cargar el mapa.</div>'; });
      })();
      </script>
      <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($mapsApiKey) ?>&callback=initMap" async defer></script>
      <?php else: ?>
      <!-- Nominatim + Leaflet (sin API key) -->
      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
      <script>
      (function(){
        var sc = document.createElement('script');
        sc.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        sc.onload = function() {
          var markerIcon = L.divIcon({
            className: 'rst-map-pin',
            html: '<div style="width:22px;height:22px;border-radius:50% 50% 50% 0;background:var(--cp);transform:rotate(-45deg);box-shadow:0 2px 8px rgba(0,0,0,.25);border:2px solid #fff;position:relative"><div style="position:absolute;inset:6px;background:#fff;border-radius:50%"></div></div>',
            iconSize: [22, 22],
            iconAnchor: [11, 22],
            popupAnchor: [0, -18]
          });
          var el  = document.getElementById('rstMap');
          var dir = el.dataset.direccion;
          el.innerHTML = '';
          fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(dir))
            .then(function(r){ return r.json(); })
            .then(function(data) {
              if (data[0]) {
                var lat = parseFloat(data[0].lat), lng = parseFloat(data[0].lon);
                var map = L.map(el).setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                  { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(map);
                L.marker([lat, lng], { icon: markerIcon }).addTo(map).bindPopup(dir).openPopup();
                document.getElementById('coordLat').textContent = lat.toFixed(6);
                document.getElementById('coordLng').textContent = lng.toFixed(6);
                document.getElementById('inpLat').value = lat.toFixed(6);
                document.getElementById('inpLng').value = lng.toFixed(6);
                document.getElementById('coordsBox').style.display = 'block';
              } else {
                el.innerHTML = '<div style="padding:20px;text-align:center;color:#9CA3AF;font-size:.82rem">No se encontró la dirección en el mapa.</div>';
              }
            })
            .catch(function(){ el.innerHTML = '<div style="padding:20px;text-align:center;color:#9CA3AF;font-size:.82rem">No se pudo cargar el mapa.</div>'; });
        };
        document.head.appendChild(sc);
      })();
      </script>
      <?php endif; ?>
      <?php endif; ?>

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
          <?php
          function horaSelect(string $id, string $val): string {
            $h = "<select id=\"$id\" onchange=\"actualizarHorariosJson()\"
                style=\"padding:7px 10px;border:1.5px solid #D1D5DB;border-radius:8px;
                        background:#fff;color:#374151;font-size:.85rem;cursor:pointer;
                        appearance:none;-webkit-appearance:none;min-width:110px\">";
            for ($hh = 0; $hh < 24; $hh++) {
              foreach ([0, 30] as $mm) {
                $v    = sprintf('%02d:%02d', $hh, $mm);
                $ampm = $hh < 12 ? 'AM' : 'PM';
                $h12  = $hh % 12 ?: 12;
                $lbl  = sprintf('%d:%02d %s', $h12, $mm, $ampm);
                $sel  = $v === $val ? ' selected' : '';
                $h   .= "<option value=\"$v\"$sel>$lbl</option>";
              }
            }
            return $h . '</select>';
          }
          ?>
          <?php foreach ($diasKeys as $i => $d): ?>
          <?php $h = $horariosJson[$d]; $cerrado = (int)($h['cerrado'] ?? 0); ?>
          <div id="row_<?= $d ?>" style="<?= $cerrado ? 'display:none' : 'display:flex' ?>;align-items:center;gap:12px;
               background:#F9FAFB;border-radius:10px;padding:10px 14px;flex-wrap:wrap">
            <span style="font-weight:600;font-size:.88rem;color:#374151;width:80px"><?= $diasNom[$i] ?></span>
            <div style="display:flex;align-items:center;gap:10px;flex:1;flex-wrap:wrap">
              <label style="font-size:.78rem;color:#6B7280;font-weight:500">Abre</label>
              <?= horaSelect('abre_' . $d, $h['abre'] ?? '09:00') ?>
              <label style="font-size:.78rem;color:#6B7280;font-weight:500">Cierra</label>
              <?= horaSelect('cierra_' . $d, $h['cierra'] ?? '22:00') ?>
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
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Foto de portada <span style="font-weight:400;color:#9CA3AF">(banner del menú público)</span></label>
            <input type="file" name="imagen_banner" accept=".jpg,.jpeg,.png,.webp"
                   class="form-input" style="padding:6px">
            <?php if (!empty($restaurante['imagen_banner'])): ?>
            <img src="<?= BASE_URL . htmlspecialchars($restaurante['imagen_banner']) ?>"
                 style="width:100%;max-height:120px;margin-top:8px;border-radius:8px;object-fit:cover;display:block">
            <?php endif; ?>
            <div style="font-size:.72rem;color:#9CA3AF;margin-top:4px">Se muestra como fondo del encabezado en la vista del cliente. Recomendado: 1200×400 px.</div>
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

      <!-- ══════════════════════════════════════════════════════════
           SECCIÓN: PAGOS DE COMENSALES
           ═════════════════════════════════════════════════════════ -->
      <hr style="border:none;border-top:1.5px solid #F3F4F6;margin:24px 0">
      <div style="font-weight:700;font-size:.95rem;color:#111827;margin-bottom:16px;
                  display:flex;align-items:center;gap:8px">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        Métodos de pago para comensales
      </div>

      <?php
        $metodosConfig = json_decode($cfgPagos['metodos_pago_habilitados'] ?? '["efectivo","tarjeta","transferencia","paypal"]', true) ?: ['efectivo','tarjeta','transferencia','paypal'];
        $metodosOpts = [
          'efectivo'       => '💵 Efectivo',
          'tarjeta'        => '💳 Tarjeta (Stripe)',
          'transferencia'  => '📲 Transferencia',
          'paypal'         => '🅿️ PayPal',
        ];
      ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px">
        <?php foreach ($metodosOpts as $val => $label): ?>
        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;
                      border:1.5px solid #E5E7EB;border-radius:10px;cursor:pointer;
                      background:#F9FAFB;font-size:.88rem;font-weight:500">
          <input type="checkbox"
                 name="metodos_pago_habilitados[]"
                 value="<?= $val ?>"
                 <?= in_array($val, $metodosConfig) ? 'checked' : '' ?>
                 style="width:16px;height:16px;accent-color:#C8102E">
          <?= $label ?>
        </label>
        <?php endforeach; ?>
      </div>
      <div style="font-size:.75rem;color:#9CA3AF;margin-bottom:20px">
        Solo los métodos marcados aparecerán al comensal en la pantalla de pago.
        Al menos uno debe estar habilitado.
      </div>

      <!-- Stripe keys -->
      <div style="background:#F0F9FF;border:1.5px solid #BAE6FD;border-radius:12px;
                  padding:16px 18px;margin-bottom:16px">
        <div style="font-weight:600;color:#0C4A6E;font-size:.9rem;margin-bottom:12px">
          🔑 Credenciales Stripe <span style="font-size:.75rem;font-weight:400;color:#0369A1">(para pagos con tarjeta)</span>
        </div>
        <div class="form-group">
          <label class="form-label" style="font-size:.8rem">Publishable Key (pk_...)</label>
          <input type="text" name="stripe_public_key" class="form-input"
                 value="<?= htmlspecialchars($cfgPagos['stripe_public_key'] ?? '') ?>"
                 placeholder="pk_live_... o pk_test_..."
                 style="font-family:monospace;font-size:.8rem">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" style="font-size:.8rem">Secret Key (sk_...)</label>
          <input type="password" name="stripe_secret_key" class="form-input"
                 value="<?= empty($cfgPagos['stripe_secret_key'] ?? '') ? '' : '••••••••••••' ?>"
                 placeholder="sk_live_... o sk_test_... (déjalo vacío para no cambiarla)"
                 style="font-family:monospace;font-size:.8rem"
                 autocomplete="new-password">
          <div style="font-size:.72rem;color:#9CA3AF;margin-top:4px">
            ⚠️ La Secret Key solo se muestra enmascarada. Escribe una nueva solo si deseas cambiarla.
          </div>
        </div>
      </div>

      <!-- Notificación email -->
      <div style="background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:12px;
                  padding:14px 16px;margin-bottom:20px">
        <label style="display:flex;align-items:center;gap:12px;cursor:pointer;margin-bottom:10px">
          <input type="checkbox" name="notif_email_pago" value="1"
                 <?= ($cfgPagos['notif_email_pago'] ?? '0') === '1' ? 'checked' : '' ?>
                 id="chkNotifEmail"
                 onchange="document.getElementById('rowEmailDestino').style.display=this.checked?'block':'none'"
                 style="width:16px;height:16px;accent-color:#C8102E">
          <div>
            <div style="font-weight:600;font-size:.88rem;color:#111827">📧 Recibir email cuando un comensal pague</div>
            <div style="font-size:.75rem;color:#6B7280">Recibirás un resumen del pago al correo configurado abajo.</div>
          </div>
        </label>
        <div id="rowEmailDestino" style="display:<?= ($cfgPagos['notif_email_pago'] ?? '0') === '1' ? 'block' : 'none' ?>">
          <label class="form-label" style="font-size:.8rem">Email destino</label>
          <input type="email" name="notif_email_pago_destino" class="form-input"
                 value="<?= htmlspecialchars($cfgPagos['notif_email_pago_destino'] ?? '') ?>"
                 placeholder="admin@mirestaurante.com">
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════
           SECCIÓN: CARNIHUB — PAGO DE PEDIDOS A PROVEEDOR
           ═════════════════════════════════════════════════════════ -->
      <?php if (!empty($cfgCarniHub)): ?>
      <hr style="border:none;border-top:1.5px solid #F3F4F6;margin:24px 0">
      <div style="font-weight:700;font-size:.95rem;color:#111827;margin-bottom:16px;
                  display:flex;align-items:center;gap:8px">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
        CarniHub — Pago de pedidos a proveedor
      </div>
      <div style="background:#FFF7ED;border:1.5px solid #FED7AA;border-radius:12px;
                  padding:14px 16px;margin-bottom:14px;font-size:.8rem;color:#92400E">
        Cuando envíes un pedido de insumos a CarniHub, el sistema usará el método configurado aquí
        para procesar el cobro automáticamente.
      </div>

      <div class="form-group">
        <label class="form-label">Método de pago al proveedor</label>
        <select name="ch_metodo_pago" class="form-input"
                onchange="document.getElementById('chTransfPanel').style.display=this.value==='transferencia'?'block':'none'">
          <?php foreach (['stripe'=>'💳 Cargo automático con Stripe','paypal'=>'🅿️ PayPal','transferencia'=>'📲 Transferencia bancaria'] as $v => $lbl): ?>
          <option value="<?= $v ?>" <?= ($cfgCarniHub['metodo_pago'] ?? 'transferencia') === $v ? 'selected' : '' ?>>
            <?= $lbl ?>
          </option>
          <?php endforeach; ?>
        </select>
        <div style="font-size:.74rem;color:#9CA3AF;margin-top:4px">
          Stripe y PayPal usan las mismas credenciales configuradas arriba en "Métodos de pago para comensales".
        </div>
      </div>

      <div id="chTransfPanel"
           style="display:<?= ($cfgCarniHub['metodo_pago'] ?? 'transferencia') === 'transferencia' ? 'block' : 'none' ?>;
                  background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:10px;padding:14px;margin-bottom:16px">
        <label class="form-label" style="font-size:.8rem">
          Instrucciones de transferencia (proporcionadas por CarniHub)
        </label>
        <textarea name="ch_instrucciones_transferencia" class="form-textarea" rows="4"
                  placeholder="Banco: BBVA&#10;CLABE: 012345678901234567&#10;Beneficiario: CarniHub S.A."><?= htmlspecialchars($cfgCarniHub['instrucciones_transferencia'] ?? '') ?></textarea>
        <div style="font-size:.73rem;color:#9CA3AF;margin-top:4px">
          Estas instrucciones se mostrarán al administrador al enviar un pedido de insumos.
        </div>
      </div>
      <?php endif; ?>

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

// ── Address autocomplete con Nominatim ──────────────────────────────────────
(function() {
  const inp  = document.getElementById('inpDireccion');
  const sugg = document.getElementById('addrSugg');
  if (!inp || !sugg) return;
  let timer;
  inp.addEventListener('input', function() {
    clearTimeout(timer);
    const q = this.value.trim();
    if (q.length < 4) { sugg.style.display = 'none'; return; }
    timer = setTimeout(function() {
      fetch('https://nominatim.openstreetmap.org/search?format=json&limit=6&addressdetails=0&q=' + encodeURIComponent(q))
        .then(function(r){ return r.json(); })
        .then(function(data) {
          if (!data || !data.length) { sugg.style.display = 'none'; return; }
          sugg.innerHTML = data.map(function(item) {
            var name = item.display_name.replace(/</g,'&lt;').replace(/>/g,'&gt;');
            return '<div class="addr-opt" onmousedown="addrSelect(event,this)" data-val="' + name.replace(/"/g,'&quot;') + '"'
              + ' style="padding:9px 13px;cursor:pointer;font-size:.82rem;color:#374151;border-bottom:1px solid #F3F4F6;display:flex;align-items:flex-start;gap:8px">'
              + '<span style="flex-shrink:0;color:var(--cp)">📍</span>'
              + '<span>' + name + '</span></div>';
          }).join('');
          sugg.style.display = 'block';
        })
        .catch(function(){ sugg.style.display = 'none'; });
    }, 420);
  });
  inp.addEventListener('blur', function() {
    setTimeout(function(){ sugg.style.display = 'none'; }, 200);
  });
  inp.addEventListener('focus', function() {
    if (sugg.innerHTML && this.value.length >= 4) sugg.style.display = 'block';
  });
})();
function addrSelect(e, el) {
  e.preventDefault();
  document.getElementById('inpDireccion').value = el.dataset.val;
  document.getElementById('addrSugg').style.display = 'none';
}

// Hover effect for suggestion options
document.addEventListener('mouseover', function(e) {
  if (e.target.closest('.addr-opt')) e.target.closest('.addr-opt').style.background = '#F9FAFB';
});
document.addEventListener('mouseout', function(e) {
  if (e.target.closest('.addr-opt')) e.target.closest('.addr-opt').style.background = '';
});
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

