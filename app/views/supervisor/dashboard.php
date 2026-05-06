<?php
// Vista: Dashboard del Supervisor
$baseUrl = BASE_URL;

$repartidores = [];
try {
    $usuarioModel = new UsuarioModel();
    $repartidores = $usuarioModel->getByRolEmpresa('repartidor', $_SESSION['usuario']['empresa_id'] ?? 0);
} catch (\Throwable $e) {}
?>

<!-- KPIs (4 tarjetas) -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">

  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:20px">
    <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Pendientes aprobar</div>
    <div style="font-size:2rem;font-weight:800;color:<?= count($pendientes) > 0 ? '#D97706' : '#059669' ?>;line-height:1">
      <?= count($pendientes) ?>
    </div>
    <?php if (count($pendientes) > 0): ?>
      <a href="<?= $baseUrl ?>empresa-pedido?estado=pendiente" style="display:inline-block;margin-top:8px;font-size:.78rem;color:var(--color-primary);text-decoration:none;font-weight:600">Revisar cola →</a>
    <?php else: ?>
      <span style="display:inline-block;margin-top:8px;font-size:.78rem;color:#059669;font-weight:500">Al día ✓</span>
    <?php endif; ?>
  </div>

  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:20px">
    <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">En ruta ahora</div>
    <div style="font-size:2rem;font-weight:800;color:#3B82F6;line-height:1"><?= count($enRuta) ?></div>
    <a href="<?= $baseUrl ?>empresa-pedido?estado=en_ruta" style="display:inline-block;margin-top:8px;font-size:.78rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver pedidos →</a>
  </div>

  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:20px">
    <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Entregados hoy</div>
    <div style="font-size:2rem;font-weight:800;color:#059669;line-height:1"><?= $entregadosHoy ?></div>
    <span style="display:inline-block;margin-top:8px;font-size:.78rem;color:#6B7280">de <?= $pedidosHoy ?> recibidos</span>
  </div>

  <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;padding:20px">
    <div style="font-size:.72rem;color:#6B7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Monto este mes</div>
    <div style="font-size:1.6rem;font-weight:800;color:#111827;line-height:1">$<?= number_format($montoMes, 0) ?></div>
    <a href="<?= $baseUrl ?>empresa-pedido" style="display:inline-block;margin-top:8px;font-size:.78rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todos →</a>
  </div>
</div>

<!-- Alertas de stock (solo si hay críticos/agotados) -->
<?php if (!empty($alertasStock)): ?>
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:16px;margin-bottom:24px">
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#DC2626" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <span style="font-weight:700;color:#991B1B;font-size:.875rem">Alertas de stock — <?= count($alertasStock) ?> producto<?= count($alertasStock) !== 1 ? 's' : '' ?> requieren atención</span>
    <a href="<?= $baseUrl ?>empresa-inventario" style="margin-left:auto;font-size:.78rem;color:#DC2626;font-weight:600;text-decoration:none">Ver inventario →</a>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:8px">
    <?php foreach (array_slice($alertasStock, 0, 6) as $alerta): ?>
    <div style="background:#fff;border:1px solid #FECACA;border-radius:6px;padding:8px 12px;min-width:140px">
      <div style="font-size:.8rem;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px"><?= htmlspecialchars($alerta['nombre']) ?></div>
      <div style="display:flex;align-items:center;gap:6px;margin-top:4px">
        <?php if ($alerta['estado_stock'] === 'agotado'): ?>
          <span style="font-size:.7rem;font-weight:700;color:#fff;background:#DC2626;padding:1px 6px;border-radius:4px">AGOTADO</span>
        <?php else: ?>
          <span style="font-size:.7rem;font-weight:700;color:#fff;background:#D97706;padding:1px 6px;border-radius:4px">CRÍTICO</span>
        <?php endif; ?>
        <span style="font-size:.75rem;color:#6B7280"><?= number_format($alerta['stock_actual'], 0) ?> restantes</span>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (count($alertasStock) > 6): ?>
    <div style="background:#fff;border:1px solid #FECACA;border-radius:6px;padding:8px 12px;display:flex;align-items:center">
      <span style="font-size:.8rem;color:#DC2626;font-weight:600">+<?= count($alertasStock) - 6 ?> más</span>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px">

  <!-- Cola de aprobación -->
  <div>
    <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden;margin-bottom:20px">
      <div style="padding:14px 20px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between">
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-weight:700;color:#111827">Cola de aprobación</span>
          <?php if (count($pendientes) > 0): ?>
          <span style="background:#FEF3C7;color:#92400E;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:999px"><?= count($pendientes) ?> pendiente<?= count($pendientes) !== 1 ? 's' : '' ?></span>
          <?php endif; ?>
        </div>
        <a href="<?= $baseUrl ?>empresa-pedido?estado=pendiente" style="font-size:.8rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todos →</a>
      </div>

      <?php if (empty($pendientes)): ?>
        <div style="padding:40px;text-align:center;color:#059669">
          <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;display:block;opacity:.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <div style="font-weight:600;margin-bottom:4px">Sin pedidos pendientes</div>
          <div style="font-size:.8rem;color:#6B7280">Todos los pedidos han sido revisados.</div>
        </div>
      <?php else: ?>
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:#FFFBEB;border-bottom:1px solid #E5E7EB">
            <th style="padding:10px 16px;text-align:left;font-size:.7rem;color:#92400E;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Folio</th>
            <th style="padding:10px 16px;text-align:left;font-size:.7rem;color:#92400E;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Comprador</th>
            <th style="padding:10px 16px;text-align:right;font-size:.7rem;color:#92400E;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Total</th>
            <th style="padding:10px 16px;text-align:left;font-size:.7rem;color:#92400E;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Fecha</th>
            <th style="padding:10px 16px;text-align:center;font-size:.7rem;color:#92400E;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($pendientes, 0, 8) as $ped): ?>
          <tr style="border-bottom:1px solid #F3F4F6;transition:background .1s" onmouseover="this.style.background='#FAFAFA'" onmouseout="this.style.background=''">
            <td style="padding:10px 16px;font-size:.875rem;font-weight:700;color:#111827"><?= htmlspecialchars($ped['folio']) ?></td>
            <td style="padding:10px 16px;font-size:.875rem;color:#374151">
              <?= htmlspecialchars(($ped['comprador_nombre'] ?? '') . ' ' . ($ped['comprador_apellido'] ?? '')) ?>
            </td>
            <td style="padding:10px 16px;text-align:right;font-size:.875rem;font-weight:600;color:#111827">
              $<?= number_format($ped['total'], 2) ?>
            </td>
            <td style="padding:10px 16px;font-size:.78rem;color:#6B7280">
              <?= date('d/m/Y', strtotime($ped['created_at'])) ?>
            </td>
            <td style="padding:10px 16px;text-align:center">
              <div style="display:flex;gap:6px;justify-content:center">
                <a href="<?= $baseUrl ?>empresa-pedido/detalle/<?= $ped['id'] ?>"
                   style="padding:5px 10px;border-radius:6px;background:#F3F4F6;color:#374151;text-decoration:none;font-size:.75rem;font-weight:600;border:1px solid #E5E7EB">
                  Ver
                </a>
                <button onclick="abrirModalAprobar(<?= $ped['id'] ?>, '<?= htmlspecialchars($ped['folio']) ?>', <?= number_format($ped['total'], 2, '.', '') ?>)"
                   style="padding:5px 10px;border-radius:6px;background:#059669;color:#fff;border:none;cursor:pointer;font-size:.75rem;font-weight:600">
                  Aprobar
                </button>
                <button onclick="abrirModalRechazar(<?= $ped['id'] ?>, '<?= htmlspecialchars($ped['folio']) ?>')"
                   style="padding:5px 10px;border-radius:6px;background:#FEE2E2;color:#DC2626;border:none;cursor:pointer;font-size:.75rem;font-weight:600">
                  Rechazar
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (count($pendientes) > 8): ?>
        <div style="padding:12px 16px;border-top:1px solid #E5E7EB;text-align:center">
          <a href="<?= $baseUrl ?>empresa-pedido?estado=pendiente" style="font-size:.875rem;color:var(--color-primary);text-decoration:none;font-weight:600">
            Ver <?= count($pendientes) - 8 ?> más pendientes →
          </a>
        </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- Pedidos en ruta -->
    <?php if (!empty($enRuta)): ?>
    <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden">
      <div style="padding:14px 20px;border-bottom:1px solid #E5E7EB">
        <span style="font-weight:700;color:#111827">Pedidos en ruta</span>
        <span style="margin-left:8px;background:#EFF6FF;color:#1E40AF;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:999px"><?= count($enRuta) ?> activos</span>
      </div>
      <div style="padding:12px 16px;display:flex;flex-direction:column;gap:8px">
        <?php foreach ($enRuta as $pr): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#EFF6FF;border-radius:8px;border:1px solid #BFDBFE">
          <div>
            <div style="font-size:.875rem;font-weight:700;color:#1E3A8A"><?= htmlspecialchars($pr['folio']) ?></div>
            <div style="font-size:.75rem;color:#3B82F6;margin-top:2px"><?= htmlspecialchars($pr['comprador_nombre'] ?? '') ?></div>
          </div>
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:.875rem;font-weight:600;color:#1E3A8A">$<?= number_format($pr['total'], 2) ?></span>
            <a href="<?= $baseUrl ?>pedido/tracking/<?= $pr['id'] ?>"
               style="padding:5px 12px;background:#3B82F6;color:#fff;border-radius:6px;text-decoration:none;font-size:.75rem;font-weight:600">
              Rastrear
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Panel lateral: últimos movimientos de stock -->
  <div>
    <div style="background:#fff;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden">
      <div style="padding:14px 20px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between">
        <span style="font-weight:700;color:#111827">Movimientos de stock</span>
        <a href="<?= $baseUrl ?>empresa-inventario" style="font-size:.78rem;color:var(--color-primary);text-decoration:none;font-weight:600">Ver todo →</a>
      </div>
      <?php if (empty($ultimosMovimientos)): ?>
        <div style="padding:32px;text-align:center;color:#9CA3AF;font-size:.875rem">
          Sin movimientos registrados.
        </div>
      <?php else: ?>
      <div style="padding:8px 0">
        <?php foreach ($ultimosMovimientos as $mov): ?>
        <div style="padding:10px 16px;display:flex;align-items:flex-start;gap:10px;border-bottom:1px solid #F9FAFB">
          <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                      background:<?= $mov['tipo'] === 'entrada' ? '#D1FAE5' : '#FEE2E2' ?>">
            <?php if ($mov['tipo'] === 'entrada'): ?>
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <?php else: ?>
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#DC2626" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.8rem;font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= htmlspecialchars($mov['producto_nombre']) ?>
            </div>
            <div style="font-size:.72rem;color:#6B7280;margin-top:2px">
              <?= $mov['tipo'] === 'entrada' ? '+' : '-' ?><?= number_format($mov['cantidad'], 0) ?> · <?= htmlspecialchars($mov['motivo'] ?? '') ?>
            </div>
            <div style="font-size:.68rem;color:#9CA3AF;margin-top:1px">
              <?= date('d/m H:i', strtotime($mov['created_at'])) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div><!-- /grid -->

<!-- Modal: Aprobar pedido -->
<div id="modal-aprobar" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;width:460px;max-width:95vw;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.2)">
    <div style="padding:16px 20px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between">
      <span style="font-weight:700;color:#111827">Aprobar pedido <span id="modal-aprobar-folio" style="color:var(--color-primary)"></span></span>
      <button onclick="cerrarModales()" style="background:none;border:none;cursor:pointer;color:#9CA3AF;font-size:1.2rem">✕</button>
    </div>
    <form method="POST" id="form-aprobar">
      <div style="padding:20px">
        <p style="font-size:.875rem;color:#374151;margin-bottom:16px">
          Total del pedido: <strong id="modal-aprobar-total"></strong>
        </p>

        <div style="margin-bottom:14px">
          <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:6px">Tipo de entrega</label>
          <select name="tipo_entrega" id="tipo-entrega-sel" onchange="toggleRepartidor(this.value)"
                  style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem">
            <option value="pickup">Recoger en empresa</option>
            <option value="repartidor">Repartidor</option>
          </select>
        </div>

        <div id="repartidor-row" style="display:none;margin-bottom:14px">
          <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:6px">Repartidor</label>
          <select name="repartidor_asignado_id"
                  style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem">
            <option value="">— Sin asignar —</option>
            <?php foreach ($repartidores as $r): ?>
            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre'] . ' ' . ($r['apellido_paterno'] ?? '')) ?></option>
            <?php endforeach; ?>
          </select>
          <div style="margin-top:8px">
            <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">Costo de envío ($)</label>
            <input type="number" name="costo_envio" min="0" step="0.01" value="0"
                   style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem">
          </div>
        </div>

        <div style="margin-bottom:14px">
          <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:6px">Nota para el comprador (opcional)</label>
          <textarea name="nota_empresa" rows="2"
                    style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem;resize:vertical"
                    placeholder="Ej: Entrega en horario de mañana"></textarea>
        </div>
      </div>
      <div style="padding:14px 20px;border-top:1px solid #E5E7EB;display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="cerrarModales()"
                style="padding:8px 18px;border:1px solid #D1D5DB;border-radius:6px;background:#fff;color:#374151;font-size:.875rem;font-weight:600;cursor:pointer">
          Cancelar
        </button>
        <button type="submit"
                style="padding:8px 18px;border:none;border-radius:6px;background:#059669;color:#fff;font-size:.875rem;font-weight:600;cursor:pointer">
          Aprobar pedido
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Rechazar pedido -->
<div id="modal-rechazar" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;width:420px;max-width:95vw;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.2)">
    <div style="padding:16px 20px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between">
      <span style="font-weight:700;color:#111827">Rechazar <span id="modal-rechazar-folio" style="color:#DC2626"></span></span>
      <button onclick="cerrarModales()" style="background:none;border:none;cursor:pointer;color:#9CA3AF;font-size:1.2rem">✕</button>
    </div>
    <form method="POST" id="form-rechazar">
      <div style="padding:20px">
        <div>
          <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:6px">Motivo del rechazo</label>
          <textarea name="nota_rechazo" rows="3" required
                    style="width:100%;padding:8px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.875rem;resize:vertical"
                    placeholder="Explica al comprador por qué se rechaza este pedido…"></textarea>
        </div>
      </div>
      <div style="padding:14px 20px;border-top:1px solid #E5E7EB;display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="cerrarModales()"
                style="padding:8px 18px;border:1px solid #D1D5DB;border-radius:6px;background:#fff;color:#374151;font-size:.875rem;font-weight:600;cursor:pointer">
          Cancelar
        </button>
        <button type="submit"
                style="padding:8px 18px;border:none;border-radius:6px;background:#DC2626;color:#fff;font-size:.875rem;font-weight:600;cursor:pointer">
          Rechazar pedido
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModalAprobar(id, folio, total) {
  document.getElementById('modal-aprobar-folio').textContent = folio;
  document.getElementById('modal-aprobar-total').textContent = '$' + total.toFixed(2);
  document.getElementById('form-aprobar').action = '<?= $baseUrl ?>empresa-pedido/aprobar/' + id;
  document.getElementById('modal-aprobar').style.display = 'flex';
}
function abrirModalRechazar(id, folio) {
  document.getElementById('modal-rechazar-folio').textContent = folio;
  document.getElementById('form-rechazar').action = '<?= $baseUrl ?>empresa-pedido/rechazar/' + id;
  document.getElementById('modal-rechazar').style.display = 'flex';
}
function cerrarModales() {
  document.getElementById('modal-aprobar').style.display = 'none';
  document.getElementById('modal-rechazar').style.display = 'none';
}
function toggleRepartidor(val) {
  document.getElementById('repartidor-row').style.display = val === 'repartidor' ? 'block' : 'none';
}
document.getElementById('modal-aprobar').addEventListener('click', function(e) {
  if (e.target === this) cerrarModales();
});
document.getElementById('modal-rechazar').addEventListener('click', function(e) {
  if (e.target === this) cerrarModales();
});
</script>
