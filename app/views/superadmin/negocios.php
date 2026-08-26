<?php ob_start(); ?>
<?php $fmt = static fn($v) => '$' . number_format((float)$v, 2); ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px">
  <h1 style="font-size:1.3rem;font-weight:800;margin:0">Negocios</h1>
  <a href="<?= BASE_URL ?>superadmin/nuevoNegocio" style="background:#A97C3F;color:#fff;padding:9px 16px;border-radius:8px;text-decoration:none;font-size:.85rem;font-weight:700">+ Nuevo negocio</a>
</div>

<form method="GET" style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap">
  <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre..."
         style="flex:1;min-width:220px;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.85rem">
  <select name="estado" style="padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.85rem">
    <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
    <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
    <option value="suspendidos" <?= $estado === 'suspendidos' ? 'selected' : '' ?>>Suspendidos</option>
  </select>
  <button type="submit" style="background:#111827;color:#fff;border:none;padding:9px 16px;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer">Filtrar</button>
</form>

<div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <table style="width:100%;border-collapse:collapse;font-size:.87rem">
    <thead>
      <tr style="text-align:left;color:#6B7280;font-size:.72rem;text-transform:uppercase;background:#F9FAFB">
        <th style="padding:10px 16px">Negocio</th>
        <th style="padding:10px 16px">Empresa</th>
        <th style="padding:10px 16px;text-align:right">Menú</th>
        <th style="padding:10px 16px;text-align:right">Admins</th>
        <th style="padding:10px 16px;text-align:right">Ventas totales</th>
        <th style="padding:10px 16px">Estado</th>
        <th style="padding:10px 16px">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($negocios as $n): ?>
      <tr style="border-top:1px solid #F3F4F6">
        <td style="padding:10px 16px;font-weight:600"><?= htmlspecialchars($n['nombre']) ?>
          <div style="font-size:.72rem;color:#9CA3AF;font-weight:400"><?= htmlspecialchars($n['slug']) ?></div>
        </td>
        <td style="padding:10px 16px;color:#6B7280"><?= htmlspecialchars($n['empresa_nombre'] ?? '—') ?></td>
        <td style="padding:10px 16px;text-align:right"><?= (int)$n['num_platillos'] ?></td>
        <td style="padding:10px 16px;text-align:right"><?= (int)$n['num_admins'] ?></td>
        <td style="padding:10px 16px;text-align:right;font-weight:600"><?= $fmt($n['ventas_totales']) ?></td>
        <td style="padding:10px 16px">
          <span style="font-size:.72rem;font-weight:700;padding:3px 9px;border-radius:99px;background:<?= $n['activo'] ? '#D1FAE5' : '#FEE2E2' ?>;color:<?= $n['activo'] ? '#065F46' : '#991B1B' ?>">
            <?= $n['activo'] ? 'Activo' : 'Suspendido' ?>
          </span>
        </td>
        <td style="padding:10px 16px;white-space:nowrap">
          <a href="<?= BASE_URL ?>superadmin/toggleActivo/<?= (int)$n['id'] ?>"
             onclick="return confirm('<?= $n['activo'] ? '¿Suspender' : '¿Reactivar' ?> <?= htmlspecialchars($n['nombre'], ENT_QUOTES) ?>?')"
             style="font-size:.78rem;font-weight:600;color:<?= $n['activo'] ? '#DC2626' : '#059669' ?>;text-decoration:none">
            <?= $n['activo'] ? 'Suspender' : 'Reactivar' ?>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($negocios)): ?>
      <tr><td colspan="7" style="padding:30px;text-align:center;color:#9CA3AF">Sin negocios que coincidan.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
$content = ob_get_clean();
$activeMenu = 'negocios';
require ROOT_PATH . '/app/views/superadmin/layout.php';
