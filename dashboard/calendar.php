<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$pageTitle = __('nav.calendar');
$activeNav = 'calendar';

$db  = getDB();
$uid = $_SESSION['user_id'];

$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));

// Clamp
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

$bookings = getMonthBookings($db, $uid, $year, $month);

// Group by date
$byDate = [];
foreach ($bookings as $b) {
    $byDate[$b['date']][] = $b;
}

require __DIR__ . '/_layout.php';

$today    = date('Y-m-d');
$firstDay = mktime(0,0,0,$month,1,$year);
$lastDay  = mktime(0,0,0,$month+1,0,$year);
$numDays  = (int)date('t', $firstDay);

// Day of week for day 1 (Monday=0)
$startDow = (int)date('N', $firstDay) - 1; // N: Mon=1,Sun=7 → 0..6
?>

<!-- Nav -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h2 style="font-size:1.25rem;font-weight:700"><?= __('month_name.' . $month) ?> <?= $year ?></h2>
  <div style="display:flex;gap:8px">
    <?php
    $pm = $month - 1; $py = $year;
    if ($pm < 1) { $pm = 12; $py--; }
    $nm = $month + 1; $ny = $year;
    if ($nm > 12) { $nm = 1;  $ny++; }
    ?>
    <a href="?year=<?= $py ?>&month=<?= $pm ?>" class="btn btn--outline btn--sm"><?= __('dash.prev_month') ?></a>
    <a href="?year=<?= date('Y') ?>&month=<?= date('n') ?>" class="btn btn--ghost btn--sm"><?= __('dash.today_btn') ?></a>
    <a href="?year=<?= $ny ?>&month=<?= $nm ?>" class="btn btn--outline btn--sm"><?= __('dash.next_month') ?></a>
  </div>
</div>

<!-- Calendar -->
<div class="dash-card">
  <div class="dash-card__body" style="padding:16px">
    <table class="cal-grid">
      <thead>
        <tr>
          <?php for ($i = 1; $i <= 7; $i++): ?>
            <th><?= getDayNameShort($i % 7) ?></th>
          <?php endfor; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        $cell = 0;
        echo '<tr>';
        for ($i = 0; $i < $startDow; $i++) {
            echo '<td><div class="cal-day other-month"></div></td>';
            $cell++;
        }
        for ($d = 1; $d <= $numDays; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $isToday = $dateStr === $today;
            $dayBkgs = $byDate[$dateStr] ?? [];
            echo '<td>';
            echo '<div class="cal-day' . ($isToday ? ' today' : '') . '">';
            echo '<div class="cal-day__num">' . $d . '</div>';
            foreach (array_slice($dayBkgs, 0, 3) as $b) {
                $cls = 'cal-event cal-event--' . $b['status'];
                echo '<div class="' . $cls . '" title="' . htmlspecialchars($b['customer_name'] . ' – ' . formatTime($b['time']), ENT_QUOTES) . '">'
                   . htmlspecialchars(formatTime($b['time']) . ' ' . $b['customer_name'], ENT_QUOTES)
                   . '</div>';
            }
            if (count($dayBkgs) > 3) {
                echo '<div style="font-size:.65rem;color:#94a3b8;padding:2px 4px">' . __('dash.more', ['n' => count($dayBkgs)-3]) . '</div>';
            }
            echo '</div></td>';
            $cell++;
            if ($cell % 7 === 0 && $d < $numDays) echo '</tr><tr>';
        }
        $rem = $cell % 7;
        if ($rem !== 0) {
            for ($i = $rem; $i < 7; $i++) {
                echo '<td><div class="cal-day other-month"></div></td>';
            }
        }
        echo '</tr>';
        ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Legend -->
<div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:16px">
  <?php
  $legend = [
      'pending'   => __('dash.legend_pending'),
      'confirmed' => __('dash.legend_conf'),
      'completed' => __('dash.legend_done'),
      'cancelled' => __('dash.legend_cancel'),
  ];
  foreach ($legend as $s => $l):
  ?>
    <div style="display:flex;align-items:center;gap:6px">
      <div class="cal-event cal-event--<?= $s ?>" style="width:14px;height:14px;border-radius:3px;flex-shrink:0"></div>
      <span style="font-size:.8rem;color:#64748b"><?= $l ?></span>
    </div>
  <?php endforeach; ?>
</div>

<!-- Monthly summary -->
<?php if (!empty($bookings)): ?>
<div class="dash-card" style="margin-top:24px">
  <div class="dash-card__header">
    <div class="dash-card__title"><?= __('nav.bookings') ?> – <?= __('month_name.' . $month) ?> <?= $year ?> (<?= count($bookings) ?>)</div>
  </div>
  <table class="data-table">
    <thead><tr>
      <th><?= __('common.date') ?></th>
      <th><?= __('common.time') ?></th>
      <th><?= __('common.customer') ?></th>
      <th><?= __('common.service') ?></th>
      <th><?= __('common.status') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td><?= formatDate($b['date']) ?></td>
          <td style="font-weight:600"><?= formatTime($b['time']) ?></td>
          <td><?= e($b['customer_name']) ?></td>
          <td style="color:#64748b"><?= e($b['service_name'] ?? '—') ?></td>
          <td><span class="badge <?= statusClass($b['status']) ?>"><?= statusLabel($b['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
