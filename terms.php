<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Obchodní podmínky – Rezervly</title>
<meta name="description" content="Obchodní podmínky platformy Rezervly pro online rezervace a správu předplatného.">
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
    <a href="/login.php" class="topnav__link" style="margin-right:12px">Přihlásit se</a>
    <a href="/register.php" class="topnav__btn">Registrace zdarma</a>
  </div>
</nav>

<main>
  <h1>Obchodní podmínky</h1>
  <p class="subtitle">Platné od 1.&nbsp;1.&nbsp;2025 &bull; Naposledy aktualizováno: <?= date('j. n. Y') ?></p>

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
        <td><?= PLAN_BASIC_PRICE ?> Kč</td>
        <td><?= PLAN_BASIC_PRICE_EUR ?> €</td>
        <td>Až <?= BASIC_MAX_SERVICES ?> služby, <?= BASIC_MAX_BOOKINGS ?> rezervací/měsíc</td>
      </tr>
      <tr>
        <td><strong>Pro</strong></td>
        <td><?= PLAN_PRO_PRICE ?> Kč</td>
        <td><?= PLAN_PRO_PRICE_EUR ?> €</td>
        <td>Neomezené služby a rezervace, prioritní podpora</td>
      </tr>
    </tbody>
  </table>

  <p>Platby jsou zpracovávány prostřednictvím platební brány <strong>Stripe</strong>. Předplatné se automaticky obnovuje každý měsíc. Provozovatel vystaví daňový doklad do 15 dnů od přijetí platby.</p>
  <p>Provozovatel si vyhrazuje právo změnit ceny předplatného. O změně cen bude uživatel informován nejméně 30 dnů předem e-mailem. Pokud uživatel se změnou nesouhlasí, může předplatné zrušit.</p>

  <h2>5. Zkušební verze (Free Trial)</h2>
  <p>Po registraci získá každý nový uživatel <strong><?= TRIAL_DAYS ?>-denní bezplatnou zkušební verzi</strong> s přístupem k funkcím plánu Pro. Kreditní karta není vyžadována pro zahájení zkušební doby.</p>
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
</body>
</html>
