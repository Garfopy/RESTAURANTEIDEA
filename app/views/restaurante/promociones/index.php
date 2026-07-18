<?php ob_start(); ?>

<style>
  .promo-list-shell{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px}
  .promo-table-card{display:none;background:rgba(255,255,255,.96);border-radius:18px;border:1px solid #E2E8F0;overflow:hidden;box-shadow:0 18px 55px rgba(15,23,42,.08)}
  .promo-table-scroll{width:100%;overflow-x:auto;padding:0 14px 14px;scrollbar-color:#CBD5E1 #F8FAFC}
  .promo-table{width:100%;min-width:1040px;border-collapse:separate;border-spacing:0 10px;font-size:.875rem;table-layout:fixed}
  .promo-table thead th{position:sticky;top:0;z-index:1;background:#fff!important;padding:16px 12px 10px!important;font-size:.72rem!important;font-weight:800!important;color:#64748B!important;letter-spacing:.06em;text-transform:uppercase;border-bottom:1px solid #EEF2F7}
  .promo-table thead th.is-center{text-align:center}
  .promo-table thead th.is-right{text-align:right}
  .promo-row td{background:#fff;border-top:1px solid #EAF0F6;border-bottom:1px solid #EAF0F6;padding:14px 12px;vertical-align:middle}
  .promo-row td:first-child{border-left:1px solid #EAF0F6;border-radius:12px 0 0 12px}
  .promo-row td:last-child{border-right:1px solid #EAF0F6;border-radius:0 12px 12px 0}
  .promo-row:hover td{background:#FBFCFE;border-color:#DDE6F1;box-shadow:0 10px 24px rgba(15,23,42,.04)}
  .promo-title{font-weight:800;color:#111827;line-height:1.25;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .promo-desc{font-size:.78rem;color:#64748B;margin-top:4px;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .promo-user{font-weight:600;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .promo-date{font-weight:700;color:#64748B;text-align:center;white-space:nowrap}
  .promo-code-pill{display:inline-flex;max-width:100%;align-items:center;border:1px solid #DDE4EE;background:#F8FAFC;border-radius:999px;padding:6px 10px;font-family:ui-monospace,SFMono-Regular,Consolas,"Liberation Mono",monospace;font-size:.76rem;color:#0F172A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .promo-rule-pill{display:inline-flex;border-radius:999px;padding:6px 11px;background:#EEF2FF;color:#3730A3;font-weight:800;font-size:.76rem;white-space:nowrap}
  .promo-status-cell{text-align:center}
  .promo-state-badge{display:inline-flex;align-items:center;justify-content:center;padding:5px 11px;border-radius:99px;font-size:.75rem;font-weight:800;white-space:nowrap}
  .promo-push-label{margin-top:7px;font-size:.72rem;font-weight:800;line-height:1.15}
  .promo-push-detail{margin-top:3px;font-size:.68rem;color:#64748B;line-height:1.2}
  .promo-actions{display:flex;justify-content:flex-end;align-items:center;gap:7px;flex-wrap:wrap}
  .promo-action{appearance:none;border:1px solid transparent;border-radius:8px;background:#F8FAFC;color:#334155;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:.77rem;font-weight:800;line-height:1;padding:8px 10px;text-decoration:none;transition:background .16s,border-color .16s,color .16s,transform .16s}
  .promo-action:hover{transform:translateY(-1px)}
  .promo-action-primary{background:#EFF6FF;color:#2563EB;border-color:#DBEAFE}
  .promo-action-primary:hover{background:#DBEAFE}
  .promo-action-warning{background:#FFFBEB;color:#B45309;border-color:#FDE68A}
  .promo-action-warning:hover{background:#FEF3C7}
  .promo-action-edit{background:var(--cp-light);color:var(--cp);border-color:color-mix(in srgb,var(--cp) 18%,#fff)}
  .promo-action-edit:hover{background:var(--cp-mid)}
  .promo-action-danger{background:#FEF2F2;color:#DC2626;border-color:#FECACA}
  .promo-action-danger:hover{background:#FEE2E2}
  @media (max-width:768px){
    .promo-table-scroll{padding:0 10px 10px}
    .promo-table{min-width:980px}
    .promo-table thead th,.promo-row td{padding-left:10px;padding-right:10px}
  }
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
  <div class="promo-table-scroll">
  <table class="promo-table">
    <colgroup>
      <col style="width:24%">
      <col style="width:15%">
      <col style="width:12%">
      <col style="width:13%">
      <col style="width:10%">
      <col style="width:13%">
      <col style="width:13%">
    </colgroup>
    <thead>
      <tr>
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
    } else if (btn.getAttribute('data-action') === 'notify') {
      enviarPushPromocion(id, titulo);
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
    var btnEnviarPush = '';
    var activo = parseInt(p.activo) === 1;
    if (activo && parseInt(p.has_push_token || 0, 10) === 1) {
      btnEnviarPush = '<button type="button" data-action="notify" data-id="' + id + '" data-title="' + escAttr(rawTitulo) + '" ' +
                      'class="promo-action promo-action-primary">' +
                      'Enviar push</button>';
    }
    if (activo && p.expires_at) {
      var expiraDate = new Date(p.expires_at.replace(' ', 'T'));
      if (expiraDate >= new Date()) {
        btnDesactivar = '<button type="button" data-action="deactivate" data-id="' + id + '" data-title="' + escAttr(rawTitulo) + '" ' +
                        'class="promo-action promo-action-warning">' +
                        'Desactivar</button>';
      }
    }

    return '<tr class="promo-row">'
      + '<td>'
      +   '<div class="promo-title">' + titulo + '</div>'
      +   (desc ? '<div class="promo-desc">' + desc + '</div>' : '')
      + '</td>'
      + '<td><div class="promo-user">' + usuario + '</div></td>'
      + '<td style="text-align:center"><span class="promo-rule-pill">' + esc(regla) + '</span></td>'
      + '<td style="text-align:center"><span class="promo-code-pill">' + code + '</span></td>'
      + '<td class="promo-date">' + expira + '</td>'
      + '<td class="promo-status-cell">'
      +   '<span class="promo-state-badge" style="background:' + badgeBg + ';color:' + badgeColor + '">' + badgeText + '</span>'
      +   (pushInfo.label ? '<div class="promo-push-label" title="' + escAttr(pushInfo.title) + '" style="color:' + pushInfo.color + '">' + pushInfo.label + '</div>' : '')
      +   (pushInfo.detail ? '<div class="promo-push-detail" title="' + escAttr(pushInfo.title) + '">' + esc(pushInfo.detail) + '</div>' : '')
      + '</td>'
      + '<td>'
      +   '<div class="promo-actions">'
      +     btnEnviarPush
      +     btnDesactivar
      +     '<a href="<?= BASE_URL ?>rest-promocion/editar/' + id + '" class="promo-action promo-action-edit">Editar</a>'
      +     '<button type="button" data-action="delete" data-id="' + id + '" data-title="' + escAttr(rawTitulo) + '" class="promo-action promo-action-danger">Eliminar</button>'
      +   '</div>'
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
    var hasToken = parseInt(p.has_push_token || 0, 10) === 1;

    if (!status) {
      if (hasToken) {
        return { label: 'Token listo', detail: 'Push pendiente', color: '#2563EB', title: 'El usuario tiene token push activo' };
      }
      return { label: 'Sin token', detail: 'Push pendiente', color: '#D97706', title: 'No hay token push activo para este usuario' };
    }
    if (status === 'sent') {
      return { label: 'Push enviada', detail: '', color: '#059669', title: p.notification_sent_at ? ('Enviada: ' + p.notification_sent_at) : 'Notificacion enviada' };
    }
    if (status === 'failed') {
      if (isInvalidPushTokenError(error)) {
        return { label: 'Push fallida', detail: 'Token invalido', color: '#EF4444', title: 'La promocion esta guardada, pero el token push del usuario ya no es valido' };
      }
      return { label: 'Push fallida', detail: getPushErrorHint(error), color: '#EF4444', title: error ? ('Error: ' + error) : 'Firebase rechazo el envio' };
    }
    if (status === 'skipped') {
      var label = 'Push no enviada';
      if (error === 'missing_fcm_config') label = 'Falta FCM';
      if (error === 'no_push_token') label = 'Sin token';
      if (error === 'invalid_push_token') label = 'Push fallida';
      if (error === 'no_push_token' && hasToken) {
        return { label: 'Token listo', detail: 'Reintenta el envio', color: '#2563EB', title: 'Antes no habia token, pero ahora el usuario tiene token push activo' };
      }
      return { label: label, detail: getPushErrorHint(error), color: '#D97706', title: error || 'Envio omitido' };
    }
    return { label: 'Push pendiente', detail: '', color: '#6B7280', title: error || 'Pendiente' };
  }

  function isInvalidPushTokenError(error) {
    var lower = (error || '').toString().toLowerCase();
    return lower.indexOf('invalid_push_token') >= 0
      || lower.indexOf('unregistered') >= 0
      || lower.indexOf('registration token') >= 0
      || lower.indexOf('requested entity was not found') >= 0;
  }

  function getPushErrorHint(error) {
    error = (error || '').toString();
    var lower = error.toLowerCase();
    if (!error) return '';
    if (isInvalidPushTokenError(error)) {
      return 'Token invalido';
    }
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

  window.enviarPushPromocion = async function(id, titulo) {
    if (!confirm('¿Enviar notificación push de "' + titulo + '" ahora?')) return;

    var resp = await ApiClient.post('/admin/promotions/' + id + '/notify', {});

    if (resp.success) {
      var notification = resp.data && resp.data.notification ? resp.data.notification : null;
      if (notification && isInvalidPushTokenError(notification.error)) {
        ApiClient.flash('error', 'Token vencido: la promocion esta guardada, pero el usuario debe abrir la app para reactivar push.');
        cargarPromociones();
        return;
      }
      if (notification && notification.status === 'failed') {
        ApiClient.flash('error', 'Push fallida: ' + (notification.error || 'Firebase rechazó el envío'));
      } else if (notification && notification.status === 'skipped') {
        ApiClient.flash('error', 'Push no enviada: ' + (notification.error || 'Sin detalle'));
      } else {
        ApiClient.flash('success', 'Push enviada correctamente.');
      }
      cargarPromociones();
    } else {
      ApiClient.flash('error', 'Error al enviar push: ' + (resp.message || 'Error desconocido'));
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
