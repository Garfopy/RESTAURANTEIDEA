<?php ob_start(); ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <h2 style="margin:0;font-size:1.15rem;color:#111827">🎁 Promociones</h2>
    <p style="margin:4px 0 0;font-size:.82rem;color:#6B7280">
      Crea descuentos especiales para comensales específicos. Se sincronizan automáticamente con la app móvil.
    </p>
  </div>
  <a href="<?= BASE_URL ?>rest-promocion/crear"
     style="background:var(--cp);color:#fff;border:none;border-radius:8px;padding:10px 20px;
            font-size:.85rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:8px;
            white-space:nowrap">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nueva Promoción
  </a>
</div>

<?php if (empty($promociones)): ?>
  <!-- Mensaje vacío -->
  <div id="promo-empty" style="background:#F9FAFB;border:2px dashed #D1D5DB;border-radius:12px;padding:48px 24px;text-align:center">
    <div style="font-size:2.5rem;margin-bottom:12px">🎁</div>
    <div style="font-weight:600;color:#374151;font-size:1rem;margin-bottom:6px">No hay promociones creadas</div>
    <div style="color:#9CA3AF;font-size:.82rem;margin-bottom:16px">
      Crea tu primera promoción para ofrecer descuentos a tus comensales desde la app móvil.
    </div>
    <a href="<?= BASE_URL ?>rest-promocion/crear"
       style="display:inline-block;background:var(--cp);color:#fff;padding:10px 24px;border-radius:8px;
              font-weight:600;font-size:.85rem;text-decoration:none">
      Crear primera promoción
    </a>
  </div>
<?php else: ?>
  <!-- Tabla de promociones -->
  <div id="promo-table" style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:.875rem">
      <thead>
        <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
          <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Promoción</th>
          <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151">Tipo</th>
          <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151">Descuento</th>
          <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151">Vigencia</th>
          <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151">Estado</th>
          <th style="padding:12px 16px;text-align:right;font-weight:600;color:#374151">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($promociones as $p): 
          $activo = (int)$p['activo'] === 1;
          $fechaFin = !empty($p['fecha_fin']) ? new DateTime($p['fecha_fin']) : new DateTime();
          $expirada = !empty($p['fecha_fin']) && $fechaFin < new DateTime();
        ?>
          <tr style="border-bottom:1px solid #F3F4F6">
            <td style="padding:12px 16px">
              <div style="font-weight:600;color:#111827"><?= htmlspecialchars($p['titulo'] ?? 'Sin título') ?></div>
              <?php if (!empty($p['descripcion'])): ?>
                <div style="font-size:.78rem;color:#6B7280;margin-top:2px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                  <?= htmlspecialchars($p['descripcion']) ?>
                </div>
              <?php endif; ?>
            </td>
            <td style="padding:12px 16px;text-align:center;text-transform:capitalize;color:#374151;font-size:.82rem">
              <?= htmlspecialchars(str_replace('_', ' ', $p['tipo'] ?? '')) ?>
            </td>
            <td style="padding:12px 16px;text-align:center;font-weight:600;color:#111827">
              <?php if (($p['tipo'] ?? '') === 'porcentaje'): ?>
                <?= (int)($p['valor_descuento'] ?? 0) ?>%
              <?php elseif (($p['tipo'] ?? '') === 'envio_gratis'): ?>
                Gratis
              <?php else: ?>
                $<?= number_format((float)($p['valor_descuento'] ?? 0), 2) ?>
              <?php endif; ?>
            </td>
            <td style="padding:12px 16px;text-align:center;font-size:.78rem;color:#6B7280">
              <?= !empty($p['fecha_inicio']) ? date('d/m/Y', strtotime($p['fecha_inicio'])) : '--' ?> <br> 
              <?= !empty($p['fecha_fin']) ? date('d/m/Y', strtotime($p['fecha_fin'])) : '--' ?>
            </td>
            <td style="padding:12px 16px;text-align:center">
              <?php if (!$activo): ?>
                <span style="display:inline-block;padding:4px 10px;border-radius:99px;font-size:.75rem;font-weight:600;background:#FEF2F2;color:#EF4444">Inactiva</span>
              <?php elseif ($expirada): ?>
                <span style="display:inline-block;padding:4px 10px;border-radius:99px;font-size:.75rem;font-weight:600;background:#F3F4F6;color:#9CA3AF">Expirada</span>
              <?php else: ?>
                <span style="display:inline-block;padding:4px 10px;border-radius:99px;font-size:.75rem;font-weight:600;background:#ECFDF5;color:#059669">Activa</span>
              <?php endif; ?>
            </td>
            <td style="padding:12px 16px;text-align:right;white-space:nowrap">
              <a href="<?= BASE_URL ?>rest-promocion/editar/<?= $p['id'] ?>" 
                 style="font-size:.82rem;color:var(--cp);font-weight:500;text-decoration:none;margin-right:12px">Editar</a>
              
              <form action="<?= BASE_URL ?>rest-promocion/eliminar/<?= $p['id'] ?>" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar la promoción «<?= htmlspecialchars(addslashes($p['titulo'] ?? '')) ?>»?\nEsta acción no se puede deshacer.');">
                <button type="submit" style="background:none;border:none;color:#EF4444;font-size:.82rem;font-weight:500;cursor:pointer;padding:0">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';