<?php
/**
 * Reply by email from the review queue: answer a message (Work with Tina, a tip, a claim) through
 * Brevo, from any sender verified in Brevo, without leaving WordPress. Each reply is noted on the item.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** Verified, active Brevo senders as [ email => name ] (cached ten minutes). Empty when Brevo isn't connected. */
function ltef_reply_senders( $fresh = false ) {
	$cached = get_transient( 'ltef_brevo_senders' );
	if ( ! $fresh && is_array( $cached ) ) {
		return $cached;
	}
	$out = array();
	if ( ltef_brevo()['key'] ) {
		$res = ltef_brevo_api( 'GET', 'senders' );
		if ( ! is_wp_error( $res ) && 200 === $res[0] && ! empty( $res[1]['senders'] ) ) {
			foreach ( $res[1]['senders'] as $s ) {
				if ( ! empty( $s['active'] ) && is_email( $s['email'] ?? '' ) ) {
					$out[ strtolower( $s['email'] ) ] = (string) ( $s['name'] ?? '' );
				}
			}
		}
	}
	$main = strtolower( ltef_brevo()['from_email'] );
	if ( isset( $out[ $main ] ) ) { // the site's own sender first
		$out = array( $main => $out[ $main ] ) + $out;
	}
	set_transient( 'ltef_brevo_senders', $out, 10 * MINUTE_IN_SECONDS );
	return $out;
}

/** The team: verified Brevo senders on the site's own domain (tina@, jeremy@ …), as [ email => name ]. */
function ltef_reply_team() {
	$main   = strtolower( ltef_brevo()['from_email'] );
	$domain = substr( strrchr( $main, '@' ), 1 );
	$team   = array();
	foreach ( ltef_reply_senders() as $email => $name ) {
		if ( $domain && substr( strrchr( $email, '@' ), 1 ) === $domain ) {
			$team[ $email ] = $name;
		}
	}
	return $team;
}

/** The email address someone left on a submission, or ''. */
function ltef_reply_to_address( $post_id ) {
	$fields = get_post_meta( $post_id, 'ltef_fields', true );
	foreach ( array( 'email', 'contact_email' ) as $k ) {
		if ( is_array( $fields ) && ! empty( $fields[ $k ] ) && is_email( $fields[ $k ] ) ) {
			return sanitize_email( $fields[ $k ] );
		}
	}
	foreach ( array( 'ltef_email', 'ltef_contact_email' ) as $k ) {
		$v = get_post_meta( $post_id, $k, true );
		if ( $v && is_email( $v ) ) {
			return sanitize_email( $v );
		}
	}
	return '';
}

/** Chips for replies already sent: [ modifier, escaped html ]. */
function ltef_reply_flags( $post_id ) {
	$flags = array();
	foreach ( (array) get_post_meta( $post_id, 'ltef_replies', true ) as $r ) {
		if ( is_array( $r ) && ! empty( $r['at'] ) ) {
			$flags[] = array( '', esc_html( 'Replied ' . wp_date( 'M j, g:ia', (int) $r['at'] ) . ' from ' . $r['from'] ) );
		}
	}
	return $flags;
}

/** What was already sent from this item, full text, so the whole team can read the thread. */
function ltef_reply_history( $post_id ) {
	$out = '';
	foreach ( (array) get_post_meta( $post_id, 'ltef_replies', true ) as $r ) {
		if ( ! is_array( $r ) || empty( $r['message'] ) ) {
			continue;
		}
		$out .= sprintf(
			'<details class="ltef-reply ltef-reply--sent"><summary>Sent %1$s: %2$s%7$s</summary><dl class="ltef-reply__meta"><dt>From</dt><dd>%3$s</dd><dt>To</dt><dd>%4$s</dd>%5$s</dl><div class="ltef-reply__text">%6$s</div></details>',
			esc_html( wp_date( 'M j, g:ia', (int) $r['at'] ) ),
			esc_html( $r['subject'] ),
			esc_html( $r['from'] ),
			esc_html( $r['to'] ),
			! empty( $r['cc'] ) ? '<dt>CC</dt><dd>' . esc_html( implode( ', ', (array) $r['cc'] ) ) . '</dd>' : '',
			esc_html( $r['message'] ),
			! empty( $r['manual'] ) ? ' <span class="description">(added by hand)</span>' : ''
		);
	}
	return $out ? '<div class="ltef-replies">' . $out . '</div>' : '';
}

/** Replies already sent plus the "Reply by email" box, in one full-width area under the card. */
function ltef_reply_form( $post ) {
	$box = ltef_reply_box( $post );
	$log = ltef_reply_history( $post->ID );
	$add = $box ? ltef_reply_log_form( $post ) : '';
	return ( $box || $log ) ? '<div class="ltef-reply-area">' . $log . $box . $add . '</div>' : '';
}

/** The "Reply by email" box for one queue card, or '' when there's no address to reply to. */
function ltef_reply_box( $post ) {
	$to = ltef_reply_to_address( $post->ID );
	if ( ! $to ) {
		return '';
	}
	$id      = (int) $post->ID;
	$senders = ltef_reply_senders();
	$head    = '<details class="ltef-reply"><summary>Reply by email</summary>';
	if ( ! $senders ) {
		return $head . '<p class="description">Connect Brevo under Email first: replies go out through it.</p></details>';
	}
	$me    = strtolower( wp_get_current_user()->user_email );
	$pick  = '';
	$first = true;
	foreach ( $senders as $email => $name ) {
		$pick .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $email ), selected( $email === $me || ( $first && ! isset( $senders[ $me ] ) ), true, false ), esc_html( ( $name ? $name . ' ' : '' ) . '<' . $email . '>' ) );
		$first = false;
	}
	$subject = 'Re: your message to Live to Eat Food';
	return $head . sprintf(
		'<form method="post" action="%1$s" class="ltef-reply__form"><input type="hidden" name="action" value="ltef_reply"><input type="hidden" name="post" value="%2$d">%3$s'
		. '<p class="ltef-reply__to"><span>To</span> %4$s</p>'
		. '<p><label for="ltef-rf-%2$d">From</label><select id="ltef-rf-%2$d" name="from">%5$s</select></p>'
		. '<p><label for="ltef-rs-%2$d">Subject</label><input type="text" id="ltef-rs-%2$d" name="subject" value="%6$s" required></p>'
		. '<p><label for="ltef-rm-%2$d">Message</label><textarea id="ltef-rm-%2$d" name="message" rows="14" required></textarea></p>'
		. '%7$s<p><button class="button button-primary">Send reply</button> <span class="description">Sent through Brevo. You get a copy, and the full text is kept on this item. Their reply goes to the From address (and the team, if they Reply All). The item stays here until you approve or decline it.</span></p></form></details>',
		esc_url( admin_url( 'admin-post.php' ) ),
		$id,
		wp_nonce_field( 'ltef_reply_' . $id, '_wpnonce', true, false ),
		esc_html( $to ),
		$pick, // escaped above
		esc_attr( $subject ),
		count( ltef_reply_team() ) > 1 ? '<p><label class="ltef-check"><input type="checkbox" name="team" value="1" checked> CC the team (' . esc_html( implode( ', ', array_keys( ltef_reply_team() ) ) ) . '), so everyone sees their reply when they Reply All</label></p>' : ''
	);
}

add_action( 'admin_post_ltef_reply', 'ltef_handle_reply' );
function ltef_handle_reply() {
	$id = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0;
	check_admin_referer( 'ltef_reply_' . $id );
	if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Not allowed.' );
	}
	$to      = ltef_reply_to_address( $id );
	$from    = isset( $_POST['from'] ) ? strtolower( sanitize_email( wp_unslash( $_POST['from'] ) ) ) : '';
	$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$senders = ltef_reply_senders( true );
	$back    = function ( $done, $note ) {
		wp_safe_redirect( add_query_arg( array( 'ltef_done' => $done, 'ltef_note' => rawurlencode( $note ) ), admin_url( 'admin.php?page=ltef-review' ) ) );
		exit;
	};
	if ( ! $to || '' === $subject || '' === trim( $message ) ) {
		$back( 'unsent', 'Reply not sent: it needs their email, a subject and a message.' );
	}
	if ( ! isset( $senders[ $from ] ) ) {
		$back( 'unsent', 'Reply not sent: ' . $from . ' isn’t a verified sender in Brevo.' );
	}
	$payload = array(
		'sender'      => array( 'name' => $senders[ $from ], 'email' => $from ),
		'to'          => array( array( 'email' => $to ) ),
		'replyTo'     => array( 'email' => $from, 'name' => $senders[ $from ] ),
		'subject'     => $subject,
		'textContent' => $message,
		'htmlContent' => ltef_mail_text_to_html( $message ),
		'tags'        => array( 'queue-reply' ),
	);
	$cc = array();
	if ( ! empty( $_POST['team'] ) ) {
		foreach ( ltef_reply_team() as $email => $name ) {
			if ( $email !== $from && strtolower( $to ) !== $email ) {
				$cc[] = array( 'email' => $email, 'name' => $name );
			}
		}
	}
	if ( $cc ) {
		$payload['cc'] = $cc; // visible, so Reply All reaches the whole team
	}
	if ( strtolower( $to ) !== $from ) {
		$payload['bcc'] = array( array( 'email' => $from ) ); // the sender's own copy: Brevo keeps none in their mailbox
	}
	$res = ltef_brevo_api( 'POST', 'smtp/email', $payload );
	if ( is_wp_error( $res ) || ! in_array( $res[0], array( 200, 201, 202 ), true ) ) {
		$back( 'unsent', 'Reply not sent. ' . ltef_brevo_error( $res ) );
	}
	$log   = (array) get_post_meta( $id, 'ltef_replies', true );
	$log   = array_values( array_filter( $log, 'is_array' ) );
	$log[] = array( 'at' => time(), 'from' => $from, 'to' => $to, 'cc' => wp_list_pluck( $cc, 'email' ), 'subject' => $subject, 'message' => $message, 'by' => get_current_user_id(), 'id' => $res[1]['messageId'] ?? '' );
	update_post_meta( $id, 'ltef_replies', $log );
	$back( 'replied', 'Reply sent to ' . $to . ' from ' . $from . '.' );
}

/** "Add an email sent elsewhere": paste a reply sent from a mailbox so the thread on the item stays complete. */
function ltef_reply_log_form( $post ) {
	$id = (int) $post->ID;
	$me = wp_get_current_user()->user_email;
	return sprintf(
		'<details class="ltef-reply"><summary>Add an email sent elsewhere</summary><form method="post" action="%1$s" class="ltef-reply__form"><input type="hidden" name="action" value="ltef_reply_log"><input type="hidden" name="post" value="%2$d">%3$s'
		. '<p class="description">For a reply sent from your own mailbox, so the whole team can read it here. Nothing is emailed.</p>'
		. '<p><label for="ltef-lf-%2$d">From</label><input type="email" id="ltef-lf-%2$d" name="from" value="%4$s" required></p>'
		. '<p><label for="ltef-lt-%2$d">To</label><input type="email" id="ltef-lt-%2$d" name="to" value="%5$s" required></p>'
		. '<p><label for="ltef-ld-%2$d">Sent on</label><input type="datetime-local" id="ltef-ld-%2$d" name="when" value="%6$s" required></p>'
		. '<p><label for="ltef-ls-%2$d">Subject</label><input type="text" id="ltef-ls-%2$d" name="subject" required></p>'
		. '<p><label for="ltef-lm-%2$d">Message</label><textarea id="ltef-lm-%2$d" name="message" rows="10" required></textarea></p>'
		. '<p><button class="button">Add to this item</button></p></form></details>',
		esc_url( admin_url( 'admin-post.php' ) ),
		$id,
		wp_nonce_field( 'ltef_reply_log_' . $id, '_wpnonce', true, false ),
		esc_attr( $me ),
		esc_attr( ltef_reply_to_address( $id ) ),
		esc_attr( wp_date( 'Y-m-d\\TH:i' ) )
	);
}

add_action( 'admin_post_ltef_reply_log', 'ltef_handle_reply_log' );
function ltef_handle_reply_log() {
	$id = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0;
	check_admin_referer( 'ltef_reply_log_' . $id );
	if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Not allowed.' );
	}
	$from    = isset( $_POST['from'] ) ? sanitize_email( wp_unslash( $_POST['from'] ) ) : '';
	$to      = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
	$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$when    = isset( $_POST['when'] ) ? sanitize_text_field( wp_unslash( $_POST['when'] ) ) : '';
	$at      = date_create_immutable( $when, wp_timezone() );
	$back    = function ( $done, $note ) {
		wp_safe_redirect( add_query_arg( array( 'ltef_done' => $done, 'ltef_note' => rawurlencode( $note ) ), admin_url( 'admin.php?page=ltef-review' ) ) );
		exit;
	};
	if ( ! is_email( $from ) || ! is_email( $to ) || '' === $subject || '' === trim( $message ) || ! $at ) {
		$back( 'unsent', 'Not added: it needs From, To, a date, a subject and the message.' );
	}
	$log    = array_values( array_filter( (array) get_post_meta( $id, 'ltef_replies', true ), 'is_array' ) );
	$filled = false;
	foreach ( $log as $i => $r ) {
		// A reply the site sent before it kept the text: fill that one in rather than adding a second.
		if ( empty( $r['message'] ) && strtolower( $r['from'] ?? '' ) === strtolower( $from ) && strtolower( $r['to'] ?? '' ) === strtolower( $to ) && ( $r['subject'] ?? '' ) === $subject ) {
			$log[ $i ]['message'] = $message;
			$filled               = true;
			break;
		}
	}
	if ( ! $filled ) {
		$log[] = array( 'at' => $at->getTimestamp(), 'from' => strtolower( $from ), 'to' => $to, 'cc' => array(), 'subject' => $subject, 'message' => $message, 'by' => get_current_user_id(), 'manual' => true );
	}
	usort( $log, function ( $a, $b ) { return (int) $a['at'] <=> (int) $b['at']; } );
	update_post_meta( $id, 'ltef_replies', $log );
	$back( 'replied', 'Added to ' . get_the_title( $id ) . '.' );
}
