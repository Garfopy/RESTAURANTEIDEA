<?php
include ROOT_PATH . '/app/views/components/header_repartidor.php';
$estadoLabels = [
  'pendiente' => ['🟡','Pendiente','#F59E0B'],
  'en_ruta'   => ['🔵','En ruta','#3B82F6'],
  'entregado' => ['🟢','Entregado','#10B981'],
  'incidente' => ['🔴','Incidente','#EF4444'],
];
?>
<div class="rep-page">
  <div class="rep-topbar">
    <div style="font-weight:700;font-size:1.1rem">Mis entregas de hoy</div>
    <?php if ($ruta): ?>
    <span style="font-size:.75rem;color:#94A3B8"><?= date('d/m/Y', strtotime($ruta['fecha'])) ?></span>
    <?php endif; ?>
  </div>

  <?php if (empty($entregas)): ?>
  <div class="rep-card" style="text-align:center;padding:40px">
    <div style="font-size:2rem;margin-bottom:8px">📭</div>
    <div style="color:#64748B">No hay entregas asignadas para hoy</div>
  </div>
  <?php else: ?>

  <div style="display:flex;flex-direction:column;gap:8px">
    <?php foreach ($entregas as $e):
      [$icon, $label, $color] = $estadoLabels[$e['estado']] ?? ['⚪','—','#6B7280'];
    ?>
    <a href="<?= BASE_URL ?>repartidor/detalle/<?= $e['id'] ?>" style="text-decoration:none">
      <div class="rep-card" style="border-left:3px solid <?= $color ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px">
          <div>
            <div style="font-weight:700;font-size:.9rem"><?= htmlspecialchars($e['empresa_nombre']) ?></div>
            <div style="font-size:.75rem;color:#94A3B8"><?= htmlspecialchars($e['sucursal_nombre']) ?></div>
          </div>
          <div style="display:flex;align-items:center;gap:4px;font-size:.75rem;font-weight:600;color:<?= $color ?>">
            <?= $icon ?> <?= $label ?>
          </div>
        </div>
        <div style="font-size:.75rem;color:#64748B"><?= htmlspecialchars($e['sucursal_dir']) ?></div>
        <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:.8rem">
          <span style="color:#94A3B8">#<?= $e['folio'] ?></span>
          <span style="font-weight:700;color:#F1F5F9">$<?= number_format($e['total'],0,'.', ',') ?></span>
        </div>
        <?php if ($e['hora_estimada']): ?>
        <div style="font-size:.7rem;color:#64748B;margin-top:4px">🕐 Est. <?= substr($e['hora_estimada'],0,5) ?></div>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<nav class="rep-bottom-nav">
  <a href="<?= BASE_URL ?>repartidor/inicio" class="rep-nav-item">🏠<span>Inicio</span></a>
  <a href="<?= BASE_URL ?>repartidor/entregas" class="rep-nav-item active">📦<span>Entregas</span></a>
  <a href="<?= BASE_URL ?>repartidor/mapa" class="rep-nav-item">🗺️<span>Mapa</span></a>
  <a href="<?= BASE_URL ?>repartidor/historial" class="rep-nav-item">📋<span>Historial</span></a>
  <a href="<?= BASE_URL ?>repartidor/perfil" class="rep-nav-item">👤<span>Perfil</span></a>
</nav>

<?php include ROOT_PATH . '/app/views/components/footer_repartidor.php'; ?>
