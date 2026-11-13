<?php
/**
 * What Explore's search box looks through for a place.
 *
 * Beyond the name, cuisine and what Tina ordered: her line, the facts, the address, neighborhood and cravings,
 * her other videos' captions, the owner's note, and the titles of her videos as TikTok and YouTube give them.
 * Video titles are looked up in the background (an hour at a time, a few places per run) and kept for search
 * only; they are never shown on the page.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** How long a place's search text may run, so the Explore page stays light. */
const LTEF_SEARCH_MAX = 600;

/** Text boiled down for matching: lowercase, no accents, no apostrophes or hashtags. The script does the same to what people type. */
function ltef_search_norm( $text ) {
	$t = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
	$t = remove_accents( $t );
	$t = strtolower( $t );
	$t = preg_replace( '/[\x{2018}\x{2019}\x{02BC}\'#]/u', '', $t );
	$t = preg_replace( '/[^a-z0-9$&]+/', ' ', $t );
	return trim( $t );
}

/** Everything a search for this place should find it by. */
function ltef_place_search_text( $post_id ) {
	$terms = function ( $tax ) use ( $post_id ) {
		$t = get_the_terms( $post_id, $tax );
		return ( $t && ! is_wp_error( $t ) ) ? implode( ' ', wp_list_pluck( $t, 'name' ) ) : '';
	};
	$captions = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) ltef_meta( $post_id, 'more_videos' ) ) as $line ) {
		$parts = explode( '|', $line, 3 );
		if ( isset( $parts[2] ) ) {
			$captions[] = $parts[2];
		}
	}
	$bits = array(
		get_the_title( $post_id ),
		ltef_meta( $post_id, 'cuisine' ),
		ltef_meta( $post_id, 'dish' ),
		ltef_meta( $post_id, 'tina_quote' ),
		$terms( 'ltef_area' ),
		$terms( 'ltef_craving' ),
		ltef_meta( $post_id, 'address' ),
		ltef_meta( $post_id, 'town' ),
		implode( ' ', $captions ),
		get_post_meta( $post_id, 'ltef_video_titles', true ),
		ltef_meta( $post_id, 'facts' ),
		ltef_meta( $post_id, 'owner_note' ),
	);
	$text = ltef_search_norm( implode( ' ', array_filter( array_map( 'strval', $bits ) ) ) );
	// Each word once, in order, so a long caption doesn't crowd out the rest.
	$text = implode( ' ', array_unique( explode( ' ', $text ) ) );
	return mb_substr( $text, 0, LTEF_SEARCH_MAX );
}

/* ------------------------------------------------------------------ Video titles */

/** Every video link on a place: her main video and its copies, and her other videos. */
function ltef_place_video_urls( $post_id ) {
	$urls = array();
	foreach ( array( 'tina_video', 'youtube', 'instagram', 'facebook' ) as $k ) {
		$urls[] = (string) ltef_meta( $post_id, $k );
	}
	foreach ( preg_split( '/\r\n|\r|\n/', (string) ltef_meta( $post_id, 'more_videos' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 3 ) );
		foreach ( preg_split( '/\s+/', $parts[1] ?? ( $parts[0] ?? '' ) ) as $u ) {
			$urls[] = $u;
		}
	}
	return array_values( array_unique( array_filter( $urls, function ( $u ) { return (bool) preg_match( '#^https?://#', $u ); } ) ) );
}

/** A video's title from the platform (TikTok gives the caption). '' when the platform has none for us. */
function ltef_video_title( $url ) {
	$pf  = ltef_video_platform( $url );
	$api = array(
		'youtube' => 'https://www.youtube.com/oembed?format=json&url=',
		'tiktok'  => 'https://www.tiktok.com/oembed?url=',
	);
	if ( ! isset( $api[ $pf ] ) ) {
		return ''; // Instagram and Facebook need an app token for this
	}
	$res = wp_remote_get( $api[ $pf ] . rawurlencode( $url ), array( 'timeout' => 8 ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	return is_array( $data ) ? trim( (string) ( $data['title'] ?? '' ) ) : '';
}

/** Look up a place's video titles again if its links changed since the last look. Returns true if it looked. */
function ltef_refresh_video_titles( $post_id ) {
	$urls = ltef_place_video_urls( $post_id );
	$hash = md5( implode( ' ', $urls ) );
	if ( get_post_meta( $post_id, 'ltef_video_titles_for', true ) === $hash ) {
		return false;
	}
	$titles = array();
	foreach ( $urls as $u ) {
		$t = ltef_video_title( $u );
		if ( '' !== $t ) {
			$titles[] = $t;
		}
	}
	update_post_meta( $post_id, 'ltef_video_titles', implode( "\n", array_unique( $titles ) ) );
	update_post_meta( $post_id, 'ltef_video_titles_for', $hash );
	return true;
}

/** Search-only fields never show in the place's Custom Fields box. */
add_filter(
	'is_protected_meta',
	function ( $protected, $key ) {
		return in_array( $key, array( 'ltef_video_titles', 'ltef_video_titles_for' ), true ) ? true : $protected;
	},
	10,
	2
);

/** When a place is saved, look up its video titles shortly after (not during the save). */
add_action(
	'save_post_ltef_place',
	function ( $post_id ) {
		if ( wp_is_post_revision( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return;
		}
		if ( ! wp_next_scheduled( 'ltef_video_titles_one', array( (int) $post_id ) ) ) {
			wp_schedule_single_event( time() + 30, 'ltef_video_titles_one', array( (int) $post_id ) );
		}
	}
);
add_action(
	'ltef_video_titles_one',
	function ( $post_id ) {
		if ( ltef_refresh_video_titles( (int) $post_id ) && function_exists( 'ltef_money_clear_page_cache' ) ) {
			ltef_money_clear_page_cache();
		}
	}
);

/** Every hour, catch up on a few places whose titles haven't been looked up yet, until all have. */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ltef_video_titles_batch' ) ) {
			wp_schedule_event( time() + 120, 'hourly', 'ltef_video_titles_batch' );
		}
	}
);
add_action(
	'ltef_video_titles_batch',
	function () {
		$ids = get_posts(
			array(
				'post_type'   => 'ltef_place',
				'post_status' => 'publish',
				'numberposts' => 25,
				'fields'      => 'ids',
				'orderby'     => 'ID',
				'order'       => 'ASC',
				'meta_query'  => array( array( 'key' => 'ltef_video_titles_for', 'compare' => 'NOT EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$looked = 0;
		foreach ( $ids as $id ) {
			if ( ltef_refresh_video_titles( $id ) && ltef_place_video_urls( $id ) ) {
				++$looked;
			}
		}
		if ( $looked && function_exists( 'ltef_money_clear_page_cache' ) ) {
			ltef_money_clear_page_cache();
		}
	}
);
