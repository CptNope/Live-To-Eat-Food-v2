<?php
/**
 * The admin in the site's colors: a "Live to Eat Food" color scheme for all of
 * wp-admin (anyone can switch back under Users → Profile), styled Live to Eat
 * Food screens that work on a phone, and a branded sign-in page.
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

/** The theme folder the fonts and wordmark come from (the plugin ships none of its own). */
function ltef_brand_asset( $path ) {
	return trailingslashit( get_theme_root_uri( 'live-to-eat-food' ) ) . 'live-to-eat-food/assets/' . ltrim( $path, '/' );
}

add_action( 'admin_init', 'ltef_register_admin_scheme', 1 );
function ltef_register_admin_scheme() {
	wp_admin_css_color(
		'ltef',
		'Live to Eat Food',
		LTEF_URL . 'assets/css/admin-colors.css?v=' . LTEF_VERSION,
		array( '#1E3B2A', '#16301F', '#FFE586', '#FFC53D' ),
		array( 'base' => '#CFE3BC', 'focus' => '#FFE586', 'current' => '#1E3B2A' )
	);
	// Once: everyone on WordPress's default scheme moves to the site's. A choice made later in Profile sticks.
	if ( get_option( 'ltef_admin_scheme_set' ) ) {
		return;
	}
	foreach ( get_users( array( 'fields' => 'ID' ) ) as $uid ) {
		$c = get_user_meta( $uid, 'admin_color', true );
		if ( '' === $c || 'fresh' === $c ) {
			update_user_meta( $uid, 'admin_color', 'ltef' );
		}
	}
	update_option( 'ltef_admin_scheme_set', 1, false );
}

/** New accounts start on the site's scheme too. */
add_action(
	'user_register',
	function ( $uid ) {
		if ( in_array( get_user_meta( $uid, 'admin_color', true ), array( '', 'fresh' ), true ) ) {
			update_user_meta( $uid, 'admin_color', 'ltef' );
		}
	}
);

/** True on the plugin's own screens: Review queue, Settings, Email, Payments, Money, setup. */
function ltef_is_brand_screen() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	return $screen && false !== strpos( $screen->id, 'page_ltef-' );
}

add_filter(
	'admin_body_class',
	function ( $classes ) {
		return ltef_is_brand_screen() ? $classes . ' ltef-screen' : $classes;
	}
);

add_action(
	'admin_enqueue_scripts',
	function () {
		if ( ltef_is_brand_screen() ) {
			wp_enqueue_style( 'ltef-admin', LTEF_URL . 'assets/css/admin.css', array(), LTEF_VERSION );
		}
	}
);

/** The brand faces, from the theme. Only where something uses them. */
function ltef_brand_fonts_css() {
	$f = function ( $family, $file, $weight, $extra = '' ) {
		return sprintf( '@font-face{font-family:"%s";src:url("%s") format("woff2");font-weight:%s;font-style:normal;font-display:swap;%s}', $family, esc_url( ltef_brand_asset( 'fonts/' . $file ) ), $weight, $extra );
	};
	return $f( 'Figtree', 'figtree.woff2', '400 700' )
		. $f( 'Bricolage Grotesque', 'bricolage-grotesque.woff2', '400 800', 'font-stretch:85% 100%;' )
		. $f( 'Outfit', 'outfit-bold.woff2', '100 900' );
}

add_action(
	'admin_head',
	function () {
		if ( 'ltef' !== get_user_option( 'admin_color' ) && ! ltef_is_brand_screen() ) {
			return;
		}
		echo '<style id="ltef-fonts">' . ltef_brand_fonts_css() . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from fixed strings and escaped URLs
	},
	1
);

add_action(
	'login_enqueue_scripts',
	function () {
		wp_enqueue_style( 'ltef-login', LTEF_URL . 'assets/css/login.css', array( 'login' ), LTEF_VERSION );
		wp_add_inline_style( 'ltef-login', ltef_brand_fonts_css() . 'body.login{--ltef-wordmark:url("' . esc_url( ltef_brand_asset( 'img/logo/ltef-wordmark.svg' ) ) . '")}' );
	}
);
add_filter( 'login_headerurl', 'home_url' );
add_filter(
	'login_headertext',
	function () {
		return get_bloginfo( 'name' );
	}
);
