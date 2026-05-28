<?php ob_start(); ?>
<?php if (!empty($stripePk)): ?>
<script src="https://js.stripe.com/v3/"></script>
<?php endif; ?>

<!-- Modal de pago a CarniHub -->
<div id="modalPago"
     style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.5);
            align-items:center;justify-content:center;padding:20px">
  <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:440px;
              box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
      <div>
        <div style="font-weight:700;font-size:1.05rem;color:#111827">Pago a CarniHub</div>
        <div style="font-size:.82rem;color:#6B7280;margin-top:2px">
          Método: <strong id="modalPagoMetodo"></strong> &nbsp;|&nbsp; Total: <strong id="modalPagoMonto"></strong>
        </div>
      </div>
      <button onclick="document.getElementById('modalPago').style.display='none'"
              style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#6B7280;line-height:1">×</button>
    </div>
    <div id="modalPagoContent"></div>
  </div>
</div>

<style>
.ph-topbar { display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px; }
.ph-topbar h2 { font-size:1.1rem;font-weight:700;color:#111827;margin:0 }

.ph-table { width:100%;border-collapse:collapse;font-size:.85rem; }
.ph-table th { background:#F9FAFB;padding:10px 14px;text-align:left;font-weight:600;color:#374151;border-bottom:1.5px solid #E5E7EB;white-space:nowrap; }
.ph-table td { padding:11px 14px;border-bottom:1px solid #F3F4F6;vertical-align:middle; }
.ph-table tr:hover td { background:#FAFAFA; }

.estado-badge {
  display:inline-flex;align-items:center;gap:4px;
  padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:700;white-space:nowrap;
}
/* Estado local */
.estado-sugerido   { background:#FEF3C7;color:#92400E; }
.estado-aprobado   { background:#DBEAFE;color:#1E40AF; }
.estado-convertido { background:#DCFCE7;color:#14532D; }
.estado-rechazado  { background:#FEE2E2;color:#991B1B; }
.estado-cancelado  { background:#F3F4F6;color:#6B7280; }

/* Estado CarniHub */
.ch-pendiente   { background:#DBEAFE;color:#1E40AF; }
.ch-aprobado    { background:#D1FAE5;color:#065F46; }
.ch-en_camino   { background:#EDE9FE;color:#5B21B6; }
.ch-entregado   { background:#064E3B;color:#ECFDF5; }
.ch-cancelado   { background:#FEE2E2;color:#991B1B; }

.btn-accion {
  display:inline-flex;align-items:center;gap:5px;padding:5px 11px;
  border-radius:7px;font-size:.76rem;font-weight:600;cursor:pointer;
  border:none;transition:opacity .15s;margin:2px;
}
.btn-reintentar { background:#2563EB;color:#fff; }
.btn-cancelar   { background:#EF4444;color:#fff; }
.btn-actualizar { background:#7C3AED;color:#fff; }
.btn-accion:disabled { opacity:.45;cursor:not-allowed; }
</style>

<!-- Top bar -->
<div class="ph-topbar">
  <div>
    <h2>📋 Pedidos Automáticos de Reabastecimiento</h2>
    <p style="font-size:.8rem;color:#6B7280;margin:2px 0 0">
      El sistema genera y envía los pedidos automáticamente a CarniHub
    </p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>rest-inventario/proyecciones" class="btn btn-primary btn-sm">📊 Ir a Proyecciones</a>
    <a href="<?= BASE_URL ?>rest-inventario/index" class="btn btn-outline btn-sm">← Inventario</a>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:16px"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<div id="toast-msg" style="display:none;position:fixed;top:20px;right:20px;z-index:9999;
     background:#1D4ED8;color:#fff;padding:12px 20px;border-radius:10px;font-size:.88rem;
     font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.18)"></div>

<div style="background:#EFF6FF;border:1.5px solid #BFDBFE;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:.84rem;color:#1E40AF">
  ⚡ <strong>Flujo automático:</strong>
  Cuando hay ingredientes críticos el sistema <em>crea y envía el pedido directamente a CarniHub</em>.
  Si el envío falla, el pedido queda en estado <em>Sugerido</em> para reintento manual.
  Una vez enviado puedes consultar el seguimiento o cancelarlo mientras CarniHub no lo haya aprobado.
</div>

<!-- Tabla -->
<div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;overflow:hidden">
  <div style="overflow-x:auto">
    <table class="ph-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Distribuidor</th>
          <th>Estado local</th>
          <th>Estado CarniHub</th>
          <th>Total estimado</th>
          <th>Pago</th>
          <th>Items</th>
          <th>Generado</th>
          <th style="text-align:center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pedidos)): ?>
        <tr>
          <td colspan="9" style="text-align:center;padding:48px 16px;color:#9CA3AF">
            <div style="font-size:2rem;margin-bottom:8px">🔭</div>
            <div style="font-weight:600">Sin pedidos automáticos aún</div>
            <div style="font-size:.8rem;margin-top:4px">
              Ve a <a href="<?= BASE_URL ?>rest-inventario/proyecciones" style="color:#2563EB">Proyecciones</a>
              cuando haya ingredientes críticos para auto-generar pedidos.
            </div>
          </td>
        </tr>
        <?php endif; ?>

        <?php foreach ($pedidos as $p): ?>
        <?php
          $pid   = (int)$p['id'];
          $est   = $p['estado'];
          $chEst = $p['estado_carnihub'] ?? null;
          $chId  = (int)($p['pedido_carnihub_id'] ?? 0);
        ?>
        <tr id="fila-<?= $pid ?>">
          <td>
            <span style="font-family:monospace;font-weight:700;color:#1D4ED8;font-size:.88rem">
              #<?= $pid ?>
            </span>
            <?php if ($chId > 0): ?>
            <br><span style="font-size:.72rem;color:#6B7280">
              <?= !empty($p['folio_carnihub']) ? htmlspecialchars($p['folio_carnihub']) : ('CH-' . $chId) ?>
            </span>
            <?php endif; ?>
          </td>
          <td style="font-weight:600;color:#111827">
            <?= htmlspecialchars($p['empresa_nombre'] ?? '—') ?>
          </td>
          <td>
            <span class="estado-badge estado-<?= htmlspecialchars($est) ?>">
              <?php echo match($est) {
                'sugerido'  => '⚠️ Sugerido',
                'aprobado'  => '⏳ Aprobado',
                'convertido'=> '🚀 Enviado',
                'rechazado' => '✗ Rechazado',
                'cancelado' => '🚫 Cancelado',
                default     => htmlspecialchars($est),
              }; ?>
            </span>
          </td>
          <td id="ch-estado-<?= $pid ?>">
            <?php if ($chEst): ?>
              <?php
                $chClass = match($chEst) {
                  'pendiente' => 'ch-pendiente',
                  'aprobado'  => 'ch-aprobado',
                  'en_camino' => 'ch-en_camino',
                  'entregado' => 'ch-entregado',
                  'cancelado' => 'ch-cancelado',
                  default     => 'estado-badge',
                };
              ?>
              <span class="estado-badge <?= $chClass ?>">
                <?php echo match($chEst) {
                  'pendiente' => '⏳ Pendiente',
                  'aprobado'  => '✅ Aprobado',
                  'en_camino' => '🚚 En camino',
                  'entregado' => '📦 Entregado',
                  'cancelado' => '✗ Cancelado',
                  default     => htmlspecialchars($chEst),
                }; ?>
              </span>
            <?php elseif ($est === 'convertido'): ?>
              <span style="font-size:.75rem;color:#9CA3AF">Sin consultar</span>
            <?php else: ?>
              <span style="color:#D1D5DB">—</span>
            <?php endif; ?>
          </td>
          <td>
            <strong style="color:#111827">$<?= number_format((float)$p['total_estimado'], 2) ?></strong>
          </td>
          <td id="pago-badge-<?= $pid ?>">
            <?php
              $epago = $p['estado_pago'] ?? null;
              if ($epago && $epago !== 'pendiente' || $epago === 'pagado'):
                $epagoCls = ['pendiente'=>'#FEF3C7::#92400E','procesando'=>'#DBEAFE::#1E40AF',
                             'pagado'=>'#DCFCE7::#166534','fallido'=>'#FEE2E2::#991B1B'][$epago] ?? '#F3F4F6::#374151';
                [$bg, $fg] = explode('::', $epagoCls);
            ?>
            <span style="background:<?= $bg ?>;color:<?= $fg ?>;padding:3px 8px;border-radius:6px;
                         font-size:.72rem;font-weight:700;white-space:nowrap">
              <?= ['pendiente'=>'⏳ Pendiente','procesando'=>'🔄 Procesando','pagado'=>'✅ Pagado','fallido'=>'❌ Fallido'][$epago] ?? $epago ?>
            </span>
            <?php elseif ($est === 'convertido'): ?>
            <span style="background:#FEF3C7;color:#92400E;padding:3px 8px;border-radius:6px;
                         font-size:.72rem;font-weight:700;white-space:nowrap">
              ⏳ Pendiente
            </span>
            <?php else: ?>
            <span style="color:#D1D5DB">—</span>
            <?php endif; ?>
          </td>
          <td style="color:#6B7280;font-size:.8rem">
            <?= isset($p['items_count']) ? (int)$p['items_count'] . ' ings.' : '—' ?>
          </td>
          <td style="font-size:.78rem;color:#6B7280;white-space:nowrap">
            <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
            <?php if (!empty($p['usuario_nombre'])): ?>
            <br><span style="color:#9CA3AF">por <?= htmlspecialchars($p['usuario_nombre']) ?></span>
            <?php endif; ?>
          </td>
          <td style="text-align:center;white-space:nowrap">
            <?php if ($est === 'sugerido'): ?>
              <!-- Pedido que falló el envío automático: ofrecer reintento -->
              <button class="btn-accion btn-reintentar"
                      onclick="accionPedido('reintentar', <?= $pid ?>, this)">
                🔁 Reintentar envío
              </button>
              <button class="btn-accion btn-cancelar"
                      onclick="accionPedido('cancelar', <?= $pid ?>, this)">
                ✗ Cancelar
              </button>

            <?php elseif ($est === 'aprobado'): ?>
              <button class="btn-accion btn-reintentar"
                      onclick="accionPedido('reintentar', <?= $pid ?>, this)">
                🚀 Enviar a CarniHub
              </button>
              <button class="btn-accion btn-cancelar"
                      onclick="accionPedido('cancelar', <?= $pid ?>, this)">
                ✗ Cancelar
              </button>

            <?php elseif ($est === 'convertido'): ?>
              <?php $epago = $p['estado_pago'] ?? 'pendiente'; ?>
              <?php if ($epago !== 'pagado'): ?>
              <button class="btn-accion btn-reintentar"
                      onclick="abrirModalPago(<?= $pid ?>, <?= (float)($p['total_estimado'] ?? 0) ?>, this)">
                💳 Pagar
              </button>
              <?php else: ?>
              <span style="font-size:.75rem;color:#059669;font-weight:600">✅ Pagado</span>
              <?php endif; ?>
              <button class="btn-accion btn-actualizar"
                      id="btn-seg-<?= $pid ?>"
                      onclick="actualizarEstado(<?= $pid ?>, this)">
                🔄 Actualizar estado
              </button>

              <?php if (!in_array($chEst, ['aprobado', 'en_camino', 'entregado'])): ?>
              <button class="btn-accion btn-cancelar"
                      onclick="accionPedido('cancelar', <?= $pid ?>, this)">
                ✗ Cancelar
              </button>
              <?php endif; ?>

            <?php elseif ($est === 'cancelado'): ?>
              <span style="font-size:.78rem;color:#9CA3AF">—</span>

            <?php else: ?>
              <span style="font-size:.78rem;color:#9CA3AF">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
// IDs de pedidos convertidos que aún pueden cambiar de estado (para auto-poll)
$idsParaPoll = array_values(array_map(
    fn($p) => (int)$p['id'],
    array_filter($pedidos, fn($p) =>
        $p['estado'] === 'convertido' &&
        !in_array($p['estado_carnihub'] ?? null, ['entregado', 'cancelado'])
    )
));
?>

<script>
const BASE = '<?= BASE_URL ?>';
const _pedidosParaPoll = <?= json_encode($idsParaPoll) ?>;
let _pollingActivo = {};   // id => true mientras no sea estado final
let _pagoModal = { id: null, monto: null, metodo: null, data: null };

function mostrarToast(msg, color) {
  const t = document.getElementById('toast-msg');
  t.textContent = msg;
  t.style.background = color || '#1D4ED8';
  t.style.display = 'block';
  setTimeout(() => t.style.display = 'none', 4500);
}

function _eliminarFila(id) {
  const fila = document.getElementById('fila-' + id);
  if (!fila) return;
  fila.style.transition = 'opacity .5s, transform .5s';
  fila.style.opacity    = '0';
  fila.style.transform  = 'translateX(40px)';
  setTimeout(() => fila.remove(), 520);
}

function _actualizarBadgeCH(id, est) {
  const cell = document.getElementById('ch-estado-' + id);
  if (!cell) return;
  const clsMap = { pendiente:'ch-pendiente', aprobado:'ch-aprobado', en_camino:'ch-en_camino', entregado:'ch-entregado', cancelado:'ch-cancelado' };
  const lblMap = { pendiente:'⏳ Pendiente', aprobado:'✅ Aprobado', en_camino:'🚚 En camino', entregado:'📦 Entregado', cancelado:'✗ Cancelado' };
  cell.dataset.est = est;
  cell.innerHTML   = `<span class="estado-badge ${clsMap[est]||''}">${lblMap[est]||est}</span>`;
}

async function _pollUno(id) {
  if (!_pollingActivo[id]) return;
  try {
    const resp = await fetch(BASE + 'rest-inventario/seguimientoPedido/' + id, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!resp.ok) return;
    const data = await resp.json();
    if (!data.ok) return;
    const est    = data.estado_carnihub;
    const cellEl = document.getElementById('ch-estado-' + id);
    const prev   = cellEl?.dataset?.est || '';
    if (!est || est === prev) return;   // sin cambio, no hacer nada
    _actualizarBadgeCH(id, est);
    if (est === 'cancelado') {
      delete _pollingActivo[id];
      mostrarToast('⚠️ Pedido #' + id + ' cancelado por CarniHub', '#DC2626');
      setTimeout(() => _eliminarFila(id), 2200);
    } else if (est === 'entregado') {
      delete _pollingActivo[id];
      mostrarToast('📦 Pedido #' + id + ' marcado como entregado', '#059669');
    } else {
      mostrarToast('Pedido #' + id + ': estado → ' + est, '#7C3AED');
    }
  } catch (_) { /* error de red — ignorar en segundo plano */ }
}

// Inicializar estado actual en dataset + arrancar polling
if (_pedidosParaPoll.length > 0) {
  _pedidosParaPoll.forEach(id => {
    _pollingActivo[id] = true;
    const cell  = document.getElementById('ch-estado-' + id);
    const badge = cell?.querySelector('[class*="ch-"]');
    if (badge && cell) {
      const m = [...badge.classList].find(c => c.startsWith('ch-'));
      cell.dataset.est = m ? m.replace('ch-', '') : '';
    }
  });
  // Poll cada 75 s, escalonado 1.5 s entre pedidos para no saturar
  setInterval(() => {
    Object.keys(_pollingActivo).forEach((id, i) =>
      setTimeout(() => _pollUno(parseInt(id)), i * 1500)
    );
  }, 75000);
}

async function accionPedido(tipo, id, btn) {
  const labels = { reintentar: 'Enviando…', cancelar: 'Cancelando…' };
  const orig   = btn.textContent;
  btn.disabled = true;
  btn.textContent = labels[tipo] || '…';

  const url = tipo === 'cancelar'
    ? BASE + 'rest-inventario/cancelarSugerido/' + id
    : BASE + 'rest-inventario/enviarACarnihub/' + id;

  try {
    const resp = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await resp.json();

    if (data.ok) {
      // Si la respuesta incluye datos de pago, abrir modal
      if (data.pago) {
        mostrarToast(data.message || 'Pedido enviado. Procesa el pago.', '#7C3AED');
        _mostrarModalPago(id, data.pago);
      } else if (tipo === 'cancelar') {
        mostrarToast(data.message || 'Pedido cancelado', '#EF4444');
        delete _pollingActivo[id];
        _eliminarFila(id);
      } else {
        mostrarToast(data.message || 'Operación exitosa', '#059669');
        setTimeout(() => location.reload(), 1500);
      }
    } else {
      mostrarToast('Error: ' + (data.error || 'Intenta de nuevo'), '#DC2626');
      btn.disabled = false;
      btn.textContent = orig;
    }
  } catch (e) {
    mostrarToast('Error de conexión', '#DC2626');
    btn.disabled = false;
    btn.textContent = orig;
  }
}

// Abrir modal de pago para un pedido ya enviado a CarniHub
async function abrirModalPago(id, monto, btn) {
  btn.disabled = true;
  // Solo abrir modal con los datos del pedido (no re-enviar)
  // Leer configuración del método de pago via AJAX o inferir
  btn.disabled = false;
  // Abrimos modal con "transferencia" como fallback (el user puede cancelar y pagar más tarde)
  const pagoFallback = { metodo: 'transferencia', monto: monto, instrucciones: '' };
  _mostrarModalPago(id, pagoFallback);
}

function _mostrarModalPago(id, pago) {
  // Cobro automático off-session completado — sin modal
  if (pago.auto) {
    mostrarToast('✅ Cobro automático con Stripe: $' + parseFloat(pago.monto || 0).toFixed(2), '#059669');
    _actualizarBadgePago(id, 'pagado');
    return;
  }
  // Autenticación 3DS necesaria
  if (pago.action_url) {
    if (confirm('Tu banco requiere autenticación adicional para procesar el pago. ¿Continuar?')) {
      window.location.href = pago.action_url;
    }
    return;
  }

  _pagoModal = { id, monto: pago.monto, metodo: pago.metodo, data: pago };
  const modal   = document.getElementById('modalPago');
  const content = document.getElementById('modalPagoContent');

  document.getElementById('modalPagoMonto').textContent =
    '$' + parseFloat(pago.monto || 0).toFixed(2);
  document.getElementById('modalPagoMetodo').textContent =
    { stripe: '💳 Tarjeta (Stripe)', transferencia: '📲 Transferencia', paypal: '🅿️ PayPal' }[pago.metodo] || pago.metodo;

  // Contenido según método
  if (pago.metodo === 'paypal' && pago.approvalUrl) {
    content.innerHTML = `
      <p style="font-size:.87rem;color:#374151;margin-bottom:16px">
        Serás redirigido a PayPal para completar el pago.
      </p>
      <a href="${pago.approvalUrl}" class="btn btn-primary" style="display:block;text-align:center">
        Pagar con PayPal →
      </a>`;
  } else if (pago.metodo === 'stripe' && pago.clientSecret) {
    content.innerHTML = `
      <div style="font-size:.85rem;font-weight:600;color:#374151;margin-bottom:8px">Datos de tarjeta</div>
      <div id="cardElPago" style="padding:12px 14px;border:1.5px solid #E5E7EB;border-radius:10px;background:#fff"></div>
      <div id="cardElPagoErr" style="color:#EF4444;font-size:.8rem;margin-top:6px"></div>
      <button id="btnConfirmarStripe" onclick="confirmarPagoStripe()" class="btn btn-primary"
              style="width:100%;margin-top:14px;justify-content:center">
        Pagar $${parseFloat(pago.monto || 0).toFixed(2)}
      </button>`;
    // Montar card element
    setTimeout(() => {
      if (!window.Stripe) { content.innerHTML = '<p style="color:#EF4444">Stripe no disponible. Configura las claves Stripe en Configuración.</p>'; return; }
      const stripePk = '<?= htmlspecialchars($stripePk ?? '', ENT_QUOTES) ?>';
      if (!stripePk) { content.innerHTML = '<p style="color:#EF4444">Stripe no configurado. Agrega las claves en Configuración del restaurante.</p>'; return; }
      const stripe   = Stripe(stripePk);
      const elements = stripe.elements();
      const card     = elements.create('card', { style: { base: { fontSize: '16px', color: '#111827' } } });
      card.mount('#cardElPago');
      card.on('change', e => document.getElementById('cardElPagoErr').textContent = e.error?.message || '');
      window._cardElPago = card;
      window._stripeInst = stripe;
    }, 100);
  } else {
    // Transferencia u otros
    const instrText = pago.instrucciones
      ? `<div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:14px;
                    font-size:.84rem;white-space:pre-line;color:#065F46;font-family:monospace;margin-bottom:14px">
             ${pago.instrucciones.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>`
      : `<p style="font-size:.84rem;color:#6B7280">El administrador debe registrar la referencia al confirmar la transferencia.</p>`;
    content.innerHTML = instrText + `
      <label style="display:block;font-size:.83rem;font-weight:600;color:#374151;margin-bottom:6px">Referencia / Folio de la transferencia</label>
      <input id="inpRefTransf" type="text" class="form-input" placeholder="Ej: TRF-20240115-001" style="margin-bottom:12px">
      <button onclick="confirmarPagoTransf()" class="btn btn-primary" style="width:100%;justify-content:center">
        Confirmar pago
      </button>`;
  }

  modal.style.display = 'flex';
}

async function confirmarPagoStripe() {
  const btn = document.getElementById('btnConfirmarStripe');
  btn.disabled = true; btn.textContent = 'Procesando…';
  try {
    const { paymentIntent, error } = await window._stripeInst.confirmCardPayment(
      _pagoModal.data.clientSecret, { payment_method: { card: window._cardElPago } }
    );
    if (error) throw new Error(error.message);
    if (paymentIntent.status !== 'succeeded') throw new Error('Pago no completado');
    const body = new URLSearchParams({ payment_intent_id: paymentIntent.id });
    const resp = await fetch(BASE + 'rest-inventario/confirmarPagoCarnihub/' + _pagoModal.id + '/stripe', {
      method: 'POST', credentials: 'same-origin', body
    });
    const d = await resp.json();
    if (!d.ok) throw new Error(d.error || 'Error al confirmar');
    document.getElementById('modalPago').style.display = 'none';
    mostrarToast('✅ Pago con tarjeta confirmado', '#059669');
    _actualizarBadgePago(_pagoModal.id, 'pagado');
  } catch(e) {
    document.getElementById('cardElPagoErr').textContent = e.message;
    btn.disabled = false; btn.textContent = 'Pagar $' + parseFloat(_pagoModal.monto||0).toFixed(2);
  }
}

async function confirmarPagoTransf() {
  const ref = document.getElementById('inpRefTransf')?.value?.trim() || '';
  const body = new URLSearchParams({ referencia: ref });
  try {
    const resp = await fetch(BASE + 'rest-inventario/confirmarPagoCarnihub/' + _pagoModal.id + '/transferencia', {
      method: 'POST', credentials: 'same-origin', body
    });
    const d = await resp.json();
    if (!d.ok) throw new Error(d.error || 'Error al confirmar');
    document.getElementById('modalPago').style.display = 'none';
    mostrarToast('✅ Pago de transferencia registrado', '#059669');
    _actualizarBadgePago(_pagoModal.id, 'pagado');
  } catch(e) {
    mostrarToast('Error: ' + e.message, '#DC2626');
  }
}

function _actualizarBadgePago(id, estado) {
  const cell = document.getElementById('pago-badge-' + id);
  if (cell) cell.innerHTML = '<span style="background:#DCFCE7;color:#166534;padding:3px 8px;border-radius:6px;font-size:.72rem;font-weight:700">✅ Pagado</span>';
}

async function actualizarEstado(id, btn) {
  const orig = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Consultando…';

  try {
    const resp = await fetch(BASE + 'rest-inventario/seguimientoPedido/' + id, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await resp.json();

    if (data.ok) {
      const est = data.estado_carnihub || '—';
      const cell = document.getElementById('ch-estado-' + id);
      if (cell) {
        const clsMap = {
          pendiente: 'ch-pendiente', aprobado: 'ch-aprobado',
          en_camino: 'ch-en_camino', entregado: 'ch-entregado', cancelado: 'ch-cancelado',
        };
        const lblMap = {
          pendiente: '⏳ Pendiente', aprobado: '✅ Aprobado',
          en_camino: '🚚 En camino', entregado: '📦 Entregado', cancelado: '✗ Cancelado',
        };
        const cls = clsMap[est] || '';
        cell.innerHTML = `<span class="estado-badge ${cls}">${lblMap[est] || est}</span>`;
        cell.dataset.est = est;
      }
      if (est === 'cancelado') {
        mostrarToast('Pedido cancelado por CarniHub', '#DC2626');
        delete _pollingActivo[id];
        setTimeout(() => _eliminarFila(id), 1800);
      } else {
        mostrarToast('Estado CarniHub: ' + est, '#7C3AED');
      }
    } else {
      mostrarToast('Error: ' + (data.error || 'No se pudo consultar'), '#DC2626');
    }
  } catch (e) {
    mostrarToast('Error de conexión', '#DC2626');
  }

  btn.disabled = false;
  btn.textContent = orig;
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
?>
