<?php
/**
 * Content types: places, Woo-pons, events, jobs, ad spots and private submissions.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ltef_register_post_types' );

function ltef_register_post_types() {
	$common = array(
		'public'       => true,
		'show_in_rest' => true,
		'menu_position' => 21,
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
	);

	register_post_type(
		'ltef_place',
		array_merge(
			$common,
			array(
				'labels'      => ltef_labels( 'Place', 'Places' ),
				'menu_icon'   => 'dashicons-location',
				'has_archive' => 'explore',
				'rewrite'     => array( 'slug' => 'place', 'with_front' => false ),
				'description' => 'Every place on the site: the ones Tina ate at, and the ones she hasn’t been to yet.',
			)
		)
	);
	register_post_type(
		'ltef_woopon',
		array_merge(
			$common,
			array(
				'labels'      => ltef_labels( 'Woo-pon', 'Woo-pons' ),
				'menu_icon'   => 'dashicons-tickets-alt',
				'has_archive' => 'woo-pons',
				'rewrite'     => array( 'slug' => 'woo-pon', 'with_front' => false ),
				'supports'    => array( 'title', 'thumbnail', 'custom-fields', 'revisions' ),
			)
		)
	);
	register_post_type(
		'ltef_event',
		array_merge(
			$common,
			array(
				'labels'      => ltef_labels( 'Event', 'Events' ),
				'menu_icon'   => 'dashicons-calendar-alt',
				'has_archive' => 'events',
				'rewrite'     => array( 'slug' => 'event', 'with_front' => false ),
			)
		)
	);
	register_post_type(
		'ltef_job',
		array_merge(
			$common,
			array(
				'labels'      => ltef_labels( 'Job', 'Jobs' ),
				'menu_icon'   => 'dashicons-businessperson',
				'has_archive' => 'restaurant-jobs',
				'rewrite'     => array( 'slug' => 'job', 'with_front' => false ),
			)
		)
	);
	register_post_type(
		'ltef_ad',
		array(
			'labels'        => ltef_labels( 'Ad', 'Ads' ),
			'public'        => false,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-megaphone',
			'menu_position' => 22,
			'supports'      => array( 'title', 'thumbnail', 'custom-fields' ),
			'description'   => 'Sold ad spots. A spot with no running ad shows “Ad space open”.',
		)
	);
	register_post_type(
		'ltef_special',
		array(
			'labels'        => array_merge( ltef_labels( 'Daily deal', 'Daily deals' ), array( 'all_items' => 'Daily deals' ) ),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => 'edit.php?post_type=ltef_place',
			'show_in_rest'  => true,
			'supports'      => array( 'title', 'custom-fields', 'revisions' ),
			'description'   => 'Specials that come back every week, shown by day on the Daily deals page and on the place’s page.',
		)
	);
	register_post_type(
		'ltef_submission',
		array(
			'labels'        => array_merge( ltef_labels( 'Message', 'Messages' ), array( 'all_items' => 'Messages and signups' ) ),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => 'ltef-review',
			'show_in_rest'  => false,
			'supports'      => array( 'title', 'custom-fields' ),
			'capability_type' => 'post',
			'capabilities'  => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'  => true,
		)
	);

	$tax = array(
		'public'            => true,
		'show_in_rest'      => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
	);
	register_taxonomy( 'ltef_area', array( 'ltef_place', 'ltef_woopon', 'ltef_event', 'ltef_job' ), array_merge( $tax, array( 'labels' => ltef_tax_labels( 'Neighborhood', 'Neighborhoods' ), 'rewrite' => array( 'slug' => 'neighborhood' ) ) ) );
	register_taxonomy( 'ltef_craving', array( 'ltef_place' ), array_merge( $tax, array( 'labels' => ltef_tax_labels( 'Craving', 'Cravings' ), 'rewrite' => array( 'slug' => 'craving' ) ) ) );
	register_taxonomy( 'ltef_with', array( 'ltef_place' ), array_merge( $tax, array( 'labels' => ltef_tax_labels( 'Good with', 'Good with' ), 'rewrite' => array( 'slug' => 'good-with' ) ) ) );
}

function ltef_labels( $one, $many ) {
	return array(
		'name'               => $many,
		'singular_name'      => $one,
		'add_new_item'       => "Add $one",
		'edit_item'          => "Edit $one",
		'new_item'           => "New $one",
		'view_item'          => "View $one",
		'search_items'       => "Search $many",
		'not_found'          => 'None yet',
		'all_items'          => "All $many",
		'menu_name'          => $many,
	);
}

function ltef_tax_labels( $one, $many ) {
	return array( 'name' => $many, 'singular_name' => $one, 'add_new_item' => "Add $one", 'edit_item' => "Edit $one", 'all_items' => "All $many" );
}
