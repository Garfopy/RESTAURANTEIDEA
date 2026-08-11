<?php ob_start(); ?>
<?php
$appMovilHabilitada = $appMovilHabilitada ?? true;
$tipo = in_array(($tipo ?? 'todos'), ['todos', 'web', 'mobile'], true) ? $tipo : 'todos';
$filtros = [
  'todos' => 'Todos',
  'web' => 'Web',
  'mobile' => 'App movil',
];
if (!$appMovilHabilitada) {
  unset($filtros['mobile']);
}
?>
<div class="client-page">
  <div class="client-toolbar">
    <div class="client-filter-tabs">
      <?php foreach ($filtros as $key => $label): ?>
        <?php $activo = $tipo === $key; ?>
        <a href="<?= BASE_URL ?>rest-cliente/index/<?= $key ?>"
           class="client-filter-tab <?= $activo ? 'active' : '' ?>">
          <?= htmlspecialchars($label) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="rst-table-wrap client-table-shell">
    <table class="rst-table client-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Telefono</th>
          <th style="text-align:right">Visitas</th>
          <th style="text-align:right">Total gastado</th>
          <th>Ultima visita</th>
          <th>Detalle</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data as $c): ?>
        <?php
          $visitas = (int)($c['num_visitas'] ?? $c['total_visitas'] ?? 0);
          $gasto = (float)($c['gasto_total'] ?? $c['total_gastado'] ?? 0);
          $ult = $c['ultima_visita_real'] ?? $c['ultima_visita'] ?? null;
          $nombre = trim((string)($c['nombre'] ?? ''))
            ?: trim((string)($c['mobile_nombre'] ?? ''))
            ?: trim((string)($c['mobile_email'] ?? ''))
            ?: 'Usuario app';
          $telefono = trim((string)($c['telefono'] ?? '')) ?: trim((string)($c['mobile_telefono'] ?? ''));
          $detalleId = (string)($c['detalle_id'] ?? $c['id'] ?? '');
          $esMobile = ($c['origen'] ?? '') === 'mobile';
          $tieneApp = !empty($c['mobile_usuario_id']);
          if ($esMobile && $tieneApp) {
            $detalleId = 'app-' . abs((int)$c['mobile_usuario_id']);
          } elseif (($detalleId === '' || $detalleId === '0') && $tieneApp) {
            $detalleId = 'app-' . abs((int)$c['mobile_usuario_id']);
          }
        ?>
        <tr>
          <td>
            <div class="client-name-cell">
              <?= htmlspecialchars($nombre) ?>
              <?php if (!$esMobile): ?>
                <span class="client-source-badge web">Web</span>
              <?php endif; ?>
              <?php if ($appMovilHabilitada && $tieneApp): ?>
                <span class="client-source-badge app">App</span>
              <?php endif; ?>
            </div>
          </td>
          <td class="client-muted"><?= $telefono !== '' ? htmlspecialchars($telefono) : '&mdash;' ?></td>
          <td style="text-align:right"><?= $visitas ?></td>
          <td style="text-align:right;font-weight:800">$<?= number_format($gasto, 2) ?></td>
          <td class="client-muted" style="font-size:.82rem"><?= $ult ? date('d/m/Y', strtotime($ult)) : '&mdash;' ?></td>
          <td>
            <?php if ($detalleId !== '' && $detalleId !== '0'): ?>
              <a href="<?= BASE_URL ?>rest-cliente/detalle/<?= urlencode($detalleId) ?>" class="client-detail-link">Ver &rarr;</a>
            <?php else: ?>
              <span class="client-empty-link">Sin detalle</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($data)): ?>
        <tr><td colspan="6" class="empty-state">No hay comensales registrados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
