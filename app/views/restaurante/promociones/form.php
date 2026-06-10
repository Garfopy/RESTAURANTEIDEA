<?php ob_start(); ?>

<?php
$isEdit    = $editando ?? false;
$promo     = $promocion ?? [];
$comList   = $comensales ?? [];
$selected  = $asignados ?? [];
$formData  = $formData ?? null;

// Valores por defecto o desde formData (tras error de validación) o desde BD
$titulo      = htmlspecialchars($formData['titulo'] ?? $promo['titulo'] ?? '');
$descripcion = htmlspecialchars($formData['descripcion'] ?? $promo['descripcion'] ?? '');
$tipo        = $formData['tipo'] ?? $promo['tipo'] ?? 'porcentaje';
$valor       = $formData['valor_descuento'] ?? $promo['valor_descuento'] ?? 0;
$fInicio     = $formData['fecha_inicio'] ?? $promo['fecha_inicio'] ?? date('Y-m-d');
$fFin        = $formData['fecha_fin'] ?? $promo['fecha_fin'] ?? date('Y-m-d', strtotime('+30 days'));
$activo      = ($formData['activo'] ?? $promo['activo'] ?? 1) ? true : false;
$promoId     = (int)($promo['id'] ?? 0);
?>

<div style="max-width:720px">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
    <a href="<?= BASE_URL ?>rest-promocion/index"
       style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;
              border-radius:8px;border:1.5px solid #D1D5DB;background:#fff;color:#6B7280;text-decoration:none">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <div>
      <div style="font-weight:700;font-size:1.1rem;color:#111827"><?= $isEdit ? 'Editar Promoción' : 'Nueva Promoción' ?></div>
      <div style="font-size:.78rem;color:#6B7280">Configura el descuento y asigna usuarios</div>
    </div>
  </div>

  <!-- Contenedor de errores de API -->
  <div id="form-errors" style="display:none;background:#FEF2F2;border:1.5px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:20px"></div>

  <form id="promo-form" onsubmit="return guardarPromocion(event)">
    <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= $promoId ?>">
    <?php endif; ?>

    <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:20px 24px;margin-bottom:20px">
      <!-- Título -->
      <div class="form-group">
        <label class="form-label">Título de la promoción *</label>
        <input type="text" name="titulo" class="form-input" id="inpTitulo"
               value="<?= $titulo ?>"
               placeholder="Ej: 15% off para clientes VIP"
               required maxlength="255">
      </div>

      <!-- Imagen -->
      <div class="form-group">
        <label class="form-label">Imagen</label>
        <input type="file" name="imagen" class="form-input" id="inpImagen">
        <?php if (!empty($promo['imagen'])): ?>
        <div style="margin-top:8px">
          <img src="<?= htmlspecialchars($promo['imagen']) ?>" alt="Imagen de la promoción" style="max-width:100px;max-height:100px;border-radius:8px">
        </div>
        <?php endif; ?>
      </div>

      <!-- Descripción -->
      <div class="form-group">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-textarea" rows="2" id="inpDescripcion"
                  placeholder="Describe los detalles de la promoción..."><?= $descripcion ?></textarea>
      </div>

      <!-- Código -->
      <div class="form-group">
        <label class="form-label">Código promocional</label>
        <input type="text" name="code" class="form-input" id="inpCode"
               value="<?= htmlspecialchars($promo['code'] ?? '') ?>"
               placeholder="Ej: VERANO20 (opcional, debe ser único)"
               maxlength="50">
        <div style="font-size:.73rem;color:#9CA3AF;margin-top:2px">
          Opcional. Si se usa, los clientes podrán aplicar este código en la app.
        </div>
      </div>

      <!-- Expiración -->
      <div class="form-group">
        <label class="form-label">Fecha de expiración</label>
        <input type="datetime-local" name="expires_at" class="form-input" id="inpExpira"
               value="<?= htmlspecialchars(!empty($promo['expires_at']) ? str_replace(' ', 'T', $promo['expires_at']) : '') ?>">
        <div style="font-size:.73rem;color:#9CA3AF;margin-top:2px">
          Opcional. Formato: YYYY-MM-DD HH:MM:SS. Si no se especifica, la promoción no expira.
        </div>
      </div>

      <!-- Usuario (auto-filled desde JWT) -->
      <div class="form-group">
        <label class="form-label">Usuario *</label>
                <select id="inpUsuario"
                name="usuario_id"
                class="form-input"
                required>
            <option value="">Cargando usuarios...</option>
        </select>

        <div style="font-size:.73rem;color:#9CA3AF;margin-top:2px">
          Selecciona el usuario que recibirá la promoción.
        </div>
      </div>

      <!-- Checkbox activo -->
      <div style="margin-top:16px">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
          <input type="checkbox" name="activo" value="1" id="inpActivo"
                 <?= $activo ? 'checked' : '' ?>
                 style="width:18px;height:18px;accent-color:var(--cp)">
          <span style="font-weight:500;font-size:.88rem;color:#374151">Promoción activa</span>
        </label>
        <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px;margin-left:28px">
          Desmarca para pausar sin eliminar. La promoción no se mostrará en la app.
        </div>
      </div>
    </div>

    <!-- Botones -->
    <div style="display:flex;justify-content:flex-end;gap:10px">
      <a href="<?= BASE_URL ?>rest-promocion/index"
         style="padding:10px 20px;border:1.5px solid #D1D5DB;border-radius:8px;background:#fff;
                color:#374151;font-size:.85rem;font-weight:600;text-decoration:none;text-align:center">
        Cancelar
      </a>
      <button type="submit" class="btn btn-primary" id="btnSubmit">
        <?= $isEdit ? 'Guardar cambios' : 'Crear promoción' ?>
      </button>
    </div>
  </form>
</div>

<script>
(function() {
  'use strict';

  var isEdit = <?= $isEdit ? 'true' : 'false' ?>;
  var promoId = <?= $promoId ?>;

  async function cargarUsuarios() {

    var select = document.getElementById('inpUsuario');

    try {

      var resp = await ApiClient.get('/admin/users');

      if (!resp.success) {
        select.innerHTML = '<option value="">Error cargando usuarios</option>';
        return;
      }

      var users = resp.data.users || [];

      select.innerHTML =
        '<option value="">Selecciona un usuario</option>';

      users.forEach(function(user) {

        var option = document.createElement('option');

        option.value = user.id;
        option.textContent = user.nombre + ' (' + user.email + ')';

        select.appendChild(option);

      });

    } catch (e) {

      select.innerHTML =
        '<option value="">Error cargando usuarios</option>';

    }
  }

  window.guardarPromocion = async function(event) {

    event.preventDefault();

    var btn = document.getElementById('btnSubmit');
    var errorsContainer = document.getElementById('form-errors');

    btn.disabled = true;
    btn.textContent = 'Guardando...';

    errorsContainer.style.display = 'none';
    errorsContainer.innerHTML = '';

    var usuarioId =
      parseInt(document.getElementById('inpUsuario').value) || 0;

    if (!usuarioId) {

      btn.disabled = false;
      btn.textContent =
        isEdit ? 'Guardar cambios' : 'Crear promoción';

      errorsContainer.innerHTML =
        '<div class="api-error">Selecciona un usuario.</div>';

      errorsContainer.style.display = 'block';

      return;
    }

    var expiresAt = document.getElementById('inpExpira').value;

    if (expiresAt) {

      expiresAt = expiresAt.replace('T', ' ');

      if (expiresAt.length === 16) {
        expiresAt += ':00';
      }

    }

    // FormData para soportar imágenes
    var data = new FormData();

    data.append(
      'usuario_id',
      usuarioId
    );

    data.append(
      'titulo',
      document.getElementById('inpTitulo').value.trim()
    );

    data.append(
      'descripcion',
      document.getElementById('inpDescripcion').value.trim()
    );

    data.append(
      'code',
      document.getElementById('inpCode').value.trim()
    );

    data.append(
      'activo',
      document.getElementById('inpActivo').checked ? 1 : 0
    );

    if (expiresAt) {
      data.append('expires_at', expiresAt);
    }

    var imagen =
      document.getElementById('inpImagen').files[0];

    if (imagen) {
      data.append('imagen', imagen);
    }

    var resp;

    if (isEdit && promoId > 0) {

      resp = await ApiClient.put(
        '/admin/promotions/' + promoId,
        data
      );

    } else {

      resp = await ApiClient.post(
        '/admin/promotions',
        data
      );

    }

    if (resp.success) {

      ApiClient.flash(
        'success',
        resp.message || 'Promoción guardada correctamente.'
      );

      window.location.href =
        '<?= BASE_URL ?>restaurante/promociones/index';

      return;
    }

    btn.disabled = false;

    btn.textContent =
      isEdit ? 'Guardar cambios' : 'Crear promoción';

    var html = '';

    if (resp.errors) {

      for (var campo in resp.errors) {

        var msgs = resp.errors[campo];

        html +=
          '<div class="api-error"><strong>' +
          esc(campo) +
          ':</strong> ' +
          msgs.join(', ') +
          '</div>';

      }

    } else {

      html =
        '<div class="api-error">' +
        esc(resp.message || 'Error al guardar') +
        '</div>';

    }

    errorsContainer.innerHTML = html;
    errorsContainer.style.display = 'block';
  };

  function esc(str) {

    if (typeof str !== 'string') {
      return str;
    }

    var div = document.createElement('div');

    div.appendChild(document.createTextNode(str));

    return div.innerHTML;
  }

  cargarUsuarios();

  if (!ApiClient.isLoggedIn()) {
    ApiClient.getTokenFromSession();
  }

})();
</script>

<style>
.api-error {
  color: #DC2626;
  font-size: .82rem;
  padding: 4px 0;
}
</style>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';