// dashboard_cliente.js — Client dashboard charts (Chart.js)

document.addEventListener('DOMContentLoaded', () => {
  // Purchases last 6 months
  const ctxCompras = document.getElementById('chartCompras');
  if (ctxCompras && window.comprasMeses) {
    new Chart(ctxCompras, {
      type: 'bar',
      data: {
        labels: window.comprasMeses.labels,
        datasets: [{
          label: 'Compras $',
          data: window.comprasMeses.datos,
          backgroundColor: '#C8102E',
          borderRadius: 6,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { callback: v => '$' + (v / 1000).toFixed(0) + 'k' }
          },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // Spending by category (doughnut)
  const ctxCat = document.getElementById('chartCategorias');
  if (ctxCat && window.categoriasDatos) {
    new Chart(ctxCat, {
      type: 'doughnut',
      data: {
        labels: window.categoriasDatos.labels,
        datasets: [{
          data: window.categoriasDatos.datos,
          backgroundColor: ['#C8102E','#3B82F6','#10B981','#F59E0B'],
          borderWidth: 0,
          hoverOffset: 4,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        cutout: '60%'
      }
    });
  }
});
