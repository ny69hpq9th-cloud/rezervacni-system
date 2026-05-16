<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// CSRF verification (token from JSON body, verified against session)
$token = $input['csrf_token'] ?? '';
if (!verifyCsrf($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Neplatný bezpečnostní token.']);
    exit;
}

// Extract and sanitize
$slug          = trim($input['slug'] ?? '');
$serviceId     = (int)($input['service_id'] ?? 0);
$date          = trim($input['date'] ?? '');
$time          = trim($input['time'] ?? '');
$customerName  = trim($input['customer_name'] ?? '');
$customerEmail = strtolower(trim($input['customer_email'] ?? ''));
$customerPhone = trim($input['customer_phone'] ?? '');
$notes         = trim($input['notes'] ?? '');

// Validation
$errors = [];

if (!preg_match('/^[a-z0-9-]+$/', $slug)) $errors[] = 'Neplatný identifikátor firmy.';
if (!$serviceId)  $errors[] = 'Nevybrána služba.';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'Neplatné datum.';
if (strtotime($date) < strtotime('today'))  $errors[] = 'Datum je v minulosti.';
if (!preg_match('/^\d{2}:\d{2}$/', $time)) $errors[] = 'Neplatný čas.';
if (strlen($customerName) < 2)             $errors[] = 'Zadejte jméno a příjmení.';
if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Neplatný email.';
if (strlen($customerPhone) < 6)            $errors[] = 'Zadejte telefonní číslo.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

$db = getDB();

// Get business
$stmt = $db->prepare("SELECT * FROM users WHERE slug = ? AND status = 'active'");
$stmt->execute([$slug]);
$business = $stmt->fetch();

if (!$business) {
    echo json_encode(['success' => false, 'error' => 'Firma nenalezena.']);
    exit;
}

// Check subscription
if (!hasActiveSubscription($business)) {
    echo json_encode(['success' => false, 'error' => 'Firma momentálně nepřijímá rezervace.']);
    exit;
}

$userId = $business['id'];

// Verify service belongs to this business
$stmt = $db->prepare("SELECT * FROM services WHERE id=? AND user_id=? AND active=1");
$stmt->execute([$serviceId, $userId]);
$service = $stmt->fetch();
if (!$service) {
    echo json_encode(['success' => false, 'error' => 'Neplatná služba.']);
    exit;
}

// Verify time is still available
$available = getAvailableTimes($db, $userId, $date, $serviceId);
if (!in_array($time, $available)) {
    echo json_encode(['success' => false, 'error' => 'Vybraný čas již není dostupný. Prosím zvolte jiný termín.']);
    exit;
}

// Basic plan booking limit
if ($business['plan'] === 'basic') {
    $monthStart = date('Y-m-01');
    $monthEnd   = date('Y-m-t');
    $cnt = $db->prepare(
        "SELECT COUNT(*) FROM bookings WHERE user_id=? AND date BETWEEN ? AND ? AND status != 'cancelled'"
    );
    $cnt->execute([$userId, $monthStart, $monthEnd]);
    if ((int)$cnt->fetchColumn() >= BASIC_MAX_BOOKINGS) {
        echo json_encode(['success' => false, 'error' => 'Firma dosáhla měsíčního limitu rezervací. Zkuste to prosím příští měsíc.']);
        exit;
    }
}

// Sanitize inputs
$customerName  = mb_substr($customerName, 0, 255, 'UTF-8');
$customerPhone = mb_substr($customerPhone, 0, 30, 'UTF-8');
$notes         = mb_substr($notes, 0, 1000, 'UTF-8');

// Create booking
$stmt = $db->prepare(
    "INSERT INTO bookings (user_id,service_id,customer_name,customer_email,customer_phone,date,time,status,notes)
     VALUES (?,?,?,?,?,?,?,?,?)"
);
$stmt->execute([
    $userId, $serviceId,
    $customerName, $customerEmail, $customerPhone,
    $date, $time . ':00',
    'pending',
    $notes ?: null,
]);
$bookingId = (int)$db->lastInsertId();

// Load full booking for emails
$bookingRow = $db->query("SELECT * FROM bookings WHERE id = $bookingId")->fetch();

// Send emails (non-blocking — errors ignored)
try {
    emailBookingCustomer($bookingRow, $service, $business);
} catch (Exception $e) { /* log in production */ }

try {
    emailBookingBusiness($bookingRow, $service, $business);
} catch (Exception $e) { /* log in production */ }

echo json_encode(['success' => true, 'booking_id' => $bookingId]);
