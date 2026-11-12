<?php
/**
 * Title: Explore
 * Slug: live-to-eat-food/explore
 * Categories: ltef-sections
 * Inserter: no
 */
$u = function ( $p ) {
	return esc_url( home_url( $p ) );
};
?>
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"lemon","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-lemon-background-color has-background">
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Explore</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"is-style-lede"} -->
<p class="is-style-lede">Every place to eat on the site: the ones Tina ate at, newest first, then the ones she hasn’t been to yet. Search what she ordered, pick a craving or a neighborhood.</p>
<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"vinyl","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-vinyl-background-color has-background">
<!-- wp:ltef/new-dishes /-->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"butter","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-butter-background-color has-background">
<!-- wp:ltef/explore /-->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","anchor":"add-dish","className":"is-style-sheet","backgroundColor":"pistachio","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section id="add-dish" class="wp-block-group is-style-sheet has-pistachio-background-color has-background"><!-- wp:group {"className":"two"} -->
<div class="wp-block-group two"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"anchor":"h-add-dish"} -->
<h2 id="h-add-dish" class="wp-block-heading">New on your menu?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Put it in New dishes at the top of Explore for 4 weeks, for [ltef_price key="dish_price"] a dish. It carries a Paid listing label, so people know you sent it in.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"is-style-meta"} -->
<p class="is-style-meta">Listing a dish doesn’t mean Tina has tried it, and it never buys her verdict. Want her to try it? <a href="<?php echo $u( '/how-it-works/' ); ?>#ltef-suggest">Suggest your spot</a>, free. A deal that comes back every week goes on <a href="<?php echo $u( '/daily-deals/' ); ?>#tell">Daily deals</a>, also free.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- wp:ltef/submit-form {"form":"dish"} /--></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
