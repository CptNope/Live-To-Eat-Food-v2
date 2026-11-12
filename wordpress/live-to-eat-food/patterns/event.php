<?php
/**
 * Title: Event
 * Slug: live-to-eat-food/event
 * Categories: ltef-sections
 * Inserter: no
 */
$u = function ( $p ) {
	return esc_url( home_url( $p ) );
};
?>
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"butter","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-butter-background-color has-background">
<!-- wp:paragraph {"className":"is-style-meta"} -->
<p class="is-style-meta"><a href="<?php echo $u( '/events/' ); ?>">All events</a></p>
<!-- /wp:paragraph -->
<!-- wp:post-title {"level":1} /-->
<!-- wp:ltef/event-facts /-->
<!-- wp:post-content {"className":"ltef-prose","layout":{"type":"default"}} /-->
</section>
<!-- /wp:group -->
