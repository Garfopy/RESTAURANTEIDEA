<?php
$baseUrl = BASE_URL;
$estados = [
    'pendiente'      => ['label'=>'Pendiente',       'bg'=>'#FEF3C7','tx'=>'#92400E'],
    'confirmado'     => ['label'=>'Confirmado',       'bg'=>'#DBEAFE','tx'=>'#1E40AF'],
    'en_preparacion' => ['label'=>'En preparación',  'bg'=>'#EDE9FE','tx'=>'#5B21B6'],
    'en_ruta'        => ['label'=>'En ruta',           'bg'=>'#FEF3C7','tx'=>'#B45309'],
    'entregado'      => ['label'=>'Entregado',         'bg'=>'#D1FAE5','tx'=>'#065F46'],
    'cancelado'      => ['label'=>'Cancelado',         'bg'=>'#FEE2E2','tx'=>'#991B1B'],
];
?>

<!-- Flash -->
<?php if ($flash): ?>
<div style="margin-bottom:16px;padding:12px 16px;border-radius:8px;font-size:.875rem;font-weight:500;
  <?= $flash['type'] === 'success' ? 'background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0' : 'background:#FEE2E2;color:#991B1B;border:1px solid #FECACA' ?>">
  <?= $flash['message'] ?>
</div>
<?php endif; ?>

<!-- Badge de pedidos pendientes -->
<?php if ($countPendientes > 0): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:#FEF3C7;border:1px solid #FCD34D;border-radius:10px;display:flex;align-items:center;gap:10px">
  <span style="font-size:1.1rem">⚠️</span>
  <div>
    <strong style="color:#92400E"><?= $countPendientes ?> pedido(s) pendiente(s) de revisión</strong>
    <span style="font-size:.8rem;color:#B45309;display:block">Asigna tipo de entrega y aprueba o rechaza cada uno.</span>
  </div>
</div>
<?php endif; ?>

<!-- Barra de acciones -->
<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center">
  <form method="GET" style="display:flex;gap:8px;flex:1;min-width:300px;align-items:flex-end;flex-wrap:wrap">
    <input type="text" name="buscar" placeholder="Buscar folio o comprador..."
           value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>"
           style="flex:1;min-width:160px;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem">
    <select name="estado" style="padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;background:#fff">
      <option value="">Todos los estados</option>
      <?php foreach ($estados as $k => $v): ?>
      <option value="<?= $k ?>" <?= ($filtros['estado'] ?? '') === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
      <?php endforeach; ?>
    </select>
    <select name="tipo" style="padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;background:#fff">
      <option value="">Todos los tipos</option>
      <option value="normal" <?= ($filtros['tipo'] ?? '') === 'normal' ? 'selected' : '' ?>>Normal</option>
      <option value="personalizado" <?= ($filtros['tipo'] ?? '') === 'personalizado' ? 'selected' : '' ?>>Personalizado</option>
    </select>
    <button type="submit" style="padding:8px 16px;background:#374151;color:#fff;border:none;border-radius:8px;font-size:.85rem;cursor:pointer;font-weight:600">Filtrar</button>
  </form>
</div>

<!-- Tabla de pedidos -->
<div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden">
  <?php if (empty($items)): ?>
    <div style="padding:48px;text-align:center;color:#9CA3AF">Sin pedidos para los filtros seleccionados.</div>
  <?php else: ?>
  <table style="width:100%;border-collapse:collapse">
    <thead>
      <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
        <th style="padding:10px 16px;text-align:left;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Folio</th>
        <th style="padding:10px 16px;text-align:left;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Comprador</th>
        <th style="padding:10px 16px;text-align:center;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Estado</th>
        <th style="padding:10px 16px;text-align:center;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Entrega</th>
        <th style="padding:10px 16px;text-align:right;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Total</th>
        <th style="padding:10px 16px;text-align:left;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Fecha</th>
        <th style="padding:10px 16px;text-align:center;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $p): ?>
      <?php
        $est = $estados[$p['estado']] ?? ['label' => $p['estado'], 'bg' => '#F3F4F6', 'tx' => '#374151'];
        $esPendiente = $p['estado'] === 'pendiente';
        $esPersonalizado = ($p['tipo'] ?? 'normal') === 'personalizado';
        $tieneComprobante = !empty($p['foto_comprobante_path']);
      ?>
      <tr style="border-bottom:1px solid #F3F4F6;<?= $esPendiente ? 'background:#FFFBEB' : '' ?>">
        <td style="padding:10px 16px">
          <div style="font-weight:700;font-size:.85rem;color:#111827;font-family:monospace"><?= htmlspecialchars($p['folio']) ?></div>
          <?php if ($esPersonalizado): ?>
          <span style="padding:1px 6px;border-radius:999px;background:#F3E8FF;color:#6B21A8;font-size:.65rem;font-weight:700">Personalizado</span>
          <?php endif; ?>
        </td>
        <td style="padding:10px 16px;font-size:.85rem;color:#374151">
          <?= htmlspecialchars($p['comprador_nombre'] . ' ' . $p['comprador_apellido']) ?>
        </td>
        <td style="padding:10px 16px;text-align:center">
          <span style="padding:3px 10px;border-radius:999px;background:<?= $est['bg'] ?>;color:<?= $est['tx'] ?>;font-size:.7rem;font-weight:700">
            <?= $est['label'] ?>
          </span>
          <?php if ($tieneComprobante): ?>
          <div style="font-size:.65rem;color:#059669;margin-top:2px;font-weight:600">✓ Comprobante</div>
          <?php endif; ?>
        </td>
        <td style="padding:10px 16px;text-align:center">
          <?php if (!empty($p['tipo_entrega'])): ?>
          <span style="font-size:.75rem;color:#374151;font-weight:600">
            <?= $p['tipo_entrega'] === 'pickup' ? '🏭 Pickup' : '🚚 Repartidor' ?>
          </span>
          <?php else: ?>
          <span style="font-size:.75rem;color:#9CA3AF">—</span>
          <?php endif; ?>
        </td>
        <td style="padding:10px 16px;text-align:right;font-size:.9rem;font-weight:700;color:#111827">
          $<?= number_format((float)$p['total'], 2) ?>
          <?php if (($p['costo_envio'] ?? 0) > 0): ?>
          <div style="font-size:.7rem;color:#6B7280;font-weight:400">+ $<?= number_format($p['costo_envio'], 2) ?> envío</div>
          <?php endif; ?>
        </td>
        <td style="padding:10px 16px;font-size:.78rem;color:#6B7280">
          <?= date('d/m/Y', strtotime($p['created_at'])) ?>
        </td>
        <td style="padding:10px 16px;text-align:center">
          <div style="display:flex;justify-content:center;gap:4px;flex-wrap:wrap">
            <a href="<?= $baseUrl ?>pedido/detalle/<?= $p['id'] ?>"
               style="padding:4px 8px;border:1px solid #D1D5DB;border-radius:6px;color:#374151;text-decoration:none;font-size:.72rem">
              Ver
            </a>
            <?php if ($esPendiente): ?>
            <button onclick="abrirRevision(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['comprador_nombre'] . ' ' . $p['comprador_apellido'])) ?>')"
                    style="padding:4px 8px;border:1px solid #F59E0B;border-radius:6px;color:#B45309;background:#FEF3C7;cursor:pointer;font-size:.72rem;font-weight:700;font-family:inherit">
              Revisar
            </button>
            <?php endif; ?>
            <?php if (in_array($p['estado'], ['en_preparacion','en_ruta','confirmado'], true)): ?>
            <button onclick="abrirSubirFoto(<?= $p['id'] ?>)"
                    style="padding:4px 8px;border:1px solid #10B981;border-radius:6px;color:#065F46;background:#D1FAE5;cursor:pointer;font-size:.72rem;font-weight:600;font-family:inherit">
              📷 Entrega
            </button>
            <?php endif; ?>
            <button onclick="abrirCambioEstado(<?= $p['id'] ?>, '<?= $p['estado'] ?>')"
                    style="padding:4px 8px;border:1px solid #D1D5DB;border-radius:6px;color:#374151;background:#fff;cursor:pointer;font-size:.72rem;font-family:inherit">
              Estado
            </button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Paginación -->
  <?php if (($paginacion['last_page'] ?? 1) > 1): ?>
  <div style="padding:16px;display:flex;justify-content:center;gap:4px;border-top:1px solid #E5E7EB">
    <?php for ($i = 1; $i <= $paginacion['last_page']; $i++): ?>
      <a href="?page=<?= $i ?>"
         style="padding:6px 12px;border-radius:6px;font-size:.8rem;text-decoration:none;<?= $i === ($paginacion['current_page'] ?? 1) ? 'background:var(--color-primary);color:#fff' : 'background:#F3F4F6;color:#374151' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Modal: Revisar pedido (asignar entrega + aprobar/rechazar) -->
<div id="modalRevision" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:14px;padding:28px;width:560px;max-width:96vw;max-height:90vh;overflow-y:auto">
    <h3 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 4px 0">Revisar pedido</h3>
    <p id="revisionComprador" style="font-size:.85rem;color:#6B7280;margin:0 0 20px 0"></p>

    <form method="POST" action="<?= $baseUrl ?>empresa-pedido/asignarEntrega" id="formAsignar">
      <input type="hidden" name="pedido_id" id="revPedidoId">
      <div style="margin-bottom:14px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px">Tipo de entrega <span style="color:#DC2626">*</span></label>
        <select name="tipo_entrega" required onchange="toggleRepartidor(this.value)"
                style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;background:#fff">
          <option value="">— Seleccionar —</option>
          <option value="pickup">🏭 Recoger en bodega (sin costo de envío)</option>
          <option value="repartidor">🚚 Envío por repartidor</option>
        </select>
      </div>
      <div id="campoRepartidor" style="display:none;margin-bottom:14px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px">Repartidor asignado</label>
        <select name="repartidor_asignado_id"
                style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;background:#fff">
          <option value="">— Sin asignar aún —</option>
          <?php foreach ($repartidores as $r): ?>
          <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido_paterno']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="costoEnvioContainer" style="margin-bottom:14px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px">Costo de envío ($)</label>
        <input type="number" name="costo_envio" id="revCostoEnvio" min="0" step="0.01" value="0"
               style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box">
        <div style="font-size:.75rem;color:#9CA3AF;margin-top:3px">0 si el envío está incluido en el precio.</div>
      </div>
      <div style="margin-bottom:18px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px">Nota para el comprador (opcional)</label>
        <textarea name="nota_empresa" rows="2" placeholder="Ej: Tu pedido estará listo el jueves a las 10am..."
                  style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;resize:vertical;box-sizing:border-box"></textarea>
      </div>
      <button type="submit"
              style="width:100%;padding:10px;background:#374151;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:.875rem;margin-bottom:12px">
        Guardar asignación
      </button>
    </form>

    <hr style="border:none;border-top:1px solid #F3F4F6;margin:4px 0 16px 0">

    <!-- Productos con precios ajustables -->
    <div id="preciosSection" style="display:none;margin-bottom:16px">
      <div style="font-size:.85rem;font-weight:700;color:#111827;margin-bottom:6px">
        Ajuste de precios (opcional)
        <span style="font-size:.72rem;color:#9CA3AF;font-weight:400"> — solo puedes bajar precios, no subirlos</span>
      </div>
      <div id="itemsContainer" style="font-size:.85rem"></div>
    </div>
    <div id="preciosLoading" style="display:none;font-size:.82rem;color:#9CA3AF;margin-bottom:12px">Cargando productos del pedido...</div>

    <form method="POST" action="<?= $baseUrl ?>empresa-pedido/aprobar" id="formAprobar" style="margin-bottom:8px">
      <input type="hidden" name="pedido_id" class="syncPedidoId">
      <input type="hidden" name="tipo_entrega" id="hTipoEntrega">
      <input type="hidden" name="repartidor_asignado_id" id="hRepartidorId">
      <input type="hidden" name="costo_envio" id="hCostoEnvio">
      <input type="hidden" name="nota_empresa" id="hNotaEmpresa">
      <button type="submit" onclick="sincronizarEntrega()"
              style="width:100%;padding:10px;background:#059669;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.875rem">
        ✓ Aprobar pedido
      </button>
    </form>
    <form method="POST" action="<?= $baseUrl ?>empresa-pedido/rechazar">
      <input type="hidden" name="pedido_id" class="syncPedidoId">
      <div style="display:flex;gap:8px">
        <input type="text" name="nota_rechazo" placeholder="Motivo del rechazo..." required
               style="flex:1;padding:9px 12px;border:1px solid #FECACA;border-radius:8px;font-size:.85rem">
        <button type="submit"
                style="padding:9px 14px;background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;border-radius:8px;font-weight:700;cursor:pointer;font-size:.82rem;white-space:nowrap">
          ✕ Rechazar
        </button>
      </div>
    </form>

    <button onclick="document.getElementById('modalRevision').style.display='none'"
            style="width:100%;margin-top:12px;padding:8px;border:1px solid #D1D5DB;border-radius:8px;background:#fff;cursor:pointer;font-size:.85rem;color:#6B7280">
      Cancelar
    </button>
  </div>
</div>

<!-- Modal: Subir foto de entrega -->
<div id="modalFotoEntrega" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:14px;padding:28px;width:400px;max-width:95vw">
    <h3 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 16px 0">📷 Foto de entrega</h3>
    <form method="POST" action="<?= $baseUrl ?>empresa-pedido/subirFotoEntrega" enctype="multipart/form-data">
      <input type="hidden" name="pedido_id" id="fotoEntregaPedidoId">
      <input type="file" name="foto" accept="image/*" capture="environment" required
             style="width:100%;padding:8px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;margin-bottom:8px;box-sizing:border-box">
      <div style="font-size:.75rem;color:#9CA3AF;margin-bottom:14px">JPG, PNG o WEBP. Al guardar, el pedido se marcará como <strong>Entregado</strong>.</div>
      <div style="display:flex;gap:8px">
        <button type="submit"
                style="flex:1;padding:10px;background:#059669;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer">
          Guardar y marcar entregado
        </button>
        <button type="button" onclick="document.getElementById('modalFotoEntrega').style.display='none'"
                style="padding:10px 16px;border:1px solid #D1D5DB;border-radius:8px;background:#fff;cursor:pointer">
          Cancelar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Cambio de estado rápido -->
<div id="modalEstado" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;padding:28px;width:380px;max-width:95vw">
    <h3 style="font-size:1rem;font-weight:700;color:#111827;margin-bottom:20px">Cambiar Estado del Pedido</h3>
    <form method="POST" action="<?= $baseUrl ?>empresa-pedido/cambiarEstado">
      <input type="hidden" name="pedido_id" id="modalPedidoId">
      <div style="margin-bottom:16px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px">Nuevo estado</label>
        <select name="estado" id="modalEstadoSelect" style="width:100%;padding:10px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;background:#fff">
          <?php foreach ($estados as $k => $v): ?>
          <option value="<?= $k ?>"><?= $v['label'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" style="flex:1;padding:10px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer">Guardar</button>
        <button type="button" onclick="document.getElementById('modalEstado').style.display='none'"
                style="padding:10px 20px;border:1px solid #D1D5DB;border-radius:8px;background:#fff;cursor:pointer;font-size:.875rem">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
const BASE_URL = '<?= $baseUrl ?>';

function abrirRevision(id, comprador) {
  document.getElementById('revPedidoId').value = id;
  document.getElementById('revisionComprador').textContent = 'Comprador: ' + comprador;
  document.querySelectorAll('.syncPedidoId').forEach(el => el.value = id);
  document.getElementById('modalRevision').style.display = 'flex';

  // Load items for price adjustment
  const section = document.getElementById('preciosSection');
  const loading = document.getElementById('preciosLoading');
  const cont    = document.getElementById('itemsContainer');
  const formAprobar = document.getElementById('formAprobar');

  // Clear previous items from formAprobar (keep hidden input)
  const oldInputs = formAprobar.querySelectorAll('input[name^="ajustes"]');
  oldInputs.forEach(el => el.remove());

  section.style.display = 'none';
  loading.style.display = 'block';
  cont.innerHTML = '';

  fetch(BASE_URL + 'empresa-pedido/itemsJson/' + id)
    .then(r => r.json())
    .then(items => {
      loading.style.display = 'none';
      if (!items || items.length === 0) return;

      section.style.display = 'block';
      items.forEach(item => {
        const row = document.createElement('div');
        row.style.cssText = 'display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center;padding:8px;border-bottom:1px solid #F3F4F6;margin-bottom:2px';
        row.innerHTML = `
          <div>
            <div style="font-weight:600;color:#111827">${item.producto_nombre}</div>
            <div style="font-size:.75rem;color:#9CA3AF">${item.cantidad} ${item.presentacion} × $${parseFloat(item.precio_unit).toFixed(2)} = $${parseFloat(item.subtotal).toFixed(2)}</div>
          </div>
          <div style="display:flex;align-items:center;gap:6px">
            <span style="font-size:.72rem;color:#9CA3AF">Nuevo precio:</span>
            <input type="number" name="ajustes[${item.id}]" form="formAprobar"
                   min="0.01" max="${item.precio_unit}" step="0.01"
                   placeholder="${parseFloat(item.precio_unit).toFixed(2)}"
                   style="width:90px;padding:5px 8px;border:1px solid #D1D5DB;border-radius:6px;font-size:.85rem;text-align:right">
          </div>`;
        cont.appendChild(row);
      });
    })
    .catch(() => { loading.style.display = 'none'; });
}

function abrirSubirFoto(id) {
  document.getElementById('fotoEntregaPedidoId').value = id;
  document.getElementById('modalFotoEntrega').style.display = 'flex';
}

function abrirCambioEstado(id, estadoActual) {
  document.getElementById('modalPedidoId').value = id;
  document.getElementById('modalEstadoSelect').value = estadoActual;
  document.getElementById('modalEstado').style.display = 'flex';
}

function toggleRepartidor(val) {
  document.getElementById('campoRepartidor').style.display = val === 'repartidor' ? 'block' : 'none';
  document.getElementById('costoEnvioContainer').style.display = val === 'pickup' ? 'none' : 'block';
  if (val === 'pickup') document.getElementById('revCostoEnvio').value = '0';
}

function sincronizarEntrega() {
  const form = document.getElementById('formAsignar');
  document.getElementById('hTipoEntrega').value   = form.querySelector('[name="tipo_entrega"]').value;
  document.getElementById('hRepartidorId').value  = form.querySelector('[name="repartidor_asignado_id"]').value;
  document.getElementById('hCostoEnvio').value    = form.querySelector('[name="costo_envio"]').value;
  document.getElementById('hNotaEmpresa').value   = form.querySelector('[name="nota_empresa"]').value;
}

['modalRevision','modalEstado','modalFotoEntrega'].forEach(id => {
  document.getElementById(id).addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
  });
});
</script>
