<?php
$tickets = $pendientes['tickets'] ?? [];
$pedidosApp = $pendientes['pedidos_app'] ?? [];
$money = static fn($value): string => '$' . number_format((float)$value, 2);
$esc = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
ob_start();
?>
<style>
.debt-wrap{max-width:1200px;margin:0 auto}.debt-hero{background:#111827;color:#fff;border-radius:18px;padding:24px;margin-bottom:20px}.debt-hero h1{margin:0 0 8px;font-size:1.5rem}.debt-hero p{margin:0;color:#cbd5e1;line-height:1.55}.debt-kpis{display:grid;grid-template-columns:repeat(2,minmax(0,220px));gap:12px;margin-top:18px}.debt-kpi{background:rgba(255,255,255,.09);border-radius:12px;padding:13px}.debt-kpi span{display:block;font-size:.78rem;color:#cbd5e1}.debt-kpi strong{display:block;font-size:1.3rem;margin-top:3px}.debt-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;margin-bottom:20px}.debt-card h2{font-size:1.05rem;margin:0 0 6px}.debt-note{font-size:.83rem;color:#64748b;line-height:1.5;margin:0 0 15px}.debt-table-wrap{overflow-x:auto}.debt-table{width:100%;border-collapse:collapse;font-size:.84rem}.debt-table th{text-align:left;color:#64748b;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;padding:10px;border-bottom:1px solid #e5e7eb;white-space:nowrap}.debt-table td{padding:12px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}.debt-table tr:last-child td{border-bottom:0}.debt-client{font-weight:700;color:#111827}.debt-meta{font-size:.77rem;color:#64748b;margin-top:3px}.debt-badge{display:inline-flex;padding:4px 8px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:.72rem;font-weight:700}.debt-form{display:grid;grid-template-columns:150px minmax(220px,1fr) auto;gap:8px;align-items:start;min-width:510px}.debt-form select,.debt-form input{width:100%;box-sizing:border-box;padding:9px 10px;border:1px solid #d1d5db;border-radius:8px;font:inherit}.debt-btn{border:0;border-radius:8px;padding:10px 13px;background:#166534;color:#fff;font-weight:700;cursor:pointer;white-space:nowrap}.debt-empty{padding:24px;text-align:center;color:#64748b;background:#f8fafc;border-radius:12px}.audit-list{display:grid;gap:0}.audit-item{padding:12px 0;border-bottom:1px solid #f1f5f9;font-size:.83rem}.audit-item:last-child{border-bottom:0}.audit-item strong{color:#111827}.audit-item span{color:#64748b}.debt-warning{border-left:4px solid #f59e0b;background:#fffbeb;color:#78350f;padding:12px 14px;border-radius:8px;font-size:.82rem;margin-bottom:15px}@media(max-width:700px){.debt-kpis{grid-template-columns:1fr 1fr}.debt-card{padding:14px}}
</style>

<div class="debt-wrap">
  <section class="debt-hero">
    <h1>Cuentas pendientes</h1>
    <p>Uso exclusivo de PROGRAMADOR. Permite reflejar manualmente un pago ya recibido sin borrar el ticket, pedido, visita ni historial del cliente.</p>
    <div class="debt-kpis">
      <div class="debt-kpi"><span>Cuentas detectadas</span><strong><?= (int)($pendientes['total_cuentas'] ?? 0) ?></strong></div>
      <div class="debt-kpi"><span>Monto pendiente</span><strong><?= $money($pendientes['monto_pendiente'] ?? 0) ?></strong></div>
    </div>
  </section>

  <div class="debt-warning"><strong>Importante:</strong> usa esta acción solo cuando el pago ya fue confirmado por otro medio. Cada ajuste queda registrado con usuario, fecha, método y motivo.</div>

  <?php
  $renderTable = static function (array $rows, string $tipo, string $emptyText) use ($money, $esc): void {
  ?>
    <?php if (!$rows): ?>
      <div class="debt-empty"><?= $esc($emptyText) ?></div>
    <?php else: ?>
      <div class="debt-table-wrap">
        <table class="debt-table">
          <thead><tr><th>Usuario / cuenta</th><th>Registro</th><th>Estado</th><th>Monto</th><th>Regularizar pago</th></tr></thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td>
                <div class="debt-client"><?= $esc($row['cliente_nombre'] ?? 'Cliente sin identificar') ?></div>
                <div class="debt-meta">
                  <?php if (!empty($row['cliente_email'])): ?><?= $esc($row['cliente_email']) ?><?php endif; ?>
                  <?php if (!empty($row['cliente_telefono'])): ?> · <?= $esc($row['cliente_telefono']) ?><?php endif; ?>
                  <?php if (!empty($row['mobile_usuario_id'])): ?> · App #<?= (int)$row['mobile_usuario_id'] ?><?php endif; ?>
                </div>
              </td>
              <td><strong><?= $esc($row['folio'] ?? ('#' . $row['registro_id'])) ?></strong><div class="debt-meta"><?= $esc($row['mesa_nombre'] ?? ($tipo === 'ticket' ? 'Mesa no asignada' : 'Pedido de app')) ?> · <?= $esc($row['created_at'] ?? '') ?></div></td>
              <td><span class="debt-badge">Pago pendiente</span><div class="debt-meta"><?= $esc($row['estado'] ?? '') ?><?= !empty($row['visita_estado']) ? ' · visita ' . $esc($row['visita_estado']) : '' ?></div></td>
              <td><strong><?= $money($row['monto'] ?? 0) ?></strong></td>
              <td>
                <form class="debt-form" method="POST" action="<?= BASE_URL ?>rest-finanzas/regularizarAdeudo" onsubmit="return confirm('¿Confirmas que este pago ya fue recibido y deseas retirar el adeudo?')">
                  <input type="hidden" name="tipo_registro" value="<?= $esc($tipo) ?>">
                  <input type="hidden" name="registro_id" value="<?= (int)$row['registro_id'] ?>">
                  <select name="metodo_pago" required aria-label="Método de pago">
                    <option value="">Método de pago</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="paypal">PayPal</option>
                  </select>
                  <input type="text" name="motivo" minlength="5" maxlength="500" placeholder="Motivo, referencia o comprobante" required>
                  <button class="debt-btn" type="submit">Quitar adeudo</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php }; ?>

  <section class="debt-card">
    <h2>Tickets de mesa pendientes</h2>
    <p class="debt-note">Al regularizar, el ticket pasa a pagado y se sincronizan la visita, los pedidos relacionados, sus artículos y la solicitud de cuenta.</p>
    <?php $renderTable($tickets, 'ticket', 'No hay tickets de mesa con pago pendiente.'); ?>
  </section>

  <section class="debt-card">
    <h2>Pedidos de app con pago no reflejado</h2>
    <p class="debt-note">Solo se registra la fecha y método de pago. El estado de preparación o entrega del pedido permanece intacto.</p>
    <?php $renderTable($pedidosApp, 'pedido_app', 'No hay pedidos independientes de app con pago pendiente.'); ?>
  </section>

  <section class="debt-card">
    <h2>Bitácora de regularizaciones</h2>
    <div class="audit-list">
      <?php foreach ($historial as $evento): ?>
        <div class="audit-item">
          <strong><?= $esc($evento['folio'] ?: ('#' . $evento['registro_id'])) ?> · <?= $esc($evento['cliente_referencia'] ?? '') ?> · <?= $money($evento['monto'] ?? 0) ?></strong><br>
          <span><?= $esc($evento['created_at'] ?? '') ?> · <?= $esc($evento['programador_nombre'] ?? 'Programador') ?> · <?= $esc($evento['metodo_pago'] ?? '') ?> · <?= $esc($evento['motivo'] ?? '') ?></span>
        </div>
      <?php endforeach; ?>
      <?php if (!$historial): ?><div class="debt-empty">Aún no se han realizado regularizaciones.</div><?php endif; ?>
    </div>
  </section>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
