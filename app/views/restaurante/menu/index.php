<?php ob_start(); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div>
    <a href="<?= BASE_URL ?>rest-menu/form"
       style="padding:8px 16px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:500;text-decoration:none">
      + Nuevo Platillo
    </a>
  </div>
</div>

<?php if (empty($categorias)): ?>
<div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:10px;padding:16px;margin-bottom:16px;font-size:.875rem;color:#92400E">
  <strong>Sin categorías.</strong> Crea al menos una categoría antes de agregar platillos.
  <button onclick="document.getElementById('modalCat').style.display='flex'"
    style="margin-left:12px;padding:4px 12px;background:#F59E0B;color:#fff;border:none;border-radius:6px;font-size:.8rem;cursor:pointer">
    Crear categoría
  </button>
</div>
<?php endif; ?>

<!-- Platillos por categoría -->
<?php foreach ($categorias as $cat): ?>
<?php $platsCat = array_filter($platillos, fn($p) => $p['categoria_id'] == $cat['id']); ?>
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;margin-bottom:16px;overflow:hidden">
  <div style="padding:14px 20px;background:#F9FAFB;border-bottom:1px solid #E5E7EB;font-weight:600">
    <?= htmlspecialchars($cat['nombre']) ?>
    <span style="font-size:.8rem;color:#9CA3AF;margin-left:8px"><?= count($platsCat) ?> platillos</span>
  </div>
  <table style="width:100%;border-collapse:collapse;font-size:.875rem">
    <tbody>
    <?php foreach ($platsCat as $p): ?>
    <tr style="border-bottom:1px solid #F9FAFB">
      <td style="padding:12px 20px;font-weight:500"><?= htmlspecialchars($p['nombre']) ?></td>
      <td style="padding:12px 20px;color:#6B7280"><?= htmlspecialchars($p['descripcion'] ?? '') ?></td>
      <td style="padding:12px 20px;font-weight:600;color:#111827">$<?= number_format((float)$p['precio'],2) ?></td>
      <td style="padding:12px 20px">
        <span style="padding:2px 8px;border-radius:99px;font-size:.72rem;font-weight:600;
          background:<?= $p['disponible'] ? '#DCFCE7' : '#FEE2E2' ?>;color:<?= $p['disponible'] ? '#166534' : '#991B1B' ?>">
          <?= $p['disponible'] ? 'Disponible' : 'No disponible' ?>
        </span>
      </td>
      <td style="padding:12px 20px">
        <a href="<?= BASE_URL ?>rest-menu/form/<?= $p['id'] ?>" style="font-size:.8rem;color:var(--color-primary);font-weight:500">Editar</a>
        <a href="<?= BASE_URL ?>rest-menu/toggleDisponible/<?= $p['id'] ?>"
           style="margin-left:10px;font-size:.8rem;color:#6B7280">Toggle</a>
        <a href="<?= BASE_URL ?>rest-menu/eliminar/<?= $p['id'] ?>" onclick="return confirm('¿Desactivar platillo?')"
           style="margin-left:10px;font-size:.8rem;color:#EF4444">Eliminar</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($platsCat)): ?>
    <tr><td colspan="5" style="padding:16px 20px;color:#9CA3AF;font-size:.85rem">Sin platillos en esta categoría.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endforeach; ?>

<!-- Botón categoría -->
<button onclick="document.getElementById('modalCat').style.display='flex'"
  style="padding:8px 16px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;cursor:pointer;background:#fff">
  + Categoría
</button>

<!-- Modal categoría -->
<div id="modalCat" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:380px;max-width:95vw">
    <h3 style="font-weight:700;margin-bottom:18px">Nueva Categoría</h3>
    <form method="POST" action="<?= BASE_URL ?>rest-menu/guardarCategoria">
      <input type="hidden" name="id" value="">
      <div style="margin-bottom:14px">
        <label style="font-size:.85rem;font-weight:500">Nombre</label>
        <input type="text" name="nombre" required
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="margin-bottom:14px">
        <label style="font-size:.85rem;font-weight:500">Orden (número)</label>
        <input type="number" name="orden" value="0"
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('modalCat').style.display='none'"
          style="padding:8px 16px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;cursor:pointer;background:#fff">Cancelar</button>
        <button type="submit"
          style="padding:8px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer">Guardar</button>
      </div>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
