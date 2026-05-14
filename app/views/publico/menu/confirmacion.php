<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>¡Pedido recibido! — <?= htmlspecialchars($restaurante['nombre'] ?? '') ?></title>
  <style>
    :root { --cp: <?= htmlspecialchars($restaurante['color_primario'] ?? '#C8102E') ?>; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, sans-serif; background: #F9FAFB; min-height: 100vh;
           display: flex; align-items: flex-start; justify-content: center; padding: 20px; }
    .card { background: #fff; border-radius: 16px; border: 1px solid #E5E7EB;
            padding: 28px; max-width: 440px; width: 100%; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px;
             font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .badge-pendiente      { background:#FEF3C7; color:#92400E; }
    .badge-en_preparacion { background:#DBEAFE; color:#1E40AF; }
    .badge-listo          { background:#DCFCE7; color:#166534; }
    .badge-entregado      { background:#F3F4F6; color:#6B7280; }
    .badge-cancelado      { background:#FEE2E2; color:#991B1B; }
    .estado-bar { display: flex; gap: 0; margin: 20px 0; border-radius: 10px; overflow: hidden; }
    .estado-step { flex: 1; padding: 8px 4px; text-align: center; font-size: .68rem;
                   font-weight: 600; background: #F3F4F6; color: #9CA3AF; transition: .3s; }
    .estado-step.active   { background: var(--cp); color: #fff; }
    .estado-step.done     { background: #D1FAE5; color: #065F46; }
    .item-row { display: flex; justify-content: space-between; align-items: center;
                padding: 10px 0; border-bottom: 1px solid #F3F4F6; gap: 8px; }
    .item-row:last-child { border-bottom: none; }
    .btn-cancel { padding: 4px 10px; background: #FEE2E2; color: #991B1B; border: none;
                  border-radius: 6px; font-size: .72rem; font-weight: 600; cursor: pointer;
                  transition: .15s; }
    .btn-cancel:hover { background: #FECACA; }
    .btn-cancel:disabled { opacity: .45; cursor: not-allowed; }
    .link-btn { display: block; padding: 12px; border-radius: 10px; font-weight: 700;
                text-align: center; text-decoration: none; transition: .15s; font-size: .9rem; }
  </style>
</head>
<body>
<div class="card">

  <!-- Header -->
  <div style="text-align:center;margin-bottom:20px">
    <?php if (!empty($restaurante['logo'])): ?>
    <img src="<?= BASE_URL . htmlspecialchars($restaurante['logo']) ?>" alt=""
         style="height:36px;object-fit:contain;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto">
    <?php endif; ?>
    <div style="font-size:2.5rem;margin-bottom:8px">✅</div>
    <h1 style="font-size:1.2rem;font-weight:700;color:#111827">¡Pedido recibido!</h1>
    <p style="color:#6B7280;font-size:.85rem;margin-top:4px">Sigue el estado en tiempo real aquí abajo</p>
  </div>

  <!-- Barra de progreso de estados -->
  <div class="estado-bar" id="estadoBar">
    <div class="estado-step" id="step-pendiente">⏳ Recibido</div>
    <div class="estado-step" id="step-en_preparacion">👨‍🍳 Preparando</div>
    <div class="estado-step" id="step-listo">🔔 Listo</div>
    <div class="estado-step" id="step-entregado">🍽 Entregado</div>
  </div>

  <!-- Tiempo estimado -->
  <div id="tiempoEst" style="text-align:center;font-size:.82rem;color:#6B7280;margin-bottom:16px;display:none">
    ⏱️ Tiempo estimado: <strong id="tiempoMin">—</strong> min
  </div>

  <!-- Lista de pedidos e ítems -->
  <div id="pedidosList" style="margin-bottom:20px">
    <?php foreach ($pedidos as $p): ?>
    <div style="margin-bottom:12px" id="pedido-<?= (int)$p['id'] ?>">
      <div style="font-size:.78rem;font-weight:700;color:#9CA3AF;text-transform:uppercase;
                  letter-spacing:.06em;margin-bottom:6px">
        <?= htmlspecialchars($p['folio']) ?>
        <?php if (!empty($p['mesa_nombre'])): ?>
        · Mesa <?= htmlspecialchars($p['mesa_nombre']) ?>
        <?php endif; ?>
        <span class="badge badge-<?= htmlspecialchars($p['estado']) ?> pedido-badge" id="badge-<?= (int)$p['id'] ?>">
          <?= htmlspecialchars($p['estado']) ?>
        </span>
      </div>
      <?php if (!empty($p['items'])): ?>
        <?php foreach ($p['items'] as $it): ?>
        <div class="item-row" id="item-row-<?= (int)$it['id'] ?>">
          <div>
            <span style="font-size:.88rem;font-weight:500"><?= htmlspecialchars($it['platillo_nombre'] ?? $it['nombre'] ?? '') ?></span>
            <span style="font-size:.78rem;color:#9CA3AF;margin-left:4px">×<?= (int)$it['cantidad'] ?></span>
          </div>
          <div style="display:flex;align-items:center;gap:8px">
            <span class="badge badge-<?= htmlspecialchars($it['estado']) ?> item-badge" id="item-badge-<?= (int)$it['id'] ?>">
              <?= htmlspecialchars($it['estado']) ?>
            </span>
            <?php if ($it['estado'] === 'pendiente'): ?>
            <button class="btn-cancel" id="cancel-<?= (int)$it['id'] ?>"
                    onclick="cancelarPedido(<?= (int)$p['id'] ?>)">
              Cancelar
            </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Acciones -->
  <a href="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug'] ?? '') ?>/pagar/<?= (int)($visita['id'] ?? 0) ?>"
     class="link-btn" style="background:var(--cp);color:#fff;margin-bottom:10px">
    💳 Pagar mi cuenta
  </a>
  <a href="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug'] ?? '') ?>"
     class="link-btn" style="background:#F3F4F6;color:#374151">
    ← Agregar más items
  </a>

  <div style="margin-top:20px;font-size:.7rem;color:#9CA3AF;text-align:center">
    Potenciado por <strong>CarniHub</strong>
  </div>
</div>

<script>
const SLUG     = '<?= htmlspecialchars($restaurante['slug'] ?? '') ?>';
const VISITA   = <?= (int)($visita['id'] ?? 0) ?>;
const PASOS    = ['pendiente','en_preparacion','listo','entregado'];

const estadoLabel = {
  pendiente:'Recibido', en_preparacion:'Preparando', listo:'Listo', entregado:'Entregado'
};

let estadosAnteriores = {};
// Pre-cargar estados actuales del render inicial
<?php foreach ($pedidos as $p): ?>
estadosAnteriores[<?= (int)$p['id'] ?>] = '<?= htmlspecialchars($p['estado']) ?>';
<?php endforeach; ?>

function actualizarUI(data) {
  if (!data.ok) return;

  let estadoGlobal = 'entregado';
  data.pedidos.forEach(p => {
    if (p.estado === 'cancelado') return;
    const pi = PASOS.indexOf(p.estado), gi = PASOS.indexOf(estadoGlobal);
    if (pi < gi) estadoGlobal = p.estado;
  });

  // Barra de progreso
  PASOS.forEach((s, i) => {
    const el = document.getElementById('step-' + s);
    if (!el) return;
    const gi = PASOS.indexOf(estadoGlobal);
    el.className = 'estado-step' + (i < gi ? ' done' : i === gi ? ' active' : '');
  });

  // Tiempo estimado
  if (data.tiempo_min > 0 && estadoGlobal === 'en_preparacion') {
    document.getElementById('tiempoEst').style.display = 'block';
    document.getElementById('tiempoMin').textContent = data.tiempo_min;
  } else {
    document.getElementById('tiempoEst').style.display = 'none';
  }

  // Actualizar badges de pedido e ítem
  data.pedidos.forEach(p => {
    const badge = document.getElementById('badge-' + p.id);
    if (badge) { badge.className = 'badge badge-' + p.estado + ' pedido-badge'; badge.textContent = p.estado; }

    p.items.forEach(it => {
      const ib = document.getElementById('item-badge-' + it.id);
      if (ib) { ib.className = 'badge badge-' + it.estado + ' item-badge'; ib.textContent = it.estado; }
      // Quitar botón cancelar si ya no está pendiente
      if (it.estado !== 'pendiente') {
        const cb = document.getElementById('cancel-' + it.id);
        if (cb) cb.remove();
      }
    });
  });
}

function pollEstado() {
  fetch(`<?= BASE_URL ?>menu/${SLUG}/estadoPedido/${VISITA}`)
    .then(r => r.json())
    .then(actualizarUI)
    .catch(() => {});
}

// Iniciar polling
pollEstado();
setInterval(pollEstado, 5000);

function cancelarPedido(pedidoId) {
  if (!confirm('¿Cancelar este pedido?')) return;
  const btn = document.getElementById('cancel-' + pedidoId);
  if (btn) btn.disabled = true;

  fetch(`<?= BASE_URL ?>menu/${SLUG}/cancelarPedido/${pedidoId}`, { method: 'POST' })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        const badge = document.getElementById('badge-' + pedidoId);
        if (badge) { badge.className = 'badge badge-cancelado pedido-badge'; badge.textContent = 'cancelado'; }
        if (btn) btn.remove();
      } else {
        alert(d.msg ?? 'No se pudo cancelar');
        if (btn) btn.disabled = false;
      }
    })
    .catch(() => { if (btn) btn.disabled = false; });
}
</script>
</body>
</html>
