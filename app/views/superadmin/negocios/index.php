<?php ob_start(); ?>

<style>
.sa-header { align-items: center; display: flex; justify-content: space-between; margin-bottom: 18px; }
.sa-title { color: #0F172A; font-size: 1.45rem; font-weight: 800; margin: 0; }
.sa-copy { color: #64748B; font-size: .9rem; margin: 4px 0 0; }
.sa-badge {
  border-radius: 999px; display: inline-block; font-size: .72rem; font-weight: 700;
  padding: 3px 10px; text-transform: uppercase; letter-spacing: .02em;
}
.sa-badge-pendiente  { background: #FEF3C7; color: #92400E; }
.sa-badge-activo     { background: #D1FAE5; color: #065F46; }
.sa-badge-suspendido { background: #FEE2E2; color: #991B1B; }
.sa-badge-baja       { background: #E5E7EB; color: #374151; }
.sa-actions { display: flex; gap: 8px; }
.sa-btn {
  border: 1px solid #E2E8F0; border-radius: 8px; cursor: pointer; font-size: .78rem;
  font-weight: 600; padding: 6px 12px;
}
.sa-btn-approve { background: #059669; border-color: #059669; color: #fff; }
.sa-btn-suspend { background: #fff; color: #B91C1C; border-color: #FCA5A5; }
</style>

<div class="sa-header">
  <div>
    <h1 class="sa-title">Negocios</h1>
    <p class="sa-copy">Todos los negocios de la plataforma — <?= count($negocios) ?> en total.</p>
  </div>
</div>

<div class="rst-card">
  <div class="rst-table-wrap">
    <table class="rst-table">
      <thead>
        <tr>
          <th>Negocio</th>
          <th>Empresa</th>
          <th>Estado</th>
          <th>Plan</th>
          <th>Creado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($negocios as $n): ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars($n['nombre']) ?></strong><br>
            <span style="color:#94A3B8;font-size:.78rem"><?= htmlspecialchars($n['slug']) ?></span>
          </td>
          <td><?= htmlspecialchars($n['empresa_nombre'] ?? '—') ?></td>
          <td>
            <?php $estado = $n['estado_plataforma'] ?? ($n['activo'] ? 'activo' : 'baja'); ?>
            <span class="sa-badge sa-badge-<?= htmlspecialchars($estado) ?>"><?= htmlspecialchars($estado) ?></span>
          </td>
          <td><?= htmlspecialchars($n['plan_nombre'] ?? '—') ?></td>
          <td style="color:#64748B;font-size:.82rem"><?= htmlspecialchars(date('d/m/Y', strtotime($n['created_at']))) ?></td>
          <td>
            <div class="sa-actions">
              <?php if ($estado !== 'activo'): ?>
              <form method="post" action="<?= BASE_URL ?>superadmin/aprobar/<?= (int)$n['id'] ?>" style="display:inline">
                <button type="submit" class="sa-btn sa-btn-approve">Aprobar</button>
              </form>
              <?php endif; ?>
              <?php if ($estado !== 'suspendido'): ?>
              <form method="post" action="<?= BASE_URL ?>superadmin/suspender/<?= (int)$n['id'] ?>" style="display:inline"
                    onsubmit="return confirm('¿Suspender \'<?= htmlspecialchars($n['nombre'], ENT_QUOTES) ?>\'? Dejará de aparecer en la app móvil.');">
                <button type="submit" class="sa-btn sa-btn-suspend">Suspender</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($negocios)): ?>
        <tr><td colspan="6" style="text-align:center;color:#94A3B8;padding:24px">No hay negocios registrados todavía.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/superadmin/layouts/main.php';
