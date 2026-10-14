/* Live to Eat Food example site. Plain JavaScript, no build step.
   Everything readable works without it; this adds the toggles, Roulette and honest
   "nothing was sent" messages, because this is a demo with no backend. */
(function () {
  'use strict';

  // One polite status message, shown as a toast and read by screen readers.
  var toast = document.createElement('p');
  toast.className = 'toast'; toast.setAttribute('role', 'status'); toast.hidden = true;
  document.body.appendChild(toast);
  var toastTimer;
  function say(msg) {
    toast.textContent = msg; toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.hidden = true; }, 3200);
  }
  var DEMO = 'Example site: nothing is saved or sent yet.';

  // Filter chips and toggle buttons (aria-pressed) outside Roulette.
  document.querySelectorAll('button[aria-pressed]').forEach(function (b) {
    if (b.hasAttribute('data-mood') || b.hasAttribute('data-budget')) return;
    b.addEventListener('click', function () {
      var on = b.getAttribute('aria-pressed') !== 'true';
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  });

  // Save buttons: a pressed state, labelled in words.
  document.querySelectorAll('button[aria-label^="Save "]').forEach(function (b) {
    b.setAttribute('aria-pressed', 'false');
    b.addEventListener('click', function () {
      var on = b.getAttribute('aria-pressed') === 'true';
      b.setAttribute('aria-pressed', on ? 'false' : 'true');
      var label = b.lastChild;
      if (label && label.nodeType === 3) label.textContent = on ? 'Save' : 'Saved';
      say(on ? 'Removed from your saved places (on this example site only).' : 'Saved on this example site. Real saving comes with the full build.');
    });
  });

  // Forms: validate in the browser, then say plainly that nothing was sent.
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!f.checkValidity()) { f.reportValidity(); return; }
      say('Looks good. ' + DEMO);
    });
  });

  // Buttons that would need the real backend.
  document.querySelectorAll('button[type="button"]').forEach(function (b) {
    if (b.hasAttribute('aria-pressed') || b.closest('[data-roulette]') || b.matches('[data-spin],[data-widen],[data-reset]') ||
        b.hasAttribute('aria-haspopup') || b.closest('.demo-bar')) return;
    b.addEventListener('click', function () { if (!b.hasAttribute('aria-pressed')) say(DEMO); });
  });
  document.querySelectorAll('button[aria-haspopup]').forEach(function (b) {
    b.addEventListener('click', function () { say('On the full site this opens a list to choose from. ' + DEMO); });
  });
  document.querySelectorAll('a[href="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) { e.preventDefault(); say('On the full site this opens maps, the phone or the menu. ' + DEMO); });
  });

  // Restaurant Roulette.
  var spinBtn = document.querySelector('[data-spin]');
  var wheel = document.querySelector('[data-wheel]');
  if (!spinBtn || !wheel) return;
  var A = '../assets/stickers/';
  var PLACES = [
    { name: 'Juniper Dumpling House', meta: 'Dumplings · Shrewsbury St · $$', month: 'Oct', verdict: 'I’d get this again', img: A + 'dumpling.svg', bg: 'var(--peach)', mood: 'cozy', budget: 'mid', hosted: false, href: 'place.html' },
    { name: 'Little Lemon Noodle Co.', meta: 'Noodles · Park Ave · $$', month: 'Oct', verdict: 'Don’t skip this', img: A + 'noodles.svg', bg: 'var(--pistachio)', mood: 'spicy', budget: 'mid', hosted: false, href: 'place.html' },
    { name: 'Pistachio Pizza Bar', meta: 'Pizza · Canal District · $', month: 'Sep', verdict: 'Bring friends', img: A + 'pizza.svg', bg: 'var(--lemon)', mood: 'group', budget: 'low', hosted: false, href: 'place.html' },
    { name: 'Matcha Taqueria', meta: 'Tacos · Main South · $', month: 'Sep', verdict: 'Don’t skip this', img: A + 'taco.svg', bg: 'var(--pistachio)', mood: 'spicy', budget: 'low', hosted: true, href: 'place-hosted.html' },
    { name: 'Sunny Donut Co.', meta: 'Donuts · Shrewsbury St · $', month: 'Jun', verdict: 'I’d get this again', img: A + 'donut.svg', bg: 'var(--peach)', mood: 'sweet', budget: 'low', hosted: false, href: 'place.html' },
    { name: 'Yolk Coffee Bar', meta: 'Coffee & brunch · Downtown · $$', month: 'Jul', verdict: 'Bring friends', img: A + 'egg.svg', bg: 'var(--lemon)', mood: 'cozy', budget: 'mid', hosted: false, href: 'place.html' }
  ];
  var st = { mood: 'anything', budget: 'any', turn: 0, pick: null, spinning: false };
  var timer;
  var label = spinBtn.querySelector('[data-spin-label]');
  var states = {}; document.querySelectorAll('[data-state]').forEach(function (el) { states[el.getAttribute('data-state')] = el; });
  function show(name) { Object.keys(states).forEach(function (k) { states[k].hidden = k !== name; }); }
  function pool() {
    return PLACES.filter(function (p) {
      return (st.mood === 'anything' || p.mood === st.mood) &&
        (st.budget === 'any' || p.budget === st.budget || (st.budget === 'mid' && p.budget === 'low'));
    });
  }
  function chipText(attr, val) { var b = document.querySelector('[' + attr + '="' + val + '"]'); return b ? b.textContent.toLowerCase() : val; }
  function filterText() { return (st.mood === 'anything' ? 'anything' : chipText('data-mood', st.mood)) + (st.budget === 'any' ? '' : ', ' + chipText('data-budget', st.budget)); }
  function fill(f, v) { document.querySelectorAll('[data-f="' + f + '"]').forEach(function (el) { el.textContent = v; }); }
  function syncChips() {
    document.querySelectorAll('[data-mood]').forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-mood') === st.mood ? 'true' : 'false'); });
    document.querySelectorAll('[data-budget]').forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-budget') === st.budget ? 'true' : 'false'); });
  }
  function choose(key, val) { if (st.spinning) return; st[key] = val; syncChips(); if (states.nomatch && !states.nomatch.hidden) show('idle'); }
  document.querySelectorAll('[data-mood]').forEach(function (b) { b.addEventListener('click', function () { choose('mood', b.getAttribute('data-mood')); }); });
  document.querySelectorAll('[data-budget]').forEach(function (b) { b.addEventListener('click', function () { choose('budget', b.getAttribute('data-budget')); }); });
  function spin() {
    if (st.spinning) return;
    var cur = pool();
    fill('filter', filterText());
    if (!cur.length) { show('nomatch'); return; }
    var i = Math.floor(Math.random() * cur.length);
    if (cur.length > 1 && st.pick && cur[i].name === st.pick.name) i = (i + 1) % cur.length;
    st.turn += 1440 + Math.floor(Math.random() * 360);
    wheel.style.transform = 'rotate(' + st.turn + 'deg)';
    st.spinning = true; spinBtn.disabled = true; spinBtn.setAttribute('aria-busy', 'true');
    if (label) label.textContent = 'Spinning…';
    fill('count', String(cur.length)); show('spinning');
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    clearTimeout(timer);
    timer = setTimeout(function () {
      var p = cur[i]; st.pick = p; st.spinning = false;
      spinBtn.disabled = false; spinBtn.removeAttribute('aria-busy');
      if (label) label.textContent = 'Spin again';
      ['name', 'meta', 'month', 'verdict'].forEach(function (k) { fill(k, p[k]); });
      document.querySelectorAll('[data-f="bg"]').forEach(function (el) { el.style.background = p.bg; });
      document.querySelectorAll('[data-f="img"]').forEach(function (el) { el.src = p.img; el.alt = 'Photo slot for ' + p.name; });
      document.querySelectorAll('[data-f="href"]').forEach(function (el) { el.href = p.href; });
      document.querySelectorAll('[data-hosted]').forEach(function (el) { el.hidden = !p.hosted; });
      show('pick');
    }, reduce ? 0 : 3200);
  }
  document.querySelectorAll('[data-spin]').forEach(function (b) { b.addEventListener('click', spin); });
  var widen = document.querySelector('[data-widen]'); if (widen) widen.addEventListener('click', function () { st.budget = 'any'; syncChips(); show('idle'); });
  var reset = document.querySelector('[data-reset]'); if (reset) reset.addEventListener('click', function () { st.mood = 'anything'; st.budget = 'any'; syncChips(); show('idle'); });
})();
