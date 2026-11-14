<?php
/**
 * Enhanced listings: what an owner adds when they upgrade, and the monthly subscription that pays for it.
 *
 * An owner upgrades from their "Your listing" screen. Stripe's checkout starts a monthly subscription at the
 * Enhanced price (Money → Prices & split); Stripe's webhook turns Enhanced on, records each month's payment and
 * fee in Money, and turns it off when the subscription ends. Cancelling from the listing screen keeps Enhanced
 * until the end of the month already paid for. Tina can also switch Enhanced on by hand on the place.
 *
 * Enhanced adds the owner's photos, story, menu highlights and booking links to their page, after Tina's take and
 * labeled as theirs. It never changes her take, the order of any list, or whether a place is in a guide.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

const LTEF_ENH_PHOTOS = 8;

function ltef_enhanced_on( $place ) {
	return (bool) ltef_meta( $place, 'enhanced' );
}

/** The subscription record on a place: id, customer, status, period_end, cancel_at_period_end, user. */
function ltef_enhanced_sub( $place ) {
	$s = get_post_meta( $place, 'ltef_sub', true );
	return is_array( $s ) ? $s : array();
}
function ltef_enhanced_sub_set( $place, $changes ) {
	update_post_meta( $place, 'ltef_sub', array_merge( ltef_enhanced_sub( $place ), $changes ) );
}

function ltef_enhanced_price_cents() {
	return ltef_cents( ltef_setting( 'enhanced_price' ) );
}

/* ------------------------------------------------------------------ Photos */

/** The owner's photos on a place, as attachment ids, in order. */
function ltef_owner_gallery( $place ) {
	$g = get_post_meta( $place, 'ltef_gallery', true );
	return is_array( $g ) ? array_values( array_filter( array_map( 'absint', $g ) ) ) : array();
}

function ltef_owner_gallery_remove( $place, $att ) {
	update_post_meta( $place, 'ltef_gallery', array_values( array_diff( ltef_owner_gallery( $place ), array( (int) $att ) ) ) );
	wp_delete_attachment( (int) $att, true );
	ltef_place_refresh( $place );
}

/** Saves an uploaded photo from $_FILES[ $field ] to a place. The attachment id, or WP_Error. */
function ltef_owner_gallery_add( $place, $field, $alt ) {
	if ( count( ltef_owner_gallery( $place ) ) >= LTEF_ENH_PHOTOS ) {
		return new WP_Error( 'many', 'You have ' . LTEF_ENH_PHOTOS . ' photos already. Remove one to add another.' );
	}
	$alt = trim( sanitize_text_field( $alt ) );
	if ( '' === $alt ) {
		return new WP_Error( 'alt', 'Describe the photo in a few words, for people who can’t see it.' );
	}
	if ( (int) ( $_FILES[ $field ]['size'] ?? 0 ) > 10 * MB_IN_BYTES ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by the caller
		return new WP_Error( 'big', 'That photo is over 10 MB.' );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$att = media_handle_upload( $field, $place, array( 'post_title' => mb_substr( $alt, 0, 120 ) ), array( 'test_form' => false, 'mimes' => array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) ) );
	if ( is_wp_error( $att ) ) {
		return new WP_Error( 'upload', 'That photo didn’t upload: use a JPG, PNG or WebP under 10 MB.' );
	}
	update_post_meta( $att, '_wp_attachment_image_alt', mb_substr( $alt, 0, 160 ) );
	update_post_meta( $place, 'ltef_gallery', array_merge( ltef_owner_gallery( $place ), array( (int) $att ) ) );
	return (int) $att;
}

/* ------------------------------------------------------------------ Full menu (PDFs or photos) */

const LTEF_ENH_MENU_FILES = 6;

/** The owner's menu files on a place, as attachment ids, in order. */
function ltef_owner_menu_files( $place ) {
	$m = get_post_meta( $place, 'ltef_menu_files', true );
	return is_array( $m ) ? array_values( array_filter( array_map( 'absint', $m ) ) ) : array();
}

function ltef_owner_menu_remove( $place, $att ) {
	update_post_meta( $place, 'ltef_menu_files', array_values( array_diff( ltef_owner_menu_files( $place ), array( (int) $att ) ) ) );
	wp_delete_attachment( (int) $att, true );
	ltef_place_refresh( $place );
}

/** Saves an uploaded menu (PDF or photo) from $_FILES[ $field ]. The attachment id, or WP_Error. */
function ltef_owner_menu_add( $place, $field, $label ) {
	if ( count( ltef_owner_menu_files( $place ) ) >= LTEF_ENH_MENU_FILES ) {
		return new WP_Error( 'many', 'You have ' . LTEF_ENH_MENU_FILES . ' menu files already. Remove one to add another.' );
	}
	$label = trim( sanitize_text_field( $label ) );
	if ( '' === $label ) {
		return new WP_Error( 'label', 'Name the menu, like “Dinner menu” or “Drinks”.' );
	}
	if ( (int) ( $_FILES[ $field ]['size'] ?? 0 ) > 15 * MB_IN_BYTES ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by the caller
		return new WP_Error( 'big', 'That file is over 15 MB.' );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$att = media_handle_upload( $field, $place, array( 'post_title' => mb_substr( $label, 0, 80 ) ), array( 'test_form' => false, 'mimes' => array( 'pdf' => 'application/pdf', 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) ) );
	if ( is_wp_error( $att ) ) {
		return new WP_Error( 'upload', 'That menu didn’t upload: use a PDF, JPG, PNG or WebP under 15 MB.' );
	}
	update_post_meta( $att, '_wp_attachment_image_alt', mb_substr( $label, 0, 160 ) );
	update_post_meta( $place, 'ltef_menu_files', array_merge( ltef_owner_menu_files( $place ), array( (int) $att ) ) );
	return (int) $att;
}

/** The "Full menu" part of an Enhanced page: PDFs as buttons, photos as tiles that open full size. */
function ltef_enhanced_menu_html( $id ) {
	$files = ltef_owner_menu_files( $id );
	if ( ! $files ) {
		return '';
	}
	$pdfs  = '';
	$pics  = '';
	foreach ( $files as $a ) {
		$name = get_the_title( $a );
		$url  = wp_get_attachment_url( $a );
		if ( ! $url ) {
			continue;
		}
		if ( wp_attachment_is_image( $a ) ) {
			$pics .= '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . wp_get_attachment_image( $a, 'medium_large', false, array( 'loading' => 'lazy', 'alt' => '' ) ) . '<span>' . esc_html( $name ) . '<span class="vh"> (opens full size in a new tab)</span></span></a></li>';
		} else {
			$pdfs .= '<a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $name ) . ' <span class="enh-fullmenu__kind">PDF</span><span class="vh"> (opens in a new tab)</span></a>';
		}
	}
	if ( '' === $pdfs . $pics ) {
		return '';
	}
	return '<div class="enh-fullmenu" id="full-menu"><h3 class="h-s">Full menu</h3>'
		. ( $pdfs ? '<div class="ltef-actions">' . $pdfs . '</div>' : '' )
		. ( $pics ? '<ul class="enh-fullmenu__pics" role="list">' . $pics . '</ul>' : '' )
		. '<p class="meta">From the owner. Menus and prices change, so check when you order.</p></div>';
}

/* ------------------------------------------------------------------ On the place page */

function ltef_block_place_enhanced( $attrs, $content, $block ) {
	$id = ltef_ctx_post( $block );
	if ( ! $id || 'ltef_place' !== get_post_type( $id ) || ! ltef_enhanced_on( $id ) ) {
		return '';
	}
	$photos = ltef_owner_gallery( $id );
	$story  = (string) ltef_meta( $id, 'story' );
	$menu   = array_filter( preg_split( '/\r\n|\r|\n/', (string) ltef_meta( $id, 'highlights' ) ) );
	$links  = array_filter( preg_split( '/\r\n|\r|\n/', (string) ltef_meta( $id, 'links' ) ) );
	$full   = ltef_enhanced_menu_html( $id );
	if ( ! $photos && '' === $story && ! $menu && ! $links && '' === $full ) {
		return '';
	}
	$out = '';
	if ( $photos ) {
		$out .= '<ul class="enh-photos" aria-label="Photos from ' . esc_attr( get_the_title( $id ) ) . '">';
		foreach ( $photos as $a ) {
			$out .= '<li>' . wp_get_attachment_image( $a, 'medium_large', false, array( 'loading' => 'lazy', 'alt' => (string) get_post_meta( $a, '_wp_attachment_image_alt', true ) ) ) . '</li>';
		}
		$out .= '</ul>';
	}
	$cols = '';
	if ( '' !== $story ) {
		$cols .= '<div class="enh-story"><h3 class="h-s">Their story</h3>' . wpautop( esc_html( $story ) ) . '</div>';
	}
	if ( $menu ) {
		$dl = '';
		foreach ( $menu as $row ) {
			$p   = array_map( 'trim', explode( '|', $row, 2 ) );
			$dl .= '<div><dt>' . esc_html( $p[0] ) . '</dt>' . ( isset( $p[1] ) ? '<dd>' . esc_html( $p[1] ) . '</dd>' : '' ) . '</div>';
		}
		$cols .= '<div class="enh-menu"><h3 class="h-s">Menu highlights</h3><dl>' . $dl . '</dl><p class="meta">Prices from the owner; check when you order.</p></div>';
	}
	$out .= $cols ? '<div class="enh-cols">' . $cols . '</div>' : '';
	$out .= $full;
	if ( $links ) {
		$out .= '<div class="ltef-actions">';
		foreach ( $links as $row ) {
			$p = array_map( 'trim', explode( '|', $row, 2 ) );
			if ( isset( $p[1] ) ) {
				$out .= '<a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="' . esc_url( $p[1] ) . '" target="_blank" rel="noopener">' . esc_html( $p[0] ) . '<span class="vh"> (opens a new tab)</span></a>';
			}
		}
		$out .= '</div>';
	}
	return ltef_wrap(
		'<section class="panel enh" aria-labelledby="enh-h-' . (int) $id . '"><div class="enh-head"><h2 class="h-m" id="enh-h-' . (int) $id . '" style="font-size: 30px;">From ' . esc_html( get_the_title( $id ) ) . '</h2>' . ltef_label( 'enhanced', 'Enhanced listing' ) . '</div>'
		. '<p class="meta" style="margin: 0;">Added and paid for by the owner. It never changes Tina’s take. <a href="' . esc_url( home_url( '/editorial-policy/' ) ) . '#enhanced">How that works</a></p>' . $out . '</section>',
		'place-enhanced'
	);
}

/** On cards, an Enhanced place without Tina's line shows the start of the owner's story, and their first photo if it has no other. */
function ltef_enhanced_card_line( $place ) {
	if ( ! ltef_enhanced_on( $place ) ) {
		return '';
	}
	$story = (string) ltef_meta( $place, 'story' );
	return '' === $story ? '' : '<p class="ltef-card__owner"><span>From the owner:</span> ' . esc_html( wp_trim_words( $story, 16 ) ) . '</p>';
}
function ltef_enhanced_card_photo( $place ) {
	$g = ltef_enhanced_on( $place ) ? ltef_owner_gallery( $place ) : array();
	return $g ? wp_get_attachment_image( $g[0], 'medium_large', false, array( 'alt' => '', 'class' => 'ltef-card__photo', 'loading' => 'lazy' ) ) : '';
}

/* ------------------------------------------------------------------ On the owner's screen */

/** The Enhanced part of "Your listing" for one place: the upgrade offer, or the editor and the plan. */
function ltef_enhanced_owner_panel( $place ) {
	$sub   = ltef_enhanced_sub( $place );
	$price = ltef_money( ltef_setting( 'enhanced_price' ) );
	$post  = esc_url( admin_url( 'admin-post.php' ) );
	if ( ! ltef_enhanced_on( $place ) ) {
		$pending = ( isset( $_GET['ltef_upgrade'] ) && 'done' === sanitize_key( wp_unslash( $_GET['ltef_upgrade'] ) ) ) || ltef_enhanced_sub_live( $place ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$items   = '<ul class="ltef-enh-list"><li>Your photos, up to ' . LTEF_ENH_PHOTOS . '</li><li>Your story, in your words</li><li>Menu highlights and prices</li><li>Your full menu, as a PDF or photos</li><li>Booking, ordering and class links</li><li>The start of your story on your card in Explore</li></ul>';
		$head    = '<h2>Upgrade to an Enhanced listing</h2><p><strong>' . esc_html( $price ) . ' a month.</strong> Adds a section to your page, after Tina’s take and labeled as yours:</p>' . $items
			. '<p class="description">It never changes Tina’s take, the order of any list, or whether you’re in a guide. A basic listing stays free forever.</p>';
		if ( $pending ) {
			return '<div class="ltef-panel ltef-enh-offer">' . $head . '<p><strong>Thanks! Stripe is confirming your payment.</strong> Enhanced switches on within a minute: <a href="' . esc_url( ltef_owner_admin_url( array( 'ltef_upgrade' => 'done' ) ) ) . '">refresh this page</a>. You won’t be charged twice.</p></div>';
		}
		if ( ! ltef_enhanced_can_sell() ) {
			return '<div class="ltef-panel ltef-enh-offer">' . $head . '<p>To upgrade, email <a href="mailto:tina@livetoeatfoodie.com">tina@livetoeatfoodie.com</a>.</p></div>';
		}
		return '<form class="ltef-panel ltef-enh-offer" method="post" action="' . $post . '">' . $head
			. '<p class="description">Stripe bills your card each month until you cancel. Cancel here any time; Enhanced stays on until the end of the month you’ve paid for.</p>'
			. '<input type="hidden" name="action" value="ltef_enhanced_upgrade"><input type="hidden" name="place" value="' . (int) $place . '">' . wp_nonce_field( 'ltef_enh_' . $place, '_wpnonce', true, false )
			. '<p class="submit"><button class="button button-primary">Upgrade for ' . esc_html( $price ) . ' a month</button></p></form>';
	}

	// Enhanced: the plan line, then the editor.
	$plan = '';
	if ( ! empty( $sub['id'] ) && 'canceled' !== ( $sub['status'] ?? '' ) ) {
		$end  = ! empty( $sub['period_end'] ) ? wp_date( 'F j', (int) $sub['period_end'] ) : '';
		$amt  = ! empty( $sub['cents'] ) ? ltef_money( $sub['cents'] / 100 ) : $price;
		$late = 'past_due' === ( $sub['status'] ?? '' ) ? ' <strong>Stripe couldn’t take this month’s payment.</strong> Update your card below and Stripe tries again.' : '';
		if ( ! empty( $sub['cancel_at_period_end'] ) ) {
			$plan = '<p>Enhanced ends' . ( $end ? ' on <strong>' . esc_html( $end ) . '</strong>' : ' at the end of this month' ) . '. Changed your mind?</p><input type="hidden" name="do" value="resume"><p class="submit"><button class="button">Keep Enhanced</button></p>';
		} else {
			$plan = '<p>Enhanced listing, ' . esc_html( $amt ) . ' a month' . ( $end ? ', renews ' . esc_html( $end ) : '' ) . '.' . $late . '</p><input type="hidden" name="do" value="cancel"><p class="submit"><button class="button ltef-danger">Cancel Enhanced</button></p><p class="description">Cancelling keeps Enhanced until the end of the month you’ve paid for, then your page goes back to the free listing. Your photos and story are kept in case you come back.</p>';
		}
		$plan = '<form class="ltef-enh-plan" method="post" action="' . $post . '"><input type="hidden" name="action" value="ltef_enhanced_plan"><input type="hidden" name="place" value="' . (int) $place . '">' . wp_nonce_field( 'ltef_enh_' . $place, '_wpnonce', true, false ) . $plan . '</form>';
		if ( ltef_enhanced_card_ok( $place ) ) {
			$plan .= '<form class="ltef-enh-plan" method="post" action="' . $post . '"><input type="hidden" name="action" value="ltef_enhanced_card"><input type="hidden" name="place" value="' . (int) $place . '">' . wp_nonce_field( 'ltef_enh_' . $place, '_wpnonce', true, false )
				. '<p class="submit"><button class="button' . ( $late ? ' button-primary' : '' ) . '">Update card</button></p><p class="description">Opens Stripe’s secure page to change the card for this listing and see your past invoices.</p></form>';
		}
	} else {
		$until = (string) ltef_meta( $place, 'enhanced_until' );
		$plan  = $until
			? '<p><strong>Free Enhanced from Tina until ' . esc_html( wp_date( 'F j, Y', strtotime( $until . ' 12:00' ) ) ) . '.</strong> After that, your page goes back to the free listing, and you can upgrade here to keep your photos, story and menu showing. Nothing is charged unless you upgrade.</p>'
			: '<p class="description">Enhanced listing, arranged with Tina.</p>';
	}

	$rows = '';
	foreach ( ltef_owner_enhanced_fields() as $key => $def ) {
		list( $label, $type, $hint ) = $def;
		$id    = 'ltef-enh-' . $place . '-' . $key;
		$rows .= '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td><textarea class="large-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" rows="' . ( 'story' === $key ? 5 : 4 ) . '"' . ( 'textarea' === $type ? ' maxlength="' . (int) $def[3] . '"' : '' ) . ' aria-describedby="' . esc_attr( $id ) . '-h">' . esc_textarea( (string) ltef_meta( $place, $key ) ) . '</textarea><p class="description" id="' . esc_attr( $id ) . '-h">' . esc_html( $hint ) . '</p></td></tr>';
	}
	$photos = ltef_owner_gallery( $place );
	$grid   = '';
	foreach ( $photos as $a ) {
		$alt   = (string) get_post_meta( $a, '_wp_attachment_image_alt', true );
		$grid .= '<li>' . wp_get_attachment_image( $a, 'thumbnail', false, array( 'alt' => $alt ) ) . '<label><input type="checkbox" name="remove_photo[]" value="' . (int) $a . '"> Remove</label></li>';
	}
	$photo_row = '<tr><th scope="row">Photos</th><td>' . ( $grid ? '<ul class="ltef-enh-photos">' . $grid . '</ul>' : '' )
		. ( count( $photos ) < LTEF_ENH_PHOTOS
			? '<p><label for="ltef-enh-' . $place . '-photo">Add a photo</label><br><input type="file" id="ltef-enh-' . $place . '-photo" name="photo" accept="image/jpeg,image/png,image/webp"></p><p><label for="ltef-enh-' . $place . '-alt">Describe it</label><br><input type="text" class="regular-text" id="ltef-enh-' . $place . '-alt" name="photo_alt" aria-describedby="ltef-enh-' . $place . '-alt-h"></p><p class="description" id="ltef-enh-' . $place . '-alt-h">A few words for people who can’t see it: “Margherita pie fresh from the oven.” JPG, PNG or WebP, up to 10 MB. ' . count( $photos ) . ' of ' . LTEF_ENH_PHOTOS . ' used.</p>'
			: '<p class="description">That’s ' . LTEF_ENH_PHOTOS . ' photos, the most a page shows. Remove one to add another.</p>' )
		. '</td></tr>';
	$mfiles = ltef_owner_menu_files( $place );
	$mlist  = '';
	foreach ( $mfiles as $a ) {
		$mlist .= '<li><a href="' . esc_url( wp_get_attachment_url( $a ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $a ) ) . '</a> <span class="description">(' . ( wp_attachment_is_image( $a ) ? 'photo' : 'PDF' ) . ')</span> <label><input type="checkbox" name="remove_menu[]" value="' . (int) $a . '"> Remove</label></li>';
	}
	$mid      = 'ltef-enh-' . $place . '-menu';
	$menu_row = '<tr><th scope="row">Full menu</th><td>' . ( $mlist ? '<ul class="ltef-enh-menufiles">' . $mlist . '</ul>' : '' )
		. ( count( $mfiles ) < LTEF_ENH_MENU_FILES
			? '<p><label for="' . $mid . '">Add a menu</label><br><input type="file" id="' . $mid . '" name="menu_file" accept="application/pdf,image/jpeg,image/png,image/webp"></p><p><label for="' . $mid . '-name">Name it</label><br><input type="text" class="regular-text" id="' . $mid . '-name" name="menu_name" aria-describedby="' . $mid . '-h"></p><p class="description" id="' . $mid . '-h">Like “Dinner menu”, “Drinks” or “Brunch”. A PDF, or a photo of each page: JPG, PNG or WebP, up to 15 MB. Shown on your page as “Full menu”. ' . count( $mfiles ) . ' of ' . LTEF_ENH_MENU_FILES . ' used.</p>'
			: '<p class="description">That’s ' . LTEF_ENH_MENU_FILES . ' menu files, the most a page shows. Remove one to add another.</p>' )
		. '</td></tr>';
	return '<div class="ltef-enh"><h2>Enhanced listing ' . '<span class="ltef-chip ltef-chip--paid">Enhanced</span>' . '</h2>' . $plan
		. '<form method="post" action="' . $post . '" enctype="multipart/form-data"><table class="form-table" role="presentation">' . $photo_row . $menu_row . $rows . '</table>'
		. '<input type="hidden" name="action" value="ltef_enhanced_save"><input type="hidden" name="place" value="' . (int) $place . '">' . wp_nonce_field( 'ltef_enh_' . $place, '_wpnonce', true, false )
		. '<p class="submit"><button class="button button-primary">Save and publish</button></p></form></div>';
}

/** The place an Enhanced form is about, if the signed-in owner manages it. Dies otherwise. */
function ltef_enhanced_owner_place() {
	$place = isset( $_POST['place'] ) ? absint( $_POST['place'] ) : 0;
	check_admin_referer( 'ltef_enh_' . $place );
	$user = wp_get_current_user();
	if ( ! current_user_can( LTEF_OWNER_CAP ) || ! $place || 'ltef_place' !== get_post_type( $place ) || ! in_array( ltef_owner_norm( $user->user_email ), ltef_owner_emails( $place ), true ) ) {
		wp_safe_redirect( add_query_arg( 'ltef_owner_error', rawurlencode( 'That listing isn’t one this login manages.' ), ltef_owner_admin_url() ) );
		exit;
	}
	return array( $place, $user );
}

function ltef_enhanced_back( $args, $place ) {
	wp_safe_redirect( add_query_arg( $args, ltef_owner_admin_url() ) . '#ltef-own-' . (int) $place );
	exit;
}

/** Upgrade: off to Stripe's checkout for a monthly subscription, never a second one. */
add_action(
	'admin_post_ltef_enhanced_upgrade',
	function () {
		list( $place, $user ) = ltef_enhanced_owner_place();
		if ( ltef_enhanced_on( $place ) || ltef_enhanced_sub_live( $place ) ) {
			ltef_enhanced_back( array(), $place );
		}
		if ( ! ltef_enhanced_can_sell() ) {
			ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( 'Upgrades aren’t open right now. Email tina@livetoeatfoodie.com.' ) ), $place );
		}
		$sub = ltef_enhanced_sub( $place );
		// A checkout already started for this place: finish that one instead of opening another.
		if ( ! empty( $sub['session'] ) ) {
			$old = ltef_stripe_api( 'GET', 'checkout/sessions/' . rawurlencode( $sub['session'] ) );
			if ( ! is_wp_error( $old ) && 'open' === ( $old['status'] ?? '' ) && ! empty( $old['url'] ) ) {
				wp_redirect( $old['url'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Stripe's checkout page
				exit;
			}
			if ( ! is_wp_error( $old ) && 'complete' === ( $old['status'] ?? '' ) && ! empty( $old['subscription'] ) ) {
				ltef_enhanced_attach( $place, (string) $old['subscription'], $user->ID );
				ltef_enhanced_back( array(), $place );
			}
		}
		$cents = ltef_enhanced_price_cents();
		$label = 'Enhanced listing: ' . get_the_title( $place );
		$meta  = array( 'ltef_kind' => 'enhanced', 'ltef_place' => $place, 'ltef_user' => $user->ID );
		$args  = array(
			'mode'                 => 'subscription',
			'payment_method_types' => array( 'card', 'link' ),
			'client_reference_id'  => (string) $place,
			'line_items'           => array( array( 'quantity' => 1, 'price_data' => array( 'currency' => 'usd', 'unit_amount' => $cents, 'recurring' => array( 'interval' => 'month' ), 'product_data' => array( 'name' => mb_substr( $label, 0, 240 ) ) ) ) ),
			'metadata'             => $meta,
			'subscription_data'    => array( 'description' => mb_substr( $label, 0, 240 ), 'metadata' => $meta ),
			'custom_text'          => array( 'submit' => array( 'message' => 'Billed monthly until you cancel. Cancel any time from your listing on Live to Eat Food.' ) ),
			'success_url'          => ltef_owner_admin_url( array( 'ltef_upgrade' => 'done' ) ) . '&session_id={CHECKOUT_SESSION_ID}#ltef-own-' . $place,
			'cancel_url'           => ltef_owner_admin_url() . '#ltef-own-' . $place,
		);
		// Reuse the Stripe customer only when it's this owner's, in this mode.
		if ( ! empty( $sub['customer'] ) && ( $sub['mode'] ?? '' ) === ltef_stripe_mode() && (int) ( $sub['user'] ?? 0 ) === (int) $user->ID ) {
			$args['customer'] = $sub['customer'];
		} else {
			$args['customer_email'] = $user->user_email;
		}
		$session = ltef_stripe_api( 'POST', 'checkout/sessions', $args );
		if ( is_wp_error( $session ) || empty( $session['url'] ) ) {
			ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( 'Stripe couldn’t start the upgrade. Please try again in a minute.' ) ), $place );
		}
		ltef_enhanced_sub_set( $place, array( 'session' => $session['id'] ) );
		wp_redirect( $session['url'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Stripe's checkout page
		exit;
	}
);

/** Back from Stripe's checkout: confirm the upgrade right away instead of waiting for the webhook. */
add_action(
	'load-toplevel_page_ltef-my-listing',
	function () {
		$sid = isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! preg_match( '/^cs_[A-Za-z0-9_]+$/', $sid ) || ! function_exists( 'ltef_stripe_on' ) || ! ltef_stripe_on() ) {
			return;
		}
		$sess = ltef_stripe_api( 'GET', 'checkout/sessions/' . rawurlencode( $sid ) );
		if ( is_wp_error( $sess ) || 'complete' !== ( $sess['status'] ?? '' ) || 'enhanced' !== ( $sess['metadata']['ltef_kind'] ?? '' ) || empty( $sess['subscription'] ) ) {
			return;
		}
		$place = (int) ( $sess['metadata']['ltef_place'] ?? 0 );
		$user  = wp_get_current_user();
		if ( $place && in_array( ltef_owner_norm( $user->user_email ), ltef_owner_emails( $place ), true ) ) {
			ltef_enhanced_attach( $place, (string) $sess['subscription'], $user->ID );
		}
	}
);

/** Cancel at the end of the paid month, or keep it after all. */
add_action(
	'admin_post_ltef_enhanced_plan',
	function () {
		list( $place ) = ltef_enhanced_owner_place();
		$sub = ltef_enhanced_sub( $place );
		$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in ltef_enhanced_owner_place
		if ( empty( $sub['id'] ) || ! in_array( $do, array( 'cancel', 'resume' ), true ) ) {
			ltef_enhanced_back( array(), $place );
		}
		$r = ltef_stripe_api( 'POST', 'subscriptions/' . rawurlencode( $sub['id'] ), array( 'cancel_at_period_end' => 'cancel' === $do ) );
		if ( is_wp_error( $r ) ) {
			ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( 'Stripe said: ' . $r->get_error_message() ) ), $place );
		}
		ltef_enhanced_sync( $place, $r );
		$to = ltef_setting( 'notify_email' );
		if ( $to ) {
			wp_mail( $to, ( 'cancel' === $do ? 'Enhanced canceled: ' : 'Enhanced kept: ' ) . get_the_title( $place ), get_the_title( $place ) . ( 'cancel' === $do ? ' canceled their Enhanced listing. It stays on until ' . ( ! empty( ltef_enhanced_sub( $place )['period_end'] ) ? wp_date( 'F j', (int) ltef_enhanced_sub( $place )['period_end'] ) : 'the end of the paid month' ) . '.' : ' changed their mind and kept Enhanced.' ) . "\n\n" . get_permalink( $place ) );
		}
		ltef_enhanced_back( array( 'ltef_enh_msg' => 'cancel' === $do ? 'canceled' : 'kept' ), $place );
	}
);

/** The Stripe customer behind this place's subscription can be sent to the card page (same mode, same owner). */
function ltef_enhanced_card_ok( $place ) {
	$sub = ltef_enhanced_sub( $place );
	return ! empty( $sub['customer'] ) && ! empty( $sub['id'] ) && function_exists( 'ltef_stripe_on' ) && ltef_stripe_on()
		&& ( $sub['mode'] ?? ltef_stripe_mode() ) === ltef_stripe_mode()
		&& ( empty( $sub['user'] ) || (int) $sub['user'] === get_current_user_id() );
}

/**
 * Stripe's customer portal, set up for this site: change the card and see invoices, nothing else
 * (cancelling happens on Your listing so the site always knows). Made once per mode and remembered.
 */
function ltef_enhanced_portal_config() {
	$saved = (array) get_option( 'ltef_stripe_portal', array() );
	$mode  = ltef_stripe_mode();
	if ( ! empty( $saved[ $mode ] ) ) {
		return $saved[ $mode ];
	}
	$c = ltef_stripe_api(
		'POST',
		'billing_portal/configurations',
		array(
			'business_profile' => array(
				'headline'             => 'Live to Eat Food: the card for your Enhanced listing',
				'privacy_policy_url'   => home_url( '/privacy-policy/' ),
				'terms_of_service_url' => home_url( '/terms/' ),
			),
			'features'         => array(
				'payment_method_update' => array( 'enabled' => true ),
				'invoice_history'       => array( 'enabled' => true ),
				'customer_update'       => array( 'enabled' => false ),
				'subscription_cancel'   => array( 'enabled' => false ),
			),
			'default_return_url' => ltef_owner_admin_url(),
		)
	);
	if ( is_wp_error( $c ) || empty( $c['id'] ) ) {
		return is_wp_error( $c ) ? $c : new WP_Error( 'portal', 'No portal configuration.' );
	}
	$saved[ $mode ] = $c['id'];
	update_option( 'ltef_stripe_portal', $saved, false );
	return $c['id'];
}

/** Update card: off to Stripe's portal for this listing's customer, and back to Your listing after. */
add_action(
	'admin_post_ltef_enhanced_card',
	function () {
		list( $place ) = ltef_enhanced_owner_place();
		if ( ! ltef_enhanced_card_ok( $place ) ) {
			ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( 'There’s no card on file for this listing to change. Email tina@livetoeatfoodie.com.' ) ), $place );
		}
		$config = ltef_enhanced_portal_config();
		$args   = array( 'customer' => ltef_enhanced_sub( $place )['customer'], 'return_url' => ltef_owner_admin_url() . '#ltef-own-' . $place );
		if ( ! is_wp_error( $config ) ) {
			$args['configuration'] = $config;
		}
		$portal = ltef_stripe_api( 'POST', 'billing_portal/sessions', $args );
		if ( is_wp_error( $portal ) && ! is_wp_error( $config ) && false !== stripos( $portal->get_error_message(), 'configuration' ) ) {
			delete_option( 'ltef_stripe_portal' ); // a configuration removed in Stripe: make a new one next time
		}
		if ( is_wp_error( $portal ) || empty( $portal['url'] ) ) {
			ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( 'Stripe couldn’t open the card page. Please try again in a minute.' ) ), $place );
		}
		wp_redirect( $portal['url'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Stripe's own page
		exit;
	}
);

/** Saves the Enhanced fields and photos. */
add_action(
	'admin_post_ltef_enhanced_save',
	function () {
		list( $place, $user ) = ltef_enhanced_owner_place();
		if ( ! ltef_enhanced_on( $place ) ) {
			ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( 'Enhanced isn’t on for this listing.' ) ), $place );
		}
		$email = ltef_owner_norm( $user->user_email );
		$rk    = 'ltef_ose_' . md5( $email );
		$n     = (int) get_transient( $rk );
		if ( $n >= 30 ) {
			ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( 'Lots of saves in the last hour. Please try again a little later.' ) ), $place );
		}
		set_transient( $rk, $n + 1, HOUR_IN_SECONDS );
		$up = isset( $_FILES['photo']['error'] ) ? (int) $_FILES['photo']['error'] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $up && UPLOAD_ERR_NO_FILE !== $up ) {
			ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( in_array( $up, array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ? 'That photo is too big to upload. Try one under ' . size_format( wp_max_upload_size() ) . '.' : 'That photo didn’t upload. Please try again.' ) ), $place );
		}
		$mup = isset( $_FILES['menu_file']['error'] ) ? (int) $_FILES['menu_file']['error'] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $mup && UPLOAD_ERR_NO_FILE !== $mup ) {
			ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( in_array( $mup, array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ? 'That menu is too big to upload. Try one under ' . size_format( wp_max_upload_size() ) . '.' : 'That menu didn’t upload. Please try again.' ) ), $place );
		}
		$log   = get_post_meta( $place, 'ltef_owner_log', true );
		$log   = is_array( $log ) ? $log : array();
		$notes = array();
		foreach ( array_map( 'absint', (array) ( $_POST['remove_menu'] ?? array() ) ) as $att ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( in_array( $att, ltef_owner_menu_files( $place ), true ) ) {
				$name = get_the_title( $att );
				ltef_owner_menu_remove( $place, $att );
				$log[]   = array( 't' => time(), 'email' => $email, 'key' => 'menu_files', 'old' => $name, 'new' => 'Removed', 'undone' => false );
				$notes[] = 'Removed a menu: ' . $name;
			}
		}
		if ( UPLOAD_ERR_OK === $mup && ! empty( $_FILES['menu_file']['name'] ) ) {
			$att = ltef_owner_menu_add( $place, 'menu_file', wp_unslash( $_POST['menu_name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized there
			if ( is_wp_error( $att ) ) {
				update_post_meta( $place, 'ltef_owner_log', array_slice( $log, -60 ) );
				ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( $att->get_error_message() ) ), $place );
			}
			$log[]   = array( 't' => time(), 'email' => $email, 'key' => 'menu_files', 'old' => '', 'new' => 'Added: ' . get_the_title( $att ), 'att' => $att, 'undone' => false );
			$notes[] = 'Added a menu: ' . get_the_title( $att ) . ' (' . wp_get_attachment_url( $att ) . ')';
		}
		foreach ( array_map( 'absint', (array) ( $_POST['remove_photo'] ?? array() ) ) as $att ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( in_array( $att, ltef_owner_gallery( $place ), true ) ) {
				$alt = (string) get_post_meta( $att, '_wp_attachment_image_alt', true );
				ltef_owner_gallery_remove( $place, $att );
				$log[]   = array( 't' => time(), 'email' => $email, 'key' => 'gallery', 'old' => $alt, 'new' => 'Removed', 'undone' => false );
				$notes[] = 'Removed a photo: ' . $alt;
			}
		}
		if ( UPLOAD_ERR_OK === $up && ! empty( $_FILES['photo']['name'] ) ) {
			$att = ltef_owner_gallery_add( $place, 'photo', wp_unslash( $_POST['photo_alt'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized there
			if ( is_wp_error( $att ) ) {
				update_post_meta( $place, 'ltef_owner_log', array_slice( $log, -60 ) );
				ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( $att->get_error_message() ) ), $place );
			}
			$alt     = (string) get_post_meta( $att, '_wp_attachment_image_alt', true );
			$log[]   = array( 't' => time(), 'email' => $email, 'key' => 'gallery', 'old' => '', 'new' => 'Added: ' . $alt, 'att' => $att, 'undone' => false );
			$notes[] = 'Added a photo: ' . $alt . ' (' . wp_get_attachment_url( $att ) . ')';
		}
		if ( $notes ) {
			update_post_meta( $place, 'ltef_owner_log', array_slice( $log, -60 ) );
			$to = ltef_setting( 'notify_email' );
			if ( $to ) {
				wp_mail( $to, 'Owner photos or menu: ' . get_the_title( $place ), $email . ' changed the photos or menu on ' . get_the_title( $place ) . ". They're live now.\n\n" . implode( "\n", $notes ) . "\n\nSee the page: " . get_permalink( $place ) . "\nUndo an added photo under Owner changes: " . admin_url( 'post.php?post=' . $place . '&action=edit' ) . '#ltef-owners' );
			}
		}
		$done = ltef_owner_apply( $place, $email, wp_unslash( $_POST ), ltef_owner_enhanced_fields() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized field by field there
		if ( is_wp_error( $done ) ) {
			ltef_enhanced_back( array( 'ltef_owner_error' => rawurlencode( $done->get_error_message() ) ), $place );
		}
		ltef_enhanced_back( array( 'ltef_saved' => $place ), $place );
	}
);

/* ------------------------------------------------------------------ Stripe tells us what happened */

/** The place a Stripe object is about: its metadata first, then the subscription id on a place. */
function ltef_enhanced_place_for( $meta, $sub_id ) {
	$place = (int) ( $meta['ltef_place'] ?? 0 );
	if ( $place && 'ltef_place' === get_post_type( $place ) ) {
		return $place;
	}
	if ( $sub_id ) {
		$p = get_posts( array( 'post_type' => 'ltef_place', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'ltef_sub', 'value' => '"' . $sub_id . '"', 'compare' => 'LIKE' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		return $p ? (int) $p[0] : 0;
	}
	return 0;
}

/** Mirrors a Stripe subscription object onto the place, and turns Enhanced on or off with it. */
function ltef_enhanced_sync( $place, $s ) {
	$end    = (int) ( $s['current_period_end'] ?? ( $s['items']['data'][0]['current_period_end'] ?? 0 ) );
	$status = (string) ( $s['status'] ?? '' );
	$set    = array( 'status' => $status, 'cancel_at_period_end' => ! empty( $s['cancel_at_period_end'] ) ? 1 : 0 );
	$cus    = is_array( $s['customer'] ?? null ) ? ( $s['customer']['id'] ?? '' ) : (string) ( $s['customer'] ?? '' );
	foreach ( array( 'id' => (string) ( $s['id'] ?? '' ), 'customer' => $cus, 'period_end' => $end, 'cents' => (int) ( $s['items']['data'][0]['price']['unit_amount'] ?? 0 ) ) as $k => $v ) {
		if ( $v ) {
			$set[ $k ] = $v;
		}
	}
	ltef_enhanced_sub_set( $place, $set );
	// Past due keeps Enhanced on while Stripe retries the card, for a week past the paid month at most.
	$grace = 'past_due' === $status && $end && $end < time() - 7 * DAY_IN_SECONDS;
	if ( in_array( $status, array( 'active', 'trialing', 'past_due' ), true ) && ! $grace ) {
		update_post_meta( $place, 'ltef_enhanced', true );
	} elseif ( $grace || in_array( $status, array( 'canceled', 'unpaid', 'incomplete_expired', 'paused' ), true ) ) {
		update_post_meta( $place, 'ltef_enhanced', false );
	}
	ltef_place_refresh( $place );
}

/** A subscription that is (or may yet be) charging, in the current Stripe mode. */
function ltef_enhanced_sub_live( $place ) {
	$s = ltef_enhanced_sub( $place );
	return ! empty( $s['id'] ) && in_array( $s['status'] ?? '', array( 'active', 'trialing', 'past_due', 'incomplete' ), true )
		&& ( empty( $s['mode'] ) || ! function_exists( 'ltef_stripe_mode' ) || $s['mode'] === ltef_stripe_mode() );
}

/** Upgrades are offered only when Stripe is on and its webhook sends the subscription events. */
function ltef_enhanced_can_sell() {
	if ( ! function_exists( 'ltef_stripe_on' ) || ! ltef_stripe_on() ) {
		return false;
	}
	$h = ltef_stripe()['hooks'][ ltef_stripe_mode() ] ?? array();
	return (int) ( $h['v'] ?? 1 ) >= LTEF_STRIPE_EVENTS_V;
}

/**
 * Ties a paid subscription to a place (from the webhook or the owner's return from checkout). A second live
 * subscription for the same place is canceled at once so nobody pays twice, and Tina is told to refund it.
 * Returns true the first time this subscription is attached.
 */
function ltef_enhanced_attach( $place, $sub_id, $user = 0 ) {
	$cur = ltef_enhanced_sub( $place );
	if ( ! empty( $cur['id'] ) && $cur['id'] !== $sub_id && ltef_enhanced_sub_live( $place ) ) {
		ltef_stripe_api( 'DELETE', 'subscriptions/' . rawurlencode( $sub_id ) );
		$to = ltef_setting( 'notify_email' );
		if ( $to ) {
			wp_mail( $to, 'Refund needed: second Enhanced payment for ' . get_the_title( $place ), get_the_title( $place ) . ' already had an Enhanced subscription, and a second one was started. The site canceled the second one straight away (' . $sub_id . '), but its first payment went through: refund it in Stripe under Payments.' );
		}
		return false;
	}
	$first = empty( $cur['id'] ) || $cur['id'] !== $sub_id;
	$base  = array( 'id' => $sub_id, 'session' => '', 'mode' => function_exists( 'ltef_stripe_mode' ) ? ltef_stripe_mode() : '' );
	if ( $first ) {
		$base += array( 'user' => (int) $user, 'started' => time(), 'cancel_at_period_end' => 0, 'period_end' => 0 );
	}
	ltef_enhanced_sub_set( $place, $base );
	$s = ltef_stripe_api( 'GET', 'subscriptions/' . rawurlencode( $sub_id ) );
	if ( is_wp_error( $s ) ) {
		ltef_enhanced_sub_set( $place, array( 'status' => 'active' ) ); // checkout completed; the next event corrects anything
		delete_post_meta( $place, 'ltef_enhanced_until' ); // a paid plan replaces any free end date
		update_post_meta( $place, 'ltef_enhanced', true );
		ltef_place_refresh( $place );
	} else {
		ltef_enhanced_sync( $place, $s );
	}
	if ( $first ) {
		$to = ltef_setting( 'notify_email' );
		if ( $to ) {
			wp_mail( $to, 'New Enhanced listing: ' . get_the_title( $place ), get_the_title( $place ) . ' upgraded to an Enhanced listing. Their photos, story, menu highlights and links show on the page as they add them.' . "\n\n" . get_permalink( $place ) );
		}
	}
	return $first;
}

/** Tell Tina about a payment the site couldn't tie to a place, so it isn't lost. */
function ltef_enhanced_unmatched( $what ) {
	$to = ltef_setting( 'notify_email' );
	if ( $to ) {
		wp_mail( $to, 'Enhanced payment needs a look', 'Stripe sent an Enhanced listing ' . $what . ' that doesn’t match a place on the site (maybe it was deleted). Check it in Stripe; if it should stop, cancel the subscription there.' );
	}
}

add_action(
	'ltef_stripe_event',
	function ( $e ) {
		$type = $e['type'] ?? '';
		$o    = $e['data']['object'] ?? array();

		if ( 'checkout.session.completed' === $type && 'enhanced' === ( $o['metadata']['ltef_kind'] ?? '' ) ) {
			$place = ltef_enhanced_place_for( $o['metadata'], '' );
			if ( ! $place ) {
				ltef_enhanced_unmatched( 'checkout (' . ( $o['subscription'] ?? '' ) . ')' );
				return;
			}
			if ( ! empty( $o['subscription'] ) ) {
				ltef_enhanced_attach( $place, (string) $o['subscription'], (int) ( $o['metadata']['ltef_user'] ?? 0 ) );
			}
			return;
		}

		if ( 'invoice.paid' === $type ) {
			$sub_id = (string) ( $o['subscription'] ?? ( $o['parent']['subscription_details']['subscription'] ?? '' ) );
			$meta   = (array) ( $o['parent']['subscription_details']['metadata'] ?? ( $o['subscription_details']['metadata'] ?? ( $o['lines']['data'][0]['metadata'] ?? array() ) ) );
			$ours   = 'enhanced' === ( $meta['ltef_kind'] ?? '' );
			$place  = $ours ? ltef_enhanced_place_for( $meta, $sub_id ) : ( $sub_id ? ltef_enhanced_place_for( array(), $sub_id ) : 0 );
			if ( ! $place ) {
				if ( $ours ) {
					ltef_enhanced_unmatched( 'payment (invoice ' . ( $o['id'] ?? '' ) . ')' );
				}
				return;
			}
			$cents = (int) ( $o['amount_paid'] ?? 0 );
			if ( $cents > 0 && function_exists( 'ltef_stripe_log_payment' ) ) { // real money either way; once per invoice
				ltef_stripe_log_payment( $cents, 'enhanced', get_the_title( $place ), 'Enhanced listing: ' . get_the_title( $place ) . ' (' . wp_date( 'M Y' ) . ')', ltef_enhanced_invoice_charge( $o ), 'invoice:' . ( $o['id'] ?? '' ) );
			}
			$cur = ltef_enhanced_sub( $place );
			if ( $sub_id && ( empty( $cur['id'] ) || $cur['id'] === $sub_id || ! ltef_enhanced_sub_live( $place ) ) ) {
				$s = ltef_stripe_api( 'GET', 'subscriptions/' . rawurlencode( $sub_id ) ); // Stripe's state now, not the event's
				if ( ! is_wp_error( $s ) ) {
					if ( empty( $cur['id'] ) || $cur['id'] !== $sub_id ) {
						ltef_enhanced_sub_set( $place, array( 'id' => $sub_id, 'mode' => ltef_stripe_mode(), 'user' => (int) ( $meta['ltef_user'] ?? 0 ), 'started' => time(), 'session' => '' ) );
					}
					ltef_enhanced_sync( $place, $s );
				}
			}
			return;
		}

		if ( in_array( $type, array( 'customer.subscription.updated', 'customer.subscription.deleted' ), true ) ) {
			$sub_id = (string) ( $o['id'] ?? '' );
			$place  = ltef_enhanced_place_for( (array) ( $o['metadata'] ?? array() ), $sub_id );
			if ( ! $place ) {
				return;
			}
			$cur = ltef_enhanced_sub( $place );
			if ( ! empty( $cur['id'] ) && $cur['id'] !== $sub_id ) {
				return; // about another subscription (say, a duplicate the site canceled): this place follows its own
			}
			$was = ltef_enhanced_on( $place );
			if ( 'customer.subscription.deleted' === $type ) {
				ltef_enhanced_sync( $place, array_merge( $o, array( 'status' => 'canceled' ) ) );
			} else {
				$s = ltef_stripe_api( 'GET', 'subscriptions/' . rawurlencode( $sub_id ) );
				ltef_enhanced_sync( $place, is_wp_error( $s ) ? $o : $s );
			}
			$to = ltef_setting( 'notify_email' );
			if ( $was && ! ltef_enhanced_on( $place ) && $to ) {
				wp_mail( $to, 'Enhanced ended: ' . get_the_title( $place ), get_the_title( $place ) . '’s Enhanced listing ended, so their photos, story, menu highlights and links are off the page (kept in case they come back).' . "\n\n" . get_permalink( $place ) );
			}
		}
	}
);

/** Once a day, check any subscription whose paid month has passed, in case Stripe's news never arrived. */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ltef_enhanced_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ltef_enhanced_daily' );
		}
	}
);
add_action(
	'ltef_enhanced_daily',
	function () {
		if ( ! function_exists( 'ltef_stripe_on' ) || ! ltef_stripe_on() ) {
			return;
		}
		$ids = get_posts( array( 'post_type' => 'ltef_place', 'post_status' => 'any', 'numberposts' => 200, 'fields' => 'ids', 'meta_key' => 'ltef_sub' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		foreach ( $ids as $place ) {
			$s = ltef_enhanced_sub( $place );
			if ( empty( $s['id'] ) || ( ! ltef_enhanced_on( $place ) && ! ltef_enhanced_sub_live( $place ) ) || ( ! empty( $s['period_end'] ) && (int) $s['period_end'] > time() - DAY_IN_SECONDS ) ) {
				continue;
			}
			$fresh = ltef_stripe_api( 'GET', 'subscriptions/' . rawurlencode( $s['id'] ) );
			if ( ! is_wp_error( $fresh ) ) {
				ltef_enhanced_sync( $place, $fresh );
			}
		}
	}
);

/** A place with a live subscription going to the trash: its owner loses the Cancel button, so tell Tina. */
add_action(
	'wp_trash_post',
	function ( $post_id ) {
		if ( 'ltef_place' === get_post_type( $post_id ) && ltef_enhanced_sub_live( $post_id ) ) {
			$to = ltef_setting( 'notify_email' );
			if ( $to ) {
				wp_mail( $to, 'Still billing: ' . get_the_title( $post_id ), get_the_title( $post_id ) . ' went to the trash but its Enhanced subscription (' . ltef_enhanced_sub( $post_id )['id'] . ') is still charging $' . ltef_setting( 'enhanced_price' ) . ' a month. Cancel it in Stripe, or restore the place.' );
			}
		}
	}
);

/** The charge behind an invoice, for Stripe's fee: older API versions put it on the invoice, newer ones on its payments. */
function ltef_enhanced_invoice_charge( $invoice ) {
	if ( ! empty( $invoice['charge'] ) && is_string( $invoice['charge'] ) ) {
		return $invoice['charge'];
	}
	$pi = is_string( $invoice['payment_intent'] ?? null ) ? $invoice['payment_intent'] : '';
	if ( ! $pi && ! empty( $invoice['id'] ) ) {
		$pays = ltef_stripe_api( 'GET', 'invoice_payments', array( 'invoice' => $invoice['id'], 'status' => 'paid', 'limit' => 1 ) );
		$pi   = is_wp_error( $pays ) ? '' : (string) ( $pays['data'][0]['payment']['payment_intent'] ?? '' );
	}
	if ( ! $pi ) {
		return '';
	}
	$intent = ltef_stripe_api( 'GET', 'payment_intents/' . rawurlencode( $pi ) );
	return is_wp_error( $intent ) ? '' : (string) ( $intent['latest_charge'] ?? '' );
}

/* ------------------------------------------------------------------ Free Enhanced with an end date */

/**
 * Enhanced Tina gives for free has a "Free Enhanced until" date. A week before it, the owners get a reminder;
 * the day after it, Enhanced switches off (their photos, story and menu are kept) unless they've upgraded to a
 * paid plan in the meantime.
 */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ltef_enhanced_free_daily' ) ) {
			$midnight = ( new DateTime( 'tomorrow', wp_timezone() ) )->getTimestamp();
			wp_schedule_event( $midnight + 7 * MINUTE_IN_SECONDS, 'daily', 'ltef_enhanced_free_daily' );
		}
	}
);
add_action( 'ltef_enhanced_free_daily', 'ltef_enhanced_free_check' );
function ltef_enhanced_free_check() {
	$today = ltef_today();
	$week  = wp_date( 'Y-m-d', strtotime( '+7 days' ) );
	$ids   = get_posts( array( 'post_type' => 'ltef_place', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'ltef_enhanced_until', 'value' => '', 'compare' => '!=' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	$tina  = ltef_setting( 'notify_email' );
	foreach ( $ids as $place ) {
		$until = (string) ltef_meta( $place, 'enhanced_until' );
		if ( ! ltef_enhanced_on( $place ) || ltef_enhanced_sub_live( $place ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $until ) ) {
			continue;
		}
		$name  = html_entity_decode( get_the_title( $place ), ENT_QUOTES, 'UTF-8' );
		$day   = wp_date( 'F j', strtotime( $until . ' 12:00' ) );
		$price = ltef_money( ltef_setting( 'enhanced_price' ) );
		if ( $until === $week ) {
			foreach ( ltef_owner_emails( $place ) as $to ) {
				wp_mail( $to, 'Your free Enhanced listing ends ' . $day, "Hi,\n\nThe free Enhanced listing Tina gave " . $name . ' runs through ' . $day . '. After that your page goes back to the free listing: your photos, story and menu are kept, just not shown.' . "\n\nTo keep them showing, sign in after " . $day . ' and choose "Upgrade" on Your listings (' . $price . " a month, cancel any time):\n" . ltef_owner_admin_url() . "\n\nThanks for being on Live to Eat Food!\n" );
			}
		} elseif ( $until < $today ) {
			update_post_meta( $place, 'ltef_enhanced', false );
			delete_post_meta( $place, 'ltef_enhanced_until' );
			ltef_place_refresh( $place );
			foreach ( ltef_owner_emails( $place ) as $to ) {
				wp_mail( $to, 'Your free Enhanced listing ended', "Hi,\n\nThe free Enhanced listing for " . $name . ' ended on ' . $day . '. Your page is back to the free listing, and your photos, story and menu are kept.' . "\n\nTo show them again, sign in and choose \"Upgrade\" on Your listings (" . $price . " a month, cancel any time):\n" . ltef_owner_admin_url() . "\n" );
			}
			if ( $tina ) {
				wp_mail( $tina, 'Free Enhanced ended: ' . $name, $name . '’s free Enhanced listing ended on ' . $day . ', so it’s back to the free listing. Their photos, story and menu are kept in case they upgrade.' . "\n\n" . get_permalink( $place ) );
			}
		}
	}
}
