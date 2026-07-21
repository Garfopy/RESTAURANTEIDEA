<?php ob_start(); ?>
<?php
$kpis = $kpis ?? [];
$usuarios = $usuarios ?? [];
$reportes = $reportes ?? [];
$available = (bool)($available ?? false);
$tab = $tab ?? 'reportes';
$fotosResultado = $fotosResultado ?? ['available' => false, 'photos' => [], 'pagination' => ['total' => 0, 'page' => 1, 'pages' => 0], 'pending_count' => 0];
$fotosAvailable = (bool)($fotosResultado['available'] ?? false);
$fotos = $fotosResultado['photos'] ?? [];
$fotoPagination = $fotosResultado['pagination'] ?? ['total' => 0, 'page' => 1, 'pages' => 0];
$fotoStatus = $fotoStatus ?? 'pending';
$fotoSearch = $fotoSearch ?? '';
$csrfToken = $csrfToken ?? '';

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
        'reviewed', 'revisado', 'resolved', 'resuelto', 'approved' => ['Revisado', '#ECFDF5', '#047857'],
        'dismissed', 'descartado' => ['Descartado', '#F3F4F6', '#4B5563'],
        'banned', 'auto_banned', 'rejected' => ['Cuenta desactivada', '#FEF2F2', '#991B1B'],
        default => ['Pendiente', '#FFFBEB', '#92400E'],
    };
};

$photoStatusLabel = static function (?string $value): array {
    return match (strtolower((string)$value)) {
        'approved' => ['Aprobada', '#ECFDF5', '#047857'],
        'rejected' => ['Rechazada', '#FEF2F2', '#991B1B'],
        default => ['Pendiente', '#FFFBEB', '#92400E'],
    };
};

$tabStyle = static function (string $current, string $expected): string {
    return $current === $expected
        ? 'background:#111827;color:#fff;border-color:#111827'
        : 'background:#fff;color:#374151;border-color:#E5E7EB';
};

$statusOptions = [
    'pending' => 'Pendientes',
    'approved' => 'Aprobadas',
    'rejected' => 'Rechazadas',
];
?>

<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px;flex-wrap:wrap">
  <div>
    <h1 style="font-size:1.45rem;margin:0;color:#111827">Reportes de App</h1>
    <p style="margin:6px 0 0;color:#6B7280;font-size:.9rem">Gestiona reportes sociales, fotografias pendientes y cuentas suspendidas.</p>
  </div>
  <a href="<?= BASE_URL ?>restaurante/dashboard"
     style="text-decoration:none;border:1px solid #E5E7EB;background:#fff;color:#374151;border-radius:8px;padding:9px 12px;font-weight:700;font-size:.84rem">
    Volver al dashboard
  </a>
</div>

<?php if (!$available && !$fotosAvailable): ?>
<div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:18px;color:#78350F">
  <strong>No hay tablas de moderacion disponibles.</strong>
  <div style="font-size:.86rem;margin-top:6px">Ejecuta las migraciones sociales para habilitar <code>social_reports</code>, <code>social_blocks</code>, <code>social_photo_moderation</code> y <code>mobile_usuarios</code>.</div>
</div>
<?php else: ?>

<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:18px">
  <a href="<?= BASE_URL ?>rest-moderacion/index?tab=reportes"
     style="<?= $tabStyle($tab, 'reportes') ?>;text-decoration:none;border:1px solid;border-radius:8px;padding:10px 12px;font-weight:800;font-size:.84rem">
    Reportes de usuarios
  </a>
  <a href="<?= BASE_URL ?>rest-moderacion/index?tab=fotos"
     style="<?= $tabStyle($tab, 'fotos') ?>;text-decoration:none;border:1px solid;border-radius:8px;padding:10px 12px;font-weight:800;font-size:.84rem">
    Fotos pendientes
    <span style="display:inline-flex;margin-left:6px;border-radius:999px;background:<?= $tab === 'fotos' ? 'rgba(255,255,255,.18)' : '#FEF2F2' ?>;color:<?= $tab === 'fotos' ? '#fff' : '#991B1B' ?>;padding:1px 7px">
      <?= (int)($fotosResultado['pending_count'] ?? 0) ?>
    </span>
  </a>
</div>

<?php if ($tab === 'reportes'): ?>
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
    <table class="rst-table" style="min-width:980px">
      <thead>
        <tr>
          <th>Usuario</th>
          <th>Estado</th>
          <th style="text-align:right">Reportes</th>
          <th style="text-align:right">Pendientes</th>
          <th style="text-align:right">Bloqueos</th>
          <th>Ultimo reporte</th>
          <th>Suspension por foto</th>
          <th style="text-align:right">Accion</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
        <?php
          $activo = (int)($u['activo'] ?? 0) === 1;
          $pendientes = (int)($u['reportes_pendientes'] ?? 0);
          $fotoSuspension = $u['suspension_foto'] ?? null;
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
          <td style="min-width:220px">
            <?php if ($fotoSuspension): ?>
            <div style="display:flex;gap:10px;align-items:flex-start">
              <img src="<?= htmlspecialchars($fotoSuspension['photo_url'] ?? '') ?>" alt="" style="width:54px;height:54px;border-radius:8px;object-fit:cover;border:1px solid #E5E7EB">
              <div style="font-size:.76rem;color:#374151;line-height:1.35">
                <strong style="color:#991B1B">Provino de fotografia</strong><br>
                <?= htmlspecialchars($fotoSuspension['review_notes'] ?? 'Sin motivo registrado') ?><br>
                <span style="color:#6B7280"><?= htmlspecialchars($fotoSuspension['moderador_nombre'] ?? ('Admin #' . ($fotoSuspension['reviewed_by'] ?? '-'))) ?> - <?= htmlspecialchars($fmtDate($fotoSuspension['reviewed_at'] ?? null)) ?></span>
              </div>
            </div>
            <?php else: ?>
            <span style="color:#9CA3AF;font-size:.78rem">-</span>
            <?php endif; ?>
          </td>
          <td style="text-align:right">
            <?php if ($activo): ?>
            <form method="post" action="<?= BASE_URL ?>rest-moderacion/desactivar/<?= (int)$u['id'] ?>" style="display:inline" onsubmit="return confirm('Desactivar esta cuenta de la app?');">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
              <button type="submit" style="border:0;background:#DC2626;color:white;border-radius:8px;padding:8px 10px;font-weight:800;cursor:pointer">Desactivar</button>
            </form>
            <?php else: ?>
            <form method="post" action="<?= BASE_URL ?>rest-moderacion/reactivar/<?= (int)$u['id'] ?>" style="display:inline" onsubmit="return confirm('Reactivar esta cuenta? Social permanecera desactivado y no se restauraran fotografias rechazadas.');">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
              <button type="submit" style="border:1px solid #A7F3D0;background:#ECFDF5;color:#047857;border-radius:8px;padding:8px 10px;font-weight:800;cursor:pointer">Reactivar</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($usuarios)): ?>
        <tr><td colspan="8" style="text-align:center;color:#9CA3AF;padding:24px">No hay cuentas reportadas.</td></tr>
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
        <form method="post" action="<?= BASE_URL ?>rest-moderacion/reporte/<?= (int)$r['id'] ?>"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="accion" value="revisar"><button type="submit" style="border:1px solid #A7F3D0;background:#ECFDF5;color:#047857;border-radius:8px;padding:8px 10px;font-weight:800;cursor:pointer">Revisado</button></form>
        <form method="post" action="<?= BASE_URL ?>rest-moderacion/reporte/<?= (int)$r['id'] ?>"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="accion" value="descartar"><button type="submit" style="border:1px solid #E5E7EB;background:#F9FAFB;color:#374151;border-radius:8px;padding:8px 10px;font-weight:800;cursor:pointer">Descartar</button></form>
        <form method="post" action="<?= BASE_URL ?>rest-moderacion/reporte/<?= (int)$r['id'] ?>"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="accion" value="reabrir"><button type="submit" style="border:1px solid #FDE68A;background:#FFFBEB;color:#92400E;border-radius:8px;padding:8px 10px;font-weight:800;cursor:pointer">Reabrir</button></form>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($reportes)): ?>
    <div style="text-align:center;color:#9CA3AF;padding:24px">No hay reportes registrados.</div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($tab === 'fotos'): ?>
<div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;margin-bottom:18px;padding:16px">
  <form method="get" action="<?= BASE_URL ?>rest-moderacion/index" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input type="hidden" name="tab" value="fotos">
    <select name="status" style="border:1px solid #D1D5DB;border-radius:8px;padding:9px 10px;font-weight:700;color:#374151;background:#fff">
      <?php foreach ($statusOptions as $value => $label): ?>
      <option value="<?= htmlspecialchars($value) ?>" <?= $fotoStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="search" name="search" value="<?= htmlspecialchars($fotoSearch) ?>" placeholder="Buscar por correo, nombre o URL" style="min-width:240px;flex:1;border:1px solid #D1D5DB;border-radius:8px;padding:9px 10px">
    <button type="submit" style="border:0;background:#111827;color:#fff;border-radius:8px;padding:10px 12px;font-weight:800;cursor:pointer">Filtrar</button>
  </form>
</div>

<?php if (!$fotosAvailable): ?>
<div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;padding:18px;color:#78350F">
  <strong>No hay cola de fotografias disponible.</strong>
  <div style="font-size:.86rem;margin-top:6px">Falta la tabla <code>social_photo_moderation</code> o sus columnas principales.</div>
</div>
<?php else: ?>
<div style="display:grid;gap:16px">
  <?php foreach ($fotos as $foto): ?>
  <?php
    [$photoStatusText, $photoStatusBg, $photoStatusColor] = $photoStatusLabel($foto['status'] ?? 'pending');
    $accountActive = (int)($foto['activo'] ?? 0) === 1;
    $socialActive = (int)($foto['social_activo'] ?? 0) === 1;
  ?>
  <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;overflow:hidden">
    <div style="display:grid;grid-template-columns:minmax(260px,360px) minmax(0,1fr);gap:0">
      <div style="background:#F9FAFB;border-right:1px solid #EEF2F7;padding:14px">
        <img src="<?= htmlspecialchars($foto['photo_url'] ?? '') ?>" alt="" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px;border:1px solid #E5E7EB;background:#fff">
      </div>
      <div style="padding:16px 18px;display:grid;gap:14px">
        <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap">
          <div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
              <h2 style="font-size:1rem;margin:0;color:#111827"><?= htmlspecialchars($foto['usuario_nombre'] ?? 'Usuario') ?></h2>
              <span style="font-size:.7rem;font-weight:800;border-radius:99px;padding:3px 8px;background:<?= $photoStatusBg ?>;color:<?= $photoStatusColor ?>"><?= htmlspecialchars($photoStatusText) ?></span>
            </div>
            <div style="font-size:.8rem;color:#6B7280;margin-top:4px"><?= htmlspecialchars($foto['usuario_email'] ?? $foto['usuario_meta'] ?? '') ?></div>
            <div style="font-size:.78rem;color:#6B7280;margin-top:3px">Publicada: <?= htmlspecialchars($fmtDate($foto['created_at'] ?? null)) ?></div>
          </div>
          <a href="<?= BASE_URL ?>rest-cliente/detalle/app-<?= (int)($foto['user_id'] ?? 0) ?>" style="height:fit-content;text-decoration:none;border:1px solid #E5E7EB;background:#fff;color:#374151;border-radius:8px;padding:8px 10px;font-weight:800;font-size:.8rem">Ver perfil completo</a>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <span style="display:inline-flex;border-radius:999px;padding:4px 9px;font-size:.74rem;font-weight:800;background:<?= $accountActive ? '#ECFDF5' : '#FEF2F2' ?>;color:<?= $accountActive ? '#047857' : '#991B1B' ?>">Cuenta <?= $accountActive ? 'activa' : 'suspendida' ?></span>
          <span style="display:inline-flex;border-radius:999px;padding:4px 9px;font-size:.74rem;font-weight:800;background:<?= $socialActive ? '#EFF6FF' : '#F3F4F6' ?>;color:<?= $socialActive ? '#1D4ED8' : '#4B5563' ?>">Social <?= $socialActive ? 'activo' : 'inactivo' ?></span>
          <span style="display:inline-flex;border-radius:999px;padding:4px 9px;font-size:.74rem;font-weight:800;background:#F9FAFB;color:#374151;border:1px solid #E5E7EB"><?= (int)($foto['reportes_existentes'] ?? 0) ?> reportes</span>
        </div>

        <div>
          <div style="font-size:.76rem;color:#6B7280;font-weight:800;margin-bottom:8px">Resto de fotografias del perfil</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php foreach (($foto['profile_photos'] ?? []) as $profilePhoto): ?>
            <img src="<?= htmlspecialchars($profilePhoto) ?>" alt="" style="width:72px;height:72px;border-radius:8px;object-fit:cover;border:1px solid #E5E7EB;background:#F9FAFB">
            <?php endforeach; ?>
            <?php if (empty($foto['profile_photos'])): ?>
            <span style="color:#9CA3AF;font-size:.8rem">Sin fotografias adicionales.</span>
            <?php endif; ?>
          </div>
        </div>

        <?php if (($foto['status'] ?? '') === 'pending'): ?>
        <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap">
          <form method="post" action="<?= BASE_URL ?>rest-moderacion/foto/<?= (int)$foto['id'] ?>" class="moderation-action-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="decision" value="approved">
            <button type="submit" style="border:1px solid #A7F3D0;background:#ECFDF5;color:#047857;border-radius:8px;padding:9px 12px;font-weight:800;cursor:pointer">Aprobar</button>
          </form>
          <button type="button"
                  data-reject-photo-id="<?= (int)$foto['id'] ?>"
                  data-reject-photo-url="<?= htmlspecialchars($foto['photo_url'] ?? '') ?>"
                  data-reject-user="<?= htmlspecialchars($foto['usuario_nombre'] ?? 'Usuario') ?>"
                  style="border:0;background:#DC2626;color:#fff;border-radius:8px;padding:9px 12px;font-weight:800;cursor:pointer">
            Rechazar y suspender
          </button>
        </div>
        <?php elseif (($foto['status'] ?? '') === 'rejected'): ?>
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:10px;color:#7F1D1D;font-size:.84rem">
          <strong>Motivo:</strong> <?= nl2br(htmlspecialchars($foto['review_notes'] ?? 'Sin motivo registrado')) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($fotos)): ?>
  <div style="background:#fff;border:1px solid #E5E7EB;border-radius:12px;text-align:center;color:#9CA3AF;padding:28px">No hay fotografias para este filtro.</div>
  <?php endif; ?>
</div>

<?php if (($fotoPagination['pages'] ?? 0) > 1): ?>
<div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
  <?php
    $currentPage = (int)($fotoPagination['page'] ?? 1);
    $pages = (int)($fotoPagination['pages'] ?? 1);
    $baseQuery = 'tab=fotos&status=' . rawurlencode($fotoStatus) . '&search=' . rawurlencode($fotoSearch) . '&page=';
  ?>
  <?php if ($currentPage > 1): ?>
  <a href="<?= BASE_URL ?>rest-moderacion/index?<?= $baseQuery . ($currentPage - 1) ?>" style="text-decoration:none;border:1px solid #E5E7EB;background:#fff;color:#374151;border-radius:8px;padding:8px 10px;font-weight:800">Anterior</a>
  <?php endif; ?>
  <span style="display:inline-flex;align-items:center;color:#6B7280;font-weight:800;font-size:.82rem">Pagina <?= $currentPage ?> de <?= $pages ?></span>
  <?php if ($currentPage < $pages): ?>
  <a href="<?= BASE_URL ?>rest-moderacion/index?<?= $baseQuery . ($currentPage + 1) ?>" style="text-decoration:none;border:1px solid #E5E7EB;background:#fff;color:#374151;border-radius:8px;padding:8px 10px;font-weight:800">Siguiente</a>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<div class="rst-modal-backdrop" id="rejectPhotoModal" style="position:fixed;inset:0;background:rgba(17,24,39,.55);align-items:center;justify-content:center;padding:18px;z-index:1000">
  <div style="background:#fff;border-radius:12px;max-width:560px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden">
    <form method="post" action="" id="rejectPhotoForm" class="moderation-action-form">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="decision" value="rejected">
      <div style="padding:18px 20px;border-bottom:1px solid #EEF2F7">
        <h2 style="margin:0;color:#111827;font-size:1.05rem">Retirar foto y suspender cuenta</h2>
        <div style="font-size:.82rem;color:#6B7280;margin-top:5px" id="rejectPhotoUser"></div>
      </div>
      <div style="padding:18px 20px;display:grid;gap:14px">
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px;color:#7F1D1D;font-size:.88rem;line-height:1.45">
          La fotografia se eliminara y la cuenta completa quedara suspendida. El usuario vera este motivo al intentar ingresar.
        </div>
        <img src="" alt="" id="rejectPhotoPreview" style="width:100%;max-height:240px;object-fit:cover;border-radius:8px;border:1px solid #E5E7EB;background:#F9FAFB">
        <label style="display:grid;gap:6px;font-size:.82rem;font-weight:800;color:#374151">
          Motivo
          <select id="rejectReason" style="border:1px solid #D1D5DB;border-radius:8px;padding:10px;background:#fff">
            <option value="Contenido sexual.">Contenido sexual.</option>
            <option value="Violencia o amenazas.">Violencia o amenazas.</option>
            <option value="Acoso.">Acoso.</option>
            <option value="Suplantacion.">Suplantacion.</option>
            <option value="Menor de edad.">Menor de edad.</option>
            <option value="Spam.">Spam.</option>
            <option value="Otro.">Otro.</option>
          </select>
        </label>
        <label style="display:grid;gap:6px;font-size:.82rem;font-weight:800;color:#374151">
          Descripcion obligatoria
          <textarea name="notes" id="rejectNotes" required minlength="10" rows="4" style="border:1px solid #D1D5DB;border-radius:8px;padding:10px;resize:vertical" placeholder="Describe el motivo que vera el usuario."></textarea>
        </label>
        <label style="display:flex;gap:8px;align-items:flex-start;color:#374151;font-size:.84rem;line-height:1.4">
          <input type="checkbox" name="confirm_suspend" value="1" id="rejectConfirm" required style="margin-top:3px">
          Confirmo que esta accion retirara la fotografia, suspendera la cuenta completa y no restaurara la foto al reactivar.
        </label>
      </div>
      <div style="padding:14px 20px;border-top:1px solid #EEF2F7;display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap">
        <button type="button" id="rejectCancel" style="border:1px solid #E5E7EB;background:#fff;color:#374151;border-radius:8px;padding:9px 12px;font-weight:800;cursor:pointer">Cancelar</button>
        <button type="submit" id="rejectSubmit" style="border:0;background:#DC2626;color:#fff;border-radius:8px;padding:9px 12px;font-weight:800;cursor:pointer">Retirar foto y suspender</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const modal = document.getElementById('rejectPhotoModal');
  const form = document.getElementById('rejectPhotoForm');
  const preview = document.getElementById('rejectPhotoPreview');
  const user = document.getElementById('rejectPhotoUser');
  const reason = document.getElementById('rejectReason');
  const notes = document.getElementById('rejectNotes');
  const confirmBox = document.getElementById('rejectConfirm');
  const cancel = document.getElementById('rejectCancel');

  function openModal(btn) {
    const id = btn.getAttribute('data-reject-photo-id');
    form.action = '<?= BASE_URL ?>rest-moderacion/foto/' + encodeURIComponent(id);
    preview.src = btn.getAttribute('data-reject-photo-url') || '';
    user.textContent = btn.getAttribute('data-reject-user') || '';
    reason.value = 'Contenido sexual.';
    notes.value = '';
    confirmBox.checked = false;
    modal.classList.add('open');
    notes.focus();
  }

  document.querySelectorAll('[data-reject-photo-id]').forEach(btn => {
    btn.addEventListener('click', () => openModal(btn));
  });
  reason.addEventListener('change', () => {
    if (!notes.value.trim() || notes.value.trim() === 'Otro.') {
      notes.value = reason.value === 'Otro.' ? '' : reason.value + ' ';
      notes.focus();
    }
  });
  cancel.addEventListener('click', () => modal.classList.remove('open'));
  modal.addEventListener('click', e => {
    if (e.target === modal) modal.classList.remove('open');
  });
  document.querySelectorAll('.moderation-action-form').forEach(item => {
    item.addEventListener('submit', () => {
      item.querySelectorAll('button').forEach(button => button.disabled = true);
    });
  });
})();
</script>

<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
