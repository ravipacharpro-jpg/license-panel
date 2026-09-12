<?php
require_once __DIR__ . '/../src/helpers.php';
$user = current_user();
$appName = app_name();
$p1 = get_setting('price_1day','49');
$p7 = get_setting('price_7days','149');
$p30 = get_setting('price_30days','299');
$pl = get_setting('price_lifetime','999');
$refPct = get_setting('referral_percent','10');
$hostH = $_SERVER['HTTP_HOST'] ?? 'your-app.onrender.com';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$apiBase = ($isHttps ? 'https://' : 'http://') . $hostH . '/api/validate';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($appName) ?> — Premium License Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="relative overflow-x-hidden">
<canvas id="particles" class="fixed inset-0 w-full h-full pointer-events-none"></canvas>
<div class="bg-grid fixed inset-0 pointer-events-none"></div>
<div class="glow-orb w-[420px] h-[420px] bg-purple-600 -top-32 -left-32"></div>
<div class="glow-orb w-[380px] h-[380px] bg-cyan-500 top-40 -right-24"></div>

<!-- NAV -->
<nav class="relative z-10 w-full max-w-6xl mx-auto flex items-center justify-between gap-4 px-5 sm:px-6 lg:px-8 py-5">
  <div class="flex items-center gap-3 min-w-0 shrink-0">
    <div class="w-10 h-10 rounded-2xl btn-glow flex items-center justify-center shrink-0">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    </div>
    <div class="font-extrabold text-lg tracking-tight truncate"><?= e($appName) ?></div>
  </div>
  <div class="flex items-center justify-end gap-2 ml-auto shrink-0">
    <?php if ($user): ?>
      <a href="dashboard.php" class="btn-glow inline-flex items-center justify-center whitespace-nowrap px-5 py-2.5 rounded-xl font-semibold text-sm">Dashboard</a>
    <?php else: ?>
      <a href="login.php" class="glass-soft inline-flex items-center justify-center whitespace-nowrap px-5 py-2.5 rounded-xl text-sm font-semibold hover:border-purple-400/50">Login</a>
      <a href="signup.php" class="btn-glow inline-flex items-center justify-center whitespace-nowrap px-5 py-2.5 rounded-xl font-semibold text-sm">Get Started</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<header class="relative z-10 w-full max-w-6xl mx-auto px-5 sm:px-6 lg:px-8 pt-10 pb-8 text-center">
  <div class="fade-up inline-flex items-center gap-2 glass-soft px-4 py-1.5 text-xs text-purple-200 mb-5">
    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
    LIVE • License + Wallet + Referral System
  </div>
  <h1 class="fade-up text-4xl md:text-6xl font-extrabold leading-tight">
    Sell License Keys<br><span class="neon-text">Like a Pro Panel</span>
  </h1>
  <p class="fade-up text-slate-300/90 max-w-2xl mx-auto mt-5 text-sm md:text-base">
    Owner, Admin aur Reseller ke liye full role-based panel. Key generation, HWID lock,
    wallet top-up, <?= e($refPct) ?>% referral commission, logs aur REST validate API — sab ek jagah.
  </p>
  <div class="fade-up flex flex-col sm:flex-row gap-3 justify-center mt-8">
    <?php if ($user): ?>
      <a href="dashboard.php" class="btn-glow px-8 py-3.5 rounded-2xl font-bold">Open Dashboard</a>
    <?php else: ?>
      <a href="signup.php" class="btn-glow px-8 py-3.5 rounded-2xl font-bold">Get Started — Free</a>
      <a href="login.php" class="glass px-8 py-3.5 rounded-2xl font-bold hover:border-cyan-300/50">Login</a>
    <?php endif; ?>
  </div>
  <div class="fade-up flex flex-wrap justify-center gap-2 mt-6 text-xs text-slate-300">
    <span class="badge">PHP 8.2</span><span class="badge">Tailwind</span>
    <span class="badge">SQLite / MySQL</span><span class="badge">Docker Ready</span>
    <span class="badge">Render Ready</span>
  </div>
</header>

<!-- FEATURES -->
<section class="relative z-10 w-full max-w-6xl mx-auto px-5 sm:px-6 lg:px-8 grid md:grid-cols-3 gap-4 pb-8">
  <div class="glass card-hover tilt p-6 fade-up">
    <div class="text-2xl font-bold mb-1 neon-text">01</div>
    <h3 class="font-bold text-lg mb-1">Smart Key Engine</h3>
    <p class="text-sm text-slate-300">1 Day / 7 Days / 30 Days / Lifetime keys, device limit, HWID lock, active / expired / revoked status.</p>
  </div>
  <div class="glass card-hover tilt p-6 fade-up">
    <div class="text-2xl font-bold mb-1 neon-text">02</div>
    <h3 class="font-bold text-lg mb-1">Wallet + UPI Topup</h3>
    <p class="text-sm text-slate-300">Reseller balance se key kharido. Manual UPI reference entry, admin approval, full transaction history.</p>
  </div>
  <div class="glass card-hover tilt p-6 fade-up">
    <div class="text-2xl font-bold mb-1 neon-text">03</div>
    <h3 class="font-bold text-lg mb-1">Referral Commission</h3>
    <p class="text-sm text-slate-300">Har user ka unique link. Referred recharge pe <?= e($refPct) ?>% auto commission + sales tree view.</p>
  </div>
</section>

<!-- PRICING -->
<section class="relative z-10 w-full max-w-6xl mx-auto px-5 sm:px-6 lg:px-8 pb-10">
  <h2 class="text-center text-2xl md:text-3xl font-extrabold mb-6">Simple <span class="neon-text">Pricing</span></h2>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="glass card-hover tilt p-6 text-center"><div class="text-sm text-slate-300">1 Day</div><div class="text-3xl font-extrabold mt-1">₹<?= e($p1) ?></div><div class="text-xs text-slate-400 mt-1">Trial key</div></div>
    <div class="glass card-hover tilt p-6 text-center border-purple-400/40"><div class="text-sm text-purple-200">7 Days ★</div><div class="text-3xl font-extrabold mt-1 neon-text">₹<?= e($p7) ?></div><div class="text-xs text-slate-400 mt-1">Most popular</div></div>
    <div class="glass card-hover tilt p-6 text-center"><div class="text-sm text-slate-300">30 Days</div><div class="text-3xl font-extrabold mt-1">₹<?= e($p30) ?></div><div class="text-xs text-slate-400 mt-1">Best value</div></div>
    <div class="glass card-hover tilt p-6 text-center"><div class="text-sm text-cyan-200">Lifetime</div><div class="text-3xl font-extrabold mt-1">₹<?= e($pl) ?></div><div class="text-xs text-slate-400 mt-1">One-time</div></div>
  </div>
  <div class="glass mt-4 p-4 text-sm text-slate-200 flex flex-col md:flex-row items-center justify-between gap-3">
    <span>External app se key check karna hai? <code class="text-cyan-300">POST /api/validate</code> ready hai.</span>
    <button onclick="copyText('<?= e($apiBase) ?>','API URL copied!')" class="glass-soft px-4 py-2 rounded-xl text-xs font-bold hover:border-cyan-300/50">Copy API Endpoint</button>
  </div>
</section>

<footer class="relative z-10 text-center text-xs text-slate-500 pb-10">Built with PHP + Tailwind • Docker + Render ready • © <?= date('Y') ?> <?= e($appName) ?></footer>
<script src="assets/js/app.js"></script>
</body>
</html>
