<?php
// public/actions.php - all dashboard POST actions
require_once __DIR__ . '/../src/auth.php';

$user = require_login();
$pdo = db();

function back(string $tab = '', string $type = '', string $msg = ''): void {
    if ($msg !== '') flash_set($type ?: 'ok', $msg);
    $url = 'dashboard.php' . ($tab !== '' ? '?tab=' . urlencode($tab) : '');
    redirect($url);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('dashboard.php');
if (!verify_csrf($_POST['csrf'] ?? null)) back('', 'err', 'Invalid CSRF. Refresh and retry.');

$action = trim($_POST['action'] ?? '');
$role = $user['role'];
$uid  = (int)$user['id'];
// refresh user (balance may change)
$fresh = current_user() ?: $user;

try {
switch ($action) {

  case 'generate_key': {
    $duration = trim($_POST['duration_type'] ?? '30days');
    if (!in_array($duration, ['1day','7days','30days','lifetime'], true)) $duration = '30days';
    $deviceLimit = max(1, min(20, (int)($_POST['device_limit'] ?? 1)));
    $assigned = trim($_POST['assigned_to'] ?? '');
    $qty = max(1, min(20, (int)($_POST['qty'] ?? 1)));
    if ($role === 'reseller' && $qty > 10) $qty = 10;

    $priceEach = price_for_duration($duration);
    $total = $priceEach * $qty;

    if ($role === 'reseller') {
        // check balance
        $st = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
        $st->execute([$uid]);
        $bal = (float)($st->fetch()['wallet_balance'] ?? 0);
        if ($bal < $total) back('keys','err',"Insufficient balance. Need ₹{$total}, have ₹{$bal}. Top-up first.");
        // deduct
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?')->execute([$total, $uid]);
        $pdo->prepare("INSERT INTO transactions (user_id,type,amount,reference,status) VALUES (?,'purchase',?, ?, 'completed')")
            ->execute([$uid, -$total, "buy {$qty}x {$duration}"]);
        $made = [];
        for ($i=0;$i<$qty;$i++) {
            $ks = generate_unique_key($pdo);
            $exp = calc_expiry($duration);
            $pdo->prepare('INSERT INTO license_keys (key_string,created_by,assigned_to,duration_type,expires_at,device_limit,status) VALUES (?,?,?,?,?,?,?)')
                ->execute([$ks, $uid, ($assigned!==''?$assigned:null), $duration, $exp, $deviceLimit, 'active']);
            $made[] = $ks;
            log_activity($uid, 'key_generate', (int)$pdo->lastInsertId(), "reseller buy {$duration} ₹{$priceEach}");
        }
        $pdo->commit();
        back('keys','ok',"{$qty} key(s) generated. ₹{$total} deducted.");
    } else {
        // owner/admin free generation
        $pdo->beginTransaction();
        for ($i=0;$i<$qty;$i++) {
            $ks = generate_unique_key($pdo);
            $exp = calc_expiry($duration);
            $pdo->prepare('INSERT INTO license_keys (key_string,created_by,assigned_to,duration_type,expires_at,device_limit,status) VALUES (?,?,?,?,?,?,?)')
                ->execute([$ks, $uid, ($assigned!==''?$assigned:null), $duration, $exp, $deviceLimit, 'active']);
            log_activity($uid, 'key_generate', (int)$pdo->lastInsertId(), "{$role} gen {$duration}");
        }
        $pdo->commit();
        back('keys','ok',"{$qty} key(s) generated (free, {$role}).");
    }
    break;
  }

  case 'revoke_key': {
    $id = (int)($_POST['key_id'] ?? 0);
    $st = $pdo->prepare('SELECT * FROM license_keys WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $k = $st->fetch();
    if (!$k) back('keys','err','Key not found.');
    if ($role === 'reseller' && (int)$k['created_by'] !== $uid) back('keys','err','You can revoke only your keys.');
    $pdo->prepare("UPDATE license_keys SET status='revoked' WHERE id=?")->execute([$id]);
    log_activity($uid, 'key_revoke', $id, 'revoked '.$k['key_string']);
    back('keys','ok','Key revoked.');
    break;
  }

  case 'activate_key': {
    $id = (int)($_POST['key_id'] ?? 0);
    if (!in_array($role, ['owner','admin'], true)) back('keys','err','Only admin/owner.');
    $pdo->prepare("UPDATE license_keys SET status='active' WHERE id=?")->execute([$id]);
    log_activity($uid, 'key_activate', $id, 'activated');
    back('keys','ok','Key activated.');
    break;
  }

  case 'delete_key': {
    $id = (int)($_POST['key_id'] ?? 0);
    if ($role !== 'owner') back('keys','err','Only owner can delete.');
    $pdo->prepare('DELETE FROM license_keys WHERE id=?')->execute([$id]);
    log_activity($uid, 'key_delete', $id, 'deleted');
    back('keys','ok','Key deleted.');
    break;
  }

  case 'topup_request': {
    $amount = (float)($_POST['amount'] ?? 0);
    $ref = trim($_POST['reference'] ?? '');
    if ($amount < 10) back('wallet','err','Min top-up ₹10.');
    if ($ref === '') back('wallet','err','UPI reference / UTR required.');
    $pdo->prepare("INSERT INTO transactions (user_id,type,amount,reference,status) VALUES (?,'topup',?,?,'pending')")
        ->execute([$uid, $amount, $ref]);
    log_activity($uid, 'topup_request', null, "₹{$amount} ref={$ref}");
    back('wallet','ok','Top-up request sent. Admin will approve soon.');
    break;
  }

  case 'topup_approve': {
    if (!in_array($role, ['owner','admin'], true)) back('topups','err','Forbidden.');
    $id = (int)($_POST['tx_id'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM transactions WHERE id=? AND type='topup' AND status='pending' LIMIT 1");
    $st->execute([$id]);
    $tx = $st->fetch();
    if (!$tx) back('topups','err','Request not found/already handled.');
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE transactions SET status='completed' WHERE id=?")->execute([$id]);
    $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id=?')->execute([(float)$tx['amount'], (int)$tx['user_id']]);
    // referral commission to referrer of this user
    $pct = (float)get_setting('referral_percent','10');
    $uRow = $pdo->prepare('SELECT referred_by FROM users WHERE id=?')->execute([(int)$tx['user_id']]) ? null : null;
    $q = $pdo->prepare('SELECT referred_by FROM users WHERE id=? LIMIT 1');
    $q->execute([(int)$tx['user_id']]);
    $ur = $q->fetch();
    if ($ur && !empty($ur['referred_by']) && $pct > 0) {
        $comm = round(((float)$tx['amount']) * $pct / 100, 2);
        if ($comm > 0) {
            $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id=?')->execute([$comm, (int)$ur['referred_by']]);
            $pdo->prepare("INSERT INTO transactions (user_id,type,amount,reference,status) VALUES (?,'commission',?,?,'completed')")
                ->execute([(int)$ur['referred_by'], $comm, 'ref commission from user#'.(int)$tx['user_id'].' topup#'.$id]);
        }
    }
    $pdo->commit();
    log_activity($uid, 'topup_approve', null, "tx#{$id} ₹{$tx['amount']}");
    back('topups','ok',"Top-up #{$id} approved.");
    break;
  }

  case 'topup_reject': {
    if (!in_array($role, ['owner','admin'], true)) back('topups','err','Forbidden.');
    $id = (int)($_POST['tx_id'] ?? 0);
    $pdo->prepare("UPDATE transactions SET status='rejected' WHERE id=? AND status='pending'")->execute([$id]);
    log_activity($uid, 'topup_reject', null, "tx#{$id}");
    back('topups','ok',"Top-up #{$id} rejected.");
    break;
  }

  case 'ban_user': {
    if (!in_array($role, ['owner','admin'], true)) back('users','err','Forbidden.');
    $tid = (int)($_POST['user_id'] ?? 0);
    if ($tid === $uid) back('users','err','You cannot ban yourself.');
    $t = $pdo->prepare('SELECT role FROM users WHERE id=?')->execute([$tid]) ? null : null;
    $q = $pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1'); $q->execute([$tid]); $tu = $q->fetch();
    if (!$tu) back('users','err','User not found.');
    if ($role === 'admin' && in_array($tu['role'], ['owner','admin'], true)) back('users','err','Admin can ban only resellers.');
    $pdo->prepare("UPDATE users SET status='banned' WHERE id=?")->execute([$tid]);
    log_activity($uid, 'user_ban', null, "banned user#{$tid} {$tu['email']}");
    back('users','ok','User banned.');
    break;
  }

  case 'unban_user': {
    if (!in_array($role, ['owner','admin'], true)) back('users','err','Forbidden.');
    $tid = (int)($_POST['user_id'] ?? 0);
    $pdo->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$tid]);
    log_activity($uid, 'user_unban', null, "unbanned user#{$tid}");
    back('users','ok','User unbanned.');
    break;
  }

  case 'set_role': {
    if ($role !== 'owner') back('users','err','Only owner can change roles.');
    $tid = (int)($_POST['user_id'] ?? 0);
    $newRole = trim($_POST['new_role'] ?? '');
    if (!in_array($newRole, ['admin','reseller'], true)) back('users','err','Invalid role.');
    if ($tid === $uid) back('users','err','Cannot change own role.');
    $pdo->prepare('UPDATE users SET role=? WHERE id=?')->execute([$newRole, $tid]);
    log_activity($uid, 'role_change', null, "user#{$tid} -> {$newRole}");
    back('users','ok',"Role updated to {$newRole}.");
    break;
  }

  case 'update_pricing': {
    if ($role !== 'owner') back('settings','err','Only owner.');
    $fields = ['price_1day','price_7days','price_30days','price_lifetime','referral_percent','upi_id','app_name'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $v = trim((string)$_POST[$f]);
            if (str_starts_with($f, 'price_') && !is_numeric($v)) continue;
            if ($f === 'referral_percent') { $v = (string)max(0, min(50, (float)$v)); }
            set_setting($f, $v);
        }
    }
    log_activity($uid, 'settings_update');
    back('settings','ok','Settings saved.');
    break;
  }

  default:
    back('', 'err', 'Unknown action.');
}
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) { try { $pdo->rollBack(); } catch (Throwable $_) {} }
    back('', 'err', 'Error: ' . $ex->getMessage());
}
