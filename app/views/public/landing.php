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
  <title><?= htmlspecialchars($appName) ?> — Plataforma para productores de carne</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    :root { --color-primary: <?= htmlspecialchars($colorPrimary) ?>; }
    .btn-primary        { background: var(--color-primary); color: #fff; }
    .btn-primary:hover  { opacity: .88; }
    .text-primary       { color: var(--color-primary); }
    .step-circle        { background: var(--color-primary); }
    .icon-wrap          { background: color-mix(in srgb, var(--color-primary) 12%, #fff); }
    .icon-svg           { color: var(--color-primary); }
    .feature-card       { transition: transform .15s, box-shadow .15s; }
    .feature-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.08); }
    .hero-bg            { background: linear-gradient(135deg, #111827 0%, #1f2937 60%, #374151 100%); }
    .bar-fill           { background: var(--color-primary); }
    .mockup-shadow      { box-shadow: 0 40px 80px rgba(0,0,0,.4), 0 0 0 1px rgba(255,255,255,.06); }
    .badge-green        { background:#D1FAE5; color:#065F46; }
    .badge-yellow       { background:#FEF3C7; color:#92400E; }
    .badge-blue         { background:#DBEAFE; color:#1E40AF; }
    @keyframes float    { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
    .float-anim         { animation: float 4.5s ease-in-out infinite; }
  </style>
</head>
<body class="bg-gray-50 text-gray-900">

<!-- ─────────────────────────── NAVBAR ─────────────────────────────────────── -->
<nav class="bg-white/95 backdrop-blur border-b border-gray-200 sticky top-0 z-50">
  <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">

    <!-- Logo / Nombre -->
    <a href="<?= BASE_URL ?>" class="flex items-center gap-2 no-underline">
      <?php if ($appLogo): ?>
        <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo" class="h-9 object-contain">
      <?php else: ?>
        <span class="text-xl font-black text-primary"><?= htmlspecialchars($appName) ?></span>
      <?php endif; ?>
    </a>

    <!-- Acciones -->
    <div class="flex items-center gap-2">
      <a href="<?= BASE_URL ?>planes"
         class="text-sm font-semibold text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100 hover:text-gray-900 transition-colors">
        Ver planes
      </a>
      <a href="<?= BASE_URL ?>auth/login"
         class="btn-primary text-sm font-bold px-5 py-2 rounded-lg transition-opacity">
        Iniciar sesión
      </a>
    </div>
  </div>
</nav>

<!-- ─────────────────────────── HERO ───────────────────────────────────────── -->
<section class="hero-bg overflow-hidden">
  <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 grid grid-cols-1 md:grid-cols-2 gap-14 items-center">

    <!-- Texto -->
    <div>
      <span class="inline-block text-xs font-bold px-4 py-1.5 rounded-full mb-6 uppercase tracking-widest"
            style="background:color-mix(in srgb,var(--color-primary) 22%,transparent);color:color-mix(in srgb,var(--color-primary) 70%,#fff)">
        Plataforma SaaS · Productores de carne
      </span>

      <h1 class="text-4xl md:text-5xl font-black text-white leading-[1.15] mb-6">
        Gestiona tu operación<br>de carne desde una
        <span style="color:var(--color-primary)"> sola plataforma</span>
      </h1>

      <p class="text-gray-400 text-lg leading-relaxed mb-10 max-w-lg">
        Pedidos en línea, GPS de repartidores, inventario, aprobaciones y reportes.
        Todo listo para usar. Sin complicaciones.
      </p>

      <div class="flex flex-col sm:flex-row gap-3 mb-12">
        <a href="<?= BASE_URL ?>planes"
           class="btn-primary font-bold text-base px-8 py-3.5 rounded-xl transition-opacity text-center">
          Ver planes y precios
        </a>
        <a href="<?= BASE_URL ?>auth/login"
           class="border border-gray-600 text-gray-300 font-semibold text-base px-8 py-3.5 rounded-xl hover:bg-white/10 transition-colors text-center">
          Iniciar sesión →
        </a>
      </div>

      <!-- Stats rápidos -->
      <div class="flex gap-8">
        <div>
          <div class="text-2xl font-black text-white">+500</div>
          <div class="text-xs text-gray-500 mt-0.5">Pedidos procesados</div>
        </div>
        <div class="border-l border-gray-700 pl-8">
          <div class="text-2xl font-black text-white">100%</div>
          <div class="text-xs text-gray-500 mt-0.5">En la nube</div>
        </div>
        <div class="border-l border-gray-700 pl-8">
          <div class="text-2xl font-black text-white">GPS</div>
          <div class="text-xs text-gray-500 mt-0.5">Rastreo en vivo</div>
        </div>
      </div>
    </div>

    <!-- Mockup dashboard -->
    <div class="hidden md:block float-anim">
      <div class="rounded-2xl overflow-hidden mockup-shadow" style="border:1px solid rgba(255,255,255,.1)">

        <!-- Barra de navegador -->
        <div class="flex items-center gap-2 px-4 py-3 bg-gray-800 border-b border-gray-700">
          <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
          <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
          <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
          <div class="flex-1 mx-3 bg-gray-700 rounded-md px-3 py-1 text-xs text-gray-400 font-mono">
            carnihub.mx/empresa/dashboard
          </div>
        </div>

        <!-- UI del dashboard -->
        <div class="bg-gray-50 p-5">

          <!-- Header -->
          <div class="flex items-center justify-between mb-4">
            <div>
              <div class="text-xs font-bold text-gray-800">Dashboard</div>
              <div class="text-[10px] text-gray-400">Lunes, 5 de mayo 2026</div>
            </div>
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-black"
                 style="background:var(--color-primary)">AE</div>
          </div>

          <!-- Tarjetas de stats -->
          <div class="grid grid-cols-3 gap-2.5 mb-4">
            <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-100">
              <div class="text-[10px] text-gray-400 mb-1">Pedidos hoy</div>
              <div class="text-lg font-black text-gray-900">24</div>
              <div class="text-[10px] text-green-600 font-semibold">↑ 12%</div>
            </div>
            <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-100">
              <div class="text-[10px] text-gray-400 mb-1">Ventas</div>
              <div class="text-lg font-black text-gray-900">$48k</div>
              <div class="text-[10px] text-green-600 font-semibold">↑ 8%</div>
            </div>
            <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-100">
              <div class="text-[10px] text-gray-400 mb-1">Repartidores</div>
              <div class="text-lg font-black text-gray-900">6</div>
              <div class="text-[10px] text-blue-600 font-semibold">5 activos</div>
            </div>
          </div>

          <!-- Mini gráfica de barras -->
          <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-3">
            <div class="text-[10px] font-semibold text-gray-600 mb-3">Ventas esta semana</div>
            <div class="flex items-end gap-1.5 h-14">
              <div class="flex-1 rounded-t-sm opacity-30 bar-fill" style="height:40%"></div>
              <div class="flex-1 rounded-t-sm bar-fill" style="height:62%"></div>
              <div class="flex-1 rounded-t-sm opacity-50 bar-fill" style="height:75%"></div>
              <div class="flex-1 rounded-t-sm bar-fill" style="height:55%"></div>
              <div class="flex-1 rounded-t-sm opacity-70 bar-fill" style="height:90%"></div>
              <div class="flex-1 rounded-t-sm bar-fill" style="height:68%"></div>
              <div class="flex-1 rounded-t-sm opacity-40 bar-fill" style="height:80%"></div>
            </div>
            <div class="flex justify-between text-[9px] text-gray-400 mt-1">
              <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
            </div>
          </div>

          <!-- Mini lista de pedidos -->
          <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-100">
            <div class="text-[10px] font-semibold text-gray-600 mb-2">Últimos pedidos</div>
            <div class="space-y-1.5">
              <div class="flex items-center justify-between text-[10px]">
                <span class="font-mono text-gray-400">#0041</span>
                <span class="text-gray-700 font-medium">Carnicería López</span>
                <span class="badge-green px-1.5 py-0.5 rounded-full font-semibold text-[9px]">Entregado</span>
              </div>
              <div class="flex items-center justify-between text-[10px]">
                <span class="font-mono text-gray-400">#0042</span>
                <span class="text-gray-700 font-medium">Res &amp; Co.</span>
                <span class="badge-yellow px-1.5 py-0.5 rounded-full font-semibold text-[9px]">En camino</span>
              </div>
              <div class="flex items-center justify-between text-[10px]">
                <span class="font-mono text-gray-400">#0043</span>
                <span class="text-gray-700 font-medium">El Toro Rest.</span>
                <span class="badge-blue px-1.5 py-0.5 rounded-full font-semibold text-[9px]">Pendiente</span>
              </div>
            </div>
          </div>

        </div><!-- /dashboard UI -->
      </div><!-- /mockup -->
    </div>

  </div>
</section>

<!-- ─────────────────────────── CARACTERÍSTICAS ────────────────────────────── -->
<section class="bg-white py-20">
  <div class="max-w-6xl mx-auto px-6">

    <div class="text-center mb-14">
      <h2 class="text-3xl font-extrabold text-gray-900 mb-3">Todo lo que necesitas para operar</h2>
      <p class="text-gray-500 max-w-xl mx-auto">
        Diseñado específicamente para productores y distribuidores de carne en México.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php
      $caracteristicas = [
        [
          'titulo' => 'Pedidos en línea',
          'desc'   => 'Tus clientes hacen pedidos desde su portal sin llamadas ni WhatsApp. Catálogo siempre actualizado.',
          'svg'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>',
        ],
        [
          'titulo' => 'GPS en tiempo real',
          'desc'   => 'Rastrea a tus repartidores con Traccar integrado. Tus clientes saben exactamente dónde está su entrega.',
          'svg'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>',
        ],
        [
          'titulo' => 'Control de inventario',
          'desc'   => 'Registra entradas y salidas de stock con alertas automáticas cuando un producto llega a su mínimo.',
          'svg'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>',
        ],
        [
          'titulo' => 'Aprobaciones y límites',
          'desc'   => 'Supervisores revisan y aprueban pedidos. Define límites de compra por cliente para un control total.',
          'svg'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>',
        ],
        [
          'titulo' => 'Reportes detallados',
          'desc'   => 'Ventas por período, movimientos de inventario y desempeño de repartidores en un solo lugar.',
          'svg'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zm6.75-4.5c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zm6.75-4.5c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>',
        ],
        [
          'titulo' => 'SaaS sin complicaciones',
          'desc'   => 'Paga mensual o anual. Sin contratos ni permanencia. Actualiza o cancela tu plan cuando lo necesites.',
          'svg'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>',
        ],
      ];
      foreach ($caracteristicas as $c): ?>
      <div class="feature-card bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
        <div class="icon-wrap w-12 h-12 rounded-xl flex items-center justify-center mb-5">
          <svg class="icon-svg w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <?= $c['svg'] ?>
          </svg>
        </div>
        <h3 class="font-bold text-gray-900 mb-2"><?= htmlspecialchars($c['titulo']) ?></h3>
        <p class="text-gray-500 text-sm leading-relaxed"><?= htmlspecialchars($c['desc']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<!-- ─────────────────────────── CÓMO FUNCIONA ──────────────────────────────── -->
<section class="bg-gray-50 py-20">
  <div class="max-w-4xl mx-auto px-6">

    <div class="text-center mb-14">
      <h2 class="text-3xl font-extrabold text-gray-900 mb-3">¿Cómo funciona?</h2>
      <p class="text-gray-500">Empieza a operar en minutos, no en semanas.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-10 relative">

      <!-- Línea conectora (solo desktop) -->
      <div class="hidden md:block absolute top-10 left-[calc(16.66%+24px)] right-[calc(16.66%+24px)] h-px bg-gray-200 z-0"></div>

      <?php
      $pasos = [
        [
          'n'      => '1',
          'titulo' => 'Te registras',
          'desc'   => 'El equipo de CarniHub activa tu cuenta de empresa. Recibes acceso inmediato a tu panel de administración.',
          'svg'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>',
        ],
        [
          'n'      => '2',
          'titulo' => 'Configuras tu empresa',
          'desc'   => 'Carga tu catálogo de productos, registra a tus clientes, supervisores y repartidores.',
          'svg'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>',
        ],
        [
          'n'      => '3',
          'titulo' => 'Tus clientes piden',
          'desc'   => 'Cada cliente entra a su portal, ve tu catálogo con sus precios y realiza pedidos al instante.',
          'svg'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>',
        ],
      ];
      foreach ($pasos as $paso): ?>
      <div class="text-center relative z-10">
        <div class="step-circle w-20 h-20 rounded-full text-white flex items-center justify-center mx-auto mb-5 shadow-lg">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <?= $paso['svg'] ?>
          </svg>
        </div>
        <div class="text-xs font-bold text-primary mb-1 uppercase tracking-widest">Paso <?= $paso['n'] ?></div>
        <h3 class="font-bold text-gray-900 text-lg mb-2"><?= htmlspecialchars($paso['titulo']) ?></h3>
        <p class="text-gray-500 text-sm leading-relaxed"><?= htmlspecialchars($paso['desc']) ?></p>
      </div>
      <?php endforeach; ?>

    </div>
  </div>
</section>

<!-- ─────────────────────────── PLANES CTA ─────────────────────────────────── -->
<section class="bg-gray-900 py-20">
  <div class="max-w-2xl mx-auto px-6 text-center">
    <h2 class="text-3xl font-extrabold text-white mb-4">Planes para cada tamaño de negocio</h2>
    <p class="text-gray-400 mb-8 leading-relaxed">
      Desde carnicerías pequeñas hasta distribuidoras con múltiples sucursales y repartidores.
    </p>
    <a href="<?= BASE_URL ?>planes"
       class="btn-primary inline-block font-bold text-base px-10 py-4 rounded-xl transition-opacity">
      Ver todos los planes →
    </a>
  </div>
</section>

<!-- ─────────────────────────── FOOTER ─────────────────────────────────────── -->
<footer class="bg-white border-t border-gray-200 py-8">
  <div class="max-w-6xl mx-auto px-6
              flex flex-col md:flex-row items-center justify-between gap-4
              text-sm text-gray-500">

    <span class="font-black text-gray-800 text-base"><?= htmlspecialchars($appName) ?></span>

    <p>
      © <?= date('Y') ?> <?= htmlspecialchars($appName) ?> ·
      <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"
         class="hover:text-gray-800 transition-colors">
        <?= htmlspecialchars($contactEmail) ?>
      </a>
    </p>

    <div class="flex gap-5">
      <a href="<?= BASE_URL ?>planes"     class="hover:text-gray-800 transition-colors">Planes</a>
      <a href="<?= BASE_URL ?>auth/login" class="hover:text-gray-800 transition-colors">Iniciar sesión</a>
    </div>

  </div>
</footer>
</body>
</html>
