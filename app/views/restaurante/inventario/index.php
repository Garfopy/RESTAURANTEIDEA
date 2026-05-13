<?php ob_start(); ?>

<style>
.inv-grid {
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(290px,1fr));
  gap:16px;
  margin-bottom:32px;
}
.inv-card {
  background:#fff;
  border-radius:14px;
  border:1.5px solid #E5E7EB;
  padding:16px;
  transition:box-shadow .15s,border-color .15s;
  position:relative;
  overflow:hidden;
}
.inv-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); border-color:#D1D5DB; }
.inv-card.bajo { border-color:#FECACA; background:#FFFBFB; }
.inv-card.bajo::before {
  content:'';
  position:absolute;top:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,#EF4444,#F87171);
}
.inv-card.ok::before {
  content:'';
  position:absolute;top:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,#22C55E,#4ADE80);
}
.inv-card-head {
  display:flex;justify-content:space-between;align-items:flex-start;
  margin-bottom:12px;gap:8px;
}
.inv-card-name {
  font-weight:700;font-size:.95rem;color:#111827;
  line-height:1.25;flex:1;min-width:0;
}
.inv-card-name small {
  display:block;font-size:.72rem;font-weight:400;color:#9CA3AF;margin-top:1px;
}
.inv-stock-bar-wrap { margin-bottom:10px; }
.inv-stock-label {
  display:flex;justify-content:space-between;
  font-size:.75rem;color:#6B7280;margin-bottom:4px;
}
.inv-stock-label strong { color:#111827; }
.inv-bar {
  height:7px;border-radius:4px;background:#F3F4F6;overflow:hidden;
}
.inv-bar-fill {
  height:100%;border-radius:4px;transition:width .3s;
  background:linear-gradient(90deg,#22C55E,#4ADE80);
}
.inv-bar-fill.low { background:linear-gradient(90deg,#EF4444,#F87171); }
.inv-bar-fill.warn { background:linear-gradient(90deg,#F59E0B,#FBBF24); }
.inv-card-meta {
  display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;align-items:center;
}
.inv-card-cost {
  font-size:.78rem;color:#6B7280;
  margin-bottom:10px;
}
.inv-card-cost strong { color:#374151; }
.inv-card-actions {
  display:flex;gap:6px;
}
.inv-card-actions .btn { flex:1;justify-content:center;font-size:.78rem;padding:5px 8px; }

/* Movements table */
.mov-row { display:grid;grid-template-columns:90px 1fr 80px 90px 80px;gap:8px;
  align-items:center;padding:10px 0;border-bottom:1px solid #F3F4F6;font-size:.82rem; }
.mov-row:last-child { border-bottom:none; }
.mov-row .mov-tipo { display:inline-flex;align-items:center;gap:4px; }
.mov-row .mov-ing { font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.mov-row .mov-fecha { color:#9CA3AF;font-size:.75rem; }
.mov-header { font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#9CA3AF; }
</style>

<!-- Alertas stock bajo -->
<?php if (!empty($alertas)): ?>
<div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
  <svg width="18" height="18" fill="none" stroke="#EF4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
  <span style="font-size:.85rem;font-weight:600;color:#991B1B">
    <?= count($alertas) ?> ingrediente<?= count($alertas) > 1 ? 's' : '' ?> con stock bajo:
    <?= implode(', ', array_column($alertas, 'nombre')) ?>
  </span>
</div>
<?php endif; ?>

<!-- Guía colapsable -->
<div id="guia-inv" style="display:none;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:16px;margin-bottom:16px;font-size:.84rem;color:#1E3A5F">
  <div style="font-weight:700;margin-bottom:10px;font-size:.92rem">📋 Cómo operar el inventario</div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <div><span style="background:#DCFCE7;color:#166534;border-radius:6px;padding:2px 8px;font-weight:600;font-size:.75rem">＋ Entrada</span>
      — Cuando recibes mercancía, compras ingredientes o devuelven producto. <strong>Suma</strong> al stock.</div>
    <div><span style="background:#FEE2E2;color:#991B1B;border-radius:6px;padding:2px 8px;font-weight:600;font-size:.75rem">－ Salida</span>
      — Uso directo sin pasar por pedido (ej: consumo del personal). <strong>Resta</strong> del stock.</div>
    <div><span style="background:#FEF3C7;color:#92400E;border-radius:6px;padding:2px 8px;font-weight:600;font-size:.75rem">Merma</span>
      — Producto caducado, dañado o derramado. Registra la pérdida.</div>
    <div><span style="background:#DBEAFE;color:#1E40AF;border-radius:6px;padding:2px 8px;font-weight:600;font-size:.75rem">Ajuste</span>
      — Corrección manual tras conteo físico del almacén.</div>
  </div>
  <div style="margin-top:10px;padding-top:10px;border-top:1px solid #BFDBFE">
    <strong>Alerta stock bajo:</strong> cuando el stock llega al mínimo que configures, aparece un aviso rojo. <br>
    <strong>Descuento automático:</strong> cuando el chef marca un pedido como "listo", los ingredientes de la receta se descuentan solos. <br>
    <strong>Stock mínimo:</strong> edita el ingrediente y ajusta el campo "Stock mínimo" para configurar desde qué cantidad te alertamos.
  </div>
</div>

<!-- Barra de herramientas -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px">
  <div style="display:flex;gap:8px;align-items:center;flex:1">
    <div style="position:relative;flex:1;max-width:320px">
      <svg width="16" height="16" fill="none" stroke="#9CA3AF" viewBox="0 0 24 24"
           style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none">
        <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
      </svg>
      <input type="text" id="invBuscar" oninput="filtrarIngredientes()"
             placeholder="Buscar ingrediente o categoría…"
             style="width:100%;padding:8px 12px 8px 34px;border:1.5px solid #E5E7EB;border-radius:10px;
                    font-size:.85rem;box-sizing:border-box;outline:none"
             onfocus="this.style.borderColor='var(--cp)'" onblur="this.style.borderColor='#E5E7EB'">
    </div>
    <a href="<?= BASE_URL ?>rest-inventario/movimientos" class="btn btn-outline btn-sm" style="white-space:nowrap">Ver historial</a>
    <button onclick="toggleGuia()" title="Ayuda"
            style="padding:7px 11px;border:1.5px solid #E5E7EB;border-radius:10px;background:#fff;
                   cursor:pointer;font-size:.85rem;color:#6B7280;transition:.15s"
            onmouseover="this.style.borderColor='#93C5FD'" onmouseout="this.style.borderColor='#E5E7EB'">
      ❓ Guía
    </button>
  </div>
  <button onclick="resetIngForm(); rstModal('modalIng')" class="btn btn-primary btn-sm" style="white-space:nowrap">
    + Ingrediente
  </button>
</div>

<!-- Cards grid -->
<?php if (!empty($ingredientes)): ?>
<div class="inv-grid">
<?php foreach ($ingredientes as $ing):
  $stock = (float)$ing['stock'];
  $min   = (float)$ing['stock_minimo'];
  $bajo  = $stock <= $min;
  $pct   = $min > 0 ? min(100, round($stock / ($min * 2) * 100)) : ($stock > 0 ? 100 : 0);
  $fillCls = $bajo ? 'low' : ($pct < 60 ? 'warn' : '');
?>
<div class="inv-card <?= $bajo ? 'bajo' : 'ok' ?>"
     data-search="<?= strtolower(htmlspecialchars($ing['nombre'] . ' ' . ($ing['categoria'] ?? ''), ENT_QUOTES)) ?>">
  <div class="inv-card-head">
    <div class="inv-card-name">
      <?= htmlspecialchars($ing['nombre']) ?>
      <?php if ($ing['categoria']): ?>
      <small><?= htmlspecialchars($ing['categoria']) ?></small>
      <?php endif; ?>
    </div>
    <?php if ($bajo): ?>
    <svg width="16" height="16" fill="none" stroke="#EF4444" viewBox="0 0 24 24" title="Stock bajo" style="flex-shrink:0;margin-top:2px">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <?php endif; ?>
  </div>

  <div class="inv-stock-bar-wrap">
    <div class="inv-stock-label">
      <span>Stock actual</span>
      <strong style="color:<?= $bajo ? '#EF4444' : '#111827' ?>"><?= number_format($stock, 2) ?> <?= htmlspecialchars($ing['unidad_principal']) ?></strong>
    </div>
    <div class="inv-bar">
      <div class="inv-bar-fill <?= $fillCls ?>" style="width:<?= $pct ?>%"></div>
    </div>
    <?php if ($min > 0): ?>
    <div style="font-size:.7rem;color:#9CA3AF;margin-top:3px">Mínimo: <?= number_format($min, 2) ?> <?= htmlspecialchars($ing['unidad_principal']) ?></div>
    <?php endif; ?>
  </div>

  <div class="inv-card-meta">
    <?php if ($ing['proveedor_carnihub']): ?>
    <span class="badge badge-purple" style="font-size:.7rem">CarniHub</span>
    <?php elseif ($ing['proveedor_nombre']): ?>
    <span class="badge badge-gray" style="font-size:.68rem"><?= htmlspecialchars($ing['proveedor_nombre']) ?></span>
    <?php endif; ?>
    <span class="badge <?= $bajo ? 'badge-red' : 'badge-green' ?>" style="font-size:.7rem">
      <?= $bajo ? 'Stock bajo' : 'OK' ?>
    </span>
  </div>

  <div class="inv-card-cost">
    Costo/u: <strong>$<?= number_format((float)$ing['costo_unitario'], 2) ?></strong>
    <?php if ((float)$ing['costo_unitario'] > 0 && $stock > 0): ?>
    &nbsp;·&nbsp; Valor: <strong>$<?= number_format($stock * (float)$ing['costo_unitario'], 2) ?></strong>
    <?php endif; ?>
  </div>

  <div class="inv-card-actions">
    <button onclick='abrirModificar(<?= htmlspecialchars(json_encode($ing), ENT_QUOTES) ?>)'
            class="btn btn-primary" style="flex:1;justify-content:center;font-size:.82rem;padding:8px 12px">
      Modificar
    </button>
  </div>
</div>
<?php endforeach; ?>
</div>

<?php else: ?>
<div id="noMatch" style="display:none;text-align:center;padding:32px;color:#9CA3AF;font-size:.9rem">
  Sin resultados para tu búsqueda.
</div>
<div class="empty-state" style="margin-bottom:32px">
  <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
  <div style="font-size:.95rem;font-weight:600;color:#374151;margin-bottom:4px">Sin ingredientes</div>
  <div style="font-size:.85rem">Agrega ingredientes de CarniHub o de proveedores externos</div>
</div>
<?php endif; ?>

<!-- Movimientos recientes -->
<?php if (!empty($movRecientes)): ?>
<div style="background:#fff;border-radius:14px;border:1.5px solid #E5E7EB;padding:20px;margin-bottom:24px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <div style="font-size:.95rem;font-weight:700;color:#111827">Movimientos recientes</div>
    <a href="<?= BASE_URL ?>rest-inventario/movimientos" style="font-size:.78rem;color:var(--cp);font-weight:600;text-decoration:none">
      Ver todos →
    </a>
  </div>
  <!-- Header -->
  <div class="mov-row mov-header" style="padding-bottom:6px;border-bottom:2px solid #E5E7EB">
    <div>Fecha</div>
    <div>Ingrediente / Motivo</div>
    <div style="text-align:center">Tipo</div>
    <div style="text-align:right">Cantidad</div>
    <div style="text-align:right">Stock final</div>
  </div>
  <?php
  $tipoCls = ['entrada'=>'badge-green','salida'=>'badge-red','merma'=>'badge-amber','ajuste'=>'badge-blue'];
  $tipoIcon = [
    'entrada' => '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>',
    'salida'  => '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>',
    'merma'   => '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/></svg>',
    'ajuste'  => '<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0115 0M20 15a9 9 0 01-15 0"/></svg>',
  ];
  foreach ($movRecientes as $m):
    $cls  = $tipoCls[$m['tipo']] ?? 'badge-gray';
    $icon = $tipoIcon[$m['tipo']] ?? '';
    $fecha = date('d/m H:i', strtotime($m['created_at']));
    $delta = in_array($m['tipo'], ['entrada','ajuste']) && (float)$m['cantidad'] >= 0 ? '+' : '-';
    $delta = $m['tipo'] === 'ajuste' ? '±' : $delta;
  ?>
  <div class="mov-row">
    <div class="mov-fecha"><?= $fecha ?></div>
    <div>
      <div class="mov-ing" title="<?= htmlspecialchars($m['ingrediente_nombre'] ?? '') ?>"><?= htmlspecialchars($m['ingrediente_nombre'] ?? '—') ?></div>
      <?php if ($m['motivo']): ?>
      <div style="font-size:.72rem;color:#9CA3AF;margin-top:1px"><?= htmlspecialchars($m['motivo']) ?></div>
      <?php endif; ?>
    </div>
    <div style="text-align:center">
      <span class="badge <?= $cls ?>" style="font-size:.68rem;display:inline-flex;align-items:center;gap:3px">
        <?= $icon ?><?= ucfirst($m['tipo']) ?>
      </span>
    </div>
    <div style="text-align:right;font-weight:600;font-size:.82rem;color:<?= in_array($m['tipo'],['entrada']) ? '#16A34A' : '#EF4444' ?>">
      <?= $delta ?><?= number_format(abs((float)$m['cantidad']), 2) ?>
      <span style="font-size:.7rem;font-weight:400;color:#9CA3AF"><?= htmlspecialchars($m['unidad_principal'] ?? '') ?></span>
    </div>
    <div style="text-align:right;font-size:.82rem;color:#374151">
      <?= number_format((float)$m['stock_despues'], 2) ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$ingCategorias = array_values(array_unique(array_filter(array_column($ingredientes ?? [], 'categoria'))));
sort($ingCategorias);
?>
<datalist id="dlCatIng">
  <?php foreach ($ingCategorias as $c): ?>
  <option value="<?= htmlspecialchars($c) ?>">
  <?php endforeach; ?>
</datalist>

<!-- Modal nuevo/editar ingrediente -->
<div id="modalIng" class="rst-modal-backdrop">
  <div class="rst-modal" style="max-width:520px">
    <div class="rst-modal-header">
      <div>
        <div class="rst-modal-title" id="modalIngTitle">Nuevo Ingrediente</div>
        <div style="font-size:.78rem;color:#9CA3AF;margin-top:2px" id="modalIngSub">Proveedor externo</div>
      </div>
      <button class="rst-modal-close" onclick="rstModal('modalIng')">✕</button>
    </div>

    <!-- Tabs fuente -->
    <div class="rst-tabs" id="ingTabs">
      <button class="rst-tab active" data-tab="ext" onclick="switchTab('ext')">Proveedor externo</button>
      <button class="rst-tab" data-tab="ch"  onclick="switchTab('ch')">
        <span style="color:var(--cp);font-weight:700">⚡ Desde CarniHub</span>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>rest-inventario/guardar" id="formIng">
      <input type="hidden" name="id" id="ingId" value="">
      <input type="hidden" name="proveedor_carnihub" id="ingEsCarniHub" value="0">
      <input type="hidden" name="carnihub_producto_id" id="ingCarniHubId" value="">

      <!-- Panel externo -->
      <div class="rst-tab-panel active" id="panelExt">

        <!-- Nombre + categoría -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group" style="grid-column:span 2">
            <label class="form-label">Nombre del ingrediente *</label>
            <input type="text" name="nombre" id="ingNombre" class="form-input"
                   placeholder="Ej: Jitomate, Carne de res, Aceite" required>
          </div>
          <div class="form-group">
            <label class="form-label">Categoría
              <span style="color:#9CA3AF;font-weight:400;font-size:.72rem">— elige o escribe nueva</span>
            </label>
            <input type="text" name="categoria" id="ingCategoria" class="form-input"
                   list="dlCatIng"
                   placeholder="Ej: Lácteos, Carnes, Verduras">
          </div>
          <div class="form-group">
            <label class="form-label">Unidad de medida</label>
            <select name="unidad_principal" id="ingUnidad" class="form-select" onchange="calcCostos()">
              <option value="kg">kg — kilogramo</option>
              <option value="g">g — gramo</option>
              <option value="L">L — litro</option>
              <option value="ml">ml — mililitro</option>
              <option value="pza">pza — pieza</option>
              <option value="caja">caja</option>
              <option value="bolsa">bolsa</option>
            </select>
          </div>
        </div>

        <!-- Costo con calculadora -->
        <div style="background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:12px;padding:14px;margin-bottom:14px">
          <div style="font-weight:600;font-size:.85rem;color:#374151;margin-bottom:10px;display:flex;align-items:center;gap:6px">
            <svg width="14" height="14" fill="none" stroke="#6B7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Costo
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">Costo por <span id="unidadLabel">kg</span></label>
              <div style="display:flex;align-items:center;gap:6px">
                <span style="color:#6B7280;font-weight:600">$</span>
                <input type="number" name="costo_unitario" id="ingCosto" class="form-input"
                       value="0" min="0" step="0.0001" placeholder="0.00"
                       oninput="calcCostos()" style="flex:1">
              </div>
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">Stock mínimo (alerta)</label>
              <div style="display:flex;align-items:center;gap:6px">
                <input type="number" name="stock_minimo" id="ingMinimo" class="form-input"
                       value="0" min="0" step="0.001" placeholder="0.000">
                <span id="unidadMinLabel" style="color:#9CA3AF;font-size:.8rem;white-space:nowrap">kg</span>
              </div>
            </div>
          </div>
          <!-- Calculadora de equivalencias -->
          <div id="calcCostosWrap" style="display:none;margin-top:10px;padding:8px 10px;background:#EFF6FF;border-radius:8px;border:1px solid #BFDBFE">
            <div style="font-size:.72rem;font-weight:700;color:#1E40AF;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em">Equivalencias de costo</div>
            <div id="calcCostos" style="font-size:.8rem;color:#1E3A5F;display:flex;gap:16px;flex-wrap:wrap"></div>
          </div>
        </div>

        <!-- Proveedor -->
        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label">Proveedor <span style="color:#9CA3AF;font-weight:400">(libre)</span></label>
          <input type="text" name="proveedor_nombre" id="ingProveedor" class="form-input"
                 placeholder="Ej: Mercado, Walmart, Don José">
        </div>

      </div>

      <!-- Panel CarniHub -->
      <div class="rst-tab-panel" id="panelCh">
        <div style="background:#FAF5FF;border:1.5px solid #DDD6FE;border-radius:10px;padding:14px;margin-bottom:14px;font-size:.85rem">
          <div style="font-weight:700;color:#5B21B6;margin-bottom:4px">⚡ Sincronizado con CarniHub</div>
          <div style="color:#6D28D9;line-height:1.4">Los productos que compraste a tu distribuidor aparecen aquí. El stock se actualiza automáticamente con cada pedido recibido.</div>
        </div>
        <?php if (!empty($productosCarnihub)): ?>
        <div style="border:1.5px solid #E5E7EB;border-radius:10px;overflow:hidden;margin-bottom:12px">
          <div style="padding:8px 12px;background:#F9FAFB;font-size:.72rem;font-weight:700;color:#9CA3AF;
                      text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #E5E7EB">
            Selecciona un producto
          </div>
          <div style="max-height:180px;overflow-y:auto">
          <?php foreach ($productosCarnihub as $pc): ?>
          <div style="padding:10px 14px;border-bottom:1px solid #F3F4F6;display:flex;justify-content:space-between;align-items:center;cursor:pointer;transition:.1s"
               onmouseover="this.style.background='#FAF5FF'" onmouseout="this.style.background=''"
               onclick="seleccionarCarniHub(<?= $pc['id'] ?>, '<?= htmlspecialchars($pc['nombre'], ENT_QUOTES) ?>')">
            <div>
              <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($pc['nombre']) ?></div>
              <div style="font-size:.75rem;color:#9CA3AF"><?= htmlspecialchars($pc['unidad'] ?? '') ?></div>
            </div>
            <span class="badge badge-purple">CarniHub</span>
          </div>
          <?php endforeach; ?>
          </div>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding:24px">
          <div style="font-size:.85rem">No tienes pedidos de CarniHub aún. Cuando compres productos a tu distribuidor, aparecerán aquí.</div>
        </div>
        <?php endif; ?>
        <div class="form-group" id="chNombreWrap" style="display:none">
          <label class="form-label">Producto seleccionado</label>
          <input type="text" id="ingNombreCh" class="form-input" readonly
                 style="background:#FAF5FF;color:#5B21B6;font-weight:600" placeholder="Selecciona un producto de arriba">
        </div>
        <div class="form-group">
          <label class="form-label">Stock mínimo (alerta)</label>
          <input type="number" name="stock_minimo_ch" id="ingMinimoCh" class="form-input"
                 value="0" min="0" step="0.001">
        </div>
      </div>

      <div class="rst-modal-footer">
        <button type="button" onclick="rstModal('modalIng')" class="btn btn-outline">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar ingrediente</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Modificar ingrediente (movimiento + editar) -->
<div id="modalModificar" class="rst-modal-backdrop">
  <div class="rst-modal" style="max-width:530px">
    <div class="rst-modal-header">
      <div style="flex:1;min-width:0">
        <div class="rst-modal-title" id="modifNombre">Ingrediente</div>
        <div style="font-size:.78rem;color:#6B7280;margin-top:2px">
          Stock: <strong id="modifStockActual">0</strong> <span id="modifUnidadPrincipal">kg</span>
          &nbsp;·&nbsp; $<span id="modifCostoU">0.00</span>/<span id="modifUnidadCosto">kg</span>
        </div>
      </div>
      <button class="rst-modal-close" onclick="rstModal('modalModificar')">✕</button>
    </div>

    <div class="rst-tabs" id="modifTabs">
      <button class="rst-tab active" onclick="switchModifTab('mov')">Movimiento de stock</button>
      <button class="rst-tab" onclick="switchModifTab('edit')">Editar datos</button>
    </div>

    <!-- Tab: Movimiento -->
    <div class="rst-tab-panel active" id="panelModifMov">
      <form method="POST" action="<?= BASE_URL ?>rest-inventario/movimiento" id="formModifMov"
            onsubmit="return prepararMovimiento()">
        <input type="hidden" name="ingrediente_id" id="modifIngId">
        <input type="hidden" name="cantidad" id="modifCantFinal">

        <div class="form-group">
          <label class="form-label">Tipo de movimiento</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px">
            <?php
            $tiposM = [
              ['val'=>'entrada','label'=>'Entrada', 'cls'=>'badge-green', 'desc'=>'Suma al stock'],
              ['val'=>'salida', 'label'=>'Salida',  'cls'=>'badge-red',   'desc'=>'Resta del stock'],
              ['val'=>'merma',  'label'=>'Merma',   'cls'=>'badge-amber', 'desc'=>'Pérdida/daño'],
              ['val'=>'ajuste', 'label'=>'Ajuste',  'cls'=>'badge-blue',  'desc'=>'Corrección manual'],
            ];
            foreach ($tiposM as $t):
            ?>
            <label style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:2px solid #E5E7EB;
                          border-radius:8px;cursor:pointer;transition:.15s" class="mtipo-lbl">
              <input type="radio" name="tipo" value="<?= $t['val'] ?>" style="display:none" class="mtipo-radio"
                     onchange="calcModifConversion()">
              <span class="badge <?= $t['cls'] ?>"><?= $t['label'] ?></span>
              <span style="font-size:.78rem;color:#6B7280"><?= $t['desc'] ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Cantidad</label>
          <div style="display:flex;gap:8px;align-items:center">
            <input type="number" id="modifCantInput" class="form-input" style="flex:2"
                   step="0.001" min="0.001" placeholder="0.000"
                   oninput="calcModifConversion()">
            <select id="modifCantUnidad" class="form-select" style="flex:0 0 80px"
                    onchange="calcModifConversion()">
            </select>
          </div>
          <div id="modifConvPrev" style="display:none;margin-top:7px;padding:8px 12px;
               border-radius:8px;font-size:.8rem;line-height:1.6"></div>
        </div>

        <div class="form-group">
          <label class="form-label">Motivo <span style="color:#9CA3AF;font-weight:400">(opcional)</span></label>
          <input type="text" name="motivo" class="form-input"
                 placeholder="Ej: Compra del día, Desperdicio, Inventario físico">
        </div>

        <div class="rst-modal-footer">
          <button type="button" onclick="rstModal('modalModificar')" class="btn btn-outline">Cancelar</button>
          <button type="submit" class="btn btn-primary">Registrar movimiento</button>
        </div>
      </form>
    </div>

    <!-- Tab: Editar datos -->
    <div class="rst-tab-panel" id="panelModifEdit">
      <form method="POST" action="<?= BASE_URL ?>rest-inventario/guardar" id="formModifEdit">
        <input type="hidden" name="id" id="modifEditId">
        <input type="hidden" name="proveedor_carnihub" id="modifEditCarnihub" value="0">
        <input type="hidden" name="carnihub_producto_id" id="modifEditCarnihubId" value="">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="form-group" style="grid-column:span 2">
            <label class="form-label">Nombre *</label>
            <input type="text" name="nombre" id="modifEditNombre" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Categoría</label>
            <input type="text" name="categoria" id="modifEditCategoria" class="form-input" list="dlCatIng">
          </div>
          <div class="form-group">
            <label class="form-label">Unidad de medida</label>
            <select name="unidad_principal" id="modifEditUnidad" class="form-select"
                    onchange="document.getElementById('modifEditUnidadLabel').textContent=this.value;
                              document.getElementById('modifEditUnidadWarn').style.display='block'">
              <option value="kg">kg — kilogramo</option>
              <option value="g">g — gramo</option>
              <option value="L">L — litro</option>
              <option value="ml">ml — mililitro</option>
              <option value="pza">pza — pieza</option>
              <option value="caja">caja</option>
              <option value="bolsa">bolsa</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Costo/<span id="modifEditUnidadLabel">kg</span></label>
            <div style="display:flex;align-items:center;gap:6px">
              <span style="color:#6B7280;font-weight:600">$</span>
              <input type="number" name="costo_unitario" id="modifEditCosto" class="form-input"
                     min="0" step="0.0001" placeholder="0.00">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Stock mínimo (alerta)</label>
            <input type="number" name="stock_minimo" id="modifEditMinimo" class="form-input"
                   min="0" step="0.001" placeholder="0.000">
          </div>
          <div class="form-group" id="modifEditProvWrap">
            <label class="form-label">Proveedor</label>
            <input type="text" name="proveedor_nombre" id="modifEditProveedor" class="form-input"
                   placeholder="Ej: Mercado, Walmart">
          </div>
        </div>

        <div id="modifEditUnidadWarn" style="display:none;background:#FEF3C7;border:1px solid #FDE68A;
             border-radius:8px;padding:8px 12px;font-size:.75rem;color:#92400E;margin-bottom:12px">
          ⚠️ Cambiar la unidad no convierte el stock existente.
        </div>

        <div class="rst-modal-footer">
          <button type="button" onclick="rstModal('modalModificar')" class="btn btn-outline">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function rstModal(id) {
  document.getElementById(id).classList.toggle('open');
}
document.querySelectorAll('.rst-modal-backdrop').forEach(bd => {
  bd.addEventListener('click', e => { if (e.target === bd) bd.classList.remove('open'); });
});

function toggleGuia() {
  const g = document.getElementById('guia-inv');
  g.style.display = g.style.display === 'none' ? 'block' : 'none';
}

function filtrarIngredientes() {
  const q = document.getElementById('invBuscar').value.toLowerCase().trim();
  document.querySelectorAll('.inv-card[data-search]').forEach(card => {
    card.style.display = !q || card.dataset.search.includes(q) ? '' : 'none';
  });
  const vis = [...document.querySelectorAll('.inv-card[data-search]')].filter(c => c.style.display !== 'none').length;
  const nm = document.getElementById('noMatch');
  if (nm) nm.style.display = (vis === 0 && q) ? 'block' : 'none';
}

let tabActual = 'ext';
function switchTab(tab) {
  tabActual = tab;
  document.querySelectorAll('.rst-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
  document.querySelectorAll('.rst-tab-panel').forEach(p => p.classList.toggle('active', p.id === 'panel' + tab.charAt(0).toUpperCase() + tab.slice(1)));
  document.getElementById('ingEsCarniHub').value = tab === 'ch' ? '1' : '0';
  document.getElementById('ingNombre').required = tab !== 'ch';
}

function seleccionarCarniHub(id, nombre) {
  document.getElementById('ingCarniHubId').value = id;
  document.getElementById('ingNombreCh').value   = nombre;
  document.getElementById('chNombreWrap').style.display = 'block';
  document.querySelectorAll('#panelCh [onclick]').forEach(r => r.style.background = '');
  event.currentTarget.style.background = 'var(--cp-light)';
}

function resetIngForm() {
  document.getElementById('ingId').value = '';
  document.getElementById('ingNombre').value = '';
  document.getElementById('ingCategoria').value = '';
  document.getElementById('ingCosto').value = '0';
  document.getElementById('ingMinimo').value = '0';
  document.getElementById('ingProveedor').value = '';
  document.getElementById('modalIngTitle').textContent = 'Nuevo Ingrediente';
  document.getElementById('modalIngSub').textContent = 'Proveedor externo';
  switchTab('ext');
  calcCostos();
}

function calcCostos() {
  const costo  = parseFloat(document.getElementById('ingCosto').value) || 0;
  const unidad = document.getElementById('ingUnidad').value;
  document.getElementById('unidadLabel').textContent = unidad;
  document.getElementById('unidadMinLabel').textContent = unidad;
  let items = [];
  if (costo > 0) {
    if (unidad === 'kg') {
      items = [`Por 100g: <strong>$${(costo/10).toFixed(2)}</strong>`,`Por g: <strong>$${(costo/1000).toFixed(4)}</strong>`];
    } else if (unidad === 'g') {
      items = [`Por kg: <strong>$${(costo*1000).toFixed(2)}</strong>`,`Por 100g: <strong>$${(costo*100).toFixed(3)}</strong>`];
    } else if (unidad === 'L') {
      items = [`Por 100ml: <strong>$${(costo/10).toFixed(2)}</strong>`,`Por ml: <strong>$${(costo/1000).toFixed(4)}</strong>`];
    } else if (unidad === 'ml') {
      items = [`Por L: <strong>$${(costo*1000).toFixed(2)}</strong>`,`Por 100ml: <strong>$${(costo*100).toFixed(3)}</strong>`];
    }
  }
  document.getElementById('calcCostos').innerHTML = items.map(i => `<span>${i}</span>`).join('');
  document.getElementById('calcCostosWrap').style.display = items.length ? 'block' : 'none';
}

// ── Modal Modificar ──────────────────────────────────────────
let modifIng = null;

function abrirModificar(ing) {
  modifIng = ing;
  document.getElementById('modifNombre').textContent = ing.nombre;
  document.getElementById('modifStockActual').textContent = parseFloat(ing.stock||0).toFixed(3);
  document.getElementById('modifUnidadPrincipal').textContent = ing.unidad_principal;
  document.getElementById('modifCostoU').textContent = parseFloat(ing.costo_unitario||0).toFixed(2);
  document.getElementById('modifUnidadCosto').textContent = ing.unidad_principal;

  // Tab movimiento
  document.getElementById('modifIngId').value = ing.id;
  document.getElementById('modifCantInput').value = '';
  document.getElementById('modifCantFinal').value = '';
  document.getElementById('modifConvPrev').style.display = 'none';
  setupModifUnidades(ing.unidad_principal);
  const firstMtipo = document.querySelector('.mtipo-lbl');
  if (firstMtipo) firstMtipo.click();

  // Tab editar
  document.getElementById('modifEditId').value = ing.id;
  document.getElementById('modifEditCarnihub').value = ing.proveedor_carnihub ? '1' : '0';
  document.getElementById('modifEditCarnihubId').value = ing.carnihub_producto_id || '';
  document.getElementById('modifEditNombre').value = ing.nombre;
  document.getElementById('modifEditCategoria').value = ing.categoria || '';
  const uSel = document.getElementById('modifEditUnidad');
  let found = false;
  for (let o of uSel.options) {
    if (o.value === ing.unidad_principal) { o.selected = true; found = true; break; }
  }
  if (!found) { uSel.add(new Option(ing.unidad_principal, ing.unidad_principal, true, true)); }
  document.getElementById('modifEditUnidadLabel').textContent = ing.unidad_principal;
  document.getElementById('modifEditCosto').value = ing.costo_unitario || 0;
  document.getElementById('modifEditMinimo').value = ing.stock_minimo || 0;
  document.getElementById('modifEditProveedor').value = ing.proveedor_nombre || '';
  document.getElementById('modifEditProvWrap').style.display = ing.proveedor_carnihub ? 'none' : '';
  document.getElementById('modifEditUnidadWarn').style.display = 'none';

  switchModifTab('mov');
  document.getElementById('modalModificar').classList.add('open');
}

function switchModifTab(tab) {
  document.querySelectorAll('#modifTabs .rst-tab').forEach((t, i) => {
    t.classList.toggle('active', (tab==='mov' && i===0) || (tab==='edit' && i===1));
  });
  document.getElementById('panelModifMov').classList.toggle('active', tab === 'mov');
  document.getElementById('panelModifEdit').classList.toggle('active', tab === 'edit');
}

document.querySelectorAll('.mtipo-lbl').forEach(lbl => {
  const radio = lbl.querySelector('.mtipo-radio');
  lbl.addEventListener('click', () => {
    document.querySelectorAll('.mtipo-lbl').forEach(l => l.style.borderColor = '#E5E7EB');
    lbl.style.borderColor = 'var(--cp)';
    radio.checked = true;
    calcModifConversion();
  });
});

function setupModifUnidades(mainUnit) {
  const sel = document.getElementById('modifCantUnidad');
  sel.innerHTML = '';
  const grupos = {
    'kg':['g','kg'],'g':['g','kg','mg'],'mg':['mg','g','kg'],
    'L':['ml','L'],'l':['ml','L'],'ml':['ml','L'],'mL':['ml','L'],
    'pza':['pza'],'caja':['caja'],'bolsa':['bolsa'],
  };
  const units = grupos[mainUnit] || [mainUnit];
  units.forEach(u => {
    const opt = new Option(u, u);
    if (u === mainUnit) opt.selected = true;
    sel.appendChild(opt);
  });
}

function convUnidad(q, desde, hasta) {
  const d = desde.toLowerCase();
  const h = hasta.toLowerCase();
  if (d === h) return q;
  const map = {
    'g_kg':1e-3,'kg_g':1e3,'mg_g':1e-3,'g_mg':1e3,'mg_kg':1e-6,'kg_mg':1e6,
    'ml_l':1e-3,'l_ml':1e3,
  };
  return q * (map[d+'_'+h] || 1);
}

function calcModifConversion() {
  if (!modifIng) return;
  const cant = parseFloat(document.getElementById('modifCantInput').value) || 0;
  const fromU = document.getElementById('modifCantUnidad').value;
  const mainU = modifIng.unidad_principal;
  const converted = convUnidad(cant, fromU, mainU);
  document.getElementById('modifCantFinal').value = converted.toFixed(6);

  const tipo = document.querySelector('.mtipo-radio:checked')?.value || 'entrada';
  const resta = ['salida','merma'].includes(tipo);
  const stockActual = parseFloat(modifIng.stock) || 0;
  const stockNuevo = resta ? Math.max(0, stockActual - converted) : stockActual + converted;

  const prev = document.getElementById('modifConvPrev');
  if (cant > 0) {
    let html = '';
    if (fromU.toLowerCase() !== mainU.toLowerCase()) {
      html += `<strong>${cant} ${fromU}</strong> = <strong>${converted.toFixed(4)} ${mainU}</strong><br>`;
    }
    const color = resta ? '#991B1B' : '#166534';
    html += `Stock: ${stockActual.toFixed(3)} → <strong style="color:${color}">${stockNuevo.toFixed(3)} ${mainU}</strong>`;
    prev.innerHTML = html;
    prev.style.background = resta ? '#FEF2F2' : '#F0FDF4';
    prev.style.border = `1px solid ${resta ? '#FECACA' : '#BBF7D0'}`;
    prev.style.color = resta ? '#991B1B' : '#166534';
    prev.style.display = 'block';
  } else {
    prev.style.display = 'none';
  }
}

function prepararMovimiento() {
  const cant = parseFloat(document.getElementById('modifCantInput').value) || 0;
  if (cant <= 0) { alert('Ingresa una cantidad mayor a 0'); return false; }
  if (!document.querySelector('.mtipo-radio:checked')) { alert('Selecciona el tipo de movimiento'); return false; }
  if (!document.getElementById('modifCantFinal').value) {
    const fromU = document.getElementById('modifCantUnidad').value;
    document.getElementById('modifCantFinal').value = convUnidad(cant, fromU, modifIng.unidad_principal).toFixed(6);
  }
  return true;
}
</script>

<?php
$content = ob_get_clean();
$activeMenu = 'rest_inventario';
$pageTitle  = 'Inventario';
require ROOT_PATH . '/app/views/restaurante/layouts/main.php';
