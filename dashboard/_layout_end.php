  </div><!-- .dash-content -->
</main><!-- .dash-main -->

<div class="dash-wrapper"></div><!-- flex context -->

<script>
// Sidebar toggle (mobile)
const toggle  = document.getElementById('sidebar-toggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebar-overlay');

function openSidebar() {
  sidebar?.classList.add('open');
  overlay?.classList.add('open');
}
function closeSidebar() {
  sidebar?.classList.remove('open');
  overlay?.classList.remove('open');
}
toggle?.addEventListener('click', () => {
  sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
});
overlay?.addEventListener('click', closeSidebar);

// Auto-hide alerts
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity .4s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 400);
  }, 6000);
});
</script>
</body>
</html>
