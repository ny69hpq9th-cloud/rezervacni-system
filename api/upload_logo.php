<?php
/**
 * AJAX logo upload endpoint.
 * Called by dashboard/settings.php via fetch().
 * Returns JSON { success: true, url: "..." } or { error: "..." }.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Auth
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Nepřihlášen']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda není povolena']);
    exit;
}

// CSRF
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Neplatný CSRF token – obnovte stránku a zkuste znovu']);
    exit;
}

$uid = (int)$_SESSION['user_id'];

// Check file was received
if (empty($_FILES['logo']['name']) || ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $code = $_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE;
    $msgs = [
        UPLOAD_ERR_INI_SIZE   => 'Soubor je příliš velký (limit serveru)',
        UPLOAD_ERR_FORM_SIZE  => 'Soubor je příliš velký (limit formuláře)',
        UPLOAD_ERR_PARTIAL    => 'Soubor byl nahrán jen částečně',
        UPLOAD_ERR_NO_FILE    => 'Nebyl vybrán žádný soubor',
        UPLOAD_ERR_NO_TMP_DIR => 'Chybí dočasná složka na serveru',
        UPLOAD_ERR_CANT_WRITE => 'Server nemůže zapsat soubor na disk',
        UPLOAD_ERR_EXTENSION  => 'Nahrávání bylo zablokováno PHP rozšířením',
    ];
    echo json_encode(['error' => $msgs[$code] ?? 'Chyba nahrávání (kód ' . $code . ')']);
    exit;
}

// Detect MIME type (several fallbacks for Hostinger compatibility)
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['logo']['tmp_name']);
    finfo_close($finfo);
} elseif (function_exists('mime_content_type')) {
    $mime = mime_content_type($_FILES['logo']['tmp_name']);
} else {
    $mime = $_FILES['logo']['type']; // fallback – trusts browser
}

$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
if (!in_array($mime, $allowed)) {
    echo json_encode(['error' => 'Nepodporovaný formát. Povoleno: JPG, PNG, GIF, SVG, WebP (detekováno: ' . $mime . ')']);
    exit;
}

// Size limit: 2 MB
if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
    echo json_encode(['error' => 'Soubor je příliš velký. Maximum je 2 MB.']);
    exit;
}

// Build path
$ext       = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
$filename  = 'logo_' . $uid . '_' . time() . '.' . $ext;
$uploadDir = __DIR__ . '/../uploads/logos/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        error_log('[LOGO UPLOAD] mkdir FAILED: ' . $uploadDir . ' | is_writable parent: ' . (is_writable(dirname($uploadDir)) ? 'yes' : 'no'));
        echo json_encode(['error' => 'Nelze vytvořit složku /uploads/logos/ — nastavte oprávnění 755 v File Manageru Hostingeru']);
        exit;
    }
    // Place an empty index.html to prevent directory listing
    file_put_contents($uploadDir . 'index.html', '');
}

$dest = $uploadDir . $filename;
if (!move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
    error_log('[LOGO UPLOAD] move_uploaded_file FAILED: src=' . $_FILES['logo']['tmp_name'] . ' dest=' . $dest);
    echo json_encode(['error' => 'Nahrávání selhalo — zkontrolujte oprávnění složky /uploads/logos/ (musí být 755)']);
    exit;
}

$logoPath = '/uploads/logos/' . $filename;

// Persist to DB
try {
    $db = getDB();
    $db->prepare("UPDATE users SET logo = ? WHERE id = ?")->execute([$logoPath, $uid]);
    error_log('[LOGO UPLOAD] OK: user=' . $uid . ' path=' . $logoPath);
    echo json_encode(['success' => true, 'url' => $logoPath]);
} catch (Exception $e) {
    error_log('[LOGO UPLOAD] DB error: ' . $e->getMessage());
    echo json_encode(['error' => 'Soubor nahrán ale chyba při uložení do DB: ' . $e->getMessage()]);
}
