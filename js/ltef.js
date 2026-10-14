/* Live to Eat Food brand book: small enhancements only. Everything works without this file. */
(function () {
  // Copy text, with a fallback when the Clipboard API is missing or refused
  // (plain http, an embedded frame, or a denied permission).
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text).catch(function () { return legacyCopy(text); });
    }
    return legacyCopy(text);
  }
  function legacyCopy(text) {
    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = text; ta.setAttribute('readonly', '');
      ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      var ok = false;
      try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
      document.body.removeChild(ta);
      ok ? resolve() : reject(new Error('copy failed'));
    });
  }
  // One polite status region so copy results are announced, not just shown.
  var status = document.createElement('p');
  status.className = 'vh'; status.setAttribute('role', 'status');
  document.body.appendChild(status);
  function announce(msg) { status.textContent = ''; setTimeout(function () { status.textContent = msg; }, 50); }
  // Copy a hex value from a swatch
  document.querySelectorAll('[data-copy]').forEach(function (el) {
    var timer;
    el.addEventListener('click', function () {
      var v = el.getAttribute('data-copy');
      var live = el.querySelector('.copied');
      copyText(v).then(function () {
        if (live) live.textContent = 'Copied ' + v;
      }, function () {
        if (live) live.textContent = 'Couldn\u2019t copy. The value is ' + v;
      }).then(function () {
        el.classList.add('done');
        clearTimeout(timer);
        timer = setTimeout(function () { el.classList.remove('done'); }, 1800);
      });
    });
  });
  // Copy a code block
  document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
    var label = btn.textContent, timer;
    btn.addEventListener('click', function () {
      var t = document.getElementById(btn.getAttribute('data-copy-target'));
      if (!t) return;
      copyText(t.textContent).then(function () {
        btn.textContent = 'Copied'; announce('Copied ' + label.replace(/^Copy /, ''));
      }, function () {
        btn.textContent = 'Couldn\u2019t copy. The code is selected, so copy it from your keyboard';
        announce(btn.textContent);
        var r = document.createRange(); r.selectNodeContents(t);
        var sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(r);
      }).then(function () {
        clearTimeout(timer);
        timer = setTimeout(function () { btn.textContent = label; }, 2200);
      });
    });
  });
  // Restaurant Roulette demo
  var wheel = document.querySelector('[data-wheel]');
  var spin = document.querySelector('[data-spin]');
  var out = document.querySelector('[data-result]');
  if (wheel && spin && out) {
    var picks = [];
    try { picks = JSON.parse(spin.getAttribute('data-picks') || '[]'); } catch (e) { picks = []; }
    var label = spin.innerHTML, turn = 0, last = -1, busy = false;
    if (!picks.length) { spin.disabled = true; out.textContent = 'No places to spin right now.'; }
    spin.addEventListener('click', function () {
      if (busy || !picks.length) return;
      busy = true;
      spin.disabled = true; spin.setAttribute('aria-busy', 'true');
      var i; do { i = Math.floor(Math.random() * picks.length); } while (i === last && picks.length > 1);
      last = i;
      turn += 1440 + Math.floor(Math.random() * 360);
      wheel.style.transform = 'rotate(' + turn + 'deg)';
      out.textContent = 'Spinning\u2026';
      var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      setTimeout(function () {
        out.innerHTML = picks[i];
        busy = false;
        spin.disabled = false; spin.removeAttribute('aria-busy');
        spin.innerHTML = label;
      }, reduce ? 0 : 3000);
    });
  }
  // Highlight the current section in the brand book contents
  var links = document.querySelectorAll('.toc a[href^="#"]');
  if (links.length && 'IntersectionObserver' in window) {
    var map = {};
    links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          links.forEach(function (a) { a.classList.remove('on'); a.removeAttribute('aria-current'); });
          var a = map[e.target.id]; if (a) { a.classList.add('on'); a.setAttribute('aria-current', 'true'); }
        }
      });
    }, { rootMargin: '-40% 0px -55% 0px' });
    Object.keys(map).forEach(function (id) { var s = document.getElementById(id); if (s) io.observe(s); });
  }
})();
