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
$rol = $_SESSION['usuario']['rol_slug'] ?? '';
?>

<!-- Flash -->
<?php if ($flash): ?>
<div style="margin-bottom:16px;padding:12px 16px;border-radius:8px;font-size:.875rem;font-weight:500;
  <?= $flash['type'] === 'success' ? 'background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0' : 'background:#FEE2E2;color:#991B1B;border:1px solid #FECACA' ?>">
  <?= htmlspecialchars($flash['message']) ?>
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
  <a href="<?= $baseUrl ?>empresa-pedido/personalizado"
     style="padding:9px 18px;background:var(--color-primary);color:#fff;border-radius:8px;text-decoration:none;font-weight:700;font-size:.85rem;white-space:nowrap">
    + Pedido Personalizado
  </a>
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
        <th style="padding:10px 16px;text-align:center;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Tipo</th>
        <th style="padding:10px 16px;text-align:right;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Total</th>
        <th style="padding:10px 16px;text-align:left;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Fecha</th>
        <th style="padding:10px 16px;text-align:center;font-size:.7rem;color:#6B7280;font-weight:600;text-transform:uppercase">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $p): ?>
      <?php
        $est = $estados[$p['estado']] ?? ['label' => $p['estado'], 'bg' => '#F3F4F6', 'tx' => '#374151'];
        $esPersonalizado = ($p['tipo'] ?? 'normal') === 'personalizado';
      ?>
      <tr style="border-bottom:1px solid #F3F4F6">
        <td style="padding:10px 16px">
          <div style="font-weight:700;font-size:.85rem;color:#111827;font-family:monospace"><?= htmlspecialchars($p['folio']) ?></div>
        </td>
        <td style="padding:10px 16px;font-size:.85rem;color:#374151">
          <?= htmlspecialchars($p['comprador_nombre'] . ' ' . $p['comprador_apellido']) ?>
        </td>
        <td style="padding:10px 16px;text-align:center">
          <span style="padding:3px 10px;border-radius:999px;background:<?= $est['bg'] ?>;color:<?= $est['tx'] ?>;font-size:.7rem;font-weight:700">
            <?= $est['label'] ?>
          </span>
        </td>
        <td style="padding:10px 16px;text-align:center">
          <?php if ($esPersonalizado): ?>
          <span style="padding:2px 8px;border-radius:999px;background:#F3E8FF;color:#6B21A8;font-size:.65rem;font-weight:700">
            Personalizado
          </span>
          <?php else: ?>
          <span style="font-size:.75rem;color:#9CA3AF">Normal</span>
          <?php endif; ?>
        </td>
        <td style="padding:10px 16px;text-align:right;font-size:.9rem;font-weight:700;color:#111827">
          $<?= number_format((float)$p['total'], 2) ?>
        </td>
        <td style="padding:10px 16px;font-size:.78rem;color:#6B7280">
          <?= date('d/m/Y', strtotime($p['created_at'])) ?>
        </td>
        <td style="padding:10px 16px;text-align:center">
          <div style="display:flex;justify-content:center;gap:6px;flex-wrap:wrap">
            <a href="<?= $baseUrl ?>pedido/detalle/<?= $p['id'] ?>"
               style="padding:5px 10px;border:1px solid #D1D5DB;border-radius:6px;color:#374151;text-decoration:none;font-size:.75rem">
              Ver
            </a>
            <!-- Cambiar estado rápido -->
            <button onclick="abrirCambioEstado(<?= $p['id'] ?>, '<?= $p['estado'] ?>')"
                    style="padding:5px 10px;border:1px solid #D1D5DB;border-radius:6px;color:#374151;background:#fff;cursor:pointer;font-size:.75rem">
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

<!-- Modal cambio de estado -->
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
      <div style="margin-bottom:20px">
        <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px">Nota (opcional)</label>
        <input type="text" name="nota" placeholder="Ej: Paquete entregado a cliente..."
               style="width:100%;padding:9px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;box-sizing:border-box">
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
function abrirCambioEstado(id, estadoActual) {
  document.getElementById('modalPedidoId').value = id;
  document.getElementById('modalEstadoSelect').value = estadoActual;
  document.getElementById('modalEstado').style.display = 'flex';
}
document.getElementById('modalEstado').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
