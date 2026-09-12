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

$step = $_POST['step'] ?? 'request';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'request') {
    $username = trim($_POST['username'] ?? '');
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || empty($user['telegram_chat_id'])) {
        $error = 'Account not found or Telegram not linked. Contact the admin.';
    } else {
        $otp = generate_otp();
        $expiry = date('Y-m-d H:i:s', time() + 600);
        $upd = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
        $upd->bind_param("ssi", $otp, $expiry, $user['id']);
        $upd->execute();
        $upd->close();

        send_telegram_message($user['telegram_chat_id'], "Your <b>" . e(PANEL_NAME) . "</b> password reset code: <code>{$otp}</code> (valid 10 minutes)");
        $success = 'OTP sent to your Telegram. Enter it below.';
        $step = 'verify';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'verify') {
    $username = trim($_POST['username'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || $user['otp_code'] !== $otp || strtotime($user['otp_expiry']) < time()) {
        $error = 'Invalid or expired OTP.';
        $step = 'verify';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters.';
        $step = 'verify';
    } else {
        $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        $upd = $conn->prepare("UPDATE users SET password_hash = ?, otp_code = NULL, otp_expiry = NULL WHERE id = ?");
        $upd->bind_param("si", $hash, $user['id']);
        $upd->execute();
        $upd->close();
        $success = 'Password reset successful. You can log in now.';
        $step = 'done';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password - <?= e(PANEL_NAME) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;min-height:100vh;font-family:'Inter',sans-serif;color:<?= $tp['text'] ?>;background:radial-gradient(1000px 600px at 20% 0%, <?= $tp['a1'] ?>38, transparent 55%),radial-gradient(900px 500px at 100% 100%, <?= $tp['glow2'] ?>24, transparent 55%),<?= $tp['bg0'] ?>;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;}
.orb{position:absolute;border-radius:50%;filter:blur(70px);opacity:0.5;pointer-events:none;}
.orb-1{width:340px;height:340px;background:<?= $tp['a1'] ?>;top:-100px;left:-80px;}
.orb-2{width:280px;height:280px;background:<?= $tp['glow2'] ?>;bottom:-80px;right:-60px;}
.wrap{position:relative;z-index:2;width:380px;padding:0 20px;}
.badge-row{display:flex;justify-content:center;margin-bottom:20px;}
.logo-3d{width:60px;height:60px;border-radius:18px;background:linear-gradient(135deg,<?= $tp['a1'] ?>,<?= $tp['a2'] ?>,<?= $tp['a3'] ?>);display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 20px 40px -12px <?= $tp['a1'] ?>a6, inset 0 2px 0 rgba(255,255,255,0.4);transform:perspective(400px) rotateX(6deg);}
.card{background:linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0) 40%), <?= $tp['card'] ?>;border:1px solid <?= $tp['border'] ?>;border-radius:22px;padding:34px 32px 28px;box-shadow:0 30px 60px -20px rgba(0,0,0,0.7);}
h1{margin:0 0 4px;font-size:20px;font-weight:800;text-align:center;}
p.sub{color:<?= $tp['textDim'] ?>;margin:0 0 22px;font-size:13px;text-align:center;}
.field{margin-bottom:14px;}
.field label{display:block;font-size:11.5px;color:<?= $tp['textDim'] ?>;margin-bottom:6px;font-weight:600;}
.input-icon{position:relative;}
.input-icon svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:<?= $tp['textFaint'] ?>;pointer-events:none;}
.input-icon input{width:100%;padding:11px 14px 11px 40px;border-radius:11px;border:1px solid <?= $tp['border'] ?>;background:<?= $tp['inputBg'] ?>;color:<?= $tp['text'] ?>;font-size:13.5px;outline:none;}
.input-icon input:focus{border-color:<?= $tp['a1'] ?>;box-shadow:0 0 0 3px <?= $tp['a1'] ?>2e;}
button.btn-primary{width:100%;margin-top:6px;padding:12px;border:0;border-radius:12px;background:linear-gradient(135deg,<?= $tp['a1'] ?>,<?= $tp['a2'] ?>);color:#fff;font-weight:700;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 10px 26px -6px <?= $tp['a1'] ?>99;}
.err{display:flex;align-items:center;gap:9px;background:rgba(251,113,133,0.09);border:1px solid rgba(251,113,133,0.3);color:#fda4af;padding:10px 13px;border-radius:11px;font-size:12.5px;margin-bottom:14px;}
.ok{display:flex;align-items:center;gap:9px;background:rgba(52,211,153,0.09);border:1px solid rgba(52,211,153,0.3);color:#86efac;padding:10px 13px;border-radius:11px;font-size:12.5px;margin-bottom:14px;}
.foot{margin-top:18px;text-align:center;}
.foot a{color:<?= $tp['a3'] ?>;font-size:12.5px;text-decoration:none;font-weight:600;}
@keyframes ghost-eye-pulse{0%,100%{opacity:1;}50%{opacity:.3;}}
.ghost-eye{animation:ghost-eye-pulse 2.6s ease-in-out infinite;}
.logo-3d svg{filter:drop-shadow(0 0 6px rgba(255,255,255,0.5));}
</style>
<script src="/assets/ghost-fx.js" defer></script>
</head>
<body data-ghost-color="<?= e($tp['a1']) ?>">
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
    <div class="badge-row"><div class="logo-3d"><?= icon('lock', 26) ?></div></div>
    <div class="card">
      <h1>Reset Password</h1>
      <p class="sub">We'll send a one-time code to your Telegram</p>

      <?php if ($error): ?><div class="err"><?= icon('alert', 16) ?><span><?= e($error) ?></span></div><?php endif; ?>
      <?php if ($success): ?><div class="ok"><?= icon('check-circle', 16) ?><span><?= e($success) ?></span></div><?php endif; ?>

      <?php if ($step === 'request'): ?>
        <form method="POST">
          <input type="hidden" name="step" value="request">
          <div class="field"><label>Username</label>
            <div class="input-icon"><?= icon('user', 16) ?><input type="text" name="username" required></div>
          </div>
          <button type="submit" class="btn-primary">Send OTP <?= icon('arrow-right', 16) ?></button>
        </form>
      <?php elseif ($step === 'verify'): ?>
        <form method="POST">
          <input type="hidden" name="step" value="verify">
          <div class="field"><label>Username</label>
            <div class="input-icon"><?= icon('user', 16) ?><input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>"></div>
          </div>
          <div class="field"><label>OTP Code</label>
            <div class="input-icon"><?= icon('shield', 16) ?><input type="text" name="otp" required></div>
          </div>
          <div class="field"><label>New Password</label>
            <div class="input-icon"><?= icon('lock', 16) ?><input type="password" name="new_password" required></div>
          </div>
          <button type="submit" class="btn-primary">Reset Password <?= icon('arrow-right', 16) ?></button>
        </form>
      <?php endif; ?>
      <div class="foot"><a href="/login">Back to login</a></div>
    </div>
  </div>
</body>
</html>
