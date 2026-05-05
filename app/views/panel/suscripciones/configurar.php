<?php
// Vista: Configurar PayPal plan IDs (superadmin)
?>
<div style="max-width:640px">
  <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:16px;margin-bottom:20px;font-size:.875rem;color:#1E40AF">
    <strong>¿Cómo obtener los IDs de planes PayPal?</strong><br>
    1. Entra a <a href="https://developer.paypal.com" target="_blank" style="color:#1D4ED8">developer.paypal.com</a>
       → Dashboard → Subscriptions → Plans.<br>
    2. Crea un producto y un plan de facturación para cada nivel (Básico, Pro, Empresa).<br>
    3. Copia el <code style="background:#DBEAFE;padding:1px 4px;border-radius:3px">Plan ID</code>
       (empieza con <code>P-</code>) y pégalo abajo.
  </div>

  <form method="POST" action="<?= BASE_URL ?>suscripcion/guardarConfig">
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px;margin-bottom:16px">
      <h2 style="font-size:.95rem;font-weight:700;margin-bottom:16px;color:#111827">
        PayPal Plan IDs
      </h2>
      <div style="display:flex;flex-direction:column;gap:14px">
        <?php foreach ($planes as $plan): ?>
        <div>
          <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">
            Plan <?= htmlspecialchars($plan['nombre']) ?>
            <span style="font-weight:400;color:#6B7280">
              ($<?= number_format($plan['precio_mensual'], 0, '.', ',') ?> MXN/mes)
            </span>
          </label>
          <input type="text"
                 name="paypal_plan_<?= $plan['id'] ?>"
                 placeholder="P-XXXXXXXXXXXXXXXX"
                 value="<?= htmlspecialchars($plan['paypal_plan_id'] ?? '') ?>"
                 style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:.875rem;font-family:monospace;box-sizing:border-box">
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="display:flex;gap:10px">
      <button type="submit"
              style="padding:10px 24px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
        Guardar IDs
      </button>
      <a href="<?= BASE_URL ?>suscripcion/index"
         style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
        Cancelar
      </a>
    </div>
  </form>
</div>
