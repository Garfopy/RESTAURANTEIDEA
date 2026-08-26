<?php
$pageTitle = 'Apertura de turno';
$turno = null;
require __DIR__ . '/parts/head.php';
?>

<div class="contenido" style="max-width:520px">
  <div class="tarjeta">
    <h2>Apertura de turno</h2>
    <p style="color:var(--txt-2);margin-top:-8px">
      <?= htmlspecialchars($cajero['nombre']) ?> · <?= date('l j \d\e F, H:i') ?>
    </p>

    <?php if ($ultimo): ?>
      <div class="aviso">
        Tu turno anterior (#<?= (int)$ultimo['id'] ?>) cerró con
        <strong>$<?= number_format((float)$ultimo['efectivo_contado'], 2) ?></strong> contados
        <?php if ((float)$ultimo['diferencia'] != 0): ?>
          y una diferencia de <strong>$<?= number_format((float)$ultimo['diferencia'], 2) ?></strong>.
        <?php else: ?>
          y sin diferencias.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>rest-caja/abrirTurno">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

      <label class="campo campo--monto">
        <span>¿Con cuánto efectivo abres?</span>
        <input type="text" inputmode="decimal" name="fondo_inicial" id="fondo"
               value="0.00" autocomplete="off" required>
      </label>

      <div class="chips" style="margin-bottom:16px">
        <?php foreach ([200, 500, 1000, 1500] as $sugerido): ?>
          <button type="button" class="chip" data-fondo="<?= $sugerido ?>">$<?= number_format($sugerido) ?></button>
        <?php endforeach; ?>
      </div>

      <label class="campo">
        <span>Notas (opcional)</span>
        <input type="text" name="notas" maxlength="255" placeholder="Ej. faltan monedas de $5">
      </label>

      <button class="btn btn--primario btn--bloque btn--xl" type="submit">Abrir turno</button>
    </form>
  </div>
</div>

<script>
  document.querySelectorAll('[data-fondo]').forEach(b => {
    b.addEventListener('click', () => {
      document.getElementById('fondo').value = parseFloat(b.dataset.fondo).toFixed(2);
    });
  });
</script>

<?php require __DIR__ . '/parts/foot.php'; ?>
