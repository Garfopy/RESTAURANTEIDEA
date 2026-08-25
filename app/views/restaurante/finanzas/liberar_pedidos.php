<?php
$esc = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$money = static fn($value): string => '$' . number_format((float)$value, 2);
$estadoLabels = [
    'pendiente' => 'Pendiente',
    'en_preparacion' => 'En preparación',
    'listo' => 'Listo',
    'reclamado' => 'Reclamado',
    'entregado' => 'Entregado',
    'cancelado' => 'Cancelado',
];
ob_start();
?>
<style>
.release-wrap{max-width:1250px;margin:0 auto}.release-hero{background:#111827;color:#fff;border-radius:18px;padding:24px;margin-bottom:20px}.release-hero h1{margin:0 0 8px;font-size:1.5rem}.release-hero p{margin:0;color:#cbd5e1;line-height:1.55}.release-warning{margin-top:15px;background:rgba(245,158,11,.13);border:1px solid rgba(245,158,11,.35);color:#fde68a;border-radius:10px;padding:11px 13px;font-size:.82rem}.release-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;margin-bottom:20px}.release-card h2{font-size:1.05rem;margin:0 0 14px}.release-filter{display:grid;grid-template-columns:minmax(220px,1fr) 190px auto auto;gap:9px;margin-bottom:18px}.release-filter input,.release-filter select,.release-form input{width:100%;box-sizing:border-box;padding:10px 11px;border:1px solid #d1d5db;border-radius:9px;font:inherit}.release-filter button,.release-filter a,.release-btn{border:0;border-radius:9px;padding:10px 14px;font-weight:700;text-decoration:none;cursor:pointer;white-space:nowrap}.release-filter button{background:#111827;color:#fff}.release-filter a{background:#f1f5f9;color:#334155;text-align:center}.release-table-wrap{overflow-x:auto}.release-table{width:100%;border-collapse:collapse;font-size:.84rem}.release-table th{text-align:left;color:#64748b;font-size:.74rem;text-transform:uppercase;padding:10px;border-bottom:1px solid #e5e7eb;white-space:nowrap}.release-table td{padding:12px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}.release-table tr:last-child td{border-bottom:0}.release-client{font-weight:800;color:#111827}.release-meta{color:#64748b;font-size:.76rem;margin-top:3px}.release-badge{display:inline-flex;padding:4px 8px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:.72rem;font-weight:800}.release-badge.cancelado{background:#fee2e2;color:#991b1b}.release-badge.entregado{background:#dcfce7;color:#166534}.release-badge.pendiente{background:#dbeafe;color:#1e40af}.release-badge.en_preparacion{background:#fef3c7;color:#92400e}.release-badge.listo,.release-badge.reclamado{background:#ede9fe;color:#5b21b6}.release-form{display:grid;grid-template-columns:minmax(230px,1fr) auto;gap:8px;min-width:430px}.release-btn{background:#1d4ed8;color:#fff}.release-btn.danger{background:#b91c1c}.release-done{font-size:.78rem;color:#166534;font-weight:800}.release-empty{padding:28px;text-align:center;background:#f8fafc;border-radius:12px;color:#64748b}.audit-item{padding:12px 0;border-bottom:1px solid #f1f5f9;font-size:.83rem}.audit-item:last-child{border-bottom:0}.audit-item strong{color:#111827}.audit-item span{color:#64748b}@media(max-width:760px){.release-filter{grid-template-columns:1fr}.release-card{padding:14px}}
</style>

<div class="release-wrap">
  <section class="release-hero">
    <h1>Superadministraci&oacute;n · Liberar pedidos</h1>
    <p>Herramienta para cerrar pedidos atascados en cualquier estado operativo.</p>
    <div class="release-warning"><strong>Importante:</strong> liberar marca el pedido y todos sus artículos como entregados. No registra pagos, no modifica tickets, puntos ni valida la salida del cliente.</div>
  </section>

  <section class="release-card">
    <form class="release-filter" method="GET" action="<?= BASE_URL ?>rest-finanzas/liberarPedidos">
      <input type="search" name="q" value="<?= $esc($busqueda) ?>" placeholder="Buscar folio, cliente, correo o teléfono">
      <select name="estado">
        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos los estados</option>
        <?php foreach ($estadoLabels as $value => $label): ?>
          <option value="<?= $esc($value) ?>" <?= $estado === $value ? 'selected' : '' ?>><?= $esc($label) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Buscar</button>
      <a href="<?= BASE_URL ?>rest-finanzas/liberarPedidos">Limpiar</a>
    </form>

    <?php if (!$pedidos): ?>
      <div class="release-empty">No se encontraron pedidos con esos filtros.</div>
    <?php else: ?>
      <div class="release-table-wrap">
        <table class="release-table">
          <thead><tr><th>Pedido</th><th>Cliente</th><th>Estado</th><th>Pago</th><th>Total</th><th>Liberación forzada</th></tr></thead>
          <tbody>
          <?php foreach ($pedidos as $pedido):
            $estadoPedido = (string)($pedido['estado'] ?? '');
            $liberado = $estadoPedido === 'entregado'
                && (int)($pedido['items_total'] ?? 0) === (int)($pedido['items_entregados'] ?? 0);
          ?>
            <tr>
              <td>
                <div class="release-client"><?= $esc($pedido['folio'] ?? ('#' . $pedido['pedido_id'])) ?></div>
                <div class="release-meta"><?= $esc($pedido['mesa_nombre'] ?: ($pedido['tipo_origen'] ?: 'Sin mesa')) ?> · <?= $esc($pedido['created_at'] ?? '') ?></div>
                <div class="release-meta"><?= (int)($pedido['items_entregados'] ?? 0) ?>/<?= (int)($pedido['items_total'] ?? 0) ?> artículos entregados</div>
              </td>
              <td>
                <div class="release-client"><?= $esc($pedido['cliente_nombre'] ?? 'Cliente sin identificar') ?></div>
                <div class="release-meta">Correo: <?= !empty($pedido['cliente_email']) ? $esc($pedido['cliente_email']) : 'Sin correo asociado' ?></div>
                <?php if (!empty($pedido['cliente_telefono'])): ?><div class="release-meta">Tel: <?= $esc($pedido['cliente_telefono']) ?></div><?php endif; ?>
              </td>
              <td><span class="release-badge <?= $esc($estadoPedido) ?>"><?= $esc($estadoLabels[$estadoPedido] ?? $estadoPedido) ?></span></td>
              <td>
                <?php if (!empty($pedido['pagado_at'])): ?>
                  <span class="release-badge entregado">Registrado</span><div class="release-meta"><?= $esc($pedido['pagado_at']) ?></div>
                <?php else: ?>
                  <span class="release-badge cancelado">No reflejado</span>
                <?php endif; ?>
              </td>
              <td><strong><?= $money($pedido['total'] ?: $pedido['subtotal']) ?></strong></td>
              <td>
                <?php if ($liberado): ?>
                  <span class="release-done">Pedido completamente liberado</span>
                <?php else: ?>
                  <form class="release-form" method="POST" action="<?= BASE_URL ?>rest-finanzas/liberarPedidoManual" onsubmit="return confirm('¿Confirmas la liberación forzada? El pedido y todos sus artículos pasarán a ENTREGADO. El pago no será modificado.')">
                    <input type="hidden" name="pedido_id" value="<?= (int)$pedido['pedido_id'] ?>">
                    <input type="text" name="motivo" minlength="5" maxlength="500" placeholder="Motivo de la liberación" required>
                    <button class="release-btn <?= $estadoPedido === 'cancelado' ? 'danger' : '' ?>" type="submit"><?= $estadoPedido === 'cancelado' ? 'Reabrir y liberar' : 'Liberar pedido' ?></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="release-card">
    <h2>Bitácora de liberaciones</h2>
    <?php foreach ($historialLiberaciones as $evento): ?>
      <div class="audit-item">
        <strong><?= $esc($evento['folio'] ?: ('Pedido #' . $evento['pedido_id'])) ?> · <?= $esc($evento['cliente_referencia'] ?? '') ?> · <?= $esc($evento['estado_anterior']) ?> → entregado</strong><br>
        <span><?= $esc($evento['created_at'] ?? '') ?> · <?= $esc($evento['programador_nombre'] ?? 'Superadministrador') ?> · <?= $esc($evento['motivo'] ?? '') ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$historialLiberaciones): ?><div class="release-empty">Aún no se han realizado liberaciones forzadas.</div><?php endif; ?>
  </section>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
