<?php
/**
 * Live to Eat Food block theme.
 *
 * @package LiveToEatFood
 */

defined( 'ABSPATH' ) || exit;

define( 'LTEF_THEME_VERSION', '1.0.0' );

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/ltef.css' );
		add_theme_support( 'responsive-embeds' );
	}
);

/** The design system's stylesheet: tokens, components (labels, tickets, ad spots) and page layout. */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'live-to-eat-food', get_theme_file_uri( 'assets/css/ltef.css' ), array(), LTEF_THEME_VERSION );
	}
);

/** Preload the two faces every page uses first. */
add_action(
	'wp_head',
	function () {
		foreach ( array( 'outfit-bold', 'figtree' ) as $f ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_theme_file_uri( 'assets/fonts/' . $f . '.woff2' ) ) );
		}
	},
	1
);

/** Block styles that carry the brand: sheets, the alt button, panels, the lede. */
add_action(
	'init',
	function () {
		register_block_style( 'core/group', array( 'name' => 'sheet', 'label' => 'Sheet' ) );
		register_block_style( 'core/group', array( 'name' => 'panel', 'label' => 'Panel (sticker)' ) );
		register_block_style( 'core/group', array( 'name' => 'dashed', 'label' => 'Dashed (an empty slot)' ) );
		register_block_style( 'core/button', array( 'name' => 'alt', 'label' => 'Outline' ) );
		register_block_style( 'core/paragraph', array( 'name' => 'lede', 'label' => 'Lede' ) );
		register_block_style( 'core/paragraph', array( 'name' => 'hand', 'label' => 'Tina’s hand' ) );
		register_block_style( 'core/paragraph', array( 'name' => 'meta', 'label' => 'Small print' ) );
		register_block_pattern_category( 'ltef-pages', array( 'label' => 'Live to Eat Food: pages' ) );
		register_block_pattern_category( 'ltef-sections', array( 'label' => 'Live to Eat Food: sections' ) );
	}
);

/** A theme image URL for patterns. */
function ltef_theme_img( $rel ) {
	return esc_url( get_theme_file_uri( 'assets/img/' . $rel ) );
}

/** Whether the companion plugin is active (patterns use its blocks). */
function ltef_has_core() {
	return function_exists( 'ltef_ad_spot' );
}

/** Ask for the companion plugin once, in the admin. */
add_action(
	'admin_notices',
	function () {
		if ( ltef_has_core() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>Live to Eat Food:</strong> activate the Live to Eat Food Core plugin for places, Woo-pons, events, jobs, ad spots and the review queue.</p></div>';
	}
);
