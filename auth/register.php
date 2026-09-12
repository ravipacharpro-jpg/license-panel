<?php
require_once __DIR__ . '/../app/icons.php';
$db = new Database();
$conn = $db->getConnection();

$mexx_theme = $_COOKIE['mexx_theme'] ?? 'nebula';
if (!in_array($mexx_theme, ['nebula', 'azure', 'light'])) $mexx_theme = 'nebula';
$theme_palettes = [
    'nebula' => ['a1'=>'#9B4DFF','a2'=>'#6C2DFF','a3'=>'#A855F7','glow2'=>'#6C2DFF','bg0'=>'#060609','card'=>'#11101A','border'=>'#2A1A40','text'=>'#F5F3FF','textDim'=>'#9893A8','textFaint'=>'#6B657A','inputBg'=>'#0B0A10','gridLine'=>'rgba(255,255,255,0.025)','ghost'=>'#A855F7','ghostOpacity'=>'0.04'],
    'azure'  => ['a1'=>'#4D8DFF','a2'=>'#2D63FF','a3'=>'#60A5FA','glow2'=>'#2D63FF','bg0'=>'#05070C','card'=>'#0E131E','border'=>'#1A2440','text'=>'#F2F5FF','textDim'=>'#8D97AE','textFaint'=>'#60687D','inputBg'=>'#0A0E17','gridLine'=>'rgba(255,255,255,0.025)','ghost'=>'#60A5FA','ghostOpacity'=>'0.04'],
    'light'  => ['a1'=>'#7C3AED','a2'=>'#6D28D9','a3'=>'#9333EA','glow2'=>'#9333EA','bg0'=>'#F3F1FA','card'=>'#FFFFFF','border'=>'#E3DDF2','text'=>'#1C1730','textDim'=>'#655F78','textFaint'=>'#948EA6','inputBg'=>'#FBFAFE','gridLine'=>'rgba(88,60,150,0.05)','ghost'=>'#8A84A0','ghostOpacity'=>'0.07'],
];
$tp = $theme_palettes[$mexx_theme];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $referral = trim($_POST['referral_code'] ?? '');
    $tenant_code = 'MEXX001';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $error = 'Username or email already taken.';
        } else {
            $role = 'reseller';
            $balance = 0.00;

            if (!empty($referral)) {
                $stmt = $conn->prepare("SELECT * FROM referral_codes WHERE code = ? AND tenant_code = ? AND is_active = 1 LIMIT 1");
                $stmt->bind_param("ss", $referral, $tenant_code);
                $stmt->execute();
                $ref = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($ref) {
                    $role = $ref['role_assigned'];
                    $balance = $ref['initial_balance'];
                }
            }

            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $conn->prepare("INSERT INTO users (tenant_code, username, email, password_hash, role, wallet_balance, referred_by_code, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')");
            $stmt->bind_param("sssssds", $tenant_code, $username, $email, $hash, $role, $balance, $referral);
            $stmt->execute();
            $stmt->close();

            $success = 'Account created! You can now log in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - <?= e(PANEL_NAME) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{
  margin:0;min-height:100vh;font-family:'Inter',sans-serif;color:<?= $tp['text'] ?>;
  background:
    radial-gradient(1000px 600px at 20% 0%, <?= $tp['a1'] ?>38, transparent 55%),
    radial-gradient(900px 500px at 100% 100%, <?= $tp['glow2'] ?>24, transparent 55%),
    <?= $tp['bg0'] ?>;
  display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;padding:30px 0;
}
.grid-overlay{position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);background-size:42px 42px;mask-image:radial-gradient(ellipse 80% 60% at 50% 30%, #000 40%, transparent 90%);}
.orb{position:absolute;border-radius:50%;filter:blur(70px);opacity:0.5;pointer-events:none;}
.orb-1{width:340px;height:340px;background:<?= $tp['a1'] ?>;top:-100px;left:-80px;}
.orb-2{width:280px;height:280px;background:<?= $tp['glow2'] ?>;bottom:-80px;right:-60px;}

.wrap{position:relative;z-index:2;width:400px;padding:0 20px;}
.badge-row{display:flex;justify-content:center;margin-bottom:20px;}
.logo-3d{width:60px;height:60px;border-radius:18px;background:linear-gradient(135deg,<?= $tp['a1'] ?>,<?= $tp['a2'] ?>,<?= $tp['a3'] ?>);display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 20px 40px -12px <?= $tp['a1'] ?>a6, inset 0 2px 0 rgba(255,255,255,0.4);transform:perspective(400px) rotateX(6deg);}

.card{background:linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0) 40%), <?= $tp['card'] ?>;border:1px solid <?= $tp['border'] ?>;border-radius:22px;padding:34px 32px 28px;box-shadow:0 1px 0 rgba(255,255,255,0.04) inset, 0 30px 60px -20px rgba(0,0,0,0.7);}
h1{margin:0 0 4px;font-size:21px;font-weight:800;text-align:center;}
p.sub{color:<?= $tp['textDim'] ?>;margin:0 0 22px;font-size:13px;text-align:center;}

.field{margin-bottom:14px;}
.field label{display:block;font-size:11.5px;color:<?= $tp['textDim'] ?>;margin-bottom:6px;font-weight:600;}
.input-icon{position:relative;}
.input-icon svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:<?= $tp['textFaint'] ?>;pointer-events:none;}
.input-icon input{width:100%;padding:11px 14px 11px 40px;border-radius:11px;border:1px solid <?= $tp['border'] ?>;background:<?= $tp['inputBg'] ?>;color:<?= $tp['text'] ?>;font-size:13.5px;outline:none;}
.input-icon input:focus{border-color:<?= $tp['a1'] ?>;box-shadow:0 0 0 3px <?= $tp['a1'] ?>2e;}

button.btn-primary{width:100%;margin-top:6px;padding:12px;border:0;border-radius:12px;background:linear-gradient(135deg,<?= $tp['a1'] ?>,<?= $tp['a2'] ?>);color:#fff;font-weight:700;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 10px 26px -6px <?= $tp['a1'] ?>99;}
button.btn-primary:hover{transform:translateY(-1px);}

.err{display:flex;align-items:center;gap:9px;background:rgba(251,113,133,0.09);border:1px solid rgba(251,113,133,0.3);color:#fda4af;padding:10px 13px;border-radius:11px;font-size:12.5px;margin-bottom:14px;}
.ok{display:flex;align-items:center;gap:9px;background:rgba(52,211,153,0.09);border:1px solid rgba(52,211,153,0.3);color:#86efac;padding:10px 13px;border-radius:11px;font-size:12.5px;margin-bottom:14px;}

.foot{margin-top:18px;text-align:center;font-size:12.5px;}
.foot a{color:<?= $tp['a3'] ?>;text-decoration:none;font-weight:600;}
@keyframes ghost-eye-pulse{0%,100%{opacity:1;}50%{opacity:.3;}}
.ghost-eye{animation:ghost-eye-pulse 2.6s ease-in-out infinite;}
.logo-3d svg{filter:drop-shadow(0 0 6px rgba(255,255,255,0.5));}
</style>
<script src="/assets/ghost-fx.js" defer></script>
</head>
<body data-ghost-color="<?= e($tp['a1']) ?>">
  <div class="grid-overlay"></div>
  <div style="position:fixed;right:-70px;bottom:-50px;width:380px;height:380px;opacity:<?= $tp['ghostOpacity'] ?>;pointer-events:none;z-index:0;color:<?= $tp['ghost'] ?>;">
    <svg viewBox="0 0 200 130" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:100%;height:100%;">
      <path d="M40 118V55C40 26 63 3 92 3h16c29 0 52 23 52 52v63l-16-14-15 14-15-14-15 14-15-14-15 14-15-14-14 14z"/>
      <circle class="ghost-eye" cx="78" cy="52" r="5.5" fill="currentColor" stroke="none"/>
      <circle class="ghost-eye" cx="122" cy="52" r="5.5" fill="currentColor" stroke="none"/>
    </svg>
  </div>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>

  <div class="wrap">
    <div class="badge-row"><div class="logo-3d"><?= icon('user', 28) ?></div></div>
    <div class="card">
      <h1>Create Account</h1>
      <p class="sub">Join <?= e(PANEL_NAME) ?> as a reseller</p>

      <?php if ($error): ?><div class="err"><?= icon('alert', 16) ?><span><?= e($error) ?></span></div><?php endif; ?>
      <?php if ($success): ?><div class="ok"><?= icon('check-circle', 16) ?><span><?= e($success) ?></span></div><?php endif; ?>

      <form method="POST">
        <div class="field">
          <label>Username</label>
          <div class="input-icon"><?= icon('user', 16) ?><input type="text" name="username" required placeholder="Choose a username"></div>
        </div>
        <div class="field">
          <label>Email</label>
          <div class="input-icon"><?= icon('mail', 16) ?><input type="email" name="email" required placeholder="you@example.com"></div>
        </div>
        <div class="field">
          <label>Password</label>
          <div class="input-icon"><?= icon('lock', 16) ?><input type="password" name="password" required placeholder="At least 6 characters"></div>
        </div>
        <div class="field">
          <label>Referral Code (optional)</label>
          <div class="input-icon"><?= icon('referral', 16) ?><input type="text" name="referral_code" placeholder="Have a code? Enter it"></div>
        </div>
        <button type="submit" class="btn-primary">Create Account <?= icon('arrow-right', 16) ?></button>
      </form>
      <div class="foot"><a href="/login">Already have an account? Sign in</a></div>
    </div>
  </div>
</body>
</html>
