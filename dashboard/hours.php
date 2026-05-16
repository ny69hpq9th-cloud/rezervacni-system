<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$pageTitle = __('nav.hours');
$activeNav = 'hours';

$db  = getDB();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flash('error', __('dash.err_token'));
        header('Location: /dashboard/hours.php'); exit;
    }

    $action = $_POST['action'] ?? 'hours';

    if ($action === 'hours') {
        for ($day = 0; $day <= 6; $day++) {
            $isWorking  = isset($_POST["working_$day"]) ? 1 : 0;
            $startTime  = $_POST["start_$day"] ?? '09:00';
            $endTime    = $_POST["end_$day"]   ?? '17:00';

            if (!preg_match('/^\d{2}:\d{2}$/', $startTime)) $startTime = '09:00';
            if (!preg_match('/^\d{2}:\d{2}$/', $endTime))   $endTime   = '17:00';

            $db->prepare(
                "INSERT INTO working_hours (user_id,day_of_week,is_working,start_time,end_time)
                 VALUES (?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE is_working=VALUES(is_working),start_time=VALUES(start_time),end_time=VALUES(end_time)"
            )->execute([$uid, $day, $isWorking, $startTime.':00', $endTime.':00']);
        }
        flash('success', __('dash.hours_saved'));
    } elseif ($action === 'block') {
        $date   = $_POST['block_date'] ?? '';
        $reason = trim($_POST['block_reason'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            flash('error', __('dash.err_date'));
        } elseif (strtotime($date) < strtotime('today')) {
            flash('error', __('dash.err_past'));
        } else {
            $chk = $db->prepare("SELECT id FROM blocked_dates WHERE user_id=? AND date=?");
            $chk->execute([$uid,$date]);
            if ($chk->fetch()) {
                flash('warning', __('dash.already_blocked'));
            } else {
                $db->prepare("INSERT INTO blocked_dates (user_id,date,reason) VALUES (?,?,?)")
                   ->execute([$uid,$date,$reason?:null]);
                flash('success', __('dash.date_blocked', ['date' => formatDate($date)]));
            }
        }
    } elseif ($action === 'unblock') {
        $id = (int)$_POST['block_id'];
        $db->prepare("DELETE FROM blocked_dates WHERE id=? AND user_id=?")->execute([$id,$uid]);
        flash('success', __('dash.date_unblocked'));
    }

    header('Location: /dashboard/hours.php'); exit;
}

// Load working hours
$stmt = $db->prepare("SELECT * FROM working_hours WHERE user_id=? ORDER BY day_of_week");
$stmt->execute([$uid]);
$hours = [];
foreach ($stmt->fetchAll() as $h) { $hours[$h['day_of_week']] = $h; }

// Load blocked dates (future only)
$stmt = $db->prepare("SELECT * FROM blocked_dates WHERE user_id=? AND date >= CURDATE() ORDER BY date");
$stmt->execute([$uid]);
$blocked = $stmt->fetchAll();

require __DIR__ . '/_layout.php';
?>

<!-- Working hours -->
<div class="dash-card" style="margin-bottom:24px">
  <div class="dash-card__header">
    <div class="dash-card__title"><?= __('dash.hours_title') ?></div>
  </div>
  <div class="dash-card__body">
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="hours">
      <div class="hours-table">
        <?php for ($day = 1; $day <= 7; $day++): $d = $day % 7; // Mon=1..Sun=0
          $h = $hours[$d] ?? null;
          $isWorking = $h ? (bool)$h['is_working'] : ($d >= 1 && $d <= 5);
          $start = $h ? substr($h['start_time'], 0, 5) : '09:00';
          $end   = $h ? substr($h['end_time'],   0, 5) : '17:00';
        ?>
          <div class="hours-row">
            <div class="hours-row__day"><?= getDayName($d) ?></div>
            <div>
              <label class="toggle" style="margin-bottom:0">
                <input type="checkbox" name="working_<?= $d ?>" class="toggle__input"
                       id="tog<?= $d ?>" <?= $isWorking ? 'checked' : '' ?>
                       onchange="toggleDay(<?= $d ?>)">
                <span class="toggle__slider"></span>
                <span class="toggle__label" id="tog-label-<?= $d ?>"><?= $isWorking ? __('day.working') : __('day.off') ?></span>
              </label>
            </div>
            <div class="hours-row__times" id="hours-<?= $d ?>" style="<?= $isWorking ? '' : 'opacity:.4;pointer-events:none' ?>">
              <input type="time" name="start_<?= $d ?>" class="form-control"
                     value="<?= e($start) ?>" step="900">
              <span>–</span>
              <input type="time" name="end_<?= $d ?>" class="form-control"
                     value="<?= e($end) ?>" step="900">
            </div>
          </div>
        <?php endfor; ?>
      </div>
      <div style="margin-top:20px">
        <button type="submit" class="btn btn--primary"><?= __('dash.save_hours') ?></button>
      </div>
    </form>
  </div>
</div>

<!-- Blocked dates -->
<div class="dash-card" id="block">
  <div class="dash-card__header">
    <div class="dash-card__title"><?= __('dash.blocked_dates') ?></div>
  </div>
  <div class="dash-card__body">
    <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="block">
      <div class="form-group" style="margin:0;flex:0 0 auto">
        <label class="form-label"><?= __('dash.block_date_lbl') ?></label>
        <input type="date" name="block_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:200px">
        <label class="form-label"><?= __('dash.block_reason') ?></label>
        <input type="text" name="block_reason" class="form-control" placeholder="<?= __('dash.block_reason_ph') ?>">
      </div>
      <div style="padding-top:24px">
        <button type="submit" class="btn btn--primary"><?= __('dash.block_btn') ?></button>
      </div>
    </form>

    <?php if (empty($blocked)): ?>
      <div style="text-align:center;padding:24px;color:#94a3b8;font-size:.875rem"><?= __('dash.no_blocked') ?></div>
    <?php else: ?>
      <table class="data-table">
        <thead><tr>
          <th><?= __('common.date') ?></th>
          <th><?= __('dash.reason_col') ?></th>
          <th><?= __('common.actions') ?></th>
        </tr></thead>
        <tbody>
          <?php foreach ($blocked as $b): ?>
            <tr>
              <td style="font-weight:600"><?= formatDate($b['date']) ?></td>
              <td style="color:#64748b"><?= e($b['reason'] ?? '—') ?></td>
              <td>
                <form method="POST">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="unblock">
                  <input type="hidden" name="block_id" value="<?= $b['id'] ?>">
                  <button class="btn btn--ghost btn--sm"><?= __('dash.unblock') ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script>
const dayWorking = <?= json_encode(__('day.working')) ?>;
const dayOff     = <?= json_encode(__('day.off')) ?>;
function toggleDay(day) {
  const checked = document.getElementById('tog' + day).checked;
  const row     = document.getElementById('hours-' + day);
  const label   = document.getElementById('tog-label-' + day);
  row.style.opacity = checked ? '1' : '.4';
  row.style.pointerEvents = checked ? '' : 'none';
  label.textContent = checked ? dayWorking : dayOff;
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
