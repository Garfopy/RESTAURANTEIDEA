<?php ob_start(); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div></div>
  <button onclick="document.getElementById('modalGasto').style.display='flex'"
    style="padding:8px 16px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer">
    + Registrar Gasto
  </button>
</div>

<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <table style="width:100%;border-collapse:collapse;font-size:.875rem">
    <thead>
      <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Descripción</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Categoría</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Fecha</th>
        <th style="padding:12px 16px;text-align:right;font-weight:600;color:#374151">Monto</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Registrado por</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($data as $g): ?>
      <tr style="border-bottom:1px solid #F3F4F6">
        <td style="padding:12px 16px"><?= htmlspecialchars($g['descripcion']) ?></td>
        <td style="padding:12px 16px;color:#6B7280"><?= htmlspecialchars($g['categoria']) ?></td>
        <td style="padding:12px 16px;color:#6B7280"><?= $g['fecha'] ?></td>
        <td style="padding:12px 16px;text-align:right;font-weight:600;color:#EF4444">$<?= number_format((float)$g['monto'],2) ?></td>
        <td style="padding:12px 16px;color:#6B7280"><?= htmlspecialchars($g['usuario_nombre'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($data)): ?>
      <tr><td colspan="5" style="padding:32px;text-align:center;color:#9CA3AF">No hay gastos registrados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal gasto -->
<div id="modalGasto" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:440px;max-width:95vw">
    <h3 style="font-weight:700;margin-bottom:18px">Registrar Gasto</h3>
    <form method="POST" action="<?= BASE_URL ?>rest-finanzas/guardarGasto">
      <div style="margin-bottom:12px">
        <label style="font-size:.85rem;font-weight:500">Descripción *</label>
        <input type="text" name="descripcion" required
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
        <div>
          <label style="font-size:.85rem;font-weight:500">Categoría</label>
          <select name="categoria"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
            <option value="personal">Personal</option>
            <option value="suministros">Suministros</option>
            <option value="mantenimiento">Mantenimiento</option>
            <option value="servicios">Servicios</option>
            <option value="propinas">Propinas</option>
            <option value="marketing">Marketing</option>
            <option value="otros">Otros</option>
          </select>
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Monto *</label>
          <input type="number" name="monto" step="0.01" min="0" required
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
      </div>
      <div style="margin-bottom:18px">
        <label style="font-size:.85rem;font-weight:500">Fecha</label>
        <input type="date" name="fecha" value="<?= date('Y-m-d') ?>"
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('modalGasto').style.display='none'"
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
