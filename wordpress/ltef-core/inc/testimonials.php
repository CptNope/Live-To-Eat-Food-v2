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
