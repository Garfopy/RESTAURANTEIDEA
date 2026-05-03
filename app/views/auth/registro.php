<?php $pageTitle = 'Crear cuenta — CarniHub'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?> — Crear cuenta</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/carnihub.css">
</head>
<body style="background:#F3F4F6;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'Inter',sans-serif">

<div style="width:100%;max-width:600px;padding:24px">

  <div style="text-align:center;margin-bottom:32px">
    <img src="<?= BASE_URL ?>public/img/logo.svg" alt="CarniHub" style="height:48px;margin-bottom:16px">
    <h1 style="font-size:1.75rem;font-weight:800;color:#111827">Únete a CarniHub</h1>
    <p style="color:#6B7280;margin-top:6px">Selecciona el tipo de cuenta que deseas crear</p>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <!-- Comprador -->
    <a href="<?= BASE_URL ?>registro/comprador"
       style="background:#fff;border-radius:16px;padding:32px 24px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.07);text-decoration:none;border:2px solid transparent;transition:all .2s;display:block"
       onmouseover="this.style.borderColor='#C8102E';this.style.transform='translateY(-4px)'"
       onmouseout="this.style.borderColor='transparent';this.style.transform='translateY(0)'">
      <div style="font-size:3rem;margin-bottom:16px">🛒</div>
      <h2 style="font-size:1.25rem;font-weight:700;color:#111827;margin-bottom:8px">Soy Comprador</h2>
      <p style="color:#6B7280;font-size:.875rem;line-height:1.6">
        Tengo un negocio (taquería, restaurant, carnicería) y quiero hacer pedidos de carne al mayoreo.
      </p>
      <div style="margin-top:20px;display:inline-block;padding:10px 24px;background:#C8102E;color:#fff;border-radius:8px;font-weight:600;font-size:.875rem">
        Registrarme como Comprador
      </div>
    </a>

    <!-- Repartidor -->
    <a href="<?= BASE_URL ?>registro/repartidor"
       style="background:#fff;border-radius:16px;padding:32px 24px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.07);text-decoration:none;border:2px solid transparent;transition:all .2s;display:block"
       onmouseover="this.style.borderColor='#374151';this.style.transform='translateY(-4px)'"
       onmouseout="this.style.borderColor='transparent';this.style.transform='translateY(0)'">
      <div style="font-size:3rem;margin-bottom:16px">🏍️</div>
      <h2 style="font-size:1.25rem;font-weight:700;color:#111827;margin-bottom:8px">Soy Repartidor</h2>
      <p style="color:#6B7280;font-size:.875rem;line-height:1.6">
        Quiero unirme al equipo de entregas de CarniHub y hacer repartos en la ciudad.
      </p>
      <div style="margin-top:20px;display:inline-block;padding:10px 24px;background:#1F2937;color:#fff;border-radius:8px;font-weight:600;font-size:.875rem">
        Registrarme como Repartidor
      </div>
    </a>

  </div>

  <div style="text-align:center;margin-top:24px;font-size:.875rem;color:#9CA3AF">
    ¿Ya tienes cuenta?
    <a href="<?= BASE_URL ?>auth/login" style="color:#C8102E;font-weight:600">Iniciar sesión</a>
  </div>

</div>

</body>
</html>
