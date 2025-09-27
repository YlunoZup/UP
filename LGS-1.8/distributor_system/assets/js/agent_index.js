const menuToggle = document.getElementById("menu-toggle");
const sidebar = document.querySelector("aside.sidebar-nav-wrapper");
const overlay = document.querySelector(".overlay");

// Load initial state from localStorage
const isSidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
if (isSidebarOpen) {
  sidebar.classList.add("active");
  overlay.classList.add("active");
}

// Debounced resize helper to avoid repeated/responsive resize glitches
let chartResizeTimer;
function scheduleResizeCharts(delay = 300) {
  clearTimeout(chartResizeTimer);
  chartResizeTimer = setTimeout(() => {
    if (typeof pieChart !== 'undefined' && pieChart) pieChart.resize();
    if (typeof performanceChart !== 'undefined' && performanceChart) performanceChart.resize();
  }, delay);
}

menuToggle.addEventListener("click", () => {
  const willBeOpen = !sidebar.classList.contains("active");
  sidebar.classList.toggle("active");
  overlay.classList.toggle("active");
  localStorage.setItem('sidebarOpen', willBeOpen);
  // Schedule resize after layout change/transition finishes
  scheduleResizeCharts();
});

overlay.addEventListener("click", () => {
  sidebar.classList.remove("active");
  overlay.classList.remove("active");
  localStorage.setItem('sidebarOpen', 'false');
  // Schedule resize after layout change/transition finishes
  scheduleResizeCharts();
});

// Pie Chart
const pieCtx = document.getElementById('leadsPieChart').getContext('2d');
const pieChart = new Chart(pieCtx, {
  type: 'pie',
  data: {
    labels: Object.keys(window.dashboardData.statusCounts),
    datasets: [{
      data: Object.values(window.dashboardData.statusCounts),
      backgroundColor: [
        '#6c757d', // N/A - gray
        '#1d4ed8', // Reviewed - blue
        '#c2410c', // Reviewed - Redesign - orange
        '#0284c7', // Contacted - In Progress - light blue
        '#b45309', // Pending - In Progress - yellow/orange
        '#15803d', // Completed - Paid - green
        '#b91c1c'  // Bad - red
      ]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          font: {
            size: 14
          },
          padding: 20
        }
      }
    }
  }
});

// Performance Chart
const performanceCtx = document.getElementById('performanceChart').getContext('2d');
const performanceChart = new Chart(performanceCtx, {
  type: 'line',
  data: {
    labels: window.dashboardData.performanceLabels,
    datasets: [{
      label: 'Completed - Paid (Conversions)',
      data: window.dashboardData.performanceData,
      borderColor: '#007bff',
      backgroundColor: 'rgba(0,123,255,0.1)',
      fill: true,
      tension: 0.3
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        display: false
      }
    },
    scales: {
      x: {
        ticks: {
          font: {
            size: 14
          }
        }
      },
      y: {
        beginAtZero: true,
        ticks: {
          font: {
            size: 14
          }
        }
      }
    }
  }
});

// Handle window resize for additional safety (debounced)
window.addEventListener('resize', () => {
  scheduleResizeCharts(150);
});