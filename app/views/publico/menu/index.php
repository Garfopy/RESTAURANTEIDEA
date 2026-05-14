<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($restaurante['nombre']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>public/css/restaurant.css">
  <style>
    :root {
      --cp: <?= htmlspecialchars($restaurante['color_primario'] ?? '#C8102E') ?>;
      --cs: <?= htmlspecialchars($restaurante['color_secundario'] ?? '#1f2937') ?>;
    }
  </style>
</head>
<body>

<!-- Hero -->
<div class="pub-hero">
  <?php if ($restaurante['logo']): ?>
  <img src="<?= BASE_URL . htmlspecialchars($restaurante['logo']) ?>" alt=""
       style="height:48px;object-fit:contain;margin-bottom:10px;display:block">
  <?php endif; ?>
  <h1 style="font-size:1.4rem;font-weight:800;margin:0 0 4px">
    <?= htmlspecialchars($restaurante['nombre']) ?>
  </h1>
  <?php if ($restaurante['descripcion']): ?>
  <p style="font-size:.85rem;opacity:.75;margin:0;line-height:1.4">
    <?= htmlspecialchars($restaurante['descripcion']) ?>
  </p>
  <?php endif; ?>

  <?php if ($mesa): ?>
  <div style="display:inline-flex;align-items:center;gap:6px;margin-top:10px;
              background:rgba(255,255,255,.15);border-radius:8px;padding:6px 12px;font-size:.85rem">
    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
    Mesa: <strong><?= htmlspecialchars($mesa['nombre']) ?></strong>
  </div>

  <!-- Botón llamar mesero -->
  <div style="margin-top:10px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
    <button id="btnLlamarMesero"
            onclick="llamarMesero()"
            style="padding:7px 16px;background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.5);
                   color:#fff;border-radius:20px;font-size:.82rem;font-weight:600;cursor:pointer;transition:.15s"
            onmouseover="this.style.background='rgba(255,255,255,.3)'"
            onmouseout="this.style.background='rgba(255,255,255,.2)'">
      🔔 Llamar mesero
    </button>
    <?php if ($visitaId): ?>
    <a href="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug']) ?>/pagar/<?= $visitaId ?>"
       style="padding:7px 16px;background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.5);
              color:#fff;border-radius:20px;font-size:.82rem;font-weight:600;text-decoration:none;transition:.15s">
      🧾 Ver mi cuenta
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Status tracker (visible cuando hay visita activa) -->
  <?php if ($visitaId): ?>
  <div id="statusTracker" style="margin-top:12px;background:rgba(255,255,255,.12);border-radius:10px;
                                  padding:10px 14px;font-size:.82rem;display:none">
    <div id="statusContent">Verificando estado del pedido…</div>
  </div>
  <?php endif; ?>
</div>

<!-- Categorías sticky -->
<div class="pub-cat-bar">
  <button class="pub-cat-btn active" data-cat="">Todos</button>
  <?php foreach ($categorias as $cat): ?>
  <button class="pub-cat-btn" data-cat="<?= $cat['id'] ?>">
    <?= htmlspecialchars($cat['nombre']) ?>
  </button>
  <?php endforeach; ?>
</div>

<!-- Platillos -->
<form id="formPedido" method="POST"
      action="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug']) ?>/ordenar">
  <input type="hidden" name="mesa_qr"  value="<?= htmlspecialchars($mesa['qr_codigo'] ?? '') ?>">
  <input type="hidden" name="visita_id" id="inpVisitaId" value="<?= (int)($visitaId ?? 0) ?>">

  <div class="pub-grid" id="grid">
    <?php if (empty($platillos)): ?>
    <div style="grid-column:1/-1;padding:60px 20px;text-align:center;color:#6B7280">
      <div style="font-size:3.5rem;margin-bottom:8px">🍽️</div>
      <div style="font-size:1.05rem;font-weight:700;color:#374151;margin-bottom:6px">
        Menú en preparación
      </div>
      <div style="font-size:.88rem;max-width:340px;margin:0 auto;line-height:1.6">
        Aún no hay platillos disponibles.<br>
        Vuelve pronto o pide ayuda al personal.
      </div>
    </div>
    <?php else: ?>
    <?php
    $alergenoBadge = ['Gluten'=>'#FEF3C7:#92400E','Lactosa'=>'#DBEAFE:#1E40AF','Mariscos'=>'#CCFBF1:#065F46','Frutos secos'=>'#FEE2E2:#991B1B','Huevo'=>'#FEF9C3:#713F12','Soya'=>'#F3E8FF:#6B21A8','Cacahuate'=>'#FFEDD5:#9A3412','Mostaza'=>'#D1FAE5:#064E3B'];
    ?>
    <?php foreach ($platillos as $p): ?>
    <?php $pId = $p['id']; $ings = $recetaIngredientes[$pId] ?? []; $ingsStock = array_values(array_filter($ings, fn($i) => !$i['es_informativo'])); ?>
    <div class="pub-card" data-cat="<?= (int)$p['categoria_id'] ?>">
      <?php if ($p['imagen']): ?>
      <img src="<?= BASE_URL . htmlspecialchars($p['imagen']) ?>" alt=""
           style="height:120px;object-fit:cover;width:100%">
      <?php else: ?>
      <div style="height:80px;background:#F3F4F6;display:flex;align-items:center;
                  justify-content:center;font-size:2rem">🍽</div>
      <?php endif; ?>

      <div class="pub-card-body">
        <div class="pub-card-name"><?= htmlspecialchars($p['nombre']) ?></div>
        <?php if ($p['descripcion']): ?>
        <div class="pub-card-desc">
          <?= htmlspecialchars(mb_substr($p['descripcion'], 0, 65)) ?>
          <?= mb_strlen($p['descripcion']) > 65 ? '…' : '' ?>
        </div>
        <?php endif; ?>

        <?php if ($p['contiene']): ?>
        <div style="font-size:.7rem;color:#6B7280;margin-top:4px">
          <em>Contiene:</em> <?= htmlspecialchars($p['contiene']) ?>
        </div>
        <?php endif; ?>

        <?php if ($p['alergenos']): ?>
        <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:5px">
          <?php foreach (array_filter(array_map('trim', explode(',', $p['alergenos']))) as $al): ?>
          <?php $parts = explode(':', $alergenoBadge[$al] ?? '#F3F4F6:#374151'); ?>
          <span style="font-size:.62rem;font-weight:700;padding:2px 6px;border-radius:5px;
                       background:<?= $parts[0] ?>;color:<?= $parts[1] ?>">
            ⚠️ <?= htmlspecialchars($al) ?>
          </span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:8px">
          <div>
            <span class="pub-card-price">$<?= number_format((float)$p['precio'], 2) ?></span>
            <?php if (!empty($ingsStock)): ?>
            <button type="button"
                    onclick="abrirDetalle(<?= $pId ?>)"
                    style="display:block;font-size:.68rem;color:var(--cp);background:none;border:none;
                           cursor:pointer;padding:0;margin-top:2px;text-decoration:underline">
              Personalizar →
            </button>
            <?php endif; ?>
          </div>
          <div class="pub-counter">
            <input type="hidden" name="platillo_id[]" value="<?= $pId ?>">
            <button type="button" class="pub-counter-btn minus" onclick="cambiarCant(this,-1)">−</button>
            <span class="cant pub-counter-val">0</span>
            <input type="hidden" name="cantidad[]" value="0" class="cant-input">
            <button type="button" class="pub-counter-btn plus" onclick="cambiarCant(this,1)">+</button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Exclusiones como hidden inputs (populated by JS modal) -->
  <div id="excl-hidden-container"></div>
</form>

<!-- Carrito flotante -->
<div class="pub-cart-bar" id="carritoBar">
  <div>
    <div style="font-size:.78rem;opacity:.75" id="carritoItems">0 items</div>
    <div style="font-weight:800;font-size:1.05rem" id="carritoTotal">$0.00</div>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <?php if ($visitaId): ?>
    <button id="btnPedirMismo" onclick="pedirLoMismo()"
            style="padding:8px 14px;background:rgba(255,255,255,.2);color:#fff;border:1.5px solid rgba(255,255,255,.5);
                   border-radius:10px;font-size:.78rem;font-weight:600;cursor:pointer;display:none">
      🔁 Lo mismo
    </button>
    <?php endif; ?>
    <button onclick="document.getElementById('formPedido').submit()"
            style="padding:10px 24px;background:#fff;color:var(--cs);border:none;
                   border-radius:10px;font-weight:700;font-size:.9rem;cursor:pointer;
                   transition:.15s" onmouseover="this.style.filter='brightness(.9)'"
                   onmouseout="this.style.filter=''">
      Ordenar →
    </button>
  </div>
</div>

<footer style="padding:24px;text-align:center;font-size:.75rem;color:#9CA3AF;padding-bottom:90px">
  Potenciado por <strong>CarniHub</strong>
</footer>

<!-- Modal de detalle/personalización -->
<div id="detalleModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:flex-end;justify-content:center">
  <div style="background:#fff;border-radius:20px 20px 0 0;padding:24px;width:100%;max-width:480px;
              max-height:80vh;overflow-y:auto;animation:slideUp .25s ease">
    <div style="font-weight:700;font-size:1rem;margin-bottom:4px" id="detalleNombre"></div>
    <div style="font-size:.82rem;color:#6B7280;margin-bottom:16px" id="detalleDesc"></div>
    <div id="detalleAlergenos" style="margin-bottom:10px"></div>
    <div id="detalleContiene" style="margin-bottom:14px;font-size:.8rem;color:#6B7280"></div>

    <div id="detalleIngsSection" style="display:none">
      <div style="font-weight:600;font-size:.88rem;color:#374151;margin-bottom:8px">
        ¿Qué no quieres que incluya?
      </div>
      <div id="detalleIngsList"></div>
    </div>

    <div style="margin-top:14px">
      <div style="font-weight:600;font-size:.88rem;color:#374151;margin-bottom:6px">
        Comentario al chef <span style="font-weight:400;color:#9CA3AF">(opcional)</span>
      </div>
      <textarea id="detalleNota" rows="2"
                style="width:100%;padding:10px 12px;border:1.5px solid #E5E7EB;border-radius:10px;
                       font-size:.875rem;resize:none;box-sizing:border-box;outline:none;font-family:inherit"
                placeholder="Ej: bien cocido, sin picante, extra salsa…"
                onfocus="this.style.borderColor='var(--cp)'" onblur="this.style.borderColor='#E5E7EB'"></textarea>
    </div>

    <div style="display:flex;gap:10px;margin-top:20px">
      <button type="button" onclick="cerrarDetalle()"
              style="flex:1;padding:12px;border:2px solid #E5E7EB;border-radius:12px;
                     background:#fff;font-size:.9rem;cursor:pointer;font-weight:600;color:#374151">
        Cancelar
      </button>
      <button type="button" onclick="confirmarDetalle()"
              style="flex:1;padding:12px;background:var(--cp);color:#fff;border:none;
                     border-radius:12px;font-size:.9rem;cursor:pointer;font-weight:700">
        Listo ✓
      </button>
    </div>
  </div>
</div>
<style>
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
</style>

<script>
const precios = {
  <?php foreach ($platillos as $p): ?>'<?= $p['id'] ?>': <?= (float)$p['precio'] ?>,<?php endforeach; ?>
};

// Datos de platillos (allergens, contiene, ingredientes no-informativos)
const platillosData = <?= json_encode(array_combine(
  array_column($platillos, 'id'),
  array_map(fn($p) => [
    'nombre'    => $p['nombre'],
    'descripcion' => $p['descripcion'] ?? '',
    'alergenos' => $p['alergenos'] ?? '',
    'contiene'  => $p['contiene'] ?? '',
    'ings'      => array_values(array_filter($recetaIngredientes[$p['id']] ?? [], fn($i) => !$i['es_informativo']))
  ], $platillos)
)) ?>;

const alergenoColors = <?= json_encode(array_map(fn($v) => array_combine(['bg','fg'], explode(':', $v)), [
  'Gluten'=>'#FEF3C7:#92400E','Lactosa'=>'#DBEAFE:#1E40AF','Mariscos'=>'#CCFBF1:#065F46',
  'Frutos secos'=>'#FEE2E2:#991B1B','Huevo'=>'#FEF9C3:#713F12','Soya'=>'#F3E8FF:#6B21A8',
  'Cacahuate'=>'#FFEDD5:#9A3412','Mostaza'=>'#D1FAE5:#064E3B'
])) ?>;

let detalleActualId = null;

function abrirDetalle(id) {
  const d = platillosData[id];
  if (!d) return;
  detalleActualId = id;
  document.getElementById('detalleNombre').textContent = d.nombre;
  document.getElementById('detalleDesc').textContent   = d.descripcion;

  // Alérgenos
  const alerDiv = document.getElementById('detalleAlergenos');
  if (d.alergenos) {
    alerDiv.innerHTML = d.alergenos.split(',').map(a => {
      a = a.trim(); const c = alergenoColors[a] || {bg:'#F3F4F6',fg:'#374151'};
      return `<span style="font-size:.72rem;font-weight:700;padding:3px 8px;border-radius:6px;margin-right:4px;background:${c.bg};color:${c.fg}">⚠️ ${a}</span>`;
    }).join('');
  } else { alerDiv.innerHTML = ''; }

  // Contiene
  const conDiv = document.getElementById('detalleContiene');
  conDiv.textContent = d.contiene ? 'También contiene: ' + d.contiene : '';

  // Ingredientes (para exclusiones)
  const ingsSec = document.getElementById('detalleIngsSection');
  const ingsList = document.getElementById('detalleIngsList');
  if (d.ings && d.ings.length) {
    ingsSec.style.display = 'block';
    // Restore previously selected exclusions
    const prevExcl = Array.from(document.querySelectorAll(`input[name="exclusiones[${id}][]"]`)).map(i => i.value);
    ingsList.innerHTML = d.ings.map(i => `
      <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;
                    border:1.5px solid #E5E7EB;margin-bottom:6px;cursor:pointer;font-size:.88rem">
        <input type="checkbox" value="${i.ingrediente_nombre}"
               ${prevExcl.includes(i.ingrediente_nombre)?'checked':''}
               style="width:18px;height:18px;cursor:pointer">
        <span style="flex:1">${i.ingrediente_nombre}</span>
        <span style="font-size:.75rem;color:#9CA3AF">${i.cantidad} ${i.unidad}</span>
      </label>`).join('');
  } else {
    ingsSec.style.display = 'none';
  }

  const modal = document.getElementById('detalleModal');
  modal.style.display = 'flex';
}

function cerrarDetalle() {
  document.getElementById('detalleModal').style.display = 'none';
  document.getElementById('detalleNota').value = '';
  detalleActualId = null;
}

function confirmarDetalle() {
  if (!detalleActualId) { cerrarDetalle(); return; }
  const container = document.getElementById('excl-hidden-container');
  container.querySelectorAll(`[data-pid="${detalleActualId}"]`).forEach(e => e.remove());
  document.querySelectorAll('#detalleIngsList input[type=checkbox]:checked').forEach(chk => {
    const inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = `exclusiones[${detalleActualId}][]`;
    inp.value = chk.value;
    inp.dataset.pid = detalleActualId;
    container.appendChild(inp);
  });
  // Guardar nota del chef como hidden input
  const nota = document.getElementById('detalleNota').value.trim();
  if (nota) {
    const ni = document.createElement('input');
    ni.type = 'hidden';
    ni.name = `notas_item[${detalleActualId}]`;
    ni.value = nota;
    ni.dataset.pid = detalleActualId;
    container.appendChild(ni);
  }
  cerrarDetalle();
}

document.getElementById('detalleModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) cerrarDetalle();
});

// Recuperar visita de cookie si existe
const cookieVisita = document.cookie.split('; ').find(r => r.startsWith('visita_<?= $restaurante['id'] ?>='));
if (cookieVisita) {
  const vid = cookieVisita.split('=')[1];
  const inp = document.getElementById('inpVisitaId');
  if (!inp.value || inp.value === '0') inp.value = vid;
}

function cambiarCant(btn, delta) {
  const card  = btn.closest('.pub-card');
  const span  = card.querySelector('.cant');
  const input = card.querySelector('.cant-input');
  const val   = Math.max(0, parseInt(span.textContent) + delta);
  span.textContent = val;
  input.value      = val;
  actualizarCarrito();
}

function actualizarCarrito() {
  let total = 0, items = 0;
  document.querySelectorAll('.pub-card').forEach(c => {
    const id   = c.querySelector('input[name="platillo_id[]"]').value;
    const cant = parseInt(c.querySelector('.cant').textContent);
    if (cant > 0) { total += precios[id] * cant; items += cant; }
  });
  document.getElementById('carritoTotal').textContent = '$' + total.toFixed(2);
  document.getElementById('carritoItems').textContent = items + ' item' + (items !== 1 ? 's' : '');
  document.getElementById('carritoBar').classList.toggle('visible', items > 0);
}

// Filtro categorías
document.querySelectorAll('.pub-cat-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.pub-cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const cat = btn.dataset.cat;
    document.querySelectorAll('.pub-card').forEach(c => {
      c.style.display = (!cat || c.dataset.cat == cat) ? '' : 'none';
    });
  });
});

// ── Llamar mesero ─────────────────────────────────────────────────────────────
<?php if ($mesa): ?>
const SLUG      = '<?= htmlspecialchars($restaurante['slug']) ?>';
const MESA_QR   = '<?= htmlspecialchars($mesa['qr_codigo'] ?? '') ?>';
const VISITA_ID = <?= (int)($visitaId ?? 0) ?>;

function llamarMesero() {
  const btn = document.getElementById('btnLlamarMesero');
  btn.disabled = true;
  btn.textContent = '🔔 Avisando…';
  fetch(`<?= BASE_URL ?>menu/${SLUG}/llamarMesero`, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `mesa_qr=${encodeURIComponent(MESA_QR)}&visita_id=${VISITA_ID}`
  })
  .then(r => r.json())
  .then(d => {
    btn.textContent = d.ok ? '✅ Mesero avisado' : '❌ Error';
    setTimeout(() => { btn.textContent = '🔔 Llamar mesero'; btn.disabled = false; }, 4000);
  })
  .catch(() => { btn.textContent = '🔔 Llamar mesero'; btn.disabled = false; });
}
<?php endif; ?>

// ── Polling estado del pedido ─────────────────────────────────────────────────
<?php if ($visitaId): ?>
const ESTADO_LABELS = {
  pendiente:       '⏳ Esperando que la cocina tome tu pedido',
  en_preparacion:  '👨‍🍳 Tu pedido está en preparación',
  listo:           '✅ ¡Tu pedido está listo! El mesero lo llevará pronto',
  entregado:       '🍽️ Pedido entregado. ¡Buen provecho!',
};
const ESTADO_COLORS = {
  pendiente:'#F59E0B', en_preparacion:'#3B82F6', listo:'#10B981', entregado:'#6B7280'
};
let ultimosEstados = {};

function actualizarEstadoPedido() {
  fetch(`<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug']) ?>/estadoPedido/<?= (int)$visitaId ?>`)
    .then(r => r.json())
    .then(d => {
      if (!d.ok || !d.pedidos.length) return;

      // Detectar si hubo cambios de estado
      let cambio = false;
      d.pedidos.forEach(p => {
        if (ultimosEstados[p.id] !== p.estado) { cambio = true; ultimosEstados[p.id] = p.estado; }
      });

      // Consolidar estado global (el peor estado = el más atrasado)
      const prioridad = ['pendiente','en_preparacion','listo','entregado'];
      let estadoGlobal = 'entregado';
      d.pedidos.forEach(p => {
        if (p.estado !== 'cancelado') {
          const pi = prioridad.indexOf(p.estado);
          const gi = prioridad.indexOf(estadoGlobal);
          if (pi < gi) estadoGlobal = p.estado;
        }
      });

      const tracker  = document.getElementById('statusTracker');
      const content  = document.getElementById('statusContent');
      const label    = ESTADO_LABELS[estadoGlobal] ?? estadoGlobal;
      const color    = ESTADO_COLORS[estadoGlobal] ?? '#374151';
      let html = `<span style="color:${color};font-weight:600">${label}</span>`;
      if (d.tiempo_min > 0 && estadoGlobal === 'en_preparacion') {
        html += ` <span style="color:rgba(255,255,255,.7);margin-left:6px">⏱️ ~${d.tiempo_min} min</span>`;
      }
      content.innerHTML = html;
      tracker.style.display = 'block';

      // Mostrar botón "Pedir lo mismo" cuando hay pedidos entregados
      const btnMismo = document.getElementById('btnPedirMismo');
      if (btnMismo && d.pedidos.some(p => p.estado === 'entregado')) {
        btnMismo._ultimosPedidos = d.pedidos;
        btnMismo.style.display = 'block';
      }
    })
    .catch(() => {});
}

// Iniciar polling cada 5 s
actualizarEstadoPedido();
setInterval(actualizarEstadoPedido, 5000);

// ── Pedir lo mismo ────────────────────────────────────────────────────────────
function pedirLoMismo() {
  const btn = document.getElementById('btnPedirMismo');
  const pedidos = btn?._ultimosPedidos ?? [];
  // Tomar los ítems del último pedido entregado y pre-llenar el carrito
  const ultimo = pedidos.filter(p => p.estado === 'entregado').pop();
  if (!ultimo) return;
  ultimo.items.forEach(it => {
    const card = [...document.querySelectorAll('.pub-card')]
      .find(c => c.querySelector('input[name="platillo_id[]"]')?.value == it.platillo_id);
    if (!card) return;
    const span  = card.querySelector('.cant');
    const input = card.querySelector('.cant-input');
    if (span && input) {
      span.textContent = it.cantidad;
      input.value      = it.cantidad;
    }
  });
  actualizarCarrito();
  window.scrollTo({top: 0, behavior: 'smooth'});
}
<?php endif; ?>
</script>
</body>
</html>
