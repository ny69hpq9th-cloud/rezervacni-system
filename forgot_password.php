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
            $db   = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate a secure token valid for 60 minutes
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600);

                try {
                    $db->prepare(
                        "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?"
                    )->execute([$token, $expires, $user['id']]);

                    $resetUrl = PLATFORM_URL . '/reset_password.php?token=' . $token;

                    $hi       = __('email.reset_hi');
                    $body1    = __('email.reset_body');
                    $btnLabel = __('email.reset_btn_label');
                    $expires_ = __('email.reset_expires');

                    $body = emailTemplate(__('email.reset_heading'), "
                        <p>{$hi}</p>
                        <p>{$body1}</p>
                        <p style='margin-top:24px;text-align:center'>
                          <a href='{$resetUrl}'
                             style='display:inline-block;padding:14px 32px;background:#2563eb;
                                    color:#fff;border-radius:8px;text-decoration:none;
                                    font-weight:600;font-size:15px'>{$btnLabel}</a>
                        </p>
                        <p style='margin-top:16px;font-size:13px;color:#6b7280;word-break:break-all'>
                          Nebo zkopírujte odkaz: <a href='{$resetUrl}' style='color:#2563eb'>{$resetUrl}</a>
                        </p>
                        <p style='color:#6b7280;font-size:13px;margin-top:16px'>{$expires_}</p>
                    ");

                    $sent = sendMail($email, __('email.reset_subject', ['name' => PLATFORM_NAME]), $body);
                    error_log('[RESET] Email attempt for ' . $email . ' → ' . ($sent ? 'OK' : 'FAILED'));

                    if (!$sent) {
                        // Token is saved; warn about email failure in logs but still show success to user
                        error_log('[RESET] sendMail() returned false for ' . $email);
                    }
                } catch (\Exception $e) {
                    // Likely missing DB columns — instruct admin to run migration
                    error_log('[RESET] DB error: ' . $e->getMessage() . ' — run ALTER TABLE to add reset_token columns');
                    $error = 'Chyba systému. Kontaktujte administrátora.';
                }
            } else {
                // User not found — log silently, show same success message (prevent email enumeration)
                error_log('[RESET] Reset requested for unknown/inactive email: ' . $email);
            }

            // Always show "sent" so attackers cannot enumerate registered emails
            if (empty($error)) $sent = true;
        }
    }
}

$pname = PLATFORM_NAME;
?>
<!DOCTYPE html>
<html lang="<?= htmlLang() ?>">
<head>
<meta charset="UTF-8">
<?= themeHeadScript() ?>
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
            <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" style="width:32px;height:32px"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
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
