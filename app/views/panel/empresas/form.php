<?php
// Vista: Formulario nueva empresa (panel admin)
$empresa  = $empresa  ?? [];
$planes   = $planes   ?? [];
$editando = !empty($empresa['id']);
?>
<div style="max-width:640px">
  <form method="POST" action="<?= BASE_URL ?><?= $editando ? 'panel-empresa/actualizar/'.$empresa['id'] : 'panel-empresa/guardar' ?>">

    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px;margin-bottom:16px">
      <h2 style="font-size:.95rem;font-weight:700;margin-bottom:16px;color:#111827">Datos de la empresa</h2>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div style="grid-column:span 2">
          <label class="form-label">Razón Social *</label>
          <input type="text" name="razon_social" class="form-control" value="<?= htmlspecialchars($empresa['razon_social'] ?? '') ?>" required>
        </div>
        <div>
          <label class="form-label">RFC</label>
          <input type="text" name="rfc" class="form-control" value="<?= htmlspecialchars($empresa['rfc'] ?? '') ?>" maxlength="13" placeholder="AAA000101AAA">
        </div>
        <div>
          <label class="form-label">Tipo de negocio</label>
          <select name="tipo_negocio" class="form-control">
            <option value="">Selecciona...</option>
            <?php foreach (['taqueria'=>'Taquería','carniceria'=>'Carnicería','restaurante'=>'Restaurante','comedor'=>'Comedor industrial','otro'=>'Otro'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= ($empresa['tipo_negocio'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label">Correo de contacto</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>">
        </div>
        <div>
          <label class="form-label">Teléfono</label>
          <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($empresa['telefono'] ?? '') ?>">
        </div>
        <div style="grid-column:span 2">
          <label class="form-label">Dirección fiscal</label>
          <textarea name="direccion_fiscal" class="form-control" rows="2"><?= htmlspecialchars($empresa['direccion_fiscal'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <?php if (!$editando && !empty($planes)): ?>
    <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:24px;margin-bottom:16px">
      <h2 style="font-size:.95rem;font-weight:700;margin-bottom:4px;color:#111827">Plan de suscripción</h2>
      <p style="font-size:.8rem;color:#6B7280;margin-bottom:16px">Selecciona el plan inicial de esta empresa.</p>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
        <?php foreach ($planes as $idx => $pl): ?>
        <label style="cursor:pointer">
          <input type="radio" name="plan_id" value="<?= $pl['id'] ?>"
                 <?= $idx === 0 ? 'checked' : '' ?>
                 style="display:none"
                 onchange="document.querySelectorAll('.plan-card').forEach(c=>c.classList.remove('selected'));this.closest('.plan-card').classList.add('selected')">
          <div class="plan-card <?= $idx === 0 ? 'selected' : '' ?>"
               style="border:2px solid <?= $idx === 0 ? 'var(--color-primary)' : '#E5E7EB' ?>;border-radius:10px;padding:14px;text-align:center;transition:border .15s"
               onclick="this.closest('label').querySelector('input').click();document.querySelectorAll('.plan-card').forEach(c=>c.style.borderColor='#E5E7EB');this.style.borderColor='var(--color-primary)'">
            <div style="font-weight:700;font-size:.9rem;color:#111827;margin-bottom:4px"><?= htmlspecialchars($pl['nombre']) ?></div>
            <div style="font-size:1.1rem;font-weight:800;color:var(--color-primary)">
              $<?= number_format($pl['precio_mensual'], 0, '.', ',') ?>
              <span style="font-size:.7rem;font-weight:400;color:#6B7280">/mes</span>
            </div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <?php elseif ($editando): ?>
    <div style="background:#F9FAFB;border-radius:10px;padding:14px;margin-bottom:16px;font-size:.875rem;color:#6B7280;border:1px solid #E5E7EB">
      Para cambiar el plan de esta empresa ve a
      <a href="<?= BASE_URL ?>suscripcion/index" style="color:var(--color-primary);font-weight:600">Suscripciones</a>.
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:10px">
      <button type="submit" style="padding:10px 24px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer">
        <?= $editando ? 'Guardar cambios' : 'Crear empresa' ?>
      </button>
      <a href="<?= BASE_URL ?>panel-empresa/index" style="padding:10px 20px;background:#F3F4F6;color:#374151;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem">
        Cancelar
      </a>
    </div>
  </form>
</div>
