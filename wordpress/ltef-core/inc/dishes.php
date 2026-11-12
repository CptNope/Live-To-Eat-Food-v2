<?php
/**
 * New dishes: a business pays to list a new dish in "New dishes" on Explore for four weeks.
 *
 * They send the form at the bottom of Explore; Stripe holds the price on their card and Tina approves it in
 * the review queue, which charges the card and puts the dish up. The four weeks start that day. Each card is
 * labeled as a paid listing, since Tina hasn't necessarily tried the dish.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** How long a new dish shows, in days. */
const LTEF_DISH_DAYS = 28;

/** The longest "About it". */
const LTEF_DISH_ABOUT_MAX = 200;

/** The four weeks start when the dish goes up (approved, or paid after approval), not when it was sent. */
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( 'ltef_dish' !== $post->post_type || 'publish' !== $new || 'publish' === $old ) {
			return;
		}
		if ( ! ltef_meta( $post->ID, 'until' ) ) {
			update_post_meta( $post->ID, 'ltef_until', wp_date( 'Y-m-d', strtotime( '+' . ( LTEF_DISH_DAYS - 1 ) . ' days' ) ) );
			update_post_meta( $post->ID, 'ltef_live_from', wp_date( 'Y-m-d' ) );
		}
		if ( function_exists( 'ltef_money_clear_page_cache' ) ) {
			ltef_money_clear_page_cache(); // Explore is cached; the new card should show now
		}
	},
	10,
	3
);

/** The dishes showing today, newest first. */
function ltef_dishes_live( $limit = 24 ) {
	return get_posts(
		array(
			'post_type'   => 'ltef_dish',
			'post_status' => 'publish',
			'numberposts' => $limit,
			'meta_key'    => 'ltef_until', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'     => array( 'meta_value' => 'DESC', 'date' => 'DESC' ),
			'meta_query'  => array( array( 'key' => 'ltef_until', 'value' => ltef_today(), 'compare' => '>=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
}

/** One dish as a card. */
function ltef_dish_card( $id ) {
	$place = (int) ltef_meta( $id, 'place' );
	$place = $place && 'publish' === get_post_status( $place ) ? $place : 0;
	$biz   = $place ? get_the_title( $place ) : (string) ltef_meta( $id, 'business' );
	$photo = (int) ltef_meta( $id, 'photo' );
	$img   = $photo ? wp_get_attachment_image( $photo, 'medium_large', false, array( 'class' => 'dish-card__img', 'alt' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 600px) 100vw, 300px' ) ) : '';
	$price = (string) ltef_meta( $id, 'price_text' );
	$price = preg_match( '/^\d+(\.\d{1,2})?$/', $price ) ? '$' . $price : $price;
	$about = (string) ltef_meta( $id, 'about' );
	$from  = (string) ltef_meta( $id, 'live_from' );
	return '<li class="dish-card">'
		. ( $img ? '<div class="dish-card__ph">' . $img . '</div>' : '<div class="dish-card__ph dish-card__ph--none" aria-hidden="true">' . ltef_icon( 'plus' ) . '</div>' )
		. '<div class="dish-card__body">'
		. '<h3 class="dish-card__t">' . esc_html( get_the_title( $id ) ) . '</h3>'
		. '<p class="dish-card__biz">' . ( $place ? '<a href="' . esc_url( get_permalink( $place ) ) . '">' . esc_html( $biz ) . '</a>' : esc_html( $biz ) ) . ( '' !== $price ? ' · <span class="dish-card__price">' . esc_html( $price ) . '</span>' : '' ) . '</p>'
		. ( '' !== $about ? '<p class="dish-card__about">' . esc_html( $about ) . '</p>' : '' )
		. '<p class="dish-card__foot">' . ltef_label( 'sponsored', 'Paid listing' ) . ( $from ? '<span class="meta">Added ' . esc_html( date_i18n( 'M j', strtotime( $from ) ) ) . '</span>' : '' ) . '</p>'
		. '</div></li>';
}

/** The New dishes section: the live dishes, or an invitation to add one. */
function ltef_block_new_dishes() {
	$dishes = ltef_dishes_live();
	$price  = ltef_money( ltef_setting( 'dish_price' ) );
	$head   = '<div class="dishes__head"><h2 class="h-l" id="new-dishes">New dishes</h2>'
		. '<p class="dishes__lede">New on menus around Worcester, sent in by the places themselves. Each is a paid listing for ' . (int) ( LTEF_DISH_DAYS / 7 ) . ' weeks, so Tina may not have tried it yet.</p></div>';
	$add    = '<a class="ltef-btn ltef-btn--sm ltef-btn--alt" href="#ltef-dish">New on your menu? Add it for ' . esc_html( $price ) . '</a>';
	if ( ! $dishes ) {
		return ltef_wrap( '<section class="dishes dishes--empty" aria-labelledby="new-dishes">' . $head . '<p class="meta" style="margin: 0;">Nothing new listed right now.</p>' . $add . '</section>', 'new-dishes' );
	}
	$cards = implode( '', array_map( function ( $p ) { return ltef_dish_card( $p->ID ); }, $dishes ) );
	return ltef_wrap( '<section class="dishes" aria-labelledby="new-dishes">' . $head . '<ul class="dishes__list" role="list">' . $cards . '</ul>' . $add . '</section>', 'new-dishes' );
}

/** Once a day, just after midnight, clear the page cache when a dish's four weeks ran out, so Explore stops showing it. */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ltef_dishes_daily' ) ) {
			$midnight = ( new DateTime( 'tomorrow', wp_timezone() ) )->getTimestamp();
			wp_schedule_event( $midnight + 3 * MINUTE_IN_SECONDS, 'daily', 'ltef_dishes_daily' );
		}
	}
);
add_action(
	'ltef_dishes_daily',
	function () {
		$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day' ) );
		$ended     = get_posts( array( 'post_type' => 'ltef_dish', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'ltef_until', 'value' => $yesterday ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $ended && function_exists( 'ltef_money_clear_page_cache' ) ) {
			ltef_money_clear_page_cache();
		}
	}
);
