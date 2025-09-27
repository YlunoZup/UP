// Sidebar toggle (shared for all admin pages)
const menuToggle = document.getElementById("menu-toggle");
const sidebar = document.querySelector("aside.sidebar-nav-wrapper");
const overlay = document.querySelector(".overlay");

// helper: decide when overlay should be visible
function shouldShowOverlay() {
  if (!sidebar || !overlay) return false;
  // show overlay only if sidebar is open AND viewport < 992px
  return sidebar.classList.contains("active") && window.innerWidth < 992;
}

function updateOverlayState() {
  if (!overlay || !sidebar) return;
  if (shouldShowOverlay()) {
    overlay.classList.add("active");
  } else {
    overlay.classList.remove("active");
  }
}

// --- Attach listeners ---
if (sidebar && overlay) {
  // Ensure sidebar starts closed on page load/refresh
  sidebar.classList.remove("active");

  // Initialize immediately (covers refresh)
  updateOverlayState();

  // Re-check when DOM is fully loaded
  document.addEventListener("DOMContentLoaded", () => {
    sidebar.classList.remove("active");
    updateOverlayState();
  });

  // Keep in sync on window resize
  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(updateOverlayState, 150);
  });

  // Clicking overlay always closes sidebar
  overlay.addEventListener("click", () => {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
  });
}

if (menuToggle && sidebar && overlay) {
  menuToggle.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    updateOverlayState(); // keep overlay in sync on toggle
  });
}