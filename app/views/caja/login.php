<?php
/**
 * Selección de cajero + PIN (decisión D8).
 * El PIN nunca identifica solo: siempre se verifica contra el cajero elegido.
 */
$rest = $restaurante ?? [];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= $bloqueada ? 'Pantalla bloqueada' : 'Entrar a caja' ?> — <?= htmlspecialchars($rest['nombre'] ?? '') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/caja.css">
<style>:root { --cp: <?= htmlspecialchars($rest['color_primario'] ?? '#A97C3F') ?>; }</style>
</head>
<body>

<div class="acceso">
  <div class="acceso-caja">
    <h1><?= htmlspecialchars($rest['nombre'] ?? 'Caja') ?></h1>
    <p class="sub">
      <?= $bloqueada ? 'Pantalla bloqueada. Teclea tu PIN para continuar.' : '¿Quién va a operar la caja?' ?>
    </p>

    <?php if (!empty($flash)): ?>
      <div class="aviso aviso--<?= $flash['type'] === 'error' ? 'error' : 'alerta' ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div id="msg" class="aviso aviso--error" hidden></div>

    <?php if (!$cajeros): ?>
      <div class="vacio">
        <span class="icono">👤</span>
        Este negocio todavía no tiene cajeros dados de alta.<br>
        Pídele a tu administrador que cree tu cuenta de cajero.
      </div>
    <?php else: ?>

      <div class="cajeros" id="cajeros">
        <?php foreach ($cajeros as $c):
          $nombre    = trim($c['nombre'] . ' ' . ($c['apellido_paterno'] ?? ''));
          $iniciales = mb_strtoupper(mb_substr($c['nombre'], 0, 1) . mb_substr($c['apellido_paterno'] ?? '', 0, 1));
          $bloq      = (int)$c['bloqueado'] === 1;
          $sinPin    = (int)$c['tiene_pin'] === 0;
          $oculto    = $bloqueada && (int)$c['id'] !== (int)$cajeroFijado;
          if ($oculto) continue;
        ?>
          <button type="button" class="cajero-btn"
                  data-id="<?= (int)$c['id'] ?>"
                  data-sinpin="<?= $sinPin ? 1 : 0 ?>"
                  data-yo="<?= (int)$c['id'] === (int)$yo ? 1 : 0 ?>"
                  aria-pressed="false"
                  <?= $bloq || ($sinPin && (int)$c['id'] !== (int)$yo) ? 'disabled' : '' ?>>
            <span class="cajero-avatar"><?= htmlspecialchars($iniciales ?: '?') ?></span>
            <span class="nom"><?= htmlspecialchars($nombre) ?></span>
            <?php if ($bloq): ?>
              <span class="estado">🔒 bloqueado</span>
            <?php elseif ($sinPin && (int)$c['id'] === (int)$yo): ?>
              <span class="estado">crear PIN</span>
            <?php elseif ($sinPin): ?>
              <span class="estado">sin PIN</span>
            <?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>

      <div id="zonaPin" <?= $bloqueada ? '' : 'hidden' ?>>
        <p class="sub" id="pinTitulo">PIN</p>
        <div class="pin-puntos" id="puntos"></div>
        <div class="teclado">
          <?php foreach ([1,2,3,4,5,6,7,8,9] as $n): ?>
            <button type="button" data-tecla="<?= $n ?>"><?= $n ?></button>
          <?php endforeach; ?>
          <button type="button" data-tecla="borrar">←</button>
          <button type="button" data-tecla="0">0</button>
          <button type="button" data-tecla="ok" class="btn--primario">✓</button>
        </div>
      </div>

    <?php endif; ?>

    <p style="margin-top:26px">
      <a class="pill" href="<?= BASE_URL ?>auth/logoutStaff/cajero">Cerrar sesión de la terminal</a>
    </p>
  </div>
</div>

<script>
(function () {
  const BASE = <?= json_encode(BASE_URL) ?>;
  const CSRF = <?= json_encode($csrf) ?>;
  const BLOQUEADA = <?= $bloqueada ? 'true' : 'false' ?>;
  const CAJERO_FIJO = <?= (int)$cajeroFijado ?>;

  const msg      = document.getElementById('msg');
  const zonaPin  = document.getElementById('zonaPin');
  const puntos   = document.getElementById('puntos');
  const titulo   = document.getElementById('pinTitulo');

  let cajeroId = BLOQUEADA ? CAJERO_FIJO : 0;
  let creando  = false;      // flujo de "definir PIN por primera vez"
  let paso     = 1;          // 1 = PIN nuevo, 2 = confirmación
  let primerPin = '';
  let pin      = '';

  function error(texto) {
    msg.textContent = texto;
    msg.hidden = !texto;
  }

  function pintar() {
    puntos.innerHTML = '';
    for (let i = 0; i < Math.max(4, pin.length); i++) {
      const d = document.createElement('span');
      d.className = 'pin-punto' + (i < pin.length ? ' lleno' : '');
      puntos.appendChild(d);
    }
  }

  document.querySelectorAll('.cajero-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.cajero-btn').forEach(b => b.setAttribute('aria-pressed', 'false'));
      btn.setAttribute('aria-pressed', 'true');
      cajeroId  = parseInt(btn.dataset.id, 10);
      creando   = btn.dataset.sinpin === '1' && btn.dataset.yo === '1';
      paso      = 1;
      primerPin = '';
      pin       = '';
      error('');
      titulo.textContent = creando ? 'Crea tu PIN (4 a 6 dígitos)' : 'PIN de ' + btn.querySelector('.nom').textContent;
      zonaPin.hidden = false;
      pintar();
    });
  });

  document.querySelectorAll('[data-tecla]').forEach(btn => {
    btn.addEventListener('click', () => tecla(btn.dataset.tecla));
  });

  document.addEventListener('keydown', (e) => {
    if (e.key >= '0' && e.key <= '9') tecla(e.key);
    else if (e.key === 'Backspace') tecla('borrar');
    else if (e.key === 'Enter') tecla('ok');
  });

  function tecla(t) {
    if (t === 'borrar') { pin = pin.slice(0, -1); error(''); return pintar(); }
    if (t === 'ok')     return enviar();
    if (pin.length >= 6) return;
    pin += t;
    pintar();
    if (!creando && pin.length >= 4) { /* el cajero decide cuándo confirmar con ✓ */ }
  }

  async function enviar() {
    if (!cajeroId) return error('Primero elige quién va a operar la caja.');
    if (pin.length < 4) return error('El PIN debe tener al menos 4 dígitos.');

    if (creando && paso === 1) {
      primerPin = pin; pin = ''; paso = 2;
      titulo.textContent = 'Confírmalo';
      error(''); return pintar();
    }

    const url  = creando ? 'rest-caja/definirPin' : 'rest-caja/pinLogin';
    const body = creando
      ? { pin: primerPin, pin_confirmacion: pin, _csrf: CSRF }
      : { cajero_id: cajeroId, pin: pin, _csrf: CSRF };

    try {
      const r = await fetch(BASE + url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify(body)
      });
      const data = await r.json();
      if (!data.ok) {
        pin = ''; paso = 1; pintar();
        return error(data.error || 'No se pudo validar el PIN.');
      }
      window.location.href = data.redirect;
    } catch (e) {
      error('Sin conexión con el servidor. Revisa la red e intenta otra vez.');
    }
  }

  if (BLOQUEADA) { titulo.textContent = 'Teclea tu PIN'; pintar(); }
})();
</script>
</body>
</html>
