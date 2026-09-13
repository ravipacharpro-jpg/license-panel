<?php
$g = $_COOKIE['ghost_theme'] ?? 'nebula';
if (!in_array($g, ['nebula', 'azure', 'light'])) $g = 'nebula';
?>
<style>
:root{
  --ghost-accent:#A855F7;
  --ghost-accent2:#7C3AED;
  --ghost-glow:rgba(168,85,247,.45);
  --accent-3:#A855F7;
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
html[data-theme="light"] body{background-color:#F1EDLA !important;}
html[data-theme="light"] body::before{opacity:.18;}
html[data-theme="light"] .custom-navbar{box-shadow:0 4px 15px rgba(124,58,237,.18);}
html[data-theme="light"] .navbar-brand,
html[data-theme="light"] .nav-link{color:#2A2140 !important;}
html[data-theme="light"] footer{color:#fff !important;}
.custom-navbar{background:var(--ghost-nav) !important;}
footer{background-color:var(--ghost-footer) !important;}
.btn-primary{background-color:var(--ghost-accent2) !important;border-color:var(--ghost-accent2) !important;}
.btn-primary:hover{filter:brightness(1.12);}
.btn-outline-primary{color:var(--ghost-accent) !important;border-color:var(--ghost-accent) !important;}
.btn-outline-primary:hover{background-color:var(--ghost-accent) !important;color:#fff !important;}
.text-primary{color:var(--ghost-accent) !important;}
.bg-primary{background-color:var(--ghost-accent2) !important;}
a{color:var(--ghost-accent);}
.dropdown-item:active{background-color:var(--ghost-accent2) !important;}
.ghost-watermark{position:fixed;right:-70px;bottom:-50px;width:420px;height:420px;opacity:.05;pointer-events:none;z-index:0;color:var(--ghost-accent);}
.ghost-watermark svg{width:100%;height:100%;filter:drop-shadow(0 0 18px var(--ghost-glow));}
.theme-switch{position:relative;z-index:99998;}
.theme-switch-btn{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;transition:.15s;}
.theme-switch-btn:hover{border-color:var(--ghost-accent);box-shadow:0 0 0 4px var(--ghost-glow);}
.ghost-theme-dropdown{position:fixed!important;top:48px!important;right:8px!important;z-index:99999!important;background:rgba(30,18,55,.98)!important;border:1px solid rgba(255,255,255,.15)!important;border-radius:14px!important;padding:6px!important;min-width:190px!important;box-shadow:0 20px 40px -12px rgba(0,0,0,.7)!important;display:none!important;flex-direction:column!important;gap:2px!important;pointer-events:auto!important;backdrop-filter:blur(12px)!important;}
.ghost-theme-dropdown.open{display:flex!important;}
.ghost-theme-opt{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;cursor:pointer;font-size:13px;color:rgba(255,255,255,.85)!important;pointer-events:auto!important;user-select:none!important;}
.ghost-theme-opt:hover{background:rgba(255,255,255,.1)!important;color:#fff!important;}
.ghost-theme-opt.active{color:#fff!important;background:rgba(255,255,255,.1)!important;}
.ghost-theme-dot{width:18px;height:18px;border-radius:50%;flex-shrink:0;position:relative;box-shadow:inset 0 0 0 1.5px currentColor, 0 0 8px -1px currentColor;}
.ghost-theme-dot::after{content:"";position:absolute;inset:4px;border-radius:50%;background:currentColor;opacity:.3;}
.ghost-theme-dot.dot-nebula{color:#A855F7;}
.ghost-theme-dot.dot-azure{color:#60A5FA;}
.ghost-theme-dot.dot-light{color:#E9D5FF;}
.ghost-theme-opt.active .ghost-theme-dot{box-shadow:inset 0 0 0 2px currentColor, 0 0 12px 0 currentColor;}
.ghost-theme-check{margin-left:auto;color:var(--ghost-accent);opacity:0;}
.ghost-theme-opt.active .ghost-theme-check{opacity:1;}
</style>
<script src="<?= base_url('assets/ghost-fx.js') ?>" defer></script>
<script>
(function(){
  // Inject dropdown directly into body to escape stacking context
  var dd = document.createElement('div');
  dd.id = 'themeDropdown';
  dd.className = 'ghost-theme-dropdown';
  dd.innerHTML =
    '<div class="ghost-theme-opt" data-t="nebula"><span class="ghost-theme-dot dot-nebula"></span> Ghost Dark <span class="ghost-theme-check"><i class="bi bi-check-circle"></i></span></div>'+
    '<div class="ghost-theme-opt" data-t="azure"><span class="ghost-theme-dot dot-azure"></span> Ghost Blue <span class="ghost-theme-check"><i class="bi bi-check-circle"></i></span></div>'+
    '<div class="ghost-theme-opt" data-t="light"><span class="ghost-theme-dot dot-light"></span> Ghost Light <span class="ghost-theme-check"><i class="bi bi-check-circle"></i></span></div>';
  document.body.appendChild(dd);

  function setGhostTheme(name){
    document.cookie='ghost_theme='+name+'; path=/; max-age=31536000';
    document.documentElement.setAttribute('data-theme',name);
    dd.classList.remove('open');
    window.location.reload();
  }
  function toggleThemeDropdown(){ dd.classList.toggle('open'); }
  dd.querySelectorAll('.ghost-theme-opt').forEach(function(o){
    o.addEventListener('click', function(e){
      e.stopPropagation();
      setGhostTheme(o.dataset.t);
    });
  });
  document.addEventListener('click',function(e){
    if(dd && !dd.contains(e.target) && e.target!==document.getElementById('themeSwitchBtn') && !document.getElementById('themeSwitchBtn')?.contains(e.target)){
      dd.classList.remove('open');
    }
  });
})();
</script>