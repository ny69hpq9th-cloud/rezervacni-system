<?php /* Cookie consent banner — vložit před </body> na všech veřejných stránkách */ ?>
<div id="cookie-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;background:#1e293b;color:#f8fafc;padding:16px 24px;z-index:9999;box-shadow:0 -4px 16px rgba(0,0,0,.25)">
  <div style="max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
    <p style="margin:0;font-size:.875rem;flex:1;min-width:220px;line-height:1.5">
      Tento web používá cookies pro správné fungování a analýzu návštěvnosti.
      <a href="/cookies.php" style="color:#93c5fd;text-decoration:underline">Zjistit více</a>
    </p>
    <div style="display:flex;gap:10px;flex-shrink:0;flex-wrap:wrap">
      <button onclick="cookieConsent('minimal')" style="padding:9px 18px;border:1px solid #475569;background:transparent;color:#f8fafc;border-radius:7px;cursor:pointer;font-size:.875rem;font-family:inherit">
        Pouze nezbytné
      </button>
      <button onclick="cookieConsent('all')" style="padding:9px 22px;background:#2563eb;color:#fff;border:none;border-radius:7px;cursor:pointer;font-weight:600;font-size:.875rem;font-family:inherit">
        Přijmout vše
      </button>
    </div>
  </div>
</div>
<script>
(function () {
  if (!localStorage.getItem('cookie_consent')) {
    document.getElementById('cookie-banner').style.display = 'block';
  }
})();
function cookieConsent(type) {
  localStorage.setItem('cookie_consent', type);
  var el = document.getElementById('cookie-banner');
  el.style.transition = 'opacity .3s';
  el.style.opacity    = '0';
  setTimeout(function () { el.style.display = 'none'; }, 320);
}
</script>
