<?php
include ROOT_PATH . '/app/views/components/header_repartidor.php';
?>
<div class="rep-page">
  <div style="font-weight:700;font-size:1.1rem;margin-bottom:16px;padding-top:12px">Historial de rutas</div>

  <?php if (empty($rutas)): ?>
  <div class="rep-card" style="text-align:center;padding:40px">
    <div style="font-size:2rem;margin-bottom:8px">📋</div>
    <div style="color:#64748B">Sin rutas completadas aún</div>
  </div>
  <?php else: ?>

  <div style="display:flex;flex-direction:column;gap:8px">
    <?php foreach ($rutas as $r):
      $estadoColor = ['pendiente'=>'#F59E0B','en_preparacion'=>'#F59E0B','en_ruta'=>'#3B82F6','completada'=>'#10B981'][$r['estado']] ?? '#64748B';
    ?>
    <div class="rep-card" style="border-left:3px solid <?= $estadoColor ?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
          <div style="font-weight:700;font-size:.875rem"><?= htmlspecialchars($r['nombre']) ?></div>
          <div style="font-size:.75rem;color:#64748B"><?= date('d/m/Y', strtotime($r['fecha'])) ?></div>
        </div>
        <span style="font-size:.7rem;color:<?= $estadoColor ?>;font-weight:600"><?= ucfirst(str_replace('_',' ',$r['estado'])) ?></span>
      </div>
      <div style="display:flex;gap:12px;margin-top:6px;font-size:.75rem;color:#64748B">
        <span>📦 <?= $r['total_entregas'] ?> entregas</span>
        <?php if ($r['km_estimados']): ?>
        <span>🛣️ <?= $r['km_estimados'] ?> km</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<nav class="rep-bottom-nav">
  <a href="<?= BASE_URL ?>repartidor/inicio" class="rep-nav-item">🏠<span>Inicio</span></a>
  <a href="<?= BASE_URL ?>repartidor/entregas" class="rep-nav-item">📦<span>Entregas</span></a>
  <a href="<?= BASE_URL ?>repartidor/mapa" class="rep-nav-item">🗺️<span>Mapa</span></a>
  <a href="<?= BASE_URL ?>repartidor/historial" class="rep-nav-item active">📋<span>Historial</span></a>
  <a href="<?= BASE_URL ?>repartidor/perfil" class="rep-nav-item">👤<span>Perfil</span></a>
</nav>

<?php include ROOT_PATH . '/app/views/components/footer_repartidor.php'; ?>
