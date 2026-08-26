<?php ob_start(); ?>

<style>
.sa-back { color: #64748B; font-size: .82rem; text-decoration: none; }
.sa-back:hover { color: #1E293B; }
.sa-header { align-items: flex-start; display: flex; justify-content: space-between; margin: 8px 0 18px; }
.sa-title { color: #0F172A; font-size: 1.45rem; font-weight: 800; margin: 0; }
.sa-copy { color: #64748B; font-size: .9rem; margin: 4px 0 0; }
.sa-badge {
  border-radius: 999px; display: inline-block; font-size: .72rem; font-weight: 700;
  padding: 3px 10px; text-transform: uppercase;
}
.sa-badge-pendiente  { background: #FEF3C7; color: #92400E; }
.sa-badge-activo     { background: #D1FAE5; color: #065F46; }
.sa-badge-suspendido { background: #FEE2E2; color: #991B1B; }
.sa-badge-baja       { background: #E5E7EB; color: #374151; }
.sa-badge-on  { background: #D1FAE5; color: #065F46; }
.sa-badge-off { background: #E5E7EB; color: #374151; }
.sa-kpi-grid { display: grid; gap: 14px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 20px; }
.sa-kpi { background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 14px 16px; }
.sa-kpi-label { color: #64748B; font-size: .74rem; font-weight: 600; text-transform: uppercase; }
.sa-kpi-value { color: #0F172A; font-size: 1.35rem; font-weight: 800; margin-top: 4px; }
.sa-cols { display: grid; gap: 16px; grid-template-columns: 1fr 1fr; }
.sa-panel { background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px 18px; }
.sa-panel h2 { color: #0F172A; font-size: 1rem; font-weight: 700; margin: 0 0 12px; }
.sa-dl { display: grid; gap: 8px; grid-template-columns: auto 1fr; font-size: .85rem; }
.sa-dl dt { color: #64748B; font-weight: 600; }
.sa-dl dd { color: #0F172A; margin: 0; }
.sa-btn {
  border: 1px solid #E2E8F0; border-radius: 8px; cursor: pointer; font-size: .78rem;
  font-weight: 600; padding: 6px 12px;
}
.sa-btn-primary { background: #1E293B; border-color: #1E293B; color: #fff; }
.sa-btn-approve { background: #059669; border-color: #059669; color: #fff; }
.sa-btn-suspend { background: #fff; color: #B91C1C; border-color: #FCA5A5; }
.sa-inline-form { align-items: center; display: flex; gap: 8px; }
.sa-inline-form select { border: 1px solid #E2E8F0; border-radius: 8px; font-size: .82rem; padding: 6px 10px; }
.sa-mini-table { font-size: .84rem; width: 100%; border-collapse: collapse; }
.sa-mini-table th { color: #64748B; font-size: .72rem; padding: 6px 8px; text-align: left; text-transform: uppercase; }
.sa-mini-table td { border-top: 1px solid #F1F5F9; padding: 7px 8px; }
.sa-empty { color: #94A3B8; font-size: .84rem; padding: 12px 0; }
</style>

<a class="sa-back" href="<?= BASE_URL ?>superadmin/negocios">← Volver a negocios</a>

<?php $estado = $negocio['estado_plataforma'] ?? ($negocio['activo'] ? 'activo' : 'baja'); ?>

<div class="sa-header">
  <div>
    <h1 class="sa-title"><?= htmlspecialchars($negocio['nombre']) ?></h1>
    <p class="sa-copy">
      <?= htmlspecialchars($negocio['empresa_nombre'] ?? 'Sin empresa') ?> ·
      <span class="sa-badge sa-badge-<?= htmlspecialchars($estado) ?>"><?= htmlspecialchars($estado) ?></span>
    </p>
  </div>
  <div style="display:flex;gap:8px">
    <?php if ($estado !== 'activo'): ?>
    <form method="post" action="<?= BASE_URL ?>superadmin/aprobar/<?= (int)$negocio['id'] ?>">
      <button type="submit" class="sa-btn sa-btn-approve">Aprobar</button>
    </form>
    <?php endif; ?>
    <?php if ($estado !== 'suspendido'): ?>
    <form method="post" action="<?= BASE_URL ?>superadmin/suspender/<?= (int)$negocio['id'] ?>"
          onsubmit="return confirm('¿Suspender este negocio? Dejará de aparecer en la app móvil.');">
      <button type="submit" class="sa-btn sa-btn-suspend">Suspender</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="sa-kpi-grid">
  <div class="sa-kpi">
    <div class="sa-kpi-label">Ventas del mes</div>
    <div class="sa-kpi-value">$<?= number_format((float)$ventas['ventas_mes'], 2) ?></div>
  </div>
  <div class="sa-kpi">
    <div class="sa-kpi-label">Ventas históricas</div>
    <div class="sa-kpi-value">$<?= number_format((float)$ventas['ventas_totales'], 2) ?></div>
  </div>
  <div class="sa-kpi">
    <div class="sa-kpi-label">Pedidos</div>
    <div class="sa-kpi-value"><?= (int)$ventas['pedidos_totales'] ?></div>
  </div>
  <div class="sa-kpi">
    <div class="sa-kpi-label">Ticket promedio</div>
    <div class="sa-kpi-value">$<?= number_format((float)$ventas['ticket_promedio'], 2) ?></div>
  </div>
</div>

<div class="sa-cols">
  <div class="sa-panel">
    <h2>Datos generales</h2>
    <dl class="sa-dl">
      <dt>Slug</dt><dd><?= htmlspecialchars($negocio['slug']) ?></dd>
      <dt>Teléfono</dt><dd><?= htmlspecialchars($negocio['telefono'] ?: '—') ?></dd>
      <dt>Dirección</dt><dd><?= htmlspecialchars($negocio['direccion'] ?: '—') ?></dd>
      <dt>Ubicación</dt>
      <dd>
        <?php if ($negocio['lat'] !== null && $negocio['lng'] !== null): ?>
          <?= htmlspecialchars($negocio['lat']) ?>, <?= htmlspecialchars($negocio['lng']) ?>
        <?php else: ?>
          <span style="color:#B45309">Sin capturar — el negocio no se asociará a puntos de referencia</span>
        <?php endif; ?>
      </dd>
      <dt>Horario</dt>
      <dd><?= htmlspecialchars(($negocio['horario_apertura'] ?? '—') . ' – ' . ($negocio['horario_cierre'] ?? '—')) ?></dd>
      <dt>App móvil</dt>
      <dd><span class="sa-badge <?= $negocio['app_movil_habilitada'] ? 'sa-badge-on' : 'sa-badge-off' ?>"><?= $negocio['app_movil_habilitada'] ? 'Habilitada' : 'Deshabilitada' ?></span></dd>
      <dt>Último pedido</dt>
      <dd><?= $ventas['ultimo_pedido'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($ventas['ultimo_pedido']))) : '—' ?></dd>
      <dt>Registrado</dt>
      <dd><?= htmlspecialchars(date('d/m/Y', strtotime($negocio['created_at']))) ?></dd>
    </dl>
    <div style="border-top:1px solid #F1F5F9;margin-top:14px;padding-top:12px">
      <a class="sa-btn sa-btn-primary" style="text-decoration:none"
         href="<?= BASE_URL ?>restaurante/seleccionar">Entrar como Admin para editar →</a>
    </div>
  </div>

  <div class="sa-panel">
    <h2>Plan y monetización</h2>
    <dl class="sa-dl">
      <dt>Plan actual</dt><dd><?= htmlspecialchars($negocio['plan_nombre'] ?? 'Sin plan') ?></dd>
      <dt>Comisión</dt><dd><?= $negocio['comision_pct'] !== null ? htmlspecialchars($negocio['comision_pct']) . '%' : '—' ?></dd>
      <dt>Cuota mensual</dt><dd><?= $negocio['cuota_mensual'] !== null ? '$' . number_format((float)$negocio['cuota_mensual'], 2) : '—' ?></dd>
    </dl>
    <?php if (!empty($planes)): ?>
    <form method="post" action="<?= BASE_URL ?>superadmin/asignarPlan/<?= (int)$negocio['id'] ?>"
          class="sa-inline-form" style="border-top:1px solid #F1F5F9;margin-top:14px;padding-top:12px">
      <select name="plan_id">
        <option value="">— Sin plan —</option>
        <?php foreach ($planes as $plan): ?>
        <option value="<?= (int)$plan['id'] ?>" <?= (int)($negocio['plan_id'] ?? 0) === (int)$plan['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($plan['nombre']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="sa-btn sa-btn-primary">Asignar plan</button>
    </form>
    <?php endif; ?>
    <p style="color:#94A3B8;font-size:.78rem;margin:12px 0 0">
      El modelo de comisión (% / cuota fija / híbrido) sigue pendiente de definir por el equipo,
      por eso el plan «Básico» está en 0.
    </p>
  </div>

  <div class="sa-panel">
    <h2>Staff del negocio (<?= count($staff) ?>)</h2>
    <?php if ($staff): ?>
    <table class="sa-mini-table">
      <thead><tr><th>Nombre</th><th>Rol</th><th>Estado</th></tr></thead>
      <tbody>
        <?php foreach ($staff as $s): ?>
        <tr>
          <td>
            <?= htmlspecialchars($s['nombre'] . ' ' . $s['apellido_paterno']) ?><br>
            <span style="color:#94A3B8;font-size:.76rem"><?= htmlspecialchars($s['email']) ?></span>
          </td>
          <td><?= htmlspecialchars($s['rol_nombre']) ?></td>
          <td><span class="sa-badge <?= $s['activo'] ? 'sa-badge-on' : 'sa-badge-off' ?>"><?= $s['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <p class="sa-empty">Este negocio no tiene cuentas de staff.</p>
    <?php endif; ?>
  </div>

  <div class="sa-panel">
    <h2>Puntos de referencia cercanos (<?= count($puntos) ?>)</h2>
    <?php if ($puntos): ?>
    <table class="sa-mini-table">
      <thead><tr><th>Punto</th><th>Distancia</th><th>Origen</th></tr></thead>
      <tbody>
        <?php foreach ($puntos as $pt): ?>
        <tr>
          <td><?= htmlspecialchars($pt['nombre']) ?><?= $pt['ciudad'] ? ' · ' . htmlspecialchars($pt['ciudad']) : '' ?></td>
          <td><?= $pt['distancia_km'] !== null ? htmlspecialchars($pt['distancia_km']) . ' km' : '—' ?></td>
          <td><?= $pt['destacado_manual'] ? 'Manual' : 'Automático' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <p class="sa-empty">
      Sin puntos de referencia asociados.
      <?php if ($negocio['lat'] === null || $negocio['lng'] === null): ?>
      Captura primero la ubicación del negocio.
      <?php endif; ?>
    </p>
    <?php endif; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/superadmin/layouts/main.php';
