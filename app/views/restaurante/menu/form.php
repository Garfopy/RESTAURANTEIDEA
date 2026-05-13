<?php ob_start(); ?>
<div>
  <a href="<?= BASE_URL ?>rest-menu/index"
     style="font-size:.85rem;color:#6B7280;text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:18px">
    ← Volver al menú
  </a>

  <!-- Indicador de pasos -->
  <div class="wizard-steps">
    <div class="wstep active" data-step="1">
      <div class="wstep-num">1</div>
      <div class="wstep-label">Información básica</div>
    </div>
    <div class="wstep-line"></div>
    <div class="wstep" data-step="2">
      <div class="wstep-num">2</div>
      <div class="wstep-label">Receta del platillo</div>
    </div>
    <div class="wstep-line"></div>
    <div class="wstep" data-step="3">
      <div class="wstep-num">3</div>
      <div class="wstep-label">Revisar y guardar</div>
    </div>
  </div>

  <div class="rst-card" style="padding:28px;margin-bottom:0">
    <form method="POST" action="<?= BASE_URL ?>rest-menu/guardar" id="formPlatillo">
      <input type="hidden" name="id" value="<?= (int)($platillo['id'] ?? 0) ?>">
      <!-- Datalist para búsqueda de ingredientes -->
      <datalist id="dlIngredientes">
        <?php foreach ($ingredientes as $i): ?>
        <option value="<?= htmlspecialchars($i['nombre'], ENT_QUOTES) ?>"
                data-id="<?= $i['id'] ?>"
                data-unidad="<?= htmlspecialchars($i['unidad_principal'], ENT_QUOTES) ?>">
        <?php endforeach; ?>
      </datalist>

      <!-- ── Paso 1: Información básica ── -->
      <div class="wpane active" data-pane="1">
        <div style="font-weight:700;color:#111827;font-size:1.05rem;margin-bottom:4px">¿Qué platillo vas a vender?</div>
        <div style="font-size:.85rem;color:#6B7280;margin-bottom:20px">
          Llena los datos básicos. La foto y la categoría ayudan al cliente a encontrarlo.
        </div>

        <div class="form-group">
          <label class="form-label">Nombre del platillo *</label>
          <input type="text" name="nombre" id="inpNombre" required
                 class="form-input" placeholder="Ej: Tacos al pastor"
                 value="<?= htmlspecialchars($platillo['nombre'] ?? '') ?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="form-group">
            <label class="form-label">Categoría</label>
            <select name="categoria_id" id="inpCat" class="form-input">
              <option value="">— Sin categoría —</option>
              <?php foreach ($categorias as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($platillo['categoria_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nombre']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Precio al cliente *</label>
            <div style="position:relative">
              <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-weight:600">$</span>
              <input type="number" name="precio" id="inpPrecio" required min="0" step="0.01"
                     class="form-input" style="padding-left:26px"
                     value="<?= (float)($platillo['precio'] ?? 0) ?>">
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Descripción <span style="color:#9CA3AF;font-weight:400">(recomendado — la ven los comensales)</span></label>
          <textarea name="descripcion" id="inpDesc" rows="2" class="form-textarea"
                    placeholder="Ej: Servidos en tortilla de maíz con piña, cilantro y cebolla."><?= htmlspecialchars($platillo['descripcion'] ?? '') ?></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="form-group">
            <label class="form-label">⏱ Tiempo de preparación</label>
            <div style="display:flex;align-items:center;gap:8px">
              <input type="number" name="tiempo_preparacion_min" min="1"
                     class="form-input" style="flex:1"
                     value="<?= (int)($platillo['tiempo_preparacion_min'] ?? 15) ?>">
              <span style="color:#6B7280;font-size:.85rem">min</span>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">📦 Disponible ahora</label>
            <select name="disponible" class="form-input">
              <option value="1" <?= ($platillo['disponible'] ?? 1) ? 'selected' : '' ?>>Sí, ya se puede pedir</option>
              <option value="0" <?= !($platillo['disponible'] ?? 1) ? 'selected' : '' ?>>Aún no</option>
            </select>
          </div>
        </div>

        <!-- Info para el cliente -->
        <div style="border:1.5px solid #E0E7FF;border-radius:12px;padding:16px;margin-top:8px;background:#F5F3FF">
          <div style="font-weight:700;color:#4C1D95;font-size:.9rem;margin-bottom:12px">
            🏷 Información para el cliente
          </div>

          <div class="form-group" style="margin-bottom:10px">
            <label class="form-label" style="font-size:.82rem">Alérgenos</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px">
              <?php
              $alergenosActivos = array_map('trim', explode(',', $platillo['alergenos'] ?? ''));
              $alergenosList = ['Gluten','Lactosa','Mariscos','Frutos secos','Huevo','Soya','Cacahuate','Mostaza'];
              $alergenoColor = ['Gluten'=>'#FEF3C7:#92400E','Lactosa'=>'#DBEAFE:#1E40AF','Mariscos'=>'#CCFBF1:#065F46','Frutos secos'=>'#FEE2E2:#991B1B','Huevo'=>'#FEF9C3:#713F12','Soya'=>'#F3E8FF:#6B21A8','Cacahuate'=>'#FFEDD5:#9A3412','Mostaza'=>'#D1FAE5:#064E3B'];
              foreach ($alergenosList as $al):
                $partes = explode(':', $alergenoColor[$al]);
                $bg = $partes[0]; $fg = $partes[1];
                $checked = in_array($al, $alergenosActivos) ? 'checked' : '';
              ?>
              <label style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;
                            background:<?= $bg ?>;color:<?= $fg ?>;border-radius:8px;padding:4px 10px;
                            font-size:.78rem;font-weight:600;border:1.5px solid transparent;
                            transition:.1s" class="alergen-lbl">
                <input type="checkbox" name="alergenos[]" value="<?= $al ?>" <?= $checked ?>
                       style="display:none" class="alergen-chk">
                <?= $al ?>
              </label>
              <?php endforeach; ?>
            </div>
            <div style="font-size:.73rem;color:#6B7280;margin-top:4px">Selecciona los que apliquen — se mostrarán al cliente como advertencia</div>
          </div>

          <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:.82rem">Contiene (ingredientes no medibles)</label>
            <input type="text" name="contiene" class="form-input"
                   placeholder="Ej: pimienta negra, cilantro, chile de árbol, comino"
                   value="<?= htmlspecialchars($platillo['contiene'] ?? '') ?>">
            <div style="font-size:.73rem;color:#6B7280;margin-top:4px">Escríbe aquí condimentos y especias que no se miden con báscula pero el cliente debe saber</div>
          </div>
        </div>

        <div class="wizard-nav">
          <a href="<?= BASE_URL ?>rest-menu/index" class="btn btn-outline">Cancelar</a>
          <button type="button" class="btn btn-primary" onclick="goStep(2)">
            Siguiente: Receta →
          </button>
        </div>
      </div>

      <!-- ── Paso 2: Receta ── -->
      <div class="wpane" data-pane="2">
        <div style="font-weight:700;color:#111827;font-size:1.05rem;margin-bottom:2px">Receta del platillo</div>
        <div style="font-size:.85rem;color:#6B7280;margin-bottom:18px;line-height:1.5">
          Define qué ingredientes lleva y cuánto. CarniHub
          <strong>descontará automáticamente del inventario</strong> cuando el chef marque el pedido como listo.
          <span style="color:#DC2626;font-weight:600">Requerido para publicar.</span>
        </div>

        <?php if (empty($ingredientes)): ?>
        <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:14px;margin-bottom:16px">
          <div style="font-weight:600;color:#92400E;font-size:.9rem;margin-bottom:4px">
            ⚠️ Aún no tienes ingredientes en tu inventario
          </div>
          <div style="font-size:.82rem;color:#78350F;margin-bottom:10px">
            Para registrar la receta primero crea ingredientes en tu inventario.
          </div>
          <a href="<?= BASE_URL ?>rest-inventario/index" target="_blank" class="btn btn-sm btn-outline">
            Ir a inventario ↗
          </a>
        </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="form-group">
            <label class="form-label">Porciones que rinde</label>
            <input type="number" name="porciones_base" min="1" class="form-input"
                   value="<?= (int)($platillo['receta']['porciones_base'] ?? 1) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Notas de cocina</label>
            <input type="text" name="receta_notas" class="form-input"
                   placeholder="Ej: marinar 1h, servir caliente"
                   value="<?= htmlspecialchars($platillo['receta']['notas'] ?? '') ?>">
          </div>
        </div>

        <div style="font-weight:600;color:#374151;font-size:.85rem;margin:14px 0 8px">
          Ingredientes
          <span style="font-weight:400;color:#9CA3AF;font-size:.78rem"> — busca y selecciona del inventario</span>
        </div>

        <div id="ingredientes-lista">
          <?php foreach (($platillo['ingredientes'] ?? []) as $ing): ?>
          <div class="ing-row">
            <div style="position:relative">
              <input type="text" class="form-input ing-search" list="dlIngredientes"
                     placeholder="Buscar ingrediente…"
                     value="<?= htmlspecialchars($ing['ingrediente_nombre'] ?? '') ?>"
                     oninput="onIngSearch(this)" autocomplete="off">
              <input type="hidden" name="ingrediente_id[]" class="ing-id-hidden"
                     value="<?= $ing['ingrediente_id'] ?>">
            </div>
            <input type="number" name="cantidad[]" step="0.001" min="0" placeholder="Cant."
                   value="<?= $ing['cantidad'] ?>" class="form-input">
            <input type="text" name="unidad[]" placeholder="Unidad"
                   value="<?= htmlspecialchars($ing['unidad']) ?>" class="form-input">
            <label style="display:flex;align-items:center;gap:4px;font-size:.75rem;color:#6B7280;cursor:pointer;white-space:nowrap" title="No descuenta stock, solo aparece en la info del cliente">
              <input type="checkbox" name="es_informativo[]" value="<?= $ing['ingrediente_id'] ?>"
                     <?= ($ing['es_informativo'] ?? 0) ? 'checked' : '' ?> style="cursor:pointer">
              Solo info
            </label>
            <button type="button" onclick="this.closest('.ing-row').remove()"
                    class="btn-icon-danger">✕</button>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" onclick="addIngrediente()"
                style="width:100%;padding:10px;border:2px dashed #D1D5DB;border-radius:10px;
                       background:#F9FAFB;color:#6B7280;font-size:.88rem;cursor:pointer;
                       margin-top:6px;transition:.15s"
                onmouseover="this.style.borderColor='var(--cp)';this.style.color='var(--cp)'"
                onmouseout="this.style.borderColor='#D1D5DB';this.style.color='#6B7280'">
          + Agregar ingrediente a la receta
        </button>

        <div id="recetaError" style="display:none;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;
             padding:10px 14px;margin-top:12px;font-size:.84rem;color:#991B1B">
          ⚠️ Agrega al menos un ingrediente a la receta antes de continuar.
        </div>

        <div class="wizard-nav">
          <button type="button" class="btn btn-outline" onclick="goStep(1)">← Atrás</button>
          <button type="button" class="btn btn-primary" onclick="goStep(3)">
            Siguiente: Revisar →
          </button>
        </div>
      </div>

      <!-- ── Paso 3: Revisar ── -->
      <div class="wpane" data-pane="3">
        <div style="font-weight:700;color:#111827;font-size:1.05rem;margin-bottom:4px">Revisar antes de guardar</div>
        <div style="font-size:.85rem;color:#6B7280;margin-bottom:20px">
          Verifica que todo esté correcto.
        </div>

        <div style="background:#F9FAFB;border-radius:12px;padding:18px;margin-bottom:18px">
          <div id="resumen" style="display:grid;gap:10px"></div>
        </div>

        <div class="wizard-nav">
          <button type="button" class="btn btn-outline" onclick="goStep(2)">← Atrás</button>
          <button type="submit" class="btn btn-primary">
            ✓ Guardar platillo
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<style>
  .wizard-steps{display:flex;align-items:center;gap:8px;margin-bottom:20px;background:#fff;
                border:1px solid #E5E7EB;border-radius:14px;padding:14px 18px}
  .wstep{display:flex;align-items:center;gap:10px;flex-shrink:0}
  .wstep-num{width:30px;height:30px;border-radius:50%;background:#E5E7EB;color:#6B7280;
             display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;
             transition:.25s}
  .wstep-label{font-size:.82rem;color:#6B7280;font-weight:500;transition:.25s}
  .wstep.active .wstep-num{background:var(--cp);color:#fff;transform:scale(1.08)}
  .wstep.active .wstep-label{color:#111827;font-weight:700}
  .wstep.done .wstep-num{background:#10B981;color:#fff}
  .wstep-line{flex:1;height:2px;background:#E5E7EB;border-radius:1px;min-width:30px}
  .wpane{display:none;animation:fadeIn .25s ease both}
  .wpane.active{display:block}
  @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
  .ing-row{display:grid;grid-template-columns:2fr 80px 80px auto auto;gap:8px;margin-bottom:8px;align-items:center}
  .btn-icon-danger{padding:8px 12px;background:#FEE2E2;color:#991B1B;border:none;border-radius:8px;
                   cursor:pointer;font-size:.85rem;transition:.15s}
  .btn-icon-danger:hover{background:#FCA5A5;color:#7F1D1D}
  .wizard-nav{display:flex;gap:10px;justify-content:space-between;margin-top:24px;padding-top:18px;border-top:1px solid #F3F4F6}
  .alergen-lbl input:checked ~ * { /* handled via JS */ }
  .alergen-lbl.activo { outline:2px solid #4C1D95; }
</style>

<script>
const ingredientesMap = <?= json_encode(array_combine(
  array_column($ingredientes, 'nombre'),
  array_values($ingredientes)
)) ?>;
const catNames = <?= json_encode(array_column($categorias, 'nombre', 'id')) ?>;

// Alérgenos: toggle visual
document.querySelectorAll('.alergen-lbl').forEach(lbl => {
  const chk = lbl.querySelector('.alergen-chk');
  function sync() { lbl.style.opacity = chk.checked ? '1' : '.45'; lbl.style.outline = chk.checked ? '2px solid #7C3AED' : 'none'; }
  sync();
  chk.addEventListener('change', sync);
});

function goStep(n) {
  if (n > 1) {
    const nombre = document.getElementById('inpNombre').value.trim();
    const precio = parseFloat(document.getElementById('inpPrecio').value);
    if (!nombre) { alert('El nombre del platillo es obligatorio.'); return; }
    if (isNaN(precio) || precio <= 0) { alert('Indica un precio válido.'); return; }
  }
  if (n > 2) {
    // Validar que haya al menos 1 ingrediente con ID seleccionado
    const ids = [...document.querySelectorAll('#ingredientes-lista .ing-id-hidden')];
    const filled = ids.some(h => h.value && h.value !== '');
    if (!filled) {
      document.getElementById('recetaError').style.display = 'block';
      return;
    }
    document.getElementById('recetaError').style.display = 'none';
  }
  document.querySelectorAll('.wstep').forEach(s => {
    const num = parseInt(s.dataset.step);
    s.classList.toggle('active', num === n);
    s.classList.toggle('done', num < n);
  });
  document.querySelectorAll('.wpane').forEach(p => {
    p.classList.toggle('active', parseInt(p.dataset.pane) === n);
  });
  if (n === 3) renderResumen();
  window.scrollTo({top:0, behavior:'smooth'});
}

function renderResumen() {
  const f = document.getElementById('formPlatillo');
  const fd = new FormData(f);
  const ings = [];
  const ids  = [...document.querySelectorAll('#ingredientes-lista .ing-id-hidden')].map(h => h.value);
  const names= [...document.querySelectorAll('#ingredientes-lista .ing-search')].map(i => i.value);
  const cants= fd.getAll('cantidad[]');
  const uns  = fd.getAll('unidad[]');
  for (let i=0;i<ids.length;i++) {
    if (!ids[i]) continue;
    ings.push(`${names[i]||'?'} — ${cants[i]||0} ${uns[i]||''}`);
  }
  const cat = fd.get('categoria_id');
  const alergs = fd.getAll('alergenos[]');
  const html = `
    <div><strong>Nombre:</strong> ${fd.get('nombre')||'—'}</div>
    <div><strong>Categoría:</strong> ${cat?(catNames[cat]||'—'):'Sin categoría'}</div>
    <div><strong>Precio:</strong> $${parseFloat(fd.get('precio')||0).toFixed(2)}</div>
    <div><strong>Tiempo:</strong> ${fd.get('tiempo_preparacion_min')} min</div>
    <div><strong>Disponible:</strong> ${fd.get('disponible')==='1'?'Sí':'No'}</div>
    <div><strong>Descripción:</strong> ${fd.get('descripcion')||'—'}</div>
    ${alergs.length?`<div><strong>Alérgenos:</strong> ${alergs.join(', ')}</div>`:''}
    ${fd.get('contiene')?`<div><strong>Contiene:</strong> ${fd.get('contiene')}</div>`:''}
    <div><strong>Receta:</strong> ${ings.length?ings.join('<br>— '):'<span style="color:#9CA3AF">Sin receta</span>'}</div>
  `;
  document.getElementById('resumen').innerHTML = html;
}

// Typeahead para ingredientes
function onIngSearch(input) {
  const row = input.closest('.ing-row');
  const hidden = row.querySelector('.ing-id-hidden');
  const ing = ingredientesMap[input.value];
  if (ing) {
    hidden.value = ing.id;
    const unidadInput = row.querySelector('input[name="unidad[]"]');
    if (unidadInput && !unidadInput.value) unidadInput.value = ing.unidad_principal;
  } else {
    hidden.value = '';
  }
}

function addIngrediente() {
  const row = document.createElement('div');
  row.className = 'ing-row';
  row.innerHTML = `
    <div style="position:relative">
      <input type="text" class="form-input ing-search" list="dlIngredientes"
             placeholder="Buscar ingrediente…" oninput="onIngSearch(this)" autocomplete="off">
      <input type="hidden" name="ingrediente_id[]" class="ing-id-hidden" value="">
    </div>
    <input type="number" name="cantidad[]" step="0.001" min="0" placeholder="Cant." class="form-input">
    <input type="text" name="unidad[]" placeholder="Unidad" value="" class="form-input">
    <label style="display:flex;align-items:center;gap:4px;font-size:.75rem;color:#6B7280;cursor:pointer;white-space:nowrap" title="No descuenta stock, solo aparece en info del cliente">
      <input type="checkbox" name="es_informativo[]" value="_new" style="cursor:pointer">
      Solo info
    </label>
    <button type="button" onclick="this.closest('.ing-row').remove()" class="btn-icon-danger">✕</button>
  `;
  document.getElementById('ingredientes-lista').appendChild(row);
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
