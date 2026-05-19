<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) redirect(PLATFORM_URL . '/dashboard/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = __('auth.err_token');
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $dnsOk    = true;
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailDomain = substr(strrchr($email, '@'), 1);
            if (!checkdnsrr($emailDomain, 'MX') && !checkdnsrr($emailDomain, 'A')) {
                $dnsOk = false;
                $error = __('reg.err_email_dns');
            }
        }
        if ($dnsOk) {
            if (loginUser($email, $password)) {
                redirect(PLATFORM_URL . '/dashboard/index.php');
            } else {
                $error = __('auth.err_credentials');
            }
        }
    }
}

$pname = PLATFORM_NAME;
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="<?= htmlLang() ?>">
<head>
<meta charset="UTF-8">
<?= themeHeadScript() ?>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('auth.login_title') ?> – <?= e(PLATFORM_TITLE) ?></title>
<?php renderSeoHead([
    'title'   => __('auth.login_title') . ' – ' . PLATFORM_TITLE,
    'desc'    => __('auth.meta_desc_login'),
    'noindex' => true,
]); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
<style>
.pw-wrap { position:relative; }
.pw-wrap .form-control { padding-right:42px; }
.pw-toggle { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; padding:4px; color:#94a3b8; display:flex; align-items:center; line-height:1; }
.pw-toggle:hover { color:#64748b; }
.pw-toggle svg { width:18px; height:18px; display:block; }
</style>
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <a href="/" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit"><?= logoIcon('color') ?><span style="font-weight:800;font-size:1.15rem"><?= e($pname) ?></span></a>
      <div style="display:flex;align-items:center;gap:8px;margin-left:auto"><?= langSwitcher() ?><?= themeToggle() ?></div>
    </div>
    <div class="card" style="padding:40px">
      <h1 class="auth-title"><?= __('auth.login_title') ?></h1>
      <p class="auth-subtitle"><?= __('auth.login_subtitle') ?></p>

      <?php if ($flash): ?>
        <div class="alert alert--<?= e($flash['type']) ?>">
          <span class="alert__icon"><?= $flash['type'] === 'success' ? '✓' : '✕' ?></span>
          <?= e($flash['message']) ?>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert--error">
          <span class="alert__icon">✕</span>
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/login.php" novalidate>
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label" for="email"><?= __('common.email') ?></label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= e($_POST['email'] ?? '') ?>"
                 placeholder="<?= currentLang() === 'en' ? 'your@email.com' : 'vas@email.cz' ?>" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label" for="password">
            <?= __('auth.password') ?>
            <a href="/forgot_password.php" style="float:right;font-weight:400;font-size:.8rem;color:var(--primary)"><?= __('auth.login_forgot') ?></a>
          </label>
          <div class="pw-wrap">
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="••••••••" required>
            <button type="button" class="pw-toggle" onclick="togglePw(this)" title="Zobrazit/skrýt heslo">
              <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn--primary btn--full" style="margin-top:8px">
          <?= __('auth.login_btn') ?>
        </button>
      </form>
    </div>
    <div class="auth-footer">
      <?= __('auth.no_account') ?> <a href="/register.php"><?= __('auth.register_free') ?></a>
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
</body>
</html>
