<?php
// This file is included by dashboard pages after they set:
// $pageTitle, $activeNav
// and after requiring config + auth + db + functions

$user       = getCurrentUser();
if (!$user) { logoutUser(); }
$trialLeft  = ($user['plan'] === 'trial') ? getTrialDaysLeft($user['created_at']) : null;
$planLabel  = getPlanLabel($user['plan']);
$initials   = mb_strtoupper(mb_substr($user['business_name'], 0, 1, 'UTF-8'), 'UTF-8');

$navItems = [
    ['href' => '/dashboard/index.php',        'icon' => 'home',     'label' => __('nav.overview'),     'key' => 'home'],
    ['href' => '/dashboard/calendar.php',     'icon' => 'calendar', 'label' => __('nav.calendar'),     'key' => 'calendar'],
    ['href' => '/dashboard/bookings.php',     'icon' => 'list',     'label' => __('nav.bookings'),     'key' => 'bookings'],
    ['href' => '/dashboard/services.php',     'icon' => 'tool',     'label' => __('nav.services'),     'key' => 'services'],
    ['href' => '/dashboard/hours.php',        'icon' => 'clock',    'label' => __('nav.hours'),        'key' => 'hours'],
    ['href' => '/dashboard/settings.php',     'icon' => 'settings', 'label' => __('nav.settings'),    'key' => 'settings'],
    ['href' => '/dashboard/subscription.php', 'icon' => 'credit',   'label' => __('nav.subscription'), 'key' => 'subscription'],
];

function navIcon(string $icon): string {
    $icons = [
        'home'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'list'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
        'tool'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>',
        'clock'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
        'credit'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
    ];
    return $icons[$icon] ?? '';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlLang() ?>">
<head>
<meta charset="UTF-8">
<?= themeHeadScript() ?>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? 'Dashboard') ?> – <?= e(PLATFORM_TITLE) ?></title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="shortcut icon" href="/favicon.svg">
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/dashboard.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Mobile header -->
<div class="dash-mobile-header">
  <div class="dash-mobile-header__logo" style="display:flex;align-items:center;gap:8px"><?= logoIcon('white') ?><span style="font-weight:800"><?= e(PLATFORM_NAME) ?></span></div>
  <button class="dash-hamburger" id="sidebar-toggle" aria-label="Menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>
</div>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <a href="/dashboard/index.php" class="sidebar__logo" style="display:flex;align-items:center;gap:10px;text-decoration:none"><?= logoIcon('white') ?><span style="font-weight:800"><?= e(PLATFORM_NAME) ?></span></a>
  <nav class="sidebar__nav">
    <div class="sidebar__section-label"><?= __('nav.main_menu') ?></div>
    <?php foreach ($navItems as $item): ?>
      <a href="<?= e($item['href']) ?>" class="sidebar__link <?= ($activeNav ?? '') === $item['key'] ? 'active' : '' ?>">
        <?= navIcon($item['icon']) ?>
        <?= e($item['label']) ?>
      </a>
    <?php endforeach; ?>

    <div class="sidebar__section-label" style="margin-top:8px"><?= __('nav.booking_page') ?></div>
    <a href="/rezervace/<?= e($user['slug']) ?>" target="_blank" class="sidebar__link">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      <?= __('nav.view_page') ?>
    </a>
  </nav>
  <div class="sidebar__footer">
    <div class="sidebar__user">
      <?php if (!empty($user['logo'])): ?>
        <img src="<?= e($user['logo']) ?>"
             alt="<?= e($user['business_name']) ?>"
             class="sidebar__avatar sidebar__avatar--logo">
      <?php else: ?>
        <div class="sidebar__avatar"><?= e($initials) ?></div>
      <?php endif; ?>
      <div>
        <div class="sidebar__user-name"><?= e($user['business_name']) ?></div>
        <div class="sidebar__user-plan"><?= e($planLabel) ?></div>
      </div>
    </div>
    <a href="/logout.php" class="sidebar__logout">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      <?= __('nav.logout') ?>
    </a>
  </div>
</aside>

<!-- Main -->
<main class="dash-main">
  <div class="dash-header">
    <div>
      <div class="dash-header__title"><?= e($pageTitle ?? 'Dashboard') ?></div>
    </div>
    <div class="dash-header__actions">
      <?php if ($trialLeft !== null): ?>
        <a href="/dashboard/subscription.php" class="badge <?= $trialLeft <= 3 ? 'badge--danger' : 'badge--warning' ?>">
          <?= __('dash.trial_badge', ['n' => $trialLeft]) ?>
        </a>
      <?php endif; ?>
      <?= langSwitcher() ?>
      <?= themeToggle() ?>
      <a href="/rezervace/<?= e($user['slug']) ?>" target="_blank" class="btn btn--outline btn--sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        <?= __('nav.my_page') ?>
      </a>
    </div>
  </div>

  <div class="dash-content">
    <?php renderFlash(); ?>
    <?php if ($trialLeft !== null && $trialLeft <= 3): ?>
      <div class="trial-bar" style="margin-bottom:20px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <p><?= __('dash.trial_bar', ['n' => $trialLeft, 'word' => trialDaysWord($trialLeft)]) ?></p>
      </div>
    <?php endif; ?>
