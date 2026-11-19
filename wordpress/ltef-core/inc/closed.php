<?php
/**
 * Places that have closed for good: off Explore, Roulette and every list, and kept in
 * "In loving memory" at the bottom of Explore. Their pages stay up with Tina's videos
 * and say plainly that the place has closed, so nobody is sent there for nothing.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

function ltef_place_closed( $post_id ) {
	return (bool) ltef_meta( $post_id, 'closed' );
}

/** "Closed Nov 2026", or "Closed" without a month. */
function ltef_closed_label( $post_id ) {
	$month = ltef_month_label( (string) ltef_meta( $post_id, 'closed_on' ) );
	return $month ? 'Closed ' . $month : 'Closed';
}

/**
 * Keep closed places out of every list on the site: Explore, Tina's picks, latest visits,
 * Roulette, neighborhood and craving pages, llms.txt. The place page itself still opens,
 * the admin lists show everything, and a query can ask for them with ltef_include_closed.
 */
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ( is_admin() && ! wp_doing_ajax() ) || wp_doing_cron() || $q->get( 'ltef_include_closed' ) ) {
			return;
		}
		if ( 'any' === $q->get( 'post_status' ) || $q->get( 'p' ) || $q->get( 'name' ) || $q->get( 'post__in' ) || $q->is_singular() ) {
			return;
		}
		$types  = array_values( array_filter( (array) $q->get( 'post_type' ) ) );
		$places = array( 'ltef_place' ) === $types;
		$terms  = $q->is_main_query() && $q->is_tax( array( 'ltef_area', 'ltef_craving', 'ltef_with' ) );
		if ( ! $places && ! $terms ) {
			return;
		}
		$open = array(
			'relation' => 'OR',
			array( 'key' => 'ltef_closed', 'compare' => 'NOT EXISTS' ),
			array( 'key' => 'ltef_closed', 'value' => '1', 'compare' => '!=' ),
		);
		$mq   = $q->get( 'meta_query' );
		$q->set( 'meta_query', is_array( $mq ) && $mq ? array( 'relation' => 'AND', $mq, $open ) : array( $open ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
);

/** The closed places, most recently closed first. */
function ltef_closed_places() {
	$posts = get_posts(
		array(
			'post_type'           => 'ltef_place',
			'post_status'         => 'publish',
			'numberposts'         => -1,
			'ltef_include_closed' => true,
			'meta_query'          => array( array( 'key' => 'ltef_closed', 'value' => '1' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'             => 'title',
			'order'               => 'ASC',
		)
	);
	usort(
		$posts,
		function ( $a, $b ) {
			return strcmp( (string) ltef_meta( $b->ID, 'closed_on' ), (string) ltef_meta( $a->ID, 'closed_on' ) );
		}
	);
	return $posts;
}

/** One closed place as a card: sticker, name, when it closed, Tina's line, and how many of her videos are on its page. */
function ltef_memory_card( $post_id ) {
	$quote  = (string) ltef_meta( $post_id, 'tina_quote' );
	$videos = ( ltef_meta( $post_id, 'tina_video' ) ? 1 : 0 ) + count( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ltef_meta( $post_id, 'more_videos' ) ) ) ) );
	$meta   = array_filter( array( ltef_meta( $post_id, 'cuisine' ), ltef_meta( $post_id, 'town' ) ? ltef_meta( $post_id, 'town' ) : ltef_place_area_name( $post_id ) ) );
	return sprintf(
		'<article class="ltef-card memory-card"><a href="%1$s" class="ltef-card__link"><div class="ltef-card__ph" style="background: var(--%2$s);"><span class="ltef-lbl ltef-lbl--closed">%3$s</span><img src="%4$s" alt="" width="148" height="148" loading="lazy" decoding="async"></div><div class="ltef-card__row"><h3 class="ltef-card__name">%5$s</h3></div><p class="ltef-card__meta">%6$s</p>%7$s%8$s</a></article>',
		esc_url( get_permalink( $post_id ) ),
		esc_attr( ltef_place_field( $post_id ) ),
		esc_html( ltef_closed_label( $post_id ) ),
		esc_url( ltef_sticker_url( ltef_place_sticker( $post_id ) ) ),
		esc_html( get_the_title( $post_id ) ),
		esc_html( implode( ' · ', $meta ) ),
		'' !== $quote ? '<p class="ltef-note">“' . esc_html( $quote ) . '”</p>' : '',
		$videos ? '<p class="memory-card__vids">' . esc_html( 1 === $videos ? 'Tina’s video is on its page' : 'Tina’s ' . $videos . ' videos are on its page' ) . '</p>' : ''
	);
}

/** The "In loving memory" section at the bottom of Explore. Shows nothing until a place is marked closed. */
function ltef_block_in_memory() {
	$posts = ltef_closed_places();
	if ( ! $posts ) {
		return '';
	}
	return ltef_wrap(
		'<section class="in-memory" aria-labelledby="in-memory-h"><div class="in-memory__h"><h2 class="h-l" id="in-memory-h">In loving memory</h2><p class="meta">Places Tina loved that have closed for good. Their pages stay up with her videos, so the meals live on.</p></div>'
		. '<div class="cards">' . implode( '', array_map( function ( $p ) { return ltef_memory_card( $p->ID ); }, $posts ) ) . '</div></section>',
		'in-memory-wrap'
	);
}

/** On a closed place's page: say so where the address and hours would be, with no directions. */
function ltef_closed_panel( $post_id ) {
	$name = get_the_title( $post_id );
	$when = ltef_month_label( (string) ltef_meta( $post_id, 'closed_on' ) );
	return '<div class="panel facts-panel facts-panel--closed"><div style="display: grid; gap: 4px;"><h2 class="h-m" style="font-size: 30px;">' . esc_html( $name ) . ' has closed</h2>'
		. '<p class="meta" style="margin: 0;">' . esc_html( $when ? 'Closed for good in ' . $when . '.' : 'Closed for good.' ) . ' We keep this page for Tina’s videos and the memories.</p></div>'
		. '<p><a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="' . esc_url( get_post_type_archive_link( 'ltef_place' ) ) . '">Find somewhere open on Explore</a></p></div>';
}
