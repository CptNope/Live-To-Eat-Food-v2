<?php
/**
 * Title: Header
 * Slug: live-to-eat-food/header
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 */
$u = function ( $p ) {
	return esc_url( home_url( $p ) );
};
?>
<!-- wp:group {"tagName":"header","className":"ltef-header","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<header class="wp-block-group ltef-header"><!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"ltef-logo"} -->
<figure class="wp-block-image size-full ltef-logo"><a href="<?php echo $u( '/' ); ?>"><img src="<?php echo ltef_theme_img( 'logo/ltef-wordmark-line.svg' ); ?>" alt="Live to Eat Food home"/></a></figure>
<!-- /wp:image -->

<!-- wp:navigation {"ariaLabel":"Main","overlayMenu":"mobile","hasIcon":false,"openSubmenusOnClick":true,"layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"}} -->
<!-- wp:navigation-link {"label":"Explore","url":"<?php echo $u( '/explore/' ); ?>","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"Guides","url":"<?php echo $u( '/guides/' ); ?>","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"Woo-pons","url":"<?php echo $u( '/woo-pons/' ); ?>","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"Roulette","url":"<?php echo $u( '/roulette/' ); ?>","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"Events","url":"<?php echo $u( '/events/' ); ?>","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-submenu {"label":"More","url":"#","kind":"custom"} -->
<!-- wp:navigation-link {"label":"Daily deals","url":"<?php echo $u( '/daily-deals/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Your bites","url":"<?php echo $u( '/your-bites/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Food help","url":"<?php echo $u( '/food-help/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Tina","url":"<?php echo $u( '/tina/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"How it works","url":"<?php echo $u( '/how-it-works/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"For restaurants","url":"<?php echo $u( '/for-restaurants/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Restaurant jobs","url":"<?php echo $u( '/restaurant-jobs/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Work with Tina","url":"<?php echo $u( '/work-with-tina/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Send something in","url":"<?php echo $u( '/submit/' ); ?>","kind":"custom"} /-->
<!-- /wp:navigation-submenu -->
<!-- /wp:navigation -->

<!-- wp:buttons {"className":"ltef-cta"} -->
<div class="wp-block-buttons ltef-cta"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo $u( '/crumbs/' ); ?>">Get Monthly Crumbs</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></header>
<!-- /wp:group -->
