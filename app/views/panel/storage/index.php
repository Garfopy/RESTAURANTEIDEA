<?php
// Variables: $dirs (array keyed by slug), $totalSize (int), $totalOld (int), $historial (array), $flash
$retDays = 60;
$managedDirs = ['entregas' => 'Fotos de entrega', 'firmas' => 'Firmas de repartidor'];
?>

<!-- Cabecera de política -->
<div style="background:linear-gradient(135deg,#1E3A5F 0%,#C8102E 100%);color:#fff;border-radius:14px;padding:22px 26px;margin-bottom:22px;display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
  <div>
    <h2 style="margin:0 0 6px;font-size:1.1rem;font-weight:800">Gestión de almacenamiento</h2>
    <p style="margin:0;font-size:.85rem;opacity:.9">Política de retención: <strong><?= $retDays ?> días</strong> para fotos de entrega y firmas. Exporta o elimina archivos por rango de fechas.</p>
  </div>
  <div style="text-align:right;white-space:nowrap">
    <div style="font-size:1.6rem;font-weight:800"><?php
      $tb = $totalSize ?? 0;
      if ($tb >= 1073741824)      echo round($tb/1073741824,1).' GB';
      elseif ($tb >= 1048576)     echo round($tb/1048576,1).' MB';
      elseif ($tb >= 1024)        echo round($tb/1024,1).' KB';
      else                        echo $tb.' B';
    ?></div>
    <div style="font-size:.75rem;opacity:.8">total en dirs monitoreados</div>
  </div>
</div>

<!-- KPI cards resumen -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px">
  <?php
  $kpiCards = [
    ['label'=>'Espacio total usado',    'valor'=> (function($b){ if($b>=1073741824) return round($b/1073741824,1).' GB'; if($b>=1048576) return round($b/1048576,1).' MB'; if($b>=1024) return round($b/1024,1).' KB'; return $b.' B'; })($totalSize??0), 'bg'=>'#EFF6FF','color'=>'#1E40AF'],
    ['label'=>'Archivos > '.$retDays.' días', 'valor'=>number_format($totalOld??0),      'bg'=>($totalOld??0)>0?'#FFF7ED':'#F0FDF4','color'=>($totalOld??0)>0?'#9A3412':'#166534'],
    ['label'=>'Directorios monitoreados','valor'=>count($managedDirs),                   'bg'=>'#F5F3FF','color'=>'#5B21B6'],
  ];
  foreach ($kpiCards as $k): ?>
  <div style="background:<?= $k['bg'] ?>;border-radius:12px;padding:16px 20px">
    <div style="font-size:.72rem;font-weight:600;color:<?= $k['color'] ?>;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em"><?= $k['label'] ?></div>
    <div style="font-size:1.7rem;font-weight:800;color:<?= $k['color'] ?>"><?= $k['valor'] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Cards por directorio -->
<?php foreach ($dirs as $slug => $info): ?>
<?php
$pctOld = $info['count'] > 0 ? ($info['old_count'] / $info['count']) * 100 : 0;
$barColor = $pctOld >= 50 ? '#EF4444' : ($pctOld >= 20 ? '#F59E0B' : '#10B981');
?>
<div style="background:#fff;border:1px solid #E5E7EB;border-radius:14px;margin-bottom:16px;overflow:hidden">
  <div style="padding:16px 20px;background:#F9FAFB;border-bottom:1px solid #E5E7EB;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
    <div>
      <span style="font-weight:800;font-size:.95rem;color:#111827"><?= htmlspecialchars($info['label']) ?></span>
      <span style="margin-left:8px;font-size:.75rem;color:#6B7280;background:#E5E7EB;padding:2px 8px;border-radius:999px"><?= $slug ?>/</span>
    </div>
    <div style="display:flex;gap:20px;font-size:.82rem;color:#374151">
      <span>Total: <strong><?= $info['label_size'] ?></strong></span>
      <span>Archivos: <strong><?= number_format($info['count']) ?></strong></span>
      <span style="color:<?= $info['old_count'] > 0 ? '#B45309' : '#059669' ?>">
        &gt;<?= $retDays ?>d: <strong><?= number_format($info['old_count']) ?></strong>
      </span>
    </div>
  </div>

  <div style="padding:14px 20px">
    <!-- Barra de antigüedad -->
    <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#6B7280;margin-bottom:5px">
      <span>Archivos fuera de retención</span>
      <span><?= round($pctOld) ?>%</span>
    </div>
    <div style="height:8px;background:#E5E7EB;border-radius:999px;overflow:hidden;margin-bottom:14px">
      <div style="height:100%;border-radius:999px;background:<?= $barColor ?>;width:<?= min(100, $pctOld) ?>%;transition:width .4s"></div>
    </div>

    <!-- Fechas -->
    <?php if ($info['oldest'] || $info['newest']): ?>
    <div style="font-size:.78rem;color:#6B7280;margin-bottom:12px">
      <?php if ($info['oldest']): ?>Archivo más antiguo: <strong><?= date('d/m/Y', strtotime($info['oldest'])) ?></strong><?php endif; ?>
      <?php if ($info['oldest'] && $info['newest']): ?> &nbsp;·&nbsp; <?php endif; ?>
      <?php if ($info['newest']): ?>Archivo más reciente: <strong><?= date('d/m/Y', strtotime($info['newest'])) ?></strong><?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Acordeón archivos más antiguos -->
    <?php if (!empty($info['oldest10'])): ?>
    <details style="border:1px solid #E5E7EB;border-radius:8px;overflow:hidden">
      <summary style="padding:8px 14px;font-size:.8rem;font-weight:600;color:#374151;cursor:pointer;background:#F9FAFB;user-select:none">
        Ver <?= count($info['oldest10']) ?> archivos más antiguos
      </summary>
      <table style="width:100%;border-collapse:collapse;font-size:.78rem">
        <thead><tr style="background:#F3F4F6">
          <th style="padding:6px 12px;text-align:left;color:#6B7280;font-weight:600">Archivo</th>
          <th style="padding:6px 12px;text-align:right;color:#6B7280;font-weight:600">Tamaño</th>
          <th style="padding:6px 12px;text-align:left;color:#6B7280;font-weight:600">Fecha</th>
        </tr></thead>
        <tbody>
          <?php foreach ($info['oldest10'] as $f): ?>
          <tr style="border-top:1px solid #F3F4F6">
            <td style="padding:6px 12px;color:#374151;font-family:monospace"><?= htmlspecialchars($f['name']) ?></td>
            <td style="padding:6px 12px;text-align:right;color:#6B7280"><?php
              $b=$f['size'];
              if($b>=1048576) echo round($b/1048576,1).' MB';
              elseif($b>=1024) echo round($b/1024,1).' KB';
              else echo $b.' B';
            ?></td>
            <td style="padding:6px 12px;color:#6B7280"><?= date('d/m/Y H:i', $f['mtime']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </details>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<!-- Panel de acciones -->
<div style="background:#fff;border:1px solid #E5E7EB;border-radius:14px;overflow:hidden;margin-bottom:20px">
  <div style="padding:16px 20px;background:#F9FAFB;border-bottom:1px solid #E5E7EB">
    <h3 style="margin:0;font-size:.95rem;font-weight:700;color:#111827">Acciones por rango de fechas</h3>
    <p style="margin:4px 0 0;font-size:.8rem;color:#6B7280">Selecciona directorio y rango, previsualiza cuántos archivos se verán afectados y luego exporta o elimina.</p>
  </div>
  <div style="padding:20px">

    <!-- Controles compartidos -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:16px">
      <div>
        <label style="font-size:.78rem;font-weight:600;color:#374151;display:block;margin-bottom:5px">Directorio</label>
        <select id="accionDir" style="width:100%;padding:9px 12px;border:1.5px solid #D1D5DB;border-radius:8px;font-size:.875rem;color:#111827;background:#fff;outline:none"
                onchange="resetPreview()">
          <option value="ambos">Ambos directorios</option>
          <?php foreach ($managedDirs as $slug => $label): ?>
          <option value="<?= $slug ?>"><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:.78rem;font-weight:600;color:#374151;display:block;margin-bottom:5px">Desde</label>
        <input type="date" id="accionDesde" value="<?= date('Y-m-d', strtotime('-60 days')) ?>"
               style="width:100%;padding:9px 12px;border:1.5px solid #D1D5DB;border-radius:8px;font-size:.875rem;color:#111827;outline:none;box-sizing:border-box"
               onchange="resetPreview()">
      </div>
      <div>
        <label style="font-size:.78rem;font-weight:600;color:#374151;display:block;margin-bottom:5px">Hasta</label>
        <input type="date" id="accionHasta" value="<?= date('Y-m-d') ?>"
               style="width:100%;padding:9px 12px;border:1.5px solid #D1D5DB;border-radius:8px;font-size:.875rem;color:#111827;outline:none;box-sizing:border-box"
               onchange="resetPreview()">
      </div>
    </div>

    <!-- Vista previa -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
      <button type="button" id="btnPreview" onclick="hacerPreview()"
              style="padding:9px 20px;background:#1E3A5F;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer;transition:opacity .15s"
              onmouseenter="this.style.opacity='.85'" onmouseleave="this.style.opacity='1'">
        Vista previa
      </button>
      <div id="previewResult" style="font-size:.875rem;color:#374151"></div>
    </div>

    <!-- Exportar ZIP -->
    <form method="POST" action="<?= BASE_URL ?>admin-storage/exportarZip" id="formExportar">
      <input type="hidden" name="directorio"   id="expDir">
      <input type="hidden" name="fecha_desde"  id="expDesde">
      <input type="hidden" name="fecha_hasta"  id="expHasta">
      <button type="submit" id="btnExportar" disabled
              style="padding:10px 24px;background:#059669;color:#fff;border:none;border-radius:9px;font-weight:700;font-size:.875rem;cursor:pointer;opacity:.5;margin-bottom:20px;transition:all .2s"
              onmouseenter="if(!this.disabled)this.style.opacity='.85'" onmouseleave="if(!this.disabled)this.style.opacity='1'">
        Exportar ZIP + resumen HTML
      </button>
    </form>

    <!-- Sección eliminar (colapsada) -->
    <details id="sectionEliminar" style="border:2px solid #FEE2E2;border-radius:10px;overflow:hidden">
      <summary style="padding:12px 16px;background:#FEF2F2;font-size:.875rem;font-weight:700;color:#991B1B;cursor:pointer;user-select:none;display:flex;align-items:center;gap:8px">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        Eliminar archivos del rango — Acción irreversible
      </summary>
      <div style="padding:16px">
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 14px;margin-bottom:14px;font-size:.83rem;color:#7F1D1D">
          Esta acción <strong>elimina permanentemente</strong> los archivos del disco. No se puede deshacer. Asegúrate de haber exportado el ZIP primero si necesitas conservar los archivos.
        </div>
        <form method="POST" action="<?= BASE_URL ?>admin-storage/eliminar" id="formEliminar">
          <input type="hidden" name="directorio"   id="delDir">
          <input type="hidden" name="fecha_desde"  id="delDesde">
          <input type="hidden" name="fecha_hasta"  id="delHasta">
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <div>
              <label style="font-size:.78rem;font-weight:600;color:#374151;display:block;margin-bottom:5px">Escribe <strong>ELIMINAR</strong> para confirmar</label>
              <input type="text" name="confirmacion" id="inputConfirm" placeholder="ELIMINAR"
                     oninput="checkConfirm()"
                     style="padding:9px 14px;border:1.5px solid #FECACA;border-radius:8px;font-size:.9rem;font-weight:700;color:#991B1B;letter-spacing:.06em;outline:none;width:160px">
            </div>
            <button type="submit" id="btnEliminar" disabled
                    style="margin-top:20px;padding:10px 24px;background:#EF4444;color:#fff;border:none;border-radius:9px;font-weight:700;font-size:.875rem;cursor:not-allowed;opacity:.4;transition:all .2s"
                    onmouseenter="if(!this.disabled)this.style.opacity='.85'" onmouseleave="if(!this.disabled)this.style.opacity='1'">
              Eliminar archivos
            </button>
          </div>
        </form>
      </div>
    </details>
  </div>
</div>

<!-- Historial de acciones -->
<?php if (!empty($historial)): ?>
<div style="background:#fff;border:1px solid #E5E7EB;border-radius:14px;overflow:hidden;margin-bottom:20px">
  <div style="padding:14px 20px;background:#F9FAFB;border-bottom:1px solid #E5E7EB">
    <h3 style="margin:0;font-size:.875rem;font-weight:700;color:#111827">Historial de acciones</h3>
  </div>
  <table style="width:100%;border-collapse:collapse;font-size:.82rem">
    <thead>
      <tr style="background:#F9FAFB">
        <th style="padding:8px 16px;text-align:left;color:#6B7280;font-weight:600">Acción</th>
        <th style="padding:8px;text-align:left;color:#6B7280;font-weight:600">Descripción</th>
        <th style="padding:8px;text-align:left;color:#6B7280;font-weight:600">Usuario</th>
        <th style="padding:8px;text-align:left;color:#6B7280;font-weight:600">Fecha</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($historial as $h): ?>
      <tr style="border-top:1px solid #F3F4F6">
        <td style="padding:8px 16px;font-weight:600;color:#111827"><?= htmlspecialchars($h['accion']) ?></td>
        <td style="padding:8px;color:#374151;max-width:300px"><?= htmlspecialchars($h['descripcion'] ?? '') ?></td>
        <td style="padding:8px;color:#374151"><?= htmlspecialchars($h['nombre'] ?? '—') ?></td>
        <td style="padding:8px;color:#6B7280"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<script>
var previewDone = false;

function resetPreview() {
  previewDone = false;
  document.getElementById('previewResult').textContent = '';
  var btnExp = document.getElementById('btnExportar');
  btnExp.disabled = true;
  btnExp.style.opacity = '.5';
  btnExp.style.cursor  = 'not-allowed';
  checkConfirm();
}

function hacerPreview() {
  var dir   = document.getElementById('accionDir').value;
  var desde = document.getElementById('accionDesde').value;
  var hasta = document.getElementById('accionHasta').value;
  var btn   = document.getElementById('btnPreview');
  var res   = document.getElementById('previewResult');

  if (!desde || !hasta) { res.textContent = 'Selecciona un rango de fechas.'; return; }

  btn.disabled = true;
  res.innerHTML = '<span style="color:#6B7280">Calculando...</span>';

  fetch('<?= BASE_URL ?>admin-storage/preview', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'directorio=' + encodeURIComponent(dir) + '&fecha_desde=' + encodeURIComponent(desde) + '&fecha_hasta=' + encodeURIComponent(hasta)
  })
  .then(r => r.json())
  .then(data => {
    btn.disabled = false;
    if (data.error) { res.innerHTML = '<span style="color:#EF4444">Error: ' + data.error + '</span>'; return; }
    var txt = '<strong>' + data.count + ' archivo' + (data.count !== 1 ? 's' : '') + '</strong> — ' + data.size_label;
    if (data.oldest) txt += ' &nbsp;|&nbsp; Del ' + data.oldest + ' al ' + (data.newest || data.oldest);
    res.innerHTML = '<span style="color:' + (data.count > 0 ? '#059669' : '#6B7280') + '">' + txt + '</span>';

    previewDone = data.count > 0;
    var btnExp = document.getElementById('btnExportar');
    btnExp.disabled = !previewDone;
    btnExp.style.opacity = previewDone ? '1' : '.5';
    btnExp.style.cursor  = previewDone ? 'pointer' : 'not-allowed';

    // Sync hidden inputs
    document.getElementById('expDir').value   = dir;
    document.getElementById('expDesde').value = desde;
    document.getElementById('expHasta').value = hasta;
    document.getElementById('delDir').value   = dir;
    document.getElementById('delDesde').value = desde;
    document.getElementById('delHasta').value = hasta;
  })
  .catch(() => { btn.disabled = false; res.innerHTML = '<span style="color:#EF4444">Error de red.</span>'; });
}

function checkConfirm() {
  var val = document.getElementById('inputConfirm').value;
  var btn = document.getElementById('btnEliminar');
  var ok  = val === 'ELIMINAR' && previewDone;
  btn.disabled   = !ok;
  btn.style.opacity = ok ? '1' : '.4';
  btn.style.cursor  = ok ? 'pointer' : 'not-allowed';
}
</script>
