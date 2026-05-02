<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
$paradasJson = json_encode(array_map(function($p) {
    return [
        'ruta_detalle_id' => $p['id'],
        'lat'             => (float)($p['lat'] ?? 0),
        'lng'             => (float)($p['lng'] ?? 0),
        'empresa_nombre'  => $p['empresa_nombre'] ?? '',
        'sucursal_nombre' => $p['sucursal_nombre'] ?? '',
        'direccion'       => $p['sucursal_dir'] ?? $p['direccion'] ?? '',
        'folio'           => $p['folio'] ?? '',
        'estado'          => $p['estado'] ?? 'pendiente',
    ];
}, $ruta['paradas'] ?? []));
?>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
  <a href="<?= BASE_URL ?>logistica/rutas" style="color:#6B7280;text-decoration:none;font-size:.875rem">← Rutas</a>
  <h1 style="font-size:1.1rem;font-weight:700;margin:0"><?= htmlspecialchars($ruta['nombre'] ?? 'Mapa de ruta') ?></h1>
</div>

<div style="display:flex;gap:12px;height:calc(100vh - 160px);min-height:500px">
  <!-- Mapa -->
  <div id="mapLogistica" style="flex:1;border-radius:12px;overflow:hidden"></div>

  <!-- Panel paradas -->
  <div style="width:280px;overflow-y:auto;display:flex;flex-direction:column;gap:8px">
    <div style="font-weight:700;font-size:.875rem;color:#374151;margin-bottom:4px">
      Paradas (<?= count($ruta['paradas'] ?? []) ?>)
    </div>
    <?php foreach ($ruta['paradas'] ?? [] as $i => $p):
      $statusColors = ['pendiente'=>'#F59E0B','en_ruta'=>'#3B82F6','entregado'=>'#10B981','incidente'=>'#EF4444'];
      $color = $statusColors[$p['estado']] ?? '#6B7280';
    ?>
    <div class="card" style="padding:10px;border-left:3px solid <?= $color ?>">
      <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
        <div style="background:<?= $color ?>;color:#fff;font-size:.65rem;font-weight:800;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center">
          <?= $i+1 ?>
        </div>
        <div style="font-weight:600;font-size:.8rem"><?= htmlspecialchars($p['empresa_nombre']) ?></div>
      </div>
      <div style="font-size:.75rem;color:#6B7280"><?= htmlspecialchars($p['sucursal_nombre']) ?></div>
      <div style="font-size:.7rem;color:#9CA3AF;margin-top:2px">#<?= $p['folio'] ?> · $<?= number_format($p['total'],0,'.', ',') ?></div>
      <?php if ($p['hora_estimada']): ?>
      <div style="font-size:.7rem;color:#6B7280;margin-top:2px">🕐 <?= substr($p['hora_estimada'],0,5) ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= BASE_URL ?>public/js/logistica_mapa.js"></script>
<script>
const paradas = <?= $paradasJson ?>;
LogisticaMapa.init('mapLogistica', [20.5888, -100.3899], 12);
LogisticaMapa.plotParadas(paradas);
</script>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
