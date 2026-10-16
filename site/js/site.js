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
    if (b.hasAttribute('data-mood') || b.hasAttribute('data-with') || b.matches('[data-filter-area],[data-filter-tina],[data-deal-filter],[data-job-filter]')) return;
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

  // Monthly Crumbs: an on-brand error for a mistyped email, then the form turns into the answer:
  // you're on the list, the date it lands, and what's inside. (Example site: nothing is saved.)
  var ERR_ICON = '<svg viewBox="0 0 24 24" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5v5.5M12 16.5v.01"/></g></svg>';
  // Monthly Crumbs goes out on the first Sunday of each month: the next one still to come.
  function nextIssue() {
    var now = new Date(), y = now.getFullYear(), m = now.getMonth();
    function firstSunday(yy, mm) { var d = new Date(yy, mm, 1); d.setDate(1 + (7 - d.getDay()) % 7); return d; }
    var d = firstSunday(y, m); if (d <= now) d = firstSunday(m === 11 ? y + 1 : y, (m + 1) % 12);
    return d.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
  }
  document.querySelectorAll('form.ltef-signup').forEach(function (f) {
    var input = f.querySelector('input[type="email"]'); if (!input) return;
    f.setAttribute('novalidate', '');
    var err = document.createElement('p'); err.className = 'ltef-field__err'; err.id = input.id + '-err'; err.hidden = true;
    input.closest('.ltef-field').appendChild(err);
    function clear() { err.hidden = true; input.removeAttribute('aria-invalid'); input.removeAttribute('aria-describedby'); }
    input.addEventListener('input', clear);
    f.addEventListener('submit', function (e) {
      e.preventDefault(); e.stopImmediatePropagation();
      var v = input.value.trim();
      if (!v || !input.checkValidity() || !/\.[a-z]{2,}$/i.test(v)) {
        err.innerHTML = ERR_ICON + '<span>' + (!v ? 'Add your email and Monthly Crumbs comes to it.' : v.indexOf('@') < 0 ? 'That email is missing the @.' : 'That email needs an ending like .com after the @. Check the spelling.') + '</span>';
        err.hidden = false; input.setAttribute('aria-invalid', 'true'); input.setAttribute('aria-describedby', err.id); input.focus();
        return;
      }
      var keep = [].slice.call(f.children);
      keep.forEach(function (c) { c.hidden = true; });  // the heading still names the form (aria-labelledby reads hidden text)
      var done = document.createElement('div'); done.className = 'signup-done'; done.setAttribute('tabindex', '-1'); done.setAttribute('role', 'status');
      done.innerHTML = '<span class="signup-done__stamp" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>' +
        '<div class="signup-done__txt"><p class="signup-done__h">You’re on the list for Monthly Crumbs.</p>' +
        '<p>The next issue lands <strong>' + nextIssue() + '</strong>, in the morning, at <strong></strong>.</p>' +
        '<p class="signup-done__fine">Example site: nothing was saved or sent.</p>' +
        '<div class="signup-done__acts"><a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="email-sample.html">See a sample issue</a><button type="button" class="linkish">Use a different email</button></div></div>';
      done.querySelectorAll('strong')[1].textContent = v;
      f.appendChild(done);
      if (!f.classList.contains('is-done')) { f.classList.add('is-done'); }
      done.querySelector('button').addEventListener('click', function () {
        done.remove(); f.classList.remove('is-done'); keep.forEach(function (c) { c.hidden = false; }); input.focus(); input.select();
      });
      done.focus();
    });
  });

  // Forms: validate in the browser, then say plainly that nothing was sent.
  document.querySelectorAll('form:not(.ltef-signup)').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!f.checkValidity()) { f.reportValidity(); return; }
      say('Looks good. ' + DEMO);
    });
  });

  // Buttons that would need the real backend.
  document.querySelectorAll('button[type="button"]').forEach(function (b) {
    if (b.hasAttribute('aria-pressed') || b.matches('[data-spin],[data-widen],[data-reset],[data-filter-clear],[data-more],[data-empty-fix],[data-used-ask],.tt-play') ||
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

  // Woo-pons: "Show at the counter" opens THAT ticket full screen (above the tab bar), never a fixed sample.
  // Staff confirm before marking it used, and a slip can be undone while it is open.
  // "Used" is remembered for this visit (sessionStorage, so a closed tab forgets it): the page's ticket
  // shows it, and the ticket reopens already used. Undo clears it everywhere.
  var USED_KEY = 'ltef-used';
  function usedMap() { try { return JSON.parse(sessionStorage.getItem(USED_KEY)) || {}; } catch (err) { return window.__ltefUsed || {}; } }
  function saveUsed(m) { window.__ltefUsed = m; try { sessionStorage.setItem(USED_KEY, JSON.stringify(m)); } catch (err) {} }
  function markCard(code) {
    var t = document.querySelector('.ltef-coupon[data-code="' + code + '"]'); if (!t) return;
    var at = usedMap()[code], tag = t.querySelector('.used-tag'), btn = t.querySelector('[data-open-ticket]');
    t.classList.toggle('is-used', !!at);
    if (at && !tag) { tag = document.createElement('span'); tag.className = 'ltef-lbl ltef-lbl--notyet used-tag'; t.insertBefore(tag, t.querySelector('.ltef-coupon__code')); }
    if (tag) { if (at) tag.textContent = 'Used today at ' + at; else tag.remove(); }
    if (btn) btn.lastChild.textContent = at ? 'Show the used ticket' : 'Show at the counter';
    if (btn) btn.setAttribute('aria-label', btn.getAttribute('aria-label').replace(/^(Show at the counter|Show the used ticket)/, at ? 'Show the used ticket' : 'Show at the counter'));
  }
  function wireUsed(root, getCode) {
    var flow = root.querySelector('[data-used-flow]'); if (!flow) return;
    var ask = flow.querySelector('[data-used-ask]'), body = root.querySelector('.ltef-coupon') && root.querySelector('.ltef-coupon').parentNode;
    function code() { return getCode ? getCode() : ''; }
    function showAsk() { flow.innerHTML = ''; flow.appendChild(ask); if (body) body.classList.remove('is-used'); }
    function showDone(at, focus) {
      flow.innerHTML = '<div class="used-flow__done" role="status"><span>Used today at ' + at + '</span><button type="button" data-undo>Undo</button></div>';
      if (body) body.classList.add('is-used');
      flow.querySelector('[data-undo]').addEventListener('click', function () {
        var m = usedMap(); delete m[code()]; saveUsed(m); markCard(code()); showAsk(); ask.focus(); say('Back to unused.');
      });
      if (focus) flow.querySelector('[data-undo]').focus();
    }
    ask.addEventListener('click', function () {
      flow.innerHTML = '<p class="used-flow__ask">Mark this Woo-pon used? Staff only, at the register.</p>' +
        '<div class="used-flow__row"><button class="ltef-btn" type="button" data-yes>Yes, it’s used</button><button class="ltef-btn ltef-btn--alt" type="button" data-no>Not yet</button></div>';
      flow.querySelector('[data-no]').addEventListener('click', function () { showAsk(); ask.focus(); });
      flow.querySelector('[data-yes]').addEventListener('click', function () {
        var at = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        var m = usedMap(); if (code()) { m[code()] = at; saveUsed(m); markCard(code()); }
        showDone(at, true);
        say('Marked as used. ' + DEMO);
      });
      flow.querySelector('[data-yes]').focus();
    });
    var at0 = code() && usedMap()[code()]; if (at0) showDone(at0, false); else showAsk();
  }
  document.querySelectorAll('.ltef-coupon[data-code]').forEach(function (t) { markCard(t.getAttribute('data-code')); });

  // Restaurant jobs: chips filter the sample listings, one at a time.
  var jobChips = document.querySelectorAll('[data-job-filter]');
  jobChips.forEach(function (c) {
    c.addEventListener('click', function () {
      var k = c.getAttribute('data-job-filter');
      jobChips.forEach(function (o) { o.setAttribute('aria-pressed', o === c ? 'true' : 'false'); });
      document.querySelectorAll('[data-job]').forEach(function (r) {
        r.hidden = !(k === 'all' || (' ' + r.getAttribute('data-job') + ' ').indexOf(' ' + k + ' ') > -1);
      });
    });
  });
  // Daily deals: mark today's column.
  var todayCol = document.querySelector('.day-col[data-day="' + new Date().getDay() + '"]');
  if (todayCol) todayCol.classList.add('is-today');

  // Woo-pons chips filter the tickets, one at a time.
  var dealChips = document.querySelectorAll('[data-deal-filter]');
  if (dealChips.length) {
    var dealCount = document.querySelector('[data-deal-count]');
    dealChips.forEach(function (c) {
      c.addEventListener('click', function () {
        var k = c.getAttribute('data-deal-filter'), n = 0;
        dealChips.forEach(function (o) { o.setAttribute('aria-pressed', o === c ? 'true' : 'false'); });
        document.querySelectorAll('.ltef-coupon[data-tags]').forEach(function (t) {
          var on = k === 'all' || (' ' + t.getAttribute('data-tags') + ' ').indexOf(' ' + k + ' ') > -1;
          t.hidden = !on; if (on) n++;
        });
        if (dealCount) dealCount.textContent = n + (n === 1 ? ' deal' : ' deals') + (k === 'all' ? '' : ' of 6');
      });
    });
  }
  var counter = document.querySelector('[data-counter]');
  if (counter) {
    var counterCode = function () { var c = counter.querySelector('[data-c="code"]'); return c ? c.textContent.trim() : ''; };
    wireUsed(counter, counterCode);
    var cf = function (root, k) { return root.querySelector('[data-c="' + k + '"]'); };
    var fillFrom = function (root, t) {
      var q = function (sel) { var el = t.querySelector(sel); return el ? el.textContent.trim() : ''; };
      if (cf(root, 'off')) cf(root, 'off').textContent = q('.ltef-coupon__off');
      if (cf(root, 'where')) cf(root, 'where').textContent = q('.ltef-coupon__where');
      var code = t.querySelector('.ltef-coupon__code b'); if (cf(root, 'code') && code) cf(root, 'code').textContent = code.textContent;
      var terms = t.querySelector('.ltef-coupon__terms'); if (cf(root, 'terms') && terms) cf(root, 'terms').innerHTML = terms.innerHTML;
      if (cf(root, 'fine')) cf(root, 'fine').textContent = q('.ltef-coupon__fine').replace(/\s*·\s*Show the code before you pay$/, '');
      var img = t.querySelector('img'); if (cf(root, 'img') && img) cf(root, 'img').src = img.src;
      var d = new Date();
      if (cf(root, 'valid')) cf(root, 'valid').textContent = 'Valid today, ' + d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }).replace(',', '');
    };
    var dlg = null, opener = null;
    var canDialog = typeof HTMLDialogElement === 'function' && 'showModal' in HTMLDialogElement.prototype;
    document.querySelectorAll('.ltef-coupon [data-open-ticket]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        var t = a.closest('.ltef-coupon'); if (!t) return;
        fillFrom(counter, t);
        if (cf(counter, 'cap')) cf(counter, 'cap').textContent = 'Sample code. This is the ticket you opened.';
        if (!canDialog) { setTimeout(function () { counter.focus({ preventScroll: true }); }, 0); return; }  // no dialog support: the in-page counter
        e.preventDefault(); opener = a;
        if (!dlg) {
          dlg = document.createElement('dialog'); dlg.className = 'ticket-dialog'; dlg.setAttribute('aria-label', 'Woo-pon, ready to show at the counter');
          document.body.appendChild(dlg);
          dlg.addEventListener('close', function () { document.documentElement.style.overflow = ''; if (opener) opener.focus(); });
          dlg.addEventListener('click', function (ev) { if (ev.target === dlg) dlg.close(); });
        }
        var card = counter.firstElementChild.cloneNode(true);
        var wrap = document.createElement('div'); wrap.className = 'ticket-dialog__card'; wrap.appendChild(card);
        var back = card.querySelector('a.linkish');
        if (back) { var close = document.createElement('button'); close.type = 'button'; close.className = 'ticket-close'; close.textContent = 'Close'; close.addEventListener('click', function () { dlg.close(); }); back.replaceWith(close); }
        card.querySelectorAll('a.ltef-btn--alt').forEach(function (x) { x.remove(); });  // the sample's place link isn't this ticket's place
        dlg.innerHTML = ''; dlg.appendChild(wrap); var openCode = t.getAttribute('data-code'); wireUsed(wrap, function () { return openCode; });
        document.documentElement.style.overflow = 'hidden';
        dlg.showModal();
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
      if (t) t.textContent = n === 0 ? 'Nothing there yet: see what’s close' : 'Show me ' + n + (n === 1 ? ' place' : ' places');
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
        // the bubble's size follows what's left in it (same scale as the build), down to a small dashed dot at zero
        var circ = b.querySelector('circle'), lab = b.querySelector('[data-map-label]');
        if (circ) {
          var rMax = +circ.getAttribute('data-r-max'), r = c ? Math.min(rMax, 32 + 15 * Math.sqrt(c)) : 24, cy = +circ.getAttribute('cy');
          circ.setAttribute('r', r.toFixed(0)); circ.style.r = r.toFixed(0) + 'px';
          if (lab) { lab.setAttribute('y', (cy + r + 30).toFixed(0)); lab.style.y = (cy + r + 30).toFixed(0) + 'px'; }
        }
        b.classList.toggle('is-on', id === st.area); b.classList.toggle('is-empty', !c);
        b.setAttribute('aria-current', id === st.area ? 'true' : 'false');
        var nm = (b.getAttribute('aria-label') || '').split(':')[0];
        b.setAttribute('aria-label', nm + ': ' + c + (c === 1 ? ' place' : ' places') + (id === st.area ? ', showing' : ''));
      });
      if (countEl) countEl.textContent = n;
      var cj = document.querySelector('[data-count-jump]'); if (cj) cj.textContent = n === 0 ? 'No places yet' : n + (n === 1 ? ' place' : ' places');
      var cs = document.querySelector('[data-count-slug]'); if (cs) cs.textContent = (n === list.length ? n : n + ' of ' + list.length) + (n === 1 && n === list.length ? ' place' : ' places');
      var cw = document.querySelector('[data-count-word]'); if (cw) cw.textContent = n === 1 ? 'place' : 'places';
      if (note) note.textContent = n === list.length ? 'Tina’s newest first' : 'Showing ' + n + ' of ' + list.length;
      if (empty) empty.hidden = n !== 0;
      if (more) { var left = n - limit; more.hidden = left <= 0; more.textContent = left <= PAGE ? 'Show the last ' + (left === 1 ? 'one' : left) : 'Show ' + PAGE + ' more (' + left + ' left)'; }
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
  var spinBtn = document.querySelector('.roul-wheel [data-spin]') || document.querySelector('[data-spin]');
  var wheel = document.querySelector('[data-wheel]');
  if (!spinBtn || !wheel || !window.LTEF_PLACES) return;
  var PLACES = window.LTEF_PLACES;
  var st = { mood: 'anything', with: 'anyone', turn: 0, pick: null, spinning: false };
  var timer;
  var label = spinBtn.querySelector('[data-spin-label]');
  var states = {}; document.querySelectorAll('[data-state]').forEach(function (el) { states[el.getAttribute('data-state')] = el; });
  var grid = document.querySelector('.roul-grid'), slot = document.querySelector('.roul-result');
  function show(name) {
    Object.keys(states).forEach(function (k) { states[k].hidden = k !== name; });
    if (grid) grid.setAttribute('data-phase', states[name] ? name : 'idle');
  }
  function phone() { return window.matchMedia && window.matchMedia('(max-width: 720px)').matches; }
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
  function spin(e) {
    if (st.spinning) return;
    // From the result's own "Spin again": bring the wheel back into view first.
    var fromCard = e && e.currentTarget && e.currentTarget.closest && e.currentTarget.closest('.roul-card');
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
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // Hold the result's height while the wheel turns, so nothing below jumps up.
    if (slot) slot.style.minHeight = slot.offsetHeight ? slot.offsetHeight + 'px' : '';
    fill('count', String(cur.length)); show('spinning');
    if (fromCard) {
      var wr = wheel.getBoundingClientRect();
      if (wr.top < 0 || wr.bottom > window.innerHeight) wheel.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
      spinBtn.focus({ preventScroll: true });
    }
    clearTimeout(timer);
    timer = setTimeout(function () {
      var p = cur[i]; st.pick = p; st.spinning = false;
      spinBtn.removeAttribute('aria-disabled'); spinBtn.removeAttribute('aria-busy');
      if (label) label.textContent = 'Spin again';
      ['name', 'meta', 'month', 'quote', 'date'].forEach(function (k) { fill(k, p[k]); });
      document.querySelectorAll('[data-f="bg"]').forEach(function (el) { el.style.background = p.bg; });
      document.querySelectorAll('[data-f="img"]').forEach(function (el) { el.src = p.img; el.alt = 'Photo slot for ' + p.name; });
      document.querySelectorAll('[data-f="href"]').forEach(function (el) { el.href = p.href; });
      document.querySelectorAll('[data-f="maps"]').forEach(function (el) { el.href = p.maps; el.setAttribute('aria-label', 'Get directions to ' + p.name + ' (opens maps)'); });
      fill('dish', p.dish);
      document.querySelectorAll('[data-f="tt"]').forEach(function (el) { el.href = p.url; });
      document.querySelectorAll('[data-hosted]').forEach(function (el) { el.hidden = true; });
      show('pick');
      if (slot) slot.style.minHeight = '';
      // Deal the ticket: restart its landing each spin.
      var card = states.pick && states.pick.querySelector('.roul-card--pick');
      if (card) { card.classList.remove('is-dealt'); void card.offsetWidth; card.classList.add('is-dealt'); }
      // On wider screens the choices fold so the ticket sits beside the wheel.
      if (!phone()) document.querySelectorAll('.roul-adjust').forEach(function (d) { d.open = false; });
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
  function back() { show('idle'); if (!phone()) document.querySelectorAll('.roul-adjust').forEach(function (d) { d.open = true; }); spinBtn.focus(); }
  var widen = document.querySelector('[data-widen]'); if (widen) widen.addEventListener('click', function () { st.with = 'anyone'; sync(); back(); });
  var reset = document.querySelector('[data-reset]'); if (reset) reset.addEventListener('click', function () { st.mood = 'anything'; st.with = 'anyone'; sync(); back(); });
  sync();
})();

// Work with Tina: each "Ask about …" link (and ?about= from other pages) picks the matching line in the one form
(function () {
  var sel = document.getElementById('c-what'); if (!sel) return;
  function pick(k, go) {
    if (!k || !sel.querySelector('option[value="' + k + '"]')) return;
    sel.value = k;
    if (go) {
      var c = document.getElementById('contact');
      if (c) c.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
      sel.focus({ preventScroll: true });
    }
  }
  try { pick(new URLSearchParams(location.search).get('about'), false); } catch (err) {}
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-ask]'); if (!a) return;
    e.preventDefault(); pick(a.getAttribute('data-ask'), true);
    try { history.replaceState(null, '', '?about=' + a.getAttribute('data-ask') + '#contact'); } catch (err) {}
  });
})();
