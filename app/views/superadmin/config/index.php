<?php ob_start(); ?>

<style>
.sa-header { margin-bottom: 18px; }
.sa-title { color: #0F172A; font-size: 1.45rem; font-weight: 800; margin: 0; }
.sa-copy { color: #64748B; font-size: .9rem; margin: 4px 0 0; }
.sa-warn {
  background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 10px; color: #92400E;
  font-size: .84rem; margin-bottom: 18px; padding: 12px 14px;
}
.sa-panel { background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 16px; padding: 18px; }
.sa-panel h2 {
  color: #0F172A; font-size: .82rem; font-weight: 800; letter-spacing: .04em;
  margin: 0 0 14px; text-transform: uppercase;
}
.sa-field { border-top: 1px solid #F1F5F9; display: grid; gap: 12px; grid-template-columns: 260px 1fr; padding: 12px 0; }
.sa-field:first-of-type { border-top: none; padding-top: 0; }
.sa-field-label { color: #334155; font-size: .85rem; font-weight: 600; }
.sa-field-key { color: #94A3B8; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .74rem; }
.sa-field input[type=text] {
  border: 1px solid #E2E8F0; border-radius: 8px; font-size: .85rem; padding: 8px 10px; width: 100%;
}
.sa-field input[type=color] {
  background: #fff; border: 1px solid #E2E8F0; border-radius: 8px; cursor: pointer;
  height: 38px; padding: 3px; width: 64px;
}
.sa-color-row { align-items: center; display: flex; gap: 10px; }
.sa-color-row input[type=text] { max-width: 130px; }
.sa-hint { color: #94A3B8; font-size: .74rem; margin-top: 4px; }
.sa-btn { border: 1px solid #E2E8F0; border-radius: 8px; cursor: pointer; font-size: .82rem; font-weight: 600; padding: 8px 16px; }
.sa-btn-primary { background: #1E293B; border-color: #1E293B; color: #fff; }
.sa-actions { position: sticky; bottom: 0; background: linear-gradient(transparent, #F8FAFC 40%); padding: 14px 0; }
</style>

<div class="sa-header">
  <h1 class="sa-title">Configuración global</h1>
  <p class="sa-copy">Parámetros que aplican a toda la plataforma (tabla <code>global_settings</code>).</p>
</div>

<div class="sa-warn">
  <strong>Ojo:</strong> estos valores los consume también la app móvil. Los campos con formato
  JSON o de color se validan al guardar — si un valor no pasa la validación, ese ajuste se
  omite y el resto sí se guarda.
</div>

<form method="post" action="<?= BASE_URL ?>superadmin/configGuardar">
  <?php foreach ($ajustes as $grupo => $filas): ?>
  <div class="sa-panel">
    <h2><?= htmlspecialchars($grupo) ?></h2>
    <?php foreach ($filas as $fila): ?>
    <?php
      $clave  = $fila['clave'];
      $valor  = (string)($fila['valor'] ?? '');
      $tipo   = (string)($fila['tipo'] ?? 'text');
      $label  = $fila['etiqueta'] ?: ucfirst(str_replace('_', ' ', $clave));
      $esJson = $valor !== '' && json_decode($valor, true) !== null;
    ?>
    <div class="sa-field">
      <div>
        <div class="sa-field-label"><?= htmlspecialchars($label) ?></div>
        <div class="sa-field-key"><?= htmlspecialchars($clave) ?></div>
      </div>
      <div>
        <?php if ($tipo === 'color'): ?>
        <div class="sa-color-row">
          <input type="color" value="<?= htmlspecialchars(preg_match('/^#[0-9A-Fa-f]{6}$/', $valor) ? $valor : '#000000') ?>"
                 oninput="this.nextElementSibling.value = this.value.toUpperCase()">
          <input type="text" name="ajustes[<?= htmlspecialchars($clave) ?>]"
                 value="<?= htmlspecialchars($valor) ?>"
                 oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)) this.previousElementSibling.value = this.value">
        </div>
        <div class="sa-hint">Formato #RRGGBB</div>
        <?php elseif ($tipo === 'password'): ?>
        <input type="text" name="ajustes[<?= htmlspecialchars($clave) ?>]"
               value="<?= htmlspecialchars($valor) ?>" autocomplete="off">
        <div class="sa-hint">Valor sensible — visible solo para Superadmin</div>
        <?php else: ?>
        <input type="text" name="ajustes[<?= htmlspecialchars($clave) ?>]" value="<?= htmlspecialchars($valor) ?>">
        <?php if ($esJson): ?>
        <div class="sa-hint">Debe seguir siendo JSON válido (ej. <code>["card","cash"]</code>)</div>
        <?php elseif ($tipo === 'number'): ?>
        <div class="sa-hint">Valor numérico</div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>

  <?php if (empty($ajustes)): ?>
  <div class="sa-panel">
    <p style="color:#94A3B8;margin:0">No hay ajustes registrados en <code>global_settings</code>.</p>
  </div>
  <?php else: ?>
  <div class="sa-actions">
    <button type="submit" class="sa-btn sa-btn-primary">Guardar cambios</button>
  </div>
  <?php endif; ?>
</form>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/superadmin/layouts/main.php';
