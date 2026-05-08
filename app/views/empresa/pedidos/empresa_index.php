<?php
$baseUrl = BASE_URL;
$estados = [
    'pendiente'      => ['label'=>'Pendiente',       'bg'=>'#FEF3C7','tx'=>'#92400E','dot'=>'#F59E0B'],
    'confirmado'     => ['label'=>'Confirmado',       'bg'=>'#DBEAFE','tx'=>'#1E40AF','dot'=>'#3B82F6'],
    'en_preparacion' => ['label'=>'En preparación',  'bg'=>'#EDE9FE','tx'=>'#5B21B6','dot'=>'#8B5CF6'],
    'en_ruta'        => ['label'=>'En ruta',           'bg'=>'#E0F2FE','tx'=>'#075985','dot'=>'#0EA5E9'],
    'entregado'      => ['label'=>'Entregado',         'bg'=>'#D1FAE5','tx'=>'#065F46','dot'=>'#10B981'],
    'cancelado'      => ['label'=>'Cancelado',         'bg'=>'#FEE2E2','tx'=>'#991B1B','dot'=>'#EF4444'],
];
?>
<style>
.pedidos-filter-bar {
  background: #fff;
  border: 1px solid #E5E7EB;
  border-radius: 14px;
  padding: 16px 20px;
  margin-bottom: 20px;
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
  box-shadow: 0 1px 4px rgba(0,0,0,.03);
}
.pedidos-input {
  flex: 1;
  min-width: 180px;
  padding: 9px 14px 9px 38px;
  border: 1.5px solid #E5E7EB;
  border-radius: 9px;
  font-size: .85rem;
  color: #111827;
  font-family: 'Inter', sans-serif;
  outline: none;
  transition: border-color .2s, box-shadow .2s;
  background: #F9FAFB url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='15' height='15' fill='none' viewBox='0 0 24 24' stroke='%239CA3AF' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat 12px center;
}
.pedidos-input:focus { border-color: #C8102E; box-shadow: 0 0 0 3px rgba(200,16,46,.09); background-color: #fff; }
.pedidos-select {
  padding: 9px 14px;
  border: 1.5px solid #E5E7EB;
  border-radius: 9px;
  font-size: .85rem;
  font-family: 'Inter', sans-serif;
  color: #374151;
  background: #F9FAFB;
  cursor: pointer;
  outline: none;
  transition: border-color .2s;
}
.pedidos-select:focus { border-color: #C8102E; background: #fff; }
.btn-filter {
  padding: 9px 20px;
  background: #111827;
  color: #fff;
  border: none;
  border-radius: 9px;
  font-size: .85rem;
  font-weight: 700;
  font-family: 'Inter', sans-serif;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background .15s;
}
.btn-filter:hover { background: #1F2937; }
.alert-banner {
  border-radius: 12px;
  padding: 14px 18px;
  margin-bottom: 14px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.alert-banner-icon {
  width: 36px; height: 36px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.pedidos-table-wrap {
  background: #fff;
  border: 1px solid #E5E7EB;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0,0,0,.03);
}
.pedidos-table { width: 100%; border-collapse: collapse; }
.pedidos-table thead tr { background: #F9FAFB; }
.pedidos-table th {
  padding: 11px 16px;
  text-align: left;
  font-size: .66rem;
  color: #6B7280;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  white-space: nowrap;
  border-bottom: 1px solid #E5E7EB;
}
.pedidos-table th.right { text-align: right; }
.pedidos-table th.center { text-align: center; }
.pedidos-table tbody tr {
  border-bottom: 1px solid #F3F4F6;
  transition: background .1s;
}
.pedidos-table tbody tr:hover { background: #FAFAFA; }
.pedidos-table td { padding: 8px 14px; }
.pedidos-table td.right { text-align: right; }
.pedidos-table td.center { text-align: center; }
.estado-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: .68rem;
  font-weight: 700;
  white-space: nowrap;
}
.btn-action {
  padding: 5px 11px;
  border-radius: 7px;
  font-size: .72rem;
  font-weight: 700;
  font-family: 'Inter', sans-serif;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: opacity .15s, transform .1s;
  border: none;
}
.btn-action:hover { opacity: .88; transform: translateY(-1px); }
.btn-action:active { transform: translateY(0); }
.pagination { display: flex; justify-content: center; gap: 5px; padding: 16px; border-top: 1px solid #F3F4F6; }
.page-link {
  padding: 6px 13px;
  border-radius: 7px;
  font-size: .82rem;
  font-weight: 600;
  text-decoration: none;
  color: #374151;
  background: #F3F4F6;
  transition: background .15s;
}
.page-link:hover { background: #E5E7EB; }
.page-link.active { background: #C8102E; color: #fff; }
</style>

<!-- Banners de alertas -->
<?php if ($countPendientes > 0): ?>
<div class="alert-banner" style="background:#FFFBEB;border:1px solid #FCD34D">
  <div class="alert-banner-icon" style="background:#FEF3C7">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#D97706" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
  </div>
  <div>
    <strong style="font-size:.875rem;color:#92400E"><?= $countPendientes ?> pedido<?= $countPendientes > 1 ? 's' : '' ?> pendiente<?= $countPendientes > 1 ? 's' : '' ?> de revisión</strong>
    <div style="font-size:.78rem;color:#B45309;margin-top:2px">Revisa cada pedido, ajusta precios si es necesario, y apruébalo o recházalo.</div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($countConComprobante) && $countConComprobante > 0): ?>
<div class="alert-banner" style="background:#ECFDF5;border:1px solid #A7F3D0">
  <div class="alert-banner-icon" style="background:#D1FAE5">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
  </div>
  <div>
    <strong style="font-size:.875rem;color:#065F46"><?= $countConComprobante ?> pedido<?= $countConComprobante > 1 ? 's' : '' ?> con comprobante adjunto</strong>
    <div style="font-size:.78rem;color:#047857;margin-top:2px">Revisa el comprobante en el detalle del pedido y procesa la entrega.</div>
  </div>
</div>
<?php endif; ?>

<!-- Barra de filtros -->
<form method="GET" class="pedidos-filter-bar">
  <input type="text" name="buscar" class="pedidos-input"
         placeholder="Buscar folio o comprador..."
         value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>">
  <select name="estado" class="pedidos-select">
    <option value="">Todos los estados</option>
    <?php foreach ($estados as $k => $v): ?>
    <option value="<?= $k ?>" <?= ($filtros['estado'] ?? '') === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
    <?php endforeach; ?>
  </select>
  <select name="tipo" class="pedidos-select">
    <option value="">Todos los tipos</option>
    <option value="normal" <?= ($filtros['tipo'] ?? '') === 'normal' ? 'selected' : '' ?>>Normal</option>
    <option value="personalizado" <?= ($filtros['tipo'] ?? '') === 'personalizado' ? 'selected' : '' ?>>Personalizado</option>
  </select>
  <button type="submit" class="btn-filter">
    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
    Filtrar
  </button>
</form>

<!-- Tabla -->
<div class="pedidos-table-wrap">
  <?php if (empty($items)): ?>
    <div style="padding:56px;text-align:center">
      <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#D1D5DB" stroke-width="1.2" style="margin:0 auto 12px;display:block"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      <p style="color:#9CA3AF;font-size:.875rem">Sin pedidos para los filtros seleccionados.</p>
    </div>
  <?php else: ?>
  <table class="pedidos-table">
    <thead>
      <tr>
        <th>Folio</th>
        <th>Comprador</th>
        <th class="center">Estado</th>
        <th class="right">Total</th>
        <th class="center">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $p): ?>
      <?php
        $est = $estados[$p['estado']] ?? ['label'=>$p['estado'],'bg'=>'#F3F4F6','tx'=>'#374151','dot'=>'#9CA3AF'];
        $esPendiente = $p['estado'] === 'pendiente';
        $esPersonalizado = ($p['tipo'] ?? 'normal') === 'personalizado';
        $tieneComprobante = !empty($p['foto_comprobante_path']);
      ?>
      <tr style="<?= $esPendiente ? 'background:#FEFCE8' : '' ?>">
        <td>
          <div style="font-weight:700;font-size:.84rem;color:#111827;font-family:monospace;letter-spacing:.01em"><?= htmlspecialchars($p['folio']) ?></div>
          <div style="font-size:.7rem;color:#9CA3AF;margin-top:2px"><?= date('d/m/Y', strtotime($p['created_at'])) ?></div>
          <?php if ($esPersonalizado): ?>
          <span style="display:inline-block;margin-top:3px;padding:1px 7px;border-radius:999px;background:#F3E8FF;color:#6B21A8;font-size:.62rem;font-weight:700;letter-spacing:.03em">Personalizado</span>
          <?php endif; ?>
        </td>
        <td style="font-size:.84rem;color:#374151;font-weight:500">
          <?= htmlspecialchars($p['comprador_nombre'] . ' ' . $p['comprador_apellido']) ?>
        </td>
        <td class="center">
          <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
            <span class="estado-badge" style="background:<?= $est['bg'] ?>;color:<?= $est['tx'] ?>">
              <span style="width:6px;height:6px;border-radius:50%;background:<?= $est['dot'] ?>;flex-shrink:0"></span>
              <?= $est['label'] ?>
            </span>
            <?php if (!empty($p['tipo_entrega'])): ?>
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:.68rem;color:#9CA3AF;font-weight:600">
              <?php if ($p['tipo_entrega'] === 'pickup'): ?>
                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3"/></svg>Pickup
              <?php else: ?>
                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>Repartidor
              <?php endif; ?>
            </span>
            <?php endif; ?>
            <?php if ($tieneComprobante && in_array($p['estado'], ['en_preparacion','confirmado'], true)): ?>
            <span class="estado-badge" style="background:#D1FAE5;color:#065F46">
              <svg width="9" height="9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
              Comprobante
            </span>
            <?php endif; ?>
          </div>
        </td>
        <td class="right">
          <div style="font-size:.9rem;font-weight:800;color:#111827">$<?= number_format((float)$p['total'], 2) ?></div>
          <?php if (($p['costo_envio'] ?? 0) > 0): ?>
          <div style="font-size:.68rem;color:#9CA3AF;margin-top:1px">+ $<?= number_format($p['costo_envio'], 2) ?> envío</div>
          <?php endif; ?>
        </td>
        <td class="center">
          <div style="display:flex;justify-content:center;gap:5px;flex-wrap:wrap;align-items:center">
            <a href="<?= $baseUrl ?>pedido/detalle/<?= $p['id'] ?>" class="btn-action" style="background:#F3F4F6;color:#374151;border:1px solid #E5E7EB">
              <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              Ver
            </a>

            <?php if ($esPendiente): ?>
            <button onclick="abrirRevision(<?= htmlspecialchars(json_encode([
                'id'                => (int)$p['id'],
                'folio'             => $p['folio'],
                'comprador'         => $p['comprador_nombre'] . ' ' . $p['comprador_apellido'],
                'tipo_entrega'      => $p['tipo_entrega'] ?? '',
                'metodo_pago'       => $p['metodo_pago'] ?? '',
                'total'             => (float)$p['total'],
                'notas'             => $p['notas'] ?? '',
                'fecha_entrega'     => $p['fecha_entrega'] ?? '',
                'created_at'        => $p['created_at'] ?? '',
                'direccion_entrega' => $p['direccion_entrega'] ?? '',
                'referencia_entrega'=> $p['referencia_entrega'] ?? '',
            ]), ENT_QUOTES) ?>)" class="btn-action" style="background:#FEF3C7;color:#92400E;border:1px solid #FDE68A">
              <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              Revisar
            </button>

            <?php elseif ($tieneComprobante && in_array($p['estado'], ['confirmado','en_preparacion'], true)): ?>
            <form method="POST" action="<?= $baseUrl ?>empresa-pedido/cambiarEstado" style="display:inline"
                  onsubmit="return confirm('¿Confirmar el comprobante y continuar con la entrega?')">
              <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="estado" value="en_ruta">
              <button type="submit" class="btn-action" style="background:#DBEAFE;color:#1D4ED8;border:1px solid #BFDBFE">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Confirmar pago
              </button>
            </form>

            <?php elseif (!$tieneComprobante && $p['estado'] === 'confirmado'): ?>
            <span style="font-size:.68rem;color:#9CA3AF;font-style:italic;white-space:nowrap">Esperando comprobante...</span>

            <?php elseif ($p['estado'] === 'en_ruta' && ($p['tipo_entrega'] ?? '') === 'pickup'): ?>
            <form method="POST" action="<?= $baseUrl ?>empresa-pedido/cambiarEstado" style="display:inline"
                  onsubmit="return confirm('¿Confirmar que el comprador recogió el pedido?')">
              <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="estado" value="entregado">
              <button type="submit" class="btn-action" style="background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Recogido
              </button>
            </form>

            <?php elseif ($p['estado'] === 'en_ruta'): ?>
            <button onclick="abrirSubirFoto(<?= $p['id'] ?>)" class="btn-action" style="background:#059669;color:#fff;border:1px solid #059669">
              <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg>
              Foto entrega
            </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if (($paginacion['last_page'] ?? 1) > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $paginacion['last_page']; $i++): ?>
      <a href="?page=<?= $i ?>" class="page-link <?= $i === ($paginacion['current_page'] ?? 1) ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- ── Modal: Revisar pedido ─────────────────────────────────────────────── -->
<div id="modalRevision" style="display:none;position:fixed;inset:0;background:rgba(15,20,30,.55);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
  <div style="background:#fff;border-radius:16px;padding:28px;width:680px;max-width:96vw;max-height:92vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,.25)">

    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px">
      <div>
        <h3 style="font-size:1.05rem;font-weight:800;color:#111827;margin:0 0 3px">Revisar pedido</h3>
        <span id="revFolioDisplay" style="font-family:monospace;font-size:.85rem;color:#C8102E;font-weight:700"></span>
      </div>
      <button onclick="document.getElementById('modalRevision').style.display='none'"
              style="width:30px;height:30px;background:#F3F4F6;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6B7280;font-size:.9rem">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Info del pedido -->
    <div style="background:#F9FAFB;border-radius:11px;padding:16px 18px;margin-bottom:16px;border:1px solid #F3F4F6">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 20px">
        <div>
          <div style="font-size:.65rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Comprador</div>
          <div id="revCompradorDisplay" style="font-size:.875rem;font-weight:600;color:#111827"></div>
        </div>
        <div>
          <div style="font-size:.65rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Fecha del pedido</div>
          <div id="revFechaDisplay" style="font-size:.875rem;font-weight:600;color:#374151"></div>
        </div>
        <div>
          <div style="font-size:.65rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Tipo de entrega</div>
          <div id="revTipoEntregaDisplay" style="font-size:.875rem;font-weight:700"></div>
        </div>
        <div>
          <div style="font-size:.65rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Método de pago</div>
          <div id="revMetodoPagoDisplay" style="font-size:.875rem;font-weight:600;color:#374151"></div>
        </div>
        <div id="revFechaEntregaBox" style="display:none">
          <div style="font-size:.65rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Fecha entrega solicitada</div>
          <div id="revFechaEntregaDisplay" style="font-size:.875rem;font-weight:600;color:#374151"></div>
        </div>
        <div id="revDireccionBox" style="grid-column:1/-1;display:none;padding-top:10px;border-top:1px solid #E5E7EB;margin-top:4px">
          <div style="font-size:.65rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Dirección de entrega</div>
          <div id="revDireccionDisplay" style="font-size:.85rem;color:#374151;font-weight:500"></div>
          <div id="revReferenciaDisplay" style="font-size:.78rem;color:#6B7280;margin-top:2px"></div>
        </div>
      </div>
    </div>

    <!-- Notas comprador -->
    <div id="revNotasBox" style="display:none;margin-bottom:14px;padding:11px 14px;background:#FFFBEB;border:1px solid #FCD34D;border-radius:9px">
      <div style="font-size:.65rem;color:#92400E;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:3px">Notas del comprador</div>
      <div id="revNotasDisplay" style="font-size:.85rem;color:#78350F;white-space:pre-line"></div>
    </div>

    <!-- Guía -->
    <div id="revGuiaAdmin" style="margin-bottom:14px;padding:12px 14px;border-radius:9px;font-size:.84rem"></div>

    <hr style="border:none;border-top:1px solid #F3F4F6;margin:0 0 14px">

    <!-- Productos -->
    <div style="margin-bottom:14px">
      <div style="font-size:.84rem;font-weight:700;color:#111827;margin-bottom:10px">Productos del pedido</div>
      <div id="revProdLoading" style="font-size:.82rem;color:#9CA3AF;padding:12px 0">Cargando productos...</div>
      <div id="revProdTabla" style="display:none;overflow-x:auto;border-radius:8px;border:1px solid #F3F4F6"></div>
      <div id="revProdTotal" style="display:none;text-align:right;font-size:.95rem;font-weight:800;color:#C8102E;margin-top:8px;padding-top:8px;border-top:2px solid #E5E7EB"></div>
    </div>

    <!-- Ajuste precios -->
    <div id="preciosSection" style="display:none;margin-bottom:14px">
      <div style="font-size:.82rem;font-weight:700;color:#374151;margin-bottom:8px">
        Ajuste de precios <span style="font-size:.72rem;color:#9CA3AF;font-weight:400">— solo puedes bajar precios</span>
      </div>
      <div id="itemsContainer" style="font-size:.85rem;border:1px solid #F3F4F6;border-radius:9px;overflow:hidden"></div>
    </div>

    <hr style="border:none;border-top:1px solid #F3F4F6;margin:0 0 14px">

    <!-- Repartidor -->
    <div id="revAsignRepartidor" style="display:none;margin-bottom:14px">
      <div style="font-size:.84rem;font-weight:700;color:#111827;margin-bottom:10px">Asignar entrega</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label style="display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:5px">Repartidor asignado</label>
          <select id="revRepartidorSelect"
                  style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:.85rem;font-family:inherit;background:#fff;color:#111827">
            <option value="">— Sin asignar aún —</option>
            <?php foreach ($repartidores as $r): ?>
            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido_paterno']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:5px">Costo de envío ($)</label>
          <input type="number" id="revCostoEnvioInput" min="0" step="0.01" value="0"
                 style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:.85rem;font-family:inherit;box-sizing:border-box">
          <div style="font-size:.7rem;color:#9CA3AF;margin-top:3px">0 si está incluido en el precio.</div>
        </div>
      </div>
    </div>

    <!-- Nota empresa -->
    <div style="margin-bottom:14px">
      <label style="display:block;font-size:.78rem;font-weight:600;color:#374151;margin-bottom:5px">
        Nota para el comprador <span style="color:#9CA3AF;font-weight:400">(opcional)</span>
      </label>
      <textarea id="revNotaEmpresaInput" rows="2" placeholder="Ej: Tu pedido estará listo el jueves a las 10am..."
                style="width:100%;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:.875rem;resize:vertical;box-sizing:border-box;font-family:inherit;color:#111827"></textarea>
    </div>

    <!-- Aprobar -->
    <form method="POST" action="<?= $baseUrl ?>empresa-pedido/aprobar" id="formAprobar" style="margin-bottom:8px">
      <input type="hidden" name="pedido_id" class="syncPedidoId">
      <input type="hidden" name="tipo_entrega" id="hTipoEntrega">
      <input type="hidden" name="repartidor_asignado_id" id="hRepartidorId">
      <input type="hidden" name="costo_envio" id="hCostoEnvio">
      <input type="hidden" name="nota_empresa" id="hNotaEmpresa">
      <button type="submit" onclick="sincronizarEntrega()"
              style="width:100%;padding:12px;background:linear-gradient(135deg,#059669,#047857);color:#fff;border:none;border-radius:9px;font-weight:700;cursor:pointer;font-size:.9rem;font-family:inherit;letter-spacing:.01em;display:flex;align-items:center;justify-content:center;gap:7px">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Aprobar pedido
      </button>
    </form>

    <!-- Rechazar -->
    <form method="POST" action="<?= $baseUrl ?>empresa-pedido/rechazar" style="margin-bottom:8px">
      <input type="hidden" name="pedido_id" class="syncPedidoId">
      <div style="display:flex;gap:8px">
        <input type="text" name="nota_rechazo" placeholder="Motivo del rechazo..." required
               style="flex:1;padding:9px 12px;border:1.5px solid #FECACA;border-radius:8px;font-size:.84rem;font-family:inherit;color:#111827;outline:none" onfocus="this.style.borderColor='#EF4444'" onblur="this.style.borderColor='#FECACA'">
        <button type="submit"
                style="padding:9px 16px;background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;border-radius:8px;font-weight:700;cursor:pointer;font-size:.82rem;white-space:nowrap;font-family:inherit">
          Rechazar
        </button>
      </div>
    </form>

    <button onclick="document.getElementById('modalRevision').style.display='none'"
            style="width:100%;margin-top:4px;padding:9px;border:1.5px solid #E5E7EB;border-radius:9px;background:#fff;cursor:pointer;font-size:.84rem;color:#6B7280;font-family:inherit">
      Cancelar
    </button>
  </div>
</div>

<!-- ── Modal: Foto entrega ─────────────────────────────────────────────── -->
<div id="modalFotoEntrega" style="display:none;position:fixed;inset:0;background:rgba(15,20,30,.55);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
  <div style="background:#fff;border-radius:16px;padding:28px;width:400px;max-width:95vw;box-shadow:0 24px 60px rgba(0,0,0,.25)">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
      <div style="width:38px;height:38px;border-radius:10px;background:#ECFDF5;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg>
      </div>
      <h3 style="font-size:1rem;font-weight:800;color:#111827;margin:0">Foto de entrega</h3>
    </div>
    <form method="POST" action="<?= $baseUrl ?>empresa-pedido/subirFotoEntrega" enctype="multipart/form-data">
      <input type="hidden" name="pedido_id" id="fotoEntregaPedidoId">
      <input type="file" name="foto" accept="image/*" capture="environment" required
             style="width:100%;padding:9px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:.85rem;margin-bottom:8px;box-sizing:border-box">
      <div style="font-size:.75rem;color:#9CA3AF;margin-bottom:18px">JPG, PNG o WEBP. Al guardar, el pedido se marcará como <strong style="color:#374151">Entregado</strong>.</div>
      <div style="display:flex;gap:8px">
        <button type="submit"
                style="flex:1;padding:11px;background:#059669;color:#fff;border:none;border-radius:9px;font-weight:700;cursor:pointer;font-family:inherit">
          Guardar y marcar entregado
        </button>
        <button type="button" onclick="document.getElementById('modalFotoEntrega').style.display='none'"
                style="padding:11px 16px;border:1.5px solid #E5E7EB;border-radius:9px;background:#fff;cursor:pointer;font-family:inherit;color:#374151">
          Cancelar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const BASE_URL = '<?= $baseUrl ?>';
let _revData = null;

function abrirRevision(data) {
  _revData = data;
  document.querySelectorAll('.syncPedidoId').forEach(el => el.value = data.id);

  document.getElementById('revFolioDisplay').textContent = data.folio;
  document.getElementById('revCompradorDisplay').textContent = data.comprador;

  if (data.created_at) {
    try {
      const d = new Date(data.created_at.replace(' ', 'T'));
      document.getElementById('revFechaDisplay').textContent =
        d.toLocaleDateString('es-MX', {day:'2-digit', month:'2-digit', year:'numeric'}) + ' ' +
        d.toLocaleTimeString('es-MX', {hour:'2-digit', minute:'2-digit'});
    } catch(e) { document.getElementById('revFechaDisplay').textContent = data.created_at; }
  }

  const tipoEl = document.getElementById('revTipoEntregaDisplay');
  if (data.tipo_entrega === 'pickup') {
    tipoEl.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;background:#F0FDF4;color:#065F46;font-size:.8rem;border:1px solid #A7F3D0;font-weight:700">Recoger en bodega</span>';
  } else if (data.tipo_entrega === 'repartidor') {
    tipoEl.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;background:#DBEAFE;color:#1E40AF;font-size:.8rem;border:1px solid #BFDBFE;font-weight:700">Envío a domicilio</span>';
  } else {
    tipoEl.innerHTML = '<span style="color:#9CA3AF;font-size:.85rem">No especificado</span>';
  }

  const pagoMap = {transferencia:'Transferencia bancaria', tarjeta:'Tarjeta de crédito/débito', credito:'Crédito de empresa'};
  document.getElementById('revMetodoPagoDisplay').textContent = pagoMap[data.metodo_pago] || data.metodo_pago || '—';

  if (data.fecha_entrega) {
    document.getElementById('revFechaEntregaBox').style.display = 'block';
    try {
      const fe = new Date(data.fecha_entrega + 'T00:00:00');
      document.getElementById('revFechaEntregaDisplay').textContent =
        fe.toLocaleDateString('es-MX', {weekday:'long', day:'2-digit', month:'2-digit', year:'numeric'});
    } catch(e) { document.getElementById('revFechaEntregaDisplay').textContent = data.fecha_entrega; }
  } else {
    document.getElementById('revFechaEntregaBox').style.display = 'none';
  }

  if (data.tipo_entrega === 'repartidor' && data.direccion_entrega) {
    document.getElementById('revDireccionBox').style.display = 'block';
    document.getElementById('revDireccionDisplay').textContent = data.direccion_entrega;
    document.getElementById('revReferenciaDisplay').textContent = data.referencia_entrega || '';
  } else {
    document.getElementById('revDireccionBox').style.display = 'none';
  }

  if (data.notas) {
    document.getElementById('revNotasBox').style.display = 'block';
    document.getElementById('revNotasDisplay').textContent = data.notas;
  } else {
    document.getElementById('revNotasBox').style.display = 'none';
  }

  const guia = document.getElementById('revGuiaAdmin');
  if (data.tipo_entrega === 'pickup') {
    guia.style.cssText = 'margin-bottom:14px;padding:12px 14px;border-radius:9px;font-size:.84rem;background:#F0FDF4;border:1px solid #A7F3D0;color:#065F46';
    guia.innerHTML = '<strong>¿Qué sigue?</strong> El comprador eligió <strong>recoger en bodega</strong>. Revisa los productos, ajusta precios si es necesario, y aprueba el pedido.';
  } else if (data.tipo_entrega === 'repartidor') {
    guia.style.cssText = 'margin-bottom:14px;padding:12px 14px;border-radius:9px;font-size:.84rem;background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF';
    guia.innerHTML = '<strong>¿Qué sigue?</strong> El comprador eligió <strong>envío a domicilio</strong>. Asigna un repartidor y define el costo de envío, luego aprueba el pedido.';
  } else {
    guia.style.cssText = 'margin-bottom:14px;padding:12px 14px;border-radius:9px;font-size:.84rem;background:#F9FAFB;border:1px solid #E5E7EB;color:#374151';
    guia.innerHTML = '<strong>¿Qué sigue?</strong> Revisa los productos, ajusta precios si es necesario, y aprueba o rechaza el pedido.';
  }

  document.getElementById('revAsignRepartidor').style.display = data.tipo_entrega === 'repartidor' ? 'block' : 'none';
  document.getElementById('hTipoEntrega').value = data.tipo_entrega || '';
  document.getElementById('revNotaEmpresaInput').value = '';
  const repSel = document.getElementById('revRepartidorSelect');
  if (repSel) repSel.value = '';
  const costoInp = document.getElementById('revCostoEnvioInput');
  if (costoInp) costoInp.value = '0';

  document.getElementById('modalRevision').style.display = 'flex';

  const loading   = document.getElementById('revProdLoading');
  const tabla     = document.getElementById('revProdTabla');
  const totalEl   = document.getElementById('revProdTotal');
  const precSec   = document.getElementById('preciosSection');
  const itemsCont = document.getElementById('itemsContainer');
  const formAprob = document.getElementById('formAprobar');

  formAprob.querySelectorAll('input[name^="ajustes"]').forEach(el => el.remove());
  tabla.innerHTML = ''; itemsCont.innerHTML = '';
  precSec.style.display = 'none';
  loading.style.display = 'block';
  tabla.style.display = 'none';
  totalEl.style.display = 'none';

  fetch(BASE_URL + 'empresa-pedido/itemsJson/' + data.id)
    .then(r => r.json())
    .then(items => {
      loading.style.display = 'none';
      if (!items || items.length === 0) return;

      let html = '<table style="width:100%;border-collapse:collapse;font-size:.83rem">';
      html += '<thead><tr style="background:#F9FAFB;border-bottom:1px solid #F3F4F6">' +
        '<th style="padding:8px 12px;text-align:left;color:#6B7280;font-weight:600;font-size:.65rem;text-transform:uppercase">Producto</th>' +
        '<th style="padding:8px 12px;text-align:center;color:#6B7280;font-weight:600;font-size:.65rem;text-transform:uppercase">Cant.</th>' +
        '<th style="padding:8px 12px;text-align:right;color:#6B7280;font-weight:600;font-size:.65rem;text-transform:uppercase">P. unit.</th>' +
        '<th style="padding:8px 12px;text-align:right;color:#6B7280;font-weight:600;font-size:.65rem;text-transform:uppercase">Subtotal</th>' +
        '</tr></thead><tbody>';
      let subtotal = 0;
      items.forEach(item => {
        subtotal += parseFloat(item.subtotal);
        html += `<tr style="border-top:1px solid #F9FAFB">
          <td style="padding:8px 12px;font-weight:600;color:#111827">${item.producto_nombre}
            <div style="font-size:.7rem;color:#9CA3AF;font-weight:400">${item.presentacion}</div>
          </td>
          <td style="padding:8px 12px;text-align:center;color:#374151">${parseFloat(item.cantidad).toFixed(2)}</td>
          <td style="padding:8px 12px;text-align:right;color:#374151">$${parseFloat(item.precio_unit).toFixed(2)}</td>
          <td style="padding:8px 12px;text-align:right;font-weight:700;color:#111827">$${parseFloat(item.subtotal).toFixed(2)}</td>
        </tr>`;
      });
      html += '</tbody></table>';
      tabla.innerHTML = html;
      tabla.style.display = 'block';
      totalEl.textContent = 'TOTAL: $' + subtotal.toFixed(2);
      totalEl.style.display = 'block';

      precSec.style.display = 'block';
      items.forEach(item => {
        const row = document.createElement('div');
        row.style.cssText = 'display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center;padding:10px 12px;border-bottom:1px solid #F9FAFB';
        row.innerHTML = `
          <div>
            <div style="font-weight:600;color:#111827;font-size:.85rem">${item.producto_nombre}</div>
            <div style="font-size:.73rem;color:#9CA3AF">${item.cantidad} ${item.presentacion} × $${parseFloat(item.precio_unit).toFixed(2)}</div>
          </div>
          <div style="display:flex;align-items:center;gap:6px">
            <span style="font-size:.7rem;color:#9CA3AF">Nuevo precio:</span>
            <input type="number" name="ajustes[${item.id}]" form="formAprobar"
                   min="0.01" max="${item.precio_unit}" step="0.01"
                   placeholder="${parseFloat(item.precio_unit).toFixed(2)}"
                   style="width:88px;padding:5px 8px;border:1.5px solid #E5E7EB;border-radius:7px;font-size:.85rem;text-align:right;font-family:inherit">
          </div>`;
        itemsCont.appendChild(row);
      });
    })
    .catch(() => { loading.style.display = 'none'; });
}

function abrirSubirFoto(id) {
  document.getElementById('fotoEntregaPedidoId').value = id;
  document.getElementById('modalFotoEntrega').style.display = 'flex';
}

function sincronizarEntrega() {
  document.getElementById('hTipoEntrega').value  = (_revData && _revData.tipo_entrega) ? _revData.tipo_entrega : '';
  const repSel   = document.getElementById('revRepartidorSelect');
  const costoInp = document.getElementById('revCostoEnvioInput');
  document.getElementById('hRepartidorId').value = repSel   ? repSel.value   : '';
  document.getElementById('hCostoEnvio').value   = costoInp ? costoInp.value : '0';
  document.getElementById('hNotaEmpresa').value  = document.getElementById('revNotaEmpresaInput').value;
}

['modalRevision','modalFotoEntrega'].forEach(id => {
  document.getElementById(id).addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
  });
});
</script>
