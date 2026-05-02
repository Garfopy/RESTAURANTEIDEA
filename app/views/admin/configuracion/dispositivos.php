<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
  <h1 style="font-size:1.25rem;font-weight:700;margin:0">Dispositivos IoT</h1>
</div>
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
  <?php foreach (['general'=>'General','apis'=>'APIs','dispositivos'=>'Dispositivos IoT','bitacora'=>'Bitácora'] as $slug=>$label): ?>
  <a href="<?= BASE_URL ?>config/<?= $slug ?>" class="btn btn-sm <?= $slug==='dispositivos'?'btn-primary':'btn-secondary' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if (!empty($flash)): ?>
<div class="toast success" style="margin-bottom:16px;position:relative;max-width:100%"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<!-- HikVision Section -->
<div class="card" style="margin-bottom:16px">
  <div class="card-header">
    <span class="card-title">📹 Cámaras HikVision</span>
    <button onclick="showModal('modalHik')" class="btn btn-primary btn-sm">+ Agregar dispositivo</button>
  </div>

  <?php if (empty($hikvision)): ?>
  <p style="color:#9CA3AF;text-align:center;padding:24px">No hay dispositivos HikVision configurados</p>
  <?php else: ?>
  <div class="table-container">
    <table>
      <thead><tr><th>Nombre</th><th>IP</th><th>Puerto</th><th>Canal</th><th>Tipo</th><th>Ubicación</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($hikvision as $d): ?>
        <tr>
          <td style="font-weight:600"><?= htmlspecialchars($d['nombre']) ?></td>
          <td style="font-family:monospace;font-size:.8rem"><?= $d['ip'] ?></td>
          <td><?= $d['puerto'] ?></td>
          <td><?= $d['canal'] ?></td>
          <td><span class="badge badge-gray"><?= $d['tipo'] ?></span></td>
          <td style="font-size:.8rem;color:#6B7280"><?= htmlspecialchars($d['ubicacion'] ?? '') ?></td>
          <td><span class="badge <?= $d['activo']?'badge-success':'badge-gray' ?>"><?= $d['activo']?'Activo':'Inactivo' ?></span></td>
          <td>
            <div style="display:flex;gap:4px">
              <button onclick="editarHik(<?= htmlspecialchars(json_encode($d)) ?>)" class="btn btn-sm btn-secondary">Editar</button>
              <button onclick="eliminarDispositivo(<?=$d['id']?>,'hikvision')" class="btn btn-sm btn-danger">Eliminar</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Shelly Section -->
<div class="card">
  <div class="card-header">
    <span class="card-title">🔌 Dispositivos Shelly Cloud</span>
    <button onclick="showModal('modalShelly')" class="btn btn-primary btn-sm">+ Agregar dispositivo</button>
  </div>

  <?php if (empty($shelly)): ?>
  <p style="color:#9CA3AF;text-align:center;padding:24px">No hay dispositivos Shelly configurados</p>
  <?php else: ?>
  <div class="table-container">
    <table>
      <thead><tr><th>Nombre</th><th>Device ID</th><th>Tipo</th><th>Ubicación</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($shelly as $s): ?>
        <tr>
          <td style="font-weight:600"><?= htmlspecialchars($s['nombre']) ?></td>
          <td style="font-family:monospace;font-size:.8rem"><?= $s['device_id'] ?></td>
          <td><span class="badge badge-gray"><?= $s['tipo'] ?></span></td>
          <td style="font-size:.8rem"><?= htmlspecialchars($s['ubicacion'] ?? '') ?></td>
          <td>
            <span class="badge <?= $s['estado_actual']==='on'?'badge-success':($s['estado_actual']==='off'?'badge-gray':'badge-warning') ?>">
              <?= strtoupper($s['estado_actual'] ?? 'unknown') ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:4px">
              <button onclick="eliminarDispositivo(<?=$s['id']?>,'shelly')" class="btn btn-sm btn-danger">Eliminar</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Modal HikVision -->
<div class="modal-overlay" id="modalHik" style="display:none">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <span class="modal-title">Dispositivo HikVision</span>
      <button onclick="closeModal('modalHik')" style="background:none;border:none;cursor:pointer;font-size:1.2rem">×</button>
    </div>
    <div class="modal-body">
      <form id="formHik" style="display:grid;gap:12px">
        <input type="hidden" name="tipo_dispositivo" value="hikvision">
        <input type="hidden" name="id" id="hikId">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div><label class="form-label">Nombre *</label><input type="text" name="nombre" id="hikNombre" class="form-control" required></div>
          <div><label class="form-label">IP *</label><input type="text" name="ip" id="hikIp" class="form-control" required placeholder="192.168.1.10"></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
          <div><label class="form-label">Puerto</label><input type="number" name="puerto" id="hikPuerto" class="form-control" value="80"></div>
          <div><label class="form-label">Canal</label><input type="number" name="canal" id="hikCanal" class="form-control" value="1"></div>
          <div><label class="form-label">Tipo</label>
            <select name="tipo" id="hikTipo" class="form-control form-select">
              <option value="camara">Cámara</option>
              <option value="nvr">NVR</option>
              <option value="dvr">DVR</option>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div><label class="form-label">Usuario</label><input type="text" name="usuario" id="hikUsuario" class="form-control" value="admin"></div>
          <div><label class="form-label">Contraseña</label><input type="password" name="password" class="form-control"></div>
        </div>
        <div><label class="form-label">Ubicación</label><input type="text" name="ubicacion" id="hikUbicacion" class="form-control" placeholder="Almacén principal, Portón, etc."></div>
      </form>
    </div>
    <div class="modal-footer">
      <button onclick="closeModal('modalHik')" class="btn btn-secondary">Cancelar</button>
      <button onclick="guardarDispositivo('formHik')" class="btn btn-primary">Guardar</button>
    </div>
  </div>
</div>

<!-- Modal Shelly -->
<div class="modal-overlay" id="modalShelly" style="display:none">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <span class="modal-title">Dispositivo Shelly Cloud</span>
      <button onclick="closeModal('modalShelly')" style="background:none;border:none;cursor:pointer;font-size:1.2rem">×</button>
    </div>
    <div class="modal-body">
      <form id="formShelly" style="display:grid;gap:12px">
        <input type="hidden" name="tipo_dispositivo" value="shelly">
        <div><label class="form-label">Nombre *</label><input type="text" name="nombre" class="form-control" required></div>
        <div><label class="form-label">Device ID *</label><input type="text" name="device_id" class="form-control" required placeholder="shellyplug-XXXXXX"></div>
        <div><label class="form-label">Auth Key</label><input type="text" name="auth_key" class="form-control" placeholder="MTExMD..."></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div><label class="form-label">Tipo</label>
            <select name="tipo" class="form-control form-select">
              <option value="relay">Relay</option>
              <option value="sensor">Sensor</option>
              <option value="plug">Plug</option>
            </select>
          </div>
          <div><label class="form-label">Ubicación</label><input type="text" name="ubicacion" class="form-control"></div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button onclick="closeModal('modalShelly')" class="btn btn-secondary">Cancelar</button>
      <button onclick="guardarDispositivo('formShelly')" class="btn btn-primary">Guardar</button>
    </div>
  </div>
</div>

<script>
function showModal(id) { document.getElementById(id).style.display='flex'; }
function closeModal(id) { document.getElementById(id).style.display='none'; }

function editarHik(d) {
  document.getElementById('hikId').value = d.id;
  document.getElementById('hikNombre').value = d.nombre;
  document.getElementById('hikIp').value = d.ip;
  document.getElementById('hikPuerto').value = d.puerto;
  document.getElementById('hikCanal').value = d.canal;
  document.getElementById('hikTipo').value = d.tipo;
  document.getElementById('hikUsuario').value = d.usuario || 'admin';
  document.getElementById('hikUbicacion').value = d.ubicacion || '';
  showModal('modalHik');
}

function guardarDispositivo(formId) {
  const form = document.getElementById(formId);
  const data = new URLSearchParams(new FormData(form));
  fetch('<?= BASE_URL ?>config/guardarDispositivo', { method:'POST', body:data })
    .then(r=>r.json()).then(d=>{ if(d.ok) location.reload(); else alert('Error guardando'); });
}

function eliminarDispositivo(id, tipo) {
  if (!confirm('¿Eliminar este dispositivo?')) return;
  fetch('<?= BASE_URL ?>config/eliminarDispositivo/'+id, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'tipo='+tipo
  }).then(r=>r.json()).then(d=>{ if(d.ok) location.reload(); });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
