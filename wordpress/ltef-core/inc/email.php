<?php
/**
 * Email through Brevo: Monthly Crumbs signups go onto a Brevo list, and the site's own
 * emails (review notifications and the rest of wp_mail) can go out through Brevo.
 *
 * The API key is entered by the site owner under Review queue → Email and stays on the server.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** Saved Brevo settings with defaults. */
function ltef_brevo() {
	$saved = get_option( 'ltef_brevo', array() );
	return wp_parse_args(
		is_array( $saved ) ? $saved : array(),
		array(
			'key'        => '',
			'list_id'    => 0,
			'from_name'  => 'Tina at Live to Eat Food',
			'from_email' => 'tina@livetoeatfoodie.com',
			'send_mail'  => false,
		)
	);
}

/** One call to Brevo's API. Returns [ status code, decoded body ] or a WP_Error. */
function ltef_brevo_api( $method, $path, $body = null, $key = '' ) {
	$key = $key ? $key : ltef_brevo()['key'];
	if ( ! $key ) {
		return new WP_Error( 'ltef_brevo_key', 'No Brevo API key saved yet.' );
	}
	$args = array(
		'method'  => $method,
		'timeout' => 12,
		'headers' => array( 'api-key' => $key, 'accept' => 'application/json', 'content-type' => 'application/json' ),
	);
	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}
	$r = wp_remote_request( 'https://api.brevo.com/v3/' . ltrim( $path, '/' ), $args );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	return array( (int) wp_remote_retrieve_response_code( $r ), json_decode( (string) wp_remote_retrieve_body( $r ), true ) );
}

/** A readable reason from a failed Brevo reply. */
function ltef_brevo_error( $res ) {
	if ( is_wp_error( $res ) ) {
		return $res->get_error_message();
	}
	$msg = is_array( $res[1] ) && ! empty( $res[1]['message'] ) ? $res[1]['message'] : 'no details';
	return sprintf( 'Brevo said %d: %s', $res[0], $msg );
}

/** The connected Brevo account (cached for ten minutes), or a WP_Error. */
function ltef_brevo_account( $fresh = false ) {
	$cached = get_transient( 'ltef_brevo_account' );
	if ( ! $fresh && is_array( $cached ) ) {
		return $cached;
	}
	$res = ltef_brevo_api( 'GET', 'account' );
	if ( is_wp_error( $res ) || 200 !== $res[0] ) {
		return new WP_Error( 'ltef_brevo_account', ltef_brevo_error( $res ) );
	}
	$acct = array( 'email' => (string) ( $res[1]['email'] ?? '' ), 'company' => (string) ( $res[1]['companyName'] ?? '' ) );
	set_transient( 'ltef_brevo_account', $acct, 10 * MINUTE_IN_SECONDS );
	return $acct;
}

/** The account's contact lists: id => "Name (n contacts)". */
function ltef_brevo_lists() {
	$res = ltef_brevo_api( 'GET', 'contacts/lists?limit=50&offset=0&sort=desc' );
	if ( is_wp_error( $res ) || 200 !== $res[0] ) {
		return array();
	}
	$out = array();
	foreach ( $res[1]['lists'] ?? array() as $l ) {
		$out[ (int) $l['id'] ] = $l['name'] . ' (' . (int) ( $l['uniqueSubscribers'] ?? $l['totalSubscribers'] ?? 0 ) . ')';
	}
	return $out;
}

/** Put one email on the Monthly Crumbs list. True, or the reason it didn't work. */
function ltef_brevo_add_contact( $email ) {
	$b = ltef_brevo();
	if ( ! $b['key'] || ! $b['list_id'] ) {
		return 'Brevo isn’t connected yet.';
	}
	$res = ltef_brevo_api(
		'POST',
		'contacts',
		array(
			'email'         => $email,
			'listIds'       => array( (int) $b['list_id'] ),
			'updateEnabled' => true,
		)
	);
	return ( ! is_wp_error( $res ) && in_array( $res[0], array( 200, 201, 204 ), true ) ) ? true : ltef_brevo_error( $res );
}

/** Each new signup goes straight to Brevo. If Brevo is down it stays saved here, and "Send saved signups" picks it up later. */
add_action(
	'ltef_submitted',
	function ( $key, $post_id, $v ) {
		if ( 'signup' !== $key || empty( $v['email'] ) ) {
			return;
		}
		$ok = ltef_brevo_add_contact( $v['email'] );
		update_post_meta( $post_id, true === $ok ? 'ltef_brevo_synced' : 'ltef_brevo_error', true === $ok ? current_time( 'mysql' ) : $ok );
	},
	10,
	3
);

/** Saved signups that haven't reached Brevo yet. */
function ltef_brevo_unsynced( $limit = -1 ) {
	return get_posts(
		array(
			'post_type'   => 'ltef_submission',
			'post_status' => array( 'publish', 'pending', 'private' ),
			'numberposts' => $limit,
			'fields'      => 'ids',
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => 'ltef_form', 'value' => 'signup' ),
				array( 'key' => 'ltef_brevo_synced', 'compare' => 'NOT EXISTS' ),
			),
		)
	);
}

/* ------------------------------------------------------------------ The site's own emails */

/** "Name <a@b.c>" or "a@b.c" -> [ 'email' => …, 'name' => … ]. */
function ltef_brevo_addr( $s ) {
	$s = trim( (string) $s );
	if ( preg_match( '/^(.*)<\s*([^>]+)\s*>$/', $s, $m ) ) {
		$a = array( 'email' => sanitize_email( $m[2] ) );
		$n = trim( $m[1], " \t\"'" );
		if ( '' !== $n ) {
			$a['name'] = $n;
		}
		return $a;
	}
	return array( 'email' => sanitize_email( $s ) );
}

/**
 * Sends wp_mail through Brevo when that's switched on. If Brevo refuses, WordPress's own mail
 * is tried instead and the reason is kept for the Email page.
 */
add_filter(
	'pre_wp_mail',
	function ( $return, $atts ) {
		$b = ltef_brevo();
		if ( null !== $return || ! $b['send_mail'] || ! $b['key'] || ! is_email( $b['from_email'] ) ) {
			return $return;
		}
		$to = is_array( $atts['to'] ) ? $atts['to'] : explode( ',', (string) $atts['to'] );
		$to = array_values( array_filter( array_map( 'ltef_brevo_addr', $to ), function ( $a ) { return is_email( $a['email'] ); } ) );
		if ( ! $to ) {
			return $return;
		}
		$payload = array(
			'sender'  => array( 'name' => $b['from_name'], 'email' => $b['from_email'] ),
			'to'      => $to,
			'subject' => (string) $atts['subject'],
		);
		$html    = false;
		$headers = is_array( $atts['headers'] ) ? $atts['headers'] : preg_split( '/\r\n|\r|\n/', (string) $atts['headers'] );
		foreach ( array_filter( $headers ) as $h ) {
			if ( false === strpos( $h, ':' ) ) {
				continue;
			}
			list( $name, $value ) = array_map( 'trim', explode( ':', $h, 2 ) );
			switch ( strtolower( $name ) ) {
				case 'content-type':
					$html = false !== stripos( $value, 'text/html' );
					break;
				case 'reply-to':
					$payload['replyTo'] = ltef_brevo_addr( $value );
					break;
				case 'cc':
				case 'bcc':
					$payload[ strtolower( $name ) ] = array_map( 'ltef_brevo_addr', explode( ',', $value ) );
					break;
			}
		}
		$html = apply_filters( 'wp_mail_content_type', $html ? 'text/html' : 'text/plain' ) === 'text/html';
		if ( $html ) {
			$payload['htmlContent'] = (string) $atts['message'];
		} else {
			$payload['textContent'] = (string) $atts['message'];
		}
		$files = array_filter( (array) $atts['attachments'] );
		foreach ( $files as $f ) {
			if ( is_readable( $f ) ) {
				$payload['attachment'][] = array( 'name' => basename( $f ), 'content' => base64_encode( (string) file_get_contents( $f ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}
		}
		$res = ltef_brevo_api( 'POST', 'smtp/email', $payload );
		if ( ! is_wp_error( $res ) && in_array( $res[0], array( 200, 201, 202 ), true ) ) {
			delete_option( 'ltef_brevo_mail_error' );
			do_action( 'wp_mail_succeeded', $atts );
			return true;
		}
		update_option( 'ltef_brevo_mail_error', current_time( 'M j, g:ia' ) . ': ' . ltef_brevo_error( $res ), false );
		return $return; // fall back to WordPress's own mail
	},
	10,
	2
);

/* ------------------------------------------------------------------ Review queue → Email */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'ltef-review', 'Email and Monthly Crumbs', 'Email', 'manage_options', 'ltef-email', 'ltef_email_page' );
	},
	20
);

add_action(
	'admin_post_ltef_brevo',
	function () {
		check_admin_referer( 'ltef_brevo' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$b   = ltef_brevo();
		$msg = '';
		if ( 'save' === $do ) {
			$new = isset( $_POST['key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['key'] ) ) ) : '';
			if ( '' !== $new ) {
				$b['key'] = $new;
				delete_transient( 'ltef_brevo_account' );
			}
			if ( ! empty( $_POST['forget'] ) ) {
				$b['key'] = '';
				delete_transient( 'ltef_brevo_account' );
			}
			$b['list_id']    = isset( $_POST['list_id'] ) ? absint( $_POST['list_id'] ) : 0;
			$b['from_name']  = isset( $_POST['from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['from_name'] ) ) : $b['from_name'];
			$b['from_email'] = isset( $_POST['from_email'] ) ? sanitize_email( wp_unslash( $_POST['from_email'] ) ) : $b['from_email'];
			$b['send_mail']  = ! empty( $_POST['send_mail'] );
			update_option( 'ltef_brevo', $b, false );
			$acct = $b['key'] ? ltef_brevo_account( true ) : null;
			$msg  = is_wp_error( $acct ) ? 'Saved, but Brevo didn’t accept the key: ' . $acct->get_error_message() : 'Saved.';
		} elseif ( 'sync' === $do ) {
			$ok   = 0;
			$fail = '';
			foreach ( ltef_brevo_unsynced( 200 ) as $id ) {
				$f   = (array) get_post_meta( $id, 'ltef_fields', true );
				$res = empty( $f['email'] ) ? 'No email saved.' : ltef_brevo_add_contact( $f['email'] );
				if ( true === $res ) {
					update_post_meta( $id, 'ltef_brevo_synced', current_time( 'mysql' ) );
					delete_post_meta( $id, 'ltef_brevo_error' );
					++$ok;
				} else {
					update_post_meta( $id, 'ltef_brevo_error', $res );
					$fail = $res;
				}
			}
			$msg = sprintf( '%d %s sent to Brevo.', $ok, 1 === $ok ? 'signup' : 'signups' ) . ( $fail ? ' Some didn’t go: ' . $fail : '' );
		} elseif ( 'test' === $do ) {
			$to   = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
			$to   = is_email( $to ) ? $to : wp_get_current_user()->user_email;
			delete_option( 'ltef_brevo_mail_error' );
			$sent = wp_mail( $to, 'Test from Live to Eat Food', "This is a test email from the Live to Eat Food site.\n\nIf it arrived, the site's emails are working." );
			$err  = get_option( 'ltef_brevo_mail_error' );
			$msg  = $sent ? sprintf( 'Test email sent to %s%s.', $to, $b['send_mail'] && ! $err ? ' through Brevo' : '' ) : 'The test email didn’t send.';
			if ( $err ) {
				$msg .= ' Brevo: ' . $err;
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ltef-email&ltef_msg=' . rawurlencode( $msg ) ) );
		exit;
	}
);

function ltef_email_page() {
	$b    = ltef_brevo();
	$msg  = isset( $_GET['ltef_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['ltef_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$acct = $b['key'] ? ltef_brevo_account() : null;
	$on   = $acct && ! is_wp_error( $acct );
	$form = function ( $do, $inner, $button, $primary = true ) {
		printf( '<form method="post" action="%s"><input type="hidden" name="action" value="ltef_brevo"><input type="hidden" name="do" value="%s">%s%s', esc_url( admin_url( 'admin-post.php' ) ), esc_attr( $do ), wp_nonce_field( 'ltef_brevo', '_wpnonce', true, false ), $inner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		submit_button( $button, $primary ? 'primary' : 'secondary', 'submit', false );
		echo '</form>';
	};
	echo '<div class="wrap"><h1>Email and Monthly Crumbs</h1>';
	if ( $msg ) {
		echo '<div class="notice notice-info"><p>' . esc_html( $msg ) . '</p></div>';
	}
	if ( $on ) {
		echo '<p><strong>Connected to Brevo</strong> as ' . esc_html( $acct['email'] ) . ( $acct['company'] ? ' (' . esc_html( $acct['company'] ) . ')' : '' ) . '.</p>';
	} elseif ( is_wp_error( $acct ) ) {
		echo '<div class="notice notice-error inline"><p>Brevo didn’t accept the saved key: ' . esc_html( $acct->get_error_message() ) . '</p></div>';
	} else {
		echo '<p>Not connected yet. In Brevo, open <strong>SMTP &amp; API → API keys</strong>, generate a key named “Live to Eat Food site”, and paste it below.</p>';
	}

	$lists = $on ? ltef_brevo_lists() : array();
	$opts  = '<option value="0">Choose a list</option>';
	foreach ( $lists as $id => $label ) {
		$opts .= '<option value="' . (int) $id . '"' . selected( (int) $b['list_id'], $id, false ) . '>' . esc_html( $label ) . '</option>';
	}
	$inner = '<table class="form-table" role="presentation">'
		. '<tr><th scope="row"><label for="ltef-bk">Brevo API key</label></th><td><input type="password" id="ltef-bk" name="key" value="" class="regular-text" autocomplete="off" placeholder="' . ( $b['key'] ? esc_attr( 'Saved, ends in ' . substr( $b['key'], -4 ) ) : 'xkeysib-…' ) . '"><p class="description">' . ( $b['key'] ? 'Leave blank to keep the saved key. <label><input type="checkbox" name="forget" value="1"> Remove the saved key</label>' : 'Kept on the server and never shown again.' ) . '</p></td></tr>'
		. '<tr><th scope="row"><label for="ltef-bl">Monthly Crumbs list</label></th><td>' . ( $on ? '<select id="ltef-bl" name="list_id">' . $opts . '</select><p class="description">Every signup on the site goes onto this list.</p>' : '<input type="hidden" name="list_id" value="' . (int) $b['list_id'] . '"><span class="description">Pick it once the key is saved.</span>' ) . '</td></tr>'
		. '<tr><th scope="row"><label for="ltef-fn">Emails come from</label></th><td><input type="text" id="ltef-fn" name="from_name" value="' . esc_attr( $b['from_name'] ) . '" class="regular-text"> <input type="email" name="from_email" value="' . esc_attr( $b['from_email'] ) . '" class="regular-text" aria-label="From email"><p class="description">Must be a sender on livetoeatfoodie.com in Brevo. Replies go to this inbox.</p></td></tr>'
		. '<tr><th scope="row">The site’s own emails</th><td><label><input type="checkbox" name="send_mail" value="1"' . checked( $b['send_mail'], true, false ) . '> Send them through Brevo</label><p class="description">New-submission notifications and WordPress’s own emails (password resets and the rest). Your host’s mail often never arrives.</p></td></tr>'
		. '</table>';
	$form( 'save', $inner, 'Save' );

	echo '<h2>Signups</h2>';
	$waiting = count( ltef_brevo_unsynced() );
	$total   = count(
		get_posts(
			array(
				'post_type'   => 'ltef_submission',
				'post_status' => array( 'publish', 'pending', 'private' ),
				'numberposts' => -1,
				'fields'      => 'ids',
				'meta_key'    => 'ltef_form', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'  => 'signup', // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		)
	);
	echo '<p>' . esc_html( sprintf( '%d Monthly Crumbs %s saved on the site; %d not in Brevo yet.', $total, 1 === $total ? 'signup' : 'signups', $waiting ) ) . '</p>';
	if ( $on && $b['list_id'] && $waiting ) {
		$form( 'sync', '', sprintf( 'Send %d saved %s to Brevo', $waiting, 1 === $waiting ? 'signup' : 'signups' ), false );
	}

	echo '<h2>Check it works</h2>';
	$err = get_option( 'ltef_brevo_mail_error' );
	if ( $err ) {
		echo '<p><strong>Last Brevo problem:</strong> ' . esc_html( $err ) . '</p>';
	}
	$form( 'test', '<label for="ltef-tt">Send a test email to</label> <input type="email" id="ltef-tt" name="to" value="' . esc_attr( $b['from_email'] ? $b['from_email'] : wp_get_current_user()->user_email ) . '" class="regular-text"> ', 'Send test', false );
	echo '</div>';
}
