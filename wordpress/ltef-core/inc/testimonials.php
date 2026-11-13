<?php
/**
 * Testimonials: businesses send how it went with Tina from /testimonial/, Tina approves each in the
 * review queue, and approved ones show under "What businesses say". Never written for anyone, never paid for.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** The longest testimonial. */
const LTEF_TESTIMONIAL_MAX = 500;

/** Pages show testimonials, so a newly approved one clears the page cache. */
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( 'ltef_testimonial' === $post->post_type && ( 'publish' === $new || 'publish' === $old ) && $new !== $old && function_exists( 'ltef_money_clear_page_cache' ) ) {
			ltef_money_clear_page_cache();
		}
	},
	10,
	3
);

/** How many testimonials are up. */
function ltef_testimonial_count() {
	$c = wp_count_posts( 'ltef_testimonial' );
	return isset( $c->publish ) ? (int) $c->publish : 0;
}

/**
 * A section (group block) with the class hide-if-no-testimonials shows only once a testimonial is up, so
 * About Tina and the home page never show an empty "What businesses say".
 */
add_filter(
	'render_block_core/group',
	function ( $html, $block ) {
		$class = (string) ( $block['attrs']['className'] ?? '' );
		return false !== strpos( $class, 'hide-if-no-testimonials' ) && ! ltef_testimonial_count() ? '' : $html;
	},
	10,
	2
);

/** The place a testimonial's business is: the one Tina picked, or else a match by the business name. 0 if none. */
function ltef_testimonial_place( $id ) {
	$place = (int) ltef_meta( $id, 'place' );
	if ( $place && 'publish' === get_post_status( $place ) ) {
		return $place;
	}
	return function_exists( 'ltef_owner_match_place' ) ? ltef_owner_match_place( (string) ltef_meta( $id, 'business' ) ) : 0;
}
