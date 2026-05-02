<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_admin.php';
$es_nuevo = empty($empresa['id']);
?>
<div style="margin-bottom:16px"><a href="<?= BASE_URL ?>cliente/index" style="font-size:.875rem;color:#6B7280;text-decoration:none">← Clientes</a></div>
<h1 style="font-size:1.25rem;font-weight:700;margin-bottom:20px"><?= $es_nuevo ? 'Nuevo Cliente' : 'Editar: ' . htmlspecialchars($empresa['razon_social']) ?></h1>

<?php if (!empty($flash)): ?>
<div style="padding:12px 14px;border-radius:8px;margin-bottom:16px;font-size:.875rem;background:<?=$flash['type']==='error'?'#FEE2E2':'#D1FAE5'?>;color:<?=$flash['type']==='error'?'#991B1B':'#065F46'?>">
  <?= htmlspecialchars($flash['message']) ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>cliente/guardar">
  <input type="hidden" name="id" value="<?= $empresa['id'] ?? '' ?>">

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="card">
      <div class="card-title" style="margin-bottom:16px">Datos Fiscales</div>
      <div style="display:grid;gap:14px">
        <div>
          <label class="form-label">Razón Social *</label>
          <input type="text" name="razon_social" class="form-control" required
                 value="<?= htmlspecialchars($empresa['razon_social'] ?? '') ?>">
        </div>
        <div>
          <label class="form-label">RFC *</label>
          <input type="text" name="rfc" class="form-control" required maxlength="15"
                 value="<?= htmlspecialchars($empresa['rfc'] ?? '') ?>" style="text-transform:uppercase">
        </div>
        <div>
          <label class="form-label">Régimen Fiscal</label>
          <select name="regimen_fiscal" class="form-control form-select">
            <option value="">-- Seleccionar --</option>
            <?php
            $regimenes = ['601 - General de Ley Personas Morales','612 - Personas Físicas con Actividades Empresariales','616 - Sin obligaciones fiscales','621 - Incorporación Fiscal'];
            foreach ($regimenes as $r):
            ?>
            <option value="<?=$r?>" <?= ($empresa['regimen_fiscal']??'')===$r?'selected':''?>><?=$r?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label">Dirección Fiscal</label>
          <textarea name="direccion_fiscal" class="form-control" rows="3"><?= htmlspecialchars($empresa['direccion_fiscal'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-title" style="margin-bottom:16px">Información Comercial</div>
      <div style="display:grid;gap:14px">
        <div>
          <label class="form-label">Email de contacto</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>">
        </div>
        <div>
          <label class="form-label">Teléfono</label>
          <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($empresa['telefono'] ?? '') ?>">
        </div>
        <div>
          <label class="form-label">Método de pago preferido</label>
          <select name="metodo_pago_preferido" class="form-control form-select">
            <?php foreach (['transferencia'=>'Transferencia bancaria','tarjeta'=>'Tarjeta','credito'=>'Crédito CarniHub','efectivo'=>'Efectivo'] as $v=>$l): ?>
            <option value="<?=$v?>" <?= ($empresa['metodo_pago_preferido']??'transferencia')===$v?'selected':''?>><?=$l?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label">Fecha de registro</label>
          <input type="date" name="fecha_registro" class="form-control"
                 value="<?= $empresa['fecha_registro'] ?? date('Y-m-d') ?>">
        </div>
        <div>
          <label class="form-label">Estado</label>
          <select name="activo" class="form-control form-select">
            <option value="1" <?= ($empresa['activo']??1)?'selected':''?>>Activo</option>
            <option value="0" <?= isset($empresa['activo'])&&!$empresa['activo']?'selected':''?>>Inactivo</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top:20px;display:flex;gap:10px">
    <button type="submit" class="btn btn-primary">
      <?= $es_nuevo ? 'Crear cliente' : 'Guardar cambios' ?>
    </button>
    <a href="<?= BASE_URL ?>cliente/index" class="btn btn-secondary">Cancelar</a>
  </div>
</form>

<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
