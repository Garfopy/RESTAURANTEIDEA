<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($restaurante['nombre']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/restaurant.css">
  <style>
    /* ── Variables de marca ─────────────────────── */
    :root {
      --cp: <?= htmlspecialchars($restaurante['color_primario']  ?? '#C8102E') ?>;
      --cs: <?= htmlspecialchars($restaurante['color_secundario'] ?? '#1f2937') ?>;
      --gold: #c9a430;
      --gold-dim: rgba(201,164,48,.16);
      --dark-bg:  #0d0d18;
      --dark-card:#161624;
      --dark-line:rgba(255,255,255,.08);
      --text-main:#f0eee8;
      --text-muted:rgba(240,238,232,.52);
    }

    /* ── Reset ──────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; }
    body { margin:0; font-family:'Inter',system-ui,sans-serif; background:var(--dark-bg); color:var(--text-main); -webkit-font-smoothing:antialiased; }
    a { color:inherit; text-decoration:none; }

    /* ── Barra de tabs ──────────────────────────── */
    .mn-tab-bar { display:flex; gap:6px; padding:12px 16px 10px; overflow-x:auto; scrollbar-width:none; position:sticky; top:0; background:rgba(13,13,24,.96); backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px); z-index:20; border-bottom:1px solid var(--dark-line); }
    .mn-tab-bar::-webkit-scrollbar { display:none; }
    .mn-tab { padding:7px 18px; border-radius:99px; font-size:.82rem; font-weight:600; border:1.5px solid rgba(255,255,255,.14); background:transparent; color:var(--text-muted); cursor:pointer; white-space:nowrap; transition:all .18s; flex-shrink:0; }
    .mn-tab:hover { border-color:var(--gold); color:var(--gold); }
    .mn-tab.active { background:var(--gold); border-color:var(--gold); color:#0d0d18; }

    /* ── Secciones y grid ───────────────────────── */
    .mn-section-title { padding:20px 16px 8px; font-family:'Playfair Display',Georgia,serif; font-size:1.15rem; color:var(--gold); letter-spacing:.02em; border-bottom:1px solid var(--dark-line); margin:0 16px 14px; }
    .mn-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(164px,1fr)); gap:14px; padding:0 16px 14px; }

    /* ── Tarjeta ────────────────────────────────── */
    .mn-card { background:var(--dark-card); border:1px solid var(--dark-line); border-radius:16px; overflow:hidden; display:flex; flex-direction:column; cursor:pointer; transition:border-color .2s,box-shadow .2s,transform .18s; }
    .mn-card:hover { border-color:var(--gold); box-shadow:0 4px 28px rgba(201,164,48,.16); transform:translateY(-2px); }
    .mn-card-img { height:110px; position:relative; overflow:hidden; }
    .mn-card-img img { width:100%; height:100%; object-fit:cover; }
    .mn-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:2.6rem; background:linear-gradient(135deg,#1a1a2e 0%,#252538 100%); }
    .mn-price-badge { position:absolute; top:8px; right:8px; background:var(--gold); color:#0d0d18; font-size:.75rem; font-weight:800; padding:3px 9px; border-radius:99px; }
    .mn-card-body { padding:11px 13px 13px; display:flex; flex-direction:column; flex:1; gap:6px; }
    .mn-card-name { font-family:'Playfair Display',Georgia,serif; font-size:.92rem; font-weight:700; line-height:1.25; color:var(--text-main); }
    .mn-chips { display:flex; flex-wrap:wrap; gap:4px; margin-top:2px; }
    .mn-chip { font-size:.6rem; font-weight:600; padding:2px 7px; border-radius:99px; border:1px solid rgba(201,164,48,.3); color:rgba(201,164,48,.75); background:rgba(201,164,48,.06); white-space:nowrap; }
    .mn-chip-more { font-size:.6rem; color:var(--text-muted); align-self:center; }
    .mn-card-btn { margin-top:auto; padding:9px 0; background:var(--gold-dim); border:1px solid rgba(201,164,48,.3); border-radius:10px; color:var(--gold); font-size:.82rem; font-weight:700; cursor:pointer; width:100%; transition:background .15s; letter-spacing:.02em; }
    .mn-card-btn:hover { background:rgba(201,164,48,.26); }

    /* ── Carrito flotante ───────────────────────── */
    .pub-cart-bar { position:fixed; bottom:0; left:0; right:0; background:linear-gradient(90deg,#1a1a0a,#111108); border-top:1px solid var(--gold); color:#fff; padding:14px 20px; display:flex; justify-content:space-between; align-items:center; z-index:99; transform:translateY(100%); transition:transform .3s cubic-bezier(.4,0,.2,1); box-shadow:0 -4px 32px rgba(201,164,48,.18); }
    .pub-cart-bar.visible { transform:translateY(0); }
    .pub-cart-btn { padding:10px 24px; background:var(--gold); color:#0d0d18; border:none; border-radius:10px; font-weight:800; font-size:.9rem; cursor:pointer; transition:filter .15s; }
    .pub-cart-btn:hover { filter:brightness(1.1); }

    /* ── Modal / bottom-sheet ───────────────────── */
    .mn-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.72); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); z-index:200; align-items:flex-end; justify-content:center; }
    .mn-backdrop.open { display:flex; }
    .mn-sheet { background:#16162a; border-radius:24px 24px 0 0; width:100%; max-width:480px; max-height:88vh; overflow-y:auto; animation:slideUp .28s cubic-bezier(.34,1.12,.64,1) both; border-top:1px solid var(--gold); scrollbar-width:thin; scrollbar-color:#2a2a3e transparent; }
    .mn-sheet::-webkit-scrollbar { width:4px; }
    .mn-sheet::-webkit-scrollbar-thumb { background:#2a2a3e; border-radius:4px; }
    @keyframes slideUp { from { transform:translateY(100%); opacity:0; } to { transform:translateY(0); opacity:1; } }
    .mn-drag { width:40px; height:4px; background:rgba(255,255,255,.18); border-radius:99px; margin:12px auto 0; }
    .mn-sheet-hdr { padding:16px 20px 4px; }
    .mn-sheet-title { font-family:'Playfair Display',Georgia,serif; font-size:1.2rem; font-weight:700; color:var(--text-main); margin:0 0 2px; }
    .mn-sheet-sub { font-size:.82rem; color:var(--text-muted); margin:0 0 14px; }
    .mn-sec { padding:0 20px 16px; }
    .mn-sec-lbl { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--gold); margin-bottom:10px; }
    .mn-guar-row { display:flex; align-items:center; gap:10px; padding:9px 13px; border-radius:12px; border:1px solid var(--dark-line); margin-bottom:6px; background:rgba(255,255,255,.03); transition:all .15s; }
    .mn-guar-row.excl { opacity:.45; border-color:rgba(239,68,68,.25); background:rgba(239,68,68,.04); }
    .mn-guar-tog { flex:1; display:flex; align-items:center; gap:8px; cursor:pointer; font-size:.85rem; color:var(--text-main); }
    .mn-tog-icon { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:800; flex-shrink:0; transition:all .15s; }
    .mn-tog-icon.incl { background:rgba(34,197,94,.18); color:#4ade80; }
    .mn-tog-icon.excl { background:rgba(239,68,68,.18); color:#f87171; }
    .mn-extra-btn { font-size:.7rem; font-weight:700; padding:3px 8px; border-radius:99px; border:1px solid rgba(201,164,48,.38); color:var(--gold); background:var(--gold-dim); cursor:pointer; white-space:nowrap; flex-shrink:0; transition:background .12s; }
    .mn-extra-btn:hover { background:rgba(201,164,48,.28); }
    .mn-xcnt { display:none; align-items:center; gap:6px; flex-shrink:0; }
    .mn-xcnt.show { display:flex; }
    .mn-xcnt button { width:24px; height:24px; border-radius:50%; border:1px solid rgba(255,255,255,.2); background:transparent; color:#fff; font-weight:700; cursor:pointer; font-size:.85rem; display:flex; align-items:center; justify-content:center; }
    .mn-xcnt span { font-weight:700; font-size:.85rem; min-width:16px; text-align:center; }
    .mn-nota { width:100%; padding:10px 14px; border:1px solid var(--dark-line); border-radius:12px; background:rgba(255,255,255,.04); color:var(--text-main); font-size:.875rem; resize:none; outline:none; font-family:inherit; transition:border-color .15s; }
    .mn-nota::placeholder { color:var(--text-muted); }
    .mn-nota:focus { border-color:var(--gold); }
    .mn-sheet-foot { padding:14px 20px max(16px,env(safe-area-inset-bottom)); border-top:1px solid var(--dark-line); display:flex; gap:12px; align-items:center; background:#16162a; position:sticky; bottom:0; }
    .mn-qty { display:flex; align-items:center; gap:10px; background:rgba(255,255,255,.06); border-radius:10px; padding:8px 12px; }
    .mn-qty button { width:28px; height:28px; border-radius:50%; border:1px solid rgba(255,255,255,.2); background:transparent; color:#fff; font-weight:700; font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; }
    .mn-qty span { font-weight:700; min-width:20px; text-align:center; }
    .mn-add-btn { flex:1; padding:13px; background:var(--gold); color:#0d0d18; border:none; border-radius:12px; font-size:.95rem; font-weight:800; cursor:pointer; transition:filter .15s; }
    .mn-add-btn:hover { filter:brightness(1.1); }
    .mn-footer { padding:28px 20px 110px; text-align:center; font-size:.75rem; color:rgba(255,255,255,.18); }
    @keyframes fadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:translateY(0); } }
    .mn-card { animation:fadeIn .22s ease both; }
  </style>
</head>
<body>

<!-- Hero -->
<div class="pub-hero">
  <?php if ($restaurante['logo']): ?>
  <img src="<?= BASE_URL . htmlspecialchars($restaurante['logo']) ?>" alt=""
       style="height:48px;object-fit:contain;margin-bottom:10px;display:block;margin-inline:auto">
  <?php endif; ?>
  <h1 style="font-family:'Playfair Display',Georgia,serif;font-size:1.5rem;font-weight:800;margin:0 0 4px">
    <?= htmlspecialchars($restaurante['nombre']) ?>
  </h1>
  <?php if ($restaurante['descripcion']): ?>
  <p style="font-size:.85rem;opacity:.7;margin:0;line-height:1.45">
    <?= htmlspecialchars($restaurante['descripcion']) ?>
  </p>
  <?php endif; ?>

  <?php if ($mesa): ?>
  <div style="display:inline-flex;align-items:center;gap:6px;margin-top:10px;
              background:rgba(255,255,255,.14);border-radius:8px;padding:6px 12px;font-size:.85rem">
    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
      <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
    </svg>
    Mesa: <strong><?= htmlspecialchars($mesa['nombre']) ?></strong>
  </div>
  <div style="margin-top:10px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
    <button id="btnLlamarMesero" onclick="llamarMesero()"
            style="padding:7px 16px;background:rgba(255,255,255,.14);border:1.5px solid rgba(255,255,255,.38);
                   color:#fff;border-radius:20px;font-size:.82rem;font-weight:600;cursor:pointer">
      🔔 Llamar mesero
    </button>
    <?php if ($visitaId): ?>
    <a href="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug']) ?>/pagar/<?= $visitaId ?>"
       style="padding:7px 16px;background:rgba(255,255,255,.14);border:1.5px solid rgba(255,255,255,.38);
              color:#fff;border-radius:20px;font-size:.82rem;font-weight:600">
      🧾 Ver mi cuenta
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if ($visitaId): ?>
  <div id="statusTracker" style="margin-top:12px;background:rgba(255,255,255,.1);border-radius:10px;
                                  padding:10px 14px;font-size:.82rem;display:none">
    <div id="statusContent">Verificando estado del pedido…</div>
  </div>
  <?php endif; ?>
</div>

<!-- Tabs de categoría -->
<?php
$catIconos = [];
foreach ($categorias as $cat) {
    $n = mb_strtolower($cat['nombre']);
    if (str_contains($n,'bebida'))                               $catIconos[$cat['id']] = '🥂';
    elseif (str_contains($n,'postre')||str_contains($n,'dulce')) $catIconos[$cat['id']] = '🍮';
    else                                                         $catIconos[$cat['id']] = '🫔';
}
?>
<div class="mn-tab-bar">
  <button class="mn-tab active" data-cat="">✨ Todos</button>
  <?php foreach ($categorias as $cat): ?>
  <button class="mn-tab" data-cat="<?= (int)$cat['id'] ?>">
    <?= $catIconos[$cat['id']] ?> <?= htmlspecialchars($cat['nombre']) ?>
  </button>
  <?php endforeach; ?>
</div>

<!-- Formulario (hidden inputs generados por JS) -->
<form id="formPedido" method="POST"
      action="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug']) ?>/ordenar">
  <input type="hidden" name="mesa_qr"   value="<?= htmlspecialchars($mesa['qr_codigo'] ?? '') ?>">
  <input type="hidden" name="visita_id" id="inpVisitaId" value="<?= (int)($visitaId ?? 0) ?>">
  <div id="hiddenContainer"></div>
</form>

<!-- Platillos por sección -->
<?php if (empty($platillos)): ?>
<div style="padding:72px 24px;text-align:center;color:rgba(240,238,232,.3)">
  <div style="font-size:3.5rem;margin-bottom:10px">🍽️</div>
  <div style="font-family:'Playfair Display',Georgia,serif;font-size:1.1rem;margin-bottom:6px;color:rgba(240,238,232,.55)">
    Menú en preparación
  </div>
  <div style="font-size:.88rem;line-height:1.6">
    Aún no hay platillos disponibles.<br>Vuelve pronto o pide ayuda al personal.
  </div>
</div>
<?php else: ?>
<?php
$porCategoria = [];
foreach ($platillos as $p) { $porCategoria[(int)($p['categoria_id'] ?? 0)][] = $p; }
$catNombres = array_column($categorias, 'nombre', 'id');
?>
<?php foreach ($porCategoria as $cid => $platos): ?>
<div class="mn-section" data-section-cat="<?= $cid ?>">
  <?php if (isset($catNombres[$cid])): ?>
  <div class="mn-section-title"><?= ($catIconos[$cid] ?? '🍽') ?> <?= htmlspecialchars($catNombres[$cid]) ?></div>
  <?php endif; ?>
  <div class="mn-grid">
  <?php foreach ($platos as $p):
    $pId    = (int)$p['id'];
    $ings   = $recetaIngredientes[$pId] ?? [];
    $chips  = array_slice($ings, 0, 3);
    $more   = count($ings) - count($chips);
    $icon   = $catIconos[$cid] ?? '🍽';
  ?>
  <div class="mn-card" data-cat="<?= $cid ?>" onclick="abrirModal(<?= $pId ?>)">
    <div class="mn-card-img">
      <?php if (!empty($p['imagen'])): ?>
      <img src="<?= BASE_URL . htmlspecialchars($p['imagen']) ?>" alt="">
      <?php else: ?>
      <div class="mn-placeholder"><?= $icon ?></div>
      <?php endif; ?>
      <span class="mn-price-badge">$<?= number_format((float)$p['precio'], 0) ?></span>
    </div>
    <div class="mn-card-body">
      <div class="mn-card-name"><?= htmlspecialchars($p['nombre']) ?></div>
      <?php if (!empty($ings)): ?>
      <div class="mn-chips">
        <?php foreach ($chips as $ch): ?>
        <span class="mn-chip"><?= htmlspecialchars($ch['ingrediente_nombre']) ?></span>
        <?php endforeach; ?>
        <?php if ($more > 0): ?><span class="mn-chip-more">+<?= $more ?> más</span><?php endif; ?>
      </div>
      <?php endif; ?>
      <button type="button" class="mn-card-btn">Ordenar</button>
    </div>
  </div>
  <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Carrito flotante -->
<div class="pub-cart-bar" id="carritoBar">
  <div>
    <div style="font-size:.78rem;opacity:.6" id="carritoItems">0 items</div>
    <div style="font-weight:800;font-size:1.1rem;color:var(--gold)" id="carritoTotal">$0</div>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <?php if ($visitaId): ?>
    <button id="btnPedirMismo" onclick="pedirLoMismo()"
            style="padding:8px 14px;background:rgba(255,255,255,.1);color:#fff;
                   border:1.5px solid rgba(255,255,255,.25);border-radius:10px;
                   font-size:.78rem;font-weight:600;cursor:pointer;display:none">
      🔁 Lo mismo
    </button>
    <?php endif; ?>
    <button onclick="submitPedido()" class="pub-cart-btn">Ordenar →</button>
  </div>
</div>

<div class="mn-footer">Potenciado por <strong>CarniHub</strong></div>

<!-- ── Modal de personalización ───────────────────────────────────────────── -->
<div id="mnBackdrop" class="mn-backdrop" onclick="handleBackdrop(event)">
  <div class="mn-sheet">
    <div class="mn-drag"></div>
    <div class="mn-sheet-hdr" style="position:relative">
      <button onclick="cerrarModal()" type="button"
              style="position:absolute;top:0;right:0;background:none;border:none;
                     color:var(--text-muted);font-size:1.3rem;line-height:1;
                     cursor:pointer;padding:6px 10px">✕</button>
      <div class="mn-sheet-title" id="sheetTitle">—</div>
      <div class="mn-sheet-sub"   id="sheetSub">—</div>
    </div>
    <div class="mn-sec" id="guarSec" style="display:none">
      <div class="mn-sec-lbl">Guarniciones incluidas</div>
      <div id="guarList"></div>
    </div>
    <div class="mn-sec">
      <div class="mn-sec-lbl">Comentario al chef <span style="text-transform:none;font-weight:400;color:var(--text-muted)">(opcional)</span></div>
      <textarea id="sheetNota" class="mn-nota" rows="2" placeholder="Ej: bien cocido, sin picante, extra salsa…"></textarea>
    </div>
    <div class="mn-sheet-foot">
      <div class="mn-qty">
        <button type="button" onclick="cambiarQty(-1)">−</button>
        <span id="sheetQty">1</span>
        <button type="button" onclick="cambiarQty(1)">+</button>
      </div>
      <button type="button" class="mn-add-btn" id="addBtn" onclick="confirmarModal()">Agregar · $0</button>
    </div>
  </div>
</div>

<!-- ── JavaScript ──────────────────────────────────────────────────────────── -->
<script>
const BASE_URL  = '<?= BASE_URL ?>';
const REST_SLUG = '<?= htmlspecialchars($restaurante['slug']) ?>';
const REST_ID   = <?= (int)$restaurante['id'] ?>;
<?php if ($mesa): ?>
const MESA_QR   = '<?= htmlspecialchars($mesa['qr_codigo'] ?? '') ?>';
<?php else: ?>
const MESA_QR   = '';
<?php endif; ?>
const VID       = <?= (int)($visitaId ?? 0) ?>;

const PRECIOS = {<?php foreach ($platillos as $p): ?><?= (int)$p['id'] ?>:<?= (float)$p['precio'] ?>,<?php endforeach; ?>};

const MENU = <?= json_encode(array_combine(
  array_column($platillos, 'id'),
  array_map(function($p) use ($recetaIngredientes) {
      return [
          'nombre' => $p['nombre'],
          'precio' => (float)$p['precio'],
          'ings'   => array_values($recetaIngredientes[(int)$p['id']] ?? []),
      ];
  }, $platillos)
), JSON_UNESCAPED_UNICODE) ?>;

// ── Carrito ─────────────────────────────────────────────────────────────────
// { platilloId: { qty, excl:Set, extras:{ingId:{nombre,precio_extra,cantidad}}, nota } }
const carrito = {};

// ── Estado del modal ─────────────────────────────────────────────────────────
let mId = null, mQty = 1, mExcl = new Set(), mExtra = {};

// ── Tabs ─────────────────────────────────────────────────────────────────────
document.querySelectorAll('.mn-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.mn-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const cat = btn.dataset.cat;
    document.querySelectorAll('.mn-card').forEach(c => {
      c.style.display = (!cat || String(c.dataset.cat) === String(cat)) ? '' : 'none';
    });
    document.querySelectorAll('.mn-section').forEach(sec => {
      const vis = [...sec.querySelectorAll('.mn-card')].some(c => c.style.display !== 'none');
      sec.style.display = (cat && !vis) ? 'none' : '';
    });
  });
});

// ── Abrir modal ───────────────────────────────────────────────────────────────
function abrirModal(id) {
  const d = MENU[id]; if (!d) return;
  mId = id; mQty = 1; mExcl = new Set(); mExtra = {};
  document.getElementById('sheetTitle').textContent = d.nombre;
  document.getElementById('sheetSub').textContent   = `$${d.precio % 1 === 0 ? d.precio.toFixed(0) : d.precio.toFixed(2)} por orden`;
  document.getElementById('sheetQty').textContent   = 1;
  document.getElementById('sheetNota').value        = '';
  const gSec = document.getElementById('guarSec');
  const gList= document.getElementById('guarList');
  if (d.ings && d.ings.length) {
    gSec.style.display = '';
    gList.innerHTML = d.ings.map(ing => guarRow(ing)).join('');
  } else { gSec.style.display = 'none'; gList.innerHTML = ''; }
  actualizarAddBtn();
  document.getElementById('mnBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function guarRow(ing) {
  const pid = ing.ingrediente_id;
  const px  = parseFloat(ing.precio_extra ?? 0);
  const extraHtml = px > 0 ? `
    <button class="mn-extra-btn" type="button"
            onclick="toggleExtra(event,${pid},'${esc(ing.ingrediente_nombre)}',${px})">
      +Extra $${px % 1===0 ? px.toFixed(0) : px.toFixed(2)}
    </button>
    <div class="mn-xcnt" id="xc_${pid}">
      <button type="button" onclick="cambiarExtra(event,${pid},-1)">−</button>
      <span id="xv_${pid}">1</span>
      <button type="button" onclick="cambiarExtra(event,${pid},1)">+</button>
    </div>` : '';
  return `<div class="mn-guar-row" id="gr_${pid}">
    <div class="mn-guar-tog" onclick="toggleExcl(${pid},'${esc(ing.ingrediente_nombre)}')">
      <div class="mn-tog-icon incl" id="gi_${pid}">✓</div>
      <span>${esc(ing.ingrediente_nombre)}</span>
      <span style="font-size:.7rem;color:var(--text-muted);margin-left:auto;padding-left:6px">${ing.cantidad} ${ing.unidad}</span>
    </div>${extraHtml}</div>`;
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function toggleExcl(ingId, nombre) {
  const row = document.getElementById(`gr_${ingId}`);
  const ico = document.getElementById(`gi_${ingId}`);
  if (mExcl.has(nombre)) {
    mExcl.delete(nombre); row.classList.remove('excl');
    ico.classList.replace('excl','incl'); ico.textContent = '✓';
  } else {
    mExcl.add(nombre); row.classList.add('excl');
    ico.classList.replace('incl','excl'); ico.textContent = '✗';
    if (mExtra[ingId]) { delete mExtra[ingId]; const xc=document.getElementById(`xc_${ingId}`); if(xc)xc.classList.remove('show'); }
  }
  actualizarAddBtn();
}

function toggleExtra(ev, ingId, nombre, px) {
  ev.stopPropagation();
  const xc = document.getElementById(`xc_${ingId}`);
  if (mExtra[ingId]) { delete mExtra[ingId]; if(xc)xc.classList.remove('show'); }
  else {
    mExtra[ingId] = { ingrediente_id:ingId, nombre, precio_extra:px, cantidad:1 };
    if(xc){ xc.classList.add('show'); const xv=document.getElementById(`xv_${ingId}`); if(xv)xv.textContent=1; }
  }
  actualizarAddBtn();
}

function cambiarExtra(ev, ingId, delta) {
  ev.stopPropagation();
  if (!mExtra[ingId]) return;
  const n = Math.max(1, mExtra[ingId].cantidad + delta);
  mExtra[ingId].cantidad = n;
  const xv = document.getElementById(`xv_${ingId}`); if(xv)xv.textContent=n;
  actualizarAddBtn();
}

function cambiarQty(d) {
  mQty = Math.max(1, mQty + d);
  document.getElementById('sheetQty').textContent = mQty;
  actualizarAddBtn();
}

function actualizarAddBtn() {
  if (!mId) return;
  const base  = PRECIOS[mId] ?? 0;
  const xtra  = Object.values(mExtra).reduce((s,e)=>s+e.precio_extra*e.cantidad,0);
  const total = (base + xtra) * mQty;
  document.getElementById('addBtn').textContent =
    `Agregar · $${total % 1===0 ? total.toFixed(0) : total.toFixed(2)}`;
}

function confirmarModal() {
  if (!mId) return;
  const nota = document.getElementById('sheetNota').value.trim();
  carrito[mId] = { qty: mQty, excl: new Set(mExcl),
    extras: Object.fromEntries(Object.entries(mExtra).map(([k,v])=>[k,{...v}])), nota };
  cerrarModal(); actualizarCarrito(); toast('✓ Agregado al pedido');
}

function cerrarModal() {
  document.getElementById('mnBackdrop').classList.remove('open');
  document.body.style.overflow = '';
  mId = null;
}
function handleBackdrop(e) { if (e.target===e.currentTarget) cerrarModal(); }

// ── Carrito ───────────────────────────────────────────────────────────────────
function actualizarCarrito() {
  let total=0, items=0;
  for (const [id,s] of Object.entries(carrito)) {
    if (s.qty < 1) continue;
    const base = PRECIOS[id] ?? 0;
    const xtra = Object.values(s.extras).reduce((a,e)=>a+e.precio_extra*e.cantidad,0);
    total += (base+xtra)*s.qty; items += s.qty;
  }
  document.getElementById('carritoTotal').textContent = '$'+(total%1===0?total.toFixed(0):total.toFixed(2));
  document.getElementById('carritoItems').textContent = items+' item'+(items!==1?'s':'');
  document.getElementById('carritoBar').classList.toggle('visible', items>0);
}

function submitPedido() {
  const hc = document.getElementById('hiddenContainer'); hc.innerHTML='';
  let n=0;
  for (const [id,s] of Object.entries(carrito)) {
    if (s.qty<1) continue;
    addH(hc,`platillo_id[]`,id); addH(hc,`cantidad[]`,s.qty);
    for (const nom of s.excl) addH(hc,`exclusiones[${id}][]`,nom);
    if (Object.keys(s.extras).length) addH(hc,`extras[${id}]`,JSON.stringify(Object.values(s.extras)));
    if (s.nota) addH(hc,`notas_item[${id}]`,s.nota);
    n++;
  }
  if (n===0) return;
  document.getElementById('formPedido').submit();
}
function addH(c,name,val) { const i=document.createElement('input'); i.type='hidden'; i.name=name; i.value=val; c.appendChild(i); }

// ── Toast ─────────────────────────────────────────────────────────────────────
let _tt;
function toast(msg) {
  let t=document.getElementById('_toast');
  if (!t) {
    t=document.createElement('div'); t.id='_toast';
    Object.assign(t.style,{position:'fixed',bottom:'88px',left:'50%',transform:'translateX(-50%) translateY(10px)',
      background:'rgba(201,164,48,.96)',color:'#0d0d18',padding:'9px 20px',borderRadius:'99px',
      fontWeight:'700',fontSize:'.85rem',zIndex:'300',opacity:'0',transition:'all .25s',whiteSpace:'nowrap'});
    document.body.appendChild(t);
  }
  t.textContent=msg; t.style.opacity='1'; t.style.transform='translateX(-50%) translateY(0)';
  clearTimeout(_tt); _tt=setTimeout(()=>{ t.style.opacity='0'; t.style.transform='translateX(-50%) translateY(10px)'; },2000);
}

// ── Cookie de visita ──────────────────────────────────────────────────────────
(function(){
  const ck = document.cookie.split('; ').find(r=>r.startsWith('visita_<?= (int)$restaurante['id'] ?>='));
  if (ck) { const v=ck.split('=')[1]; const i=document.getElementById('inpVisitaId'); if(i&&(!i.value||i.value==='0'))i.value=v; }
})();

// ── Llamar mesero ─────────────────────────────────────────────────────────────
<?php if ($mesa): ?>
function llamarMesero() {
  const btn=document.getElementById('btnLlamarMesero'); btn.disabled=true; btn.textContent='🔔 Avisando…';
  fetch(`${BASE_URL}menu/${REST_SLUG}/llamarMesero`,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`mesa_qr=${encodeURIComponent(MESA_QR)}&visita_id=${VID}`})
    .then(r=>r.json())
    .then(d=>{btn.textContent=d.ok?'✅ Mesero avisado':'❌ Error';setTimeout(()=>{btn.textContent='🔔 Llamar mesero';btn.disabled=false;},4000);})
    .catch(()=>{btn.textContent='🔔 Llamar mesero';btn.disabled=false;});
}
<?php endif; ?>

// ── Polling estado del pedido ─────────────────────────────────────────────────
<?php if ($visitaId): ?>
const LABELS={pendiente:'⏳ Esperando que la cocina tome tu pedido',en_preparacion:'👨‍🍳 Tu pedido está en preparación',listo:'✅ ¡Listo! El mesero lo llevará pronto',entregado:'🍽️ Pedido entregado. ¡Buen provecho!'};
const COLORS={pendiente:'#F59E0B',en_preparacion:'#3B82F6',listo:'#10B981',entregado:'#6B7280'};
let prevState={};
function pollEstado(){
  fetch(`${BASE_URL}menu/${REST_SLUG}/estadoPedido/${VID}`)
    .then(r=>r.json()).then(d=>{
      if(!d.ok||!d.pedidos.length)return;
      const pri=['pendiente','en_preparacion','listo','entregado']; let eg='entregado';
      d.pedidos.forEach(p=>{ if(p.estado!=='cancelado'){const pi=pri.indexOf(p.estado),gi=pri.indexOf(eg);if(pi<gi)eg=p.estado;} });
      const tr=document.getElementById('statusTracker'),ct=document.getElementById('statusContent');
      let html=`<span style="color:${COLORS[eg]??'#c9a430'};font-weight:600">${LABELS[eg]??eg}</span>`;
      if(d.tiempo_min>0&&eg==='en_preparacion')html+=` <span style="color:rgba(255,255,255,.55)">⏱️ ~${d.tiempo_min} min</span>`;
      ct.innerHTML=html; tr.style.display='block';
      const bm=document.getElementById('btnPedirMismo');
      if(bm&&d.pedidos.some(p=>p.estado==='entregado')){bm._pedidos=d.pedidos;bm.style.display='block';}
    }).catch(()=>{});
}
pollEstado(); setInterval(pollEstado,5000);

function pedirLoMismo(){
  const bm=document.getElementById('btnPedirMismo'); const peds=bm?._pedidos??[];
  const ul=peds.filter(p=>p.estado==='entregado').pop(); if(!ul||!ul.items)return;
  ul.items.forEach(it=>{ if(!carrito[it.platillo_id])carrito[it.platillo_id]={qty:it.cantidad,excl:new Set(),extras:{},nota:''}; });
  actualizarCarrito(); window.scrollTo({top:0,behavior:'smooth'}); toast('🔁 Items añadidos al carrito');
}
<?php endif; ?>
</script>
</body>
</html>
