<?php
/**
 * Submission forms. Everything sent lands as "pending" in Tina's review queue; nothing publishes itself.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form definitions: fields are [ name, label, type, required, hint, options ].
 */
function ltef_forms() {
	$s     = ltef_settings();
	$weekly = ltef_money( $s['woopon_weekly'] );
	$vip   = ltef_money( $s['woopon_vip'] );
	$contact_opts = array(
		''         => 'Pick one',
		'home'     => 'Ad spot: home page',
		'rail'     => 'Ad spot: place pages',
		'feed'     => 'Ad spot: a card in Explore',
		'guide'    => 'Ad spot: in a guide',
		'sunday'   => 'Paid ad in Monthly Crumbs',
		'vip'      => 'VIP Woo-pon (' . $vip . ')',
		'event'    => 'Premium event listing (' . ltef_money( $s['event_premium'] ) . ' a month)',
		'job'      => 'Job post (' . ltef_money( $s['job_post'] ) . ' a month)',
		'visit'    => 'Paid visit',
		'partner'  => 'Paid partnership',
		'enhanced' => 'Enhanced listing',
		'hosted'   => 'Invite Tina for a hosted visit',
		'prize'    => 'Donate a giveaway prize',
		'other'    => 'Something else',
	);
	return array(
		'woopon'  => array(
			'title'  => 'Send a Woo-pon',
			'intro'  => 'Every field shows on the ticket except your phone, email and flyer.',
			'button' => 'Send the Woo-pon',
			'note'   => 'Sent to Tina for review. Nothing goes live until she approves it.',
			'fields' => array(
				array( 'business', 'Business name', 'text', true, '' ),
				array( 'deal', 'The deal', 'text', true, 'For example: $5 off two large pies, or a free side with any bowl.' ),
				array( 'tier', 'How you’d like to run it', 'select', true, '', array( '' => 'Pick one', 'free' => 'Try it free: ' . (int) $s['woopon_free'] . ' weeks, first time only', 'weekly' => 'Weekly: ' . $weekly . ' a week', 'vip' => 'VIP Woo-pon: ' . $vip . ', in Tina’s monthly video' ) ),
				array( 'kind', 'What kind of business', 'select', true, '', array( '' => 'Pick one', 'food' => 'Food or drink', 'service' => 'A service', 'online' => 'Online' ) ),
				array( 'start', 'Start date', 'date', true, '' ),
				array( 'end', 'End date', 'date', true, '' ),
				array( 'exclusions', 'Exclusions', 'textarea', true, 'What it doesn’t cover: delivery apps, holidays, other offers, one per table. Write “None” if nothing.' ),
				array( 'fine', 'Fine print', 'textarea', false, 'Anything else people need to know at the counter.' ),
				array( 'flyer', 'Flyer for the video', 'file', 'vip', 'Required for a VIP Woo-pon: Tina shows it in her monthly video. A JPG, PNG or PDF, up to 10 MB; portrait (9:16) fills a phone screen best. Put the deal, your business name and the dates on it.' ),
				array( 'phone', 'Phone to contact', 'tel', true, 'So Tina can call if something needs checking. Never shown.' ),
				array( 'email', 'Email', 'email', true, 'For the confirmation. Never shown.' ),
			),
		),
		'event'   => array(
			'title'  => 'Tell us about it',
			'intro'  => 'Real details only; we check before it goes up.',
			'button' => 'Send the event',
			'fields' => array(
				array( 'name', 'Event name', 'text', true, '' ),
				array( 'date', 'Date', 'date', true, '' ),
				array( 'time', 'Time', 'text', true, 'For example: 2–5pm.' ),
				array( 'venue', 'Where', 'text', true, 'The place and the neighborhood.' ),
				array( 'kind', 'What kind of event', 'select', true, '', array( '' => 'Pick one', 'food' => 'Food or drink', 'market' => 'Market or fair', 'music' => 'Music or a show', 'art' => 'Art or a class', 'family' => 'Family', 'other' => 'Something else' ) ),
				array( 'price', 'Price', 'text', false, 'Free, $35, pay your own way…' ),
				array( 'about', 'What it is', 'textarea', false, 'Two lines is plenty.' ),
				array( 'link', 'Link for details or tickets', 'url', false, '' ),
				array( 'tier', 'Listing', 'select', true, 'We confirm the price by email before anything is charged.', array( 'free' => 'Free listing', 'premium' => 'Premium listing: ' . ltef_money( $s['event_premium'] ) . ' a month' ) ),
				array( 'phone', 'Phone to contact', 'tel', false, 'Only if we need to check a detail. Never shown.' ),
				array( 'email', 'Your email', 'email', true, 'Only to ask about the event. Never shown.' ),
			),
		),
		'job'     => array(
			'title'  => 'Post a job',
			'intro'  => 'Restaurants and food businesses around Worcester and Central Mass. ' . ltef_money( $s['job_post'] ) . ' a month; we confirm by email before anything is charged.',
			'button' => 'Post the job',
			'fields' => array(
				array( 'business', 'Business', 'text', true, '' ),
				array( 'role', 'The job', 'text', true, 'Host, server, line cook, manager…' ),
				array( 'job_kind', 'Kind of job', 'select', true, '', array( '' => 'Pick one', 'front' => 'Front of house', 'kitchen' => 'Kitchen', 'manage' => 'Management' ) ),
				array( 'hours_type', 'Hours', 'select', true, '', array( '' => 'Pick one', 'ft' => 'Full-time', 'pt' => 'Part-time', 'seasonal' => 'Seasonal' ) ),
				array( 'pay', 'Pay', 'text', false, 'A range helps people apply.' ),
				array( 'how', 'How to apply', 'text', true, 'An email, a link or “walk in Tuesday 2–4pm”.' ),
				array( 'phone', 'Phone to contact', 'tel', true, 'Only to check the post. Never shown.' ),
				array( 'email', 'Email', 'email', true, 'Never shown.' ),
			),
		),
		'suggest' => array(
			'title'  => 'Suggest a spot',
			'intro'  => 'Where should Tina eat next? Owners can suggest their own place too. It goes on her list to try, with everyone else’s, and it can’t be paid for.',
			'button' => 'Send it to Tina',
			'fields' => array(
				array( 'place', 'The place', 'text', true, 'Name and street, if you know it.' ),
				array( 'why', 'What should she order?', 'textarea', false, '' ),
				array( 'email', 'Your email', 'email', false, 'Only if you’d like to hear when she goes.' ),
			),
		),
		'listing' => array(
			'title'  => 'Fix my listing',
			'intro'  => 'Hours, menu, links, an opening or a closing. Being listed is free, forever.',
			'button' => 'Send the fix',
			'fields' => array(
				array( 'business', 'Your business', 'text', true, 'Not on the site yet? Type the name and street.' ),
				array( 'fix', 'What should change', 'textarea', true, 'New hours, a menu link, a dish that’s gone…' ),
				array( 'phone', 'Phone', 'tel', false, 'We confirm it’s yours by calling the number on the listing.' ),
				array( 'email', 'Email', 'email', true, '' ),
			),
		),
		'special' => array(
			'title'  => 'Send a special',
			'intro'  => 'A weekly special or a new dish: tell us the place, the day and the deal. Owners add their own, free.',
			'button' => 'Send the special',
			'fields' => array(
				array( 'business', 'The place', 'text', true, '' ),
				array( 'what', 'The special or dish', 'text', true, 'Like BOGO pizza, or a new pandan latte.' ),
				array( 'when', 'When', 'text', true, 'Every Tuesday, or through Dec 15.' ),
				array( 'email', 'Email', 'email', false, '' ),
			),
		),
		'photo'   => array(
			'title'  => 'Share your bite',
			'intro'  => 'A photo of what you ate somewhere Tina posted, or a photo you took with Tina. Credited to you, food first.',
			'button' => 'Send your photo',
			'fields' => array(
				array( 'handle', 'Your name or handle', 'text', true, 'Shown with the photo.' ),
				array( 'place', 'Where it was', 'text', true, '' ),
				array( 'photo', 'The photo', 'file', true, 'A JPG or PNG, up to 10 MB.' ),
				array( 'consent', 'Everyone in the photo is OK with it being posted.', 'checkbox', true, '' ),
				array( 'email', 'Email', 'email', false, 'Only to ask about the photo.' ),
			),
		),
		'signup'  => array(
			'title'  => 'Get Monthly Crumbs',
			'intro'  => 'Worcester’s best food deals and Tina’s latest picks, once a month.',
			'button' => 'Get Monthly Crumbs',
			'note'   => 'One email on the first Sunday of the month, with that month’s Woo-pons inside. Used for the list and nothing else; unsubscribe in one tap.',
			'fields' => array(
				array( 'email', 'Email', 'email', true, '' ),
			),
		),
		'contact' => array(
			'title'  => 'Get in touch',
			'intro'  => 'Tell us what you want to reach and when. We’ll reply with what fits and what it costs.',
			'button' => 'Send',
			'note'   => 'Just want Tina to try your place? That’s editorial: suggest a spot instead. It never goes through this form.',
			'fields' => array(
				array( 'name', 'Your name', 'text', true, '' ),
				array( 'business', 'Business or brand', 'text', true, '' ),
				array( 'email', 'Email', 'email', true, '' ),
				array( 'about', 'Interested in', 'select', true, '', $contact_opts ),
				array( 'budget', 'Budget', 'text', false, 'A range is fine.' ),
				array( 'message', 'What you have in mind', 'textarea', true, 'For a hosted visit: whether you’d cover the meal.' ),
			),
		),
	);
}

/** The form, or its thank-you once sent. */
function ltef_block_submit_form( $attrs ) {
	$key   = sanitize_key( $attrs['form'] ?? 'woopon' );
	$forms = ltef_forms();
	if ( ! isset( $forms[ $key ] ) ) {
		return '';
	}
	$f     = $forms[ $key ];
	$uid   = 'ltef-' . $key;
	$sent  = isset( $_GET['ltef_sent'] ) && sanitize_key( wp_unslash( $_GET['ltef_sent'] ) ) === $key; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$error = isset( $_GET['ltef_error'], $_GET['ltef_form'] ) && sanitize_key( wp_unslash( $_GET['ltef_form'] ) ) === $key ? sanitize_text_field( wp_unslash( $_GET['ltef_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $sent ) {
		$days = (int) ltef_setting( 'reply_days' );
		if ( 'signup' === $key ) {
			return ltef_wrap( '<div class="panel ltef-sent" id="' . esc_attr( $uid ) . '" tabindex="-1"><p class="h-m">You’re on the list for Monthly Crumbs.</p><p>The next one lands on the first Sunday of the month.</p></div>' );
		}
		return ltef_wrap( '<div class="panel ltef-sent" id="' . esc_attr( $uid ) . '" tabindex="-1"><p class="h-m">Sent. Thank you.</p><p>Tina reads everything, usually within ' . esc_html( $days ) . ' days. Nothing goes up until she approves it.</p></div>' );
	}
	$about = isset( $_GET['about'] ) ? sanitize_key( wp_unslash( $_GET['about'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$has_file = false;
	$fields = '';
	foreach ( $f['fields'] as $fd ) {
		list( $name, $label, $type, $req ) = $fd;
		$hint = $fd[4] ?? '';
		$opts = $fd[5] ?? array();
		$id   = $uid . '-' . $name;
		$desc = $hint ? ' aria-describedby="' . esc_attr( $id ) . '-h"' : '';
		$r    = true === $req ? ' required' : '';
		$opt  = false === $req ? ' <span class="ltef-field__opt">(optional)</span>' : ( 'vip' === $req ? ' <span class="ltef-field__opt">(required for a VIP Woo-pon)</span>' : '' );
		$auto = array( 'email' => 'email', 'phone' => 'tel', 'business' => 'organization', 'name' => 'name' );
		$ac   = isset( $auto[ $name ] ) ? ' autocomplete="' . $auto[ $name ] . '"' : '';
		$h    = $hint ? '<p class="ltef-field__hint" id="' . esc_attr( $id ) . '-h">' . esc_html( $hint ) . '</p>' : '';
		if ( 'checkbox' === $type ) {
			$fields .= '<div class="ltef-field ltef-field--check"><label for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . $r . '> ' . esc_html( $label ) . '</label></div>';
			continue;
		}
		if ( 'select' === $type ) {
			$ctl = '<select class="ltef-input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $r . $desc . '>';
			foreach ( $opts as $v => $t ) {
				$ctl .= '<option value="' . esc_attr( $v ) . '"' . selected( 'about' === $name && $about === $v, true, false ) . '>' . esc_html( $t ) . '</option>';
			}
			$ctl .= '</select>';
		} elseif ( 'textarea' === $type ) {
			$ctl = '<textarea class="ltef-input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="3"' . $r . $desc . '></textarea>';
		} elseif ( 'file' === $type ) {
			$has_file = true;
			$ctl      = '<input class="ltef-input" type="file" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" accept="image/jpeg,image/png' . ( 'flyer' === $name ? ',application/pdf' : '' ) . '"' . $r . $desc . '>';
		} else {
			$ctl = '<input class="ltef-input" type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $r . $desc . $ac . '>';
		}
		$vip_only = 'vip' === $req ? ' data-vip-only' : '';
		$fields  .= '<div class="ltef-field"' . $vip_only . '><label class="ltef-field__label" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . $opt . '</label>' . $h . $ctl . '</div>';
	}
	$out  = '<form class="panel ltef-form" id="' . esc_attr( $uid ) . '" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . ( $has_file ? ' enctype="multipart/form-data"' : '' ) . ' aria-labelledby="' . esc_attr( $uid ) . '-h">';
	$out .= '<h2 id="' . esc_attr( $uid ) . '-h" class="h-m" style="font-size: 30px;">' . esc_html( $f['title'] ) . '</h2><p style="margin: 0;">' . esc_html( $f['intro'] ) . '</p>';
	if ( $error ) {
		$out .= '<p class="ltef-form__error" role="alert">' . esc_html( $error ) . '</p>';
	}
	$out .= '<div class="form-grid">' . $fields . '</div>';
	$out .= '<input type="hidden" name="action" value="ltef_submit"><input type="hidden" name="ltef_form" value="' . esc_attr( $key ) . '">';
	$out .= '<input type="hidden" name="ltef_back" value="' . esc_attr( remove_query_arg( array( 'ltef_sent', 'ltef_error', 'ltef_form' ) ) ) . '">';
	$out .= '<input type="hidden" name="ltef_t" value="' . esc_attr( time() . '.' . wp_hash( time() . $key ) ) . '">';
	$out .= '<p class="ltef-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="ltef_website" tabindex="-1" autocomplete="off"></label></p>';
	$out .= wp_nonce_field( 'ltef_submit_' . $key, '_ltef_nonce', true, false );
	$out .= '<button class="ltef-btn" type="submit">' . esc_html( $f['button'] ) . '</button>';
	if ( ! empty( $f['note'] ) ) {
		$out .= '<p class="meta" style="margin: 0; font-size: 13px;">' . esc_html( $f['note'] ) . '</p>';
	}
	$out .= '</form>';
	return ltef_wrap( $out );
}

add_action( 'admin_post_nopriv_ltef_submit', 'ltef_handle_submit' );
add_action( 'admin_post_ltef_submit', 'ltef_handle_submit' );

function ltef_handle_submit() {
	$key   = isset( $_POST['ltef_form'] ) ? sanitize_key( wp_unslash( $_POST['ltef_form'] ) ) : '';
	$forms = ltef_forms();
	$back  = isset( $_POST['ltef_back'] ) ? esc_url_raw( wp_unslash( $_POST['ltef_back'] ) ) : home_url( '/' );
	$back  = wp_validate_redirect( $back, home_url( '/' ) );
	$fail  = function ( $msg ) use ( $back, $key ) {
		wp_safe_redirect( add_query_arg( array( 'ltef_error' => rawurlencode( $msg ), 'ltef_form' => $key ), $back ) . '#ltef-' . $key );
		exit;
	};
	if ( ! isset( $forms[ $key ] ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
	if ( ! isset( $_POST['_ltef_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ltef_nonce'] ) ), 'ltef_submit_' . $key ) ) {
		$fail( 'That form timed out. Please send it again.' );
	}
	// a bot fills the hidden field or sends in under three seconds
	$t = isset( $_POST['ltef_t'] ) ? explode( '.', sanitize_text_field( wp_unslash( $_POST['ltef_t'] ) ) ) : array();
	if ( ! empty( $_POST['ltef_website'] ) || count( $t ) !== 2 || ! hash_equals( wp_hash( $t[0] . $key ), $t[1] ) || time() - (int) $t[0] < 3 ) {
		wp_safe_redirect( add_query_arg( 'ltef_sent', $key, $back ) . '#ltef-' . $key );
		exit;
	}
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rkey  = 'ltef_rate_' . md5( $ip );
	$count = (int) get_transient( $rkey );
	if ( $count >= 12 ) {
		$fail( 'Lots of forms from here in the last hour. Please try again a little later.' );
	}
	set_transient( $rkey, $count + 1, HOUR_IN_SECONDS );

	$v = array();
	foreach ( $forms[ $key ]['fields'] as $fd ) {
		list( $name, $label, $type, $req ) = $fd;
		if ( 'file' === $type ) {
			continue;
		}
		$raw = isset( $_POST[ $name ] ) ? wp_unslash( $_POST[ $name ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$val = 'textarea' === $type ? sanitize_textarea_field( $raw ) : ( 'email' === $type ? sanitize_email( $raw ) : ( 'url' === $type ? esc_url_raw( $raw ) : sanitize_text_field( $raw ) ) );
		if ( isset( $fd[5] ) && $fd[5] && '' !== $val && ! array_key_exists( $val, $fd[5] ) ) {
			$val = '';
		}
		if ( true === $req && '' === $val ) {
			$fail( $label . ' is missing.' );
		}
		if ( 'email' === $type && '' !== $raw && ! is_email( $val ) ) {
			$fail( 'That email doesn’t look right.' );
		}
		if ( 'date' === $type && '' !== $val && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $val ) ) {
			$fail( $label . ' needs a date.' );
		}
		$v[ $name ] = $val;
	}
	if ( 'woopon' === $key && $v['end'] < $v['start'] ) {
		$fail( 'The end date is before the start date.' );
	}

	// uploads: the VIP flyer, a photo
	$files = array();
	foreach ( $forms[ $key ]['fields'] as $fd ) {
		if ( 'file' !== $fd[2] ) {
			continue;
		}
		$name   = $fd[0];
		$needed = true === $fd[3] || ( 'vip' === $fd[3] && 'vip' === ( $v['tier'] ?? '' ) );
		$has    = ! empty( $_FILES[ $name ]['name'] ) && UPLOAD_ERR_OK === (int) $_FILES[ $name ]['error'];
		if ( $needed && ! $has ) {
			$fail( 'flyer' === $name ? 'A VIP Woo-pon needs a flyer for Tina’s video.' : $fd[1] . ' is missing.' );
		}
		if ( $has ) {
			if ( (int) $_FILES[ $name ]['size'] > 10 * MB_IN_BYTES ) {
				$fail( $fd[1] . ' is over 10 MB.' );
			}
			$files[ $name ] = true;
		}
	}

	$title = '';
	$status = 'signup' === $key ? 'publish' : 'pending';
	$meta  = array();
	switch ( $key ) {
		case 'woopon':
			$type  = 'ltef_woopon';
			$title = $v['deal'];
			$meta  = array( 'business' => $v['business'], 'tier' => $v['tier'], 'kind' => $v['kind'], 'start' => $v['start'], 'end' => $v['end'], 'exclusions' => $v['exclusions'], 'fine' => $v['fine'], 'contact_phone' => $v['phone'], 'contact_email' => $v['email'] );
			break;
		case 'event':
			$type  = 'ltef_event';
			$title = $v['name'];
			$meta  = array( 'date' => $v['date'], 'time' => $v['time'], 'venue' => $v['venue'], 'kind' => $v['kind'], 'price_text' => $v['price'], 'link' => $v['link'], 'premium' => 'premium' === $v['tier'], 'contact_phone' => $v['phone'], 'contact_email' => $v['email'] );
			break;
		case 'job':
			$type  = 'ltef_job';
			$title = $v['role'];
			$meta  = array( 'business' => $v['business'], 'job_kind' => $v['job_kind'], 'hours_type' => $v['hours_type'], 'pay' => $v['pay'], 'how_to_apply' => $v['how'], 'until' => wp_date( 'Y-m-d', strtotime( '+1 month' ) ), 'contact_phone' => $v['phone'], 'contact_email' => $v['email'] );
			break;
		default:
			$type   = 'ltef_submission';
			$labels = array( 'signup' => 'Monthly Crumbs signup', 'suggest' => 'Suggested spot', 'listing' => 'Listing fix', 'special' => 'Special or new dish', 'photo' => 'Photo', 'contact' => 'Work with Tina' );
			$title  = $labels[ $key ] . ': ' . ( $v['place'] ?? $v['business'] ?? $v['handle'] ?? $v['name'] ?? $v['email'] ?? '' );
	}
	$post_id = wp_insert_post(
		array(
			'post_type'    => $type,
			'post_status'  => $status,
			'post_title'   => wp_strip_all_tags( $title ),
			'post_content' => 'event' === $key && $v['about'] ? $v['about'] : '',
			'post_excerpt' => 'event' === $key && $v['about'] ? $v['about'] : '',
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		$fail( 'Something went wrong on our side. Please try again.' );
	}
	foreach ( $meta as $k => $val ) {
		update_post_meta( $post_id, 'ltef_' . $k, $val );
	}
	update_post_meta( $post_id, 'ltef_form', $key );
	update_post_meta( $post_id, 'ltef_fields', $v );
	update_post_meta( $post_id, 'ltef_sent_at', current_time( 'mysql' ) );
	$who = $v['business'] ?? ( $v['place'] ?? '' );
	if ( ltef_on_blocklist( $who ) ) {
		update_post_meta( $post_id, 'ltef_flag_blocklist', 1 );
	}

	if ( $files ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		foreach ( array_keys( $files ) as $name ) {
			$mimes = array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png' );
			if ( 'flyer' === $name ) {
				$mimes['pdf'] = 'application/pdf';
			}
			$att = media_handle_upload( $name, $post_id, array(), array( 'test_form' => false, 'mimes' => $mimes ) );
			if ( is_wp_error( $att ) ) {
				wp_trash_post( $post_id );
				$fail( 'That file didn’t upload: use a JPG, PNG' . ( 'flyer' === $name ? ' or PDF' : '' ) . ' under 10 MB.' );
			}
			update_post_meta( $post_id, 'ltef_' . $name, (int) $att );
		}
	}

	/** Lets other parts of the plugin act on a new submission (Monthly Crumbs signups go to Brevo). */
	do_action( 'ltef_submitted', $key, $post_id, $v );

	$to = ltef_setting( 'notify_email' );
	if ( $to && 'signup' !== $key ) {
		$lines = array();
		foreach ( $v as $k => $val ) {
			$lines[] = ucfirst( str_replace( '_', ' ', $k ) ) . ': ' . ( is_bool( $val ) ? ( $val ? 'yes' : 'no' ) : $val );
		}
		wp_mail( $to, 'New for review: ' . wp_strip_all_tags( $title ), implode( "\n", $lines ) . "\n\nReview it: " . admin_url( 'admin.php?page=ltef-review' ) );
	}
	wp_safe_redirect( add_query_arg( 'ltef_sent', $key, $back ) . '#ltef-' . $key );
	exit;
}
