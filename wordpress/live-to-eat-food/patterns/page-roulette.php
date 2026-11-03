<?php
/**
 * Title: Restaurant Roulette
 * Slug: live-to-eat-food/page-roulette
 * Categories: ltef-pages
 * Block Types: core/post-content
 * Post Types: page
 * Description: The Restaurant Roulette page, ready to edit.
 * Viewport Width: 1400
 */
?>
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"basil","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group is-style-sheet has-basil-background-color has-background"><!-- wp:heading {"level":1,"anchor":"h-roul"} -->
<h1 id="h-roul" class="wp-block-heading">Restaurant Roulette</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"is-style-lede"} -->
<p class="is-style-lede">Can’t decide? Pick a craving, say who’s coming, spin. Spin just the places Tina has eaten at, or every restaurant on the site.</p>
<!-- /wp:paragraph -->
<!-- wp:ltef/roulette /--></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","anchor":"share","className":"is-style-sheet","backgroundColor":"butter","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section id="share" class="wp-block-group is-style-sheet has-butter-background-color has-background"><!-- wp:group {"className":"two"} -->
<div class="wp-block-group two"><!-- wp:heading {"anchor":"h-share"} -->
<h2 id="h-share" class="wp-block-heading">Spun it and went?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Show us what the wheel sent you to. Your photo goes on Your bites, credited to you, food first.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-alt"} -->
<div class="wp-block-button is-style-alt"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/your-bites/' ) ); ?>">See Your bites</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- wp:ltef/submit-form {"form":"photo"} /--></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
