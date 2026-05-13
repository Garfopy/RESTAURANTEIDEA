<?php ob_start(); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div style="display:flex;gap:10px">
    <a href="<?= BASE_URL ?>rest-menu/form"
       class="btn btn-primary">
      + Nuevo Platillo
    </a>
    <button onclick="abrirModalCat()" class="btn btn-outline">
      + Categoría
    </button>
  </div>
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

<!-- Platillos por categoría -->
<?php foreach ($categorias as $cat): ?>
<?php $platsCat = array_filter($platillos, fn($p) => $p['categoria_id'] == $cat['id']); ?>
<div class="rst-card" style="padding:0;margin-bottom:16px;overflow:hidden">
  <div style="padding:14px 20px;background:#F9FAFB;border-bottom:1px solid #E5E7EB;display:flex;justify-content:space-between;align-items:center">
    <span style="font-weight:600;color:#111827"><?= htmlspecialchars($cat['nombre']) ?></span>
    <span style="font-size:.8rem;color:#9CA3AF"><?= count($platsCat) ?> platillo<?= count($platsCat) != 1 ? 's' : '' ?></span>
  </div>
  <table style="width:100%;border-collapse:collapse;font-size:.875rem">
    <tbody>
    <?php foreach ($platsCat as $p): ?>
    <tr style="border-bottom:1px solid #F9FAFB;transition:background .1s" onmouseover="this.style.background='#FAFAFA'" onmouseout="this.style.background=''">
      <td style="padding:12px 20px;font-weight:500;color:#111827"><?= htmlspecialchars($p['nombre']) ?></td>
      <td style="padding:12px 20px;color:#6B7280;font-size:.82rem;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['descripcion'] ?? '—') ?></td>
      <td style="padding:12px 20px;font-weight:700;color:#111827">$<?= number_format((float)$p['precio'],2) ?></td>
      <td style="padding:12px 20px">
        <span style="padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:600;
          background:<?= $p['disponible'] ? '#DCFCE7' : '#FEE2E2' ?>;color:<?= $p['disponible'] ? '#166534' : '#991B1B' ?>">
          <?= $p['disponible'] ? 'Disponible' : 'No disponible' ?>
        </span>
      </td>
      <td style="padding:12px 20px;white-space:nowrap">
        <a href="<?= BASE_URL ?>rest-menu/form/<?= $p['id'] ?>" class="btn btn-sm btn-outline">Editar</a>
        <a href="<?= BASE_URL ?>rest-menu/toggleDisponible/<?= $p['id'] ?>"
           style="margin-left:6px;font-size:.78rem;color:#6B7280;text-decoration:underline">
          <?= $p['disponible'] ? 'Pausar' : 'Activar' ?>
        </a>
        <a href="<?= BASE_URL ?>rest-menu/eliminar/<?= $p['id'] ?>" onclick="return confirm('¿Desactivar este platillo?')"
           style="margin-left:6px;font-size:.78rem;color:#EF4444;text-decoration:underline">Quitar</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($platsCat)): ?>
    <tr><td colspan="5" style="padding:16px 20px;color:#9CA3AF;font-size:.85rem;font-style:italic">
      Sin platillos — <a href="<?= BASE_URL ?>rest-menu/form" style="color:var(--cp)">agregar uno</a>
    </td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endforeach; ?>

<?php endif; ?>

<!-- Modal categoría -->
<div id="modalCat" class="rst-modal-backdrop" onclick="if(event.target===this)cerrarModalCat()">
  <div class="rst-modal" style="width:420px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h3 style="font-weight:700;color:#111827;font-size:1.05rem;margin:0">Nueva Categoría</h3>
      <button onclick="cerrarModalCat()"
        style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#6B7280;padding:4px">✕</button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>rest-menu/guardarCategoria" onsubmit="return validarCat()">
      <input type="hidden" name="id" value="">
      <div class="form-group">
        <label class="form-label">Nombre de la categoría *</label>
        <input type="text" name="nombre" id="catNombre" class="form-input"
               placeholder="Ej: Entradas, Bebidas, Postres">
        <div id="catError" style="font-size:.78rem;color:#EF4444;margin-top:4px;display:none">El nombre es obligatorio.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Orden <span style="color:#9CA3AF;font-weight:400">(número, menor = primero)</span></label>
        <input type="number" name="orden" value="0" class="form-input" style="max-width:120px">
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
