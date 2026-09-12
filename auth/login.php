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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'Banned') {
                $error = 'This account has been suspended.';
            } else {
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['username']    = $user['username'];
                $_SESSION['role']        = $user['role'];
                $_SESSION['tenant_code'] = $user['tenant_code'];
                header("Location: /dashboard");
                exit();
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - <?= e(PANEL_NAME) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{
  margin:0;min-height:100vh;font-family:'Inter',sans-serif;color:<?= $tp['text'] ?>;
  background:
    radial-gradient(1000px 600px at 20% 0%, <?= $tp['a1'] ?>38, transparent 55%),
    radial-gradient(900px 500px at 100% 100%, <?= $tp['glow2'] ?>24, transparent 55%),
    <?= $tp['bg0'] ?>;
  display:flex;align-items:center;justify-content:center;
  position:relative;overflow:hidden;
}
.grid-overlay{
  position:absolute;inset:0;
  background-image:linear-gradient(<?= $tp['gridLine'] ?> 1px, transparent 1px),
                    linear-gradient(90deg, <?= $tp['gridLine'] ?> 1px, transparent 1px);
  background-size:42px 42px;
  mask-image:radial-gradient(ellipse 80% 60% at 50% 30%, #000 40%, transparent 90%);
}
.orb{position:absolute;border-radius:50%;filter:blur(70px);opacity:0.5;pointer-events:none;}
.orb-1{width:340px;height:340px;background:<?= $tp['a1'] ?>;top:-100px;left:-80px;}
.orb-2{width:280px;height:280px;background:<?= $tp['glow2'] ?>;bottom:-80px;right:-60px;}

.wrap{position:relative;z-index:2;width:380px;padding:0 20px;}
.badge-row{display:flex;justify-content:center;align-items:center;gap:14px;margin-bottom:22px;}
.logo-3d{
  width:64px;height:64px;border-radius:20px;
  background:linear-gradient(135deg,<?= $tp['a1'] ?> 0%,<?= $tp['a2'] ?> 55%,<?= $tp['a3'] ?> 100%);
  display:flex;align-items:center;justify-content:center;color:#fff;
  box-shadow:0 20px 40px -12px <?= $tp['a1'] ?>a6, inset 0 2px 0 rgba(255,255,255,0.4), inset 0 -6px 12px rgba(0,0,0,0.25);
  transform:perspective(400px) rotateX(6deg);
}
.theme-picker{display:flex;gap:9px;}
.theme-dot-btn{
  width:26px;height:26px;border-radius:50%;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  background:rgba(255,255,255,0.04);
  backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);
  border:1.5px solid currentColor;
  box-shadow:0 0 0 0 currentColor;
  transition:transform .14s ease, box-shadow .18s ease, background .18s ease;
}
.theme-dot-btn svg{width:13px;height:13px;color:currentColor;opacity:0.85;}
.theme-dot-btn:hover{transform:scale(1.15);box-shadow:0 0 10px 0 currentColor;}
.theme-dot-btn:active{transform:scale(0.94);}
.theme-dot-btn.active{background:rgba(255,255,255,0.09);box-shadow:0 0 12px 1px currentColor;}
.dot-nebula{color:#9B4DFF;}
.dot-azure{color:#4D8DFF;}
.dot-light{color:#7C3AED;}

.card{
  background:linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0) 40%), <?= $tp['card'] ?>;
  border:1px solid <?= $tp['border'] ?>;
  border-radius:22px;
  padding:36px 34px 30px;
  box-shadow:0 1px 0 rgba(255,255,255,0.04) inset, 0 30px 60px -20px rgba(0,0,0,0.7);
}
h1{margin:0 0 4px;font-size:23px;font-weight:800;text-align:center;letter-spacing:-0.3px;}
p.sub{color:<?= $tp['textDim'] ?>;margin:0 0 26px;font-size:13px;text-align:center;}

.field{margin-bottom:16px;}
.field label{display:block;font-size:11.5px;color:<?= $tp['textDim'] ?>;margin-bottom:7px;font-weight:600;letter-spacing:0.2px;}
.input-icon{position:relative;}
.input-icon svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:<?= $tp['textFaint'] ?>;pointer-events:none;}
.input-icon input{
  width:100%;padding:12px 14px 12px 40px;border-radius:11px;
  border:1px solid <?= $tp['border'] ?>;background:<?= $tp['inputBg'] ?>;color:<?= $tp['text'] ?>;font-size:13.5px;
  outline:none;transition:border-color .15s ease, box-shadow .15s ease;
}
.input-icon input:focus{border-color:<?= $tp['a1'] ?>;box-shadow:0 0 0 3px <?= $tp['a1'] ?>2e;}

button.btn-primary{
  width:100%;margin-top:8px;padding:13px;border:0;border-radius:12px;
  background:linear-gradient(135deg,<?= $tp['a1'] ?>,<?= $tp['a2'] ?>);
  color:#fff;font-weight:700;font-size:14px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:8px;
  box-shadow:0 10px 26px -6px <?= $tp['a1'] ?>99, inset 0 1px 0 rgba(255,255,255,0.3);
  transition:transform .12s ease, box-shadow .12s ease;
}
button.btn-primary:hover{transform:translateY(-1px);box-shadow:0 14px 32px -6px <?= $tp['a1'] ?>bf, inset 0 1px 0 rgba(255,255,255,0.35);}

.err{
  display:flex;align-items:center;gap:9px;
  background:rgba(251,113,133,0.09);border:1px solid rgba(251,113,133,0.3);
  color:#fda4af;padding:10px 13px;border-radius:11px;font-size:12.5px;margin-bottom:16px;
}

.foot{margin-top:22px;display:flex;justify-content:space-between;font-size:12.5px;}
.foot a{color:<?= $tp['a3'] ?>;text-decoration:none;font-weight:600;}
.foot a:hover{opacity:0.85;}

.trust-row{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:20px;color:<?= $tp['textFaint'] ?>;font-size:11px;}
@keyframes ghost-eye-pulse{0%,100%{opacity:1;}50%{opacity:.3;}}
.ghost-eye{animation:ghost-eye-pulse 2.6s ease-in-out infinite;}
.logo-3d svg{filter:drop-shadow(0 0 6px rgba(255,255,255,0.5));}
</style>
<script>
function setLoginTheme(name){
  document.cookie = 'mexx_theme=' + name + '; path=/; max-age=31536000';
  window.location.reload();
}
</script>
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
    <div class="badge-row">
      <div class="logo-3d"><?= icon('ghost', 32) ?></div>
    </div>
    <div style="display:flex;justify-content:center;margin-bottom:20px;">
      <div class="theme-picker">
        <div class="theme-dot-btn dot-nebula <?= $mexx_theme === 'nebula' ? 'active' : '' ?>" onclick="setLoginTheme('nebula')" title="Ghost Dark"><?= icon('sparkle', 13) ?></div>
        <div class="theme-dot-btn dot-azure <?= $mexx_theme === 'azure' ? 'active' : '' ?>" onclick="setLoginTheme('azure')" title="Ghost Blue"><?= icon('sparkle', 13) ?></div>
        <div class="theme-dot-btn dot-light <?= $mexx_theme === 'light' ? 'active' : '' ?>" onclick="setLoginTheme('light')" title="Ghost Light"><?= icon('sparkle', 13) ?></div>
      </div>
    </div>
    <div class="card">
      <h1><?= e(PANEL_NAME) ?></h1>
      <p class="sub"><?= e(PANEL_SUBTITLE) ?></p>

      <?php if ($error): ?>
        <div class="err"><?= icon('alert', 16) ?><span><?= e($error) ?></span></div>
      <?php endif; ?>

      <form method="POST">
        <div class="field">
          <label>Username</label>
          <div class="input-icon">
            <?= icon('user', 16) ?>
            <input type="text" name="username" required autofocus placeholder="Enter your username">
          </div>
        </div>
        <div class="field">
          <label>Password</label>
          <div class="input-icon">
            <?= icon('lock', 16) ?>
            <input type="password" name="password" required placeholder="Enter your password">
          </div>
        </div>
        <button type="submit" class="btn-primary">Sign In <?= icon('arrow-right', 16) ?></button>
      </form>

      <div class="foot">
        <a href="/register">Create account</a>
        <a href="/forgot">Forgot password?</a>
      </div>
    </div>
    <div class="trust-row"><?= icon('lock', 12) ?> Secured session — encrypted credentials</div>
  </div>
</body>
</html>
