<?php
// Layout/Ghost.php - Ghost theme system (3 variants) + ghost click FX
// Variants: nebula (Ghost Dark/Violet, default), azure (Ghost Blue), light (Ghost Light)
// Switcher UI lives in Layout/Header (theme-switch-btn). Persists via ghost_theme cookie.
$g = $_COOKIE['ghost_theme'] ?? 'nebula';
if (!in_array($g, ['nebula', 'azure', 'light'])) $g = 'nebula';
?>
<style>
:root{
  --ghost-accent:#A855F7;
  --ghost-accent2:#7C3AED;
  --ghost-glow:rgba(168,85,247,.45);
  --accent-3:#A855F7; /* ghost-fx pop color reads this */
  --ghost-nav:rgba(48,24,92,.82);
  --ghost-footer:rgba(93,63,159,.7);
}
html[data-theme="azure"]{
  --ghost-accent:#60A5FA;
  --ghost-accent2:#2563EB;
  --ghost-glow:rgba(96,165,250,.45);
  --accent-3:#60A5FA;
  --ghost-nav:rgba(18,32,96,.85);
  --ghost-footer:rgba(30,58,138,.7);
}
html[data-theme="azure"] body::after{
  background:radial-gradient(circle, rgba(37,99,235,.28) 0%, rgba(10,18,60,.72) 100%) !important;
}
html[data-theme="light"]{
  --ghost-accent:#9333EA;
  --ghost-accent2:#7C3AED;
  --ghost-glow:rgba(147,51,234,.35);
  --accent-3:#9333EA;
  --ghost-nav:rgba(255,255,255,.92);
  --ghost-footer:rgba(124,58,237,.85);
}
html[data-theme="light"] body{background-color:#F1EDFA !important;}
html[data-theme="light"] body::before{opacity:.18;}
html[data-theme="light"] .custom-navbar{box-shadow:0 4px 15px rgba(124,58,237,.18);}
html[data-theme="light"] .navbar-brand,
html[data-theme="light"] .nav-link{color:#2A2140 !important;}
html[data-theme="light"] footer{color:#fff !important;}

/* Navbar + footer follow the active ghost theme */
.custom-navbar{background:var(--ghost-nav) !important;}
footer{background-color:var(--ghost-footer) !important;}

/* Primary buttons + links follow accent */
.btn-primary{background-color:var(--ghost-accent2) !important;border-color:var(--ghost-accent2) !important;}
.btn-primary:hover{filter:brightness(1.12);}
.btn-outline-primary{color:var(--ghost-accent) !important;border-color:var(--ghost-accent) !important;}
.btn-outline-primary:hover{background-color:var(--ghost-accent) !important;color:#fff !important;}
.text-primary{color:var(--ghost-accent) !important;}
.bg-primary{background-color:var(--ghost-accent2) !important;}
a{color:var(--ghost-accent);}
.dropdown-item:active{background-color:var(--ghost-accent2) !important;}

/* Ghost watermark (subtle, non-interactive) */
.ghost-watermark{position:fixed;right:-70px;bottom:-50px;width:420px;height:420px;opacity:.05;pointer-events:none;z-index:0;color:var(--ghost-accent);}
.ghost-watermark svg{width:100%;height:100%;filter:drop-shadow(0 0 18px var(--ghost-glow));}

/* Theme switcher (ghost glass style) */
.theme-switch{position:relative;}
.theme-switch-btn{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;transition:.15s;}
.theme-switch-btn:hover{border-color:var(--ghost-accent);box-shadow:0 0 0 4px var(--ghost-glow);}
.theme-dropdown{position:fixed;top:48px;right:8px;z-index:1060;background:rgba(30,18,55,.98);border:1px solid rgba(255,255,255,.15);border-radius:14px;padding:6px;min-width:190px;box-shadow:0 20px 40px -12px rgba(0,0,0,.7);display:none;flex-direction:column;gap:2px;pointer-events:auto;backdrop-filter:blur(12px);}
.theme-dropdown.open{display:flex;}
.theme-option{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;cursor:pointer;font-size:13px;color:rgba(255,255,255,.85);pointer-events:auto;user-select:none;}
.theme-option:hover{background:rgba(255,255,255,.1);color:#fff;}
.theme-option.active{color:#fff;background:rgba(255,255,255,.1);}
.theme-dot{width:18px;height:18px;border-radius:50%;flex-shrink:0;position:relative;box-shadow:inset 0 0 0 1.5px currentColor, 0 0 8px -1px currentColor;}
.theme-dot::after{content:"";position:absolute;inset:4px;border-radius:50%;background:currentColor;opacity:.3;}
.theme-dot.dot-nebula{color:#A855F7;}
.theme-dot.dot-azure{color:#60A5FA;}
.theme-dot.dot-light{color:#E9D5FF;}
.theme-option.active .theme-dot{box-shadow:inset 0 0 0 2px currentColor, 0 0 12px 0 currentColor;}
.theme-check{margin-left:auto;color:var(--ghost-accent);opacity:0;}
.theme-option.active .theme-check{opacity:1;}
</style>
<script src="<?= base_url('assets/ghost-fx.js') ?>" defer></script>
<script>
function setGhostTheme(name) {
  document.cookie = 'ghost_theme=' + name + '; path=/; max-age=31536000';
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
</script>
