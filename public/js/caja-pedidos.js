/* ============================================================
   caja-pedidos.js — cola de pedidos que llegan de la app.

   Tiempo real por polling (decisión abierta del equipo: si se adopta
   Firebase/Pusher, solo se cambia `refrescar()` por el suscriptor).
   ============================================================ */
(function () {
  'use strict';

  const { postJson, getJson, pesos, abrirModal, cerrarModal, beep } = window.Caja;
  const INTERVALO = Math.max(5, (window.CAJA.polling || 15)) * 1000;

  const $ = (id) => document.getElementById(id);

  let datos     = { prepagados: [], por_cobrar: [] };
  let ultimoId  = 0;
  let sonido    = true;
  let filtro    = '';
  let actual    = null;   // pedido abierto en el modal
  let metodo    = 'efectivo';
  let enviando  = false;

  async function refrescar() {
    const r = await getJson('rest-caja/pedidosEntrantes');
    if (!r.ok) return;

    // Solo suena si de verdad llegó algo nuevo, no en cada vuelta.
    if (ultimoId && r.ultimo_id > ultimoId && sonido) beep();
    ultimoId = r.ultimo_id;
    datos = r;
    pintar();
  }

  function coincide(p) {
    if (!filtro) return true;
    const q = filtro.toLowerCase();
    return (p.folio || '').toLowerCase().includes(q) || (p.cliente || '').toLowerCase().includes(q);
  }

  function etiquetaEstado(estado) {
    return ({
      pendiente: 'En espera de Cocina',
      en_preparacion: 'En preparación',
      listo: 'Listo para entregar'
    })[estado] || 'En proceso';
  }

  function configurarAccion(p, pagado) {
    const btn = $('btnAccionPedido');
    if (!pagado) {
      btn.textContent = 'Cobrar ahora';
      btn.disabled = false;
      return;
    }

    const listo = p.estado === 'listo';
    btn.textContent = listo ? 'Confirmar entrega' : 'Esperando a Cocina';
    btn.disabled = !listo;
  }

  function pintar() {
    pintarLista('listaPrepagados', datos.prepagados.filter(coincide), true);
    pintarLista('listaPorCobrar', datos.por_cobrar.filter(coincide), false);
    $('nPrepagados').textContent = datos.prepagados.length;
    $('nPorCobrar').textContent = datos.por_cobrar.length;
  }

  function pintarLista(id, lista, pagado) {
    const cont = $(id);
    if (!lista.length) {
      cont.innerHTML = '<div class="vacio" style="padding:28px"><span class="icono">✅</span>Sin pedidos pendientes</div>';
      return;
    }

    cont.innerHTML = '';
    lista.forEach(p => {
      const div = document.createElement('div');
      div.className = 'tarjeta';
      div.style.cssText = 'padding:12px;margin-bottom:10px;cursor:pointer';
      div.innerHTML =
        '<div style="display:flex;justify-content:space-between;align-items:center;gap:10px">' +
          '<strong class="folio"></strong><span class="n"></span>' +
        '</div>' +
        '<div style="color:var(--txt-2);font-size:.82rem;margin-top:4px" class="meta"></div>';

      div.querySelector('.folio').textContent = p.folio + ' · ' + p.cliente;
      div.querySelector('.n').textContent = pesos(p.total);
      div.querySelector('.meta').textContent =
        p.items + ' producto(s) · ' + (p.tipo || '') +
        (p.pickup_at ? ' · recoge ' + p.pickup_at.substring(11, 16) : '') +
        ' · ' + etiquetaEstado(p.estado) +
        (pagado ? ' · pagado' : ' · pendiente de cobro');

      div.addEventListener('click', () => abrirPedido(p, pagado));
      cont.appendChild(div);
    });
  }

  async function abrirPedido(p, pagado) {
    actual = { pedido: p, pagado };
    metodo = 'efectivo';
    enviando = false;

    $('pedError').hidden = true;
    $('pedFolio').textContent = p.folio;
    $('pedCliente').textContent = p.cliente + (p.telefono ? ' · ' + p.telefono : '');
    $('pedTotal').textContent = pesos(p.total);
    $('pedItems').innerHTML = '<p class="sub">Cargando detalle…</p>';
    $('pedCobro').hidden = pagado;
    configurarAccion(p, pagado);
    $('pedRecibido').value = Number(p.total).toFixed(2);
    $('pedCambio').hidden = true;

    document.querySelectorAll('[data-pedmetodo]').forEach(b => {
      b.setAttribute('aria-pressed', b.dataset.pedmetodo === 'efectivo' ? 'true' : 'false');
    });

    abrirModal('modalPedido');

    const r = await getJson('rest-caja/pedido/' + p.id);
    if (!r.ok) {
      $('pedItems').innerHTML = '<div class="aviso aviso--error">No se pudo cargar el detalle.</div>';
      return;
    }

    p.estado = r.pedido.estado;
    configurarAccion(p, pagado);

    $('pedItems').innerHTML = '';
    r.pedido.items.forEach(i => {
      const fila = document.createElement('div');
      fila.className = 'resumen-fila';
      fila.innerHTML = '<span></span><span class="n"></span>';
      fila.children[0].textContent = i.cantidad + '× ' + i.nombre +
        (i.exclusiones ? ' (sin ' + i.exclusiones + ')' : '') +
        (i.notas ? ' — ' + i.notas : '');
      fila.children[1].textContent = pesos(i.subtotal);
      $('pedItems').appendChild(fila);
    });
  }

  document.querySelectorAll('[data-pedmetodo]').forEach(b => {
    b.addEventListener('click', () => {
      document.querySelectorAll('[data-pedmetodo]').forEach(x => x.setAttribute('aria-pressed', 'false'));
      b.setAttribute('aria-pressed', 'true');
      metodo = b.dataset.pedmetodo;
      $('pedCampoRecibido').hidden = metodo !== 'efectivo';
      calcularCambio();
    });
  });

  $('pedRecibido').addEventListener('input', calcularCambio);

  function calcularCambio() {
    if (!actual || metodo !== 'efectivo') { $('pedCambio').hidden = true; return; }
    const cambio = (parseFloat($('pedRecibido').value) || 0) - actual.pedido.total;
    $('pedCambio').hidden = cambio <= 0;
    $('pedCambioMonto').textContent = pesos(cambio);
  }

  $('btnAccionPedido').addEventListener('click', async () => {
    if (!actual || enviando) return;
    enviando = true;
    const btn = $('btnAccionPedido');
    const texto = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Procesando…';

    const p = actual.pedido;
    let r;

    if (actual.pagado) {
      r = await postJson('rest-caja/entregarPedido/' + p.id, {});
    } else {
      r = await postJson('rest-caja/cobrarPedido/' + p.id, {
        pagos: [{
          metodo: metodo,
          monto: p.total,
          recibido: metodo === 'efectivo' ? (parseFloat($('pedRecibido').value) || p.total) : null
        }]
      });
    }

    enviando = false;
    btn.disabled = false;
    btn.textContent = texto;

    if (!r.ok) {
      $('pedError').textContent = r.error || 'No se pudo completar.';
      $('pedError').hidden = false;
      return;
    }

    cerrarModal('modalPedido');
    window.PrintBridge.imprimirTicket(p.id, {});
    refrescar();
  });

  $('buscarPedido').addEventListener('input', (e) => { filtro = e.target.value; pintar(); });

  $('btnSonido').addEventListener('click', () => {
    sonido = !sonido;
    $('btnSonido').setAttribute('aria-pressed', sonido ? 'true' : 'false');
    if (sonido) beep();   // el primer toque también desbloquea el audio del navegador
  });

  refrescar();
  setInterval(refrescar, INTERVALO);
})();
