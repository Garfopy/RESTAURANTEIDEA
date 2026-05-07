<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Mis entregas — CarniHub</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body { background: #111827; color: #F9FAFB; font-family: 'Inter', sans-serif; min-height: 100vh; margin: 0; }
    .header { background: #1F2937; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #374151; }
    .card { background: #1F2937; border-radius: 12px; margin-bottom: 12px; overflow: hidden; }
    .card-parada { padding: 16px; border-left: 4px solid #374151; }
    .card-parada.pendiente { border-left-color: #F59E0B; }
    .card-parada.entregado { border-left-color: #10B981; }
    .card-parada.fallido   { border-left-color: #EF4444; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: .75rem; font-weight: 600; }
    .badge-p { background: #78350F; color: #FCD34D; }
    .badge-e { background: #064E3B; color: #6EE7B7; }
    .badge-f { background: #7F1D1D; color: #FCA5A5; }
    .btn-primary { background: #C8102E; color: #fff; padding: 14px; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; width: 100%; text-decoration: none; display: block; text-align: center; }
    .btn-secondary { background: #374151; color: #F9FAFB; padding: 12px; border: none; border-radius: 10px; font-size: .9rem; font-weight: 600; cursor: pointer; width: 100%; text-decoration: none; display: block; text-align: center; }
  </style>
</head>
<body>

<div class="header">
  <div>
    <div style="font-weight:800;font-size:1rem">CarniHub Repartidor</div>
    <div style="font-size:.75rem;color:#9CA3AF"><?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? '') ?></div>
  </div>
  <a href="<?= BASE_URL ?>auth/logout" style="font-size:.8rem;color:#9CA3AF;text-decoration:none">Salir</a>
</div>

<div style="padding:16px">
  <?php if (!empty($flash)): ?>
  <div style="padding:12px;border-radius:8px;margin-bottom:12px;<?= $flash['type']==='error' ? 'background:#7F1D1D;color:#FCA5A5' : 'background:#064E3B;color:#6EE7B7' ?>">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <h1 style="font-size:1.1rem;font-weight:800;margin-bottom:4px">Entregas de hoy</h1>
  <p style="font-size:.8rem;color:#9CA3AF;margin-bottom:16px"><?= date('d \d\e F \d\e Y') ?></p>

  <?php if (!empty($pedidosDirectos)): ?>
  <!-- Pedidos directos asignados -->
  <div style="margin-bottom:20px">
    <div style="font-size:.72rem;font-weight:700;color:#9CA3AF;letter-spacing:.05em;margin-bottom:8px">PEDIDOS ASIGNADOS DIRECTAMENTE</div>
    <?php foreach ($pedidosDirectos as $pd): ?>
    <div class="card" style="margin-bottom:12px">
      <div style="padding:16px;border-left:4px solid <?= $pd['estado']==='en_ruta' ? '#F59E0B' : '#6D28D9' ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
          <div>
            <div style="font-weight:700;font-size:.95rem;font-family:monospace"><?= htmlspecialchars($pd['folio']) ?></div>
            <div style="font-size:.8rem;color:#9CA3AF;margin-top:2px"><?= htmlspecialchars($pd['empresa_nombre']) ?></div>
          </div>
          <?php if ($pd['estado']==='en_ruta'): ?>
          <span class="badge" style="background:#78350F;color:#FCD34D">En camino</span>
          <?php else: ?>
          <span class="badge" style="background:#3B0764;color:#E9D5FF">Listo para salir</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($pd['direccion_entrega'])): ?>
        <div style="font-size:.8rem;color:#D1D5DB;margin-bottom:8px">📍 <?= htmlspecialchars($pd['direccion_entrega']) ?></div>
        <?php endif; ?>
        <?php if (!empty($pd['fecha_entrega'])): ?>
        <div style="font-size:.75rem;color:#9CA3AF;margin-bottom:10px">📅 Entrega: <?= date('d/m/Y', strtotime($pd['fecha_entrega'])) ?></div>
        <?php endif; ?>

        <?php if ($pd['estado'] === 'en_preparacion'): ?>
        <form method="POST" action="<?= BASE_URL ?>repartidor/iniciarViaje/<?= $pd['id'] ?>"
              onsubmit="return confirm('¿Iniciar el viaje para el pedido <?= htmlspecialchars($pd['folio']) ?>?')">
          <button type="submit" class="btn-primary" style="font-size:.9rem;padding:12px">
            🚀 Empezar viaje
          </button>
        </form>
        <?php else: ?>
        <a href="<?= BASE_URL ?>repartidor/pedidoDirecto/<?= $pd['id'] ?>" class="btn-primary" style="font-size:.9rem;padding:12px">
          📍 Ver entrega en curso
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$rutaHoy && empty($pedidosDirectos)): ?>
    <div style="text-align:center;padding:40px 20px;color:#6B7280">
      <div style="font-size:2.5rem;margin-bottom:12px">📦</div>
      <p style="font-weight:600">No tienes entregas asignadas para hoy.</p>
      <p style="font-size:.85rem;margin-top:4px">Contacta a tu empresa para más información.</p>
      <a href="<?= BASE_URL ?>repartidor/historial" class="btn-secondary" style="margin-top:20px;display:inline-block;width:auto;padding:10px 24px">Ver historial</a>
    </div>

  <?php elseif ($rutaHoy): ?>
    <!-- Resumen de ruta -->
    <div class="card" style="padding:14px 16px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center">
      <div>
        <div style="font-size:.75rem;color:#9CA3AF;margin-bottom:2px">Progreso de ruta</div>
        <div style="font-weight:800;font-size:1rem"><?= (int)$rutaHoy['entregadas'] ?> / <?= (int)$rutaHoy['total_paradas'] ?> entregas</div>
      </div>
      <div style="text-align:right">
        <div style="font-size:.75rem;color:#9CA3AF;margin-bottom:2px">Estado</div>
        <?php
        $eBg = $rutaHoy['estado'] === 'en_curso' ? '#064E3B' : '#1E3A5F';
        $eC  = $rutaHoy['estado'] === 'en_curso' ? '#6EE7B7' : '#93C5FD';
        ?>
        <span class="badge" style="background:<?= $eBg ?>;color:<?= $eC ?>"><?= htmlspecialchars($rutaHoy['estado']) ?></span>
      </div>
    </div>

    <!-- Lista de paradas -->
    <?php foreach ($paradas as $i => $parada): ?>
    <div class="card">
      <div class="card-parada <?= $parada['estado'] ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
          <div>
            <div style="font-weight:700;font-size:.95rem"><?= $i+1 ?>. <?= htmlspecialchars($parada['sucursal_nombre']) ?></div>
            <div style="font-size:.8rem;color:#9CA3AF;margin-top:2px"><?= htmlspecialchars($parada['empresa_nombre']) ?></div>
          </div>
          <?php
          $bClass = match($parada['estado']) {
            'entregado' => 'badge-e',
            'fallido'   => 'badge-f',
            default     => 'badge-p',
          };
          $bLabel = match($parada['estado']) {
            'entregado' => 'Entregado',
            'fallido'   => 'Fallido',
            default     => 'Pendiente',
          };
          ?>
          <span class="badge <?= $bClass ?>"><?= $bLabel ?></span>
        </div>
        <div style="font-size:.8rem;color:#D1D5DB;margin-bottom:10px">
          <span>📍 </span><?= htmlspecialchars($parada['direccion']) ?>
        </div>
        <div style="font-size:.8rem;color:#9CA3AF;margin-bottom:10px">
          Pedido: <span style="color:#F9FAFB;font-weight:600"><?= htmlspecialchars($parada['pedido_folio']) ?></span>
        </div>
        <?php if ($parada['estado'] === 'pendiente'): ?>
        <a href="<?= BASE_URL ?>repartidor/entrega/<?= $parada['id'] ?>" class="btn-primary">
          Registrar entrega
        </a>
        <?php elseif ($parada['hora_entrega']): ?>
        <div style="font-size:.75rem;color:#6EE7B7;text-align:center;margin-top:4px">
          Entregado a las <?= date('H:i', strtotime($parada['hora_entrega'])) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <a href="<?= BASE_URL ?>repartidor/historial" class="btn-secondary" style="margin-top:8px">
      Ver historial de entregas
    </a>
  <?php endif; ?>
</div>

</body>
</html>
