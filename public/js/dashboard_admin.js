// dashboard_admin.js — Admin dashboard charts (Chart.js)

document.addEventListener('DOMContentLoaded', () => {
  // Sales by day (bar chart)
  const ctxBar = document.getElementById('chartVentas');
  if (ctxBar && window.ventasDiasLabels) {
    new Chart(ctxBar, {
      type: 'bar',
      data: {
        labels: window.ventasDiasLabels,
        datasets: [{
          label: 'Ventas $',
          data: window.ventasDiasDatos,
          backgroundColor: '#C8102E',
          borderRadius: 6,
          hoverBackgroundColor: '#A00D24',
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ' $' + ctx.parsed.y.toLocaleString('es-MX')
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: '#F3F4F6' },
            ticks: { callback: v => '$' + (v / 1000).toFixed(0) + 'k' }
          },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // Sales by category (doughnut)
  const ctxDnt = document.getElementById('chartCategorias');
  if (ctxDnt && window.categoriasLabels) {
    new Chart(ctxDnt, {
      type: 'doughnut',
      data: {
        labels: window.categoriasLabels,
        datasets: [{
          data: window.categoriasDatos,
          backgroundColor: ['#C8102E','#3B82F6','#10B981','#F59E0B','#8B5CF6'],
          borderWidth: 0,
          hoverOffset: 6,
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom', labels: { padding: 14, font: { size: 12 } } },
          tooltip: {
            callbacks: {
              label: ctx => ' $' + ctx.parsed.toLocaleString('es-MX')
            }
          }
        },
        cutout: '65%'
      }
    });
  }
});
