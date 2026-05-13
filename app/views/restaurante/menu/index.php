<?php ob_start(); ?>
<style>
.menu-cat-header {
  display:flex;justify-content:space-between;align-items:center;
  padding:12px 0 10px;margin-bottom:12px;border-bottom:2px solid #F3F4F6;
}
.menu-cat-title {
  font-size:1.05rem;font-weight:700;color:#111827;display:flex;align-items:center;gap:8px;
}
.menu-cards {
  display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:28px;
}
.menu-card {
  background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;overflow:hidden;
  transition:box-shadow .15s,border-color .15s;
}
.menu-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08);border-color:#D1D5DB; }
.menu-card-img {
  height:90px;background:#F9FAFB;display:flex;align-items:center;justify-content:center;
  font-size:2rem;overflow:hidden;
}
.menu-card-img img { width:100%;height:100%;object-fit:cover; }
.menu-card-body { padding:12px; }
.menu-card-name { font-weight:700;font-size:.9rem;color:#111827;margin-bottom:3px;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.menu-card-desc { font-size:.75rem;color:#9CA3AF;margin-bottom:8px;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
.menu-card-footer { display:flex;justify-content:space-between;align-items:center;
  padding:8px 12px;border-top:1px solid #F3F4F6;gap:6px;flex-wrap:wrap; }
.menu-card-price { font-weight:800;font-size:.95rem;color:#111827; }
.menu-card-actions { display:flex;gap:4px;align-items:center;flex-wrap:wrap; }
.menu-card-actions a { font-size:.72rem;text-decoration:none;padding:3px 8px;border-radius:6px;
  transition:background .1s; }
</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div style="display:flex;gap:10px">
    <a href="<?= BASE_URL ?>rest-menu/form" class="btn btn-primary">+ Nuevo Platillo</a>
    <button onclick="abrirModalCat()" class="btn btn-outline">+ Categoría</button>
  </div>
  <a href="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug'] ?? '') ?>"
     target="_blank" style="font-size:.8rem;color:var(--cp);font-weight:600;text-decoration:none">
    Ver menú público ↗
  </a>
</div>

<?php if (!empty($flash)): ?>
<div class="flash flash-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" style="margin-bottom:16px">
  <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<?php if (empty($categorias)): ?>
<div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:10px;padding:16px;margin-bottom:16px;font-size:.875rem;color:#92400E">
  <strong>Sin categorías.</strong> Crea al menos una categoría antes de agregar platillos.
  <button onclick="abrirModalCat()"
    style="margin-left:12px;padding:4px 12px;background:#F59E0B;color:#fff;border:none;border-radius:6px;font-size:.8rem;cursor:pointer">
    Crear categoría
  </button>
</div>
<?php endif; ?>

<?php if (empty($platillos) && !empty($categorias)): ?>
<div style="text-align:center;padding:48px 20px;color:#9CA3AF">
  <div style="font-size:3rem;margin-bottom:12px">🍽️</div>
  <div style="font-weight:600;color:#374151;margin-bottom:6px">Aún no hay platillos</div>
  <div style="font-size:.875rem;margin-bottom:18px">Agrega tu primer platillo para que aparezca en el menú.</div>
  <a href="<?= BASE_URL ?>rest-menu/form" class="btn btn-primary">+ Nuevo Platillo</a>
</div>
<?php else: ?>

<?php foreach ($categorias as $cat):
  $platsCat = array_values(array_filter($platillos, fn($p) => $p['categoria_id'] == $cat['id']));
?>
<div style="margin-bottom:24px">
  <div class="menu-cat-header">
    <div class="menu-cat-title">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
      <?= htmlspecialchars($cat['nombre']) ?>
      <span style="font-size:.72rem;font-weight:400;color:#9CA3AF;background:#F3F4F6;border-radius:99px;padding:2px 8px">
        <?= count($platsCat) ?> platillo<?= count($platsCat) != 1 ? 's' : '' ?>
      </span>
    </div>
  </div>

  <div class="menu-cards">
    <?php foreach ($platsCat as $p): ?>
    <div class="menu-card">
      <div class="menu-card-img">
        <?php if ($p['imagen']): ?>
        <img src="<?= BASE_URL . htmlspecialchars($p['imagen']) ?>" alt="">
        <?php else: ?>
        🍽
        <?php endif; ?>
      </div>
      <div class="menu-card-body">
        <div class="menu-card-name" title="<?= htmlspecialchars($p['nombre']) ?>">
          <?= htmlspecialchars($p['nombre']) ?>
        </div>
        <?php if ($p['descripcion']): ?>
        <div class="menu-card-desc"><?= htmlspecialchars($p['descripcion']) ?></div>
        <?php endif; ?>
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
          <span style="padding:2px 8px;border-radius:99px;font-size:.68rem;font-weight:700;
            background:<?= $p['disponible'] ? '#DCFCE7' : '#FEE2E2' ?>;
            color:<?= $p['disponible'] ? '#166534' : '#991B1B' ?>">
            <?= $p['disponible'] ? 'Disponible' : 'No disponible' ?>
          </span>
          <?php if ($p['tiempo_preparacion_min'] ?? 0): ?>
          <span style="font-size:.68rem;color:#9CA3AF">⏱ <?= (int)$p['tiempo_preparacion_min'] ?>min</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="menu-card-footer">
        <span class="menu-card-price">$<?= number_format((float)$p['precio'],2) ?></span>
        <div class="menu-card-actions">
          <a href="<?= BASE_URL ?>rest-menu/form/<?= $p['id'] ?>"
             style="background:#EFF6FF;color:#1D4ED8">Editar</a>
          <a href="<?= BASE_URL ?>rest-menu/detalle/<?= $p['id'] ?>"
             style="background:#F0FDF4;color:#16A34A">Ver costos</a>
          <a href="<?= BASE_URL ?>rest-menu/toggleDisponible/<?= $p['id'] ?>"
             style="background:#F9FAFB;color:#6B7280">
            <?= $p['disponible'] ? 'Pausar' : 'Activar' ?>
          </a>
          <a href="<?= BASE_URL ?>rest-menu/eliminar/<?= $p['id'] ?>"
             onclick="return confirm('¿Desactivar este platillo?')"
             style="background:#FEF2F2;color:#EF4444">Quitar</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($platsCat)): ?>
    <div style="grid-column:1/-1;padding:20px;color:#9CA3AF;font-size:.85rem;font-style:italic;
                border:2px dashed #E5E7EB;border-radius:12px;text-align:center">
      Sin platillos — <a href="<?= BASE_URL ?>rest-menu/form" style="color:var(--cp)">agregar uno</a>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- Modal categoría -->
<div id="modalCat" class="rst-modal-backdrop" onclick="if(event.target===this)cerrarModalCat()">
  <div class="rst-modal" style="width:420px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h3 style="font-weight:700;color:#111827;font-size:1.05rem;margin:0">Nueva Categoría de Menú</h3>
      <button onclick="cerrarModalCat()"
        style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#6B7280;padding:4px">✕</button>
    </div>
    <div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:10px 12px;margin-bottom:16px;font-size:.8rem;color:#92400E">
      <strong>Nota:</strong> Estas categorías son para el <strong>menú del cliente</strong> (Bebidas, Entradas, etc.). Las categorías de ingredientes del inventario son independientes.
    </div>
    <form method="POST" action="<?= BASE_URL ?>rest-menu/guardarCategoria" onsubmit="return validarCat()">
      <input type="hidden" name="id" value="">
      <div class="form-group">
        <label class="form-label">Nombre de la categoría *</label>
        <input type="text" name="nombre" id="catNombre" class="form-input"
               placeholder="Ej: Entradas, Bebidas, Postres">
        <div id="catError" style="font-size:.78rem;color:#EF4444;margin-top:4px;display:none">El nombre es obligatorio.</div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid #F3F4F6">
        <button type="button" onclick="cerrarModalCat()" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar categoría</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModalCat() {
  document.getElementById('modalCat').classList.add('open');
  setTimeout(() => document.getElementById('catNombre').focus(), 100);
}
function cerrarModalCat() {
  document.getElementById('modalCat').classList.remove('open');
}
function validarCat() {
  const n = document.getElementById('catNombre').value.trim();
  if (!n) {
    document.getElementById('catError').style.display = 'block';
    document.getElementById('catNombre').focus();
    return false;
  }
  return true;
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') cerrarModalCat();
});
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
