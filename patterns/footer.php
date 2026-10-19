<?php
/**
 * Title: Footer
 * Slug: blammo/footer
 * Categories: blammo, footer
 * Block Types: core/template-part/footer
 * Description: Site title, tagline and credits.
 */

?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}},"border":{"top":{"color":"var:preset|color|border","width":"1px","style":"solid"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="border-top-color:var(--wp--preset--color--border);border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"top"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group">
			<!-- wp:site-title {"level":0} /-->
			<!-- wp:site-tagline /-->
		</div>
		<!-- /wp:group -->
		<!-- wp:paragraph {"fontSize":"small"} -->
		<p class="has-small-font-size">
			<?php
			printf(
				/* translators: %s: WordPress link. */
				esc_html__( 'Proudly powered by %s', 'blammo' ),
				'<a href="' . esc_url( __( 'https://wordpress.org/', 'blammo' ) ) . '">WordPress</a>'
			);
			?>
		</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
