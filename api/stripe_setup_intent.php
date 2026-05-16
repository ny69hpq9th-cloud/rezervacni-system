<?php
/**
 * Vytvoří Stripe SetupIntent a vrátí client_secret.
 * Volá se z registračního formuláře přes AJAX.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/stripe.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!STRIPE_ENABLED) {
    echo json_encode(['error' => 'Stripe není nastaven.']);
    exit;
}

try {
    $intent = stripeCreateSetupIntent();
    echo json_encode(['client_secret' => $intent['client_secret']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
