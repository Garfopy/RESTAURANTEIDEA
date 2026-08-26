<?php
$pageTitle = 'Pedidos de la app';
require __DIR__ . '/parts/head.php';
$metodos = $cfg['metodos_pago'] ?? ['efectivo'];
?>

<div class="contenido">
  <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap">
    <h2 style="margin:0">Pedidos de la app</h2>
    <span class="sep" style="flex:1"></span>
    <input class="buscador" id="buscarPedido" type="search" placeholder="Buscar folio o cliente…" style="max-width:280px">
    <button class="chip" type="button" id="btnSonido" aria-pressed="true">🔔 Aviso sonoro</button>
    <a class="btn" href="<?= BASE_URL ?>rest-caja/venta">Volver a vender</a>
  </div>

  <div class="cols">
    <div class="tarjeta">
      <h3 style="margin-top:0;font-size:1rem">Ya pagados <span class="estado-badge ok" id="nPrepagados">0</span></h3>
      <p class="sub" style="color:var(--txt-2);font-size:.82rem">Solo confirma la entrega.</p>
      <div id="listaPrepagados"><div class="vacio">Cargando…</div></div>
    </div>

    <div class="tarjeta">
      <h3 style="margin-top:0;font-size:1rem">Por cobrar en caja <span class="estado-badge esp" id="nPorCobrar">0</span></h3>
      <p class="sub" style="color:var(--txt-2);font-size:.82rem">El cliente eligió pagar al recoger.</p>
      <div id="listaPorCobrar"><div class="vacio">Cargando…</div></div>
    </div>
  </div>
</div>

<!-- ═══ Modal: detalle / cobro del pedido ═══ -->
<div class="modal" id="modalPedido" hidden>
  <div class="modal-caja">
    <h3 id="pedFolio">Pedido</h3>
    <p class="sub" id="pedCliente"></p>
    <div id="pedError" class="aviso aviso--error" hidden></div>
    <div id="pedItems"></div>

    <div class="tot-row total"><span>TOTAL</span><span class="n" id="pedTotal">$0.00</span></div>

    <div id="pedCobro" hidden>
      <div class="chips" style="margin:14px 0">
        <?php foreach ($metodos as $m):
          if ($m === 'wallet') continue; ?>
          <button class="chip" type="button" data-pedmetodo="<?= htmlspecialchars($m) ?>">
            <?= htmlspecialchars(['efectivo'=>'Efectivo','tarjeta'=>'Tarjeta','transferencia'=>'Transferencia'][$m] ?? $m) ?>
          </button>
        <?php endforeach; ?>
      </div>
      <label class="campo campo--monto" id="pedCampoRecibido">
        <span>Recibí (efectivo)</span>
        <input type="number" id="pedRecibido" min="0" step="0.01" value="0">
      </label>
      <div class="cambio-caja" id="pedCambio" hidden>Cambio<span class="n" id="pedCambioMonto">$0.00</span></div>
    </div>

    <div class="modal-acciones">
      <button class="btn" type="button" data-cerrar="modalPedido">Cerrar</button>
      <button class="btn btn--ok" type="button" id="btnAccionPedido">Entregar</button>
    </div>
  </div>
</div>

<script defer src="<?= BASE_URL ?>public/js/caja-pedidos.js"></script>

<?php require __DIR__ . '/parts/foot.php'; ?>
