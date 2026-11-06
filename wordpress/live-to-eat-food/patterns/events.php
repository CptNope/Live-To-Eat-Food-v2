<?php
/**
 * Title: Events
 * Slug: live-to-eat-food/events
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
<h1 class="wp-block-heading">Out &amp; About Worcester+</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"is-style-lede"} -->
<p class="is-style-lede">Food nights, markets, music, art walks, classes and Live to Eat meetups in Worcester and beyond: the + is for everything past the city line.</p>
<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"vinyl","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-vinyl-background-color has-background">
<!-- wp:heading {"level":2,"anchor":"h-wk"} -->
<h2 id="h-wk" class="wp-block-heading">Coming up</h2>
<!-- /wp:heading -->
<!-- wp:ltef/events {"count":24} /-->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"pistachio","anchor":"submit","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section id="submit" class="wp-block-group is-style-sheet has-pistachio-background-color has-background">
<!-- wp:group {"className":"two"} -->
<div class="wp-block-group two"><!-- wp:group -->
<div class="wp-block-group">
<!-- wp:heading {"level":2,"anchor":"h-submit"} -->
<h2 id="h-submit" class="wp-block-heading">Add your event</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Food or not: pop-ups, markets, shows, art walks, classes and fundraisers. Listing is free. A premium listing is [ltef_price key="event_premium"] a month: pinned near the top of its week and labeled Premium listing.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:ltef/submit-form {"form":"event"} /--></div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
