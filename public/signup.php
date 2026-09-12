<?php
require_once __DIR__ . '/../src/helpers.php';
if (current_user()) redirect('dashboard.php');
$error = ''; $success = '';
$refPrefill = trim($_GET['ref'] ?? '');
$tokenRequired = get_setting('signup_token_required', '0') === '1';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) { $error = 'Invalid session, refresh and try again.'; }
    else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $refCode = trim($_POST['ref'] ?? '');
        $tokenCode = trim($_POST['signup_token'] ?? '');
        if (strlen($name) < 2) $error = 'Enter your name.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter valid email.';
        elseif (strlen($pass) < 6) $error = 'Password min 6 chars.';
        else {
            try {
                $pdo = db();
                $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $chk->execute([$email]);
                if ($chk->fetch()) { $error = 'Email already registered. Login.'; }
                else {
                    $cnt = (int)$pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
                    $isFirst = ($cnt === 0);
                    // signup token check (skip for very first owner)
                    $tokenRow = null;
                    if ($tokenRequired && !$isFirst) {
                        if ($tokenCode === '') { $error = 'Signup token required. Ask admin.'; }
                        else {
                            $t = $pdo->prepare("SELECT * FROM referral_tokens WHERE code=? AND status='active' LIMIT 1");
                            $t->execute([$tokenCode]);
                            $tokenRow = $t->fetch();
                            if (!$tokenRow || strtotime($tokenRow['expires_at']) < time()) $error = 'Invalid/expired signup token.';
                        }
                        if ($error !== '') throw new Exception('__token_error__');
                    } elseif ($tokenCode !== '' && !$isFirst) {
                        $t = $pdo->prepare("SELECT * FROM referral_tokens WHERE code=? AND status='active' LIMIT 1");
                        $t->execute([$tokenCode]);
                        $tr = $t->fetch();
                        if ($tr && strtotime($tr['expires_at']) >= time()) $tokenRow = $tr;
                    }
                    $referredBy = null;
                    if ($refCode !== '') {
                        $r = $pdo->prepare('SELECT id FROM users WHERE referral_code = ? LIMIT 1');
                        $r->execute([$refCode]);
                        $ref = $r->fetch();
                        if ($ref) $referredBy = (int)$ref['id'];
                    }
                    $cnt = (int)$pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
                    $role = ($cnt === 0) ? 'owner' : 'reseller'; // first user = owner
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $myRef = generate_referral_code($pdo);
                    $ins = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,wallet_balance,referral_code,referred_by,status) VALUES (?,?,?,?,0,?,?,?)');
                    $ins->execute([$name, $email, $hash, $role, $myRef, $referredBy, 'active']);
                    $uid = (int)$pdo->lastInsertId();
                    if ($tokenRow) {
                        $pdo->prepare("UPDATE referral_tokens SET status='inactive' WHERE id=?")->execute([(int)$tokenRow['id']]);
                    }
                    log_activity($uid, 'signup', null, 'role='.$role.' ref_by='.($referredBy??'none'));
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $uid;
                    redirect('dashboard.php');
                }
            } catch (Throwable $ex) { if ($ex->getMessage() !== '__token_error__') $error = 'DB error: '.$ex->getMessage(); }
        }
    }
}
$appName = app_name();
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Signup — <?= e($appName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="relative">
<canvas id="particles" class="fixed inset-0 w-full h-full pointer-events-none"></canvas>
<div class="glow-orb w-[400px] h-[400px] bg-cyan-500 top-10 -right-24"></div>
<div class="min-h-screen flex items-center justify-center px-4 relative z-10 py-8">
  <div class="glass tilt w-full max-w-md p-8 fade-up">
    <div class="font-extrabold text-xl">Create account</div>
    <div class="text-xs text-slate-400 mb-5">First account auto-becomes OWNER • Referral supported</div>
    <?php if ($error): ?><div class="mb-4 text-sm bg-red-500/15 border border-red-400/30 text-red-200 rounded-xl px-4 py-3"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" class="flex flex-col gap-4">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div><label class="text-xs text-slate-300">Name</label>
        <input name="name" required class="input-glass w-full mt-1 px-4 py-3" placeholder="Your name" value="<?= e($_POST['name'] ?? '') ?>"></div>
      <div><label class="text-xs text-slate-300">Email</label>
        <input name="email" type="email" required class="input-glass w-full mt-1 px-4 py-3" placeholder="you@mail.com" value="<?= e($_POST['email'] ?? '') ?>"></div>
      <div><label class="text-xs text-slate-300">Password</label>
        <input name="password" type="password" required minlength="6" class="input-glass w-full mt-1 px-4 py-3" placeholder="Min 6 chars"></div>
      <div><label class="text-xs text-slate-300">Referral code (optional)</label>
        <input name="ref" class="input-glass w-full mt-1 px-4 py-3" placeholder="NX-XXXXXX" value="<?= e($_POST['ref'] ?? $refPrefill) ?>"></div>
      <?php if ($tokenRequired): ?>
      <div><label class="text-xs text-slate-300">Signup token (required by admin)</label>
        <input name="signup_token" class="input-glass w-full mt-1 px-4 py-3" placeholder="8-char token" value="<?= e($_POST['signup_token'] ?? '') ?>"></div>
      <?php endif; ?>
      <button class="btn-glow rounded-xl py-3 font-bold">Create Account</button>
    </form>
    <div class="text-center text-sm text-slate-400 mt-5">Have account? <a href="login.php" class="text-cyan-300 font-semibold">Login</a></div>
    <div class="text-center mt-3"><a href="index.php" class="text-xs text-slate-500">← Back to home</a></div>
  </div>
</div>
<script src="assets/js/app.js"></script>
</body></html>
