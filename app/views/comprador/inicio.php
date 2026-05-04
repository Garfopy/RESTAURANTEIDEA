<?php
// Vista: Portal de inicio del comprador
$baseUrl = BASE_URL;
$usuario = $_SESSION['usuario'] ?? [];
$estadoLabel = [
    'pendiente'  => ['Pendiente',  '#FEF3C7','#92400E'],
    'aprobado'   => ['Aprobado',   '#D1FAE5','#065F46'],
    'confirmado' => ['Confirmado', '#DBEAFE','#1E40AF'],
    'en_ruta'    => ['En ruta',    '#EDE9FE','#5B21B6'],
    'entregado'  => ['Entregado',  '#D1FAE5','#065F46'],
    'cancelado'  => ['Cancelado',  '#F3F4F6','#6B7280'],
];
?>

<!-- Bienvenida -->
<div style="background:linear-gradient(135deg,var(--color-primary),#991B1B);border-radius:12px;padding:24px;color:#fff;margin-bottom:24px">
  <h2 style="font-size:1.25rem;font-weight:800;margin-bottom:4px">
    Hola, <?= htmlspecialchars($usuario['nombre'] ?? 'Comprador') ?> 👋
  </h2>
  <p style="font-size:.875rem;opacity:.85">¿Qué necesitas hoy? Explora el catálogo o revisa tus pedidos.</p>
  <div style="margin-top:16px;display:flex;gap:10px">
    <a href="<?= $baseUrl ?>catalogo/index"
       style="padding:9px 20px;background:#fff;color:var(--color-primary);border-radius:8px;font-weight:700;text-decoration:none;font-size:.875rem">
      Ver catálogo
    </a>
    <a href="<?= $baseUrl ?>carrito/index"
       style="padding:9px 20px;background:rgba(255,255,255,.2);color:#fff;border-radius:8px;font-weight:600;text-decoration:none;font-size:.875rem;border:1px solid rgba(255,255,255,.3)">
      Hacer pedido
    </a>
  </div>
</div>

<!-- Pedidos en ruta -->
<?php if (!empty($enRuta)): ?>
<div style="background:#EDE9FE;border:1px solid #C4B5FD;border-radius:10px;padding:16px;margin-bottom:20px">
  <div style="font-weight:700;color:#5B21B6;margin-bottom:10px;font-size:.875rem">
    🚚 <?= count($enRuta) ?> pedido(s) en camino a ti
  </div>
  <div style="display:flex;flex-direction:column;gap:8px">
    <?php foreach ($enRuta as $pr): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;background:#fff;padding:10px 14px;border-radius:8px">
      <span style="font-size:.875rem;font-weight:600;color:#5B21B6"><?= htmlspecialchars($pr['folio']) ?></span>
      <a href="<?= $baseUrl ?>pedido/tracking/<?= $pr['id'] ?>"
         style="padding:5px 14px;background:#7C3AED;color:#fff;border-radius:6px;text-decoration:none;font-size:.78rem;font-weight:600">
        Rastrear en mapa
      </a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Grid: últimos pedidos + acceso rápido -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">

  <!-- Últimos pedidos -->
  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden">
    <div style="padding:14px 16px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between">
      <span style="font-weight:700;color:#111827">Mis últimos pedidos</span>
      <a href="<?= $baseUrl ?>pedido/index" style="font-size:.8rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver historial →</a>
    </div>
    <?php if (empty($ultimosPedidos)): ?>
      <div style="padding:32px;text-align:center;color:#9CA3AF">
        <p style="font-size:1rem;font-weight:600">Aún no tienes pedidos</p>
        <p style="font-size:.875rem;margin-top:4px">Haz tu primer pedido en el catálogo.</p>
        <a href="<?= $baseUrl ?>catalogo/index"
           style="display:inline-block;margin-top:12px;padding:9px 20px;background:var(--color-primary);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
          Explorar catálogo
        </a>
      </div>
    <?php else: ?>
    <div style="padding:16px;display:flex;flex-direction:column;gap:8px">
      <?php foreach ($ultimosPedidos as $ped):
        [$lb, $bg, $tx] = $estadoLabel[$ped['estado']] ?? ['—','#F3F4F6','#6B7280'];
      ?>
      <a href="<?= $baseUrl ?>pedido/detalle/<?= $ped['id'] ?>"
         style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border:1px solid #E5E7EB;border-radius:8px;text-decoration:none;transition:background .15s"
         onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
        <div>
          <div style="font-size:.875rem;font-weight:700;color:#111827"><?= htmlspecialchars($ped['folio']) ?></div>
          <div style="font-size:.75rem;color:#6B7280"><?= date('d/m/Y', strtotime($ped['created_at'])) ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:12px">
          <span style="font-size:.875rem;font-weight:700;color:#111827">$<?= number_format($ped['total'], 2) ?></span>
          <span style="padding:3px 10px;border-radius:999px;background:<?= $bg ?>;color:<?= $tx ?>;font-size:.7rem;font-weight:600"><?= $lb ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Acceso rápido -->
  <div style="display:flex;flex-direction:column;gap:12px">
    <a href="<?= $baseUrl ?>catalogo/index"
       style="display:flex;align-items:center;gap:12px;padding:16px;background:#fff;border-radius:10px;border:1px solid #E5E7EB;text-decoration:none">
      <span style="font-size:1.5rem">📋</span>
      <div>
        <div style="font-weight:700;color:#111827;font-size:.875rem">Catálogo</div>
        <div style="font-size:.75rem;color:#6B7280">Ver todos los productos</div>
      </div>
    </a>
    <a href="<?= $baseUrl ?>carrito/index"
       style="display:flex;align-items:center;gap:12px;padding:16px;background:#fff;border-radius:10px;border:1px solid #E5E7EB;text-decoration:none">
      <span style="font-size:1.5rem">🛒</span>
      <div>
        <div style="font-weight:700;color:#111827;font-size:.875rem">Nuevo pedido</div>
        <div style="font-size:.75rem;color:#6B7280">Agregar al carrito</div>
      </div>
    </a>
    <a href="<?= $baseUrl ?>pedido/index"
       style="display:flex;align-items:center;gap:12px;padding:16px;background:#fff;border-radius:10px;border:1px solid #E5E7EB;text-decoration:none">
      <span style="font-size:1.5rem">📦</span>
      <div>
        <div style="font-weight:700;color:#111827;font-size:.875rem">Mis pedidos</div>
        <div style="font-size:.75rem;color:#6B7280">Historial completo</div>
      </div>
    </a>
    <a href="<?= $baseUrl ?>cuenta/perfil"
       style="display:flex;align-items:center;gap:12px;padding:16px;background:#fff;border-radius:10px;border:1px solid #E5E7EB;text-decoration:none">
      <span style="font-size:1.5rem">👤</span>
      <div>
        <div style="font-weight:700;color:#111827;font-size:.875rem">Mi perfil</div>
        <div style="font-size:.75rem;color:#6B7280">Datos y contraseña</div>
      </div>
    </a>
  </div>
</div>
