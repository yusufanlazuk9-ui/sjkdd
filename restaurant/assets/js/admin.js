// Admin panel utilities
function toggleSidebar() {
  document.getElementById('admin-sidebar').classList.toggle('open');
}

// Close sidebar on outside click (mobile)
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('admin-sidebar');
  const toggle = document.querySelector('.admin-mobile-toggle');
  if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggle) {
    sidebar.classList.remove('open');
  }
});
