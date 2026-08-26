<?php
/**
 * Cabecera común de las pantallas del POS.
 * Variables esperadas: $restaurante, $pageTitle (opcional), $cajero, $turno.
 */
$rest      = $restaurante ?? [];
$titulo    = $pageTitle ?? 'Caja';
$cajeroNom = $cajero['nombre'] ?? '';
$turnoAct  = $turno ?? null;
$csrfTok   = $csrf ?? '';
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="<?= htmlspecialchars($rest['color_primario'] ?? '#A97C3F') ?>">
<title><?= htmlspecialchars($titulo) ?> — <?= htmlspecialchars($rest['nombre'] ?? 'Caja') ?></title>
<link rel="manifest" href="<?= BASE_URL ?>public/caja-manifest.json">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/caja.css">
<style>
  :root {
    --cp: <?= htmlspecialchars($rest['color_primario'] ?? '#A97C3F') ?>;
    --cs: <?= htmlspecialchars($rest['color_secundario'] ?? '#2B1B12') ?>;
  }
</style>
<script>
  window.CAJA = {
    base: <?= json_encode(BASE_URL) ?>,
    csrf: <?= json_encode($csrfTok) ?>,
    turnoId: <?= (int)($turnoAct['id'] ?? 0) ?>,
    polling: <?= (int)($cfg['pos_polling_segundos'] ?? 15) ?>
  };
</script>
<!-- `defer` conserva el orden: caja-comun define window.Caja antes de que
     lo usen las pantallas que se cargan después. -->
<script defer src="<?= BASE_URL ?>public/js/caja-comun.js"></script>
<script defer src="<?= BASE_URL ?>public/js/caja-print.js"></script>
</head>
<body>

<header class="caja-top no-print">
  <h1><?= htmlspecialchars($rest['nombre'] ?? 'Caja') ?></h1>

  <?php if ($turnoAct): ?>
    <span class="pill">Turno #<?= (int)$turnoAct['id'] ?> · desde <?= date('H:i', strtotime($turnoAct['abierto_at'])) ?></span>
  <?php endif; ?>

  <span class="sep"></span>

  <?php if (!empty($pendientes ?? 0)): ?>
    <a class="pill pill--alerta" href="<?= BASE_URL ?>rest-caja/pedidos">
      Pedidos <span class="badge"><?= (int)$pendientes ?></span>
    </a>
  <?php endif; ?>

  <span class="meta"><strong><?= htmlspecialchars($cajeroNom) ?></strong></span>

  <?php if ($turnoAct): ?>
    <a class="pill" href="<?= BASE_URL ?>rest-caja/historial">Historial</a>
    <a class="pill" href="<?= BASE_URL ?>rest-caja/cierre">Cierre</a>
  <?php endif; ?>

  <button class="pill" type="button" data-accion="bloquear" title="Bloquear pantalla (Ctrl+L)">Bloquear</button>
</header>

<?php $flashMsg = $flash ?? null; if ($flashMsg): ?>
  <div class="contenido no-print" style="padding-bottom:0">
    <div class="aviso aviso--<?= $flashMsg['type'] === 'error' ? 'error' : ($flashMsg['type'] === 'success' ? 'ok' : 'alerta') ?>">
      <?= htmlspecialchars($flashMsg['message']) ?>
    </div>
  </div>
<?php endif; ?>
