<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($kdsTitle ?? 'KDS - Cocina') ?></title>
  <style>
    :root {
      --bg: #10100f;
      --panel: #1a1a18;
      --panel-soft: #22221f;
      --line: #32322e;
      --line-soft: #282824;
      --text: #eef2f5;
      --muted: #9aa6b2;
      --faint: #74716a;
      --ready: #20a36b;
      --work: #d1872b;
      --danger: #d94b4b;
      --info: #5f94d6;
      --shadow: 0 14px 34px rgba(0, 0, 0, .24);
    }

    *, *::before, *::after { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      background: var(--bg);
      color: var(--text);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      -webkit-font-smoothing: antialiased;
    }

    button, a { -webkit-tap-highlight-color: transparent; }

    .topbar {
      position: sticky;
      top: 0;
      z-index: 20;
      min-height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      padding: 10px 18px;
      background: rgba(15, 19, 24, .96);
      border-bottom: 1px solid var(--line);
      backdrop-filter: blur(12px);
    }

    .brand {
      min-width: 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand-mark {
      width: 38px;
      height: 38px;
      border-radius: 8px;
      display: grid;
      place-items: center;
      background: #24241f;
      border: 1px solid var(--line);
      color: var(--text);
      font-weight: 800;
      letter-spacing: 0;
    }

    .brand-title {
      font-size: 1rem;
      font-weight: 800;
      line-height: 1.1;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .brand-subtitle {
      margin-top: 3px;
      color: var(--muted);
      font-size: .78rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .topbar-actions {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 8px;
      flex-wrap: wrap;
    }

    .stat {
      min-height: 34px;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 0 11px;
      border: 1px solid var(--line);
      border-radius: 8px;
      background: var(--panel);
      color: var(--muted);
      font-size: .78rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .stat strong { color: var(--text); font-variant-numeric: tabular-nums; }
    .stat-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--info); }
    .stat-work .stat-dot { background: var(--work); }
    .stat-live .stat-dot { background: var(--ready); box-shadow: 0 0 0 4px rgba(32, 163, 107, .12); }
    .stat-live.is-offline .stat-dot { background: var(--danger); box-shadow: 0 0 0 4px rgba(217, 75, 75, .12); }

    .clock {
      color: var(--muted);
      font-size: .8rem;
      font-variant-numeric: tabular-nums;
      white-space: nowrap;
    }

    .icon-btn, .exit-link {
      min-width: 36px;
      height: 36px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid var(--line);
      border-radius: 8px;
      background: var(--panel);
      color: var(--muted);
      text-decoration: none;
      font-size: .82rem;
      font-weight: 800;
      cursor: pointer;
      transition: background .15s, color .15s, border-color .15s, transform .12s;
    }

    .icon-btn:hover, .exit-link:hover {
      color: var(--text);
      background: var(--panel-soft);
      border-color: #394553;
    }

    .icon-btn:active, .exit-link:active { transform: translateY(1px); }
    .icon-btn.is-active { color: var(--ready); border-color: rgba(32, 163, 107, .45); }

    .toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 14px 18px 0;
    }

    .filters {
      display: inline-flex;
      padding: 4px;
      border: 1px solid var(--line);
      border-radius: 8px;
      background: #141412;
      overflow-x: auto;
      max-width: 100%;
    }

    .filter-btn {
      height: 32px;
      padding: 0 12px;
      border: 0;
      border-radius: 6px;
      background: transparent;
      color: var(--muted);
      font-size: .76rem;
      font-weight: 800;
      cursor: pointer;
      white-space: nowrap;
    }

    .filter-btn.active {
      background: var(--panel-soft);
      color: var(--text);
      box-shadow: inset 0 0 0 1px var(--line);
    }

    .sync-meta {
      color: var(--faint);
      font-size: .75rem;
      white-space: nowrap;
    }

    .kds-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
      padding: 14px 18px 18px;
    }

    .kds-col {
      min-height: calc(100vh - 144px);
      padding: 12px;
      background: #151512;
      border: 1px solid var(--line);
      border-radius: 8px;
    }

    .kds-col-header {
      position: sticky;
      top: 65px;
      z-index: 10;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      min-height: 38px;
      padding: 0 4px 10px;
      background: #151512;
      color: var(--muted);
      font-size: .72rem;
      font-weight: 900;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .col-count {
      min-width: 26px;
      height: 24px;
      padding: 0 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      background: var(--panel-soft);
      color: var(--text);
      font-size: .72rem;
      font-variant-numeric: tabular-nums;
      letter-spacing: 0;
    }

    .section-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin: 4px 0 8px;
      color: var(--faint);
      font-size: .68rem;
      font-weight: 900;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .section-title span:last-child {
      color: var(--muted);
      font-variant-numeric: tabular-nums;
      letter-spacing: 0;
    }

    .kds-card {
      margin-bottom: 10px;
      padding: 13px;
      background: var(--panel);
      border: 1px solid var(--line);
      border-left-width: 4px;
      border-radius: 8px;
      box-shadow: var(--shadow);
    }

    .kds-card.normal { border-left-color: var(--info); }
    .kds-card.alerta { border-left-color: var(--work); }
    .kds-card.urgente { border-left-color: var(--danger); }
    .kds-card.preparacion { border-left-color: var(--work); }

    .card-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--line-soft);
    }

    .card-main { min-width: 0; }
    .card-folio { font-weight: 900; font-size: 1rem; letter-spacing: 0; }

    .card-meta-row {
      display: flex;
      align-items: center;
      gap: 7px;
      flex-wrap: wrap;
      margin-top: 7px;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      min-height: 24px;
      max-width: 100%;
      padding: 0 8px;
      border: 1px solid var(--line);
      border-radius: 999px;
      background: #141412;
      color: var(--muted);
      font-size: .7rem;
      font-weight: 800;
      white-space: nowrap;
    }

    .chip-strong { color: var(--text); }
    .timer-badge {
      flex-shrink: 0;
      min-height: 30px;
      display: inline-flex;
      align-items: center;
      padding: 0 10px;
      border-radius: 8px;
      font-size: .74rem;
      font-weight: 900;
      font-variant-numeric: tabular-nums;
      white-space: nowrap;
      background: #141412;
      border: 1px solid var(--line);
    }

    .timer-normal { color: #8fb8ea; }
    .timer-alerta { color: #f0b85b; }
    .timer-urgente { color: #ff8b8b; }

    .item-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 12px;
      padding: 12px 0 0;
    }

    .item-row + .item-row {
      margin-top: 12px;
      border-top: 1px solid var(--line-soft);
    }

    .item-title {
      display: flex;
      align-items: baseline;
      gap: 8px;
      min-width: 0;
      color: var(--text);
      font-weight: 800;
      font-size: .95rem;
      line-height: 1.25;
    }

    .platillo-codigo {
      flex-shrink: 0;
      padding: 2px 6px;
      border-radius: 6px;
      background: #282822;
      color: #cdd6df;
      font-family: "Cascadia Code", "SFMono-Regular", Consolas, monospace;
      font-size: .72rem;
      font-weight: 900;
    }

    .item-name {
      min-width: 0;
      overflow-wrap: anywhere;
    }

    .item-sub {
      margin-top: 4px;
      color: var(--muted);
      font-size: .76rem;
    }

    .pill-row {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 5px;
      margin-top: 7px;
    }

    .pill {
      display: inline-flex;
      align-items: center;
      max-width: 100%;
      min-height: 24px;
      padding: 0 8px;
      border-radius: 6px;
      font-size: .72rem;
      font-weight: 750;
      overflow-wrap: anywhere;
    }

    .pill-exclu {
      background: rgba(217, 75, 75, .12);
      border: 1px solid rgba(217, 75, 75, .35);
      color: #ffb1b1;
    }

    .pill-nota {
      background: rgba(95, 148, 214, .12);
      border: 1px solid rgba(95, 148, 214, .35);
      color: #b8d3f3;
    }

    .receta-block {
      margin-top: 10px;
      padding: 10px;
      background: #151512;
      border: 1px solid var(--line-soft);
      border-radius: 8px;
    }

    .receta-title, .receta-group-label {
      color: var(--faint);
      font-size: .66rem;
      font-weight: 900;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .receta-title { margin-bottom: 8px; }
    .receta-group { margin-top: 8px; }
    .receta-group-label { margin-bottom: 5px; }
    .receta-group-mp .receta-group-label { color: #ffb1b1; }
    .receta-group-gn .receta-group-label { color: #9ee5bd; }

    .armado-instr {
      margin-bottom: 9px;
      padding: 8px 9px;
      border-left: 3px solid var(--ready);
      border-radius: 6px;
      background: rgba(32, 163, 107, .10);
      color: #d8f6e5;
      font-size: .78rem;
      line-height: 1.45;
      white-space: pre-wrap;
    }

    .ing-list {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 5px;
    }

    .ing-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      max-width: 100%;
      min-height: 26px;
      padding: 0 8px;
      background: #20201c;
      border: 1px solid #393932;
      border-radius: 6px;
      color: #dce4eb;
      font-size: .73rem;
    }

    .ing-chip.tipo-mp { border-color: rgba(217, 75, 75, .35); background: rgba(217, 75, 75, .10); }
    .ing-chip.tipo-gn { border-color: rgba(32, 163, 107, .35); background: rgba(32, 163, 107, .10); }
    .ing-chip.informativo { border-style: dashed; opacity: .82; }

    .ing-code {
      flex-shrink: 0;
      color: #ffffff;
      font-family: "Cascadia Code", "SFMono-Regular", Consolas, monospace;
      font-size: .72rem;
      font-weight: 900;
    }

    .ing-name { overflow-wrap: anywhere; }
    .ing-qty { color: var(--muted); font-size: .7rem; font-variant-numeric: tabular-nums; }
    .ing-note { color: var(--faint); font-size: .68rem; font-style: italic; }

    .btn-action {
      min-width: 100px;
      min-height: 42px;
      padding: 0 14px;
      border: 0;
      border-radius: 8px;
      color: #fff;
      font-size: .82rem;
      font-weight: 900;
      cursor: pointer;
      white-space: nowrap;
      transition: filter .15s, transform .12s, opacity .15s;
    }

    .btn-action:hover { filter: brightness(1.06); }
    .btn-action:active { transform: translateY(1px); }
    .btn-action:disabled { cursor: wait; opacity: .62; }
    .btn-prep { background: var(--work); }
    .btn-listo { background: var(--ready); }

    .empty-col {
      min-height: 170px;
      display: grid;
      place-items: center;
      padding: 28px 16px;
      border: 1px dashed var(--line);
      border-radius: 8px;
      color: var(--faint);
      font-size: .9rem;
      text-align: center;
    }

    #kds-toast {
      position: fixed;
      left: 50%;
      bottom: 18px;
      z-index: 99;
      max-width: min(460px, calc(100vw - 28px));
      padding: 11px 16px;
      border: 1px solid var(--line);
      border-radius: 8px;
      background: #202832;
      color: var(--text);
      box-shadow: var(--shadow);
      font-size: .86rem;
      font-weight: 750;
      opacity: 0;
      transform: translateX(-50%) translateY(18px);
      pointer-events: none;
      transition: opacity .2s, transform .2s;
    }

    #kds-toast.show {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }

    body.compact .kds-card { padding: 10px; margin-bottom: 8px; }
    body.compact .card-header { padding-bottom: 8px; }
    body.compact .item-row { padding-top: 9px; }
    body.compact .item-row + .item-row { margin-top: 9px; }
    body.compact .receta-block { display: none; }

    @media (max-width: 920px) {
      .topbar { align-items: flex-start; flex-direction: column; }
      .topbar-actions { width: 100%; justify-content: flex-start; }
      .toolbar { align-items: stretch; flex-direction: column; }
      .sync-meta { padding-left: 2px; }
      .kds-grid { grid-template-columns: 1fr; }
      .kds-col { min-height: auto; }
      .kds-col-header { top: 0; position: relative; }
    }

    @media (max-width: 560px) {
      .topbar, .toolbar, .kds-grid { padding-left: 10px; padding-right: 10px; }
      .brand { width: 100%; }
      .brand-title, .brand-subtitle { max-width: calc(100vw - 82px); }
      .stat { flex: 1 1 auto; justify-content: center; }
      .clock { width: 100%; }
      .item-row { grid-template-columns: 1fr; }
      .item-row .btn-action { width: 100%; }
    }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="brand">
      <div class="brand-mark"><?= htmlspecialchars(substr((string)($kdsIcon ?? 'K'), 0, 1)) ?></div>
      <div>
        <div class="brand-title"><?= htmlspecialchars($kdsBrand ?? 'KDS Cocina') ?></div>
        <div class="brand-subtitle"><?= htmlspecialchars($restaurante['nombre'] ?? ($kdsIcon ?? 'Cocina')) ?></div>
      </div>
    </div>

    <div class="topbar-actions">
      <span class="stat" title="Ordenes pendientes"><span class="stat-dot"></span><strong id="cnt-pendiente">0</strong> pendientes</span>
      <span class="stat stat-work" title="Ordenes en preparacion"><span class="stat-dot"></span><strong id="cnt-preparacion">0</strong> en prep.</span>
      <span class="stat stat-live" id="net-status" title="Estado de conexion"><span class="stat-dot"></span><span id="net-label">En linea</span></span>
      <span class="clock" id="clock"></span>
      <button type="button" class="icon-btn is-active" id="sound-toggle" title="Sonido">S</button>
      <button type="button" class="icon-btn" id="compact-toggle" title="Vista compacta">C</button>
      <button type="button" class="icon-btn" id="refresh-btn" title="Actualizar">R</button>
      <a href="<?= BASE_URL ?>auth/logoutStaff/<?= urlencode($kdsLogoutRol ?? 'chef') ?>" class="exit-link" title="Salir">Salir</a>
    </div>
  </header>

  <div class="toolbar">
    <nav class="filters" aria-label="Filtrar pedidos">
      <button type="button" class="filter-btn active" data-filter="all">Todos</button>
      <button type="button" class="filter-btn" data-filter="eat_in">Mesa</button>
      <button type="button" class="filter-btn" data-filter="delivery">Delivery</button>
      <button type="button" class="filter-btn" data-filter="pickup">Mostrador</button>
    </nav>
    <div class="sync-meta" id="sync-meta">Sin sincronizar</div>
  </div>

  <main class="kds-grid">
    <section class="kds-col col-pendientes" aria-labelledby="pendientes-title">
      <div class="kds-col-header" id="pendientes-title">
        <span>Pendientes</span>
        <span class="col-count" id="col-count-pendiente">0</span>
      </div>
      <div id="col-pendiente"></div>
    </section>

    <section class="kds-col col-preparacion" aria-labelledby="preparacion-title">
      <div class="kds-col-header" id="preparacion-title">
        <span>En preparacion</span>
        <span class="col-count" id="col-count-preparacion">0</span>
      </div>
      <div id="col-preparacion"></div>
    </section>
  </main>

  <div id="kds-toast" role="status" aria-live="polite"></div>

  <script>
    let prevIds = new Set();
    let currentItems = [];
    let activeFilter = 'all';
    let loading = false;
    let toastT;

    const BASE = <?= json_encode(BASE_URL) ?>;
    const KDS_ROUTE = <?= json_encode($kdsBaseRoute ?? 'rest-chef') ?>;
    const POLL_MS = 5000;
    const soundKey = 'kds_sound_enabled';
    const compactKey = 'kds_compact_enabled';

    const els = {
      pending: document.getElementById('col-pendiente'),
      prep: document.getElementById('col-preparacion'),
      pendingCount: document.getElementById('cnt-pendiente'),
      prepCount: document.getElementById('cnt-preparacion'),
      pendingColCount: document.getElementById('col-count-pendiente'),
      prepColCount: document.getElementById('col-count-preparacion'),
      clock: document.getElementById('clock'),
      toast: document.getElementById('kds-toast'),
      sync: document.getElementById('sync-meta'),
      net: document.getElementById('net-status'),
      netLabel: document.getElementById('net-label'),
      sound: document.getElementById('sound-toggle'),
      compact: document.getElementById('compact-toggle'),
      refresh: document.getElementById('refresh-btn')
    };

    const ORDER_TYPE_LABELS = {
      eat_in: 'Mesa',
      delivery: 'Delivery',
      pickup: 'Mostrador'
    };

    function storageEnabled(key, fallback) {
      try {
        const value = localStorage.getItem(key);
        if (value === null) return fallback;
        return value === '1';
      } catch (e) {
        return fallback;
      }
    }

    function setStorageFlag(key, enabled) {
      try {
        localStorage.setItem(key, enabled ? '1' : '0');
      } catch (e) {}
    }

    let soundEnabled = storageEnabled(soundKey, true);
    let compactEnabled = storageEnabled(compactKey, false);

    function setSound(enabled) {
      soundEnabled = enabled;
      setStorageFlag(soundKey, enabled);
      els.sound.classList.toggle('is-active', enabled);
      els.sound.textContent = enabled ? 'S' : 'M';
      els.sound.title = enabled ? 'Silenciar avisos' : 'Activar avisos';
    }

    function setCompact(enabled) {
      compactEnabled = enabled;
      setStorageFlag(compactKey, enabled);
      document.body.classList.toggle('compact', enabled);
      els.compact.classList.toggle('is-active', enabled);
    }

    function setOnlineState(isOnline) {
      els.net.classList.toggle('is-offline', !isOnline);
      els.netLabel.textContent = isOnline ? 'En linea' : 'Sin conexion';
    }

    function alertSound() {
      if (!soundEnabled) return;
      try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(740, ctx.currentTime);
        osc.frequency.setValueAtTime(920, ctx.currentTime + .12);
        gain.gain.setValueAtTime(.22, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(.001, ctx.currentTime + .55);
        osc.start();
        osc.stop(ctx.currentTime + .55);
      } catch (e) {}
    }

    function kdsToast(msg) {
      els.toast.textContent = msg;
      clearTimeout(toastT);
      els.toast.classList.add('show');
      toastT = setTimeout(() => els.toast.classList.remove('show'), 3200);
    }

    function tick() {
      els.clock.textContent = new Date().toLocaleTimeString('es-MX', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
    }

    function parseDate(value) {
      if (!value) return new Date();
      const normalized = String(value).replace(' ', 'T');
      const date = new Date(normalized);
      return Number.isNaN(date.getTime()) ? new Date() : date;
    }

    function elapsed(createdAt) {
      const min = Math.max(0, Math.floor((Date.now() - parseDate(createdAt).getTime()) / 60000));
      if (min < 1) return { label: 'Ahora', cls: 'timer-normal' };
      if (min < 10) return { label: min + ' min', cls: 'timer-normal' };
      if (min < 20) return { label: min + ' min', cls: 'timer-alerta' };
      return { label: min + ' min', cls: 'timer-urgente' };
    }

    function urgencyClass(createdAt) {
      const min = Math.max(0, Math.floor((Date.now() - parseDate(createdAt).getTime()) / 60000));
      if (min >= 20) return 'urgente';
      if (min >= 10) return 'alerta';
      return 'normal';
    }

    function esc(value) {
      return String(value ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      }[c]));
    }

    function tipoPedido(ped) {
      const raw = String(ped.tipo_pedido || ped.tipo_entrega || '').toLowerCase().replace(/\s+/g, '_');
      if (raw === 'delivery' || raw === 'domicilio') return 'delivery';
      if (raw === 'pickup' || raw === 'pick_up' || raw === 'takeaway' || raw === 'para_llevar') return 'pickup';
      if (raw === 'eat_in' || raw === 'mesa' || raw === 'comedor') return 'eat_in';
      return ped.mesa_nombre ? 'eat_in' : 'pickup';
    }

    function ubicacionPedido(ped, tipo) {
      if (tipo === 'delivery') return ped.direccion_entrega || 'Delivery';
      if (tipo === 'pickup') return 'Mostrador';
      return ped.mesa_nombre || 'Sin mesa';
    }

    function sortPedidos(pedidos) {
      const order = { eat_in: 0, delivery: 1, pickup: 2 };
      return [...pedidos].sort((a, b) => {
        const ta = tipoPedido(a);
        const tb = tipoPedido(b);
        if (order[ta] !== order[tb]) return order[ta] - order[tb];
        return parseDate(a.created_at) - parseDate(b.created_at);
      });
    }

    function formatQty(value) {
      const number = Number(value) || 0;
      return Number.isInteger(number) ? String(number) : number.toFixed(2).replace(/\.?0+$/, '');
    }

    function renderReceta(raw, cantidadPlatillo, instruccionesArmado) {
      const cant = Number(cantidadPlatillo) || 1;
      const items = (raw || '').split('||').map(s => {
        const [codigo, nombre, tipo, qty, unidad, notas, esInfo] = s.split('|');
        return {
          codigo: (codigo || '').trim(),
          nombre: (nombre || '').trim(),
          tipo: (tipo || 'otro').trim().toLowerCase(),
          qty: (Number(qty) || 0) * cant,
          unidad: (unidad || '').trim(),
          notas: (notas || '').trim(),
          esInfo: (esInfo || '').trim() === '1'
        };
      }).filter(i => i.nombre);

      if (!items.length && !instruccionesArmado) return '';

      const materiaPrima = items.filter(i => i.tipo === 'materia_prima');
      const guarniciones = items.filter(i => i.tipo === 'guarnicion');
      const otros = items.filter(i => i.tipo !== 'materia_prima' && i.tipo !== 'guarnicion');

      const chip = (i, cls) => `
        <span class="ing-chip ${cls}${i.esInfo ? ' informativo' : ''}" title="${esc(i.notas || i.nombre)}">
          ${i.codigo ? `<span class="ing-code">${esc(i.codigo)}</span>` : ''}
          <span class="ing-name">${esc(i.nombre)}</span>
          ${i.qty ? `<span class="ing-qty">${formatQty(i.qty)} ${esc(i.unidad)}</span>` : ''}
          ${i.notas ? `<span class="ing-note">${esc(i.notas)}</span>` : ''}
        </span>`;

      const grupo = (label, arr, cls, chipCls) => arr.length
        ? `<div class="receta-group ${cls}">
             <div class="receta-group-label">${label} (${arr.length})</div>
             <div class="ing-list">${arr.map(i => chip(i, chipCls)).join('')}</div>
           </div>`
        : '';

      return `
        <div class="receta-block">
          <div class="receta-title">Armado</div>
          ${instruccionesArmado ? `<div class="armado-instr">${esc(instruccionesArmado)}</div>` : ''}
          ${grupo('Materia prima', materiaPrima, 'receta-group-mp', 'tipo-mp')}
          ${grupo('Guarniciones', guarniciones, 'receta-group-gn', 'tipo-gn')}
          ${grupo('Otros', otros, 'receta-group-ot', '')}
        </div>`;
    }

    function groupItems(items, estado) {
      const filtered = items.filter(it => it.item_estado === estado);
      const map = {};
      for (const it of filtered) {
        if (!map[it.id]) map[it.id] = { ...it, items: [] };
        map[it.id].items.push(it);
      }
      return sortPedidos(Object.values(map));
    }

    function renderColumn(pedidos, colId, estado) {
      const target = colId === 'col-pendiente' ? els.pending : els.prep;
      if (!pedidos.length) {
        target.innerHTML = `<div class="empty-col">Sin ordenes ${estado === 'pendiente' ? 'pendientes' : 'en preparacion'}</div>`;
        return;
      }

      target.innerHTML = pedidos.map((ped, idx) => {
        const tipo = tipoPedido(ped);
        const prevTipo = idx > 0 ? tipoPedido(pedidos[idx - 1]) : null;
        const typeHeader = prevTipo !== tipo
          ? `<div class="section-title"><span>${esc(ORDER_TYPE_LABELS[tipo] || 'Pedido')}</span><span>${pedidos.filter(p => tipoPedido(p) === tipo).length}</span></div>`
          : '';
        const timer = elapsed(ped.created_at);
        const urgent = urgencyClass(ped.created_at);
        const isPrep = estado === 'en_preparacion';
        const location = ubicacionPedido(ped, tipo);

        const itemsHtml = ped.items.map(it => `
          <div class="item-row">
            <div>
              <div class="item-title">
                ${it.platillo_codigo ? `<span class="platillo-codigo">${esc(it.platillo_codigo)}</span>` : ''}
                <span class="item-name">${esc(it.platillo_nombre)}</span>
              </div>
              <div class="item-sub">${formatQty(it.cantidad)} unidad${Number(it.cantidad) === 1 ? '' : 'es'}${it.tiempo_preparacion_min ? ' · ' + esc(it.tiempo_preparacion_min) + ' min' : ''}</div>
              <div class="pill-row">
                ${it.exclusiones ? `<span class="pill pill-exclu">Sin: ${esc(it.exclusiones)}</span>` : ''}
                ${it.item_notas ? `<span class="pill pill-nota">${esc(it.item_notas)}</span>` : ''}
                ${it.extras_display ? `<span class="pill pill-nota">Extras: ${esc(it.extras_display)}</span>` : ''}
              </div>
              ${renderReceta(it.ingredientes_raw, it.cantidad, it.instrucciones_armado)}
            </div>
            <button
              type="button"
              class="btn-action ${isPrep ? 'btn-listo' : 'btn-prep'}"
              data-action-url="${BASE}${KDS_ROUTE}/${isPrep ? 'marcarListo' : 'marcarPreparacion'}/${encodeURIComponent(it.item_id)}">
              ${isPrep ? 'Listo' : 'Preparar'}
            </button>
          </div>
        `).join('');

        return `${typeHeader}
          <article class="kds-card ${urgent}${isPrep ? ' preparacion' : ''}">
            <header class="card-header">
              <div class="card-main">
                <div class="card-folio">${esc(ped.folio || 'Pedido')}</div>
                <div class="card-meta-row">
                  <span class="chip chip-strong">${esc(ORDER_TYPE_LABELS[tipo] || 'Pedido')}</span>
                  <span class="chip">${esc(location)}</span>
                </div>
              </div>
              <span class="timer-badge ${timer.cls}" data-created="${esc(ped.created_at)}">${timer.label}</span>
            </header>
            ${itemsHtml}
          </article>`;
      }).join('');
    }

    function renderQueue(items) {
      currentItems = Array.isArray(items) ? items : [];
      const visibleItems = activeFilter === 'all'
        ? currentItems
        : currentItems.filter(it => tipoPedido(it) === activeFilter);

      const pendientes = groupItems(visibleItems, 'pendiente');
      const preparacion = groupItems(visibleItems, 'en_preparacion');
      const pendingItems = visibleItems.filter(it => it.item_estado === 'pendiente').length;
      const prepItems = visibleItems.filter(it => it.item_estado === 'en_preparacion').length;

      renderColumn(pendientes, 'col-pendiente', 'pendiente');
      renderColumn(preparacion, 'col-preparacion', 'en_preparacion');

      els.pendingCount.textContent = String(pendientes.length);
      els.prepCount.textContent = String(preparacion.length);
      els.pendingColCount.textContent = String(pendingItems);
      els.prepColCount.textContent = String(prepItems);

      const newIds = new Set(currentItems.map(i => i.item_id));
      const hasNew = [...newIds].some(id => !prevIds.has(id));
      if (hasNew && prevIds.size > 0) {
        alertSound();
        kdsToast('Nuevo pedido recibido');
      }
      prevIds = newIds;
    }

    function updateTimers() {
      document.querySelectorAll('[data-created]').forEach(el => {
        const timer = elapsed(el.dataset.created);
        el.textContent = timer.label;
        el.className = 'timer-badge ' + timer.cls;
        const card = el.closest('.kds-card');
        if (card) {
          const urgent = urgencyClass(el.dataset.created);
          card.className = card.className.replace(/\b(normal|alerta|urgente)\b/, urgent);
        }
      });
    }

    async function marcar(url, btn) {
      const original = btn.textContent;
      btn.disabled = true;
      btn.textContent = '...';
      try {
        const res = await fetch(url, { method: 'POST', credentials: 'same-origin' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        await loadQueue({ silent: true });
      } catch (e) {
        console.error('marcar:', e);
        btn.disabled = false;
        btn.textContent = original;
        kdsToast('No se pudo actualizar el item');
      }
    }

    async function loadQueue(options = {}) {
      if (loading) return;
      loading = true;
      els.refresh.disabled = true;
      try {
        const res = await fetch(BASE + KDS_ROUTE + '/queue?t=' + Date.now(), { credentials: 'same-origin' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        renderQueue(data);
        setOnlineState(true);
        els.sync.textContent = 'Actualizado ' + new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
      } catch (e) {
        console.error('loadQueue:', e);
        setOnlineState(false);
        if (!options.silent) kdsToast('No se pudo sincronizar la cola');
      } finally {
        loading = false;
        els.refresh.disabled = false;
      }
    }

    document.querySelector('.filters').addEventListener('click', event => {
      const btn = event.target.closest('[data-filter]');
      if (!btn) return;
      activeFilter = btn.dataset.filter;
      document.querySelectorAll('.filter-btn').forEach(el => el.classList.toggle('active', el === btn));
      renderQueue(currentItems);
    });

    document.addEventListener('click', event => {
      const btn = event.target.closest('[data-action-url]');
      if (!btn) return;
      marcar(btn.dataset.actionUrl, btn);
    });

    els.sound.addEventListener('click', () => setSound(!soundEnabled));
    els.compact.addEventListener('click', () => setCompact(!compactEnabled));
    els.refresh.addEventListener('click', () => loadQueue());
    window.addEventListener('online', () => setOnlineState(true));
    window.addEventListener('offline', () => setOnlineState(false));

    setSound(soundEnabled);
    setCompact(compactEnabled);
    setOnlineState(navigator.onLine);
    tick();
    setInterval(tick, 1000);
    setInterval(updateTimers, 30000);
    loadQueue();
    setInterval(loadQueue, POLL_MS);
  </script>
</body>
</html>
