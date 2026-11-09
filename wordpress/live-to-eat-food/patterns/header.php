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

<!-- wp:ltef/site-nav /-->
</header>
<!-- /wp:group -->
