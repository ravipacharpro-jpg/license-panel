<?php
// api/connect.php - SDK License Validation Endpoint

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/functions.php';

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

$rawPayload = file_get_contents('php://input');
$jsonPayload = json_decode($rawPayload, true) ?: [];

$uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url_tenant = '';
if (preg_match('/\/api\/connect(?:\.php)?\/([a-zA-Z0-9_-]+)/i', $uri_path, $matches)) {
    $url_tenant = $matches[1];
}

$tenant_code  = $url_tenant ?: ($_POST['tenant_code']  ?? ($_GET['tenant_code']  ?? ($jsonPayload['tenant_code']  ?? '')));
$action       = $_POST['action']       ?? ($_GET['action']       ?? ($jsonPayload['action']       ?? 'connect'));
$user_key     = $_POST['user_key']     ?? ($_GET['user_key']     ?? ($jsonPayload['user_key']     ?? ''));
$package_name = $_POST['package_name'] ?? ($_GET['package_name'] ?? ($jsonPayload['package_name'] ?? ''));
$app_name     = $_POST['app_name']     ?? ($_GET['app_name']     ?? ($jsonPayload['app_name']     ?? ''));
$device_id    = $_POST['device_id']    ?? ($_GET['device_id']    ?? ($jsonPayload['device_id']    ?? ''));

$tenant_code = preg_replace('/[^a-zA-Z0-9_-]/', '', strtoupper(trim($tenant_code)));

if (empty($tenant_code)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing tenant identifier.']);
    exit();
}

// Load server settings
$server_settings = [];
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM server_settings WHERE tenant_code = ?");
$stmt->bind_param("s", $tenant_code);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $server_settings[$row['setting_key']] = $row['setting_value'];
}
$stmt->close();

if (empty($server_settings)) {
    echo json_encode(['status' => 'error', 'message' => 'Tenant configuration not found.']);
    exit();
}

if (intval($server_settings['maintenance_mode'] ?? 0) === 1) {
    echo json_encode([
        'status' => 'error',
        'server_mode' => 'maintenance',
        'message' => $server_settings['maintenance_message'] ?? 'Server under maintenance. Try again later.'
    ]);
    exit();
}

if ($action !== 'connect') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid API action.']);
    exit();
}

$user_key     = trim($user_key);
$package_name = trim($package_name);
$app_name     = trim($app_name);
$device_id    = trim($device_id);

if (empty($user_key) || empty($package_name) || empty($app_name) || empty($device_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
    exit();
}

// Fetch key
$stmt = $conn->prepare("SELECT * FROM sdk_keys WHERE sdk_key = ? AND tenant_code = ? LIMIT 1");
$stmt->bind_param("ss", $user_key, $tenant_code);
$stmt->execute();
$key_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$key_data) {
    write_log($conn, $tenant_code, 'api', 'warning', 'Invalid key attempt', $user_key);
    echo json_encode(['status' => 'error', 'message' => 'License key not found or invalid.']);
    exit();
}

$key_id = $key_data['id'];

if ($key_data['status'] === 'Banned') {
    echo json_encode(['status' => 'error', 'message' => 'This license has been banned.']);
    exit();
}

if ($key_data['status'] === 'Expired' || (!empty($key_data['expires_at']) && strtotime($key_data['expires_at']) < time())) {
    if ($key_data['status'] !== 'Expired') {
        $conn->query("UPDATE sdk_keys SET status = 'Expired' WHERE id = $key_id");
    }
    echo json_encode(['status' => 'error', 'message' => 'License has expired.']);
    exit();
}

// HWID enforcement (0 = unlimited)
$stmt = $conn->prepare("SELECT device_id FROM key_devices WHERE key_id = ?");
$stmt->bind_param("i", $key_id);
$stmt->execute();
$dev_res = $stmt->get_result();
$current_hwids = [];
while ($d = $dev_res->fetch_assoc()) $current_hwids[] = $d['device_id'];
$stmt->close();

if (!in_array($device_id, $current_hwids)) {
    $device_limit = intval($key_data['device_limit']);
    if ($device_limit === 0 || count($current_hwids) < $device_limit) {
        $ins = $conn->prepare("INSERT INTO key_devices (key_id, device_id) VALUES (?, ?)");
        $ins->bind_param("is", $key_id, $device_id);
        $ins->execute();
        $ins->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Device limit reached for this license.']);
        exit();
    }
} else {
    $upd = $conn->prepare("UPDATE key_devices SET last_active = CURRENT_TIMESTAMP WHERE key_id = ? AND device_id = ?");
    $upd->bind_param("is", $key_id, $device_id);
    $upd->execute();
    $upd->close();
}

// Package binding
if ($key_data['package_mode'] === 'single') {
    if (empty($key_data['allowed_packages']) || $key_data['allowed_packages'] === 'ALL') {
        $upd = $conn->prepare("UPDATE sdk_keys SET allowed_packages = ? WHERE id = ?");
        $upd->bind_param("si", $package_name, $key_id);
        $upd->execute();
        $upd->close();
    } elseif ($key_data['allowed_packages'] !== $package_name) {
        echo json_encode(['status' => 'error', 'message' => 'License is bound to a different app package.']);
        exit();
    }
}

// App node tracking
try {
    $stmt = $conn->prepare("SELECT id, status FROM key_app_connections WHERE key_id = ? AND package_name = ? AND app_name = ? LIMIT 1");
    $stmt->bind_param("iss", $key_id, $package_name, $app_name);
    $stmt->execute();
    $app_conn = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($app_conn) {
        if ($app_conn['status'] === 'Banned') {
            echo json_encode(['status' => 'error', 'message' => 'This application node has been banned.']);
            exit();
        }
    } else {
        $ins = $conn->prepare("INSERT IGNORE INTO key_app_connections (key_id, package_name, app_name, status) VALUES (?, ?, ?, 'Active')");
        $ins->bind_param("iss", $key_id, $package_name, $app_name);
        $ins->execute();
        $ins->close();
    }
} catch (Exception $e) { /* table optional */ }

// First-time activation
if ($key_data['status'] === 'Unused') {
    $new_expiry = null;
    if ($key_data['duration_hours'] > 0) {
        $new_expiry = date('Y-m-d H:i:s', time() + ($key_data['duration_hours'] * 3600));
    }
    $upd = $conn->prepare("UPDATE sdk_keys SET status = 'Active', expires_at = ? WHERE id = ?");
    $upd->bind_param("si", $new_expiry, $key_id);
    $upd->execute();
    $upd->close();
    $key_data['expires_at'] = $new_expiry;
}

write_log($conn, $tenant_code, 'api', 'success', 'Connection established', $user_key);

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'server_mode' => 'online',
    'expiry' => $key_data['expires_at'] ?? '',
    'message' => $server_settings['success_message'] ?? 'Server is online',
    'server_notification' => [
        'enabled' => 1,
        'title' => $server_settings['notification_title'] ?? 'System Note',
        'message' => $server_settings['notification_message'] ?? 'Welcome!',
    ]
]);
exit();
?>
