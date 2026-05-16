<?php
/**
 * Zruší Stripe předplatné na konci aktuálního období.
 * POST /api/stripe_cancel.php
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/stripe.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

if (!isLoggedIn()) {
    http_response_code(401); echo json_encode(['error' => 'Nepřihlášen']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!verifyCsrf($input['csrf_token'] ?? '')) {
    http_response_code(403); echo json_encode(['error' => 'Neplatný token']); exit;
}

$db   = getDB();
$user = getCurrentUser();
if (!$user) { http_response_code(401); echo json_encode(['error' => 'Uživatel nenalezen']); exit; }

$subId = $user['stripe_subscription_id'] ?? null;
if (!$subId) {
    echo json_encode(['success' => false, 'error' => 'Žádné aktivní předplatné.']);
    exit;
}

try {
    if (!STRIPE_ENABLED) throw new RuntimeException('Stripe není nakonfigurován.');

    $result = stripeCancelAtPeriodEnd($subId);
    $endDate = isset($result['current_period_end'])
        ? date('j. n. Y', $result['current_period_end'])
        : null;

    // Update local subscription record
    $db->prepare("UPDATE subscriptions SET status='cancelled' WHERE user_id=? AND stripe_subscription_id=?")
       ->execute([$user['id'], $subId]);

    echo json_encode([
        'success'  => true,
        'end_date' => $endDate,
        'message'  => 'Předplatné bude ukončeno ' . ($endDate ?? 'na konci období') . '.',
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
