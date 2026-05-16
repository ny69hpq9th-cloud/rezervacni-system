<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . PLATFORM_URL . '/login.php');
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function hasActiveSubscription(array $user): bool {
    // Trial period
    if ($user['plan'] === 'trial') {
        $trialEnd = strtotime($user['created_at']) + (TRIAL_DAYS * 86400);
        return time() < $trialEnd;
    }
    // Paid subscription
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT id FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW()"
    );
    $stmt->execute([$user['id']]);
    return (bool)$stmt->fetch();
}

function loginUser(string $email, string $password): bool {
    $db  = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        return true;
    }
    return false;
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . PLATFORM_URL . '/login.php');
    exit;
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

// Admin session
function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_logged_in']);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . PLATFORM_URL . '/admin/login.php');
        exit;
    }
}
