<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($restaurante['nombre']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/restaurant.css">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    /* ── Variables de marca ─────────────────────── */
    :root {
      --cp:       <?= htmlspecialchars($restaurante['color_primario']  ?? '#C8102E') ?>;
      --cs:       <?= htmlspecialchars($restaurante['color_secundario'] ?? '#1f2937') ?>;
      --accent:   var(--cp);
      --accent-soft: color-mix(in srgb, var(--cp) 10%, white);
      --accent-line: color-mix(in srgb, var(--cp) 28%, #D7DAE0);
      --bg:       #F7F8FA;
      --card:     #FFFFFF;
      --panel:    #F1F4F8;
      --line:     rgba(17,24,39,.10);
      --text-main:#151922;
      --text-muted:#667085;
      --radius-card: 8px;
      --shadow-sm: 0 10px 28px rgba(15, 23, 42, .08);
      --shadow-lg: 0 -18px 56px rgba(15, 23, 42, .22);
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
    .mn-icon { width:16px; height:16px; stroke-width:2; flex-shrink:0; }
    .mn-icon-lg { width:20px; height:20px; stroke-width:2; flex-shrink:0; }
    .mn-page { max-width:1120px; margin:0 auto; padding:0 14px 34px; }

    /* ── Barra de tabs ──────────────────────────── */
    .mn-tab-bar { display:flex; gap:8px; padding:12px 14px; overflow-x:auto; scrollbar-width:none; position:sticky; top:0; background:rgba(247,248,250,.92); backdrop-filter:blur(18px); -webkit-backdrop-filter:blur(18px); z-index:20; border-bottom:1px solid var(--line); }
    .mn-tab-bar::-webkit-scrollbar { display:none; }
    .mn-tab { min-height:38px; padding:8px 14px; border-radius:999px; font-size:.78rem; font-weight:700; border:1px solid #DEE3EA; background:#fff; color:#4B5563; cursor:pointer; white-space:nowrap; transition:all .18s; flex-shrink:0; display:flex; align-items:center; gap:7px; box-shadow:0 1px 0 rgba(15,23,42,.02); }
    .mn-tab .mn-tab-count { font-size:.67rem; font-weight:800; background:#EEF1F5; color:#6B7280; border-radius:99px; padding:2px 7px; transition:all .18s; }
    .mn-tab:hover { border-color:var(--accent-line); color:var(--text-main); background:var(--accent-soft); }
    .mn-tab.active { background:var(--accent); border-color:var(--accent); color:#fff; box-shadow:0 8px 20px color-mix(in srgb, var(--accent) 28%, transparent); }
    .mn-tab.active .mn-tab-count { background:rgba(255,255,255,.25); color:#fff; }

    /* ── Buscador ───────────────────────────────── */
    .mn-search-wrap { position:sticky; top:63px; z-index:18; padding:10px 14px 8px; background:rgba(247,248,250,.92); backdrop-filter:blur(18px); -webkit-backdrop-filter:blur(18px); border-bottom:1px solid var(--line); }
    .mn-search { max-width:1120px; margin:0 auto; position:relative; }
    .mn-search .mn-icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:#667085; pointer-events:none; }
    .mn-search-input { width:100%; height:42px; border:1px solid #DEE3EA; border-radius:999px; background:#fff; color:var(--text-main); font:600 .88rem 'Inter',system-ui,sans-serif; outline:none; padding:0 46px 0 40px; transition:border-color .18s, box-shadow .18s, background .18s; box-shadow:0 1px 0 rgba(15,23,42,.02); }
    .mn-search-input::placeholder { color:#98A2B3; font-weight:600; }
    .mn-search-input:focus { border-color:var(--accent-line); box-shadow:0 0 0 4px color-mix(in srgb, var(--accent) 12%, transparent); }
    .mn-search-clear { position:absolute; right:8px; top:50%; transform:translateY(-50%); width:30px; height:30px; border:0; border-radius:999px; background:#F2F4F7; color:#667085; cursor:pointer; display:none; align-items:center; justify-content:center; transition:background .15s, color .15s; }
    .mn-search-clear:hover { background:#E8EDF4; color:#111827; }
    .mn-search-clear.visible { display:flex; }
    .mn-search-empty { display:none; margin:18px 14px 0; padding:28px 18px; text-align:center; background:#fff; border:1px dashed #CBD5E1; border-radius:8px; color:var(--text-muted); }
    .mn-search-empty.visible { display:block; }
    .mn-search-empty strong { display:block; color:var(--text-main); font-size:.95rem; margin-bottom:5px; }

    /* ── Secciones ──────────────────────────────── */
    .mn-section { margin:18px 0 4px; }
    .mn-section-title {
      display:flex; align-items:center; gap:10px;
      margin:0 0 10px;
      padding:8px 2px;
      background:transparent;
      position:sticky; top:116px; z-index:10;
      backdrop-filter:blur(10px);
    }
    .mn-section-icon { width:34px; height:34px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; color:var(--accent); background:var(--accent-soft); border:1px solid var(--accent-line); }
    .mn-section-text { flex:1; }
    .mn-section-text h2 { margin:0; font-family:'Inter',system-ui,sans-serif; font-size:1.04rem; font-weight:800; color:var(--text-main); line-height:1.2; }
    .mn-section-text span { font-size:.72rem; color:var(--text-muted); font-weight:600; }

    /* ── Lista de platillos ─────────────────────── */
    .mn-list { display:grid; gap:10px; }

    /* ── Tarjeta horizontal ─────────────────────── */
    .mn-card {
      display:flex; align-items:stretch; gap:0;
      padding:12px;
      border:1px solid var(--line);
      border-radius:var(--radius-card);
      background:var(--card);
      cursor:pointer;
      transition:transform .16s, box-shadow .16s, border-color .16s;
      animation:fadeIn .22s ease both;
      position:relative;
      box-shadow:0 1px 0 rgba(15,23,42,.02);
    }
    .mn-card:hover { transform:translateY(-1px); border-color:var(--accent-line); box-shadow:var(--shadow-sm); }
    .mn-card:active { transform:translateY(0); box-shadow:none; }

    /* Lado izquierdo: texto */
    .mn-card-body { flex:1; display:flex; flex-direction:column; justify-content:center; gap:6px; padding-right:14px; min-width:0; }
    .mn-card-name { font-size:1rem; font-weight:800; color:var(--text-main); line-height:1.28; letter-spacing:0; }
    .mn-card-desc { font-size:.8rem; color:var(--text-muted); line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; margin-top:1px; }
    .mn-card-chips { display:flex; flex-wrap:wrap; gap:5px; margin-top:3px; }
    .mn-chip { display:inline-flex; align-items:center; gap:4px; font-size:.64rem; font-weight:700; padding:3px 8px; border-radius:999px; border:1px solid var(--accent-line); color:color-mix(in srgb, var(--accent) 72%, #111827); background:var(--accent-soft); white-space:nowrap; }
    .mn-chip-more { font-size:.65rem; color:color-mix(in srgb, var(--accent) 55%, #667085); align-self:center; font-weight:700; }
    .mn-card-price { width:max-content; font-size:.86rem; font-weight:800; color:var(--text-main); margin-top:4px; padding:5px 10px; border-radius:999px; background:#F3F5F8; border:1px solid #E5EAF0; }
    .mn-allergen { display:inline-flex; align-items:center; gap:4px; font-size:.62rem; font-weight:800; background:#FFF7ED; color:#9A3412; border:1px solid #FED7AA; border-radius:999px; padding:2px 7px; }

    /* Lado derecho: imagen */
    .mn-card-thumb { position:relative; flex-shrink:0; width:118px; height:118px; border-radius:8px; overflow:hidden; background:linear-gradient(135deg,var(--panel),color-mix(in srgb, var(--accent) 12%, white)); align-self:center; border:1px solid #E8ECF2; }
    .mn-card-thumb img { width:100%; height:100%; object-fit:cover; }
    .mn-card-icon { width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:color-mix(in srgb, var(--accent) 70%, #475467); }
    .mn-card-icon .mn-icon-lg { width:34px; height:34px; }
    .mn-card--visual { min-height:152px; }
    .mn-card--visual .mn-card-body { justify-content:flex-start; padding-top:4px; padding-bottom:4px; }
    .mn-card--visual .mn-card-desc { -webkit-line-clamp:3; }
    .mn-card--visual .mn-card-thumb { width:148px; height:148px; }

    /* ── Modal / bottom-sheet ───────────────────── */
    .mn-backdrop { display:none; position:fixed; inset:0; background:rgba(15,23,42,.48); backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px); z-index:200; align-items:flex-end; justify-content:center; }
    .mn-backdrop.open { display:flex; }
    .mn-sheet { background:#fff; border-radius:18px 18px 0 0; width:100%; max-width:560px; height:min(88dvh, 720px); max-height:90vh; overflow-y:auto; animation:slideUp .28s cubic-bezier(.34,1.12,.64,1) both; scrollbar-width:thin; scrollbar-color:#D1D5DB transparent; box-shadow:var(--shadow-lg); padding-bottom:max(18px, env(safe-area-inset-bottom)); }
    .mn-sheet::-webkit-scrollbar { width:4px; }
    .mn-sheet::-webkit-scrollbar-thumb { background:#D1D5DB; border-radius:4px; }
    @keyframes slideUp { from { transform:translateY(100%); opacity:0; } to { transform:translateY(0); opacity:1; } }
    .mn-drag { width:40px; height:4px; background:rgba(0,0,0,.12); border-radius:99px; margin:10px auto 0; }

    /* Imagen del sheet */
    .mn-sheet-img { width:100%; height:clamp(240px, 42dvh, 340px); object-fit:cover; display:block; }
    .mn-sheet-img-placeholder { width:100%; height:220px; display:flex; align-items:center; justify-content:center; color:var(--accent); background:linear-gradient(135deg,#F1F4F8 0%,var(--accent-soft) 100%); }

    .mn-sheet-hdr { padding:18px 22px 4px; position:relative; }
    .mn-sheet-close { position:absolute; top:14px; right:16px; background:#F2F4F7; border:1px solid #E5EAF0; width:34px; height:34px; border-radius:999px; color:var(--text-main); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background .15s, transform .15s; }
    .mn-sheet-close:hover { background:#E8EDF4; transform:scale(1.03); }
    .mn-sheet-title { font-family:'Inter',system-ui,sans-serif; font-size:1.28rem; font-weight:800; color:var(--text-main); margin:0 0 5px; padding-right:42px; line-height:1.2; }
    .mn-sheet-sub { display:inline-flex; align-items:center; gap:6px; font-size:.84rem; font-weight:800; color:var(--text-main); margin:0; padding:5px 10px; border-radius:999px; background:#F3F5F8; border:1px solid #E5EAF0; }
    .mn-sec { padding:0 20px 16px; }
    .mn-sec-lbl { display:flex; align-items:center; gap:7px; font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:var(--accent); margin-bottom:10px; margin-top:16px; }
    .mn-guar-row { display:flex; align-items:center; gap:10px; padding:9px 13px; border-radius:12px; border:1px solid #E5E7EB; margin-bottom:6px; background:#F9FAFB; transition:all .15s; }
    .mn-guar-tog { flex:1; display:flex; align-items:center; gap:8px; font-size:.85rem; color:var(--text-main); }
    .mn-tog-icon { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:800; flex-shrink:0; transition:all .15s; }
    .mn-tog-icon.incl { background:rgba(34,197,94,.15); color:#16a34a; }
    .mn-footer { padding:28px 20px 40px; text-align:center; font-size:.75rem; color:rgba(0,0,0,.22); }

    @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

    /* ── Vacío / sin platillos ──────────────────── */
    .mn-empty { padding:72px 24px; text-align:center; }
    .mn-empty-icon { width:54px; height:54px; border-radius:16px; margin:0 auto 12px; display:flex; align-items:center; justify-content:center; color:var(--accent); background:var(--accent-soft); border:1px solid var(--accent-line); }
    .mn-empty-title { font-family:'Playfair Display',Georgia,serif; font-size:1.1rem; color:var(--text-muted); margin-bottom:6px; }
    .mn-empty-sub { font-size:.87rem; color:var(--text-muted); line-height:1.6; opacity:.7; }

    /* ── Responsive: pantallas grandes ─────────── */
    @media (min-width:640px) {
      .mn-page { padding-left:20px; padding-right:20px; }
      .mn-search-wrap { padding-left:20px; padding-right:20px; }
      .mn-list { grid-template-columns:1fr 1fr; gap:14px; }
      .mn-card { min-height:154px; }
      .mn-card-thumb { width:132px; height:132px; }
      .mn-card--visual { min-height:182px; }
      .mn-card--visual .mn-card-thumb { width:162px; height:162px; }
    }
    @media (min-width:1024px) {
      .mn-list { grid-template-columns:repeat(3, minmax(0, 1fr)); }
      .mn-card { flex-direction:column-reverse; min-height:0; padding:0; overflow:hidden; }
      .mn-card-body { padding:14px; }
      .mn-card-thumb { width:100%; height:180px; border-radius:0; border:0; }
      .mn-card-desc { -webkit-line-clamp:3; }
      .mn-card--visual .mn-card-thumb { width:100%; height:240px; }
      .mn-card--visual .mn-card-body { min-height:142px; padding:16px; }
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
  if (!function_exists('mnIcon')) {
      function mnIcon(string $name, string $class = 'mn-icon'): string
      {
          return '<i data-lucide="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"></i>';
      }
  }
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
    <?= mnIcon('book-open', 'mn-icon') ?> Menú informativo
  </div>

  <?php if ($mesa): ?>
  <div style="display:flex;flex-direction:column;align-items:center;gap:8px;width:100%;margin-top:10px">
    <div style="display:inline-flex;align-items:center;gap:6px;
                background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.25);border-radius:10px;padding:7px 14px;font-size:.85rem;color:#fff">
      <?= mnIcon('map-pin', 'mn-icon') ?>
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
    if (str_contains($n,'bebida') || str_contains($n,'bar'))      $catIconos[$cat['id']] = 'wine';
    elseif (str_contains($n,'postre')||str_contains($n,'dulce')) $catIconos[$cat['id']] = 'cake-slice';
    elseif (str_contains($n,'entrada')||str_contains($n,'botana')) $catIconos[$cat['id']] = 'salad';
    else                                                         $catIconos[$cat['id']] = 'utensils';

    if (str_contains($n,'bebida') || str_contains($n,'postre') || str_contains($n,'dulce')) {
        $catSinIng[] = (int)$cat['id'];
    }
}
// Contar platillos por categoría (se llenará después de armar $porCategoria)
?>
<div class="mn-tab-bar">
  <button class="mn-tab active" data-cat=""><?= mnIcon('layout-grid', 'mn-icon') ?> Todos <span class="mn-tab-count"><?= count($platillos) ?></span></button>
  <?php foreach ($categorias as $cat):
    $cnt = count(array_filter($platillos, fn($p) => (int)($p['categoria_id'] ?? 0) === (int)$cat['id']));
    if ($cnt === 0) continue;
  ?>
  <button class="mn-tab" data-cat="<?= (int)$cat['id'] ?>">
    <?= mnIcon($catIconos[$cat['id']] ?? 'utensils', 'mn-icon') ?> <?= htmlspecialchars($cat['nombre']) ?>
    <span class="mn-tab-count"><?= $cnt ?></span>
  </button>
  <?php endforeach; ?>
</div>

<?php if (!empty($platillos)): ?>
<div class="mn-search-wrap">
  <div class="mn-search">
    <?= mnIcon('search', 'mn-icon') ?>
    <input id="mnSearchInput" class="mn-search-input" type="search" autocomplete="off" placeholder="Buscar platillo, bebida o ingrediente">
    <button id="mnSearchClear" class="mn-search-clear" type="button" aria-label="Limpiar busqueda"><?= mnIcon('x', 'mn-icon') ?></button>
  </div>
</div>
<div id="mnSearchEmpty" class="mn-search-empty">
  <strong>Sin resultados</strong>
  Prueba con otro nombre, ingrediente o categoria.
</div>
<?php endif; ?>

<!-- Platillos por sección -->
<?php if (empty($platillos)): ?>
<div class="mn-empty">
  <div class="mn-empty-icon"><?= mnIcon('utensils', 'mn-icon-lg') ?></div>
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
    <div class="mn-section-icon"><?= mnIcon($catIconos[$cid] ?? 'utensils', 'mn-icon-lg') ?></div>
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
    $icon  = $catIconos[$cid] ?? 'utensils';
    $desc  = trim($p['descripcion'] ?? '');
    $alerg = array_filter(array_map('trim', explode(',', $p['alergenos'] ?? '')));
    $ingredientesBusqueda = array_map(fn($r) => (string)($r['ingrediente_nombre'] ?? ''), $ings);
    $searchText = implode(' ', array_filter(array_merge([
        (string)($p['nombre'] ?? ''),
        $desc,
        (string)($catNombres[$cid] ?? ''),
        (string)($p['contiene'] ?? ''),
    ], $alerg, $ingredientesBusqueda)));
  ?>
  <div class="mn-card <?= $esSinIng ? 'mn-card--visual' : '' ?>" data-cat="<?= $cid ?>" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>" onclick="abrirModal(<?= $pId ?>)">
    <div class="mn-card-body">
      <div class="mn-card-name"><?= htmlspecialchars($p['nombre']) ?></div>
      <?php if ($desc !== ''): ?>
      <div class="mn-card-desc"><?= htmlspecialchars($desc) ?></div>
      <?php endif; ?>
      <?php if (!empty($alerg)): ?>
      <div style="display:flex;flex-wrap:wrap;gap:4px;margin:4px 0 6px">
        <?php foreach ($alerg as $a): ?>
        <span title="Contiene <?= htmlspecialchars($a) ?>"
              class="mn-allergen">
          <?= mnIcon('triangle-alert', 'mn-icon') ?> <?= htmlspecialchars($a) ?>
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
      <div class="mn-card-icon"><?= mnIcon($icon, 'mn-icon-lg') ?></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<div class="mn-footer">
  Potenciado por <strong>Jungle Pizza</strong><br>
  <a href="<?= BASE_URL ?>legal/terminos?slug=<?= urlencode($restaurante['slug'] ?? '') ?>"
     style="display:inline-flex;margin-top:8px;color:rgba(0,0,0,.42);font-weight:700;text-decoration:underline;text-underline-offset:3px">
    Terminos y condiciones
  </a>
</div>

<!-- ── Modal informativo ──────────────────────────────────────────────────── -->
<div id="mnBackdrop" class="mn-backdrop" onclick="handleBackdrop(event)">
  <div class="mn-sheet">
    <div class="mn-drag"></div>
    <div id="sheetImgWrap"></div>
    <div class="mn-sheet-hdr">
      <button onclick="cerrarModal()" type="button" class="mn-sheet-close" aria-label="Cerrar"><?= mnIcon('x', 'mn-icon') ?></button>
      <div class="mn-sheet-title" id="sheetTitle">—</div>
      <div class="mn-sheet-sub"   id="sheetSub">—</div>
      <div id="sheetDesc" style="font-size:.9rem;color:#374151;line-height:1.55;margin-top:12px;display:none"></div>
    </div>
    <div class="mn-sec" id="infoAlergSec" style="display:none">
      <div class="mn-sec-lbl"><?= mnIcon('info', 'mn-icon') ?> Información del platillo</div>
      <div id="infoAlergBox" style="display:flex;flex-direction:column;gap:8px;font-size:.82rem;color:#374151"></div>
    </div>
    <div class="mn-sec" id="guarSec" style="display:none">
      <div class="mn-sec-lbl"><?= mnIcon('list-checks', 'mn-icon') ?> Guarniciones incluidas</div>
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

let activeCat = '';
const searchInput = document.getElementById('mnSearchInput');
const searchClear = document.getElementById('mnSearchClear');
const searchEmpty = document.getElementById('mnSearchEmpty');

document.querySelectorAll('.mn-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.mn-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeCat = btn.dataset.cat || '';
    aplicarFiltros();
  });
});

if (searchInput) {
  searchInput.addEventListener('input', aplicarFiltros);
}
if (searchClear) {
  searchClear.addEventListener('click', () => {
    searchInput.value = '';
    searchInput.focus();
    aplicarFiltros();
  });
}

function normalizarBusqueda(str) {
  return String(str || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();
}

function aplicarFiltros() {
  const query = normalizarBusqueda(searchInput ? searchInput.value : '');
  let visibles = 0;

  document.querySelectorAll('.mn-card').forEach(card => {
    const coincideCat = !activeCat || String(card.dataset.cat) === String(activeCat);
    const hayTexto = !query || normalizarBusqueda(card.dataset.search).includes(query);
    const visible = coincideCat && hayTexto;
    card.style.display = visible ? '' : 'none';
    if (visible) visibles++;
  });

  document.querySelectorAll('.mn-section').forEach(sec => {
    const vis = [...sec.querySelectorAll('.mn-card')].some(card => card.style.display !== 'none');
    sec.style.display = vis ? '' : 'none';
  });

  if (searchClear) searchClear.classList.toggle('visible', query.length > 0);
  if (searchEmpty) searchEmpty.classList.toggle('visible', visibles === 0);
}

function abrirModal(id) {
  const d = MENU[id]; if (!d) return;
  document.getElementById('sheetTitle').textContent = d.nombre;
  document.getElementById('sheetSub').innerHTML = `${iconHtml('badge-dollar-sign')} $${d.precio % 1 === 0 ? d.precio.toFixed(0) : d.precio.toFixed(2)} por orden`;

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
    imgWrap.innerHTML = `<div class="mn-sheet-img-placeholder">${iconHtml('utensils', 'mn-icon-lg')}</div>`;
  }

  const gSec  = document.getElementById('guarSec');
  const gList = document.getElementById('guarList');
  const mostrarIng = !CAT_SIN_ING.has(d.cat_id);

  const infoSec = document.getElementById('infoAlergSec');
  const infoBox = document.getElementById('infoAlergBox');
  let infoHtml = '';
  if (d.alergenos && d.alergenos.length) {
    infoHtml += `<div><strong style="color:#92400E;display:inline-flex;align-items:center;gap:5px">${iconHtml('triangle-alert')} Alérgenos:</strong> `
      + d.alergenos.map(a => `<span class="mn-allergen" style="margin:2px 3px 0 0">${iconHtml('triangle-alert')} ${esc(a)}</span>`).join('')
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
  renderIcons();
}

function guarRow(ing) {
  const cantidad = [ing.cantidad, ing.unidad].filter(Boolean).join(' ');
  return `<div class="mn-guar-row">
    <div class="mn-guar-tog" style="cursor:default">
      <div class="mn-tog-icon incl">${iconHtml('check')}</div>
      <span>${esc(ing.ingrediente_nombre)}</span>
      ${cantidad ? `<span style="font-size:.7rem;color:var(--text-muted);margin-left:auto;padding-left:6px">${esc(cantidad)}</span>` : ''}
    </div>
  </div>`;
}

function iconHtml(name, className = 'mn-icon') {
  return `<i data-lucide="${esc(name)}" class="${esc(className)}"></i>`;
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function cerrarModal() {
  document.getElementById('mnBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}
function handleBackdrop(e) { if (e.target===e.currentTarget) cerrarModal(); }
function renderIcons() { if (window.lucide) lucide.createIcons(); }
renderIcons();
</script>
</body>
</html>
