<?php
require_once __DIR__ . '/../src/helpers.php';
if (current_user()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) { $error = 'Invalid session, refresh and try again.'; }
    else {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        try {
            $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $st->execute([$email]);
            $u = $st->fetch();
            if ($u && password_verify($pass, $u['password_hash'])) {
                if (($u['status'] ?? 'active') !== 'active') { $error = 'Account banned. Contact owner.'; }
                else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $u['id'];
                    log_activity($u['id'], 'login');
                    redirect('dashboard.php');
                }
            } else { $error = 'Invalid email or password.'; }
        } catch (Throwable $ex) { $error = 'DB error: ' . $ex->getMessage(); }
    }
}
$appName = app_name();
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= e($appName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="relative">
<canvas id="particles" class="fixed inset-0 w-full h-full pointer-events-none"></canvas>
<div class="glow-orb w-[400px] h-[400px] bg-purple-600 -top-24 -left-24"></div>
<div class="min-h-screen flex items-center justify-center px-4 relative z-10">
  <div class="glass tilt w-full max-w-md p-8 fade-up">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-11 h-11 rounded-2xl btn-glow flex items-center justify-center">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <div><div class="font-extrabold text-lg">Welcome back</div><div class="text-xs text-slate-400"><?= e($appName) ?></div></div>
    </div>
    <?php if ($error): ?><div class="mb-4 text-sm bg-red-500/15 border border-red-400/30 text-red-200 rounded-xl px-4 py-3"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" class="flex flex-col gap-4">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div><label class="text-xs text-slate-300">Email</label>
        <input name="email" type="email" required class="input-glass w-full mt-1 px-4 py-3" placeholder="you@mail.com" value="<?= e($_POST['email'] ?? '') ?>"></div>
      <div><label class="text-xs text-slate-300">Password</label>
        <input name="password" type="password" required class="input-glass w-full mt-1 px-4 py-3" placeholder="••••••••"></div>
      <button class="btn-glow rounded-xl py-3 font-bold mt-1">Login</button>
    </form>
    <div class="text-center text-sm text-slate-400 mt-5">No account? <a href="signup.php<?= isset($_GET['ref'])?'?ref='.e($_GET['ref']):'' ?>" class="text-cyan-300 font-semibold">Create one</a></div>
    <div class="text-center mt-3"><a href="index.php" class="text-xs text-slate-500">← Back to home</a></div>
  </div>
</div>
<script src="assets/js/app.js"></script>
</body></html>
