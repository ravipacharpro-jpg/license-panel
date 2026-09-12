<?php
// app/functions.php - Global Utility & Security Functions

if (session_status() === PHP_SESSION_NONE) {
    // Some hosting environments (shared/free hosting) don't provide a writable
    // default session path. Fall back to a local sessions/ folder in that case.
    $custom_session_path = __DIR__ . '/../sessions';
    if (!is_dir($custom_session_path)) {
        @mkdir($custom_session_path, 0755, true);
    }
    if (is_dir($custom_session_path) && is_writable($custom_session_path)) {
        session_save_path($custom_session_path);
    }
    session_start();
}

/* =========================================================================
   1. SESSION & ROUTING SECURITY
========================================================================= */

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login");
        exit();
    }
}

function require_role($allowed_roles) {
    if (!isset($_SESSION['role'])) {
        header("Location: /login");
        exit();
    }
    $current_role = strtolower($_SESSION['role']);
    $allowed_array = array_map('strtolower', (array)$allowed_roles);
    if (!in_array($current_role, $allowed_array)) {
        die("Access Denied: You do not have permission to view this page.");
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/* =========================================================================
   2. GENERATORS & FORMATTERS
========================================================================= */

function generate_random_string($length = 10, $prefix = '') {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomString = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $max)];
    }
    return $prefix . $randomString;
}

function generate_otp() {
    return sprintf("%06d", random_int(100000, 999999));
}

function format_date($datetime) {
    if (empty($datetime)) return 'Never';
    return date('M j, Y - H:i', strtotime($datetime));
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/* =========================================================================
   3. TELEGRAM BOT API DISPATCHER
========================================================================= */

function send_telegram_message($chat_id, $message) {
    if (!defined('TELEGRAM_BOT_TOKEN') || empty(TELEGRAM_BOT_TOKEN) || TELEGRAM_BOT_TOKEN === 'PASTE_YOUR_NEW_BOT_TOKEN_HERE') {
        return ['ok' => false, 'description' => 'Bot token is not configured yet.'];
    }
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
        return ['ok' => false, 'description' => 'cURL Error: ' . $err];
    }
    return json_decode($result, true);
}

/* =========================================================================
   4. LOGGING
========================================================================= */

function write_log($conn, $tenant_code, $log_type, $category, $message, $sdk_key = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $stmt = $conn->prepare("INSERT INTO logs (tenant_code, log_type, category, message, sdk_key, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $tenant_code, $log_type, $category, $message, $sdk_key, $ip);
    $stmt->execute();
    $stmt->close();
}
?>
