<?php ob_start(); ?>
<?php
$nombre = trim((string)($comensal['nombre'] ?? ''))
  ?: trim((string)($comensal['mobile_nombre'] ?? ''))
  ?: trim((string)($comensal['mobile_email'] ?? ''))
  ?: 'Visitante anonimo';
$telefono = trim((string)($comensal['telefono'] ?? '')) ?: trim((string)($comensal['mobile_telefono'] ?? ''));
$email = trim((string)($comensal['email'] ?? '')) ?: trim((string)($comensal['mobile_email'] ?? ''));
$totalGastado = (float)($comensal['gasto_total'] ?? $comensal['total_gastado'] ?? 0);
$visitasTotales = (int)($comensal['total_visitas'] ?? $comensal['num_visitas'] ?? 0);
$ultimaVisita = !empty($comensal['ultima_visita']) ? date('d/m/Y', strtotime($comensal['ultima_visita'])) : '&mdash;';
$esMobile = ($comensal['origen'] ?? '') === 'mobile';
$productosFavoritos = $productosFavoritos ?? [];
$promocionSugerida = $promocionSugerida ?? [
  'titulo' => 'Promocion de bienvenida',
  'descripcion' => 'Aun no hay suficientes productos para detectar un favorito claro.',
  'mecanica' => 'Ofrece una bebida o postre de cortesia en su proxima visita.',
];
$promocionApp = $promocionApp ?? [];
$detalleParam = $detalleParam ?? (string)($comensal['id'] ?? '');
$puedeEnviarPromoApp = !empty($comensal['mobile_usuario_id']) && !empty($promocionApp['code']);
$promoProductoObjetivo = trim((string)($promocionApp['producto_favorito'] ?? ''));
$maxCantidad = 1;
foreach ($productosFavoritos as $producto) {
  $maxCantidad = max($maxCantidad, (float)($producto['cantidad_total'] ?? 0));
}
?>
<div class="client-page">
  <a href="<?= BASE_URL ?>rest-cliente/index" class="client-back">
    <span class="client-back-icon" aria-hidden="true">&larr;</span>
    <span>Comensales</span>
  </a>

  <section class="client-hero">
    <div class="client-hero-main">
      <div class="client-title-row">
        <div class="client-title"><?= htmlspecialchars($nombre) ?></div>
        <span class="client-source-badge <?= $esMobile ? 'app' : 'web' ?>"><?= $esMobile ? 'App' : 'Web' ?></span>
      </div>
      <div class="client-subtitle">
        <?= $telefono !== '' ? htmlspecialchars($telefono) : '&mdash;' ?>
        <?= $email !== '' ? ' &middot; ' . htmlspecialchars($email) : '' ?>
        <?= !empty($comensal['mobile_usuario_id']) ? ' &middot; App #' . (int)$comensal['mobile_usuario_id'] : '' ?>
      </div>

      <div class="client-stats-grid">
        <div class="client-stat">
          <div class="client-stat-label">Visitas totales</div>
          <div class="client-stat-value"><?= $visitasTotales ?></div>
        </div>
        <div class="client-stat">
          <div class="client-stat-label">Total gastado</div>
          <div class="client-stat-value">$<?= number_format($totalGastado, 2) ?></div>
        </div>
        <div class="client-stat">
          <div class="client-stat-label">Ultima visita</div>
          <div class="client-stat-value" style="font-size:1.12rem"><?= $ultimaVisita ?></div>
        </div>
      </div>
    </div>
  </section>

  <div class="client-insights-grid">
    <section class="client-panel">
      <div class="client-panel-header">
        <div class="client-panel-title">Productos que mas compra</div>
        <div class="client-panel-note">Basado en pedidos</div>
      </div>
      <?php if (!empty($productosFavoritos)): ?>
        <div class="favorite-list">
          <?php foreach ($productosFavoritos as $producto): ?>
            <?php
              $cantidad = (float)($producto['cantidad_total'] ?? 0);
              $porcentaje = min(100, max(8, ($cantidad / $maxCantidad) * 100));
            ?>
            <div class="favorite-item">
              <div>
                <div class="favorite-name"><?= htmlspecialchars((string)$producto['nombre']) ?></div>
                <div class="favorite-meta">
                  <?= number_format($cantidad, $cantidad === floor($cantidad) ? 0 : 1) ?> vendidos
                  &middot; <?= (int)($producto['veces'] ?? 0) ?> pedidos
                </div>
              </div>
              <div class="favorite-amount">$<?= number_format((float)($producto['total_gastado'] ?? 0), 2) ?></div>
              <div class="favorite-bar"><span style="width:<?= $porcentaje ?>%"></span></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state" style="padding:36px 24px">Aun no hay productos suficientes para analizar preferencias.</div>
      <?php endif; ?>
    </section>

    <aside class="client-promo-card">
      <div class="client-promo-eyebrow">Promocion sugerida</div>
      <div class="client-promo-title"><?= htmlspecialchars((string)($promocionApp['titulo'] ?? $promocionSugerida['titulo'])) ?></div>
      <div class="client-promo-copy"><?= htmlspecialchars((string)($promocionApp['descripcion'] ?? $promocionSugerida['descripcion'])) ?></div>
      <div class="client-promo-mechanic">
        <?php if (!empty($promocionApp)): ?>
          <div style="display:grid;gap:8px">
            <div><strong>Descuento:</strong> <?= number_format((float)$promocionApp['valor_descuento'], 0) ?>%<?= $promoProductoObjetivo !== '' ? ' en ' . htmlspecialchars($promoProductoObjetivo) : ' sobre la proxima visita' ?></div>
            <div><strong>Codigo:</strong> <span style="font-family:monospace;letter-spacing:.08em"><?= htmlspecialchars((string)$promocionApp['code']) ?></span></div>
            <div><strong>Vigencia:</strong> <?= date('d/m/Y', strtotime((string)$promocionApp['fecha_inicio'])) ?> al <?= date('d/m/Y', strtotime((string)$promocionApp['fecha_fin'])) ?></div>
          </div>
        <?php else: ?>
          <?= htmlspecialchars((string)$promocionSugerida['mecanica']) ?>
        <?php endif; ?>
      </div>
      <?php if ($puedeEnviarPromoApp): ?>
        <form method="post" action="<?= BASE_URL ?>rest-cliente/enviarPromocion/<?= urlencode($detalleParam) ?>" style="margin-top:16px">
          <button type="submit"
                  onclick="return confirm('Enviar esta promocion a la app movil de este comensal?')"
                  style="width:100%;border:none;border-radius:14px;background:#fff;color:#111827;padding:12px 16px;font-weight:900;cursor:pointer">
            Enviar promocion
          </button>
        </form>
      <?php else: ?>
        <div style="margin-top:14px;color:#CBD5E1;font-size:.82rem;line-height:1.45">
          Para enviarla a la app, este comensal debe estar vinculado con un usuario movil.
        </div>
      <?php endif; ?>
    </aside>
  </div>

  <section class="client-panel">
    <div class="client-panel-header">
      <div class="client-panel-title">Historial</div>
      <div class="client-panel-note"><?= count($historial) ?> registros</div>
    </div>
    <?php foreach ($historial as $v): ?>
    <?php
      $ticketTotal = array_key_exists('ticket_total', $v) && $v['ticket_total'] !== null ? (float)$v['ticket_total'] : null;
      $pedidoTotal = (float)($v['pedido_total'] ?? 0);
      $totalVisita = $ticketTotal !== null ? $ticketTotal : $pedidoTotal;
      $fechaHistorial = !empty($v['created_at']) ? date('d/m/Y H:i', strtotime($v['created_at'])) : '&mdash;';
      $mesaNombre = trim((string)($v['mesa_nombre'] ?? ''));
      if (($v['historial_origen'] ?? '') === 'mobile') {
        $estadoPago = 'pedido app';
        if (!empty($v['estado'])) {
          $estadoPago .= ' - ' . $v['estado'];
        }
        if (!empty($v['metodo_pago'])) {
          $estadoPago .= ' / ' . $v['metodo_pago'];
        }
      } else {
        $estadoPago = $ticketTotal !== null ? ($v['metodo_pago'] ?? 'pagado') : 'pedidos sin ticket pagado';
      }
    ?>
    <div class="client-history-row">
      <div>
        <div class="client-history-date">
          <?= $fechaHistorial ?><?= $mesaNombre !== '' ? ' &middot; ' . htmlspecialchars($mesaNombre) : '' ?>
        </div>
        <?php if (!empty($v['items_resumen'])): ?>
          <div class="client-history-items"><?= htmlspecialchars($v['items_resumen']) ?></div>
        <?php endif; ?>
        <div class="client-history-meta"><?= htmlspecialchars($estadoPago) ?></div>
      </div>
      <div class="client-history-total">$<?= number_format($totalVisita, 2) ?></div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($historial)): ?><div class="empty-state">Sin historial.</div><?php endif; ?>
  </section>
</div>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
