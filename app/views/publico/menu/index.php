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
      --cp:       <?= htmlspecialchars($restaurante['color_primario']  ?? '#C8102E') ?>;
      --cs:       <?= htmlspecialchars($restaurante['color_secundario'] ?? '#1f2937') ?>;
      --gold:     var(--cp);
      --gold-dim: color-mix(in srgb, var(--cp) 12%, white);
      --gold-hi:  color-mix(in srgb, var(--cp) 24%, white);
      --bg:       #F7F6F2;
      --card:     #FFFFFF;
      --line:     rgba(0,0,0,.07);
      --text-main:#1C1C2E;
      --text-muted:#6B7280;
      --radius-card: 16px;
    }

    /* Evita recorte circular del logo en encabezado con banner */
    .pub-hero .pub-hero-logo {
      border-radius: 14px;
      object-fit: contain;
      background: rgba(255,255,255,.16);
      padding: 6px;
    }

    /* ── Reset ──────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; }
    body { margin:0; font-family:'Inter',system-ui,sans-serif; background:var(--bg); color:var(--text-main); -webkit-font-smoothing:antialiased; }
    a { color:inherit; text-decoration:none; }

    /* ── Barra de tabs ──────────────────────────── */
    .mn-tab-bar { display:flex; gap:8px; padding:10px 16px 10px; overflow-x:auto; scrollbar-width:none; position:sticky; top:0; background:rgba(247,246,242,.97); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); z-index:20; border-bottom:1px solid rgba(0,0,0,.07); box-shadow:0 2px 12px rgba(0,0,0,.05); }
    .mn-tab-bar::-webkit-scrollbar { display:none; }
    .mn-tab { padding:7px 16px; border-radius:99px; font-size:.78rem; font-weight:600; border:1.5px solid #E2E3E6; background:#fff; color:var(--text-muted); cursor:pointer; white-space:nowrap; transition:all .18s; flex-shrink:0; display:flex; align-items:center; gap:5px; }
    .mn-tab .mn-tab-count { font-size:.68rem; font-weight:700; background:#E5E7EB; color:var(--text-muted); border-radius:99px; padding:1px 6px; transition:all .18s; }
    .mn-tab:hover { border-color:var(--gold); color:#7a5a00; background:var(--gold-dim); }
    .mn-tab:hover .mn-tab-count { background:var(--gold-hi); color:#7a5a00; }
    .mn-tab.active { background:var(--gold); border-color:var(--gold); color:#fff; box-shadow:0 3px 12px rgba(201,164,48,.4); }
    .mn-tab.active .mn-tab-count { background:rgba(255,255,255,.25); color:#fff; }

    /* ── Secciones ──────────────────────────────── */
    .mn-section { margin-bottom:8px; }
    .mn-section-title {
      display:flex; align-items:center; gap:10px;
      margin:20px 16px 0;
      padding:14px 16px;
      background:var(--card);
      border-radius:var(--radius-card) var(--radius-card) 0 0;
      border-bottom:1px solid rgba(0,0,0,.06);
      position:sticky; top:50px; z-index:10;
    }
    .mn-section-icon { font-size:1.5rem; line-height:1; }
    .mn-section-text { flex:1; }
    .mn-section-text h2 { margin:0; font-family:'Inter',system-ui,sans-serif; font-size:1.1rem; font-weight:700; color:var(--text-main); line-height:1.2; }
    .mn-section-text span { font-size:.72rem; color:var(--text-muted); font-weight:500; }
    .mn-section-divider { height:1px; background:linear-gradient(90deg,var(--gold) 0%,transparent 70%); margin:0 16px 0; opacity:.35; }

    /* ── Lista de platillos ─────────────────────── */
    .mn-list { background:var(--card); margin:0 16px; border-radius:0 0 var(--radius-card) var(--radius-card); overflow:hidden; }

    /* ── Tarjeta horizontal ─────────────────────── */
    .mn-card {
      display:flex; align-items:stretch; gap:0;
      padding:14px 16px;
      border-bottom:1px solid rgba(0,0,0,.055);
      cursor:pointer;
      transition:background .15s;
      animation:fadeIn .22s ease both;
      position:relative;
    }
    .mn-card:last-child { border-bottom:none; }
    .mn-card:hover { background:color-mix(in srgb, var(--cp) 8%, white); }
    .mn-card:active { background:color-mix(in srgb, var(--cp) 14%, white); }

    /* Lado izquierdo: texto */
    .mn-card-body { flex:1; display:flex; flex-direction:column; justify-content:center; gap:4px; padding-right:14px; min-width:0; }
    .mn-card-name { font-size:.97rem; font-weight:700; color:var(--text-main); line-height:1.3; }
    .mn-card-desc { font-size:.78rem; color:var(--text-muted); line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; margin-top:2px; }
    .mn-card-chips { display:flex; flex-wrap:wrap; gap:4px; margin-top:5px; }
    .mn-chip { font-size:.62rem; font-weight:600; padding:2px 8px; border-radius:99px; border:1px solid color-mix(in srgb, var(--cp) 35%, white); color:color-mix(in srgb, var(--cp) 78%, #111827); background:color-mix(in srgb, var(--cp) 12%, white); white-space:nowrap; }
    .mn-chip-more { font-size:.62rem; color:color-mix(in srgb, var(--cp) 60%, #6B7280); align-self:center; }
    .mn-card-price { font-size:.9rem; font-weight:800; color:var(--text-main); margin-top:6px; }

    /* Lado derecho: imagen */
    .mn-card-thumb { position:relative; flex-shrink:0; width:140px; height:140px; border-radius:12px; overflow:hidden; background:linear-gradient(135deg,color-mix(in srgb, var(--cp) 8%, white),color-mix(in srgb, var(--cp) 18%, white)); align-self:center; }
    .mn-card-thumb img { width:100%; height:100%; object-fit:cover; }
    .mn-card-emoji { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:3rem; }

    /* ── Modal / bottom-sheet ───────────────────── */
    .mn-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); z-index:200; align-items:flex-end; justify-content:center; }
    .mn-backdrop.open { display:flex; }
    .mn-sheet { background:#fff; border-radius:24px 24px 0 0; width:100%; max-width:520px; max-height:90vh; overflow-y:auto; animation:slideUp .28s cubic-bezier(.34,1.12,.64,1) both; scrollbar-width:thin; scrollbar-color:#D1D5DB transparent; box-shadow:0 -8px 40px rgba(0,0,0,.18); }
    .mn-sheet::-webkit-scrollbar { width:4px; }
    .mn-sheet::-webkit-scrollbar-thumb { background:#D1D5DB; border-radius:4px; }
    @keyframes slideUp { from { transform:translateY(100%); opacity:0; } to { transform:translateY(0); opacity:1; } }
    .mn-drag { width:40px; height:4px; background:rgba(0,0,0,.12); border-radius:99px; margin:10px auto 0; }

    /* Imagen del sheet */
    .mn-sheet-img { width:100%; height:300px; object-fit:cover; display:block; }
    .mn-sheet-img-placeholder { width:100%; height:240px; display:flex; align-items:center; justify-content:center; font-size:4.5rem; background:linear-gradient(135deg,#F5F3EE 0%,#EDE8DC 100%); }

    .mn-sheet-hdr { padding:16px 20px 4px; position:relative; }
    .mn-sheet-close { position:absolute; top:12px; right:16px; background:rgba(0,0,0,.07); border:none; width:30px; height:30px; border-radius:50%; color:var(--text-main); font-size:1rem; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .15s; }
    .mn-sheet-close:hover { background:rgba(0,0,0,.12); }
    .mn-sheet-title { font-family:'Inter',system-ui,sans-serif; font-size:1.25rem; font-weight:700; color:var(--text-main); margin:0 0 4px; padding-right:38px; }
    .mn-sheet-sub { font-size:.83rem; color:var(--text-muted); margin:0 0 0; }
    .mn-sec { padding:0 20px 16px; }
    .mn-sec-lbl { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--gold); margin-bottom:10px; margin-top:16px; }
    .mn-guar-row { display:flex; align-items:center; gap:10px; padding:9px 13px; border-radius:12px; border:1px solid #E5E7EB; margin-bottom:6px; background:#F9FAFB; transition:all .15s; }
    .mn-guar-tog { flex:1; display:flex; align-items:center; gap:8px; font-size:.85rem; color:var(--text-main); }
    .mn-tog-icon { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:800; flex-shrink:0; transition:all .15s; }
    .mn-tog-icon.incl { background:rgba(34,197,94,.15); color:#16a34a; }
    .mn-footer { padding:28px 20px 40px; text-align:center; font-size:.75rem; color:rgba(0,0,0,.22); }

    @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

    /* ── Vacío / sin platillos ──────────────────── */
    .mn-empty { padding:72px 24px; text-align:center; }
    .mn-empty-icon { font-size:3.5rem; margin-bottom:12px; }
    .mn-empty-title { font-family:'Playfair Display',Georgia,serif; font-size:1.1rem; color:var(--text-muted); margin-bottom:6px; }
    .mn-empty-sub { font-size:.87rem; color:var(--text-muted); line-height:1.6; opacity:.7; }

    /* ── Responsive: pantallas grandes ─────────── */
    @media (min-width:640px) {
      .mn-list { display:grid; grid-template-columns:1fr 1fr; }
      .mn-card { border-bottom:1px solid rgba(0,0,0,.055); border-right:1px solid rgba(0,0,0,.055); }
      .mn-card:nth-child(2n) { border-right:none; }
      .mn-card:nth-last-child(-n+2) { border-bottom:none; }
    }
  </style>
</head>
<body>

<!-- Hero -->
<?php
  $hasBanner = !empty($restaurante['imagen_banner']);
  $heroClass  = $hasBanner ? 'pub-hero pub-hero--banner' : 'pub-hero';
  $heroStyle  = $hasBanner
    ? 'style="background-image:url(\'' . BASE_URL . htmlspecialchars($restaurante['imagen_banner']) . '\')"'
    : '';
?>
<div class="<?= $heroClass ?>" <?= $heroStyle ?>>
  <div class="pub-hero-content">
    <?php if ($restaurante['logo']): ?>
        <img src="<?= BASE_URL . htmlspecialchars($restaurante['logo']) ?>" alt=""
          class="pub-hero-logo pub-hero-logo--contain">
    <?php endif; ?>
    <h1 style="font-family:'Playfair Display',Georgia,serif;font-size:1.85rem;font-weight:800;margin:0 0 6px;color:#fff;text-shadow:0 2px 16px rgba(0,0,0,.4);letter-spacing:-.01em">
      <?= htmlspecialchars($restaurante['nombre']) ?>
    </h1>
    <?php if ($restaurante['descripcion']): ?>
    <p style="font-size:.875rem;color:rgba(255,255,255,.8);margin:0;line-height:1.6;max-width:340px;text-shadow:0 1px 6px rgba(0,0,0,.3)">
      <?= htmlspecialchars($restaurante['descripcion']) ?>
    </p>
    <?php endif; ?>

  <div style="margin-top:12px;display:inline-flex;align-items:center;gap:8px;
              background:rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.25);
              backdrop-filter:blur(8px);border-radius:999px;padding:7px 16px;
              font-size:.82rem;color:#fff;font-weight:600">
    👁 Menú informativo
  </div>

  <?php if ($mesa): ?>
  <div style="display:flex;flex-direction:column;align-items:center;gap:8px;width:100%;margin-top:10px">
    <div style="display:inline-flex;align-items:center;gap:6px;
                background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.25);border-radius:10px;padding:7px 14px;font-size:.85rem;color:#fff">
      <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="opacity:.6">
        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
      </svg>
      Mesa: <strong><?= htmlspecialchars($mesa['nombre']) ?></strong>
    </div>
  </div>
  <?php endif; ?>
  </div><!-- /pub-hero-content -->
</div><!-- /pub-hero -->

<!-- Tabs de categoría -->
<?php
$catIconos  = [];
$catSinIng  = []; // IDs de categorías que NO muestran ingredientes (bebidas, dulces, postres)
$catConteos = []; // cantidad de platillos por categoría

foreach ($categorias as $cat) {
    $n = mb_strtolower($cat['nombre']);
    if (str_contains($n,'bebida'))                               $catIconos[$cat['id']] = '🥂';
    elseif (str_contains($n,'postre')||str_contains($n,'dulce')) $catIconos[$cat['id']] = '🍮';
    else                                                         $catIconos[$cat['id']] = '🫔';

    if (str_contains($n,'bebida') || str_contains($n,'postre') || str_contains($n,'dulce')) {
        $catSinIng[] = (int)$cat['id'];
    }
}
// Contar platillos por categoría (se llenará después de armar $porCategoria)
?>
<div class="mn-tab-bar">
  <button class="mn-tab active" data-cat="">✨ Todos <span class="mn-tab-count"><?= count($platillos) ?></span></button>
  <?php foreach ($categorias as $cat):
    $cnt = count(array_filter($platillos, fn($p) => (int)($p['categoria_id'] ?? 0) === (int)$cat['id']));
    if ($cnt === 0) continue;
  ?>
  <button class="mn-tab" data-cat="<?= (int)$cat['id'] ?>">
    <?= $catIconos[$cat['id']] ?> <?= htmlspecialchars($cat['nombre']) ?>
    <span class="mn-tab-count"><?= $cnt ?></span>
  </button>
  <?php endforeach; ?>
</div>

<!-- Platillos por sección -->
<?php if (empty($platillos)): ?>
<div class="mn-empty">
  <div class="mn-empty-icon">🍽️</div>
  <div class="mn-empty-title">Menú en preparación</div>
  <div class="mn-empty-sub">Aún no hay platillos disponibles.<br>Vuelve pronto o pide ayuda al personal.</div>
</div>
<?php else: ?>
<?php
$porCategoria = [];
foreach ($platillos as $p) { $porCategoria[(int)($p['categoria_id'] ?? 0)][] = $p; }
$catNombres = array_column($categorias, 'nombre', 'id');
?>
<?php foreach ($porCategoria as $cid => $platos):
  $esSinIng = in_array($cid, $catSinIng, true);
?>
<div class="mn-section" data-section-cat="<?= $cid ?>">
  <?php if (isset($catNombres[$cid])): ?>
  <div class="mn-section-title">
    <div class="mn-section-icon"><?= $catIconos[$cid] ?? '🍽' ?></div>
    <div class="mn-section-text">
      <h2><?= htmlspecialchars($catNombres[$cid]) ?></h2>
      <span><?= count($platos) ?> <?= count($platos) === 1 ? 'platillo' : 'platillos' ?></span>
    </div>
  </div>
  <?php endif; ?>
  <div class="mn-list">
  <?php foreach ($platos as $p):
    $pId   = (int)$p['id'];
    $ings  = array_values(array_filter(
        $recetaIngredientes[$pId] ?? [],
        fn($r) => ($r['tipo_componente'] ?? null) !== 'materia_prima'
                  || (float)($r['precio_extra'] ?? 0) > 0
    ));
    $chips = (!$esSinIng && !empty($ings)) ? array_slice($ings, 0, 4) : [];
    $more  = (!$esSinIng && !empty($ings)) ? max(0, count($ings) - count($chips)) : 0;
    $icon  = $catIconos[$cid] ?? '🍽';
    $desc  = trim($p['descripcion'] ?? '');
    $alerg = array_filter(array_map('trim', explode(',', $p['alergenos'] ?? '')));
  ?>
  <div class="mn-card" data-cat="<?= $cid ?>" onclick="abrirModal(<?= $pId ?>)">
    <div class="mn-card-body">
      <div class="mn-card-name"><?= htmlspecialchars($p['nombre']) ?></div>
      <?php if ($desc !== ''): ?>
      <div class="mn-card-desc"><?= htmlspecialchars($desc) ?></div>
      <?php endif; ?>
      <?php if (!empty($alerg)): ?>
      <div style="display:flex;flex-wrap:wrap;gap:4px;margin:4px 0 6px">
        <?php foreach ($alerg as $a): ?>
        <span title="Contiene <?= htmlspecialchars($a) ?>"
              style="font-size:.62rem;font-weight:700;background:#FEF3C7;color:#92400E;
                     border:1px solid #FDE68A;border-radius:6px;padding:1px 6px">
          ⚠ <?= htmlspecialchars($a) ?>
        </span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($chips)): ?>
      <div class="mn-card-chips">
        <?php foreach ($chips as $ch): ?>
        <span class="mn-chip"><?= htmlspecialchars($ch['ingrediente_nombre']) ?></span>
        <?php endforeach; ?>
        <?php if ($more > 0): ?><span class="mn-chip-more">+<?= $more ?> más</span><?php endif; ?>
      </div>
      <?php endif; ?>
      <div class="mn-card-price">$<?= number_format((float)$p['precio'], 0) ?></div>
    </div>
    <div class="mn-card-thumb">
      <?php if (!empty($p['imagen'])): ?>
      <img src="<?= BASE_URL . htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>">
      <?php else: ?>
      <div class="mn-card-emoji"><?= $icon ?></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="mn-footer">Potenciado por <strong>CarniHub</strong></div>

<!-- ── Modal informativo ──────────────────────────────────────────────────── -->
<div id="mnBackdrop" class="mn-backdrop" onclick="handleBackdrop(event)">
  <div class="mn-sheet">
    <div class="mn-drag"></div>
    <div id="sheetImgWrap"></div>
    <div class="mn-sheet-hdr">
      <button onclick="cerrarModal()" type="button" class="mn-sheet-close">✕</button>
      <div class="mn-sheet-title" id="sheetTitle">—</div>
      <div class="mn-sheet-sub"   id="sheetSub">—</div>
      <div id="sheetDesc" style="font-size:.9rem;color:#374151;line-height:1.55;margin-top:12px;display:none"></div>
    </div>
    <div class="mn-sec" id="infoAlergSec" style="display:none">
      <div class="mn-sec-lbl">Información del platillo</div>
      <div id="infoAlergBox" style="display:flex;flex-direction:column;gap:8px;font-size:.82rem;color:#374151"></div>
    </div>
    <div class="mn-sec" id="guarSec" style="display:none">
      <div class="mn-sec-lbl">Guarniciones incluidas</div>
      <div id="guarList"></div>
    </div>
  </div>
</div>

<!-- ── JavaScript ──────────────────────────────────────────────────────────── -->
<script>
const BASE_URL = '<?= BASE_URL ?>';
const CAT_SIN_ING = new Set(<?= json_encode($catSinIng) ?>);
const MENU = <?= json_encode(array_combine(
  array_column($platillos, 'id'),
  array_map(function($p) use ($recetaIngredientes) {
      return [
          'nombre' => $p['nombre'],
          'descripcion' => trim($p['descripcion'] ?? ''),
          'precio' => (float)$p['precio'],
          'imagen' => $p['imagen'] ?? '',
          'cat_id' => (int)($p['categoria_id'] ?? 0),
          'alergenos' => array_values(array_filter(array_map('trim', explode(',', $p['alergenos'] ?? '')))),
          'contiene'  => trim($p['contiene'] ?? ''),
          'ings'   => array_values(array_filter(
              $recetaIngredientes[(int)$p['id']] ?? [],
              fn($r) => ($r['tipo_componente'] ?? null) !== 'materia_prima'
                        || (float)($r['precio_extra'] ?? 0) > 0
          )),
      ];
  }, $platillos)
), JSON_UNESCAPED_UNICODE) ?>;

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

function abrirModal(id) {
  const d = MENU[id]; if (!d) return;
  document.getElementById('sheetTitle').textContent = d.nombre;
  document.getElementById('sheetSub').textContent   = `$${d.precio % 1 === 0 ? d.precio.toFixed(0) : d.precio.toFixed(2)} por orden`;

  const desc = document.getElementById('sheetDesc');
  if (d.descripcion) {
    desc.textContent = d.descripcion;
    desc.style.display = '';
  } else {
    desc.textContent = '';
    desc.style.display = 'none';
  }

  const imgWrap = document.getElementById('sheetImgWrap');
  if (d.imagen) {
    imgWrap.innerHTML = `<img class="mn-sheet-img" src="${BASE_URL}${d.imagen.replace(/^\//, '')}" alt="${esc(d.nombre)}">`;
  } else {
    imgWrap.innerHTML = '';
  }

  const gSec  = document.getElementById('guarSec');
  const gList = document.getElementById('guarList');
  const mostrarIng = !CAT_SIN_ING.has(d.cat_id);

  const infoSec = document.getElementById('infoAlergSec');
  const infoBox = document.getElementById('infoAlergBox');
  let infoHtml = '';
  if (d.alergenos && d.alergenos.length) {
    infoHtml += '<div><strong style="color:#92400E">⚠️ Alérgenos:</strong> '
      + d.alergenos.map(a => `<span style="display:inline-block;background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;border-radius:6px;padding:1px 7px;font-size:.74rem;font-weight:700;margin:2px 3px 0 0">${esc(a)}</span>`).join('')
      + '</div>';
  }
  if (d.contiene) {
    infoHtml += `<div><strong style="color:#374151">Contiene:</strong> <span style="color:#6B7280">${esc(d.contiene)}</span></div>`;
  }
  if (infoHtml) { infoBox.innerHTML = infoHtml; infoSec.style.display = ''; }
  else { infoSec.style.display = 'none'; }
  if (mostrarIng && d.ings && d.ings.length) {
    gSec.style.display = '';
    gList.innerHTML = d.ings.map(ing => guarRow(ing)).join('');
  } else {
    gSec.style.display = 'none';
    gList.innerHTML = '';
  }
  document.getElementById('mnBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function guarRow(ing) {
  const cantidad = [ing.cantidad, ing.unidad].filter(Boolean).join(' ');
  return `<div class="mn-guar-row">
    <div class="mn-guar-tog" style="cursor:default">
      <div class="mn-tog-icon incl">✓</div>
      <span>${esc(ing.ingrediente_nombre)}</span>
      ${cantidad ? `<span style="font-size:.7rem;color:var(--text-muted);margin-left:auto;padding-left:6px">${esc(cantidad)}</span>` : ''}
    </div>
  </div>`;
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function cerrarModal() {
  document.getElementById('mnBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}
function handleBackdrop(e) { if (e.target===e.currentTarget) cerrarModal(); }
</script>
</body>
</html>
