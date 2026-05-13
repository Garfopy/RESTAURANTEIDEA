<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($restaurante['nombre']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
      --cp: <?= htmlspecialchars($restaurante['color_primario'] ?? '#C8102E') ?>;
      --cs: <?= htmlspecialchars($restaurante['color_secundario'] ?? '#1f2937') ?>;
    }
    body { font-family: system-ui, sans-serif; background: #F9FAFB; }
    .hero { background: var(--cs); color: #fff; padding: 28px 20px 20px; }
    .cat-btn { padding: 6px 16px; border-radius: 99px; font-size: .82rem; font-weight: 600; border: 2px solid var(--cp); cursor: pointer; transition: .15s; }
    .cat-btn.active, .cat-btn:hover { background: var(--cp); color: #fff; }
    .platillo-card { background: #fff; border-radius: 12px; border: 1px solid #E5E7EB; overflow: hidden; display: flex; flex-direction: column; }
    .add-btn { background: var(--cp); color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: .875rem; font-weight: 600; cursor: pointer; }
    .carrito-bar { position: fixed; bottom: 0; left: 0; right: 0; background: var(--cs); color: #fff; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; z-index: 99; transform: translateY(100%); transition: .3s; }
    .carrito-bar.visible { transform: translateY(0); }
    footer { padding: 24px; text-align: center; font-size: .75rem; color: #9CA3AF; padding-bottom: 80px; }
  </style>
</head>
<body>
<div class="hero">
  <?php if ($restaurante['logo']): ?>
  <img src="<?= BASE_URL . htmlspecialchars($restaurante['logo']) ?>" alt="" style="height:48px;object-fit:contain;margin-bottom:10px">
  <?php endif; ?>
  <h1 style="font-size:1.4rem;font-weight:700;margin:0"><?= htmlspecialchars($restaurante['nombre']) ?></h1>
  <?php if ($restaurante['descripcion']): ?>
  <p style="font-size:.85rem;opacity:.8;margin:6px 0 0"><?= htmlspecialchars($restaurante['descripcion']) ?></p>
  <?php endif; ?>
  <?php if ($mesa): ?>
  <div style="margin-top:10px;background:rgba(255,255,255,.15);border-radius:8px;padding:6px 12px;font-size:.85rem;display:inline-block">
    🪑 Mesa: <strong><?= htmlspecialchars($mesa['nombre']) ?></strong>
  </div>
  <?php endif; ?>
</div>

<!-- Filtro categorías -->
<div style="padding:16px 16px 8px;display:flex;gap:8px;overflow-x:auto;scrollbar-width:none">
  <button class="cat-btn active" data-cat="">Todos</button>
  <?php foreach ($categorias as $cat): ?>
  <button class="cat-btn" data-cat="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></button>
  <?php endforeach; ?>
</div>

<!-- Platillos -->
<form id="formPedido" method="POST" action="<?= BASE_URL ?>menu/<?= htmlspecialchars($restaurante['slug']) ?>/ordenar">
  <input type="hidden" name="mesa_qr" value="<?= htmlspecialchars($mesa['qr_codigo'] ?? '') ?>">
  <input type="hidden" name="visita_id" value="">

  <div id="grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;padding:12px 16px">
    <?php foreach ($platillos as $p): ?>
    <div class="platillo-card" data-cat="<?= (int)$p['categoria_id'] ?>">
      <?php if ($p['imagen']): ?>
      <img src="<?= BASE_URL . htmlspecialchars($p['imagen']) ?>" alt="" style="height:120px;object-fit:cover;width:100%">
      <?php else: ?>
      <div style="height:80px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;font-size:2rem">🍽</div>
      <?php endif; ?>
      <div style="padding:10px">
        <div style="font-weight:600;font-size:.9rem;margin-bottom:2px"><?= htmlspecialchars($p['nombre']) ?></div>
        <?php if ($p['descripcion']): ?>
        <div style="font-size:.75rem;color:#6B7280;margin-bottom:6px;line-height:1.3"><?= htmlspecialchars(mb_substr($p['descripcion'], 0, 60)) ?>...</div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span style="font-weight:700;color:var(--cp)">$<?= number_format((float)$p['precio'],2) ?></span>
          <div style="display:flex;align-items:center;gap:6px">
            <input type="hidden" name="platillo_id[]" value="<?= $p['id'] ?>">
            <button type="button" onclick="cambiarCant(this,-1)"
              style="width:26px;height:26px;border-radius:50%;border:1px solid #D1D5DB;background:#fff;font-weight:700;cursor:pointer;font-size:1rem;line-height:1">−</button>
            <span class="cant" style="font-weight:600;min-width:16px;text-align:center">0</span>
            <input type="hidden" name="cantidad[]" value="0" class="cant-input">
            <button type="button" onclick="cambiarCant(this,1)"
              style="width:26px;height:26px;border-radius:50%;border:none;background:var(--cp);color:#fff;font-weight:700;cursor:pointer;font-size:1rem;line-height:1">+</button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</form>

<!-- Carrito flotante -->
<div class="carrito-bar" id="carritoBar">
  <div>
    <div style="font-size:.8rem;opacity:.8" id="carritoItems">0 items</div>
    <div style="font-weight:700;font-size:1rem" id="carritoTotal">$0.00</div>
  </div>
  <button onclick="document.getElementById('formPedido').submit()"
    style="padding:10px 24px;background:#fff;color:var(--cs);border:none;border-radius:10px;font-weight:700;font-size:.9rem;cursor:pointer">
    Ordenar →
  </button>
</div>

<footer>Potenciado por <strong>CarniHub</strong></footer>

<script>
const precios = {
  <?php foreach ($platillos as $p): ?>
  '<?= $p['id'] ?>': <?= (float)$p['precio'] ?>,
  <?php endforeach; ?>
};

function cambiarCant(btn, delta) {
  const card  = btn.closest('.platillo-card');
  const span  = card.querySelector('.cant');
  const input = card.querySelector('.cant-input');
  const hidden = card.querySelector('input[name="platillo_id[]"]');
  const curr  = parseInt(span.textContent) + delta;
  const val   = Math.max(0, curr);
  span.textContent = val;
  input.value = val;
  actualizarCarrito();
}

function actualizarCarrito() {
  const cards = document.querySelectorAll('.platillo-card');
  let total = 0, items = 0;
  cards.forEach(c => {
    const id   = c.querySelector('input[name="platillo_id[]"]').value;
    const cant = parseInt(c.querySelector('.cant').textContent);
    if (cant > 0) { total += precios[id] * cant; items += cant; }
  });
  document.getElementById('carritoTotal').textContent = '$' + total.toFixed(2);
  document.getElementById('carritoItems').textContent = items + ' item' + (items !== 1 ? 's' : '');
  document.getElementById('carritoBar').classList.toggle('visible', items > 0);
}

// Filtro categorías
document.querySelectorAll('.cat-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const cat = btn.dataset.cat;
    document.querySelectorAll('.platillo-card').forEach(c => {
      c.style.display = (!cat || c.dataset.cat == cat) ? '' : 'none';
    });
  });
});
</script>
</body>
</html>
