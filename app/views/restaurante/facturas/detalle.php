<?php ob_start(); ?>
<?php
if (!function_exists('facturaEstadoBadge')) {
    function facturaEstadoBadge(string $estado): string {
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
$ref = $solicitud['pedido_folio'] ? 'Pedido ' . $solicitud['pedido_folio'] : '';
if ($solicitud['ticket_folio']) $ref .= ($ref ? ' / ' : '') . 'Ticket ' . $solicitud['ticket_folio'];
if ($solicitud['mesa_nombre']) $ref .= ($ref ? ' / ' : '') . 'Mesa ' . $solicitud['mesa_nombre'];
$ref = $ref ?: 'Sin referencia';
?>

<div style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:16px">
  <a href="<?= BASE_URL ?>rest-factura/index" class="btn btn-outline btn-sm">Volver</a>
  <?php if ($solicitud['estado'] === 'pendiente'): ?>
  <form method="POST" action="<?= BASE_URL ?>rest-factura/marcarProceso/<?= (int)$solicitud['id'] ?>">
    <button class="btn btn-primary btn-sm" type="submit">Marcar en proceso</button>
  </form>
  <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:18px;align-items:start">
  <div class="rst-card">
    <div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:18px">
      <div>
        <div style="font-size:.78rem;color:#6B7280;margin-bottom:4px">Solicitud</div>
        <div style="font-size:1.1rem;font-weight:800">#<?= (int)$solicitud['id'] ?> &middot; <?= htmlspecialchars($ref) ?></div>
      </div>
      <div><?= facturaEstadoBadge($solicitud['estado']) ?></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div>
        <div class="form-label">RFC receptor</div>
        <div style="font-family:monospace;font-weight:700"><?= htmlspecialchars($solicitud['receptor_rfc']) ?></div>
      </div>
      <div>
        <div class="form-label">Nombre fiscal receptor</div>
        <div><?= htmlspecialchars($solicitud['receptor_nombre_fiscal']) ?></div>
      </div>
      <div>
        <div class="form-label">Regimen fiscal</div>
        <div><?= htmlspecialchars($solicitud['receptor_regimen_fiscal'] ?: '-') ?></div>
      </div>
      <div>
        <div class="form-label">Codigo postal</div>
        <div><?= htmlspecialchars($solicitud['receptor_codigo_postal'] ?: '-') ?></div>
      </div>
      <div>
        <div class="form-label">Uso CFDI</div>
        <div><?= htmlspecialchars($solicitud['receptor_uso_cfdi'] ?: '-') ?></div>
      </div>
      <div>
        <div class="form-label">Email</div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <span id="receptorEmail"><?= htmlspecialchars($solicitud['receptor_email'] ?: '-') ?></span>
          <?php if (!empty($solicitud['receptor_email'])): ?>
          <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('receptorEmail').textContent)">Copiar</button>
          <?php endif; ?>
        </div>
      </div>
      <div>
        <div class="form-label">Monto</div>
        <div style="font-weight:800">$<?= number_format((float)$solicitud['monto'], 2) ?></div>
      </div>
      <div>
        <div class="form-label">Metodo de pago</div>
        <div><?= htmlspecialchars($solicitud['metodo_pago'] ?: '-') ?></div>
      </div>
      <div>
        <div class="form-label">Origen</div>
        <div><?= htmlspecialchars($solicitud['origen']) ?></div>
      </div>
      <div>
        <div class="form-label">Tipo</div>
        <div><?= htmlspecialchars(str_replace('_', ' ', $solicitud['scope'])) ?></div>
      </div>
      <div>
        <div class="form-label">Fecha de solicitud</div>
        <div><?= htmlspecialchars(date('d/m/Y H:i', strtotime($solicitud['created_at']))) ?></div>
      </div>
    </div>
  </div>

  <div class="rst-card">
    <div style="font-weight:700;margin-bottom:14px">Actualizar solicitud</div>
    <form method="POST" action="<?= BASE_URL ?>rest-factura/actualizar/<?= (int)$solicitud['id'] ?>" onsubmit="return validarFacturaForm(this)">
      <div class="form-group">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-input" id="estadoFactura">
          <?php foreach (['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'facturada' => 'Facturada', 'cancelada' => 'Cancelada'] as $value => $label): ?>
          <option value="<?= $value ?>" <?= $solicitud['estado'] === $value ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">UUID CFDI</label>
        <input type="text" name="cfdi_uuid" class="form-input" value="<?= htmlspecialchars($solicitud['cfdi_uuid'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">URL PDF</label>
        <input type="url" name="pdf_url" class="form-input" value="<?= htmlspecialchars($solicitud['pdf_url'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">URL XML</label>
        <input type="url" name="xml_url" class="form-input" value="<?= htmlspecialchars($solicitud['xml_url'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Notas</label>
        <textarea name="notas" class="form-textarea" rows="4"><?= htmlspecialchars($solicitud['notas'] ?? '') ?></textarea>
      </div>
      <button class="btn btn-primary" type="submit">Guardar cambios</button>
    </form>
  </div>
</div>

<script>
function validarFacturaForm(form) {
  if (form.estado.value !== 'facturada') return true;
  const required = ['cfdi_uuid', 'pdf_url', 'xml_url'];
  for (const name of required) {
    if (!form.elements[name].value.trim()) {
      alert('UUID, PDF y XML son obligatorios para marcar como facturada.');
      form.elements[name].focus();
      return false;
    }
  }
  return true;
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
