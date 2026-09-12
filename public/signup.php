<?php
require_once __DIR__ . '/../src/helpers.php';
if (current_user()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) { $error = 'Invalid session, refresh and try again.'; }
    else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        if (strlen($name) < 2) $error = 'Enter your name.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter valid email.';
        elseif (strlen($pass) < 6) $error = 'Password min 6 chars.';
        elseif ($pass !== $conf) $error = 'Passwords do not match.';
        else {
            try {
                $pdo = db();
                $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $chk->execute([$email]);
                if ($chk->fetch()) { $error = 'Email already registered. Login.'; }
                else {
                    $cnt = (int)$pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
                    $role = ($cnt === 0) ? 'owner' : 'reseller'; // first user = owner
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $myRef = generate_referral_code($pdo);
                    $ins = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,wallet_balance,referral_code,status) VALUES (?,?,?,?,0,?,?)');
                    $ins->execute([$name, $email, $hash, $role, $myRef, 'active']);
                    $uid = (int)$pdo->lastInsertId();
                    log_activity($uid, 'signup', null, 'role='.$role);
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $uid;
                    redirect('dashboard.php');
                }
            } catch (Throwable $ex) { $error = 'DB error: '.$ex->getMessage(); }
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
    <div class="text-xs text-slate-400 mb-5">Join today — free registration</div>
    <?php if ($error): ?><div class="mb-4 text-sm bg-red-500/15 border border-red-400/30 text-red-200 rounded-xl px-4 py-3"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" class="flex flex-col gap-4">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div><label class="text-xs text-slate-300">Username</label>
        <input name="name" required class="input-glass w-full mt-1 px-4 py-3" placeholder="e.g. aryan123" value="<?= e($_POST['name'] ?? '') ?>"></div>
      <div><label class="text-xs text-slate-300">Email Address</label>
        <input name="email" type="email" required class="input-glass w-full mt-1 px-4 py-3" placeholder="you@mail.com" value="<?= e($_POST['email'] ?? '') ?>"></div>
      <div><label class="text-xs text-slate-300">Password</label>
        <input name="password" type="password" required minlength="6" class="input-glass w-full mt-1 px-4 py-3" placeholder="Min 6 chars"></div>
      <div><label class="text-xs text-slate-300">Confirm Password</label>
        <input name="confirm_password" type="password" required minlength="6" class="input-glass w-full mt-1 px-4 py-3" placeholder="Re-type password"></div>
      <button class="btn-glow rounded-xl py-3 font-bold">Register Account</button>
    </form>
    <div class="text-center text-sm text-slate-400 mt-5">Have account? <a href="login.php" class="text-cyan-300 font-semibold">Login</a></div>
    <div class="text-center mt-3"><a href="index.php" class="text-xs text-slate-500">← Back to home</a></div>
  </div>
</div>
<script src="assets/js/app.js"></script>
</body></html>
