<?php
/**
 * Owners keep their own listing up to date.
 *
 * A business claims its place (the "claim" form). Tina approves the claim after calling the number on the
 * listing, and the owner's email goes on the place. From then on the owner asks for a sign-in link by email,
 * no password, no account and no cookie, and edits the facts of the listing: phone, website, menu link, hours
 * a short note labeled as theirs, and a dated announcement that comes down on its end date. Their changes go live at once; Tina is emailed each one and can undo it
 * from the place's edit screen. Tina's take, verdict, videos and labels are never editable by owners.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** How long a sign-in link works. */
const LTEF_OWNER_LINK_TTL = 2 * HOUR_IN_SECONDS;

/** The longest owner note. */
const LTEF_OWNER_NOTE_MAX = 280;

/** Announcements: the longest one, how long one shows when no end date is given, and the furthest end date. */
const LTEF_OWNER_ANNOUNCE_MAX  = 200;
const LTEF_OWNER_ANNOUNCE_DAYS = 30;
const LTEF_OWNER_ANNOUNCE_CAP  = 60;

/** What an owner can change: field => [ label, type, hint ]. */
function ltef_owner_fields() {
	return array(
		'phone'      => array( 'Phone', 'tel', '' ),
		'website'    => array( 'Website', 'url', 'Like yourplace.com' ),
		'menu'       => array( 'Menu link', 'url', 'A page or PDF with your current menu.' ),
		'hours'      => array( 'Hours', 'text', 'Like: Tue–Sun 11–9, closed Mon. Saving marks them checked today.' ),
		'owner_note' => array( 'A note from you', 'textarea', 'Shown on your page as “From the owner”: something that stays true, like cash only, parking out back or BYOB. Up to ' . LTEF_OWNER_NOTE_MAX . ' characters.', LTEF_OWNER_NOTE_MAX ),
		'announce'       => array( 'Announcement', 'textarea', 'News for now: a holiday menu, a pop-up, closed for a week. Shown near the top of your page with the day you post it. Up to ' . LTEF_OWNER_ANNOUNCE_MAX . ' characters. Clear it to take it down.', LTEF_OWNER_ANNOUNCE_MAX ),
		'announce_link'  => array( 'Announcement link', 'url', 'Where to read more, book or order.' ),
		'announce_until' => array( 'Show the announcement until', 'date', 'Leave blank for ' . LTEF_OWNER_ANNOUNCE_DAYS . ' days. Up to ' . LTEF_OWNER_ANNOUNCE_CAP . ' days from today.' ),
	);
}

/** The announcement on a place while it's showing: [ text, link, posted Y-m-d, until Y-m-d ], or null. Only listings with an owner show one. */
function ltef_owner_announcement( $place ) {
	$text  = (string) ltef_meta( $place, 'announce' );
	$until = (string) ltef_meta( $place, 'announce_until' );
	if ( '' === $text || ( $until && $until < ltef_today() ) || ! ltef_owner_emails( $place ) ) {
		return null;
	}
	return array( $text, (string) ltef_meta( $place, 'announce_link' ), (string) ltef_meta( $place, 'announce_posted' ), $until );
}

/** A field's value for the owner's form: an announcement that has ended comes back empty, so saving other fields doesn't bring it back. */
function ltef_owner_form_value( $place, $key ) {
	if ( 0 === strpos( $key, 'announce' ) && ! ltef_owner_announcement( $place ) ) {
		return '';
	}
	return (string) ltef_meta( $place, $key );
}

/** Extra attributes for an owner field: date limits for the announcement's end date. */
function ltef_owner_field_attrs( $key, $type ) {
	if ( 'date' === $type ) {
		return ' min="' . esc_attr( ltef_today() ) . '" max="' . esc_attr( wp_date( 'Y-m-d', strtotime( '+' . LTEF_OWNER_ANNOUNCE_CAP . ' days' ) ) ) . '"';
	}
	return '';
}

/** A place's page changed outside a normal save: clear WordPress's copy and the page caches (Breeze and Varnish), so visitors see it now. */
function ltef_place_refresh( $place ) {
	clean_post_cache( $place );
	if ( function_exists( 'ltef_money_clear_page_cache' ) ) {
		ltef_money_clear_page_cache();
	}
}

/** What an Enhanced listing adds, edited the same way (photos are handled on their own). */
function ltef_owner_enhanced_fields() {
	return array(
		'story'      => array( 'Your story', 'textarea', 'Who you are, what you cook and what to order, in your words. Up to 700 characters.', 700 ),
		'highlights' => array( 'Menu highlights', 'lines', 'One dish per line, with the price after a bar: Margherita pie | $16. Up to 10.', 10 ),
		'links'      => array( 'Booking and ordering links', 'links', 'One per line: what it is, a bar, then the address. Book a table | resy.com/your-place. Up to 3.', 3 ),
	);
}

/** Every owner-editable field's label, for logs and emails. */
function ltef_owner_labels() {
	return array_merge( wp_list_pluck( ltef_owner_fields(), 0 ), wp_list_pluck( ltef_owner_enhanced_fields(), 0 ), array( 'owner_note' => 'From the owner', 'announce_until' => 'Announcement ends', 'gallery' => 'Photos' ) );
}

/** A web address as typed ("mysite.com" too) made whole, or WP_Error. */
function ltef_owner_url( $raw, $label ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}
	if ( ! preg_match( '#^https?://#i', $raw ) ) {
		$raw = 'https://' . $raw;
	}
	$val  = esc_url_raw( $raw, array( 'http', 'https' ) );
	$host = (string) wp_parse_url( $val, PHP_URL_HOST );
	if ( '' === $val || ! filter_var( $val, FILTER_VALIDATE_URL ) || false === strpos( $host, '.' ) ) {
		return new WP_Error( 'url', $label . ' doesn’t look like a web address.' );
	}
	return $val;
}

/* ------------------------------------------------------------------ Who owns what */

function ltef_owner_norm( $email ) {
	return strtolower( trim( sanitize_email( (string) $email ) ) );
}

/** Owner emails on a place. */
function ltef_owner_emails( $place_id ) {
	return array_values( array_unique( array_filter( array_map( 'ltef_owner_norm', (array) get_post_meta( $place_id, 'ltef_owner' ) ) ) ) );
}

function ltef_owner_add( $place_id, $email ) {
	$email = ltef_owner_norm( $email );
	if ( is_email( $email ) && ! in_array( $email, ltef_owner_emails( $place_id ), true ) ) {
		add_post_meta( $place_id, 'ltef_owner', $email );
	}
}

function ltef_owner_remove( $place_id, $email ) {
	delete_post_meta( $place_id, 'ltef_owner', ltef_owner_norm( $email ) );
}

/** Published places an email can edit. */
function ltef_owner_places( $email ) {
	$email = ltef_owner_norm( $email );
	if ( ! $email ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'   => 'ltef_place',
			'post_status' => 'publish',
			'numberposts' => 20,
			'orderby'     => 'title',
			'order'       => 'ASC',
			'meta_query'  => array( array( 'key' => 'ltef_owner', 'value' => $email ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
}

/** Owner data stays out of the REST API and the custom-fields box. */
add_filter(
	'is_protected_meta',
	function ( $protected, $key ) {
		return in_array( $key, array( 'ltef_owner', 'ltef_owner_log', 'ltef_claim_place' ), true ) ? true : $protected;
	},
	10,
	2
);

/* ------------------------------------------------------------------ Sign-in links */

function ltef_owner_token_key( $token ) {
	return 'ltef_ot_' . substr( hash( 'sha256', (string) $token ), 0, 40 );
}

/** A new sign-in link token for an email. Only its hash is stored. */
function ltef_owner_token_make( $email ) {
	$token = bin2hex( random_bytes( 24 ) );
	set_transient( ltef_owner_token_key( $token ), ltef_owner_norm( $email ), LTEF_OWNER_LINK_TTL );
	return $token;
}

/** The email a token signs in, or '' when it's unknown or expired. */
function ltef_owner_token_email( $token ) {
	$token = preg_replace( '/[^a-f0-9]/', '', (string) $token );
	if ( 48 !== strlen( $token ) ) {
		return '';
	}
	$email = get_transient( ltef_owner_token_key( $token ) );
	return is_string( $email ) ? $email : '';
}

/** The page owners manage their listing from. */
function ltef_owner_page_url() {
	$page = get_page_by_path( 'for-restaurants' );
	return $page ? get_permalink( $page ) : home_url( '/for-restaurants/' );
}

function ltef_owner_link( $email ) {
	return add_query_arg( 'owner', ltef_owner_token_make( $email ), ltef_owner_page_url() ) . '#manage';
}

/** Email a sign-in link. $first is true when Tina has just approved the claim. */
function ltef_owner_send_link( $email, $first = false ) {
	$places = ltef_owner_places( $email );
	if ( ! $places ) {
		return false;
	}
	$names = implode( ', ', wp_list_pluck( $places, 'post_title' ) );
	$hours = (int) ( LTEF_OWNER_LINK_TTL / HOUR_IN_SECONDS );
	$lines = $first
		? array( 'Tina approved your claim. You can now keep ' . $names . ' up to date on Live to Eat Food: phone, website, menu link, hours, a short note from you and announcements.', '' )
		: array( 'Here’s your sign-in link for ' . $names . ' on Live to Eat Food.', '' );
	$lines[] = 'Sign in (the link works for ' . $hours . ' hours):';
	$lines[] = ltef_owner_link( $email );
	$lines[] = '';
	$lines[] = 'Your changes go live right away. Tina’s take, her videos and her verdict are hers, so they aren’t part of this.';
	$lines[] = 'Need a new link later? Go to ' . ltef_owner_page_url() . '#manage and enter this email.';
	$lines[] = '';
	$lines[] = 'Didn’t ask for this? You can ignore it; nothing changes unless someone uses the link.';
	return wp_mail( $email, $first ? 'You can now update ' . $names : 'Your sign-in link for ' . $names, implode( "\n", $lines ) );
}

/* ------------------------------------------------------------------ Claims (the review queue) */

/** The place a claim names: the id from the place page when it matches, else an exact title match. */
function ltef_owner_match_place( $name, $id = 0 ) {
	$id = absint( $id );
	if ( $id && 'ltef_place' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
		return $id;
	}
	$name = trim( preg_replace( '/\s+/', ' ', (string) $name ) );
	if ( '' === $name ) {
		return 0;
	}
	$base  = trim( preg_replace( '/\s*\((.*)\)\s*$/', '', $name ) ); // "Name (Town)" from the list
	$found = get_posts( array( 'post_type' => 'ltef_place', 'post_status' => 'publish', 'title' => $base, 'numberposts' => 5, 'fields' => 'ids' ) );
	if ( 1 < count( $found ) && $base !== $name ) {
		preg_match( '/\(([^)]*)\)\s*$/', $name, $m );
		$town  = isset( $m[1] ) ? strtolower( trim( $m[1] ) ) : '';
		$found = array_values( array_filter( $found, function ( $p ) use ( $town ) { return strtolower( (string) ltef_meta( $p, 'town' ) ) === $town; } ) );
	}
	return 1 === count( $found ) ? (int) $found[0] : 0;
}

/** "Name (Town)", as the claim form's list shows a place. */
function ltef_owner_place_label( $id ) {
	$town = ltef_meta( $id, 'town' );
	return get_the_title( $id ) . ( $town ? ' (' . $town . ')' : '' );
}

/** The claim form's list of places to pick from. */
function ltef_owner_datalist( $id ) {
	$ids  = get_posts( array( 'post_type' => 'ltef_place', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids' ) );
	$opts = '';
	foreach ( $ids as $p ) {
		$opts .= '<option value="' . esc_attr( ltef_owner_place_label( $p ) ) . '"></option>';
	}
	return '<datalist id="' . esc_attr( $id ) . '">' . $opts . '</datalist>';
}

/** Review-queue chips for a claim: which place, and the number Tina calls to check. */
function ltef_owner_claim_flags( $post_id ) {
	$place = (int) get_post_meta( $post_id, 'ltef_claim_place', true );
	if ( ! $place ) {
		return array( array( 'warn', 'No place on the site matched. Add their email under Owners on the right place, then mark this done.' ) );
	}
	$flags   = array( array( '', 'Claims <a href="' . esc_url( get_permalink( $place ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $place ) ) . '</a>' ) );
	$phone   = ltef_meta( $place, 'phone' );
	$flags[] = $phone
		? array( '', 'Call the listing to confirm: ' . esc_html( $phone ) )
		: array( 'warn', 'The listing has no phone. Confirm it another way first.' );
	$owners  = ltef_owner_emails( $place );
	if ( $owners ) {
		$flags[] = array( 'warn', 'Already managed by ' . esc_html( implode( ', ', $owners ) ) );
	}
	$fields = get_post_meta( $post_id, 'ltef_fields', true );
	$sugg   = array();
	foreach ( ltef_owner_claim_map() as $from => $to ) {
		if ( is_array( $fields ) && '' !== (string) ( $fields[ $from ] ?? '' ) ) {
			$sugg[] = ltef_owner_short( $to );
		}
	}
	if ( $sugg ) {
		$flags[] = array( 'pay', 'Approving puts their ' . esc_html( implode( ', ', $sugg ) ) . ' live' );
	}
	return $flags;
}

/** Claim form name => listing field, for the details an owner can suggest on their claim. */
/** Short names for listing fields in Tina's notes: "their phone, hours and note". */
function ltef_owner_short( $key ) {
	return array( 'phone' => 'phone', 'website' => 'website', 'menu' => 'menu link', 'hours' => 'hours', 'owner_note' => 'note' )[ $key ] ?? $key;
}

function ltef_owner_claim_map() {
	return array( 'listing_phone' => 'phone', 'website' => 'website', 'menu' => 'menu', 'hours' => 'hours', 'owner_note' => 'owner_note' );
}

/** The listing details on a claim, checked as they would be on the owner's own screen. [ claim name => clean value ] or WP_Error. */
function ltef_owner_claim_details( $v ) {
	$in = array();
	foreach ( ltef_owner_claim_map() as $from => $to ) {
		if ( '' !== (string) ( $v[ $from ] ?? '' ) ) {
			$in[ $to ] = $v[ $from ];
		}
	}
	$clean = ltef_owner_clean( $in, array_intersect_key( ltef_owner_fields(), $in ) );
	if ( is_wp_error( $clean ) ) {
		return $clean;
	}
	$out = array();
	foreach ( ltef_owner_claim_map() as $from => $to ) {
		if ( isset( $clean[ $to ] ) ) {
			$out[ $from ] = $clean[ $to ];
		}
	}
	return $out;
}

/** Approving a claim: the owner gets their login, and the details they suggested go live. [ publish, note ] */
function ltef_owner_on_approve( $post_id ) {
	$place  = (int) get_post_meta( $post_id, 'ltef_claim_place', true );
	$fields = get_post_meta( $post_id, 'ltef_fields', true );
	$email  = is_array( $fields ) ? ( $fields['email'] ?? '' ) : '';
	if ( ! $place || ! is_email( $email ) ) {
		return array( false, 'Pick the place first: no listing matched this claim. Add their email under Owners on the place instead.' );
	}
	$note  = ltef_owner_grant( $place, $email, $fields['name'] ?? '' );
	$sugg  = array();
	foreach ( ltef_owner_claim_map() as $from => $to ) {
		if ( '' !== (string) ( $fields[ $from ] ?? '' ) ) {
			$sugg[ $to ] = $fields[ $from ];
		}
	}
	if ( $sugg ) {
		$done = ltef_owner_apply( $place, ltef_owner_norm( $email ), $sugg, array_intersect_key( ltef_owner_fields(), $sugg ) );
		$note .= is_wp_error( $done ) ? ' Their listing details weren’t saved: ' . $done->get_error_message() : ( $done ? ' Their details are live (' . implode( ', ', array_map( function ( $c ) { return ltef_owner_short( $c['key'] ); }, $done ) ) . '); undo any of it on the place.' : '' );
	}
	return array( true, $note );
}

/* ------------------------------------------------------------------ The owner area (For restaurants) */

/** Sign-in links carry a token: never cache that page, never index it, never leak it to other sites. */
add_action(
	'template_redirect',
	function () {
		if ( isset( $_GET['owner'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			nocache_headers();
			header( 'Referrer-Policy: no-referrer' );
			add_filter( 'wp_robots', 'wp_robots_no_robots' );
		}
	}
);

function ltef_block_owner_area() {
	$token   = isset( $_GET['owner'] ) ? sanitize_text_field( wp_unslash( $_GET['owner'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$email   = $token ? ltef_owner_token_email( $token ) : '';
	$places  = $email ? ltef_owner_places( $email ) : array();
	$notice  = '';
	$saved   = isset( $_GET['ltef_saved'] ) ? absint( $_GET['ltef_saved'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$err     = isset( $_GET['ltef_owner_error'] ) ? sanitize_text_field( wp_unslash( $_GET['ltef_owner_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $places ) {
		if ( $saved ) {
			$notice = '<p class="ltef-sent-line" role="status">Saved. It’s live on <a href="' . esc_url( get_permalink( $saved ) ) . '">' . esc_html( get_the_title( $saved ) ) . '’s page</a>.</p>';
		} elseif ( $err ) {
			$notice = '<p class="ltef-form__error" role="alert">' . esc_html( $err ) . '</p>';
		}
		$out = '<div class="ltef-owner-area" id="ltef-owner" tabindex="-1"><p class="meta">Signed in as ' . esc_html( $email ) . ' until the link runs out. Changes go live right away; Tina sees each one.</p>' . $notice;
		foreach ( $places as $p ) {
			$out .= ltef_owner_editor( $p->ID, $token );
		}
		return ltef_wrap( $out . '</div>' );
	}
	$out = '';
	if ( function_exists( 'ltef_owner_is_limited' ) && ltef_owner_is_limited() ) {
		return ltef_wrap( '<div class="panel ltef-sent" id="ltef-owner"><p class="h-m">You’re signed in.</p><p><a class="ltef-btn" href="' . esc_url( ltef_owner_admin_url() ) . '">Go to your listing</a></p></div>' );
	}
	if ( $token ) {
		$out .= '<p class="ltef-form__error" role="alert">That sign-in link has run out or was already replaced. Sign in with your password, or enter your email for a new link.</p>';
	}
	$sent = isset( $_GET['ltef_link'] ) && 'sent' === sanitize_key( wp_unslash( $_GET['ltef_link'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $sent ) {
		return ltef_wrap( '<div class="panel ltef-sent" id="ltef-owner" tabindex="-1"><p class="h-m">Check your email.</p><p>If that address manages a listing here, a sign-in link is on its way. It works for ' . (int) ( LTEF_OWNER_LINK_TTL / HOUR_IN_SECONDS ) . ' hours.</p></div>' );
	}
	if ( $err ) {
		$out .= '<p class="ltef-form__error" role="alert">' . esc_html( $err ) . '</p>';
	}
	$out .= function_exists( 'ltef_owner_admin_url' ) ? '<div class="panel ltef-owner-signin"><h2 class="h-m" style="font-size: 30px;">Sign in</h2><p style="margin: 0;">With the username or email and the password you set when Tina approved your claim.</p><p style="margin: 0;"><a class="ltef-btn" href="' . esc_url( wp_login_url( ltef_owner_admin_url() ) ) . '">Sign in to your listing</a></p></div>' : '';
	$out .= '<form class="panel ltef-form" id="ltef-owner" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" aria-labelledby="ltef-owner-h">'
		. '<h2 id="ltef-owner-h" class="h-m" style="font-size: 30px;">Or get a one-time link</h2><p style="margin: 0;">No password needed: we email a link that works for two hours. Use the email Tina approved.</p>'
		. '<div class="form-grid"><div class="ltef-field"><label class="ltef-field__label" for="ltef-owner-email">Email</label><input class="ltef-input" type="email" id="ltef-owner-email" name="email" required autocomplete="email"></div></div>'
		. '<input type="hidden" name="action" value="ltef_owner_link">'
		. '<input type="hidden" name="ltef_t" value="' . esc_attr( time() . '.' . wp_hash( time() . 'owner' ) ) . '">'
		. '<p class="ltef-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="ltef_website" tabindex="-1" autocomplete="off"></label></p>'
		. ( function_exists( 'ltef_turnstile_ready' ) && ltef_turnstile_ready() ? ltef_turnstile_widget( 'owner-link' ) : '' )
		. '<button class="ltef-btn" type="submit">Email me a sign-in link</button></form>';
	return ltef_wrap( '<div class="ltef-owner-area">' . $out . '</div>' );
}

/** One place's editor. */
function ltef_owner_editor( $place_id, $token ) {
	$uid    = 'ltef-own-' . $place_id;
	$fields = '';
	foreach ( ltef_owner_fields() as $key => $def ) {
		list( $label, $type, $hint ) = $def;
		$id    = $uid . '-' . $key;
		$val   = ltef_owner_form_value( $place_id, $key );
		$desc  = $hint ? ' aria-describedby="' . esc_attr( $id ) . '-h"' : '';
		$h     = $hint ? '<p class="ltef-field__hint" id="' . esc_attr( $id ) . '-h">' . esc_html( $hint ) . '</p>' : '';
		$ac    = array( 'phone' => ' autocomplete="tel"', 'website' => ' autocomplete="url"' )[ $key ] ?? '';
		$ctl   = 'textarea' === $type
			? '<textarea class="ltef-input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" rows="3" maxlength="' . (int) ( $def[3] ?? LTEF_OWNER_NOTE_MAX ) . '"' . $desc . '>' . esc_textarea( $val ) . '</textarea>'
			// Web addresses are plain text so “mysite.com” isn't refused by the browser; the server adds https://.
			: '<input class="ltef-input" type="' . ( 'url' === $type ? 'text" inputmode="url' : esc_attr( $type ) ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '"' . $desc . $ac . ltef_owner_field_attrs( $key, $type ) . ( 'url' === $type ? ' spellcheck="false" autocapitalize="off"' : '' ) . '>';
		$fields .= '<div class="ltef-field' . ( 'textarea' === $type ? ' ltef-field--wide' : '' ) . '"><label class="ltef-field__label" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . ' <span class="ltef-field__opt">(optional)</span></label>' . $h . $ctl . '</div>';
	}
	$checked = ltef_meta( $place_id, 'hours_checked' );
	return '<form class="panel ltef-form ltef-owner__place" id="' . esc_attr( $uid ) . '" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" aria-labelledby="' . esc_attr( $uid ) . '-h">'
		. '<h2 id="' . esc_attr( $uid ) . '-h" class="h-m" style="font-size: 30px;">' . esc_html( get_the_title( $place_id ) ) . '</h2>'
		. '<p style="margin: 0;"><a href="' . esc_url( get_permalink( $place_id ) ) . '">See your page</a>' . ( $checked ? ' · Hours last checked ' . esc_html( date_i18n( 'M j, Y', strtotime( $checked ) ) ) : '' ) . '</p>'
		. '<div class="form-grid">' . $fields . '</div>'
		. '<input type="hidden" name="action" value="ltef_owner_save"><input type="hidden" name="owner" value="' . esc_attr( $token ) . '"><input type="hidden" name="place" value="' . (int) $place_id . '">'
		. '<button class="ltef-btn" type="submit">Save and publish</button>'
		. '<p class="meta" style="margin: 0; font-size: 13px;">Tina’s take, her videos and her verdict are hers, so they aren’t here. Something else wrong on the page? <a href="' . esc_url( ltef_owner_page_url() ) . '#listing">Tell us</a>.</p>'
		. '</form>';
}

/* ------------------------------------------------------------------ Asking for a link */

add_action( 'admin_post_nopriv_ltef_owner_link', 'ltef_owner_handle_link' );
add_action( 'admin_post_ltef_owner_link', 'ltef_owner_handle_link' );
function ltef_owner_handle_link() {
	$back = ltef_owner_page_url();
	$fail = function ( $msg ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'ltef_owner_error', rawurlencode( $msg ), $back ) . '#manage' );
		exit;
	};
	$done = function () use ( $back ) {
		wp_safe_redirect( add_query_arg( 'ltef_link', 'sent', $back ) . '#manage' );
		exit;
	};
	$t = isset( $_POST['ltef_t'] ) ? explode( '.', sanitize_text_field( wp_unslash( $_POST['ltef_t'] ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Turnstile, the honeypot and rate limits guard this form; it only ever emails an owner
	if ( ! empty( $_POST['ltef_website'] ) || count( $t ) !== 2 || ! hash_equals( wp_hash( $t[0] . 'owner' ), $t[1] ) || time() - (int) $t[0] < 2 ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$done();
	}
	if ( function_exists( 'ltef_turnstile_ready' ) && ltef_turnstile_ready() ) {
		$ok = ltef_turnstile_verify();
		if ( is_wp_error( $ok ) ) {
			$fail( $ok->get_error_message() );
		}
	}
	$email = ltef_owner_norm( wp_unslash( $_POST['email'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- normalized by sanitize_email
	if ( ! is_email( $email ) ) {
		$fail( 'That email doesn’t look right.' );
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	foreach ( array( 'ltef_olr_' . md5( $ip ) => 10, 'ltef_ole_' . md5( $email ) => 4 ) as $k => $max ) {
		$n = (int) get_transient( $k );
		if ( $n >= $max ) {
			$fail( 'Lots of sign-in links in the last hour. Please use the newest one, or try again later.' );
		}
		set_transient( $k, $n + 1, HOUR_IN_SECONDS );
	}
	ltef_owner_send_link( $email ); // the same answer either way, so nobody can test which emails own a listing
	$done();
}

/* ------------------------------------------------------------------ Saving */

add_action( 'admin_post_nopriv_ltef_owner_save', 'ltef_owner_handle_save' );
add_action( 'admin_post_ltef_owner_save', 'ltef_owner_handle_save' );
function ltef_owner_handle_save() {
	// The sign-in token in the form is the proof: only someone who opened the emailed link has it.
	$token = isset( $_POST['owner'] ) ? sanitize_text_field( wp_unslash( $_POST['owner'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$place = isset( $_POST['place'] ) ? absint( $_POST['place'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$email = ltef_owner_token_email( $token );
	$back  = add_query_arg( 'owner', $token, ltef_owner_page_url() );
	if ( ! $email ) {
		wp_safe_redirect( $back . '#manage' );
		exit;
	}
	$fail = function ( $msg ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'ltef_owner_error', rawurlencode( $msg ), $back ) . '#manage' );
		exit;
	};
	if ( ! $place || 'ltef_place' !== get_post_type( $place ) || ! in_array( $email, ltef_owner_emails( $place ), true ) ) {
		$fail( 'That listing isn’t one this email manages.' );
	}
	$rk = 'ltef_ose_' . md5( $email );
	$n  = (int) get_transient( $rk );
	if ( $n >= 30 ) {
		$fail( 'Lots of saves in the last hour. Please try again a little later.' );
	}
	set_transient( $rk, $n + 1, HOUR_IN_SECONDS );

	$done = ltef_owner_apply( $place, $email, wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized field by field there
	if ( is_wp_error( $done ) ) {
		$fail( $done->get_error_message() );
	}
	wp_safe_redirect( add_query_arg( 'ltef_saved', $place, $back ) . '#manage' );
	exit;
}

/**
 * Owner input sanitized field by field: [ key => value ] for every field given, or WP_Error saying what to fix.
 * A field missing from the input comes back empty, so callers that only change some fields pass only those.
 */
function ltef_owner_clean( $input, $fields ) {
	$new = array();
	foreach ( $fields as $key => $def ) {
		$raw  = isset( $input[ $key ] ) ? (string) $input[ $key ] : '';
		$type = $def[1];
		if ( 'url' === $type ) {
			$val = ltef_owner_url( $raw, $def[0] );
			if ( is_wp_error( $val ) ) {
				return $val;
			}
		} elseif ( 'textarea' === $type ) {
			$val = trim( sanitize_textarea_field( $raw ) );
			$max = (int) ( $def[3] ?? LTEF_OWNER_NOTE_MAX );
			if ( mb_strlen( $val ) > $max ) {
				return new WP_Error( 'long', $def[0] . ' is over ' . $max . ' characters.' );
			}
		} elseif ( 'date' === $type ) {
			$val = trim( sanitize_text_field( $raw ) );
			if ( '' !== $val ) {
				$d = DateTime::createFromFormat( '!Y-m-d', $val );
				if ( ! $d || $d->format( 'Y-m-d' ) !== $val ) {
					return new WP_Error( 'date', $def[0] . ': pick a date.' );
				}
				if ( $val < ltef_today() ) {
					return new WP_Error( 'date', $def[0] . ' can’t be in the past.' );
				}
				if ( $val > wp_date( 'Y-m-d', strtotime( '+' . LTEF_OWNER_ANNOUNCE_CAP . ' days' ) ) ) {
					return new WP_Error( 'date', $def[0] . ' can be up to ' . LTEF_OWNER_ANNOUNCE_CAP . ' days from today.' );
				}
			}
		} elseif ( 'lines' === $type || 'links' === $type ) {
			$rows = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', sanitize_textarea_field( $raw ) ) ) ) );
			if ( count( $rows ) > (int) $def[3] ) {
				return new WP_Error( 'many', $def[0] . ': up to ' . (int) $def[3] . ', please.' );
			}
			foreach ( $rows as $i => $row ) {
				$parts = array_map( 'trim', explode( '|', $row, 2 ) );
				if ( 'links' === $type ) {
					if ( count( $parts ) < 2 || '' === $parts[0] ) {
						return new WP_Error( 'link', 'Each link needs a name, a bar, then the address: Book a table | resy.com/your-place' );
					}
					$u = ltef_owner_url( $parts[1], 'The link for “' . $parts[0] . '”' );
					if ( is_wp_error( $u ) || '' === $u ) {
						return is_wp_error( $u ) ? $u : new WP_Error( 'link', 'The link for “' . $parts[0] . '” has no address.' );
					}
					$rows[ $i ] = mb_substr( $parts[0], 0, 30 ) . ' | ' . $u;
				} else {
					$rows[ $i ] = mb_substr( $parts[0], 0, 70 ) . ( isset( $parts[1] ) && '' !== $parts[1] ? ' | ' . mb_substr( $parts[1], 0, 20 ) : '' );
				}
			}
			$val = implode( "\n", $rows );
		} else {
			$val = mb_substr( sanitize_text_field( $raw ), 0, 'hours' === $key ? 160 : 40 );
		}
		$new[ $key ] = $val;
	}
	return $new;
}

/**
 * Saves an owner's changes to a place: sanitized, logged, live at once, and emailed to Tina.
 * Returns the changes, or a WP_Error saying what to fix. Callers check who's asking first.
 */
function ltef_owner_apply( $place, $email, $input, $fields = null ) {
	$new = ltef_owner_clean( $input, null === $fields ? ltef_owner_fields() : $fields );
	if ( is_wp_error( $new ) ) {
		return $new;
	}
	if ( array_key_exists( 'announce', $new ) ) {
		if ( '' === $new['announce'] ) {
			$new['announce_link']  = ''; // taking it down takes its link and end date with it
			$new['announce_until'] = '';
		} elseif ( '' === ( $new['announce_until'] ?? '' ) ) {
			$new['announce_until'] = wp_date( 'Y-m-d', strtotime( '+' . LTEF_OWNER_ANNOUNCE_DAYS . ' days' ) );
		}
	}

	$log     = get_post_meta( $place, 'ltef_owner_log', true );
	$log     = is_array( $log ) ? $log : array();
	$changes = array();
	foreach ( $new as $key => $val ) {
		$old = (string) ltef_meta( $place, $key );
		if ( $old === $val ) {
			continue;
		}
		update_post_meta( $place, 'ltef_' . $key, $val );
		$changes[] = array( 'key' => $key, 'old' => $old, 'new' => $val );
		$log[]     = array( 't' => time(), 'email' => $email, 'key' => $key, 'old' => $old, 'new' => $val, 'undone' => false );
	}
	if ( '' !== ( $new['announce'] ?? '' ) && in_array( 'announce', wp_list_pluck( $changes, 'key' ), true ) ) {
		update_post_meta( $place, 'ltef_announce_posted', wp_date( 'Y-m-d' ) ); // new words, new date
	}
	if ( '' !== ( $new['hours'] ?? '' ) ) {
		update_post_meta( $place, 'ltef_hours_checked', wp_date( 'Y-m-d' ) ); // they looked at the hours and saved them
	}
	if ( $changes ) {
		update_post_meta( $place, 'ltef_owner_log', array_slice( $log, -60 ) );
		update_post_meta( $place, 'ltef_owner_updated', wp_date( 'Y-m-d' ) );
		ltef_place_refresh( $place );
		do_action( 'ltef_owner_saved', $place, $changes, $email );
		$to = ltef_setting( 'notify_email' );
		if ( $to ) {
			$labels = ltef_owner_labels();
			$lines  = array( $email . ' changed ' . get_the_title( $place ) . '. It’s live now.', '' );
			foreach ( $changes as $c ) {
				$lines[] = $labels[ $c['key'] ] . ': ' . ( '' === $c['old'] ? '(empty)' : $c['old'] ) . '  →  ' . ( '' === $c['new'] ? '(empty)' : $c['new'] );
			}
			$lines[] = '';
			$lines[] = 'See the page: ' . get_permalink( $place );
			$lines[] = 'Undo any of it under Owner changes: ' . admin_url( 'post.php?post=' . $place . '&action=edit' ) . '#ltef-owners';
			wp_mail( $to, 'Owner update: ' . get_the_title( $place ), implode( "\n", $lines ) );
		}
	}
	return $changes;
}

/* ------------------------------------------------------------------ Tina's side: the Owners box */

add_action(
	'add_meta_boxes_ltef_place',
	function () {
		add_meta_box( 'ltef-owners', 'Owners', 'ltef_owner_meta_box', 'ltef_place', 'side', 'default' );
	}
);

function ltef_owner_meta_box( $post ) {
	wp_nonce_field( 'ltef_owners_' . $post->ID, 'ltef_owners_nonce' );
	$owners = ltef_owner_emails( $post->ID );
	echo '<p class="description">People who can update the phone, website, menu link, hours, their note and an announcement, with their own login or an email link. Approve a claim in the review queue, or add an email here once you’ve confirmed it’s theirs.</p>';
	if ( $owners ) {
		echo '<ul>';
		foreach ( $owners as $i => $e ) {
			$u = get_user_by( 'email', $e );
			printf( '<li><label><input type="checkbox" name="ltef_owner_remove[]" value="%1$s"> Remove %1$s</label>%2$s</li>', esc_attr( $e ), $u ? '<br><span class="description">Login: <a href="' . esc_url( get_edit_user_link( $u->ID ) ) . '">' . esc_html( $u->user_login ) . '</a></span>' : '' );
		}
		echo '</ul>';
	} else {
		echo '<p><em>No owner yet. The page invites them to claim it.</em></p>';
	}
	if ( function_exists( 'ltef_enhanced_sub' ) ) {
		$sub = ltef_enhanced_sub( $post->ID );
		if ( ! empty( $sub['id'] ) && function_exists( 'ltef_enhanced_sub_live' ) && ltef_enhanced_sub_live( $post->ID ) ) {
			$end = ! empty( $sub['period_end'] ) ? wp_date( 'M j', (int) $sub['period_end'] ) : '';
			printf( '<p><strong>Enhanced: paid by subscription.</strong><br><span class="description">%s. The Enhanced checkbox follows Stripe while this runs; cancel in Stripe to stop it.</span></p>', esc_html( ! empty( $sub['cancel_at_period_end'] ) ? 'Canceled, ends ' . $end : ( 'past_due' === ( $sub['status'] ?? '' ) ? 'Payment failed, Stripe is retrying' : 'Renews ' . $end ) ) );
		}
	}
	echo '<p><label for="ltef-owner-add">Add an owner email</label><input type="email" id="ltef-owner-add" name="ltef_owner_add" class="widefat"></p>';
	echo '<p><label><input type="checkbox" name="ltef_owner_send" value="1" checked> Make them a login and email them to set a password</label></p>';

	$log = get_post_meta( $post->ID, 'ltef_owner_log', true );
	if ( is_array( $log ) && $log ) {
		$labels = ltef_owner_labels();
		echo '<h4 style="margin: 1.2em 0 .4em;">Owner changes</h4><ol class="ltef-owner-log" reversed>';
		$shown = 0;
		foreach ( array_reverse( $log, true ) as $i => $c ) {
			if ( ++$shown > 15 ) {
				break;
			}
			// Only the newest change to a field can be undone, and only while the page still shows it.
			$current = 'gallery' === $c['key'] ? '' : (string) ltef_meta( $post->ID, $c['key'] );
			$can     = 'gallery' === $c['key'] ? ! empty( $c['att'] ) && in_array( (int) $c['att'], ltef_owner_gallery( $post->ID ), true ) : $current === $c['new'];
			if ( $c['undone'] ) {
				$undo = '<em>Undone</em>';
			} elseif ( 'gallery' === $c['key'] && empty( $c['att'] ) ) {
				$undo = '<em>Can’t be undone</em>';
			} elseif ( $can ) {
				$undo = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ltef_owner_undo&post=' . $post->ID . '&i=' . (int) $i ), 'ltef_owner_undo_' . $post->ID ) ) . '">Undo</a>';
			} else {
				$undo = '<em>Changed since</em>';
			}
			printf(
				'<li><strong>%1$s</strong><br><span class="description">%2$s → %3$s</span><br><span class="description">%4$s · %5$s</span> · %6$s</li>',
				esc_html( $labels[ $c['key'] ] ?? $c['key'] ),
				esc_html( '' === $c['old'] ? '(empty)' : wp_trim_words( $c['old'], 12 ) ),
				esc_html( '' === $c['new'] ? '(empty)' : wp_trim_words( $c['new'], 12 ) ),
				esc_html( $c['email'] ),
				esc_html( wp_date( 'M j, g:ia', $c['t'] ) ),
				$undo // built above, escaped
			);
		}
		echo '</ol>';
	}
}

add_action(
	'save_post_ltef_place',
	function ( $post_id ) {
		if ( ! isset( $_POST['ltef_owners_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ltef_owners_nonce'] ) ), 'ltef_owners_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( (array) ( $_POST['ltef_owner_remove'] ?? array() ) as $e ) {
			ltef_owner_remove( $post_id, sanitize_email( wp_unslash( $e ) ) );
		}
		$add = ltef_owner_norm( wp_unslash( $_POST['ltef_owner_add'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- normalized by sanitize_email
		if ( is_email( $add ) && ! in_array( $add, ltef_owner_emails( $post_id ), true ) ) {
			if ( ! empty( $_POST['ltef_owner_send'] ) && 'publish' === get_post_status( $post_id ) && function_exists( 'ltef_owner_grant' ) ) {
				ltef_owner_grant( $post_id, $add );
			} else {
				ltef_owner_add( $post_id, $add );
			}
		}
	},
	20
);

add_action( 'admin_post_ltef_owner_undo', 'ltef_owner_handle_undo' );
function ltef_owner_handle_undo() {
	$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	check_admin_referer( 'ltef_owner_undo_' . $id );
	if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Not allowed.' );
	}
	$i   = isset( $_GET['i'] ) ? absint( $_GET['i'] ) : -1;
	$log = get_post_meta( $id, 'ltef_owner_log', true );
	$c = is_array( $log ) && isset( $log[ $i ] ) && empty( $log[ $i ]['undone'] ) ? $log[ $i ] : null;
	if ( $c && 'gallery' === $c['key'] && ! empty( $c['att'] ) && in_array( (int) $c['att'], ltef_owner_gallery( $id ), true ) ) {
		ltef_owner_gallery_remove( $id, (int) $c['att'] ); // an added photo comes off and is deleted
		$log[ $i ]['undone'] = true;
		update_post_meta( $id, 'ltef_owner_log', $log );
	} elseif ( $c && array_key_exists( $c['key'], ltef_owner_fields() + ltef_owner_enhanced_fields() ) && (string) ltef_meta( $id, $c['key'] ) === $c['new'] ) {
		update_post_meta( $id, 'ltef_' . $log[ $i ]['key'], $log[ $i ]['old'] );
		$log[ $i ]['undone'] = true;
		update_post_meta( $id, 'ltef_owner_log', $log );
	}
	ltef_place_refresh( $id );
	wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) . '#ltef-owners' );
	exit;
}

/* ------------------------------------------------------------------ Announcements that have ended */

/** Once a day, just after midnight, take down announcements past their end date, so cached pages stop showing them. */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ltef_owner_daily' ) ) {
			$midnight = ( new DateTime( 'tomorrow', wp_timezone() ) )->getTimestamp();
			wp_schedule_event( $midnight + 5 * MINUTE_IN_SECONDS, 'daily', 'ltef_owner_daily' );
		}
	}
);
add_action(
	'ltef_owner_daily',
	function () {
		$ended = get_posts(
			array(
				'post_type'   => 'ltef_place',
				'post_status' => 'any',
				'numberposts' => 200,
				'fields'      => 'ids',
				'meta_query'  => array( array( 'key' => 'ltef_announce_until', 'value' => array( '0000-00-01', ltef_today() ), 'compare' => 'BETWEEN' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$ended = array_filter( $ended, function ( $id ) { return (string) ltef_meta( $id, 'announce_until' ) < ltef_today(); } );
		foreach ( $ended as $id ) {
			foreach ( array( 'announce', 'announce_link', 'announce_until', 'announce_posted' ) as $k ) {
				delete_post_meta( $id, 'ltef_' . $k );
			}
			clean_post_cache( $id );
		}
		if ( $ended && function_exists( 'ltef_money_clear_page_cache' ) ) {
			ltef_money_clear_page_cache();
		}
	}
);
