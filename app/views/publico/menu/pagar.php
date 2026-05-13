<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pagar cuenta — <?= htmlspecialchars($restaurante['nombre'] ?? '') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/restaurant.css">
  <style>
    :root {
      --cp: <?= htmlspecialchars($restaurante['color_primario'] ?? '#C8102E') ?>;
      --cs: <?= htmlspecialchars($restaurante['color_secundario'] ?? '#1f2937') ?>;
    }
    body { background: var(--cs); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
  </style>
</head>
<body>
<div style="width:100%;max-width:420px">
  <!-- Header marca -->
  <div style="text-align:center;margin-bottom:20px;color:#fff">
    <?php if (!empty($restaurante['logo'])): ?>
    <img src="<?= BASE_URL . htmlspecialchars($restaurante['logo']) ?>" alt=""
         style="height:48px;object-fit:contain;margin-bottom:8px">
    <?php endif; ?>
    <div style="font-weight:700;font-size:1.1rem"><?= htmlspecialchars($restaurante['nombre']) ?></div>
  </div>

  <!-- Ticket card -->
  <div class="rst-card" style="border-radius:20px;padding:28px;margin-bottom:0">
    <div style="text-align:center;margin-bottom:20px;padding-bottom:16px;border-bottom:1px dashed #E5E7EB">
      <div style="font-size:.75rem;color:#9CA3AF;font-weight:600;letter-spacing:.06em;text-transform:uppercase;margin-bottom:4px">
        Tu cuenta
      </div>
      <div style="font-size:1.1rem;font-weight:700;color:#111827"><?= htmlspecialchars($ticket['folio'] ?? '') ?></div>
      <?php if (!empty($ticket['mesa_nombre'])): ?>
      <div style="font-size:.85rem;color:#6B7280;margin-top:4px">Mesa: <?= htmlspecialchars($ticket['mesa_nombre']) ?></div>
      <?php endif; ?>
    </div>

    <!-- Detalle montos -->
    <div style="space-y:8px">
      <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:.9rem">
        <span style="color:#6B7280">Subtotal</span>
        <span>$<?= number_format((float)($ticket['subtotal'] ?? 0), 2) ?></span>
      </div>
      <?php if (($ticket['propina'] ?? 0) > 0): ?>
      <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:.9rem">
        <span style="color:#10B981;font-weight:500">Propina</span>
        <span style="color:#10B981">$<?= number_format((float)$ticket['propina'], 2) ?></span>
      </div>
      <?php endif; ?>
      <div style="display:flex;justify-content:space-between;padding:12px 0;border-top:2px solid #F3F4F6;font-size:1.2rem;font-weight:800">
        <span>Total</span>
        <span style="color:var(--cp)">$<?= number_format((float)($ticket['total'] ?? 0), 2) ?></span>
      </div>
    </div>

    <?php if (($ticket['estado'] ?? '') === 'pagado'): ?>
    <!-- Ya pagado -->
    <div style="background:#DCFCE7;border-radius:12px;padding:16px;text-align:center;margin-top:8px">
      <div style="font-size:2rem;margin-bottom:4px">✅</div>
      <div style="font-weight:700;color:#166534;font-size:1rem">¡Cuenta pagada!</div>
      <div style="font-size:.85rem;color:#16A34A;margin-top:4px">
        <?= htmlspecialchars(ucfirst($ticket['metodo_pago'] ?? '')) ?>
      </div>
    </div>

    <?php else: ?>
    <!-- Propina selector -->
    <div style="margin:16px 0">
      <div style="font-size:.85rem;font-weight:600;color:#374151;margin-bottom:8px">¿Deseas dejar propina?</div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px" id="propinaGrid">
        <?php
        $subtotal = (float)($ticket['subtotal'] ?? 0);
        $tips = [0, 10, 15, 20];
        foreach ($tips as $pct):
          $monto = $subtotal * $pct / 100;
        ?>
        <button type="button" onclick="seleccionarPropina(<?= $pct ?>, <?= $monto ?>)"
                class="propina-btn <?= $pct === 0 ? 'selected' : '' ?>"
                data-pct="<?= $pct ?>"
                style="padding:8px 4px;border-radius:8px;border:2px solid #E5E7EB;background:#fff;
                       font-size:.8rem;font-weight:600;cursor:pointer;transition:.15s;text-align:center">
          <?= $pct === 0 ? 'Sin propina' : $pct . '%' ?>
          <?php if ($pct > 0): ?>
          <div style="font-size:.7rem;color:#6B7280;font-weight:400">$<?= number_format($monto, 2) ?></div>
          <?php endif; ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Selector método de pago -->
    <div style="margin-bottom:16px">
      <div style="font-size:.85rem;font-weight:600;color:#374151;margin-bottom:8px">Método de pago</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px" id="metodoGrid">
        <?php
        $metodos = [
          ['val'=>'efectivo',       'label'=>'Efectivo',       'icon'=>'💵'],
          ['val'=>'tarjeta',        'label'=>'Tarjeta',        'icon'=>'💳'],
          ['val'=>'transferencia',  'label'=>'Transferencia',  'icon'=>'📲'],
          ['val'=>'paypal',         'label'=>'PayPal',         'icon'=>'🅿️'],
        ];
        foreach ($metodos as $m):
        ?>
        <button type="button"
                onclick="seleccionarMetodo('<?= $m['val'] ?>')"
                data-metodo="<?= $m['val'] ?>"
                class="metodo-btn"
                style="padding:12px;border-radius:10px;border:2px solid #E5E7EB;background:#fff;
                       cursor:pointer;transition:.15s;text-align:center;font-size:.85rem;font-weight:600">
          <div style="font-size:1.3rem;margin-bottom:3px"><?= $m['icon'] ?></div>
          <?= $m['label'] ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Botón pagar -->
    <form method="POST" action="<?= BASE_URL ?>rest-ticket/confirmarPago/<?= (int)($ticket['id'] ?? 0) ?>" id="formPago">
      <input type="hidden" name="metodo_pago" id="inpMetodo" value="efectivo">
      <input type="hidden" name="propina" id="inpPropina" value="0">
      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;border-radius:12px">
        Confirmar pago $<span id="totalFinal"><?= number_format((float)($ticket['total'] ?? 0), 2) ?></span> →
      </button>
    </form>
    <?php endif; ?>
  </div>

  <div style="text-align:center;margin-top:16px;font-size:.72rem;color:rgba(255,255,255,.5)">
    Potenciado por <strong style="color:rgba(255,255,255,.7)">CarniHub</strong>
  </div>
</div>

<style>
  .propina-btn.selected { border-color: var(--cp) !important; background: var(--cp) !important; color: #fff; }
  .propina-btn.selected div { color: rgba(255,255,255,.8) !important; }
  .metodo-btn.selected  { border-color: var(--cp) !important; background: color-mix(in srgb, var(--cp) 10%, white); color: var(--cp); }
</style>

<script>
const subtotal = <?= (float)($ticket['subtotal'] ?? 0) ?>;
const baseTotal = <?= (float)($ticket['total'] ?? 0) ?>;
let propinaMonto = 0;
let metodoActual = 'efectivo';

// Seleccionar primer método
seleccionarMetodo('efectivo');

function seleccionarPropina(pct, monto) {
  propinaMonto = monto;
  document.getElementById('inpPropina').value = monto.toFixed(2);
  document.getElementById('totalFinal').textContent = (subtotal + monto).toFixed(2);
  document.querySelectorAll('.propina-btn').forEach(b => b.classList.remove('selected'));
  document.querySelector(`.propina-btn[data-pct="${pct}"]`).classList.add('selected');
}

function seleccionarMetodo(metodo) {
  metodoActual = metodo;
  document.getElementById('inpMetodo').value = metodo;
  document.querySelectorAll('.metodo-btn').forEach(b => b.classList.remove('selected'));
  const btn = document.querySelector(`.metodo-btn[data-metodo="${metodo}"]`);
  if (btn) btn.classList.add('selected');
}
</script>
</body>
</html>
