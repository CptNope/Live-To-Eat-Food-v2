/* Live to Eat Food brand book: small enhancements only. Everything works without this file. */
(function () {
  // Copy a hex value from a swatch
  document.querySelectorAll('[data-copy]').forEach(function (el) {
    el.addEventListener('click', function () {
      var v = el.getAttribute('data-copy');
      if (!navigator.clipboard) return;
      navigator.clipboard.writeText(v).then(function () {
        el.classList.add('done');
        var live = el.querySelector('.copied');
        if (live) live.textContent = 'Copied ' + v;
        setTimeout(function () { el.classList.remove('done'); }, 1400);
      });
    });
  });
  // Copy a code block
  document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var t = document.getElementById(btn.getAttribute('data-copy-target'));
      if (!t || !navigator.clipboard) return;
      navigator.clipboard.writeText(t.textContent).then(function () {
        var old = btn.textContent; btn.textContent = 'Copied';
        setTimeout(function () { btn.textContent = old; }, 1400);
      });
    });
  });
  // Restaurant Roulette demo
  var wheel = document.querySelector('[data-wheel]');
  var spin = document.querySelector('[data-spin]');
  var out = document.querySelector('[data-result]');
  if (wheel && spin && out) {
    var picks = JSON.parse(spin.getAttribute('data-picks'));
    var turn = 0, last = -1;
    spin.addEventListener('click', function () {
      var i; do { i = Math.floor(Math.random() * picks.length); } while (i === last && picks.length > 1);
      last = i;
      turn += 1440 + Math.floor(Math.random() * 360);
      wheel.style.transform = 'rotate(' + turn + 'deg)';
      out.textContent = '';
      var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      setTimeout(function () { out.innerHTML = picks[i]; }, reduce ? 0 : 3000);
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
