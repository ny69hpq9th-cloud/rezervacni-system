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

requestAnimationFrame(function(){document.documentElement.classList.add('theme-ready');});

// Auto-hide alerts
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity .4s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 400);
  }, 6000);
});
</script>

<footer style="text-align:center;padding:16px 24px;font-size:.78rem;color:#94a3b8;border-top:1px solid #e5e7eb;margin-top:auto">
  © <?= date('Y') ?> Rezervly &bull; Oliver Hlavnička &bull; IČO: 29521939 &nbsp;|&nbsp;
  <a href="/privacy-policy.php" style="color:#94a3b8;text-decoration:none" target="_blank">Ochrana osobních údajů</a> &bull;
  <a href="/terms.php"          style="color:#94a3b8;text-decoration:none" target="_blank">Obchodní podmínky</a> &bull;
  <a href="/cookies.php"        style="color:#94a3b8;text-decoration:none" target="_blank">Cookies</a>
</footer>
</body>
</html>
