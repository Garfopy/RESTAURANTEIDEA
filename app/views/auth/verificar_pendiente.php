<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?> — Verifica tu correo</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/carnihub.css">
</head>
<body style="background:#F3F4F6;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'Inter',sans-serif">

<div style="max-width:480px;width:100%;padding:24px;text-align:center">

  <img src="<?= BASE_URL ?>public/img/logo.svg" alt="CarniHub" style="height:48px;margin-bottom:24px">

  <?php if (!empty($flash)): ?>
  <div style="padding:12px 14px;border-radius:8px;margin-bottom:20px;font-size:.875rem;<?= $flash['type']==='success' ? 'background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0' : 'background:#FEE2E2;color:#991B1B;border:1px solid #FECACA' ?>">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <div style="background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:40px 32px">
    <div style="font-size:4rem;margin-bottom:16px">📧</div>
    <h1 style="font-size:1.5rem;font-weight:800;color:#111827;margin-bottom:10px">¡Revisa tu correo!</h1>
    <p style="color:#6B7280;line-height:1.7;margin-bottom:24px">
      Te enviamos un enlace de verificación. Haz clic en él para activar tu cuenta.
      El enlace es válido por <strong>24 horas</strong>.
    </p>

    <div style="background:#F9FAFB;border-radius:10px;padding:16px;margin-bottom:24px;font-size:.875rem;color:#374151;text-align:left">
      <p style="font-weight:600;margin-bottom:8px">¿No lo encuentras?</p>
      <ul style="list-style:disc;padding-left:20px;line-height:1.8;color:#6B7280">
        <li>Revisa la carpeta de <strong>Spam</strong> o Correo no deseado</li>
        <li>El remitente es <strong>noreply@carnihub.mx</strong></li>
        <li>Asunto: <em>"Verifica tu cuenta en CarniHub"</em></li>
      </ul>
    </div>

    <a href="<?= BASE_URL ?>auth/login"
       style="display:block;padding:12px;background:#C8102E;color:#fff;border-radius:8px;font-weight:700;text-decoration:none;font-size:.9rem">
      Ir al inicio de sesión
    </a>

    <p style="margin-top:20px;font-size:.8rem;color:#9CA3AF">
      ¿Correo incorrecto?
      <a href="<?= BASE_URL ?>registro/index" style="color:#C8102E;font-weight:600">Regístrate de nuevo</a>
    </p>
  </div>

</div>

</body>
</html>
