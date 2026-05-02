<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
$empresa = $_SESSION['empresa'] ?? [];
?>
<!-- Home cliente -->
<div style="margin-bottom:20px">
  <h1 style="font-size:1.25rem;font-weight:700;margin:0">
    Hola, <?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? '') ?> 👋
  </h1>
  <p style="color:#6B7280;margin:4px 0 0;font-size:.875rem">¿Qué bueno verte de nuevo!</p>
</div>

<!-- Credit card -->
<?php if (!empty($empresa)): ?>
<div style="background:linear-gradient(135deg,#C8102E,#8B0A1F);border-radius:14px;padding:20px;color:#fff;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between">
  <div>
    <div style="font-size:.75rem;opacity:.8;margin-bottom:4px">Crédito disponible</div>
    <div style="font-size:1.75rem;font-weight:800">$<?= number_format(max(0, $empresa['limite_credito'] - $empresa['saldo_credito']),2) ?></div>
    <?php if (!$empresa['credito_activo']): ?>
    <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:2px 10px;font-size:.75rem;margin-top:6px;display:inline-block">Crédito desactivado</span>
    <?php endif; ?>
  </div>
  <?php if (!$empresa['credito_activo']): ?>
  <span style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);border-radius:8px;padding:6px 14px;font-size:.8rem;cursor:pointer">
    Activar crédito
  </span>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Próxima entrega -->
<?php if (!empty($recurrentes)): ?>
<div class="card" style="margin-bottom:16px;padding:14px 16px">
  <div style="font-size:.75rem;color:#6B7280;margin-bottom:6px">Mi siguiente entrega</div>
  <div style="display:flex;align-items:center;gap:10px">
    <div style="width:36px;height:36px;background:#FEE2E2;border-radius:8px;display:flex;align-items:center;justify-content:center">
      <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#C8102E"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    </div>
    <div>
      <div style="font-weight:600;font-size:.875rem"><?= $recurrentes[0]['proximo_pedido'] ?? '—' ?></div>
      <div style="font-size:.75rem;color:#6B7280">08:00 am - 2:00 pm</div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Último pedido -->
<?php if ($ultimoPedido): ?>
<div class="card" style="margin-bottom:16px">
  <div style="font-size:.75rem;color:#6B7280;margin-bottom:10px">Último pedido</div>
  <div style="display:flex;align-items:center;justify-content:space-between">
    <div>
      <div style="font-weight:700;font-size:.9rem"><?= $ultimoPedido['folio'] ?></div>
      <div style="font-size:.75rem;color:#9CA3AF"><?= $ultimoPedido['fecha_pedido'] ?></div>
    </div>
    <div style="text-align:right">
      <div style="font-weight:700">$<?= number_format($ultimoPedido['total'],0,'.', ',') ?></div>
      <?php
      $ec=['pendiente'=>'badge-warning','confirmado'=>'badge-blue','en_preparacion'=>'badge-orange','en_ruta'=>'badge-info','entregado'=>'badge-success','cancelado'=>'badge-danger'];
      $el=['pendiente'=>'Pendiente','confirmado'=>'Confirmado','en_preparacion'=>'En preparación','en_ruta'=>'En ruta','entregado'=>'Entregado','cancelado'=>'Cancelado'];
      ?>
      <span class="badge <?= $ec[$ultimoPedido['estado']] ?? 'badge-gray' ?>"><?= $el[$ultimoPedido['estado']] ?? '' ?></span>
    </div>
  </div>
  <div style="display:flex;gap:8px;margin-top:12px">
    <a href="<?= BASE_URL ?>pedido/detalle/<?= $ultimoPedido['id'] ?>" class="btn btn-sm btn-secondary" style="flex:1;justify-content:center">Ver pedido</a>
    <a href="<?= BASE_URL ?>pedido/reordenar/<?= $ultimoPedido['id'] ?>" class="btn btn-sm btn-primary" style="flex:1;justify-content:center">Reordenar</a>
  </div>
</div>
<?php endif; ?>

<!-- Quick actions -->
<div class="card" style="margin-bottom:16px">
  <div style="font-size:.75rem;color:#6B7280;margin-bottom:12px">Acciones rápidas</div>
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px">
    <?php
    $acciones = [
      ['icon'=>'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z','label'=>'Hacer pedido','url'=>'carrito/index'],
      ['icon'=>'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15','label'=>'Recurrentes','url'=>'recurrente/index'],
      ['icon'=>'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z','label'=>'Sucursales','url'=>'sucursal/index'],
      ['icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2','label'=>'Historial','url'=>'pedido/index'],
    ];
    foreach ($acciones as $a):
    ?>
    <a href="<?= BASE_URL . $a['url'] ?>" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px;border-radius:10px;background:#F9FAFB;text-decoration:none;color:#374151;font-size:.7rem;font-weight:600;text-align:center">
      <div style="width:36px;height:36px;background:#FEE2E2;border-radius:8px;display:flex;align-items:center;justify-content:center">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#C8102E"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $a['icon'] ?>"/></svg>
      </div>
      <?= $a['label'] ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Bottom nav mobile -->
<nav class="bottom-nav hide-desktop">
  <?php
  $navItems = [
    ['url'=>'carrito/inicio','icon'=>'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6','label'=>'Inicio','active'=>'inicio'],
    ['url'=>'producto/catalogo','icon'=>'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z','label'=>'Catálogo','active'=>'catalogo'],
    ['url'=>'pedido/index','icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2','label'=>'Pedidos','active'=>'pedido'],
    ['url'=>'carrito/index','icon'=>'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z','label'=>'Carrito','active'=>'carrito'],
    ['url'=>'auth/logout','icon'=>'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1','label'=>'Cuenta','active'=>''],
  ];
  foreach ($navItems as $n):
  ?>
  <a href="<?= BASE_URL . $n['url'] ?>" class="bottom-nav-item <?= ($ctrlSlug??'')===$n['active']?'active':'' ?>">
    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $n['icon'] ?>"/></svg>
    <?= $n['label'] ?>
  </a>
  <?php endforeach; ?>
</nav>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
