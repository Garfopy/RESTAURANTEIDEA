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
      <div style="font-size:.78rem;color:#6B7280">Configura el descuento y asigna comensales</div>
    </div>
  </div>

  <?php if (!empty($formErrors)): ?>
  <div style="background:#FEF2F2;border:1.5px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:20px">
    <?php foreach ($formErrors as $err): ?>
    <div style="color:#DC2626;font-size:.82rem">• <?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="<?= BASE_URL ?>rest-promocion/guardar">
    <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= (int)($promo['id'] ?? 0) ?>">
    <?php endif; ?>

    <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:20px 24px;margin-bottom:20px">
      <!-- Título -->
      <div class="form-group">
        <label class="form-label">Título de la promoción *</label>
        <input type="text" name="titulo" class="form-input"
               value="<?= $titulo ?>"
               placeholder="Ej: 15% off para clientes VIP"
               required maxlength="200">
      </div>

      <!-- Descripción -->
      <div class="form-group">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-textarea" rows="2"
                  placeholder="Describe los detalles de la promoción..."><?= $descripcion ?></textarea>
      </div>

      <!-- Tipo + Valor -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Tipo de descuento</label>
          <select name="tipo" class="form-input" id="tipoDescuento" onchange="tipoChange()">
            <option value="porcentaje"   <?= $tipo === 'porcentaje'   ? 'selected' : '' ?>>Porcentaje (%)</option>
            <option value="monto_fijo"   <?= $tipo === 'monto_fijo'   ? 'selected' : '' ?>>Monto fijo ($)</option>
            <option value="envio_gratis" <?= $tipo === 'envio_gratis' ? 'selected' : '' ?>>Envío gratis</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0" id="wrapValor">
          <label class="form-label" id="lblValor">
            <?= $tipo === 'monto_fijo' ? 'Monto ($)' : 'Porcentaje (%)' ?>
          </label>
          <input type="number" name="valor_descuento" class="form-input"
                 value="<?= htmlspecialchars((string)$valor) ?>"
                 min="0" step="0.01"
                 id="inpValor">
        </div>
      </div>

      <script>
      function tipoChange() {
        var t = document.getElementById('tipoDescuento').value;
        var w = document.getElementById('wrapValor');
        var l = document.getElementById('lblValor');
        var i = document.getElementById('inpValor');
        if (t === 'envio_gratis') {
          w.style.display = 'none';
          i.value = '0';
        } else {
          w.style.display = '';
          l.textContent = t === 'monto_fijo' ? 'Monto ($)' : 'Porcentaje (%)';
          i.placeholder = t === 'monto_fijo' ? '0.00' : '0';
        }
      }
      // Ajuste inicial
      tipoChange();
      </script>

      <!-- Fechas -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Fecha de inicio *</label>
          <input type="date" name="fecha_inicio" class="form-input"
                 value="<?= htmlspecialchars($fInicio) ?>" required>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Fecha de fin *</label>
          <input type="date" name="fecha_fin" class="form-input"
                 value="<?= htmlspecialchars($fFin) ?>" required>
        </div>
      </div>

      <!-- Checkbox activo -->
      <div style="margin-top:16px">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
          <input type="checkbox" name="activo" value="1"
                 <?= $activo ? 'checked' : '' ?>
                 style="width:18px;height:18px;accent-color:var(--cp)">
          <span style="font-weight:500;font-size:.88rem;color:#374151">Promoción activa</span>
        </label>
        <div style="font-size:.75rem;color:#9CA3AF;margin-top:4px;margin-left:28px">
          Desmarca para pausar sin eliminar. La promoción no se mostrará en la app.
        </div>
      </div>
    </div>

    <!-- Sección: Comensales -->
    <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:20px 24px;margin-bottom:20px">
      <div style="font-weight:700;font-size:.95rem;color:#111827;margin-bottom:6px;
                  display:flex;align-items:center;gap:8px">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Comensales asignados
      </div>
      <div style="font-size:.8rem;color:#6B7280;margin-bottom:14px">
        Selecciona los comensales que recibirán esta promoción en la app móvil. Solo ellos la verán disponible.
      </div>

      <?php if (empty($comList)): ?>
      <div style="background:#FFF7ED;border:1.5px solid #FED7AA;border-radius:10px;padding:14px 16px;text-align:center">
        <div style="font-size:.82rem;color:#92400E;margin-bottom:4px">
          Aún no tienes comensales registrados.
        </div>
        <div style="font-size:.76rem;color:#9CA3AF">
          Los comensales aparecerán aquí automáticamente cuando escaneen el QR del menú e ingresen sus datos.
        </div>
      </div>
      <?php else: ?>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <span style="font-size:.78rem;color:#6B7280">
          <span id="contadorSel"><?= count($selected) ?></span> seleccionado(s)
        </span>
        <button type="button" onclick="toggleTodos()"
                style="font-size:.76rem;color:var(--cp);background:none;border:1px solid var(--cp);border-radius:6px;
                       padding:3px 10px;cursor:pointer;font-weight:500">
          <span id="btnToggleLabel">Seleccionar todos</span>
        </button>
      </div>

      <div style="max-height:280px;overflow-y:auto;border:1.5px solid #E5E7EB;border-radius:10px;
                  background:#F9FAFB;padding:6px 0">
        <?php foreach ($comList as $c):
          $cid   = (int)$c['id'];
          $check = in_array($cid, $selected);
        ?>
        <label style="display:flex;align-items:center;gap:10px;padding:8px 14px;cursor:pointer;
                      font-size:.84rem;transition:background .1s"
               onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background=''">
          <input type="checkbox" name="comensales[]" value="<?= $cid ?>"
                 class="chkComensal"
                 <?= $check ? 'checked' : '' ?>
                 style="width:16px;height:16px;accent-color:var(--cp);flex-shrink:0">
          <div style="flex:1;min-width:0">
            <div style="font-weight:500;color:#111827"><?= htmlspecialchars($c['nombre'] ?? 'Sin nombre') ?></div>
            <div style="font-size:.73rem;color:#9CA3AF;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= htmlspecialchars($c['email'] ?? $c['telefono'] ?? '') ?>
            </div>
          </div>
          <?php if ($check): ?>
          <span style="font-size:.7rem;color:var(--cp);font-weight:600">✓ Asignado</span>
          <?php endif; ?>
        </label>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Botones -->
    <div style="display:flex;justify-content:flex-end;gap:10px">
      <a href="<?= BASE_URL ?>rest-promocion/index"
         style="padding:10px 20px;border:1.5px solid #D1D5DB;border-radius:8px;background:#fff;
                color:#374151;font-size:.85rem;font-weight:600;text-decoration:none;text-align:center">
        Cancelar
      </a>
      <button type="submit" class="btn btn-primary">
        <?= $isEdit ? 'Guardar cambios' : 'Crear promoción' ?>
      </button>
    </div>
  </form>
</div>

<script>
// Contador de seleccionados
document.querySelectorAll('.chkComensal').forEach(function(chk) {
  chk.addEventListener('change', actualizarContador);
});
function actualizarContador() {
  var total = document.querySelectorAll('.chkComensal:checked').length;
  document.getElementById('contadorSel').textContent = total;
}
function toggleTodos() {
  var chks = document.querySelectorAll('.chkComensal');
  var todos = document.querySelectorAll('.chkComensal:checked').length < chks.length;
  chks.forEach(function(c) { c.checked = todos; });
  actualizarContador();
  document.getElementById('btnToggleLabel').textContent = todos ? 'Deseleccionar todos' : 'Seleccionar todos';
}
// Inicializar label del botón
(function(){
  var chks = document.querySelectorAll('.chkComensal');
  var todos = document.querySelectorAll('.chkComensal:checked').length === chks.length && chks.length > 0;
  document.getElementById('btnToggleLabel').textContent = todos ? 'Deseleccionar todos' : 'Seleccionar todos';
})();
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';