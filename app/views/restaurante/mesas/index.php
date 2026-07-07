<?php ob_start(); ?>
<style>
/* ── QR thumbnail en tabla ─────────────────────────────────────────── */
.qr-cell { vertical-align: top; width: 130px; }
[id^="qr-"] { width: 80px; height: 80px; overflow: hidden; }
[id^="qr-"] canvas,
[id^="qr-"] img { display: block !important; width: 80px !important; height: 80px !important; }
</style>
<style>
.mesa-page-head{align-items:center;background:rgba(255,255,255,.86);border:1px solid #E5E7EB;border-radius:22px;box-shadow:0 18px 55px rgba(15,23,42,.06);display:flex;justify-content:space-between;gap:18px;margin-bottom:18px;padding:18px 20px}
.mesa-page-title{color:#111827;font-size:1.35rem;font-weight:900;letter-spacing:-.03em;margin-bottom:4px}
.mesa-page-subtitle{color:#64748B;font-size:.86rem}
.mesa-actions-top{display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end}
.mesa-tabs{background:rgba(255,255,255,.76);border:1px solid #E5E7EB;border-radius:999px;display:inline-flex;gap:4px;margin-bottom:16px;padding:5px;box-shadow:0 12px 34px rgba(15,23,42,.05)}
.mesa-tab{border-radius:999px;color:#64748B;font-size:.84rem;font-weight:800;padding:9px 16px;text-decoration:none}
.mesa-tab.active{background:#111827;color:#fff}
.mesa-tab span{color:inherit;opacity:.72}
.mesa-table-wrap{border-radius:22px;box-shadow:0 18px 55px rgba(15,23,42,.07)}
.mesa-table th,.mesa-table td{text-align:center;vertical-align:middle}
.mesa-table th{color:#334155;font-size:.76rem;letter-spacing:.03em;text-transform:uppercase}
.mesa-table tbody tr{background:rgba(255,255,255,.98)}
.mesa-table tbody tr:hover{background:#F8FAFC}
.mesa-name{color:#111827;font-size:.95rem;font-weight:900}
.mesa-zone{color:#64748B;font-weight:600}
.mesa-capacity{background:#F1F5F9;border-radius:999px;color:#334155;display:inline-flex;font-size:.82rem;font-weight:900;justify-content:center;min-width:34px;padding:5px 10px}
.qr-cell{vertical-align:middle;width:150px}
.qr-mini{align-items:center;background:#fff;border:1px solid #E2E8F0;border-radius:16px;display:inline-flex;justify-content:center;padding:8px;box-shadow:0 10px 24px rgba(15,23,42,.06)}
[id^="qr-"]{width:86px!important;height:86px!important;overflow:hidden}
[id^="qr-"] canvas,[id^="qr-"] img{display:block!important;width:86px!important;height:86px!important}
.mesa-qr-links{align-items:center;display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-top:8px}
.mesa-actions{display:flex;flex-wrap:wrap;gap:8px;justify-content:center}
@media (max-width:768px){.mesa-page-head{align-items:stretch;flex-direction:column}.mesa-actions-top{justify-content:stretch}.mesa-actions-top .btn{flex:1}.mesa-tabs{border-radius:18px;overflow-x:auto;width:100%}.mesa-tab{flex:1;text-align:center;white-space:nowrap}.mesa-table-wrap{overflow-x:auto}.mesa-table{min-width:860px}}
</style>
<div class="mesa-page-head">
  <div>
    <div class="mesa-page-title">Mesas</div>
    <div class="mesa-page-subtitle">
      <?= count($mesas) ?> mesa<?= count($mesas) !== 1 ? 's' : '' ?> configurada<?= count($mesas) !== 1 ? 's' : '' ?> para operar con QR.
    </div>
  </div>
  <div class="mesa-actions-top">
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

<!-- Tabs filtro estado -->
<div class="mesa-tabs">
  <?php
    $tabs = [
      'activas'   => ['Activas',   (int)($countActivas ?? 0)],
      'inactivas' => ['Inactivas', (int)($countInactivas ?? 0)],
      'todas'     => ['Todas',     (int)(($countActivas ?? 0) + ($countInactivas ?? 0))],
    ];
    $filtroActual = $filtro ?? 'activas';
  ?>
  <?php foreach ($tabs as $key => [$label, $cnt]): ?>
    <a href="<?= BASE_URL ?>rest-mesa/index?estado=<?= $key ?>"
       class="mesa-tab <?= $filtroActual === $key ? 'active' : '' ?>">
      <?= $label ?> <span>(<?= $cnt ?>)</span>
    </a>
  <?php endforeach; ?>
</div>

<!-- Tabla mesas -->
<div class="rst-table-wrap mesa-table-wrap">
  <table class="rst-table mesa-table">
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
        $esInactiva = (int)($m['activo'] ?? 1) === 0;
        if ($esInactiva) {
          $badgeCls = 'badge-gray'; $badgeTxt = 'Inactiva';
        } elseif ($m['estado'] === 'reservada') {
          // Verificar si la reservación ya comenzó (llegó) o está completada
          $resInfo = $reservasTiempoHoy[$m['id']] ?? null;
          if ($resInfo) {
            $resTs = strtotime($resInfo['fecha'] . ' ' . $resInfo['hora']);
            $now   = time();
            if ($now >= $resTs && $now <= $resTs + 3 * 3600) {
              // Dentro de la ventana de 3h → llegó
              $badgeCls = ''; $badgeTxt = '<span style="padding:2px 8px;border-radius:99px;font-size:.78rem;font-weight:600;background:#DBEAFE;color:#1D4ED8">llegó 📍</span>';
            } elseif ($now > $resTs + 3 * 3600) {
              // Pasó la ventana → completada
              $badgeCls = ''; $badgeTxt = '<span style="padding:2px 8px;border-radius:99px;font-size:.78rem;font-weight:600;background:#F3F4F6;color:#374151">completada</span>';
            } else {
              [$badgeCls, $badgeTxt] = $estadoMap['reservada'];
            }
          } else {
            [$badgeCls, $badgeTxt] = $estadoMap['reservada'];
          }
        } else {
          [$badgeCls, $badgeTxt] = $estadoMap[$m['estado']] ?? ['badge-gray', $m['estado']];
        }
        $rowStyle = $esInactiva ? 'opacity:.55;background:#F9FAFB' : '';
      ?>
      <tr style="<?= $rowStyle ?>">
        <td><span class="mesa-name"><?= htmlspecialchars($m['nombre']) ?></span></td>
        <td style="color:#6B7280"><?= htmlspecialchars($m['zona_nombre'] ?? '—') ?></td>
        <td><span class="mesa-capacity"><?= (int)$m['capacidad'] ?></span></td>
        <td data-estado-mesa="<?= (int)$m['id'] ?>"><?= $badgeCls ? "<span class=\"badge $badgeCls\">$badgeTxt</span>" : $badgeTxt ?></td>
        <td class="qr-cell">
          <div class="qr-mini"><div id="qr-<?= $m['id'] ?>"></div></div>
          <div class="mesa-qr-links">
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
          <div class="mesa-actions">
            <button onclick='editMesa(<?= htmlspecialchars(json_encode($m), ENT_QUOTES) ?>)'
                    class="btn btn-outline btn-sm">Editar</button>
            <?php if ($esInactiva): ?>
              <a href="<?= BASE_URL ?>rest-mesa/activar/<?= $m['id'] ?>"
                 class="btn btn-success btn-sm">Activar</a>
              <a href="<?= BASE_URL ?>rest-mesa/borrar/<?= $m['id'] ?>"
                 onclick="return confirm('⚠️ Eliminar PERMANENTEMENTE esta mesa? Esta acción no se puede deshacer.')"
                 class="btn btn-danger btn-sm">Eliminar</a>
            <?php else: ?>
              <?php $estadoMesa = strtolower((string)($m['estado'] ?? 'disponible')); $bloqueada = in_array($estadoMesa, ['ocupada','pagando','reservada'], true); ?>
              <?php if ($bloqueada): ?>
                <button type="button" disabled
                        title="No se puede desactivar: mesa <?= htmlspecialchars($estadoMesa) ?>"
                        class="btn btn-danger btn-sm" style="opacity:.45;cursor:not-allowed">Desactivar</button>
              <?php else: ?>
                <a href="<?= BASE_URL ?>rest-mesa/eliminar/<?= $m['id'] ?>"
                   onclick="return confirm('¿Desactivar esta mesa?')"
                   class="btn btn-danger btn-sm">Desactivar</a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
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
<div id="modalQR" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
     z-index:9999;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:20px;text-align:center;
              max-width:340px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.3);overflow:hidden">
    <!-- Header -->
    <div style="padding:14px 18px;border-bottom:1px solid #F3F4F6;
                display:flex;align-items:center;justify-content:space-between">
      <span id="qrModalNombre" style="font-weight:700;font-size:.95rem;color:#111827"></span>
      <button onclick="cerrarModalQR()"
              style="border:none;background:#F3F4F6;width:28px;height:28px;border-radius:50%;
                     font-size:.85rem;cursor:pointer;color:#6B7280;line-height:28px">✕</button>
    </div>
    <!-- QR -->
    <div style="padding:20px;background:#F9FAFB">
      <div id="qrGrande" style="display:inline-flex;justify-content:center;align-items:center;
                                 background:#fff;border-radius:12px;padding:12px;
                                 box-shadow:0 2px 10px rgba(0,0,0,.1)"></div>
    </div>
    <!-- URL -->
    <div style="padding:0 18px 14px">
      <div id="qrModalUrl"
           style="font-size:.68rem;color:#6B7280;word-break:break-all;
                  background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;
                  padding:8px 10px;text-align:left;font-family:monospace;line-height:1.5"></div>
    </div>
    <!-- Botones -->
    <div style="padding:0 18px 18px;display:flex;gap:8px">
      <button onclick="copiarUrl()" id="btnCopiarQR"
              style="flex:1;padding:10px;background:#F3F4F6;color:#374151;border:none;
                     border-radius:10px;font-size:.8rem;font-weight:600;cursor:pointer">
        📋 Copiar URL
      </button>
      <button onclick="descargarQR()"
              style="flex:1;padding:10px;background:#111827;color:#fff;border:none;
                     border-radius:10px;font-size:.8rem;font-weight:600;cursor:pointer">
        ⬇️ Descargar PNG
      </button>
    </div>
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
function copiarUrl() {
  const url = document.getElementById('qrModalUrl').textContent;
  const btn = document.getElementById('btnCopiarQR');
  navigator.clipboard.writeText(url).then(() => {
    btn.textContent = '✅ Copiado';
    setTimeout(() => { btn.textContent = '📋 Copiar URL'; }, 2000);
  });
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

// ── Polling en vivo de estados de mesas ──────────────────────────────────────
const _mesaEstadoBadge = {
  disponible: ['badge badge-green',  'Disponible'],
  ocupada:    ['badge badge-red',    'Ocupada'],
  reservada:  ['badge badge-blue',   'Reservada'],
  pagando:    ['badge badge-amber',  'Pagando'],
};
function _pollMesas() {
  fetch('<?= BASE_URL ?>rest-mesa/estados', { credentials: 'same-origin' })
    .then(r => r.ok ? r.json() : null)
    .then(data => {
      if (!Array.isArray(data)) return;
      data.forEach(m => {
        const cell = document.querySelector('[data-estado-mesa="' + m.id + '"]');
        if (!cell) return;
        const [cls, txt] = _mesaEstadoBadge[m.estado] || ['badge badge-gray', m.estado];
        cell.innerHTML = '<span class="' + cls + '">' + txt + '</span>';
      });
    })
    .catch(() => {});
}
setInterval(_pollMesas, 10000); // cada 10 segundos
</script>
<?php
$content = ob_get_clean();
$activeMenu = 'rest_mesas';
$pageTitle  = 'Mesas';
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
