<?php
/**
 * Title: Home
 * Slug: live-to-eat-food/home
 * Categories: ltef-pages
 * Description: Where should we eat? Tina's latest picks, the home ad spot, Woo-pons, Roulette, events and Monthly Crumbs.
 * Viewport Width: 1400
 */
$u = function ( $p ) {
	return esc_url( home_url( $p ) );
};
?>
<!-- wp:group {"tagName":"section","className":"is-style-sheet home-hero","backgroundColor":"pistachio","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet home-hero has-pistachio-background-color has-background"><!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"62%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:62%"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Where should we eat?</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-lede"} -->
<p class="is-style-lede">Ten years of Worcester and Central Mass meals Tina really ate. Tell us what you’re craving and we’ll show you where she’d go.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo $u( '/explore/' ); ?>">Explore every place</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-alt"} -->
<div class="wp-block-button is-style-alt"><a class="wp-block-button__link wp-element-button" href="<?php echo $u( '/roulette/' ); ?>">Or spin for it</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-alt"} -->
<div class="wp-block-button is-style-alt"><a class="wp-block-button__link wp-element-button" href="<?php echo $u( '/woo-pons/' ); ?>">This month’s Woo-pons</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"38%","className":"home-hero__art"} -->
<div class="wp-block-column is-vertically-aligned-center home-hero__art" style="flex-basis:38%"><!-- wp:image {"sizeSlug":"full","className":"home-hero__stamp"} -->
<figure class="wp-block-image size-full home-hero__stamp"><img src="<?php echo ltef_theme_img( 'logo/ltef-stamp-tina-ate-here.svg' ); ?>" alt="Tina ate here: every pick is a real meal Tina ate"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></section>
<!-- /wp:group -->

<!-- wp:ltef/giveaway-banner /-->

<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"butter","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-butter-background-color has-background"><!-- wp:group {"className":"head"} -->
<div class="wp-block-group head"><!-- wp:heading {"anchor":"h-picks"} -->
<h2 id="h-picks" class="wp-block-heading">Tina’s latest picks</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-meta"} -->
<p class="is-style-meta"><a href="<?php echo $u( '/explore/' ); ?>">See every place</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:ltef/latest-places {"count":4} /-->

<!-- wp:ltef/ad-spot {"spot":"home"} /--></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"pistachio","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-pistachio-background-color has-background"><!-- wp:group {"className":"head"} -->
<div class="wp-block-group head"><!-- wp:heading -->
<h2 class="wp-block-heading">Local deals.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-lede"} -->
<p class="is-style-lede">Woo-pons are local deals; each ticket says whether Tina has eaten there. Show it at the counter.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:ltef/woopons /-->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-alt"} -->
<div class="wp-block-button is-style-alt"><a class="wp-block-button__link wp-element-button" href="<?php echo $u( '/woo-pons/#submit' ); ?>">Run a Woo-pon</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"basil","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-basil-background-color has-background"><!-- wp:heading -->
<h2 class="wp-block-heading">Can’t decide? Spin for it.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-lede"} -->
<p class="is-style-lede">Spin just the places Tina has eaten at, or every restaurant on the site. Pick a craving, say who’s coming, spin.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-alt"} -->
<div class="wp-block-button is-style-alt"><a class="wp-block-button__link wp-element-button" href="<?php echo $u( '/roulette/' ); ?>">Spin the wheel</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"butter","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-butter-background-color has-background"><!-- wp:group {"className":"head"} -->
<div class="wp-block-group head"><!-- wp:heading -->
<h2 class="wp-block-heading">This week around the table</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-meta"} -->
<p class="is-style-meta"><a href="<?php echo $u( '/events/' ); ?>">All events</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:ltef/events {"count":3} /--></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"said","className":"is-style-sheet hide-if-no-testimonials","backgroundColor":"pistachio","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section id="said" class="wp-block-group is-style-sheet hide-if-no-testimonials has-pistachio-background-color has-background"><!-- wp:heading {"anchor":"h-said"} -->
<h2 id="h-said" class="wp-block-heading">What businesses say</h2>
<!-- /wp:heading -->
<!-- wp:ltef/testimonials {"count":3,"more":true} /--></section>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"live-to-eat-food/crumbs-signup"} /-->
