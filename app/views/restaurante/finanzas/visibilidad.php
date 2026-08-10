<?php ob_start(); ?>
<?php
$activo = !empty($configuracion['activo']) && !empty($configuracion['ocultar_hasta']);
$ocultarHasta = (string)($configuracion['ocultar_hasta'] ?? '');
?>

<style>
.visibility-wrap{max-width:980px;margin:0 auto}.visibility-hero{background:#111827;color:#fff;border-radius:18px;padding:24px;margin-bottom:20px}.visibility-hero h1{margin:0 0 8px;font-size:1.5rem}.visibility-hero p{margin:0;color:#cbd5e1;line-height:1.55}.visibility-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:20px}.visibility-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px}.visibility-card h2{font-size:1.05rem;margin:0 0 14px}.visibility-status{display:inline-flex;padding:6px 10px;border-radius:999px;font-size:.78rem;font-weight:700;margin-bottom:14px}.visibility-status.on{background:#fef3c7;color:#92400e}.visibility-status.off{background:#dcfce7;color:#166534}.visibility-form{display:grid;gap:12px}.visibility-form label{font-size:.82rem;font-weight:700;color:#374151}.visibility-form input{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d1d5db;border-radius:9px}.visibility-btn{border:0;border-radius:9px;padding:10px 14px;font-weight:700;cursor:pointer}.visibility-btn.primary{background:var(--color-primary);color:#fff}.visibility-btn.danger{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}.visibility-note{font-size:.83rem;color:#64748b;line-height:1.5}.visibility-list{display:grid;gap:10px}.visibility-event{padding:11px 0;border-bottom:1px solid #f1f5f9;font-size:.83rem}.visibility-event:last-child{border-bottom:0}.visibility-event strong{display:block;color:#111827}.visibility-event span{color:#64748b}@media(max-width:760px){.visibility-grid{grid-template-columns:1fr}}
</style>

<div class="visibility-wrap">
  <section class="visibility-hero">
    <h1>Modo Macias</h1>
    <p>Controla qué periodo financiero pueden consultar los demás niveles. Las ventas, tickets, gastos, retiros, cortes, facturas, historial y puntos permanecen guardados sin modificación. Modo Macias siempre conserva la vista completa.</p>
    <p style="margin-top:12px"><a href="<?= BASE_URL ?>rest-finanzas/cuentasPendientes" style="color:#fff;font-weight:700">Revisar usuarios con cuentas pendientes →</a></p>
  </section>

  <div class="visibility-grid">
    <section class="visibility-card">
      <span class="visibility-status <?= $activo ? 'on' : 'off' ?>">
        <?= $activo ? 'Ocultamiento activo' : 'Todos los datos visibles' ?>
      </span>
      <h2>Ocultar registros por antigüedad</h2>
      <?php if ($activo): ?>
      <p class="visibility-note">Los demás niveles no pueden consultar registros financieros con fecha igual o anterior al <strong><?= htmlspecialchars($ocultarHasta) ?></strong>.</p>
      <?php else: ?>
      <p class="visibility-note">Actualmente no hay una fecha límite aplicada.</p>
      <?php endif; ?>

      <form class="visibility-form" method="POST" action="<?= BASE_URL ?>rest-finanzas/guardarVisibilidad" onsubmit="return confirm('¿Aplicar este límite de visibilidad a todos los demás niveles?')">
        <label for="ocultar_hasta">Ocultar esta fecha y todo lo anterior</label>
        <input id="ocultar_hasta" type="date" name="ocultar_hasta" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($ocultarHasta) ?>" required>
        <button class="visibility-btn primary" type="submit">Aplicar ocultamiento</button>
      </form>

      <?php if ($activo): ?>
      <form method="POST" action="<?= BASE_URL ?>rest-finanzas/restaurarVisibilidad" style="margin-top:12px" onsubmit="return confirm('¿Restaurar la visibilidad histórica para todos los niveles?')">
        <button class="visibility-btn danger" type="submit">Restaurar todos los datos</button>
      </form>
      <?php endif; ?>
    </section>

    <section class="visibility-card">
      <h2>Historial de cambios</h2>
      <div class="visibility-list">
        <?php foreach ($historial as $evento): ?>
        <div class="visibility-event">
          <strong><?= $evento['accion'] === 'ocultar' ? 'Ocultamiento aplicado' : 'Visibilidad restaurada' ?></strong>
          <span>
            <?= htmlspecialchars((string)($evento['created_at'] ?? '')) ?>
            · <?= htmlspecialchars((string)($evento['usuario_nombre'] ?? 'Macias')) ?>
            <?php if (!empty($evento['ocultar_hasta'])): ?>
            · límite <?= htmlspecialchars((string)$evento['ocultar_hasta']) ?>
            <?php endif; ?>
          </span>
        </div>
        <?php endforeach; ?>
        <?php if (empty($historial)): ?>
        <p class="visibility-note">Aún no se han realizado cambios.</p>
        <?php endif; ?>
      </div>
    </section>
  </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
