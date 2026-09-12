<?php
// public/actions.php - dashboard POST actions (reference features only)
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

try {
switch ($action) {

  // ---------- Mods ----------
  case 'mod_save': {
    if (!in_array($role, ['owner','admin'], true)) back('mods','err','Forbidden.');
    $modId = (int)($_POST['mod_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $version = trim($_POST['version'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $plink = trim($_POST['purchase_link'] ?? '');
    $status = in_array(($_POST['status'] ?? 'active'), ['active','inactive'], true) ? $_POST['status'] : 'active';
    if ($name === '') back('mods','err','Mod name required.');
    if ($modId > 0) {
        $pdo->prepare('UPDATE mods SET name=?, description=?, version=?, features=?, purchase_link=?, status=? WHERE id=?')
            ->execute([$name, $desc !== '' ? $desc : null, $version !== '' ? $version : null, $features !== '' ? $features : null, $plink !== '' ? $plink : null, $status, $modId]);
        log_activity($uid, 'mod_edit', null, "mod#{$modId} {$name}");
        back('mods','ok','Mod updated.');
    } else {
        $pdo->prepare("INSERT INTO mods (name,description,version,features,purchase_link,status) VALUES (?,?,?,?,?,?)")
            ->execute([$name, $desc !== '' ? $desc : null, $version !== '' ? $version : null, $features !== '' ? $features : null, $plink !== '' ? $plink : null, $status]);
        log_activity($uid, 'mod_add', null, "mod {$name}");
        back('mods','ok','Mod added.');
    }
    break;
  }

  case 'mod_delete': {
    if (!in_array($role, ['owner','admin'], true)) back('mods','err','Forbidden.');
    $id = (int)($_POST['mod_id'] ?? 0);
    $pdo->prepare('DELETE FROM mods WHERE id=?')->execute([$id]);
    log_activity($uid, 'mod_delete', null, "mod#{$id}");
    back('mods','ok','Mod deleted.');
    break;
  }

  case 'mod_toggle': {
    if (!in_array($role, ['owner','admin'], true)) back('mods','err','Forbidden.');
    $id = (int)($_POST['mod_id'] ?? 0);
    $pdo->prepare("UPDATE mods SET status = CASE WHEN status='active' THEN 'inactive' ELSE 'active' END WHERE id=?")->execute([$id]);
    back('mods','ok','Mod status toggled.');
    break;
  }

  // ---------- Plans ----------
  case 'plan_save': {
    if (!in_array($role, ['owner','admin'], true)) back('plans','err','Forbidden.');
    $planId = (int)($_POST['plan_id'] ?? 0);
    $modId = (int)($_POST['mod_id'] ?? 0);
    $pname = trim($_POST['plan_name'] ?? '');
    $dur = max(0, (int)($_POST['duration'] ?? 30));
    $dtype = strtolower(trim($_POST['duration_type'] ?? 'days'));
    $price = max(0, (float)($_POST['price'] ?? 0));
    $feats = trim($_POST['features'] ?? '');
    if (!in_array($dtype, ['minutes','hours','days','months','lifetime'], true)) $dtype = 'days';
    if ($pname === '' || $modId <= 0) back('plans','err','Mod + plan name required.');
    if ($planId > 0) {
        $pdo->prepare('UPDATE mod_plans SET mod_id=?, plan_name=?, duration=?, duration_type=?, price=?, features=? WHERE id=?')
            ->execute([$modId, $pname, $dur, $dtype, $price, $feats !== '' ? $feats : null, $planId]);
        back('plans','ok','Plan updated.');
    } else {
        $pdo->prepare("INSERT INTO mod_plans (mod_id,plan_name,duration,duration_type,price,features,status) VALUES (?,?,?,?,?,?,'active')")
            ->execute([$modId, $pname, $dur, $dtype, $price, $feats !== '' ? $feats : null]);
        back('plans','ok','Plan added.');
    }
    break;
  }

  case 'plan_delete': {
    if (!in_array($role, ['owner','admin'], true)) back('plans','err','Forbidden.');
    $id = (int)($_POST['plan_id'] ?? 0);
    $pdo->prepare('DELETE FROM mod_plans WHERE id=?')->execute([$id]);
    back('plans','ok','Plan deleted.');
    break;
  }

  case 'plan_toggle': {
    if (!in_array($role, ['owner','admin'], true)) back('plans','err','Forbidden.');
    $id = (int)($_POST['plan_id'] ?? 0);
    $pdo->prepare("UPDATE mod_plans SET status = CASE WHEN status='active' THEN 'inactive' ELSE 'active' END WHERE id=?")->execute([$id]);
    back('plans','ok','Plan status toggled.');
    break;
  }

  // ---------- License keys: bulk generate for mod (available pool) ----------
  case 'mod_key_generate': {
    if (!in_array($role, ['owner','admin'], true)) back('keys','err','Forbidden.');
    $modId = (int)($_POST['mod_id'] ?? 0);
    $dur = max(0, (int)($_POST['duration'] ?? 30));
    $dtype = strtolower(trim($_POST['duration_type'] ?? 'days'));
    $price = max(0, (float)($_POST['price'] ?? 0));
    $qty = max(1, min(50, (int)($_POST['qty'] ?? 1)));
    if (!in_array($dtype, ['minutes','hours','days','months','lifetime'], true)) $dtype = 'days';
    if ($modId <= 0) back('keys','err','Select mod.');
    $pdo->beginTransaction();
    for ($i = 0; $i < $qty; $i++) {
        $ks = generate_unique_key($pdo);
        $pdo->prepare("INSERT INTO license_keys (key_string,created_by,mod_id,duration,duration_type,price,status,device_limit) VALUES (?,?,?,?,?,?,'available',1)")
            ->execute([$ks, $uid, $modId, $dur, $dtype, $price]);
        log_activity($uid, 'key_generate', (int)$pdo->lastInsertId(), "mod#{$modId} {$dur} {$dtype} ₹{$price}");
    }
    $pdo->commit();
    back('keys','ok',"{$qty} key(s) generated to available pool.");
    break;
  }

  case 'key_block': {
    if (!in_array($role, ['owner','admin'], true)) back('keys','err','Forbidden.');
    $id = (int)($_POST['key_id'] ?? 0);
    $pdo->prepare("UPDATE license_keys SET status='blocked' WHERE id=?")->execute([$id]);
    log_activity($uid, 'key_block', $id, 'blocked');
    back('keys','ok','Key blocked.');
    break;
  }

  case 'key_unblock': {
    if (!in_array($role, ['owner','admin'], true)) back('keys','err','Forbidden.');
    $id = (int)($_POST['key_id'] ?? 0);
    $st = $pdo->prepare('SELECT sold_to FROM license_keys WHERE id=? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    $new = ($row && !empty($row['sold_to'])) ? 'sold' : 'available';
    $pdo->prepare('UPDATE license_keys SET status=? WHERE id=?')->execute([$new, $id]);
    log_activity($uid, 'key_unblock', $id, 'unblocked');
    back('keys','ok','Key unblocked.');
    break;
  }

  case 'key_expire': {
    if (!in_array($role, ['owner','admin'], true)) back('keys','err','Forbidden.');
    $id = (int)($_POST['key_id'] ?? 0);
    $pdo->prepare("UPDATE license_keys SET status='expired' WHERE id=?")->execute([$id]);
    log_activity($uid, 'key_expire', $id, 'expired');
    back('keys','ok','Key expired.');
    break;
  }

  case 'key_delete': {
    if (!in_array($role, ['owner','admin'], true)) back('keys','err','Forbidden.');
    $id = (int)($_POST['key_id'] ?? 0);
    $pdo->prepare('DELETE FROM license_keys WHERE id=?')->execute([$id]);
    log_activity($uid, 'key_delete', $id, 'deleted');
    back('keys','ok','Key deleted.');
    break;
  }

  // ---------- APK upload ----------
  case 'apk_upload': {
    if (!in_array($role, ['owner','admin'], true)) back('downloads','err','Forbidden.');
    $modId = (int)($_POST['mod_id'] ?? 0);
    if ($modId <= 0) back('downloads','err','Select mod.');
    if (empty($_FILES['apk']) || ($_FILES['apk']['error'] ?? 4) !== 0) back('downloads','err','APK file required.');
    $upDir = dirname(__DIR__) . '/data/uploads';
    if (!is_dir($upDir)) @mkdir($upDir, 0775, true);
    $orig = basename($_FILES['apk']['name']);
    $safe = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
    $dest = $upDir . '/' . $safe;
    if (!@move_uploaded_file($_FILES['apk']['tmp_name'], $dest)) back('downloads','err','Upload failed.');
    $rel = 'data/uploads/' . $safe;
    $pdo->prepare('INSERT INTO mod_apks (mod_id,file_name,file_path,file_size) VALUES (?,?,?,?)')
        ->execute([$modId, $orig, $rel, (int)$_FILES['apk']['size']]);
    log_activity($uid, 'apk_upload', null, "mod#{$modId} {$orig}");
    back('downloads','ok','APK uploaded.');
    break;
  }

  case 'apk_delete': {
    if (!in_array($role, ['owner','admin'], true)) back('downloads','err','Forbidden.');
    $id = (int)($_POST['apk_id'] ?? 0);
    $st = $pdo->prepare('SELECT * FROM mod_apks WHERE id=? LIMIT 1'); $st->execute([$id]); $row = $st->fetch();
    if ($row) {
        $abs = dirname(__DIR__) . '/' . $row['file_path'];
        if (is_file($abs)) @unlink($abs);
        $pdo->prepare('DELETE FROM mod_apks WHERE id=?')->execute([$id]);
    }
    back('downloads','ok','APK deleted.');
    break;
  }

  // ---------- Store order (UPI, pending approval) ----------
  case 'store_order': {
    $planId = (int)($_POST['plan_id'] ?? 0);
    $txn = trim($_POST['upi_txn_id'] ?? '');
    if ($planId <= 0) back('store','err','Invalid plan.');
    if ($txn === '') back('store','err','UPI Transaction / UTR ID required.');
    $st = $pdo->prepare("SELECT p.*, m.name AS mod_name FROM mod_plans p LEFT JOIN mods m ON m.id=p.mod_id WHERE p.id=? AND p.status='active' LIMIT 1");
    $st->execute([$planId]);
    $plan = $st->fetch();
    if (!$plan) back('store','err','Plan not available.');
    $ref = 'Plan purchase: ' . ($plan['mod_name'] ?? 'Mod') . ' - ' . $plan['plan_name'];
    $pdo->prepare("INSERT INTO transactions (user_id,amount,type,reference,status,plan_id,upi_txn_id) VALUES (?,?,?,?, 'pending',?,?)")
        ->execute([$uid, (float)$plan['price'], 'purchase', $ref, $planId, $txn]);
    log_activity($uid, 'order_place', null, "plan#{$planId} ₹{$plan['price']} utr={$txn}");
    back('orders','ok','Order placed! Admin will verify UPI payment and approve shortly.');
    break;
  }

  // ---------- Approvals (auto-generate key on approve) ----------
  case 'order_approve': {
    if (!in_array($role, ['owner','admin'], true)) back('orders','err','Forbidden.');
    $id = (int)($_POST['tx_id'] ?? 0);
    $st = $pdo->prepare("SELECT * FROM transactions WHERE id=? AND status='pending' AND type='purchase' LIMIT 1");
    $st->execute([$id]);
    $tx = $st->fetch();
    if (!$tx) back('orders','err','Order not found/already handled.');
    $pdo->beginTransaction();
    if (!empty($tx['plan_id'])) {
        $p = $pdo->prepare('SELECT * FROM mod_plans WHERE id=? LIMIT 1'); $p->execute([(int)$tx['plan_id']]); $plan = $p->fetch();
        if (!$plan) throw new Exception('Plan not found.');
        $pdo->prepare("UPDATE transactions SET status='completed' WHERE id=?")->execute([$id]);
        $ks = generate_unique_key($pdo);
        $exp = ($plan['duration_type'] === 'lifetime') ? null : calc_expiry_plan((int)$plan['duration'], (string)$plan['duration_type']);
        $pdo->prepare("INSERT INTO license_keys (key_string,created_by,assigned_to,mod_id,duration,duration_type,expires_at,price,status,sold_to,sold_at,device_limit) VALUES (?,?,?,?,?,?,?,?, 'sold',?,?,1)")
            ->execute([$ks, $uid, null, (int)$plan['mod_id'], (int)$plan['duration'], (string)$plan['duration_type'], $exp, (float)$plan['price'], (int)$tx['user_id'], date('Y-m-d H:i:s')]);
        log_activity($uid, 'order_approve', (int)$pdo->lastInsertId(), "tx#{$id} key={$ks}");
        $pdo->commit();
        back('orders','ok',"Order #{$id} approved. Key {$ks} assigned.");
    } else {
        $pdo->prepare("UPDATE transactions SET status='completed' WHERE id=?")->execute([$id]);
        $pdo->commit();
        back('orders','ok',"Order #{$id} approved.");
    }
    break;
  }

  case 'order_reject': {
    if (!in_array($role, ['owner','admin'], true)) back('orders','err','Forbidden.');
    $id = (int)($_POST['tx_id'] ?? 0);
    $pdo->prepare("UPDATE transactions SET status='failed' WHERE id=? AND status='pending'")->execute([$id]);
    log_activity($uid, 'order_reject', null, "tx#{$id}");
    back('orders','ok',"Order #{$id} rejected.");
    break;
  }

  // ---------- Buy available key with wallet ----------
  case 'purchase_available_key': {
    $keyId = (int)($_POST['key_id'] ?? 0);
    if ($keyId <= 0) back('store','err','Invalid key.');
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT * FROM license_keys WHERE id=? LIMIT 1');
    $st->execute([$keyId]);
    $key = $st->fetch();
    if (!$key || !empty($key['sold_to']) || ($key['status'] ?? '') !== 'available') throw new Exception('Key no longer available.');
    $price = (float)($key['price'] ?? 0);
    $b = $pdo->prepare('SELECT wallet_balance FROM users WHERE id=?'); $b->execute([$uid]);
    $bal = (float)($b->fetch()['wallet_balance'] ?? 0);
    if ($bal < $price) throw new Exception("Insufficient balance. Need ₹{$price}.");
    $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id=?')->execute([$price, $uid]);
    $pdo->prepare("UPDATE license_keys SET status='sold', sold_to=?, sold_at=? WHERE id=?")->execute([$uid, date('Y-m-d H:i:s'), $keyId]);
    $pdo->prepare("INSERT INTO transactions (user_id,type,amount,reference,status) VALUES (?,'purchase',?,?,'completed')")
        ->execute([$uid, -$price, 'License purchase #' . $keyId]);
    $pdo->commit();
    log_activity($uid, 'key_purchase', $keyId, "bought ₹{$price}");
    back('store','ok','License key purchased successfully!');
    break;
  }

  // ---------- Admin direct balance add ----------
  case 'balance_add': {
    if (!in_array($role, ['owner','admin'], true)) back('users','err','Forbidden.');
    $tid = (int)($_POST['user_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $ref = trim($_POST['reference'] ?? 'Admin credit');
    if ($tid <= 0 || $amount <= 0) back('users','err','Select user + valid amount.');
    $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id=?')->execute([$amount, $tid]);
    $pdo->prepare("INSERT INTO transactions (user_id,type,amount,reference,status) VALUES (?,'balance_add',?,?,'completed')")
        ->execute([$tid, $amount, $ref !== '' ? $ref : 'Admin credit']);
    log_activity($uid, 'balance_add', null, "user#{$tid} +₹{$amount}");
    back('users','ok','Balance added successfully!');
    break;
  }

  // ---------- Signup tokens ----------
  case 'token_generate': {
    if (!in_array($role, ['owner','admin'], true)) back('tokens','err','Forbidden.');
    $days = max(1, min(365, (int)($_POST['expiry_days'] ?? 7)));
    $code = generate_token_code($pdo);
    $exp = date('Y-m-d H:i:s', strtotime("+{$days} days"));
    $pdo->prepare("INSERT INTO referral_tokens (code,created_by,expires_at,status) VALUES (?,?,?,'active')")
        ->execute([$code, $uid, $exp]);
    log_activity($uid, 'token_generate', null, $code);
    back('tokens','ok',"Token generated: {$code}");
    break;
  }

  case 'token_deactivate': {
    if (!in_array($role, ['owner','admin'], true)) back('tokens','err','Forbidden.');
    $id = (int)($_POST['token_id'] ?? 0);
    $pdo->prepare("UPDATE referral_tokens SET status='inactive' WHERE id=?")->execute([$id]);
    back('tokens','ok','Token deactivated.');
    break;
  }

  case 'token_delete': {
    if (!in_array($role, ['owner','admin'], true)) back('tokens','err','Forbidden.');
    $id = (int)($_POST['token_id'] ?? 0);
    $pdo->prepare('DELETE FROM referral_tokens WHERE id=?')->execute([$id]);
    back('tokens','ok','Token deleted.');
    break;
  }

  // ---------- Users ----------
  case 'ban_user': {
    if (!in_array($role, ['owner','admin'], true)) back('users','err','Forbidden.');
    $tid = (int)($_POST['user_id'] ?? 0);
    if ($tid === $uid) back('users','err','You cannot ban yourself.');
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

  // ---------- Site settings (branding + UPI only) ----------
  case 'update_site': {
    if ($role !== 'owner') back('settings','err','Only owner.');
    foreach (['app_name','site_tagline','telegram_link','support_email','upi_id'] as $f) {
        if (isset($_POST[$f])) set_setting($f, trim((string)$_POST[$f]));
    }
    log_activity($uid, 'settings_update');
    back('settings','ok','Settings saved.');
    break;
  }

  case 'regen_api_key': {
    if ($role !== 'owner') back('api','err','Only owner.');
    $k = bin2hex(random_bytes(16));
    set_setting('admin_api_key', $k);
    log_activity($uid, 'api_key_regen');
    back('api','ok','Admin API key regenerated.');
    break;
  }

  // ---------- Profile ----------
  case 'profile_update': {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if (strlen($name) < 2) back('profile','err','Enter name.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back('profile','err','Invalid email.');
    $chk = $pdo->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');
    $chk->execute([$email, $uid]);
    if ($chk->fetch()) back('profile','err','Email already used.');
    $pdo->prepare('UPDATE users SET name=?, email=? WHERE id=?')->execute([$name, $email, $uid]);
    back('profile','ok','Profile updated.');
    break;
  }

  case 'password_change': {
    $cur = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $conf = $_POST['confirm_password'] ?? '';
    if ($new !== $conf) back('profile','err','Passwords do not match.');
    if (strlen($new) < 6) back('profile','err','Min 6 chars.');
    $st = $pdo->prepare('SELECT password_hash FROM users WHERE id=?'); $st->execute([$uid]);
    $row = $st->fetch();
    if (!$row || !password_verify($cur, $row['password_hash'])) back('profile','err','Current password wrong.');
    $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
    log_activity($uid, 'password_change');
    back('profile','ok','Password changed.');
    break;
  }

  default:
    back('', 'err', 'Unknown action.');
}
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) { try { $pdo->rollBack(); } catch (Throwable $_) {} }
    back('', 'err', 'Error: ' . $ex->getMessage());
}
