<?php ob_start(); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div></div>
  <button onclick="document.getElementById('modalRes').style.display='flex'"
    style="padding:8px 16px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer">
    + Reservación
  </button>
</div>

<?php if (!empty($proximas)): ?>
<div style="background:#DBEAFE;border:1px solid #93C5FD;border-radius:12px;padding:16px;margin-bottom:16px;font-size:.875rem">
  <div style="font-weight:600;color:#1E40AF;margin-bottom:8px">Próximas (7 días)</div>
  <?php foreach ($proximas as $r): ?>
  <div style="padding:4px 0;color:#1E3A8A"><?= date('d/m H:i', strtotime($r['fecha'].' '.$r['hora'])) ?> — <strong><?= htmlspecialchars($r['nombre']) ?></strong> (<?= $r['personas'] ?> personas) <?= $r['mesa_nombre'] ? '· '.$r['mesa_nombre'] : '' ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <table style="width:100%;border-collapse:collapse;font-size:.875rem">
    <thead>
      <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Nombre</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Fecha</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Hora</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Personas</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Mesa</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Estado</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($data as $r): ?>
      <?php $colors=['pendiente'=>['#FEF3C7','#92400E'],'confirmada'=>['#DCFCE7','#166534'],'cancelada'=>['#FEE2E2','#991B1B'],'completada'=>['#F3F4F6','#374151']][$r['estado']] ?? ['#F3F4F6','#374151']; ?>
      <tr style="border-bottom:1px solid #F3F4F6">
        <td style="padding:12px 16px;font-weight:500"><?= htmlspecialchars($r['nombre']) ?></td>
        <td style="padding:12px 16px"><?= $r['fecha'] ?></td>
        <td style="padding:12px 16px"><?= substr($r['hora'],0,5) ?></td>
        <td style="padding:12px 16px;text-align:center"><?= (int)$r['personas'] ?></td>
        <td style="padding:12px 16px;color:#6B7280"><?= htmlspecialchars($r['mesa_nombre'] ?? '—') ?></td>
        <td style="padding:12px 16px">
          <span style="padding:2px 8px;border-radius:99px;font-size:.72rem;font-weight:600;background:<?= $colors[0] ?>;color:<?= $colors[1] ?>">
            <?= $r['estado'] ?>
          </span>
        </td>
        <td style="padding:12px 16px;display:flex;gap:8px">
          <form method="POST" action="<?= BASE_URL ?>rest-reserva/cambiarEstado/<?= $r['id'] ?>" style="display:inline">
            <input type="hidden" name="estado" value="<?= $r['estado'] === 'pendiente' ? 'confirmada' : 'completada' ?>">
            <button type="submit" style="font-size:.75rem;padding:3px 10px;border:1px solid #10B981;color:#10B981;background:none;border-radius:6px;cursor:pointer">
              <?= $r['estado'] === 'pendiente' ? 'Confirmar' : 'Completar' ?>
            </button>
          </form>
          <a href="<?= BASE_URL ?>rest-reserva/eliminar/<?= $r['id'] ?>" onclick="return confirm('¿Eliminar?')"
             style="font-size:.75rem;color:#EF4444">Eliminar</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($data)): ?>
      <tr><td colspan="7" style="padding:32px;text-align:center;color:#9CA3AF">No hay reservaciones.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal nueva reservación -->
<div id="modalRes" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:460px;max-width:95vw">
    <h3 style="font-weight:700;margin-bottom:18px">Nueva Reservación</h3>
    <form method="POST" action="<?= BASE_URL ?>rest-reserva/guardar">
      <input type="hidden" name="id" value="">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
        <div style="grid-column:span 2">
          <label style="font-size:.85rem;font-weight:500">Nombre *</label>
          <input type="text" name="nombre" required
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Teléfono</label>
          <input type="tel" name="telefono"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Personas</label>
          <input type="number" name="personas" value="2" min="1"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Fecha *</label>
          <input type="date" name="fecha" required
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Hora *</label>
          <input type="time" name="hora" required
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div style="grid-column:span 2">
          <label style="font-size:.85rem;font-weight:500">Notas</label>
          <input type="text" name="notas"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('modalRes').style.display='none'"
          style="padding:8px 16px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;cursor:pointer;background:#fff">Cancelar</button>
        <button type="submit"
          style="padding:8px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:500;cursor:pointer">Guardar</button>
      </div>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
