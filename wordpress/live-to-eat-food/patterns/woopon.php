<?php
/**
 * Title: Woo-pon
 * Slug: live-to-eat-food/woopon
 * Categories: ltef-sections
 * Inserter: no
 */
$u = function ( $p ) {
	return esc_url( home_url( $p ) );
};
?>
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"pistachio","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-pistachio-background-color has-background">
<!-- wp:paragraph {"className":"is-style-meta"} -->
<p class="is-style-meta"><a href="<?php echo $u( '/woo-pons/' ); ?>">All Woo-pons</a></p>
<!-- /wp:paragraph -->
<!-- wp:ltef/woopon-ticket /-->
</section>
<!-- /wp:group -->
