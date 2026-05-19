<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect(PLATFORM_URL . '/dashboard/index.php');
}

$flash = getFlash();
$pname = PLATFORM_NAME;
$lang  = currentLang();
?>
<!DOCTYPE html>
<html lang="<?= htmlLang() ?>">
<head>
<meta charset="UTF-8">
<?= themeHeadScript() ?>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(PLATFORM_TITLE) ?></title>
<?php renderSeoHead([
    'title'    => PLATFORM_TITLE,
    'desc'     => __('index.meta_desc'),
    'keywords' => __('index.meta_keywords'),
    'canonical' => PLATFORM_URL . '/',
]); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
<?php
// ── Structured data (Schema.org) ──────────────────────────────────────────
$faqEntities = [];
for ($i = 1; $i <= 5; $i++) {
    $q = strip_tags(__("index.faq.q{$i}"));
    $a = strip_tags(__("index.faq.a{$i}"));
    if ($q && $q !== "index.faq.q{$i}") {
        $faqEntities[] = [
            '@type' => 'Question',
            'name'  => $q,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
        ];
    }
}
$seoSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => PLATFORM_NAME,
        'url'      => PLATFORM_URL,
        'logo'     => PLATFORM_URL . '/assets/img/og-image.jpg',
        'contactPoint' => [
            '@type'       => 'ContactPoint',
            'email'       => PLATFORM_EMAIL,
            'contactType' => 'customer support',
        ],
    ],
    [
        '@context'            => 'https://schema.org',
        '@type'               => 'SoftwareApplication',
        'name'                => PLATFORM_NAME,
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem'     => 'Web',
        'url'                 => PLATFORM_URL,
        'description'         => __('index.meta_desc'),
        'offers'              => [
            ['@type' => 'Offer', 'name' => 'Basic', 'price' => (string)PLAN_BASIC_PRICE, 'priceCurrency' => 'CZK'],
            ['@type' => 'Offer', 'name' => 'Pro',   'price' => (string)PLAN_PRO_PRICE,   'priceCurrency' => 'CZK'],
        ],
    ],
    [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $faqEntities,
    ],
];
foreach ($seoSchemas as $schema) {
    echo '<script type="application/ld+json">'
        . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . '</script>' . "\n";
}
?>
</head>
<body>

<!-- NAV -->
<nav class="nav" id="main-nav">
  <div class="container">
    <div class="nav__inner">
      <a href="/" class="nav__logo" style="display:flex;align-items:center;gap:10px;text-decoration:none"><?= logoIcon('color') ?><span style="font-weight:800;font-size:1.15rem"><?= e($pname) ?></span></a>
      <div class="nav__links">
        <a href="#funkce"><?= __('nav.features') ?></a>
        <a href="#jak-to-funguje"><?= __('nav.how_it_works') ?></a>
        <a href="#cenik"><?= __('nav.pricing') ?></a>
        <a href="#faq"><?= __('nav.faq') ?></a>
        <div class="nav__mobile-cta">
          <a href="/login.php" class="btn btn--ghost btn--sm" style="width:100%;justify-content:center"><?= __('nav.login') ?></a>
          <a href="/register.php" class="btn btn--primary btn--sm" style="width:100%;justify-content:center"><?= __('nav.start_free') ?></a>
        </div>
      </div>
      <div class="nav__cta">
        <?= langSwitcher() ?>
        <?= themeToggle() ?>
        <div class="nav__cta-btns">
          <a href="/login.php" class="btn btn--ghost btn--sm"><?= __('nav.login') ?></a>
          <a href="/register.php" class="btn btn--primary btn--sm"><?= __('nav.start_free') ?></a>
        </div>
      </div>
      <button class="nav__toggle" id="nav-toggle" aria-label="Menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="hero__inner">
      <div>
        <div class="hero__badge"><?= __('index.hero.badge') ?></div>
        <h1 class="hero__title"><?= __('index.hero.title') ?> <span><?= __('index.hero.title_em') ?></span></h1>
        <p class="hero__subtitle"><?= __('index.hero.subtitle') ?></p>
        <div class="hero__actions">
          <a href="/register.php" class="btn btn--primary btn--lg"><?= __('index.hero.btn_trial') ?></a>
          <a href="#cenik" class="btn btn--outline btn--lg"><?= __('index.hero.btn_price') ?></a>
        </div>
        <div class="hero__stats">
          <div>
            <div class="hero__stat-num">24/7</div>
            <div class="hero__stat-label"><?= __('index.hero.stat_247') ?></div>
          </div>
          <div>
            <div class="hero__stat-num">14 <?= $lang === 'en' ? 'days' : 'dní' ?></div>
            <div class="hero__stat-label"><?= __('index.hero.stat_trial') ?></div>
          </div>
          <div>
            <div class="hero__stat-num">5 min</div>
            <div class="hero__stat-label"><?= __('index.hero.stat_setup') ?></div>
          </div>
        </div>
      </div>
      <div class="hero__visual">
        <div class="hero__visual-header">
          <div class="hero__visual-dot" style="background:#ef4444"></div>
          <div class="hero__visual-dot" style="background:#f59e0b;margin-left:6px"></div>
          <div class="hero__visual-dot" style="background:#10b981;margin-left:6px"></div>
          <span style="margin-left:12px;font-size:.75rem;color:#94a3b8"><?= __('nav.bookings') ?></span>
        </div>
        <div class="hero__visual-body">
          <div class="mock-widget">
            <div class="mock-widget__title"><?= __('index.hero.widget_select') ?></div>
            <div class="mock-service active">
              <div><div class="mock-service__name"><?= $lang === 'en' ? 'Haircut' : 'Střih vlasů' ?></div><div style="font-size:.75rem;color:#64748b">45 min</div></div>
              <div class="mock-service__price"><?= $lang === 'en' ? '€14' : '350 Kč' ?></div>
            </div>
            <div class="mock-service">
              <div><div class="mock-service__name"><?= $lang === 'en' ? 'Colouring' : 'Barvení' ?></div><div style="font-size:.75rem;color:#64748b">90 min</div></div>
              <div class="mock-service__price"><?= $lang === 'en' ? '€32' : '800 Kč' ?></div>
            </div>
            <div style="font-size:.8rem;font-weight:600;color:#374151;margin-top:4px"><?= __('index.hero.widget_times') ?></div>
            <div class="mock-times">
              <div class="mock-time active">9:00</div>
              <div class="mock-time">9:45</div>
              <div class="mock-time">10:30</div>
              <div class="mock-time">14:00</div>
              <div class="mock-time">15:30</div>
            </div>
            <div style="background:#2563eb;color:#fff;border-radius:8px;padding:10px;text-align:center;font-size:.875rem;font-weight:600;margin-top:4px"><?= __('index.hero.widget_book') ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="section section--gray" id="funkce">
  <div class="container">
    <div class="section-header">
      <div class="section-tag"><?= __('index.features.tag') ?></div>
      <h2><?= __('index.features.title') ?></h2>
      <p><?= __('index.features.sub') ?></p>
    </div>
    <div class="features__grid">
      <div class="card card--shadow feature-card">
        <div class="feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg></div>
        <div class="feature-card__title"><?= __('index.features.f1_title') ?></div>
        <div class="feature-card__desc"><?= __('index.features.f1_desc') ?></div>
      </div>
      <div class="card card--shadow feature-card">
        <div class="feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
        <div class="feature-card__title"><?= __('index.features.f2_title') ?></div>
        <div class="feature-card__desc"><?= __('index.features.f2_desc') ?></div>
      </div>
      <div class="card card--shadow feature-card">
        <div class="feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
        <div class="feature-card__title"><?= __('index.features.f3_title') ?></div>
        <div class="feature-card__desc"><?= __('index.features.f3_desc') ?></div>
      </div>
      <div class="card card--shadow feature-card">
        <div class="feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg></div>
        <div class="feature-card__title"><?= __('index.features.f4_title') ?></div>
        <div class="feature-card__desc"><?= __('index.features.f4_desc') ?></div>
      </div>
      <div class="card card--shadow feature-card">
        <div class="feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
        <div class="feature-card__title"><?= __('index.features.f5_title') ?></div>
        <div class="feature-card__desc"><?= __('index.features.f5_desc') ?></div>
      </div>
      <div class="card card--shadow feature-card">
        <div class="feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div class="feature-card__title"><?= __('index.features.f6_title') ?></div>
        <div class="feature-card__desc"><?= __('index.features.f6_desc') ?></div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section" id="jak-to-funguje">
  <div class="container">
    <div class="section-header">
      <div class="section-tag"><?= __('index.how.tag') ?></div>
      <h2><?= __('index.how.title') ?></h2>
      <p><?= __('index.how.sub') ?></p>
    </div>
    <div class="steps">
      <div class="step">
        <div class="step__num">1</div>
        <div class="step__title"><?= __('index.how.s1_title') ?></div>
        <div class="step__desc"><?= __('index.how.s1_desc') ?></div>
      </div>
      <div class="step">
        <div class="step__num">2</div>
        <div class="step__title"><?= __('index.how.s2_title') ?></div>
        <div class="step__desc"><?= __('index.how.s2_desc') ?></div>
      </div>
      <div class="step">
        <div class="step__num">3</div>
        <div class="step__title"><?= __('index.how.s3_title') ?></div>
        <div class="step__desc"><?= __('index.how.s3_desc') ?></div>
      </div>
      <div class="step">
        <div class="step__num">4</div>
        <div class="step__title"><?= __('index.how.s4_title') ?></div>
        <div class="step__desc"><?= __('index.how.s4_desc') ?></div>
      </div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section class="section section--gray" id="cenik">
  <div class="container">
    <div class="section-header">
      <div class="section-tag"><?= __('index.pricing.tag') ?></div>
      <h2><?= __('index.pricing.title') ?></h2>
      <p><?= __('index.pricing.sub') ?></p>
    </div>
    <div class="pricing__grid">
      <div class="card pricing-card">
        <div class="pricing-card__plan">Basic</div>
        <div class="pricing-card__price">
          <div class="pricing-card__amount"><?= langPrice('basic') ?></div>
          <div class="pricing-card__currency"><?= langCurrencySymbol() ?></div>
        </div>
        <div class="pricing-card__period"><?= __('index.pricing.per_month') ?></div>
        <div class="pricing-card__features">
          <div class="pricing-card__feature"><?= __('index.pricing.basic_f1') ?></div>
          <div class="pricing-card__feature"><?= __('index.pricing.basic_f2') ?></div>
          <div class="pricing-card__feature"><?= __('index.pricing.basic_f3') ?></div>
          <div class="pricing-card__feature"><?= __('index.pricing.basic_f4') ?></div>
          <div class="pricing-card__feature"><?= __('index.pricing.basic_f5') ?></div>
          <div class="pricing-card__feature"><?= __('index.pricing.basic_f6') ?></div>
        </div>
        <a href="/register.php" class="btn btn--outline btn--full"><?= __('index.pricing.start_free') ?></a>
      </div>
      <div class="card pricing-card pricing-card--popular">
        <div class="pricing-card__popular-badge"><?= __('common.recommended') ?></div>
        <div class="pricing-card__plan">Pro</div>
        <div class="pricing-card__price">
          <div class="pricing-card__amount"><?= langPrice('pro') ?></div>
          <div class="pricing-card__currency"><?= langCurrencySymbol() ?></div>
        </div>
        <div class="pricing-card__period"><?= __('index.pricing.per_month') ?></div>
        <div class="pricing-card__features">
          <div class="pricing-card__feature"><?= __('index.pricing.pro_f1') ?></div>
          <div class="pricing-card__feature"><?= __('index.pricing.pro_f2') ?></div>
          <div class="pricing-card__feature"><?= __('index.pricing.pro_f3') ?></div>
          <div class="pricing-card__feature"><?= __('index.pricing.pro_f4') ?></div>
          <div class="pricing-card__feature"><?= __('index.pricing.pro_f5') ?></div>
          <div class="pricing-card__feature"><?= __('index.pricing.pro_f6') ?></div>
          <div class="pricing-card__feature"><?= __('index.pricing.pro_f7') ?></div>
        </div>
        <a href="/register.php" class="btn btn--primary btn--full"><?= __('index.pricing.start_free') ?></a>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section" id="faq">
  <div class="container">
    <div class="section-header">
      <div class="section-tag"><?= __('index.faq.tag') ?></div>
      <h2><?= __('index.faq.title') ?></h2>
    </div>
    <div class="faq">
      <?php for ($i = 1; $i <= 5; $i++): ?>
      <div class="faq-item">
        <button class="faq-question"><?= __("index.faq.q{$i}") ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6,9 12,15 18,9"/></svg>
        </button>
        <div class="faq-answer"><?= __("index.faq.a{$i}") ?></div>
      </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<!-- REGISTER CTA -->
<section class="section register-section" id="registrace">
  <div class="container container--md">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center">
      <div>
        <div class="section-tag" style="background:rgba(255,255,255,.15);color:#fff"><?= __('index.cta.tag') ?></div>
        <h2><?= __('index.cta.title') ?></h2>
        <p><?= __('index.cta.sub') ?></p>
        <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px">
          <div style="display:flex;align-items:center;gap:10px;font-size:.875rem;color:rgba(255,255,255,.8)">
            <span style="color:#86efac">✓</span> <?= __('index.cta.b1') ?>
          </div>
          <div style="display:flex;align-items:center;gap:10px;font-size:.875rem;color:rgba(255,255,255,.8)">
            <span style="color:#86efac">✓</span> <?= __('index.cta.b2') ?>
          </div>
          <div style="display:flex;align-items:center;gap:10px;font-size:.875rem;color:rgba(255,255,255,.8)">
            <span style="color:#86efac">✓</span> <?= __('index.cta.b3') ?>
          </div>
        </div>
      </div>
      <div style="text-align:center">
        <?php if ($flash): ?>
          <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <a href="/register.php" class="btn btn--primary btn--lg" style="display:inline-block;width:100%;justify-content:center;font-size:1rem;padding:16px 24px">
          <?= __('index.cta.btn') ?>
        </a>
        <p style="text-align:center;font-size:.8rem;color:rgba(255,255,255,.6);margin-top:14px">
          <?= __('index.cta.terms') ?>
          <?= __('index.cta.have_account') ?> <a href="/login.php" style="color:#93c5fd"><?= __('index.cta.login') ?></a>
        </p>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div>
        <div class="footer__brand"><?= e($pname) ?><span>.</span></div>
        <p class="footer__desc"><?= __('index.footer.desc') ?></p>
      </div>
      <div class="footer__col">
        <h4><?= __('index.footer.product') ?></h4>
        <ul>
          <li><a href="#funkce"><?= __('nav.features') ?></a></li>
          <li><a href="#cenik"><?= __('nav.pricing') ?></a></li>
          <li><a href="#jak-to-funguje"><?= __('nav.how_it_works') ?></a></li>
        </ul>
      </div>
      <div class="footer__col">
        <h4><?= __('index.footer.account') ?></h4>
        <ul>
          <li><a href="/register.php"><?= __('index.footer.register') ?></a></li>
          <li><a href="/login.php"><?= __('index.footer.login') ?></a></li>
        </ul>
      </div>
      <div class="footer__col">
        <h4><?= __('index.footer.contact') ?></h4>
        <ul>
          <li><a href="mailto:<?= e(PLATFORM_EMAIL) ?>"><?= e(PLATFORM_EMAIL) ?></a></li>
        </ul>
      </div>
    </div>
    <div class="footer__bottom">
      <div>© <?= date('Y') ?> <?= e($pname) ?> &bull; Oliver Hlavnička &bull; IČO: 29521939</div>
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <a href="/privacy-policy.php" style="color:inherit;text-decoration:none;opacity:.7"><?= __('index.footer.privacy') ?></a>
        <a href="/terms.php"          style="color:inherit;text-decoration:none;opacity:.7"><?= __('index.footer.terms') ?></a>
        <a href="/cookies.php"        style="color:inherit;text-decoration:none;opacity:.7"><?= __('index.footer.cookies') ?></a>
      </div>
    </div>
  </div>
</footer>

<script src="/assets/js/main.js"></script>
<?php require_once __DIR__ . '/includes/cookie-banner.php'; ?>
</body>
</html>
