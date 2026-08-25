<?php
$_cfgLogin = new ConfigModel();
$_appName  = 'Jungle Pizza';
$_waNumero = $_cfgLogin->get('whatsapp_numero_contacto', '');
$_telefono = $_waNumero ?: $_cfgLogin->get('telefono_contacto', '');
$_waPhone  = preg_replace('/[^0-9]/', '', (string)$_telefono);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#13212b">
  <title><?= htmlspecialchars($_appName) ?> &mdash; Iniciar sesi&oacute;n</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --red:#d4143c; --red-deep:#97102c; --green:#006c68; --green-deep:#064641;
      --ink:#13212b; --cream:#f3f1ea; --paper:#fffefa; --muted:#59636a;
      --border:#d9ded8; --radius:14px; --ease:180ms ease;
    }
    *,*::before,*::after{box-sizing:border-box}
    html{color-scheme:light;background:var(--ink)}
    html,body{width:100%;height:100%;overflow:hidden}
    body{margin:0;min-width:320px;color:var(--ink);background:var(--ink);font-family:"Nunito",Arial,sans-serif;-webkit-font-smoothing:antialiased}
    button,input{font:inherit} button,a{touch-action:manipulation}

    .login-page{
      position:relative;isolation:isolate;display:grid;width:100%;height:100vh;height:100dvh;min-height:0;
      place-items:center;overflow:hidden;padding:16px;
      background:
        radial-gradient(circle at 8% 15%,rgba(0,108,104,.34),transparent 32rem),
        radial-gradient(circle at 92% 88%,rgba(212,20,60,.22),transparent 30rem),
        linear-gradient(135deg,#0b171e 0%,var(--ink) 48%,#08191a 100%);
    }
    .login-page::before{
      position:fixed;z-index:-1;inset:0;content:"";opacity:.2;pointer-events:none;
      background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);
      background-size:48px 48px;mask-image:linear-gradient(to bottom,#000,transparent 82%);
    }
    .login-layout{width:min(1180px,100%);height:min(760px,calc(100dvh - 32px))}
    .login-card{
      display:grid;grid-template-columns:minmax(0,1.08fr) minmax(420px,.92fr);
      width:100%;height:100%;min-height:0;overflow:hidden;border:1px solid rgba(255,255,255,.18);
      border-radius:32px;background:var(--paper);box-shadow:0 32px 90px rgba(5,17,23,.34);
    }

    .login-story{position:relative;min-width:0;overflow:hidden;color:#fff;background:var(--green-deep)}
    .login-story picture,.story-photo,.story-scrim{position:absolute;inset:0;width:100%;height:100%}
    .story-photo{display:block;object-fit:cover;object-position:52% center;transform:scale(1.015)}
    .story-scrim{
      background:linear-gradient(180deg,rgba(8,20,23,.2),rgba(8,20,23,.08) 32%,rgba(7,18,20,.94) 100%),
                 linear-gradient(115deg,rgba(0,108,104,.42),transparent 55%,rgba(212,20,60,.2));
    }
    .brand-link{
      position:absolute;z-index:2;top:32px;left:36px;display:inline-flex;width:154px;min-height:92px;
      align-items:center;justify-content:center;border-radius:24px;outline-offset:6px;
    }
    .brand-link:focus-visible{outline:3px solid #fff}
    .brand-logo{display:block;width:154px;height:auto;filter:drop-shadow(0 12px 28px rgba(0,0,0,.32))}
    .story-content{position:absolute;z-index:2;right:0;bottom:0;left:0;padding:clamp(30px,4vh,48px)}
    .location-chip{
      display:inline-flex;min-height:36px;align-items:center;margin-bottom:18px;padding:7px 12px;gap:8px;
      border:1px solid rgba(255,255,255,.34);border-radius:999px;background:rgba(6,70,65,.65);
      backdrop-filter:blur(10px);font-size:.78rem;font-weight:900;letter-spacing:.09em;text-transform:uppercase;
    }
    .location-chip svg,.story-tag svg{width:16px;height:16px;flex:0 0 auto}
    .story-title{
      max-width:540px;margin:0;font-family:"Chewy","Trebuchet MS",sans-serif;
      font-size:clamp(2.25rem,3.9vw,3.7rem);font-weight:400;line-height:1.02;letter-spacing:.01em;
      text-wrap:balance;text-shadow:0 3px 24px rgba(0,0,0,.34);
    }
    .story-copy{max-width:520px;margin:18px 0 0;color:rgba(255,255,255,.87);font-size:1rem;line-height:1.65}
    .story-tags{display:flex;flex-wrap:wrap;margin-top:24px;gap:10px}
    .story-tag{
      display:inline-flex;min-height:36px;align-items:center;padding:7px 12px;gap:8px;border-radius:999px;
      background:rgba(19,33,43,.72);backdrop-filter:blur(8px);font-size:.8rem;font-weight:800;
    }
    .story-tag svg{color:#b9d4cc}

    .login-panel{
      position:relative;display:flex;min-height:0;align-items:center;justify-content:center;padding:clamp(24px,4vh,44px) clamp(36px,5vw,68px);
      background:radial-gradient(circle at 100% 0%,rgba(212,20,60,.055),transparent 18rem),var(--paper);
    }
    .login-panel::before{position:absolute;top:0;right:0;left:0;height:5px;content:"";background:linear-gradient(90deg,var(--green) 0 34%,var(--red) 34% 100%)}
    .form-box{width:100%;max-width:410px}
    .kicker{
      display:inline-flex;align-items:center;margin-bottom:14px;gap:9px;color:var(--green);
      font-size:.76rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;
    }
    .kicker::before{width:28px;height:3px;border-radius:999px;content:"";background:var(--red)}
    .login-title{
      margin:0;color:var(--ink);font-family:"Chewy","Trebuchet MS",sans-serif;
      font-size:clamp(2.35rem,4vw,3.15rem);font-weight:400;line-height:1;
    }
    .login-intro{margin:10px 0 24px;color:var(--muted);font-size:1rem;line-height:1.55}
    .login-intro strong{color:var(--ink);font-weight:900}

    .flash-box{
      display:flex;align-items:flex-start;margin-bottom:16px;padding:12px 14px;gap:11px;
      border:1px solid;border-radius:13px;font-size:.92rem;font-weight:700;line-height:1.45;
    }
    .flash-box svg{width:20px;height:20px;flex:0 0 auto;margin-top:1px}
    .flash-box.is-error{border-color:#f0b7c3;color:#7f1028;background:#fff1f4}
    .flash-box.is-success{border-color:#a8d5c4;color:#0b5c42;background:#edf9f4}

    .field{margin-bottom:16px}
    .field-label{display:inline-block;margin-bottom:8px;color:var(--ink);font-size:.94rem;font-weight:800}
    .field-control{position:relative}
    .field-icon{
      position:absolute;z-index:1;top:50%;left:17px;display:grid;width:20px;height:20px;place-items:center;
      color:#738087;pointer-events:none;transform:translateY(-50%);transition:color var(--ease);
    }
    .field-icon svg{width:20px;height:20px}.field-control:focus-within .field-icon{color:var(--red)}
    .field-input{
      width:100%;min-height:56px;padding:14px 54px 14px 50px;border:1.5px solid var(--border);
      border-radius:var(--radius);outline:none;color:var(--ink);background:#fff;font-size:1rem;
      transition:border-color var(--ease),box-shadow var(--ease);
    }
    .field-input:hover{border-color:#acb8b3}
    .field-input:focus{border-color:var(--red);box-shadow:0 0 0 4px rgba(212,20,60,.13)}
    .field-input::placeholder{color:#89949a;opacity:1}
    .password-toggle{
      position:absolute;z-index:2;top:50%;right:5px;display:grid;width:46px;height:46px;padding:0;place-items:center;
      border:0;border-radius:11px;color:#66747a;background:transparent;cursor:pointer;transform:translateY(-50%);
      transition:color var(--ease),background-color var(--ease);
    }
    .password-toggle:hover{color:var(--red);background:#fff0f3}
    .password-toggle:focus-visible{outline:3px solid rgba(212,20,60,.32);outline-offset:1px}
    .password-toggle svg{position:absolute;width:21px;height:21px;transition:opacity var(--ease)}
    .icon-eye-off,.password-toggle[aria-pressed="true"] .icon-eye{opacity:0}
    .password-toggle[aria-pressed="true"] .icon-eye-off{opacity:1}
    .form-options{display:flex;min-height:44px;align-items:center;justify-content:flex-end;margin-top:-8px;margin-bottom:16px}
    .forgot-link{
      display:inline-flex;min-height:44px;align-items:center;color:var(--green);font-size:.9rem;
      font-weight:900;text-decoration:none;transition:color var(--ease);
    }
    .forgot-link:hover{color:var(--red-deep);text-decoration:underline;text-underline-offset:4px}
    .forgot-link:focus-visible{border-radius:6px;outline:3px solid rgba(0,108,104,.28);outline-offset:3px}

    .login-submit{
      display:inline-flex;width:100%;min-height:56px;align-items:center;justify-content:center;padding:14px 22px;gap:11px;
      border:0;border-radius:var(--radius);color:#fff;background:var(--red);box-shadow:0 12px 28px rgba(151,16,44,.24);
      cursor:pointer;font-size:1rem;font-weight:900;transition:background-color var(--ease),box-shadow var(--ease),transform var(--ease);
    }
    .login-submit:hover{background:var(--red-deep);box-shadow:0 15px 34px rgba(151,16,44,.3)}
    .login-submit:active{transform:scale(.985)}
    .login-submit:focus-visible{outline:4px solid rgba(212,20,60,.28);outline-offset:4px}
    .login-submit:disabled{opacity:.72;cursor:wait;box-shadow:none}
    .submit-spinner{
      display:none;width:20px;height:20px;border:2.5px solid rgba(255,255,255,.45);
      border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;
    }
    .login-submit.is-loading .submit-spinner{display:block}
    .support-box{
      margin-top:20px;padding:14px 18px;border:1px solid #cbded9;border-radius:14px;
      color:#44565c;background:#f0f7f4;font-size:.88rem;line-height:1.55;text-align:center;
    }
    .support-box strong{display:block;margin-bottom:2px;color:var(--green-deep);font-size:.92rem}
    .support-box a{display:inline-flex;min-height:44px;align-items:center;color:var(--red-deep);font-weight:900;text-decoration:none}
    .support-box a:hover{text-decoration:underline;text-underline-offset:4px}
    .security-note{
      display:none;align-items:center;justify-content:center;margin:0;gap:8px;
      color:rgba(255,255,255,.7);font-size:.78rem;font-weight:700;text-align:center;
    }
    .security-note svg{width:15px;height:15px;flex:0 0 auto;color:#b9d4cc}
    @keyframes spin{to{transform:rotate(360deg)}}

    @media(max-width:980px){
      .login-card{grid-template-columns:minmax(0,.9fr) minmax(400px,1.1fr)}
      .story-content{padding:36px}.login-panel{padding-right:36px;padding-left:36px}
    }
    @media(min-width:761px) and (max-height:760px){
      .login-page{padding:8px}.login-layout{height:calc(100dvh - 16px)}
      .brand-link{top:16px;left:24px;width:112px;min-height:68px}.brand-logo{width:112px}
      .story-content{padding:28px}.location-chip{min-height:32px;margin-bottom:10px;padding:5px 10px}
      .story-title{font-size:clamp(2.1rem,4.8vh,2.8rem)}.story-copy{margin-top:10px;line-height:1.45}
      .story-tags{margin-top:12px}.login-panel{padding:20px 34px}
      .kicker{margin-bottom:8px}.login-title{font-size:clamp(2.2rem,6vh,2.7rem)}
      .login-intro{margin:8px 0 16px;line-height:1.4}.field{margin-bottom:12px}
      .field-label{margin-bottom:6px}.field-input{min-height:50px;padding-top:11px;padding-bottom:11px}
      .form-options{min-height:36px;margin-top:-6px;margin-bottom:8px}.forgot-link{min-height:36px}
      .login-submit{min-height:50px;padding:11px 20px}.support-box{margin-top:12px;padding:9px 14px;line-height:1.35}
    }
    @media(min-width:761px) and (max-height:650px){
      .story-copy,.story-tags,.support-box{display:none}
    }
    @media(max-width:760px){
      .login-page{display:block;height:100dvh;overflow:hidden;padding:8px}.login-layout{height:100%;max-width:560px;margin:0 auto}
      .login-card{display:grid;grid-template-rows:clamp(140px,26dvh,210px) minmax(0,1fr);height:100%;min-height:0;border-radius:24px}
      .login-story{min-height:0}
      .story-photo{object-position:center 48%}.brand-link{top:18px;left:20px;width:112px;min-height:72px}
      .brand-logo{width:112px}.story-content{padding:22px}.location-chip{min-height:32px;margin-bottom:10px;padding:5px 10px;font-size:.67rem}
      .story-title{font-size:clamp(1.8rem,8vw,2.35rem)}.story-copy,.story-tags{display:none}
      .login-panel{overflow:hidden;padding:18px 24px}.kicker{margin-bottom:8px}
      .login-title{font-size:clamp(2.1rem,10vw,2.6rem)}.login-intro{margin:8px 0 16px;line-height:1.4}
      .field{margin-bottom:12px}.field-label{margin-bottom:6px}.field-input{min-height:52px;padding-top:11px;padding-bottom:11px}
      .form-options{min-height:36px;margin-top:-4px;margin-bottom:8px}.forgot-link{min-height:36px}
      .login-submit{min-height:52px;padding:11px 20px}.support-box,.security-note{display:none}
    }
    @media(max-width:420px){
      .login-page{padding:0;background:var(--paper)}.login-card{border:0;border-radius:0;box-shadow:none}
      .login-panel{padding:16px 20px}
      .security-note{color:var(--muted)}.security-note svg{color:var(--green)}
    }
    @media(max-width:760px) and (max-height:560px) and (orientation:landscape){
      .login-page{padding:6px}.login-layout{max-width:980px}
      .login-card{display:grid;grid-template-columns:minmax(280px,.82fr) minmax(400px,1.18fr)}
      .login-story{min-height:0}.brand-link,.brand-logo{width:94px}.story-content{padding:20px}
      .location-chip,.story-copy,.story-tags,.kicker,.support-box{display:none}.story-title{font-size:1.85rem}
      .login-panel{padding:14px 28px}.login-title{font-size:2rem}.login-intro{margin:4px 0 10px;font-size:.9rem}
      .field{margin-bottom:8px}.field-input{min-height:46px}.form-options{min-height:30px;margin-bottom:4px}
      .forgot-link{min-height:30px}.login-submit{min-height:46px}
    }
    @media(max-width:760px) and (max-height:650px) and (orientation:portrait){
      .kicker{display:none}.login-intro{font-size:.9rem}.login-panel{padding-top:12px;padding-bottom:12px}
    }
    @media(max-height:420px){
      .login-story{display:none}.login-card{display:block}.login-panel{height:100%}
    }
    @media(prefers-reduced-motion:reduce){
      *,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}
    }
  </style>
</head>
<body>
<main class="login-page" id="main-content">
  <div class="login-layout">
    <section class="login-card" aria-labelledby="login-title">
      <aside class="login-story" aria-label="Jungle Pizza en Playa La Ropa">
        <picture>
          <source media="(max-width:760px)" srcset="<?= BASE_URL ?>base/redesign-assets/hero-jungle-pizza-760.webp">
          <img class="story-photo"
            src="<?= BASE_URL ?>base/redesign-assets/hero-jungle-pizza-1600.webp"
            srcset="<?= BASE_URL ?>base/redesign-assets/hero-jungle-pizza-760.webp 760w, <?= BASE_URL ?>base/redesign-assets/hero-jungle-pizza-960.webp 960w, <?= BASE_URL ?>base/redesign-assets/hero-jungle-pizza-1600.webp 1600w"
            sizes="(max-width:760px) 100vw, 58vw" width="1600" height="878"
            alt="Pizza y margarita junto al horno de Jungle Pizza" fetchpriority="high" decoding="async">
        </picture>
        <div class="story-scrim" aria-hidden="true"></div>
        <a class="brand-link" href="<?= BASE_URL ?>" aria-label="Ir al inicio de Jungle Pizza">
          <img class="brand-logo" src="<?= BASE_URL ?>base/redesign-assets/jungle-pizza-logo-420.webp"
            width="420" height="338" alt="Jungle Pizza">
        </a>
        <div class="story-content">
          <span class="location-chip">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-4.35 7-11A7 7 0 105 10c0 6.65 7 11 7 11z"/><circle cx="12" cy="10" r="2.25"/>
            </svg>
            Playa La Ropa &middot; Zihuatanejo
          </span>
          <h2 class="story-title">Tu restaurante, en movimiento y bajo control.</h2>
          <p class="story-copy">Reservas, mesas, comandas y ventas conectadas para que el equipo se concentre en servir una experiencia inolvidable.</p>
          <div class="story-tags" aria-label="Funciones principales">
            <span class="story-tag">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 012 2v12H4V7a2 2 0 012-2z"/></svg>
              Reservaciones
            </span>
            <span class="story-tag">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M7 6l1 14h8l1-14M9 3h6"/></svg>
              Comandas
            </span>
            <span class="story-tag">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M5 20V10m7 10V4m7 16v-7"/></svg>
              Ventas en vivo
            </span>
          </div>
        </div>
      </aside>

      <section class="login-panel">
        <div class="form-box">
          <span class="kicker">Administraci&oacute;n del restaurante</span>
          <h1 class="login-title" id="login-title">Bienvenido de vuelta</h1>
          <p class="login-intro">Ingresa con tu cuenta para acceder al panel de <strong><?= htmlspecialchars($_appName) ?></strong>.</p>

          <?php if (!empty($flash)): ?>
            <?php $flashIsError = ($flash['type'] ?? '') === 'error'; ?>
            <div class="flash-box <?= $flashIsError ? 'is-error' : 'is-success' ?>"
              role="<?= $flashIsError ? 'alert' : 'status' ?>" aria-live="<?= $flashIsError ? 'assertive' : 'polite' ?>">
              <?php if ($flashIsError): ?>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7.5v5M12 16.5h.01"/></svg>
              <?php else: ?>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M8 12l2.5 2.5L16 9"/></svg>
              <?php endif; ?>
              <span><?= htmlspecialchars((string)$flash['message']) ?></span>
            </div>
          <?php endif; ?>

          <form method="POST" action="<?= BASE_URL ?>auth/doLogin" id="login-form">
            <div class="field">
              <label class="field-label" for="login-email">Correo electr&oacute;nico</label>
              <div class="field-control">
                <span class="field-icon" aria-hidden="true">
                  <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" d="M3.5 7.5l7.2 5a2.25 2.25 0 002.6 0l7.2-5M5.5 19h13a2 2 0 002-2V7a2 2 0 00-2-2h-13a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </span>
                <input class="field-input" type="email" id="login-email" name="email"
                  placeholder="admin@junglezihua.com" required autocomplete="username"
                  autocapitalize="none" spellcheck="false" autofocus>
              </div>
            </div>
            <div class="field">
              <label class="field-label" for="passwordInput">Contrase&ntilde;a</label>
              <div class="field-control">
                <span class="field-icon" aria-hidden="true">
                  <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9"><rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path stroke-linecap="round" d="M8 10.5V7.75a4 4 0 018 0v2.75"/></svg>
                </span>
                <input class="field-input" type="password" id="passwordInput" name="password"
                  placeholder="Escribe tu contrase&ntilde;a" required autocomplete="current-password">
                <button class="password-toggle" type="button" id="password-toggle"
                  aria-label="Mostrar contrase&ntilde;a" aria-controls="passwordInput" aria-pressed="false">
                  <svg class="icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"/><circle cx="12" cy="12" r="2.75"/></svg>
                  <svg class="icon-eye-off" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" d="M3 3l18 18M10.7 6.1A10.8 10.8 0 0112 6c6 0 9.5 6 9.5 6a15.4 15.4 0 01-2.1 2.8M6.2 6.3C3.8 8.1 2.5 12 2.5 12s3.5 6 9.5 6c1 0 2-.17 2.9-.49"/></svg>
                </button>
              </div>
            </div>
            <div class="form-options">
              <a class="forgot-link" href="<?= BASE_URL ?>auth/forgot">&iquest;Olvidaste tu contrase&ntilde;a?</a>
            </div>
            <button class="login-submit" type="submit" id="login-submit">
              <span class="submit-spinner" aria-hidden="true"></span><span id="submit-label">Entrar al sistema</span>
            </button>
          </form>

          <div class="support-box">
            <strong>&iquest;Necesitas ayuda para acceder?</strong>
            <?php if ($_waPhone): ?>
              <a href="https://wa.me/<?= htmlspecialchars($_waPhone) ?>?text=<?= urlencode('Hola, necesito ayuda para acceder al sistema de Jungle Pizza.') ?>"
                target="_blank" rel="noopener">Contactar al administrador</a>
            <?php else: ?>
              Contacta al administrador de Jungle Pizza.
            <?php endif; ?>
          </div>
        </div>
      </section>
    </section>
    <p class="security-note">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M7 10V7a5 5 0 0110 0v3M5 10h14v10H5z"/></svg>
      Acceso privado y seguro para el equipo de Jungle Pizza
    </p>
  </div>
</main>
<script>
(function(){
  const input=document.getElementById('passwordInput');
  const toggle=document.getElementById('password-toggle');
  const form=document.getElementById('login-form');
  const submit=document.getElementById('login-submit');
  const label=document.getElementById('submit-label');
  toggle.addEventListener('click',function(){
    const show=input.type==='password';
    input.type=show?'text':'password';
    toggle.setAttribute('aria-pressed',show?'true':'false');
    toggle.setAttribute('aria-label',show?'Ocultar contraseña':'Mostrar contraseña');
    input.focus({preventScroll:true});
  });
  form.addEventListener('submit',function(){
    submit.disabled=true;submit.classList.add('is-loading');
    submit.setAttribute('aria-busy','true');label.textContent='Ingresando...';
  });
}());
</script>
</body>
</html>
