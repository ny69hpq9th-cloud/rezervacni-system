<?php
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function renderFlash(): void {
    $f = getFlash();
    if (!$f) return;
    $icons = ['success' => '✓', 'error' => '✕', 'warning' => '⚠', 'info' => 'ℹ'];
    $icon  = $icons[$f['type']] ?? 'ℹ';
    echo '<div class="alert alert--' . e($f['type']) . '"><span class="alert__icon">' . $icon . '</span>' . e($f['message']) . '</div>';
}

function slugify(string $str): string {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $map = ['á'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','í'=>'i','ň'=>'n',
            'ó'=>'o','ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ý'=>'y','ž'=>'z'];
    $str = strtr($str, $map);
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-');
}

function generateUniqueSlug(string $businessName, PDO $db): string {
    $base = slugify($businessName) ?: 'firma';
    $slug = $base;
    $i    = 1;
    while (true) {
        $stmt = $db->prepare("SELECT id FROM users WHERE slug = ?");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function formatDate(string $date): string {
    return date('j. n. Y', strtotime($date));
}

function formatTime(string $time): string {
    return substr($time, 0, 5);
}

function formatPrice(float $price): string {
    return number_format($price, 0, ',', ' ') . ' Kč';
}

function formatDuration(int $minutes): string {
    if (currentLang() === 'en') {
        if ($minutes < 60) return $minutes . ' min';
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h . 'h' . ($m > 0 ? ' ' . $m . ' min' : '');
    }
    if ($minutes < 60) return $minutes . ' min';
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $h . 'h' . ($m > 0 ? ' ' . $m . ' min' : '');
}

function getDayName(int $day): string {
    return __('day.' . $day);
}

function getDayNameShort(int $day): string {
    return __('day.short.' . $day);
}

function statusLabel(string $status): string {
    return __('status.' . $status) ?: $status;
}

function statusClass(string $status): string {
    return match ($status) {
        'pending'   => 'badge--warning',
        'confirmed' => 'badge--success',
        'cancelled' => 'badge--danger',
        'completed' => 'badge--info',
        default     => 'badge--default',
    };
}

function getTrialDaysLeft(string $createdAt): int {
    $end = strtotime($createdAt) + (TRIAL_DAYS * 86400);
    return max(0, (int) ceil(($end - time()) / 86400));
}

/** Czech-aware plural for "days". */
function trialDaysWord(int $n): string {
    if (currentLang() === 'en') {
        return $n === 1 ? __('dash.trial_day') : __('dash.trial_days5');
    }
    if ($n === 1)                      return __('dash.trial_day');
    if ($n >= 2 && $n <= 4)           return __('dash.trial_days2');
    return __('dash.trial_days5');
}

function getPlanLabel(string $plan): string {
    return __('plan.' . $plan) ?: ucfirst($plan);
}

function getAvailableTimes(PDO $db, int $userId, string $date, int $serviceId): array {
    $stmt = $db->prepare("SELECT duration FROM services WHERE id = ? AND user_id = ? AND active = 1");
    $stmt->execute([$serviceId, $userId]);
    $svc = $stmt->fetch();
    if (!$svc) return [];
    $duration = (int)$svc['duration'];

    $dow = (int)date('w', strtotime($date));

    $stmt = $db->prepare("SELECT id FROM blocked_dates WHERE user_id = ? AND date = ?");
    $stmt->execute([$userId, $date]);
    if ($stmt->fetch()) return [];

    $stmt = $db->prepare("SELECT * FROM working_hours WHERE user_id = ? AND day_of_week = ?");
    $stmt->execute([$userId, $dow]);
    $wh = $stmt->fetch();
    if (!$wh || !$wh['is_working']) return [];

    $slots = [];
    $start = strtotime($wh['start_time']);
    $end   = strtotime($wh['end_time']);
    $dur   = $duration * 60;
    $cur   = $start;
    while ($cur + $dur <= $end) {
        $slots[] = date('H:i', $cur);
        $cur    += $dur;
    }

    $stmt = $db->prepare(
        "SELECT time FROM bookings WHERE user_id = ? AND date = ? AND status != 'cancelled'"
    );
    $stmt->execute([$userId, $date]);
    // Normalize DB times (stored as HH:MM:SS) to HH:MM for comparison
    $booked = array_map(fn($t) => substr($t, 0, 5), $stmt->fetchAll(PDO::FETCH_COLUMN));

    return array_values(array_filter($slots, fn($s) => !in_array($s, $booked)));
}

function getMonthBookings(PDO $db, int $userId, int $year, int $month): array {
    $from = sprintf('%04d-%02d-01', $year, $month);
    $to   = date('Y-m-t', strtotime($from));
    $stmt = $db->prepare(
        "SELECT b.*, s.name as service_name FROM bookings b
         LEFT JOIN services s ON b.service_id = s.id
         WHERE b.user_id = ? AND b.date BETWEEN ? AND ?
         ORDER BY b.date, b.time"
    );
    $stmt->execute([$userId, $from, $to]);
    return $stmt->fetchAll();
}
