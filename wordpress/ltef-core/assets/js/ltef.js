/* Live to Eat Food on the front end: video play, filters, Roulette and the VIP flyer field. Plain JS, no dependencies. */
( function () {
	'use strict';
	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	function $$( sel, root ) { return Array.prototype.slice.call( ( root || document ).querySelectorAll( sel ) ); }

	// Tina's TikTok: a still until pressed, then TikTok's own player in place.
	$$( '.tt' ).forEach( function ( box ) {
		var btn = box.querySelector( '.tt-play' ), id = box.getAttribute( 'data-tt' );
		if ( ! btn || ! id ) return;
		btn.addEventListener( 'click', function () {
			var f = document.createElement( 'iframe' );
			f.src = 'https://www.tiktok.com/player/v1/' + id + '?music_info=0&description=0&rel=0&native_context_menu=0&closed_caption=1&autoplay=1';
			f.title = box.getAttribute( 'data-title' ) || 'Tina’s video';
			f.allow = 'autoplay; fullscreen; encrypted-media; picture-in-picture';
			f.setAttribute( 'allowfullscreen', '' );
			f.className = 'tt-frame';
			box.innerHTML = '';
			box.appendChild( f );
			f.focus();
		} );
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
		var q = ex.querySelector( '[data-explore-q]' ), cr = ex.querySelector( '[data-explore-craving]' ), ar = ex.querySelector( '[data-explore-area]' ), tb = ex.querySelector( '[data-explore-tina]' );
		var rows = $$( '.place-row' ), feed = document.querySelector( '.ltef-explore .ltef-slot--feed' );
		var countEl = document.querySelector( '[data-explore-count]' ), empty = document.querySelector( '[data-explore-empty]' );
		var apply = function () {
			var t = ( q.value || '' ).trim().toLowerCase(), c = cr.value, a = ar.value, only = tb.getAttribute( 'aria-pressed' ) === 'true', shown = [];
			rows.forEach( function ( r ) {
				var ok = ( ! t || r.getAttribute( 'data-name' ).indexOf( t ) > -1 ) &&
					( ! c || ( ' ' + r.getAttribute( 'data-cravings' ) + ' ' ).indexOf( ' ' + c + ' ' ) > -1 ) &&
					( ! a || ( ' ' + r.getAttribute( 'data-area' ) + ' ' ).indexOf( ' ' + a + ' ' ) > -1 ) &&
					( ! only || r.getAttribute( 'data-tina' ) === '1' );
				r.hidden = ! ok; if ( ok ) shown.push( r );
			} );
			if ( feed ) { feed.hidden = shown.length < 4; if ( shown[ 3 ] ) shown[ 3 ].after( feed ); }
			if ( countEl ) countEl.textContent = shown.length === rows.length ? rows.length + ' places' : 'Showing ' + shown.length + ' of ' + rows.length;
			if ( empty ) empty.hidden = shown.length !== 0;
		};
		var timer;
		q.addEventListener( 'input', function () { clearTimeout( timer ); timer = setTimeout( apply, 150 ); } );
		cr.addEventListener( 'change', apply ); ar.addEventListener( 'change', apply );
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
		var st = { where: 'tina', mood: '', with: '', turn: 0, pick: null, spinning: false };
		var wheel = R.querySelector( '[data-wheel]' ), btn = R.querySelector( '[data-spin]' ), label = R.querySelector( '[data-spin-label]' );
		var states = {}; $$( '[data-state]', R ).forEach( function ( s ) { states[ s.getAttribute( 'data-state' ) ] = s; } );
		var show = function ( name ) { Object.keys( states ).forEach( function ( k ) { states[ k ].hidden = k !== name; } ); };
		var pool = function () {
			return data.filter( function ( p ) {
				return ( st.where === 'all' || p.tina ) && ( ! st.mood || p.cravings.indexOf( st.mood ) > -1 ) && ( ! st.with || p.with.indexOf( st.with ) > -1 );
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
} )();
