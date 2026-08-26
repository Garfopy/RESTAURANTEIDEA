<?php
$pageTitle = 'Venta';
require __DIR__ . '/parts/head.php';
$propinasSugeridas = $cfg['propinas_sugeridas'] ?? [];
$metodos = $cfg['metodos_pago'] ?? ['efectivo'];
$etiquetasMetodo = [
  'efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta',
  'transferencia' => 'Transferencia', 'wallet' => 'Saldo del cliente',
];
?>

<main class="caja-venta">

  <!-- ── Catálogo ─────────────────────────────────────────── -->
  <section class="catalogo">
    <div class="catalogo-head">
      <input class="buscador" id="buscador" type="search" autocomplete="off"
             placeholder="Buscar producto o código…  (F3)">
      <button class="btn" type="button" data-modal="modalCliente">Cliente</button>
      <button class="btn" type="button" data-modal="modalMovimiento" title="Movimiento de caja (F8)">Caja</button>
    </div>

    <div class="tabs" id="tabs"></div>

    <div class="grid-productos" id="productos">
      <div class="vacio"><span class="icono">⏳</span>Cargando el menú…</div>
    </div>
  </section>

  <!-- ── Carrito ──────────────────────────────────────────── -->
  <aside class="carrito">
    <div class="carrito-head">
      <h2>Venta actual</h2>
      <button class="btn btn--fantasma" type="button" id="btnLimpiar" style="min-height:36px;padding:6px 12px">Limpiar</button>
    </div>

    <div class="carrito-items" id="lineas">
      <div class="vacio"><span class="icono">🧾</span>Toca un producto para empezar</div>
    </div>

    <div class="totales">
      <div class="tot-row"><span>Subtotal</span><span class="n" id="tSubtotal">$0.00</span></div>
      <div class="tot-row desc" id="filaDescuento" hidden><span id="tDescLabel">Descuento</span><span class="n" id="tDescuento">$0.00</span></div>
      <div class="tot-row" id="filaPropina" hidden><span>Propina</span><span class="n" id="tPropina">$0.00</span></div>
      <div class="tot-row total"><span>TOTAL</span><span class="n" id="tTotal">$0.00</span></div>
    </div>

    <div class="carrito-acciones">
      <button class="btn" type="button" data-modal="modalCupon">Cupón</button>
      <button class="btn" type="button" data-modal="modalDescuento">Descuento</button>
      <button class="btn" type="button" data-modal="modalPropina"
              <?= empty($cfg['propinas_pos_habilitadas']) ? 'disabled' : '' ?>>Propina</button>
    </div>

    <div class="carrito-cobrar">
      <button class="btn btn--primario btn--bloque btn--xl" type="button" id="btnCobrar" disabled>
        Cobrar <span id="btnCobrarMonto"></span>
      </button>
    </div>
  </aside>
</main>

<!-- ═══ Modal: modificadores del producto ═══ -->
<div class="modal" id="modalMods" hidden>
  <div class="modal-caja">
    <h3 id="modNombre">Producto</h3>
    <p class="sub" id="modPrecio"></p>
    <div id="modLista"></div>
    <label class="campo">
      <span>Nota para cocina (opcional)</span>
      <input type="text" id="modNota" maxlength="255" placeholder="Ej. sin hielo, para llevar">
    </label>
    <label class="campo">
      <span>Cantidad</span>
      <input type="number" id="modCantidad" min="1" max="99" value="1">
    </label>
    <div class="modal-acciones">
      <button class="btn" type="button" data-cerrar="modalMods">Cancelar</button>
      <button class="btn btn--primario" type="button" id="modAgregar">Agregar</button>
    </div>
  </div>
</div>

<!-- ═══ Modal: cobro ═══ -->
<div class="modal" id="modalCobro" hidden>
  <div class="modal-caja">
    <h3>Cobrar <span id="cobroTotal"></span></h3>
    <p class="sub">Puedes combinar métodos hasta cubrir el total.</p>

    <div id="cobroError" class="aviso aviso--error" hidden></div>

    <div class="chips" style="margin-bottom:16px">
      <?php foreach ($metodos as $m):
        if ($m === 'wallet' && !$walletOn) continue; ?>
        <button class="chip" type="button" data-metodo="<?= htmlspecialchars($m) ?>">
          <?= htmlspecialchars($etiquetasMetodo[$m] ?? $m) ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div id="cobroPagos"></div>

    <div class="tot-row" style="border-top:1px solid var(--linea);padding-top:10px">
      <span>Cubierto</span><span class="n" id="cobroCubierto">$0.00</span>
    </div>

    <div class="cambio-caja" id="cobroCambio" hidden>
      Cambio<span class="n" id="cobroCambioMonto">$0.00</span>
    </div>

    <div class="modal-acciones">
      <button class="btn" type="button" data-cerrar="modalCobro">Cancelar</button>
      <button class="btn btn--ok" type="button" id="btnConfirmarCobro">Cobrar</button>
    </div>
  </div>
</div>

<!-- ═══ Modal: descuento manual ═══ -->
<div class="modal" id="modalDescuento" hidden>
  <div class="modal-caja">
    <h3>Descuento manual</h3>
    <p class="sub">
      Sin autorización puedes aplicar hasta
      <strong><?= rtrim(rtrim(number_format((float)$cfg['descuento_max_cajero_pct'], 2), '0'), '.') ?>%</strong>.
    </p>

    <div id="descError" class="aviso aviso--error" hidden></div>

    <div class="chips" style="margin-bottom:14px">
      <button class="chip" type="button" data-desctipo="porcentaje" aria-pressed="true">Porcentaje</button>
      <button class="chip" type="button" data-desctipo="monto_fijo" aria-pressed="false">Monto fijo</button>
    </div>

    <label class="campo campo--monto">
      <span id="descLabel">Porcentaje a descontar</span>
      <input type="number" id="descValor" min="0" step="0.01" value="0">
    </label>

    <label class="campo">
      <span>Motivo</span>
      <input type="text" id="descMotivo" maxlength="255" placeholder="Ej. producto con demora">
    </label>

    <div id="descAutorizacion" hidden>
      <div class="aviso aviso--alerta">
        Ese descuento pasa del límite. Un administrador tiene que teclear su PIN.
        <?php if (!$hayAdminPin): ?>
          <br><strong>Ningún administrador de este negocio tiene PIN configurado todavía.</strong>
        <?php endif; ?>
      </div>
      <label class="campo">
        <span>PIN del administrador</span>
        <input type="password" id="descPin" inputmode="numeric" autocomplete="off" maxlength="6">
      </label>
      <button class="btn btn--bloque" type="button" id="btnAutorizar">Autorizar</button>
    </div>

    <div class="modal-acciones">
      <button class="btn" type="button" data-cerrar="modalDescuento">Cancelar</button>
      <button class="btn btn--primario" type="button" id="btnAplicarDescuento">Aplicar</button>
    </div>
  </div>
</div>

<!-- ═══ Modal: cupón ═══ -->
<div class="modal" id="modalCupon" hidden>
  <div class="modal-caja">
    <h3>Cupón</h3>
    <div id="cuponError" class="aviso aviso--error" hidden></div>
    <div id="cuponOk" class="aviso aviso--ok" hidden></div>
    <label class="campo">
      <span>Código</span>
      <input type="text" id="cuponCode" autocomplete="off" style="text-transform:uppercase">
    </label>
    <div class="modal-acciones">
      <button class="btn" type="button" id="btnQuitarCupon">Quitar</button>
      <button class="btn btn--primario" type="button" id="btnAplicarCupon">Aplicar</button>
    </div>
  </div>
</div>

<!-- ═══ Modal: propina ═══ -->
<div class="modal" id="modalPropina" hidden>
  <div class="modal-caja">
    <h3>Propina</h3>
    <div class="chips" style="margin-bottom:14px">
      <?php foreach ($propinasSugeridas as $pct): ?>
        <button class="chip" type="button" data-propinapct="<?= (int)$pct ?>"><?= (int)$pct ?>%</button>
      <?php endforeach; ?>
      <button class="chip" type="button" data-propinapct="0">Sin propina</button>
    </div>
    <label class="campo campo--monto">
      <span>Monto</span>
      <input type="number" id="propinaMonto" min="0" step="0.01" value="0">
    </label>
    <div class="modal-acciones">
      <button class="btn" type="button" data-cerrar="modalPropina">Cancelar</button>
      <button class="btn btn--primario" type="button" id="btnAplicarPropina">Aplicar</button>
    </div>
  </div>
</div>

<!-- ═══ Modal: cliente ═══ -->
<div class="modal" id="modalCliente" hidden>
  <div class="modal-caja">
    <h3>Cliente</h3>
    <p class="sub">Opcional. Solo hace falta para cobrar con saldo o para el ticket.</p>
    <div id="clienteError" class="aviso aviso--error" hidden></div>
    <label class="campo">
      <span>Nombre</span>
      <input type="text" id="clienteNombre" maxlength="120">
    </label>
    <label class="campo">
      <span>Teléfono</span>
      <input type="tel" id="clienteTelefono" inputmode="numeric" maxlength="20">
    </label>
    <button class="btn btn--bloque" type="button" id="btnBuscarCliente">Buscar por teléfono</button>
    <div id="clienteResultados" style="margin-top:14px"></div>
    <div class="modal-acciones">
      <button class="btn" type="button" id="btnQuitarCliente">Quitar</button>
      <button class="btn btn--primario" type="button" id="btnGuardarCliente">Usar este cliente</button>
    </div>
  </div>
</div>

<!-- ═══ Modal: movimiento de caja ═══ -->
<div class="modal" id="modalMovimiento" hidden>
  <div class="modal-caja">
    <h3>Movimiento de caja</h3>
    <p class="sub">Registra el dinero que entra o sale de la caja fuera de una venta.</p>
    <div id="movError" class="aviso aviso--error" hidden></div>
    <div class="chips" style="margin-bottom:14px">
      <button class="chip" type="button" data-movtipo="retiro" aria-pressed="true">Retiro</button>
      <button class="chip" type="button" data-movtipo="ingreso" aria-pressed="false">Ingreso</button>
    </div>
    <label class="campo campo--monto">
      <span>Monto</span>
      <input type="number" id="movMonto" min="0" step="0.01" value="0">
    </label>
    <label class="campo">
      <span>Motivo</span>
      <input type="text" id="movMotivo" maxlength="255" placeholder="Ej. pago al proveedor de refrescos">
    </label>
    <div class="modal-acciones">
      <button class="btn" type="button" data-cerrar="modalMovimiento">Cancelar</button>
      <button class="btn btn--primario" type="button" id="btnGuardarMovimiento">Registrar</button>
    </div>
  </div>
</div>

<!-- ═══ Modal: venta cobrada ═══ -->
<div class="modal" id="modalHecho" hidden>
  <div class="modal-caja" style="text-align:center">
    <h3 id="hechoFolio">Venta cobrada</h3>
    <div class="cambio-caja" id="hechoCambio" hidden>Cambio<span class="n" id="hechoCambioMonto"></span></div>
    <div class="modal-acciones">
      <button class="btn" type="button" id="btnReimprimir">Imprimir de nuevo</button>
      <button class="btn btn--primario" type="button" id="btnNuevaVenta">Nueva venta</button>
    </div>
    <button class="btn btn--fantasma btn--bloque" type="button" id="btnCancelarUltima" style="margin-top:10px">
      Cancelar esta venta
    </button>
  </div>
</div>

<script>
  window.CAJA_VENTA = {
    propinaHabilitada: <?= !empty($cfg['propinas_pos_habilitadas']) ? 'true' : 'false' ?>,
    descuentoMax: <?= (float)$cfg['descuento_max_cajero_pct'] ?>,
    anchoTicket: <?= json_encode(str_replace('mm', '', (string)$cfg['impresora_ancho_ticket'])) ?>
  };
</script>
<script defer src="<?= BASE_URL ?>public/js/caja-venta.js"></script>

<?php require __DIR__ . '/parts/foot.php'; ?>
