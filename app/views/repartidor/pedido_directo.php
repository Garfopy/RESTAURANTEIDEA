<?php
// Encontrar la primera sucursal pendiente y su número de orden
$paradaActual    = null;
$paradaActualNum = 1;
$todasEntregadas = true;
foreach ($sucursales as $i => $s) {
    if (empty($s['foto_entrega_path'])) {
        if (!$paradaActual) {
            $paradaActual    = $s;
            $paradaActualNum = $i + 1;
        }
        $todasEntregadas = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Entrega — <?= htmlspecialchars($pedido['folio']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #0F172A; color: #F1F5F9; font-family: 'Inter', sans-serif; min-height: 100vh; }

    .app-shell { max-width: 480px; margin: 0 auto; min-height: 100vh; display: flex; flex-direction: column; background: #111827; }

    .header {
      background: #1E293B; padding: 14px 16px;
      display: flex; align-items: center; gap: 12px;
      border-bottom: 1px solid #334155;
      position: sticky; top: 0; z-index: 10;
    }
    .header-back { color: #94A3B8; text-decoration: none; font-size: 1.3rem; line-height: 1; padding: 4px; }
    .header-title { font-weight: 800; font-size: .95rem; letter-spacing: -.01em; }
    .header-sub   { font-size: .72rem; color: #94A3B8; margin-top: 1px; }
    .badge-ruta { margin-left: auto; font-size: .7rem; padding: 4px 10px; border-radius: 999px; background: #78350F; color: #FCD34D; font-weight: 700; white-space: nowrap; }

    .body { padding: 16px; flex: 1; display: flex; flex-direction: column; gap: 12px; }

    .card { background: #1E293B; border-radius: 14px; padding: 16px; border: 1px solid #334155; }

    /* Progreso de paradas */
    .paradas-lista { display: flex; flex-direction: column; gap: 8px; }
    .parada-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 10px; background: #0F172A; border: 1px solid #334155; }
    .parada-item.activa { border-color: #F59E0B; background: #1C1407; }
    .parada-item.entregada { border-color: #065F46; background: #042F1F; }
    .parada-num { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 700; flex-shrink: 0; }
    .parada-num.pend { background: #334155; color: #94A3B8; }
    .parada-num.act  { background: #D97706; color: #fff; }
    .parada-num.done { background: #059669; color: #fff; }
    .parada-info { flex: 1; min-width: 0; }
    .parada-nombre { font-size: .82rem; font-weight: 600; color: #F1F5F9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .parada-estado { font-size: .7rem; color: #64748B; margin-top: 2px; }
    .parada-estado.done { color: #6EE7B7; }

    /* GPS status bar */
    #gpsStatus { display: none; border-radius: 10px; padding: 10px 14px; font-size: .82rem; font-weight: 600; background: #064E3B; color: #6EE7B7; border: 1px solid #065F46; }
    #gpsStatus.error { background: #431407; color: #FCA5A5; border-color: #7F1D1D; }

    /* Dirección */
    .dir-label { font-size: .68rem; font-weight: 700; color: #64748B; letter-spacing: .06em; margin-bottom: 4px; }
    .dir-text  { font-size: .93rem; font-weight: 600; color: #F1F5F9; line-height: 1.4; }
    .dir-ref   { font-size: .78rem; color: #94A3B8; margin-top: 3px; }
    .dir-maps  { font-size: .75rem; color: #60A5FA; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-top: 8px; }

    .notas-box { background: #0F172A; border-radius: 8px; padding: 10px 12px; font-size: .82rem; color: #FCD34D; border-left: 3px solid #D97706; }

    .step-label { font-size: .75rem; color: #64748B; font-weight: 600; text-align: center; margin-bottom: 8px; }

    .btn { width: 100%; border: none; border-radius: 12px; font-size: .95rem; font-weight: 700; cursor: pointer; padding: 15px; transition: opacity .15s; }
    .btn:active { opacity: .85; }
    .btn-yellow { background: linear-gradient(135deg, #D97706, #F59E0B); color: #fff; }
    .btn-green  { background: linear-gradient(135deg, #059669, #10B981); color: #fff; }
    .btn-gray   { background: #1E293B; color: #94A3B8; border: 1px solid #334155; border-radius: 12px; }

    #llegadaBtn  { display: none; }
    #entregaForm { display: none; }

    label.field-label { display: block; font-size: .78rem; font-weight: 600; color: #94A3B8; margin-bottom: 6px; }
    .file-input { width: 100%; background: #0F172A; border: 1px solid #334155; border-radius: 8px; padding: 10px; color: #F1F5F9; font-size: .82rem; }

    .flash-ok  { background: #064E3B; color: #6EE7B7; border: 1px solid #065F46; border-radius: 10px; padding: 12px 14px; font-size: .85rem; font-weight: 600; }
    .flash-err { background: #7F1D1D; color: #FCA5A5; border: 1px solid #991B1B; border-radius: 10px; padding: 12px 14px; font-size: .85rem; font-weight: 600; }

    #mapaContainer { display: none; border-radius: 14px; overflow: hidden; border: 1px solid #334155; }
    #mapaRepartidor { height: 210px; width: 100%; }
    @keyframes rippleR { 0% { transform: scale(.6); opacity: .8; } 100% { transform: scale(2.8); opacity: 0; } }
    @keyframes pulseR  { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.15); } }
  </style>
</head>
<body>

<div class="app-shell">

  <div class="header">
    <a href="<?= BASE_URL ?>repartidor/inicio" class="header-back">&larr;</a>
    <div>
      <div class="header-title"><?= htmlspecialchars($pedido['folio']) ?></div>
      <div class="header-sub"><?= htmlspecialchars($pedido['empresa_nombre']) ?></div>
    </div>
    <span class="badge-ruta">En camino</span>
  </div>

  <div class="body">

    <?php if (!empty($flash)): ?>
    <div class="<?= $flash['type']==='error' ? 'flash-err' : 'flash-ok' ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <!-- Progreso de paradas -->
    <div class="card">
      <div style="font-size:.72rem;font-weight:700;color:#64748B;letter-spacing:.06em;margin-bottom:10px">
        PARADAS DE ENTREGA (<?= count(array_filter($sucursales, fn($s) => !empty($s['foto_entrega_path']))) ?>/<?= count($sucursales) ?>)
      </div>
      <div class="paradas-lista">
        <?php foreach ($sucursales as $i => $s):
          $entregada = !empty($s['foto_entrega_path']);
          $esActual  = !$entregada && $paradaActual && $s['id'] === $paradaActual['id'];
          $clase     = $entregada ? 'entregada' : ($esActual ? 'activa' : '');
          $numCls    = $entregada ? 'done' : ($esActual ? 'act' : 'pend');
        ?>
        <div class="parada-item <?= $clase ?>">
          <div class="parada-num <?= $numCls ?>"><?= $entregada ? '✓' : ($i + 1) ?></div>
          <div class="parada-info">
            <div class="parada-nombre"><?= htmlspecialchars($s['sucursal_nombre']) ?></div>
            <div class="parada-estado <?= $entregada ? 'done' : '' ?>">
              <?php if ($entregada): ?>
                Entregada <?= $s['fecha_llegada'] ? '· ' . date('H:i', strtotime($s['fecha_llegada'])) : '' ?>
              <?php elseif ($esActual): ?>
                ← Siguiente
              <?php else: ?>
                Pendiente
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($paradaActual): ?>

    <!-- Destino actual -->
    <div class="card">
      <div class="dir-label">📍 PARADA <?= $paradaActualNum ?> — <?= htmlspecialchars(strtoupper($paradaActual['sucursal_nombre'])) ?></div>
      <?php if (!empty($paradaActual['direccion'])): ?>
      <div class="dir-text"><?= htmlspecialchars($paradaActual['direccion']) ?></div>
      <?php if (!empty($paradaActual['referencia_entrega'])): ?>
      <div class="dir-ref"><?= htmlspecialchars($paradaActual['referencia_entrega']) ?></div>
      <?php endif; ?>
      <a href="https://maps.google.com/?q=<?= urlencode($paradaActual['direccion']) ?>"
         target="_blank" class="dir-maps">Abrir en Google Maps ↗</a>
      <?php else: ?>
      <div style="font-size:.85rem;color:#64748B">Sin dirección registrada en sucursal</div>
      <?php endif; ?>
      <?php if (!empty($pedido['notas'])): ?>
      <div class="notas-box" style="margin-top:12px">📝 <?= htmlspecialchars($pedido['notas']) ?></div>
      <?php endif; ?>
    </div>

    <!-- Mini-mapa de posición -->
    <div id="mapaContainer">
      <div id="mapaRepartidor"></div>
    </div>

    <!-- Estado GPS -->
    <div id="gpsStatus">
      <span id="gpsLabel">⏳ Activando GPS...</span>
    </div>

    <!-- Paso 1: He llegado -->
    <div id="llegadaBtn">
      <div class="step-label">¿Ya llegaste a <?= htmlspecialchars($paradaActual['sucursal_nombre']) ?>?</div>
      <button class="btn btn-yellow" onclick="marcarLlegada()">
        📍 &nbsp;He llegado a <?= htmlspecialchars($paradaActual['sucursal_nombre']) ?>
      </button>
    </div>

    <!-- Paso 2: Foto de entrega -->
    <div id="entregaForm">
      <div class="card">
        <div style="font-weight:700;font-size:.95rem;margin-bottom:6px">📷 Foto de evidencia — <?= htmlspecialchars($paradaActual['sucursal_nombre']) ?></div>
        <p style="font-size:.8rem;color:#94A3B8;margin-bottom:14px;line-height:1.5">
          Toma una foto como prueba de entrega en esta sucursal.
        </p>
        <form method="POST"
              action="<?= BASE_URL ?>repartidor/confirmarParadaDirecta/<?= (int)$paradaActual['id'] ?>"
              enctype="multipart/form-data">
          <label class="field-label">Foto de evidencia *</label>
          <input type="file" name="foto" accept="image/*" capture="environment" required
                 class="file-input" style="margin-bottom:14px">
          <button type="submit" class="btn btn-green"
                  onclick="return confirm('¿Confirmar entrega en <?= htmlspecialchars(addslashes($paradaActual['sucursal_nombre'])) ?>?')">
            ✅ &nbsp;Confirmar entrega
          </button>
        </form>
      </div>
    </div>

    <?php else: ?>
    <!-- Todas entregadas (no debería llegar aquí — el controller redirige) -->
    <div class="flash-ok">✅ Todas las sucursales fueron entregadas.</div>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>repartidor/inicio" class="btn btn-gray"
       style="display:block;text-align:center;text-decoration:none;padding:13px">
      ← Volver al inicio
    </a>

  </div><!-- /.body -->
</div><!-- /.app-shell -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var BASE_URL  = '<?= BASE_URL ?>';
var pedidoId  = <?= (int)$pedido['id'] ?>;

<?php
$dLat = $paradaActual['lat'] ?? ($pedido['lat_entrega'] ?? null);
$dLng = $paradaActual['lng'] ?? ($pedido['lng_entrega'] ?? null);
$emLat = $pedido['empresa_lat'] ?? null;
$emLng = $pedido['empresa_lng'] ?? null;
?>
var _destLat = <?= $dLat  ? (float)$dLat  : 'null' ?>;
var _destLng = <?= $dLng  ? (float)$dLng  : 'null' ?>;
var _emLat   = <?= $emLat ? (float)$emLat : 'null' ?>;
var _emLng   = <?= $emLng ? (float)$emLng : 'null' ?>;

var _mapaR          = null;
var _marcadorPropio = null;
var _routeR         = null;
var _histR          = [];
var _iconoPropio    = null;

function _initMapa(lat, lng) {
  if (!_iconoPropio) {
    _iconoPropio = L.divIcon({
      className: '',
      html: '<div style="position:relative;width:44px;height:44px;display:flex;align-items:center;justify-content:center">'
          + '<div style="position:absolute;inset:0;border-radius:50%;background:#3B82F6;opacity:.22;animation:rippleR 2s ease-out infinite"></div>'
          + '<div style="position:relative;z-index:1;background:#3B82F6;color:#fff;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-size:15px;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.5);animation:pulseR 2s ease-in-out infinite">📍</div>'
          + '</div>',
      iconSize: [44, 44], iconAnchor: [22, 22],
    });
  }
  var cont = document.getElementById('mapaContainer');
  if (cont) cont.style.display = 'block';
  if (!_mapaR) {
    _mapaR = L.map('mapaRepartidor').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap', maxZoom: 19
    }).addTo(_mapaR);
    var dLat = _destLat || _emLat;
    var dLng = _destLng || _emLng;
    if (dLat && dLng) {
      L.marker([dLat, dLng], {icon: L.divIcon({
        className: '',
        html: '<div style="background:#C8102E;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:14px;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.4)">📦</div>',
        iconSize: [28,28], iconAnchor: [14,14],
      })}).addTo(_mapaR).bindPopup('Destino de entrega');
    }
    _marcadorPropio = L.marker([lat, lng], {icon: _iconoPropio}).addTo(_mapaR).bindPopup('Tu posición');
  } else {
    _marcadorPropio.setLatLng([lat, lng]);
    _mapaR.panTo([lat, lng]);
  }
  _histR.push([lat, lng]);
  if (_histR.length > 1) {
    if (_routeR) { _routeR.setLatLngs(_histR); }
    else { _routeR = L.polyline(_histR, {color:'#3B82F6', weight:3, opacity:.6, dashArray:'6,4'}).addTo(_mapaR); }
  }
}

var _lastSentLat = null, _lastSentLng = null;
var _lastSavedLat = null, _lastSavedLng = null;
var _lastSavedAt  = 0;

function _distM(la1, lo1, la2, lo2) {
  var R = 6371000;
  var dLa = (la2-la1)*Math.PI/180, dLo = (lo2-lo1)*Math.PI/180;
  var a = Math.sin(dLa/2)*Math.sin(dLa/2)
        + Math.cos(la1*Math.PI/180)*Math.cos(la2*Math.PI/180)*Math.sin(dLo/2)*Math.sin(dLo/2);
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function _guardarPosDB(lat, lng) {
  fetch(BASE_URL + 'api/guardarPosicion', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({pedido_id: pedidoId, lat: lat, lng: lng})
  }).catch(function() {});
}

function _checkGuardarPos(lat, lng) {
  var now = Date.now();
  var movedEnough = _lastSavedLat === null || _distM(_lastSavedLat, _lastSavedLng, lat, lng) >= 50;
  var timeEnough  = now - _lastSavedAt >= 300000;
  if (movedEnough || timeEnough) {
    _guardarPosDB(lat, lng);
    _lastSavedLat = lat; _lastSavedLng = lng; _lastSavedAt = now;
  }
}
</script>

<?php if ($firebaseActivo): ?>
<script type="module">
  import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
  import { getDatabase, ref, set, onDisconnect } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-database.js';

  const firebaseConfig = <?= json_encode($firebaseConfig) ?>;
  const app      = initializeApp(firebaseConfig);
  const db       = getDatabase(app);
  const trackRef = ref(db, 'tracking/' + <?= (int)$pedido['id'] ?>);

  onDisconnect(trackRef).remove();

  const gpsEl    = document.getElementById('gpsStatus');
  const gpsLabel = document.getElementById('gpsLabel');
  const llegBtn  = document.getElementById('llegadaBtn');

  if (gpsEl) gpsEl.style.display = 'block';

  if (navigator.geolocation) {
    navigator.geolocation.watchPosition(
      pos => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        const acc = Math.round(pos.coords.accuracy);

        if (gpsEl) gpsEl.classList.remove('error');
        if (gpsLabel) gpsLabel.textContent = '✅ GPS activo — precisión: ' + acc + ' m';
        if (llegBtn) llegBtn.style.display = 'block';

        if (_lastSentLat !== null && _distM(_lastSentLat, _lastSentLng, lat, lng) < 10) return;
        _lastSentLat = lat; _lastSentLng = lng;

        _initMapa(lat, lng);
        _checkGuardarPos(lat, lng);
        set(trackRef, { lat, lng, accuracy: acc, ts: Date.now(), llegado: window._llegado || false });
      },
      err => {
        if (gpsEl) { gpsEl.classList.add('error'); }
        if (gpsLabel) gpsLabel.textContent = '⚠ GPS no disponible — el botón sigue activo';
        if (llegBtn) llegBtn.style.display = 'block';
      },
      { enableHighAccuracy: true, maximumAge: 15000, timeout: 10000 }
    );
  } else {
    if (gpsEl) { gpsEl.classList.add('error'); }
    if (gpsLabel) gpsLabel.textContent = '⚠ GPS no soportado en este dispositivo';
    if (llegBtn) llegBtn.style.display = 'block';
  }

  window.marcarLlegada = function() {
    window._llegado = true;
    set(ref(db, 'tracking/' + <?= (int)$pedido['id'] ?> + '/llegado'), true);
    document.getElementById('llegadaBtn').style.display = 'none';
    document.getElementById('entregaForm').style.display = 'block';
  };
</script>
<?php else: ?>
<script>
  var llegBtn = document.getElementById('llegadaBtn');
  if (llegBtn) llegBtn.style.display = 'block';

  if (navigator.geolocation) {
    const gpsEl = document.getElementById('gpsStatus');
    if (gpsEl) gpsEl.style.display = 'block';
    navigator.geolocation.watchPosition(
      pos => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        const acc = Math.round(pos.coords.accuracy);
        const lbl = document.getElementById('gpsLabel');
        if (lbl) lbl.textContent = '✅ GPS activo — precisión: ' + acc + ' m';

        if (_lastSentLat !== null && _distM(_lastSentLat, _lastSentLng, lat, lng) < 10) return;
        _lastSentLat = lat; _lastSentLng = lng;

        _initMapa(lat, lng);
        _checkGuardarPos(lat, lng);
        fetch('<?= BASE_URL ?>api/actualizarTracking', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ pedido_id: <?= (int)$pedido['id'] ?>, lat, lng })
        });
      },
      err => {
        const gpsEl = document.getElementById('gpsStatus');
        const lbl   = document.getElementById('gpsLabel');
        if (gpsEl) gpsEl.classList.add('error');
        if (lbl) lbl.textContent = '⚠ GPS no disponible';
      },
      { enableHighAccuracy: true, maximumAge: 15000, timeout: 10000 }
    );
  }

  window.marcarLlegada = function() {
    document.getElementById('llegadaBtn').style.display = 'none';
    document.getElementById('entregaForm').style.display = 'block';
  };
</script>
<?php endif; ?>

</body>
</html>
