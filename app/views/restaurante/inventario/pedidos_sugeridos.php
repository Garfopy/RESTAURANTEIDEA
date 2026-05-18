<?php ob_start(); ?>

<style>
.ps-topbar { display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px; }
.ps-topbar h2 { font-size:1.1rem;font-weight:700;color:#111827;margin:0 }

.ps-table { width:100%;border-collapse:collapse;font-size:.85rem; }
.ps-table th { background:#F9FAFB;padding:10px 14px;text-align:left;font-weight:600;color:#374151;border-bottom:1.5px solid #E5E7EB;white-space:nowrap; }
.ps-table td { padding:11px 14px;border-bottom:1px solid #F3F4F6;vertical-align:middle; }
.ps-table tr:hover td { background:#FAFAFA; }

.estado-badge {
  display:inline-flex;align-items:center;gap:4px;
  padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:700;white-space:nowrap;
}
.estado-sugerido   { background:#DBEAFE;color:#1E40AF; }
.estado-aprobado   { background:#D1FAE5;color:#065F46; }
.estado-rechazado  { background:#FEE2E2;color:#991B1B; }
.estado-convertido { background:#F3E8FF;color:#6B21A8; }

/* Modal */
.ps-modal-overlay {
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;
  align-items:center;justify-content:center;padding:16px;
}
.ps-modal-overlay.open { display:flex; }
.ps-modal {
  background:#fff;border-radius:16px;width:100%;max-width:760px;max-height:90vh;
  overflow:hidden;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,.2);
}
.ps-modal-head {
  padding:20px 24px;border-bottom:1px solid #E5E7EB;
  display:flex;justify-content:space-between;align-items:flex-start;
}
.ps-modal-head h3 { font-size:1rem;font-weight:700;margin:0 }
.ps-modal-body { padding:20px 24px;overflow-y:auto;flex:1; }
.ps-modal-foot { padding:14px 24px;border-top:1px solid #E5E7EB;display:flex;justify-content:flex-end;gap:8px; }

.items-table { width:100%;border-collapse:collapse;font-size:.83rem; }
.items-table th { background:#F9FAFB;padding:8px 10px;text-align:left;font-weight:600;color:#374151;border-bottom:1px solid #E5E7EB; }
.items-table td { padding:8px 10px;border-bottom:1px solid #F3F4F6; }
.items-table input[type=number] {
  width:80px;padding:4px 6px;border:1.5px solid #D1D5DB;border-radius:6px;
  font-size:.82rem;text-align:right;
}
.items-table input[type=number]:focus { border-color:#2563EB;outline:none; }
</style>

<!-- Top bar -->
<div class="ps-topbar">
  <div>
    <h2>📦 Pedidos Sugeridos de Compra</h2>
    <p style="font-size:.8rem;color:#6B7280;margin:2px 0 0">Órdenes generadas por el sistema de forecast inteligente</p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>rest-inventario/proyecciones" class="btn btn-outline btn-sm">📊 Proyecciones</a>
    <a href="<?= BASE_URL ?>rest-inventario/index" class="btn btn-outline btn-sm">← Inventario</a>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:16px"><?= $flash['message'] ?></div>
<?php endif; ?>

<!-- Filtros de estado -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
  <?php
  $estados = ['' => 'Todos', 'sugerido' => '🔵 Sugerido', 'aprobado' => '🟢 Aprobado', 'rechazado' => '🔴 Rechazado', 'convertido' => '🟣 Convertido'];
  foreach ($estados as $v => $label):
    $active = ($estado === $v) ? 'border-color:#C8102E;color:#C8102E;background:#FFF5F5;' : '';
  ?>
  <a href="<?= BASE_URL ?>rest-inventario/pedidosSugeridos<?= $v ? '?estado=' . $v : '' ?>"
     style="padding:5px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:.77rem;color:#374151;text-decoration:none;background:#fff;<?= $active ?>">
    <?= $label ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Tabla principal -->
<div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;overflow:hidden">
  <div style="overflow-x:auto">
    <table class="ps-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Empresa proveedora</th>
          <th>Estado</th>
          <th>Total estimado</th>
          <th>Generado por</th>
          <th>Fecha</th>
          <th>Pedido CarniHub</th>
          <th style="text-align:right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pedidos)): ?>
        <tr>
          <td colspan="8" style="text-align:center;padding:48px 16px;color:#9CA3AF">
            <div style="font-size:2rem;margin-bottom:8px">📭</div>
            <div style="font-weight:600">Sin pedidos sugeridos</div>
            <div style="font-size:.8rem;margin-top:4px">
              Ve a <a href="<?= BASE_URL ?>rest-inventario/proyecciones" style="color:#2563EB">Proyecciones</a>
              y haz clic en "Generar pedidos sugeridos ahora" para crear pedidos automáticamente.
            </div>
          </td>
        </tr>
        <?php endif; ?>
        <?php foreach ($pedidos as $p): ?>
        <tr>
          <td style="color:#9CA3AF;font-size:.78rem">#<?= $p['id'] ?></td>
          <td>
            <div style="font-weight:600;color:#111827"><?= htmlspecialchars($p['empresa_nombre']) ?></div>
            <?php if ($p['empresa_email']): ?>
            <div style="font-size:.72rem;color:#9CA3AF"><?= htmlspecialchars($p['empresa_email']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span class="estado-badge estado-<?= $p['estado'] ?>">
              <?php echo match($p['estado']) {
                'sugerido'   => '🔵 Sugerido',
                'aprobado'   => '🟢 Aprobado',
                'rechazado'  => '🔴 Rechazado',
                'convertido' => '🟣 Convertido',
                default      => $p['estado'],
              }; ?>
            </span>
          </td>
          <td>
            <strong style="color:#111827">$<?= number_format((float)$p['total_estimado'], 2) ?></strong>
            <div style="font-size:.7rem;color:#9CA3AF">estimado</div>
          </td>
          <td style="font-size:.8rem;color:#6B7280">
            <?= $p['usuario_nombre'] ? htmlspecialchars($p['usuario_nombre']) : 'Sistema' ?>
          </td>
          <td style="font-size:.78rem;color:#6B7280;white-space:nowrap">
            <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
            <?php if ($p['aprobado_at']): ?>
            <div style="color:#16A34A">✓ Aprobado <?= date('d/m', strtotime($p['aprobado_at'])) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($p['pedido_carnihub_id']): ?>
            <a href="<?= BASE_URL ?>pedido/detalle/<?= $p['pedido_carnihub_id'] ?>"
               style="font-size:.78rem;color:#7C3AED;font-weight:600;text-decoration:none">
              🔗 Ver pedido #<?= $p['pedido_carnihub_id'] ?>
            </a>
            <?php else: ?>
            <span style="color:#D1D5DB;font-size:.75rem">—</span>
            <?php endif; ?>
          </td>
          <td style="text-align:right;white-space:nowrap">
            <!-- Ver detalles (siempre visible) -->
            <button onclick="verDetalle(<?= $p['id'] ?>, <?= $p['estado'] === 'sugerido' ? 'true' : 'false' ?>)"
                    class="btn btn-outline btn-xs" style="margin-right:4px">
              👁 Ver items
            </button>
            <?php if ($p['estado'] === 'sugerido'): ?>
            <!-- Aprobar -->
            <button onclick="abrirModalAprobar(<?= $p['id'] ?>)"
                    class="btn btn-primary btn-xs" style="margin-right:4px;background:#16A34A;border-color:#16A34A">
              ✅ Aprobar
            </button>
            <!-- Rechazar -->
            <form method="POST" action="<?= BASE_URL ?>rest-inventario/rechazarPedidoSugerido/<?= $p['id'] ?>" style="display:inline" onsubmit="return confirm('¿Rechazar este pedido sugerido?')">
              <button type="submit" class="btn btn-xs" style="background:#FEE2E2;color:#DC2626;border:1.5px solid #FECACA">
                ✗ Rechazar
              </button>
            </form>
            <?php elseif ($p['estado'] === 'aprobado'): ?>
            <span style="font-size:.75rem;color:#065F46;font-weight:600">⏳ Pendiente envío</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: ver items + aprobar con cantidades -->
<div class="ps-modal-overlay" id="modalDetalle">
  <div class="ps-modal">
    <div class="ps-modal-head">
      <div>
        <h3 id="modalTitle">Items del pedido sugerido</h3>
        <p id="modalSubtitle" style="font-size:.78rem;color:#6B7280;margin:2px 0 0"></p>
      </div>
      <button onclick="cerrarModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#9CA3AF;padding:0">×</button>
    </div>
    <div class="ps-modal-body" id="modalBody">
      <div style="text-align:center;padding:32px;color:#9CA3AF">Cargando…</div>
    </div>
    <div class="ps-modal-foot">
      <button onclick="cerrarModal()" class="btn btn-outline btn-sm">Cerrar</button>
      <button id="btnAprobarModal" onclick="aprobarConCantidades()" class="btn btn-primary btn-sm" style="display:none;background:#16A34A;border-color:#16A34A">
        ✅ Confirmar y enviar a CarniHub
      </button>
    </div>
  </div>
</div>

<!-- Form oculto para aprobar -->
<form method="POST" id="formAprobar" action="" style="display:none">
  <input type="hidden" name="pedido_id" id="formPedidoId">
  <div id="cantidadesHiddens"></div>
</form>

<script>
const BASE = '<?= BASE_URL ?>';
let modalPedidoId = null;
let modalPuedeAprobar = false;
let itemsCache = {};

async function verDetalle(id, puedeAprobar) {
  modalPedidoId = id;
  modalPuedeAprobar = puedeAprobar;
  document.getElementById('modalTitle').textContent = 'Items del pedido #' + id;
  document.getElementById('btnAprobarModal').style.display = puedeAprobar ? 'inline-flex' : 'none';
  document.getElementById('modalDetalle').classList.add('open');
  document.getElementById('modalBody').innerHTML = '<div style="text-align:center;padding:32px;color:#9CA3AF">Cargando…</div>';

  try {
    const res  = await fetch(BASE + 'rest-inventario/pedidoSugeridoItems/' + id, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Error al cargar');

    itemsCache[id] = data.items;
    document.getElementById('modalSubtitle').textContent = data.empresa + ' · Total estimado: $' + parseFloat(data.total).toFixed(2);
    renderItems(data.items, puedeAprobar);
  } catch (e) {
    document.getElementById('modalBody').innerHTML = '<div style="color:#DC2626;text-align:center;padding:24px">⚠️ ' + e.message + '</div>';
  }
}

function renderItems(items, editable) {
  let html = '<table class="items-table"><thead><tr>';
  html += '<th>Ingrediente</th><th>Producto CarniHub</th><th>Unidad</th><th>Precio est.</th>';
  if (editable) html += '<th>Cantidad aprobada</th>';
  else html += '<th>Cant. aprobada</th>';
  html += '<th>Subtotal</th></tr></thead><tbody>';

  items.forEach(it => {
    html += `<tr>
      <td><div style="font-weight:600">${it.ingrediente_nombre}</div></td>
      <td style="font-size:.78rem;color:#374151">${it.producto_nombre}</td>
      <td style="font-size:.78rem;color:#6B7280">${it.unidad}</td>
      <td>$${parseFloat(it.precio_unit_estimado).toFixed(2)}</td>`;
    if (editable) {
      html += `<td><input type="number" id="cant_${it.id}" value="${parseFloat(it.cantidad_aprobada)}" min="0.01" step="0.01"></td>`;
    } else {
      html += `<td style="font-weight:600">${parseFloat(it.cantidad_aprobada).toFixed(2)}</td>`;
    }
    html += `<td style="font-weight:600">$${parseFloat(it.subtotal_estimado).toFixed(2)}</td>
    </tr>`;
  });

  html += '</tbody></table>';
  if (editable) {
    html += '<p style="font-size:.78rem;color:#6B7280;margin-top:12px">💡 Puedes ajustar las cantidades antes de aprobar. El subtotal se recalculará automáticamente.</p>';
  }
  document.getElementById('modalBody').innerHTML = html;
}

function abrirModalAprobar(id) {
  verDetalle(id, true);
}

function aprobarConCantidades() {
  const items = itemsCache[modalPedidoId] || [];
  const form  = document.getElementById('formAprobar');
  form.action = BASE + 'rest-inventario/aprobarPedidoSugerido/' + modalPedidoId;
  document.getElementById('formPedidoId').value = modalPedidoId;

  const hiddens = document.getElementById('cantidadesHiddens');
  hiddens.innerHTML = '';
  items.forEach(it => {
    const inputEl = document.getElementById('cant_' + it.id);
    if (inputEl) {
      const inp = document.createElement('input');
      inp.type  = 'hidden';
      inp.name  = 'cantidades[' + it.id + ']';
      inp.value = inputEl.value;
      hiddens.appendChild(inp);
    }
  });

  if (!confirm('¿Aprobar este pedido y enviarlo a CarniHub? La empresa proveedora lo recibirá como un pedido real.')) return;
  form.submit();
}

function cerrarModal() {
  document.getElementById('modalDetalle').classList.remove('open');
  modalPedidoId = null;
}

document.getElementById('modalDetalle').addEventListener('click', function(e) {
  if (e.target === this) cerrarModal();
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
?>
