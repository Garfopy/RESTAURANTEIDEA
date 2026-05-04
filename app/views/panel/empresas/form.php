<?php
// Vista: Formulario nueva empresa (panel admin)
$empresa = $empresa ?? [];
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
