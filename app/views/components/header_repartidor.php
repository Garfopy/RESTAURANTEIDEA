<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>CarniHub Repartidor</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/carnihub.css">
  <style>
    body { background:#0F1117; color:#F1F5F9; font-family:'Inter',sans-serif; margin:0; padding:0 0 80px; }
    .rep-page { max-width:480px; margin:0 auto; padding:16px; }
    .rep-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 0 20px; }
    .rep-greeting { font-size:.8rem; color:#94A3B8; }
    .rep-name { font-size:1.1rem; font-weight:700; }
    .rep-card { background:#1E2130; border-radius:12px; padding:14px; }
    .rep-btn-primary { background:#C8102E; color:#fff; font-weight:700; font-size:.875rem; cursor:pointer; border:none; }
    .rep-btn-primary:hover { background:#A00D24; }
    .rep-bottom-nav { position:fixed; bottom:0; left:0; right:0; background:#1E2130; border-top:1px solid #2D3348; display:flex; z-index:100; }
    .rep-nav-item { flex:1; display:flex; flex-direction:column; align-items:center; gap:2px; padding:10px 0; text-decoration:none; color:#64748B; font-size:.6rem; }
    .rep-nav-item span { font-size:.6rem; }
    .rep-nav-item.active { color:#C8102E; }
    .rep-badge { background:#C8102E; color:#fff; border-radius:50%; width:18px; height:18px; font-size:.65rem; font-weight:800; display:flex; align-items:center; justify-content:center; margin-left:4px; }
  </style>
</head>
<body>
