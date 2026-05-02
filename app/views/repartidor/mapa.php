<?php
include ROOT_PATH . '/app/views/components/header_repartidor.php';
$entregasJson = json_encode(array_map(function($e) {
    return [
        'id'       => $e['id'],
        'lat'      => (float)($e['lat'] ?? 0),
        'lng'      => (float)($e['lng'] ?? 0),
        'empresa'  => $e['empresa_nombre'] ?? '',
        'sucursal' => $e['sucursal_nombre'] ?? '',
        'estado'   => $e['estado'] ?? 'pendiente',
        'folio'    => $e['folio'] ?? '',
    ];
}, $entregas));
?>
<div style="position:relative">
  <div id="mapaRepartidor" style="width:100%;height:calc(100vh - 80px)"></div>

  <!-- Overlay info ruta -->
  <?php if ($ruta): ?>
  <div style="position:absolute;top:12px;left:12px;right:12px;background:#1E2130;border-radius:10px;padding:12px;box-shadow:0 2px 10px rgba(0,0,0,.4);z-index:500">
    <div style="font-size:.75rem;color:#94A3B8">Ruta activa</div>
    <div style="font-weight:700"><?= htmlspecialchars($ruta['nombre']) ?></div>
    <div style="font-size:.75rem;color:#64748B"><?= count($entregas) ?> paradas</div>
  </div>
  <?php endif; ?>
</div>

<nav class="rep-bottom-nav">
  <a href="<?= BASE_URL ?>repartidor/inicio" class="rep-nav-item">🏠<span>Inicio</span></a>
  <a href="<?= BASE_URL ?>repartidor/entregas" class="rep-nav-item">📦<span>Entregas</span></a>
  <a href="<?= BASE_URL ?>repartidor/mapa" class="rep-nav-item active">🗺️<span>Mapa</span></a>
  <a href="<?= BASE_URL ?>repartidor/historial" class="rep-nav-item">📋<span>Historial</span></a>
  <a href="<?= BASE_URL ?>repartidor/perfil" class="rep-nav-item">👤<span>Perfil</span></a>
</nav>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const entregas = <?= $entregasJson ?>;
const colores = { pendiente:'#F59E0B', en_ruta:'#3B82F6', entregado:'#10B981', incidente:'#EF4444' };

const map = L.map('mapaRepartidor', { zoomControl: false }).setView([20.5888, -100.3899], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap'
}).addTo(map);

const latlngs = [];

entregas.forEach((e, i) => {
  if (!e.lat && !e.lng) return;
  const color = colores[e.estado] || '#6B7280';
  const icon = L.divIcon({
    html: `<div style="background:${color};color:#fff;font-size:11px;font-weight:800;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 4px rgba(0,0,0,.4)">${i+1}</div>`,
    iconSize: [26,26], iconAnchor: [13,13], className:''
  });
  L.marker([e.lat, e.lng], { icon })
   .addTo(map)
   .bindPopup(`<strong>${e.empresa}</strong><br>${e.sucursal}<br>#${e.folio}`);
  latlngs.push([e.lat, e.lng]);
});

if (latlngs.length) {
  L.polyline(latlngs, { color:'#C8102E', weight:3, dashArray:'8,6' }).addTo(map);
  map.fitBounds(latlngs, { padding:[40,40] });
}

// Geolocalización
if (navigator.geolocation) {
  navigator.geolocation.watchPosition(pos => {
    const { latitude: lat, longitude: lng } = pos.coords;
    if (!window._myMarker) {
      window._myMarker = L.circleMarker([lat, lng], { radius:8, fillColor:'#C8102E', fillOpacity:1, color:'#fff', weight:2 })
        .addTo(map).bindPopup('Tú estás aquí');
    } else {
      window._myMarker.setLatLng([lat, lng]);
    }
  });
}
</script>

<?php include ROOT_PATH . '/app/views/components/footer_repartidor.php'; ?>
