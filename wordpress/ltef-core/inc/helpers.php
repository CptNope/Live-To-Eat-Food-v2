<?php
/**
 * Small shared helpers: settings, icons, labels, dates.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin settings with defaults (prices decided with Tina, Nov 2, 2026).
 */
function ltef_settings() {
	$defaults = array(
		'notify_email'    => get_option( 'admin_email' ),
		'woopon_weekly'   => 10,
		'woopon_free'     => 2,
		'woopon_vip'      => 100,
		'vip_limit'       => 10,
		'event_premium'   => 50,
		'job_post'        => 100,
		'dont_work_with'  => '',
		'reply_days'      => 2,
	);
	$saved = get_option( 'ltef_settings', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

function ltef_setting( $key ) {
	$s = ltef_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

/**
 * Inline SVG icons used by labels and buttons. All decorative (aria-hidden).
 */
function ltef_icon( $name ) {
	$p = array(
		'pen'     => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20l4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10z"/><path d="M14 7l3 3"/></g>',
		'mail'    => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linejoin="round" stroke-linecap="round"><rect x="3.5" y="6" width="17" height="12.5" rx="2"/><path d="M4.5 7.5l7.5 6 7.5-6"/></g>',
		'dollar'  => '<path d="M12 3.5v17M16 7.8c-.6-1.4-2.2-2.4-4-2.4-2.2 0-4 1.2-4 3s1.8 2.5 4 2.9 4 1.4 4 3.2-1.8 2.8-4 2.8c-1.9 0-3.5-1-4.1-2.5" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/>',
		'plus'    => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="4"/><path d="M12 8.5v7M8.5 12h7"/></g>',
		'pin'     => '<g fill="none" stroke="currentColor" stroke-width="2.1"><path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.3"/></g>',
		'ticket'  => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linejoin="round"><path d="M3.5 7h17v3a2 2 0 0 0 0 4v3h-17v-3a2 2 0 0 0 0-4z"/><path d="M14.5 7v10" stroke-dasharray="2 2.4"/></g>',
		'play'    => '<path d="M8 5.5v13l11-6.5z" fill="currentColor"/>',
		'mega'    => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linejoin="round" stroke-linecap="round"><path d="M4 10v4h3l7 4.5v-13L7 10z"/><path d="M17.5 9a4 4 0 0 1 0 6"/></g>',
		'star'    => '<path d="M12 3.6l2.5 5.3 5.8.7-4.3 4 1.1 5.8L12 16.6l-5.1 2.8 1.1-5.8-4.3-4 5.8-.7z" fill="currentColor" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
		'target'  => '<g fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="8.6"/><circle cx="12" cy="12" r="4.8"/></g><circle cx="12" cy="12" r="1.9" fill="currentColor"/>',
		'spin'    => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 3.5v8.5l6 6M3.5 12H12"/></g>',
		'cal'     => '<g fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"><rect x="4" y="5.5" width="16" height="14.5" rx="2.5"/><path d="M4 10.5h16M8.5 3.5v4M15.5 3.5v4"/></g>',
		'arrow'   => '<path d="M5 12h13M13 6.5l5.5 5.5-5.5 5.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',
	);
	$class = in_array( $name, array( 'star', 'target' ), true ) ? ' class="ltef-verdict__i"' : '';
	return isset( $p[ $name ] ) ? '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"' . $class . '>' . $p[ $name ] . '</svg>' : '';
}

/**
 * A trust or paid label. Paid kinds wear the oat hatch (ltef-lbl--sponsored).
 */
function ltef_label( $kind, $text, $small = true ) {
	$icons = array( 'tina' => 'pen', 'hosted' => 'mail', 'sponsored' => 'dollar', 'partner' => 'dollar', 'enhanced' => 'plus', 'vip' => 'star', 'open' => 'mega' );
	$class = 'vip' === $kind ? 'sponsored' : $kind;
	$icon  = isset( $icons[ $kind ] ) ? ltef_icon( $icons[ $kind ] ) : '';
	return sprintf( '<span class="ltef-lbl ltef-lbl--%s%s">%s%s</span>', esc_attr( $class ), $small ? ' ltef-lbl--sm' : '', $icon, esc_html( $text ) );
}

/** "2026-08" -> "Aug 2026". */
function ltef_month_label( $ym ) {
	if ( ! $ym || ! preg_match( '/^(\d{4})-(\d{2})/', $ym, $m ) ) {
		return '';
	}
	return date_i18n( 'M Y', mktime( 0, 0, 0, (int) $m[2], 1, (int) $m[1] ) );
}

/** Google Maps search link for an address. */
function ltef_maps_url( $name, $address, $town = 'Worcester, MA' ) {
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( trim( $name . ' ' . $address . ' ' . $town ) );
}

/** The numeric id from a TikTok video URL. */
function ltef_tiktok_id( $url ) {
	return preg_match( '#/video/(\d+)#', (string) $url, $m ) ? $m[1] : '';
}

/** Price shown as money, no cents when whole. */
function ltef_money( $n ) {
	$n = (float) $n;
	return '$' . ( floor( $n ) == $n ? number_format_i18n( $n ) : number_format_i18n( $n, 2 ) );
}

/** Today in the site's timezone, Y-m-d. */
function ltef_today() {
	return wp_date( 'Y-m-d' );
}

/** A post meta value, trimmed. */
function ltef_meta( $post_id, $key ) {
	$v = get_post_meta( $post_id, 'ltef_' . $key, true );
	return is_string( $v ) ? trim( $v ) : $v;
}

/**
 * Names on the private don't-work-with list (Settings). Never printed on the front end.
 */
function ltef_blocklist() {
	$lines = preg_split( '/\r\n|\r|\n/', (string) ltef_setting( 'dont_work_with' ) );
	return array_values( array_filter( array_map( 'trim', $lines ) ) );
}

/** Whether a business name matches the private list (loose, case-insensitive). */
function ltef_on_blocklist( $name ) {
	$name = strtolower( remove_accents( (string) $name ) );
	if ( '' === trim( $name ) ) {
		return false;
	}
	foreach ( ltef_blocklist() as $entry ) {
		$e = strtolower( remove_accents( preg_replace( '/\s*\(.*\)\s*/', '', $entry ) ) );
		$e = trim( preg_replace( "/['’]s\b/", '', $e ) );
		if ( $e && false !== strpos( preg_replace( "/['’]s\b/", '', $name ), $e ) ) {
			return true;
		}
	}
	return false;
}
