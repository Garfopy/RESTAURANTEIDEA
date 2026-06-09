<?php ob_start(); ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <h2 style="margin:0;font-size:1.15rem;color:#111827">🎁 Promociones</h2>
    <p style="margin:4px 0 0;font-size:.82rem;color:#6B7280">
      Crea descuentos especiales para comensales específicos. Se sincronizan automáticamente con la app móvil.
    </p>
  </div>
  <a href="<?= BASE_URL ?>rest-promocion/crear"
     style="background:var(--cp);color:#fff;border:none;border-radius:8px;padding:10px 20px;
            font-size:.85rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:8px;
            white-space:nowrap">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Nueva Promoción
  </a>
</div>

<!-- Mensaje de carga / error -->
<div id="promo-status" style="text-align:center;padding:40px;color:#6B7280;font-size:.88rem">
  Cargando promociones...
</div>

<!-- Tabla de promociones (oculta hasta que carguen datos) -->
<div id="promo-table" style="display:none;background:#fff;border-radius:12px;border:1px solid #E5E7EB;overflow:hidden">
  <table style="width:100%;border-collapse:collapse;font-size:.875rem">
    <thead>
      <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Promoción</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Usuario</th>
        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151">Código</th>
        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151">Expira</th>
        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151">Estado</th>
        <th style="padding:12px 16px;text-align:right;font-weight:600;color:#374151">Acciones</th>
      </tr>
    </thead>
    <tbody id="promo-tbody">
    </tbody>
  </table>
</div>

<!-- Mensaje vacío (oculto inicialmente) -->
<div id="promo-empty" style="display:none;background:#F9FAFB;border:2px dashed #D1D5DB;border-radius:12px;padding:48px 24px;text-align:center">
  <div style="font-size:2.5rem;margin-bottom:12px">🎁</div>
  <div style="font-weight:600;color:#374151;font-size:1rem;margin-bottom:6px">No hay promociones creadas</div>
  <div style="color:#9CA3AF;font-size:.82rem;margin-bottom:16px">
    Crea tu primera promoción para ofrecer descuentos a tus comensales desde la app móvil.
  </div>
  <a href="<?= BASE_URL ?>rest-promocion/crear"
     style="display:inline-block;background:var(--cp);color:#fff;padding:10px 24px;border-radius:8px;
            font-weight:600;font-size:.85rem;text-decoration:none">
    Crear primera promoción
  </a>
</div>

<script>
(function() {
  'use strict';

  var statusEl  = document.getElementById('promo-status');
  var tableEl   = document.getElementById('promo-table');
  var tbodyEl   = document.getElementById('promo-tbody');
  var emptyEl   = document.getElementById('promo-empty');

  /**
   * Carga la lista de promociones desde la API
   */
  async function cargarPromociones() {
    statusEl.style.display = 'block';
    tableEl.style.display = 'none';
    emptyEl.style.display = 'none';

    var resp = await ApiClient.get('/admin/promotions?page=1&per_page=100');

    if (!resp.success) {
      statusEl.innerHTML = '<div style="color:#DC2626">Error al cargar: ' + ApiClient._esc(resp.message || 'Error desconocido') + '</div>'
        + '<button onclick="cargarPromociones()" style="margin-top:12px;background:var(--cp);color:#fff;border:none;border-radius:6px;padding:8px 16px;cursor:pointer;font-weight:500">Reintentar</button>';
      return;
    }

    var promotions = resp.data && resp.data.promotions ? resp.data.promotions : [];

    if (promotions.length === 0) {
      statusEl.style.display = 'none';
      emptyEl.style.display = 'block';
      return;
    }

    statusEl.style.display = 'none';
    tableEl.style.display = 'block';

    // Renderizar filas
    var html = '';
    for (var i = 0; i < promotions.length; i++) {
      var p = promotions[i];
      html += renderFila(p);
    }
    tbodyEl.innerHTML = html;
  }

  /**
   * Renderiza una fila de la tabla
   */
  function renderFila(p) {
    var titulo = esc(p.titulo || 'Sin título');
    var desc   = p.descripcion ? esc(p.descripcion) : '';
    var usuario = esc(p.usuario_nombre || p.usuario_email || '—');
    var code   = p.code ? esc(p.code) : '—';
    var expira = p.expires_at ? formatearFecha(p.expires_at) : 'Sin expiración';

    var estadoInfo = getEstadoInfo(p);
    var badgeColor = estadoInfo.color;
    var badgeBg    = estadoInfo.bg;
    var badgeText  = estadoInfo.label;

    return '<tr style="border-bottom:1px solid #F3F4F6">'
      + '<td style="padding:12px 16px">'
      +   '<div style="font-weight:600;color:#111827">' + titulo + '</div>'
      +   (desc ? '<div style="font-size:.78rem;color:#6B7280;margin-top:2px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + desc + '</div>' : '')
      + '</td>'
      + '<td style="padding:12px 16px;font-size:.82rem;color:#374151">' + usuario + '</td>'
      + '<td style="padding:12px 16px;text-align:center;font-family:monospace;font-size:.82rem">' + code + '</td>'
      + '<td style="padding:12px 16px;text-align:center;font-size:.78rem;color:#6B7280">' + expira + '</td>'
      + '<td style="padding:12px 16px;text-align:center">'
      +   '<span style="display:inline-block;padding:4px 10px;border-radius:99px;font-size:.75rem;font-weight:600;background:' + badgeBg + ';color:' + badgeColor + '">' + badgeText + '</span>'
      + '</td>'
      + '<td style="padding:12px 16px;text-align:right;white-space:nowrap">'
      +   '<a href="<?= BASE_URL ?>rest-promocion/editar/' + p.id + '" style="font-size:.82rem;color:var(--cp);font-weight:500;text-decoration:none;margin-right:12px">Editar</a>'
      +   '<button onclick="eliminarPromocion(' + p.id + ',\'' + titulo.replace(/'/g, "\\'") + '\')" style="background:none;border:none;color:#EF4444;font-size:.82rem;font-weight:500;cursor:pointer;padding:0">Eliminar</button>'
      + '</td>'
      + '</tr>';
  }

  /**
   * Determina el estado visual de una promoción
   */
  function getEstadoInfo(p) {
    var activo = parseInt(p.activo) === 1;
    if (!activo) return { color: '#EF4444', bg: '#FEF2F2', label: 'Inactiva' };

    if (p.expires_at) {
      var expiraDate = new Date(p.expires_at.replace(' ', 'T'));
      var ahora = new Date();
      if (expiraDate < ahora) {
        return { color: '#9CA3AF', bg: '#F3F4F6', label: 'Expirada' };
      }
      // Si se creó en el futuro (programada)
      if (p.created_at) {
        var creada = new Date(p.created_at.replace(' ', 'T'));
        if (creada > ahora) {
          return { color: '#D97706', bg: '#FFFBEB', label: 'Programada' };
        }
      }
    }

    return { color: '#059669', bg: '#ECFDF5', label: 'Activa' };
  }

  function formatearFecha(fechaStr) {
    if (!fechaStr) return '';
    var d = new Date(fechaStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return fechaStr;
    var dd = String(d.getDate()).padStart(2, '0');
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    var yy = d.getFullYear();
    return dd + '/' + mm + '/' + yy;
  }

  function esc(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  /**
   * Eliminar promoción vía API
   */
  window.eliminarPromocion = async function(id, titulo) {
    if (!confirm('¿Eliminar la promoción "' + titulo + '"?\nEsta acción no se puede deshacer.')) return;

    var resp = await ApiClient.del('/admin/promotions/' + id);

    if (resp.success) {
      ApiClient.flash('success', 'Promoción eliminada correctamente.');
      cargarPromociones();
    } else {
      ApiClient.flash('error', 'Error al eliminar: ' + (resp.message || 'Error desconocido'));
    }
  };

  // Exponer recarga para que pueda llamarse desde fuera
  window.adminRecargarPromociones = cargarPromociones;

  // Cargar al iniciar
  cargarPromociones();
})();
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';