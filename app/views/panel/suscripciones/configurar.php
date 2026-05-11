<?php
// Vista: Configurar PayPal plan IDs (superadmin)
$todosConId = array_reduce($planes, fn($ok, $p) =>
    $ok && !empty($p['paypal_plan_id']) && !empty($p['paypal_plan_id_anual']), true);
?>
<div style="max-width:700px">

  <?php if ($todosConId): ?>
  <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:14px 16px;margin-bottom:20px;font-size:.875rem;color:#166534">
    Todos los planes están sincronizados con PayPal.
  </div>
  <?php else: ?>
  <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:14px 16px;margin-bottom:20px;font-size:.875rem;color:#92400E">
    Hay planes sin ID de PayPal. Usa el botón para generarlos automáticamente.
  </div>
  <?php endif; ?>

  <!-- Sincronización automática -->
  <form method="POST" action="<?= BASE_URL ?>suscripcion/sincronizarPlanes"
        style="margin-bottom:24px"
        onsubmit="return confirm('¿Crear los planes faltantes en PayPal automáticamente?')">
    <button type="submit"
            style="padding:10px 24px;background:#0070BA;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
      Sincronizar planes con PayPal
    </button>
    <span style="font-size:.8rem;color:#6B7280;margin-left:10px">
      Solo crea los planes que aún no tienen ID asignado.
    </span>
  </form>

  <!-- Estado actual de los IDs -->
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px">
    <h2 style="font-size:.95rem;font-weight:700;margin-bottom:16px;color:#111827">
      PayPal Plan IDs actuales
    </h2>
    <div style="display:flex;flex-direction:column;gap:16px">
      <?php foreach ($planes as $plan): ?>
      <div style="border:1px solid #E5E7EB;border-radius:8px;padding:14px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div>
            <span style="font-size:.875rem;font-weight:700;color:#111827">
              <?= htmlspecialchars($plan['nombre']) ?>
            </span>
            <span style="font-weight:400;color:#6B7280;font-size:.8rem">
              ($<?= number_format($plan['precio_mensual'], 0, '.', ',') ?>/mes · $<?= number_format($plan['precio_anual'], 0, '.', ',') ?>/año)
            </span>
          </div>
          <a href="<?= BASE_URL ?>suscripcion/editarPlan/<?= (int)$plan['id'] ?>"
             style="font-size:.78rem;padding:5px 12px;background:#EFF6FF;color:#1D4ED8;border-radius:6px;text-decoration:none;font-weight:600">
            Editar límites
          </a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;font-size:.78rem;margin-bottom:10px">
          <?php
          $ilim = fn(int $v) => $v === 0 ? '∞' : $v;
          $limites = [
            'Usuarios'  => $ilim((int)$plan['max_usuarios']),
            'Productos' => $ilim((int)$plan['max_productos']),
            'Pedidos/mes'=> $ilim((int)$plan['max_pedidos_mes']),
            'Sucursales'=> $ilim((int)$plan['max_sucursales']),
          ];
          foreach ($limites as $label => $val): ?>
          <div style="background:#F9FAFB;border-radius:6px;padding:6px 8px;text-align:center">
            <div style="color:#9CA3AF;font-size:.7rem"><?= $label ?></div>
            <div style="font-weight:700;color:#111827"><?= $val ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:.8rem">
          <div>
            <div style="color:#6B7280;margin-bottom:3px">Mensual</div>
            <?php if (!empty($plan['paypal_plan_id'])): ?>
              <code style="background:#F3F4F6;padding:3px 8px;border-radius:4px;color:#111827"><?= htmlspecialchars($plan['paypal_plan_id']) ?></code>
            <?php else: ?>
              <span style="color:#EF4444">Sin ID</span>
            <?php endif; ?>
          </div>
          <div>
            <div style="color:#6B7280;margin-bottom:3px">Anual</div>
            <?php if (!empty($plan['paypal_plan_id_anual'])): ?>
              <code style="background:#F3F4F6;padding:3px 8px;border-radius:4px;color:#111827"><?= htmlspecialchars($plan['paypal_plan_id_anual']) ?></code>
            <?php else: ?>
              <span style="color:#EF4444">Sin ID</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>
