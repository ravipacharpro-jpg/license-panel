// particles + tilt + sidebar + helpers
(function(){
  // ---- Particles (lightweight canvas) ----
  function initParticles(){
    const c = document.getElementById('particles');
    if(!c) return;
    const ctx = c.getContext('2d');
    let w,h,pts=[];
    function resize(){ w=c.width=c.offsetWidth; h=c.height=c.offsetHeight; }
    resize(); window.addEventListener('resize', resize);
    const N = Math.min(90, Math.floor(window.innerWidth/14));
    for(let i=0;i<N;i++) pts.push({x:Math.random()*2000,y:Math.random()*1200,r:Math.random()*2+0.6,s:Math.random()*0.6+0.2,o:Math.random()*0.7+0.2, hue: Math.random()>0.5?'139,92,246':'34,211,238'});
    function tick(){
      ctx.clearRect(0,0,w,h);
      // resize guard
      if(c.offsetWidth!==w||c.offsetHeight!==h) resize();
      for(const p of pts){
        p.y-=p.s; p.x+=Math.sin((p.y+p.r*40)/90)*0.25;
        if(p.y<-10){p.y=h+10;p.x=Math.random()*w;}
        ctx.beginPath();
        ctx.arc(p.x%w,p.y,p.r,0,Math.PI*2);
        ctx.fillStyle=`rgba(${p.hue},${p.o})`;
        ctx.shadowBlur=12; ctx.shadowColor=`rgba(${p.hue},0.8)`;
        ctx.fill(); ctx.shadowBlur=0;
      }
      requestAnimationFrame(tick);
    }
    tick();
  }

  // ---- Subtle 3D tilt on .tilt ----
  function initTilt(){
    if(window.matchMedia('(pointer:coarse)').matches) return;
    document.querySelectorAll('.tilt').forEach(el=>{
      el.addEventListener('mousemove', ev=>{
        const r=el.getBoundingClientRect();
        const x=(ev.clientX-r.left)/r.width-0.5;
        const y=(ev.clientY-r.top)/r.height-0.5;
        el.style.transform=`perspective(900px) rotateX(${-y*7}deg) rotateY(${x*7}deg) translateY(-2px)`;
      });
      el.addEventListener('mouseleave', ()=>{ el.style.transform=''; });
    });
  }

  // ---- Sidebar ----
  window.toggleSidebar = function(){
    document.getElementById('sidebar')?.classList.toggle('open');
  };

  // ---- Tabs (dashboard) ----
  window.showTab = function(id, btn){
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.add('hidden'));
    document.getElementById('tab-'+id)?.classList.remove('hidden');
    document.querySelectorAll('.sidebar-link').forEach(b=>b.classList.remove('active'));
    if(btn) btn.classList.add('active');
    document.getElementById('sidebar')?.classList.remove('open');
    window.scrollTo({top:0, behavior:'smooth'});
  };

  // ---- Copy ----
  window.copyText = async function(t, msg){
    try{ await navigator.clipboard.writeText(t); toast(msg||'Copied!'); }
    catch(e){
      const ta=document.createElement('textarea'); ta.value=t; document.body.appendChild(ta);
      ta.select(); try{document.execCommand('copy'); toast(msg||'Copied!');}catch(_){}
      ta.remove();
    }
  };

  window.toast = function(msg){
    let box=document.getElementById('toast-box');
    if(!box){ box=document.createElement('div'); box.id='toast-box'; box.className='toast flex flex-col gap-2'; document.body.appendChild(box); }
    const d=document.createElement('div');
    d.className='glass px-4 py-3 text-sm';
    d.textContent=msg;
    box.appendChild(d);
    setTimeout(()=>{ d.style.opacity='0'; d.style.transition='.4s'; setTimeout(()=>d.remove(),400); },2600);
  };

  document.addEventListener('DOMContentLoaded', ()=>{ initParticles(); initTilt(); });
})();
