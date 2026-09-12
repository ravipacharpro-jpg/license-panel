<?php
// public/api/validate.php - POST /api/validate  {key, hwid}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'reason' => 'Use POST']);
    exit;
}
require_once __DIR__ . '/../../src/helpers.php';

$raw = file_get_contents('php://input');
$data = [];
if ($raw) { $j = json_decode($raw, true); if (is_array($j)) $data = $j; }
$key = trim($data['key'] ?? $_POST['key'] ?? $_POST['key_string'] ?? '');
$hwid = trim($data['hwid'] ?? $_POST['hwid'] ?? '');

if ($key === '') { echo json_encode(['valid'=>false,'reason'=>'key required']); exit; }

try {
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM license_keys WHERE key_string = ? LIMIT 1');
    $st->execute([$key]);
    $k = $st->fetch();
    if (!$k) { echo json_encode(['valid'=>false,'reason'=>'invalid_key']); exit; }
    if (($k['status'] ?? '') === 'revoked') { echo json_encode(['valid'=>false,'reason'=>'revoked','expires_at'=>$k['expires_at']]); exit; }
    if (($k['status'] ?? '') !== 'active') { echo json_encode(['valid'=>false,'reason'=>$k['status']]); exit; }

    // expiry check
    if (!empty($k['expires_at'])) {
        $exp = strtotime($k['expires_at'] . ' UTC');
        if ($exp !== false && time() > $exp) {
            $pdo->prepare("UPDATE license_keys SET status='expired' WHERE id=?")->execute([$k['id']]);
            echo json_encode(['valid'=>false,'reason'=>'expired','expires_at'=>$k['expires_at']]);
            exit;
        }
    }

    // HWID lock
    $limit = max(1, (int)($k['device_limit'] ?? 1));
    $stored = trim((string)($k['hwid'] ?? ''));
    $list = [];
    if ($stored !== '') {
        $dec = json_decode($stored, true);
        if (is_array($dec)) $list = array_values(array_filter(array_map('strval', $dec)));
        elseif ($stored !== '') $list = [$stored]; // legacy single hwid
    }
    if ($hwid !== '') {
        if (!in_array($hwid, $list, true)) {
            if (count($list) >= $limit) {
                log_activity(null, 'key_validate_fail', (int)$k['id'], 'hwid_limit key='.$key);
                echo json_encode(['valid'=>false,'reason'=>'hwid_limit','devices'=>count($list),'device_limit'=>$limit,'expires_at'=>$k['expires_at']]);
                exit;
            }
            $list[] = $hwid;
            $pdo->prepare('UPDATE license_keys SET hwid=? WHERE id=?')->execute([json_encode($list), $k['id']]);
        }
    }

    echo json_encode([
        'valid' => true,
        'key' => $key,
        'duration_type' => $k['duration_type'],
        'expires_at' => $k['expires_at'],
        'device_limit' => $limit,
        'devices' => count($list),
    ]);
} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['valid'=>false,'reason'=>'server_error','detail'=>$ex->getMessage()]);
}
