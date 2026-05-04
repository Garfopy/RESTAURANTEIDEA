<?php
// Vista: Paso 4 — Pedido confirmado
?>
<!-- Pasos -->
<div style="display:flex;align-items:center;gap:0;margin-bottom:32px;font-size:.8rem">
  <?php
  $pasos = ['1'=>'Productos','2'=>'Sucursales','3'=>'Resumen','4'=>'Confirmado'];
  foreach ($pasos as $num => $label):
    $activo = $num === '4';
    $hecho  = $num < '4';
  ?>
  <div style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:<?= $activo || $hecho ? '#D1FAE5' : '#E5E7EB' ?>;color:<?= $activo || $hecho ? '#065F46' : '#9CA3AF' ?>;<?= $num === '1' ? 'border-radius:8px 0 0 8px' : ($num === '4' ? 'border-radius:0 8px 8px 0' : '') ?>">
    <span style="font-weight:700"><?= $hecho ? '✓' : $num ?></span>
    <?= $label ?>
  </div>
  <?php if ($num < '4'): ?>
  <div style="width:0;height:0;border-top:18px solid transparent;border-bottom:18px solid transparent;border-left:10px solid <?= $activo || $hecho ? '#D1FAE5' : '#E5E7EB' ?>"></div>
  <?php endif; ?>
  <?php endforeach; ?>
</div>

<div style="max-width:480px;margin:0 auto;text-align:center">
  <div style="width:72px;height:72px;border-radius:50%;background:#D1FAE5;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem">✓</div>
  <h2 style="font-size:1.4rem;font-weight:800;color:#111827;margin-bottom:8px">¡Pedido realizado!</h2>
  <p style="color:#6B7280;margin-bottom:20px;font-size:.95rem">
    Tu pedido fue registrado correctamente. El equipo de CarniHub lo procesará en breve.
  </p>

  <div style="background:#F9FAFB;border:2px dashed #D1D5DB;border-radius:12px;padding:20px;margin-bottom:24px">
    <div style="font-size:.8rem;color:#6B7280;margin-bottom:6px">Número de folio</div>
    <div style="font-size:1.8rem;font-weight:800;color:var(--color-primary);letter-spacing:.05em"><?= htmlspecialchars($folio) ?></div>
    <div style="font-size:.75rem;color:#9CA3AF;margin-top:6px">Guarda este folio para dar seguimiento</div>
  </div>

  <?php if ($pedidoId): ?>
  <div style="display:flex;flex-direction:column;gap:10px">
    <a href="<?= BASE_URL ?>pedido/detalle/<?= $pedidoId ?>"
       style="display:block;padding:12px;background:var(--color-primary);color:#fff;border-radius:8px;text-decoration:none;font-weight:700;font-size:.95rem">
      Ver detalle del pedido
    </a>
    <a href="<?= BASE_URL ?>carrito/index"
       style="display:block;padding:10px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
      Hacer otro pedido
    </a>
    <a href="<?= BASE_URL ?>pedido/index"
       style="display:block;padding:10px;color:#6B7280;text-decoration:none;font-size:.875rem">
      Ver historial de pedidos
    </a>
  </div>
  <?php endif; ?>
</div>
