<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
$settingsMap = [];
foreach ($config as $row) { $settingsMap[$row['clave']] = $row['valor']; }
?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
  <h1 style="font-size:1.25rem;font-weight:700;margin:0">APIs e Integraciones</h1>
</div>
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
  <?php foreach (['general'=>'General','apis'=>'APIs','dispositivos'=>'Dispositivos IoT','bitacora'=>'Bitácora'] as $slug=>$label): ?>
  <a href="<?= BASE_URL ?>config/<?= $slug ?>" class="btn btn-sm <?= $slug==='apis'?'btn-primary':'btn-secondary' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if (!empty($flash)): ?>
<div class="toast success" style="margin-bottom:16px;position:relative;max-width:100%"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>config/guardar">
<input type="hidden" name="grupo" value="apis">

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

  <div class="card">
    <div class="card-title" style="margin-bottom:14px">💳 PayPal</div>
    <div style="display:grid;gap:10px">
      <div><label class="form-label">Client ID</label>
        <input type="text" name="api_paypal_key" class="form-control" placeholder="AXXX..." value="<?= htmlspecialchars($settingsMap['api_paypal_key'] ?? '') ?>"></div>
      <div><label class="form-label">Client Secret</label>
        <input type="password" name="api_paypal_secret" class="form-control" value="<?= htmlspecialchars($settingsMap['api_paypal_secret'] ?? '') ?>"></div>
    </div>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:14px">🧾 Factura-lo (CFDI)</div>
    <div style="display:grid;gap:10px">
      <div><label class="form-label">API Key</label>
        <input type="text" name="api_facturalo_key" class="form-control" placeholder="sk_..." value="<?= htmlspecialchars($settingsMap['api_facturalo_key'] ?? '') ?>"></div>
    </div>
    <p style="font-size:.75rem;color:#6B7280;margin-top:8px">Registra tu cuenta en factura-lo.mx para obtener el API Key.</p>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:14px">📍 GPS Traccar</div>
    <div style="display:grid;gap:10px">
      <div><label class="form-label">URL del servidor</label>
        <input type="url" name="api_traccar_url" class="form-control" placeholder="https://traccar.tudominio.com" value="<?= htmlspecialchars($settingsMap['api_traccar_url'] ?? '') ?>"></div>
      <div><label class="form-label">Usuario</label>
        <input type="text" name="api_traccar_user" class="form-control" value="<?= htmlspecialchars($settingsMap['api_traccar_user'] ?? '') ?>"></div>
      <div><label class="form-label">Contraseña</label>
        <input type="password" name="api_traccar_pass" class="form-control" value="<?= htmlspecialchars($settingsMap['api_traccar_pass'] ?? '') ?>"></div>
    </div>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:14px">💬 WhatsApp Business</div>
    <div style="display:grid;gap:10px">
      <div><label class="form-label">Token de acceso</label>
        <input type="text" name="api_whatsapp_token" class="form-control" placeholder="EAAx..." value="<?= htmlspecialchars($settingsMap['api_whatsapp_token'] ?? '') ?>"></div>
      <div><label class="form-label">Phone Number ID</label>
        <input type="text" name="api_whatsapp_phone" class="form-control" value="<?= htmlspecialchars($settingsMap['api_whatsapp_phone'] ?? '') ?>"></div>
    </div>
    <p style="font-size:.75rem;color:#6B7280;margin-top:8px">Obtén las credenciales en Meta for Developers.</p>
  </div>

</div>

<div style="margin-top:16px">
  <button type="submit" class="btn btn-primary">Guardar APIs</button>
</div>
</form>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
