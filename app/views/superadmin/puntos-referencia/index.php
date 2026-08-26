<?php ob_start(); ?>

<style>
.sa-header { align-items: center; display: flex; justify-content: space-between; margin-bottom: 18px; }
.sa-title { color: #0F172A; font-size: 1.45rem; font-weight: 800; margin: 0; }
.sa-copy { color: #64748B; font-size: .9rem; margin: 4px 0 0; }
.sa-form-card {
  background: #fff; border: 1px solid #E2E8F0; border-radius: 12px;
  margin-bottom: 18px; padding: 18px;
}
.sa-form-grid { display: grid; gap: 12px; grid-template-columns: repeat(5, minmax(0, 1fr)); }
.sa-form-grid label { color: #475569; display: block; font-size: .78rem; font-weight: 600; margin-bottom: 4px; }
.sa-form-grid input {
  border: 1px solid #E2E8F0; border-radius: 8px; font-size: .85rem; padding: 8px 10px; width: 100%;
}
.sa-btn {
  border: 1px solid #E2E8F0; border-radius: 8px; cursor: pointer; font-size: .78rem;
  font-weight: 600; padding: 6px 12px;
}
.sa-btn-primary { background: #1E293B; border-color: #1E293B; color: #fff; }
.sa-btn-toggle { background: #fff; color: #334155; }
.sa-badge {
  border-radius: 999px; display: inline-block; font-size: .72rem; font-weight: 700;
  padding: 3px 10px; text-transform: uppercase;
}
.sa-badge-on  { background: #D1FAE5; color: #065F46; }
.sa-badge-off { background: #E5E7EB; color: #374151; }
</style>

<div class="sa-header">
  <div>
    <h1 class="sa-title">Puntos de referencia</h1>
    <p class="sa-copy">Universidades, plazas u otras zonas contra las que se mide "cerca de mí" en la app móvil.</p>
  </div>
</div>

<div class="sa-form-card">
  <form method="post" action="<?= BASE_URL ?>superadmin/puntoReferenciaGuardar">
    <div class="sa-form-grid">
      <div>
        <label>Nombre</label>
        <input type="text" name="nombre" placeholder="Universidad Tecnológica de Querétaro" required>
      </div>
      <div>
        <label>Ciudad</label>
        <input type="text" name="ciudad" placeholder="Querétaro">
      </div>
      <div>
        <label>Latitud</label>
        <input type="text" name="lat" placeholder="20.588793" required>
      </div>
      <div>
        <label>Longitud</label>
        <input type="text" name="lng" placeholder="-100.389888" required>
      </div>
      <div>
        <label>Radio de cobertura (km)</label>
        <input type="text" name="radio_km" value="2.00">
      </div>
    </div>
    <div style="margin-top:12px">
      <button type="submit" class="sa-btn sa-btn-primary">Agregar punto de referencia</button>
    </div>
  </form>
</div>

<div class="rst-card">
  <div class="rst-table-wrap">
    <table class="rst-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Ciudad</th>
          <th>Radio</th>
          <th>Estado</th>
          <th>Negocios asociados</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($puntos as $pt): ?>
        <tr>
          <td><strong><?= htmlspecialchars($pt['nombre']) ?></strong></td>
          <td><?= htmlspecialchars($pt['ciudad'] ?? '—') ?></td>
          <td><?= htmlspecialchars($pt['radio_km']) ?> km</td>
          <td>
            <span class="sa-badge <?= $pt['activo'] ? 'sa-badge-on' : 'sa-badge-off' ?>">
              <?= $pt['activo'] ? 'Activo' : 'Inactivo' ?>
            </span>
          </td>
          <td><?= (int)$pt['negocios_asociados'] ?></td>
          <td>
            <form method="post" action="<?= BASE_URL ?>superadmin/puntoReferenciaToggle/<?= (int)$pt['id'] ?>" style="display:inline">
              <button type="submit" class="sa-btn sa-btn-toggle"><?= $pt['activo'] ? 'Desactivar' : 'Reactivar' ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($puntos)): ?>
        <tr><td colspan="6" style="text-align:center;color:#94A3B8;padding:24px">Todavía no hay puntos de referencia registrados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/superadmin/layouts/main.php';
