<?php ob_start(); ?>

<?php
$isEdit    = $editando ?? false;
$promo     = $promocion ?? [];
$formData  = $formData ?? null;

// Valores por defecto o desde formData (tras error de validación) o desde BD
$titulo      = htmlspecialchars($formData['titulo'] ?? $promo['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
$descripcion = htmlspecialchars($formData['descripcion'] ?? $promo['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
$code        = htmlspecialchars($promo['code'] ?? '', ENT_QUOTES, 'UTF-8');
$expiresAt   = !empty($promo['expires_at']) ? str_replace(' ', 'T', $promo['expires_at']) : '';
$imagenUrl   = $promo['imagen'] ?? null;
$activo      = ($formData['activo'] ?? $promo['activo'] ?? 1) ? true : false;
$promoId     = (int)($promoId ?? $promo['id'] ?? 0);
?>

<style>
  .promo-shell{max-width:980px;margin:0 auto;padding:20px}
  .promo-card{background:#fff;border:1px solid #E5E7EB;border-radius:14px;padding:24px;margin-bottom:20px;box-shadow:0 10px 24px rgba(15,23,42,.06)}
  .promo-card-header{display:flex;align-items:center;gap:10px;margin-bottom:16px}
  .promo-card-header h2{margin:0;font-size:1.1rem;font-weight:700;color:#111827}
  .promo-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .promo-label{display:block;font-weight:700;font-size:.9rem;color:#374151;margin-bottom:8px}
  .promo-help{font-size:.75rem;color:#9CA3AF;margin-top:5px}
  .promo-segment{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:16px}
  .promo-segment button{border:1.5px solid #D1D5DB;background:#fff;border-radius:10px;padding:12px;color:#374151;font-weight:700;cursor:pointer;text-align:left}
  .promo-segment button.is-active{border-color:var(--cp);background:#F8FAFC;color:#111827;box-shadow:inset 0 0 0 1px var(--cp)}
  .promo-pill-row{display:flex;gap:8px;flex-wrap:wrap}
  .promo-pill-row button{border:1px solid #D1D5DB;background:#fff;border-radius:999px;padding:8px 12px;font-weight:700;color:#374151;cursor:pointer}
  .promo-summary{background:#F8FAFC;border:1px solid #E5E7EB;border-radius:12px;padding:14px;color:#374151;font-size:.9rem}
  .promo-multiselect{min-height:168px}
  @media (max-width:760px){.promo-shell{padding:14px}.promo-grid-2,.promo-segment{grid-template-columns:1fr}}
</style>

<div class="promo-shell">
  <!-- Header -->
  <div style="display:flex;align-items:center;gap:16px;margin-bottom:32px;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>rest-promocion/index"
       style="display:flex;align-items:center;justify-content:center;width:40px;height:40px;
              border-radius:10px;background:#F3F4F6;color:#6B7280;text-decoration:none;
              border:none;cursor:pointer;transition:all 0.2s">
      <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
      </svg>
    </a>
    <div style="flex:1">
      <h1 style="margin:0;font-size:1.5rem;font-weight:700;color:#111827">
        <?= $isEdit ? '🎁 Editar Promoción' : '🎁 Nueva Promoción' ?>
      </h1>
      <p style="margin:4px 0 0;font-size:.9rem;color:#6B7280">
        <?= $isEdit ? 'Actualiza los detalles de la promoción' : 'Crea una nueva promoción especial' ?>
      </p>
    </div>
  </div>

  <!-- Alertas -->
  <div id="promo-alerts" style="margin-bottom:24px"></div>

  <form id="promo-form" onsubmit="return guardarPromocion(event)" enctype="multipart/form-data" accept-charset="UTF-8">
    <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= $promoId ?>">
    <?php endif; ?>

    <!-- Card: Información Básica -->
    <div class="promo-card">
      <div class="promo-card-header">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--cp)">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h2 style="margin:0;font-size:1.1rem;font-weight:600;color:#111827">Información Básica</h2>
      </div>

      <!-- Título -->
      <div style="margin-bottom:20px">
        <label style="display:block;font-weight:600;font-size:.9rem;color:#374151;margin-bottom:8px">
          Título de la Promoción <span style="color:#EF4444">*</span>
        </label>
        <input type="text" name="titulo" id="inpTitulo" class="promo-input"
               value="<?= $titulo ?>"
               placeholder="Ej: 15% OFF para clientes VIP"
               maxlength="255" required
               style="width:100%;padding:12px 14px;border:1.5px solid #D1D5DB;border-radius:8px;
                      font-size:.95rem;transition:border-color 0.2s"
               onchange="validarCampo(this)">
        <div class="promo-error" style="display:none;font-size:.8rem;color:#EF4444;margin-top:4px"></div>
      </div>

      <!-- Descripción -->
      <div>
        <label style="display:block;font-weight:600;font-size:.9rem;color:#374151;margin-bottom:8px">
          Descripción
        </label>
        <textarea name="descripcion" id="inpDescripcion" class="promo-input" rows="3"
                  placeholder="Describe los detalles y términos de la promoción..."
                  style="width:100%;padding:12px 14px;border:1.5px solid #D1D5DB;border-radius:8px;
                         font-size:.95rem;resize:vertical;transition:border-color 0.2s"
                  onchange="validarCampo(this)"><?= $descripcion ?></textarea>
        <div class="promo-error" style="display:none;font-size:.8rem;color:#EF4444;margin-top:4px"></div>
      </div>
    </div>

    <!-- Card: Imagen -->
    <div class="promo-card">
      <div class="promo-card-header">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--cp)">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <h2 style="margin:0;font-size:1.1rem;font-weight:600;color:#111827">Imagen</h2>
      </div>

      <!-- Preview de imagen -->
      <div id="image-preview-container" style="margin-bottom:16px">
        <?php if ($imagenUrl): ?>
        <div style="position:relative;width:fit-content">
          <img id="image-preview" src="<?= htmlspecialchars($imagenUrl) ?>" 
               alt="Preview" style="max-width:160px;max-height:160px;border-radius:10px;object-fit:cover;border:2px solid #E5E7EB">
          <button type="button" onclick="limpiarImagen()" 
                  style="position:absolute;top:-8px;right:-8px;background:#EF4444;color:#fff;border:none;
                         border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:1.2rem;line-height:1">
            ×
          </button>
        </div>
        <?php else: ?>
        <div id="image-preview" style="display:none"></div>
        <?php endif; ?>
      </div>

      <!-- Upload area -->
      <div id="upload-area" 
           style="border:2px dashed #D1D5DB;border-radius:10px;padding:24px;text-align:center;
                  cursor:pointer;background:#F9FAFB;transition:all 0.2s"
           ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
        <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 12px;color:#9CA3AF">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <p style="margin:0 0 4px;font-weight:600;color:#374151">Arrastra tu imagen aquí o haz clic</p>
        <p style="margin:0;font-size:.85rem;color:#9CA3AF">PNG, JPG, GIF (máx 5MB)</p>
      </div>

      <input type="file" id="inpImagen" name="imagen" accept="image/*" style="display:none"
             onchange="manejarSeleccionImagen(this)">
      <input type="hidden" id="inpRemoveImagen" name="remove_image" value="0">

      <div class="promo-error" style="display:none;font-size:.8rem;color:#EF4444;margin-top:8px"></div>

      <script>
        document.getElementById('upload-area').addEventListener('click', function() {
          document.getElementById('inpImagen').click();
        });
      </script>
    </div>

    <!-- Card: Código y Expiración -->
    <div class="promo-card">
      <div class="promo-card-header">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--cp)">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h2 style="margin:0;font-size:1.1rem;font-weight:600;color:#111827">Código y Validez</h2>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <!-- Código -->
        <div>
          <label style="display:block;font-weight:600;font-size:.9rem;color:#374151;margin-bottom:8px">
            Código Promocional
          </label>
          <input type="text" name="code" id="inpCode" class="promo-input"
                 value="<?= $code ?>"
                 placeholder="Ej: VERANO20"
                 maxlength="50"
                 style="width:100%;padding:12px 14px;border:1.5px solid #D1D5DB;border-radius:8px;
                        font-size:.95rem;font-family:monospace;transition:border-color 0.2s"
                 onchange="validarCampo(this)">
          <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px">Opcional. Debe ser único.</div>
          <div class="promo-error" style="display:none;font-size:.8rem;color:#EF4444;margin-top:4px"></div>
        </div>

        <!-- Expiración -->
        <div>
          <label style="display:block;font-weight:600;font-size:.9rem;color:#374151;margin-bottom:8px">
            Fecha de Expiración
          </label>
          <input type="datetime-local" name="expires_at" id="inpExpira"
                 value="<?= $expiresAt ?>"
                 style="width:100%;padding:12px 14px;border:1.5px solid #D1D5DB;border-radius:8px;
                        font-size:.95rem;transition:border-color 0.2s"
                 onchange="validarCampo(this)">
          <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px">Opcional. Sin fecha = sin expiración.</div>
          <div class="promo-error" style="display:none;font-size:.8rem;color:#EF4444;margin-top:4px"></div>
        </div>
      </div>
    </div>

    <!-- Card: Regla de descuento -->
    <div class="promo-card">
      <div class="promo-card-header">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--cp)">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m5 5h.01M19 5l-2 14-5-3-5 3-2-14 4 2 3-2 3 2 4-2z"/>
        </svg>
        <h2>Regla de descuento</h2>
      </div>

      <input type="hidden" id="inpTipoDescuento" name="tipo_descuento" value="porcentaje">

      <label class="promo-label">Tipo de promocion</label>
      <div class="promo-segment" role="group" aria-label="Tipo de promocion">
        <button type="button" data-discount-type="porcentaje" class="is-active">
          <span style="display:block;font-size:1rem">Porcentaje</span>
          <span style="display:block;font-size:.74rem;color:#6B7280;margin-top:3px">Ej. 15% OFF</span>
        </button>
        <button type="button" data-discount-type="monto_fijo">
          <span style="display:block;font-size:1rem">Monto fijo</span>
          <span style="display:block;font-size:.74rem;color:#6B7280;margin-top:3px">Ej. $50 MXN</span>
        </button>
        <button type="button" data-discount-type="bxgy">
          <span style="display:block;font-size:1rem">Paquete</span>
          <span style="display:block;font-size:.74rem;color:#6B7280;margin-top:3px">2x1, 3x2 u otro</span>
        </button>
      </div>

      <div id="discount-value-panel" class="promo-grid-2">
        <div>
          <label class="promo-label" id="lblValorDescuento">Porcentaje de descuento</label>
          <input type="number" id="inpValorDescuento" name="valor_descuento" class="promo-input" min="0" max="100" step="0.01" value="10"
                 style="width:100%;padding:12px 14px;border:1.5px solid #D1D5DB;border-radius:8px;font-size:.95rem">
          <div class="promo-help" id="helpValorDescuento">Usa valores de 1 a 100.</div>
        </div>
        <div>
          <label class="promo-label">Minimo de compra</label>
          <input type="number" id="inpMinSubtotal" name="min_subtotal" class="promo-input" min="0" step="0.01" value="0"
                 style="width:100%;padding:12px 14px;border:1.5px solid #D1D5DB;border-radius:8px;font-size:.95rem">
          <div class="promo-help">Opcional. En 0 aplica sin minimo.</div>
        </div>
      </div>

      <div id="discount-bxgy-panel" style="display:none">
        <div class="promo-pill-row" style="margin-bottom:12px">
          <button type="button" onclick="presetBxgy(2,1)">2x1</button>
          <button type="button" onclick="presetBxgy(3,2)">3x2</button>
          <button type="button" onclick="presetBxgy(4,3)">4x3</button>
        </div>
        <div class="promo-grid-2">
          <div>
            <label class="promo-label">Compra</label>
            <input type="number" id="inpBuyQty" name="buy_qty" class="promo-input" min="2" step="1" value="2"
                   style="width:100%;padding:12px 14px;border:1.5px solid #D1D5DB;border-radius:8px;font-size:.95rem">
          </div>
          <div>
            <label class="promo-label">Paga</label>
            <input type="number" id="inpPayQty" name="pay_qty" class="promo-input" min="1" step="1" value="1"
                   style="width:100%;padding:12px 14px;border:1.5px solid #D1D5DB;border-radius:8px;font-size:.95rem">
          </div>
        </div>
      </div>

      <div style="height:1px;background:#E5E7EB;margin:20px 0"></div>

      <label class="promo-label">Aplicar descuento a</label>
      <div class="promo-segment" role="group" aria-label="Alcance de promocion">
        <button type="button" data-scope-type="all" class="is-active">
          <span style="display:block;font-size:1rem">Todo el menu</span>
          <span style="display:block;font-size:.74rem;color:#6B7280;margin-top:3px">Cualquier producto</span>
        </button>
        <button type="button" data-scope-type="products">
          <span style="display:block;font-size:1rem">Productos</span>
          <span style="display:block;font-size:.74rem;color:#6B7280;margin-top:3px">Platillos especificos</span>
        </button>
        <button type="button" data-scope-type="categories">
          <span style="display:block;font-size:1rem">Categorias</span>
          <span style="display:block;font-size:.74rem;color:#6B7280;margin-top:3px">Grupo completo</span>
        </button>
      </div>
      <input type="hidden" id="inpScopeTipo" name="scope_tipo" value="all">

      <div id="scope-products-panel" style="display:none;margin-bottom:16px">
        <label class="promo-label">Productos participantes</label>
        <select id="inpProductos" name="producto_ids[]" class="promo-input promo-multiselect" multiple
                style="width:100%;padding:10px 14px;border:1.5px solid #D1D5DB;border-radius:8px;font-size:.92rem;background:#fff"></select>
        <div class="promo-help">Puedes seleccionar mas de uno con Ctrl/Cmd + clic.</div>
      </div>

      <div id="scope-categories-panel" style="display:none;margin-bottom:16px">
        <label class="promo-label">Categorias participantes</label>
        <select id="inpCategorias" name="categoria_ids[]" class="promo-input promo-multiselect" multiple
                style="width:100%;padding:10px 14px;border:1.5px solid #D1D5DB;border-radius:8px;font-size:.92rem;background:#fff"></select>
      </div>

      <div id="promo-rule-summary" class="promo-summary"></div>
    </div>

    <!-- Card: Usuario y Estado -->
    <div class="promo-card">
      <div class="promo-card-header">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--cp)">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h2 style="margin:0;font-size:1.1rem;font-weight:600;color:#111827">Usuario y Visibilidad</h2>
      </div>

      <!-- Usuario -->
      <div style="margin-bottom:20px">
        <label style="display:block;font-weight:600;font-size:.9rem;color:#374151;margin-bottom:8px">
          Usuarios Receptores <span style="color:#EF4444">*</span>
        </label>
        <input type="search" id="inpUsuarioBuscar" class="promo-input"
               placeholder="Buscar por nombre, telefono o correo"
               autocomplete="off"
               style="width:100%;padding:10px 14px;border:1.5px solid #D1D5DB;border-radius:8px;
                      font-size:.9rem;margin-bottom:8px;background:#fff">
        <select id="inpUsuario" name="usuario_ids[]" class="promo-input" required <?= $isEdit ? '' : 'multiple size="10"' ?>
                style="width:100%;padding:12px 14px;border:1.5px solid #D1D5DB;border-radius:8px;
                       font-size:.95rem;cursor:pointer;background-color:#fff;transition:border-color 0.2s;line-height:1.6"
                onchange="validarCampo(this)">
          <option value="">Cargando usuarios...</option>
        </select>
        <div id="usuariosFiltroInfo" style="font-size:.72rem;color:#6B7280;margin-top:5px"></div>
        <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px"><?= $isEdit ? 'Selecciona el usuario registrado en la app m&oacute;vil.' : 'Selecciona uno o varios usuarios registrados en la app m&oacute;vil.' ?></div>
        <div class="promo-error" style="display:none;font-size:.8rem;color:#EF4444;margin-top:4px"></div>
      </div>

      <!-- Estado activo -->
      <div style="padding:16px;background:#F9FAFB;border-radius:8px;border:1px solid #E5E7EB">
        <label style="display:flex;align-items:center;gap:12px;cursor:pointer;margin:0">
          <input type="checkbox" name="activo" value="1" id="inpActivo"
                 <?= $activo ? 'checked' : '' ?>
                 style="width:20px;height:20px;cursor:pointer;accent-color:var(--cp)">
          <div style="flex:1">
            <div style="font-weight:600;font-size:.95rem;color:#111827">Promoción Activa</div>
            <div style="font-size:.8rem;color:#6B7280;margin-top:2px">
              <?= $activo ? '✓ La promoción se mostrará en la app móvil' : '✗ La promoción está oculta' ?>
            </div>
          </div>
        </label>
      </div>
    </div>

    <!-- Botones de acción -->
    <div style="display:flex;gap:12px;justify-content:flex-end;padding:24px 0">
      <a href="<?= BASE_URL ?>rest-promocion/index"
         style="padding:12px 28px;border:1.5px solid #D1D5DB;border-radius:8px;background:#fff;
                color:#374151;font-size:.95rem;font-weight:600;text-decoration:none;
                text-align:center;cursor:pointer;transition:all 0.2s">
        Cancelar
      </a>
      <button type="submit" id="btnSubmit" class="btn btn-primary"
              style="padding:12px 32px;background:var(--cp);color:#fff;border:none;border-radius:8px;
                     font-size:.95rem;font-weight:600;cursor:pointer;transition:all 0.2s;
                     display:inline-flex;align-items:center;gap:8px">
        <span id="btn-icon">💾</span>
        <span id="btn-text"><?= $isEdit ? 'Guardar cambios' : 'Crear promoción' ?></span>
      </button>
    </div>
  </form>
</div>

<script>
(function() {
  'use strict';

  var isEdit = <?= $isEdit ? 'true' : 'false' ?>;
  var promoId = <?= $promoId ?>;
  var currentImagen = <?= json_encode($imagenUrl ?? '', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  var imagenQuitada = false;
  var pendingUsuarioId = '';
  var pendingScopeIds = [];
  var promoCatalog = { products: [], categories: [] };
  var usuariosAppTotal = 0;

  // ──────────────────────────────────────────────────────────────
  // Carga inicial
  // ──────────────────────────────────────────────────────────────

  async function cargarUsuarios() {
    var select = document.getElementById('inpUsuario');
    try {
      var resp = await ApiClient.get('/admin/users?per_page=all');
      if (!resp.success) {
        select.innerHTML = '<option value="">Error cargando usuarios</option>';
        return;
      }

      var payload = resp.data || {};
      var users = payload.users || (payload.data && payload.data.users) || resp.users || [];
      if (!Array.isArray(users)) {
        users = [];
      }
      usuariosAppTotal = users.length;
      select.innerHTML = <?= $isEdit ? '\'<option value="">Selecciona un usuario de la app</option>\'' : "''" ?>;
      if (users.length === 0) {
        select.innerHTML = '<option value="">No hay usuarios registrados en la app movil</option>';
        actualizarInfoUsuarios(0, 0);
        return;
      }

      users.forEach(function(user) {
        var option = document.createElement('option');
        option.value = user.id;
        var nombre = user.nombre || user.name || user.full_name || ('Usuario ' + user.id);
        var telefono = user.phone || user.telefono || '';
        var correo = user.email || user.correo || '';
        var contacto = telefono || correo || '';
        option.textContent = contacto ? (nombre + '  |  ' + contacto) : nombre;
        option.dataset.search = normalizarBusqueda([nombre, telefono, correo].join(' '));
        select.appendChild(option);
      });

      seleccionarUsuarioPendiente();
      filtrarUsuarios();
    } catch (e) {
      select.innerHTML = '<option value="">Error cargando usuarios</option>';
      actualizarInfoUsuarios(0, usuariosAppTotal);
    }
  }

  function normalizarBusqueda(value) {
    return String(value || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function actualizarInfoUsuarios(visibles, total) {
    var info = document.getElementById('usuariosFiltroInfo');
    if (!info) return;
    if (total <= 0) {
      info.textContent = '';
      return;
    }
    info.textContent = visibles === total
      ? total + ' usuario' + (total === 1 ? '' : 's') + ' disponible' + (total === 1 ? '' : 's')
      : visibles + ' de ' + total + ' usuario' + (total === 1 ? '' : 's') + ' coinciden con la busqueda';
  }

  function filtrarUsuarios() {
    var input = document.getElementById('inpUsuarioBuscar');
    var select = document.getElementById('inpUsuario');
    if (!input || !select) return;

    var q = normalizarBusqueda(input.value);
    var visibles = 0;
    var total = 0;
    for (var i = 0; i < select.options.length; i++) {
      var option = select.options[i];
      if (!option.value) {
        option.hidden = q.length > 0;
        continue;
      }
      total++;
      var match = q === '' || (option.dataset.search || '').indexOf(q) !== -1;
      option.hidden = !match;
      if (match) visibles++;
    }
    actualizarInfoUsuarios(visibles, total);
  }

  async function cargarCatalogoPromos() {
    try {
      var resp = await ApiClient.get('/admin/promo-catalog');
      if (!resp.success) {
        promoCatalog = { products: [], categories: [] };
        pintarCatalogoPromos();
        return;
      }
      promoCatalog.products = (resp.data && resp.data.products) || [];
      promoCatalog.categories = (resp.data && resp.data.categories) || [];
      pintarCatalogoPromos();
    } catch (e) {
      promoCatalog = { products: [], categories: [] };
      pintarCatalogoPromos();
    }
  }

  function pintarCatalogoPromos() {
    var productsSelect = document.getElementById('inpProductos');
    var categoriesSelect = document.getElementById('inpCategorias');
    productsSelect.innerHTML = '';
    categoriesSelect.innerHTML = '';

    promoCatalog.products.forEach(function(product) {
      var option = document.createElement('option');
      option.value = product.id;
      var categoria = product.categoria_nombre ? (' - ' + product.categoria_nombre) : '';
      var precio = product.precio ? ('  $' + Number(product.precio).toFixed(2)) : '';
      option.textContent = (product.nombre || ('Producto ' + product.id)) + categoria + precio;
      productsSelect.appendChild(option);
    });
    if (promoCatalog.products.length === 0) {
      var emptyProduct = document.createElement('option');
      emptyProduct.value = '';
      emptyProduct.disabled = true;
      emptyProduct.textContent = 'No hay productos activos para este restaurante';
      productsSelect.appendChild(emptyProduct);
    }

    promoCatalog.categories.forEach(function(category) {
      var option = document.createElement('option');
      option.value = category.id;
      option.textContent = category.nombre || ('Categoria ' + category.id);
      categoriesSelect.appendChild(option);
    });
    if (promoCatalog.categories.length === 0) {
      var emptyCategory = document.createElement('option');
      emptyCategory.value = '';
      emptyCategory.disabled = true;
      emptyCategory.textContent = 'No hay categorias activas para este restaurante';
      categoriesSelect.appendChild(emptyCategory);
    }

    aplicarScopePendiente();
    actualizarResumenPromo();
  }

  async function cargarPromocionParaEditar() {
    if (!isEdit || promoId <= 0) return;

    setSubmitState(true, 'Cargando...', '⏳');
    try {
      var resp = await ApiClient.get('/admin/promotions/' + promoId);
      if (!resp.success) {
        mostrarError(resp.message || 'No se pudo cargar la promoción.');
        return;
      }

      aplicarPromocion(resp.data || {});
    } catch (e) {
      mostrarError('Error de conexión al cargar la promoción: ' + e.message);
    } finally {
      setSubmitState(false, 'Guardar cambios', '💾');
    }
  }

  function aplicarPromocion(p) {
    document.getElementById('inpTitulo').value = p.titulo || '';
    document.getElementById('inpDescripcion').value = p.descripcion || '';
    document.getElementById('inpCode').value = p.code || '';
    document.getElementById('inpActivo').checked = parseInt(p.activo) === 1 || p.activo === true;

    if (p.expires_at) {
      document.getElementById('inpExpira').value = formatearDatetimeLocal(p.expires_at);
    } else {
      document.getElementById('inpExpira').value = '';
    }

    pendingUsuarioId = String(p.usuario_id || p.user_id || (p.usuario && p.usuario.id) || '');
    seleccionarUsuarioPendiente();

    setDiscountType(p.tipo_descuento || 'porcentaje');
    document.getElementById('inpValorDescuento').value = p.valor_descuento || 10;
    document.getElementById('inpBuyQty').value = p.buy_qty || 2;
    document.getElementById('inpPayQty').value = p.pay_qty || 1;
    document.getElementById('inpMinSubtotal').value = p.min_subtotal || 0;
    setScopeType(p.scope_tipo || 'all');
    pendingScopeIds = parseScopeIds(p.scope_ids);
    aplicarScopePendiente();
    actualizarResumenPromo();

    currentImagen = p.imagen || '';
    imagenQuitada = false;
    document.getElementById('inpRemoveImagen').value = '0';
    if (currentImagen) {
      mostrarPreviewImagen(currentImagen, true);
    } else {
      limpiarPreviewImagen();
    }
  }

  function seleccionarUsuarioPendiente() {
    if (!pendingUsuarioId) return;
    var select = document.getElementById('inpUsuario');
    if (!select || !select.options || select.options.length <= 1) return;
    for (var i = 0; i < select.options.length; i++) {
      select.options[i].selected = String(select.options[i].value) === pendingUsuarioId;
    }
  }

  function setDiscountType(type) {
    type = ['porcentaje', 'monto_fijo', 'bxgy'].indexOf(type) >= 0 ? type : 'porcentaje';
    document.getElementById('inpTipoDescuento').value = type;
    document.querySelectorAll('[data-discount-type]').forEach(function(btn) {
      btn.classList.toggle('is-active', btn.getAttribute('data-discount-type') === type);
    });
    var valuePanel = document.getElementById('discount-value-panel');
    var bxgyPanel = document.getElementById('discount-bxgy-panel');
    var label = document.getElementById('lblValorDescuento');
    var help = document.getElementById('helpValorDescuento');
    var valueInput = document.getElementById('inpValorDescuento');

    if (type === 'bxgy') {
      valuePanel.style.display = 'none';
      bxgyPanel.style.display = 'block';
    } else {
      valuePanel.style.display = 'grid';
      bxgyPanel.style.display = 'none';
      label.textContent = type === 'monto_fijo' ? 'Monto de descuento' : 'Porcentaje de descuento';
      help.textContent = type === 'monto_fijo' ? 'Monto en MXN que se descontara.' : 'Usa valores de 1 a 100.';
      valueInput.max = type === 'monto_fijo' ? '' : '100';
      if (!valueInput.value || Number(valueInput.value) <= 0) {
        valueInput.value = type === 'monto_fijo' ? '50' : '10';
      }
    }
    actualizarResumenPromo();
  }

  function setScopeType(type) {
    type = ['all', 'products', 'categories'].indexOf(type) >= 0 ? type : 'all';
    document.getElementById('inpScopeTipo').value = type;
    document.querySelectorAll('[data-scope-type]').forEach(function(btn) {
      btn.classList.toggle('is-active', btn.getAttribute('data-scope-type') === type);
    });
    document.getElementById('scope-products-panel').style.display = type === 'products' ? 'block' : 'none';
    document.getElementById('scope-categories-panel').style.display = type === 'categories' ? 'block' : 'none';
    actualizarResumenPromo();
  }

  function aplicarScopePendiente() {
    var type = document.getElementById('inpScopeTipo').value || 'all';
    if (!pendingScopeIds.length || type === 'all') return;
    var select = type === 'products' ? document.getElementById('inpProductos') : document.getElementById('inpCategorias');
    setSelectedValues(select, pendingScopeIds);
  }

  function parseScopeIds(value) {
    if (!value) return [];
    if (Array.isArray(value)) return value.map(Number).filter(Boolean);
    try {
      var parsed = JSON.parse(value);
      if (Array.isArray(parsed)) return parsed.map(Number).filter(Boolean);
    } catch (e) {}
    return String(value).split(/[,\s]+/).map(Number).filter(Boolean);
  }

  function setSelectedValues(select, values) {
    if (!select) return;
    var lookup = {};
    values.forEach(function(value) { lookup[String(value)] = true; });
    for (var i = 0; i < select.options.length; i++) {
      select.options[i].selected = !!lookup[String(select.options[i].value)];
    }
  }

  function getSelectedValues(id) {
    var select = document.getElementById(id);
    if (!select) return [];
    var values = [];
    for (var i = 0; i < select.options.length; i++) {
      if (select.options[i].selected) {
        var value = parseInt(select.options[i].value, 10);
        if (value > 0) values.push(value);
      }
    }
    return values;
  }

  window.presetBxgy = function(buy, pay) {
    document.getElementById('inpBuyQty').value = buy;
    document.getElementById('inpPayQty').value = pay;
    actualizarResumenPromo();
  };

  function actualizarResumenPromo() {
    var type = document.getElementById('inpTipoDescuento').value || 'porcentaje';
    var scope = document.getElementById('inpScopeTipo').value || 'all';
    var resumen = '';
    if (type === 'bxgy') {
      resumen = 'Cliente compra ' + (document.getElementById('inpBuyQty').value || 2) +
        ' y paga ' + (document.getElementById('inpPayQty').value || 1) + '.';
    } else if (type === 'monto_fijo') {
      resumen = 'Descuento de $' + Number(document.getElementById('inpValorDescuento').value || 0).toFixed(2) + ' MXN.';
    } else {
      resumen = 'Descuento de ' + Number(document.getElementById('inpValorDescuento').value || 0).toFixed(2) + '%.';
    }

    if (scope === 'products') {
      resumen += ' Aplica a ' + getSelectedValues('inpProductos').length + ' producto(s).';
    } else if (scope === 'categories') {
      resumen += ' Aplica a ' + getSelectedValues('inpCategorias').length + ' categoria(s).';
    } else {
      resumen += ' Aplica a todo el menu.';
    }

    var min = Number(document.getElementById('inpMinSubtotal').value || 0);
    if (min > 0) {
      resumen += ' Compra minima: $' + min.toFixed(2) + ' MXN.';
    }
    document.getElementById('promo-rule-summary').textContent = resumen;
  }

  // ──────────────────────────────────────────────────────────────
  // Manejo de Imagen
  // ──────────────────────────────────────────────────────────────

  window.manejarSeleccionImagen = function(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    if (allowed.indexOf(file.type) === -1) {
      mostrarError('La imagen debe ser JPG, PNG, WEBP o GIF');
      input.value = '';
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      mostrarError('La imagen no debe exceder 5MB');
      input.value = '';
      return;
    }

    var reader = new FileReader();
    reader.onload = function(e) {
      imagenQuitada = false;
      document.getElementById('inpRemoveImagen').value = '0';
      mostrarPreviewImagen(e.target.result, false);
    };
    reader.readAsDataURL(file);
  };

  window.mostrarPreviewImagen = function(src, existente) {
    var container = document.getElementById('image-preview-container');
    var uploadArea = document.getElementById('upload-area');

    var html = '<div style="position:relative;width:fit-content">' +
               '<img id="image-preview" src="' + escAttr(src) + '" alt="Preview" ' +
               'style="max-width:160px;max-height:160px;border-radius:10px;object-fit:cover;border:2px solid #E5E7EB">' +
               '<button type="button" onclick="limpiarImagen()" ' +
               'style="position:absolute;top:-8px;right:-8px;background:#EF4444;color:#fff;border:none;' +
               'border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:1.2rem;line-height:1">' +
               '×</button></div>';

    container.innerHTML = html;
    uploadArea.style.display = 'none';
  };

  window.limpiarImagen = function() {
    imagenQuitada = true;
    currentImagen = '';
    document.getElementById('inpRemoveImagen').value = '1';
    document.getElementById('inpImagen').value = '';
    limpiarPreviewImagen();
  };

  function limpiarPreviewImagen() {
    document.getElementById('image-preview-container').innerHTML = '';
    document.getElementById('upload-area').style.display = 'block';
  }

  function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('upload-area').style.backgroundColor = '#F0F9FF';
    document.getElementById('upload-area').style.borderColor = 'var(--cp)';
  }

  function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('upload-area').style.backgroundColor = '#F9FAFB';
    document.getElementById('upload-area').style.borderColor = '#D1D5DB';
  }

  function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    document.getElementById('upload-area').style.backgroundColor = '#F9FAFB';
    document.getElementById('upload-area').style.borderColor = '#D1D5DB';

    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      document.getElementById('inpImagen').files = e.dataTransfer.files;
      manejarSeleccionImagen(document.getElementById('inpImagen'));
    }
  }

  window.handleDragOver = handleDragOver;
  window.handleDragLeave = handleDragLeave;
  window.handleDrop = handleDrop;

  // ──────────────────────────────────────────────────────────────
  // Validación de Campos
  // ──────────────────────────────────────────────────────────────

  function validarCampo(input) {
    var isValid = true;
    var errorDiv = input.closest('div').querySelector('.promo-error') || 
                   input.parentElement.querySelector('.promo-error');

    if (!errorDiv) {
      input.parentElement.style.borderColor = isValid ? '#D1D5DB' : '#EF4444';
      return isValid;
    }

    if (input.type === 'text' && input.id === 'inpTitulo') {
      isValid = input.value.trim().length > 0;
    }

    if (isValid) {
      input.style.borderColor = '#D1D5DB';
      errorDiv.style.display = 'none';
    } else {
      input.style.borderColor = '#EF4444';
      errorDiv.style.display = 'block';
      errorDiv.textContent = 'Este campo es requerido';
    }

    return isValid;
  }

  window.validarCampo = validarCampo;

  document.querySelectorAll('[data-discount-type]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      setDiscountType(btn.getAttribute('data-discount-type'));
    });
  });
  document.querySelectorAll('[data-scope-type]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      setScopeType(btn.getAttribute('data-scope-type'));
    });
  });
  ['inpValorDescuento', 'inpBuyQty', 'inpPayQty', 'inpMinSubtotal', 'inpProductos', 'inpCategorias'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', actualizarResumenPromo);
      el.addEventListener('change', actualizarResumenPromo);
    }
  });

  function mostrarError(msg) {
    var container = document.getElementById('promo-alerts');
    var html = '<div style="background:#FEF2F2;border:1.5px solid #FECACA;border-radius:10px;' +
               'padding:12px 16px;color:#DC2626;font-size:.9rem;display:flex;align-items:center;gap:10px">' +
               '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
               '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>' +
               '</svg>' +
               '<div>' + esc(msg) + '</div>' +
               '</div>';
    container.innerHTML = html;

    setTimeout(function() {
      container.innerHTML = '';
    }, 5000);
  }

  function mostrarExito(msg) {
    var container = document.getElementById('promo-alerts');
    var html = '<div style="background:#ECFDF5;border:1.5px solid #A7F3D0;border-radius:10px;' +
               'padding:12px 16px;color:#059669;font-size:.9rem;display:flex;align-items:center;gap:10px">' +
               '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
               '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>' +
               '</svg>' +
               '<div>' + esc(msg) + '</div>' +
               '</div>';
    container.innerHTML = html;
  }

  function mensajeGuardadoPromocion(resp) {
    var data = resp && resp.data ? resp.data : {};
    var summary = data.notification_summary
      || (data.local_promotion && data.local_promotion.notification_summary)
      || null;
    if (!summary) {
      return resp.message || 'Promocion guardada correctamente.';
    }
    var error = (summary.error || '').toString();
    if (error === 'invalid_push_token') {
      return 'Promocion guardada correctamente. El push no se envio porque el token del usuario vencio; cuando abra la app se registrara uno nuevo.';
    }
    if (error === 'no_push_token') {
      return 'Promocion guardada correctamente. El usuario aun no tiene token push activo; la vera al abrir la app.';
    }
    if (summary.status === 'failed') {
      return 'Promocion guardada correctamente. El push no se pudo enviar ahora, pero la promocion quedo disponible en la app.';
    }
    return resp.message || 'Promocion guardada correctamente.';
  }

  window.mostrarError = mostrarError;
  window.mostrarExito = mostrarExito;

  // ──────────────────────────────────────────────────────────────
  // Guardar Promoción
  // ──────────────────────────────────────────────────────────────

  window.guardarPromocion = async function(event) {
    event.preventDefault();

    setSubmitState(true, 'Guardando...', '⏳');

    var titulo = document.getElementById('inpTitulo').value.trim();
    var usuarioIds = getUsuariosSeleccionados();
    var usuarioId = usuarioIds.length ? usuarioIds[0] : 0;
    var scopeTipo = document.getElementById('inpScopeTipo').value || 'all';
    var productoIds = getSelectedValues('inpProductos');
    var categoriaIds = getSelectedValues('inpCategorias');

    if (!titulo) {
      setSubmitState(false, isEdit ? 'Guardar cambios' : 'Crear promoción', '💾');
      mostrarError('El título es requerido');
      return false;
    }

    if (!usuarioId) {
      setSubmitState(false, isEdit ? 'Guardar cambios' : 'Crear promoción', '💾');
      mostrarError('Debes seleccionar al menos un usuario');
      return false;
    }

    if (scopeTipo === 'products' && productoIds.length === 0) {
      setSubmitState(false, isEdit ? 'Guardar cambios' : 'Crear promociÃ³n', 'ðŸ’¾');
      setSubmitState(false, isEdit ? 'Guardar cambios' : 'Crear promocion', 'Guardar');
      mostrarError('Selecciona al menos un producto para esta promocion');
      return false;
    }

    if (scopeTipo === 'categories' && categoriaIds.length === 0) {
      setSubmitState(false, isEdit ? 'Guardar cambios' : 'Crear promociÃ³n', 'ðŸ’¾');
      setSubmitState(false, isEdit ? 'Guardar cambios' : 'Crear promocion', 'Guardar');
      mostrarError('Selecciona al menos una categoria para esta promocion');
      return false;
    }

    var expiresAt = document.getElementById('inpExpira').value;
    if (expiresAt) {
      expiresAt = expiresAt.replace('T', ' ');
      if (expiresAt.length === 16) {
        expiresAt += ':00';
      }
    }

    var payload = {
      usuario_id: usuarioId,
      usuario_ids: usuarioIds,
      titulo: titulo,
      descripcion: document.getElementById('inpDescripcion').value.trim(),
      code: document.getElementById('inpCode').value.trim() || null,
      expires_at: expiresAt || null,
      tipo_descuento: document.getElementById('inpTipoDescuento').value,
      valor_descuento: document.getElementById('inpValorDescuento').value || 0,
      scope_tipo: scopeTipo,
      producto_ids: productoIds,
      categoria_ids: categoriaIds,
      buy_qty: document.getElementById('inpBuyQty').value || null,
      pay_qty: document.getElementById('inpPayQty').value || null,
      min_subtotal: document.getElementById('inpMinSubtotal').value || 0,
      activo: document.getElementById('inpActivo').checked ? 1 : 0
    };
    var imagen = document.getElementById('inpImagen').files[0];
    var usarMultipart = !!imagen || imagenQuitada;

    var resp;
    try {
      if (usarMultipart) {
        var data = new FormData();
        appendPayload(data, payload);
        if (imagenQuitada) data.append('remove_image', '1');
        if (imagen) data.append('imagen', imagen);

        if (isEdit && promoId > 0) {
          data.append('_method', 'PUT');
          resp = await ApiClient.post('/admin/promotions/' + promoId, data);
        } else {
          resp = await ApiClient.post('/admin/promotions', data);
        }
      } else if (isEdit && promoId > 0) {
        resp = await ApiClient.put('/admin/promotions/' + promoId, payload);
      } else {
        resp = await ApiClient.post('/admin/promotions', payload);
      }
    } catch (e) {
      resp = { success: false, message: 'Error de conexión: ' + e.message };
    }

    if (resp.success) {
      resp.message = mensajeGuardadoPromocion(resp);
      mostrarExito(resp.message || 'Promoción guardada correctamente.');
      setTimeout(function() {
        window.location.href = '<?= BASE_URL ?>rest-promocion/index';
      }, 1200);
      return false;
    }

    setSubmitState(false, isEdit ? 'Guardar cambios' : 'Crear promoción', '💾');

    var errorMsg = resp.message || 'Error al guardar';
    if (resp.errors) {
      var errList = [];
      for (var campo in resp.errors) {
        if (resp.errors.hasOwnProperty(campo)) {
          var msgs = resp.errors[campo];
          if (Array.isArray(msgs)) {
            errList.push(msgs.join(', '));
          } else {
            errList.push(msgs);
          }
        }
      }
      errorMsg = errList.join('. ');
    }

    mostrarError(errorMsg);
    return false;
  };

  // ──────────────────────────────────────────────────────────────
  // Utilidades
  // ──────────────────────────────────────────────────────────────

  function esc(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str == null ? '' : str)));
    return div.innerHTML;
  }

  function escAttr(str) {
    return esc(str).replace(/"/g, '&quot;');
  }

  function appendPayload(fd, payload) {
    for (var key in payload) {
      if (!payload.hasOwnProperty(key)) continue;
      if (Array.isArray(payload[key])) {
        payload[key].forEach(function(value) {
          fd.append(key + '[]', value);
        });
        continue;
      }
      fd.append(key, payload[key] == null ? '' : payload[key]);
    }
  }

  function getUsuariosSeleccionados() {
    var select = document.getElementById('inpUsuario');
    if (!select) return [];
    var values = [];
    for (var i = 0; i < select.options.length; i++) {
      if (select.options[i].selected) {
        var id = parseInt(select.options[i].value, 10);
        if (id > 0) values.push(id);
      }
    }
    return values;
  }

  function setSubmitState(disabled, text, icon) {
    var btn = document.getElementById('btnSubmit');
    var btnText = document.getElementById('btn-text');
    var btnIcon = document.getElementById('btn-icon');
    btn.disabled = disabled;
    btnText.textContent = text;
    btnIcon.textContent = icon;
  }

  function formatearDatetimeLocal(fechaStr) {
    if (!fechaStr) return '';
    var normalized = String(fechaStr).replace(' ', 'T');
    if (normalized.length >= 16) return normalized.substring(0, 16);
    return normalized;
  }

  // ──────────────────────────────────────────────────────────────
  // Inicialización
  // ──────────────────────────────────────────────────────────────

  async function iniciar() {
    if (!ApiClient.isLoggedIn()) {
      await ApiClient.getTokenFromSession();
    }

    var usuarioBuscar = document.getElementById('inpUsuarioBuscar');
    if (usuarioBuscar) {
      usuarioBuscar.addEventListener('input', filtrarUsuarios);
    }

    await cargarUsuarios();
    await cargarCatalogoPromos();
    await cargarPromocionParaEditar();
    setDiscountType(document.getElementById('inpTipoDescuento').value || 'porcentaje');
    setScopeType(document.getElementById('inpScopeTipo').value || 'all');
    actualizarResumenPromo();
  }

  iniciar();

})();
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
