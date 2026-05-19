<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$lang    = currentLang();
$en      = $lang === 'en';
$updated = date('j. n. Y');
?>
<!DOCTYPE html>
<html lang="<?= htmlLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $en ? 'Cookie Policy' : 'Cookie policy' ?> – Rezervly</title>
<meta name="description" content="<?= $en
    ? 'Information about the use of cookies on the Rezervly platform in accordance with GDPR and the ePrivacy Directive.'
    : 'Informace o použití cookies na platformě Rezervly v souladu s GDPR a směrnicí ePrivacy.' ?>">
<meta name="robots" content="noindex">
<link rel="canonical" href="<?= e(PLATFORM_URL) ?>/cookies.php">
<link rel="alternate" hreflang="cs" href="<?= e(PLATFORM_URL) ?>/cookies.php?setlang=cs">
<link rel="alternate" hreflang="en" href="<?= e(PLATFORM_URL) ?>/cookies.php?setlang=en">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<?= themeHeadScript() ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,Arial,sans-serif;color:#1e293b;background:#f8fafc;line-height:1.7}
a{color:#2563eb}
.topnav{background:#fff;border-bottom:1px solid #e5e7eb;padding:0 24px}
.topnav__inner{max-width:960px;margin:0 auto;display:flex;align-items:center;height:64px;gap:12px}
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
    <?= langSwitcher() ?>
    <?= themeToggle() ?>
    <a href="/login.php" class="topnav__link" style="margin-left:8px">
      <?= $en ? 'Sign in' : 'Přihlásit se' ?>
    </a>
    <a href="/register.php" class="topnav__btn">
      <?= $en ? 'Get started free' : 'Registrace zdarma' ?>
    </a>
  </div>
</nav>

<main>
<?php if ($en): ?>

  <h1>Cookie Policy</h1>
  <p class="subtitle">Effective from 1&nbsp;January&nbsp;2025 &bull; Last updated: <?= $updated ?></p>

  <div class="highlight">
    <p><strong>Controller:</strong> Oliver Hlavnička, Company ID (IČO): 29521939, e-mail: <a href="mailto:info@rezervly.eu">info@rezervly.eu</a></p>
  </div>

  <h2>1. What Are Cookies</h2>
  <p>Cookies are small text files that a website stores in your browser when you visit it. They allow the website to remember your preferences, keep you logged in, and analyse how you use the site.</p>
  <p>This website also uses <strong>localStorage</strong> (local browser storage) to store your cookie consent — with no expiration, stored only locally in your browser.</p>

  <h2>2. Cookies We Use</h2>

  <table class="cookie-table">
    <thead>
      <tr>
        <th>Name / Key</th>
        <th>Type</th>
        <th>Purpose</th>
        <th>Duration</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><code>rezervly_sess</code></td>
        <td><span class="badge badge--green">Essential</span></td>
        <td>Maintains login session (PHP session cookie). Essential for login functionality and admin panel.</td>
        <td>Session (browser close)</td>
      </tr>
      <tr>
        <td><code>lang</code></td>
        <td><span class="badge badge--green">Essential</span></td>
        <td>Stores the preferred interface language (cs / en).</td>
        <td>1 year</td>
      </tr>
      <tr>
        <td><code>cookie_consent</code><br><small>(localStorage)</small></td>
        <td><span class="badge badge--green">Essential</span></td>
        <td>Stores your cookie consent choice — prevents the banner from appearing again.</td>
        <td>No expiry (localStorage)</td>
      </tr>
      <tr>
        <td><code>__stripe_mid</code><br><code>__stripe_sid</code></td>
        <td><span class="badge badge--yellow">Functional</span></td>
        <td>Cookies set by the Stripe payment gateway during payment processing. Necessary to verify transactions and prevent fraud.</td>
        <td>1 year / session</td>
      </tr>
    </tbody>
  </table>

  <p>We <strong>do not use</strong> advertising or third-party tracking cookies (Google Analytics, Facebook Pixel, etc.).</p>

  <h2>3. Essential Cookies</h2>
  <p>Essential cookies are technically necessary for the basic functioning of the website — login, language settings, and security. These cookies cannot be disabled without losing functionality. They do not require your consent under Art. 5(3) of Directive 2002/58/EC (ePrivacy Directive) as amended.</p>

  <h2>4. Functional Cookies (Stripe)</h2>
  <p>The Stripe payment gateway sets its own cookies necessary for processing payments and fraud prevention. These cookies are only active on pages where a payment operation takes place. Stripe is certified to PCI DSS Level 1. More information: <a href="https://stripe.com/privacy" target="_blank" rel="noopener">stripe.com/privacy</a>.</p>

  <h2>5. Managing and Withdrawing Consent</h2>
  <p>You can change or withdraw your cookie consent at any time:</p>
  <ul>
    <li><strong>Using the button below</strong> — clears your stored choice and shows the cookie banner again</li>
    <li><strong>In your browser settings</strong> — most browsers allow you to view, block, or delete cookies</li>
    <li><strong>Using browser tools</strong> — incognito/private mode, tracker-blocking extensions</li>
  </ul>
  <p>Instructions for managing cookies in specific browsers:</p>
  <ul>
    <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Google Chrome</a></li>
    <li><a href="https://support.mozilla.org/en-US/kb/clear-cookies-and-site-data-firefox" target="_blank" rel="noopener">Mozilla Firefox</a></li>
    <li><a href="https://support.apple.com/guide/safari/manage-cookies-sfri11471/mac" target="_blank" rel="noopener">Apple Safari</a></li>
    <li><a href="https://support.microsoft.com/en-us/windows/delete-and-manage-cookies-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener">Microsoft Edge</a></li>
  </ul>

  <button class="btn-reset" onclick="resetCookieConsent()">Reset cookie settings</button>

  <h2>6. Data Subject Rights</h2>
  <p>To the extent that cookies process personal data, you have rights under GDPR (access, erasure, restriction of processing). For details, see our <a href="/privacy-policy.php">Privacy Policy</a>.</p>

  <h2>7. Contact</h2>
  <p>For questions about this Cookie Policy, contact us at <a href="mailto:info@rezervly.eu">info@rezervly.eu</a>.</p>

<?php else: ?>

  <h1>Cookie policy</h1>
  <p class="subtitle">Platné od 1.&nbsp;1.&nbsp;2025 &bull; Naposledy aktualizováno: <?= $updated ?></p>

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

<?php endif; ?>
</main>

<footer>
  <div class="footer__inner">
    <div>© <?= date('Y') ?> Rezervly &bull; Oliver Hlavnička &bull; IČO: 29521939</div>
    <div class="footer__links">
      <a href="/privacy-policy.php"><?= $en ? 'Privacy Policy'   : 'Ochrana osobních údajů' ?></a>
      <a href="/terms.php">         <?= $en ? 'Terms of Service' : 'Obchodní podmínky' ?></a>
      <a href="/cookies.php">       <?= $en ? 'Cookies'          : 'Cookies' ?></a>
    </div>
  </div>
</footer>

<?php require_once __DIR__ . '/includes/cookie-banner.php'; ?>

<script>
function resetCookieConsent() {
  localStorage.removeItem('cookie_consent');
  var banner = document.getElementById('cookie-banner');
  banner.style.display    = 'block';
  banner.style.opacity    = '1';
  banner.style.transition = '';
  window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}
</script>
</body>
</html>
