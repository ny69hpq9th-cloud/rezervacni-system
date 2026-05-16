<?php
// Error reporting — zapnout na dobu ladění, pak odebrat/zakomentovat
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

requireLogin();
$pageTitle = __('nav.bookings');
$activeNav = 'bookings';

$db  = getDB();
$uid = $_SESSION['user_id'];

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flash('error', __('dash.err_token'));
    } else {
        $bId    = (int)($_POST['booking_id'] ?? 0);
        $action = $_POST['action'];

        $chk = $db->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
        $chk->execute([$bId, $uid]);
        $booking = $chk->fetch();

        if ($booking) {
            if ($action === 'cancel') {
                $db->prepare("UPDATE bookings SET status='cancelled' WHERE id=?")->execute([$bId]);
                $stmtB = $db->prepare("SELECT * FROM bookings WHERE id=?");
                $stmtB->execute([$bId]);
                $bFull = $stmtB->fetch();
                $svc   = null;
                if ($bFull && !empty($bFull['service_id'])) {
                    $stmtS = $db->prepare("SELECT * FROM services WHERE id=?");
                    $stmtS->execute([(int)$bFull['service_id']]);
                    $svc = $stmtS->fetch() ?: null;
                }
                $biz = getCurrentUser();
                if ($bFull && $svc) {
                    try { emailCancellation($bFull, $svc, $biz); } catch(\Exception $e){}
                }
                flash('success', __('dash.cancel_done'));
            } elseif (in_array($action, ['confirm', 'complete', 'pending'])) {
                $map = ['confirm'=>'confirmed','complete'=>'completed','pending'=>'pending'];
                $db->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$map[$action], $bId]);
                flash('success', __('dash.confirm_done'));
            }
        }
    }
    header('Location: /dashboard/bookings.php');
    exit;
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$today = date('Y-m-d');
$where = "b.user_id = $uid";
$params = [];

if ($filter === 'today')  $where .= " AND b.date = '$today'";
if ($filter === 'week')   $where .= " AND b.date >= '" . date('Y-m-d', strtotime('monday this week')) . "' AND b.date <= '" . date('Y-m-d', strtotime('sunday this week')) . "'";
if ($filter === 'month')  $where .= " AND b.date >= '" . date('Y-m-01') . "' AND b.date <= '" . date('Y-m-t') . "'";

if ($status) {
    $safeStatus = in_array($status, ['pending','confirmed','cancelled','completed']) ? $status : '';
    if ($safeStatus) $where .= " AND b.status = '$safeStatus'";
}
if ($search) {
    $s = $db->quote('%' . $search . '%');
    $where .= " AND (b.customer_name LIKE $s OR b.customer_email LIKE $s OR b.customer_phone LIKE $s)";
}

$stmt = $db->query(
    "SELECT b.*, s.name as service_name, s.price as service_price
     FROM bookings b LEFT JOIN services s ON b.service_id = s.id
     WHERE $where ORDER BY b.date DESC, b.time DESC LIMIT 200"
);
$bookings = $stmt->fetchAll();

// Get booking for modal
$viewId      = (int)($_GET['view'] ?? 0);
$viewBooking = null;
if ($viewId) {
    $vs = $db->prepare("SELECT b.*,s.name as svc_name,s.duration,s.price FROM bookings b LEFT JOIN services s ON b.service_id=s.id WHERE b.id=? AND b.user_id=?");
    $vs->execute([$viewId, $uid]);
    $viewBooking = $vs->fetch();
}

require __DIR__ . '/_layout.php';
?>

<!-- Filters -->
<div class="dash-card">
  <div class="dash-card__body">
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
      <div class="view-tabs">
        <?php
        $tabs = [
            'all'   => __('common.all'),
            'today' => __('common.today'),
            'week'  => __('common.week'),
            'month' => __('common.month'),
        ];
        foreach ($tabs as $k => $v):
        ?>
          <button class="view-tab <?= $filter===$k?'active':'' ?>"
            onclick="location.href='?filter=<?= $k ?><?= $status?"&status=$status":'' ?><?= $search?"&search=".urlencode($search):'' ?>'">
            <?= $v ?>
          </button>
        <?php endforeach; ?>
      </div>
      <form method="GET" style="display:flex;gap:8px;flex:1">
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <select name="status" class="form-control" style="width:auto" onchange="this.form.submit()">
          <option value=""><?= __('common.all_statuses') ?></option>
          <option value="pending"   <?= $status==='pending'?'selected':'' ?>><?= __('status.pending') ?></option>
          <option value="confirmed" <?= $status==='confirmed'?'selected':'' ?>><?= __('status.confirmed') ?></option>
          <option value="completed" <?= $status==='completed'?'selected':'' ?>><?= __('status.completed') ?></option>
          <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>><?= __('status.cancelled') ?></option>
        </select>
        <input type="text" name="search" value="<?= e($search) ?>" class="form-control" placeholder="<?= __('common.search_customer') ?>" style="min-width:200px">
        <button type="submit" class="btn btn--primary btn--sm"><?= __('common.search') ?></button>
        <?php if ($search || $status): ?>
          <a href="?filter=<?= e($filter) ?>" class="btn btn--ghost btn--sm"><?= __('common.clear') ?></a>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>

<!-- Bookings table -->
<div class="dash-card">
  <div class="dash-card__header">
    <div class="dash-card__title"><?= __('dash.bookings_title') ?> (<?= count($bookings) ?>)</div>
  </div>
  <?php if (empty($bookings)): ?>
    <div class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <h3><?= __('dash.no_bookings') ?></h3>
      <p><?= __('dash.no_bkgs_sub') ?></p>
      <a href="/rezervace/<?= e($_SESSION['user_id'] ? $db->query("SELECT slug FROM users WHERE id=" . (int)$uid)->fetchColumn() : '') ?>" class="btn btn--primary"><?= __('dash.view_my_page') ?></a>
    </div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th><?= __('common.customer') ?></th>
          <th><?= __('dash.contact') ?></th>
          <th><?= __('common.service') ?></th>
          <th><?= __('dash.datetime') ?></th>
          <th><?= __('common.status') ?></th>
          <th><?= __('common.actions') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td>
              <div style="font-weight:600"><?= e($b['customer_name']) ?></div>
              <?php if ($b['notes']): ?>
                <div style="font-size:.75rem;color:#94a3b8;margin-top:2px" title="<?= e($b['notes']) ?>">💬 <?= __('common.note') ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-size:.875rem"><?= e($b['customer_email']) ?></div>
              <div style="font-size:.8rem;color:#64748b"><?= e($b['customer_phone']) ?></div>
            </td>
            <td><?= e($b['service_name'] ?? '—') ?></td>
            <td>
              <div style="font-weight:500"><?= formatDate($b['date']) ?></div>
              <div style="font-size:.875rem;color:#64748b"><?= formatTime($b['time']) ?></div>
            </td>
            <td><span class="badge <?= statusClass($b['status']) ?>"><?= statusLabel($b['status']) ?></span></td>
            <td>
              <div style="display:flex;gap:6px;align-items:center">
                <a href="?filter=<?= e($filter) ?>&view=<?= $b['id'] ?>" class="btn btn--ghost btn--sm"><?= __('common.detail') ?></a>
                <?php if ($b['status'] !== 'cancelled'): ?>
                  <form method="POST" style="display:inline" onsubmit="return confirm('<?= __('dash.cancel_confirm') ?>')">
                    <?= csrfField() ?>
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button class="btn btn--danger btn--sm"><?= __('dash.cancel_booking') ?></button>
                  </form>
                <?php endif; ?>
                <?php if ($b['status'] === 'pending'): ?>
                  <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <input type="hidden" name="action" value="confirm">
                    <button class="btn btn--success btn--sm"><?= __('dash.confirm') ?></button>
                  </form>
                <?php elseif ($b['status'] === 'confirmed'): ?>
                  <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <input type="hidden" name="action" value="complete">
                    <button class="btn btn--sm" style="background:#8b5cf6;color:#fff;border-color:#8b5cf6"><?= __('dash.complete') ?></button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- Detail modal -->
<?php if ($viewBooking): ?>
<div class="modal-overlay open" id="booking-modal">
  <div class="modal">
    <div class="modal__header">
      <div class="modal__title"><?= __('dash.booking_detail', ['id' => $viewBooking['id']]) ?></div>
      <button class="modal__close" onclick="document.getElementById('booking-modal').classList.remove('open')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal__body">
      <div class="detail-row"><div class="detail-row__label"><?= __('common.customer') ?></div><div class="detail-row__value"><?= e($viewBooking['customer_name']) ?></div></div>
      <div class="detail-row"><div class="detail-row__label"><?= __('common.email') ?></div><div class="detail-row__value"><a href="mailto:<?= e($viewBooking['customer_email']) ?>"><?= e($viewBooking['customer_email']) ?></a></div></div>
      <div class="detail-row"><div class="detail-row__label"><?= __('common.phone') ?></div><div class="detail-row__value"><a href="tel:<?= e($viewBooking['customer_phone']) ?>"><?= e($viewBooking['customer_phone']) ?></a></div></div>
      <div class="detail-row"><div class="detail-row__label"><?= __('common.service') ?></div><div class="detail-row__value"><?= e($viewBooking['svc_name'] ?? '—') ?></div></div>
      <div class="detail-row"><div class="detail-row__label"><?= __('common.date') ?></div><div class="detail-row__value"><?= formatDate($viewBooking['date']) ?></div></div>
      <div class="detail-row"><div class="detail-row__label"><?= __('common.time') ?></div><div class="detail-row__value"><?= formatTime($viewBooking['time']) ?></div></div>
      <?php if ($viewBooking['duration']): ?>
        <div class="detail-row"><div class="detail-row__label"><?= __('dash.length') ?></div><div class="detail-row__value"><?= formatDuration((int)$viewBooking['duration']) ?></div></div>
      <?php endif; ?>
      <?php if ($viewBooking['price']): ?>
        <div class="detail-row"><div class="detail-row__label"><?= __('common.price') ?></div><div class="detail-row__value" style="color:#2563eb;font-weight:700"><?= formatPrice((float)$viewBooking['price']) ?></div></div>
      <?php endif; ?>
      <div class="detail-row"><div class="detail-row__label"><?= __('common.status') ?></div><div class="detail-row__value"><span class="badge <?= statusClass($viewBooking['status']) ?>"><?= statusLabel($viewBooking['status']) ?></span></div></div>
      <?php if ($viewBooking['notes']): ?>
        <div class="detail-row"><div class="detail-row__label"><?= __('common.note') ?></div><div class="detail-row__value" style="font-style:italic"><?= e($viewBooking['notes']) ?></div></div>
      <?php endif; ?>
      <div class="detail-row"><div class="detail-row__label"><?= __('dash.created') ?></div><div class="detail-row__value" style="color:#94a3b8;font-size:.85rem"><?= formatDate($viewBooking['created_at']) ?></div></div>
    </div>
    <div class="modal__footer">
      <a href="?filter=<?= e($filter) ?>" class="btn btn--ghost btn--sm"><?= __('dash.close_modal') ?></a>
      <?php if ($viewBooking['status'] !== 'cancelled'): ?>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="booking_id" value="<?= $viewBooking['id'] ?>">
          <input type="hidden" name="action" value="cancel">
          <button class="btn btn--danger btn--sm" onclick="return confirm('<?= __('dash.cancel_confirm') ?>')"><?= __('dash.cancel_bkg') ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
