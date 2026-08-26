<?php ob_start(); ?>

<style>
.staff-shell { display: grid; gap: 18px; }
.staff-hero {
  align-items: center;
  background: linear-gradient(135deg, #fff 0%, #F8FAFC 56%, color-mix(in srgb, var(--cp) 10%, white) 100%);
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  box-shadow: 0 18px 55px rgba(15,23,42,.06);
  display: flex;
  gap: 18px;
  justify-content: space-between;
  padding: 22px;
}
.staff-title { color: #0F172A; font-size: 1.45rem; font-weight: 800; margin: 0; }
.staff-copy { color: #64748B; font-size: .9rem; line-height: 1.5; margin: 6px 0 0; max-width: 680px; }
.staff-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
.staff-access {
  align-items: center;
  background: #fff;
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  display: grid;
  gap: 14px;
  grid-template-columns: 1fr auto;
  padding: 16px 18px;
}
.staff-access-label { color: var(--cp); font-size: .82rem; font-weight: 800; margin-bottom: 4px; text-transform: uppercase; }
.staff-access-url { color: #334155; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .8rem; overflow-wrap: anywhere; }
.staff-role-grid { display: grid; gap: 12px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
.staff-role-card {
  appearance: none;
  background: rgba(255,255,255,.96);
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  box-shadow: 0 14px 40px rgba(15,23,42,.05);
  cursor: pointer;
  display: grid;
  gap: 10px;
  min-height: 156px;
  padding: 16px;
  text-align: left;
  transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
  width: 100%;
}
.staff-role-card:hover {
  border-color: color-mix(in srgb, var(--cp) 42%, #E2E8F0);
  box-shadow: 0 18px 46px rgba(15,23,42,.09);
  transform: translateY(-2px);
}
.staff-role-top { align-items: flex-start; display: flex; justify-content: space-between; gap: 10px; }
.staff-role-icon {
  align-items: center;
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  color: #111827;
  display: inline-flex;
  font-size: 1.45rem;
  height: 44px;
  justify-content: center;
  line-height: 1;
  width: 44px;
}
.staff-role-card[data-role="barra"] .staff-role-icon { background: #FFF7ED; border-color: #FED7AA; }
.staff-role-title { color: #111827; display: block; font-size: 1rem; font-weight: 800; margin: 0; }
.staff-role-desc { color: #64748B; display: block; font-size: .82rem; line-height: 1.45; margin: 4px 0 0; }
.staff-table-title { align-items: center; display: flex; justify-content: space-between; margin-top: 4px; }
.staff-table-title h2 { color: #111827; font-size: 1rem; margin: 0; }
.btn-success { background: #DCFCE7; color: #166534; }
.btn-success:hover { background: #BBF7D0; }
.staff-modal-role-grid { display: grid; gap: 8px; grid-template-columns: repeat(4, 1fr); margin-top: 4px; }
.rol-lbl {
  align-items: center;
  border: 2px solid #E5E7EB;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  padding: 12px 8px;
  text-align: center;
  transition: border-color .15s ease, background .15s ease;
}
.rol-lbl:hover { background: #F8FAFC; }
.rol-lbl-icon { font-size: 1.4rem; line-height: 1; margin-bottom: 4px; }
.rol-lbl-text { font-size: .82rem; font-weight: 800; }
@media (max-width: 980px) {
  .staff-role-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .staff-hero { align-items: flex-start; flex-direction: column; }
  .staff-actions { justify-content: flex-start; }
  .staff-access { align-items: flex-start; grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .staff-role-grid, .staff-modal-role-grid { grid-template-columns: 1fr; }
}
</style>

<?php
$roles = [
  ['slug'=>'cocina', 'label'=>'Cocina', 'icon'=>'&#127859;', 'desc'=>'Ve la cola de pedidos y marca platillos listos', 'badge'=>'badge-amber'],
  ['slug'=>'cajero', 'label'=>'Cajero', 'icon'=>'&#128179;', 'desc'=>'Cobra en mostrador y confirma pedidos de la app', 'badge'=>'badge-blue'],
];
?>

<div class="staff-shell">
  <section class="staff-hero">
    <div>
      <h1 class="staff-title">Staff</h1>
      <p class="staff-copy">Administra accesos por rol, comparte el enlace de entrada y prepara el turno del equipo.</p>
    </div>
    <div class="staff-actions">
      <button onclick="rstModal('modalStaff')" class="btn btn-primary btn-sm">+ Nuevo staff</button>
    </div>
  </section>

  <section class="staff-access">
    <div>
      <div class="staff-access-label">Link de acceso del staff</div>
      <div class="staff-access-url"><?= htmlspecialchars($linkAcceso) ?></div>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0">
      <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($linkAcceso, ENT_QUOTES) ?>');this.textContent='Copiado';setTimeout(()=>this.textContent='Copiar',2000)"
              class="btn btn-outline btn-sm">Copiar</button>
      <a href="<?= htmlspecialchars($linkAcceso) ?>" target="_blank" class="btn btn-primary btn-sm">Abrir</a>
    </div>
  </section>

  <section class="staff-role-grid">
    <?php foreach ($roles as $r): ?>
      <?php $count = count(array_filter($staff, fn($s) => $s['rol_slug'] === $r['slug'] && $s['activo'])); ?>
      <button type="button" class="staff-role-card" data-role="<?= $r['slug'] ?>" onclick="preseleccionarRol('<?= $r['slug'] ?>')">
        <span class="staff-role-top">
          <span class="staff-role-icon"><?= $r['icon'] ?></span>
          <span class="badge <?= $r['badge'] ?>"><?= $count ?> activo<?= $count !== 1 ? 's' : '' ?></span>
        </span>
        <span>
          <span class="staff-role-title"><?= $r['label'] ?></span>
          <span class="staff-role-desc"><?= $r['desc'] ?></span>
        </span>
      </button>
    <?php endforeach; ?>
  </section>

  <div class="staff-table-title">
    <h2>Equipo registrado</h2>
  </div>

  <div class="rst-table-wrap">
    <table class="rst-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Correo</th>
          <th>Rol</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staff as $s): ?>
        <tr>
          <td style="font-weight:600"><?= htmlspecialchars($s['nombre']) ?></td>
          <td style="color:#6B7280;font-size:.85rem"><?= htmlspecialchars($s['email']) ?></td>
          <td>
            <?php $badgeRol = ['cocina'=>'badge-amber','cajero'=>'badge-blue'][$s['rol_slug']] ?? 'badge-gray'; ?>
            <span class="badge <?= $badgeRol ?>"><?= htmlspecialchars($s['rol_nombre']) ?></span>
          </td>
          <td>
            <span class="badge <?= $s['activo'] ? 'badge-green' : 'badge-red' ?>">
              <?= $s['activo'] ? 'Activo' : 'Inactivo' ?>
            </span>
          </td>
          <td>
            <?php if ($s['activo']): ?>
            <a href="<?= BASE_URL ?>rest-staff/desactivar/<?= $s['id'] ?>"
               onclick="return confirm('Desactivar a <?= htmlspecialchars($s['nombre'], ENT_QUOTES) ?>?')"
               class="btn btn-danger btn-sm">Desactivar</a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>rest-staff/activar/<?= $s['id'] ?>"
               onclick="return confirm('Reactivar a <?= htmlspecialchars($s['nombre'], ENT_QUOTES) ?>?')"
               class="btn btn-success btn-sm">Activar</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($staff)): ?>
        <tr>
          <td colspan="5">
            <div class="empty-state">
              <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <div style="font-size:.95rem;font-weight:600;color:#374151;margin-bottom:4px">Sin staff registrado</div>
              <div>Crea cuentas para cocina y cajero.</div>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="modalStaff" class="rst-modal-backdrop">
  <div class="rst-modal">
    <div class="rst-modal-header">
      <div class="rst-modal-title">Agregar staff</div>
      <button class="rst-modal-close" onclick="rstModal('modalStaff')">x</button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>rest-staff/crear">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group" style="grid-column:span 2">
          <label class="form-label">Rol *</label>
          <div class="staff-modal-role-grid">
            <?php foreach ($roles as $r): ?>
            <label class="rol-lbl">
              <input type="radio" name="rol_slug" value="<?= $r['slug'] ?>" class="rol-radio" style="display:none">
              <span class="rol-lbl-icon"><?= $r['icon'] ?></span>
              <span class="rol-lbl-text"><?= $r['label'] ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="form-group" style="grid-column:span 2">
          <label class="form-label">Nombre completo *</label>
          <input type="text" name="nombre" class="form-input" required placeholder="Nombre del trabajador">
        </div>
        <div class="form-group" style="grid-column:span 2">
          <label class="form-label">Correo electronico *</label>
          <input type="email" name="email" class="form-input" required placeholder="correo@ejemplo.com">
        </div>
        <div class="form-group" style="grid-column:span 2">
          <label class="form-label">Contrasena *</label>
          <input type="password" name="password" class="form-input" required placeholder="Min. 6 caracteres" minlength="6">
        </div>
      </div>
      <div style="background:#F0FDF4;border-radius:8px;padding:10px 12px;font-size:.8rem;color:#166534;margin-bottom:4px">
        El staff iniciara sesion en <strong><?= BASE_URL ?>auth/login</strong> con su correo y contrasena.
      </div>
      <div class="rst-modal-footer">
        <button type="button" onclick="rstModal('modalStaff')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Crear cuenta</button>
      </div>
    </form>
  </div>
</div>

<script>
function rstModal(id) {
  document.getElementById(id).classList.toggle('open');
}
document.querySelectorAll('.rst-modal-backdrop').forEach(bd => {
  bd.addEventListener('click', e => { if (e.target === bd) bd.classList.remove('open'); });
});

document.querySelectorAll('.rol-lbl').forEach(lbl => {
  lbl.addEventListener('click', () => {
    document.querySelectorAll('.rol-lbl').forEach(l => {
      l.style.borderColor = '#E5E7EB';
      l.style.background = '';
    });
    lbl.style.borderColor = 'var(--cp)';
    lbl.style.background = 'var(--cp-light)';
    lbl.querySelector('.rol-radio').checked = true;
  });
});

const firstRol = document.querySelector('.rol-lbl');
if (firstRol) firstRol.click();

function preseleccionarRol(slug) {
  const lbl = document.querySelector(`.rol-lbl input[value="${slug}"]`)?.closest('.rol-lbl');
  if (lbl) {
    document.querySelectorAll('.rol-lbl').forEach(l => {
      l.style.borderColor = '#E5E7EB';
      l.style.background = '';
    });
    lbl.style.borderColor = 'var(--cp)';
    lbl.style.background = 'var(--cp-light)';
    lbl.querySelector('.rol-radio').checked = true;
  }
  document.getElementById('modalStaff').classList.add('open');
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
