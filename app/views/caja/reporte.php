<?php
/** Reporte de cierre imprimible. Se abre solo al terminar el cierre. */
$rest  = $restaurante ?? [];
$money = fn($n) => '$' . number_format((float)$n, 2);
$dif   = (float)$turno['diferencia'];
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Corte de caja #<?= (int)$turno['id'] ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>public/css/caja.css">
<style>
  @media print {
    body { background: #fff; color: #000; }
    .tarjeta { border-color: #ccc; background: #fff; color: #000; }
    .resumen-fila { border-color: #ddd; }
    table.tabla th, table.tabla td { border-color: #ddd; }
  }
</style>
</head>
<body>

<div class="contenido" style="max-width:720px">
  <div class="no-print" style="display:flex;gap:10px;margin-bottom:16px">
    <a class="btn" href="<?= BASE_URL ?>rest-caja/index">Volver a caja</a>
    <button class="btn btn--primario" onclick="window.print()">Imprimir reporte</button>
  </div>

  <div class="tarjeta">
    <h2 style="margin-top:0">Corte de caja #<?= (int)$turno['id'] ?></h2>
    <p style="color:var(--txt-2);margin-top:-8px">
      <?= htmlspecialchars($rest['nombre'] ?? '') ?><br>
      Cajero: <?= htmlspecialchars(trim(($turno['cajero_nombre'] ?? '') . ' ' . ($turno['cajero_apellido'] ?? ''))) ?><br>
      Abierto: <?= date('d/m/Y H:i', strtotime($turno['abierto_at'])) ?>
      <?php if ($turno['cerrado_at']): ?>
        · Cerrado: <?= date('d/m/Y H:i', strtotime($turno['cerrado_at'])) ?>
      <?php endif; ?>
    </p>

    <h3 style="font-size:1rem">Ventas por método</h3>
    <div class="resumen-fila"><span>Efectivo</span><span class="n"><?= $money($turno['total_efectivo']) ?></span></div>
    <div class="resumen-fila"><span>Tarjeta</span><span class="n"><?= $money($turno['total_tarjeta']) ?></span></div>
    <div class="resumen-fila"><span>Transferencia</span><span class="n"><?= $money($turno['total_transferencia']) ?></span></div>
    <div class="resumen-fila"><span>Saldo del cliente</span><span class="n"><?= $money($turno['total_wallet']) ?></span></div>
    <div class="resumen-fila"><span>Prepagado en la app (informativo)</span><span class="n"><?= $money($turno['total_prepagado_app']) ?></span></div>
    <div class="resumen-fila"><span>Propinas</span><span class="n"><?= $money($turno['total_propinas']) ?></span></div>
    <div class="resumen-fila"><span>Descuentos aplicados</span><span class="n">−<?= number_format((float)$turno['total_descuentos'], 2) ?></span></div>
    <div class="resumen-fila"><span>Cancelaciones</span><span class="n">−<?= number_format((float)$turno['total_cancelado'], 2) ?></span></div>
    <div class="resumen-fila"><span>Pedidos cobrados</span><span class="n"><?= (int)$turno['pedidos_vendidos'] ?></span></div>

    <h3 style="font-size:1rem;margin-top:22px">Arqueo de efectivo</h3>
    <div class="resumen-fila"><span>Fondo inicial</span><span class="n"><?= $money($turno['fondo_inicial']) ?></span></div>
    <div class="resumen-fila"><span>Retiros</span><span class="n">−<?= number_format((float)$turno['total_retiros'], 2) ?></span></div>
    <div class="resumen-fila"><span>Ingresos de caja</span><span class="n"><?= $money($turno['total_ingresos_extra']) ?></span></div>
    <div class="resumen-fila"><span>Efectivo esperado</span><span class="n"><?= $money($turno['efectivo_esperado']) ?></span></div>
    <div class="resumen-fila"><span>Efectivo contado</span><span class="n"><?= $money($turno['efectivo_contado']) ?></span></div>
    <div class="resumen-fila fuerte">
      <span>Diferencia</span>
      <span class="n"><?= ($dif > 0 ? '+' : '') . '$' . number_format($dif, 2) ?></span>
    </div>

    <?php if ((int)$turno['alerta_diferencia'] === 1): ?>
      <div class="aviso aviso--error" style="margin-top:12px">
        Diferencia fuera del margen permitido. Queda marcada para revisión del administrador.
      </div>
    <?php endif; ?>

    <?php if ((int)$turno['pedidos_pendientes_al_cierre'] > 0): ?>
      <div class="aviso aviso--alerta" style="margin-top:12px">
        Se dejaron <?= (int)$turno['pedidos_pendientes_al_cierre'] ?> pedido(s) de la app sin entregar,
        disponibles para el siguiente turno.
      </div>
    <?php endif; ?>

    <?php if (!empty($turno['notas'])): ?>
      <p style="margin-top:14px"><strong>Notas:</strong> <?= nl2br(htmlspecialchars($turno['notas'])) ?></p>
    <?php endif; ?>
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

  <?php if ($ventas): ?>
    <div class="tarjeta">
      <h3 style="margin-top:0;font-size:1rem">Ventas del turno (<?= count($ventas) ?>)</h3>
      <div class="tabla-wrap">
        <table class="tabla">
          <thead><tr><th>Folio</th><th>Hora</th><th>Origen</th><th>Pago</th><th class="n">Total</th><th>Estado</th></tr></thead>
          <tbody>
            <?php foreach ($ventas as $v): ?>
              <tr>
                <td><?= htmlspecialchars($v['folio']) ?></td>
                <td><?= date('H:i', strtotime($v['created_at'])) ?></td>
                <td><?= $v['pedido_origen'] === 'cajero' ? 'Mostrador' : 'App' ?></td>
                <td><?= htmlspecialchars($v['metodo_pago'] ?? '—') ?></td>
                <td class="n"><?= number_format((float)$v['total'], 2) ?></td>
                <td>
                  <?php if ($v['estado'] === 'cancelado'): ?>
                    <span class="estado-badge no">Cancelada</span>
                  <?php else: ?>
                    <span class="estado-badge ok">Cobrada</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
