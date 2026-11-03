<?php
/**
 * Title: Run a Woo-pon
 * Slug: live-to-eat-food/woopons-run
 * Categories: ltef-sections
 * Description: The three ways to run a Woo-pon, what happens next, and the form.
 */
$price = function ( $key, $fallback ) {
	return function_exists( 'ltef_setting' ) ? ltef_money( ltef_setting( $key ) ) : $fallback;
};
$weeks = function_exists( 'ltef_setting' ) ? (int) ltef_setting( 'woopon_free' ) : 2;
?>
<!-- wp:group {"tagName":"section","className":"is-style-sheet","backgroundColor":"butter","anchor":"submit","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section id="submit" class="wp-block-group is-style-sheet has-butter-background-color has-background"><!-- wp:group {"className":"head"} -->
<div class="wp-block-group head"><!-- wp:heading {"anchor":"h-own"} -->
<h2 id="h-own" class="wp-block-heading">Run a Woo-pon</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-meta"} -->
<p class="is-style-meta">Three ways to list a deal</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph -->
<p>Any local business can send one in. Tina reads every submission, usually within a day or two, and we can turn any deal down. Paying to list a Woo-pon never buys a pick: the ticket still says whether Tina has eaten there.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"tier-grid"} -->
<div class="wp-block-group tier-grid"><!-- wp:group {"className":"is-style-panel tier"} -->
<div class="wp-block-group is-style-panel tier"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Try it free</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tier__price"} -->
<p class="tier__price"><?php echo esc_html( $weeks ); ?> weeks free</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Your first Woo-pon runs <?php echo esc_html( $weeks ); ?> weeks at no cost. Once per business.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-panel tier"} -->
<div class="wp-block-group is-style-panel tier"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Weekly</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tier__price"} -->
<p class="tier__price"><?php echo esc_html( $price( 'woopon_weekly', '$10' ) ); ?> a week</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Keep a deal up for as long as you like. Stop any week.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-panel tier tier--vip"} -->
<div class="wp-block-group is-style-panel tier tier--vip"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">VIP Woo-pon</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tier__price"} -->
<p class="tier__price"><?php echo esc_html( $price( 'woopon_vip', '$100' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Tina presents your Woo-pon in her monthly video, and the ticket carries the VIP label. Send a flyer with it: she shows it in the video. Up to 10 a month.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"two"} -->
<div class="wp-block-group two"><!-- wp:group -->
<div class="wp-block-group"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">What happens next</h3>
<!-- /wp:heading -->

<!-- wp:list {"ordered":true} -->
<ol class="wp-block-list"><!-- wp:list-item -->
<li>Send the deal, the dates, what it excludes and a phone number we can reach. For a VIP Woo-pon, attach a flyer for the video.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Tina checks the business and the deal are real, and calls if anything’s unclear. For a paid option, we confirm the price by email before anything is charged.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>It goes live on the start date as a ticket people show at the counter, and comes down on the end date.</li>
<!-- /wp:list-item --></ol>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:ltef/submit-form {"form":"woopon"} /--></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
