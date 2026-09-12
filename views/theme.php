<?php
// views/theme.php - Premium Ghost/Phantom Stylesheet (glassmorphism + 3D depth, icon-first, no emojis)
// 3 Ghost variants: nebula (Ghost Dark/Violet), azure (Ghost Blue), light (Ghost Light)
$mexx_theme = $_COOKIE['mexx_theme'] ?? 'nebula';
if (!in_array($mexx_theme, ['nebula', 'azure', 'light'])) $mexx_theme = 'nebula';
?>
<style>
:root{
  /* ===== Ghost Dark (Violet) — default ===== */
  --bg-0:#060609;
  --bg-1:#0B0A10;
  --panel:#11101A;
  --panel-2:#171323;
  --border:#2A1A40;
  --border-soft:#1C1428;
  --text:#F5F3FF;
  --text-dim:#9893A8;
  --text-faint:#6B657A;
  --success:#22C55E;
  --warning:#fbbf24;
  --danger:#EF4444;
  --shadow-soft:0 1px 0 rgba(255,255,255,0.02) inset, 0 20px 40px -20px rgba(0,0,0,0.75);
  --radius:16px;
  --radius-sm:10px;
  --hover-1:rgba(255,255,255,0.04);
  --hover-2:rgba(255,255,255,0.015);
  --hover-3:rgba(255,255,255,0.05);
  --hover-6:rgba(255,255,255,0.06);
  --grid-line:rgba(255,255,255,0.025);
  --input-bg:#0B0A10;
  --code-bg:#0A0A14;
  --ghost-color:var(--accent-3);
  --ghost-opacity:0.035;
  --scroll-thumb:#2E2440;

  --accent:#9B4DFF;
  --accent-2:#6C2DFF;
  --accent-3:#A855F7;
  --accent-rgb:155,77,255;
  --grad-accent:linear-gradient(135deg,#9B4DFF 0%,#6C2DFF 55%,#A855F7 100%);
  --grad-surface:linear-gradient(180deg,rgba(var(--accent-rgb),0.045),rgba(255,255,255,0) 40%);
  --glow-bg-1:rgba(155,77,255,0.10);
  --glow-bg-2:rgba(108,45,255,0.08);
}

/* ===== Theme: Ghost Blue (Azure) — dark bg, blue accent ===== */
[data-theme="azure"]{
  --accent:#4D8DFF;
  --accent-2:#2D63FF;
  --accent-3:#60A5FA;
  --accent-rgb:77,141,255;
  --grad-accent:linear-gradient(135deg,#4D8DFF 0%,#2D63FF 55%,#60A5FA 100%);
  --glow-bg-1:rgba(77,141,255,0.10);
  --glow-bg-2:rgba(45,99,255,0.08);
  --border:#1A2440;
  --border-soft:#141C33;
}

/* ===== Theme: Ghost Light — light bg, same ghost design ===== */
[data-theme="light"]{
  --bg-0:#F3F1FA;
  --bg-1:#FFFFFF;
  --panel:#FFFFFF;
  --panel-2:#F6F3FC;
  --border:#E3DDF2;
  --border-soft:#ECE7F7;
  --text:#1C1730;
  --text-dim:#655F78;
  --text-faint:#948EA6;
  --success:#16A34A;
  --warning:#D97706;
  --danger:#DC2626;
  --shadow-soft:0 1px 0 rgba(255,255,255,0.6) inset, 0 16px 36px -18px rgba(88,60,150,0.18);
  --hover-1:rgba(88,60,150,0.05);
  --hover-2:rgba(88,60,150,0.03);
  --hover-3:rgba(88,60,150,0.06);
  --hover-6:rgba(88,60,150,0.08);
  --grid-line:rgba(88,60,150,0.05);
  --input-bg:#FBFAFE;
  --code-bg:#F2EEFA;
  --ghost-color:var(--text-faint);
  --ghost-opacity:0.06;
  --scroll-thumb:#D9D1EC;

  --accent:#7C3AED;
  --accent-2:#6D28D9;
  --accent-3:#9333EA;
  --accent-rgb:124,58,237;
  --grad-accent:linear-gradient(135deg,#7C3AED 0%,#6D28D9 55%,#9333EA 100%);
  --grad-surface:linear-gradient(180deg,rgba(124,58,237,0.03),rgba(255,255,255,0) 40%);
  --glow-bg-1:rgba(124,58,237,0.06);
  --glow-bg-2:rgba(147,51,234,0.05);
}

*{box-sizing:border-box;}
body{
  margin:0;
  font-family:'Inter','Segoe UI',system-ui,sans-serif;
  background:
    radial-gradient(1100px 600px at 15% -10%, var(--glow-bg-1), transparent 60%),
    radial-gradient(900px 500px at 110% 10%, var(--glow-bg-2), transparent 55%),
    var(--bg-0);
  color:var(--text);
  -webkit-font-smoothing:antialiased;
  position:relative;
}

/* ===== Ghost silhouette watermark (subtle, non-interactive) ===== */
.ghost-watermark{
  position:fixed;
  right:-60px;
  bottom:-40px;
  width:460px;
  height:460px;
  opacity:var(--ghost-opacity);
  pointer-events:none;
  z-index:0;
  color:var(--ghost-color);
}
.ghost-watermark svg{width:100%;height:100%;}

/* ===== Layout shell ===== */
.layout{display:flex;min-height:100vh;}
.sidebar{
  width:250px;flex-shrink:0;
  background:linear-gradient(180deg, var(--panel) 0%, var(--bg-1) 100%);
  border-right:1px solid var(--border-soft);
  padding:22px 14px;
  position:relative;
}
.sidebar::after{content:"";position:absolute;top:0;right:-1px;width:1px;height:100%;background:linear-gradient(180deg,transparent,rgba(var(--accent-rgb),0.25),transparent);}
.brand{display:flex;align-items:center;gap:10px;padding:6px 10px 26px;}
.brand-mark{
  width:36px;height:36px;border-radius:11px;
  background:var(--grad-accent);
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 6px 18px -4px rgba(var(--accent-rgb),0.6), inset 0 1px 0 rgba(255,255,255,0.35);
  color:#fff;
  flex-shrink:0;
}
.brand-text h2{margin:0;font-size:16px;font-weight:700;letter-spacing:0.2px;}
.brand-text span{font-size:10.5px;color:var(--text-faint);letter-spacing:1.2px;text-transform:uppercase;}

.nav-group-label{font-size:10.5px;color:var(--text-faint);text-transform:uppercase;letter-spacing:1.2px;padding:14px 14px 6px;}
.sidebar a.nav-link{
  display:flex;align-items:center;gap:11px;
  padding:10px 14px;margin-bottom:2px;
  color:var(--text-dim);text-decoration:none;font-size:13.5px;font-weight:500;
  border-radius:10px;
  transition:all .15s ease;
  position:relative;
}
.sidebar a.nav-link svg{opacity:0.75;flex-shrink:0;transition:opacity .15s ease;}
.sidebar a.nav-link:hover{background:var(--hover-1);color:var(--text);}
.sidebar a.nav-link:hover svg{opacity:1;}
.sidebar a.nav-link.active{
  color:#fff;
  background:linear-gradient(90deg, rgba(var(--accent-rgb),0.22), rgba(var(--accent-rgb),0.03));
  box-shadow:inset 2px 0 0 var(--accent);
}
.sidebar a.nav-link.active svg{opacity:1;color:var(--accent-3);}
.sidebar .logout-link{color:var(--danger);opacity:0.85;margin-top:14px;border-top:1px solid var(--border-soft);padding-top:14px;}
.sidebar .logout-link:hover{opacity:1;background:rgba(239,68,68,0.08);}

/* ===== Ghost-styled icons everywhere: soft phantom glow + blinking eyes ===== */
@keyframes ghost-eye-pulse{0%,100%{opacity:1;}50%{opacity:.3;}}
.ghost-eye{animation:ghost-eye-pulse 2.6s ease-in-out infinite;}
.sidebar a.nav-link svg,.stat-icon svg,.icon-wrap svg,.card h3 svg,.brand-mark svg{
  transition:filter .2s ease;
}
.sidebar a.nav-link:hover svg,.sidebar a.nav-link.active svg{filter:drop-shadow(0 0 6px rgba(var(--accent-rgb),0.65));}
.stat-icon svg{filter:drop-shadow(0 0 5px rgba(var(--accent-rgb),0.45));}
.card h3 svg{filter:drop-shadow(0 0 4px rgba(var(--accent-rgb),0.4));}
.brand-mark svg{filter:drop-shadow(0 0 5px rgba(255,255,255,0.5));}

/* ===== Ghost mascot card (sidebar footer) — recolors automatically per theme ===== */
.ghost-card{
  margin-top:16px;
  background:radial-gradient(120px 90px at 50% 0%, rgba(var(--accent-rgb),0.20), transparent 70%), var(--panel-2);
  border:1px solid var(--border);
  border-radius:16px;
  padding:18px 14px 16px;
  text-align:center;
  position:relative;
  overflow:hidden;
}
.ghost-card-art{color:var(--accent-3);display:flex;justify-content:center;margin-bottom:10px;filter:drop-shadow(0 0 14px rgba(var(--accent-rgb),0.55));}
.ghost-card-title{font-size:13px;font-weight:700;color:var(--text);}
.ghost-card-sub{margin-top:4px;font-size:11px;color:var(--text-faint);display:flex;align-items:center;justify-content:center;gap:6px;}
.ghost-status-dot{width:6px;height:6px;border-radius:50%;background:var(--success);box-shadow:0 0 6px var(--success);}

.main{flex:1;padding:28px 36px 60px;max-width:1400px;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;}
.topbar-title h1{font-size:22px;margin:0 0 3px;font-weight:700;}
.topbar-title p{margin:0;font-size:13px;color:var(--text-dim);}
.user-chip{
  display:flex;align-items:center;gap:10px;
  background:var(--panel);border:1px solid var(--border);
  padding:6px 14px 6px 6px;border-radius:30px;
  box-shadow:var(--shadow-soft);
}
.avatar{
  width:32px;height:32px;border-radius:50%;
  background:var(--grad-accent);
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:13px;color:#fff;
  box-shadow:inset 0 1px 0 rgba(255,255,255,0.35);
}
.user-chip .u-name{font-size:13px;font-weight:600;}
.role-pill{
  font-size:10px;font-weight:700;letter-spacing:0.6px;text-transform:uppercase;
  padding:2px 8px;border-radius:20px;
  background:rgba(var(--accent-rgb),0.18);color:var(--accent-2);
  border:1px solid rgba(var(--accent-rgb),0.3);
}

/* ===== Cards ===== */
.card{
  background:var(--grad-surface), var(--panel);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:22px;
  margin-bottom:20px;
  box-shadow:var(--shadow-soft);
  transition:background .18s ease, border-color .18s ease;
  position:relative;
  z-index:1;
}
.card:hover{
  background:var(--grad-surface), var(--panel-2);
  border-color:rgba(var(--accent-rgb),0.35);
}
.card h3{margin:0 0 16px;font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;}
.card h3 svg{color:var(--accent-3);}

.grid{display:flex;gap:16px;flex-wrap:wrap;}
.stat{
  flex:1;min-width:190px;
  background:var(--grad-surface), var(--panel);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:20px;
  position:relative;
  overflow:hidden;
  box-shadow:var(--shadow-soft);
}
.stat::before{
  content:"";position:absolute;top:-40%;right:-30%;width:140px;height:140px;
  background:radial-gradient(circle, rgba(var(--accent-rgb),0.25), transparent 70%);
  pointer-events:none;
}
.stat-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;}
.stat-icon{
  width:38px;height:38px;border-radius:11px;
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg, rgba(var(--accent-rgb),0.28), rgba(var(--accent-rgb),0.08));
  border:1px solid rgba(var(--accent-rgb),0.3);
  color:var(--accent-3);
  box-shadow:inset 0 1px 0 rgba(255,255,255,0.08);
}
.stat .num{font-size:28px;font-weight:800;letter-spacing:-0.5px;}
.stat .lbl{font-size:12px;color:var(--text-dim);margin-top:2px;}

/* ===== Table ===== */
table{width:100%;border-collapse:collapse;font-size:13px;}
th{text-align:left;color:var(--text-faint);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.6px;padding:0 12px 12px;border-bottom:1px solid var(--border-soft);}
td{padding:13px 12px;border-bottom:1px solid var(--border-soft);color:var(--text);}
tr:last-child td{border-bottom:none;}
tr:hover td{background:var(--hover-2);}
code{background:var(--code-bg);border:1px solid var(--border-soft);padding:3px 8px;border-radius:6px;font-size:12px;color:var(--accent-3);font-family:'JetBrains Mono',monospace;}

/* ===== Buttons ===== */
.btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:10px 18px;border-radius:10px;border:0;
  background:var(--grad-accent);
  color:#fff;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;
  box-shadow:0 6px 16px -4px rgba(var(--accent-rgb),0.55), inset 0 1px 0 rgba(255,255,255,0.25);
  transition:transform .12s ease, box-shadow .12s ease;
}
.btn:hover{transform:translateY(-1px);box-shadow:0 10px 22px -4px rgba(var(--accent-rgb),0.7), inset 0 1px 0 rgba(255,255,255,0.3);}
.btn:active{transform:translateY(0);}
.btn-sm{padding:6px 12px;font-size:12px;border-radius:8px;}
.btn-red{background:linear-gradient(135deg,#fb7185,#e11d48);box-shadow:0 6px 16px -4px rgba(225,29,72,0.5);}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--text-dim);box-shadow:none;}
.btn-outline:hover{border-color:var(--accent);color:#fff;}
.btn-icon{padding:8px;border-radius:8px;}

input,select,textarea{
  padding:11px 13px;border-radius:10px;
  border:1px solid var(--border);background:var(--input-bg);color:var(--text);
  font-size:13px;font-family:inherit;
  transition:border-color .15s ease, box-shadow .15s ease;
}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(var(--accent-rgb),0.18);}
label.field-label{display:block;font-size:11.5px;color:var(--text-dim);margin-bottom:6px;font-weight:600;letter-spacing:0.2px;}

.pill{padding:3px 10px;border-radius:20px;font-size:10.5px;font-weight:700;letter-spacing:0.3px;text-transform:uppercase;display:inline-block;}
.pill-active{background:rgba(34,197,94,0.14);color:var(--success);border:1px solid rgba(34,197,94,0.25);}
.pill-unused{background:rgba(var(--accent-rgb),0.14);color:var(--accent-3);border:1px solid rgba(var(--accent-rgb),0.25);}
.pill-expired{background:rgba(251,191,36,0.14);color:var(--warning);border:1px solid rgba(251,191,36,0.25);}
.pill-banned{background:rgba(239,68,68,0.14);color:var(--danger);border:1px solid rgba(239,68,68,0.25);}

.alert-box{
  display:flex;align-items:center;gap:10px;
  padding:13px 16px;border-radius:12px;font-size:13px;margin-bottom:18px;
  border:1px solid;
}
.alert-success{background:rgba(34,197,94,0.08);border-color:rgba(34,197,94,0.3);color:#86EFAC;}
.alert-error{background:rgba(239,68,68,0.08);border-color:rgba(239,68,68,0.3);color:#FCA5A5;}

.icon-wrap{color:var(--accent-3);display:inline-flex;}

::-webkit-scrollbar{width:8px;height:8px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--scroll-thumb);border-radius:8px;}

/* ===== Toast ===== */
#toast-container{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
.toast{
  background:var(--panel-2);border:1px solid var(--border);
  padding:12px 18px;border-radius:12px;font-size:13px;
  box-shadow:0 10px 30px -8px rgba(0,0,0,0.6);
  display:flex;align-items:center;gap:9px;
  animation:toast-in .25s ease;
  color:var(--text);
}
.toast.success{border-color:rgba(34,197,94,0.35);color:#86EFAC;}
@keyframes toast-in{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}

/* ===== Theme Switcher — ghost glass style ===== */
.theme-switch{position:relative;}
.theme-switch-btn{
  width:36px;height:36px;border-radius:50%;
  background:rgba(var(--accent-rgb),0.06);
  backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);
  border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;color:var(--text-dim);
  transition:border-color .15s ease, box-shadow .18s ease, background .18s ease, transform .12s ease;
}
.theme-switch-btn:hover{border-color:var(--accent);color:var(--text);box-shadow:0 0 0 4px rgba(var(--accent-rgb),0.12);}
.theme-switch-btn:active{transform:scale(0.92);}
.theme-dropdown{
  position:absolute;top:44px;right:0;z-index:50;
  background:var(--panel-2);border:1px solid var(--border);
  border-radius:14px;padding:8px;min-width:190px;
  box-shadow:0 20px 40px -12px rgba(0,0,0,0.7);
  display:none;flex-direction:column;gap:2px;
}
.theme-dropdown.open{display:flex;}
.theme-option{
  display:flex;align-items:center;gap:10px;
  padding:9px 10px;border-radius:9px;cursor:pointer;
  font-size:12.5px;color:var(--text-dim);
  transition:background .12s ease;
}
.theme-option:hover{background:var(--hover-3);color:var(--text);}
.theme-option.active{color:var(--text);background:var(--hover-6);}

/* Ghost-glass dot: a translucent ring that glows in the theme's own
   accent colour, instead of a flat filled circle. */
.theme-dot{
  width:18px;height:18px;border-radius:50%;flex-shrink:0;
  background:rgba(255,255,255,0.03);
  backdrop-filter:blur(3px);
  position:relative;
  box-shadow:inset 0 0 0 1.5px currentColor, 0 0 8px -1px currentColor;
}
.theme-dot::after{
  content:"";position:absolute;inset:4px;border-radius:50%;
  background:currentColor;opacity:0.28;
}
.theme-dot.dot-nebula{color:#A855F7;}
.theme-dot.dot-azure{color:#60A5FA;}
.theme-dot.dot-light{color:#9333EA;}
.theme-option.active .theme-dot{box-shadow:inset 0 0 0 2px currentColor, 0 0 12px 0 currentColor;}
.theme-check{margin-left:auto;color:var(--accent-3);opacity:0;}
.theme-option.active .theme-check{opacity:1;}
</style>
<script src="/assets/ghost-fx.js" defer></script>
<script>
function setMexxTheme(name) {
  document.cookie = 'mexx_theme=' + name + '; path=/; max-age=31536000';
  document.documentElement.setAttribute('data-theme', name);
  window.location.reload();
}
function toggleThemeDropdown() {
  document.getElementById('themeDropdown')?.classList.toggle('open');
}
document.addEventListener('click', function(e) {
  const dd = document.getElementById('themeDropdown');
  const btn = document.getElementById('themeSwitchBtn');
  if (dd && !dd.contains(e.target) && e.target !== btn && !btn?.contains(e.target)) {
    dd.classList.remove('open');
  }
});
function copyToClipboard(text, label) {
  navigator.clipboard.writeText(text).then(() => showToast((label || 'Copied') + ' to clipboard'));
}
function showToast(msg) {
  let c = document.getElementById('toast-container');
  if (!c) {
    c = document.createElement('div');
    c.id = 'toast-container';
    document.body.appendChild(c);
  }
  const t = document.createElement('div');
  t.className = 'toast success';
  t.innerHTML = msg;
  c.appendChild(t);
  setTimeout(() => t.remove(), 2500);
}
</script>
