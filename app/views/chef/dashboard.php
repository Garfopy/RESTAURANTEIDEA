<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KDS — Cocina</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0D1117; color: #E6EDF3; font-family: system-ui, -apple-system, sans-serif; min-height: 100vh; }

    /* ── Topbar ── */
    .topbar {
      background: #161B22; border-bottom: 1px solid #30363D;
      padding: 0 20px; height: 56px;
      display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 10;
    }
    .topbar-brand { font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .topbar-right  { display: flex; align-items: center; gap: 16px; }
    .counter-badge {
      padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700;
    }
    .cb-azul   { background: #1D4ED820; color: #60A5FA; border: 1px solid #1D4ED840; }
    .cb-naranja{ background: #92400E20; color: #FBBF24; border: 1px solid #92400E40; }
    #clock { font-size: .85rem; color: #8B949E; font-variant-numeric: tabular-nums; }
    .exit-link { color: #6E7681; font-size: .78rem; text-decoration: none; }
    .exit-link:hover { color: #E6EDF3; }

    /* ── Layout columnas ── */
    .kds-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0;
      padding: 0;
    }
    .kds-col {
      padding: 16px;
      min-height: calc(100vh - 56px);
    }
    .kds-col-header {
      font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em;
      padding: 8px 12px; border-radius: 8px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;
    }
    .col-pendientes   .kds-col-header { background: #1D4ED815; color: #60A5FA; border: 1px solid #1D4ED830; }
    .col-preparacion  .kds-col-header { background: #92400E15; color: #FBBF24; border: 1px solid #92400E30; }
    .col-divider { width: 1px; background: #21262D; margin: 16px 0; }

    /* ── Cards ── */
    .kds-card {
      background: #161B22;
      border: 1px solid #30363D;
      border-radius: 14px;
      padding: 16px;
      margin-bottom: 12px;
      transition: border-color .25s;
    }
    .kds-card.urgente  { border-color: #EF4444; }
    .kds-card.alerta   { border-color: #F59E0B; }
    .kds-card.normal   { border-color: #3B82F6; }
    .kds-card.preparacion { border-color: #F59E0B; }

    .card-header {
      display: flex; justify-content: space-between; align-items: flex-start;
      margin-bottom: 12px; gap: 8px;
    }
    .card-folio  { font-weight: 800; font-size: 1rem; color: #E6EDF3; }
    .card-meta   { font-size: .75rem; color: #8B949E; margin-top: 2px; }
    .timer-badge {
      padding: 4px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700;
      white-space: nowrap; flex-shrink: 0;
    }
    .timer-normal  { background: #1D4ED820; color: #60A5FA; }
    .timer-alerta  { background: #92400E20; color: #FBBF24; }
    .timer-urgente { background: #7F1D1D20; color: #F87171; }

    /* ── Item row ── */
    .item-row {
      padding: 10px 0; border-bottom: 1px solid #21262D;
      display: flex; justify-content: space-between; align-items: center; gap: 8px;
    }
    .item-row:last-child { border-bottom: none; }
    .item-nombre  { font-weight: 600; font-size: .95rem; color: #E6EDF3; }
    .item-sub     { font-size: .75rem; color: #8B949E; margin-top: 2px; }
    .pill {
      display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: .7rem; font-weight: 600; margin-top: 4px;
    }
    .pill-exclu { background: #7F1D1D; color: #FCA5A5; }
    .pill-nota  { background: #1E3A5F; color: #93C5FD; }

    /* ── Botones ── */
    .btn-action {
      min-height: 44px; min-width: 90px;
      padding: 8px 16px; border-radius: 10px; border: none;
      font-size: .82rem; font-weight: 700; cursor: pointer; transition: opacity .15s;
    }
    .btn-action:disabled { opacity: .5; cursor: not-allowed; }
    .btn-action:active   { opacity: .75; }
    .btn-prep  { background: #D97706; color: #fff; }
    .btn-listo { background: #059669; color: #fff; }

    /* ── Empty ── */
    .empty-col { text-align: center; padding: 40px 16px; color: #484F58; font-size: .88rem; }

    /* ── Toast ── */
    #kds-toast {
      position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%) translateY(80px);
      background: #161B22; border: 1px solid #30363D; color: #E6EDF3;
      padding: 12px 22px; border-radius: 30px; font-size: .85rem; font-weight: 600;
      opacity: 0; transition: transform .35s cubic-bezier(.34,1.56,.64,1), opacity .35s; z-index: 99;
    }
    #kds-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-brand">
    🍳 KDS
    <span style="font-size:.85rem;color:#8B949E;font-weight:400"><?= htmlspecialchars($restaurante['nombre'] ?? 'Cocina') ?></span>
  </div>
  <div class="topbar-right">
    <span class="counter-badge cb-azul"   id="cnt-pendiente">— pendientes</span>
    <span class="counter-badge cb-naranja" id="cnt-preparacion">— en prep.</span>
    <span id="clock"></span>
    <a href="<?= BASE_URL ?>auth/logout" class="exit-link">Salir</a>
  </div>
</div>

<!-- Layout dos columnas -->
<div class="kds-grid">
  <div class="kds-col col-pendientes">
    <div class="kds-col-header">🔵 Pendientes</div>
    <div id="col-pendiente"></div>
  </div>
  <div class="col-divider"></div>
  <div class="kds-col col-preparacion">
    <div class="kds-col-header">🟡 En preparación</div>
    <div id="col-preparacion"></div>
  </div>
</div>

<div id="kds-toast"></div>

<script>
let prevIds  = new Set();
const BASE   = '<?= BASE_URL ?>';

// ── Sonido ────────────────────────────────────────
const alertSound = () => {
  try {
    const ctx = new AudioContext();
    const osc = ctx.createOscillator();
    const g   = ctx.createGain();
    osc.connect(g); g.connect(ctx.destination);
    osc.type = 'sine'; osc.frequency.value = 880;
    g.gain.setValueAtTime(.3, ctx.currentTime);
    g.gain.exponentialRampToValueAtTime(.001, ctx.currentTime + .6);
    osc.start(); osc.stop(ctx.currentTime + .6);
  } catch {}
};

// ── Toast ────────────────────────────────────────
let toastT;
function kdsToast(msg) {
  const t = document.getElementById('kds-toast');
  t.textContent = msg; clearTimeout(toastT);
  t.classList.add('show');
  toastT = setTimeout(() => t.classList.remove('show'), 3500);
}

// ── Reloj ────────────────────────────────────────
function tick() {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString('es-MX');
}
tick(); setInterval(tick, 1000);

// ── Timer transcurrido ───────────────────────────
function elapsed(createdAt) {
  const ms  = Date.now() - new Date(createdAt).getTime();
  const min = Math.floor(ms / 60000);
  if (min < 1)  return { label: 'Ahora',   cls: 'timer-normal' };
  if (min < 10) return { label: min + ' min', cls: 'timer-normal' };
  if (min < 20) return { label: min + ' min', cls: 'timer-alerta' };
  return { label: min + ' min ⚠️', cls: 'timer-urgente' };
}

function urgencyClass(createdAt) {
  const min = Math.floor((Date.now() - new Date(createdAt).getTime()) / 60000);
  if (min >= 20) return 'urgente';
  if (min >= 10) return 'alerta';
  return 'normal';
}

// ── Renderizar columna ───────────────────────────
function renderColumna(pedidos, colId) {
  const col = document.getElementById(colId);
  if (!pedidos.length) {
    col.innerHTML = '<div class="empty-col">✅ Sin órdenes</div>';
    return;
  }
  col.innerHTML = pedidos.map(ped => {
    const t   = elapsed(ped.created_at);
    const urg = urgencyClass(ped.created_at);
    const esPrepCol = colId === 'col-preparacion';

    const itemsHtml = ped.items.map(it => `
      <div class="item-row">
        <div style="flex:1">
          <div class="item-nombre">${it.platillo_nombre}</div>
          <div class="item-sub">×${it.cantidad}${it.tiempo_preparacion_min ? ' · ' + it.tiempo_preparacion_min + ' min' : ''}</div>
          ${it.exclusiones ? `<span class="pill pill-exclu">🚫 Sin: ${it.exclusiones}</span>` : ''}
          ${it.item_notas   ? `<span class="pill pill-nota">💬 ${it.item_notas}</span>`       : ''}
        </div>
        <div>
          ${!esPrepCol && it.item_estado === 'pendiente'
            ? `<button class="btn-action btn-prep"  onclick="marcar('${BASE}rest-chef/marcarPreparacion/${it.item_id}',this)">Prep. ▶</button>`
            : ''}
          ${esPrepCol && it.item_estado === 'en_preparacion'
            ? `<button class="btn-action btn-listo" onclick="marcar('${BASE}rest-chef/marcarListo/${it.item_id}',this)">Listo ✓</button>`
            : ''}
        </div>
      </div>
    `).join('');

    return `
      <div class="kds-card ${urg}${esPrepCol ? ' preparacion' : ''}">
        <div class="card-header">
          <div>
            <div class="card-folio">${ped.folio || 'Pedido'}</div>
            <div class="card-meta">🪑 ${ped.mesa_nombre || '—'}</div>
          </div>
          <span class="timer-badge ${t.cls}" data-created="${ped.created_at}">⏱ ${t.label}</span>
        </div>
        ${itemsHtml}
      </div>
    `;
  }).join('');
}

// ── Renderizar todo ──────────────────────────────
function renderQueue(items) {
  if (!items.length) {
    document.getElementById('col-pendiente').innerHTML   = '<div class="empty-col">✅ Sin órdenes pendientes</div>';
    document.getElementById('col-preparacion').innerHTML = '<div class="empty-col">✅ Sin órdenes en preparación</div>';
    document.getElementById('cnt-pendiente').textContent   = '0 pendientes';
    document.getElementById('cnt-preparacion').textContent = '0 en prep.';
    return;
  }

  // Agrupar por pedido
  const pedidosMap = {};
  for (const it of items) {
    if (!pedidosMap[it.id]) pedidosMap[it.id] = { ...it, items: [] };
    pedidosMap[it.id].items.push(it);
  }
  const pedidos = Object.values(pedidosMap);

  // Separar por columna: si TODOS los items están en_preparacion → columna derecha
  const pendientes  = pedidos.filter(p => p.items.some(i => i.item_estado === 'pendiente'));
  const preparacion = pedidos.filter(p => p.items.every(i => i.item_estado === 'en_preparacion'));

  renderColumna(pendientes,  'col-pendiente');
  renderColumna(preparacion, 'col-preparacion');

  document.getElementById('cnt-pendiente').textContent   = pendientes.length  + ' pendientes';
  document.getElementById('cnt-preparacion').textContent = preparacion.length + ' en prep.';

  // Detectar nuevos
  const newIds    = new Set(items.map(i => i.item_id));
  const hayNuevos = [...newIds].some(id => !prevIds.has(id));
  if (hayNuevos && prevIds.size > 0) { alertSound(); kdsToast('🍽️ ¡Nuevo pedido recibido!'); }
  prevIds = newIds;
}

// ── Actualizar timers cada 30s sin recargar ──────
setInterval(() => {
  document.querySelectorAll('[data-created]').forEach(el => {
    const t   = elapsed(el.dataset.created);
    el.textContent = '⏱ ' + t.label;
    el.className   = 'timer-badge ' + t.cls;
    const card     = el.closest('.kds-card');
    if (card) {
      const urg = urgencyClass(el.dataset.created);
      card.className = card.className.replace(/\b(normal|alerta|urgente)\b/, urg);
    }
  });
}, 30000);

// ── Marcar item ──────────────────────────────────
async function marcar(url, btn) {
  const orig = btn.textContent;
  btn.disabled = true; btn.textContent = '...';
  try {
    const res = await fetch(url, { method: 'POST', credentials: 'same-origin' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    await loadQueue();
  } catch (e) {
    console.error('marcar:', e);
    btn.disabled = false; btn.textContent = orig;
  }
}

// ── Cargar queue ─────────────────────────────────
async function loadQueue() {
  try {
    const res = await fetch(BASE + 'rest-chef/queue?t=' + Date.now(), { credentials: 'same-origin' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    renderQueue(await res.json());
  } catch (e) {
    console.error('loadQueue:', e);
  }
}

loadQueue();
setInterval(loadQueue, 5000);
</script>
</body>
</html>
