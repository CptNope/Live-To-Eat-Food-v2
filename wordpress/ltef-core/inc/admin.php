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
	echo '<p>Everything people send in waits here. Nothing goes on the site until it’s approved. Aim to reply within ' . (int) ltef_setting( 'reply_days' ) . ' days: the site promises that.</p>';
	if ( $done ) {
		$note = isset( $_GET['ltef_note'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['ltef_note'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$text = array( 'approved' => 'Approved. It’s live now (messages are marked done).', 'waiting' => 'Not live yet.' )[ $done ] ?? 'Declined and moved to the trash. It can be restored from there. Any card hold was released.';
		echo '<div class="notice notice-' . ( 'waiting' === $done ? 'warning' : 'success' ) . ' is-dismissible"><p>' . esc_html( trim( $text . ' ' . $note ) ) . '</p></div>';
	}
	if ( ! $items ) {
		echo '<p><strong>All caught up.</strong></p></div>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th>What</th><th>Details</th><th>Flags</th><th>Decide</th></tr></thead><tbody>';
	foreach ( $items as $p ) {
		$fields = get_post_meta( $p->ID, 'ltef_fields', true );
		$flags  = array();
		if ( get_post_meta( $p->ID, 'ltef_flag_blocklist', true ) ) {
			$flags[] = '<span style="background:#ffd8c2;padding:2px 8px;border-radius:99px"><strong>On your don’t-work-with list</strong></span>';
		}
		if ( 'ltef_woopon' === $p->post_type ) {
			$tier = ltef_meta( $p->ID, 'tier' );
			$flags[] = esc_html( array( 'free' => 'Free trial: check it’s their first', 'weekly' => 'Weekly: ' . ltef_money( ltef_setting( 'woopon_weekly' ) ) . ' a week', 'vip' => 'VIP: ' . ltef_money( ltef_setting( 'woopon_vip' ) ), 'partner' => 'Partner code' )[ $tier ] ?? '' );
			if ( 'vip' === $tier ) {
				$flyer = (int) ltef_meta( $p->ID, 'flyer' );
				$flags[] = $flyer ? '<a href="' . esc_url( wp_get_attachment_url( $flyer ) ) . '" target="_blank" rel="noopener">See the flyer</a>' : '<strong>No flyer</strong>';
			}
		}
		if ( 'ltef_event' === $p->post_type && ltef_meta( $p->ID, 'premium' ) ) {
			$flags[] = 'Premium listing: ' . esc_html( ltef_money( ltef_setting( 'event_premium' ) ) ) . ' a month';
		}
		if ( 'ltef_job' === $p->post_type ) {
			$flags[] = 'Job post: ' . esc_html( ltef_money( ltef_setting( 'job_post' ) ) ) . ' a month';
		}
		$payflag = function_exists( 'ltef_pay_flag' ) ? ltef_pay_flag( $p->ID ) : '';
		if ( $payflag ) {
			$flags[] = $payflag; // built from fixed strings and an amount
		}
		$photo = (int) get_post_meta( $p->ID, 'ltef_photo', true );
		if ( $photo ) {
			$flags[] = '<a href="' . esc_url( wp_get_attachment_url( $photo ) ) . '" target="_blank" rel="noopener">See the photo</a>';
		}
		$details = '';
		if ( is_array( $fields ) ) {
			foreach ( $fields as $k => $val ) {
				if ( '' === $val || null === $val ) {
					continue;
				}
				$details .= '<div><strong>' . esc_html( ucfirst( str_replace( '_', ' ', $k ) ) ) . ':</strong> ' . esc_html( is_bool( $val ) ? ( $val ? 'yes' : 'no' ) : $val ) . '</div>';
			}
		}
		$url = function ( $act ) use ( $p ) {
			return wp_nonce_url( admin_url( 'admin-post.php?action=ltef_review&do=' . $act . '&post=' . $p->ID ), 'ltef_review_' . $p->ID );
		};
		printf(
			'<tr><td><strong>%1$s</strong><br>%2$s<br><span class="description">Sent %3$s</span></td><td>%4$s</td><td>%5$s</td><td><a class="button button-primary" href="%6$s">Approve</a> <a class="button" href="%7$s">Edit first</a><form method="post" action="%8$s" style="margin-top:8px"><input type="hidden" name="action" value="ltef_review"><input type="hidden" name="do" value="decline"><input type="hidden" name="post" value="%9$d">%10$s<label class="screen-reader-text" for="why-%9$d">Private reason</label><input id="why-%9$d" type="text" name="why" placeholder="Private reason (optional)" class="regular-text" style="max-width:200px"> <button class="button">Decline</button><br><label><input type="checkbox" name="block" value="1"> Add to the don’t-work-with list</label></form></td></tr>',
			esc_html( ltef_review_types()[ $p->post_type ] ),
			esc_html( $p->post_title ),
			esc_html( human_time_diff( strtotime( $p->post_date ), current_time( 'timestamp' ) ) . ' ago' ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			$details ? $details : '—', // already escaped
			$flags ? implode( '<br>', $flags ) : '—', // already escaped
			esc_url( $url( 'approve' ) ),
			esc_url( get_edit_post_link( $p->ID ) ? get_edit_post_link( $p->ID ) : '#' ),
			esc_url( admin_url( 'admin-post.php' ) ),
			$p->ID,
			wp_nonce_field( 'ltef_review_' . $p->ID, '_wpnonce', true, false )
		);
	}
	echo '</tbody></table></div>';
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
	echo '<div class="wrap"><h1>Live to Eat Food settings</h1><form method="post" action="options.php">';
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
