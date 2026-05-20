<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$lang    = currentLang();
$en      = $lang === 'en';
$updated = date('j. n. Y');

$basicCzk = PLAN_BASIC_PRICE;
$proCzk   = PLAN_PRO_PRICE;
$basicEur = PLAN_BASIC_PRICE_EUR;
$proEur   = PLAN_PRO_PRICE_EUR;
$maxSvc   = BASIC_MAX_SERVICES;
$maxBook  = BASIC_MAX_BOOKINGS;
$trial    = TRIAL_DAYS;
?>
<!DOCTYPE html>
<html lang="<?= htmlLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $en ? 'Terms of Service' : 'Obchodní podmínky' ?> – Rezervly</title>
<meta name="description" content="<?= $en
    ? 'Terms of Service for the Rezervly online booking platform — subscriptions, payments, and cancellation policy.'
    : 'Obchodní podmínky platformy Rezervly pro online rezervace a správu předplatného.' ?>">
<meta name="robots" content="noindex">
<link rel="canonical" href="<?= e(PLATFORM_URL) ?>/terms.php">
<link rel="alternate" hreflang="cs" href="<?= e(PLATFORM_URL) ?>/terms.php?setlang=cs">
<link rel="alternate" hreflang="en" href="<?= e(PLATFORM_URL) ?>/terms.php?setlang=en">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="shortcut icon" href="/favicon.svg">
<?= themeHeadScript() ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
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
.pricing-table{width:100%;border-collapse:collapse;margin:16px 0 20px;font-size:.9rem}
.pricing-table th{background:#f1f5f9;padding:10px 14px;text-align:left;font-weight:600;color:#0f172a;border:1px solid #e5e7eb}
.pricing-table td{padding:10px 14px;border:1px solid #e5e7eb;color:#374151}
footer{background:#1e293b;color:#94a3b8;padding:32px 24px}
.footer__inner{max-width:960px;margin:0 auto;display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;font-size:.85rem}
.footer__links{display:flex;gap:20px;flex-wrap:wrap}
.footer__links a{color:#94a3b8;text-decoration:none}
.footer__links a:hover{color:#e2e8f0}
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

  <h1>Terms of Service</h1>
  <p class="subtitle">Effective from 1&nbsp;January&nbsp;2025 &bull; Last updated: <?= $updated ?></p>

  <div class="highlight">
    <p><strong>Service provider:</strong> Oliver Hlavnička, Company ID (IČO): 29521939, e-mail: <a href="mailto:info@rezervly.eu">info@rezervly.eu</a></p>
  </div>

  <h2>1. General Provisions</h2>
  <p>These Terms of Service ("Terms") govern the rights and obligations between the operator of the Rezervly platform (Oliver Hlavnička, Company ID: 29521939, hereinafter "we" or "operator") and users — businesses registered on the platform available at <a href="https://rezervly.eu">rezervly.eu</a> (hereinafter "platform").</p>
  <p>By registering on the platform, you agree to these Terms. If you do not agree, please do not use the platform.</p>

  <h2>2. Description of Service</h2>
  <p>Rezervly is a SaaS (Software as a Service) platform designed for businesses to manage online bookings and appointments. The platform enables:</p>
  <ul>
    <li>Creating a public booking page for customers</li>
    <li>Managing offered services, working hours, and capacity</li>
    <li>Automated confirmation and notification emails</li>
    <li>Overview and management of bookings in the admin panel</li>
  </ul>
  <p>The operator reserves the right to continuously develop, modify, and update the platform. Users will be notified of significant changes by email.</p>

  <h2>3. Registration and User Account</h2>
  <p>Registration on the platform is free. The user agrees to:</p>
  <ul>
    <li>Provide truthful and up-to-date information</li>
    <li>Keep login credentials confidential</li>
    <li>Not share account access with third parties</li>
    <li>Immediately notify the operator of any suspected unauthorised access</li>
  </ul>
  <p>The operator reserves the right to suspend or terminate accounts that violate these Terms or are used for unlawful activities.</p>

  <h2>4. Subscriptions and Payments</h2>
  <p>After the free trial period, a paid subscription is required for full access to the platform. Current pricing:</p>

  <table class="pricing-table">
    <thead>
      <tr><th>Plan</th><th>Price (CZK/month)</th><th>Price (EUR/month)</th><th>Includes</th></tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Basic</strong></td>
        <td><?= $basicCzk ?> CZK</td>
        <td><?= $basicEur ?> €</td>
        <td>Up to <?= $maxSvc ?> services, <?= $maxBook ?> bookings/month</td>
      </tr>
      <tr>
        <td><strong>Pro</strong></td>
        <td><?= $proCzk ?> CZK</td>
        <td><?= $proEur ?> €</td>
        <td>Unlimited services and bookings, priority support</td>
      </tr>
    </tbody>
  </table>

  <p>Payments are processed through the <strong>Stripe</strong> payment gateway. Subscriptions renew automatically each month. The operator will issue a tax receipt within 15 days of receiving payment.</p>
  <p>The operator reserves the right to change subscription prices with at least 30 days' advance notice by email. If you do not agree to the new prices, you may cancel your subscription.</p>

  <h2>5. Free Trial</h2>
  <p>Every new user receives a <strong><?= $trial ?>-day free trial</strong> with full access to Pro plan features. No credit card is required to start the trial.</p>
  <p>After the trial expires without activating a subscription, access to the admin panel will be restricted. Your data (settings, services, bookings) is retained for 90 days after the trial expires.</p>

  <h2>6. Cancellation of Subscription</h2>
  <p>You may cancel your subscription at any time through the <em>Subscription</em> section in the admin panel, or by emailing <a href="mailto:info@rezervly.eu">info@rezervly.eu</a>.</p>
  <p>After cancellation, your subscription remains active until the end of the current billing period. Automatic renewal will stop and no further charges will be made.</p>

  <h2>7. Refund Policy</h2>
  <p>Due to the digital nature of our service, <strong>we do not provide refunds for already-paid subscription months</strong>, except in the following cases:</p>
  <ul>
    <li>Technical failure on our part that prevented use of the service for more than 72 hours</li>
    <li>Unintentional duplicate billing</li>
    <li>Other cases at the sole discretion of the operator</li>
  </ul>
  <p>To request a refund, email <a href="mailto:info@rezervly.eu">info@rezervly.eu</a> with your account ID and reason for the request.</p>

  <h2>8. User Obligations</h2>
  <p>You agree not to use the platform for:</p>
  <ul>
    <li>Any unlawful activity</li>
    <li>Sending spam or unsolicited commercial messages</li>
    <li>Damaging the reputation of the operator or other users</li>
    <li>Attempting to gain unauthorised access to platform systems</li>
    <li>Overloading the platform infrastructure</li>
  </ul>

  <h2>9. Limitation of Liability</h2>
  <p>The platform is provided "as is". The operator is not liable for:</p>
  <ul>
    <li>Loss of data caused by force majeure or third-party attacks</li>
    <li>Loss of profit arising from temporary platform unavailability</li>
    <li>Damages caused by improper use of the platform by the user</li>
  </ul>
  <p>Platform availability (SLA) is not guaranteed; however, the operator commits to minimising downtime and providing advance notice of planned maintenance.</p>

  <h2>10. Privacy</h2>
  <p>Personal data processing is carried out in accordance with GDPR. For details, see our <a href="/privacy-policy.php">Privacy Policy</a>.</p>

  <h2>11. Governing Law and Dispute Resolution</h2>
  <p>These Terms are governed by the laws of the Czech Republic. Disputes will be resolved preferably by amicable agreement. If no agreement is reached, the competent courts of the Czech Republic shall have jurisdiction.</p>
  <p>Consumers may contact the Czech Trade Inspection Authority (coi.cz) for alternative dispute resolution.</p>

  <h2>12. Changes to Terms</h2>
  <p>The operator reserves the right to amend these Terms. Users will be notified of significant changes by email at least 14 days in advance. By continuing to use the platform after changes take effect, the user accepts the new Terms.</p>

  <h2>13. Contact</h2>
  <p><strong>Oliver Hlavnička</strong><br>
  Company ID (IČO): 29521939<br>
  E-mail: <a href="mailto:info@rezervly.eu">info@rezervly.eu</a><br>
  Web: <a href="https://rezervly.eu">rezervly.eu</a></p>

<?php else: ?>

  <h1>Obchodní podmínky</h1>
  <p class="subtitle">Platné od 1.&nbsp;1.&nbsp;2025 &bull; Naposledy aktualizováno: <?= $updated ?></p>

  <div class="highlight">
    <p><strong>Provozovatel:</strong> Oliver Hlavnička, IČO: 29521939, e-mail: <a href="mailto:info@rezervly.eu">info@rezervly.eu</a></p>
  </div>

  <h2>1. Obecná ustanovení</h2>
  <p>Tyto obchodní podmínky (dále jen „OP") upravují práva a povinnosti mezi provozovatelem platformy Rezervly (Oliver Hlavnička, IČO: 29521939, dále jen „provozovatel") a uživateli — podnikateli registrovanými na platformě dostupné na adrese <a href="https://rezervly.eu">rezervly.eu</a> (dále jen „platforma").</p>
  <p>Registrací na platformě vyjadřujete souhlas s těmito OP. Pokud s podmínkami nesouhlasíte, platformu nepoužívejte.</p>

  <h2>2. Popis služby</h2>
  <p>Rezervly je SaaS platforma (Software as a Service) určená podnikatelům k online správě rezervací a objednávek zákazníků. Platforma umožňuje:</p>
  <ul>
    <li>Vytvoření veřejné rezervační stránky pro zákazníky</li>
    <li>Správu nabízených služeb, pracovní doby a kapacity</li>
    <li>Automatické potvrzovací a notifikační e-maily</li>
    <li>Přehled a správu rezervací v administraci</li>
  </ul>
  <p>Provozovatel si vyhrazuje právo platformu průběžně rozvíjet, upravovat a aktualizovat. O podstatných změnách bude uživatel informován e-mailem.</p>

  <h2>3. Registrace a uživatelský účet</h2>
  <p>Registrace na platformě je bezplatná. Uživatel se zavazuje:</p>
  <ul>
    <li>Uvádět pravdivé a aktuální informace</li>
    <li>Udržovat přístupové údaje v tajnosti</li>
    <li>Neposkytovat přístup k účtu třetím osobám</li>
    <li>Okamžitě informovat provozovatele o podezření na neoprávněný přístup</li>
  </ul>
  <p>Provozovatel si vyhrazuje právo pozastavit nebo zrušit účet, který porušuje tyto OP, nebo jehož prostřednictvím dochází k nezákonnému jednání.</p>

  <h2>4. Předplatné a platby</h2>
  <p>Po uplynutí bezplatné zkušební doby je pro plné využití platformy nutné aktivovat předplatné. Aktuální ceník:</p>

  <table class="pricing-table">
    <thead>
      <tr><th>Plán</th><th>Cena (CZK/měsíc)</th><th>Cena (EUR/měsíc)</th><th>Zahrnuje</th></tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Basic</strong></td>
        <td><?= $basicCzk ?> Kč</td>
        <td><?= $basicEur ?> €</td>
        <td>Až <?= $maxSvc ?> služby, <?= $maxBook ?> rezervací/měsíc</td>
      </tr>
      <tr>
        <td><strong>Pro</strong></td>
        <td><?= $proCzk ?> Kč</td>
        <td><?= $proEur ?> €</td>
        <td>Neomezené služby a rezervace, prioritní podpora</td>
      </tr>
    </tbody>
  </table>

  <p>Platby jsou zpracovávány prostřednictvím platební brány <strong>Stripe</strong>. Předplatné se automaticky obnovuje každý měsíc. Provozovatel vystaví daňový doklad do 15 dnů od přijetí platby.</p>
  <p>Provozovatel si vyhrazuje právo změnit ceny předplatného. O změně cen bude uživatel informován nejméně 30 dnů předem e-mailem. Pokud uživatel se změnou nesouhlasí, může předplatné zrušit.</p>

  <h2>5. Zkušební verze (Free Trial)</h2>
  <p>Po registraci získá každý nový uživatel <strong><?= $trial ?>-denní bezplatnou zkušební verzi</strong> s přístupem k funkcím plánu Pro. Kreditní karta není vyžadována pro zahájení zkušební doby.</p>
  <p>Po uplynutí zkušební doby bez aktivace předplatného bude přístup k administraci omezen. Data (nastavení, služby, rezervace) jsou uchovávána po dobu 90 dnů od vypršení trialu.</p>

  <h2>6. Zrušení předplatného</h2>
  <p>Předplatné lze zrušit kdykoli prostřednictvím sekce <em>Předplatné</em> v administraci platformy, nebo e-mailem na <a href="mailto:info@rezervly.eu">info@rezervly.eu</a>.</p>
  <p>Po zrušení bude předplatné aktivní do konce aktuálního fakturačního období. Automatické obnovování se zastaví a žádná další platba nebude stržena.</p>

  <h2>7. Zásady vrácení peněz (Refund Policy)</h2>
  <p>Vzhledem k digitální povaze služby <strong>neposkytujeme vrácení peněz za již uhrazené měsíce předplatného</strong>, s výjimkou případů:</p>
  <ul>
    <li>Technické závady na straně provozovatele, která znemožnila použití služby po dobu delší než 72 hodin</li>
    <li>Neúmyslného duplicitního účtování</li>
    <li>Jiných případů na základě individuálního posouzení provozovatelem</li>
  </ul>
  <p>Žádost o vrácení peněz zasílejte na <a href="mailto:info@rezervly.eu">info@rezervly.eu</a> s uvedením ID účtu a důvodu žádosti.</p>

  <h2>8. Povinnosti uživatele</h2>
  <p>Uživatel se zavazuje, že platformu nebude využívat k:</p>
  <ul>
    <li>Nezákonnému jednání</li>
    <li>Šíření spamu nebo nevyžádaných obchodních sdělení</li>
    <li>Poškozování dobré pověsti provozovatele nebo jiných uživatelů</li>
    <li>Pokusu o získání neoprávněného přístupu k systémům</li>
    <li>Přetěžování infrastruktury platformy</li>
  </ul>

  <h2>9. Omezení odpovědnosti</h2>
  <p>Platforma je poskytována „tak, jak je" (as-is). Provozovatel neodpovídá za:</p>
  <ul>
    <li>Ztrátu dat způsobenou vyšší mocí nebo útokem třetích stran</li>
    <li>Ušlý zisk vzniklý dočasnou nedostupností platformy</li>
    <li>Škody způsobené nesprávným použitím platformy uživatelem</li>
  </ul>
  <p>Dostupnost platformy (SLA) není zaručena, provozovatel se však zavazuje usilovat o minimální výpadky a o technických přestávkách informovat předem.</p>

  <h2>10. Ochrana osobních údajů</h2>
  <p>Zpracování osobních údajů probíhá v souladu s GDPR. Podrobnosti naleznete v <a href="/privacy-policy.php">Zásadách ochrany osobních údajů</a>.</p>

  <h2>11. Rozhodné právo a řešení sporů</h2>
  <p>Tyto OP se řídí právem České republiky. Případné spory budou řešeny přednostně smírnou cestou. Pokud nedojde k dohodě, jsou k řešení sporů příslušné soudy České republiky.</p>
  <p>Spotřebitelé mají právo obrátit se na Českou obchodní inspekci (coi.cz) pro mimosoudní řešení sporů.</p>

  <h2>12. Změny obchodních podmínek</h2>
  <p>Provozovatel si vyhrazuje právo tyto OP měnit. O podstatných změnách bude uživatel informován e-mailem nejméně 14 dní předem. Pokračováním v užívání platformy po nabytí účinnosti změn uživatel vyjadřuje souhlas s novým zněním.</p>

  <h2>13. Kontakt</h2>
  <p><strong>Oliver Hlavnička</strong><br>
  IČO: 29521939<br>
  E-mail: <a href="mailto:info@rezervly.eu">info@rezervly.eu</a><br>
  Web: <a href="https://rezervly.eu">rezervly.eu</a></p>

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
</body>
</html>
