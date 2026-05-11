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
            "acceptedAnswer": { "@type": "Answer", "text": "Centralizando pedidos, facturación y reportes desde una sola plataforma como CarniHub." }
          },
          {
            "@type": "Question",
            "name": "¿Cómo mejorar la logística de alimentos perecederos?",
            "acceptedAnswer": { "@type": "Answer", "text": "Implementando monitoreo de rutas, temperatura y validación digital de entregas." }
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
    /* Feat cards */
    .feat-card { border:1px solid #e2e8f0; transition:transform .25s cubic-bezier(.34,1.56,.64,1), box-shadow .25s, border-color .25s; }
    .feat-card:hover { transform:translateY(-6px); box-shadow:0 16px 40px rgba(0,0,0,.08); border-color:color-mix(in srgb,var(--cp) 30%,transparent); }
    .feat-icon { background:color-mix(in srgb,var(--cp) 12%,#fff); transition:transform .25s cubic-bezier(.34,1.56,.64,1), background .2s; }
    .feat-card:hover .feat-icon { background:var(--cp); transform:scale(1.15) rotate(-6deg); }
    .feat-card:hover .feat-icon svg { color:#fff; }
    /* Step */
    .step-circle { background:var(--cp); transition:transform .3s cubic-bezier(.34,1.56,.64,1), box-shadow .3s; }
    .step-wrap:hover .step-circle { transform:scale(1.12) rotate(8deg); box-shadow:0 10px 30px color-mix(in srgb,var(--cp) 50%,transparent); }
    /* Scroll */
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
      <a href="#soluciones" class="text-sm font-medium text-white/70 px-4 py-2 rounded-lg hover:text-white hover:bg-white/10 transition-all">Soluciones</a>
      <a href="#funciones"  class="text-sm font-medium text-white/70 px-4 py-2 rounded-lg hover:text-white hover:bg-white/10 transition-all">Funciones</a>
      <a href="#precios"    class="text-sm font-medium text-white/70 px-4 py-2 rounded-lg hover:text-white hover:bg-white/10 transition-all">Precios</a>
    </div>
    <div class="flex items-center gap-2">
      <a href="<?= BASE_URL ?>auth/login" class="text-sm font-semibold text-white/80 px-4 py-2 hover:text-white transition-colors">Iniciar sesión</a>
      <a href="<?= BASE_URL ?>planes/registro" class="btn-primary btn-shimmer text-sm font-bold px-5 py-2.5 rounded-xl">Solicitar demo</a>
    </div>
  </div>
</nav>

<!-- ══ HERO ══ -->
<section class="hero-bg relative min-h-screen flex flex-col justify-center overflow-hidden pt-16">
  <div class="orb" style="width:500px;height:500px;background:var(--cp);top:-120px;right:-100px;"></div>
  <div class="orb" style="width:350px;height:350px;background:#6366f1;bottom:-80px;left:-60px;"></div>

  <div class="max-w-6xl mx-auto px-6 py-24 md:py-32 text-center relative z-10">
    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-8 pulse-badge"
         style="background:color-mix(in srgb,var(--cp) 20%,transparent);border:1px solid color-mix(in srgb,var(--cp) 50%,transparent)">
      <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
      <span class="text-xs font-bold uppercase tracking-widest text-primary">Plataforma SaaS · Productores de carne</span>
    </div>

    <h1 class="text-5xl md:text-6xl font-black leading-[1.1] mb-6 max-w-4xl mx-auto">
      <span class="text-gradient">Plataforma especializada</span>
      <br>
      <span class="text-white">en abastecimiento, trazabilidad</span>
      <br>
      <span class="text-white text-4xl md:text-5xl">y logística para cadenas gastronómicas</span>
    </h1>

    <p class="text-gray-400 text-lg leading-relaxed mb-10 max-w-2xl mx-auto">
      Centraliza compras, pedidos, entregas, crédito, devoluciones y monitoreo operativo desde un solo ecosistema digital.
    </p>

    <div class="flex flex-col sm:flex-row gap-3 justify-center mb-16">
      <a href="<?= BASE_URL ?>planes/registro"
         class="btn-primary btn-shimmer font-bold text-base px-8 py-4 rounded-xl text-center">
        Solicitar demostración →
      </a>
      <a href="#soluciones"
         class="btn-outline font-semibold text-base px-8 py-4 rounded-xl text-center">
        Explorar soluciones
      </a>
    </div>

    <!-- Stats -->
    <div class="flex gap-10 flex-wrap justify-center">
      <?php foreach ([
        ['+500', 'Pedidos conectados'],
        ['100%', 'Trazabilidad'],
        ['GPS',  'Tiempo real'],
        ['Crédito', 'Empresarial'],
      ] as [$n, $l]): ?>
      <div class="text-center">
        <div class="text-2xl font-black text-white"><?= $n ?></div>
        <div class="text-xs text-gray-500 mt-0.5"><?= $l ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="absolute bottom-10 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 opacity-40">
    <span class="text-white text-xs tracking-widest uppercase">Descubrir</span>
    <svg class="scroll-arrow w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
    </svg>
  </div>
</section>

<!-- ══ SELECTOR DE SOLUCIONES ══ -->
<section id="soluciones" class="bg-slate-50 py-24">
  <div class="max-w-6xl mx-auto px-6">
    <div class="text-center mb-14 reveal">
      <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">¿Qué tipo de negocio eres?</p>
      <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Elige tu experiencia</h2>
      <p class="text-gray-500 max-w-2xl mx-auto">
        Tenemos una solución diseñada específicamente para tu operación.
        Selecciona y descubre cómo CarniHub transforma tu negocio.
      </p>
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
          <?php foreach ([
            'Precio de bistec por mayoreo',
            'Pastor preparado certificado',
            'Carne a domicilio para negocio',
            'Crédito empresarial disponible',
          ] as $item): ?>
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
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold text-white"
                style="background:var(--cp)">
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
          <?php foreach ([
            'Proveedor certificado TIF',
            'Trazabilidad de productos cárnicos',
            'Evidencia digital POD',
            'Reportes de consumo por sucursal',
          ] as $item): ?>
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

<!-- ══ FUNCIONES ══ -->
<section id="funciones" class="bg-white py-24">
  <div class="max-w-6xl mx-auto px-6">
    <div class="text-center mb-16 reveal">
      <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Funcionalidades</p>
      <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Todo lo que necesitas para operar</h2>
      <p class="text-gray-500 max-w-xl mx-auto">Diseñado específicamente para productores y distribuidores de carne en México.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php
      $feats = [
        ['Pedidos en línea',       'Tus clientes hacen pedidos desde su portal sin llamadas ni WhatsApp. Catálogo siempre actualizado.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>'],
        ['GPS en tiempo real',     'Rastrea repartidores con integración GPS. Tus clientes ven exactamente dónde está su entrega.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>'],
        ['Control de inventario',  'Registra entradas y salidas de stock con alertas automáticas al llegar al mínimo.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>'],
        ['Trazabilidad total',     'Evidencia digital POD, seguimiento de incidencias y control documental para cada entrega.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>'],
        ['Reportes detallados',    'Ventas por período, movimientos de inventario y desempeño de repartidores en un solo lugar.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zm6.75-4.5c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zm6.75-4.5c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>'],
        ['SaaS sin contratos',     'Paga mensual o anual. Sin permanencia. Actualiza o cancela tu plan cuando lo necesites.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>'],
      ];
      foreach ($feats as $i => [$titulo,$desc,$svg]): ?>
      <div class="feat-card bg-white rounded-2xl p-7 reveal" style="transition-delay:<?= $i * 80 ?>ms">
        <div class="feat-icon w-12 h-12 rounded-xl flex items-center justify-center mb-5">
          <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <?= $svg ?>
          </svg>
        </div>
        <h3 class="font-bold text-gray-900 text-base mb-2"><?= htmlspecialchars($titulo) ?></h3>
        <p class="text-gray-500 text-sm leading-relaxed"><?= htmlspecialchars($desc) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ CÓMO FUNCIONA ══ -->
<section class="py-24" style="background:linear-gradient(180deg,#f8fafc,#f1f5f9)">
  <div class="max-w-5xl mx-auto px-6">
    <div class="text-center mb-16 reveal">
      <p class="text-xs font-bold uppercase tracking-widest text-primary mb-3">Proceso</p>
      <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">¿Cómo funciona?</h2>
      <p class="text-gray-500">Empieza a operar en minutos, no en semanas.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-10 relative">
      <div class="hidden md:block absolute top-10 left-[calc(16.66%+24px)] right-[calc(16.66%+24px)] h-px"
           style="background:linear-gradient(90deg,var(--cp),#6366f1,var(--cp))"></div>

      <?php foreach ([
        ['1','Te registras',         'Elige tu plan, realiza el pago con PayPal y recibe acceso inmediato a tu panel de administración.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>'],
        ['2','Configuras tu empresa','Carga tu catálogo, registra clientes, supervisores y repartidores. Listo en minutos.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>'],
        ['3','Tus clientes piden',   'Cada cliente entra a su portal, ve el catálogo con sus precios personalizados y hace pedidos al instante.',
         '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>'],
      ] as $i => [$n,$t,$d,$s]): ?>
      <div class="step-wrap text-center relative z-10 reveal" style="transition-delay:<?= $i*120 ?>ms">
        <div class="step-circle w-20 h-20 rounded-full text-white flex items-center justify-center mx-auto mb-5 shadow-xl">
          <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><?= $s ?></svg>
        </div>
        <div class="text-xs font-bold text-primary mb-2 uppercase tracking-widest">Paso <?= $n ?></div>
        <h3 class="font-bold text-gray-900 text-lg mb-3"><?= htmlspecialchars($t) ?></h3>
        <p class="text-gray-500 text-sm leading-relaxed"><?= htmlspecialchars($d) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ PRECIOS ══ -->
<section id="precios" class="bg-white py-24">
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
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold text-white mb-4"
             style="background:var(--cp)">
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

<!-- ══ CTA FINAL ══ -->
<section class="py-24 relative overflow-hidden" style="background:linear-gradient(135deg,#0a0f1e 0%,#111827 60%,#1a2235 100%)">
  <div class="orb" style="width:600px;height:600px;background:var(--cp);top:-200px;left:50%;transform:translateX(-50%);opacity:.12;filter:blur(100px);"></div>
  <div class="max-w-3xl mx-auto px-6 text-center relative z-10 reveal">
    <p class="text-xs font-bold uppercase tracking-widest text-primary mb-4">Empieza hoy</p>
    <h2 class="text-4xl md:text-5xl font-black text-white mb-6 leading-tight">
      Optimiza abastecimiento,<br>
      <span class="text-gradient">logística y trazabilidad</span><br>
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
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
</script>
</body>
</html>
