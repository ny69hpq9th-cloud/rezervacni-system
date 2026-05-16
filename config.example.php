<?php
// ============================================================
// KONFIGURACE — zkopírujte jako config.php a vyplňte hodnoty
// ============================================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'your_database_name');
define('DB_USER',    'your_database_user');
define('DB_PASS',    'your_database_password');
define('DB_CHARSET', 'utf8mb4');

define('PLATFORM_NAME',  'Rezervly');
define('PLATFORM_TITLE', 'Rezervly – Rezervační systém');
define('PLATFORM_URL',   'https://your-domain.com');
define('PLATFORM_EMAIL', 'info@your-domain.com');
define('MAIL_FROM',      'noreply@your-domain.com');
define('MAIL_FROM_NAME', 'Rezervly');

define('ADMIN_EMAIL',    'admin@your-domain.com');
define('ADMIN_PASSWORD', 'CHANGE_THIS_PASSWORD');

define('TRIAL_DAYS', 14);

define('PLAN_BASIC_PRICE', 199);
define('PLAN_PRO_PRICE',   399);
define('PLAN_BASIC_PRICE_EUR', 8);
define('PLAN_PRO_PRICE_EUR',   16);

define('BASIC_MAX_SERVICES', 2);
define('BASIC_MAX_BOOKINGS', 50);

// SMTP
define('SMTP_HOST',   'smtp.your-provider.com');
define('SMTP_PORT',   465);
define('SMTP_SECURE', 'ssl');
define('SMTP_USER',   'info@your-domain.com');
define('SMTP_PASS',   'your_smtp_password');

// Stripe — https://dashboard.stripe.com/apikeys
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_KEY');
define('STRIPE_SECRET_KEY',      'sk_test_YOUR_KEY');
define('STRIPE_WEBHOOK_SECRET',  'whsec_YOUR_SECRET');

define('STRIPE_ENABLED',
    defined('STRIPE_SECRET_KEY') &&
    strlen(STRIPE_SECRET_KEY) > 20 &&
    STRIPE_SECRET_KEY !== 'sk_test_YOUR_KEY'
);

if (session_status() === PHP_SESSION_NONE) {
    session_name('rezervly_sess');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/seo.php';
