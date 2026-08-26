<?php ob_start(); ?>

<h1 style="font-size:1.3rem;font-weight:800;margin-bottom:20px">Nuevo negocio</h1>

<form method="POST" action="<?= BASE_URL ?>superadmin/crearNegocio" style="background:#fff;border-radius:12px;padding:24px;border:1px solid #E5E7EB;max-width:640px">
  <div style="font-weight:700;margin-bottom:12px">Datos del negocio</div>
  <div style="display:grid;gap:12px;margin-bottom:20px">
    <div>
      <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:4px">Razón social *</label>
      <input type="text" name="razon_social" required placeholder="Ej: Café UTEQ S.A. de C.V."
             style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem">
    </div>
    <div>
      <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:4px">Nombre del negocio (como lo verán los clientes) *</label>
      <input type="text" name="nombre" required placeholder="Ej: Cafetería UTEQ"
             style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem">
    </div>
    <div>
      <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:4px">Descripción</label>
      <input type="text" name="descripcion" placeholder="Ej: Cafetería del campus"
             style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div>
        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:4px">Teléfono</label>
        <input type="text" name="telefono" placeholder="10 dígitos"
               style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem">
      </div>
      <div>
        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:4px">Dirección</label>
        <input type="text" name="direccion"
               style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem">
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div>
        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:4px">Latitud <span style="color:#9CA3AF;font-weight:400">(opcional)</span></label>
        <input type="text" name="lat" placeholder="20.5888"
               style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem">
      </div>
      <div>
        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:4px">Longitud <span style="color:#9CA3AF;font-weight:400">(opcional)</span></label>
        <input type="text" name="lng" placeholder="-100.3899"
               style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem">
      </div>
    </div>
  </div>

  <div style="font-weight:700;margin-bottom:12px;border-top:1px solid #F3F4F6;padding-top:16px">Primera cuenta de Admin</div>
  <div style="font-size:.8rem;color:#6B7280;margin-bottom:12px">Con estos datos el dueño del negocio entra por primera vez a su panel.</div>
  <div style="display:grid;gap:12px;margin-bottom:20px">
    <div>
      <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:4px">Nombre completo *</label>
      <input type="text" name="admin_nombre" required
             style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div>
        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:4px">Correo *</label>
        <input type="email" name="admin_email" required
               style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem">
      </div>
      <div>
        <label style="display:block;font-size:.82rem;font-weight:600;margin-bottom:4px">Contraseña *</label>
        <input type="password" name="admin_password" required minlength="6" placeholder="Mín. 6 caracteres"
               style="width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem">
      </div>
    </div>
  </div>

  <div style="display:flex;gap:10px;justify-content:flex-end">
    <a href="<?= BASE_URL ?>superadmin/negocios" style="padding:10px 18px;border:1px solid #E5E7EB;border-radius:8px;text-decoration:none;color:#374151;font-size:.88rem;font-weight:600">Cancelar</a>
    <button type="submit" style="background:#A97C3F;color:#fff;border:none;padding:10px 18px;border-radius:8px;font-size:.88rem;font-weight:700;cursor:pointer">Crear negocio</button>
  </div>
</form>

<?php
$content = ob_get_clean();
$activeMenu = 'nuevo';
require ROOT_PATH . '/app/views/superadmin/layout.php';
