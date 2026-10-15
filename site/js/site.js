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
    if (b.hasAttribute('data-mood') || b.hasAttribute('data-with') || b.matches('[data-filter-area],[data-filter-tina]')) return;
    b.addEventListener('click', function () {
      var on = b.getAttribute('aria-pressed') !== 'true';
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
      // Sample filters on demo pages: say so instead of silently filtering nothing.
      if (b.classList.contains('ltef-chip') && b.closest('[role="group"]')) say('On the full site this filters the list. ' + DEMO);
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
    if (b.hasAttribute('aria-pressed') || b.matches('[data-spin],[data-widen],[data-reset],[data-filter-clear],[data-more],[data-empty-fix],.tt-play') ||
        b.hasAttribute('aria-haspopup') || b.closest('.demo-bar')) return;
    b.addEventListener('click', function () { if (!b.hasAttribute('aria-pressed')) say(DEMO); });
  });
  document.querySelectorAll('button[aria-haspopup]').forEach(function (b) {
    b.addEventListener('click', function () { say('On the full site this opens a list to choose from. ' + DEMO); });
  });
  document.querySelectorAll('a[href="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) { e.preventDefault(); say('On the full site this opens maps, the phone or the menu. ' + DEMO); });
  });

  // Menus: <details> work without JavaScript; this closes them on Escape, outside clicks and when another opens.
  var menus = document.querySelectorAll('.site-more, .site-drawer');
  menus.forEach(function (d) {
    d.addEventListener('toggle', function () {
      if (d.open) menus.forEach(function (o) { if (o !== d) o.open = false; });
    });
  });
  document.addEventListener('click', function (e) {
    menus.forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    menus.forEach(function (d) { if (d.open) { d.open = false; var s = d.querySelector('summary'); if (s) s.focus(); } });
  });

  // Tina's TikToks: a still until pressed, then TikTok's own player (views and credit stay with her).
  document.querySelectorAll('.tt').forEach(function (box) {
    var btn = box.querySelector('.tt-play');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var id = box.getAttribute('data-tt');
      if (!id) return;
      var f = document.createElement('iframe');
      f.src = 'https://www.tiktok.com/player/v1/' + id + '?music_info=0&description=0&rel=0&native_context_menu=0&closed_caption=1&autoplay=1';
      f.title = box.getAttribute('data-title') || 'Tina’s TikTok';
      f.setAttribute('allow', 'autoplay; fullscreen; encrypted-media; picture-in-picture');
      f.setAttribute('allowfullscreen', '');
      f.className = 'tt-frame';
      box.classList.add('is-playing');
      box.appendChild(f);
      f.focus();
    });
  });

  // Woo-pons: "Get the Woo-pon" opens THAT ticket at the counter, never a fixed sample.
  var counter = document.querySelector('[data-counter]');
  if (counter) {
    var cf = function (k) { return counter.querySelector('[data-c="' + k + '"]'); };
    document.querySelectorAll('.ltef-coupon a[href="#counter"]').forEach(function (a) {
      a.addEventListener('click', function () {
        var t = a.closest('.ltef-coupon'); if (!t) return;
        var q = function (sel) { var el = t.querySelector(sel); return el ? el.textContent.trim() : ''; };
        if (cf('off')) cf('off').textContent = q('.ltef-coupon__off');
        if (cf('where')) cf('where').textContent = q('.ltef-coupon__where');
        var code = t.querySelector('.ltef-coupon__code b'); if (cf('code') && code) cf('code').textContent = code.textContent;
        var terms = t.querySelector('.ltef-coupon__terms'); if (cf('terms') && terms) cf('terms').innerHTML = terms.innerHTML;
        if (cf('fine')) cf('fine').textContent = q('.ltef-coupon__fine').replace(/\s*·\s*Show the code before you pay$/, '');
        var img = t.querySelector('img'); if (cf('img') && img) cf('img').src = img.src;
        var d = new Date();
        if (cf('valid')) cf('valid').textContent = 'Valid today, ' + d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }).replace(',', '');
        if (cf('cap')) cf('cap').textContent = 'Sample code. This is the ticket you opened.';
        setTimeout(function () { counter.focus({ preventScroll: true }); }, 0);
      });
    });
  }

  // Home: the craving sentence hands off to Explore, with a live count.
  var ask = document.querySelector('[data-home-ask]'), go = document.querySelector('[data-home-go]');
  if (ask && go) {
    var counts = {}; try { counts = JSON.parse(ask.getAttribute('data-counts')); } catch (err) {}
    var upd = function () {
      var c = ask.elements.craving.value, a = ask.elements.area.value, n = counts[c + '|' + a];
      var q = new URLSearchParams(); if (c) q.set('craving', c); if (a) q.set('area', a);
      go.href = 'explore.html' + (q.toString() ? '?' + q : '');
      var t = go.querySelector('[data-home-count]');
      if (t) t.textContent = n === 0 ? 'None yet: see options' : 'Show me ' + n + (n === 1 ? ' place' : ' places');
    };
    ask.addEventListener('change', upd); ask.addEventListener('submit', function (e) { e.preventDefault(); location.href = go.href; }); upd();
  }

  // Explore: filter the real list by craving, company, neighborhood and "Tina's been".
  var list = document.querySelectorAll('.place-row[data-area]');
  if (list.length) {
    var form = document.querySelector('[data-explore-form]');
    var crav = document.getElementById('f-craving'), withSel = document.getElementById('f-with'), areaSel = document.getElementById('f-area');
    var PAGE = 12, limit = PAGE, more = document.querySelector('[data-more]'), qIn = document.getElementById('f-q');
    var norm = function (t) { return (t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[’']/g, '').trim(); };
    var nameOk = function (r, q) { if (!q) return true; var n = norm(r.getAttribute('data-name')); return norm(q).split(/\s+/).every(function (w) { return n.indexOf(w) > -1; }); };
    var areaBtns = document.querySelectorAll('[data-filter-area]'), tinaBtn = document.querySelector('[data-filter-tina]');
    var countEl = document.querySelector('[data-count]'), note = document.querySelector('[data-count-note]'), empty = document.querySelector('[data-empty]');
    var bubbles = document.querySelectorAll('[data-map-area]');
    var params = new URLSearchParams(location.search);
    var st = { q: params.get('q') || '', area: params.get('area') || '', craving: params.get('craving') || '', with: params.get('with') || 'anyone', tina: params.get('tina') === '1' };
    function apply(push) {
      if (crav) crav.value = st.craving; if (withSel) withSel.value = st.with; if (areaSel) areaSel.value = st.area; if (qIn && qIn.value !== st.q) qIn.value = st.q;
      areaBtns.forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-filter-area') === st.area ? 'true' : 'false'); });
      if (tinaBtn) tinaBtn.setAttribute('aria-pressed', st.tina ? 'true' : 'false');
      var n = 0, per = {};
      list.forEach(function (r) {
        var rest = nameOk(r, st.q) && (!st.craving || (' ' + r.getAttribute('data-cravings') + ' ').indexOf(' ' + st.craving + ' ') > -1) &&
          (st.with === 'anyone' || (' ' + r.getAttribute('data-with') + ' ').indexOf(' ' + st.with + ' ') > -1) &&
          (!st.tina || r.getAttribute('data-tina') === '1');
        var ar = r.getAttribute('data-area'), ok = rest && (!st.area || ar === st.area);
        if (rest) per[ar] = (per[ar] || 0) + 1;
        if (ok) n++;
        r.hidden = !ok || n > limit;
      });
      // Bubbles count what the other filters leave in each neighborhood.
      bubbles.forEach(function (b) {
        var id = b.getAttribute('data-map-area'), c = per[id] || 0, t = b.querySelector('[data-map-count]');
        if (t) t.textContent = c;
        b.classList.toggle('is-on', id === st.area); b.classList.toggle('is-empty', !c);
        b.setAttribute('aria-current', id === st.area ? 'true' : 'false');
        var nm = (b.getAttribute('aria-label') || '').split(':')[0];
        b.setAttribute('aria-label', nm + ': ' + c + (c === 1 ? ' place' : ' places') + (id === st.area ? ', showing' : ''));
      });
      if (countEl) countEl.textContent = n;
      var cw = document.querySelector('[data-count-word]'); if (cw) cw.textContent = n === 1 ? 'place' : 'places';
      if (note) note.textContent = n === list.length ? 'Tina’s newest first' : 'Showing ' + n + ' of ' + list.length;
      if (empty) empty.hidden = n !== 0;
      if (more) { var left = n - limit; more.hidden = left <= 0; more.textContent = 'Show ' + Math.min(PAGE, left) + ' more of ' + left; }
      // A paid card never stands in for results: it shows only beside at least four picks.
      var shownRows = [].filter.call(list, function (r) { return !r.hidden; });
      document.querySelectorAll('.ltef-slot--feed').forEach(function (f) {
        f.hidden = n < 4;
        if (shownRows[3] && shownRows[3].nextElementSibling !== f) shownRows[3].after(f);  // always after the fourth pick on screen
      });
      if (n === 0) explainEmpty();
      if (push && history.replaceState) {
        var q = new URLSearchParams();
        if (st.q) q.set('q', st.q); if (st.area) q.set('area', st.area); if (st.craving) q.set('craving', st.craving);
        if (st.with !== 'anyone') q.set('with', st.with); if (st.tina) q.set('tina', '1');
        history.replaceState(null, '', location.pathname + (q.toString() ? '?' + q : ''));
      }
    }
    // Name what's on, and offer the one change that brings the most places back.
    function count(o) {
      var c = 0;
      list.forEach(function (r) {
        if (nameOk(r, o.q) && (!o.area || r.getAttribute('data-area') === o.area) &&
          (!o.craving || (' ' + r.getAttribute('data-cravings') + ' ').indexOf(' ' + o.craving + ' ') > -1) &&
          (o.with === 'anyone' || (' ' + r.getAttribute('data-with') + ' ').indexOf(' ' + o.with + ' ') > -1) &&
          (!o.tina || r.getAttribute('data-tina') === '1')) c++;
      });
      return c;
    }
    function optText(sel) { return sel && sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : ''; }
    function explainEmpty() {
      var what = document.querySelector('[data-empty-what]'), hint = document.querySelector('[data-empty-hint]'), fix = document.querySelector('[data-empty-fix]');
      var on = [], drops = [];
      if (st.q) { on.push('“' + st.q + '”'); drops.push(['q', '', 'with any name']); }
      if (st.craving) { on.push(optText(crav)); drops.push(['craving', '', 'without ' + optText(crav)]); }
      if (st.with !== 'anyone') { on.push('with ' + optText(withSel)); drops.push(['with', 'anyone', 'for anyone']); }
      if (st.area) { on.push('in ' + optText(areaSel)); drops.push(['area', '', 'everywhere']); }
      if (st.tina) { on.push('only where Tina’s been'); drops.push(['tina', false, 'including places she hasn’t been']); }
      var best = null;
      drops.forEach(function (d) { var o = Object.assign({}, st); o[d[0]] = d[1]; var c = count(o); if (c && (!best || c > best.c)) best = { k: d[0], v: d[1], t: d[2], c: c }; });
      if (what) what.textContent = 'Nothing matches ' + on.join(', ') + '.';
      if (st.q && !count({ q: st.q, area: '', craving: '', with: 'anyone', tina: false })) {  // the name itself isn't on the list
        best = { k: 'q', v: '', c: list.length, t: '' };
        if (what) what.textContent = 'Nothing on Tina’s list matches “' + st.q + '” yet.';
        if (hint) hint.innerHTML = 'Check the spelling, or <a href="how-it-works.html#suggest">suggest it to Tina</a>. Suggestions are never for sale.';
        if (fix) { fix.hidden = false; fix.textContent = 'Clear the search'; fix.onclick = function () { st.q = ''; limit = PAGE; apply(true); if (qIn) qIn.focus(); }; }
        return;
      }
      if (hint) hint.textContent = best ? 'Go ' + best.t + ' and there ' + (best.c === 1 ? 'is 1 place' : 'are ' + best.c + ' places') + '.' : 'Clear the filters to see every place.';
      if (fix) {
        fix.hidden = !best;
        if (best) { fix.textContent = 'Show ' + (best.c === 1 ? 'it' : 'those ' + best.c); fix.onclick = function () { st[best.k] = best.v; limit = PAGE; apply(true); var h = document.getElementById('h-res'); if (h) { h.setAttribute('tabindex', '-1'); h.focus(); } }; }
      }
    }
    function reset() { limit = PAGE; }
    if (crav) crav.addEventListener('change', function () { st.craving = crav.value; reset(); apply(true); });
    var qt; if (qIn) qIn.addEventListener('input', function () { clearTimeout(qt); qt = setTimeout(function () { st.q = qIn.value.trim(); reset(); apply(true); }, 150); });
    if (areaSel) areaSel.addEventListener('change', function () { st.area = areaSel.value; reset(); apply(true); });
    if (more) more.addEventListener('click', function () {
      var first = limit; limit += PAGE; apply(false);
      var shown = [].filter.call(list, function (r) { return !r.hidden; });
      var next = shown[first]; if (next) { var a = next.querySelector('a[href]'); if (a) a.focus(); }
    });
    if (window.matchMedia && window.matchMedia('(max-width: 720px)').matches) document.querySelectorAll('.map-fold').forEach(function (d) { d.open = false; });
    if (withSel) withSel.addEventListener('change', function () { st.with = withSel.value; reset(); apply(true); });
    areaBtns.forEach(function (b) { b.addEventListener('click', function () { st.area = b.getAttribute('data-filter-area'); apply(true); }); });
    if (tinaBtn) tinaBtn.addEventListener('click', function () { st.tina = !st.tina; reset(); apply(true); });
    bubbles.forEach(function (b) { b.addEventListener('click', function (e) {
      e.preventDefault(); var id = b.getAttribute('data-map-area'); st.area = st.area === id ? '' : id; reset(); apply(true);
      var h = document.getElementById('h-res'); if (h && h.getBoundingClientRect().top < 0) h.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    }); });
    document.querySelectorAll('[data-filter-clear]').forEach(function (b) { b.addEventListener('click', function () { st = { q: '', area: '', craving: '', with: 'anyone', tina: false }; limit = PAGE; apply(true); }); });
    if (form) form.addEventListener('submit', function (e) { e.preventDefault(); });
    apply(false);
  }

  // Restaurant Roulette, on the real places Tina has posted from.
  var spinBtn = document.querySelector('[data-spin]');
  var wheel = document.querySelector('[data-wheel]');
  if (!spinBtn || !wheel || !window.LTEF_PLACES) return;
  var PLACES = window.LTEF_PLACES;
  var st = { mood: 'anything', with: 'anyone', turn: 0, pick: null, spinning: false };
  var timer;
  var label = spinBtn.querySelector('[data-spin-label]');
  var states = {}; document.querySelectorAll('[data-state]').forEach(function (el) { states[el.getAttribute('data-state')] = el; });
  function show(name) { Object.keys(states).forEach(function (k) { states[k].hidden = k !== name; }); }
  function pool() {
    return PLACES.filter(function (p) {
      return (st.mood === 'anything' || p.cravings.indexOf(st.mood) > -1) && (st.with === 'anyone' || p.with.indexOf(st.with) > -1);
    });
  }
  function chipText(attr, val) { var b = document.querySelector('[' + attr + '="' + val + '"]'); return b ? b.textContent.toLowerCase() : val; }
  function filterText() { return (st.mood === 'anything' ? 'anything' : chipText('data-mood', st.mood)) + (st.with === 'anyone' ? '' : ', with ' + chipText('data-with', st.with)); }
  function fill(f, v) { document.querySelectorAll('[data-f="' + f + '"]').forEach(function (el) { el.textContent = v; }); }
  function sync() {
    document.querySelectorAll('[data-mood]').forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-mood') === st.mood ? 'true' : 'false'); });
    document.querySelectorAll('[data-with]').forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-with') === st.with ? 'true' : 'false'); });
    fill('count', String(pool().length));
    fill('adjust', filterText());
  }
  // Phones get the wheel first; the choices fold behind "Adjust" there and stay open on wider screens.
  document.querySelectorAll('.roul-adjust').forEach(function (d) { if (window.matchMedia && window.matchMedia('(max-width: 720px)').matches) d.open = false; });
  function choose(key, val) { if (st.spinning) return; st[key] = val; sync(); if (states.nomatch && !states.nomatch.hidden) show('idle'); }
  document.querySelectorAll('[data-mood]').forEach(function (b) { b.addEventListener('click', function () { choose('mood', b.getAttribute('data-mood')); }); });
  document.querySelectorAll('[data-with]').forEach(function (b) { b.addEventListener('click', function () { choose('with', b.getAttribute('data-with')); }); });
  function spin() {
    if (st.spinning) return;
    var cur = pool();
    fill('filter', filterText());
    if (!cur.length) { show('nomatch'); return; }
    var i = Math.floor(Math.random() * cur.length);
    if (cur.length > 1 && st.pick && cur[i].id === st.pick.id) i = (i + 1) % cur.length;
    st.turn += 1440 + Math.floor(Math.random() * 360);
    wheel.style.transform = 'rotate(' + st.turn + 'deg)';
    // aria-disabled, not disabled: a disabled button drops keyboard focus to <body>.
    st.spinning = true; spinBtn.setAttribute('aria-disabled', 'true'); spinBtn.setAttribute('aria-busy', 'true');
    if (label) label.textContent = 'Spinning…';
    fill('count', String(cur.length)); show('spinning');
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    clearTimeout(timer);
    timer = setTimeout(function () {
      var p = cur[i]; st.pick = p; st.spinning = false;
      spinBtn.removeAttribute('aria-disabled'); spinBtn.removeAttribute('aria-busy');
      if (label) label.textContent = 'Spin again';
      ['name', 'meta', 'month', 'quote', 'date'].forEach(function (k) { fill(k, p[k]); });
      document.querySelectorAll('[data-f="bg"]').forEach(function (el) { el.style.background = p.bg; });
      document.querySelectorAll('[data-f="img"]').forEach(function (el) { el.src = p.img; el.alt = 'Photo slot for ' + p.name; });
      document.querySelectorAll('[data-f="href"]').forEach(function (el) { el.href = p.href; });
      document.querySelectorAll('[data-f="tt"]').forEach(function (el) { el.href = p.url; });
      document.querySelectorAll('[data-hosted]').forEach(function (el) { el.hidden = true; });
      show('pick');
      // Bring the answer into view and hand focus to its name.
      var head = states.pick && states.pick.querySelector('h2');
      if (head) {
        head.setAttribute('tabindex', '-1');
        var r = head.getBoundingClientRect();
        if (r.top < 0 || r.bottom > window.innerHeight) states.pick.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
        head.focus({ preventScroll: true });
      }
    }, reduce ? 0 : 3200);
  }
  document.querySelectorAll('[data-spin]').forEach(function (b) { b.addEventListener('click', spin); });
  var widen = document.querySelector('[data-widen]'); if (widen) widen.addEventListener('click', function () { st.with = 'anyone'; sync(); show('idle'); });
  var reset = document.querySelector('[data-reset]'); if (reset) reset.addEventListener('click', function () { st.mood = 'anything'; st.with = 'anyone'; sync(); show('idle'); });
  sync();
})();
