<?php
/**
 * Weekly specials sent in from the "Send a special" form.
 *
 * A special arrives as a pending Daily deal, already matched to the place and the days it runs, so approving
 * it in the review queue puts it on the Daily deals page and the place's page. Tina can fix the place, days or
 * wording under "Edit first".
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/**
 * The weekdays a "When" answer names, as weekday keys in order: "every tuesday" → [ tue ],
 * "Mon-Fri" → [ mon … fri ], "weekends" → [ sat, sun ], "daily" → all seven. Empty when it names none.
 */
function ltef_parse_days( $text ) {
	$t    = strtolower( (string) $text );
	$keys = array_keys( ltef_weekdays() );
	if ( preg_match( '/\b(every ?day|daily|7 days|seven days|all week)\b/', $t ) ) {
		return $keys;
	}
	$day  = '(mon|tue|wed|thu|fri|sat|sun)[a-z]*\.?';
	$days = array();
	// Ranges first: "mon-fri", "monday through friday", "tues to thurs".
	if ( preg_match_all( '/\b' . $day . '\s*(?:-|–|—|to|through|thru|till|until)\s*' . $day . '/', $t, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $r ) {
			$a = array_search( $r[1], $keys, true );
			$b = array_search( $r[2], $keys, true );
			for ( $i = $a; ; $i = ( $i + 1 ) % 7 ) {
				$days[] = $keys[ $i ];
				if ( $i === $b ) {
					break;
				}
			}
		}
		$t = preg_replace( '/\b' . $day . '\s*(?:-|–|—|to|through|thru|till|until)\s*' . $day . '/', ' ', $t );
	}
	if ( preg_match( '/\bweekdays?\b/', $t ) ) {
		$days = array_merge( $days, array( 'mon', 'tue', 'wed', 'thu', 'fri' ) );
	}
	if ( preg_match( '/\bweekends?\b/', $t ) ) {
		$days = array_merge( $days, array( 'sat', 'sun' ) );
	}
	if ( preg_match_all( '/\b' . $day . '/', $t, $m ) ) {
		$days = array_merge( $days, $m[1] );
	}
	return array_values( array_intersect( $keys, array_unique( $days ) ) );
}

/** Whatever a "When" answer says beyond the days ("through Dec 15", "4–6pm"), to show as the deal's details. */
function ltef_special_when_extra( $text ) {
	$words = '(?:(?:mon|tue|wed|thu|fri|sat|sun)[a-z]*|every ?day|daily|all|week|every|each|on|weekdays?|weekends?|and|to|through|thru|till|until)';
	$rest  = preg_replace( '/\b' . $words . '\b/i', ' ', (string) $text );
	return preg_match( '/[a-z0-9]/i', $rest ) ? trim( (string) $text ) : '';
}

/** "bogo medium pizzas" → "Bogo medium pizzas": first letter up, the rest as they wrote it. */
function ltef_special_tidy( $text ) {
	$text = trim( preg_replace( '/\s+/', ' ', (string) $text ) );
	$text = preg_replace_callback( '/\b(bogo|bbq|ayce)\b/i', function ( $m ) { return strtoupper( $m[1] ); }, $text );
	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( mb_substr( $text, 0, 1 ) ) . mb_substr( $text, 1 ) : ucfirst( $text );
}

/**
 * Who sent it, for the line under the deal: the place itself when the email is one of the listing's owners or
 * on the listing's website domain, otherwise a reader.
 */
function ltef_special_guess_source( $place, $email ) {
	$email = strtolower( trim( (string) $email ) );
	if ( ! $place || ! is_email( $email ) ) {
		return 'reader';
	}
	if ( function_exists( 'ltef_owner_emails' ) && in_array( $email, ltef_owner_emails( $place ), true ) ) {
		return 'owner';
	}
	$site = strtolower( (string) wp_parse_url( (string) ltef_meta( $place, 'website' ), PHP_URL_HOST ) );
	$site = preg_replace( '/^www\./', '', $site );
	$dom  = substr( strrchr( $email, '@' ), 1 );
	$ends = function ( $a, $b ) {
		return strlen( $a ) > strlen( $b ) && substr( $a, -strlen( $b ) ) === $b;
	};
	return $site && $dom && ( $dom === $site || $ends( $site, '.' . $dom ) || $ends( $dom, '.' . $site ) ) ? 'owner' : 'reader';
}

/** The pending Daily deal for a sent special: [ title, meta ]. */
function ltef_special_from_form( $v ) {
	$place = function_exists( 'ltef_owner_match_place' ) ? ltef_owner_match_place( $v['business'] ?? '' ) : 0;
	$deal  = ltef_special_tidy( $v['what'] ?? '' );
	$meta  = array(
		'place'         => $place,
		'deal'          => $deal,
		'details'       => ltef_special_when_extra( $v['when'] ?? '' ),
		'source'        => ltef_special_guess_source( $place, $v['email'] ?? '' ),
		'contact_email' => $v['email'] ?? '',
	);
	foreach ( ltef_parse_days( $v['when'] ?? '' ) as $d ) {
		$meta[ 'day_' . $d ] = true;
	}
	$name = $place ? html_entity_decode( get_the_title( $place ), ENT_QUOTES, 'UTF-8' ) : ltef_special_tidy( $v['business'] ?? '' );
	return array( $name . ': ' . $deal, $meta );
}

/** Review-queue chips for a Daily deal: the place, the days, and how it'll be credited. */
function ltef_special_flags( $post_id ) {
	$flags = array();
	$place = (int) ltef_meta( $post_id, 'place' );
	$flags[] = $place && 'publish' === get_post_status( $place )
		? array( '', 'At <a href="' . esc_url( get_permalink( $place ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $place ) ) . '</a>' )
		: array( 'warn', 'No place on the site matched. Pick one under Edit first.' );
	$days    = ltef_special_days( $post_id );
	$flags[] = $days
		? array( '', 'Shows on ' . esc_html( ltef_days_label( $days ) ) )
		: array( 'warn', 'No day found in “When”. Tick the days under Edit first.' );
	$who     = array( 'owner' => 'Credited “per the place”', 'reader' => 'Credited “from a reader, checked with the place”: check with them first', 'tina' => 'Credited “per Tina”' );
	$flags[] = array( '', esc_html( $who[ ltef_meta( $post_id, 'source' ) ? ltef_meta( $post_id, 'source' ) : 'tina' ] ?? '' ) );
	return $flags;
}

/** Approving needs a place and at least one day, or the deal would show nowhere. [ ok, note ] */
function ltef_special_ready( $post_id ) {
	$place = (int) ltef_meta( $post_id, 'place' );
	if ( ! $place || 'publish' !== get_post_status( $place ) ) {
		return array( false, 'Pick the place under Edit first, then approve.' );
	}
	if ( ! ltef_special_days( $post_id ) ) {
		return array( false, 'Tick the days it runs under Edit first, then approve.' );
	}
	return array( true, 'It’s on Daily deals under ' . ltef_days_label( ltef_special_days( $post_id ) ) . '.' );
}

/** When a deal goes up: stamp the month it was confirmed, and clear the cached Daily deals and place pages. */
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( 'ltef_special' !== $post->post_type || ( 'publish' !== $new && 'publish' !== $old ) ) {
			return;
		}
		if ( 'publish' === $new && ! ltef_meta( $post->ID, 'checked' ) ) {
			update_post_meta( $post->ID, 'ltef_checked', wp_date( 'Y-m' ) );
		}
		if ( function_exists( 'ltef_money_clear_page_cache' ) ) {
			ltef_money_clear_page_cache();
		}
	},
	10,
	3
);
