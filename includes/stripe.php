<?php
/**
 * Stripe API helper — přímé cURL volání bez SDK.
 * Plně funkční pro Hostinger shared hosting.
 */
require_once __DIR__ . '/../config.php';

// ============================================================
// CORE REQUEST
// ============================================================

function stripeRequest(string $method, string $endpoint, array $data = []): array {
    if (!STRIPE_ENABLED) {
        throw new RuntimeException('Stripe není nakonfigurován. Nastavte klíče v config.php.');
    }

    $url = 'https://api.stripe.com/v1/' . $endpoint;
    $ch  = curl_init();

    $curlOpts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . STRIPE_SECRET_KEY,
            'Stripe-Version: 2024-06-20',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ];

    if ($method === 'POST') {
        $curlOpts[CURLOPT_POST]       = true;
        $curlOpts[CURLOPT_POSTFIELDS] = http_build_query($data);
    } elseif ($method === 'DELETE') {
        $curlOpts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        if ($data) $curlOpts[CURLOPT_POSTFIELDS] = http_build_query($data);
    } elseif ($method === 'GET' && $data) {
        $url .= '?' . http_build_query($data);
    }

    $curlOpts[CURLOPT_URL] = $url;
    curl_setopt_array($ch, $curlOpts);

    $response = curl_exec($ch);
    $errno    = curl_errno($ch);
    $errMsg   = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        throw new RuntimeException('Stripe cURL chyba: ' . $errMsg);
    }

    $result = json_decode($response, true);
    if (isset($result['error'])) {
        throw new RuntimeException($result['error']['message'] ?? 'Neznámá chyba Stripe');
    }

    return $result;
}

// ============================================================
// SETUP INTENT — bezpečné uložení karty (podporuje 3D Secure)
// ============================================================

function stripeCreateSetupIntent(): array {
    return stripeRequest('POST', 'setup_intents', [
        'usage'    => 'off_session',
        'automatic_payment_methods[enabled]' => 'true',
        'automatic_payment_methods[allow_redirects]' => 'never',
    ]);
}

// ============================================================
// ZÁKAZNÍK
// ============================================================

function stripeCreateCustomer(string $email, string $name, string $paymentMethodId): array {
    return stripeRequest('POST', 'customers', [
        'email'                                         => $email,
        'name'                                          => $name,
        'payment_method'                                => $paymentMethodId,
        'invoice_settings[default_payment_method]'      => $paymentMethodId,
    ]);
}

// ============================================================
// PŘEDPLATNÉ
// ============================================================

/**
 * Vytvoří Stripe Subscription s trialem.
 *
 * Subscriptions endpoint nepodporuje price_data[product_data] — vyžaduje
 * existující product ID. Proto nejprve vytvoříme produkt, pak subscription.
 */
function stripeCreateSubscription(string $customerId, string $plan, int $trialDays = 14): array {
    $amountCents = ($plan === 'pro') ? PLAN_PRO_PRICE * 100 : PLAN_BASIC_PRICE * 100;
    $productName = PLATFORM_NAME . ' ' . ($plan === 'pro' ? 'Pro' : 'Basic');

    // Krok 1: Vytvořit produkt (/products podporuje product_data inline)
    $product = stripeRequest('POST', 'products', [
        'name' => $productName,
    ]);

    // Krok 2: Vytvořit subscription s price_data[product] = ID produktu
    return stripeRequest('POST', 'subscriptions', [
        'customer'                                           => $customerId,
        'trial_period_days'                                  => $trialDays,
        'items[0][price_data][currency]'                     => 'czk',
        'items[0][price_data][product]'                      => $product['id'],
        'items[0][price_data][unit_amount]'                  => $amountCents,
        'items[0][price_data][recurring][interval]'          => 'month',
        'payment_settings[save_default_payment_method]'      => 'on_subscription',
        'payment_settings[payment_method_types][0]'          => 'card',
        'expand[0]'                                          => 'latest_invoice.payment_intent',
    ]);
}

/**
 * Zruší předplatné na konci aktuálního období (uživatel přijde o přístup po skončení).
 */
function stripeCancelAtPeriodEnd(string $subscriptionId): array {
    return stripeRequest('POST', 'subscriptions/' . $subscriptionId, [
        'cancel_at_period_end' => 'true',
    ]);
}

/**
 * Okamžité zrušení (bez vrácení peněz).
 */
function stripeCancelNow(string $subscriptionId): array {
    return stripeRequest('DELETE', 'subscriptions/' . $subscriptionId);
}

function stripeGetSubscription(string $subscriptionId): array {
    return stripeRequest('GET', 'subscriptions/' . $subscriptionId, [
        'expand[0]' => 'latest_invoice',
    ]);
}

// ============================================================
// WEBHOOK OVĚŘENÍ
// ============================================================

/**
 * Ověří podpis Stripe webhooku (HMAC-SHA256).
 * Vrátí true pokud je podpis platný.
 */
function stripeVerifyWebhook(string $payload, string $sigHeader, string $secret): bool {
    if (empty($sigHeader) || empty($secret)) return false;

    $timestamp  = null;
    $signatures = [];

    foreach (explode(',', $sigHeader) as $part) {
        $kv = explode('=', trim($part), 2);
        if (count($kv) !== 2) continue;
        if ($kv[0] === 't')  $timestamp    = $kv[1];
        if ($kv[0] === 'v1') $signatures[] = $kv[1];
    }

    if (!$timestamp || empty($signatures)) return false;

    // Odmítnout zprávy starší než 5 minut
    if (abs(time() - (int)$timestamp) > 300) return false;

    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

    foreach ($signatures as $sig) {
        if (hash_equals($expected, $sig)) return true;
    }
    return false;
}
