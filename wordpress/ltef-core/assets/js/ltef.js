/* Live to Eat Food on the front end: video play, filters, Roulette and the VIP flyer field. Plain JS, no dependencies. */
( function () {
	'use strict';
	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	function $$( sel, root ) { return Array.prototype.slice.call( ( root || document ).querySelectorAll( sel ) ); }

	// Tina's video: a still until pressed, then the best copy's own player in place (the server picks it).
	// TikTok's player says when it's ready; if it doesn't (TikTok busy, blocked on the network, or the
	// video gone), switch to another copy when there is one, or offer the video on TikTok and a retry.
	var hosts = /^https:\/\/www\.(youtube-nocookie\.com|instagram\.com|facebook\.com|tiktok\.com)\//;
	$$( '.tt' ).forEach( function ( box ) {
		var btn = box.querySelector( '.tt-play' ), src = box.getAttribute( 'data-embed' );
		if ( ! btn || ! src || ! hosts.test( src ) ) return;
		var alt = box.getAttribute( 'data-alt' ) || '', url = box.getAttribute( 'data-url' ) || '', label = box.getAttribute( 'data-label' ) || 'TikTok';
		function frame( s ) {
			var f = document.createElement( 'iframe' );
			f.src = s;
			f.title = box.getAttribute( 'data-title' ) || 'Tina’s video';
			f.allow = 'autoplay; fullscreen; encrypted-media; picture-in-picture';
			f.setAttribute( 'allowfullscreen', '' );
			f.className = 'tt-frame';
			box.innerHTML = '';
			box.appendChild( f );
			if ( /^https:\/\/www\.tiktok\.com\//.test( s ) ) watch( f );
			return f;
		}
		function failed() {
			if ( alt && hosts.test( alt ) ) { var a = alt; alt = ''; frame( a ); return; }
			var panel = document.createElement( 'div' );
			panel.className = 'tt-fail';
			panel.setAttribute( 'role', 'status' );
			panel.innerHTML = '<p><b>' + label + ' didn’t load the video here.</b> It happens when ' + label + ' is busy or blocked on your network.</p>';
			if ( url ) {
				var go = document.createElement( 'a' );
				go.className = 'ltef-btn ltef-btn--sm'; go.href = url; go.target = '_blank'; go.rel = 'noopener';
				go.textContent = 'Watch it on ' + label;
				panel.appendChild( go );
			}
			var again = document.createElement( 'button' );
			again.type = 'button'; again.className = 'tt-again'; again.textContent = 'Try again';
			again.addEventListener( 'click', function () { frame( src ).focus(); } );
			panel.appendChild( again );
			box.appendChild( panel );
		}
		function watch( f ) {
			var done = false;
			var timer = setTimeout( function () { if ( ! done ) { done = true; window.removeEventListener( 'message', hear ); failed(); } }, 9000 );
			function hear( e ) {
				if ( e.origin !== 'https://www.tiktok.com' || e.source !== f.contentWindow ) return;
				var d = e.data;
				if ( typeof d === 'string' ) { try { d = JSON.parse( d ); } catch ( x ) { return; } }
				if ( ! d || ! d[ 'x-tiktok-player' ] ) return;
				if ( d.type === 'onPlayerReady' || ( d.type === 'onStateChange' && d.value === 1 ) ) {
					done = true; clearTimeout( timer ); window.removeEventListener( 'message', hear );
				} else if ( d.type === 'onPlayerError' && d.value && [ 1001, 2001, 3001 ].indexOf( Number( d.value.errorCode ) ) > -1 ) {
					done = true; clearTimeout( timer ); window.removeEventListener( 'message', hear ); failed();
				}
			}
			window.addEventListener( 'message', hear );
		}
		btn.addEventListener( 'click', function () { frame( src ).focus(); } );
	} );

	// Chip groups that filter a list by data attributes (Woo-pons, jobs).
	function chipFilter( chipAttr, itemSel, itemAttr, countEl, word ) {
		var chips = $$( '[' + chipAttr + ']' );
		if ( ! chips.length ) return;
		chips.forEach( function ( c ) {
			c.addEventListener( 'click', function () {
				var v = c.getAttribute( chipAttr ), n = 0;
				chips.forEach( function ( x ) { x.setAttribute( 'aria-pressed', x === c ? 'true' : 'false' ); } );
				$$( itemSel ).forEach( function ( it ) {
					var show = v === 'all' || ( ' ' + it.getAttribute( itemAttr ) + ' ' ).indexOf( ' ' + v + ' ' ) > -1;
					it.hidden = ! show; if ( show ) n++;
				} );
				var ce = document.querySelector( countEl );
				if ( ce ) ce.textContent = n + ' ' + word + ( n === 1 ? '' : 's' );
			} );
		} );
	}
	chipFilter( 'data-deal-filter', '.ltef-woopons .ltef-coupon', 'data-tags', '[data-deal-count]', 'deal' );
	chipFilter( 'data-job-filter', '.job-row', 'data-job', '[data-job-count]', 'job' );

	// Explore: search what Tina ordered, cravings, neighborhoods, only her places.
	var ex = document.querySelector( '[data-explore-form]' );
	if ( ex ) {
		var q = ex.querySelector( '[data-explore-q]' ), cr = ex.querySelector( '[data-explore-craving]' ), ar = ex.querySelector( '[data-explore-area]' ), kd = ex.querySelector( '[data-explore-kind]' ), tb = ex.querySelector( '[data-explore-tina]' );
		var rows = $$( '.place-row' ), feed = document.querySelector( '.ltef-explore .ltef-slot--feed' );
		var countEl = document.querySelector( '[data-explore-count]' ), empty = document.querySelector( '[data-explore-empty]' );
		var PAGE = 24, limit = PAGE, more = document.querySelector( '[data-explore-more]' );
		var apply = function ( keep ) {
			var t = ( q.value || '' ).trim().toLowerCase(), k = kd ? kd.value : '', c = cr.value, a = ar.value, only = tb.getAttribute( 'aria-pressed' ) === 'true', matched = [];
			if ( ! keep ) limit = PAGE;
			rows.forEach( function ( r ) {
				var ok = ( ! t || r.getAttribute( 'data-name' ).indexOf( t ) > -1 ) &&
					( ! k || r.getAttribute( 'data-kind' ) === k ) &&
					( ! c || ( ' ' + r.getAttribute( 'data-cravings' ) + ' ' ).indexOf( ' ' + c + ' ' ) > -1 ) &&
					( ! a || ( ' ' + r.getAttribute( 'data-area' ) + ' ' ).indexOf( ' ' + a + ' ' ) > -1 ) &&
					( ! only || r.getAttribute( 'data-tina' ) === '1' );
				if ( ok ) matched.push( r );
			} );
			var shown = matched.slice( 0, limit );
			rows.forEach( function ( r ) { r.hidden = shown.indexOf( r ) < 0; } );
			if ( feed ) { feed.hidden = shown.length < 4; if ( shown[ 3 ] ) shown[ 3 ].after( feed ); }
			if ( countEl ) countEl.textContent = matched.length === rows.length ? rows.length + ' places' : matched.length + ' of ' + rows.length + ' places match';
			if ( more ) {
				var left = matched.length - shown.length;
				more.hidden = left <= 0;
				more.textContent = 'Show ' + Math.min( PAGE, left ) + ' more (' + left + ' left)';
			}
			if ( empty ) empty.hidden = matched.length !== 0;
			return shown;
		};
		if ( more ) {
			more.addEventListener( 'click', function () {
				var before = limit;
				limit += PAGE;
				var shown = apply( true ), first = shown[ before ];
				if ( first ) { var link = first.querySelector( 'a' ); if ( link ) link.focus(); }
			} );
		}
		apply();
		var timer;
		q.addEventListener( 'input', function () { clearTimeout( timer ); timer = setTimeout( function () { apply(); }, 150 ); } );
		[ cr, ar, kd ].forEach( function ( el ) { if ( el ) el.addEventListener( 'change', function () { apply(); } ); } );
		tb.addEventListener( 'click', function () { tb.setAttribute( 'aria-pressed', tb.getAttribute( 'aria-pressed' ) === 'true' ? 'false' : 'true' ); apply(); } );
		ex.addEventListener( 'submit', function ( e ) { e.preventDefault(); } );
	}

	// Run a Woo-pon: the flyer upload shows, and is required, only for a VIP Woo-pon.
	$$( 'form.ltef-form' ).forEach( function ( form ) {
		var tier = form.querySelector( 'select[name="tier"]' ), box = form.querySelector( '[data-vip-only]' );
		if ( ! tier || ! box ) return;
		var file = box.querySelector( 'input[type="file"]' );
		var sync = function () { var vip = tier.value === 'vip'; box.hidden = ! vip; file.required = vip; if ( ! vip ) file.value = ''; };
		tier.addEventListener( 'change', sync ); sync();
	} );

	// Restaurant Roulette.
	var R = document.querySelector( '[data-roulette]' );
	if ( R ) {
		var data = [];
		try { data = JSON.parse( R.querySelector( '[data-roulette-places]' ).textContent ); } catch ( e ) {}
		var st = { where: 'tina', mood: '', with: '', town: '', turn: 0, pick: null, spinning: false };
		var wheel = R.querySelector( '[data-wheel]' ), btn = R.querySelector( '[data-spin]' ), label = R.querySelector( '[data-spin-label]' );
		var states = {}; $$( '[data-state]', R ).forEach( function ( s ) { states[ s.getAttribute( 'data-state' ) ] = s; } );
		var show = function ( name ) { Object.keys( states ).forEach( function ( k ) { states[ k ].hidden = k !== name; } ); };
		var pool = function () {
			return data.filter( function ( p ) {
				return ( st.where === 'all' || p.tina ) && ( ! st.town || p.town === st.town ) && ( ! st.mood || p.cravings.indexOf( st.mood ) > -1 ) && ( ! st.with || p.with.indexOf( st.with ) > -1 );
			} );
		};
		var fill = function ( f, v ) { $$( '[data-f="' + f + '"]', R ).forEach( function ( el ) { el.textContent = v || ''; } ); };
		[ 'where', 'mood', 'with' ].forEach( function ( key ) {
			$$( '[data-' + key + ']', R ).forEach( function ( b ) {
				b.addEventListener( 'click', function () {
					if ( st.spinning ) return;
					st[ key ] = b.getAttribute( 'data-' + key );
					$$( '[data-' + key + ']', R ).forEach( function ( x ) { x.setAttribute( 'aria-pressed', x === b ? 'true' : 'false' ); } );
					if ( states.nomatch && ! states.nomatch.hidden ) show( '' );
				} );
			} );
		} );
		var townSel = R.querySelector( '[data-roul-town]' );
		if ( townSel ) townSel.addEventListener( 'change', function () {
			st.town = townSel.value;
			if ( states.nomatch && ! states.nomatch.hidden ) show( '' );
		} );
		btn.addEventListener( 'click', function () {
			if ( st.spinning ) return;
			var cur = pool();
			if ( ! cur.length ) { show( 'nomatch' ); return; }
			var i = Math.floor( Math.random() * cur.length );
			if ( cur.length > 1 && st.pick && cur[ i ].id === st.pick.id ) i = ( i + 1 ) % cur.length;
			st.turn += 1440 + Math.floor( Math.random() * 360 );
			wheel.style.transform = 'rotate(' + st.turn + 'deg)';
			st.spinning = true; btn.setAttribute( 'aria-disabled', 'true' ); label.textContent = 'Spinning…';
			fill( 'count', String( cur.length ) ); show( 'spinning' );
			setTimeout( function () {
				var p = cur[ i ]; st.pick = p; st.spinning = false;
				btn.removeAttribute( 'aria-disabled' ); label.textContent = 'Spin again';
				[ 'name', 'meta', 'month', 'quote', 'dish' ].forEach( function ( k ) { fill( k, p[ k ] ); } );
				$$( '[data-if="tina"]', R ).forEach( function ( el ) { el.hidden = ! p.tina; } );
				$$( '[data-if="notyet"]', R ).forEach( function ( el ) { el.hidden = !! p.tina; } );
				$$( '[data-f="bg"]', R ).forEach( function ( el ) { el.style.background = p.bg; } );
				$$( '[data-f="img"]', R ).forEach( function ( el ) { el.src = p.img; } );
				$$( '[data-f="href"]', R ).forEach( function ( el ) { el.href = p.href; } );
				$$( '[data-f="maps"]', R ).forEach( function ( el ) { el.href = p.maps; el.setAttribute( 'aria-label', 'Get directions to ' + p.name + ' (opens maps)' ); } );
				$$( '[data-f="video"]', R ).forEach( function ( el ) { if ( p.url ) el.href = p.url; el.setAttribute( 'aria-label', 'Watch Tina’s video at ' + p.name + ' (opens a new tab)' ); } );
				show( 'pick' );
				var h = R.querySelector( '[data-f="name"]' ); if ( h ) h.focus( { preventScroll: false } );
			}, reduce ? 0 : 3200 );
		} );
	}

	/* Daily deals: mark today's column. */
	var todayCol = document.querySelector( '[data-ltef-days] .day-col[data-day="' + new Date().getDay() + '"]' );
	if ( todayCol ) todayCol.classList.add( 'is-today' );

	/* Header menus: one open at a time; close on Escape or a click outside. */
	var menus = Array.prototype.slice.call( document.querySelectorAll( 'details[data-ltef-menu]' ) );
	menus.forEach( function ( d ) {
		d.addEventListener( 'toggle', function () {
			if ( d.open ) menus.forEach( function ( o ) { if ( o !== d ) o.open = false; } );
		} );
	} );
	document.addEventListener( 'click', function ( e ) {
		menus.forEach( function ( d ) { if ( d.open && ! d.contains( e.target ) ) d.open = false; } );
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' !== e.key ) return;
		menus.forEach( function ( d ) { if ( d.open ) { d.open = false; d.querySelector( 'summary' ).focus(); } } );
	} );
} )();
