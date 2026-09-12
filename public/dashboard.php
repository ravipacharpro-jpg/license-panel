<?php
require_once __DIR__ . '/../src/auth.php';
$user = require_login();
$pdo = db();
$role = $user['role'];
$uid = (int)$user['id'];
// refresh
$fresh = current_user(); if ($fresh) $user = $fresh;
$tab = $_GET['tab'] ?? 'overview';
$flash = flash_get();
$q = trim($_GET['q'] ?? '');

function stat_count(PDO $pdo, string $sql, array $p = []) { $s=$pdo->prepare($sql); $s->execute($p); return $s->fetchColumn(); }

// ---- STATS ----
if (in_array($role, ['owner','admin'], true)) {
    $totalKeys   = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys");
    $activeKeys  = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE status='active'");
    $revKeys     = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE status='revoked'");
    $expKeys     = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE status='expired'");
    $totalUsers  = (int)stat_count($pdo, "SELECT COUNT(*) FROM users");
    $activeRes   = (int)stat_count($pdo, "SELECT COUNT(*) FROM users WHERE role='reseller' AND status='active'");
    $revenue     = (float)(stat_count($pdo, "SELECT COALESCE(SUM(-amount),0) FROM transactions WHERE type='purchase' AND status='completed'") ?: 0);
    $pendTopups  = (int)stat_count($pdo, "SELECT COUNT(*) FROM transactions WHERE type='topup' AND status='pending'");
} else {
    $totalKeys   = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE created_by=?", [$uid]);
    $activeKeys  = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE created_by=? AND status='active'", [$uid]);
    $revKeys     = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE created_by=? AND status='revoked'", [$uid]);
    $expKeys     = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE created_by=? AND status='expired'", [$uid]);
    $revenue     = (float)(stat_count($pdo, "SELECT COALESCE(SUM(-amount),0) FROM transactions WHERE user_id=? AND type='purchase' AND status='completed'", [$uid]) ?: 0);
    $totalUsers = 0; $activeRes = 0; $pendTopups = 0;
    $balRow = $pdo->prepare('SELECT wallet_balance FROM users WHERE id=?'); $balRow->execute([$uid]);
    $myBal = (float)($balRow->fetch()['wallet_balance'] ?? 0);
    $user['wallet_balance'] = $myBal;
}

// ---- KEYS LIST ----
$keySql = "SELECT k.*, u.email AS creator FROM license_keys k LEFT JOIN users u ON u.id=k.created_by ";
$params = [];
if ($role === 'reseller') { $keySql .= "WHERE k.created_by=? "; $params[] = $uid; }
else { $keySql .= "WHERE 1=1 "; }
if ($q !== '') { $keySql .= "AND (k.key_string LIKE ? OR k.assigned_to LIKE ?) "; $params[] = "%$q%"; $params[] = "%$q%"; }
$keySql .= "ORDER BY k.id DESC LIMIT 150";
$ks = $pdo->prepare($keySql); $ks->execute($params); $keys = $ks->fetchAll();

// ---- USERS ----
$users = [];
if (in_array($role, ['owner','admin'], true)) {
    $us = $pdo->query("SELECT *, (SELECT COUNT(*) FROM license_keys WHERE created_by=users.id) AS key_count FROM users ORDER BY id DESC LIMIT 200");
    $users = $us->fetchAll();
}
// ---- TRANSACTIONS ----
if (in_array($role, ['owner','admin'], true)) {
    $txs = $pdo->query("SELECT t.*, u.email FROM transactions t LEFT JOIN users u ON u.id=t.user_id ORDER BY t.id DESC LIMIT 100")->fetchAll();
    $pendTx = array_values(array_filter($txs, fn($t)=>$t['type']==='topup' && $t['status']==='pending'));
} else {
    $stx = $pdo->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY id DESC LIMIT 100"); $stx->execute([$uid]); $txs = $stx->fetchAll();
    $pendTx = [];
}
// ---- LOGS ----
if (in_array($role, ['owner','admin'], true)) {
    $logs = $pdo->query("SELECT l.*, u.email FROM logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 80")->fetchAll();
} else {
    $sl = $pdo->prepare("SELECT * FROM logs WHERE user_id=? ORDER BY id DESC LIMIT 50"); $sl->execute([$uid]); $logs = $sl->fetchAll();
}
// ---- REFERRAL ----
$myRefCode = $user['referral_code'] ?? '';
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'your-app.onrender.com';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
$refLink = $proto.$host.$basePath.'/signup.php?ref='.urlencode($myRefCode);
$refUsers = [];
$rc = $pdo->prepare('SELECT id,name,email,created_at,status FROM users WHERE referred_by=? ORDER BY id DESC LIMIT 100'); $rc->execute([$uid]); $refUsers = $rc->fetchAll();
$commEarned = (float)(stat_count($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=? AND type='commission' AND status='completed'", [$uid]) ?: 0);

$appName = app_name();
$upiId = get_setting('upi_id','owner@upi');
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — <?= e($appName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="relative">
<canvas id="particles" class="fixed inset-0 w-full h-full pointer-events-none"></canvas>
<div class="glow-orb w-[380px] h-[380px] bg-purple-700 top-0 -left-24"></div>

<div class="relative z-10 flex min-h-screen">
  <!-- SIDEBAR -->
  <aside id="sidebar" class="sidebar glass !rounded-none md:!rounded-none w-64 shrink-0 p-4 flex flex-col gap-1 m-0 md:m-0 min-h-screen">
    <div class="flex items-center gap-2 px-2 py-3">
      <div class="w-9 h-9 rounded-xl btn-glow flex items-center justify-center">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <div><div class="font-extrabold text-sm leading-tight"><?= e($appName) ?></div><div class="text-[11px] text-slate-400 uppercase tracking-wider"><?= e($role) ?> • #<?= $uid ?></div></div>
    </div>
    <button class="sidebar-link <?= $tab==='overview'?'active':'' ?>" onclick="showTab('overview',this)">Overview</button>
    <button class="sidebar-link <?= $tab==='keys'?'active':'' ?>" onclick="showTab('keys',this)">Keys</button>
    <?php if ($role==='reseller'): ?>
      <button class="sidebar-link <?= $tab==='wallet'?'active':'' ?>" onclick="showTab('wallet',this)">Wallet</button>
    <?php else: ?>
      <button class="sidebar-link <?= $tab==='topups'?'active':'' ?>" onclick="showTab('topups',this)">Top-ups <?= $pendTopups? "($pendTopups)":'' ?></button>
      <button class="sidebar-link <?= $tab==='users'?'active':'' ?>" onclick="showTab('users',this)">Users</button>
    <?php endif; ?>
    <button class="sidebar-link <?= $tab==='referral'?'active':'' ?>" onclick="showTab('referral',this)">Referral</button>
    <button class="sidebar-link <?= $tab==='logs'?'active':'' ?>" onclick="showTab('logs',this)">Logs</button>
    <button class="sidebar-link <?= $tab==='api'?'active':'' ?>" onclick="showTab('api',this)">API Docs</button>
    <?php if ($role==='owner'): ?><button class="sidebar-link <?= $tab==='settings'?'active':'' ?>" onclick="showTab('settings',this)">Settings</button><?php endif; ?>
    <div class="mt-auto pt-4 border-t border-white/10">
      <div class="glass-soft p-3 text-xs mb-2"><div class="font-bold truncate"><?= e($user['name']) ?></div><div class="text-slate-400 truncate"><?= e($user['email']) ?></div>
      <?php if ($role==='reseller'): ?><div class="mt-1 text-emerald-300 font-bold">₹<?= number_format((float)($user['wallet_balance']??0),2) ?></div><?php endif; ?></div>
      <a href="logout.php" class="sidebar-link">Logout</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="flex-1 p-4 md:p-7 max-w-6xl w-full">
    <div class="flex items-center justify-between mb-5">
      <button onclick="toggleSidebar()" class="md:hidden glass-soft px-3 py-2 rounded-xl text-sm">Menu</button>
      <h1 class="text-xl md:text-2xl font-extrabold capitalize"><?= e($tab) ?> <span class="neon-text">Panel</span></h1>
      <a href="index.php" class="text-xs text-slate-400">Home</a>
    </div>

    <?php if ($flash): ?>
      <div class="mb-4 px-4 py-3 rounded-xl text-sm <?= ($flash['type']==='err')?'bg-red-500/15 border border-red-400/30 text-red-200':'bg-emerald-500/15 border border-emerald-400/30 text-emerald-200' ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <!-- OVERVIEW -->
    <div id="tab-overview" class="tab-pane <?= $tab==='overview'?'':'hidden' ?>">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="glass card-hover p-5"><div class="text-xs text-slate-400">Total Keys</div><div class="text-3xl font-extrabold"><?= $totalKeys ?></div></div>
        <div class="glass card-hover p-5"><div class="text-xs text-slate-400">Active Keys</div><div class="text-3xl font-extrabold text-emerald-300"><?= $activeKeys ?></div></div>
        <div class="glass card-hover p-5"><div class="text-xs text-slate-400"><?= $role==='reseller'?'My Spend':'Revenue' ?></div><div class="text-3xl font-extrabold neon-text">₹<?= number_format($revenue,0) ?></div></div>
        <?php if ($role!=='reseller'): ?>
          <div class="glass card-hover p-5"><div class="text-xs text-slate-400">Active Resellers</div><div class="text-3xl font-extrabold text-cyan-300"><?= $activeRes ?></div></div>
        <?php else: ?>
          <div class="glass card-hover p-5"><div class="text-xs text-slate-400">Wallet</div><div class="text-3xl font-extrabold text-cyan-300">₹<?= number_format((float)($user['wallet_balance']??0),0) ?></div></div>
        <?php endif; ?>
      </div>
      <div class="grid md:grid-cols-2 gap-3">
        <div class="glass p-5"><h3 class="font-bold mb-2">Key Health</h3>
          <div class="text-sm flex flex-col gap-2">
            <div class="flex justify-between"><span>Revoked</span><span class="badge badge-revoked"><?= $revKeys ?></span></div>
            <div class="flex justify-between"><span>Expired</span><span class="badge badge-expired"><?= $expKeys ?></span></div>
            <?php if ($role!=='reseller'): ?><div class="flex justify-between"><span>Pending Top-ups</span><span class="badge badge-pending"><?= $pendTopups ?></span></div><?php endif; ?>
          </div>
          <button onclick="showTab('keys',document.querySelectorAll('.sidebar-link')[1])" class="btn-glow mt-4 px-5 py-2.5 rounded-xl text-sm font-bold">Generate Key</button>
        </div>
        <div class="glass p-5"><h3 class="font-bold mb-2">Referral Earning</h3>
          <div class="text-2xl font-extrabold text-emerald-300">₹<?= number_format($commEarned,2) ?></div>
          <div class="text-xs text-slate-400 mb-2"><?= count($refUsers) ?> referred users • <?= e(get_setting('referral_percent','10')) ?>% commission</div>
          <div class="flex gap-2"><input readonly value="<?= e($refLink) ?>" class="input-glass flex-1 px-3 py-2 text-xs"><button onclick="copyText('<?= e($refLink) ?>','Referral link copied!')" class="glass-soft px-3 py-2 rounded-xl text-xs font-bold">Copy</button></div>
        </div>
      </div>
    </div>

    <!-- KEYS -->
    <div id="tab-keys" class="tab-pane <?= $tab==='keys'?'':'hidden' ?>">
      <div class="glass p-5 mb-4">
        <h3 class="font-bold mb-3">Generate Key — <?= $role==='reseller' ? 'paid from wallet ₹'.e(get_setting('price_1day','49')).'/'.e(get_setting('price_7days','149')).'/'.e(get_setting('price_30days','299')).'/'.e(get_setting('price_lifetime','999')) : 'FREE ('.$role.')' ?></h3>
        <form method="POST" action="actions.php" class="grid md:grid-cols-5 gap-2">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="generate_key">
          <select name="duration_type" class="input-glass px-3 py-2.5 text-sm">
            <option value="1day">1 Day — ₹<?= e(get_setting('price_1day','49')) ?></option>
            <option value="7days" selected>7 Days — ₹<?= e(get_setting('price_7days','149')) ?></option>
            <option value="30days">30 Days — ₹<?= e(get_setting('price_30days','299')) ?></option>
            <option value="lifetime">Lifetime — ₹<?= e(get_setting('price_lifetime','999')) ?></option>
          </select>
          <input name="device_limit" type="number" min="1" max="20" value="1" class="input-glass px-3 py-2.5 text-sm" title="Device limit">
          <input name="assigned_to" placeholder="Assign to (email/opt)" class="input-glass px-3 py-2.5 text-sm">
          <input name="qty" type="number" min="1" max="20" value="1" class="input-glass px-3 py-2.5 text-sm" title="Qty">
          <button class="btn-glow rounded-xl py-2.5 font-bold text-sm md:col-span-5">Generate</button>
        </form>
      </div>
      <div class="glass p-5">
        <form method="GET" class="flex gap-2 mb-3">
          <input type="hidden" name="tab" value="keys">
          <input name="q" value="<?= e($q) ?>" placeholder="Search key / email..." class="input-glass flex-1 px-3 py-2 text-sm">
          <button class="glass-soft px-4 py-2 rounded-xl text-sm">Search</button>
        </form>
        <div class="overflow-x-auto scroll-thin"><table class="w-full text-sm table-glass min-w-[760px]">
          <tr><th>Key</th><th>Plan</th><th>Expiry</th><th>Devices</th><th>Status</th><th>Action</th></tr>
          <?php foreach ($keys as $k): $dev = $k['hwid'] ? (json_decode($k['hwid'],true)?:[$k['hwid']]) : []; $dc = is_array($dev)?count($dev):0; ?>
          <tr>
            <td><code class="text-cyan-200 text-xs"><?= e($k['key_string']) ?></code><div class="text-[11px] text-slate-500"><?= e($k['assigned_to']??'') ?> • by <?= e($k['creator']??('#'.$k['created_by'])) ?></div></td>
            <td><?= e(duration_label($k['duration_type'])) ?></td>
            <td class="text-xs"><?= e($k['expires_at']??'Never') ?></td>
            <td><?= $dc ?>/<?= (int)$k['device_limit'] ?></td>
            <td><span class="badge badge-<?= e($k['status']) ?>"><?= e($k['status']) ?></span></td>
            <td class="flex gap-1 flex-wrap">
              <button onclick="copyText('<?= e($k['key_string']) ?>','Key copied!')" class="glass-soft px-2 py-1 rounded-lg text-xs">Copy</button>
              <?php if ($k['status']!=='revoked'): ?>
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="revoke_key"><input type="hidden" name="key_id" value="<?= (int)$k['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-red-300">Revoke</button></form>
              <?php else: if ($role!=='reseller'): ?>
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="activate_key"><input type="hidden" name="key_id" value="<?= (int)$k['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-emerald-300">Activate</button></form>
              <?php endif; endif; ?>
              <?php if ($role==='owner'): ?>
              <form method="POST" action="actions.php" onsubmit="return confirm('Delete?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_key"><input type="hidden" name="key_id" value="<?= (int)$k['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs">Del</button></form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; if (!$keys): ?><tr><td colspan="6" class="text-center text-slate-500 py-6">No keys yet. Generate one above.</td></tr><?php endif; ?>
        </table></div>
      </div>
    </div>

    <?php if ($role==='reseller'): ?>
    <!-- WALLET -->
    <div id="tab-wallet" class="tab-pane <?= $tab==='wallet'?'':'hidden' ?>">
      <div class="grid md:grid-cols-2 gap-3">
        <div class="glass p-5">
          <div class="text-xs text-slate-400">Wallet Balance</div><div class="text-4xl font-extrabold text-emerald-300">₹<?= number_format((float)($user['wallet_balance']??0),2) ?></div>
          <div class="text-xs text-slate-400 mt-2">Pay on UPI <b class="text-white"><?= e($upiId) ?></b> then submit UTR below.</div>
          <form method="POST" action="actions.php" class="flex flex-col gap-2 mt-3">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="topup_request">
            <input name="amount" type="number" min="10" step="1" required placeholder="Amount ₹" class="input-glass px-3 py-2.5 text-sm">
            <input name="reference" required placeholder="UPI UTR / Reference" class="input-glass px-3 py-2.5 text-sm">
            <button class="btn-glow rounded-xl py-2.5 font-bold text-sm">Request Top-up</button>
          </form>
        </div>
        <div class="glass p-5"><h3 class="font-bold mb-2">Transactions</h3>
          <div class="overflow-x-auto scroll-thin max-h-[380px] overflow-y-auto"><table class="w-full text-xs table-glass">
            <tr><th>Type</th><th>Amt</th><th>Ref</th><th>Status</th><th>Time</th></tr>
            <?php foreach ($txs as $t): ?><tr><td><?= e($t['type']) ?></td><td class="<?= ((float)$t['amount']<0)?'text-red-300':'text-emerald-300' ?>">₹<?= e($t['amount']) ?></td><td class="max-w-[140px] truncate"><?= e($t['reference']??'') ?></td><td><span class="badge badge-<?= e($t['status']==='completed'?'active':($t['status']==='pending'?'pending':'revoked')) ?>"><?= e($t['status']) ?></span></td><td><?= e($t['created_at']) ?></td></tr><?php endforeach; ?>
          </table></div>
        </div>
      </div>
    </div>
    <?php else: ?>
    <!-- TOPUPS (admin) -->
    <div id="tab-topups" class="tab-pane <?= $tab==='topups'?'':'hidden' ?>">
      <div class="glass p-5 mb-3"><h3 class="font-bold">Pending Top-ups (<?= count($pendTx) ?>)</h3>
        <?php if (!$pendTx): ?><div class="text-sm text-slate-500 mt-2">No pending requests.</div><?php endif; ?>
        <?php foreach ($pendTx as $t): ?>
          <div class="glass-soft p-3 mt-2 flex flex-col md:flex-row md:items-center gap-2 justify-between">
            <div class="text-sm">#<?= (int)$t['id'] ?> • <?= e($t['email']) ?> • <b class="text-emerald-300">₹<?= e($t['amount']) ?></b> • UTR: <code><?= e($t['reference']) ?></code> • <?= e($t['created_at']) ?></div>
            <div class="flex gap-2">
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="topup_approve"><input type="hidden" name="tx_id" value="<?= (int)$t['id'] ?>"><button class="btn-glow px-4 py-1.5 rounded-xl text-xs font-bold">Approve</button></form>
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="topup_reject"><input type="hidden" name="tx_id" value="<?= (int)$t['id'] ?>"><button class="glass-soft px-4 py-1.5 rounded-xl text-xs">Reject</button></form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="glass p-5"><h3 class="font-bold mb-2">All Transactions</h3>
        <div class="overflow-x-auto scroll-thin"><table class="w-full text-xs table-glass min-w-[700px]"><tr><th>ID</th><th>User</th><th>Type</th><th>Amt</th><th>Ref</th><th>Status</th><th>Time</th></tr>
        <?php foreach ($txs as $t): ?><tr><td>#<?= (int)$t['id'] ?></td><td><?= e($t['email']??$t['user_id']) ?></td><td><?= e($t['type']) ?></td><td>₹<?= e($t['amount']) ?></td><td class="max-w-[160px] truncate"><?= e($t['reference']??'') ?></td><td><?= e($t['status']) ?></td><td><?= e($t['created_at']) ?></td></tr><?php endforeach; ?>
        </table></div>
      </div>
    </div>
    <!-- USERS -->
    <div id="tab-users" class="tab-pane <?= $tab==='users'?'':'hidden' ?>">
      <div class="glass p-5"><h3 class="font-bold mb-2">Users (<?= count($users) ?>)</h3>
        <div class="overflow-x-auto scroll-thin"><table class="w-full text-xs table-glass min-w-[820px]"><tr><th>ID</th><th>Name/Email</th><th>Role</th><th>Balance</th><th>Keys</th><th>Status</th><th>Action</th></tr>
        <?php foreach ($users as $x): ?>
          <tr><td>#<?= (int)$x['id'] ?></td>
          <td><b><?= e($x['name']) ?></b><div class="text-slate-400"><?= e($x['email']) ?></div><div class="text-[10px] text-slate-500">ref:<?= e($x['referral_code']??'') ?> by:<?= e($x['referred_by']??'-') ?></div></td>
          <td><span class="badge"><?= e($x['role']) ?></span></td><td>₹<?= e($x['wallet_balance']) ?></td><td><?= (int)$x['key_count'] ?></td>
          <td><span class="badge badge-<?= $x['status']==='active'?'active':'expired' ?>"><?= e($x['status']) ?></span></td>
          <td class="flex gap-1 flex-wrap">
            <?php if ((int)$x['id']!==$uid): ?>
              <?php if ($x['status']==='active'): ?>
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="ban_user"><input type="hidden" name="user_id" value="<?= (int)$x['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-red-300">Ban</button></form>
              <?php else: ?>
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="unban_user"><input type="hidden" name="user_id" value="<?= (int)$x['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-emerald-300">Unban</button></form>
              <?php endif; ?>
              <?php if ($role==='owner' && $x['role']!=='owner'): ?>
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="set_role"><input type="hidden" name="user_id" value="<?= (int)$x['id'] ?>"><input type="hidden" name="new_role" value="<?= $x['role']==='admin'?'reseller':'admin' ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs">→<?= $x['role']==='admin'?'reseller':'admin' ?></button></form>
              <?php endif; ?>
            <?php else: ?><span class="text-[11px] text-slate-500">you</span><?php endif; ?>
          </td></tr>
        <?php endforeach; ?>
        </table></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- REFERRAL -->
    <div id="tab-referral" class="tab-pane <?= $tab==='referral'?'':'hidden' ?>">
      <div class="glass p-5 mb-3">
        <h3 class="font-bold">My Referral</h3>
        <div class="text-sm text-slate-300 mt-1">Code: <b class="text-cyan-300"><?= e($myRefCode) ?></b> • Commission: <b><?= e(get_setting('referral_percent','10')) ?>%</b> • Earned: <b class="text-emerald-300">₹<?= number_format($commEarned,2) ?></b></div>
        <div class="flex gap-2 mt-2"><input readonly value="<?= e($refLink) ?>" class="input-glass flex-1 px-3 py-2 text-xs"><button onclick="copyText('<?= e($refLink) ?>','Referral link copied!')" class="btn-glow px-4 py-2 rounded-xl text-xs font-bold">Copy Link</button></div>
      </div>
      <div class="glass p-5"><h3 class="font-bold mb-2">My Referrals (<?= count($refUsers) ?>)</h3>
        <div class="overflow-x-auto"><table class="w-full text-sm table-glass"><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th></tr>
        <?php foreach ($refUsers as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['status']) ?></td><td class="text-xs"><?= e($r['created_at']) ?></td></tr><?php endforeach; ?>
        <?php if (!$refUsers): ?><tr><td colspan="4" class="text-center text-slate-500 py-4">Link share karo, har recharge pe commission pao.</td></tr><?php endif; ?>
        </table></div>
      </div>
    </div>

    <!-- LOGS -->
    <div id="tab-logs" class="tab-pane <?= $tab==='logs'?'':'hidden' ?>">
      <div class="glass p-5"><h3 class="font-bold mb-2">Activity Logs</h3>
        <div class="overflow-x-auto scroll-thin"><table class="w-full text-xs table-glass min-w-[700px]"><tr><th>ID</th><th>User</th><th>Action</th><th>KeyID</th><th>Details</th><th>Time</th></tr>
        <?php foreach ($logs as $l): ?><tr><td>#<?= (int)$l['id'] ?></td><td><?= e($l['email']??$l['user_id']??'-') ?></td><td><code><?= e($l['action']) ?></code></td><td><?= e($l['target_key_id']??'-') ?></td><td class="max-w-[220px] truncate"><?= e($l['details']??'') ?></td><td><?= e($l['created_at']) ?></td></tr><?php endforeach; ?>
        </table></div>
      </div>
    </div>

    <!-- API -->
    <div id="tab-api" class="tab-pane <?= $tab==='api'?'':'hidden' ?>">
      <div class="glass p-5">
        <h3 class="font-bold mb-2">Validate API — <code class="text-cyan-300">POST /api/validate</code></h3>
        <pre class="glass-soft p-4 text-xs overflow-x-auto">curl -X POST <?= e($proto.$host.$basePath) ?>/api/validate \
  -H "Content-Type: application/json" \
  -d '{"key":"XXXX-XXXX-XXXX-XXXX","hwid":"DEVICE_ID"}'

# success:
{"valid":true,"duration_type":"30days","expires_at":"2026-10-12 00:00:00","device_limit":1,"devices":1}
# fail:
{"valid":false,"reason":"expired | revoked | hwid_limit | invalid_key"}</pre>
        <div class="text-xs text-slate-400 mt-2">HWID limit auto-enforce hota hai. Nayi device limit se zyada aayi to <code>hwid_limit</code> milega.</div>
      </div>
    </div>

    <?php if ($role==='owner'): ?>
    <!-- SETTINGS -->
    <div id="tab-settings" class="tab-pane <?= $tab==='settings'?'':'hidden' ?>">
      <div class="glass p-5">
        <h3 class="font-bold mb-3">Global Pricing + Settings</h3>
        <form method="POST" action="actions.php" class="grid md:grid-cols-2 gap-3">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update_pricing">
          <div><label class="text-xs">App Name</label><input name="app_name" value="<?= e(get_setting('app_name','NEXUS License Panel')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">UPI ID (for topup)</label><input name="upi_id" value="<?= e($upiId) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Price 1 Day ₹</label><input name="price_1day" value="<?= e(get_setting('price_1day','49')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Price 7 Days ₹</label><input name="price_7days" value="<?= e(get_setting('price_7days','149')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Price 30 Days ₹</label><input name="price_30days" value="<?= e(get_setting('price_30days','299')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Price Lifetime ₹</label><input name="price_lifetime" value="<?= e(get_setting('price_lifetime','999')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div class="md:col-span-2"><label class="text-xs">Referral % (0-50)</label><input name="referral_percent" value="<?= e(get_setting('referral_percent','10')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <button class="btn-glow md:col-span-2 rounded-xl py-2.5 font-bold text-sm">Save Settings</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>
<script src="assets/js/app.js"></script>
</body></html>
