<?php ob_start(); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div></div>
  <button onclick="document.getElementById('modalRet').style.display='flex'"
    style="padding:8px 16px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer">
    + Registrar Retiro
  </button>
</div>

<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <table style="width:100%;border-collapse:collapse;font-size:.875rem">
    <thead>
      <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Descripción</th>
        <th style="padding:12px 16px;text-align:right;font-weight:600;color:#374151">Monto</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Fecha</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Registrado por</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($data as $r): ?>
      <tr style="border-bottom:1px solid #F3F4F6">
        <td style="padding:12px 16px"><?= htmlspecialchars($r['descripcion']) ?></td>
        <td style="padding:12px 16px;text-align:right;font-weight:600;color:#F59E0B">$<?= number_format((float)$r['monto'],2) ?></td>
        <td style="padding:12px 16px;color:#6B7280"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
        <td style="padding:12px 16px;color:#6B7280"><?= htmlspecialchars($r['usuario_nombre'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($data)): ?>
      <tr><td colspan="4" style="padding:32px;text-align:center;color:#9CA3AF">No hay retiros registrados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div id="modalRet" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:400px;max-width:95vw">
    <h3 style="font-weight:700;margin-bottom:18px">Registrar Retiro</h3>
    <form method="POST" action="<?= BASE_URL ?>rest-finanzas/guardarRetiro">
      <div style="margin-bottom:12px">
        <label style="font-size:.85rem;font-weight:500">Descripción *</label>
        <input type="text" name="descripcion" required
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="margin-bottom:18px">
        <label style="font-size:.85rem;font-weight:500">Monto *</label>
        <input type="number" name="monto" step="0.01" min="0" required
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('modalRet').style.display='none'"
          style="padding:8px 16px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;cursor:pointer;background:#fff">Cancelar</button>
        <button type="submit"
          style="padding:8px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:500;cursor:pointer">Registrar</button>
      </div>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
