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
        Estamos preparando el menú
      </div>
      <div style="font-size:.88rem;max-width:340px;margin:0 auto;line-height:1.5">
        Aún no hay platillos disponibles. Vuelve en un momento o pide ayuda al personal.
      </div>
    </div>
    <?php else: ?>
    <?php foreach ($platillos as $p): ?>
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
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto">
          <span class="pub-card-price">$<?= number_format((float)$p['precio'], 2) ?></span>
          <div class="pub-counter">
            <input type="hidden" name="platillo_id[]" value="<?= $p['id'] ?>">
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
</form>

<!-- Carrito flotante -->
<div class="pub-cart-bar" id="carritoBar">
  <div>
    <div style="font-size:.78rem;opacity:.75" id="carritoItems">0 items</div>
    <div style="font-weight:800;font-size:1.05rem" id="carritoTotal">$0.00</div>
  </div>
  <button onclick="document.getElementById('formPedido').submit()"
          style="padding:10px 24px;background:#fff;color:var(--cs);border:none;
                 border-radius:10px;font-weight:700;font-size:.9rem;cursor:pointer;
                 transition:.15s" onmouseover="this.style.filter='brightness(.9)'"
                 onmouseout="this.style.filter=''">
    Ordenar →
  </button>
</div>

<footer style="padding:24px;text-align:center;font-size:.75rem;color:#9CA3AF;padding-bottom:90px">
  Potenciado por <strong>CarniHub</strong>
</footer>

<script>
const precios = {
  <?php foreach ($platillos as $p): ?>'<?= $p['id'] ?>': <?= (float)$p['precio'] ?>,<?php endforeach; ?>
};

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
</script>
</body>
</html>
