<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

if (isLoggedIn()) redirect(PLATFORM_URL . '/dashboard/index.php');

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
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
        } elseif (!preg_match('/[A-Z]/', $pass1)) {
            $error = currentLang() === 'en' ? 'Password must contain at least one uppercase letter.' : 'Heslo musí obsahovat alespoň jedno velké písmeno.';
        } elseif (!preg_match('/[0-9]/', $pass1)) {
            $error = currentLang() === 'en' ? 'Password must contain at least one number.' : 'Heslo musí obsahovat alespoň jedno číslo.';
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
$isEn       = currentLang() === 'en';
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
<style>
/* ── Password show/hide toggle (same as register.php) ── */
.pw-wrap { position: relative; }
.pw-wrap .form-control { padding-right: 42px; }
.pw-toggle {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; padding: 4px;
  color: #94a3b8; display: flex; align-items: center; line-height: 1;
}
.pw-toggle:hover { color: #64748b; }
.pw-toggle svg { width: 18px; height: 18px; display: block; }
</style>
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

        <form id="reset-form" method="POST" novalidate>
          <?= csrfField() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">

          <!-- New password -->
          <div class="form-group">
            <label class="form-label"><?= __('email.reset_new_pass') ?> <span>*</span></label>
            <div class="pw-wrap">
              <input type="password" id="pw-input" name="password" class="form-control"
                     placeholder="<?= $isEn ? 'At least 8 characters' : 'Alespoň 8 znaků' ?>"
                     required minlength="8" autofocus>
              <button type="button" class="pw-toggle" onclick="togglePw(this)" title="<?= $isEn ? 'Show/hide password' : 'Zobrazit/skrýt heslo' ?>">
                <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>

            <!-- Strength indicator (identical to register.php) -->
            <div id="pw-strength-wrap" style="display:none;margin-top:5px">
              <div style="display:flex;align-items:center;gap:8px">
                <div style="flex:1;height:4px;border-radius:4px;background:#e2e8f0;overflow:hidden">
                  <div id="pw-strength-fill" style="height:100%;width:0;border-radius:4px;transition:width .25s,background .25s"></div>
                </div>
                <span id="pw-strength-label" style="font-size:.7rem;white-space:nowrap;min-width:56px;text-align:right"></span>
              </div>
              <ul id="pw-criteria" style="list-style:none;margin:4px 0 0;padding:0;display:flex;flex-direction:column;gap:1px">
                <li id="pc-len"  style="font-size:.72rem">&times; <?= $isEn ? 'Min. 8 characters' : 'Min. 8 znaků' ?></li>
                <li id="pc-up"   style="font-size:.72rem">&times; <?= $isEn ? 'Uppercase letter'  : 'Velké písmeno' ?></li>
                <li id="pc-num"  style="font-size:.72rem">&times; <?= $isEn ? 'Number'            : 'Číslo' ?></li>
                <li id="pc-spec" style="font-size:.72rem">&times; <?= $isEn ? 'Special char'      : 'Speciální znak' ?></li>
              </ul>
            </div>
          </div>

          <!-- Confirm password -->
          <div class="form-group">
            <label class="form-label"><?= __('email.reset_confirm') ?> <span>*</span></label>
            <div class="pw-wrap">
              <input type="password" id="pw-confirm" name="password_confirm" class="form-control"
                     placeholder="<?= $isEn ? 'Repeat password' : 'Zopakujte heslo' ?>"
                     required minlength="8">
              <button type="button" class="pw-toggle" onclick="togglePw(this)" title="<?= $isEn ? 'Show/hide password' : 'Zobrazit/skrýt heslo' ?>">
                <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
            <div id="pw-match-hint" style="font-size:.75rem;margin-top:4px;min-height:16px"></div>
          </div>

          <button type="submit" class="btn btn--primary btn--full" id="reset-btn"><?= __('email.reset_submit') ?></button>
        </form>
      <?php endif; ?>

    </div>
    <div class="auth-footer">
      <a href="/login.php"><?= __('auth.back_to_login') ?></a>
    </div>
  </div>
</div>

<script>
/* ── Show/hide password toggle ───────────────────────────────────────────── */
function togglePw(btn) {
  var input = btn.closest('.pw-wrap').querySelector('input');
  var show  = input.type === 'password';
  input.type = show ? 'text' : 'password';
  btn.querySelector('.eye-on').style.display  = show ? 'none' : '';
  btn.querySelector('.eye-off').style.display = show ? ''     : 'none';
}

/* ── Password strength indicator (identical logic to register.php) ─────── */
(function () {
  var isEn         = <?= $isEn ? 'true' : 'false' ?>;
  var pwInput      = document.getElementById('pw-input');
  var pwConfirm    = document.getElementById('pw-confirm');
  var strengthWrap = document.getElementById('pw-strength-wrap');
  var strengthFill = document.getElementById('pw-strength-fill');
  var strengthLabel= document.getElementById('pw-strength-label');
  var matchHint    = document.getElementById('pw-match-hint');
  var submitBtn    = document.getElementById('reset-btn');

  if (!pwInput) return; // page may show success/error state without form

  var levels = isEn
    ? [
        { label: 'Weak',        color: '#ef4444', w: '25%'  },
        { label: 'Fair',        color: '#f97316', w: '50%'  },
        { label: 'Strong',      color: '#eab308', w: '75%'  },
        { label: 'Very strong', color: '#22c55e', w: '100%' },
      ]
    : [
        { label: 'Slabé',       color: '#ef4444', w: '25%'  },
        { label: 'Střední',     color: '#f97316', w: '50%'  },
        { label: 'Silné',       color: '#eab308', w: '75%'  },
        { label: 'Velmi silné', color: '#22c55e', w: '100%' },
      ];

  var criteriaLabels = isEn
    ? { len: 'Min. 8 characters', up: 'Uppercase letter', num: 'Number', spec: 'Special char' }
    : { len: 'Min. 8 znaků',      up: 'Velké písmeno',   num: 'Číslo',  spec: 'Speciální znak' };

  var criteriaEls = {
    len:  document.getElementById('pc-len'),
    up:   document.getElementById('pc-up'),
    num:  document.getElementById('pc-num'),
    spec: document.getElementById('pc-spec'),
  };

  function getStrengthLevel(pw) {
    var score = 0;
    if (pw.length >= 8)           score++;
    if (pw.length >= 12)          score++;
    if (/[A-Z]/.test(pw))        score++;
    if (/[0-9]/.test(pw))        score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    if (score <= 1) return 0;
    if (score === 2) return 1;
    if (score <= 4) return 2;
    return 3;
  }

  function setCriterion(el, met, label) {
    el.textContent = (met ? '✓ ' : '× ') + label;
    el.classList.toggle('crit-met',  met);
    el.classList.toggle('crit-fail', !met);
  }

  function meetsMinRules(pw) {
    return pw.length >= 8 && /[A-Z]/.test(pw) && /[0-9]/.test(pw);
  }

  function updateMatchHint() {
    var p1 = pwInput.value;
    var p2 = pwConfirm.value;
    if (!p2) { matchHint.textContent = ''; return; }
    if (p1 === p2) {
      matchHint.textContent = isEn ? '✓ Passwords match' : '✓ Hesla se shodují';
      matchHint.style.color = '#22c55e';
    } else {
      matchHint.textContent = isEn ? '× Passwords do not match' : '× Hesla se neshodují';
      matchHint.style.color = '#ef4444';
    }
  }

  pwInput.addEventListener('input', function () {
    var val = this.value;
    if (!val) { strengthWrap.style.display = 'none'; updateMatchHint(); return; }
    strengthWrap.style.display = 'block';
    var lv = levels[getStrengthLevel(val)];
    strengthFill.style.width      = lv.w;
    strengthFill.style.background = lv.color;
    strengthLabel.textContent     = lv.label;
    strengthLabel.style.color     = lv.color;
    setCriterion(criteriaEls.len,  val.length >= 8,           criteriaLabels.len);
    setCriterion(criteriaEls.up,   /[A-Z]/.test(val),         criteriaLabels.up);
    setCriterion(criteriaEls.num,  /[0-9]/.test(val),         criteriaLabels.num);
    setCriterion(criteriaEls.spec, /[^A-Za-z0-9]/.test(val),  criteriaLabels.spec);
    updateMatchHint();
  });

  pwConfirm.addEventListener('input', updateMatchHint);

  /* Client-side guard before submit */
  document.getElementById('reset-form').addEventListener('submit', function (e) {
    var p1 = pwInput.value;
    var p2 = pwConfirm.value;
    if (!meetsMinRules(p1)) {
      e.preventDefault();
      pwInput.focus();
      strengthWrap.style.display = 'block';
      return;
    }
    if (p1 !== p2) {
      e.preventDefault();
      matchHint.textContent = isEn ? '× Passwords do not match' : '× Hesla se neshodují';
      matchHint.style.color = '#ef4444';
      pwConfirm.focus();
    }
  });
})();
</script>
</body>
</html>
