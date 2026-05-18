<?php ob_start(); ?>

<style>
.ph-topbar { display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px; }
.ph-topbar h2 { font-size:1.1rem;font-weight:700;color:#111827;margin:0 }

.ph-table { width:100%;border-collapse:collapse;font-size:.85rem; }
.ph-table th { background:#F9FAFB;padding:10px 14px;text-align:left;font-weight:600;color:#374151;border-bottom:1.5px solid #E5E7EB;white-space:nowrap; }
.ph-table td { padding:11px 14px;border-bottom:1px solid #F3F4F6;vertical-align:middle; }
.ph-table tr:hover td { background:#FAFAFA; }

.estado-badge {
  display:inline-flex;align-items:center;gap:4px;
  padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:700;white-space:nowrap;
}
.estado-pendiente      { background:#DBEAFE;color:#1E40AF; }
.estado-confirmado     { background:#D1FAE5;color:#065F46; }
.estado-en_preparacion { background:#FEF3C7;color:#92400E; }
.estado-en_ruta        { background:#E0E7FF;color:#3730A3; }
.estado-entregado      { background:#DCFCE7;color:#14532D; }
.estado-cancelado      { background:#FEE2E2;color:#991B1B; }
</style>

<!-- Top bar -->
<div class="ph-topbar">
  <div>
    <h2>ðŸ“‹ Historial de Pedidos AutomÃ¡ticos</h2>
    <p style="font-size:.8rem;color:#6B7280;margin:2px 0 0">
      Pedidos generados por el sistema de forecast Â· enviados directamente a proveedores CarniHub
    </p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>rest-inventario/proyecciones" class="btn btn-primary btn-sm">ðŸ“Š Ir a Proyecciones</a>
    <a href="<?= BASE_URL ?>rest-inventario/index" class="btn btn-outline btn-sm">â† Inventario</a>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:16px"><?= $flash['message'] ?></div>
<?php endif; ?>

<div style="background:#EFF6FF;border:1.5px solid #BFDBFE;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:.84rem;color:#1E40AF">
  â„¹ï¸ Estos pedidos fueron <strong>enviados automÃ¡ticamente</strong> al proveedor. Cuando la empresa los marque como
  <strong>entregado</strong>, el stock del inventario se actualizarÃ¡ automÃ¡ticamente.
</div>

<!-- Tabla de historial -->
<div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;overflow:hidden">
  <div style="overflow-x:auto">
    <table class="ph-table">
      <thead>
        <tr>
          <th>Folio</th>
          <th>Empresa proveedora</th>
          <th>Estado del pedido</th>
          <th>Total</th>
          <th>Generado</th>
          <th style="text-align:right">Detalle</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pedidos)): ?>
        <tr>
          <td colspan="6" style="text-align:center;padding:48px 16px;color:#9CA3AF">
            <div style="font-size:2rem;margin-bottom:8px">ðŸ“­</div>
            <div style="font-weight:600">Sin pedidos automÃ¡ticos aÃºn</div>
            <div style="font-size:.8rem;margin-top:4px">
              Ve a <a href="<?= BASE_URL ?>rest-inventario/proyecciones" style="color:#2563EB">Proyecciones</a>
              y haz clic en "Generar pedidos automÃ¡ticamente" cuando haya ingredientes crÃ­ticos.
            </div>
          </td>
        </tr>
        <?php endif; ?>
        <?php foreach ($pedidos as $p): ?>
        <tr>
          <td>
            <span style="font-family:monospace;font-weight:700;color:#1D4ED8;font-size:.88rem">
              <?= htmlspecialchars($p['folio']) ?>
            </span>
          </td>
          <td style="font-weight:600;color:#111827">
            <?= htmlspecialchars($p['empresa_nombre']) ?>
          </td>
          <td>
            <span class="estado-badge estado-<?= str_replace('_','',$p['estado']) ?>">
              <?php echo match($p['estado']) {
                'pendiente'      => 'â³ Pendiente',
                'confirmado'     => 'âœ… Confirmado',
                'en_preparacion' => 'ðŸ”§ En preparaciÃ³n',
                'en_ruta'        => 'ðŸšš En ruta',
                'entregado'      => 'âœ… Entregado',
                'cancelado'      => 'âœ— Cancelado',
                default          => $p['estado'],
              }; ?>
            </span>
          </td>
          <td>
            <strong style="color:#111827">$<?= number_format((float)$p['total'], 2) ?></strong>
          </td>
          <td style="font-size:.78rem;color:#6B7280;white-space:nowrap">
            <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
          </td>
          <td style="text-align:right">
            <a href="<?= BASE_URL ?>empresa-pedido/detalle/<?= $p['id'] ?>"
               class="btn btn-outline btn-xs" target="_blank">
              ðŸ‘ Ver pedido
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
?>
