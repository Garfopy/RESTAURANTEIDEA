<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mesero — <?= htmlspecialchars($restaurante['nombre'] ?? '') ?></title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #F3F4F6; font-family: system-ui, -apple-system, sans-serif; min-height: 100vh; }

    /* ── Topbar ── */
    .topbar {
      background: #fff; border-bottom: 1px solid #E5E7EB;
      padding: 0 20px; height: 56px; position: sticky; top: 0; z-index: 20;
      display: flex; align-items: center; justify-content: space-between;
    }
    .topbar-brand { font-weight: 700; font-size: 1rem; color: #111827; display: flex; align-items: center; gap: 10px; }
    .topbar-right  { display: flex; align-items: center; gap: 10px; }
    .badge-cnt {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 22px; height: 22px; padding: 0 6px; border-radius: 11px;
      font-size: .72rem; font-weight: 700; line-height: 1;
    }
    .bc-rojo   { background: #FEE2E2; color: #991B1B; }
    .bc-verde  { background: #DCFCE7; color: #166534; }
    .bc-gris   { background: #F3F4F6; color: #6B7280; }
    .btn-top { padding: 8px 14px; border-radius: 8px; font-size: .83rem; font-weight: 600; text-decoration: none; }
    .btn-primario { background: #C8102E; color: #fff; }
    .exit-link { color: #6B7280; font-size: .78rem; text-decoration: none; }

    /* ── Sección ── */
    .section { padding: 0 16px 20px; }
    .section-title { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em;
                     color: #9CA3AF; margin: 20px 0 10px; }

    /* ── Panel alertas ── */
    #alertasBanner {
      margin: 12px 16px 0;
      background: #FFFBEB; border: 1px solid #FCD34D; border-radius: 14px; padding: 14px;
      animation: slideIn .3s ease;
    }
    #alertasBanner.hidden { display: none; }
    @keyframes slideIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
    .alerta-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 8px 0; border-bottom: 1px solid #FEF3C7;
    }
    .alerta-row:last-child { border-bottom: none; }

    /* ── Listos panel ── */
    #listosBanner {
      margin: 12px 16px 0;
      background: #F0FDF4; border: 1px solid #86EFAC; border-radius: 14px; padding: 14px;
    }
    #listosBanner.hidden { display: none; }
    .listo-card {
      background: #fff; border: 1px solid #D1FAE5; border-radius: 10px;
      padding: 12px; margin-bottom: 8px;
      display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;
    }
    .listo-card:last-child { margin-bottom: 0; }

    /* ── Grid de mesas ── */
    .mesas-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px;
      padding: 0 16px;
    }
    .mesa-card {
      background: #fff; border: 2px solid #E5E7EB; border-radius: 14px;
      padding: 14px 10px; text-align: center; cursor: pointer;
      transition: transform .15s, border-color .2s;
      position: relative;
    }
    .mesa-card:active { transform: scale(.96); }
    .mesa-card.disponible { border-color: #10B981; }
    .mesa-card.ocupada    { border-color: #F59E0B; }
    .mesa-card.pagando    { border-color: #EF4444; }
    .mesa-card.reservada  { border-color: #6366F1; }
    .mesa-estado-dot {
      display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px;
    }
    .dot-disponible { background: #10B981; }
    .dot-ocupada    { background: #F59E0B; }
    .dot-pagando    { background: #EF4444; }
    .dot-reservada  { background: #6366F1; }
    .pedidos-badge {
      position: absolute; top: 8px; right: 8px;
      background: #F59E0B; color: #fff; font-size: .65rem; font-weight: 700;
      padding: 1px 6px; border-radius: 10px; display: none;
    }

    /* ── Botones ── */
    .btn-sm {
      padding: 6px 14px; border-radius: 8px; border: none;
      font-size: .78rem; font-weight: 600; cursor: pointer;
    }
    .btn-atender  { background: #F59E0B; color: #fff; }
    .btn-entregar { background: #10B981; color: #fff; }
    .btn-sm:disabled { opacity: .5; cursor: not-allowed; }

    /* ── Modal ── */
    #modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,.45);
      display: flex; align-items: flex-end; justify-content: center;
      z-index: 50; opacity: 0; pointer-events: none; transition: opacity .25s;
    }
    #modal-overlay.open { opacity: 1; pointer-events: all; }
    #modal-sheet {
      background: #fff; border-radius: 20px 20px 0 0; padding: 24px 20px 32px;
      width: 100%; max-width: 480px; max-height: 80vh; overflow-y: auto;
      transform: translateY(100%); transition: transform .3s cubic-bezier(.34,1.56,.64,1);
    }
    #modal-overlay.open #modal-sheet { transform: translateY(0); }

    /* ── Toast ── */
    #m-toast {
      position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(80px);
      background: #111827; color: #fff; padding: 12px 22px; border-radius: 30px;
      font-size: .85rem; font-weight: 600; opacity: 0; z-index: 99;
      transition: transform .35s cubic-bezier(.34,1.56,.64,1), opacity .35s;
    }
    #m-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <div class="topbar-brand">
    🍽 Mesero
    <span style="font-size:.82rem;color:#6B7280;font-weight:400"><?= htmlspecialchars($restaurante['nombre'] ?? '') ?></span>
  </div>
  <div class="topbar-right">
    <span id="badge-alertas" class="badge-cnt bc-gris" title="Alertas">—</span>
    <span id="badge-listos"  class="badge-cnt bc-gris" title="Listos">—</span>
    <a href="<?= BASE_URL ?>rest-pedido/nuevo" class="btn-top btn-primario">+ Pedido</a>
    <a href="<?= BASE_URL ?>auth/logoutStaff/mesero" class="exit-link">Salir</a>
  </div>
</div>

<!-- Alertas de comensales -->
<div id="alertasBanner" class="hidden">
  <div style="font-weight:700;color:#92400E;font-size:.88rem;margin-bottom:10px;display:flex;align-items:center;gap:6px">
    🔔 Solicitudes de comensales <span id="cnt-alertas-text"></span>
  </div>
  <div id="alertasList"></div>
</div>

<!-- Órdenes listas para entregar -->
<div id="listosBanner" class="hidden">
  <div style="font-weight:700;color:#166534;font-size:.88rem;margin-bottom:10px;display:flex;align-items:center;gap:6px">
    ✅ Listos para entregar <span id="cnt-listos-text"></span>
  </div>
  <div id="listosList"></div>
</div>

<!-- Mesas -->
<div class="section">
  <div class="section-title" style="padding-left:0">Mesas</div>
</div>
<div class="mesas-grid">
  <?php foreach ($mesas as $m): ?>
  <div class="mesa-card <?= htmlspecialchars($m['estado']) ?>"
       onclick="abrirMesa(<?= (int)$m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nombre'])) ?>', '<?= htmlspecialchars($m['estado']) ?>')"
       id="mesa-card-<?= (int)$m['id'] ?>">
    <div id="badge-pedidos-<?= (int)$m['id'] ?>" class="pedidos-badge"></div>
    <div style="font-size:1.6rem;margin-bottom:6px">🪑</div>
    <div style="font-weight:700;font-size:.92rem;color:#111827"><?= htmlspecialchars($m['nombre']) ?></div>
    <div style="font-size:.7rem;color:#9CA3AF;margin-top:2px"><?= (int)$m['capacidad'] ?> personas</div>
    <div style="font-size:.7rem;font-weight:700;margin-top:6px;display:flex;align-items:center;justify-content:center">
      <span class="mesa-estado-dot dot-<?= htmlspecialchars($m['estado']) ?>"></span>
      <?= strtoupper($m['estado']) ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Modal detalle de mesa -->
<div id="modal-overlay" onclick="cerrarModal(event)">
  <div id="modal-sheet">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <div>
        <div id="modal-mesa-nombre" style="font-size:1.1rem;font-weight:700"></div>
        <div id="modal-mesa-estado" style="font-size:.78rem;color:#6B7280;margin-top:2px"></div>
      </div>
      <a id="modal-nuevo-pedido" href="#" style="padding:8px 14px;background:#C8102E;color:#fff;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none">+ Pedido</a>
    </div>
    <div id="modal-pedidos" style="font-size:.85rem;color:#6B7280;text-align:center;padding:20px 0">Cargando...</div>
  </div>
</div>

<div id="m-toast"></div>

<script>
const BASE = '<?= BASE_URL ?>';
const TIPO_LABEL = { mesero: '🙋 Llama al mesero', cuenta: '💳 Pide la cuenta' };
const ESTADO_LABEL = { pendiente:'Recibido', en_preparacion:'Preparando', listo:'¡Listo!', entregado:'Entregado', cancelado:'Cancelado' };
const ESTADO_COLOR = { pendiente:'#FEF3C7', en_preparacion:'#DBEAFE', listo:'#DCFCE7', entregado:'#F3F4F6', cancelado:'#FEE2E2' };
const ESTADO_TEXT  = { pendiente:'#92400E', en_preparacion:'#1E40AF', listo:'#166534', entregado:'#6B7280', cancelado:'#991B1B' };

// ── Toast ───────────────────────────────────────────────────────────────────
let toastT;
function toast(msg) {
  const t = document.getElementById('m-toast');
  t.textContent = msg; clearTimeout(toastT);
  t.classList.add('show');
  toastT = setTimeout(() => t.classList.remove('show'), 3000);
}

// ── Vibrar ──────────────────────────────────────────────────────────────────
function vibrar() { try { navigator.vibrate && navigator.vibrate(200); } catch {} }

// ── Alertas polling ─────────────────────────────────────────────────────────
let prevAlertasCount = 0;

function pollAlertas() {
  fetch(BASE + 'rest-mesero/alertas')
    .then(r => r.json())
    .then(d => {
      if (!d.ok) return;
      const cnt = d.alertas.length;

      // Topbar badge
      const badge = document.getElementById('badge-alertas');
      badge.textContent = cnt;
      badge.className   = 'badge-cnt ' + (cnt > 0 ? 'bc-rojo' : 'bc-gris');

      const banner = document.getElementById('alertasBanner');
      if (!cnt) { banner.classList.add('hidden'); prevAlertasCount = 0; return; }

      if (cnt > prevAlertasCount && prevAlertasCount > 0) vibrar();
      prevAlertasCount = cnt;

      document.getElementById('cnt-alertas-text').textContent = `(${cnt})`;
      let html = '';
      d.alertas.forEach(a => {
        const label = TIPO_LABEL[a.tipo] ?? a.tipo;
        html += `<div class="alerta-row">
          <span style="font-size:.85rem;color:#78350F">
            <strong>${label}</strong>
            ${a.mesa_nombre ? ' · Mesa <strong>' + a.mesa_nombre + '</strong>' : ''}
          </span>
          <button class="btn-sm btn-atender" onclick="atenderAlerta(${a.id},this)">Atendido ✓</button>
        </div>`;
      });
      document.getElementById('alertasList').innerHTML = html;
      banner.classList.remove('hidden');
    })
    .catch(() => {});
}

function atenderAlerta(id, btn) {
  btn.disabled = true;
  fetch(`${BASE}rest-mesero/atenderAlerta/${id}`, { method: 'POST' })
    .then(r => r.json())
    .then(d => { if (d.ok) pollAlertas(); else btn.disabled = false; })
    .catch(() => { btn.disabled = false; });
}

// ── Listos polling ──────────────────────────────────────────────────────────
let prevListosIds = new Set();

function pollListos() {
  fetch(BASE + 'rest-mesero/listos')
    .then(r => r.json())
    .then(d => {
      if (!d.ok) return;
      const listos = d.listos;
      const cnt    = listos.length;

      // Topbar badge
      const badge = document.getElementById('badge-listos');
      badge.textContent = cnt;
      badge.className   = 'badge-cnt ' + (cnt > 0 ? 'bc-verde' : 'bc-gris');

      const banner = document.getElementById('listosBanner');
      if (!cnt) { banner.classList.add('hidden'); prevListosIds = new Set(); return; }

      // Detectar nuevos listos
      const newIds = new Set(listos.map(l => l.id));
      const hayNuevos = [...newIds].some(id => !prevListosIds.has(id));
      if (hayNuevos && prevListosIds.size > 0) { vibrar(); toast('🔔 ¡Pedido listo para entregar!'); }
      prevListosIds = newIds;

      document.getElementById('cnt-listos-text').textContent = `(${cnt})`;
      let html = '';
      listos.forEach(p => {
        const itemsText = (p.items || []).map(i => `${i.cantidad}× ${i.nombre}`).join(', ');
        html += `<div class="listo-card" id="listo-${p.id}">
          <div style="flex:1">
            <div style="font-weight:700;font-size:.9rem;color:#111827">${p.folio} · Mesa ${p.mesa_nombre || '—'}</div>
            ${itemsText ? `<div style="font-size:.78rem;color:#6B7280;margin-top:3px">${itemsText}</div>` : ''}
          </div>
          <button class="btn-sm btn-entregar" onclick="marcarEntregado(${p.id},this)">Entregado ✓</button>
        </div>`;
      });
      document.getElementById('listosList').innerHTML = html;
      banner.classList.remove('hidden');
    })
    .catch(() => {});
}

function marcarEntregado(pedidoId, btn) {
  btn.disabled = true; btn.textContent = '...';
  fetch(`${BASE}rest-mesero/marcarEntregado/${pedidoId}`, { method: 'POST' })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        const card = document.getElementById('listo-' + pedidoId);
        if (card) {
          card.style.opacity = '0';
          card.style.transition = 'opacity .3s';
          setTimeout(() => { card.remove(); pollListos(); }, 300);
        } else {
          pollListos();
        }
        toast('✅ Pedido marcado como entregado');
      } else {
        btn.disabled = false; btn.textContent = 'Entregado ✓';
      }
    })
    .catch(() => { btn.disabled = false; btn.textContent = 'Entregado ✓'; });
}

// ── Modal mesa ───────────────────────────────────────────────────────────────
let modalMesaId = null;

function abrirMesa(mesaId, nombre, estado) {
  modalMesaId = mesaId;
  document.getElementById('modal-mesa-nombre').textContent = 'Mesa: ' + nombre;
  document.getElementById('modal-mesa-estado').textContent = 'Estado: ' + estado;
  document.getElementById('modal-nuevo-pedido').href = BASE + 'rest-pedido/nuevo/' + mesaId;
  document.getElementById('modal-pedidos').innerHTML = '<div style="text-align:center;padding:20px;color:#9CA3AF">Cargando pedidos...</div>';
  document.getElementById('modal-overlay').classList.add('open');

  fetch(BASE + 'rest-mesero/pedidosMesa/' + mesaId)
    .then(r => r.json())
    .then(d => {
      const cont = document.getElementById('modal-pedidos');
      if (!d.ok || !d.pedidos.length) {
        cont.innerHTML = '<div style="text-align:center;padding:20px;color:#9CA3AF">Sin pedidos activos en esta mesa.</div>';
        return;
      }
      cont.innerHTML = d.pedidos.map(p => {
        const col = ESTADO_COLOR[p.estado] || '#F3F4F6';
        const txt = ESTADO_TEXT[p.estado]  || '#374151';
        const itemsHtml = (p.items || []).map(it =>
          `<div style="padding:6px 0;border-bottom:1px solid #F3F4F6;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:.85rem">${it.cantidad}× ${it.nombre}</span>
            <span style="font-size:.72rem;font-weight:600;padding:2px 8px;border-radius:10px;background:${ESTADO_COLOR[it.estado]||'#F3F4F6'};color:${ESTADO_TEXT[it.estado]||'#374151'}">${ESTADO_LABEL[it.estado]||it.estado}</span>
          </div>`
        ).join('');
        return `<div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:12px;margin-bottom:10px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <span style="font-weight:700;font-size:.88rem">${p.folio}</span>
            <span style="font-size:.72rem;font-weight:600;padding:2px 10px;border-radius:10px;background:${col};color:${txt}">${ESTADO_LABEL[p.estado]||p.estado}</span>
          </div>
          ${itemsHtml}
          ${p.estado === 'listo'
            ? `<button class="btn-sm btn-entregar" style="width:100%;margin-top:8px" onclick="marcarEntregado(${p.id},this)">Entregado ✓</button>`
            : ''}
        </div>`;
      }).join('');
    })
    .catch(() => {
      document.getElementById('modal-pedidos').innerHTML = '<div style="text-align:center;padding:20px;color:#9CA3AF">No se pudieron cargar los pedidos.</div>';
    });
}

function cerrarModal(e) {
  if (e.target === document.getElementById('modal-overlay')) {
    document.getElementById('modal-overlay').classList.remove('open');
    modalMesaId = null;
  }
}

// ── Iniciar polling ──────────────────────────────────────────────────────────
pollAlertas();
pollListos();
setInterval(pollAlertas, 5000);
setInterval(pollListos,  5000);
</script>
</body>
</html>
