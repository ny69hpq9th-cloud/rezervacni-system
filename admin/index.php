<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flash('error', __('admin.err_token'));
    } else {
        $action = $_POST['action'] ?? '';
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($action === 'suspend' && $userId) {
            $db->prepare("UPDATE users SET status='suspended' WHERE id=?")->execute([$userId]);
            flash('success', __('admin.suspended_ok'));
        } elseif ($action === 'activate' && $userId) {
            $db->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$userId]);
            flash('success', __('admin.activated_ok'));
        } elseif ($action === 'activate_plan' && $userId) {
            $plan   = $_POST['plan'] ?? 'basic';
            $months = (int)($_POST['months'] ?? 1);
            if (!in_array($plan, ['basic','pro'])) $plan = 'basic';
            $amount  = ($plan === 'pro') ? PLAN_PRO_PRICE : PLAN_BASIC_PRICE;
            $expires = date('Y-m-d H:i:s', strtotime("+$months months"));

            $db->prepare("UPDATE users SET plan=? WHERE id=?")->execute([$plan, $userId]);
            $db->prepare("UPDATE subscriptions SET status='expired' WHERE user_id=? AND status='active'")->execute([$userId]);
            $db->prepare("INSERT INTO subscriptions (user_id,plan,status,amount,expires_at) VALUES (?,?,'active',?,?)")
               ->execute([$userId,$plan,$amount*$months,$expires]);
            flash('success', __('admin.plan_activated'));
        }
    }
    header('Location: /admin/index.php'); exit;
}

// Stats
$totalUsers   = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers  = $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$trialUsers   = $db->query("SELECT COUNT(*) FROM users WHERE plan='trial'")->fetchColumn();
$totalBooks   = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$monthBooks   = $db->query("SELECT COUNT(*) FROM bookings WHERE date >= '" . date('Y-m-01') . "'")->fetchColumn();
$activeSubs   = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status='active' AND expires_at > NOW()")->fetchColumn();
$monthRevenue = $db->query(
    "SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE status='active' AND created_at >= '" . date('Y-m-01') . "'"
)->fetchColumn();

// Users list
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$where  = '1=1';
if ($filter === 'trial')     $where .= " AND u.plan='trial'";
if ($filter === 'paid')      $where .= " AND u.plan IN ('basic','pro')";
if ($filter === 'suspended') $where .= " AND u.status='suspended'";
if ($search) {
    $s    = $db->quote('%' . $search . '%');
    $where .= " AND (u.business_name LIKE $s OR u.email LIKE $s)";
}

$users = $db->query(
    "SELECT u.*,
     (SELECT COUNT(*) FROM bookings b WHERE b.user_id=u.id) as total_bookings,
     (SELECT COUNT(*) FROM bookings b WHERE b.user_id=u.id AND b.date >= '" . date('Y-m-01') . "') as month_bookings,
     (SELECT expires_at FROM subscriptions s WHERE s.user_id=u.id AND s.status='active' AND s.expires_at>NOW() ORDER BY expires_at DESC LIMIT 1) as sub_expires
     FROM users u WHERE $where ORDER BY u.created_at DESC"
)->fetchAll();

$pname = PLATFORM_NAME;
?>
<!DOCTYPE html>
<html lang="<?= htmlLang() ?>">
<head>
<meta charset="UTF-8">
<?= themeHeadScript() ?>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('admin.panel_title') ?> – <?= e(PLATFORM_TITLE) ?></title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="shortcut icon" href="/favicon.svg">
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/dashboard.css">
<link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="dash-mobile-header">
  <div class="dash-mobile-header__logo" style="display:flex;align-items:center;gap:8px"><?= logoIcon('white') ?><span style="font-weight:800"><?= e($pname) ?></span><span style="font-size:.7rem;background:#ef4444;color:#fff;padding:1px 6px;border-radius:3px">ADMIN</span></div>
  <button class="dash-hamburger" id="sidebar-toggle">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>
</div>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar__logo" style="display:flex;align-items:center;gap:10px"><?= logoIcon('white') ?><span style="font-weight:800"><?= e($pname) ?></span><small style="font-size:.65rem;color:#ef4444;background:rgba(239,68,68,.15);padding:2px 6px;border-radius:4px">ADMIN</small></div>
  <nav class="sidebar__nav">
    <div class="sidebar__section-label"><?= __('admin.admin_menu') ?></div>
    <a href="/admin/index.php" class="sidebar__link active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      <?= __('admin.admin_overview') ?>
    </a>
    <a href="/" class="sidebar__link" target="_blank">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      <?= __('admin.main_site') ?>
    </a>
  </nav>
  <div class="sidebar__footer">
    <div class="sidebar__user">
      <div class="sidebar__avatar" style="background:linear-gradient(135deg,#ef4444,#dc2626)">A</div>
      <div>
        <div class="sidebar__user-name"><?= __('admin.administrator') ?></div>
        <div class="sidebar__user-plan"><?= e(ADMIN_EMAIL) ?></div>
      </div>
    </div>
    <a href="/admin/logout.php" class="sidebar__logout">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      <?= __('nav.logout') ?>
    </a>
  </div>
</aside>

<main class="dash-main">
  <div class="dash-header">
    <div class="dash-header__title"><?= __('admin.panel_title') ?></div>
    <div class="dash-header__actions"><?= langSwitcher() ?></div>
  </div>
  <div class="dash-content">
    <?php renderFlash(); ?>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom:24px">
      <div class="stat-card">
        <div>
          <div class="stat-card__label"><?= __('admin.stat_businesses') ?></div>
          <div class="stat-card__value"><?= $totalUsers ?></div>
          <div class="stat-card__sub"><?= __('admin.stat_biz_sub', ['n' => $activeUsers]) ?></div>
        </div>
        <div class="stat-card__icon stat-card__icon--blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div>
      </div>
      <div class="stat-card">
        <div>
          <div class="stat-card__label"><?= __('admin.stat_subs') ?></div>
          <div class="stat-card__value"><?= $activeSubs ?></div>
          <div class="stat-card__sub"><?= __('admin.stat_subs_sub', ['n' => $trialUsers]) ?></div>
        </div>
        <div class="stat-card__icon stat-card__icon--green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
      </div>
      <div class="stat-card">
        <div>
          <div class="stat-card__label"><?= __('admin.stat_bookings') ?></div>
          <div class="stat-card__value"><?= $totalBooks ?></div>
          <div class="stat-card__sub"><?= __('admin.stat_bkgs_sub', ['n' => $monthBooks]) ?></div>
        </div>
        <div class="stat-card__icon stat-card__icon--purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
      </div>
      <div class="stat-card">
        <div>
          <div class="stat-card__label"><?= __('admin.stat_revenue') ?></div>
          <div class="stat-card__value"><?= number_format((float)$monthRevenue,0,',',' ') ?></div>
          <div class="stat-card__sub"><?= __('admin.stat_rev_sub') ?></div>
        </div>
        <div class="stat-card__icon stat-card__icon--yellow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
      </div>
    </div>

    <!-- Filters -->
    <div class="dash-card" style="margin-bottom:20px">
      <div class="dash-card__body">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
          <div class="view-tabs">
            <?php
            $tabs = [
                'all'       => __('admin.filter_all'),
                'trial'     => __('admin.filter_trial'),
                'paid'      => __('admin.filter_paid'),
                'suspended' => __('admin.filter_susp'),
            ];
            foreach ($tabs as $k => $v):
            ?>
              <button class="view-tab <?= $filter===$k?'active':'' ?>"
                onclick="location.href='?filter=<?= $k ?><?= $search?"&search=".urlencode($search):'' ?>'">
                <?= $v ?>
              </button>
            <?php endforeach; ?>
          </div>
          <form method="GET" style="display:flex;gap:8px;flex:1">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <input type="text" name="search" value="<?= e($search) ?>" class="form-control" placeholder="<?= __('admin.search_ph') ?>" style="min-width:220px">
            <button type="submit" class="btn btn--primary btn--sm"><?= __('common.search') ?></button>
            <?php if ($search): ?><a href="?filter=<?= e($filter) ?>" class="btn btn--ghost btn--sm"><?= __('common.clear') ?></a><?php endif; ?>
          </form>
        </div>
      </div>
    </div>

    <!-- Users table -->
    <div class="dash-card">
      <div class="dash-card__header">
        <div class="dash-card__title"><?= __('admin.businesses') ?> (<?= count($users) ?>)</div>
      </div>
      <?php if (empty($users)): ?>
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          <h3><?= __('admin.no_businesses') ?></h3>
        </div>
      <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th><?= __('admin.col_business') ?></th>
              <th><?= __('common.email') ?></th>
              <th><?= __('admin.col_plan') ?></th>
              <th><?= __('admin.col_bookings') ?></th>
              <th><?= __('admin.col_registered') ?></th>
              <th><?= __('admin.col_status') ?></th>
              <th><?= __('admin.col_actions') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td>
                  <div style="font-weight:600"><?= e($u['business_name']) ?></div>
                  <div style="font-size:.75rem;color:#94a3b8">
                    <a href="<?= e(PLATFORM_URL . '/rezervace/' . $u['slug']) ?>" target="_blank" style="color:#64748b"><?= e($u['slug']) ?></a>
                  </div>
                </td>
                <td style="font-size:.875rem"><?= e($u['email']) ?></td>
                <td>
                  <?php
                  $planC = match($u['plan']) { 'pro'=>'badge--success','basic'=>'badge--info','trial'=>'badge--warning', default=>'badge--default' };
                  echo '<span class="badge ' . $planC . '">' . e(getPlanLabel($u['plan'])) . '</span>';
                  if ($u['sub_expires']) echo '<div style="font-size:.7rem;color:#94a3b8;margin-top:3px">' . __('admin.sub_until') . ' ' . date('j.n.Y', strtotime($u['sub_expires'])) . '</div>';
                  ?>
                </td>
                <td>
                  <div><?= $u['total_bookings'] ?> <?= __('admin.total_bookings') ?></div>
                  <div style="font-size:.8rem;color:#64748b"><?= $u['month_bookings'] ?> <?= __('admin.this_month') ?></div>
                </td>
                <td style="font-size:.875rem"><?= formatDate($u['created_at']) ?></td>
                <td>
                  <span class="badge <?= $u['status']==='active'?'badge--success':'badge--danger' ?>">
                    <?= $u['status']==='active' ? __('admin.biz_active') : __('admin.biz_suspended') ?>
                  </span>
                </td>
                <td>
                  <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <!-- Toggle status -->
                    <form method="POST" style="display:inline">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <input type="hidden" name="action" value="<?= $u['status']==='active'?'suspend':'activate' ?>">
                      <button class="btn btn--sm <?= $u['status']==='active'?'btn--danger':'btn--success' ?>"
                        onclick="return confirm('<?= $u['status']==='active' ? __('admin.suspend_confirm') : __('admin.activate_confirm') ?>')">
                        <?= $u['status']==='active' ? __('admin.suspend') : __('admin.activate') ?>
                      </button>
                    </form>
                    <!-- Activate plan -->
                    <button class="btn btn--outline btn--sm"
                      onclick="document.getElementById('modal-<?= $u['id'] ?>').classList.add('open')">
                      <?= __('admin.plan_btn') ?>
                    </button>
                  </div>
                </td>
              </tr>
              <!-- Plan modal -->
              <tr style="display:none">
                <td colspan="7">
                  <div class="modal-overlay" id="modal-<?= $u['id'] ?>">
                    <div class="modal">
                      <div class="modal__header">
                        <div class="modal__title"><?= __('admin.plan_modal', ['name' => e($u['business_name'])]) ?></div>
                        <button class="modal__close" onclick="document.getElementById('modal-<?= $u['id'] ?>').classList.remove('open')">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                      </div>
                      <div class="modal__body">
                        <form method="POST">
                          <?= csrfField() ?>
                          <input type="hidden" name="action" value="activate_plan">
                          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                          <div class="form-group">
                            <label class="form-label"><?= __('admin.plan_label') ?></label>
                            <select name="plan" class="form-control">
                              <option value="basic">Basic (<?= PLAN_BASIC_PRICE ?> Kč/<?= __('common.month') ?>)</option>
                              <option value="pro">Pro (<?= PLAN_PRO_PRICE ?> Kč/<?= __('common.month') ?>)</option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label class="form-label"><?= __('admin.months_label') ?></label>
                            <select name="months" class="form-control">
                              <option value="1">1 <?= __('common.month') ?></option>
                              <option value="3">3 <?= __('common.months') ?></option>
                              <option value="6">6 <?= __('common.months') ?></option>
                              <option value="12">12 <?= __('common.months') ?></option>
                            </select>
                          </div>
                          <button type="submit" class="btn btn--primary btn--full"><?= __('admin.activate_plan') ?></button>
                        </form>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
const toggle  = document.getElementById('sidebar-toggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebar-overlay');
toggle?.addEventListener('click', () => { sidebar?.classList.toggle('open'); overlay?.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar?.classList.remove('open'); overlay?.classList.remove('open'); });

document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }, 5000);
});
</script>
</body>
</html>
