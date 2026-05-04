<?php
// Vista: Tracking GPS en tiempo real (Leaflet.js + OpenStreetMap)
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
<!-- Barra de progreso de entrega -->
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

<!-- Mapa Leaflet -->
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:20px">
  <div id="mapa" style="height:400px;width:100%"></div>
</div>

<?php if (!$hayTracking): ?>
<div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:20px;text-align:center;color:#6B7280;font-size:.875rem">
  <?php if ($estadoPedido === 'entregado'): ?>
    <span style="font-size:1.5rem">✅</span><br>
    Este pedido ya fue entregado.
  <?php elseif (in_array($estadoPedido, ['pendiente','confirmado'], true)): ?>
    <span style="font-size:1.5rem">⏳</span><br>
    El rastreo estará disponible cuando el repartidor inicie la entrega.
  <?php else: ?>
    <span style="font-size:1.5rem">📍</span><br>
    El repartidor aún no ha activado el rastreo GPS.
  <?php endif; ?>
</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
  <a href="<?= BASE_URL ?>pedido/detalle/<?= $pedido['id'] ?>"
     style="padding:9px 18px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
    ← Ver detalle
  </a>
  <?php if ($hayTracking): ?>
  <div style="font-size:.75rem;color:#9CA3AF">Posición actualizada cada 5 segundos</div>
  <?php endif; ?>
</div>

<!-- Leaflet CSS + JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
var pedidoId    = <?= (int)$pedido['id'] ?>;
var hayTracking = <?= $hayTracking ? 'true' : 'false' ?>;

// Coordenadas iniciales
var initLat = <?= $hayTracking ? $tracking['lat_actual'] : ($sucursalLat ?? '19.4326') ?>;
var initLng = <?= $hayTracking ? $tracking['lng_actual'] : ($sucursalLng ?? '-99.1332') ?>;

var mapa = L.map('mapa').setView([initLat, initLng], 14);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors',
  maxZoom: 19
}).addTo(mapa);

// Icono repartidor
var iconoRepartidor = L.divIcon({
  className: '',
  html: '<div style="background:var(--color-primary,#C8102E);color:#fff;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:18px;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3)">🚚</div>',
  iconSize: [36, 36],
  iconAnchor: [18, 18],
});

// Icono sucursal
var iconoSucursal = L.divIcon({
  className: '',
  html: '<div style="background:#1E40AF;color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:16px;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3)">📦</div>',
  iconSize: [32, 32],
  iconAnchor: [16, 32],
});

var marcadorRepartidor = null;
var marcadorSucursal   = null;

<?php if ($hayTracking): ?>
marcadorRepartidor = L.marker([<?= $tracking['lat_actual'] ?>, <?= $tracking['lng_actual'] ?>], {icon: iconoRepartidor})
  .addTo(mapa)
  .bindPopup('Repartidor: <?= htmlspecialchars($tracking['repartidor_nombre'] ?? '') ?>');
<?php endif; ?>

<?php if ($sucursalLat && $sucursalLng): ?>
marcadorSucursal = L.marker([<?= $sucursalLat ?>, <?= $sucursalLng ?>], {icon: iconoSucursal})
  .addTo(mapa)
  .bindPopup('Destino: <?= htmlspecialchars($tracking['sucursal_nombre'] ?? '') ?>');
<?php endif; ?>

// Polling AJAX cada 5 segundos cuando hay tracking activo
<?php if ($hayTracking): ?>
function actualizarMapa() {
  fetch('<?= BASE_URL ?>api/tracking/' + pedidoId)
    .then(r => r.json())
    .then(d => {
      if (!d.lat || !d.lng) return;
      var pos = [d.lat, d.lng];
      if (marcadorRepartidor) {
        marcadorRepartidor.setLatLng(pos);
      } else {
        marcadorRepartidor = L.marker(pos, {icon: iconoRepartidor}).addTo(mapa);
      }
      if (d.eta !== null) {
        document.querySelector('[data-eta]') && (document.querySelector('[data-eta]').textContent = d.eta + ' min');
      }
      if (d.estado === 'entregado') {
        clearInterval(pollingInterval);
        location.reload();
      }
    })
    .catch(() => {});
}

var pollingInterval = setInterval(actualizarMapa, 5000);
<?php endif; ?>
</script>
