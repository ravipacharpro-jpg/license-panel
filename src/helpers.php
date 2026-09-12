<?php
// src/helpers.php - shared helpers
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(?string $t): bool {
    return isset($_SESSION['csrf']) && is_string($t) && hash_equals($_SESSION['csrf'], $t);
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    try {
        $st = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $st->execute([$_SESSION['user_id']]);
        $u = $st->fetch();
        if (!$u) { unset($_SESSION['user_id']); return null; }
        if (($u['status'] ?? 'active') !== 'active') { unset($_SESSION['user_id']); return null; }
        return $u;
    } catch (Throwable $ex) {
        return null;
    }
}

function get_setting(string $k, string $default = ''): string {
    try {
        $st = db()->prepare('SELECT v FROM settings WHERE k = ?');
        $st->execute([$k]);
        $row = $st->fetch();
        return $row ? (string)$row['v'] : $default;
    } catch (Throwable $ex) {
        return $default;
    }
}

function set_setting(string $k, string $v): void {
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $st = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
        $st->execute([$k, $v]);
    } else {
        $st = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v');
        $st->execute([$k, $v]);
    }
}

function price_for_duration(string $d): float {
    $map = [
        '1day' => (float)get_setting('price_1day', '49'),
        '7days' => (float)get_setting('price_7days', '149'),
        '30days' => (float)get_setting('price_30days', '299'),
        'lifetime' => (float)get_setting('price_lifetime', '999'),
        // aliases
        '1_day' => (float)get_setting('price_1day', '49'),
        '7_days' => (float)get_setting('price_7days', '149'),
        '30_days' => (float)get_setting('price_30days', '299'),
    ];
    return $map[$d] ?? $map['30days'] ?? 299.0;
}

function duration_label(string $d): string {
    return match($d) {
        '1day' => '1 Day',
        '7days' => '7 Days',
        '30days' => '30 Days',
        'lifetime' => 'Lifetime',
        default => $d,
    };
}

function calc_expiry(string $duration): ?string {
    $now = new DateTime('now', new DateTimeZone('UTC'));
    switch ($duration) {
        case '1day': $now->modify('+1 day'); break;
        case '7days': $now->modify('+7 days'); break;
        case '30days': $now->modify('+30 days'); break;
        case 'lifetime': return null;
        default: $now->modify('+30 days');
    }
    return $now->format('Y-m-d H:i:s');
}

function generate_key_string(): string {
    // Format: XXXX-XXXX-XXXX-XXXX (uppercase alnum without confusing chars)
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $parts = [];
    for ($p = 0; $p < 4; $p++) {
        $s = '';
        for ($i = 0; $i < 4; $i++) $s .= $chars[random_int(0, strlen($chars) - 1)];
        $parts[] = $s;
    }
    return implode('-', $parts);
}

function generate_unique_key(PDO $pdo): string {
    for ($i = 0; $i < 20; $i++) {
        $k = generate_key_string();
        $st = $pdo->prepare('SELECT id FROM license_keys WHERE key_string = ?');
        $st->execute([$k]);
        if (!$st->fetch()) return $k;
    }
    return strtoupper(bin2hex(random_bytes(8))) . '-' . strtoupper(bin2hex(random_bytes(4)));
}

function generate_referral_code(PDO $pdo): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($i = 0; $i < 30; $i++) {
        $c = 'NX-';
        for ($j = 0; $j < 6; $j++) $c .= $chars[random_int(0, strlen($chars) - 1)];
        $st = $pdo->prepare('SELECT id FROM users WHERE referral_code = ?');
        $st->execute([$c]);
        if (!$st->fetch()) return $c;
    }
    return 'NX-' . strtoupper(bin2hex(random_bytes(3)));
}

function log_activity(?int $userId, string $action, ?int $keyId = null, ?string $details = null): void {
    try {
        $st = db()->prepare('INSERT INTO logs (user_id, action, target_key_id, details) VALUES (?, ?, ?, ?)');
        $st->execute([$userId, $action, $keyId, $details]);
    } catch (Throwable $ex) {}
}

function app_name(): string {
    return get_setting('app_name', 'NEXUS License Panel');
}

function site_tagline(): string {
    return get_setting('site_tagline', 'Premium Mod Panel');
}

function support_email(): string {
    return get_setting('support_email', 'admin@example.com');
}

function telegram_link(): string {
    return get_setting('telegram_link', '');
}

// MultiPanelX-style: numeric duration + type -> expiry datetime or null
function calc_expiry_plan(int $duration, string $type): ?string {
    $type = strtolower($type);
    if ($type === 'lifetime' || $duration <= 0) return null;
    $now = new DateTime('now', new DateTimeZone('UTC'));
    switch ($type) {
        case 'minutes': $now->modify("+{$duration} minutes"); break;
        case 'hours': $now->modify("+{$duration} hours"); break;
        case 'months': $now->modify("+{$duration} months"); break;
        case 'days':
        default: $now->modify("+{$duration} days"); break;
    }
    return $now->format('Y-m-d H:i:s');
}

function plan_expiry_label(?int $duration, ?string $type): string {
    if (!$type) return '-';
    if (strtolower($type) === 'lifetime') return 'Lifetime';
    return ((int)$duration) . ' ' . ucfirst((string)$type);
}

// Check if a sold key (sold_at + duration/type) is expired; if yes, optionally mark expired
function plan_key_expired(array $key): bool {
    if (empty($key['sold_at'])) return false;
    $dur = (int)($key['duration'] ?? 0);
    $type = strtolower((string)($key['duration_type'] ?? 'days'));
    // legacy string types like 30days
    if (in_array($key['duration_type'] ?? '', ['1day','7days','30days','lifetime'], true)) {
        if (($key['duration_type'] ?? '') === 'lifetime') return false;
        return !empty($key['expires_at']) && strtotime($key['expires_at'] . ' UTC') < time();
    }
    if ($type === 'lifetime' || $dur <= 0) return false;
    $sold = strtotime($key['sold_at'] . ' UTC');
    if ($sold === false) return false;
    $exp = strtotime("+{$dur} {$type}", $sold);
    return $exp !== false && time() > $exp;
}

function upi_pay_string(string $upiId, float $amount, string $name = 'Admin'): string {
    return 'upi://pay?pa=' . urlencode($upiId) . '&pn=' . urlencode($name) . '&am=' . number_format($amount, 2, '.', '') . '&cu=INR';
}

function generate_token_code(PDO $pdo): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($i = 0; $i < 30; $i++) {
        $c = '';
        for ($j = 0; $j < 8; $j++) $c .= $chars[random_int(0, strlen($chars) - 1)];
        $st = $pdo->prepare('SELECT id FROM referral_tokens WHERE code = ?');
        $st->execute([$c]);
        if (!$st->fetch()) return $c;
    }
    return strtoupper(bin2hex(random_bytes(4)));
}

function ensure_admin_api_key(): string {
    $k = get_setting('admin_api_key', '');
    if ($k === '') {
        $k = bin2hex(random_bytes(16));
        set_setting('admin_api_key', $k);
    }
    return $k;
}

// Last 7 days labels + counts for Chart.js (sales + revenue)
function chart_last7(PDO $pdo, ?int $userId = null): array {
    $labels = []; $sales = []; $rev = [];
    $map = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $map[$d] = ['label' => date('D d', strtotime("-$i days")), 'sales' => 0, 'rev' => 0.0];
    }
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $dateExprSold = $driver === 'mysql' ? 'DATE(sold_at)' : "date(sold_at)";
    $dateExprTx = $driver === 'mysql' ? 'DATE(created_at)' : "date(created_at)";
    $cutoff = date('Y-m-d H:i:s', strtotime('-6 days 00:00:00'));
    try {
        if ($userId) {
            $st = $pdo->prepare("SELECT $dateExprSold d, COUNT(*) c FROM license_keys WHERE sold_to = ? AND sold_at >= ? GROUP BY $dateExprSold");
            $st->execute([$userId, $cutoff]);
        } else {
            $st = $pdo->prepare("SELECT $dateExprSold d, COUNT(*) c FROM license_keys WHERE sold_at >= ? GROUP BY $dateExprSold");
            $st->execute([$cutoff]);
        }
        foreach ($st->fetchAll() as $r) { if (isset($map[$r['d']])) $map[$r['d']]['sales'] = (int)$r['c']; }
    } catch (Throwable $ex) {}
    try {
        if ($userId) {
            $st = $pdo->prepare("SELECT $dateExprTx d, SUM(ABS(amount)) t FROM transactions WHERE user_id = ? AND type='purchase' AND status IN ('completed','approved') AND created_at >= ? GROUP BY $dateExprTx");
            $st->execute([$userId, $cutoff]);
        } else {
            $st = $pdo->prepare("SELECT $dateExprTx d, SUM(ABS(amount)) t FROM transactions WHERE type='purchase' AND status IN ('completed','approved') AND created_at >= ? GROUP BY $dateExprTx");
            $st->execute([$cutoff]);
        }
        foreach ($st->fetchAll() as $r) { if (isset($map[$r['d']])) $map[$r['d']]['rev'] = (float)$r['t']; }
    } catch (Throwable $ex) {}
    foreach ($map as $m) { $labels[] = $m['label']; $sales[] = $m['sales']; $rev[] = $m['rev']; }
    return [$labels, $sales, $rev];
}

function flash_set(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}
