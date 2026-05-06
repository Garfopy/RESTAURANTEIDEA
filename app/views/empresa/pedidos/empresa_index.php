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
<div style="margin-bottom:12px;padding:12px 16px;background:#FEF3C7;border:1px solid #FCD34D;border-radius:10px;display:flex;align-items:center;gap:10px">
  <span style="font-size:1.1rem">⚠️</span>
  <div>
    <strong style="color:#92400E"><?= $countPendientes ?> pedido(s) pendiente(s) de revisión</strong>
    <span style="font-size:.8rem;color:#B45309;display:block">Revisa cada pedido, ajusta precios si es necesario, y apruébalo o recházalo.</span>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($countConComprobante) && $countConComprobante > 0): ?>
<div style="margin-bottom:16px;padding:12px 16px;background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;display:flex;align-items:center;gap:10px">
  <span style="font-size:1.1rem">💳</span>
  <div>
    <strong style="color:#065F46"><?= $countConComprobante ?> pedido(s) con comprobante de pago adjunto</strong>
    <span style="font-size:.8rem;color:#047857;display:block">Revisa el comprobante en el detalle del pedido y procesa la entrega.</span>
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
          <?php if ($tieneComprobante && in_array($p['estado'], ['en_preparacion','confirmado'], true)): ?>
          <div style="margin-top:4px">
            <span style="padding:2px 7px;border-radius:999px;background:#D1FAE5;color:#059669;font-size:.65rem;font-weight:700">💳 Comprobante</span>
          </div>
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
          <div style="display:flex;justify-content:center;gap:4px;flex-wrap:wrap;align-items:center">
            <a href="<?= $baseUrl ?>pedido/detalle/<?= $p['id'] ?>"
               style="padding:5px 10px;border:1px solid #D1D5DB;border-radius:6px;color:#374151;text-decoration:none;font-size:.72rem;font-weight:600">
              Ver →
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
            ]), ENT_QUOTES) ?>)"
                    style="padding:5px 10px;border:1px solid #F59E0B;border-radius:6px;color:#B45309;background:#FEF3C7;cursor:pointer;font-size:.72rem;font-weight:700;font-family:inherit">
              🔍 Revisar
            </button>

            <?php elseif ($tieneComprobante && in_array($p['estado'], ['confirmado','en_preparacion'], true)): ?>
            <form method="POST" action="<?= $baseUrl ?>empresa-pedido/cambiarEstado" style="display:inline"
                  onsubmit="return confirm('¿Confirmar el comprobante de pago y continuar con la entrega?')">
              <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="estado" value="en_ruta">
              <button type="submit"
                      style="padding:5px 10px;border:1px solid #2563EB;border-radius:6px;color:#fff;background:#2563EB;cursor:pointer;font-size:.72rem;font-weight:700;font-family:inherit">
                💳 Confirmar pago
              </button>
            </form>

            <?php elseif (!$tieneComprobante && $p['estado'] === 'confirmado'): ?>
            <span style="font-size:.7rem;color:#9CA3AF;font-style:italic">Esperando comprobante...</span>

            <?php elseif ($p['estado'] === 'en_ruta' && ($p['tipo_entrega'] ?? '') === 'pickup'): ?>
            <form method="POST" action="<?= $baseUrl ?>empresa-pedido/cambiarEstado" style="display:inline"
                  onsubmit="return confirm('¿Confirmar que el comprador recogió el pedido?')">
              <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="estado" value="entregado">
              <button type="submit"
                      style="padding:5px 10px;border:1px solid #10B981;border-radius:6px;color:#fff;background:#059669;cursor:pointer;font-size:.72rem;font-weight:700;font-family:inherit">
                ✓ Recogido
              </button>
            </form>

            <?php elseif ($p['estado'] === 'en_ruta'): ?>
            <button onclick="abrirSubirFoto(<?= $p['id'] ?>)"
                    style="padding:5px 10px;border:1px solid #10B981;border-radius:6px;color:#fff;background:#059669;cursor:pointer;font-size:.72rem;font-weight:700;font-family:inherit">
              📷 Foto entrega
            </button>
            <?php endif; ?>
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

<!-- Modal: Revisar pedido -->
<div id="modalRevision" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:14px;padding:28px;width:660px;max-width:96vw;max-height:92vh;overflow-y:auto">

    <!-- Encabezado -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px">
      <div>
        <h3 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 3px 0">Revisar pedido</h3>
        <span id="revFolioDisplay" style="font-family:monospace;font-size:.85rem;color:#6B7280;font-weight:600"></span>
      </div>
      <button onclick="document.getElementById('modalRevision').style.display='none'"
              style="background:none;border:none;cursor:pointer;font-size:1.2rem;color:#9CA3AF;padding:2px 8px;line-height:1">✕</button>
    </div>

    <!-- Información del pedido (solo lectura) -->
    <div style="background:#F9FAFB;border-radius:10px;padding:14px 16px;margin-bottom:14px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 16px">
        <div>
          <div style="font-size:.68rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;margin-bottom:2px">Comprador</div>
          <div id="revCompradorDisplay" style="font-size:.875rem;font-weight:600;color:#374151"></div>
        </div>
        <div>
          <div style="font-size:.68rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;margin-bottom:2px">Fecha del pedido</div>
          <div id="revFechaDisplay" style="font-size:.875rem;font-weight:600;color:#374151"></div>
        </div>
        <div>
          <div style="font-size:.68rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;margin-bottom:2px">Tipo de entrega</div>
          <div id="revTipoEntregaDisplay" style="font-size:.875rem;font-weight:700"></div>
        </div>
        <div>
          <div style="font-size:.68rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;margin-bottom:2px">Método de pago</div>
          <div id="revMetodoPagoDisplay" style="font-size:.875rem;font-weight:600;color:#374151"></div>
        </div>
        <div id="revFechaEntregaBox" style="display:none">
          <div style="font-size:.68rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;margin-bottom:2px">Fecha entrega solicitada</div>
          <div id="revFechaEntregaDisplay" style="font-size:.875rem;font-weight:600;color:#374151"></div>
        </div>
        <div id="revDireccionBox" style="grid-column:1/-1;display:none;padding-top:6px;border-top:1px solid #E5E7EB;margin-top:4px">
          <div style="font-size:.68rem;color:#9CA3AF;font-weight:700;text-transform:uppercase;margin-bottom:2px">Dirección de entrega</div>
          <div id="revDireccionDisplay" style="font-size:.85rem;color:#374151"></div>
          <div id="revReferenciaDisplay" style="font-size:.78rem;color:#6B7280;margin-top:1px"></div>
        </div>
      </div>
    </div>

    <!-- Notas del comprador -->
    <div id="revNotasBox" style="display:none;margin-bottom:12px;padding:10px 14px;background:#FFFBEB;border:1px solid #FCD34D;border-radius:8px">
      <div style="font-size:.68rem;color:#92400E;font-weight:700;text-transform:uppercase;margin-bottom:2px">Notas del comprador</div>
      <div id="revNotasDisplay" style="font-size:.85rem;color:#78350F;white-space:pre-line"></div>
    </div>

    <!-- Guía para el admin -->
    <div id="revGuiaAdmin" style="margin-bottom:14px;padding:12px 14px;border-radius:10px;font-size:.85rem"></div>

    <hr style="border:none;border-top:1px solid #E5E7EB;margin:0 0 14px 0">

    <!-- Productos (AJAX) -->
    <div style="margin-bottom:14px">
      <div style="font-size:.85rem;font-weight:700;color:#111827;margin-bottom:8px">Productos del pedido</div>
      <div id="revProdLoading" style="font-size:.82rem;color:#9CA3AF">Cargando productos...</div>
      <div id="revProdTabla" style="display:none;overflow-x:auto"></div>
      <div id="revProdTotal" style="display:none;text-align:right;font-size:.95rem;font-weight:800;color:var(--color-primary);margin-top:6px;padding-top:6px;border-top:2px solid #E5E7EB"></div>
    </div>

    <!-- Ajuste de precios (AJAX) -->
    <div id="preciosSection" style="display:none;margin-bottom:14px">
      <div style="font-size:.82rem;font-weight:700;color:#374151;margin-bottom:6px">
        Ajuste de precios <span style="font-size:.72rem;color:#9CA3AF;font-weight:400">— opcional, solo puedes bajar precios</span>
      </div>
      <div id="itemsContainer" style="font-size:.85rem"></div>
    </div>

    <hr style="border:none;border-top:1px solid #E5E7EB;margin:0 0 14px 0">

    <!-- Asignación repartidor (solo si tipo=repartidor) -->
    <div id="revAsignRepartidor" style="display:none;margin-bottom:14px">
      <div style="font-size:.85rem;font-weight:700;color:#374151;margin-bottom:8px">Asignar entrega</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">Repartidor asignado</label>
          <select id="revRepartidorSelect"
                  style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;background:#fff">
            <option value="">— Sin asignar aún —</option>
            <?php foreach ($repartidores as $r): ?>
            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido_paterno']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">Costo de envío ($)</label>
          <input type="number" id="revCostoEnvioInput" min="0" step="0.01" value="0"
                 style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.85rem;box-sizing:border-box">
          <div style="font-size:.72rem;color:#9CA3AF;margin-top:2px">0 si está incluido en el precio.</div>
        </div>
      </div>
    </div>

    <!-- Nota para el comprador -->
    <div style="margin-bottom:14px">
      <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:4px">
        Nota para el comprador <span style="color:#9CA3AF;font-weight:400">(opcional)</span>
      </label>
      <textarea id="revNotaEmpresaInput" rows="2" placeholder="Ej: Tu pedido estará listo el jueves a las 10am..."
                style="width:100%;padding:9px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;resize:vertical;box-sizing:border-box"></textarea>
    </div>

    <!-- Aprobar -->
    <form method="POST" action="<?= $baseUrl ?>empresa-pedido/aprobar" id="formAprobar" style="margin-bottom:8px">
      <input type="hidden" name="pedido_id" class="syncPedidoId">
      <input type="hidden" name="tipo_entrega" id="hTipoEntrega">
      <input type="hidden" name="repartidor_asignado_id" id="hRepartidorId">
      <input type="hidden" name="costo_envio" id="hCostoEnvio">
      <input type="hidden" name="nota_empresa" id="hNotaEmpresa">
      <button type="submit" onclick="sincronizarEntrega()"
              style="width:100%;padding:11px;background:#059669;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.9rem">
        ✓ Aprobar pedido
      </button>
    </form>

    <!-- Rechazar -->
    <form method="POST" action="<?= $baseUrl ?>empresa-pedido/rechazar" style="margin-bottom:8px">
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
            style="width:100%;margin-top:4px;padding:8px;border:1px solid #D1D5DB;border-radius:8px;background:#fff;cursor:pointer;font-size:.85rem;color:#6B7280">
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



<script>
const BASE_URL = '<?= $baseUrl ?>';
let _revData = null;

function abrirRevision(data) {
  _revData = data;
  document.querySelectorAll('.syncPedidoId').forEach(el => el.value = data.id);

  // Folio y comprador
  document.getElementById('revFolioDisplay').textContent = data.folio;
  document.getElementById('revCompradorDisplay').textContent = data.comprador;

  // Fecha del pedido
  if (data.created_at) {
    try {
      const d = new Date(data.created_at.replace(' ', 'T'));
      document.getElementById('revFechaDisplay').textContent =
        d.toLocaleDateString('es-MX', {day:'2-digit', month:'2-digit', year:'numeric'}) + ' ' +
        d.toLocaleTimeString('es-MX', {hour:'2-digit', minute:'2-digit'});
    } catch(e) { document.getElementById('revFechaDisplay').textContent = data.created_at; }
  }

  // Tipo entrega (solo lectura, badge)
  const tipoEl = document.getElementById('revTipoEntregaDisplay');
  if (data.tipo_entrega === 'pickup') {
    tipoEl.innerHTML = '<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#F0FDF4;color:#065F46;font-size:.8rem;border:1px solid #A7F3D0;font-weight:600">🏭 Recoger en bodega</span>';
  } else if (data.tipo_entrega === 'repartidor') {
    tipoEl.innerHTML = '<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#DBEAFE;color:#1E40AF;font-size:.8rem;border:1px solid #BFDBFE;font-weight:600">🚚 Envío a domicilio</span>';
  } else {
    tipoEl.innerHTML = '<span style="color:#9CA3AF;font-size:.85rem">— No especificado —</span>';
  }

  // Método de pago
  const pagoMap = {transferencia:'Transferencia bancaria', tarjeta:'Tarjeta de crédito/débito', credito:'Crédito de empresa'};
  document.getElementById('revMetodoPagoDisplay').textContent = pagoMap[data.metodo_pago] || data.metodo_pago || '—';

  // Fecha entrega solicitada
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

  // Dirección de entrega (solo si repartidor)
  if (data.tipo_entrega === 'repartidor' && data.direccion_entrega) {
    document.getElementById('revDireccionBox').style.display = 'block';
    document.getElementById('revDireccionDisplay').textContent = data.direccion_entrega;
    document.getElementById('revReferenciaDisplay').textContent = data.referencia_entrega || '';
  } else {
    document.getElementById('revDireccionBox').style.display = 'none';
  }

  // Notas del comprador
  if (data.notas) {
    document.getElementById('revNotasBox').style.display = 'block';
    document.getElementById('revNotasDisplay').textContent = data.notas;
  } else {
    document.getElementById('revNotasBox').style.display = 'none';
  }

  // Guía ¿Qué sigue?
  const guia = document.getElementById('revGuiaAdmin');
  if (data.tipo_entrega === 'pickup') {
    guia.style.cssText = 'margin-bottom:14px;padding:12px 14px;border-radius:10px;font-size:.85rem;background:#F0FDF4;border:1px solid #A7F3D0;color:#065F46';
    guia.innerHTML = '<strong>¿Qué sigue?</strong> El comprador eligió <strong>recoger en bodega</strong>. Revisa los productos (puedes ajustar precios a la baja), agrega una nota si es necesario, y aprueba el pedido. El comprador recibirá la confirmación y podrá subir su comprobante de pago.';
  } else if (data.tipo_entrega === 'repartidor') {
    guia.style.cssText = 'margin-bottom:14px;padding:12px 14px;border-radius:10px;font-size:.85rem;background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF';
    guia.innerHTML = '<strong>¿Qué sigue?</strong> El comprador eligió <strong>envío a domicilio</strong>. Asigna un repartidor y define el costo de envío, luego aprueba el pedido. El comprador recibirá la confirmación y podrá subir su comprobante de pago.';
  } else {
    guia.style.cssText = 'margin-bottom:14px;padding:12px 14px;border-radius:10px;font-size:.85rem;background:#F9FAFB;border:1px solid #E5E7EB;color:#374151';
    guia.innerHTML = '<strong>¿Qué sigue?</strong> Revisa los productos, ajusta precios si es necesario, y aprueba o rechaza el pedido.';
  }

  // Mostrar/ocultar asignación repartidor
  document.getElementById('revAsignRepartidor').style.display = data.tipo_entrega === 'repartidor' ? 'block' : 'none';
  document.getElementById('hTipoEntrega').value = data.tipo_entrega || '';

  // Reset inputs
  document.getElementById('revNotaEmpresaInput').value = '';
  const repSel = document.getElementById('revRepartidorSelect');
  if (repSel) repSel.value = '';
  const costoInp = document.getElementById('revCostoEnvioInput');
  if (costoInp) costoInp.value = '0';

  document.getElementById('modalRevision').style.display = 'flex';

  // Cargar productos vía AJAX
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
      html += '<thead><tr style="background:#F9FAFB">' +
        '<th style="padding:7px 10px;text-align:left;color:#6B7280;font-weight:600">Producto</th>' +
        '<th style="padding:7px 10px;text-align:center;color:#6B7280;font-weight:600">Cant.</th>' +
        '<th style="padding:7px 10px;text-align:right;color:#6B7280;font-weight:600">P. unit.</th>' +
        '<th style="padding:7px 10px;text-align:right;color:#6B7280;font-weight:600">Subtotal</th>' +
        '</tr></thead><tbody>';
      let subtotal = 0;
      items.forEach(item => {
        subtotal += parseFloat(item.subtotal);
        html += `<tr style="border-top:1px solid #F3F4F6">
          <td style="padding:7px 10px;font-weight:600;color:#111827">${item.producto_nombre}
            <div style="font-size:.72rem;color:#9CA3AF;font-weight:400">${item.presentacion}</div>
          </td>
          <td style="padding:7px 10px;text-align:center;color:#374151">${parseFloat(item.cantidad).toFixed(2)}</td>
          <td style="padding:7px 10px;text-align:right;color:#374151">$${parseFloat(item.precio_unit).toFixed(2)}</td>
          <td style="padding:7px 10px;text-align:right;font-weight:700;color:#111827">$${parseFloat(item.subtotal).toFixed(2)}</td>
        </tr>`;
      });
      html += '</tbody></table>';
      tabla.innerHTML = html;
      tabla.style.display = 'block';
      totalEl.textContent = 'TOTAL: $' + subtotal.toFixed(2);
      totalEl.style.display = 'block';

      // Ajuste de precios
      precSec.style.display = 'block';
      items.forEach(item => {
        const row = document.createElement('div');
        row.style.cssText = 'display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center;padding:8px;border-bottom:1px solid #F3F4F6';
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
