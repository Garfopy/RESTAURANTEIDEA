<?php
/** @var string $appName */
/** @var string $appLogo */
/** @var string $colorPrimary */
/** @var string $contactEmail */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Plataforma de abastecimiento productos cárnicos con visibilidad y trazabilidad a 1 click | <?= htmlspecialchars($appName) ?></title>
  <meta name="description" content="Optimiza compras, entregas, trazabilidad y logística inversa con CarniHub. Ideal para CEDIS de restaurantes, hoteles, taquerías y carnicerías.">
  <meta name="keywords" content="proveedores de carne con crédito, cotización de carne para comedores, precio de carne para restaurantes, carne por mayoreo para hoteles, proveedor de carne para cadenas restauranteras, proveedor de carne con entrega garantizada, proveedor de carne para CEDIS, compra de carne para restaurantes, compra de carne para hoteles, proveedor de carne para taquerías">
  <link rel="canonical" href="<?= BASE_URL ?>cedis">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Organization",
        "name": "CarniHub",
        "url": "<?= BASE_URL ?>",
        "description": "Plataforma de abastecimiento, trazabilidad y logística para restaurantes y cadenas gastronómicas.",
        "knowsAbout": [
          "logística de alimentos perecederos",
          "trazabilidad de productos cárnicos",
          "software de compras para restaurantes",
          "gestión de devoluciones alimentos",
          "monitoreo de temperatura"
        ]
      },
      {
        "@type": "Service",
        "name": "Plataforma logística y abastecimiento para restaurantes",
        "provider": { "@type": "Organization", "name": "CarniHub" }
      },
      {
        "@type": "FAQPage",
        "mainEntity": [
          {
            "@type": "Question",
            "name": "¿Cómo controlar compras multi-sucursal en restaurantes?",
            "acceptedAnswer": { "@type": "Answer", "text": "Centralizando pedidos, facturación y reportes desde una sola plataforma como CarniHub." }
          },
          {
            "@type": "Question",
            "name": "¿Cómo mejorar la logística de alimentos perecederos?",
            "acceptedAnswer": { "@type": "Answer", "text": "Implementando monitoreo de rutas, temperatura y validación digital de entregas." }
          },
          {
            "@type": "Question",
            "name": "¿Cómo reducir mermas y devoluciones en restaurantes?",
            "acceptedAnswer": { "@type": "Answer", "text": "Usando sistemas con trazabilidad, evidencia digital y control logístico." }
          },
          {
            "@type": "Question",
            "name": "¿CarniHub funciona para cadenas con múltiples sucursales?",
            "acceptedAnswer": { "@type": "Answer", "text": "Sí. Está diseñado para operaciones multi-sucursal y control centralizado." }
          }
        ]
      }
    ]
  }
  </script>
  <style>
    :root { --cp: <?= htmlspecialchars($colorPrimary) ?>; }
    * { scroll-behavior: smooth; }
    body { font-family: 'Inter', sans-serif; overflow-x: hidden; }
    .bg-primary   { background: var(--cp); }
    .text-primary { color: var(--cp); }
    .btn-primary  { background: var(--cp); color: #fff; transition: transform .2s, box-shadow .2s, opacity .2s; }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px color-mix(in srgb, var(--cp) 50%, transparent); opacity: .92; }
    .btn-outline  { border: 2px solid rgba(255,255,255,.35); color: #fff; transition: background .2s, border-color .2s, transform .2s; }
    .btn-outline:hover { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.7); transform: translateY(-2px); }
    .hero-bg {
      background: radial-gradient(ellipse 80% 60% at 50% -10%, color-mix(in srgb, var(--cp) 30%, transparent), transparent),
                  linear-gradient(160deg, #0a0f1e 0%, #111827 55%, #1a2235 100%);
    }
    .orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: .2; }
    .reveal { opacity:0; transform:translateY(32px); transition:opacity .7s ease, transform .7s ease; }
    .reveal.visible { opacity:1; transform:translateY(0); }
    .navbar { transition: background .3s, box-shadow .3s; }
    .navbar.scrolled { background: rgba(255,255,255,.97) !important; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
    .btn-shimmer { position:relative; overflow:hidden; }
    .btn-shimmer::after { content:''; position:absolute; top:0; left:-100%; width:60%; height:100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent); animation: shimmer 2.5s infinite; }
    @keyframes shimmer { 0%{left:-100%} 100%{left:200%} }
    @keyframes pulse-ring { 0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--cp) 60%,transparent)} 100%{box-shadow:0 0 0 12px transparent} }
    .pulse-badge { animation: pulse-ring 2s infinite; }
    .feat-card { border: 1px solid #e2e8f0; transition: transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s, border-color .25s; }
    .feat-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,.08); border-color: color-mix(in srgb, var(--cp) 30%, transparent); }
    .banner-primary { background: linear-gradient(135deg, color-mix(in srgb, var(--cp) 90%, #000), var(--cp)); }
    .faq-item details summary { cursor: pointer; list-style: none; }
    .faq-item details summary::-webkit-details-marker { display: none; }
    .faq-arrow { transition: transform .2s; }
    .faq-item details[open] .faq-arrow { transform: rotate(180deg); }
    .text-gradient { background: linear-gradient(135deg, #fff 30%, color-mix(in srgb,var(--cp) 80%,#fff)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
    .module-card { background: linear-gradient(180deg,#fff,#f8fafc); border: 1px solid #e2e8f0; border-radius: 1.25rem; padding: 1.5rem; transition: transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s; }
    .module-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,.08); }
    /* ── Slider ── */
    .slider-wrap { position:relative; overflow:hidden; min-height:82vh; }
    .slide { position:absolute; inset:0; opacity:0; pointer-events:none; transition:opacity .8s ease; }
    .slide.active { opacity:1; pointer-events:auto; }
    .slider-arrow { position:absolute; top:50%; transform:translateY(-50%); z-index:30; background:rgba(255,255,255,.13); border:1px solid rgba(255,255,255,.3); color:#fff; width:52px; height:52px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:background .2s, border-color .2s, transform .2s; backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); }
    .slider-arrow:hover { background:rgba(255,255,255,.28); border-color:rgba(255,255,255,.7); transform:translateY(-50%) scale(1.08); }
    #slider-prev { left:20px; }
    #slider-next { right:20px; }
    .slider-dots { position:absolute; bottom:30px; left:50%; transform:translateX(-50%); display:flex; gap:10px; z-index:30; }
    .slider-dot { width:10px; height:10px; border-radius:50%; background:rgba(255,255,255,.35); border:none; cursor:pointer; transition:background .25s, transform .25s; padding:0; }
    .slider-dot.active { background:#fff; transform:scale(1.4); }
    .slide-chip { display:inline-flex; align-items:center; gap:.5rem; padding:.4rem 1.1rem; border-radius:9999px; font-size:.72rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; margin-bottom:1.25rem; }
    .slider-progress { position:absolute; bottom:0; left:0; height:3px; background:var(--cp); width:0%; z-index:30; }
    .slider-progress.running { width:100%; transition:width 5s linear; }
  </style>
</head>
<body class="bg-white text-gray-900">

<!-- ══ NAVBAR ══ -->
<nav class="navbar fixed top-0 w-full z-50 bg-transparent">
  <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="<?= BASE_URL ?>" class="flex items-center gap-2 no-underline">
        <?php if ($appLogo): ?>
          <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo" class="h-9 object-contain">
        <?php else: ?>
          <span class="text-xl font-black text-white tracking-tight"><?= htmlspecialchars($appName) ?></span>
        <?php endif; ?>
      </a>
      <svg class="w-3.5 h-3.5 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      <span class="text-white/60 text-sm font-medium hidden md:block">CEDIS &amp; Carnicerías</span>
    </div>
    <div class="flex items-center gap-2">
      <a href="<?= BASE_URL ?>auth/login" class="text-sm font-semibold text-white/80 px-4 py-2 hover:text-white transition-colors">Iniciar sesión</a>
      <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer text-sm font-bold px-5 py-2.5 rounded-xl">Solicitar demo</a>
    </div>
  </div>
</nav>

<!-- ══ SLIDER HERO ══ -->
<div class="slider-wrap pt-16" id="hero-slider">

  <!-- Slide 1: Controla entregas, incidencias y trazabilidad desde un solo sistema -->
  <!-- alt-img: proveedor de carne para taquerías -->
  <div class="slide active" data-slide="0" style="background:radial-gradient(ellipse 80% 60% at 50% -10%,color-mix(in srgb,var(--cp) 30%,transparent),transparent),linear-gradient(160deg,#0a0f1e 0%,#111827 55%,#1a2235 100%)">
    <div class="orb" style="width:500px;height:500px;background:var(--cp);top:-120px;right:-100px;"></div>
    <div class="orb" style="width:350px;height:350px;background:#6366f1;bottom:-80px;left:-60px;"></div>
    <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 relative z-10">
      <div class="max-w-3xl">
        <div class="slide-chip pulse-badge" style="background:color-mix(in srgb,var(--cp) 20%,transparent);border:1px solid color-mix(in srgb,var(--cp) 50%,transparent);color:var(--cp)">
          <span class="w-2 h-2 rounded-full bg-primary animate-pulse inline-block"></span>
          Plataforma para CEDIS · Trazabilidad a 1 click
        </div>
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white leading-tight mb-5">
          Controla entregas, incidencias<br>
          <span class="text-gradient">y trazabilidad desde un solo sistema</span>
        </h2>
        <p class="text-gray-400 text-lg mb-8 max-w-xl leading-relaxed">
          Visibilidad total sobre cada pedido, ruta y entrega. Diseñado para responsables de CEDIS y operaciones gastronómicas complejas.
        </p>
        <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold px-8 py-4 rounded-xl">Solicitar demostración →</a>
      </div>
    </div>
  </div>

  <!-- Slide 2: Reduce mermas, devoluciones y pérdidas operativas -->
  <!-- alt-img: proveedor de carne con entrega garantizada -->
  <div class="slide" data-slide="1" style="background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(34,197,94,.25),transparent),linear-gradient(160deg,#0a0f1e 0%,#111827 55%,#0f1e14 100%)">
    <div class="orb" style="width:500px;height:500px;background:#22c55e;top:-120px;right:-100px;"></div>
    <div class="orb" style="width:350px;height:350px;background:var(--cp);bottom:-80px;left:-60px;"></div>
    <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 relative z-10">
      <div class="max-w-3xl">
        <div class="slide-chip" style="background:rgba(34,197,94,.18);border:1px solid rgba(34,197,94,.45);color:#22c55e">
          <span class="w-2 h-2 rounded-full inline-block" style="background:#22c55e"></span>
          Logística inversa · Devoluciones ágiles
        </div>
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white leading-tight mb-5">
          Reduce mermas, devoluciones<br>
          <span style="background:linear-gradient(135deg,#fff 30%,#86efac);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">y pérdidas operativas en tus sucursales</span>
        </h2>
        <p class="text-gray-400 text-lg mb-8 max-w-xl leading-relaxed">
          Trazabilidad documental, evidencia POD digital y seguimiento de incidencias para reducir conflictos con proveedores y pérdidas económicas.
        </p>
        <a href="<?= BASE_URL ?>planes/registro" class="inline-block font-bold px-8 py-4 rounded-xl" style="background:#22c55e;color:#fff">Ver gestión de mermas →</a>
      </div>
    </div>
  </div>

  <!-- Slide 3: Centraliza compras, crédito, facturación y pedidos multi-sucursal -->
  <!-- alt-img: proveedores de carne con crédito -->
  <div class="slide" data-slide="2" style="background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(245,158,11,.28),transparent),linear-gradient(160deg,#0a0f1e 0%,#111827 55%,#1e1a10 100%)">
    <div class="orb" style="width:500px;height:500px;background:#f59e0b;top:-120px;right:-100px;"></div>
    <div class="orb" style="width:350px;height:350px;background:var(--cp);bottom:-80px;left:-60px;"></div>
    <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 relative z-10">
      <div class="max-w-3xl">
        <div class="slide-chip" style="background:rgba(245,158,11,.18);border:1px solid rgba(245,158,11,.45);color:#f59e0b">
          <span class="w-2 h-2 rounded-full inline-block" style="background:#f59e0b"></span>
          Compras multi-sucursal · Crédito estructurado
        </div>
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white leading-tight mb-5">
          Centraliza compras, crédito,<br>
          <span style="background:linear-gradient(135deg,#fff 30%,#fcd34d);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">facturación y pedidos multi-sucursal</span>
        </h2>
        <p class="text-gray-400 text-lg mb-8 max-w-xl leading-relaxed">
          Control administrativo total sobre precios acordados, crédito con proveedores, facturación automática y conciliación sin errores.
        </p>
        <a href="<?= BASE_URL ?>planes/registro" class="inline-block font-bold px-8 py-4 rounded-xl" style="background:#f59e0b;color:#fff">Ver control de compras →</a>
      </div>
    </div>
  </div>

  <!-- Slide 4: Monitorea unidades, rutas y cadena de frío en tiempo real -->
  <!-- alt-img: proveedor de carne para cadenas restauranteras -->
  <div class="slide" data-slide="3" style="background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(99,102,241,.28),transparent),linear-gradient(160deg,#0a0f1e 0%,#111827 55%,#0e1120 100%)">
    <div class="orb" style="width:500px;height:500px;background:#6366f1;top:-120px;right:-100px;"></div>
    <div class="orb" style="width:350px;height:350px;background:var(--cp);bottom:-80px;left:-60px;"></div>
    <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 relative z-10">
      <div class="max-w-3xl">
        <div class="slide-chip" style="background:rgba(99,102,241,.18);border:1px solid rgba(99,102,241,.45);color:#a5b4fc">
          <span class="w-2 h-2 rounded-full inline-block" style="background:#a5b4fc"></span>
          IIoT · Cadena de frío · Rutas
        </div>
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white leading-tight mb-5">
          Monitorea unidades, rutas<br>
          <span style="background:linear-gradient(135deg,#fff 30%,#c7d2fe);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">y cadena de frío en tiempo real</span>
        </h2>
        <p class="text-gray-400 text-lg mb-8 max-w-xl leading-relaxed">
          Visibilidad en tiempo real sobre temperatura, rutas y estado de activos para cadenas restauranteras con múltiples puntos de operación.
        </p>
        <a href="<?= BASE_URL ?>planes/registro" class="inline-block font-bold px-8 py-4 rounded-xl" style="background:#6366f1;color:#fff">Ver monitoreo IIoT →</a>
      </div>
    </div>
  </div>

  <!-- Flecha izquierda -->
  <button class="slider-arrow" id="slider-prev" aria-label="Diapositiva anterior">
    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
  </button>

  <!-- Flecha derecha -->
  <button class="slider-arrow" id="slider-next" aria-label="Diapositiva siguiente">
    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
    </svg>
  </button>

  <!-- Dots de navegación -->
  <div class="slider-dots" id="slider-dots">
    <button class="slider-dot active" data-slide="0" aria-label="Diapositiva 1"></button>
    <button class="slider-dot" data-slide="1" aria-label="Diapositiva 2"></button>
    <button class="slider-dot" data-slide="2" aria-label="Diapositiva 3"></button>
    <button class="slider-dot" data-slide="3" aria-label="Diapositiva 4"></button>
  </div>

  <!-- Barra de progreso -->
  <div class="slider-progress" id="slider-progress"></div>
</div>

<!-- ══ H1 INTRO ══ -->
<section id="intro" class="bg-white py-16">
  <div class="max-w-6xl mx-auto px-6 reveal">
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-5">
      <a href="<?= BASE_URL ?>" class="hover:text-gray-600 transition-colors">Inicio</a>
      <span>›</span>
      <span>CEDIS &amp; Carnicerías</span>
    </div>
    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-5">
      Plataforma especializada en abastecimiento, trazabilidad y logística para CEDIS de cadenas restauranteras, hoteles, taquerías, comedores industriales
    </h1>
    <p class="text-gray-600 leading-relaxed max-w-3xl mb-4">
      CarniHub ayuda a responsables de CEDIS, cadenas de restaurantes, hoteles, taquerías y carnicerías
      a optimizar su operación completa desde un ecosistema digital diseñado para operaciones gastronómicas complejas.
    </p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-6 max-w-3xl">
      <?php foreach (['Compras','Abastecimiento','Logística','Devoluciones','Entregas','Inventarios','Control multi-sucursal','Trazabilidad'] as $tag): ?>
      <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold border"
            style="background:color-mix(in srgb,var(--cp) 8%,#fff);border-color:color-mix(in srgb,var(--cp) 25%,transparent);color:var(--cp)"><?= htmlspecialchars($tag) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="flex flex-col sm:flex-row gap-3">
      <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold text-base px-8 py-4 rounded-xl text-center">Solicitar demostración →</a>
      <a href="#control" class="inline-block border-2 border-gray-200 font-semibold text-base px-8 py-4 rounded-xl text-gray-700 hover:border-gray-400 transition-colors text-center">Ver funcionalidades</a>
    </div>
  </div>
</section>

<!-- ══ Módulos en un vistazo ══ -->
<section class="bg-slate-50 py-14">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 reveal">
      <?php foreach ([
        ['✅','Mermas y devoluciones',['Trazabilidad','POD digital','Incidencias']],
        ['💳','Facturación y crédito',['Control administrativo','Compras recurrentes','Conciliación']],
        ['🚚','Personal y entregas',['Validación de rutas','Evidencias','Inventarios']],
        ['📡','IIoT y mantenimiento',['Monitoreo activos','Cadena de frío','Preventivo']],
      ] as [$icon,$title,$items]): ?>
      <div class="module-card text-center">
        <div class="text-3xl mb-3"><?= $icon ?></div>
        <div class="font-extrabold text-gray-900 text-sm mb-2"><?= htmlspecialchars($title) ?></div>
        <?php foreach ($items as $i): ?>
        <div class="text-xs text-gray-400 py-0.5"><?= htmlspecialchars($i) ?></div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ H2: Control compras / crédito / facturación ══ -->
<section id="control" class="bg-white py-20">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid md:grid-cols-2 gap-14 items-center">
      <div class="reveal">
        <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Gestión administrativa</p>
        <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-5">
          Control total de compras, crédito y facturación para restaurantes y CEDIS
        </h2>
        <p class="text-gray-600 mb-6 leading-relaxed">
          Uno de los mayores problemas en operaciones gastronómicas multi-sucursal es la falta de control
          administrativo sobre precios variables, compras descentralizadas y diferencias de facturación.
        </p>
        <ul class="space-y-3 mb-8">
          <?php foreach ([
            'Múltiples usuarios con roles definidos',
            'Centralizar pedidos de todas las sucursales',
            'Controlar precios acordados por proveedor',
            'Gestionar crédito con proveedores',
            'Validar facturación automáticamente',
            'Automatizar compras recurrentes',
          ] as $item): ?>
          <li class="flex items-center gap-3 text-gray-700">
            <svg class="w-5 h-5 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <?= htmlspecialchars($item) ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <p class="text-sm text-gray-400 mb-6 italic">Especial atención a: taquerías · compras consolidadas · carne en caja por mayoreo · crédito para negocios</p>
        <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold px-7 py-3.5 rounded-xl">Centraliza tu operación →</a>
      </div>
      <div class="reveal">
        <div class="rounded-2xl p-8" style="background:linear-gradient(135deg,#1a2235,#111827)">
          <div class="text-4xl mb-4">🏭</div>
          <h3 class="text-xl font-extrabold text-white mb-5">Esto reduce errores y mejora control financiero</h3>
          <div class="space-y-4">
            <?php foreach ([
              ['Visibilidad total','Trazabilidad de cada solicitud en tiempo real'],
              ['Control de precios','Sin variaciones inesperadas entre sucursales'],
              ['Crédito estructurado','Gestión transparente con cada proveedor'],
              ['Facturación automática','Conciliación sin intervención manual'],
            ] as [$title,$desc]): ?>
            <div class="bg-white/5 rounded-xl p-4">
              <div class="font-bold text-white text-sm"><?= htmlspecialchars($title) ?></div>
              <div class="text-gray-400 text-xs mt-0.5"><?= htmlspecialchars($desc) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ BANNER 1: link cruzado a taquerías ══ -->
<section class="banner-primary py-14">
  <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6 reveal">
    <div>
      <p class="text-white/70 text-sm font-medium mb-1">Para taquerías</p>
      <h3 class="text-2xl md:text-3xl font-extrabold text-white">Conoce a la distribuidora de carne más cerca de ti.</h3>
    </div>
    <a href="<?= BASE_URL ?>taqueria"
       class="flex-shrink-0 bg-white font-bold px-8 py-4 rounded-xl text-sm hover:bg-gray-100 transition-colors whitespace-nowrap"
       style="color:var(--cp)">
      Ver solución para taquerías →
    </a>
  </div>
</section>

<!-- ══ H2: Mermas, devoluciones, logística inversa ══ -->
<section class="bg-white py-20">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid md:grid-cols-2 gap-14 items-start">
      <div class="reveal">
        <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Logística inversa</p>
        <h2 class="text-3xl font-extrabold text-gray-900 mb-5">
          Gestión de mermas, devoluciones y logística inversa alimentaria
        </h2>
        <p class="text-gray-600 mb-6 leading-relaxed">
          La falta de trazabilidad y control operativo provoca mermas, productos dañados, devoluciones tardías,
          pérdidas económicas y conflictos con proveedores.
        </p>
        <div class="bg-red-50 border border-red-100 rounded-2xl p-5 mb-6">
          <h4 class="font-bold text-red-800 text-sm mb-2">Sin control, se generan:</h4>
          <ul class="space-y-1.5">
            <?php foreach (['Mermas no registradas','Productos dañados sin seguimiento','Devoluciones tardías','Pérdidas económicas','Conflictos con proveedores'] as $item): ?>
            <li class="text-sm text-red-700 flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
              <?= htmlspecialchars($item) ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="bg-green-50 border border-green-100 rounded-2xl p-5">
          <h4 class="font-bold text-green-800 text-sm mb-2">Con CarniHub mejoras:</h4>
          <ul class="space-y-1.5">
            <?php foreach (['Gestión de devoluciones alimentos','Logística inversa ágil','Trazabilidad documental completa','Seguimiento de incidencias','Validación de entregas'] as $item): ?>
            <li class="text-sm text-green-700 flex items-center gap-2">
              <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
              </svg>
              <?= htmlspecialchars($item) ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <div class="reveal">
        <div class="bg-slate-50 rounded-2xl p-8 border border-gray-100">
          <div class="text-4xl mb-4">📱</div>
          <h3 class="text-xl font-extrabold text-gray-900 mb-2">Evidencia digital POD para validar entregas y devoluciones</h3>
          <p class="text-gray-500 text-sm mb-6 leading-relaxed">
            Ideal para operaciones con múltiples sucursales y rutas diarias. La evidencia digital POD permite:
          </p>
          <ul class="space-y-3 mb-6">
            <?php foreach ([
              ['📝','Documentar entregas','Registro automático en tiempo real'],
              ['✅','Validar recepción','Firma o foto digital del receptor'],
              ['⚠️','Registrar incidencias','Anomalías documentadas al instante'],
              ['⚖️','Reducir disputas','Evidencia verificable ante cualquier reclamo'],
              ['🔍','Mejorar auditorías internas','Historial completo accesible'],
            ] as [$icon,$title,$sub]): ?>
            <li class="flex items-start gap-3">
              <span class="text-lg"><?= $icon ?></span>
              <div>
                <div class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($title) ?></div>
                <div class="text-gray-400 text-xs"><?= htmlspecialchars($sub) ?></div>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
          <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer block text-center font-bold py-3.5 rounded-xl">
            Activar POD digital →
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ H2: Monitoreo rutas y cadena de frío ══ -->
<section class="py-20" style="background:linear-gradient(180deg,#f8fafc,#fff)">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid md:grid-cols-2 gap-14 items-center">
      <div class="reveal">
        <div class="rounded-2xl p-8" style="background:linear-gradient(135deg,#1a2235,#111827)">
          <div class="text-4xl mb-4">🌡️</div>
          <h3 class="text-xl font-extrabold text-white mb-5">A menos de 2 clicks, obtendrás</h3>
          <ul class="space-y-3">
            <?php foreach ([
              'Rastreo de transporte de alimentos',
              'Monitoreo de temperatura en transporte',
              'Control de unidades de reparto',
              'Trazabilidad de productos cárnicos',
              'Validación operativa de rutas',
            ] as $item): ?>
            <li class="flex items-center gap-3 text-gray-300">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="color:var(--cp)">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
              </svg>
              <?= htmlspecialchars($item) ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <p class="mt-5 pt-4 border-t border-white/10 text-sm text-gray-400">
            Esto fortalece cumplimiento sanitario y control logístico.
          </p>
        </div>
      </div>
      <div class="reveal">
        <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Cadena de frío</p>
        <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-5">
          Monitoreo de rutas, unidades y cadena de frío en tiempo real
        </h2>
        <p class="text-gray-600 mb-6 leading-relaxed">
          La logística de alimentos perecederos requiere monitoreo constante, trazabilidad, control de temperatura,
          seguimiento de rutas y visibilidad operativa. Sin estos controles aumenta el riesgo operativo
          y se compromete el cumplimiento normativo.
        </p>
        <div class="grid grid-cols-2 gap-3 mb-8">
          <?php foreach ([
            ['🚛','Hoteles','Cadenas Premium'],
            ['👨‍🍳','Chefs ejecutivos','Estándares altos'],
            ['🏅','Prov. TIF','Certificados'],
            ['📋','Control sanitario','Trazabilidad total'],
          ] as [$icon,$title,$sub]): ?>
          <div class="bg-slate-50 rounded-xl p-4 border border-gray-100 feat-card text-center">
            <div class="text-2xl mb-1"><?= $icon ?></div>
            <div class="font-bold text-gray-900 text-xs"><?= htmlspecialchars($title) ?></div>
            <div class="text-gray-400 text-xs"><?= htmlspecialchars($sub) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold px-7 py-3.5 rounded-xl">Monitorear mi operación →</a>
      </div>
    </div>
  </div>
</section>

<!-- ══ BANNER 2: link cruzado a restaurantes ══ -->
<section style="background:linear-gradient(135deg,#0a0f1e,#1a2235)" class="py-14">
  <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6 reveal">
    <div>
      <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color:var(--cp)">Para restaurantes</p>
      <h3 class="text-2xl md:text-3xl font-extrabold text-white">Cortes de carne premium para restaurantes y hoteles.</h3>
    </div>
    <a href="<?= BASE_URL ?>restaurantes" class="flex-shrink-0 btn-primary btn-shimmer font-bold px-8 py-4 rounded-xl text-sm whitespace-nowrap">
      Ver solución para restaurantes →
    </a>
  </div>
</section>

<!-- ══ H2: Pedidos multi-sucursal ══ -->
<section class="bg-white py-20">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid md:grid-cols-2 gap-14 items-start">
      <div class="reveal">
        <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Multi-sucursal</p>
        <h2 class="text-3xl font-extrabold text-gray-900 mb-5">
          Pedidos multi-sucursal, inventarios y control operativo
        </h2>
        <p class="text-gray-600 mb-6 leading-relaxed">
          Los grupos restauranteros necesitan pedidos centralizados, control de inventarios, historial de consumo,
          seguimiento por sucursal y control de entregas.
        </p>
        <h3 class="font-extrabold text-gray-900 mb-4">Resolvemos</h3>
        <ul class="space-y-3 mb-6">
          <?php foreach ([
            'Automatizar pedidos multi-sucursal',
            'Registrar incidencias logísticas',
            'Controlar inventarios por sucursal',
            'Analizar consumo operativo',
            'Reducir compras urgentes',
          ] as $item): ?>
          <li class="flex items-center gap-3 text-gray-700">
            <svg class="w-5 h-5 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <?= htmlspecialchars($item) ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <p class="text-sm text-gray-400 italic mb-6">Esto mejora eficiencia y reduce desperdicio en operaciones multi-punto.</p>
      </div>

      <div class="reveal">
        <div class="rounded-2xl p-8" style="background:linear-gradient(135deg,#1a2235,#111827)">
          <h3 class="text-xl font-extrabold text-white mb-2">Reportes de consumo y rendimiento por sucursal</h3>
          <p class="text-gray-400 text-sm mb-5 leading-relaxed">
            Información clave para responsables de CEDIS y operaciones. CarniHub genera visibilidad sobre:
          </p>
          <ul class="space-y-3 mb-6">
            <?php foreach ([
              'Consumo de productos por unidad',
              'Comportamiento operativo histórico',
              'Frecuencia de compras por sucursal',
              'Incidencias recurrentes identificadas',
              'Eficiencia logística por ruta',
            ] as $item): ?>
            <li class="flex items-center gap-3 text-gray-300">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="color:var(--cp)">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
              </svg>
              <?= htmlspecialchars($item) ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer block text-center font-bold py-3.5 rounded-xl">
            Ver reportes →
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ H2: IIoT y mantenimiento ══ -->
<section class="py-20" style="background:linear-gradient(180deg,#f8fafc,#f1f5f9)">
  <div class="max-w-6xl mx-auto px-6">
    <div class="text-center mb-12 reveal">
      <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Tecnología avanzada</p>
      <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">
        IIoT y mantenimiento para instalaciones, almacenes y unidades
      </h2>
      <p class="text-gray-500 max-w-2xl mx-auto">
        Las operaciones modernas requieren visibilidad completa sobre almacenes, cámaras de refrigeración,
        unidades de reparto, mantenimiento preventivo e incidencias técnicas.
      </p>
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ([
        ['📡','IIoT a unidades de entrega','Conectividad en tiempo real a cada vehículo de reparto y punto de almacenamiento.'],
        ['🌡️','Monitoreo de cadena de frío','Alertas automáticas ante desviaciones de temperatura en cámaras y unidades.'],
        ['🔧','Mantenimiento preventivo','Programa mantenimientos antes de que se conviertan en fallas operativas.'],
        ['📊','Control de activos','Visibilidad del estado y ubicación de cada activo operativo.'],
        ['🗺️','Visibilidad de rutas','Seguimiento en tiempo real de entregas, devoluciones y paradas.'],
        ['⚠️','Incidencias técnicas','Registro y seguimiento de fallas para reducir tiempos de inactividad.'],
      ] as [$icon,$title,$desc]): ?>
      <div class="bg-white rounded-2xl p-6 border border-gray-100 feat-card reveal">
        <div class="text-3xl mb-3"><?= $icon ?></div>
        <h3 class="font-extrabold text-gray-900 text-base mb-2"><?= htmlspecialchars($title) ?></h3>
        <p class="text-gray-500 text-sm leading-relaxed"><?= htmlspecialchars($desc) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ BANNER 3 ══ -->
<section class="banner-primary py-14">
  <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6 reveal">
    <div>
      <p class="text-white/70 text-sm font-medium mb-1">Multi-punto</p>
      <h3 class="text-2xl md:text-3xl font-extrabold text-white">Diseñado para cadenas gastronómicas con múltiples puntos de operación.</h3>
    </div>
    <a href="<?= BASE_URL ?>planes/registro"
       class="flex-shrink-0 bg-white font-bold px-8 py-4 rounded-xl text-sm hover:bg-gray-100 transition-colors whitespace-nowrap"
       style="color:var(--cp)">
      Haz una prueba hoy →
    </a>
  </div>
</section>

<!-- ══ FAQ ══ -->
<section class="bg-white py-20">
  <div class="max-w-3xl mx-auto px-6">
    <div class="text-center mb-10 reveal">
      <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Preguntas frecuentes</p>
      <h2 class="text-3xl font-extrabold text-gray-900">Resolvemos tus dudas</h2>
    </div>
    <div class="space-y-4 reveal">
      <?php foreach ([
        ['¿Cómo controlar compras multi-sucursal en restaurantes?',
         'Centralizando pedidos, facturación y reportes desde una sola plataforma como CarniHub.'],
        ['¿Cómo mejorar la logística de alimentos perecederos?',
         'Implementando monitoreo de rutas, temperatura y validación digital de entregas.'],
        ['¿Cómo reducir mermas y devoluciones en restaurantes?',
         'Usando sistemas con trazabilidad, evidencia digital y control logístico integrado.'],
        ['¿CarniHub vende carne directamente?',
         'Sí. CarniHub cuenta con infraestructura para abastecimiento y distribución de productos cárnicos.'],
        ['¿CarniHub funciona para cadenas con múltiples sucursales?',
         'Sí. Está diseñado para operaciones multi-sucursal y control centralizado desde un solo panel.'],
        ['¿Se puede monitorear temperatura y rutas?',
         'Sí. CarniHub permite monitoreo operativo y trazabilidad logística en tiempo real.'],
        ['¿CarniHub ayuda con devoluciones e incidencias?',
         'Sí. Facilita gestión documental, seguimiento operativo y logística inversa ágil.'],
        ['¿Qué tipo de negocios usan CarniHub?',
         'Restaurantes, hoteles, taquerías, hospitales, carnicerías y comedores industriales.'],
        ['¿Qué beneficios tiene la trazabilidad de productos cárnicos?',
         'Mejora control sanitario, reduce pérdidas y facilita auditorías internas y externas.'],
      ] as [$q,$a]): ?>
      <div class="faq-item border border-gray-200 rounded-2xl overflow-hidden">
        <details>
          <summary class="flex items-center justify-between p-5 font-semibold text-gray-900 hover:bg-slate-50 transition-colors">
            <?= htmlspecialchars($q) ?>
            <svg class="faq-arrow w-5 h-5 flex-shrink-0 ml-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </summary>
          <div class="px-5 pb-5 text-gray-600 text-sm leading-relaxed border-t border-gray-100 pt-4"><?= htmlspecialchars($a) ?></div>
        </details>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ CTA FINAL ══ -->
<section class="py-24 relative overflow-hidden" style="background:linear-gradient(135deg,#0a0f1e 0%,#111827 60%,#1a2235 100%)">
  <div class="orb" style="width:600px;height:600px;background:var(--cp);top:-200px;left:50%;transform:translateX(-50%);opacity:.12;filter:blur(100px);"></div>
  <div class="max-w-3xl mx-auto px-6 text-center relative z-10 reveal">
    <p class="text-xs font-bold uppercase tracking-widest text-primary mb-4">Empieza hoy</p>
    <h2 class="text-4xl md:text-5xl font-black text-white mb-6 leading-tight">
      Optimiza abastecimiento,<br>
      <span class="text-gradient">logística y trazabilidad</span>
    </h2>
    <p class="text-gray-400 text-lg mb-10 max-w-xl mx-auto leading-relaxed">
      Únete a los responsables de CEDIS que ya digitalizaron su operación con CarniHub.
      Solicita una demostración y comprueba el impacto en tu operación.
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer font-bold text-lg px-10 py-4 rounded-2xl">Solicitar demostración →</a>
      <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" class="btn-outline font-semibold text-base px-8 py-4 rounded-2xl">Hablar con ventas</a>
    </div>
  </div>
</section>

<!-- ══ FOOTER ══ -->
<footer class="bg-gray-950 py-12">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 border-b border-gray-800 pb-8 mb-8">
      <div>
        <span class="text-lg font-black text-white"><?= htmlspecialchars($appName) ?></span>
        <p class="text-sm text-gray-500 mt-2">Plataforma de abastecimiento, trazabilidad y logística para cadenas gastronómicas.</p>
      </div>
      <div>
        <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Soluciones</h4>
        <ul class="space-y-2">
          <li><a href="<?= BASE_URL ?>taqueria"      class="text-sm text-gray-500 hover:text-white transition-colors">Distribuidora de carne cerca de mí</a></li>
          <li><a href="<?= BASE_URL ?>restaurantes"  class="text-sm text-gray-500 hover:text-white transition-colors">Cortes de carne para restaurantes</a></li>
          <li><a href="<?= BASE_URL ?>restaurantes"  class="text-sm text-gray-500 hover:text-white transition-colors">Software de compras para restaurantes</a></li>
          <li><a href="<?= BASE_URL ?>cedis"         class="text-sm text-white/60 font-semibold">→ Software para CEDIS y carnicerias</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Contacto</h4>
        <ul class="space-y-2">
          <li><span class="text-sm text-gray-500">Querétaro, México</span></li>
          <li><a href="<?= BASE_URL ?>planes" class="text-sm text-gray-500 hover:text-white transition-colors">Ver planes</a></li>
          <li><a href="mailto:<?= htmlspecialchars($contactEmail) ?>" class="text-sm text-gray-500 hover:text-white transition-colors"><?= htmlspecialchars($contactEmail) ?></a></li>
        </ul>
      </div>
    </div>
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-gray-600">
      <span>© <?= date('Y') ?> <?= htmlspecialchars($appName) ?> · Todos los derechos reservados</span>
      <a href="<?= BASE_URL ?>auth/login" class="hover:text-gray-400 transition-colors">Iniciar sesión</a>
    </div>
  </div>
</footer>

<script>
window.addEventListener('scroll', () => {
  document.querySelector('.navbar').classList.toggle('scrolled', window.scrollY > 40);
});
// ── Scroll reveal ──
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// ── Slider ──
(function () {
  const INTERVAL = 5000;
  const slides  = document.querySelectorAll('#hero-slider .slide');
  const dots    = document.querySelectorAll('#slider-dots .slider-dot');
  const bar     = document.getElementById('slider-progress');
  let current   = 0;
  let autoTimer = null;

  function goTo(index) {
    slides[current].classList.remove('active');
    dots[current].classList.remove('active');
    current = (index + slides.length) % slides.length;
    slides[current].classList.add('active');
    dots[current].classList.add('active');
    resetProgress();
  }

  function resetProgress() {
    if (!bar) return;
    bar.classList.remove('running');
    bar.style.transition = 'none';
    bar.style.width = '0%';
    requestAnimationFrame(() => requestAnimationFrame(() => {
      bar.style.transition = '';
      bar.classList.add('running');
    }));
  }

  function startAuto() {
    clearInterval(autoTimer);
    autoTimer = setInterval(() => goTo(current + 1), INTERVAL);
  }

  document.getElementById('slider-prev').addEventListener('click', () => { goTo(current - 1); startAuto(); });
  document.getElementById('slider-next').addEventListener('click', () => { goTo(current + 1); startAuto(); });
  dots.forEach(dot => dot.addEventListener('click', () => { goTo(parseInt(dot.dataset.slide)); startAuto(); }));

  // Swipe support
  let touchStartX = 0;
  const wrap = document.getElementById('hero-slider');
  wrap.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].clientX; }, { passive: true });
  wrap.addEventListener('touchend', e => {
    const diff = touchStartX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) { goTo(diff > 0 ? current + 1 : current - 1); startAuto(); }
  }, { passive: true });

  resetProgress();
  startAuto();
})();
</script>
</body>
</html>
