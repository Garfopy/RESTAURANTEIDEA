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
        <div>
          <label class="form-label">Logotipo actual</label>
          <?php if (!empty($settingsMap['app_logo'])): ?>
          <img src="<?= BASE_URL . $settingsMap['app_logo'] ?>" style="height:40px;margin-bottom:8px;display:block">
          <?php endif; ?>
          <input type="file" name="app_logo" class="form-control" accept="image/*">
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top:16px">
    <button type="submit" class="btn btn-primary">Guardar configuración</button>
  </div>
</form>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
