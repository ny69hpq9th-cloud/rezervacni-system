<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$pageTitle = __('nav.settings');
$activeNav = 'settings';

$db  = getDB();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flash('error', __('dash.err_token'));
        header('Location: /dashboard/settings.php'); exit;
    }

    $businessName   = trim($_POST['business_name'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $notifEmail     = strtolower(trim($_POST['notification_email'] ?? ''));
    $accentColor    = $_POST['accent_color'] ?? '#2563eb';
    $customMessage  = trim($_POST['custom_message'] ?? '');

    $allowed_colors = ['#2563eb','#059669','#dc2626','#d97706','#7c3aed'];
    if (!in_array($accentColor, $allowed_colors)) $accentColor = '#2563eb';

    $errors = [];
    if (strlen($businessName) < 2)        $errors[] = __('dash.err_name');
    if ($notifEmail && !filter_var($notifEmail, FILTER_VALIDATE_EMAIL)) $errors[] = __('dash.err_email');

    if (empty($errors)) {
        $logoPath = null;
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            error_log('[LOGO UPLOAD] File received: ' . $_FILES['logo']['name'] . ', size: ' . $_FILES['logo']['size'] . ', tmp: ' . $_FILES['logo']['tmp_name']);
            // finfo is preferred; fall back to mime_content_type() or extension-based detection
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $_FILES['logo']['tmp_name']);
                finfo_close($finfo);
            } elseif (function_exists('mime_content_type')) {
                $mime = mime_content_type($_FILES['logo']['tmp_name']);
            } else {
                // Last-resort: trust browser-supplied MIME (less secure but functional)
                $mime = $_FILES['logo']['type'];
            }
            error_log('[LOGO UPLOAD] Detected MIME: ' . $mime);
            $allowed = ['image/jpeg','image/png','image/gif','image/svg+xml','image/webp'];
            if (!in_array($mime, $allowed)) {
                $errors[] = __('dash.err_logo_type');
                error_log('[LOGO UPLOAD] MIME type rejected: ' . $mime);
            } elseif ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
                $errors[] = __('dash.err_logo_size');
                error_log('[LOGO UPLOAD] File too large: ' . $_FILES['logo']['size']);
            } else {
                $ext       = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $filename  = 'logo_' . $uid . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/logos/';
                error_log('[LOGO UPLOAD] Upload dir: ' . $uploadDir . ', exists: ' . (is_dir($uploadDir) ? 'yes' : 'no') . ', writable: ' . (is_writable(dirname($uploadDir)) ? 'yes' : 'no'));
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        $errors[] = 'Nelze vytvořit složku pro nahrávání. Zkontrolujte oprávnění na Hostingeru (/uploads/).';
                        error_log('[LOGO UPLOAD] mkdir FAILED for: ' . $uploadDir);
                    } else {
                        error_log('[LOGO UPLOAD] mkdir OK: ' . $uploadDir);
                    }
                }
                if (empty($errors)) {
                    $dest = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                        $logoPath = '/uploads/logos/' . $filename;
                        error_log('[LOGO UPLOAD] SUCCESS: ' . $dest);
                    } else {
                        $errors[] = 'Nahrávání souboru selhalo. Zkontrolujte oprávnění složky /uploads/logos/ na Hostingeru (musí být 755).';
                        error_log('[LOGO UPLOAD] move_uploaded_file FAILED: tmp=' . $_FILES['logo']['tmp_name'] . ', dest=' . $dest);
                    }
                }
            }
        } elseif (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            error_log('[LOGO UPLOAD] Upload error code: ' . ($_FILES['logo']['error'] ?? 'n/a'));
        }

        if (empty($errors)) {
            $sql    = "UPDATE users SET business_name=?,description=?,address=?,phone=?,notification_email=?,accent_color=?,custom_message=?";
            $params = [$businessName,$description,$address,$phone,$notifEmail?:null,$accentColor,$customMessage?:null];

            if ($logoPath) { $sql .= ',logo=?'; $params[] = $logoPath; }
            $sql .= ' WHERE id=?'; $params[] = $uid;

            $db->prepare($sql)->execute($params);
            flash('success', __('dash.settings_saved'));
        }
    }

    if (!empty($errors)) flash('error', implode(' ', $errors));
    header('Location: /dashboard/settings.php'); exit;
}

$user = getCurrentUser();

// Color options — must use __array() because __() only returns strings
$colors = __array('dash.colors');
if (empty($colors)) {
    $colors = ['#2563eb'=>'Modrá','#059669'=>'Zelená','#dc2626'=>'Červená','#d97706'=>'Oranžová','#7c3aed'=>'Fialová'];
}

require __DIR__ . '/_layout.php';
?>

<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

    <!-- Basic info -->
    <div class="dash-card">
      <div class="dash-card__header"><div class="dash-card__title"><?= __('dash.basic_info') ?></div></div>
      <div class="dash-card__body">
        <div class="form-group">
          <label class="form-label"><?= __('dash.biz_name') ?> <span>*</span></label>
          <input type="text" name="business_name" class="form-control"
                 value="<?= e($user['business_name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label"><?= __('dash.description') ?></label>
          <textarea name="description" class="form-control" rows="3"
                    placeholder="<?= __('dash.desc_ph') ?>"><?= e($user['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label"><?= __('common.address') ?></label>
          <input type="text" name="address" class="form-control"
                 value="<?= e($user['address'] ?? '') ?>" placeholder="Ul. 123, Praha 1">
        </div>
        <div class="form-group">
          <label class="form-label"><?= __('common.phone') ?></label>
          <input type="tel" name="phone" class="form-control"
                 value="<?= e($user['phone'] ?? '') ?>" placeholder="+420 123 456 789">
        </div>
        <div class="form-group">
          <label class="form-label"><?= __('dash.notif_email') ?></label>
          <input type="email" name="notification_email" class="form-control"
                 value="<?= e($user['notification_email'] ?? '') ?>"
                 placeholder="<?= e($user['email']) ?>">
          <div class="form-hint"><?= __('dash.notif_hint') ?></div>
        </div>
        <div class="form-group">
          <label class="form-label"><?= __('dash.custom_msg') ?></label>
          <textarea name="custom_message" class="form-control" rows="2"
                    placeholder="<?= __('dash.custom_msg_ph') ?>"><?= e($user['custom_message'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Appearance -->
    <div>
      <div class="dash-card" style="margin-bottom:24px">
        <div class="dash-card__header"><div class="dash-card__title"><?= __('dash.logo_section') ?></div></div>
        <div class="dash-card__body">
          <img id="logo-preview-img"
               src="<?= !empty($user['logo']) ? e($user['logo']) : '' ?>"
               alt="Logo"
               class="logo-preview"
               style="width:80px;height:80px;object-fit:contain;margin-bottom:12px;border-radius:8px;<?= empty($user['logo']) ? 'display:none' : '' ?>">
          <label class="logo-upload-area" id="logo-upload-area">
            <input type="file" id="logo-file-input" accept="image/*" style="position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:2">
            <div style="font-size:2rem;margin-bottom:8px">🖼️</div>
            <div style="font-weight:500;margin-bottom:4px" id="logo-upload-label"><?= __('dash.logo_upload') ?></div>
            <div style="font-size:.8rem;color:#94a3b8"><?= __('dash.logo_types') ?></div>
          </label>
          <div id="logo-status" style="margin-top:8px;font-size:.85rem;display:none"></div>
        </div>
      </div>

      <div class="dash-card" style="margin-bottom:24px">
        <div class="dash-card__header"><div class="dash-card__title"><?= __('dash.color_section') ?></div></div>
        <div class="dash-card__body">
          <div id="color-picker-grid" style="display:flex;flex-wrap:wrap;gap:12px;padding:4px 0">
            <?php foreach ($colors as $hex => $name):
                $selected = ($user['accent_color'] ?? '#2563eb') === $hex;
            ?>
              <label title="<?= e($name) ?>" style="cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:5px">
                <input type="radio" name="accent_color" value="<?= e($hex) ?>"
                       style="position:absolute;opacity:0;width:0;height:0"
                       <?= $selected ? 'checked' : '' ?>>
                <span class="color-swatch"
                      style="display:block;width:38px;height:38px;border-radius:50%;background:<?= e($hex) ?>;
                             border:3px solid <?= $selected ? $hex : '#e2e8f0' ?>;
                             box-shadow:<?= $selected ? '0 0 0 3px ' . $hex . '55' : 'none' ?>;
                             transition:all .18s;cursor:pointer"></span>
                <span style="font-size:.7rem;color:#64748b;font-weight:500"><?= e($name) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:12px;font-size:.8rem;color:#64748b"><?= __('dash.color_hint') ?></div>
          <script>
          (function(){
            var grid = document.getElementById('color-picker-grid');
            grid.querySelectorAll('label').forEach(function(label) {
              label.addEventListener('click', function() {
                var radio  = label.querySelector('input[type=radio]');
                var swatch = label.querySelector('.color-swatch');
                var color  = radio.value;
                // Reset all
                grid.querySelectorAll('.color-swatch').forEach(function(s) {
                  s.style.border     = '3px solid #e2e8f0';
                  s.style.boxShadow  = 'none';
                });
                // Mark selected
                swatch.style.border    = '3px solid ' + color;
                swatch.style.boxShadow = '0 0 0 3px ' + color + '55';
                radio.checked = true;
              });
            });
          })();
          </script>
        </div>
      </div>

      <div class="dash-card">
        <div class="dash-card__header"><div class="dash-card__title"><?= __('dash.url_section') ?></div></div>
        <div class="dash-card__body">
          <div class="url-box" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;word-break:break-all">
            <a href="<?= e(PLATFORM_URL . '/rezervace/' . $user['slug']) ?>" target="_blank" class="url-box__link" style="color:#2563eb;font-size:.875rem">
              <?= e(PLATFORM_URL . '/rezervace/' . $user['slug']) ?>
            </a>
          </div>
          <div style="margin-top:12px;font-size:.8rem;color:#94a3b8"><?= __('dash.url_hint') ?></div>
        </div>
      </div>
    </div>
  </div>

  <div style="margin-top:20px">
    <button type="submit" class="btn btn--primary btn--lg"><?= __('dash.save_settings') ?></button>
  </div>
</form>

<script>
(function () {
  var input    = document.getElementById('logo-file-input');
  var preview  = document.getElementById('logo-preview-img');
  var status   = document.getElementById('logo-status');
  var label    = document.getElementById('logo-upload-label');
  var csrfEl   = document.querySelector('[name=csrf_token]');

  if (!input) return;

  input.addEventListener('change', function () {
    var file = input.files[0];
    if (!file) return;

    // Live preview immediately
    var reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);

    // Show uploading state
    setStatus('loading', '<?= addslashes(__('dash.logo_uploading') ?: 'Nahrávám…') ?>');
    label.textContent = file.name;

    // Build FormData
    var fd = new FormData();
    fd.append('logo', file);
    fd.append('csrf_token', csrfEl ? csrfEl.value : '');

    fetch('/api/upload_logo.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          preview.src = data.url;
          preview.style.display = 'block';
          setStatus('success', '<?= addslashes(__('dash.logo_saved') ?: 'Logo uloženo!') ?>');
        } else {
          setStatus('error', data.error || 'Chyba při nahrávání');
          // revert preview if we had no prior logo
        }
      })
      .catch(function () {
        setStatus('error', 'Síťová chyba – zkuste to znovu');
      });
  });

  function setStatus(type, msg) {
    status.style.display = 'block';
    status.textContent   = msg;
    if (type === 'success') {
      status.style.color = '#10b981';
    } else if (type === 'error') {
      status.style.color = '#ef4444';
    } else {
      status.style.color = '#64748b';
    }
  }
})();
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
