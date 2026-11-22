import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const loansChart = document.querySelector('#loans-chart');

if (loansChart) {
    import('chart.js/auto').then(({ default: Chart }) => {
        new Chart(loansChart, {
            type: 'line',
            data: {
                labels: JSON.parse(loansChart.dataset.labels),
                datasets: [{
                    label: 'Peminjaman',
                    data: JSON.parse(loansChart.dataset.values),
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.12)',
                    fill: true,
                    tension: 0.3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });
    });
}
