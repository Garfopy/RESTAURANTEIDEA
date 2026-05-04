<?php
$esComprador   = ($tipo === 'comprador');
$tituloTipo    = $esComprador ? 'Comprador' : 'Repartidor';
$iconoTipo     = $esComprador ? '🛒' : '🏍️';
$colorBoton    = $esComprador ? '#C8102E' : '#1F2937';
$tieneMapa     = !empty($mapsKey);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?> — Registro <?= $tituloTipo ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/carnihub.css">
</head>
<body style="background:#F3F4F6;min-height:100vh;font-family:'Inter',sans-serif;padding:32px 16px">

<div style="max-width:640px;margin:0 auto">

  <!-- Header -->
  <div style="text-align:center;margin-bottom:28px">
    <a href="<?= BASE_URL ?>registro/index">
      <?php
        // Logo dinámico
        try {
            $stmt = Database::getInstance()->prepare("SELECT clave, valor FROM global_settings WHERE clave IN ('app_logo','app_nombre')");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) { $rows = []; }
        $_logo   = $rows['app_logo'] ?? '';
        $_nombre = $rows['app_nombre'] ?? APP_NAME;
      ?>
      <?php if (!empty($_logo)): ?>
      <img src="<?= BASE_URL . htmlspecialchars($_logo) ?>" alt="<?= htmlspecialchars($_nombre) ?>" style="height:44px;margin-bottom:12px;object-fit:contain">
      <?php else: ?>
      <div style="font-size:1.75rem;font-weight:800;color:#C8102E;margin-bottom:12px;letter-spacing:-1px"><?= htmlspecialchars($_nombre) ?></div>
      <?php endif; ?>
    </a>
    <h1 style="font-size:1.5rem;font-weight:800;color:#111827">
      <?= $iconoTipo ?> Registro de <?= $tituloTipo ?>
    </h1>
    <p style="color:#6B7280;font-size:.875rem;margin-top:4px">Completa tus datos para solicitar acceso a CarniHub</p>
  </div>

  <!-- Flash -->
  <?php if (!empty($flash)): ?>
  <div style="padding:12px 14px;border-radius:8px;margin-bottom:20px;font-size:.875rem;<?= $flash['type']==='error' ? 'background:#FEE2E2;color:#991B1B;border:1px solid #FECACA' : 'background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0' ?>">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <!-- Formulario -->
  <div style="background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:32px">
    <form method="POST" action="<?= BASE_URL ?>registro/guardar" id="formRegistro">
      <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">

      <!-- Datos personales -->
      <h3 style="font-size:1rem;font-weight:700;color:#111827;margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid #F3F4F6">
        Datos personales
      </h3>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
        <div>
          <label class="form-label">Nombre(s) <span style="color:#C8102E">*</span></label>
          <input type="text" name="nombre" class="form-control" placeholder="Juan" required maxlength="100">
        </div>
        <div>
          <label class="form-label">Apellido paterno <span style="color:#C8102E">*</span></label>
          <input type="text" name="apellido_paterno" class="form-control" placeholder="Pérez" required maxlength="100">
        </div>
      </div>

      <div style="margin-bottom:16px">
        <label class="form-label">Apellido materno</label>
        <input type="text" name="apellido_materno" class="form-control" placeholder="García (opcional)" maxlength="100">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
        <div>
          <label class="form-label">Correo electrónico <span style="color:#C8102E">*</span></label>
          <input type="email" name="email" class="form-control" placeholder="tu@correo.com" required maxlength="150" autocomplete="email">
        </div>
        <div>
          <label class="form-label">Teléfono <span style="color:#C8102E">*</span></label>
          <input type="tel" name="telefono" class="form-control" placeholder="442 123 4567" required maxlength="20">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
        <div>
          <label class="form-label">Contraseña <span style="color:#C8102E">*</span></label>
          <div style="position:relative">
            <input type="password" name="password" id="passInput" class="form-control" placeholder="Mín. 8 caracteres" required minlength="8" autocomplete="new-password">
            <button type="button" onclick="togglePass('passInput')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>
        <div>
          <label class="form-label">Confirmar contraseña <span style="color:#C8102E">*</span></label>
          <div style="position:relative">
            <input type="password" name="confirmar_password" id="passConfirm" class="form-control" placeholder="Repite tu contraseña" required minlength="8" autocomplete="new-password">
            <button type="button" onclick="togglePass('passConfirm')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
          <p id="passMsg" style="font-size:.75rem;margin-top:4px;color:#9CA3AF"></p>
        </div>
      </div>

      <?php if ($esComprador): ?>
      <!-- Datos del negocio -->
      <h3 style="font-size:1rem;font-weight:700;color:#111827;margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid #F3F4F6">
        Datos del negocio
      </h3>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
        <div>
          <label class="form-label">Nombre del negocio <span style="color:#C8102E">*</span></label>
          <input type="text" name="nombre_empresa" class="form-control" placeholder="Taquería El Buen Sabor" required maxlength="150">
        </div>
        <div>
          <label class="form-label">Tipo de negocio</label>
          <select name="tipo_negocio" id="tipoNegocio" class="form-control" onchange="mostrarOtroTipo(this.value)">
            <option value="">Seleccionar…</option>
            <option value="taqueria">Taquería</option>
            <option value="restaurant">Restaurant</option>
            <option value="carniceria">Carnicería</option>
            <option value="cocina_economica">Cocina económica</option>
            <option value="supermercado">Supermercado / Tienda</option>
            <option value="hotel">Hotel / Catering</option>
            <option value="otro">Otro…</option>
          </select>
        </div>
      </div>
      <!-- Campo libre para "Otro" tipo de negocio -->
      <div id="otroTipoDiv" style="display:none;margin-bottom:16px">
        <label class="form-label">Especifica el tipo de negocio <span style="color:#C8102E">*</span></label>
        <input type="text" name="tipo_negocio_otro" id="tipoNegocioOtro" class="form-control" placeholder="Ej: Asadero, Lonchería, Fonda…" maxlength="100">
      </div>
      <?php endif; ?>

      <!-- Ubicación -->
      <h3 style="font-size:1rem;font-weight:700;color:#111827;margin-bottom:8px;padding-bottom:8px;border-bottom:2px solid #F3F4F6">
        <?= $esComprador ? 'Ubicación del negocio' : 'Tu zona de trabajo' ?>
      </h3>

      <?php if (!$esComprador): ?>
      <p style="font-size:.8rem;color:#6B7280;margin-bottom:12px">
        Selecciona el municipio de México donde realizas tus entregas.
      </p>
      <?php else: ?>
      <p style="font-size:.8rem;color:#6B7280;margin-bottom:12px">
        Busca la dirección de tu negocio. Puedes mover el marcador en el mapa para afinar la ubicación exacta.
      </p>
      <?php endif; ?>

      <?php if (!$esComprador): ?>
      <!-- Repartidor: selección de municipio -->
      <div style="margin-bottom:16px">
        <label class="form-label">Municipio donde operas <span style="color:#C8102E">*</span></label>
        <select name="ubicacion" id="ubicacionInput" class="form-control" required>
          <option value="">Selecciona un municipio…</option>
          <optgroup label="Querétaro">
            <option value="Querétaro, QRO">Querétaro</option>
            <option value="San Juan del Río, QRO">San Juan del Río</option>
            <option value="Corregidora, QRO">Corregidora</option>
            <option value="El Marqués, QRO">El Marqués</option>
            <option value="Cadereyta de Montes, QRO">Cadereyta de Montes</option>
            <option value="Tequisquiapan, QRO">Tequisquiapan</option>
            <option value="Pedro Escobedo, QRO">Pedro Escobedo</option>
            <option value="Huimilpan, QRO">Huimilpan</option>
            <option value="Colón, QRO">Colón</option>
            <option value="Amealco de Bonfil, QRO">Amealco de Bonfil</option>
            <option value="Ezequiel Montes, QRO">Ezequiel Montes</option>
            <option value="Tolimán, QRO">Tolimán</option>
            <option value="San Joaquín, QRO">San Joaquín</option>
            <option value="Peñamiller, QRO">Peñamiller</option>
            <option value="Jalpan de Serra, QRO">Jalpan de Serra</option>
            <option value="Landa de Matamoros, QRO">Landa de Matamoros</option>
            <option value="Arroyo Seco, QRO">Arroyo Seco</option>
            <option value="Pinal de Amoles, QRO">Pinal de Amoles</option>
          </optgroup>
          <optgroup label="Guanajuato">
            <option value="León, GTO">León</option>
            <option value="Irapuato, GTO">Irapuato</option>
            <option value="Celaya, GTO">Celaya</option>
            <option value="Salamanca, GTO">Salamanca</option>
            <option value="San Miguel de Allende, GTO">San Miguel de Allende</option>
            <option value="Guanajuato, GTO">Guanajuato</option>
            <option value="Silao de la Victoria, GTO">Silao de la Victoria</option>
            <option value="Pénjamo, GTO">Pénjamo</option>
          </optgroup>
          <optgroup label="Hidalgo">
            <option value="Pachuca de Soto, HGO">Pachuca de Soto</option>
            <option value="Tulancingo, HGO">Tulancingo</option>
            <option value="Tula de Allende, HGO">Tula de Allende</option>
          </optgroup>
          <optgroup label="Estado de México">
            <option value="Toluca, MEX">Toluca</option>
            <option value="Ecatepec de Morelos, MEX">Ecatepec de Morelos</option>
            <option value="Naucalpan de Juárez, MEX">Naucalpan de Juárez</option>
          </optgroup>
          <optgroup label="Ciudad de México">
            <option value="Benito Juárez, CDMX">Benito Juárez</option>
            <option value="Cuauhtémoc, CDMX">Cuauhtémoc</option>
            <option value="Iztapalapa, CDMX">Iztapalapa</option>
            <option value="Gustavo A. Madero, CDMX">Gustavo A. Madero</option>
            <option value="Álvaro Obregón, CDMX">Álvaro Obregón</option>
          </optgroup>
          <optgroup label="Jalisco">
            <option value="Guadalajara, JAL">Guadalajara</option>
            <option value="Zapopan, JAL">Zapopan</option>
            <option value="Tlaquepaque, JAL">Tlaquepaque</option>
            <option value="Tonalá, JAL">Tonalá</option>
          </optgroup>
          <optgroup label="Nuevo León">
            <option value="Monterrey, NL">Monterrey</option>
            <option value="San Nicolás de los Garza, NL">San Nicolás de los Garza</option>
            <option value="Guadalupe, NL">Guadalupe</option>
          </optgroup>
          <optgroup label="Otro estado">
            <option value="Otro">Otro municipio</option>
          </optgroup>
        </select>
        <input type="hidden" name="ubicacion_lat" id="ubicacionLat">
        <input type="hidden" name="ubicacion_lng" id="ubicacionLng">
      </div>
      <?php else: ?>
      <!-- Comprador: dirección con mapa -->
      <div style="margin-bottom:8px">
        <label class="form-label">Dirección del negocio <span style="color:#C8102E">*</span></label>
        <input
          type="text"
          id="ubicacionInput"
          name="ubicacion"
          class="form-control"
          placeholder="Ej: Av. Juárez 123, Centro, Querétaro, QRO"
          required
          autocomplete="off">
        <input type="hidden" name="ubicacion_lat" id="ubicacionLat">
        <input type="hidden" name="ubicacion_lng" id="ubicacionLng">
        <?php if ($tieneMapa): ?>
        <p style="font-size:.75rem;color:#6B7280;margin-top:4px">
          Escribe y selecciona una opción de la lista para centrar el mapa.
        </p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Mapa interactivo (solo comprador) -->
      <?php if ($tieneMapa && $esComprador): ?>
      <div id="mapaDiv" style="margin-bottom:16px;border-radius:12px;overflow:hidden;border:1px solid #E5E7EB">
        <div id="mapa" style="height:260px;width:100%"></div>
        <div style="background:#EFF6FF;padding:8px 12px;font-size:.75rem;color:#1E40AF">
          📍 Busca tu dirección arriba para centrar el mapa. Arrastra el marcador para afinar la ubicación exacta.
        </div>
      </div>
      <?php endif; ?>

      <!-- Submit -->
      <div style="margin-top:24px">
        <button type="submit" id="btnSubmit"
          style="width:100%;padding:14px;border-radius:10px;background:<?= $colorBoton ?>;color:#fff;font-weight:700;font-size:1rem;border:none;cursor:pointer;transition:opacity .2s"
          onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
          Crear mi cuenta
        </button>
      </div>

      <p style="text-align:center;font-size:.8rem;color:#9CA3AF;margin-top:16px">
        Al registrarte aceptas que un administrador de CarniHub revisará tu solicitud.
      </p>
    </form>
  </div>

  <div style="text-align:center;margin-top:20px;font-size:.875rem;color:#9CA3AF">
    ¿Ya tienes cuenta?
    <a href="<?= BASE_URL ?>auth/login" style="color:#C8102E;font-weight:600">Iniciar sesión</a>
    &nbsp;·&nbsp;
    <a href="<?= BASE_URL ?>registro/index" style="color:#6B7280">Cambiar tipo de cuenta</a>
  </div>

</div>

<!-- Scripts -->
<script>
function togglePass(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}

function mostrarOtroTipo(val) {
  const div  = document.getElementById('otroTipoDiv');
  const inp  = document.getElementById('tipoNegocioOtro');
  if (val === 'otro') {
    div.style.display = 'block';
    inp.required = true;
  } else {
    div.style.display = 'none';
    inp.required = false;
    inp.value = '';
  }
}

document.getElementById('passConfirm').addEventListener('input', function() {
  const pass = document.getElementById('passInput').value;
  const msg  = document.getElementById('passMsg');
  if (!this.value) { msg.textContent = ''; return; }
  if (this.value === pass) {
    msg.textContent = '✓ Las contraseñas coinciden';
    msg.style.color = '#059669';
  } else {
    msg.textContent = '✗ Las contraseñas no coinciden';
    msg.style.color = '#DC2626';
  }
});

document.getElementById('formRegistro').addEventListener('submit', function(e) {
  const p1 = document.getElementById('passInput').value;
  const p2 = document.getElementById('passConfirm').value;
  if (p1 !== p2) {
    e.preventDefault();
    document.getElementById('passMsg').textContent = '✗ Las contraseñas no coinciden';
    document.getElementById('passMsg').style.color = '#DC2626';
    document.getElementById('passConfirm').focus();
    return;
  }
  // Validar tipo_negocio_otro si aplica
  const tipoSelect = document.getElementById('tipoNegocio');
  if (tipoSelect && tipoSelect.value === 'otro') {
    const otroVal = document.getElementById('tipoNegocioOtro').value.trim();
    if (!otroVal) {
      e.preventDefault();
      document.getElementById('tipoNegocioOtro').focus();
      return;
    }
  }
});
</script>

<?php if ($tieneMapa && $esComprador): ?>
<script>
let map, marker, autocomplete;
const DEFAULT_CENTER = { lat: 20.5881, lng: -100.3895 };

function initMap() {
  map = new google.maps.Map(document.getElementById('mapa'), {
    center: DEFAULT_CENTER,
    zoom: 12,
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: false,
  });

  marker = new google.maps.Marker({
    position: DEFAULT_CENTER,
    map: map,
    draggable: true,
    animation: google.maps.Animation.DROP,
    title: 'Arrastra para ajustar la ubicación',
  });
  marker.addListener('dragend', function() {
    const pos = marker.getPosition();
    document.getElementById('ubicacionLat').value = pos.lat();
    document.getElementById('ubicacionLng').value = pos.lng();
  });
  map.addListener('click', function(e) {
    marker.setPosition(e.latLng);
    document.getElementById('ubicacionLat').value = e.latLng.lat();
    document.getElementById('ubicacionLng').value = e.latLng.lng();
  });

  autocomplete = new google.maps.places.Autocomplete(
    document.getElementById('ubicacionInput'),
    { types: ['geocode', 'establishment'], componentRestrictions: { country: 'mx' } }
  );
  autocomplete.addListener('place_changed', function() {
    const place = autocomplete.getPlace();
    if (!place.geometry) return;
    const lat = place.geometry.location.lat();
    const lng = place.geometry.location.lng();
    document.getElementById('ubicacionLat').value = lat;
    document.getElementById('ubicacionLng').value = lng;
    map.setCenter({ lat, lng });
    marker.setPosition({ lat, lng });
    map.setZoom(17);
  });
}
</script>
<script
  src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($mapsKey, ENT_QUOTES) ?>&libraries=places&callback=initMap"
  async defer></script>
<?php endif; ?>
</body>
</html>
