<?php
/**
 * Title: Place
 * Slug: live-to-eat-food/place
 * Categories: ltef-sections
 * Inserter: no
 */
$u = function ( $p ) {
	return esc_url( home_url( $p ) );
};
?>
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"pistachio","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-pistachio-background-color has-background">
<!-- wp:group {"className":"place-hero-wp"} -->
<div class="wp-block-group place-hero-wp"><!-- wp:ltef/place-video /-->

<!-- wp:group {"className":"place-hero__main"} -->
<div class="wp-block-group place-hero__main"><!-- wp:post-title {"level":1} /-->

<!-- wp:ltef/place-take /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"butter","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-butter-background-color has-background">
<!-- wp:ltef/place-more /-->
<!-- wp:ltef/place-facts /-->
<!-- wp:post-content {"className":"ltef-prose","layout":{"type":"default"}} /-->
</section>
<!-- /wp:group -->
<!-- wp:pattern {"slug":"live-to-eat-food/crumbs-signup"} /-->
