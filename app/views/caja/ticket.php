<?php
/**
 * Ticket térmico (58mm / 80mm).
 * Vista independiente: la carga PrintBridge en un iframe oculto y llama a print().
 */
$ancho   = $t['ticket']['ancho'] === '58mm' ? '58mm' : '80mm';
$tot     = $t['totales'];
$linea   = str_repeat('-', $ancho === '58mm' ? 32 : 42);
$money   = fn($n) => number_format((float)$n, 2);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket <?= htmlspecialchars($t['ticket']['folio']) ?></title>
<style>
  @page { margin: 0; }
  body { margin: 0; padding: 6px 4px; background: #fff; color: #000; }
  .ticket { font-family: "Courier New", ui-monospace, monospace; font-size: 12px; line-height: 1.35; }
  .ticket--80mm { width: 72mm; }
  .ticket--58mm { width: 48mm; font-size: 11px; }
  .c { text-align: center; }
  .b { font-weight: 700; }
  .row { display: flex; justify-content: space-between; gap: 6px; }
  .row .nom { overflow-wrap: anywhere; }
  .row .n { white-space: nowrap; font-variant-numeric: tabular-nums; }
  .sep { white-space: nowrap; overflow: hidden; }
  .sub { padding-left: 10px; font-size: .92em; }
  .grande { font-size: 1.25em; font-weight: 700; }
  .marca { border: 2px solid #000; padding: 3px; margin: 6px 0; text-align: center; font-weight: 700; }
  .no-print { margin-top: 14px; text-align: center; }
  @media print { .no-print { display: none !important; } }
</style>
</head>
<body>
<div class="ticket ticket--<?= $ancho ?>">

  <div class="c b"><?= htmlspecialchars(mb_strtoupper($t['negocio']['nombre'])) ?></div>
  <?php if ($t['negocio']['direccion']): ?>
    <div class="c"><?= htmlspecialchars($t['negocio']['direccion']) ?></div>
  <?php endif; ?>
  <?php if ($t['negocio']['telefono']): ?>
    <div class="c">Tel. <?= htmlspecialchars($t['negocio']['telefono']) ?></div>
  <?php endif; ?>

  <?php if ($t['ticket']['reimpresion']): ?>
    <div class="marca">*** REIMPRESIÓN ***</div>
  <?php endif; ?>
  <?php if ($t['ticket']['cancelado']): ?>
    <div class="marca">*** VENTA CANCELADA ***</div>
  <?php endif; ?>

  <div class="sep"><?= $linea ?></div>
  <div class="row"><span>Folio:</span><span class="n b"><?= htmlspecialchars($t['ticket']['folio']) ?></span></div>
  <div class="row">
    <span><?= date('d/m/Y H:i', strtotime($t['ticket']['fecha'])) ?></span>
    <span class="n">Caja: <?= htmlspecialchars($t['ticket']['cajero']) ?></span>
  </div>
  <?php if ($t['cliente']['nombre']): ?>
    <div class="row"><span>Cliente:</span><span class="n"><?= htmlspecialchars($t['cliente']['nombre']) ?></span></div>
  <?php endif; ?>
  <div class="sep"><?= $linea ?></div>

  <?php foreach ($t['items'] as $item): ?>
    <div class="row">
      <span class="nom"><?= (int)$item['cantidad'] ?>  <?= htmlspecialchars($item['nombre']) ?></span>
      <span class="n"><?= $money($item['subtotal']) ?></span>
    </div>
    <?php foreach ($item['modificadores'] as $mod): ?>
      <div class="row sub">
        <span class="nom">+ <?= htmlspecialchars($mod['nombre']) ?></span>
        <span class="n"><?= $mod['precio_extra'] > 0 ? $money($mod['precio_extra'] * $mod['cantidad'] * $item['cantidad']) : '' ?></span>
      </div>
    <?php endforeach; ?>
    <?php if ($item['exclusiones']): ?>
      <div class="sub">sin <?= htmlspecialchars($item['exclusiones']) ?></div>
    <?php endif; ?>
    <?php if ($item['nota']): ?>
      <div class="sub"><?= htmlspecialchars($item['nota']) ?></div>
    <?php endif; ?>
  <?php endforeach; ?>

  <div class="sep"><?= $linea ?></div>
  <div class="row"><span>Subtotal</span><span class="n"><?= $money($tot['subtotal']) ?></span></div>
  <?php if ($tot['descuento'] > 0): ?>
    <div class="row"><span>Descuento</span><span class="n">-<?= $money($tot['descuento']) ?></span></div>
  <?php endif; ?>
  <?php if ($tot['propina'] > 0): ?>
    <div class="row"><span>Propina</span><span class="n"><?= $money($tot['propina']) ?></span></div>
  <?php endif; ?>
  <div class="row grande"><span>TOTAL</span><span class="n"><?= $money($tot['total']) ?></span></div>

  <?php if ($tot['iva_habilitado']): ?>
    <div style="margin-top:6px">
      <div class="row"><span>Base gravable</span><span class="n"><?= $money($tot['base_gravable']) ?></span></div>
      <div class="row">
        <span>IVA (<?= rtrim(rtrim(number_format((float)$tot['iva_porcentaje'], 2), '0'), '.') ?>%)</span>
        <span class="n"><?= $money($tot['iva_mxn']) ?></span>
      </div>
      <div style="font-size:.85em">Precios con IVA incluido.</div>
    </div>
  <?php endif; ?>

  <div class="sep"><?= $linea ?></div>
  <?php foreach ($t['pagos'] as $pago): ?>
    <div class="row">
      <span><?= $pago['tipo'] === 'devolucion' ? 'Devolución ' : '' ?><?= htmlspecialchars($pago['etiqueta']) ?></span>
      <span class="n"><?= ($pago['tipo'] === 'devolucion' ? '-' : '') . $money($pago['monto']) ?></span>
    </div>
    <?php if ($pago['recibido'] !== null): ?>
      <div class="row"><span>Recibido</span><span class="n"><?= $money($pago['recibido']) ?></span></div>
      <div class="row b"><span>Cambio</span><span class="n"><?= $money($pago['cambio']) ?></span></div>
    <?php endif; ?>
  <?php endforeach; ?>

  <div class="sep"><?= $linea ?></div>
  <div class="c"><?= htmlspecialchars($t['leyenda'] ?: '¡Gracias por tu compra!') ?></div>
  <div class="c" style="font-size:.85em">Turno #<?= (int)$t['ticket']['turno_id'] ?></div>

  <div class="no-print">
    <button onclick="window.print()">Imprimir</button>
    <button onclick="window.close()">Cerrar</button>
  </div>
</div>
</body>
</html>
