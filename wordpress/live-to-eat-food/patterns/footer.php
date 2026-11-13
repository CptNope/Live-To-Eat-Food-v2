<?php
/**
 * Title: Footer
 * Slug: live-to-eat-food/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 */
$u = function ( $p ) {
	return esc_url( home_url( $p ) );
};
?>
<!-- wp:group {"tagName":"footer","className":"ltef-footer","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"top"}} -->
<footer class="wp-block-group ltef-footer"><!-- wp:group {"style":{"layout":{"selfStretch":"fixed","flexSize":"560px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p><strong>Every pick is a real meal Tina ate.</strong> When a restaurant hosts her, the page says so. Ads and paid listings always say so too.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"ltef-credit"} -->
<p class="ltef-credit">Site designed and built by <a href="https://jeremyanderson.tech" target="_blank" rel="noreferrer noopener">Jeremy Anderson Tech</a>, a Live to Eat Food partner.</p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"ariaLabel":"Policies","overlayMenu":"never","className":"ltef-legal","layout":{"type":"flex","flexWrap":"wrap"}} -->
<!-- wp:navigation-link {"label":"Accessibility","url":"<?php echo $u( '/accessibility/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Privacy policy","url":"<?php echo $u( '/privacy-policy/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Terms of use","url":"<?php echo $u( '/terms/' ); ?>","kind":"custom"} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group -->

<!-- wp:navigation {"ariaLabel":"Footer","overlayMenu":"never","layout":{"type":"flex","flexWrap":"wrap"}} -->
<!-- wp:navigation-link {"label":"How it works","url":"<?php echo $u( '/how-it-works/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Editorial policy","url":"<?php echo $u( '/editorial-policy/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Food help","url":"<?php echo $u( '/food-help/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"For businesses","url":"<?php echo $u( '/for-restaurants/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Work with Tina","url":"<?php echo $u( '/work-with-tina/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"About Tina","url":"<?php echo $u( '/tina/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Monthly Crumbs","url":"<?php echo $u( '/crumbs/' ); ?>","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Send something in","url":"<?php echo $u( '/submit/' ); ?>","kind":"custom"} /-->
<!-- /wp:navigation --></footer>
<!-- /wp:group -->
