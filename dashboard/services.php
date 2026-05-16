<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$pageTitle = __('nav.services');
$activeNav = 'services';

$db  = getDB();
$uid = $_SESSION['user_id'];
$user = getCurrentUser();

// Plan limits
$isPro     = $user['plan'] === 'pro' || hasActiveSubscription($user) && $user['plan'] !== 'basic';
$maxSvcs   = ($user['plan'] === 'basic') ? BASIC_MAX_SERVICES : PHP_INT_MAX;

// Count current services
$countStmt = $db->prepare("SELECT COUNT(*) FROM services WHERE user_id=?");
$countStmt->execute([$uid]);
$svcCount = (int)$countStmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        flash('error', __('dash.err_token'));
        header('Location: /dashboard/services.php'); exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $duration = (int)($_POST['duration'] ?? 30);
        $price    = (float)str_replace(',', '.', $_POST['price'] ?? '0');
        $desc     = trim($_POST['description'] ?? '');
        $active   = isset($_POST['active']) ? 1 : 0;

        if (!$name) { flash('error', __('dash.err_name')); header('Location: /dashboard/services.php'); exit; }
        if (!in_array($duration, [15,30,45,60,90,120])) $duration = 30;

        if ($id) {
            $chk = $db->prepare("SELECT id FROM services WHERE id=? AND user_id=?");
            $chk->execute([$id, $uid]);
            if ($chk->fetch()) {
                $db->prepare("UPDATE services SET name=?,duration=?,price=?,description=?,active=? WHERE id=?")
                   ->execute([$name,$duration,$price,$desc,$active,$id]);
                flash('success', __('dash.svc_updated'));
            }
        } else {
            if ($svcCount >= $maxSvcs && $user['plan'] === 'basic') {
                flash('error', __('dash.plan_limit', ['n' => BASIC_MAX_SERVICES]));
            } else {
                $db->prepare("INSERT INTO services (user_id,name,duration,price,description,active) VALUES (?,?,?,?,?,?)")
                   ->execute([$uid,$name,$duration,$price,$desc,$active]);
                flash('success', __('dash.svc_added'));
            }
        }
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE services SET active = 1-active WHERE id=? AND user_id=?")->execute([$id,$uid]);
        flash('success', __('dash.svc_toggled'));
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM services WHERE id=? AND user_id=?")->execute([$id,$uid]);
        flash('success', __('dash.svc_deleted'));
    }

    header('Location: /dashboard/services.php'); exit;
}

// Load services
$stmt = $db->prepare("SELECT * FROM services WHERE user_id=? ORDER BY sort_order,name");
$stmt->execute([$uid]);
$services = $stmt->fetchAll();

// Edit mode?
$editId  = (int)($_GET['edit'] ?? 0);
$editSvc = null;
if ($editId) {
    foreach ($services as $s) {
        if ($s['id'] === $editId) { $editSvc = $s; break; }
    }
}
$showForm      = isset($_GET['new']) || $editSvc;
$presetName    = trim($_GET['preset_name'] ?? '');
$presetDuration = in_array((int)($_GET['preset_duration'] ?? 0), [15,30,45,60,90,120])
    ? (int)$_GET['preset_duration'] : 30;

// Template categories (cs/en)
$isEn = currentLang() === 'en';
$templateCategories = [
    ['id'=>'hairdressing','icon'=>'✂️','name'=>$isEn?'Hairdressing':'Kadeřnictví','services'=>[
        [$isEn?'Haircut':'Střih vlasů',45],[$isEn?'Hair coloring':'Barvení vlasů',120],
        [$isEn?'Highlights / balayage':'Melír a balayage',90],[$isEn?'Blow-dry':'Foukaná',30],
        [$isEn?'Keratin treatment':'Keratin',120],[$isEn?'Perm':'Trvalá ondulace',90],
    ]],
    ['id'=>'beauty','icon'=>'💅','name'=>$isEn?'Beauty & Nails':'Kosmetika','services'=>[
        [$isEn?'Classic manicure':'Manikúra klasická',60],[$isEn?'Gel manicure':'Manikúra gelová',60],
        [$isEn?'Pedicure':'Pedikúra',60],[$isEn?'Eyelash extensions':'Prodlužování řas',90],
        [$isEn?'Lash lamination':'Laminace řas',60],[$isEn?'Eyebrow tinting':'Barvení obočí',30],
        [$isEn?'Wax depilation':'Depilace voskem',30],[$isEn?'Facial cleanse':'Čištění pleti',60],
    ]],
    ['id'=>'massage','icon'=>'💆','name'=>$isEn?'Massage':'Masáže','services'=>[
        [$isEn?'Classic massage':'Klasická masáž',60],[$isEn?'Sports massage':'Sportovní masáž',60],
        [$isEn?'Relaxation massage':'Relaxační masáž',60],[$isEn?'Back & neck massage':'Masáž zad a šíje',45],
        [$isEn?'Full-body massage':'Celotělová masáž',90],[$isEn?'Lymphatic massage':'Lymfatická masáž',60],
        [$isEn?'Hot stone massage':'Masáž lávovými kameny',90],
    ]],
    ['id'=>'physio','icon'=>'🏃','name'=>$isEn?'Physiotherapy':'Fyzioterapie','services'=>[
        [$isEn?'Physiotherapy session':'Fyzioterapeutické cvičení',60],
        [$isEn?'Rehabilitation':'Rehabilitace',45],[$isEn?'Initial assessment':'Vstupní diagnostika',30],
        [$isEn?'Manual therapy':'Manuální terapie',45],[$isEn?'Therapeutic exercise':'Léčebná tělesná výchova',60],
    ]],
    ['id'=>'fitness','icon'=>'💪','name'=>$isEn?'Fitness':'Fitness','services'=>[
        [$isEn?'Personal training':'Osobní trénink',60],[$isEn?'Group class':'Skupinová lekce',60],
        [$isEn?'Yoga':'Yoga',60],[$isEn?'Pilates':'Pilates',60],
        [$isEn?'Functional training':'Funkční trénink',60],[$isEn?'Spinning':'Spinning',45],
    ]],
    ['id'=>'tattoo','icon'=>'🔮','name'=>$isEn?'Tattoo & Piercing':'Tetování a piercing','services'=>[
        [$isEn?'Tattoo session':'Tetování',120],[$isEn?'Small tattoo':'Malé tetování',60],
        [$isEn?'Cover-up tattoo':'Coverup tetování',120],[$isEn?'Piercing':'Piercing',30],
        [$isEn?'Tattoo consultation':'Konzultace motivu',30],
    ]],
    ['id'=>'photo','icon'=>'📸','name'=>$isEn?'Photography':'Fotografování','services'=>[
        [$isEn?'Portrait session':'Portrétní focení',60],[$isEn?'Product photography':'Produktová fotografie',120],
        [$isEn?'Family photography':'Rodinné focení',90],[$isEn?'Corporate photography':'Firemní fotografie',120],
        [$isEn?'Event coverage':'Reportáž z akce',120],
    ]],
    ['id'=>'consulting','icon'=>'💬','name'=>$isEn?'Consulting':'Konzultace','services'=>[
        [$isEn?'Legal consultation':'Právní konzultace',60],[$isEn?'Financial advisory':'Finanční poradenství',60],
        [$isEn?'Psychological session':'Psychologická konzultace',60],
        [$isEn?'Life coaching':'Koučování',60],[$isEn?'Nutritional consultation':'Nutriční konzultace',45],
    ]],
    ['id'=>'vet','icon'=>'🐾','name'=>$isEn?'Veterinary':'Veterina','services'=>[
        [$isEn?'Routine check-up':'Preventivní prohlídka',30],[$isEn?'Vaccination':'Očkování',15],
        [$isEn?'Vet consultation':'Veterinární konzultace',30],[$isEn?'Dental treatment':'Dentální ošetření',60],
    ]],
    ['id'=>'auto','icon'=>'🚗','name'=>$isEn?'Auto Service':'Autoservis','services'=>[
        [$isEn?'Oil change':'Výměna oleje',30],[$isEn?'Vehicle diagnostics':'Diagnostika vozidla',45],
        [$isEn?'Tyre change':'Výměna pneumatik',30],[$isEn?'MOT preparation':'Příprava na STK',120],
        [$isEn?'Car wash & detailing':'Mytí a čištění vozu',60],
    ]],
    ['id'=>'tutoring','icon'=>'📚','name'=>$isEn?'Tutoring & Lessons':'Výuka a lekce','services'=>[
        [$isEn?'Language lesson':'Výuka jazyků',60],[$isEn?'Music lesson':'Hudební lekce',60],
        [$isEn?'Maths tutoring':'Doučování matematiky',60],[$isEn?'Sports coaching':'Sportovní trénink',60],
        [$isEn?'Driving lesson':'Řidičský výcvik',60],
    ]],
    ['id'=>'other','icon'=>'✨','name'=>$isEn?'Other':'Jiné','services'=>[
        [$isEn?'Custom service':'Vlastní služba',30],[$isEn?'Consultation':'Konzultace',30],
        [$isEn?'Appointment':'Schůzka',60],
    ]],
];

// UI labels for the modal
$tmplTitle = $isEn ? 'Pick service template' : 'Vybrat šablonu služby';
$tmplStep1 = $isEn ? 'Select a category' : 'Vyberte kategorii';
$tmplStep2 = $isEn ? 'Select a service' : 'Vyberte službu';
$tmplBack  = $isEn ? '← Back to categories' : '← Zpět na kategorie';
$tmplHint  = $isEn ? 'You can edit the name and duration after selection.'
                   : 'Název a délku si po výběru upravte dle potřeby.';
$tmplBtnLbl = $isEn ? 'Templates' : 'Ze šablon';
$tmplCustom = $isEn ? '+ Custom service' : '+ Vlastní služba';

require __DIR__ . '/_layout.php';

$durations = [
    15  => '15 min',
    30  => '30 min',
    45  => '45 min',
    60  => formatDuration(60),
    90  => formatDuration(90),
    120 => formatDuration(120),
];
?>

<!-- Plan limit warning -->
<?php if ($user['plan'] === 'basic' && $svcCount >= BASIC_MAX_SERVICES && !$showForm): ?>
  <div class="alert alert--warning">
    <span class="alert__icon">⚠</span>
    <?= __('dash.plan_limit', ['n' => BASIC_MAX_SERVICES]) ?>
  </div>
<?php endif; ?>

<!-- Add/Edit form -->
<?php if ($showForm): ?>
<div class="dash-card" style="margin-bottom:24px">
  <div class="dash-card__header">
    <div class="dash-card__title"><?= $editSvc ? __('dash.edit_service') : __('dash.add_new_service') ?></div>
  </div>
  <div class="dash-card__body">
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save">
      <?php if ($editSvc): ?>
        <input type="hidden" name="id" value="<?= $editSvc['id'] ?>">
      <?php endif; ?>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label"><?= __('dash.svc_name') ?> <span>*</span></label>
          <input type="text" name="name" class="form-control"
                 value="<?= e($editSvc['name'] ?? $presetName) ?>" placeholder="<?= __('dash.svc_name_ph') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label"><?= __('dash.duration') ?></label>
          <select name="duration" class="form-control">
            <?php foreach ($durations as $val=>$label): ?>
              <option value="<?= $val ?>" <?= ($editSvc['duration'] ?? $presetDuration) == $val ? 'selected':'' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label"><?= __('dash.price_label') ?> (<?= langCurrencySymbol() ?>)</label>
          <input type="number" name="price" class="form-control" min="0" step="0.01"
                 value="<?= e($editSvc['price'] ?? '0') ?>" placeholder="0">
          <div class="form-hint"><?= __('dash.price_hint') ?></div>
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;gap:12px;padding-bottom:18px">
          <label class="toggle">
            <input type="checkbox" name="active" class="toggle__input" <?= (!isset($editSvc) || $editSvc['active']) ? 'checked' : '' ?>>
            <span class="toggle__slider"></span>
            <span class="toggle__label"><?= __('dash.active_label') ?></span>
          </label>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label"><?= __('dash.description') ?> (<?= __('common.optional') ?>)</label>
        <textarea name="description" class="form-control" rows="3" placeholder="<?= __('dash.desc_ph') ?>"><?= e($editSvc['description'] ?? '') ?></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn--primary">
          <?= $editSvc ? __('dash.save_changes') : __('dash.add_svc_btn') ?>
        </button>
        <a href="/dashboard/services.php" class="btn btn--ghost"><?= __('dash.cancel_btn') ?></a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Services list -->
<div class="dash-card">
  <div class="dash-card__header">
    <div class="dash-card__title"><?= __('dash.my_services') ?> (<?= count($services) ?>)</div>
    <?php if (!$showForm && ($user['plan'] !== 'basic' || $svcCount < BASIC_MAX_SERVICES)): ?>
      <div style="display:flex;gap:8px">
        <button type="button" onclick="openTmplModal()" class="btn btn--outline btn--sm" style="display:flex;align-items:center;gap:6px">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          <?= e($tmplBtnLbl) ?>
        </button>
        <a href="?new=1" class="btn btn--primary btn--sm"><?= e($tmplCustom) ?></a>
      </div>
    <?php endif; ?>
  </div>

  <?php if (empty($services)): ?>
    <div class="empty-state">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
      <h3><?= __('dash.no_services') ?></h3>
      <p><?= __('dash.no_svc_sub') ?></p>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
        <button type="button" onclick="openTmplModal()" class="btn btn--outline" style="display:flex;align-items:center;gap:6px">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          <?= e($tmplBtnLbl) ?>
        </button>
        <a href="?new=1" class="btn btn--primary"><?= __('dash.add_first') ?></a>
      </div>
    </div>
  <?php else: ?>
    <div class="dash-card__body">
      <div class="services-grid">
        <?php foreach ($services as $s): ?>
          <div class="service-card" style="<?= !$s['active'] ? 'opacity:.6' : '' ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
              <div class="service-card__name"><?= e($s['name']) ?></div>
              <span class="badge <?= $s['active'] ? 'badge--success' : 'badge--default' ?>">
                <?= $s['active'] ? __('dash.active_badge') : __('dash.inactive_badge') ?>
              </span>
            </div>
            <div class="service-card__meta">
              <div class="service-card__meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
                <?= formatDuration((int)$s['duration']) ?>
              </div>
              <?php if ($s['price'] > 0): ?>
                <div class="service-card__meta-item" style="color:#2563eb;font-weight:600">
                  <?= formatPrice((float)$s['price']) ?>
                </div>
              <?php else: ?>
                <div class="service-card__meta-item"><?= __('dash.free_on_agree') ?></div>
              <?php endif; ?>
            </div>
            <?php if ($s['description']): ?>
              <div class="service-card__desc"><?= e($s['description']) ?></div>
            <?php endif; ?>
            <div class="service-card__footer">
              <div class="service-card__actions">
                <a href="?edit=<?= $s['id'] ?>" class="btn btn--outline btn--sm"><?= __('common.edit') ?></a>
                <form method="POST" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
                  <button class="btn btn--ghost btn--sm"><?= $s['active'] ? __('dash.deactivate') : __('dash.activate') ?></button>
                </form>
                <form method="POST" style="display:inline" onsubmit="return confirm('<?= __('dash.delete_confirm') ?>')">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
                  <button class="btn btn--danger btn--sm"><?= __('common.delete') ?></button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- ── Template picker modal ────────────────────────────────────────────── -->
<div id="tmpl-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(15,23,42,.5);align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border-radius:16px;width:100%;max-width:640px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.18)">

    <!-- Header -->
    <div style="padding:18px 22px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
      <div>
        <div style="font-size:1rem;font-weight:700;color:#0f172a"><?= e($tmplTitle) ?></div>
        <div id="tmpl-sub" style="font-size:.78rem;color:#64748b;margin-top:2px"><?= e($tmplStep1) ?></div>
      </div>
      <button onclick="closeTmplModal()" style="width:30px;height:30px;border:none;background:#f1f5f9;border-radius:8px;cursor:pointer;font-size:1rem;color:#64748b;display:flex;align-items:center;justify-content:center">✕</button>
    </div>

    <!-- Step 1: Category grid -->
    <div id="tmpl-step1" style="padding:18px 22px;overflow-y:auto;flex:1">
      <div id="tmpl-cats" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px"></div>
    </div>

    <!-- Step 2: Service list -->
    <div id="tmpl-step2" style="display:none;padding:18px 22px;overflow-y:auto;flex:1">
      <button onclick="tmplBack()" style="background:none;border:none;cursor:pointer;color:#2563eb;font-size:.82rem;font-weight:600;padding:0;margin-bottom:14px;display:flex;align-items:center;gap:4px">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15,18 9,12 15,6"/></svg>
        <?= e($tmplBack) ?>
      </button>
      <p style="font-size:.78rem;color:#94a3b8;margin-bottom:12px"><?= e($tmplHint) ?></p>
      <div id="tmpl-svcs"></div>
    </div>
  </div>
</div>

<script>
(function () {
  const TEMPLATES = <?= json_encode($templateCategories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const SUB_STEP1 = <?= json_encode($tmplStep1) ?>;
  const modal     = document.getElementById('tmpl-modal');

  window.openTmplModal = function () {
    modal.style.display = 'flex';
    showCats();
  };
  window.closeTmplModal = function () {
    modal.style.display = 'none';
  };
  window.tmplBack = function () {
    showCats();
  };

  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeTmplModal();
  });

  function showCats() {
    document.getElementById('tmpl-step1').style.display = 'block';
    document.getElementById('tmpl-step2').style.display = 'none';
    document.getElementById('tmpl-sub').textContent = SUB_STEP1;

    var catsEl = document.getElementById('tmpl-cats');
    catsEl.innerHTML = TEMPLATES.map(function (cat, idx) {
      return '<button type="button" data-cat-idx="' + idx + '" style="'
        + 'display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;'
        + 'padding:16px 10px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;'
        + 'cursor:pointer;transition:all .15s;text-align:center;width:100%;">'
        + '<span style="font-size:1.8rem;line-height:1">' + cat.icon + '</span>'
        + '<span style="font-size:.78rem;font-weight:600;color:#0f172a;line-height:1.3">' + escHtml(cat.name) + '</span>'
        + '<span style="font-size:.7rem;color:#94a3b8">' + cat.services.length + <?= json_encode($isEn ? '" services"' : '" služeb"') ?> + '</span>'
        + '</button>';
    }).join('');

    catsEl.querySelectorAll('[data-cat-idx]').forEach(function(btn) {
      btn.addEventListener('mouseover', function() { btn.style.borderColor='#2563eb'; btn.style.background='#eff6ff'; });
      btn.addEventListener('mouseout',  function() { btn.style.borderColor='#e2e8f0'; btn.style.background='#f8fafc'; });
      btn.addEventListener('click', function() {
        showSvcs(parseInt(btn.dataset.catIdx));
      });
    });
  }

  window.showSvcs = function (catIdx) {
    var cat = TEMPLATES[catIdx];
    if (!cat) return;

    document.getElementById('tmpl-step1').style.display = 'none';
    document.getElementById('tmpl-step2').style.display = 'block';
    document.getElementById('tmpl-sub').textContent = cat.name;

    var svcsEl = document.getElementById('tmpl-svcs');
    svcsEl.innerHTML = cat.services.map(function (svc, idx) {
      return '<button type="button" data-svc-idx="' + idx + '" style="'
        + 'display:flex;justify-content:space-between;align-items:center;width:100%;'
        + 'padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;'
        + 'cursor:pointer;margin-bottom:8px;text-align:left;transition:all .15s;">'
        + '<span style="font-weight:600;font-size:.875rem;color:#0f172a">' + escHtml(svc[0]) + '</span>'
        + '<span style="font-size:.78rem;color:#64748b;background:#f1f5f9;padding:3px 10px;border-radius:20px;white-space:nowrap;margin-left:12px">' + fmtDur(svc[1]) + '</span>'
        + '</button>';
    }).join('');

    svcsEl.querySelectorAll('[data-svc-idx]').forEach(function(btn) {
      btn.addEventListener('mouseover', function() { btn.style.borderColor='#2563eb'; btn.style.background='#eff6ff'; });
      btn.addEventListener('mouseout',  function() { btn.style.borderColor='#e2e8f0'; btn.style.background='#fff'; });
      btn.addEventListener('click', function() {
        var svc = cat.services[parseInt(btn.dataset.svcIdx)];
        selectTmpl(svc[0], svc[1]);
      });
    });
  };

  window.selectTmpl = function (name, duration) {
    closeTmplModal();
    var url = new URL(window.location.href);
    url.search = '';
    url.searchParams.set('new', '1');
    url.searchParams.set('preset_name', name);
    url.searchParams.set('preset_duration', duration);
    window.location.href = url.toString();
  };

  function fmtDur(min) {
    if (min < 60) return min + ' min';
    var h = Math.floor(min / 60), m = min % 60;
    return m ? h + ' h ' + m + ' min' : h + ' h';
  }
  function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
})();
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
