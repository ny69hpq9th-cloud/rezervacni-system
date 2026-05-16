<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$slug      = trim($_GET['slug'] ?? '');
$serviceId = (int)($_GET['service_id'] ?? 0);
$date      = trim($_GET['date'] ?? '');

// Validate
if (!preg_match('/^[a-z0-9-]+$/', $slug) || !$serviceId || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Invalid parameters', 'times' => []]);
    exit;
}

// Don't allow past dates
if (strtotime($date) < strtotime('today')) {
    echo json_encode(['times' => []]);
    exit;
}

$db = getDB();

// Get business
$stmt = $db->prepare("SELECT id FROM users WHERE slug = ? AND status = 'active'");
$stmt->execute([$slug]);
$business = $stmt->fetch();

if (!$business) {
    echo json_encode(['error' => 'Business not found', 'times' => []]);
    exit;
}

$times = getAvailableTimes($db, $business['id'], $date, $serviceId);

echo json_encode(['times' => array_values($times)]);
