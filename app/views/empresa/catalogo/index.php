<?php
// Vista: Catálogo de productos para el portal empresa
?>
<!-- Filtros -->
<form method="GET" action="<?= BASE_URL ?>catalogo/index" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:flex-end">
  <div style="flex:1;min-width:180px">
    <label style="font-size:.75rem;font-weight:600;color:#6B7280;display:block;margin-bottom:4px">Buscar</label>
    <input type="text" name="buscar" value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>"
           placeholder="Nombre de producto..."
           style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
  </div>
  <div style="min-width:160px">
    <label style="font-size:.75rem;font-weight:600;color:#6B7280;display:block;margin-bottom:4px">Categoría</label>
    <select name="categoria_id" style="padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;width:100%">
      <option value="">Todas</option>
      <?php foreach ($categorias as $cat): ?>
      <option value="<?= $cat['id'] ?>" <?= ($filtros['categoria_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($cat['nombre']) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" style="padding:9px 20px;background:#374151;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
    Filtrar
  </button>
  <?php
  $rol = $_SESSION['usuario']['rol_slug'] ?? '';
  $puedeComprar = in_array($rol, ['admin_empresa','comprador'], true);
  if ($puedeComprar):
  ?>
  <a href="<?= BASE_URL ?>carrito/index"
     style="padding:9px 20px;background:var(--color-primary);color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem;margin-left:auto">
    Ver carrito
  </a>
  <?php endif; ?>
</form>

<!-- Grid de productos -->
<?php if (empty($productos)): ?>
<div style="text-align:center;padding:40px;color:#6B7280">Sin productos disponibles.</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
  <?php foreach ($productos as $prod): ?>
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;display:flex;flex-direction:column">
    <!-- Imagen -->
    <div style="height:140px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;overflow:hidden">
      <?php if ($prod['imagen']): ?>
        <img src="<?= htmlspecialchars(UPLOAD_URL . $prod['imagen']) ?>" alt="<?= htmlspecialchars($prod['nombre']) ?>" style="width:100%;height:100%;object-fit:cover">
      <?php else: ?>
        <span style="font-size:3rem">🥩</span>
      <?php endif; ?>
    </div>
    <!-- Info -->
    <div style="padding:14px;flex:1">
      <div style="font-size:.75rem;color:#9CA3AF;margin-bottom:4px"><?= htmlspecialchars($prod['categoria_nombre']) ?></div>
      <div style="font-weight:700;font-size:.95rem;color:#111827;margin-bottom:4px"><?= htmlspecialchars($prod['nombre']) ?></div>
      <div style="font-size:.8rem;color:#6B7280;margin-bottom:10px"><?= htmlspecialchars($prod['presentacion']) ?></div>
      <!-- Precio base -->
      <div style="font-size:1.1rem;font-weight:800;color:var(--color-primary)">
        $<?= number_format($prod['precio_base'],2) ?> / <?= $prod['presentacion'] ?>
      </div>
      <!-- Stock -->
      <?php if ($prod['stock'] !== null): ?>
      <div style="font-size:.75rem;color:<?= $prod['stock'] <= $prod['umbral_minimo'] ? '#EF4444' : '#6B7280' ?>;margin-top:4px">
        Stock: <?= number_format($prod['stock'],1) ?> <?= $prod['presentacion'] ?>
        <?php if ($prod['stock'] <= $prod['umbral_minimo']): ?>
          <span style="font-weight:600"> — Bajo</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <!-- Acción -->
    <div style="padding:12px 14px;border-top:1px solid #F3F4F6">
      <a href="<?= BASE_URL ?>catalogo/detalle/<?= $prod['id'] ?>"
         style="font-size:.8rem;color:#6B7280;text-decoration:none">Ver precios escalonados</a>
      <?php if ($puedeComprar): ?>
      <button onclick="agregarAlCarrito(<?= $prod['id'] ?>, '<?= htmlspecialchars(addslashes($prod['nombre'])) ?>')"
              style="float:right;padding:6px 14px;background:var(--color-primary);color:#fff;border:none;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer">
        + Agregar
      </button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function agregarAlCarrito(id, nombre) {
  window.location.href = '<?= BASE_URL ?>carrito/index';
}
</script>
