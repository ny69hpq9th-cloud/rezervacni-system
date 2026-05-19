<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Zásady ochrany osobních údajů – Rezervly</title>
<meta name="description" content="Informace o zpracování osobních údajů a ochraně soukromí v souladu s GDPR.">
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
  <h1>Zásady ochrany osobních údajů</h1>
  <p class="subtitle">Platné od 1.&nbsp;1.&nbsp;2025 &bull; Naposledy aktualizováno: <?= date('j. n. Y') ?></p>

  <div class="highlight">
    <p><strong>Provozovatel:</strong> Oliver Hlavnička, IČO: 29521939, e-mail: <a href="mailto:info@rezervly.eu">info@rezervly.eu</a></p>
  </div>

  <h2>1. Úvod</h2>
  <p>Rezervly („my", „naše platforma") provozuje rezervační systém dostupný na adrese <a href="https://rezervly.eu">rezervly.eu</a>. Tímto dokumentem vás informujeme o tom, jaké osobní údaje zpracováváme, proč a jak, a jaká máte v souvislosti s jejich zpracováním práva v souladu s nařízením (EU) 2016/679 (GDPR).</p>

  <h2>2. Jaké osobní údaje zpracováváme</h2>
  <p><strong>a) Údaje podnikatelů (registrovaných uživatelů):</strong></p>
  <ul>
    <li>Jméno a příjmení / název firmy</li>
    <li>E-mailová adresa</li>
    <li>Heslo (uloženo výhradně jako bcrypt hash — nikdy v čitelné podobě)</li>
    <li>Typ podnikání, logo, adresa provozovny</li>
    <li>Platební informace — zpracovává výhradně <strong>Stripe</strong> (viz sekce 5); my ukládáme pouze identifikátor zákazníka Stripe</li>
    <li>IP adresa při přihlášení (z bezpečnostních důvodů)</li>
  </ul>
  <p><strong>b) Údaje zákazníků při rezervaci:</strong></p>
  <ul>
    <li>Jméno a příjmení zákazníka</li>
    <li>E-mailová adresa zákazníka</li>
    <li>Telefonní číslo</li>
    <li>Datum a čas rezervace, poznámka</li>
  </ul>
  <p>Tyto údaje jsou předávány a zpracovávány výhradně jménem podnikatele (uživatele Rezervly), který je ve vztahu k nim správcem údajů.</p>

  <h2>3. Právní základ zpracování</h2>
  <ul>
    <li><strong>Plnění smlouvy (čl. 6 odst. 1 písm. b) GDPR)</strong> — registrace, vedení účtu, zpracování plateb</li>
    <li><strong>Oprávněný zájem (čl. 6 odst. 1 písm. f) GDPR)</strong> — bezpečnost platformy, prevence podvodů</li>
    <li><strong>Souhlas (čl. 6 odst. 1 písm. a) GDPR)</strong> — marketingové e-maily (pouze pokud je udělen)</li>
    <li><strong>Právní povinnost (čl. 6 odst. 1 písm. c) GDPR)</strong> — daňová a účetní dokumentace</li>
  </ul>

  <h2>4. Jak osobní údaje používáme</h2>
  <ul>
    <li>Poskytování a provozování rezervační platformy</li>
    <li>Odesílání potvrzovacích a notifikačních e-mailů</li>
    <li>Zpracování plateb předplatného</li>
    <li>Technická podpora a komunikace se zákazníky</li>
    <li>Zabezpečení platformy a detekce podvodného jednání</li>
    <li>Plnění zákonných povinností</li>
  </ul>

  <h2>5. Stripe jako zpracovatel plateb</h2>
  <p>Veškeré platby jsou zpracovávány prostřednictvím společnosti <strong>Stripe, Inc.</strong> (sídlo: 510 Townsend Street, San Francisco, CA 94103, USA). Stripe je certifikován dle PCI DSS Level 1. Čísla platebních karet ani jejich plné údaje nikdy neprochází našimi servery ani nejsou u nás uloženy.</p>
  <p>Stripe je naším zpracovatelem ve smyslu čl. 28 GDPR. Zpracování probíhá v souladu se zásadami ochrany osobních údajů Stripe dostupnými na <a href="https://stripe.com/cz/privacy" target="_blank" rel="noopener">stripe.com/cz/privacy</a>.</p>
  <p>Přenos dat do USA probíhá na základě standardních smluvních doložek (SCC) schválených Evropskou komisí.</p>

  <h2>6. Příjemci osobních údajů</h2>
  <p>Vaše osobní údaje neprodáváme třetím stranám. Sdílíme je pouze s:</p>
  <ul>
    <li><strong>Stripe</strong> — zpracování plateb</li>
    <li><strong>Hostinger</strong> — provozovatel serverové infrastruktury (EU datová centra)</li>
    <li><strong>Orgány veřejné moci</strong> — pokud to vyžaduje zákon</li>
  </ul>

  <h2>7. Doba uchovávání údajů</h2>
  <ul>
    <li>Uživatelský účet — po dobu trvání smluvního vztahu + 3 roky po jeho ukončení</li>
    <li>Rezervace — 3 roky od uskutečnění</li>
    <li>Fakturační záznamy — 10 let (zákonná povinnost)</li>
    <li>Přihlašovací logy — 90 dní</li>
  </ul>

  <h2>8. Vaše práva</h2>
  <p>Podle GDPR máte tato práva, která můžete uplatnit e-mailem na <a href="mailto:info@rezervly.eu">info@rezervly.eu</a>:</p>
  <ul>
    <li><strong>Právo na přístup</strong> — právo získat potvrzení, zda zpracováváme vaše osobní údaje, a kopii těchto údajů</li>
    <li><strong>Právo na opravu</strong> — právo na opravu nepřesných nebo doplnění neúplných údajů</li>
    <li><strong>Právo na výmaz („být zapomenut")</strong> — za podmínek stanovených GDPR (čl. 17)</li>
    <li><strong>Právo na omezení zpracování</strong> — v případech stanovených čl. 18 GDPR</li>
    <li><strong>Právo na přenositelnost údajů</strong> — obdržení svých údajů ve strojově čitelném formátu</li>
    <li><strong>Právo vznést námitku</strong> — proti zpracování na základě oprávněného zájmu</li>
    <li><strong>Právo odvolat souhlas</strong> — kdykoli, bez dopadu na zákonnost zpracování před odvoláním</li>
    <li><strong>Právo podat stížnost</strong> — u Úřadu pro ochranu osobních údajů (uoou.cz)</li>
  </ul>
  <p>Na vaši žádost odpovíme bez zbytečného odkladu, nejpozději do 30 dnů.</p>

  <h2>9. Cookies</h2>
  <p>Informace o cookies a jejich správě najdete v naší <a href="/cookies.php">Cookie policy</a>.</p>

  <h2>10. Zabezpečení</h2>
  <p>Přijali jsme technická a organizační opatření k ochraně vašich osobních údajů: šifrování přenosu (HTTPS/TLS), hashování hesel (bcrypt), přístupy na základě principu nejmenšího privilegia a pravidelné zálohy dat.</p>

  <h2>11. Změny těchto zásad</h2>
  <p>Tyto zásady ochrany osobních údajů můžeme příležitostně aktualizovat. O podstatných změnách vás budeme informovat e-mailem nebo upozorněním na platformě. Aktuální verze je vždy dostupná na této stránce.</p>

  <h2>12. Kontakt</h2>
  <p>S jakýmikoli dotazy týkajícími se ochrany osobních údajů se obraťte na:<br>
  <strong>Oliver Hlavnička</strong><br>
  IČO: 29521939<br>
  E-mail: <a href="mailto:info@rezervly.eu">info@rezervly.eu</a></p>
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
