<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$pageTitle = __('nav.subscription');
$activeNav = 'subscription';

$db   = getDB();
$uid  = $_SESSION['user_id'];
$user = getCurrentUser();

// Subscription history
$stmt = $db->prepare("SELECT * FROM subscriptions WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$uid]);
$subscriptions = $stmt->fetchAll();

// Active subscription
$activeSub = null;
foreach ($subscriptions as $s) {
    if ($s['status'] === 'active' && strtotime($s['expires_at']) > time()) {
        $activeSub = $s;
        break;
    }
}

$trialEnd  = date('j. n. Y', strtotime($user['created_at']) + (TRIAL_DAYS * 86400));
$trialLeft = getTrialDaysLeft($user['created_at']);

// Check for Stripe cancel_at_period_end flag on user
$stripeCustomerId = $user['stripe_customer_id'] ?? null;
$stripeCancelsAt  = $user['stripe_cancel_at'] ?? null;

require __DIR__ . '/_layout.php';
?>

<!-- Current plan -->
<div class="dash-card" style="margin-bottom:24px">
  <div class="dash-card__header"><div class="dash-card__title"><?= __('dash.current_plan') ?></div></div>
  <div class="dash-card__body">
    <?php if ($user['plan'] === 'trial'): ?>
      <div class="plan-card plan-card--active">
        <div class="plan-badge">
          <span class="badge badge--warning"><?= __('status.trial') ?></span>
        </div>
        <div class="plan-name"><?= __('dash.trial_active') ?></div>
        <p style="color:#64748b;margin-top:8px;font-size:.9rem">
          <?php if ($trialLeft > 0): ?>
            <?= __('dash.trial_in', ['n' => $trialLeft, 'word' => trialDaysWord($trialLeft), 'date' => $trialEnd]) ?>
            <?= __('dash.trial_enjoy') ?>
          <?php else: ?>
            <?= __('dash.trial_expired') ?>
            <?= __('dash.trial_activate') ?>
          <?php endif; ?>
        </p>
      </div>
    <?php elseif ($activeSub): ?>
      <div class="plan-card plan-card--active">
        <div class="plan-badge">
          <span class="badge badge--success"><?= __('dash.plan_active') ?></span>
          <?php if ($stripeCancelsAt): ?>
            <span class="badge badge--warning" style="margin-left:8px"><?= __('dash.cancels_at') ?> <?= date('j. n. Y', strtotime($stripeCancelsAt)) ?></span>
          <?php endif; ?>
        </div>
        <div class="plan-name"><?= e(getPlanLabel($activeSub['plan'])) ?></div>
        <p style="color:#64748b;margin-top:8px;font-size:.9rem">
          <?= __('dash.plan_valid_to') ?> <strong><?= date('j. n. Y', strtotime($activeSub['expires_at'])) ?></strong>
        </p>
        <?php if ($stripeCustomerId && !$stripeCancelsAt): ?>
          <div style="margin-top:16px">
            <button id="btn-cancel-stripe" class="btn btn--ghost btn--sm" style="color:#ef4444;border-color:#ef4444">
              <?= __('dash.cancel_stripe') ?>
            </button>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="plan-card" style="border-color:#ef4444;background:#fef2f2">
        <div class="plan-badge"><span class="badge badge--danger"><?= __('status.expired') ?></span></div>
        <div class="plan-name"><?= __('dash.plan_expired') ?></div>
        <p style="color:#64748b;margin-top:8px;font-size:.9rem"><?= __('dash.plan_exp_sub') ?></p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Plans -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

  <!-- Basic -->
  <div class="card pricing-card" style="padding:28px">
    <div class="pricing-card__plan">Basic</div>
    <div class="pricing-card__price">
      <div class="pricing-card__amount"><?= langPrice('basic') ?></div>
      <div class="pricing-card__currency"><?= langCurrencySymbol() ?></div>
    </div>
    <div class="pricing-card__period">/ <?= __('common.month') ?></div>
    <div class="pricing-card__features" style="margin:16px 0">
      <div class="pricing-card__feature"><?= __('dash.basic_f1') ?></div>
      <div class="pricing-card__feature"><?= __('dash.basic_f2') ?></div>
      <div class="pricing-card__feature"><?= __('dash.basic_f3') ?></div>
      <div class="pricing-card__feature"><?= __('dash.basic_f4') ?></div>
    </div>
    <div style="background:#f8fafc;border-radius:8px;padding:14px;font-size:.875rem;color:#64748b">
      <strong><?= __('dash.how_to_pay') ?></strong> <?= __('dash.pay_basic', ['vs01' => $uid . '0001', 'price' => langPrice('basic') . ' ' . langCurrencySymbol()]) ?>
      <?= __('dash.pay_after') ?>
    </div>
    <a href="mailto:<?= e(PLATFORM_EMAIL) ?>?subject=<?= rawurlencode(__('dash.sub_mail_basic', ['uid' => $uid])) ?>" class="btn btn--outline btn--full" style="margin-top:16px">
      <?= __('dash.activate_basic') ?>
    </a>
  </div>

  <!-- Pro -->
  <div class="card pricing-card pricing-card--popular" style="padding:28px">
    <div class="pricing-card__popular-badge"><?= __('common.recommended2') ?></div>
    <div class="pricing-card__plan">Pro</div>
    <div class="pricing-card__price">
      <div class="pricing-card__amount"><?= langPrice('pro') ?></div>
      <div class="pricing-card__currency"><?= langCurrencySymbol() ?></div>
    </div>
    <div class="pricing-card__period">/ <?= __('common.month') ?></div>
    <div class="pricing-card__features" style="margin:16px 0">
      <div class="pricing-card__feature"><?= __('dash.pro_f1') ?></div>
      <div class="pricing-card__feature"><?= __('dash.pro_f2') ?></div>
      <div class="pricing-card__feature"><?= __('dash.pro_f3') ?></div>
      <div class="pricing-card__feature"><?= __('dash.pro_f4') ?></div>
      <div class="pricing-card__feature"><?= __('dash.pro_f5') ?></div>
    </div>
    <div style="background:#eff6ff;border-radius:8px;padding:14px;font-size:.875rem;color:#1e40af">
      <strong><?= __('dash.how_to_pay') ?></strong> <?= __('dash.pay_pro', ['vs02' => $uid . '0002', 'price' => langPrice('pro') . ' ' . langCurrencySymbol()]) ?>
      <?= __('dash.pay_after') ?>
    </div>
    <a href="mailto:<?= e(PLATFORM_EMAIL) ?>?subject=<?= rawurlencode(__('dash.sub_mail_pro', ['uid' => $uid])) ?>" class="btn btn--primary btn--full" style="margin-top:16px">
      <?= __('dash.activate_pro') ?>
    </a>
  </div>
</div>

<!-- Payment history -->
<?php if (!empty($subscriptions)): ?>
<div class="dash-card">
  <div class="dash-card__header"><div class="dash-card__title"><?= __('dash.sub_history') ?></div></div>
  <table class="data-table">
    <thead><tr>
      <th><?= __('common.plan') ?></th>
      <th><?= __('common.status') ?></th>
      <th><?= __('dash.valid_to') ?></th>
      <th><?= __('common.amount') ?></th>
      <th><?= __('common.date') ?></th>
    </tr></thead>
    <tbody>
      <?php foreach ($subscriptions as $s): ?>
        <tr>
          <td><?= e(getPlanLabel($s['plan'])) ?></td>
          <td>
            <?php
            $sc = match($s['status']) { 'active'=>'badge--success','expired'=>'badge--default','cancelled'=>'badge--danger', default=>'badge--default' };
            $sl = __('sub_status.' . $s['status']);
            ?>
            <span class="badge <?= $sc ?>"><?= $sl ?></span>
          </td>
          <td><?= date('j. n. Y', strtotime($s['expires_at'])) ?></td>
          <td><?= formatPrice((float)$s['amount']) ?></td>
          <td><?= formatDate($s['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Cancel confirmation modal -->
<div class="modal-overlay" id="cancel-modal">
  <div class="modal">
    <div class="modal__header">
      <div class="modal__title"><?= __('dash.cancel_stripe') ?></div>
      <button class="modal__close" onclick="document.getElementById('cancel-modal').classList.remove('open')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal__body">
      <p style="color:#64748b"><?= __('dash.cancel_confirm') ?></p>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost btn--sm" onclick="document.getElementById('cancel-modal').classList.remove('open')"><?= __('common.back') ?></button>
      <button id="btn-cancel-confirm" class="btn btn--danger btn--sm"><?= __('dash.cancel_stripe') ?></button>
    </div>
  </div>
</div>

<script>
document.getElementById('btn-cancel-stripe')?.addEventListener('click', function() {
  document.getElementById('cancel-modal').classList.add('open');
});

document.getElementById('btn-cancel-confirm')?.addEventListener('click', async function() {
  this.disabled = true;
  this.textContent = '...';
  try {
    const res = await fetch('/api/stripe_cancel.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf: document.querySelector('[name=csrf_token]')?.value || '' })
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById('cancel-modal').classList.remove('open');
      location.reload();
    } else {
      alert(data.error || <?= json_encode(__('dash.cancel_err')) ?>);
      this.disabled = false;
      this.textContent = <?= json_encode(__('dash.cancel_stripe')) ?>;
    }
  } catch(e) {
    alert(<?= json_encode(__('dash.cancel_err')) ?>);
    this.disabled = false;
    this.textContent = <?= json_encode(__('dash.cancel_stripe')) ?>;
  }
});
</script>
<?= csrfField() ?>

<?php require __DIR__ . '/_layout_end.php'; ?>
