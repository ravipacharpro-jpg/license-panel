/*
 * MEXX Ghost FX
 * -------------
 * Panel-wide "ghost" click effect (a little phantom that puffs out from
 * every click) + a synthesized "ghost voice" blip that's a bit different
 * every time you click. No audio files needed — everything is generated
 * live with the Web Audio API, so it stays tiny and works offline.
 *
 * Include once (e.g. in <head>) and it wires itself to every button,
 * .btn, theme-switch button and theme dot automatically — no per-page
 * setup needed.
 */
(function () {
  'use strict';

  var SELECTOR = '.btn, button, .theme-dot-btn, .theme-option, .theme-switch-btn, input[type="submit"]';

  /* ---------- inject the CSS for the ripple once ---------- */
  var css = ''
    + '.ghost-fx-pop{position:fixed;width:64px;height:44px;margin-left:-32px;margin-top:-30px;'
    + 'pointer-events:none;z-index:99999;opacity:0;transform:scale(.35) translateY(6px);'
    + 'transition:opacity .55s ease, transform .55s cubic-bezier(.2,.8,.3,1);will-change:opacity,transform;}'
    + '.ghost-fx-pop.run{opacity:.85;transform:scale(1.5) translateY(-34px);}'
    + '.ghost-fx-pop svg{width:100%;height:100%;filter:drop-shadow(0 0 6px currentColor);}';
  var styleTag = document.createElement('style');
  styleTag.textContent = css;
  document.head.appendChild(styleTag);

  var GHOST_PATH = '<svg viewBox="0 0 200 130" fill="none" stroke="currentColor" stroke-width="3" '
    + 'stroke-linecap="round" stroke-linejoin="round">'
    + '<path d="M40 118V55C40 26 63 3 92 3h16c29 0 52 23 52 52v63l-16-14-15 14-15-14-15 14-15-14-15 14-15-14-14 14z"/>'
    + '<circle cx="78" cy="52" r="5.5" fill="currentColor" stroke="none"/>'
    + '<circle cx="122" cy="52" r="5.5" fill="currentColor" stroke="none"/>'
    + '</svg>';

  function ghostColor() {
    var cssVar = getComputedStyle(document.documentElement).getPropertyValue('--accent-3').trim();
    if (cssVar) return cssVar;
    var attr = document.body.getAttribute('data-ghost-color');
    return attr || '#A855F7';
  }

  function popGhost(x, y) {
    var el = document.createElement('div');
    el.className = 'ghost-fx-pop';
    el.style.left = x + 'px';
    el.style.top = y + 'px';
    el.style.color = ghostColor();
    el.innerHTML = GHOST_PATH;
    document.body.appendChild(el);
    requestAnimationFrame(function () {
      el.classList.add('run');
    });
    setTimeout(function () {
      el.remove();
    }, 600);
  }

  /* ---------- ghost "voice" — a few distinct synth presets, picked at random ---------- */
  var audioCtx = null;
  function getCtx() {
    if (!audioCtx) {
      var AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) return null;
      audioCtx = new AC();
    }
    if (audioCtx.state === 'suspended') audioCtx.resume();
    return audioCtx;
  }

  var VOICES = [
    { base: 210, sweep: -70, wobbleHz: 5.5, wobbleDepth: 26, dur: 0.55, type: 'sine' },     // low moan
    { base: 340, sweep: 140, wobbleHz: 9,   wobbleDepth: 50, dur: 0.38, type: 'triangle' }, // playful "boo"
    { base: 160, sweep: -45, wobbleHz: 3.5, wobbleDepth: 16, dur: 0.7,  type: 'sine' },     // deep whisper
    { base: 260, sweep: 65,  wobbleHz: 12,  wobbleDepth: 60, dur: 0.32, type: 'sawtooth' }, // eerie squeak
    { base: 200, sweep: -110,wobbleHz: 6.5, wobbleDepth: 30, dur: 0.6,  type: 'triangle' }  // fading wail
  ];

  function playGhostVoice() {
    var ac = getCtx();
    if (!ac) return;
    try {
      var v = VOICES[Math.floor(Math.random() * VOICES.length)];
      var t0 = ac.currentTime;

      var osc = ac.createOscillator();
      osc.type = v.type;
      osc.frequency.setValueAtTime(v.base, t0);
      osc.frequency.linearRampToValueAtTime(v.base + v.sweep, t0 + v.dur);

      var lfo = ac.createOscillator();
      lfo.frequency.value = v.wobbleHz;
      var lfoGain = ac.createGain();
      lfoGain.gain.value = v.wobbleDepth;
      lfo.connect(lfoGain);
      lfoGain.connect(osc.frequency);

      var filter = ac.createBiquadFilter();
      filter.type = 'lowpass';
      filter.frequency.value = 1400;

      var gain = ac.createGain();
      gain.gain.setValueAtTime(0.0001, t0);
      gain.gain.exponentialRampToValueAtTime(0.05, t0 + 0.035);
      gain.gain.exponentialRampToValueAtTime(0.0001, t0 + v.dur);

      osc.connect(filter);
      filter.connect(gain);
      gain.connect(ac.destination);

      osc.start(t0);
      lfo.start(t0);
      osc.stop(t0 + v.dur + 0.05);
      lfo.stop(t0 + v.dur + 0.05);
    } catch (e) { /* ignore audio errors silently */ }
  }

  document.addEventListener('click', function (e) {
    var el = e.target.closest ? e.target.closest(SELECTOR) : null;
    if (!el) return;
    popGhost(e.clientX, e.clientY);
    playGhostVoice();
  }, true);
})();
