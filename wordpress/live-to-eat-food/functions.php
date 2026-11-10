<?php
/**
 * Live to Eat Food block theme.
 *
 * @package LiveToEatFood
 */

defined( 'ABSPATH' ) || exit;

define( 'LTEF_THEME_VERSION', wp_get_theme( 'live-to-eat-food' )->get( 'Version' ) ); // from style.css, so a new version gets past browser and Cloudflare caches

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
		wp_enqueue_style( 'live-to-eat-food', get_theme_file_uri( 'assets/css/ltef.css' ), array(), LTEF_THEME_VERSION . '.' . filemtime( get_theme_file_path( 'assets/css/ltef.css' ) ) );
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

/**
 * The sushi-roll avatar as the site icon: browser tabs, bookmarks, phone home screens.
 * A Site Icon chosen in Settings → General takes over, as WordPress expects.
 */
function ltef_theme_icons() {
	if ( has_site_icon() ) {
		return;
	}
	$u = function ( $f ) {
		return esc_url( get_theme_file_uri( 'assets/img/icons/' . $f ) . '?v=' . LTEF_THEME_VERSION );
	};
	echo '<link rel="icon" href="' . $u( 'favicon.ico' ) . '" sizes="48x48">' . "\n";
	echo '<link rel="icon" href="' . $u( 'favicon.svg' ) . '" type="image/svg+xml">' . "\n";
	echo '<link rel="icon" href="' . $u( 'favicon-32.png' ) . '" type="image/png" sizes="32x32">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . $u( 'apple-touch-icon.png' ) . '">' . "\n";
	echo '<link rel="manifest" href="' . $u( 'site.webmanifest' ) . '">' . "\n";
	echo '<meta name="theme-color" content="#FFE586">' . "\n";
}
add_action( 'wp_head', 'ltef_theme_icons', 2 );
add_action( 'admin_head', 'ltef_theme_icons' );
add_action( 'login_head', 'ltef_theme_icons' );

/** Browsers still ask for /favicon.ico on their own; answer with the avatar instead of the WordPress logo. */
add_action(
	'do_faviconico',
	function () {
		if ( has_site_icon() ) {
			return;
		}
		$file = get_theme_file_path( 'assets/img/icons/favicon.ico' );
		if ( is_readable( $file ) ) {
			header( 'Content-Type: image/x-icon' );
			header( 'Cache-Control: public, max-age=604800' );
			header( 'Content-Length: ' . filesize( $file ) );
			readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
			exit;
		}
	}
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
