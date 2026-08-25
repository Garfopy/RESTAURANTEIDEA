<?php
$isEdit = !empty($restaurante);
$nombre = $restaurante['nombre'] ?? '';
$descripcion = $restaurante['descripcion'] ?? '';
$telefono = substr(preg_replace('/\D+/', '', (string)($restaurante['telefono'] ?? '')), 0, 10);
$direccion = $restaurante['direccion'] ?? '';
$lat = $restaurante['lat'] ?? '';
$lng = $restaurante['lng'] ?? '';
$horarioApertura = $restaurante['horario_apertura'] ?? '';
$horarioCierre = $restaurante['horario_cierre'] ?? '';
$colorPrimario = $restaurante['color_primario'] ?? '#C8102E';
$colorSecundario = $restaurante['color_secundario'] ?? '#1F2937';
$formAction = BASE_URL . ($isEdit ? 'restaurante/actualizar/' . (int)$restaurante['id'] : 'restaurante/guardar');
$cancelUrl = BASE_URL . ($isEdit || !empty($_SESSION['restaurante_activo_id']) ? 'restaurante/dashboard' : 'restaurante/seleccionar');
$title = ($isEdit ? 'Editar local' : 'Crear local') . ' - Jungle Pizza';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --cp: <?= htmlspecialchars($colorPrimario) ?>;
      --cs: <?= htmlspecialchars($colorSecundario) ?>;
      --bg: #F6F7F9;
      --ink: #111827;
      --muted: #6B7280;
      --line: #E5E7EB;
      --soft: #F9FAFB;
      --danger-bg: #FEE2E2;
      --danger: #991B1B;
      --success-bg: #DCFCE7;
      --success: #166534;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      background: var(--bg);
      color: var(--ink);
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      -webkit-font-smoothing: antialiased;
    }
    a { color: inherit; }
    .page {
      width: min(1180px, calc(100% - 32px));
      margin: 0 auto;
      padding: 28px 0 40px;
    }
    .topline {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 18px;
    }
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      color: #334155;
      text-decoration: none;
      font-size: 1rem;
      font-weight: 800;
      padding: 9px 14px 9px 10px;
      border: 1px solid #E2E8F0;
      border-radius: 999px;
      background: rgba(255,255,255,.86);
      box-shadow: 0 8px 20px rgba(15,23,42,.06);
      transition: color .15s, border-color .15s, background .15s, transform .15s, box-shadow .15s;
    }
    .back-link:hover {
      color: var(--cp);
      border-color: color-mix(in srgb, var(--cp) 34%, #CBD5E1);
      background: color-mix(in srgb, var(--cp-light) 45%, white);
      box-shadow: 0 12px 26px rgba(15,23,42,.1);
      transform: translateX(-2px);
    }
    .shell {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 340px;
      gap: 20px;
      align-items: start;
    }
    .panel {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 8px;
      box-shadow: 0 12px 28px rgba(17,24,39,.06);
      overflow: hidden;
    }
    .hero {
      padding: 26px 28px 22px;
      border-bottom: 1px solid var(--line);
      background:
        linear-gradient(135deg, color-mix(in srgb, var(--cp) 10%, white), #fff 58%),
        #fff;
    }
    .eyebrow {
      color: var(--cp);
      font-size: .72rem;
      font-weight: 800;
      letter-spacing: .08em;
      text-transform: uppercase;
      margin-bottom: 8px;
    }
    h1 {
      margin: 0;
      font-size: clamp(1.45rem, 2.4vw, 2.15rem);
      line-height: 1.08;
      letter-spacing: 0;
    }
    .hero-copy {
      margin: 10px 0 0;
      max-width: 720px;
      color: var(--muted);
      font-size: .94rem;
      line-height: 1.55;
    }
    form { margin: 0; }
    .form-body { padding: 24px 28px 8px; }
    .section {
      padding: 0 0 24px;
      margin-bottom: 24px;
      border-bottom: 1px solid #EEF0F3;
    }
    .section:last-child {
      border-bottom: none;
      margin-bottom: 0;
    }
    .section-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 14px;
      margin-bottom: 16px;
    }
    .section-title {
      margin: 0;
      font-size: 1rem;
      font-weight: 800;
      letter-spacing: 0;
    }
    .section-note {
      margin: 4px 0 0;
      color: var(--muted);
      font-size: .82rem;
      line-height: 1.45;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }
    .field.full { grid-column: 1 / -1; }
    label {
      display: block;
      margin-bottom: 6px;
      color: #374151;
      font-size: .82rem;
      font-weight: 700;
    }
    .required { color: var(--cp); }
    input[type="text"],
    input[type="tel"],
    input[type="time"],
    textarea {
      width: 100%;
      border: 1px solid #D1D5DB;
      border-radius: 8px;
      background: #fff;
      color: var(--ink);
      font: inherit;
      font-size: .93rem;
      outline: none;
      padding: 11px 12px;
      transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
    }
    textarea {
      min-height: 104px;
      resize: vertical;
      line-height: 1.45;
    }
    input:focus,
    textarea:focus {
      border-color: var(--cp);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--cp) 14%, transparent);
    }
    input::placeholder,
    textarea::placeholder { color: #9CA3AF; }
    .hint {
      margin-top: 6px;
      color: #6B7280;
      font-size: .76rem;
      line-height: 1.4;
    }
    .address-wrap {
      position: relative;
    }
    .address-suggestions {
      display: none;
      position: absolute;
      top: calc(100% + 4px);
      left: 0;
      right: 0;
      z-index: 30;
      max-height: 220px;
      overflow-y: auto;
      background: #fff;
      border: 1px solid #E5E7EB;
      border-radius: 8px;
      box-shadow: 0 12px 28px rgba(17,24,39,.12);
    }
    .address-option {
      padding: 10px 12px;
      border-bottom: 1px solid #F3F4F6;
      color: #374151;
      cursor: pointer;
      font-size: .82rem;
      line-height: 1.35;
    }
    .address-option:hover {
      background: #F9FAFB;
      color: var(--cp);
    }
    .map-shell {
      min-height: 230px;
      border: 1px solid #D1D5DB;
      border-radius: 8px;
      overflow: hidden;
      background: #F3F4F6;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .map-placeholder {
      padding: 22px;
      color: #6B7280;
      font-size: .84rem;
      text-align: center;
    }
    .map-pin {
      position: relative;
      width: 24px;
      height: 24px;
      transform: rotate(-45deg);
      border-radius: 50% 50% 50% 0;
      background: var(--cp);
      border: 2px solid #fff;
      box-shadow: 0 8px 18px rgba(17,24,39,.28);
    }
    .map-pin::after {
      content: "";
      position: absolute;
      top: 6px;
      left: 6px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #fff;
    }
    .coords {
      display: none;
      margin-top: 8px;
      color: #4B5563;
      font-size: .76rem;
      line-height: 1.45;
    }
    .coords strong {
      color: #111827;
    }
    .color-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    .color-field {
      display: grid;
      grid-template-columns: 48px minmax(0, 1fr);
      gap: 10px;
      align-items: center;
      padding: 10px;
      border: 1px solid #D1D5DB;
      border-radius: 8px;
      background: #fff;
    }
    .color-field input[type="color"] {
      width: 48px;
      height: 42px;
      padding: 0;
      border: none;
      background: transparent;
      cursor: pointer;
    }
    .color-name {
      color: #374151;
      font-size: .82rem;
      font-weight: 800;
    }
    .color-value {
      margin-top: 2px;
      color: #6B7280;
      font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      font-size: .78rem;
      text-transform: uppercase;
    }
    .actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      padding: 18px 28px;
      border-top: 1px solid var(--line);
      background: #fff;
      position: sticky;
      bottom: 0;
      z-index: 5;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-height: 42px;
      padding: 10px 18px;
      border-radius: 8px;
      border: 1px solid transparent;
      font-size: .88rem;
      font-weight: 800;
      text-decoration: none;
      cursor: pointer;
      transition: transform .14s ease, filter .14s ease, box-shadow .14s ease, background .14s ease;
      white-space: nowrap;
    }
    .btn:active { transform: translateY(1px); }
    .btn-primary {
      background: var(--cp);
      color: #fff;
      box-shadow: 0 10px 18px color-mix(in srgb, var(--cp) 22%, transparent);
    }
    .btn-primary:hover { filter: brightness(1.04); }
    .btn-outline {
      background: #fff;
      border-color: #D1D5DB;
      color: #374151;
    }
    .btn-outline:hover { background: #F9FAFB; }
    .side {
      position: sticky;
      top: 20px;
      display: grid;
      gap: 14px;
    }
    .preview {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 12px 28px rgba(17,24,39,.06);
    }
    .preview-top {
      min-height: 118px;
      background:
        linear-gradient(135deg, color-mix(in srgb, var(--cp) 86%, #000), color-mix(in srgb, var(--cs) 90%, #000));
      color: #fff;
      padding: 18px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .preview-chip {
      align-self: flex-start;
      border: 1px solid rgba(255,255,255,.28);
      border-radius: 999px;
      padding: 4px 9px;
      font-size: .72rem;
      font-weight: 800;
      background: rgba(255,255,255,.12);
    }
    .preview-name {
      font-size: 1.15rem;
      font-weight: 800;
      line-height: 1.2;
      word-break: break-word;
    }
    .preview-body { padding: 16px; }
    .preview-desc {
      color: #4B5563;
      font-size: .86rem;
      line-height: 1.5;
      min-height: 42px;
    }
    .preview-list {
      display: grid;
      gap: 10px;
      margin-top: 16px;
    }
    .preview-row {
      display: grid;
      grid-template-columns: 30px minmax(0, 1fr);
      gap: 10px;
      align-items: center;
      color: #374151;
      font-size: .84rem;
    }
    .preview-icon {
      width: 30px;
      height: 30px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: color-mix(in srgb, var(--cp) 10%, white);
      color: var(--cp);
      font-weight: 800;
      font-size: .78rem;
    }
    .status {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 14px;
    }
    .status-title {
      font-size: .84rem;
      font-weight: 800;
      margin-bottom: 10px;
    }
    .checks {
      display: grid;
      gap: 8px;
    }
    .check {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #4B5563;
      font-size: .8rem;
    }
    .dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background: #D1D5DB;
      flex: 0 0 auto;
    }
    .check.done .dot { background: var(--cp); }
    .flash {
      padding: 12px 14px;
      margin-bottom: 14px;
      border-radius: 8px;
      font-size: .86rem;
      font-weight: 700;
      border: 1px solid transparent;
    }
    .flash.success {
      background: var(--success-bg);
      color: var(--success);
      border-color: #BBF7D0;
    }
    .flash.error {
      background: var(--danger-bg);
      color: var(--danger);
      border-color: #FECACA;
    }
    @media (max-width: 980px) {
      .shell { grid-template-columns: 1fr; }
      .side { position: static; grid-template-columns: 1fr; }
    }
    @media (max-width: 680px) {
      .page {
        width: min(100% - 20px, 1180px);
        padding-top: 16px;
      }
      .topline { align-items: flex-start; }
      .hero,
      .form-body,
      .actions { padding-left: 18px; padding-right: 18px; }
      .grid,
      .color-grid { grid-template-columns: 1fr; }
      .actions {
        position: static;
        flex-direction: column-reverse;
      }
      .btn { width: 100%; }
    }
  </style>
</head>
<body>
  <main class="page">
    <div class="topline">
      <a class="back-link" href="<?= htmlspecialchars($cancelUrl) ?>" aria-label="Volver">
        <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
      </a>
    </div>

    <?php if (!empty($flash)): ?>
    <div class="flash <?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <div class="shell">
      <section class="panel">
        <div class="hero">
          <div class="eyebrow"><?= $isEdit ? 'Configuracion del local' : 'Nuevo local' ?></div>
          <h1><?= $isEdit ? 'Actualiza la informacion del restaurante' : 'Crea tu restaurante' ?></h1>
          <p class="hero-copy">
            Estos datos se usan para identificar el local en el panel, el menu publico y las operaciones del restaurante.
          </p>
        </div>

        <form method="POST" action="<?= htmlspecialchars($formAction) ?>" id="restaurantForm">
          <div class="form-body">
            <div class="section">
              <div class="section-head">
                <div>
                  <h2 class="section-title">Identidad</h2>
                  <p class="section-note">Nombre comercial y descripcion visible del local.</p>
                </div>
              </div>

              <div class="grid">
                <div class="field full">
                  <label for="nombre">Nombre del restaurante <span class="required">*</span></label>
                  <input
                    id="nombre"
                    type="text"
                    name="nombre"
                    value="<?= htmlspecialchars($nombre) ?>"
                    required
                    maxlength="120"
                    autocomplete="organization"
                    placeholder="Ej. Jungle Pizza Zihuatanejo"
                  >
                </div>

                <div class="field full">
                  <label for="descripcion">Descripcion</label>
                  <textarea
                    id="descripcion"
                    name="descripcion"
                    maxlength="500"
                    placeholder="Cocina mexicana contemporanea, bebidas de autor y servicio para mesa."
                  ><?= htmlspecialchars($descripcion) ?></textarea>
                </div>
              </div>
            </div>

            <div class="section">
              <div class="section-head">
                <div>
                  <h2 class="section-title">Contacto y ubicacion</h2>
                  <p class="section-note">Informacion operativa del local.</p>
                </div>
              </div>

              <div class="grid">
                <div class="field">
                  <label for="telefono">Telefono</label>
                  <input
                    id="telefono"
                    type="tel"
                    name="telefono"
                    value="<?= htmlspecialchars($telefono) ?>"
                    maxlength="10"
                    minlength="10"
                    inputmode="numeric"
                    pattern="[0-9]{10}"
                    autocomplete="tel"
                    placeholder="442 123 4567"
                  >
                  <div class="hint">Usa solo 10 digitos.</div>
                </div>

                <div class="field">
                  <label for="horario_apertura">Apertura</label>
                  <input
                    id="horario_apertura"
                    type="time"
                    name="horario_apertura"
                    value="<?= htmlspecialchars($horarioApertura) ?>"
                  >
                </div>

                <div class="field">
                  <label for="horario_cierre">Cierre</label>
                  <input
                    id="horario_cierre"
                    type="time"
                    name="horario_cierre"
                    value="<?= htmlspecialchars($horarioCierre) ?>"
                  >
                </div>

                <div class="field full">
                  <label for="direccion">Direccion</label>
                  <div class="address-wrap">
                    <input
                      id="direccion"
                      type="text"
                      name="direccion"
                      value="<?= htmlspecialchars($direccion) ?>"
                      maxlength="255"
                      autocomplete="street-address"
                      placeholder="Calle, numero, colonia, ciudad"
                    >
                    <div id="addressSuggestions" class="address-suggestions"></div>
                  </div>
                  <input type="hidden" name="lat" id="lat" value="<?= htmlspecialchars((string)$lat) ?>">
                  <input type="hidden" name="lng" id="lng" value="<?= htmlspecialchars((string)$lng) ?>">
                  <div class="coords" id="coordsBox">
                    <strong>Coordenadas:</strong>
                    <span id="coordLat"><?= htmlspecialchars((string)($lat ?: '')) ?></span>,
                    <span id="coordLng"><?= htmlspecialchars((string)($lng ?: '')) ?></span>
                  </div>
                </div>

                <div class="field full">
                  <label>Ubicacion en mapa</label>
                  <div id="restaurantMap" class="map-shell" data-initial-lat="<?= htmlspecialchars((string)$lat) ?>" data-initial-lng="<?= htmlspecialchars((string)$lng) ?>">
                    <div class="map-placeholder">Escribe la direccion para cargar la ubicacion en el mapa.</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="section">
              <div class="section-head">
                <div>
                  <h2 class="section-title">Marca visual</h2>
                  <p class="section-note">Colores base para el panel y el menu publico.</p>
                </div>
              </div>

              <div class="color-grid">
                <label class="color-field" for="color_primario">
                  <input id="color_primario" type="color" name="color_primario" value="<?= htmlspecialchars($colorPrimario) ?>">
                  <span>
                    <span class="color-name">Primario</span>
                    <span class="color-value" id="primaryValue"><?= htmlspecialchars($colorPrimario) ?></span>
                  </span>
                </label>

                <label class="color-field" for="color_secundario">
                  <input id="color_secundario" type="color" name="color_secundario" value="<?= htmlspecialchars($colorSecundario) ?>">
                  <span>
                    <span class="color-name">Secundario</span>
                    <span class="color-value" id="secondaryValue"><?= htmlspecialchars($colorSecundario) ?></span>
                  </span>
                </label>
              </div>
            </div>
          </div>

          <div class="actions">
            <a class="btn btn-outline" href="<?= htmlspecialchars($cancelUrl) ?>">Cancelar</a>
            <button class="btn btn-primary" type="submit">
              <?= $isEdit ? 'Guardar cambios' : 'Crear local' ?>
            </button>
          </div>
        </form>
      </section>

      <aside class="side" aria-label="Vista previa del local">
        <section class="preview">
          <div class="preview-top">
            <span class="preview-chip">Menu publico</span>
            <div class="preview-name" id="previewName"><?= htmlspecialchars($nombre ?: 'Nombre del restaurante') ?></div>
          </div>
          <div class="preview-body">
            <div class="preview-desc" id="previewDesc">
              <?= htmlspecialchars($descripcion ?: 'Descripcion breve del local') ?>
            </div>
            <div class="preview-list">
              <div class="preview-row">
                <span class="preview-icon">TEL</span>
                <span id="previewPhone"><?= htmlspecialchars($telefono ?: 'Sin telefono') ?></span>
              </div>
              <div class="preview-row">
                <span class="preview-icon">HRS</span>
                <span id="previewHours">
                  <?= htmlspecialchars(($horarioApertura || $horarioCierre) ? trim($horarioApertura . ' - ' . $horarioCierre, ' -') : 'Sin horario') ?>
                </span>
              </div>
              <div class="preview-row">
                <span class="preview-icon">DIR</span>
                <span id="previewAddress"><?= htmlspecialchars($direccion ?: 'Sin direccion') ?></span>
              </div>
            </div>
          </div>
        </section>

        <section class="status">
          <div class="status-title">Datos capturados</div>
          <div class="checks">
            <div class="check" data-check="nombre"><span class="dot"></span><span>Nombre</span></div>
            <div class="check" data-check="descripcion"><span class="dot"></span><span>Descripcion</span></div>
            <div class="check" data-check="contacto"><span class="dot"></span><span>Contacto</span></div>
            <div class="check" data-check="horario"><span class="dot"></span><span>Horario</span></div>
            <div class="check" data-check="direccion"><span class="dot"></span><span>Direccion</span></div>
          </div>
        </section>
      </aside>
    </div>
  </main>

  <script>
    const form = document.getElementById('restaurantForm');
    const fields = {
      nombre: document.getElementById('nombre'),
      descripcion: document.getElementById('descripcion'),
      telefono: document.getElementById('telefono'),
      apertura: document.getElementById('horario_apertura'),
      cierre: document.getElementById('horario_cierre'),
      direccion: document.getElementById('direccion'),
      primary: document.getElementById('color_primario'),
      secondary: document.getElementById('color_secundario')
    };

    const setText = (id, value, fallback) => {
      document.getElementById(id).textContent = value && value.trim() ? value.trim() : fallback;
    };

    const setCheck = (name, done) => {
      const el = document.querySelector(`[data-check="${name}"]`);
      if (el) el.classList.toggle('done', !!done);
    };

    const updatePreview = () => {
      const nombre = fields.nombre.value.trim();
      const descripcion = fields.descripcion.value.trim();
      const telefono = fields.telefono.value.replace(/\D/g, '').slice(0, 10);
      if (fields.telefono.value !== telefono) fields.telefono.value = telefono;
      const apertura = fields.apertura.value;
      const cierre = fields.cierre.value;
      const direccion = fields.direccion.value.trim();
      const horario = [apertura, cierre].filter(Boolean).join(' - ');

      document.documentElement.style.setProperty('--cp', fields.primary.value);
      document.documentElement.style.setProperty('--cs', fields.secondary.value);
      document.getElementById('primaryValue').textContent = fields.primary.value.toUpperCase();
      document.getElementById('secondaryValue').textContent = fields.secondary.value.toUpperCase();

      setText('previewName', nombre, 'Nombre del restaurante');
      setText('previewDesc', descripcion, 'Descripcion breve del local');
      setText('previewPhone', telefono, 'Sin telefono');
      setText('previewHours', horario, 'Sin horario');
      setText('previewAddress', direccion, 'Sin direccion');

      setCheck('nombre', nombre.length > 0);
      setCheck('descripcion', descripcion.length > 0);
      setCheck('contacto', telefono.length > 0);
      setCheck('horario', apertura.length > 0 || cierre.length > 0);
      setCheck('direccion', direccion.length > 0);
    };

    Object.values(fields).forEach(field => {
      field.addEventListener('input', updatePreview);
      field.addEventListener('change', updatePreview);
    });

    const mapEl = document.getElementById('restaurantMap');
    const suggestionsEl = document.getElementById('addressSuggestions');
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');
    const coordLat = document.getElementById('coordLat');
    const coordLng = document.getElementById('coordLng');
    const coordsBox = document.getElementById('coordsBox');
    let leafletMap = null;
    let leafletMarker = null;
    let addressTimer = null;
    let leafletLoading = false;
    const leafletCallbacks = [];

    const ensureLeaflet = (callback) => {
      if (window.L) {
        callback();
        return;
      }
      leafletCallbacks.push(callback);
      if (leafletLoading) return;
      leafletLoading = true;

      const css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
      document.head.appendChild(css);

      const script = document.createElement('script');
      script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
      script.onload = () => {
        while (leafletCallbacks.length) leafletCallbacks.shift()();
      };
      document.head.appendChild(script);
    };

    const setMapPlaceholder = (message) => {
      if (!mapEl || leafletMap) return;
      mapEl.style.display = 'flex';
      mapEl.innerHTML = `<div class="map-placeholder">${message}</div>`;
    };

    const renderMap = (lat, lng, label) => {
      if (!mapEl || !isFinite(lat) || !isFinite(lng)) return;
      ensureLeaflet(() => {
        mapEl.style.display = 'block';
        if (!leafletMap) {
          const markerIcon = L.divIcon({
            className: '',
            html: '<div class="map-pin"></div>',
            iconSize: [24, 24],
            iconAnchor: [12, 24],
            popupAnchor: [0, -22]
          });
          mapEl.innerHTML = '';
          leafletMap = L.map(mapEl).setView([lat, lng], 16);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'OpenStreetMap',
            maxZoom: 19
          }).addTo(leafletMap);
          leafletMarker = L.marker([lat, lng], { icon: markerIcon }).addTo(leafletMap);
        } else {
          leafletMap.setView([lat, lng], 16);
          leafletMarker.setLatLng([lat, lng]);
        }
        if (label) leafletMarker.bindPopup(label);
        setTimeout(() => leafletMap.invalidateSize(), 80);
      });
    };

    const setAddressCoords = (lat, lng, label) => {
      const latFixed = Number(lat).toFixed(6);
      const lngFixed = Number(lng).toFixed(6);
      latInput.value = latFixed;
      lngInput.value = lngFixed;
      coordLat.textContent = latFixed;
      coordLng.textContent = lngFixed;
      coordsBox.style.display = 'block';
      renderMap(Number(latFixed), Number(lngFixed), label);
    };

    const clearAddressCoords = () => {
      latInput.value = '';
      lngInput.value = '';
      coordLat.textContent = '';
      coordLng.textContent = '';
      coordsBox.style.display = 'none';
      if (!leafletMap) setMapPlaceholder('Escribe la direccion para cargar la ubicacion en el mapa.');
    };

    const escapeAttr = (value) => String(value)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

    const geocodeAddress = (query, showSuggestions) => {
      fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&addressdetails=0&q=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
          if (!Array.isArray(data) || !data.length) {
            if (showSuggestions) suggestionsEl.style.display = 'none';
            if (!leafletMap) setMapPlaceholder('No se encontro la direccion en el mapa.');
            return;
          }

          const first = data[0];
          setAddressCoords(parseFloat(first.lat), parseFloat(first.lon), first.display_name);

          if (showSuggestions) {
            suggestionsEl.innerHTML = data.map(item => {
              const name = escapeAttr(item.display_name);
              return `<div class="address-option" onmousedown="selectAddress(event, this)" data-value="${name}" data-lat="${escapeAttr(item.lat)}" data-lng="${escapeAttr(item.lon)}">${name}</div>`;
            }).join('');
            suggestionsEl.style.display = 'block';
          }
        })
        .catch(() => {
          if (showSuggestions) suggestionsEl.style.display = 'none';
          if (!leafletMap) setMapPlaceholder('No se pudo cargar el mapa.');
        });
    };

    window.selectAddress = (event, el) => {
      event.preventDefault();
      fields.direccion.value = el.dataset.value;
      setAddressCoords(parseFloat(el.dataset.lat), parseFloat(el.dataset.lng), el.dataset.value);
      suggestionsEl.style.display = 'none';
      updatePreview();
    };

    fields.direccion.addEventListener('input', () => {
      clearTimeout(addressTimer);
      clearAddressCoords();
      const query = fields.direccion.value.trim();
      if (query.length < 4) {
        suggestionsEl.style.display = 'none';
        return;
      }
      addressTimer = setTimeout(() => geocodeAddress(query, true), 550);
    });
    fields.direccion.addEventListener('blur', () => {
      setTimeout(() => { suggestionsEl.style.display = 'none'; }, 160);
    });

    const initialLat = parseFloat(mapEl?.dataset.initialLat || '');
    const initialLng = parseFloat(mapEl?.dataset.initialLng || '');
    if (isFinite(initialLat) && isFinite(initialLng)) {
      setAddressCoords(initialLat, initialLng, fields.direccion.value.trim());
    } else if (fields.direccion.value.trim().length >= 4) {
      geocodeAddress(fields.direccion.value.trim(), false);
    }

    form.addEventListener('submit', () => {
      form.querySelector('button[type="submit"]').disabled = true;
      form.querySelector('button[type="submit"]').textContent = 'Guardando...';
    });

    updatePreview();
  </script>
</body>
</html>
