<?php ob_start(); ?>
<div style="max-width:700px">
  <div style="background:#fff;border-radius:12px;border:1px solid #E5E7EB;padding:28px">
    <form method="POST" action="<?= BASE_URL ?>rest-config/guardar" enctype="multipart/form-data">
      <div style="margin-bottom:20px">
        <label style="font-size:.85rem;font-weight:500">Nombre del restaurante *</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($restaurante['nombre'] ?? '') ?>" required
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
      </div>
      <div style="margin-bottom:20px">
        <label style="font-size:.85rem;font-weight:500">Descripción</label>
        <textarea name="descripcion" rows="3"
          style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem;resize:vertical"><?= htmlspecialchars($restaurante['descripcion'] ?? '') ?></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
        <div>
          <label style="font-size:.85rem;font-weight:500">Teléfono</label>
          <input type="text" name="telefono" value="<?= htmlspecialchars($restaurante['telefono'] ?? '') ?>"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Dirección</label>
          <input type="text" name="direccion" value="<?= htmlspecialchars($restaurante['direccion'] ?? '') ?>"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Horario apertura</label>
          <input type="time" name="horario_apertura" value="<?= htmlspecialchars($restaurante['horario_apertura'] ?? '') ?>"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
        <div>
          <label style="font-size:.85rem;font-weight:500">Horario cierre</label>
          <input type="time" name="horario_cierre" value="<?= htmlspecialchars($restaurante['horario_cierre'] ?? '') ?>"
            style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.9rem">
        </div>
      </div>

      <div style="border-top:1px solid #F3F4F6;padding-top:20px;margin-bottom:20px">
        <div style="font-weight:600;margin-bottom:14px">Branding</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;align-items:end">
          <div>
            <label style="font-size:.85rem;font-weight:500">Color primario</label>
            <div style="display:flex;gap:8px;align-items:center;margin-top:4px">
              <input type="color" name="color_primario" value="<?= htmlspecialchars($restaurante['color_primario'] ?? '#C8102E') ?>"
                style="height:40px;width:48px;border:1px solid #D1D5DB;border-radius:6px;padding:2px;cursor:pointer">
              <input type="text" id="txtColorPri" value="<?= htmlspecialchars($restaurante['color_primario'] ?? '#C8102E') ?>"
                style="flex:1;padding:8px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.85rem">
            </div>
          </div>
          <div>
            <label style="font-size:.85rem;font-weight:500">Color secundario</label>
            <div style="display:flex;gap:8px;align-items:center;margin-top:4px">
              <input type="color" name="color_secundario" value="<?= htmlspecialchars($restaurante['color_secundario'] ?? '#1f2937') ?>"
                style="height:40px;width:48px;border:1px solid #D1D5DB;border-radius:6px;padding:2px;cursor:pointer">
              <input type="text" id="txtColorSec" value="<?= htmlspecialchars($restaurante['color_secundario'] ?? '#1f2937') ?>"
                style="flex:1;padding:8px 10px;border:1px solid #D1D5DB;border-radius:6px;font-size:.85rem">
            </div>
          </div>
          <div>
            <label style="font-size:.85rem;font-weight:500">Logo</label>
            <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.svg"
              style="width:100%;padding:6px;border:1px solid #D1D5DB;border-radius:8px;margin-top:4px;font-size:.85rem">
            <?php if (!empty($restaurante['logo'])): ?>
            <img src="<?= BASE_URL . htmlspecialchars($restaurante['logo']) ?>" style="height:40px;margin-top:6px;border-radius:4px;object-fit:contain">
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div style="background:#F9FAFB;border-radius:8px;padding:12px;font-size:.8rem;color:#6B7280;margin-bottom:20px">
        El footer del menú siempre mostrará: <strong>Potenciado por CarniHub</strong>
      </div>

      <div style="display:flex;justify-content:flex-end">
        <button type="submit"
          style="padding:10px 28px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer">
          Guardar configuración
        </button>
      </div>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
