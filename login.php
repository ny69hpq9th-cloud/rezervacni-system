<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) redirect(PLATFORM_URL . '/dashboard/index.php');

$error = '';
$_debugInfo = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = __('auth.err_token');
        $_debugInfo[] = 'CSRF check FAILED';
    } else {
        $_debugInfo[] = 'CSRF check OK';
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        // Debug: check what's in the DB for this email
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, email, status, plan, created_at, LENGTH(password) as pw_len FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $dbUser = $stmt->fetch();
            if ($dbUser) {
                $_debugInfo[] = "DB user found: id={$dbUser['id']}, status={$dbUser['status']}, plan={$dbUser['plan']}, pw_len={$dbUser['pw_len']}";
            } else {
                $_debugInfo[] = "DB user NOT found for email: {$email}";
            }
        } catch (Exception $e) {
            $_debugInfo[] = "DB error: " . $e->getMessage();
        }

        if (loginUser($email, $password)) {
            redirect(PLATFORM_URL . '/dashboard/index.php');
        } else {
            // Extra debug: check user without status filter
            try {
                $stmt2 = $db->prepare("SELECT id, status, LENGTH(password) as pw_len FROM users WHERE email = ?");
                $stmt2->execute([$email]);
                $u2 = $stmt2->fetch();
                if ($u2) {
                    $_debugInfo[] = "Login failed — user exists, status='{$u2['status']}', pw_len={$u2['pw_len']}";
                    // Test password_verify directly
                    $stmt3 = $db->prepare("SELECT password FROM users WHERE email = ?");
                    $stmt3->execute([$email]);
                    $row = $stmt3->fetch();
                    $pwOk = $row ? password_verify($password, $row['password']) : false;
                    $_debugInfo[] = "password_verify result: " . ($pwOk ? 'TRUE' : 'FALSE');
                } else {
                    $_debugInfo[] = "Login failed — no user with this email at all";
                }
            } catch (Exception $e) {
                $_debugInfo[] = "Debug query error: " . $e->getMessage();
            }
            $error = __('auth.err_credentials');
        }
    }
}
// Log debug info to PHP error log (always)
if (!empty($_debugInfo)) {
    error_log('[LOGIN DEBUG] ' . implode(' | ', $_debugInfo));
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
<link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
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
<?php if (!empty($_debugInfo)): ?>
<div style="position:fixed;bottom:0;left:0;right:0;background:#1e1e1e;color:#a8ff78;font-family:monospace;font-size:11px;padding:8px 16px;z-index:9999;border-top:2px solid #333;max-height:160px;overflow-y:auto">
  <strong style="color:#fff">LOGIN DEBUG:</strong><br>
  <?= implode('<br>', array_map('htmlspecialchars', $_debugInfo)) ?>
</div>
<?php endif; ?>
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
