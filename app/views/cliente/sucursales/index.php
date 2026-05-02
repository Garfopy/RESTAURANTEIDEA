<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
$colorMap = ['#10B981','#3B82F6','#F59E0B','#8B5CF6','#EC4899'];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h1 style="font-size:1.1rem;font-weight:700;margin:0">Mis sucursales</h1>
  <a href="<?= BASE_URL ?>cliente/solicitudSucursal" class="btn btn-secondary btn-sm">+ Solicitar sucursal</a>
</div>

<?php if (empty($sucursales)): ?>
<div class="card" style="text-align:center;padding:48px">
  <p style="color:#9CA3AF">No hay sucursales registradas</p>
</div>
<?php else: ?>

<div style="display:flex;flex-direction:column;gap:10px">
  <?php foreach ($sucursales as $i => $s):
    $color = $colorMap[$i % count($colorMap)];
  ?>
  <div class="card" style="padding:14px">
    <div style="display:flex;align-items:center;gap:14px">
      <div style="width:44px;height:44px;border-radius:10px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:1.1rem;flex-shrink:0">
        <?= strtoupper(substr($s['nombre'],0,1)) ?>
      </div>
      <div style="flex:1">
        <div style="font-weight:700;font-size:.9rem"><?= htmlspecialchars($s['nombre']) ?></div>
        <div style="font-size:.75rem;color:#6B7280"><?= htmlspecialchars($s['direccion']) ?></div>
        <?php if ($s['ciudad']): ?>
        <div style="font-size:.75rem;color:#9CA3AF"><?= htmlspecialchars($s['ciudad']) ?>, <?= htmlspecialchars($s['estado']) ?></div>
        <?php endif; ?>
        <?php if ($s['contacto_nombre']): ?>
        <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px">👤 <?= htmlspecialchars($s['contacto_nombre']) ?> · 📞 <?= htmlspecialchars($s['contacto_telefono'] ?? '') ?></div>
        <?php endif; ?>
      </div>
      <div style="flex-shrink:0">
        <?php if ($s['activo']): ?>
        <span style="background:#D1FAE5;color:#065F46;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:20px">Activa</span>
        <?php else: ?>
        <span style="background:#F3F4F6;color:#6B7280;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:20px">Inactiva</span>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($s['lat'] && $s['lng']): ?>
    <div style="margin-top:10px">
      <a href="https://maps.google.com/?q=<?= $s['lat'] ?>,<?= $s['lng'] ?>" target="_blank" class="btn btn-secondary btn-sm" style="font-size:.75rem">📍 Ver en mapa</a>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<!-- Bottom nav mobile -->
<nav class="bottom-nav hide-desktop">
  <a href="<?= BASE_URL ?>carrito/inicio" class="bottom-nav-item">🏠 <span>Inicio</span></a>
  <a href="<?= BASE_URL ?>producto/catalogo" class="bottom-nav-item">📦 <span>Catálogo</span></a>
  <a href="<?= BASE_URL ?>pedido/index" class="bottom-nav-item">📋 <span>Pedidos</span></a>
  <a href="<?= BASE_URL ?>carrito/index" class="bottom-nav-item">🛒 <span>Carrito</span></a>
  <a href="<?= BASE_URL ?>auth/logout" class="bottom-nav-item">👤 <span>Cuenta</span></a>
</nav>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
