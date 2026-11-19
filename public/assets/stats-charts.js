/**
 * Vite & Gourmand — graphiques des statistiques MongoDB.
 * Chart.js est chargé uniquement dans le back-office administrateur.
 * Les données agrégées sont incluses par PHP dans #vg-stats-chart-data.
 */
(() => {
  'use strict';

  function initStatisticsCharts() {
    const dataNode = document.getElementById('vg-stats-chart-data');
    if (!dataNode) return; // Aucune statistique ou pas dans l'espace administrateur.

    const errorNode = document.getElementById('stats-chart-error');
    const showError = () => {
      if (errorNode) errorNode.hidden = false;
    };

    if (typeof Chart === 'undefined') {
      showError(); // La CDN est inaccessible : le tableau HTML reste disponible.
      return;
    }

    let data;
    try {
      data = JSON.parse(dataNode.textContent);
    } catch (error) {
      showError();
      return;
    }

    const labels = Array.isArray(data.labels) ? data.labels : [];
    const counts = Array.isArray(data.counts) ? data.counts.map(Number) : [];
    const realized = Array.isArray(data.realized) ? data.realized.map(Number) : [];
    const pending = Array.isArray(data.pending) ? data.pending.map(Number) : [];
    if (!labels.length || [counts, realized, pending].some(series => series.length !== labels.length)) {
      showError();
      return;
    }

    const countCanvas = document.getElementById('ordersByMenuChart');
    const amountCanvas = document.getElementById('revenueByMenuChart');
    if (!countCanvas || !amountCanvas) return;

    // Augmente la hauteur si des menus supplémentaires sont ajoutés.
    const chartHeight = `${Math.min(750, Math.max(270, labels.length * 55 + 135))}px`;
    countCanvas.parentElement.style.height = chartHeight;
    amountCanvas.parentElement.style.height = chartHeight;

    const green = '#244c42';
    const gold = '#cb9153';
    const ink = '#28251f';
    const euroFormat = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });

    const labelTicks = {
      color: ink,
      autoSkip: false,
      font: { size: 12 },
      callback(value) {
        const label = String(this.getLabelForValue(value));
        return label.length > 25 ? `${label.slice(0, 23)}…` : label;
      }
    };

    try {
      new Chart(countCanvas, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Commandes',
            data: counts,
            backgroundColor: green,
            borderRadius: 5,
            maxBarThickness: 40
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          animation: { duration: 350 },
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: item => `${item.parsed.x} commande(s)` } }
          },
          scales: {
            x: { beginAtZero: true, ticks: { color: ink, precision: 0, stepSize: 1 } },
            y: { ticks: labelTicks, grid: { display: false } }
          }
        }
      });

      new Chart(amountCanvas, {
        type: 'bar',
        data: {
          labels,
          datasets: [
            { label: 'CA réalisé', data: realized, backgroundColor: green, borderRadius: 4, maxBarThickness: 40 },
            { label: 'Commandes en cours', data: pending, backgroundColor: gold, borderRadius: 4, maxBarThickness: 40 }
          ]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          animation: { duration: 350 },
          plugins: {
            legend: { position: 'bottom', labels: { color: ink, usePointStyle: true } },
            tooltip: { callbacks: { label: item => `${item.dataset.label} : ${euroFormat.format(item.parsed.x)}` } }
          },
          scales: {
            x: {
              stacked: true,
              beginAtZero: true,
              ticks: { color: ink, callback: value => `${value} €` }
            },
            y: { stacked: true, ticks: labelTicks, grid: { display: false } }
          }
        }
      });
    } catch (error) {
      showError();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStatisticsCharts);
  } else {
    initStatisticsCharts();
  }
})();
