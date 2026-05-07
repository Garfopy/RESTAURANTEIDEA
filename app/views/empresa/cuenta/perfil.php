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
  <?php
  $configModel2 = new ConfigModel();
  $gmKeyPerfil  = $configModel2->get('google_maps_key', '');
  ?>
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px;margin-top:16px">
    <h2 style="font-size:.95rem;font-weight:700;margin-bottom:4px;color:#111827">Dirección de entrega principal</h2>
    <p style="font-size:.8rem;color:#6B7280;margin-bottom:16px">
      Dirección predeterminada para pedidos con envío. También puedes gestionar múltiples sucursales desde
      <a href="<?= BASE_URL ?>comprador-sucursal/index" style="color:var(--color-primary)">Mis sucursales</a>.
    </p>
    <form method="POST" action="<?= BASE_URL ?>cuenta/guardarDireccion">
      <div style="margin-bottom:12px">
        <label class="form-label">Dirección completa</label>
        <?php if ($gmKeyPerfil): ?>
        <input type="text" id="perfil-dir-input" name="direccion_entrega" class="form-control"
               placeholder="Escribe tu dirección para buscar con Google Maps..."
               value="<?= htmlspecialchars($usuario['direccion_entrega'] ?? '') ?>"
               autocomplete="off">
        <?php else: ?>
        <textarea name="direccion_entrega" class="form-control" rows="2"
                  placeholder="Calle, número exterior, colonia, municipio, estado..."><?= htmlspecialchars($usuario['direccion_entrega'] ?? '') ?></textarea>
        <?php endif; ?>
      </div>
      <div style="margin-bottom:<?= $gmKeyPerfil ? '12px' : '16px' ?>">
        <label class="form-label">Referencia / número interior</label>
        <input type="text" name="referencia_entrega" class="form-control"
               placeholder="Ej: Depto 3B, edificio azul, portón negro..."
               value="<?= htmlspecialchars($usuario['referencia_entrega'] ?? '') ?>">
      </div>

      <?php if ($gmKeyPerfil): ?>
      <!-- Mapa para confirmar ubicación -->
      <div id="mapa-perfil-container" style="border-radius:10px;overflow:hidden;height:220px;margin-bottom:12px;border:1px solid #E5E7EB;display:<?= (!empty($usuario['lat_entrega'])) ? 'block' : 'none' ?>">
        <div id="mapa-perfil" style="width:100%;height:100%"></div>
      </div>
      <p id="mapa-perfil-hint" style="font-size:.75rem;color:#6B7280;margin-bottom:12px;display:<?= (!empty($usuario['lat_entrega'])) ? 'none' : 'block' ?>">
        Escribe la dirección para ver el mapa y confirmar la ubicación exacta.
      </p>
      <?php endif; ?>

      <?php if (!empty($usuario['lat_entrega']) && !empty($usuario['lng_entrega'])): ?>
      <div style="margin-bottom:12px;font-size:.78rem;color:#059669">
        ✓ Ubicación GPS guardada (<?= number_format((float)$usuario['lat_entrega'],5) ?>, <?= number_format((float)$usuario['lng_entrega'],5) ?>)
      </div>
      <?php endif; ?>

      <input type="hidden" name="lat_entrega" id="perfil-lat" value="<?= htmlspecialchars($usuario['lat_entrega'] ?? '') ?>">
      <input type="hidden" name="lng_entrega" id="perfil-lng" value="<?= htmlspecialchars($usuario['lng_entrega'] ?? '') ?>">

      <button type="submit" style="padding:9px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
        Guardar dirección
      </button>
    </form>
  </div>

  <?php if ($gmKeyPerfil): ?>
  <script>
  (function() {
    var mapPerfil = null, markerPerfil = null;
    var initLat = parseFloat('<?= (float)($usuario['lat_entrega'] ?? 0) ?>') || null;
    var initLng = parseFloat('<?= (float)($usuario['lng_entrega'] ?? 0) ?>') || null;

    window.initGoogleMapsPerfil = function() {
      var center = (initLat && initLng) ? { lat: initLat, lng: initLng } : { lat: 19.4326, lng: -99.1332 };
      mapPerfil = new google.maps.Map(document.getElementById('mapa-perfil'), {
        center: center, zoom: (initLat && initLng) ? 16 : 12,
        mapTypeControl: false, streetViewControl: false
      });

      if (initLat && initLng) {
        markerPerfil = new google.maps.Marker({ position: center, map: mapPerfil, draggable: true });
        markerPerfil.addListener('dragend', actualizarCoords);
        document.getElementById('mapa-perfil-container').style.display = 'block';
      }

      mapPerfil.addListener('click', function(e) {
        var pos = e.latLng;
        if (markerPerfil) { markerPerfil.setPosition(pos); } else {
          markerPerfil = new google.maps.Marker({ position: pos, map: mapPerfil, draggable: true });
          markerPerfil.addListener('dragend', actualizarCoords);
        }
        actualizarCoords();
      });

      var autocomplete = new google.maps.places.Autocomplete(
        document.getElementById('perfil-dir-input'),
        { componentRestrictions: { country: 'mx' }, fields: ['geometry', 'formatted_address'] }
      );
      autocomplete.addListener('place_changed', function() {
        var place = autocomplete.getPlace();
        if (!place.geometry) return;
        var pos = place.geometry.location;
        mapPerfil.setCenter(pos);
        mapPerfil.setZoom(16);
        if (markerPerfil) { markerPerfil.setPosition(pos); } else {
          markerPerfil = new google.maps.Marker({ position: pos, map: mapPerfil, draggable: true });
          markerPerfil.addListener('dragend', actualizarCoords);
        }
        document.getElementById('perfil-lat').value = pos.lat().toFixed(7);
        document.getElementById('perfil-lng').value = pos.lng().toFixed(7);
        document.getElementById('mapa-perfil-container').style.display = 'block';
        var hint = document.getElementById('mapa-perfil-hint');
        if (hint) hint.style.display = 'none';
      });
    };

    function actualizarCoords() {
      if (!markerPerfil) return;
      var pos = markerPerfil.getPosition();
      document.getElementById('perfil-lat').value = pos.lat().toFixed(7);
      document.getElementById('perfil-lng').value = pos.lng().toFixed(7);
    }
  })();
  </script>
  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($gmKeyPerfil) ?>&libraries=places&callback=initGoogleMapsPerfil">
  </script>
  <?php endif; ?>
  <?php endif; ?>

  <?php if (in_array($rol ?? '', ['admin_empresa', 'supervisor'], true)): ?>
  <?php
  $empresaDataPerfil = $_SESSION['empresa'] ?? [];
  $configModelEmp    = new ConfigModel();
  $gmKeyEmp          = $configModelEmp->get('google_maps_key', '');
  ?>
  <!-- Dirección de la empresa (supervisores y admin_empresa) -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px;margin-top:16px">
    <h2 style="font-size:.95rem;font-weight:700;margin-bottom:4px;color:#111827">Dirección de la empresa</h2>
    <p style="font-size:.8rem;color:#6B7280;margin-bottom:16px">
      Esta dirección se usa como <strong>punto de origen</strong> para calcular rutas de entrega y el costo de envío automáticamente.
      <?php if (empty($empresaDataPerfil['direccion_fiscal'])): ?>
      <strong style="color:#DC2626">⚠ Sin dirección registrada — algunos cálculos de ruta no funcionarán.</strong>
      <?php endif; ?>
    </p>
    <form method="POST" action="<?= BASE_URL ?>empresa/guardarDireccion">
      <div style="margin-bottom:12px">
        <label class="form-label">Dirección completa de la empresa</label>
        <?php if ($gmKeyEmp): ?>
        <input type="text" id="emp-dir-input" name="direccion_fiscal" class="form-control"
               placeholder="Busca la dirección con Google Maps..."
               value="<?= htmlspecialchars($empresaDataPerfil['direccion_fiscal'] ?? '') ?>"
               autocomplete="off">
        <?php else: ?>
        <input type="text" name="direccion_fiscal" class="form-control"
               placeholder="Calle, número, colonia, ciudad..."
               value="<?= htmlspecialchars($empresaDataPerfil['direccion_fiscal'] ?? '') ?>">
        <?php endif; ?>
      </div>

      <?php if ($gmKeyEmp): ?>
      <div id="mapa-emp-container" style="border-radius:10px;overflow:hidden;height:220px;margin-bottom:12px;border:1px solid #E5E7EB;display:<?= (!empty($empresaDataPerfil['lat'])) ? 'block' : 'none' ?>">
        <div id="mapa-emp" style="width:100%;height:100%"></div>
      </div>
      <p id="mapa-emp-hint" style="font-size:.75rem;color:#6B7280;margin-bottom:12px;display:<?= (!empty($empresaDataPerfil['lat'])) ? 'none' : 'block' ?>">
        Escribe la dirección para ver el mapa y confirmar la ubicación exacta.
      </p>
      <?php endif; ?>

      <?php if (!empty($empresaDataPerfil['lat']) && !empty($empresaDataPerfil['lng'])): ?>
      <div style="margin-bottom:12px;font-size:.78rem;color:#059669">
        ✓ Ubicación GPS guardada (<?= number_format((float)$empresaDataPerfil['lat'],5) ?>, <?= number_format((float)$empresaDataPerfil['lng'],5) ?>)
      </div>
      <?php endif; ?>

      <input type="hidden" name="lat" id="emp-lat" value="<?= htmlspecialchars($empresaDataPerfil['lat'] ?? '') ?>">
      <input type="hidden" name="lng" id="emp-lng" value="<?= htmlspecialchars($empresaDataPerfil['lng'] ?? '') ?>">

      <button type="submit" style="padding:9px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
        Guardar dirección
      </button>
    </form>
  </div>

  <?php if ($gmKeyEmp): ?>
  <script>
  (function() {
    var mapEmp = null, markerEmp = null;
    var initLatEmp = parseFloat('<?= (float)($empresaDataPerfil['lat'] ?? 0) ?>') || null;
    var initLngEmp = parseFloat('<?= (float)($empresaDataPerfil['lng'] ?? 0) ?>') || null;

    window.initGoogleMapsEmpresa = function() {
      var center = (initLatEmp && initLngEmp) ? { lat: initLatEmp, lng: initLngEmp } : { lat: 19.4326, lng: -99.1332 };
      mapEmp = new google.maps.Map(document.getElementById('mapa-emp'), {
        center: center, zoom: (initLatEmp && initLngEmp) ? 16 : 12,
        mapTypeControl: false, streetViewControl: false
      });
      if (initLatEmp && initLngEmp) {
        markerEmp = new google.maps.Marker({ position: center, map: mapEmp, draggable: true });
        markerEmp.addListener('dragend', actualizarCoordsEmp);
        document.getElementById('mapa-emp-container').style.display = 'block';
      }
      mapEmp.addListener('click', function(e) {
        var pos = e.latLng;
        if (markerEmp) { markerEmp.setPosition(pos); } else {
          markerEmp = new google.maps.Marker({ position: pos, map: mapEmp, draggable: true });
          markerEmp.addListener('dragend', actualizarCoordsEmp);
        }
        actualizarCoordsEmp();
      });
      var autocomplete = new google.maps.places.Autocomplete(
        document.getElementById('emp-dir-input'),
        { componentRestrictions: { country: 'mx' }, fields: ['geometry', 'formatted_address'] }
      );
      autocomplete.addListener('place_changed', function() {
        var place = autocomplete.getPlace();
        if (!place.geometry) return;
        var pos = place.geometry.location;
        mapEmp.setCenter(pos); mapEmp.setZoom(16);
        if (markerEmp) { markerEmp.setPosition(pos); } else {
          markerEmp = new google.maps.Marker({ position: pos, map: mapEmp, draggable: true });
          markerEmp.addListener('dragend', actualizarCoordsEmp);
        }
        document.getElementById('emp-lat').value = pos.lat().toFixed(7);
        document.getElementById('emp-lng').value = pos.lng().toFixed(7);
        document.getElementById('mapa-emp-container').style.display = 'block';
        var hint = document.getElementById('mapa-emp-hint');
        if (hint) hint.style.display = 'none';
      });
    };
    function actualizarCoordsEmp() {
      if (!markerEmp) return;
      var pos = markerEmp.getPosition();
      document.getElementById('emp-lat').value = pos.lat().toFixed(7);
      document.getElementById('emp-lng').value = pos.lng().toFixed(7);
    }
  })();
  </script>
  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($gmKeyEmp) ?>&libraries=places&callback=initGoogleMapsEmpresa">
  </script>
  <?php endif; ?>
  <?php endif; ?>

</div>
