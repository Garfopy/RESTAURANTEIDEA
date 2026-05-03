<?php $pageTitle = 'Iniciar Sesión'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?> — Iniciar Sesión</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/carnihub.css">
</head>
<body style="background:#F3F4F6;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'Inter',sans-serif">

<div style="display:flex;width:100%;max-width:900px;min-height:560px;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.15)">

  <!-- Left panel — branding -->
  <div style="flex:1;background:linear-gradient(135deg,#1A1D23 0%,#2D3139 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px;color:#fff" class="hide-mobile">
    <img src="<?= BASE_URL ?>public/img/logo.svg" alt="CarniHub" style="height:56px;margin-bottom:24px;filter:brightness(0) invert(1)">
    <h2 style="font-size:1.5rem;font-weight:800;margin-bottom:8px;text-align:center">Abasto Inteligente de Carne</h2>
    <p style="color:#9CA3AF;text-align:center;font-size:.9rem;line-height:1.6">
      La plataforma B2B que conecta tu negocio con el mejor abasto de carne en Querétaro.
    </p>
    <div style="margin-top:32px;display:flex;flex-direction:column;gap:12px;width:100%">
      <?php foreach (['Precios escalonados dinámicos','Pedidos multi-sucursal','Logística inteligente','Pedidos recurrentes'] as $f): ?>
      <div style="display:flex;align-items:center;gap:10px;font-size:.85rem;color:#D1D5DB">
        <div style="width:20px;height:20px;border-radius:50%;background:#C8102E;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#fff"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
        </div>
        <?= $f ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right panel — form -->
  <div style="flex:1;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 40px">
    <div style="width:100%;max-width:360px">
      <div style="text-align:center;margin-bottom:32px">
        <img src="<?= BASE_URL ?>public/img/logo.svg" alt="CarniHub" style="height:40px;margin-bottom:16px" class="hide-desktop">
        <h1 style="font-size:1.5rem;font-weight:800;color:#111827;margin-bottom:6px">Iniciar sesión</h1>
        <p style="color:#6B7280;font-size:.875rem">Accede a tu cuenta de CarniHub</p>
      </div>

      <?php if (!empty($flash)): ?>
      <div style="padding:12px 14px;border-radius:8px;margin-bottom:16px;font-size:.875rem;<?= $flash['type']==='error' ? 'background:#FEE2E2;color:#991B1B;border:1px solid #FECACA' : 'background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0' ?>">
        <?= htmlspecialchars($flash['message']) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>auth/doLogin">
        <div style="margin-bottom:16px">
          <label class="form-label">Correo electrónico</label>
          <input type="email" name="email" class="form-control" placeholder="ejemplo@empresa.com" required autocomplete="email">
        </div>
        <div style="margin-bottom:24px">
          <label class="form-label">Contraseña</label>
          <div style="position:relative">
            <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required autocomplete="current-password">
            <button type="button" onclick="togglePassword()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9CA3AF">
              <svg id="eyeIcon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          Iniciar sesión
        </button>
      </form>

      <div style="margin-top:24px;padding-top:20px;border-top:1px solid #F3F4F6">
        <p style="text-align:center;font-size:.8rem;color:#6B7280;margin-bottom:12px">¿No tienes cuenta? Únete a CarniHub:</p>
        <div style="display:flex;gap:10px">
          <a href="<?= BASE_URL ?>registro/comprador"
             style="flex:1;text-align:center;padding:10px 8px;border-radius:8px;border:2px solid #C8102E;color:#C8102E;font-weight:600;font-size:.8rem;text-decoration:none;transition:all .2s"
             onmouseover="this.style.background='#C8102E';this.style.color='#fff'"
             onmouseout="this.style.background='transparent';this.style.color='#C8102E'">
            🛒 Soy Comprador
          </a>
          <a href="<?= BASE_URL ?>registro/repartidor"
             style="flex:1;text-align:center;padding:10px 8px;border-radius:8px;border:2px solid #374151;color:#374151;font-weight:600;font-size:.8rem;text-decoration:none;transition:all .2s"
             onmouseover="this.style.background='#374151';this.style.color='#fff'"
             onmouseout="this.style.background='transparent';this.style.color='#374151'">
            🏍️ Soy Repartidor
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function togglePassword() {
  const i = document.getElementById('passwordInput');
  i.type = i.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
