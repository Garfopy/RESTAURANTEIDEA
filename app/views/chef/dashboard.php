<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KDS — Cocina</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { background: #111827; color: #F9FAFB; font-family: system-ui, sans-serif; }
    .card { background: #1F2937; border: 1px solid #374151; border-radius: 12px; padding: 16px; margin: 8px; min-width: 220px; }
    .card.en_preparacion { border-color: #F59E0B; }
    .card.pendiente { border-color: #3B82F6; }
    .badge { padding: 2px 8px; border-radius: 99px; font-size: .72rem; font-weight: 600; }
    .btn { padding: 8px 16px; border-radius: 8px; font-size: .8rem; font-weight: 600; cursor: pointer; border: none; }
    .btn-amarillo { background: #F59E0B; color: #000; }
    .btn-verde    { background: #10B981; color: #fff; }
    .topbar { background: #1F2937; border-bottom: 1px solid #374151; padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; }
    #nuevo-audio { display: none; }
  </style>
</head>
<body>
<div class="topbar">
  <div style="font-size:1.1rem;font-weight:700">
    🍳 KDS — <?= htmlspecialchars($restaurante['nombre'] ?? 'Cocina') ?>
  </div>
  <div style="display:flex;align-items:center;gap:16px">
    <div id="clock" style="font-size:1rem;color:#9CA3AF"></div>
    <a href="<?= BASE_URL ?>auth/logout" style="color:#6B7280;font-size:.8rem">Salir</a>
  </div>
</div>

<div style="padding:16px">
  <div id="kds-container" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-start">
    <div style="color:#6B7280;font-size:.9rem;padding:32px;width:100%;text-align:center" id="empty-msg">
      Cargando órdenes...
    </div>
  </div>
</div>

<script>
let prevIds = new Set();
const baseUrl = '<?= BASE_URL ?>';

const alertSound = () => {
  try {
    const ctx = new AudioContext();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain); gain.connect(ctx.destination);
    osc.type = 'sine'; osc.frequency.value = 880;
    gain.gain.setValueAtTime(.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(.001, ctx.currentTime + .6);
    osc.start(); osc.stop(ctx.currentTime + .6);
  } catch {}
};

function estadoBadge(estado) {
  const map = {
    pendiente:       ['#3B82F6','Pendiente'],
    en_preparacion:  ['#F59E0B','En prep.'],
    listo:           ['#10B981','Listo'],
    entregado:       ['#6B7280','Entregado'],
  };
  const [c, l] = map[estado] || ['#9CA3AF', estado];
  return `<span class="badge" style="background:${c}22;color:${c}">${l}</span>`;
}

function renderQueue(items) {
  const container = document.getElementById('kds-container');
  const emptyMsg  = document.getElementById('empty-msg');

  if (!items.length) {
    container.innerHTML = '';
    emptyMsg.style.display = 'block';
    emptyMsg.textContent = '✅ Sin órdenes pendientes';
    return;
  }
  emptyMsg.style.display = 'none';

  // Agrupar por pedido
  const pedidos = {};
  for (const item of items) {
    if (!pedidos[item.id]) pedidos[item.id] = { ...item, items: [] };
    pedidos[item.id].items.push(item);
  }

  const newIds = new Set(items.map(i => i.item_id));
  const hayNuevos = [...newIds].some(id => !prevIds.has(id));
  if (hayNuevos && prevIds.size > 0) alertSound();
  prevIds = newIds;

  container.innerHTML = Object.values(pedidos).map(ped => `
    <div class="card ${ped.items[0]?.item_estado || ''}">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <div style="font-weight:700;font-size:1rem">${ped.folio || ''}</div>
        <div style="font-size:.8rem;color:#9CA3AF">${ped.mesa_nombre || '—'}</div>
      </div>
      ${ped.items.map(it => `
        <div style="padding:8px 0;border-bottom:1px solid #374151;display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-weight:600">${it.platillo_nombre}</div>
            <div style="font-size:.78rem;color:#9CA3AF">x${it.cantidad} · ${it.tiempo_preparacion_min}min</div>
            ${it.exclusiones ? `<div style="font-size:.75rem;background:#7F1D1D;color:#FCA5A5;border-radius:6px;padding:2px 7px;margin-top:3px;display:inline-block">🚫 Sin: ${it.exclusiones}</div>` : ''}
            ${it.item_notas ? `<div style="font-size:.75rem;background:#1E3A5F;color:#93C5FD;border-radius:6px;padding:2px 7px;margin-top:3px;display:inline-block">💬 ${it.item_notas}</div>` : ''}
          </div>
          <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end">
            ${estadoBadge(it.item_estado)}
            ${it.item_estado === 'pendiente' ?
              `<button class="btn btn-amarillo" onclick="marcar('${baseUrl}rest-chef/marcarPreparacion/${it.item_id}', this)">Prep. ▶</button>` : ''}
            ${it.item_estado === 'en_preparacion' ?
              `<button class="btn btn-verde" onclick="marcar('${baseUrl}rest-chef/marcarListo/${it.item_id}', this)">Listo ✓</button>` : ''}
          </div>
        </div>
      `).join('')}
      <div style="font-size:.72rem;color:#6B7280;margin-top:8px">${new Date(ped.created_at).toLocaleTimeString('es-MX')}</div>
    </div>
  `).join('');
}

async function marcar(url, btn) {
  btn.disabled = true;
  await fetch(url, { method: 'POST' });
  await loadQueue();
}

async function loadQueue() {
  try {
    const res = await fetch('<?= BASE_URL ?>rest-chef/queue');
    const data = await res.json();
    renderQueue(data);
  } catch (e) {}
}

// Reloj
function tick() {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString('es-MX');
}
tick(); setInterval(tick, 1000);

loadQueue();
setInterval(loadQueue, 5000);
</script>
</body>
</html>
