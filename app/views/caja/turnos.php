<?php
$pageTitle = 'Mis turnos';
$turno = $turnoActual;
require __DIR__ . '/parts/head.php';
$money = fn($n) => '$' . number_format((float)$n, 2);
?>

<div class="contenido">
  <h2>Mis turnos cerrados</h2>

  <?php if (!$turnos): ?>
    <div class="vacio"><span class="icono">📋</span>Todavía no has cerrado ningún turno.</div>
  <?php else: ?>
    <div class="tarjeta">
      <div class="tabla-wrap">
        <table class="tabla">
          <thead>
            <tr>
              <th>#</th><th>Abierto</th><th>Cerrado</th><th class="n">Ventas</th>
              <th class="n">Esperado</th><th class="n">Contado</th><th class="n">Diferencia</th><th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($turnos as $t):
              $dif = (float)$t['diferencia']; ?>
              <tr>
                <td><strong><?= (int)$t['id'] ?></strong></td>
                <td><?= date('d/m H:i', strtotime($t['abierto_at'])) ?></td>
                <td><?= $t['cerrado_at'] ? date('d/m H:i', strtotime($t['cerrado_at'])) : '—' ?></td>
                <td class="n"><?= (int)$t['pedidos_vendidos'] ?></td>
                <td class="n"><?= number_format((float)$t['efectivo_esperado'], 2) ?></td>
                <td class="n"><?= number_format((float)$t['efectivo_contado'], 2) ?></td>
                <td class="n">
                  <?php if ((int)$t['alerta_diferencia'] === 1): ?>
                    <span class="estado-badge no"><?= ($dif > 0 ? '+' : '') . number_format($dif, 2) ?></span>
                  <?php elseif (abs($dif) > 0.009): ?>
                    <span class="estado-badge esp"><?= ($dif > 0 ? '+' : '') . number_format($dif, 2) ?></span>
                  <?php else: ?>
                    <span class="estado-badge ok">0.00</span>
                  <?php endif; ?>
                </td>
                <td><a class="btn btn--fantasma" style="min-height:34px;padding:4px 10px"
                       href="<?= BASE_URL ?>rest-caja/reporte/<?= (int)$t['id'] ?>">Ver corte</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/parts/foot.php'; ?>
