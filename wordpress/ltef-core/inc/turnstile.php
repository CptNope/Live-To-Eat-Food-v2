<?php
/**
 * Cloudflare Turnstile: a spam check on the site's forms (and, once it's
 * shown to work, the sign-in page). Off until both keys are saved under
 * Review queue → Settings. Most people never see a puzzle: Turnstile only
 * asks for a click when something looks off.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** The site key (public) and secret key (private), or '' when not set. */
function ltef_turnstile_keys() {
	$s = ltef_settings();
	return array( trim( (string) ( $s['turnstile_site'] ?? '' ) ), trim( (string) ( $s['turnstile_secret'] ?? '' ) ) );
}

function ltef_turnstile_on() {
	list( $site, $secret ) = ltef_turnstile_keys();
	return '' !== $site && '' !== $secret;
}

/** Checking forms: keys saved and a check passed on the Settings page, so a wrong key can't break the forms. */
function ltef_turnstile_ready() {
	$s = ltef_settings();
	return ltef_turnstile_on() && ! empty( $s['turnstile_ok'] );
}

/** The sign-in page too, when ticked. */
function ltef_turnstile_login_on() {
	$s = ltef_settings();
	return ltef_turnstile_ready() && ! empty( $s['turnstile_login'] );
}

/** The widget. $where names the place for Cloudflare's analytics (letters, digits, - and _). */
function ltef_turnstile_widget( $where, $appearance = 'interaction-only' ) {
	list( $site ) = ltef_turnstile_keys();
	if ( '' === $site ) {
		return '';
	}
	wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, array( 'strategy' => 'defer', 'in_footer' => true ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Cloudflare serves the current version at this URL
	return sprintf(
		'<div class="cf-turnstile ltef-turnstile" data-sitekey="%s" data-action="%s" data-theme="light" data-size="flexible" data-appearance="%s" data-language="en"></div>',
		esc_attr( $site ),
		esc_attr( substr( preg_replace( '/[^a-z0-9_-]/i', '-', $where ), 0, 32 ) ),
		esc_attr( $appearance )
	);
}

/**
 * Ask Cloudflare whether the token from the form is good.
 * Returns true, or a WP_Error whose message can be shown to the person.
 */
function ltef_turnstile_verify( $token = null, $secret = null ) {
	static $seen = array(); // a token can be checked once; WordPress may run the same save twice
	if ( null === $secret ) {
		list( , $secret ) = ltef_turnstile_keys();
	}
	if ( null === $token ) {
		$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by the caller
	}
	$memo = md5( $token . '|' . $secret );
	if ( isset( $seen[ $memo ] ) ) {
		return $seen[ $memo ];
	}
	if ( '' === $token ) {
		return new WP_Error( 'ltef_turnstile_missing', 'The spam check didn’t finish. Wait a moment for it, then send again.' );
	}
	$ip   = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) : ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	$resp = wp_remote_post(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		array(
			'timeout' => 10,
			'body'    => array( 'secret' => $secret, 'response' => $token, 'remoteip' => $ip, 'idempotency_key' => wp_generate_uuid4() ),
		)
	);
	if ( is_wp_error( $resp ) ) {
		// Cloudflare unreachable from the server: let real people through rather than lose their message.
		update_option( 'ltef_turnstile_last_error', gmdate( 'Y-m-d H:i' ) . ' ' . $resp->get_error_message(), false );
		return true;
	}
	$body = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( ! empty( $body['success'] ) ) {
		$seen[ $memo ] = true;
		return true;
	}
	$codes = isset( $body['error-codes'] ) ? (array) $body['error-codes'] : array( 'no-answer' );
	update_option( 'ltef_turnstile_last_error', gmdate( 'Y-m-d H:i' ) . ' ' . implode( ', ', $codes ), false );
	if ( array_intersect( $codes, array( 'invalid-input-secret', 'missing-input-secret' ) ) ) {
		$err = new WP_Error( 'ltef_turnstile_secret', 'The spam check isn’t set up right on our end. Please try again later.' );
	} elseif ( in_array( 'timeout-or-duplicate', $codes, true ) ) {
		$err = new WP_Error( 'ltef_turnstile_expired', 'The spam check expired. Please send it again.' );
	} else {
		$err = new WP_Error( 'ltef_turnstile_failed', 'The spam check didn’t pass. Please try again.' );
	}
	$err->add_data( $codes );
	$seen[ $memo ] = $err;
	return $err;
}

/* ---------- The sign-in page ---------- */

add_action(
	'login_form',
	function () {
		if ( ltef_turnstile_login_on() ) {
			echo ltef_turnstile_widget( 'login' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_attr
		}
	}
);
add_action(
	'lostpassword_form',
	function () {
		if ( ltef_turnstile_login_on() ) {
			echo ltef_turnstile_widget( 'lost-password' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_attr
		}
	}
);
add_action(
	'login_enqueue_scripts',
	function () {
		if ( ltef_turnstile_login_on() ) {
			wp_add_inline_style( 'login', '.login .ltef-turnstile{margin:0 0 16px}' );
		}
	}
);

/** Password sign-ins through wp-login.php only; app passwords, XML-RPC and the REST API are left alone. */
add_filter(
	'authenticate',
	function ( $user, $username ) {
		if ( ! ltef_turnstile_login_on() || '' === (string) $username || ! isset( $_POST['log'] ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $user;
		}
		$ok = ltef_turnstile_verify();
		return is_wp_error( $ok ) ? $ok : $user;
	},
	30,
	2
);

add_action(
	'lostpassword_post',
	function ( $errors ) {
		if ( ! ltef_turnstile_login_on() ) {
			return;
		}
		$ok = ltef_turnstile_verify();
		if ( is_wp_error( $ok ) ) {
			$errors->add( $ok->get_error_code(), $ok->get_error_message() );
		}
	}
);
