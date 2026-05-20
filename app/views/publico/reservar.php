<?php
/**
 * Página pública de reservaciones — accesible vía QR sin login.
 *
 * @var array       $restaurante
 * @var string      $pageTitle
 * @var bool        $ok       true cuando la reservación se guardó con éxito
 * @var array|null  $flash
 */
$color  = htmlspecialchars($restaurante['color_primario'] ?? '#C8102E');
$nombre = htmlspecialchars($restaurante['nombre'] ?? 'el restaurante');
$logo   = $restaurante['logo'] ?? '';
$slug   = htmlspecialchars($restaurante['slug'] ?? '');
$habilitadas = !empty($restaurante['reservas_habilitadas']);

$flashMsg  = $flash['message'] ?? null;
$flashType = $flash['type']    ?? 'info';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <style>
    :root { --cp: <?= $color ?>; }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: system-ui, -apple-system, sans-serif;
      background: #F3F4F6;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .topbar {
      width: 100%;
      background: #fff;
      border-bottom: 1px solid #E5E7EB;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 20px;
    }
    .topbar img  { width: 36px; height: 36px; border-radius: 8px; object-fit: cover; }
    .topbar-name { font-weight: 700; font-size: 1rem; color: #111827; }
    .card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      padding: 28px 24px;
      margin: 24px 16px;
      width: 100%;
      max-width: 460px;
    }
    .card-title {
      font-size: 1.15rem;
      font-weight: 700;
      color: #111827;
      margin-bottom: 4px;
    }
    .card-sub {
      font-size: .82rem;
      color: #6B7280;
      margin-bottom: 22px;
    }
    label {
      display: block;
      font-size: .82rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 4px;
    }
    input, select, textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #D1D5DB;
      border-radius: 10px;
      font-size: .9rem;
      color: #111827;
      background: #fff;
      outline: none;
      transition: border-color .15s;
      margin-bottom: 14px;
    }
    input:focus, select:focus, textarea:focus { border-color: var(--cp); }
    textarea { resize: vertical; min-height: 72px; }
    .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .btn-submit {
      width: 100%;
      padding: 13px;
      background: var(--cp);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: 6px;
      transition: opacity .15s;
    }
    .btn-submit:active { opacity: .85; }
    .alert {
      border-radius: 10px;
      padding: 12px 14px;
      margin-bottom: 16px;
      font-size: .85rem;
      font-weight: 500;
    }
    .alert-error   { background: #FEE2E2; color: #991B1B; }
    .alert-success { background: #DCFCE7; color: #166534; }
    .success-box {
      text-align: center;
      padding: 12px 0 4px;
    }
    .success-icon { font-size: 3rem; margin-bottom: 10px; }
    .success-title { font-size: 1.2rem; font-weight: 700; color: #111827; margin-bottom: 6px; }
    .success-sub   { font-size: .85rem; color: #6B7280; margin-bottom: 20px; }
    .btn-otra {
      display: inline-block;
      padding: 10px 22px;
      border: 2px solid var(--cp);
      color: var(--cp);
      border-radius: 10px;
      font-size: .88rem;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      background: transparent;
    }
    .disabled-box { text-align: center; padding: 20px 0; color: #6B7280; font-size: .9rem; }
    .required-star { color: var(--cp); }
  </style>
</head>
<body>

<div class="topbar">
  <?php if ($logo): ?>
    <img src="<?= htmlspecialchars($logo) ?>" alt="logo">
  <?php endif; ?>
  <span class="topbar-name"><?= $nombre ?></span>
</div>

<div class="card">

  <?php if (!$habilitadas): ?>
    <div class="disabled-box">
      <div style="font-size:2rem;margin-bottom:10px">🚫</div>
      <div style="font-weight:700;font-size:1rem;color:#111827;margin-bottom:6px">Reservaciones no disponibles</div>
      <div>Este restaurante no acepta reservaciones en este momento.</div>
    </div>

  <?php elseif ($ok): ?>
    <div class="success-box">
      <div class="success-icon">🎉</div>
      <div class="success-title">¡Reservación recibida!</div>
      <div class="success-sub">
        Recibirás confirmación por teléfono.<br>
        ¡Te esperamos pronto!
      </div>
      <a href="<?= BASE_URL ?>menu/<?= $slug ?>/reservar" class="btn-otra">Hacer otra reservación</a>
    </div>

  <?php else: ?>
    <div class="card-title">📅 Reserva tu mesa</div>
    <div class="card-sub">en <?= $nombre ?></div>

    <?php if ($flashMsg): ?>
      <div class="alert alert-<?= $flashType === 'error' ? 'error' : 'success' ?>">
        <?= htmlspecialchars($flashMsg) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>menu/<?= $slug ?>/guardarReserva">

      <label>Nombre <span class="required-star">*</span></label>
      <input type="text" name="nombre" placeholder="Tu nombre completo" required autocomplete="name">

      <label>Teléfono <span class="required-star">*</span></label>
      <input type="tel" name="telefono" placeholder="10 dígitos" required autocomplete="tel">

      <label>Correo electrónico <span style="font-weight:400;color:#9CA3AF">(opcional)</span></label>
      <input type="email" name="email" placeholder="tu@email.com" autocomplete="email">

      <div class="row2">
        <div>
          <label>Fecha <span class="required-star">*</span></label>
          <input type="date" name="fecha" required min="<?= date('Y-m-d') ?>">
        </div>
        <div>
          <label>Hora <span class="required-star">*</span></label>
          <input type="time" name="hora" required>
        </div>
      </div>

      <label>Número de personas</label>
      <select name="personas">
        <?php for ($i = 1; $i <= 12; $i++): ?>
          <option value="<?= $i ?>" <?= $i === 2 ? 'selected' : '' ?>><?= $i ?> persona<?= $i > 1 ? 's' : '' ?></option>
        <?php endfor; ?>
      </select>

      <label>Notas <span style="font-weight:400;color:#9CA3AF">(opcional)</span></label>
      <textarea name="notas" placeholder="Alergias, ocasión especial, preferencias de mesa…"></textarea>

      <button type="submit" class="btn-submit">Solicitar reservación</button>
    </form>
  <?php endif; ?>

</div>

</body>
</html>
