<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) redirect(PLATFORM_URL . '/dashboard/index.php');

$token = trim($_GET['token'] ?? '');
$error = '';
$done  = false;
$user  = null;

$db = getDB();

// ── Validate token ───────────────────────────────────────────────────────────
if ($token) {
    try {
        $stmt = $db->prepare(
            "SELECT * FROM users
             WHERE reset_token = ?
               AND reset_token_expires > NOW()
               AND status = 'active'
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();
    } catch (\Exception $e) {
        error_log('[RESET] DB error looking up token: ' . $e->getMessage());
    }
}

// ── Handle form POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = __('auth.err_token');
    } else {
        $pass1 = $_POST['password']         ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';

        if (strlen($pass1) < 8) {
            $error = __('email.reset_err_short');
        } elseif ($pass1 !== $pass2) {
            $error = __('email.reset_err_match');
        } else {
            $hash = password_hash($pass1, PASSWORD_DEFAULT);
            try {
                $db->prepare(
                    "UPDATE users
                     SET password = ?, reset_token = NULL, reset_token_expires = NULL
                     WHERE id = ?"
                )->execute([$hash, $user['id']]);

                error_log('[RESET] Password changed for user id=' . $user['id']);
                $done = true;
            } catch (\Exception $e) {
                error_log('[RESET] DB update error: ' . $e->getMessage());
                $error = 'Chyba systému. Zkuste to prosím znovu.';
            }
        }
    }
}

$pname      = PLATFORM_NAME;
$validToken = ($user !== null);
?>
<!DOCTYPE html>
<html lang="<?= htmlLang() ?>">
<head>
<meta charset="UTF-8">
<?= themeHeadScript() ?>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= __('email.reset_page_title') ?> – <?= e(PLATFORM_TITLE) ?></title>
<?php renderSeoHead([
    'title'   => __('email.reset_page_title') . ' – ' . PLATFORM_TITLE,
    'noindex' => true,
]); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <a href="/" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit"><?= logoIcon('color') ?><span style="font-weight:800;font-size:1.15rem"><?= e($pname) ?></span></a>
      <?= langSwitcher('ms-auto') ?>
    </div>
    <div class="card" style="padding:40px">

      <?php if ($done): ?>
        <!-- ── Success ── -->
        <div style="text-align:center">
          <div style="width:64px;height:64px;border-radius:50%;background:#ecfdf5;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
            <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="width:32px;height:32px"><polyline points="20,6 9,17 4,12"/></svg>
          </div>
          <h2 style="margin-bottom:10px"><?= __('email.reset_page_title') ?></h2>
          <p style="color:#64748b;margin-bottom:24px"><?= __('email.reset_success') ?></p>
          <a href="/login.php" class="btn btn--primary"><?= __('auth.back_login') ?></a>
        </div>

      <?php elseif (!$validToken): ?>
        <!-- ── Invalid / expired token ── -->
        <div style="text-align:center">
          <div style="width:64px;height:64px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
            <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" style="width:32px;height:32px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <h2 style="margin-bottom:10px"><?= __('email.reset_page_title') ?></h2>
          <p style="color:#64748b;margin-bottom:24px"><?= __('email.reset_invalid') ?></p>
          <a href="/forgot_password.php" class="btn btn--primary"><?= __('auth.forgot_btn') ?></a>
        </div>

      <?php else: ?>
        <!-- ── Password form ── -->
        <h1 class="auth-title"><?= __('email.reset_page_title') ?></h1>
        <p class="auth-subtitle"><?= __('email.reset_page_sub') ?></p>

        <?php if ($error): ?>
          <div class="alert alert--error"><span class="alert__icon">✕</span><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">

          <div class="form-group">
            <label class="form-label"><?= __('email.reset_new_pass') ?></label>
            <input type="password" name="password" class="form-control"
                   minlength="8" required autofocus
                   placeholder="<?= currentLang() === 'en' ? 'At least 8 characters' : 'Alespoň 8 znaků' ?>">
          </div>

          <div class="form-group">
            <label class="form-label"><?= __('email.reset_confirm') ?></label>
            <input type="password" name="password_confirm" class="form-control"
                   minlength="8" required
                   placeholder="<?= currentLang() === 'en' ? 'Repeat password' : 'Zopakujte heslo' ?>">
          </div>

          <button type="submit" class="btn btn--primary btn--full"><?= __('email.reset_submit') ?></button>
        </form>
      <?php endif; ?>

    </div>
    <div class="auth-footer">
      <a href="/login.php"><?= __('auth.back_to_login') ?></a>
    </div>
  </div>
</div>
</body>
</html>
