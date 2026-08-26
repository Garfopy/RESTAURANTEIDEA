<?php
$esEdicion = !empty($platillo['id']);
$ingredientesReceta = $platillo['ingredientes'] ?? [];
$alergenosActivos = array_filter(array_map('trim', explode(',', (string)($platillo['alergenos'] ?? ''))));
$alergenosList = ['Gluten', 'Lactosa', 'Mariscos', 'Frutos secos', 'Huevo', 'Soya', 'Cacahuate', 'Mostaza'];
$abrirInformacion = !empty($alergenosActivos) || !empty($platillo['contiene']);
$abrirReceta = !empty($ingredientesReceta) || !empty($platillo['ingrediente_directo_id']);
ob_start();
?>

<main class="dish-form-shell">
  <a href="<?= BASE_URL ?>rest-menu/index" class="rst-back-link">&larr; Volver al menú</a>

  <header class="dish-form-header">
    <div>
      <p class="dish-form-eyebrow">Menú del restaurante</p>
      <h1><?= $esEdicion ? 'Editar platillo' : 'Crear platillo' ?></h1>
      <p>Solo necesitas nombre, precio y categoría. Los demás datos se pueden completar después.</p>
    </div>
    <span class="dish-form-time" aria-label="Tiempo estimado de captura">1–2 minutos</span>
  </header>

  <form method="POST" action="<?= BASE_URL ?>rest-menu/guardar" id="formPlatillo"
        enctype="multipart/form-data" class="dish-form" novalidate>
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
    <input type="hidden" name="id" value="<?= (int)($platillo['id'] ?? 0) ?>">

    <section class="dish-form-card" aria-labelledby="datos-esenciales-title">
      <div class="dish-section-heading">
        <span class="dish-section-number" aria-hidden="true">1</span>
        <div>
          <h2 id="datos-esenciales-title">Datos esenciales</h2>
          <p>Esta información aparece en caja y en el menú.</p>
        </div>
      </div>

      <div class="dish-form-grid dish-form-grid--primary">
        <div class="form-group dish-field--wide">
          <label class="form-label" for="inpNombre">Nombre del platillo <span aria-hidden="true">*</span></label>
          <input type="text" name="nombre" id="inpNombre" required maxlength="150"
                 class="form-input" autocomplete="off" placeholder="Ej. Tacos al pastor"
                 value="<?= htmlspecialchars($platillo['nombre'] ?? '', ENT_QUOTES) ?>"
                 aria-describedby="nombre-ayuda nombre-error">
          <small id="nombre-ayuda" class="dish-help">Usa el nombre que reconocerán cocina y clientes.</small>
          <small id="nombre-error" class="dish-field-error" aria-live="polite"></small>
        </div>

        <div class="form-group">
          <label class="form-label" for="inpPrecio">Precio <span aria-hidden="true">*</span></label>
          <div class="dish-money-field">
            <span aria-hidden="true">$</span>
            <input type="number" name="precio" id="inpPrecio" required min="0.01" step="0.01"
                   class="form-input" inputmode="decimal"
                   value="<?= htmlspecialchars((string)($platillo['precio'] ?? ''), ENT_QUOTES) ?>"
                   aria-describedby="precio-error">
          </div>
          <small id="precio-error" class="dish-field-error" aria-live="polite"></small>
        </div>

        <div class="form-group">
          <label class="form-label" for="inpCat">Categoría</label>
          <select name="categoria_id" id="inpCat" class="form-select">
            <option value="">Sin categoría</option>
            <?php foreach ($categorias as $categoria): ?>
              <option value="<?= (int)$categoria['id'] ?>"
                <?= (int)($platillo['categoria_id'] ?? 0) === (int)$categoria['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($categoria['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="dish-inline-link" id="toggleNewCategory" aria-expanded="false" aria-controls="newCategoryFields">
            + Crear una categoría nueva
          </button>
          <div id="newCategoryFields" class="dish-new-category" hidden>
            <label class="form-label" for="inpNuevaCategoria">Nombre de la categoría</label>
            <input type="text" name="nueva_categoria" id="inpNuevaCategoria" maxlength="100"
                   class="form-input" placeholder="Ej. Desayunos">
            <small class="dish-help">Se creará junto con el platillo.</small>
          </div>
        </div>

        <fieldset class="form-group dish-status-field">
          <legend class="form-label">Disponibilidad</legend>
          <input type="hidden" name="disponible" value="0">
          <label class="dish-switch-row" for="inpDisponible">
            <span>
              <strong>Disponible para vender</strong>
              <small>Se mostrará de inmediato en caja y menú.</small>
            </span>
            <input type="checkbox" name="disponible" id="inpDisponible" value="1"
                   <?= ($platillo['disponible'] ?? 1) ? 'checked' : '' ?>>
            <span class="dish-switch" aria-hidden="true"></span>
          </label>
        </fieldset>
      </div>
    </section>

    <details class="dish-form-card dish-details" <?= $esEdicion && (!empty($platillo['descripcion']) || !empty($platillo['imagen'])) ? 'open' : '' ?>>
      <summary>
        <span class="dish-summary-icon" aria-hidden="true">+</span>
        <span><strong>Foto y descripción</strong><small>Opcional · mejora cómo se ve en el menú</small></span>
      </summary>
      <div class="dish-details-content dish-media-grid">
        <div class="dish-image-picker">
          <div id="imgPreviewBox" class="dish-image-preview">
            <img id="imgPreview"
                 src="<?= !empty($platillo['imagen']) ? BASE_URL . htmlspecialchars($platillo['imagen'], ENT_QUOTES) : '' ?>"
                 alt="Vista previa del platillo"
                 <?= empty($platillo['imagen']) ? 'hidden' : '' ?>>
            <span id="imgPlaceholder" <?= !empty($platillo['imagen']) ? 'hidden' : '' ?> aria-hidden="true">Foto</span>
          </div>
          <div>
            <label class="form-label" for="inpImg">Seleccionar imagen</label>
            <input type="file" name="imagen" id="inpImg" accept="image/jpeg,image/png,image/webp"
                   class="dish-file-input" aria-describedby="imagen-ayuda">
            <small id="imagen-ayuda" class="dish-help">JPG, PNG o WebP. Máximo 3 MB; se recomienda formato cuadrado.</small>
            <?php if (!empty($platillo['imagen'])): ?>
              <label class="dish-remove-image">
                <input type="checkbox" name="quitar_imagen" value="1"> Quitar imagen actual
              </label>
            <?php endif; ?>
          </div>
        </div>

        <div>
          <div class="form-group">
            <label class="form-label" for="inpDesc">Descripción para clientes</label>
            <textarea name="descripcion" id="inpDesc" rows="3" maxlength="500" class="form-textarea"
                      placeholder="Ej. Tortilla de maíz, carne al pastor, piña, cilantro y cebolla."><?= htmlspecialchars($platillo['descripcion'] ?? '') ?></textarea>
            <small class="dish-help">Describe el platillo en una frase corta y fácil de leer.</small>
          </div>
          <div class="form-group">
            <label class="form-label" for="inpTiempo">Preparación aproximada</label>
            <div class="dish-suffix-field">
              <input type="number" name="tiempo_preparacion_min" id="inpTiempo" min="1" max="127"
                     class="form-input" inputmode="numeric"
                     value="<?= (int)($platillo['tiempo_preparacion_min'] ?? 15) ?>">
              <span>min</span>
            </div>
            <small class="dish-help">Ayuda a cocina y caja a estimar la espera.</small>
          </div>
        </div>
      </div>
    </details>

    <details class="dish-form-card dish-details" <?= $abrirReceta ? 'open' : '' ?>>
      <summary>
        <span class="dish-summary-icon" aria-hidden="true">+</span>
        <span><strong>Receta e inventario</strong><small>Opcional · activa el descuento automático de existencias</small></span>
      </summary>
      <div class="dish-details-content">
        <div class="dish-info-note" role="note">
          Puedes guardar el platillo sin receta. Hasta que agregues ingredientes, las ventas no descontarán automáticamente esas existencias.
        </div>

        <?php if (empty($ingredientes)): ?>
          <div class="dish-warning" role="status">
            <strong>Aún no hay ingredientes disponibles.</strong>
            <span>Crea primero los insumos que usarás en este platillo.</span>
            <a href="<?= BASE_URL ?>rest-inventario/index" target="_blank" rel="noopener">Abrir inventario</a>
          </div>
        <?php else: ?>
          <div class="dish-form-grid">
            <div class="form-group">
              <label class="form-label" for="inpPorciones">Porciones que rinde la receta</label>
              <input type="number" name="porciones_base" id="inpPorciones" min="1" max="127"
                     class="form-input" value="<?= (int)($platillo['receta']['porciones_base'] ?? 1) ?>">
            </div>
            <div class="form-group dish-field--wide">
              <label class="form-label" for="inpNotas">Nota breve para cocina</label>
              <input type="text" name="receta_notas" id="inpNotas" maxlength="255" class="form-input"
                     placeholder="Ej. Marinar una hora y servir caliente"
                     value="<?= htmlspecialchars($platillo['receta']['notas'] ?? '', ENT_QUOTES) ?>">
            </div>
          </div>

          <div class="dish-recipe-heading">
            <div>
              <h3>Ingredientes de la receta</h3>
              <p>Indica cuánto se consume para el total de porciones.</p>
            </div>
            <button type="button" class="btn btn-outline btn-sm" id="addIngredientButton">+ Agregar ingrediente</button>
          </div>

          <div id="ingredientes-lista" class="dish-recipe-list" aria-live="polite">
            <?php foreach ($ingredientesReceta as $indice => $ingredienteReceta): ?>
              <div class="dish-recipe-row">
                <div class="form-group">
                  <label class="form-label" for="ingrediente-<?= $indice ?>">Ingrediente</label>
                  <select name="ingrediente_id[]" id="ingrediente-<?= $indice ?>" class="form-select dish-ingredient-select">
                    <option value="">Seleccionar ingrediente</option>
                    <?php foreach ($ingredientes as $ingrediente): ?>
                      <option value="<?= (int)$ingrediente['id'] ?>"
                              data-unidad="<?= htmlspecialchars($ingrediente['unidad_principal'], ENT_QUOTES) ?>"
                        <?= (int)$ingredienteReceta['ingrediente_id'] === (int)$ingrediente['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ingrediente['nombre']) ?> · <?= htmlspecialchars($ingrediente['unidad_principal']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label" for="cantidad-<?= $indice ?>">Cantidad</label>
                  <input type="number" name="cantidad[]" id="cantidad-<?= $indice ?>" min="0.001" step="0.001"
                         inputmode="decimal" class="form-input"
                         value="<?= htmlspecialchars((string)$ingredienteReceta['cantidad'], ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label class="form-label" for="unidad-<?= $indice ?>">Unidad</label>
                  <select name="unidad[]" id="unidad-<?= $indice ?>" class="form-select dish-unit-select">
                    <?php foreach (['g', 'kg', 'mg', 'L', 'ml', 'mL', 'pza', 'caja', 'bolsa'] as $unidad): ?>
                      <option value="<?= $unidad ?>" <?= ($ingredienteReceta['unidad'] ?? '') === $unidad ? 'selected' : '' ?>><?= $unidad ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button type="button" class="dish-remove-row" aria-label="Quitar ingrediente">Quitar</button>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if (empty($ingredientesReceta)): ?>
            <p id="recipeEmptyState" class="dish-empty-recipe">No has agregado ingredientes. Puedes hacerlo ahora o más tarde.</p>
          <?php endif; ?>

          <hr class="dish-divider">
          <div class="form-group dish-direct-stock">
            <label class="form-label" for="ingredienteDirecto">Producto directo de inventario</label>
            <select name="ingrediente_directo_id" id="ingredienteDirecto" class="form-select">
              <option value="">Sin vínculo directo</option>
              <?php foreach ($ingredientes as $ingrediente): ?>
                <option value="<?= (int)$ingrediente['id'] ?>"
                  <?= (int)($platillo['ingrediente_directo_id'] ?? 0) === (int)$ingrediente['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ingrediente['nombre']) ?> · <?= htmlspecialchars($ingrediente['unidad_principal']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="dish-help">Úsalo solo para bebidas o productos que descuentan una unidad y no necesitan receta.</small>
          </div>
        <?php endif; ?>
      </div>
    </details>

    <details class="dish-form-card dish-details" <?= $abrirInformacion ? 'open' : '' ?>>
      <summary>
        <span class="dish-summary-icon" aria-hidden="true">+</span>
        <span><strong>Alérgenos e información</strong><small>Opcional · ayuda al cliente a elegir con seguridad</small></span>
      </summary>
      <div class="dish-details-content">
        <fieldset class="form-group dish-allergens">
          <legend class="form-label">Alérgenos conocidos</legend>
          <div class="dish-chip-list">
            <?php foreach ($alergenosList as $alergeno): ?>
              <label class="dish-check-chip">
                <input type="checkbox" name="alergenos[]" value="<?= htmlspecialchars($alergeno, ENT_QUOTES) ?>"
                  <?= in_array($alergeno, $alergenosActivos, true) ? 'checked' : '' ?>>
                <span><?= htmlspecialchars($alergeno) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <div class="form-group">
          <label class="form-label" for="inpContiene">Otros ingredientes que conviene mencionar</label>
          <input type="text" name="contiene" id="inpContiene" maxlength="500" class="form-input"
                 placeholder="Ej. Pimienta, cilantro, chile de árbol"
                 value="<?= htmlspecialchars($platillo['contiene'] ?? '', ENT_QUOTES) ?>">
        </div>
      </div>
    </details>

    <div class="dish-form-actions">
      <p><strong><?= $esEdicion ? 'Actualizar platillo' : 'Listo para guardar' ?></strong><span>Los campos opcionales se pueden editar en cualquier momento.</span></p>
      <div>
        <a href="<?= BASE_URL ?>rest-menu/index" class="btn btn-outline">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg" id="saveDishButton">
          <?= $esEdicion ? 'Guardar cambios' : 'Crear platillo' ?>
        </button>
      </div>
    </div>
  </form>
</main>

<template id="ingredientRowTemplate">
  <div class="dish-recipe-row">
    <div class="form-group">
      <label class="form-label">Ingrediente</label>
      <select name="ingrediente_id[]" class="form-select dish-ingredient-select">
        <option value="">Seleccionar ingrediente</option>
        <?php foreach ($ingredientes as $ingrediente): ?>
          <option value="<?= (int)$ingrediente['id'] ?>" data-unidad="<?= htmlspecialchars($ingrediente['unidad_principal'], ENT_QUOTES) ?>">
            <?= htmlspecialchars($ingrediente['nombre']) ?> · <?= htmlspecialchars($ingrediente['unidad_principal']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Cantidad</label>
      <input type="number" name="cantidad[]" min="0.001" step="0.001" inputmode="decimal" class="form-input" placeholder="0.000">
    </div>
    <div class="form-group">
      <label class="form-label">Unidad</label>
      <select name="unidad[]" class="form-select dish-unit-select">
        <?php foreach (['g', 'kg', 'mg', 'L', 'ml', 'mL', 'pza', 'caja', 'bolsa'] as $unidad): ?>
          <option value="<?= $unidad ?>"><?= $unidad ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="button" class="dish-remove-row" aria-label="Quitar ingrediente">Quitar</button>
  </div>
</template>

<script>
(() => {
  const form = document.getElementById('formPlatillo');
  const list = document.getElementById('ingredientes-lista');
  const template = document.getElementById('ingredientRowTemplate');
  const addButton = document.getElementById('addIngredientButton');
  const categorySelect = document.getElementById('inpCat');
  const categoryToggle = document.getElementById('toggleNewCategory');
  const newCategoryFields = document.getElementById('newCategoryFields');
  const newCategoryInput = document.getElementById('inpNuevaCategoria');
  let rowSequence = <?= count($ingredientesReceta) ?>;

  const syncRecipeEmptyState = () => {
    if (!list) return;
    let empty = document.getElementById('recipeEmptyState');
    if (list.children.length === 0 && !empty) {
      empty = document.createElement('p');
      empty.id = 'recipeEmptyState';
      empty.className = 'dish-empty-recipe';
      empty.textContent = 'No has agregado ingredientes. Puedes hacerlo ahora o más tarde.';
      list.insertAdjacentElement('afterend', empty);
    } else if (list.children.length > 0 && empty) {
      empty.remove();
    }
  };

  const prepareRow = (row) => {
    const index = rowSequence++;
    row.querySelectorAll('label').forEach((label, labelIndex) => {
      const control = label.parentElement.querySelector('select, input');
      if (!control) return;
      const id = `recipe-${index}-${labelIndex}`;
      control.id = id;
      label.htmlFor = id;
    });
    const ingredient = row.querySelector('.dish-ingredient-select');
    const unit = row.querySelector('.dish-unit-select');
    ingredient?.addEventListener('change', () => {
      const selectedUnit = ingredient.selectedOptions[0]?.dataset.unidad;
      if (!selectedUnit || !unit) return;
      const matchingOption = [...unit.options].find(option => option.value.toLowerCase() === selectedUnit.toLowerCase());
      if (matchingOption) unit.value = matchingOption.value;
    });
    row.querySelector('.dish-remove-row')?.addEventListener('click', () => {
      row.remove();
      syncRecipeEmptyState();
    });
  };

  list?.querySelectorAll('.dish-recipe-row').forEach(prepareRow);
  addButton?.addEventListener('click', () => {
    const row = template.content.firstElementChild.cloneNode(true);
    prepareRow(row);
    list.appendChild(row);
    syncRecipeEmptyState();
    row.querySelector('select')?.focus();
  });

  categoryToggle?.addEventListener('click', () => {
    const willOpen = newCategoryFields.hidden;
    newCategoryFields.hidden = !willOpen;
    categoryToggle.setAttribute('aria-expanded', String(willOpen));
    categoryToggle.textContent = willOpen ? 'Usar una categoría existente' : '+ Crear una categoría nueva';
    categorySelect.disabled = willOpen;
    if (willOpen) {
      categorySelect.value = '';
      newCategoryInput.focus();
    } else {
      newCategoryInput.value = '';
    }
  });

  const imageInput = document.getElementById('inpImg');
  imageInput?.addEventListener('change', () => {
    const file = imageInput.files?.[0];
    if (!file) return;
    if (file.size > 3 * 1024 * 1024) {
      imageInput.setCustomValidity('La imagen debe pesar menos de 3 MB.');
      imageInput.reportValidity();
      imageInput.value = '';
      return;
    }
    imageInput.setCustomValidity('');
    const preview = document.getElementById('imgPreview');
    preview.src = URL.createObjectURL(file);
    preview.hidden = false;
    const placeholder = document.getElementById('imgPlaceholder');
    if (placeholder) placeholder.hidden = true;
  });

  form?.addEventListener('submit', event => {
    const name = document.getElementById('inpNombre');
    const price = document.getElementById('inpPrecio');
    const nameError = document.getElementById('nombre-error');
    const priceError = document.getElementById('precio-error');
    nameError.textContent = name.value.trim() ? '' : 'Escribe el nombre del platillo.';
    priceError.textContent = Number(price.value) > 0 ? '' : 'El precio debe ser mayor a cero.';

    list?.querySelectorAll('.dish-recipe-row').forEach(row => {
      const ingredient = row.querySelector('.dish-ingredient-select');
      const quantity = row.querySelector('input[name="cantidad[]"]');
      quantity.setCustomValidity(ingredient.value && Number(quantity.value) <= 0
        ? 'Captura una cantidad mayor a cero para este ingrediente.'
        : '');
    });
    const selectedIngredients = [...(list?.querySelectorAll('.dish-ingredient-select') || [])]
      .map(select => select.value)
      .filter(Boolean);
    if (new Set(selectedIngredients).size !== selectedIngredients.length) {
      event.preventDefault();
      const duplicate = [...list.querySelectorAll('.dish-ingredient-select')]
        .find((select, index, all) => select.value && all.findIndex(item => item.value === select.value) !== index);
      duplicate?.setCustomValidity('Este ingrediente ya está agregado en la receta.');
      duplicate?.reportValidity();
      duplicate?.focus();
      return;
    }

    if (!form.checkValidity() || !name.value.trim() || Number(price.value) <= 0) {
      event.preventDefault();
      form.querySelector(':invalid')?.focus();
      form.reportValidity();
      return;
    }

    const saveButton = document.getElementById('saveDishButton');
    saveButton.disabled = true;
    saveButton.textContent = 'Guardando…';
  });
})();
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
?>
