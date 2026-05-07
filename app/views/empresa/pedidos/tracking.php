<?php
// Vista: Tracking GPS en tiempo real (Leaflet.js + OpenStreetMap + Firebase opcional)
$hayTracking   = !empty($tracking) && $tracking['lat_actual'] && $tracking['lng_actual'];
$sucursalLat   = $tracking['sucursal_lat'] ?? null;
$sucursalLng   = $tracking['sucursal_lng'] ?? null;
$estadoPedido  = $pedido['estado'] ?? 'pendiente';
$barraEstados  = [
    'pendiente'      => 0,
    'confirmado'     => 25,
    'en_preparacion' => 50,
    'en_ruta'        => 75,
    'entregado'      => 100,
];
$progreso = $barraEstados[$estadoPedido] ?? 0;
?>

<style>
@keyframes ripple {
  0%   { transform: scale(.7); opacity: .7; }
  100% { transform: scale(2.8); opacity: 0; }
}
@keyframes pulse-dot {
  0%, 100% { transform: scale(1); }
  50%       { transform: scale(1.12); }
}
</style>

<!-- Barra de progreso -->
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:16px 20px;margin-bottom:20px">
  <div style="display:flex;justify-content:space-between;font-size:.75rem;color:#6B7280;margin-bottom:8px">
    <span style="<?= $progreso >= 0   ? 'color:var(--color-primary);font-weight:700' : '' ?>">Pendiente</span>
    <span style="<?= $progreso >= 25  ? 'color:var(--color-primary);font-weight:700' : '' ?>">Confirmado</span>
    <span style="<?= $progreso >= 50  ? 'color:var(--color-primary);font-weight:700' : '' ?>">En preparación</span>
    <span style="<?= $progreso >= 75  ? 'color:var(--color-primary);font-weight:700' : '' ?>">En ruta</span>
    <span style="<?= $progreso >= 100 ? 'color:var(--color-primary);font-weight:700' : '' ?>">Entregado</span>
  </div>
  <div style="background:#E5E7EB;border-radius:999px;height:8px;overflow:hidden">
    <div style="width:<?= $progreso ?>%;background:var(--color-primary);height:100%;border-radius:999px;transition:width .5s ease"></div>
  </div>

  <?php if (!empty($tracking)): ?>
  <div style="display:flex;gap:20px;margin-top:12px;flex-wrap:wrap">
    <div style="font-size:.85rem">
      <span style="color:#6B7280">Repartidor: </span>
      <strong><?= htmlspecialchars($tracking['repartidor_nombre'] ?? '—') ?></strong>
    </div>
    <div style="font-size:.85rem">
      <span style="color:#6B7280">ETA: </span>
      <strong><?= $tracking['eta_minutos'] ? $tracking['eta_minutos'] . ' min' : '—' ?></strong>
    </div>
    <div style="font-size:.85rem">
      <span style="color:#6B7280">Destino: </span>
      <strong><?= htmlspecialchars($tracking['sucursal_nombre'] ?? '—') ?></strong>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Estado de conexión -->
<div id="estadoConexion" style="display:none;align-items:center;gap:8px;font-size:.78rem;color:#6B7280;margin-bottom:10px">
  <span id="conexionDot" style="width:8px;height:8px;border-radius:50%;background:#D1D5DB;display:inline-block;transition:background .4s"></span>
  <span id="conexionTxt">Conectando...</span>
</div>

<!-- Mapa Leaflet -->
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:16px">
  <div id="mapa" style="height:430px;width:100%"></div>
</div>

<!-- Alerta llegada -->
<div id="llegadoAlert" style="display:none;margin-bottom:14px;padding:13px 18px;background:#D1FAE5;border:1px solid #A7F3D0;border-radius:10px;font-size:.9rem;color:#065F46;font-weight:600">
  ✅ El repartidor ha llegado al destino. Esperando confirmación de entrega.
</div>

<?php if (!$hayTracking): ?>
<div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:20px;text-align:center;color:#6B7280;font-size:.875rem;margin-bottom:16px">
  <?php if ($estadoPedido === 'entregado'): ?>
    <span style="font-size:1.5rem">✅</span><br>Este pedido ya fue entregado.
  <?php elseif (in_array($estadoPedido, ['pendiente','confirmado'], true)): ?>
    <span style="font-size:1.5rem">⏳</span><br>El rastreo estará disponible cuando el repartidor inicie la entrega.
  <?php else: ?>
    <span style="font-size:1.5rem">📍</span><br>El repartidor aún no ha activado el rastreo GPS.
  <?php endif; ?>
</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
  <a href="<?= BASE_URL ?>pedido/detalle/<?= $pedido['id'] ?>"
     style="padding:9px 18px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
    ← Ver detalle
  </a>
</div>

<!-- Leaflet CSS + JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
var pedidoId = <?= (int)$pedido['id'] ?>;

var initLat = <?= $hayTracking ? (float)$tracking['lat_actual'] : ($sucursalLat ? (float)$sucursalLat : 19.4326) ?>;
var initLng = <?= $hayTracking ? (float)$tracking['lng_actual'] : ($sucursalLng ? (float)$sucursalLng : -99.1332) ?>;

var mapa = L.map('mapa').setView([initLat, initLng], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors', maxZoom: 19
}).addTo(mapa);

// Marcador repartidor: punto rojo pulsante con ripple
var iconoRepartidor = L.divIcon({
  className: '',
  html: '<div style="position:relative;width:52px;height:52px;display:flex;align-items:center;justify-content:center">'
      + '<div style="position:absolute;inset:0;border-radius:50%;background:#C8102E;opacity:.22;animation:ripple 2s ease-out infinite"></div>'
      + '<div style="position:relative;z-index:1;background:#C8102E;color:#fff;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:18px;border:3px solid #fff;box-shadow:0 2px 10px rgba(0,0,0,.4);animation:pulse-dot 2s ease-in-out infinite">🚚</div>'
      + '</div>',
  iconSize: [52, 52], iconAnchor: [26, 26],
});
var iconoSucursal = L.divIcon({
  className: '',
  html: '<div style="background:#1E40AF;color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:16px;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3)">📦</div>',
  iconSize: [32, 32], iconAnchor: [16, 16],
});

var marcadorRepartidor = null;
var posicionesRuta     = [];
var routeLine          = null;

// Destino fijo
<?php if ($sucursalLat && $sucursalLng): ?>
L.marker([<?= (float)$sucursalLat ?>, <?= (float)$sucursalLng ?>], {icon: iconoSucursal})
  .addTo(mapa).bindPopup('Destino: <?= htmlspecialchars(addslashes($tracking['sucursal_nombre'] ?? '')) ?>');
<?php endif; ?>

// Si ya hay posición conocida al cargar
<?php if ($hayTracking): ?>
marcadorRepartidor = L.marker([<?= (float)$tracking['lat_actual'] ?>, <?= (float)$tracking['lng_actual'] ?>], {icon: iconoRepartidor})
  .addTo(mapa).bindPopup('Repartidor: <?= htmlspecialchars(addslashes($tracking['repartidor_nombre'] ?? '')) ?>');
posicionesRuta.push([<?= (float)$tracking['lat_actual'] ?>, <?= (float)$tracking['lng_actual'] ?>]);
<?php endif; ?>

// Centraliza la lógica de actualización del marcador + trail
function actualizarPosicion(lat, lng) {
  var pos = [lat, lng];
  posicionesRuta.push(pos);
  if (marcadorRepartidor) {
    marcadorRepartidor.setLatLng(pos);
    mapa.panTo(pos);
  } else {
    marcadorRepartidor = L.marker(pos, {icon: iconoRepartidor})
      .addTo(mapa).bindPopup('🚚 Repartidor en camino').openPopup();
  }
  if (posicionesRuta.length > 1) {
    if (routeLine) {
      routeLine.setLatLngs(posicionesRuta);
    } else {
      routeLine = L.polyline(posicionesRuta, {
        color: '#C8102E', weight: 3, opacity: .6, dashArray: '8,5'
      }).addTo(mapa);
    }
  }
}

function setConexion(ok, txt) {
  var c = document.getElementById('estadoConexion');
  var d = document.getElementById('conexionDot');
  var t = document.getElementById('conexionTxt');
  if (!c) return;
  c.style.display = 'flex';
  d.style.background = ok ? '#10B981' : '#F59E0B';
  t.textContent = txt;
}
</script>

<?php if (!empty($firebaseActivo)): ?>
<script type="module">
  import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
  import { getDatabase, ref, onValue } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-database.js';

  const app = initializeApp(<?= json_encode($firebaseConfig) ?>);
  const db  = getDatabase(app);

  setConexion(true, 'Conectado — actualizando en tiempo real');

  onValue(ref(db, 'tracking/<?= (int)$pedido['id'] ?>'), snap => {
    const d = snap.val();
    if (!d || !d.lat || !d.lng) return;
    actualizarPosicion(d.lat, d.lng);
    setConexion(true, 'Última actualización: ' + new Date().toLocaleTimeString());
    if (d.llegado) {
      const al = document.getElementById('llegadoAlert');
      if (al) al.style.display = 'block';
    }
  });
</script>
<?php else: ?>
<script>
<?php if ($hayTracking): ?>
setConexion(true, 'Actualizando cada 5 segundos...');
function pollingTracking() {
  fetch('<?= BASE_URL ?>api/tracking/' + pedidoId)
    .then(r => r.json())
    .then(d => {
      if (!d.lat || !d.lng) return;
      actualizarPosicion(d.lat, d.lng);
      setConexion(true, 'Actualizado: ' + new Date().toLocaleTimeString());
      if (d.estado === 'entregado') { clearInterval(pollingInterval); location.reload(); }
    }).catch(() => setConexion(false, 'Sin señal — reintentando...'));
}
var pollingInterval = setInterval(pollingTracking, 5000);
<?php endif; ?>
</script>
<?php endif; ?>
