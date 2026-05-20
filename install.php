<?php
/**
 * Instalátor databáze — spusťte jednou po nahrání na hosting.
 * Po úspěšné instalaci SMAŽTE tento soubor!
 *
 * Standalone skript — nenačítá config.php záměrně, aby se
 * zabránilo chybám z lang/session závislostí při prvním spuštění.
 */

// ── Databázové údaje (musí odpovídat config.php) ────────────────
$DB_HOST = 'localhost';
$DB_NAME = 'u589761686_rezervly';
$DB_USER = 'u589761686_rezervly';
$DB_PASS = 'Ty?k5VQW8';
// ────────────────────────────────────────────────────────────────

$errors  = [];
$success = false;

if (isset($_POST['install'])) {
    try {
        // Připojení přímo k existující databázi (bez CREATE DATABASE)
        $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // Každá tabulka jako samostatný příkaz
        $tables = [];

        $tables[] = "CREATE TABLE IF NOT EXISTS `users` (
          `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `email`               VARCHAR(255) NOT NULL UNIQUE,
          `password`            VARCHAR(255) NOT NULL,
          `business_name`       VARCHAR(255) NOT NULL,
          `slug`                VARCHAR(100) NOT NULL UNIQUE,
          `business_type`       VARCHAR(100) DEFAULT NULL,
          `description`         TEXT,
          `address`             VARCHAR(255) DEFAULT NULL,
          `phone`               VARCHAR(30)  DEFAULT NULL,
          `notification_email`  VARCHAR(255) DEFAULT NULL,
          `logo`                VARCHAR(255) DEFAULT NULL,
          `accent_color`        VARCHAR(7)   DEFAULT '#2563eb',
          `custom_message`      TEXT,
          `stripe_customer_id`      VARCHAR(255) DEFAULT NULL,
          `stripe_subscription_id`  VARCHAR(255) DEFAULT NULL,
          `stripe_cancel_at`        DATETIME DEFAULT NULL,
          `payment_failed_at`       DATETIME DEFAULT NULL,
          `reset_token`             VARCHAR(64) DEFAULT NULL,
          `reset_token_expires`     DATETIME DEFAULT NULL,
          `plan`                ENUM('trial','basic','pro') DEFAULT 'trial',
          `status`              ENUM('active','suspended') DEFAULT 'active',
          `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $tables[] = "CREATE TABLE IF NOT EXISTS `services` (
          `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id`     INT UNSIGNED NOT NULL,
          `name`        VARCHAR(255) NOT NULL,
          `duration`    SMALLINT UNSIGNED NOT NULL,
          `price`       DECIMAL(10,2) DEFAULT 0,
          `description` TEXT,
          `active`      TINYINT(1) DEFAULT 1,
          `sort_order`  SMALLINT DEFAULT 0,
          `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $tables[] = "CREATE TABLE IF NOT EXISTS `working_hours` (
          `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id`     INT UNSIGNED NOT NULL,
          `day_of_week` TINYINT UNSIGNED NOT NULL,
          `is_working`  TINYINT(1) DEFAULT 1,
          `start_time`  TIME DEFAULT '09:00:00',
          `end_time`    TIME DEFAULT '17:00:00',
          UNIQUE KEY `user_day` (`user_id`,`day_of_week`),
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $tables[] = "CREATE TABLE IF NOT EXISTS `blocked_dates` (
          `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id`    INT UNSIGNED NOT NULL,
          `date`       DATE NOT NULL,
          `reason`     VARCHAR(255) DEFAULT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $tables[] = "CREATE TABLE IF NOT EXISTS `bookings` (
          `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id`        INT UNSIGNED NOT NULL,
          `service_id`     INT UNSIGNED DEFAULT NULL,
          `customer_name`  VARCHAR(255) NOT NULL,
          `customer_email` VARCHAR(255) NOT NULL,
          `customer_phone` VARCHAR(30)  NOT NULL,
          `date`           DATE NOT NULL,
          `time`           TIME NOT NULL,
          `status`         ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
          `notes`          TEXT,
          `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
          FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $tables[] = "CREATE TABLE IF NOT EXISTS `subscriptions` (
          `id`                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id`                INT UNSIGNED NOT NULL,
          `plan`                   ENUM('basic','pro') NOT NULL,
          `status`                 ENUM('active','expired','cancelled') DEFAULT 'active',
          `amount`                 DECIMAL(10,2) DEFAULT 0,
          `stripe_subscription_id` VARCHAR(255) DEFAULT NULL,
          `stripe_invoice_id`      VARCHAR(255) DEFAULT NULL,
          `expires_at`             DATETIME NOT NULL,
          `created_at`             DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        foreach ($tables as $sql) {
            $pdo->exec($sql);
        }

        // Migrace — přidá sloupce do existujících tabulek (bezpečné, ignoruje chybu pokud sloupec už existuje)
        $migrations = [
            "ALTER TABLE `users` ADD COLUMN `payment_failed_at` DATETIME DEFAULT NULL",
            "ALTER TABLE `subscriptions` ADD COLUMN `stripe_invoice_id` VARCHAR(255) DEFAULT NULL",
            // Password reset (2025-05)
            "ALTER TABLE `users` ADD COLUMN `reset_token` VARCHAR(64) DEFAULT NULL",
            "ALTER TABLE `users` ADD COLUMN `reset_token_expires` DATETIME DEFAULT NULL",
        ];
        foreach ($migrations as $sql) {
            try { $pdo->exec($sql); } catch (PDOException $e) { /* sloupec již existuje */ }
        }

        $success = true;
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
<title>Instalace – Rezervly</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,sans-serif;background:#f8fafc;color:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:40px;max-width:540px;width:100%;box-shadow:0 4px 6px rgba(0,0,0,.06)}
h1{font-size:1.5rem;font-weight:800;margin-bottom:6px}
.sub{color:#64748b;margin-bottom:28px;font-size:.9rem}
.info{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:20px;font-size:.85rem}
.info code{background:#e2e8f0;padding:2px 6px;border-radius:4px;font-family:monospace}
.step{display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px}
.step-num{width:26px;height:26px;border-radius:50%;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0}
.step-text{font-size:.875rem;line-height:1.5}
.step-text strong{display:block;margin-bottom:2px}
.step-text code{background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:.8rem}
.btn{display:block;width:100%;padding:13px;background:#2563eb;color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;margin-top:24px}
.btn:hover{background:#1d4ed8}
.alert{padding:14px 18px;border-radius:10px;margin-bottom:20px;font-size:.875rem;font-weight:500}
.alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;font-family:monospace;font-size:.8rem;word-break:break-all}
.warning{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 18px;margin-top:20px;font-size:.825rem;color:#92400e}
a{color:#2563eb}
</style>
</head>
<body>
<div class="card">
  <h1>🚀 Instalace Rezervly</h1>
  <p class="sub">Vytvoření databázových tabulek</p>

  <?php if ($success): ?>
    <div class="alert alert-success">✓ Všechny tabulky byly úspěšně vytvořeny!</div>
    <div class="step"><div class="step-num">1</div><div class="step-text"><strong>Smažte tento soubor</strong>Odstraňte <code>install.php</code> ze serveru ihned po instalaci.</div></div>
    <div class="step"><div class="step-num">2</div><div class="step-text"><strong>Přejděte na hlavní stránku</strong><a href="/">Klikněte sem →</a></div></div>
  <?php elseif (!empty($errors)): ?>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error">✕ <?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
    <form method="POST"><button class="btn" name="install" value="1">Zkusit znovu</button></form>
  <?php else: ?>
    <div class="info">
      Připojení k: <code><?= htmlspecialchars($DB_HOST) ?></code> /
      databáze: <code><?= htmlspecialchars($DB_NAME) ?></code> /
      uživatel: <code><?= htmlspecialchars($DB_USER) ?></code>
    </div>
    <div class="step"><div class="step-num">1</div><div class="step-text"><strong>Vytvoří tabulky</strong>users, services, working_hours, blocked_dates, bookings, subscriptions</div></div>
    <div class="step"><div class="step-num">2</div><div class="step-text"><strong>Bezpečné</strong>Používá IF NOT EXISTS — existující data nebudou smazána.</div></div>
    <form method="POST"><button class="btn" name="install" value="1">Spustit instalaci</button></form>
  <?php endif; ?>

  <div class="warning">⚠️ <strong>Bezpečnost:</strong> Po dokončení instalace smažte <code>install.php</code> ze serveru!</div>
</div>
</body>
</html>
