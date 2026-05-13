<?php ob_start(); ?>
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:20px">
  <table style="width:100%;border-collapse:collapse;font-size:.875rem">
    <thead>
      <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Nombre</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Teléfono</th>
        <th style="padding:12px 16px;text-align:right;font-weight:600;color:#374151">Visitas</th>
        <th style="padding:12px 16px;text-align:right;font-weight:600;color:#374151">Total gastado</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Última visita</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Detalle</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($data as $c): ?>
      <tr style="border-bottom:1px solid #F3F4F6">
        <td style="padding:12px 16px;font-weight:500"><?= htmlspecialchars($c['nombre'] ?? 'Visitante') ?></td>
        <td style="padding:12px 16px;color:#6B7280"><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
        <td style="padding:12px 16px;text-align:right"><?= (int)$c['total_visitas'] ?></td>
        <td style="padding:12px 16px;text-align:right;font-weight:600">$<?= number_format((float)$c['total_gastado'],2) ?></td>
        <td style="padding:12px 16px;color:#6B7280;font-size:.8rem"><?= $c['ultima_visita'] ? date('d/m/Y', strtotime($c['ultima_visita'])) : '—' ?></td>
        <td style="padding:12px 16px">
          <a href="<?= BASE_URL ?>rest-cliente/detalle/<?= $c['id'] ?>" style="font-size:.8rem;color:var(--color-primary);font-weight:500">Ver →</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($data)): ?>
      <tr><td colspan="6" style="padding:32px;text-align:center;color:#9CA3AF">No hay comensales registrados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
