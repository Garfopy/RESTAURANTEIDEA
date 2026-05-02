<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h1 style="font-size:1.1rem;font-weight:700;margin:0">Mis pedidos</h1>
  <a href="<?= BASE_URL ?>producto/catalogo" class="btn btn-primary btn-sm">+ Nuevo pedido</a>
</div>

<!-- Filtros -->
<div style="display:flex;gap:8px;margin-bottom:16px;overflow-x:auto;padding-bottom:4px">
  <?php
  $estados = ['todos'=>'Todos','pendiente'=>'Pendiente','confirmado'=>'Confirmado','en_preparacion'=>'En preparación','en_ruta'=>'En ruta','entregado'=>'Entregado','cancelado'=>'Cancelado'];
  $estadoActual = $_GET['estado'] ?? 'todos';
  foreach ($estados as $val => $label):
  ?>
  <a href="?estado=<?= $val ?>" class="btn btn-sm <?= $estadoActual === $val ? 'btn-primary' : 'btn-secondary' ?>" style="white-space:nowrap"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if (empty($pedidos)): ?>
<div class="card" style="text-align:center;padding:48px">
  <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#D1D5DB" style="margin:0 auto 12px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
  <p style="color:#9CA3AF;margin-bottom:12px">No tienes pedidos todavía</p>
  <a href="<?= BASE_URL ?>producto/catalogo" class="btn btn-primary">Ir al catálogo</a>
</div>
<?php else: ?>

<?php foreach ($pedidos as $p): ?>
<?php
$estadoColors = [
  'pendiente' => ['#FEF3C7','#92400E','Pendiente'],
  'confirmado' => ['#DBEAFE','#1E40AF','Confirmado'],
  'en_preparacion' => ['#FDE68A','#92400E','En preparación'],
  'en_ruta' => ['#D1FAE5','#065F46','En ruta'],
  'entregado' => ['#F0FDF4','#166534','Entregado'],
  'cancelado' => ['#FEE2E2','#991B1B','Cancelado'],
];
[$bgColor, $textColor, $label] = $estadoColors[$p['estado']] ?? ['#F3F4F6','#374151',$p['estado']];
?>
<div class="card" style="margin-bottom:10px;padding:14px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
    <div>
      <div style="font-weight:700;font-size:.875rem">#<?= $p['folio'] ?></div>
      <div style="font-size:.75rem;color:#6B7280"><?= date('d/m/Y', strtotime($p['fecha_pedido'])) ?></div>
    </div>
    <span style="background:<?= $bgColor ?>;color:<?= $textColor ?>;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px"><?= $label ?></span>
  </div>
  <div style="display:flex;justify-content:space-between;align-items:center">
    <div style="font-size:.875rem;color:#374151">
      <?php if ($p['fecha_entrega']): ?>
      📅 <span style="color:#6B7280">Entrega:</span> <strong><?= date('d/m/Y', strtotime($p['fecha_entrega'])) ?></strong>
      <?php if ($p['ventana_entrega']): ?>· <?= $p['ventana_entrega'] ?><?php endif; ?>
      <?php endif; ?>
    </div>
    <div style="font-size:1rem;font-weight:800;color:#111827">$<?= number_format($p['total'],0,'.', ',') ?></div>
  </div>
  <div style="display:flex;gap:8px;margin-top:10px">
    <a href="<?= BASE_URL ?>pedido/detalle/<?= $p['id'] ?>" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center">Ver detalle</a>
    <?php if (in_array($p['estado'], ['entregado','cancelado'])): ?>
    <button onclick="reordenar(<?= $p['id'] ?>)" class="btn btn-sm" style="flex:1;justify-content:center;background:#FEF2F2;color:#C8102E;border:1px solid #FECACA">🔄 Reordenar</button>
    <?php endif; ?>
    <?php if ($p['estado'] === 'en_ruta'): ?>
    <a href="<?= BASE_URL ?>pedido/seguimiento/<?= $p['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;justify-content:center">📍 Seguir</a>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<!-- Paginación -->
<?php if ($paginacion['last_page'] > 1): ?>
<div style="display:flex;justify-content:center;gap:6px;margin-top:20px">
  <?php for ($i = 1; $i <= $paginacion['last_page']; $i++): ?>
  <a href="?estado=<?= $estadoActual ?>&pagina=<?= $i ?>" class="btn btn-sm <?= $paginacion['current_page'] == $i ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Bottom nav mobile -->
<nav class="bottom-nav hide-desktop">
  <a href="<?= BASE_URL ?>carrito/inicio" class="bottom-nav-item">🏠 <span>Inicio</span></a>
  <a href="<?= BASE_URL ?>producto/catalogo" class="bottom-nav-item">📦 <span>Catálogo</span></a>
  <a href="<?= BASE_URL ?>pedido/index" class="bottom-nav-item active">📋 <span>Pedidos</span></a>
  <a href="<?= BASE_URL ?>carrito/index" class="bottom-nav-item">🛒 <span>Carrito</span></a>
  <a href="<?= BASE_URL ?>auth/logout" class="bottom-nav-item">👤 <span>Cuenta</span></a>
</nav>

<script>
function reordenar(pedidoId) {
  if (!confirm('¿Agregar este pedido al carrito?')) return;
  fetch('<?= BASE_URL ?>pedido/reordenar/' + pedidoId, { method: 'POST' })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        showToast('Pedido copiado al carrito', 'success');
        setTimeout(() => window.location = '<?= BASE_URL ?>carrito/index', 900);
      } else {
        showToast(d.error || 'Error al reordenar', 'error');
      }
    });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
