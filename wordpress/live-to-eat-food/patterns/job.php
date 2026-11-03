<?php
/**
 * Title: Job
 * Slug: live-to-eat-food/job
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
<p class="is-style-meta"><a href="<?php echo $u( '/restaurant-jobs/' ); ?>">All jobs</a></p>
<!-- /wp:paragraph -->
<!-- wp:post-title {"level":1} /-->
<!-- wp:post-content {"className":"ltef-prose","layout":{"type":"default"}} /-->
</section>
<!-- /wp:group -->
