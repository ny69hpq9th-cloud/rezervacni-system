<?php
/**
 * Testovací data — spusťte jednou v prohlížeči, poté SMAŽTE.
 * Vytvoří testovací účet, služby, pracovní hodiny a ukázkové rezervace.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'u589761686_rezervly';
$DB_USER = 'u589761686_rezervly';
$DB_PASS = 'Ty?k5VQW8';

$errors  = [];
$results = [];

if (isset($_POST['seed'])) {
    try {
        $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // ── 1. Testovací uživatel ────────────────────────────────────────────
        $email    = 'test@rezervly.eu';
        $passHash = password_hash('Test1234', PASSWORD_DEFAULT);

        // Smazat starý účet pokud existuje (cascade smaže i vše ostatní)
        $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);

        $pdo->prepare("
            INSERT INTO users
                (email, password, business_name, slug, business_type,
                 description, address, phone, notification_email,
                 accent_color, plan, status, created_at)
            VALUES
                (?, ?, 'Ukázkový Salón Rezervly', 'ukazkovy-salon', 'Salón krásy',
                 'Moderní salón krásy v centru města. Nabízíme střihy, barvení, manikúru a masáže.',
                 'Náměstí Míru 12, Praha 2', '+420 777 123 456', ?,
                 '#2563eb', 'pro', 'active', NOW())
        ")->execute([$email, $passHash, $email]);

        $userId = (int)$pdo->lastInsertId();
        $results[] = "✓ Uživatel vytvořen (ID: {$userId})";

        // ── 2. Pro předplatné — aktivní 14 dní ──────────────────────────────
        $pdo->prepare("
            INSERT INTO subscriptions (user_id, plan, status, amount, expires_at)
            VALUES (?, 'pro', 'active', 399.00, DATE_ADD(NOW(), INTERVAL 14 DAY))
        ")->execute([$userId]);
        $results[] = '✓ Pro předplatné vytvořeno (platné 14 dní)';

        // ── 3. Služby ────────────────────────────────────────────────────────
        $services = [
            ['Střih a úprava vlasů',  45,  350.00, 'Profesionální střih dle vašeho přání, mytí a foukaná v ceně.', 0],
            ['Barvení vlasů',        120, 1200.00, 'Barvení přírodními nebo syntetickými barvami, konzultace zdarma.',  1],
            ['Melír a balayage',      90,  900.00, 'Techniky melíru a balayage pro přirozený přechod barev.',          2],
            ['Manikúra klasická',     60,  450.00, 'Úprava nehtů, odlíčení a lakování dle výběru z barevníku.',        3],
            ['Masáž zad a šíje',      60,  600.00, 'Relaxační masáž uvolňující napětí v zádech a šíji.',               4],
            ['Čištění pleti',         60,  800.00, 'Hloubkové čištění pleti, hydratace a ošetření sérem.',              5],
        ];

        $svcIds = [];
        $svcStmt = $pdo->prepare("
            INSERT INTO services (user_id, name, duration, price, description, active, sort_order)
            VALUES (?, ?, ?, ?, ?, 1, ?)
        ");
        foreach ($services as [$name, $duration, $price, $desc, $order]) {
            $svcStmt->execute([$userId, $name, $duration, $price, $desc, $order]);
            $svcIds[$name] = (int)$pdo->lastInsertId();
        }
        $results[] = '✓ ' . count($services) . ' služeb vytvořeno';

        // ── 4. Pracovní hodiny ───────────────────────────────────────────────
        // 0=Ne, 1=Po, 2=Út, 3=St, 4=Čt, 5=Pá, 6=So
        $hours = [
            [0, 0, '09:00', '18:00'], // Neděle – zavřeno
            [1, 1, '09:00', '18:00'], // Pondělí
            [2, 1, '09:00', '18:00'], // Úterý
            [3, 1, '09:00', '18:00'], // Středa
            [4, 1, '09:00', '18:00'], // Čtvrtek
            [5, 1, '09:00', '18:00'], // Pátek
            [6, 1, '09:00', '14:00'], // Sobota (dopoledne)
        ];

        $hoursStmt = $pdo->prepare("
            INSERT INTO working_hours (user_id, day_of_week, is_working, start_time, end_time)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($hours as [$day, $working, $start, $end]) {
            $hoursStmt->execute([$userId, $day, $working, $start, $end]);
        }
        $results[] = '✓ Pracovní hodiny nastaveny (Po–Pá 9–18, So 9–14, Ne zavřeno)';

        // ── 5. Ukázkové rezervace ────────────────────────────────────────────
        $bookings = [
            // Budoucí potvrzené
            ['Jana Nováková',   'jana@example.com',  '+420 601 111 222', 'Střih a úprava vlasů',  '+2 days',  '10:00', 'confirmed',  null],
            ['Petra Svobodová', 'petra@example.com', '+420 602 333 444', 'Barvení vlasů',         '+3 days',  '13:00', 'confirmed',  'Přeji světlejší odstín'],
            ['Martin Dvořák',   'martin@example.com','+420 603 555 666', 'Masáž zad a šíje',      '+4 days',  '11:30', 'pending',    null],
            ['Lucie Procházková','lucie@example.com', '+420 604 777 888', 'Manikúra klasická',     '+5 days',  '14:00', 'confirmed',  null],
            ['Tomáš Krejčí',    'tomas@example.com', '+420 605 999 000', 'Čištění pleti',         '+7 days',  '09:30', 'pending',    'Citlivá pleť'],
            // Dnešní
            ['Eva Horáčková',   'eva@example.com',   '+420 606 111 333', 'Střih a úprava vlasů',  'today',    '09:00', 'confirmed',  null],
            ['Karel Novák',     'karel@example.com', '+420 607 222 444', 'Melír a balayage',      'today',    '11:00', 'confirmed',  null],
            // Minulé
            ['Alena Marková',   'alena@example.com', '+420 608 333 555', 'Barvení vlasů',         '-2 days',  '10:00', 'completed',  null],
            ['Jiří Veselý',     'jiri@example.com',  '+420 609 444 666', 'Masáž zad a šíje',      '-3 days',  '14:00', 'completed',  null],
            ['Monika Šimková',  'monika@example.com','+420 610 555 777', 'Střih a úprava vlasů',  '-5 days',  '10:30', 'completed',  null],
            ['Radek Blažek',    'radek@example.com', '+420 611 666 888', 'Manikúra klasická',     '-6 days',  '13:00', 'cancelled',  'Zákazník zrušil'],
            ['Hana Pokorná',    'hana@example.com',  '+420 612 777 999', 'Čištění pleti',         '-7 days',  '15:00', 'completed',  null],
        ];

        $bStmt = $pdo->prepare("
            INSERT INTO bookings
                (user_id, service_id, customer_name, customer_email, customer_phone,
                 date, time, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $booked = 0;
        foreach ($bookings as [$cName, $cEmail, $cPhone, $svcName, $dateExpr, $time, $status, $notes]) {
            $svcId = $svcIds[$svcName] ?? null;
            if ($dateExpr === 'today') {
                $date = date('Y-m-d');
            } else {
                $date = date('Y-m-d', strtotime($dateExpr));
            }
            $bStmt->execute([$userId, $svcId, $cName, $cEmail, $cPhone, $date, $time, $status, $notes]);
            $booked++;
        }
        $results[] = "✓ {$booked} ukázkových rezervací vytvořeno";

    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Seed – Rezervly</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,sans-serif;background:#f8fafc;color:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:40px;max-width:560px;width:100%;box-shadow:0 4px 6px rgba(0,0,0,.06)}
h1{font-size:1.4rem;font-weight:800;margin-bottom:6px}
.sub{color:#64748b;margin-bottom:28px;font-size:.875rem}
.info{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:16px;margin-bottom:20px;font-size:.875rem;line-height:1.8}
.info code{background:#dbeafe;padding:2px 6px;border-radius:4px;font-family:monospace;font-size:.85rem}
.result{padding:10px 14px;border-radius:8px;margin-bottom:8px;font-size:.875rem;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.alert-error{padding:14px 18px;border-radius:10px;margin-bottom:12px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;font-family:monospace;font-size:.8rem;word-break:break-all}
.btn{display:block;width:100%;padding:13px;background:#2563eb;color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;margin-top:20px}
.btn:hover{background:#1d4ed8}
.warning{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 18px;margin-top:20px;font-size:.825rem;color:#92400e}
a.link{color:#2563eb;font-weight:600;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <h1>🌱 Testovací data – Rezervly</h1>
  <p class="sub">Vytvoří testovací účet, služby, pracovní hodiny a ukázkové rezervace.</p>

  <?php if (!empty($results)): ?>
    <?php foreach ($results as $r): ?>
      <div class="result"><?= htmlspecialchars($r) ?></div>
    <?php endforeach; ?>
    <div class="info" style="margin-top:16px">
      <strong>Přihlašovací údaje:</strong><br>
      Email: <code>test@rezervly.eu</code><br>
      Heslo: <code>Test1234</code><br>
      Plán: <code>Pro</code> (aktivní 14 dní)<br>
      Rezervační stránka: <a href="/rezervace/ukazkovy-salon" class="link" target="_blank">/rezervace/ukazkovy-salon</a>
    </div>
    <a href="/login.php" style="display:block;margin-top:16px">
      <button class="btn">Přihlásit se →</button>
    </a>
  <?php elseif (!empty($errors)): ?>
    <?php foreach ($errors as $e): ?>
      <div class="alert-error">✕ <?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <form method="POST"><button class="btn" name="seed" value="1">Zkusit znovu</button></form>
  <?php else: ?>
    <div class="info">
      Vytvoří nebo přepíše:<br>
      • Uživatel <code>test@rezervly.eu</code> / heslo <code>Test1234</code><br>
      • Plán <code>Pro</code> s předplatným na 14 dní<br>
      • 6 ukázkových služeb (střih, barvení, melír, manikúra, masáž, čištění pleti)<br>
      • Pracovní hodiny: Po–Pá 9:00–18:00, So 9:00–14:00, Ne zavřeno<br>
      • 12 ukázkových rezervací (minulé, dnešní, budoucí)
    </div>
    <form method="POST"><button class="btn" name="seed" value="1">Vytvořit testovací data →</button></form>
  <?php endif; ?>

  <div class="warning">⚠️ <strong>Bezpečnost:</strong> Po použití smažte <code>seed.php</code> ze serveru!</div>
</div>
</body>
</html>
