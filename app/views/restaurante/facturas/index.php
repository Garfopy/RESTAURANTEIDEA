<?php ob_start(); ?>
<?php
if (!function_exists('facturaEstadoBadge')) {
    function facturaEstadoBadge(string $estado): string
    {
        $map = [
            'pendiente' => ['badge-amber', 'Pendiente'],
            'en_proceso' => ['badge-blue', 'En proceso'],
            'facturada' => ['badge-green', 'Facturada'],
            'cancelada' => ['badge-gray', 'Cancelada'],
        ];
        [$class, $label] = $map[$estado] ?? ['badge-gray', ucfirst($estado)];
        return '<span class="badge ' . $class . '">' . htmlspecialchars($label) . '</span>';
    }
}

$rows = $solicitudes['data'] ?? [];
$currentPage = (int)($solicitudes['current_page'] ?? 1);
$lastPage = max(1, (int)($solicitudes['last_page'] ?? 1));
$hasInvoiceModule = isset($solicitudes) && (is_array($solicitudes) || is_object($solicitudes));
?>

<style>
.invoice-shell {
  display: grid;
  gap: 18px;
}
.invoice-hero {
  align-items: center;
  background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 58%, color-mix(in srgb, var(--cp) 10%, white) 100%);
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  box-shadow: 0 18px 55px rgba(15,23,42,.06);
  display: flex;
  gap: 18px;
  justify-content: space-between;
  padding: 22px;
}
.invoice-title {
  color: #0F172A;
  font-size: 1.45rem;
  font-weight: 800;
  margin: 0;
}
.invoice-copy {
  color: #64748B;
  font-size: .9rem;
  line-height: 1.55;
  margin: 6px 0 0;
  max-width: 720px;
}
.invoice-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.invoice-grid {
  display: grid;
  gap: 14px;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}
.invoice-step {
  background: rgba(255,255,255,.94);
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  padding: 18px;
}
.invoice-step span {
  align-items: center;
  background: var(--cp-light);
  border-radius: 999px;
  color: var(--cp);
  display: inline-flex;
  font-size: .75rem;
  font-weight: 800;
  height: 28px;
  justify-content: center;
  margin-bottom: 12px;
  width: 28px;
}
.invoice-step strong {
  color: #111827;
  display: block;
  font-size: .98rem;
  margin-bottom: 6px;
}
.invoice-step p {
  color: #64748B;
  font-size: .84rem;
  line-height: 1.5;
  margin: 0;
}
.invoice-empty {
  background: #FFFFFF;
  border: 1px dashed #CBD5E1;
  border-radius: 12px;
  color: #64748B;
  padding: 28px;
  text-align: center;
}
@media (max-width: 860px) {
  .invoice-hero { align-items: flex-start; flex-direction: column; }
  .invoice-grid { grid-template-columns: 1fr; }
}
</style>

<?php if (!$hasInvoiceModule): ?>
<div class="invoice-shell">
  <section class="invoice-hero">
    <div>
      <h1 class="invoice-title">Facturas</h1>
      <p class="invoice-copy">
        El apartado ya carga correctamente dentro del panel. Aun no hay un modulo CFDI conectado en esta instalacion,
        por eso dejamos esta vista preparada con accesos a ventas y cortes para revisar los importes a facturar.
      </p>
    </div>
    <div class="invoice-actions">
      <a class="btn btn-primary" href="<?= BASE_URL ?>rest-finanzas/ventas">Ver ventas</a>
      <a class="btn btn-outline" href="<?= BASE_URL ?>rest-finanzas/cortes">Ver cortes</a>
    </div>
  </section>

  <section class="invoice-grid" aria-label="Flujo de facturacion">
    <article class="invoice-step">
      <span>1</span>
      <strong>Revisa ventas</strong>
      <p>Valida productos, propinas y totales desde el reporte de ventas antes de emitir comprobantes.</p>
    </article>
    <article class="invoice-step">
      <span>2</span>
      <strong>Cuadra corte</strong>
      <p>Usa cortes de caja para confirmar ingresos, retiros y metodos de pago del periodo.</p>
    </article>
    <article class="invoice-step">
      <span>3</span>
      <strong>Conecta CFDI</strong>
      <p>Cuando se agregue el proveedor fiscal, aqui se listaran facturas, estados y descargas.</p>
    </article>
  </section>

  <div class="invoice-empty">
    No hay facturas registradas todavia.
  </div>
</div>
<?php else: ?>
<div class="rst-card">
  <form method="GET" action="<?= BASE_URL ?>rest-factura/index" style="display:grid;grid-template-columns:1fr 1fr 1fr auto auto;gap:12px;align-items:end">
    <div>
      <label class="form-label">Estado</label>
      <select name="estado" class="form-input">
        <?php foreach (['' => 'Todos', 'pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'facturada' => 'Facturada', 'cancelada' => 'Cancelada'] as $value => $label): ?>
        <option value="<?= $value ?>" <?= ($filtros['estado'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="form-label">Desde</label>
      <input type="date" name="from" class="form-input" value="<?= htmlspecialchars($filtros['from'] ?? '') ?>">
    </div>
    <div>
      <label class="form-label">Hasta</label>
      <input type="date" name="to" class="form-input" value="<?= htmlspecialchars($filtros['to'] ?? '') ?>">
    </div>
    <button class="btn btn-primary" type="submit">Filtrar</button>
    <a class="btn btn-outline" href="<?= BASE_URL ?>rest-factura/index?estado=pendiente">Pendientes</a>
  </form>
</div>

<div class="rst-table-wrap">
  <table class="rst-table">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Origen</th>
        <th>Tipo</th>
        <th>Referencia</th>
        <th>RFC receptor</th>
        <th>Nombre fiscal</th>
        <th>Monto</th>
        <th>Pago</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
      <tr>
        <td colspan="10">
          <div class="empty-state" style="padding:34px 20px">No hay solicitudes de factura con estos filtros.</div>
        </td>
      </tr>
      <?php endif; ?>
      <?php foreach ($rows as $s): ?>
      <?php
        $ref = $s['pedido_folio'] ? 'Pedido ' . $s['pedido_folio'] : '';
        if ($s['ticket_folio']) {
            $ref .= ($ref ? ' / ' : '') . 'Ticket ' . $s['ticket_folio'];
        }
        if ($s['mesa_nombre']) {
            $ref .= ($ref ? ' / ' : '') . 'Mesa ' . $s['mesa_nombre'];
        }
        $ref = $ref ?: ('Solicitud #' . (int)$s['id']);
      ?>
      <tr>
        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($s['created_at']))) ?></td>
        <td><?= htmlspecialchars($s['origen']) ?></td>
        <td><?= htmlspecialchars(str_replace('_', ' ', $s['scope'])) ?></td>
        <td><?= htmlspecialchars($ref) ?></td>
        <td style="font-family:monospace"><?= htmlspecialchars($s['receptor_rfc']) ?></td>
        <td><?= htmlspecialchars($s['receptor_nombre_fiscal']) ?></td>
        <td>$<?= number_format((float)$s['monto'], 2) ?></td>
        <td><?= htmlspecialchars($s['metodo_pago'] ?? '-') ?></td>
        <td><?= facturaEstadoBadge($s['estado']) ?></td>
        <td><a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>rest-factura/detalle/<?= (int)$s['id'] ?>">Ver</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($lastPage > 1): ?>
<div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px">
  <?php
    $query = $_GET;
    $query['page'] = max(1, $currentPage - 1);
  ?>
  <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>rest-factura/index?<?= htmlspecialchars(http_build_query($query)) ?>" <?= $currentPage <= 1 ? 'style="pointer-events:none;opacity:.5"' : '' ?>>Anterior</a>
  <span style="font-size:.82rem;color:#6B7280;align-self:center">Pagina <?= $currentPage ?> de <?= $lastPage ?></span>
  <?php $query['page'] = min($lastPage, $currentPage + 1); ?>
  <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>rest-factura/index?<?= htmlspecialchars(http_build_query($query)) ?>" <?= $currentPage >= $lastPage ? 'style="pointer-events:none;opacity:.5"' : '' ?>>Siguiente</a>
</div>
<?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
