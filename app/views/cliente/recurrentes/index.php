<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h1 style="font-size:1.1rem;font-weight:700;margin:0">Pedidos recurrentes</h1>
  <a href="<?= BASE_URL ?>recurrente/crear" class="btn btn-primary btn-sm">+ Crear plantilla</a>
</div>

<p style="font-size:.875rem;color:#6B7280;margin-bottom:16px">Define una plantilla de pedido y recíbela automáticamente cada semana o quincena.</p>

<?php if (empty($recurrentes)): ?>
<div class="card" style="text-align:center;padding:48px">
  <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#D1D5DB" style="margin:0 auto 12px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
  <p style="color:#9CA3AF;margin-bottom:12px">No tienes pedidos recurrentes configurados</p>
  <a href="<?= BASE_URL ?>recurrente/crear" class="btn btn-primary">Crear plantilla</a>
</div>
<?php else: ?>

<?php foreach ($recurrentes as $r): ?>
<div class="card" style="margin-bottom:10px">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px">
    <div>
      <div style="font-weight:700"><?= htmlspecialchars($r['nombre']) ?></div>
      <div style="font-size:.75rem;color:#6B7280;margin-top:2px">
        🔄 <?= ucfirst($r['frecuencia']) ?>
        <?php if ($r['proximo_pedido']): ?>
         · Próximo: <strong><?= date('d/m/Y', strtotime($r['proximo_pedido'])) ?></strong>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($r['pausado']): ?>
    <span style="background:#F3F4F6;color:#6B7280;font-size:.7rem;font-weight:600;padding:3px 10px;border-radius:20px">⏸ Pausado</span>
    <?php else: ?>
    <span style="background:#D1FAE5;color:#065F46;font-size:.7rem;font-weight:600;padding:3px 10px;border-radius:20px">● Activo</span>
    <?php endif; ?>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>recurrente/detalle/<?= $r['id'] ?>" class="btn btn-secondary btn-sm">Ver detalle</a>
    <button onclick="confirmarAhora(<?= $r['id'] ?>)" class="btn btn-sm" style="background:#FEF2F2;color:#C8102E;border:1px solid #FECACA">Pedir ahora</button>
    <?php if ($r['pausado']): ?>
    <button onclick="togglePausa(<?= $r['id'] ?>, 0)" class="btn btn-secondary btn-sm">▶ Reactivar</button>
    <?php else: ?>
    <button onclick="togglePausa(<?= $r['id'] ?>, 1)" class="btn btn-secondary btn-sm">⏸ Pausar</button>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Bottom nav mobile -->
<nav class="bottom-nav hide-desktop">
  <a href="<?= BASE_URL ?>carrito/inicio" class="bottom-nav-item">🏠 <span>Inicio</span></a>
  <a href="<?= BASE_URL ?>producto/catalogo" class="bottom-nav-item">📦 <span>Catálogo</span></a>
  <a href="<?= BASE_URL ?>pedido/index" class="bottom-nav-item">📋 <span>Pedidos</span></a>
  <a href="<?= BASE_URL ?>carrito/index" class="bottom-nav-item">🛒 <span>Carrito</span></a>
  <a href="<?= BASE_URL ?>auth/logout" class="bottom-nav-item">👤 <span>Cuenta</span></a>
</nav>

<script>
function confirmarAhora(id) {
  if (!confirm('¿Crear pedido ahora con esta plantilla?')) return;
  fetch('<?= BASE_URL ?>recurrente/confirmarAhora/' + id, { method: 'POST' })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        showToast('Pedido creado exitosamente', 'success');
        setTimeout(() => window.location = '<?= BASE_URL ?>pedido/detalle/' + d.pedido_id, 1000);
      } else {
        showToast(d.error || 'Error', 'error');
      }
    });
}

function togglePausa(id, pausar) {
  const url = pausar ? '<?= BASE_URL ?>recurrente/pausar/' : '<?= BASE_URL ?>recurrente/activar/';
  fetch(url + id, { method: 'POST' })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
