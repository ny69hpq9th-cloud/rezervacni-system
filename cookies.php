<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cookie policy – Rezervly</title>
<meta name="description" content="Informace o použití cookies na platformě Rezervly v souladu s GDPR.">
<meta name="robots" content="noindex">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,Arial,sans-serif;color:#1e293b;background:#f8fafc;line-height:1.7}
a{color:#2563eb}
.topnav{background:#fff;border-bottom:1px solid #e5e7eb;padding:0 24px}
.topnav__inner{max-width:960px;margin:0 auto;display:flex;align-items:center;height:64px;gap:16px}
.topnav__brand{font-weight:800;font-size:1.25rem;color:#2563eb;text-decoration:none}
.topnav__brand span{color:#0f172a}
.topnav__spacer{flex:1}
.topnav__link{color:#64748b;text-decoration:none;font-size:.9rem}
.topnav__btn{padding:8px 18px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem}
main{max-width:760px;margin:48px auto 80px;padding:0 24px}
h1{font-size:2rem;font-weight:800;color:#0f172a;margin-bottom:6px}
.subtitle{color:#64748b;font-size:.9rem;margin-bottom:40px;padding-bottom:24px;border-bottom:1px solid #e5e7eb}
h2{font-size:1.2rem;font-weight:700;color:#0f172a;margin:36px 0 12px}
p{color:#374151;margin-bottom:14px;font-size:.95rem}
ul{color:#374151;margin:10px 0 14px 24px;font-size:.95rem}
ul li{margin-bottom:6px}
.highlight{background:#eff6ff;border-left:3px solid #2563eb;padding:14px 18px;border-radius:0 8px 8px 0;margin:20px 0}
.highlight p{margin:0;color:#1e40af;font-size:.9rem}
.cookie-table{width:100%;border-collapse:collapse;margin:16px 0 20px;font-size:.88rem}
.cookie-table th{background:#f1f5f9;padding:10px 14px;text-align:left;font-weight:600;color:#0f172a;border:1px solid #e5e7eb}
.cookie-table td{padding:10px 14px;border:1px solid #e5e7eb;color:#374151;vertical-align:top}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.8rem;font-weight:600}
.badge--green{background:#dcfce7;color:#166534}
.badge--yellow{background:#fef9c3;color:#854d0e}
footer{background:#1e293b;color:#94a3b8;padding:32px 24px}
.footer__inner{max-width:960px;margin:0 auto;display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;font-size:.85rem}
.footer__links{display:flex;gap:20px;flex-wrap:wrap}
.footer__links a{color:#94a3b8;text-decoration:none}
.footer__links a:hover{color:#e2e8f0}
.btn-reset{display:inline-block;margin-top:10px;padding:9px 22px;background:#2563eb;color:#fff;border:none;border-radius:8px;cursor:pointer;font-family:inherit;font-size:.9rem;font-weight:600}
</style>
</head>
<body>

<nav class="topnav">
  <div class="topnav__inner">
    <a href="/" class="topnav__brand">Rezervly<span>.</span></a>
    <div class="topnav__spacer"></div>
    <a href="/login.php" class="topnav__link" style="margin-right:12px">Přihlásit se</a>
    <a href="/register.php" class="topnav__btn">Registrace zdarma</a>
  </div>
</nav>

<main>
  <h1>Cookie policy</h1>
  <p class="subtitle">Platné od 1.&nbsp;1.&nbsp;2025 &bull; Naposledy aktualizováno: <?= date('j. n. Y') ?></p>

  <div class="highlight">
    <p><strong>Provozovatel:</strong> Oliver Hlavnička, IČO: 29521939, e-mail: <a href="mailto:info@rezervly.eu">info@rezervly.eu</a></p>
  </div>

  <h2>1. Co jsou cookies</h2>
  <p>Cookies jsou malé textové soubory, které webová stránka ukládá do vašeho prohlížeče při návštěvě. Umožňují webu zapamatovat si vaše preference, udržet vás přihlášené a analyzovat, jak web používáte.</p>
  <p>Tato stránka využívá také technologii <strong>localStorage</strong> (lokální úložiště prohlížeče) pro uložení vašeho souhlasu s cookies — bez expirace, pouze v místním úložišti.</p>

  <h2>2. Jaké cookies používáme</h2>

  <table class="cookie-table">
    <thead>
      <tr>
        <th>Název / klíč</th>
        <th>Typ</th>
        <th>Účel</th>
        <th>Platnost</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><code>rezervly_sess</code></td>
        <td><span class="badge badge--green">Nezbytná</span></td>
        <td>Udržení přihlašovací session (PHP session cookie). Nezbytná pro fungování přihlašování a administrace.</td>
        <td>Session (zavření prohlížeče)</td>
      </tr>
      <tr>
        <td><code>lang</code></td>
        <td><span class="badge badge--green">Nezbytná</span></td>
        <td>Uložení preferovaného jazyka rozhraní (cs / en).</td>
        <td>1 rok</td>
      </tr>
      <tr>
        <td><code>cookie_consent</code><br><small>(localStorage)</small></td>
        <td><span class="badge badge--green">Nezbytná</span></td>
        <td>Uložení volby souhlasu s cookies — zabraňuje opakovanému zobrazení banneru.</td>
        <td>Bez expirace (localStorage)</td>
      </tr>
      <tr>
        <td><code>__stripe_mid</code><br><code>__stripe_sid</code></td>
        <td><span class="badge badge--yellow">Funkční</span></td>
        <td>Cookies nastavované platební bránou Stripe při zpracování plateb. Nutné pro ověření platební transakce a prevenci podvodů.</td>
        <td>1 rok / session</td>
      </tr>
    </tbody>
  </table>

  <p>Na tomto webu <strong>nepoužíváme</strong> reklamní ani sledovací cookies třetích stran (Google Analytics, Facebook Pixel apod.).</p>

  <h2>3. Nezbytné cookies</h2>
  <p>Nezbytné cookies jsou technicky nutné pro základní fungování webu — přihlašování, jazykové nastavení a zabezpečení. Tyto cookies nelze zakázat bez ztráty funkčnosti. Nevyžadují váš souhlas dle čl. 5 odst. 3 směrnice 2002/58/ES (ePrivacy) ve znění pozdějších předpisů.</p>

  <h2>4. Funkční cookies (Stripe)</h2>
  <p>Platební brána Stripe nastavuje vlastní cookies nezbytné pro zpracování platby a ochranu před podvody. Tyto cookies jsou aktivní pouze na stránkách, kde probíhá platební operace. Stripe je certifikován dle PCI DSS Level 1. Více informací: <a href="https://stripe.com/cz/privacy" target="_blank" rel="noopener">stripe.com/cz/privacy</a>.</p>

  <h2>5. Správa a odvolání souhlasu</h2>
  <p>Svůj souhlas s cookies můžete kdykoli změnit nebo odvolat:</p>
  <ul>
    <li><strong>Tlačítkem níže</strong> — vymaže vaši volbu a znovu zobrazí cookie banner</li>
    <li><strong>V nastavení prohlížeče</strong> — většina prohlížečů umožňuje cookies zobrazit, zablokovat nebo smazat</li>
    <li><strong>Nástrojem prohlížeče</strong> — incognito/soukromý režim, rozšíření pro blokování trackerů</li>
  </ul>
  <p>Návody pro správu cookies v jednotlivých prohlížečích:</p>
  <ul>
    <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Google Chrome</a></li>
    <li><a href="https://support.mozilla.org/cs/kb/vymazani-cookies" target="_blank" rel="noopener">Mozilla Firefox</a></li>
    <li><a href="https://support.apple.com/cs-cz/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Apple Safari</a></li>
    <li><a href="https://support.microsoft.com/cs-cz/windows/odstranit-a-spravovat-soubory-cookie-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener">Microsoft Edge</a></li>
  </ul>

  <button class="btn-reset" onclick="resetCookieConsent()">Obnovit nastavení cookies</button>

  <h2>6. Práva subjektů údajů</h2>
  <p>V rozsahu, ve kterém cookies zpracovávají osobní údaje, máte práva dle GDPR (přístup, výmaz, omezení zpracování). Podrobnosti naleznete v <a href="/privacy-policy.php">Zásadách ochrany osobních údajů</a>.</p>

  <h2>7. Kontakt</h2>
  <p>S dotazy k cookie policy se obraťte na <a href="mailto:info@rezervly.eu">info@rezervly.eu</a>.</p>
</main>

<footer>
  <div class="footer__inner">
    <div>© <?= date('Y') ?> Rezervly &bull; Oliver Hlavnička &bull; IČO: 29521939</div>
    <div class="footer__links">
      <a href="/privacy-policy.php">Ochrana osobních údajů</a>
      <a href="/terms.php">Obchodní podmínky</a>
      <a href="/cookies.php">Cookies</a>
    </div>
  </div>
</footer>

<?php require_once __DIR__ . '/includes/cookie-banner.php'; ?>

<script>
function resetCookieConsent() {
  localStorage.removeItem('cookie_consent');
  var banner = document.getElementById('cookie-banner');
  banner.style.display  = 'block';
  banner.style.opacity  = '1';
  banner.style.transition = '';
  window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}
</script>
</body>
</html>
