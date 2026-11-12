// Chart.js charts for view_reports.php. The data arrays (catLabels, catTotals, monthLabels,
// incomeData, expenseData) are printed into the page by PHP just before this file loads.

var categoryCanvas = document.getElementById('categoryChart');
if (categoryCanvas && catLabels.length) {
  new Chart(categoryCanvas, {
    type: 'doughnut',
    data: {
      labels: catLabels,
      datasets: [{
        data: catTotals,
        backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#0dcaf0', '#6c757d']
      }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
  });
}

new Chart(document.getElementById('trendChart'), {
  type: 'bar',
  data: {
    labels: monthLabels,
    datasets: [
      { label: 'Income',   data: incomeData,  backgroundColor: '#198754' },
      { label: 'Expenses', data: expenseData, backgroundColor: '#dc3545' }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: { y: { beginAtZero: true } }
  }
});
