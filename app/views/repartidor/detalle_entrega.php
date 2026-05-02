<?php
include ROOT_PATH . '/app/views/components/header_repartidor.php';
?>
<div class="rep-page">
  <div style="margin-bottom:16px">
    <a href="<?= BASE_URL ?>repartidor/entregas" style="font-size:.875rem;color:#94A3B8;text-decoration:none">← Entregas</a>
  </div>

  <div class="rep-card" style="margin-bottom:12px">
    <div style="font-size:.75rem;color:#94A3B8;margin-bottom:2px">Cliente</div>
    <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($entrega['empresa_nombre']) ?></div>
    <div style="font-size:.8rem;color:#94A3B8;margin-top:2px">#<?= $entrega['folio'] ?></div>
  </div>

  <!-- Dirección -->
  <div class="rep-card" style="margin-bottom:12px">
    <div style="font-size:.75rem;color:#94A3B8;margin-bottom:4px">📍 Dirección de entrega</div>
    <div style="font-weight:600"><?= htmlspecialchars($entrega['sucursal_nombre']) ?></div>
    <div style="font-size:.8rem;color:#94A3B8"><?= htmlspecialchars($entrega['sucursal_dir']) ?></div>
    <?php if ($entrega['lat'] && $entrega['lng']): ?>
    <a href="https://maps.google.com/?q=<?= $entrega['lat'] ?>,<?= $entrega['lng'] ?>" target="_blank"
       class="rep-btn-primary" style="display:inline-block;margin-top:8px;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:.8rem">
      🧭 Cómo llegar
    </a>
    <?php endif; ?>
  </div>

  <!-- Contacto -->
  <?php if ($entrega['contacto_nombre']): ?>
  <div class="rep-card" style="margin-bottom:12px">
    <div style="font-size:.75rem;color:#94A3B8;margin-bottom:4px">👤 Contacto</div>
    <div style="font-weight:600"><?= htmlspecialchars($entrega['contacto_nombre']) ?></div>
    <?php if ($entrega['contacto_telefono']): ?>
    <a href="tel:<?= $entrega['contacto_telefono'] ?>" style="font-size:.8rem;color:#3B82F6;text-decoration:none">
      📞 <?= htmlspecialchars($entrega['contacto_telefono']) ?>
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Total + notas -->
  <div class="rep-card" style="margin-bottom:12px">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <span style="color:#94A3B8;font-size:.875rem">Total del pedido</span>
      <span style="font-size:1.25rem;font-weight:800;color:#C8102E">$<?= number_format($entrega['total'],0,'.', ',') ?></span>
    </div>
    <?php if ($entrega['ventana_entrega']): ?>
    <div style="font-size:.75rem;color:#64748B;margin-top:4px">🕐 Ventana: <?= $entrega['ventana_entrega'] ?></div>
    <?php endif; ?>
    <?php if ($entrega['notas']): ?>
    <div style="margin-top:8px;padding:8px;background:#2D3348;border-radius:6px;font-size:.75rem;color:#94A3B8">
      📝 <?= htmlspecialchars($entrega['notas']) ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Acciones -->
  <?php if ($entrega['estado'] === 'pendiente'): ?>
  <button onclick="iniciarEntrega(<?= $entrega['id'] ?>)" class="rep-btn-primary" style="width:100%;padding:14px;border-radius:10px;font-size:.875rem;margin-bottom:8px">
    🚀 Iniciar entrega
  </button>
  <?php elseif ($entrega['estado'] === 'en_ruta'): ?>
  <a href="<?= BASE_URL ?>repartidor/enProgreso/<?= $entrega['id'] ?>" class="rep-btn-primary"
     style="display:block;text-align:center;padding:14px;border-radius:10px;font-size:.875rem;margin-bottom:8px;text-decoration:none">
    ✍️ Completar entrega
  </a>
  <?php elseif ($entrega['estado'] === 'entregado'): ?>
  <div style="background:#064E3B;border-radius:10px;padding:14px;text-align:center;font-weight:700;color:#10B981">
    ✅ Entregado
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

<script>
function iniciarEntrega(id) {
  fetch('<?= BASE_URL ?>repartidor/iniciarEntrega/' + id, { method: 'POST' })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer_repartidor.php'; ?>
