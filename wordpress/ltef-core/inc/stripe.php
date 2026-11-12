<?php
/**
 * Card payments through Stripe.
 *
 * Paid forms (weekly and VIP Woo-pons, premium events, job posts): the business adds a card on
 * Stripe's own checkout page and the amount is held, not charged. Approving in the review queue
 * charges it and puts the listing up; declining releases the hold. Anything approved without a
 * card, and custom deals (ad spots, Monthly Crumbs ads, sponsorships), get a Stripe pay link by
 * email instead. Stripe tells the site what happened through a signed webhook, and every payment
 * and Stripe's fee land in Money on their own.
 *
 * Card numbers never touch this site: they're typed on Stripe's pages only.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ Settings and the API */

function ltef_stripe() {
	$saved = get_option( 'ltef_stripe', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), array( 'key' => '', 'on' => false, 'hooks' => array() ) );
}

/** 'test' or 'live', from the saved key. */
function ltef_stripe_mode() {
	$k = ltef_stripe()['key'];
	return preg_match( '/^(sk|rk)_live_/', $k ) ? 'live' : 'test';
}

/** Taking card payments on the paid forms right now. */
function ltef_stripe_on() {
	$s = ltef_stripe();
	return $s['on'] && $s['key'] && ! empty( $s['hooks'][ ltef_stripe_mode() ]['secret'] );
}

/** Nested params as Stripe's form encoding: line_items[0][price_data][currency]=usd. */
function ltef_stripe_flat( $a, $prefix = '' ) {
	$out = array();
	foreach ( $a as $k => $v ) {
		$key = '' === $prefix ? $k : $prefix . '[' . $k . ']';
		if ( is_array( $v ) ) {
			$out += ltef_stripe_flat( $v, $key );
		} else {
			$out[ $key ] = is_bool( $v ) ? ( $v ? 'true' : 'false' ) : (string) $v;
		}
	}
	return $out;
}

/** One call to Stripe. The decoded object, or a WP_Error with Stripe's own message. */
function ltef_stripe_api( $method, $path, $params = array(), $key = '' ) {
	$key = $key ? $key : ltef_stripe()['key'];
	if ( ! $key ) {
		return new WP_Error( 'ltef_stripe_key', 'No Stripe key saved yet.' );
	}
	$url  = 'https://api.stripe.com/v1/' . ltrim( $path, '/' );
	$args = array(
		'method'  => $method,
		'timeout' => 20,
		'headers' => array( 'Authorization' => 'Bearer ' . $key ),
	);
	if ( $params ) {
		$q = http_build_query( ltef_stripe_flat( $params ), '', '&', PHP_QUERY_RFC1738 );
		if ( 'GET' === $method ) {
			$url .= '?' . $q;
		} else {
			$args['body']                    = $q;
			$args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
		}
	}
	$r = wp_remote_request( $url, $args );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$code = (int) wp_remote_retrieve_response_code( $r );
	$body = json_decode( (string) wp_remote_retrieve_body( $r ), true );
	if ( $code >= 300 || ! is_array( $body ) ) {
		$msg = is_array( $body ) && ! empty( $body['error']['message'] ) ? $body['error']['message'] : 'Stripe answered ' . $code . '.';
		return new WP_Error( 'ltef_stripe', $msg );
	}
	return $body;
}

/** Dollars from the price settings to cents. */
function ltef_cents( $dollars ) {
	return (int) round( (float) $dollars * 100 );
}

/* ------------------------------------------------------------------ What each paid form costs */

/**
 * The charge for a submission, or null when there's nothing to pay.
 * [ 'cents' => int, 'label' => what it's for, 'source' => Money income source ].
 */
function ltef_paid_item( $key, $v ) {
	$s = ltef_settings();
	if ( 'woopon' === $key && 'weekly' === ( $v['tier'] ?? '' ) ) {
		$days  = max( 1, (int) round( ( strtotime( $v['end'] ) - strtotime( $v['start'] ) ) / DAY_IN_SECONDS ) + 1 );
		$weeks = (int) ceil( $days / 7 );
		return array( 'cents' => ltef_cents( $s['woopon_weekly'] ) * $weeks, 'label' => sprintf( 'Woo-pon for %s: %s (%d %s)', $v['business'], $v['deal'], $weeks, 1 === $weeks ? 'week' : 'weeks' ), 'source' => 'woopon' );
	}
	if ( 'woopon' === $key && 'vip' === ( $v['tier'] ?? '' ) ) {
		return array( 'cents' => ltef_cents( $s['woopon_vip'] ), 'label' => sprintf( 'VIP Woo-pon for %s: %s', $v['business'], $v['deal'] ), 'source' => 'vip' );
	}
	if ( 'event' === $key && 'premium' === ( $v['tier'] ?? '' ) ) {
		return array( 'cents' => ltef_cents( $s['event_premium'] ), 'label' => sprintf( 'Premium event listing: %s (1 month)', $v['name'] ), 'source' => 'event' );
	}
	if ( 'job' === $key ) {
		$m = max( 1, min( 3, (int) ( $v['months'] ?? 1 ) ) );
		return array( 'cents' => ltef_cents( $s['job_post'] ) * $m, 'label' => sprintf( 'Job post for %s: %s (%d %s)', $v['business'], $v['role'], $m, 1 === $m ? 'month' : 'months' ), 'source' => 'job' );
	}
	return null;
}

/** The payment record kept on a submission. */
function ltef_pay( $post_id ) {
	$p = get_post_meta( $post_id, 'ltef_payment', true );
	return is_array( $p ) ? $p : array();
}
function ltef_pay_set( $post_id, $changes ) {
	update_post_meta( $post_id, 'ltef_payment', array_merge( ltef_pay( $post_id ), $changes ) );
}

/* ------------------------------------------------------------------ At submit: hold the card */

/**
 * After a paid form is saved, send the business to Stripe to add a card. Returns the Stripe
 * checkout URL, or '' to carry on as before (payments off, nothing to pay, or Stripe unreachable).
 */
function ltef_stripe_hold_url( $key, $post_id, $v, $back ) {
	if ( ! ltef_stripe_on() ) {
		return '';
	}
	$item = ltef_paid_item( $key, $v );
	if ( ! $item || $item['cents'] < 50 ) {
		return '';
	}
	if ( ! preg_match( '#^https?://#', $back ) ) { // the form sends back a path; Stripe needs the full address
		$back = preg_replace( '#^(https?://[^/]+).*$#', '$1', home_url() ) . '/' . ltrim( $back, '/' );
	}
	ltef_pay_set( $post_id, $item + array( 'status' => 'awaiting_card', 'email' => $v['email'] ?? '', 'party' => $v['business'] ?? ( $v['name'] ?? '' ), 'mode' => ltef_stripe_mode() ) );
	$session = ltef_stripe_api(
		'POST',
		'checkout/sessions',
		array(
			'mode'                => 'payment',
			// Cards (with Apple Pay and Google Pay) and Link can be held until Tina approves; pay-later and bank methods can't.
			'payment_method_types' => array( 'card', 'link' ),
			'customer_email'      => $v['email'] ?? '',
			'client_reference_id' => (string) $post_id,
			'line_items'          => array( array( 'quantity' => 1, 'price_data' => array( 'currency' => 'usd', 'unit_amount' => $item['cents'], 'product_data' => array( 'name' => mb_substr( $item['label'], 0, 240 ) ) ) ) ),
			'payment_intent_data' => array(
				'capture_method' => 'manual',
				'description'    => mb_substr( $item['label'], 0, 240 ),
				'receipt_email'  => $v['email'] ?? '',
				'metadata'       => array( 'ltef_post' => $post_id ),
			),
			'custom_text'         => array( 'submit' => array( 'message' => 'Your card is only charged if Tina approves it. If she doesn’t, the hold is released.' ) ),
			'metadata'            => array( 'ltef_post' => $post_id, 'ltef_kind' => 'hold' ),
			'success_url'         => add_query_arg( array( 'ltef_sent' => $key, 'ltef_card' => 1 ), $back ) . '#ltef-' . $key,
			'cancel_url'          => add_query_arg( array( 'ltef_sent' => $key, 'ltef_card' => 0 ), $back ) . '#ltef-' . $key,
		)
	);
	if ( is_wp_error( $session ) || empty( $session['url'] ) ) {
		ltef_pay_set( $post_id, array( 'status' => 'no_card', 'error' => is_wp_error( $session ) ? $session->get_error_message() : 'No checkout link.' ) );
		return '';
	}
	ltef_pay_set( $post_id, array( 'session' => $session['id'] ) );
	return $session['url'];
}

/* ------------------------------------------------------------------ In the review queue */

/** A short line for the review queue's Flags column. */
function ltef_pay_flag( $post_id ) {
	$p = ltef_pay( $post_id );
	if ( ! $p ) {
		return '';
	}
	$amt = ltef_money( $p['cents'] / 100 );
	$map = array(
		'awaiting_card' => 'Payment: ' . $amt . ', card not added yet',
		'no_card'       => 'Payment: ' . $amt . ', no card. Approving emails them a pay link',
		'held'          => '<strong>Card held: ' . $amt . '</strong>. Approving charges it',
		'expired'       => 'Payment: ' . $amt . ', the card hold ran out. Approving emails a pay link',
		'link_sent'     => '<strong>Approved, waiting for payment</strong> (' . $amt . '): it goes live when they pay',
		'paid'          => 'Paid ' . $amt,
	);
	return $map[ $p['status'] ] ?? '';
}

/**
 * Approving a paid submission. Returns [ publish now?, message for Tina ].
 */
function ltef_stripe_on_approve( $post_id ) {
	$p = ltef_pay( $post_id );
	if ( ! $p || in_array( $p['status'] ?? '', array( 'paid', 'released' ), true ) ) {
		return array( true, '' );
	}
	if ( 'link_sent' === $p['status'] ) {
		return array( false, 'Still waiting for their payment. The pay link is on the Payments page if they need it again.' );
	}
	if ( 'held' === $p['status'] && ! empty( $p['intent'] ) ) {
		$pi = ltef_stripe_api( 'POST', 'payment_intents/' . rawurlencode( $p['intent'] ) . '/capture' );
		if ( ! is_wp_error( $pi ) && 'succeeded' === ( $pi['status'] ?? '' ) ) {
			ltef_pay_set( $post_id, array( 'status' => 'paid', 'paid_at' => current_time( 'mysql' ) ) );
			ltef_stripe_log_payment( (int) ( $pi['amount_received'] ?? $p['cents'] ), $p['source'], $p['party'], $p['label'], $pi['latest_charge'] ?? '' );
			return array( true, 'Charged ' . ltef_money( (int) ( $pi['amount_received'] ?? $p['cents'] ) / 100 ) . ' and put it up.' );
		}
		ltef_pay_set( $post_id, array( 'status' => 'expired', 'error' => is_wp_error( $pi ) ? $pi->get_error_message() : 'Capture failed.' ) );
	}
	if ( ! ltef_stripe_on() ) {
		return array( true, 'Stripe is off, so nothing was charged. Collect the payment by hand.' );
	}
	// No card held (they skipped it, or the hold ran out): email a pay link and go live once it's paid.
	$link = ltef_stripe_pay_link( $p['cents'], $p['label'], array( 'ltef_post' => $post_id ) );
	if ( is_wp_error( $link ) ) {
		return array( false, 'Stripe couldn’t make a pay link: ' . $link->get_error_message() . ' It’s still waiting here.' );
	}
	ltef_pay_set( $post_id, array( 'status' => 'link_sent', 'link_id' => $link['id'], 'link' => $link['url'] ) );
	ltef_stripe_email_link( $p['email'], $p['label'], $p['cents'], $link['url'], true );
	return array( false, 'Approved. ' . ( $p['email'] ? 'A pay link went to ' . $p['email'] : 'There’s no email on it, so copy the pay link from the Payments page' ) . '; it goes live as soon as they pay.' );
}

/** Declining: release a held card or switch off an unpaid link, so nobody is charged. */
function ltef_stripe_on_decline( $post_id ) {
	$p = ltef_pay( $post_id );
	if ( ! $p ) {
		return;
	}
	if ( 'held' === $p['status'] && ! empty( $p['intent'] ) ) {
		ltef_stripe_api( 'POST', 'payment_intents/' . rawurlencode( $p['intent'] ) . '/cancel' );
		ltef_pay_set( $post_id, array( 'status' => 'released' ) );
	} elseif ( 'link_sent' === $p['status'] && ! empty( $p['link_id'] ) ) {
		ltef_stripe_api( 'POST', 'payment_links/' . rawurlencode( $p['link_id'] ), array( 'active' => false ) );
		ltef_pay_set( $post_id, array( 'status' => 'released' ) );
	}
}

/* ------------------------------------------------------------------ Pay links */

/** A one-time Stripe pay link for an amount. Returns the link object or a WP_Error. */
function ltef_stripe_pay_link( $cents, $label, $meta ) {
	$price = ltef_stripe_api( 'POST', 'prices', array( 'currency' => 'usd', 'unit_amount' => (int) $cents, 'product_data' => array( 'name' => mb_substr( $label, 0, 240 ) ) ) );
	if ( is_wp_error( $price ) ) {
		return $price;
	}
	return ltef_stripe_api(
		'POST',
		'payment_links',
		array(
			'line_items'   => array( array( 'price' => $price['id'], 'quantity' => 1 ) ),
			'metadata'     => $meta,
			'restrictions' => array( 'completed_sessions' => array( 'limit' => 1 ) ),
			'payment_intent_data' => array( 'description' => mb_substr( $label, 0, 240 ), 'metadata' => $meta ),
		)
	);
}

function ltef_stripe_email_link( $to, $label, $cents, $url, $approved ) {
	if ( ! is_email( $to ) ) {
		return false;
	}
	$body = ( $approved ? "Good news: Tina approved it.\n\n" : '' )
		. 'What it’s for: ' . $label . "\nAmount: " . ltef_money( $cents / 100 ) . "\n\nPay securely with Stripe here:\n" . $url
		. "\n\n" . ( $approved ? 'It goes up on the site as soon as it’s paid. ' : '' ) . "Stripe emails a receipt.\n\nQuestions? Just reply to this email.\n\nLive to Eat Food";
	return wp_mail( $to, ( $approved ? 'Approved: ' : 'Payment for ' ) . $label, $body, array( 'Reply-To: ' . ltef_brevo()['from_email'] ) );
}

/** Custom payment requests (ad spots, Crumbs ads, sponsorships) kept as private records. */
add_action(
	'init',
	function () {
		register_post_type( 'ltef_payreq', array( 'public' => false, 'show_ui' => false, 'label' => 'Payment requests', 'supports' => array( 'title' ), 'rewrite' => false, 'query_var' => false ) );
	}
);

/* ------------------------------------------------------------------ Money */

/**
 * Income for a payment, and Stripe's fee as an expense, so Money stays right on its own.
 * Logged once per payment: $ref (or the charge id) is kept on the row, and a repeat is skipped.
 */
function ltef_stripe_log_payment( $cents, $source, $party, $label, $charge_id, $ref = '' ) {
	$ref = $ref ? $ref : ( $charge_id ? 'charge:' . $charge_id : '' );
	if ( $ref && get_posts( array( 'post_type' => 'ltef_income', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => 'ltef_m_ref', 'meta_value' => $ref ) ) ) { // phpcs:ignore WordPress.DB.SlowDBQuery
		return;
	}
	$sources = ltef_money_sources();
	$source  = isset( $sources[ $source ] ) ? $source : 'other';
	wp_insert_post(
		array(
			'post_type'   => 'ltef_income',
			'post_status' => 'private',
			'post_title'  => $sources[ $source ] . ( $party ? ' · ' . $party : '' ),
			'meta_input'  => array( 'ltef_m_date' => wp_date( 'Y-m-d' ), 'ltef_m_cents' => (int) $cents, 'ltef_m_note' => $label . ' (Stripe)', 'ltef_m_source' => $source, 'ltef_m_method' => 'stripe', 'ltef_m_party' => $party, 'ltef_m_ref' => $ref ),
		)
	);
	if ( $charge_id ) {
		$ch  = ltef_stripe_api( 'GET', 'charges/' . rawurlencode( $charge_id ), array( 'expand' => array( 'balance_transaction' ) ) );
		$fee = is_wp_error( $ch ) ? 0 : (int) ( $ch['balance_transaction']['fee'] ?? 0 );
		if ( $fee > 0 ) {
			wp_insert_post(
				array(
					'post_type'   => 'ltef_expense',
					'post_status' => 'private',
					'post_title'  => ltef_money_categories()['fees'] . ' · Stripe',
					'meta_input'  => array( 'ltef_m_date' => wp_date( 'Y-m-d' ), 'ltef_m_cents' => $fee, 'ltef_m_note' => 'Stripe fee: ' . $label, 'ltef_m_category' => 'fees', 'ltef_m_paid_by' => 'business', 'ltef_m_payback' => 'no', 'ltef_m_party' => 'Stripe' ),
				)
			);
		}
	}
	if ( function_exists( 'ltef_money_clear_page_cache' ) ) {
		ltef_money_clear_page_cache();
	}
}

/* ------------------------------------------------------------------ The webhook */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ltef/v1',
			'/stripe',
			array(
				'methods'             => 'POST',
				'callback'            => 'ltef_stripe_webhook',
				'permission_callback' => '__return_true', // Stripe's signature is checked inside
			)
		);
	}
);

/** Stripe's signature header against the endpoint secret, within five minutes. */
function ltef_stripe_signed( $payload, $header, $secret ) {
	$t  = 0;
	$v1 = array();
	foreach ( explode( ',', (string) $header ) as $part ) {
		$kv = explode( '=', trim( $part ), 2 );
		if ( 2 === count( $kv ) ) {
			if ( 't' === $kv[0] ) {
				$t = (int) $kv[1];
			} elseif ( 'v1' === $kv[0] ) {
				$v1[] = $kv[1];
			}
		}
	}
	if ( ! $t || ! $v1 || abs( time() - $t ) > 300 || ! $secret ) {
		return false;
	}
	$want = hash_hmac( 'sha256', $t . '.' . $payload, $secret );
	foreach ( $v1 as $sig ) {
		if ( hash_equals( $want, $sig ) ) {
			return true;
		}
	}
	return false;
}

function ltef_stripe_webhook( WP_REST_Request $req ) {
	$payload = $req->get_body();
	$ok      = false;
	foreach ( ltef_stripe()['hooks'] as $hook ) {
		if ( ltef_stripe_signed( $payload, $req->get_header( 'stripe_signature' ), $hook['secret'] ?? '' ) ) {
			$ok = true;
			break;
		}
	}
	if ( ! $ok ) {
		return new WP_REST_Response( array( 'error' => 'bad signature' ), 400 );
	}
	$e    = json_decode( $payload, true );
	// Test-mode news after switching to live (or the other way round) is acknowledged and ignored.
	if ( is_array( $e ) && isset( $e['livemode'] ) && (bool) $e['livemode'] !== ( 'live' === ltef_stripe_mode() ) ) {
		return new WP_REST_Response( array( 'ok' => true, 'ignored' => 'other mode' ), 200 );
	}
	$seen = (array) get_option( 'ltef_stripe_seen', array() );
	if ( empty( $e['id'] ) || in_array( $e['id'], $seen, true ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
	update_option( 'ltef_stripe_seen', array_slice( array_merge( $seen, array( $e['id'] ) ), -300 ), false );
	$o = $e['data']['object'] ?? array();

	if ( 'checkout.session.completed' === $e['type'] ) {
		$post = (int) ( $o['metadata']['ltef_post'] ?? 0 );
		if ( 'hold' === ( $o['metadata']['ltef_kind'] ?? '' ) && $post ) {
			ltef_pay_set( $post, array( 'status' => 'held', 'intent' => $o['payment_intent'] ?? '', 'held_at' => current_time( 'mysql' ) ) );
		} elseif ( ! empty( $o['payment_link'] ) ) {
			ltef_stripe_link_paid( $o );
		}
	} elseif ( 'checkout.session.expired' === $e['type'] ) {
		$post = (int) ( $o['metadata']['ltef_post'] ?? 0 );
		if ( $post && 'awaiting_card' === ( ltef_pay( $post )['status'] ?? '' ) ) {
			ltef_pay_set( $post, array( 'status' => 'no_card' ) );
		}
	} elseif ( 'payment_intent.canceled' === $e['type'] ) {
		$post = (int) ( $o['metadata']['ltef_post'] ?? 0 );
		if ( $post && 'held' === ( ltef_pay( $post )['status'] ?? '' ) ) {
			ltef_pay_set( $post, array( 'status' => 'expired' ) ); // Stripe let the hold go after 7 days
		}
	} elseif ( 'charge.refunded' === $e['type'] ) {
		$amt = (int) ( $o['amount_refunded'] ?? 0 ) - (int) ( $e['data']['previous_attributes']['amount_refunded'] ?? 0 ); // only what's new this time
		if ( $amt > 0 ) {
			wp_insert_post(
				array(
					'post_type'   => 'ltef_income',
					'post_status' => 'private',
					'post_title'  => 'Refund · ' . ( $o['billing_details']['name'] ?? 'Stripe' ),
					'meta_input'  => array( 'ltef_m_date' => wp_date( 'Y-m-d' ), 'ltef_m_cents' => -$amt, 'ltef_m_note' => 'Refunded in Stripe: ' . ( $o['description'] ?? '' ), 'ltef_m_source' => 'other', 'ltef_m_method' => 'stripe', 'ltef_m_party' => $o['billing_details']['email'] ?? '' ),
				)
			);
		}
	}
	/** Lets other parts of the plugin act on a verified event (Enhanced subscriptions). */
	do_action( 'ltef_stripe_event', $e );
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/** A pay link was paid: a submission goes live, or a custom request is marked paid. Both land in Money. */
function ltef_stripe_link_paid( $session ) {
	$link  = $session['payment_link'];
	$cents = (int) ( $session['amount_total'] ?? 0 );
	$pi    = $session['payment_intent'] ?? '';
	$charge = '';
	if ( $pi ) {
		$intent = ltef_stripe_api( 'GET', 'payment_intents/' . rawurlencode( $pi ) );
		$charge = is_wp_error( $intent ) ? '' : ( $intent['latest_charge'] ?? '' );
	}
	$posts = get_posts( array( 'post_type' => array_keys( ltef_review_types() ), 'post_status' => 'pending', 'numberposts' => 1, 'meta_query' => array( array( 'key' => 'ltef_payment', 'value' => $link, 'compare' => 'LIKE' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $posts ) {
		$p = ltef_pay( $posts[0]->ID );
		ltef_pay_set( $posts[0]->ID, array( 'status' => 'paid', 'paid_at' => current_time( 'mysql' ) ) );
		wp_update_post( array( 'ID' => $posts[0]->ID, 'post_status' => 'publish' ) );
		ltef_stripe_log_payment( $cents ? $cents : $p['cents'], $p['source'], $p['party'], $p['label'], $charge );
		return;
	}
	$req = get_posts( array( 'post_type' => 'ltef_payreq', 'post_status' => 'private', 'numberposts' => 1, 'meta_key' => 'ltef_link_id', 'meta_value' => $link ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $req ) {
		$r = $req[0]->ID;
		update_post_meta( $r, 'ltef_status', 'paid' );
		update_post_meta( $r, 'ltef_paid_at', current_time( 'mysql' ) );
		ltef_stripe_log_payment( $cents ? $cents : (int) get_post_meta( $r, 'ltef_cents', true ), get_post_meta( $r, 'ltef_source', true ), get_post_meta( $r, 'ltef_party', true ), $req[0]->post_title, $charge );
	}
}

/* ------------------------------------------------------------------ Review queue → Payments */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'ltef-review', 'Payments', 'Payments', 'manage_options', 'ltef-payments', 'ltef_payments_page' );
	},
	21
);

/** What the site listens for. Bump the version when this changes, so a connected webhook is updated. */
const LTEF_STRIPE_EVENTS_V = 2;
function ltef_stripe_events() {
	return array( 'checkout.session.completed', 'checkout.session.expired', 'payment_intent.canceled', 'charge.refunded', 'invoice.paid', 'customer.subscription.updated', 'customer.subscription.deleted' );
}

/** A webhook made before the event list changed gets the new list (its secret stays the same). */
add_action(
	'admin_init',
	function () {
		$s    = ltef_stripe();
		$mode = ltef_stripe_mode();
		$h    = $s['hooks'][ $mode ] ?? array();
		if ( ! $s['key'] || empty( $h['id'] ) || (int) ( $h['v'] ?? 1 ) >= LTEF_STRIPE_EVENTS_V || ! current_user_can( 'manage_options' ) || get_transient( 'ltef_stripe_events_try' ) ) {
			return;
		}
		set_transient( 'ltef_stripe_events_try', 1, 10 * MINUTE_IN_SECONDS ); // don't hammer Stripe if it fails
		$r = ltef_stripe_api( 'POST', 'webhook_endpoints/' . rawurlencode( $h['id'] ), array( 'enabled_events' => ltef_stripe_events() ) );
		if ( ! is_wp_error( $r ) ) {
			$s['hooks'][ $mode ]['v'] = LTEF_STRIPE_EVENTS_V;
			update_option( 'ltef_stripe', $s, false );
		}
	}
);

/** Make (or remake) the webhook in Stripe for this site, for the saved key's mode. */
function ltef_stripe_connect() {
	$s    = ltef_stripe();
	$mode = ltef_stripe_mode();
	$url  = rest_url( 'ltef/v1/stripe' );
	if ( ! empty( $s['hooks'][ $mode ]['id'] ) ) {
		ltef_stripe_api( 'DELETE', 'webhook_endpoints/' . rawurlencode( $s['hooks'][ $mode ]['id'] ) );
	}
	$hook = ltef_stripe_api(
		'POST',
		'webhook_endpoints',
		array(
			'url'            => $url,
			'description'    => 'Live to Eat Food site',
			'enabled_events' => ltef_stripe_events(),
		)
	);
	if ( is_wp_error( $hook ) ) {
		return $hook;
	}
	$s['hooks'][ $mode ] = array( 'id' => $hook['id'], 'secret' => $hook['secret'], 'url' => $url, 'v' => LTEF_STRIPE_EVENTS_V );
	update_option( 'ltef_stripe', $s, false );
	return true;
}

add_action(
	'admin_post_ltef_payments',
	function () {
		check_admin_referer( 'ltef_payments' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$s   = ltef_stripe();
		$msg = '';
		$txt = function ( $k ) {
			return isset( $_POST[ $k ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		};
		if ( 'save' === $do ) {
			$new = $txt( 'key' );
			if ( '' !== $new ) {
				if ( ! preg_match( '/^(sk|rk)_(test|live)_[A-Za-z0-9]+$/', $new ) ) {
					wp_safe_redirect( admin_url( 'admin.php?page=ltef-payments&ltef_msg=' . rawurlencode( 'That doesn’t look like a Stripe secret key (it starts with sk_ or rk_). Nothing was changed.' ) ) );
					exit;
				}
				$s['key'] = $new;
			}
			$s['on'] = ! empty( $_POST['on'] );
			update_option( 'ltef_stripe', $s, false );
			if ( '' !== $new || empty( $s['hooks'][ ltef_stripe_mode() ]['secret'] ) || (int) ( $s['hooks'][ ltef_stripe_mode() ]['v'] ?? 1 ) < LTEF_STRIPE_EVENTS_V ) {
				$c   = $s['key'] ? ltef_stripe_connect() : true;
				$msg = is_wp_error( $c ) ? 'Saved, but Stripe said: ' . $c->get_error_message() : 'Saved and connected to Stripe (' . ltef_stripe_mode() . ' mode).';
			} else {
				$msg = 'Saved.';
			}
		} elseif ( 'forget' === $do ) {
			foreach ( $s['hooks'] as $mode => $h ) {
				if ( ! empty( $h['id'] ) && ltef_stripe_mode() === $mode ) {
					ltef_stripe_api( 'DELETE', 'webhook_endpoints/' . rawurlencode( $h['id'] ) );
				}
			}
			update_option( 'ltef_stripe', array( 'key' => '', 'on' => false, 'hooks' => array() ), false );
			$msg = 'Stripe disconnected. The paid forms work as before, with payments collected by hand.';
		} elseif ( 'request' === $do ) {
			$cents = function_exists( 'ltef_money_parse' ) ? ltef_money_parse( $txt( 'amount' ) ) : ltef_cents( $txt( 'amount' ) );
			$email = sanitize_email( $txt( 'email' ) );
			$what  = $txt( 'what' );
			$who   = $txt( 'who' );
			$src   = isset( ltef_money_sources()[ $txt( 'source' ) ] ) ? $txt( 'source' ) : 'other';
			if ( ! $cents || $cents < 50 || ! $what || ! $who ) {
				$msg = 'Fill in who it’s for, what it’s for and an amount of at least $0.50.';
			} else {
				$label = $what . ' · ' . $who;
				$link  = ltef_stripe_pay_link( $cents, $label, array( 'ltef_request' => '1' ) );
				if ( is_wp_error( $link ) ) {
					$msg = 'Stripe couldn’t make the link: ' . $link->get_error_message();
				} else {
					$rid = wp_insert_post( array( 'post_type' => 'ltef_payreq', 'post_status' => 'private', 'post_title' => $label ) );
					foreach ( array( 'ltef_cents' => $cents, 'ltef_email' => $email, 'ltef_party' => $who, 'ltef_source' => $src, 'ltef_link_id' => $link['id'], 'ltef_link' => $link['url'], 'ltef_status' => 'sent', 'ltef_mode' => ltef_stripe_mode() ) as $k => $val ) {
						update_post_meta( $rid, $k, $val );
					}
					$sent = $email && ltef_stripe_email_link( $email, $label, $cents, $link['url'], false );
					$msg  = 'Pay link made' . ( $sent ? ' and emailed to ' . $email : ' (no email sent: copy it from the list below)' ) . '.';
				}
			}
		} elseif ( 'cancel' === $do ) {
			$rid = isset( $_POST['req'] ) ? absint( $_POST['req'] ) : 0;
			if ( $rid && 'ltef_payreq' === get_post_type( $rid ) && 'sent' === get_post_meta( $rid, 'ltef_status', true ) ) {
				ltef_stripe_api( 'POST', 'payment_links/' . rawurlencode( get_post_meta( $rid, 'ltef_link_id', true ) ), array( 'active' => false ) );
				update_post_meta( $rid, 'ltef_status', 'canceled' );
				$msg = 'That pay link no longer works.';
			}
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ltef-payments&ltef_msg=' . rawurlencode( $msg ) ) );
		exit;
	}
);

/** On Payments: say when the webhook is missing the subscription events, so Enhanced upgrades are paused. */
add_action(
	'admin_notices',
	function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$s      = ltef_stripe();
		if ( ! $screen || false === strpos( $screen->id, 'ltef-payments' ) || ! $s['key'] || empty( $s['hooks'][ ltef_stripe_mode() ]['id'] ) || (int) ( $s['hooks'][ ltef_stripe_mode() ]['v'] ?? 1 ) >= LTEF_STRIPE_EVENTS_V ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>Enhanced upgrades are paused.</strong> Stripe’s connection to the site doesn’t send subscription updates yet. Press <em>Save</em> below to reconnect it; if that fails, the Stripe key may not be allowed to manage webhooks.</p></div>';
	}
);

function ltef_payments_page() {
	$s    = ltef_stripe();
	$mode = ltef_stripe_mode();
	$msg  = isset( $_GET['ltef_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['ltef_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$form = function ( $do, $inner, $button, $primary = true, $extra = '' ) {
		printf( '<form method="post" action="%s" %s><input type="hidden" name="action" value="ltef_payments"><input type="hidden" name="do" value="%s">%s%s', esc_url( admin_url( 'admin-post.php' ) ), $extra, esc_attr( $do ), wp_nonce_field( 'ltef_payments', '_wpnonce', true, false ), $inner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		submit_button( $button, $primary ? 'primary' : 'secondary', 'submit', false );
		echo '</form>';
	};
	echo '<div class="wrap"><h1>Payments</h1>';
	if ( $msg ) {
		echo '<div class="notice notice-info"><p>' . esc_html( $msg ) . '</p></div>';
	}
	$hooked = ! empty( $s['hooks'][ $mode ]['secret'] );
	if ( $s['key'] ) {
		echo '<p><strong>Stripe:</strong> ' . ( 'live' === $mode ? '<span class="ltef-chip ltef-chip--live">Live mode</span> Real cards are charged.' : '<span class="ltef-chip ltef-chip--test">Test mode</span> Only Stripe’s test cards work.' ) . ' Key ends in ' . esc_html( substr( $s['key'], -4 ) ) . '. ' . ( $hooked ? 'Stripe reports payments back to the site.' : '<strong>Not connected yet:</strong> press Save to finish.' ) . ' Card payments on the paid forms are <strong>' . ( ltef_stripe_on() ? 'on' : 'off' ) . '</strong>.</p>';
	} else {
		echo '<p>Not connected. In Stripe, open <strong>Developers → API keys</strong>, copy the <strong>Secret key</strong> (start with the test one, <code>sk_test_…</code>), and paste it below. The site sets up the rest.</p>';
	}
	$inner = '<table class="form-table" role="presentation">'
		. '<tr><th scope="row"><label for="ltef-sk">Stripe secret key</label></th><td><input type="password" id="ltef-sk" name="key" value="" class="regular-text" autocomplete="off" placeholder="' . esc_attr( $s['key'] ? 'Saved, ends in ' . substr( $s['key'], -4 ) : 'sk_test_…' ) . '"><p class="description">' . ( $s['key'] ? 'Leave blank to keep it. Paste the live key (sk_live_…) here when you’re ready for real payments.' : 'Kept on the server and never shown again.' ) . '</p></td></tr>'
		. '<tr><th scope="row">Card payments</th><td><label><input type="checkbox" name="on" value="1"' . checked( $s['on'], true, false ) . '> Take cards on the paid forms</label><p class="description">Weekly and VIP Woo-pons, premium events and job posts. The card is held when they send it in and only charged when you approve.</p></td></tr>'
		. '</table>';
	$form( 'save', $inner, 'Save' );
	if ( $s['key'] ) {
		$form( 'forget', '', 'Disconnect Stripe', false, 'style="margin-top:8px" onsubmit="return confirm(\'Disconnect Stripe? Paid forms go back to collecting payment by hand.\')"' );
	}

	if ( $s['key'] && $hooked ) {
		echo '<h2>Send a pay link</h2><p>For anything without a set price: ad spots, a Monthly Crumbs ad, a sponsorship, a paid visit. They get an email with a Stripe link; when they pay it shows below and in Money.</p>';
		$opts = '';
		foreach ( ltef_money_sources() as $k => $label ) {
			if ( ! in_array( $k, array( 'partner' ), true ) ) {
				$opts .= '<option value="' . esc_attr( $k ) . '"' . selected( 'ad', $k, false ) . '>' . esc_html( $label ) . '</option>';
			}
		}
		$inner = '<table class="form-table" role="presentation">'
			. '<tr><th scope="row"><label for="ltef-pw">Who</label></th><td><input type="text" id="ltef-pw" name="who" class="regular-text" required placeholder="Business name"></td></tr>'
			. '<tr><th scope="row"><label for="ltef-pe">Their email</label></th><td><input type="email" id="ltef-pe" name="email" class="regular-text" placeholder="Leave blank to copy the link yourself"></td></tr>'
			. '<tr><th scope="row"><label for="ltef-pf">What it’s for</label></th><td><input type="text" id="ltef-pf" name="what" class="regular-text" required placeholder="Monthly Crumbs ad, December"><p class="description">They see this on Stripe’s page and their receipt.</p></td></tr>'
			. '<tr><th scope="row"><label for="ltef-pa">Amount</label></th><td>$ <input type="text" id="ltef-pa" name="amount" class="small-text" required inputmode="decimal" placeholder="150"></td></tr>'
			. '<tr><th scope="row"><label for="ltef-ps">In Money as</label></th><td><select id="ltef-ps" name="source">' . $opts . '</select></td></tr>'
			. '</table>';
		$form( 'request', $inner, 'Make the pay link' );
	}

	$reqs = get_posts( array( 'post_type' => 'ltef_payreq', 'post_status' => 'private', 'numberposts' => 30 ) );
	$held = get_posts( array( 'post_type' => array_keys( ltef_review_types() ), 'post_status' => array( 'pending', 'publish' ), 'numberposts' => 30, 'meta_key' => 'ltef_payment', 'orderby' => 'modified' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $reqs || $held ) {
		echo '<h2>Recent payments</h2><table class="widefat striped"><thead><tr><th>Date</th><th>For</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>';
		foreach ( $held as $p ) {
			$pay = ltef_pay( $p->ID );
			$st  = array( 'awaiting_card' => 'Card not added', 'no_card' => 'No card', 'held' => 'Card held, waiting for Tina', 'expired' => 'Hold ran out', 'link_sent' => 'Pay link sent', 'paid' => 'Paid', 'released' => 'Declined, nothing charged' );
			printf( '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', esc_html( get_the_date( 'M j', $p ) ), esc_html( $pay['label'] ?? $p->post_title ), esc_html( ltef_money( ( $pay['cents'] ?? 0 ) / 100 ) ), esc_html( $st[ $pay['status'] ?? '' ] ?? '' ), ! empty( $pay['link'] ) && 'link_sent' === ( $pay['status'] ?? '' ) ? '<a href="' . esc_url( $pay['link'] ) . '" target="_blank" rel="noopener">Pay link</a>' : '' );
		}
		foreach ( $reqs as $r ) {
			$st     = get_post_meta( $r->ID, 'ltef_status', true );
			$cancel = '';
			if ( 'sent' === $st ) {
				ob_start();
				$form( 'cancel', '<input type="hidden" name="req" value="' . (int) $r->ID . '">', 'Cancel link', false, 'style="display:inline"' );
				$cancel = ob_get_clean();
			}
			printf( '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s %s</td></tr>', esc_html( get_the_date( 'M j', $r ) ), esc_html( $r->post_title ), esc_html( ltef_money( (int) get_post_meta( $r->ID, 'ltef_cents', true ) / 100 ) ), esc_html( array( 'sent' => 'Link sent', 'paid' => 'Paid', 'canceled' => 'Canceled' )[ $st ] ?? $st ), 'sent' === $st ? '<a href="' . esc_url( get_post_meta( $r->ID, 'ltef_link', true ) ) . '" target="_blank" rel="noopener">Pay link</a>' : '', $cancel ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</tbody></table>';
	}
	echo '<h2>How it works</h2><ol>'
		. '<li>Someone sends a weekly or VIP Woo-pon, a premium event or a job post. After the form, Stripe’s own page asks for their card and holds the amount. Card numbers never touch this site.</li>'
		. '<li>It waits in the review queue marked “Card held”. <strong>Approve</strong> charges the card and puts it up. <strong>Decline</strong> releases the hold, so they’re never charged.</li>'
		. '<li>A hold lasts 7 days. If it runs out, or they skipped the card, Approve emails them a pay link instead and it goes up once they pay.</li>'
		. '<li>Every payment goes into Money as income, with Stripe’s fee as an expense. Refunds you make in Stripe show up as negative income.</li>'
		. '</ol></div>';
}
