const menuToggle = document.getElementById("menu-toggle");
const sidebar = document.querySelector("aside.sidebar-nav-wrapper");
const overlay = document.querySelector(".overlay");

// Load initial state from localStorage
const isSidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
if (isSidebarOpen) {
  sidebar.classList.add("active");
  overlay.classList.add("active");
}

menuToggle.addEventListener("click", () => {
  const willBeOpen = !sidebar.classList.contains("active");
  sidebar.classList.toggle("active");
  overlay.classList.toggle("active");
  localStorage.setItem('sidebarOpen', willBeOpen);
});

overlay.addEventListener("click", () => {
  sidebar.classList.remove("active");
  overlay.classList.remove("active");
  localStorage.setItem('sidebarOpen', 'false');
});

// Initialize Bootstrap tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl);
});