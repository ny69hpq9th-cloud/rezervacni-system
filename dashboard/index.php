<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$pageTitle = __('nav.overview');
$activeNav = 'home';

$db   = getDB();
$user = getCurrentUser();
$uid  = $user['id'];
$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd   = date('Y-m-d', strtotime('sunday this week'));
$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');

// Today's bookings
$stmt = $db->prepare(
    "SELECT b.*, s.name as service_name FROM bookings b
     LEFT JOIN services s ON b.service_id = s.id
     WHERE b.user_id = ? AND b.date = ? AND b.status != 'cancelled'
     ORDER BY b.time"
);
$stmt->execute([$uid, $today]);
$todayBookings = $stmt->fetchAll();

// Upcoming this week (excluding today)
$stmt = $db->prepare(
    "SELECT b.*, s.name as service_name FROM bookings b
     LEFT JOIN services s ON b.service_id = s.id
     WHERE b.user_id = ? AND b.date > ? AND b.date <= ? AND b.status != 'cancelled'
     ORDER BY b.date, b.time LIMIT 10"
);
$stmt->execute([$uid, $today, $weekEnd]);
$upcomingBookings = $stmt->fetchAll();

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status != 'cancelled'");
$stmt->execute([$uid]);
$totalBookings = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND date >= ? AND date <= ? AND status != 'cancelled'");
$stmt->execute([$uid, $monthStart, $monthEnd]);
$monthBookings = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(DISTINCT customer_email) FROM bookings WHERE user_id = ?");
$stmt->execute([$uid]);
$totalCustomers = $stmt->fetchColumn();

// Revenue this month
$stmt = $db->prepare(
    "SELECT COALESCE(SUM(s.price),0) FROM bookings b
     LEFT JOIN services s ON b.service_id = s.id
     WHERE b.user_id = ? AND b.date >= ? AND b.date <= ? AND b.status NOT IN ('cancelled')"
);
$stmt->execute([$uid, $monthStart, $monthEnd]);
$monthRevenue = (float)$stmt->fetchColumn();

require __DIR__ . '/_layout.php';
?>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div>
      <div class="stat-card__label"><?= __('dash.stat_today') ?></div>
      <div class="stat-card__value"><?= count($todayBookings) ?></div>
      <div class="stat-card__sub"><?= __('dash.stat_today_sub') ?></div>
    </div>
    <div class="stat-card__icon stat-card__icon--blue">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    </div>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-card__label"><?= __('dash.stat_month') ?></div>
      <div class="stat-card__value"><?= $monthBookings ?></div>
      <div class="stat-card__sub"><?= __('dash.stat_month_sub') ?></div>
    </div>
    <div class="stat-card__icon stat-card__icon--green">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/><polyline points="17,6 23,6 23,12"/></svg>
    </div>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-card__label"><?= __('dash.stat_customers') ?></div>
      <div class="stat-card__value"><?= $totalCustomers ?></div>
      <div class="stat-card__sub"><?= __('dash.stat_cust_sub') ?></div>
    </div>
    <div class="stat-card__icon stat-card__icon--purple">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
    </div>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-card__label"><?= __('dash.stat_revenue') ?></div>
      <div class="stat-card__value"><?= number_format($monthRevenue, 0, ',', ' ') ?></div>
      <div class="stat-card__sub"><?= langCurrencySymbol() ?> <?= __('dash.stat_rev_sub') ?></div>
    </div>
    <div class="stat-card__icon stat-card__icon--yellow">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

  <!-- Today's bookings -->
  <div class="dash-card">
    <div class="dash-card__header">
      <div class="dash-card__title"><?= __('dash.today_bkgs') ?></div>
      <a href="/dashboard/bookings.php?filter=today" class="btn btn--ghost btn--sm"><?= __('common.all') ?> →</a>
    </div>
    <?php if (empty($todayBookings)): ?>
      <div style="padding:32px;text-align:center;color:#94a3b8">
        <div style="font-size:2rem;margin-bottom:8px">📅</div>
        <div style="font-size:.875rem"><?= __('dash.no_today') ?></div>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead><tr>
          <th><?= __('common.customer') ?></th>
          <th><?= __('common.service') ?></th>
          <th><?= __('common.time') ?></th>
          <th><?= __('common.status') ?></th>
        </tr></thead>
        <tbody>
          <?php foreach ($todayBookings as $b): ?>
            <tr>
              <td>
                <div style="font-weight:500"><?= e($b['customer_name']) ?></div>
                <div style="font-size:.8rem;color:#64748b"><?= e($b['customer_phone']) ?></div>
              </td>
              <td><?= e($b['service_name'] ?? '—') ?></td>
              <td style="font-weight:600"><?= formatTime($b['time']) ?></td>
              <td><span class="badge <?= statusClass($b['status']) ?>"><?= statusLabel($b['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- Upcoming this week -->
  <div class="dash-card">
    <div class="dash-card__header">
      <div class="dash-card__title"><?= __('dash.upcoming_week') ?></div>
      <a href="/dashboard/bookings.php" class="btn btn--ghost btn--sm"><?= __('common.all') ?> →</a>
    </div>
    <?php if (empty($upcomingBookings)): ?>
      <div style="padding:32px;text-align:center;color:#94a3b8">
        <div style="font-size:2rem;margin-bottom:8px">🎉</div>
        <div style="font-size:.875rem"><?= __('dash.no_upcoming') ?></div>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead><tr>
          <th><?= __('common.customer') ?></th>
          <th><?= __('common.date') ?></th>
          <th><?= __('common.time') ?></th>
          <th><?= __('common.service') ?></th>
        </tr></thead>
        <tbody>
          <?php foreach ($upcomingBookings as $b): ?>
            <tr>
              <td style="font-weight:500"><?= e($b['customer_name']) ?></td>
              <td><?= formatDate($b['date']) ?></td>
              <td style="font-weight:600"><?= formatTime($b['time']) ?></td>
              <td style="color:#64748b;font-size:.875rem"><?= e($b['service_name'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<!-- Quick actions -->
<div class="dash-card">
  <div class="dash-card__header">
    <div class="dash-card__title"><?= __('dash.quick_actions') ?></div>
  </div>
  <div class="dash-card__body" style="display:flex;gap:12px;flex-wrap:wrap">
    <a href="/dashboard/services.php" class="btn btn--outline btn--sm"><?= __('dash.add_service') ?></a>
    <a href="/dashboard/hours.php" class="btn btn--outline btn--sm"><?= __('dash.edit_hours') ?></a>
    <a href="/dashboard/hours.php#block" class="btn btn--outline btn--sm"><?= __('dash.block_date') ?></a>
    <a href="/rezervace/<?= e($user['slug']) ?>" target="_blank" class="btn btn--outline btn--sm"><?= __('dash.my_page_icon') ?></a>
    <button onclick="copyLink()" class="btn btn--outline btn--sm">📋 <?= __('common.copy_link') ?></button>
  </div>
</div>

<script>
function copyLink() {
  navigator.clipboard.writeText('<?= PLATFORM_URL . '/rezervace/' . e($user['slug']) ?>').then(() => {
    alert('<?= __('common.link_copied') ?>');
  });
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
