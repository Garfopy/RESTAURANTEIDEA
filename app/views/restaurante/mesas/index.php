<?php ob_start(); ?>
<style>
/* ── QR thumbnail en tabla ─────────────────────────────────────────── */
.qr-cell { vertical-align: top; width: 130px; }
[id^="qr-"] { width: 80px; height: 80px; overflow: hidden; }
[id^="qr-"] canvas,
[id^="qr-"] img { display: block !important; width: 80px !important; height: 80px !important; }
</style>
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
        <td class="qr-cell">
          <div id="qr-<?= $m['id'] ?>"></div>
          <div style="margin-top:6px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
            <button type="button" onclick="verQR(<?= $m['id'] ?>, '<?= htmlspecialchars($m['nombre'], ENT_QUOTES) ?>')"
                    style="font-size:.7rem;color:#6B7280;background:#F3F4F6;border:none;
                           padding:3px 8px;border-radius:5px;cursor:pointer">
              🔍 Ver QR
            </button>
            <a href="<?= BASE_URL ?>menu/<?= htmlspecialchars($rest['slug'] ?? '') ?>?mesa=<?= urlencode($m['qr_codigo']) ?>"
               target="_blank"
               style="font-size:.7rem;color:#3B82F6;text-decoration:none">
              🔗 Abrir
            </a>
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

<!-- Modal QR grande -->
<div id="modalQR" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);
     z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:20px;padding:28px 24px;text-align:center;
              max-width:340px;width:92%;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <button onclick="cerrarModalQR()"
            style="position:absolute;top:12px;right:14px;border:none;background:none;
                   font-size:1.4rem;cursor:pointer;color:#9CA3AF;line-height:1">✕</button>
    <div id="qrModalNombre" style="font-weight:700;font-size:1rem;color:#111827;margin-bottom:16px"></div>
    <div id="qrGrande" style="display:flex;justify-content:center;margin-bottom:14px"></div>
    <div id="qrModalUrl"
         style="font-size:.68rem;color:#9CA3AF;word-break:break-all;margin-bottom:18px;padding:0 4px"></div>
    <button onclick="descargarQR()"
            style="padding:10px 22px;background:#111827;color:#fff;border:none;
                   border-radius:10px;font-size:.88rem;font-weight:600;cursor:pointer">
      ⬇️ Descargar PNG
    </button>
  </div>
</div>

<script src="https://unpkg.com/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// URLs por mesa
const qrUrls = {};
<?php foreach ($mesas as $m): ?>
qrUrls[<?= $m['id'] ?>] = '<?= addslashes(BASE_URL . 'menu/' . ($rest['slug'] ?? '') . '?mesa=' . $m['qr_codigo']) ?>';
<?php endforeach; ?>

// Thumbnails 80×80 en la tabla
<?php foreach ($mesas as $m): ?>
new QRCode(document.getElementById('qr-<?= $m['id'] ?>'), {
  text: qrUrls[<?= $m['id'] ?>],
  width: 80, height: 80, colorDark: '#111827'
});
<?php endforeach; ?>

// ── Modal QR grande ──────────────────────────────────────────────────────────
function verQR(mesaId, nombre) {
  document.getElementById('qrModalNombre').textContent = nombre;
  document.getElementById('qrModalUrl').textContent    = qrUrls[mesaId] ?? '';
  const c = document.getElementById('qrGrande');
  c.innerHTML = '';
  new QRCode(c, { text: qrUrls[mesaId], width: 256, height: 256, colorDark: '#111827' });
  document.getElementById('modalQR').style.display = 'flex';
}
function cerrarModalQR() {
  document.getElementById('modalQR').style.display = 'none';
  document.getElementById('qrGrande').innerHTML = '';
}
function descargarQR() {
  const canvas = document.querySelector('#qrGrande canvas');
  if (!canvas) return;
  const a = document.createElement('a');
  a.href = canvas.toDataURL('image/png');
  a.download = (document.getElementById('qrModalNombre').textContent || 'qr-mesa') + '.png';
  a.click();
}
document.getElementById('modalQR').addEventListener('click', e => {
  if (e.target.id === 'modalQR') cerrarModalQR();
});

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
