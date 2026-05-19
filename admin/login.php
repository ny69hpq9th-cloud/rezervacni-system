<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isAdminLoggedIn()) redirect(PLATFORM_URL . '/admin/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = __('admin.err_token');
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email']     = $email;
            redirect(PLATFORM_URL . '/admin/index.php');
        } else {
            $error = __('admin.err_credentials');
        }
    }
}

$pname = PLATFORM_NAME;
?>
<!DOCTYPE html>
<html lang="<?= htmlLang() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin – <?= e(PLATFORM_TITLE) ?></title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="shortcut icon" href="/favicon.svg">
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo" style="display:flex;align-items:center;gap:10px"><?= logoIcon('color') ?><span style="font-weight:800;font-size:1.15rem"><?= e($pname) ?></span><span style="font-size:.75rem;background:#ef4444;color:#fff;padding:2px 8px;border-radius:4px;font-weight:700">ADMIN</span></div>
    <div class="card" style="padding:40px">
      <h1 class="auth-title"><?= __('admin.login_title') ?></h1>
      <p class="auth-subtitle"><?= __('admin.login_sub') ?></p>

      <?php if ($error): ?>
        <div class="alert alert--error"><span class="alert__icon">✕</span><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label"><?= __('common.email') ?></label>
          <input type="email" name="email" class="form-control" placeholder="admin@email.cz" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label"><?= __('auth.password') ?></label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn--primary btn--full"><?= __('admin.login_btn') ?></button>
      </form>
    </div>
    <div class="auth-footer"><a href="/"><?= __('admin.back_home') ?></a></div>
  </div>
</div>
</body>
</html>
