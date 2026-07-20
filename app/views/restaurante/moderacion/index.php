<?php ob_start(); ?>
<?php
$kpis = $kpis ?? [];
$usuarios = $usuarios ?? [];
$reportes = $reportes ?? [];
$available = (bool)($available ?? false);

$fmtDate = static function (?string $date): string {
    if (!$date) return '-';
    $ts = strtotime($date);
    return $ts ? date('d/m/Y H:i', $ts) : $date;
};

$reasonLabel = static function (?string $value): string {
    $value = trim((string)$value);
    if ($value === '') return 'Sin motivo';
    $labels = [
        'user_report' => 'Reporte de usuario',
        'user_request' => 'Solicitud del usuario',
        'harassment' => 'Acoso',
        'spam' => 'Spam',
        'inappropriate' => 'Contenido inapropiado',
    ];
    return $labels[$value] ?? ucfirst(str_replace('_', ' ', $value));
};

$statusLabel = static function (?string $value): array {
    $value = strtolower(trim((string)$value));
    return match ($value) {
        'reviewed', 'revisado', 'resolved', 'resuelto' => ['Revisado', '#ECFDF5', '#047857'],
        'dismissed', 'descartado' => ['Descartado', '#F3F4F6', '#4B5563'],
        'banned', 'auto_banned' => ['Cuenta desactivada', '#FEF2F2', '#991B1B'],
        default => ['Pendiente', '#FFFBEB', '#92400E'],
    };
};
?>

<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px;flex-wrap:wrap">
  <div>
    <h1 style="font-size:1.45rem;margin:0;color:#111827">Reportes de App</h1>
    <p style="margin:6px 0 0;color:#6B7280;font-size:.9rem">Gestiona reportes sociales, bloqueos y cuentas con riesgo operativo.</p>
  </div>
  <a href="<?= BASE_URL ?>restaurante/dashboard"
     style="text-decoration:none;border:1px solid #E5E7EB;background:#fff;color:#374151;border-radius:8px;padding:9px 12px;font-weight:700;font-size:.84rem">
    Volver al dashboard
  </a>
</div>

<?php if (!$available): ?>
<div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:18px;color:#78350F">
  <strong>No hay tablas de moderacion disponibles.</strong>
  <div style="font-size:.86rem;margin-top:6px">Ejecuta la migracion <code>078_social_reports_blocks.sql</code> o importa una base que incluya <code>social_reports</code>, <code>social_blocks</code> y <code>mobile_usuarios</code>.</div>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px">
  <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:18px">
    <div style="font-size:.76rem;color:#6B7280;font-weight:700">Reportes pendientes</div>
    <div style="font-size:1.6rem;font-weight:800;color:#111827;margin-top:7px"><?= (int)($kpis['pendientes'] ?? 0) ?></div>
  </div>
  <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:18px">
    <div style="font-size:.76rem;color:#991B1B;font-weight:700">Con 3+ reportes</div>
    <div style="font-size:1.6rem;font-weight:800;color:#7F1D1D;margin-top:7px"><?= (int)($kpis['auto_baneables'] ?? 0) ?></div>
  </div>
  <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;padding:18px">
    <div style="font-size:.76rem;color:#374151;font-weight:700">Cuentas desactivadas</div>
    <div style="font-size:1.6rem;font-weight:800;color:#111827;margin-top:7px"><?= (int)($kpis['desactivados'] ?? 0) ?></div>
  </div>
</div>

<div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;margin-bottom:20px;overflow:hidden">
  <div style="padding:16px 18px;border-bottom:1px solid #EEF2F7">
    <div style="font-weight:800;color:#111827">Cuentas reportadas</div>
    <div style="font-size:.78rem;color:#6B7280;margin-top:3px">Al llegar a 3 reportes pendientes, la cuenta se desactiva automaticamente al abrir este apartado.</div>
  </div>
  <div style="overflow:auto">
    <table class="rst-table" style="min-width:880px">
      <thead>
        <tr>
          <th>Usuario</th>
          <th>Estado</th>
          <th style="text-align:right">Reportes</th>
          <th style="text-align:right">Pendientes</th>
          <th style="text-align:right">Bloqueos</th>
          <th>Ultimo reporte</th>
          <th style="text-align:right">Accion</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
        <?php
          $activo = (int)($u['activo'] ?? 0) === 1;
          $pendientes = (int)($u['reportes_pendientes'] ?? 0);
        ?>
        <tr>
          <td>
            <div style="font-weight:800;color:#111827"><?= htmlspecialchars($u['nombre'] ?? 'Usuario') ?></div>
            <div style="font-size:.76rem;color:#6B7280"><?= htmlspecialchars($u['email'] ?? $u['meta'] ?? '') ?></div>
          </td>
          <td>
            <span style="display:inline-flex;border-radius:99px;padding:3px 9px;font-size:.72rem;font-weight:800;background:<?= $activo ? '#ECFDF5' : '#FEF2F2' ?>;color:<?= $activo ? '#047857' : '#991B1B' ?>">
              <?= $activo ? 'Activa' : 'Desactivada' ?>
            </span>
            <?php if ($pendientes >= 3): ?>
            <span style="display:inline-flex;border-radius:99px;padding:3px 9px;font-size:.72rem;font-weight:800;background:#FFFBEB;color:#92400E;margin-left:6px">3+ reportes</span>
            <?php endif; ?>
          </td>
          <td style="text-align:right;font-weight:800"><?= (int)($u['total_reportes'] ?? 0) ?></td>
          <td style="text-align:right;font-weight:800;color:<?= $pendientes >= 3 ? '#991B1B' : '#111827' ?>"><?= $pendientes ?></td>
          <td style="text-align:right"><?= (int)($u['bloqueos_recibidos'] ?? 0) ?></td>
          <td style="color:#6B7280;font-size:.82rem"><?= htmlspecialchars($fmtDate($u['ultimo_reporte'] ?? null)) ?></td>
          <td style="text-align:right">
            <?php if ($activo): ?>
            <form method="post" action="<?= BASE_URL ?>rest-moderacion/desactivar/<?= (int)$u['id'] ?>" style="display:inline"
                  onsubmit="return confirm('Desactivar esta cuenta de la app?');">
              <button type="submit" style="border:0;background:#DC2626;color:white;border-radius:8px;padding:8px 10px;font-weight:800;cursor:pointer">Desactivar</button>
            </form>
            <?php else: ?>
            <form method="post" action="<?= BASE_URL ?>rest-moderacion/reactivar/<?= (int)$u['id'] ?>" style="display:inline"
                  onsubmit="return confirm('Reactivar esta cuenta de la app?');">
              <button type="submit" style="border:1px solid #A7F3D0;background:#ECFDF5;color:#047857;border-radius:8px;padding:8px 10px;font-weight:800;cursor:pointer">Reactivar</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($usuarios)): ?>
        <tr><td colspan="7" style="text-align:center;color:#9CA3AF;padding:24px">No hay cuentas reportadas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;overflow:hidden">
  <div style="padding:16px 18px;border-bottom:1px solid #EEF2F7">
    <div style="font-weight:800;color:#111827">Reportes individuales</div>
    <div style="font-size:.78rem;color:#6B7280;margin-top:3px">Marca cada reporte como revisado, descartado o reabierto.</div>
  </div>
  <div style="display:grid;gap:0">
    <?php foreach ($reportes as $r): ?>
    <?php [$statusText, $statusBg, $statusColor] = $statusLabel($r['status'] ?? 'open'); ?>
    <div style="padding:16px 18px;border-bottom:1px solid #F3F4F6;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:14px;align-items:start">
      <div style="min-width:0">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <strong style="color:#111827"><?= htmlspecialchars($r['reported_nombre'] ?? 'Usuario reportado') ?></strong>
          <span style="font-size:.7rem;font-weight:800;border-radius:99px;padding:3px 8px;background:<?= $statusBg ?>;color:<?= $statusColor ?>"><?= htmlspecialchars($statusText) ?></span>
        </div>
        <div style="font-size:.78rem;color:#6B7280;margin-top:4px">
          Reporta <?= htmlspecialchars($r['reporter_nombre'] ?? 'Usuario') ?> - <?= htmlspecialchars($reasonLabel($r['reason'] ?? null)) ?> - <?= htmlspecialchars($fmtDate($r['created_at'] ?? null)) ?>
        </div>
        <?php if (!empty($r['details'])): ?>
        <div style="margin-top:8px;color:#374151;font-size:.84rem;line-height:1.45"><?= nl2br(htmlspecialchars($r['details'])) ?></div>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
        <form method="post" action="<?= BASE_URL ?>rest-moderacion/reporte/<?= (int)$r['id'] ?>">
          <input type="hidden" name="accion" value="revisar">
          <button type="submit" style="border:1px solid #A7F3D0;background:#ECFDF5;color:#047857;border-radius:8px;padding:8px 10px;font-weight:800;cursor:pointer">Revisado</button>
        </form>
        <form method="post" action="<?= BASE_URL ?>rest-moderacion/reporte/<?= (int)$r['id'] ?>">
          <input type="hidden" name="accion" value="descartar">
          <button type="submit" style="border:1px solid #E5E7EB;background:#F9FAFB;color:#374151;border-radius:8px;padding:8px 10px;font-weight:800;cursor:pointer">Descartar</button>
        </form>
        <form method="post" action="<?= BASE_URL ?>rest-moderacion/reporte/<?= (int)$r['id'] ?>">
          <input type="hidden" name="accion" value="reabrir">
          <button type="submit" style="border:1px solid #FDE68A;background:#FFFBEB;color:#92400E;border-radius:8px;padding:8px 10px;font-weight:800;cursor:pointer">Reabrir</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($reportes)): ?>
    <div style="text-align:center;color:#9CA3AF;padding:24px">No hay reportes registrados.</div>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
