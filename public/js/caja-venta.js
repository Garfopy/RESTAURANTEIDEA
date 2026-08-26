/* ============================================================
   caja-venta.js — pantalla de venta del POS.

   El carrito vive aquí y en sessionStorage (un F5 accidental no lo borra).
   Los totales que se pintan son solo para el cajero: el servidor los
   recalcula desde cero al cobrar y su cálculo es el que manda.
   ============================================================ */
(function () {
  'use strict';

  const { postJson, getJson, pesos, abrirModal, cerrarModal } = window.Caja;
  const CFG = window.CAJA_VENTA || {};
  const LLAVE_CARRITO = 'caja_carrito_v1';

  // ── Estado ────────────────────────────────────────────────
  let catalogo   = { categorias: [], platillos: [] };
  let categoria  = 0;          // 0 = todas
  let busqueda   = '';
  let carrito    = [];
  let descuento  = { tipo: 'porcentaje', valor: 0, motivo: '', token: null };
  let cupon      = null;
  let propina    = 0;
  let cliente    = null;
  let seleccion  = null;       // uid de la línea seleccionada
  let productoModal = null;
  let pagos      = [];
  let cobrando   = false;
  let ultimaVenta = null;

  const $ = (id) => document.getElementById(id);

  // ── Persistencia del carrito ──────────────────────────────
  function guardar() {
    try {
      sessionStorage.setItem(LLAVE_CARRITO, JSON.stringify({ carrito, descuento, cupon, propina, cliente }));
    } catch (e) { /* modo privado: seguimos sin persistencia */ }
  }

  function restaurar() {
    try {
      const crudo = sessionStorage.getItem(LLAVE_CARRITO);
      if (!crudo) return;
      const d = JSON.parse(crudo);
      carrito   = d.carrito || [];
      descuento = d.descuento || descuento;
      cupon     = d.cupon || null;
      propina   = d.propina || 0;
      cliente   = d.cliente || null;
    } catch (e) { /* dato corrupto: se ignora */ }
  }

  function limpiarVenta() {
    carrito = []; descuento = { tipo: 'porcentaje', valor: 0, motivo: '', token: null };
    cupon = null; propina = 0; cliente = null; seleccion = null; pagos = [];
    guardar(); pintarCarrito();
  }

  // ── Catálogo ──────────────────────────────────────────────
  async function cargarCatalogo() {
    const data = await getJson('rest-caja/catalogo');
    if (!data.ok) {
      $('productos').innerHTML =
        '<div class="vacio"><span class="icono">⚠️</span>No se pudo cargar el menú.' +
        '<br><button class="btn" style="margin-top:12px" onclick="location.reload()">Reintentar</button></div>';
      return;
    }
    catalogo = data;
    pintarTabs();
    pintarProductos();
  }

  function pintarTabs() {
    const tabs = $('tabs');
    tabs.innerHTML = '';
    const todas = [{ id: 0, nombre: 'Todo' }].concat(
      catalogo.categorias.slice().sort((a, b) => a.orden - b.orden)
    );
    todas.forEach(cat => {
      const b = document.createElement('button');
      b.className = 'tab';
      b.textContent = cat.nombre;
      b.setAttribute('aria-selected', cat.id === categoria ? 'true' : 'false');
      b.addEventListener('click', () => { categoria = cat.id; pintarTabs(); pintarProductos(); });
      tabs.appendChild(b);
    });
  }

  function pintarProductos() {
    const cont = $('productos');
    const q = busqueda.trim().toLowerCase();

    const lista = catalogo.platillos.filter(p => {
      if (categoria && p.categoria_id !== categoria) return false;
      if (!q) return true;
      return p.nombre.toLowerCase().includes(q) || (p.codigo || '').toLowerCase().includes(q);
    });

    if (!lista.length) {
      cont.innerHTML = '<div class="vacio"><span class="icono">🔍</span>' +
        (q ? 'Ningún producto coincide con «' + q + '»' : 'Esta categoría no tiene productos') + '</div>';
      return;
    }

    cont.innerHTML = '';
    lista.forEach(p => {
      const b = document.createElement('button');
      b.className = 'producto';
      b.type = 'button';
      b.innerHTML =
        '<span class="nom"></span>' +
        '<span><span class="precio"></span>' + (p.codigo ? ' <span class="cod"></span>' : '') + '</span>';
      b.querySelector('.nom').textContent = p.nombre;
      b.querySelector('.precio').textContent = pesos(p.precio);
      if (p.codigo) b.querySelector('.cod').textContent = p.codigo;
      b.addEventListener('click', () => elegirProducto(p));
      cont.appendChild(b);
    });
  }

  // ── Alta de línea ─────────────────────────────────────────
  function elegirProducto(p) {
    if (!p.modificadores.length) return agregar(p, 1, [], null);

    productoModal = p;
    $('modNombre').textContent = p.nombre;
    $('modPrecio').textContent = pesos(p.precio);
    $('modCantidad').value = 1;
    $('modNota').value = '';

    const lista = $('modLista');
    lista.innerHTML = '';
    const grupos = { opcion: 'Opciones', extra: 'Extras', sin: 'Quitar ingredientes' };

    Object.keys(grupos).forEach(tipo => {
      const mods = p.modificadores.filter(m => m.tipo === tipo);
      if (!mods.length) return;

      const t = document.createElement('p');
      t.className = 'sub';
      t.style.marginBottom = '8px';
      t.textContent = grupos[tipo];
      lista.appendChild(t);

      const chips = document.createElement('div');
      chips.className = 'chips';
      chips.style.marginBottom = '16px';
      mods.forEach(m => {
        const c = document.createElement('button');
        c.className = 'chip';
        c.type = 'button';
        c.dataset.mod = m.id;
        c.setAttribute('aria-pressed', 'false');
        c.textContent = m.nombre + (m.precio_extra > 0 ? '  +' + pesos(m.precio_extra) : '');
        c.addEventListener('click', () => {
          c.setAttribute('aria-pressed', c.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
        });
        chips.appendChild(c);
      });
      lista.appendChild(chips);
    });

    abrirModal('modalMods');
  }

  $('modAgregar').addEventListener('click', () => {
    const elegidos = [];
    document.querySelectorAll('#modLista .chip[aria-pressed="true"]').forEach(c => {
      const m = productoModal.modificadores.find(x => x.id === parseInt(c.dataset.mod, 10));
      if (m) elegidos.push({ modificador_id: m.id, nombre: m.nombre, precio_extra: m.precio_extra, cantidad: 1 });
    });
    agregar(productoModal, parseInt($('modCantidad').value, 10) || 1, elegidos, $('modNota').value.trim() || null);
    cerrarModal('modalMods');
  });

  function agregar(p, cantidad, modificadores, notas) {
    const extra = modificadores.reduce((s, m) => s + (m.precio_extra || 0) * (m.cantidad || 1), 0);
    carrito.push({
      uid: 'l' + Date.now() + Math.random().toString(16).slice(2, 6),
      platillo_id: p.id,
      nombre: p.nombre,
      precio_unit: Math.round((p.precio + extra) * 100) / 100,
      cantidad: Math.max(1, Math.min(99, cantidad)),
      modificadores: modificadores,
      notas: notas
    });
    guardar();
    pintarCarrito();
  }

  // ── Carrito y totales ─────────────────────────────────────
  function subtotal() {
    return Math.round(carrito.reduce((s, l) => s + l.precio_unit * l.cantidad, 0) * 100) / 100;
  }

  function descuentoMonto() {
    const sub = subtotal();
    let total = 0;

    if (cupon) {
      total += cupon.tipo === 'porcentaje'
        ? Math.round(sub * cupon.valor) / 100
        : Math.min(cupon.valor, sub);
    }
    if (descuento.valor > 0) {
      total += descuento.tipo === 'porcentaje'
        ? Math.round(sub * Math.min(descuento.valor, 100)) / 100
        : Math.min(descuento.valor, sub);
    }
    return Math.round(Math.min(total, sub) * 100) / 100;
  }

  const totalVenta = () => Math.round((subtotal() - descuentoMonto() + propina) * 100) / 100;

  function pintarCarrito() {
    const cont = $('lineas');

    if (!carrito.length) {
      cont.innerHTML = '<div class="vacio"><span class="icono">🧾</span>Toca un producto para empezar</div>';
    } else {
      cont.innerHTML = '';
      carrito.forEach(l => {
        const div = document.createElement('div');
        div.className = 'linea';
        div.setAttribute('aria-selected', l.uid === seleccion ? 'true' : 'false');
        div.innerHTML =
          '<span class="nom"></span><span class="imp"></span>' +
          '<span class="det"></span>' +
          '<div class="linea-acciones">' +
            '<button type="button" data-acc="menos">−</button>' +
            '<button type="button" data-acc="mas">+</button>' +
            '<button type="button" data-acc="quitar">Quitar</button>' +
          '</div>';
        div.querySelector('.nom').textContent = l.cantidad + '× ' + l.nombre;
        div.querySelector('.imp').textContent = pesos(l.precio_unit * l.cantidad);

        const detalle = [];
        l.modificadores.forEach(m => detalle.push('+ ' + m.nombre));
        if (l.notas) detalle.push('“' + l.notas + '”');
        div.querySelector('.det').textContent = detalle.join(' · ');

        div.addEventListener('click', (e) => {
          seleccion = l.uid;
          const acc = e.target.dataset ? e.target.dataset.acc : null;
          if (acc === 'mas')    l.cantidad = Math.min(99, l.cantidad + 1);
          if (acc === 'menos')  l.cantidad = Math.max(1, l.cantidad - 1);
          if (acc === 'quitar') carrito = carrito.filter(x => x.uid !== l.uid);
          guardar();
          pintarCarrito();
        });

        cont.appendChild(div);
      });
    }

    const sub = subtotal(), desc = descuentoMonto(), tot = totalVenta();
    $('tSubtotal').textContent = pesos(sub);
    $('tTotal').textContent    = pesos(tot);

    $('filaDescuento').hidden = desc <= 0;
    $('tDescuento').textContent = '−' + pesos(desc);
    $('tDescLabel').textContent = cupon ? 'Descuento (' + cupon.code + ')' : 'Descuento';

    $('filaPropina').hidden = propina <= 0;
    $('tPropina').textContent = pesos(propina);

    $('btnCobrar').disabled = !carrito.length;
    $('btnCobrarMonto').textContent = carrito.length ? pesos(tot) : '';
  }

  // ── Cobro ─────────────────────────────────────────────────
  function abrirCobro() {
    if (!carrito.length) return;
    pagos = [];
    $('cobroError').hidden = true;
    $('cobroTotal').textContent = pesos(totalVenta());
    pintarPagos();
    abrirModal('modalCobro');
  }

  const cubierto = () => Math.round(pagos.reduce((s, p) => s + (parseFloat(p.monto) || 0), 0) * 100) / 100;
  const restante = () => Math.round((totalVenta() - cubierto()) * 100) / 100;

  document.querySelectorAll('[data-metodo]').forEach(b => {
    b.addEventListener('click', () => {
      const falta = restante();
      if (falta <= 0) return;
      pagos.push({ metodo: b.dataset.metodo, monto: falta, recibido: falta, referencia: '' });
      pintarPagos();
    });
  });

  function pintarPagos() {
    const cont = $('cobroPagos');
    cont.innerHTML = '';

    pagos.forEach((p, i) => {
      const caja = document.createElement('div');
      caja.className = 'tarjeta';
      caja.style.padding = '12px';

      const titulo = document.createElement('div');
      titulo.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin-bottom:8px';
      titulo.innerHTML = '<strong></strong>';
      titulo.querySelector('strong').textContent = etiqueta(p.metodo);

      const quitar = document.createElement('button');
      quitar.className = 'btn btn--fantasma';
      quitar.type = 'button';
      quitar.style.cssText = 'min-height:32px;padding:4px 10px';
      quitar.textContent = 'Quitar';
      quitar.addEventListener('click', () => { pagos.splice(i, 1); pintarPagos(); });
      titulo.appendChild(quitar);
      caja.appendChild(titulo);

      caja.appendChild(campoNumero('Monto', p.monto, (v) => { p.monto = v; pintarPagos(); }));

      if (p.metodo === 'efectivo') {
        caja.appendChild(campoNumero('Recibí', p.recibido, (v) => { p.recibido = v; pintarPagos(); }));

        const chips = document.createElement('div');
        chips.className = 'chips';
        [Math.ceil(p.monto), 50, 100, 200, 500].forEach(v => {
          if (v < p.monto) return;
          const c = document.createElement('button');
          c.className = 'chip'; c.type = 'button';
          c.textContent = v === Math.ceil(p.monto) ? 'Exacto' : pesos(v);
          c.addEventListener('click', () => { p.recibido = v; pintarPagos(); });
          chips.appendChild(c);
        });
        caja.appendChild(chips);
      } else if (p.metodo === 'tarjeta' || p.metodo === 'transferencia') {
        caja.appendChild(campoTexto('Referencia (opcional)', p.referencia, (v) => { p.referencia = v; }));
      } else if (p.metodo === 'wallet' && (!cliente || !cliente.mobile_usuario_id)) {
        const aviso = document.createElement('div');
        aviso.className = 'aviso aviso--alerta';
        aviso.textContent = 'Para cobrar con saldo primero identifica al cliente en el botón «Cliente».';
        caja.appendChild(aviso);
      }

      cont.appendChild(caja);
    });

    const falta = restante();
    $('cobroCubierto').textContent = pesos(cubierto()) + ' / ' + pesos(totalVenta());

    const cambio = pagos
      .filter(p => p.metodo === 'efectivo')
      .reduce((s, p) => s + Math.max(0, (parseFloat(p.recibido) || 0) - (parseFloat(p.monto) || 0)), 0);

    $('cobroCambio').hidden = cambio <= 0;
    $('cobroCambioMonto').textContent = pesos(cambio);
    $('btnConfirmarCobro').disabled = Math.abs(falta) > 0.011 || !pagos.length || cobrando;
  }

  function campoNumero(etiquetaTxt, valor, alCambiar) {
    const l = document.createElement('label');
    l.className = 'campo campo--monto';
    l.innerHTML = '<span></span><input type="number" min="0" step="0.01">';
    l.querySelector('span').textContent = etiquetaTxt;
    const input = l.querySelector('input');
    input.value = (parseFloat(valor) || 0).toFixed(2);
    input.addEventListener('change', () => alCambiar(Math.round((parseFloat(input.value) || 0) * 100) / 100));
    return l;
  }

  function campoTexto(etiquetaTxt, valor, alCambiar) {
    const l = document.createElement('label');
    l.className = 'campo';
    l.innerHTML = '<span></span><input type="text" maxlength="120">';
    l.querySelector('span').textContent = etiquetaTxt;
    const input = l.querySelector('input');
    input.value = valor || '';
    input.addEventListener('input', () => alCambiar(input.value));
    return l;
  }

  function etiqueta(m) {
    return { efectivo: 'Efectivo', tarjeta: 'Tarjeta', transferencia: 'Transferencia', wallet: 'Saldo del cliente' }[m] || m;
  }

  $('btnConfirmarCobro').addEventListener('click', async () => {
    if (cobrando) return;
    cobrando = true;
    const btn = $('btnConfirmarCobro');
    const textoOriginal = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Cobrando…';

    const cuerpo = {
      client_uuid: uuid(),
      items: carrito.map(l => ({
        platillo_id: l.platillo_id,
        cantidad: l.cantidad,
        notas: l.notas,
        modificadores: l.modificadores.map(m => ({ modificador_id: m.modificador_id, cantidad: m.cantidad }))
      })),
      descuento: { tipo: descuento.tipo, valor: descuento.valor, motivo: descuento.motivo, autorizacion_token: descuento.token },
      cupon_code: cupon ? cupon.code : '',
      propina_mxn: propina,
      cliente: cliente || {},
      pagos: pagos.map(p => ({
        metodo: p.metodo,
        monto: parseFloat(p.monto) || 0,
        recibido: p.metodo === 'efectivo' ? (parseFloat(p.recibido) || 0) : null,
        referencia: p.referencia || null
      }))
    };

    const r = await postJson('rest-caja/cobrar', cuerpo);
    cobrando = false;
    btn.disabled = false;
    btn.textContent = textoOriginal;

    if (!r.ok) {
      $('cobroError').textContent = r.error || 'No se pudo cobrar.';
      $('cobroError').hidden = false;
      return;
    }

    ultimaVenta = r;
    cerrarModal('modalCobro');
    limpiarVenta();

    $('hechoFolio').textContent = 'Cobrado · ' + r.folio;
    $('hechoCambio').hidden = !(r.cambio > 0);
    $('hechoCambioMonto').textContent = pesos(r.cambio || 0);
    abrirModal('modalHecho');

    window.PrintBridge.imprimirTicket(r.pedido_id, { ancho: CFG.anchoTicket });
  });

  /** UUID por venta: es lo que evita cobrar dos veces con un doble clic. */
  function uuid() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
      const r = Math.random() * 16 | 0;
      return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
  }

  // ── Modal: venta cobrada ──────────────────────────────────
  $('btnNuevaVenta').addEventListener('click', () => cerrarModal('modalHecho'));

  $('btnReimprimir').addEventListener('click', () => {
    if (ultimaVenta) window.PrintBridge.imprimirTicket(ultimaVenta.pedido_id, { ancho: CFG.anchoTicket, reimpresion: true });
  });

  $('btnCancelarUltima').addEventListener('click', async () => {
    if (!ultimaVenta) return;
    const motivo = prompt('¿Por qué se cancela la venta ' + ultimaVenta.folio + '?');
    if (motivo === null) return;
    if (motivo.trim().length < 5) return alert('El motivo debe tener al menos 5 caracteres.');

    const r = await postJson('rest-caja/cancelarVenta/' + ultimaVenta.pedido_id, { motivo: motivo.trim() });
    alert(r.ok ? (r.aviso || 'Venta cancelada.') : (r.error || 'No se pudo cancelar.'));
    if (r.ok) { ultimaVenta = null; cerrarModal('modalHecho'); }
  });

  // ── Descuento ─────────────────────────────────────────────
  document.querySelectorAll('[data-desctipo]').forEach(b => {
    b.addEventListener('click', () => {
      document.querySelectorAll('[data-desctipo]').forEach(x => x.setAttribute('aria-pressed', 'false'));
      b.setAttribute('aria-pressed', 'true');
      $('descLabel').textContent = b.dataset.desctipo === 'porcentaje' ? 'Porcentaje a descontar' : 'Monto a descontar';
    });
  });

  $('descValor').addEventListener('input', () => {
    const tipo = document.querySelector('[data-desctipo][aria-pressed="true"]').dataset.desctipo;
    const valor = parseFloat($('descValor').value) || 0;
    const sub = subtotal();
    const pct = tipo === 'porcentaje' ? valor : (sub > 0 ? (valor / sub) * 100 : 0);
    $('descAutorizacion').hidden = pct <= CFG.descuentoMax + 0.001;
  });

  $('btnAutorizar').addEventListener('click', async () => {
    const r = await postJson('rest-caja/autorizarPin', { pin: $('descPin').value });
    if (!r.ok) {
      $('descError').textContent = r.error || 'PIN incorrecto.';
      $('descError').hidden = false;
      return;
    }
    descuento.token = r.token;
    $('descPin').value = '';
    $('descError').hidden = true;
    $('descAutorizacion').innerHTML = '<div class="aviso aviso--ok">Descuento autorizado.</div>';
  });

  $('btnAplicarDescuento').addEventListener('click', () => {
    const tipo = document.querySelector('[data-desctipo][aria-pressed="true"]').dataset.desctipo;
    descuento.tipo = tipo;
    descuento.valor = parseFloat($('descValor').value) || 0;
    descuento.motivo = $('descMotivo').value.trim();
    guardar();
    pintarCarrito();
    cerrarModal('modalDescuento');
  });

  // ── Cupón ─────────────────────────────────────────────────
  $('btnAplicarCupon').addEventListener('click', async () => {
    const code = $('cuponCode').value.trim().toUpperCase();
    const r = await postJson('rest-caja/validarCupon', { code });
    $('cuponError').hidden = true; $('cuponOk').hidden = true;

    if (!r.ok) {
      $('cuponError').textContent = r.error || 'Cupón no válido.';
      $('cuponError').hidden = false;
      return;
    }
    cupon = r.cupon;
    $('cuponOk').textContent = 'Aplicado: ' + cupon.titulo;
    $('cuponOk').hidden = false;
    guardar(); pintarCarrito();
  });

  $('btnQuitarCupon').addEventListener('click', () => {
    cupon = null; $('cuponCode').value = '';
    $('cuponOk').hidden = true; $('cuponError').hidden = true;
    guardar(); pintarCarrito(); cerrarModal('modalCupon');
  });

  // ── Propina ───────────────────────────────────────────────
  document.querySelectorAll('[data-propinapct]').forEach(b => {
    b.addEventListener('click', () => {
      const pct = parseInt(b.dataset.propinapct, 10);
      const base = subtotal() - descuentoMonto();
      $('propinaMonto').value = (Math.round(base * pct) / 100).toFixed(2);
    });
  });

  $('btnAplicarPropina').addEventListener('click', () => {
    propina = Math.max(0, Math.round((parseFloat($('propinaMonto').value) || 0) * 100) / 100);
    guardar(); pintarCarrito(); cerrarModal('modalPropina');
  });

  // ── Cliente ───────────────────────────────────────────────
  $('btnBuscarCliente').addEventListener('click', async () => {
    const tel = $('clienteTelefono').value.replace(/\D+/g, '');
    const cont = $('clienteResultados');
    $('clienteError').hidden = true;
    cont.innerHTML = '<p class="sub">Buscando…</p>';

    const r = await getJson('rest-caja/buscarCliente?telefono=' + encodeURIComponent(tel));
    if (!r.ok) {
      cont.innerHTML = '';
      $('clienteError').textContent = r.error || 'No se pudo buscar.';
      $('clienteError').hidden = false;
      return;
    }
    if (!r.clientes.length) {
      cont.innerHTML = '<p class="sub">Sin coincidencias. Puedes capturar el nombre a mano.</p>';
      return;
    }

    cont.innerHTML = '';
    r.clientes.forEach(c => {
      const b = document.createElement('button');
      b.className = 'btn btn--bloque';
      b.type = 'button';
      b.style.marginBottom = '8px';
      b.textContent = c.nombre + ' · ' + (c.telefono || '') +
        (c.saldo !== null && c.saldo !== undefined ? '  ·  saldo ' + pesos(c.saldo) : '');
      b.addEventListener('click', () => {
        cliente = {
          nombre: c.nombre,
          telefono: c.telefono,
          mobile_usuario_id: c.origen === 'mobile' ? c.id : null
        };
        $('clienteNombre').value = c.nombre || '';
        $('clienteTelefono').value = c.telefono || '';
        guardar();
        cerrarModal('modalCliente');
      });
      cont.appendChild(b);
    });
  });

  $('btnGuardarCliente').addEventListener('click', () => {
    const nombre = $('clienteNombre').value.trim();
    const tel = $('clienteTelefono').value.trim();
    cliente = (nombre || tel)
      ? { nombre, telefono: tel, mobile_usuario_id: cliente ? cliente.mobile_usuario_id : null }
      : null;
    guardar();
    cerrarModal('modalCliente');
  });

  $('btnQuitarCliente').addEventListener('click', () => {
    cliente = null;
    $('clienteNombre').value = ''; $('clienteTelefono').value = ''; $('clienteResultados').innerHTML = '';
    guardar(); cerrarModal('modalCliente');
  });

  // ── Movimiento de caja ────────────────────────────────────
  document.querySelectorAll('[data-movtipo]').forEach(b => {
    b.addEventListener('click', () => {
      document.querySelectorAll('[data-movtipo]').forEach(x => x.setAttribute('aria-pressed', 'false'));
      b.setAttribute('aria-pressed', 'true');
    });
  });

  $('btnGuardarMovimiento').addEventListener('click', async () => {
    const tipo = document.querySelector('[data-movtipo][aria-pressed="true"]').dataset.movtipo;
    const r = await postJson('rest-caja/movimiento', {
      tipo,
      monto: parseFloat($('movMonto').value) || 0,
      motivo: $('movMotivo').value.trim()
    });
    if (!r.ok) {
      $('movError').textContent = r.error || 'No se pudo registrar.';
      $('movError').hidden = false;
      return;
    }
    $('movMonto').value = '0'; $('movMotivo').value = '';
    $('movError').hidden = true;
    cerrarModal('modalMovimiento');
  });

  // ── Búsqueda, atajos y arranque ───────────────────────────
  $('buscador').addEventListener('input', (e) => { busqueda = e.target.value; pintarProductos(); });
  $('btnCobrar').addEventListener('click', abrirCobro);
  $('btnLimpiar').addEventListener('click', () => {
    if (!carrito.length || confirm('¿Descartar la venta actual?')) limpiarVenta();
  });

  document.addEventListener('keydown', (e) => {
    const escribiendo = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName);

    if (e.key === 'F2') { e.preventDefault(); return abrirCobro(); }
    if (e.key === 'F3') { e.preventDefault(); return $('buscador').focus(); }
    if (e.key === 'F4') { e.preventDefault(); return (window.location.href = window.CAJA.base + 'rest-caja/pedidos'); }
    if (e.key === 'F8') { e.preventDefault(); return abrirModal('modalMovimiento'); }
    if (escribiendo) return;

    const linea = carrito.find(l => l.uid === seleccion);
    if (!linea) return;
    if (e.key === '+') { linea.cantidad = Math.min(99, linea.cantidad + 1); guardar(); pintarCarrito(); }
    if (e.key === '-') { linea.cantidad = Math.max(1, linea.cantidad - 1); guardar(); pintarCarrito(); }
    if (e.key === 'Delete') { carrito = carrito.filter(l => l.uid !== seleccion); guardar(); pintarCarrito(); }
  });

  restaurar();
  pintarCarrito();
  cargarCatalogo();
})();
