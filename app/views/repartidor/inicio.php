<?php
// Repartidor views use a dark layout — include a dark header
include ROOT_PATH . '/app/views/components/header_repartidor.php';
?>
<div class="rep-page">
  <!-- Top bar -->
  <div class="rep-topbar">
    <div>
      <div class="rep-greeting">Buenos días,</div>
      <div class="rep-name"><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></div>
    </div>
    <div style="width:40px;height:40px;border-radius:50%;background:#C8102E;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff">
      <?= strtoupper(substr($_SESSION['usuario']['nombre'],0,1)) ?>
    </div>
  </div>

  <!-- Vehículo asignado -->
  <?php if (!empty($chofer['placa'])): ?>
  <div class="rep-card" style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
    <div style="font-size:1.5rem">🚛</div>
    <div>
      <div style="font-size:.75rem;color:#94A3B8">Vehículo asignado</div>
      <div style="font-weight:700"><?= htmlspecialchars($chofer['marca'] . ' ' . $chofer['modelo']) ?></div>
      <div style="font-size:.8rem;color:#94A3B8"><?= htmlspecialchars($chofer['placa']) ?></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- KPIs -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
    <div class="rep-card" style="text-align:center">
      <div style="font-size:2rem;font-weight:800;color:#C8102E"><?= $pendientes ?></div>
      <div style="font-size:.75rem;color:#94A3B8">Pendientes</div>
    </div>
    <div class="rep-card" style="text-align:center">
      <div style="font-size:2rem;font-weight:800;color:#10B981"><?= $entregados ?></div>
      <div style="font-size:.75rem;color:#94A3B8">Entregados</div>
    </div>
  </div>

  <?php if ($rutaHoy): ?>
  <!-- Ruta activa -->
  <div class="rep-card" style="margin-bottom:12px">
    <div style="font-size:.75rem;color:#94A3B8;margin-bottom:4px">Ruta de hoy</div>
    <div style="font-weight:700"><?= htmlspecialchars($rutaHoy['nombre']) ?></div>
    <div style="font-size:.8rem;color:#94A3B8"><?= $rutaHoy['total_entregas'] ?> entregas · <?= $rutaHoy['km_estimados'] ?? '—' ?> km est.</div>
    <a href="<?= BASE_URL ?>repartidor/entregas" class="rep-btn-primary" style="display:block;text-align:center;margin-top:10px;text-decoration:none;padding:10px;border-radius:8px">
      Ver mis entregas →
    </a>
  </div>
  <?php else: ?>
  <div class="rep-card" style="text-align:center;padding:32px;margin-bottom:12px">
    <div style="font-size:2rem;margin-bottom:8px">📭</div>
    <div style="color:#64748B">Sin ruta asignada para hoy</div>
  </div>
  <?php endif; ?>

  <!-- Nav rápido -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <a href="<?= BASE_URL ?>repartidor/mapa" class="rep-card" style="text-align:center;text-decoration:none;padding:16px">
      <div style="font-size:1.5rem">🗺️</div>
      <div style="font-size:.8rem;color:#94A3B8;margin-top:4px">Mapa</div>
    </a>
    <a href="<?= BASE_URL ?>repartidor/historial" class="rep-card" style="text-align:center;text-decoration:none;padding:16px">
      <div style="font-size:1.5rem">📋</div>
      <div style="font-size:.8rem;color:#94A3B8;margin-top:4px">Historial</div>
    </a>
  </div>
</div>

<!-- Bottom nav repartidor -->
<nav class="rep-bottom-nav">
  <a href="<?= BASE_URL ?>repartidor/inicio" class="rep-nav-item active">🏠<span>Inicio</span></a>
  <a href="<?= BASE_URL ?>repartidor/entregas" class="rep-nav-item">📦<span>Entregas</span></a>
  <a href="<?= BASE_URL ?>repartidor/mapa" class="rep-nav-item">🗺️<span>Mapa</span></a>
  <a href="<?= BASE_URL ?>repartidor/historial" class="rep-nav-item">📋<span>Historial</span></a>
  <a href="<?= BASE_URL ?>repartidor/perfil" class="rep-nav-item">👤<span>Perfil</span></a>
</nav>

<?php include ROOT_PATH . '/app/views/components/footer_repartidor.php'; ?>
