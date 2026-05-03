<?php
$esComprador   = ($tipo === 'comprador');
$tituloTipo    = $esComprador ? 'Comprador' : 'Repartidor';
$iconoTipo     = $esComprador ? '🛒' : '🏍️';
$colorBoton    = $esComprador ? '#C8102E' : '#1F2937';
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
      <img src="<?= BASE_URL ?>public/img/logo.svg" alt="CarniHub" style="height:40px;margin-bottom:12px">
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
      <!-- Datos del negocio (solo comprador) -->
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
          <select name="tipo_negocio" class="form-control">
            <option value="">Seleccionar…</option>
            <option value="taqueria">Taquería</option>
            <option value="restaurant">Restaurant</option>
            <option value="carniceria">Carnicería</option>
            <option value="cocina_economica">Cocina económica</option>
            <option value="supermercado">Supermercado / Tienda</option>
            <option value="hotel">Hotel / Catering</option>
            <option value="otro">Otro</option>
          </select>
        </div>
      </div>
      <?php endif; ?>

      <!-- Ubicación -->
      <h3 style="font-size:1rem;font-weight:700;color:#111827;margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid #F3F4F6">
        <?= $esComprador ? 'Ubicación del negocio' : 'Tu zona de trabajo' ?>
      </h3>

      <div style="margin-bottom:8px">
        <label class="form-label">
          <?= $esComprador ? 'Dirección del negocio' : 'Colonia / zona donde operas' ?>
          <span style="color:#C8102E">*</span>
        </label>
        <input
          type="text"
          id="ubicacionInput"
          name="ubicacion"
          class="form-control"
          placeholder="Escribe la dirección o colonia…"
          required
          autocomplete="off">
        <input type="hidden" name="ubicacion_lat" id="ubicacionLat">
        <input type="hidden" name="ubicacion_lng" id="ubicacionLng">
        <p style="font-size:.75rem;color:#6B7280;margin-top:4px">
          Selecciona una opción de la lista para capturar las coordenadas exactas.
        </p>
      </div>

      <!-- Mini mapa de confirmación -->
      <div id="mapaPreview" style="display:none;height:180px;border-radius:10px;overflow:hidden;margin-bottom:16px;border:1px solid #E5E7EB">
        <iframe id="mapaFrame" width="100%" height="180" frameborder="0" style="border:0" allowfullscreen></iframe>
      </div>

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

<!-- Google Maps Places Autocomplete -->
<?php if (defined('GOOGLE_MAPS_KEY') && GOOGLE_MAPS_KEY): ?>
<script>
  let autocomplete;

  function initAutocomplete() {
    autocomplete = new google.maps.places.Autocomplete(
      document.getElementById('ubicacionInput'),
      { types: ['geocode', 'establishment'], componentRestrictions: { country: 'mx' } }
    );
    autocomplete.addListener('place_changed', onPlaceChanged);
  }

  function onPlaceChanged() {
    const place = autocomplete.getPlace();
    if (!place.geometry) return;
    const lat = place.geometry.location.lat();
    const lng = place.geometry.location.lng();
    document.getElementById('ubicacionLat').value = lat;
    document.getElementById('ubicacionLng').value = lng;

    // Mini mapa embed
    const frame  = document.getElementById('mapaFrame');
    const key    = '<?= GOOGLE_MAPS_KEY ?>';
    frame.src    = `https://www.google.com/maps/embed/v1/place?key=${key}&q=${lat},${lng}&zoom=16`;
    document.getElementById('mapaPreview').style.display = 'block';
  }
</script>
<script
  src="https://maps.googleapis.com/maps/api/js?key=<?= GOOGLE_MAPS_KEY ?>&libraries=places&callback=initAutocomplete"
  async defer></script>
<?php else: ?>
<script>
  // Sin API key de Google Maps: el campo de texto funciona como campo libre
  document.getElementById('ubicacionInput').placeholder = 'Ej: Colonia Centro, Querétaro, QRO';
</script>
<?php endif; ?>

<script>
function togglePass(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}

// Validación visual contraseñas coinciden
document.getElementById('passConfirm').addEventListener('input', function() {
  const pass  = document.getElementById('passInput').value;
  const msg   = document.getElementById('passMsg');
  if (!this.value) { msg.textContent = ''; return; }
  if (this.value === pass) {
    msg.textContent = '✓ Las contraseñas coinciden';
    msg.style.color = '#059669';
  } else {
    msg.textContent = '✗ Las contraseñas no coinciden';
    msg.style.color = '#DC2626';
  }
});

// Prevenir submit con contraseñas distintas
document.getElementById('formRegistro').addEventListener('submit', function(e) {
  const p1 = document.getElementById('passInput').value;
  const p2 = document.getElementById('passConfirm').value;
  if (p1 !== p2) {
    e.preventDefault();
    document.getElementById('passMsg').textContent = '✗ Las contraseñas no coinciden';
    document.getElementById('passMsg').style.color = '#DC2626';
    document.getElementById('passConfirm').focus();
  }
});
</script>
</body>
</html>
