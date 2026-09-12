<?php
require_once __DIR__ . '/../src/auth.php';
$user = require_login();
$pdo = db();
$role = $user['role'];
$uid = (int)$user['id'];
$fresh = current_user(); if ($fresh) $user = $fresh;
$tab = $_GET['tab'] ?? 'overview';
$flash = flash_get();
$q = trim($_GET['q'] ?? '');
$modFilter = trim($_GET['mod_id'] ?? '');

function stat_count(PDO $pdo, string $sql, array $p = []) { $s=$pdo->prepare($sql); $s->execute($p); return $s->fetchColumn(); }

// ---- STATS (mods-aware) ----
if (in_array($role, ['owner','admin'], true)) {
    $totalKeys   = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys");
    $activeKeys  = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE status IN ('active','available','sold')");
    $soldKeys    = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE status='sold' OR sold_to IS NOT NULL");
    $revKeys     = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE status IN ('revoked','blocked')");
    $expKeys     = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE status='expired'");
    $availKeys   = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE status='available' AND sold_to IS NULL");
    $totalUsers  = (int)stat_count($pdo, "SELECT COUNT(*) FROM users");
    $activeRes   = (int)stat_count($pdo, "SELECT COUNT(*) FROM users WHERE role='reseller' AND status='active'");
    $totalMods   = (int)stat_count($pdo, "SELECT COUNT(*) FROM mods");
    $activeMods  = (int)stat_count($pdo, "SELECT COUNT(*) FROM mods WHERE status='active'");
    $revenue     = (float)(stat_count($pdo, "SELECT COALESCE(SUM(ABS(amount)),0) FROM transactions WHERE type='purchase' AND status IN ('completed','approved')") ?: 0);
    $pendTopups  = (int)stat_count($pdo, "SELECT COUNT(*) FROM transactions WHERE type='topup' AND status='pending'");
    $pendOrders  = (int)stat_count($pdo, "SELECT COUNT(*) FROM transactions WHERE type='purchase' AND status='pending'");
} else {
    $totalKeys   = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE created_by=? OR sold_to=?", [$uid, $uid]);
    $activeKeys  = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE (created_by=? OR sold_to=?) AND status IN ('active','sold','available')", [$uid, $uid]);
    $soldKeys    = (int)stat_count($pdo, "SELECT COUNT(*) FROM license_keys WHERE sold_to=?", [$uid]);
    $revKeys = 0; $expKeys = 0; $availKeys = 0; $totalUsers = 0; $activeRes = 0;
    $totalMods = (int)stat_count($pdo, "SELECT COUNT(*) FROM mods WHERE status='active'");
    $activeMods = $totalMods;
    $revenue     = (float)(stat_count($pdo, "SELECT COALESCE(SUM(ABS(amount)),0) FROM transactions WHERE user_id=? AND type='purchase' AND status IN ('completed','approved')", [$uid]) ?: 0);
    $pendTopups = 0; $pendOrders = (int)stat_count($pdo, "SELECT COUNT(*) FROM transactions WHERE user_id=? AND type='purchase' AND status='pending'", [$uid]);
    $balRow = $pdo->prepare('SELECT wallet_balance FROM users WHERE id=?'); $balRow->execute([$uid]);
    $myBal = (float)($balRow->fetch()['wallet_balance'] ?? 0);
    $user['wallet_balance'] = $myBal;
}
[$chartLabels, $chartSales, $chartRev] = chart_last7($pdo, in_array($role, ['owner','admin'], true) ? null : $uid);

// ---- KEYS LIST (with mod join + search) ----
$keySql = "SELECT k.*, u.email AS creator, m.name AS mod_name FROM license_keys k LEFT JOIN users u ON u.id=k.created_by LEFT JOIN mods m ON m.id=k.mod_id ";
$params = [];
if ($role === 'reseller') { $keySql .= "WHERE (k.created_by=? OR k.sold_to=?) "; $params[] = $uid; $params[] = $uid; }
else { $keySql .= "WHERE 1=1 "; }
if ($q !== '') { $keySql .= "AND (k.key_string LIKE ? OR k.assigned_to LIKE ? OR m.name LIKE ?) "; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($modFilter !== '' && ctype_digit($modFilter)) { $keySql .= "AND k.mod_id=? "; $params[] = (int)$modFilter; }
$keySql .= "ORDER BY k.id DESC LIMIT 150";
$ks = $pdo->prepare($keySql); $ks->execute($params); $keys = $ks->fetchAll();

// ---- MODS / PLANS / APKS ----
$mods = $pdo->query("SELECT *, (SELECT COUNT(*) FROM mod_plans WHERE mod_id=mods.id) AS plan_count, (SELECT COUNT(*) FROM license_keys WHERE mod_id=mods.id AND (status='sold' OR sold_to IS NOT NULL)) AS sold_count FROM mods ORDER BY id DESC")->fetchAll();
$activeModsList = array_values(array_filter($mods, fn($m) => ($m['status'] ?? '') === 'active'));
$plans = $pdo->query("SELECT p.*, m.name AS mod_name FROM mod_plans p LEFT JOIN mods m ON m.id=p.mod_id ORDER BY m.name, p.price")->fetchAll();
$activePlans = array_values(array_filter($plans, fn($p) => ($p['status'] ?? '') === 'active'));
if ($modFilter !== '' && ctype_digit($modFilter)) {
    $activePlans = array_values(array_filter($activePlans, fn($p) => (int)$p['mod_id'] === (int)$modFilter));
}
$apks = $pdo->query("SELECT a.*, m.name AS mod_name FROM mod_apks a LEFT JOIN mods m ON m.id=a.mod_id ORDER BY a.id DESC LIMIT 100")->fetchAll();

// my purchased mod keys (store vault)
$myModKeys = [];
$st = $pdo->prepare("SELECT k.*, m.name AS mod_name FROM license_keys k LEFT JOIN mods m ON m.id=k.mod_id WHERE k.sold_to=? ORDER BY k.sold_at DESC LIMIT 100");
$st->execute([$uid]); $myModKeys = $st->fetchAll();

// my downloads (purchased mods with apk)
$myDownloads = [];
try {
    $st = $pdo->prepare("SELECT lk.*, m.name AS mod_name, m.description, ma.id AS apk_id, ma.file_name, ma.file_path FROM license_keys lk LEFT JOIN mods m ON m.id=lk.mod_id LEFT JOIN mod_apks ma ON ma.mod_id=m.id WHERE lk.sold_to=? AND lk.mod_id IS NOT NULL GROUP BY lk.mod_id ORDER BY lk.sold_at DESC");
    $st->execute([$uid]); $myDownloads = $st->fetchAll();
} catch (Throwable $ex) {}

// ---- USERS ----
$users = [];
if (in_array($role, ['owner','admin'], true)) {
    $us = $pdo->query("SELECT *, (SELECT COUNT(*) FROM license_keys WHERE created_by=users.id OR sold_to=users.id) AS key_count FROM users ORDER BY id DESC LIMIT 200");
    $users = $us->fetchAll();
}
// ---- TRANSACTIONS ----
if (in_array($role, ['owner','admin'], true)) {
    $txs = $pdo->query("SELECT t.*, u.email FROM transactions t LEFT JOIN users u ON u.id=t.user_id ORDER BY t.id DESC LIMIT 100")->fetchAll();
    $pendTx = array_values(array_filter($txs, fn($t)=>$t['type']==='topup' && $t['status']==='pending'));
    $pendOrderTx = $pdo->query("SELECT t.*, u.email, u.name, p.plan_name, p.duration, p.duration_type, m.name AS mod_name FROM transactions t LEFT JOIN users u ON u.id=t.user_id LEFT JOIN mod_plans p ON p.id=t.plan_id LEFT JOIN mods m ON m.id=p.mod_id WHERE t.type='purchase' AND t.status='pending' ORDER BY t.id DESC LIMIT 100")->fetchAll();
} else {
    $stx = $pdo->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY id DESC LIMIT 100"); $stx->execute([$uid]); $txs = $stx->fetchAll();
    $pendTx = [];
    $sto = $pdo->prepare("SELECT t.*, p.plan_name, p.duration, p.duration_type, m.name AS mod_name FROM transactions t LEFT JOIN mod_plans p ON p.id=t.plan_id LEFT JOIN mods m ON m.id=p.mod_id WHERE t.user_id=? AND t.type='purchase' ORDER BY t.id DESC LIMIT 50");
    $sto->execute([$uid]); $myOrders = $sto->fetchAll();
    $pendOrderTx = [];
}
if (!isset($myOrders)) {
    $sto = $pdo->prepare("SELECT t.*, p.plan_name, p.duration, p.duration_type, m.name AS mod_name FROM transactions t LEFT JOIN mod_plans p ON p.id=t.plan_id LEFT JOIN mods m ON m.id=p.mod_id WHERE t.user_id=? AND t.type='purchase' ORDER BY t.id DESC LIMIT 50");
    $sto->execute([$uid]); $myOrders = $sto->fetchAll();
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
$apiBase = $proto.$host.$basePath;
$refUsers = [];
$rc = $pdo->prepare('SELECT id,name,email,created_at,status FROM users WHERE referred_by=? ORDER BY id DESC LIMIT 100'); $rc->execute([$uid]); $refUsers = $rc->fetchAll();
$commEarned = (float)(stat_count($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=? AND type='commission' AND status='completed'", [$uid]) ?: 0);
// tokens (admin)
$tokens = [];
$tokStats = ['total' => 0, 'active' => 0, 'used' => 0];
if (in_array($role, ['owner','admin'], true)) {
    $tokens = $pdo->query("SELECT t.*, u.email AS creator FROM referral_tokens t LEFT JOIN users u ON u.id=t.created_by ORDER BY t.id DESC LIMIT 100")->fetchAll();
    $tokStats['total'] = count($tokens);
    foreach ($tokens as $tk) {
        $exp = strtotime($tk['expires_at'] ?? '');
        if (($tk['status'] ?? '') === 'active' && $exp && $exp > time()) $tokStats['active']++;
        else $tokStats['used']++;
    }
}

$appName = app_name();
$tagline = site_tagline();
$upiId = get_setting('upi_id','owner@upi');
$adminApiKey = get_setting('admin_api_key','');
if ($adminApiKey === '' && $role === 'owner') $adminApiKey = ensure_admin_api_key();
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — <?= e($appName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="relative">
<canvas id="particles" class="fixed inset-0 w-full h-full pointer-events-none"></canvas>
<div class="glow-orb w-[380px] h-[380px] bg-purple-700 top-0 -left-24"></div>

<div class="relative z-10 flex min-h-screen">
  <!-- SIDEBAR (same UI) -->
  <aside id="sidebar" class="sidebar glass !rounded-none md:!rounded-none w-64 shrink-0 p-4 flex flex-col gap-1 m-0 md:m-0 min-h-screen">
    <div class="flex items-center gap-2 px-2 py-3">
      <div class="w-9 h-9 rounded-xl btn-glow flex items-center justify-center">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <div><div class="font-extrabold text-sm leading-tight"><?= e($appName) ?></div><div class="text-[11px] text-slate-400 uppercase tracking-wider"><?= e($role) ?> • #<?= $uid ?></div></div>
    </div>
    <button class="sidebar-link <?= $tab==='overview'?'active':'' ?>" onclick="showTab('overview',this)">Overview</button>
    <button class="sidebar-link <?= $tab==='store'?'active':'' ?>" onclick="showTab('store',this)">Store</button>
    <button class="sidebar-link <?= $tab==='keys'?'active':'' ?>" onclick="showTab('keys',this)">Keys</button>
    <button class="sidebar-link <?= $tab==='orders'?'active':'' ?>" onclick="showTab('orders',this)">Orders <?= (($role!=='reseller'?$pendOrders:$pendOrders)>0)?"($pendOrders)":'' ?></button>
    <?php if ($role==='reseller'): ?>
      <button class="sidebar-link <?= $tab==='wallet'?'active':'' ?>" onclick="showTab('wallet',this)">Wallet</button>
      <button class="sidebar-link <?= $tab==='downloads'?'active':'' ?>" onclick="showTab('downloads',this)">Downloads</button>
    <?php else: ?>
      <button class="sidebar-link <?= $tab==='mods'?'active':'' ?>" onclick="showTab('mods',this)">Mods (<?= $totalMods ?>)</button>
      <button class="sidebar-link <?= $tab==='plans'?'active':'' ?>" onclick="showTab('plans',this)">Plans</button>
      <button class="sidebar-link <?= $tab==='downloads'?'active':'' ?>" onclick="showTab('downloads',this)">APKs</button>
      <button class="sidebar-link <?= $tab==='topups'?'active':'' ?>" onclick="showTab('topups',this)">Top-ups <?= $pendTopups? "($pendTopups)":'' ?></button>
      <button class="sidebar-link <?= $tab==='users'?'active':'' ?>" onclick="showTab('users',this)">Users</button>
      <button class="sidebar-link <?= $tab==='tokens'?'active':'' ?>" onclick="showTab('tokens',this)">Tokens</button>
    <?php endif; ?>
    <button class="sidebar-link <?= $tab==='referral'?'active':'' ?>" onclick="showTab('referral',this)">Referral</button>
    <button class="sidebar-link <?= $tab==='logs'?'active':'' ?>" onclick="showTab('logs',this)">Logs</button>
    <button class="sidebar-link <?= $tab==='api'?'active':'' ?>" onclick="showTab('api',this)">API Docs</button>
    <button class="sidebar-link <?= $tab==='profile'?'active':'' ?>" onclick="showTab('profile',this)">Profile</button>
    <?php if ($role==='owner'): ?><button class="sidebar-link <?= $tab==='settings'?'active':'' ?>" onclick="showTab('settings',this)">Settings</button><?php endif; ?>
    <div class="mt-auto pt-4 border-t border-white/10">
      <div class="glass-soft p-3 text-xs mb-2"><div class="font-bold truncate"><?= e($user['name']) ?></div><div class="text-slate-400 truncate"><?= e($user['email']) ?></div>
      <?php if ($role==='reseller'): ?><div class="mt-1 text-emerald-300 font-bold">₹<?= number_format((float)($user['wallet_balance']??0),2) ?></div><?php endif; ?></div>
      <a href="logout.php" class="sidebar-link">Logout</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="flex-1 p-4 md:p-7 max-w-6xl w-full">
    <div class="flex flex-col md:flex-row md:items-center gap-3 justify-between mb-5">
      <div class="flex items-center gap-3">
        <button onclick="toggleSidebar()" class="md:hidden glass-soft px-3 py-2 rounded-xl text-sm">Menu</button>
        <h1 class="text-xl md:text-2xl font-extrabold capitalize"><?= e($tab) ?> <span class="neon-text">Panel</span></h1>
      </div>
      <div class="flex items-center gap-2">
        <form method="GET" class="flex gap-2">
          <input type="hidden" name="tab" value="<?= e($tab) ?>">
          <input name="q" value="<?= e($q) ?>" placeholder="Search mods, keys, users... (Ctrl+K)" class="input-glass px-3 py-2 text-xs w-56 md:w-72">
          <button class="glass-soft px-3 py-2 rounded-xl text-xs">Search</button>
        </form>
        <a href="index.php" class="text-xs text-slate-400">Home</a>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="mb-4 px-4 py-3 rounded-xl text-sm <?= ($flash['type']==='err')?'bg-red-500/15 border border-red-400/30 text-red-200':'bg-emerald-500/15 border border-emerald-400/30 text-emerald-200' ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <!-- OVERVIEW -->
    <div id="tab-overview" class="tab-pane <?= $tab==='overview'?'':'hidden' ?>">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="glass card-hover p-5"><div class="text-xs text-slate-400">Total Mods</div><div class="text-3xl font-extrabold"><?= $totalMods ?></div><div class="text-[11px] text-slate-500"><?= $activeMods ?> active</div></div>
        <div class="glass card-hover p-5"><div class="text-xs text-slate-400">Total Keys</div><div class="text-3xl font-extrabold"><?= $totalKeys ?></div><div class="text-[11px] text-slate-500"><?= $soldKeys ?> sold • <?= $availKeys ?> avail</div></div>
        <div class="glass card-hover p-5"><div class="text-xs text-slate-400"><?= $role==='reseller'?'My Spend':'Revenue' ?></div><div class="text-3xl font-extrabold neon-text">₹<?= number_format($revenue,0) ?></div></div>
        <?php if ($role!=='reseller'): ?>
          <div class="glass card-hover p-5"><div class="text-xs text-slate-400">Active Resellers</div><div class="text-3xl font-extrabold text-cyan-300"><?= $activeRes ?></div></div>
        <?php else: ?>
          <div class="glass card-hover p-5"><div class="text-xs text-slate-400">Wallet</div><div class="text-3xl font-extrabold text-cyan-300">₹<?= number_format((float)($user['wallet_balance']??0),0) ?></div></div>
        <?php endif; ?>
      </div>
      <div class="glass p-5 mb-4">
        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
          <h3 class="font-bold">Sales & Revenue — last 7 days</h3>
          <div class="text-[11px] text-slate-400">Keys sold vs Revenue (₹)</div>
        </div>
        <div class="h-[240px]"><canvas id="analyticsChart"></canvas></div>
      </div>
      <div class="grid md:grid-cols-2 gap-3">
        <div class="glass p-5"><h3 class="font-bold mb-2">Key Health</h3>
          <div class="text-sm flex flex-col gap-2">
            <div class="flex justify-between"><span>Sold</span><span class="badge badge-active"><?= $soldKeys ?></span></div>
            <div class="flex justify-between"><span>Available</span><span class="badge"><?= $availKeys ?></span></div>
            <div class="flex justify-between"><span>Blocked/Revoked</span><span class="badge badge-revoked"><?= $revKeys ?></span></div>
            <div class="flex justify-between"><span>Expired</span><span class="badge badge-expired"><?= $expKeys ?></span></div>
            <div class="flex justify-between"><span>Pending Orders</span><span class="badge badge-pending"><?= $pendOrders ?></span></div>
            <?php if ($role!=='reseller'): ?><div class="flex justify-between"><span>Pending Top-ups</span><span class="badge badge-pending"><?= $pendTopups ?></span></div><?php endif; ?>
          </div>
          <div class="flex gap-2 mt-4">
            <button onclick="showTab('store',document.querySelectorAll('.sidebar-link')[1])" class="btn-glow px-5 py-2.5 rounded-xl text-sm font-bold">Open Store</button>
            <button onclick="showTab('keys',this)" class="glass-soft px-5 py-2.5 rounded-xl text-sm font-bold">Keys</button>
          </div>
        </div>
        <div class="glass p-5"><h3 class="font-bold mb-2">Referral Earning</h3>
          <div class="text-2xl font-extrabold text-emerald-300">₹<?= number_format($commEarned,2) ?></div>
          <div class="text-xs text-slate-400 mb-2"><?= count($refUsers) ?> referred users • <?= e(get_setting('referral_percent','10')) ?>% commission</div>
          <div class="flex gap-2"><input readonly value="<?= e($refLink) ?>" class="input-glass flex-1 px-3 py-2 text-xs"><button onclick="copyText('<?= e($refLink) ?>','Referral link copied!')" class="glass-soft px-3 py-2 rounded-xl text-xs font-bold">Copy</button></div>
        </div>
      </div>
    </div>

    <!-- STORE (MultiPanelX plans + available keys) -->
    <div id="tab-store" class="tab-pane <?= $tab==='store'?'':'hidden' ?>">
      <form method="GET" class="glass p-4 mb-4 flex flex-col md:flex-row gap-2 md:items-center">
        <input type="hidden" name="tab" value="store">
        <select name="mod_id" onchange="this.form.submit()" class="input-glass px-3 py-2 text-sm">
          <option value="">All Mods (<?= count($activePlans) ?> plans)</option>
          <?php foreach ($activeModsList as $m): ?><option value="<?= (int)$m['id'] ?>" <?= $modFilter==(string)$m['id']?'selected':'' ?>><?= e($m['name']) ?></option><?php endforeach; ?>
        </select>
        <?php if ($modFilter!==''): ?><a href="dashboard.php?tab=store" class="text-xs text-slate-400">Clear</a><?php endif; ?>
        <div class="text-xs text-slate-400 md:ml-auto">Pay via UPI QR → submit UTR → admin approves → key auto-assigned.</div>
      </form>
      <?php if (!$activePlans): ?><div class="glass p-8 text-center text-slate-400 text-sm">No plans available. <?= $role!=='reseller' ? 'Mods → add mod + Plans → add plan.' : 'Ask admin to add plans.' ?></div>
      <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
        <?php foreach ($activePlans as $p): ?>
        <div class="glass card-hover tilt p-5 flex flex-col">
          <div class="flex items-start justify-between mb-2">
            <div><div class="font-bold"><?= e($p['mod_name'] ?? 'Mod') ?></div><div class="text-[11px] text-purple-200 font-bold"><?= e($p['plan_name']) ?></div></div>
            <span class="badge"><?= e(plan_expiry_label(isset($p['duration'])?(int)$p['duration']:null, $p['duration_type'] ?? '')) ?></span>
          </div>
          <?php if (!empty($p['features'])): ?><div class="text-xs text-slate-400 mb-3 whitespace-pre-line"><?= e(mb_strimwidth($p['features'],0,160,'…')) ?></div><?php endif; ?>
          <div class="mt-auto flex items-center justify-between pt-3 border-t border-white/10">
            <div class="text-xl font-extrabold text-emerald-300">₹<?= number_format((float)$p['price'],2) ?></div>
            <button onclick='openBuyModal(<?= json_encode(['id'=>(int)$p['id'],'mod'=>($p['mod_name']??''),'plan'=>($p['plan_name']??''),'dur'=>plan_expiry_label((int)$p['duration'],$p['duration_type']),'price'=>(float)$p['price']], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' class="btn-glow px-4 py-2 rounded-xl text-xs font-bold">Buy Now</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="glass p-5"><h3 class="font-bold mb-2">My Purchased Keys (<?= count($myModKeys) ?>)</h3>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($myModKeys as $k): ?>
          <div class="glass-soft p-3">
            <div class="flex justify-between text-xs mb-1"><b><?= e($k['mod_name'] ?? 'Key') ?></b><span class="badge badge-<?= e($k['status']) ?>"><?= e($k['status']) ?></span></div>
            <code class="text-cyan-200 text-xs break-all"><?= e($k['key_string']) ?></code>
            <div class="text-[11px] text-slate-500 mt-1">Bought: <?= e($k['sold_at'] ?? $k['created_at']) ?> • <?= e($k['duration']!==null?plan_expiry_label((int)$k['duration'],$k['duration_type']):duration_label($k['duration_type'])) ?></div>
            <button onclick="copyText('<?= e($k['key_string']) ?>','Key copied!')" class="glass-soft px-3 py-1 rounded-lg text-xs mt-2">Copy</button>
          </div>
        <?php endforeach; if (!$myModKeys): ?><div class="text-sm text-slate-500">No keys yet — buy a plan above.</div><?php endif; ?>
        </div>
      </div>
      <!-- Buy modal with UPI QR -->
      <div id="buyModal" class="fixed inset-0 z-50 items-center justify-center bg-black/60 backdrop-blur-sm px-4" style="display:none">
        <div class="glass w-full max-w-md p-6 relative">
          <button onclick="closeBuyModal()" class="absolute top-3 right-3 glass-soft w-8 h-8 rounded-lg">✕</button>
          <h3 class="font-extrabold text-lg">Complete Purchase</h3>
          <p class="text-xs text-slate-400 mb-3">Scan QR, pay exact amount, submit UTR.</p>
          <div class="glass-soft p-3 text-sm mb-3"><div>Plan: <b id="bmPlan">-</b></div><div>Duration: <b id="bmDur">-</b></div><div>Pay: <b id="bmPrice" class="text-emerald-300">-</b></div></div>
          <div class="flex justify-center mb-2 bg-white p-3 rounded-xl w-fit mx-auto"><img id="bmQr" src="" class="w-44 h-44" alt="UPI QR"></div>
          <div class="text-center text-xs mb-3">UPI: <code id="bmUpi"><?= e($upiId) ?></code> <button onclick="copyText(document.getElementById('bmUpi').textContent,'UPI copied!')" class="underline">copy</button></div>
          <form method="POST" action="actions.php">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="store_order">
            <input type="hidden" name="plan_id" id="bmPlanId">
            <input name="upi_txn_id" required placeholder="12-digit UTR" class="input-glass w-full px-3 py-2.5 text-sm mb-2 font-mono">
            <button class="btn-glow w-full rounded-xl py-2.5 font-bold text-sm">I Paid — Submit Order</button>
          </form>
        </div>
      </div>
    </div>

    <!-- KEYS -->
    <div id="tab-keys" class="tab-pane <?= $tab==='keys'?'':'hidden' ?>">
      <div class="glass p-5 mb-4">
        <h3 class="font-bold mb-3">Generate Key — <?= $role==='reseller' ? 'paid from wallet' : 'FREE ('.$role.')' ?></h3>
        <form method="POST" action="actions.php" class="grid md:grid-cols-6 gap-2">
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
          <button class="btn-glow rounded-xl py-2.5 font-bold text-sm md:col-span-2">Generate</button>
        </form>
        <div class="text-[11px] text-slate-500 mt-2">Mod keys auto-generate on order approval. Admin API se bhi block/unblock/expire/delete/edit hota hai (API Docs dekho).</div>
      </div>
      <div class="glass p-5">
        <form method="GET" class="flex flex-col md:flex-row gap-2 mb-3">
          <input type="hidden" name="tab" value="keys">
          <input name="q" value="<?= e($q) ?>" placeholder="Search key / email / mod..." class="input-glass flex-1 px-3 py-2 text-sm">
          <select name="mod_id" class="input-glass px-3 py-2 text-sm"><option value="">All mods</option><?php foreach ($mods as $m): ?><option value="<?= (int)$m['id'] ?>" <?= $modFilter==(string)$m['id']?'selected':'' ?>><?= e($m['name']) ?></option><?php endforeach; ?></select>
          <button class="glass-soft px-4 py-2 rounded-xl text-sm">Search</button>
        </form>
        <div class="overflow-x-auto scroll-thin"><table class="w-full text-sm table-glass min-w-[860px]">
          <tr><th>Key</th><th>Mod/Plan</th><th>Expiry/Sold</th><th>Devices</th><th>Status</th><th>Action</th></tr>
          <?php foreach ($keys as $k): $dev = $k['hwid'] ? (json_decode($k['hwid'],true)?:[$k['hwid']]) : []; $dc = is_array($dev)?count($dev):0; ?>
          <tr>
            <td><code class="text-cyan-200 text-xs"><?= e($k['key_string']) ?></code><div class="text-[11px] text-slate-500"><?= e($k['assigned_to']??'') ?> • by <?= e($k['creator']??('#'.$k['created_by'])) ?><?= !empty($k['sold_to'])?' • sold→#'.(int)$k['sold_to']:'' ?></div></td>
            <td class="text-xs"><?= e($k['mod_name'] ?? duration_label($k['duration_type'])) ?><?= isset($k['price']) && (float)$k['price']>0 ? '<div class="text-emerald-300">₹'.e($k['price']).'</div>':'' ?></td>
            <td class="text-xs"><?= e($k['sold_at'] ?? $k['expires_at'] ?? 'Never') ?></td>
            <td class="text-xs"><?= $dc ?>/<?= (int)$k['device_limit'] ?><?= !empty($k['device_id'])?'<div class="text-[10px] truncate max-w-[110px]">🔒 '.e($k['device_id']).'</div>':'' ?></td>
            <td><span class="badge badge-<?= e(in_array($k['status'],['active','sold','available'])?'active':($k['status']==='pending'?'pending':'revoked')) ?>"><?= e($k['status']) ?></span></td>
            <td class="flex gap-1 flex-wrap">
              <button onclick="copyText('<?= e($k['key_string']) ?>','Key copied!')" class="glass-soft px-2 py-1 rounded-lg text-xs">Copy</button>
              <?php if (!in_array($k['status'],['revoked','blocked'])): ?>
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="revoke_key"><input type="hidden" name="key_id" value="<?= (int)$k['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-red-300">Revoke</button></form>
              <?php else: if ($role!=='reseller'): ?>
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="activate_key"><input type="hidden" name="key_id" value="<?= (int)$k['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-emerald-300">Activate</button></form>
              <?php endif; endif; ?>
              <?php if ($role==='owner'): ?>
              <form method="POST" action="actions.php" onsubmit="return confirm('Delete?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_key"><input type="hidden" name="key_id" value="<?= (int)$k['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs">Del</button></form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; if (!$keys): ?><tr><td colspan="6" class="text-center text-slate-500 py-6">No keys yet.</td></tr><?php endif; ?>
        </table></div>
      </div>
    </div>

    <!-- ORDERS -->
    <div id="tab-orders" class="tab-pane <?= $tab==='orders'?'':'hidden' ?>">
      <?php if ($role!=='reseller'): ?>
      <div class="glass p-5 mb-3"><h3 class="font-bold">Pending Plan Orders (<?= count($pendOrderTx) ?>)</h3>
        <?php if (!$pendOrderTx): ?><div class="text-sm text-slate-500 mt-2">No pending orders. User Store se Buy karega to yaha UTR ke saath ayega, Approve pe key auto-generate hogi.</div><?php endif; ?>
        <?php foreach ($pendOrderTx as $t): ?>
          <div class="glass-soft p-3 mt-2 flex flex-col md:flex-row md:items-center gap-2 justify-between">
            <div class="text-sm">#<?= (int)$t['id'] ?> • <?= e($t['name'] ?? $t['email']) ?> (<?= e($t['email']) ?>) • <b><?= e($t['mod_name'] ?? '') ?> <?= e($t['plan_name'] ?? '') ?></b> • <b class="text-emerald-300">₹<?= e($t['amount']) ?></b> • UTR: <code><?= e($t['upi_txn_id'] ?? $t['reference']) ?></code> • <?= e($t['created_at']) ?></div>
            <div class="flex gap-2">
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="order_approve"><input type="hidden" name="tx_id" value="<?= (int)$t['id'] ?>"><button class="btn-glow px-4 py-1.5 rounded-xl text-xs font-bold">Approve + Gen Key</button></form>
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="order_reject"><input type="hidden" name="tx_id" value="<?= (int)$t['id'] ?>"><button class="glass-soft px-4 py-1.5 rounded-xl text-xs">Reject</button></form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div class="glass p-5"><h3 class="font-bold mb-2"><?= $role==='reseller' ? 'My Orders' : 'Recent Purchase Orders' ?></h3>
        <div class="overflow-x-auto scroll-thin"><table class="w-full text-xs table-glass min-w-[720px]"><tr><th>ID</th><th>Item</th><th>UTR</th><th>Amt</th><th>Status</th><th>Time</th><?= $role!=='reseller'?'<th>Act</th>':'' ?></tr>
        <?php $orderRows = $role==='reseller' ? $myOrders : array_slice($txs,0,60); foreach ($orderRows as $t): if ($role!=='reseller' && ($t['type']??'')!=='purchase') continue; ?>
          <tr><td>#<?= (int)$t['id'] ?></td><td class="max-w-[220px] truncate"><?= e(($t['mod_name']??'').' '.($t['plan_name']??'').' '.($t['reference']??'')) ?></td><td class="font-mono"><?= e($t['upi_txn_id'] ?? '-') ?></td><td>₹<?= e($t['amount']) ?></td><td><span class="badge badge-<?= e(in_array($t['status'],['completed','approved'])?'active':($t['status']==='pending'?'pending':'revoked')) ?>"><?= e($t['status']) ?></span></td><td><?= e($t['created_at']) ?></td>
          <?php if ($role!=='reseller'): ?><td><?php if (($t['status']??'')==='pending'): ?><div class="flex gap-1"><form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="order_approve"><input type="hidden" name="tx_id" value="<?= (int)$t['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-emerald-300">Approve</button></form><form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="order_reject"><input type="hidden" name="tx_id" value="<?= (int)$t['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-red-300">Reject</button></form></div><?php else: ?>-<?php endif; ?></td><?php endif; ?></tr>
        <?php endforeach; ?>
        </table></div>
      </div>
    </div>

    <?php if ($role==='reseller'): ?>
    <!-- WALLET with QR -->
    <div id="tab-wallet" class="tab-pane <?= $tab==='wallet'?'':'hidden' ?>">
      <div class="grid md:grid-cols-2 gap-3">
        <div class="glass p-5">
          <div class="text-xs text-slate-400">Wallet Balance</div><div class="text-4xl font-extrabold text-emerald-300">₹<?= number_format((float)($user['wallet_balance']??0),2) ?></div>
          <div class="text-xs text-slate-400 mt-2">UPI <b class="text-white"><?= e($upiId) ?></b> pe pay karo, QR scan karo, UTR submit karo.</div>
          <div class="flex justify-center my-3 bg-white p-3 rounded-xl w-fit mx-auto"><img id="topupQr" src="" class="w-40 h-40" alt="UPI QR"></div>
          <form method="POST" action="actions.php" class="flex flex-col gap-2 mt-1">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="topup_request">
            <input id="topupAmt" name="amount" type="number" min="10" step="1" required placeholder="Amount ₹" class="input-glass px-3 py-2.5 text-sm" oninput="drawTopupQr()">
            <input name="reference" required placeholder="UPI UTR / Reference (12-digit)" class="input-glass px-3 py-2.5 text-sm font-mono">
            <button class="btn-glow rounded-xl py-2.5 font-bold text-sm">Request Top-up</button>
          </form>
        </div>
        <div class="glass p-5"><h3 class="font-bold mb-2">Transactions</h3>
          <div class="overflow-x-auto scroll-thin max-h-[420px] overflow-y-auto"><table class="w-full text-xs table-glass">
            <tr><th>Type</th><th>Amt</th><th>Ref</th><th>Status</th><th>Time</th></tr>
            <?php foreach ($txs as $t): ?><tr><td><?= e($t['type']) ?></td><td class="<?= ((float)$t['amount']<0)?'text-red-300':'text-emerald-300' ?>">₹<?= e($t['amount']) ?></td><td class="max-w-[140px] truncate"><?= e(trim(($t['reference']??'').' '.($t['upi_txn_id']??''))) ?></td><td><span class="badge badge-<?= e(in_array($t['status'],['completed','approved'])?'active':($t['status']==='pending'?'pending':'revoked')) ?>"><?= e($t['status']) ?></span></td><td><?= e($t['created_at']) ?></td></tr><?php endforeach; ?>
          </table></div>
        </div>
      </div>
    </div>
    <!-- DOWNLOADS user -->
    <div id="tab-downloads" class="tab-pane <?= $tab==='downloads'?'':'hidden' ?>">
      <div class="glass p-5"><h3 class="font-bold mb-1">My Applications</h3><p class="text-xs text-slate-400 mb-3">Purchased mods ke APK yaha milege.</p>
        <?php if (!$myDownloads): ?><div class="text-sm text-slate-500">Koi purchase nahi. Store se plan kharido.</div>
        <?php else: ?><div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($myDownloads as $d): ?>
          <div class="glass-soft p-4 flex flex-col gap-2">
            <div class="font-bold"><?= e($d['mod_name'] ?? 'Mod') ?></div>
            <div class="text-xs text-slate-400"><?= e(mb_strimwidth($d['description']??'',0,120,'…')) ?></div>
            <code class="text-[11px] text-cyan-200 break-all"><?= e($d['key_string']) ?></code>
            <?php if (!empty($d['file_path'])): ?><a href="download.php?id=<?= (int)$d['apk_id'] ?>" class="btn-glow text-center rounded-xl py-2 text-xs font-bold">Download APK<?= !empty($d['file_name'])?' ('.e($d['file_name']).')':'' ?></a>
            <?php else: ?><div class="text-xs text-yellow-300">APK not uploaded yet.</div><?php endif; ?>
          </div>
        <?php endforeach; ?></div><?php endif; ?>
      </div>
    </div>
    <?php else: ?>
    <!-- MODS admin -->
    <div id="tab-mods" class="tab-pane <?= $tab==='mods'?'':'hidden' ?>">
      <div class="glass p-5 mb-4"><h3 class="font-bold mb-3">Add / Edit Mod</h3>
        <form method="POST" action="actions.php" class="grid md:grid-cols-3 gap-2">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="mod_save"><input type="hidden" name="mod_id" id="modId" value="">
          <input name="name" id="modName" required placeholder="Mod name (e.g. PUBGM ESP)" class="input-glass px-3 py-2.5 text-sm">
          <input name="version" id="modVer" placeholder="Version (v2.1)" class="input-glass px-3 py-2.5 text-sm">
          <select name="status" id="modStatus" class="input-glass px-3 py-2.5 text-sm"><option value="active">active</option><option value="inactive">inactive</option></select>
          <input name="purchase_link" id="modLink" placeholder="Purchase link (optional)" class="input-glass px-3 py-2.5 text-sm md:col-span-2">
          <input name="description" id="modDesc" placeholder="Short description" class="input-glass px-3 py-2.5 text-sm">
          <input name="features" id="modFeat" placeholder="Features (comma/newline separated)" class="input-glass px-3 py-2.5 text-sm md:col-span-3">
          <button class="btn-glow rounded-xl py-2.5 font-bold text-sm md:col-span-3">Save Mod</button>
        </form>
      </div>
      <div class="glass p-5"><h3 class="font-bold mb-2">All Mods (<?= count($mods) ?>)</h3>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($mods as $m): ?>
          <div class="glass-soft p-4">
            <div class="flex justify-between items-start"><b><?= e($m['name']) ?></b><span class="badge badge-<?= $m['status']==='active'?'active':'revoked' ?>"><?= e($m['status']) ?></span></div>
            <div class="text-[11px] text-slate-500"><?= e($m['version'] ?? '') ?> • <?= (int)$m['plan_count'] ?> plans • <?= (int)$m['sold_count'] ?> sold</div>
            <div class="text-xs text-slate-400 mt-1"><?= e(mb_strimwidth($m['description']??'',0,140,'…')) ?></div>
            <div class="flex gap-1 mt-2 flex-wrap">
              <button onclick='editMod(<?= json_encode(['id'=>(int)$m['id'],'name'=>$m['name'],'version'=>$m['version']??'','status'=>$m['status'],'link'=>$m['purchase_link']??'','desc'=>$m['description']??'','feat'=>$m['features']??''], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' class="glass-soft px-2 py-1 rounded-lg text-xs">Edit</button>
              <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="mod_toggle"><input type="hidden" name="mod_id" value="<?= (int)$m['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs">On/Off</button></form>
              <form method="POST" action="actions.php" onsubmit="return confirm('Delete mod + plans/keys?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="mod_delete"><input type="hidden" name="mod_id" value="<?= (int)$m['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-red-300">Del</button></form>
            </div>
          </div>
        <?php endforeach; if (!$mods): ?><div class="text-sm text-slate-500">Pehla mod add karo (e.g. BGMI ESP).</div><?php endif; ?>
        </div>
      </div>
    </div>
    <!-- PLANS admin -->
    <div id="tab-plans" class="tab-pane <?= $tab==='plans'?'':'hidden' ?>">
      <div class="glass p-5 mb-4"><h3 class="font-bold mb-3">Add / Edit Plan (hours/days/months/lifetime)</h3>
        <form method="POST" action="actions.php" class="grid md:grid-cols-3 gap-2">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="plan_save"><input type="hidden" name="plan_id" id="planId" value="">
          <select name="mod_id" id="planMod" required class="input-glass px-3 py-2.5 text-sm"><option value="">Select mod</option><?php foreach ($mods as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?></select>
          <input name="plan_name" id="planName" required placeholder="Plan (Monthly VIP)" class="input-glass px-3 py-2.5 text-sm">
          <div class="flex gap-2"><input name="duration" id="planDur" type="number" min="0" value="30" class="input-glass px-3 py-2.5 text-sm w-full"><select name="duration_type" id="planDtype" class="input-glass px-3 py-2.5 text-sm"><option value="hours">hours</option><option value="days" selected>days</option><option value="months">months</option><option value="minutes">minutes</option><option value="lifetime">lifetime</option></select></div>
          <input name="price" id="planPrice" type="number" min="0" step="0.01" required placeholder="Price ₹" class="input-glass px-3 py-2.5 text-sm">
          <input name="features" id="planFeat" placeholder="Features (optional)" class="input-glass px-3 py-2.5 text-sm md:col-span-2">
          <button class="btn-glow rounded-xl py-2.5 font-bold text-sm md:col-span-3">Save Plan</button>
        </form>
      </div>
      <div class="glass p-5"><div class="overflow-x-auto"><table class="w-full text-xs table-glass min-w-[720px]"><tr><th>Mod</th><th>Plan</th><th>Duration</th><th>Price</th><th>Status</th><th>Act</th></tr>
      <?php foreach ($plans as $p): ?><tr><td><?= e($p['mod_name'] ?? '-') ?></td><td><?= e($p['plan_name']) ?></td><td><?= e(plan_expiry_label((int)$p['duration'], $p['duration_type'])) ?></td><td class="text-emerald-300">₹<?= e($p['price']) ?></td><td><?= e($p['status']) ?></td>
      <td class="flex gap-1"><button onclick='editPlan(<?= json_encode(['id'=>(int)$p['id'],'mod'=>(int)$p['mod_id'],'name'=>$p['plan_name'],'dur'=>(int)$p['duration'],'dtype'=>$p['duration_type'],'price'=>$p['price'],'feat'=>$p['features']??''], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' class="glass-soft px-2 py-1 rounded-lg text-xs">Edit</button>
      <form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="plan_toggle"><input type="hidden" name="plan_id" value="<?= (int)$p['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs">On/Off</button></form>
      <form method="POST" action="actions.php" onsubmit="return confirm('Delete?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="plan_delete"><input type="hidden" name="plan_id" value="<?= (int)$p['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-red-300">Del</button></form></td></tr><?php endforeach; ?>
      </table></div></div>
    </div>
    <!-- APKS admin -->
    <div id="tab-downloads" class="tab-pane <?= $tab==='downloads'?'':'hidden' ?>">
      <div class="glass p-5 mb-4"><h3 class="font-bold mb-2">Upload APK for Mod</h3>
        <form method="POST" action="actions.php" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-2">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="apk_upload">
          <select name="mod_id" required class="input-glass px-3 py-2.5 text-sm"><option value="">Select mod</option><?php foreach ($mods as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?></select>
          <input type="file" name="apk" accept=".apk,.zip" required class="input-glass px-3 py-2 text-sm">
          <button class="btn-glow px-5 rounded-xl text-sm font-bold">Upload</button>
        </form>
        <div class="text-[11px] text-slate-500 mt-1">Render free pe uploads redeploy par ud sakte hain — permanent ke liye external storage use karo.</div>
      </div>
      <div class="glass p-5"><table class="w-full text-xs table-glass"><tr><th>Mod</th><th>File</th><th>Size</th><th>Link</th><th>Act</th></tr>
      <?php foreach ($apks as $a): ?><tr><td><?= e($a['mod_name'] ?? '') ?></td><td><?= e($a['file_name']) ?></td><td><?= number_format((int)$a['file_size']/1024,1) ?> KB</td><td><a class="underline" href="download.php?id=<?= (int)$a['id'] ?>">download</a></td>
      <td><form method="POST" action="actions.php" onsubmit="return confirm('Delete file?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="apk_delete"><input type="hidden" name="apk_id" value="<?= (int)$a['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-red-300">Del</button></form></td></tr><?php endforeach; ?>
      </table></div>
    </div>
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
        <div class="overflow-x-auto scroll-thin"><table class="w-full text-xs table-glass min-w-[760px]"><tr><th>ID</th><th>User</th><th>Type</th><th>Amt</th><th>Ref/UTR</th><th>Status</th><th>Time</th></tr>
        <?php foreach ($txs as $t): ?><tr><td>#<?= (int)$t['id'] ?></td><td><?= e($t['email']??$t['user_id']) ?></td><td><?= e($t['type']) ?></td><td>₹<?= e($t['amount']) ?></td><td class="max-w-[180px] truncate"><?= e(trim(($t['reference']??'').' '.($t['upi_txn_id']??''))) ?></td><td><?= e($t['status']) ?></td><td><?= e($t['created_at']) ?></td></tr><?php endforeach; ?>
        </table></div>
      </div>
    </div>
    <!-- USERS -->
    <div id="tab-users" class="tab-pane <?= $tab==='users'?'':'hidden' ?>">
      <div class="glass p-5 mb-3"><h3 class="font-bold mb-2">Direct Balance Add (admin)</h3>
        <form method="POST" action="actions.php" class="grid md:grid-cols-4 gap-2">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="balance_add">
          <select name="user_id" required class="input-glass px-3 py-2 text-sm"><option value="">Select user</option><?php foreach ($users as $x): ?><option value="<?= (int)$x['id'] ?>"><?= e($x['name']) ?> (<?= e($x['email']) ?> • ₹<?= e($x['wallet_balance']) ?>)</option><?php endforeach; ?></select>
          <input name="amount" type="number" min="1" step="0.01" required placeholder="Amount ₹" class="input-glass px-3 py-2 text-sm">
          <input name="reference" placeholder="Remark" class="input-glass px-3 py-2 text-sm">
          <button class="btn-glow rounded-xl py-2 font-bold text-sm">Add Balance</button>
        </form>
      </div>
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
    <!-- TOKENS -->
    <div id="tab-tokens" class="tab-pane <?= $tab==='tokens'?'':'hidden' ?>">
      <div class="grid md:grid-cols-3 gap-3">
        <div class="glass p-5"><h3 class="font-bold mb-2">Generate Signup Token</h3>
          <form method="POST" action="actions.php" class="flex flex-col gap-2">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="token_generate">
            <input name="expiry_days" type="number" min="1" max="365" value="7" class="input-glass px-3 py-2 text-sm">
            <button class="btn-glow rounded-xl py-2 font-bold text-sm">Generate</button>
          </form>
          <div class="text-xs text-slate-400 mt-3">Total <?= $tokStats['total'] ?> • Active <?= $tokStats['active'] ?> • Used/Exp <?= $tokStats['used'] ?></div>
          <div class="text-[11px] text-slate-500 mt-1">Settings → signup token required ON karoge to register bina token ke band.</div>
        </div>
        <div class="glass p-5 md:col-span-2"><table class="w-full text-xs table-glass"><tr><th>Code</th><th>By</th><th>Expiry</th><th>Status</th><th>Act</th></tr>
        <?php foreach ($tokens as $tk): $ex = strtotime($tk['expires_at']); $st8 = ($tk['status']==='active' && $ex>time())?'active':'expired'; ?>
          <tr><td><code><?= e($tk['code']) ?></code> <button onclick="copyText('<?= e($tk['code']) ?>','Copied!')" class="underline">copy</button></td><td><?= e($tk['creator']??'') ?></td><td><?= e($tk['expires_at']) ?></td><td><span class="badge badge-<?= $st8==='active'?'active':'expired' ?>"><?= $st8 ?></span></td>
          <td class="flex gap-1"><form method="POST" action="actions.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="token_deactivate"><input type="hidden" name="token_id" value="<?= (int)$tk['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs">Lock</button></form>
          <form method="POST" action="actions.php" onsubmit="return confirm('Delete?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="token_delete"><input type="hidden" name="token_id" value="<?= (int)$tk['id'] ?>"><button class="glass-soft px-2 py-1 rounded-lg text-xs text-red-300">Del</button></form></td></tr>
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

    <!-- API DOCS (ref-style + ours) -->
    <div id="tab-api" class="tab-pane <?= $tab==='api'?'':'hidden' ?>">
      <div class="glass p-5 mb-3">
        <h3 class="font-bold mb-2">1) App Verify — <code class="text-cyan-300">GET /api.php?key=&device_id=</code> <span class="badge">MultiPanelX compatible</span></h3>
        <pre class="glass-soft p-4 text-xs overflow-x-auto">curl "<?= e($apiBase) ?>/api.php?key=XXXX-XXXX&device_id=DEV123"

# success:
{"status":"success","message":"License key validated successfully",
 "data":{"mod_name":"BGMI ESP","duration":"30 days","sold_at":"2026-..","device_id":"DEV123"}}
# errors: Invalid license key / mod disabled / blocked / expired /
#         not sold yet / locked to another device / Device ID required</pre>
        <h3 class="font-bold mt-4 mb-2">2) Validate — <code class="text-cyan-300">POST /api/validate</code> (JSON)</h3>
        <pre class="glass-soft p-4 text-xs overflow-x-auto">curl -X POST <?= e($apiBase) ?>/api/validate.php -H "Content-Type: application/json" -d '{"key":"XXXX","hwid":"DEV123"}'</pre>
        <h3 class="font-bold mt-4 mb-2">3) Admin Remote API — <code class="text-cyan-300">POST /api.php</code></h3>
        <pre class="glass-soft p-4 text-xs overflow-x-auto">curl -X POST <?= e($apiBase) ?>/api.php -d 'api_key=<?= e($role==='owner'?$adminApiKey:'***') ?>&action=block&key_id=12'
# action: block | unblock | expire | delete | edit (+duration,duration_type,price,reset_device=1)</pre>
        <?php if ($role==='owner'): ?>
        <form method="POST" action="actions.php" class="mt-3 flex flex-col md:flex-row gap-2 md:items-center">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="regen_api_key">
          <code class="text-xs flex-1 break-all">KEY: <?= e($adminApiKey) ?></code>
          <button onclick="copyText('<?= e($adminApiKey) ?>','API key copied!')" type="button" class="glass-soft px-3 py-2 rounded-xl text-xs">Copy</button>
          <button class="glass-soft px-3 py-2 rounded-xl text-xs">Regenerate</button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- PROFILE -->
    <div id="tab-profile" class="tab-pane <?= $tab==='profile'?'':'hidden' ?>">
      <div class="grid md:grid-cols-2 gap-3">
        <div class="glass p-5"><h3 class="font-bold mb-3">Update Profile</h3>
          <form method="POST" action="actions.php" class="flex flex-col gap-2">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="profile_update">
            <input name="name" value="<?= e($user['name']) ?>" class="input-glass px-3 py-2.5 text-sm">
            <input name="email" type="email" value="<?= e($user['email']) ?>" class="input-glass px-3 py-2.5 text-sm">
            <button class="btn-glow rounded-xl py-2.5 font-bold text-sm">Save</button>
          </form>
        </div>
        <div class="glass p-5"><h3 class="font-bold mb-3">Change Password</h3>
          <form method="POST" action="actions.php" class="flex flex-col gap-2">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="password_change">
            <input name="current_password" type="password" required placeholder="Current" class="input-glass px-3 py-2.5 text-sm">
            <input name="new_password" type="password" required placeholder="New (min 6)" class="input-glass px-3 py-2.5 text-sm">
            <input name="confirm_password" type="password" required placeholder="Confirm" class="input-glass px-3 py-2.5 text-sm">
            <button class="btn-glow rounded-xl py-2.5 font-bold text-sm">Update Password</button>
          </form>
        </div>
      </div>
    </div>

    <?php if ($role==='owner'): ?>
    <!-- SETTINGS -->
    <div id="tab-settings" class="tab-pane <?= $tab==='settings'?'':'hidden' ?>">
      <div class="glass p-5">
        <h3 class="font-bold mb-3">Global Branding + Pricing + Payments</h3>
        <form method="POST" action="actions.php" class="grid md:grid-cols-2 gap-3">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update_pricing">
          <div><label class="text-xs">App Name</label><input name="app_name" value="<?= e(get_setting('app_name','NEXUS License Panel')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Tagline</label><input name="site_tagline" value="<?= e(get_setting('site_tagline','Premium Mod Panel')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Support Email</label><input name="support_email" value="<?= e(get_setting('support_email','admin@example.com')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Telegram Link</label><input name="telegram_link" value="<?= e(get_setting('telegram_link','')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">UPI ID (QR + topup)</label><input name="upi_id" value="<?= e($upiId) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Referral % (0-50)</label><input name="referral_percent" value="<?= e(get_setting('referral_percent','10')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Price 1 Day ₹</label><input name="price_1day" value="<?= e(get_setting('price_1day','49')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Price 7 Days ₹</label><input name="price_7days" value="<?= e(get_setting('price_7days','149')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Price 30 Days ₹</label><input name="price_30days" value="<?= e(get_setting('price_30days','299')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div><label class="text-xs">Price Lifetime ₹</label><input name="price_lifetime" value="<?= e(get_setting('price_lifetime','999')) ?>" class="input-glass w-full px-3 py-2.5 text-sm mt-1"></div>
          <div class="md:col-span-2 glass-soft p-3 flex items-center gap-2 text-sm"><input type="checkbox" name="signup_token_required" value="1" <?= get_setting('signup_token_required','0')==='1'?'checked':'' ?>> <span>Signup token required (Tokens tab se token banao, bina token register band)</span></div>
          <button class="btn-glow md:col-span-2 rounded-xl py-2.5 font-bold text-sm">Save Settings</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

  </main>
</div>
<script>
// chart
(function(){
  const el = document.getElementById('analyticsChart');
  if (el && window.Chart) {
    new Chart(el.getContext('2d'), {type:'line',
      data:{labels:<?= json_encode($chartLabels) ?>,datasets:[
        {label:'Keys Sold',data:<?= json_encode($chartSales) ?>,borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,.18)',fill:true,tension:.4,borderWidth:3},
        {label:'Revenue',data:<?= json_encode($chartRev) ?>,borderColor:'#22d3ee',backgroundColor:'rgba(34,211,238,.14)',fill:true,tension:.4,borderWidth:3}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#a5a5c0',font:{size:11}}}},scales:{x:{ticks:{color:'#8b8ba3',font:{size:11}},grid:{display:false}},y:{ticks:{color:'#8b8ba3',font:{size:11}},grid:{color:'rgba(255,255,255,.07)'}}}}});
  }
})();
// UPI QR
const UPI_ID = <?= json_encode($upiId) ?>;
function upiQr(el, amt){
  if(!el) return;
  const a = parseFloat(amt||0);
  if(!UPI_ID || !(a>0)){ el.src=''; el.parentElement.style.display='none'; return; }
  el.parentElement.style.display='';
  el.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='+encodeURIComponent('upi://pay?pa='+UPI_ID+'&pn=Admin&am='+a.toFixed(2)+'&cu=INR');
}
function drawTopupQr(){ upiQr(document.getElementById('topupQr'), document.getElementById('topupAmt')?.value); }
function openBuyModal(p){
  document.getElementById('bmPlanId').value=p.id;
  document.getElementById('bmPlan').textContent=p.mod+' - '+p.plan;
  document.getElementById('bmDur').textContent=p.dur;
  document.getElementById('bmPrice').textContent='₹'+Number(p.price).toFixed(2);
  upiQr(document.getElementById('bmQr'), p.price);
  const m=document.getElementById('buyModal'); m.style.display='flex';
}
function closeBuyModal(){ document.getElementById('buyModal').style.display='none'; }
document.getElementById('buyModal')?.addEventListener('click',e=>{ if(e.target.id==='buyModal') closeBuyModal(); });
function editMod(m){ document.getElementById('modId').value=m.id; document.getElementById('modName').value=m.name||''; document.getElementById('modVer').value=m.version||''; document.getElementById('modStatus').value=m.status||'active'; document.getElementById('modLink').value=m.link||''; document.getElementById('modDesc').value=m.desc||''; document.getElementById('modFeat').value=m.feat||''; window.scrollTo({top:0,behavior:'smooth'}); }
function editPlan(p){ document.getElementById('planId').value=p.id; document.getElementById('planMod').value=p.mod; document.getElementById('planName').value=p.name||''; document.getElementById('planDur').value=p.dur??30; document.getElementById('planDtype').value=p.dtype||'days'; document.getElementById('planPrice').value=p.price??0; document.getElementById('planFeat').value=p.feat||''; window.scrollTo({top:0,behavior:'smooth'}); }
document.addEventListener('keydown',e=>{ if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){ e.preventDefault(); document.querySelector('input[name=q]')?.focus(); }});
drawTopupQr();
</script>
<script src="assets/js/app.js"></script>
</body></html>
