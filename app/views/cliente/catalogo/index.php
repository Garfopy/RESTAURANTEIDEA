<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
?>
<div style="margin-bottom:16px">
  <h1 style="font-size:1.25rem;font-weight:700;margin:0">Catálogo de productos</h1>
</div>

<!-- Search + filter -->
<div style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
  <form method="GET" style="flex:1;min-width:200px;display:flex;gap:8px">
    <input type="text" name="q" class="form-control" placeholder="Buscar productos..." value="<?= htmlspecialchars($filtros['busqueda']) ?>" style="flex:1">
    <button type="submit" class="btn btn-secondary btn-sm">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </button>
  </form>
</div>

<!-- Category tabs -->
<div style="display:flex;gap:6px;overflow-x:auto;padding-bottom:4px;margin-bottom:16px;scrollbar-width:none">
  <a href="<?= BASE_URL ?>producto/catalogo?cat=todos" class="btn btn-sm <?= $filtros['categoria']==='todos'?'btn-primary':'btn-secondary' ?>">Todos</a>
  <?php foreach ($categorias as $cat): ?>
  <a href="?cat=<?= $cat['slug'] ?>" class="btn btn-sm <?= $filtros['categoria']===$cat['slug']?'btn-primary':'btn-secondary' ?>">
    <?= $cat['nombre'] ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Product grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:80px">
  <?php foreach ($productos as $p): ?>
  <div class="card" style="padding:0;overflow:hidden;cursor:pointer" onclick="location.href='<?= BASE_URL ?>producto/detalle/<?= $p['id'] ?>'">
    <div style="height:140px;background:#F3F4F6;overflow:hidden;position:relative">
      <?php if ($p['imagen']): ?>
      <img src="<?= UPLOAD_URL ?>productos/<?= htmlspecialchars($p['imagen']) ?>"
           style="width:100%;height:100%;object-fit:cover" loading="lazy">
      <?php else: ?>
      <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
        <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#D1D5DB"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      </div>
      <?php endif; ?>
    </div>
    <div style="padding:10px">
      <div style="font-weight:600;font-size:.875rem;margin-bottom:2px"><?= htmlspecialchars($p['nombre']) ?></div>
      <div style="font-size:.75rem;color:#6B7280;margin-bottom:6px"><?= $p['categoria_nombre'] ?></div>
      <div style="font-size:.8rem;color:#C8102E;font-weight:700">Desde $<?= number_format($p['precio_minimo'] ?? $p['precio_base'],0) ?>/kg</div>
      <button onclick="event.stopPropagation();abrirProducto(<?= $p['id'] ?>)"
        style="width:100%;margin-top:8px;background:#C8102E;color:#fff;border:none;border-radius:6px;padding:6px;font-size:.75rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px">
        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Agregar
      </button>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($productos)): ?>
  <div style="grid-column:1/-1;text-align:center;padding:40px;color:#9CA3AF">No se encontraron productos</div>
  <?php endif; ?>
</div>

<!-- Bottom nav mobile -->
<nav class="bottom-nav hide-desktop">
  <a href="<?= BASE_URL ?>carrito/inicio" class="bottom-nav-item">
    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    Inicio
  </a>
  <a href="<?= BASE_URL ?>producto/catalogo" class="bottom-nav-item active">
    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
    Catálogo
  </a>
  <a href="<?= BASE_URL ?>pedido/index" class="bottom-nav-item">
    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
    Pedidos
  </a>
  <a href="<?= BASE_URL ?>carrito/index" class="bottom-nav-item">
    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    Carrito
  </a>
  <a href="<?= BASE_URL ?>auth/logout" class="bottom-nav-item">
    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
    Cuenta
  </a>
</nav>

<script>
function abrirProducto(id) {
  window.location = '<?= BASE_URL ?>producto/detalle/' + id;
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
