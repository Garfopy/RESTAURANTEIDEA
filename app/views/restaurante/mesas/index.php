<?php ob_start(); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div style="font-size:.85rem;color:#6B7280">
    <?= count($mesas) ?> mesa<?= count($mesas) !== 1 ? 's' : '' ?> registrada<?= count($mesas) !== 1 ? 's' : '' ?>
  </div>
  <div style="display:flex;gap:10px">
    <button onclick="rstModal('modalZona')"
      class="btn btn-outline btn-sm">
      + Zona
    </button>
    <button onclick="rstModal('modalMesa')"
      class="btn btn-primary btn-sm">
      + Mesa / Silla
    </button>
  </div>
</div>

<!-- Tabla mesas -->
<div class="rst-table-wrap">
  <table class="rst-table">
    <thead>
      <tr>
        <th>Mesa / Silla</th>
        <th>Zona</th>
        <th style="text-align:center">Cap.</th>
        <th>Estado</th>
        <th>QR de mesa</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($mesas as $m): ?>
      <?php
        $estadoMap = [
          'disponible' => ['badge-green',  'Disponible'],
          'ocupada'    => ['badge-red',    'Ocupada'],
          'reservada'  => ['badge-blue',   'Reservada'],
          'pagando'    => ['badge-amber',  'Pagando'],
        ];
        [$badgeCls, $badgeTxt] = $estadoMap[$m['estado']] ?? ['badge-gray', $m['estado']];
      ?>
      <tr>
        <td style="font-weight:600"><?= htmlspecialchars($m['nombre']) ?></td>
        <td style="color:#6B7280"><?= htmlspecialchars($m['zona_nombre'] ?? '—') ?></td>
        <td style="text-align:center"><?= (int)$m['capacidad'] ?></td>
        <td><span class="badge <?= $badgeCls ?>"><?= $badgeTxt ?></span></td>
        <td>
          <div id="qr-<?= $m['id'] ?>" style="line-height:0"></div>
          <div style="font-size:.65rem;color:#9CA3AF;margin-top:2px;font-family:monospace">
            <?= htmlspecialchars(substr($m['qr_codigo'], 0, 18)) ?>…
          </div>
        </td>
        <td>
          <button onclick='editMesa(<?= htmlspecialchars(json_encode($m), ENT_QUOTES) ?>)'
                  class="btn btn-outline btn-sm">Editar</button>
          <a href="<?= BASE_URL ?>rest-mesa/eliminar/<?= $m['id'] ?>"
             onclick="return confirm('¿Desactivar esta mesa?')"
             class="btn btn-danger btn-sm" style="margin-left:6px">Quitar</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($mesas)): ?>
      <tr>
        <td colspan="6">
          <div class="empty-state">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <div style="font-size:.95rem;font-weight:600;color:#374151;margin-bottom:4px">Sin mesas registradas</div>
            <div style="font-size:.85rem">Crea la primera mesa o silla de tu restaurante</div>
          </div>
        </td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal nueva/editar mesa -->
<div id="modalMesa" class="rst-modal-backdrop">
  <div class="rst-modal rst-modal-sm">
    <div class="rst-modal-header">
      <div class="rst-modal-title" id="modalMesaTitle">Nueva Mesa / Silla</div>
      <button class="rst-modal-close" onclick="rstModal('modalMesa')">✕</button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>rest-mesa/guardar">
      <input type="hidden" name="id" id="mesaId" value="">
      <div class="form-group">
        <label class="form-label">Nombre *</label>
        <input type="text" name="nombre" id="mesaNombre" class="form-input"
               placeholder="Ej: Mesa 1, Terraza A, Barra 3" required>
      </div>
      <div class="form-group">
        <label class="form-label">Zona <span style="color:#9CA3AF;font-weight:400">(opcional)</span></label>
        <select name="zona_id" class="form-select" id="mesaZona">
          <option value="">Sin zona</option>
          <?php foreach ($zonas as $z): ?>
          <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Capacidad de personas</label>
        <input type="number" name="capacidad" id="mesaCapacidad" class="form-input"
               value="4" min="1" max="50">
      </div>
      <div class="rst-modal-footer">
        <button type="button" onclick="rstModal('modalMesa')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal nueva zona -->
<div id="modalZona" class="rst-modal-backdrop">
  <div class="rst-modal rst-modal-sm">
    <div class="rst-modal-header">
      <div class="rst-modal-title">Nueva Zona</div>
      <button class="rst-modal-close" onclick="rstModal('modalZona')">✕</button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>rest-mesa/guardarZona">
      <div class="form-group">
        <label class="form-label">Nombre de la zona *</label>
        <input type="text" name="nombre" class="form-input"
               placeholder="Ej: Terraza, Interior, Barra" required>
      </div>
      <div class="rst-modal-footer">
        <button type="button" onclick="rstModal('modalZona')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Crear Zona</button>
      </div>
    </form>
  </div>
</div>

<script src="https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// Generar QR por mesa
<?php foreach ($mesas as $m): ?>
new QRCode(document.getElementById('qr-<?= $m['id'] ?>'), {
  text: '<?= addslashes(BASE_URL . 'menu/' . ($rest['slug'] ?? '') . '?mesa=' . $m['qr_codigo']) ?>',
  width: 52, height: 52, colorDark: '#111827'
});
<?php endforeach; ?>

function rstModal(id) {
  const el = document.getElementById(id);
  el.classList.toggle('open');
}
// Cerrar con backdrop
document.querySelectorAll('.rst-modal-backdrop').forEach(bd => {
  bd.addEventListener('click', e => { if (e.target === bd) bd.classList.remove('open'); });
});

function editMesa(m) {
  document.getElementById('mesaId').value       = m.id;
  document.getElementById('mesaNombre').value   = m.nombre;
  document.getElementById('mesaCapacidad').value= m.capacidad;
  const zSel = document.getElementById('mesaZona');
  for (let o of zSel.options) o.selected = (o.value == (m.zona_id || ''));
  document.getElementById('modalMesaTitle').textContent = 'Editar Mesa';
  document.getElementById('modalMesa').classList.add('open');
}
</script>
<?php
$content = ob_get_clean();
$activeMenu = 'rest_mesas';
$pageTitle  = 'Mesas';
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
