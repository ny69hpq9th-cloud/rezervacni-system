<?php
/**
 * Stripe Webhook handler.
 *
 * V Stripe dashboardu nastavte endpoint na:
 *   https://vasedomena.cz/api/stripe_webhook.php
 *
 * Sledované události:
 *   - customer.subscription.created       ← orphan-detection (user must exist)
 *   - customer.subscription.trial_will_end
 *   - invoice.payment_succeeded
 *   - invoice.payment_failed
 *   - customer.subscription.deleted
 *   - customer.subscription.updated
 */

// Musí být PŘED jakýmkoliv output bufferingem
$rawPayload = file_get_contents('php://input');
$sigHeader  = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../includes/stripe.php';

header('Content-Type: application/json');

if (!STRIPE_ENABLED) {
    http_response_code(503);
    echo json_encode(['error' => 'Stripe disabled']);
    exit;
}

// Ověření podpisu
if (!stripeVerifyWebhook($rawPayload, $sigHeader, STRIPE_WEBHOOK_SECRET)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$event = json_decode($rawPayload, true);
if (!$event || !isset($event['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$db = getDB();

// ============================================================
// Helper: najít uživatele podle stripe_customer_id
// ============================================================
function getUserByCustomer(PDO $db, string $customerId): ?array {
    $stmt = $db->prepare("SELECT * FROM users WHERE stripe_customer_id = ?");
    $stmt->execute([$customerId]);
    return $stmt->fetch() ?: null;
}

// ============================================================
// ZPRACOVÁNÍ UDÁLOSTÍ
// ============================================================
$obj  = $event['data']['object'];
$type = $event['type'];

try {
    switch ($type) {

        // ----------------------------------------------------------
        // Nové předplatné vytvořeno — zkontroluj, že uživatel v DB existuje.
        // Pokud ne, jde o "orphan" subscription (registrace selhala po Stripe
        // volání) → zaloguj CRITICAL a pošli admin email.
        // ----------------------------------------------------------
        case 'customer.subscription.created':
            $customerId     = $obj['customer'];
            $subscriptionId = $obj['id'];
            $user = getUserByCustomer($db, $customerId);

            if (!$user) {
                // Pokus najít uživatele přes stripe_subscription_id (race condition fix)
                $stmt = $db->prepare("SELECT * FROM users WHERE stripe_subscription_id = ?");
                $stmt->execute([$subscriptionId]);
                $user = $stmt->fetch() ?: null;
            }

            if (!$user) {
                // Orphan subscription — uživatel není v DB!
                $msg = '[STRIPE CRITICAL] customer.subscription.created: No DB user for '
                     . 'customer=' . $customerId . ', subscription=' . $subscriptionId
                     . '. Registration may have failed AFTER Stripe call. Manual review needed.';
                error_log($msg);

                // Pošli admin email
                try {
                    $body = emailTemplate('⚠️ Orphan Stripe subscription detected', "
                        <p>Byl vytvořen Stripe zákazník a předplatné, ale v databázi neexistuje odpovídající uživatel.</p>
                        <p><strong>Customer ID:</strong> " . htmlspecialchars($customerId) . "<br>
                        <strong>Subscription ID:</strong> " . htmlspecialchars($subscriptionId) . "</p>
                        <p>Zkontrolujte Stripe dashboard a PHP error log pro více informací.<br>
                        Zvažte zrušení předplatného nebo manuální vytvoření uživatele.</p>
                        <p style='color:#6b7280;font-size:13px'>Tato zpráva byla vygenerována automaticky webhookem " . PLATFORM_NAME . ".</p>
                    ");
                    sendMail(ADMIN_EMAIL, '[' . PLATFORM_NAME . '] ⚠️ Orphan Stripe subscription – akce vyžadována', $body);
                } catch (Exception $mailEx) {
                    error_log('[STRIPE CRITICAL] Failed to send admin alert email: ' . $mailEx->getMessage());
                }

            } else {
                // Uživatel existuje — ujisti se, že má subscription ID uložené
                if (empty($user['stripe_subscription_id'])) {
                    $db->prepare("UPDATE users SET stripe_subscription_id=? WHERE id=?")
                       ->execute([$subscriptionId, $user['id']]);
                    error_log('[STRIPE] customer.subscription.created: Updated subscription ID for user #' . $user['id']);
                }
            }
            break;

        // ----------------------------------------------------------
        // Trial se blíží ke konci — 3 dny před koncem
        // ----------------------------------------------------------
        case 'customer.subscription.trial_will_end':
            $user = getUserByCustomer($db, $obj['customer']);
            if (!$user) break;

            $trialEnd  = date('j. n. Y', $obj['trial_end']);
            $plan      = $obj['metadata']['plan'] ?? $user['plan'];
            $price     = ($plan === 'pro') ? PLAN_PRO_PRICE : PLAN_BASIC_PRICE;
            $dashUrl   = PLATFORM_URL . '/dashboard/subscription.php';

            $body = emailTemplate('Trial se blíží ke konci', "
                <p>Dobrý den, <strong>" . e($user['business_name']) . "</strong>,</p>
                <p>Váš bezplatný trial vyprší <strong>{$trialEnd}</strong>.</p>
                <p>Po skončení trialu bude z vaší karty automaticky strženo <strong>" . formatPrice($price) . "</strong> za měsíční předplatné " . PLATFORM_NAME . ".</p>
                <p>Pokud si nepřejete pokračovat, zrušte předplatné před tímto datem v nastavení účtu:</p>
                <p><a href='{$dashUrl}' style='display:inline-block;padding:12px 28px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-weight:600'>Správa předplatného</a></p>
                <p style='color:#6b7280;font-size:14px'>Chcete pokračovat? Nemusíte nic dělat — platba proběhne automaticky.</p>
            ");
            sendMail($user['email'], 'Trial vyprší za 3 dny – ' . PLATFORM_NAME, $body);
            break;

        // ----------------------------------------------------------
        // Platba proběhla úspěšně
        // ----------------------------------------------------------
        case 'invoice.payment_succeeded':
            if ($obj['billing_reason'] === 'subscription_create') break; // první faktura je jen nastavení

            $customerId = $obj['customer'];
            $user = getUserByCustomer($db, $customerId);
            if (!$user) break;

            $periodEnd  = isset($obj['lines']['data'][0]['period']['end'])
                ? date('Y-m-d H:i:s', $obj['lines']['data'][0]['period']['end'])
                : date('Y-m-d H:i:s', strtotime('+1 month'));

            $amount = round($obj['amount_paid'] / 100);
            $plan   = ($amount >= PLAN_PRO_PRICE) ? 'pro' : 'basic';

            // Aktualizuj uživatele
            $db->prepare("UPDATE users SET plan=?, payment_failed_at=NULL WHERE id=?")
               ->execute([$plan, $user['id']]);
            // Poznámka: payment_failed_at sloupec vyžaduje spuštění install.php (migrace)

            // Upsert do subscriptions
            $db->prepare(
                "INSERT INTO subscriptions (user_id,plan,status,amount,expires_at,stripe_subscription_id,stripe_invoice_id)
                 VALUES (?,?,'active',?,?,?,?)
                 ON DUPLICATE KEY UPDATE status='active',amount=VALUES(amount),expires_at=VALUES(expires_at),stripe_invoice_id=VALUES(stripe_invoice_id)"
            )->execute([$user['id'],$plan,$amount,$periodEnd,$obj['subscription'],$obj['id']]);

            // Email zákazníkovi
            $invoiceUrl = $obj['hosted_invoice_url'] ?? PLATFORM_URL . '/dashboard/subscription.php';
            $body = emailTemplate('Platba proběhla úspěšně', "
                <p>Dobrý den, <strong>" . e($user['business_name']) . "</strong>,</p>
                <p>Vaše platba <strong>" . formatPrice($amount) . "</strong> za plán " . PLATFORM_NAME . " " . strtoupper($plan) . " proběhla úspěšně.</p>
                <p>Předplatné je aktivní do: <strong>" . date('j. n. Y', strtotime($periodEnd)) . "</strong></p>
                <p><a href='{$invoiceUrl}' style='color:#2563eb'>Zobrazit fakturu</a></p>
            ");
            sendMail($user['email'], 'Platba přijata – ' . PLATFORM_NAME, $body);
            break;

        // ----------------------------------------------------------
        // Platba selhala
        // ----------------------------------------------------------
        case 'invoice.payment_failed':
            $customerId = $obj['customer'];
            $user = getUserByCustomer($db, $customerId);
            if (!$user) break;

            $failedAt = $user['payment_failed_at'];

            if (!$failedAt) {
                // První neúspěch — zaznamenat a poslat varování
                $db->prepare("UPDATE users SET payment_failed_at=NOW() WHERE id=?")->execute([$user['id']]);
                $failedAt = date('Y-m-d H:i:s');
            } else {
                // Opakovaný neúspěch — zkontroluj jestli uplynuly 3 dny
                $daysFailed = (time() - strtotime($failedAt)) / 86400;
                if ($daysFailed >= 3) {
                    // Deaktivuj účet
                    $db->prepare("UPDATE users SET status='suspended' WHERE id=?")->execute([$user['id']]);
                    $body = emailTemplate('Účet pozastaven', "
                        <p>Dobrý den, <strong>" . e($user['business_name']) . "</strong>,</p>
                        <p>Váš účet byl <strong>pozastaven</strong> z důvodu neúspěšné platby.</p>
                        <p>Aktualizujte platební metodu v nastavení předplatného a kontaktujte nás pro obnovení přístupu:</p>
                        <p><a href='" . PLATFORM_URL . "/dashboard/subscription.php' style='display:inline-block;padding:12px 28px;background:#ef4444;color:#fff;border-radius:8px;text-decoration:none;font-weight:600'>Aktualizovat platbu</a></p>
                        <p style='color:#6b7280;font-size:14px'>Nebo nás kontaktujte na " . PLATFORM_EMAIL . "</p>
                    ");
                    sendMail($user['email'], 'Účet pozastaven – neúspěšná platba', $body);
                    break;
                }
            }

            // Pošli varovací email
            $nextAttempt = isset($obj['next_payment_attempt'])
                ? date('j. n. Y', $obj['next_payment_attempt'])
                : 'brzy';

            $body = emailTemplate('Platba selhala', "
                <p>Dobrý den, <strong>" . e($user['business_name']) . "</strong>,</p>
                <p>Nepodařilo se strhnout platbu za vaše předplatné " . PLATFORM_NAME . ".</p>
                <p>Prosím aktualizujte vaši platební metodu, aby nedošlo k přerušení služby.</p>
                <p>Další pokus o platbu: <strong>{$nextAttempt}</strong></p>
                <p>Pokud platba neproběhne do 3 dnů, váš účet bude pozastaven.</p>
                <p><a href='" . PLATFORM_URL . "/dashboard/subscription.php' style='display:inline-block;padding:12px 28px;background:#f59e0b;color:#fff;border-radius:8px;text-decoration:none;font-weight:600'>Aktualizovat platbu</a></p>
            ");
            sendMail($user['email'], '⚠️ Platba selhala – ' . PLATFORM_NAME, $body);
            break;

        // ----------------------------------------------------------
        // Předplatné zrušeno / expirováno
        // ----------------------------------------------------------
        case 'customer.subscription.deleted':
            $user = getUserByCustomer($db, $obj['customer']);
            if (!$user) break;

            // Vrátit na trial pokud ještě v trial období, jinak suspend
            $trialLeft = getTrialDaysLeft($user['created_at']);
            if ($trialLeft > 0) {
                $db->prepare("UPDATE users SET plan='trial', stripe_subscription_id=NULL WHERE id=?")->execute([$user['id']]);
            } else {
                $db->prepare("UPDATE users SET status='suspended', plan='trial', stripe_subscription_id=NULL WHERE id=?")->execute([$user['id']]);
            }

            $db->prepare("UPDATE subscriptions SET status='cancelled' WHERE user_id=? AND stripe_subscription_id=?")
               ->execute([$user['id'], $obj['id']]);
            break;

        // ----------------------------------------------------------
        // Předplatné aktualizováno (změna plánu)
        // ----------------------------------------------------------
        case 'customer.subscription.updated':
            $user = getUserByCustomer($db, $obj['customer']);
            if (!$user || $obj['status'] !== 'active') break;

            $amount = 0;
            if (!empty($obj['items']['data'][0]['price']['unit_amount'])) {
                $amount = round($obj['items']['data'][0]['price']['unit_amount'] / 100);
            }
            $plan = ($amount >= PLAN_PRO_PRICE) ? 'pro' : 'basic';

            if ($obj['status'] === 'active') {
                $db->prepare("UPDATE users SET plan=? WHERE id=?")->execute([$plan, $user['id']]);
            }
            break;
    }

    http_response_code(200);
    echo json_encode(['received' => true]);

} catch (Exception $e) {
    // Neblokuj Stripe — vrátit 200 ale zalogovat chybu
    error_log('Stripe webhook error [' . $type . ']: ' . $e->getMessage());
    http_response_code(200);
    echo json_encode(['received' => true, 'warning' => $e->getMessage()]);
}
