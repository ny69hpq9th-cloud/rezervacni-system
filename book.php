<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    http_response_code(404);
    die('<h1>404 – ' . __('book.not_found') . '</h1>');
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE slug = ? AND status = 'active'");
$stmt->execute([$slug]);
$business = $stmt->fetch();

if (!$business) {
    http_response_code(404);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . __('book.not_found') . '</title></head><body style="font-family:sans-serif;text-align:center;padding:80px"><h2>' . __('book.not_found') . '</h2><p>' . __('book.not_found_sub') . '</p></body></html>');
}

if (!hasActiveSubscription($business)) {
    http_response_code(503);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . __('book.unavailable') . '</title></head><body style="font-family:sans-serif;text-align:center;padding:80px"><h2>' . __('book.unavailable') . '</h2><p>' . __('book.unavail_sub') . '</p></body></html>');
}

$svcStmt = $db->prepare("SELECT * FROM services WHERE user_id=? AND active=1 ORDER BY sort_order,name");
$svcStmt->execute([$business['id']]);
$services = $svcStmt->fetchAll();

$accentColor = $business['accent_color'] ?? '#2563eb';
$bname   = e($business['business_name']);
$pname   = PLATFORM_NAME;
$csrfTok = csrfToken();

// Calendar day headers (short, Mon-first)
$calDays = [];
for ($i = 1; $i <= 7; $i++) {
    $calDays[] = getDayNameShort($i % 7);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('book.select_service') ?> – <?= $bname ?></title>
<?php
$bookDesc  = currentLang() === 'en'
    ? 'Online booking at ' . $business['business_name'] . '. Choose a service, date and time easily.'
    : 'Online rezervace u ' . $business['business_name'] . '. Vyberte si službu, datum a čas snadno a rychle.';
$bookCanon = PLATFORM_URL . '/rezervace/' . $business['slug'];
renderSeoHead([
    'title'     => __('book.select_service') . ' – ' . $business['business_name'],
    'desc'      => $bookDesc,
    'canonical' => $bookCanon,
    'ogType'    => 'website',
]);
$bizSchema = json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'LocalBusiness',
    'name'        => $business['business_name'],
    'url'         => $bookCanon,
    'description' => $bookDesc,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo '<script type="application/ld+json">' . $bizSchema . '</script>' . "\n";
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/booking.css">
<style>
:root {
  --accent: <?= e($accentColor) ?>;
  --accent-light: color-mix(in srgb, <?= e($accentColor) ?> 12%, white);
}
@supports not (color: color-mix(in srgb, red 50%, blue)) {
  :root { --accent-light: #eff6ff; }
}
</style>
</head>
<body class="booking-page" data-slug="<?= e($slug) ?>">

<!-- Header -->
<header class="booking-header">
  <div class="booking-header__inner">
    <?php if (!empty($business['logo'])): ?>
      <img src="<?= e($business['logo']) ?>" alt="Logo" class="booking-header__logo">
    <?php else: ?>
      <div class="booking-header__logo-placeholder"><?= mb_strtoupper(mb_substr($business['business_name'],0,1,'UTF-8'),'UTF-8') ?></div>
    <?php endif; ?>
    <div>
      <div class="booking-header__name"><?= $bname ?></div>
      <?php if (!empty($business['address'])): ?>
        <div class="booking-header__desc"><?= e($business['address']) ?></div>
      <?php endif; ?>
    </div>
    <div class="booking-header__badge"><?= __('book.online_badge') ?></div>
    <div style="margin-left:auto"><?= langSwitcher() ?></div>
  </div>
</header>

<!-- Steps indicator -->
<div class="booking-steps">
  <div class="booking-steps__inner">
    <div class="booking-step active" data-step="1">
      <div class="booking-step__num">1</div>
      <div class="booking-step__label"><?= __('book.step1') ?></div>
    </div>
    <div class="booking-step" data-step="2">
      <div class="booking-step__num">2</div>
      <div class="booking-step__label"><?= __('book.step2') ?></div>
    </div>
    <div class="booking-step" data-step="3">
      <div class="booking-step__num">3</div>
      <div class="booking-step__label"><?= __('book.step3') ?></div>
    </div>
    <div class="booking-step" data-step="4">
      <div class="booking-step__num">4</div>
      <div class="booking-step__label"><?= __('book.step4') ?></div>
    </div>
  </div>
</div>

<!-- Main content -->
<main class="booking-main">

  <?php if (!empty($business['custom_message'])): ?>
    <div class="alert alert--info" style="margin-bottom:20px">
      <span class="alert__icon">ℹ</span>
      <?= e($business['custom_message']) ?>
    </div>
  <?php endif; ?>

  <!-- STEP 1: Select service -->
  <div class="booking-panel active" id="panel-1">
    <h2 style="margin-bottom:20px;font-size:1.25rem"><?= __('book.select_service') ?></h2>

    <?php if (empty($services)): ?>
      <div style="text-align:center;padding:60px 20px;color:#94a3b8">
        <div style="font-size:3rem;margin-bottom:16px">🔧</div>
        <h3><?= __('book.no_services') ?></h3>
        <p><?= __('book.no_svc_sub') ?></p>
      </div>
    <?php else: ?>
      <div class="service-list">
        <?php foreach ($services as $s): ?>
          <div class="service-item"
               data-id="<?= $s['id'] ?>"
               data-name="<?= e($s['name']) ?>"
               data-price="<?= (int)$s['price'] ?>"
               data-duration="<?= $s['duration'] ?>">
            <div class="service-item__left">
              <div class="service-item__name"><?= e($s['name']) ?></div>
              <div class="service-item__meta">
                <div class="service-item__meta-tag">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                  <?= formatDuration((int)$s['duration']) ?>
                </div>
              </div>
              <?php if (!empty($s['description'])): ?>
                <div class="service-item__desc"><?= e($s['description']) ?></div>
              <?php endif; ?>
            </div>
            <?php if ($s['price'] > 0): ?>
              <div class="service-item__price"><?= formatPrice((float)$s['price']) ?></div>
            <?php else: ?>
              <div class="service-item__price" style="color:#64748b;font-size:.875rem"><?= __('book.free') ?></div>
            <?php endif; ?>
            <div class="service-item__check"></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="booking-nav">
        <div></div>
        <button id="btn-next-1" class="btn btn--primary btn--lg" disabled>
          <?= __('book.next_date') ?>
        </button>
      </div>
    <?php endif; ?>
  </div>

  <!-- STEP 2: Select date & time -->
  <div class="booking-panel" id="panel-2">
    <h2 style="margin-bottom:20px;font-size:1.25rem"><?= __('book.select_datetime') ?></h2>
    <div class="datetime-grid">
      <div class="mini-cal">
        <div class="mini-cal__nav">
          <div id="cal-month-title" style="font-size:.95rem;font-weight:700"></div>
          <div style="display:flex;gap:6px">
            <button id="cal-prev" class="mini-cal__btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,18 9,12 15,6"/></svg>
            </button>
            <button id="cal-next" class="mini-cal__btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9,18 15,12 9,6"/></svg>
            </button>
          </div>
        </div>
        <table class="mini-cal__grid">
          <thead>
            <tr>
              <?php foreach ($calDays as $d): ?>
                <th><?= e($d) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody id="cal-grid-body"></tbody>
        </table>
      </div>

      <div class="time-slots">
        <div class="time-slots__title"><?= __('book.available_times') ?></div>
        <div class="time-slots__date" id="time-slots-date"><?= __('book.pick_date') ?></div>
        <div id="time-slots-grid">
          <div class="time-slots__empty"><?= __('book.times_empty') ?></div>
        </div>
      </div>
    </div>
    <div class="booking-nav">
      <button id="btn-back-2" class="btn btn--ghost">← <?= __('common.back') ?></button>
      <button id="btn-next-2" class="btn btn--primary btn--lg" disabled>
        <?= __('book.enter_contact') ?>
      </button>
    </div>
  </div>

  <!-- STEP 3: Contact details -->
  <div class="booking-panel" id="panel-3">
    <h2 style="margin-bottom:20px;font-size:1.25rem"><?= __('book.contact_title') ?></h2>
    <div class="booking-form-card">
      <div class="booking-summary">
        <div class="booking-summary__title"><?= __('book.summary_title') ?></div>
        <div class="booking-summary__row">
          <span class="booking-summary__label"><?= __('book.sum_service') ?></span>
          <span class="booking-summary__value" id="sum-service"></span>
        </div>
        <div class="booking-summary__row">
          <span class="booking-summary__label"><?= __('book.sum_date') ?></span>
          <span class="booking-summary__value" id="sum-date"></span>
        </div>
        <div class="booking-summary__row">
          <span class="booking-summary__label"><?= __('book.sum_time') ?></span>
          <span class="booking-summary__value" id="sum-time"></span>
        </div>
        <div class="booking-summary__row" id="sum-price-row">
          <span class="booking-summary__label"><?= __('book.sum_price') ?></span>
          <span class="booking-summary__value" id="sum-price" style="color:var(--accent);font-weight:700"></span>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label"><?= __('book.full_name') ?> <span>*</span></label>
          <input type="text" id="f-name" class="form-control" placeholder="<?= __('book.full_name_ph') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label"><?= __('common.phone') ?> <span>*</span></label>
          <input type="tel" id="f-phone" class="form-control" placeholder="<?= __('book.phone_ph') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label"><?= __('common.email') ?> <span>*</span></label>
        <input type="email" id="f-email" class="form-control" placeholder="<?= currentLang() === 'en' ? 'your@email.com' : 'vas@email.cz' ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label"><?= __('book.note_opt') ?></label>
        <textarea id="f-notes" class="form-control" rows="3" placeholder="<?= __('book.note_ph') ?>"></textarea>
      </div>
      <input type="hidden" id="f-csrf" value="<?= e($csrfTok) ?>">

      <div class="booking-nav">
        <button id="btn-back-3" class="btn btn--ghost">← <?= __('common.back') ?></button>
        <button id="btn-submit" class="btn btn--primary btn--lg">
          <?= __('book.confirm_btn') ?>
        </button>
      </div>
    </div>
  </div>

  <!-- STEP 4: Confirmation -->
  <div class="booking-panel" id="panel-4">
    <div class="booking-confirm">
      <div class="booking-confirm__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>
      </div>
      <h2 class="booking-confirm__title"><?= __('book.confirmed_title') ?></h2>
      <p class="booking-confirm__sub" id="conf-thanks">
        <?= str_replace(':name', '<strong id="conf-name"></strong>', __('book.confirmed_thanks', ['name' => ':name'])) ?>
      </p>
      <div class="booking-confirm__details">
        <div class="booking-summary">
          <div class="booking-summary__title"><?= __('book.your_booking') ?></div>
          <div class="booking-summary__row">
            <span class="booking-summary__label"><?= __('book.sum_business') ?></span>
            <span class="booking-summary__value"><?= $bname ?></span>
          </div>
          <div class="booking-summary__row">
            <span class="booking-summary__label"><?= __('book.sum_service') ?></span>
            <span class="booking-summary__value" id="conf-service"></span>
          </div>
          <div class="booking-summary__row">
            <span class="booking-summary__label"><?= __('book.sum_date') ?></span>
            <span class="booking-summary__value" id="conf-date"></span>
          </div>
          <div class="booking-summary__row">
            <span class="booking-summary__label"><?= __('book.sum_time') ?></span>
            <span class="booking-summary__value" id="conf-time"></span>
          </div>
          <?php if (!empty($business['address'])): ?>
            <div class="booking-summary__row">
              <span class="booking-summary__label"><?= __('book.sum_address') ?></span>
              <span class="booking-summary__value"><?= e($business['address']) ?></span>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php if (!empty($business['phone'])): ?>
        <p style="color:#64748b;font-size:.875rem">
          <?= __('book.change_time') ?> <a href="tel:<?= e($business['phone']) ?>" style="color:var(--accent)"><?= e($business['phone']) ?></a>
        </p>
      <?php endif; ?>
      <button onclick="location.reload()" class="btn btn--outline" style="margin-top:16px"><?= __('book.new_booking') ?></button>
    </div>
  </div>

</main>

<footer style="text-align:center;padding:20px;font-size:.75rem;color:#94a3b8;border-top:1px solid #e2e8f0">
  <?= __('book.powered_by') ?> <a href="/" style="color:#94a3b8"><?= e($pname) ?></a>
</footer>

<script src="/assets/js/booking.js"></script>
</body>
</html>
