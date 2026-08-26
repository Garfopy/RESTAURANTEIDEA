<?php ob_start(); ?>

<style>
.sa-header { margin-bottom: 18px; }
.sa-title { color: #0F172A; font-size: 1.45rem; font-weight: 800; margin: 0; }
.sa-copy { color: #64748B; font-size: .9rem; margin: 4px 0 0; }
.sa-filters { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
.sa-filters select, .sa-filters input {
  border: 1px solid #E2E8F0; border-radius: 8px; font-size: .82rem; padding: 6px 10px;
}
.sa-btn { border: 1px solid #E2E8F0; border-radius: 8px; cursor: pointer; font-size: .78rem; font-weight: 600; padding: 6px 12px; }
.sa-btn-primary { background: #1E293B; border-color: #1E293B; color: #fff; }
.sa-btn-toggle { background: #fff; color: #334155; text-decoration: none; }
.sa-chip { background: #EEF2FF; border-radius: 6px; color: #3730A3; font-size: .72rem; font-weight: 700; padding: 2px 8px; }
.sa-chip-rol { background: #F1F5F9; color: #475569; }
.sa-mono { color: #94A3B8; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .74rem; }
</style>

<div class="sa-header">
  <h1 class="sa-title">Bitácora</h1>
  <p class="sa-copy">Auditoría de acciones en la plataforma — <?= (int)$resultado['total'] ?> registro(s).</p>
</div>

<form method="get" action="<?= BASE_URL ?>superadmin/bitacora" class="sa-filters">
  <select name="modulo">
    <option value="">Todos los módulos</option>
    <?php foreach ($modulos as $m): ?>
    <option value="<?= htmlspecialchars($m) ?>" <?= ($filtros['modulo'] ?? '') === $m ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="fecha" value="<?= htmlspecialchars($filtros['fecha'] ?? '') ?>">
  <button type="submit" class="sa-btn sa-btn-toggle">Filtrar</button>
  <?php if (!empty($filtros['modulo']) || !empty($filtros['fecha'])): ?>
  <a class="sa-btn sa-btn-toggle" href="<?= BASE_URL ?>superadmin/bitacora">Limpiar</a>
  <?php endif; ?>
</form>

<div class="rst-card">
  <div class="rst-table-wrap">
    <table class="rst-table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Usuario</th>
          <th>Acción</th>
          <th>Módulo</th>
          <th>Detalle</th>
          <th>IP</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($resultado['data'] as $log): ?>
        <tr>
          <td style="white-space:nowrap"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($log['created_at']))) ?></td>
          <td>
            <?= htmlspecialchars($log['usuario_nombre'] ?? '—') ?>
            <?php if (!empty($log['rol'])): ?>
            <br><span class="sa-chip sa-chip-rol"><?= htmlspecialchars($log['rol']) ?></span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($log['accion'] ?? '—') ?></td>
          <td><?php if (!empty($log['modulo'])): ?><span class="sa-chip"><?= htmlspecialchars($log['modulo']) ?></span><?php else: ?>—<?php endif; ?></td>
          <td style="max-width:420px"><?= htmlspecialchars($log['descripcion'] ?? '') ?: '—' ?></td>
          <td><span class="sa-mono"><?= htmlspecialchars($log['ip'] ?? '—') ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($resultado['data'])): ?>
        <tr><td colspan="6" style="text-align:center;color:#94A3B8;padding:24px">Sin registros para este filtro.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($resultado['last_page'] > 1): ?>
<?php
  $qs = static function (int $page) use ($filtros): string {
      $params = ['page' => $page];
      if (!empty($filtros['modulo'])) $params['modulo'] = $filtros['modulo'];
      if (!empty($filtros['fecha']))  $params['fecha']  = $filtros['fecha'];
      return http_build_query($params);
  };
  $inicio = max(1, $resultado['current_page'] - 4);
  $fin    = min($resultado['last_page'], $inicio + 8);
?>
<div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:14px">
  <?php if ($resultado['current_page'] > 1): ?>
  <a class="sa-btn sa-btn-toggle" href="<?= BASE_URL ?>superadmin/bitacora?<?= $qs($resultado['current_page'] - 1) ?>">‹ Anterior</a>
  <?php endif; ?>
  <?php for ($i = $inicio; $i <= $fin; $i++): ?>
  <a class="sa-btn <?= $i === $resultado['current_page'] ? 'sa-btn-primary' : 'sa-btn-toggle' ?>"
     href="<?= BASE_URL ?>superadmin/bitacora?<?= $qs($i) ?>"><?= $i ?></a>
  <?php endfor; ?>
  <?php if ($resultado['current_page'] < $resultado['last_page']): ?>
  <a class="sa-btn sa-btn-toggle" href="<?= BASE_URL ?>superadmin/bitacora?<?= $qs($resultado['current_page'] + 1) ?>">Siguiente ›</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/superadmin/layouts/main.php';
