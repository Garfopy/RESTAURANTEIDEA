<?php ob_start();
// Obtener slug del restaurante para el QR
$_resQr  = (new RestauranteModel())->find($_SESSION['restaurante_activo_id'] ?? 0);
$_qrSlug = $_resQr['slug'] ?? '';
$_qrUrl  = BASE_URL . 'menu/' . $_qrSlug . '/reservar';

// Separar por origen
$_delComensal    = array_values(array_filter($data ?? [], fn($r) => ($r['origen'] ?? 'restaurante') === 'comensal'));
$_delRestaurante = array_values(array_filter($data ?? [], fn($r) => ($r['origen'] ?? 'restaurante') === 'restaurante'));

// Helper badge de estado
$_badge = function(string $estado): string {
    $map = [
        'pendiente'  => ['#FEF3C7','#92400E'],
        'confirmada' => ['#DCFCE7','#166534'],
        'cancelada'  => ['#FEE2E2','#991B1B'],
        'completada' => ['#F3F4F6','#374151'],
    ];
    [$bg, $fg] = $map[$estado] ?? ['#F3F4F6','#374151'];
    return "<span style='padding:2px 8px;border-radius:99px;font-size:.72rem;font-weight:600;background:$bg;color:$fg'>$estado</span>";
};

// Cargar meseros de turno activo hoy (para el select del modal asignar)
$_stmtMs = Database::getInstance()->prepare(
    "SELECT u.id, u.nombre FROM rest_mesero_turno mt
     JOIN usuarios u ON u.id = mt.usuario_id
     WHERE mt.restaurante_id = ? AND mt.turno_fecha = CURDATE() AND mt.activo = 1
     ORDER BY u.nombre"
);
$_stmtMs->execute([$_SESSION['restaurante_activo_id'] ?? 0]);
$_meserosTurno = $_stmtMs->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ── Header ───────────────────────────────────────────────── -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <button onclick="document.getElementById('modalQr').style.display='flex'"
    style="padding:8px 14px;background:#fff;color:#374151;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer">
    📱 QR reservas
  </button>
  <button onclick="document.getElementById('modalRes').style.display='flex'"
    style="padding:8px 16px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:500;cursor:pointer">
    + Reservación
  </button>
</div>

<!-- ── Banner próximas ──────────────────────────────────────── -->
<?php if (!empty($proximas)): ?>
<div style="background:#DBEAFE;border:1px solid #93C5FD;border-radius:12px;padding:16px;margin-bottom:16px;font-size:.875rem">
  <div style="font-weight:600;color:#1E40AF;margin-bottom:8px">Próximas (7 días)</div>
  <?php foreach ($proximas as $r): ?>
  <div style="padding:4px 0;color:#1E3A8A">
    <?= date('d/m H:i', strtotime($r['fecha'].' '.$r['hora'])) ?> —
    <strong><?= htmlspecialchars($r['nombre']) ?></strong> (<?= $r['personas'] ?> personas)
    <?= $r['mesa_nombre'] ? '· '.$r['mesa_nombre'] : '' ?>
    <?php if (($r['origen'] ?? '') === 'comensal'): ?>
      <span style="font-size:.7rem;background:#FEF3C7;color:#92400E;padding:1px 6px;border-radius:99px;margin-left:4px">QR</span>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══ SECCIÓN 1: Solicitudes del comensal (vía QR) ══════════ -->
<div style="margin-bottom:28px">
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
    <span style="font-size:.95rem;font-weight:700;color:#111827">📱 Solicitudes del comensal</span>
    <?php $pendQr = count(array_filter($_delComensal, fn($r) => $r['estado'] === 'pendiente')); ?>
    <?php if ($pendQr > 0): ?>
      <span style="background:#FEF3C7;color:#92400E;font-size:.72rem;font-weight:700;padding:2px 9px;border-radius:99px">
        <?= $pendQr ?> sin atender
      </span>
    <?php endif; ?>
  </div>

  <?php if (empty($_delComensal)): ?>
    <div style="background:#fff;border:1px dashed #D1D5DB;border-radius:12px;padding:24px;text-align:center;color:#9CA3AF;font-size:.88rem">
      Aún no hay solicitudes. Comparte el QR para recibir reservaciones de tus comensales.
    </div>
  <?php else: ?>
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:.875rem">
      <thead>
        <tr style="background:#FFFBEB;border-bottom:1px solid #FDE68A">
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Comensal</th>
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Fecha / Hora</th>
          <th style="padding:10px 14px;text-align:center;font-weight:600;color:#374151">Pax</th>
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Mesa</th>
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Mesero</th>
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Estado</th>
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($_delComensal as $r): ?>
      <tr style="border-bottom:1px solid #F3F4F6">
        <td style="padding:11px 14px">
          <div style="font-weight:600"><?= htmlspecialchars($r['nombre']) ?></div>
          <?php if ($r['telefono']): ?>
            <a href="tel:<?= preg_replace('/\D/', '', $r['telefono']) ?>"
               style="font-size:.76rem;color:#6B7280;text-decoration:none">
              <?= htmlspecialchars($r['telefono']) ?>
            </a>
          <?php endif; ?>
          <?php if ($r['notas']): ?>
            <div style="font-size:.74rem;color:#9CA3AF;font-style:italic;margin-top:2px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                 title="<?= htmlspecialchars($r['notas']) ?>">
              <?= htmlspecialchars($r['notas']) ?>
            </div>
          <?php endif; ?>
        </td>
        <td style="padding:11px 14px">
          <div style="font-weight:600"><?= date('d/m/Y', strtotime($r['fecha'])) ?></div>
          <div style="color:#6B7280;font-size:.85rem"><?= substr($r['hora'],0,5) ?></div>
        </td>
        <td style="padding:11px 14px;text-align:center;font-weight:600"><?= (int)$r['personas'] ?></td>
        <td style="padding:11px 14px">
          <?php if ($r['mesa_nombre']): ?>
            <span style="color:#111827"><?= htmlspecialchars($r['mesa_nombre']) ?></span>
          <?php else: ?>
            <span style="font-size:.78rem;color:#EF4444;font-weight:600">Sin asignar</span>
          <?php endif; ?>
        </td>
        <td style="padding:11px 14px;color:#6B7280;font-size:.85rem">
          <?= $r['mesero_nombre'] ? htmlspecialchars($r['mesero_nombre']) : '—' ?>
        </td>
        <td style="padding:11px 14px"><?= $_badge($r['estado']) ?></td>
        <td style="padding:11px 14px">
          <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
            <button onclick="abrirAsignar(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['nombre'])) ?>')"
              style="font-size:.75rem;padding:4px 10px;background:#1D4ED8;color:#fff;border:none;border-radius:6px;cursor:pointer;white-space:nowrap">
              🪑 Asignar
            </button>
            <?php if (!in_array($r['estado'], ['cancelada','completada'])): ?>
            <form method="POST" action="<?= BASE_URL ?>rest-reserva/cambiarEstado/<?= $r['id'] ?>" style="display:inline">
              <input type="hidden" name="estado" value="completada">
              <button type="submit" style="font-size:.75rem;padding:4px 9px;border:1px solid #10B981;color:#10B981;background:none;border-radius:6px;cursor:pointer">
                ✓ Completar
              </button>
            </form>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>rest-reserva/eliminar/<?= $r['id'] ?>" onclick="return confirm('¿Eliminar esta solicitud?')"
               style="font-size:.75rem;color:#EF4444;text-decoration:none">Eliminar</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ══ SECCIÓN 2: Reservaciones del restaurante ══════════════ -->
<div>
  <div style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:10px">🏠 Reservaciones del restaurante</div>
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:.875rem">
      <thead>
        <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Nombre</th>
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Fecha / Hora</th>
          <th style="padding:10px 14px;text-align:center;font-weight:600;color:#374151">Pax</th>
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Mesa</th>
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Mesero</th>
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Estado</th>
          <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151">Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($_delRestaurante as $r): ?>
      <tr style="border-bottom:1px solid #F3F4F6">
        <td style="padding:11px 14px;font-weight:500"><?= htmlspecialchars($r['nombre']) ?></td>
        <td style="padding:11px 14px">
          <div><?= date('d/m/Y', strtotime($r['fecha'])) ?></div>
          <div style="color:#6B7280;font-size:.85rem"><?= substr($r['hora'],0,5) ?></div>
        </td>
        <td style="padding:11px 14px;text-align:center"><?= (int)$r['personas'] ?></td>
        <td style="padding:11px 14px;color:#6B7280"><?= htmlspecialchars($r['mesa_nombre'] ?? '—') ?></td>
        <td style="padding:11px 14px;color:#6B7280;font-size:.85rem"><?= htmlspecialchars($r['mesero_nombre'] ?? '—') ?></td>
        <td style="padding:11px 14px"><?= $_badge($r['estado']) ?></td>
        <td style="padding:11px 14px">
          <div style="display:flex;gap:8px;align-items:center">
            <form method="POST" action="<?= BASE_URL ?>rest-reserva/cambiarEstado/<?= $r['id'] ?>" style="display:inline">
              <input type="hidden" name="estado" value="<?= $r['estado'] === 'pendiente' ? 'confirmada' : 'completada' ?>">
              <button type="submit" style="font-size:.75rem;padding:3px 10px;border:1px solid #10B981;color:#10B981;background:none;border-radius:6px;cursor:pointer">
                <?= $r['estado'] === 'pendiente' ? 'Confirmar' : 'Completar' ?>
              </button>
            </form>
            <a href="<?= BASE_URL ?>rest-reserva/eliminar/<?= $r['id'] ?>" onclick="return confirm('¿Eliminar?')"
               style="font-size:.75rem;color:#EF4444;text-decoration:none">Eliminar</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($_delRestaurante)): ?>
      <tr><td colspan="7" style="padding:28px;text-align:center;color:#9CA3AF">No hay reservaciones del restaurante.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Modal nueva reservación ─────────────────────────────── -->
<div id="modalRes" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:460px;max-width:95vw;max-height:90vh;overflow-y:auto">
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
          <label style="font-size:.85rem;font-weight:500">Mesa</label>
          <select name="mesa_id" id="selectMesaRes" onchange="previewMesero(this.value)"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
            <option value="">— Sin mesa asignada —</option>
            <?php foreach ($mesas as $m): ?>
            <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?> (<?= (int)$m['capacidad'] ?> personas)</option>
            <?php endforeach; ?>
          </select>
          <div id="meseroPreview" style="margin-top:6px;font-size:.8rem;color:#6B7280;min-height:18px"></div>
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

<!-- ── Modal asignar mesa/mesero (solicitudes comensal) ─────── -->
<div id="modalAsignar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:400px;max-width:95vw">
    <h3 style="font-weight:700;margin-bottom:4px">🪑 Asignar mesa y mesero</h3>
    <p id="asignarNombre" style="font-size:.85rem;color:#6B7280;margin:0 0 18px"></p>
    <form method="POST" id="formAsignar" action="">
      <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:4px">Mesa</label>
      <select name="mesa_id" id="selectAsignarMesa" onchange="previewMeseroAsignar(this.value)"
        style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-bottom:6px;font-size:.9rem">
        <option value="">— Sin mesa —</option>
        <?php foreach ($mesas as $m): ?>
        <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?> (<?= (int)$m['capacidad'] ?> personas)</option>
        <?php endforeach; ?>
      </select>
      <div id="asignarMeseroPreview" style="font-size:.8rem;color:#6B7280;min-height:18px;margin-bottom:14px"></div>

      <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:4px">
        Mesero <span style="font-weight:400;color:#9CA3AF">(opcional — auto por zona)</span>
      </label>
      <select name="mesero_id" id="selectAsignarMesero"
        style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-bottom:20px;font-size:.9rem">
        <option value="">— Auto (según zona) —</option>
        <?php foreach ($_meserosTurno as $ms): ?>
        <option value="<?= (int)$ms['id'] ?>"><?= htmlspecialchars($ms['nombre']) ?></option>
        <?php endforeach; ?>
      </select>

      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('modalAsignar').style.display='none'"
          style="padding:8px 16px;border:1px solid #D1D5DB;border-radius:8px;cursor:pointer;background:#fff;font-size:.875rem">
          Cancelar
        </button>
        <button type="submit"
          style="padding:8px 20px;background:#1D4ED8;color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer">
          Confirmar y asignar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── Modal QR ─────────────────────────────────────────────── -->
<div id="modalQr" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px 24px;width:340px;max-width:94vw;text-align:center">
    <h3 style="font-weight:700;margin-bottom:6px">📱 QR de reservaciones</h3>
    <p style="font-size:.82rem;color:#6B7280;margin-bottom:16px">Muéstralo en tu local para que los comensales reserven sin app</p>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= urlencode($_qrUrl) ?>&ecc=M"
         alt="QR Reservaciones" style="border:1px solid #E5E7EB;border-radius:10px;padding:8px;width:220px;height:220px">
    <div style="margin-top:12px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:8px 10px;font-size:.72rem;color:#6B7280;word-break:break-all">
      <?= htmlspecialchars($_qrUrl) ?>
    </div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:center">
      <a href="<?= htmlspecialchars($_qrUrl) ?>" target="_blank"
         style="padding:8px 16px;background:#F3F4F6;color:#374151;border-radius:8px;font-size:.83rem;font-weight:600;text-decoration:none">
        Abrir enlace
      </a>
      <button onclick="document.getElementById('modalQr').style.display='none'"
        style="padding:8px 16px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.83rem;font-weight:600;cursor:pointer">
        Cerrar
      </button>
    </div>
  </div>
</div>

<script>
const BASE_RES = '<?= BASE_URL ?>';

function previewMesero(mesaId) {
  const el = document.getElementById('meseroPreview');
  if (!mesaId) { el.textContent = ''; return; }
  el.textContent = 'Buscando mesero de turno…';
  fetch(BASE_RES + 'rest-reserva/meseroDeZona/' + mesaId)
    .then(r => r.json())
    .then(d => {
      if (d.ok && d.mesero) {
        el.innerHTML = '🧑‍💼 Se auto-asignará a: <strong>' + d.mesero.nombre + '</strong>';
        el.style.color = '#1D4ED8';
      } else {
        el.textContent = 'Sin mesero de turno en esta zona hoy.';
        el.style.color = '#9CA3AF';
      }
    })
    .catch(() => { el.textContent = ''; });
}

function abrirAsignar(id, nombre) {
  document.getElementById('formAsignar').action = BASE_RES + 'rest-reserva/asignar/' + id;
  document.getElementById('asignarNombre').textContent = 'Reservación de: ' + nombre;
  document.getElementById('selectAsignarMesa').value    = '';
  document.getElementById('selectAsignarMesero').value  = '';
  document.getElementById('asignarMeseroPreview').textContent = '';
  document.getElementById('modalAsignar').style.display = 'flex';
}

function previewMeseroAsignar(mesaId) {
  const el = document.getElementById('asignarMeseroPreview');
  if (!mesaId) { el.textContent = ''; return; }
  el.textContent = 'Buscando mesero de turno…';
  fetch(BASE_RES + 'rest-reserva/meseroDeZona/' + mesaId)
    .then(r => r.json())
    .then(d => {
      if (d.ok && d.mesero) {
        el.innerHTML = '🧑‍💼 Auto-asignar a: <strong>' + d.mesero.nombre + '</strong>';
        el.style.color = '#1D4ED8';
      } else {
        el.textContent = 'Sin mesero de turno en esta zona hoy.';
        el.style.color = '#9CA3AF';
      }
    })
    .catch(() => { el.textContent = ''; });
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';

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
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Mesero</th>
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
        <td style="padding:12px 16px;color:#6B7280;font-size:.85rem"><?= htmlspecialchars($r['mesero_nombre'] ?? '—') ?></td>
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
      <tr><td colspan="8" style="padding:32px;text-align:center;color:#9CA3AF">No hay reservaciones.</td></tr>
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
          <label style="font-size:.85rem;font-weight:500">Mesa</label>
          <select name="mesa_id" id="selectMesaRes" onchange="previewMesero(this.value)"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
            <option value="">— Sin mesa asignada —</option>
            <?php foreach ($mesas as $m): ?>
            <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?> (<?= (int)$m['capacidad'] ?> personas)</option>
            <?php endforeach; ?>
          </select>
          <div id="meseroPreview" style="margin-top:6px;font-size:.8rem;color:#6B7280;min-height:18px"></div>
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
<script>
const BASE_RES = '<?= BASE_URL ?>';
function previewMesero(mesaId) {
  const el = document.getElementById('meseroPreview');
  if (!mesaId) { el.textContent = ''; return; }
  el.textContent = 'Buscando mesero de turno…';
  fetch(BASE_RES + 'rest-reserva/meseroDeZona/' + mesaId)
    .then(r => r.json())
    .then(d => {
      if (d.ok && d.mesero) {
        el.innerHTML = '🧑‍💼 Se auto-asignará a: <strong>' + d.mesero.nombre + '</strong>';
        el.style.color = '#1D4ED8';
      } else {
        el.textContent = 'Sin mesero de turno en esta zona hoy.';
        el.style.color = '#9CA3AF';
      }
    })
    .catch(() => { el.textContent = ''; });
}
</script>

<!-- Modal QR -->
<div id="modalQr" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px 24px;width:340px;max-width:94vw;text-align:center">
    <h3 style="font-weight:700;margin-bottom:6px">📱 QR de reservaciones</h3>
    <p style="font-size:.82rem;color:#6B7280;margin-bottom:16px">Muéstralo en tu local para que los comensales reserven sin app</p>
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= urlencode($_qrUrl) ?>&ecc=M"
         alt="QR Reservaciones" style="border:1px solid #E5E7EB;border-radius:10px;padding:8px;width:220px;height:220px">
    <div style="margin-top:12px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:8px 10px;font-size:.72rem;color:#6B7280;word-break:break-all">
      <?= htmlspecialchars($_qrUrl) ?>
    </div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:center">
      <a href="<?= htmlspecialchars($_qrUrl) ?>" target="_blank"
         style="padding:8px 16px;background:#F3F4F6;color:#374151;border-radius:8px;font-size:.83rem;font-weight:600;text-decoration:none">
        Abrir enlace
      </a>
      <button onclick="document.getElementById('modalQr').style.display='none'"
        style="padding:8px 16px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.83rem;font-weight:600;cursor:pointer">
        Cerrar
      </button>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
