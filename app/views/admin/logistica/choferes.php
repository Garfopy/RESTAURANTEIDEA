<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h1 style="font-size:1.25rem;font-weight:800;margin:0">Choferes</h1>
  <button onclick="document.getElementById('modalChofer').classList.add('active')" class="btn btn-primary">+ Nuevo chofer</button>
</div>

<div class="card" style="padding:0;overflow-x:auto">
  <table class="table">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Vehículo</th>
        <th>Licencia</th>
        <th style="text-align:center">Calificación</th>
        <th>Estado</th>
        <th style="text-align:right">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($choferes as $c): ?>
      <tr>
        <td>
          <div style="font-weight:600"><?= htmlspecialchars($c['nombre']) ?></div>
          <div style="font-size:.75rem;color:#6B7280"><?= htmlspecialchars($c['email']) ?></div>
        </td>
        <td><?= htmlspecialchars($c['placa'] ?? '—') ?> <?= !empty($c['modelo']) ? '· ' . htmlspecialchars($c['marca'] . ' ' . $c['modelo']) : '' ?></td>
        <td><?= htmlspecialchars($c['licencia'] ?? '—') ?></td>
        <td style="text-align:center">
          <span style="font-weight:700;color:<?= $c['calificacion'] >= 4.5 ? '#10B981' : ($c['calificacion'] >= 3.5 ? '#F59E0B' : '#EF4444') ?>">
            ★ <?= number_format($c['calificacion'],1) ?>
          </span>
        </td>
        <td>
          <?php if ($c['activo']): ?>
          <span class="badge badge-success">Activo</span>
          <?php else: ?>
          <span class="badge badge-danger">Inactivo</span>
          <?php endif; ?>
        </td>
        <td style="text-align:right">
          <button onclick="editarChofer(<?= $c['id'] ?>)" class="btn btn-sm btn-secondary">Editar</button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($choferes)): ?>
      <tr><td colspan="6" style="text-align:center;color:#9CA3AF;padding:32px">No hay choferes registrados</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal chofer -->
<div id="modalChofer" class="modal-overlay">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title" id="modalChoferTitle">Nuevo chofer</div>
      <button class="modal-close" onclick="document.getElementById('modalChofer').classList.remove('active')">×</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="<?= BASE_URL ?>logistica/guardarChofer" id="formChofer">
        <input type="hidden" name="id" id="choferId" value="">
        <div style="display:flex;flex-direction:column;gap:12px">
          <div>
            <label class="form-label">Nombre completo *</label>
            <input type="text" name="nombre" id="choferNombre" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Email *</label>
            <input type="email" name="email" id="choferEmail" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Contraseña <small style="color:#9CA3AF">(dejar vacío para no cambiar)</small></label>
            <input type="password" name="password" class="form-control" minlength="6">
          </div>
          <div>
            <label class="form-label">Vehículo asignado</label>
            <select name="vehiculo_id" id="choferVehiculo" class="form-control form-select">
              <option value="">Sin vehículo</option>
              <?php foreach ($vehiculos as $v): ?>
              <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['placa'] . ' — ' . $v['marca'] . ' ' . $v['modelo']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Nº de licencia</label>
            <input type="text" name="licencia" id="choferLicencia" class="form-control">
          </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalChofer').classList.remove('active')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editarChofer(id) {
  fetch('<?= BASE_URL ?>logistica/getChofer/' + id)
    .then(r => r.json())
    .then(d => {
      if (!d.ok) return;
      document.getElementById('choferId').value      = d.chofer.id;
      document.getElementById('choferNombre').value  = d.chofer.nombre;
      document.getElementById('choferEmail').value   = d.chofer.email;
      document.getElementById('choferLicencia').value= d.chofer.licencia || '';
      document.getElementById('choferVehiculo').value= d.chofer.vehiculo_id || '';
      document.getElementById('modalChoferTitle').textContent = 'Editar chofer';
      document.getElementById('modalChofer').classList.add('active');
    });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
