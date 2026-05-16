<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';
if (STRIPE_ENABLED) require_once __DIR__ . '/includes/stripe.php';

if (isLoggedIn()) redirect(PLATFORM_URL . '/dashboard/index.php');

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = __('reg.err_token');
    } else {
        $businessName    = trim($_POST['business_name'] ?? '');
        $email           = strtolower(trim($_POST['email'] ?? ''));
        $password        = $_POST['password'] ?? '';
        $businessType    = trim($_POST['business_type'] ?? '');
        $plan            = in_array($_POST['plan'] ?? '', ['basic','pro']) ? $_POST['plan'] : 'basic';
        $paymentMethodId = trim($_POST['payment_method_id'] ?? '');

        $old = compact('businessName','email','businessType','plan');

        if (strlen($businessName) < 2)                  $errors[] = __('reg.err_name');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('reg.err_email');
        } else {
            $emailDomain = substr(strrchr($email, '@'), 1);
            if (!checkdnsrr($emailDomain, 'MX') && !checkdnsrr($emailDomain, 'A')) {
                $errors[] = __('reg.err_email_dns');
            }
        }
        if (strlen($password) < 8)                      $errors[] = __('reg.err_password');
        if (STRIPE_ENABLED && empty($paymentMethodId))  $errors[] = __('reg.err_card');

        if (empty($errors)) {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) $errors[] = __('reg.err_exists');
        }

        if (empty($errors)) {
            $db   = getDB();
            $slug = generateUniqueSlug($businessName, $db);
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $db->prepare(
                "INSERT INTO users (email,password,business_name,slug,business_type,plan,status)
                 VALUES (?,?,?,?,?,'trial','active')"
            )->execute([$email, $hash, $businessName, $slug, $businessType ?: null]);
            $userId = (int)$db->lastInsertId();

            // Default working hours (Mon–Fri 9–17)
            $whStmt = $db->prepare(
                "INSERT INTO working_hours (user_id,day_of_week,is_working,start_time,end_time) VALUES (?,?,?,?,?)"
            );
            for ($i = 0; $i <= 6; $i++) {
                $whStmt->execute([$userId, $i, ($i >= 1 && $i <= 5) ? 1 : 0, '09:00:00', '17:00:00']);
            }

            // Stripe integration
            $stripeError = null;
            if (STRIPE_ENABLED && $paymentMethodId) {
                try {
                    $customer     = stripeCreateCustomer($email, $businessName, $paymentMethodId);
                    $subscription = stripeCreateSubscription($customer['id'], $plan, TRIAL_DAYS);

                    $db->prepare(
                        "UPDATE users SET stripe_customer_id=?, stripe_subscription_id=?, plan=? WHERE id=?"
                    )->execute([$customer['id'], $subscription['id'], $plan, $userId]);

                    $trialEnd = date('Y-m-d H:i:s', strtotime('+' . TRIAL_DAYS . ' days'));
                    $db->prepare(
                        "INSERT INTO subscriptions (user_id,plan,status,amount,expires_at,stripe_subscription_id)
                         VALUES (?,?,'active',0,?,?)"
                    )->execute([$userId, $plan, $trialEnd, $subscription['id']]);

                } catch (Exception $e) {
                    $stripeError = $e->getMessage();
                    error_log('Stripe registration error for user #' . $userId . ': ' . $stripeError);
                }
            }

            $userRow = $db->query("SELECT * FROM users WHERE id=$userId")->fetch();
            try { emailWelcome($userRow); } catch (Exception $e) {}

            session_regenerate_id(true);
            $_SESSION['user_id']    = $userId;
            $_SESSION['user_email'] = $email;

            if ($stripeError) {
                flash('warning', __('reg.warn_stripe', ['error' => $stripeError]));
            } else {
                flash('success', __('reg.ok_stripe'));
            }
            redirect(PLATFORM_URL . '/dashboard/index.php');
        }
    }
}

$pname = PLATFORM_NAME;
$bizTypes = __array('reg.biz_types');
?>
<!DOCTYPE html>
<html lang="<?= htmlLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('reg.page_title') ?> – <?= e(PLATFORM_TITLE) ?></title>
<?php renderSeoHead([
    'title'    => __('reg.page_title') . ' – ' . PLATFORM_TITLE,
    'desc'     => __('reg.meta_desc'),
    'keywords' => __('index.meta_keywords'),
]); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<?php if (STRIPE_ENABLED): ?>
<script src="https://js.stripe.com/v3/"></script>
<?php endif; ?>
<style>
.reg-page { min-height:100vh; display:grid; grid-template-columns:1fr 1fr; }
.reg-left {
  background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 60%,#3b82f6 100%);
  padding:60px 48px; display:flex; flex-direction:column; justify-content:center; color:#fff;
}
.reg-left h1 { font-size:2rem; font-weight:800; margin-bottom:16px; color:#fff; white-space:pre-line; }
.reg-left p  { color:rgba(255,255,255,.8); font-size:1rem; margin-bottom:36px; line-height:1.7; }
.benefit { display:flex; align-items:flex-start; gap:14px; margin-bottom:20px; }
.benefit__icon { width:36px; height:36px; border-radius:8px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.benefit__text strong { display:block; font-weight:600; margin-bottom:2px; }
.benefit__text span   { font-size:.85rem; color:rgba(255,255,255,.7); }
.reg-right { background:#f8fafc; display:flex; align-items:center; justify-content:center; padding:48px 40px; }
.reg-form-wrap { width:100%; max-width:440px; }
.reg-logo { font-size:1.4rem; font-weight:800; color:#0f172a; margin-bottom:28px; display:flex; align-items:center; justify-content:space-between; }
.reg-logo a { text-decoration:none; color:inherit; }
.reg-logo a span { color:#2563eb; }
.reg-title { font-size:1.4rem; font-weight:700; margin-bottom:4px; }
.reg-subtitle { color:#64748b; font-size:.875rem; margin-bottom:24px; }
.plan-selector { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px; }
.plan-option { cursor:pointer; }
.plan-option input { display:none; }
.plan-card-select { border:2px solid #e2e8f0; border-radius:10px; padding:14px; text-align:center; transition:all .15s; }
.plan-option input:checked + .plan-card-select { border-color:#2563eb; background:#eff6ff; }
.plan-card-select__name { font-weight:700; font-size:.95rem; margin-bottom:2px; }
.plan-card-select__price { font-size:1.1rem; font-weight:800; color:#2563eb; }
.plan-card-select__sub { font-size:.75rem; color:#94a3b8; }
.stripe-card-wrap { border:1.5px solid #e2e8f0; border-radius:10px; padding:12px 14px; background:#fff; transition:border-color .15s; }
.stripe-card-wrap:focus-within { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.15); }
.stripe-badge { display:flex; align-items:center; gap:6px; font-size:.75rem; color:#94a3b8; margin-top:8px; }
.stripe-badge svg { width:14px; height:14px; }
.trial-banner { background:#ecfdf5; border:1px solid #a7f3d0; border-radius:10px; padding:12px 16px; margin-bottom:20px; font-size:.85rem; color:#065f46; display:flex; align-items:flex-start; gap:10px; }
.trial-banner svg { width:18px; height:18px; flex-shrink:0; margin-top:1px; }
#card-errors { color:#ef4444; font-size:.8rem; margin-top:6px; min-height:18px; }
.ms-auto { margin-left:auto; }
.pw-wrap { position:relative; }
.pw-wrap .form-control { padding-right:42px; }
.pw-toggle { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; padding:4px; color:#94a3b8; display:flex; align-items:center; line-height:1; }
.pw-toggle:hover { color:#64748b; }
.pw-toggle svg { width:18px; height:18px; display:block; }
@media (max-width:768px) {
  .reg-page { grid-template-columns:1fr; }
  .reg-left  { display:none; }
  .reg-right { padding:32px 20px; }
}
</style>
</head>
<body>
<div class="reg-page">

  <!-- Left: benefits -->
  <div class="reg-left">
    <a href="/" style="color:rgba(255,255,255,.6);font-size:.85rem;margin-bottom:40px;display:block;text-decoration:none"><?= __('reg.back_to') ?> <?= e($pname) ?></a>
    <h1><?= __('reg.left_title') ?></h1>
    <p><?= __('reg.left_sub', ['name' => e($pname)]) ?></p>
    <?php
    $benefits = [
        ['b1_title','b1_sub','check'],
        ['b2_title','b2_sub','clock'],
        ['b3_title','b3_sub','mail'],
        ['b4_title','b4_sub','shield'],
    ];
    $icons = [
        'check'  => '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>',
        'clock'  => '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>',
        'mail'   => '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    ];
    foreach ($benefits as [$tk, $sk, $ic]):
    ?>
    <div class="benefit">
      <div class="benefit__icon"><?= $icons[$ic] ?></div>
      <div class="benefit__text">
        <strong><?= __('reg.' . $tk) ?></strong>
        <span><?= __('reg.' . $sk) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Right: form -->
  <div class="reg-right">
    <div class="reg-form-wrap">
      <div class="reg-logo">
        <a href="/" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit"><?= logoIcon('color') ?><span style="font-weight:800;font-size:1.15rem"><?= e($pname) ?></span></a>
        <?= langSwitcher() ?>
      </div>
      <h1 class="reg-title"><?= __('reg.form_title') ?></h1>
      <p class="reg-subtitle"><?= __('reg.form_sub') ?></p>

      <?php if (!empty($errors)): ?>
        <div class="alert alert--error" style="margin-bottom:18px">
          <span class="alert__icon">✕</span>
          <?= e(implode(' ', $errors)) ?>
        </div>
      <?php endif; ?>

      <div class="trial-banner">
        <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>
        <div><?= __('reg.trial_banner') ?></div>
      </div>

      <form id="reg-form" method="POST" action="/register.php" novalidate>
        <?= csrfField() ?>

        <div class="form-group">
          <label class="form-label"><?= __('reg.biz_name') ?> <span>*</span></label>
          <input type="text" name="business_name" class="form-control"
                 value="<?= e($old['businessName'] ?? '') ?>"
                 placeholder="<?= __('reg.biz_name_ph') ?>" required autofocus>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label"><?= __('common.email') ?> <span>*</span></label>
            <input type="email" name="email" class="form-control"
                   value="<?= e($old['email'] ?? '') ?>"
                   placeholder="<?= currentLang() === 'en' ? 'your@email.com' : 'vas@email.cz' ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label"><?= __('auth.password') ?> <span>*</span></label>
            <div class="pw-wrap">
              <input type="password" name="password" class="form-control"
                     placeholder="<?= __('reg.password_ph') ?>" required minlength="8">
              <button type="button" class="pw-toggle" onclick="togglePw(this)" title="Zobrazit/skrýt heslo">
                <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label"><?= __('reg.biz_type') ?></label>
          <select name="business_type" class="form-control">
            <option value=""><?= __('reg.biz_type_sel') ?></option>
            <?php foreach ($bizTypes as $t): ?>
              <option value="<?= e($t) ?>" <?= ($old['businessType'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Plan selector -->
        <div class="form-group">
          <label class="form-label"><?= __('reg.choose_plan') ?></label>
          <div class="plan-selector">
            <label class="plan-option">
              <input type="radio" name="plan" value="basic" <?= ($old['plan'] ?? 'basic') === 'basic' ? 'checked' : '' ?>>
              <div class="plan-card-select">
                <div class="plan-card-select__name">Basic</div>
                <div class="plan-card-select__price"><?= langPrice('basic') ?> <?= langCurrencySymbol() ?></div>
                <div class="plan-card-select__sub"><?= __('reg.basic_sub') ?></div>
              </div>
            </label>
            <label class="plan-option">
              <input type="radio" name="plan" value="pro" <?= ($old['plan'] ?? '') === 'pro' ? 'checked' : '' ?>>
              <div class="plan-card-select">
                <div class="plan-card-select__name">Pro ⭐</div>
                <div class="plan-card-select__price"><?= langPrice('pro') ?> <?= langCurrencySymbol() ?></div>
                <div class="plan-card-select__sub"><?= __('reg.pro_sub') ?></div>
              </div>
            </label>
          </div>
        </div>

        <?php if (STRIPE_ENABLED): ?>
        <div class="form-group">
          <label class="form-label"><?= __('reg.card_label') ?> <span>*</span></label>
          <div class="stripe-card-wrap" id="card-element"></div>
          <div id="card-errors" role="alert"></div>
          <div class="stripe-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            <?= __('reg.card_secure') ?>
          </div>
        </div>
        <input type="hidden" name="payment_method_id" id="payment_method_id">
        <?php else: ?>
        <div class="alert alert--warning" style="margin-bottom:18px">
          <span class="alert__icon">⚠</span>
          <?= __('reg.stripe_off') ?>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn--primary btn--full btn--lg" id="submit-btn">
          <?= __('reg.submit') ?>
        </button>

        <p style="text-align:center;font-size:.78rem;color:#94a3b8;margin-top:14px;line-height:1.6">
          <?= __('reg.terms', ['days' => TRIAL_DAYS]) ?><br>
          <?= __('reg.have_account') ?> <a href="/login.php"><?= __('reg.sign_in') ?></a>
        </p>
      </form>
    </div>
  </div>

</div>

<script>
function togglePw(btn) {
  var input = btn.closest('.pw-wrap').querySelector('input');
  var show  = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.querySelector('.eye-on').style.display  = show ? 'none' : '';
  btn.querySelector('.eye-off').style.display = show ? ''     : 'none';
}
</script>
<?php if (STRIPE_ENABLED): ?>
<script>
(function() {
  const stripe   = Stripe('<?= e(STRIPE_PUBLISHABLE_KEY) ?>');
  const elements = stripe.elements({ locale: '<?= currentLang() ?>' });
  const style = {
    base: { fontSize:'15px', fontFamily:"'Inter',sans-serif", color:'#0f172a', '::placeholder':{ color:'#94a3b8' } },
    invalid: { color:'#ef4444' },
  };
  const card = elements.create('card', { style, hidePostalCode: true });
  card.mount('#card-element');
  card.on('change', ({ error }) => {
    document.getElementById('card-errors').textContent = error ? error.message : '';
  });

  let clientSecret = null;
  fetch('/api/stripe_setup_intent.php', { method: 'POST' })
    .then(r => r.json())
    .then(data => { if (data.client_secret) clientSecret = data.client_secret; })
    .catch(err => console.error('Network error:', err));

  document.getElementById('reg-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!clientSecret) {
      document.getElementById('card-errors').textContent = '<?= __('reg.loading_gw') ?>';
      return;
    }
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = '<?= __('reg.verifying') ?>';

    const { setupIntent, error } = await stripe.confirmCardSetup(clientSecret, {
      payment_method: {
        card,
        billing_details: {
          name:  document.querySelector('[name="business_name"]').value.trim(),
          email: document.querySelector('[name="email"]').value.trim(),
        },
      },
    });

    if (error) {
      document.getElementById('card-errors').textContent = error.message;
      btn.disabled = false;
      btn.textContent = '<?= __('reg.submit') ?>';
      return;
    }

    document.getElementById('payment_method_id').value = setupIntent.payment_method;
    btn.textContent = '<?= __('reg.creating') ?>';
    this.submit();
  });
})();
</script>
<?php endif; ?>
</body>
</html>
