<?php ob_start(); ?>

<?php
$isEdit    = $editando ?? false;
$promo     = $promocion ?? [];
$formData  = $formData ?? null;

// Valores por defecto o desde formData (tras error de validación) o desde BD
$titulo      = htmlspecialchars($formData['titulo'] ?? $promo['titulo'] ?? '');
$descripcion = htmlspecialchars($formData['descripcion'] ?? $promo['descripcion'] ?? '');
$code        = htmlspecialchars($promo['code'] ?? '');
$expiresAt   = !empty($promo['expires_at']) ? str_replace(' ', 'T', $promo['expires_at']) : '';
$imagenUrl   = $promo['imagen'] ?? null;
$activo      = ($formData['activo'] ?? $promo['activo'] ?? 1) ? true : false;
$promoId     = (int)($promo['id'] ?? 0);
?>

<div style="max-width:800px;margin:0 auto;padding:20px">
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

  <form id="promo-form" onsubmit="return guardarPromocion(event)">
    <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= $promoId ?>">
    <?php endif; ?>

    <!-- Card: Información Básica -->
    <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:24px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1)">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
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
    <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:24px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1)">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
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

      <div class="promo-error" style="display:none;font-size:.8rem;color:#EF4444;margin-top:8px"></div>

      <script>
        document.getElementById('upload-area').addEventListener('click', function() {
          document.getElementById('inpImagen').click();
        });
      </script>
    </div>

    <!-- Card: Código y Expiración -->
    <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:24px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1)">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
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

    <!-- Card: Usuario y Estado -->
    <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:24px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1)">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:var(--cp)">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h2 style="margin:0;font-size:1.1rem;font-weight:600;color:#111827">Usuario y Visibilidad</h2>
      </div>

      <!-- Usuario -->
      <div style="margin-bottom:20px">
        <label style="display:block;font-weight:600;font-size:.9rem;color:#374151;margin-bottom:8px">
          Usuario Receptor <span style="color:#EF4444">*</span>
        </label>
        <select id="inpUsuario" name="usuario_id" class="promo-input" required
                style="width:100%;padding:12px 14px;border:1.5px solid #D1D5DB;border-radius:8px;
                       font-size:.95rem;cursor:pointer;background-color:#fff;transition:border-color 0.2s"
                onchange="validarCampo(this)">
          <option value="">Cargando usuarios...</option>
        </select>
        <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px">Selecciona a quién irá dirigida esta promoción.</div>
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

  // ──────────────────────────────────────────────────────────────
  // Manejo de Usuarios
  // ──────────────────────────────────────────────────────────────

  async function cargarUsuarios() {
    var select = document.getElementById('inpUsuario');
    try {
      var resp = await ApiClient.get('/admin/users');
      if (!resp.success) {
        select.innerHTML = '<option value="">Error cargando usuarios</option>';
        return;
      }

      var users = resp.data.users || [];
      select.innerHTML = '<option value="">Selecciona un usuario</option>';

      users.forEach(function(user) {
        var option = document.createElement('option');
        option.value = user.id;
        option.textContent = user.nombre + ' (' + user.email + ')';
        select.appendChild(option);
      });
    } catch (e) {
      select.innerHTML = '<option value="">Error cargando usuarios</option>';
    }
  }

  // ──────────────────────────────────────────────────────────────
  // Manejo de Imagen
  // ──────────────────────────────────────────────────────────────

  window.manejarSeleccionImagen = function(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];

    // Validar tipo
    if (!file.type.startsWith('image/')) {
      mostrarError('La imagen debe ser de tipo PNG, JPG o GIF');
      input.value = '';
      return;
    }

    // Validar tamaño (5MB)
    if (file.size > 5 * 1024 * 1024) {
      mostrarError('La imagen no debe exceder 5MB');
      input.value = '';
      return;
    }

    var reader = new FileReader();
    reader.onload = function(e) {
      mostrarPreviewImagen(e.target.result);
    };
    reader.readAsDataURL(file);
  };

  window.mostrarPreviewImagen = function(dataUrl) {
    var container = document.getElementById('image-preview-container');
    var uploadArea = document.getElementById('upload-area');

    var html = '<div style="position:relative;width:fit-content">' +
               '<img id="image-preview" src="' + dataUrl + '" ' +
               'style="max-width:160px;max-height:160px;border-radius:10px;object-fit:cover;border:2px solid #E5E7EB">' +
               '<button type="button" onclick="limpiarImagen()" ' +
               'style="position:absolute;top:-8px;right:-8px;background:#EF4444;color:#fff;border:none;' +
               'border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:1.2rem;line-height:1">' +
               '×</button></div>';

    container.innerHTML = html;
    uploadArea.style.display = 'none';
  };

  window.limpiarImagen = function() {
    document.getElementById('inpImagen').value = '';
    document.getElementById('image-preview-container').innerHTML = '';
    document.getElementById('upload-area').style.display = 'block';
  };

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

  window.mostrarError = mostrarError;
  window.mostrarExito = mostrarExito;

  // ──────────────────────────────────────────────────────────────
  // Guardar Promoción
  // ──────────────────────────────────────────────────────────────

  window.guardarPromocion = async function(event) {
    event.preventDefault();

    var btn = document.getElementById('btnSubmit');
    var btnText = document.getElementById('btn-text');
    var btnIcon = document.getElementById('btn-icon');

    btn.disabled = true;
    btnText.textContent = 'Guardando...';
    btnIcon.textContent = '⏳';

    var titulo = document.getElementById('inpTitulo').value.trim();
    var usuarioId = parseInt(document.getElementById('inpUsuario').value) || 0;

    if (!titulo) {
      btn.disabled = false;
      btnText.textContent = isEdit ? 'Guardar cambios' : 'Crear promoción';
      btnIcon.textContent = '💾';
      mostrarError('El título es requerido');
      return false;
    }

    if (!usuarioId) {
      btn.disabled = false;
      btnText.textContent = isEdit ? 'Guardar cambios' : 'Crear promoción';
      btnIcon.textContent = '💾';
      mostrarError('Debes seleccionar un usuario');
      return false;
    }

    var expiresAt = document.getElementById('inpExpira').value;
    if (expiresAt) {
      expiresAt = expiresAt.replace('T', ' ');
      if (expiresAt.length === 16) {
        expiresAt += ':00';
      }
    }

    var data = new FormData();
    data.append('usuario_id', usuarioId);
    data.append('titulo', titulo);
    data.append('descripcion', document.getElementById('inpDescripcion').value.trim());
    data.append('code', document.getElementById('inpCode').value.trim());
    data.append('activo', document.getElementById('inpActivo').checked ? 1 : 0);

    if (expiresAt) {
      data.append('expires_at', expiresAt);
    }

    var imagen = document.getElementById('inpImagen').files[0];
    if (imagen) {
      data.append('imagen', imagen);
    }

    var resp;
    if (isEdit && promoId > 0) {
      resp = await ApiClient.put('/admin/promotions/' + promoId, data);
    } else {
      resp = await ApiClient.post('/admin/promotions', data);
    }

    if (resp.success) {
      mostrarExito(resp.message || 'Promoción guardada correctamente.');
      setTimeout(function() {
        window.location.href = '<?= BASE_URL ?>rest-promocion/index';
      }, 1200);
      return false;
    }

    btn.disabled = false;
    btnText.textContent = isEdit ? 'Guardar cambios' : 'Crear promoción';
    btnIcon.textContent = '💾';

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
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // ──────────────────────────────────────────────────────────────
  // Inicialización
  // ──────────────────────────────────────────────────────────────

  cargarUsuarios();

  if (!ApiClient.isLoggedIn()) {
    ApiClient.getTokenFromSession();
  }

})();
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';