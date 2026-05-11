<?php
/** @var string $appName */
/** @var string $appLogo */
/** @var string $colorPrimary */
/** @var string $contactEmail */
/** @var array  $planes */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Plataforma de abastecimiento productos cárnicos con visibilidad y trazabilidad a 1 click | <?= htmlspecialchars($appName) ?></title>
  <meta name="description" content="Optimiza compras, entregas, trazabilidad y logística inversa con CarniHub. Ideal para CEDIS de restaurantes, hoteles, taquerías y carnicerías.">
  <meta name="keywords" content="proveedores de carne con crédito, cotización de carne para comedores, precio de carne para restaurantes, carne por mayoreo para hoteles, proveedor de carne para cadenas restauranteras, proveedor de carne con entrega garantizada, proveedor de carne para CEDIS, compra de carne para restaurantes, compra de carne para hoteles, proveedor de carne para taquerías">
  <link rel="canonical" href="<?= BASE_URL ?>carnihub">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Organization",
        "name": "CarniHub",
        "url": "<?= BASE_URL ?>carnihub",
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
            "acceptedAnswer": { "@type": "Answer", "text": "Centralizando pedidos, facturación y reportes desde una sola plataforma como CarniHub, haz una prueba hoy." }
          },
          {
            "@type": "Question",
            "name": "¿Cómo mejorar la logística de alimentos perecederos?",
            "acceptedAnswer": { "@type": "Answer", "text": "Implementando monitoreo de rutas, temperatura y validación digital de entregas." }
          },
          {
            "@type": "Question",
            "name": "¿CarniHub vende carne directamente?",
            "acceptedAnswer": { "@type": "Answer", "text": "Sí. CarniHub cuenta con infraestructura para abastecimiento y distribución de productos cárnicos." }
          },
          {
            "@type": "Question",
            "name": "¿CarniHub funciona para cadenas con múltiples sucursales?",
            "acceptedAnswer": { "@type": "Answer", "text": "Sí. Está diseñado para operaciones multi-sucursal y control centralizado." }
          },
          {
            "@type": "Question",
            "name": "¿Se puede monitorear temperatura y rutas?",
            "acceptedAnswer": { "@type": "Answer", "text": "Sí. CarniHub permite monitoreo operativo y trazabilidad logística." }
          },
          {
            "@type": "Question",
            "name": "¿CarniHub ayuda con devoluciones e incidencias?",
            "acceptedAnswer": { "@type": "Answer", "text": "Sí. Facilita gestión documental y seguimiento operativo." }
          },
          {
            "@type": "Question",
            "name": "¿Cómo reducir mermas y devoluciones en restaurantes?",
            "acceptedAnswer": { "@type": "Answer", "text": "Usando sistemas con trazabilidad, evidencia digital y control logístico." }
          },
          {
            "@type": "Question",
            "name": "¿Qué beneficios tiene la trazabilidad de productos cárnicos?",
            "acceptedAnswer": { "@type": "Answer", "text": "Mejora control sanitario, reduce pérdidas y facilita auditorías." }
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
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px color-mix(in srgb,var(--cp) 50%,transparent); opacity: .92; }
    .btn-outline  { border: 2px solid rgba(255,255,255,.35); color: #fff; transition: background .2s, border-color .2s, transform .2s; }
    .btn-outline:hover { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.7); transform: translateY(-2px); }
    .orb { position:absolute; border-radius:50%; filter:blur(80px); opacity:.2; }
    .reveal { opacity:0; transform:translateY(32px); transition:opacity .7s ease, transform .7s ease; }
    .reveal.visible { opacity:1; transform:translateY(0); }
    .navbar { transition: background .3s, box-shadow .3s; }
    .navbar.scrolled { background: rgba(255,255,255,.97) !important; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
    .navbar.scrolled a:not(.btn-primary) { color: #374151 !important; }
    .navbar.scrolled a:not(.btn-primary):hover { color: #111827 !important; background: rgba(0,0,0,.04) !important; }
    .navbar.scrolled span { color: #374151 !important; }
    .btn-shimmer { position:relative; overflow:hidden; }
    .btn-shimmer::after { content:''; position:absolute; top:0; left:-100%; width:60%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.3),transparent); animation:shimmer 2.5s infinite; }
    @keyframes shimmer { 0%{left:-100%} 100%{left:200%} }
    @keyframes pulse-ring { 0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--cp) 60%,transparent)} 100%{box-shadow:0 0 0 12px transparent} }
    .pulse-badge { animation: pulse-ring 2s infinite; }
    .text-gradient { background:linear-gradient(135deg,#fff 30%,color-mix(in srgb,var(--cp) 80%,#fff)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
    /* Slider */
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
    /* Feat / step cards */
    .feat-card { border:1px solid #e2e8f0; transition:transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s, border-color .25s; }
    .feat-card:hover { transform:translateY(-6px); box-shadow:0 16px 40px rgba(0,0,0,.08); border-color:color-mix(in srgb,var(--cp) 30%,transparent); }
    .feat-icon { background:color-mix(in srgb,var(--cp) 12%,#fff); transition:transform .25s cubic-bezier(.34,1.56,.64,1), background .2s; }
    .feat-card:hover .feat-icon { background:var(--cp); transform:scale(1.15) rotate(-6deg); }
    .feat-card:hover .feat-icon svg { color:#fff; }
    .step-circle { background:var(--cp); transition:transform .3s cubic-bezier(.34,1.56,.64,1), box-shadow .3s; }
    .step-wrap:hover .step-circle { transform:scale(1.12) rotate(8deg); box-shadow:0 10px 30px color-mix(in srgb,var(--cp) 50%,transparent); }
    /* Audience cards */
    .audience-card { display:block; text-decoration:none; border:1px solid #e2e8f0; background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%); transition:transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s, border-color .25s; }
    .audience-card:hover { transform:translateY(-8px); border-color:color-mix(in srgb,var(--cp) 40%,transparent); box-shadow:0 24px 60px rgba(15,23,42,.14); text-decoration:none; }
    .audience-card-featured { border:2px solid var(--cp) !important; transform:scale(1.03); }
    .audience-card-featured:hover { transform:scale(1.03) translateY(-8px); }
    .audience-icon { width:64px; height:64px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:1.75rem; transition:transform .25s cubic-bezier(.34,1.56,.64,1); }
    .audience-card:hover .audience-icon { transform:scale(1.12) rotate(-5deg); }
    .audience-chip { display:inline-flex; font-size:.7rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; padding:.25rem .75rem; border-radius:9999px; background:color-mix(in srgb,var(--cp) 12%,transparent); color:var(--cp); }
    .audience-cta { display:inline-flex; align-items:center; gap:.4rem; font-weight:700; font-size:.875rem; color:var(--cp); transition:gap .2s; }
    .audience-card:hover .audience-cta { gap:.65rem; }
    /* Plan cards */
    .plan-card { transition:transform .3s cubic-bezier(.34,1.56,.64,1), box-shadow .3s; position:relative; overflow:hidden; }
    .plan-card:hover { transform:translateY(-10px); box-shadow:0 30px 70px rgba(0,0,0,.12); }
    .plan-card::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,color-mix(in srgb,var(--cp) 8%,transparent),transparent); opacity:0; transition:opacity .3s; }
    .plan-card:hover::before { opacity:1; }
    .plan-popular { border:2px solid var(--cp) !important; transform:scale(1.03); }
    .plan-popular:hover { transform:scale(1.03) translateY(-10px); }
    /* Banner */
    .banner-primary { background: var(--cp); }
    /* FAQ */
    .faq-item { border-bottom: 1px solid #e5e7eb; }
    .faq-btn { width:100%; text-align:left; padding:1.25rem 0; display:flex; align-items:center; justify-content:space-between; cursor:pointer; background:transparent; border:none; font-weight:600; font-size:.95rem; color:#111827; }
    .faq-body { max-height:0; overflow:hidden; transition:max-height .3s ease; }
    .faq-body.open { max-height:300px; }
    .faq-icon { transition:transform .3s; flex-shrink:0; }
    .faq-icon.open { transform:rotate(45deg); }
    /* Scroll bounce */
    @keyframes bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(8px)} }
    .scroll-arrow { animation: bounce 1.5s ease-in-out infinite; }
  </style>
</head>
<body class="bg-white text-gray-900">

<!-- ══ NAVBAR ══ -->
<nav class="navbar fixed top-0 w-full z-50 bg-transparent">
  <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
    <a href="<?= BASE_URL ?>" class="flex items-center gap-2 no-underline">
      <?php if ($appLogo): ?>
        <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo" class="h-9 object-contain">
      <?php else: ?>
        <span class="text-xl font-black text-white tracking-tight"><?= htmlspecialchars($appName) ?></span>
      <?php endif; ?>
    </a>
    <div class="hidden md:flex items-center gap-1">
      <a href="#compras"    class="text-sm font-medium text-white/70 px-4 py-2 rounded-lg hover:text-white hover:bg-white/10 transition-all">Compras</a>
      <a href="#logistica"  class="text-sm font-medium text-white/70 px-4 py-2 rounded-lg hover:text-white hover:bg-white/10 transition-all">Logística</a>
      <a href="#soluciones" class="text-sm font-medium text-white/70 px-4 py-2 rounded-lg hover:text-white hover:bg-white/10 transition-all">Soluciones</a>
      <a href="#precios"    class="text-sm font-medium text-white/70 px-4 py-2 rounded-lg hover:text-white hover:bg-white/10 transition-all">Precios</a>
    </div>
    <div class="flex items-center gap-2">
      <a href="<?= BASE_URL ?>auth/login" class="text-sm font-semibold text-white/80 px-4 py-2 hover:text-white transition-colors">Iniciar sesión</a>
      <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer text-sm font-bold px-5 py-2.5 rounded-xl">Solicitar demo</a>
    </div>
  </div>
</nav>

<!-- ══ SLIDER HERO ══ -->
<div class="slider-wrap pt-16" id="hero-slider">

  <!-- Slide 1 · alt-img: proveedor de carne para taquerías -->
  <div class="slide active" data-slide="0"
       style="background:radial-gradient(ellipse 80% 60% at 50% -10%,color-mix(in srgb,var(--cp) 30%,transparent),transparent),linear-gradient(160deg,#0a0f1e 0%,#111827 55%,#1a2235 100%)">
    <div class="orb" style="width:500px;height:500px;background:var(--cp);top:-120px;right:-100px;"></div>
    <div class="orb" style="width:350px;height:350px;background:#22c55e;bottom:-80px;left:-60px;"></div>
    <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 relative z-10">
      <div class="max-w-3xl">
        <div class="slide-chip pulse-badge" style="background:color-mix(in srgb,var(--cp) 20%,transparent);border:1px solid color-mix(in srgb,var(--cp) 50%,transparent);color:var(--cp)">
          <span class="w-2 h-2 rounded-full bg-primary animate-pulse inline-block"></span>
          Trazabilidad · Entregas · Control operativo
        </div>
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white leading-tight mb-5">
          Controla entregas,<br>incidencias y trazabilidad<br>
          <span class="text-gradient">desde un solo sistema</span>
        </h2>
        <p class="text-gray-400 text-lg mb-8 max-w-xl leading-relaxed">
          CarniHub centraliza toda la operación de CEDIS, restaurantes, hoteles y taquerías en un ecosistema digital diseñado para operaciones gastronómicas complejas.
        </p>
        <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold px-8 py-4 rounded-xl">Solicitar demostración →</a>
      </div>
    </div>
  </div>

  <!-- Slide 2 · alt-img: proveedor de carne con entrega garantizada -->
  <div class="slide" data-slide="1"
       style="background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(239,68,68,.28),transparent),linear-gradient(160deg,#0a0f1e 0%,#111827 55%,#1e0f0f 100%)">
    <div class="orb" style="width:500px;height:500px;background:#ef4444;top:-120px;right:-100px;"></div>
    <div class="orb" style="width:350px;height:350px;background:var(--cp);bottom:-80px;left:-60px;"></div>
    <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 relative z-10">
      <div class="max-w-3xl">
        <div class="slide-chip" style="background:rgba(239,68,68,.18);border:1px solid rgba(239,68,68,.45);color:#fca5a5">
          <span class="w-2 h-2 rounded-full inline-block" style="background:#fca5a5"></span>
          Logística inversa · Devoluciones · Mermas
        </div>
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white leading-tight mb-5">
          Reduce mermas, devoluciones<br>
          <span style="background:linear-gradient(135deg,#fff 30%,#fca5a5);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">y pérdidas operativas en tus sucursales</span>
        </h2>
        <p class="text-gray-400 text-lg mb-8 max-w-xl leading-relaxed">
          Trazabilidad documental, evidencia digital POD y seguimiento de incidencias para eliminar disputas con proveedores y reducir pérdidas.
        </p>
        <a href="<?= BASE_URL ?>planes/registro" class="inline-block font-bold px-8 py-4 rounded-xl" style="background:#ef4444;color:#fff">Ver gestión de devoluciones →</a>
      </div>
    </div>
  </div>

  <!-- Slide 3 · alt-img: proveedores de carne con crédito -->
  <div class="slide" data-slide="2"
       style="background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(245,158,11,.28),transparent),linear-gradient(160deg,#0a0f1e 0%,#111827 55%,#1e1a10 100%)">
    <div class="orb" style="width:500px;height:500px;background:#f59e0b;top:-120px;right:-100px;"></div>
    <div class="orb" style="width:350px;height:350px;background:var(--cp);bottom:-80px;left:-60px;"></div>
    <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 relative z-10">
      <div class="max-w-3xl">
        <div class="slide-chip" style="background:rgba(245,158,11,.18);border:1px solid rgba(245,158,11,.45);color:#f59e0b">
          <span class="w-2 h-2 rounded-full inline-block" style="background:#f59e0b"></span>
          Crédito · Facturación · Pedidos multi-sucursal
        </div>
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white leading-tight mb-5">
          Centraliza compras, crédito,<br>
          <span style="background:linear-gradient(135deg,#fff 30%,#fcd34d);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">facturación y pedidos multi-sucursal</span>
        </h2>
        <p class="text-gray-400 text-lg mb-8 max-w-xl leading-relaxed">
          Controla precios variables, compras descentralizadas y diferencias de facturación desde una sola plataforma con visibilidad total.
        </p>
        <a href="<?= BASE_URL ?>planes/registro" class="inline-block font-bold px-8 py-4 rounded-xl" style="background:#f59e0b;color:#fff">Ver control de compras →</a>
      </div>
    </div>
  </div>

  <!-- Slide 4 · alt-img: proveedor de carne para cadenas restauranteras -->
  <div class="slide" data-slide="3"
       style="background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(99,102,241,.28),transparent),linear-gradient(160deg,#0a0f1e 0%,#111827 55%,#0e1120 100%)">
    <div class="orb" style="width:500px;height:500px;background:#6366f1;top:-120px;right:-100px;"></div>
    <div class="orb" style="width:350px;height:350px;background:var(--cp);bottom:-80px;left:-60px;"></div>
    <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 relative z-10">
      <div class="max-w-3xl">
        <div class="slide-chip" style="background:rgba(99,102,241,.18);border:1px solid rgba(99,102,241,.45);color:#a5b4fc">
          <span class="w-2 h-2 rounded-full inline-block" style="background:#a5b4fc"></span>
          GPS · IIoT · Cadena de frío
        </div>
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white leading-tight mb-5">
          Monitorea unidades, rutas<br>
          <span style="background:linear-gradient(135deg,#fff 30%,#c7d2fe);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">y cadena de frío en tiempo real</span>
        </h2>
        <p class="text-gray-400 text-lg mb-8 max-w-xl leading-relaxed">
          Rastreo GPS, control de temperatura y visibilidad operativa para logística de alimentos perecederos en cadenas restauranteras y hoteles.
        </p>
        <a href="<?= BASE_URL ?>planes/registro" class="inline-block font-bold px-8 py-4 rounded-xl" style="background:#6366f1;color:#fff">Ver monitoreo en tiempo real →</a>
      </div>
    </div>
  </div>

  <button class="slider-arrow" id="slider-prev" aria-label="Anterior">
    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
  </button>
  <button class="slider-arrow" id="slider-next" aria-label="Siguiente">
    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
    </svg>
  </button>
  <div class="slider-dots" id="slider-dots">
    <button class="slider-dot active" data-slide="0" aria-label="Diapositiva 1"></button>
    <button class="slider-dot" data-slide="1" aria-label="Diapositiva 2"></button>
    <button class="slider-dot" data-slide="2" aria-label="Diapositiva 3"></button>
    <button class="slider-dot" data-slide="3" aria-label="Diapositiva 4"></button>
  </div>
  <div class="slider-progress" id="slider-progress"></div>
</div>

<!-- ══ H1 INTRO ══ -->
<section class="bg-white py-16">
  <div class="max-w-6xl mx-auto px-6 reveal">
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-5">
      <a href="<?= BASE_URL ?>" class="hover:text-gray-600 transition-colors">Inicio</a>
      <span>›</span>
      <span>CarniHub · Plataforma</span>
    </div>
    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-5">
      Plataforma especializada en abastecimiento, trazabilidad y logística para CEDIS de cadenas restauranteras, hoteles, taquerías, comedores industriales
    </h1>
    <p class="text-gray-600 leading-relaxed max-w-3xl mb-5">
      CarniHub ayuda a responsables de CEDIS, cadenas de restaurantes, hoteles, taquerías y carnicerías a optimizar:
      <strong>compras, abastecimiento, logística, devoluciones, entregas, inventarios y control operativo multi-sucursal.</strong>
      Todo desde un ecosistema digital diseñado para operaciones gastronómicas complejas.
    </p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-6 max-w-3xl">
      <?php foreach (['Compras centralizadas','Trazabilidad total','Logística inversa','GPS en tiempo real','Evidencia digital POD','IIoT & mantenimiento','Control multi-sucursal','Cadena de frío'] as $tag): ?>
      <span class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600"><?= htmlspecialchars($tag) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="flex flex-col sm:flex-row gap-3">
      <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold text-base px-8 py-4 rounded-xl text-center">Solicitar demostración →</a>
      <a href="#compras" class="inline-block border-2 border-gray-200 font-semibold text-base px-8 py-4 rounded-xl text-gray-700 hover:border-gray-400 transition-colors text-center">Ver funcionalidades</a>
    </div>
  </div>
</section>

<!-- ══ H2: Compras, crédito y facturación ══ -->
<section id="compras" class="bg-slate-50 py-20">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid md:grid-cols-2 gap-14 items-center">
      <div class="reveal">
        <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Control administrativo</p>
        <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-5">
          Control total de compras, crédito y facturación para restaurantes y CEDIS
        </h2>
        <p class="text-gray-600 mb-5 leading-relaxed">
          Uno de los mayores problemas en operaciones gastronómicas multi-sucursal es la falta de control
          administrativo sobre precios variables, compras descentralizadas, diferencias de facturación,
          crédito con proveedores y pagos y conciliaciones.
        </p>
        <p class="text-gray-600 mb-6 leading-relaxed">
          Con CarniHub obtienes múltiples usuarios que centralizan pedidos, controlan precios, gestionan
          crédito, validan facturación y tienen visibilidad y trazabilidad de cada solicitud. Las compras
          recurrentes se automatizan, reduciendo errores administrativos y mejorando el control financiero.
          Atención especial a taquerías con compras consolidadas, carne en caja por mayoreo y crédito para negocios.
        </p>
        <ul class="space-y-3 mb-8">
          <?php foreach ([
            'Centralizar pedidos multi-sucursal',
            'Controlar precios y crédito con proveedores',
            'Validar facturación y conciliaciones',
            'Automatizar compras recurrentes',
            'Visibilidad y trazabilidad de cada solicitud',
          ] as $item): ?>
          <li class="flex items-center gap-3 text-gray-700">
            <svg class="w-5 h-5 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <?= htmlspecialchars($item) ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold px-7 py-3.5 rounded-xl">Ver control de compras →</a>
      </div>
      <div class="reveal">
        <div class="grid grid-cols-2 gap-4">
          <?php foreach ([
            ['💳','Crédito empresarial','Para taquerías y CEDIS'],
            ['📄','Facturación validada','Sin diferencias ni disputas'],
            ['🔄','Compras recurrentes','Automatizadas al 100%'],
            ['📊','Conciliación','Control financiero total'],
          ] as [$icon,$title,$sub]): ?>
          <div class="bg-white rounded-2xl p-6 border border-gray-100 feat-card text-center">
            <div class="text-3xl mb-2"><?= $icon ?></div>
            <div class="font-extrabold text-gray-900 text-sm mb-0.5"><?= htmlspecialchars($title) ?></div>
            <div class="text-xs text-gray-400"><?= htmlspecialchars($sub) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ BANNER 1 · alt-img: proveedor de carne para taquerías ══ -->
<section class="banner-primary py-14">
  <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6 reveal">
    <div>
      <p class="text-white/70 text-sm font-medium mb-1">Distribuidora de carne</p>
      <h3 class="text-2xl md:text-3xl font-extrabold text-white">Conoce a la distribuidora de carne más cerca de ti.</h3>
    </div>
    <a href="<?= BASE_URL ?>distribuidora-carne-cerca-de-mi"
       class="flex-shrink-0 bg-white font-bold px-8 py-4 rounded-xl text-sm hover:bg-gray-100 transition-colors whitespace-nowrap"
       style="color:var(--cp)">
      Distribuidora de carne cerca de mí →
    </a>
  </div>
</section>

<!-- ══ H2: Mermas y devoluciones ══ -->
<section id="devoluciones" class="bg-white py-20">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid md:grid-cols-2 gap-14 items-center">
      <div class="order-2 md:order-1 reveal">
        <div class="grid grid-cols-2 gap-4">
          <?php foreach ([
            ['📦','POD digital','Evidencia en cada entrega'],
            ['↩️','Logística inversa','Devoluciones trazadas'],
            ['📋','Incidencias','Seguimiento completo'],
            ['✅','Auditorías','Documentación verificable'],
          ] as [$icon,$title,$sub]): ?>
          <div class="bg-slate-50 rounded-2xl p-6 border border-gray-100 feat-card text-center">
            <div class="text-3xl mb-2"><?= $icon ?></div>
            <div class="font-extrabold text-gray-900 text-sm mb-0.5"><?= htmlspecialchars($title) ?></div>
            <div class="text-xs text-gray-400"><?= htmlspecialchars($sub) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="order-1 md:order-2 reveal">
        <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Logística inversa</p>
        <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-5">
          Gestión de mermas, devoluciones y logística inversa alimentaria
        </h2>
        <p class="text-gray-600 mb-5 leading-relaxed">
          La falta de trazabilidad y control operativo provoca mermas, productos dañados, devoluciones tardías,
          pérdidas económicas y conflictos con proveedores.
          Con CarniHub mejoras la gestión de devoluciones de alimentos, logística inversa, trazabilidad documental,
          seguimiento de incidencias y validación de entregas.
        </p>

        <h3 class="font-extrabold text-gray-900 text-lg mb-3">Evidencia digital POD para validar entregas y devoluciones</h3>
        <p class="text-gray-600 mb-6 leading-relaxed">
          La evidencia digital POD permite documentar entregas, validar recepción, registrar incidencias, reducir
          disputas y mejorar auditorías internas. Ideal para operaciones con múltiples sucursales y rutas diarias.
        </p>
        <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold px-7 py-3.5 rounded-xl">Ver trazabilidad POD →</a>
      </div>
    </div>
  </div>
</section>

<!-- ══ H2: Monitoreo rutas y cadena de frío ══ -->
<section id="logistica" class="py-20" style="background:linear-gradient(180deg,#f8fafc,#f1f5f9)">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid md:grid-cols-2 gap-14 items-center">
      <div class="reveal">
        <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Logística en tiempo real</p>
        <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-5">
          Monitoreo de rutas, unidades y cadena de frío en tiempo real
        </h2>
        <p class="text-gray-600 mb-5 leading-relaxed">
          La logística de alimentos perecederos requiere monitoreo constante, trazabilidad, control de temperatura,
          seguimiento de rutas y visibilidad operativa. A menos de 2 clics obtendrás:
        </p>
        <ul class="space-y-3 mb-6">
          <?php foreach ([
            'Rastreo de transporte de alimentos GPS',
            'Monitoreo de temperatura en transporte',
            'Control de unidades de reparto',
            'Trazabilidad de productos cárnicos',
            'Validación operativa por ruta',
          ] as $item): ?>
          <li class="flex items-center gap-3 text-gray-700">
            <svg class="w-5 h-5 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <?= htmlspecialchars($item) ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <p class="text-gray-600 mb-6 text-sm leading-relaxed">
          Especializado para hoteles, chefs ejecutivos, proveedores certificados TIF y operaciones con altos
          requerimientos de trazabilidad y control sanitario.
        </p>
        <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold px-7 py-3.5 rounded-xl">Ver monitoreo GPS →</a>
      </div>
      <div class="reveal">
        <div class="grid grid-cols-2 gap-4">
          <?php foreach ([
            ['📍','GPS en tiempo real','Rutas y unidades'],
            ['🌡️','Cadena de frío','Temperatura monitoreada'],
            ['🏨','Hoteles y chefs','Proveedores TIF'],
            ['✅','Sanitario','Cumplimiento verificado'],
          ] as [$icon,$title,$sub]): ?>
          <div class="bg-white rounded-2xl p-6 border border-gray-100 feat-card text-center">
            <div class="text-3xl mb-2"><?= $icon ?></div>
            <div class="font-extrabold text-gray-900 text-sm mb-0.5"><?= htmlspecialchars($title) ?></div>
            <div class="text-xs text-gray-400"><?= htmlspecialchars($sub) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ BANNER 2 · alt-img: precio de carne para restaurantes ══ -->
<section class="banner-primary py-14">
  <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6 reveal">
    <div>
      <p class="text-white/70 text-sm font-medium mb-1">Restaurantes &amp; Hoteles</p>
      <h3 class="text-2xl md:text-3xl font-extrabold text-white">Cortes de carne para restaurantes con trazabilidad y entrega garantizada.</h3>
    </div>
    <a href="<?= BASE_URL ?>carnihub/cortes-de-carne-para-restaurantes"
       class="flex-shrink-0 bg-white font-bold px-8 py-4 rounded-xl text-sm hover:bg-gray-100 transition-colors whitespace-nowrap"
       style="color:var(--cp)">
      Ver solución restaurantes →
    </a>
  </div>
</section>

<!-- ══ H2: Pedidos multi-sucursal e inventarios ══ -->
<section id="soluciones" class="bg-white py-20">
  <div class="max-w-6xl mx-auto px-6">
    <div class="text-center mb-16 reveal">
      <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Operaciones multi-sucursal</p>
      <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Pedidos multi-sucursal, inventarios y control operativo</h2>
      <p class="text-gray-500 max-w-xl mx-auto">Los grupos restauranteros necesitan pedidos centralizados, control de inventarios, historial de consumo, seguimiento por sucursal y control de entregas.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-16">
      <?php
      $feats = [
        ['Pedidos multi-sucursal','Centraliza y automatiza pedidos de todas tus sucursales desde un solo panel. Elimina WhatsApp y llamadas.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>'],
        ['Control de inventarios','Registra entradas y salidas con alertas automáticas al llegar al mínimo. Sin sorpresas operativas.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>'],
        ['Incidencias logísticas','Registra y da seguimiento a cada incidencia operativa. Historial completo por ruta y repartidor.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>'],
        ['Análisis de consumo','Historial de consumo por sucursal, período y producto. Información clave para decisiones de compra.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zm6.75-4.5c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zm6.75-4.5c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>'],
        ['Reducir compras urgentes','Automatiza reórdenes y evita quiebres de stock. Menos compras de emergencia, mejor margen operativo.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
        ['Trazabilidad total','Evidencia digital en cada etapa: pedido, despacho, entrega, devolución. Cumplimiento documental completo.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>'],
      ];
      foreach ($feats as $i => [$titulo,$desc,$svg]): ?>
      <div class="feat-card bg-white rounded-2xl p-7 reveal" style="transition-delay:<?= $i * 80 ?>ms">
        <div class="feat-icon w-12 h-12 rounded-xl flex items-center justify-center mb-5">
          <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><?= $svg ?></svg>
        </div>
        <h3 class="font-bold text-gray-900 text-base mb-2"><?= htmlspecialchars($titulo) ?></h3>
        <p class="text-gray-500 text-sm leading-relaxed"><?= htmlspecialchars($desc) ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- H3: Reportes -->
    <div class="bg-slate-50 rounded-3xl p-10 reveal">
      <div class="grid md:grid-cols-2 gap-10 items-center">
        <div>
          <h3 class="text-2xl font-extrabold text-gray-900 mb-4">Reportes de consumo y rendimiento operativo por sucursal</h3>
          <p class="text-gray-600 leading-relaxed mb-4">
            CarniHub genera visibilidad sobre consumo de productos, comportamiento operativo, frecuencia de compras,
            incidencias recurrentes y eficiencia logística. Información clave para responsables de CEDIS y operaciones.
          </p>
          <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold px-7 py-3.5 rounded-xl">Ver reportes →</a>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <?php foreach ([
            ['📊','Consumo por sucursal'],['📈','Tendencias operativas'],['🔁','Frecuencia de compras'],['⚠️','Incidencias recurrentes'],
          ] as [$icon,$lab]): ?>
          <div class="bg-white rounded-xl p-4 border border-gray-100 text-center">
            <div class="text-2xl mb-1"><?= $icon ?></div>
            <div class="text-xs font-semibold text-gray-700"><?= htmlspecialchars($lab) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ H2: IIoT ══ -->
<section class="py-20" style="background:linear-gradient(180deg,#f1f5f9,#e2e8f0)">
  <div class="max-w-6xl mx-auto px-6">
    <div class="grid md:grid-cols-2 gap-14 items-center">
      <div class="reveal">
        <div class="grid grid-cols-2 gap-4">
          <?php foreach ([
            ['🌡️','Cámaras de refrigeración','Monitoreo continuo'],
            ['🚚','Unidades de reparto','Control de activos'],
            ['🔧','Mantenimiento preventivo','Sin paros inesperados'],
            ['📡','IIoT integrado','Visibilidad operativa total'],
          ] as [$icon,$title,$sub]): ?>
          <div class="bg-white rounded-2xl p-6 border border-gray-100 feat-card text-center">
            <div class="text-3xl mb-2"><?= $icon ?></div>
            <div class="font-extrabold text-gray-900 text-sm mb-0.5"><?= htmlspecialchars($title) ?></div>
            <div class="text-xs text-gray-400"><?= htmlspecialchars($sub) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="reveal">
        <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Tecnología operativa</p>
        <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-5">
          IIoT y mantenimiento para instalaciones, almacenes y unidades
        </h2>
        <p class="text-gray-600 mb-5 leading-relaxed">
          Las operaciones modernas requieren visibilidad completa sobre almacenes, cámaras de refrigeración,
          unidades de reparto, mantenimiento preventivo e incidencias técnicas.
          CarniHub integra capacidades de IIoT a unidades de entrega, almacenes y cadena de frío para:
        </p>
        <ul class="space-y-3 mb-6">
          <?php foreach ([
            'Monitoreo operativo en tiempo real',
            'Mantenimiento preventivo programado',
            'Control de activos y equipos',
            'Visibilidad de rutas, entregas y devoluciones',
            'Reducción de riesgos y continuidad del servicio',
          ] as $item): ?>
          <li class="flex items-center gap-3 text-gray-700">
            <svg class="w-5 h-5 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <?= htmlspecialchars($item) ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold px-7 py-3.5 rounded-xl">Ver IIoT y mantenimiento →</a>
      </div>
    </div>
  </div>
</section>

<!-- ══ BANNER 3 · alt-img: carne por mayoreo para hoteles ══ -->
<section style="background:linear-gradient(135deg,#0a0f1e 0%,#111827 100%)" class="py-16">
  <div class="max-w-6xl mx-auto px-6 text-center reveal">
    <p class="text-xs font-bold uppercase tracking-widest text-primary mb-4">Multi-sucursal</p>
    <h3 class="text-2xl md:text-3xl font-extrabold text-white mb-4">
      Diseñado para cadenas gastronómicas<br class="hidden md:block"> con múltiples puntos de operación.
    </h3>
    <p class="text-gray-400 mb-8 max-w-2xl mx-auto">
      Restaurantes, hoteles, taquerías, hospitales, carnicerías y comedores industriales operan con
      CarniHub para centralizar abastecimiento, logística y control operativo.
    </p>
    <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer inline-block font-bold px-10 py-4 rounded-2xl">Solicitar demo gratis →</a>
  </div>
</section>

<!-- ══ SELECTOR DE AUDIENCIA ══ -->
<section class="bg-slate-50 py-24">
  <div class="max-w-6xl mx-auto px-6">
    <div class="text-center mb-14 reveal">
      <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">¿Qué tipo de negocio eres?</p>
      <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Elige tu experiencia</h2>
      <p class="text-gray-500 max-w-2xl mx-auto">Tenemos una solución diseñada específicamente para tu operación. Selecciona y descubre cómo CarniHub transforma tu negocio.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
      <!-- Taquerías -->
      <a href="<?= BASE_URL ?>distribuidora-carne-cerca-de-mi"
         class="audience-card rounded-3xl p-8 reveal" style="transition-delay:0ms">
        <div class="audience-icon mb-6" style="background:color-mix(in srgb,#f97316 12%,transparent)">🌮</div>
        <span class="audience-chip mb-4 inline-block">Taquerías</span>
        <h3 class="text-xl font-extrabold text-gray-900 mt-3 mb-3">Distribuidora de carne cerca de mí</h3>
        <p class="text-sm text-gray-500 leading-relaxed mb-6">
          Encuentra proveedores confiables para taquerías, compra carne por mayoreo, obtén crédito y entrega garantizada.
        </p>
        <ul class="space-y-2.5 mb-8">
          <?php foreach (['Precio de bistec por mayoreo','Pastor preparado certificado','Carne a domicilio para negocio','Crédito empresarial disponible'] as $item): ?>
          <li class="flex items-center gap-2.5 text-sm text-gray-600">
            <svg class="w-4 h-4 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <?= htmlspecialchars($item) ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <span class="audience-cta">
          Ver mi solución
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
          </svg>
        </span>
      </a>

      <!-- Restaurantes (destacado) -->
      <a href="<?= BASE_URL ?>carnihub/cortes-de-carne-para-restaurantes"
         class="audience-card audience-card-featured rounded-3xl p-8 reveal relative" style="transition-delay:100ms">
        <div class="absolute top-5 right-5">
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold text-white" style="background:var(--cp)">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            Premium
          </span>
        </div>
        <div class="audience-icon mb-6" style="background:color-mix(in srgb,var(--cp) 12%,transparent)">🍽️</div>
        <span class="audience-chip mb-4 inline-block">Restaurantes &amp; Hoteles</span>
        <h3 class="text-xl font-extrabold text-gray-900 mt-3 mb-3">Cortes de carne premium con trazabilidad</h3>
        <p class="text-sm text-gray-500 leading-relaxed mb-6">
          Trabaja con proveedores certificados TIF, controla calidad, temperatura y entregas desde un solo lugar.
        </p>
        <ul class="space-y-2.5 mb-8">
          <?php foreach (['Proveedor certificado TIF','Trazabilidad de productos cárnicos','Evidencia digital POD','Reportes de consumo por sucursal'] as $item): ?>
          <li class="flex items-center gap-2.5 text-sm text-gray-600">
            <svg class="w-4 h-4 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <?= htmlspecialchars($item) ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <span class="audience-cta">
          Ver mi solución
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
          </svg>
        </span>
      </a>
    </div>
  </div>
</section>

<!-- ══ QUIÉNES USAN CARNIHUB ══ -->
<section class="bg-white py-16">
  <div class="max-w-5xl mx-auto px-6 text-center reveal">
    <p class="text-xs font-bold uppercase tracking-widest text-primary mb-4">¿Qué tipo de negocios usan CarniHub?</p>
    <div class="flex flex-wrap gap-3 justify-center mb-10">
      <?php foreach (['Restaurantes','Hoteles','Taquerías','Hospitales','Carnicerías','Comedores industriales'] as $seg): ?>
      <span class="px-4 py-2 rounded-full text-sm font-semibold bg-slate-100 text-slate-700"><?= htmlspecialchars($seg) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <?php foreach ([
        ['Mermas y devoluciones','Trazabilidad · POD digital · Incidencias'],
        ['Facturación y crédito','Control admin · Compras recurrentes · Conciliación'],
        ['Personal y entregas','Validación de rutas · Evidencias · Inventarios'],
        ['IIoT y mantenimiento','Monitoreo · Activos · Cadena de frío'],
      ] as [$t,$d]): ?>
      <div class="bg-slate-50 rounded-2xl p-5 border border-gray-100 text-left">
        <div class="font-extrabold text-sm text-gray-900 mb-1"><?= htmlspecialchars($t) ?></div>
        <div class="text-xs text-gray-500 leading-relaxed"><?= htmlspecialchars($d) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ PRECIOS ══ -->
<section id="precios" class="bg-slate-50 py-24">
  <div class="max-w-6xl mx-auto px-6">
    <div class="text-center mb-16 reveal">
      <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Precios</p>
      <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Planes para cada negocio</h2>
      <p class="text-gray-500 max-w-xl mx-auto">Sin contratos ni permanencia. Cancela cuando quieras.</p>
    </div>

    <?php if (!empty($planes)): ?>
    <div class="grid grid-cols-1 md:grid-cols-<?= min(count($planes), 3) ?> gap-6 items-start">
      <?php
      $midIndex = (int)floor(count($planes) / 2);
      foreach ($planes as $i => $plan):
        $popular = ($i === $midIndex);
        $features = [];
        if (!empty($plan['features'])) {
          $features = is_array($plan['features']) ? $plan['features'] : json_decode($plan['features'], true) ?? [];
        }
      ?>
      <div class="plan-card bg-white rounded-2xl p-8 border border-gray-200 shadow-sm reveal <?= $popular ? 'plan-popular' : '' ?>"
           style="transition-delay:<?= $i*100 ?>ms">
        <?php if ($popular): ?>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold text-white mb-4" style="background:var(--cp)">
          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
          Más popular
        </div>
        <?php endif; ?>
        <h3 class="text-xl font-extrabold text-gray-900 mb-1"><?= htmlspecialchars($plan['nombre']) ?></h3>
        <p class="text-sm text-gray-400 mb-6">Plan <?= strtolower(htmlspecialchars($plan['nombre'])) ?></p>
        <div class="mb-6">
          <div class="flex items-end gap-1 mb-1">
            <span class="text-4xl font-black text-gray-900">$<?= number_format($plan['precio_mensual'], 0, '.', ',') ?></span>
            <span class="text-gray-400 mb-1.5">MXN/mes</span>
          </div>
          <?php if (!empty($plan['precio_anual'])): ?>
          <div class="text-sm text-green-600 font-medium">
            o $<?= number_format($plan['precio_anual'], 0, '.', ',') ?> MXN/año
            <span class="text-xs text-green-500">(ahorra <?= round((1 - $plan['precio_anual'] / ($plan['precio_mensual'] * 12)) * 100) ?>%)</span>
          </div>
          <?php endif; ?>
        </div>
        <a href="<?= BASE_URL ?>planes/registro?plan=<?= urlencode($plan['slug']) ?>&ciclo=mensual"
           class="block text-center font-bold py-3.5 rounded-xl mb-8 transition-all <?= $popular ? 'btn-primary btn-shimmer' : 'border-2 border-gray-200 text-gray-700 hover:border-primary hover:text-primary' ?>">
          Comenzar ahora
        </a>
        <?php if (!empty($features)): ?>
        <ul class="space-y-3">
          <?php foreach (array_slice($features, 0, 6) as $f): ?>
          <li class="flex items-start gap-3 text-sm text-gray-600">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <?= htmlspecialchars($f) ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <ul class="space-y-3">
          <?php if ($plan['max_usuarios'] > 0): ?>
          <li class="flex items-center gap-3 text-sm text-gray-600">
            <svg class="w-5 h-5 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            Hasta <?= $plan['max_usuarios'] ?> usuarios
          </li>
          <?php endif; ?>
          <?php if ($plan['max_productos'] > 0): ?>
          <li class="flex items-center gap-3 text-sm text-gray-600">
            <svg class="w-5 h-5 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            Hasta <?= $plan['max_productos'] ?> productos
          </li>
          <?php endif; ?>
          <?php if ($plan['max_sucursales'] > 0): ?>
          <li class="flex items-center gap-3 text-sm text-gray-600">
            <svg class="w-5 h-5 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            Hasta <?= $plan['max_sucursales'] ?> sucursales
          </li>
          <?php endif; ?>
          <li class="flex items-center gap-3 text-sm text-gray-600">
            <svg class="w-5 h-5 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            GPS repartidores
          </li>
          <li class="flex items-center gap-3 text-sm text-gray-600">
            <svg class="w-5 h-5 flex-shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            Soporte incluido
          </li>
        </ul>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center">
      <a href="<?= BASE_URL ?>planes" class="btn-primary btn-shimmer inline-block font-bold text-base px-10 py-4 rounded-xl">
        Ver planes y precios →
      </a>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ══ FAQ ══ -->
<section class="bg-white py-20">
  <div class="max-w-3xl mx-auto px-6">
    <div class="text-center mb-12 reveal">
      <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Preguntas frecuentes</p>
      <h2 class="text-3xl font-extrabold text-gray-900">Resolvemos tus dudas</h2>
    </div>
    <div class="reveal" id="faq-list">
      <?php
      $faqs = [
        ['¿Cómo controlar compras multi-sucursal en restaurantes?',
         'Centralizando pedidos, facturación y reportes desde una sola plataforma como CarniHub. Haz una prueba hoy.'],
        ['¿Cómo mejorar la logística de alimentos perecederos?',
         'Implementando monitoreo de rutas, temperatura y validación digital de entregas.'],
        ['¿CarniHub vende carne directamente?',
         'Sí. CarniHub cuenta con infraestructura para abastecimiento y distribución de productos cárnicos.'],
        ['¿CarniHub funciona para cadenas con múltiples sucursales?',
         'Sí. Está diseñado para operaciones multi-sucursal y control centralizado.'],
        ['¿Se puede monitorear temperatura y rutas?',
         'Sí. CarniHub permite monitoreo operativo y trazabilidad logística en tiempo real.'],
        ['¿CarniHub ayuda con devoluciones e incidencias?',
         'Sí. Facilita gestión documental, evidencia POD y seguimiento operativo completo.'],
        ['¿Cómo reducir mermas y devoluciones en restaurantes?',
         'Usando sistemas con trazabilidad, evidencia digital y control logístico como CarniHub.'],
        ['¿Qué beneficios tiene la trazabilidad de productos cárnicos?',
         'Mejora control sanitario, reduce pérdidas y facilita auditorías internas y con proveedores.'],
      ];
      foreach ($faqs as $j => [$q,$a]): ?>
      <div class="faq-item">
        <button class="faq-btn" onclick="toggleFaq(this)" type="button">
          <span><?= htmlspecialchars($q) ?></span>
          <svg class="faq-icon w-5 h-5 text-primary flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
        </button>
        <div class="faq-body">
          <p class="text-gray-600 text-sm leading-relaxed pb-5"><?= htmlspecialchars($a) ?></p>
        </div>
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
      <span style="background:linear-gradient(135deg,#fff 30%,color-mix(in srgb,var(--cp) 80%,#fff));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">logística y trazabilidad</span><br>
      <span class="text-white text-3xl">en tus sucursales</span>
    </h2>
    <p class="text-gray-400 text-lg mb-10 max-w-xl mx-auto leading-relaxed">
      Solicita una demostración de CarniHub y comprueba el impacto en tu operación.
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
      <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer font-bold text-lg px-10 py-4 rounded-2xl">Solicitar demo →</a>
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
          <li><a href="<?= BASE_URL ?>distribuidora-carne-cerca-de-mi"            class="text-sm text-gray-500 hover:text-white transition-colors">Distribuidora de carne cerca de mí</a></li>
          <li><a href="<?= BASE_URL ?>carnihub/cortes-de-carne-para-restaurantes" class="text-sm text-gray-500 hover:text-white transition-colors">Cortes de carne para restaurantes</a></li>
          <li><a href="<?= BASE_URL ?>carnihub/cortes-de-carne-para-restaurantes" class="text-sm text-gray-500 hover:text-white transition-colors">Software de compras para restaurantes</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Contacto</h4>
        <ul class="space-y-2">
          <li><span class="text-sm text-gray-500">Querétaro, México</span></li>
          <li><a href="<?= BASE_URL ?>carnihub" class="text-sm text-gray-500 hover:text-white transition-colors"><?= rtrim(BASE_URL, '/') ?>/carnihub</a></li>
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
// Navbar scroll
window.addEventListener('scroll', () => {
  document.querySelector('.navbar').classList.toggle('scrolled', window.scrollY > 40);
});

// Reveal
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// FAQ accordion
function toggleFaq(btn) {
  const body = btn.nextElementSibling;
  const icon = btn.querySelector('.faq-icon');
  const open = body.classList.contains('open');
  document.querySelectorAll('.faq-body').forEach(b => b.classList.remove('open'));
  document.querySelectorAll('.faq-icon').forEach(i => i.classList.remove('open'));
  if (!open) { body.classList.add('open'); icon.classList.add('open'); }
}

// Slider
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
