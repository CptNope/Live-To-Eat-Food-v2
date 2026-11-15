<?php
/**
 * Local supporters: businesses that chip in every month to keep Live to Eat Food going.
 *
 * A business signs up from For businesses → Become a local supporter. It waits in the review queue like
 * everything else (the don't-work-with list is checked). Approving emails them a link to Stripe for the
 * monthly plan (Money → Prices, "Local supporter"); "Approve free" makes them a supporter at no charge.
 * Once paid (or free), they're thanked: a Local supporter label on their place page, the supporters list
 * on For businesses and About Tina, a line in the footer, and a ready-to-paste block for Monthly Crumbs.
 *
 * Supporting is a thank-you, not an ad and not a review: it never changes Tina's take, her verdicts or any
 * ranking, and every place it shows says it's paid.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ The record */

add_action(
	'init',
	function () {
		register_post_type(
			'ltef_supporter',
			array(
				'labels'          => array_merge( ltef_labels( 'Local supporter', 'Local supporters' ), array( 'all_items' => 'Local supporters' ) ),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'ltef-review',
				'show_in_rest'    => true,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'description'     => 'Businesses that chip in every month to keep the site going. Thanked on the site and in Monthly Crumbs; never part of Tina’s take.',
			)
		);
		foreach ( ltef_fields( 'ltef_supporter' ) as $key => $def ) {
			register_post_meta(
				'ltef_supporter',
				'ltef_' . $key,
				array(
					'type'              => 'checkbox' === $def[1] ? 'boolean' : ( 'place' === $def[1] ? 'integer' : 'string' ),
					'single'            => true,
					'show_in_rest'      => empty( $def[4] ),
					'sanitize_callback' => 'ltef_sanitize_' . ( in_array( $def[1], array( 'checkbox', 'place', 'url', 'email' ), true ) ? $def[1] : 'text' ),
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	},
	21
);

add_filter(
	'ltef_fields',
	function ( $fields, $type ) {
		if ( 'ltef_supporter' !== $type ) {
			return $fields;
		}
		return array(
			'website'       => array( 'Website', 'url', 'Their name links here when they have no place page.' ),
			'place'         => array( 'Their place on the site', 'place', 'Optional. Their name links to it, and it shows a Local supporter label.' ),
			'comp'          => array( 'Given free by Tina', 'checkbox', 'No charge. They show as a supporter while this is on (until the date below, if set).' ),
			'comp_until'    => array( 'Free until', 'date', 'Leave blank for no end.' ),
			'contact_name'  => array( 'Contact name', 'text', 'Private.', null, true ),
			'contact_email' => array( 'Contact email', 'email', 'Private. The sign-up link and receipts go here.', null, true ),
			'contact_phone' => array( 'Contact phone', 'tel', 'Private.', null, true ),
		);
	},
	10,
	2
);

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'ltef-fields', 'The supporter', 'ltef_render_meta_box', 'ltef_supporter', 'normal', 'high' );
		add_meta_box( 'ltef-sup-plan', 'Plan', 'ltef_supporter_plan_box', 'ltef_supporter', 'side' );
	}
);

/** Billing state kept from Stripe: id, customer, status, period_end, cancel_at_period_end, mode, cents. */
function ltef_sup_sub( $id ) {
	$s = get_post_meta( $id, 'ltef_sup_sub', true );
	return is_array( $s ) ? $s : array();
}

function ltef_sup_sub_set( $id, $changes ) {
	update_post_meta( $id, 'ltef_sup_sub', array_merge( ltef_sup_sub( $id ), $changes ) );
}

add_filter(
	'is_protected_meta',
	function ( $protected, $key ) {
		return in_array( $key, array( 'ltef_sup_sub', 'ltef_sup_key', 'ltef_sup_since' ), true ) ? true : $protected;
	},
	10,
	2
);

/** The monthly price, in cents. */
function ltef_supporter_cents() {
	return function_exists( 'ltef_cents' ) ? ltef_cents( ltef_setting( 'supporter_price' ) ) : (int) round( 100 * (float) ltef_setting( 'supporter_price' ) );
}

/** "$15 a month". */
function ltef_supporter_price_text() {
	return ltef_money( ltef_setting( 'supporter_price' ) ) . ' a month';
}

/** Paid and current: Stripe says active, trialing, or past due within a week of the paid month. */
function ltef_supporter_paid( $id ) {
	$s = ltef_sup_sub( $id );
	if ( empty( $s['id'] ) || ( ! empty( $s['mode'] ) && function_exists( 'ltef_stripe_mode' ) && $s['mode'] !== ltef_stripe_mode() ) ) {
		return false;
	}
	$status = (string) ( $s['status'] ?? '' );
	if ( 'past_due' === $status ) {
		return empty( $s['period_end'] ) || (int) $s['period_end'] > time() - 7 * DAY_IN_SECONDS;
	}
	return in_array( $status, array( 'active', 'trialing' ), true );
}

/** Given free and not past its end date. */
function ltef_supporter_comped( $id ) {
	if ( ! ltef_meta( $id, 'comp' ) ) {
		return false;
	}
	$until = (string) ltef_meta( $id, 'comp_until' );
	return '' === $until || $until >= ltef_today();
}

/** Shown as a supporter right now. */
function ltef_supporter_active( $id ) {
	return 'publish' === get_post_status( $id ) && ( ltef_supporter_paid( $id ) || ltef_supporter_comped( $id ) );
}

/** Every current supporter, A to Z. */
function ltef_supporters() {
	$ids = get_posts( array( 'post_type' => 'ltef_supporter', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids' ) );
	return array_values( array_filter( $ids, 'ltef_supporter_active' ) );
}

/** Where a supporter's name links: their place page, else their website, else nowhere. [ url, is_external ] */
function ltef_supporter_link( $id ) {
	$place = (int) ltef_meta( $id, 'place' );
	if ( $place && 'publish' === get_post_status( $place ) ) {
		return array( get_permalink( $place ), false );
	}
	$web = (string) ltef_meta( $id, 'website' );
	return $web ? array( $web, true ) : array( '', false );
}

/** A supporter's name, linked. External links say they're paid (rel sponsored). */
function ltef_supporter_name_html( $id ) {
	list( $url, $ext ) = ltef_supporter_link( $id );
	$name = esc_html( get_the_title( $id ) );
	if ( ! $url ) {
		return $name;
	}
	return '<a href="' . esc_url( $url ) . '"' . ( $ext ? ' rel="sponsored noopener" target="_blank"' : '' ) . '>' . $name . ( $ext ? '<span class="vh"> (opens a new tab)</span>' : '' ) . '</a>';
}

/** True when a place belongs to a current supporter. */
function ltef_place_supporter( $place_id ) {
	static $map = null;
	if ( null === $map ) {
		$map = array();
		foreach ( ltef_supporters() as $s ) {
			$p = (int) ltef_meta( $s, 'place' );
			if ( $p ) {
				$map[ $p ] = true;
			}
		}
	}
	return isset( $map[ (int) $place_id ] );
}

/** A random key for the supporter's own links (sign up, manage). */
function ltef_supporter_key( $id ) {
	$k = (string) get_post_meta( $id, 'ltef_sup_key', true );
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $k ) ) {
		$k = bin2hex( random_bytes( 16 ) );
		update_post_meta( $id, 'ltef_sup_key', $k );
	}
	return $k;
}

function ltef_supporter_url( $id, $do ) {
	return add_query_arg( array( 'ltef_support' => $do, 'id' => (int) $id, 'k' => ltef_supporter_key( $id ) ), home_url( '/' ) );
}

/** The page businesses sign up from. */
function ltef_supporter_page_url() {
	return ( function_exists( 'ltef_owner_page_url' ) ? ltef_owner_page_url() : home_url( '/for-restaurants/' ) ) . '#supporters';
}

/* ------------------------------------------------------------------ Review queue */

/** Approve: email the sign-up link. They show as a supporter once Stripe says they've paid. [ publish, note ] */
function ltef_supporter_on_approve( $id ) {
	if ( ltef_supporter_comped( $id ) ) {
		ltef_supporter_welcome( $id, true );
		return array( true, 'Approved free. They’re on the supporters list now.' );
	}
	if ( ! function_exists( 'ltef_enhanced_can_sell' ) || ! ltef_enhanced_can_sell() ) {
		return array( false, 'Stripe isn’t set up for monthly plans yet (Review queue → Payments), so there’s no way for them to pay. Use “Approve free”, or connect Stripe first.' );
	}
	$to = (string) ltef_meta( $id, 'contact_email' );
	if ( ! is_email( $to ) ) {
		return array( false, 'There’s no contact email to send the sign-up link to. Add one under Edit first.' );
	}
	$name = get_the_title( $id );
	$sent = wp_mail(
		$to,
		'You’re in: finish signing up as a local supporter',
		"Hi,\n\nThank you for supporting Live to Eat Food! Tina said yes to " . $name . ' as a local supporter.' . "\n\n"
		. 'Finish signing up here (' . ltef_supporter_price_text() . ", billed monthly, cancel any time):\n" . ltef_supporter_url( $id, 'pay' ) . "\n\n"
		. "Once that's done, your name goes on the supporters list on the site and in Tina's Monthly Crumbs email" . ( (int) ltef_meta( $id, 'place' ) ? ', and your page gets a Local supporter label' : '' ) . ".\n\n"
		. "Supporting is a thank-you, not an ad or a review: it doesn't change what Tina says about anyone.\n\nTina\nLive to Eat Food\n"
	);
	update_post_meta( $id, 'ltef_sup_invited', current_time( 'mysql' ) );
	return array( true, $sent ? 'Approved. The sign-up link went to ' . $to . '. They show as a supporter once they pay.' : 'Approved, but the sign-up email didn’t send. Copy the link from the supporter’s edit screen and send it yourself.' );
}

/** Approve free. */
function ltef_supporter_comp( $id ) {
	update_post_meta( $id, 'ltef_comp', true );
	wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
	ltef_supporter_welcome( $id, true );
	ltef_supporter_changed();
}

/** Review-queue chips. */
function ltef_supporter_flags( $id ) {
	$out   = array( array( 'paid', 'Local supporter: ' . esc_html( ltef_supporter_price_text() ) . '. Approving emails them the sign-up link; nothing is charged until they finish it.' ) );
	$place = (int) ltef_meta( $id, 'place' );
	$out[] = $place ? array( '', 'Their page: <a href="' . esc_url( get_permalink( $place ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $place ) ) . '</a>' ) : array( '', 'No place on the site matched. Their name will link to their website.' );
	return $out;
}

/** Welcome email once they're on the list. */
function ltef_supporter_welcome( $id, $free = false ) {
	$to = (string) ltef_meta( $id, 'contact_email' );
	if ( is_email( $to ) ) {
		wp_mail(
			$to,
			'Thank you for supporting Live to Eat Food',
			"Hi,\n\n" . get_the_title( $id ) . ' is now a local supporter of Live to Eat Food. Thank you!' . "\n\n"
			. "You're on the supporters list on the site and in Tina's next Monthly Crumbs email:\n" . ltef_supporter_page_url() . "\n\n"
			. ( $free ? '' : "To change your card or cancel, use this link any time:\n" . ltef_supporter_url( $id, 'manage' ) . "\n\n" )
			. "Tina\nLive to Eat Food\n"
		);
	}
	$tina = ltef_setting( 'notify_email' );
	if ( $tina ) {
		wp_mail( $tina, 'New local supporter: ' . get_the_title( $id ), get_the_title( $id ) . ( $free ? ' is a local supporter (given free).' : ' signed up as a local supporter (' . ltef_supporter_price_text() . ').' ) . "\n\nThank them in the next Monthly Crumbs: Review queue → Email has the list ready to paste.\n" . admin_url( 'edit.php?post_type=ltef_supporter' ) );
	}
}

/** The list changed: clear cached pages so the badge, lists and footer follow. */
function ltef_supporter_changed() {
	if ( function_exists( 'ltef_money_clear_page_cache' ) ) {
		ltef_money_clear_page_cache();
	}
}
add_action(
	'save_post_ltef_supporter',
	function ( $id ) {
		if ( ! wp_is_post_revision( $id ) ) {
			ltef_supporter_changed();
		}
	},
	30
);

/* ------------------------------------------------------------------ Stripe */

/** Their links: ?ltef_support=pay (start the plan) or =manage (card, invoices, cancel). */
add_action(
	'template_redirect',
	function () {
		$do = isset( $_GET['ltef_support'] ) ? sanitize_key( wp_unslash( $_GET['ltef_support'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $do ) {
			return;
		}
		nocache_headers();
		$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$k    = isset( $_GET['k'] ) ? sanitize_key( wp_unslash( $_GET['k'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = function ( $msg ) {
			wp_safe_redirect( add_query_arg( 'ltef_sup_msg', $msg, ( function_exists( 'ltef_owner_page_url' ) ? ltef_owner_page_url() : home_url( '/for-restaurants/' ) ) ) . '#supporters' );
			exit;
		};
		if ( 'back' === $do ) {
			ltef_supporter_back();
		}
		if ( ! $id || 'ltef_supporter' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) || ! hash_equals( (string) get_post_meta( $id, 'ltef_sup_key', true ), $k ) ) {
			$page( 'link' );
		}
		if ( 'pay' === $do ) {
			if ( ltef_supporter_paid( $id ) ) {
				$page( 'thanks' );
			}
			if ( ! function_exists( 'ltef_enhanced_can_sell' ) || ! ltef_enhanced_can_sell() ) {
				$page( 'closed' );
			}
			$label = 'Local supporter: ' . get_the_title( $id );
			$meta  = array( 'ltef_kind' => 'supporter', 'ltef_supporter' => (string) $id );
			$args  = array(
				'mode'                 => 'subscription',
				'payment_method_types' => array( 'card', 'link' ),
				'client_reference_id'  => 'supporter-' . $id,
				'line_items'           => array( array( 'quantity' => 1, 'price_data' => array( 'currency' => 'usd', 'unit_amount' => ltef_supporter_cents(), 'recurring' => array( 'interval' => 'month' ), 'product_data' => array( 'name' => mb_substr( $label, 0, 240 ) ) ) ) ),
				'metadata'             => $meta,
				'subscription_data'    => array( 'description' => mb_substr( $label, 0, 240 ), 'metadata' => $meta ),
				'custom_text'          => array( 'submit' => array( 'message' => 'Billed monthly until you cancel. Cancel any time from the link in your welcome email.' ) ),
				'success_url'          => add_query_arg( array( 'ltef_support' => 'back', 'id' => $id ), home_url( '/' ) ) . '&session_id={CHECKOUT_SESSION_ID}',
				'cancel_url'           => ltef_supporter_page_url(),
			);
			$sub = ltef_sup_sub( $id );
			if ( ! empty( $sub['customer'] ) && ( $sub['mode'] ?? '' ) === ltef_stripe_mode() ) {
				$args['customer'] = $sub['customer'];
			} elseif ( is_email( (string) ltef_meta( $id, 'contact_email' ) ) ) {
				$args['customer_email'] = (string) ltef_meta( $id, 'contact_email' );
			}
			$session = ltef_stripe_api( 'POST', 'checkout/sessions', $args );
			if ( is_wp_error( $session ) || empty( $session['url'] ) ) {
				$page( 'stripe' );
			}
			wp_redirect( $session['url'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Stripe's checkout page
			exit;
		}
		if ( 'manage' === $do ) {
			$sub = ltef_sup_sub( $id );
			if ( empty( $sub['customer'] ) || ( ! empty( $sub['mode'] ) && $sub['mode'] !== ltef_stripe_mode() ) ) {
				$page( 'nocard' );
			}
			$args   = array( 'customer' => $sub['customer'], 'return_url' => ltef_supporter_page_url() );
			$config = ltef_supporter_portal_config();
			if ( ! is_wp_error( $config ) ) {
				$args['configuration'] = $config;
			}
			$portal = ltef_stripe_api( 'POST', 'billing_portal/sessions', $args );
			if ( is_wp_error( $portal ) || empty( $portal['url'] ) ) {
				$page( 'stripe' );
			}
			wp_redirect( $portal['url'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Stripe's own page
			exit;
		}
		$page( 'link' );
	},
	1
);

/** Back from checkout: confirm now instead of waiting for the webhook, then say thanks. */
function ltef_supporter_back() {
	$sid = isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$msg = 'thanks';
	if ( preg_match( '/^cs_[A-Za-z0-9_]+$/', $sid ) && function_exists( 'ltef_stripe_on' ) && ltef_stripe_on() ) {
		$s  = ltef_stripe_api( 'GET', 'checkout/sessions/' . rawurlencode( $sid ) );
		$id = is_wp_error( $s ) ? 0 : (int) ( $s['metadata']['ltef_supporter'] ?? 0 );
		if ( $id && 'complete' === ( $s['status'] ?? '' ) && 'supporter' === ( $s['metadata']['ltef_kind'] ?? '' ) && ! empty( $s['subscription'] ) ) {
			ltef_supporter_attach( $id, (string) $s['subscription'] );
		}
	}
	wp_safe_redirect( add_query_arg( 'ltef_sup_msg', $msg, ( function_exists( 'ltef_owner_page_url' ) ? ltef_owner_page_url() : home_url( '/for-restaurants/' ) ) ) . '#supporters' );
	exit;
}

/** Ties a paid subscription to a supporter. Says thank you the first time. */
function ltef_supporter_attach( $id, $sub_id ) {
	if ( 'ltef_supporter' !== get_post_type( $id ) ) {
		return;
	}
	$cur   = ltef_sup_sub( $id );
	$first = ( $cur['id'] ?? '' ) !== $sub_id;
	if ( $first && ! empty( $cur['id'] ) && ltef_supporter_paid( $id ) ) {
		ltef_stripe_api( 'DELETE', 'subscriptions/' . rawurlencode( $sub_id ) ); // never two plans for one supporter
		$tina = ltef_setting( 'notify_email' );
		if ( $tina ) {
			wp_mail( $tina, 'Duplicate supporter plan canceled: ' . get_the_title( $id ), get_the_title( $id ) . ' started a second monthly plan (' . $sub_id . ') while one was running. The site canceled the new one; if Stripe charged it, refund that charge in Stripe.' );
		}
		return;
	}
	$s = ltef_stripe_api( 'GET', 'subscriptions/' . rawurlencode( $sub_id ) );
	ltef_sup_sub_set( $id, array( 'id' => $sub_id, 'mode' => ltef_stripe_mode() ) );
	if ( ! is_wp_error( $s ) ) {
		ltef_supporter_sync( $id, $s );
	}
	if ( $first ) {
		if ( ! get_post_meta( $id, 'ltef_sup_since', true ) ) {
			update_post_meta( $id, 'ltef_sup_since', wp_date( 'Y-m-d' ) );
		}
		ltef_supporter_welcome( $id );
	}
	ltef_supporter_changed();
}

/** Mirrors Stripe's subscription onto the supporter. */
function ltef_supporter_sync( $id, $s ) {
	$set = array( 'status' => (string) ( $s['status'] ?? '' ), 'cancel_at_period_end' => ! empty( $s['cancel_at_period_end'] ) ? 1 : 0 );
	$cus = is_array( $s['customer'] ?? null ) ? ( $s['customer']['id'] ?? '' ) : (string) ( $s['customer'] ?? '' );
	foreach ( array( 'customer' => $cus, 'period_end' => (int) ( $s['current_period_end'] ?? ( $s['items']['data'][0]['current_period_end'] ?? 0 ) ), 'cents' => (int) ( $s['items']['data'][0]['price']['unit_amount'] ?? 0 ) ) as $k => $v ) {
		if ( $v ) {
			$set[ $k ] = $v;
		}
	}
	ltef_sup_sub_set( $id, $set );
	ltef_supporter_changed();
}

/** The supporter a Stripe object belongs to: from its metadata, else by subscription id. */
function ltef_supporter_for( $meta, $sub_id ) {
	$id = (int) ( $meta['ltef_supporter'] ?? 0 );
	if ( $id && 'ltef_supporter' === get_post_type( $id ) ) {
		return $id;
	}
	if ( $sub_id ) {
		$p = get_posts( array( 'post_type' => 'ltef_supporter', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'ltef_sup_sub', 'value' => '"' . $sub_id . '"', 'compare' => 'LIKE' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		return $p ? (int) $p[0] : 0;
	}
	return 0;
}

add_action(
	'ltef_stripe_event',
	function ( $e ) {
		$type = $e['type'] ?? '';
		$o    = $e['data']['object'] ?? array();
		if ( 'checkout.session.completed' === $type && 'supporter' === ( $o['metadata']['ltef_kind'] ?? '' ) ) {
			$id = ltef_supporter_for( $o['metadata'], '' );
			if ( $id && ! empty( $o['subscription'] ) ) {
				ltef_supporter_attach( $id, (string) $o['subscription'] );
			}
			return;
		}
		if ( 'invoice.paid' === $type ) {
			$sub_id = (string) ( $o['subscription'] ?? ( $o['parent']['subscription_details']['subscription'] ?? '' ) );
			$meta   = (array) ( $o['parent']['subscription_details']['metadata'] ?? ( $o['subscription_details']['metadata'] ?? ( $o['lines']['data'][0]['metadata'] ?? array() ) ) );
			if ( 'supporter' !== ( $meta['ltef_kind'] ?? '' ) && ! ltef_supporter_for( array(), $sub_id ) ) {
				return;
			}
			$id = ltef_supporter_for( $meta, $sub_id );
			if ( ! $id ) {
				return;
			}
			$cents = (int) ( $o['amount_paid'] ?? 0 );
			if ( $cents > 0 && function_exists( 'ltef_stripe_log_payment' ) ) {
				$charge = function_exists( 'ltef_enhanced_invoice_charge' ) ? ltef_enhanced_invoice_charge( $o ) : '';
				ltef_stripe_log_payment( $cents, 'supporter', get_the_title( $id ), 'Local supporter: ' . get_the_title( $id ) . ' (' . wp_date( 'M Y' ) . ')', $charge, 'invoice:' . ( $o['id'] ?? '' ) );
			}
			if ( $sub_id ) {
				$cur = ltef_sup_sub( $id );
				if ( empty( $cur['id'] ) || $cur['id'] === $sub_id ) {
					$s = ltef_stripe_api( 'GET', 'subscriptions/' . rawurlencode( $sub_id ) );
					if ( ! is_wp_error( $s ) ) {
						ltef_sup_sub_set( $id, array( 'id' => $sub_id, 'mode' => ltef_stripe_mode() ) );
						ltef_supporter_sync( $id, $s );
					}
				}
			}
			return;
		}
		if ( in_array( $type, array( 'customer.subscription.updated', 'customer.subscription.deleted' ), true ) ) {
			$sub_id = (string) ( $o['id'] ?? '' );
			$id     = ltef_supporter_for( (array) ( $o['metadata'] ?? array() ), $sub_id );
			if ( ! $id || ( ! empty( ltef_sup_sub( $id )['id'] ) && ltef_sup_sub( $id )['id'] !== $sub_id ) ) {
				return;
			}
			ltef_supporter_sync( $id, 'customer.subscription.deleted' === $type ? array_merge( $o, array( 'status' => 'canceled' ) ) : $o );
			if ( 'customer.subscription.deleted' === $type ) {
				$tina = ltef_setting( 'notify_email' );
				if ( $tina ) {
					wp_mail( $tina, 'Local supporter ended: ' . get_the_title( $id ), get_the_title( $id ) . '’s monthly plan ended, so they’re off the supporters list.' );
				}
			}
		}
	}
);

/** Stripe's portal for supporters: change the card, see invoices, cancel at the end of the paid month. */
function ltef_supporter_portal_config() {
	$saved = (array) get_option( 'ltef_stripe_portal_sup', array() );
	$mode  = ltef_stripe_mode();
	if ( ! empty( $saved[ $mode ] ) ) {
		return $saved[ $mode ];
	}
	$c = ltef_stripe_api(
		'POST',
		'billing_portal/configurations',
		array(
			'business_profile'   => array(
				'headline'             => 'Live to Eat Food: your local supporter plan',
				'privacy_policy_url'   => home_url( '/privacy-policy/' ),
				'terms_of_service_url' => home_url( '/terms/' ),
			),
			'features'           => array(
				'payment_method_update' => array( 'enabled' => true ),
				'invoice_history'       => array( 'enabled' => true ),
				'customer_update'       => array( 'enabled' => false ),
				'subscription_cancel'   => array( 'enabled' => true, 'mode' => 'at_period_end' ),
			),
			'default_return_url' => ltef_supporter_page_url(),
		)
	);
	if ( is_wp_error( $c ) || empty( $c['id'] ) ) {
		return is_wp_error( $c ) ? $c : new WP_Error( 'portal', 'No portal configuration.' );
	}
	$saved[ $mode ] = $c['id'];
	update_option( 'ltef_stripe_portal_sup', $saved, false );
	return $c['id'];
}

/* ------------------------------------------------------------------ Admin */

/** Edit screen: where the plan stands, and the links to send by hand if an email went astray. */
function ltef_supporter_plan_box( $post ) {
	$s = ltef_sup_sub( $post->ID );
	if ( ltef_supporter_paid( $post->ID ) ) {
		echo '<p><strong>Paid monthly.</strong><br>' . esc_html( ! empty( $s['cancel_at_period_end'] ) ? 'Canceled; ends ' . wp_date( 'M j', (int) $s['period_end'] ) : ( 'past_due' === ( $s['status'] ?? '' ) ? 'Payment failed; Stripe is retrying.' : 'Renews ' . ( ! empty( $s['period_end'] ) ? wp_date( 'M j', (int) $s['period_end'] ) : 'monthly' ) ) ) . '</p>';
	} elseif ( ltef_supporter_comped( $post->ID ) ) {
		echo '<p><strong>Free, from Tina.</strong></p>';
	} elseif ( ! empty( $s['status'] ) ) {
		echo '<p><strong>Not active.</strong> Stripe says: ' . esc_html( $s['status'] ) . '.</p>';
	} else {
		echo '<p><strong>Not paid yet.</strong></p>';
	}
	echo '<p class="ltef-status-line">' . esc_html( ltef_supporter_active( $post->ID ) ? 'On the supporters list.' : 'Not on the supporters list.' ) . '</p>';
	if ( 'publish' === $post->post_status ) {
		printf( '<p><label for="ltef-sup-pay">Sign-up link</label><input id="ltef-sup-pay" class="widefat" readonly value="%s"></p>', esc_attr( ltef_supporter_url( $post->ID, 'pay' ) ) );
		if ( ! empty( $s['customer'] ) ) {
			printf( '<p><label for="ltef-sup-manage">Their manage link (card, cancel)</label><input id="ltef-sup-manage" class="widefat" readonly value="%s"></p>', esc_attr( ltef_supporter_url( $post->ID, 'manage' ) ) );
		}
	}
}

/** The list screen: a Status column. */
add_filter(
	'manage_ltef_supporter_posts_columns',
	function ( $cols ) {
		$out = array();
		foreach ( $cols as $k => $v ) {
			$out[ $k ] = $v;
			if ( 'title' === $k ) {
				$out['ltef_sup'] = 'Status';
			}
		}
		return $out;
	}
);
add_action(
	'manage_ltef_supporter_posts_custom_column',
	function ( $col, $id ) {
		if ( 'ltef_sup' !== $col ) {
			return;
		}
		if ( 'pending' === get_post_status( $id ) ) {
			echo esc_html( 'Waiting in the review queue' );
		} elseif ( ltef_supporter_paid( $id ) ) {
			echo esc_html( 'On the list · paid monthly' );
		} elseif ( ltef_supporter_comped( $id ) ) {
			echo esc_html( 'On the list · free' . ( ltef_meta( $id, 'comp_until' ) ? ' until ' . wp_date( 'M j, Y', strtotime( ltef_meta( $id, 'comp_until' ) ) ) : '' ) );
		} elseif ( get_post_meta( $id, 'ltef_sup_invited', true ) ) {
			echo esc_html( 'Approved · sign-up link sent, not paid yet' );
		} else {
			echo esc_html( 'Not on the list' );
		}
	},
	10,
	2
);

/** For Monthly Crumbs: the thank-you, ready to paste. */
function ltef_supporter_crumbs_text() {
	$ids = ltef_supporters();
	if ( ! $ids ) {
		return '';
	}
	$names = array_map( function ( $id ) { return html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ); }, $ids );
	$last  = array_pop( $names );
	$list  = $names ? implode( ', ', $names ) . ' and ' . $last : $last;
	return 'Thank you to our local supporters, who help keep Live to Eat Food going: ' . $list . '. Want to join them? ' . ( function_exists( 'ltef_owner_page_url' ) ? ltef_owner_page_url() : home_url( '/for-restaurants/' ) ) . '#supporters';
}

function ltef_supporter_crumbs_box() {
	$text = ltef_supporter_crumbs_text();
	$ids  = ltef_supporters();
	$out  = '<div class="ltef-sup-crumbs"><h2>For Monthly Crumbs</h2>';
	if ( ! $text ) {
		return $out . '<p>No local supporters yet. When there are, a thank-you to paste into Monthly Crumbs shows here.</p></div>';
	}
	$links = '';
	foreach ( $ids as $id ) {
		list( $url ) = ltef_supporter_link( $id );
		$links      .= '<li>' . esc_html( get_the_title( $id ) ) . ( $url ? ' · <code>' . esc_html( $url ) . '</code>' : '' ) . '</li>';
	}
	return $out . '<p>Paste this into the next Monthly Crumbs. It names everyone on the list today, and links to For businesses, which says supporters pay.</p>'
		. '<p><label class="screen-reader-text" for="ltef-sup-text">Thank-you text</label><textarea id="ltef-sup-text" class="large-text" rows="4" readonly onclick="this.select()">' . esc_textarea( $text ) . '</textarea></p>'
		. '<details><summary>Links for each supporter</summary><ul>' . $links . '</ul></details></div>';
}

add_filter(
	'views_edit-ltef_supporter',
	function ( $views ) {
		echo '<p class="ltef-lede" style="max-width: 760px;">Businesses that chip in ' . esc_html( ltef_supporter_price_text() ) . ' to keep the site going. They sign up from For businesses, and wait in the review queue like everything else. Change the price under Money → Prices.</p>' . ltef_supporter_crumbs_box(); // escaped there
		return $views;
	}
);

/* ------------------------------------------------------------------ On the site */

/** [supporters] block: the thank-you list, with the sign-up form when cta is on. */
function ltef_block_supporters( $attrs ) {
	$ids  = ltef_supporters();
	$line = ! empty( $attrs['line'] );
	if ( $line ) {
		return $ids ? '<p class="ltef-supporters-line">Thanks to our local supporters: ' . implode( ', ', array_map( 'ltef_supporter_name_html', $ids ) ) . '. <a href="' . esc_url( ltef_supporter_page_url() ) . '">Become one</a></p>' : '';
	}
	$cta = ! empty( $attrs['cta'] );
	if ( ! $ids && ! $cta ) {
		return '';
	}
	$msg  = isset( $_GET['ltef_sup_msg'] ) ? sanitize_key( wp_unslash( $_GET['ltef_sup_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$note = $cta && $msg ? array(
		'thanks' => array( 'status', 'Thank you! You’re a local supporter now. Your name is on the list below, and a receipt is on its way from Stripe.' ),
		'link'   => array( 'alert', 'That link doesn’t work anymore. Email tina@livetoeatfoodie.com and she’ll send a new one.' ),
		'closed' => array( 'alert', 'Sign-ups are paused for a moment. Email tina@livetoeatfoodie.com.' ),
		'stripe' => array( 'alert', 'Stripe couldn’t open just now. Please try the link again in a minute.' ),
		'nocard' => array( 'alert', 'There’s no card on file for that plan. Email tina@livetoeatfoodie.com.' ),
	)[ $msg ] ?? null : null;
	$out = $note ? '<p class="' . ( 'status' === $note[0] ? 'ltef-sent-line' : 'ltef-form__error' ) . '" role="' . esc_attr( $note[0] ) . '">' . esc_html( $note[1] ) . '</p>' : '';
	$out .= '<p class="ltef-supporters__about">These local businesses help keep Live to Eat Food going. Supporters chip in ' . esc_html( ltef_supporter_price_text() ) . '. It’s a thank-you, not an ad or a review: it never changes what Tina says about anyone.</p>';
	if ( $ids ) {
		$out .= '<ul class="ltef-supporters">' . implode( '', array_map( function ( $id ) { return '<li>' . ltef_supporter_name_html( $id ) . '</li>'; }, $ids ) ) . '</ul>';
	} else {
		$out .= '<p class="meta">No supporters yet. Be the first!</p>';
	}
	return ltef_wrap( $out, 'supporters-block' );
}

/** A section with the class hide-if-no-supporters shows only while there's someone to thank. */
add_filter(
	'render_block',
	function ( $html, $block ) {
		$class = (string) ( $block['attrs']['className'] ?? '' );
		if ( false !== strpos( $class, 'hide-if-no-supporters' ) && ! ltef_supporters() ) {
			return '';
		}
		// The footer thanks them on every page.
		if ( 'core/group' === ( $block['blockName'] ?? '' ) && false !== strpos( $class, 'ltef-footer' ) && false !== strpos( $html, '</footer>' ) ) {
			$line = ltef_block_supporters( array( 'line' => true ) );
			if ( $line ) {
				$pos  = strrpos( $html, '</footer>' );
				$html = substr( $html, 0, $pos ) . $line . substr( $html, $pos );
			}
		}
		return $html;
	},
	10,
	2
);
