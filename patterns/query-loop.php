<?php
/**
 * Title: Post list
 * Slug: blammo/query-loop
 * Categories: blammo, query
 * Block Types: core/query
 * Inserter: no
 * Description: The main query's posts with excerpts, a no-results message and pagination. Used by the index, home, archive and search templates.
 */

?>
<!-- wp:query {"query":{"inherit":true},"layout":{"type":"default"}} -->
<div class="wp-block-query">
	<!-- wp:post-template -->
		<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group" style="padding-bottom:var(--wp--preset--spacing--50)">
			<!-- wp:post-title {"isLink":true,"fontSize":"large"} /-->
			<!-- wp:post-date /-->
			<!-- wp:post-excerpt /-->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->

	<!-- wp:query-no-results -->
		<!-- wp:paragraph -->
		<p><?php esc_html_e( 'Sorry, nothing matched. Try a search with different keywords.', 'blammo' ); ?></p>
		<!-- /wp:paragraph -->
	<!-- /wp:query-no-results -->

	<!-- wp:query-pagination {"paginationArrow":"arrow","layout":{"type":"flex","justifyContent":"space-between"}} -->
		<!-- wp:query-pagination-previous /-->
		<!-- wp:query-pagination-numbers /-->
		<!-- wp:query-pagination-next /-->
	<!-- /wp:query-pagination -->
</div>
<!-- /wp:query -->
