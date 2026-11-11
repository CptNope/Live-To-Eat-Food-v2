<?php
/**
 * Tina's review queue and the settings (prices, where notifications go, the private don't-work-with list).
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'ltef_admin_menu' );
function ltef_admin_menu() {
	$count = ltef_pending_count();
	add_menu_page( 'Review queue', 'Review queue' . ( $count ? ' <span class="awaiting-mod">' . (int) $count . '</span>' : '' ), 'edit_others_posts', 'ltef-review', 'ltef_review_page', 'dashicons-yes-alt', 3 );
	add_submenu_page( 'ltef-review', 'Review queue', 'Waiting for Tina', 'edit_others_posts', 'ltef-review', 'ltef_review_page' );
	add_submenu_page( 'ltef-review', 'Live to Eat Food settings', 'Settings', 'manage_options', 'ltef-settings', 'ltef_settings_page' );
}

function ltef_review_types() {
	return array( 'ltef_woopon' => 'Woo-pon', 'ltef_event' => 'Event', 'ltef_job' => 'Job post', 'ltef_place' => 'Place', 'ltef_submission' => 'Message' );
}

function ltef_pending_count() {
	$n = 0;
	foreach ( array_keys( ltef_review_types() ) as $t ) {
		$c  = wp_count_posts( $t );
		$n += isset( $c->pending ) ? (int) $c->pending : 0;
	}
	return $n;
}

function ltef_review_page() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$items = get_posts( array( 'post_type' => array_keys( ltef_review_types() ), 'post_status' => 'pending', 'numberposts' => 200, 'orderby' => 'date', 'order' => 'ASC' ) );
	$done  = isset( $_GET['ltef_done'] ) ? sanitize_key( wp_unslash( $_GET['ltef_done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<div class="wrap ltef-review"><h1>Waiting for Tina</h1>';
	echo '<p class="ltef-lede">Everything people send in waits here. Nothing goes on the site until it’s approved. Aim to reply within ' . (int) ltef_setting( 'reply_days' ) . ' days: the site promises that.</p>';
	if ( $done ) {
		$note = isset( $_GET['ltef_note'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['ltef_note'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$text = array( 'approved' => 'Approved. It’s live now (messages are marked done).', 'waiting' => 'Not live yet.' )[ $done ] ?? 'Declined and moved to the trash. It can be restored from there. Any card hold was released.';
		echo '<div class="notice notice-' . ( 'waiting' === $done ? 'warning' : 'success' ) . ' is-dismissible"><p>' . esc_html( trim( $text . ' ' . $note ) ) . '</p></div>';
	}
	if ( ! $items ) {
		printf(
			'<div class="ltef-empty"><img src="%s" alt=""><p><strong>All caught up.</strong>New submissions land here, and each one is emailed to you as it comes in.</p></div></div>',
			esc_url( ltef_brand_asset( 'img/stickers/lemon.svg' ) )
		);
		return;
	}
	echo '<ol class="ltef-queue">';
	foreach ( $items as $p ) {
		$fields = get_post_meta( $p->ID, 'ltef_fields', true );
		$flags  = array(); // [ chip modifier, html ]
		if ( get_post_meta( $p->ID, 'ltef_flag_blocklist', true ) ) {
			$flags[] = array( 'warn', 'On your don’t-work-with list' );
		}
		if ( 'ltef_woopon' === $p->post_type ) {
			$tier = ltef_meta( $p->ID, 'tier' );
			$t    = array( 'free' => 'Free trial: check it’s their first', 'weekly' => 'Weekly: ' . ltef_money( ltef_setting( 'woopon_weekly' ) ) . ' a week', 'vip' => 'VIP: ' . ltef_money( ltef_setting( 'woopon_vip' ) ), 'partner' => 'Partner code' )[ $tier ] ?? '';
			if ( $t ) {
				$flags[] = array( 'partner' === $tier || 'free' === $tier ? '' : 'paid', esc_html( $t ) );
			}
			if ( 'vip' === $tier ) {
				$flyer   = (int) ltef_meta( $p->ID, 'flyer' );
				$flags[] = $flyer ? array( '', '<a href="' . esc_url( wp_get_attachment_url( $flyer ) ) . '" target="_blank" rel="noopener">See the flyer</a>' ) : array( 'warn', 'No flyer' );
			}
		}
		if ( 'ltef_event' === $p->post_type && ltef_meta( $p->ID, 'premium' ) ) {
			$flags[] = array( 'paid', 'Premium listing: ' . esc_html( ltef_money( ltef_setting( 'event_premium' ) ) ) . ' a month' );
		}
		if ( 'ltef_job' === $p->post_type ) {
			$flags[] = array( 'paid', 'Job post: ' . esc_html( ltef_money( ltef_setting( 'job_post' ) ) ) . ' a month' );
		}
		$payflag = function_exists( 'ltef_pay_flag' ) ? ltef_pay_flag( $p->ID ) : '';
		if ( $payflag ) {
			$flags[] = array( 'pay', $payflag ); // built from fixed strings and an amount
		}
		$photo = (int) get_post_meta( $p->ID, 'ltef_photo', true );
		if ( $photo ) {
			$flags[] = array( '', '<a href="' . esc_url( wp_get_attachment_url( $photo ) ) . '" target="_blank" rel="noopener">See the photo</a>' );
		}
		$details = '';
		if ( is_array( $fields ) ) {
			foreach ( $fields as $k => $val ) {
				if ( '' === $val || null === $val ) {
					continue;
				}
				$details .= '<dt>' . esc_html( ucfirst( str_replace( '_', ' ', $k ) ) ) . '</dt><dd>' . esc_html( is_bool( $val ) ? ( $val ? 'yes' : 'no' ) : $val ) . '</dd>';
			}
		}
		$chips = '';
		foreach ( $flags as $f ) {
			$chips .= '<li><span class="ltef-chip' . ( $f[0] ? ' ltef-chip--' . $f[0] : '' ) . '">' . $f[1] . '</span></li>'; // already escaped
		}
		$url = function ( $act ) use ( $p ) {
			return wp_nonce_url( admin_url( 'admin-post.php?action=ltef_review&do=' . $act . '&post=' . $p->ID ), 'ltef_review_' . $p->ID );
		};
		printf(
			'<li class="ltef-card" aria-labelledby="ltef-q-%9$d"><header class="ltef-card__head"><span class="ltef-chip ltef-chip--kind">%1$s</span><h2 class="ltef-card__title" id="ltef-q-%9$d">%2$s</h2><span class="ltef-card__when">Sent %3$s</span></header>%5$s<dl class="ltef-card__details">%4$s</dl><div class="ltef-card__actions"><a class="button button-primary" href="%6$s">Approve</a><a class="button" href="%7$s">Edit first</a><details class="ltef-decline"><summary>Decline</summary><form method="post" action="%8$s"><input type="hidden" name="action" value="ltef_review"><input type="hidden" name="do" value="decline"><input type="hidden" name="post" value="%9$d">%10$s<label class="screen-reader-text" for="why-%9$d">Private reason</label><input id="why-%9$d" type="text" name="why" placeholder="Private reason (optional)"><label class="ltef-check"><input type="checkbox" name="block" value="1"> Add to the don’t-work-with list</label><button class="button">Decline and move to trash</button></form></details></div></li>',
			esc_html( ltef_review_types()[ $p->post_type ] ),
			esc_html( $p->post_title ),
			esc_html( human_time_diff( strtotime( $p->post_date ), current_time( 'timestamp' ) ) . ' ago' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			$details ? $details : '<dt>Details</dt><dd>None sent</dd>', // already escaped
			$chips ? '<ul class="ltef-card__flags">' . $chips . '</ul>' : '', // already escaped
			esc_url( $url( 'approve' ) ),
			esc_url( get_edit_post_link( $p->ID ) ? get_edit_post_link( $p->ID ) : '#' ),
			esc_url( admin_url( 'admin-post.php' ) ),
			$p->ID,
			wp_nonce_field( 'ltef_review_' . $p->ID, '_wpnonce', true, false )
		);
	}
	echo '</ol></div>';
}

add_action( 'admin_post_ltef_review', 'ltef_handle_review' );
function ltef_handle_review() {
	$id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : 0;
	check_admin_referer( 'ltef_review_' . $id );
	if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Not allowed.' );
	}
	$do = isset( $_REQUEST['do'] ) ? sanitize_key( wp_unslash( $_REQUEST['do'] ) ) : '';
	$note = '';
	if ( 'approve' === $do ) {
		list( $publish, $note ) = function_exists( 'ltef_stripe_on_approve' ) ? ltef_stripe_on_approve( $id ) : array( true, '' );
		if ( $publish ) {
			wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		} else {
			$do = 'waiting';
		}
	} elseif ( 'decline' === $do ) {
		if ( function_exists( 'ltef_stripe_on_decline' ) ) {
			ltef_stripe_on_decline( $id );
		}
		$why = isset( $_POST['why'] ) ? sanitize_text_field( wp_unslash( $_POST['why'] ) ) : '';
		update_post_meta( $id, 'ltef_declined_why', $why );
		if ( ! empty( $_POST['block'] ) ) {
			$fields = get_post_meta( $id, 'ltef_fields', true );
			$name   = is_array( $fields ) ? ( $fields['business'] ?? ( $fields['place'] ?? '' ) ) : '';
			if ( $name ) {
				$s                   = ltef_settings();
				$s['dont_work_with'] = trim( $s['dont_work_with'] . "\n" . $name );
				update_option( 'ltef_settings', $s, false );
			}
		}
		wp_trash_post( $id );
	}
	wp_safe_redirect( add_query_arg( array( 'ltef_done' => array( 'approve' => 'approved', 'waiting' => 'waiting' )[ $do ] ?? 'declined', 'ltef_note' => rawurlencode( $note ) ), admin_url( 'admin.php?page=ltef-review' ) ) );
	exit;
}

add_action( 'admin_init', 'ltef_register_settings' );
function ltef_register_settings() {
	register_setting(
		'ltef_settings',
		'ltef_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => function ( $in ) {
				$out = ltef_settings();
				foreach ( array( 'woopon_weekly', 'woopon_free', 'woopon_vip', 'vip_limit', 'event_premium', 'job_post', 'enhanced_low', 'enhanced_high', 'reply_days' ) as $k ) {
					if ( isset( $in[ $k ] ) ) {
						$out[ $k ] = max( 0, (float) $in[ $k ] );
					}
				}
				$out['notify_email']   = ltef_email_list( $in['notify_email'] ?? '' );
				$out['dont_work_with'] = sanitize_textarea_field( $in['dont_work_with'] ?? '' );
				return $out;
			},
		)
	);
}

function ltef_settings_page() {
	$s = ltef_settings();
	echo '<div class="wrap"><h1>Live to Eat Food settings</h1><p class="ltef-lede">Where submissions go, how fast you reply, and the private don’t-work-with list.</p><form method="post" action="options.php">';
	settings_fields( 'ltef_settings' );
	echo '<table class="form-table" role="presentation">';
	$num = function ( $k, $label, $help ) use ( $s ) {
		printf( '<tr><th scope="row"><label for="ltef-%1$s">%2$s</label></th><td><input type="number" step="1" min="0" id="ltef-%1$s" name="ltef_settings[%1$s]" value="%3$s" class="small-text"> <span class="description">%4$s</span></td></tr>', esc_attr( $k ), esc_html( $label ), esc_attr( $s[ $k ] ), esc_html( $help ) );
	};
	printf( '<tr><th scope="row"><label for="ltef-notify">Send new submissions to</label></th><td><input type="text" id="ltef-notify" name="ltef_settings[notify_email]" value="%s" class="large-text" autocomplete="off"><p class="description">One or more addresses, separated by commas. Everyone here gets each new submission.</p></td></tr>', esc_attr( $s['notify_email'] ) );
	$num( 'reply_days', 'Reply within (days)', 'The site promises this.' );
	echo '<tr><th scope="row">Prices</th><td><a href="' . esc_url( admin_url( 'admin.php?page=ltef-money&tab=settings' ) ) . '">Edit prices under Money</a></td></tr>';
	printf( '<tr><th scope="row"><label for="ltef-dww">Don’t-work-with list</label></th><td><textarea id="ltef-dww" name="ltef_settings[dont_work_with]" rows="6" class="large-text">%s</textarea><p class="description"><strong>Private.</strong> One business per line. Never shown on the site; submissions that match are flagged in the review queue.</p></td></tr>', esc_textarea( $s['dont_work_with'] ) );
	echo '</table>';
	submit_button( 'Save settings' );
	echo '</form></div>';
}

/** Private contact details stay out of the public REST API and search. */
add_filter(
	'is_protected_meta',
	function ( $protected, $key ) {
		return in_array( $key, array( 'ltef_contact_phone', 'ltef_contact_email', 'ltef_fields', 'ltef_flag_blocklist', 'ltef_declined_why' ), true ) ? true : $protected;
	},
	10,
	2
);
