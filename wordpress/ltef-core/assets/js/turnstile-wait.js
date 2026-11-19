/**
 * Forms with the Cloudflare spam check: wait for it instead of failing.
 *
 * The check runs invisibly and takes a second or two. Pressing the button (or a password
 * manager signing in) before it has finished used to send the form without its token, which
 * the server refuses ("the spam check didn't finish") and, on the sign-in page, counts as a
 * failed login toward a lockout. Now the press is held, a short note says what's happening,
 * and the form goes as soon as the check is done.
 */
( function () {
	var WAIT_MS = 25000;

	function token( form ) {
		var input = form.querySelector( 'input[name="cf-turnstile-response"]' );
		return input && input.value ? input.value : '';
	}

	function note( form, text ) {
		var n = form.querySelector( '.ltef-turnstile-wait' );
		if ( ! n ) {
			n = document.createElement( 'p' );
			n.className = 'ltef-turnstile-wait';
			n.setAttribute( 'role', 'status' );
			n.setAttribute( 'aria-live', 'polite' );
			var widget = form.querySelector( '.ltef-turnstile' );
			if ( widget && widget.parentNode ) {
				widget.parentNode.insertBefore( n, widget.nextSibling );
			} else {
				form.appendChild( n );
			}
		}
		n.textContent = text;
		n.hidden = ! text;
	}

	document.addEventListener(
		'submit',
		function ( e ) {
			var form = e.target;
			if ( ! form || ! form.querySelector || ! form.querySelector( '.ltef-turnstile' ) || token( form ) ) {
				return;
			}
			e.preventDefault();
			e.stopImmediatePropagation();
			if ( form.getAttribute( 'data-ltef-waiting' ) ) {
				return;
			}
			form.setAttribute( 'data-ltef-waiting', '1' );
			var submitter = e.submitter && e.submitter.form === form ? e.submitter : null;
			var started = Date.now();
			note( form, 'One moment: checking you’re not a bot…' );
			( function tick() {
				if ( token( form ) ) {
					form.removeAttribute( 'data-ltef-waiting' );
					note( form, '' );
					if ( form.requestSubmit ) {
						if ( submitter ) {
							form.requestSubmit( submitter );
						} else {
							form.requestSubmit();
						}
					} else {
						form.submit();
					}
					return;
				}
				if ( Date.now() - started > WAIT_MS ) {
					form.removeAttribute( 'data-ltef-waiting' );
					note( form, 'The spam check needs you: tick the box above, then press the button again.' );
					return;
				}
				window.setTimeout( tick, 250 );
			} )();
		},
		true
	);
} )();
