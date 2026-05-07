<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Entrega — <?= htmlspecialchars($pedido['folio']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body { background: #111827; color: #F9FAFB; font-family: 'Inter', sans-serif; min-height: 100vh; margin: 0; }
    .header { background: #1F2937; padding: 14px 16px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #374151; }
    .card { background: #1F2937; border-radius: 12px; padding: 16px; margin-bottom: 14px; }
    label { display: block; font-size: .8rem; font-weight: 600; color: #D1D5DB; margin-bottom: 6px; }
    .btn-green  { background: #059669; color: #fff; padding: 14px; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; width: 100%; }
    .btn-yellow { background: #D97706; color: #fff; padding: 14px; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; width: 100%; }
    .btn-red    { background: #C8102E; color: #fff; padding: 14px; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; width: 100%; }
    .btn-gray   { background: #374151; color: #F9FAFB; padding: 12px; border: none; border-radius: 10px; font-size: .9rem; font-weight: 600; cursor: pointer; width: 100%; }
    #gpsStatus { border-radius: 8px; padding: 10px 14px; font-size: .8rem; display: none; margin-bottom: 12px; }
    #llegadaBtn { display: none; }
    #entregaForm { display: none; }
  </style>
</head>
<body>

<div class="header">
  <a href="<?= BASE_URL ?>repartidor/inicio" style="color:#9CA3AF;text-decoration:none;font-size:1.4rem">&larr;</a>
  <div>
    <div style="font-weight:800;font-size:.95rem"><?= htmlspecialchars($pedido['folio']) ?></div>
    <div style="font-size:.75rem;color:#9CA3AF"><?= htmlspecialchars($pedido['empresa_nombre']) ?></div>
  </div>
  <span style="margin-left:auto;font-size:.72rem;padding:4px 12px;border-radius:999px;background:#78350F;color:#FCD34D;font-weight:600">En camino</span>
</div>

<div style="padding:16px">

  <?php if (!empty($flash)): ?>
  <div style="padding:12px;border-radius:8px;margin-bottom:12px;<?= $flash['type']==='error' ? 'background:#7F1D1D;color:#FCA5A5' : 'background:#064E3B;color:#6EE7B7' ?>">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>

  <!-- Info del pedido -->
  <div class="card">
    <?php if (!empty($pedido['direccion_entrega'])): ?>
    <div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px">
      <span style="font-size:1.1rem;flex-shrink:0">📍</span>
      <div>
        <div style="font-size:.78rem;color:#9CA3AF;margin-bottom:2px">DIRECCIÓN DE ENTREGA</div>
        <div style="font-size:.9rem;font-weight:600"><?= htmlspecialchars($pedido['direccion_entrega']) ?></div>
        <?php if (!empty($pedido['referencia_entrega'])): ?>
        <div style="font-size:.78rem;color:#9CA3AF;margin-top:3px"><?= htmlspecialchars($pedido['referencia_entrega']) ?></div>
        <?php endif; ?>
        <a href="https://maps.google.com/?q=<?= urlencode($pedido['direccion_entrega']) ?>" target="_blank"
           style="font-size:.75rem;color:#60A5FA;font-weight:600;display:inline-block;margin-top:4px">
          Abrir en Maps ↗
        </a>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($pedido['notas'])): ?>
    <div style="background:#111827;border-radius:6px;padding:8px;font-size:.8rem;color:#FCD34D">
      📝 Notas: <?= htmlspecialchars($pedido['notas']) ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Estado GPS -->
  <div id="gpsStatus" style="background:#064E3B;color:#6EE7B7">
    <span id="gpsLabel">⏳ Activando GPS...</span>
  </div>

  <!-- Paso 1: Botón "Llegué al destino" -->
  <div id="llegadaBtn" class="card" style="text-align:center">
    <div style="font-size:.8rem;color:#9CA3AF;margin-bottom:10px">¿Ya llegaste al destino?</div>
    <button class="btn-yellow" onclick="marcarLlegada()">
      📍 He llegado al destino
    </button>
  </div>

  <!-- Paso 2: Formulario de evidencia de entrega -->
  <div id="entregaForm">
    <div class="card">
      <div style="font-weight:700;margin-bottom:12px;font-size:.95rem">📷 Registrar entrega</div>
      <p style="font-size:.82rem;color:#9CA3AF;margin-bottom:14px">Toma una foto como evidencia de que el pedido fue entregado. Esta acción marcará el pedido como completado.</p>
      <form method="POST" action="<?= BASE_URL ?>repartidor/confirmarEntregaDirecta/<?= $pedido['id'] ?>"
            enctype="multipart/form-data" id="fotoForm">
        <div style="margin-bottom:12px">
          <label>Foto de evidencia *</label>
          <input type="file" name="foto" accept="image/*" capture="environment" required
                 style="width:100%;background:#374151;border:1px solid #4B5563;border-radius:8px;padding:10px;color:#F9FAFB;font-size:.85rem">
        </div>
        <button type="submit" class="btn-green"
                onclick="return confirm('¿Confirmar la entrega? El pedido se marcará como ENTREGADO.')">
          ✅ Confirmar entrega
        </button>
      </form>
    </div>
  </div>

  <a href="<?= BASE_URL ?>repartidor/inicio" class="btn-gray" style="display:block;text-align:center;text-decoration:none;margin-top:8px">
    ← Volver al inicio
  </a>
</div>

<?php if ($firebaseActivo): ?>
<script type="module">
  import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js';
  import { getDatabase, ref, set, onDisconnect } from 'https://www.gstatic.com/firebasejs/10.12.0/firebase-database.js';

  const firebaseConfig = <?= json_encode($firebaseConfig) ?>;
  const app = initializeApp(firebaseConfig);
  const db  = getDatabase(app);
  const pedidoId = <?= (int)$pedido['id'] ?>;
  const trackRef = ref(db, 'tracking/' + pedidoId);

  // Limpiar tracking al desconectar
  onDisconnect(trackRef).remove();

  document.getElementById('gpsStatus').style.display = 'block';

  if (navigator.geolocation) {
    navigator.geolocation.watchPosition(
      pos => {
        document.getElementById('gpsLabel').textContent = '✅ GPS activo — enviando ubicación';
        document.getElementById('llegadaBtn').style.display = 'block';
        set(trackRef, {
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
          accuracy: pos.coords.accuracy,
          ts: Date.now(),
          llegado: window._llegado || false
        });
      },
      err => {
        document.getElementById('gpsLabel').textContent = '⚠ GPS no disponible';
        document.getElementById('llegadaBtn').style.display = 'block';
      },
      { enableHighAccuracy: true, maximumAge: 15000, timeout: 10000 }
    );
  } else {
    document.getElementById('gpsLabel').textContent = '⚠ GPS no soportado';
    document.getElementById('llegadaBtn').style.display = 'block';
  }

  window.marcarLlegada = function() {
    window._llegado = true;
    set(ref(db, 'tracking/' + pedidoId + '/llegado'), true);
    document.getElementById('llegadaBtn').style.display = 'none';
    document.getElementById('entregaForm').style.display = 'block';
  };
</script>
<?php else: ?>
<script>
  // Sin Firebase: mostrar botones de todos modos
  document.getElementById('llegadaBtn').style.display = 'block';

  if (navigator.geolocation) {
    document.getElementById('gpsStatus').style.display = 'block';
    navigator.geolocation.watchPosition(pos => {
      document.getElementById('gpsLabel').textContent = '✅ GPS activo';
      fetch('<?= BASE_URL ?>api/actualizarTracking', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ pedido_id: <?= (int)$pedido['id'] ?>, lat: pos.coords.latitude, lng: pos.coords.longitude })
      });
    }, null, { enableHighAccuracy: true, maximumAge: 15000, timeout: 10000 });
  }

  window.marcarLlegada = function() {
    document.getElementById('llegadaBtn').style.display = 'none';
    document.getElementById('entregaForm').style.display = 'block';
  };
</script>
<?php endif; ?>

</body>
</html>
