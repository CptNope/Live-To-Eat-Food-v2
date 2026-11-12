<?php
/**
 * Owner logins: a WordPress account limited to one screen, "Your listing".
 *
 * Approving a claim creates the account (role "Business owner") and emails a link to set a password. The role
 * can read and edit its own listing's phone, website, menu link, hours and note, and nothing else: no posts,
 * pages, media, comments, other places, settings or Tina's fields. The dashboard and the front-end admin bar are
 * hidden from it, and it can't make application passwords. Which places a login can edit comes from the owner
 * emails on each place, so removing an email in the place's Owners box removes that login's access to it.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

const LTEF_OWNER_ROLE = 'ltef_owner';
const LTEF_OWNER_CAP  = 'ltef_edit_own_listing';

/** The role exists on every request (updating the plugin by upload doesn't run activation). */
add_action(
	'init',
	function () {
		if ( ! get_role( LTEF_OWNER_ROLE ) ) {
			add_role( LTEF_OWNER_ROLE, 'Business owner', array( 'read' => true, LTEF_OWNER_CAP => true ) );
		}
	}
);

/** A business owner and nothing more: the screens below are fenced to this. */
function ltef_owner_is_limited( $user = null ) {
	$user = $user ? $user : wp_get_current_user();
	return $user instanceof WP_User && $user->exists() && in_array( LTEF_OWNER_ROLE, (array) $user->roles, true ) && ! user_can( $user, 'edit_posts' );
}

function ltef_owner_admin_url( $args = array() ) {
	return add_query_arg( $args, admin_url( 'admin.php?page=ltef-my-listing' ) );
}

/**
 * The login for an owner email, made if it doesn't exist. [ user id, made now ] or WP_Error.
 * An existing account keeps its role; one without editing rights also becomes a business owner.
 */
function ltef_owner_ensure_user( $email, $name = '' ) {
	$email = ltef_owner_norm( $email );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'email', 'That email doesn’t look right.' );
	}
	$user = get_user_by( 'email', $email );
	if ( $user ) {
		if ( ! user_can( $user, 'edit_posts' ) && ! in_array( LTEF_OWNER_ROLE, (array) $user->roles, true ) ) {
			$user->add_role( LTEF_OWNER_ROLE );
		}
		return array( $user->ID, false );
	}
	$base  = sanitize_user( strtolower( strstr( $email, '@', true ) ), true );
	$base  = $base ? substr( $base, 0, 40 ) : 'owner';
	$login = $base;
	for ( $i = 2; username_exists( $login ); $i++ ) {
		$login = $base . $i;
	}
	$name = sanitize_text_field( $name );
	$id   = wp_insert_user(
		array(
			'user_login'           => $login,
			'user_email'           => $email,
			'user_pass'            => wp_generate_password( 32, true, true ), // replaced when they set their own
			'role'                 => LTEF_OWNER_ROLE,
			'display_name'         => $name ? $name : $login,
			'first_name'           => $name,
			'admin_color'          => 'ltef',
			'show_admin_bar_front' => 'false',
		)
	);
	return is_wp_error( $id ) ? $id : array( (int) $id, true );
}

/** Emails a new owner a link to set their password, or an existing one where to sign in. */
function ltef_owner_welcome( $user_id, $is_new ) {
	$user   = get_userdata( $user_id );
	$places = $user ? ltef_owner_places( $user->user_email ) : array();
	if ( ! $places ) {
		return false;
	}
	$names  = implode( ', ', wp_list_pluck( $places, 'post_title' ) );
	$signin = wp_login_url( ltef_owner_admin_url() );
	$lines  = array( 'Tina approved your claim. You can now keep ' . $names . ' up to date on Live to Eat Food: phone, website, menu link, hours, a short note from you and announcements.', '' );
	if ( $is_new ) {
		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			return false;
		}
		$lines[] = '1. Set your password (this link works for 24 hours):';
		$lines[] = network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user->user_login ), 'login' );
		$lines[] = '';
		$lines[] = '2. Then sign in any time at ' . $signin;
		$lines[] = 'Your username is ' . $user->user_login . ', or use this email address.';
	} else {
		$lines[] = 'Sign in with your username (' . $user->user_login . ') or this email address at:';
		$lines[] = $signin;
	}
	$lines[] = '';
	$lines[] = 'Your login opens only your own listing, and changes go live right away. Tina’s take, her videos and her verdict are hers, so they aren’t part of it.';
	$lines[] = 'Link run out or password forgotten? Use “Lost your password?” on the sign-in page, or get a one-time sign-in link at ' . ltef_owner_page_url() . '#manage';
	return wp_mail( $user->user_email, $is_new ? 'Set up your Live to Eat Food login' : 'You can now update ' . $names, implode( "\n", $lines ) );
}

/** Gives an owner email a login and tells them. Returns a short note for Tina. */
function ltef_owner_grant( $place, $email, $name = '' ) {
	ltef_owner_add( $place, $email );
	$made = ltef_owner_ensure_user( $email, $name );
	if ( is_wp_error( $made ) ) {
		return 'They’re an owner of ' . get_the_title( $place ) . ', but no login was made (' . $made->get_error_message() . '). They can still use an email link on For restaurants.';
	}
	list( $uid, $is_new ) = $made;
	$sent = ltef_owner_welcome( $uid, $is_new );
	if ( ! $sent ) {
		return 'They’re an owner of ' . get_the_title( $place ) . ', but the email didn’t send. They can use “Lost your password?” on the sign-in page.';
	}
	return $is_new
		? 'They can edit ' . get_the_title( $place ) . ' now. A login was made for ' . $email . ', and they got an email to set a password.'
		: 'They can edit ' . get_the_title( $place ) . ' now with their existing login (' . $email . '); they got an email saying so.';
}

/* ------------------------------------------------------------------ Fencing the role in */

add_filter(
	'login_redirect',
	function ( $to, $requested, $user ) {
		return $user instanceof WP_User && ltef_owner_is_limited( $user ) ? ltef_owner_admin_url() : $to;
	},
	10,
	3
);

add_action(
	'admin_init',
	function () {
		global $pagenow;
		if ( 'index.php' === $pagenow && ltef_owner_is_limited() ) {
			wp_safe_redirect( ltef_owner_admin_url() );
			exit;
		}
	}
);

add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Your listing', 'Your listing', LTEF_OWNER_CAP, 'ltef-my-listing', 'ltef_owner_admin_page', 'dashicons-store', 2 );
		if ( ltef_owner_is_limited() ) {
			remove_menu_page( 'index.php' );
		}
	},
	999
);

add_action(
	'admin_bar_menu',
	function ( $bar ) {
		if ( ltef_owner_is_limited() ) {
			$bar->remove_node( 'wp-logo' );
			$bar->remove_node( 'dashboard' );
		}
	},
	999
);

add_filter(
	'show_admin_bar',
	function ( $show ) {
		return ltef_owner_is_limited() ? false : $show;
	}
);

add_filter(
	'wp_is_application_passwords_available_for_user',
	function ( $ok, $user ) {
		return ltef_owner_is_limited( $user ) ? false : $ok;
	},
	10,
	2
);

/** If an owner changes their email on their profile, their listings follow it. */
add_action(
	'profile_update',
	function ( $user_id, $old ) {
		$new = get_userdata( $user_id );
		if ( ! $new || ! $old || ltef_owner_norm( $old->user_email ) === ltef_owner_norm( $new->user_email ) ) {
			return;
		}
		foreach ( ltef_owner_places( $old->user_email ) as $p ) {
			ltef_owner_remove( $p->ID, $old->user_email );
			ltef_owner_add( $p->ID, $new->user_email );
		}
	},
	10,
	2
);

/* ------------------------------------------------------------------ The "Your listing" screen */

function ltef_owner_admin_page() {
	if ( ! current_user_can( LTEF_OWNER_CAP ) ) {
		return;
	}
	$user   = wp_get_current_user();
	$places = ltef_owner_places( $user->user_email );
	$saved  = isset( $_GET['ltef_saved'] ) ? absint( $_GET['ltef_saved'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$err    = isset( $_GET['ltef_owner_error'] ) ? sanitize_text_field( wp_unslash( $_GET['ltef_owner_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<div class="wrap ltef-owner-admin"><h1>Your listing</h1>';
	echo '<p class="ltef-lede">Changes go live on Live to Eat Food right away, and Tina sees each one. Her take, her videos and her verdict are hers, so they aren’t here.</p>';
	if ( $saved && in_array( $saved, wp_list_pluck( $places, 'ID' ), true ) ) {
		printf( '<div class="notice notice-success is-dismissible"><p>Saved. It’s live on <a href="%s">%s’s page</a>.</p></div>', esc_url( get_permalink( $saved ) ), esc_html( get_the_title( $saved ) ) );
	} elseif ( $err ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $err ) . '</p></div>';
	}
	$enh = isset( $_GET['ltef_enh_msg'] ) ? sanitize_key( wp_unslash( $_GET['ltef_enh_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $enh ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( 'canceled' === $enh ? 'Enhanced is canceled. It stays on until the end of the month you’ve paid for, and nothing more is charged.' : 'You’re keeping Enhanced. It renews as before.' ) . '</p></div>';
	}
	if ( ! $places ) {
		echo '<div class="ltef-panel"><p><strong>No listing is linked to this login yet.</strong></p><p>If Tina approved your claim, the listing follows the email on your profile: ' . esc_html( $user->user_email ) . '. Changed it? Email <a href="mailto:tina@livetoeatfoodie.com">tina@livetoeatfoodie.com</a> and she’ll link it again.</p></div></div>';
		return;
	}
	foreach ( $places as $p ) {
		$uid  = 'ltef-own-' . $p->ID;
		$rows = '';
		foreach ( ltef_owner_fields() as $key => $def ) {
			list( $label, $type, $hint ) = $def;
			$id   = $uid . '-' . $key;
			$val  = ltef_owner_form_value( $p->ID, $key );
			$desc = $hint ? ' aria-describedby="' . esc_attr( $id ) . '-h"' : '';
			$ctl  = 'textarea' === $type
				? '<textarea class="large-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" rows="3" maxlength="' . (int) ( $def[3] ?? LTEF_OWNER_NOTE_MAX ) . '"' . $desc . '>' . esc_textarea( $val ) . '</textarea>'
				: '<input class="regular-text" type="' . ( 'url' === $type ? 'text" inputmode="url" spellcheck="false" autocapitalize="off' : esc_attr( $type ) ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '"' . $desc . ltef_owner_field_attrs( $key, $type ) . '>';
			$rows .= '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>' . $ctl . ( $hint ? '<p class="description" id="' . esc_attr( $id ) . '-h">' . esc_html( $hint ) . '</p>' : '' ) . '</td></tr>';
		}
		$checked = ltef_meta( $p->ID, 'hours_checked' );
		printf(
			'<form class="ltef-owner-form" id="%1$s" method="post" action="%2$s"><h2>%3$s</h2><p><a href="%4$s" target="_blank" rel="noopener">See your page<span class="screen-reader-text"> (opens a new tab)</span></a>%5$s</p><table class="form-table" role="presentation">%6$s</table><input type="hidden" name="action" value="ltef_owner_save_account"><input type="hidden" name="place" value="%7$d">%8$s<p class="submit"><button class="button button-primary">Save and publish</button></p></form>',
			esc_attr( $uid ),
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_html( get_the_title( $p->ID ) ),
			esc_url( get_permalink( $p->ID ) ),
			$checked ? ' · Hours last checked ' . esc_html( date_i18n( 'M j, Y', strtotime( $checked ) ) ) : '',
			$rows, // built above, escaped
			(int) $p->ID,
			wp_nonce_field( 'ltef_owner_save_' . $p->ID, '_wpnonce', true, false )
		);
		echo function_exists( 'ltef_enhanced_owner_panel' ) ? ltef_enhanced_owner_panel( $p->ID ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped there
	}
	echo '<p class="description">Something else wrong on your page? <a href="' . esc_url( ltef_owner_page_url() ) . '#listing">Tell us</a>. To change your password, open <a href="' . esc_url( admin_url( 'profile.php' ) ) . '">Profile</a>.</p></div>';
}

add_action( 'admin_post_ltef_owner_save_account', 'ltef_owner_handle_save_account' );
function ltef_owner_handle_save_account() {
	$place = isset( $_POST['place'] ) ? absint( $_POST['place'] ) : 0;
	check_admin_referer( 'ltef_owner_save_' . $place );
	$user = wp_get_current_user();
	$back = ltef_owner_admin_url();
	if ( ! current_user_can( LTEF_OWNER_CAP ) || ! $place || 'ltef_place' !== get_post_type( $place ) || ! in_array( ltef_owner_norm( $user->user_email ), ltef_owner_emails( $place ), true ) ) {
		wp_safe_redirect( add_query_arg( 'ltef_owner_error', rawurlencode( 'That listing isn’t one this login manages.' ), $back ) );
		exit;
	}
	$rk = 'ltef_ose_' . md5( ltef_owner_norm( $user->user_email ) );
	$n  = (int) get_transient( $rk );
	if ( $n >= 30 ) {
		wp_safe_redirect( add_query_arg( 'ltef_owner_error', rawurlencode( 'Lots of saves in the last hour. Please try again a little later.' ), $back ) );
		exit;
	}
	set_transient( $rk, $n + 1, HOUR_IN_SECONDS );
	$done = ltef_owner_apply( $place, ltef_owner_norm( $user->user_email ), wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized field by field there
	if ( is_wp_error( $done ) ) {
		wp_safe_redirect( add_query_arg( 'ltef_owner_error', rawurlencode( $done->get_error_message() ), $back ) . '#ltef-own-' . $place );
		exit;
	}
	wp_safe_redirect( add_query_arg( 'ltef_saved', $place, $back ) );
	exit;
}
