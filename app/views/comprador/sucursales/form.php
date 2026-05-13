<?php
// Vista: Formulario nueva/editar sucursal — con Google Maps picker
$esEdicion = !empty($sucursal);
$configModel = new ConfigModel();
$gmKey = $configModel->get('google_maps_key', '');
?>

<div style="max-width:640px">
  <a href="<?= BASE_URL ?>comprador-sucursal/index"
     style="font-size:.82rem;color:#6B7280;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:16px">
    ← Mis sucursales
  </a>

  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px">
    <h2 style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:20px">
      <?= $esEdicion ? 'Editar sucursal' : 'Nueva sucursal' ?>
    </h2>

    <form method="POST" action="<?= BASE_URL ?>comprador-sucursal/<?= $esEdicion ? 'actualizar' : 'guardar' ?>" id="form-sucursal">
      <?php if ($esEdicion): ?>
      <input type="hidden" name="id" value="<?= $sucursal['id'] ?>">
      <?php endif; ?>

      <!-- Nombre -->
      <div style="margin-bottom:16px">
        <label class="form-label">Nombre de la sucursal *</label>
        <input type="text" name="nombre" class="form-control" required
               placeholder="Ej: Taquería Norte, Cocina Central..."
               value="<?= htmlspecialchars($sucursal['nombre'] ?? '') ?>">
      </div>

      <!-- Dirección con autocomplete Google Maps -->
      <div style="margin-bottom:8px">
        <label class="form-label">Dirección *</label>
        <?php if ($gmKey): ?>
        <input type="text" id="input-direccion" name="direccion" class="form-control" required
               placeholder="Escribe la dirección para buscarla..."
               value="<?= htmlspecialchars($sucursal['direccion'] ?? '') ?>"
               autocomplete="off">
        <p style="font-size:.73rem;color:#6B7280;margin-top:4px">Escribe y selecciona del menú para capturar la ubicación exacta.</p>
        <?php else: ?>
        <textarea name="direccion" class="form-control" rows="2" required
                  placeholder="Calle, número, colonia, municipio, estado..."><?= htmlspecialchars($sucursal['direccion'] ?? '') ?></textarea>
        <p style="font-size:.73rem;color:#F59E0B;margin-top:4px">⚠ Google Maps no está configurado. Pide al administrador que configure la API key en /config/apis.</p>
        <?php endif; ?>
      </div>

      <!-- Mapa (solo si hay Google Maps key) -->
      <?php if ($gmKey): ?>
      <div id="mapa-container" style="border-radius:10px;overflow:hidden;height:260px;margin-bottom:16px;border:1px solid #E5E7EB;display:<?= (empty($sucursal['lat']) && empty($sucursal['lng'])) ? 'none' : 'block' ?>">
        <div id="mapa" style="width:100%;height:100%"></div>
      </div>
      <p id="mapa-hint" style="font-size:.73rem;color:#6B7280;margin-bottom:16px;display:<?= (!empty($sucursal['lat'])) ? 'none' : 'block' ?>">
        El mapa aparecerá al seleccionar una dirección. Puedes mover el pin para afinar la ubicación.
      </p>
      <?php endif; ?>

      <!-- Coordenadas (ocultas, se muestran como fallback si Maps falla) -->
      <input type="hidden" name="lat" id="input-lat" value="<?= htmlspecialchars($sucursal['lat'] ?? '') ?>">
      <input type="hidden" name="lng" id="input-lng" value="<?= htmlspecialchars($sucursal['lng'] ?? '') ?>">
      <!-- Fallback coordenadas manuales (visible solo si Maps no carga) -->
      <div id="coords-fallback" style="display:none;margin-bottom:16px;padding:12px;background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px">
        <p style="font-size:.78rem;color:#92400E;margin-bottom:10px">⚠ Google Maps no pudo cargar. Ingresa las coordenadas manualmente (puedes buscarlas en maps.google.com → clic derecho sobre el lugar).</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div>
            <label style="font-size:.75rem;font-weight:600;color:#374151">Latitud</label>
            <input type="text" id="input-lat-manual" placeholder="19.4326" class="form-control"
                   oninput="document.getElementById('input-lat').value=this.value">
          </div>
          <div>
            <label style="font-size:.75rem;font-weight:600;color:#374151">Longitud</label>
            <input type="text" id="input-lng-manual" placeholder="-99.1332" class="form-control"
                   oninput="document.getElementById('input-lng').value=this.value">
          </div>
        </div>
      </div>

      <!-- Responsable y teléfono -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
        <div>
          <label class="form-label">Responsable</label>
          <input type="text" name="responsable" class="form-control"
                 placeholder="Nombre del encargado"
                 value="<?= htmlspecialchars($sucursal['responsable'] ?? '') ?>">
        </div>
        <div>
          <label class="form-label">Teléfono</label>
          <input type="tel" name="telefono" class="form-control"
                 placeholder="10 dígitos" maxlength="10" inputmode="numeric" pattern="[0-9]{10}"
                 oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)"
                 value="<?= htmlspecialchars($sucursal['telefono'] ?? '') ?>">
        </div>
      </div>

      <div style="display:flex;gap:10px">
        <button type="submit"
                style="padding:10px 24px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
          <?= $esEdicion ? 'Guardar cambios' : 'Agregar sucursal' ?>
        </button>
        <a href="<?= BASE_URL ?>comprador-sucursal/index"
           style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;font-size:.875rem;font-weight:600;text-decoration:none">
          Cancelar
        </a>
      </div>
    </form>
  </div>
</div>

<?php if ($gmKey): ?>
<script>
(function() {
  var map, marker;
  var latInput = document.getElementById('input-lat');
  var lngInput = document.getElementById('input-lng');
  var dirInput = document.getElementById('input-direccion');
  var mapaContainer = document.getElementById('mapa-container');
  var mapaHint = document.getElementById('mapa-hint');

  var initLat = parseFloat(latInput.value) || null;
  var initLng = parseFloat(lngInput.value) || null;

  window.initGoogleMaps = function() {
    try {
    // Inicializar mapa
    var center = (initLat && initLng)
      ? { lat: initLat, lng: initLng }
      : { lat: 19.4326, lng: -99.1332 }; // CDMX por defecto

    map = new google.maps.Map(document.getElementById('mapa'), {
      center: center,
      zoom: (initLat && initLng) ? 15 : 12,
      mapTypeControl: false,
      streetViewControl: false,
    });

    // Marcador inicial si hay coordenadas
    if (initLat && initLng) {
      marker = new google.maps.Marker({ position: center, map: map, draggable: true, title: 'Sucursal' });
      marker.addListener('dragend', updateCoords);
      mapaContainer.style.display = 'block';
    }

    // Click en mapa crea/mueve pin
    map.addListener('click', function(e) {
      var pos = e.latLng;
      if (marker) {
        marker.setPosition(pos);
      } else {
        marker = new google.maps.Marker({ position: pos, map: map, draggable: true, title: 'Sucursal' });
        marker.addListener('dragend', updateCoords);
      }
      updateCoords();
    });

    // Places Autocomplete
    var autocomplete = new google.maps.places.Autocomplete(dirInput, {
      componentRestrictions: { country: 'mx' },
      fields: ['geometry', 'formatted_address'],
    });

    autocomplete.addListener('place_changed', function() {
      var place = autocomplete.getPlace();
      if (!place.geometry || !place.geometry.location) return;
      var pos = place.geometry.location;
      map.setCenter(pos);
      map.setZoom(16);
      if (marker) {
        marker.setPosition(pos);
      } else {
        marker = new google.maps.Marker({ position: pos, map: map, draggable: true, title: 'Sucursal' });
        marker.addListener('dragend', updateCoords);
      }
      latInput.value = pos.lat().toFixed(7);
      lngInput.value = pos.lng().toFixed(7);
      mapaContainer.style.display = 'block';
      if (mapaHint) mapaHint.style.display = 'none';
    });
  };

  function updateCoords() {
    if (!marker) return;
    var pos = marker.getPosition();
    latInput.value = pos.lat().toFixed(7);
    lngInput.value = pos.lng().toFixed(7);
  }

  } catch(e) {
    mostrarFallbackCoords();
  }

  };

  // Manejo de errores de autenticación de Google Maps
  window.gm_authFailure = function() {
    mostrarFallbackCoords();
  };

  function mostrarFallbackCoords() {
    document.getElementById('coords-fallback').style.display = 'block';
    var mapaC = document.getElementById('mapa-container');
    if (mapaC) mapaC.style.display = 'none';
    var hint = document.getElementById('mapa-hint');
    if (hint) hint.style.display = 'none';
    // Pre-rellenar si ya hay coords
    var lat = document.getElementById('input-lat').value;
    var lng = document.getElementById('input-lng').value;
    if (lat) document.getElementById('input-lat-manual').value = lat;
    if (lng) document.getElementById('input-lng-manual').value = lng;
  }
})();
</script>
<script async defer
  src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($gmKey) ?>&libraries=places&callback=initGoogleMaps&onerror=gm_authFailure">
</script>
<?php endif; ?>
