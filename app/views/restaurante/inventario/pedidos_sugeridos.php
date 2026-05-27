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
.estado-sugerido   { background:#DBEAFE;color:#1E40AF; }
.estado-aprobado   { background:#FEF3C7;color:#92400E; }
.estado-convertido { background:#DCFCE7;color:#14532D; }
.estado-rechazado  { background:#FEE2E2;color:#991B1B; }
.estado-borrador   { background:#F3F4F6;color:#6B7280; }

.btn-accion {
  display:inline-flex;align-items:center;gap:5px;padding:5px 12px;
  border-radius:7px;font-size:.78rem;font-weight:600;cursor:pointer;
  border:none;transition:opacity .15s;
}
.btn-aprobar  { background:#2563EB;color:#fff; }
.btn-enviar   { background:#059669;color:#fff; }
.btn-accion:disabled { opacity:.45;cursor:not-allowed; }
</style>

<!-- Top bar -->
<div class="ph-topbar">
  <div>
    <h2>📋 Pedidos Automáticos de Reabastecimiento</h2>
    <p style="font-size:.8rem;color:#6B7280;margin:2px 0 0">
      Generados por forecast · Apruébalos y envíalos a CarniHub con un clic
    </p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="<?= BASE_URL ?>rest-inventario/proyecciones" class="btn btn-primary btn-sm">📊 Ir a Proyecciones</a>
    <a href="<?= BASE_URL ?>rest-inventario/index" class="btn btn-outline btn-sm">← Inventario</a>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:16px"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>

<div id="toast-msg" style="display:none;position:fixed;top:20px;right:20px;z-index:9999;
     background:#1D4ED8;color:#fff;padding:12px 20px;border-radius:10px;font-size:.88rem;
     font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.18)"></div>

<div style="background:#EFF6FF;border:1.5px solid #BFDBFE;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:.84rem;color:#1E40AF">
  ℹ️ <strong>Flujo:</strong>
  <strong>Sugerido</strong> → (Aprobar) → <strong>Aprobado</strong> → (Enviar a CarniHub) → <strong>Convertido</strong>
</div>

<!-- Tabla -->
<div style="background:#fff;border:1.5px solid #E5E7EB;border-radius:14px;overflow:hidden">
  <div style="overflow-x:auto">
    <table class="ph-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Distribuidor</th>
          <th>Estado</th>
          <th>Total estimado</th>
          <th>Items</th>
          <th>Generado</th>
          <th style="text-align:center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pedidos)): ?>
        <tr>
          <td colspan="7" style="text-align:center;padding:48px 16px;color:#9CA3AF">
            <div style="font-size:2rem;margin-bottom:8px">🔭</div>
            <div style="font-weight:600">Sin pedidos automáticos aún</div>
            <div style="font-size:.8rem;margin-top:4px">
              Ve a <a href="<?= BASE_URL ?>rest-inventario/proyecciones" style="color:#2563EB">Proyecciones</a>
              cuando haya ingredientes críticos para auto-generar pedidos.
            </div>
          </td>
        </tr>
        <?php endif; ?>

        <?php foreach ($pedidos as $p): ?>
        <tr id="fila-<?= (int)$p['id'] ?>">
          <td>
            <span style="font-family:monospace;font-weight:700;color:#1D4ED8;font-size:.88rem">
              #<?= (int)$p['id'] ?>
            </span>
            <?php if (!empty($p['pedido_carnihub_id'])): ?>
            <br><span style="font-size:.72rem;color:#6B7280">
              CarniHub #<?= (int)$p['pedido_carnihub_id'] ?>
            </span>
            <?php endif; ?>
          </td>
          <td style="font-weight:600;color:#111827">
            <?= htmlspecialchars($p['empresa_nombre'] ?? '—') ?>
          </td>
          <td>
            <span class="estado-badge estado-<?= htmlspecialchars($p['estado']) ?>">
              <?php echo match($p['estado']) {
                'sugerido'  => '⏳ Sugerido',
                'aprobado'  => '✅ Aprobado',
                'convertido'=> '🚀 Enviado',
                'rechazado' => '✗ Rechazado',
                'borrador'  => '📝 Borrador',
                default     => htmlspecialchars($p['estado']),
              }; ?>
            </span>
          </td>
          <td>
            <strong style="color:#111827">$<?= number_format((float)$p['total_estimado'], 2) ?></strong>
          </td>
          <td style="color:#6B7280;font-size:.8rem">
            <?php
              // Contar items en una sub-consulta lazy (pasado desde el modelo o calcular inline)
              // Si el modelo no lo trae, mostramos '—'
              echo isset($p['items_count']) ? (int)$p['items_count'] . ' ings.' : '—';
            ?>
          </td>
          <td style="font-size:.78rem;color:#6B7280;white-space:nowrap">
            <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
            <?php if (!empty($p['usuario_nombre'])): ?>
            <br><span style="color:#9CA3AF">por <?= htmlspecialchars($p['usuario_nombre']) ?></span>
            <?php endif; ?>
          </td>
          <td style="text-align:center;white-space:nowrap">
            <?php if ($p['estado'] === 'sugerido'): ?>
              <button class="btn-accion btn-aprobar"
                      onclick="accionPedido('aprobar', <?= (int)$p['id'] ?>, this)">
                ✅ Aprobar
              </button>
            <?php elseif ($p['estado'] === 'aprobado'): ?>
              <button class="btn-accion btn-enviar"
                      onclick="accionPedido('enviar', <?= (int)$p['id'] ?>, this)">
                🚀 Enviar a CarniHub
              </button>
            <?php elseif ($p['estado'] === 'convertido'): ?>
              <span style="font-size:.78rem;color:#14532D;font-weight:600">Enviado ✓</span>
            <?php else: ?>
              <span style="font-size:.78rem;color:#9CA3AF">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';

function mostrarToast(msg, color) {
  const t = document.getElementById('toast-msg');
  t.textContent = msg;
  t.style.background = color || '#1D4ED8';
  t.style.display = 'block';
  setTimeout(() => t.style.display = 'none', 4000);
}

async function accionPedido(tipo, id, btn) {
  btn.disabled = true;
  const textoOrig = btn.textContent;
  btn.textContent = tipo === 'aprobar' ? 'Aprobando…' : 'Enviando…';

  const url = tipo === 'aprobar'
    ? BASE + 'rest-inventario/aprobarSugerido/' + id
    : BASE + 'rest-inventario/enviarACarnihub/' + id;

  try {
    const resp = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await resp.json();

    if (data.ok) {
      mostrarToast(data.message || 'Operación exitosa', '#059669');
      setTimeout(() => location.reload(), 1500);
    } else {
      mostrarToast('Error: ' + (data.error || 'Intenta de nuevo'), '#DC2626');
      btn.disabled = false;
      btn.textContent = textoOrig;
    }
  } catch (e) {
    mostrarToast('Error de conexión', '#DC2626');
    btn.disabled = false;
    btn.textContent = textoOrig;
  }
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
?>
