<?php
$pageTitle = 'Cierre de turno';
require __DIR__ . '/parts/head.php';
$m = $totales['por_metodo'];
$money = fn($n) => '$' . number_format((float)$n, 2);
$horas = round((time() - strtotime($turno['abierto_at'])) / 3600, 1);
?>

<div class="contenido" style="max-width:760px">
  <h2>Cierre de turno #<?= (int)$turno['id'] ?></h2>
  <p style="color:var(--txt-2);margin-top:-10px">
    <?= htmlspecialchars($cajero['nombre']) ?> · abierto
    <?= date('d/m H:i', strtotime($turno['abierto_at'])) ?> · <?= $horas ?> h
  </p>

  <div class="cols">
    <div class="tarjeta">
      <h3 style="margin-top:0;font-size:1rem">Ventas del turno</h3>
      <div class="resumen-fila"><span>Pedidos cobrados</span><span class="n"><?= (int)$totales['pedidos_vendidos'] ?></span></div>
      <div class="resumen-fila"><span>Efectivo</span><span class="n"><?= $money($m['efectivo']) ?></span></div>
      <div class="resumen-fila"><span>Tarjeta</span><span class="n"><?= $money($m['tarjeta']) ?></span></div>
      <div class="resumen-fila"><span>Transferencia</span><span class="n"><?= $money($m['transferencia']) ?></span></div>
      <div class="resumen-fila"><span>Saldo del cliente</span><span class="n"><?= $money($m['wallet']) ?></span></div>
      <div class="resumen-fila" style="color:var(--txt-2)">
        <span>Prepagado en la app</span><span class="n"><?= $money($m['stripe_app']) ?></span>
      </div>
      <div class="resumen-fila"><span>Propinas</span><span class="n"><?= $money($totales['propinas']) ?></span></div>
      <div class="resumen-fila"><span>Descuentos</span><span class="n">−<?= number_format($totales['descuentos'], 2) ?></span></div>
      <div class="resumen-fila"><span>Cancelaciones</span><span class="n">−<?= number_format($totales['cancelado'], 2) ?></span></div>
    </div>

    <div class="tarjeta">
      <h3 style="margin-top:0;font-size:1rem">Efectivo</h3>
      <div class="resumen-fila"><span>Fondo inicial</span><span class="n"><?= $money($totales['fondo_inicial']) ?></span></div>
      <div class="resumen-fila"><span>+ Ventas en efectivo</span><span class="n"><?= $money($totales['efectivo_cobrado']) ?></span></div>
      <div class="resumen-fila"><span>− Devoluciones</span><span class="n">−<?= number_format($totales['efectivo_devuelto'], 2) ?></span></div>
      <div class="resumen-fila"><span>+ Ingresos de caja</span><span class="n"><?= $money($totales['ingresos']) ?></span></div>
      <div class="resumen-fila"><span>− Retiros</span><span class="n">−<?= number_format($totales['retiros'], 2) ?></span></div>
      <div class="resumen-fila fuerte"><span>Efectivo esperado</span><span class="n"><?= $money($totales['efectivo_esperado']) ?></span></div>
    </div>
  </div>

  <?php if ($movimientos): ?>
    <div class="tarjeta">
      <h3 style="margin-top:0;font-size:1rem">Movimientos de efectivo</h3>
      <div class="tabla-wrap">
        <table class="tabla">
          <thead><tr><th>Hora</th><th>Tipo</th><th>Motivo</th><th class="n">Monto</th></tr></thead>
          <tbody>
            <?php foreach ($movimientos as $mov): ?>
              <tr>
                <td><?= date('H:i', strtotime($mov['created_at'])) ?></td>
                <td><?= $mov['tipo'] === 'retiro' ? 'Retiro' : 'Ingreso' ?></td>
                <td><?= htmlspecialchars($mov['motivo']) ?></td>
                <td class="n"><?= ($mov['tipo'] === 'retiro' ? '−' : '+') . number_format((float)$mov['monto'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($pendientes): ?>
    <div class="aviso aviso--alerta">
      <strong>Quedan <?= count($pendientes) ?> pedido(s) de la app sin entregar.</strong>
      Al cerrar, pasan al siguiente turno tal cual:
      <?= htmlspecialchars(implode(', ', array_column(array_slice($pendientes, 0, 6), 'folio'))) ?><?= count($pendientes) > 6 ? '…' : '' ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= BASE_URL ?>rest-caja/cerrarTurno" class="tarjeta" id="formCierre">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

    <h3 style="margin-top:0;font-size:1rem">Conteo de efectivo</h3>

    <label class="campo campo--monto">
      <span>¿Cuánto efectivo contaste?</span>
      <input type="text" inputmode="decimal" name="efectivo_contado" id="contado" autocomplete="off" required>
    </label>

    <details style="margin-bottom:14px">
      <summary style="cursor:pointer;color:var(--txt-2);font-size:.85rem">Desglosar por denominación (opcional)</summary>
      <div class="cols" style="margin-top:12px">
        <?php foreach ([1000, 500, 200, 100, 50, 20, 10, 5, 2, 1] as $den): ?>
          <label class="campo" style="margin-bottom:8px">
            <span>$<?= number_format($den) ?></span>
            <input type="number" min="0" step="1" name="denominaciones[<?= $den ?>]" data-den="<?= $den ?>" placeholder="0">
          </label>
        <?php endforeach; ?>
      </div>
      <button class="btn btn--bloque" type="button" id="btnSumarDen">Usar esta suma</button>
    </details>

    <div class="aviso" id="cajaDiferencia">
      Diferencia: <strong id="difMonto">—</strong>
      <span id="difNota" style="display:block;font-size:.82rem;margin-top:4px"></span>
    </div>

    <label class="campo">
      <span>Notas del turno<?= '' ?></span>
      <textarea name="notas" rows="2" maxlength="500" id="notas"
                placeholder="Obligatorio si la diferencia pasa de <?= number_format($cfg['diferencia_caja_alerta_mxn'], 2) ?>"></textarea>
    </label>

    <div style="display:flex;gap:10px">
      <a class="btn" href="<?= BASE_URL ?>rest-caja/venta">Volver</a>
      <button class="btn btn--primario btn--xl" type="submit" style="flex:1">Cerrar turno</button>
    </div>
  </form>
</div>

<script>
(function () {
  const ESPERADO = <?= (float)$totales['efectivo_esperado'] ?>;
  const UMBRAL   = <?= (float)$cfg['diferencia_caja_alerta_mxn'] ?>;
  const contado  = document.getElementById('contado');
  const caja     = document.getElementById('cajaDiferencia');
  const monto    = document.getElementById('difMonto');
  const nota     = document.getElementById('difNota');

  function pintar() {
    const v = parseFloat(String(contado.value).replace(/,/g, ''));
    if (isNaN(v)) { monto.textContent = '—'; nota.textContent = ''; caja.className = 'aviso'; return; }

    const dif = Math.round((v - ESPERADO) * 100) / 100;
    monto.textContent = (dif > 0 ? '+' : '') + '$' + dif.toFixed(2);

    if (Math.abs(dif) <= 0.009) {
      caja.className = 'aviso aviso--ok';
      nota.textContent = 'La caja cuadra exacto.';
    } else if (Math.abs(dif) > UMBRAL) {
      caja.className = 'aviso aviso--error';
      nota.textContent = 'Pasa del margen permitido: explica en las notas a qué se debió.';
    } else {
      caja.className = 'aviso aviso--alerta';
      nota.textContent = dif > 0 ? 'Sobra dinero en la caja.' : 'Falta dinero en la caja.';
    }
  }

  contado.addEventListener('input', pintar);

  document.getElementById('btnSumarDen').addEventListener('click', () => {
    let suma = 0;
    document.querySelectorAll('[data-den]').forEach(i => {
      suma += (parseInt(i.value, 10) || 0) * parseFloat(i.dataset.den);
    });
    contado.value = suma.toFixed(2);
    pintar();
  });

  pintar();
})();
</script>

<?php require __DIR__ . '/parts/foot.php'; ?>
