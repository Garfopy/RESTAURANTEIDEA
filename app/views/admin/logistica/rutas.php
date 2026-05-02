<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h1 style="font-size:1.25rem;font-weight:800;margin:0">Logística — Rutas de entrega</h1>
  <button onclick="document.getElementById('modalNuevaRuta').classList.add('active')" class="btn btn-primary">+ Nueva ruta</button>
</div>

<!-- Filtros fecha -->
<div style="display:flex;gap:10px;margin-bottom:16px;align-items:center;flex-wrap:wrap">
  <input type="date" id="filtroFecha" value="<?= date('Y-m-d') ?>" class="form-control" style="max-width:180px" onchange="filtrarRutas()">
  <select id="filtroEstado" class="form-control form-select" style="max-width:160px" onchange="filtrarRutas()">
    <option value="">Todos los estados</option>
    <option value="pendiente">Pendiente</option>
    <option value="en_preparacion">En preparación</option>
    <option value="en_ruta">En ruta</option>
    <option value="completada">Completada</option>
  </select>
</div>

<!-- Tabla de rutas -->
<div class="card" style="padding:0;overflow-x:auto">
  <table class="table">
    <thead>
      <tr>
        <th>Nombre de ruta</th>
        <th>Fecha</th>
        <th>Chofer</th>
        <th>Vehículo</th>
        <th style="text-align:center">Entregas</th>
        <th>Estado</th>
        <th style="text-align:right">Acciones</th>
      </tr>
    </thead>
    <tbody id="rutasBody">
      <?php foreach ($rutas as $r):
        $estadoColors = ['pendiente'=>['#FEF3C7','#92400E'],'en_preparacion'=>['#DBEAFE','#1E40AF'],'en_ruta'=>['#D1FAE5','#065F46'],'completada'=>['#F0FDF4','#166534']];
        [$bg, $tc] = $estadoColors[$r['estado']] ?? ['#F3F4F6','#374151'];
      ?>
      <tr>
        <td style="font-weight:600"><?= htmlspecialchars($r['nombre']) ?></td>
        <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
        <td><?= htmlspecialchars($r['chofer_nombre'] ?? '—') ?></td>
        <td><?= htmlspecialchars($r['vehiculo_placa'] ?? '—') ?></td>
        <td style="text-align:center"><?= $r['total_entregas'] ?></td>
        <td><span class="badge" style="background:<?= $bg ?>;color:<?= $tc ?>"><?= ucfirst(str_replace('_',' ',$r['estado'])) ?></span></td>
        <td style="text-align:right">
          <a href="<?= BASE_URL ?>logistica/detalle/<?= $r['id'] ?>" class="btn btn-sm btn-secondary">Ver</a>
          <a href="<?= BASE_URL ?>logistica/mapa/<?= $r['id'] ?>" class="btn btn-sm btn-secondary">🗺️</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($rutas)): ?>
      <tr><td colspan="7" style="text-align:center;color:#9CA3AF;padding:32px">No hay rutas para este día</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal nueva ruta -->
<div id="modalNuevaRuta" class="modal-overlay">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <div class="modal-title">Nueva ruta</div>
      <button class="modal-close" onclick="document.getElementById('modalNuevaRuta').classList.remove('active')">×</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="<?= BASE_URL ?>logistica/crearRuta">
        <div style="display:flex;flex-direction:column;gap:12px">
          <div>
            <label class="form-label">Nombre de la ruta</label>
            <input type="text" name="nombre" class="form-control" placeholder="Ej. Ruta Norte - 2024-01-15" required>
          </div>
          <div>
            <label class="form-label">Fecha</label>
            <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div>
            <label class="form-label">Chofer asignado</label>
            <select name="chofer_id" class="form-control form-select">
              <option value="">Sin asignar</option>
              <?php foreach ($choferes as $ch): ?>
              <option value="<?= $ch['id'] ?>"><?= htmlspecialchars($ch['nombre']) ?> — <?= htmlspecialchars($ch['placa'] ?? '') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Km estimados</label>
            <input type="number" name="km_estimados" class="form-control" step="0.1" placeholder="80.5">
          </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalNuevaRuta').classList.remove('active')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear ruta</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function filtrarRutas() {
  const fecha  = document.getElementById('filtroFecha').value;
  const estado = document.getElementById('filtroEstado').value;
  window.location = '<?= BASE_URL ?>logistica/rutas?fecha=' + fecha + '&estado=' + estado;
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
