<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Terminos y condiciones') ?></title>
  <meta name="robots" content="index, follow">
  <style>
    :root{--bg:#f7f8fa;--card:#fff;--text:#151922;--muted:#667085;--line:#e5e7eb;--accent:#C8102E}
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:var(--bg);color:var(--text);line-height:1.65}
    .legal-shell{max-width:900px;margin:0 auto;padding:32px 18px 48px}
    .legal-card{background:var(--card);border:1px solid var(--line);border-radius:10px;box-shadow:0 16px 48px rgba(15,23,42,.08);overflow:hidden}
    .legal-head{padding:28px 28px 22px;border-bottom:1px solid var(--line)}
    .legal-eyebrow{font-size:.76rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);margin:0 0 8px}
    h1{font-size:clamp(1.7rem,4vw,2.45rem);line-height:1.12;margin:0 0 10px;letter-spacing:0}
    .legal-meta{margin:0;color:var(--muted);font-size:.92rem}
    .legal-body{padding:26px 28px}
    .legal-body h2{font-size:1.05rem;line-height:1.3;margin:24px 0 8px}
    .legal-body h2:first-child{margin-top:0}
    .legal-body p{margin:0 0 14px;color:#344054}
    .legal-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}
    .legal-btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:.88rem;font-weight:700}
    .legal-btn.primary{background:var(--accent);color:#fff}
    .legal-btn.secondary{border:1px solid var(--line);color:#344054;background:#fff}
    @media (max-width:640px){.legal-shell{padding:18px 12px 36px}.legal-head,.legal-body{padding-left:18px;padding-right:18px}}
  </style>
</head>
<body>
  <main class="legal-shell">
    <article class="legal-card">
      <header class="legal-head">
        <p class="legal-eyebrow"><?= htmlspecialchars($terms['brand'] ?? APP_NAME) ?></p>
        <h1><?= htmlspecialchars($terms['title'] ?? 'Terminos y condiciones') ?></h1>
        <p class="legal-meta">
          Version <?= htmlspecialchars($terms['version'] ?? '') ?> · Actualizado el <?= htmlspecialchars($terms['updated_at'] ?? '') ?>
        </p>
      </header>
      <section class="legal-body">
        <?= $terms['html'] ?>
        <div class="legal-actions">
          <a class="legal-btn primary" href="<?= BASE_URL ?>">Ir al inicio</a>
          <?php if (!empty($restaurante['slug'])): ?>
          <a class="legal-btn secondary" href="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug']) ?>">Ver menu</a>
          <?php endif; ?>
        </div>
      </section>
    </article>
  </main>
</body>
</html>
