<?php ob_start(); ?>
<div style="max-width:760px;margin:0 auto">
  <a href="<?= BASE_URL ?>rest-menu/index"
     style="font-size:.85rem;color:#6B7280;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:20px">
    ← Volver al menú
  </a>
  <div style="background:#fff;border-radius:16px;border:1px solid #E5E7EB;padding:28px">
    <form method="POST" action="<?= BASE_URL ?>rest-menu/guardar">
      <input type="hidden" name="id" value="<?= (int)($platillo['id'] ?? 0) ?>">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
        <div>
          <label style="font-size:.85rem;font-weight:500">Nombre del platillo *</label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($platillo['nombre'] ?? '') ?>" required
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Categoría</label>
          <select name="categoria_id" style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
            <option value="">Sin categoría</option>
            <?php foreach ($categorias as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($platillo['categoria_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nombre']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="margin-bottom:16px">
        <label style="font-size:.85rem;font-weight:500">Descripción</label>
        <textarea name="descripcion" rows="2"
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem;resize:vertical"><?= htmlspecialchars($platillo['descripcion'] ?? '') ?></textarea>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px">
        <div>
          <label style="font-size:.85rem;font-weight:500">Precio *</label>
          <input type="number" name="precio" value="<?= (float)($platillo['precio'] ?? 0) ?>" min="0" step="0.01" required
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Tiempo preparación (min)</label>
          <input type="number" name="tiempo_preparacion_min" value="<?= (int)($platillo['tiempo_preparacion_min'] ?? 15) ?>" min="1"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Disponible</label>
          <select name="disponible" style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
            <option value="1" <?= ($platillo['disponible'] ?? 1) ? 'selected' : '' ?>>Sí</option>
            <option value="0" <?= !($platillo['disponible'] ?? 1) ? 'selected' : '' ?>>No</option>
          </select>
        </div>
      </div>

      <!-- Receta -->
      <div style="border-top:1px solid #F3F4F6;margin:24px 0 20px;padding-top:20px">
        <div style="font-weight:600;margin-bottom:14px">Receta (ingredientes)</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
          <div>
            <label style="font-size:.85rem;font-weight:500">Porciones base</label>
            <input type="number" name="porciones_base" value="<?= (int)($platillo['receta']['porciones_base'] ?? 1) ?>" min="1"
              style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
          </div>
          <div>
            <label style="font-size:.85rem;font-weight:500">Notas de receta</label>
            <input type="text" name="receta_notas" value="<?= htmlspecialchars($platillo['receta']['notas'] ?? '') ?>"
              style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
          </div>
        </div>

        <div id="ingredientes-lista">
          <?php foreach (($platillo['ingredientes'] ?? []) as $ing): ?>
          <div class="ing-row" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center">
            <select name="ingrediente_id[]" style="padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
              <option value="">-- Ingrediente --</option>
              <?php foreach ($ingredientes as $i): ?>
              <option value="<?= $i['id'] ?>" <?= $i['id'] == $ing['ingrediente_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($i['nombre']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="cantidad[]" value="<?= $ing['cantidad'] ?>" step="0.001" min="0"
              placeholder="Cantidad"
              style="padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
            <input type="text" name="unidad[]" value="<?= htmlspecialchars($ing['unidad']) ?>"
              placeholder="Unidad"
              style="padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
            <button type="button" onclick="this.closest('.ing-row').remove()"
              style="padding:4px 10px;background:#FEE2E2;color:#991B1B;border:none;border-radius:6px;cursor:pointer;font-size:.8rem">✕</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" onclick="addIngrediente()"
          style="padding:7px 14px;border:1px dashed #D1D5DB;border-radius:8px;font-size:.85rem;cursor:pointer;background:#F9FAFB">
          + Agregar ingrediente
        </button>
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px">
        <a href="<?= BASE_URL ?>rest-menu/index"
          style="padding:8px 16px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;text-decoration:none;color:#374151">Cancelar</a>
        <button type="submit"
          style="padding:8px 24px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:500;cursor:pointer">
          Guardar Platillo
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const ingredientesOptions = <?= json_encode(array_map(fn($i) => ['id'=>$i['id'],'nombre'=>$i['nombre']], $ingredientes)) ?>;

function addIngrediente() {
  const opts = ingredientesOptions.map(i => `<option value="${i.id}">${i.nombre}</option>`).join('');
  const row  = document.createElement('div');
  row.className = 'ing-row';
  row.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center';
  row.innerHTML = `
    <select name="ingrediente_id[]" style="padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
      <option value="">-- Ingrediente --</option>${opts}
    </select>
    <input type="number" name="cantidad[]" step="0.001" min="0" placeholder="Cantidad"
      style="padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
    <input type="text" name="unidad[]" placeholder="Unidad" value="kg"
      style="padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem">
    <button type="button" onclick="this.closest('.ing-row').remove()"
      style="padding:4px 10px;background:#FEE2E2;color:#991B1B;border:none;border-radius:6px;cursor:pointer;font-size:.8rem">✕</button>
  `;
  document.getElementById('ingredientes-lista').appendChild(row);
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
