<?php
/**
 * Pagina publica de reservaciones.
 *
 * @var array       $restaurante
 * @var string      $pageTitle
 * @var bool        $ok
 * @var array|null  $flash
 * @var int         $reservaId
 */
$color = htmlspecialchars($restaurante['color_primario'] ?? '#B68A48');
$nombre = htmlspecialchars($restaurante['nombre'] ?? 'el restaurante');
$logo = !empty($restaurante['logo'])
  ? BASE_URL . ltrim((string) $restaurante['logo'], '/')
  : BASE_URL . 'public/img/logo-amare.png';
$heroImage = BASE_URL . 'public/img/amare4.jpeg';
$slug = htmlspecialchars($restaurante['slug'] ?? '');
$habilitadas = !empty($restaurante['reservas_habilitadas']);
$showSuccessScreen = $habilitadas && $ok;

$flashMsg = $flash['message'] ?? null;
$flashType = $flash['type'] ?? 'info';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --brand: <?= $color ?>;
      --gold: #B68A48;
      --gold-soft: #D8B77A;
      --ivory: #F6F0E7;
      --sand: #D7C6AE;
      --ink: #070605;
      --coal: #11100E;
      --walnut: #2A1C14;
      --panel: rgba(17, 16, 14, 0.82);
      --panel-strong: rgba(9, 8, 7, 0.92);
      --line: rgba(216, 183, 122, 0.18);
      --line-soft: rgba(255, 255, 255, 0.10);
      --text-soft: rgba(246, 240, 231, 0.72);
      --text-muted: rgba(246, 240, 231, 0.54);
      --success-bg: rgba(34, 197, 94, 0.14);
      --success-fg: #DCFCE7;
      --error-bg: rgba(239, 68, 68, 0.14);
      --error-fg: #FECACA;
      --shadow-soft: 0 28px 90px rgba(0, 0, 0, 0.38);
      --shadow-gold: 0 22px 80px rgba(182, 138, 72, 0.14);
    }

    * { box-sizing: border-box; }

    html, body { margin: 0; min-height: 100%; }

    body {
      font-family: 'Inter', sans-serif;
      color: var(--ivory);
      background:
        radial-gradient(circle at top left, rgba(182, 138, 72, 0.16), transparent 30%),
        radial-gradient(circle at bottom right, rgba(182, 138, 72, 0.10), transparent 28%),
        linear-gradient(180deg, #090806 0%, #0E0C0A 42%, #15110E 100%);
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      pointer-events: none;
      opacity: 0.08;
      background-image:
        radial-gradient(circle at 1px 1px, rgba(255,255,255,.55) 1px, transparent 0);
      background-size: 26px 26px;
    }

    .page {
      position: relative;
      min-height: 100vh;
      padding: 32px 18px;
    }

    .page.page-success {
      display: flex;
      align-items: stretch;
    }

    .wrap {
      width: min(1240px, 100%);
      margin: 0 auto;
      display: grid;
      gap: 24px;
      align-items: stretch;
    }

    .wrap.wrap-success {
      width: min(980px, 100%);
      flex: 1;
      display: block;
    }

    .panel {
      position: relative;
      overflow: hidden;
      border: 1px solid var(--line);
      background: var(--panel);
      border-radius: 32px;
      box-shadow: var(--shadow-soft);
      backdrop-filter: blur(18px);
    }

    .panel::before {
      content: '';
      position: absolute;
      inset: 0;
      pointer-events: none;
      background: linear-gradient(135deg, rgba(255,255,255,0.04), transparent 30%, transparent 70%, rgba(182,138,72,0.05));
    }

    .hero {
      padding: 34px 28px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 280px;
    }

    .hero-intro,
    .hero-grid,
    .hero-media {
      position: relative;
      z-index: 1;
    }

    .brand-row {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .logo-box {
      width: clamp(140px, 18vw, 220px);
      max-width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .logo-box img {
      width: 100%;
      height: auto;
      object-fit: contain;
      display: block;
    }

    .brand-kicker {
      font-size: 11px;
      letter-spacing: .34em;
      text-transform: uppercase;
      color: var(--gold-soft);
      font-weight: 700;
      margin-bottom: 6px;
    }

    .brand-name {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2.4rem, 4vw, 4rem);
      line-height: .95;
      margin: 0;
      font-weight: 600;
    }

    .hero-copy {
      margin-top: 24px;
      max-width: 640px;
      color: var(--text-soft);
      font-size: 1rem;
      line-height: 1.9;
      font-weight: 300;
    }

    .hero-media {
      display: none;
      overflow: hidden;
      border-radius: 28px;
      border: 1px solid var(--line);
      background:
        radial-gradient(circle at top left, rgba(182, 138, 72, 0.22), transparent 36%),
        linear-gradient(135deg, rgba(255,255,255,0.05), rgba(182,138,72,0.12));
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.06);
    }

    .hero-media.image-zoom img {
      transition: transform 1.4s cubic-bezier(.2,.8,.2,1), filter .8s ease;
    }

    .hero-media.image-zoom:hover img {
      transform: scale(1.075);
      filter: saturate(1.12);
    }

    .hero-media::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(8, 7, 6, 0.10) 0%, rgba(8, 7, 6, 0.42) 100%);
      pointer-events: none;
    }

    .hero-media img {
      width: 100%;
      height: 100%;
      min-height: 100%;
      object-fit: cover;
      display: block;
    }

    .hero-grid {
      margin-top: 30px;
      display: grid;
      gap: 12px;
    }

    .hero-chip {
      border: 1px solid var(--line);
      border-radius: 22px;
      padding: 16px 18px;
      background: rgba(255,255,255,0.03);
      height: 100%;
      min-width: 0;
    }

    .hero-chip span {
      display: block;
      font-size: 11px;
      letter-spacing: .28em;
      text-transform: uppercase;
      color: var(--gold-soft);
      margin-bottom: 8px;
      font-weight: 700;
    }

    .hero-chip strong {
      display: block;
      font-size: .93rem;
      color: var(--ivory);
      font-weight: 500;
      line-height: 1.58;
      text-wrap: balance;
    }

    .form-panel {
      padding: 30px 22px;
      background: var(--panel-strong);
    }

    .form-panel--success-screen {
      min-height: calc(100vh - 64px);
      display: grid;
      place-items: center;
      padding: 48px 28px;
    }

    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 999px;
      border: 1px solid var(--line);
      background: rgba(182,138,72,0.08);
      color: var(--gold-soft);
      font-size: 11px;
      letter-spacing: .26em;
      text-transform: uppercase;
      font-weight: 700;
    }

    .form-title {
      margin: 18px 0 8px;
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2.2rem, 4vw, 3.5rem);
      line-height: .98;
      font-weight: 600;
    }

    .form-sub {
      margin: 0 0 28px;
      color: var(--text-soft);
      line-height: 1.8;
      font-size: .98rem;
      max-width: 620px;
    }

    .field-grid {
      display: grid;
      gap: 14px;
    }

    .field-grid.two {
      grid-template-columns: 1fr;
    }

    .field {
      display: block;
    }

    .field-label {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 8px;
      font-size: .78rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .16em;
      color: var(--sand);
    }

    .required-star {
      color: var(--gold-soft);
      font-weight: 700;
    }

    .field-note {
      display: block;
      margin-top: 8px;
      color: var(--text-muted);
      font-size: .78rem;
      line-height: 1.6;
    }

    .field-date {
      position: relative;
    }

    .field-date::after {
      content: 'dd/mm/aaaa';
      position: absolute;
      right: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(246,240,231,0.38);
      font-size: .82rem;
      letter-spacing: .04em;
      pointer-events: none;
      opacity: 0;
      transition: opacity .18s ease;
    }

    .field-date.is-empty::after {
      opacity: 1;
    }

    input, select, textarea {
      width: 100%;
      border: 1px solid var(--line-soft);
      background: rgba(255,255,255,0.04);
      color: var(--ivory);
      border-radius: 18px;
      padding: 16px 18px;
      font: inherit;
      outline: none;
      transition: border-color .2s ease, background .2s ease, transform .2s ease;
      box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
    }

    input::placeholder,
    textarea::placeholder {
      color: rgba(246,240,231,0.36);
    }

    input:focus,
    select:focus,
    textarea:focus {
      border-color: rgba(216,183,122,0.62);
      background: rgba(255,255,255,0.06);
      transform: translateY(-1px);
    }

    select,
    input[type="time"] {
      color-scheme: dark;
    }

    select {
      appearance: none;
      padding-right: 56px;
      background-image:
        linear-gradient(45deg, transparent 50%, rgba(246,240,231,.84) 50%),
        linear-gradient(135deg, rgba(246,240,231,.84) 50%, transparent 50%);
      background-position:
        calc(100% - 27px) calc(50% - 3px),
        calc(100% - 20px) calc(50% - 3px);
      background-size: 7px 7px, 7px 7px;
      background-repeat: no-repeat;
    }

    select option {
      background: #15110E;
      color: var(--ivory);
    }

    select option:checked,
    select option:hover {
      background: #B68A48;
      color: #090705;
    }

    textarea {
      resize: vertical;
      min-height: 110px;
    }

    .alert {
      border: 1px solid transparent;
      border-radius: 18px;
      padding: 14px 16px;
      margin-bottom: 18px;
      font-size: .9rem;
      line-height: 1.6;
    }

    .alert-error {
      background: var(--error-bg);
      border-color: rgba(248, 113, 113, 0.20);
      color: var(--error-fg);
    }

    .alert-success {
      background: var(--success-bg);
      border-color: rgba(74, 222, 128, 0.18);
      color: var(--success-fg);
    }

    .mesa-section {
      margin-top: 6px;
    }

    .mesa-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }

    .mesa-info {
      font-size: .76rem;
      color: var(--text-muted);
      white-space: nowrap;
    }

    .mesa-state {
      border: 1px solid var(--line-soft);
      background: rgba(255,255,255,0.03);
      border-radius: 22px;
      padding: 18px;
      color: var(--text-soft);
      text-align: center;
      line-height: 1.75;
    }

    .mesa-state.empty {
      background: rgba(180, 83, 9, 0.12);
      border-color: rgba(217, 119, 6, 0.18);
      color: #FDE68A;
    }

    .mesa-state.loading::before {
      content: '';
      display: inline-block;
      width: 14px;
      height: 14px;
      margin-right: 10px;
      vertical-align: -2px;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.18);
      border-top-color: var(--gold-soft);
      animation: spin .8s linear infinite;
    }

    .mesa-list {
      display: grid;
      gap: 10px;
    }

    .mesa-opt {
      width: 100%;
      text-align: left;
      border: 1px solid var(--line-soft);
      background: rgba(255,255,255,0.03);
      color: var(--ivory);
      border-radius: 20px;
      padding: 16px 18px;
      cursor: pointer;
      transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
    }

    .mesa-opt:hover {
      transform: translateY(-1px);
      border-color: rgba(216,183,122,0.48);
      box-shadow: var(--shadow-gold);
    }

    .mesa-opt.selected {
      border-color: rgba(216,183,122,0.72);
      background: rgba(182,138,72,0.12);
      box-shadow: var(--shadow-gold);
    }

    .mesa-opt .nm {
      font-size: 1rem;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .mesa-opt .cap {
      font-size: .8rem;
      color: var(--text-muted);
    }

    .btn-submit,
    .btn-secondary {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      border: 0;
      border-radius: 999px;
      padding: 16px 22px;
      font-size: .84rem;
      font-weight: 800;
      letter-spacing: .24em;
      text-transform: uppercase;
      cursor: pointer;
      text-decoration: none;
      transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
    }

    .btn-submit {
      margin-top: 20px;
      color: #090705;
      background: linear-gradient(135deg, #D8B77A 0%, #A77634 100%);
      box-shadow: 0 18px 48px rgba(182,138,72,0.24);
    }

    .btn-submit:hover:not(:disabled),
    .btn-secondary:hover {
      transform: translateY(-2px);
    }

    .btn-submit:disabled {
      opacity: .48;
      cursor: not-allowed;
      box-shadow: none;
    }

    .btn-secondary {
      width: auto;
      padding-inline: 24px;
      border: 1px solid rgba(216,183,122,0.34);
      background: transparent;
      color: var(--gold-soft);
    }

    .success-box,
    .disabled-box {
      display: grid;
      gap: 14px;
      text-align: center;
      padding: 10px 0 6px;
    }

    .success-box {
      width: min(760px, 100%);
      margin: 0 auto;
      align-content: center;
    }

    .success-mark,
    .disabled-mark {
      width: 74px;
      height: 74px;
      margin: 0 auto;
      display: grid;
      place-items: center;
      border-radius: 999px;
      font-size: 1.8rem;
      border: 1px solid var(--line);
      background: rgba(255,255,255,0.04);
      color: var(--gold-soft);
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
    }

    .success-title,
    .disabled-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2rem, 3vw, 3rem);
      line-height: 1;
      margin: 0;
      font-weight: 600;
    }

    .success-sub,
    .disabled-sub {
      margin: 0 auto;
      max-width: 520px;
      color: var(--text-soft);
      line-height: 1.9;
      font-size: .98rem;
    }

    .success-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 10px;
    }

    .cancel-link {
      display: inline-block;
      color: var(--text-muted);
      font-size: .82rem;
      margin-top: 4px;
    }

    .policy {
      margin-top: 16px;
      padding-top: 16px;
      border-top: 1px solid var(--line);
      color: var(--text-muted);
      font-size: .8rem;
      line-height: 1.75;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    @media (min-width: 940px) {
      .wrap {
        grid-template-columns: minmax(0, 1.02fr) minmax(0, .98fr);
      }

      .hero {
        padding: 44px 38px;
        display: grid;
        grid-template-rows: auto minmax(240px, 1fr) auto;
        gap: 24px;
        align-content: stretch;
      }

      .form-panel {
        padding: 42px 36px;
      }

      .form-panel--success-screen {
        padding: 64px 48px;
      }

      .field-grid.two {
        grid-template-columns: 1fr 1fr;
      }

      .hero-copy {
        max-width: none;
        margin-bottom: 0;
      }

      .hero-media {
        display: block;
        min-height: 280px;
      }

      .hero-grid {
        margin-top: 0;
        grid-template-columns: minmax(0, .96fr) minmax(0, .96fr) minmax(0, 1.18fr);
        align-items: stretch;
        gap: 10px;
      }

      .mesa-list {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 939px) {
      .mesa-header {
        align-items: flex-start;
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <main class="page<?= $showSuccessScreen ? ' page-success' : '' ?>">
    <div class="wrap<?= $showSuccessScreen ? ' wrap-success' : '' ?>">
      <?php if (!$showSuccessScreen): ?>
      <section class="panel hero">
        <div class="hero-intro">
          <div class="brand-row">
            <?php if ($logo): ?>
              <div class="logo-box">
                <img src="<?= htmlspecialchars($logo) ?>" alt="Logo de <?= $nombre ?>">
              </div>
            <?php endif; ?>
            <div>
              <div class="brand-kicker">Reserva directa</div>
              <h1 class="brand-name"><?= $nombre ?></h1>
            </div>
          </div>

          <p class="hero-copy">
            Una experiencia de reservacion pensada para llegar con calma.
            Elige fecha, horario, numero de personas y consulta la disponibilidad
            real de mesas antes de confirmar tu visita.
          </p>
        </div>

        <div class="hero-media image-zoom">
          <img
            src="<?= htmlspecialchars($heroImage) ?>"
            alt="Ambiente de <?= $nombre ?>"
            loading="lazy"
          >
        </div>

        <div class="hero-grid">
          <article class="hero-chip">
            <span>Disponibilidad</span>
            <strong>Consulta mesas disponibles en tiempo real antes de reservar.</strong>
          </article>
          <article class="hero-chip">
            <span>Confirmacion</span>
            <strong>Recibe tu referencia por correo para tener todo a la mano.</strong>
          </article>
          <article class="hero-chip">
            <span>Detalle</span>
            <strong>Agrega notas para celebrar algo especial o pedir preferencias.</strong>
          </article>
        </div>
      </section>
      <?php endif; ?>

      <section class="panel form-panel<?= $showSuccessScreen ? ' form-panel--success-screen' : '' ?>">
        <?php if (!$habilitadas): ?>
          <div class="disabled-box">
            <div class="disabled-mark">!</div>
            <h2 class="disabled-title">Reservaciones cerradas</h2>
            <p class="disabled-sub">
              Este restaurante no esta aceptando reservaciones en este momento.
              Si lo deseas, intenta nuevamente mas tarde.
            </p>
          </div>

        <?php elseif ($ok): ?>
          <div class="success-box">
            <div class="success-mark">&#10003;</div>
            <h2 class="success-title">Tu mesa ya quedo solicitada</h2>
            <p class="success-sub">
              Recibimos tu reservacion y enviamos una confirmacion al correo registrado.
              Muy pronto te esperamos.
            </p>
            <div class="success-actions">
              <a href="<?= BASE_URL ?>menu/<?= $slug ?>/reservar" class="btn-submit" style="width:auto;padding-inline:28px;">Hacer otra reserva</a>
            </div>
            <?php if (!empty($reservaId)): ?>
              <a href="<?= BASE_URL ?>menu/<?= $slug ?>/cancelarReserva/<?= (int) $reservaId ?>" class="cancel-link">
                Si necesitas cancelar esta reservacion, entra aqui
              </a>
            <?php endif; ?>
          </div>

        <?php else: ?>
          <div class="eyebrow">Sistema de reservaciones</div>
          <h2 class="form-title">Reserva tu mesa</h2>
          <p class="form-sub">
            Completa los datos, revisa la disponibilidad y selecciona la mesa que mejor se ajuste a tu visita.
          </p>

          <?php if ($flashMsg): ?>
            <div class="alert alert-<?= $flashType === 'error' ? 'error' : 'success' ?>">
              <?= htmlspecialchars($flashMsg) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="<?= BASE_URL ?>menu/<?= $slug ?>/guardarReserva" id="formReserva">
            <div class="field-grid">
              <label class="field">
                <span class="field-label">Nombre <span class="required-star">*</span></span>
                <input type="text" name="nombre" placeholder="Tu nombre completo" required autocomplete="name">
              </label>

              <div class="field-grid two">
                <label class="field">
                  <span class="field-label">Telefono <span class="required-star">*</span></span>
                  <input
                    type="tel"
                    name="telefono"
                    id="fTel"
                    placeholder="10 digitos"
                    required
                    inputmode="numeric"
                    pattern="\d{10}"
                    maxlength="10"
                    minlength="10"
                    title="Debe contener exactamente 10 digitos numericos"
                    autocomplete="tel"
                  >
                </label>

                <label class="field">
                  <span class="field-label">Correo <span class="required-star">*</span></span>
                  <input
                    type="email"
                    name="email"
                    id="fEmail"
                    placeholder="tu@email.com"
                    required
                    pattern="[^\s@]+@[^\s@]+\.[^\s@]+"
                    title="Ingresa un correo valido"
                    autocomplete="email"
                  >
                  <span class="field-note">Te enviaremos aqui tu confirmacion y recordatorio.</span>
                </label>
              </div>

              <div class="field-grid two">
                <label class="field">
                  <span class="field-label">Fecha <span class="required-star">*</span></span>
                  <div class="field-date is-empty" id="fechaField">
                    <input
                      type="text"
                      id="fFechaDisplay"
                      inputmode="numeric"
                      autocomplete="off"
                      maxlength="10"
                      title="Usa el formato dd/mm/aaaa"
                      required
                    >
                    <input type="hidden" name="fecha" id="fFecha" value="">
                  </div>
                  <span class="field-note">Escribe la fecha con formato dd/mm/aaaa.</span>
                </label>

                <label class="field">
                  <span class="field-label">Hora <span class="required-star">*</span></span>
                  <input type="time" name="hora" id="fHora" required>
                </label>
              </div>

              <label class="field">
                <span class="field-label">Numero de personas <span class="required-star">*</span></span>
                <select name="personas" id="fPersonas">
                  <?php for ($i = 1; $i <= 20; $i++): ?>
                    <option value="<?= $i ?>" <?= $i === 2 ? 'selected' : '' ?>><?= $i ?> persona<?= $i > 1 ? 's' : '' ?></option>
                  <?php endfor; ?>
                </select>
              </label>

              <div class="mesa-section">
                <div class="mesa-header">
                  <span class="field-label" style="margin-bottom:0">Mesa disponible <span class="required-star">*</span></span>
                  <span class="mesa-info" id="mesaInfo"></span>
                </div>
                <div id="mesaContainer" class="mesa-state">
                  Elige fecha, hora y personas para consultar mesas disponibles.
                </div>
                <input type="hidden" name="mesa_id" id="fMesaId" value="">
              </div>

              <label class="field">
                <span class="field-label">Notas <span style="color:var(--text-muted);font-weight:500;text-transform:none;letter-spacing:normal">(opcional)</span></span>
                <textarea name="notas" placeholder="Alergias, ocasion especial, preferencias de mesa o cualquier detalle importante"></textarea>
              </label>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit" disabled>Selecciona una mesa</button>

            <div class="policy">
              La disponibilidad se verifica con un margen operativo para cuidar cada servicio.
              Si no aparece una mesa para tu horario, prueba otro rango de tiempo o ajusta el numero de personas.
            </div>
          </form>
        <?php endif; ?>
      </section>
    </div>
  </main>

<?php if ($habilitadas && !$ok): ?>
<script>
(function() {
  const hoyISO = <?= json_encode(date('Y-m-d')) ?>;
  const fFechaDisplay = document.getElementById('fFechaDisplay');
  const fFecha = document.getElementById('fFecha');
  const fechaField = document.getElementById('fechaField');
  const fHora = document.getElementById('fHora');
  const fPersonas = document.getElementById('fPersonas');
  const fMesaId = document.getElementById('fMesaId');
  const cont = document.getElementById('mesaContainer');
  const info = document.getElementById('mesaInfo');
  const btn = document.getElementById('btnSubmit');
  const baseURL = <?= json_encode(BASE_URL . 'menu/' . $slug . '/mesasDisponibles') ?>;

  let timer = null;

  function formatFechaDisplay(iso) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(iso)) {
      return '';
    }

    const parts = iso.split('-');
    return parts[2] + '/' + parts[1] + '/' + parts[0];
  }

  function parseFechaDisplay(value) {
    const clean = value.replace(/\D/g, '').slice(0, 8);
    if (clean.length !== 8) {
      return '';
    }

    const day = clean.slice(0, 2);
    const month = clean.slice(2, 4);
    const year = clean.slice(4, 8);
    const iso = year + '-' + month + '-' + day;
    const date = new Date(iso + 'T00:00:00');

    if (Number.isNaN(date.getTime())) {
      return '';
    }

    if (date.getFullYear() !== parseInt(year, 10)
      || (date.getMonth() + 1) !== parseInt(month, 10)
      || date.getDate() !== parseInt(day, 10)) {
      return '';
    }

    return iso;
  }

  function maskFecha(value) {
    const clean = value.replace(/\D/g, '').slice(0, 8);
    let masked = clean.slice(0, 2);

    if (clean.length > 2) {
      masked += '/' + clean.slice(2, 4);
    }

    if (clean.length > 4) {
      masked += '/' + clean.slice(4, 8);
    }

    return masked;
  }

  function syncFechaField() {
    const masked = maskFecha(fFechaDisplay.value);
    fFechaDisplay.value = masked;
    fechaField.classList.toggle('is-empty', masked.length === 0);

    const iso = parseFechaDisplay(masked);
    fFecha.value = iso;

    if (!masked) {
      fFechaDisplay.setCustomValidity('');
      return false;
    }

    if (!iso) {
      fFechaDisplay.setCustomValidity('Usa el formato dd/mm/aaaa.');
      return false;
    }

    if (iso < hoyISO) {
      fFechaDisplay.setCustomValidity('La fecha no puede ser anterior a hoy.');
      return false;
    }

    fFechaDisplay.setCustomValidity('');
    return true;
  }

  function setBtn(enabled, txt) {
    btn.disabled = !enabled;
    btn.textContent = txt;
  }

  function clearMesas(msg, cls = '') {
    cont.className = 'mesa-state' + (cls ? ' ' + cls : '');
    cont.innerHTML = msg;
    fMesaId.value = '';
    info.textContent = '';
    setBtn(false, 'Selecciona una mesa');
  }

  function renderMesas(mesas, personas) {
    if (mesas.length === 0) {
      clearMesas(
        'No hay mesas disponibles para esa fecha y horario con capacidad para ' + personas + ' personas.<br><br><span style="font-size:.78rem;color:rgba(246,240,231,.62)">Prueba con otro horario o ajusta el numero de personas para encontrar una mejor opcion.</span>',
        'empty'
      );
      return;
    }

    cont.className = '';
    let html = '<div class="mesa-list">';

    mesas.forEach(function(m) {
      const zona = m.zona_nombre ? ' · ' + m.zona_nombre : '';
      html += '<button type="button" class="mesa-opt" data-id="' + m.id + '" data-nm="' + m.nombre + '">'
        + '<div class="nm">' + m.nombre + '</div>'
        + '<div class="cap">Capacidad: ' + m.capacidad + zona + '</div>'
        + '</button>';
    });

    html += '</div>';
    cont.innerHTML = html;
    info.textContent = mesas.length + ' disponible' + (mesas.length > 1 ? 's' : '');

    cont.querySelectorAll('.mesa-opt').forEach(function(btnMesa) {
      btnMesa.addEventListener('click', function() {
        cont.querySelectorAll('.mesa-opt').forEach(function(b) {
          b.classList.remove('selected');
        });
        btnMesa.classList.add('selected');
        fMesaId.value = btnMesa.dataset.id;
        setBtn(true, 'Reservar mesa ' + btnMesa.dataset.nm);
      });
    });
  }

  function buscarMesas() {
    const fecha = fFecha.value;
    const hora = fHora.value;
    const personas = parseInt(fPersonas.value, 10) || 2;

    if (!fecha || !hora) {
      clearMesas('Elige fecha, hora y personas para consultar mesas disponibles.');
      return;
    }

    cont.className = 'mesa-state loading';
    cont.innerHTML = 'Buscando mesas disponibles...';
    fMesaId.value = '';
    info.textContent = '';
    setBtn(false, 'Selecciona una mesa');

    const url = baseURL
      + '?fecha=' + encodeURIComponent(fecha)
      + '&hora=' + encodeURIComponent(hora)
      + '&personas=' + personas;

    fetch(url)
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (!d.ok) {
          clearMesas('No se pudo verificar la disponibilidad. Intenta nuevamente.');
          return;
        }
        renderMesas(d.mesas || [], personas);
      })
      .catch(function() {
        clearMesas('Error de conexion. Intenta nuevamente.');
      });
  }

  function programar() {
    clearTimeout(timer);
    timer = setTimeout(buscarMesas, 250);
  }

  fFechaDisplay.addEventListener('input', function() {
    syncFechaField();

    if (fFecha.value && fHora.value) {
      programar();
    }
  });

  fFechaDisplay.addEventListener('blur', function() {
    if (syncFechaField()) {
      if (fHora.value) {
        programar();
      }
      return;
    }

    if (fFechaDisplay.value) {
      fFechaDisplay.reportValidity();
    }
  });

  [fHora, fPersonas].forEach(function(el) {
    el.addEventListener('change', programar);
  });

  document.getElementById('formReserva').addEventListener('submit', function(e) {
    if (!syncFechaField()) {
      e.preventDefault();
      fFechaDisplay.reportValidity();
      return;
    }

    if (!fMesaId.value) {
      e.preventDefault();
      alert('Por favor selecciona una mesa.');
    }
  });

  const fTel = document.getElementById('fTel');
  fTel.addEventListener('input', function() {
    fTel.value = fTel.value.replace(/\D/g, '').slice(0, 10);
  });

  if (fFecha.value) {
    fFechaDisplay.value = formatFechaDisplay(fFecha.value);
  }

  fechaField.classList.add('is-empty');
  syncFechaField();
})();
</script>
<?php endif; ?>

</body>
</html>
