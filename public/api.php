<?php
// public/api.php - MultiPanelX-compatible validation endpoint
// GET /api.php?key=XXX&device_id=YYY  (or ?action=verify&key=..&device_id=..)
// POST admin: api_key + action=block/unblock/expire/delete/edit + key_id
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
require_once __DIR__ . '/../src/helpers.php';

function out(array $a, int $code = 200): void {
    http_response_code($code);
    echo json_encode($a);
    exit;
}

// ---------- ADMIN API (POST api_key) ----------
if (isset($_POST['api_key'])) {
    $saved = get_setting('admin_api_key', '');
    if ($saved === '' || !hash_equals($saved, trim((string)$_POST['api_key']))) {
        out(['status' => 'error', 'message' => 'Unauthorized. Invalid Admin API Key'], 403);
    }
    $action = trim($_POST['action'] ?? '');
    $keyId = (int)($_POST['key_id'] ?? 0);
    if ($action === '' || $keyId <= 0) out(['status' => 'error', 'message' => 'action + key_id required'], 400);
    try {
        $pdo = db();
        $st = $pdo->prepare('SELECT * FROM license_keys WHERE id=? LIMIT 1');
        $st->execute([$keyId]);
        $kd = $st->fetch();
        if (!$kd) out(['status' => 'error', 'message' => 'License key not found'], 404);
        switch ($action) {
            case 'block':
                $pdo->prepare("UPDATE license_keys SET status='blocked' WHERE id=?")->execute([$keyId]);
                out(['status' => 'success', 'message' => 'License key successfully blocked']);
            case 'unblock': {
                // mod keys: available if unsold else sold; generic: active
                $new = !empty($kd['mod_id']) ? (empty($kd['sold_to']) ? 'available' : 'sold') : 'active';
                $pdo->prepare('UPDATE license_keys SET status=? WHERE id=?')->execute([$new, $keyId]);
                out(['status' => 'success', 'message' => 'License key successfully unblocked']);
            }
            case 'expire':
                $pdo->prepare("UPDATE license_keys SET status='expired' WHERE id=?")->execute([$keyId]);
                out(['status' => 'success', 'message' => 'License key successfully expired']);
            case 'delete':
                $pdo->prepare('DELETE FROM license_keys WHERE id=?')->execute([$keyId]);
                out(['status' => 'success', 'message' => 'License key successfully deleted']);
            case 'edit': {
                $dur = isset($_POST['duration']) ? (int)$_POST['duration'] : (int)($kd['duration'] ?? 0);
                $dtype = isset($_POST['duration_type']) ? trim((string)$_POST['duration_type']) : (string)($kd['duration_type'] ?? 'days');
                $price = isset($_POST['price']) ? (float)$_POST['price'] : (float)($kd['price'] ?? 0);
                $reset = isset($_POST['reset_device']) && $_POST['reset_device'] == '1';
                if ($reset) {
                    $pdo->prepare('UPDATE license_keys SET duration=?, duration_type=?, price=?, device_id=NULL, hwid=NULL WHERE id=?')
                        ->execute([$dur, $dtype, $price, $keyId]);
                } else {
                    $pdo->prepare('UPDATE license_keys SET duration=?, duration_type=?, price=? WHERE id=?')
                        ->execute([$dur, $dtype, $price, $keyId]);
                }
                out(['status' => 'success', 'message' => 'License key successfully edited']);
            }
            default:
                out(['status' => 'error', 'message' => 'Invalid action. Use: block, unblock, expire, delete, edit'], 400);
        }
    } catch (Throwable $ex) {
        out(['status' => 'error', 'message' => 'DB error: ' . $ex->getMessage()], 500);
    }
}

// ---------- APP VERIFY (GET) ----------
$key = trim($_GET['key'] ?? $_POST['key'] ?? '');
$deviceId = trim($_GET['device_id'] ?? $_GET['hwid'] ?? $_POST['device_id'] ?? $_POST['hwid'] ?? '');
if ($key === '') out(['status' => 'error', 'message' => 'License key is required', 'valid' => false], 400);

try {
    $pdo = db();
    $st = $pdo->prepare('SELECT lk.*, m.name AS mod_name, m.status AS mod_status FROM license_keys lk LEFT JOIN mods m ON m.id = lk.mod_id WHERE lk.key_string = ? LIMIT 1');
    $st->execute([$key]);
    $lic = $st->fetch();
    if (!$lic) out(['status' => 'error', 'message' => 'Invalid license key', 'valid' => false], 404);

    // mod disabled?
    if (!empty($lic['mod_id']) && ($lic['mod_status'] ?? 'active') !== 'active') {
        out(['status' => 'error', 'message' => 'This mod is currently disabled', 'valid' => false], 403);
    }
    // blocked / revoked
    if (in_array(($lic['status'] ?? ''), ['blocked', 'revoked'], true)) {
        out(['status' => 'error', 'message' => 'This license key has been blocked by the administrator', 'valid' => false], 403);
    }
    if (($lic['status'] ?? '') === 'expired') {
        out(['status' => 'error', 'message' => 'This license key has been manually expired by the administrator', 'valid' => false], 403);
    }

    // mod keys must be sold
    if (!empty($lic['mod_id']) && empty($lic['sold_to'])) {
        out(['status' => 'error', 'message' => 'License key has not been activated or sold yet', 'valid' => false], 403);
    }
    // generic keys must be active/sold
    if (empty($lic['mod_id']) && !in_array(($lic['status'] ?? ''), ['active', 'sold', 'available'], true)) {
        out(['status' => 'error', 'message' => 'License key is not active', 'valid' => false], 403);
    }

    // expiry
    if (!empty($lic['mod_id'])) {
        if (plan_key_expired($lic)) {
            try { $pdo->prepare("UPDATE license_keys SET status='expired' WHERE id=?")->execute([$lic['id']]); } catch (Throwable $ex) {}
            out(['status' => 'error', 'message' => 'License key has expired', 'valid' => false], 403);
        }
    } else {
        if (!empty($lic['expires_at']) && strtotime($lic['expires_at'] . ' UTC') < time()) {
            try { $pdo->prepare("UPDATE license_keys SET status='expired' WHERE id=?")->execute([$lic['id']]); } catch (Throwable $ex) {}
            out(['status' => 'error', 'message' => 'License key has expired', 'valid' => false], 403);
        }
    }

    // device lock: single device_id (ref style) + hwid list (multi-device)
    if ($deviceId !== '') {
        $boundSingle = trim((string)($lic['device_id'] ?? ''));
        if ($boundSingle === '') {
            // check hwid list too
            $stored = trim((string)($lic['hwid'] ?? ''));
            $list = $stored !== '' ? (json_decode($stored, true) ?: [$stored]) : [];
            if (!is_array($list)) $list = [];
            $limit = max(1, (int)($lic['device_limit'] ?? 1));
            if (!in_array($deviceId, $list, true)) {
                if ($limit <= 1) {
                    // single-lock mode like ref: bind device_id
                    $pdo->prepare('UPDATE license_keys SET device_id=? WHERE id=?')->execute([$deviceId, $lic['id']]);
                    $lic['device_id'] = $deviceId;
                } else {
                    if (count($list) >= $limit) {
                        out(['status' => 'error', 'message' => 'License key is locked to another device', 'valid' => false, 'devices' => count($list)], 403);
                    }
                    $list[] = $deviceId;
                    $pdo->prepare('UPDATE license_keys SET hwid=? WHERE id=?')->execute([json_encode($list), $lic['id']]);
                }
            }
        } elseif ($boundSingle !== $deviceId) {
            out(['status' => 'error', 'message' => 'License key is locked to another device', 'valid' => false], 403);
        }
    } else {
        if (!empty($lic['device_id'])) {
            out(['status' => 'error', 'message' => 'Device ID is required for this locked license key', 'valid' => false], 400);
        }
    }

    $durLabel = !empty($lic['mod_id'])
        ? plan_expiry_label(isset($lic['duration']) ? (int)$lic['duration'] : null, $lic['duration_type'] ?? null)
        : duration_label($lic['duration_type'] ?? '');
    out([
        'status' => 'success',
        'message' => 'License key validated successfully',
        'valid' => true,
        'data' => [
            'mod_name' => $lic['mod_name'] ?? null,
            'duration' => $durLabel,
            'sold_at' => $lic['sold_at'] ?? null,
            'expires_at' => $lic['expires_at'] ?? null,
            'device_id' => $lic['device_id'] ?? $deviceId,
        ],
        // flat compat
        'mod_name' => $lic['mod_name'] ?? null,
        'expires_at' => $lic['expires_at'] ?? null,
    ]);
} catch (Throwable $ex) {
    out(['status' => 'error', 'message' => 'Server database connection error', 'valid' => false], 500);
}
