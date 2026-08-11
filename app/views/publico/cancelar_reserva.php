<?php
/**
 * Página pública de cancelación de reservación.
 *
 * @var array      $restaurante
 * @var string     $pageTitle
 * @var int        $reservaId
 * @var bool       $cancelada
 * @var array|null $flash
 */
$brandColor = htmlspecialchars($restaurante['color_primario'] ?? '#B68A48');
$nombre = htmlspecialchars($restaurante['nombre'] ?? 'Jungle Pizza');
$slug = htmlspecialchars($restaurante['slug'] ?? '');
$logo = !empty($restaurante['logo'])
    ? BASE_URL . ltrim((string)$restaurante['logo'], '/')
    : BASE_URL . 'base/redesign-assets/jungle-pizza-logo-420.webp';

$flashMsg = $flash['message'] ?? null;
$flashType = $flash['type'] ?? 'info';
$cancelada = $cancelada ?? false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --brand: <?= $brandColor ?>;
      --brand-soft: rgba(182,138,72,.18);
      --ink: #f6f0e7;
      --muted: rgba(246,240,231,.72);
      --line: rgba(182,138,72,.18);
      --panel: rgba(10,8,7,.86);
      --panel-solid: #110f0d;
      --danger: #ef4444;
      --danger-soft: rgba(239,68,68,.16);
      --success: #22c55e;
      --success-soft: rgba(34,197,94,.16);
      --shadow: 0 32px 90px rgba(0,0,0,.34);
    }
    * { box-sizing: border-box; }
    html, body { margin: 0; min-height: 100%; }
    body {
      font-family: 'Inter', sans-serif;
      color: var(--ink);
      background:
        radial-gradient(circle at top left, rgba(182,138,72,.18), transparent 30%),
        radial-gradient(circle at bottom right, rgba(127,29,29,.28), transparent 28%),
        linear-gradient(180deg, #090807 0%, #12100d 46%, #090807 100%);
    }
    .shell {
      min-height: 100vh;
      position: relative;
      overflow: hidden;
    }
    .shell::before {
      content: '';
      position: absolute;
      inset: 0;
      opacity: .08;
      background-image:
        linear-gradient(rgba(255,255,255,.07) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
      background-size: 34px 34px;
      pointer-events: none;
    }
    .topbar {
      position: relative;
      z-index: 2;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 22px 24px;
      border-bottom: 1px solid rgba(255,255,255,.06);
      backdrop-filter: blur(18px);
      background: rgba(8,7,6,.58);
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 14px;
      min-width: 0;
    }
    .brand img {
      width: 56px;
      height: 56px;
      object-fit: contain;
      border-radius: 16px;
      background: rgba(255,255,255,.04);
      padding: 6px;
      border: 1px solid rgba(255,255,255,.08);
    }
    .brand-copy small {
      display: block;
      color: rgba(246,240,231,.56);
      font-size: .72rem;
      letter-spacing: .26em;
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    .brand-copy strong {
      display: block;
      font-family: 'Cormorant Garamond', serif;
      font-size: 2rem;
      font-weight: 600;
      line-height: .95;
      letter-spacing: .04em;
    }
    .back-link {
      color: var(--ink);
      text-decoration: none;
      padding: 11px 16px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,.1);
      background: rgba(255,255,255,.03);
      font-size: .86rem;
      font-weight: 600;
      transition: transform .2s ease, border-color .2s ease, background .2s ease;
    }
    .back-link:hover {
      transform: translateY(-1px);
      border-color: rgba(182,138,72,.4);
      background: rgba(182,138,72,.08);
    }
    .stage {
      position: relative;
      z-index: 1;
      display: grid;
      place-items: center;
      padding: 48px 20px 72px;
    }
    .card {
      width: min(100%, 560px);
      border-radius: 32px;
      border: 1px solid var(--line);
      background: linear-gradient(180deg, rgba(17,15,13,.95) 0%, rgba(10,8,7,.94) 100%);
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    .card-header {
      padding: 32px 32px 24px;
      border-bottom: 1px solid rgba(255,255,255,.06);
      background:
        linear-gradient(120deg, rgba(182,138,72,.16), transparent 50%),
        linear-gradient(180deg, rgba(255,255,255,.02), rgba(255,255,255,0));
    }
    .eyebrow {
      color: rgba(246,240,231,.58);
      font-size: .72rem;
      letter-spacing: .34em;
      text-transform: uppercase;
      margin-bottom: 14px;
    }
    .title-row {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .title-icon {
      width: 52px;
      height: 52px;
      display: grid;
      place-items: center;
      border-radius: 16px;
      background: var(--danger-soft);
      color: #fda4af;
      font-size: 1.45rem;
      border: 1px solid rgba(239,68,68,.22);
      flex-shrink: 0;
    }
    .card-title {
      margin: 0;
      font-size: clamp(1.7rem, 4vw, 2.35rem);
      line-height: .95;
      font-family: 'Cormorant Garamond', serif;
      font-weight: 600;
      letter-spacing: .02em;
    }
    .card-sub {
      margin: 10px 0 0;
      color: var(--muted);
      font-size: .96rem;
      line-height: 1.7;
      max-width: 40ch;
    }
    .content {
      padding: 28px 32px 34px;
    }
    .alert,
    .info-box,
    .result-box {
      border-radius: 22px;
      padding: 18px 18px 18px 20px;
      border: 1px solid transparent;
    }
    .alert {
      margin-bottom: 18px;
      font-size: .92rem;
      line-height: 1.65;
    }
    .alert-error {
      background: rgba(127,29,29,.18);
      border-color: rgba(248,113,113,.24);
      color: #fecaca;
    }
    .alert-success {
      background: rgba(20,83,45,.18);
      border-color: rgba(74,222,128,.2);
      color: #dcfce7;
    }
    .info-box {
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 14px;
      align-items: start;
      margin-bottom: 24px;
      background: rgba(182,138,72,.12);
      border-color: rgba(182,138,72,.22);
      color: #f7dfb0;
    }
    .info-box strong {
      display: block;
      margin-bottom: 4px;
      color: #fde7bc;
      font-size: .95rem;
    }
    .info-mark {
      width: 34px;
      height: 34px;
      display: grid;
      place-items: center;
      border-radius: 12px;
      background: rgba(255,255,255,.08);
      color: #fde7bc;
      font-weight: 700;
    }
    .field {
      margin-bottom: 20px;
    }
    .field-label {
      display: block;
      margin-bottom: 10px;
      font-size: .8rem;
      color: rgba(246,240,231,.72);
      text-transform: uppercase;
      letter-spacing: .22em;
      font-weight: 700;
    }
    .field-input {
      width: 100%;
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(255,255,255,.04);
      color: var(--ink);
      border-radius: 18px;
      padding: 17px 18px;
      font-size: 1rem;
      outline: none;
      transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .field-input::placeholder {
      color: rgba(246,240,231,.34);
    }
    .field-input:focus {
      border-color: rgba(182,138,72,.5);
      background: rgba(255,255,255,.06);
      box-shadow: 0 0 0 4px rgba(182,138,72,.12);
    }
    .field-note {
      margin-top: 10px;
      color: rgba(246,240,231,.5);
      font-size: .85rem;
      line-height: 1.55;
    }
    .actions {
      display: flex;
      flex-direction: column;
      gap: 14px;
      margin-top: 8px;
    }
    .btn-danger,
    .btn-secondary,
    .btn-primary {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      min-height: 58px;
      border-radius: 999px;
      text-decoration: none;
      border: 1px solid transparent;
      font-size: .97rem;
      font-weight: 800;
      cursor: pointer;
      transition: transform .2s ease, opacity .2s ease, box-shadow .2s ease, border-color .2s ease, background .2s ease;
    }
    .btn-danger {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: #fff;
      box-shadow: 0 22px 45px rgba(220,38,38,.22);
    }
    .btn-primary {
      background: linear-gradient(135deg, #d8b77a 0%, #a77634 100%);
      color: #120f0d;
      box-shadow: 0 22px 45px rgba(182,138,72,.22);
    }
    .btn-secondary {
      border-color: rgba(255,255,255,.14);
      background: rgba(255,255,255,.03);
      color: var(--ink);
    }
    .btn-danger:hover,
    .btn-primary:hover,
    .btn-secondary:hover {
      transform: translateY(-1px);
    }
    .btn-secondary:hover {
      border-color: rgba(182,138,72,.36);
      background: rgba(182,138,72,.08);
    }
    .result-box {
      background: rgba(34,197,94,.1);
      border: 1px solid rgba(34,197,94,.18);
      text-align: center;
      padding: 28px 24px;
    }
    .result-badge {
      width: 72px;
      height: 72px;
      display: grid;
      place-items: center;
      margin: 0 auto 18px;
      border-radius: 22px;
      background: var(--success-soft);
      border: 1px solid rgba(74,222,128,.24);
      color: #86efac;
      font-size: 2rem;
    }
    .result-title {
      margin: 0 0 10px;
      font-family: 'Cormorant Garamond', serif;
      font-size: 2rem;
      font-weight: 600;
      line-height: 1;
    }
    .result-sub {
      margin: 0 auto 24px;
      max-width: 34ch;
      color: var(--muted);
      font-size: .96rem;
      line-height: 1.7;
    }
    @media (max-width: 720px) {
      .topbar {
        align-items: flex-start;
        flex-direction: column;
      }
      .back-link {
        width: 100%;
        justify-content: center;
      }
      .card-header,
      .content {
        padding-left: 22px;
        padding-right: 22px;
      }
      .brand img {
        width: 48px;
        height: 48px;
      }
    }
  </style>
</head>
<body>
  <div class="shell">
    <header class="topbar">
      <div class="brand">
        <img src="<?= htmlspecialchars($logo) ?>" alt="Jungle Pizza" onerror="this.onerror=null;this.src='<?= htmlspecialchars(BASE_URL . 'base/redesign-assets/jungle-pizza-logo-420.webp') ?>';">
        <div class="brand-copy">
          <small>Reserva directa</small>
          <strong><?= $nombre ?></strong>
        </div>
      </div>
      <a href="<?= BASE_URL ?>menu/<?= $slug ?>/reservar" class="back-link">Volver a reservaciones</a>
    </header>

    <main class="stage">
      <section class="card">
        <div class="card-header">
          <div class="eyebrow">Gestion de reservacion</div>
          <?php if ($cancelada): ?>
            <div class="title-row">
              <div class="title-icon" style="background:var(--success-soft);border-color:rgba(34,197,94,.22);color:#86efac;">&#10003;</div>
              <div>
                <h1 class="card-title">Reservacion cancelada</h1>
                <p class="card-sub">La solicitud se procesó correctamente. Si cambias de planes, puedes reservar otra mesa cuando quieras.</p>
              </div>
            </div>
          <?php else: ?>
            <div class="title-row">
              <div class="title-icon">&#10005;</div>
              <div>
                <h1 class="card-title">Cancelar reservacion</h1>
                <p class="card-sub">Por seguridad te pedimos el telefono con el que registraste la reserva para confirmar la cancelacion.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="content">
          <?php if ($cancelada): ?>
            <div class="result-box">
              <div class="result-badge">&#10003;</div>
              <h2 class="result-title">Todo listo</h2>
              <p class="result-sub">
                Tu reservacion fue cancelada exitosamente. Esperamos recibirte pronto en otra visita.
              </p>
              <div class="actions">
                <a href="<?= BASE_URL ?>menu/<?= $slug ?>/reservar" class="btn-primary">Hacer nueva reservacion</a>
                <a href="<?= BASE_URL ?>" class="btn-secondary">Ir al inicio</a>
              </div>
            </div>
          <?php else: ?>
            <?php if ($flashMsg): ?>
              <div class="alert alert-<?= $flashType === 'error' ? 'error' : 'success' ?>">
                <?= htmlspecialchars($flashMsg) ?>
              </div>
            <?php endif; ?>

            <div class="info-box">
              <div class="info-mark">i</div>
              <div>
                <strong>Validacion rapida</strong>
                Usa el mismo numero que capturaste al reservar. Esto evita cancelaciones por error.
              </div>
            </div>

            <form method="POST" action="<?= BASE_URL ?>menu/<?= $slug ?>/cancelarReserva/<?= (int)$reservaId ?>">
              <label class="field">
                <span class="field-label">Telefono de confirmacion</span>
                <input
                  class="field-input"
                  type="tel"
                  name="telefono"
                  placeholder="10 digitos"
                  required
                  inputmode="numeric"
                  pattern="\d{10}"
                  maxlength="10"
                  minlength="10"
                  autocomplete="tel"
                >
                <div class="field-note">Solo usaremos este dato para validar la cancelacion de tu reservacion.</div>
              </label>

              <div class="actions">
                <button type="submit" class="btn-danger">Confirmar cancelacion</button>
                <a href="<?= BASE_URL ?>menu/<?= $slug ?>/reservar" class="btn-secondary">Volver</a>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
