<?php
$pageTitle = 'Iniciar Sesión';
$_cfgLogin = new ConfigModel();
$_appLogo  = $_cfgLogin->get('app_logo', '');
$_appName  = $_cfgLogin->get('app_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($_appName) ?> — Iniciar Sesión</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/carnihub.css">
  <style>
    @keyframes loginCardIn {
      from { opacity: 0; transform: translateY(32px) scale(.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes glowPulse {
      0%, 100% { opacity: .35; transform: scale(1); }
      50%       { opacity: .65; transform: scale(1.12); }
    }
    @keyframes bgShift {
      0%, 100% { background-position: 0% 50%; }
      50%       { background-position: 100% 50%; }
    }

    .login-bg {
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Inter', sans-serif;
      background: #0D1117;
      position: relative;
      padding: 24px 16px;
      overflow: hidden;
    }
    /* Atmospheric blobs behind the card */
    .login-bg::before {
      content: '';
      position: fixed; inset: 0; pointer-events: none;
      background:
        radial-gradient(ellipse 55% 45% at 12% 25%, rgba(200,16,46,.18) 0%, transparent 65%),
        radial-gradient(ellipse 45% 55% at 88% 75%, rgba(25,35,65,.7) 0%, transparent 65%),
        radial-gradient(ellipse 70% 35% at 50% 110%, rgba(200,16,46,.06) 0%, transparent 60%);
    }

    .login-card-wrap {
      display: flex;
      width: 100%;
      max-width: 1000px;
      min-height: 640px;
      border-radius: 24px;
      overflow: hidden;
      border: 1px solid rgba(255,255,255,.07);
      box-shadow:
        0 0 0 1px rgba(255,255,255,.03),
        0 32px 80px rgba(0,0,0,.65),
        0 80px 160px rgba(0,0,0,.40),
        0 0 120px rgba(200,16,46,.12);
      animation: loginCardIn .7s cubic-bezier(.22,1,.36,1) both;
      position: relative; z-index: 1;
    }

    /* ── Left panel ── */
    .login-left {
      flex: 1;
      position: relative;
      overflow: hidden;
      background: linear-gradient(150deg, #12151A 0%, #1C2028 45%, #252B34 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 48px 40px;
      color: #fff;
    }
    /* Dot grid */
    .login-left::before {
      content: '';
      position: absolute; inset: 0;
      background-image: radial-gradient(circle, rgba(255,255,255,.09) 1px, transparent 1px);
      background-size: 24px 24px;
      pointer-events: none;
      z-index: 0;
    }
    /* Diagonal stripe overlay — premium texture */
    .login-left::after {
      content: '';
      position: absolute; inset: 0;
      background: repeating-linear-gradient(
        -52deg,
        transparent,
        transparent 28px,
        rgba(255,255,255,.018) 28px,
        rgba(255,255,255,.018) 29px
      );
      pointer-events: none;
      z-index: 0;
    }
    .login-glow-top {
      position: absolute;
      top: -100px; right: -100px;
      width: 340px; height: 340px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(200,16,46,.45) 0%, transparent 70%);
      filter: blur(50px);
      animation: glowPulse 4.5s ease-in-out infinite;
      pointer-events: none;
      z-index: 0;
    }
    .login-glow-btm {
      position: absolute;
      bottom: -120px; left: -70px;
      width: 260px; height: 260px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(200,16,46,.22) 0%, transparent 70%);
      filter: blur(60px);
      animation: glowPulse 4.5s ease-in-out infinite reverse;
      pointer-events: none;
      z-index: 0;
    }
    .login-accent-bar {
      width: 44px; height: 4px;
      border-radius: 2px;
      background: linear-gradient(90deg, #C8102E, #FF2E52);
      margin-bottom: 18px;
      box-shadow: 0 2px 12px rgba(200,16,46,.5);
    }
    .login-stats {
      display: flex; gap: 10px;
      margin-top: 28px;
      width: 100%;
    }
    .login-stat {
      flex: 1;
      background: rgba(255,255,255,.055);
      border: 1px solid rgba(255,255,255,.09);
      border-radius: 12px;
      padding: 14px 8px;
      text-align: center;
      backdrop-filter: blur(6px);
    }
    .login-stat-num {
      font-size: 1.1rem; font-weight: 800;
      color: #fff; line-height: 1;
      margin-bottom: 5px;
    }
    .login-stat-label {
      font-size: .6rem; font-weight: 600;
      color: rgba(255,255,255,.45);
      text-transform: uppercase; letter-spacing: .07em;
    }
    .login-features {
      display: flex; flex-direction: column;
      gap: 13px; width: 100%;
      margin-top: 24px;
    }
    .login-feature {
      display: flex; align-items: center;
      gap: 12px; font-size: .84rem;
      color: #CBD5E1; line-height: 1.3;
    }
    .login-feature-dot {
      width: 22px; height: 22px; border-radius: 50%;
      background: #C8102E;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(200,16,46,.45);
    }
    .login-tagline {
      position: absolute;
      bottom: 22px; left: 0; right: 0;
      text-align: center;
      font-size: .67rem;
      color: rgba(255,255,255,.22);
      letter-spacing: .08em;
      text-transform: uppercase;
      z-index: 1;
    }

    /* ── Right panel ── */
    .login-right {
      flex: 1;
      background: #FFFFFF;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      padding: 52px 44px;
      position: relative;
    }
    /* Thin red top accent line */
    .login-right::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, #C8102E 0%, #FF2E52 50%, #C8102E 100%);
      background-size: 200% 100%;
      animation: bgShift 4s ease infinite;
    }

    /* ── Inputs ── */
    .input-wrap {
      position: relative;
      margin-bottom: 16px;
    }
    .input-icon-left {
      position: absolute; left: 13px; top: 50%;
      transform: translateY(-50%);
      color: #9CA3AF;
      pointer-events: none; display: flex;
      transition: color .2s;
    }
    .input-wrap:focus-within .input-icon-left { color: #C8102E; }
    .input-login {
      width: 100%;
      padding: 11px 44px;
      border: 1.5px solid #E2E5EB;
      border-radius: 10px;
      font-size: .875rem;
      color: #111827;
      background: #fff;
      outline: none;
      font-family: 'Inter', sans-serif;
      transition: border-color .2s, box-shadow .2s;
      box-sizing: border-box;
    }
    .input-login:focus {
      border-color: #C8102E;
      box-shadow: 0 0 0 3px rgba(200,16,46,.11);
    }
    .input-login::placeholder { color: #BFC4CE; }

    @media (max-width: 768px) {
      .login-card-wrap {
        border-radius: 20px;
        min-height: auto;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0,0,0,.6), 0 0 60px rgba(200,16,46,.10);
      }
      .login-right {
        padding: 36px 24px;
      }
    }

    /* ── Password toggle icons ── */
    .pw-toggle-btn {
      position: absolute; right: 11px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      padding: 0; display: flex; align-items: center; justify-content: center;
      color: #9CA3AF; transition: color .2s;
      width: 22px; height: 22px;
    }
    .pw-toggle-btn:hover { color: #374151; }
    .icon-eye, .icon-eye-off {
      position: absolute; top: 0; left: 0;
      transition: opacity .2s ease, transform .2s ease;
    }
    .icon-eye-off { opacity: 0; transform: scale(.7); }
    .pw-wrap.pw-shown .icon-eye     { opacity: 0; transform: scale(.7); }
    .pw-wrap.pw-shown .icon-eye-off { opacity: 1; transform: scale(1); }

    /* ── Button ── */
    .btn-login-submit {
      width: 100%; padding: 13px;
      border: none; border-radius: 10px;
      font-size: .9375rem; font-weight: 700;
      font-family: 'Inter', sans-serif;
      color: #fff;
      background: linear-gradient(135deg, #C8102E 0%, #A00D24 100%);
      cursor: pointer;
      box-shadow: 0 4px 16px rgba(200,16,46,.38);
      transition: transform .2s, box-shadow .2s;
      letter-spacing: .015em;
      margin-top: 4px;
    }
    .btn-login-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(200,16,46,.48);
    }
    .btn-login-submit:active {
      transform: translateY(0);
      box-shadow: 0 3px 10px rgba(200,16,46,.28);
    }

    /* ── Flash messages ── */
    .flash-box {
      display: flex; align-items: flex-start;
      gap: 10px; padding: 13px 14px;
      border-radius: 10px; margin-bottom: 20px;
      font-size: .85rem; line-height: 1.5;
    }
    .flash-box.is-error {
      background: #FEE2E2;
      border-left: 4px solid #C8102E;
      color: #7F1D1D;
    }
    .flash-box.is-success {
      background: #D1FAE5;
      border-left: 4px solid #10B981;
      color: #064E3B;
    }
    .flash-box svg { flex-shrink: 0; margin-top: 1px; }

    /* ── Forgot link ── */
    .forgot-link {
      display: block; text-align: right;
      font-size: .8rem; color: #C8102E;
      text-decoration: none; font-weight: 500;
      margin-top: -8px; margin-bottom: 20px;
      transition: color .15s;
    }
    .forgot-link:hover { color: #A00D24; text-decoration: underline; }
  </style>
</head>
<body class="login-bg">

<div class="login-card-wrap">

  <!-- Panel izquierdo — branding -->
  <div class="login-left hide-mobile">

    <div class="login-glow-top"></div>
    <div class="login-glow-btm"></div>

    <div style="position:relative;z-index:1;width:100%;display:flex;flex-direction:column;align-items:center">

      <?php if ($_appLogo): ?>
        <img src="<?= htmlspecialchars($_appLogo) ?>" alt="<?= htmlspecialchars($_appName) ?>"
             style="height:60px;margin-bottom:20px;object-fit:contain;filter:brightness(0) invert(1)">
      <?php else: ?>
        <img src="<?= BASE_URL ?>public/img/logo.svg" alt="<?= htmlspecialchars($_appName) ?>"
             style="height:60px;margin-bottom:20px;filter:brightness(0) invert(1)">
      <?php endif; ?>

      <div class="login-accent-bar"></div>

      <h2 style="font-size:1.45rem;font-weight:800;margin-bottom:10px;text-align:center;line-height:1.25">
        Abasto Inteligente<br>de Carne
      </h2>
      <p style="color:#94A3B8;text-align:center;font-size:.84rem;line-height:1.65;max-width:240px;margin:0">
        La plataforma B2B que conecta tu negocio con el mejor abasto cárnico.
      </p>

      <div class="login-stats">
        <div class="login-stat">
          <div class="login-stat-num">500+</div>
          <div class="login-stat-label">Empresas</div>
        </div>
        <div class="login-stat">
          <div class="login-stat-num">1M+</div>
          <div class="login-stat-label">kg gestionados</div>
        </div>
        <div class="login-stat">
          <div class="login-stat-num">99.9%</div>
          <div class="login-stat-label">Uptime</div>
        </div>
      </div>

      <div class="login-features">
        <?php
          $features = [
            ['Precios escalonados dinámicos', 'M5 13l4 4L19 7'],
            ['Pedidos multi-sucursal',        'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['Logística inteligente',         'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0'],
            ['Análisis de consumo',           'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
          ];
          foreach ($features as [$label, $path]): ?>
        <div class="login-feature">
          <div class="login-feature-dot">
            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="<?= $path ?>"/>
            </svg>
          </div>
          <span><?= $label ?></span>
        </div>
        <?php endforeach; ?>
      </div>

    </div>

    <div class="login-tagline">Plataforma líder en abasto cárnico B2B</div>
  </div>

  <!-- Panel derecho — formulario -->
  <div class="login-right">
    <div style="width:100%;max-width:380px">

      <div style="text-align:center;margin-bottom:24px" class="hide-desktop">
        <?php if ($_appLogo): ?>
          <img src="<?= htmlspecialchars($_appLogo) ?>" alt="<?= htmlspecialchars($_appName) ?>"
               style="height:44px;margin-bottom:14px;object-fit:contain">
        <?php else: ?>
          <img src="<?= BASE_URL ?>public/img/logo.svg" alt="<?= htmlspecialchars($_appName) ?>"
               style="height:44px;margin-bottom:14px">
        <?php endif; ?>
      </div>

      <div style="margin-bottom:30px">
        <h1 style="font-size:1.6rem;font-weight:800;color:#111827;margin:0 0 6px">Iniciar sesión</h1>
        <p style="color:#6B7280;font-size:.875rem;margin:0">
          Accede a tu cuenta de <strong style="color:#374151;font-weight:600"><?= htmlspecialchars($_appName) ?></strong>
        </p>
      </div>

      <?php if (!empty($flash)): ?>
      <div class="flash-box <?= $flash['type'] === 'error' ? 'is-error' : 'is-success' ?>">
        <?php if ($flash['type'] === 'error'): ?>
          <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        <?php else: ?>
          <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        <?php endif; ?>
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>auth/doLogin">

        <div>
          <label class="form-label" style="display:block;margin-bottom:6px">Correo electrónico</label>
          <div class="input-wrap">
            <span class="input-icon-left">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
            </span>
            <input type="email" name="email" class="input-login"
                   placeholder="ejemplo@empresa.com" required autocomplete="email">
          </div>
        </div>

        <div>
          <label class="form-label" style="display:block;margin-bottom:6px">Contraseña</label>
          <div class="input-wrap pw-wrap" id="pwWrap">
            <span class="input-icon-left">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <rect x="5" y="11" width="14" height="10" rx="2" ry="2"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 018 0v4"/>
              </svg>
            </span>
            <input type="password" name="password" id="passwordInput" class="input-login"
                   placeholder="••••••••" required autocomplete="current-password">
            <button type="button" onclick="togglePassword()" class="pw-toggle-btn" aria-label="Mostrar contraseña">
              <svg class="icon-eye" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <svg class="icon-eye-off" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
              </svg>
            </button>
          </div>
        </div>

        <a href="<?= BASE_URL ?>auth/forgot" class="forgot-link">¿Olvidaste tu contraseña?</a>

        <button type="submit" class="btn-login-submit">Iniciar sesión</button>
      </form>

      <p style="margin-top:22px;text-align:center;font-size:.775rem;color:#9CA3AF;line-height:1.5">
        ¿Problemas para acceder?<br>Contacta al administrador de tu empresa.
      </p>

    </div>
  </div>
</div>

<script>
function togglePassword() {
  const input = document.getElementById('passwordInput');
  const wrap  = document.getElementById('pwWrap');
  input.type  = input.type === 'password' ? 'text' : 'password';
  wrap.classList.toggle('pw-shown', input.type === 'text');
}
</script>
</body>
</html>
