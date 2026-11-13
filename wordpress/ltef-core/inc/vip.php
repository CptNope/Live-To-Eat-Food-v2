<?php
/**
 * VIP Woo-pons: Tina presents them in her monthly video, so each month takes only so many
 * (Money → Prices, "VIP Woo-pons per month", 10 by default).
 *
 * A VIP Woo-pon belongs to the month its start date falls in. Waiting and approved ones both hold a spot, so
 * the count people see is what's really left; a declined one goes to the trash and frees its spot.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** The most VIP Woo-pons in one month. */
function ltef_vip_limit() {
	return max( 0, (int) ltef_setting( 'vip_limit' ) );
}

/** The month a VIP Woo-pon is in, Y-m: its start date's month. */
function ltef_vip_month( $post_id ) {
	$start = (string) ltef_meta( $post_id, 'start' );
	return preg_match( '/^\d{4}-\d{2}/', $start ) ? substr( $start, 0, 7 ) : get_the_date( 'Y-m', $post_id );
}

/** How many VIP Woo-pons hold a spot in a month (Y-m). */
function ltef_vip_taken( $ym, $statuses = array( 'publish', 'pending', 'future' ), $exclude = 0 ) {
	$ids = get_posts(
		array(
			'post_type'    => 'ltef_woopon',
			'post_status'  => $statuses,
			'numberposts'  => -1,
			'fields'       => 'ids',
			'post__not_in' => $exclude ? array( (int) $exclude ) : array(),
			'meta_query'   => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => 'ltef_tier', 'value' => 'vip' ),
				array( 'key' => 'ltef_start', 'value' => array( $ym . '-01', $ym . '-31' ), 'compare' => 'BETWEEN' ),
			),
		)
	);
	return count( $ids );
}

/** Spots left in a month (Y-m). */
function ltef_vip_left( $ym ) {
	return max( 0, ltef_vip_limit() - ltef_vip_taken( $ym ) );
}

/** "November" for "2026-11". */
function ltef_vip_month_name( $ym ) {
	return wp_date( 'F', strtotime( $ym . '-15' ) );
}

/** This month and next, Y-m. */
function ltef_vip_months() {
	$now = new DateTime( 'first day of this month', wp_timezone() );
	$nxt = ( clone $now )->modify( '+1 month' );
	return array( $now->format( 'Y-m' ), $nxt->format( 'Y-m' ) );
}

/** "7 of 10 spots left for November, 10 for December." */
function ltef_vip_left_text() {
	$limit = ltef_vip_limit();
	if ( ! $limit ) {
		return '';
	}
	list( $a, $b ) = ltef_vip_months();
	$la = ltef_vip_left( $a );
	$lb = ltef_vip_left( $b );
	$first = $la
		? $la . ' of ' . $limit . ' ' . ( 1 === $la ? 'spot' : 'spots' ) . ' left for ' . ltef_vip_month_name( $a )
		: ltef_vip_month_name( $a ) . ' is full';
	$second = $lb ? $lb . ' for ' . ltef_vip_month_name( $b ) : ltef_vip_month_name( $b ) . ' is full too';
	return $first . ', ' . $second . '.';
}

/** [ltef_vip_left] prints what's left this month and next. */
add_shortcode(
	'ltef_vip_left',
	function () {
		$t = ltef_vip_left_text();
		return $t ? '<span class="ltef-vip-left">' . esc_html( $t ) . '</span>' : '';
	}
);
add_filter(
	'render_block',
	function ( $html ) {
		return ( is_string( $html ) && false !== strpos( $html, '[ltef_vip_left' ) ) ? do_shortcode( $html ) : $html;
	}
);

/** For the form: an error when the start date's month has no VIP spot left, or ''. */
function ltef_vip_full_error( $start ) {
	if ( ! preg_match( '/^\d{4}-\d{2}/', (string) $start ) ) {
		return '';
	}
	$ym = substr( $start, 0, 7 );
	if ( ltef_vip_left( $ym ) > 0 ) {
		return '';
	}
	$next = ( new DateTime( $ym . '-01', wp_timezone() ) )->modify( '+1 month' )->format( 'Y-m' );
	return 'VIP Woo-pons for ' . ltef_vip_month_name( $ym ) . ' are full (' . ltef_vip_limit() . ' a month, so Tina’s video doesn’t run long). '
		. ( ltef_vip_left( $next ) ? 'Start it in ' . ltef_vip_month_name( $next ) . ', or pick Weekly.' : 'Pick Weekly, or try again next month.' );
}

/** For approving: refuse an extra VIP beyond the month's limit. [ ok, note ] */
function ltef_vip_can_approve( $post_id ) {
	if ( 'vip' !== ltef_meta( $post_id, 'tier' ) ) {
		return array( true, '' );
	}
	$ym = ltef_vip_month( $post_id );
	if ( ltef_vip_taken( $ym, array( 'publish', 'future' ), $post_id ) >= ltef_vip_limit() ) {
		return array( false, ltef_vip_month_name( $ym ) . ' already has ' . ltef_vip_limit() . ' VIP Woo-pons. Move this one’s start date to another month under Edit first, or raise the limit under Money → Prices.' );
	}
	return array( true, '' );
}

/** Review-queue chip: where this VIP stands in its month. */
function ltef_vip_flag( $post_id ) {
	$ym    = ltef_vip_month( $post_id );
	$taken = ltef_vip_taken( $ym );
	return array( $taken > ltef_vip_limit() ? 'warn' : '', esc_html( ltef_vip_month_name( $ym ) . ' video: ' . $taken . ' of ' . ltef_vip_limit() . ' VIP spots taken, counting this one' ) );
}

/** A VIP Woo-pon sent, approved, declined or moved changes what's left: clear the cached pages that show it. */
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( 'ltef_woopon' === $post->post_type && $new !== $old && function_exists( 'ltef_money_clear_page_cache' ) ) {
			ltef_money_clear_page_cache();
		}
	},
	20,
	3
);

/**
 * The VIP spots callout for the Woo-pons page: how many are left this month, with a way to take one.
 * When this month is full, it points to next month instead.
 */
function ltef_block_vip_spots() {
	$limit = ltef_vip_limit();
	if ( ! $limit ) {
		return '';
	}
	list( $a, $b ) = ltef_vip_months();
	$left = ltef_vip_left( $a );
	$ym   = $left ? $a : $b;
	$n    = $left ? $left : ltef_vip_left( $b );
	if ( ! $n ) {
		return ltef_wrap( '<aside class="vip-spots vip-spots--full" aria-label="VIP Woo-pon spots">' . ltef_label( 'vip', 'VIP Woo-pon' ) . '<p class="vip-spots__line">VIP spots for ' . esc_html( ltef_vip_month_name( $a ) ) . ' and ' . esc_html( ltef_vip_month_name( $b ) ) . ' are full.</p><p class="vip-spots__sub">A regular Woo-pon still runs any week.</p></aside>', 'vip-spots-block' );
	}
	$name = ltef_vip_month_name( $ym );
	$line = $left
		? '<strong>' . $n . ' of ' . $limit . '</strong> VIP ' . ( 1 === $n ? 'spot' : 'spots' ) . ' left for ' . esc_html( $name )
		: esc_html( ltef_vip_month_name( $a ) ) . ' is full. <strong>' . $n . ' of ' . $limit . '</strong> VIP ' . ( 1 === $n ? 'spot' : 'spots' ) . ' open for ' . esc_html( $name );
	return ltef_wrap(
		'<aside class="vip-spots" aria-label="VIP Woo-pon spots">'
		. ltef_label( 'vip', 'VIP Woo-pon' )
		. '<p class="vip-spots__line">' . $line . '</p>'
		. '<p class="vip-spots__sub">Tina presents each VIP Woo-pon in her ' . esc_html( $name ) . ' video, with your flyer. Only ' . $limit . ' a month, so the video stays short. ' . esc_html( ltef_money( ltef_setting( 'woopon_vip' ) ) ) . '.</p>'
		. '<a class="ltef-btn ltef-btn--sm" href="#submit">Get a VIP spot</a>'
		. '</aside>',
		'vip-spots-block'
	);
}
