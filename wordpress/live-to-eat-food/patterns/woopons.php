<?php
/**
 * Title: Woo-pons
 * Slug: live-to-eat-food/woopons
 * Categories: ltef-sections
 * Inserter: no
 */
$u = function ( $p ) {
	return esc_url( home_url( $p ) );
};
?>
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"pistachio","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-pistachio-background-color has-background">
<!-- wp:image {"sizeSlug":"full","className":"woopons-mark"} -->
<figure class="wp-block-image size-full woopons-mark"><img src="<?php echo ltef_theme_img( 'stickers/woopon.svg' ); ?>" alt="Woo-pons"/></figure>
<!-- /wp:image -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Local deals.</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"is-style-lede"} -->
<p class="is-style-lede">Woo-pons are local deals around Worcester, sent in by the businesses themselves. Some are at places Tina loves, some at places she hasn’t tried yet, and every ticket says which. Businesses pay to list them (the first two weeks are free), and that never buys a pick.</p>
<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"butter","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-butter-background-color has-background">
<!-- wp:heading {"level":2,"anchor":"h-list"} -->
<h2 id="h-list" class="wp-block-heading">This week’s Woo-pons</h2>
<!-- /wp:heading -->
<!-- wp:ltef/woopons /-->
</section>
<!-- /wp:group -->
<!-- wp:pattern {"slug":"live-to-eat-food/woopons-using"} /-->
<!-- wp:pattern {"slug":"live-to-eat-food/woopons-run"} /-->
<!-- wp:pattern {"slug":"live-to-eat-food/woopons-online"} /-->
