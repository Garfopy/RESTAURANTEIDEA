<?php ob_start(); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div></div>
  <div style="display:flex;gap:10px">
    <button onclick="document.getElementById('modalZona').style.display='flex'"
      style="padding:8px 14px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;cursor:pointer;background:#fff">
      + Zona
    </button>
    <button onclick="document.getElementById('modalMesa').style.display='flex'"
      style="padding:8px 16px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer">
      + Mesa
    </button>
  </div>
</div>

<!-- Tabla mesas -->
<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <table style="width:100%;border-collapse:collapse;font-size:.875rem">
    <thead>
      <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Mesa</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Zona</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Capacidad</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Estado</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">QR</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($mesas as $m): ?>
      <tr style="border-bottom:1px solid #F3F4F6">
        <td style="padding:12px 16px;font-weight:500"><?= htmlspecialchars($m['nombre']) ?></td>
        <td style="padding:12px 16px;color:#6B7280"><?= htmlspecialchars($m['zona_nombre'] ?? '—') ?></td>
        <td style="padding:12px 16px"><?= (int)$m['capacidad'] ?></td>
        <td style="padding:12px 16px">
          <?php $colors=['disponible'=>['#DCFCE7','#166534'],'ocupada'=>['#FEE2E2','#991B1B'],'reservada'=>['#DBEAFE','#1E40AF'],'pagando'=>['#FEF3C7','#92400E']]; $cs=$colors[$m['estado']]??['#F3F4F6','#374151']; ?>
          <span style="padding:2px 10px;border-radius:99px;font-size:.75rem;font-weight:600;background:<?= $cs[0] ?>;color:<?= $cs[1] ?>"><?= $m['estado'] ?></span>
        </td>
        <td style="padding:12px 16px">
          <div id="qr-<?= $m['id'] ?>"></div>
          <div style="font-size:.7rem;color:#9CA3AF;margin-top:2px"><?= htmlspecialchars($m['qr_codigo']) ?></div>
        </td>
        <td style="padding:12px 16px">
          <a href="#" onclick="editMesa(<?= htmlspecialchars(json_encode($m)) ?>)"
             style="font-size:.8rem;color:var(--color-primary);font-weight:500">Editar</a>
          <a href="<?= BASE_URL ?>rest-mesa/layout" style="margin-left:10px;font-size:.8rem;color:#6B7280">Layout</a>
          <a href="<?= BASE_URL ?>rest-mesa/eliminar/<?= $m['id'] ?>" onclick="return confirm('¿Desactivar mesa?')"
             style="margin-left:10px;font-size:.8rem;color:#EF4444">Eliminar</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($mesas)): ?>
      <tr><td colspan="6" style="padding:32px;text-align:center;color:#9CA3AF">No hay mesas. Crea la primera.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal nueva/editar mesa -->
<div id="modalMesa" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:420px;max-width:95vw">
    <h3 style="font-weight:700;margin-bottom:18px" id="modalMesaTitle">Nueva Mesa</h3>
    <form method="POST" action="<?= BASE_URL ?>rest-mesa/guardar">
      <input type="hidden" name="id" id="mesaId" value="">
      <div style="margin-bottom:14px">
        <label style="font-size:.85rem;font-weight:500">Nombre</label>
        <input type="text" name="nombre" id="mesaNombre" required
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="margin-bottom:14px">
        <label style="font-size:.85rem;font-weight:500">Zona</label>
        <select name="zona_id" style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
          <option value="">Sin zona</option>
          <?php foreach ($zonas as $z): ?>
          <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="margin-bottom:18px">
        <label style="font-size:.85rem;font-weight:500">Capacidad</label>
        <input type="number" name="capacidad" id="mesaCapacidad" value="4" min="1" max="50"
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('modalMesa').style.display='none'"
          style="padding:8px 16px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;cursor:pointer;background:#fff">Cancelar</button>
        <button type="submit"
          style="padding:8px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:500;cursor:pointer">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal nueva zona -->
<div id="modalZona" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:380px;max-width:95vw">
    <h3 style="font-weight:700;margin-bottom:18px">Nueva Zona</h3>
    <form method="POST" action="<?= BASE_URL ?>rest-mesa/guardarZona">
      <div style="margin-bottom:14px">
        <label style="font-size:.85rem;font-weight:500">Nombre</label>
        <input type="text" name="nombre" required
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('modalZona').style.display='none'"
          style="padding:8px 16px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;cursor:pointer;background:#fff">Cancelar</button>
        <button type="submit"
          style="padding:8px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
// Generate QR codes
document.querySelectorAll('[id^="qr-"]').forEach(el => {
  const id  = el.id.replace('qr-', '');
  const row = el.closest('tr');
  const qr  = row.querySelector('[style*="qr_codigo"]')?.textContent || '';
  if (qr.trim()) {
    new QRCode(el, { text: '<?= BASE_URL ?>menu/<?= htmlspecialchars($rest['slug'] ?? '') ?>?mesa=' + qr.trim(), width: 64, height: 64, colorDark: '#000' });
  }
});

function editMesa(m) {
  document.getElementById('mesaId').value      = m.id;
  document.getElementById('mesaNombre').value   = m.nombre;
  document.getElementById('mesaCapacidad').value= m.capacidad;
  document.getElementById('modalMesaTitle').textContent = 'Editar Mesa';
  document.getElementById('modalMesa').style.display = 'flex';
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
