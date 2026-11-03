<?php
/**
 * Title: Not found
 * Slug: live-to-eat-food/not-found
 * Categories: ltef-sections
 * Inserter: no
 */
$u = function ( $p ) {
	return esc_url( home_url( $p ) );
};
?>
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"pistachio","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-pistachio-background-color has-background">
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">That page isn’t on the menu.</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"is-style-lede"} -->
<p class="is-style-lede">It may have moved, or the place may have closed. Try Explore, or spin for somewhere to eat.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo $u( '/explore/' ); ?>">Explore every place</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-alt"} -->
<div class="wp-block-button is-style-alt"><a class="wp-block-button__link wp-element-button" href="<?php echo $u( '/roulette/' ); ?>">Spin for it</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</section>
<!-- /wp:group -->
