<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) redirect(PLATFORM_URL . '/dashboard/index.php');

$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = __('auth.err_token');
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = __('auth.err_email');
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $hi      = __('email.reset_hi');
                $body1   = __('email.reset_body');
                $contact = __('email.reset_contact');
                $ignore  = __('email.reset_ignore');
                $body    = emailTemplate(__('email.reset_heading'), "
                    <p>{$hi}</p>
                    <p>{$body1}</p>
                    <p>{$contact} <a href='mailto:" . PLATFORM_EMAIL . "'>" . PLATFORM_EMAIL . "</a>.</p>
                    <p style='color:#6b7280;font-size:14px'>{$ignore}</p>
                ");
                sendMail($email, __('email.reset_subject', ['name' => PLATFORM_NAME]), $body);
            }
            $sent = true;
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
<title><?= __('auth.forgot_title') ?> – <?= e(PLATFORM_TITLE) ?></title>
<?php renderSeoHead([
    'title'   => __('auth.forgot_title') . ' – ' . PLATFORM_TITLE,
    'desc'    => __('auth.meta_desc_forgot'),
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
      <?php if ($sent): ?>
        <div style="text-align:center">
          <div style="width:64px;height:64px;border-radius:50%;background:#ecfdf5;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
            <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" style="width:32px;height:32px"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.21 1.23 2 2 0 012.18 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.56-1.56a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92z"/></svg>
          </div>
          <h2 style="margin-bottom:10px"><?= __('auth.forgot_sent_title') ?></h2>
          <p style="color:#64748b;margin-bottom:24px"><?= __('auth.forgot_sent_sub') ?></p>
          <a href="/login.php" class="btn btn--primary"><?= __('auth.back_login') ?></a>
        </div>
      <?php else: ?>
        <h1 class="auth-title"><?= __('auth.forgot_title') ?></h1>
        <p class="auth-subtitle"><?= __('auth.forgot_subtitle') ?></p>
        <?php if ($error): ?>
          <div class="alert alert--error"><span class="alert__icon">✕</span><?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST">
          <?= csrfField() ?>
          <div class="form-group">
            <label class="form-label"><?= __('common.email') ?></label>
            <input type="email" name="email" class="form-control"
                   placeholder="<?= currentLang() === 'en' ? 'your@email.com' : 'vas@email.cz' ?>" required autofocus>
          </div>
          <button type="submit" class="btn btn--primary btn--full"><?= __('auth.forgot_btn') ?></button>
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
