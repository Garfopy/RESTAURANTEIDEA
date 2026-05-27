<?php ob_start(); ?>

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
          <th>Items</th>
          <th>Generado</th>
          <th style="text-align:center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pedidos)): ?>
        <tr>
          <td colspan="8" style="text-align:center;padding:48px 16px;color:#9CA3AF">
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

<script>
const BASE = '<?= BASE_URL ?>';

function mostrarToast(msg, color) {
  const t = document.getElementById('toast-msg');
  t.textContent = msg;
  t.style.background = color || '#1D4ED8';
  t.style.display = 'block';
  setTimeout(() => t.style.display = 'none', 4500);
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
      mostrarToast(data.message || 'Operación exitosa', '#059669');
      setTimeout(() => location.reload(), 1500);
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
      }
      mostrarToast('Estado CarniHub: ' + est, '#7C3AED');
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
