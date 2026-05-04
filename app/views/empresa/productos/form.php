<?php
// Vista: Formulario alta/edición de producto (admin_empresa)
$esEdicion = !empty($producto);
$accion    = $esEdicion ? BASE_URL . 'empresa-producto/actualizar/' . $producto['id'] : BASE_URL . 'empresa-producto/guardar';
?>

<div style="max-width:780px">
  <a href="<?= BASE_URL ?>empresa-producto/index"
     style="display:inline-flex;align-items:center;gap:4px;font-size:.875rem;color:#6B7280;text-decoration:none;margin-bottom:20px">
    ← Volver al catálogo
  </a>

  <form method="POST" action="<?= $accion ?>" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

      <!-- Nombre -->
      <div style="grid-column:1/-1">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">Nombre del producto *</label>
        <input type="text" name="nombre" required value="<?= htmlspecialchars($producto['nombre'] ?? '') ?>"
               placeholder="Ej: Costilla de res premium"
               style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem;box-sizing:border-box">
      </div>

      <!-- Categoría -->
      <div>
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">Categoría *</label>
        <select name="categoria_id" required
                style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem">
          <option value="">Selecciona...</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($producto['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Presentación -->
      <div>
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">Presentación *</label>
        <select name="presentacion"
                style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem">
          <?php foreach (['kg','caja','pieza'] as $op): ?>
            <option value="<?= $op ?>" <?= ($producto['presentacion'] ?? 'kg') === $op ? 'selected' : '' ?>><?= $op ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Precio base -->
      <div>
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">Precio base (MXN) *</label>
        <input type="number" name="precio_base" required min="0" step="0.01"
               value="<?= $producto['precio_base'] ?? '' ?>"
               placeholder="0.00"
               style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem;box-sizing:border-box">
      </div>

      <!-- Descripción -->
      <div style="grid-column:1/-1">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">Descripción</label>
        <textarea name="descripcion" rows="3"
                  placeholder="Descripción del corte, calidad, proceso, etc."
                  style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem;resize:vertical;box-sizing:border-box"><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
      </div>

      <!-- Imagen -->
      <div style="grid-column:1/-1">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">Imagen del producto</label>
        <?php if (!empty($producto['imagen'])): ?>
          <img src="<?= htmlspecialchars($producto['imagen']) ?>" alt="" style="width:80px;height:80px;border-radius:8px;object-fit:cover;border:1px solid #E5E7EB;margin-bottom:8px;display:block">
        <?php endif; ?>
        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
               style="font-size:.875rem;color:#374151">
        <p style="font-size:.75rem;color:#9CA3AF;margin-top:4px">JPG, PNG o WebP · máx 2 MB</p>
      </div>
    </div>

    <!-- Precios escalonados -->
    <div style="margin-top:24px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <div>
          <h3 style="font-size:.9rem;font-weight:700;color:#111827">Precios escalonados por volumen</h3>
          <p style="font-size:.75rem;color:#6B7280">Defina rangos de cantidad con descuento para compradores de mayor volumen.</p>
        </div>
        <button type="button" onclick="agregarEscalon()" style="padding:6px 14px;border:1px solid #D1D5DB;border-radius:6px;background:#fff;font-size:.8rem;cursor:pointer;font-weight:600">+ Agregar rango</button>
      </div>

      <div id="escalonados" style="display:flex;flex-direction:column;gap:8px">
        <?php
        $escalonados = $producto['escalonados'] ?? [];
        foreach ($escalonados as $i => $esc):
        ?>
        <div class="escalon" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:end">
          <div>
            <label style="font-size:.75rem;color:#6B7280">Cantidad mínima</label>
            <input type="number" name="esc_cant_min[]" min="0" step="0.1" value="<?= $esc['cantidad_min'] ?>"
                   style="width:100%;padding:7px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.85rem;box-sizing:border-box">
          </div>
          <div>
            <label style="font-size:.75rem;color:#6B7280">Cantidad máxima (vacío = sin límite)</label>
            <input type="number" name="esc_cant_max[]" min="0" step="0.1" value="<?= $esc['cantidad_max'] ?? '' ?>"
                   style="width:100%;padding:7px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.85rem;box-sizing:border-box">
          </div>
          <div>
            <label style="font-size:.75rem;color:#6B7280">Precio (MXN)</label>
            <input type="number" name="esc_precio[]" min="0" step="0.01" value="<?= $esc['precio'] ?>"
                   style="width:100%;padding:7px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.85rem;box-sizing:border-box">
          </div>
          <button type="button" onclick="this.closest('.escalon').remove()"
                  style="padding:7px 10px;border:1px solid #FCA5A5;border-radius:6px;background:#FEF2F2;color:#DC2626;cursor:pointer;font-size:.8rem">✕</button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Inventario inicial (solo en creación) -->
    <?php if (!$esEdicion): ?>
    <div style="margin-top:24px;padding:16px;background:#F9FAFB;border-radius:8px;border:1px solid #E5E7EB">
      <h3 style="font-size:.9rem;font-weight:700;color:#111827;margin-bottom:12px">Inventario inicial</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">Stock inicial</label>
          <input type="number" name="stock_inicial" min="0" step="0.1" value="0"
                 style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem;box-sizing:border-box">
        </div>
        <div>
          <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">Umbral de alerta de stock bajo</label>
          <input type="number" name="umbral_minimo" min="0" step="0.1" value="10"
                 style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem;box-sizing:border-box">
        </div>
      </div>
    </div>
    <?php else: ?>
    <div style="margin-top:16px">
      <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">Umbral de alerta de stock bajo</label>
      <input type="number" name="umbral_minimo" min="0" step="0.1" value="<?= $producto['umbral_minimo'] ?? 10 ?>"
             style="width:200px;padding:9px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem">
    </div>
    <?php endif; ?>

    <div style="margin-top:24px;display:flex;gap:12px">
      <button type="submit"
              style="padding:10px 24px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer">
        <?= $esEdicion ? 'Guardar cambios' : 'Crear producto' ?>
      </button>
      <a href="<?= BASE_URL ?>empresa-producto/index"
         style="padding:10px 20px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;color:#374151;text-decoration:none">
        Cancelar
      </a>
    </div>
  </form>
</div>

<script>
function agregarEscalon() {
  const cont = document.getElementById('escalonados');
  const div  = document.createElement('div');
  div.className = 'escalon';
  div.style.cssText = 'display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:end';
  div.innerHTML = `
    <div><label style="font-size:.75rem;color:#6B7280">Cantidad mínima</label>
      <input type="number" name="esc_cant_min[]" min="0" step="0.1"
             style="width:100%;padding:7px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.85rem;box-sizing:border-box"></div>
    <div><label style="font-size:.75rem;color:#6B7280">Cantidad máxima</label>
      <input type="number" name="esc_cant_max[]" min="0" step="0.1"
             style="width:100%;padding:7px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.85rem;box-sizing:border-box"></div>
    <div><label style="font-size:.75rem;color:#6B7280">Precio (MXN)</label>
      <input type="number" name="esc_precio[]" min="0" step="0.01"
             style="width:100%;padding:7px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.85rem;box-sizing:border-box"></div>
    <button type="button" onclick="this.closest('.escalon').remove()"
            style="padding:7px 10px;border:1px solid #FCA5A5;border-radius:6px;background:#FEF2F2;color:#DC2626;cursor:pointer;font-size:.8rem">✕</button>
  `;
  cont.appendChild(div);
}
</script>
