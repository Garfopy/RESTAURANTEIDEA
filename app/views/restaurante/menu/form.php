<?php
$esEdicion = !empty($platillo['id']);
$ingredientesReceta = $platillo['ingredientes'] ?? [];
$alergenosActivos = array_filter(array_map('trim', explode(',', (string)($platillo['alergenos'] ?? ''))));
$alergenosList = ['Gluten', 'Lactosa', 'Mariscos', 'Frutos secos', 'Huevo', 'Soya', 'Cacahuate', 'Mostaza'];
$abrirInformacion = !empty($alergenosActivos) || !empty($platillo['contiene']);
$abrirReceta = !empty($ingredientesReceta) || !empty($platillo['ingrediente_directo_id']);
$modoInventario = !empty($platillo['ingrediente_directo_id'])
  ? 'unit'
  : (!empty($ingredientesReceta) ? 'recipe' : 'none');
$cantidadDirecta = max(0.001, (float)($platillo['ingrediente_directo_cantidad'] ?? 1));
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
          <fieldset class="form-group dish-status-field">
            <legend class="form-label">Flujo de preparación</legend>
            <input type="hidden" name="requiere_preparacion" value="0">
            <label class="dish-switch-row" for="inpRequierePreparacion">
              <span>
                <strong>Enviar a Cocina</strong>
                <small>Desactívalo para botellas, piezas o productos listos para entregar.</small>
              </span>
              <input type="checkbox" name="requiere_preparacion" id="inpRequierePreparacion" value="1"
                     <?= ($platillo['requiere_preparacion'] ?? 1) ? 'checked' : '' ?>>
              <span class="dish-switch" aria-hidden="true"></span>
            </label>
          </fieldset>
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
          Elige una sola forma de descontar existencias. Puedes cambiarla después sin modificar el platillo en Caja o en la app.
        </div>

        <fieldset class="dish-inventory-modes">
          <legend>¿Cómo se descuenta del inventario?</legend>
          <div class="dish-inventory-mode-grid">
            <label class="dish-mode-card">
              <input type="radio" name="inventory_mode" value="none" <?= $modoInventario === 'none' ? 'checked' : '' ?>>
              <span><strong>Sin descuento</strong><small>Solo publicar el platillo</small></span>
            </label>
            <label class="dish-mode-card <?= empty($ingredientes) ? 'is-disabled' : '' ?>">
              <input type="radio" name="inventory_mode" value="recipe" <?= $modoInventario === 'recipe' ? 'checked' : '' ?>
                     <?= empty($ingredientes) ? 'disabled' : '' ?>>
              <span><strong>Ingredientes</strong><small>Solo marca lo que lleva</small></span>
            </label>
            <label class="dish-mode-card <?= empty($ingredientes) ? 'is-disabled' : '' ?>">
              <input type="radio" name="inventory_mode" value="unit" <?= $modoInventario === 'unit' ? 'checked' : '' ?>
                     <?= empty($ingredientes) ? 'disabled' : '' ?>>
              <span><strong>Producto por unidad</strong><small>Refrescos, papas o piezas</small></span>
            </label>
          </div>
        </fieldset>

        <?php if (empty($ingredientes)): ?>
          <div class="dish-warning" role="status">
            <strong>Aún no hay productos en inventario.</strong>
            <span>Puedes guardar sin descuento o crear primero los productos que vas a controlar.</span>
            <a href="<?= BASE_URL ?>rest-inventario/index" target="_blank" rel="noopener">Abrir inventario</a>
          </div>
        <?php else: ?>
        <section id="inventoryRecipePanel" class="dish-inventory-panel" <?= $modoInventario !== 'recipe' ? 'hidden' : '' ?> aria-labelledby="recipePanelTitle">
          <input type="hidden" name="porciones_base" value="1">
          <div class="dish-form-grid">
            <div class="form-group dish-field--wide">
              <label class="form-label" for="inpNotas">Nota breve para cocina</label>
              <input type="text" name="receta_notas" id="inpNotas" maxlength="255" class="form-input"
                     placeholder="Ej. Marinar una hora y servir caliente"
                     value="<?= htmlspecialchars($platillo['receta']['notas'] ?? '', ENT_QUOTES) ?>">
            </div>
          </div>

          <div class="dish-recipe-heading">
            <div>
              <h3 id="recipePanelTitle">Ingredientes de la receta</h3>
              <p>Toca cada ingrediente que lleva el platillo. Sin kilos, gramos ni medidas.</p>
            </div>
            <button type="button" class="btn btn-outline btn-sm" id="addIngredientButton">+ Elegir manualmente</button>
          </div>

          <div class="dish-ingredient-picker">
            <label class="form-label" for="ingredientQuickSearch">Agregar rápidamente desde inventario</label>
            <input type="search" id="ingredientQuickSearch" class="form-input" placeholder="Buscar pan, queso, jamón…" autocomplete="off">
            <div id="ingredientCatalog" class="dish-ingredient-catalog">
              <?php foreach ($ingredientes as $ingrediente): ?>
              <button type="button" class="dish-ingredient-option"
                      data-id="<?= (int)$ingrediente['id'] ?>"
                      data-unit="pza"
                      data-search="<?= htmlspecialchars(mb_strtolower((string)$ingrediente['nombre']), ENT_QUOTES) ?>">
                <span><?= htmlspecialchars($ingrediente['nombre']) ?></span>
                <small><?= number_format((float)($ingrediente['stock'] ?? 0), 0) ?> disponibles</small>
              </button>
              <?php endforeach; ?>
            </div>
            <p id="ingredientSearchEmpty" class="dish-search-empty" hidden>No se encontraron productos con ese nombre.</p>
          </div>

          <div id="ingredientes-lista" class="dish-recipe-list" aria-live="polite">
            <?php foreach ($ingredientesReceta as $indice => $ingredienteReceta): ?>
              <div class="dish-recipe-row dish-recipe-row--simple">
                <div class="form-group">
                  <label class="form-label" for="ingrediente-<?= $indice ?>">Ingrediente</label>
                  <select name="ingrediente_id[]" id="ingrediente-<?= $indice ?>" class="form-select dish-ingredient-select">
                    <option value="">Seleccionar ingrediente</option>
                    <?php foreach ($ingredientes as $ingrediente): ?>
                      <option value="<?= (int)$ingrediente['id'] ?>"
                              data-unidad="pza"
                              data-stock="<?= (float)($ingrediente['stock'] ?? 0) ?>"
                        <?= (int)$ingredienteReceta['ingrediente_id'] === (int)$ingrediente['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ingrediente['nombre']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <input type="hidden" name="cantidad[]" value="1">
                <input type="hidden" name="unidad[]" value="pza">
                <button type="button" class="dish-remove-row" aria-label="Quitar ingrediente">Quitar</button>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if (empty($ingredientesReceta)): ?>
            <p id="recipeEmptyState" class="dish-empty-recipe">No has agregado ingredientes. Puedes hacerlo ahora o más tarde.</p>
          <?php endif; ?>

        </section>

        <section id="inventoryUnitPanel" class="dish-inventory-panel" <?= $modoInventario !== 'unit' ? 'hidden' : '' ?> aria-labelledby="unitPanelTitle">
          <div class="dish-unit-heading">
            <h3 id="unitPanelTitle">Producto o bebida por unidad</h3>
            <p>Para bebidas, papas o productos listos: cada venta descuenta piezas.</p>
          </div>
          <div class="dish-unit-picker">
            <div class="form-group">
              <label class="form-label" for="directSearch">Buscar en inventario</label>
              <input type="search" id="directSearch" class="form-input" placeholder="Escribe el nombre del producto" autocomplete="off">
            </div>
            <div class="form-group">
              <label class="form-label" for="ingredienteDirecto">Seleccionar producto</label>
              <select name="ingrediente_directo_id" id="ingredienteDirecto" class="form-select">
                <option value="">Selecciona un producto</option>
                <?php foreach ($ingredientes as $ingrediente): ?>
                  <option value="<?= (int)$ingrediente['id'] ?>"
                          data-unit="pza"
                          data-stock="<?= (float)($ingrediente['stock'] ?? 0) ?>"
                          data-name="<?= htmlspecialchars($ingrediente['nombre'], ENT_QUOTES) ?>"
                          data-search="<?= htmlspecialchars(mb_strtolower((string)$ingrediente['nombre']), ENT_QUOTES) ?>"
                    <?= (int)($platillo['ingrediente_directo_id'] ?? 0) === (int)$ingrediente['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ingrediente['nombre']) ?> · <?= number_format((float)($ingrediente['stock'] ?? 0), 0) ?> disponibles
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="ingredienteDirectoCantidad">Piezas por venta</label>
              <input type="number" name="ingrediente_directo_cantidad" id="ingredienteDirectoCantidad"
                     class="form-input" min="1" step="1" inputmode="numeric"
                     value="<?= htmlspecialchars((string)max(1, (int)round($cantidadDirecta)), ENT_QUOTES) ?>">
            </div>
          </div>
          <div id="directStockSummary" class="dish-unit-summary" role="status" aria-live="polite">
            Selecciona un producto para ver cómo se descontará.
          </div>
        </section>
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
  <div class="dish-recipe-row dish-recipe-row--simple">
    <div class="form-group">
      <label class="form-label">Ingrediente</label>
      <select name="ingrediente_id[]" class="form-select dish-ingredient-select">
        <option value="">Seleccionar ingrediente</option>
        <?php foreach ($ingredientes as $ingrediente): ?>
          <option value="<?= (int)$ingrediente['id'] ?>"
                  data-unidad="pza"
                  data-stock="<?= (float)($ingrediente['stock'] ?? 0) ?>">
            <?= htmlspecialchars($ingrediente['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <input type="hidden" name="cantidad[]" value="1">
    <input type="hidden" name="unidad[]" value="pza">
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
  const inventoryModes = [...document.querySelectorAll('input[name="inventory_mode"]')];
  const recipePanel = document.getElementById('inventoryRecipePanel');
  const unitPanel = document.getElementById('inventoryUnitPanel');
  const ingredientQuickSearch = document.getElementById('ingredientQuickSearch');
  const ingredientOptions = [...document.querySelectorAll('.dish-ingredient-option')];
  const directSearch = document.getElementById('directSearch');
  const directSelect = document.getElementById('ingredienteDirecto');
  const directQuantity = document.getElementById('ingredienteDirectoCantidad');
  const directSummary = document.getElementById('directStockSummary');
  const requiresPreparation = document.getElementById('inpRequierePreparacion');
  let preparationWasChanged = false;
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
    ingredient?.addEventListener('change', () => {
      ingredient.setCustomValidity('');
    });
    row.querySelector('.dish-remove-row')?.addEventListener('click', () => {
      row.remove();
      syncRecipeEmptyState();
    });
  };

  const addIngredient = (ingredientId = '', mainUnit = '') => {
    const existing = [...(list?.querySelectorAll('.dish-ingredient-select') || [])]
      .find(select => select.value === String(ingredientId) && ingredientId !== '');
    if (existing) {
      existing.closest('.dish-recipe-row')?.classList.add('is-highlighted');
      setTimeout(() => existing.closest('.dish-recipe-row')?.classList.remove('is-highlighted'), 900);
      existing.focus();
      return;
    }
    const row = template.content.firstElementChild.cloneNode(true);
    prepareRow(row);
    list.appendChild(row);
    if (ingredientId !== '') {
      const select = row.querySelector('.dish-ingredient-select');
      select.value = String(ingredientId);
      select.dispatchEvent(new Event('change'));
    }
    syncRecipeEmptyState();
    (ingredientId !== ''
      ? row.querySelector('.dish-ingredient-select')
      : row.querySelector('select'))?.focus();
  };

  list?.querySelectorAll('.dish-recipe-row').forEach(prepareRow);
  addButton?.addEventListener('click', () => addIngredient());

  ingredientOptions.forEach(option => option.addEventListener('click', () => {
    addIngredient(option.dataset.id, option.dataset.unit);
  }));
  ingredientQuickSearch?.addEventListener('input', () => {
    const query = ingredientQuickSearch.value.trim().toLocaleLowerCase('es-MX');
    let visible = 0;
    ingredientOptions.forEach(option => {
      const matches = !query || (option.dataset.search || '').includes(query);
      option.hidden = !matches;
      if (matches) visible++;
    });
    const empty = document.getElementById('ingredientSearchEmpty');
    if (empty) empty.hidden = visible > 0;
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

  const setPanelState = (panel, enabled) => {
    if (!panel) return;
    panel.hidden = !enabled;
    panel.querySelectorAll('input, select, textarea, button').forEach(control => {
      control.disabled = !enabled;
    });
  };

  const syncInventoryMode = (adaptPreparation = false) => {
    const mode = inventoryModes.find(radio => radio.checked)?.value || 'none';
    setPanelState(recipePanel, mode === 'recipe');
    setPanelState(unitPanel, mode === 'unit');
    if (directSelect) directSelect.required = mode === 'unit';
    if (adaptPreparation && requiresPreparation && !preparationWasChanged) {
      requiresPreparation.checked = mode !== 'unit';
    }
  };
  inventoryModes.forEach(radio => radio.addEventListener('change', () => syncInventoryMode(true)));
  requiresPreparation?.addEventListener('change', () => { preparationWasChanged = true; });

  const syncDirectSummary = () => {
    if (!directSelect || !directSummary) return;
    const selected = directSelect.selectedOptions[0];
    if (!selected?.value) {
      directSummary.textContent = 'Selecciona un producto para ver cómo se descontará.';
      directSummary.classList.remove('is-ready');
      return;
    }
    const name = selected.dataset.name || selected.textContent.trim();
    const qty = Math.max(1, Math.round(Number(directQuantity?.value || 1)));
    const stock = Number(selected.dataset.stock || 0).toLocaleString('es-MX', { maximumFractionDigits: 0 });
    const title = document.createElement('strong');
    title.textContent = `Cada venta descuenta ${qty.toLocaleString('es-MX')} pieza${qty === 1 ? '' : 's'}`;
    const detail = document.createElement('span');
    detail.textContent = `de ${name}. Disponibles ahora: ${stock}.`;
    directSummary.replaceChildren(title, detail);
    directSummary.classList.add('is-ready');
  };
  directSelect?.addEventListener('change', syncDirectSummary);
  directQuantity?.addEventListener('input', syncDirectSummary);
  directSearch?.addEventListener('input', () => {
    const query = directSearch.value.trim().toLocaleLowerCase('es-MX');
    [...directSelect.options].forEach((option, index) => {
      if (index === 0) return;
      option.hidden = Boolean(query) && !(option.dataset.search || '').includes(query) && !option.selected;
    });
  });

  syncInventoryMode();
  syncDirectSummary();

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

    const inventoryMode = inventoryModes.find(radio => radio.checked)?.value || 'none';
    directQuantity?.setCustomValidity(inventoryMode === 'unit' && Math.round(Number(directQuantity.value)) <= 0
      ? 'Captura una o más piezas por venta.'
      : '');
    const selectedIngredients = inventoryMode === 'recipe'
      ? [...(list?.querySelectorAll('.dish-ingredient-select') || [])]
      .map(select => select.value)
      .filter(Boolean)
      : [];
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
