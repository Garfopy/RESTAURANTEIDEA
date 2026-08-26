<?php
$pageTitle = 'Historial del turno';
require __DIR__ . '/parts/head.php';
$money = fn($n) => '$' . number_format((float)$n, 2);
?>

<div class="contenido">
  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
    <h2 style="margin:0">Ventas del turno #<?= (int)$turno['id'] ?></h2>
    <span style="flex:1"></span>
    <span class="pill"><?= (int)$totales['pedidos_vendidos'] ?> cobradas · <?= $money($totales['ventas']) ?></span>
    <a class="btn" href="<?= BASE_URL ?>rest-caja/venta">Volver a vender</a>
  </div>

  <?php if (!$ventas): ?>
    <div class="vacio"><span class="icono">🧾</span>Todavía no hay ventas en este turno.</div>
  <?php else: ?>
    <div class="tarjeta">
      <div class="tabla-wrap">
        <table class="tabla">
          <thead>
            <tr>
              <th>Folio</th><th>Hora</th><th>Origen</th><th>Cliente</th>
              <th>Pago</th><th class="n">Total</th><th>Estado</th><th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ventas as $v): ?>
              <tr>
                <td><strong><?= htmlspecialchars($v['folio']) ?></strong></td>
                <td><?= date('H:i', strtotime($v['created_at'])) ?></td>
                <td><?= $v['pedido_origen'] === 'cajero' ? 'Mostrador' : 'App' ?></td>
                <td><?= htmlspecialchars($v['cliente_nombre'] ?: '—') ?></td>
                <td><?= htmlspecialchars($v['metodo_pago'] ?? '—') ?></td>
                <td class="n"><?= number_format((float)$v['total'], 2) ?></td>
                <td>
                  <?php if ($v['estado'] === 'cancelado'): ?>
                    <span class="estado-badge no" title="<?= htmlspecialchars($v['motivo_cancelacion'] ?? '') ?>">Cancelada</span>
                  <?php else: ?>
                    <span class="estado-badge ok">Cobrada</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button class="btn btn--fantasma" type="button" style="min-height:34px;padding:4px 10px"
                          data-reimprimir="<?= (int)$v['id'] ?>">Ticket</button>
                  <?php if ($v['estado'] !== 'cancelado'): ?>
                    <button class="btn btn--fantasma" type="button" style="min-height:34px;padding:4px 10px;color:var(--error)"
                            data-cancelar="<?= (int)$v['id'] ?>" data-folio="<?= htmlspecialchars($v['folio']) ?>">Cancelar</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

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
</div>

<script>
(function () {
  document.querySelectorAll('[data-reimprimir]').forEach(b => {
    b.addEventListener('click', () => {
      window.PrintBridge.imprimirTicket(parseInt(b.dataset.reimprimir, 10), { reimpresion: true });
    });
  });

  document.querySelectorAll('[data-cancelar]').forEach(b => {
    b.addEventListener('click', async () => {
      const motivo = prompt('¿Por qué se cancela la venta ' + b.dataset.folio + '?');
      if (motivo === null) return;
      if (motivo.trim().length < 5) return alert('El motivo debe tener al menos 5 caracteres.');

      const r = await window.Caja.postJson('rest-caja/cancelarVenta/' + b.dataset.cancelar, { motivo: motivo.trim() });
      alert(r.ok ? (r.aviso || 'Venta cancelada.') : (r.error || 'No se pudo cancelar.'));
      if (r.ok) location.reload();
    });
  });
})();
</script>

<?php require __DIR__ . '/parts/foot.php'; ?>
