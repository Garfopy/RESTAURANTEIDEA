<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
$esEdicion = !empty($producto);
$categorias = $categorias ?? [];
?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
  <a href="<?= BASE_URL ?>producto/index" style="color:#6B7280;text-decoration:none;font-size:.875rem">← Productos</a>
  <h1 style="font-size:1.25rem;font-weight:800;margin:0"><?= $esEdicion ? 'Editar producto' : 'Nuevo producto' ?></h1>
</div>

<?php if (!empty($error)): ?>
<div class="toast error" style="margin-bottom:12px"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>producto/guardar" enctype="multipart/form-data">
  <?php if ($esEdicion): ?><input type="hidden" name="id" value="<?= $producto['id'] ?>"><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <!-- Información básica -->
    <div class="card">
      <div style="font-weight:700;margin-bottom:14px">Información básica</div>
      <div style="display:flex;flex-direction:column;gap:12px">
        <div>
          <label class="form-label">Nombre del producto *</label>
          <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($producto['nombre'] ?? '') ?>" required>
        </div>
        <div>
          <label class="form-label">Categoría *</label>
          <select name="categoria_id" class="form-control form-select" required>
            <option value="">Selecciona categoría</option>
            <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($producto['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['nombre']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label">Descripción</label>
          <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="form-label">Presentación</label>
          <select name="presentacion" class="form-control form-select">
            <option value="kg" <?= ($producto['presentacion'] ?? 'kg') === 'kg' ? 'selected' : '' ?>>Kilogramo (kg)</option>
            <option value="pieza" <?= ($producto['presentacion'] ?? '') === 'pieza' ? 'selected' : '' ?>>Pieza</option>
            <option value="caja" <?= ($producto['presentacion'] ?? '') === 'caja' ? 'selected' : '' ?>>Caja</option>
          </select>
        </div>
        <div>
          <label class="form-label">Precio base ($/kg) *</label>
          <input type="number" name="precio_base" class="form-control" value="<?= $producto['precio_base'] ?? '' ?>" step="0.01" min="0" required>
        </div>
        <div>
          <label class="form-label">Estado</label>
          <select name="activo" class="form-control form-select">
            <option value="1" <?= ($producto['activo'] ?? 1) ? 'selected' : '' ?>>Activo</option>
            <option value="0" <?= !($producto['activo'] ?? 1) ? 'selected' : '' ?>>Inactivo</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Imagen -->
    <div class="card">
      <div style="font-weight:700;margin-bottom:14px">Imagen del producto</div>
      <?php if (!empty($producto['imagen'])): ?>
      <img src="<?= UPLOAD_URL ?>productos/<?= htmlspecialchars($producto['imagen']) ?>"
           style="width:100%;border-radius:8px;margin-bottom:12px;max-height:200px;object-fit:cover">
      <?php else: ?>
      <div style="background:#F3F4F6;border-radius:8px;height:160px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;color:#9CA3AF;font-size:.875rem">
        Sin imagen
      </div>
      <?php endif; ?>
      <div>
        <label class="form-label">Subir imagen (JPG, PNG — máx 2MB)</label>
        <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/webp">
      </div>
    </div>
  </div>

  <div style="display:flex;gap:10px;justify-content:flex-end">
    <a href="<?= BASE_URL ?>producto/index" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
      <?= $esEdicion ? 'Guardar cambios' : 'Crear producto' ?>
    </button>
    <?php if ($esEdicion): ?>
    <a href="<?= BASE_URL ?>producto/precios/<?= $producto['id'] ?>" class="btn btn-secondary">
      Precios escalonados →
    </a>
    <?php endif; ?>
  </div>
</form>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
