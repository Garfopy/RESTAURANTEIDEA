<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
?>
<div style="margin-bottom:16px">
  <a href="<?= BASE_URL ?>cliente/index" style="font-size:.875rem;color:#6B7280;text-decoration:none">← Clientes</a>
</div>

<!-- Header -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:12px;flex-wrap:wrap">
  <div style="display:flex;align-items:center;gap:14px">
    <div style="width:52px;height:52px;border-radius:50%;background:#FEE2E2;color:#C8102E;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.3rem">
      <?= strtoupper(substr($empresa['razon_social'],0,1)) ?>
    </div>
    <div>
      <h1 style="font-size:1.25rem;font-weight:700;margin:0"><?= htmlspecialchars($empresa['razon_social']) ?></h1>
      <div style="font-size:.8rem;color:#6B7280"><?= $empresa['rfc'] ?></div>
      <span class="badge <?= $empresa['activo'] ? 'badge-success' : 'badge-danger' ?>" style="margin-top:4px">
        <?= $empresa['activo'] ? 'Activo' : 'Inactivo' ?>
      </span>
    </div>
  </div>
  <div style="display:flex;gap:8px">
    <a href="<?= BASE_URL ?>cliente/editar/<?= $empresa['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>
    <a href="<?= BASE_URL ?>pedido/index?empresa_id=<?= $empresa['id'] ?>" class="btn btn-primary btn-sm">Ver pedidos</a>
  </div>
</div>

<!-- Tabs -->
<div style="display:flex;border-bottom:2px solid #E5E7EB;margin-bottom:20px;gap:0" id="tabs">
  <?php foreach (['informacion'=>'Información','sucursales'=>'Sucursales (' . count($empresa['sucursales']) . ')','credito'=>'Crédito','historial'=>'Historial'] as $tab=>$label): ?>
  <button onclick="showTab('<?= $tab ?>')" id="tab-<?= $tab ?>"
    style="padding:10px 16px;font-size:.875rem;font-weight:600;background:none;border:none;cursor:pointer;color:#6B7280;border-bottom:2px solid transparent;margin-bottom:-2px">
    <?= $label ?>
  </button>
  <?php endforeach; ?>
</div>

<!-- Tab: Información -->
<div id="panel-informacion" class="tab-panel">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="card">
      <div class="card-title" style="margin-bottom:14px">Datos Fiscales</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:.875rem">
        <div><div style="color:#6B7280;font-size:.75rem">Razón Social</div><div style="font-weight:600"><?= htmlspecialchars($empresa['razon_social']) ?></div></div>
        <div><div style="color:#6B7280;font-size:.75rem">RFC</div><div style="font-weight:600"><?= $empresa['rfc'] ?></div></div>
        <div><div style="color:#6B7280;font-size:.75rem">Régimen Fiscal</div><div><?= htmlspecialchars($empresa['regimen_fiscal'] ?? '—') ?></div></div>
        <div><div style="color:#6B7280;font-size:.75rem">Fecha de Registro</div><div><?= $empresa['fecha_registro'] ?? '—' ?></div></div>
      </div>
      <?php if ($empresa['direccion_fiscal']): ?>
      <div style="margin-top:12px;padding-top:12px;border-top:1px solid #F3F4F6;font-size:.875rem">
        <div style="color:#6B7280;font-size:.75rem;margin-bottom:4px">Dirección Fiscal</div>
        <div><?= htmlspecialchars($empresa['direccion_fiscal']) ?></div>
      </div>
      <?php endif; ?>
    </div>
    <div class="card">
      <div class="card-title" style="margin-bottom:14px">Información Comercial</div>
      <div style="display:grid;gap:10px;font-size:.875rem">
        <div><div style="color:#6B7280;font-size:.75rem">Email</div><div><?= htmlspecialchars($empresa['email'] ?? '—') ?></div></div>
        <div><div style="color:#6B7280;font-size:.75rem">Teléfono</div><div><?= $empresa['telefono'] ?? '—' ?></div></div>
        <div><div style="color:#6B7280;font-size:.75rem">Método de pago preferido</div><div style="text-transform:capitalize"><?= $empresa['metodo_pago_preferido'] ?></div></div>
      </div>
    </div>
  </div>
</div>

<!-- Tab: Sucursales -->
<div id="panel-sucursales" class="tab-panel" style="display:none">
  <div class="card" style="padding:0">
    <div class="table-container">
      <table>
        <thead><tr><th>Nombre</th><th>Dirección</th><th>Contacto</th><th>Estado</th></tr></thead>
        <tbody>
          <?php foreach ($empresa['sucursales'] as $s): ?>
          <tr>
            <td style="font-weight:600"><?= htmlspecialchars($s['nombre']) ?></td>
            <td style="font-size:.8rem;color:#6B7280"><?= htmlspecialchars($s['direccion']) ?></td>
            <td style="font-size:.8rem"><?= htmlspecialchars($s['contacto_nombre'] ?? '') ?><br><?= $s['contacto_telefono'] ?? '' ?></td>
            <td><span class="badge <?= $s['activo'] ? 'badge-success':'badge-gray' ?>"><?= $s['activo']?'Activa':'Inactiva' ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Tab: Crédito -->
<div id="panel-credito" class="tab-panel" style="display:none">
  <div class="card" style="max-width:400px">
    <div class="card-title" style="margin-bottom:16px">Crédito</div>
    <div style="display:grid;gap:12px;font-size:.875rem">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <span>Estado:</span>
        <span class="badge <?= $empresa['credito_activo'] ? 'badge-success':'badge-danger' ?>">
          <?= $empresa['credito_activo'] ? 'Activo':'Desactivado' ?>
        </span>
      </div>
      <div style="display:flex;justify-content:space-between">
        <span>Límite de crédito:</span>
        <strong>$<?= number_format($empresa['limite_credito'],2) ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between">
        <span>Saldo actual:</span>
        <strong>$<?= number_format($empresa['saldo_credito'],2) ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between">
        <span>Días de crédito:</span>
        <strong><?= $empresa['dias_credito'] ?> días</strong>
      </div>
    </div>
    <div style="margin-top:16px;display:flex;gap:8px">
      <button onclick="toggleCredito(<?= $empresa['id'] ?>)"
        class="btn <?= $empresa['credito_activo'] ? 'btn-danger':'btn-success' ?> btn-sm">
        <?= $empresa['credito_activo'] ? 'Desactivar crédito':'Activar crédito' ?>
      </button>
    </div>
  </div>
</div>

<!-- Tab: Historial -->
<div id="panel-historial" class="tab-panel" style="display:none">
  <div class="card" style="padding:0">
    <div class="table-container">
      <table>
        <thead><tr><th>Folio</th><th>Fecha</th><th>Total</th><th>Estado</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($pedidos['data'] as $p): ?>
          <tr>
            <td style="font-weight:600;font-size:.875rem"><?= $p['folio'] ?></td>
            <td style="font-size:.8rem"><?= $p['fecha_pedido'] ?></td>
            <td style="font-weight:600">$<?= number_format($p['total'],2) ?></td>
            <td><?php
              $ec=['pendiente'=>'badge-warning','confirmado'=>'badge-blue','en_preparacion'=>'badge-orange','en_ruta'=>'badge-info','entregado'=>'badge-success','cancelado'=>'badge-danger'];
              $eel=['pendiente'=>'Pendiente','confirmado'=>'Confirmado','en_preparacion'=>'En preparación','en_ruta'=>'En ruta','entregado'=>'Entregado','cancelado'=>'Cancelado'];
            ?><span class="badge <?= $ec[$p['estado']] ?? 'badge-gray' ?>"><?= $eel[$p['estado']] ?? $p['estado'] ?></span></td>
            <td><a href="<?= BASE_URL ?>pedido/detalle/<?= $p['id'] ?>" class="btn btn-sm btn-secondary">Ver</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function showTab(name) {
  document.querySelectorAll('.tab-panel').forEach(p => p.style.display='none');
  document.querySelectorAll('#tabs button').forEach(b => { b.style.color='#6B7280'; b.style.borderBottomColor='transparent'; });
  document.getElementById('panel-'+name).style.display='block';
  const btn = document.getElementById('tab-'+name);
  btn.style.color='#C8102E';
  btn.style.borderBottomColor='#C8102E';
}
showTab('informacion');

function toggleCredito(id) {
  if (!confirm('¿Deseas cambiar el estado del crédito?')) return;
  fetch(`<?= BASE_URL ?>cliente/activarCredito/${id}`, {method:'POST'})
    .then(r=>r.json()).then(d=>{ if(d.ok) location.reload(); });
}
</script>
<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
