<?php ob_start(); ?>

<style>
  .promo-list-shell{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px}
  .promo-table-card{display:none;background:#fff;border-radius:14px;border:1px solid #E5E7EB;overflow:hidden;box-shadow:0 10px 24px rgba(15,23,42,.06)}
  .promo-code-pill{display:inline-block;border:1px solid #E5E7EB;background:#F8FAFC;border-radius:999px;padding:5px 9px;font-family:monospace;font-size:.78rem}
  .promo-rule-pill{display:inline-block;border-radius:999px;padding:5px 10px;background:#EEF2FF;color:#3730A3;font-weight:700;font-size:.76rem}
</style>

<div class="promo-list-shell">
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
<div id="promo-table" class="promo-table-card">
  <div style="width:100%;overflow-x:auto">
  <table style="width:100%;min-width:900px;border-collapse:collapse;font-size:.875rem;table-layout:fixed">
    <colgroup>
      <col style="width:25%">
      <col style="width:16%">
      <col style="width:15%">
      <col style="width:11%">
      <col style="width:11%">
      <col style="width:12%">
      <col style="width:10%">
    </colgroup>
    <thead>
      <tr style="background:#F9FAFB;border-bottom:1px solid #E5E7EB">
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Promoción</th>
        <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151">Usuario</th>
        <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151">Regla</th>
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

  tbodyEl.addEventListener('click', function(e) {
    var btn = e.target.closest('button[data-action]');
    if (!btn) return;

    var id = parseInt(btn.getAttribute('data-id'), 10) || 0;
    var titulo = btn.getAttribute('data-title') || 'Sin título';
    if (!id) return;

    if (btn.getAttribute('data-action') === 'delete') {
      eliminarPromocion(id, titulo);
    } else if (btn.getAttribute('data-action') === 'deactivate') {
      desactivarPromocion(id, titulo);
    }
  });

  /**
   * Carga la lista de promociones desde la API
   */
  async function cargarPromociones() {
    statusEl.style.display = 'block';
    tableEl.style.display = 'none';
    emptyEl.style.display = 'none';

    var resp = await ApiClient.get('/admin/promotions?page=1&per_page=100');

    if (!resp.success) {
      var errorMsg = resp.message || 'Error desconocido';

      // Mensajes específicos según el código HTTP
      if (resp.httpCode === 401) {
        errorMsg = 'Token de conexión con Amare expirado. Reconecta en <strong>Configuración > Conexión API Amare-App</strong>.';
      } else if (resp.httpCode === 404) {
        errorMsg = 'Restaurante no vinculado a Amare. Verifica la configuración en el panel de administración.';
      } else if (resp.httpCode >= 500) {
        errorMsg = 'Error de conexión con la app móvil. Intenta más tarde.';
      }

      statusEl.innerHTML = '<div style="color:#DC2626">' + errorMsg + '</div>'
        + '<button onclick="adminRecargarPromociones()" style="margin-top:12px;background:var(--cp);color:#fff;border:none;border-radius:6px;padding:8px 16px;cursor:pointer;font-weight:500">Reintentar</button>';
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
    var id = parseInt(p.id, 10) || 0;
    var titulo = esc(p.titulo || 'Sin título');
    var desc   = p.descripcion ? esc(p.descripcion) : '';
    var usuario = esc(p.usuario_nombre || p.usuario_email || '—');
    var code   = p.code ? esc(p.code) : '—';
    var expira = p.expires_at ? formatearFecha(p.expires_at) : 'Sin expiración';
    var rawTitulo = p.titulo || 'Sin título';

    var regla = getRuleLabel(p);
    var estadoInfo = getEstadoInfo(p);
    var badgeColor = estadoInfo.color;
    var badgeBg    = estadoInfo.bg;
    var badgeText  = estadoInfo.label;
    var pushInfo = getPushInfo(p);

    // Botones de acción: Editar y Eliminar siempre; Desactivar solo si activa y no expirada
    var btnDesactivar = '';
    var activo = parseInt(p.activo) === 1;
    if (activo && p.expires_at) {
      var expiraDate = new Date(p.expires_at.replace(' ', 'T'));
      if (expiraDate >= new Date()) {
        btnDesactivar = '<button type="button" data-action="deactivate" data-id="' + id + '" data-title="' + escAttr(rawTitulo) + '" ' +
                        'style="background:none;border:none;color:#D97706;font-size:.82rem;font-weight:500;cursor:pointer;padding:0;margin-right:12px">' +
                        'Desactivar</button>';
      }
    }

    return '<tr style="border-bottom:1px solid #F3F4F6">'
      + '<td style="padding:12px 16px;min-width:0">'
      +   '<div style="font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + titulo + '</div>'
      +   (desc ? '<div style="font-size:.78rem;color:#6B7280;margin-top:2px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + desc + '</div>' : '')
      + '</td>'
      + '<td style="padding:12px 16px;font-size:.82rem;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + usuario + '</td>'
      + '<td style="padding:12px 16px;text-align:center"><span class="promo-rule-pill">' + esc(regla) + '</span></td>'
      + '<td style="padding:12px 16px;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><span class="promo-code-pill">' + code + '</span></td>'
      + '<td style="padding:12px 16px;text-align:center;font-size:.78rem;color:#6B7280">' + expira + '</td>'
      + '<td style="padding:12px 16px;text-align:center">'
      +   '<span style="display:inline-block;padding:4px 10px;border-radius:99px;font-size:.75rem;font-weight:600;background:' + badgeBg + ';color:' + badgeColor + '">' + badgeText + '</span>'
      +   (pushInfo.label ? '<div title="' + escAttr(pushInfo.title) + '" style="margin-top:6px;font-size:.72rem;font-weight:600;color:' + pushInfo.color + '">' + pushInfo.label + '</div>' : '')
      +   (pushInfo.detail ? '<div title="' + escAttr(pushInfo.title) + '" style="margin-top:3px;font-size:.68rem;color:#6B7280;line-height:1.2">' + esc(pushInfo.detail) + '</div>' : '')
      + '</td>'
      + '<td style="padding:12px 16px;text-align:right;white-space:nowrap">'
      +   btnDesactivar
      +   '<a href="<?= BASE_URL ?>rest-promocion/editar/' + id + '" style="font-size:.82rem;color:var(--cp);font-weight:500;text-decoration:none;margin-right:12px">Editar</a>'
      +   '<button type="button" data-action="delete" data-id="' + id + '" data-title="' + escAttr(rawTitulo) + '" style="background:none;border:none;color:#EF4444;font-size:.82rem;font-weight:500;cursor:pointer;padding:0">Eliminar</button>'
      + '</td>'
      + '</tr>';
  }

  function getRuleLabel(p) {
    var type = (p.tipo_descuento || 'porcentaje').toString();
    if (type === 'bxgy') {
      return (p.buy_qty || 2) + 'x' + (p.pay_qty || 1);
    }
    if (type === 'monto_fijo') {
      return '$' + Number(p.valor_descuento || 0).toFixed(2);
    }
    return Number(p.valor_descuento || 0).toFixed(0) + '% OFF';
  }

  /**
   * Determina el estado visual de una promoción
   * Estados: Activa, Expirada, Inactiva, Programada
   */
  function getEstadoInfo(p) {
    var activo = parseInt(p.activo) === 1;

    // Inactiva: si está desactivada explícitamente
    if (!activo) {
      return { color: '#EF4444', bg: '#FEF2F2', label: 'Inactiva' };
    }

    // Si tiene fecha de expiración
    if (p.expires_at) {
      try {
        var expiraDate = new Date(p.expires_at.replace(' ', 'T'));
        var ahora = new Date();

        // Expirada: si la fecha de expiración ya pasó
        if (expiraDate < ahora) {
          return { color: '#9CA3AF', bg: '#F3F4F6', label: 'Expirada' };
        }

        // Programada: si se creó en el futuro
        if (p.created_at) {
          var creada = new Date(p.created_at.replace(' ', 'T'));
          if (creada > ahora) {
            return { color: '#D97706', bg: '#FFFBEB', label: 'Programada' };
          }
        }
      } catch (e) {
        // Si hay error al parsear fechas, asumir activa
      }
    }

    // Activa: en todos los otros casos
    return { color: '#059669', bg: '#ECFDF5', label: 'Activa' };
  }

  function getPushInfo(p) {
    var status = (p.notification_status || '').toString().toLowerCase();
    var error = p.notification_error || '';

    if (!status) {
      return { label: '', detail: '', color: '#6B7280', title: '' };
    }
    if (status === 'sent') {
      return { label: 'Push enviada', detail: '', color: '#059669', title: p.notification_sent_at ? ('Enviada: ' + p.notification_sent_at) : 'Notificacion enviada' };
    }
    if (status === 'failed') {
      return { label: 'Push fallida', detail: getPushErrorHint(error), color: '#EF4444', title: error ? ('Error: ' + error) : 'Firebase rechazo el envio' };
    }
    if (status === 'skipped') {
      var label = 'Push no enviada';
      if (error === 'missing_fcm_config') label = 'Falta FCM';
      if (error === 'no_push_token') label = 'Sin token';
      return { label: label, detail: getPushErrorHint(error), color: '#D97706', title: error || 'Envio omitido' };
    }
    return { label: 'Push pendiente', detail: '', color: '#6B7280', title: error || 'Pendiente' };
  }

  function getPushErrorHint(error) {
    error = (error || '').toString();
    var lower = error.toLowerCase();
    if (!error) return '';
    if (lower.indexOf('third_party_auth_error') >= 0 || lower.indexOf('apns') >= 0) {
      return 'Revisar APNs iOS';
    }
    if (lower.indexOf('missing required authentication') >= 0 || lower.indexOf('oauth 2 access token') >= 0) {
      return 'Revisar OAuth Firebase';
    }
    if (lower.indexOf('sender') >= 0 || lower.indexOf('mismatch') >= 0) {
      return 'Proyecto Firebase distinto';
    }
    if (lower.indexOf('unregistered') >= 0 || lower.indexOf('registration token') >= 0 || lower.indexOf('invalid') >= 0) {
      return 'Token invalido';
    }
    if (lower.indexOf('permission') >= 0 || lower.indexOf('not found') >= 0) {
      return 'Revisar Firebase';
    }
    if (lower.indexOf('missing_fcm_config') >= 0) {
      return 'Configurar Firebase';
    }
    if (lower.indexOf('no_push_token') >= 0) {
      return 'Abrir app y permitir push';
    }
    return error.length > 34 ? error.substring(0, 34) + '...' : error;
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
    div.appendChild(document.createTextNode(String(str == null ? '' : str)));
    return div.innerHTML;
  }

  function escAttr(str) {
    return esc(str).replace(/"/g, '&quot;');
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

  /**
   * Desactivar promoción vía API (PUT /admin/promotions/{id}/deactivate)
   */
  window.desactivarPromocion = async function(id, titulo) {
    if (!confirm('¿Desactivar la promoción "' + titulo + '"?\nSeguirá existiendo pero no se mostrará en la app.')) return;

    var resp = await ApiClient.put('/admin/promotions/' + id + '/deactivate', {});

    if (resp.success) {
      ApiClient.flash('success', 'Promoción desactivada correctamente.');
      cargarPromociones();
    } else {
      var errorMsg = resp.message || 'Error desconocido';
      if (resp.httpCode === 404) {
        errorMsg = 'Promoción no encontrada.';
      } else if (resp.httpCode === 401) {
        errorMsg = 'Token de Amare expirado. Reconecta en Configuración.';
      }
      ApiClient.flash('error', 'Error al desactivar: ' + errorMsg);
    }
  };

  // Exponer recarga para que pueda llamarse desde fuera
  async function asegurarSesionApi() {
    if (!ApiClient.isLoggedIn()) {
      await ApiClient.getTokenFromSession();
    }
  }

  async function recargarPromociones() {
    await asegurarSesionApi();
    return cargarPromociones();
  }

  window.adminRecargarPromociones = recargarPromociones;

  // Cargar al iniciar
  recargarPromociones();
})();
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
