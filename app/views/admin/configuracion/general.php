<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
// Build settings map
$settingsMap = [];
foreach ($config as $row) { $settingsMap[$row['clave']] = $row['valor']; }
?>
<!-- Config nav tabs -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <h1 style="font-size:1.25rem;font-weight:700;margin:0">Configuración</h1>
</div>
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
  <?php foreach (['general'=>'General','apis'=>'APIs e Integraciones','dispositivos'=>'Dispositivos IoT','bitacora'=>'Bitácora'] as $slug=>$label): ?>
  <a href="<?= BASE_URL ?>config/<?= $slug ?>" class="btn btn-sm <?= $slug==='general'?'btn-primary':'btn-secondary' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if (!empty($flash)): ?>
<div class="toast success" style="margin-bottom:16px;position:relative;max-width:100%"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>config/guardar" enctype="multipart/form-data">
  <input type="hidden" name="grupo" value="general">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div class="card">
      <div class="card-title" style="margin-bottom:14px">Información de la empresa</div>
      <div style="display:grid;gap:12px">
        <div>
          <label class="form-label">Nombre del sistema</label>
          <input type="text" name="app_nombre" class="form-control" value="<?= htmlspecialchars($settingsMap['app_nombre'] ?? 'CarniHub') ?>">
        </div>
        <div>
          <label class="form-label">Correo principal</label>
          <input type="email" name="app_email" class="form-control" value="<?= htmlspecialchars($settingsMap['app_email'] ?? '') ?>">
        </div>
        <div>
          <label class="form-label">Teléfono</label>
          <input type="text" name="app_telefono" class="form-control" value="<?= htmlspecialchars($settingsMap['app_telefono'] ?? '') ?>">
        </div>
        <div>
          <label class="form-label">Horario de atención</label>
          <input type="text" name="app_horario" class="form-control" value="<?= htmlspecialchars($settingsMap['app_horario'] ?? '') ?>">
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-title" style="margin-bottom:14px">Apariencia</div>
      <div style="display:grid;gap:12px">
        <div>
          <label class="form-label">Color primario</label>
          <div style="display:flex;align-items:center;gap:10px">
            <input type="color" name="estilo_color" value="<?= $settingsMap['estilo_color'] ?? '#C8102E' ?>" style="width:48px;height:40px;border:none;border-radius:8px;cursor:pointer;padding:2px">
            <input type="text" id="colorHex" class="form-control" style="flex:1" value="<?= $settingsMap['estilo_color'] ?? '#C8102E' ?>" oninput="document.querySelector('[name=estilo_color]').value=this.value">
          </div>
        </div>

        <!-- Logo upload widget -->
        <div>
          <label class="form-label">Logotipo del sistema</label>
          <div style="display:flex;align-items:center;gap:14px;padding:12px;border:1px solid #E5E7EB;border-radius:10px;background:#F9FAFB">
            <!-- Preview -->
            <div id="logoPreview" style="width:80px;height:50px;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid #E5E7EB;border-radius:8px;overflow:hidden;flex-shrink:0">
              <?php if (!empty($settingsMap['app_logo'])): ?>
              <img id="logoImg" src="<?= BASE_URL . htmlspecialchars($settingsMap['app_logo']) ?>" style="max-width:100%;max-height:100%;object-fit:contain" alt="Logo">
              <?php else: ?>
              <span id="logoPlaceholder" style="font-size:.7rem;color:#9CA3AF;text-align:center;padding:4px">Sin logo</span>
              <?php endif; ?>
            </div>
            <div style="flex:1">
              <input type="file" id="logoInput" name="app_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" style="display:none" onchange="previewLogo(this)">
              <p style="font-size:.75rem;color:#6B7280;margin:0 0 8px">PNG, JPG, WebP o SVG · máx. 2 MB</p>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button type="button" onclick="document.getElementById('logoInput').click()" class="btn btn-secondary btn-sm">
                  Seleccionar logo
                </button>
                <?php if (!empty($settingsMap['app_logo'])): ?>
                <button type="button" onclick="borrarLogo()" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:1px solid #FECACA">
                  Quitar logo
                </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <input type="hidden" name="app_logo_borrar" id="appLogoBorrar" value="0">
        </div>

      </div>
    </div>
  </div>

  <div style="margin-top:16px">
    <button type="submit" class="btn btn-primary">Guardar configuración</button>
  </div>
</form>

<script>
document.querySelector('[name=estilo_color]').addEventListener('input', function(){
  document.getElementById('colorHex').value = this.value;
});
document.getElementById('colorHex').addEventListener('input', function(){
  document.querySelector('[name=estilo_color]').value = this.value;
});

function previewLogo(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    const preview = document.getElementById('logoPreview');
    preview.innerHTML = '<img id="logoImg" src="' + e.target.result + '" style="max-width:100%;max-height:100%;object-fit:contain" alt="Logo">';
  };
  reader.readAsDataURL(input.files[0]);
  document.getElementById('appLogoBorrar').value = '0';
}

function borrarLogo() {
  document.getElementById('appLogoBorrar').value = '1';
  document.getElementById('logoInput').value = '';
  const preview = document.getElementById('logoPreview');
  preview.innerHTML = '<span style="font-size:.7rem;color:#9CA3AF;text-align:center;padding:4px">Sin logo</span>';
}
</script>
<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
