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
      <div class="wstep-label">Receta (opcional)</div>
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

      <!-- Paso 1: Información básica -->
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
            <?php if (empty($categorias)): ?>
            <div style="font-size:.75rem;color:#F59E0B;margin-top:4px">
              ⚠️ Sin categorías aún — créalas desde la lista del menú.
            </div>
            <?php endif; ?>
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
          <label class="form-label">Descripción <span style="color:#9CA3AF;font-weight:400">(opcional, recomendado)</span></label>
          <textarea name="descripcion" id="inpDesc" rows="3" class="form-textarea"
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

        <div class="wizard-nav">
          <a href="<?= BASE_URL ?>rest-menu/index" class="btn btn-outline">Cancelar</a>
          <button type="button" class="btn btn-primary" onclick="goStep(2)">
            Siguiente: Receta →
          </button>
        </div>
      </div>

      <!-- Paso 2: Receta -->
      <div class="wpane" data-pane="2">
        <div style="font-weight:700;color:#111827;font-size:1.05rem;margin-bottom:4px">Receta del platillo</div>
        <div style="font-size:.85rem;color:#6B7280;margin-bottom:18px;line-height:1.5">
          Define qué ingredientes lleva y cuánto. Cuando un comensal lo pida, CarniHub
          <strong>descontará automáticamente del inventario</strong> cuando el chef lo marque listo.
          Puedes saltar este paso y agregarla más tarde.
        </div>

        <?php if (empty($ingredientes)): ?>
        <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:14px;margin-bottom:16px">
          <div style="font-weight:600;color:#92400E;font-size:.9rem;margin-bottom:4px">
            ⚠️ Aún no tienes ingredientes
          </div>
          <div style="font-size:.82rem;color:#78350F;margin-bottom:10px">
            Para registrar la receta primero crea ingredientes en tu inventario (de CarniHub o de proveedores externos).
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
                   placeholder="Ej: marinar 1h"
                   value="<?= htmlspecialchars($platillo['receta']['notas'] ?? '') ?>">
          </div>
        </div>

        <div style="font-weight:600;color:#374151;font-size:.85rem;margin:14px 0 8px">
          Ingredientes
        </div>
        <div id="ingredientes-lista">
          <?php foreach (($platillo['ingredientes'] ?? []) as $ing): ?>
          <div class="ing-row">
            <select name="ingrediente_id[]" class="form-input">
              <option value="">— Ingrediente —</option>
              <?php foreach ($ingredientes as $i): ?>
              <option value="<?= $i['id'] ?>" <?= $i['id'] == $ing['ingrediente_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($i['nombre']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="cantidad[]" step="0.001" min="0" placeholder="Cant."
                   value="<?= $ing['cantidad'] ?>" class="form-input">
            <input type="text" name="unidad[]" placeholder="Unidad"
                   value="<?= htmlspecialchars($ing['unidad']) ?>" class="form-input">
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

        <div class="wizard-nav">
          <button type="button" class="btn btn-outline" onclick="goStep(1)">← Atrás</button>
          <button type="button" class="btn btn-primary" onclick="goStep(3)">
            Siguiente: Revisar →
          </button>
        </div>
      </div>

      <!-- Paso 3: Revisar -->
      <div class="wpane" data-pane="3">
        <div style="font-weight:700;color:#111827;font-size:1.05rem;margin-bottom:4px">Revisar antes de guardar</div>
        <div style="font-size:.85rem;color:#6B7280;margin-bottom:20px">
          Verifica que todo esté correcto. Puedes editar después desde la lista de platillos.
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
  .ing-row{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center}
  .btn-icon-danger{padding:8px 12px;background:#FEE2E2;color:#991B1B;border:none;border-radius:8px;
                   cursor:pointer;font-size:.85rem;transition:.15s}
  .btn-icon-danger:hover{background:#FCA5A5;color:#7F1D1D}
  .wizard-nav{display:flex;gap:10px;justify-content:space-between;margin-top:24px;padding-top:18px;border-top:1px solid #F3F4F6}
</style>

<script>
const ingredientesOptions = <?= json_encode(array_map(fn($i) => ['id'=>$i['id'],'nombre'=>$i['nombre']], $ingredientes)) ?>;
const catNames = <?= json_encode(array_column($categorias, 'nombre', 'id')) ?>;

function goStep(n) {
  // Validación paso 1
  if (n > 1) {
    const nombre = document.getElementById('inpNombre').value.trim();
    const precio = parseFloat(document.getElementById('inpPrecio').value);
    if (!nombre) { alert('El nombre del platillo es obligatorio.'); return; }
    if (isNaN(precio) || precio <= 0) { alert('Indica un precio válido.'); return; }
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
  const ids = fd.getAll('ingrediente_id[]');
  const cants = fd.getAll('cantidad[]');
  const uns = fd.getAll('unidad[]');
  for (let i=0;i<ids.length;i++) {
    if (!ids[i]) continue;
    const opt = ingredientesOptions.find(x => x.id == ids[i]);
    ings.push(`${opt?.nombre || '?'} — ${cants[i] || 0} ${uns[i] || ''}`);
  }
  const cat = fd.get('categoria_id');
  const html = `
    <div><strong>Nombre:</strong> ${fd.get('nombre') || '—'}</div>
    <div><strong>Categoría:</strong> ${cat ? (catNames[cat] || '—') : 'Sin categoría'}</div>
    <div><strong>Precio:</strong> $${parseFloat(fd.get('precio')||0).toFixed(2)}</div>
    <div><strong>Tiempo:</strong> ${fd.get('tiempo_preparacion_min')} min</div>
    <div><strong>Disponible:</strong> ${fd.get('disponible')==='1'?'Sí':'No'}</div>
    <div><strong>Descripción:</strong> ${fd.get('descripcion') || '—'}</div>
    <div><strong>Receta:</strong> ${ings.length ? ings.join(', ') : '<span style="color:#9CA3AF">Sin receta (se puede agregar después)</span>'}</div>
  `;
  document.getElementById('resumen').innerHTML = html;
}

function addIngrediente() {
  const opts = ingredientesOptions.map(i => `<option value="${i.id}">${i.nombre}</option>`).join('');
  const row = document.createElement('div');
  row.className = 'ing-row';
  row.innerHTML = `
    <select name="ingrediente_id[]" class="form-input"><option value="">— Ingrediente —</option>${opts}</select>
    <input type="number" name="cantidad[]" step="0.001" min="0" placeholder="Cant." class="form-input">
    <input type="text" name="unidad[]" placeholder="Unidad" value="kg" class="form-input">
    <button type="button" onclick="this.closest('.ing-row').remove()" class="btn-icon-danger">✕</button>
  `;
  document.getElementById('ingredientes-lista').appendChild(row);
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
