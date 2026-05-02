<?php
include ROOT_PATH . '/app/views/components/header.php';
include ROOT_PATH . '/app/views/components/sidebar_cliente.php';
?>
<h1 style="font-size:1.1rem;font-weight:700;margin-bottom:20px">Mis reportes</h1>

<!-- Resumen -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
  <div class="card" style="text-align:center;padding:16px">
    <div style="font-size:.75rem;color:#6B7280">Este mes</div>
    <div style="font-size:1.4rem;font-weight:800;color:#C8102E">$<?= number_format($stats['mes_actual'] ?? 0,0,'.', ',') ?></div>
    <div style="font-size:.7rem;color:#9CA3AF">en pedidos</div>
  </div>
  <div class="card" style="text-align:center;padding:16px">
    <div style="font-size:.75rem;color:#6B7280">Total pedidos</div>
    <div style="font-size:1.4rem;font-weight:800;color:#111827"><?= $stats['total_pedidos'] ?? 0 ?></div>
    <div style="font-size:.7rem;color:#9CA3AF">todos los tiempos</div>
  </div>
</div>

<!-- Gráfica compras últimos 6 meses -->
<div class="card" style="margin-bottom:16px">
  <div style="font-weight:700;font-size:.875rem;margin-bottom:14px">Compras últimos 6 meses</div>
  <canvas id="chartCompras" height="200"></canvas>
</div>

<!-- Top productos -->
<div class="card" style="margin-bottom:16px">
  <div style="font-weight:700;font-size:.875rem;margin-bottom:12px">Productos más pedidos</div>
  <?php foreach ($topProductos as $tp): ?>
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
    <div style="flex:1;font-size:.875rem"><?= htmlspecialchars($tp['nombre']) ?></div>
    <div style="font-size:.75rem;color:#6B7280"><?= number_format($tp['total_kg'],0) ?> kg</div>
    <div style="width:80px;height:6px;background:#F3F4F6;border-radius:3px;overflow:hidden">
      <div style="height:100%;background:#C8102E;border-radius:3px;width:<?= min(100, ($tp['total_kg']/max(1,$topProductos[0]['total_kg']))*100) ?>%"></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Bottom nav mobile -->
<nav class="bottom-nav hide-desktop">
  <a href="<?= BASE_URL ?>carrito/inicio" class="bottom-nav-item">🏠 <span>Inicio</span></a>
  <a href="<?= BASE_URL ?>producto/catalogo" class="bottom-nav-item">📦 <span>Catálogo</span></a>
  <a href="<?= BASE_URL ?>pedido/index" class="bottom-nav-item">📋 <span>Pedidos</span></a>
  <a href="<?= BASE_URL ?>carrito/index" class="bottom-nav-item">🛒 <span>Carrito</span></a>
  <a href="<?= BASE_URL ?>auth/logout" class="bottom-nav-item">👤 <span>Cuenta</span></a>
</nav>

<?php
$labels = array_column($ventasMes, 'mes');
$datos = array_column($ventasMes, 'total');
$extraScripts = <<<JS
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartCompras'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: 'Compras $',
      data: <?= json_encode($datos) ?>,
      backgroundColor: '#C8102E',
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString('es-MX') } } }
  }
});
</script>
JS;
?>
<?php include ROOT_PATH . '/app/views/components/footer.php'; ?>
