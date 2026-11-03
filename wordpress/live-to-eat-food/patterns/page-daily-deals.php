<?php
/**
 * Title: Daily deals
 * Slug: live-to-eat-food/page-daily-deals
 * Categories: ltef-pages
 * Block Types: core/post-content
 * Post Types: page
 * Description: The Daily deals page, ready to edit.
 * Viewport Width: 1400
 */
?>
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"butter","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-butter-background-color has-background"><!-- wp:heading {"level":1,"anchor":"h-daily"} -->
<h1 id="h-daily" class="wp-block-heading">Daily deals</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"is-style-lede"} -->
<p class="is-style-lede">Specials that come back every week, sorted by day. From Tina’s posts and from what places tell us. Specials change, so check before you go.</p>
<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"tell","className":"is-style-sheet","backgroundColor":"pistachio","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section id="tell" class="wp-block-group is-style-sheet has-pistachio-background-color has-background"><!-- wp:group {"className":"two"} -->
<div class="wp-block-group two"><!-- wp:heading {"anchor":"h-tell"} -->
<h2 id="h-tell" class="wp-block-heading">Know a weekly special?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Tell us the place, the day and the deal. Owners can add their own too, free, from <a href="<?php echo esc_url( home_url( '/for-restaurants/' ) ); ?>">For restaurants</a>.</p>
<!-- /wp:paragraph -->
<!-- wp:ltef/submit-form {"form":"special"} /--></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
